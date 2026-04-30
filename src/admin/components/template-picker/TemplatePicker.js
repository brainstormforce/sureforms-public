import { useEffect, createRoot } from '@wordpress/element';
import { BrowserRouter as Router } from 'react-router-dom';
import AiFormBuilder from './components/AiFormBuilder.js';

const TemplatePicker = () => {
	// Remove admin bar padding.
	useEffect( () => {
		document.querySelector( 'html.wp-toolbar' ).style.paddingTop = 0;
	}, [] );

	function QueryScreen() {
		return <AiFormBuilder />;
	}

	return (
		<>
			<Router>
				<QueryScreen />
			</Router>
		</>
	);
};

export default TemplatePicker;

( function () {
	const app = document.getElementById( 'srfm-add-new-form-container' );

	function renderApp() {
		if ( null !== app ) {
			const root = createRoot( app );
			root.render( <TemplatePicker /> );
		}
	}

	document.addEventListener( 'DOMContentLoaded', renderApp );
}() );
