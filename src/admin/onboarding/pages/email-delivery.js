import { __, sprintf } from '@wordpress/i18n';
import {
	useState,
	useEffect,
	useRef,
} from '@wordpress/element';
import { Text } from '@bsf/force-ui';
import { CheckCircle2, ExternalLink } from 'lucide-react';
import apiFetch from '@wordpress/api-fetch';
import { useOnboardingNavigation } from '../hooks';
import { useOnboardingState } from '../onboarding-state';
import { installAndActivatePlugin } from '@Utils/Helpers';
import { Divider, Header, HERO_PANEL_CLASS } from '../components';
import NavigationButtons from '../components/navigation-buttons';
import {
	getView,
	getDoneAction,
	getProvisionFailure,
	isSiteRefused,
	isLikelyLocalHost,
	getDefaultOption,
} from './email-delivery-view';
import { EmailOptions, ConsentCheckbox } from './email-delivery-options';
// Rendered via <object>, not <img> or an inlined component: the artwork
// animates with CSS keyframes, which svgr/svgo strips when inlining, and
// <object> gives the SVG its own document where they run untouched.
import emailDeliveryIllustration from '@Image/onboarding/email-delivery.svg';

/**
 * Confirmation after the one-click sign-up, so the owner sees it worked
 * without leaving the step.
 *
 * @return {JSX.Element} Connected note.
 */
const ConnectedNote = () => (
	<div className="flex items-center gap-2">
		<CheckCircle2 className="size-5 shrink-0 text-support-success" />
		<Text size={ 14 } weight={ 500 } color="primary">
			{ __(
				'SureMail is installed and connected to SureContact.',
				'sureforms'
			) }
		</Text>
	</div>
);

/**
 * Whether this site's address looks local or private.
 *
 * @return {boolean} Looks local or private.
 */
const siteLooksLocal = () => {
	try {
		return isLikelyLocalHost(
			new URL( srfm_admin?.site_url || window.location.href ).hostname
		);
	} catch ( e ) {
		return false;
	}
};

// SureMail's REST routes. SureMail checks manage_options and the wp_rest
// nonce, which apiFetch already sends from this screen.
const SUREMAIL_PROVISION_PATH = '/suremails/v1/surecontact/provision';

const fetchStatus = () =>
	apiFetch( {
		path: '/sureforms/v1/email-delivery/status',
		method: 'GET',
	} );

const EmailDelivery = () => {
	const [ , actions ] = useOnboardingState();
	const { navigateToNextRoute, navigateToPreviousRoute } =
		useOnboardingNavigation();

	// SureMail's installer entry, used by installAndActivatePlugin().
	const sureMailsPlugin = srfm_admin?.integrations?.sure_mails ?? null;

	// Check for access_key parameter on mount
	useEffect( () => {
		// Parse the URL to check for access_key parameter
		const urlParams = new URLSearchParams( window.location.search );
		const accessKey = urlParams.get( 'access_key' );

		if ( accessKey ) {
			// Handle access key by sending it to the server
			const handleAccessKey = async () => {
				try {
					// apiFetch adds the REST nonce itself; template_picker_nonce
					// is not localized on this screen.
					const response = await apiFetch( {
						path: '/sureforms/v1/handle-access-key',
						method: 'POST',
						data: { accessKey },
					} );

					if ( response?.success ) {
						// Update local state to indicate account was connected during onboarding
						actions.setAccountConnected( true );

						// Clean up URL parameter to avoid duplicate tracking on page refresh
						const url = new URL( window.location.href );

						// Only remove the access_key parameter
						url.searchParams.delete( 'access_key' );

						// Build the cleaned-up URL with remaining query params and hash
						const newUrl = `${
							url.pathname
						}?${ url.searchParams.toString() }${ url.hash }`;
						window.history.replaceState(
							{},
							document.title,
							newUrl
						);
					} else {
						console.error(
							'Error handling access key:',
							response?.message
						);
					}
				} catch ( error ) {
					console.error( 'Error handling access key:', error );
				}
			};

			handleAccessKey();
		}
	}, [] );

	// Localised with the page so the step renders the right screen at once;
	// re-read on mount and whenever the owner comes back from SureMail's tab.
	const [ status, setStatus ] = useState(
		srfm_admin?.email_delivery ?? null
	);
	// 'installing' | 'activating' | 'provisioning' while the button is
	// working, '' otherwise.
	const [ progress, setProgress ] = useState( '' );
	const [ error, setError ] = useState( '' );
	// Set once SureMail was opened in a new tab, until the step is left.
	const [ openedSureMail, setOpenedSureMail ] = useState( false );
	// Required before a SureContact account is created; the other option
	// creates nothing, so it needs no consent.
	const [ consented, setConsented ] = useState( false );
	// Whether one-click sign-up is likely to be refused here, read once.
	const [ localSite ] = useState( siteLooksLocal );
	// 'surecontact' | 'other_smtp': the open option card.
	const [ option, setOption ] = useState( () =>
		getDefaultOption( srfm_admin?.email_delivery ?? null, localSite )
	);
	const userEmail = srfm_admin?.website_lead_details?.email ?? '';
	const userName = [
		srfm_admin?.website_lead_details?.first_name,
		srfm_admin?.website_lead_details?.last_name,
	]
		.filter( Boolean )
		.join( ' ' );
	// Outcome of the one-click SureContact sign-up: '' until tried, then
	// 'created' | 'already_registered' | 'unavailable' | 'failed'.
	const [ provision, setProvision ] = useState( '' );
	// State the owner arrived with, for analytics. Captured once.
	const stateAtStep = useRef( srfm_admin?.email_delivery?.state ?? '' );

	// One request at a time: coming back from SureMail fires both focus and
	// visibilitychange, which would otherwise fetch the status twice.
	const inFlight = useRef( null );
	const refreshStatus = () => {
		if ( ! inFlight.current ) {
			inFlight.current = fetchStatus()
				.then( ( next ) => {
					if ( next?.state ) {
						setStatus( next );
					}
					return next;
				} )
				.catch( () => null )
				.finally( () => {
					inFlight.current = null;
				} );
		}
		return inFlight.current;
	};

	useEffect( () => {
		refreshStatus().then( ( next ) => {
			if ( ! stateAtStep.current && next?.state ) {
				stateAtStep.current = next.state;
			}
		} );
		actions.setEmailDeliveryAnalytics( {
			stateAtStep: stateAtStep.current,
		} );
	}, [] );

	// SureMail's connection flow runs in its own tab. When the owner comes back,
	// read the status again so a finished connection shows up without a reload.
	useEffect( () => {
		if ( ! openedSureMail ) {
			return;
		}
		const onReturn = () => {
			if ( 'visible' === document.visibilityState ) {
				refreshStatus();
			}
		};
		window.addEventListener( 'focus', onReturn );
		document.addEventListener( 'visibilitychange', onReturn );
		return () => {
			window.removeEventListener( 'focus', onReturn );
			document.removeEventListener( 'visibilitychange', onReturn );
		};
	}, [ openedSureMail ] );

	const view = getView( status );
	const busy = '' !== progress;

	const leave = ( action, { skipped = false } = {} ) => {
		if ( skipped ) {
			actions.markStepSkipped( 'emailDelivery' );
		} else {
			actions.unmarkStepSkipped( 'emailDelivery' );
		}
		actions.setEmailDeliveryAnalytics( {
			stateAtStep: stateAtStep.current,
			finalState: status?.state ?? '',
			action,
			suremailOpened: openedSureMail,
			consented,
			provision,
			option,
		} );
		navigateToNextRoute();
	};

	// Creates the SureContact account through SureMail, which stores the
	// returned key as its connection. Only after the consent box is ticked.
	const provisionSureContact = async () => {
		// Every caller is gated on the box today; this keeps it so.
		if ( ! consented ) {
			return;
		}
		setError( '' );
		setProgress( 'provisioning' );
		try {
			await apiFetch( {
				path: SUREMAIL_PROVISION_PATH,
				method: 'POST',
				data: { email: userEmail, name: userName },
			} );
			setProvision( 'created' );
		} catch ( provisionError ) {
			const outcome = getProvisionFailure( provisionError );
			setProvision( outcome );
			// SureContact calls the site back to confirm it owns it, so local,
			// private or firewalled sites are refused. SureMail can still connect
			// another provider there.
			if ( isSiteRefused( provisionError ) ) {
				// Open the option that still works here.
				setOption( 'other_smtp' );
				setError(
					__(
						'SureContact could not reach this site to confirm it is yours. This happens on local, private or firewalled sites. Connect another email provider in SureMail instead.',
						'sureforms'
					)
				);
			} else if ( 'failed' === outcome ) {
				setError(
					provisionError?.message ||
						__(
							'Your SureContact account could not be created. Please try again, or connect it in SureMail.',
							'sureforms'
						)
				);
			}
		} finally {
			setProgress( '' );
		}
		await refreshStatus();
	};

	// SureMail is active with no connection at all: never set up, or a lost
	// connection (reported as 'attention'). Any connection, even a broken
	// one, is left for the owner to fix in SureMail.
	const canProvision = ( currentStatus ) =>
		Boolean( userEmail ) &&
		Boolean( currentStatus?.suremail?.supports_surecontact ) &&
		0 === Number( currentStatus?.suremail?.connections ?? -1 ) &&
		'open_suremail' === currentStatus?.action?.type;

	const handleInstall = async () => {
		if ( ! sureMailsPlugin ) {
			setError(
				__(
					'SureMail could not be found. Please skip this step and install it from Plugins → Add New.',
					'sureforms'
				)
			);
			return;
		}

		setError( '' );

		// Awaited so a failure shows here, not on a step the owner already left.
		// NavigationButtons shows its spinner while this promise is pending.
		try {
			await installAndActivatePlugin( sureMailsPlugin, setProgress );
			actions.setSuremailInstalled( true );
			// SureMail skips its own welcome redirect for an AJAX activation, so
			// the next screen here is what leads the owner into its setup.
			const next = await refreshStatus();
			// Another provider: SureMail is in place, and connecting it is left
			// for later rather than sending the owner out of the setup. The
			// dashboard keeps reminding them until it is connected.
			if ( 'other_smtp' === option ) {
				leave( 'install_other_smtp' );
				return;
			}
			// The consent box covered the account, so carry on in one click.
			if ( canProvision( next ) ) {
				await provisionSureContact();
			}
		} catch ( installError ) {
			setError(
				installError?.message ||
					__(
						'SureMail could not be installed. Please try again, or skip this step.',
						'sureforms'
					)
			);
			// The install may have landed and only the activation failed.
			refreshStatus();
		} finally {
			setProgress( '' );
		}
	};

	// window.open() straight from the click, so popup blockers allow it.
	const openSureMail = () => {
		const url = status?.action?.url || status?.links?.setup;
		if ( ! url ) {
			return;
		}
		window.open( url, '_blank', 'noopener' );
		setOpenedSureMail( true );
	};

	const progressLabel = () => {
		if ( 'installing' === progress ) {
			return __( 'Installing SureMail…', 'sureforms' );
		}
		if ( 'activating' === progress ) {
			return __( 'Activating SureMail…', 'sureforms' );
		}
		if ( 'provisioning' === progress ) {
			return __( 'Creating your SureContact account…', 'sureforms' );
		}
		return '';
	};

	// The button says what it will do for the open option, so "SureContact"
	// on the card never meets a button that only mentions SureMail.
	const installLabel = () => {
		if ( busy ) {
			return progressLabel();
		}
		const activates = 'activate' === status?.action?.type;
		if ( 'other_smtp' === option ) {
			return activates
				? __( 'Activate SureMail', 'sureforms' )
				: __( 'Install SureMail', 'sureforms' );
		}
		return activates
			? __( 'Activate SureMail & Connect SureContact', 'sureforms' )
			: __( 'Install SureMail & Connect SureContact', 'sureforms' );
	};

	// Consent is about the SureContact account, so it shows only with that
	// option open, and only when an account can be created by email.
	const consent = ( installs ) =>
		'surecontact' === option && userEmail ? (
			<ConsentCheckbox
				checked={ consented }
				onChange={ setConsented }
				installs={ installs }
				email={ userEmail }
			/>
		) : null;

	const options = (
		<EmailOptions
			option={ option }
			onSelect={ ( next ) => {
				setOption( next );
				setError( '' );
			} }
			localSite={ localSite }
		/>
	);

	let title;
	let description;
	let body = null;
	let continueProps;
	let skipProps;
	// The option cards replace the illustration: together they push the
	// buttons below the fold on a laptop screen.
	let showsOptions = false;

	// A sign-up that succeeded is connected, even if reading the status back
	// failed: offering "Create account" again would only meet SureMail's
	// "already connected" error.
	const shownView = 'created' === provision && 'connect' === view ? 'limited' : view;

	switch ( shownView ) {
		case 'install':
			title = __( 'Make Sure Your Emails Get Delivered', 'sureforms' );
			description = __(
				'Most hosts block the mail function WordPress uses by default. SureMail installs now and sends through a real service instead.',
				'sureforms'
			);
			if ( false === status?.can_install ) {
				body = (
					<Text size={ 14 } color="secondary">
						{ __(
							'Your account cannot install plugins. Ask a site administrator to set up email delivery.',
							'sureforms'
						) }
					</Text>
				);
				continueProps = {
					onClick: () => leave( 'cannot_install', { skipped: true } ),
					text: __( 'Continue', 'sureforms' ),
				};
			} else {
				showsOptions = true;
				body = (
					<>
						{ options }
						{ consent( true ) }
					</>
				);
				continueProps = {
					onClick: handleInstall,
					text: installLabel(),
					// No address to create the account for: SureMail's own
					// screens can still connect SureContact.
					disabled:
						'surecontact' === option && Boolean( userEmail ) && ! consented,
				};
			}
			skipProps = {
				onClick: () => leave( 'skip', { skipped: true } ),
			};
			break;

		case 'connect':
			if ( 'already_registered' === provision ) {
				// An existing account is never connected by email alone. SureMail
				// only completes a SureContact sign-in that its own screens
				// started, so the owner signs in from there.
				title = __(
					'You Already Have a SureContact Account',
					'sureforms'
				);
				description = openedSureMail
					? __(
						'In the SureMail tab, choose SureContact, then "Already have a SureContact account? Connect it instead" and sign in. Come back here when done; this step updates by itself.',
						'sureforms'
					  )
					: sprintf(
						/* translators: %s: the current user's email address. */
						__(
							'%s already has a SureContact account. In SureMail, choose SureContact, then "Already have a SureContact account? Connect it instead" to sign in.',
							'sureforms'
						),
						userEmail
					  );
				continueProps = {
					onClick: openSureMail,
					text: __( 'Open SureMail', 'sureforms' ),
					icon: <ExternalLink />,
				};
				skipProps = {
					onClick: () => leave( 'skip', { skipped: true } ),
					text: __( 'Skip for now', 'sureforms' ),
				};
				break;
			}

			title = __( 'Make Sure Your Emails Get Delivered', 'sureforms' );
			description = openedSureMail
				? __(
					'Finish connecting in the SureMail tab, then come back here. This step updates by itself.',
					'sureforms'
				  )
				: __(
					'SureMail is installed but not connected to an email service yet, so your form emails are not sent through it. Connect SureContact in one click, or a provider you already use.',
					'sureforms'
				  );
			showsOptions = true;
			body = (
				<>
					{ options }
					{ consent( false ) }
				</>
			);
			if ( 'other_smtp' === option ) {
				continueProps = {
					onClick: openSureMail,
					text: __( 'Set Up in SureMail', 'sureforms' ),
					icon: <ExternalLink />,
				};
			} else if ( canProvision( status ) && 'unavailable' !== provision ) {
				continueProps = {
					onClick: provisionSureContact,
					disabled: ! consented,
					text: busy
						? progressLabel()
						: __( 'Connect SureContact', 'sureforms' ),
				};
			} else {
				// SureMail too old for the one-click sign-up, or no address to
				// create the account for: its own screens connect SureContact.
				continueProps = {
					onClick: openSureMail,
					text: __( 'Connect in SureMail', 'sureforms' ),
					icon: <ExternalLink />,
				};
			}
			skipProps = {
				onClick: () => leave( 'skip', { skipped: true } ),
				text: __( 'Skip for now', 'sureforms' ),
			};
			break;

		case 'limited':
			if ( 'created' === provision ) {
				title = __( 'SureContact SMTP Connected', 'sureforms' );
				description = sprintf(
					/* translators: %s: the current user's email address. */
					__(
						'Your form emails now go out through SureContact. Check %s for a verification email: until you verify it, only a few emails a day are accepted.',
						'sureforms'
					),
					userEmail
				);
				body = <ConnectedNote />;
			} else {
				title = __( 'Verify Your SureContact Account', 'sureforms' );
				description =
					status?.message ||
					__(
						'SureContact is connected, but only a few emails a day are accepted until you verify your account email.',
						'sureforms'
					);
			}
			continueProps = {
				onClick: () => leave( 'continue_limited' ),
				text: __( 'Continue', 'sureforms' ),
			};
			skipProps = {
				onClick: openSureMail,
				text: __( 'Finish in SureMail', 'sureforms' ),
				icon: <ExternalLink />,
			};
			break;

		case 'attention':
			// A notification failed and the fix is not to set up or connect
			// SureMail: say so, and offer that fix (logs, or verifying SureContact).
			title = status?.title || __( 'Email Delivery', 'sureforms' );
			description = status?.message || '';
			continueProps = {
				onClick: () => leave( 'continue_attention' ),
				text: __( 'Continue', 'sureforms' ),
			};
			skipProps = status?.action?.url
				? {
					onClick: openSureMail,
					text: status.action.label,
					icon: <ExternalLink />,
				  }
				: {};
			break;

		default:
			title = status?.title || __( 'Email Delivery', 'sureforms' );
			description = status?.message || '';
			if ( 'created' === provision ) {
				body = <ConnectedNote />;
			}
			continueProps = {
				onClick: () => leave( getDoneAction( status ) ),
				text: __( 'Continue', 'sureforms' ),
			};
			skipProps = {};
	}

	return (
		<div className="space-y-4">
			{ ! showsOptions && (
				<div className={ HERO_PANEL_CLASS }>
					<object
						type="image/svg+xml"
						data={ emailDeliveryIllustration }
						className="pointer-events-none block h-auto w-full"
						aria-label={ __(
							'Illustration of a form submission travelling to an inbox',
							'sureforms'
						) }
					/>
				</div>
			) }

			<Header title={ title } description={ description } />
			{ /* The step changes in place (installing, connected); announce it. */ }
			<div className="sr-only" aria-live="polite">
				{ title }
			</div>

			{ body }

			{ error && (
				<Text size={ 14 } color="error" role="alert">
					{ error }
				</Text>
			) }

			<Divider />

			<NavigationButtons
				backProps={ {
					onClick: navigateToPreviousRoute,
					disabled: busy,
				} }
				continueProps={ continueProps }
				// Sign-up can wait up to 30s on SureContact; leaving meanwhile would
				// record a skip while the account is still being created.
				skipProps={ busy ? { ...skipProps, disabled: true } : skipProps }
			/>
		</div>
	);
};

export default EmailDelivery;
