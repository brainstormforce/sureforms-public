import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import IntegrationsInitialState from '@Admin/components/IntegrationsInitialState';
import GoogleMapsImage from '@Image/google-maps.svg';
import { addQueryParam } from '@Utils/Helpers';

const GoogleMapsPage = ( { loading, toast } ) => {
	const freePluginUI = (
		<IntegrationsInitialState
			image={ GoogleMapsImage }
			title={ __(
				'Enable Google Address Autocomplete',
				'sureforms'
			) }
			description={ __(
				'Upgrade to the SureForms Business Plan to add Google-powered address autocomplete with interactive map preview to your forms.',
				'sureforms'
			) }
			features={ [
				__(
					'Auto-suggest addresses as users type for faster, error-free submissions',
					'sureforms'
				),
				__(
					'Show an interactive map preview with draggable pin for precise locations',
					'sureforms'
				),
				__(
					'Automatically populate address fields like city, state, and postal code',
					'sureforms'
				),
			] }
			buttonText={ __( 'Upgrade Now', 'sureforms' ) }
			onButtonClick={ () => {
				window.open(
					addQueryParam(
						srfm_admin?.pricing_page_url ||
							srfm_admin?.sureforms_pricing_page,
						'global-google-maps'
					),
					'_blank',
					'noreferrer'
				);
			} }
			imageClassName="mb-0"
		/>
	);

	const googleMapsUI = applyFilters(
		'srfm.settings.google-maps.page.content',
		freePluginUI,
		loading,
		toast
	);

	return googleMapsUI;
};

export default GoogleMapsPage;
