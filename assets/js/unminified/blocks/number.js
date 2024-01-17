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
					if ( formatType === 'none' ) {
						return;
					}
					if ( formatType === 'non-decimal' ) {
						inputValue = inputValue?.replace( /[^0-9]/g, '' );
					} else {
						inputValue = inputValue?.replace( /[^0-9.]/g, '' );
						const dotCount = inputValue?.split( '.' ).length - 1;
						if ( dotCount > 1 ) {
							inputValue = inputValue?.replace( /\.+$/g, '' );
						}
					}
					numberInput.value = inputValue;
				} );
			}
		} );
	}
}
document.addEventListener( 'DOMContentLoaded', initializeNumberField );
