import { __ } from '@wordpress/i18n';
import { Input } from '@bsf/force-ui';
import ContentSection from '../components/ContentSection';

const GoogleMapsPage = ( {
	loading,
	googleMapsSettings,
	updateGlobalSettings,
} ) => {
	const ApiKeyContent = () => {
		return (
			<>
				<Input
					size="md"
					label={ __( 'Google Maps API Key', 'sureforms' ) }
					type="text"
					value={ googleMapsSettings.srfm_google_maps_api_key }
					onChange={ ( value ) =>
						updateGlobalSettings(
							'srfm_google_maps_api_key',
							value,
							'google-maps-settings'
						)
					}
					placeholder={ __(
						'Enter your Google Maps API key',
						'sureforms'
					) }
					autoComplete="off"
				/>
				<p className="text-sm text-field-helper mt-1">
					{ __(
						'Required for address autocomplete and map preview in the Address field. Enable the Places API and Maps JavaScript API in your ',
						'sureforms'
					) }
					<a
						href="https://console.cloud.google.com/apis/library"
						target="_blank"
						rel="noopener noreferrer"
						className="text-field-helper"
					>
						{ __( 'Google Cloud Console', 'sureforms' ) }
					</a>
					{ '.' }
				</p>
				<p className="text-sm text-field-helper mt-2">
					{ __(
						"For security, restrict this API key to your site's domain using HTTP referrer restrictions in your ",
						'sureforms'
					) }
					<a
						href="https://console.cloud.google.com/apis/credentials"
						target="_blank"
						rel="noopener noreferrer"
						className="text-field-helper"
					>
						{ __( 'Google Cloud credentials settings', 'sureforms' ) }
					</a>
					{ __(
						'. This prevents unauthorized use of your key from other origins.',
						'sureforms'
					) }
				</p>
			</>
		);
	};

	return (
		<div className="space-y-6">
			<ContentSection
				loading={ loading }
				title={ __( 'Google Maps API Key', 'sureforms' ) }
				content={ ApiKeyContent() }
			/>
		</div>
	);
};

export default GoogleMapsPage;
