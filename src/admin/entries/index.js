import { render } from '@wordpress/element';
import FormPageHeader from '../components/PageHeader';

( function () {
	const app = document.getElementById( 'srfm-page-header' );

	function renderApp() {
		if ( null !== app ) {
			render( <FormPageHeader />, app );
		}
	}

	document.addEventListener( 'DOMContentLoaded', renderApp );
}() );