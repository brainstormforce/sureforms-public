import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import LearnPage from '../dashboard/Learn';
import '../tw-base.scss';

function renderApp() {
	const app = document.getElementById( 'srfm-learn-root' );

	if ( app ) {
		const root = createRoot( app );
		root.render( <LearnPage /> );
	}
}

domReady( renderApp );
