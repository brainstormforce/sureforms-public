import { __ } from '@wordpress/i18n';
import {
	ToggleControl,
	SelectControl,
	TextControl,
	Spinner,
} from '@wordpress/components';

import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import toast from 'react-hot-toast';

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
				<ToggleControl
					label={ __( 'Enable Email Summaries ', 'sureforms' ) }
					checked={ emailTabOptions.srfm_email_summary }
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
						<div className="srfm-email-input-wrapper">
							<TextControl
								label={ __( 'Send Email To', 'sureforms' ) }
								type="text"
								className="srfm-components-input-control"
								value={ emailTabOptions.srfm_email_sent_to }
								onChange={ ( value ) =>
									updateGlobalSettings(
										'srfm_email_sent_to',
										value,
										'email-settings'
									)
								}
							/>
							<button
								className="srfm-button-secondary srfm-button-xs srfm-email-test-btn"
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
											toast.dismiss();
											toast.success( response?.data, {
												duration: 1500,
											} );
											setTimeout( () => {
												toast.dismiss();
											}, 1500 );
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
								{ sendingTestEmail && (
									<Spinner
										style={ {
											margin: '0',
											color: '#d54407',
										} }
									/>
								) }
							</button>
						</div>
						<SelectControl
							label={ __( 'Schedule Reports', 'sureforms' ) }
							value={ emailTabOptions.srfm_schedule_report }
							className="srfm-components-select-control"
							onChange={ ( value ) =>
								updateGlobalSettings(
									'srfm_schedule_report',
									value,
									'email-settings'
								)
							}
							options={ days }
						/>
					</>
				) }
			</>
		);
	};

	const IPLoggingContent = () => {
		return (
			<>
				<ToggleControl
					label={ __( 'Enable IP Logging', 'sureforms' ) }
					help={ __(
						"If this option is turned on, the user's IP address will be saved with the form data",
						'sureforms'
					) }
					checked={ generalTabOptions.srfm_ip_log }
					onChange={ ( value ) =>
						updateGlobalSettings(
							'srfm_ip_log',
							value,
							'general-settings'
						)
					}
				/>
				{ /* Will be implemented later */ }
				{ /* <ToggleControl
					label={ __( 'Enable Form Analytics', 'sureforms' ) }
					help={ __(
						'Enable this to prevent tracking unique views and submission counts.',
						'sureforms'
					) }
					checked={ generalTabOptions.srfm_form_analytics }
					onChange={ ( value ) =>
						updateGlobalSettings(
							'srfm_form_analytics',
							value,
							'general-settings'
						)
					}
				/> */ }
			</>
		);
	};

	return (
		<>
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
		</>
	);
};

export default GeneralPage;
