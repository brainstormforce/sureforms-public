import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useDebouncedCallback } from 'use-debounce';
import { toast } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import { navigation } from './Navigation';
import GeneralPage from './pages/General';
import ValidationsPage from './pages/Validations';
import SecurityPage from './pages/Security';
import IntegrationPage from './pages/Integrations';
import OttoKitPage from './pages/OttoKit';
import { applyFilters } from '@wordpress/hooks';
import PageTitleSection from '@Admin/components/PageTitleSection';

const Component = ( { path } ) => {
	const [ pageTitle, setPageTitle ] = useState( '' );
	// State to maintain whether to hide the page title.
	const [ hidePageTitle, setHidePageTitle ] = useState( false );
	const [ loading, setLoading ] = useState( false );

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
	const [ dynamicBlockOptions, setDynamicBlockOptions ] = useState( {} );
	const [ preDynamicBlockOptions, setPreDynamicBlockOptions ] = useState(
		{}
	);

	// Options to fetch from API.
	const optionsToFetch = [
		'srfm_general_settings_options',
		'srfm_email_summary_settings_options',
		'srfm_security_settings_options',
		'srfm_default_dynamic_block_option',
	];

	// set page title and icon based on the path.
	useEffect( () => {
		if ( path ) {
			navigation.forEach( ( single ) => {
				const slug = single?.slug && single.slug ? single.slug : '';
				const title = single?.name && single.name ? single.name : '';
				// Check for the property to hide the page title.
				const hideTitle = !! single?.hidePageTitle;
				if ( slug ) {
					if ( slug === path ) {
						setPageTitle( title );
						setHidePageTitle( hideTitle );
					}
				}
			} );
		}
	}, [ path ] );

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

				if ( data.srfm_default_dynamic_block_option ) {
					setDynamicBlockOptions( {
						...data.srfm_default_dynamic_block_option,
					} );
					setPreDynamicBlockOptions( {
						...data.srfm_default_dynamic_block_option,
					} );
				}
				setLoading( false );
			} catch ( error ) {
				console.error( 'Error fetching data:', error );
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
			} ).then( ( response ) => {
				toast.success( response?.data );
				setPreDynamicBlockOptions( newFormData );
			} );
		} catch ( error ) {
			console.error( error );
		}
	}, 1000 );

	// Handle global settings change.
	function updateGlobalSettings( setting, value, tab ) {
		let updatedTabOptions;

		if ( tab === 'email-settings' ) {
			updatedTabOptions = {
				...emailTabOptions,
				srfm_tab: tab,
				[ setting ]: value,
			};
			setEmailTabOptions( updatedTabOptions );
		} else if ( tab === 'general-settings' ) {
			updatedTabOptions = {
				...generalTabOptions,
				srfm_tab: tab,
				[ setting ]: value,
			};
			setGeneralTabOptions( updatedTabOptions );
		} else if ( tab === 'security-settings' ) {
			updatedTabOptions = {
				...securitytabOptions,
				srfm_tab: tab,
				[ setting ]: value,
			};
			setSecurityTabOptions( updatedTabOptions );
		} else if ( tab === 'general-settings-dynamic-opt' ) {
			updatedTabOptions = {
				...dynamicBlockOptions,
				srfm_tab: tab,
				[ setting ]: value,
			};
			setDynamicBlockOptions( updatedTabOptions );
		} else {
			return;
		}
		debouncedSave( updatedTabOptions, tab );
	}

	return (
		<>
			{ pageTitle && (
				<PageTitleSection
					title={ pageTitle }
					hidePageTitle={ hidePageTitle }
				/>
			) }
			<div
				className={ cn(
					'mx-auto p-4 rounded-xl bg-background-primary shadow-sm',
					'ottokit-settings' === path
						? 'w-full'
						: 'max-w-content-container'
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

				{ 'ottokit-settings' === path && (
					<OttoKitPage loading={ loading } />
				) }

				{ 'integration-settings' === path && (
					<IntegrationPage loading={ loading } />
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
