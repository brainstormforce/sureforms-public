import { __ } from '@wordpress/i18n';
import EmailConfirmation from './EmailConfirmation';
import { useEffect, useState, forwardRef } from '@wordpress/element';
import {
	srfmEditFormMeta,
	srfmSaveFormMeta,
} from '@Components/tab-content-wrapper/edit-form-meta';
import {
	Button,
	Container,
	Switch,
	Table,
	Tooltip,
} from '@bsf/force-ui';
import { Copy, PenLine, Trash } from 'lucide-react';
import TabContentWrapper from '@Components/tab-content-wrapper';
import { notify } from '@Utils/notify';
import { applyFilters, doAction } from '@wordpress/hooks';

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

	const getNextUniqueId = () => {
		if ( ! emailNotificationData || emailNotificationData.length === 0 ) {
			return 1;
		}
		const maxId = emailNotificationData.reduce( ( max, el ) => {
			const currentId = parseInt( el?.id, 10 );
			return ! isNaN( currentId ) && currentId > max ? currentId : max;
		}, 0 );
		return maxId + 1;
	};

	const handleEdit = ( data ) => {
		setShowConfirmation( true );
		setCurrData( data );
	};
	const handleDelete = ( data ) => {
		const filterData = emailNotificationData.filter(
			( el ) => el.id !== data.id
		);
		// Commit immediately so the deletion isn't seen as an "unsaved
		// change" — no Save button activation, no unsaved-changes popup.
		srfmSaveFormMeta( '_srfm_email_notification', filterData );

		doAction( 'srfm.emailNotification.deleted', data );

		notify.success( __( 'Email notification deleted!', 'sureforms' ), {
			duration: 500,
		} );
	};
	const handleDuplicate = ( data ) => {
		const duplicateData = { ...data };
		if ( duplicateData.id ) {
			duplicateData.id = getNextUniqueId();
		}
		const allData = [ ...emailNotificationData, duplicateData ];
		updateMeta( '_srfm_email_notification', allData );

		doAction( 'srfm.emailNotification.duplicated', data, duplicateData );

		notify.success( __( 'Email notification duplicated!', 'sureforms' ), {
			duration: 500,
		} );
	};
	const handleUpdateEmailData = ( newData, { silent = false } = {} ) => {
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
			if ( ! silent ) {
				// Branch the toast copy so the user knows exactly which
				// required field is missing.
				const missingEmail = ! email_to;
				const missingSubject = ! subject;
				let message;
				if ( missingEmail && missingSubject ) {
					message = __(
						'Please provide a recipient email address and subject line.',
						'sureforms'
					);
				} else if ( missingEmail ) {
					message = __(
						'Please provide a recipient email address.',
						'sureforms'
					);
				} else {
					message = __(
						'Please provide a subject line.',
						'sureforms'
					);
				}
				notify.error( message, { duration: 500 } );
			}
			setHasValidationErrors( true );
			return false;
		}

		let currEmailData = emailNotificationData;
		if ( ! newData.id ) {
			newData.id = getNextUniqueId();
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
		return true;
	};
	function updateMeta( option, value ) {
		srfmEditFormMeta( option, value );
	}
	const handleToggle = ( data ) => {
		const updatedData = emailNotificationData.map( ( el ) => {
			if ( el.id === data.id ) {
				return { ...el, status: ! el.status };
			}
			return el;
		} );
		// Commit immediately so flipping status isn't seen as an "unsaved
		// change" — it shouldn't activate Save or trip the discard popup.
		// The Switch in the table re-renders from the new data, so the
		// "enabled/disabled successfully" toast is redundant.
		srfmSaveFormMeta( '_srfm_email_notification', updatedData );
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
		// Toaster is mounted once at the dialog level (Dialog.js).
		// `notify.success/.error` calls from this branch route through
		// the store and surface there.
		return (
			<EmailConfirmation
				setHasValidationErrors={ setHasValidationErrors }
				handleConfirmEmail={ handleUpdateEmailData }
				handleBackNotification={ handleBackNotification }
				data={ currData }
			/>
		);
	}

	// hook to make delete, duplicate feature work with conditional logic.
	let attachNecessaryHooks = applyFilters(
		'srfm.emailNotification.loaded',
		[]
	);

	if ( ! attachNecessaryHooks || attachNecessaryHooks.length === 0 ) {
		attachNecessaryHooks = [];
	}

	return (
		<TabContentWrapper
			title={ __( 'Email Notifications', 'sureforms' ) }
			actionBtnText={ __( 'Add Notification', 'sureforms' ) }
			onClickAction={ handleEdit }
			className="gap-2"
			showTitleHelpText={ true }
			titleHelpText={ __(
				'Control email alerts sent to admins or users after a form submission.',
				'sureforms'
			) }
			// The listing view itself has nothing to "save" — toggle and
			// delete are immediate-commit; Add Notification opens the
			// editor sub-view which renders its own Save button. Hide the
			// header Save here so the regression flagged on the prior PR
			// (perma-disabled Save) doesn't appear.
			showSaveButton={ false }
			tabId="email_notification"
		>
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
									className="hover:bg-background-primary"
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
											<Tooltip
												placement="top"
												tooltipPortalId="srfm-settings-container"
												content={ __(
													'Duplicate',
													'sureforms'
												) }
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
											</Tooltip>
											<Tooltip
												placement="top"
												tooltipPortalId="srfm-settings-container"
												content={ __(
													'Edit',
													'sureforms'
												) }
											>
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
											</Tooltip>
											<Tooltip
												placement="top"
												tooltipPortalId="srfm-settings-container"
												content={ __(
													'Delete',
													'sureforms'
												) }
											>
												<span>
													<Tooltip
														arrow
														offset={ 20 }
														content={
															<Container
																direction="column"
																className="gap-2"
															>
																<p className="text-[13px] font-normal">
																	{ __(
																		'Are you sure you want to delete this email notification?',
																		'sureforms'
																	) }
																</p>
																<Container className="gap-3">
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
												</span>
											</Tooltip>
										</Container>
									</Table.Cell>
								</Table.Row>
							);
						} ) }
				</Table.Body>
			</Table>
			{ attachNecessaryHooks.map( ( option ) => option.component ) }
		</TabContentWrapper>
	);
};

export default EmailNotification;
