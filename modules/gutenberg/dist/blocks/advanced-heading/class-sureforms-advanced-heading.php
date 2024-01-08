<?php
/**
 * Sureforms - Advanced Heading
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Sureforms_Advanced_Heading' ) ) {

	/**
	 * Class Sureforms_Advanced_Heading.
	 */
	class Sureforms_Advanced_Heading {

		/**
		 * Member Variable
		 *
		 * @var Sureforms_Advanced_Heading|null
		 */
		private static $instance;

		/**
		 *  Initiator
		 *
		 * @return Sureforms_Advanced_Heading The instance of the Sureforms_Advanced_Heading class.
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
		 * Registers the `sureforms/advanced-heading` block on server.
		 *
		 * @since 0.0.1
		 */
		public function register_blocks() {

			// Check if the register function exists.
			if ( ! function_exists( 'register_block_type' ) ) {
				return;
			}

			$attr = array(
				'block_id'                     => array(
					'type' => 'string',
				),
				'classMigrate'                 => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'blockBackgroundType'          => array(
					'type'    => 'string',
					'default' => 'classic',
				),
				'blockGradientBackground'      => array(
					'type'    => 'string',
					'default' => 'linear-gradient(90deg, rgb(6, 147, 227) 0%, rgb(155, 81, 224) 100%)',
				),
				'blockBackground'              => array(
					'type' => 'string',
				),
				'headingTitleToggle'           => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'headingTitle'                 => array(
					'source'   => 'html',
					'selector' => '.uagb-heading-text',
					'type'     => 'string',
					'default'  => __( 'My Attractive Heading', 'sureforms' ),
				),
				'headingId'                    => array(
					'type' => 'string',
				),
				'headingDescToggle'            => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'headingDescPosition'          => array(
					'type'    => 'string',
					'default' => 'below-heading',
				),
				'headingDesc'                  => array(
					'source'   => 'html',
					'selector' => '.uagb-desc-text',
				),
				'headingAlign'                 => array(
					'type'    => 'string',
					'default' => 'left',
				),
				'headingAlignTablet'           => array(
					'type'    => 'string',
					'default' => '',
				),
				'headingAlignMobile'           => array(
					'type'    => 'string',
					'default' => '',
				),
				'headingColorType'             => array(
					'type'    => 'string',
					'default' => 'classic',
				),
				'headingColor'                 => array(
					'type' => 'string',
				),
				'headingGradientColor'         => array(
					'type'    => 'string',
					'default' => 'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
				),
				'subHeadingColor'              => array(
					'type' => 'string',
				),
				'separatorColor'               => array(
					'type'    => 'string',
					'default' => '#0170b9',
				),
				'headingTag'                   => array(
					'type'    => 'string',
					'default' => 'h2',
				),
				'level'                        => array(
					'type'    => 'number',
					'default' => 2,
				),
				'separatorStyle'               => array(
					'type'    => 'string',
					'default' => 'none',
				),
				'separatorPosition'            => array(
					'type'    => 'string',
					'default' => 'below-heading',
				),
				'separatorHeight'              => array(
					'type'    => 'number',
					'default' => 2,
				),
				'separatorHeightType'          => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'separatorWidth'               => array(
					'type'    => 'number',
					'default' => 12,
				),
				'separatorWidthTablet'         => array(
					'type' => 'number',
				),
				'separatorWidthMobile'         => array(
					'type' => 'number',
				),
				'separatorWidthType'           => array(
					'type'    => 'string',
					'default' => '%',
				),
				'headSpace'                    => array(
					'type'    => 'number',
					'default' => 15,
				),
				'headSpaceMobile'              => array(
					'type'    => 'number',
					'default' => '',
				),
				'headSpaceTablet'              => array(
					'type'    => 'number',
					'default' => '',
				),
				'headSpaceType'                => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'subHeadSpace'                 => array(
					'type'    => 'number',
					'default' => 15,
				),
				'subHeadSpaceMobile'           => array(
					'type'    => 'number',
					'default' => '',
				),
				'subHeadSpaceTablet'           => array(
					'type'    => 'number',
					'default' => '',
				),
				'subHeadSpaceType'             => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'headFontFamily'               => array(
					'type'    => 'string',
					'default' => 'Default',
				),
				'headFontWeight'               => array(
					'type' => 'string',
				),
				'headFontStyle'                => array(
					'type'    => 'string',
					'default' => 'normal',
				),
				'headTransform'                => array(
					'type' => 'string',
				),
				'headDecoration'               => array(
					'type' => 'string',
				),
				'headFontSizeType'             => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'headFontSizeTypeMobile'       => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'headFontSizeTypeTablet'       => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'headLineHeightType'           => array(
					'type'    => 'string',
					'default' => 'em',
				),
				'headFontSize'                 => array(
					'type' => 'number',
				),
				'headFontSizeTablet'           => array(
					'type' => 'number',
				),
				'headFontSizeMobile'           => array(
					'type' => 'number',
				),
				'headLineHeight'               => array(
					'type' => 'number',
				),
				'headLineHeightTablet'         => array(
					'type' => 'number',
				),
				'headLineHeightMobile'         => array(
					'type' => 'number',
				),
				'headLetterSpacing'            => array(
					'type' => 'number',
				),
				'headLetterSpacingTablet'      => array(
					'type' => 'number',
				),
				'headLetterSpacingMobile'      => array(
					'type' => 'number',
				),
				'headLetterSpacingType'        => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'headShadowColor'              => array(
					'type'    => 'string',
					'default' => '',
				),
				'headShadowHOffset'            => array(
					'type'    => 'number',
					'default' => 0,
				),
				'headShadowVOffset'            => array(
					'type'    => 'number',
					'default' => 0,
				),
				'headShadowBlur'               => array(
					'type'    => 'number',
					'default' => 10,
				),
				// sub headline.
				'subHeadFontFamily'            => array(
					'type'    => 'string',
					'default' => 'Default',
				),
				'subHeadFontWeight'            => array(
					'type' => 'string',
				),
				'subHeadFontStyle'             => array(
					'type'    => 'string',
					'default' => 'normal',
				),
				'subHeadTransform'             => array(
					'type' => 'string',
				),
				'subHeadDecoration'            => array(
					'type' => 'string',
				),
				'subHeadFontSize'              => array(
					'type' => 'number',
				),
				'subHeadFontSizeType'          => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'subHeadFontSizeTypeMobile'    => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'subHeadFontSizeTypeTablet'    => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'subHeadFontSizeTablet'        => array(
					'type' => 'number',
				),
				'subHeadFontSizeMobile'        => array(
					'type' => 'number',
				),
				'subHeadLineHeight'            => array(
					'type' => 'number',
				),
				'subHeadLineHeightType'        => array(
					'type'    => 'string',
					'default' => 'em',
				),
				'subHeadLineHeightTablet'      => array(
					'type' => 'number',
				),
				'subHeadLineHeightMobile'      => array(
					'type' => 'number',
				),
				'subHeadLetterSpacing'         => array(
					'type' => 'number',
				),
				'subHeadLetterSpacingTablet'   => array(
					'type' => 'number',
				),
				'subHeadLetterSpacingMobile'   => array(
					'type' => 'number',
				),
				'subHeadLetterSpacingType'     => array(
					'type'    => 'string',
					'default' => 'px',
				),
				// separator.
				'separatorSpace'               => array(
					'type'    => 'number',
					'default' => 15,
				),
				'separatorSpaceTablet'         => array(
					'type'    => 'number',
					'default' => '',
				),
				'separatorSpaceMobile'         => array(
					'type'    => 'number',
					'default' => '',
				),
				'separatorSpaceType'           => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'headLoadGoogleFonts'          => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'subHeadLoadGoogleFonts'       => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'separatorHoverColor'          => array(
					'type' => 'string',
				),
				'isPreview'                    => array(
					'type'    => 'boolean',
					'default' => false,
				),
				// padding.
				'blockTopPadding'              => array(
					'type' => 'number',
				),
				'blockRightPadding'            => array(
					'type' => 'number',
				),
				'blockLeftPadding'             => array(
					'type' => 'number',
				),
				'blockBottomPadding'           => array(
					'type' => 'number',
				),
				'blockTopPaddingTablet'        => array(
					'type' => 'number',
				),
				'blockRightPaddingTablet'      => array(
					'type' => 'number',
				),
				'blockLeftPaddingTablet'       => array(
					'type' => 'number',
				),
				'blockBottomPaddingTablet'     => array(
					'type' => 'number',
				),
				'blockTopPaddingMobile'        => array(
					'type' => 'number',
				),
				'blockRightPaddingMobile'      => array(
					'type' => 'number',
				),
				'blockLeftPaddingMobile'       => array(
					'type' => 'number',
				),
				'blockBottomPaddingMobile'     => array(
					'type' => 'number',
				),
				'blockPaddingUnit'             => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'blockPaddingUnitTablet'       => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'blockPaddingUnitMobile'       => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'blockPaddingLink'             => array(
					'type'    => 'boolean',
					'default' => false,
				),
				// margin.
				'blockTopMargin'               => array(
					'type' => 'number',
				),
				'blockRightMargin'             => array(
					'type' => 'number',
				),
				'blockLeftMargin'              => array(
					'type' => 'number',
				),
				'blockBottomMargin'            => array(
					'type' => 'number',
				),
				'blockTopMarginTablet'         => array(
					'type' => 'number',
				),
				'blockRightMarginTablet'       => array(
					'type' => 'number',
				),
				'blockLeftMarginTablet'        => array(
					'type' => 'number',
				),
				'blockBottomMarginTablet'      => array(
					'type' => 'number',
				),
				'blockTopMarginMobile'         => array(
					'type' => 'number',
				),
				'blockRightMarginMobile'       => array(
					'type' => 'number',
				),
				'blockLeftMarginMobile'        => array(
					'type' => 'number',
				),
				'blockBottomMarginMobile'      => array(
					'type' => 'number',
				),
				'blockMarginUnit'              => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'blockMarginUnitTablet'        => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'blockMarginUnitMobile'        => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'blockMarginLink'              => array(
					'type'    => 'boolean',
					'default' => false,
				),
				// link.
				'linkColor'                    => array(
					'type' => 'string',
				),
				'linkHColor'                   => array(
					'type' => 'string',
				),
				// Highlight.
				'highLightColor'               => array(
					'type'    => 'string',
					'default' => '#fff',
				),
				'highLightBackground'          => array(
					'type'    => 'string',
					'default' => '#007cba',
				),
				'highLightLoadGoogleFonts'     => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'highLightFontFamily'          => array(
					'type'    => 'string',
					'default' => 'default',
				),
				'highLightFontWeight'          => array(
					'type'    => 'string',
					'default' => 'Default',
				),
				'highLightFontStyle'           => array(
					'type'    => 'string',
					'default' => 'normal',
				),
				'highLightTransform'           => array(
					'type' => 'string',
				),
				'highLightDecoration'          => array(
					'type' => 'string',
				),
				'highLightFontSizeType'        => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'highLightFontSizeTypeMobile'  => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'highLightFontSizeTypeTablet'  => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'highLightLineHeightType'      => array(
					'type'    => 'string',
					'default' => 'em',
				),
				'highLightFontSize'            => array(
					'type' => 'number',
				),
				'highLightFontSizeTablet'      => array(
					'type' => 'number',
				),
				'highLightFontSizeMobile'      => array(
					'type' => 'number',
				),
				'highLightLineHeight'          => array(
					'type' => 'number',
				),
				'highLightLineHeightTablet'    => array(
					'type' => 'number',
				),
				'highLightLineHeightMobile'    => array(
					'type' => 'number',
				),
				'highLightLetterSpacing'       => array(
					'type' => 'number',
				),
				'highLightLetterSpacingTablet' => array(
					'type' => 'number',
				),
				'highLightLetterSpacingMobile' => array(
					'type' => 'number',
				),
				'highLightLetterSpacingType'   => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'highLightTopPadding'          => array(
					'type' => 'number',
				),
				'highLightRightPadding'        => array(
					'type' => 'number',
				),
				'highLightLeftPadding'         => array(
					'type' => 'number',
				),
				'highLightBottomPadding'       => array(
					'type' => 'number',
				),
				'highLightTopPaddingTablet'    => array(
					'type' => 'number',
				),
				'highLightRightPaddingTablet'  => array(
					'type' => 'number',
				),
				'highLightLeftPaddingTablet'   => array(
					'type' => 'number',
				),
				'highLightBottomPaddingTablet' => array(
					'type' => 'number',
				),
				'highLightTopPaddingMobile'    => array(
					'type' => 'number',
				),
				'highLightRightPaddingMobile'  => array(
					'type' => 'number',
				),
				'highLightLeftPaddingMobile'   => array(
					'type' => 'number',
				),
				'highLightBottomPaddingMobile' => array(
					'type' => 'number',
				),
				'highLightPaddingUnit'         => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'highLightPaddingUnitTablet'   => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'highLightPaddingUnitMobile'   => array(
					'type'    => 'string',
					'default' => 'px',
				),
				'highLightPaddingLink'         => array(
					'type'    => 'boolean',
					'default' => false,
				),
			);

			$advaned_heading_border_attr = Sureforms_Spec_Gb_Helper::get_instance()->generate_php_border_attribute( 'highLight' );

			$attr = array_merge( $advaned_heading_border_attr, $attr );

			$attributes = apply_filters( 'sureforms_gutenberg_advaned_heading_attributes_filters', $attr );

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
		 * @since 0.0.1
		 */
		public function render_html( $attributes ) {
			if ( isset( $attributes['block_id'] ) ) {
				$block_id = $attributes['block_id'];
			}

			$seperator = '';

			if ( isset( $attributes['separatorStyle'] )
				&& 'none' !== $attributes['separatorStyle']
			) {
				$seperator = '<div class="uagb-separator"></div>';
			}

			$heading_text = '';

			if ( isset( $attributes['headingTitle'] ) ) {
				$heading_text            = 'above-heading' === $attributes['separatorPosition'] ? $seperator : '';
				$attributes['headingId'] = isset( $attributes['headingId'] ) ? "id='{$attributes['headingId']}'" : '';
				$heading_text           .= sprintf(
					'<%1$s class="uagb-heading-text" %3$s>%2$s</%1$s>',
					esc_attr( $attributes['headingTag'] ),
					$attributes['headingTitle'],
					esc_attr( $attributes['headingId'] )
				);
				$heading_text           .= 'below-heading' === $attributes['separatorPosition'] ? $seperator : '';
			}

			$desc_text = '';

			if ( isset( $attributes['headingDesc'] ) ) {
				$desc_text  = 'above-sub-heading' === $attributes['separatorPosition'] ? $seperator : '';
				$desc_text .= sprintf(
					'<p class="uagb-desc-text">%1$s</p>',
					esc_html( $attributes['headingDesc'] )
				);
				$desc_text .= 'below-sub-heading' === $attributes['separatorPosition'] ? $seperator : '';
			}

			$main_classes = array(
				'wp-block-uagb-advanced-heading',
				'uagb-block',
				'uagb-block-' . $block_id,
			);

			if ( isset( $attributes['className'] ) ) {
				$main_classes[] = $attributes['className'];
			}

			ob_start();
			?>
				<div class="<?php echo esc_attr( implode( ' ', $main_classes ) ); ?>">
					<?php
					if ( $attributes['headingDescToggle']
						&& 'above-heading' === $attributes['headingDescPosition']
					) {
						echo $desc_text;
					}
					if ( isset( $attributes['headingTitleToggle'] ) && true === $attributes['headingTitleToggle'] ) {
						echo $heading_text;
					}
					if ( $attributes['headingDescToggle']
						&& 'below-heading' === $attributes['headingDescPosition']
					) {
						echo $desc_text;
					}
					if ( ! $attributes['headingDescToggle']
						&& ! $attributes['headingTitleToggle']
					) {
						echo $seperator;
					}
					?>
				</div>
			<?php
			return ob_get_clean();
		}
	}

	/**
	 *  Prepare if class 'Sureforms_Advanced_Heading' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	Sureforms_Advanced_Heading::get_instance();
}
