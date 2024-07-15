import { addBlurListener } from './utils';

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

				// addBlurListener(
				// 	'srfm-textarea-block',
				// 	'.srfm-textarea-block'
				// );
			}
		}
	}

	const blocks = [
		'srfm-input-block',
		'srfm-email-block',
		'srfm-url-block',
		'srfm-phone-block',
		'srfm-checkbox-block',
		'srfm-gdpr-block',
		'srfm-number-block',
		'srfm-dropdown-block',
		'srfm-multi-choice-block',
		'srfm-datepicker-block',
		'srfm-upload-block',
		'srfm-rating-block',
		'srfm-textarea-block',
	];

	blocks.forEach( ( block ) => addBlurListener( block, `.${ block }` ) );
}

document.addEventListener( 'DOMContentLoaded', initializeTextarea );
