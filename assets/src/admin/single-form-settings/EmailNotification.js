import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import Editor from './JoditEditor';

const EmailNotification = ()=>{
    
    return (
        <div className='srfm-modal-content'>
        <div className='srfm-modal-inner-content'>
            <div className='srfm-modal-inner-heading'>
                <span className='srfm-modal-inner-heading-text'>
                    <h4>{__('Email Notification','sureforms')}</h4>
                </span>
                <button className='srfm-modal-inner-heading-button'>
                    {__('Save Changes','sureforms')}
                </button>
            </div>
            <div className='srfm-modal-inner-box'>
                <div className='srfm-modal-inner-box-text'>
                    <h5>{__('Confirmation','sureforms')}</h5>
                </div>
                <div className='srfm-modal-separator'></div>
                <div className='srfm-modal-inner-box-content'>
                    <div className='srfm-modal-input-box'>
                        <label className='srfm-modal-label'>{__('Name','sureforms')}</label>
                        <input className='srfm-modal-input'/>
                    </div>
                    <div className='srfm-modal-input-box'>
                        <label className='srfm-modal-label'>{__('Send Email To','sureforms')}</label>
                        <input className='srfm-modal-input'/>
                    </div>
                    <div className='srfm-modal-input-box'>
                        <label className='srfm-modal-label'>{__('Subject','sureforms')}</label>
                        <input className='srfm-modal-input'/>
                    </div>
                    <div className='srfm-modal-area-box'>
                        <div className='srfm-modal-area-header'>
                            <div className='srfm-modal-area-header-text'>
                                <p>Email Body</p>
                            </div>
                            <div className='srfm-modal-area-header-checkbox'>
                                <input type='checkbox'/>
                                <p className='checkbox-text'>Send Email as RAW HTML Format</p>
                            </div>
                        </div>
                        <div>
                            {/* <textarea className='srfm-email-editor-wrap' type='textarea'/> */}
                            {/* <CMSEditor/> */}
                            <Editor placeholder={'placeholder'} />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    )
}

export default EmailNotification;