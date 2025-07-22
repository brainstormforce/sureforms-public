/* eslint-disable no-undef */
import {
	fieldValidation,
	initializeInlineFieldValidation,
	handleScrollAndFocusOnError,
	handleCaptchaValidation,
	srfmFields,
} from './validation';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Event listener for external validation initialization.
 *
 * Allows external code to trigger form validation initialization by dispatching
 * the 'srfm_initialize_validation' custom event. When triggered, this will
 * initialize inline field validation for specified fields or all fields if none specified.
 *
 * @param {CustomEvent} event                 - The custom event object
 * @param {Object}      event.detail          - Event details
 * @param {Array}       [event.detail.fields] - Optional array of field types to initialize validation for
 */
document.addEventListener( 'srfm_initialize_validation', ( event ) => {
	const fields = event?.detail?.fields;

	if ( ! fields || ! Array.isArray( fields ) ) {
		// If no fields specified, initialize validation for all field types
		initializeInlineFieldValidation();
		return;
	}

	// Filter the fields array to only include valid field types
	const validFields = fields.filter( ( field ) =>
		srfmFields().includes( field )
	);

	// Initialize validation with the filtered fields
	initializeInlineFieldValidation( validFields );
} );

/**
 * Initializes form handlers for all forms with the class `.srfm-form`.
 */
function initializeFormHandlers() {
	initializeInlineFieldValidation();

	const forms = Array.from( document.querySelectorAll( '.srfm-form' ) );
	for ( const form of forms ) {
		// Add the event before the form initialization to ensure that the all third party libraries are loaded and initialized.
		// Dispatch a custom event *before* the form is submitted.
		document.dispatchEvent(
			new CustomEvent( 'srfm_form_before_submission', {
				detail: { form },
			} )
		);

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
			hasLoginBlock,
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

			/**
			 * Check for the form login completion status.
			 * If the login is not completed, dispatch a custom event to handle the login request.
			 * This allows for a two-step process where the login is handled separately before form submission.
			 */
			if ( hasLoginBlock && ! form.__loginSuccess ) {
				const loginEvent = new CustomEvent( 'srfm_login_request', {
					cancelable: true,
					detail: { form },
				} );
				form.dispatchEvent( loginEvent );
				return;
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
			// Set the login completion status to true after form submission.
			form.__loginSuccess = true;
		} );
	}
}

document.addEventListener( 'DOMContentLoaded', function () {
	// Initialize the form submission script.
	initializeFormHandlers();
} );

/**
 * Prepares and formats address data from a form for submission.
 *
 * This function extracts address information from blocks within the form that have the class `.srfm-address-block`.
 * It builds a compact address string for each block in the format: "address_1, address_2, city, state, country".
 * If the address is not empty, it is added to the submission data. This is particularly useful for third-party
 * integrations like third-party services, which can utilize compact addresses such as permanent address, temporary address, etc.
 * The address will be structured as field 1, field 2, and so on.
 *
 * @param {HTMLFormElement} form - The form element containing address blocks.
 * @return {Object|null} - An object containing address data, or null if no addresses are found.
 */
function prepareAddressesData( form ) {
	const addressBlocks = form.querySelectorAll( '.srfm-address-block' );
	if ( ! addressBlocks ) {
		return null;
	}

	const addresses = {};

	// Iterate over each address block to extract and format address data.
	addressBlocks.forEach( ( block ) => {
		const addressSlug = block.getAttribute( 'data-slug' );

		// Skip processing if the block does not have a `data-slug` attribute.
		if ( ! addressSlug ) {
			return;
		}

		// Select all input fields within the block (text inputs and dropdowns).
		const addressFields = block.querySelectorAll(
			'.srfm-input-input, .srfm-dropdown-input'
		);

		// Build a compact address string in the format: "address_1, address_2, city, state, country".
		const compactAddress = Array.from( addressFields )
			.map( ( field ) => field?.value?.trim() )
			.filter( Boolean ) // Remove empty or undefined values.
			.join( ', ' );

		// Add the compact address to the addresses object using the slug as the key.
		addresses[ addressSlug ] = compactAddress;
	} );

	// Check if the addresses object is not empty.
	return Object.keys( addresses ).length > 0 ? addresses : null;
}

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

			/**
			 * Filters the field value during form submission.
			 *
			 * Allows modifying or clearing field values before they are submitted.
			 * Similar to how hidden fields are cleared, this filter enables custom handling
			 * for specific field types like repeater fields.
			 *
			 * @since x.x.x
			 * hook srfm.filterFieldValue
			 *
			 * @param {*}       value           The current field value.
			 * @param {Object}  filterArgs      Arguments passed to the filter.
			 * @param {string}  filterArgs.key  The field key/name.
			 * @param {Element} filterArgs.form The form DOM element.
			 * @return {*} The filtered field value.
			 */
			value = applyFilters( 'srfm.filterFieldValue', value, {
				key,
				form,
			} );
		}

		/**
		 * Filters whether a field should be skipped during form submission.
		 *
		 * @since x.x.x
		 * hook srfm.shouldSkipField
		 *
		 * @param {boolean} false            Default value indicating field should not be skipped.
		 * @param {Object}  filterArgs       Arguments passed to the filter.
		 * @param {string}  filterArgs.key   The field key/name.
		 * @param {*}       filterArgs.value The field value.
		 * @param {Element} filterArgs.form  The form DOM element.
		 * @return {boolean} Whether to skip the field.
		 */
		const shouldSkipField = applyFilters( 'srfm.shouldSkipField', false, {
			key,
			value,
			form,
		} );

		if ( shouldSkipField ) {
			continue;
		}

		// Append the (possibly modified) key-value pair to filteredFormData
		filteredFormData.append( key, value );
	}

	const addresses = prepareAddressesData( form );
	if ( addresses ) {
		filteredFormData.append(
			'srfm_addresses',
			JSON.stringify( addresses )
		);
	}

	// Allow pro plugins to add additional form data
	const additionalData = applyFilters(
		'srfm.prepareAdditionalFormData',
		{},
		form
	);
	Object.keys( additionalData ).forEach( ( key ) => {
		if (
			additionalData[ key ] !== null &&
			additionalData[ key ] !== undefined
		) {
			filteredFormData.append(
				key,
				JSON.stringify( additionalData[ key ] )
			);
		}
	} );

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
	const {
		form,
		message = '',
		position = 'footer',
		log_message = null,
	} = event.detail || {};

	if ( ! form ) {
		return;
	}

	// Log the error message to the console if provided.
	if ( log_message ) {
		console.warn( log_message );
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
	const {
		form,
		message = '',
		position = 'footer',
		log_message = null,
	} = args;

	const errorEvent = new CustomEvent( 'srfm_show_common_form_error', {
		detail: {
			form,
			message,
			position,
			log_message,
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
	const hasLoginBlock = form.querySelector( '.srfm-login-block' );

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
		hasLoginBlock,
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
				'error-callback': () => {
					showErrorMessageOnRecaptchaError( {
						containerSelector:
							'.g-recaptcha[recaptcha-type="v2-invisible"]:not(.captcha-error-added)',
						message:
							srfm_submit?.messages
								?.srfm_google_captcha_error_message,
					} );
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

function showErrorMessageOnRecaptchaError( args ) {
	const { containerSelector, message = '' } = args;

	const getCaptchaContainer = document.querySelectorAll( containerSelector );
	if ( ! getCaptchaContainer ) {
		return;
	}

	getCaptchaContainer.forEach( ( element ) => {
		const getTheForm = element.closest( '.srfm-form' );
		if ( getTheForm ) {
			showErrorMessage( { form: getTheForm, message } );

			// Add class
			element.classList.add( 'captcha-error-added' );
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

// Listen for the Elementor popup show event
window.addEventListener( 'elementor/popup/show', function ( e ) {
	// Check if the popup contains a SureForms form container
	const formContainer = e?.detail?.instance?.$element?.[ 0 ]?.querySelector(
		'.srfm-form-container'
	);

	// If a form container is found, initialize form handlers
	if ( formContainer ) {
		initializeFormHandlers();
	}
} );

// Listen for a custom event named 'srfm_form_initialize'
// This event should be dispatched whenever a form is dynamically initialized
document.addEventListener( 'srfm_form_initialize', function () {
	// Call a function to attach event listeners, validation, or other custom logic
	initializeFormHandlers();
} );
