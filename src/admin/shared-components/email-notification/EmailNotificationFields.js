import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import ModalInputBox from '@Components/force-ui-components/ModalInputBox';
import WarningBox from '@Components/misc/WarningBox';
import Editor from '@Admin/single-form-settings/components/QuillEditor';
import ContentSection from '@Admin/settings/components/ContentSection';
import {
	getFromEmailWarningMessage,
	renderSureMailRecommendation,
} from '@Utils/emailValidation';
import { useEmailSmartTags } from './useEmailSmartTags';

/**
 * Shared Email Notification Fields Component
 *
 * Context-aware component for rendering email notification settings.
 * Can be used in both form-level and global settings contexts.
 *
 * @param {Object}   props
 * @param {string}   props.context                - 'form' | 'global'
 * @param {Object}   props.values                 - Current email notification values
 * @param {Function} props.onChange               - Handler: (fieldKey, value) => void
 * @param {boolean}  props.showNameField          - Show name field (form context only)
 * @param {boolean}  props.showAllDataTag         - Show {all_data} in editor (form context only)
 * @param {boolean}  props.loading                - Loading state (global context only)
 * @param {boolean}  props.wrapWithContentSection - Wrap fields in ContentSection (global context)
 * @param {string}   props.dynamicSubject         - External subject state (form context for smart tags)
 * @param {Function} props.setDynamicSubject      - External subject setter (form context)
 */
const EmailNotificationFields = ( {
	context,
	values,
	onChange,
	showNameField = false,
	showAllDataTag = false,
	loading = false,
	wrapWithContentSection = false,
	dynamicSubject,
	setDynamicSubject,
} ) => {
	const [ fromEmailWarningMessage, setFromEmailWarningMessage ] =
		useState( '' );

	// Get smart tags based on context.
	const { smartTags, emailSmartTags, formSmartTags } =
		useEmailSmartTags( context );

	// Use internal subject state if external not provided.
	const [ internalSubject, setInternalSubject ] = useState(
		values?.subject || ''
	);
	const subjectValue =
		dynamicSubject !== undefined ? dynamicSubject : internalSubject;
	const setSubjectValue = setDynamicSubject || setInternalSubject;

	// Sync internal subject with values when values change (for global context).
	useEffect( () => {
		if ( context === 'global' && dynamicSubject === undefined ) {
			setInternalSubject( values?.subject || '' );
		}
	}, [ values?.subject, context, dynamicSubject ] );

	// Validate and show warning message for From Email.
	useEffect( () => {
		const warningResult = getFromEmailWarningMessage( values?.from_email );

		if ( warningResult?.type === 'jsx' ) {
			setFromEmailWarningMessage(
				renderSureMailRecommendation(
					warningResult.siteUrl,
					warningResult.messageType
				)
			);
		} else {
			setFromEmailWarningMessage( warningResult );
		}
	}, [ values?.from_email ] );

	const emailHelpText = __(
		'Comma separated values are also accepted.',
		'sureforms'
	);

	const fromEmailHelpText = __(
		'Notifications can use only one From Email so please enter a single address.',
		'sureforms'
	);

	// Helper to get smart tag handler for appending tags.
	const getSmartTagHandler = ( key, currentValue ) => ( tag ) => {
		onChange( key, ( currentValue || '' ) + tag );
	};

	// Helper for subject smart tag handler.
	const getSubjectSmartTagHandler = () => ( tag ) => {
		setSubjectValue( subjectValue + tag );
	};

	// Handle subject change and sync with parent.
	const handleSubjectChange = ( value ) => {
		setSubjectValue( value );
		onChange( 'subject', value );
	};

	/**
	 * Render a field, optionally wrapped in ContentSection.
	 * @param {string}      title
	 * @param {JSX.Element} content
	 * @param {string}      key
	 */
	const renderField = ( title, content, key ) => {
		if ( wrapWithContentSection ) {
			return (
				<ContentSection
					key={ key }
					loading={ loading }
					title={ title }
					content={ content }
				/>
			);
		}
		return <div key={ key }>{ content }</div>;
	};

	// Build fields array based on context.
	const fields = [];

	// Name field (form context only).
	if ( showNameField ) {
		fields.push( {
			key: 'name',
			title: __( 'Name', 'sureforms' ),
			content: (
				<ModalInputBox
					label={ __( 'Name', 'sureforms' ) }
					id="srfm-email-notification-name"
					value={ values?.name || '' }
					onChange={ ( newInput ) => onChange( 'name', newInput ) }
					showSmartTagList={ false }
					smartTagList={ smartTags }
					tagFor="srfm-email-notification-name"
					setTargetData={ getSubjectSmartTagHandler() }
				/>
			),
		} );
	}

	// Send Email To field.
	fields.push( {
		key: 'email_to',
		title: __( 'Send Email To', 'sureforms' ),
		content: (
			<div className="space-y-4">
				<ModalInputBox
					label={ __( 'Send Email To', 'sureforms' ) }
					id="srfm-email-notification-to"
					value={ values?.email_to || '' }
					onChange={ ( val ) => onChange( 'email_to', val ) }
					required={ true }
					helpText={ emailHelpText }
					smartTagList={ emailSmartTags }
					tagFor={
						context === 'global'
							? 'globalEmailNotification.emailTo'
							: 'emailConfirmation.sendEmailTo'
					}
					setTargetData={ getSmartTagHandler(
						'email_to',
						values?.email_to
					) }
				/>
			</div>
		),
	} );

	// Subject field.
	fields.push( {
		key: 'subject',
		title: __( 'Subject', 'sureforms' ),
		content: (
			<div className="space-y-4">
				<ModalInputBox
					label={ __( 'Subject', 'sureforms' ) }
					id="srfm-email-notification-subject"
					value={ subjectValue }
					onChange={ handleSubjectChange }
					required={ true }
					smartTagList={ smartTags }
					tagFor={
						context === 'global'
							? 'globalEmailNotification.subject'
							: 'emailConfirmation.Subject'
					}
					setTargetData={ getSubjectSmartTagHandler() }
				/>
			</div>
		),
	} );

	// Email Body field.
	fields.push( {
		key: 'email_body',
		title: __( 'Confirmation Message', 'sureforms' ),
		content: (
			<div className="py-2 gap-6">
				<Editor
					handleContentChange={ ( newContent ) =>
						onChange( 'email_body', newContent )
					}
					content={ values?.email_body || '' }
					/* SF-2815 start: forward context so global shows generic tags only. */
					context={ context }
					/* SF-2815 end. */
					{ ...( showAllDataTag && {
						formData: values,
						setFormData: ( newData ) => {
							Object.keys( newData ).forEach( ( key ) => {
								if ( newData[ key ] !== values?.[ key ] ) {
									onChange( key, newData[ key ] );
								}
							} );
						},
						allData: true,
					} ) }
				/>
			</div>
		),
	} );

	// From Name field.
	fields.push( {
		key: 'from_name',
		title: __( 'From Name', 'sureforms' ),
		content: (
			<div className="space-y-4">
				<ModalInputBox
					label={ __( 'From Name', 'sureforms' ) }
					id="srfm-email-notification-from-name"
					value={ values?.from_name || '' }
					onChange={ ( val ) => onChange( 'from_name', val ) }
					required={ false }
					smartTagList={
						context === 'form'
							? [
								{
									tags: formSmartTags,
									label: __(
										'Form input tags',
										'sureforms'
									),
								},
								{
									tags:
											smartTags[ smartTags.length - 1 ]
												?.tags || [],
									label: __(
										'Generic tags',
										'sureforms'
									),
								},
							  ]
							: smartTags
					}
					tagFor={
						context === 'global'
							? 'globalEmailNotification.fromName'
							: 'emailConfirmation.fromName'
					}
					setTargetData={ getSmartTagHandler(
						'from_name',
						values?.from_name
					) }
				/>
			</div>
		),
	} );

	// From Email field with warning.
	fields.push( {
		key: 'from_email',
		title: __( 'From Email', 'sureforms' ),
		content: (
			<div className="space-y-4">
				<ModalInputBox
					label={ __( 'From Email', 'sureforms' ) }
					id="srfm-email-notification-from-email"
					value={ values?.from_email || '' }
					onChange={ ( val ) => onChange( 'from_email', val.trim() ) }
					required={ false }
					helpText={ fromEmailHelpText }
					smartTagList={ emailSmartTags }
					tagFor={
						context === 'global'
							? 'globalEmailNotification.fromEmail'
							: 'emailConfirmation.fromEmail'
					}
					setTargetData={ getSmartTagHandler(
						'from_email',
						values?.from_email
					) }
				/>
				{ fromEmailWarningMessage && (
					<WarningBox message={ fromEmailWarningMessage } />
				) }
			</div>
		),
	} );

	// CC field.
	fields.push( {
		key: 'email_cc',
		title: __( 'CC', 'sureforms' ),
		content: (
			<div className="space-y-4">
				<ModalInputBox
					label={ __( 'CC', 'sureforms' ) }
					id="srfm-email-notification-cc"
					value={ values?.email_cc || '' }
					onChange={ ( val ) => onChange( 'email_cc', val ) }
					required={ false }
					helpText={ emailHelpText }
					smartTagList={ emailSmartTags }
					tagFor={
						context === 'global'
							? 'globalEmailNotification.cc'
							: 'emailConfirmation.CC'
					}
					setTargetData={ getSmartTagHandler(
						'email_cc',
						values?.email_cc
					) }
					labelWithInfoTooltip={ true }
				/>
			</div>
		),
	} );

	// BCC field.
	fields.push( {
		key: 'email_bcc',
		title: __( 'BCC', 'sureforms' ),
		content: (
			<div className="space-y-4">
				<ModalInputBox
					label={ __( 'BCC', 'sureforms' ) }
					id="srfm-email-notification-bcc"
					value={ values?.email_bcc || '' }
					onChange={ ( val ) => onChange( 'email_bcc', val ) }
					required={ false }
					helpText={ emailHelpText }
					smartTagList={ emailSmartTags }
					tagFor={
						context === 'global'
							? 'globalEmailNotification.bcc'
							: 'emailConfirmation.BCC'
					}
					setTargetData={ getSmartTagHandler(
						'email_bcc',
						values?.email_bcc
					) }
					labelWithInfoTooltip={ true }
				/>
			</div>
		),
	} );

	// Reply To field.
	fields.push( {
		key: 'email_reply_to',
		title: __( 'Reply To', 'sureforms' ),
		content: (
			<div className="space-y-4">
				<ModalInputBox
					label={ __( 'Reply To', 'sureforms' ) }
					id="srfm-email-notification-reply-to"
					value={ values?.email_reply_to || '' }
					onChange={ ( val ) => onChange( 'email_reply_to', val ) }
					required={ false }
					helpText={ emailHelpText }
					smartTagList={ emailSmartTags }
					tagFor={
						context === 'global'
							? 'globalEmailNotification.replyTo'
							: 'emailConfirmation.replyTo'
					}
					setTargetData={ getSmartTagHandler(
						'email_reply_to',
						values?.email_reply_to
					) }
					labelWithInfoTooltip={ true }
				/>
			</div>
		),
	} );

	// Render based on context.
	if ( wrapWithContentSection ) {
		return (
			<div className="space-y-6">
				{ fields.map( ( field ) =>
					renderField( field.title, field.content, field.key )
				) }
			</div>
		);
	}

	// Form context: render fields directly.
	return <>{ fields.map( ( field ) => field.content ) }</>;
};

export default EmailNotificationFields;
