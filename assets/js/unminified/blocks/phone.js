function initializePhoneField() {
	const phone = document.querySelectorAll( '.srfm-phone-block' );

	phone.forEach( ( element ) => {
		const phoneNumber = element.querySelector( '.srfm-input-phone' );
		const errorMessage = element.querySelector( '.srfm-error-message' );
		const isAutoCountry = phoneNumber.getAttribute( 'auto-country' );
		const phoneFieldName = phoneNumber.getAttribute( 'name' );
		const itlOptions = {
			utilsScript: '../scripts/int-tel-input/utils.js',
			autoPlaceholder: 'off',
			separateDialCode: true,
			hiddenInput: phoneFieldName,
		};

		if ( isAutoCountry === 'true' ) {
			itlOptions.initialCountry = 'auto';
			itlOptions.geoIpLookup = function ( callback ) {
				fetch( 'https://ipapi.co/json' )
					.then( function ( res ) {
						return res.json();
					} )
					.then( function ( data ) {
						callback( data.country_code );
					} )
					.catch( function () {
						callback( 'us' );
					} );
			};
		}

		const iti = window.intlTelInput( phoneNumber, itlOptions );

		const updatePhoneNumber = () => {
			const phoneNumberValue = phoneNumber?.value
				? phoneNumber?.value.trim()
				: '';
			const parentBlock = phoneNumber.closest( '.srfm-block' );
			const hiddenInput = element.querySelector( 'input[type="hidden"]' );

			if ( phoneNumberValue && ! iti.isValidNumber() ) {
				parentBlock.classList.add( 'srfm-phone-error' );
				parentBlock.classList.add( 'srfm-error' );
				errorMessage.textContent = 'Please enter a valid phone number.';
			} else {
				parentBlock.classList.remove( 'srfm-phone-error' );
				parentBlock.classList.remove( 'srfm-error' );
				hiddenInput.value = iti.getNumber();
			}
		};

		if ( phoneNumber ) {
			phoneNumber.addEventListener( 'change', updatePhoneNumber );
			phoneNumber.addEventListener( 'countrychange', updatePhoneNumber );
		}
	} );
}

document.addEventListener( 'DOMContentLoaded', initializePhoneField );
