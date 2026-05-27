/**
 * FormSelector — the "select forms" view of the Migration tab.
 *
 * Renders the source plugin's forms in a native force-ui Table (the same
 * listing pattern used by Entries / Forms / Payments). Each row offers
 * selection; previously-imported forms show a badge, a link to the existing
 * SureForms post, and a re-import behavior control (update / skip / create).
 *
 * @since x.x.x
 */

import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import { Badge, Button, Container, Select, Text } from '@bsf/force-ui';
import { ArrowRight, ExternalLink, RefreshCcw } from 'lucide-react';
import Table from '@Admin/common/listing/components/Table';
import LoadingSkeleton from '@Admin/components/LoadingSkeleton';
import { listForms } from './api';

// Re-import behavior options for forms that already have a SureForms copy.
const BEHAVIOR_OPTIONS = [
	{ value: 'update', label: __( 'Update existing', 'sureforms' ) },
	{ value: 'skip', label: __( 'Skip', 'sureforms' ) },
	{ value: 'create', label: __( 'Create a new copy', 'sureforms' ) },
];

const labelForBehavior = ( value ) =>
	BEHAVIOR_OPTIONS.find( ( o ) => o.value === value )?.label ?? value;

const FormSelector = ( { source, toolbar = null, onContinue } ) => {
	const [ forms, setForms ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );
	// Set of selected source-form ids (raw form.id values).
	const [ selected, setSelected ] = useState( () => new Set() );
	// behaviorMap: { [sourceFormId: string]: 'update' | 'skip' | 'create' }.
	// Only populated for previously-imported forms (new forms default to insert).
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
				// Surface the server's message when provided.
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

	const seedBehavior = ( form ) => {
		if ( form.imported_srfm_id > 0 ) {
			setBehaviorMap( ( m ) =>
				m[ String( form.id ) ]
					? m
					: { ...m, [ String( form.id ) ]: 'update' }
			);
		}
	};

	const handleRowSelection = ( isSelected, form ) => {
		setSelected( ( prev ) => {
			const next = new Set( prev );
			if ( isSelected ) {
				next.add( form.id );
			} else {
				next.delete( form.id );
			}
			return next;
		} );
		if ( isSelected ) {
			seedBehavior( form );
		}
	};

	const handleToggleAll = ( checked ) => {
		if ( ! checked ) {
			setSelected( new Set() );
			return;
		}
		setSelected( new Set( forms.map( ( f ) => f.id ) ) );
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

	const setBehavior = ( id, value ) =>
		setBehaviorMap( ( m ) => ( { ...m, [ String( id ) ]: value } ) );

	// Are all selected rows previously-imported? Flips the CTA label.
	const allSelectedAreImported = useMemo( () => {
		if ( selected.size === 0 ) {
			return false;
		}
		const byId = new Map( forms.map( ( f ) => [ f.id, f ] ) );
		for ( const id of selected ) {
			const f = byId.get( id );
			if ( ! f || ! ( f.imported_srfm_id > 0 ) ) {
				return false;
			}
		}
		return true;
	}, [ selected, forms ] );

	const columns = [
		{
			key: 'name',
			label: __( 'Form', 'sureforms' ),
			render: ( form ) => (
				<Text size={ 14 }>
					{ form.name || __( '(untitled form)', 'sureforms' ) }
				</Text>
			),
		},
		{
			key: 'status',
			label: __( 'Status', 'sureforms' ),
			render: ( form ) =>
				form.imported_srfm_id > 0 ? (
					<Container align="center" gap="xs">
						<Badge
							variant="blue"
							size="xs"
							icon={ <RefreshCcw className="size-3" /> }
							label={ __( 'Previously imported', 'sureforms' ) }
						/>
						{ form.imported_srfm_edit_url && (
							<a
								href={ form.imported_srfm_edit_url }
								target="_blank"
								rel="noopener noreferrer"
								className="inline-flex items-center text-text-tertiary hover:text-text-primary"
								aria-label={ __(
									'Open existing SureForms form in a new tab',
									'sureforms'
								) }
							>
								<ExternalLink className="size-3" />
							</a>
						) }
					</Container>
				) : (
					<Badge
						variant="green"
						size="xs"
						label={ __( 'New', 'sureforms' ) }
					/>
				),
		},
		{
			key: 'behavior',
			label: __( 'On re-import', 'sureforms' ),
			render: ( form ) => {
				if ( ! ( form.imported_srfm_id > 0 ) ) {
					return <Text size={ 13 } color="secondary">—</Text>;
				}
				const behavior = behaviorMap[ String( form.id ) ] || 'update';
				return (
					<div className="min-w-[150px]">
						<Select
							size="sm"
							value={ behavior }
							onChange={ ( v ) => setBehavior( form.id, v ) }
						>
							<Select.Button>
								{ labelForBehavior( behavior ) }
							</Select.Button>
							<Select.Options className="z-999999">
								{ BEHAVIOR_OPTIONS.map( ( opt ) => (
									<Select.Option
										key={ opt.value }
										value={ opt.value }
									>
										{ opt.label }
									</Select.Option>
								) ) }
							</Select.Options>
						</Select>
					</div>
				);
			},
		},
	];

	if ( loading ) {
		return <LoadingSkeleton count={ 4 } height={ 25 } />;
	}

	if ( error ) {
		return (
			<Container direction="column" gap="md">
				<Text size={ 14 } className="text-text-error">
					{ error }
				</Text>
			</Container>
		);
	}

	const selectedIds = Array.from( selected ).map( String );
	const indeterminate = selected.size > 0 && selected.size < forms.length;

	return (
		<Container direction="column" gap="md">
			{ toolbar }

			<Table
				data={ forms }
				columns={ columns }
				selectedItems={ Array.from( selected ) }
				onToggleAll={ handleToggleAll }
				onChangeRowSelection={ handleRowSelection }
				indeterminate={ indeterminate }
				emptyMessage={ __(
					'No forms found in this plugin.',
					'sureforms'
				) }
			/>

			<Container align="center" justify="between">
				<Text size={ 13 } color="secondary">
					{ sprintf(
						/* translators: 1: selected count, 2: total forms. */
						_n(
							'%1$d of %2$d form selected',
							'%1$d of %2$d forms selected',
							forms.length,
							'sureforms'
						),
						selected.size,
						forms.length
					) }
				</Text>
				<Button
					variant="primary"
					size="sm"
					disabled={ selectedIds.length === 0 }
					onClick={ () =>
						onContinue(
							selectedIds,
							behaviorMap,
							forms.filter( ( f ) => selected.has( f.id ) )
						)
					}
					icon={ <ArrowRight /> }
					iconPosition="right"
				>
					{ allSelectedAreImported
						? __( 'Preview update', 'sureforms' )
						: __( 'Preview import', 'sureforms' ) }
				</Button>
			</Container>
		</Container>
	);
};

export default FormSelector;
