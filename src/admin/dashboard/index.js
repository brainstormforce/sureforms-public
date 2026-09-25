import { createRoot } from '@wordpress/element';
import {
	HashRouter as Router,
	Routes,
	Route,
	Navigate,
} from 'react-router-dom';
import Dashboard from './Dashboard';
import {
	OnboardingLayout,
	Welcome,
	Connect,
	EmailDelivery,
	PremiumFeatures,
	UserDetails,
	ImportForms,
	CacheConflict,
	Done,
} from '../onboarding';
import '../tw-base.scss';
import '../onboarding/styles.scss';

// One definition for both Router branches below. Keeping two hand-maintained
// copies is how `cache-conflict` ended up registered in only one of them: a
// missing path falls through to `*`, which unmounts OnboardingLayout and wipes
// the wizard's state mid-flow.
const ONBOARDING_ROUTES = [
	[ 'welcome', <Welcome key="welcome" /> ],
	[ 'connect', <Connect key="connect" /> ],
	[ 'email-delivery', <EmailDelivery key="email-delivery" /> ],
	[ 'premium-features', <PremiumFeatures key="premium-features" /> ],
	[ 'import-forms', <ImportForms key="import-forms" /> ],
	[ 'cache-conflict', <CacheConflict key="cache-conflict" /> ],
	[ 'user-details', <UserDetails key="user-details" /> ],
	[ 'done', <Done key="done" /> ],
];

const renderOnboardingRoutes = () => (
	<Route path="/onboarding" element={ <OnboardingLayout /> }>
		{ ONBOARDING_ROUTES.map( ( [ path, element ] ) => (
			<Route key={ path } path={ path } element={ element } />
		) ) }
	</Route>
);

const APP = () => {
	const { onboarding_completed, onboarding_redirect } = srfm_admin || {};

	// If onboarding is not completed and this is an activation redirect, show onboarding.
	const shouldShowOnboarding = ! onboarding_completed || onboarding_redirect;

	if ( shouldShowOnboarding ) {
		return (
			<Router>
				<Routes>
					{ renderOnboardingRoutes() }
					<Route
						path="*"
						element={ <Navigate to="/onboarding/welcome" replace /> }
					/>
				</Routes>
			</Router>
		);
	}

	// Show regular dashboard if onboarding is completed
	return (
		<Router>
			<Routes>
				<Route path="/" element={ <Dashboard /> } />
				{ renderOnboardingRoutes() }
				<Route path="*" element={ <Dashboard /> } />
			</Routes>
		</Router>
	);
};

( function () {
	const app = document.getElementById( 'srfm-dashboard-container' );

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( null !== app ) {
			const root = createRoot( app );
			root.render( <APP /> );
		}
	} );
}() );
