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
				'block_id'                       =>
				array(
					'type' => 'string',
				),
				'isPreview'                      =>
				array(
					'type'    => 'boolean',
					'default' => false,
				),
				'separatorAlign'                 =>
				array(
					'type'         => 'string',
					'default'      => 'center',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'overall-alignment',
					),
				),
				'separatorAlignTablet'           =>
				array(
					'type'         => 'string',
					'default'      => 'center',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'overall-alignment-tablet',
					),
				),
				'separatorAlignMobile'           =>
				array(
					'type'         => 'string',
					'default'      => 'center',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'overall-alignment-mobile',
					),
				),
				'separatorStyle'                 =>
				array(
					'type'         => 'string',
					'default'      => 'solid',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-style',
					),
				),
				'separatorWidth'                 =>
				array(
					'type'         => 'number',
					'default'      => 100,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-width',
					),
				),
				'separatorWidthTablet'           =>
				array(
					'type'         => 'number',
					'default'      => 100,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-width-tablet',
					),
				),
				'separatorWidthMobile'           =>
				array(
					'type'         => 'number',
					'default'      => 100,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-width-mobile',
					),
				),
				'separatorWidthType'             =>
				array(
					'type'         => 'string',
					'default'      => '%',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-width-type',
					),
				),
				'separatorBorderHeight'          =>
				array(
					'type'         => 'number',
					'default'      => 3,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-height',
					),
				),
				'separatorBorderHeightMobile'    =>
				array(
					'type'         => 'number',
					'default'      => 3,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-height-mobile',
					),
				),
				'separatorBorderHeightTablet'    =>
				array(
					'type'         => 'number',
					'default'      => 3,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-height-tablet',
					),
				),
				'separatorBorderHeightUnit'      =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-thickness-unit',
					),
				),
				'separatorSize'                  =>
				array(
					'type'         => 'number',
					'default'      => 5,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-size',
					),
				),
				'separatorSizeMobile'            =>
				array(
					'type'         => 'number',
					'default'      => 5,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-size-Mobile',
					),
				),
				'separatorSizeTablet'            =>
				array(
					'type'         => 'number',
					'default'      => 5,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-size-Tablet',
					),
				),
				'separatorSizeType'              =>
				array(
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-size-type',
					),
				),
				'separatorColor'                 =>
				array(
					'type'         => 'string',
					'default'      => '#333',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-color',
					),
				),
				'separatorHeight'                =>
				array(
					'type'         => 'number',
					'default'      => 10,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-gap',
					),
				),
				'separatorHeightMobile'          =>
				array(
					'type'         => 'number',
					'default'      => 10,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-gap-mobile',
					),
				),
				'separatorHeightTablet'          =>
				array(
					'type'         => 'number',
					'default'      => 10,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-gap-tablet',
					),
				),
				'separatorHeightType'            =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-gap-unit',
					),
				),
				'separatorBottomPadding'         =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-bottom-padding',
					),
				),
				'separatorPaddingTopTablet'      =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-top-padding-tablet',
					),
				),
				'separatorPaddingRightTablet'    =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-right-padding-tablet',
					),
				),
				'separatorPaddingBottomTablet'   =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-bottom-padding-tablet',
					),
				),
				'separatorPaddingLeftTablet'     =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-left-padding-tablet',
					),
				),
				'separatorPaddingTopMobile'      =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-top-padding-mobile',
					),
				),
				'separatorPaddingRightMobile'    =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-right-padding-mobile',
					),
				),
				'separatorPaddingBottomMobile'   =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-bottom-padding-mobile',
					),
				),
				'separatorPaddingLeftMobile'     =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-left-padding-mobile',
					),
				),
				'separatorPaddingUnit'           =>
				array(
					'type'         => 'number',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-padding-unit',
					),
				),
				'separatorMobilePaddingUnit'     =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-padding-unit-mobile',
					),
					'default'      => 'px',
				),
				'separatorTabletPaddingUnit'     =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-padding-unit-tablet',
					),
					'default'      => 'px',
				),
				'separatorPaddingLink'           =>
				array(
					'type'         => 'boolean',
					'default'      => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-padding-link',
					),
				),
				'elementType'                    =>
				array(
					'type'    => 'string',
					'default' => 'none',
				),
				'separatorText'                  =>
				array(
					'type'    => 'string',
					'default' => __( 'Divider', 'sureforms' ),
				),
				'separatorTextTag'               =>
				array(
					'type'         => 'string',
					'default'      => 'h4',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'separator-text-tag',
					),
				),
				'separatorIcon'                  =>
				array(
					'type'    => 'string',
					'default' => 'circle-check',
				),
				'elementPosition'                =>
				array(
					'type'         => 'string',
					'default'      => 'center',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'element-position',
					),
				),
				'elementSpacing'                 =>
				array(
					'type'         => 'number',
					'default'      => 15,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'element-spacing',
					),
				),
				'elementSpacingTablet'           =>
				array(
					'type'         => 'number',
					'default'      => 15,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'element-spacing-tablet',
					),
				),
				'elementSpacingMobile'           =>
				array(
					'type'         => 'number',
					'default'      => 15,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'element-spacing-mobile',
					),
				),
				'elementSpacingUnit'             =>
				array(
					'type'    => 'string',
					'default' => 'px',
				),
				'elementTextLoadGoogleFonts'     =>
				array(
					'type'         => 'boolean',
					'default'      => false,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-load-google-fonts',
					),
				),
				'elementTextFontFamily'          =>
				array(
					'type'         => 'string',
					'default'      => 'Default',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-family',
					),
				),
				'elementTextFontWeight'          =>
				array(
					'type'         => 'string',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-weight',
					),
				),
				'elementTextFontSize'            =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-size',
					),
				),
				'elementTextFontSizeType'        =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-size-type',
					),
				),
				'elementTextFontSizeTablet'      =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-size-tablet',
					),
				),
				'elementTextFontSizeMobile'      =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-size-mobile',
					),
				),
				'elementTextLineHeightType'      =>
				array(
					'type'         => 'string',
					'default'      => 'em',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-line-height-type',
					),
				),
				'elementTextLineHeight'          =>
				array(
					'type'         => 'number',
					'default'      => 1,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-line-height',
					),
				),
				'elementTextLineHeightTablet'    =>
				array(
					'type'         => 'number',
					'default'      => 1,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-line-height-tablet',
					),
				),
				'elementTextLineHeightMobile'    =>
				array(
					'type'         => 'number',
					'default'      => 1,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-line-height-mobile',
					),
				),
				'elementTextFontStyle'           =>
				array(
					'type'         => 'string',
					'default'      => 'normal',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-font-style',
					),
				),
				'elementTextLetterSpacing'       =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'label-letter-spacing',
					),
				),
				'elementTextLetterSpacingTablet' =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'label-letter-spacing-tablet',
					),
				),
				'elementTextLetterSpacingMobile' =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'label-letter-spacing-mobile',
					),
				),
				'elementTextLetterSpacingType'   =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'label-letter-spacing-type',
					),
				),
				'elementTextDecoration'          =>
				array(
					'type'         => 'string',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-decoration',
					),
				),
				'elementTextTransform'           =>
				array(
					'type'         => 'string',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-transform',
					),
				),
				'elementColor'                   =>
				array(
					'type'         => 'string',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-title-color',
					),
				),
				'elementIconWidth'               =>
				array(
					'type'         => 'number',
					'default'      => 30,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-icon-width',
					),
				),
				'elementIconWidthTablet'         =>
				array(
					'type'         => 'number',
					'default'      => 30,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-icon-width-tablet',
					),
				),
				'elementIconWidthMobile'         =>
				array(
					'type'         => 'number',
					'default'      => 30,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'main-icon-width-mobile',
					),
				),
				'elementIconWidthType'           =>
				array(
					'type'    => 'string',
					'default' => 'px',
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
		 * @since 0.0.1
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
