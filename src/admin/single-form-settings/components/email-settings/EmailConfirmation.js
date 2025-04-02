import { __, sprintf } from '@wordpress/i18n';
import Editor from '../QuillEditor';
import { useState, useEffect } from '@wordpress/element';
import SmartTagList from '@Components/misc/SmartTagList';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';
import { useDebouncedCallback } from 'use-debounce';

const EmailConfirmation = ( props ) => {
	const {
		data,
		handleConfirmEmail,
		handleBackNotification,
		setHasValidationErrors,
	} = props;
	const backArrow = parse( svgIcons.leftArrow );
	const [ formData, setFormData ] = useState( {
		id: data.id || false,
		status: data.status || true,
		is_raw_format: data.is_raw_format || false,
		name: data.name || 'New Notification',
		email_to: data.email_to || '',
		subject: data.subject || '',
		email_reply_to: data.email_reply_to || '',
		email_bcc: data.email_bcc || '',
		email_cc: data.email_cc || '',
		email_body: data.email_body || '',
		from_name: data.from_name || '{site_title}',
		from_email: data.from_email || '{admin_email}',
	} );

	const [ prevData, setPrevData ] = useState( {} ); // Previous saved data before making any changes.
	const [ fromEmailWarningMessage, setFromEmailWarningMessage ] = useState( '' );
	const [ showFromEmailWarning, setShowFromEmailWarning ] = useState( false );

	const [ dynamicSubject, setDynamicSubject ] = useState(
		data.subject || ''
	);
	const handleOnChangeEmailBodyContent = ( newContent ) => {
		setFormData( { ...formData, email_body: newContent } );
	};
	useEffect( () => {
		setFormData( { ...formData, subject: dynamicSubject } );
	}, [ dynamicSubject ] );

	const genericSmartTags = window.srfm_block_data?.smart_tags_array
		? Object.entries( window.srfm_block_data.smart_tags_array )
		: [];
	const genericEmailSmartTags = window.srfm_block_data?.smart_tags_array_email
		? Object.entries( window.srfm_block_data.smart_tags_array_email )
		: [];
	const formSmartTags = window.sureforms?.formSpecificSmartTags ?? [];

	const formEmailSmartTags =
		window.sureforms?.formSpecificEmailSmartTags ?? [];

	// Remove the required error class from the input field on change
	const maybeRemoveRequiredError = ( e ) => {
		e.target.classList.remove( 'required-error' );
	};

	// Function to remove the required error class if the condition is met
	const removeErrorClassIfNeeded = ( selector, condition ) => {
		if ( condition ) {
			const inputElement = document.querySelector( selector );
			inputElement?.classList.remove( 'required-error' );
		}
	};

	// if required fields values are changed by smart tags then remove the required error
	useEffect( () => {
		removeErrorClassIfNeeded( '.srfm-modal-email-to', formData.email_to );
		removeErrorClassIfNeeded( '.srfm-modal-subject', dynamicSubject );
	}, [ formData.email_to, dynamicSubject ] );

	// Set previous data one time on component load.
	useEffect( () => {
		setPrevData( formData );
	}, [] );

	const onClickBack = () => {
		if ( handleConfirmEmail( formData ) ) {
			handleBackNotification();
		}
	};

	// On cancel button clicked.
	const onCancel = () => {
		if ( formData.id ) {
			handleConfirmEmail( prevData );
		}
		handleBackNotification();
	};

	const debounced = useDebouncedCallback( ( value ) => {
		if ( ! handleConfirmEmail( value ) ) {
			setHasValidationErrors( true );
		}
	}, 500 );

	useEffect( () => {
		setHasValidationErrors( false );

		if ( formData.id ) {
			/**
			 * Only do autosave, if it is an existing data item.
			 */
			debounced( formData );
		}
	}, [ formData ] );

	const emailHelpText = __( 'Comma separated values are also accepted.', 'sureforms' );

	useEffect( () => {
		const fromEmail = formData.from_email || '';
		const userEnteredUrl = fromEmail.split( '@' )[ 1 ] || '';
		const siteUrl = window?.srfm_block_data?.site_url || '';

		const isValidEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( fromEmail );

		// If value starts with '{', no warning should be displayed. To avoid issue with smart tags.
		if ( fromEmail.startsWith( '{' ) ) {
			setShowFromEmailWarning( false );
		} else if ( fromEmail === '' || ! isValidEmail ) {
			// Show general warning for empty or invalid email
			setShowFromEmailWarning( true );
			setFromEmailWarningMessage( __( "Please enter a valid email address. Your notifications won't be sent if the field is not filled in correctly.", 'sureforms' ) );
		} else if ( userEnteredUrl !== siteUrl ) {
			// Show domain mismatch warning
			setShowFromEmailWarning( true );
			setFromEmailWarningMessage(
				sprintf(
					// Translators: %1$s is the website domain, %2$s is the suggested admin email.
					__(
						"The current 'From Email' address does not match your website domain name (%1$s). This can cause your notification emails to be blocked or marked as spam. Alternately, try using a From Address that matches your website domain (admin@%2$s).",
						'sureforms'
					),
					siteUrl,
					siteUrl
				)
			);
		} else {
			// No warning needed
			setShowFromEmailWarning( false );
		}
	}
	, [ formData.from_email ] );

	return (
		<div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<div
						onClick={ onClickBack }
						className="srfm-modal-inner-heading-text srfm-modal-inner-heading-back-button"
					>
						<span className="srfm-back-btn">{ backArrow }</span>
						<h4>{ __( 'Email Notification', 'sureforms' ) }</h4>
					</div>
					<button
						onClick={ onCancel }
						className="srfm-modal-inner-heading-button"
					>
						{ __( 'Cancel', 'sureforms' ) }
					</button>
				</div>
				<div className="srfm-modal-inner-box">
					<div className="srfm-modal-inner-box-text">
						<h5>{ __( 'Notification Settings', 'sureforms' ) }</h5>
					</div>
					<div className="srfm-modal-separator" />
					<div className="srfm-modal-inner-box-content">
						<div className="srfm-modal-input-box">
							<div className="srfm-modal-label">
								<label htmlFor="srfm-email-notification-name">
									{ __( 'Name', 'sureforms' ) }
								</label>
							</div>
							<input
								id="srfm-email-notification-name"
								onChange={ ( e ) =>
									setFormData( {
										...formData,
										name: e.target.value,
									} )
								}
								value={ formData.name }
								className="srfm-modal-input"
							/>
						</div>
						<div className="srfm-modal-input-box">
							<div className="srfm-modal-label">
								<label htmlFor="srfm-email-notification-to">
									{ __( 'Send Email To', 'sureforms' ) }
								</label>
								<span className="srfm-required"> *</span>
							</div>
							<input
								id="srfm-email-notification-to"
								onChange={ ( e ) => {
									setFormData( {
										...formData,
										email_to: e.target.value,
									} );
									maybeRemoveRequiredError( e );
								} }
								value={ formData.email_to }
								className="srfm-modal-input srfm-modal-email-to"
							/>
							<p className="components-base-control__help">{ emailHelpText }</p>
							<SmartTagList
								tagFor="emailConfirmation.sendEmailTo"
								tagsArray={ [
									{
										tags: formEmailSmartTags,
										label: __(
											'Form input tags',
											'sureforms'
										),
									},
									{
										tags: genericEmailSmartTags,
										label: __(
											'Generic tags',
											'sureforms'
										),
									},
								] }
								setTargetData={ ( tag ) =>
									setFormData( {
										...formData,
										email_to: formData.email_to + tag,
									} )
								}
							/>
						</div>
						<div className="srfm-modal-input-box">
							<div className="srfm-modal-label">
								<label htmlFor="srfm-email-notification-subject">
									{ __( 'Subject', 'sureforms' ) }
								</label>
								<span className="srfm-required"> *</span>
							</div>
							<input
								id="srfm-email-notification-subject"
								onChange={ ( e ) => {
									setDynamicSubject( e.target.value );
									maybeRemoveRequiredError( e );
								} }
								value={ dynamicSubject }
								className="srfm-modal-input with-icon srfm-modal-subject"
							/>

							<SmartTagList
								tagFor="emailConfirmation.Subject"
								tagsArray={ [
									{
										tags: formSmartTags,
										label: __(
											'Form input tags',
											'sureforms'
										),
									},
									{
										tags: genericSmartTags,
										label: __(
											'Generic tags',
											'sureforms'
										),
									},
								] }
								setTargetData={ ( tag ) =>
									setDynamicSubject( dynamicSubject + tag )
								}
							/>
						</div>
						<div className="srfm-modal-area-box">
							<div className="srfm-modal-area-header">
								<div className="srfm-modal-area-header-text">
									<p>{ __( 'Email Body', 'sureforms' ) }</p>
								</div>
							</div>
							<div className="srfm-editor-wrap">
								{ formData.is_raw_format === true ? (
									<textarea
										onChange={ ( e ) =>
											setFormData( {
												...formData,
												email_body: e.target.value,
											} )
										}
										className="srfm-editor-textarea"
									>
										{ formData.email_body }
									</textarea>
								) : (
									<Editor
										handleContentChange={
											handleOnChangeEmailBodyContent
										}
										content={ formData.email_body }
										formData={ formData }
										setFormData={ setFormData }
										allData={ true }
									/>
								) }
							</div>
						</div>
						<div className="srfm-modal-email-advanced-fields">
							<h1 className="srfm-modal-email-advanced-fields-title">
								{ __( 'Advanced Fields', 'sureforms' ) }
							</h1>
							<div className="srfm-modal-input-box"
								style={ {
									marginBottom: '20px',
								} }
							>
								<div className="srfm-modal-label">
									<label htmlFor="srfm-email-notification-from-name">
										{ __( 'From Name', 'sureforms' ) }
									</label>
								</div>
								<input
									id="srfm-email-notification-from-name"
									onChange={ ( e ) =>
										setFormData( {
											...formData,
											from_name: e.target.value,
										} )
									}
									value={ formData.from_name }
									className="srfm-modal-input"
								/>
								<SmartTagList
									tagFor="emailConfirmation.fromName"
									tagsArray={ [
										{
											tags: formSmartTags,
											label: __(
												'Form input tags',
												'sureforms'
											),
										},
										{
											tags: genericSmartTags,
											label: __(
												'Generic tags',
												'sureforms'
											),
										},
									] }
									// setTargetData={ ( tag ) =>
									// 	setDynamicSubject( dynamicSubject + tag )
									// }
									setTargetData={ ( tag ) =>
										setFormData( {
											...formData,
											from_name:
											formData.from_name + tag,
										} )
									}
								/>
							</div>
							<div className="srfm-modal-input-box"
								style={ {
									marginBottom: '10px',
								} }
							>
								<div className="srfm-modal-label">
									<label htmlFor="srfm-email-notification-from-email">
										{ __( 'From Email', 'sureforms' ) }
									</label>
								</div>
								<input
									id="srfm-email-notification-from-email"
									onChange={ ( e ) => {
										setFormData( {
											...formData,
											from_email: e.target.value,
										} );
									} }
									value={ formData.from_email }
									className="srfm-modal-input srfm-modal-from-email"
								/>
								<SmartTagList
									tagFor="emailConfirmation.fromEmail"
									tagsArray={ [
										{
											tags: formEmailSmartTags,
											label: __(
												'Form input tags',
												'sureforms'
											),
										},
										{
											tags: genericEmailSmartTags,
											label: __(
												'Generic tags',
												'sureforms'
											),
										},
									] }
									setTargetData={ ( tag ) =>
										setFormData( {
											...formData,
											from_email:
												formData.from_email + tag,
										} )
									}
								/>
							</div>
							<div className="srfm-modal-input-box"
								style={ {
									display: 'flex',
									alignItems: 'center',
									gap: '10px',
									marginBottom: '20px',
								} }>
								<p className="components-base-control__help">
									{ __(
										'Notifications can only use 1 From Email. Please do not enter multiple addresses. Please check out our ',
										'sureforms'
									) }
									<a
										href="https://sureforms.com/docs/how-to-setup-smtp-for-sureforms/"
										target="_blank"
										rel="noopener noreferrer"
										style={ {
											textDecoration: 'underline',
											color: '#2563EB',
										} }
									>
										{ __(
											'SMTP documentation',
											'sureforms'
										) }
									</a>
									{ __( ' on fixing email delivery issues for more details.', 'sureforms' ) }
								</p>

								{ showFromEmailWarning && <FromEmailWarning
									message={ fromEmailWarningMessage }
								/> }
							</div>
							<div className="srfm-modal-email-advanced-fields-inner">
								<div
									className="srfm-modal-input-box"
									style={ {
										width: '100%',
									} }
								>
									<div className="srfm-modal-label">
										<label htmlFor="srfm-email-notification-cc">
											{ __( 'CC', 'sureforms' ) }
										</label>
									</div>
									<input
										id="srfm-email-notification-cc"
										onChange={ ( e ) =>
											setFormData( {
												...formData,
												email_cc: e.target.value,
											} )
										}
										value={ formData.email_cc }
										className="srfm-modal-input"
									/>
									<p className="components-base-control__help">{ emailHelpText }</p>
									<SmartTagList
										tagFor="emailConfirmation.CC"
										tagsArray={ [
											{
												tags: formEmailSmartTags,
												label: __(
													'Form input tags',
													'sureforms'
												),
											},
											{
												tags: genericEmailSmartTags,
												label: __(
													'Generic tags',
													'sureforms'
												),
											},
										] }
										setTargetData={ ( tag ) =>
											setFormData( {
												...formData,
												email_cc:
													formData.email_cc + tag,
											} )
										}
									/>
								</div>
								<div
									className="srfm-modal-input-box"
									style={ {
										width: '100%',
									} }
								>
									<div className="srfm-modal-label">
										<label htmlFor="srfm-email-notification-bcc">
											{ __( 'BCC', 'sureforms' ) }
										</label>
									</div>
									<input
										id="srfm-email-notification-bcc"
										onChange={ ( e ) =>
											setFormData( {
												...formData,
												email_bcc: e.target.value,
											} )
										}
										value={ formData.email_bcc }
										className="srfm-modal-input"
									/>
									<p className="components-base-control__help">{ emailHelpText }</p>
									<SmartTagList
										tagFor="emailConfirmation.BCC"
										tagsArray={ [
											{
												tags: formEmailSmartTags,
												label: __(
													'Form input tags',
													'sureforms'
												),
											},
											{
												tags: genericEmailSmartTags,
												label: __(
													'Generic tags',
													'sureforms'
												),
											},
										] }
										setTargetData={ ( tag ) =>
											setFormData( {
												...formData,
												email_bcc:
													formData.email_bcc + tag,
											} )
										}
									/>
								</div>
							</div>
							<div className="srfm-modal-input-box">
								<div className="srfm-modal-label">
									<label htmlFor="srfm-email-notification-reply-to">
										{ __( 'Reply To', 'sureforms' ) }
									</label>
								</div>
								<input
									id="srfm-email-notification-reply-to"
									onChange={ ( e ) =>
										setFormData( {
											...formData,
											email_reply_to: e.target.value,
										} )
									}
									value={ formData.email_reply_to }
									className="srfm-modal-input"
								/>
								<p className="components-base-control__help">{ emailHelpText }</p>
								<SmartTagList
									tagFor="emailConfirmation.replyTo"
									tagsArray={ [
										{
											tags: formEmailSmartTags,
											label: __(
												'Form input tags',
												'sureforms'
											),
										},
										{
											tags: genericEmailSmartTags,
											label: __(
												'Generic tags',
												'sureforms'
											),
										},
									] }
									setTargetData={ ( tag ) =>
										setFormData( {
											...formData,
											email_reply_to:
												formData.email_reply_to + tag,
										} )
									}
								/>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

const FromEmailWarning = ( { message } ) => {
	return (
		<div
			style={ {
				display: 'flex',
				alignItems: 'center',
				gap: '8px',
				borderRadius: '8px',
				border: '1px solid  #FEF08A',
				padding: '12px',
				backgroundColor: '#FEFCE8',
				width: '100%',
			} }
		>
			<span style={ {
				height: '20px',
				width: '20px',
			} }>
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M18.1085 14.9999L11.4419 3.33319C11.2965 3.0767 11.0857 2.86335 10.831 2.71492C10.5762 2.56649 10.2867 2.48828 9.99185 2.48828C9.69703 2.48828 9.40748 2.56649 9.15275 2.71492C8.89802 2.86335 8.68722 3.0767 8.54185 3.33319L1.87519 14.9999C1.72825 15.2543 1.65121 15.5431 1.65186 15.837C1.65251 16.1308 1.73083 16.4192 1.87889 16.673C2.02695 16.9269 2.23948 17.137 2.49493 17.2822C2.75039 17.4274 3.03969 17.5025 3.33352 17.4999H16.6669C16.9593 17.4996 17.2465 17.4223 17.4996 17.2759C17.7527 17.1295 17.9629 16.9191 18.1089 16.6658C18.255 16.4125 18.3319 16.1252 18.3318 15.8328C18.3317 15.5404 18.2547 15.2531 18.1085 14.9999Z" stroke="#EAB308" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
					<path d="M10 7.5V10.8333" stroke="#EAB308" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
					<path d="M10 14.168H10.0083" stroke="#EAB308" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
				</svg>
			</span>
			<span
				style={ {
					fontWeight: '400',
					fontSize: '14px',
					lineHeight: '20px',
					color: '#111827',
					width: '100%',
				} }
			>{ message }</span>
		</div>
	);
};

export default EmailConfirmation;
