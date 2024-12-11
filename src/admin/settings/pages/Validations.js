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
				{ key: 'srfm_url_block_required_text', label: __( 'URL Block Required Error Message', 'sureforms' ) },
				{ key: 'srfm_input_block_required_text', label: __( 'Input Block Required Error Message', 'sureforms' ) },
				{ key: 'srfm_input_block_unique_text', label: __( 'Input Block Unique Error Message', 'sureforms' ) },
				{ key: 'srfm_address_block_required_text', label: __( 'Address Block Required Error Message', 'sureforms' ) },
				{ key: 'srfm_phone_block_required_text', label: __( 'Phone Block Required Error Message', 'sureforms' ) },
				{ key: 'srfm_phone_block_unique_text', label: __( 'Phone Block Unique Error Message', 'sureforms' ) },
				{ key: 'srfm_number_block_required_text', label: __( 'Number Block Required Error Message', 'sureforms' ) },
				{ key: 'srfm_textarea_block_required_text', label: __( 'Textarea Block Required Error Message', 'sureforms' ) },
				{ key: 'srfm_multi_choice_block_required_text', label: __( 'Multiple Choice Block Required Error Message', 'sureforms' ) },
				{ key: 'srfm_checkbox_block_required_text', label: __( 'Checkbox Block Required Error Message', 'sureforms' ) },
				{ key: 'srfm_email_block_required_text', label: __( 'Email Block Required Error Message', 'sureforms' ) },
				{ key: 'srfm_email_block_unique_text', label: __( 'Email Block Unique Error Message', 'sureforms' ) },
				{ key: 'srfm_dropdown_block_required_text', label: __( 'Dropdown Block Required Error Message', 'sureforms' ) },
				{ key: 'srfm_valid_phone_number', label: __( 'Invalid Phone Number Error Message', 'sureforms' ) },
				{ key: 'srfm_valid_url', label: __( 'Invalid URL Error Message', 'sureforms' ) },
				{ key: 'srfm_confirm_email_same', label: __( 'Set Confirmation Email Mismatch Message', 'sureforms' ) },
				{ key: 'srfm_valid_email', label: __( 'Invalid Email Error Message', 'sureforms' ) },
				{ key: 'srfm_input_min_value', label: __( 'Input Min Value Error Message', 'sureforms' ), description: __( '%s represents the minimum input value. For example: “Minimum value is 10.”', 'sureforms' ) },
				{ key: 'srfm_input_max_value', label: __( 'Input Max Value Error Message', 'sureforms' ), description: __( '%s represents the maximum input value. For example: “Maximum value is 100.”', 'sureforms' ) },
				{ key: 'srfm_dropdown_min_selections', label: __( 'Dropdown Min Selections Error Message', 'sureforms' ), description: __( '%s represents the minimum selections needed. For example: “Minimum 2 selections are required.”', 'sureforms' ) },
				{ key: 'srfm_dropdown_max_selections', label: __( 'Dropdown Max Selections Error Message', 'sureforms' ), description: __( '%s represents the maximum selections allowed. For example: “Maximum 4 selections are allowed.”', 'sureforms' ) },
				{ key: 'srfm_multi_choice_min_selections', label: __( 'Multiple Choice Min Selections Error Message', 'sureforms' ), description: __( '%s represents the minimum choices needed. For example: “Minimum 1 selection is required.”', 'sureforms' ) },
				{ key: 'srfm_multi_choice_max_selections', label: __( 'Multiple Choice Max Selections Error Message', 'sureforms' ), description: __( '%s represents the maximum choices allowed. For example: “Maximum 3 selections are allowed.”', 'sureforms' ) },
			]
		);

		/**
		 * Ensures backward compatibility with the Pro version of the software.
		 *
		 * This component is designed to dynamically address certain fields introduced in Pro Version 1.1.0.
		 * It ensures compatibility with features introduced in that version. Note that this component is
		 * planned for deprecation in 3-4 versions after Pro Version 1.1.0.
		 *
		 * @param {string} field - The field key being processed.
		 * @since 1.1.0
		 * @deprecated Planned for removal in future versions after Pro 1.1.0.
		 */
		const handleProCompatibilityInput = ( field ) => {
			let fieldLabel = field
				.replace( 'srfm_', '' )
				.replace( /_/g, ' ' );
			fieldLabel = fieldLabel.replace( /text/g, '' );
			fieldLabel = fieldLabel
				.split( ' ' )
				.map(
					( word ) =>
						word.charAt( 0 ).toUpperCase() + word.slice( 1 )
				)
				.join( ' ' );
			return (
				<TextControl
					key={ field }
					label={ `${
						( fieldLabel === 'Area Block Required '
							? __(
								'Textarea Block Required',
								'sureforms'
							)
							: fieldLabel === 'Url Block Required '
								? __( 'URL Block Required', 'sureforms' )
								: fieldLabel ) +
								__( ' Error Message', 'sureforms' )
					}` }
					type="text"
					className="srfm-components-input-control"
					value={ dynamicBlockOptions[ field ] }
					onChange={ ( value ) => {
						updateGlobalSettings(
							field,
							value,
							'general-settings-dynamic-opt'
						);
					} }
				/>
			);
		};

		return (
			<>
				{ validationFields.map( ( field ) => {
					// Ensure compatibility for validation fields in Pro version.
					// If the key is not available in dynamicBlockOptions, treat it as a Pro-specific field.
					if ( ! field?.key ) {
						return handleProCompatibilityInput( field );
					}

					return (
						<TextControl
							key={ field.key }
							label={ field.label }
							type="text"
							className="srfm-components-input-control"
							value={ dynamicBlockOptions?.[ field.key ] || '' }
							onChange={ ( value ) => {
								updateGlobalSettings(
									field.key,
									value,
									'general-settings-dynamic-opt'
								);
							} }
							help={ field?.description || '' }
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
