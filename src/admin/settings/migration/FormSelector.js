/**
 * FormSelector — step 2 of the migration wizard.
 *
 * Renders the list of forms in the previously-chosen source plugin with
 * checkbox selection. Forms that have already been imported show an
 * "Imported" badge so the user knows a re-import will update the existing
 * SureForms post rather than create a duplicate.
 *
 * @since x.x.x
 */

import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import {
	Badge,
	Button,
	Checkbox,
	Container,
	Loader,
	Select,
	Text,
	Title,
} from '@bsf/force-ui';
import { ArrowLeft, ArrowRight, ExternalLink, RefreshCcw } from 'lucide-react';
import { listForms } from './api';

// Re-import behavior options for forms that already have a SureForms copy.
const BEHAVIOR_OPTIONS = [
	{ value: 'update', label: __( 'Update existing', 'sureforms' ) },
	{ value: 'skip', label: __( 'Skip', 'sureforms' ) },
	{ value: 'create', label: __( 'Create a new copy', 'sureforms' ) },
];

const labelForBehavior = ( value ) =>
	BEHAVIOR_OPTIONS.find( ( o ) => o.value === value )?.label ?? value;

const FormSelector = ( { source, onBack, onContinue } ) => {
	const [ forms, setForms ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );
	const [ selected, setSelected ] = useState( () => new Set() );
	// behaviorMap: { [sourceFormId: string]: 'update' | 'skip' | 'create' }.
	// Only populated for forms that have a prior SureForms import; new forms
	// don't need an entry (the backend defaults to insert).
	const [ behaviorMap, setBehaviorMap ] = useState( {} );

	useEffect( () => {
		let cancelled = false;
		setLoading( true );
		setSelected( new Set() );
		setBehaviorMap( {} );
		listForms( source.key )
			.then( ( res ) => {
				if ( cancelled ) {
					return;
				}
				setForms( Array.isArray( res?.forms ) ? res.forms : [] );
				setLoading( false );
			} )
			.catch( ( err ) => {
				if ( cancelled ) {
					return;
				}
				// Surface the server's message when one is provided — the WP
				// REST error envelope puts the human-readable string on
				// `err.message` (apiFetch unwraps `data.message` automatically).
				setError(
					err?.message ||
						__(
							'Could not load forms for this source.',
							'sureforms'
						)
				);
				setLoading( false );
			} );
		return () => {
			cancelled = true;
		};
	}, [ source.key ] );

	const toggle = ( id, isImported ) => {
		const key = String( id );
		const next = new Set( selected );
		if ( next.has( key ) ) {
			next.delete( key );
		} else {
			next.add( key );
		}
		setSelected( next );
		// Seed the behavior for previously-imported forms when they're picked
		// for the first time so the dropdown has a real value to bind to.
		if ( isImported && next.has( key ) && ! behaviorMap[ key ] ) {
			setBehaviorMap( ( m ) => ( { ...m, [ key ]: 'update' } ) );
		}
	};

	const setBehavior = ( id, value ) =>
		setBehaviorMap( ( m ) => ( { ...m, [ String( id ) ]: value } ) );

	const toggleAll = () => {
		if ( selected.size === forms.length ) {
			setSelected( new Set() );
			return;
		}
		const next = new Set( forms.map( ( f ) => String( f.id ) ) );
		setSelected( next );
		// Default behavior for every previously-imported form just added.
		setBehaviorMap( ( m ) => {
			const updated = { ...m };
			forms.forEach( ( f ) => {
				if ( f.imported_srfm_id > 0 && ! updated[ String( f.id ) ] ) {
					updated[ String( f.id ) ] = 'update';
				}
			} );
			return updated;
		} );
	};

	// Are all selected rows previously-imported? Used to flip the CTA label.
	const allSelectedAreImported = useMemo( () => {
		if ( selected.size === 0 ) {
			return false;
		}
		const byId = new Map( forms.map( ( f ) => [ String( f.id ), f ] ) );
		for ( const sid of selected ) {
			const f = byId.get( sid );
			if ( ! f || ! ( f.imported_srfm_id > 0 ) ) {
				return false;
			}
		}
		return true;
	}, [ selected, forms ] );

	if ( loading ) {
		return (
			<div className="flex items-center justify-center p-12">
				<Loader />
			</div>
		);
	}

	if ( error ) {
		return (
			<Container direction="column" gap="md">
				<Text size={ 14 } className="text-text-error">
					{ error }
				</Text>
				<Button
					variant="outline"
					size="sm"
					onClick={ onBack }
					icon={ <ArrowLeft /> }
					iconPosition="left"
				>
					{ __( 'Go back', 'sureforms' ) }
				</Button>
			</Container>
		);
	}

	const allSelected = forms.length > 0 && selected.size === forms.length;
	const selectedIds = Array.from( selected );

	return (
		<Container direction="column" gap="lg">
			<Container direction="column" gap="xs">
				<Title
					size="md"
					tag="h3"
					title={ sprintf(
						/* translators: %s: source plugin display name. */
						__( 'Choose forms to import from %s', 'sureforms' ),
						source.title
					) }
				/>
				<Text size={ 14 } color="secondary">
					{ forms.length === 0
						? __(
							'No forms found in this plugin.',
							'sureforms'
						  )
						: sprintf(
							/* translators: %d: total forms. */
							_n(
								'%d form found. Select the ones you want to migrate.',
								'%d forms found. Select the ones you want to migrate.',
								forms.length,
								'sureforms'
							),
							forms.length
						  ) }
				</Text>
			</Container>

			{ forms.length > 0 && (
				<div className="rounded-lg border border-border-subtle bg-background-primary overflow-hidden">
					<div className="flex items-center justify-between px-4 py-3 border-b border-border-subtle bg-background-secondary">
						<Checkbox
							size="sm"
							checked={ allSelected }
							onChange={ toggleAll }
							label={ {
								heading: __( 'Select all', 'sureforms' ),
							} }
						/>
						<Text size={ 13 } color="secondary">
							{ sprintf(
								/* translators: 1: selected count, 2: total. */
								__( '%1$d of %2$d selected', 'sureforms' ),
								selected.size,
								forms.length
							) }
						</Text>
					</div>
					<ul className="divide-y divide-border-subtle">
						{ forms.map( ( form ) => {
							const sid = String( form.id );
							const isImported = form.imported_srfm_id > 0;
							const isSelected = selected.has( sid );
							const behavior = behaviorMap[ sid ] || 'update';
							return (
								<li
									key={ form.id }
									className="flex items-center justify-between gap-3 px-4 py-3"
								>
									<Checkbox
										size="sm"
										checked={ isSelected }
										onChange={ () =>
											toggle( form.id, isImported )
										}
										label={ {
											heading:
												form.name ||
												__(
													'(untitled form)',
													'sureforms'
												),
										} }
									/>
									{ isImported && (
										<div className="flex items-center gap-2">
											<Badge
												variant="blue"
												size="xs"
												icon={
													<RefreshCcw className="size-3" />
												}
												label={ __(
													'Previously imported',
													'sureforms'
												) }
											/>
											{ form.imported_srfm_edit_url && (
												<a
													href={
														form.imported_srfm_edit_url
													}
													target="_blank"
													rel="noopener noreferrer"
													className="inline-flex items-center gap-1 text-xs text-text-tertiary hover:text-text-primary"
													aria-label={ __(
														'Open existing SureForms form in a new tab',
														'sureforms'
													) }
												>
													<ExternalLink className="size-3" />
												</a>
											) }
											{ isSelected && (
												<div className="min-w-[140px]">
													<Select
														size="sm"
														value={ behavior }
														onChange={ ( v ) =>
															setBehavior(
																form.id,
																v
															)
														}
													>
														<Select.Button>
															{ labelForBehavior(
																behavior
															) }
														</Select.Button>
														<Select.Options className="z-999999">
															{ BEHAVIOR_OPTIONS.map(
																( opt ) => (
																	<Select.Option
																		key={
																			opt.value
																		}
																		value={
																			opt.value
																		}
																	>
																		{
																			opt.label
																		}
																	</Select.Option>
																)
															) }
														</Select.Options>
													</Select>
												</div>
											) }
										</div>
									) }
								</li>
							);
						} ) }
					</ul>
				</div>
			) }

			<div className="flex items-center justify-between">
				<Button
					variant="outline"
					size="sm"
					onClick={ onBack }
					icon={ <ArrowLeft /> }
					iconPosition="left"
				>
					{ __( 'Back', 'sureforms' ) }
				</Button>
				<Button
					variant="primary"
					size="sm"
					disabled={ selectedIds.length === 0 }
					onClick={ () =>
						onContinue( selectedIds, behaviorMap )
					}
					icon={ <ArrowRight /> }
					iconPosition="right"
				>
					{ allSelectedAreImported
						? __( 'Preview update', 'sureforms' )
						: __( 'Preview import', 'sureforms' ) }
				</Button>
			</div>
		</Container>
	);
};

export default FormSelector;
