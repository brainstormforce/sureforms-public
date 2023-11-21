<?php
/**
 * Sureforms - Separator.
 *
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Sureforms_Spec_Separator' ) ) {

	/**
	 * Class Sureforms_Spec_Separator.
	 */
	class Sureforms_Spec_Separator {

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
					'type'    => 'string',
					'default' => 'center',
				),
				'separatorAlignTablet'           =>
				array(
					'type'    => 'string',
					'default' => 'center',
				),
				'separatorAlignMobile'           =>
				array(
					'type'    => 'string',
					'default' => 'center',
				),
				'separatorStyle'                 =>
				array(
					'type'    => 'string',
					'default' => 'solid',
				),
				'separatorWidth'                 =>
				array(
					'type'    => 'number',
					'default' => 100,
				),
				'separatorWidthTablet'           =>
				array(
					'type'    => 'number',
					'default' => 100,
				),
				'separatorWidthMobile'           =>
				array(
					'type'    => 'number',
					'default' => 100,
				),
				'separatorWidthType'             =>
				array(
					'type'    => 'string',
					'default' => '%',
				),
				'separatorBorderHeight'          =>
				array(
					'type'    => 'number',
					'default' => 3,
				),
				'separatorBorderHeightMobile'    =>
				array(
					'type'    => 'number',
					'default' => 3,
				),
				'separatorBorderHeightTablet'    =>
				array(
					'type'    => 'number',
					'default' => 3,
				),
				'separatorBorderHeightUnit'      =>
				array(
					'type'    => 'string',
					'default' => 'px',
				),
				'separatorSize'                  =>
				array(
					'type'    => 'number',
					'default' => 5,
				),
				'separatorSizeMobile'            =>
				array(
					'type'    => 'number',
					'default' => 5,
				),
				'separatorSizeTablet'            =>
				array(
					'type'    => 'number',
					'default' => 5,
				),
				'separatorSizeType'              =>
				array(
					'default' => 'px',
				),
				'separatorColor'                 =>
				array(
					'type'    => 'string',
					'default' => '#333',
				),
				'separatorHeight'                =>
				array(
					'type'    => 'number',
					'default' => 10,
				),
				'separatorHeightMobile'          =>
				array(
					'type'    => 'number',
					'default' => 10,
				),
				'separatorHeightTablet'          =>
				array(
					'type'    => 'number',
					'default' => 10,
				),
				'separatorHeightType'            =>
				array(
					'type'    => 'string',
					'default' => 'px',
				),
				'separatorBottomPadding'         =>
				array(
					'type' => 'number',
				),
				'separatorPaddingTopTablet'      =>
				array(
					'type' => 'number',
				),
				'separatorPaddingRightTablet'    =>
				array(
					'type' => 'number',
				),
				'separatorPaddingBottomTablet'   =>
				array(
					'type' => 'number',
				),
				'separatorPaddingLeftTablet'     =>
				array(
					'type' => 'number',
				),
				'separatorPaddingTopMobile'      =>
				array(
					'type' => 'number',
				),
				'separatorPaddingRightMobile'    =>
				array(
					'type' => 'number',
				),
				'separatorPaddingBottomMobile'   =>
				array(
					'type' => 'number',
				),
				'separatorPaddingLeftMobile'     =>
				array(
					'type' => 'number',
				),
				'separatorPaddingUnit'           =>
				array(
					'type'    => 'number',
					'default' => 'px',
				),
				'separatorMobilePaddingUnit'     =>
				array(
					'type'    => 'number',
					'default' => 'px',
				),
				'separatorTabletPaddingUnit'     =>
				array(
					'type'    => 'number',
					'default' => 'px',
				),
				'separatorPaddingLink'           =>
				array(
					'type'    => 'boolean',
					'default' => true,
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
					'type'    => 'string',
					'default' => 'h4',
				),
				'separatorIcon'                  =>
				array(
					'type'    => 'string',
					'default' => 'circle-check',
				),
				'elementPosition'                =>
				array(
					'type'    => 'string',
					'default' => 'center',
				),
				'elementSpacing'                 =>
				array(
					'type'    => 'number',
					'default' => 15,
				),
				'elementSpacingTablet'           =>
				array(
					'type'    => 'number',
					'default' => 15,
				),
				'elementSpacingMobile'           =>
				array(
					'type'    => 'number',
					'default' => 15,
				),
				'elementSpacingUnit'             =>
				array(
					'type'    => 'string',
					'default' => 'px',
				),
				'elementTextLoadGoogleFonts'     =>
				array(
					'type'    => 'boolean',
					'default' => false,
				),
				'elementTextFontFamily'          =>
				array(
					'type'    => 'string',
					'default' => 'Default',
				),
				'elementTextFontWeight'          =>
				array(
					'type' => 'string',
				),
				'elementTextFontSize'            =>
				array(
					'type' => 'number',
				),
				'elementTextFontSizeType'        =>
				array(
					'type'    => 'string',
					'default' => 'px',
				),
				'elementTextFontSizeTablet'      =>
				array(
					'type' => 'number',
				),
				'elementTextFontSizeMobile'      =>
				array(
					'type' => 'number',
				),
				'elementTextLineHeightType'      =>
				array(
					'type'    => 'string',
					'default' => 'em',
				),
				'elementTextLineHeight'          =>
				array(
					'type'    => 'number',
					'default' => 1,
				),
				'elementTextLineHeightTablet'    =>
				array(
					'type'    => 'number',
					'default' => 1,
				),
				'elementTextLineHeightMobile'    =>
				array(
					'type'    => 'number',
					'default' => 1,
				),
				'elementTextFontStyle'           =>
				array(
					'type'    => 'string',
					'default' => 'normal',
				),
				'elementTextLetterSpacing'       =>
				array(
					'type' => 'number',
				),
				'elementTextLetterSpacingTablet' =>
				array(
					'type' => 'number',
				),
				'elementTextLetterSpacingMobile' =>
				array(
					'type' => 'number',
				),
				'elementTextLetterSpacingType'   =>
				array(
					'type'    => 'string',
					'default' => 'px',
				),
				'elementTextDecoration'          =>
				array(
					'type' => 'string',
				),
				'elementTextTransform'           =>
				array(
					'type' => 'string',
				),
				'elementColor'                   =>
				array(
					'type' => 'string',
				),
				'elementIconWidth'               =>
				array(
					'type'    => 'number',
					'default' => 30,
				),
				'elementIconWidthTablet'         =>
				array(
					'type'    => 'number',
					'default' => 30,
				),
				'elementIconWidthMobile'         =>
				array(
					'type'    => 'number',
					'default' => 30,
				),
				'elementIconWidthType'           =>
				array(
					'type'    => 'string',
					'default' => 'px',
				),
			);

			$attributes = apply_filters( 'sureforms_gutenberg_separator_attributes_filters', $attr );

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

			$block_id = '';

			if ( isset( $attributes['block_id'] ) ) {
				$block_id = $attributes['block_id'];
			}

			$custom_svg = array(
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
			$svg_pattern        = isset( $custom_svg[ $separator_style ] ) ? $custom_svg[ $separator_style ] : '';

			$main_classes = array(
				'wp-block-uagb-separator',
				'uagb-block-' . $block_id,
				$element_type_css,
			);

			if ( isset( $attributes['className'] ) ) {
				$main_classes[] = $attributes['className'];
			}

			$custom_tag = '<' . $separator_text_tag . ' class="uagb-html-tag">' . $attributes['separatorText'] . '</' . $separator_text_tag . '>';

			ob_start();
			?>
			<div class = "<?php echo esc_attr( implode( ' ', $main_classes ) ); ?>" style="--my-background-image: <?php echo esc_attr( $svg_pattern ); ?>;" >
				<div class="wp-block-uagb-separator__inner">
					<div class="wp-block-uagb-separator-element">
						<?php
						if ( 'icon' === $element_type ) {
							Sureforms_Spec_Gb_Helper::render_svg_html( $separator_icon );
						} elseif ( 'text' === $element_type ) {
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
	 *  Prepare if class 'Sureforms_Spec_Separator' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	Sureforms_Spec_Separator::get_instance();
}
