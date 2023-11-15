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
				'icon'                             =>
				array(
					'type'    => 'string',
					'default' => 'circle-check',
				),
				'iconSize'                         =>
				array(
					'type'         => 'number',
					'default'      => 40,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-size',
					),
				),
				'iconSizeTablet'                   =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-size-tablet',
					),
				),
				'iconSizeMobile'                   =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-size-mobile',
					),
				),
				'iconSizeUnit'                     =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-size-type',
					),
				),
				'align'                            =>
				array(
					'type'         => 'string',
					'default'      => 'center',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'overall-alignment',
					),
				),
				'alignTablet'                      =>
				array(
					'type'         => 'string',
					'default'      => '',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'overall-alignment-tablet',
					),
				),
				'alignMobile'                      =>
				array(
					'type'         => 'string',
					'default'      => '',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'overall-alignment-mobile',
					),
				),
				'iconColor'                        =>
				array(
					'type'         => 'string',
					'default'      => '#333',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-color',
					),
				),
				'iconBorderColor'                  =>
				array(
					'type'         => 'string',
					'default'      => '',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-border-color',
					),
				),
				'iconBackgroundColorType'          =>
				array(
					'type'         => 'string',
					'default'      => 'classic',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-background-color-type',
					),
				),
				'iconBackgroundColor'              =>
				array(
					'type'         => 'string',
					'default'      => '',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-background-color',
					),
				),
				'iconBackgroundGradientColor'      =>
				array(
					'type'         => 'string',
					'default'      => 'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-background-gradient-color',
					),
				),
				'iconHoverColor'                   =>
				array(
					'type'         => 'string',
					'default'      => '',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-hover-color',
					),
				),
				'iconHoverBackgroundColorType'     =>
				array(
					'type'         => 'string',
					'default'      => 'classic',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-hover-background-color-type',
					),
				),
				'iconHoverBackgroundColor'         =>
				array(
					'type'         => 'string',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-hover-background-color',
					),
				),
				'iconHoverBackgroundGradientColor' =>
				array(
					'type'         => 'string',
					'default'      => 'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-hover-background-gradient-color',
					),
				),
				'rotation'                         =>
				array(
					'type'         => 'number',
					'default'      => 0,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-rotation',
					),
				),
				'rotationUnit'                     =>
				array(
					'type'         => 'string',
					'default'      => 'deg',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-rotation-type',
					),
				),
				'block_id'                         =>
				array(
					'type' => 'string',
				),
				'link'                             =>
				array(
					'type'    => 'string',
					'default' => '',
				),
				'target'                           =>
				array(
					'type'    => 'boolean',
					'default' => false,
				),
				'disableLink'                      =>
				array(
					'type'    => 'boolean',
					'default' => false,
				),
				'iconTopPadding'                   =>
				array(
					'type'         => 'number',
					'default'      => 5,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-top-padding',
					),
				),
				'iconRightPadding'                 =>
				array(
					'type'         => 'number',
					'default'      => 5,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-right-padding',
					),
				),
				'iconLeftPadding'                  =>
				array(
					'type'         => 'number',
					'default'      => 5,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-left-padding',
					),
				),
				'iconBottomPadding'                =>
				array(
					'type'         => 'number',
					'default'      => 5,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-bottom-padding',
					),
				),
				'iconTopTabletPadding'             =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-top-tablet-padding',
					),
				),
				'iconRightTabletPadding'           =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-right-tablet-padding',
					),
				),
				'iconLeftTabletPadding'            =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-left-tablet-padding',
					),
				),
				'iconBottomTabletPadding'          =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-bottom-tablet-padding',
					),
				),
				'iconTopMobilePadding'             =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-top-mobile-padding',
					),
				),
				'iconRightMobilePadding'           =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-right-mobile-padding',
					),
				),
				'iconLeftMobilePadding'            =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-left-mobile-padding',
					),
				),
				'iconBottomMobilePadding'          =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-bottom-mobile-padding',
					),
				),
				'iconPaddingUnit'                  =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-padding-unit',
					),
				),
				'iconTabletPaddingUnit'            =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-tablet-padding-unit',
					),
				),
				'iconMobilePaddingUnit'            =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-mobile-padding-unit',
					),
				),
				'iconPaddingLink'                  =>
				array(
					'type'    => 'boolean',
					'default' => false,
				),
				'iconTopMargin'                    =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-top-margin',
					),
				),
				'iconRightMargin'                  =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-right-margin',
					),
				),
				'iconLeftMargin'                   =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-left-margin',
					),
				),
				'iconBottomMargin'                 =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-bottom-margin',
					),
				),
				'iconTopTabletMargin'              =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-top-tablet-margin',
					),
				),
				'iconRightTabletMargin'            =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-right-tablet-margin',
					),
				),
				'iconLeftTabletMargin'             =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-left-tablet-margin',
					),
				),
				'iconBottomTabletMargin'           =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-bottom-tablet-margin',
					),
				),
				'iconTopMobileMargin'              =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-top-mobile-margin',
					),
				),
				'iconRightMobileMargin'            =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-right-mobile-margin',
					),
				),
				'iconLeftMobileMargin'             =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-left-mobile-margin',
					),
				),
				'iconBottomMobileMargin'           =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-bottom-mobile-margin',
					),
				),
				'iconMarginUnit'                   =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-margin-unit',
					),
				),
				'iconTabletMarginUnit'             =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-tablet-margin-unit',
					),
				),
				'iconMobileMarginUnit'             =>
				array(
					'type'         => 'string',
					'default'      => 'px',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-mobile-margin-unit',
					),
				),
				'iconMarginLink'                   =>
				array(
					'type'    => 'boolean',
					'default' => false,
				),
				'isPreview'                        =>
				array(
					'type'    => 'boolean',
					'default' => false,
				),
				'iconBorderStyle'                  =>
				array(
					'type'         => 'string',
					'default'      => 'default',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-border-style',
					),
				),
				'useSeparateBoxShadows'            =>
				array(
					'type'    => 'boolean',
					'default' => true,
				),
				'iconShadowColor'                  =>
				array(
					'type'         => 'string',
					'default'      => '#00000070',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-shadow-color',
					),
				),
				'iconShadowHOffset'                =>
				array(
					'type'         => 'number',
					'default'      => 0,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-shadow-hoffset',
					),
				),
				'iconShadowVOffset'                =>
				array(
					'type'         => 'number',
					'default'      => 0,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-shadow-voffset',
					),
				),
				'iconShadowBlur'                   =>
				array(
					'type'         => 'number',
					'default'      => 0,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-shadow-blur',
					),
				),
				'iconBoxShadowColor'               =>
				array(
					'type'         => 'string',
					'default'      => '#00000070',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-box-shadow-color',
					),
				),
				'iconBoxShadowHOffset'             =>
				array(
					'type'         => 'number',
					'default'      => 0,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-box-shadow-hoffset',
					),
				),
				'iconBoxShadowVOffset'             =>
				array(
					'type'         => 'number',
					'default'      => 0,
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-box-shadow-voffset',
					),
				),
				'iconBoxShadowBlur'                =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-box-shadow-blur',
					),
				),
				'iconBoxShadowSpread'              =>
				array(
					'type'         => 'number',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-box-shadow-spread',
					),
				),
				'iconBoxShadowPosition'            =>
				array(
					'type'         => 'string',
					'default'      => 'outset',
					'isGBSStyle'   => true,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-box-shadow-position',
					),
				),
				'iconShadowColorHover'             =>
				array(
					'type'         => 'string',
					'default'      => '#00000070',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-shadow-color-hover',
					),
				),
				'iconShadowHOffsetHover'           =>
				array(
					'type'         => 'number',
					'default'      => 0,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-shadow-hoffset-hover',
					),
				),
				'iconShadowVOffsetHover'           =>
				array(
					'type'         => 'number',
					'default'      => 0,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-shadow-voffset-hover',
					),
				),
				'iconShadowBlurHover'              =>
				array(
					'type'         => 'number',
					'default'      => 0,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-shadow-blur-hover',
					),
				),
				'iconBoxShadowColorHover'          =>
				array(
					'type'         => 'string',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-box-shadow-color-hover',
					),
				),
				'iconBoxShadowHOffsetHover'        =>
				array(
					'type'         => 'number',
					'default'      => 0,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-box-shadow-hoffset-hover',
					),
				),
				'iconBoxShadowVOffsetHover'        =>
				array(
					'type'         => 'number',
					'default'      => 0,
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-box-shadow-voffset-hover',
					),
				),
				'iconBoxShadowBlurHover'           =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-box-shadow-blur-hover',
					),
				),
				'iconBoxShadowSpreadHover'         =>
				array(
					'type'         => 'number',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-box-shadow-spread-hover',
					),
				),
				'iconBoxShadowPositionHover'       =>
				array(
					'type'         => 'string',
					'default'      => 'outset',
					'UAGCopyPaste' =>
					array(
						'styleType' => 'icon-box-shadow-position-hover',
					),
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
