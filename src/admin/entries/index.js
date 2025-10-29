import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import FormPageHeader from '../components/PageHeader';
import EntriesListingPage from './EntriesListingPage';
import { Toaster } from '@bsf/force-ui';

// Create a client
const queryClient = new QueryClient( {
	defaultOptions: {
		queries: {
			refetchOnWindowFocus: false,
			retry: 1,
			staleTime: 1000 * 60 * 5, // 5 minutes
		},
	},
} );

function renderApp() {
	// Render page header.
	const headerApp = document.getElementById( 'srfm-page-header' );
	const headerRoot = createRoot( headerApp );
	if ( headerRoot ) {
		headerRoot.render( <FormPageHeader /> );
	}

	// Render entries table.
	const entriesApp = document.getElementById( 'srfm-root' );
	const entriesRoot = createRoot( entriesApp );

	if ( entriesRoot ) {
		entriesRoot.render(
			<QueryClientProvider client={ queryClient }>
				<EntriesListingPage />
				<Toaster className="z-999999" />
			</QueryClientProvider>
		);
	}
}

domReady( renderApp );
