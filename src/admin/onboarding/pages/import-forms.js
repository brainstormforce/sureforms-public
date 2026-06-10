/**
 * ImportForms — the conditional onboarding step that detects existing form
 * plugins (CF7, WPForms, Gravity Forms, Ninja Forms) and offers to import
 * them into SureForms in one click.
 *
 * The step is registered between `/onboarding/user-details` and
 * `/onboarding/done` and is **hidden silently** when:
 *  - the migration sources REST call fails, OR
 *  - no source plugin is installed, OR
 *  - all installed sources have zero forms.
 *
 * For users who skip or never see this step, the Forms-listing banner
 * (`src/admin/forms/components/MigrationBanner.js`) is the second touch-
 * point; the Settings → Migration tab remains the always-on surface.
 *
 * @since x.x.x
 */

import { Container, Text, Title, Loader, Alert } from '@bsf/force-ui';
import { __, _n, sprintf } from '@wordpress/i18n';
import { useState, useMemo, useEffect } from '@wordpress/element';
import { CheckCircle2 } from 'lucide-react';
import { Divider } from '../components';
import NavigationButtons from '../components/navigation-buttons';
import SourceMigrationCard from '../components/source-migration-card';
import { useOnboardingState } from '../onboarding-state';
import { useOnboardingNavigation } from '../hooks';
import { importForms } from '@Admin/settings/migration/api';

const ImportForms = () => {
	const [ onboardingState, actions ] = useOnboardingState();
	const { navigateToPreviousRoute, navigateToNextRoute } =
		useOnboardingNavigation();

	// Sources detected on onboarding boot (see onboarding-layout.js useEffect).
	// `usableSources` keeps every installed source so users see fully-imported
	// ones too — the card renders them as disabled with an "All forms
	// already imported" caption. The step itself only renders when at least
	// one source has `pending_count > 0` (see `hasPendingSource` below).
	const usableSources = useMemo(
		() =>
			( onboardingState?.migrationSources || [] ).filter(
				( s ) =>
					s?.installed &&
					( Number( s?.form_count ?? 0 ) > 0 ||
						Number( s?.imported_count ?? 0 ) > 0 )
			),
		[ onboardingState?.migrationSources ]
	);

	// Sources with at least one form still pending import — the actionable
	// subset for pre-selection + CTA arithmetic.
	const pendingSources = useMemo(
		() =>
			usableSources.filter(
				( s ) =>
					Number(
						s?.pending_count ??
							Number( s?.form_count ?? 0 ) -
								Number( s?.imported_count ?? 0 )
					) > 0
			),
		[ usableSources ]
	);

	// Pre-select the source with the most PENDING forms so the CTA is never
	// "Import 0 forms" and the user has a one-click happy path.
	const defaultSourceKey = useMemo( () => {
		if ( pendingSources.length === 0 ) {
			return null;
		}
		const sorted = [ ...pendingSources ].sort(
			( a, b ) =>
				Number( b?.pending_count ?? 0 ) -
				Number( a?.pending_count ?? 0 )
		);
		return sorted[ 0 ]?.key ?? null;
	}, [ pendingSources ] );

	const [ selectedKey, setSelectedKey ] = useState( defaultSourceKey );
	const [ status, setStatus ] = useState( 'idle' ); // idle | importing | success | error
	const [ result, setResult ] = useState( null );
	const [ errorMsg, setErrorMsg ] = useState( '' );

	// Keep the selection in sync if the upstream list refreshes mid-flow.
	// Reset to the default when nothing is selected OR when the current
	// selection is no longer among the pending sources (e.g. it became fully
	// imported on a refresh) — otherwise the CTA can read "Import 0 forms"
	// while still enabled (review #5).
	useEffect( () => {
		const selectionPending = pendingSources.some(
			( s ) => s?.key === selectedKey
		);
		if ( ! selectionPending && defaultSourceKey ) {
			setSelectedKey( defaultSourceKey );
		}
	}, [ defaultSourceKey, selectedKey, pendingSources ] );

	const selectedSource = useMemo(
		() => usableSources.find( ( s ) => s?.key === selectedKey ) ?? null,
		[ usableSources, selectedKey ]
	);

	// CTA arithmetic uses `pending_count` — already-imported forms are
	// skipped at the API level (skip_existing=true) so we never quote a
	// number we won't actually attempt to import.
	const selectedFormCount = Number(
		selectedSource?.pending_count ??
			Number( selectedSource?.form_count ?? 0 ) -
				Number( selectedSource?.imported_count ?? 0 )
	);

	const ctaLabel = useMemo( () => {
		if ( ! selectedSource ) {
			return __( 'Import forms', 'sureforms' );
		}
		return sprintf(
			/* translators: %d: number of forms that will be imported. */
			_n(
				'Import %d form',
				'Import %d forms',
				selectedFormCount,
				'sureforms'
			),
			selectedFormCount
		);
	}, [ selectedSource, selectedFormCount ] );

	const handleImport = async () => {
		if ( ! selectedSource || status === 'importing' ) {
			return;
		}
		setStatus( 'importing' );
		setErrorMsg( '' );
		try {
			// `form_ids: []` is the migrator's "import all from this source"
			// signal — Base_Migrator iterates every form when the array is
			// empty. `skip_existing: true` guarantees we never silently
			// overwrite a form the user has previously imported + edited,
			// even if they re-run the onboarding wizard.
			// Args: key, formIds(all), dryRun, behavior, postStatus, skipExisting.
			// Onboarding imports land as drafts and never overwrite an existing
			// import (skip_existing=true) — both must be passed in their slots.
			const res = await importForms(
				selectedSource.key,
				[],
				false,
				{},
				'draft',
				true
			);
			setResult( res );
			setStatus( 'success' );
			actions.setMigrationResult( selectedSource.key, {
				imported: ( res?.imported || [] ).length,
				failed: ( res?.failed || [] ).length,
				unsupported: ( res?.unsupported_fields || [] ).length,
			} );
		} catch ( err ) {
			setErrorMsg(
				err?.message ||
					__(
						'Something went wrong while importing. You can retry, skip, or open Settings → Migration.',
						'sureforms'
					)
			);
			setStatus( 'error' );
		}
	};

	const handleSkip = () => {
		actions.markStepSkipped( 'importForms' );
		navigateToNextRoute();
	};

	const handleContinue = () => {
		navigateToNextRoute();
	};

	const handleRetry = () => {
		setStatus( 'idle' );
		setErrorMsg( '' );
	};

	// ---------- render ----------

	// While the onboarding boot probe is still detecting source plugins, show
	// a loader instead of the "no plugins detected" fallback below — otherwise
	// a slow REST round-trip flashes a false dead-end to exactly the switchers
	// this step targets (review #1).
	if ( ! onboardingState?.migrationDetectionLoaded ) {
		return (
			<div className="space-y-6">
				<Container justify="center" className="srfm-py-10">
					<Loader variant="secondary" />
				</Container>
			</div>
		);
	}

	// Defensive fallback — the visibility filter should normally prevent
	// the step from rendering at all when there's nothing pending. If we
	// somehow land here without any source still having work to do, skip
	// cleanly to the next step rather than render a dead-end UI.
	if ( pendingSources.length === 0 ) {
		return (
			<div className="space-y-6">
				<Container gap="sm" align="center">
					<Text size={ 14 } color="secondary">
						{ __(
							'No other form plugins detected. You can import any time from Settings → Migration.',
							'sureforms'
						) }
					</Text>
				</Container>
				<Divider />
				<NavigationButtons
					backProps={ { onClick: navigateToPreviousRoute } }
					continueProps={ {
						onClick: handleContinue,
						text: __( 'Continue', 'sureforms' ),
					} }
				/>
			</div>
		);
	}

	if ( status === 'success' ) {
		const imported = ( result?.imported || [] ).length;
		const failed = ( result?.failed || [] ).length;
		const unsupported = ( result?.unsupported_fields || [] ).length;
		return (
			<div className="space-y-6">
				<Container gap="sm" align="center" className="h-auto">
					<div className="space-y-2">
						<Title
							tag="h3"
							title={ __( 'Forms imported', 'sureforms' ) }
							size="lg"
						/>
						<Text size={ 14 } weight={ 400 } color="secondary">
							{ sprintf(
								/* translators: 1: imported count, 2: source plugin title. */
								_n(
									'%1$d form from %2$s is now in SureForms, ready to publish, style, and connect.',
									'%1$d forms from %2$s are now in SureForms, ready to publish, style, and connect.',
									imported,
									'sureforms'
								),
								imported,
								selectedSource?.title || ''
							) }
						</Text>
					</div>
				</Container>
				<div className="space-y-2">
					<Container className="flex items-center gap-1.5">
						<CheckCircle2 className="size-4 text-icon-interactive" />
						<Text size={ 14 } weight={ 500 } color="label">
							{ sprintf(
								/* translators: %d: imported form count. */
								_n(
									'%d form imported',
									'%d forms imported',
									imported,
									'sureforms'
								),
								imported
							) }
						</Text>
					</Container>
					{ failed > 0 && (
						<Container className="flex items-center gap-1.5">
							<Text size={ 14 } weight={ 500 } color="secondary">
								{ sprintf(
									/* translators: %d: failed form count. */
									_n(
										'%d form could not be imported',
										'%d forms could not be imported',
										failed,
										'sureforms'
									),
									failed
								) }
							</Text>
						</Container>
					) }
					{ unsupported > 0 && (
						<Container className="flex items-center gap-1.5">
							<Text size={ 14 } weight={ 500 } color="secondary">
								{ sprintf(
									/* translators: %d: count of unsupported fields. */
									_n(
										'%d field type was unsupported. You can rebuild it manually inside SureForms.',
										'%d field types were unsupported. You can rebuild them manually inside SureForms.',
										unsupported,
										'sureforms'
									),
									unsupported
								) }
							</Text>
						</Container>
					) }
				</div>
				<Divider />
				<NavigationButtons
					continueProps={ {
						onClick: handleContinue,
						text: __( 'Continue', 'sureforms' ),
					} }
				/>
			</div>
		);
	}

	return (
		<div className="space-y-6">
			<Container gap="sm" align="center" className="h-auto">
				<div className="space-y-2">
					<Title
						tag="h3"
						title={ __(
							'Bring your existing forms with you',
							'sureforms'
						) }
						size="lg"
					/>
					<Text size={ 14 } weight={ 400 } color="secondary">
						{ __(
							'We detected forms in another plugin. Pick one to import into SureForms.',
							'sureforms'
						) }
					</Text>
				</div>
			</Container>

			<div
				role="radiogroup"
				aria-label={ __(
					'Choose a form plugin to import from',
					'sureforms'
				) }
				className="space-y-2"
			>
				{ usableSources.map( ( source ) => (
					<SourceMigrationCard
						key={ source.key }
						source={ source }
						selected={ source.key === selectedKey }
						onSelect={ ( s ) => setSelectedKey( s.key ) }
						disabled={ status === 'importing' }
					/>
				) ) }
			</div>

			<Text size={ 13 } color="secondary">
				{ __(
					'Importing more than one plugin? You can import additional sources later from Settings → Migration.',
					'sureforms'
				) }
			</Text>

			{ status === 'error' && (
				<Alert
					variant="warning"
					title={ __(
						'Import did not complete',
						'sureforms'
					) }
					content={ errorMsg }
				/>
			) }

			<Divider />

			<NavigationButtons
				backProps={ { onClick: navigateToPreviousRoute } }
				skipProps={ {
					onClick: handleSkip,
					text: __(
						"I'll do this later",
						'sureforms'
					),
				} }
				continueProps={ {
					onClick: status === 'error' ? handleRetry : handleImport,
					text:
						status === 'importing'
							? __( 'Importing…', 'sureforms' )
							: status === 'error'
								? __( 'Retry', 'sureforms' )
								: ctaLabel,
					disabled: ! selectedSource || status === 'importing',
					icon:
						status === 'importing' ? (
							<Loader variant="secondary" />
						) : undefined,
				} }
			/>
		</div>
	);
};

export default ImportForms;
