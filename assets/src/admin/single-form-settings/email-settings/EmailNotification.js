import { __ } from '@wordpress/i18n';
import EmailConfirmation from './EmailConfirmation';
import { useState } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';
import { ToggleControl } from '@wordpress/components';
import svgIcons from '../../../../../images/single-form-logo.json';
import parse from 'html-react-parser';

const EmailNotification = ( { emailNotificationData } ) => {
	const [ showConfirmation, setShowConfirmation ] = useState( false );
	const [ currData, setCurrData ] = useState( [] );
	const { editPost } = useDispatch( editorStore );
	const plusIcons = parse( svgIcons.plus );
	const editIcons = parse( svgIcons.edit );
	const deleteIcons = parse( svgIcons.delete );
	const handleEdit = ( data ) => {
		setShowConfirmation( true );
		setCurrData( data );
	};
	const handleDelete = ( data ) => {
		const filterData = emailNotificationData.filter( ( el ) => el.id !== data.id );
		updateMeta( '_srfm_email_notification', filterData );
	};
	const handleDuplicate = ( data ) => {
		const duplicateData = { ...data };
		if ( duplicateData.id ) {
			duplicateData.id = emailNotificationData.length + 1;
		}
		const allData = [ ...emailNotificationData, duplicateData ];
		updateMeta( '_srfm_email_notification', allData );
	};
	const handleUpdateEmailData = ( newData ) => {
		let currEmailData = emailNotificationData;
		if ( ! newData.id ) {
			const currId = emailNotificationData.length + 1;
			newData.id = currId;
			currEmailData = [ ...currEmailData, newData ];
		} else {
			currEmailData = currEmailData.map( ( el ) => {
				if ( el.id === newData.id ) {
					return newData;
				}
				return el;
			} );
		}
		updateMeta( '_srfm_email_notification', currEmailData );
	};
	function updateMeta( option, value ) {
		const option_array = {};
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
		setShowConfirmation( false );
	}
	const handleToggle = ( data ) => {
		const updatedData = emailNotificationData.map( ( el ) => {
			if ( el.id === data.id ) {
				return { ...el, status: ! el.status };
			}
			return el;
		} );
		updateMeta( '_srfm_email_notification', updatedData );
	};
	if ( showConfirmation ) {
		return <EmailConfirmation handleConfirmEmail={ handleUpdateEmailData } data={ currData } />;
	}
	return (
		<div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<span className="srfm-modal-inner-heading-text">
						<h4>{ __( 'Email Notification', 'sureforms' ) }</h4>
					</span>
					<button onClick={ handleEdit } className="srfm-modal-inner-heading-button">
						{ __( 'Add Notification', 'sureforms' ) }
					</button>
				</div>
				<div className="srfm-modal-inner-box">
					<div className="srfm-modal-inner-box-text">
						<h5>{ __( 'Notification', 'sureforms' ) }</h5>
					</div>
					<div className="srfm-modal-separator"></div>
					<div className="srfm-modal-inner-box-table">
						<div className="srfm-modal-table-wrapper">
							<table>
								<thead>
									<tr className="srfm-modal-row">
										<th className="srfm-modal-col-first">
											<p className="srfm-modal-col-text">{ __( 'Status', 'sureforms' ) }</p>
										</th>
										<th className="srfm-modal-col-second">
											<p className="srfm-modal-col-text">{ __( 'Name', 'sureforms' ) }</p>
										</th>
										<th className="srfm-modal-col-third">
											<p className="srfm-modal-col-text">{ __( 'Subject', 'sureforms' ) }</p>
										</th>
										<th className="srfm-modal-col-fourth">
											<p className="srfm-modal-col-text">{ __( 'Action', 'sureforms' ) }</p>
										</th>
									</tr>
								</thead>
								<tbody>
									{
										emailNotificationData && emailNotificationData.map( ( el, i ) => {
											return (
												<div key={ el.id } className="srfm-modal-row-body">
													<tr className={ `srfm-modal-row srfm-modal-row-data ${ i % 2 !== 0 ? ' odd' : '' }` }>
														<td className="srfm-modal-col-first">
															<ToggleControl
																checked={ el.status }
																onChange={ () => {
																	handleToggle( el );
																} }
															/>
														</td>
														<td className="srfm-modal-col-second">
															<span>{ el.name }</span>
														</td>
														<td className="srfm-modal-col-third">
															<span>{ el.subject }</span>
														</td>
														<td className="srfm-modal-col-fourth">
															<button onClick={ () => handleDuplicate( el ) } className="srfm-cursor-pointer">
																{ plusIcons }
															</button>
															<button onClick={ () => handleEdit( el ) } className="srfm-cursor-pointer">
																{ editIcons }
															</button>
															<button onClick={ () => handleDelete( el ) } className="srfm-cursor-pointer">
																{ deleteIcons }
															</button>
														</td>
													</tr>
												</div>
											);
										} )
									}
								</tbody>
								<tfoot>
									<tr className="srfm-modal-row">
										<th className="srfm-modal-col-first">
											<p className="srfm-modal-col-text">{ __( 'Status', 'sureforms' ) }</p>
										</th>
										<th className="srfm-modal-col-second">
											<p className="srfm-modal-col-text">{ __( 'Name', 'sureforms' ) }</p>
										</th>
										<th className="srfm-modal-col-third">
											<p className="srfm-modal-col-text">{ __( 'Subject', 'sureforms' ) }</p>
										</th>
										<th className="srfm-modal-col-fourth">
											<p className="srfm-modal-col-text">{ __( 'Action', 'sureforms' ) }</p>
										</th>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default EmailNotification;
