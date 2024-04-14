import { __ } from '@wordpress/i18n';
import {
	ToggleControl,
	SelectControl,
	TextareaControl,
	Spinner,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import toast from 'react-hot-toast';

import ContentSection from '../components/ContentSection';

const EmailPage = ( { loading, emailTabOptions, updateGlobalSettings } ) => {
	const EmailSummariesContent = () => {
		const [ sendingTestEmail, setSendingTestEmail ] = useState( false );

		const days = [
			{ label: 'Monday', value: 'Monday' },
			{ label: 'Tuesday', value: 'Tuesday' },
			{ label: 'Wednesday', value: 'Wednesday' },
			{ label: 'Thursday', value: 'Thursday' },
			{ label: 'Friday', value: 'Friday' },
			{ label: 'Saturday', value: 'Saturday' },
			{ label: 'Sunday', value: 'Sunday' },
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
						<TextareaControl
							label={ __( 'Email Send To', 'sureforms' ) }
							type="text"
							className="srfm-components-input-control srfm-col-6"
							value={ emailTabOptions.srfm_email_sent_to }
							onChange={ ( value ) =>
								updateGlobalSettings(
									'srfm_email_sent_to',
									value,
									'email-settings'
								)
							}
						/>
						<SelectControl
							label={ __( 'Schedule Reports', 'sureforms' ) }
							value={ emailTabOptions.srfm_schedule_report }
							className="srfm-components-select-control srfm-col-6"
							onChange={ ( value ) =>
								updateGlobalSettings(
									'srfm_schedule_report',
									value,
									'email-settings'
								)
							}
							options={ days }
						/>
						<button
							className="button button-primary srfm-email-test-btn"
							disabled={ sendingTestEmail }
							onClick={ async () => {
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
							{ __( 'Send a Test Email Summary', 'sureforms' ) }
							{ sendingTestEmail && <Spinner /> }
						</button>
					</>
				) }
			</>
		);
	};

	return (
		<ContentSection
			loading={ loading }
			title={ __( 'Email Summaries', 'sureforms' ) }
			content={ EmailSummariesContent() }
		/>
	);
};

export default EmailPage;
