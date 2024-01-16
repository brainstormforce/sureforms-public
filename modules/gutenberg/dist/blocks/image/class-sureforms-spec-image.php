<?php
/**
 * Sureforms - Image
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Sureforms_Advanced_Image' ) ) {

	/**
	 * Class Sureforms_Advanced_Image.
	 */
	class Sureforms_Advanced_Image {

		/**
		 * Member Variable
		 *
		 * @var Sureforms_Advanced_Image|null
		 */
		private static $instance;

		/**
		 *  Initiator
		 *
		 * @return Sureforms_Advanced_Image The instance of the Sureforms_Advanced_Image class.
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

			$attr = array(
				'block_id'                    => array(
					'type' => 'string',
				),
				'isPreview'                   => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'layout'                      => array(
					'type'    => 'string',
					'default' => 'default',
				),
				'url'                         => array(
					'type'    => 'string',
					'default' => '',
				),
				'urlTablet'                   => array(
					'type'    => 'string',
					'default' => '',
				),
				'urlMobile'                   => array(
					'type'    => 'string',
					'default' => '',
				),
				'alt'                         => array(
					'type'    => 'string',
					'default' => '',
				),
				'enableCaption'               => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'caption'                     => array(
					'type'    => 'string',
					'default' => '',
				),
				'align'                       => array(
					'type'    => 'string',
					'default' => '',
				),
				'alignTablet'                 => array(
					'type'    => 'string',
					'default' => '',
				),
				'alignMobile'                 => array(
					'type'    => 'string',
					'default' => '',
				),
				'id'                          => array(
					'type'    => 'integer',
					'default' => '',
				),
				'href'                        => array(
					'type'    => 'string',
					'default' => '',
				),
				'rel'                         => array(
					'type'    => 'string',
					'default' => '',
				),
				'linkClass'                   => array(
					'type'    => 'string',
					'default' => '',
				),
				'linkDestination'             => array(
					'type'    => 'string',
					'default' => '',
				),
				'title'                       => array(
					'type'    => 'string',
					'default' => '',
				),
				'width'                       => array(
					'type'    => 'integer',
					'default' => '',
				),
				'widthTablet'                 => array(
					'type'    => 'integer',
					'default' => '',
				),
				'widthMobile'                 => array(
					'type'    => 'integer',
					'default' => '',
				),
				'height'                      => array(
					'type'    => 'integer',
					'default' => '',
				),
				'heightTablet'                => array(
					'type'    => 'integer',
					'default' => '',
				),
				'heightMobile'                => array(
					'type'    => 'integer',
					'default' => '',
				),
				'naturalWidth'                => array(
					'type'    => 'integer',
					'default' => '',
				),
				'naturalHeight'               => array(
					'type'    => 'integer',
					'default' => '',
				),
				'linkTarget'                  => array(
					'type'    => 'string',
					'default' => '',
				),
				'sizeSlug'                    => array(
					'type'    => 'string',
					'default' => '',
				),
				'sizeSlugTablet'              => array(
					'type'    => 'string',
					'default' => '',
				),
				'sizeSlugMobile'              => array(
					'type'    => 'string',
					'default' => '',
				),
				'imageTopMargin'              => array(
					'type' => 'number',
				),
				'imageRightMargin'            => array(
					'type' => 'number',
				),
				'imageLeftMargin'             => array(
					'type' => 'number',
				),
				'imageBottomMargin'           => array(
					'type' => 'number',
				),
				'imageTopMarginTablet'        => array(
					'type' => 'number',
				),
				'imageRightMarginTablet'      => array(
					'type' => 'number',
				),
				'imageLeftMarginTablet'       => array(
					'type' => 'number',
				),
				'imageBottomMarginTablet'     => array(
					'type' => 'number',
				),
				'imageTopMarginMobile'        => array(
					'type' => 'number',
				),
				'imageRightMarginMobile'      => array(
					'type' => 'number',
				),
				'imageLeftMarginMobile'       => array(
					'type' => 'number',
				),
				'imageBottomMarginMobile'     => array(
					'type' => 'number',
				),
				'imageMarginUnit'             => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'imageMarginUnitTablet'       => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'imageMarginUnitMobile'       => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'imageMarginLink'             => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'captionText'                 => array(
					'type'    => 'string',
					'default' => '',
				),
				'captionShowOn'               => array(
					'type'    => 'string',
					'default' => 'hover',
				),
				'captionLoadGoogleFonts'      => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'captionColor'                => array(
					'type' => 'string',
				),
				'captionAlign'                => array(
					'type'    => 'string',
					'default' => 'center',
				),
				'captionFontFamily'           => array(
					'type'    => 'string',
					'default' => 'Default',
				),
				'captionFontWeight'           => array(
					'type' => 'string',
				),
				'captionFontStyle'            => array(
					'type'    => 'string',
					'default' => 'normal',
				),
				'captionTransform'            => array(
					'type' => 'string',
				),
				'captionDecoration'           => array(
					'type' => 'string',
				),
				'captionFontSizeType'         => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'captionFontSizeTypeTablet'   => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'captionFontSizeTypeMobile'   => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'captionLineHeightType'       => array(
					'type'    => 'string',
					'default' => 'em',
				),
				'captionFontSize'             => array(
					'type' => 'number',
				),
				'captionFontSizeTablet'       => array(
					'type' => 'number',
				),
				'captionFontSizeMobile'       => array(
					'type' => 'number',
				),
				'captionLineHeight'           => array(
					'type' => 'number',
				),
				'captionLineHeightTablet'     => array(
					'type' => 'number',
				),
				'captionLineHeightMobile'     => array(
					'type' => 'number',
				),
				'captionTopMargin'            => array(
					'type' => 'number',
				),
				'captionRightMargin'          => array(
					'type' => 'number',
				),
				'captionLeftMargin'           => array(
					'type' => 'number',
				),
				'captionBottomMargin'         => array(
					'type' => 'number',
				),
				'captionTopMarginTablet'      => array(
					'type' => 'number',
				),
				'captionRightMarginTablet'    => array(
					'type' => 'number',
				),
				'captionLeftMarginTablet'     => array(
					'type' => 'number',
				),
				'captionBottomMarginTablet'   => array(
					'type' => 'number',
				),
				'captionTopMarginMobile'      => array(
					'type' => 'number',
				),
				'captionRightMarginMobile'    => array(
					'type' => 'number',
				),
				'captionLeftMarginMobile'     => array(
					'type' => 'number',
				),
				'captionBottomMarginMobile'   => array(
					'type' => 'number',
				),
				'captionMarginUnit'           => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'captionMarginUnitTablet'     => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'captionMarginUnitMobile'     => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'captionMarginLink'           => array(
					'type'    => 'boolean',
					'default' => false,
				),
				// heading.
				'heading'                     => array(
					'type'    => 'string',
					'default' => '',
				),
				'headingShowOn'               => array(
					'type'    => 'string',
					'default' => 'always',
				),
				'headingTag'                  => array(
					'type'    => 'string',
					'default' => 'h2',
				),
				'headingId'                   => array(
					'type' => 'string',
				),
				'headingLoadGoogleFonts'      => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'headingColor'                => array(
					'type'    => 'string',
					'default' => '#fff',
				),
				'headingFontFamily'           => array(
					'type'    => 'string',
					'default' => 'Default',
				),
				'headingFontWeight'           => array(
					'type' => 'string',
				),
				'headingFontStyle'            => array(
					'type'    => 'string',
					'default' => 'normal',
				),
				'headingTransform'            => array(
					'type' => 'string',
				),
				'headingDecoration'           => array(
					'type' => 'string',
				),
				'headingFontSizeType'         => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'headingFontSizeTypeTablet'   => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'headingFontSizeTypeMobile'   => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'headingLineHeightType'       => array(
					'type'    => 'string',
					'default' => 'em',
				),
				'headingFontSize'             => array(
					'type' => 'number',
				),
				'headingFontSizeTablet'       => array(
					'type' => 'number',
				),
				'headingFontSizeMobile'       => array(
					'type' => 'number',
				),
				'headingLineHeight'           => array(
					'type' => 'number',
				),
				'headingLineHeightTablet'     => array(
					'type' => 'number',
				),
				'headingLineHeightMobile'     => array(
					'type' => 'number',
				),
				'headingTopMargin'            => array(
					'type' => 'number',
				),
				'headingRightMargin'          => array(
					'type' => 'number',
				),
				'headingLeftMargin'           => array(
					'type' => 'number',
				),
				'headingBottomMargin'         => array(
					'type' => 'number',
				),
				'headingTopMarginTablet'      => array(
					'type' => 'number',
				),
				'headingRightMarginTablet'    => array(
					'type' => 'number',
				),
				'headingLeftMarginTablet'     => array(
					'type' => 'number',
				),
				'headingBottomMarginTablet'   => array(
					'type' => 'number',
				),
				'headingTopMarginMobile'      => array(
					'type' => 'number',
				),
				'headingRightMarginMobile'    => array(
					'type' => 'number',
				),
				'headingLeftMarginMobile'     => array(
					'type' => 'number',
				),
				'headingBottomMarginMobile'   => array(
					'type' => 'number',
				),
				'headingMarginUnit'           => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'headingMarginUnitTablet'     => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'headingMarginUnitMobile'     => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'headingMarginLink'           => array(
					'type'    => 'boolean',
					'default' => false,
				),
				// overlay.
				'overlayPositionFromEdge'     => array(
					'type'    => 'number',
					'default' => 15,
				),
				'overlayPositionFromEdgeUnit' => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'overlayContentPosition'      => array(
					'type'    => 'string',
					'default' => 'center center',
				),
				'overlayBackground'           => array(
					'type'    => 'string',
					'default' => '',
				),
				'overlayOpacity'              => array(
					'type'    => 'float',
					'default' => 0.2,
				),
				'overlayHoverOpacity'         => array(
					'type'    => 'number',
					'default' => 1,
				),
				// separator.
				'separatorShowOn'             => array(
					'type'    => 'string',
					'default' => 'hover',
				),
				'separatorStyle'              => array(
					'type'    => 'string',
					'default' => 'none',
				),
				'separatorColor'              => array(
					'type'    => 'string',
					'default' => '#fff',
				),
				'separatorPosition'           => array(
					'type'    => 'string',
					'default' => 'after_title',
				),
				'separatorWidth'              => array(
					'type'    => 'number',
					'default' => 30,
				),
				'separatorWidthType'          => array(
					'type'    => 'string',
					'default' => '%',
				),
				'separatorThickness'          => array(
					'type'    => 'number',
					'default' => 2,
				),
				'separatorThicknessUnit'      => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'separatorTopMargin'          => array(
					'type' => 'number',
				),
				'separatorRightMargin'        => array(
					'type' => 'number',
				),
				'separatorLeftMargin'         => array(
					'type' => 'number',
				),
				'separatorBottomMargin'       => array(
					'type' => 'number',
				),
				'separatorTopMarginTablet'    => array(
					'type' => 'number',
				),
				'separatorRightMarginTablet'  => array(
					'type' => 'number',
				),
				'separatorLeftMarginTablet'   => array(
					'type' => 'number',
				),
				'separatorBottomMarginTablet' => array(
					'type' => 'number',
				),
				'separatorTopMarginMobile'    => array(
					'type' => 'number',
				),
				'separatorRightMarginMobile'  => array(
					'type' => 'number',
				),
				'separatorLeftMarginMobile'   => array(
					'type' => 'number',
				),
				'separatorBottomMarginMobile' => array(
					'type' => 'number',
				),
				'separatorMarginUnit'         => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'separatorMarginUnitTablet'   => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'separatorMarginUnitMobile'   => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'separatorMarginLink'         => array(
					'type'    => 'boolean',
					'default' => false,
				),
				// effect.
				'imageHoverEffect'            => array(
					'type'    => 'string',
					'default' => 'static',
				),
				'objectFit'                   => array(
					'type' => 'string',
				),
				'objectFitTablet'             => array(
					'type' => 'string',
				),
				'objectFitMobile'             => array(
					'type' => 'string',
				),
				'useSeparateBoxShadows'       => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'imageBoxShadowColor'         => array(
					'type'    => 'string',
					'default' => '#00000070',
				),
				'imageBoxShadowHOffset'       => array(
					'type'    => 'number',
					'default' => 0,
				),
				'imageBoxShadowVOffset'       => array(
					'type'    => 'number',
					'default' => 0,
				),
				'imageBoxShadowBlur'          => array(
					'type' => 'number',
				),
				'imageBoxShadowSpread'        => array(
					'type' => 'number',
				),
				'imageBoxShadowPosition'      => array(
					'type'    => 'string',
					'default' => 'outset',
				),
				'imageBoxShadowColorHover'    => array(
					'type' => 'string',
				),
				'imageBoxShadowHOffsetHover'  => array(
					'type'    => 'number',
					'default' => 0,
				),
				'imageBoxShadowVOffsetHover'  => array(
					'type'    => 'number',
					'default' => 0,
				),
				'imageBoxShadowBlurHover'     => array(
					'type' => 'number',
				),
				'imageBoxShadowSpreadHover'   => array(
					'type' => 'number',
				),
				'imageBoxShadowPositionHover' => array(
					'type'    => 'string',
					'default' => 'outset',
				),
				// mask.
				'maskShape'                   => array(
					'type'    => 'string',
					'default' => 'none',
				),
				'maskCustomShape'             => array(
					'type'    => 'object',
					'default' => array(
						'url' => '',
						'alt' => 'mask shape',
					),
				),
				'maskSize'                    => array(
					'type'    => 'string',
					'default' => 'auto',
				),
				'maskPosition'                => array(
					'type'    => 'string',
					'default' => 'center center',
				),
				'maskRepeat'                  => array(
					'type'    => 'string',
					'default' => 'no-repeat',
				),
				'headingLetterSpacing'        => array(
					'type' => 'number',
				),
				'headingLetterSpacingTablet'  => array(
					'type' => 'number',
				),
				'headingLetterSpacingMobile'  => array(
					'type' => 'number',
				),
				'headingLetterSpacingType'    => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'captionLetterSpacing'        => array(
					'type' => 'number',
				),
				'captionLetterSpacingTablet'  => array(
					'type' => 'number',
				),
				'captionLetterSpacingMobile'  => array(
					'type' => 'number',
				),
				'captionLetterSpacingType'    => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'customHeightSetDesktop'      => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'customHeightSetTablet'       => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'customHeightSetMobile'       => array(
					'type'    => 'boolean',
					'default' => false,
				),
			);

			$advaned_image_border_attr         = Sureforms_Spec_Gb_Helper::get_instance()->generate_php_border_attribute( 'image' );
			$advaned_image_border_overlay_attr = Sureforms_Spec_Gb_Helper::get_instance()->generate_php_border_attribute( 'overlay' );

			$attr = array_merge( $advaned_image_border_attr, $advaned_image_border_overlay_attr, $attr );

			$attributes = apply_filters( 'sureforms_gutenberg_image_attributes_filters', $attr );

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
                loading="lazy"
            />';

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

			$main_classes = array(
				'wp-block-uagb-image',
				'uagb-block-' . $block_id,
				'wp-block-uagb-image--layout-' . esc_attr( $attributes['layout'] ),
				'wp-block-uagb-image--effect-' . esc_attr( $attributes['imageHoverEffect'] ),
				'wp-block-uagb-image--align-' . ( $attributes['align'] ? esc_attr( $attributes['align'] ) : 'none' ),
			);

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
	 *  Prepare if class 'Sureforms_Advanced_Image' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	Sureforms_Advanced_Image::get_instance();
}
