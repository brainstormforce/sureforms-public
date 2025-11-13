import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { toast, Toaster } from '@bsf/force-ui';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { PaymentDataProvider } from './components/context.js';
import { AppRouter } from './routes';
import '../tw-base.scss';

// Create a client
const queryClient = new QueryClient( {
	defaultOptions: {
		queries: {
			refetchOnWindowFocus: false,
			refetchOnMount: true,
			retry: 1,
			staleTime: 1000 * 60 * 5, // 5 minutes
		},
	},
} );

// Expose query client globally
window.srfm_payment_query_client = queryClient;
// Expose toast globally for easy access across the payment interface
window.srfm_payment_toast = toast;

function renderApp() {
	// Render payments application with router.
	const paymentsApp = document.getElementById(
		'srfm-payments-react-container'
	);

	if ( paymentsApp ) {
		const paymentsRoot = createRoot( paymentsApp );
		paymentsRoot.render(
			<PaymentDataProvider>
				<QueryClientProvider client={ queryClient }>
					<AppRouter />
					<Toaster className="z-999999" />
				</QueryClientProvider>
			</PaymentDataProvider>
		);
	}
}

domReady( renderApp );
