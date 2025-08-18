import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import svg from '@Svg/svgs.json';
import parse from 'html-react-parser';
import ModalInputBox from '@Components/force-ui-components/ModalInputBox';
import { Container } from '@bsf/force-ui';

const FromEmail = ( {
	formData,
	setFormData,
	genericSmartTags,
	genericEmailSmartTags,
	formSmartTags,
	formEmailSmartTags,
} ) => {
	const [ fromEmailWarningMessage, setFromEmailWarningMessage ] =
		useState( '' );

	/**
	 * Validate and show warning message for From Email
	 */
	const validateAndShowFromEmailWarning = () => {
		const fromEmail = formData?.from_email || '';
		const userEnteredUrl = fromEmail?.split( '@' )[ 1 ] || '';
		const url = window?.srfm_block_data?.site_url || '';
		const siteUrl = url.replace( /^www\./, '' );
		const isValidEmail =
			/^[\p{L}\p{N}._%+-]+@[\p{L}\p{N}.-]+\.[\p{L}]{2,}$/u.test(
				fromEmail
			);

		// If value starts with '{', no warning should be displayed. To avoid issue with smart tags.
		let warningMessage = '';

		if ( fromEmail.startsWith( '{' ) && fromEmail.endsWith( '}' ) ) {
			if ( srfm_block_data?.is_suremails_active ) {
				warningMessage = sprintf(
					// Translators: %1$s is the website domain, %2$s is the suggested admin email.
					__(
						"The current 'From Email' address may not match your website domain name (%1$s). This can cause your notification emails to be blocked or marked as spam. Alternately, try using a From Address that matches your website domain (admin@%2$s).",
						'sureforms'
					),
					siteUrl,
					siteUrl
				);
			} else {
				warningMessage = (
					<>
						{ sprintf(
							// Translators: %s is the website domain.
							__(
								"The current 'From Email' address may not match your website domain name (%s). This can cause your notification emails to be blocked or marked as spam. ",
								'sureforms'
							),
							siteUrl
						) }
						{ __(
							'We strongly recommend that you install the free ',
							'sureforms'
						) }
						<a
							href="https://suremails.com?utm_medium=sureforms"
							target="_blank"
							rel="noopener noreferrer"
						>
							SureMail
						</a>
						{ __(
							' plugin! The Setup Wizard makes it easy to fix your emails. ',
							'sureforms'
						) }
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
		} else if ( fromEmail === '' || ! isValidEmail ) {
			warningMessage = __(
				"Please enter a valid email address. Your notifications won't be sent if the field is not filled in correctly.",
				'sureforms'
			);
		} else if ( userEnteredUrl !== siteUrl ) {
			if ( srfm_block_data?.is_suremails_active ) {
				warningMessage = sprintf(
					// Translators: %1$s is the website domain, %2$s is the suggested admin email.
					__(
						"The current 'From Email' address does not match your website domain name (%1$s). This can cause your notification emails to be blocked or marked as spam. Alternately, try using a From Address that matches your website domain (admin@%2$s).",
						'sureforms'
					),
					siteUrl,
					siteUrl
				);
			} else {
				warningMessage = (
					<>
						{ sprintf(
							// Translators: %s is the website domain.
							__(
								"The current 'From Email' address does not match your website domain name (%s). This can cause your notification emails to be blocked or marked as spam. ",
								'sureforms'
							),
							siteUrl
						) }
						{ __(
							'We strongly recommend that you install the free ',
							'sureforms'
						) }
						<a
							href="https://suremails.com?utm_medium=sureforms"
							target="_blank"
							rel="noopener noreferrer"
						>
							SureMail
						</a>
						{ __(
							' plugin! The Setup Wizard makes it easy to fix your emails. ',
							'sureforms'
						) }
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
			warningMessage = undefined;
		}

		setFromEmailWarningMessage( warningMessage );
	};

	useEffect( () => {
		// Validate and show warning message for From Email
		validateAndShowFromEmailWarning();
	}, [ formData ] );

	const fromEmailHelpText = __(
		'Notifications can use only one From Email so please enter a single address.',
		'sureforms'
	);

	return (
		<>
			<ModalInputBox
				label={ __( 'From Name', 'sureforms' ) }
				id="srfm-email-notification-from-name"
				value={ formData?.from_name }
				onChange={ ( e ) =>
					setFormData( {
						...formData,
						from_name: e,
					} )
				}
				required={ false }
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
				tagFor="emailConfirmation.fromName"
				setTargetData={ ( tag ) =>
					setFormData( {
						...formData,
						from_name: formData?.from_name + tag,
					} )
				}
			/>

			<ModalInputBox
				label={ __( 'From Email', 'sureforms' ) }
				id="srfm-email-notification-from-email"
				value={ formData?.from_email }
				onChange={ ( e ) => {
					setFormData( {
						...formData,
						from_email: e.trim(),
					} );
				} }
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
				tagFor="emailConfirmation.fromEmail"
				setTargetData={ ( tag ) =>
					setFormData( {
						...formData,
						from_email: formData?.from_email + tag,
					} )
				}
				helpText={ fromEmailHelpText }
			/>

			<Container direction="column" className="gap-0" align="stretch">
				{ fromEmailWarningMessage && (
					<ModalWarning message={ fromEmailWarningMessage } />
				) }
			</Container>
		</>
	);
};

const ModalWarning = ( { message } ) => {
	return (
		<Container className="w-full p-3 gap-2 border border-solid border-alert-border-warning bg-alert-background-warning">
			<span className="size-5">{ parse( svg?.warning ) }</span>
			<span className="text-sm font-normal">{ message }</span>
		</Container>
	);
};

export default FromEmail;
