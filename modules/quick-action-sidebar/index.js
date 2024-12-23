/**
 * The Quick Access React App for Spectra Enhanced Editor.
 */
import { createRoot } from 'react-dom/client';
import Sidebar from './components/Sidebar';

// Toggles the sidebar based on the url parameters.
export const toggleSidebar = ( url ) => {
	const currentUrl = new URL( url );
	if ( '/wp-admin/site-editor.php' === currentUrl.pathname ) {
		if ( 'edit' === currentUrl.searchParams.get( 'canvas' ) ) {
			attachSidebarAfterLoading();
		} else {
			const container = document.querySelector( '.srfm-ee-quick-access' );
			if ( container ) {
				container.parentElement.remove();
			}
		}
	}
};

// Attaches the sidebar to the DOM.
export const attachSidebar = () => {
	const interval = setInterval( () => {
		const rootElement = document.querySelector(
			'.interface-interface-skeleton__body'
		);
		if ( ! rootElement ) {
			return;
		}

		const blockElement = document.querySelector(
			'.srfm-ee-quick-access-container'
		);
		if ( blockElement ) {
			clearInterval( interval );
			return;
		}

		clearInterval( interval );

		let container = rootElement.querySelector( '.srfm-ee-quick-access' );

		if ( ! container ) {
			container = document.createElement( 'div' );
			container.classList.add( 'srfm-ee-quick-access-container' );
			rootElement.insertBefore( container, rootElement.firstChild );
		}

		const root = createRoot( container );
		root.render( <Sidebar /> );
	}, 100 );
};

// Attaches the sidebar after the page is loaded ( in FSE editor).
const attachSidebarAfterLoading = () => {
	const skeletonInterval = setInterval( () => {
		const skeleton = document.querySelector(
			'.edit-site-editor__interface-skeleton'
		);
		if ( skeleton ) {
			if ( ! skeleton.classList.contains( 'is-loading' ) ) {
				clearInterval( skeletonInterval );
				attachSidebar();
			}
		}
	}, 100 );
};
