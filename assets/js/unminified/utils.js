import { fieldValidation } from './validation';

/**
 * Initialize inline field validation
 */
function initializeInlineFieldValidation() {
	const blocks = [
		'srfm-input-block',
		'srfm-email-block',
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
							const formTextarea = field.closest( blockClass );
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
				} );

				return;
			}

			// multi choice block
			if ( containerClass === 'srfm-multi-choice-block' ) {
				areaField = areaInput.querySelectorAll(
					'.srfm-input-multi-choice-single'
				);
				areaField.forEach( ( field, index ) => {
					field.addEventListener( 'blur', async function () {
						const formTextarea = field.closest( blockClass );
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

					field.addEventListener(
						'keydown',
						async function ( event ) {
							if (
								event.keyCode === 9 &&
								index === areaField.length - 1
							) {
								// 9 is the keyCode for 'Tab'
								const formTextarea =
									field.closest( blockClass );
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
							}
						}
					);
				} );

				return;
			}

			if ( areaField ) {
				areaField.addEventListener( 'blur', async function () {
					console.log( areaField );
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

document.addEventListener(
	'DOMContentLoaded',
	initializeInlineFieldValidation
);

export { addBlurListener };
