import { render } from '@wordpress/element';
import { BrowserRouter as Router, useLocation } from 'react-router-dom';
import FormPageHeader from '../components/PageHeader';

import Navigation from './Navigation';
import Component from './Component';
import { Toaster } from '@bsf/force-ui';

function useQuery() {
	return new URLSearchParams( useLocation().search );
}

function QueryScreen() {
	const query = useQuery();
	return <Component path={ query.get( 'tab' ) } />;
}

const Settings = () => {

	const isRTL = srfm_admin?.is_rtl;
	const toasterPosition = isRTL
		? 'top-left'
		: 'top-right';

	return (
		<>
			<Router>
				<FormPageHeader />
				<div className="grid grid-cols-[16rem_1fr] auto-rows-fr bg-background-secondary before:content-['_'] before:fixed before:inset-0 before:h-full before:w-[26rem] before:bg-background-primary before:shadow-sm">
					<Navigation />
					<div className="max-h-full h-full overflow-y-auto">
						<div className="p-8">
							<QueryScreen />
						</div>
					</div>
				</div>
			</Router>
			<Toaster className="z-[999999]" position={ toasterPosition } />
		</>
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
