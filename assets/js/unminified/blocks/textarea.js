import { fieldValidation } from '../validation';

function addBlurListener( containerClass, blockClass ) {
	const container = Array.from(
		document.getElementsByClassName( containerClass )
	);

	if ( container ) {
		for ( const areaInput of container ) {
			const areaField =
				areaInput.querySelector( 'input' ) ||
				areaInput.querySelector( 'textarea' ) ||
				areaInput.querySelector( 'select' );

			if ( areaField ) {
				areaField.addEventListener( 'blur', async function () {
					const formTextarea = areaField.closest( blockClass );
					const form = formTextarea.closest( 'form' );
					const formId = form.getAttribute( 'form-id' );
					const ajaxUrl = form.getAttribute( 'action' );
					const nonce = form.getAttribute( 'nonce' );
					const singleField = true;

					await fieldValidation(
						formId,
						ajaxUrl,
						nonce,
						formTextarea,
						singleField
					);
				} );
			}
		}
	}
}

function initializeTextarea() {
	// Textarea field validation
	const textAreaContainer = Array.from(
		document.getElementsByClassName( 'srfm-textarea-block' )
	);

	if ( textAreaContainer ) {
		for ( const areaInput of textAreaContainer ) {
			const areaField = areaInput.querySelector( 'textarea' );
			if ( areaField ) {
				areaField.addEventListener( 'input', function () {
					const textAreaValue = areaField.value;
					const maxLength = areaField.getAttribute( 'maxLength' );
					if ( maxLength !== '' ) {
						const counterDiv =
							areaInput.querySelector( '.srfm-text-counter' );
						if ( counterDiv ) {
							const remainingLength =
								maxLength - textAreaValue.length;
							counterDiv.innerText =
								remainingLength + '/' + maxLength;
						}
					}
				} );

				addBlurListener(
					'srfm-textarea-block',
					'.srfm-textarea-block'
				);
			}
		}
	}

	// Input field validation
	addBlurListener( 'srfm-input-block', '.srfm-input-block' );

	// Email field validation
	addBlurListener( 'srfm-email-block', '.srfm-email-block' );

	// URL field validation
	addBlurListener( 'srfm-url-block', '.srfm-url-block' );

	// Phone field validation
	addBlurListener( 'srfm-phone-block', '.srfm-phone-block' );

	// Checkbox field validation
	addBlurListener( 'srfm-checkbox-block', '.srfm-checkbox-block' );

	// GDPR field validation
	addBlurListener( 'srfm-gdpr-block', '.srfm-gdpr-block' );

	// Number field validation
	addBlurListener( 'srfm-number-block', '.srfm-number-block' );

	// dropdown field validation
	addBlurListener( 'srfm-dropdown-block', '.srfm-dropdown-block' );

	// address compact field validation - no
	// multi choice - no
	// number slider - no

	// date time field validation
	addBlurListener( 'srfm-datepicker-block', '.srfm-datepicker-block' );
	// upload field validation
	addBlurListener( 'srfm-upload-block', '.srfm-upload-block' );
}

document.addEventListener( 'DOMContentLoaded', initializeTextarea );
