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

import { useEffect, useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import {
	Badge,
	Button,
	Checkbox,
	Container,
	Loader,
	Text,
	Title,
} from '@bsf/force-ui';
import { ArrowLeft, ArrowRight, RefreshCcw } from 'lucide-react';
import { listForms } from './api';

const FormSelector = ( { source, onBack, onContinue } ) => {
	const [ forms, setForms ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );
	const [ selected, setSelected ] = useState( () => new Set() );

	useEffect( () => {
		let cancelled = false;
		setLoading( true );
		setSelected( new Set() );
		listForms( source.key )
			.then( ( res ) => {
				if ( cancelled ) {
					return;
				}
				setForms( Array.isArray( res?.forms ) ? res.forms : [] );
				setLoading( false );
			} )
			.catch( () => {
				if ( cancelled ) {
					return;
				}
				setError(
					__( 'Could not load forms for this source.', 'sureforms' )
				);
				setLoading( false );
			} );
		return () => {
			cancelled = true;
		};
	}, [ source.key ] );

	const toggle = ( id ) => {
		const next = new Set( selected );
		const key = String( id );
		if ( next.has( key ) ) {
			next.delete( key );
		} else {
			next.add( key );
		}
		setSelected( next );
	};

	const toggleAll = () => {
		if ( selected.size === forms.length ) {
			setSelected( new Set() );
			return;
		}
		setSelected( new Set( forms.map( ( f ) => String( f.id ) ) ) );
	};

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
						{ forms.map( ( form ) => (
							<li
								key={ form.id }
								className="flex items-center justify-between gap-3 px-4 py-3"
							>
								<Checkbox
									size="sm"
									checked={ selected.has(
										String( form.id )
									) }
									onChange={ () => toggle( form.id ) }
									label={ {
										heading:
											form.name ||
											__(
												'(untitled form)',
												'sureforms'
											),
									} }
								/>
								{ form.imported_srfm_id > 0 && (
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
								) }
							</li>
						) ) }
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
					onClick={ () => onContinue( selectedIds ) }
					icon={ <ArrowRight /> }
					iconPosition="right"
				>
					{ __( 'Preview import', 'sureforms' ) }
				</Button>
			</div>
		</Container>
	);
};

export default FormSelector;
