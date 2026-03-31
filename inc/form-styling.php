<?php
/**
 * Form Styling Handler.
 *
 * Handles form styling customization for both embed (srfm/form block)
 * and instant forms. Maps block attributes to form styling array and
 * provides filter hooks for Pro to extend with theme functionality.
 *
 * @package sureforms
 * @since 2.7.0
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Form_Styling class.
 *
 * @since 2.7.0
 */
class Form_Styling {
	/**
	 * Map block attributes to form styling array.
	 *
	 * Converts camelCase block attributes to snake_case form styling keys.
	 * This allows per-embed customization when formTheme is not 'inherit'.
	 *
	 * @param array<string,mixed> $form_styling Existing form styling from post meta.
	 * @param array<string,mixed> $block_attrs  Block attributes from srfm/form block.
	 * @return array<string,mixed> Modified form styling array.
	 * @since 2.7.0
	 */
	public static function map_block_attrs_to_styling( $form_styling, $block_attrs ) {
		if ( empty( $block_attrs ) ) {
			return $form_styling;
		}

		// Form Theme - allows Pro to add custom themes.
		if ( ! empty( $block_attrs['formTheme'] ) ) {
			$form_styling['form_theme'] = Helper::get_string_value( $block_attrs['formTheme'] );

			// Apply theme styling if a theme is selected.
			$form_styling = self::apply_theme_styling( $form_styling, Helper::get_string_value( $block_attrs['formTheme'] ) );
		}

		// Colors.
		if ( ! empty( $block_attrs['primaryColor'] ) ) {
			$form_styling['primary_color'] = Helper::sanitize_css_value( $block_attrs['primaryColor'] );
		}
		if ( ! empty( $block_attrs['textColor'] ) ) {
			$form_styling['text_color'] = Helper::sanitize_css_value( $block_attrs['textColor'] );
		}
		if ( ! empty( $block_attrs['textOnPrimaryColor'] ) ) {
			$form_styling['text_color_on_primary'] = Helper::sanitize_css_value( $block_attrs['textOnPrimaryColor'] );
		}

		// Padding.
		$form_styling = self::map_dimension_attrs( $form_styling, $block_attrs, 'formPadding', 'form_padding' );

		// Border Radius.
		$form_styling = self::map_dimension_attrs( $form_styling, $block_attrs, 'formBorderRadius', 'form_border_radius' );

		// Background.
		if ( ! empty( $block_attrs['bgType'] ) && in_array( $block_attrs['bgType'], [ 'color', 'gradient', 'image' ], true ) ) {
			$form_styling['bg_type'] = $block_attrs['bgType'];
		}
		if ( ! empty( $block_attrs['bgColor'] ) ) {
			$form_styling['bg_color'] = Helper::sanitize_css_value( $block_attrs['bgColor'] );
		}
		if ( ! empty( $block_attrs['bgGradient'] ) ) {
			$form_styling['bg_gradient'] = Helper::sanitize_css_value( $block_attrs['bgGradient'] );
		}
		if ( ! empty( $block_attrs['bgImage'] ) ) {
			$form_styling['bg_image'] = esc_url_raw( Helper::get_string_value( $block_attrs['bgImage'] ) );
		}
		if ( ! empty( $block_attrs['bgImagePosition'] ) ) {
			$form_styling['bg_image_position'] = $block_attrs['bgImagePosition'];
		}
		if ( ! empty( $block_attrs['bgImageSize'] ) && in_array( $block_attrs['bgImageSize'], [ 'auto', 'cover', 'contain' ], true ) ) {
			$form_styling['bg_image_size'] = $block_attrs['bgImageSize'];
		}
		if ( ! empty( $block_attrs['bgImageRepeat'] ) && in_array( $block_attrs['bgImageRepeat'], [ 'repeat', 'no-repeat', 'repeat-x', 'repeat-y' ], true ) ) {
			$form_styling['bg_image_repeat'] = $block_attrs['bgImageRepeat'];
		}
		if ( ! empty( $block_attrs['bgImageAttachment'] ) && in_array( $block_attrs['bgImageAttachment'], [ 'scroll', 'fixed', 'local' ], true ) ) {
			$form_styling['bg_image_attachment'] = $block_attrs['bgImageAttachment'];
		}

		// Field Spacing and Button Alignment.
		if ( ! empty( $block_attrs['fieldSpacing'] ) && in_array( $block_attrs['fieldSpacing'], [ 'small', 'medium', 'large' ], true ) ) {
			$form_styling['field_spacing'] = $block_attrs['fieldSpacing'];
		}
		if ( ! empty( $block_attrs['buttonAlignment'] ) && in_array( $block_attrs['buttonAlignment'], [ 'left', 'center', 'right', 'full', 'justify' ], true ) ) {
			$form_styling['submit_button_alignment'] = $block_attrs['buttonAlignment'];
		}

		/**
		 * Filter to allow Pro to extend block attribute mapping.
		 *
		 * @param array<string,mixed> $form_styling Modified form styling array.
		 * @param array<string,mixed> $block_attrs  Original block attributes.
		 * @since 2.7.0
		 */
		return apply_filters( 'srfm_embed_block_attrs_to_styling', $form_styling, $block_attrs );
	}

	/**
	 * Apply theme styling to form styling array.
	 *
	 * Returns theme-specific CSS variable values. In the free version,
	 * this returns the original styling. Pro overrides via filter to
	 * apply predefined theme presets.
	 *
	 * @param array<string,mixed> $form_styling Current form styling array.
	 * @param string              $theme_slug   Theme identifier (e.g., 'modern', 'minimal').
	 * @return array<string,mixed> Form styling with theme values applied.
	 * @since 2.7.0
	 */
	public static function apply_theme_styling( $form_styling, $theme_slug ) {
		if ( empty( $theme_slug ) || 'default' === $theme_slug || 'inherit' === $theme_slug ) {
			return $form_styling;
		}

		/**
		 * Filter to apply theme-specific styling.
		 *
		 * Pro uses this filter to merge predefined theme CSS variables
		 * into the form styling array.
		 *
		 * @param array<string,mixed> $form_styling Current form styling array.
		 * @param string              $theme_slug   Theme identifier.
		 * @since 2.7.0
		 */
		return apply_filters( 'srfm_apply_form_theme_styling', $form_styling, $theme_slug );
	}

	/**
	 * Check if embed has custom styling (formTheme is not 'inherit').
	 *
	 * @param array<string,mixed> $block_attrs Block attributes.
	 * @return bool True if using custom embed styling.
	 * @since 2.7.0
	 */
	public static function has_custom_styling( $block_attrs ) {
		return 'inherit' !== ( $block_attrs['formTheme'] ?? 'inherit' );
	}

	/**
	 * Map dimension block attributes (Top/Right/Bottom/Left/Unit) to form styling.
	 *
	 * @param array<string,mixed> $form_styling Form styling array.
	 * @param array<string,mixed> $block_attrs  Block attributes.
	 * @param string              $attr_prefix  Block attribute prefix (e.g., 'formPadding').
	 * @param string              $style_prefix Form styling key prefix (e.g., 'form_padding').
	 * @return array<string,mixed> Modified form styling array.
	 * @since 2.7.0
	 */
	private static function map_dimension_attrs( $form_styling, $block_attrs, $attr_prefix, $style_prefix ) {
		$sides = [ 'Top', 'Right', 'Bottom', 'Left' ];

		foreach ( $sides as $side ) {
			$attr_key  = $attr_prefix . $side;
			$style_key = $style_prefix . '_' . strtolower( $side );

			if ( isset( $block_attrs[ $attr_key ] ) && is_scalar( $block_attrs[ $attr_key ] ) ) {
				$form_styling[ $style_key ] = floatval( $block_attrs[ $attr_key ] );
			}
		}

		$unit_attr_key  = $attr_prefix . 'Unit';
		$unit_style_key = $style_prefix . '_unit';

		if ( ! empty( $block_attrs[ $unit_attr_key ] ) && in_array( $block_attrs[ $unit_attr_key ], [ 'px', 'em', 'rem', '%', 'vw', 'vh' ], true ) ) {
			$form_styling[ $unit_style_key ] = $block_attrs[ $unit_attr_key ];
		}

		return $form_styling;
	}
}
