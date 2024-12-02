/**
 * Replaces "%s" or "%1$s", "%2$s", etc., placeholders in a string with provided arguments, in sequence.
 *
 * This function provides basic `sprintf`-style formatting, where each "%s" or "%n$s" placeholder in
 * the `str` parameter is replaced with corresponding values from `args`.
 *
 * @param {string}    str  - The string containing "%s" or "%n$s" placeholders to be replaced.
 * @param {...string} args - Values to replace each "%s" or "%n$s" placeholder in the string.
 *                         Each placeholder is replaced by the respective item in `args`.
 * @return {string} The formatted string with all placeholders replaced by corresponding `args` values.
 *
 * @example
 * srfmSprintfString('Page %1$s of %2$s', 5, 10);
 * // Returns: 'Page 5 of 10'
 */
function srfmSprintfString( str, ...args ) {
	let i = 0; // Initialize sequential index for unnumbered %s placeholders.
	return str.replace( /%(\d+\$)?s/g, ( match, index ) => {
		// If there's an index, use it (subtract 1 to make it 0-based); otherwise, use next sequential index
		const argIndex = index ? parseInt( index ) - 1 : i++;
		return args[ argIndex ] !== undefined ? args[ argIndex ] : match;
	} );
}

async function getUniqueValidationData( checkData, formId, ajaxUrl, nonce ) {
	let queryString =
		'action=validation_ajax_action&nonce=' +
		encodeURIComponent( nonce ) +
		'&id=' +
		encodeURIComponent( formId );

	// Add the data to the query string
	Object.keys( checkData ).forEach( ( key ) => {
		queryString +=
			'&' +
			encodeURIComponent( key ) +
			'=' +
			encodeURIComponent( checkData[ key ] );
	} );

	try {
		const response = await fetch( ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: queryString,
		} );

		if ( response.ok ) {
			const data = await response.json();
			return data.data;
		}
	} catch ( error ) {
		console.error( error );
	}
}

export async function fieldValidation(
	formId,
	ajaxUrl,
	nonce,
	formContainer,
	singleField = false
) {
	let validateResult = false;
	let firstErrorInput = null;
	let scrollElement = null;

	/**
	 * Additional validation object.
	 * This object is used to store additional validation data that can be used to validate fields.
	 */
	let additionalValidationObject = {};

	/**
	 * Sets the first error input and the element to scroll to.
	 * This function is used to identify the first input field that has an error
	 * and set it as the target for scrolling. It ensures that the user is
	 * directed to the first error input when validation fails.
	 *
	 * @param {HTMLElement} input         - The input element that has the error.
	 * @param {HTMLElement} element       - The element to scroll to, typically the same as the input or its container.
	 * @param {Object}      additionalObj - Additional validation object.
	 */
	const setFirstErrorInput = ( input, element, additionalObj = {} ) => {
		if ( ! firstErrorInput ) {
			firstErrorInput = input;
			scrollElement = element;
			additionalValidationObject = additionalObj;
		}
	};

	let uniqueEntryData = null;
	const uniqueFields = document.querySelectorAll(
		'input[data-unique="true"]'
	);
	if ( uniqueFields.length !== 0 ) {
		const uniqueValue = {};
		for ( const uniqueField of uniqueFields ) {
			const uniqueFieldName = uniqueField.name;
			const uniqueFieldValue = uniqueField.value;
			uniqueValue[ uniqueFieldName ] = uniqueFieldValue;
		}

		uniqueEntryData = await getUniqueValidationData(
			uniqueValue,
			formId,
			ajaxUrl,
			nonce
		);
	}

	const fieldContainers = singleField
		? [ formContainer ]
		: Array.from( formContainer.querySelectorAll( '.srfm-block-single' ) );

	for ( const container of fieldContainers ) {
		let skipValidation = false;
		if ( Array.isArray( window.sureforms?.skipValidationCallbacks ) ) {
			window.sureforms.skipValidationCallbacks.forEach(
				( skipValidationCallback ) => {
					if ( typeof skipValidationCallback === 'function' ) {
						skipValidation =
							skipValidation ||
							skipValidationCallback( container );
					}
				}
			);
		}

		if ( skipValidation ) {
			continue;
		}
		const currentForm = container.closest( 'form' );
		const currentFormId = currentForm.getAttribute( 'form-id' );

		if ( currentFormId !== formId ) {
			continue;
		}
		let inputField;
		// Determine the inputField based on the container's class.
		switch ( true ) {
			// Case 1: If the container corresponds to a phone number field.
			// This is because phone number containers have multiple inputs inside them,
			// and we specifically need to target the phone input using the class '.srfm-input-phone'.
			case container.classList.contains( 'srfm-phone-block' ):
				inputField = container.querySelector( '.srfm-input-phone' );
				break;
			// Default Case: For all other containers, select a general input element.
			// This handles standard input types such as text, textarea, or select.
			default:
				inputField = container.querySelector(
					'input, textarea, select'
				);
				break;
		}
		const isRequired = inputField.getAttribute( 'data-required' );
		const isUnique = inputField.getAttribute( 'data-unique' );
		let fieldName = inputField.getAttribute( 'name' );
		const inputValue = inputField.value;
		const errorMessage = container.querySelector( '.srfm-error-message' );

		if ( fieldName ) {
			fieldName = fieldName.replace( /_/g, ' ' );
		}

		// Checks if input if required is filled or not.
		if ( isRequired && inputField.type !== 'hidden' ) {
			if ( isRequired === 'true' && ! inputValue ) {
				if ( inputField ) {
					inputField
						.closest( '.srfm-block' )
						.classList.add( 'srfm-error' );
				}
				if ( errorMessage ) {
					errorMessage.textContent =
						errorMessage.getAttribute( 'data-error-msg' );
				}
				validateResult = true;
				// Set the first error input.
				setFirstErrorInput(
					inputField,
					inputField.closest( '.srfm-block' )
				);
			} else if ( inputField ) {
				inputField
					.closest( '.srfm-block' )
					.classList.remove( 'srfm-error' );
			}

			// remove the error message on input of the input
			inputField.addEventListener( 'input', () => {
				inputField
					.closest( '.srfm-block' )
					.classList.remove( 'srfm-error' );
			} );
		}

		// Checks if input is unique.
		if ( isUnique === 'true' && inputValue !== '' ) {
			const hasDuplicate = uniqueEntryData?.some(
				( entry ) => entry[ fieldName ] === 'not unique'
			);

			if ( hasDuplicate ) {
				if ( inputField ) {
					inputField
						.closest( '.srfm-block' )
						.classList.add( 'srfm-error' );
				}

				errorMessage.style.display = 'block';

				errorMessage.textContent =
					errorMessage.getAttribute( 'data-unique-msg' );

				validateResult = true;
				// Set the first error input.
				setFirstErrorInput(
					inputField,
					inputField.closest( '.srfm-block' )
				);
			} else if ( inputField ) {
				inputField
					.closest( '.srfm-block' )
					.classList.remove( 'srfm-error' );

				errorMessage.style.display = 'none';
			}
		}

		//Radio OR Checkbox or GDPR type field
		if (
			container.classList.contains( 'srfm-multi-choice-block' ) ||
			container.classList.contains( 'srfm-checkbox-block' ) ||
			container.classList.contains( 'srfm-gdpr-block' )
		) {
			const checkedInput = container.querySelectorAll( 'input' );
			const isCheckedRequired =
				checkedInput[ 0 ].getAttribute( 'data-required' );
			let checkedSelected = false;
			let visibleInput = null;

			for ( let i = 0; i < checkedInput.length; i++ ) {
				if ( ! visibleInput && checkedInput[ i ].type !== 'hidden' ) {
					visibleInput = checkedInput[ i ];
				}
				if ( checkedInput[ i ].checked ) {
					checkedSelected = true;
					break;
				}
			}

			if ( isCheckedRequired === 'true' && ! checkedSelected ) {
				if ( errorMessage ) {
					errorMessage.textContent = container
						.querySelector( '.srfm-error-message' )
						.getAttribute( 'data-error-msg' );
					container.classList.add( 'srfm-error' );
				}
				validateResult = true;

				// Set the first error input.
				setFirstErrorInput( visibleInput, container );
			} else if ( errorMessage ) {
				container.classList.remove( 'srfm-error' );
			}

			// also remove the error message on input of the input
			checkedInput.forEach( ( input ) => {
				input.addEventListener( 'input', () => {
					container.classList.remove( 'srfm-error' );
				} );
			} );
		}

		//Url field
		if ( container.classList.contains( 'srfm-url-block' ) ) {
			const urlInput = container.querySelector( 'input' );
			const urlError = container.classList.contains( 'srfm-url-error' );

			if ( urlError ) {
				container.classList.add( 'srfm-error' );
				validateResult = true;
				// Set the first error input.
				setFirstErrorInput( urlInput, container );
			}

			// remove the error message on input of the url input
			urlInput.addEventListener( 'input', () => {
				container.classList.remove( 'srfm-error' );
			} );
		}

		//Phone field
		if ( container.classList.contains( 'srfm-phone-block' ) ) {
			const phoneInput = container.querySelectorAll( 'input' )[ 1 ];
			const isIntelError =
				container.classList.contains( 'srfm-phone-error' );

			if ( isIntelError ) {
				container.classList.add( 'srfm-error' );
				validateResult = true;
				// Set the first error input.
				setFirstErrorInput( phoneInput, container );
			}

			// remove the error message on input of the phone input
			const phoneInputs = container.querySelectorAll( 'input' );
			phoneInputs.forEach( ( input ) => {
				input.addEventListener( 'input', () => {
					container.classList.remove( 'srfm-error' );
				} );
			} );
		}

		//Password field
		if ( container.classList.contains( 'srfm-password-block-wrap' ) ) {
			const parent = container;

			if ( parent ) {
				const confirmParent = parent.querySelector(
					'.srfm-password-confirm-block'
				);

				if ( confirmParent ) {
					const confirmValue = confirmParent.querySelector(
						'.srfm-input-password-confirm'
					).value;
					const confirmError = confirmParent.querySelector(
						'.srfm-error-message'
					);

					if (
						! confirmValue &&
						confirmError &&
						isRequired === 'true'
					) {
						confirmError.textContent =
							confirmError.getAttribute( 'data-error-msg' );
						confirmParent.classList.add( 'srfm-error' );
						// Set the first error input.
						setFirstErrorInput( confirmValue, confirmParent );
						validateResult = true;
					} else if ( confirmValue !== inputValue ) {
						confirmParent.classList.add( 'srfm-error' );
						confirmError.textContent =
							window?.srfm_submit?.messages?.confirm_password_same;

						// Set the first error input.
						setFirstErrorInput( confirmValue, confirmParent );
						validateResult = true;
					} else {
						confirmParent.classList.remove( 'srfm-error' );
					}
				}
			}
		}

		//Check for email
		if ( container.classList.contains( 'srfm-email-block-wrap' ) ) {
			const parent = container;
			if ( parent ) {
				const confirmParent = parent.querySelector(
					'.srfm-email-confirm-block'
				);

				if ( parent.classList.contains( 'srfm-valid-email-error' ) ) {
					// set the first error input.
					setFirstErrorInput( inputField, parent );
					validateResult = true;
				}

				if ( confirmParent ) {
					const confirmInput = confirmParent.querySelector(
						'.srfm-input-email-confirm'
					);
					const confirmValue = confirmParent.querySelector(
						'.srfm-input-email-confirm'
					).value;
					const confirmError = confirmParent.querySelector(
						'.srfm-error-message'
					);

					if (
						! confirmValue &&
						confirmError &&
						isRequired === 'true'
					) {
						confirmError.textContent =
							confirmError.getAttribute( 'data-error-msg' );
						confirmParent.classList.add( 'srfm-error' );

						// Set the first error input.
						setFirstErrorInput( confirmInput, confirmParent );
						validateResult = true;
					} else if ( confirmValue !== inputValue ) {
						confirmParent.classList.add( 'srfm-error' );
						confirmError.textContent =
							window?.srfm_submit?.messages?.confirm_email_same;

						// Set the first error input.
						setFirstErrorInput( confirmInput, confirmParent );
						validateResult = true;
					} else {
						confirmParent.classList.remove( 'srfm-error' );
					}

					// remove the error message on input of the email confirm field
					confirmInput.addEventListener( 'input', () => {
						confirmParent.classList.remove( 'srfm-error' );
					} );
				}

				// remove the error message on input of the email main field
				const allInputFields =
					parent.querySelector( '.srfm-input-email' );
				allInputFields.addEventListener( 'input', () => {
					parent.classList.remove( 'srfm-error' );
				} );
			}
		}

		// Upload field
		if ( container.classList.contains( 'srfm-upload-block' ) ) {
			const uploadInput = container.querySelector( '.srfm-input-upload' );

			const isUploadRequired =
				uploadInput.getAttribute( 'data-required' );
			if ( 'true' === isUploadRequired && ! uploadInput.value ) {
				if ( 'true' === isUploadRequired ) {
					if ( errorMessage ) {
						errorMessage.textContent =
							errorMessage.getAttribute( 'data-error-msg' );
					}
				}

				if ( inputField ) {
					inputField
						.closest( '.srfm-block' )
						.classList.add( 'srfm-error' );
				}

				validateResult = true;
				// Set the first error input.
				setFirstErrorInput( uploadInput, container );
			} else if ( inputField ) {
				inputField
					.closest( '.srfm-block' )
					.classList.remove( 'srfm-error' );
			}

			// remove srfm-error class when file is selected
			uploadInput.addEventListener( 'input', () => {
				if ( inputField ) {
					inputField
						.closest( '.srfm-block' )
						.classList.remove( 'srfm-error' );
				}
			} );
		}

		// Number field.
		if ( container.classList.contains( 'srfm-number-block' ) ) {
			const min = inputField.getAttribute( 'min' );
			const max = inputField.getAttribute( 'max' );
			const formatType = inputField.getAttribute( 'format-type' );

			if ( inputValue ) {
				// Normalize the number value as per the format type.
				const normalizedInputValue =
					'eu-style' === formatType
						? parseFloat(
							inputValue
								.replace( /\./g, '' )
								.replace( ',', '.' )
						  )
						: parseFloat( inputValue.replace( /,/g, '' ) );

				if ( min ) {
					if (
						min !== '' &&
						Number( normalizedInputValue ) < Number( min )
					) {
						inputField
							.closest( '.srfm-block' )
							.classList.add( 'srfm-error' );
						if ( errorMessage ) {
							errorMessage.textContent = srfmSprintfString(
								window?.srfm_submit?.messages?.input_min_value,
								min
							);
						}
					} else {
						inputField
							.closest( '.srfm-block' )
							.classList.remove( 'srfm-error' );
					}
				}

				if ( max ) {
					if (
						max !== '' &&
						Number( normalizedInputValue ) > Number( max )
					) {
						inputField
							.closest( '.srfm-block' )
							.classList.add( 'srfm-error' );

						if ( errorMessage ) {
							errorMessage.textContent = srfmSprintfString(
								window?.srfm_submit?.messages?.input_max_value,
								max
							);
						}
					} else {
						inputField
							.closest( '.srfm-block' )
							.classList.remove( 'srfm-error' );
					}
				}
			}
		}

		//rating field
		if ( container.classList.contains( 'srfm-rating-block' ) ) {
			const ratingInput = container.querySelector( '.srfm-input-rating' );
			const ratingRequired = ratingInput.getAttribute( 'data-required' );
			if ( ratingRequired === 'true' && ! ratingInput.value ) {
				ratingInput
					.closest( '.srfm-block' )
					.classList.add( 'srfm-error' );
				validateResult = true;
				// Set the first error input.
				setFirstErrorInput(
					container.querySelector( '.srfm-icon' ),
					container
				);
			} else {
				ratingInput
					.closest( '.srfm-block' )
					.classList.remove( 'srfm-error' );
			}
		}

		// Slider Field.
		if ( container.classList.contains( 'srfm-slider-block' ) ) {
			const isSliderRequired = container.getAttribute( 'data-required' );
			const sliderInput = container.querySelector( '.srfm-input-slider' );
			const textSliderElement =
				container.querySelector( '.srfm-text-slider' );
			const sliderDefault = container.getAttribute( 'data-default' );
			if ( isSliderRequired === 'true' ) {
				let hasError = false;
				if (
					sliderInput &&
					! sliderInput.dataset.interacted &&
					( ! sliderDefault || sliderDefault === 'false' )
				) {
					hasError = true;
				} else if (
					textSliderElement &&
					! textSliderElement.dataset.interacted &&
					( ! sliderDefault || sliderDefault === 'false' )
				) {
					hasError = true;
				}

				if ( hasError ) {
					container.classList.add( 'srfm-error' );
					validateResult = true;

					// Set the first error input.
					setFirstErrorInput( sliderInput, container );
				} else {
					container.classList.remove( 'srfm-error' );
				}
			}
		}

		// Dropdown Field
		if ( container.classList.contains( 'srfm-dropdown-block' ) ) {
			const dropdownInputs = container.querySelectorAll(
				'.srfm-input-dropdown-hidden'
			);
			// Create a mutation observer to watch for changes in the value of the hidden input.
			const MutationObserver =
				window.MutationObserver ||
				window.WebKitMutationObserver ||
				window.MozMutationObserver;

			dropdownInputs.forEach( ( dropdownInput ) => {
				const dropdownRequired =
					dropdownInput.getAttribute( 'data-required' );
				const inputName = dropdownInput.getAttribute( 'name' );
				if ( dropdownRequired === 'true' && ! dropdownInput.value ) {
					errorMessage.textContent =
						errorMessage.getAttribute( 'data-error-msg' );
					dropdownInput
						.closest( '.srfm-block' )
						.classList.add( 'srfm-error' );
					validateResult = true;
				} else if ( dropdownInput.value ) {
					const minSelection =
						dropdownInput.getAttribute( 'data-min-selection' );
					const maxSelection =
						dropdownInput.getAttribute( 'data-max-selection' );

					if ( minSelection || maxSelection ) {
						// create array from dropdownInput.value
						const selectedOptions =
							dropdownInput.value.split( ',' );
						// If some value is selected but less than minSelection.
						if (
							minSelection &&
							selectedOptions.length < minSelection
						) {
							errorMessage.textContent = srfmSprintfString(
								window?.srfm_submit?.messages
									?.dropdown_min_selections,
								minSelection
							);
							dropdownInput
								.closest( '.srfm-block' )
								.classList.add( 'srfm-error' );
							validateResult = true;
						} else if (
							maxSelection &&
							selectedOptions.length > maxSelection
						) {
							// If some value is selected but more than maxSelection.
							errorMessage.textContent = srfmSprintfString(
								window?.srfm_submit?.messages
									?.dropdown_max_selections,
								maxSelection
							);
							dropdownInput
								.closest( '.srfm-block' )
								.classList.add( 'srfm-error' );
							validateResult = true;
						}
					}
				} else {
					dropdownInput
						.closest( '.srfm-block' )
						.classList.remove( 'srfm-error' );
				}

				if ( validateResult ) {
					/**
					 * Set the first error input element.
					 *
					 * We are retrieving the input element from the third-party library's global `window.srfm` object.
					 * The input instance is stored within the `srfm` object, where `inputName` corresponds to the specific
					 * input field. We focus on this input if it exists. If not found, we fallback to a default `dropdownInput`.
					 */
					const inputElement =
						window?.srfm?.[ inputName ] || dropdownInput;
					setFirstErrorInput(
						inputElement,
						dropdownInput.closest( '.srfm-block' ),
						{
							shouldDelayOnFocus: true,
						}
					);
				}

				// Observe changes in the hidden input's value.
				const dropdownObserver = new MutationObserver( () => {
					if ( dropdownInput.value ) {
						dropdownInput
							.closest( '.srfm-block' )
							.classList.remove( 'srfm-error' );
					} else if ( dropdownRequired === 'true' ) {
						dropdownInput
							.closest( '.srfm-block' )
							.classList.add( 'srfm-error' );
					}
				} );

				// Configure the observer to watch for changes in attributes.
				dropdownObserver.observe( dropdownInput, {
					attributes: true,
					attributeFilter: [ 'value' ],
				} );
			} );
		}

		// Validation for minimum and maximum selection for multi choice field
		if ( container.classList.contains( 'srfm-multi-choice-block' ) ) {
			const checkedInput = container.querySelectorAll( 'input' );
			const minSelection =
				checkedInput[ 0 ].getAttribute( 'data-min-selection' );
			const maxSelection =
				checkedInput[ 0 ].getAttribute( 'data-max-selection' );
			let visibleInput = null;
			let totalCheckedInput = 0;
			let errorFound = false;

			for ( let i = 0; i < checkedInput.length; i++ ) {
				if ( ! visibleInput && checkedInput[ i ].type !== 'hidden' ) {
					visibleInput = checkedInput[ i ];
				}
				if ( checkedInput[ i ].checked ) {
					totalCheckedInput++;
				}
			}

			if ( ( minSelection || maxSelection ) && totalCheckedInput > 0 ) {
				if (
					! errorFound &&
					minSelection > 0 &&
					( ( isRequired && minSelection > 1 ) || ! isRequired ) &&
					totalCheckedInput < minSelection
				) {
					errorMessage.textContent = srfmSprintfString(
						window?.srfm_submit?.messages?.multi_choice_min_selections,
						minSelection
					);
					errorFound = true;
				}
				if (
					! errorFound &&
					maxSelection > 0 &&
					totalCheckedInput > maxSelection
				) {
					errorMessage.textContent = srfmSprintfString(
						window?.srfm_submit?.messages?.multi_choice_max_selections,
						maxSelection
					);
					errorFound = true;
				}

				if ( errorFound ) {
					container.classList.add( 'srfm-error' );
					setFirstErrorInput( visibleInput, container );
					validateResult = true;
				} else if ( ! isRequired ) {
					container.classList.remove( 'srfm-error' );
				}
			}
		}
	}

	/**
	 * If the validation fails, return validateResult, firstErrorInput and scrollElement
	 *  validateResult: if validation fails return true || default value is false
	 *  firstErrorInput: the first input field that has an error
	 *  scrollElement: the element to scroll to
	 */
	return validateResult
		? {
			validateResult,
			firstErrorInput,
			scrollElement,
			...additionalValidationObject,
		  }
		: false;
}

/**
 * Initialize inline field validation
 */
export function initializeInlineFieldValidation() {
	// Array of all the fields classes that needs to be validated
	const srfmFields = [
		'srfm-input-block',
		'srfm-email-block-wrap',
		'srfm-url-block',
		'srfm-phone-block',
		'srfm-checkbox-block',
		'srfm-gdpr-block',
		'srfm-number-block',
		'srfm-multi-choice-block',
		'srfm-datepicker-block',
		'srfm-upload-block',
		'srfm-rating-block',
		'srfm-textarea-block',
		'srfm-dropdown-block',
		'srfm-slider-block',
	];

	srfmFields.forEach( ( block ) => addBlurListener( block, `.${ block }` ) );
}

/**
 * Add blur listeners to all fields
 * That shows validation errors on blur
 *
 * @param {string} containerClass
 * @param {string} blockClass
 */
function addBlurListener( containerClass, blockClass ) {
	const container = Array.from(
		document.getElementsByClassName( containerClass )
	);

	if ( container ) {
		for ( const areaInput of container ) {
			let areaField =
				areaInput.querySelector( 'input' ) ||
				areaInput.querySelector( 'textarea' ) ||
				areaInput.querySelector( 'select' );

			// upload block
			if ( containerClass === 'srfm-upload-block' ) {
				areaField = areaInput.querySelector( 'input[type="file"]' );
			}

			// rating block
			if ( containerClass === 'srfm-rating-block' ) {
				addRatingBlurListener( areaField, areaInput, blockClass );
			}

			// multi choice block
			if ( containerClass === 'srfm-multi-choice-block' ) {
				addMultiChoiceBlurListener( areaField, areaInput, blockClass );
			}

			// Function to validate email inputs within the email block
			if ( containerClass === 'srfm-email-block-wrap' ) {
				addEmailBlurListener( areaInput, blockClass );
			}

			// Function to validate slider inputs within the slider block
			if ( containerClass === 'srfm-slider-block' ) {
				addSliderBlurListener( areaField, areaInput, blockClass );
			}

			// Function to validate dropdown blur
			// on tom-select blur event.
			if ( containerClass === 'srfm-dropdown-block' ) {
				const blockName = areaField.getAttribute( 'name' );
				setTimeout( () => {
					window?.srfm?.[ blockName ]?.on( 'blur', function () {
						fieldValidationInit( areaField, blockClass );
					} );
				}, 500 );
			}

			// First input element is search for phone number block so reassigning it with phone number input for proper validation.
			if ( containerClass === 'srfm-phone-block' ) {
				areaField = areaInput.querySelector( '.srfm-input-phone' );
			}

			// for all other fields
			if ( areaField ) {
				areaField.addEventListener( 'blur', async function () {
					fieldValidationInit( areaField, blockClass );
				} );
			}
		}
	}
}

/**
 * Add blur listeners to rating fields
 * That shows validation errors on blur
 *
 * @param {HTMLElement} areaField
 * @param {HTMLElement} areaInput
 * @param {string}      blockClass
 */
function addRatingBlurListener( areaField, areaInput, blockClass ) {
	areaField = areaInput.querySelectorAll( '.srfm-icon' );

	areaField.forEach( ( field, index ) => {
		if ( index === areaField.length - 1 ) {
			field.addEventListener( 'blur', async function () {
				fieldValidationInit( field, blockClass );
			} );
		}
	} );
}

/**
 * Add blur listeners to multi choice fields
 * That shows validation errors on blur
 *
 * @param {HTMLElement} areaField
 * @param {HTMLElement} areaInput
 * @param {string}      blockClass
 */
function addMultiChoiceBlurListener( areaField, areaInput, blockClass ) {
	areaField = areaInput.querySelectorAll( '.srfm-input-multi-choice-single' );
	areaField.forEach( ( field ) => {
		field.addEventListener( 'blur', async function () {
			fieldValidationInit( field, blockClass );
		} );
	} );
}

/**
 * Add blur listeners to email fields
 * That shows validation errors on blur and also check if it is valid email
 *
 * @param {HTMLElement} areaInput
 * @param {string}      blockClass
 *
 * @return {void}
 */
function addEmailBlurListener( areaInput, blockClass ) {
	const emailInputs = areaInput.querySelectorAll( 'input' );
	const parentBlock = areaInput.closest( blockClass );

	emailInputs.forEach( ( emailField ) => {
		emailField.addEventListener( 'input', async function () {
			// Trim and lowercase the email input value
			emailField.value = emailField.value.trim().toLowerCase();

			// Regular expression for validating email
			const emailRegex =
				/^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			let isValidEmail = false;
			if ( emailRegex.test( emailField.value ) ) {
				isValidEmail = true;
			}

			// Determine the relevant block (normal email or confirmation email)
			const inputBlock = emailField.classList.contains(
				'srfm-input-email-confirm'
			)
				? parentBlock.querySelector( '.srfm-email-confirm-block' )
				: parentBlock.querySelector( '.srfm-email-block' );

			const errorContainer = inputBlock.querySelector(
				'.srfm-error-message'
			);

			// If the email field is empty, remove the error class and hide error message
			if ( ! emailField.value ) {
				errorContainer.style.display = 'none';
				inputBlock.classList.remove( 'srfm-valid-email-error' );
			}

			// Handle email confirmation field validation
			if ( emailField.classList.contains( 'srfm-input-email-confirm' ) ) {
				const originalEmailField =
					parentBlock.querySelector( '.srfm-input-email' );
				const confirmEmailBlock = parentBlock.querySelector(
					'.srfm-email-confirm-block'
				);
				const confirmErrorContainer = confirmEmailBlock.querySelector(
					'.srfm-error-message'
				);
				const originalEmailValue = originalEmailField.value;

				if ( originalEmailValue !== emailField.value ) {
					confirmErrorContainer.style.display = 'block';
					confirmErrorContainer.textContent =
						window?.srfm_submit?.messages?.confirm_email_same;
					parentBlock.classList.add( 'srfm-error' );
					return;
				}
				parentBlock.classList.remove( 'srfm-error' );
				confirmErrorContainer.textContent = '';
				confirmErrorContainer.style.display = 'none';
			}

			// Handle general email validation
			if ( ! isValidEmail ) {
				inputBlock.parentElement.classList.add(
					'srfm-valid-email-error'
				);
				errorContainer.style.display = 'block';
				errorContainer.innerHTML =
					window?.srfm_submit?.messages?.valid_email;
			} else {
				errorContainer.style.display = 'none';
				inputBlock.parentElement.classList.remove(
					'srfm-valid-email-error'
				);
			}
		} );
	} );
}

/**
 * Add blur listeners to slider fields
 * That shows validation errors on blur.
 *
 * @param {HTMLElement} areaField
 * @param {HTMLElement} areaInput
 * @param {string}      blockClass
 */
function addSliderBlurListener( areaField, areaInput, blockClass ) {
	const sliderInput = areaInput.querySelector( '.srfm-input-slider' );
	const textSliderElement = areaInput.querySelector( '.srfm-text-slider' );
	// Number slider
	if ( sliderInput ) {
		sliderInput.addEventListener( 'blur', async function () {
			fieldValidationInit( sliderInput, blockClass );
		} );
	}

	// Text slider
	if ( textSliderElement ) {
		const sliderThumb =
			textSliderElement.querySelector( '.srfm-slider-thumb' );
		if ( sliderThumb ) {
			sliderThumb.addEventListener( 'blur', async function () {
				fieldValidationInit( sliderThumb, blockClass );
			} );
		}
	}
}

/**
 * Initialize field validation
 *
 * @param {HTMLElement} areaField
 * @param {string}      blockClass
 */
const fieldValidationInit = async ( areaField, blockClass ) => {
	const formTextarea = areaField.closest( blockClass );
	const form = formTextarea.closest( 'form' );
	const formId = form.getAttribute( 'form-id' );
	const ajaxUrl = form.getAttribute( 'ajaxurl' );
	const nonce = form.getAttribute( 'nonce' );
	const singleField = true;

	await fieldValidation( formId, ajaxUrl, nonce, formTextarea, singleField );
};

/**
 * Scrolls to the first input error and focuses on it if necessary.
 *
 * @param {Object}      validationObject                            - The validation object containing error details and settings.
 * @param {HTMLElement} validationObject.firstErrorInput            - The first input field that has an error.
 * @param {HTMLElement} [validationObject.scrollElement]            - The element to scroll into view if present.
 * @param {boolean}     [validationObject.shouldDelayOnFocus=false] - Whether to delay focusing on the input field.
 */
export const handleScrollAndFocusOnError = ( validationObject ) => {
	// If the first error input is available
	if ( validationObject?.firstErrorInput ) {
		// If the scroll element exists, smoothly scroll the element into view
		if ( validationObject?.scrollElement ) {
			/**
			 * Scrolls the window to center the specified element in the viewport with a smooth animation.
			 *
			 * @param {HTMLElement} validationObject.scrollElement - The element to scroll to.
			 */
			const getElementTop =
				validationObject.scrollElement.getBoundingClientRect().top; // Get element's top position relative to the viewport
			const getPageYOffset = window.pageYOffset; // Get the current vertical scroll position of the window
			const getWindowHeight = window.innerHeight; // Get the height of the browser window
			const getHalfWindowHeight = getWindowHeight / 2; // Calculate half of the window height for centering

			// Calculate the scroll position to align the element in the center of the viewport
			const calculatedScrollTop =
				getElementTop + getPageYOffset - getHalfWindowHeight;

			window.scroll( {
				top: calculatedScrollTop, // Set the calculated top scroll position
				behavior: 'smooth', // Smooth scrolling animation
			} );
		}

		// Check if we should delay the focus on the error input
		if ( validationObject?.shouldDelayOnFocus ) {
			// Delay focusing the input by 500ms to allow for the scroll animation to complete
			setTimeout( () => {
				validationObject.firstErrorInput.focus( {
					preventScroll: true,
				} ); // Focus without scrolling
			}, 500 );
		} else {
			// Immediately focus the error input without scrolling
			validationObject.firstErrorInput.focus( { preventScroll: true } );
		}
	}
};
