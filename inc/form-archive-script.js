/* eslint-disable no-unused-vars */
function toggleStatus( postId ) {
	const checkbox = document.querySelector(
		'input[data-postid="' + postId + '"][type="checkbox"]'
	);

	updatePostStatus( postId, checkbox.checked );
}

async function updatePostStatus( postId, status ) {
	const data = { post_id: postId, sureforms_form_status: status };

	try {
		const response = await fetch(
			'/wp-json/sureforms/v1/update-form-status',
			{
				method: 'POST',
				body: JSON.stringify( data ),
				headers: {
					'Content-Type': 'application/json',
				},
			}
		);

		if ( response.ok ) {
			// console.log( response );
		} else {
			throw new Error( response.statusText );
		}
	} catch ( error ) {
		console.error( error );
	}
}

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
