const id = formSubmitData.formID;
const submitType = formSubmitData.successSubmitType;
const successUrl = formSubmitData.successUrl;
let allEntries = [];

function getUniqueValidationData( ) {
	// eslint-disable-next-line
	const xhr = new XMLHttpRequest();

	xhr.onreadystatechange = function () {
	  if ( xhr.readyState === 4 ) {
			if ( xhr.status === 200 ) {
				const responseData = JSON.parse( xhr.responseText );
				const data = responseData.data;
				if ( data ) {
					// Process the data
					allEntries = data;
				}
			} else {
				console.error( xhr.statusText );
			}
	  }
	};
	xhr.open( 'POST', formSubmitData.ajaxurl, true );
	xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
	xhr.send( 'action=validation_ajax_action&nonce=' + encodeURIComponent( formSubmitData.nonce ) + '&id=' + encodeURIComponent( id ) );
}

//If any field need unique check then we need all entries
document.addEventListener( 'DOMContentLoaded', function () {
	const isPresentUniqueField = document.querySelectorAll( 'input[area-unique="true"]' );
	if ( isPresentUniqueField.length !== 0 ) {
		getUniqueValidationData();
	}
} );

function fieldValidation() {
	let validateResult = false;
	let firstErrorInput = null;
	const fieldContainers = Array.from( document.querySelectorAll( '.main-container' ) );
	for ( const container of fieldContainers ) {
		const inputField = container.querySelector( 'input, textarea' );
		const isRequired = inputField.getAttribute( 'area-required' );
		const isUnique = inputField.getAttribute( 'area-unique' );
		const fieldName = inputField.getAttribute( 'name' ).replace( /_/g, ' ' );
		const inputValue = inputField.value;
		const errorMessage = container.querySelector( '.error-message' );
		const duplicateMessage = container.querySelector( '.duplicate-message' );

		if ( isRequired ) {
			if ( isRequired === 'true' && ! inputValue ) {
				errorMessage.style.display = 'block';
				validateResult = true;
				if ( ! firstErrorInput ) {
					firstErrorInput = inputField;
				  }
			} else {
				errorMessage.style.display = 'none';
			}
		}

		if ( isUnique && inputValue !== '' ) {
			const hasDuplicate = allEntries.some( ( entry ) => entry[ fieldName ] === inputValue );
			if ( hasDuplicate ) {
				duplicateMessage.style.display = 'block';
				validateResult = true;
				if ( ! firstErrorInput ) {
					firstErrorInput = inputField;
				  }
			} else {
				duplicateMessage.style.display = 'none';
			}
		}

		//phone field
		if ( container.classList.contains( 'sureforms-input-phone-container' ) ) {
			const phoneInput = container.querySelectorAll( 'input' )[ 1 ];
			const isPhoneRequired = phoneInput.getAttribute( 'area-required' );
			if ( isPhoneRequired === 'true' && ! inputValue ) {
				errorMessage.style.display = 'block';
				validateResult = true;
				if ( ! firstErrorInput ) {
					firstErrorInput = inputField;
				  }
			} else {
				errorMessage.style.display = 'none';
			}
		}
	}

	if ( firstErrorInput ) {
		firstErrorInput.focus();
	  }

	return validateResult;
}

document.addEventListener( 'DOMContentLoaded', function () {
	// Capture the form submission event
	const form = document.querySelector( '#sureforms-form-' + id );
	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault(); // Prevent the default form submission
		const isValidate = fieldValidation();
		if ( isValidate ) {
			return;
		}
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
