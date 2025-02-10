import '@surecart/components/src/themes/base.css';
// import '@Admin/tw-base.css';
import { createRoot } from '@wordpress/element';
import Dashboard from './Dashboard';

const APP = () => {
	return (
		<Dashboard />
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
