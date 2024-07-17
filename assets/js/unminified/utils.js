import { fieldValidation } from './validation';

/**
 * Initialize inline field validation
 */
function initializeInlineFieldValidation() {
	const blocks = [
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
	];

	blocks.forEach( ( block ) => addBlurListener( block, `.${ block }` ) );
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
				areaField = areaInput.querySelectorAll( '.srfm-star-icon' );

				areaField.forEach( ( field, index ) => {
					if ( index === areaField.length - 1 ) {
						field.addEventListener( 'blur', async function () {
							fieldValidationInit( field, blockClass );
						} );
					}
				} );

				return;
			}

			// multi choice block
			if ( containerClass === 'srfm-multi-choice-block' ) {
				areaField = areaInput.querySelectorAll(
					'.srfm-input-multi-choice-single'
				);
				areaField.forEach( ( field ) => {
					field.addEventListener( 'blur', async function () {
						fieldValidationInit( field, blockClass );
					} );
				} );

				return;
			}

			// Function to validate email inputs within the email block
			if ( containerClass === 'srfm-email-block-wrap' ) {
				const emailInputs = areaInput.querySelectorAll( 'input' );
				const parentBlock = areaInput.closest( blockClass );

				emailInputs.forEach( ( emailField ) => {
					emailField.addEventListener( 'input', async function () {
						// Trim and lowercase the email input value
						emailField.value = emailField.value
							.trim()
							.toLowerCase();

						// Regular expression for validating email
						const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
						const isValidEmail = emailRegex.test(
							emailField.value
						);

						// Determine the relevant block (normal email or confirmation email)
						const inputBlock = emailField.classList.contains(
							'srfm-input-email-confirm'
						)
							? parentBlock.querySelector(
									'.srfm-email-confirm-block'
							  )
							: parentBlock.querySelector( '.srfm-email-block' );

						const errorContainer = inputBlock.querySelector(
							'.srfm-error-message'
						);

						// If the email field is empty, remove the error class and hide error message
						if ( ! emailField.value ) {
							errorContainer.style.display = 'none';
							inputBlock.classList.remove(
								'srfm-valid-email-error'
							);
						}

						// Handle email confirmation field validation
						if (
							emailField.classList.contains(
								'srfm-input-email-confirm'
							)
						) {
							const originalEmailField =
								parentBlock.querySelector(
									'.srfm-input-email'
								);
							const confirmEmailBlock = parentBlock.querySelector(
								'.srfm-email-confirm-block'
							);
							const confirmErrorContainer =
								confirmEmailBlock.querySelector(
									'.srfm-error-message'
								);
							const originalEmailValue = originalEmailField.value;

							if ( originalEmailValue !== emailField.value ) {
								confirmErrorContainer.style.display = 'block';
								confirmErrorContainer.textContent =
									'Confirmation email is not the same';
								parentBlock.classList.add( 'srfm-error' );
								return;
							} else {
								parentBlock.classList.remove( 'srfm-error' );
								confirmErrorContainer.textContent = '';
								confirmErrorContainer.style.display = 'none';
							}
						}

						// Handle general email validation
						if ( ! isValidEmail ) {
							inputBlock.classList.add(
								'srfm-valid-email-error'
							);
							errorContainer.style.display = 'block';
							errorContainer.innerHTML =
								'Please enter a valid email address';
						} else {
							errorContainer.style.display = 'none';
							inputBlock.classList.remove(
								'srfm-valid-email-error'
							);
						}
					} );
				} );
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
 * Initialize field validation
 *
 * @param {HTMLElement} areaField
 * @param {string}      blockClass
 */
const fieldValidationInit = async ( areaField, blockClass ) => {
	const formTextarea = areaField.closest( blockClass );
	const form = formTextarea.closest( 'form' );
	const formId = form.getAttribute( 'form-id' );
	const ajaxUrl = form.getAttribute( 'action' );
	const nonce = form.getAttribute( 'nonce' );
	const singleField = true;

	await fieldValidation( formId, ajaxUrl, nonce, formTextarea, singleField );
};

document.addEventListener(
	'DOMContentLoaded',
	initializeInlineFieldValidation
);

export { addBlurListener };
