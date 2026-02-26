import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

import ContentSection from '../components/ContentSection';
import { Input, Label, Tabs } from '@bsf/force-ui';

const TABS = [
	{
		label: __( 'Required Error Messages', 'sureforms' ),
		slug: 'required-error-messages',
	},
	{
		label: __( 'Other Error Messages', 'sureforms' ),
		slug: 'other-error-messages',
	},
];

const ValidationsPage = ( {
	loading,
	updateGlobalSettings,
	dynamicBlockOptions,
} ) => {
	const [ activeTab, setActiveTab ] = useState( TABS[ 0 ].slug );

	const validationContent = () => {
		const validationFields = applyFilters(
			'srfm.general.tab.validationFields',
			[
				{
					key: 'srfm_input_block_unique_text',
					label: __( 'Input Field Unique', 'sureforms' ),
				},
				{
					key: 'srfm_email_block_unique_text',
					label: __( 'Email Field Unique', 'sureforms' ),
				},
				{
					key: 'srfm_valid_url',
					label: __( 'Invalid URL', 'sureforms' ),
				},
				{
					key: 'srfm_phone_block_unique_text',
					label: __( 'Phone Field Unique', 'sureforms' ),
				},
				{
					key: 'srfm_valid_phone_number',
					label: __( 'Invalid Field Number Block', 'sureforms' ),
				},
				{
					key: 'srfm_confirm_email_same',
					label: __(
						'Confirmation Email Mismatch Message',
						'sureforms'
					),
				},
				{
					key: 'srfm_valid_email',
					label: __( 'Invalid Email', 'sureforms' ),
				},
				{
					key: 'srfm_input_min_value',
					label: __( 'Number Minimum Value', 'sureforms' ),
					// Translators: %s represents the minimum input value.
					description: __(
						'%s represents the minimum input value. For example: "Minimum value is 10."',
						'sureforms'
					),
				},
				{
					key: 'srfm_input_max_value',
					label: __( 'Number Maximum Value', 'sureforms' ),
					// Translators: %s represents the maximum input value.
					description: __(
						'%s represents the maximum input value. For example: "Maximum value is 100."',
						'sureforms'
					),
				},
				{
					key: 'srfm_dropdown_min_selections',
					label: __( 'Dropdown Minimum Selections', 'sureforms' ),
					// Translators: %s represents the minimum input length.
					description: __(
						'%s represents the minimum selections needed. For example: “Minimum 2 selections are required.”',
						'sureforms'
					),
				},
				{
					key: 'srfm_dropdown_max_selections',
					label: __( 'Dropdown Maximum Selections', 'sureforms' ),
					// Translators: %s represents the maximum input length.
					description: __(
						'%s represents the maximum selections allowed. For example: “Maximum 4 selections are allowed.”',
						'sureforms'
					),
				},
				{
					key: 'srfm_multi_choice_min_selections',
					label: __(
						'Multiple Choice Minimum Selections',
						'sureforms'
					),
					// Translators: %s represents the minimum input length.
					description: __(
						'%s represents the minimum choices needed. For example: “Minimum 1 selection is required.”',
						'sureforms'
					),
				},
				{
					key: 'srfm_multi_choice_max_selections',
					label: __(
						'Multiple Choice Maximum Selections',
						'sureforms'
					),
					// Translators: %s represents the maximum input length.
					description: __(
						'%s represents the maximum choices allowed. For example: “Maximum 3 selections are allowed.”',
						'sureforms'
					),
				},
			]
		);

		const requiredValidationFields = applyFilters(
			'srfm.general.tab.requiredValidationFields',
			[
				{
					key: 'srfm_input_block_required_text',
					label: __( 'Input Field', 'sureforms' ),
				},
				{
					key: 'srfm_email_block_required_text',
					label: __( 'Email Field', 'sureforms' ),
				},
				{
					key: 'srfm_url_block_required_text',
					label: __( 'URL Field', 'sureforms' ),
				},
				{
					key: 'srfm_phone_block_required_text',
					label: __( 'Phone Field', 'sureforms' ),
				},
				{
					key: 'srfm_textarea_block_required_text',
					label: __( 'Textarea Field', 'sureforms' ),
				},
				{
					key: 'srfm_checkbox_block_required_text',
					label: __( 'Checkbox Field', 'sureforms' ),
				},
				{
					key: 'srfm_dropdown_block_required_text',
					label: __( 'Dropdown Field', 'sureforms' ),
				},
				{
					key: 'srfm_multi_choice_block_required_text',
					label: __( 'Multiple Choice Field', 'sureforms' ),
				},
				{
					key: 'srfm_address_block_required_text',
					label: __( 'Address Field', 'sureforms' ),
				},
				{
					key: 'srfm_number_block_required_text',
					label: __( 'Number Field', 'sureforms' ),
				},
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
			let fieldLabel = field.replace( 'srfm_', '' ).replace( /_/g, ' ' );
			fieldLabel = fieldLabel.replace( /text/g, '' );
			fieldLabel = fieldLabel
				.split( ' ' )
				.map(
					( word ) => word.charAt( 0 ).toUpperCase() + word.slice( 1 )
				)
				.join( ' ' );
			return (
				<Input
					key={ field }
					label={ `${
						( fieldLabel === 'Area Field'
							? __( 'Textarea Field', 'sureforms' )
							: fieldLabel === 'Url Field'
								? __( 'URL Field', 'sureforms' )
								: fieldLabel ) + __( ' Error Message', 'sureforms' )
					}` }
					type="text"
					size="md"
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
				{ ( activeTab === 'other-error-messages'
					? validationFields
					: requiredValidationFields
				).map( ( field ) => {
					// Ensure compatibility for validation fields in Pro version.
					// If the key is not available in dynamicBlockOptions, treat it as a Pro-specific field.
					if ( ! field?.key ) {
						return handleProCompatibilityInput( field );
					}

					return (
						<div key={ field.key } className="space-y-2">
							<Input
								label={ field.label }
								type="text"
								size="md"
								value={
									dynamicBlockOptions?.[ field.key ] || ''
								}
								onChange={ ( value ) => {
									updateGlobalSettings(
										field.key,
										value,
										'general-settings-dynamic-opt'
									);
								} }
							/>
							{ field?.description && (
								<Label tag="p" size="xs" variant="help">
									{ field?.description }
								</Label>
							) }
						</div>
					);
				} ) }
			</>
		);
	};

	return (
		<Tabs activeItem={ activeTab }>
			<Tabs.Group
				variant="rounded"
				onChange={ ( { value: { slug } } ) => setActiveTab( slug ) }
			>
				{ TABS.map( ( tab ) => (
					<Tabs.Tab
						key={ tab.label }
						slug={ tab.slug }
						text={ tab.label }
					/>
				) ) }
			</Tabs.Group>
			<Tabs.Panel slug={ activeTab }>
				<div className="mt-6">
					<ContentSection
						loading={ loading }
						title={ __( 'Validations', 'sureforms' ) }
						content={ validationContent( activeTab ) }
					/>
				</div>
			</Tabs.Panel>
		</Tabs>
	);
};

export default ValidationsPage;
