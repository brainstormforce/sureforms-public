import { __ } from '@wordpress/i18n';
// import Editor from './JoditEditor';
import { useState, useEffect } from '@wordpress/element';
import { generateSmartTagsDropDown } from '@Utils/Helpers';
import { DropdownMenu } from '@wordpress/components';
import svgIcons from '../../../../../images/single-form-logo.json';
import parse from 'html-react-parser';

const EmailConfirmation = ( props ) => {
	const { data, handleConfirmEmail } = props;
	const dropdownIcon = parse( svgIcons.verticalDot );
	const [ formData, setFormData ] = useState( {
		id: data.id || false,
		status: data.status || false,
		is_raw_format: data.is_raw_format || false,
		name: data.name,
		email_to: data.email_to,
		subject: data.subject,
		email_body: data.email_body,
	} );
	const [ dynamicSubject, setDynamicSubject ] = useState( data.subject );
	const handleOnChangeEmailBodyContent = ( newContent ) => {
		setFormData( { ...formData, email_body: newContent } );
	};
	useEffect( () => {
		setFormData( { ...formData, subject: dynamicSubject } );
	}, [ dynamicSubject ] );

	return (
		<div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<span className="srfm-modal-inner-heading-text">
						<h4>{ __( 'Email Notification', 'sureforms' ) }</h4>
					</span>
					<button
						onClick={ () => handleConfirmEmail( formData ) }
						className="srfm-modal-inner-heading-button"
					>
						{ __( 'Save Changes', 'sureforms' ) }
					</button>
				</div>
				<div className="srfm-modal-inner-box">
					<div className="srfm-modal-inner-box-text">
						<h5>{ __( 'Confirmation', 'sureforms' ) }</h5>
					</div>
					<div className="srfm-modal-separator"></div>
					<div className="srfm-modal-inner-box-content">
						<div className="srfm-modal-input-box">
							<label className="srfm-modal-label">
								{ __( 'Name', 'sureforms' ) }
							</label>
							<input
								onChange={ ( e ) =>
									setFormData( {
										...formData,
										name: e.target.value,
									} )
								}
								value={ formData.name }
								className="srfm-modal-input"
							/>
						</div>
						<div className="srfm-modal-input-box">
							<label className="srfm-modal-label">
								{ __( 'Send Email To', 'sureforms' ) }
							</label>
							<input
								onChange={ ( e ) =>
									setFormData( {
										...formData,
										email_to: e.target.value,
									} )
								}
								value={ formData.email_to }
								className="srfm-modal-input"
							/>
						</div>
						<div className="srfm-modal-input-box">
							<label className="srfm-modal-label">
								{ __( 'Subject', 'sureforms' ) }
							</label>
							<input
								onChange={ ( e ) =>
									setDynamicSubject( e.target.value )
								}
								value={ dynamicSubject }
								className="srfm-modal-input with-icon"
							/>
							<DropdownMenu
								icon={ dropdownIcon }
								className="srfm-scroll-dropdown"
								label="Select Shortcodes"
								controls={
									generateSmartTagsDropDown(
										setDynamicSubject,
										dynamicSubject
									)
										? generateSmartTagsDropDown(
												setDynamicSubject,
												dynamicSubject
										  )
										: []
								}
							/>
						</div>
						<div className="srfm-modal-area-box">
							<div className="srfm-modal-area-header">
								<div className="srfm-modal-area-header-text">
									<p>{ __( 'Email Body', 'sureforms' ) }</p>
								</div>
								<div className="srfm-modal-area-header-checkbox">
									<input
										checked={ formData.is_raw_format }
										onChange={ () =>
											setFormData( {
												...formData,
												is_raw_format:
													! formData.is_raw_format,
											} )
										}
										className="srfm-modal-checkbox"
										type="checkbox"
									/>
									<span className="checkbox-text">
										{ __(
											'Send Email as RAW HTML Format',
											'sureforms'
										) }
									</span>
								</div>
							</div>
							<div className="srfm-editor-wrap">
								{ formData.is_raw_format === true ? (
									<textarea
										onChange={ ( e ) =>
											setFormData( {
												...formData,
												email_body: e.target.value,
											} )
										}
										className="srfm-editor-textarea"
									>
										{ formData.email_body }
									</textarea>
								) : // <Editor
								// 	handleEmailBodyContent={
								// 		handleOnChangeEmailBodyContent
								// 	}
								// 	content={ formData.email_body }
								// />
								null }
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default EmailConfirmation;
