function initializePhoneField() {
	const phone = document.querySelectorAll( '.srfm-phone-block' );

	phone.forEach( ( element ) => {
		const phoneNumber = element.querySelector( '.srfm-input-phone' );
		const errorMessage = element.querySelector( '.srfm-error-message' );
		const isAutoCountry = phoneNumber.getAttribute( 'auto-country' );
		const phoneFieldName = phoneNumber.getAttribute( 'name' );
		const itlOptions = {
			autoPlaceholder: 'off',
			separateDialCode: true,
			hiddenInput: () => ( {
				phone: phoneFieldName,
			} ),
			countrySearch: false,
			initialCountry: 'us',
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

		// handle padding based on the direction of the page
		const selectedCountry = element.querySelector(
			'.iti__selected-country-primary'
		);
		if ( srfm_submit?.is_rtl ) {
			selectedCountry.style.paddingLeft = '0';
		} else {
			selectedCountry.style.paddingRight = '0';
		}

		const updatePhoneNumber = () => {
			const phoneNumberValue = phoneNumber?.value
				? phoneNumber?.value.trim()
				: '';
			const parentBlock = phoneNumber.closest( '.srfm-block' );

			if ( phoneNumberValue && ! iti.isValidNumber() ) {
				parentBlock.classList.add( 'srfm-phone-error' );
				parentBlock.classList.add( 'srfm-error' );
				errorMessage.textContent =
					window?.srfm_submit?.messages?.srfm_valid_phone_number;
			} else {
				parentBlock.classList.remove( 'srfm-phone-error' );
				parentBlock.classList.remove( 'srfm-error' );
				iti.hiddenInput.value = iti.getNumber();
			}
		};

		if ( phoneNumber ) {
			phoneNumber.addEventListener( 'change', updatePhoneNumber );
			phoneNumber.addEventListener( 'countrychange', updatePhoneNumber );
		}

		itiContainerClass( element );
	} );
}

/**
 * Checks if the current device is a mobile device based on screen width.
 * Considering the devices with a screen width of 768px or less as mobile devices.
 *
 * @return {boolean} True if the device is considered mobile, false otherwise.
 */
function isMobileDevice() {
	return window.innerWidth <= 768;
}

/**
 * This function adds an event listener to the selected flag container to add a class to the iti container for mobile devices.
 * The class needs to be added as the CSS variables are scoped under the form container class,
 * and for mobile view the iti container is not a child of the form container.
 *
 * @param {HTMLElement} element - The phone block element inside the form.
 * @return {void} This function does not return a value. It modifies the DOM by adding an event listener to the country list dropdown.
 */
function itiContainerClass( element ) {
	if ( ! isMobileDevice() ) {
		return;
	}
	const id = element.closest( 'form' ).getAttribute( 'form-id' );
	const flagContainer = element.querySelector( '.iti__selected-flag' );
	flagContainer.addEventListener( 'click', () => {
		const itiContainerMobile = document.querySelector( '.iti--container' );
		itiContainerMobile?.classList.add( `srfm-form-container-${ id }` );
	} );
}

// make phone field initialization function available globally
window.srfmInitializePhoneField = initializePhoneField;

document.addEventListener( 'DOMContentLoaded', initializePhoneField );
