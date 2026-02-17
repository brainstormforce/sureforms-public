/**
 * Shared Styling Utilities
 *
 * Common constants and functions used by both preview-styling.js
 * and elementor-preview-styling.js for consistent styling behavior.
 *
 * @package
 * @since x.x.x
 */

/**
 * Dimension sides for padding/border-radius controls.
 */
export const SIDES = [ 'top', 'right', 'bottom', 'left' ];

/**
 * DIMENSIONS control mappings: controlName → cssVarPrefix
 * These controls have top/right/bottom/left values.
 */
export const DIMENSIONS_CSS_MAP = {
	formPadding: '--srfm-form-padding',
	formBorderRadius: '--srfm-form-border-radius',
};

/**
 * CSS variables set by primaryColor control.
 * Maps cssVariable → opacity (null = use raw value, number = HSL alpha).
 */
export const PRIMARY_COLOR_MAP = {
	'--srfm-color-scheme-primary': null,
	'--srfm-quill-editor-color': null,
	'--srfm-color-input-border-hover': 0.65,
	'--srfm-color-input-border-focus-glow': 0.15,
	'--srfm-color-input-selected': 0.1,
	'--srfm-btn-color-hover': 0.9,
	'--srfm-btn-color-disabled': 0.25,
};

/**
 * CSS variables set by textColor control.
 * Maps cssVariable → opacity (null = use raw value, number = HSL alpha).
 */
export const TEXT_COLOR_MAP = {
	'--srfm-color-scheme-text': null,
	'--srfm-color-input-label': null,
	'--srfm-color-input-text': null,
	'--srfm-color-input-description': 0.65,
	'--srfm-color-input-placeholder': 0.5,
	'--srfm-color-input-prefix': 0.65,
	'--srfm-color-input-background': 0.02,
	'--srfm-color-input-background-hover': 0.05,
	'--srfm-color-input-background-disabled': 0.07,
	'--srfm-color-input-border': 0.25,
	'--srfm-color-input-border-disabled': 0.15,
	'--srfm-color-multi-choice-svg': 0.7,
};

/**
 * Simple CSS variable mappings: controlName → cssVariable
 * For controls that just set a single CSS variable.
 */
export const SIMPLE_CSS_MAP = {
	textOnPrimaryColor: '--srfm-color-scheme-text-on-primary',
	bgImageSize: '--srfm-bg-size',
	bgImagePosition: '--srfm-bg-position',
	bgImageRepeat: '--srfm-bg-repeat',
	bgImageAttachment: '--srfm-bg-attachment',
};

/**
 * Apply color with HSL transformations based on a color map.
 *
 * @param {HTMLElement} container The form container element.
 * @param {Object}      colorMap  Map of cssVariable → opacity (null = raw, number = HSL alpha).
 * @param {string}      val       The color value.
 */
export function applyColorMap( container, colorMap, val ) {
	for ( const cssVar in colorMap ) {
		if ( ! Object.hasOwn( colorMap, cssVar ) ) {
			continue;
		}
		const opacity = colorMap[ cssVar ];
		if ( opacity === null ) {
			container.style.setProperty( cssVar, val );
		} else {
			container.style.setProperty(
				cssVar,
				'hsl(from ' + val + ' h s l / ' + opacity + ')'
			);
		}
	}
}

/**
 * Reset color map CSS variables.
 *
 * @param {HTMLElement} container The form container element.
 * @param {Object}      colorMap  Map of cssVariable → opacity.
 */
export function resetColorMap( container, colorMap ) {
	for ( const cssVar in colorMap ) {
		if ( Object.hasOwn( colorMap, cssVar ) ) {
			container.style.removeProperty( cssVar );
		}
	}
}

/**
 * Apply DIMENSIONS control value (top/right/bottom/left).
 *
 * @param {HTMLElement} container The form container element.
 * @param {string}      prefix    The CSS variable prefix.
 * @param {Object}      val       The dimensions value object with top/right/bottom/left and unit.
 */
export function applyDimensions( container, prefix, val ) {
	if ( typeof val !== 'object' ) {
		return;
	}
	const unit = val.unit || 'px';

	SIDES.forEach( function ( side ) {
		if ( val[ side ] !== '' && val[ side ] !== undefined ) {
			container.style.setProperty(
				prefix + '-' + side,
				val[ side ] + unit
			);
		}
	} );
}

/**
 * Reset DIMENSIONS control (remove all sides).
 *
 * @param {HTMLElement} container The form container element.
 * @param {string}      prefix    The CSS variable prefix.
 */
export function resetDimensions( container, prefix ) {
	SIDES.forEach( function ( side ) {
		container.style.removeProperty( prefix + '-' + side );
	} );
}

/**
 * Apply individual dimension values (for flat styling objects).
 *
 * @param {HTMLElement} container The form container element.
 * @param {string}      prefix    The CSS variable prefix.
 * @param {Object}      styling   The styling object with individual dimension values.
 * @param {string}      propName  The property name prefix (e.g., 'formPadding').
 * @param {string}      unit      The unit to use.
 */
export function applyDimensionsFromStyling(
	container,
	prefix,
	styling,
	propName,
	unit
) {
	SIDES.forEach( function ( side ) {
		const key = propName + side.charAt( 0 ).toUpperCase() + side.slice( 1 );
		if ( styling[ key ] !== undefined ) {
			container.style.setProperty(
				prefix + '-' + side,
				styling[ key ] + unit
			);
		}
	} );
}
