import {
	Router,
	createRoute,
	createRootRoute,
	createHashHistory,
} from '@tanstack/react-router';
import EntriesListingPage from './EntriesListingPage';
import EntryDetailPage from './EntryDetailPage';
import RootLayout from './RootLayout';
import EntryLayout from './EntryLayout';

// Create the root route (no component, just a container)
const rootRoute = new createRootRoute();

// Create a layout route for entries listing with RootLayout
const listingLayoutRoute = new createRoute( {
	getParentRoute: () => rootRoute,
	id: 'listing-layout',
	component: RootLayout,
} );

// Create the index route for entries listing
const indexRoute = new createRoute( {
	getParentRoute: () => listingLayoutRoute,
	path: '/',
	component: EntriesListingPage,
} );

// Create the entry layout route (separate from RootLayout)
const entryLayoutRoute = new createRoute( {
	getParentRoute: () => rootRoute,
	id: 'entry-layout',
	component: EntryLayout,
} );

// Create the dynamic route for individual entry view
const entryDetailRoute = new createRoute( {
	getParentRoute: () => entryLayoutRoute,
	path: '/entry/$id',
	component: EntryDetailPage,
} );

// Create the route tree
const routeTree = rootRoute.addChildren( [
	listingLayoutRoute.addChildren( [ indexRoute ] ),
	entryLayoutRoute.addChildren( [ entryDetailRoute ] ),
] );

// Create hash history
const hashHistory = createHashHistory();

// Create the router instance with hash routing
export const router = new Router( {
	routeTree,
	history: hashHistory,
	defaultPreload: 'intent',
} );
