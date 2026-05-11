import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import { useDebouncedCallback } from 'use-debounce';
import { toast } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import { getNavigation } from './Navigation';
import GeneralPage from './pages/General';
import GlobalDefaultsPage from './pages/GlobalDefaults';
import ValidationsPage from './pages/Validations';
import SecurityPage from './pages/Security';
import IntegrationPage from './pages/Integrations';
import GoogleMapsPage from './pages/GoogleMaps';
import PaymentsPage from '../payment/global-setting-page';
import MCPPage from './pages/MCP';
import OttoKitPage from './pages/OttoKit';
import { applyFilters } from '@wordpress/hooks';
import PageTitleSection from '@Admin/components/PageTitleSection';

const Component = ( { path, subpage } ) => {
	const [ pageTitle, setPageTitle ] = useState( '' );
	// State to maintain whether to hide the page title.
	const [ hidePageTitle, setHidePageTitle ] = useState( false );
	const [ loading, setLoading ] = useState( true );
	const [ helpText, setHelpText ] = useState( '' );

	// Global settings states.
	const [ generalTabOptions, setGeneralTabOptions ] = useState( {
		srfm_ip_log: false,
		srfm_form_analytics: false,
		srfm_bsf_analytics: false,
		srfm_admin_notification: true,
	} );
	const [ emailTabOptions, setEmailTabOptions ] = useState( {
		srfm_email_summary: false,
		srfm_emails_send_to: srfm_admin.admin_email,
		srfm_schedule_report: 'Monday',
	} );
	const [ securitytabOptions, setSecurityTabOptions ] = useState( {
		srfm_v2_checkbox_site_key: '',
		srfm_v2_checkbox_secret_key: '',
		srfm_v2_invisible_site_key: '',
		srfm_v2_invisible_secret_key: '',
		srfm_v3_site_key: '',
		srfm_v3_secret_key: '',
		srfm_cf_appearance_mode: 'auto',
		srfm_cf_turnstile_site_key: '',
		srfm_cf_turnstile_secret_key: '',
		srfm_hcaptcha_site_key: '',
		srfm_hcaptcha_secret_key: '',
		srfm_honeypot: false,
	} );
	const [ mcpTabOptions, setMcpTabOptions ] = useState( {
		srfm_abilities_api: false,
		srfm_abilities_api_edit: false,
		srfm_abilities_api_delete: false,
		srfm_mcp_server: false,
	} );
	const [ dynamicBlockOptions, setDynamicBlockOptions ] = useState( {} );
	const [ preDynamicBlockOptions, setPreDynamicBlockOptions ] = useState(
		{}
	);
	const [ paymentsSettings, setPaymentsSettings ] = useState( {} );
	const [ pluginConnected, setPluginConnected ] = useState(
		srfm_admin?.integrations?.sure_triggers?.connected ?? null
	);
	const [ localPluginStatus, setLocalPluginStatus ] = useState(
		srfm_admin?.integrations?.sure_triggers?.status
	);
	const [ formRestrictionOptions, setFormRestrictionOptions ] = useState( {
		max_entries: {
			status: false,
			maxEntries: 0,
			message: '',
		},
		ip_restriction: {
			status: false,
			mode: 'block',
			ips: '',
			includeDisallowedKeys: false,
			message: '',
		},
		country_restriction: {
			status: false,
			mode: 'block',
			countries: '',
			message: '',
		},
		keyword_restriction: {
			status: false,
			keywords: '',
			includeDisallowedKeys: false,
			message: '',
		},
	} );
	const [ complianceOptions, setComplianceOptions ] = useState( {
		gdpr: false,
		do_not_store_entries: false,
		auto_delete_entries: false,
		auto_delete_days: 30,
	} );
	const [ formConfirmationOptions, setFormConfirmationOptions ] = useState( {
		confirmation_type: 'same page',
		message:
			window.srfm_admin?.default_confirmation_message ||
			__(
				'Thank you for contacting us! We will be in touch with you shortly.',
				'sureforms'
			),
		submission_action: 'hide form',
		page_url: '',
		custom_url: '',
		enable_query_params: false,
		query_params: [],
	} );
	const [ emailNotificationOptions, setEmailNotificationOptions ] = useState(
		{
			email_to: '{admin_email}',
			subject: sprintf(
				/* translators: %s: {form_title} smart tag placeholder. */
				__( 'New Form Submission - %s', 'sureforms' ),
				'{form_title}'
			),
			email_body: '{all_data}',
			from_name: '{site_title}',
			from_email: '{admin_email}',
			email_cc: '{admin_email}',
			email_bcc: '{admin_email}',
			email_reply_to: '{admin_email}',
		}
	);

	// Ref always holds the latest state slices so useCallback closures never go stale.
	const settingsStateRef = useRef( {} );
	settingsStateRef.current = {
		emailTabOptions,
		generalTabOptions,
		securitytabOptions,
		dynamicBlockOptions,
		paymentsSettings,
		formRestrictionOptions,
		complianceOptions,
		formConfirmationOptions,
		emailNotificationOptions,
	};

	// Stable refs to setter functions (useState setters are guaranteed stable by React).
	const tabSetters = useRef( {
		'email-settings': setEmailTabOptions,
		'general-settings': setGeneralTabOptions,
		'security-settings': setSecurityTabOptions,
		'general-settings-dynamic-opt': setDynamicBlockOptions,
		'payments-settings': setPaymentsSettings,
		'form-restriction-settings': setFormRestrictionOptions,
		'compliance-settings': setComplianceOptions,
		'form-confirmation-settings': setFormConfirmationOptions,
		'email-notification-settings': setEmailNotificationOptions,
	} );

	// Map from tab slug to the state key in settingsStateRef.
	const stateKeys = useRef( {
		'email-settings': 'emailTabOptions',
		'general-settings': 'generalTabOptions',
		'security-settings': 'securitytabOptions',
		'general-settings-dynamic-opt': 'dynamicBlockOptions',
		'payments-settings': 'paymentsSettings',
		'form-restriction-settings': 'formRestrictionOptions',
		'compliance-settings': 'complianceOptions',
		'form-confirmation-settings': 'formConfirmationOptions',
		'email-notification-settings': 'emailNotificationOptions',
	} );

	// Options to fetch from API.
	const optionsToFetch = [
		'srfm_general_settings_options',
		'srfm_email_summary_settings_options',
		'srfm_security_settings_options',
		'srfm_default_dynamic_block_option',
		'srfm_mcp_settings_options',
		'srfm_form_restriction_settings_options',
		'srfm_compliance_settings_options',
		'srfm_form_confirmation_settings_options',
		'srfm_email_notification_settings_options',
	];

	// set page title and icon based on the path and subpage.
	useEffect( () => {
		if ( path ) {
			getNavigation().forEach( ( single ) => {
				const slug = single?.slug && single.slug ? single.slug : '';
				let title = single?.name && single.name ? single.name : '';
				// eslint-disable-next-line no-shadow
				const helpText =
					single?.helpText && single.helpText ? single.helpText : '';
				// Check for the property to hide the page title.
				// Show title for Google Maps when pro is active (settings page), hide for banner.
				const hideTitle = slug === 'google-maps-settings' && srfm_admin?.is_pro_active
					? false
					: !! single?.hidePageTitle;
				if ( slug ) {
					if ( slug === path ) {
						// If there's a subpage and the navigation item has a submenu,
						// use the submenu item's name as the title.
						if ( single.submenu ) {
							// Get the current or default subpage.
							const currentSubpage =
								subpage || single.submenu[ 0 ]?.slug;
							const submenuItem = single.submenu.find(
								( item ) => item.slug === currentSubpage
							);
							if ( submenuItem ) {
								title = submenuItem.name;
							}
						}
						setPageTitle( title );
						setHidePageTitle( hideTitle );
						setHelpText( helpText );
					}
				}
			} );
		}
	}, [ path, subpage ] );

	// Fetch global settings.
	useEffect( () => {
		const fetchData = async () => {
			setLoading( true );
			try {
				const data = await apiFetch( {
					path: `sureforms/v1/srfm-global-settings?options_to_fetch=${ optionsToFetch }`,
					method: 'GET',
					headers: {
						'content-type': 'application/json',
						'X-WP-Nonce': srfm_admin.global_settings_nonce,
					},
				} );

				const {
					srfm_general_settings_options,
					srfm_email_summary_settings_options,
				} = data;

				if ( srfm_general_settings_options ) {
					const {
						srfm_ip_log,
						srfm_form_analytics,
						srfm_bsf_analytics,
						srfm_admin_notification,
					} = srfm_general_settings_options;
					setGeneralTabOptions( {
						srfm_ip_log,
						srfm_form_analytics,
						srfm_bsf_analytics,
						srfm_admin_notification,
					} );
				}

				if ( srfm_email_summary_settings_options ) {
					const {
						srfm_email_summary,
						srfm_email_sent_to,
						srfm_schedule_report,
					} = srfm_email_summary_settings_options;
					setEmailTabOptions( {
						srfm_email_summary,
						srfm_email_sent_to,
						srfm_schedule_report,
					} );
				}

				if ( data.srfm_security_settings_options ) {
					const {
						srfm_v2_checkbox_site_key,
						srfm_v2_checkbox_secret_key,
						srfm_v2_invisible_site_key,
						srfm_v2_invisible_secret_key,
						srfm_v3_site_key,
						srfm_v3_secret_key,
						srfm_cf_appearance_mode,
						srfm_cf_turnstile_site_key,
						srfm_cf_turnstile_secret_key,
						srfm_hcaptcha_site_key,
						srfm_hcaptcha_secret_key,
						srfm_honeypot,
					} = data.srfm_security_settings_options;
					setSecurityTabOptions( {
						srfm_v2_checkbox_site_key,
						srfm_v2_checkbox_secret_key,
						srfm_v2_invisible_site_key,
						srfm_v2_invisible_secret_key,
						srfm_v3_site_key,
						srfm_v3_secret_key,
						srfm_cf_appearance_mode,
						srfm_cf_turnstile_site_key,
						srfm_cf_turnstile_secret_key,
						srfm_hcaptcha_site_key,
						srfm_hcaptcha_secret_key,
						srfm_honeypot,
					} );
				}

				if ( data.srfm_mcp_settings_options ) {
					const {
						srfm_abilities_api,
						srfm_abilities_api_edit,
						srfm_abilities_api_delete,
						srfm_mcp_server,
					} = data.srfm_mcp_settings_options;
					setMcpTabOptions( {
						srfm_abilities_api,
						srfm_abilities_api_edit,
						srfm_abilities_api_delete,
						srfm_mcp_server,
					} );
				}

				if ( data.srfm_default_dynamic_block_option ) {
					setDynamicBlockOptions( {
						...data.srfm_default_dynamic_block_option,
					} );
					setPreDynamicBlockOptions( {
						...data.srfm_default_dynamic_block_option,
					} );
				}

				if ( data.payment_settings ) {
					setPaymentsSettings( data.payment_settings );
				}

				if ( data.srfm_form_restriction_settings_options ) {
					setFormRestrictionOptions(
						data.srfm_form_restriction_settings_options
					);
				}

				if ( data.srfm_compliance_settings_options ) {
					setComplianceOptions(
						data.srfm_compliance_settings_options
					);
				}

				if ( data.srfm_form_confirmation_settings_options ) {
					setFormConfirmationOptions(
						data.srfm_form_confirmation_settings_options
					);
				}

				if ( data.srfm_email_notification_settings_options ) {
					setEmailNotificationOptions(
						data.srfm_email_notification_settings_options
					);
				}

				setLoading( false );
			} catch ( error ) {
				toast.error(
					__(
						'Failed to load settings. Please refresh and try again.',
						'sureforms'
					)
				);
			}
		};

		fetchData();
	}, [] );

	// Save global settings.
	const debouncedSave = useDebouncedCallback( ( newFormData, tab ) => {
		try {
			if ( tab === 'general-settings-dynamic-opt' ) {
				const hasEmptyValue = Object.values( newFormData ).some(
					( value ) => value.trim() === ''
				);
				if ( hasEmptyValue ) {
					toast.error(
						__( 'This field cannot be left blank.', 'sureforms' )
					);
					setDynamicBlockOptions( { ...preDynamicBlockOptions } );
					return;
				}
			}
			apiFetch( {
				path: 'sureforms/v1/srfm-global-settings',
				method: 'POST',
				body: JSON.stringify( newFormData ),
				headers: {
					'content-type': 'application/json',
					'X-WP-Nonce': srfm_admin.global_settings_nonce,
				},
			} )
				.then( ( response ) => {
					toast.success( response?.data );
					setPreDynamicBlockOptions( newFormData );
				} )
				.catch( () => {
					toast.error(
						__(
							'Failed to save settings. Please try again.',
							'sureforms'
						)
					);
				} );
		} catch ( error ) {
			toast.error(
				__( 'Failed to save settings. Please try again.', 'sureforms' )
			);
		}
	}, 1000 );

	// Handle global settings change.
	// `setting` may be a string key (single field) or a plain object (batch of
	// fields). The batch form is used by the debounced flush in GlobalDefaults
	// so that multiple pending field changes are merged in one state update and
	// one save call, avoiding stale-closure data-loss bugs.
	// Wrapped in useCallback with empty deps so the function reference is stable
	// across renders — prevents every child page from re-rendering on any state
	// change. State is read via settingsStateRef so closures never go stale.
	const updateGlobalSettings = useCallback(
		( setting, value, tab ) => {
			const setter = tabSetters.current[ tab ];
			const stateKey = stateKeys.current[ tab ];
			if ( ! setter || ! stateKey ) {
				return;
			}
			const changes =
				setting !== null && typeof setting === 'object'
					? setting
					: { [ setting ]: value };
			const updatedTabOptions = {
				...settingsStateRef.current[ stateKey ],
				srfm_tab: tab,
				...changes,
			};
			setter( updatedTabOptions );
			debouncedSave( updatedTabOptions, tab );
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[]
	); // stable — reads state via ref; setters and debouncedSave are stable
	const pathsForFullWidth = [ 'ottokit-settings', 'integration-settings' ];
	const isFullWidth = pathsForFullWidth.includes( path ) ||
		( 'google-maps-settings' === path && ! srfm_admin?.is_pro_active );

	return (
		<>
			{ pageTitle && (
				<PageTitleSection
					title={ pageTitle }
					hidePageTitle={ hidePageTitle }
					helpText={ helpText }
				/>
			) }
			{ /* Added the below check to make the container full width for the OttoKit tab. */ }
			<div
				className={ cn(
					'mx-auto p-4 rounded-xl bg-background-primary shadow-sm',
					isFullWidth ? 'w-full' : 'max-w-content-container'
				) }
			>
				{ 'general-settings' === path && (
					<GeneralPage
						loading={ loading }
						emailTabOptions={ emailTabOptions }
						generalTabOptions={ generalTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				) }
				{ 'global-defaults' === path && (
					<GlobalDefaultsPage
						loading={ loading }
						formRestrictionOptions={ formRestrictionOptions }
						complianceOptions={ complianceOptions }
						emailNotificationOptions={ emailNotificationOptions }
						formConfirmationOptions={ formConfirmationOptions }
						updateGlobalSettings={ updateGlobalSettings }
						toast={ toast }
					/>
				) }
				{ 'validation-settings' === path && (
					<ValidationsPage
						loading={ loading }
						dynamicBlockOptions={ dynamicBlockOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				) }
				{ 'security-settings' === path && (
					<SecurityPage
						loading={ loading }
						securitytabOptions={ securitytabOptions }
						generalTabOptions={ generalTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				) }

				{ 'mcp-settings' === path && (
					<MCPPage
						loading={ loading }
						mcpTabOptions={ mcpTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				) }
				{ 'ottokit-settings' === path && (
					<OttoKitPage
						loading={ loading }
						pluginConnected={ pluginConnected }
						setPluginConnected={ setPluginConnected }
						localPluginStatus={ localPluginStatus }
						setLocalPluginStatus={ setLocalPluginStatus }
					/>
				) }

				{ 'integration-settings' === path && (
					<IntegrationPage loading={ loading } />
				) }
				{ 'google-maps-settings' === path && (
					<GoogleMapsPage
						loading={ loading }
						toast={ toast }
					/>
				) }
				{ 'payments-settings' === path && (
					<PaymentsPage
						loading={ loading }
						paymentsSettings={ paymentsSettings }
						updateGlobalSettings={ updateGlobalSettings }
						setPaymentsSettings={ setPaymentsSettings }
						subpage={ subpage }
					/>
				) }
				{ applyFilters(
					'srfm.settings.page.content',
					[],
					path,
					loading,
					toast
				) }
			</div>
		</>
	);
};

export default Component;
