document.addEventListener( 'load', spectra_responsive_icons );
document.addEventListener( 'DOMContentLoaded', spectra_responsive_icons );

import DeviceIcons from './device-icons';

function spectra_responsive_icons() {
	if ( srfm_spec_blocks_info.wp_version > '6.2.2' ) {
		// Don't show the Spectra Responsive Icons if WP version is greater than 6.2.2.
		return;
	}
	wp.data.subscribe( function () {
		setTimeout( function () {
			spectra_responsive_icon();
		}, 500 );
	} );
}

function spectra_responsive_icon() {
	if ( ! document.querySelector( '.edit-post-header__settings' ) ) {
		return null;
	}
	if ( document.querySelector( '.spectra-responsive-icons__wrap' ) ) {
		return null;
	}

	const buttonWrapper = document.createElement( 'div' );
	buttonWrapper.classList.add( 'spectra-responsive-icons__wrap' );

	document
		.querySelector( '.edit-post-header__settings' )
		.insertBefore(
			buttonWrapper,
			document.querySelector( '.edit-post-header__settings' ).firstChild
		);
	wp.element.render(
		<DeviceIcons />,
		document.querySelector( '.spectra-responsive-icons__wrap' )
	);
}
