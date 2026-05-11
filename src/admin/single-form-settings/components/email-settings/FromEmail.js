import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import ModalInputBox from '@Components/force-ui-components/ModalInputBox';
import WarningBox from '@Components/misc/WarningBox';
import { Container } from '@bsf/force-ui';
import {
	getFromEmailWarningMessage,
	renderSureMailRecommendation,
} from '@Utils/emailValidation';

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

	useEffect( () => {
		const warningResult = getFromEmailWarningMessage(
			formData?.from_email
		);

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
					<WarningBox message={ fromEmailWarningMessage } />
				) }
			</Container>
		</>
	);
};

export default FromEmail;
