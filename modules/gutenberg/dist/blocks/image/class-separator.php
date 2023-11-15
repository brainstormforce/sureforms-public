<?php
/**
 * WCFB - Checkout Form Styler.
 *
 * @package WCFB
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCFB_Image' ) ) {

	/**
	 * Class WCFB_Separator.
	 */
	class WCFB_Image {

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
				'block_id'                => array(
					'type'    => 'string',
					'default' => '',
				),

			);

			// $field_border_attr = Cartflows_Gb_Helper::get_instance()->generate_php_border_attribute( 'field' );
			// $btn_border_attr = Cartflows_Gb_Helper::get_instance()->generate_php_border_attribute( 'button' );

			//$attr = array_merge( $field_border_attr, $btn_border_attr, $attr );

			$attributes = apply_filters( 'cartflows_gutenberg_cf_attributes_filters', $attr );

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
		 * @param array $attributes Array of block attributes.
		 *
		 * @since 1.6.15
		 */
		public function render_html( $attributes ) {

			// $custom_svg = array(
			// 	'rectangles' => 'url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M6.4 0H16L9.6 16H0L6.4 0Z' fill='black'/%3E%3C/svg%3E")',
		
			// 	parallelogram: `url("data:image/svg+xml,%3Csvg width='8' height='16' viewBox='0 0 8 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='8' height='16' fill='black'/%3E%3C/svg%3E")`,
		
			// 	slash: `url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M6.29312 16.9999L17 6.29302M14.2931 16.9999L17 14.293M-0.707031 15.9999L16.0002 -0.707153M8.00017 -0.707153L-0.706882 7.9999' stroke='black'/%3E%3C/svg%3E")`,
		
			// 	leaves: `url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cg clip-path='url(%23clip0_2356_5631)'%3E%3Cpath d='M15 1C10.5 1 9 2.5 9 7C13.5 7 15 5.5 15 1Z' stroke='black'/%3E%3Cpath d='M1 1C5.5 1 7 2.5 7 7C2.5 7 1 5.5 1 1Z' stroke='black'/%3E%3Cpath d='M15 15C10.5 15 9 13.5 9 9C13.5 9 15 10.5 15 15Z' stroke='black'/%3E%3Cpath d='M1 15C5.5 15 7 13.5 7 9C2.5 9 1 10.5 1 15Z' stroke='black'/%3E%3C/g%3E%3Cdefs%3E%3CclipPath id='clip0_2356_5631'%3E%3Crect width='16' height='16' fill='white'/%3E%3C/clipPath%3E%3C/defs%3E%3C/svg%3E")`,
			// };

			$advanced_classes = Cartflows_Gb_Helper::get_instance()->generate_advanced_setting_classes( $attributes );

			$zindex_wrap = $advanced_classes[ 'zindex_wrap'];

			$element_type = isset($attributes['elementType']) && $attributes['elementType'] !== 'none'	? 'wp-block-uagb-separator--' . $attributes['elementType'] : '' ;
			$separator_text_tag = isset($attributes['separatorTextTag']) ? $attributes['separatorTextTag'] : '';
			$main_classes = array(
				'wp-block-uagb-separator',
				'cf-block-' . $attributes['block_id'],
				$element_type,
				$advanced_classes[ 'desktop_class'],
				$advanced_classes[ 'tab_class'],
				$advanced_classes[ 'mob_class'],
				$advanced_classes[ 'zindex_extention_enabled'] ? 'uag-blocks-common-selector' : '',
			);

			if ( isset( $attributes['className'] ) ) {
				$main_classes[] = $attributes['className'];
			}

			$custom_tag = '<'. $separator_text_tag .' class="uagb-html-tag">'. $attributes['separatorText'] .'</'. $separator_text_tag .'>';

			ob_start();
			?>
			<div class = "<?php echo esc_attr( implode( ' ', $main_classes ) ); ?>">
				Testing
			</div>
			<?php
			return ob_get_clean();
		}
	}

	/**
	 *  Prepare if class 'WCFB_Checkout_Form' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	WCFB_Image::get_instance();
}
