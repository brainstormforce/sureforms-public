import { render } from '@wordpress/element';
import { BrowserRouter as Router, useLocation } from 'react-router-dom';
import FormPageHeader from '../components/PageHeader';

import Navigation from './Navigation';
import Component from './Component';

function useQuery() {
	return new URLSearchParams( useLocation().search );
}

function QueryScreen() {
	const query = useQuery();
	return <Component path={ query.get( 'tab' ) } />;
}

const Settings = () => {
	return (
		<Router>
			<FormPageHeader />
			<div className="srfm-settings-wrap">
				<Navigation />
				<div className="srfm-settings-page-container">
					<div className="srfm-settings-page-inner">
						<QueryScreen />
					</div>
				</div>
			</div>
		</Router>
	);
};

export default Settings;

( function () {
	const app = document.getElementById( 'srfm-settings-container' );

	function renderApp() {
		if ( null !== app ) {
			render( <Settings />, app );
		}
	}

	document.addEventListener( 'DOMContentLoaded', renderApp );
}() );
