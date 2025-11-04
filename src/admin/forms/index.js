import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import FormsListingPage from './FormsListingPage';
import { Toaster } from '@bsf/force-ui';
import '../tw-base.scss';

// Create a client
const queryClient = new QueryClient( {
	defaultOptions: {
		queries: {
			retry: 1,
			staleTime: 1000 * 60 * 5, // 5 minutes
		},
	},
} );

const App = () => (
	<QueryClientProvider client={ queryClient }>
		<FormsListingPage />
		<Toaster />
	</QueryClientProvider>
);

domReady( () => {
	const container = document.getElementById( 'srfm-forms-root' );
	if ( container ) {
		const root = createRoot( container );
		root.render( <App /> );
	}
} );
