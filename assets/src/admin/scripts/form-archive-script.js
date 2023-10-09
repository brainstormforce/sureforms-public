/* eslint-disable no-unused-vars */

function handleFormShortcode( button ) {
	const input = button.previousElementSibling;

	const icon = button.parentElement.querySelector( '#sf-copy-icon' );

	icon.classList.remove( 'dashicons-admin-page' );
	icon.classList.add( 'dashicons-yes' );
	icon.style = 'color: green;';

	setTimeout( () => {
		icon.classList.remove( 'dashicons-yes' );
		icon.classList.add( 'dashicons-admin-page' );
		icon.style = 'color: black;';
	}, 2000 );

	input.setSelectionRange( 0, 99999 ); // For mobile devices

	navigator.clipboard.writeText( input.value );
}
