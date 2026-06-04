/**
 * MigrationBanner — dismissible callout on the Forms listing page that
 * surfaces detected source plugins (CF7 / WPForms / Gravity / Ninja) when
 * the migrator finds at least one with importable forms.
 *
 * Visibility rules:
 *   1. At least one detected source has installed=true AND form_count > 0.
 *   2. User meta `srfm_onboarding_migration_banner_dismissed` is falsy.
 *   3. Onboarding completed (we don't double-surface to mid-flow users).
 *
 * Click → Settings → Migration tab (existing landing). Dismiss → POST
 * user-meta endpoint; banner stays dismissed across reloads.
 *
 * @since x.x.x
 */

import { __, sprintf, _n } from '@wordpress/i18n';
import { useEffect, useState, useMemo, useCallback } from '@wordpress/element';
import { Button, Container, Text } from '@bsf/force-ui';
import { ArrowRight, RefreshCcw, X as XIcon } from 'lucide-react';
import { listSources, dismissMigrationBanner } from '@Admin/settings/migration/api';

const STORAGE_KEY = 'srfm_migration_banner_dismissed';

const MigrationBanner = () => {
	const [ sources, setSources ] = useState( [] );
	const [ loaded, setLoaded ] = useState( false );
	const [ dismissed, setDismissed ] = useState( () => {
		// Initial check from the server-localized payload; falls back to
		// localStorage so the banner stays gone between reloads even if
		// the REST round-trip is slow.
		if ( window?.srfm_admin?.migration_banner_dismissed ) {
			return true;
		}
		try {
			return localStorage.getItem( STORAGE_KEY ) === '1';
		} catch ( e ) {
			return false;
		}
	} );

	// Only run for users who have completed onboarding. Mid-onboarding the
	// dedicated /onboarding/import-forms step already covers them.
	const onboardingCompleted = Boolean( window?.srfm_admin?.onboarding_completed );

	useEffect( () => {
		if ( dismissed || ! onboardingCompleted ) {
			return undefined;
		}
		let cancelled = false;
		listSources()
			.then( ( res ) => {
				if ( cancelled ) {
					return;
				}
				setSources(
					Array.isArray( res?.sources ) ? res.sources : []
				);
				setLoaded( true );
			} )
			.catch( () => {
				if ( cancelled ) {
					return;
				}
				setSources( [] );
				setLoaded( true );
			} );
		return () => {
			cancelled = true;
		};
	}, [ dismissed, onboardingCompleted ] );

	// Pick the source with the most PENDING forms — that's the strongest
	// pitch. Sources where everything's already imported are filtered out
	// so the banner never appears for a site that has nothing left to
	// migrate from this plugin.
	const primary = useMemo( () => {
		const pendingOf = ( s ) =>
			Number(
				s?.pending_count ??
					Number( s?.form_count ?? 0 ) -
						Number( s?.imported_count ?? 0 )
			);
		const usable = sources.filter(
			( s ) => s?.installed && pendingOf( s ) > 0
		);
		if ( usable.length === 0 ) {
			return null;
		}
		return [ ...usable ].sort(
			( a, b ) => pendingOf( b ) - pendingOf( a )
		)[ 0 ];
	}, [ sources ] );

	const handleDismiss = useCallback( () => {
		setDismissed( true );
		try {
			localStorage.setItem( STORAGE_KEY, '1' );
		} catch ( e ) {
			// localStorage may be unavailable (private mode, quota); the
			// REST persistence below is the source of truth anyway.
		}
		dismissMigrationBanner().catch( () => {
			// Swallow — the banner is already hidden client-side. Worst case
			// it returns on the next reload, which is an acceptable failure
			// mode given the value of the call is purely UX.
		} );
	}, [] );

	const handleImport = useCallback( () => {
		// Use the server-localized admin_url (correct scheme + subdirectory
		// path); fall back to a relative admin path which the browser resolves
		// against the current wp-admin URL — never window.location.origin,
		// which drops the subdirectory on subdir installs (review #4).
		const url =
			window?.srfm_admin?.migration_settings_url ||
			'admin.php?page=sureforms_form_settings&tab=migration-settings';
		window.location.href = url;
	}, [] );

	if ( dismissed || ! onboardingCompleted || ! loaded || ! primary ) {
		return null;
	}

	// Banner quotes the pending number, not the raw form total — the user
	// only cares about what's left to migrate.
	const formCount = Number(
		primary?.pending_count ??
			Number( primary?.form_count ?? 0 ) -
				Number( primary?.imported_count ?? 0 )
	);
	const formsLabel = sprintf(
		/* translators: %d: number of forms still pending import from the source plugin. */
		_n( '%d form to import', '%d forms to import', formCount, 'sureforms' ),
		formCount
	);

	return (
		<Container
			align="center"
			justify="between"
			className="rounded-lg border-0.5 border-solid border-border-subtle bg-background-primary px-4 py-3 shadow-sm"
		>
			<Container align="center" gap="md" className="min-w-0">
				<div className="flex items-center justify-center rounded-md bg-background-interactive-subtle p-2">
					<RefreshCcw className="size-4 text-icon-interactive" />
				</div>
				<div className="min-w-0">
					<Text size={ 14 } weight={ 600 } color="primary">
						{ sprintf(
							/* translators: %s: source plugin title (e.g. "Contact Form 7"). */
							__(
								'Bring your forms from %s into SureForms',
								'sureforms'
							),
							primary?.title || ''
						) }
					</Text>
					<Text size={ 13 } color="secondary">
						{ formsLabel }
						{ ' · ' }
						{ __(
							'Fields, settings, and conditional logic come over automatically.',
							'sureforms'
						) }
					</Text>
				</div>
			</Container>
			<Container align="center" gap="sm">
				<Button
					variant="primary"
					size="sm"
					onClick={ handleImport }
					icon={ <ArrowRight /> }
					iconPosition="right"
				>
					{ __( 'Import', 'sureforms' ) }
				</Button>
				<Button
					variant="ghost"
					size="sm"
					onClick={ handleDismiss }
					icon={ <XIcon /> }
					aria-label={ __(
						'Dismiss migration banner',
						'sureforms'
					) }
				/>
			</Container>
		</Container>
	);
};

export default MigrationBanner;
