/**
 * MigrationPage — top-level component for the Migration tab.
 *
 * Single-screen importer (no wizard): pick a source plugin (auto-selected when
 * only one is installed, a dropdown when several are), choose forms in a native
 * table, review a human-readable summary, then import — with the result shown
 * inline. Mounted from `src/admin/settings/Component.js` when the URL `tab`
 * query param equals `migration-settings`.
 *
 * @since x.x.x
 */

import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Container, Select, Text } from '@bsf/force-ui';
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

	// view: 'select' (choose forms) → 'review' (confirm) → 'result' (done).
	const [ view, setView ] = useState( 'select' );
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
				const list = Array.isArray( res?.sources ) ? res.sources : [];
				setSources( list );
				// Auto-select the first installed source that has forms.
				const firstUsable = list.find(
					( s ) => s.installed && Number( s.form_count ?? 0 ) > 0
				);
				setSource( firstUsable || null );
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

	const handleRestart = () => {
		setFormIds( [] );
		setBehaviorMap( {} );
		setSelectedForms( [] );
		setResult( null );
		setView( 'select' );
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

	// Installed sources that can actually be imported from.
	const usableSources = sources.filter(
		( s ) => s.installed && Number( s.form_count ?? 0 ) > 0
	);

	if ( ! source ) {
		return (
			<Text size={ 14 }>
				{ __(
					'No importable form plugins detected. Activate a supported plugin (e.g. Contact Form 7) with at least one form to begin.',
					'sureforms'
				) }
			</Text>
		);
	}

	// A compact source switcher, shown only when more than one source is
	// available. With a single source it is auto-selected and the control is
	// hidden — keeping the screen as lean as the other settings tabs.
	const sourceToolbar =
		usableSources.length > 1 ? (
			<Container align="center" gap="sm">
				<Text size={ 13 } color="secondary">
					{ __( 'Import from', 'sureforms' ) }
				</Text>
				<div className="min-w-[200px]">
					<Select
						size="sm"
						value={ source.key }
						onChange={ ( key ) => {
							const next = usableSources.find(
								( s ) => s.key === key
							);
							if ( next ) {
								setSource( next );
							}
						} }
					>
						<Select.Button>{ source.title }</Select.Button>
						<Select.Options className="z-999999">
							{ usableSources.map( ( s ) => (
								<Select.Option key={ s.key } value={ s.key }>
									{ s.title }
								</Select.Option>
							) ) }
						</Select.Options>
					</Select>
				</div>
			</Container>
		) : null;

	if ( 'result' === view ) {
		return <ImportResult result={ result } onRestart={ handleRestart } />;
	}

	if ( 'review' === view ) {
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

	return (
		<FormSelector
			key={ source.key }
			source={ source }
			toolbar={ sourceToolbar }
			onContinue={ handleFormsContinue }
		/>
	);
};

export default MigrationPage;
