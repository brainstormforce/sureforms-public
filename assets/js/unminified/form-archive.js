/* eslint-disable no-unused-vars */
document.addEventListener( 'DOMContentLoaded', function () {
	const shortcodeInputs = document.querySelectorAll(
		'.srfm-shortcode-input'
	);
	shortcodeInputs.forEach( ( input ) => {
		const text = input.value;
		const testSpan = document.createElement( 'span' );
		testSpan.style.visibility = 'hidden';
		testSpan.style.whiteSpace = 'pre';
		testSpan.style.font = window.getComputedStyle( input ).font;
		testSpan.textContent = text;

		document.body.appendChild( testSpan );
		const width = testSpan.offsetWidth + 8;
		document.body.removeChild( testSpan );

		input.style.width = width + 'px';
	} );
} );
function handleFormShortcode( button ) {
	const input = button.previousElementSibling;

	const icon = button.parentElement.querySelector( '#srfm-copy-icon' );

	icon.classList.remove( 'dashicons-admin-page' );
	icon.classList.add( 'dashicons-yes' );
	icon.style = 'color: green;';

	setTimeout( () => {
		icon.style = 'color: #9BA3AF;';
		icon.classList.remove( 'dashicons-yes' );
		icon.classList.add( 'dashicons-admin-page' );
	}, 2000 );

	input.setSelectionRange( 0, 99999 ); // For mobile devices

	navigator.clipboard.writeText( input.value );
}
