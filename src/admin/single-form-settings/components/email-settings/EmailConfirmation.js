import { __ } from '@wordpress/i18n';
import Editor from '../QuillEditor';
import { useState, useEffect } from '@wordpress/element';
import SmartTagList from '@Components/misc/SmartTagList';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';

const EmailConfirmation = ( props ) => {
	const { data, handleConfirmEmail, handleBackNotifation } = props;
	const backArrow = parse( svgIcons.leftArrow );
	const [ formData, setFormData ] = useState( {
		id: data.id || false,
		status: data.status || false,
		is_raw_format: data.is_raw_format || false,
		name: data.name || 'New Notification',
		email_to: data.email_to || '',
		subject: data.subject || '',
		email_reply_to: data.email_reply_to || '',
		email_bcc: data.email_bcc || '',
		email_cc: data.email_cc || '',
		email_body: data.email_body || '',
	} );
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

	return (
		<div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<div className="srfm-modal-inner-heading-text">
						<span
							onClick={ handleBackNotifation }
							className="srfm-back-btn"
						>
							{ backArrow }
						</span>
						<h4>{ __( 'Email Notification', 'sureforms' ) }</h4>
					</div>
					<button
						onClick={ () => handleConfirmEmail( formData ) }
						className="srfm-modal-inner-heading-button"
					>
						{ __( 'Save Changes', 'sureforms' ) }
					</button>
				</div>
				<div className="srfm-modal-inner-box">
					<div className="srfm-modal-inner-box-text">
						<h5>{ __( 'Confirmation', 'sureforms' ) }</h5>
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
							<SmartTagList
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
									/>
								) }
							</div>
						</div>
						<div
							className="srfm-modal-email-advanced-fields"

						>
							<h1
								className="srfm-modal-email-advanced-fields-title"
							>{ __( 'Advanced Fields', 'sureforms' ) }</h1>

							<div
								className="srfm-modal-email-advanced-fields-inner"
							>
								<div className="srfm-modal-input-box" style={ {
									width: '100%',
								} }>
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
									<SmartTagList
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
												email_cc: formData.email_cc + tag,
											} )
										}
									/>
								</div>
								<div className="srfm-modal-input-box"

									style={ {
										width: '100%',
									} }>
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
									<SmartTagList
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
												email_bcc: formData.email_bcc + tag,
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
								<SmartTagList
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
				<button
					onClick={ () => handleConfirmEmail( formData ) }
					className="srfm-modal-inner-footer-button"
					style={ {
						alignSelf: 'flex-start',
					} }
				>
					{ __( 'Save Changes', 'sureforms' ) }
				</button>
			</div>
		</div>
	);
};

export default EmailConfirmation;
