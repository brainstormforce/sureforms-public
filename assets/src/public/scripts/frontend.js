
// Sender's Email.

const emailElements = document.getElementsByClassName(
	'srfm-input-email-container'
);

if ( emailElements.length > 0 ) {
	const emailAddress = document.getElementsByClassName( 'srfm-input-email' );
	emailAddress[ 0 ].addEventListener( 'input', ( e ) => {
		document.querySelector( '#srfm-sender-email' ).value = e.target.value;
	} );
}

// Multi Choice

const multiChoices = document.getElementsByClassName( 'srfm-multi-choice' );

if ( multiChoices ) {
	const selectedOptions = new Map();
	for ( let i = 0; i < multiChoices.length; i++ ) {
		multiChoices[ i ].addEventListener( 'click', ( e ) => {
			const clickArr = e.target.id.split( '-' );
			const clickedId = clickArr[ 3 ];
			const selectedInd = Number( clickArr[ 4 ] );

			const sureformsMultiChoiceLabel = document.getElementsByClassName(
				`srfm-multi-choice-label-${ clickedId }`
			);

			if (
				'buttons' ===
				document.getElementById(
					`srfm-multi-choice-style-${ clickedId }`
				).value
			) {
				const selectContainer = document.getElementById(
					`srfm-multi-choice-container-${ clickedId }`
				);
				selectContainer.classList.add( 'sf--focus' );
				const singleSelection = document.getElementById(
					`srfm-multi-choice-selection-${ clickedId }`
				).value;
				if (
					singleSelection &&
					sureformsMultiChoiceLabel.length !== 0
				) {
					// Reset background color and text color for all labels
					for (
						let j = 0;
						j < sureformsMultiChoiceLabel.length;
						j++
					) {
						sureformsMultiChoiceLabel[ j ].style.backgroundColor =
							'white';
						sureformsMultiChoiceLabel[ j ].style.color = 'black';
					}
					// Set background color and text color for the selected label
					sureformsMultiChoiceLabel[
						selectedInd
					].style.backgroundColor = 'black';
					sureformsMultiChoiceLabel[ selectedInd ].style.color =
						'white';
				} else if ( sureformsMultiChoiceLabel.length !== 0 ) {
					const backgroundColor =
						sureformsMultiChoiceLabel[ selectedInd ].style
							.backgroundColor;
					const color =
						sureformsMultiChoiceLabel[ selectedInd ].style.color;
					if ( backgroundColor === 'black' && color === 'white' ) {
						sureformsMultiChoiceLabel[
							selectedInd
						].style.backgroundColor = 'white';
						sureformsMultiChoiceLabel[ selectedInd ].style.color =
							'black';
					} else {
						sureformsMultiChoiceLabel[
							selectedInd
						].style.backgroundColor = 'black';
						sureformsMultiChoiceLabel[ selectedInd ].style.color =
							'white';
					}
				}
			}
			const singleSelection = document.getElementById(
				`srfm-multi-choice-selection-${ clickedId }`
			).value;
			const selectedValue = document.getElementById(
				`srfm-multi-choice-option-${ clickedId }-${ selectedInd }`
			).innerText;
			if ( ! selectedOptions.has( clickedId ) ) {
				selectedOptions.set( clickedId, [] );
			}
			const curr_block = selectedOptions.get( clickedId );
			if ( singleSelection ) {
				if ( ! curr_block.includes( selectedInd + 1 ) ) {
					selectedOptions.set( clickedId, [ selectedValue ] );
				} else {
					selectedOptions.set( clickedId, [] );
				}
			} else if ( curr_block.includes( selectedValue ) ) {
				const index = curr_block.indexOf( selectedValue );
				if ( index !== -1 ) {
					curr_block.splice( index, 1 );
				}
			} else {
				curr_block.push( selectedValue );
			}

			const multiChoiceValueField = document.getElementsByClassName(
				`srfm-multi-choice-${ clickedId }`
			);
			multiChoiceValueField[ 0 ].value = selectedOptions
				.get( clickedId )
				.join( ', ' );
		} );
	}
}
const multichoiceOptions = document.getElementsByClassName(
	'srfm-classic-multi-choice'
);
if ( window.innerWidth > 630 ) {
	for ( let x = 0; x < multichoiceOptions.length - 1; x++ ) {
		const optionHeight = multichoiceOptions[ x ].offsetHeight;
		const adjacentOptionHeight = multichoiceOptions[ x + 1 ].offsetHeight;
		if ( optionHeight > adjacentOptionHeight ) {
			multichoiceOptions[ x ].style.height = optionHeight + 'px';
			multichoiceOptions[ x + 1 ].style.height = optionHeight + 'px';
		} else {
			multichoiceOptions[ x ].style.height = adjacentOptionHeight + 'px';
			multichoiceOptions[ x + 1 ].style.height =
				adjacentOptionHeight + 'px';
		}
		x++;
	}
}

// Address Field

const addressElement = document.getElementsByClassName(
	'srfm-address-container'
);

if ( addressElement ) {
	for ( let i = 0; i < addressElement.length; i++ ) {
		const blockID = addressElement[ i ].id.split( '-' )[ 3 ];
		const addressLine1 = document.getElementById(
			`srfm-address-line-1-${ blockID }`
		);
		const addressLine2 = document.getElementById(
			`srfm-address-line-2-${ blockID }`
		);
		const addressCity = document.getElementById(
			`srfm-address-city-${ blockID }`
		);
		const addressState = document.getElementById(
			`srfm-address-state-${ blockID }`
		);
		const addressPostal = document.getElementById(
			`srfm-address-postal-${ blockID }`
		);
		const addressCountry = document.getElementById(
			`srfm-address-country-${ blockID }`
		);

		const fullAddressInput = document.getElementById(
			`srfm-fullAddress-${ blockID }`
		);

		const updateFullAddress = () => {
			const addressLine1Value = addressLine1.value.trim();
			const addressLine2Value = addressLine2.value.trim();
			const addressCityValue = addressCity.value.trim();
			const addressStateValue = addressState.value.trim();
			const addressPostalValue = addressPostal.value.trim();
			const addressCountryValue = addressCountry.value.trim();

			const addressParts = [
				addressLine1Value,
				addressLine2Value,
				addressCityValue,
				addressStateValue,
				addressPostalValue,
				addressCountryValue,
			];

			const fullAddress = addressParts
				.filter( ( part ) => part !== '' )
				.join( ', ' );

			fullAddressInput.value = fullAddress;
		};

		addressLine1.addEventListener( 'change', updateFullAddress );
		addressLine2.addEventListener( 'change', updateFullAddress );
		addressCity.addEventListener( 'change', updateFullAddress );
		addressState.addEventListener( 'change', updateFullAddress );
		addressPostal.addEventListener( 'change', updateFullAddress );
		addressCountry.addEventListener( 'change', updateFullAddress );
		addressCountry.addEventListener( 'click', () => {
			addressCountry.style.color = '#2c3338';
		} );
	}
}

// Date & Time Picker

const dateTimeElement = document.getElementsByClassName(
	'srfm-input-date-container'
);

if ( dateTimeElement ) {
	for ( let i = 0; i < dateTimeElement.length; i++ ) {
		const blockID = dateTimeElement[ i ].id.split( '-' )[ 4 ];
		const dateInput = document.getElementById(
			`srfm-input-date-${ blockID }`
		);
		const timeInput = document.getElementById(
			`srfm-input-time-${ blockID }`
		);

		const fullDateTimeInput = document.getElementById(
			`srfm-full-date-time-${ blockID }`
		);

		const updateFullDateTime = () => {
			let date = '';
			if ( dateInput ) {
				date = dateInput.value
					.trim()
					.split( /[\/-]/ )
					.reverse()
					.join( '-' );
			}
			let time = '';
			if ( timeInput ) {
				time = timeInput.value.trim();
			}
			const dateTimeParts = [ date, time ];

			const fullDateTime = dateTimeParts
				.filter( ( part ) => part !== '' )
				.join( ', ' );

			fullDateTimeInput.value = fullDateTime;
		};

		if ( dateInput ) {
			dateInput.addEventListener( 'change', updateFullDateTime );
		}
		if ( timeInput ) {
			timeInput.addEventListener( 'change', updateFullDateTime );
		}
	}
}

// Phone Field

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

//input range field

const sliderElement = document.getElementsByClassName(
	'srfm-number-slider-input'
);

if ( sliderElement ) {
	for ( let i = 0; i < sliderElement.length; i++ ) {
		const blockID = sliderElement[ i ].id.split( '-' )[ 3 ];
		const sliderInput = document.getElementById(
			`srfm-number-slider-${ blockID }`
		);
		sliderInput.addEventListener( 'input', ( e ) => {
			const slideValue = e.target.value;
			document.getElementById(
				`srfm-number-slider-value-${ blockID }`
			).innerText = slideValue;
		} );
	}
}

//Number field
const numberElements = Array.from(
	document.getElementsByClassName( 'srfm-input-number-container' )
);

if ( numberElements ) {
	for ( const numberContainer of numberElements ) {
		const numberInput = numberContainer.querySelector( 'input' );
		numberInput.addEventListener( 'input', ( e ) => {
			const formatType = numberInput.getAttribute( 'format-type' );
			let inputValue = e.target.value;
			if ( formatType === 'none' ) {
				return;
			}
			if ( formatType === 'non-decimal' ) {
				inputValue = inputValue.replace( /[^0-9]/g, '' );
			} else {
				inputValue = inputValue.replace( /[^0-9.]/g, '' );
				const dotCount = inputValue.split( '.' ).length - 1;
				if ( dotCount > 1 ) {
					inputValue = inputValue.replace( /\.+$/g, '' );
				}
			}
			numberInput.value = inputValue;
		} );
	}
}

const inputContainers = document.querySelectorAll( '.srfm-main-container' );

inputContainers.forEach( ( container ) => {
	const inputs = container.querySelectorAll( 'input, textarea, select' );

	inputs.forEach( ( input ) => {
		const isRatingInput = input.classList.contains( 'srfm-rating-field' );
		const isMultiInput = input.classList.contains(
			'srfm-multi-choice-container'
		);
		const isFileInput = input.type === 'file';
		if ( isRatingInput || isFileInput || isMultiInput ) {
			input.addEventListener( 'click', () => {
				const focusedDivs = document.querySelector( '.sf--focus' );
				if ( focusedDivs ) {
					focusedDivs.classList.remove( 'sf--focus' );
				}
				container.classList.add( 'sf--focus' );
			} );
		} else {
			input.addEventListener( 'focus', () => {
				const focusedDivs = document.querySelector( '.sf--focus' );
				if ( focusedDivs ) {
					focusedDivs.classList.remove( 'sf--focus' );
				}
				container.classList.add( 'sf--focus' );
			} );

			input.addEventListener( 'blur', () => {
				container.classList.remove( 'sf--focus' );
			} );
		}
	} );
} );

const checkboxContainers = document.querySelectorAll(
	'.srfm-checkbox-container'
);

if ( checkboxContainers ) {
	for ( let i = 0; i < checkboxContainers.length; i++ ) {
		{
			const formElement = checkboxContainers[ i ].closest( 'form' );
			// eslint-disable-next-line no-undef
			const computedStyle = getComputedStyle( formElement );
			const primaryColor = computedStyle.getPropertyValue(
				'--srfm-primary-color'
			);
			const checkboxInputs = checkboxContainers[ i ].querySelectorAll(
				'.srfm-classic-checkbox-input'
			);
			checkboxInputs.forEach( ( checkboxInput ) => {
				if ( '' === primaryColor ) {
					checkboxInput.classList.add(
						'!srfm-text-[#0084C7]',
						'focus:!srfm-ring-[#0084C7]',
						'checked:!srfm-bg-[#0084C7]',
						'checked:!srfm-border-none'
					);
				} else {
					checkboxInput.classList.add(
						'!srfm-text-srfm_primary_color',
						'focus:!srfm-ring-srfm_primary_color',
						'checked:!srfm-bg-srfm_primary_color',
						'checked:!srfm-border-none'
					);
				}
			} );
		}
	}
}

//text-area field
const textAreaContainer = Array.from(
	document.getElementsByClassName( 'srfm-textarea-container' )
);
if ( textAreaContainer ) {
	for ( const areaInput of textAreaContainer ) {
		const areaField = areaInput.querySelector( 'textarea' );
		areaField.addEventListener( 'input', function () {
			const textAreaValue = areaField.value;
			const maxLength = areaField.getAttribute( 'maxLength' );
			if ( maxLength !== '' ) {
				const counterDiv = areaInput.querySelector(
					'.srfm-text-area-counter'
				);
				const remainingLength = maxLength - textAreaValue.length;
				counterDiv.innerText = remainingLength + '/' + maxLength;
			}
		} );
	}
}
//password strength
const passwordContainer = Array.from(
	document.getElementsByClassName( 'srfm-input-password-container' )
);
if ( passwordContainer ) {
	for ( const passwordInput of passwordContainer ) {
		const isClassic = passwordInput.classList.contains(
			'srfm-classic-inputs-holder'
		);
		if ( isClassic ) {
			continue;
		}
		const inputField = passwordInput.querySelector( 'input' );
		inputField.addEventListener( 'input', function () {
			const password = inputField.value;
			const passwordStrength = passwordInput.querySelector(
				'.srfm-password-strength-message'
			);
			passwordInput.querySelector( '.srfm-error-message' ).style.display =
				'none';
			if ( passwordInput.querySelector( '.srfm-info-icon' ) ) {
				passwordInput.querySelector( '.srfm-info-icon' ).style.display =
					'inline-block';
			}
			const strength = calculatePasswordStrength( password );
			updatePasswordStrength( strength, passwordStrength );
		} );
	}
}

function calculatePasswordStrength( password ) {
	let strength = 0;

	// Evaluate the strength based on your desired criteria
	if ( password.length >= 8 ) {
		strength += 1;
	}
	if ( /[a-z]/.test( password ) ) {
		strength += 1;
	}
	if ( /[A-Z]/.test( password ) ) {
		strength += 1;
	}
	if ( /\d/.test( password ) ) {
		strength += 1;
	}
	if ( /[!@#$%^&*]/.test( password ) ) {
		strength += 1;
	}

	return strength;
}

function updatePasswordStrength( strength, passwordStrength ) {
	// Update the UI to reflect the password strength
	switch ( strength ) {
		case 0:
			passwordStrength.textContent = '';
			break;
		case 1:
			passwordStrength.style.color = '#FF0000';
			passwordStrength.textContent = 'Your password strength is weak';
			break;
		case 2:
			passwordStrength.style.color = '#FFBF00';
			passwordStrength.textContent = 'Your password strength is moderate';
			break;
		case 4:
			passwordStrength.style.color = '#00FF7F';
			passwordStrength.textContent = 'Your password strength is strong';
			break;
		case 5:
			passwordStrength.style.color = '#008000';
			passwordStrength.textContent =
				'Your password strength is very strong';
			break;
		default:
			break;
	}
}

const selectFieldContainer = document.getElementsByClassName(
	'srfm-classic-dropdown-container'
);

if ( selectFieldContainer ) {
	let i = 0;
	for ( const selectField of selectFieldContainer ) {
		const formElement = selectFieldContainer[ i ].closest( 'form' );
		// eslint-disable-next-line no-undef
		const computedStyle = getComputedStyle( formElement );
		const primaryColor = computedStyle.getPropertyValue(
			'--srfm-primary-color'
		);
		i++;
		const selectFieldButton = selectField.querySelector(
			'.srfm-classic-dropdown-button'
		);

		selectFieldButton.addEventListener( 'focus', () => {
			selectFieldButton
				.querySelector( '.srfm-classic-select-icon ' )
				.classList.add( 'srfm-rotate-180', '!srfm-pl-4' );

			const nextSibling = selectFieldButton.nextElementSibling;
			const options = nextSibling.querySelectorAll(
				'.srfm-classic-dropdown-option'
			);
			if ( '' === primaryColor ) {
				for ( let index = 0; index < options.length; index++ ) {
					options[ index ].classList.remove(
						'hover:!srfm-bg-srfm_primary_color'
					);
					options[ index ].classList.add(
						'hover:!srfm-bg-[#0084C7]'
					);
				}
			}
			const dropdownResultInput = selectField.querySelector(
				'.srfm-classic-dropdown-result'
			);
			nextSibling.style.display = 'block';
			if ( nextSibling ) {
				nextSibling.classList.add( '!srfm-opacity-100' );
				nextSibling.classList.add( '!srfm-z-10' );
				nextSibling.classList.remove( '!srfm-opacity-0' );

				const liElements = nextSibling.querySelectorAll( 'ul li' );
				liElements.forEach( ( li ) => {
					li.addEventListener( 'mousedown', ( event ) => {
						selectFieldButton
							.querySelector( '.srfm-classic-select-icon ' )
							.classList.remove(
								'srfm-rotate-180',
								'!srfm-pl-4'
							);
						selectFieldButton
							.querySelector( '.srfm-classic-select-icon ' )
							.classList.add( 'srfm-rotate-0' );
						const selectedValue = event.target.textContent.trim();
						selectFieldButton.querySelector(
							'.srfm-dropdown-value'
						).textContent = selectedValue;
						dropdownResultInput.value = selectedValue;

						nextSibling.classList.remove( '!srfm-opacity-100' );
						nextSibling.classList.remove( '!srfm-z-10' );
						nextSibling.classList.add( '!srfm-opacity-0' );
						nextSibling.style.display = 'none';
					} );
				} );
			}
		} );
		selectFieldButton.addEventListener( 'blur', () => {
			selectFieldButton
				.querySelector( '.srfm-classic-select-icon ' )
				.classList.remove( 'srfm-rotate-180', '!srfm-pl-4' );
			selectFieldButton
				.querySelector( '.srfm-classic-select-icon ' )
				.classList.add( 'srfm-rotate-0' );
			const nextSibling = selectFieldButton.nextElementSibling;
			nextSibling.classList.remove( '!srfm-opacity-100' );
			nextSibling.classList.remove( '!srfm-z-10' );
			nextSibling.classList.add( '!srfm-opacity-0' );
			nextSibling.style.display = 'none';
		} );
	}
}

//submit-button CSS

const submitButton = document.getElementsByClassName( 'srfm-button' );
if ( submitButton ) {
	// eslint-disable-next-line
	const rootStyles = getComputedStyle( document.documentElement );
	const primaryColorValue = rootStyles.getPropertyValue(
		'--srfm-primary-color'
	);
	const secondaryColorValue = rootStyles.getPropertyValue(
		'--srfm-secondary-color'
	);

	if ( primaryColorValue !== '' ) {
		for ( let i = 0; i < submitButton.length; i++ ) {
			submitButton[ i ].style.backgroundColor = primaryColorValue;
		}
	}
	if ( secondaryColorValue !== '' ) {
		for ( let i = 0; i < submitButton.length; i++ ) {
			submitButton[ i ].style.color = secondaryColorValue;
		}
	}
}

const numberSliderContainer = document.getElementsByClassName(
	'srfm-classic-number-slider'
);

if ( numberSliderContainer ) {
	// Bg Init
	const bgInit = ( element, val = 0, min = 0, max = 255, color ) => {
		color = color === '' ? '#0284c7' : color;
		// Background Change
		const valBg = ( ( val - min ) / ( max - min ) ) * 100;
		element.style.background = `linear-gradient(to right, ${ color } 0%, ${ color } ${ valBg }%, #fff ${ valBg }%, white 100%)`;
	};

	// Pre Init
	const preInit = ( sliderContainer, primaryColor ) => {
		const rangeSliders =
			sliderContainer.querySelector( '.srfm-range-slider' );
		const val = Number( rangeSliders.value );
		const min = Number( rangeSliders.getAttribute( 'min' ) );
		const max = Number( rangeSliders.getAttribute( 'max' ) );
		bgInit( rangeSliders, val, min, max, primaryColor );
	};

	// Toggle Class
	const init = ( sliderContainer, primaryColor ) => {
		// Slider Range Change or Input
		const rangeSliders =
			sliderContainer.querySelector( '.srfm-range-slider' );
		const numberInput = sliderContainer.querySelector(
			'.srfm-number-input-slider'
		);
		rangeSliders.addEventListener( 'input', function ( e ) {
			// Prevent Default
			e.preventDefault();
			e.stopPropagation();
			// Background Change
			const val = Number( rangeSliders.value );
			const min = Number( rangeSliders.getAttribute( 'min' ) );
			const max = Number( rangeSliders.getAttribute( 'max' ) );
			bgInit( rangeSliders, val, min, max, primaryColor );

			// Assign value to slider input
			numberInput.value = rangeSliders.value;
		} );
		// Input Slider Input
		numberInput.addEventListener( 'input', function ( event ) {
			// Prevent Default
			event.preventDefault();
			event.stopPropagation();

			// Background Change
			const thisInput = this;
			let inputVal = Number( thisInput.value );
			const inputMin = Number( thisInput.getAttribute( 'min' ) );
			const inputMax = Number( thisInput.getAttribute( 'max' ) );

			// Max Validation
			if ( inputVal > inputMax ) {
				inputVal = inputMax;
				thisInput.value = inputVal;
			}

			// Min Validation
			if ( inputVal < inputMin ) {
				inputVal = inputMin;
				thisInput.value = inputVal;
			}

			// Background Change
			const sliderEle = rangeSliders;
			bgInit( sliderEle, inputVal, inputMin, inputMax, primaryColor );

			// Assign value to slider range.
			sliderEle.value = inputVal;
		} );
	};
	for ( const numberSlider of numberSliderContainer ) {
		const formElement = numberSlider.closest( 'form' );
		// eslint-disable-next-line no-undef
		const computedStyle = getComputedStyle( formElement );
		const primaryColor = computedStyle.getPropertyValue(
			'--srfm-primary-color'
		);
		preInit( numberSlider, primaryColor );
		init( numberSlider, primaryColor );
	}
}

const datePickerContainers = document.getElementsByClassName(
	'srfm-classic-date-time-container'
);
if ( datePickerContainers ) {
	flatpickr( '.srfm-input-date-time', {
		enableTime: true,
		dateFormat: 'Y-m-d H:i',
	} );

	flatpickr( '.srfm-input-date' );

	flatpickr( '.srfm-input-time', {
		enableTime: true,
		noCalendar: true,
		dateFormat: 'H:i',
	} );

	for ( const datePickerContainer of datePickerContainers ) {
		const resultInput = datePickerContainer.querySelector(
			'.srfm-classic-date-time-result'
		);

		datePickerContainer.querySelector( '.srfm-input-data-time' ).onchange =
			function ( e ) {
				formattedDate = e.target.value.replaceAll( '/', '-' );
				resultInput.value = formattedDate;
			};
	}
}

const urlFiledContainers = document.getElementsByClassName(
	'srfm-classic-input-url-container'
);
if ( urlFiledContainers ) {
	for ( const urlFiledContainer of urlFiledContainers ) {
		const urlInput = urlFiledContainer.querySelector( '.srfm-url-input' );
		const validUrlMessage = urlFiledContainer.querySelector(
			'.srfm-validation-url-message'
		);
		urlInput.addEventListener( 'change', () => {
			const pattern = new RegExp(
				'^(https?:\\/\\/)?' + // protocol
					'((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
					'((\\d{1,3}\\.){3}\\d{1,3}))' + // OR IP (v4) address
					'(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
					'(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
					'(\\#[-a-z\\d_]*)?$', // fragment locator
				'i'
			);

			const isValidUrl = pattern.test( urlInput.value );
			if ( isValidUrl ) {
				validUrlMessage.style.display = 'none';
				urlInput.classList.remove(
					'!srfm-ring-red-500',
					'!srfm-border-red-500',
					'placeholder:!srfm-text-red-300'
				);
			} else {
				validUrlMessage.style.display = 'block';
				urlInput.classList.add(
					'!srfm-ring-red-500',
					'!srfm-border-red-500',
					'placeholder:!srfm-text-red-300'
				);
			}
		} );
	}
}
