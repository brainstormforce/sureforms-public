import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Snackbar } from '@wordpress/components';

import { MdOutlineDone } from 'react-icons/md';
import { useDebouncedCallback } from 'use-debounce';

import { navigation } from './Navigation';
import GeneralPage from './pages/General';
import EmailPage from './pages/Email';
import SecurityPage from './pages/Security';

const Component = ( { path } ) => {
	const [ pageTitle, setPageTitle ] = useState( '' );
	const [ pageIcon, setPageIcon ] = useState( '' );
	const [ isSaved, setIsSaved ] = useState( false );

	useEffect( () => {
		if ( path ) {
			navigation.map( ( single ) => {
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

	const [ generalTabOptions, setGeneralTabOptions ] = useState( {
		srfm_ip_log: false,
		srfm_honeypot: false,
		srfm_form_analytics: false,
		srfm_gdpr: false,
	} );

	const [ emailTabOptions, setEmailTabOptions ] = useState( {
		srfm_email_summary: false,
		srfm_emails_send_to: sureforms_admin.admin_email,
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

	// Fetch global settings.
	useEffect( () => {
		const fetchData = async () => {
			try {
				const data = await apiFetch( {
					path: 'sureforms/v1/srfm-global-settings',
					method: 'GET',
					headers: {
						'content-type': 'application/json',
						'X-WP-Nonce': sureforms_admin.global_settings_nonce,
					},
				} );

				console.log( data );

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
			} catch ( error ) {
				console.error( 'Error fetching data:', error );
			}
		};

		fetchData();
	}, [] );

	// Save global settings.
	const debouncedSave = useDebouncedCallback( ( newFormData ) => {
		setIsSaved( false );
		try {
			apiFetch( {
				path: 'sureforms/v1/srfm-global-settings',
				method: 'POST',
				body: JSON.stringify( newFormData ),
				headers: {
					'content-type': 'application/json',
					'X-WP-Nonce': sureforms_admin.global_settings_nonce,
				},
			} ).then( () => {
				setIsSaved( true );
				setTimeout( () => {
					setIsSaved( false );
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
		} else {
			return;
		}

		debouncedSave( updatedTabOptions );
	}

	return (
		<>
			<div class="srfm-page-heading">
				<div class="srfm-page-icon">{ pageIcon }</div>
				<span>{ pageTitle }</span>
				{ isSaved && (
					<Snackbar onDismiss={ () => setIsSaved( false ) }>
						<div
							style={ {
								display: 'flex',
								alignItems: 'center',
								gap: '.5rem',
							} }
						>
							<MdOutlineDone fill="green" size={ 24 } />
							<span>
								{ __(
									'Settings Saved Successfully!',
									'sureforms'
								) }
							</span>
						</div>
					</Snackbar>
				) }
			</div>
			<div class="srfm-page-content">
				{ 'general-settings' === path && (
					<GeneralPage
						generalTabOptions={ generalTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				) }
				{ 'email-settings' === path && (
					<EmailPage
						emailTabOptions={ emailTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				) }
				{ 'security-settings' === path && (
					<SecurityPage
						securitytabOptions={ securitytabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				) }
			</div>
		</>
	);
};

export default Component;
