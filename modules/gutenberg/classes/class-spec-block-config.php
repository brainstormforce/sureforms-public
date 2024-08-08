<?php
/**
 * Sureforms Config.
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Spec_Block_Config' ) ) {

	/**
	 * Class Spec_Block_Config.
	 */
	class Spec_Block_Config {

		/**
		 * Block Attributes
		 *
		 * @var block_attributes
		 */
		public static $block_attributes = null;

		/**
		 * Block Assets
		 *
		 * @since 0.0.1
		 * @var block_attributes
		 */
		public static $block_assets_css = null;

		/**
		 * Block Assets
		 *
		 * @var block_attributes
		 */
		public static $block_assets = null;

		/**
		 * Get Widget List.
		 *
		 * @since 0.0.1
		 *
		 * @return array The Widget List.
		 */
		public static function get_block_attributes() {

			$image_attribute             = self::generate_border_attribute( 'image' );
			$overlay_attribute           = self::generate_border_attribute( 'overlay' );
			$icon_border_attribute       = self::generate_border_attribute( 'icon' );
			$high_light_border_attribute = self::generate_border_attribute( 'highLight' );

			$image_base_attr = [
				'height'                      => '',
				'width'                       => '',
				'widthTablet'                 => '',
				'heightTablet'                => '',
				'widthMobile'                 => '',
				'heightMobile'                => '',
				'layout'                      => 'default',
				// image.
				'imageTopMargin'              => '',
				'imageRightMargin'            => '',
				'imageLeftMargin'             => '',
				'imageBottomMargin'           => '',
				'imageTopMarginTablet'        => '',
				'imageRightMarginTablet'      => '',
				'imageLeftMarginTablet'       => '',
				'imageBottomMarginTablet'     => '',
				'imageTopMarginMobile'        => '',
				'imageRightMarginMobile'      => '',
				'imageLeftMarginMobile'       => '',
				'imageBottomMarginMobile'     => '',
				'imageMarginUnit'             => 'px',
				'imageMarginUnitTablet'       => 'px',
				'imageMarginUnitMobile'       => 'px',
				'align'                       => '',
				'alignTablet'                 => '',
				'alignMobile'                 => '',
				// heading.
				'headingShowOn'               => 'always',
				'headingLoadGoogleFonts'      => false,
				'headingFontFamily'           => 'Default',
				'headingFontWeight'           => '',
				'headingFontStyle'            => 'normal',
				'headingTransform'            => '',
				'headingFontSize'             => '',
				'headingColor'                => '#fff',
				'headingTransform'            => '',
				'headingDecoration'           => '',
				'headingFontSizeType'         => 'px',
				'headingFontSizeTypeTablet'   => 'px',
				'headingFontSizeTypeMobile'   => 'px',
				'headingFontSizeMobile'       => '',
				'headingFontSizeTablet'       => '',
				'headingLineHeight'           => 'em',
				'headingLineHeightType'       => 'em',
				'headingLineHeightMobile'     => '',
				'headingLineHeightTablet'     => '',
				'headingTopMargin'            => '',
				'headingRightMargin'          => '',
				'headingLeftMargin'           => '',
				'headingBottomMargin'         => '',
				'headingTopMarginTablet'      => '',
				'headingRightMarginTablet'    => '',
				'headingLeftMarginTablet'     => '',
				'headingBottomMarginTablet'   => '',
				'headingTopMarginMobile'      => '',
				'headingRightMarginMobile'    => '',
				'headingLeftMarginMobile'     => '',
				'headingBottomMarginMobile'   => '',
				'headingMarginUnit'           => 'px',
				'headingMarginUnitTablet'     => 'px',
				'headingMarginUnitMobile'     => 'px',
				// overlay.
				'overlayOpacity'              => 0.2,
				'overlayHoverOpacity'         => 1,
				'overlayPositionFromEdge'     => 15,
				'overlayPositionFromEdgeUnit' => 'px',
				'overlayBackground'           => '',
				// caption.
				'captionShowOn'               => 'hover',
				'captionAlign'                => 'center',
				'captionColor'                => '',
				'captionLoadGoogleFonts'      => false,
				'captionFontFamily'           => '',
				'captionFontStyle'            => 'normal',
				'captionTransform'            => '',
				'captionDecoration'           => '',
				'captionFontSizeType'         => 'px',
				'captionFontSizeTypeTablet'   => 'px',
				'captionFontSizeTypeMobile'   => 'px',
				'captionLineHeightType'       => 'em',
				'captionFontSize'             => '',
				'captionFontWeight'           => '',
				'captionFontSizeTablet'       => '',
				'captionFontSizeMobile'       => '',
				'captionLineHeight'           => '',
				'captionLineHeightTablet'     => '',
				'captionLineHeightMobile'     => '',
				'captionTopMargin'            => '',
				'captionRightMargin'          => '',
				'captionLeftMargin'           => '',
				'captionBottomMargin'         => '',
				'captionTopMarginTablet'      => '',
				'captionRightMarginTablet'    => '',
				'captionLeftMarginTablet'     => '',
				'captionBottomMarginTablet'   => '',
				'captionTopMarginMobile'      => '',
				'captionRightMarginMobile'    => '',
				'captionLeftMarginMobile'     => '',
				'captionBottomMarginMobile'   => '',
				'captionMarginUnit'           => 'px',
				'captionMarginUnitTablet'     => 'px',
				'captionMarginUnitMobile'     => 'px',
				// separator.
				'separatorShowOn'             => 'hover',
				'separatorStyle'              => '',
				'separatorColor'              => '#fff',
				'separatorWidth'              => 30,
				'separatorWidthType'          => '%',
				'separatorThickness'          => 2,
				'separatorThicknessUnit'      => 'px',
				'separatorTopMargin'          => '',
				'separatorRightMargin'        => '',
				'separatorLeftMargin'         => '',
				'separatorBottomMargin'       => '',
				'separatorTopMarginTablet'    => '',
				'separatorRightMarginTablet'  => '',
				'separatorLeftMarginTablet'   => '',
				'separatorBottomMarginTablet' => '',
				'separatorTopMarginMobile'    => '',
				'separatorRightMarginMobile'  => '',
				'separatorLeftMarginMobile'   => '',
				'separatorBottomMarginMobile' => '',
				'separatorMarginUnit'         => 'px',
				'separatorMarginUnitTablet'   => 'px',
				'separatorMarginUnitMobile'   => 'px',
				// image shadow.
				'useSeparateBoxShadows'       => true,
				'imageBoxShadowColor'         => '#00000070',
				'imageBoxShadowHOffset'       => 0,
				'imageBoxShadowVOffset'       => 0,
				'imageBoxShadowBlur'          => '',
				'imageBoxShadowSpread'        => '',
				'imageBoxShadowPosition'      => 'outset',
				'imageBoxShadowColorHover'    => '',
				'imageBoxShadowHOffsetHover'  => 0,
				'imageBoxShadowVOffsetHover'  => 0,
				'imageBoxShadowBlurHover'     => '',
				'imageBoxShadowSpreadHover'   => '',
				'imageBoxShadowPositionHover' => 'outset',
				// mask.
				'maskShape'                   => 'none',
				'maskSize'                    => 'auto',
				'maskPosition'                => 'center center',
				'maskRepeat'                  => 'no-repeat',
				'objectFit'                   => '',
				'objectFitTablet'             => '',
				'objectFitMobile'             => '',
				'headingLetterSpacing'        => '',
				'headingLetterSpacingTablet'  => '',
				'headingLetterSpacingMobile'  => '',
				'headingLetterSpacingType'    => 'px',
				'captionLetterSpacing'        => '',
				'captionLetterSpacingTablet'  => '',
				'captionLetterSpacingMobile'  => '',
				'captionLetterSpacingType'    => 'px',
				'customHeightSetDesktop'      => false,
				'customHeightSetTablet'       => false,
				'customHeightSetMobile'       => false,
				// For Global Block Styles.
				'globalBlockStyleName'        => '',
				'globalBlockStyleId'          => '',
			];

			$icon_base_attr = [
				// Size.
				'icon'                             => '',
				'iconSize'                         => 40,
				'iconSizeTablet'                   => '',
				'iconSizeMobile'                   => '',
				'iconSizeUnit'                     => 'px',
				// Alignment.
				'align'                            => 'center',
				'alignTablet'                      => '',
				'alignMobile'                      => '',
				// Rotation.
				'rotation'                         => 0,
				'rotationUnit'                     => 'deg',
				// StyleSettings.
				'iconView'                         => 'none',
				'iconBorderWidth'                  => 3,
				// Color.
				'iconColor'                        => '#333',
				'iconBorderColor'                  => '',
				'iconBackgroundColorType'          => 'classic',
				'iconBackgroundColor'              => '',
				'iconBackgroundGradientColor'      => 'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
				'iconHoverColor'                   => '',
				'iconHoverBorderColor'             => '',
				'iconHoverBackgroundColorType'     => 'classic',
				'iconHoverBackgroundColor'         => '',
				'iconHoverBackgroundGradientColor' => 'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
				// Padding.
				'iconTopPadding'                   => 5,
				'iconRightPadding'                 => 5,
				'iconBottomPadding'                => 5,
				'iconLeftPadding'                  => 5,
				'iconTopTabletPadding'             => '',
				'iconRightTabletPadding'           => '',
				'iconBottomTabletPadding'          => '',
				'iconLeftTabletPadding'            => '',
				'iconTopMobilePadding'             => '',
				'iconRightMobilePadding'           => '',
				'iconBottomMobilePadding'          => '',
				'iconLeftMobilePadding'            => '',
				'iconPaddingUnit'                  => 'px',
				'iconMobilePaddingUnit'            => 'px',
				'iconTabletPaddingUnit'            => 'px',
				// Margin.
				'iconTopMargin'                    => '',
				'iconRightMargin'                  => '',
				'iconBottomMargin'                 => '',
				'iconLeftMargin'                   => '',
				'iconTopTabletMargin'              => '',
				'iconRightTabletMargin'            => '',
				'iconBottomTabletMargin'           => '',
				'iconLeftTabletMargin'             => '',
				'iconTopMobileMargin'              => '',
				'iconRightMobileMargin'            => '',
				'iconBottomMobileMargin'           => '',
				'iconLeftMobileMargin'             => '',
				'iconMarginUnit'                   => 'px',
				'iconMobileMarginUnit'             => 'px',
				'iconTabletMarginUnit'             => 'px',
				// Shadow.
				'iconShadowColor'                  => '#00000070',
				'iconShadowHOffset'                => 0,
				'iconShadowVOffset'                => 0,
				'iconShadowBlur'                   => 0,
				// Box Shadow.
				'useSeparateBoxShadows'            => true,
				'iconBoxShadowColor'               => '#00000070',
				'iconBoxShadowHOffset'             => 0,
				'iconBoxShadowVOffset'             => 0,
				'iconBoxShadowBlur'                => '',
				'iconBoxShadowSpread'              => '',
				'iconBoxShadowPosition'            => '',
				'iconBoxShadowColorHover'          => '',
				'iconBoxShadowHOffsetHover'        => 0,
				'iconBoxShadowVOffsetHover'        => 0,
				'iconBoxShadowBlurHover'           => '',
				'iconBoxShadowSpreadHover'         => '',
				'iconBoxShadowPositionHover'       => '',
				// Border.
				'iconBorderStyle'                  => 'default',
				// For Global Block Styles.
				'globalBlockStyleName'             => '',
				'globalBlockStyleId'               => '',
			];

			$heading_base_attr = [
				'classMigrate'                 => false,
				'blockBackground'              => '',
				'blockBackgroundType'          => 'classic',
				'blockGradientBackground'      => 'linear-gradient(90deg, rgb(6, 147, 227) 0%, rgb(155, 81, 224) 100%)',
				'headingAlign'                 => '',
				'headingAlignTablet'           => '',
				'headingDescPosition'          => 'below-heading',
				'separatorPosition'            => 'below-heading',
				'headingAlignMobile'           => '',
				'headingColor'                 => '#000',
				'headingColorType'             => 'classic',
				'headingGradientColor'         => 'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
				'subHeadingColor'              => '#000',
				'separatorHeightType'          => 'px',
				'separatorSpaceType'           => 'px',
				'separatorColor'               => '#0170b9',
				'separatorStyle'               => 'none',
				'separatorHeight'              => 2,
				'separatorWidth'               => 12,
				'separatorWidthTablet'         => '',
				'separatorWidthMobile'         => '',
				'separatorWidthType'           => '%',
				'headFontFamily'               => '',
				'headLoadGoogleFonts'          => false,
				'headFontWeight'               => '',
				'headFontStyle'                => '',
				'headFontSize'                 => '',
				'headFontSizeType'             => 'px',
				'headFontSizeTypeTablet'       => 'px',
				'headFontSizeTypeMobile'       => 'px',
				'headFontSizeTablet'           => '',
				'headFontSizeMobile'           => '',
				'headSpaceType'                => 'px',
				'headLineHeight'               => '',
				'headLineHeightType'           => 'em',
				'headLineHeightTablet'         => '',
				'headLineHeightMobile'         => '',
				'headLetterSpacing'            => '',
				'headLetterSpacingTablet'      => '',
				'headLetterSpacingMobile'      => '',
				'headLetterSpacingType'        => 'px',
				'headShadowColor'              => '',
				'headShadowHOffset'            => 0,
				'headShadowVOffset'            => 0,
				'headShadowBlur'               => 10,
				'subHeadFontFamily'            => '',
				'subHeadLoadGoogleFonts'       => false,
				'subHeadFontWeight'            => '',
				'subHeadFontStyle'             => '',
				'subHeadFontSize'              => '',
				'subHeadFontSizeType'          => 'px',
				'subHeadFontSizeTypeTablet'    => 'px',
				'subHeadFontSizeTypeMobile'    => 'px',
				'subHeadFontSizeTablet'        => '',
				'subHeadFontSizeMobile'        => '',
				'subHeadLineHeight'            => '',
				'subHeadLineHeightType'        => 'em',
				'subHeadLineHeightTablet'      => '',
				'subHeadLineHeightMobile'      => '',
				'subHeadLetterSpacing'         => '',
				'subHeadLetterSpacingTablet'   => '',
				'subHeadLetterSpacingMobile'   => '',
				'subHeadLetterSpacingType'     => 'px',
				'headSpace'                    => 15,
				'headSpaceTablet'              => '',
				'headSpaceMobile'              => '',
				'separatorSpace'               => 15,
				'separatorSpaceTablet'         => '',
				'separatorSpaceMobile'         => '',
				'separatorHoverColor'          => '',
				'headTransform'                => '',
				'headDecoration'               => '',
				'subHeadTransform'             => '',
				'subHeadDecoration'            => '',
				// padding.
				'blockTopPadding'              => '',
				'blockRightPadding'            => '',
				'blockLeftPadding'             => '',
				'blockBottomPadding'           => '',
				'blockTopPaddingTablet'        => '',
				'blockRightPaddingTablet'      => '',
				'blockLeftPaddingTablet'       => '',
				'blockBottomPaddingTablet'     => '',
				'blockTopPaddingMobile'        => '',
				'blockRightPaddingMobile'      => '',
				'blockLeftPaddingMobile'       => '',
				'blockBottomPaddingMobile'     => '',
				'blockPaddingUnit'             => 'px',
				'blockPaddingUnitTablet'       => 'px',
				'blockPaddingUnitMobile'       => 'px',
				'blockPaddingLink'             => '',
				// margin.
				'blockTopMargin'               => '',
				'blockRightMargin'             => '',
				'blockLeftMargin'              => '',
				'blockBottomMargin'            => '',
				'blockTopMarginTablet'         => '',
				'blockRightMarginTablet'       => '',
				'blockLeftMarginTablet'        => '',
				'blockBottomMarginTablet'      => '',
				'blockTopMarginMobile'         => '',
				'blockRightMarginMobile'       => '',
				'blockLeftMarginMobile'        => '',
				'blockBottomMarginMobile'      => '',
				'blockMarginUnit'              => 'px',
				'blockMarginUnitTablet'        => 'px',
				'blockMarginUnitMobile'        => 'px',
				'blockMarginLink'              => '',
				// link.
				'linkColor'                    => '',
				'linkHColor'                   => '',
				// Highlight.
				'highLightColor'               => '#fff',
				'highLightBackground'          => '#007cba',
				'highLightLoadGoogleFonts'     => false,
				'highLightFontFamily'          => 'Default',
				'highLightFontWeight'          => 'Default',
				'highLightFontStyle'           => 'normal',
				'highLightTransform'           => '',
				'highLightDecoration'          => '',
				'highLightFontSizeType'        => 'px',
				'highLightFontSizeTypeTablet'  => 'px',
				'highLightFontSizeTypeMobile'  => 'px',
				'highLightLineHeightType'      => 'em',
				'highLightFontSize'            => '',
				'highLightFontSizeTablet'      => '',
				'highLightFontSizeMobile'      => '',
				'highLightLineHeight'          => '',
				'highLightLineHeightTablet'    => '',
				'highLightLineHeightMobile'    => '',
				'highLightLetterSpacing'       => '',
				'highLightLetterSpacingTablet' => '',
				'highLightLetterSpacingMobile' => '',
				'highLightLetterSpacingType'   => 'px',
				'highLightTopPadding'          => '',
				'highLightRightPadding'        => '',
				'highLightLeftPadding'         => '',
				'highLightBottomPadding'       => '',
				'highLightTopPaddingTablet'    => '',
				'highLightRightPaddingTablet'  => '',
				'highLightLeftPaddingTablet'   => '',
				'highLightBottomPaddingTablet' => '',
				'highLightTopPaddingMobile'    => '',
				'highLightRightPaddingMobile'  => '',
				'highLightLeftPaddingMobile'   => '',
				'highLightBottomPaddingMobile' => '',
				'highLightPaddingUnit'         => 'px',
				'highLightPaddingUnitTablet'   => 'px',
				'highLightPaddingUnitMobile'   => 'px',
				'highLightPaddingLink'         => '',
				'subHeadSpace'                 => 15,
				'subHeadSpaceMobile'           => '',
				'subHeadSpaceTablet'           => '',
				'subHeadSpaceType'             => 'px',
				'headingDescToggle'            => false,
				// For Global Block Styles.
				'globalBlockStyleName'         => '',
				'globalBlockStyleId'           => '',
			];

			if ( null === self::$block_attributes ) {
				self::$block_attributes = [
					'srfm/separator'        => [
						'slug'        => '',
						'title'       => __( 'Separator', 'sureforms' ),
						'description' => '',
						'default'     => true,
						'attributes'  => [
							'separatorAlign'               => 'center',
							'separatorAlignTablet'         => 'center',
							'separatorAlignMobile'         => 'center',
							'separatorStyle'               => 'solid',
							'separatorBorderHeight'        => 1,
							'separatorBorderHeightMobile'  => 1,
							'separatorBorderHeightTablet'  => 1,
							'separatorBorderHeightUnit'    => 'px',
							'separatorWidth'               => 100,
							'separatorWidthTablet'         => 100,
							'separatorWidthMobile'         => 100,
							'separatorWidthType'           => '%',
							'separatorSize'                => 5,
							'separatorSizeTablet'          => 5,
							'separatorSizeMobile'          => 5,
							'separatorSizeType'            => 'px',
							'separatorHeight'              => 10,
							'separatorHeightMobile'        => 10,
							'separatorHeightTablet'        => 10,
							'separatorHeightType'          => 'px',
							'separatorColor'               => '#000',
							'elementType'                  => 'none',
							'elementPosition'              => 'center',
							'elementSpacing'               => 15,
							'elementSpacingTablet'         => 15,
							'elementSpacingMobile'         => 15,
							'elementSpacingUnit'           => 'px',
							'elementTextLoadGoogleFonts'   => false,
							'elementTextFontFamily'        => 'Default',
							'elementTextFontWeight'        => '',
							'elementTextFontSize'          => '',
							'elementTextFontSizeType'      => 'px',
							'elementTextFontSizeTablet'    => '',
							'elementTextFontSizeMobile'    => '',
							'elementTextLineHeightType'    => 'em',
							'elementTextLineHeight'        => 1,
							'elementTextLineHeightTablet'  => 1,
							'elementTextLineHeightMobile'  => 1,
							'elementTextFontStyle'         => 'normal',
							'elementTextLetterSpacing'     => '',
							'elementTextLetterSpacingTablet' => '',
							'elementTextLetterSpacingMobile' => '',
							'elementTextLetterSpacingType' => 'px',
							'elementTextDecoration'        => '',
							'elementTextTransform'         => '',
							'elementColor'                 => '#000',
							'elementIconWidth'             => '',
							'elementIconWidthTablet'       => '',
							'elementIconWidthMobile'       => '',
							'elementIconWidthType'         => 'px',

							// Padding.
							'blockTopPadding'              => '',
							'blockRightPadding'            => '',
							'blockLeftPadding'             => '',
							'blockBottomPadding'           => '',
							'blockTopPaddingTablet'        => '',
							'blockRightPaddingTablet'      => '',
							'blockLeftPaddingTablet'       => '',
							'blockBottomPaddingTablet'     => '',
							'blockTopPaddingMobile'        => '',
							'blockRightPaddingMobile'      => '',
							'blockLeftPaddingMobile'       => '',
							'blockBottomPaddingMobile'     => '',
							'blockPaddingUnit'             => 'px',
							'blockPaddingUnitTablet'       => 'px',
							'blockPaddingUnitMobile'       => 'px',

							// Margin.
							'blockTopMargin'               => '',
							'blockRightMargin'             => '',
							'blockLeftMargin'              => '',
							'blockBottomMargin'            => '',
							'blockTopMarginTablet'         => '',
							'blockRightMarginTablet'       => '',
							'blockLeftMarginTablet'        => '',
							'blockBottomMarginTablet'      => '',
							'blockTopMarginMobile'         => '',
							'blockRightMarginMobile'       => '',
							'blockLeftMarginMobile'        => '',
							'blockBottomMarginMobile'      => '',
							'blockMarginUnit'              => 'px',
							'blockMarginUnitTablet'        => 'px',
							'blockMarginUnitMobile'        => 'px',
						],
					],
					'srfm/image'            => [
						'slug'        => '',
						'title'       => __( 'Image', 'sureforms' ),
						'description' => '',
						'default'     => true,
						'attributes'  => array_merge(
							$image_base_attr,
							$image_attribute,
							$overlay_attribute
						),
					],

					'srfm/icon'             => [
						'slug'        => '',
						'title'       => __( 'Icon', 'sureforms' ),
						'description' => '',
						'default'     => true,
						'attributes'  => array_merge(
							$icon_border_attribute,
							$icon_base_attr
						),
					],

					'srfm/advanced-heading' => [
						'slug'        => '',
						'title'       => __( 'Heading', 'sureforms' ),
						'description' => '',
						'default'     => true,
						'attributes'  => array_merge(
							$high_light_border_attribute,
							$heading_base_attr
						),

					],

				];
			}
			return apply_filters( 'srfm_gutenberg_blocks_attributes', self::$block_attributes );
		}

		/**
		 * Get Block Assets.
		 *
		 * @since 0.0.1
		 *
		 * @return array The Asset List.
		 */
		public static function get_block_assets() {

			if ( null === self::$block_assets ) {
				self::$block_assets = [];
			}

			return self::$block_assets;
		}


			/**
			 * Get Block Assets.
			 *
			 * @since 0.0.1
			 *
			 * @return array The Asset List.
			 */
		public static function get_block_assets_css() {

			if ( null === self::$block_assets_css ) {
				self::$block_assets_css = [
					'srfm/separator'        => [
						'name' => 'separator',
					],
					'srfm/image'            => [
						'name' => 'image',
					],
					'srfm/icon'             => [
						'name' => 'icon',
					],
					'srfm/advanced-heading' => [
						'name' => 'advanced-heading',
					],

				];
			}
			return self::$block_assets_css;
		}

		/**
		 * Border attribute generation Function.
		 *
		 * @since 0.0.1
		 * @param  array $prefix   Attribute Prefix.
		 * @return array
		 */
		public static function generate_border_attribute( $prefix ) {
			$defaults = [
				// Width.
				'borderTopWidth'                => '',
				'borderRightWidth'              => '',
				'borderBottomWidth'             => '',
				'borderLeftWidth'               => '',
				'borderTopWidthTablet'          => '',
				'borderRightWidthTablet'        => '',
				'borderBottomWidthTablet'       => '',
				'borderLeftWidthTablet'         => '',
				'borderTopWidthMobile'          => '',
				'borderRightWidthMobile'        => '',
				'borderBottomWidthMobile'       => '',
				'borderLeftWidthMobile'         => '',
				// Radius.
				'borderTopLeftRadius'           => '',
				'borderTopRightRadius'          => '',
				'borderBottomRightRadius'       => '',
				'borderBottomLeftRadius'        => '',
				'borderTopLeftRadiusTablet'     => '',
				'borderTopRightRadiusTablet'    => '',
				'borderBottomRightRadiusTablet' => '',
				'borderBottomLeftRadiusTablet'  => '',
				'borderTopLeftRadiusMobile'     => '',
				'borderTopRightRadiusMobile'    => '',
				'borderBottomRightRadiusMobile' => '',
				'borderBottomLeftRadiusMobile'  => '',
				// unit.
				'borderRadiusUnit'              => 'px',
				'borderRadiusUnitTablet'        => 'px',
				'borderRadiusUnitMobile'        => 'px',
				// common.
				'borderStyle'                   => '',
				'borderColor'                   => '',
				'borderHColor'                  => '',

			];

			$border_attr = [];

			$device = [ '', 'Tablet', 'Mobile' ];

			foreach ( $device as $slug => $data ) {

				$border_attr[ "{$prefix}BorderTopWidth{$data}" ]          = '';
				$border_attr[ "{$prefix}BorderLeftWidth{$data}" ]         = '';
				$border_attr[ "{$prefix}BorderRightWidth{$data}" ]        = '';
				$border_attr[ "{$prefix}BorderBottomWidth{$data}" ]       = '';
				$border_attr[ "{$prefix}BorderTopLeftRadius{$data}" ]     = '';
				$border_attr[ "{$prefix}BorderTopRightRadius{$data}" ]    = '';
				$border_attr[ "{$prefix}BorderBottomLeftRadius{$data}" ]  = '';
				$border_attr[ "{$prefix}BorderBottomRightRadius{$data}" ] = '';
				$border_attr[ "{$prefix}BorderRadiusUnit{$data}" ]        = 'px';
			}

			$border_attr[ "{$prefix}BorderStyle" ]  = '';
			$border_attr[ "{$prefix}BorderColor" ]  = '';
			$border_attr[ "{$prefix}BorderHColor" ] = '';
			return $border_attr;
		}
	}
}
