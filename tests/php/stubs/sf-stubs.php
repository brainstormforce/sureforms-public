<?php

namespace SureForms {
	/**
	 * Plugin_Loader
	 *
	 * @since X.X.X
	 */
	class Plugin_Loader {

		/**
		 * Initiator
		 *
		 * @since X.X.X
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
		 * @since X.X.X
		 */
		public function __construct() {
		}
		/**
		 * Load Classes.
		 *
		 * @return void
		 * @since X.X.X
		 */
		public function load_classes() {
		}
		/**
		 * Load Plugin Text Domain.
		 * This will load the translation textdomain depending on the file priorities.
		 *      1. Global Languages /wp-content/languages/sureforms/ folder
		 *      2. Local directory /wp-content/plugins/sureforms/languages/ folder
		 *
		 * @since X.X.X
		 * @return void
		 */
		public function load_textdomain() {
		}
	}
}

namespace SureForms\Inc\Blocks {
	/**
	 * Block base class.
	 */
	abstract class Base {

		/**
		 * Optional directory to .json block data files.
		 *
		 * @var string
		 * @since X.X.X
		 */
		protected $directory = '';
		/**
		 * Holds the block.
		 *
		 * @var object
		 * @since X.X.X
		 */
		protected $block;
		/**
		 * Register the block for dynamic output
		 *
		 * @return void
		 * @since X.X.X
		 */
		public function register() {
		}
		/**
		 * Get the called class directory path
		 *
		 * @return string
		 * @since X.X.X
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
		 * @since X.X.X
		 */
		public function pre_render( $attributes, $content, $block ) {
		}
		/**
		 * Run any block middleware before rendering.
		 *
		 * @param array  $attributes Block attributes.
		 * @param string $content   Post content.
		 * @return boolean|\WP_Error;
		 * @since X.X.X
		 */
		protected function middleware( $attributes, $content ) {
		}
		/**
		 * Allows filtering of attributes before rendering.
		 *
		 * @param array $attributes Block attributes.
		 * @return array $attributes
		 * @since X.X.X
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
		 * @since X.X.X
		 */
		public function render( $attributes, $content ) {
		}
	}
}

namespace SureForms\Inc\Traits {
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
		 * @since X.X.X
		 * @return object initialized object of class.
		 */
		public static function get_instance() {
		}
	}
}

namespace SureForms\Inc {
	/**
	 * Post Types Main Class.
	 *
	 * @since X.X.X
	 */
	class Post_Types {

		use \SureForms\Inc\Traits\Get_Instance;
		/**
		 * Constructor
		 *
		 * @since  X.X.X
		 */
		public function __construct() {
		}
		/**
		 * Registers the forms and submissions post types.
		 *
		 * @return void
		 * @since X.X.X
		 */
		public function register_post_types() {
		}
	}
}

namespace SureForms\Admin {
	/**
	 * Gutenberg hooks handler class.
	 *
	 * @since X.X.X
	 */
	class Gutenberg_Hooks {

		use \SureForms\Inc\Traits\Get_Instance;
		/**
		 * Class constructor.
		 *
		 * @return void
		 * @since X.X.X
		 */
		public function __construct() {
		}
		/**
		 * Add Editor Scripts.
		 *
		 * @return void
		 * @since X.X.X
		 */
		public function add_editor_assets() {
		}
	}
	/**
	 * Admin handler class.
	 *
	 * @since X.X.X
	 */
	class Admin {

		use \SureForms\Inc\Traits\Get_Instance;
		/**
		 * Class constructor.
		 *
		 * @return void
		 * @since X.X.X
		 */
		public function __construct() {
		}
		/**
		 * Add menu page.
		 *
		 * @return void
		 * @since X.X.X
		 */
		public function add_menu_page() {
		}
		/**
		 * Enqueue Admin Scripts.
		 *
		 * @return void
		 * @since X.X.X
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
	\define( 'SUREFORMS_FILE', __FILE__ );
	\define( 'SUREFORMS_BASE', \plugin_basename( \SUREFORMS_FILE ) );
	\define( 'SUREFORMS_DIR', \plugin_dir_path( \SUREFORMS_FILE ) );
	\define( 'SUREFORMS_URL', \plugins_url( '/', \SUREFORMS_FILE ) );
	\define( 'SUREFORMS_VER', '0.0.1' );
	// ------ ADDITIONAL CONSTANTS ------- //
	\define( 'SUREFORMS_FORMS_POST_TYPE', 'sureforms_form' );
	\define( 'SUREFORMS_ENTRIES_POST_TYPE', 'sureforms_entry' );
}
