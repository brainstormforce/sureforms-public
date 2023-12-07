import { __ } from '@wordpress/i18n';
import EmailConfirmation from './EmailConfirmation';
import { useState } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch,useSelect } from '@wordpress/data';

const EmailNotification = ( {emailNotificationData} ) => {
	const [ showConfirmation, setShowConfirmation ] = useState( false );
    const [currData,setCurrData]=useState([]);
    const { editPost } = useDispatch( editorStore );
	const handleEdit = (data) => {
		setShowConfirmation( true );
        setCurrData(data);
	};
    const handleUpdateEmailData=(newData)=>{
        let currEmailData = emailNotificationData;
        if(! newData.id){
            const currId = emailNotificationData.length + 1;
            newData['id'] = currId;
            currEmailData = [...currEmailData,newData];
        } else{
            currEmailData = currEmailData.map((el)=>{
                if(el.id === newData.id){
                    return newData;
                }else{
                    return el
                }
            })
        }
        updateMeta('_srfm_email_notification',currEmailData);
    }
    function updateMeta( option, value ) {
		const option_array = {};
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
        setShowConfirmation( false );
	}
	if ( showConfirmation ) {
		return <EmailConfirmation handleConfirmEmail={handleUpdateEmailData} data={currData} />;
	}
	return (
		<div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<span className="srfm-modal-inner-heading-text">
						<h4>{ __( 'Email Notification', 'sureforms' ) }</h4>
					</span>
					<button className="srfm-modal-inner-heading-button">
						{ __( 'Save Changes', 'sureforms' ) }
					</button>
				</div>
				<div className="srfm-modal-inner-box">
					<div className="srfm-modal-inner-box-text">
						<h5>{ __( 'Notification', 'sureforms' ) }</h5>
						<button onClick={ handleEdit } className="srfm-modal-inner-notification-btn">
							{ __( 'Add Notification', 'sureforms' ) }
						</button>
					</div>
					<div className="srfm-modal-separator"></div>
					<div className="srfm-modal-inner-box-table">
						<div className="srfm-modal-table-wrapper">
							<table>
								<thead>
									<tr className="srfm-modal-row">
										<th className="srfm-modal-col first">
											<p className="srfm-modal-col-text">Status</p>
										</th>
										<th className="srfm-modal-col second">
											<p className="srfm-modal-col-text">Name</p>
										</th>
										<th className="srfm-modal-col third">
											<p className="srfm-modal-col-text">Subject</p>
										</th>
										<th className="srfm-modal-col fourth">
											<p className="srfm-modal-col-text">Action</p>
										</th>
									</tr>
								</thead>
								<div className="srfm-modal-separator"></div>
								<tbody>
									{
										emailNotificationData && emailNotificationData.map( ( el ) => {
											return (
												<div key={el.id} className="srfm-modal-row-body">
													<tr className="srfm-modal-row srfm-modal-row-data">
														<td className="srfm-modal-col first">
															<span>
																<input type="checkbox" checked={ el.status } hidden="hidden" id="email-notification-status" />
																<label className="srfm-status-switch" htmlFor="email-notification-status"></label>
															</span>
														</td>
														<td className="srfm-modal-col second">
															<span>{ el.name }</span>
														</td>
														<td className="srfm-modal-col third">
															<span>{ el.subject }</span>
														</td>
														<td className="srfm-modal-col fourth">
															<button className="srfm-cursor-pointer">
																<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
																	<path d="M8 4V12M12 8L4 8" stroke="#94A3B8" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
																</svg>
															</button>
															<button onClick={ ()=>handleEdit(el) } className="srfm-cursor-pointer">
																<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
																	<g clipPath="url(#clip0_4465_33162)">
																		<path d="M11.2411 2.99111L12.3661 1.86612C12.8543 1.37796 13.6457 1.37796 14.1339 1.86612C14.622 2.35427 14.622 3.14573 14.1339 3.63388L4.55479 13.213C4.20234 13.5654 3.76762 13.8245 3.28993 13.9668L1.5 14.5L2.03319 12.7101C2.17548 12.2324 2.43456 11.7977 2.78701 11.4452L11.2411 2.99111ZM11.2411 2.99111L13 4.74999" stroke="#94A3B8" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
																	</g>
																	<defs>
																		<clipPath id="clip0_4465_33162">
																			<rect width="16" height="16" fill="white" />
																		</clipPath>
																	</defs>
																</svg>
															</button>
															<button className="srfm-cursor-pointer">
																<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
																	<path d="M9.82692 6L9.59615 12M6.40385 12L6.17308 6M12.8184 3.86038C13.0464 3.89481 13.2736 3.93165 13.5 3.97086M12.8184 3.86038L12.1065 13.115C12.0464 13.8965 11.3948 14.5 10.611 14.5H5.38905C4.60524 14.5 3.95358 13.8965 3.89346 13.115L3.18157 3.86038M12.8184 3.86038C12.0542 3.74496 11.281 3.65657 10.5 3.59622M2.5 3.97086C2.72638 3.93165 2.95358 3.89481 3.18157 3.86038M3.18157 3.86038C3.94585 3.74496 4.719 3.65657 5.5 3.59622M10.5 3.59622V2.98546C10.5 2.19922 9.8929 1.54282 9.10706 1.51768C8.73948 1.50592 8.37043 1.5 8 1.5C7.62957 1.5 7.26052 1.50592 6.89294 1.51768C6.1071 1.54282 5.5 2.19922 5.5 2.98546V3.59622M10.5 3.59622C9.67504 3.53247 8.84131 3.5 8 3.5C7.15869 3.5 6.32496 3.53247 5.5 3.59622" stroke="#94A3B8" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
																</svg>
															</button>
														</td>
													</tr>
												</div>
											);
										} )
									}
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default EmailNotification;
