// A valid ISO 3166-1 alpha-2 code, lowercase.
const isIso2 = ( c ) => typeof c === 'string' && /^[a-z]{2}$/.test( c );

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
	const countryLower =
		typeof country === 'string' ? country.toLowerCase() : '';

	if ( enableCountryFilter !== 'true' ) {
		return isIso2( countryLower ) ? countryLower : '';
	}

	// Handle include filter. The list is JSON.parsed from a data attribute, so
	// normalize to lowercase and drop anything that isn't a valid ISO2 code.
	if (
		countryFilterType === 'include' &&
		Array.isArray( includeCountries ) &&
		includeCountries.length > 0
	) {
		const include = includeCountries
			.map( ( c ) => String( c ).toLowerCase() )
			.filter( isIso2 );
		if ( countryLower && include.includes( countryLower ) ) {
			return countryLower;
		}
		// Country not in include list — use the first valid included country.
		return include[ 0 ] || '';
	}

	// Handle exclude filter.
	if (
		countryFilterType === 'exclude' &&
		Array.isArray( excludeCountries ) &&
		excludeCountries.length > 0
	) {
		const exclude = excludeCountries
			.map( ( c ) => String( c ).toLowerCase() )
			.filter( isIso2 );
		if ( countryLower && ! exclude.includes( countryLower ) ) {
			return countryLower;
		}
		// Country is excluded — pick the first sensible fallback that is NOT
		// itself excluded (never a hardcoded gb/us that the site may exclude).
		const candidates = [ 'us', 'gb', 'ca', 'au', 'de', 'fr', 'in', 'jp' ];
		return candidates.find( ( c ) => ! exclude.includes( c ) ) || '';
	}

	return isIso2( countryLower ) ? countryLower : '';
}

/**
 * IANA timezone → ISO 3166-1 alpha-2 country (lowercase).
 *
 * The device timezone reflects the visitor's physical location far better than
 * their browser language, so it is the primary on-device signal. Covers the
 * common canonical zones; anything unlisted falls back to the locale region.
 */
const TIMEZONE_TO_COUNTRY = {
	// North America.
	'America/New_York': 'us', 'America/Detroit': 'us', 'America/Chicago': 'us', 'America/Denver': 'us', 'America/Phoenix': 'us', 'America/Los_Angeles': 'us', 'America/Anchorage': 'us', 'America/Adak': 'us', 'America/Boise': 'us', 'America/Indiana/Indianapolis': 'us', 'Pacific/Honolulu': 'us',
	'America/Toronto': 'ca', 'America/Vancouver': 'ca', 'America/Edmonton': 'ca', 'America/Winnipeg': 'ca', 'America/Halifax': 'ca', 'America/St_Johns': 'ca', 'America/Regina': 'ca',
	'America/Mexico_City': 'mx', 'America/Tijuana': 'mx', 'America/Monterrey': 'mx', 'America/Cancun': 'mx',
	// Central & South America.
	'America/Bogota': 'co', 'America/Lima': 'pe', 'America/Caracas': 've', 'America/Santiago': 'cl', 'America/Argentina/Buenos_Aires': 'ar', 'America/Sao_Paulo': 'br', 'America/Bahia': 'br', 'America/Fortaleza': 'br', 'America/Manaus': 'br', 'America/Montevideo': 'uy', 'America/Asuncion': 'py', 'America/La_Paz': 'bo', 'America/Guayaquil': 'ec', 'America/Panama': 'pa', 'America/Costa_Rica': 'cr', 'America/Guatemala': 'gt', 'America/El_Salvador': 'sv', 'America/Tegucigalpa': 'hn', 'America/Managua': 'ni', 'America/Santo_Domingo': 'do', 'America/Havana': 'cu', 'America/Jamaica': 'jm', 'America/Puerto_Rico': 'pr', 'America/Port_of_Spain': 'tt',
	// Europe.
	'Europe/London': 'gb', 'Europe/Dublin': 'ie', 'Europe/Lisbon': 'pt', 'Atlantic/Canary': 'es', 'Europe/Madrid': 'es', 'Europe/Paris': 'fr', 'Europe/Brussels': 'be', 'Europe/Amsterdam': 'nl', 'Europe/Luxembourg': 'lu', 'Europe/Berlin': 'de', 'Europe/Zurich': 'ch', 'Europe/Vienna': 'at', 'Europe/Rome': 'it', 'Europe/Malta': 'mt', 'Europe/Copenhagen': 'dk', 'Europe/Oslo': 'no', 'Europe/Stockholm': 'se', 'Europe/Helsinki': 'fi', 'Europe/Reykjavik': 'is', 'Europe/Warsaw': 'pl', 'Europe/Prague': 'cz', 'Europe/Bratislava': 'sk', 'Europe/Budapest': 'hu', 'Europe/Vilnius': 'lt', 'Europe/Riga': 'lv', 'Europe/Tallinn': 'ee', 'Europe/Athens': 'gr', 'Europe/Bucharest': 'ro', 'Europe/Sofia': 'bg', 'Europe/Belgrade': 'rs', 'Europe/Zagreb': 'hr', 'Europe/Ljubljana': 'si', 'Europe/Sarajevo': 'ba', 'Europe/Skopje': 'mk', 'Europe/Tirane': 'al', 'Europe/Chisinau': 'md', 'Europe/Kyiv': 'ua', 'Europe/Kiev': 'ua', 'Europe/Minsk': 'by', 'Europe/Moscow': 'ru', 'Europe/Istanbul': 'tr',
	// Middle East.
	'Asia/Jerusalem': 'il', 'Asia/Beirut': 'lb', 'Asia/Amman': 'jo', 'Asia/Damascus': 'sy', 'Asia/Baghdad': 'iq', 'Asia/Riyadh': 'sa', 'Asia/Kuwait': 'kw', 'Asia/Qatar': 'qa', 'Asia/Dubai': 'ae', 'Asia/Muscat': 'om', 'Asia/Bahrain': 'bh', 'Asia/Tehran': 'ir',
	// South & Central Asia.
	'Asia/Kabul': 'af', 'Asia/Karachi': 'pk', 'Asia/Kolkata': 'in', 'Asia/Calcutta': 'in', 'Asia/Colombo': 'lk', 'Asia/Kathmandu': 'np', 'Asia/Dhaka': 'bd', 'Asia/Thimphu': 'bt', 'Asia/Almaty': 'kz', 'Asia/Tashkent': 'uz', 'Asia/Baku': 'az', 'Asia/Yerevan': 'am', 'Asia/Tbilisi': 'ge',
	// East & Southeast Asia.
	'Asia/Yangon': 'mm', 'Asia/Bangkok': 'th', 'Asia/Ho_Chi_Minh': 'vn', 'Asia/Phnom_Penh': 'kh', 'Asia/Vientiane': 'la', 'Asia/Jakarta': 'id', 'Asia/Makassar': 'id', 'Asia/Kuala_Lumpur': 'my', 'Asia/Singapore': 'sg', 'Asia/Manila': 'ph', 'Asia/Hong_Kong': 'hk', 'Asia/Macau': 'mo', 'Asia/Taipei': 'tw', 'Asia/Shanghai': 'cn', 'Asia/Urumqi': 'cn', 'Asia/Tokyo': 'jp', 'Asia/Seoul': 'kr', 'Asia/Ulaanbaatar': 'mn',
	// Africa.
	'Africa/Abidjan': 'ci', 'Africa/Accra': 'gh', 'Africa/Addis_Ababa': 'et', 'Africa/Algiers': 'dz', 'Africa/Cairo': 'eg', 'Africa/Casablanca': 'ma', 'Africa/Johannesburg': 'za', 'Africa/Lagos': 'ng', 'Africa/Nairobi': 'ke', 'Africa/Tripoli': 'ly', 'Africa/Tunis': 'tn', 'Africa/Khartoum': 'sd', 'Africa/Dar_es_Salaam': 'tz', 'Africa/Kampala': 'ug', 'Africa/Kinshasa': 'cd', 'Africa/Maputo': 'mz', 'Africa/Windhoek': 'na', 'Africa/Harare': 'zw', 'Africa/Lusaka': 'zm', 'Africa/Dakar': 'sn',
	// Oceania.
	'Australia/Sydney': 'au', 'Australia/Melbourne': 'au', 'Australia/Brisbane': 'au', 'Australia/Perth': 'au', 'Australia/Adelaide': 'au', 'Australia/Darwin': 'au', 'Australia/Hobart': 'au', 'Pacific/Auckland': 'nz', 'Pacific/Fiji': 'fj', 'Pacific/Guam': 'gu', 'Pacific/Port_Moresby': 'pg',
};

/**
 * Detect a likely country for the visitor using only on-device browser data.
 *
 * Primary signal is the device timezone (physical location); the browser locale
 * region (language) is a weaker secondary fallback. Both are read locally via
 * Intl — no network request, no IP, no third-party call — so detection works
 * offline, on localhost and on full-page-cached pages, and never raises the
 * CORS / rate-limit / privacy concerns of a browser geo-IP request.
 *
 * @return {string} Lowercase 2-letter country code, or '' when undeterminable.
 */
function detectCountryFromBrowser() {
	// 1. Timezone → country (reflects physical location).
	try {
		const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
		if ( tz && TIMEZONE_TO_COUNTRY[ tz ] ) {
			return TIMEZONE_TO_COUNTRY[ tz ];
		}
	} catch {
		// Intl.DateTimeFormat unsupported — fall through to locale.
	}

	// 2. Locale region (language) — weaker, but better than nothing.
	try {
		const locales =
			Array.isArray( navigator.languages ) && navigator.languages.length
				? navigator.languages
				: [ navigator.language ];
		for ( const locale of locales ) {
			if ( ! locale ) {
				continue;
			}
			const region = new Intl.Locale( locale ).region;
			if ( region && /^[A-Z]{2}$/.test( region ) ) {
				return region.toLowerCase();
			}
		}
	} catch {
		// Intl.Locale unsupported or a malformed locale — fall through.
	}
	return '';
}

/**
 * Apply per-visitor auto country detection to an intl-tel-input instance.
 *
 * Resolution order, most-to-least authoritative:
 *   1. A browser-local Intl guess applied immediately — works offline, on
 *      localhost and on full-page-cached pages, with no flash and no network.
 *   2. The same-origin geo-country REST endpoint, used to refine the guess only
 *      when the server *confidently* detected (CDN header or a successful IP
 *      lookup); cached in sessionStorage for the session.
 *
 * Respects the field's country filters and never overrides a value the visitor
 * already started typing.
 *
 * @param {Object}      iti         The intl-tel-input instance.
 * @param {HTMLElement} phoneNumber The phone input element.
 * @param {Object}      filters     Country-filter settings for this field.
 */
function applyAutoCountry( iti, phoneNumber, filters ) {
	// Track the last country WE applied, seeded from whatever the instance
	// currently shows (the server-baked default). The async "refine" steps only
	// apply if the selected country still equals this — i.e. the visitor hasn't
	// changed it meanwhile (by typing OR by picking from the dropdown).
	let lastAutoApplied = iti.getSelectedCountryData()?.iso2 || null;

	const setCountry = ( country, { isRefine = false } = {} ) => {
		if ( ! country || typeof country !== 'string' ) {
			return;
		}
		const countryLower = country.toLowerCase();
		// Guard the (possibly external) value before it reaches setCountry().
		if ( ! isIso2( countryLower ) ) {
			return;
		}
		// Don't override a value the visitor already started entering.
		if ( phoneNumber.value && phoneNumber.value.trim() !== '' ) {
			return;
		}
		// Don't override an explicit country the visitor chose (e.g. from the
		// dropdown, without typing) before this async refine resolved.
		if ( isRefine && lastAutoApplied !== null ) {
			const current = iti.getSelectedCountryData()?.iso2;
			if ( current && current !== lastAutoApplied ) {
				return;
			}
		}
		const validated = validateCountryWithFilters(
			countryLower,
			filters.enableCountryFilter,
			filters.countryFilterType,
			filters.includeCountries,
			filters.excludeCountries
		);
		if ( validated ) {
			iti.setCountry( validated );
			lastAutoApplied = validated;
		}
	};

	// 1. Immediate, network-free local guess so the flag is sensible even on
	//    localhost, offline, or before any request resolves.
	setCountry( detectCountryFromBrowser() );

	const endpoint =
		window.srfm_phone_data && window.srfm_phone_data.geo_endpoint;
	if ( ! endpoint ) {
		return;
	}

	// 2. Prefer a confident server detection. Reuse a cached one for the session.
	let cached = null;
	try {
		cached = window.sessionStorage?.getItem( 'srfm_geo_country_detected' );
	} catch {
		cached = null;
	}
	if ( cached ) {
		setCountry( cached, { isRefine: true } );
		return;
	}

	fetch( endpoint, { headers: { Accept: 'application/json' } } )
		.then( ( res ) => ( res.ok ? res.json() : null ) )
		.then( ( data ) => {
			// Only override the local guess when the server is confident.
			if ( ! data || ! data.detected || ! data.country ) {
				return;
			}
			const country = String( data.country );
			try {
				window.sessionStorage?.setItem( 'srfm_geo_country_detected', country );
			} catch {
				// sessionStorage may be unavailable (e.g. private mode) — ignore.
			}
			setCountry( country, { isRefine: true } );
		} )
		.catch( () => {
			// Network error — keep the local guess.
		} );
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
			allowPhonewords: true,
		};

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

		// Add i18n translations if available (loaded via language file)
		// The language file sets window.intlTelInputI18n with country names and UI translations
		if ( window.intlTelInputI18n ) {
			itlOptions.i18n = window.intlTelInputI18n;
		}

		const iti = window.intlTelInput( phoneNumber, itlOptions );

		// Prevent the country flag from changing based on input value changes
		// (e.g., browser autocomplete, typing, or deleting the country code).
		// The flag should only change when the user explicitly selects a country from the dropdown.
		// Dropdown selection goes through _selectListItem -> _setCountry directly, so it still works.
		iti._updateCountryFromNumber = () => false;

		// Per-visitor auto country detection. The server bakes an initial
		// `default-country`, but on full-page-cached sites that value is the first
		// visitor's. When auto-country is enabled, fetch the current visitor's
		// country from the same-origin REST endpoint (not part of the cached page
		// HTML) and apply it — correct per visitor.
		if ( phoneNumber.getAttribute( 'data-auto-country' ) === 'true' ) {
			applyAutoCountry( iti, phoneNumber, {
				enableCountryFilter,
				countryFilterType,
				includeCountries,
				excludeCountries,
			} );
		}

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
			 * Get the normalized phone number (converts phonewords like '+1 800 FLOWERS' to digits).
			 * Then validate using intlTelInput.utils.isValidNumber() which properly handles
			 * the normalized value. This approach supports phonewords when allowPhonewords is enabled.
			 */
			const normalizedNumber = iti.getNumber();
			const isValid =
				normalizedNumber &&
				window.intlTelInput?.utils?.isValidNumber( normalizedNumber );

			if ( phoneNumberValue && ! isValid ) {
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
				// Use normalized number (ensures phonewords are converted to digits)
				iti.hiddenInput.value = normalizedNumber || '';
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
