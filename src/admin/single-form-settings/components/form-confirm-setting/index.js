
import { __ } from '@wordpress/i18n';
import Editor from '../email-settings/QuillEditor';
import Select from 'react-select';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

const FormConfirmSetting=()=>{
	const sureforms_keys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);
    return (
        <div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<div className="srfm-modal-inner-heading-text">
						<h4>{ __( 'Form Confirmation', 'sureforms' ) }</h4>
					</div>
					<button
						// onClick={ () => handleConfirmEmail( formData ) }
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
						<div className="srfm-modal-option-box">
							<div className="srfm-modal-label">
								<label>
									{ __( 'Confirmation Type', 'sureforms' ) }
								</label>
							</div>
							<div className='srfm-options-wrapper'>
                                <div className='srfm-option'>
                                    <input className='srfm-option-input' type="radio" id="confirm-type-1" name="confirm-type"/>
                                    <label className='srfm-option-text' for="confirm-type-1">Same Page</label>
                                </div>
                                <div className='srfm-option'>
                                    <input className='srfm-option-input' type="radio" id="confirm-type-2" name="confirm-type"/>
                                    <label className='srfm-option-text' for="confirm-type-2">Different Page</label>
                                </div>
                                <div className='srfm-option'>
                                    <input className='srfm-option-input' type="radio" id="confirm-type-3" name="confirm-type"/>
                                    <label className='srfm-option-text' for="confirm-type-3">Custom URL</label>
                                </div>
                            </div>
						</div>
						<div className="srfm-modal-option-box">
							<div className="srfm-modal-label">
								<label>
									{ __( 'Select Page', 'sureforms' ) }
								</label>
							</div>
							<div className='srfm-options-wrapper'>
								<Select
									className='srfm-select-page'
									options={ [
										{ value: "none", label: "Empty" },
										{ value: "left", label: "Open Left" },
										{ value: "right", label: "Open Right" },
										{
										  value: "tilt,left",
										  label: "Tilf and Open Left"
										}] }
									isMulti={false}
								/>
                            </div>
						</div>
                        <div className="srfm-modal-area-box">
							<div className="srfm-modal-area-header">
								<div className="srfm-modal-area-header-text">
									<p>{ __( 'Confirmation Message', 'sureforms' ) }</p>
								</div>
							</div>
							<div className="srfm-editor-wrap">
									<Editor
										// handleEmailBodyContent={
										// 	handleOnChangeEmailBodyContent
										// }
										// content={ formData.email_body }
										// formData={ formData }
										// setFormData={ setFormData }
									/>
							</div>
						</div>
                        <div className="srfm-modal-option-box">
							<div className="srfm-modal-label">
								<label>
									{ __( 'After Form Submission', 'sureforms' ) }
								</label>
							</div>
							<div className='srfm-options-wrapper'>
                                <div className='srfm-option'>
                                    <input className='srfm-option-input' type="radio" id="submission-type-1" name="submission-type"/>
                                    <label className='srfm-option-text' for="submission-type-1">Hide Form</label>
                                </div>
                                <div className='srfm-option'>
                                    <input className='srfm-option-input' type="radio" id="submission-type-2" name="submission-type"/>
                                    <label className='srfm-option-text' for="submission-type-1">Reset Form</label>
                                </div>
                            </div>
						</div>
					</div>
				</div>
			</div>
		</div>
    )
}

export default FormConfirmSetting;