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
	Done,
} from '../onboarding';
import '../tw-base.scss';
import '../onboarding/styles.scss';

const APP = () => {
	const { onboarding_completed, onboarding_redirect } = srfm_admin || {};

	// If onboarding is not completed and this is an activation redirect, show onboarding.
	const shouldShowOnboarding = ! onboarding_completed || onboarding_redirect;

	if ( shouldShowOnboarding ) {
		return (
			<Router>
				<Routes>
					<Route path="/onboarding" element={ <OnboardingLayout /> }>
						<Route path="welcome" element={ <Welcome /> } />
						<Route path="connect" element={ <Connect /> } />
						<Route
							path="email-delivery"
							element={ <EmailDelivery /> }
						/>
						<Route
							path="premium-features"
							element={ <PremiumFeatures /> }
						/>
						<Route path="done" element={ <Done /> } />
					</Route>
					<Route
						path="*"
						element={
							<Navigate to="/onboarding/welcome" replace />
						}
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
				<Route path="/onboarding" element={ <OnboardingLayout /> }>
					<Route path="welcome" element={ <Welcome /> } />
					<Route path="connect" element={ <Connect /> } />
					<Route
						path="email-delivery"
						element={ <EmailDelivery /> }
					/>
					<Route
						path="premium-features"
						element={ <PremiumFeatures /> }
					/>
					<Route path="done" element={ <Done /> } />
				</Route>
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
