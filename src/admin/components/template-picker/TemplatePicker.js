import { useEffect, createRoot } from '@wordpress/element';
import { BrowserRouter as Router, useLocation } from 'react-router-dom';
import AiFormBuilder from './components/AiFormBuilder.js';
import TemplateLibrary from './components/TemplateLibrary.js';

/**
 * Picks the screen from the URL. Reads the router's location, so moving to the
 * template library with `useOpenTemplateLibrary()` swaps screens in place.
 */
const QueryScreen = () => {
	const { search } = useLocation();
	const view = new URLSearchParams( search ).get( 'view' );
	return 'templates' === view ? <TemplateLibrary /> : <AiFormBuilder />;
};

const TemplatePicker = () => {
	// Remove admin bar padding.
	useEffect( () => {
		// The wp-toolbar class is only present when WordPress renders the admin
		// bar, so bail out instead of dereferencing a missing element.
		const htmlElement = document.querySelector( 'html.wp-toolbar' );

		if ( ! htmlElement ) {
			return;
		}

		const previousPaddingTop = htmlElement.style.paddingTop;

		htmlElement.style.paddingTop = 0;

		return () => {
			htmlElement.style.paddingTop = previousPaddingTop;
		};
	}, [] );

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
