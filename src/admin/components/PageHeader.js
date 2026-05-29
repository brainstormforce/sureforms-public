import { createRoot } from '@wordpress/element';
import { cn } from '@Utils/Helpers';
import Header from './Header';

const FormPageHeader = ( { className } ) => {
	return (
		<div className={ cn( 'z-50 relative', className ) }>
			<Header />
		</div>
	);
};

( function () {
	const app = document.getElementById( 'srfm-page-header' );

	function renderApp() {
		if ( null !== app ) {
			const root = createRoot( app );
			root.render( <FormPageHeader /> );
		}
	}

	document.addEventListener( 'DOMContentLoaded', renderApp );
}() );

export default FormPageHeader;
