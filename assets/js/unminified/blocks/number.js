function SRFMFormatNumber( number, formatType ) {
	if ( ! number ) {
		return '';
	}

	let formattedNumber = '';
	const formatOptions = { style: 'decimal', maximumFractionDigits: 20 };

	if ( 'eu-style' === formatType ) {
		const normalizeNumber = parseFloat(
			number.replace( /\./g, '' ).replace( ',', '.' )
		);

		// EU style number format.
		formattedNumber = new Intl.NumberFormat(
			'de-DE',
			formatOptions
		).format( normalizeNumber );
	} else {
		// US style number format. Default.
		formattedNumber = new Intl.NumberFormat(
			'en-US',
			formatOptions
		).format( parseFloat( number.replace( /,/g, '' ) ) );
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
				// Things we want to reflect on real time: "input".
				numberInput.addEventListener( 'input', ( e ) => {
					// Convert all to empty strings except: Numbers, Dots, Commas, and a single leading Minus.
					numberInput.value = e.target.value.replace(
						/(?!^-)[^0-9,.-]+/g,
						''
					);
				} );

				// Things we want to process after user changes the focus, to save performance: "change".
				numberInput.addEventListener( 'change', ( e ) => {
					numberInput.value = SRFMFormatNumber(
						e.target.value,
						numberInput.getAttribute( 'format-type' )
					);
				} );

				// Trigger the change event to format the default value.
				numberInput.dispatchEvent( new Event( 'change' ) );
			}
		} );
	}
}
document.addEventListener( 'DOMContentLoaded', initializeNumberField );
