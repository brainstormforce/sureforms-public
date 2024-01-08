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
		public function register_blocks() {}

		/**
		 * Render CF HTML.
		 *
		 * @param array $attributes Array of block attributes.
		 *
		 * @since 0.0.1
		 */
		public function render_html( $attributes ) {}
	}

	/**
	 *  Prepare if class 'Sureforms_Advanced_Heading' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	Sureforms_Advanced_Heading::get_instance();
}
