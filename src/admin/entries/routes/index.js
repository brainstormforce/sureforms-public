import {
	Router,
	createRoute,
	createRootRoute,
	createHashHistory,
} from '@tanstack/react-router';
import EntriesListingPage from './EntriesListingPage';
import EntryDetailPage from './EntryDetailPage';
import RootLayout from './RootLayout';

// Create the root route
const rootRoute = new createRootRoute( {
	component: RootLayout,
} );

// Create the index route for entries listing
const indexRoute = new createRoute( {
	getParentRoute: () => rootRoute,
	path: '/',
	component: EntriesListingPage,
} );

// Create the dynamic route for individual entry view
const entryDetailRoute = new createRoute( {
	getParentRoute: () => rootRoute,
	path: '/entry/$id',
	component: EntryDetailPage,
} );

// Create the route tree
const routeTree = rootRoute.addChildren( [ indexRoute, entryDetailRoute ] );

// Create hash history
const hashHistory = createHashHistory();

// Create the router instance with hash routing
export const router = new Router( {
	routeTree,
	history: hashHistory,
	defaultPreload: 'intent',
} );
