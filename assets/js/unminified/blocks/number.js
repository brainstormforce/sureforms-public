function initializeNumberField() {
	const numberElement = document.querySelectorAll( '.srfm-number-block' );

	if ( numberElement ) {
		numberElement.forEach( ( element ) => {
			const numberInput = element.querySelector( 'input' );
			if ( numberInput ) {
				numberInput.addEventListener( 'input', ( e ) => {
					const formatType =
						numberInput.getAttribute( 'format-type' );
					let inputValue = e.target.value;
					switch ( formatType ) {
						case 'none':
							return;
						case 'decimal':
							return;
						case 'non-decimal':
							if ( inputValue.includes( '.' ) ) {
								inputValue = inputValue?.replace( '.', '' );
							} else {
								return;
							}
							break;
					}

					numberInput.value = inputValue;
				} );
			}
		} );
	}
}
document.addEventListener( 'DOMContentLoaded', initializeNumberField );
