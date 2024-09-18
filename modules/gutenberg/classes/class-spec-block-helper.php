<?php
/**
 * Sureforms Block Helper.
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Spec_Block_Helper' ) ) {

	/**
	 * Class Spec_Block_Helper.
	 */
	class Spec_Block_Helper {
		/**
		 * Get Icon Block CSS
		 *
		 * @since 0.0.1
		 * @param array  $attr The block attributes.
		 * @param string $id The selector ID.
		 * @return array The Widget List.
		 */
		public static function get_icon_css( $attr, $id ) {

			$defaults = Spec_Gb_Helper::$block_list['srfm/icon']['attributes'];

			$attr = array_merge( $defaults, $attr );

			$icon_width       = Spec_Gb_Helper::get_css_value( $attr['iconSize'], $attr['iconSizeUnit'] );
			$transformation   = Spec_Gb_Helper::get_css_value( $attr['rotation'], $attr['rotationUnit'] );
			$background       = 'classic' === $attr['iconBackgroundColorType'] ? $attr['iconBackgroundColor'] : $attr['iconBackgroundGradientColor'];
			$hover_background = 'classic' === $attr['iconHoverBackgroundColorType'] ? $attr['iconHoverBackgroundColor'] : $attr['iconHoverBackgroundGradientColor'];

			$drop_shadow_properties = [
				'horizontal' => $attr['iconShadowHOffset'],
				'vertical'   => $attr['iconShadowVOffset'],
				'blur'       => $attr['iconShadowBlur'],
				'color'      => $attr['iconShadowColor'],
			];
			$drop_shadow            = Spec_Gb_Helper::generate_shadow_css( $drop_shadow_properties );

			$box_shadow_properties = [
				'horizontal' => $attr['iconBoxShadowHOffset'],
				'vertical'   => $attr['iconBoxShadowVOffset'],
				'blur'       => $attr['iconBoxShadowBlur'],
				'spread'     => $attr['iconBoxShadowSpread'],
				'color'      => $attr['iconBoxShadowColor'],
				'position'   => $attr['iconBoxShadowPosition'],
			];

			$box_shadow_hover_properties = [
				'horizontal' => $attr['iconBoxShadowHOffsetHover'],
				'vertical'   => $attr['iconBoxShadowVOffsetHover'],
				'blur'       => $attr['iconBoxShadowBlurHover'],
				'spread'     => $attr['iconBoxShadowSpreadHover'],
				'color'      => $attr['iconBoxShadowColorHover'],
				'position'   => $attr['iconBoxShadowPositionHover'],
				'alt_color'  => $attr['iconBoxShadowColor'],
			];

			$box_shadow           = Spec_Gb_Helper::generate_shadow_css( $box_shadow_properties );
			$box_shadow_hover_css = Spec_Gb_Helper::generate_shadow_css( $box_shadow_hover_properties );

			$t_selectors = [];
			$m_selectors = [];

			$selectors['.uagb-icon-wrapper']                             = [
				'text-align' => $attr['align'],
			];
			$selectors['.uagb-icon-wrapper .uagb-svg-wrapper a']         = [
				'display' => 'contents',
			];
			$selectors['.uagb-icon-wrapper svg']                         = [
				'width'      => $icon_width,
				'height'     => $icon_width,
				'transform'  => "rotate($transformation)",
				'box-sizing' => 'content-box',
				'fill'       => $attr['iconColor'],
				'filter'     => $drop_shadow ? "drop-shadow( $drop_shadow )" : '',
			];
			$selectors['.uagb-icon-wrapper .uagb-svg-wrapper:hover svg'] = [
				'fill' => $attr['iconHoverColor'],
			];
			$selectors['.uagb-icon-wrapper .uagb-svg-wrapper']           = array_merge(
				[
					'display'        => 'inline-flex',
					'background'     => $background,
					// padding.
					'padding-top'    => Spec_Gb_Helper::get_css_value( $attr['iconTopPadding'], $attr['iconPaddingUnit'] ),
					'padding-right'  => Spec_Gb_Helper::get_css_value( $attr['iconRightPadding'], $attr['iconPaddingUnit'] ),
					'padding-bottom' => Spec_Gb_Helper::get_css_value( $attr['iconBottomPadding'], $attr['iconPaddingUnit'] ),
					'padding-left'   => Spec_Gb_Helper::get_css_value( $attr['iconLeftPadding'], $attr['iconPaddingUnit'] ),
					// margin.
					'margin-top'     => Spec_Gb_Helper::get_css_value( $attr['iconTopMargin'], $attr['iconMarginUnit'] ),
					'margin-right'   => Spec_Gb_Helper::get_css_value( $attr['iconRightMargin'], $attr['iconMarginUnit'] ),
					'margin-bottom'  => Spec_Gb_Helper::get_css_value( $attr['iconBottomMargin'], $attr['iconMarginUnit'] ),
					'margin-left'    => Spec_Gb_Helper::get_css_value( $attr['iconLeftMargin'], $attr['iconMarginUnit'] ),
					// border.
					'border-style'   => $attr['iconBorderStyle'],
					'border-color'   => $attr['iconBorderColor'],
					'box-shadow'     => $box_shadow,
				],
				self::generate_border_css( $attr, 'icon' )
			);
			$selectors['.uagb-icon-wrapper .uagb-svg-wrapper:hover']     = [
				'border-color' => $attr['iconBorderHColor'],
				'background'   => $hover_background,
			];

			// If using separate box shadow hover settings, then generate CSS for it.
			if ( $attr['useSeparateBoxShadows'] ) {
				$selectors['.uagb-icon-wrapper .uagb-svg-wrapper:hover'] = [
					'box-shadow'   => $box_shadow_hover_css,
					'border-color' => $attr['iconBorderHColor'],
					'background'   => $hover_background,
				];

			};

			// Generates css for tablet devices.
			$t_icon_width                                        = Spec_Gb_Helper::get_css_value( $attr['iconSizeTablet'], $attr['iconSizeUnit'] );
			$t_selectors['.uagb-icon-wrapper']                   = [
				'text-align' => $attr['alignTablet'],
			];
			$t_selectors['.uagb-icon-wrapper svg']               = [
				'width'  => $t_icon_width,
				'height' => $t_icon_width,
			];
			$t_selectors['.uagb-icon-wrapper .uagb-svg-wrapper'] = array_merge(
				[
					'display'        => 'inline-flex',
					'padding-top'    => Spec_Gb_Helper::get_css_value( $attr['iconTopTabletPadding'], $attr['iconTabletPaddingUnit'] ),
					'padding-right'  => Spec_Gb_Helper::get_css_value( $attr['iconRightTabletPadding'], $attr['iconTabletPaddingUnit'] ),
					'padding-bottom' => Spec_Gb_Helper::get_css_value( $attr['iconBottomTabletPadding'], $attr['iconTabletPaddingUnit'] ),
					'padding-left'   => Spec_Gb_Helper::get_css_value( $attr['iconLeftTabletPadding'], $attr['iconTabletPaddingUnit'] ),
					'margin-top'     => Spec_Gb_Helper::get_css_value( $attr['iconTopTabletMargin'], $attr['iconTabletMarginUnit'] ),
					'margin-right'   => Spec_Gb_Helper::get_css_value( $attr['iconRightTabletMargin'], $attr['iconTabletMarginUnit'] ),
					'margin-bottom'  => Spec_Gb_Helper::get_css_value( $attr['iconBottomTabletMargin'], $attr['iconTabletMarginUnit'] ),
					'margin-left'    => Spec_Gb_Helper::get_css_value( $attr['iconLeftTabletMargin'], $attr['iconTabletMarginUnit'] ),
				],
				self::generate_border_css( $attr, 'icon', 'tablet' )
			);

			// Generates css for mobile devices.
			$m_icon_width                                        = Spec_Gb_Helper::get_css_value( $attr['iconSizeMobile'], $attr['iconSizeUnit'] );
			$m_selectors['.uagb-icon-wrapper']                   = [
				'text-align' => $attr['alignMobile'],
			];
			$m_selectors['.uagb-icon-wrapper svg']               = [
				'width'  => $m_icon_width,
				'height' => $m_icon_width,
			];
			$m_selectors['.uagb-icon-wrapper .uagb-svg-wrapper'] = array_merge(
				[
					'display'        => 'inline-flex',
					'padding-top'    => Spec_Gb_Helper::get_css_value( $attr['iconTopMobilePadding'], $attr['iconMobilePaddingUnit'] ),
					'padding-right'  => Spec_Gb_Helper::get_css_value( $attr['iconRightMobilePadding'], $attr['iconMobilePaddingUnit'] ),
					'padding-bottom' => Spec_Gb_Helper::get_css_value( $attr['iconBottomMobilePadding'], $attr['iconMobilePaddingUnit'] ),
					'padding-left'   => Spec_Gb_Helper::get_css_value( $attr['iconLeftMobilePadding'], $attr['iconMobilePaddingUnit'] ),
					'margin-top'     => Spec_Gb_Helper::get_css_value( $attr['iconTopMobileMargin'], $attr['iconMobileMarginUnit'] ),
					'margin-right'   => Spec_Gb_Helper::get_css_value( $attr['iconRightMobileMargin'], $attr['iconMobileMarginUnit'] ),
					'margin-bottom'  => Spec_Gb_Helper::get_css_value( $attr['iconBottomMobileMargin'], $attr['iconMobileMarginUnit'] ),
					'margin-left'    => Spec_Gb_Helper::get_css_value( $attr['iconLeftMobileMargin'], $attr['iconMobileMarginUnit'] ),
				],
				self::generate_border_css( $attr, 'icon', 'mobile' )
			);

			$combined_selectors = [
				'desktop' => $selectors,
				'tablet'  => $t_selectors,
				'mobile'  => $m_selectors,
			];

			return Spec_Gb_Helper::generate_all_css( $combined_selectors, ' .uagb-block-' . $id );
		}

		/**
		 * Get Image Block CSS
		 *
		 * @since 0.0.1
		 * @param array  $attr The block attributes.
		 * @param string $id The selector ID.
		 * @return array The Widget List.
		 */
		public static function get_image_css( $attr, $id ) {

			$defaults = Spec_Gb_Helper::$block_list['srfm/image']['attributes'];

			$attr = array_merge( $defaults, $attr );

			$m_selectors = [];
			$t_selectors = [];

			$image_border_css          = self::generate_border_css( $attr, 'image' );
			$image_border_css_tablet   = self::generate_border_css( $attr, 'image', 'tablet' );
			$image_border_css_mobile   = self::generate_border_css( $attr, 'image', 'mobile' );
			$overlay_border_css        = self::generate_border_css( $attr, 'overlay' );
			$overlay_border_css_tablet = self::generate_border_css( $attr, 'overlay', 'tablet' );
			$overlay_border_css_mobile = self::generate_border_css( $attr, 'overlay', 'mobile' );

			$width_tablet = '' !== $attr['widthTablet'] ? $attr['widthTablet'] . 'px' : $attr['width'] . 'px';
			$width_mobile = '' !== $attr['widthMobile'] ? $attr['widthMobile'] . 'px' : $width_tablet;

			$height_tablet = '' !== $attr['heightTablet'] ? $attr['heightTablet'] . 'px' : $attr['height'] . 'px';
			$height_mobile = '' !== $attr['heightMobile'] ? $attr['heightMobile'] . 'px' : $height_tablet;

			$align        = '';
			$align_tablet = '';
			$align_mobile = '';

			switch ( $attr['align'] ) {
				case 'left':
					$align = 'flex-start';
					break;
				case 'right':
					$align = 'flex-end';
					break;
				case 'center':
					$align = 'center';
					break;
			}

			switch ( $attr['alignTablet'] ) {
				case 'left':
					$align_tablet = 'flex-start';
					break;
				case 'right':
					$align_tablet = 'flex-end';
					break;
				case 'center':
					$align_tablet = 'center';
					break;
			}

			switch ( $attr['alignMobile'] ) {
				case 'left':
					$align_mobile = 'flex-start';
					break;
				case 'right':
					$align_mobile = 'flex-end';
					break;
				case 'center':
					$align_mobile = 'center';
					break;
			}

			$box_shadow_properties       = [
				'horizontal' => $attr['imageBoxShadowHOffset'],
				'vertical'   => $attr['imageBoxShadowVOffset'],
				'blur'       => $attr['imageBoxShadowBlur'],
				'spread'     => $attr['imageBoxShadowSpread'],
				'color'      => $attr['imageBoxShadowColor'],
				'position'   => $attr['imageBoxShadowPosition'],
			];
			$box_shadow_hover_properties = [
				'horizontal' => $attr['imageBoxShadowHOffsetHover'],
				'vertical'   => $attr['imageBoxShadowVOffsetHover'],
				'blur'       => $attr['imageBoxShadowBlurHover'],
				'spread'     => $attr['imageBoxShadowSpreadHover'],
				'color'      => $attr['imageBoxShadowColorHover'],
				'position'   => $attr['imageBoxShadowPositionHover'],
				'alt_color'  => $attr['imageBoxShadowColor'],
			];

			$box_shadow_css       = Spec_Gb_Helper::generate_shadow_css( $box_shadow_properties );
			$box_shadow_hover_css = Spec_Gb_Helper::generate_shadow_css( $box_shadow_hover_properties );

			$selectors = [
				'.wp-block-uagb-image'          => [
					'margin-top'      => Spec_Gb_Helper::get_css_value( $attr['imageTopMargin'], $attr['imageMarginUnit'] ),
					'margin-right'    => Spec_Gb_Helper::get_css_value( $attr['imageRightMargin'], $attr['imageMarginUnit'] ),
					'margin-bottom'   => Spec_Gb_Helper::get_css_value( $attr['imageBottomMargin'], $attr['imageMarginUnit'] ),
					'margin-left'     => Spec_Gb_Helper::get_css_value( $attr['imageLeftMargin'], $attr['imageMarginUnit'] ),
					'text-align'      => $attr['align'],
					'justify-content' => $align,
					'align-self'      => $align,
				],
				' .wp-block-uagb-image__figure' => [
					'align-items' => $align,
				],
				'.wp-block-uagb-image--layout-default figure img' => array_merge(
					[
						'box-shadow' => $box_shadow_css,
					],
					$image_border_css
				),
				'.wp-block-uagb-image .wp-block-uagb-image__figure img:hover' => [
					'border-color' => $attr['imageBorderHColor'],
				],
				'.wp-block-uagb-image .wp-block-uagb-image__figure figcaption' => [
					'color'         => $attr['captionColor'],
					'margin-top'    => Spec_Gb_Helper::get_css_value( $attr['captionTopMargin'], $attr['captionMarginUnit'] ),
					'margin-right'  => Spec_Gb_Helper::get_css_value( $attr['captionRightMargin'], $attr['captionMarginUnit'] ),
					'margin-bottom' => Spec_Gb_Helper::get_css_value( $attr['captionBottomMargin'], $attr['captionMarginUnit'] ),
					'margin-left'   => Spec_Gb_Helper::get_css_value( $attr['captionLeftMargin'], $attr['captionMarginUnit'] ),
					'text-align'    => $attr['captionAlign'],
				],
				'.wp-block-uagb-image .wp-block-uagb-image__figure figcaption a' => [
					'color' => $attr['captionColor'],
				],
				// overlay.
				'.wp-block-uagb-image--layout-overlay figure img' => array_merge(
					[
						'box-shadow' => $box_shadow_css,
					],
					$image_border_css
				),
				'.wp-block-uagb-image--layout-overlay .wp-block-uagb-image--layout-overlay__color-wrapper' => array_merge(
					[
						'background' => $attr['overlayBackground'],
						'opacity'    => $attr['overlayOpacity'],
					],
					$image_border_css
				),
				'.wp-block-uagb-image--layout-overlay .wp-block-uagb-image--layout-overlay__color-wrapper:hover' => [
					'border-color' => $attr['imageBorderHColor'],
				],
				'.wp-block-uagb-image--layout-overlay .wp-block-uagb-image--layout-overlay__inner' => array_merge(
					$overlay_border_css,
					[
						'left'   => Spec_Gb_Helper::get_css_value( $attr['overlayPositionFromEdge'], $attr['overlayPositionFromEdgeUnit'] ),
						'right'  => Spec_Gb_Helper::get_css_value( $attr['overlayPositionFromEdge'], $attr['overlayPositionFromEdgeUnit'] ),
						'top'    => Spec_Gb_Helper::get_css_value( $attr['overlayPositionFromEdge'], $attr['overlayPositionFromEdgeUnit'] ),
						'bottom' => Spec_Gb_Helper::get_css_value( $attr['overlayPositionFromEdge'], $attr['overlayPositionFromEdgeUnit'] ),
					]
				),
				'.wp-block-uagb-image--layout-overlay .wp-block-uagb-image--layout-overlay__inner .uagb-image-heading' => [
					'color'         => $attr['headingColor'],
					'margin-top'    => Spec_Gb_Helper::get_css_value( $attr['headingTopMargin'], $attr['headingMarginUnit'] ),
					'margin-right'  => Spec_Gb_Helper::get_css_value( $attr['headingRightMargin'], $attr['headingMarginUnit'] ),
					'margin-bottom' => Spec_Gb_Helper::get_css_value( $attr['headingBottomMargin'], $attr['headingMarginUnit'] ),
					'margin-left'   => Spec_Gb_Helper::get_css_value( $attr['headingLeftMargin'], $attr['headingMarginUnit'] ),
					'opacity'       => 'always' === $attr['headingShowOn'] ? 1 : 0,
				],
				'.wp-block-uagb-image--layout-overlay .wp-block-uagb-image--layout-overlay__inner .uagb-image-heading a' => [
					'color' => $attr['headingColor'],
				],
				'.wp-block-uagb-image--layout-overlay .wp-block-uagb-image--layout-overlay__inner .uagb-image-caption' => [
					'opacity' => 'always' === $attr['captionShowOn'] ? 1 : 0,
				],
				'.wp-block-uagb-image--layout-overlay .wp-block-uagb-image__figure:hover .wp-block-uagb-image--layout-overlay__inner' => [
					'border-color' => $attr['overlayBorderHColor'],
				],
				'.wp-block-uagb-image--layout-overlay .wp-block-uagb-image__figure:hover .wp-block-uagb-image--layout-overlay__color-wrapper' => [
					'opacity' => $attr['overlayHoverOpacity'],
				],
				// separator.
				'.wp-block-uagb-image .wp-block-uagb-image--layout-overlay__inner .uagb-image-separator' => [
					'width'            => Spec_Gb_Helper::get_css_value( $attr['separatorWidth'], $attr['separatorWidthType'] ),
					'border-top-width' => Spec_Gb_Helper::get_css_value( $attr['separatorThickness'], $attr['separatorThicknessUnit'] ),
					'border-top-color' => $attr['separatorColor'],
					'border-top-style' => $attr['separatorStyle'],
					'margin-bottom'    => Spec_Gb_Helper::get_css_value( $attr['separatorBottomMargin'], $attr['separatorMarginUnit'] ),
					'margin-top'       => Spec_Gb_Helper::get_css_value( $attr['separatorTopMargin'], $attr['separatorMarginUnit'] ),
					'margin-left'      => Spec_Gb_Helper::get_css_value( $attr['separatorLeftMargin'], $attr['separatorMarginUnit'] ),
					'margin-right'     => Spec_Gb_Helper::get_css_value( $attr['separatorRightMargin'], $attr['separatorMarginUnit'] ),
					'opacity'          => 'always' === $attr['separatorShowOn'] ? 1 : 0,
				],
			];

			$selectors['.wp-block-uagb-image .wp-block-uagb-image__figure img'] = [
				'object-fit' => $attr['objectFit'],
				'width'      => $attr['width'] . 'px',
				'height'     => 'auto',
			];
			if ( $attr['customHeightSetDesktop'] ) {
				$selectors['.wp-block-uagb-image .wp-block-uagb-image__figure img']['height'] = $attr['height'] . 'px';
			}

			if ( 'hover' === $attr['headingShowOn'] ) {
				$selectors['.wp-block-uagb-image .wp-block-uagb-image__figure:hover .wp-block-uagb-image--layout-overlay__inner .uagb-image-heading'] = [
					'opacity' => 1,
				];
			}
			if ( 'hover' === $attr['captionShowOn'] ) {
				$selectors['.wp-block-uagb-image .wp-block-uagb-image__figure:hover .wp-block-uagb-image--layout-overlay__inner .uagb-image-caption'] = [
					'opacity' => 1,
				];
			}
			if ( 'hover' === $attr['separatorShowOn'] ) {
				$selectors['.wp-block-uagb-image .wp-block-uagb-image__figure:hover .wp-block-uagb-image--layout-overlay__inner .uagb-image-separator'] = [
					'opacity' => 1,
				];
			}

			// If using separate box shadow hover settings, then generate CSS for it.
			if ( $attr['useSeparateBoxShadows'] ) {
				$selectors['.wp-block-uagb-image--layout-default figure img:hover'] = [
					'box-shadow' => $box_shadow_hover_css,
				];

				$selectors['.wp-block-uagb-image--layout-overlay figure img:hover'] = [
					'box-shadow' => $box_shadow_hover_css,
				];

			};

			if ( 'none' !== $attr['maskShape'] ) {
				$image_path = SRFM_URL . 'assets/images/masks/' . $attr['maskShape'] . '.svg';
				if ( 'custom' === $attr['maskShape'] ) {
					$image_path = $attr['maskCustomShape']['url'];
				}
				if ( ! empty( $image_path ) ) {
					$selectors[ '.wp-block-uagb-image .wp-block-uagb-image__figure img, .uagb-block-' . $id . ' .wp-block-uagb-image--layout-overlay__color-wrapper' ] = [
						'mask-image'            => 'url(' . $image_path . ')',
						'-webkit-mask-image'    => 'url(' . $image_path . ')',
						'mask-size'             => $attr['maskSize'],
						'-webkit-mask-size'     => $attr['maskSize'],
						'mask-repeat'           => $attr['maskRepeat'],
						'-webkit-mask-repeat'   => $attr['maskRepeat'],
						'mask-position'         => $attr['maskPosition'],
						'-webkit-mask-position' => $attr['maskPosition'],
					];
				}
			}

			// tablet.
			$t_selectors['.wp-block-uagb-image--layout-default figure img']       = $image_border_css_tablet;
			$t_selectors['.wp-block-uagb-image--layout-overlay figure img']       = $image_border_css_tablet;
			$t_selectors['.wp-block-uagb-image .wp-block-uagb-image__figure img'] = [
				'width' => Spec_Gb_Helper::get_css_value( $attr['widthTablet'], 'px' ),
			];
			$t_selectors['.wp-block-uagb-image']                                  = [
				'margin-top'      => Spec_Gb_Helper::get_css_value( $attr['imageTopMarginTablet'], $attr['imageMarginUnitTablet'] ),
				'margin-right'    => Spec_Gb_Helper::get_css_value( $attr['imageRightMarginTablet'], $attr['imageMarginUnitTablet'] ),
				'margin-bottom'   => Spec_Gb_Helper::get_css_value( $attr['imageBottomMarginTablet'], $attr['imageMarginUnitTablet'] ),
				'margin-left'     => Spec_Gb_Helper::get_css_value( $attr['imageLeftMarginTablet'], $attr['imageMarginUnitTablet'] ),
				'text-align'      => $attr['alignTablet'],
				'justify-content' => $align_tablet,
				'align-self'      => $align_tablet,
			];
			$t_selectors[' .wp-block-uagb-image__figure']                         = [
				'align-items' => $align_tablet,
			];
			$t_selectors['.wp-block-uagb-image .wp-block-uagb-image__figure figcaption']                           = [
				'margin-top'    => Spec_Gb_Helper::get_css_value( $attr['captionTopMarginTablet'], $attr['captionMarginUnitTablet'] ),
				'margin-right'  => Spec_Gb_Helper::get_css_value( $attr['captionRightMarginTablet'], $attr['captionMarginUnitTablet'] ),
				'margin-bottom' => Spec_Gb_Helper::get_css_value( $attr['captionBottomMarginTablet'], $attr['captionMarginUnitTablet'] ),
				'margin-left'   => Spec_Gb_Helper::get_css_value( $attr['captionLeftMarginTablet'], $attr['captionMarginUnitTablet'] ),
			];
			$t_selectors['.wp-block-uagb-image--layout-overlay .wp-block-uagb-image--layout-overlay__inner']       = $overlay_border_css_tablet;
			$t_selectors['.wp-block-uagb-image .wp-block-uagb-image--layout-overlay__inner .uagb-image-heading']   = [
				'margin-top'    => Spec_Gb_Helper::get_css_value( $attr['headingTopMarginTablet'], $attr['headingMarginUnitTablet'] ),
				'margin-right'  => Spec_Gb_Helper::get_css_value( $attr['headingRightMarginTablet'], $attr['headingMarginUnitTablet'] ),
				'margin-bottom' => Spec_Gb_Helper::get_css_value( $attr['headingBottomMarginTablet'], $attr['headingMarginUnitTablet'] ),
				'margin-left'   => Spec_Gb_Helper::get_css_value( $attr['headingLeftMarginTablet'], $attr['headingMarginUnitTablet'] ),
			];
			$t_selectors['.wp-block-uagb-image .wp-block-uagb-image--layout-overlay__inner .uagb-image-separator'] = [
				'margin-bottom' => Spec_Gb_Helper::get_css_value( $attr['separatorBottomMarginTablet'], $attr['separatorMarginUnitTablet'] ),
				'margin-top'    => Spec_Gb_Helper::get_css_value( $attr['separatorTopMarginTablet'], $attr['separatorMarginUnitTablet'] ),
				'margin-left'   => Spec_Gb_Helper::get_css_value( $attr['separatorLeftMarginTablet'], $attr['separatorMarginUnitTablet'] ),
				'margin-right'  => Spec_Gb_Helper::get_css_value( $attr['separatorRightMarginTablet'], $attr['separatorMarginUnitTablet'] ),
			];

			$t_selectors['.wp-block-uagb-image .wp-block-uagb-image__figure img'] = [
				'object-fit' => $attr['objectFitTablet'],
				'width'      => $width_tablet,
				'height'     => 'auto',
			];

			if ( $attr['customHeightSetTablet'] ) {
				$t_selectors['.wp-block-uagb-image .wp-block-uagb-image__figure img']['height'] = $height_tablet;
			}

			// mobile.
			$m_selectors['.wp-block-uagb-image--layout-default figure img']       = $image_border_css_mobile;
			$m_selectors['.wp-block-uagb-image--layout-overlay figure img']       = $image_border_css_mobile;
			$m_selectors['.wp-block-uagb-image .wp-block-uagb-image__figure img'] = [
				'width' => Spec_Gb_Helper::get_css_value( $attr['widthMobile'], 'px' ),
			];
			$m_selectors['.wp-block-uagb-image']                                  = [
				'margin-top'      => Spec_Gb_Helper::get_css_value( $attr['imageTopMarginMobile'], $attr['imageMarginUnitMobile'] ),
				'margin-right'    => Spec_Gb_Helper::get_css_value( $attr['imageRightMarginMobile'], $attr['imageMarginUnitMobile'] ),
				'margin-bottom'   => Spec_Gb_Helper::get_css_value( $attr['imageBottomMarginMobile'], $attr['imageMarginUnitMobile'] ),
				'margin-left'     => Spec_Gb_Helper::get_css_value( $attr['imageLeftMarginMobile'], $attr['imageMarginUnitMobile'] ),
				'text-align'      => $attr['alignMobile'],
				'justify-content' => $align_mobile,
				'align-self'      => $align_mobile,
			];
			$m_selectors[' .wp-block-uagb-image__figure']                         = [
				'align-items' => $align_mobile,
			];
			$m_selectors['.wp-block-uagb-image .wp-block-uagb-image__figure figcaption'] = [
				'margin-top'    => Spec_Gb_Helper::get_css_value( $attr['captionTopMarginMobile'], $attr['captionMarginUnitMobile'] ),
				'margin-right'  => Spec_Gb_Helper::get_css_value( $attr['captionRightMarginMobile'], $attr['captionMarginUnitMobile'] ),
				'margin-bottom' => Spec_Gb_Helper::get_css_value( $attr['captionBottomMarginMobile'], $attr['captionMarginUnitMobile'] ),
				'margin-left'   => Spec_Gb_Helper::get_css_value( $attr['captionLeftMarginMobile'], $attr['captionMarginUnitMobile'] ),
			];

			$m_selectors['.wp-block-uagb-image .wp-block-uagb-image--layout-overlay__inner .uagb-image-heading']   = [
				'margin-top'    => Spec_Gb_Helper::get_css_value( $attr['headingTopMarginMobile'], $attr['headingMarginUnitMobile'] ),
				'margin-right'  => Spec_Gb_Helper::get_css_value( $attr['headingRightMarginMobile'], $attr['headingMarginUnitMobile'] ),
				'margin-bottom' => Spec_Gb_Helper::get_css_value( $attr['headingBottomMarginMobile'], $attr['headingMarginUnitMobile'] ),
				'margin-left'   => Spec_Gb_Helper::get_css_value( $attr['headingLeftMarginMobile'], $attr['headingMarginUnitMobile'] ),
			];
			$m_selectors['.wp-block-uagb-image--layout-overlay .wp-block-uagb-image--layout-overlay__inner']       = $overlay_border_css_mobile;
			$m_selectors['.wp-block-uagb-image .wp-block-uagb-image--layout-overlay__inner .uagb-image-separator'] = [
				'margin-bottom' => Spec_Gb_Helper::get_css_value( $attr['separatorBottomMarginMobile'], $attr['separatorMarginUnitMobile'] ),
				'margin-top'    => Spec_Gb_Helper::get_css_value( $attr['separatorTopMarginMobile'], $attr['separatorMarginUnitMobile'] ),
				'margin-left'   => Spec_Gb_Helper::get_css_value( $attr['separatorLeftMarginMobile'], $attr['separatorMarginUnitMobile'] ),
				'margin-right'  => Spec_Gb_Helper::get_css_value( $attr['separatorRightMarginMobile'], $attr['separatorMarginUnitMobile'] ),
			];

			$m_selectors['.wp-block-uagb-image .wp-block-uagb-image__figure img'] = [
				'object-fit' => $attr['objectFitMobile'],
				'width'      => $width_mobile,
				'height'     => 'auto',
			];

			if ( $attr['customHeightSetMobile'] ) {
				$m_selectors['.wp-block-uagb-image .wp-block-uagb-image__figure img']['height'] = $height_mobile;
			}

			$combined_selectors = [
				'desktop' => $selectors,
				'tablet'  => $t_selectors,
				'mobile'  => $m_selectors,
			];

			$combined_selectors = Spec_Gb_Helper::get_typography_css( $attr, 'heading', '.wp-block-uagb-image--layout-overlay .wp-block-uagb-image--layout-overlay__inner .uagb-image-heading', $combined_selectors );
			$combined_selectors = Spec_Gb_Helper::get_typography_css( $attr, 'caption', '.wp-block-uagb-image .wp-block-uagb-image__figure figcaption', $combined_selectors );

			return Spec_Gb_Helper::generate_all_css( $combined_selectors, ' .uagb-block-' . $id );
		}

		/**
		 * Get Heading Block CSS
		 *
		 * @since 0.0.1
		 * @param array  $attr The block attributes.
		 * @param string $id The selector ID.
		 * @return array The Widget List.
		 */
		public static function get_advanced_heading_css( $attr, $id ) {
			$defaults = Spec_Gb_Helper::$block_list['srfm/advanced-heading']['attributes'];

			$attr = array_merge( $defaults, $attr );

			$t_selectors = [];
			$m_selectors = [];
			$selectors   = [];

			$high_light_border_css        = self::generate_border_css( $attr, 'highLight' );
			$high_light_border_css_tablet = self::generate_border_css( $attr, 'highLight', 'tablet' );
			$high_light_border_css_mobile = self::generate_border_css( $attr, 'highLight', 'mobile' );

			$selectors = [
				'.wp-block-uagb-advanced-heading .uagb-heading-text' => [
					'color' => $attr['headingColor'],
				],
				'.wp-block-uagb-advanced-heading '        => [
					'background'     => 'classic' === $attr['blockBackgroundType'] ? $attr['blockBackground'] : $attr['blockGradientBackground'],
					'text-align'     => $attr['headingAlign'],
					'margin-top'     => Spec_Gb_Helper::get_css_value(
						$attr['blockTopMargin'],
						$attr['blockMarginUnit']
					),
					'margin-right'   => Spec_Gb_Helper::get_css_value(
						$attr['blockRightMargin'],
						$attr['blockMarginUnit']
					),
					'margin-bottom'  => Spec_Gb_Helper::get_css_value(
						$attr['blockBottomMargin'],
						$attr['blockMarginUnit']
					),
					'margin-left'    => Spec_Gb_Helper::get_css_value(
						$attr['blockLeftMargin'],
						$attr['blockMarginUnit']
					),
					'padding-top'    => Spec_Gb_Helper::get_css_value(
						$attr['blockTopPadding'],
						$attr['blockPaddingUnit']
					),
					'padding-right'  => Spec_Gb_Helper::get_css_value(
						$attr['blockRightPadding'],
						$attr['blockPaddingUnit']
					),
					'padding-bottom' => Spec_Gb_Helper::get_css_value(
						$attr['blockBottomPadding'],
						$attr['blockPaddingUnit']
					),
					'padding-left'   => Spec_Gb_Helper::get_css_value(
						$attr['blockLeftPadding'],
						$attr['blockPaddingUnit']
					),
				],
				'.wp-block-uagb-advanced-heading a'       => [
					'color' => $attr['linkColor'],
				],
				'.wp-block-uagb-advanced-heading a:hover' => [
					'color' => $attr['linkHColor'],
				],
				'.wp-block-uagb-advanced-heading .uagb-desc-text' => [
					'color'         => $attr['subHeadingColor'],
					'margin-bottom' => Spec_Gb_Helper::get_css_value(
						$attr['subHeadSpace'],
						'px'
					),
				],
				'.wp-block-uagb-advanced-heading .srfm-highlight' => array_merge(
					[
						'background'              => $attr['highLightBackground'],
						'color'                   => $attr['highLightColor'],
						'-webkit-text-fill-color' => $attr['highLightColor'],
						'font-family'             => $attr['highLightFontFamily'],
						'font-style'              => $attr['highLightFontStyle'],
						'text-decoration'         => $attr['highLightDecoration'],
						'text-transform'          => $attr['highLightTransform'],
						'font-weight'             => $attr['highLightFontWeight'],
						'font-size'               => Spec_Gb_Helper::get_css_value( $attr['highLightFontSize'], $attr['highLightFontSizeType'] ),
						'line-height'             => Spec_Gb_Helper::get_css_value( $attr['highLightLineHeight'], $attr['highLightLineHeightType'] ),
						'padding-top'             => Spec_Gb_Helper::get_css_value(
							$attr['highLightTopPadding'],
							$attr['highLightPaddingUnit']
						),
						'padding-right'           => Spec_Gb_Helper::get_css_value(
							$attr['highLightRightPadding'],
							$attr['highLightPaddingUnit']
						),
						'padding-bottom'          => Spec_Gb_Helper::get_css_value(
							$attr['highLightBottomPadding'],
							$attr['highLightPaddingUnit']
						),
						'padding-left'            => Spec_Gb_Helper::get_css_value(
							$attr['highLightLeftPadding'],
							$attr['highLightPaddingUnit']
						),

					],
					$high_light_border_css
				),
				'.wp-block-uagb-advanced-heading .srfm-highlight:hover' => [
					'border-color' => $attr['highLightBorderHColor'],
				],
			];

			$heading_text_shadow_color = ( ! empty( $attr['headShadowColor'] ) ? Spec_Gb_Helper::get_css_value( $attr['headShadowHOffset'], 'px' ) . ' ' . Spec_Gb_Helper::get_css_value( $attr['headShadowVOffset'], 'px' ) . ' ' . Spec_Gb_Helper::get_css_value( $attr['headShadowBlur'], 'px' ) . ' ' . $attr['headShadowColor'] : '' );

			if ( 'gradient' === $attr['headingColorType'] ) {
				$selectors['.wp-block-uagb-advanced-heading .uagb-heading-text'] = array_merge(
					$selectors['.wp-block-uagb-advanced-heading .uagb-heading-text'],
					[
						'background'              => $attr['headingGradientColor'],
						'-webkit-background-clip' => 'text',
						'-webkit-text-fill-color' => 'transparent',
						'filter'                  => 'drop-shadow( ' . $heading_text_shadow_color . ' )',
					]
				);
				$selectors['.wp-block-uagb-advanced-heading a']                  = array_merge(
					$selectors['.wp-block-uagb-advanced-heading a'],
					[
						'-webkit-text-fill-color' => $attr['linkColor'],
					]
				);
				$selectors['.wp-block-uagb-advanced-heading a:hover']            = array_merge(
					$selectors['.wp-block-uagb-advanced-heading a:hover'],
					[
						'-webkit-text-fill-color' => $attr['linkHColor'],
					]
				);
			} else {
				$selectors['.wp-block-uagb-advanced-heading .uagb-heading-text'] = array_merge(
					$selectors['.wp-block-uagb-advanced-heading .uagb-heading-text'],
					[
						'text-shadow' => $heading_text_shadow_color,
					]
				);
			}

			// Text Selection & highlight.
			$highlight_selection_text = [
				'color'                   => $attr['highLightColor'],
				'background'              => $attr['highLightBackground'],
				'-webkit-text-fill-color' => $attr['highLightColor'],
			];

			$selectors['.wp-block-uagb-advanced-heading .srfm-highlight::-moz-selection'] = $highlight_selection_text;
			$selectors['.wp-block-uagb-advanced-heading .srfm-highlight::selection']      = $highlight_selection_text;

			$separator_style = isset( $attr['separatorStyle'] ) ? $attr['separatorStyle'] : '';

			if ( 'none' !== $separator_style ) {
				$selectors['.wp-block-uagb-advanced-heading .uagb-separator']   = [
					'border-top-style' => $attr['separatorStyle'],
					'border-top-width' => Spec_Gb_Helper::get_css_value(
						$attr['separatorHeight'],
						$attr['separatorHeightType']
					),
					'width'            => Spec_Gb_Helper::get_css_value(
						$attr['separatorWidth'],
						$attr['separatorWidthType']
					),
					'border-color'     => $attr['separatorColor'],
					'margin-bottom'    => Spec_Gb_Helper::get_css_value(
						$attr['separatorSpace'],
						$attr['separatorSpaceType']
					),
				];
				$t_selectors['.wp-block-uagb-advanced-heading .uagb-separator'] = [
					'width'         => Spec_Gb_Helper::get_css_value(
						$attr['separatorWidthTablet'],
						$attr['separatorWidthType']
					),
					'margin-bottom' => Spec_Gb_Helper::get_css_value(
						$attr['separatorSpaceTablet'],
						$attr['separatorSpaceType']
					),
				];
				$m_selectors['.wp-block-uagb-advanced-heading .uagb-separator'] = [
					'width'         => Spec_Gb_Helper::get_css_value(
						$attr['separatorWidthMobile'],
						$attr['separatorWidthType']
					),
					'margin-bottom' => Spec_Gb_Helper::get_css_value(
						$attr['separatorSpaceMobile'],
						$attr['separatorSpaceType']
					),
				];
			}
			$t_selectors['.wp-block-uagb-advanced-heading '] = [
				'text-align'     => $attr['headingAlignTablet'],
				'padding-top'    => Spec_Gb_Helper::get_css_value( $attr['blockTopPaddingTablet'], $attr['blockPaddingUnitTablet'] ),
				'padding-right'  => Spec_Gb_Helper::get_css_value( $attr['blockRightPaddingTablet'], $attr['blockPaddingUnitTablet'] ),
				'padding-bottom' => Spec_Gb_Helper::get_css_value( $attr['blockBottomPaddingTablet'], $attr['blockPaddingUnitTablet'] ),
				'padding-left'   => Spec_Gb_Helper::get_css_value( $attr['blockLeftPaddingTablet'], $attr['blockPaddingUnitTablet'] ),
				'margin-top'     => Spec_Gb_Helper::get_css_value( $attr['blockTopMarginTablet'], $attr['blockMarginUnitTablet'] ),
				'margin-right'   => Spec_Gb_Helper::get_css_value( $attr['blockRightMarginTablet'], $attr['blockMarginUnitTablet'] ),
				'margin-bottom'  => Spec_Gb_Helper::get_css_value( $attr['blockBottomMarginTablet'], $attr['blockMarginUnitTablet'] ),
				'margin-left'    => Spec_Gb_Helper::get_css_value( $attr['blockLeftMarginTablet'], $attr['blockMarginUnitTablet'] ),
			];

			$t_selectors['.wp-block-uagb-advanced-heading .srfm-highlight'] = array_merge(
				[
					'padding-top'    => Spec_Gb_Helper::get_css_value( $attr['highLightTopPaddingTablet'], $attr['highLightPaddingUnitTablet'] ),
					'padding-right'  => Spec_Gb_Helper::get_css_value( $attr['highLightRightPaddingTablet'], $attr['highLightPaddingUnitTablet'] ),
					'padding-bottom' => Spec_Gb_Helper::get_css_value( $attr['highLightBottomPaddingTablet'], $attr['highLightPaddingUnitTablet'] ),
					'padding-left'   => Spec_Gb_Helper::get_css_value( $attr['highLightLeftPaddingTablet'], $attr['highLightPaddingUnitTablet'] ),
				],
				$high_light_border_css_tablet
			);

			$m_selectors['.wp-block-uagb-advanced-heading ']                = [
				'text-align'     => $attr['headingAlignMobile'],
				'padding-top'    => Spec_Gb_Helper::get_css_value( $attr['blockTopPaddingMobile'], $attr['blockPaddingUnitMobile'] ),
				'padding-right'  => Spec_Gb_Helper::get_css_value( $attr['blockRightPaddingMobile'], $attr['blockPaddingUnitMobile'] ),
				'padding-bottom' => Spec_Gb_Helper::get_css_value( $attr['blockBottomPaddingMobile'], $attr['blockPaddingUnitMobile'] ),
				'padding-left'   => Spec_Gb_Helper::get_css_value( $attr['blockLeftPaddingMobile'], $attr['blockPaddingUnitMobile'] ),
				'margin-top'     => Spec_Gb_Helper::get_css_value( $attr['blockTopMarginMobile'], $attr['blockMarginUnitMobile'] ),
				'margin-right'   => Spec_Gb_Helper::get_css_value( $attr['blockRightMarginMobile'], $attr['blockMarginUnitMobile'] ),
				'margin-bottom'  => Spec_Gb_Helper::get_css_value( $attr['blockBottomMarginMobile'], $attr['blockMarginUnitMobile'] ),
				'margin-left'    => Spec_Gb_Helper::get_css_value( $attr['blockLeftMarginMobile'], $attr['blockMarginUnitMobile'] ),
			];
			$m_selectors['.wp-block-uagb-advanced-heading .srfm-highlight'] = array_merge(
				[
					'padding-top'    => Spec_Gb_Helper::get_css_value( $attr['highLightTopPaddingMobile'], $attr['highLightPaddingUnitMobile'] ),
					'padding-right'  => Spec_Gb_Helper::get_css_value( $attr['highLightRightPaddingMobile'], $attr['highLightPaddingUnitMobile'] ),
					'padding-bottom' => Spec_Gb_Helper::get_css_value( $attr['highLightBottomPaddingMobile'], $attr['highLightPaddingUnitMobile'] ),
					'padding-left'   => Spec_Gb_Helper::get_css_value( $attr['highLightLeftPaddingMobile'], $attr['highLightPaddingUnitMobile'] ),
				],
				$high_light_border_css_mobile
			);

			$t_selectors['.wp-block-uagb-advanced-heading .uagb-desc-text'] = [
				'margin-bottom' => Spec_Gb_Helper::get_css_value(
					$attr['subHeadSpaceTablet'],
					$attr['subHeadSpaceType']
				),
			];
			$m_selectors['.wp-block-uagb-advanced-heading .uagb-desc-text'] = [
				'margin-bottom' => Spec_Gb_Helper::get_css_value(
					$attr['subHeadSpaceMobile'],
					$attr['subHeadSpaceType']
				),
			];
			if ( $attr['headingDescToggle'] || 'none' !== $attr['separatorStyle'] ) {
				$selectors[' .uagb-heading-text']   = [
					'margin-bottom' => Spec_Gb_Helper::get_css_value(
						$attr['headSpace'],
						'px'
					),
				];
				$t_selectors[' .uagb-heading-text'] = [
					'margin-bottom' => Spec_Gb_Helper::get_css_value(
						$attr['headSpaceTablet'],
						$attr['headSpaceType']
					),
				];
				$m_selectors[' .uagb-heading-text'] = [
					'margin-bottom' => Spec_Gb_Helper::get_css_value(
						$attr['headSpaceMobile'],
						$attr['headSpaceType']
					),
				];
			}

			$combined_selectors = Spec_Gb_Helper::get_combined_selectors(
				'advanced-heading',
				[
					'desktop' => $selectors,
					'tablet'  => $t_selectors,
					'mobile'  => $m_selectors,
				],
				$attr
			);

			$combined_selectors = Spec_Gb_Helper::get_typography_css( $attr, 'head', ' .uagb-heading-text', $combined_selectors );
			$combined_selectors = Spec_Gb_Helper::get_typography_css( $attr, 'subHead', ' .uagb-desc-text', $combined_selectors );
			$combined_selectors = Spec_Gb_Helper::get_typography_css( $attr, 'highLight', '.wp-block-uagb-advanced-heading .srfm-highlight', $combined_selectors );

			return Spec_Gb_Helper::generate_all_css( $combined_selectors, ' .uagb-block-' . $id );

		}


		/**
		 * Get Separator Block CSS
		 *
		 * @since 0.0.1
		 * @param array  $attr The block attributes.
		 * @param string $id The selector ID.
		 * @return array The Widget List.
		 */
		public static function get_separator_css( $attr, $id ) {

			$defaults = Spec_Gb_Helper::$block_list['srfm/separator']['attributes'];

			$attr = array_merge( $defaults, $attr );

			$t_selectors = [];
			$m_selectors = [];
			$selectors   = [];
			$border_size = '100%';

			$border_css = [
				'-webkit-mask-size' => ( Spec_Gb_Helper::get_css_value( $attr['separatorSize'], $attr['separatorSizeType'] ) . ' ' . $border_size ),
				'border-top-width'  => Spec_Gb_Helper::get_css_value( $attr['separatorBorderHeight'], $attr['separatorBorderHeightUnit'] ),
				'width'             => Spec_Gb_Helper::get_css_value( $attr['separatorWidth'], $attr['separatorWidthType'] ),
				'border-top-color'  => $attr['separatorColor'],
				'border-top-style'  => $attr['separatorStyle'],
			];

			$border_style       = [];
			$icon_spacing_style = [];

			if ( 'none' === $attr['elementType'] ) {
				$border_style['.wp-block-uagb-separator:not(.wp-block-uagb-separator--text):not(.wp-block-uagb-separator--icon) .wp-block-uagb-separator__inner'] = $border_css;

			} else {
				$align_css    = Spec_Gb_Helper::alignment_css( $attr['separatorAlign'] );
				$border_style = [
					'.wp-block-uagb-separator .wp-block-uagb-separator__inner' => array_merge(
						[
							'width' => Spec_Gb_Helper::get_css_value( $attr['separatorWidth'], $attr['separatorWidthType'] ),

						],
						$align_css
					),
				];
				$border_style['.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::before'] = $border_css;
				$border_style['.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::before'] = $border_css;
				$border_style['.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::after']  = $border_css;
				$border_style['.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::after']  = $border_css;

				if ( 'left' === $attr['elementPosition'] ) {
					$icon_spacing_style['.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'] = [
						'margin-right' => Spec_Gb_Helper::get_css_value( $attr['elementSpacing'], $attr['elementSpacingUnit'] ),
					];
					$border_style['.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::before']                          = [
						'display' => 'none',
					];
					$border_style['.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::before']                          = [
						'display' => 'none',
					];
				}
				if ( 'right' === $attr['elementPosition'] ) {
					$icon_spacing_style['.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'] = [
						'margin-left' => Spec_Gb_Helper::get_css_value( $attr['elementSpacing'], $attr['elementSpacingUnit'] ),
					];
					$border_style['.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::after']                           = [
						'display' => 'none',
					];
					$border_style['.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::after']                           = [
						'display' => 'none',
					];
				}
				if ( 'center' === $attr['elementPosition'] ) {
					$icon_spacing_style['.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'] = [
						'margin-right' => Spec_Gb_Helper::get_css_value( $attr['elementSpacing'], $attr['elementSpacingUnit'] ),
						'margin-left'  => Spec_Gb_Helper::get_css_value( $attr['elementSpacing'], $attr['elementSpacingUnit'] ),
					];
				}
			}

			$selectors = [
				'.wp-block-uagb-separator.uagb-block .wp-block-uagb-separator-wrapper' => [
					'margin-top'     => Spec_Gb_Helper::get_css_value( $attr['blockTopMargin'], $attr['blockMarginUnit'] ),
					'margin-right'   => Spec_Gb_Helper::get_css_value( $attr['blockRightMargin'], $attr['blockMarginUnit'] ),
					'margin-bottom'  => Spec_Gb_Helper::get_css_value( $attr['blockBottomMargin'], $attr['blockMarginUnit'] ),
					'margin-left'    => Spec_Gb_Helper::get_css_value( $attr['blockLeftMargin'], $attr['blockMarginUnit'] ),
					'padding-top'    => Spec_Gb_Helper::get_css_value( $attr['blockTopPadding'], $attr['blockPaddingUnit'] ),
					'padding-right'  => Spec_Gb_Helper::get_css_value( $attr['blockRightPadding'], $attr['blockPaddingUnit'] ),
					'padding-bottom' => Spec_Gb_Helper::get_css_value( $attr['blockBottomPadding'], $attr['blockPaddingUnit'] ),
					'padding-left'   => Spec_Gb_Helper::get_css_value( $attr['blockLeftPadding'], $attr['blockPaddingUnit'] ),
					'text-align'     => $attr['separatorAlign'],
				],
				'.wp-block-uagb-separator--text .wp-block-uagb-separator-element .uagb-html-tag' => [
					'font-family'     => $attr['elementTextFontFamily'],
					'font-style'      => $attr['elementTextFontStyle'],
					'text-decoration' => $attr['elementTextDecoration'],
					'text-transform'  => $attr['elementTextTransform'],
					'font-weight'     => $attr['elementTextFontWeight'],
					'color'           => $attr['elementColor'],
					'font-size'       => Spec_Gb_Helper::get_css_value( $attr['elementTextFontSize'], $attr['elementTextFontSizeType'] ),
					'line-height'     => Spec_Gb_Helper::get_css_value( $attr['elementTextLineHeight'], $attr['elementTextLineHeightType'] ),
					'letter-spacing'  => Spec_Gb_Helper::get_css_value( $attr['elementTextLetterSpacing'], $attr['elementTextLetterSpacingType'] ),
				],
				'.wp-block-uagb-separator--icon .wp-block-uagb-separator-element svg' => [
					'font-size'   => Spec_Gb_Helper::get_css_value( $attr['elementIconWidth'], $attr['elementIconWidthType'] ),
					'width'       => Spec_Gb_Helper::get_css_value( $attr['elementIconWidth'], $attr['elementIconWidthType'] ),
					'height'      => Spec_Gb_Helper::get_css_value( $attr['elementIconWidth'], $attr['elementIconWidthType'] ),
					'line-height' => Spec_Gb_Helper::get_css_value( $attr['elementIconWidth'], $attr['elementIconWidthType'] ),
					'color'       => $attr['elementColor'],
					'fill'        => $attr['elementColor'],
				],
			];
			$selectors = array_merge( $selectors, $border_style, $icon_spacing_style );

			// Tablet.
			$border_css_tablet = [
				'-webkit-mask-size' => ( Spec_Gb_Helper::get_css_value( $attr['separatorSizeTablet'], $attr['separatorSizeType'] ) . ' ' . $border_size ),
				'border-top-width'  => Spec_Gb_Helper::get_css_value( $attr['separatorBorderHeightTablet'], $attr['separatorBorderHeightUnit'] ),
				'width'             => Spec_Gb_Helper::get_css_value( $attr['separatorWidthTablet'], $attr['separatorWidthType'] ),
				'border-top-color'  => $attr['separatorColor'],
				'border-top-style'  => $attr['separatorStyle'],
			];

			$border_style_tablet       = [];
			$icon_spacing_style_tablet = [];
			if ( 'none' === $attr['elementType'] ) {
				$border_style_tablet['.wp-block-uagb-separator:not(.wp-block-uagb-separator--text):not(.wp-block-uagb-separator--icon) .wp-block-uagb-separator__inner'] = $border_css_tablet;

			} else {
				$align_css           = Spec_Gb_Helper::alignment_css( $attr['separatorAlignTablet'] );
				$border_style_tablet = [
					'.wp-block-uagb-separator .wp-block-uagb-separator__inner' => array_merge(
						[
							'width' => Spec_Gb_Helper::get_css_value( $attr['separatorWidthTablet'], $attr['separatorWidthType'] ),

						],
						$align_css
					),
				];
				$border_style_tablet['.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::before'] = $border_css_tablet;
				$border_style_tablet['.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::before'] = $border_css_tablet;
				$border_style_tablet['.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::after']  = $border_css_tablet;
				$border_style_tablet['.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::after']  = $border_css_tablet;
				if ( 'left' === $attr['elementPosition'] ) {
					$icon_spacing_style_tablet['.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'] = [
						'margin-right' => Spec_Gb_Helper::get_css_value( $attr['elementSpacingTablet'], $attr['elementSpacingUnit'] ),
					];
					$border_style_tablet['.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::before']                          = [
						'display' => 'none',
					];
					$border_style_tablet['.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::before']                          = [
						'display' => 'none',
					];
				}
				if ( 'center' === $attr['elementPosition'] ) {
					$icon_spacing_style_tablet['.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'] = [
						'margin-left'  => Spec_Gb_Helper::get_css_value( $attr['elementSpacingTablet'], $attr['elementSpacingUnit'] ),
						'margin-right' => Spec_Gb_Helper::get_css_value( $attr['elementSpacingTablet'], $attr['elementSpacingUnit'] ),
					];
				}
				if ( 'right' === $attr['elementPosition'] ) {
					$icon_spacing_style_tablet['.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'] = [
						'margin-left' => Spec_Gb_Helper::get_css_value( $attr['elementSpacingTablet'], $attr['elementSpacingUnit'] ),
					];
					$border_style_tablet['.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::after']                           = [
						'display' => 'none',
					];
					$border_style_tablet['.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::after']                           = [
						'display' => 'none',
					];
				}
			}
			$t_selectors = [
				'.wp-block-uagb-separator.uagb-block .wp-block-uagb-separator-wrapper' => [
					'margin-top'     => Spec_Gb_Helper::get_css_value( $attr['blockTopMarginTablet'], $attr['blockMarginUnit'] ),
					'margin-right'   => Spec_Gb_Helper::get_css_value( $attr['blockRightMarginTablet'], $attr['blockMarginUnit'] ),
					'margin-bottom'  => Spec_Gb_Helper::get_css_value( $attr['blockBottomMarginTablet'], $attr['blockMarginUnit'] ),
					'margin-left'    => Spec_Gb_Helper::get_css_value( $attr['blockLeftMarginTablet'], $attr['blockMarginUnit'] ),
					'padding-top'    => Spec_Gb_Helper::get_css_value( $attr['blockTopPaddingTablet'], $attr['blockPaddingUnit'] ),
					'padding-right'  => Spec_Gb_Helper::get_css_value( $attr['blockRightPaddingTablet'], $attr['blockPaddingUnit'] ),
					'padding-bottom' => Spec_Gb_Helper::get_css_value( $attr['blockBottomPaddingTablet'], $attr['blockPaddingUnit'] ),
					'padding-left'   => Spec_Gb_Helper::get_css_value( $attr['blockLeftPaddingTablet'], $attr['blockPaddingUnit'] ),
					'text-align'     => $attr['separatorAlignTablet'],
				],
				'.wp-block-uagb-separator--text .wp-block-uagb-separator-element .uagb-html-tag' => [
					'font-family'     => $attr['elementTextFontFamily'],
					'font-style'      => $attr['elementTextFontStyle'],
					'text-decoration' => $attr['elementTextDecoration'],
					'text-transform'  => $attr['elementTextTransform'],
					'font-weight'     => $attr['elementTextFontWeight'],
					'color'           => $attr['elementColor'],
					'margin-bottom'   => 'initial',
					'font-size'       => Spec_Gb_Helper::get_css_value( $attr['elementTextFontSizeTablet'], $attr['elementTextFontSizeType'] ),
					'line-height'     => Spec_Gb_Helper::get_css_value( $attr['elementTextLineHeightTablet'], $attr['elementTextLineHeightType'] ),
					'letter-spacing'  => Spec_Gb_Helper::get_css_value( $attr['elementTextLetterSpacingTablet'], $attr['elementTextLetterSpacingType'] ),
				],
				'.wp-block-uagb-separator--icon .wp-block-uagb-separator-element svg' => [
					'font-size'   => Spec_Gb_Helper::get_css_value( $attr['elementIconWidthTablet'], $attr['elementIconWidthType'] ),
					'width'       => Spec_Gb_Helper::get_css_value( $attr['elementIconWidthTablet'], $attr['elementIconWidthType'] ),
					'height'      => Spec_Gb_Helper::get_css_value( $attr['elementIconWidthTablet'], $attr['elementIconWidthType'] ),
					'line-height' => Spec_Gb_Helper::get_css_value( $attr['elementIconWidthTablet'], $attr    ['elementIconWidthType'] ),
					'color'       => $attr['elementColor'],
					'fill'        => $attr['elementColor'],
				],
			];

			$t_selectors = array_merge( $t_selectors, $border_style_tablet, $icon_spacing_style_tablet );

			// Mobile.
			$border_css_mobile         = [
				'-webkit-mask-size' => ( Spec_Gb_Helper::get_css_value( $attr['separatorSizeMobile'], $attr['separatorSizeType'] ) . ' ' . $border_size ),
				'border-top-width'  => Spec_Gb_Helper::get_css_value( $attr['separatorBorderHeightMobile'], $attr['separatorBorderHeightUnit'] ),
				'width'             => Spec_Gb_Helper::get_css_value( $attr['separatorWidthMobile'], $attr['separatorWidthType'] ),
				'border-top-color'  => $attr['separatorColor'],
				'border-top-style'  => $attr['separatorStyle'],
			];
			$border_style_mobile       = [];
			$icon_spacing_style_mobile = [];
			if ( 'none' === $attr['elementType'] ) {
				$border_style_mobile['.wp-block-uagb-separator:not(.wp-block-uagb-separator--text):not(.wp-block-uagb-separator--icon) .wp-block-uagb-separator__inner'] = $border_css_mobile;

			} else {
				$align_css           = Spec_Gb_Helper::alignment_css( $attr['separatorAlignMobile'] );
				$border_style_mobile = [
					'.wp-block-uagb-separator .wp-block-uagb-separator__inner' => array_merge(
						[
							'width' => Spec_Gb_Helper::get_css_value( $attr['separatorWidthMobile'], $attr['separatorWidthType'] ),

						],
						$align_css
					),
				];
				$border_style_mobile['.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::before'] = $border_css_mobile;
				$border_style_mobile['.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::before'] = $border_css_mobile;
				$border_style_mobile['.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::after']  = $border_css_mobile;
				$border_style_mobile['.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::after']  = $border_css_mobile;
				if ( 'left' === $attr['elementPosition'] ) {
					$icon_spacing_style_mobile['.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'] = [
						'margin-right' => Spec_Gb_Helper::get_css_value( $attr['elementSpacingMobile'], $attr['elementSpacingUnit'] ),

					];
					$border_style_mobile['.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::before'] = [
						'display' => 'none',
					];
					$border_style_mobile['.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::before'] = [
						'display' => 'none',
					];
				}
				if ( 'center' === $attr['elementPosition'] ) {
					$icon_spacing_style_mobile['.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'] = [
						'margin-left'  => Spec_Gb_Helper::get_css_value( $attr['elementSpacingMobile'], $attr['elementSpacingUnit'] ),
						'margin-right' => Spec_Gb_Helper::get_css_value( $attr['elementSpacingMobile'], $attr['elementSpacingUnit'] ),
					];
				}
				if ( 'right' === $attr['elementPosition'] ) {
					$icon_spacing_style_mobile['.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'] = [
						'margin-left' => Spec_Gb_Helper::get_css_value( $attr['elementSpacingMobile'], $attr['elementSpacingUnit'] ),
					];
					$border_style_mobile['.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::after']                           = [
						'display' => 'none',
					];
					$border_style_mobile['.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::after']                           = [
						'display' => 'none',
					];
				}
			}
			$m_selectors = [
				'.wp-block-uagb-separator.uagb-block .wp-block-uagb-separator-wrapper' => [
					'margin-top'     => Spec_Gb_Helper::get_css_value( $attr['blockTopMarginMobile'], $attr['blockMarginUnit'] ),
					'margin-right'   => Spec_Gb_Helper::get_css_value( $attr['blockRightMarginMobile'], $attr['blockMarginUnit'] ),
					'margin-bottom'  => Spec_Gb_Helper::get_css_value( $attr['blockBottomMarginMobile'], $attr['blockMarginUnit'] ),
					'margin-left'    => Spec_Gb_Helper::get_css_value( $attr['blockLeftMarginMobile'], $attr['blockMarginUnit'] ),
					'padding-top'    => Spec_Gb_Helper::get_css_value( $attr['blockTopPaddingMobile'], $attr['blockPaddingUnit'] ),
					'padding-right'  => Spec_Gb_Helper::get_css_value( $attr['blockRightPaddingMobile'], $attr['blockPaddingUnit'] ),
					'padding-bottom' => Spec_Gb_Helper::get_css_value( $attr['blockBottomPaddingMobile'], $attr['blockPaddingUnit'] ),
					'padding-left'   => Spec_Gb_Helper::get_css_value( $attr['blockLeftPaddingMobile'], $attr['blockPaddingUnit'] ),
					'text-align'     => $attr['separatorAlignMobile'],
				],
				'.wp-block-uagb-separator--text .wp-block-uagb-separator-element .uagb-html-tag' => [
					'font-family'     => $attr['elementTextFontFamily'],
					'font-style'      => $attr['elementTextFontStyle'],
					'text-decoration' => $attr['elementTextDecoration'],
					'text-transform'  => $attr['elementTextTransform'],
					'font-weight'     => $attr['elementTextFontWeight'],
					'color'           => $attr['elementColor'],
					'margin-bottom'   => 'initial',
					'font-size'       => Spec_Gb_Helper::get_css_value( $attr['elementTextFontSizeMobile'], $attr['elementTextFontSizeType'] ),
					'line-height'     => Spec_Gb_Helper::get_css_value( $attr['elementTextLineHeightMobile'], $attr['elementTextLineHeightType'] ),
					'letter-spacing'  => Spec_Gb_Helper::get_css_value( $attr['elementTextLetterSpacingMobile'], $attr['elementTextLetterSpacingType'] ),
				],
				'.wp-block-uagb-separator--icon .wp-block-uagb-separator-element svg' => [
					'font-size'   => Spec_Gb_Helper::get_css_value( $attr['elementIconWidthMobile'], $attr['elementIconWidthType'] ),
					'width'       => Spec_Gb_Helper::get_css_value( $attr['elementIconWidthMobile'], $attr['elementIconWidthType'] ),
					'height'      => Spec_Gb_Helper::get_css_value( $attr['elementIconWidthMobile'], $attr['elementIconWidthType'] ),
					'line-height' => Spec_Gb_Helper::get_css_value( $attr['elementIconWidthMobile'], $attr    ['elementIconWidthType'] ),
					'color'       => $attr['elementColor'],
					'fill'        => $attr['elementColor'],
				],
			];
			$m_selectors = array_merge( $m_selectors, $border_style_mobile, $icon_spacing_style_mobile );

			$combined_selectors = [
				'desktop' => $selectors,
				'tablet'  => $t_selectors,
				'mobile'  => $m_selectors,
			];
			return Spec_Gb_Helper::generate_all_css( $combined_selectors, ' .uagb-block-' . $id );
		}

		/**
		 * Border CSS generation Function.
		 *
		 * @since 0.0.1
		 * @param  array  $attr   Attribute List.
		 * @param  string $prefix Attribuate prefix .
		 * @param  string $device Responsive.
		 * @return array         border css array.
		 */
		public static function generate_border_css( $attr, $prefix, $device = 'desktop' ) {
			$gen_border_css = [];
			if ( 'tablet' === $device ) {
				if ( 'none' !== $attr[ $prefix . 'BorderStyle' ] ) {
					$gen_border_css['border-top-width']    = self::get_css_value( $attr[ $prefix . 'BorderTopWidthTablet' ], 'px' );
					$gen_border_css['border-left-width']   = self::get_css_value( $attr[ $prefix . 'BorderLeftWidthTablet' ], 'px' );
					$gen_border_css['border-right-width']  = self::get_css_value( $attr[ $prefix . 'BorderRightWidthTablet' ], 'px' );
					$gen_border_css['border-bottom-width'] = self::get_css_value( $attr[ $prefix . 'BorderBottomWidthTablet' ], 'px' );
				}
				$gen_border_unit_tablet                       = isset( $attr[ $prefix . 'BorderRadiusUnitTablet' ] ) ? $attr[ $prefix . 'BorderRadiusUnitTablet' ] : 'px';
				$gen_border_css['border-top-left-radius']     = self::get_css_value( $attr[ $prefix . 'BorderTopLeftRadiusTablet' ], $gen_border_unit_tablet );
				$gen_border_css['border-top-right-radius']    = self::get_css_value( $attr[ $prefix . 'BorderTopRightRadiusTablet' ], $gen_border_unit_tablet );
				$gen_border_css['border-bottom-left-radius']  = self::get_css_value( $attr[ $prefix . 'BorderBottomLeftRadiusTablet' ], $gen_border_unit_tablet );
				$gen_border_css['border-bottom-right-radius'] = self::get_css_value( $attr[ $prefix . 'BorderBottomRightRadiusTablet' ], $gen_border_unit_tablet );
			} elseif ( 'mobile' === $device ) {
				if ( 'none' !== $attr[ $prefix . 'BorderStyle' ] ) {
					$gen_border_css['border-top-width']    = self::get_css_value( $attr[ $prefix . 'BorderTopWidthMobile' ], 'px' );
					$gen_border_css['border-left-width']   = self::get_css_value( $attr[ $prefix . 'BorderLeftWidthMobile' ], 'px' );
					$gen_border_css['border-right-width']  = self::get_css_value( $attr[ $prefix . 'BorderRightWidthMobile' ], 'px' );
					$gen_border_css['border-bottom-width'] = self::get_css_value( $attr[ $prefix . 'BorderBottomWidthMobile' ], 'px' );
				}
				$gen_border_unit_mobile                       = isset( $attr[ $prefix . 'BorderTopLeftRadiusMobile' ] ) ? $attr[ $prefix . 'BorderTopLeftRadiusMobile' ] : 'px';
				$gen_border_css['border-top-left-radius']     = self::get_css_value( $attr[ $prefix . 'BorderTopLeftRadiusMobile' ], $gen_border_unit_mobile );
				$gen_border_css['border-top-right-radius']    = self::get_css_value( $attr[ $prefix . 'BorderTopRightRadiusMobile' ], $gen_border_unit_mobile );
				$gen_border_css['border-bottom-left-radius']  = self::get_css_value( $attr[ $prefix . 'BorderBottomLeftRadiusMobile' ], $gen_border_unit_mobile );
				$gen_border_css['border-bottom-right-radius'] = self::get_css_value( $attr[ $prefix . 'BorderBottomRightRadiusMobile' ], $gen_border_unit_mobile );
			} else {
				if ( 'none' !== $attr[ $prefix . 'BorderStyle' ] ) {
					$gen_border_css['border-top-width']    = self::get_css_value( $attr[ $prefix . 'BorderTopWidth' ], 'px' );
					$gen_border_css['border-left-width']   = self::get_css_value( $attr[ $prefix . 'BorderLeftWidth' ], 'px' );
					$gen_border_css['border-right-width']  = self::get_css_value( $attr[ $prefix . 'BorderRightWidth' ], 'px' );
					$gen_border_css['border-bottom-width'] = self::get_css_value( $attr[ $prefix . 'BorderBottomWidth' ], 'px' );
				}
				$gen_border_unit                              = isset( $attr[ $prefix . 'BorderRadiusUnit' ] ) ? $attr[ $prefix . 'BorderRadiusUnit' ] : 'px';
				$gen_border_css['border-top-left-radius']     = self::get_css_value( $attr[ $prefix . 'BorderTopLeftRadius' ], $gen_border_unit );
				$gen_border_css['border-top-right-radius']    = self::get_css_value( $attr[ $prefix . 'BorderTopRightRadius' ], $gen_border_unit );
				$gen_border_css['border-bottom-left-radius']  = self::get_css_value( $attr[ $prefix . 'BorderBottomLeftRadius' ], $gen_border_unit );
				$gen_border_css['border-bottom-right-radius'] = self::get_css_value( $attr[ $prefix . 'BorderBottomRightRadius' ], $gen_border_unit );
			}
			$border_style                   = $attr[ $prefix . 'BorderStyle' ];
			$border_color                   = $attr[ $prefix . 'BorderColor' ];
			$gen_border_css['border-style'] = $border_style;
			$gen_border_css['border-color'] = $border_color;
			return $gen_border_css;
		}

		/**
		 * Get CSS value
		 *
		 * Syntax:
		 *
		 *  get_css_value( VALUE, UNIT );
		 *
		 * E.g.
		 *
		 *  get_css_value( VALUE, 'em' );
		 *
		 * @param string $value  CSS value.
		 * @param string $unit  CSS unit.
		 * @since 0.0.1
		 */
		public static function get_css_value( $value = '', $unit = '' ) {

			if ( '' === $value ) {
				return $value;
			}

			$css_val = '';

			if ( isset( $value ) ) {
				$css_val = esc_attr( $value ) . $unit;
			}

			return $css_val;
		}

		/**
		 * Deprecated Border CSS generation Function.
		 *
		 * @since 0.0.1
		 * @param  array  $current_css   Current style list.
		 * @param  string $border_width   Border Width.
		 * @param  string $border_radius Border Radius.
		 * @param  string $border_color Border Color.
		 * @param string $border_style Border Style.
		 */
		public static function generate_deprecated_border_css( $current_css, $border_width, $border_radius, $border_color = '', $border_style = '' ) {
			$gen_border_css = [];
			if ( is_numeric( $border_width ) ) {
				$gen_border_css['border-top-width']    = self::get_css_value( $border_width, 'px' );
				$gen_border_css['border-left-width']   = self::get_css_value( $border_width, 'px' );
				$gen_border_css['border-right-width']  = self::get_css_value( $border_width, 'px' );
				$gen_border_css['border-bottom-width'] = self::get_css_value( $border_width, 'px' );
			}

			if ( is_numeric( $border_radius ) ) {
				$gen_border_css['border-top-left-radius']     = self::get_css_value( $border_radius, 'px' );
				$gen_border_css['border-top-right-radius']    = self::get_css_value( $border_radius, 'px' );
				$gen_border_css['border-bottom-left-radius']  = self::get_css_value( $border_radius, 'px' );
				$gen_border_css['border-bottom-right-radius'] = self::get_css_value( $border_radius, 'px' );
			}

			if ( $border_color ) {
				$gen_border_css['border-color'] = $border_color;
			}

			if ( $border_style ) {
				$gen_border_css['border-style'] = $border_style;
			}
			return wp_parse_args( $gen_border_css, $current_css );
		}

	}
}
