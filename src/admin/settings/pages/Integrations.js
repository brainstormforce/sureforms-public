import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { Button, Container, Text, Title } from '@bsf/force-ui';
import ContentCard from '../components/ContentCard';
import IntegrationPlaceholder from '@Image/integrations-placeholder.svg';
import { addQueryParam } from '@Utils/Helpers';

const IntegrationsPage = ( { loading } ) => {
	// Apply filter to allow Pro plugin to add additional integrations
	const additionalContent = applyFilters(
		'srfm.settings.integrations.additional.content',
		[]
	);

	return (
		<Container className="flex flex-col items-center justify-center bg-background-primary">
				<img 
					src={ IntegrationPlaceholder } 
					alt={ __( 'Integrations', 'sureforms' ) } 
					className="h-20 w-20 mx-auto"
				/>

			{/* Title */}
			<Title
				tag="h3"
				title={ __( 'Unlock Powerful Integrations', 'sureforms' ) }
				size="md"
				className="text-center text-text-primary"
			/>

			{/* Description Text */}
			<Text 
				size={ 16 } 
				weight={ 400 } 
				color="secondary" 
				className="text-center max-w-2xl"
			>
				{ __(
					'Connect your forms with popular tools like Google Sheets, Mailchimp, and Brevo. Automatically send leads, save entries to spreadsheets, trigger workflows, and much more—without any manual work.',
					'sureforms'
				) }
			</Text>

			{/* Upgrade Button */}
			<Button
				variant="primary"
				size="md"
				onClick={ () => {
					window.open(
						addQueryParam(
							srfm_admin?.pricing_page_url ||
								srfm_admin?.sureforms_pricing_page,
							'global-integrations'
						),
						'_blank',
						'noreferrer'
					)
				} }
			>
				{ __( 'Upgrade Now', 'sureforms' ) }
			</Button>

			{/* Hidden content for now - can be shown when integrations are available */}
			<div className="hidden">
				{/* <ContentCard loading={ loading } content={ <Webhooks /> } /> */}
				{/* <ContentCard loading={ loading } content={ <Zapier /> } /> */}

				{ /* Additional integrations from Pro plugin */ }
				{ additionalContent.map( ( content, index ) => (
					<ContentCard
						key={ `additional-integration-${ index }` }
						loading={ loading }
						content={ content }
					/>
				) ) }
			</div>
		</Container>
	);
};

export default IntegrationsPage;
