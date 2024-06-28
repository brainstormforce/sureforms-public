<?php
/**
 * Sureforms - Image
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Advanced_Image' ) ) {

	/**
	 * Class Advanced_Image.
	 */
	class Advanced_Image {

		/**
		 * Member Variable
		 *
		 * @var Advanced_Image|null
		 */
		private static $instance;

		/**
		 *  Initiator
		 *
		 * @return Advanced_Image The instance of the Advanced_Image class.
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
			add_action( 'init', [ $this, 'register_blocks' ] );
		}

		/**
		 * Registers the `sureforms/image` block on server.
		 *
		 * @since 0.0.1
		 *
		 * @return void
		 */
		public function register_blocks() {
			// Check if the register function exists.
			if ( ! function_exists( 'register_block_type' ) ) {
				return;
			}

			$attr = [
				'block_id'                    => [
					'type' => 'string',
				],
				'isPreview'                   => [
					'type'    => 'boolean',
					'default' => false,
				],
				'layout'                      => [
					'type'    => 'string',
					'default' => 'default',
				],
				'url'                         => [
					'type'    => 'string',
					'default' => '',
				],
				'urlTablet'                   => [
					'type'    => 'string',
					'default' => '',
				],
				'urlMobile'                   => [
					'type'    => 'string',
					'default' => '',
				],
				'alt'                         => [
					'type'    => 'string',
					'default' => '',
				],
				'enableCaption'               => [
					'type'    => 'boolean',
					'default' => false,
				],
				'caption'                     => [
					'type'    => 'string',
					'default' => '',
				],
				'align'                       => [
					'type'    => 'string',
					'default' => '',
				],
				'alignTablet'                 => [
					'type'    => 'string',
					'default' => '',
				],
				'alignMobile'                 => [
					'type'    => 'string',
					'default' => '',
				],
				'id'                          => [
					'type'    => 'integer',
					'default' => '',
				],
				'href'                        => [
					'type'    => 'string',
					'default' => '',
				],
				'rel'                         => [
					'type'    => 'string',
					'default' => '',
				],
				'linkClass'                   => [
					'type'    => 'string',
					'default' => '',
				],
				'linkDestination'             => [
					'type'    => 'string',
					'default' => '',
				],
				'title'                       => [
					'type'    => 'string',
					'default' => '',
				],
				'width'                       => [
					'type'    => 'integer',
					'default' => '',
				],
				'widthTablet'                 => [
					'type'    => 'integer',
					'default' => '',
				],
				'widthMobile'                 => [
					'type'    => 'integer',
					'default' => '',
				],
				'height'                      => [
					'type'    => 'integer',
					'default' => '',
				],
				'heightTablet'                => [
					'type'    => 'integer',
					'default' => '',
				],
				'heightMobile'                => [
					'type'    => 'integer',
					'default' => '',
				],
				'naturalWidth'                => [
					'type'    => 'integer',
					'default' => '',
				],
				'naturalHeight'               => [
					'type'    => 'integer',
					'default' => '',
				],
				'linkTarget'                  => [
					'type'    => 'string',
					'default' => '',
				],
				'sizeSlug'                    => [
					'type'    => 'string',
					'default' => '',
				],
				'sizeSlugTablet'              => [
					'type'    => 'string',
					'default' => '',
				],
				'sizeSlugMobile'              => [
					'type'    => 'string',
					'default' => '',
				],
				'imageTopMargin'              => [
					'type' => 'number',
				],
				'imageRightMargin'            => [
					'type' => 'number',
				],
				'imageLeftMargin'             => [
					'type' => 'number',
				],
				'imageBottomMargin'           => [
					'type' => 'number',
				],
				'imageTopMarginTablet'        => [
					'type' => 'number',
				],
				'imageRightMarginTablet'      => [
					'type' => 'number',
				],
				'imageLeftMarginTablet'       => [
					'type' => 'number',
				],
				'imageBottomMarginTablet'     => [
					'type' => 'number',
				],
				'imageTopMarginMobile'        => [
					'type' => 'number',
				],
				'imageRightMarginMobile'      => [
					'type' => 'number',
				],
				'imageLeftMarginMobile'       => [
					'type' => 'number',
				],
				'imageBottomMarginMobile'     => [
					'type' => 'number',
				],
				'imageMarginUnit'             => [
					'type'    => 'string',
					'default' => 'px',
				],
				'imageMarginUnitTablet'       => [
					'type'    => 'string',
					'default' => 'px',
				],
				'imageMarginUnitMobile'       => [
					'type'    => 'string',
					'default' => 'px',
				],
				'imageMarginLink'             => [
					'type'    => 'boolean',
					'default' => true,
				],
				'captionText'                 => [
					'type'    => 'string',
					'default' => '',
				],
				'captionShowOn'               => [
					'type'    => 'string',
					'default' => 'hover',
				],
				'captionLoadGoogleFonts'      => [
					'type'    => 'boolean',
					'default' => false,
				],
				'captionColor'                => [
					'type' => 'string',
				],
				'captionAlign'                => [
					'type'    => 'string',
					'default' => 'center',
				],
				'captionFontFamily'           => [
					'type'    => 'string',
					'default' => 'Default',
				],
				'captionFontWeight'           => [
					'type' => 'string',
				],
				'captionFontStyle'            => [
					'type'    => 'string',
					'default' => 'normal',
				],
				'captionTransform'            => [
					'type' => 'string',
				],
				'captionDecoration'           => [
					'type' => 'string',
				],
				'captionFontSizeType'         => [
					'type'    => 'string',
					'default' => 'px',
				],
				'captionFontSizeTypeTablet'   => [
					'type'    => 'string',
					'default' => 'px',
				],
				'captionFontSizeTypeMobile'   => [
					'type'    => 'string',
					'default' => 'px',
				],
				'captionLineHeightType'       => [
					'type'    => 'string',
					'default' => 'em',
				],
				'captionFontSize'             => [
					'type' => 'number',
				],
				'captionFontSizeTablet'       => [
					'type' => 'number',
				],
				'captionFontSizeMobile'       => [
					'type' => 'number',
				],
				'captionLineHeight'           => [
					'type' => 'number',
				],
				'captionLineHeightTablet'     => [
					'type' => 'number',
				],
				'captionLineHeightMobile'     => [
					'type' => 'number',
				],
				'captionTopMargin'            => [
					'type' => 'number',
				],
				'captionRightMargin'          => [
					'type' => 'number',
				],
				'captionLeftMargin'           => [
					'type' => 'number',
				],
				'captionBottomMargin'         => [
					'type' => 'number',
				],
				'captionTopMarginTablet'      => [
					'type' => 'number',
				],
				'captionRightMarginTablet'    => [
					'type' => 'number',
				],
				'captionLeftMarginTablet'     => [
					'type' => 'number',
				],
				'captionBottomMarginTablet'   => [
					'type' => 'number',
				],
				'captionTopMarginMobile'      => [
					'type' => 'number',
				],
				'captionRightMarginMobile'    => [
					'type' => 'number',
				],
				'captionLeftMarginMobile'     => [
					'type' => 'number',
				],
				'captionBottomMarginMobile'   => [
					'type' => 'number',
				],
				'captionMarginUnit'           => [
					'type'    => 'string',
					'default' => 'px',
				],
				'captionMarginUnitTablet'     => [
					'type'    => 'string',
					'default' => 'px',
				],
				'captionMarginUnitMobile'     => [
					'type'    => 'string',
					'default' => 'px',
				],
				'captionMarginLink'           => [
					'type'    => 'boolean',
					'default' => false,
				],
				// heading.
				'heading'                     => [
					'type'    => 'string',
					'default' => '',
				],
				'headingShowOn'               => [
					'type'    => 'string',
					'default' => 'always',
				],
				'headingTag'                  => [
					'type'    => 'string',
					'default' => 'h2',
				],
				'headingId'                   => [
					'type' => 'string',
				],
				'headingLoadGoogleFonts'      => [
					'type'    => 'boolean',
					'default' => false,
				],
				'headingColor'                => [
					'type'    => 'string',
					'default' => '#fff',
				],
				'headingFontFamily'           => [
					'type'    => 'string',
					'default' => 'Default',
				],
				'headingFontWeight'           => [
					'type' => 'string',
				],
				'headingFontStyle'            => [
					'type'    => 'string',
					'default' => 'normal',
				],
				'headingTransform'            => [
					'type' => 'string',
				],
				'headingDecoration'           => [
					'type' => 'string',
				],
				'headingFontSizeType'         => [
					'type'    => 'string',
					'default' => 'px',
				],
				'headingFontSizeTypeTablet'   => [
					'type'    => 'string',
					'default' => 'px',
				],
				'headingFontSizeTypeMobile'   => [
					'type'    => 'string',
					'default' => 'px',
				],
				'headingLineHeightType'       => [
					'type'    => 'string',
					'default' => 'em',
				],
				'headingFontSize'             => [
					'type' => 'number',
				],
				'headingFontSizeTablet'       => [
					'type' => 'number',
				],
				'headingFontSizeMobile'       => [
					'type' => 'number',
				],
				'headingLineHeight'           => [
					'type' => 'number',
				],
				'headingLineHeightTablet'     => [
					'type' => 'number',
				],
				'headingLineHeightMobile'     => [
					'type' => 'number',
				],
				'headingTopMargin'            => [
					'type' => 'number',
				],
				'headingRightMargin'          => [
					'type' => 'number',
				],
				'headingLeftMargin'           => [
					'type' => 'number',
				],
				'headingBottomMargin'         => [
					'type' => 'number',
				],
				'headingTopMarginTablet'      => [
					'type' => 'number',
				],
				'headingRightMarginTablet'    => [
					'type' => 'number',
				],
				'headingLeftMarginTablet'     => [
					'type' => 'number',
				],
				'headingBottomMarginTablet'   => [
					'type' => 'number',
				],
				'headingTopMarginMobile'      => [
					'type' => 'number',
				],
				'headingRightMarginMobile'    => [
					'type' => 'number',
				],
				'headingLeftMarginMobile'     => [
					'type' => 'number',
				],
				'headingBottomMarginMobile'   => [
					'type' => 'number',
				],
				'headingMarginUnit'           => [
					'type'    => 'string',
					'default' => 'px',
				],
				'headingMarginUnitTablet'     => [
					'type'    => 'string',
					'default' => 'px',
				],
				'headingMarginUnitMobile'     => [
					'type'    => 'string',
					'default' => 'px',
				],
				'headingMarginLink'           => [
					'type'    => 'boolean',
					'default' => false,
				],
				// overlay.
				'overlayPositionFromEdge'     => [
					'type'    => 'number',
					'default' => 15,
				],
				'overlayPositionFromEdgeUnit' => [
					'type'    => 'string',
					'default' => 'px',
				],
				'overlayContentPosition'      => [
					'type'    => 'string',
					'default' => 'center center',
				],
				'overlayBackground'           => [
					'type'    => 'string',
					'default' => '',
				],
				'overlayOpacity'              => [
					'type'    => 'float',
					'default' => 0.2,
				],
				'overlayHoverOpacity'         => [
					'type'    => 'number',
					'default' => 1,
				],
				// separator.
				'separatorShowOn'             => [
					'type'    => 'string',
					'default' => 'hover',
				],
				'separatorStyle'              => [
					'type'    => 'string',
					'default' => 'none',
				],
				'separatorColor'              => [
					'type'    => 'string',
					'default' => '#fff',
				],
				'separatorPosition'           => [
					'type'    => 'string',
					'default' => 'after_title',
				],
				'separatorWidth'              => [
					'type'    => 'number',
					'default' => 30,
				],
				'separatorWidthType'          => [
					'type'    => 'string',
					'default' => '%',
				],
				'separatorThickness'          => [
					'type'    => 'number',
					'default' => 2,
				],
				'separatorThicknessUnit'      => [
					'type'    => 'string',
					'default' => 'px',
				],
				'separatorTopMargin'          => [
					'type' => 'number',
				],
				'separatorRightMargin'        => [
					'type' => 'number',
				],
				'separatorLeftMargin'         => [
					'type' => 'number',
				],
				'separatorBottomMargin'       => [
					'type' => 'number',
				],
				'separatorTopMarginTablet'    => [
					'type' => 'number',
				],
				'separatorRightMarginTablet'  => [
					'type' => 'number',
				],
				'separatorLeftMarginTablet'   => [
					'type' => 'number',
				],
				'separatorBottomMarginTablet' => [
					'type' => 'number',
				],
				'separatorTopMarginMobile'    => [
					'type' => 'number',
				],
				'separatorRightMarginMobile'  => [
					'type' => 'number',
				],
				'separatorLeftMarginMobile'   => [
					'type' => 'number',
				],
				'separatorBottomMarginMobile' => [
					'type' => 'number',
				],
				'separatorMarginUnit'         => [
					'type'    => 'string',
					'default' => 'px',
				],
				'separatorMarginUnitTablet'   => [
					'type'    => 'string',
					'default' => 'px',
				],
				'separatorMarginUnitMobile'   => [
					'type'    => 'string',
					'default' => 'px',
				],
				'separatorMarginLink'         => [
					'type'    => 'boolean',
					'default' => false,
				],
				// effect.
				'imageHoverEffect'            => [
					'type'    => 'string',
					'default' => 'static',
				],
				'objectFit'                   => [
					'type' => 'string',
				],
				'objectFitTablet'             => [
					'type' => 'string',
				],
				'objectFitMobile'             => [
					'type' => 'string',
				],
				'useSeparateBoxShadows'       => [
					'type'    => 'boolean',
					'default' => true,
				],
				'imageBoxShadowColor'         => [
					'type'    => 'string',
					'default' => '#00000070',
				],
				'imageBoxShadowHOffset'       => [
					'type'    => 'number',
					'default' => 0,
				],
				'imageBoxShadowVOffset'       => [
					'type'    => 'number',
					'default' => 0,
				],
				'imageBoxShadowBlur'          => [
					'type' => 'number',
				],
				'imageBoxShadowSpread'        => [
					'type' => 'number',
				],
				'imageBoxShadowPosition'      => [
					'type'    => 'string',
					'default' => 'outset',
				],
				'imageBoxShadowColorHover'    => [
					'type' => 'string',
				],
				'imageBoxShadowHOffsetHover'  => [
					'type'    => 'number',
					'default' => 0,
				],
				'imageBoxShadowVOffsetHover'  => [
					'type'    => 'number',
					'default' => 0,
				],
				'imageBoxShadowBlurHover'     => [
					'type' => 'number',
				],
				'imageBoxShadowSpreadHover'   => [
					'type' => 'number',
				],
				'imageBoxShadowPositionHover' => [
					'type'    => 'string',
					'default' => 'outset',
				],
				// mask.
				'maskShape'                   => [
					'type'    => 'string',
					'default' => 'none',
				],
				'maskCustomShape'             => [
					'type'    => 'object',
					'default' => [
						'url' => '',
						'alt' => 'mask shape',
					],
				],
				'maskSize'                    => [
					'type'    => 'string',
					'default' => 'auto',
				],
				'maskPosition'                => [
					'type'    => 'string',
					'default' => 'center center',
				],
				'maskRepeat'                  => [
					'type'    => 'string',
					'default' => 'no-repeat',
				],
				'headingLetterSpacing'        => [
					'type' => 'number',
				],
				'headingLetterSpacingTablet'  => [
					'type' => 'number',
				],
				'headingLetterSpacingMobile'  => [
					'type' => 'number',
				],
				'headingLetterSpacingType'    => [
					'type'    => 'string',
					'default' => 'px',
				],
				'captionLetterSpacing'        => [
					'type' => 'number',
				],
				'captionLetterSpacingTablet'  => [
					'type' => 'number',
				],
				'captionLetterSpacingMobile'  => [
					'type' => 'number',
				],
				'captionLetterSpacingType'    => [
					'type'    => 'string',
					'default' => 'px',
				],
				'customHeightSetDesktop'      => [
					'type'    => 'boolean',
					'default' => false,
				],
				'customHeightSetTablet'       => [
					'type'    => 'boolean',
					'default' => false,
				],
				'customHeightSetMobile'       => [
					'type'    => 'boolean',
					'default' => false,
				],
				'disableLazyLoad'             => [
					'type'    => 'boolean',
					'default' => false,
				],
			];

			$advaned_image_border_attr         = Spec_Gb_Helper::get_instance()->generate_php_border_attribute( 'image' );
			$advaned_image_border_overlay_attr = Spec_Gb_Helper::get_instance()->generate_php_border_attribute( 'overlay' );

			$attr = array_merge( $advaned_image_border_attr, $advaned_image_border_overlay_attr, $attr );

			$attributes = apply_filters( 'srfm_gutenberg_image_attributes_filters', $attr );

			register_block_type(
				'srfm/image',
				[
					'attributes'      => $attributes,
					'render_callback' => [ $this, 'render_html' ],
				]
			);
		}

		/**
		 * Render CF HTML.
		 *
		 * @param array<mixed> $attributes Array of block attributes.
		 *
		 * @since 0.0.1
		 *
		 * @return string|false
		 */
		public function render_html( $attributes ) {

			$block_id = '';
			if ( isset( $attributes['block_id'] ) ) {
				$block_id = $attributes['block_id'];
			}

			$image = '<img
            srcset="' . esc_url( $attributes['url'] ) . ( $attributes['urlTablet'] ? ', ' . esc_url( $attributes['urlTablet'] ) . ' 780w' : '' )
			. ( $attributes['urlMobile'] ? ', ' . esc_url( $attributes['urlMobile'] ) . ' 360w' : '' ) . '"
            sizes="(max-width: 480px) 150px"
            src="' . esc_url( $attributes['url'] ) . '"
            alt="' . esc_attr( $attributes['alt'] ) . '"';

			if ( $attributes['id'] ) {
				$image .= ' class="uag-image-' . esc_attr( $attributes['id'] ) . '"';
			}

			$image .= ' width="' . ( $attributes['width'] ? esc_attr( $attributes['width'] ) : esc_attr( $attributes['naturalWidth'] ) )
				. '" height="' . ( $attributes['height'] ? esc_attr( $attributes['height'] ) : esc_attr( $attributes['naturalHeight'] ) ) . '"
                title="' . esc_attr( $attributes['title'] ) . '"
                loading="' . ( empty( $attributes['disableLazyLoad'] ) ? 'lazy' : 'eager' ) .
			'" />';

			$figure_image = '';
			if ( $attributes['href'] && '' !== $attributes['href'] ) {
				$figure_image = '<a
                            class="' . esc_attr( $attributes['linkClass'] ) . '"
                            href="' . esc_url( $attributes['href'] ) . '"
                            target="' . esc_attr( $attributes['linkTarget'] ) . '"
                            rel="' . $this->get_rel( $attributes['rel'] ) . '">
                            ' . $image . '
                        </a>';
			} else {
				$figure_image = $image;
			}

			$image_heading = '';

			if ( ! empty( $attributes['heading'] ) ) {

				$heading_id    = isset( $attributes['headingId'] ) ? ' id="' . $attributes['headingId'] . '"' : '';
				$image_heading = sprintf(
					'<%1$s%2$s class="uagb-image-heading">%3$s</%1$s>',
					esc_html( $attributes['headingTag'] ),
					esc_attr( $heading_id ),
					esc_html( $attributes['heading'] )
				);
			}

			$image_caption = '';

			if ( ! empty( $attributes['caption'] ) ) {
				$image_caption = sprintf(
					'<figcaption class="uagb-image-caption">%s</figcaption>',
					esc_html( $attributes['caption'] )
				);
			}

			$separator = 'none' !== $attributes['separatorStyle'] ? '<div class="uagb-image-separator"></div>' : '';

			$image_overlay_link = sprintf(
				'<a class="wp-block-uagb-image--layout-overlay-link %1$s" href="%2$s" target="%3$s" rel="%4$s"></a>',
				esc_attr( $attributes['linkClass'] ),
				esc_url( $attributes['href'] ),
				esc_attr( $attributes['linkTarget'] ),
				$this->get_rel( $attributes['rel'] )
			);

			$main_classes = [
				'wp-block-uagb-image',
				'uagb-block',
				'uagb-block-' . $block_id,
				'wp-block-uagb-image--layout-' . esc_attr( $attributes['layout'] ),
				'wp-block-uagb-image--effect-' . esc_attr( $attributes['imageHoverEffect'] ),
				'wp-block-uagb-image--align-' . ( $attributes['align'] ? esc_attr( $attributes['align'] ) : 'none' ),
			];

			if ( isset( $attributes['className'] ) ) {
				$main_classes[] = $attributes['className'];
			}

			ob_start();
			?>
				<div class="<?php echo esc_attr( implode( ' ', $main_classes ) ); ?>">
					<figure class="wp-block-uagb-image__figure">
						<?php echo wp_kses( $figure_image, 'post' ); ?>

						<?php if ( 'overlay' === $attributes['layout'] ) : ?>
							<div class="wp-block-uagb-image--layout-overlay__color-wrapper"></div>
							<div class="wp-block-uagb-image--layout-overlay__inner <?php echo esc_attr( str_replace( ' ', '-', $attributes['overlayContentPosition'] ) ); ?>">
								<?php echo wp_kses( $image_overlay_link, 'post' ); ?>

								<?php
								if ( 'before_title' === $attributes['separatorPosition'] ) {
									echo wp_kses( $separator, 'post' );}
								?>
								<?php echo wp_kses( $image_heading, 'post' ); ?>
								<?php
								if ( 'after_title' === $attributes['separatorPosition'] ) {
									echo wp_kses( $separator, 'post' );}
								?>
								<?php echo wp_kses( $image_caption, 'post' ); ?>
								<?php
								if ( 'after_sub_title' === $attributes['separatorPosition'] ) {
									echo wp_kses( $separator, 'post' );}
								?>
							</div>
						<?php else : ?>
							<?php
							if ( $attributes['enableCaption'] ) {
								echo wp_kses( $image_caption, 'post' );}
							?>
						<?php endif; ?>
					</figure>
				</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Utility function to get link relation.
		 *
		 * @param string|false $rel stored in block attributes.
		 *
		 * @since 0.0.1
		 *
		 * @return string
		 */
		public function get_rel( $rel ) {
			if ( $rel ) {
				return esc_attr( trim( $rel ) );
			}
			return 'noopener';
		}

	}

	/**
	 *  Prepare if class 'Advanced_Image' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	Advanced_Image::get_instance();
}
