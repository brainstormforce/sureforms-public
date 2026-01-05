/**
 * Validates and returns a country code based on filter settings.
 * If the country is not valid for current filters, returns the first valid country.
 *
 * @param {string} country             - The country code to validate
 * @param {string} enableCountryFilter - Whether country filtering is enabled ('true' or 'false')
 * @param {string} countryFilterType   - The filter type ('include' or 'exclude')
 * @param {Array}  includeCountries    - Array of country codes to include
 * @param {Array}  excludeCountries    - Array of country codes to exclude
 * @return {string} A valid country code based on the filters
 */
function validateCountryWithFilters(
	country,
	enableCountryFilter,
	countryFilterType,
	includeCountries,
	excludeCountries
) {
	if ( enableCountryFilter !== 'true' ) {
		return country;
	}

	const countryLower = country.toLowerCase();

	// Handle include filter
	if ( countryFilterType === 'include' && includeCountries.length > 0 ) {
		if ( ! includeCountries.includes( countryLower ) ) {
			// Country not in include list, use first country from the list
			return includeCountries[ 0 ];
		}
		return countryLower;
	}

	// Handle exclude filter
	if ( countryFilterType === 'exclude' && excludeCountries.length > 0 ) {
		if ( excludeCountries.includes( countryLower ) ) {
			// Country is excluded, use 'us' or another fallback
			return excludeCountries.includes( 'us' ) ? 'gb' : 'us';
		}
		return countryLower;
	}

	return country;
}

function initializePhoneField() {
	const phone = document.querySelectorAll( '.srfm-phone-block' );

	phone.forEach( ( element ) => {
		const phoneNumber = element.querySelector( '.srfm-input-phone' );

		// Check if already initialized and clean up if needed
		if (
			phoneNumber.getAttribute( 'data-srfm-phone-initialized' ) === 'true'
		) {
			cleanupPhoneFields( element );
		}

		// Mark as being initialized to prevent double initialization
		phoneNumber.setAttribute( 'data-srfm-phone-initialized', 'true' );

		const errorMessage = element.querySelector( '.srfm-error-message' );
		const isAutoCountry = phoneNumber.getAttribute( 'auto-country' );
		const defaultCountry = phoneNumber.getAttribute( 'default-country' );
		const phoneFieldName = phoneNumber.getAttribute( 'name' );
		const enableCountryFilter = phoneNumber.getAttribute(
			'data-enable-country-filter'
		);
		const countryFilterType = phoneNumber.getAttribute(
			'data-country-filter-type'
		);
		const includeCountriesAttr = phoneNumber.getAttribute(
			'data-include-countries'
		);
		const excludeCountriesAttr = phoneNumber.getAttribute(
			'data-exclude-countries'
		);

		// Parse country filter arrays
		let includeCountries = [];
		let excludeCountries = [];

		if ( enableCountryFilter === 'true' ) {
			try {
				if ( countryFilterType === 'include' && includeCountriesAttr ) {
					includeCountries = JSON.parse( includeCountriesAttr );
				}
				if ( countryFilterType === 'exclude' && excludeCountriesAttr ) {
					excludeCountries = JSON.parse( excludeCountriesAttr );
				}
			} catch ( e ) {
				console.error( 'Error parsing country filter data:', e );
			}
		}

		// Determine initial country based on filter settings
		let initialCountry = defaultCountry || 'us';

		// Validate the initial country against filters
		initialCountry = validateCountryWithFilters(
			initialCountry,
			enableCountryFilter,
			countryFilterType,
			includeCountries,
			excludeCountries
		);

		const itlOptions = {
			autoPlaceholder: 'off',
			separateDialCode: true,
			hiddenInput: () => ( {
				phone: phoneFieldName,
			} ),
			countrySearch: true,
			initialCountry,
		};

		if ( isAutoCountry === 'true' ) {
			itlOptions.initialCountry = 'auto';
			itlOptions.geoIpLookup = function ( callback ) {
				fetch( 'https://ipapi.co/json' )
					.then( function ( res ) {
						return res.json();
					} )
					.then( function ( data ) {
						let detectedCountry = data.country_code
							? data.country_code.toLowerCase()
							: 'us';

						// Validate detected country against filters
						detectedCountry = validateCountryWithFilters(
							detectedCountry,
							enableCountryFilter,
							countryFilterType,
							includeCountries,
							excludeCountries
						);

						callback( detectedCountry );
					} )
					.catch( function () {
						// On error, use validated fallback country
						let fallbackCountry = 'us';

						// Validate fallback country against filters
						fallbackCountry = validateCountryWithFilters(
							fallbackCountry,
							enableCountryFilter,
							countryFilterType,
							includeCountries,
							excludeCountries
						);

						callback( fallbackCountry );
					} );
			};
		}

		// Apply country filtering if enabled
		if ( enableCountryFilter === 'true' ) {
			if (
				countryFilterType === 'include' &&
				includeCountries.length > 0
			) {
				// Use onlyCountries when filter type is include
				itlOptions.onlyCountries = includeCountries;
			} else if (
				countryFilterType === 'exclude' &&
				excludeCountries.length > 0
			) {
				// Use excludeCountries when filter type is exclude
				itlOptions.excludeCountries = excludeCountries;
			}
		}

		const iti = window.intlTelInput( phoneNumber, itlOptions );
		const countriesData =
			iti?.countryList.querySelectorAll( '.iti__country' );

		// handle padding based on the direction of the page
		const selectedCountry = element.querySelector(
			'.iti__selected-country-primary'
		);

		/**
		 * Set the aria-hidden attribute to true for the selected dial code element,
		 * so that it is not read by screen readers. This is because the selected country
		 * has the dial code as well, and we don't want to repeat the information.
		 */
		const selectedDialCode = element.querySelector(
			'.iti__selected-dial-code'
		);
		if ( selectedDialCode ) {
			selectedDialCode.setAttribute( 'aria-hidden', 'true' );
		}
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

			/**
			 * Using the internal _utilsIsValidNumber() function from the intl-tel-input library to validate the phone number.
			 * The function returns true if the number is valid, false otherwise.
			 *
			 * The isValidNumber() contains the following check:
			 * If the input is alpha numeric then it validates the number separately.
			 * For example, if the input is '+611800FLIGHT' then it validates '+611800' and 'FLIGHT' separately
			 * which returns false.
			 */
			if (
				phoneNumberValue &&
				! iti._utilsIsValidNumber( iti.getNumber() )
			) {
				parentBlock.classList.add( 'srfm-phone-error' );
				window?.srfm?.toggleErrorState( parentBlock, true );
				errorMessage.textContent =
					window?.srfm_submit?.messages?.srfm_valid_phone_number;
				/**
				 * Set the phone number input value to the hidden input even if the phone number is not valid,
				 * so that the unique validation can be overridden and invalid/required validation messages will be visible.
				 */
				iti.hiddenInput.value = iti.telInput.value;
			} else {
				parentBlock.classList.remove( 'srfm-phone-error' );
				window?.srfm?.toggleErrorState( parentBlock, false );
				iti.hiddenInput.value = iti.getNumber();
			}
		};
		/**
		 * Set the data-unique parameter to the hidden input field based on the data-unique
		 * parameter of the phone input field.
		 * The ajax validation will use the hidden input value to check for uniqueness.
		 */
		const uniqueAttr = iti.telInput.getAttribute( 'data-unique' );
		if ( uniqueAttr ) {
			iti.hiddenInput.setAttribute( 'data-unique', `${ uniqueAttr }` );
		}

		if ( phoneNumber ) {
			/**
			 * Changed onChange event to input event to handle paste event.
			 */
			phoneNumber.addEventListener( 'input', updatePhoneNumber );
			phoneNumber.addEventListener( 'countrychange', updatePhoneNumber );
			// Add iti__active class to the selected country in the dropdown and scroll to the selected country.
			phoneNumber.addEventListener( 'open:countrydropdown', () => {
				const selectedCountryData = iti.getSelectedCountryData();
				if ( selectedCountryData ) {
					countriesData.forEach( ( country ) => {
						if ( country.classList.contains( 'iti__active' ) ) {
							country.classList.remove( 'iti__active' );
						}
					} );
					const activeCountry = iti?.countryList.querySelector(
						`.iti__country[data-country-code="${ selectedCountryData.iso2 }"]`
					);
					if ( activeCountry ) {
						activeCountry.classList.add( 'iti__active' );
						activeCountry.scrollIntoView( {
							block: 'nearest',
							behavior: 'instant',
						} );
					}
				}
			} );
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
	const flagContainer = element.querySelector( '.iti__selected-country' );
	flagContainer.addEventListener( 'click', () => {
		const itiContainerMobile = document.querySelector( '.iti--container' );
		itiContainerMobile?.classList.add( `srfm-form-container-${ id }` );
	} );
}

// Cleanup function for phone fields
function cleanupPhoneFields( container = document ) {
	const phoneNumbers = container.querySelectorAll( '.srfm-input-phone' );

	phoneNumbers.forEach( ( phoneNumber ) => {
		try {
			const itiContainer = phoneNumber.closest( '.iti' );
			if ( itiContainer && itiContainer.parentNode ) {
				itiContainer.parentNode.insertBefore(
					phoneNumber,
					itiContainer
				);
				itiContainer.remove();
			}

			// Reset initialization flag
			phoneNumber.removeAttribute( 'data-srfm-phone-initialized' );
		} catch ( cleanupError ) {
			console.warn( 'Error cleaning up phone field:', cleanupError );
		}
	} );
}

// make phone field initialization function available globally
window.srfmInitializePhoneField = initializePhoneField;
window.srfmCleanupPhoneFields = cleanupPhoneFields;

document.addEventListener( 'srfm_form_before_submission', ( e ) => {
	const form = e.detail?.form;
	if ( ! form ) {
		return;
	}

	const phones = form.querySelectorAll( '.srfm-input-phone' );
	if ( ! phones || phones.length === 0 ) {
		return;
	}

	// Clean up existing instances first
	cleanupPhoneFields( form );

	setTimeout( () => {
		initializePhoneField();
	}, 100 );
} );
