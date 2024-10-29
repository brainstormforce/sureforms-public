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

const EmailPage = ( { loading, emailTabOptions, updateGlobalSettings } ) => {
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
						<TextControl
							label={ __( 'To', 'sureforms' ) }
							help={ __(
								'Comma separated values are also accepted.',
								'sureforms'
							) }
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
						<button
							className="srfm-button-primary srfm-email-test-btn"
							style={ {
								backgroundColor: '#D54407',
							} }
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
										__( 'Error Sending Test Email Summary:', 'sureforms' ),
										error
									);
								}
							} }
						>
							{ __( 'Send a Test Email Summary', 'sureforms' ) }
							{ sendingTestEmail && (
								<Spinner
									style={ {
										margin: '0',
										color: '#FFFFFF',
									} }
								/>
							) }
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
