import ContentCard from '../components/ContentCard';
import SureTriggers from '../components/integrations/SureTriggers';
import Webhooks from '../components/integrations/webhooks';
import Zapier from '../components/integrations/zapier';

const IntegrationsPage = ( { loading } ) => {
	return (
		<div className="flex flex-col gap-1 bg-background-secondary rounded-lg p-1">
			<ContentCard
				loading={ loading }
				content={ <SureTriggers /> }
			/>
			<ContentCard
				loading={ loading }
				content={ <Webhooks /> }
			/>
			<ContentCard
				loading={ loading }
				content={ <Zapier /> }
			/>
		</div>
	);
};

export default IntegrationsPage;
