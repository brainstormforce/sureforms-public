/**
 * Adds a key-value pair to the global `window.srfm` object.
 *
 * This function ensures that an object called `srfm` exists in the global `window` scope, and allows
 * developers to store variables, instances, or functions in that object. These stored values can then
 * be accessed globally across different files and components in the project.
 *
 * @param {string} key   - The key under which the value will be stored in the global `window.srfm` object.
 * @param {*}      value - The value to store under the specified key. Can be any type (e.g., string, object, function).
 *
 *                       * @example
 *                       addGlobalSrfmObject('some_key', 'some_value');
 */
function addGlobalSrfmObject( key, value ) {
	// Retrieve the existing `srfm` object from the global `window` object, or create an empty object if it doesn't exist.
	const obj = window?.srfm || {};

	// Assign the provided key-value pair to the `srfm` object.
	obj[ key ] = value;

	// Update the global `window.srfm` object with the modified `srfm` object.
	window.srfm = obj;
}

// Assign the function to the global `window` object so it can be accessed anywhere in the project.
window.addGlobalSrfmObject = addGlobalSrfmObject;

// Functions used for managing the error state and the equivalent of the sprintf() to replace the values in a translatable string.
/**
 * Toggles the "srfm-error" class on the specified container element based on validation results.
 * Additionally, manages the `id` attribute on the error message element to enable screen reader accessibility.
 *
 * @param {HTMLElement} container - The parent container of the input field to validate.
 * @param {boolean}     hasError  - Determines whether to add or remove the error state.
 *
 *                                Description:
 *                                - When `hasError` is true, the function adds the "srfm-error" class to the container, enabling styling for invalid fields.
 *                                - If an error message element with the `data-srfm-id` attribute exists as a child of the container,
 *                                this function assigns its value as the `id` attribute to allow screen readers to announce the error when visible.
 *                                - Conversely, if `hasError` is false, the function removes both the "srfm-error" class and `id` attribute from the
 *                                error message element, making it hidden from screen readers when the field is valid.
 */
function toggleErrorState( container, hasError ) {
	// Check if the current class state already matches the intended state to avoid redundant operations
	if ( container.classList.contains( 'srfm-error' ) === hasError ) {
		return;
	}

	// Toggle the "srfm-error" class based on `hasError` state
	container.classList.toggle( 'srfm-error', hasError );

	// Manage the error message's `id` attribute for screen reader accessibility
	const errorMessage = container.querySelector( '.srfm-error-message' );
	if ( errorMessage ) {
		if ( hasError && errorMessage.hasAttribute( 'data-srfm-id' ) ) {
			// Set the `id` to `data-srfm-id` value to allow screen readers to announce the error message
			errorMessage.id = errorMessage.getAttribute( 'data-srfm-id' );
		} else {
			// Remove the `id` when the field is valid, hiding the message from screen readers
			errorMessage.removeAttribute( 'id' );
		}
	}
}
/**
 * Replaces "%s" or "%1$s", "%2$s", etc., placeholders in a string with provided arguments, in sequence.
 *
 * This function provides basic `sprintf`-style formatting, where each "%s" or "%n$s" placeholder in
 * the `str` parameter is replaced with corresponding values from `args`.
 *
 * @param {string}    str  - The string containing "%s" or "%n$s" placeholders to be replaced.
 * @param {...string} args - Values to replace each "%s" or "%n$s" placeholder in the string.
 *                         Each placeholder is replaced by the respective item in `args`.
 * @return {string} The formatted string with all placeholders replaced by corresponding `args` values.
 *
 * @example
 * srfmSprintfString('You have selected %s', 'Option 2');
 * // Returns: 'You have selected Option 2'
 */
function srfmSprintfString( str, ...args ) {
	// If more than one argument is passed, handle numbered and regular placeholders
	if ( args.length > 1 ) {
		return str.replace( /%(\d*)\$s|%s/g, ( match, index ) => {
			if ( index ) {
				// Handle numbered placeholders like %1$s, %2$s
				const argIndex = parseInt( index, 10 ) - 1;
				if ( argIndex >= args.length ) {
					return ''; // Or any fallback value you prefer
				}
				return String( args[ argIndex ] );
			}
			// Handle regular %s placeholders
			if ( args.length === 0 ) {
				return ''; // Fallback if no arguments are provided
			}
			return String( args[ 0 ] );
		} );
	}

	// If only one argument is passed, replace %s with that argument
	return str.replace( /%s/g, () => {
		if ( args.length === 0 ) {
			return ''; // Fallback if no arguments are provided
		}
		return String( args[ 0 ] ); // Use the first argument for all %s placeholders
	} );
}

// Add the functions in the srfm global object so they can be accessed from anywhere within the project.
addGlobalSrfmObject( 'toggleErrorState', toggleErrorState );
addGlobalSrfmObject( 'srfmSprintfString', srfmSprintfString );

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

	console.log({ "turnstileResponse": response })

	if ( 0 !== response.length ) {
		document.querySelector( '#captcha-error' ).style.display = 'none';
	}
}

function onTurnstileError( error, x, y ) {
	console.log({ "turnstileError": { error, x, y, this: this } })
	// Handle the error as needed

	const getTheTurnStileElement = document.querySelectorAll( '#srfm-cf-sitekey:not(.turnstile-error-added)' );
	getTheTurnStileElement.forEach( ( element ) => {
		const getTheForm = element.closest( '.srfm-form' );
		if ( getTheForm ) {
			
			// document.addEventListener( 'srfm_show_common_form_error', dispatchErrorEvent );

			// Add class
			getTheTurnStileElement.classList.add( 'turnstile-error-added' );
		}
	} );
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
	 * Returns translation ready localized string as per the the key.
	 *
	 * @param {string} key Translation key
	 * @since 1.1.0
	 */
	function getTranslatableString( key ) {
		return window?.srfm_submit?.messages?.[ key ] || '';
	}

	// Add 'getTranslatableString' to global scope under srfm object.
	addGlobalSrfmObject( 'getTranslatableString', getTranslatableString );

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
		const style = element ? window.getComputedStyle( element ) : null;
		return style?.backgroundColor || 'transparent';
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
			return value <= 0.03928
				? value / 12.92
				: Math.pow( ( value + 0.055 ) / 1.055, 2.4 );
		} );
		// Calculate luminance
		const red = 0.2126 * R;
		const green = 0.7152 * G;
		const blue = 0.0722 * B;
		return red + green + blue;
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
				[ r, g, b ] = [ 0, 2, 4 ].map( ( start ) =>
					parseInt( hex.substr( start, 2 ), 16 )
				);
			} else if ( hex.length === 3 ) {
				[ r, g, b ] = hex
					.split( '' )
					.map( ( char ) => parseInt( char + char, 16 ) );
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

		let mayBeIsTransparent =
			'rgba(0, 0, 0, 0)' === color && color === parentColor;

		while (
			( color === 'transparent' || mayBeIsTransparent ) &&
			element !== document.documentElement
		) {
			/**
			 * If we are here, then it can mean that the current element is probably transparent.
			 * So lets climb-up the DOM tree, until we find the valid background color.
			 */
			element = element.parentElement;
			color = getComputedBackgroundColor( element );

			parentColor = getComputedBackgroundColor( element.parentElement );
			mayBeIsTransparent =
				'rgba(0, 0, 0, 0)' === color && color === parentColor;

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
	 * The function can further be used to adjust the form's label color and other related elements (such as dropdowns and calendars)
	 * based on whether the existing text color is dark or light. If the text color is not dark, it updates CSS variables
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

		if ( isDark ) {
			// Add a class in the form container if background color is dark, so that it can be used by other developers as well.
			element.classList.add( 'srfm-has-dark-bg' );
		}

		/**
		 * Lets calculate the form's text color.
		 */
		const textColor = window
			.getComputedStyle( element )
			.getPropertyValue( '--srfm-color-input-text' );

		const textLuminance = getColorLuminance( textColor );

		if ( ! isColorDark( textLuminance ) ) {
			const cssVariablesAndColors = {
				'--srfm-expandable-menu-background': '#2e2e2e',
			};

			const cssElements = [];

			Object.keys( cssVariablesAndColors ).forEach( ( cssVariable ) => {
				cssElements.push(
					`${ cssVariable }: ${ cssVariablesAndColors[ cssVariable ] };`
				);
			} );

			const styles = `.srfm-form-container-${ form.getAttribute(
				'form-id'
			) } { ${ cssElements.join( ' ' ) } }`;

			element.querySelector( 'style' ).innerHTML += styles;
		}
	}

	/**
	 * Ensures that the element with the class 'srfm-branding' is always fully visible.
	 *
	 * This function checks the computed styles of the element with the class 'srfm-branding'
	 * every second to ensure that its opacity is set to 1, its visibility is set to 'visible',
	 * and its display property is set to 'block'. If any of these styles are not set correctly,
	 * they are updated to ensure the element is always fully visible.
	 *
	 * Note: This function relies on the presence of the element with the class 'srfm-branding'
	 * in the DOM. If the element is not found, the function exits early without making any changes.
	 */
	function handleInstantFormBranding() {
		const srfmBranding = document.querySelector( '.srfm-branding' );

		if ( ! srfmBranding ) {
			return;
		}

		const srfmForm = document.querySelector( 'form.srfm-form' );

		setInterval( () => {
			if ( 'none' === window.getComputedStyle( srfmForm ).display ) {
				// Hide the SureForms branding if Form is hidden.
				srfmBranding.style.display = 'none';
				return;
			}

			const { opacity, visibility, display } =
				window.getComputedStyle( srfmBranding );

			if ( opacity < 1 ) {
				srfmBranding.style.opacity = 1;
			}

			if ( 'visible' !== visibility ) {
				srfmBranding.style.visibility = 'visible';
			}

			if ( 'none' === display ) {
				srfmBranding.style.display = 'block';
			}
		}, 100 );
	}

	/**
	 * Adjusts the height of the `.srfm-form-wrapper` element to match the height of the
	 * `.srfm-form-container` element, but only if the current page contains the
	 * `#srfm-single-page-container` element.
	 *
	 * This function is used to ensure that the form wrapper's height dynamically
	 * matches the height of the form container, which can be important for maintaining
	 * layout consistency on pages where the form content may change in size.
	 *
	 * It performs the following steps:
	 * 1. Checks if the `#srfm-single-page-container` element exists in the DOM.
	 *    - If not, the function exits early as it indicates the function is not
	 *      running on the relevant page.
	 * 2. Selects the `.srfm-form-container` element.
	 *    - If this element is found, it sets the height of the `.srfm-form-wrapper`
	 *      element to the height of the `.srfm-form-container` element.
	 *
	 * The height is set using inline CSS, applying the `clientHeight` of the form
	 * container, ensuring the wrapper matches the container's height.
	 */
	function handleInstantFormWrapperHeight() {
		if ( ! document.getElementById( 'srfm-single-page-container' ) ) {
			// Bail if we are not in the Instant Form page.
			return;
		}

		const formContainer = document.querySelector( '.srfm-form-container' );

		if ( formContainer ) {
			if (
				'absolute' === window.getComputedStyle( formContainer ).position
			) {
				document.querySelector(
					'.srfm-form-wrapper'
				).style.height = `${ formContainer.clientHeight }px`;
			}
		}
	}
	window.addEventListener( 'resize', handleInstantFormWrapperHeight ); // Handle wrapper height on window resize.
	window.addEventListener( 'mousemove', handleInstantFormWrapperHeight ); // Recalculate the wrapper height on mouse move.

	window.addEventListener( 'load', function () {
		handleInstantFormWrapperHeight();
		handleInstantFormBranding();

		const formContainers = document.querySelectorAll(
			'.srfm-form-container'
		);

		formContainers.forEach( ( formContainer ) => {
			normalizeCSSVariablesForDarkBackground( formContainer );
		} );
	} );

	// Adding the `handleInstantFormWrapperHeight` function to the global `srfm` object.
	// This allows the function to be accessed from other parts of the application,
	// enabling consistent handling of the form wrapper height across different modules.
	addGlobalSrfmObject(
		'handleInstantFormWrapperHeight',
		handleInstantFormWrapperHeight
	);

	const UTILITY = {
		prepareValue( value = [] ) {
			// Validates the input value to ensure it is an array.
			// If the value is not an array or is empty, return an empty string.
			// Otherwise, join the array elements with " | " as a delimiter.
			if ( ! value?.length || ! Array.isArray( value ) ) {
				return '';
			}

			return value.join( ' | ' );
		},
		extractValue( value = '' ) {
			// Validates the input value to ensure it is a non-empty string.
			// If the value is not a string or is empty, return an empty array.
			// Otherwise, split the string by " | " to return an array of values.
			if ( typeof value !== 'string' || value.trim() === '' ) {
				return [];
			}

			return value.split( ' | ' );
		},
	};

	// Adding the `UTILITY` object to the global `srfm` object.
	// This allows the object to be accessed from other parts of the application,
	// enabling consistent handling of utility functions across different modules.
	addGlobalSrfmObject( 'srfmUtility', UTILITY );
}() );
