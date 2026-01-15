import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Toaster } from '@bsf/force-ui';
import useLearn from '../useLearn';
import LearnChapters from './LearnChapters';
import LearnSkeleton from './LearnSkeleton';
import LearnHowDialog from './LearnHowDialog';

/**
 * Complete Learn component that manages chapters and steps
 *
 * This is a complete wrapper component that handles all the logic internally.
 * Just pass your chapters data OR API endpoints to fetch and save the data.
 *
 * @param {Object}   props                  - Component props
 * @param {Array}    props.chapters         - Array of chapter objects with steps (optional if endpoints provided)
 * @param {Object}   props.endpoints        - API endpoints object (optional if chapters provided)
 * @param {string}   props.className        - Optional className for wrapper
 * @param {Function} props.onProgressChange - Optional callback when progress changes
 * @return {JSX.Element} - Rendered learn component
 *
 * @example
 * // With direct data
 * <Learn
 *   chapters={chaptersData}
 * />
 *
 * @example
 * // With API endpoints
 * <Learn
 *   endpoints={{
 *     get: "/sureforms/v1/learn-chapters",
 *     set: "/sureforms/v1/learn-progress"
 *   }}
 * />
 */
const Learn = ( {
	chapters: initialChapters = [],
	endpoints = null,
	className = '',
	onProgressChange,
} ) => {
	const [ apiChapters, setApiChapters ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	// Fetch chapters from API if endpoint is provided
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
				setApiChapters( response );
				setIsLoading( false );
			} )
			.catch( ( err ) => {
				setError( err.message || 'Failed to load chapters' );
				setIsLoading( false );
			} );

		return () => {
			abortController.abort();
		};
	}, [ endpoints?.get ] );

	// Determine which chapters to use - API data or prop data
	const chaptersToUse = endpoints?.get ? apiChapters : initialChapters;

	// Initialize the hook with provided data
	const {
		chapters,
		updateStepCompletion,
		firstIncompleteChapterId,
		progressStats,
		learnHowDialogOpen,
		currentLearnHowItem,
		openLearnHowDialog,
		setLearnHowDialogOpen,
	} = useLearn( {
		initialChapters: chaptersToUse,
		saveEndpoint: endpoints?.set,
	} );

	// Call progress change callback if provided
	if ( onProgressChange && typeof onProgressChange === 'function' ) {
		onProgressChange( progressStats );
	}

	// Show loading skeleton
	if ( isLoading ) {
		return (
			<div className={ `flex flex-col gap-2 ${ className } !bg-transparent` }>
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

	// If no chapters, return null
	if ( ! chapters || chapters.length === 0 ) {
		return null;
	}

	return (
		<div className={ className }>
			<LearnChapters
				chapters={ chapters }
				defaultValue={ firstIncompleteChapterId }
				onStepCompletionChange={ updateStepCompletion }
				onLearnHowClick={ openLearnHowDialog }
			/>

			<LearnHowDialog
				open={ learnHowDialogOpen }
				setOpen={ setLearnHowDialogOpen }
				item={ currentLearnHowItem }
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
