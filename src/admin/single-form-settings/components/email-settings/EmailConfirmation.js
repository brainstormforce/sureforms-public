import { __ } from '@wordpress/i18n';
import Editor from '../QuillEditor';
import { useState, useEffect } from '@wordpress/element';
import { useDebouncedCallback } from 'use-debounce';
import {
	Button,
	Title,
} from '@bsf/force-ui';
import { ArrowLeft } from 'lucide-react';
import ModalInputBox from './ModalInputBox';

const EmailConfirmation = ( props ) => {
	const {
		data,
		handleConfirmEmail,
		handleBackNotification,
		setHasValidationErrors,
	} = props;
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
	} );

	const [ prevData, setPrevData ] = useState( {} ); // Previous saved data before making any changes.

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
		const queryId =
			e === 'subject'
				? '#srfm-email-notification-subject'
				: '#srfm-email-notification-to';
		const inputField = document.querySelector( queryId );
		if ( inputField ) {
			inputField.classList.remove( 'outline-focus-error-border' );
		}
	};

	// Function to remove the required error class if the condition is met
	const removeErrorClassIfNeeded = ( selector, condition ) => {
		if ( condition ) {
			const inputElement = document.querySelector( selector );
			inputElement?.classList.remove( 'outline-focus-error-border' );
		}
	};

	// if required fields values are changed by smart tags then remove the required error
	useEffect( () => {
		removeErrorClassIfNeeded( '#srfm-email-notification-to', formData.email_to );
		removeErrorClassIfNeeded( '#srfm-email-notification-subject', dynamicSubject );
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

	const emailHelpText = __(
		'Comma separated values are also accepted.',
		'sureforms'
	);

	return (
		<div className="space-y-7 pb-8">
			<div className="flex flex-row justify-between items-center">
				<div onClick={ onClickBack } className="cursor-pointer">
					<Title
						icon={ <ArrowLeft /> }
						iconPosition="left"
						tag="h4"
						title={ __( 'Email Notification', 'sureforms' ) }
					/>
				</div>
				<Button
					size="md"
					variant="outline"
					onClick={ onCancel }
				>
					{ __( 'Cancel', 'sureforms' ) }
				</Button>
			</div>
			<div className="p-6 bg-background-primary rounded-lg shadow-sm">
				<div className="space-y-6">
					<ModalInputBox
						label={ __( 'Name', 'sureforms' ) }
						id="srfm-email-notification-name"
						value={ formData.name }
						onChange={ ( newInput ) =>
							setFormData( {
								...formData,
								name: newInput,
							} )
						}
						showSmartTagList={ false }
						smartTagList={ [
							{
								tags: formSmartTags,
								label: __( 'Form input tags', 'sureforms' ),
							},
							{
								tags: genericSmartTags,
								label: __( 'Generic tags', 'sureforms' ),
							},
						] }
						tagFor="srfm-email-notification-name"
						setTargetData={ ( tag ) =>
							setDynamicSubject( dynamicSubject + tag )
						}
					/>

					<ModalInputBox
						label={ __( 'Send Email To', 'sureforms' ) }
						id="srfm-email-notification-to"
						value={ formData.email_to }
						onChange={ ( e ) => {
							setFormData( {
								...formData,
								email_to: e,
							} );
							maybeRemoveRequiredError( 'email_to' );
						} }
						required={ true }
						helpText={ emailHelpText }
						smartTagList={ [
							{
								tags: formEmailSmartTags,
								label: __( 'Form input tags', 'sureforms' ),
							},
							{
								tags: genericEmailSmartTags,
								label: __( 'Generic tags', 'sureforms' ),
							},
						] }
						tagFor="emailConfirmation.sendEmailTo"
						setTargetData={ ( tag ) =>
							setFormData( {
								...formData,
								email_to: formData.email_to + tag,
							} )
						}
					/>

					<ModalInputBox
						label={ __( 'Subject', 'sureforms' ) }
						id="srfm-email-notification-subject"
						value={ dynamicSubject }
						onChange={ ( e ) => {
							setDynamicSubject( e );
							maybeRemoveRequiredError( 'subject' );
						} }
						required={ true }
						smartTagList={ [
							{
								tags: formSmartTags,
								label: __( 'Form input tags', 'sureforms' ),
							},
							{
								tags: genericSmartTags,
								label: __( 'Generic tags', 'sureforms' ),
							},
						] }
						tagFor="emailConfirmation.Subject"
						setTargetData={ ( tag ) =>
							setDynamicSubject( dynamicSubject + tag )
						}
					/>

					<div>
						<Editor
							handleContentChange={
								handleOnChangeEmailBodyContent
							}
							content={ formData.email_body }
							formData={ formData }
							setFormData={ setFormData }
							allData={ true }
						/>
					</div>

					<div className="flex flex-row gap-2">
						<ModalInputBox
							label={ __( 'CC', 'sureforms' ) }
							id="srfm-email-notification-cc"
							value={ formData.email_cc }
							onChange={ ( e ) =>
								setFormData( {
									...formData,
									email_cc: e,
								} )
							}
							helpText={ emailHelpText }
							required={ false }
							smartTagList={ [
								{
									tags: formEmailSmartTags,
									label: __( 'Form input tags', 'sureforms' ),
								},
								{
									tags: genericEmailSmartTags,
									label: __( 'Generic tags', 'sureforms' ),
								},
							] }
							tagFor="emailConfirmation.CC"
							setTargetData={ ( tag ) =>
								setFormData( {
									...formData,
									email_cc: formData.email_cc + tag,
								} )
							}
						/>
						<ModalInputBox
							label={ __( 'BCC', 'sureforms' ) }
							id="srfm-email-notification-bcc"
							value={ formData.email_bcc }
							onChange={ ( e ) =>
								setFormData( {
									...formData,
									email_bcc: e,
								} )
							}
							required={ false }
							helpText={ emailHelpText }
							smartTagList={ [
								{
									tags: formEmailSmartTags,
									label: __( 'Form input tags', 'sureforms' ),
								},
								{
									tags: genericEmailSmartTags,
									label: __( 'Generic tags', 'sureforms' ),
								},
							] }
							tagFor="emailConfirmation.BCC"
							setTargetData={ ( tag ) =>
								setFormData( {
									...formData,
									email_bcc: formData.email_bcc + tag,
								} )
							}
						/>
					</div>
					<ModalInputBox
						label={ __( 'Reply To', 'sureforms' ) }
						id="srfm-email-notification-reply-to"
						value={ formData.email_reply_to }
						onChange={ ( e ) =>
							setFormData( {
								...formData,
								email_reply_to: e,
							} )
						}
						required={ false }
						helpText={ emailHelpText }
						smartTagList={ [
							{
								tags: formEmailSmartTags,
								label: __( 'Form input tags', 'sureforms' ),
							},
							{
								tags: genericEmailSmartTags,
								label: __( 'Generic tags', 'sureforms' ),
							},
						] }
						tagFor="emailConfirmation.replyTo"
						setTargetData={ ( tag ) =>
							setFormData( {
								...formData,
								email_reply_to: formData.email_reply_to + tag,
							} )
						}
					/>
				</div>
			</div>
		</div>
	);
};

export default EmailConfirmation;
