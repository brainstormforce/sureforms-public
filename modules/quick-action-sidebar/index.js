/**
 * The Quick Access React App for Spectra Enhanced Editor.
 */
import domReady from '@wordpress/dom-ready';
import { createRoot } from 'react-dom';
import Sidebar from './components/sidebar';

domReady( () => {
	// If not FSE editor, attach the sidebar to the DOM.
	const currentUrl = new URL( window.location.href );
	if( '/wp-admin/site-editor.php' === currentUrl.pathname ) {
		toggleSidebar( window.location.href );

		// For FSE we are adding eventlistener to remove the sidebar when the user canvas is not editable.
		window.navigation.addEventListener( 'navigate', e => {
			toggleSidebar( e.destination.url );
		} );
	} else {

		// Attach the sidebar to the DOM.
		attachSidebar();
	}

} );

// Toggles the sidebar based on the url parameters.
const toggleSidebar = ( url ) => {
	const currentUrl = new URL( url );
	if( '/wp-admin/site-editor.php' === currentUrl.pathname ) {
		if( 'edit' === currentUrl.searchParams.get( 'canvas' ) ) {
			attachSidebarAfterLoading();
		} else {
			const container = document.querySelector( '.srfm-ee-quick-access' );
			if ( container ) {
				container.parentElement.remove();
			}
		}
	}
}

// Attaches the sidebar to the DOM.
const attachSidebar = () => {
	const interval = setInterval( () => {
		const rootElement = document.querySelector( '.interface-interface-skeleton__body' );
		if ( ! rootElement ) {
			return;
		}

		const blockElement = document.querySelector( '.srfm-ee-quick-access__sidebar--blocks--block' );
		if ( blockElement ) {
			clearInterval( interval );
			return;
		}

		clearInterval( interval );

		let container = rootElement.querySelector( '.srfm-ee-quick-access' );

		if ( ! container ) {
			const parentDiv = rootElement.insertBefore( document.createElement( 'div' ), rootElement.firstChild );
			container = parentDiv.appendChild( document.createElement( 'div' ) );
			container.classList.add( 'srfm-ee-quick-access' );
		}
		const root = createRoot( container );
		root.render( <Sidebar/> );
	}
	, 100 );
}

// Attaches the sidebar after the page is loaded ( in FSE editor).
const attachSidebarAfterLoading = () => {
	const skeletonInterval = setInterval( () => {
		const skeleton = document.querySelector( '.edit-site-editor__interface-skeleton' );
		if( skeleton ) {
			if( ! skeleton.classList.contains( 'is-loading' ) ) {
				clearInterval( skeletonInterval );
				attachSidebar();
			}
		}
	}
	, 100 );
}
