<?php
/**
 * WCFB - Checkout Form Styler.
 *
 * @package WCFB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCFB_Image' ) ) {

	/**
	 * Class WCFB_Image.
	 */
	class WCFB_Image {

		/**
		 * Member Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 *  Initiator
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {

			// Activation hook.
			add_action( 'init', array( $this, 'register_blocks' ) );
		}

		/**
		 * Registers the `core/latest-posts` block on server.
		 *
		 * @since 0.0.1
		 */
		public function register_blocks() {

			// Check if the register function exists.
			if ( ! function_exists( 'register_block_type' ) ) {
				return;
			}

			$image_border_attributes   = Cartflows_Block_Config::generate_border_attribute( 'overlay' );
			$overlay_border_attributes = Cartflows_Block_Config::generate_border_attribute( 'image' );

			$attr = array_merge(
				array(
					'block_id'                    =>
					array(
						'type' => 'string',
					),
					'isPreview'                   =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'layout'                      =>
					array(
						'type'         => 'string',
						'default'      => 'default',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-layout',
						),
					),
					'url'                         =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'urlTablet'                   =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'urlMobile'                   =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'alt'                         =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'enableCaption'               =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'caption'                     =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'align'                       =>
					array(
						'type'         => 'string',
						'default'      => '',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-align',
						),
					),
					'alignTablet'                 =>
					array(
						'type'         => 'string',
						'default'      => '',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-align-tablet',
						),
					),
					'alignMobile'                 =>
					array(
						'type'         => 'string',
						'default'      => '',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-align-mobile',
						),
					),
					'id'                          =>
					array(
						'type'    => 'integer',
						'default' => '',
					),
					'href'                        =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'rel'                         =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'linkClass'                   =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'linkDestination'             =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'title'                       =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'width'                       =>
					array(
						'type'         => 'integer',
						'default'      => '',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-width',
						),
					),
					'widthTablet'                 =>
					array(
						'type'         => 'integer',
						'default'      => '',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-width-tablet',
						),
					),
					'widthMobile'                 =>
					array(
						'type'         => 'integer',
						'default'      => '',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-width-mobile',
						),
					),
					'height'                      =>
					array(
						'type'         => 'integer',
						'default'      => '',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-height',
						),
					),
					'heightTablet'                =>
					array(
						'type'         => 'integer',
						'default'      => '',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-height-tablet',
						),
					),
					'heightMobile'                =>
					array(
						'type'         => 'integer',
						'default'      => '',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-height-mobile',
						),
					),
					'naturalWidth'                =>
					array(
						'type'    => 'integer',
						'default' => '',
					),
					'naturalHeight'               =>
					array(
						'type'    => 'integer',
						'default' => '',
					),
					'linkTarget'                  =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'sizeSlug'                    =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'sizeSlugTablet'              =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'sizeSlugMobile'              =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'imageTopMargin'              =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-top-margin',
						),
					),
					'imageRightMargin'            =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-right-margin',
						),
					),
					'imageLeftMargin'             =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-left-margin',
						),
					),
					'imageBottomMargin'           =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-bottom-margin',
						),
					),
					'imageTopMarginTablet'        =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-top-margin-tablet',
						),
					),
					'imageRightMarginTablet'      =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-right-margin-tablet',
						),
					),
					'imageLeftMarginTablet'       =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-left-margin-tablet',
						),
					),
					'imageBottomMarginTablet'     =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-bottom-margin-tablet',
						),
					),
					'imageTopMarginMobile'        =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-top-margin-mobile',
						),
					),
					'imageRightMarginMobile'      =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-right-margin-mobile',
						),
					),
					'imageLeftMarginMobile'       =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-left-margin-mobile',
						),
					),
					'imageBottomMarginMobile'     =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-bottom-margin-mobile',
						),
					),
					'imageMarginUnit'             =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-margin-unit',
						),
					),
					'imageMarginUnitTablet'       =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-margin-unit-tablet',
						),
					),
					'imageMarginUnitMobile'       =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-margin-unit-mobile',
						),
					),
					'imageMarginLink'             =>
					array(
						'type'    => 'boolean',
						'default' => true,
					),
					'captionText'                 =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'captionShowOn'               =>
					array(
						'type'    => 'string',
						'default' => 'hover',
					),
					'captionLoadGoogleFonts'      =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'captionColor'                =>
					array(
						'type'         => 'string',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-color',
						),
					),
					'captionAlign'                =>
					array(
						'type'         => 'string',
						'default'      => 'center',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-align',
						),
					),
					'captionFontFamily'           =>
					array(
						'type'         => 'string',
						'default'      => 'Default',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-font-family',
						),
					),
					'captionFontWeight'           =>
					array(
						'type'         => 'string',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-font-weight',
						),
					),
					'captionFontStyle'            =>
					array(
						'type'         => 'string',
						'default'      => 'normal',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-font-style',
						),
					),
					'captionTransform'            =>
					array(
						'type'         => 'string',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-transform',
						),
					),
					'captionDecoration'           =>
					array(
						'type'         => 'string',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-decoration',
						),
					),
					'captionFontSizeType'         =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-font-size-type',
						),
					),
					'captionFontSizeTypeTablet'   =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-font-size-type-tablet',
						),
					),
					'captionFontSizeTypeMobile'   =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-font-size-type-mobile',
						),
					),
					'captionLineHeightType'       =>
					array(
						'type'         => 'string',
						'default'      => 'em',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-line-height-type',
						),
					),
					'captionFontSize'             =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-font-size',
						),
					),
					'captionFontSizeTablet'       =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-font-size-tablet',
						),
					),
					'captionFontSizeMobile'       =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-font-size-mobile',
						),
					),
					'captionLineHeight'           =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-line-height',
						),
					),
					'captionLineHeightTablet'     =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-line-height-tablet',
						),
					),
					'captionLineHeightMobile'     =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-line-height-mobile',
						),
					),
					'captionTopMargin'            =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-top-margin',
						),
					),
					'captionRightMargin'          =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-right-margin',
						),
					),
					'captionLeftMargin'           =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-left-margin',
						),
					),
					'captionBottomMargin'         =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-bottom-margin',
						),
					),
					'captionTopMarginTablet'      =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-top-margin-tablet',
						),
					),
					'captionRightMarginTablet'    =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-right-margin-tablet',
						),
					),
					'captionLeftMarginTablet'     =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-left-margin-tablet',
						),
					),
					'captionBottomMarginTablet'   =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-bottom-margin-tablet',
						),
					),
					'captionTopMarginMobile'      =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-top-margin-mobile',
						),
					),
					'captionRightMarginMobile'    =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-right-margin-mobile',
						),
					),
					'captionLeftMarginMobile'     =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-left-margin-mobile',
						),
					),
					'captionBottomMarginMobile'   =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-bottom-margin-mobile',
						),
					),
					'captionMarginUnit'           =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-margin-unit',
						),
					),
					'captionMarginUnitTablet'     =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-margin-unit-tablet',
						),
					),
					'captionMarginUnitMobile'     =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-margin-unit-mobile',
						),
					),
					'captionMarginLink'           =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'heading'                     =>
					array(
						'type'    => 'string',
						'default' => '',
					),
					'headingShowOn'               =>
					array(
						'type'    => 'string',
						'default' => 'always',
					),
					'headingTag'                  =>
					array(
						'type'    => 'string',
						'default' => 'h2',
					),
					'headingId'                   =>
					array(
						'type' => 'string',
					),
					'headingLoadGoogleFonts'      =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'headingColor'                =>
					array(
						'type'         => 'string',
						'default'      => '#fff',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-color',
						),
					),
					'headingFontFamily'           =>
					array(
						'type'         => 'string',
						'default'      => 'Default',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-font-family',
						),
					),
					'headingFontWeight'           =>
					array(
						'type'         => 'string',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-font-weight',
						),
					),
					'headingFontStyle'            =>
					array(
						'type'         => 'string',
						'default'      => 'normal',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-font-style',
						),
					),
					'headingTransform'            =>
					array(
						'type'         => 'string',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-transform',
						),
					),
					'headingDecoration'           =>
					array(
						'type'         => 'string',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-decoration',
						),
					),
					'headingFontSizeType'         =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-font-size-type',
						),
					),
					'headingFontSizeTypeTablet'   =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-font-size-type-tablet',
						),
					),
					'headingFontSizeTypeMobile'   =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-font-size-type-mobile',
						),
					),
					'headingLineHeightType'       =>
					array(
						'type'         => 'string',
						'default'      => 'em',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-line-height',
						),
					),
					'headingFontSize'             =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-font-size',
						),
					),
					'headingFontSizeTablet'       =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-font-size-tablet',
						),
					),
					'headingFontSizeMobile'       =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-font-size-mobile',
						),
					),
					'headingLineHeight'           =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-line-height',
						),
					),
					'headingLineHeightTablet'     =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-line-height-tablet',
						),
					),
					'headingLineHeightMobile'     =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-line-height-mobile',
						),
					),
					'headingTopMargin'            =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-top-margin',
						),
					),
					'headingRightMargin'          =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-right-margin',
						),
					),
					'headingLeftMargin'           =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-left-margin',
						),
					),
					'headingBottomMargin'         =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-bottom-margin',
						),
					),
					'headingTopMarginTablet'      =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-top-margin-tablet',
						),
					),
					'headingRightMarginTablet'    =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-right-margin-tablet',
						),
					),
					'headingLeftMarginTablet'     =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-left-margin-tablet',
						),
					),
					'headingBottomMarginTablet'   =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-bottom-margin-tablet',
						),
					),
					'headingTopMarginMobile'      =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-top-margin-mobile',
						),
					),
					'headingRightMarginMobile'    =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-right-margin-mobile',
						),
					),
					'headingLeftMarginMobile'     =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-left-margin-mobile',
						),
					),
					'headingBottomMarginMobile'   =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-bottom-margin-mobile',
						),
					),
					'headingMarginUnit'           =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-margin-unit',
						),
					),
					'headingMarginUnitTablet'     =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-margin-unit-tablet',
						),
					),
					'headingMarginUnitMobile'     =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'main-title-margin-unit-mobile',
						),
					),
					'headingMarginLink'           =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'overlayPositionFromEdge'     =>
					array(
						'type'         => 'number',
						'default'      => 15,
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'overlay-position-from-edge',
						),
					),
					'overlayPositionFromEdgeUnit' =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'overlay-position-from-edge-unit',
						),
					),
					'overlayContentPosition'      =>
					array(
						'type'         => 'string',
						'default'      => 'center center',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'overlay-content-position',
						),
					),
					'overlayBackground'           =>
					array(
						'type'         => 'string',
						'default'      => '',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'overlay-background',
						),
					),
					'overlayOpacity'              =>
					array(
						'type'         => 'float',
						'default'      => 0.2,
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'overlay-opacity',
						),
					),
					'overlayHoverOpacity'         =>
					array(
						'type'         => 'number',
						'default'      => 1,
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'overlay-hover-opacity',
						),
					),
					'seperatorShowOn'             =>
					array(
						'type'         => 'string',
						'default'      => 'hover',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'seperator-show-on',
						),
					),
					'seperatorStyle'              =>
					array(
						'type'         => 'string',
						'default'      => 'none',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-style',
						),
					),
					'seperatorColor'              =>
					array(
						'type'         => 'string',
						'default'      => '#fff',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-color',
						),
					),
					'seperatorPosition'           =>
					array(
						'type'    => 'string',
						'default' => 'after_title',
					),
					'seperatorWidth'              =>
					array(
						'type'         => 'number',
						'default'      => 30,
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-width',
						),
					),
					'separatorWidthType'          =>
					array(
						'type'         => 'string',
						'default'      => '%',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-width-type',
						),
					),
					'seperatorThickness'          =>
					array(
						'type'         => 'number',
						'default'      => 2,
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-thickness',
						),
					),
					'seperatorThicknessUnit'      =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-width-unit',
						),
					),
					'seperatorTopMargin'          =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-top-margin',
						),
					),
					'seperatorRightMargin'        =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-right-margin',
						),
					),
					'seperatorLeftMargin'         =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-left-margin',
						),
					),
					'seperatorBottomMargin'       =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-bottom-margin',
						),
					),
					'seperatorTopMarginTablet'    =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-top-margin-tablet',
						),
					),
					'seperatorRightMarginTablet'  =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-right-margin-tablet',
						),
					),
					'seperatorLeftMarginTablet'   =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-left-margin-tablet',
						),
					),
					'seperatorBottomMarginTablet' =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-bottom-margin-tablet',
						),
					),
					'seperatorTopMarginMobile'    =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-top-margin-mobile',
						),
					),
					'seperatorRightMarginMobile'  =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-right-margin-mobile',
						),
					),
					'seperatorLeftMarginMobile'   =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-left-margin-mobile',
						),
					),
					'seperatorBottomMarginMobile' =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-bottom-margin-mobile',
						),
					),
					'seperatorMarginUnit'         =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-margin-unit',
						),
					),
					'seperatorMarginUnitTablet'   =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-margin-unit-tablet',
						),
					),
					'seperatorMarginUnitMobile'   =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'separator-margin-unit-mobile',
						),
					),
					'seperatorMarginLink'         =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'imageHoverEffect'            =>
					array(
						'type'    => 'string',
						'default' => 'static',
					),
					'objectFit'                   =>
					array(
						'type' => 'string',
					),
					'objectFitTablet'             =>
					array(
						'type' => 'string',
					),
					'objectFitMobile'             =>
					array(
						'type' => 'string',
					),
					'useSeparateBoxShadows'       =>
					array(
						'type'    => 'boolean',
						'default' => true,
					),
					'imageBoxShadowColor'         =>
					array(
						'type'         => 'string',
						'default'      => '#00000070',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-shadow-color',
						),
					),
					'imageBoxShadowHOffset'       =>
					array(
						'type'         => 'number',
						'default'      => 0,
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-shadow-h-offset',
						),
					),
					'imageBoxShadowVOffset'       =>
					array(
						'type'         => 'number',
						'default'      => 0,
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-shadow-v-offset',
						),
					),
					'imageBoxShadowBlur'          =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-shadow-blur',
						),
					),
					'imageBoxShadowSpread'        =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-shadow-spread',
						),
					),
					'imageBoxShadowPosition'      =>
					array(
						'type'         => 'string',
						'default'      => 'outset',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-shadow-position',
						),
					),
					'imageBoxShadowColorHover'    =>
					array(
						'type'         => 'string',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-shadow-color-hover',
						),
					),
					'imageBoxShadowHOffsetHover'  =>
					array(
						'type'         => 'number',
						'default'      => 0,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-shadow-h-offset-hover',
						),
					),
					'imageBoxShadowVOffsetHover'  =>
					array(
						'type'         => 'number',
						'default'      => 0,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-shadow-v-offset-hover',
						),
					),
					'imageBoxShadowBlurHover'     =>
					array(
						'type'         => 'number',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-shadow-blur-hover',
						),
					),
					'imageBoxShadowSpreadHover'   =>
					array(
						'type'         => 'number',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-shadow-spread-hover',
						),
					),
					'imageBoxShadowPositionHover' =>
					array(
						'type'         => 'string',
						'default'      => 'outset',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'image-shadow-position-hover',
						),
					),
					'maskShape'                   =>
					array(
						'type'    => 'string',
						'default' => 'none',
					),
					'maskCustomShape'             =>
					array(
						'type'    => 'object',
						'default' =>
						array(
							'url' => '',
							'alt' => 'mask shape',
						),
					),
					'maskSize'                    =>
					array(
						'type'    => 'string',
						'default' => 'auto',
					),
					'maskPosition'                =>
					array(
						'type'    => 'string',
						'default' => 'center center',
					),
					'maskRepeat'                  =>
					array(
						'type'    => 'string',
						'default' => 'no-repeat',
					),
					'headingLetterSpacing'        =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-letter-spacing',
						),
					),
					'headingLetterSpacingTablet'  =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-letter-spacing-tablet',
						),
					),
					'headingLetterSpacingMobile'  =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-letter-spacing-mobile',
						),
					),
					'headingLetterSpacingType'    =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-letter-spacing-type',
						),
					),
					'captionLetterSpacing'        =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-letter-spacing',
						),
					),
					'captionLetterSpacingTablet'  =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-letter-spacing-tablet',
						),
					),
					'captionLetterSpacingMobile'  =>
					array(
						'type'         => 'number',
						'isGBSStyle'   => true,
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-letter-spacing-mobile',
						),
					),
					'captionLetterSpacingType'    =>
					array(
						'type'         => 'string',
						'default'      => 'px',
						'UAGCopyPaste' =>
						array(
							'styleType' => 'desc-letter-spacing-type',
						),
					),
					'customHeightSetDesktop'      =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'customHeightSetTablet'       =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
					'customHeightSetMobile'       =>
					array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				$image_border_attributes,
				$overlay_border_attributes
			);

			// $field_border_attr = Cartflows_Gb_Helper::get_instance()->generate_php_border_attribute( 'field' );
			// $btn_border_attr = Cartflows_Gb_Helper::get_instance()->generate_php_border_attribute( 'button' );

			// $attr = array_merge( $field_border_attr, $btn_border_attr, $attr );

			$attributes = apply_filters( 'cartflows_gutenberg_cf_attributes_filters', $attr );

			register_block_type(
				'sureforms/image',
				array(
					'attributes'      => $attributes,
					'render_callback' => array( $this, 'render_html' ),
				)
			);
		}

		/**
		 * Render CF HTML.
		 *
		 * @param array $attributes Array of block attributes.
		 *
		 * @since 0.0.1
		 */
		public function render_html( $attributes ) {

			$layout                   = isset( $attributes['layout'] ) && $attributes['layout'] ? $attributes['layout'] : '';
			$url                      = isset( $attributes['url'] ) && $attributes['url'] ? $attributes['url'] : '';
			$url_tablet               = isset( $attributes['urlTablet'] ) && $attributes['urlTablet'] ? $attributes['urlTablet'] : '';
			$url_mobile               = isset( $attributes['urlMobile'] ) && $attributes['urlMobile'] ? $attributes['urlMobile'] : '';
			$alt                      = isset( $attributes['alt'] ) && $attributes['alt'] ? $attributes['alt'] : '';
			$caption                  = isset( $attributes['caption'] ) && $attributes['caption'] ? $attributes['caption'] : '';
			$enable_caption           = isset( $attributes['enableCaption'] ) && $attributes['enableCaption'] ? $attributes['enableCaption'] : '';
			$image_hover_effect       = isset( $attributes['imageHoverEffect'] ) && $attributes['imageHoverEffect'] ? $attributes['imageHoverEffect'] : '';
			$href                     = isset( $attributes['href'] ) && $attributes['href'] ? $attributes['href'] : '';
			$link_class               = isset( $attributes['linkClass'] ) && $attributes['linkClass'] ? $attributes['linkClass'] : '';
			$width                    = isset( $attributes['width'] ) && $attributes['width'] ? $attributes['width'] : '';
			$height                   = isset( $attributes['height'] ) && $attributes['height'] ? $attributes['height'] : '';
			$natural_height           = isset( $attributes['naturalHeight'] ) && $attributes['naturalHeight'] ? $attributes['naturalHeight'] : '';
			$natural_width            = isset( $attributes['naturalWidth'] ) && $attributes['naturalWidth'] ? $attributes['naturalWidth'] : '';
			$align                    = isset( $attributes['align'] ) && $attributes['align'] ? $attributes['align'] : '';
			$id                       = isset( $attributes['id'] ) && $attributes['id'] ? $attributes['id'] : '';
			$link_target              = isset( $attributes['linkTarget'] ) && $attributes['linkTarget'] ? $attributes['linkTarget'] : '';
			$rel                      = isset( $attributes['rel'] ) && $attributes['rel'] ? $attributes['rel'] : '';
			$title                    = isset( $attributes['title'] ) && $attributes['title'] ? $attributes['title'] : '';
			$heading                  = isset( $attributes['heading'] ) && $attributes['heading'] ? $attributes['heading'] : '';
			$heading_tag              = isset( $attributes['headingTag'] ) && $attributes['headingTag'] ? $attributes['headingTag'] : '';
			$heading_id               = isset( $attributes['headingId'] ) && $attributes['headingId'] ? $attributes['headingId'] : '';
			$overlay_content_position = isset( $attributes['overlayContentPosition'] ) && $attributes['overlayContentPosition'] ? $attributes['overlayContentPosition'] : '';
			$seperator_style          = isset( $attributes['seperatorStyle'] ) && $attributes['seperatorStyle'] ? $attributes['seperatorStyle'] : '';
			$seperator_position       = isset( $attributes['seperatorPosition'] ) && $attributes['seperatorPosition'] ? $attributes['seperatorPosition'] : '';

			$url_tablet ? ', ' . $url_tablet . ' 780w' : '';
			$url_mobile ? ', ' . $url_mobile . ' 360w' : '';
			$src_set = $url . '' . $url_tablet . '' . $url_mobile;

			$image = $url && '' !== $url ? "<img srcset='" . $src_set . "' sizes='(max-width: 480px) 150px' src='" . $url . "' alt='" . $alt . "'/>" : '';

			$get_rel = $rel ? trim( $rel ) : 'noopener';

			$figure_image = $href && '' !== $href ? "<a class='" . $link_class . "' href='" . $href . "' target='" . $link_target . "' rel='" . $get_rel . "'>" . $image . '</a>' : $image;

			$custom_image_heading = '<' . $heading_tag . ' id="' . $heading_id . '" class="uagb-image-heading" >' . $heading . '</' . $heading_tag . '>';

			$custom_image_caption = '<figcaption class="uagb-image-caption" >' . $caption . '</figcaption>';

			$separator = 'none' !== $seperator_style ? '<div class="uagb-image-separator"></div>' : '';

			$image_overlay_link = "<a class='wp-block-uagb-image--layout-overlay-link " . $link_class . "' href='" . $href . "' target='" . $link_target . "' rel='" . $get_rel . "'></a>";

			$main_classes = array(
				'wp-block-uagb-image',
				'wp-block-uagb-image--layout-' . $layout,
				'wp-block-uagb-image--effect-' . $image_hover_effect,
				'wp-block-uagb-image--align-' . $align ? $align : 'none',
				'cf-block-' . $attributes['block_id'],
			);

			if ( isset( $attributes['className'] ) ) {
				$main_classes[] = $attributes['className'];
			}

			ob_start();
			?>
				<div class="<?php echo esc_attr( implode( ' ', $main_classes ) ); ?>">
					<?php echo wp_kses_post( $figure_image ); ?>
					<?php
					if ( 'overlay' === $layout ) {
						?>
						<div class="wp-block-uagb-image--layout-overlay__color-wrapper"></div>	
						<div class="wp-block-uagb-image--layout-overlay__inner <?php $overlay_content_position = str_replace( ' ', '-', $overlay_content_position ); ?>">
						<?php
						$image_overlay_link;
						'before_title' === $seperator_position ? $separator : '';
						$custom_image_heading;
						'after_title' === $seperator_position ? $separator : '';
						$custom_image_caption;
						'after_sub_title' === $seperator_position ? $separator : '';
						?>
						</div>
						<?php
					} else {
						$enable_caption ? $custom_image_caption : '';
					}
					?>
				</div>
			<?php
			return ob_get_clean();
		}
	}

	/**
	 *  Prepare if class 'WCFB_Checkout_Form' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	WCFB_Image::get_instance();
}
