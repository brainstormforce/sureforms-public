import { fieldValidation } from '../validation';

/**
 * Add blur listeners to all fields
 * That shows validation errors on blur
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

			if ( containerClass === 'srfm-upload-block' ) {
				areaField = areaInput.querySelector( 'input[type="file"]' );
			}

			if ( containerClass === 'srfm-rating-block' ) {
				areaField = areaInput.querySelectorAll( '.srfm-star-icon' );

				areaField.forEach( ( field, index ) => {
					if ( index === areaField.length - 1 ) {
						console.log( field );
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
			// if ( containerClass === 'srfm-multi-choice-block' ) {
			// 	areaField = areaInput.querySelectorAll(
			// 		'.srfm-input-multi-choice-single'
			// 	);
			// 	areaField.forEach( ( field, index ) => {
			// 		field.addEventListener( 'blur', async function () {
			// 			// check for tab key press
			// 			field.addEventListener(
			// 				'keydown',
			// 				async function ( event ) {
			// 					console.log( event.keyCode );
			// 					if (
			// 						event.keyCode === 9 &&
			// 						index === areaField.length - 1
			// 					) {
			// 						console.log( 'in if' );
			// 						const formTextarea =
			// 							field.closest( blockClass );
			// 						const form = formTextarea.closest( 'form' );
			// 						const formId =
			// 							form.getAttribute( 'form-id' );
			// 						const ajaxUrl =
			// 							form.getAttribute( 'action' );
			// 						const nonce = form.getAttribute( 'nonce' );
			// 						const singleField = true;

			// 						await fieldValidation(
			// 							formId,
			// 							ajaxUrl,
			// 							nonce,
			// 							formTextarea,
			// 							singleField
			// 						);
			// 						return;
			// 					}
			// 					return;
			// 				}
			// 			);

			// 			// blockClass = '.srfm-multi-choice-single';
			// 			const formTextarea = field.closest( blockClass );
			// 			const form = formTextarea.closest( 'form' );
			// 			const formId = form.getAttribute( 'form-id' );
			// 			const ajaxUrl = form.getAttribute( 'action' );
			// 			const nonce = form.getAttribute( 'nonce' );
			// 			const singleField = true;

			// 			await fieldValidation(
			// 				formId,
			// 				ajaxUrl,
			// 				nonce,
			// 				formTextarea,
			// 				singleField
			// 			);
			// 		} );
			// 	} );

			// 	return;
			// }

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

			if ( areaField && areaField !== null ) {
				areaField.addEventListener( 'blur', async function () {
					console.log( areaField );
					const formTextarea = areaField.closest( blockClass );
					const form = formTextarea.closest( 'form' );
					const formId = form.getAttribute( 'form-id' );
					const ajaxUrl = form.getAttribute( 'action' );
					const nonce = form.getAttribute( 'nonce' );
					const singleField = true;

					console.log( {
						formTextarea,
						form,
						formId,
						ajaxUrl,
						nonce,
						singleField,
					} );

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

export { addBlurListener };
