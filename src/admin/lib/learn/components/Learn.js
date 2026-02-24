import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Toaster } from '@bsf/force-ui';
import useLearn from '../useLearn';
import LearnModules from './LearnModules';
import LearnSkeleton from './LearnSkeleton';

/**
 * Complete Learn component that manages modules and lessons
 *
 * This is a complete wrapper component that handles all the logic internally.
 * Just pass your modules data OR API endpoints to fetch and save the data.
 *
 * @param {Object}   props                  - Component props
 * @param {Array}    props.modules          - Array of module objects with lessons (optional if endpoints provided)
 * @param {Object}   props.endpoints        - API endpoints object (optional if modules provided)
 * @param {string}   props.className        - Optional className for wrapper
 * @param {Function} props.onProgressChange - Optional callback when progress changes
 * @return {JSX.Element} - Rendered learn component
 *
 * @example
 * // With direct data
 * <Learn
 *   modules={modulesData}
 * />
 *
 * @example
 * // With API endpoints
 * <Learn
 *   endpoints={{
 *     get: "/sureforms/v1/get-learn-chapters",
 *     set: "/sureforms/v1/update-learn-progress"
 *   }}
 * />
 */
const Learn = ( {
	modules: initialModules = [],
	endpoints = null,
	className = '',
	onProgressChange,
} ) => {
	const [ apiModules, setApiModules ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ latestFormId, setLatestFormId ] = useState( undefined );

	const fetchLatestFormId = useCallback( () => {
		apiFetch( {
			path: '/wp/v2/sureforms_form?per_page=1&orderby=date&order=desc&status=any',
		} )
			.then( ( forms ) => {
				setLatestFormId( forms?.length > 0 ? forms[ 0 ].id : null );
			} )
			.catch( () => {
				// On error: leave as undefined so button stays enabled (safe fallback)
			} );
	}, [] );

	useEffect( () => {
		fetchLatestFormId();
	}, [ fetchLatestFormId ] );

	useEffect( () => {
		const handleVisibilityChange = () => {
			if ( document.visibilityState === 'visible' ) {
				fetchLatestFormId();
			}
		};
		document.addEventListener( 'visibilitychange', handleVisibilityChange );
		return () => {
			document.removeEventListener( 'visibilitychange', handleVisibilityChange );
		};
	}, [ fetchLatestFormId ] );

	// Fetch modules from API if endpoint is provided
	useEffect( () => {
		if ( ! endpoints?.get ) {
			return;
		}

		const abortController = new AbortController();

		setIsLoading( true );
		setError( null );

		apiFetch( {
			path: endpoints.get,
			signal: abortController.signal,
		} )
			.then( ( response ) => {
				setApiModules( response );
				setIsLoading( false );
			} )
			.catch( ( err ) => {
				// AbortError is expected when the component unmounts — not a real error.
				if ( err.name === 'AbortError' ) {
					return;
				}
				setError( err.message || 'Failed to load modules' );
				setIsLoading( false );
			} );

		return () => {
			abortController.abort();
		};
	}, [ endpoints?.get ] );

	// Determine which modules to use - API data or prop data
	const modulesToUse = endpoints?.get ? apiModules : initialModules;

	// Initialize the hook with provided data
	const {
		modules,
		updateLessonCompletion,
		markAllComplete,
		firstIncompleteModuleId,
		progressStats,
	} = useLearn( {
		initialModules: modulesToUse,
		saveEndpoint: endpoints?.set,
	} );

	// Call progress change callback when stats change
	useEffect( () => {
		// Only fire after data has loaded to avoid misleading zero-state callbacks.
		if (
			onProgressChange &&
			typeof onProgressChange === 'function' &&
			modules.length > 0
		) {
			onProgressChange( progressStats );
		}
	}, [ progressStats, onProgressChange, modules ] );

	// Show loading skeleton
	if ( isLoading ) {
		return (
			<div className={ className }>
				<LearnSkeleton />
			</div>
		);
	}

	// Show error message
	if ( error ) {
		return (
			<div className={ className }>
				<div className="text-error p-4">
					{ error }
				</div>
			</div>
		);
	}

	// If no modules, return null
	if ( ! modules || modules.length === 0 ) {
		return null;
	}

	return (
		<div className={ className }>
			<LearnModules
				modules={ modules }
				defaultValue={ firstIncompleteModuleId }
				onLessonCompletionChange={ updateLessonCompletion }
				onMarkAllComplete={ markAllComplete }
				latestFormId={ latestFormId }
			/>

			<Toaster
				position="top-right"
				design="stack"
				theme="light"
				autoDismiss={ true }
				dismissAfter={ 5000 }
			/>
		</div>
	);
};

export default Learn;
