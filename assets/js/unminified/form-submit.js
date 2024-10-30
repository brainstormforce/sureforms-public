/* eslint-disable no-undef */
import {
	fieldValidation,
	initializeInlineFieldValidation,
	handleScrollAndFocusOnError,
} from './validation';
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
			errorElement,
			submitBtn,
			siteKey,
			recaptchaType,
			afterSubmission,
			captchaErrorElement,
			hCaptchaDiv,
			turnstileDiv,
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
									successContainer,
									successElement,
									errorElement,
									submitType,
									afterSubmission
								);
							}
						} );
				} );
			} );
		} else if (
			'v2-checkbox' === recaptchaType ||
			!! hCaptchaDiv ||
			!! turnstileDiv
		) {
			form.addEventListener( 'submit', ( e ) => {
				e.preventDefault();
				let captchaResponse;
				if ( 'v2-checkbox' === recaptchaType ) {
					captchaResponse = grecaptcha.getResponse();
				} else if ( !! hCaptchaDiv ) {
					captchaResponse = hcaptcha.getResponse();
				} else if ( !! turnstileDiv ) {
					captchaResponse = turnstile.getResponse();
				}
				if ( 0 === captchaResponse.length ) {
					captchaErrorElement.style.display = 'block';
					return;
				}
				captchaErrorElement.style.display = 'none';
				handleFormSubmission(
					form,
					formId,
					ajaxUrl,
					nonce,
					loader,
					successUrl,
					successContainer,
					successElement,
					errorElement,
					submitType,
					afterSubmission
				);
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
					successContainer,
					successElement,
					errorElement,
					submitType,
					afterSubmission
				);
			} );
		}
	}
} );

async function submitFormData( form ) {
	const site_url = srfm_submit.site_url;

	const formData = new FormData( form );
	const filteredFormData = new FormData();

	for ( const [ key, value ] of formData.entries() ) {
		if (
			! key.includes( 'srfm-email-confirm' ) &&
			! key.includes( 'srfm-password-confirm' )
		) {
			filteredFormData.append( key, value );
		}
	}

	return await fetch( `${ site_url }/wp-json/sureforms/v1/submit-form`, {
		method: 'POST',
		headers: {
			'X-WP-Nonce': srfm_submit.nonce,
		},
		body: filteredFormData,
	} )
		.then( ( response ) => {
			if ( response.ok ) {
				return response.json();
			}
		} )
		.catch( ( e ) => {
			console.log( e );
		} );
}

async function afterSubmit( formStatus ) {
	const site_url = window.srfm_submit.site_url;
	const submissionId = formStatus.data.submission_id;

	try {
		const response = await fetch(
			`${ site_url }/wp-json/sureforms/v1/after-submission/${ submissionId }`,
			{
				headers: {
					'X-WP-Nonce': window.srfm_submit.nonce,
				},
			}
		);

		if ( ! response.ok ) {
			throw new Error( `HTTP error! Status: ${ response.status }` );
		}
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
	const event = new CustomEvent( 'SRFM_Form_Success_Message', {
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
	form.parentElement.scrollIntoView( { behavior: 'smooth' } );
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
	successContainer,
	successElement,
	errorElement,
	submitType,
	afterSubmission
) {
	try {
		loader.classList.add( 'srfm-active' );

		const isValidate = await fieldValidation(
			formId,
			ajaxUrl,
			nonce,
			form
		);

		/**
		 * if validation fails, show error message and scroll to the first
		 */
		if ( isValidate?.validateResult ) {
			loader.classList.remove( 'srfm-active' );

			// Handle scroll and focus on error field when validation fails.
			handleScrollAndFocusOnError( isValidate );
			return;
		}

		const formStatus = await submitFormData( form );
		if ( formStatus?.success ) {
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
				if ( formStatus?.data?.after_submit ) {
					afterSubmit( formStatus );
				}
			} else if (
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
		} else {
			loader.classList.remove( 'srfm-active' );
			showErrorMessage( errorElement );
			loader.classList.remove( 'srfm-active' );
		}
	} catch ( error ) {
		loader.classList.remove( 'srfm-active' );
		showErrorMessage( errorElement );
	}
}

function extractFormAttributesAndElements( form ) {
	const formId = form.getAttribute( 'form-id' );
	const submitType = form.getAttribute( 'message-type' );
	const successUrl = form.getAttribute( 'success-url' );
	const ajaxUrl = form.getAttribute( 'ajaxurl' );
	const nonce = form.getAttribute( 'nonce' );
	const loader = form.querySelector( '.srfm-loader' );
	const successContainer = form.parentElement.querySelector(
		'.srfm-single-form.srfm-success-box'
	);
	const successElement = successContainer?.querySelector(
		'.srfm-success-box-description'
	);
	const errorElement = form.querySelector( '.srfm-error-message' );
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
		errorElement,
		submitBtn,
		siteKey,
		recaptchaType,
		afterSubmission,
		captchaErrorElement,
		hCaptchaDiv,
		turnstileDiv,
	};
}

// eslint-disable-next-line no-unused-vars
// v-2 invisible recaptcha callback
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
			successContainer,
			successElement,
			errorElement,
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
						errorElement,
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
						errorElement,
						submitType,
						afterSubmission
					);
				}
			} );
		}
	} );
}

// directly assign onloadCallback into the global space:
window.onloadCallback = onloadCallback;

// Bricks Builder compatibility to disable form submission in the preview mode
window.handleBricksPreviewFormSubmission = function () {
	const forms = Array.from( document.querySelectorAll( '.srfm-form' ) );
	for ( const form of forms ) {
		form.addEventListener( 'submit', async function ( e ) {
			e.preventDefault();
		} );
	}
};
