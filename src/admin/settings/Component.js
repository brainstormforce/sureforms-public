import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useDebouncedCallback } from 'use-debounce';
import 'react-loading-skeleton/dist/skeleton.css';
import toast, { Toaster, ToastBar } from 'react-hot-toast';

import { navigation } from './Navigation';
import GeneralPage from './pages/General';
import ValidationsPage from './pages/Validations';
import EmailPage from './pages/Email';
import SecurityPage from './pages/Security';
import IntegrationPage from './pages/Integrations';

const Component = ( { path } ) => {
	const [ pageTitle, setPageTitle ] = useState( '' );
	const [ pageIcon, setPageIcon ] = useState( '' );
	const [ loading, setLoading ] = useState( false );

	// Global settings states.
	const [ generalTabOptions, setGeneralTabOptions ] = useState( {
		srfm_ip_log: false,
		srfm_honeypot: false,
		srfm_form_analytics: false,
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
	const [ preDynamicBlockOptions, setPreDynamicBlockOptions ] = useState(
		{}
	);

	// Options to fetch from API.
	const optionsToFetch = [
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
					const { srfm_ip_log, srfm_honeypot, srfm_form_analytics } =
						srfm_general_settings_options;
					setGeneralTabOptions( {
						srfm_ip_log,
						srfm_honeypot,
						srfm_form_analytics,
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
					setPreDynamicBlockOptions( {
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
	const debouncedSave = useDebouncedCallback( ( newFormData, tab ) => {
		try {
			if ( tab === 'general-settings-dynamic-opt' ) {
				const hasEmptyValue = Object.values( newFormData ).some(
					( value ) => value.trim() === ''
				);
				if ( hasEmptyValue ) {
					toast.dismiss();
					toast.error(
						__( 'This field cannot be left blank.', 'sureforms' ),
						{
							duration: 0,
						}
					);
					setDynamicBlockOptions( { ...preDynamicBlockOptions } );
					setTimeout( () => {
						toast.dismiss();
					}, 1500 );
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
				toast.dismiss();
				toast.success( response?.data, {
					duration: 1500,
				} );
				setPreDynamicBlockOptions( newFormData );
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
		debouncedSave( updatedTabOptions, tab );
	}

	return (
		<>
			<Toaster
				containerClassName="srfm-toast-container"
				position="top-right"
			>
				{ ( t ) => (
					<ToastBar
						toast={ t }
						style={ {
							...t.style,
							animation: t.visible
								? 'slide-in-left 0.5s ease'
								: 'slide-out-right 0.5s ease',
						} }
					/>
				) }
			</Toaster>
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
					/>
				) }
				{ 'validation-settings' === path && (
					<ValidationsPage
						loading={ loading }
						dynamicBlockOptions={ dynamicBlockOptions }
						updateGlobalSettings={ updateGlobalSettings }
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

				{
					'integration-settings' === path && (
						<IntegrationPage
							loading={ loading }
						/>
					)
				}
			</div>
		</>
	);
};

export default Component;
