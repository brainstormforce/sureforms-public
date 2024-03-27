import ContentCard from '../components/ContentCard';
import Webhooks from '../components/integrations/webhooks';

const IntegrationsPage = ( { loading } ) => {
	return (
		<div className="srfm-card-container">
			<div className="srfm-card-content">
				<ContentCard
					loading={ loading }
					content={ <Webhooks /> }
				/>
			</div>
		</div>
	);
};

export default IntegrationsPage;
