import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { Text } from '@bsf/force-ui';
import { ExternalLink } from 'lucide-react';
import apiFetch from '@wordpress/api-fetch';
import { useOnboardingNavigation } from '../hooks';
import { useOnboardingState } from '../onboarding-state';
import { installAndActivatePlugin } from '@Utils/Helpers';
import { Divider, Header, FeatureList, HERO_PANEL_CLASS } from '../components';
import NavigationButtons from '../components/navigation-buttons';
import { getView, getDoneAction } from './email-delivery-view';
// Rendered via <object>, not <img> or an inlined component: the artwork
// animates with CSS keyframes, which svgr/svgo strips when inlining, and
// <object> gives the SVG its own document where they run untouched.
import emailDeliveryIllustration from '@Image/onboarding/email-delivery.svg';

const features = [
	__(
		'Sends form emails through a real email service, not your server',
		'sureforms'
	),
	__(
		'Free SureContact sending, or connect a provider you already use',
		'sureforms'
	),
	__(
		'Covers every email your site sends, with logs of each one',
		'sureforms'
	),
];

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
	// 'installing' | 'activating' while the button is working, '' otherwise.
	const [ progress, setProgress ] = useState( '' );
	const [ error, setError ] = useState( '' );
	// Set once SureMail was opened in a new tab, until the step is left.
	const [ openedSureMail, setOpenedSureMail ] = useState( false );
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
		} );
		navigateToNextRoute();
	};

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
			await refreshStatus();
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

	const installLabel = () => {
		if ( 'installing' === progress ) {
			return __( 'Installing SureMail…', 'sureforms' );
		}
		if ( 'activating' === progress ) {
			return __( 'Activating SureMail…', 'sureforms' );
		}
		return 'activate' === status?.action?.type
			? __( 'Activate SureMail', 'sureforms' )
			: __( 'Set up email delivery', 'sureforms' );
	};

	const disclosure = (
		<Text size={ 14 } color="secondary">
			{ __(
				'SureMail is a free plugin from the SureForms team that sends every email your site sends. If you choose SureContact in SureMail, you create a free SureContact account there, and your site\'s outgoing emails, including recipient addresses and content, are sent through SureContact. You can pick a different email provider in SureMail instead.',
				'sureforms'
			) }
		</Text>
	);

	let title;
	let description;
	let body = null;
	let continueProps;
	let skipProps;

	switch ( view ) {
		case 'install':
			title = __( 'Make Sure Your Form Emails Are Sent', 'sureforms' );
			description = __(
				'WordPress hands form emails to your server\'s built-in mail function, which many hosts block or send to spam. SureMail sends them through a proper email service instead.',
				'sureforms'
			);
			body = (
				<>
					<FeatureList
						heading={ __( 'What SureMail does', 'sureforms' ) }
						items={ features }
					/>
					{ disclosure }
					{ false === status?.can_install && (
						<Text size={ 14 } color="secondary">
							{ __(
								'Your account cannot install plugins. Ask a site administrator to set up email delivery.',
								'sureforms'
							) }
						</Text>
					) }
				</>
			);
			continueProps =
				false === status?.can_install
					? {
						onClick: () =>
							leave( 'cannot_install', { skipped: true } ),
						text: __( 'Continue', 'sureforms' ),
					  }
					: { onClick: handleInstall, text: installLabel() };
			skipProps = {
				onClick: () => leave( 'skip', { skipped: true } ),
			};
			break;

		case 'connect':
			title = __( 'Connect an Email Service in SureMail', 'sureforms' );
			description = openedSureMail
				? __(
					'Finish connecting in the SureMail tab, then come back here. This step updates by itself.',
					'sureforms'
				  )
				: __(
					'SureMail is active but not connected to an email service yet, so form emails are not being sent through it. Connect SureContact for free, or another provider.',
					'sureforms'
				  );
			body = disclosure;
			continueProps = {
				onClick: openSureMail,
				text:
					status?.action?.label ||
					__( 'Connect in SureMail', 'sureforms' ),
				icon: <ExternalLink />,
			};
			skipProps = {
				onClick: () => leave( 'skip', { skipped: true } ),
				text: __( 'Skip for now', 'sureforms' ),
			};
			break;

		case 'limited':
			title = __( 'Verify Your SureContact Account', 'sureforms' );
			description =
				status?.message ||
				__(
					'SureContact is connected, but only a few emails a day are accepted until you verify your account email.',
					'sureforms'
				);
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
			continueProps = {
				onClick: () => leave( getDoneAction( status ) ),
				text: __( 'Continue', 'sureforms' ),
			};
			skipProps = {};
	}

	return (
		<div className="space-y-4">
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

			<Header title={ title } description={ description } />

			{ body }

			{ error && (
				<Text size={ 14 } color="error" role="alert">
					{ error }
				</Text>
			) }

			<Divider />

			<NavigationButtons
				backProps={ { onClick: navigateToPreviousRoute } }
				continueProps={ continueProps }
				skipProps={ skipProps }
			/>
		</div>
	);
};

export default EmailDelivery;
