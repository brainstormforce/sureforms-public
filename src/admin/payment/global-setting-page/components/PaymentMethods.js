import { __ } from '@wordpress/i18n';
import { Tabs } from '@bsf/force-ui';
import { applyFilters } from '@wordpress/hooks';
import usePaymentTabs from '../hooks/usePaymentTabs';
import StripeSettings from './StripeSettings';

/**
 * Payment Methods component
 * Contains payment gateway tabs (Stripe by default, others via filters)
 */
const PaymentMethods = (props) => {
	const {
		loading,
		paymentsSettings,
		updateGlobalSettings,
		setPaymentsSettings,
	} = props;

	// Use the custom hook for tab management
	const { activeTab, availableTabs, changeTab } = usePaymentTabs();

	/**
	 * Filter: srfm.payment.gateway.panels
	 *
	 * Allows adding payment gateway tab panels.
	 * Business plan uses this filter to add PayPal panel.
	 *
	 * @param {Array} panels - Array of panel objects with id and component
	 * @param {Object} props - Component props to pass to panels
	 *
	 * Example usage in Business plan:
	 * addFilter('srfm.payment.gateway.panels', 'sureforms-business', (panels, props) => {
	 *   return [
	 *     ...panels,
	 *     {
	 *       id: 'paypal',
	 *       component: <PayPalSettings {...props} />
	 *     }
	 *   ];
	 * });
	 */
	const tabPanels = applyFilters(
		'srfm.payment.gateway.panels',
		[
			{
				id: 'stripe',
				component: (
					<StripeSettings
						paymentsSettings={paymentsSettings}
						setPaymentsSettings={setPaymentsSettings}
						updateGlobalSettings={updateGlobalSettings}
						loading={loading}
					/>
				)
			}
		],
		{
			paymentsSettings,
			setPaymentsSettings,
			updateGlobalSettings,
			loading
		}
	);

	return (
		<div className="srfm-payment-methods-wrapper">
			{/* Payment Gateway Tabs */}
			<Tabs activeItem={activeTab}>
				<Tabs.Group
					onChange={({ value: { slug } }) => changeTab(slug)}
					className="mb-6"
				>
					{availableTabs.map((tab) => (
						<Tabs.Tab
							key={tab.id}
							slug={tab.id}
							text={tab.label}
						/>
					))}
				</Tabs.Group>

				{/* Tab Panels - filtered to allow extensions */}
				{tabPanels.map((panel) => (
					<Tabs.Panel key={panel.id} slug={panel.id}>
						{panel.component}
					</Tabs.Panel>
				))}
			</Tabs>
		</div>
	);
};

export default PaymentMethods;
