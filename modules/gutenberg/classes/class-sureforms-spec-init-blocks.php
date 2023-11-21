<?php
/**
 * Sureforms Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since 0.0.1
 * @package Sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms_Spec_Init_Blocks.
 *
 * @package Sureforms
 */
class Sureforms_Spec_Init_Blocks {

	/**
	 * Member Variable
	 *
	 * @var instance
	 */
	private static $instance;

	/**
	 * Store Json variable
	 *
	 * @since 0.0.1
	 * @var instance
	 */
	public static $icon_json;

	/**
	 * As our svg icon is too long array so we will divide that into number of icon chunks.
	 *
	 * @var int
	 * @since 0.0.1
	 */
	public static $number_of_icon_chunks = 4;

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

		// Hook: Frontend assets.
		add_action( 'enqueue_block_assets', array( $this, 'block_assets' ) );

		// Hook: Editor assets.
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_assets' ) );

	}

	/**
	 * Enqueue Gutenberg block assets for both frontend + backend.
	 *
	 * @since 0.0.1
	 */
	public function block_assets() {

		global $post;

		if ( $post && SUREFORMS_FORMS_POST_TYPE === $post->post_type ) {

			// Register block styles for both frontend + backend.
			wp_enqueue_style(
				'SRFM_block-sureforms-style-css', // Handle.
				SUREFORMS_URL . 'modules/gutenberg/build/style-blocks.css',
				is_admin() ? array( 'wp-editor' ) : null, // Dependency to include the CSS after it.
				SUREFORMS_VER // filemtime( plugin_dir_path( __DIR__ ) . 'build/style-blocks.css' ) // Version: File modification time.
			);

		}
	}

	/**
	 * Enqueue assets for both backend.
	 *
	 * @since 0.0.1
	 */
	public function editor_assets() {

		$post_id   = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_type = get_post_type( $post_id );

		if ( SUREFORMS_FORMS_POST_TYPE === $post_type ) {

			$script_dep_path = SUREFORMS_DIR . 'modules/gutenberg/build/blocks.asset.php';
			$script_info     = file_exists( $script_dep_path )
				? include $script_dep_path
				: array(
					'dependencies' => array(),
					'version'      => SUREFORMS_VER,
				);
			$script_dep      = array_merge( $script_info['dependencies'], array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ) );
			$script_ver      = $script_info['version'];

			// Register block editor script for backend.
			wp_enqueue_script(
				'SRFM_block-sureforms-block-js', // Handle.
				SUREFORMS_URL . 'modules/gutenberg/build/blocks.js',
				$script_dep, // Dependencies, defined above.
				$script_ver, // Version: filemtime — Gets file modification time.
				true // Enqueue the script in the footer.
			);

			wp_set_script_translations( 'SRFM_block-sureforms-block-js', 'sureforms' );

			// Register block editor styles for backend.
			wp_enqueue_style(
				'SRFM_block-sureforms-block-editor-css', // Handle.
				SUREFORMS_URL . 'modules/gutenberg/build/blocks.style.css',
				array( 'wp-edit-blocks' ), // Dependency to include the CSS after it.
				SUREFORMS_VER // Version: File modification time.
			);

			// Common Editor style.
			wp_enqueue_style(
				'SRFM_block-common-editor-css', // Handle.
				SUREFORMS_URL . 'modules/gutenberg/dist/editor.css',
				array( 'wp-edit-blocks' ), // Dependency to include the CSS after it.
				SUREFORMS_VER // Version: File modification time.
			);

		}
	}
}

/**
 *  Prepare if class 'Sureforms_Spec_Init_Blocks' exist.
 *  Kicking this off by calling 'get_instance()' method
 */
Sureforms_Spec_Init_Blocks::get_instance();
