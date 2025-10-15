import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import IntegrationsInitialState from '@Admin/components/IntegrationsInitialState';
import NoIntegrationsImage from '@Image/no-integrations.svg';
import { addQueryParam } from '@Utils/Helpers';

const IntegrationsPage = ( { loading } ) => {
	// Default free plugin UI using reusable IntegrationsInitialState component
	const freePluginUI = (
		<IntegrationsInitialState
			image={ NoIntegrationsImage }
			title={ __(
				'Connect Native Integrations with SureForms',
				'sureforms'
			) }
			description={ __(
				'Unlock powerful integrations in the Premium plan to automate your workflows and connect SureForms directly with your favourite tools.',
				'sureforms'
			) }
			features={ [
				__(
					'Send form submissions straight to CRMs, email, and marketing platforms',
					'sureforms'
				),
				__(
					'Automate repetitive tasks with seamless data syncing',
					'sureforms'
				),
				__(
					'Access exclusive native integrations for faster workflows',
					'sureforms'
				),
			] }
			buttonText={ __( 'Upgrade Now', 'sureforms' ) }
			onButtonClick={ () => {
				window.open(
					addQueryParam(
						srfm_admin?.pricing_page_url ||
							srfm_admin?.sureforms_pricing_page,
						'global-integrations'
					),
					'_blank',
					'noreferrer'
				);
			} }
			imageClassName="mb-0"
		/>
	);

	// Apply filter to allow Pro plugin to completely replace the UI
	const integrationsUI = applyFilters(
		'srfm.settings.integrations.page.content',
		freePluginUI,
		loading
	);

	return integrationsUI;
};

export default IntegrationsPage;
