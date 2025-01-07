import { __ } from '@wordpress/i18n';
import { FormToggle } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';

const ConversationalFormSettingsPreview = ( { setHidePopover } ) => {
	const showCoversationalFormSettings = false;
	const conversationalFormSettings = applyFilters(
		'srfm.instant_form_settings.conversational_forms',
		showCoversationalFormSettings,
		setHidePopover
	);

	return (
		<>
			{ false === conversationalFormSettings
				? <>
					<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
						<div className="srfm-conversational-placeholder-label-ctn">
							<label
								htmlFor="srfm-conversational-placeholder-toggle"
								style={ { cursor: 'pointer' } }
							>
								{ __( 'Conversational Layout', 'sureforms' ) }
							</label>
							<span
								className="srfm-conversational-placeholder-label"
							>
								{ __( 'Pro', 'sureforms' ) }
							</span>
						</div>
						<FormToggle
							disabled={ true }
							label=""
							id="srfm-conversational-placeholder-toggle"
						/>
					</div>
				</>
			 : conversationalFormSettings }
		</>
	);
};

export default ConversationalFormSettingsPreview;
