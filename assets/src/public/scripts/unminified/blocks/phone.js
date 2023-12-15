function initializePhoneField() {
	const phone = document.querySelectorAll('.srfm-phone-block');

	phone.forEach(element => {
		const phoneNumber = element.querySelector('.srfm-input-phone');
		const errorMessage = element.querySelector('.srfm-error-message');
		const isAutoCountry = phoneNumber.getAttribute( 'auto-country' );
		const itlOptions = {
			utilsScript: '../scripts/int-tel-input/utils.js',
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
			const phoneNumberValue = phoneNumber.value.trim();

			if ( phoneNumberValue && ! iti.isValidNumber() ) {
				phoneNumber.closest('.srfm-block').classList.add('srfm-error');
				errorMessage.textContent = "Please enter a valid phone number.";
			} else {
				phoneNumber.closest('.srfm-block').classList.remove('srfm-error');
			}
		};


		if ( phoneNumber ) {
			phoneNumber.addEventListener( 'change', updatePhoneNumber );
			phoneNumber.addEventListener(
				'countrychange',
				updatePhoneNumber
			);
		}
	});
}




document.addEventListener( 'DOMContentLoaded', initializePhoneField );
