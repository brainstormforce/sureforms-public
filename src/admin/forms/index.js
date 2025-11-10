import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { HashRouter as Router, Routes, Route } from 'react-router-dom';
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

// Component to handle invalid routes with blank screen
const NotFound = () => <div />;

const App = () => (
	<QueryClientProvider client={ queryClient }>
		<Router>
			<Routes>
				<Route path="/" element={ <FormsListingPage /> } />
				<Route path="*" element={ <NotFound /> } />
			</Routes>
			<Toaster />
		</Router>
	</QueryClientProvider>
);

domReady( () => {
	const container = document.getElementById( 'srfm-forms-root' );
	if ( container ) {
		const root = createRoot( container );
		root.render( <App /> );
	}
} );
