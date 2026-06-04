/**
 * DryRunPreview — the "review" view of the Migration tab.
 *
 * Runs the importer with `dry_run: true` and renders a human-readable summary
 * of what each selected form will become in SureForms (field count + field
 * list) plus any skipped-field warnings. No raw block markup is shown. From
 * here the user commits the import or goes back to reselect.
 *
 * @since x.x.x
 */

import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import { Alert, Badge, Button, Container, Text } from '@bsf/force-ui';
import { ArrowLeft, AlertTriangle, Check } from 'lucide-react';
import LoadingSkeleton from '@Admin/components/LoadingSkeleton';
import { parseFieldSummary } from './parseFieldSummary';
import { importForms } from './api';

const DryRunPreview = ( {
	source,
	formIds,
	behavior = {},
	selectedForms = [],
	onBack,
	onConfirm,
} ) => {
	const [ data, setData ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );
	const [ submitting, setSubmitting ] = useState( false );
	// Synchronous double-submit guard — blocks a second click before React
	// re-renders with `submitting = true`.
	const inFlight = useRef( false );

	// Depend on serialized values, not the array/object identities — otherwise a
	// parent that recreates `formIds`/`behavior` each render would re-fire the
	// dry-run import on every render.
	const formIdsKey = formIds.join( ',' );
	const behaviorKey = JSON.stringify( behavior );

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
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ source.key, formIdsKey, behaviorKey ] );

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
		return <LoadingSkeleton count={ 4 } height={ 25 } />;
	}

	if ( error ) {
		return (
			<Container direction="column" gap="md">
				<Text size={ 14 } className="text-text-error">
					{ error }
				</Text>
				<div>
					<Button
						variant="outline"
						size="sm"
						onClick={ onBack }
						icon={ <ArrowLeft /> }
						iconPosition="left"
					>
						{ __( 'Go back', 'sureforms' ) }
					</Button>
				</div>
			</Container>
		);
	}

	const previewEntries = data?.preview ? Object.entries( data.preview ) : [];
	const unsupported = Array.isArray( data?.unsupported_fields )
		? data.unsupported_fields
		: [];
	const failed = Array.isArray( data?.failed ) ? data.failed : [];

	const nameById = {};
	selectedForms.forEach( ( f ) => {
		nameById[ String( f.id ) ] = f.name;
	} );

	// CTA label: "Update & import" when updating existing forms (none created fresh).
	const behaviorValues = Object.values( behavior );
	const hasUpdates = behaviorValues.includes( 'update' );
	const hasCreates = behaviorValues.includes( 'create' );
	const confirmLabel =
		hasUpdates && ! hasCreates
			? __( 'Update & import', 'sureforms' )
			: __( 'Confirm & import', 'sureforms' );

	return (
		<Container direction="column" gap="lg">
			{ previewEntries.map( ( [ sourceId, markup ] ) => {
				const summary = parseFieldSummary( markup );
				const name =
					nameById[ sourceId ] ||
					sprintf(
						/* translators: %s: source form id. */
						__( 'Form #%s', 'sureforms' ),
						sourceId
					);
				return (
					<Container
						key={ sourceId }
						direction="column"
						gap="sm"
						className="rounded-lg border border-border-subtle bg-background-primary p-4"
					>
						<Container align="center" justify="between" gap="xs">
							<Text size={ 14 } weight={ 600 }>
								{ name }
							</Text>
							<Text size={ 13 } color="secondary">
								{ sprintf(
									/* translators: %d: number of fields. */
									_n(
										'%d field will import',
										'%d fields will import',
										summary.count,
										'sureforms'
									),
									summary.count
								) }
							</Text>
						</Container>
						{ summary.fields.length > 0 && (
							<Container wrap="wrap" gap="xs">
								{ summary.fields.map( ( field, idx ) => (
									<Badge
										key={ idx }
										variant="neutral"
										size="xs"
										label={ field.label }
									/>
								) ) }
							</Container>
						) }
					</Container>
				);
			} ) }

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
									'These fields will be skipped - add them manually after the form is created:',
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
					title={ __( 'Some forms could not be parsed', 'sureforms' ) }
					content={ <Text size={ 13 }>{ failed.join( ', ' ) }</Text> }
					icon={ <AlertTriangle /> }
				/>
			) }

			<Container align="center" justify="between">
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
			</Container>
		</Container>
	);
};

export default DryRunPreview;
