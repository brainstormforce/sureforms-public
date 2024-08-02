function SRFMFormatNumber( number, formatType ) {
	if ( ! number ) {
		return '';
	}

	let formattedNumber = '';

	if ( 'eu-style' === formatType ) {
		const normalizeNumber = parseFloat( number.replace( /\./g, '' ).replace( ',', '.' ) );

		// EU style number format.
		formattedNumber = new Intl.NumberFormat( 'de-DE', { style: 'decimal', maximumFractionDigits: 2 } ).format( normalizeNumber );
	} else {
		// US style number format. Default.
		formattedNumber = new Intl.NumberFormat( 'en-US', { style: 'decimal', maximumFractionDigits: 2 } ).format( parseFloat( number.replace( /,/g, '' ) ) );
	}

	if ( 'NaN' === formattedNumber ) {
		// Bail, if NaN.
		return '';
	}

	return formattedNumber;
}

function initializeNumberField() {
	const numberElement = document.querySelectorAll( '.srfm-number-block' );

	if ( numberElement ) {
		numberElement.forEach( ( element ) => {
			const numberInput = element.querySelector( 'input' );
			if ( numberInput ) {
				numberInput.addEventListener( 'change', ( e ) => {
					numberInput.value = SRFMFormatNumber( e.target.value, numberInput.getAttribute( 'format-type' ) );
				} );
			}
		} );
	}
}
document.addEventListener( 'DOMContentLoaded', initializeNumberField );
