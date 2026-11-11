// Inputs that already have a mask applied.
// A WeakSet rather than a marker attribute: Elementor Pro renders each opened
// popup from an outerHTML copy, which would carry the attribute onto inputs
// that have no mask.
const srfmMaskedInputs = new WeakSet();

// Input Masking
function inputFieldMasking( root = document ) {
	const inputFields = root.querySelectorAll( '.srfm-input-input' );

	inputFields.forEach( ( inputField ) => {
		if ( srfmMaskedInputs.has( inputField ) ) {
			return;
		}
		const inputMask = inputField.getAttribute( 'data-srfm-mask' );
		if ( inputMask === 'none' ) {
			return;
		}
		const inputMaskOptions = {
			clearIncomplete: true,
		};
		switch ( inputMask ) {
			case '(###) ###-####':
				inputMaskOptions.mask = '(999) 999-9999';
				break;
			case '(##) ####-####':
				inputMaskOptions.mask = '(99) 9999-9999';
				break;
			case 'dd/mm/yyyy':
				inputMaskOptions.alias = 'datetime';
				inputMaskOptions.inputFormat = 'dd/mm/yyyy';
				break;
			case 'hh:mm:ss':
				inputMaskOptions.alias = 'datetime';
				inputMaskOptions.inputFormat = 'HH:MM:ss';
				break;
			case 'dd/mm/yyyy hh:mm:ss':
				inputMaskOptions.alias = 'datetime';
				inputMaskOptions.inputFormat = 'dd/mm/yyyy HH:MM:ss';
				break;
			case 'custom-mask':
				const customMask = inputField.getAttribute(
					'data-custom-srfm-mask'
				);
				if ( customMask.startsWith( 'date:' ) ) {
					const format = customMask.replace( /^date:\s*/, '' );
					inputMaskOptions.alias = 'datetime';
					inputMaskOptions.inputFormat = format;
				} else if ( customMask.startsWith( 'alias:' ) ) {
					const alias = customMask.replace( /^alias:\s*/, '' );
					inputMaskOptions.alias = alias;
				} else {
					inputMaskOptions.mask = customMask;
				}
				break;
			default:
				break;
		}

		srfmMaskedInputs.add( inputField );
		const im = new Inputmask( inputMaskOptions );
		im.mask( inputField );
	} );
}

document.addEventListener( 'DOMContentLoaded', () => inputFieldMasking() );

// Bind forms rendered after page load, such as the copy of the form an Elementor
// Pro popup builds each time it opens.
document.addEventListener( 'srfm_form_before_submission', ( e ) => {
	if ( e.detail?.form ) {
		inputFieldMasking( e.detail.form );
	}
} );

// make input field initialization function available globally
window.srfmInitializeInputField = inputFieldMasking;
