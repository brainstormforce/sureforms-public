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
		formContainer.querySelectorAll( '.main-container' )
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
		const errorMessage = container.querySelector( '.error-message' );
		const errorInputIcon = container.querySelector(
			'.sf-input-error-icon'
		);
		const duplicateMessage =
			container.querySelector( '.duplicate-message' );
		const isAllowDecimal = inputField.getAttribute( 'format-type' );
		if ( fieldName ) {
			fieldName = fieldName.replace( /_/g, ' ' );
		}
		if ( isRequired && inputField.type !== 'hidden' ) {
			if ( isRequired === 'true' && ! inputValue ) {
				if ( errorMessage ) {
					errorMessage.style.display = 'block';
				}
				// might be used later
				if ( duplicateMessage ) {
					duplicateMessage.style.display = 'none';
				}
				// inputField.style.borderColor = '#FCA5A5';
				if ( inputField ) {
					inputField.classList.add( 'sf-classic-input-error' );
				}
				if ( errorInputIcon ) {
					errorInputIcon.style.display = 'flex';
				}
				validateResult = true;
				if ( ! firstErrorInput ) {
					firstErrorInput = inputField;
				}
			} else {
				if ( inputField ) {
					inputField.classList.remove( 'sf-classic-input-error' );
				}
				if ( errorInputIcon ) {
					errorInputIcon.style.display = 'none';
				}
				if ( errorMessage ) {
					errorMessage.style.display = 'none';
				}
				// inputField.style.borderColor = '#d1d5db';
			}
		}

		if ( isUnique === 'true' && inputValue !== '' ) {
			const hasDuplicate = uniqueEntryData?.some(
				( entry ) => entry[ fieldName ] === 'not unique'
			);
			const phoneParent = container.querySelector(
				'#sureforms-phone-parent'
			);
			if ( hasDuplicate ) {
				if ( duplicateMessage ) {
					duplicateMessage.style.display = 'block';
				}
				if ( inputField ) {
					inputField.classList.add( 'sf-classic-input-error' );
				}
				if ( errorInputIcon ) {
					errorInputIcon.style.display = 'flex';
				}
				if ( phoneParent ) {
					phoneParent.classList.add(
						'!ring-red-500',
						'!border-red-500'
					);
				}
				validateResult = true;
				if ( ! firstErrorInput ) {
					firstErrorInput = inputField;
				}
			} else {
				if ( duplicateMessage ) {
					duplicateMessage.style.display = 'none';
				}
				if ( inputField ) {
					inputField.classList.remove( 'sf-classic-input-error' );
				}
				if ( errorInputIcon ) {
					errorInputIcon.style.display = 'none';
				}
				if ( phoneParent ) {
					phoneParent.classList.remove(
						'!ring-red-500',
						'!border-red-500'
					);
				}
			}
		}

		//Radio OR Chekcbox type field
		if (
			container.classList.contains( 'sureforms-rating-container' ) ||
			container.classList.contains(
				'sureforms-multi-choice-container'
			) ||
			container.classList.contains( 'sureforms-checkbox-container' ) ||
			container.classList.contains( 'sureforms-switch-container' )
		) {
			const checkedInput = container.querySelectorAll( 'input' );
			const ischeckedRequired =
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

			if ( ischeckedRequired === 'true' && ! checkedSelected ) {
				errorMessage.style.display = 'block';
				validateResult = true;
				if ( ! firstErrorInput && visibleInput ) {
					firstErrorInput = visibleInput;
				}
			} else {
				errorMessage.style.display = 'none';
			}
		}

		//phone field
		if (
			container.classList.contains( 'sureforms-input-phone-container' )
		) {
			const phoneInput = container.querySelectorAll( 'input' )[ 1 ];
			const isPhoneRequired = phoneInput.getAttribute( 'aria-required' );
			if ( isPhoneRequired === 'true' && ! inputValue ) {
				errorMessage.style.display = 'block';
				duplicateMessage.style.display = 'none';
				validateResult = true;
				// we might be needing that later.
				// phoneInput.parentElement.style.borderColor = '#FCA5A5';
				const phoneParent = container.querySelector(
					'#sureforms-phone-parent'
				);
				if ( phoneParent ) {
					phoneParent.classList.add(
						'!ring-red-500',
						'!border-red-500'
					);
					phoneInput.classList.add( 'placeholder:!text-red-300' );
				}
				if ( errorInputIcon ) {
					errorInputIcon.style.display = 'flex';
				}
				// phoneInput.style.color = 'red';
				if ( ! firstErrorInput ) {
					firstErrorInput = inputField;
				}
			} else {
				errorMessage.style.display = 'none';
				//for Tailwind phone field UI
				const phoneParent = container.querySelector(
					'#sureforms-phone-parent'
				);
				if ( isUnique !== 'true' && phoneParent ) {
					phoneParent.classList.remove(
						'!ring-red-500',
						'!border-red-500'
					);
					phoneInput.classList.remove( 'placeholder:!text-red-300' );
				}
			}
		}

		if ( isAllowDecimal === 'decimal' && inputValue !== '' ) {
			// Add .00 if no decimal point exists
			if ( ! inputValue.includes( '.' ) ) {
				inputField.value += '.00';
			}
		}

		//check for password field
		if (
			container.classList.contains( 'sureforms-input-password-container' )
		) {
			const confirmPassword = container.querySelector(
				'.sureforms-confirm-input-password'
			);
			if ( container.querySelector( '.info-icon' ) ) {
				container.querySelector( '.info-icon' ).style.display = 'none';
			}
			if ( confirmPassword ) {
				const confirmPasswordValue = confirmPassword.value;
				const confirmFieldError =
					container.querySelectorAll( '.error-message' )[ 1 ];
				if ( isRequired === 'true' && ! confirmPasswordValue ) {
					confirmFieldError.style.display = 'block';
					container.querySelector(
						'.confirm-password-error'
					).style.display = 'none';
					confirmPassword.style.borderColor = '#FCA5A5';
					confirmPassword.classList.add( 'sf-classic-input-error' );
					if ( ! firstErrorInput ) {
						firstErrorInput = confirmPassword;
					}
					validateResult = true;
				} else if ( confirmPasswordValue !== inputValue ) {
					confirmFieldError.style.display = 'none';
					container.querySelector(
						'.confirm-password-error'
					).style.display = 'block';
					confirmPassword.style.borderColor = '#FCA5A5';
					confirmPassword.classList.add( 'sf-classic-input-error' );
					if ( ! firstErrorInput ) {
						firstErrorInput = confirmPassword;
					}
					validateResult = true;
				} else {
					confirmFieldError.style.display = 'none';
					confirmPassword.classList.remove(
						'sf-classic-input-error'
					);
					container.querySelector(
						'.confirm-password-error'
					).style.display = 'none';
					confirmPassword.style.borderColor = '#d1d5db';
				}
			}
		}

		//Check for email
		if (
			container.classList.contains( 'sureforms-input-email-container' )
		) {
			const confirmEmail = container.querySelector(
				'.sureforms-input-confirm-email'
			);
			const confirmFieldError = container.querySelector(
				'.srfm-cnf-email-required-message'
			);
			if ( confirmEmail ) {
				const confirmEmailValue = confirmEmail.value;
				if ( isRequired === 'true' && ! confirmEmailValue ) {
					confirmFieldError.style.display = 'block';
					container.querySelector(
						'.confirm-email-error'
					).style.display = 'none';
					confirmEmail.style.borderColor = '#FCA5A5';
					confirmEmail.classList.add( 'sf-classic-input-error' );
					if ( ! firstErrorInput ) {
						firstErrorInput = confirmEmail;
					}
					validateResult = true;
				} else if (
					confirmEmailValue &&
					confirmEmailValue !== inputValue
				) {
					confirmFieldError.style.display = 'none';
					container.querySelector(
						'.confirm-email-error'
					).style.display = 'block';
					confirmEmail.style.borderColor = '#FCA5A5';
					confirmEmail.classList.add( 'sf-classic-input-error' );
					if ( ! firstErrorInput ) {
						firstErrorInput = confirmEmail;
					}
					validateResult = true;
				} else {
					confirmFieldError.style.display = 'none';
					confirmEmail.style.borderColor = '#d1d5db';
					confirmEmail.classList.remove( 'sf-classic-input-error' );
					container.querySelector(
						'.confirm-email-error'
					).style.display = 'none';
				}
			}
		}

		//Address field
		//Might be used later
		// if ( container.classList.contains( 'sureforms-address-container' ) ) {
		// 	const addressInput = container.querySelectorAll( 'input' );
		// 	const isAddressRequired =
		// 		addressInput[ 1 ].getAttribute( 'aria-required' );
		// 	let errCounter = 0;
		// 	for (
		// 		let i = 1;
		// 		i < addressInput.length && isAddressRequired === 'true';
		// 		i++
		// 	) {
		// 		if ( ! addressInput[ i ].value ) {
		// 			errorMessage.style.display = 'block';
		// 			addressInput[ i ].style.borderColor = '#FCA5A5';
		// 			errCounter = 1;
		// 			validateResult = true;
		// 			if ( ! firstErrorInput ) {
		// 				firstErrorInput = addressInput[ i ];
		// 			}
		// 		} else {
		// 			errorMessage.style.display = 'none';
		// 			addressInput[ i ].style.borderColor = '#d1d5db';
		// 		}
		// 		if ( errCounter === 1 ) {
		// 			errorMessage.style.display = 'block';
		// 		} else {
		// 			errorMessage.style.display = 'none';
		// 		}
		// 	}
		// }

		//Upload field
		if ( container.classList.contains( 'sureforms-upload-container' ) ) {
			const uploadInput = container.querySelectorAll( 'input' )[ 1 ];
			const uploadInputInnerDiv = container.getElementsByClassName(
				'sureforms-upload-inner-div'
			)[ 0 ];
			const isSizeError = container.querySelector(
				'.srfm-upload-file-size-error'
			);
			if ( isSizeError ) {
				isSizeError.setAttribute( 'hidden', 'true' );
			}

			const isUploadRequired =
				uploadInput.getAttribute( 'aria-required' );
			if ( isUploadRequired === 'true' && ! uploadInput.value ) {
				errorMessage.style.display = 'block';
				uploadInputInnerDiv.style.borderColor = '#FCA5A5';

				validateResult = true;
				if ( ! firstErrorInput ) {
					firstErrorInput = uploadInput;
				}
			} else {
				errorMessage.style.display = 'none';
				uploadInputInnerDiv.style.borderColor = '#d1d5db';
			}
		}

		//Date field
		if (
			container.classList.contains( 'sureforms-input-date-container' )
		) {
			const dateInput = container.querySelectorAll( 'input' );
			const isDateRequired =
				dateInput[ 1 ].getAttribute( 'aria-required' );
			let dateErrCounter = 0;

			for (
				let i = 1;
				i < dateInput.length && isDateRequired === 'true';
				i++
			) {
				if ( isDateRequired === 'true' && ! dateInput[ i ].value ) {
					errorMessage.style.display = 'block';
					dateInput[ i ].style.borderColor = '#FCA5A5';
					dateErrCounter = 1;
					validateResult = true;
					if ( ! firstErrorInput ) {
						firstErrorInput = dateInput[ i ];
					}
				} else {
					dateInput[ i ].style.borderColor = '#d1d5db';
					errorMessage.style.display = 'none';
				}
			}
			if ( dateErrCounter === 1 ) {
				errorMessage.style.display = 'block';
			} else {
				errorMessage.style.display = 'none';
			}
		}

		if (
			container.classList.contains( 'sureforms-input-number-container' )
		) {
			const min = inputField.getAttribute( 'minimum' );
			const max = inputField.getAttribute( 'maximum' );
			const minMaxErrorMessage = container.querySelector(
				'.min-max-validation-message'
			);
			if (
				inputValue &&
				min !== '' &&
				Number( inputValue ) < Number( min )
			) {
				minMaxErrorMessage.innerText = `Minimum value is ${ min }`;
				inputField.classList.add( 'sf-classic-input-error' );
				validateResult = true;
				if ( ! firstErrorInput ) {
					firstErrorInput = inputField;
				}
			} else if (
				inputValue &&
				max !== '' &&
				Number( inputValue ) > Number( max )
			) {
				minMaxErrorMessage.innerText = `Maximum value is ${ max }`;
				inputField.classList.add( 'sf-classic-input-error' );
				validateResult = true;
				if ( ! firstErrorInput ) {
					firstErrorInput = inputField;
				}
			} else {
				minMaxErrorMessage.innerText = '';
				inputField.classList.remove( 'sf-classic-input-error' );
			}
		}

		//classic rating field
		if (
			container.classList.contains( 'sureforms-classic-rating-container' )
		) {
			const classicRatingField = container.querySelector(
				'.sf-rating-field-result'
			);
			const ratingRequired =
				classicRatingField.getAttribute( 'aria-required' );
			if ( ratingRequired === 'true' && ! classicRatingField.value ) {
				errorMessage.style.display = 'block';
				validateResult = true;
			} else {
				errorMessage.style.display = 'none';
			}
		}

		//classic date time field
		if (
			container.classList.contains( 'sf-classic-date-time-container' )
		) {
			const classicDateTimeField = container.querySelector(
				'.sureforms-input-data-time'
			);
			const dateTimeRequired =
				classicDateTimeField.getAttribute( 'aria-required' );
			if ( dateTimeRequired === 'true' && ! classicDateTimeField.value ) {
				errorMessage.style.display = 'block';
				validateResult = true;
				if ( ! firstErrorInput ) {
					firstErrorInput = classicDateTimeField;
				}
			} else {
				errorMessage.style.display = 'none';
			}
		}

		//classic field url
		if (
			container.classList.contains(
				'sureforms-classic-input-url-container'
			)
		) {
			const urlInput = container.querySelector( '.sureforms-url-input' );
			const validUrlMessage = container.querySelector(
				'.validation-url-message'
			);

			if ( validUrlMessage.style.display === 'block' ) {
				validateResult = true;
				if ( ! firstErrorInput ) {
					firstErrorInput = urlInput;
				}
			}
		}

		//classic dropdown field
		if (
			container.classList.contains(
				'sureforms-classic-dropdown-container'
			)
		) {
			const dropdownInput = container.querySelector(
				'.sf-classic-dropdown-result'
			);
			const dropdownValue = dropdownInput.value;
			const isDropDownRequired =
				dropdownInput.getAttribute( 'aria-required' );
			const dropdownBtn = container.querySelector(
				'.sf-classic-dropdown-btn'
			);
			if ( isDropDownRequired === 'true' && ! dropdownValue ) {
				errorMessage.style.display = 'block';
				dropdownBtn.classList.add( 'sf-classic-input-error' );
				validateResult = true;
				if ( ! firstErrorInput ) {
					firstErrorInput = dropdownBtn;
				}
			} else {
				errorMessage.style.display = 'none';
				dropdownBtn.classList.remove( 'sf-classic-input-error' );
			}
		}
	}

	if ( firstErrorInput ) {
		firstErrorInput.focus();
	}
	return validateResult;
}

document.addEventListener( 'DOMContentLoaded', function () {
	const forms = Array.from( document.querySelectorAll( '.sureforms-form' ) );
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
	const formData = new FormData( form );
	return fetch( '/wp-json/sureforms/v1/submit-form', {
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
	if ( window.innerWidth > 760 ) {
		element.style.minHeight = '600px';
	} else {
		element.style.minHeight = '420px';
	}
	element.style.opacity = 1;
	form.style.display = 'none';
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
		loader.removeAttribute( 'style' );

		const isValidate = await fieldValidation(
			formId,
			ajaxUrl,
			nonce,
			form
		);

		if ( isValidate ) {
			loader.setAttribute( 'style', 'display: none' );
			return;
		}

		const formStatus = await submitFormData( form );

		loader.setAttribute( 'style', 'display: none' );
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
		loader.setAttribute( 'style', 'display: none' );
		showErrorMessage( errorMessage );
	}
}

function extractFormAttributesAndElements( form ) {
	const formId = form.getAttribute( 'form-id' );
	const submitType = form.getAttribute( 'message-type' );
	const successUrl = form.getAttribute( 'success-url' );
	const ajaxUrl = form.getAttribute( 'ajaxurl' );
	const nonce = form.getAttribute( 'nonce' );
	const loader = form.querySelector( '.sureforms-loader' );
	const successMessage = form.nextElementSibling;
	const errorMessage = form.querySelector( '.sureforms-error-message' );
	const submitBtn = form.querySelector( '#sureforms-submit-btn' );
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
	const forms = Array.from( document.querySelectorAll( '.sureforms-form' ) );

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
