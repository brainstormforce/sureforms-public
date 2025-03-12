import ContentCard from '../components/ContentCard';
import Webhooks from '../components/integrations/webhooks';
import Zapier from '../components/integrations/zapier';

const IntegrationsPage = ( { loading } ) => {
	return (
		<>
			<div className="srfm-card-container">
				<div className="srfm-card-content">
					<ContentCard
						loading={ loading }
						content={ <Webhooks /> }
					/>
				</div>

			</div>
			<div className="srfm-card-container">
				<div className="srfm-card-content">
					<ContentCard
						loading={ loading }
						content={ <Zapier /> }
					/>
				</div>

			</div>
		</>
	);
};

export default IntegrationsPage;
