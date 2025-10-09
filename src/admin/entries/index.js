import domReady from '@wordpress/dom-ready';
import { render } from '@wordpress/element';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import FormPageHeader from '../components/PageHeader';
import EntriesListingPage from './EntriesListingPage';

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

	if ( headerApp !== null ) {
		render( <FormPageHeader />, headerApp );
	}

	// Render entries table.
	const entriesApp = document.getElementById( 'srfm-root' );

	if ( entriesApp !== null ) {
		render(
			<QueryClientProvider client={ queryClient }>
				<EntriesListingPage />
			</QueryClientProvider>,
			entriesApp
		);
	}
}

domReady( renderApp );
