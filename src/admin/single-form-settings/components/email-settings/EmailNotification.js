import { __ } from '@wordpress/i18n';
import EmailConfirmation from './EmailConfirmation';
import { useEffect, useState, forwardRef } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';
import {
	Button,
	Container,
	Switch,
	Table,
	Toaster,
	toast,
	Tooltip,
} from '@bsf/force-ui';
import { Copy, PenLine, Trash } from 'lucide-react';
import TabContentWrapper from '@Components/tab-content-wrapper';

const CustomButton = forwardRef(
	(
		{
			ariaLabel,
			icon,
			onClick,
			variant = 'ghost',
			classNames = 'text-icon-secondary',
			label = '',
			...props
		},
		ref
	) => {
		return (
			<Button
				aria-label={ ariaLabel }
				className={ classNames }
				variant={ variant }
				size="xs"
				onClick={ onClick }
				icon={ icon }
				ref={ ref }
				{ ...props }
			>
				{ label || '' }
			</Button>
		);
	}
);

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
		function handleClickOutside() {
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
		<TabContentWrapper
			title={ __( 'Email Notifications', 'sureforms' ) }
			actionBtnText={ __( 'Add Notification', 'sureforms' ) }
			onClickAction={ handleEdit }
			className="gap-2"
		>
			<Toaster
				position="top-right"
				design="stack"
				theme="light"
				autoDismiss={ true }
				dismissAfter={ 5000 }
			/>
			<Table className="rounded-md">
				<Table.Head>
					{ headerContent.map( ( header, index ) => (
						<Table.HeadCell
							key={ index }
							className={ index === 3 ? 'text-right' : '' }
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
											aria-label="Email Notification Switch"
											id={ el.id }
											size="sm"
											checked={ el.status }
											onChange={ () => {
												handleToggle( el );
											} }
										/>
									</Table.Cell>
									<Table.Cell>{ el.name }</Table.Cell>
									<Table.Cell>{ el.subject }</Table.Cell>
									<Table.Cell>
										<Container
											align="center"
											className="gap-2"
											justify="end"
										>
											<CustomButton
												ariaLabel={ __(
													'Duplicate',
													'sureforms'
												) }
												icon={
													<Copy className="size-4" />
												}
												onClick={ () =>
													handleDuplicate( el )
												}
											/>
											<CustomButton
												ariaLabel={ __(
													'Edit',
													'sureforms'
												) }
												icon={
													<PenLine className="size-4" />
												}
												onClick={ () =>
													handleEdit( el )
												}
											/>
											<Tooltip
												arrow
												offset={ 20 }
												content={
													<Container
														containerType="flex"
														direction="column"
														className="gap-2"
													>
														<p className="text-[13px] font-normal">
															{ __(
																'Are you sure to delete this?',
																'sureforms'
															) }
														</p>
														<Container
															containerType="flex"
															direction="row"
															className="gap-3"
														>
															<CustomButton
																ariaLabel={ __(
																	'Cancel',
																	'sureforms'
																) }
																onClick={ () =>
																	setIsPopup(
																		null
																	)
																}
																label={ __(
																	'Cancel',
																	'sureforms'
																) }
																variant="outline"
																className="px-3"
															/>
															<CustomButton
																ariaLabel={ __(
																	'Confirm',
																	'sureforms'
																) }
																onClick={ () =>
																	handleDelete(
																		el
																	)
																}
																label={ __(
																	'Confirm',
																	'sureforms'
																) }
																variant="primary"
																className="px-2 ml-2"
																destructive
															/>
														</Container>
													</Container>
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
												setOpen={ () =>
													setIsPopup( el.id )
												}
											>
												<CustomButton
													ariaLabel={ __(
														'Delete',
														'sureforms'
													) }
													icon={
														<Trash className="text-icon-secondary size-4" />
													}
													onClick={ () => {
														setIsPopup( el.id );
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
		</TabContentWrapper>
	);
};

export default EmailNotification;
