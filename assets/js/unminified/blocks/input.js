// Input Masking
function inputFieldMasking() {
	const inputFields = document.querySelectorAll( '.srfm-input-input' );
	
	inputFields.forEach( ( inputField ) => {
		const inputMask = inputField.getAttribute( 'data-mask' );
        let inputMaskOptions = {
            clearIncomplete: true,
        }
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
                inputMaskOptions.regex = inputField.getAttribute( 'data-custom-mask' );
                break;
            default:
                break;
        }
		if ( inputMask !== 'none' ) {
			const im = new Inputmask( inputMaskOptions );
			im.mask( inputField );
            inputField.inputmaskInstance = im;
		}
	} );
}

document.addEventListener( 'DOMContentLoaded', inputFieldMasking );