import { __ } from '@wordpress/i18n';
import { ToggleControl, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

import ContentSection from '../components/ContentSection';

const GeneralPage = ( {
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
				<ToggleControl
					label={ __( 'Enable Form Analytics ', 'sureforms' ) }
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
				/>
				<ToggleControl
					label={ __( 'GDPR Enhancements', 'sureforms' ) }
					help={ __(
						'Enable GDPR related features and enhancements.',
						'sureforms'
					) }
					checked={ generalTabOptions.srfm_gdpr }
					onChange={ ( value ) =>
						updateGlobalSettings(
							'srfm_gdpr',
							value,
							'general-settings'
						)
					}
				/>
			</>
		);
	};

	const validationContent = () => {
		return (
			<>
				<TextControl
					label={ __( 'Required Field', 'sureforms' ) }
					type="text"
					className="srfm-components-input-control"
					value={ dynamicBlockOptions.srfm_url_block_required_text }
					onChange={ ( value ) => {
						updateGlobalSettings(
							'srfm_url_block_required_text',
							value,
							'general-settings-dynamic-opt'
						);
					} }
				/>
			</>
		);
	};

	return (
		<>
			<ContentSection
				title={ __( 'Miscellaneous', 'sureforms' ) }
				content={ miscellaneousContent() }
			/>
			<ContentSection
				title={ __( 'Validations', 'sureforms' ) }
				content={ validationContent() }
			/>
		</>
	);
};

export default GeneralPage;
