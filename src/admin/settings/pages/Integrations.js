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
			title={ __( 'Unlock Powerful Integrations', 'sureforms' ) }
			description={ __(
				'Connect your forms with popular tools like Google Sheets, Mailchimp, and Brevo. Automatically send leads, save entries to spreadsheets, trigger workflows, and much more—without any manual work.',
				'sureforms'
			) }
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
			containerPadding={ false }
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
