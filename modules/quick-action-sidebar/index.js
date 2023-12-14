/**
 * The Quick Access React App for Spectra Enhanced Editor.
 */
import domReady from '@wordpress/dom-ready';
import { createRoot } from 'react-dom';
import Sidebar from './components/Sidebar';

domReady( function () {
	const interval = setInterval( () => {
		const rootElement = document.querySelector( '.interface-interface-skeleton__body' );
		if ( ! rootElement ) {
			return;
		}

		clearInterval( interval );

		let container =document.querySelector( '.interface-interface-skeleton__body .srfm-ee-quick-access' );

		if ( ! container ) {
			container = rootElement.insertBefore( document.createElement( 'div' ), rootElement.firstChild );
			container.classList.add( 'srfm-ee-quick-access' );
		}
		const root = createRoot( container );

		root.render(
			<Sidebar/>
		);
	}
	, 10 );
} );