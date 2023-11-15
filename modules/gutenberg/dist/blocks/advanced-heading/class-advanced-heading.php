<?php
/**
 * WCFB - Checkout Form Styler.
 *
 * @package WCFB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCFB_Advanced_Heading' ) ) {

	/**
	 * Class WCFB_Advanced_Heading.
	 */
	class WCFB_Advanced_Heading {

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
				'block_id'                     =>
				array(
					'type' => 'string',
				),
				'classMigrate'                 =>
				array(
					'type'    => 'boolean',
					'default' => false,
				),
				'blockBackgroundType'          =>
				array(
					'type'         => 'string',
					'default'      => 'classic',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-blackground-type',
					),
				),
				'blockGradientBackground'      =>
				array(
					'type'         => 'string',
					'default'      => 'linear-gradient(90deg, rgb(6, 147,_ 227) 0%, rgb(155, 81, 224) 100%)',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-gradient-blackground',
					),
				),
				'blockBackground'              =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-blackground',
					),
				),
				'headingTitleToggle'           =>
				array(
					'type'    => 'boolean',
					'default' => true,
				),
				'headingTitle'                 =>
				array(
					'source'   => 'html',
					'selector' => '.uagb-heading-text',
					'default'  => __( 'Your Attractive Heading', 'sureforms' ),
				),
				'headingId'                    =>
				array(
					'type' => 'string',
				),
				'headingDescToggle'            =>
				array(
					'type'    => 'boolean',
					'default' => false,
				),
				'headingDescPosition'          =>
				array(
					'type'         => 'string',
					'default'      => 'below-heading',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'prefix-position',
					),
				),
				'headingDesc'                  =>
				array(
					'source'   => 'html',
					'selector' => '.uagb-desc-text',
				),
				'headingAlign'                 =>
				array(
					'type'         => 'string',
					'default'      => 'left',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'overall-alignment',
					),
				),
				'headingAlignTablet'           =>
				array(
					'type'         => 'string',
					'default'      => '',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'overall-alignment-tablet',
					),
				),
				'headingAlignMobile'           =>
				array(
					'type'         => 'string',
					'default'      => '',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'overall-alignment-mobile',
					),
				),
				'headingColorType'             =>
				array(
					'type'         => 'string',
					'default'      => 'classic',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-color-type',
					),
				),
				'headingColor'                 =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-color',
					),
				),
				'headingGradientColor'         =>
				array(
					'type'         => 'string',
					'default'      => 'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-gradient-color',
					),
				),
				'subHeadingColor'              =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-color',
					),
				),
				'separatorColor'               =>
				array(
					'type'         => 'string',
					'default'      => '#0170b9',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-color',
					),
				),
				'headingTag'                   =>
				array(
					'type'    => 'string',
					'default' => 'h2',
				),
				'level'                        =>
				array(
					'type'    => 'number',
					'default' => 2,
				),
				'seperatorStyle'               =>
				array(
					'type'         => 'string',
					'default'      => 'none',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-style',
					),
				),
				'seperatorPosition'            =>
				array(
					'type'         => 'string',
					'default'      => 'below-heading',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-position',
					),
				),
				'separatorHeight'              =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-thickness',
					),
					'default'      => 2,
				),
				'separatorHeightType'          =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-height-type',
					),
				),
				'separatorWidth'               =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-width',
					),
					'default'      => 12,
				),
				'separatorWidthTablet'         =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-width',
					),
				),
				'separatorWidthMobile'         =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-width',
					),
				),
				'separatorWidthType'           =>
				array(
					'type'         => 'string',
					'default'      => '%',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-width-type',
					),
				),
				'headSpace'                    =>
				array(
					'type'         => 'number',
					'default'      => 15,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-bottom-margin',
					),
				),
				'headSpaceMobile'              =>
				array(
					'type'         => 'number',
					'default'      => '',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-bottom-margin-tablet',
					),
				),
				'headSpaceTablet'              =>
				array(
					'type'         => 'number',
					'default'      => '',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-bottom-margin-mobile',
					),
				),
				'headSpaceType'                =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-unit-type',
					),
				),
				'subHeadSpace'                 =>
				array(
					'type'         => 'number',
					'default'      => 15,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'sub-title-bottom-margin',
					),
				),
				'subHeadSpaceMobile'           =>
				array(
					'type'         => 'number',
					'default'      => '',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'sub-title-bottom-margin-tablet',
					),
				),
				'subHeadSpaceTablet'           =>
				array(
					'type'         => 'number',
					'default'      => '',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'sub-title-bottom-margin-mobile',
					),
				),
				'subHeadSpaceType'             =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'sub-title-unit-type',
					),
				),
				'headFontFamily'               =>
				array(
					'type'         => 'string',
					'default'      => 'Default',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-family',
					),
				),
				'headFontWeight'               =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-weight',
					),
				),
				'headFontStyle'                =>
				array(
					'type'         => 'string',
					'default'      => 'normal',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-style',
					),
				),
				'headTransform'                =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-transform',
					),
				),
				'headDecoration'               =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-decoration',
					),
				),
				'headFontSizeType'             =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-size-type',
					),
				),
				'headFontSizeTypeMobile'       =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-size-type-mobile',
					),
				),
				'headFontSizeTypeTablet'       =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-size-type-tablet',
					),
				),
				'headLineHeightType'           =>
				array(
					'type'         => 'string',
					'default'      => 'em',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-line-height-type',
					),
				),
				'headFontSize'                 =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-size',
					),
				),
				'headFontSizeTablet'           =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-size-tablet',
					),
				),
				'headFontSizeMobile'           =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-size-mobile',
					),
				),
				'headLineHeight'               =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-line-height',
					),
				),
				'headLineHeightTablet'         =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-line-height-tablet',
					),
				),
				'headLineHeightMobile'         =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-line-height-mobile',
					),
				),
				'headLetterSpacing'            =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-letter-spacing',
					),
				),
				'headLetterSpacingTablet'      =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-letter-spacing-tablet',
					),
				),
				'headLetterSpacingMobile'      =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-letter-spacing-mobile',
					),
				),
				'headLetterSpacingType'        =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-letter-spacing-type',
					),
				),
				'headShadowColor'              =>
				array(
					'type'    => 'string',
					'default' => '',
				),
				'headShadowHOffset'            =>
				array(
					'type'    => 'number',
					'default' => 0,
				),
				'headShadowVOffset'            =>
				array(
					'type'    => 'number',
					'default' => 0,
				),
				'headShadowBlur'               =>
				array(
					'type'    => 'number',
					'default' => 10,
				),
				'subHeadFontFamily'            =>
				array(
					'type'         => 'string',
					'default'      => 'Default',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-font-family',
					),
				),
				'subHeadFontWeight'            =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-font-weight',
					),
				),
				'subHeadFontStyle'             =>
				array(
					'type'         => 'string',
					'default'      => 'normal',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-font-style',
					),
				),
				'subHeadTransform'             =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-transform',
					),
				),
				'subHeadDecoration'            =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-decoration',
					),
				),
				'subHeadFontSize'              =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-font-size',
					),
				),
				'subHeadFontSizeType'          =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-font-size-type',
					),
				),
				'subHeadFontSizeTypeMobile'    =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-font-size-type-mobile',
					),
				),
				'subHeadFontSizeTypeTablet'    =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-font-size-type-tablet',
					),
				),
				'subHeadFontSizeTablet'        =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-font-size-tablet',
					),
				),
				'subHeadFontSizeMobile'        =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-font-size-mobile',
					),
				),
				'subHeadLineHeight'            =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-line-height',
					),
				),
				'subHeadLineHeightType'        =>
				array(
					'type'         => 'string',
					'default'      => 'em',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-line-height-type',
					),
				),
				'subHeadLineHeightTablet'      =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-line-height-tablet',
					),
				),
				'subHeadLineHeightMobile'      =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-line-height-mobile',
					),
				),
				'subHeadLetterSpacing'         =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-letter-spacing',
					),
				),
				'subHeadLetterSpacingTablet'   =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-letter-spacing-tablet',
					),
				),
				'subHeadLetterSpacingMobile'   =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-letter-spacing-mobile',
					),
				),
				'subHeadLetterSpacingType'     =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-letter-spacing-type',
					),
				),
				'separatorSpace'               =>
				array(
					'type'         => 'number',
					'default'      => 15,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-bottom-margin',
					),
				),
				'separatorSpaceTablet'         =>
				array(
					'type'         => 'number',
					'default'      => '',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-bottom-margin',
					),
				),
				'separatorSpaceMobile'         =>
				array(
					'type'         => 'number',
					'default'      => '',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-bottom-margin',
					),
				),
				'separatorSpaceType'           =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-width-type',
					),
				),
				'headLoadGoogleFonts'          =>
				array(
					'type'         => 'boolean',
					'default'      => false,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-load-google-fonts',
					),
				),
				'subHeadLoadGoogleFonts'       =>
				array(
					'type'         => 'boolean',
					'default'      => false,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'desc-load-google-fonts',
					),
				),
				'separatorHoverColor'          =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-hover-color',
					),
				),
				'isPreview'                    =>
				array(
					'type'    => 'boolean',
					'default' => false,
				),
				'blockTopPadding'              =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-top-padding',
					),
				),
				'blockRightPadding'            =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-right-padding',
					),
				),
				'blockLeftPadding'             =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-left-padding',
					),
				),
				'blockBottomPadding'           =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-bottom-padding',
					),
				),
				'blockTopPaddingTablet'        =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-top-padding-tablet',
					),
				),
				'blockRightPaddingTablet'      =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-right-padding-tablet',
					),
				),
				'blockLeftPaddingTablet'       =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-left-padding-tablet',
					),
				),
				'blockBottomPaddingTablet'     =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-bottom-padding-tablet',
					),
				),
				'blockTopPaddingMobile'        =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-top-padding-mobile',
					),
				),
				'blockRightPaddingMobile'      =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-right-padding-mobile',
					),
				),
				'blockLeftPaddingMobile'       =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-left-padding-mobile',
					),
				),
				'blockBottomPaddingMobile'     =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-bottom-padding-mobile',
					),
				),
				'blockPaddingUnit'             =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-padding-unit',
					),
				),
				'blockPaddingUnitTablet'       =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-padding-unit-tablet',
					),
				),
				'blockPaddingUnitMobile'       =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-padding-unit-mobile',
					),
				),
				'blockPaddingLink'             =>
				array(
					'type'    => 'boolean',
					'default' => false,
				),
				'blockTopMargin'               =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-top-margin',
					),
				),
				'blockRightMargin'             =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-right-margin',
					),
				),
				'blockLeftMargin'              =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-left-margin',
					),
				),
				'blockBottomMargin'            =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-bottom-margin',
					),
				),
				'blockTopMarginTablet'         =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-top-margin-tablet',
					),
				),
				'blockRightMarginTablet'       =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-right-margin-tablet',
					),
				),
				'blockLeftMarginTablet'        =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-left-margin-tablet',
					),
				),
				'blockBottomMarginTablet'      =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-bottom-margin-tablet',
					),
				),
				'blockTopMarginMobile'         =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-top-margin-mobile',
					),
				),
				'blockRightMarginMobile'       =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-right-margin-mobile',
					),
				),
				'blockLeftMarginMobile'        =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-left-margin-mobile',
					),
				),
				'blockBottomMarginMobile'      =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-bottom-margin-mobile',
					),
				),
				'blockMarginUnit'              =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-margin-unit',
					),
				),
				'blockMarginUnitTablet'        =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-margin-unit-tablet',
					),
				),
				'blockMarginUnitMobile'        =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'block-margin-unit-mobile',
					),
				),
				'blockMarginLink'              =>
				array(
					'type'    => 'boolean',
					'default' => false,
				),
				'linkColor'                    =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'link-color',
					),
				),
				'linkHColor'                   =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'link-hover-color',
					),
				),
				'highLightColor'               =>
				array(
					'type'         => 'string',
					'default'      => '#fff',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-color',
					),
				),
				'highLightBackground'          =>
				array(
					'type'         => 'string',
					'default'      => '#007cba',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-background',
					),
				),
				'highLightLoadGoogleFonts'     =>
				array(
					'type'         => 'boolean',
					'default'      => false,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-load-google-fonts',
					),
				),
				'highLightFontFamily'          =>
				array(
					'type'         => 'string',
					'default'      => 'Default',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-font-family',
					),
				),
				'highLightFontWeight'          =>
				array(
					'type'         => 'string',
					'default'      => 'Default',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-font-weight',
					),
				),
				'highLightFontStyle'           =>
				array(
					'type'         => 'string',
					'default'      => 'normal',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-font-style',
					),
				),
				'highLightTransform'           =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-font-transform',
					),
				),
				'highLightDecoration'          =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-font-decoration',
					),
				),
				'highLightFontSizeType'        =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-font-size-type',
					),
				),
				'highLightFontSizeTypeMobile'  =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-font-size-type-mobile',
					),
				),
				'highLightFontSizeTypeTablet'  =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-font-size-type-tablet',
					),
				),
				'highLightLineHeightType'      =>
				array(
					'type'         => 'string',
					'default'      => 'em',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-line-height-type',
					),
				),
				'highLightFontSize'            =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-font-size',
					),
				),
				'highLightFontSizeTablet'      =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-font-size-tablet',
					),
				),
				'highLightFontSizeMobile'      =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-font-size-mobile',
					),
				),
				'highLightLineHeight'          =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-line-height',
					),
				),
				'highLightLineHeightTablet'    =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-line-height-tablet',
					),
				),
				'highLightLineHeightMobile'    =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-line-height-mobile',
					),
				),
				'highLightLetterSpacing'       =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-letter-spacing',
					),
				),
				'highLightLetterSpacingTablet' =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-letter-spacing-tablet',
					),
				),
				'highLightLetterSpacingMobile' =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-letter-spacing-mobile',
					),
				),
				'highLightLetterSpacingType'   =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-letter-spacing-type',
					),
				),
				'highLightTopPadding'          =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-top-padding',
					),
				),
				'highLightRightPadding'        =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-right-padding',
					),
				),
				'highLightLeftPadding'         =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-left-padding',
					),
				),
				'highLightBottomPadding'       =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-bottom-padding',
					),
				),
				'highLightTopPaddingTablet'    =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-top-padding-tablet',
					),
				),
				'highLightRightPaddingTablet'  =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-right-padding-tablet',
					),
				),
				'highLightLeftPaddingTablet'   =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-left-padding-tablet',
					),
				),
				'highLightBottomPaddingTablet' =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-bottom-padding-tablet',
					),
				),
				'highLightTopPaddingMobile'    =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-top-padding-mobile',
					),
				),
				'highLightRightPaddingMobile'  =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-right-padding-mobile',
					),
				),
				'highLightLeftPaddingMobile'   =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-left-padding-mobile',
					),
				),
				'highLightBottomPaddingMobile' =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-bottom-padding-mobile',
					),
				),
				'highLightPaddingUnit'         =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-padding-unit',
					),
				),
				'highLightPaddingUnitTablet'   =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-padding-unit-tablet',
					),
				),
				'highLightPaddingUnitMobile'   =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-padding-unit-mobile',
					),
				),
				'highLightPaddingLink'         =>
				array(
					'type'         => 'boolean',
					'default'      => false,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'highlight-padding-link',
					),
				),
			)

			// $field_border_attr = Cartflows_Gb_Helper::get_instance()->generate_php_border_attribute( 'field' );
			// $btn_border_attr = Cartflows_Gb_Helper::get_instance()->generate_php_border_attribute( 'button' );

			// $attr = array_merge( $field_border_attr, $btn_border_attr, $attr );

			$attributes = apply_filters( 'cartflows_gutenberg_cf_attributes_filters', $attr );

			register_block_type(
				'sureforms/advanced-heading',
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
