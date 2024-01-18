document.addEventListener( 'load', srfm_responsive_icons );
document.addEventListener( 'DOMContentLoaded', srfm_responsive_icons );

import DeviceIcons from './device-icons';

function srfm_responsive_icons() {
	wp.data.subscribe( function () {
		setTimeout( function () {
			srfm_responsive_icon();
		}, 500 );
	} );
}

function srfm_responsive_icon() {
	if ( ! document.querySelector( '.edit-post-header__settings' ) ) {
		return null;
	}
	if ( document.querySelector( '.srfm-responsive-icons__wrap' ) ) {
		return null;
	}

	const buttonWrapper = document.createElement( 'div' );
	buttonWrapper.classList.add( 'srfm-responsive-icons__wrap' );

	document
		.querySelector( '.edit-post-header__settings' )
		.insertBefore(
			buttonWrapper,
			document.querySelector( '.edit-post-header__settings' ).firstChild
		);
	wp.element.render(
		<DeviceIcons />,
		document.querySelector( '.srfm-responsive-icons__wrap' )
	);
}
