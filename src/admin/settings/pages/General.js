import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';

import ContentSection from '../components/ContentSection';

const GeneralPage = ( {
	loading,
	generalTabOptions,
	updateGlobalSettings,
} ) => {
	const miscellaneousContent = () => {
		return (
			<>
				<ToggleControl
					label={ __( 'Enable IP Logging', 'sureforms' ) }
					help={ __(
						"If this option is turned on, the user's IP address will be saved with the form data",
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

	return (
		<ContentSection
			loading={ loading }
			title={ __( 'Miscellaneous', 'sureforms' ) }
			content={ miscellaneousContent() }
		/>
	);
};

export default GeneralPage;
