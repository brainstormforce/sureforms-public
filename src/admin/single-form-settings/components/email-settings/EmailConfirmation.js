import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useDebouncedCallback } from 'use-debounce';
import { Container } from '@bsf/force-ui';
import TabContentWrapper from '@Components/tab-content-wrapper';
import { EmailNotificationFields } from '@Admin/shared-components/email-notification';
import { singleFormEmailOptionsWithFilter } from '@Components/hooks';

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
		from_name: data.from_name || '{site_title}',
		from_email: data.from_email || '{admin_email}',
	} );

	const [ prevData, setPrevData ] = useState( {} ); // Previous saved data before making any changes.
	const [ dynamicSubject, setDynamicSubject ] = useState(
		data.subject || ''
	);

	// Sync subject with formData.
	useEffect( () => {
		setFormData( ( prev ) => ( { ...prev, subject: dynamicSubject } ) );
	}, [ dynamicSubject ] );

	// Remove the required error class from the input field on change.
	const maybeRemoveRequiredError = ( fieldId ) => {
		const queryId =
			fieldId === 'subject'
				? '#srfm-email-notification-subject'
				: '#srfm-email-notification-to';
		const inputField = document.querySelector( queryId );
		if ( inputField ) {
			inputField.classList.remove( 'outline-focus-error-border' );
		}
	};

	// Function to remove the required error class if the condition is met.
	const removeErrorClassIfNeeded = ( selector, condition ) => {
		if ( condition ) {
			const inputElement = document.querySelector( selector );
			inputElement?.classList.remove( 'outline-focus-error-border' );
		}
	};

	// If required fields values are changed by smart tags then remove the required error.
	useEffect( () => {
		removeErrorClassIfNeeded(
			'#srfm-email-notification-to',
			formData.email_to
		);
		removeErrorClassIfNeeded(
			'#srfm-email-notification-subject',
			dynamicSubject
		);
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

	/**
	 * Handle field change from shared component.
	 *
	 * @param {string} key   - Field key
	 * @param {*}      value - New value
	 */
	const handleChange = ( key, value ) => {
		// Handle special cases for required field error removal.
		if ( key === 'email_to' ) {
			maybeRemoveRequiredError( 'email_to' );
		}
		if ( key === 'subject' ) {
			maybeRemoveRequiredError( 'subject' );
			setDynamicSubject( value );
			return; // Subject is synced via useEffect.
		}

		setFormData( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	// Apply filters to the email options.
	// This allows Pro and other extensions to modify the email notification options.
	// Pass formData/setFormData so Pro can inject UI components that
	// read/write notification state (e.g. upload field attachment selection).
	singleFormEmailOptionsWithFilter( [], { ...props, formData, setFormData } );

	// If filters returned custom options, we might need to handle them.
	// For now, use the shared component for standard fields.

	return (
		<TabContentWrapper
			title={ __( 'Email Notifications', 'sureforms' ) }
			actionBtnText={ __( 'Cancel', 'sureforms' ) }
			actionBtnVariant="outline"
			onClickAction={ onCancel }
			onClickBack={ onClickBack }
			shouldShowAutoSaveText={ true }
		>
			<Container direction="column" className="gap-4 px-2">
				<EmailNotificationFields
					context="form"
					values={ formData }
					onChange={ handleChange }
					showNameField={ true }
					showAllDataTag={ true }
					dynamicSubject={ dynamicSubject }
					setDynamicSubject={ setDynamicSubject }
					onValidationError={ setHasValidationErrors }
				/>
			</Container>
		</TabContentWrapper>
	);
};

export default EmailConfirmation;
