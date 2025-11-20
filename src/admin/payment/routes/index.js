import { HashRouter, Routes, Route } from 'react-router-dom';
import PaymentListingPage from './PaymentListingPage';
import PaymentDetailPage from './PaymentDetailPage';
import RootLayout from './RootLayout';
import PaymentLayout from './PaymentLayout';

/**
 * AppRouter Component
 * Router component with hash routing to avoid conflicts with WordPress admin page parameter
 *
 * URL Structure:
 * - Listing: ?page=sureforms_payments#/?page=2&per_page=20
 * - Payment Detail: ?page=sureforms_payments#/payment/123
 * - Subscription Detail: ?page=sureforms_payments#/payment/456?type=subscription
 */
export const AppRouter = () => {
	return (
		<HashRouter
			basename="/"
			future={ {
				v7_startTransition: true,
				v7_relativeSplatPath: true,
			} }
		>
			<Routes>
				{ /* Listing page with RootLayout */ }
				<Route path="/" element={ <RootLayout /> }>
					<Route index element={ <PaymentListingPage /> } />
				</Route>

				{ /* Payment/Subscription detail page with PaymentLayout */ }
				<Route path="/payment/:id" element={ <PaymentLayout /> }>
					<Route index element={ <PaymentDetailPage /> } />
				</Route>
			</Routes>
		</HashRouter>
	);
};
