/* eslint-disable no-undef */
import {
	fieldValidation,
	initializeInlineFieldValidation,
	handleScrollAndFocusOnError,
	handleCaptchaValidation,
} from './validation';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

document.addEventListener( 'DOMContentLoaded', function () {
	initializeInlineFieldValidation();

	const forms = Array.from( document.querySelectorAll( '.srfm-form' ) );
	for ( const form of forms ) {
		const {
			formId,
			submitType,
			successUrl,
			ajaxUrl,
			nonce,
			loader,
			successContainer,
			successElement,
			recaptchaType,
			afterSubmission,
			captchaErrorElement,
			hCaptchaDiv,
			turnstileDiv,
		} = extractFormAttributesAndElements( form );

		const hasCaptcha =
			'v2-checkbox' === recaptchaType ||
			!! hCaptchaDiv ||
			!! turnstileDiv;

		form.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();

			const formTarget = e.target;
			if ( formTarget?.tagName === 'FORM' ) {
				// If the submit button is hidden, prevent form submission.
				const getTheContainer = formTarget?.closest(
					'.srfm-form-container'
				);
				const hasHiddenClass = getTheContainer?.classList.contains(
					'srfm-submit-button-hidden'
				);

				const isCustomButton = formTarget?.querySelector(
					'button.srfm-custom-button'
				);

				if ( hasHiddenClass && ! isCustomButton ) {
					console.warn(
						'Form submission is disabled because the submit button is hidden.'
					);
					return;
				}
			}

			handleFormSubmission(
				form,
				formId,
				ajaxUrl,
				nonce,
				loader,
				successUrl,
				successContainer,
				successElement,
				submitType,
				afterSubmission,
				hasCaptcha ? recaptchaType : undefined,
				hasCaptcha ? hCaptchaDiv : undefined,
				hasCaptcha ? turnstileDiv : undefined,
				hasCaptcha ? captchaErrorElement : undefined
			);
		} );
	}
} );

async function submitFormData( form ) {
	const formData = new FormData( form );
	const filteredFormData = new FormData();

	// Define keys to exclude from filtered form data
	const blockTheseKeys = [ 'srfm-email-confirm', 'srfm-password-confirm' ];

	// Iterate over each entry in formData
	for ( let [ key, value ] of formData.entries() ) {
		// Skip keys listed in blockTheseKeys array
		if ( blockTheseKeys.includes( key ) ) {
			continue;
		}

		if ( value !== '' ) {
			// Retrieve input element by key name and find closest `.srfm-block-single` parent
			const inputElement = form.querySelector( `[name="${ key }"]` );
			const parentBlock = inputElement?.closest( '.srfm-block-single' );

			// If parent has `.hide-element` class, reset value to empty string
			if ( parentBlock?.classList.contains( 'hide-element' ) ) {
				value = '';
			}
		}

		// Append the (possibly modified) key-value pair to filteredFormData
		filteredFormData.append( key, value );
	}

	try {
		return await wp.apiFetch( {
			path: 'sureforms/v1/submit-form',
			method: 'POST',
			body: filteredFormData,
		} );
	} catch ( e ) {
		console.log( e );
	}
}

async function afterSubmit( formStatus ) {
	const submissionId = formStatus.data.submission_id;

	try {
		await wp.apiFetch( {
			path: `/sureforms/v1/after-submission/${ submissionId }`,
			method: 'GET',
		} );
	} catch ( error ) {
		console.error( error );
	}
}

function showSuccessMessage(
	container,
	element,
	message,
	form,
	afterSubmission,
	submitType,
	loader
) {
	// Create and dispatch a custom event
	const event = new CustomEvent( 'srfm_on_show_success_message', {
		cancelable: true,
		detail: {
			form,
			element,
			message,
			submitType,
			container,
			loader,
		},
	} );

	if ( ! document.dispatchEvent( event ) ) {
		return; // Stop further execution if event.preventDefault() was called.
	}
	if ( afterSubmission === 'hide form' ) {
		form.style.opacity = 1;
		form.style.display = 'none';
		setTimeout( () => {
			element.style.opacity = 1;
		}, 500 );
	} else if ( afterSubmission === 'reset form' ) {
		form.reset();
	}
	element.innerHTML = message;
	container.classList.add( 'srfm-active' );
	window?.srfm?.handleInstantFormWrapperHeight();

	// Scroll to the success message container, if enabled.
	if ( applyFilters( 'srfm.enableScrollOnSuccess', true ) ) {
		form.parentElement.scrollIntoView( { behavior: 'smooth' } );
	}
}

function redirectToUrl( url ) {
	window.location.assign( url );
}

/**
 * Handles the display of an error message on the form at a specified position.
 *
 * This function is triggered by the `srfm_show_common_form_error` event and is responsible
 * for showing an error message on the form. The error message can be displayed either
 * at the header or footer of the form, based on the provided position.
 *
 * @param {Event}       event                            - The custom event containing details for displaying the error message.
 * @param {Object}      event.detail                     - The event detail object.
 * @param {HTMLElement} event.detail.form                - The form element where the error message will be displayed.
 * @param {string}      [event.detail.message]           - The error message to display. Defaults to a generic error message.
 * @param {string}      [event.detail.position='footer'] - The position to display the error message ('header' or 'footer').
 */
function dispatchErrorEvent( event ) {
	const { form, message = '', position = 'footer' } = event.detail || {};

	if ( ! form ) {
		return;
	}

	const errorMessage =
		message ||
		__(
			'There was an error trying to submit your form. Please try again.',
			'sureforms'
		);

	const errorClass =
		position === 'header' ? 'srfm-head-error' : 'srfm-footer-error';
	const errorElement = form.querySelector(
		`.srfm-common-error-message.${ errorClass }`
	);

	if ( errorElement ) {
		// errorElement.innerHTML = errorMessage;
		errorElement.querySelector( '.srfm-error-content' ).innerHTML =
			errorMessage;
		errorElement.removeAttribute( 'hidden' );

		// Scroll to and focus on the error message container, if enabled.
		handleScrollAndFocusOnError( {
			firstErrorInput: errorElement,
			scrollElement: errorElement,
		} );
	}
}

// Listen for the custom event
document.addEventListener( 'srfm_show_common_form_error', dispatchErrorEvent );

function showErrorMessage( args ) {
	const { form, message = '', position = 'footer' } = args;

	const errorEvent = new CustomEvent( 'srfm_show_common_form_error', {
		detail: {
			form,
			message,
			position,
		},
	} );

	document.dispatchEvent( errorEvent );
}

function hideErrorMessage( form ) {
	const getErrorMessages = form.querySelectorAll(
		'.srfm-common-error-message'
	);
	getErrorMessages.forEach( ( errorMessage ) => {
		errorMessage.setAttribute( 'hidden', true );
	} );
}

async function handleFormSubmission(
	form,
	formId,
	ajaxUrl,
	nonce,
	loader,
	successUrl,
	successContainer,
	successElement,
	submitType,
	afterSubmission,
	recaptchaType,
	hCaptchaDiv,
	turnstileDiv,
	captchaErrorElement
) {
	try {
		loader.classList.add( 'srfm-active' );

		// Hide any previous error messages.
		hideErrorMessage( form );

		const isValidate = await fieldValidation(
			formId,
			ajaxUrl,
			nonce,
			form
		);

		// Handle captcha validation, returns true if captcha is valid or not present.
		const isCaptchaValid = handleCaptchaValidation(
			recaptchaType,
			hCaptchaDiv,
			turnstileDiv,
			captchaErrorElement
		);

		/**
		 * if captcha or field validation fails, show error message and scroll to the first input or captcha container.
		 */
		if ( isValidate?.validateResult || ! isCaptchaValid ) {
			loader.classList.remove( 'srfm-active' );

			if ( isValidate?.validateResult ) {
				// Handle scroll and focus on error field when validation fails.
				handleScrollAndFocusOnError( isValidate );
			} else if ( ! isCaptchaValid ) {
				// Handle scroll and focus on error field when captcha validation fails.
				handleScrollAndFocusOnError( {
					firstErrorInput: captchaErrorElement,
					scrollElement: captchaErrorElement,
				} );
			}
			return;
		}

		// Create and dispatch a custom event
		const event = new CustomEvent( 'srfm_on_trigger_form_submission', {
			cancelable: true,
			detail: {
				form,
				loader,
				formId,
				submitType,
				successElement,
				successContainer,
			},
		} );

		if ( ! document.dispatchEvent( event ) ) {
			loader.classList.remove( 'srfm-active' );
			return; // Stop further execution if event.preventDefault() was called.
		}

		const formStatus = await submitFormData( form );
		if ( formStatus?.success ) {
			/**
			 * Emit a function to signal the successful submission of a form.
			 */
			emitFormSubmitSuccess( { ...formStatus, formId } );

			if ( submitType === 'same page' ) {
				showSuccessMessage(
					successContainer,
					successElement,
					formStatus?.message ?? '',
					form,
					afterSubmission,
					submitType
				);
				loader.classList.remove( 'srfm-active' );
			} else if (
				/**
				 * This condition is similar to above one but we are using this for custom-app
				 * here we are not removing 'srfm-active' class from loader
				 * and sending loader as an extra parameter
				 */
				! [ 'different page', 'custom url' ].includes( submitType )
			) {
				showSuccessMessage(
					successContainer,
					successElement,
					formStatus?.message ?? '',
					form,
					afterSubmission,
					submitType,
					loader
				);
			} else {
				if ( formStatus?.redirect_url ) {
					redirectToUrl( formStatus?.redirect_url );
				}
				loader.classList.remove( 'srfm-active' );
			}
			// Moving afterSubmit action out of specific method so it should work for all submission mode
			if ( formStatus?.data?.after_submit ) {
				afterSubmit( formStatus );
			}
		} else {
			const errorData = formStatus?.data || {};
			showErrorMessage( { form, ...errorData } );
			loader.classList.remove( 'srfm-active' );
		}
	} catch ( error ) {
		// Create and dispatch a custom event
		const event = new CustomEvent(
			'srfm_on_trigger_form_submission_failure',
			{
				detail: {
					form,
					error,
					loader,
					formId,
					submitType,
					successElement,
					successContainer,
				},
			}
		);

		document.dispatchEvent( event );

		loader.classList.remove( 'srfm-active' );
		showErrorMessage( { form } );
	}
}

function extractFormAttributesAndElements( form ) {
	const formId = form.getAttribute( 'form-id' );
	const submitType = form.getAttribute( 'message-type' );
	const successUrl = form.getAttribute( 'success-url' );
	const ajaxUrl = form.getAttribute( 'ajaxurl' );
	const nonce = form.getAttribute( 'data-nonce' );
	const loader = form.querySelector( '.srfm-loader' );
	const successContainer = form.parentElement.querySelector(
		'.srfm-single-form.srfm-success-box'
	);
	const successElement = successContainer?.querySelector(
		'.srfm-success-box-description'
	);
	const submitBtn = form.querySelector( '#srfm-submit-btn' );
	const afterSubmission = form.getAttribute( 'after-submission' );
	const gcaptchaDiv = form.querySelector( '.g-recaptcha' );
	const siteKey = gcaptchaDiv?.getAttribute( 'data-sitekey' );
	const recaptchaType = gcaptchaDiv?.getAttribute( 'recaptcha-type' );
	const captchaErrorElement = form.querySelector( '#captcha-error' );
	const hCaptchaDiv = form.querySelector( '.h-captcha' );
	const turnstileDiv = form.querySelector( '.cf-turnstile' );

	return {
		formId,
		submitType,
		successUrl,
		ajaxUrl,
		nonce,
		loader,
		successContainer,
		successElement,
		submitBtn,
		siteKey,
		recaptchaType,
		afterSubmission,
		captchaErrorElement,
		hCaptchaDiv,
		turnstileDiv,
	};
}

/**
 * Callback function to handle form submission.
 * Incase of v2-invisible reCAPTCHA, it will render the reCAPTCHA and handle form submission.
 * Incase of v3-reCAPTCHA, it will handle form submission directly.
 *
 * @param {string} token v3-reCAPTCHA token.
 */
function recaptchaCallback( token = '' ) {
	const forms = Array.from( document.querySelectorAll( '.srfm-form' ) );
	forms.forEach( ( form ) => {
		const {
			formId,
			submitType,
			successUrl,
			ajaxUrl,
			nonce,
			loader,
			successContainer,
			successElement,
			submitBtn,
			siteKey,
			recaptchaType,
			afterSubmission,
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
						successContainer,
						successElement,
						submitType,
						afterSubmission
					);
					isRecaptchaRender = true;
				},
			} );

			submitBtn.addEventListener( 'click', () => {
				loader.classList.add( 'srfm-active' );
				if ( isRecaptchaRender ) {
					handleFormSubmission(
						form,
						formId,
						ajaxUrl,
						nonce,
						loader,
						successUrl,
						successContainer,
						successElement,
						submitType,
						afterSubmission
					);
				}
			} );
		}

		// v3-reCAPTCHA
		if ( recaptchaType === 'v3-reCAPTCHA' && token ) {
			loader.classList.add( 'srfm-active' );
			handleFormSubmission(
				form,
				formId,
				ajaxUrl,
				nonce,
				loader,
				successUrl,
				successContainer,
				successElement,
				submitType,
				afterSubmission
			);
		}
	} );
}

/**
 * Emits a custom event to signal the successful submission of a form.
 *
 * This function creates and dispatches a custom event, `srfm_form_submission_success`,
 * to notify other parts of the application that a form has been successfully submitted.
 * It includes form-specific details, such as the form data.
 *
 * Custom Event: `srfm_form_submission_success`
 * - Dispatched event signaling a form submission success.
 * - Event payload (`detail`) includes: form data.
 *
 * @param {Object} formStatus - An object representing the status of the form submission.
 */
function emitFormSubmitSuccess( formStatus ) {
	// Create a custom event with form details.
	const srfmFormSubmissionSuccessEvent = new CustomEvent(
		'srfm_form_submission_success',
		{
			detail: {
				formId: `srfm-form-${ formStatus.formId }`,
			},
		}
	);

	// Dispatch the custom event.
	document.dispatchEvent( srfmFormSubmissionSuccessEvent );
}

// directly assign recaptchaCallback into the global space:
window.recaptchaCallback = recaptchaCallback;

// Bricks Builder compatibility to disable form submission in the preview mode
window.handleBricksPreviewFormSubmission = function () {
	const forms = Array.from( document.querySelectorAll( '.srfm-form' ) );
	for ( const form of forms ) {
		form.addEventListener( 'submit', async function ( e ) {
			e.preventDefault();
		} );
	}
};
