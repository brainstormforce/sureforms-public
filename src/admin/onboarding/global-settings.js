import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Text, Container } from '@bsf/force-ui';
import { useOnboardingNavigation } from './hooks';
import { useOnboardingState } from './onboarding-state';
import NavigationButtons from './navigation-buttons';

const GlobalSettings = () => {
	const [ settings, setSettings ] = useState( {
		ipLogging: false,
		emailSummaries: true,
	} );
	const [ , actions ] = useOnboardingState();
	const { navigateToNextRoute, navigateToPreviousRoute } = useOnboardingNavigation();

	const handleSettingToggle = ( settingKey, enabled ) => {
		setSettings( prevSettings => ( {
			...prevSettings,
			[ settingKey ]: enabled,
		} ) );
	};

	const handleContinue = async () => {
		try {
			// Save global settings
			await wp.apiFetch( {
				path: '/wp/v2/settings',
				method: 'POST',
				data: {
					srfm_ip_logging: settings.ipLogging,
					srfm_email_summaries: settings.emailSummaries,
				},
				headers: {
					'X-WP-Nonce': srfm_admin.nonce,
				},
			} );

			// Update onboarding state
			actions.setGlobalSettingsConfigured( true );
			navigateToNextRoute();
		} catch ( error ) {
			console.error( 'Error saving global settings:', error );
		}
	};

	const handleBack = async () => {
		navigateToPreviousRoute();
	};

	const handleSkip = async () => {
		navigateToNextRoute();
	};

	return (
		<div className="space-y-6">
			<div className="space-y-1.5">
				<Text as="h2" size={ 30 } weight={ 600 }>
					{ __( 'Global Settings', 'sureforms' ) }
				</Text>
				<Text size={ 16 } weight={ 500 } color="secondary">
					{ __( 'Configure your global form settings to enhance security and get insights about your forms.', 'sureforms' ) }
				</Text>
			</div>

			<div className="space-y-4">
				{/* IP Logging Setting */}
				<div className="flex items-start justify-between p-6 border border-border-subtle rounded-lg bg-white">
					<div className="flex items-start gap-4">
						<div className="text-2xl">🔒</div>
						<div className="flex-1">
							<Text size={ 18 } weight={ 600 } color="primary">
								{ __( 'IP Address Logging', 'sureforms' ) }
							</Text>
							<Text size={ 14 } color="secondary" className="mt-1">
								{ __( 'Log IP addresses of form submissions for security and analytics purposes. This helps track form abuse and provides geographical insights.', 'sureforms' ) }
							</Text>
							<div className="mt-3">
								<Text size={ 12 } color="tertiary">
									{ __( '• Helps prevent spam and abuse', 'sureforms' ) }
								</Text>
								<Text size={ 12 } color="tertiary">
									{ __( '• Provides geographical analytics', 'sureforms' ) }
								</Text>
								<Text size={ 12 } color="tertiary">
									{ __( '• Complies with GDPR when properly disclosed', 'sureforms' ) }
								</Text>
							</div>
						</div>
					</div>
					<div className="flex-shrink-0 ml-4">
						<label className="relative inline-flex items-center cursor-pointer">
							<input
								type="checkbox"
								className="sr-only peer"
								checked={ settings.ipLogging }
								onChange={ ( e ) => handleSettingToggle( 'ipLogging', e.target.checked ) }
							/>
							<div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
						</label>
					</div>
				</div>

				{/* Email Summaries Setting */}
				<div className="flex items-start justify-between p-6 border border-border-subtle rounded-lg bg-white">
					<div className="flex items-start gap-4">
						<div className="text-2xl">📧</div>
						<div className="flex-1">
							<Text size={ 18 } weight={ 600 } color="primary">
								{ __( 'Email Summaries', 'sureforms' ) }
							</Text>
							<Text size={ 14 } color="secondary" className="mt-1">
								{ __( 'Receive weekly email summaries with form submission statistics, popular forms, and performance insights directly in your inbox.', 'sureforms' ) }
							</Text>
							<div className="mt-3">
								<Text size={ 12 } color="tertiary">
									{ __( '• Weekly submission statistics', 'sureforms' ) }
								</Text>
								<Text size={ 12 } color="tertiary">
									{ __( '• Form performance insights', 'sureforms' ) }
								</Text>
								<Text size={ 12 } color="tertiary">
									{ __( '• Popular forms and trends', 'sureforms' ) }
								</Text>
							</div>
						</div>
					</div>
					<div className="flex-shrink-0 ml-4">
						<label className="relative inline-flex items-center cursor-pointer">
							<input
								type="checkbox"
								className="sr-only peer"
								checked={ settings.emailSummaries }
								onChange={ ( e ) => handleSettingToggle( 'emailSummaries', e.target.checked ) }
							/>
							<div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
						</label>
					</div>
				</div>
			</div>

			<Container className="p-6 bg-background-secondary rounded-lg">
				<div className="space-y-4">
					<Text size={ 18 } weight={ 600 }>
						{ __( 'Privacy & Compliance', 'sureforms' ) }
					</Text>
					<Text size={ 14 } color="secondary">
						{ __( 'These settings can be changed at any time from the global settings page. We recommend enabling both features for better form management and security.', 'sureforms' ) }
					</Text>
					<ul className="space-y-2 text-sm text-gray-600">
						<li>• { __( 'All data is stored securely on your server', 'sureforms' ) }</li>
						<li>• { __( 'IP logging can be disabled for GDPR compliance', 'sureforms' ) }</li>
						<li>• { __( 'Email summaries help you stay informed about form performance', 'sureforms' ) }</li>
					</ul>
				</div>
			</Container>

			<NavigationButtons
				backProps={ {
					onClick: handleBack,
				} }
				continueProps={ {
					onClick: handleContinue,
				} }
				skipProps={ {
					onClick: handleSkip,
				} }
			/>
		</div>
	);
};

export default GlobalSettings;
