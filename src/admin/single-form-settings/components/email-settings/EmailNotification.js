import { __ } from '@wordpress/i18n';
import EmailConfirmation from './EmailConfirmation';
import { useState } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';
import { ToggleControl, Popover } from '@wordpress/components';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';

const EmailNotification = ( {
	setHasValidationErrors,
	emailNotificationData,
	toast,
} ) => {
	const [ showConfirmation, setShowConfirmation ] = useState( false );
	const [ currData, setCurrData ] = useState( [] );
	const [ isPopup, setIsPopup ] = useState( null );
	const { editPost } = useDispatch( editorStore );
	const plusIcons = parse( svgIcons.plus );
	const editIcons = parse( svgIcons.edit );
	const deleteIcons = parse( svgIcons.delete );
	const handleEdit = ( data ) => {
		setShowConfirmation( true );
		setCurrData( data );
	};
	const handleDelete = ( data ) => {
		const filterData = emailNotificationData.filter(
			( el ) => el.id !== data.id
		);
		updateMeta( '_srfm_email_notification', filterData );
		toast.dismiss();
		toast.success(
			__( 'Email Notification deleted successfully.', 'sureforms' ),
			{ duration: 500 }
		);
	};
	const handleDuplicate = ( data ) => {
		const duplicateData = { ...data };
		if ( duplicateData.id ) {
			duplicateData.id = emailNotificationData.length + 1;
		}
		const allData = [ ...emailNotificationData, duplicateData ];
		updateMeta( '_srfm_email_notification', allData );
		toast.dismiss();
		toast.success(
			__( 'Email Notification duplicated successfully.', 'sureforms' ),
			{ duration: 500 }
		);
	};
	const handleUpdateEmailData = ( newData ) => {
		let { email_to, subject } = newData;
		let hasError = false;

		// Trim off the empty blank white spaces for validation.
		email_to = email_to.trim();
		subject = subject.trim();


		if ( ! email_to ) {
			document
				.querySelector( '.srfm-modal-email-to' )
				.classList.add( 'required-error' );
			hasError = true;
		}

		if ( ! subject ) {
			document
				.querySelector( '.srfm-modal-subject' )
				.classList.add( 'required-error' );
			hasError = true;
		}

		if ( hasError ) {
			toast.dismiss();
			toast.error(
				__( 'Please fill out the required field.', 'sureforms' ),
				{ duration: 500 }
			);
			setHasValidationErrors( true );
			return false;
		}

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
		toast.dismiss();
		return true;
	};
	function updateMeta( option, value ) {
		const option_array = {};
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
	}
	const handleToggle = ( data ) => {
		const updatedData = emailNotificationData.map( ( el ) => {
			if ( el.id === data.id ) {
				return { ...el, status: ! el.status };
			}
			return el;
		} );
		updateMeta( '_srfm_email_notification', updatedData );

		toast.dismiss();
		if ( ! data.status ) {
			toast.success(
				__( 'Email Notification enabled successfully.', 'sureforms' ),
				{ duration: 500 }
			);
		} else {
			toast.success(
				__( 'Email Notification disabled successfully.', 'sureforms' ),
				{ duration: 500 }
			);
		}
	};
	const handleBackNotification = () => {
		setShowConfirmation( false );
		setHasValidationErrors( false );
	};
	if ( showConfirmation ) {
		return (
			<EmailConfirmation
				setHasValidationErrors={ setHasValidationErrors }
				handleConfirmEmail={ handleUpdateEmailData }
				handleBackNotification={ handleBackNotification }
				data={ currData }
			/>
		);
	}
	return (
		<div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<span className="srfm-modal-inner-heading-text">
						<h4>{ __( 'Email Notification', 'sureforms' ) }</h4>
					</span>
					<button
						onClick={ handleEdit }
						className="srfm-modal-inner-heading-button"
					>
						{ __( 'Add Notification', 'sureforms' ) }
					</button>
				</div>
				<div className="srfm-modal-inner-box">
					<div className="srfm-modal-inner-box-text">
						<h5>{ __( 'Notification', 'sureforms' ) }</h5>
					</div>
					<div className="srfm-modal-separator" />
					{
						emailNotificationData.length === 0 ? (
							<div className="srfm-empty-data">
								<p>{ __( 'No data', 'sureforms' ) }</p>
							</div>
						) : (
							<div className="srfm-modal-inner-box-table">
								<div className="srfm-modal-table-wrapper">
									<div className="srfm-responsive-table">
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
																		<span>
																			<button onClick={ () => {
																				setIsPopup( el.id );
																			} } className="srfm-cursor-pointer">
																				{ deleteIcons }
																			</button>
																			{
																				isPopup === el.id &&
																					<Popover placement={ 'top' } className="srfm-el-popover" offset={ 20 } noArrow={ false } onFocusOutside={ () => setIsPopup( null ) } >
																						<p className="srfm-popover-text">{ __( 'Are you sure to delete this?', 'sureforms' ) }</p>
																						<div className="srfm-popover-btn">
																							<button onClick={ () => setIsPopup( null ) } className="srfm-cancel-btn popover-btn">{ __( 'Cancel', 'sureforms' ) }</button>
																							<button onClick={ () => handleDelete( el ) } className="srfm-confirm-btn popover-btn">{ __( 'Confirm', 'sureforms' ) }</button>
																						</div>
																					</Popover>
																			}
																		</span>
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
														<p className="srfm-modal-col-text">
															{ __(
																'Status',
																'sureforms'
															) }
														</p>
													</th>
													<th className="srfm-modal-col-second">
														<p className="srfm-modal-col-text">
															{ __(
																'Name',
																'sureforms'
															) }
														</p>
													</th>
													<th className="srfm-modal-col-third">
														<p className="srfm-modal-col-text">
															{ __(
																'Subject',
																'sureforms'
															) }
														</p>
													</th>
													<th className="srfm-modal-col-fourth">
														<p className="srfm-modal-col-text">
															{ __(
																'Action',
																'sureforms'
															) }
														</p>
													</th>
												</tr>
											</tfoot>
										</table>
									</div>
								</div>
							</div>
						)
					}
				</div>
			</div>
		</div>
	);
};

export default EmailNotification;
