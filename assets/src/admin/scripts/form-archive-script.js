/* eslint-disable no-unused-vars */

function handleFormShortcode( inputElement ) {
	inputElement.select();

	navigator.clipboard
		.writeText( inputElement.value )
		.then( function () {
			displayNotification( 'Copied to clipboard: ' + inputElement.value );
			setTimeout( () => {
				const notification = document.getElementById( 'notification' );
				notification.setAttribute( 'hidden', 'true' );
			}, 2000 );
		} )
		.catch( function ( error ) {
			console.error( 'Failed to copy to clipboard:', error );
		} );
}

function displayNotification( message ) {
	const notification = document.getElementById( 'notification' );
	notification.removeAttribute( 'hidden' );
	notification.textContent = message;
}
