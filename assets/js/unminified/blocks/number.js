function SRFMFormatNumber( number, formatType ) {
	if ( ! number ) {
		return '';
	}

	// Convert all to empty strings except: Numbers, Dots, Commas, and a single leading Minus.
	number = number.replace( /(?!^-)[^0-9,.-]+/g, '' );

	if (
		( number.length === 1 && number === '-' ) ||
		number.endsWith( '.' ) ||
		number.endsWith( ',' )
	) {
		// User is in the middle of typing a number: "-", "2.", or "2,"
		return number;
	}

	const decimalSeparator = formatType === 'eu-style' ? ',' : '.';

	// Check if number has decimal part with trailing zeros
	const preserveTrailingZeros = ( num ) => {
		const parts = num.split( decimalSeparator );
		if ( parts.length === 2 && parts[ 1 ].includes( '0' ) ) {
			return true;
		}
		return false;
	};

	// Store original decimal part if it has trailing zeros
	let originalDecimalPart = '';
	if ( preserveTrailingZeros( number ) ) {
		originalDecimalPart = number.split( decimalSeparator )[ 1 ];
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

	// Preserve trailing zeros if needed
	if ( originalDecimalPart ) {
		formattedNumber =
			formattedNumber.split( decimalSeparator )[ 0 ] +
			decimalSeparator +
			originalDecimalPart;
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

				// Add focus event to remove formatting and add 'srfm-input-focused' class.
				numberInput.addEventListener( 'focus', ( e ) => {
					const parentElement = e.target.closest(
						'.srfm-input-content'
					);
					if ( parentElement ) {
						parentElement.classList.add( 'srfm-input-focused' );
						parentElement.classList.remove( 'srfm-input-filled' );
					}
				} );

				// Add blur event to format the input and update classes based on input value.
				numberInput.addEventListener( 'blur', ( e ) => {
					const parentElement = e.target.closest(
						'.srfm-input-content'
					);
					if ( parentElement ) {
						parentElement.classList.remove( 'srfm-input-focused' );
						if ( e.target.value ) {
							parentElement.classList.add( 'srfm-input-filled' );
						} else {
							parentElement.classList.remove(
								'srfm-input-filled'
							);
						}
					}
				} );

				// Add hover event to add 'srfm-input-hovered' class.
				numberInput.addEventListener( 'mouseenter', ( e ) => {
					const parentElement = e.target.closest(
						'.srfm-input-content'
					);
					if ( parentElement ) {
						parentElement.classList.add( 'srfm-input-hovered' );
					}
				} );
				numberInput.addEventListener( 'mouseleave', ( e ) => {
					const parentElement = e.target.closest(
						'.srfm-input-content'
					);
					if ( parentElement ) {
						parentElement.classList.remove( 'srfm-input-hovered' );
					}
				} );

				// Set default state for 'srfm-input-filled' class based on initial input value.
				if ( numberInput.value ) {
					const parentElement = numberInput.closest(
						'.srfm-input-content'
					);
					if ( parentElement ) {
						parentElement.classList.add( 'srfm-input-filled' );
					}
				}
			}
		} );
	}
}
document.addEventListener( 'DOMContentLoaded', initializeNumberField );

/**
 * Listen for the custom event 'srfm_init_number_field' that is dispatched when number fields need to be initialized.
 * This event is triggered when number fields are dynamically added or updated in the form.
 *
 * The event handler calls initializeNumberField() which sets up all number input functionality including:
 * - Input validation
 * - Focus/blur handling
 * - Hover states
 * - Filled state classes
 */
document.addEventListener( 'srfm_init_number_field', () => {
	initializeNumberField();
} );
