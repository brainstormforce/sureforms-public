<?php

namespace SRFM {
	/**
	 * Plugin_Loader
	 *
	 * @since 0.0.1
	 */
	class SRFM_Plugin_Loader {

		/**
		 * Initiator
		 *
		 * @since 0.0.1
		 * @return object initialized object of class.
		 */
		public static function get_instance() {
		}
		/**
		 * Autoload classes.
		 *
		 * @param string $class class name.
		 * @return void
		 */
		public function autoload( $class ) {
		}
		/**
		 * Constructor
		 *
		 * @since 0.0.1
		 */
		public function __construct() {
		}
		/**
		 * Load Classes.
		 *
		 * @return void
		 * @since 0.0.1
		 */
		public function load_classes() {
		}
		/**
		 * Load Plugin Text Domain.
		 * This will load the translation textdomain depending on the file priorities.
		 *      1. Global Languages /wp-content/languages/sureforms/ folder
		 *      2. Local directory /wp-content/plugins/sureforms/languages/ folder
		 *
		 * @since 0.0.1
		 * @return void
		 */
		public function load_textdomain() {
		}
	}
}

namespace SRFM\Inc\Blocks {
	/**
	 * Block base class.
	 */
	abstract class Base {

		/**
		 * Optional directory to .json block data files.
		 *
		 * @var string
		 * @since 0.0.1
		 */
		protected $directory = '';
		/**
		 * Holds the block.
		 *
		 * @var object
		 * @since 0.0.1
		 */
		protected $block;
		/**
		 * Register the block for dynamic output
		 *
		 * @return void
		 * @since 0.0.1
		 */
		public function register() {
		}
		/**
		 * Get the called class directory path
		 *
		 * @return string
		 * @since 0.0.1
		 */
		public function get_dir() {
		}
		/**
		 * Optionally run a function to modify attributes before rendering.
		 *
		 * @param array  $attributes Block attributes.
		 * @param string $content   Post content.
		 * @param array  $block Block attributes.
		 *
		 * @return function
		 * @since 0.0.1
		 */
		public function pre_render( $attributes, $content, $block ) {
		}
		/**
		 * Run any block middleware before rendering.
		 *
		 * @param array  $attributes Block attributes.
		 * @param string $content   Post content.
		 * @return boolean|\WP_Error;
		 * @since 0.0.1
		 */
		protected function middleware( $attributes, $content ) {
		}
		/**
		 * Allows filtering of attributes before rendering.
		 *
		 * @param array $attributes Block attributes.
		 * @return array $attributes
		 * @since 0.0.1
		 */
		public function get_attributes( $attributes ) {
		}
		/**
		 * Render the block
		 *
		 * @param array  $attributes Block attributes.
		 * @param string $content Post content.
		 *
		 * @return string
		 * @since 0.0.1
		 */
		public function render( $attributes, $content ) {
		}
	}
}

namespace SRFM\Inc\Traits {
	/**
	 * Trait Get_Instance.
	 */
	trait Get_Instance {

		/**
		 * Instance object.
		 *
		 * @var object Class Instance.
		 */
		private static $instance = null;
		/**
		 * Initiator
		 *
		 * @since 0.0.1
		 * @return object initialized object of class.
		 */
		public static function get_instance() {
		}
	}
}

namespace SRFM\Inc {
	/**
	 * Post Types Main Class.
	 *
	 * @since 0.0.1
	 */
	class SRFM_Post_Types {

		use \SRFM\Inc\Traits\Get_Instance;
		/**
		 * Constructor
		 *
		 * @since  0.0.1
		 */
		public function __construct() {
		}
		/**
		 * Registers the forms and submissions post types.
		 *
		 * @return void
		 * @since 0.0.1
		 */
		public function register_post_types() {
		}
	}
}

namespace SRFM\Admin {
	/**
	 * Gutenberg hooks handler class.
	 *
	 * @since 0.0.1
	 */
	class SRFM_Gutenberg_Hooks {

		use \SRFM\Inc\Traits\Get_Instance;
		/**
		 * Class constructor.
		 *
		 * @return void
		 * @since 0.0.1
		 */
		public function __construct() {
		}
		/**
		 * Add Editor Scripts.
		 *
		 * @return void
		 * @since 0.0.1
		 */
		public function add_editor_assets() {
		}
	}
	/**
	 * Admin handler class.
	 *
	 * @since 0.0.1
	 */
	class SRFM_Admin {

		use \SRFM\Inc\Traits\Get_Instance;
		/**
		 * Class constructor.
		 *
		 * @return void
		 * @since 0.0.1
		 */
		public function __construct() {
		}
		/**
		 * Add menu page.
		 *
		 * @return void
		 * @since 0.0.1
		 */
		public function add_menu_page() {
		}
		/**
		 * Enqueue Admin Scripts.
		 *
		 * @return void
		 * @since 0.0.1
		 */
		public function enqueue_scripts() {
		}
	}
}

namespace {
	/**
	 * Plugin Name: SureForms
	 * Description: A simple yet powerful way to create modern forms for your website.
	 * Author: SureCart
	 * Author URI: https://surecart.com
	 * Version: 0.0.1
	 * License: GPL v2
	 * Text Domain: sureforms
	 *
	 * @package sureforms
	 */
	/**
	 * Set constants
	 */
	\define( 'SRFM_FILE', __FILE__ );
	\define( 'SRFM_BASE', \plugin_basename( \SRFM_FILE ) );
	\define( 'SRFM_DIR', \plugin_dir_path( \SRFM_FILE ) );
	\define( 'SRFM_URL', \plugins_url( '/', \SRFM_FILE ) );
	\define( 'SRFM_VER', '0.0.1' );
	// ------ ADDITIONAL CONSTANTS ------- //
	\define( 'SRFM_FORMS_POST_TYPE', 'sureforms_form' );
	\define( 'SRFM_ENTRIES_POST_TYPE', 'sureforms_entry' );
}