import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { Fragment } from '@wordpress/element';
import './webhooks';
import { Button, Label } from '@bsf/force-ui';
import TabContentWrapper from '@Components/tab-content-wrapper';

const Integrations = ( { setSelectedTab } ) => {
	const cards = [
		{
			title: __( 'All Integrations', 'sureforms' ),
			component: <AllIntegrations setSelectedTab={ setSelectedTab } />,
		},
	];
	return (
		<TabContentWrapper
			title={ __( 'Integrations', 'sureforms' ) }
			className="p-4"
		>
			<div className="flex flex-col gap-1 bg-background-secondary rounded-lg p-1">
				{ cards.map( ( cardItem, cardIndex ) => (
					<Fragment key={ cardIndex }>
						{ cardItem.component }
					</Fragment>
				) ) }
			</div>
		</TabContentWrapper>
	);
};

const AllIntegrations = ( { setSelectedTab } ) => {
	const integrationCards = applyFilters(
		'srfm.formSettings.integrations.cards',
		[],
		setSelectedTab
	);
	if ( 0 === integrationCards.length ) {
		return <EnableIntegrations />;
	}
	return <>{ integrationCards.map( ( card ) => card.component ) }</>;
};

const EnableIntegrations = () => {
	return (
		<div className="flex justify-between border border-solid border-border-subtle rounded-lg p-4 bg-background-primary shadow-sm">
			<div>
				<Label tag="p">
					{ __( 'No Integrations Enabled', 'sureforms' ) }
				</Label>
				<Label tag="p">
					{ __(
						'Please enable Integrations from Global Settings.',
						'sureforms'
					) }
				</Label>
			</div>
			<div>
				<Button
					onClick={ () => {
						window.open(
							'admin.php?page=sureforms_form_settings&tab=integration-settings'
						);
					} }
				>
					{ __( 'Enable from Settings', 'sureforms' ) }
				</Button>
			</div>
		</div>
	);
};

export default Integrations;
