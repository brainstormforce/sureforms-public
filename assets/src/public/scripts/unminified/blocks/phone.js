function initializePhoneField() {
	const phoneElement = document.getElementsByClassName(
		'srfm-input-phone-container'
	);

	if ( phoneElement ) {
		for ( let i = 0; i < phoneElement.length; i++ ) {
			const blockID = phoneElement[ i ].id.split( '-' )[ 3 ];
			const phoneNumber = document.getElementById(
				`srfm-phone-number-${ blockID }`
			);
			const fullPhoneNumberInput = document.getElementById(
				`srfm-fullPhoneNumber-${ blockID }`
			);
			const errorMessage = phoneElement[ i ].querySelector(
				'.srfm-error-message'
			);
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
			const updateFullPhoneNumber = () => {
				const phoneNumberValue = phoneNumber.value.trim();
				fullPhoneNumberInput.value = iti.getNumber();
				if ( ! phoneNumberValue ) {
					fullPhoneNumberInput.value = '';
				}
				const intTelError = phoneElement[ i ].querySelector(
					'.srfm-int-tel-error'
				);
				const phoneParent = phoneElement[ i ].querySelector(
					'.srfm-classic-phone-parent'
				);
				if ( phoneNumberValue && ! iti.isValidNumber() ) {
					if ( intTelError ) {
						intTelError.style.display = 'block';
						phoneParent.classList.add( 'srfm-classic-input-error' );
						errorMessage.style.display = 'none';
					}
				} else {
					intTelError.style.display = 'none';
					phoneParent.classList.remove( 'srfm-classic-input-error' );
				}
			};

			if ( phoneNumber ) {
				phoneNumber.addEventListener( 'change', updateFullPhoneNumber );
				phoneNumber.addEventListener(
					'countrychange',
					updateFullPhoneNumber
				);
			}
		}
	}
}

document.addEventListener( 'DOMContentLoaded', initializePhoneField );
