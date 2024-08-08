// Sender's Email.

const emailElements = document.getElementsByClassName(
	'srfm-input-email-container'
);

if ( emailElements.length > 0 ) {
	const emailAddress = document.getElementsByClassName( 'srfm-input-email' );
	emailAddress[ 0 ].addEventListener( 'input', ( e ) => {
		document.querySelector( '#srfm-sender-email' ).value = e.target.value;
	} );
}

//submit-button CSS

const submitButton = document.getElementsByClassName( 'srfm-button' );
if ( submitButton ) {
	// eslint-disable-next-line
	const rootStyles = getComputedStyle( document.documentElement );
	const primaryColorValue = rootStyles.getPropertyValue(
		'--srfm-primary-color'
	);
	const secondaryColorValue = rootStyles.getPropertyValue(
		'--srfm-secondary-color'
	);

	if ( primaryColorValue !== '' ) {
		for ( let i = 0; i < submitButton.length; i++ ) {
			submitButton[ i ].style.backgroundColor = primaryColorValue;
		}
	}
	if ( secondaryColorValue !== '' ) {
		for ( let i = 0; i < submitButton.length; i++ ) {
			submitButton[ i ].style.color = secondaryColorValue;
		}
	}
}
// eslint-disable-next-line
function onSuccess( response ) {
	if ( 0 !== response.length ) {
		document.querySelector( '#captcha-error' ).style.display = 'none';
	}
}

/**
 * An Immediately Invoked Function Expression (IIFE) is a function that is defined
 * and executed immediately after its creation. It is used to create a new scope,
 * avoiding polluting the global namespace and encapsulating variables.
 *
 * Example:
 * (function() {
 *     var localVariable = 'This is local';
 *     console.log(localVariable); // Outputs: This is local
 * })();
 */
( function () {
	/**
	 * Retrieves the computed background color of a given HTML element.
	 *
	 * This function uses the `window.getComputedStyle` method to obtain the computed style of the specified
	 * element and returns the background color. If the background color is not explicitly set, it defaults
	 * to `'transparent'`.
	 *
	 * @param {HTMLElement} element - The HTML element whose computed background color is to be retrieved.
	 * @return {string} The computed background color of the element in CSS color format (e.g., `rgb(255, 255, 255)`, `rgba(0, 0, 0, 0)`, or `transparent`).
	 */
	function getComputedBackgroundColor( element ) {
		const style = window.getComputedStyle( element );
		return style.backgroundColor || 'transparent';
	}

	/**
	 * Calculates the luminance of a color given its RGB components.
	 *
	 * The luminance is calculated using the formula specified by the W3C for relative luminance, which
	 * accounts for the perceptual importance of different color channels. The RGB values are first normalized
	 * to the range [0, 1], then converted using gamma correction, and finally combined using the weighted
	 * formula to produce the luminance.
	 *
	 * @param {number} r - The red component of the color, in the range [0, 255].
	 * @param {number} g - The green component of the color, in the range [0, 255].
	 * @param {number} b - The blue component of the color, in the range [0, 255].
	 * @return {number} The luminance of the color, which is a value in the range [0, 1]. A value of 0 represents
	 *   black, and a value of 1 represents white.
	 */
	function rgbToLuminance( r, g, b ) {
		// Convert RGB to the range of [0, 1]
		const [ R, G, B ] = [ r, g, b ].map( ( value ) => {
			value /= 255;
			return value <= 0.03928 ? value / 12.92 : Math.pow( ( value + 0.055 ) / 1.055, 2.4 );
		} );
		// Calculate luminance
		return ( 0.2126 * R ) + ( 0.7152 * G ) + ( 0.0722 * B );
	}

	/**
	 * Calculates the luminance of a color given in various formats.
	 *
	 * This function parses a color provided as a string in different formats (e.g., RGB, HEX) and computes its
	 * luminance using the `rgbToLuminance` function. The color can be specified in RGB format (e.g., `rgb(255, 0, 0)`)
	 * or HEX format (e.g., `#ff0000`). If the color format is not recognized, the function defaults to a neutral gray
	 * luminance value of 0.5.
	 *
	 * @param {string} color - The color to be parsed and converted to luminance. It can be in the format:
	 *                       - `rgb(r, g, b)` where `r`, `g`, and `b` are integer values between 0 and 255.
	 *                       - `#rrggbb` or `#rgb` where `rr`, `gg`, `bb` are hexadecimal values or `r`, `g`, `b` are single hexadecimal digits.
	 * @return {number} The luminance of the color, which is a value in the range [0, 1]. A value of 0 represents
	 *   black, and a value of 1 represents white. For unrecognized color formats, a default luminance of 0.5 is returned.
	 */
	function getColorLuminance( color ) {
		// Parse color in different formats
		let r, g, b;
		if ( color.startsWith( 'rgb' ) ) {
			[ r, g, b ] = color.match( /\d+/g ).map( Number );
		} else if ( color.startsWith( '#' ) ) {
			// Convert hex to RGB
			const hex = color.slice( 1 );
			if ( hex.length === 6 ) {
				[ r, g, b ] = [ 0, 2, 4 ].map( ( start ) => parseInt( hex.substr( start, 2 ), 16 ) );
			} else if ( hex.length === 3 ) {
				[ r, g, b ] = hex.split( '' ).map( ( char ) => parseInt( char + char, 16 ) );
			}
		} else {
			// Handle other color formats or fall back to default
			return 0.5; // Neutral gray for unknown formats
		}

		return rgbToLuminance( r, g, b );
	}

	/**
	 * Determines whether a color is considered dark based on its luminance.
	 *
	 * This function uses a simple threshold to classify the color as dark or not. A luminance value below 0.5
	 * is considered dark, while a value of 0.5 or above is considered light. This threshold is based on the
	 * standard practice for evaluating color brightness.
	 *
	 * @param {number} luminance - The luminance of the color, which is a value in the range [0, 1]. A value of 0 represents black, and a value of 1 represents white.
	 * @return {boolean} `true` if the luminance value is less than 0.5 (indicating a dark color); otherwise, `false`.
	 */
	function isColorDark( luminance ) {
		return luminance < 0.5; // Threshold for darkness
	}

	/**
	 * Retrieves the primary background color of an HTML element, traversing up the DOM tree if necessary,
	 * and determines whether the color is dark or light.
	 *
	 * This function examines the computed background color of the specified element and, if the color is
	 * transparent or matches the background color of its parent element, it continues to traverse up the
	 * DOM tree until a non-transparent background color is found or it reaches the root element.
	 *
	 * The function then calculates the luminance of the determined background color to assess if it is dark
	 * or light, using appropriate utility functions.
	 *
	 * @param {HTMLElement} element - The HTML element whose primary background color is to be determined.
	 * @return {Object} An object containing:
	 *   - {string} color - The computed background color of the element. If the color is transparent, the
	 *     color of the nearest ancestor with a non-transparent background will be returned.
	 *   - {boolean} isDark - A boolean indicating whether the background color is dark (`true`) or light
	 *     (`false`), based on its luminance.
	 */
	function getPrimaryBackgroundColor( element ) {
		// Get the primary background color
		let color = getComputedBackgroundColor( element );
		let parentColor = getComputedBackgroundColor( element.parentElement );

		let mayBeIsTransparent = ( 'rgba(0, 0, 0, 0)' === color ) && ( color === parentColor );

		while ( ( color === 'transparent' || mayBeIsTransparent ) && element !== document.documentElement ) {
			/**
			 * If we are here, then it can mean that the current element is probably transparent.
			 * So lets climb-up the DOM tree, until we find the valid background color.
			 */
			element = element.parentElement;
			color = getComputedBackgroundColor( element );

			parentColor = getComputedBackgroundColor( element.parentElement );
			mayBeIsTransparent = ( 'rgba(0, 0, 0, 0)' === color ) && ( color === parentColor );

			color = mayBeIsTransparent ? 'transparent' : parentColor;
		}

		// Get luminance of the color
		const luminance = getColorLuminance( color );

		// Check if the color is dark or light
		const isDark = isColorDark( luminance );

		return {
			color,
			isDark,
		};
	}

	/**
	 * Normalizes CSS variables for a form element based on the background color of its container.
	 *
	 * This function first checks if the given element contains a form. If no form is found, it exits early.
	 * It then determines if the background color of the container element is dark. If the background is dark,
	 * it adds a specific class (`'srfm-has-dark-bg'`) to the container element for potential use by other styles or scripts.
	 *
	 * The function further adjusts the form's label color and other related elements (such as dropdowns and calendars)
	 * based on whether the existing label color is dark or light. If the label color is not dark, it updates CSS variables
	 * to ensure proper visibility on a dark background, and appends these styles to a `<style>` tag within the element.
	 *
	 * @param {HTMLElement} element - The container element which includes the form and other related elements. This element should contain a form and be styled appropriately to have a background color that is checked for darkness.
	 * @return {void} This function does not return a value. It modifies the DOM by adding classes and updating styles as needed based on the background color of the container element.
	 */
	function normalizeCSSVariablesForDarkBackground( element ) {
		const form = element.querySelector( 'form' );

		if ( ! form ) {
			// Probably invalid form.
			return;
		}

		const { isDark } = getPrimaryBackgroundColor( element );

		if ( ! isDark ) {
			return;
		}

		// Add a class in the form container, so that it can be used by other developers as well.
		element.classList.add( 'srfm-has-dark-bg' );

		/**
		 * Lets calculate the form's label color and other elements such as dropdown, and calendar.
		 */
		const labelColor = window.getComputedStyle( element ).getPropertyValue( '--srfm-color-input-label' );

		const labelLuminance = getColorLuminance( labelColor );

		if ( ! isColorDark( labelLuminance ) ) {
			const cssVariablesAndColors = {
				'--srfm-color-input-label-inverse': '#181818',
			};

			const cssElements = [];

			Object.keys( cssVariablesAndColors ).forEach( ( cssVariable ) => {
				cssElements.push( `${ cssVariable }: ${ cssVariablesAndColors[ cssVariable ] };` );
			} );

			const styles = `.srfm-form-container-${ form.getAttribute( 'form-id' ) } { ${ cssElements.join( ' ' ) } }`;

			element.querySelector( 'style' ).innerHTML += styles;
		}
	}

	/**
	 * Sets the dropdown menu background color based on the text color retrieved.
	 * 
	 * @param {HTMLElement} element - The container element which includes the form.
	 * @returns {void} This function does not return a value. It modifies the DOM by updating the dropdown menu background color based on the text color.
	 */
	function setDropdownMenuBackground( element ) {
		/**
		 * Calculate the form's text color and check if it is dark or light.
		 */
		const textColor = window.getComputedStyle( element ).getPropertyValue( '--srfm-color-input-text' );
		const isTextDark = isColorDark( getColorLuminance( textColor ) );
		if ( ! isTextDark ) {
			document.documentElement.style.setProperty('--srfm-dropdown-menu-background', '#2e2e2e');
		}
	}

	window.addEventListener( 'load', function () {
		const formContainers = document.querySelectorAll( '.srfm-form-container' );

		formContainers.forEach( ( formContainer ) => {
			normalizeCSSVariablesForDarkBackground( formContainer );
			setDropdownMenuBackground( formContainer );
		} );
	} );
}() );
