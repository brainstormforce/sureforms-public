import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Switch, Input, Button, Select, toast } from '@bsf/force-ui';
import { useOnboardingNavigation } from './hooks';
import { useOnboardingState } from './onboarding-state';
import NavigationButtons from './navigation-buttons';
import ContentSection from '@Admin/settings/components/ContentSection';
import apiFetch from '@wordpress/api-fetch';
import { Header, Divider } from './components';

const GlobalSettings = () => {
	const [ emailSettings, setEmailSettings ] = useState( {
		srfm_email_summary: false,
		srfm_email_sent_to: srfm_admin.admin_email,
		srfm_schedule_report: 'Monday',
	} );

	const [ generalSettings, setGeneralSettings ] = useState( {
		srfm_ip_log: false,
		srfm_form_analytics: false,
		srfm_bsf_analytics: false,
	} );

	const [ , actions ] = useOnboardingState();
	const { navigateToNextRoute, navigateToPreviousRoute } = useOnboardingNavigation();

	// Options to fetch from API.
	const optionsToFetch = [
		'srfm_general_settings_options',
		'srfm_email_summary_settings_options',
	];

	// Fetch existing global settings
	useEffect( () => {
		const fetchData = async () => {
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
					setGeneralSettings( prevSettings => ( {
						...prevSettings,
						...srfm_general_settings_options,
					} ) );
				}

				if ( srfm_email_summary_settings_options ) {
					setEmailSettings( prevSettings => ( {
						...prevSettings,
						...srfm_email_summary_settings_options
					} ) );
				}
			} catch ( error ) {
				console.error( 'Error fetching global settings:', error );
			}
		};

		fetchData();
	}, [] );

	const handleSettingToggle = async ( settingKey, value ) => {
		try {
			// Determine which settings object to update
			const isEmailSetting = [
				'srfm_email_summary',
				'srfm_email_sent_to',
				'srfm_schedule_report'
			].includes( settingKey );

			// Update local state
			if ( isEmailSetting ) {
				setEmailSettings( prevSettings => ( {
					...prevSettings,
					[ settingKey ]: value,
				} ) );
			} else {
				setGeneralSettings( prevSettings => ( {
					...prevSettings,
					[ settingKey ]: value,
				} ) );
			}

			// Save to database
			await apiFetch( {
				path: 'sureforms/v1/srfm-global-settings',
				method: 'POST',
				body: JSON.stringify( {
					...( isEmailSetting ? emailSettings : generalSettings ),
					[ settingKey ]: value,
					srfm_tab: isEmailSetting ? 'email-settings' : 'general-settings'
				} ),
				headers: {
					'content-type': 'application/json',
					'X-WP-Nonce': srfm_admin.global_settings_nonce,
				},
			} );
		} catch ( error ) {
			console.error( 'Error saving setting:', error );
			toast.error( __( 'Error saving setting', 'sureforms' ) );
		}
	};

	const handleContinue = async () => {
		actions.setGlobalSettingsConfigured( true );
		navigateToNextRoute();
	};

	const handleBack = async () => {
		navigateToPreviousRoute();
	};

	const EmailSummariesContent = () => {
		const [ sendingTestEmail, setSendingTestEmail ] = useState( false );

		const days = [
			{ label: __( 'Monday', 'sureforms' ), value: 'Monday' },
			{ label: __( 'Tuesday', 'sureforms' ), value: 'Tuesday' },
			{ label: __( 'Wednesday', 'sureforms' ), value: 'Wednesday' },
			{ label: __( 'Thursday', 'sureforms' ), value: 'Thursday' },
			{ label: __( 'Friday', 'sureforms' ), value: 'Friday' },
			{ label: __( 'Saturday', 'sureforms' ), value: 'Saturday' },
			{ label: __( 'Sunday', 'sureforms' ), value: 'Sunday' },
		];

		const getReportsLabel = ( value ) => {
			const selectedDay = days.find( ( day ) => day.value === value );
			return selectedDay ? selectedDay.label : '';
		};

		return (
			<>
				<Switch
					label={ {
						heading: __( 'Enable Email Summaries ', 'sureforms' ),
					} }
					value={ emailSettings.srfm_email_summary }
					onChange={ ( value ) => handleSettingToggle( 'srfm_email_summary', value ) }
				/>
				{ emailSettings.srfm_email_summary && (
					<>
						<div className="flex items-end gap-2">
							<div className="flex-1">
								<Input
									size="md"
									label={ __( 'Send Email To', 'sureforms' ) }
									type="email"
									value={ emailSettings.srfm_email_sent_to || '' }
									onChange={ ( value ) => handleSettingToggle( 'srfm_email_sent_to', value ) }
									required
									autoComplete="off"
								/>
							</div>
							<Button
								variant="outline"
								size="md"
								icon={ sendingTestEmail && <Loader /> }
								iconPosition="left"
								onClick={ async () => {
									if ( sendingTestEmail ) {
										return;
									}

									setSendingTestEmail( true );
									try {
										await apiFetch( {
											path: '/sureforms/v1/send-test-email-summary',
											method: 'POST',
											data: {
												srfm_email_sent_to: emailSettings.srfm_email_sent_to,
											},
										} ).then( ( response ) => {
											setSendingTestEmail( false );
											toast.success( response?.data );
										} );
									} catch ( error ) {
										console.error(
											'Error Sending Test Email Summary:',
											error
										);
									}
								} }
								className="bg-background-secondary"
							>
								{ __( 'Test Email', 'sureforms' ) }
							</Button>
						</div>
						<Select
							value={ getReportsLabel( emailSettings.srfm_schedule_report ) }
							onChange={ ( value ) => handleSettingToggle( 'srfm_schedule_report', value ) }
						>
							<Select.Button
								type="button"
								label={ __( 'Schedule Reports', 'sureforms' ) }
							/>
							<Select.Portal id="srfm-settings-container">
								<Select.Options>
									{ days.map( ( day ) => (
										<Select.Option
											key={ day.value }
											value={ day.value }
										>
											{ day.label }
										</Select.Option>
									) ) }
								</Select.Options>
							</Select.Portal>
						</Select>
					</>
				) }
			</>
		);
	};

	const IPLoggingContent = () => {
		return (
			<>
				<Switch
					label={ {
						heading: __( 'Enable IP Logging', 'sureforms' ),
						description: __(
							"If this option is turned on, the user's IP address will be saved with the form data",
							'sureforms'
						),
					} }
					value={ generalSettings.srfm_ip_log }
					onChange={ ( value ) => handleSettingToggle( 'srfm_ip_log', value ) }
				/>
			</>
		);
	};

	return (
		<div className="space-y-6">
			<Header
				title={ __( 'Global Settings', 'sureforms' ) }
				description={ __( 'Configure your global form settings to enhance security and get insights about your forms.', 'sureforms' ) }
			/>
			<div className="space-y-4">
				<ContentSection
					title={ __( 'Email Summaries', 'sureforms' ) }
					content={ <EmailSummariesContent /> }
				/>
				<ContentSection
					title={ __( 'IP Logging', 'sureforms' ) }
					content={ <IPLoggingContent /> }
				/>
			</div>

			<Divider />

			<NavigationButtons
				backProps={ {
					onClick: handleBack,
				} }
				continueProps={ {
					onClick: handleContinue,
				} }
			/>
		</div>
	);
};

export default GlobalSettings;
