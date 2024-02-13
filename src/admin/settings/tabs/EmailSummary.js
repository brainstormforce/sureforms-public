import { __ } from '@wordpress/i18n';
// settings icons.
import GeneralIcon from '../settingsIcon.js';
import { useState, Fragment, useEffect } from '@wordpress/element';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import apiFetch from '@wordpress/api-fetch';
import { Spinner } from '@wordpress/components';

const EmailSummary = () => {
	const [ formData, setFormData ] = useState( {
		enable_email_summary: false,
		emails_send_to: sureforms_admin.admin_email,
		schedule_reports: 'Monday',
	} );
	const [ loading, setLoading ] = useState( false );

	useEffect( () => {
		const fetchEmailSummaryOptions = async () => {
			try {
				const response = await apiFetch( {
					path: 'sureforms/v1/get-email-summary-options',
				} );
				setFormData( response );
			} catch ( error ) {
				console.error( 'Error fetching email summary options:', error );
			}
		};

		fetchEmailSummaryOptions();
	}, [] );

	const handleSubmit = async ( e ) => {
		e.preventDefault();
		try {
			setLoading( true );
			await apiFetch( {
				path: 'sureforms/v1/save-email-summaries-options',
				method: 'POST',
				body: JSON.stringify( formData ),
				headers: {
					'content-type': 'application/json',
				},
			} );
			toast.success( __( 'Settings Saved Successfully!', 'sureforms' ), {
				position: 'bottom-right',
				hideProgressBar: true,
			} );
			setLoading( false );
		} catch ( error ) {
			toast.error( __( 'Error Saving Settings!', 'sureforms' ), {
				position: 'bottom-right',
				hideProgressBar: true,
			} );
			console.error( error );
		}
	};

	const handleChange = ( e ) => {
		const { name, value, checked } = e.target;
		setFormData( {
			...formData,
			[ name ]: name === 'enable_email_summary' ? checked : value,
		} );
	};

	return (
		<div className="srfm-flex srfm-justify-center lg:srfm-w-[100%] md:srfm-w-[80%] srfm-w-[70%]">
			<ToastContainer />
			<div className="srfm-w-full srfm-p-8 srfm-bg-[#FBFBFC] srfm-rounded-md srfm-m-4 srfm-h-3/4 srfm-overflow-scroll srfm-shadow-md srfm-mb-8">
				<div
					className="srfm-flex srfm-gap-2 srfm-text-left srfm-text-[17.6px] srfm-text-[#111827] srfm-pb-4"
					style={ {
						borderBottom: '1px solid rgba(229, 231, 235, 1)',
					} }
				>
					<GeneralIcon />
					<span className="srfm-font-semibold">
						{ __( 'Email Summary', 'sureforms' ) }
					</span>
				</div>
				<form onSubmit={ handleSubmit }>
					<div className="srfm-mt-4">
						<div className="srfm-mb-4 srfm-flex srfm-items-start srfm-gap-10">
							<div className="srfm-max-w-[250px]">
								<h3 className="srfm-text-base srfm-font-semibold srfm-text-gray-90">
									{ __(
										'Enable Email Summaries',
										'sureforms'
									) }
								</h3>
							</div>
							<div className="srfm-w-[600px] srfm-mt-4">
								<Fragment>
									<div className="srfm-mb-4 ">
										<label
											htmlFor="srfm-honeypot-checkbox-input"
											className="toggle-button"
										>
											<input
												id="srfm-honeypot-checkbox-input"
												type="checkbox"
												name="enable_email_summary"
												checked={
													formData.enable_email_summary
												}
												onChange={ handleChange }
											/>
											<span className="slider"></span>
										</label>
									</div>
								</Fragment>
							</div>
						</div>
						{ formData.enable_email_summary && (
							<>
								<div className="srfm-mb-4 srfm-flex srfm-items-start srfm-gap-10">
									<div className="srfm-max-w-[250px]">
										<h3 className="srfm-text-base srfm-font-semibold srfm-text-gray-90">
											{ __(
												'Email Send To',
												'sureforms'
											) }
										</h3>
									</div>
									<div className="srfm-w-[600px] srfm-mt-4">
										<textarea
											className="srfm-input srfm-w-full srfm-rounded-md srfm-border-gray-300 srfm-shadow-sm"
											placeholder={ __(
												'Enter Email Addresses Separated by Comma',
												'sureforms'
											) }
											name="emails_send_to"
											value={ formData.emails_send_to }
											onChange={ handleChange }
										/>
									</div>
								</div>
								<div className="srfm-mb-4 srfm-flex srfm-items-start srfm-gap-10">
									<div className="srfm-max-w-[250px]">
										<h3 className="srfm-text-base srfm-font-semibold srfm-text-gray-90">
											{ __(
												'Schedule Reports',
												'sureforms'
											) }
										</h3>
									</div>
									<div className="srfm-w-[600px] srfm-mt-4">
										<select
											id="srfm-select-days"
											className="srfm-input srfm-w-full srfm-rounded-md srfm-border-gray-300 srfm-shadow-sm"
											name="schedule_reports"
											value={ formData.schedule_reports }
											onChange={ handleChange }
										>
											<option value="Monday">
												{ __( 'Monday', 'sureforms' ) }
											</option>
											<option value="Tuesday">
												{ __( 'Tuesday', 'sureforms' ) }
											</option>
											<option value="Wednesday">
												{ __(
													'Wednesday',
													'sureforms'
												) }
											</option>
											<option value="Thursday">
												{ __(
													'Thursday',
													'sureforms'
												) }
											</option>
											<option value="Friday">
												{ __( 'Friday', 'sureforms' ) }
											</option>
											<option value="Saturday">
												{ __(
													'Saturday',
													'sureforms'
												) }
											</option>
											<option value="Sunday">
												{ __( 'Sunday', 'sureforms' ) }
											</option>
										</select>
									</div>
								</div>
							</>
						) }
					</div>
					<button
						type="submit"
						className="button-primary srfm-flex srfm-items-center"
						disabled={ loading }
					>
						{ __( ' Save', 'sureforms' ) }
					</button>
					{ loading && <Spinner /> }
				</form>
			</div>
		</div>
	);
};

export default EmailSummary;
