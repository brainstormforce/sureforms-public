/**
 * Toggles the "srfm-error" class on the specified container element based on validation results.
 * Additionally, manages the `id` attribute on the error message element to enable screen reader accessibility.
 *
 * @param {HTMLElement} container - The parent container of the input field to validate.
 * @param {boolean}     hasError  - Determines whether to add or remove the error state.
 *
 *                                Description:
 *                                - When `hasError` is true, the function adds the "srfm-error" class to the container, enabling styling for invalid fields.
 *                                - If an error message element with the `data-temp-id` attribute exists as a child of the container,
 *                                this function assigns its value as the `id` attribute to allow screen readers to announce the error when visible.
 *                                - Conversely, if `hasError` is false, the function removes both the "srfm-error" class and `id` attribute from the
 *                                error message element, making it hidden from screen readers when the field is valid.
 */
function toggleErrorState( container, hasError ) {
	// Check if the current class state already matches the intended state to avoid redundant operations
	if ( container.classList.contains( 'srfm-error' ) === hasError ) {
		return;
	}

	// Toggle the "srfm-error" class based on `hasError` state
	container.classList.toggle( 'srfm-error', hasError );

	// Manage the error message's `id` attribute for screen reader accessibility
	const errorMessage = container.querySelector( '.srfm-error-message' );
	if ( errorMessage ) {
		if ( hasError && errorMessage.hasAttribute( 'data-temp-id' ) ) {
			// Set the `id` to `data-temp-id` value to allow screen readers to announce the error message
			errorMessage.id = errorMessage.getAttribute( 'data-temp-id' );
		} else {
			// Remove the `id` when the field is valid, hiding the message from screen readers
			errorMessage.removeAttribute( 'id' );
		}
	}
}

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
				toggleErrorState( parentBlock, true );
				errorMessage.textContent =
					window?.srfm_submit?.messages?.valid_phone_number;
			} else {
				parentBlock.classList.remove( 'srfm-phone-error' );
				toggleErrorState( parentBlock, false );
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
