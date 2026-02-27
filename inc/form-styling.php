<?php
/**
 * Form Styling Handler.
 *
 * Handles form styling customization for both embed (srfm/form block)
 * and instant forms. Maps block attributes to form styling array and
 * provides filter hooks for Pro to extend with theme functionality.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Form_Styling class.
 *
 * @since x.x.x
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
	 * @since x.x.x
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
			$form_styling['primary_color'] = $block_attrs['primaryColor'];
		}
		if ( ! empty( $block_attrs['textColor'] ) ) {
			$form_styling['text_color'] = $block_attrs['textColor'];
		}
		if ( ! empty( $block_attrs['textOnPrimaryColor'] ) ) {
			$form_styling['text_color_on_primary'] = $block_attrs['textOnPrimaryColor'];
		}

		// Padding.
		if ( isset( $block_attrs['formPaddingTop'] ) && is_scalar( $block_attrs['formPaddingTop'] ) ) {
			$form_styling['form_padding_top'] = floatval( $block_attrs['formPaddingTop'] );
		}
		if ( isset( $block_attrs['formPaddingRight'] ) && is_scalar( $block_attrs['formPaddingRight'] ) ) {
			$form_styling['form_padding_right'] = floatval( $block_attrs['formPaddingRight'] );
		}
		if ( isset( $block_attrs['formPaddingBottom'] ) && is_scalar( $block_attrs['formPaddingBottom'] ) ) {
			$form_styling['form_padding_bottom'] = floatval( $block_attrs['formPaddingBottom'] );
		}
		if ( isset( $block_attrs['formPaddingLeft'] ) && is_scalar( $block_attrs['formPaddingLeft'] ) ) {
			$form_styling['form_padding_left'] = floatval( $block_attrs['formPaddingLeft'] );
		}
		if ( ! empty( $block_attrs['formPaddingUnit'] ) ) {
			$form_styling['form_padding_unit'] = $block_attrs['formPaddingUnit'];
		}

		// Border Radius.
		if ( isset( $block_attrs['formBorderRadiusTop'] ) && is_scalar( $block_attrs['formBorderRadiusTop'] ) ) {
			$form_styling['form_border_radius_top'] = floatval( $block_attrs['formBorderRadiusTop'] );
		}
		if ( isset( $block_attrs['formBorderRadiusRight'] ) && is_scalar( $block_attrs['formBorderRadiusRight'] ) ) {
			$form_styling['form_border_radius_right'] = floatval( $block_attrs['formBorderRadiusRight'] );
		}
		if ( isset( $block_attrs['formBorderRadiusBottom'] ) && is_scalar( $block_attrs['formBorderRadiusBottom'] ) ) {
			$form_styling['form_border_radius_bottom'] = floatval( $block_attrs['formBorderRadiusBottom'] );
		}
		if ( isset( $block_attrs['formBorderRadiusLeft'] ) && is_scalar( $block_attrs['formBorderRadiusLeft'] ) ) {
			$form_styling['form_border_radius_left'] = floatval( $block_attrs['formBorderRadiusLeft'] );
		}
		if ( ! empty( $block_attrs['formBorderRadiusUnit'] ) ) {
			$form_styling['form_border_radius_unit'] = $block_attrs['formBorderRadiusUnit'];
		}

		// Background.
		if ( ! empty( $block_attrs['bgType'] ) ) {
			$form_styling['bg_type'] = $block_attrs['bgType'];
		}
		if ( ! empty( $block_attrs['bgColor'] ) ) {
			$form_styling['bg_color'] = $block_attrs['bgColor'];
		}
		if ( ! empty( $block_attrs['bgGradient'] ) ) {
			$form_styling['bg_gradient'] = $block_attrs['bgGradient'];
		}
		if ( ! empty( $block_attrs['bgImage'] ) ) {
			$form_styling['bg_image'] = $block_attrs['bgImage'];
		}
		if ( ! empty( $block_attrs['bgImagePosition'] ) ) {
			$form_styling['bg_image_position'] = $block_attrs['bgImagePosition'];
		}
		if ( ! empty( $block_attrs['bgImageSize'] ) ) {
			$form_styling['bg_image_size'] = $block_attrs['bgImageSize'];
		}
		if ( ! empty( $block_attrs['bgImageRepeat'] ) ) {
			$form_styling['bg_image_repeat'] = $block_attrs['bgImageRepeat'];
		}
		if ( ! empty( $block_attrs['bgImageAttachment'] ) ) {
			$form_styling['bg_image_attachment'] = $block_attrs['bgImageAttachment'];
		}

		// Field Spacing and Button Alignment.
		if ( ! empty( $block_attrs['fieldSpacing'] ) ) {
			$form_styling['field_spacing'] = $block_attrs['fieldSpacing'];
		}
		if ( ! empty( $block_attrs['buttonAlignment'] ) ) {
			$form_styling['submit_button_alignment'] = $block_attrs['buttonAlignment'];
		}

		/**
		 * Filter to allow Pro to extend block attribute mapping.
		 *
		 * @param array<string,mixed> $form_styling Modified form styling array.
		 * @param array<string,mixed> $block_attrs  Original block attributes.
		 * @since x.x.x
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
	 * @since x.x.x
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
		 * @since x.x.x
		 */
		return apply_filters( 'srfm_apply_form_theme_styling', $form_styling, $theme_slug );
	}

	/**
	 * Check if embed has custom styling (formTheme is not 'inherit').
	 *
	 * @param array<string,mixed> $block_attrs Block attributes.
	 * @return bool True if using custom embed styling.
	 * @since x.x.x
	 */
	public static function has_custom_styling( $block_attrs ) {
		return 'inherit' !== ( $block_attrs['formTheme'] ?? 'inherit' );
	}
}
