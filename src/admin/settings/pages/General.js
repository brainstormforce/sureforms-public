import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button, Input, Loader, Select, Switch, toast } from '@bsf/force-ui';
import ContentSection from '../components/ContentSection';

const GeneralPage = ( {
	loading,
	generalTabOptions,
	emailTabOptions,
	updateGlobalSettings,
} ) => {
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
					value={ emailTabOptions.srfm_email_summary }
					onChange={ ( value ) =>
						updateGlobalSettings(
							'srfm_email_summary',
							value,
							'email-settings'
						)
					}
				/>
				{ emailTabOptions.srfm_email_summary && (
					<>
						<div className="flex items-end gap-2">
							<div className="flex-1">
								<Input
									size="md"
									label={ __( 'Send Email To', 'sureforms' ) }
									type="email"
									value={ emailTabOptions.srfm_email_sent_to }
									onChange={ ( value ) =>
										updateGlobalSettings(
											'srfm_email_sent_to',
											value,
											'email-settings'
										)
									}
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
												srfm_email_sent_to:
													emailTabOptions.srfm_email_sent_to,
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
							value={ getReportsLabel(
								emailTabOptions.srfm_schedule_report
							) }
							onChange={ ( value ) =>
								updateGlobalSettings(
									'srfm_schedule_report',
									value,
									'email-settings'
								)
							}
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
					value={ generalTabOptions.srfm_ip_log }
					onChange={ ( value ) =>
						updateGlobalSettings(
							'srfm_ip_log',
							value,
							'general-settings'
						)
					}
				/>
			</>
		);
	};

	const AdminNotificationContent = () => {
		return (
			<Switch
				label={ {
					heading: __( 'Enable Admin Notification', 'sureforms' ),
					description: __(
						'Admin notifications keep you informed about new form entries since your last visit.',
						'sureforms'
					),
				} }
				value={ generalTabOptions.srfm_admin_notification }
				onChange={ ( value ) =>
					updateGlobalSettings(
						'srfm_admin_notification',
						value,
						'general-settings'
					)
				}
			/>
		);
	};

	const UsageTrackingContent = () => {
		return (
			<Switch
				label={ {
					heading: __( 'Enable Anonymous Analytics', 'sureforms' ),
					description: (
						<>
							<p>
								{ __(
									'Collect non-sensitive information from your website, such as the PHP version and features used, to help us fix bugs faster, make smarter decisions, and build features that actually matter to you. ',
									'sureforms'
								) }
								<a
									href="https://sureforms.com/share-usage-data/"
									target="_blank"
									rel="noopener noreferrer"
									className="text-field-helper"
								>
									{ __( 'Learn More', 'sureforms' ) }
								</a>
							</p>
						</>
					),
				} }
				value={ generalTabOptions.srfm_bsf_analytics }
				onChange={ ( value ) =>
					updateGlobalSettings(
						'srfm_bsf_analytics',
						value,
						'general-settings'
					)
				}
			/>
		);
	};

	return (
		<div className="space-y-6">
			<ContentSection
				loading={ loading }
				title={ __( 'Email Summaries', 'sureforms' ) }
				content={ EmailSummariesContent() }
			/>
			<ContentSection
				loading={ loading }
				title={ __( 'IP Logging', 'sureforms' ) }
				content={ IPLoggingContent() }
			/>
			<ContentSection
				loading={ loading }
				title={ __( 'Admin Notification', 'sureforms' ) }
				content={ AdminNotificationContent() }
			/>
			<ContentSection
				loading={ loading }
				title={ __( 'Anonymous Analytics', 'sureforms' ) }
				content={ UsageTrackingContent() }
			/>
		</div>
	);
};

export default GeneralPage;
