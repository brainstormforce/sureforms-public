import { __ } from '@wordpress/i18n';
import { ToggleControl, TextControl } from '@wordpress/components';

import ContentSection from '../components/ContentSection';

const GeneralPage = ( {
	loading,
	generalTabOptions,
	updateGlobalSettings,
	dynamicBlockOptions,
} ) => {
	const miscellaneousContent = () => {
		return (
			<>
				<ToggleControl
					label={ __( 'Enable IP Logging', 'sureforms' ) }
					help={ __(
						"If this option is turned on, the user's IP address will not be saved with the form data",
						'sureforms'
					) }
					checked={ generalTabOptions.srfm_ip_log }
					onChange={ ( value ) =>
						updateGlobalSettings(
							'srfm_ip_log',
							value,
							'general-settings'
						)
					}
				/>
				<ToggleControl
					label={ __( 'Enable Honeypot Security', 'sureforms' ) }
					help={ __(
						'Enable Honeypot Security for better spam protection',
						'sureforms'
					) }
					checked={ generalTabOptions.srfm_honeypot }
					onChange={ ( value ) =>
						updateGlobalSettings(
							'srfm_honeypot',
							value,
							'general-settings'
						)
					}
				/>
				{ /* Will be implemented later */ }
				{ /* <ToggleControl
					label={ __( 'Enable Form Analytics', 'sureforms' ) }
					help={ __(
						'Enable this to prevent tracking unique views and submission counts.',
						'sureforms'
					) }
					checked={ generalTabOptions.srfm_form_analytics }
					onChange={ ( value ) =>
						updateGlobalSettings(
							'srfm_form_analytics',
							value,
							'general-settings'
						)
					}
				/> */ }
			</>
		);
	};

	const validationContent = () => {
		const validationFields = [
			'srfm_url_block_required_text',
			'srfm_input_block_required_text',
			'srfm_input_block_unique_text',
			'srfm_address_block_required_text',
			'srfm_phone_block_required_text',
			'srfm_phone_block_unique_text',
			'srfm_number_block_required_text',
			'srfm_textarea_block_required_text',
			'srfm_multi_choice_block_required_text',
			'srfm_checkbox_block_required_text',
			'srfm_email_block_required_text',
			'srfm_email_block_unique_text',
			'srfm_dropdown_block_required_text',
			// add only when pro version is active.
			'srfm_password_block_required_text',
			'srfm_rating_block_required_text',
			'srfm_date_time_block_required_text',
			'srfm_upload_block_required_text',
		];

		return (
			<>
				{ validationFields.map( ( field ) => {
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
				} ) }
			</>
		);
	};

	return (
		<>
			<ContentSection
				loading={ loading }
				title={ __( 'Miscellaneous', 'sureforms' ) }
				content={ miscellaneousContent() }
			/>
			<ContentSection
				loading={ loading }
				title={ __( 'Validations', 'sureforms' ) }
				content={ validationContent() }
			/>
		</>
	);
};

export default GeneralPage;
