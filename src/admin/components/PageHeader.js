import Header from './Header';
import { render } from '@wordpress/element';

const FormPageHeader = () => {
	return (
		<div className="z-50 relative">
			<Header />
		</div>
	);
};

export default FormPageHeader;

( function () {
	const app = document.getElementById( 'srfm-page-header' );

	function renderApp() {
		if ( null !== app ) {
			render( <FormPageHeader />, app );
		}
	}

	document.addEventListener( 'DOMContentLoaded', renderApp );
}() );
