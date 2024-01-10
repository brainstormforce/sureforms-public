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
		public function register_blocks() {}

		/**
		 * Render CF HTML.
		 *
		 * @param array<mixed> $attributes Array of block attributes.
		 *
		 * @since 0.0.1
		 *
		 * @return string|false
		 */
		public function render_html( $attributes ) {}

	}

	/**
	 *  Prepare if class 'Sureforms_Advanced_Image' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	Sureforms_Advanced_Image::get_instance();
}
