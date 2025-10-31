import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { toast } from '@bsf/force-ui';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { RouterProvider } from '@tanstack/react-router';
import { router } from './routes';

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

// Expose query client globally
window.srfm_query_client = queryClient;
// Expose toast globally for easy access across the admin interface
window.srfm_toast = toast;

function renderApp() {
	// Render entries application with router.
	const entriesApp = document.getElementById( 'srfm-root' );
	const entriesRoot = createRoot( entriesApp );

	if ( entriesRoot ) {
		entriesRoot.render(
			<QueryClientProvider client={ queryClient }>
				<RouterProvider router={ router } />
			</QueryClientProvider>
		);
	}
}

domReady( renderApp );
