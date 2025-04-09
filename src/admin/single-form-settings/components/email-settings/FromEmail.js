import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import SmartTagList from '@Components/misc/SmartTagList';
import svg from '@Svg/svgs.json';
import parse from 'html-react-parser';

const FromEmail = ( {	formData,
	setFormData,
	genericSmartTags,
	genericEmailSmartTags,
	formSmartTags,
	formEmailSmartTags } ) => {
	const [ fromEmailWarningMessage, setFromEmailWarningMessage ] = useState( '' );

	/**
	 * Validate and show warning message for From Email
	 */
	const validateAndShowFromEmailWarning = () => {
		const fromEmail = formData.from_email || '';
		const userEnteredUrl = fromEmail.split( '@' )[ 1 ] || '';
		const siteUrl = window?.srfm_block_data?.site_url || '';
		const isValidEmail = /^[\p{L}\p{N}._%+-]+@[\p{L}\p{N}.-]+\.[\p{L}]{2,}$/u.test( fromEmail );

		// If value starts with '{', no warning should be displayed. To avoid issue with smart tags.
		if ( fromEmail.startsWith( '{' ) && fromEmail.endsWith( '}' ) ) {
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
	};

	useEffect(
		() => {
			// Validate and show warning message for From Email
			validateAndShowFromEmailWarning();
		}
		, [ formData ] );

	return (
		<>
			<div className="srfm-modal-input-box srfm-modal-from-name-box">
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
							from_email: e.target.value.trim(),
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
						__( 'Notifications can only use 1 From Email. Please do not enter multiple addresses.', 'sureforms' )
					}
				</p>
				{ fromEmailWarningMessage && <ModalWarning message={ fromEmailWarningMessage } /> }
			</div>
		</>
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

export default FromEmail;
