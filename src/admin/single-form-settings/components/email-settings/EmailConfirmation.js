import { __, sprintf } from '@wordpress/i18n';
import Editor from '../QuillEditor';
import { useState, useEffect } from '@wordpress/element';
import SmartTagList from '@Components/misc/SmartTagList';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';
import { useDebouncedCallback } from 'use-debounce';
import svg from '@Svg/svgs.json';

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
			setFromEmailWarningMessage();
		} else if ( fromEmail === '' || ! isValidEmail ) {
			// Show general warning for empty or invalid email
			setFromEmailWarningMessage( __( "Please enter a valid email address. Your notifications won't be sent if the field is not filled in correctly.", 'sureforms' ) );
		} else if ( userEnteredUrl !== siteUrl ) {
			// Show domain mismatch warning
			if ( srfm_block_data?.is_suremails_active ) {
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
				setFromEmailWarningMessage(
					<>
			  { sprintf(
							// Translators: %s is the website domain.
							__(
				  "The current 'From Email' address does not match your website domain name (%s). This can cause your notification emails to be blocked or marked as spam. ",
				  'sureforms'
							),
							siteUrl
			  ) }
			  { __( 'We strongly recommend that you install the free ', 'sureforms' ) }
			  <a href="https://suremails.com?utm_medium=sureforms" target="_blank" rel="noopener noreferrer">
				SureMails
			  </a>
			  { __( ' plugin! The Setup Wizard makes it easy to fix your emails. ', 'sureforms' ) }
			  { sprintf(
							// Translators: %s is the website domain.
							__(
				  ' Alternately, try using a From Address that matches your website domain (admin@%s).',
				  'sureforms'
							),
							siteUrl
			  ) }
					</>
		  );
			}
		} else {
			// No warning needed
			setFromEmailWarningMessage();
		}
	}
	, [ formData ] );

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
							<div className="srfm-modal-input-box srfm-modal-from-name-box"
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
									setTargetData={ ( tag ) =>
										setFormData( {
											...formData,
											from_name:
											formData.from_name + tag,
										} )
									}
								/>
							</div>
							<div className="srfm-modal-input-box srfm-modal-from-email-box"
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
							<div className="srfm-modal-input-box srfm-modal-from-email-warning-box">
								<p className="components-base-control__help">
									{
										sprintf(
											// Translators: %1$s is the SMTP documentation link, %2$s is the closing tag.
											__( 'Check out our %1$sSMTP documentation%2$s for more info.', 'sureforms' ),
											'<a href="https://sureforms.com/docs/how-to-setup-smtp-for-sureforms/" target="_blank" rel="noopener noreferrer">',
											'</a>'
										  )
									}
								</p>
								{ fromEmailWarningMessage && <ModalWarning message={ fromEmailWarningMessage } /> }
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

const ModalWarning = ( { message } ) => {
	return (
		<div className="srfm-modal-warning-box">
			<span className="srfm-modal-warning-icon">
				{ parse( svg?.warning ) }
			</span>
			<span className="srfm-modal-warning-text">{ message }</span>
		</div>
	);
};

export default EmailConfirmation;
