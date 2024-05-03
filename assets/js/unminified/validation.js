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

export async function fieldValidation( formId, ajaxUrl, nonce, formContainer ) {
	let validateResult = false;
	let firstErrorInput = null;
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
	const fieldContainers = Array.from(
		formContainer.querySelectorAll( '.srfm-block-single' )
	);

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
		const inputField = container.querySelector( 'input, textarea,select' );
		const isRequired = inputField.getAttribute( 'aria-required' );
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

				if ( ! firstErrorInput ) {
					firstErrorInput = inputField;
				}
			} else if ( inputField ) {
				inputField
					.closest( '.srfm-block' )
					.classList.remove( 'srfm-error' );
			}

			// remove the error message on change of the input
			inputField.addEventListener( 'change', () => {
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

				errorMessage.textContent =
					errorMessage.getAttribute( 'data-unique-msg' );

				validateResult = true;
				if ( ! firstErrorInput ) {
					firstErrorInput = inputField;
				}
			} else if ( inputField ) {
				inputField
					.closest( '.srfm-block' )
					.classList.remove( 'srfm-error' );
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
				checkedInput[ 0 ].getAttribute( 'aria-required' );
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
					container.classList.add( 'srfm-error' );
				}
				validateResult = true;
				if ( ! firstErrorInput && visibleInput ) {
					firstErrorInput = visibleInput;
				}
			} else if ( errorMessage ) {
				container.classList.remove( 'srfm-error' );
			}

			// also remove the error message on change of the input
			checkedInput.forEach( ( input ) => {
				input.addEventListener( 'change', () => {
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
				if ( ! firstErrorInput ) {
					firstErrorInput = urlInput;
				}
			}

			// remove the error message on change of the url input
			urlInput.addEventListener( 'change', () => {
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
				if ( ! firstErrorInput ) {
					firstErrorInput = phoneInput;
				}
			}

			// remove the error message on change of the phone input
			const phoneInputs = container.querySelectorAll( 'input' );
			phoneInputs.forEach( ( input ) => {
				input.addEventListener( 'change', () => {
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

						if ( ! firstErrorInput ) {
							firstErrorInput = confirmValue;
						}
						validateResult = true;
					} else if ( confirmValue !== inputValue ) {
						confirmParent.classList.add( 'srfm-error' );
						confirmError.textContent =
							'Confirmation Password is not the same';

						if ( ! firstErrorInput ) {
							firstErrorInput = confirmValue;
						}
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

						if ( ! firstErrorInput ) {
							firstErrorInput = confirmInput;
						}
						validateResult = true;
					} else if ( confirmValue !== inputValue ) {
						confirmParent.classList.add( 'srfm-error' );
						confirmError.textContent =
							'Confirmation email is not the same';

						if ( ! firstErrorInput ) {
							firstErrorInput = confirmInput;
						}
						validateResult = true;
					} else {
						confirmParent.classList.remove( 'srfm-error' );
					}

					// remove the error message on change of the email confirm field
					confirmInput.addEventListener( 'change', () => {
						confirmParent.classList.remove( 'srfm-error' );
					} );
				}

				// remove the error message on change of the email main field
				const allInputFields =
					parent.querySelector( '.srfm-input-email' );
				allInputFields.addEventListener( 'change', () => {
					parent.classList.remove( 'srfm-error' );
				} );
			}
		}

		//Address Compact field
		if ( container.classList.contains( 'srfm-address-compact-block' ) ) {
			const addressInput = container.querySelectorAll( 'input' );
			const isAddressRequired =
				addressInput[ 1 ].getAttribute( 'aria-required' );

			const selectWrap = container.querySelector( '.ts-wrapper' );
			let hasItem = '';
			if ( selectWrap ) {
				hasItem = selectWrap.classList.contains( 'has-items' );
			}

			for (
				let i = 1;
				i < addressInput.length && isAddressRequired === 'true';
				i++
			) {
				if (
					( ! addressInput[ i ].value && i !== 2 && i !== 5 ) ||
					( i === 5 && ! hasItem )
				) {
					container.classList.add( 'srfm-error' );

					if ( ! firstErrorInput ) {
						firstErrorInput = addressInput[ i ];
					}

					validateResult = true;
					break;
				} else {
					container.classList.remove( 'srfm-error' );
				}
			}

			// Function to check if all inputs are filled
			function checkInputs() {
				const hasCountry = selectWrap.classList.contains( 'has-items' );

				if (
					addressInput[ 1 ].value &&
					addressInput[ 2 ].value &&
					addressInput[ 3 ].value &&
					addressInput[ 4 ].value &&
					hasCountry &&
					addressInput[ 6 ].value
				) {
					container.classList.remove( 'srfm-error' );
				}
			}

			// Add event listener to each input element to check if all inputs are filled
			addressInput.forEach( ( input ) => {
				input.addEventListener( 'change', checkInputs );
			} );

			// Create a mutation observer to watch for changes in the class of selectWrap
			const MutationObserver =
				window.MutationObserver ||
				window.WebKitMutationObserver ||
				window.MozMutationObserver;

			const observer = new MutationObserver( ( mutations ) => {
				mutations.forEach( ( mutation ) => {
					if ( mutation.attributeName === 'class' ) {
						checkInputs();
					}
				} );
			} );

			observer.observe( selectWrap, {
				attributes: true,
			} );
		}

		//Upload field
		if ( container.classList.contains( 'srfm-upload-block' ) ) {
			const uploadInput = container.querySelector( '.srfm-input-upload' );

			const isUploadRequired =
				uploadInput.getAttribute( 'aria-required' );
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
				if ( ! firstErrorInput ) {
					firstErrorInput = uploadInput;
				}
			} else if ( inputField ) {
				inputField
					.closest( '.srfm-block' )
					.classList.remove( 'srfm-error' );
			}

			// remove srfm-error class when file is selected
			uploadInput.addEventListener( 'change', () => {
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
			if ( inputValue ) {
				if ( min ) {
					if ( min !== '' && Number( inputValue ) < Number( min ) ) {
						inputField
							.closest( '.srfm-block' )
							.classList.add( 'srfm-error' );
						if ( errorMessage ) {
							errorMessage.textContent = `Minimum value is ${ min }`;
						}
					} else {
						inputField
							.closest( '.srfm-block' )
							.classList.remove( 'srfm-error' );
					}
				}

				if ( max ) {
					if ( max !== '' && Number( inputValue ) > Number( max ) ) {
						inputField
							.closest( '.srfm-block' )
							.classList.add( 'srfm-error' );

						if ( errorMessage ) {
							errorMessage.textContent = `Maximum value is ${ max }`;
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
			const ratingRequired = ratingInput.getAttribute( 'aria-required' );
			if ( ratingRequired === 'true' && ! ratingInput.value ) {
				ratingInput
					.closest( '.srfm-block' )
					.classList.add( 'srfm-error' );
				validateResult = true;
			} else {
				ratingInput
					.closest( '.srfm-block' )
					.classList.remove( 'srfm-error' );
			}
		}
	}

	if ( firstErrorInput ) {
		firstErrorInput.focus();
	}
	return validateResult;
}
