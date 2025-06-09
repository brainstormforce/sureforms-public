import { __ } from '@wordpress/i18n';
import { FormToggle } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import EditorPremiumBadge from '@Admin/components/EditorPremiumBadge';

const ConversationalFormSettingsPreview = ( { setHidePopover } ) => {
	const showCoversationalFormSettings = false;
	const conversationalFormSettings = applyFilters(
		'srfm.instant_form_settings.conversational_forms',
		showCoversationalFormSettings,
		setHidePopover
	);

	return (
		<>
			{ false === conversationalFormSettings ? (
				<>
					<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
						<div className="srfm-conversational-placeholder-label-ctn">
							<label
								htmlFor="srfm-conversational-placeholder-toggle"
								style={ { cursor: 'pointer' } }
							>
								{ __( 'Conversational Layout', 'sureforms' ) }
							</label>
							<EditorPremiumBadge
								tooltipHeading={ __(
									'Unlock Conversational Forms',
									'sureforms'
								) }
								tooltipContent={ __(
									'With the SureForms Pro Plan, you can transform your forms into engaging conversational layouts for a seamless user experience.',
									'sureforms'
								) }
								utmMedium="sureforms_editor"
							/>
						</div>

						<FormToggle
							disabled={ true }
							label=""
							id="srfm-conversational-placeholder-toggle"
						/>
					</div>
				</>
			) : (
				conversationalFormSettings
			) }
		</>
	);
};

export default ConversationalFormSettingsPreview;
