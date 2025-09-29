import { createRoot } from '@wordpress/element';
import PaymentListWrapper from './pages/paymentListWrapper.js';
import { PaymentDataProvider } from './components/context.js';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import '../tw-base.scss';

const WithContext = () => {
	const queryClient = new QueryClient();

	return (
		<PaymentDataProvider>
			<QueryClientProvider client={ queryClient }>
				<PaymentListWrapper />
			</QueryClientProvider>
		</PaymentDataProvider>
	);
};

( function () {
	const app = document.getElementById( 'srfm-payments-react-container' );

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( null !== app ) {
			const root = createRoot( app );
			root.render( <WithContext /> );
		}
	} );
}() );
