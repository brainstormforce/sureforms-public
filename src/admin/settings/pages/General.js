import { __ } from '@wordpress/i18n';
import { useState, memo } from '@wordpress/element';
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
							>
								{ __( 'Test Email', 'sureforms' ) }
							</Button>
						</div>
						<Select
							value={ emailTabOptions.srfm_schedule_report }
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
							<Select.Portal>
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

	const UsageTrackingContent = memo(() => {
		const description = (
			<>
				<p>
					{ __( 'Allow SureForms to track non-sensitive usage tracking data.', 'sureforms' ) }
				</p>
				<a
					href="#"
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( ' Learn More', 'sureforms' ) }
				</a>
			</>
		);

		return (
			<Switch
				label={{
					heading: __( 'Enable Usage Tracking', 'sureforms' ),
					description: description,
				}}
				value={ generalTabOptions.srfm_form_analytics }
				onChange={ (value) =>
					updateGlobalSettings('srfm_form_analytics', value, 'general-settings')
				}
			/>
		);
	});

	const ShowUsageTrackingSection = ({ isProActive, loading }) => {
		if (isProActive) return null;

		return (
			<ContentSection
				loading={loading}
				title={__( 'Usage Tracking', 'sureforms' )}
				content={<UsageTrackingContent />}
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
			<ShowUsageTrackingSection
				isProActive={srfm_admin?.is_pro_active}
				loading={loading}
			/>
		</div>
	);
};

export default GeneralPage;
