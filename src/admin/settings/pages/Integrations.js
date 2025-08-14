import { applyFilters } from '@wordpress/hooks';
import ContentCard from '../components/ContentCard';
import Webhooks from '../components/integrations/webhooks';
import Zapier from '../components/integrations/zapier';

const IntegrationsPage = ( { loading } ) => {
	// Apply filter to allow Pro plugin to add additional integrations
	const additionalContent = applyFilters(
		'srfm.settings.integrations.additional.content',
		[]
	);

	return (
		<div className="flex flex-col gap-1 bg-background-secondary rounded-lg p-1">
			<ContentCard loading={ loading } content={ <Webhooks /> } />
			<ContentCard loading={ loading } content={ <Zapier /> } />

			{ /* Additional integrations from Pro plugin */ }
			{ additionalContent.map( ( content, index ) => (
				<ContentCard
					key={ `additional-integration-${ index }` }
					loading={ loading }
					content={ content }
				/>
			) ) }
		</div>
	);
};

export default IntegrationsPage;
