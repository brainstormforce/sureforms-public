import { __, sprintf } from '@wordpress/i18n';
import {
	useState,
	useEffect,
	useRef,
	createInterpolateElement,
} from '@wordpress/element';
import { Checkbox, Text } from '@bsf/force-ui';
import { ExternalLink, MailCheck } from 'lucide-react';
import apiFetch from '@wordpress/api-fetch';
import { useOnboardingNavigation } from '../hooks';
import { useOnboardingState } from '../onboarding-state';
import { installAndActivatePlugin } from '@Utils/Helpers';
import { Divider, Header, FeatureList, HERO_PANEL_CLASS } from '../components';
import NavigationButtons from '../components/navigation-buttons';
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

const SURECONTACT_PRIVACY_URL = 'https://surecontact.com/privacy-policy/';
const SURECONTACT_TERMS_URL = 'https://surecontact.com/smtp-terms-of-service/';

const linkClass =
	'underline text-button-primary hover:text-button-primary-hover';

/**
 * The offer, in a tinted card so it reads as the point of the step rather
 * than small print under it.
 *
 * @return {JSX.Element} Value banner.
 */
const ValueBanner = () => (
	<div className="flex gap-3 rounded-lg border border-solid border-brand-border-300 bg-brand-background-50 p-4">
		<MailCheck className="mt-0.5 size-5 shrink-0 text-icon-interactive" />
		<div className="space-y-3">
			<div className="space-y-1">
				<Text size={ 16 } weight={ 600 } color="primary">
					{ __( 'Free email sending with SureContact', 'sureforms' ) }
				</Text>
				<Text size={ 14 } color="secondary">
					{ __(
						'SureMail connects your site to SureContact, a free sending service from the SureForms team. Your form notifications go out through a real email service instead of your server.',
						'sureforms'
					) }
				</Text>
			</div>
			<FeatureList items={ features } />
		</div>
	</div>
);

/**
 * What the owner agrees to before SureMail is installed or SureContact is
 * connected: both services, named, and what SureContact receives.
 *
 * @param {Object}   props
 * @param {boolean}  props.checked  Whether the box is ticked.
 * @param {Function} props.onChange Called with the new checked value.
 * @param {boolean}  props.installs Whether continuing installs SureMail.
 * @param {string}   props.email    The current user's email, shown so the
 *                                  owner knows which address the account uses.
 * @return {JSX.Element} Consent checkbox.
 */
const ConsentCheckbox = ( { checked, onChange, installs, email } ) => {
	const account = email
		? sprintf(
			/* translators: %s: the current user's email address. */
			__(
				'Connecting SureContact creates a free SureContact account for <b>%s</b>.',
				'sureforms'
			),
			email
		  )
		: __(
			'Connecting SureContact creates a free SureContact account for you.',
			'sureforms'
		  );

	const text = [
		installs
			? __(
				'I agree to set up SureMail, a free plugin that sends and logs every email from this site.',
				'sureforms'
			  )
			: __(
				'I understand that SureMail sends and logs every email from this site.',
				'sureforms'
			  ),
		account,
		__(
			'Your outgoing emails, including recipients and content, are then sent through SureContact. <privacy>Privacy Policy</privacy> · <terms>Terms</terms>',
			'sureforms'
		),
	].join( ' ' );

	return (
		<Checkbox
			checked={ checked }
			onChange={ onChange }
			size="sm"
			label={ {
				heading: (
					<span className="font-normal">
						{ createInterpolateElement( text, {
							b: <strong />,
							privacy: (
								// eslint-disable-next-line jsx-a11y/anchor-has-content -- Text comes from the interpolated string.
								<a
									href={ SURECONTACT_PRIVACY_URL }
									target="_blank"
									rel="noopener noreferrer"
									className={ linkClass }
								/>
							),
							terms: (
								// eslint-disable-next-line jsx-a11y/anchor-has-content -- Text comes from the interpolated string.
								<a
									href={ SURECONTACT_TERMS_URL }
									target="_blank"
									rel="noopener noreferrer"
									className={ linkClass }
								/>
							),
						} ) }
					</span>
				),
			} }
		/>
	);
};

/**
 * Which screen the step shows for an email delivery state.
 *
 * @param {Object|null} status Email delivery status from the REST endpoint.
 * @return {'install'|'connect'|'limited'|'done'} View name.
 */
const getView = ( status ) => {
	const state = status?.state;
	const action = status?.action?.type;

	if (
		'not_configured' === state ||
		'install_activate' === action ||
		'activate' === action
	) {
		return 'install';
	}
	if ( 'suremail_no_connection' === state || 'open_suremail' === action ) {
		return 'limited' === state ? 'limited' : 'connect';
	}
	if ( 'limited' === state ) {
		return 'limited';
	}
	return 'done';
};

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
	// Required before SureMail is installed or opened to connect SureContact.
	const [ consented, setConsented ] = useState( false );
	const userEmail = srfm_admin?.website_lead_details?.email ?? '';
	// State the owner arrived with, for analytics. Captured once.
	const stateAtStep = useRef( srfm_admin?.email_delivery?.state ?? '' );

	const refreshStatus = async () => {
		try {
			const next = await fetchStatus();
			if ( next?.state ) {
				setStatus( next );
			}
			return next;
		} catch ( fetchError ) {
			return null;
		}
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
			consented,
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

	const consent = ( installs ) => (
		<div className="space-y-1.5">
			<ConsentCheckbox
				checked={ consented }
				onChange={ setConsented }
				installs={ installs }
				email={ userEmail }
			/>
			<Text size={ 12 } color="tertiary">
				{ __(
					'Prefer another provider? You can connect one in SureMail instead of SureContact.',
					'sureforms'
				) }
			</Text>
		</div>
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
					<ValueBanner />
					{ false !== status?.can_install && consent( true ) }
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
					: {
						onClick: handleInstall,
						text: installLabel(),
						disabled: ! consented,
					  };
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
			body = (
				<>
					<ValueBanner />
					{ consent( false ) }
				</>
			);
			continueProps = {
				onClick: openSureMail,
				disabled: ! consented,
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

		default:
			title = status?.title || __( 'Email Delivery', 'sureforms' );
			description = status?.message || '';
			continueProps = {
				onClick: () =>
					leave(
						'other_mailer' === status?.state
							? 'continue_other_mailer'
							: 'continue_ready'
					),
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
