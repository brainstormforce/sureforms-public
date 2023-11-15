<?php
/**
 * WCFB - Checkout Form Styler.
 *
 * @package WCFB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCFB_Separator' ) ) {

	/**
	 * Class WCFB_Separator.
	 */
	class WCFB_Separator {

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

			$attr = array(
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
			);

			// $field_border_attr = Cartflows_Gb_Helper::get_instance()->generate_php_border_attribute( 'field' );
			// $btn_border_attr = Cartflows_Gb_Helper::get_instance()->generate_php_border_attribute( 'button' );

			// $attr = array_merge( $field_border_attr, $btn_border_attr, $attr );

			$attributes = apply_filters( 'cartflows_gutenberg_cf_attributes_filters', $attr );

			register_block_type(
				'sureforms/separator',
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
		 * @since 1.6.15
		 */
		public function render_html( $attributes ) {

			$custom_svg         = array(
				'rectangles'    => 'url("data:image/svg+xml,%3Csvg width=\'16\' height=\'16\' viewBox=\'0 0 16 16\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M6.4 0H16L9.6 16H0L6.4 0Z\' fill=\'black\'/%3E%3C/svg%3E")',
				'parallelogram' => 'url("data:image/svg+xml,%3Csvg width=\'8\' height=\'16\' viewBox=\'0 0 8 16\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Crect width=\'8\' height=\'16\' fill=\'black\'/%3E%3C/svg%3E")',
				'slash'         => 'url("data:image/svg+xml,%3Csvg width=\'16\' height=\'16\' viewBox=\'0 0 16 16\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M6.29312 16.9999L17 6.29302M14.2931 16.9999L17 14.293M-0.707031 15.9999L16.0002 -0.707153M8.00017 -0.707153L-0.706882 7.9999\' stroke=\'black\'/%3E%3C/svg%3E")',
				'leaves'        => 'url("data:image/svg+xml,%3Csvg width=\'16\' height=\'16\' viewBox=\'0 0 16 16\' fill=\'none\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg clip-path=\'url(%23clip0_2356_5631)\'%3E%3Cpath d=\'M15 1C10.5 1 9 2.5 9 7C13.5 7 15 5.5 15 1Z\' stroke=\'black\'/%3E%3Cpath d=\'M1 1C5.5 1 7 2.5 7 7C2.5 7 1 5.5 1 1Z\' stroke=\'black\'/%3E%3Cpath d=\'M15 15C10.5 15 9 13.5 9 9C13.5 9 15 10.5 15 15Z\' stroke=\'black\'/%3E%3Cpath d=\'M1 15C5.5 15 7 13.5 7 9C2.5 9 1 10.5 1 15Z\' stroke=\'black\'/%3E%3C/g%3E%3Cdefs%3E%3CclipPath id=\'clip0_2356_5631\'%3E%3Crect width=\'16\' height=\'16\' fill=\'white\'/%3E%3C/clipPath%3E%3C/defs%3E%3C/svg%3E")',
			);
			$separator_icon     = isset( $attributes['separatorIcon'] ) ? $attributes['separatorIcon'] : '';
			$separator_style    = isset( $attributes['separatorStyle'] ) ? $attributes['separatorStyle'] : '';
			$element_type       = isset( $attributes['elementType'] ) ? $attributes['elementType'] : '';
			$element_type_css   = $element_type ? 'wp-block-uagb-separator--' . $attributes['elementType'] : '';
			$separator_text_tag = isset( $attributes['separatorTextTag'] ) ? $attributes['separatorTextTag'] : '';
			$main_classes       = array(
				'wp-block-uagb-separator',
				'cf-block-' . $attributes['block_id'],
				$element_type_css,
			);

			if ( isset( $attributes['className'] ) ) {
				$main_classes[] = $attributes['className'];
			}

			$custom_tag = '<' . $separator_text_tag . ' class="uagb-html-tag">' . $attributes['separatorText'] . '</' . $separator_text_tag . '>';

			ob_start();
			?>
			<div class = "<?php echo esc_attr( implode( ' ', $main_classes ) ); ?>" style="--my-background-image: <?php echo esc_attr( $custom_svg[ $separator_style ] ); ?>;" >
				<div class="wp-block-uagb-separator__inner">
					<div class="wp-block-uagb-separator-element">
						<?php
						if ( 'icon' === $element_type ) {
							Cartflows_Gb_Helper::render_svg_html( $attributes['separatorIcon'] );
						} else {
							echo wp_kses_post( $custom_tag );
						}
						?>
					</div>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}
	}

	/**
	 *  Prepare if class 'WCFB_Checkout_Form' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	WCFB_Separator::get_instance();
}
