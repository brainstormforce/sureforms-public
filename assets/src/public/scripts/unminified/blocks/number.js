function initializeNumberField() {
	const numberElements = Array.from(
		document.getElementsByClassName( 'srfm-input-number-container' )
	);

	if ( numberElements ) {
		for ( const numberContainer of numberElements ) {
			const numberInput = numberContainer.querySelector( 'input' );
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
		}
	}
}
document.addEventListener( 'DOMContentLoaded', initializeNumberField );
