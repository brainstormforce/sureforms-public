const id = formSubmitData.formID;
const submitType = formSubmitData.successSubmitType;
const successUrl = formSubmitData.successUrl;

document.addEventListener( 'DOMContentLoaded', function () {
	// Capture the form submission event
	const form = document.querySelector( '#sureforms-form-' + id );
	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault(); // Prevent the default form submission
		document
			.querySelector( '.sureforms-loader' )
			.removeAttribute( 'style' );

		const formData = new FormData( form );
		fetch( '/wp-json/sureforms/v1/submit-form', {
			method: 'POST',
			body: formData,
		} )
			.then( ( response ) => {
				if ( response.ok ) {
					// Handle the successful response
					document
						.querySelector( '.sureforms-loader' )
						.setAttribute( 'style', 'display: none' );
					if ( 'message' === submitType ) {
						document
							.querySelector( '#sureforms-success-message' )
							.removeAttribute( 'hidden' );
						setTimeout( () => {
							document
								.querySelector( '#sureforms-success-message' )
								.setAttribute( 'hidden', 'true' );
						}, 2000 );
					} else {
						window.location.assign( successUrl );
					}
				} else {
					document
						.querySelector( '.sureforms-loader' )
						.setAttribute( 'style', 'display: none' );
					document
						.querySelector( '#sureforms-error-message' )
						.removeAttribute( 'hidden' );
				}
			} )
			.catch( ( error ) => {
				// Handle the network error
				document
					.querySelector( '.sureforms-loader' )
					.setAttribute( 'style', 'display: none' );
				document
					.querySelector( '#sureforms-error-message' )
					.removeAttribute( 'hidden' );
				console.error( 'Network Error:', error );
			} );
	} );
} );
