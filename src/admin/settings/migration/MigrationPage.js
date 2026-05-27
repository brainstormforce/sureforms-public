/**
 * MigrationPage — top-level component for the Migration tab.
 *
 * Single-screen importer (no wizard). Lands on a table of available source
 * plugins (SourcePicker); choosing one drills into its forms (FormSelector),
 * then a human-readable review (DryRunPreview), then an inline result
 * (ImportResult). Mounted from `src/admin/settings/Component.js` when the URL
 * `tab` query param equals `migration-settings`.
 *
 * @since x.x.x
 */

import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Text } from '@bsf/force-ui';
import SourcePicker from './SourcePicker';
import FormSelector from './FormSelector';
import DryRunPreview from './DryRunPreview';
import ImportResult from './ImportResult';
import LoadingSkeleton from '@Admin/components/LoadingSkeleton';
import { listSources } from './api';

const MigrationPage = () => {
	const [ sources, setSources ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );
	const [ source, setSource ] = useState( null );

	// view: 'sources' (pick plugin) → 'select' (choose forms) → 'review' → 'result'.
	const [ view, setView ] = useState( 'sources' );
	const [ formIds, setFormIds ] = useState( [] );
	const [ behaviorMap, setBehaviorMap ] = useState( {} );
	const [ selectedForms, setSelectedForms ] = useState( [] );
	const [ result, setResult ] = useState( null );

	useEffect( () => {
		let cancelled = false;
		listSources()
			.then( ( res ) => {
				if ( cancelled ) {
					return;
				}
				setSources( Array.isArray( res?.sources ) ? res.sources : [] );
				setLoading( false );
			} )
			.catch( () => {
				if ( cancelled ) {
					return;
				}
				setError(
					__(
						'Could not load the list of importable plugins.',
						'sureforms'
					)
				);
				setLoading( false );
			} );
		return () => {
			cancelled = true;
		};
	}, [] );

	const handleSourceSelect = ( picked ) => {
		setSource( picked );
		setView( 'select' );
	};

	const handleFormsContinue = ( ids, behavior, forms ) => {
		setFormIds( ids );
		setBehaviorMap( behavior );
		setSelectedForms( forms || [] );
		setView( 'review' );
	};

	const handleImportConfirmed = ( importResult ) => {
		setResult( importResult );
		setView( 'result' );
	};

	const handleBackToSources = () => {
		setSource( null );
		setFormIds( [] );
		setBehaviorMap( {} );
		setSelectedForms( [] );
		setView( 'sources' );
	};

	const handleRestart = () => {
		setResult( null );
		handleBackToSources();
	};

	if ( loading ) {
		return <LoadingSkeleton count={ 4 } height={ 25 } />;
	}

	if ( error ) {
		return (
			<Text size={ 14 } className="text-text-error">
				{ error }
			</Text>
		);
	}

	if ( 'result' === view ) {
		return <ImportResult result={ result } onRestart={ handleRestart } />;
	}

	if ( 'review' === view && source ) {
		return (
			<DryRunPreview
				source={ source }
				formIds={ formIds }
				behavior={ behaviorMap }
				selectedForms={ selectedForms }
				onBack={ () => setView( 'select' ) }
				onConfirm={ handleImportConfirmed }
			/>
		);
	}

	if ( 'select' === view && source ) {
		return (
			<FormSelector
				key={ source.key }
				source={ source }
				onBack={ handleBackToSources }
				onContinue={ handleFormsContinue }
			/>
		);
	}

	return <SourcePicker sources={ sources } onSelect={ handleSourceSelect } />;
};

export default MigrationPage;
