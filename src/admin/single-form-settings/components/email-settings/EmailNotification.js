import { __ } from '@wordpress/i18n';
import EmailConfirmation from './EmailConfirmation';
import { useEffect, useState } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';
import {
	Button,
	Container,
	Switch,
	Title,
	Table,
	Toaster,
	toast,
	Tooltip,
} from '@bsf/force-ui';
import { Files, SquarePen, Trash } from 'lucide-react';

const EmailNotification = ( {
	setHasValidationErrors,
	emailNotificationData,
} ) => {
	const [ showConfirmation, setShowConfirmation ] = useState( false );
	const [ currData, setCurrData ] = useState( [] );
	const [ isPopup, setIsPopup ] = useState( null );
	const { editPost } = useDispatch( editorStore );
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
			const inputField = document.querySelector(
				'#srfm-email-notification-to'
			);
			if ( inputField ) {
				inputField.classList.add( 'outline-focus-error-border' );
			}
			hasError = true;
		}

		if ( ! subject ) {
			const inputField = document.querySelector(
				'#srfm-email-notification-subject'
			);
			if ( inputField ) {
				inputField.classList.add( 'outline-focus-error-border' );
			}
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

	const headerContent = [
		{
			label: __( 'Status', 'sureforms' ),
		},
		{
			label: __( 'Name', 'sureforms' ),
		},
		{
			label: __( 'Subject', 'sureforms' ),
		},
		{
			label: __( 'Actions', 'sureforms' ),
		},
	];

	useEffect( () => {
		function handleClickOutside( ) {
			setIsPopup( null );
		}

		// Bind the event listener
		document.addEventListener( 'mousedown', handleClickOutside );
		return () => {
			// Unbind the event listener on cleanup
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [ isPopup ] );

	if ( showConfirmation ) {
		return (
			<>
				<Toaster
					position="top-right"
					design="stack"
					theme="light"
					autoDismiss={ true }
					dismissAfter={ 5000 }
				/>
				<EmailConfirmation
					setHasValidationErrors={ setHasValidationErrors }
					handleConfirmEmail={ handleUpdateEmailData }
					handleBackNotification={ handleBackNotification }
					data={ currData }
				/>
			</>
		);
	}

	return (
		<div className="space-y-7 pb-8">
			<Toaster
				position="top-right"
				design="stack"
				theme="light"
				autoDismiss={ true }
				dismissAfter={ 5000 }
			/>
			<div className="flex flex-row justify-between items-center">
				<Title
					tag="h4"
					title={ __( 'Email Notification', 'sureforms' ) }
				/>
				<Button
					className=""
					size="md"
					variant="primary"
					onClick={ handleEdit }
				>
					{ __( 'Add Notification', 'sureforms' ) }
				</Button>
			</div>
			<div className="p-6 bg-background-primary rounded-lg shadow-sm">
				<div className="space-y-6">
					<Table>
						<Table.Head>
							{ headerContent.map( ( header, index ) => (
								<Table.HeadCell
									key={ index }
									className={
										index === 3 ? 'text-right' : ''
									}
								>
									{ header.label }
								</Table.HeadCell>
							) ) }
						</Table.Head>
						<Table.Body>
							{ emailNotificationData &&
								emailNotificationData.map( ( el ) => {
									return (
										<Table.Row
											key={ el.id }
											onChangeSelection={ function Ki() {} }
											value={ {
												status: el.status,
												name: el.name,
												subject: el.subject,
											} }
										>
											<Table.Cell>
												<Switch
													aria-label="Switch Element"
													id="switch-element"
													size="sm"
													checked={ el.status }
													onChange={ () => {
														handleToggle( el );
													} }
												/>
											</Table.Cell>
											<Table.Cell>{ el.name }</Table.Cell>
											<Table.Cell>
												{ el.subject }
											</Table.Cell>
											<Table.Cell>
												<Container
													align="center"
													className="gap-2"
													justify="end"
												>
													<Button
														aria-label="Duplicate"
														className="text-icon-secondary hover:text-icon-primary"
														icon={ <Files /> }
														size="xs"
														variant="ghost"
														onClick={ () =>
															handleDuplicate(
																el
															)
														}
													/>
													<Button
														aria-label="Edit"
														className="text-icon-secondary hover:text-icon-primary"
														icon={ <SquarePen /> }
														size="xs"
														variant="ghost"
														onClick={ () =>
															handleEdit( el )
														}
													/>

													<Tooltip
														arrow
														content={
															<div
																className="relative"
																offset={ 20 }
															>
																<p className="text-center my-2 mx-2">
																	{ __(
																		'Are you sure to delete this?',
																		'sureforms'
																	) }
																</p>
																<div className="flex px-4 pb-2 gap-2">
																	<Button
																		onClick={ () =>
																			setIsPopup(
																				null
																			)
																		}
																		className="inline-flex items-center px-2"
																		variant="outline"
																		size="xs"
																	>
																		{ __(
																			'Cancel',
																			'sureforms'
																		) }
																	</Button>
																	<Button
																		onClick={ () =>
																			handleDelete(
																				el
																			)
																		}
																		variant="primary"
																		size="xs"
																		className="inline-flex items-center px-2"
																		destructive
																	>
																		{ __(
																			'Confirm',
																			'sureforms'
																		) }
																	</Button>
																</div>
															</div>
														}
														placement="top"
														triggers={ [
															'click',
															'focus',
														] }
														tooltipPortalId="srfm-settings-container"
														interactive
														className="z-999999"
														variant="light"
														open={ isPopup === el.id }
														setOpen={ () => setIsPopup( el.id ) }
													>
														<Button
															aria-label="Delete"
															className="text-icon-secondary hover:text-icon-primary"
															icon={ <Trash /> }
															size="xs"
															variant="ghost"
															onClick={ () => {
																setIsPopup(
																	el.id
																);
															} }
														/>
													</Tooltip>
												</Container>
											</Table.Cell>
										</Table.Row>
									);
								} ) }
						</Table.Body>
					</Table>
				</div>
			</div>
		</div>
	);
};

export default EmailNotification;
