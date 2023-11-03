import { render } from '@wordpress/element';
import { BrowserRouter as Router, useLocation } from 'react-router-dom';

import Header from './Header';
import Navigation from './Navigation';
import Component from './Component';
import './styles.scss';

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
			<Header />
			<div className="flex flex-row h-[100vh] bg-transparent]">
				<Navigation />
				<QueryScreen />
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
