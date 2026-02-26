import { Tabs } from '@bsf/force-ui';
import { applyFilters } from '@wordpress/hooks';
import { useNavigate } from 'react-router-dom';
import usePaymentTabs from '../hooks/usePaymentTabs';
import StripeSettings from './StripeSettings';

/**
 * Payment Methods component
 * Contains payment gateway tabs (Stripe by default, others via filters)
 * @param {Object} props - Payment methods props object
 */
const PaymentMethods = ( props ) => {
	const {
		loading,
		paymentsSettings,
		updateGlobalSettings,
		setPaymentsSettings,
	} = props;

	// Get React Router's navigate function for programmatic navigation
	const navigate = useNavigate();

	// Use the custom hook for tab management
	const { activeTab, availableTabs, changeTab } = usePaymentTabs( navigate );

	/**
	 * Filter: srfm.payment.gateway.panels
	 *
	 * Allows adding payment gateway tab panels.
	 * Business plan uses this filter to add PayPal panel.
	 *
	 * @param {Array}  panels - Array of panel objects with id and render function
	 * @param {Object} props  - Component props to pass to panels
	 *
	 *                        Example usage in Business plan:
	 *                        addFilter('srfm.payment.gateway.panels', 'sureforms-business', (panels, props) => {
	 *                        return [
	 *                        ...panels,
	 *                        {
	 *                        id: 'paypal',
	 *                        render: (renderProps) => <PayPalSettings {...renderProps} />
	 *                        }
	 *                        ];
	 *                        });
	 */
	const tabPanels = applyFilters(
		'srfm.payment.gateway.panels',
		[
			{
				id: 'stripe',
				render: ( renderProps ) => (
					<StripeSettings
						paymentsSettings={ renderProps.paymentsSettings }
						setPaymentsSettings={ renderProps.setPaymentsSettings }
						updateGlobalSettings={
							renderProps.updateGlobalSettings
						}
						loading={ renderProps.loading }
					/>
				),
			},
		],
		{
			paymentsSettings,
			setPaymentsSettings,
			updateGlobalSettings,
			loading,
		}
	);

	// Prepare the props object to pass to render functions
	const panelProps = {
		paymentsSettings,
		setPaymentsSettings,
		updateGlobalSettings,
		loading,
	};

	return (
		<div className="srfm-payment-methods-wrapper">
			{ /* Payment Gateway Tabs */ }
			<Tabs activeItem={ activeTab }>
				<Tabs.Group
					onChange={ ( { value: { slug } } ) => changeTab( slug ) }
					className="mb-6"
					variant="rounded"
				>
					{ availableTabs.map( ( tab ) => (
						<Tabs.Tab
							key={ tab.id }
							slug={ tab.id }
							text={ tab.label }
						/>
					) ) }
				</Tabs.Group>

				{ /* Tab Panels - filtered to allow extensions */ }
				{ tabPanels.map( ( panel ) => (
					<Tabs.Panel key={ panel.id } slug={ panel.id }>
						{ /* Support both legacy component prop and new render function */ }
						{ panel.render
							? panel.render( panelProps )
							: panel.component }
					</Tabs.Panel>
				) ) }
			</Tabs>
		</div>
	);
};

export default PaymentMethods;
