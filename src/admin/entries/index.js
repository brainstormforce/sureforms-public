import domReady from '@wordpress/dom-ready';
import { render } from '@wordpress/element';
import FormPageHeader from '../components/PageHeader';
import EntriesListingPage from './EntriesListingPage';

function renderApp() {
	// Render page header.
	const headerApp = document.getElementById( 'srfm-page-header' );

	if ( headerApp !== null ) {
		render( <FormPageHeader />, headerApp );
	}

	// Render entries table.
	const entriesApp = document.getElementById( 'srfm-root' );

	if ( entriesApp !== null ) {
		render( <EntriesListingPage />, entriesApp );
	}
}

domReady( renderApp );
