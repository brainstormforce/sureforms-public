import { fieldValidation } from './validation';
import { __ } from '@wordpress/i18n';

/**
 * Initialize inline field validation by setting up blur listeners for various blocks.
 */
function initializeInlineFieldValidation() {
	addInputBlockListener();
	addEmailBlockListener();
	addUrlBlockListener();
	addPhoneBlockListener();
	addCheckboxBlockListener();
	addGdprBlockListener();
	addNumberBlockListener();
	addMultiChoiceBlockListener();
	addDatepickerBlockListener();
	addUploadBlockListener();
	addRatingBlockListener();
	addTextareaBlockListener();
	addDropdownBlockListener();
}

/**
 * Add blur listener for input block.
 */
function addInputBlockListener() {
	addBlurListener( 'srfm-input-block', '.srfm-input-block' );
}

/**
 * Add blur listener for email block.
 * This includes validation for email format and confirmation email matching.
 */
function addEmailBlockListener() {
	const containerClass = 'srfm-email-block-wrap';
	const blockClass = '.srfm-email-block-wrap';
	const containers = Array.from(
		document.getElementsByClassName( containerClass )
	);

	containers.forEach( ( container ) => {
		const emailInputs = container.querySelectorAll( 'input' );
		const parentBlock = container.closest( blockClass );

		emailInputs.forEach( ( emailField ) => {
			emailField.addEventListener( 'input', async function () {
				// Normalize email input value
				emailField.value = emailField.value.trim().toLowerCase();

				// Regex for email validation
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

				// Clear error if the email field is empty
				if ( ! emailField.value ) {
					errorContainer.style.display = 'none';
					inputBlock.classList.remove( 'srfm-valid-email-error' );
				}

				// Handle email confirmation field validation
				if (
					emailField.classList.contains( 'srfm-input-email-confirm' )
				) {
					const originalEmailField =
						parentBlock.querySelector( '.srfm-input-email' );
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
					}
					parentBlock.classList.remove( 'srfm-error' );
					confirmErrorContainer.textContent = '';
					confirmErrorContainer.style.display = 'none';
				}

				// Display error message if the email is not valid
				if ( ! isValidEmail ) {
					inputBlock.classList.add( 'srfm-valid-email-error' );
					errorContainer.style.display = 'block';
					errorContainer.innerHTML = __(
						'Please enter a valid email address',
						'sureforms'
					);
				} else {
					errorContainer.style.display = 'none';
					inputBlock.classList.remove( 'srfm-valid-email-error' );
				}
			} );
		} );
	} );

	addBlurListener( 'srfm-email-block', '.srfm-email-block' );
}

/**
 * Add blur listener for URL block.
 */
function addUrlBlockListener() {
	addBlurListener( 'srfm-url-block', '.srfm-url-block' );
}

/**
 * Add blur listener for phone block.
 */
function addPhoneBlockListener() {
	addBlurListener( 'srfm-phone-block', '.srfm-phone-block' );
}

/**
 * Add blur listener for checkbox block.
 */
function addCheckboxBlockListener() {
	addBlurListener( 'srfm-checkbox-block', '.srfm-checkbox-block' );
}

/**
 * Add blur listener for GDPR block.
 */
function addGdprBlockListener() {
	addBlurListener( 'srfm-gdpr-block', '.srfm-gdpr-block' );
}

/**
 * Add blur listener for number block.
 */
function addNumberBlockListener() {
	addBlurListener( 'srfm-number-block', '.srfm-number-block' );
}

/**
 * Add blur listener for multi-choice block.
 * This handles multiple choice fields within the block.
 */
function addMultiChoiceBlockListener() {
	const containerClass = 'srfm-multi-choice-block';
	const blockClass = '.srfm-multi-choice-block';
	const containers = Array.from(
		document.getElementsByClassName( containerClass )
	);

	containers.forEach( ( container ) => {
		const areaFields = container.querySelectorAll(
			'.srfm-input-multi-choice-single'
		);

		areaFields.forEach( ( field ) => {
			field.addEventListener( 'blur', async function () {
				fieldValidationInit( field, blockClass );
			} );
		} );
	} );
}

/**
 * Add blur listener for datepicker block.
 */
function addDatepickerBlockListener() {
	addBlurListener( 'srfm-datepicker-block', '.srfm-datepicker-block' );
}

/**
 * Add blur listener for upload block.
 * This handles file input fields within the block.
 */
function addUploadBlockListener() {
	const containerClass = 'srfm-upload-block';
	const blockClass = '.srfm-upload-block';
	const containers = Array.from(
		document.getElementsByClassName( containerClass )
	);

	containers.forEach( ( container ) => {
		const field = container.querySelector( 'input[type="file"]' );

		if ( field ) {
			field.addEventListener( 'blur', async function () {
				fieldValidationInit( field, blockClass );
			} );
		}
	} );
}

/**
 * Add blur listener for rating block.
 * This handles star rating fields within the block.
 */
function addRatingBlockListener() {
	const containerClass = 'srfm-rating-block';
	const blockClass = '.srfm-rating-block';
	const containers = Array.from(
		document.getElementsByClassName( containerClass )
	);

	containers.forEach( ( container ) => {
		const areaFields = container.querySelectorAll( '.srfm-star-icon' );

		areaFields.forEach( ( field, index ) => {
			if ( index === areaFields.length - 1 ) {
				field.addEventListener( 'blur', async function () {
					fieldValidationInit( field, blockClass );
				} );
			}
		} );
	} );
}

/**
 * Add blur listener for textarea block.
 */
function addTextareaBlockListener() {
	addBlurListener( 'srfm-textarea-block', '.srfm-textarea-block' );
}

/**
 * Add blur listener for dropdown block.
 */
function addDropdownBlockListener() {
	addBlurListener( 'srfm-dropdown-block', '.srfm-dropdown-block' );
}

/**
 * Add blur listeners to all fields.
 * This function attaches blur event listeners to input, textarea, and select elements within the specified block.
 * On blur, it initializes field validation.
 *
 * @param {string} containerClass - Class of the container element.
 * @param {string} blockClass     - Class of the block element.
 */
function addBlurListener( containerClass, blockClass ) {
	const containers = Array.from(
		document.getElementsByClassName( containerClass )
	);

	containers.forEach( ( container ) => {
		const areaField =
			container.querySelector( 'input' ) ||
			container.querySelector( 'textarea' ) ||
			container.querySelector( 'select' );

		if ( areaField ) {
			areaField.addEventListener( 'blur', async function () {
				fieldValidationInit( areaField, blockClass );
			} );
		}
	} );
}

/**
 * Initialize field validation.
 *
 * @param {HTMLElement} areaField  - The field element that triggered the validation.
 * @param {string}      blockClass - Class of the block element.
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

// Initialize the inline field validation when the DOM content is loaded.
document.addEventListener(
	'DOMContentLoaded',
	initializeInlineFieldValidation
);

export { addBlurListener };
