/* eslint-disable no-undef */
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

async function fieldValidation( formId, ajaxUrl, nonce, formContainer ) {
	let validateResult = false;
	let firstErrorInput = null;
	let uniqueEntryData = null;
	const uniqueFields = document.querySelectorAll(
		'input[aria-unique="true"]'
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
		const currentForm = container.closest( 'form' );
		const currentFormId = currentForm.getAttribute( 'form-id' );

		if ( currentFormId !== formId ) {
			continue;
		}
		const inputField = container.querySelector( 'input, textarea,select' );
		const isRequired = inputField.getAttribute( 'aria-required' );
		const isUnique = inputField.getAttribute( 'aria-unique' );
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

		//Radio OR Checkbox type field
		if (
			container.classList.contains( 'srfm-multi-choice-block' ) ||
			container.classList.contains( 'srfm-checkbox-block' )
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
				}
			}
		}

		//Address field
		if ( container.classList.contains( 'srfm-address-block' ) ) {
			const addressInput = container.querySelectorAll( 'input,select' );
			const isAddressRequired =
				addressInput[ 1 ].getAttribute( 'aria-required' );

			for (
				let i = 1;
				i < addressInput.length && isAddressRequired === 'true';
				i++
			) {
				if ( ! addressInput[ i ].value && 2 !== i ) {
					container.classList.add( 'srfm-error' );
					validateResult = true;

					if ( ! firstErrorInput ) {
						firstErrorInput = addressInput[ i ];
					}
				} else {
					container.classList.remove( 'srfm-error' );
				}
			}
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

document.addEventListener( 'DOMContentLoaded', function () {
	const forms = Array.from( document.querySelectorAll( '.srfm-form' ) );
	for ( const form of forms ) {
		const {
			formId,
			submitType,
			successUrl,
			ajaxUrl,
			nonce,
			loader,
			successMessage,
			errorMessage,
			submitBtn,
			siteKey,
			recaptchaType,
		} = extractFormAttributesAndElements( form );

		if ( recaptchaType === 'v3-reCAPTCHA' ) {
			//For V3 recaptcha
			submitBtn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				grecaptcha.ready( function () {
					grecaptcha
						.execute( siteKey, { action: 'submit' } )
						.then( async function ( token ) {
							if ( token ) {
								handleFormSubmission(
									form,
									formId,
									ajaxUrl,
									nonce,
									loader,
									successUrl,
									successMessage,
									errorMessage,
									submitType
								);
							}
						} );
				} );
			} );
		} else {
			form.addEventListener( 'submit', async function ( e ) {
				e.preventDefault();
				handleFormSubmission(
					form,
					formId,
					ajaxUrl,
					nonce,
					loader,
					successUrl,
					successMessage,
					errorMessage,
					submitType
				);
			} );
		}
	}
} );

function submitFormData( form ) {
	const site_url = sureforms_submit.site_url;

	const formData = new FormData( form );
	return fetch( `${ site_url }/wp-json/sureforms/v1/submit-form`, {
		method: 'POST',
		body: formData,
	} )
		.then( ( response ) => {
			if ( response.ok ) {
				return response;
			}
		} )
		.catch( ( e ) => {
			console.log( e );
		} );
}

function showSuccessMessage( element, form ) {
	form.style.opacity = 1;
	form.style.display = 'none';
	element.classList.add( 'srfm-active' );

	setTimeout( () => {
		element.style.opacity = 1;
	}, 500 );
}

function redirectToUrl( url ) {
	window.location.assign( url );
}

function showErrorMessage( element ) {
	element.removeAttribute( 'hidden' );
	console.error( 'Network Error' );
}

async function handleFormSubmission(
	form,
	formId,
	ajaxUrl,
	nonce,
	loader,
	successUrl,
	successMessage,
	errorMessage,
	submitType
) {
	try {
		loader.classList.add( 'srfm-active' );

		const isValidate = await fieldValidation(
			formId,
			ajaxUrl,
			nonce,
			form
		);

		if ( isValidate ) {
			loader.classList.remove( 'srfm-active' );
			return;
		}

		const formStatus = await submitFormData( form );

		loader.classList.add( 'srfm-active' );
		if ( formStatus ) {
			if ( submitType === 'message' ) {
				showSuccessMessage( successMessage, form );
			} else {
				redirectToUrl( successUrl );
			}
		} else {
			showErrorMessage( errorMessage );
		}
	} catch ( error ) {
		loader.classList.remove( 'srfm-active' );
		showErrorMessage( errorMessage );
	}
}

function extractFormAttributesAndElements( form ) {
	const formId = form.getAttribute( 'form-id' );
	const submitType = form.getAttribute( 'message-type' );
	const successUrl = form.getAttribute( 'success-url' );
	const ajaxUrl = form.getAttribute( 'ajaxurl' );
	const nonce = form.getAttribute( 'nonce' );
	const loader = form.querySelector( '.srfm-loader' );
	const successMessage = form.nextElementSibling;
	const errorMessage = form.querySelector( '.srfm-error-message' );
	const submitBtn = form.querySelector( '#srfm-submit-btn' );
	const siteKey = submitBtn.getAttribute( 'data-sitekey' );
	const recaptchaType = submitBtn.getAttribute( 'recaptcha-type' );

	return {
		formId,
		submitType,
		successUrl,
		ajaxUrl,
		nonce,
		loader,
		successMessage,
		errorMessage,
		submitBtn,
		siteKey,
		recaptchaType,
	};
}

// eslint-disable-next-line no-unused-vars
function onloadCallback() {
	const forms = Array.from( document.querySelectorAll( '.srfm-form' ) );

	forms.forEach( ( form ) => {
		const {
			formId,
			submitType,
			successUrl,
			ajaxUrl,
			nonce,
			loader,
			successMessage,
			errorMessage,
			submitBtn,
			siteKey,
			recaptchaType,
		} = extractFormAttributesAndElements( form );
		let isRecaptchaRender = false;
		if ( recaptchaType === 'v2-invisible' ) {
			grecaptcha.render( submitBtn, {
				sitekey: siteKey,
				callback: () => {
					handleFormSubmission(
						form,
						formId,
						ajaxUrl,
						nonce,
						loader,
						successUrl,
						successMessage,
						errorMessage,
						submitType
					);
					isRecaptchaRender = true;
				},
			} );

			submitBtn.addEventListener( 'click', () => {
				if ( isRecaptchaRender ) {
					handleFormSubmission(
						form,
						formId,
						ajaxUrl,
						nonce,
						loader,
						successUrl,
						successMessage,
						errorMessage,
						submitType
					);
				}
			} );
		}
	} );
}

export { getUniqueValidationData, fieldValidation, onloadCallback };
