import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';

import ContentSection from '../components/ContentSection';

const ValidationsPage = ( {
	loading,
	updateGlobalSettings,
	dynamicBlockOptions,
} ) => {
	const validationContent = () => {
		const validationFields = applyFilters(
			'srfm.general.tab.validationFields',
			[
				{ key: "srfm_url_block_required_text", label: __("URL Block Required Error Message", "your-text-domain") },
				{ key: "srfm_input_block_required_text", label: __("Input Block Required Error Message", "your-text-domain") },
				{ key: "srfm_input_block_unique_text", label: __("Input Block Unique Error Message", "your-text-domain") },
				{ key: "srfm_address_block_required_text", label: __("Address Block Required Error Message", "your-text-domain") },
				{ key: "srfm_phone_block_required_text", label: __("Phone Block Required Error Message", "your-text-domain") },
				{ key: "srfm_phone_block_unique_text", label: __("Phone Block Unique Error Message", "your-text-domain") },
				{ key: "srfm_number_block_required_text", label: __("Number Block Required Error Message", "your-text-domain") },
				{ key: "srfm_textarea_block_required_text", label: __("Textarea Block Required Error Message", "your-text-domain") },
				{ key: "srfm_multi_choice_block_required_text", label: __("Multi Choice Block Required Error Message", "your-text-domain") },
				{ key: "srfm_checkbox_block_required_text", label: __("Checkbox Block Required Error Message", "your-text-domain") },
				{ key: "srfm_email_block_required_text", label: __("Email Block Required Error Message", "your-text-domain") },
				{ key: "srfm_email_block_unique_text", label: __("Email Block Unique Error Message", "your-text-domain") },
				{ key: "srfm_dropdown_block_required_text", label: __("Dropdown Block Required Error Message", "your-text-domain") },
				{ key: "srfm_valid_phone_number", label: __("Valid Phone Number Error Message", "your-text-domain") },
				{ key: "srfm_valid_url", label: __("Valid URL Error Message", "your-text-domain") },
				{ key: "srfm_confirm_email_same", label: __("Confirm Email Same Error Message", "your-text-domain") },
				{ key: "srfm_valid_email", label: __("Valid Email Error Message", "your-text-domain") },
				{ key: "srfm_confirm_password_same", label: __("Confirm Password Same Error Message", "your-text-domain") },
				{ key: "srfm_input_min_value", label: __("Input Min Value Error Message", "your-text-domain") },
				{ key: "srfm_input_max_value", label: __("Input Max Value Error Message", "your-text-domain") },
				{ key: "srfm_dropdown_min_selections", label: __("Dropdown Min Selections Error Message", "your-text-domain") },
				{ key: "srfm_dropdown_max_selections", label: __("Dropdown Max Selections Error Message", "your-text-domain") },
			]
		);

		return (
			<>
				{ validationFields.map( ( field ) => {
					return (
						<TextControl
							key={ field.key }
							label={ field.label }
							type="text"
							className="srfm-components-input-control"
							value={ dynamicBlockOptions?.[field.key] || '' }
							onChange={ ( value ) => {
								updateGlobalSettings(
									field,
									value,
									'general-settings-dynamic-opt'
								);
							} }
						/>
					);
				} ) }
			</>
		);
	};

	return (
		<ContentSection
			loading={ loading }
			title={ __( 'Validations', 'sureforms' ) }
			content={ validationContent() }
		/>
	);
};

export default ValidationsPage;
