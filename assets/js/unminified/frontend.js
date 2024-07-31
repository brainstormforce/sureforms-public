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
 * IIFE so that we don't pollute the global variables.
 */
(function () {

	function getComputedBackgroundColor(element) {
		const style = window.getComputedStyle(element);
		return style.backgroundColor || 'transparent';
	}

	function rgbToLuminance(r, g, b) {
		// Convert RGB to the range of [0, 1]
		const [R, G, B] = [r, g, b].map(value => {
			value /= 255;
			return value <= 0.03928 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4);
		});
		// Calculate luminance
		return 0.2126 * R + 0.7152 * G + 0.0722 * B;
	}

	function getColorLuminance(color) {
		// Parse color in different formats
		let r, g, b;
		if (color.startsWith('rgb')) {
			[r, g, b] = color.match(/\d+/g).map(Number);
		} else if (color.startsWith('#')) {
			// Convert hex to RGB
			const hex = color.slice(1);
			if (hex.length === 6) {
				[r, g, b] = [0, 2, 4].map(start => parseInt(hex.substr(start, 2), 16));
			} else if (hex.length === 3) {
				[r, g, b] = hex.split('').map(char => parseInt(char + char, 16));
			}
		} else {
			// Handle other color formats or fall back to default
			return 0.5; // Neutral gray for unknown formats
		}

		return rgbToLuminance(r, g, b);
	}

	function isColorDark(luminance) {
		return luminance < 0.5; // Threshold for darkness
	}

	function getPrimaryBackgroundColor(element) {
		// Get the primary background color
		let color = getComputedBackgroundColor(element);
		let parentColor = getComputedBackgroundColor(element.parentElement);

		let mayBeIsTransparent = ( 'rgba(0, 0, 0, 0)' === color ) && ( color === parentColor );

		while ( ( color === 'transparent' || mayBeIsTransparent ) && element !== document.documentElement) {
			/**
			 * If we are here, then it can mean that the current element is probably transparent.
			 * So lets climb-up the DOM tree, until we find the valid background color.
			 */
			element = element.parentElement;
			color = getComputedBackgroundColor(element);

			parentColor = getComputedBackgroundColor(element.parentElement);
			mayBeIsTransparent = ( 'rgba(0, 0, 0, 0)' === color ) && ( color === parentColor );

			color = mayBeIsTransparent ? 'transparent' : parentColor;
		}

		// Get luminance of the color
		const luminance = getColorLuminance(color);

		// Check if the color is dark or light
		const isDark = isColorDark(luminance);

		return {
			color: color,
			isDark: isDark,
		};
	}

	function normalizeCSSVariablesForDarkBackground(element) {

		const form = element.querySelector('form');

		if (!form) {
			// Probably invalid form.
			return;
		}

		const { isDark } = getPrimaryBackgroundColor(element);

		if (!isDark) {
			return;
		}

		// Add a class in the form container, so that it can be used by other developers as well.
		element.classList.add('srfm-has-dark-bg');

		/**
		 * Lets calculate the form's label color and other elements such as dropdown, and calendar.
		 */
		const labelColor = window.getComputedStyle(element).getPropertyValue('--srfm-color-input-label');

		const labelLuminance = getColorLuminance(labelColor);

		if ( ! isColorDark(labelLuminance) ) {
			const cssVariablesAndColors = {
				"--srfm-color-input-label-inverse": "#181818",
			}

			const cssElements = []

			Object.keys(cssVariablesAndColors).map((cssVariable) => {
				cssElements.push(`${cssVariable}: ${cssVariablesAndColors[cssVariable]};`);
			});

			const styles = `.srfm-form-container-${ form.getAttribute( 'form-id' ) } { ${cssElements.join(' ')} }`;

			element.querySelector('style').innerHTML += styles;
		}
	}

	window.addEventListener("load", function () {

		const formContainers = document.querySelectorAll('.srfm-form-container');

		formContainers.forEach((formContainer) => {
			normalizeCSSVariablesForDarkBackground(formContainer);
		});
	});

})();
