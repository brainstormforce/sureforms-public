<?php
/**
 * Sureforms Block Js.
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Spec_Block_JS' ) ) {

	/**
	 * Class Sureforms_Spec_Block_JS.
	 */
	class Spec_Block_JS {

		/**
		 * Adds Google fonts for Next Step Button.
		 *
		 * @since 0.0.1
		 * @param array $attr the blocks attr.
		 */
		public static function blocks_separator_gfont( $attr ) {

			$element_text_load_google_font = isset( $attr['elementTextLoadGoogleFonts'] ) ? $attr['elementTextLoadGoogleFonts'] : '';
			$element_text_font_family      = isset( $attr['elementTextFontFamily'] ) ? $attr['elementTextFontFamily'] : '';
			$element_text_font_weight      = isset( $attr['elementTextFontWeight'] ) ? $attr['elementTextFontWeight'] : '';

			Spec_Gb_Helper::blocks_google_font( $element_text_load_google_font, $element_text_font_family, $element_text_font_weight );
		}

		/**
		 * Adds Google fonts for Advanced Heading block.
		 *
		 * @since 0.0.1
		 * @param array $attr the blocks attr.
		 */
		public static function blocks_advanced_heading_gfont( $attr ) {

			$head_load_google_font = isset( $attr['headLoadGoogleFonts'] ) ? $attr['headLoadGoogleFonts'] : '';
			$head_font_family      = isset( $attr['headFontFamily'] ) ? $attr['headFontFamily'] : '';
			$head_font_weight      = isset( $attr['headFontWeight'] ) ? $attr['headFontWeight'] : '';

			$subhead_load_google_font = isset( $attr['subHeadLoadGoogleFonts'] ) ? $attr['subHeadLoadGoogleFonts'] : '';
			$subhead_font_family      = isset( $attr['subHeadFontFamily'] ) ? $attr['subHeadFontFamily'] : '';
			$subhead_font_weight      = isset( $attr['subHeadFontWeight'] ) ? $attr['subHeadFontWeight'] : '';

			$highlight_head_load_google_font = isset( $attr['highLightLoadGoogleFonts'] ) ? $attr['highLightLoadGoogleFonts'] : '';
			$highlight_head_font_family      = isset( $attr['highLightFontFamily'] ) ? $attr['highLightFontFamily'] : '';
			$highlight_head_font_weight      = isset( $attr['highLightFontWeight'] ) ? $attr['highLightFontWeight'] : '';

			Spec_Gb_Helper::blocks_google_font( $head_load_google_font, $head_font_family, $head_font_weight );
			Spec_Gb_Helper::blocks_google_font( $subhead_load_google_font, $subhead_font_family, $subhead_font_weight );
			Spec_Gb_Helper::blocks_google_font( $highlight_head_load_google_font, $highlight_head_font_family, $highlight_head_font_weight );
		}

		/**
		 * Adds Google fonts for Advanced Image block.
		 *
		 * @since 0.0.1
		 * @param array $attr the blocks attr.
		 */
		public static function blocks_image_gfont( $attr ) {

			$heading_load_google_font = isset( $attr['headingLoadGoogleFonts'] ) ? $attr['headingLoadGoogleFonts'] : '';
			$heading_font_family      = isset( $attr['headingFontFamily'] ) ? $attr['headingFontFamily'] : '';
			$heading_font_weight      = isset( $attr['headingFontWeight'] ) ? $attr['headingFontWeight'] : '';

			$caption_load_google_font = isset( $attr['captionLoadGoogleFonts'] ) ? $attr['captionLoadGoogleFonts'] : '';
			$caption_font_family      = isset( $attr['captionFontFamily'] ) ? $attr['captionFontFamily'] : '';
			$caption_font_weight      = isset( $attr['captionFontWeight'] ) ? $attr['captionFontWeight'] : '';

			Spec_Gb_Helper::blocks_google_font( $heading_load_google_font, $heading_font_family, $heading_font_weight );
			Spec_Gb_Helper::blocks_google_font( $caption_load_google_font, $caption_font_family, $caption_font_weight );
		}
	}
}
