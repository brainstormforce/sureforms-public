<?php
/**
 * Sureforms - Advanced Heading
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Advanced_Heading' ) ) {

	/**
	 * Class Advanced_Heading.
	 */
	class Advanced_Heading {

		/**
		 * Member Variable
		 *
		 * @var Advanced_Heading|null
		 */
		private static $instance;

		/**
		 *  Initiator
		 *
		 * @return Advanced_Heading The instance of the Advanced_Heading class.
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
		 * Registers the `sureforms/advanced-heading` block on server.
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
				'block_id'                     => [
					'type' => 'string',
				],
				'classMigrate'                 => [
					'type'    => 'boolean',
					'default' => false,
				],
				'blockBackgroundType'          => [
					'type'    => 'string',
					'default' => 'classic',
				],
				'blockGradientBackground'      => [
					'type'    => 'string',
					'default' => 'linear-gradient(90deg, rgb(6, 147, 227) 0%, rgb(155, 81, 224) 100%)',
				],
				'blockBackground'              => [
					'type' => 'string',
				],
				'headingTitleToggle'           => [
					'type'    => 'boolean',
					'default' => true,
				],
				'headingTitle'                 => [
					'type'    => 'string',
					'default' => __( 'Your Attractive Heading', 'sureforms' ),
				],
				'headingId'                    => [
					'type' => 'string',
				],
				'headingDescToggle'            => [
					'type'    => 'boolean',
					'default' => false,
				],
				'headingDescPosition'          => [
					'type'    => 'string',
					'default' => 'below-heading',
				],
				'headingDesc'                  => [
					'type' => 'string',
				],
				'headingAlign'                 => [
					'type'    => 'string',
					'default' => 'left',
				],
				'headingAlignTablet'           => [
					'type'    => 'string',
					'default' => '',
				],
				'headingAlignMobile'           => [
					'type'    => 'string',
					'default' => '',
				],
				'headingColorType'             => [
					'type'    => 'string',
					'default' => 'classic',
				],
				'headingColor'                 => [
					'type'    => 'string',
					'default' => '#000',
				],
				'headingGradientColor'         => [
					'type'    => 'string',
					'default' => 'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
				],
				'subHeadingColor'              => [
					'type'    => 'string',
					'default' => '#000',
				],
				'separatorColor'               => [
					'type'    => 'string',
					'default' => '#0170b9',
				],
				'headingTag'                   => [
					'type'    => 'string',
					'default' => 'h2',
				],
				'level'                        => [
					'type'    => 'number',
					'default' => 2,
				],
				'separatorStyle'               => [
					'type'    => 'string',
					'default' => 'none',
				],
				'separatorPosition'            => [
					'type'    => 'string',
					'default' => 'below-heading',
				],
				'separatorHeight'              => [
					'type'    => 'number',
					'default' => 2,
				],
				'separatorHeightType'          => [
					'type'    => 'string',
					'default' => 'px',
				],
				'separatorWidth'               => [
					'type'    => 'number',
					'default' => 12,
				],
				'separatorWidthTablet'         => [
					'type' => 'number',
				],
				'separatorWidthMobile'         => [
					'type' => 'number',
				],
				'separatorWidthType'           => [
					'type'    => 'string',
					'default' => '%',
				],
				'headSpace'                    => [
					'type'    => 'number',
					'default' => 15,
				],
				'headSpaceMobile'              => [
					'type'    => 'number',
					'default' => '',
				],
				'headSpaceTablet'              => [
					'type'    => 'number',
					'default' => '',
				],
				'headSpaceType'                => [
					'type'    => 'string',
					'default' => 'px',
				],
				'subHeadSpace'                 => [
					'type'    => 'number',
					'default' => 15,
				],
				'subHeadSpaceMobile'           => [
					'type'    => 'number',
					'default' => '',
				],
				'subHeadSpaceTablet'           => [
					'type'    => 'number',
					'default' => '',
				],
				'subHeadSpaceType'             => [
					'type'    => 'string',
					'default' => 'px',
				],
				'headFontFamily'               => [
					'type'    => 'string',
					'default' => 'Default',
				],
				'headFontWeight'               => [
					'type' => 'string',
				],
				'headFontStyle'                => [
					'type'    => 'string',
					'default' => 'normal',
				],
				'headTransform'                => [
					'type' => 'string',
				],
				'headDecoration'               => [
					'type' => 'string',
				],
				'headFontSizeType'             => [
					'type'    => 'string',
					'default' => 'px',
				],
				'headFontSizeTypeMobile'       => [
					'type'    => 'string',
					'default' => 'px',
				],
				'headFontSizeTypeTablet'       => [
					'type'    => 'string',
					'default' => 'px',
				],
				'headLineHeightType'           => [
					'type'    => 'string',
					'default' => 'em',
				],
				'headFontSize'                 => [
					'type' => 'number',
				],
				'headFontSizeTablet'           => [
					'type' => 'number',
				],
				'headFontSizeMobile'           => [
					'type' => 'number',
				],
				'headLineHeight'               => [
					'type' => 'number',
				],
				'headLineHeightTablet'         => [
					'type' => 'number',
				],
				'headLineHeightMobile'         => [
					'type' => 'number',
				],
				'headLetterSpacing'            => [
					'type' => 'number',
				],
				'headLetterSpacingTablet'      => [
					'type' => 'number',
				],
				'headLetterSpacingMobile'      => [
					'type' => 'number',
				],
				'headLetterSpacingType'        => [
					'type'    => 'string',
					'default' => 'px',
				],
				'headShadowColor'              => [
					'type'    => 'string',
					'default' => '',
				],
				'headShadowHOffset'            => [
					'type'    => 'number',
					'default' => 0,
				],
				'headShadowVOffset'            => [
					'type'    => 'number',
					'default' => 0,
				],
				'headShadowBlur'               => [
					'type'    => 'number',
					'default' => 10,
				],
				// sub headline.
				'subHeadFontFamily'            => [
					'type'    => 'string',
					'default' => 'Default',
				],
				'subHeadFontWeight'            => [
					'type' => 'string',
				],
				'subHeadFontStyle'             => [
					'type'    => 'string',
					'default' => 'normal',
				],
				'subHeadTransform'             => [
					'type' => 'string',
				],
				'subHeadDecoration'            => [
					'type' => 'string',
				],
				'subHeadFontSize'              => [
					'type' => 'number',
				],
				'subHeadFontSizeType'          => [
					'type'    => 'string',
					'default' => 'px',
				],
				'subHeadFontSizeTypeMobile'    => [
					'type'    => 'string',
					'default' => 'px',
				],
				'subHeadFontSizeTypeTablet'    => [
					'type'    => 'string',
					'default' => 'px',
				],
				'subHeadFontSizeTablet'        => [
					'type' => 'number',
				],
				'subHeadFontSizeMobile'        => [
					'type' => 'number',
				],
				'subHeadLineHeight'            => [
					'type' => 'number',
				],
				'subHeadLineHeightType'        => [
					'type'    => 'string',
					'default' => 'em',
				],
				'subHeadLineHeightTablet'      => [
					'type' => 'number',
				],
				'subHeadLineHeightMobile'      => [
					'type' => 'number',
				],
				'subHeadLetterSpacing'         => [
					'type' => 'number',
				],
				'subHeadLetterSpacingTablet'   => [
					'type' => 'number',
				],
				'subHeadLetterSpacingMobile'   => [
					'type' => 'number',
				],
				'subHeadLetterSpacingType'     => [
					'type'    => 'string',
					'default' => 'px',
				],
				// separator.
				'separatorSpace'               => [
					'type'    => 'number',
					'default' => 15,
				],
				'separatorSpaceTablet'         => [
					'type'    => 'number',
					'default' => '',
				],
				'separatorSpaceMobile'         => [
					'type'    => 'number',
					'default' => '',
				],
				'separatorSpaceType'           => [
					'type'    => 'string',
					'default' => 'px',
				],
				'headLoadGoogleFonts'          => [
					'type'    => 'boolean',
					'default' => false,
				],
				'subHeadLoadGoogleFonts'       => [
					'type'    => 'boolean',
					'default' => false,
				],
				'separatorHoverColor'          => [
					'type' => 'string',
				],
				'isPreview'                    => [
					'type'    => 'boolean',
					'default' => false,
				],
				// padding.
				'blockTopPadding'              => [
					'type' => 'number',
				],
				'blockRightPadding'            => [
					'type' => 'number',
				],
				'blockLeftPadding'             => [
					'type' => 'number',
				],
				'blockBottomPadding'           => [
					'type' => 'number',
				],
				'blockTopPaddingTablet'        => [
					'type' => 'number',
				],
				'blockRightPaddingTablet'      => [
					'type' => 'number',
				],
				'blockLeftPaddingTablet'       => [
					'type' => 'number',
				],
				'blockBottomPaddingTablet'     => [
					'type' => 'number',
				],
				'blockTopPaddingMobile'        => [
					'type' => 'number',
				],
				'blockRightPaddingMobile'      => [
					'type' => 'number',
				],
				'blockLeftPaddingMobile'       => [
					'type' => 'number',
				],
				'blockBottomPaddingMobile'     => [
					'type' => 'number',
				],
				'blockPaddingUnit'             => [
					'type'    => 'string',
					'default' => 'px',
				],
				'blockPaddingUnitTablet'       => [
					'type'    => 'string',
					'default' => 'px',
				],
				'blockPaddingUnitMobile'       => [
					'type'    => 'string',
					'default' => 'px',
				],
				'blockPaddingLink'             => [
					'type'    => 'boolean',
					'default' => false,
				],
				// margin.
				'blockTopMargin'               => [
					'type' => 'number',
				],
				'blockRightMargin'             => [
					'type' => 'number',
				],
				'blockLeftMargin'              => [
					'type' => 'number',
				],
				'blockBottomMargin'            => [
					'type' => 'number',
				],
				'blockTopMarginTablet'         => [
					'type' => 'number',
				],
				'blockRightMarginTablet'       => [
					'type' => 'number',
				],
				'blockLeftMarginTablet'        => [
					'type' => 'number',
				],
				'blockBottomMarginTablet'      => [
					'type' => 'number',
				],
				'blockTopMarginMobile'         => [
					'type' => 'number',
				],
				'blockRightMarginMobile'       => [
					'type' => 'number',
				],
				'blockLeftMarginMobile'        => [
					'type' => 'number',
				],
				'blockBottomMarginMobile'      => [
					'type' => 'number',
				],
				'blockMarginUnit'              => [
					'type'    => 'string',
					'default' => 'px',
				],
				'blockMarginUnitTablet'        => [
					'type'    => 'string',
					'default' => 'px',
				],
				'blockMarginUnitMobile'        => [
					'type'    => 'string',
					'default' => 'px',
				],
				'blockMarginLink'              => [
					'type'    => 'boolean',
					'default' => false,
				],
				// link.
				'linkColor'                    => [
					'type' => 'string',
				],
				'linkHColor'                   => [
					'type' => 'string',
				],
				// Highlight.
				'highLightColor'               => [
					'type'    => 'string',
					'default' => '#fff',
				],
				'highLightBackground'          => [
					'type'    => 'string',
					'default' => '#007cba',
				],
				'highLightLoadGoogleFonts'     => [
					'type'    => 'boolean',
					'default' => false,
				],
				'highLightFontFamily'          => [
					'type'    => 'string',
					'default' => 'default',
				],
				'highLightFontWeight'          => [
					'type'    => 'string',
					'default' => 'Default',
				],
				'highLightFontStyle'           => [
					'type'    => 'string',
					'default' => 'normal',
				],
				'highLightTransform'           => [
					'type' => 'string',
				],
				'highLightDecoration'          => [
					'type' => 'string',
				],
				'highLightFontSizeType'        => [
					'type'    => 'string',
					'default' => 'px',
				],
				'highLightFontSizeTypeMobile'  => [
					'type'    => 'string',
					'default' => 'px',
				],
				'highLightFontSizeTypeTablet'  => [
					'type'    => 'string',
					'default' => 'px',
				],
				'highLightLineHeightType'      => [
					'type'    => 'string',
					'default' => 'em',
				],
				'highLightFontSize'            => [
					'type' => 'number',
				],
				'highLightFontSizeTablet'      => [
					'type' => 'number',
				],
				'highLightFontSizeMobile'      => [
					'type' => 'number',
				],
				'highLightLineHeight'          => [
					'type' => 'number',
				],
				'highLightLineHeightTablet'    => [
					'type' => 'number',
				],
				'highLightLineHeightMobile'    => [
					'type' => 'number',
				],
				'highLightLetterSpacing'       => [
					'type' => 'number',
				],
				'highLightLetterSpacingTablet' => [
					'type' => 'number',
				],
				'highLightLetterSpacingMobile' => [
					'type' => 'number',
				],
				'highLightLetterSpacingType'   => [
					'type'    => 'string',
					'default' => 'px',
				],
				'highLightTopPadding'          => [
					'type' => 'number',
				],
				'highLightRightPadding'        => [
					'type' => 'number',
				],
				'highLightLeftPadding'         => [
					'type' => 'number',
				],
				'highLightBottomPadding'       => [
					'type' => 'number',
				],
				'highLightTopPaddingTablet'    => [
					'type' => 'number',
				],
				'highLightRightPaddingTablet'  => [
					'type' => 'number',
				],
				'highLightLeftPaddingTablet'   => [
					'type' => 'number',
				],
				'highLightBottomPaddingTablet' => [
					'type' => 'number',
				],
				'highLightTopPaddingMobile'    => [
					'type' => 'number',
				],
				'highLightRightPaddingMobile'  => [
					'type' => 'number',
				],
				'highLightLeftPaddingMobile'   => [
					'type' => 'number',
				],
				'highLightBottomPaddingMobile' => [
					'type' => 'number',
				],
				'highLightPaddingUnit'         => [
					'type'    => 'string',
					'default' => 'px',
				],
				'highLightPaddingUnitTablet'   => [
					'type'    => 'string',
					'default' => 'px',
				],
				'highLightPaddingUnitMobile'   => [
					'type'    => 'string',
					'default' => 'px',
				],
				'highLightPaddingLink'         => [
					'type'    => 'boolean',
					'default' => false,
				],
			];

			$advaned_heading_border_attr = Spec_Gb_Helper::get_instance()->generate_php_border_attribute( 'highLight' );

			$attr = array_merge( $advaned_heading_border_attr, $attr );

			$attributes = apply_filters( 'srfm_gutenberg_advaned_heading_attributes_filters', $attr );

			register_block_type(
				'srfm/advanced-heading',
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

			$block_id        = '';
			$form_id         = '';
			$heading_wrapper = '';
			if ( isset( $attributes['block_id'] ) ) {
				$block_id = $attributes['block_id'];
			}
			if ( isset( $attributes['formId'] ) ) {
				$form_id = $attributes['formId'];
			}
			if ( isset( $attributes['headingWrapper'] ) ) {
				$heading_wrapper = $attributes['headingWrapper'];
			}

			$element = ! empty( $heading_wrapper ) ? $heading_wrapper : 'div';

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
					$attributes['headingDesc']
				);
				$desc_text .= 'below-sub-heading' === $attributes['separatorPosition'] ? $seperator : '';
			}

			$conditional_class = apply_filters( 'srfm_conditional_logic_classes', $form_id, $block_id );

			$main_classes = [
				'wp-block-uagb-advanced-heading',
				'uagb-block',
				'uagb-block-' . $block_id,
				$conditional_class,
			];

			if ( isset( $attributes['className'] ) ) {
				$main_classes[] = $attributes['className'];
			}

			ob_start();
			?>
				<<?php echo esc_attr( $element ); ?> data-block-id="<?php echo esc_attr( $block_id ); ?>" class="<?php echo esc_attr( implode( ' ', $main_classes ) ); ?>">
					<?php
					if ( $attributes['headingDescToggle']
						&& 'above-heading' === $attributes['headingDescPosition']
					) {
						echo wp_kses( $desc_text, 'post' );
					}
					if ( isset( $attributes['headingTitleToggle'] ) && true === $attributes['headingTitleToggle'] ) {
						echo wp_kses( $heading_text, 'post' );
					}
					if ( $attributes['headingDescToggle']
						&& 'below-heading' === $attributes['headingDescPosition']
					) {
						echo wp_kses( $desc_text, 'post' );
					}
					if ( ! $attributes['headingDescToggle']
						&& ! $attributes['headingTitleToggle']
					) {
						echo wp_kses( $seperator, 'post' );
					}
					?>
				</<?php echo esc_attr( $element ); ?>>
			<?php
			return ob_get_clean();
		}
	}

	/**
	 *  Prepare if class 'Advanced_Heading' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	Advanced_Heading::get_instance();
}
