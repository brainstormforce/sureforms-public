import { __ } from '@wordpress/i18n';
import Editor from './JoditEditor';
import { useState, useEffect } from '@wordpress/element';
import { generateSmartTagsDropDown } from '@Utils/Helpers';
import {
	DropdownMenu,
} from '@wordpress/components';

const EmailConfirmation = ( props ) => {
	const { data, handleConfirmEmail } = props;
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
					<button onClick={ () => handleConfirmEmail( formData ) } className="srfm-modal-inner-heading-button">
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
							<label className="srfm-modal-label">{ __( 'Name', 'sureforms' ) }</label>
							<input onChange={ ( e ) => setFormData( { ...formData, name: e.target.value } ) } value={ formData.name } className="srfm-modal-input" />
						</div>
						<div className="srfm-modal-input-box">
							<label className="srfm-modal-label">{ __( 'Send Email To', 'sureforms' ) }</label>
							<input onChange={ ( e ) => setFormData( { ...formData, email_to: e.target.value } ) } value={ formData.email_to } className="srfm-modal-input" />
						</div>
						<div className="srfm-modal-input-box">
							<label className="srfm-modal-label">{ __( 'Subject', 'sureforms' ) }</label>
							<input onChange={ ( e ) => setDynamicSubject( e.target.value ) } value={ dynamicSubject } className="srfm-modal-input" />
							<DropdownMenu
								icon={ <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
									<path fillRule="evenodd" clipRule="evenodd" d="M10.5 6C10.5 5.17157 11.1716 4.5 12 4.5C12.8284 4.5 13.5 5.17157 13.5 6C13.5 6.82843 12.8284 7.5 12 7.5C11.1716 7.5 10.5 6.82843 10.5 6ZM10.5 12C10.5 11.1716 11.1716 10.5 12 10.5C12.8284 10.5 13.5 11.1716 13.5 12C13.5 12.8284 12.8284 13.5 12 13.5C11.1716 13.5 10.5 12.8284 10.5 12ZM10.5 18C10.5 17.1716 11.1716 16.5 12 16.5C12.8284 16.5 13.5 17.1716 13.5 18C13.5 18.8284 12.8284 19.5 12 19.5C11.1716 19.5 10.5 18.8284 10.5 18Z" fill="#0F172A" />
							  </svg> }
								className="srfm-scroll-dropdown"
								label="Select Shortcodes"
								controls={
									generateSmartTagsDropDown( setDynamicSubject, dynamicSubject )
										? generateSmartTagsDropDown( setDynamicSubject, dynamicSubject )
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
									<input checked={ formData.is_raw_format } onChange={ () => setFormData( { ...formData, is_raw_format: ! formData.is_raw_format } ) } className="srfm-modal-checkbox" type="checkbox" />
									<span className="checkbox-text">{ __( 'Send Email as RAW HTML Format', 'sureforms' ) }</span>
								</div>
							</div>
							<div className="srfm-editor-wrap">
								{
									formData.is_raw_format === true
										? <textarea onChange={ ( e ) => setFormData( { ...formData, email_body: e.target.value } ) } className="srfm-editor-textarea">{ formData.email_body }</textarea> : <Editor handleEmailBodyContent={ handleOnChangeEmailBodyContent } content={ formData.email_body } />
								}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default EmailConfirmation;
