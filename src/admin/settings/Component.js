import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import { useDebouncedCallback } from 'use-debounce';
import 'react-loading-skeleton/dist/skeleton.css';
import toast, { Toaster } from 'react-hot-toast';

import { navigation } from './Navigation';
import GeneralPage from './pages/General';
import EmailPage from './pages/Email';
import SecurityPage from './pages/Security';

const Component = ( { path } ) => {
	const [ pageTitle, setPageTitle ] = useState( '' );
	const [ pageIcon, setPageIcon ] = useState( '' );
	const [ loading, setLoading ] = useState( false );

	// Global settings states.
	const [ generalTabOptions, setGeneralTabOptions ] = useState( {
		srfm_ip_log: false,
		srfm_honeypot: false,
		srfm_form_analytics: false,
		srfm_gdpr: false,
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
	} );
	const [ dynamicBlockOptions, setDynamicBlockOptions ] = useState( {} );

	// Options to fetch from API.
	const options_to_fetch = [
		'srfm_general_settings_options',
		'srfm_email_summary_settings_options',
		'srfm_security_settings_options',
		'get_default_dynamic_block_option',
	];

	// set page title and icon based on the path.
	useEffect( () => {
		if ( path ) {
			navigation.forEach( ( single ) => {
				const slug = single?.slug && single.slug ? single.slug : '';
				const title = single?.name && single.name ? single.name : '';
				const icon = single?.icon && single.icon ? single.icon : '';
				if ( slug ) {
					if ( slug === path ) {
						setPageTitle( title );
						setPageIcon( icon );
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
					path: `sureforms/v1/srfm-global-settings?options_to_fetch=${ options_to_fetch }`,
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
						srfm_honeypot,
						srfm_form_analytics,
						srfm_gdpr,
					} = srfm_general_settings_options;
					setGeneralTabOptions( {
						srfm_ip_log,
						srfm_honeypot,
						srfm_form_analytics,
						srfm_gdpr,
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
					} = data.srfm_security_settings_options;
					setSecurityTabOptions( {
						srfm_v2_checkbox_site_key,
						srfm_v2_checkbox_secret_key,
						srfm_v2_invisible_site_key,
						srfm_v2_invisible_secret_key,
						srfm_v3_site_key,
						srfm_v3_secret_key,
					} );
				}

				if ( data.get_default_dynamic_block_option ) {
					setDynamicBlockOptions( {
						...data.get_default_dynamic_block_option,
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
	const debouncedSave = useDebouncedCallback( ( newFormData ) => {
		try {
			apiFetch( {
				path: 'sureforms/v1/srfm-global-settings',
				method: 'POST',
				body: JSON.stringify( newFormData ),
				headers: {
					'content-type': 'application/json',
					'X-WP-Nonce': srfm_admin.global_settings_nonce,
				},
			} ).then( () => {
				toast.success(
					__( 'Settings Saved Successfully!', 'sureforms' ),
					{
						duration: 1500,
					}
				);
				setTimeout( () => {
					toast.dismiss();
				}, 1500 );
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

		debouncedSave( updatedTabOptions );
	}

	return (
		<>
			<Toaster
				containerClassName="srfm-toast-container"
				position="top-right"
			/>
			<div className="srfm-page-heading">
				<div className="srfm-page-icon">{ pageIcon }</div>
				<span>{ pageTitle }</span>
			</div>
			<div className="srfm-page-content">
				{ 'general-settings' === path && (
					<GeneralPage
						loading={ loading }
						generalTabOptions={ generalTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
						dynamicBlockOptions={ dynamicBlockOptions }
					/>
				) }
				{ 'email-settings' === path && (
					<EmailPage
						loading={ loading }
						emailTabOptions={ emailTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				) }
				{ 'security-settings' === path && (
					<SecurityPage
						loading={ loading }
						securitytabOptions={ securitytabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				) }
			</div>
		</>
	);
};

export default Component;
