/**
 * DryRunPreview — step 3 of the migration wizard.
 *
 * Calls the migrator import endpoint with `dry_run: true`, then renders the
 * resulting block markup in a code pane and any unsupported-field warnings
 * in an alert callout. From here the user either commits the import or
 * goes back to reselect.
 *
 * @since x.x.x
 */

import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import {
	Alert,
	Button,
	Container,
	Loader,
	Text,
	Title,
} from '@bsf/force-ui';
import { ArrowLeft, AlertTriangle, Check } from 'lucide-react';
import { importForms } from './api';

const DryRunPreview = ( {
	source,
	formIds,
	behavior = {},
	onBack,
	onConfirm,
} ) => {
	const [ data, setData ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );
	const [ submitting, setSubmitting ] = useState( false );
	// Double-submit guard — synchronous so two rapid clicks can't both pass
	// the in-flight check before React re-renders with `submitting = true`.
	const inFlight = useRef( false );

	useEffect( () => {
		let cancelled = false;
		setLoading( true );
		setError( '' );
		importForms( source.key, formIds, true, behavior )
			.then( ( res ) => {
				if ( cancelled ) {
					return;
				}
				setData( res );
				setLoading( false );
			} )
			.catch( ( err ) => {
				if ( cancelled ) {
					return;
				}
				setError(
					err?.message ||
						__(
							'Could not generate the import preview.',
							'sureforms'
						)
				);
				setLoading( false );
			} );
		return () => {
			cancelled = true;
		};
		// `behavior` participates because changing it (e.g. choosing "skip")
		// should re-flow the preview text. Reference identity is stable
		// because MigrationPage seeds it once per step transition.
	}, [ source.key, formIds, behavior ] );

	const handleConfirm = async () => {
		if ( inFlight.current ) {
			return;
		}
		inFlight.current = true;
		setSubmitting( true );
		try {
			const res = await importForms(
				source.key,
				formIds,
				false,
				behavior
			);
			onConfirm( res );
		} catch ( e ) {
			setError(
				e?.message ||
					__(
						'Import failed. Please try again or check your error logs.',
						'sureforms'
					)
			);
			setSubmitting( false );
			inFlight.current = false;
		}
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

	const previewEntries = data?.preview ? Object.entries( data.preview ) : [];
	const unsupported = Array.isArray( data?.unsupported_fields )
		? data.unsupported_fields
		: [];
	const failed = Array.isArray( data?.failed ) ? data.failed : [];

	// Choose the CTA label based on the behavior the user picked on step 2.
	// If at least one form is being updated and none are being created fresh,
	// "Update & import" reads more accurately than "Confirm & import".
	const behaviorValues = Object.values( behavior );
	const hasUpdates = behaviorValues.includes( 'update' );
	const hasCreates = behaviorValues.includes( 'create' );
	const confirmLabel =
		hasUpdates && ! hasCreates
			? __( 'Update & import', 'sureforms' )
			: __( 'Confirm & import', 'sureforms' );

	return (
		<Container direction="column" gap="lg">
			<Container direction="column" gap="xs">
				<Title
					size="md"
					tag="h3"
					title={ __( 'Review the migration preview', 'sureforms' ) }
				/>
				<Text size={ 14 } color="secondary">
					{ sprintf(
						/* translators: %d: number of forms in this batch. */
						_n(
							'This is what %d form will look like in SureForms after import. Nothing has been saved yet.',
							'This is what %d forms will look like in SureForms after import. Nothing has been saved yet.',
							previewEntries.length,
							'sureforms'
						),
						previewEntries.length
					) }
				</Text>
			</Container>

			{ unsupported.length > 0 && (
				<Alert
					variant="warning"
					title={ __(
						"Some fields can't be migrated yet",
						'sureforms'
					) }
					content={
						<Container direction="column" gap="2xs">
							<Text size={ 13 }>
								{ __(
									'These fields will be skipped during import — add them manually after the form is created:',
									'sureforms'
								) }
							</Text>
							<ul className="list-disc pl-5 mt-1">
								{ unsupported.map( ( label, idx ) => (
									<li key={ idx }>
										<Text size={ 13 }>{ label }</Text>
									</li>
								) ) }
							</ul>
						</Container>
					}
					icon={ <AlertTriangle /> }
				/>
			) }

			{ failed.length > 0 && (
				<Alert
					variant="danger"
					title={ __(
						'Some forms could not be parsed',
						'sureforms'
					) }
					content={
						<Text size={ 13 }>{ failed.join( ', ' ) }</Text>
					}
					icon={ <AlertTriangle /> }
				/>
			) }

			{ previewEntries.length > 0 && (
				<div className="space-y-3">
					{ previewEntries.map( ( [ sourceId, markup ] ) => (
						<details
							key={ sourceId }
							className="rounded-lg border border-border-subtle bg-background-primary"
						>
							<summary className="px-4 py-3 cursor-pointer text-sm font-medium">
								{ sprintf(
									/* translators: %s: source form id. */
									__( 'Source form #%s', 'sureforms' ),
									sourceId
								) }
							</summary>
							<pre className="px-4 py-3 text-xs bg-background-secondary overflow-x-auto whitespace-pre-wrap break-all border-t border-border-subtle m-0">
								{ markup }
							</pre>
						</details>
					) ) }
				</div>
			) }

			<div className="flex items-center justify-between">
				<Button
					variant="outline"
					size="sm"
					onClick={ onBack }
					disabled={ submitting }
					icon={ <ArrowLeft /> }
					iconPosition="left"
				>
					{ __( 'Back', 'sureforms' ) }
				</Button>
				<Button
					variant="primary"
					size="sm"
					onClick={ handleConfirm }
					loading={ submitting }
					disabled={ submitting || previewEntries.length === 0 }
					icon={ <Check /> }
					iconPosition="right"
				>
					{ confirmLabel }
				</Button>
			</div>
		</Container>
	);
};

export default DryRunPreview;
