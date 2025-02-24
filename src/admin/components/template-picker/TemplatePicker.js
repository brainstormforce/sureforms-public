import { useEffect, render } from '@wordpress/element';
import Header from './components/Header.js';
import { BrowserRouter as Router, useLocation } from 'react-router-dom';
import AiFormBuilder, { getLimitReachedPopup } from './components/AiFormBuilder.js';
import AddNewForm from './ai-form-builder-components/AddNewForm.js';

const TemplatePicker = () => {
	// Remove admin bar padding.
	useEffect( () => {
		document.querySelector( 'html.wp-toolbar' ).style.paddingTop = 0;
	}, [] );

	// Starting screen navigation
	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	function QueryScreen() {
		const query = useQuery();
		const method = query.get( 'method' );
		switch ( method ) {
			case 'ai':
				return (
					// Check if the user has reached the limit of AI usage. If not, show the AI form builder.
					srfm_admin?.srfm_ai_usage_details?.remaining === 0 || srfm_admin?.srfm_ai_usage_details?.code ? getLimitReachedPopup(
					) : <AiFormBuilder />
				);

			default:
				return (
					<>
						<Header />
						<AddNewForm />
					</>
				);
		}
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
			render( <TemplatePicker />, app );
		}
	}

	document.addEventListener( 'DOMContentLoaded', renderApp );
}() );
