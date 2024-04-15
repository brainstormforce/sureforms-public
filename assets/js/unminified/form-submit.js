/* eslint-disable no-undef */
import { fieldValidation } from './validation';
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

	return fetch( `${ site_url }/wp-json/sureforms/v1/submit-form`, {
		method: 'POST',
		headers: {
			'X-WP-Nonce': srfm_submit.nonce,
		},
		body: filteredFormData,
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
			loader.classList.remove( 'srfm-active' );
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
	const gcaptchaDiv = form.querySelector( '.g-recaptcha' );
	const siteKey = gcaptchaDiv?.getAttribute( 'data-sitekey' );
	const recaptchaType = gcaptchaDiv?.getAttribute( 'recaptcha-type' );

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
				callback: ( ) => {
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
				loader.classList.add( 'srfm-active' );
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

// directly assign onloadCallback into the global space:
window.onloadCallback = onloadCallback;
