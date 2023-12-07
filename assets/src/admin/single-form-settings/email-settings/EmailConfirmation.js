import { __ } from '@wordpress/i18n';
import Editor from './JoditEditor';
import { useState } from '@wordpress/element';

const EmailConfirmation = ({data,handleConfirmEmail}) => {
	const [formData,setFormData]=useState({
		id: data.id || false,
		status:data.status || false,
		name:data.name,
		email_to:data.email_to,
		subject:data.subject,
		email_body:data.email_body,
	});
	const handleOnChangeEmailBodyContent=(newContent)=>{
		setFormData({...formData,email_body:newContent})
	}

	return (
		<div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<span className="srfm-modal-inner-heading-text">
						<h4>{ __( 'Email Notification', 'sureforms' ) }</h4>
					</span>
					<button onClick={()=>handleConfirmEmail(formData)} className="srfm-modal-inner-heading-button">
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
							<input onChange={(e)=>setFormData({...formData,name:e.target.value})} value={formData.name} className="srfm-modal-input" />
						</div>
						<div className="srfm-modal-input-box">
							<label className="srfm-modal-label">{ __( 'Send Email To', 'sureforms' ) }</label>
							<input onChange={(e)=>setFormData({...formData,email_to:e.target.value})} value={formData.email_to} className="srfm-modal-input" />
						</div>
						<div className="srfm-modal-input-box">
							<label className="srfm-modal-label">{ __( 'Subject', 'sureforms' ) }</label>
							<input onChange={(e)=>setFormData({...formData,subject:e.target.value})} value={formData.subject} className="srfm-modal-input" />
						</div>
						<div className="srfm-modal-area-box">
							<div className="srfm-modal-area-header">
								<div className="srfm-modal-area-header-text">
									<p>{ __( 'Email Body', 'sureforms' ) }</p>
								</div>
								<div className="srfm-modal-area-header-checkbox">
									<input className="srfm-modal-checkbox" type="checkbox" />
									<span className="checkbox-text">{ __( 'Send Email as RAW HTML Format', 'sureforms' ) }</span>
								</div>
							</div>
							<div className="srfm-editor-wrap">
								<Editor placeholder={ 'placeholder' } handleEmailBodyContent={handleOnChangeEmailBodyContent} content={formData.email_body} />
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default EmailConfirmation;
