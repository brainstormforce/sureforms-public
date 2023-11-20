<?php
/**
 * Cartflows Blocks Initializer
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

		// add_action( 'enqueue_block_editor_assets', array( $this, 'add_gcp_vars_to_block_editor' ), 12 );

	}

	/**
	 * Enqueue the Global Color Pallet CSS vars to the page to use in the page builder settings.
	 * This CSS vars needs to be re-added so as to enqueue in the block editor to display the colors in the editor window.
	 *
	 * Note: Currently the GCP support is added for Elementor and Block Builder.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function add_gcp_vars_to_block_editor() {

		// Call the same function which generates the inline styles i:e the CSS VARs with the selected values.
		wcf()->flow->enqueue_gcp_color_vars( 'CF_block-cartflows-frontend-style' );

	}

	/**
	 * Enqueue Gutenberg block assets for both frontend + backend.
	 *
	 * @since 0.0.1
	 */
	public function block_assets() {

		global $post;

		// if ( $post && SUREFORMS_FORMS_POST_TYPE === $post->post_type ) { //Nathan

			// Register block styles for both frontend + backend.
			wp_enqueue_style(
				'CF_block-cartflows-style-css', // Handle.
				SUREFORMS_URL . 'modules/gutenberg/build/style-blocks.css',
				is_admin() ? array( 'wp-editor' ) : null, // Dependency to include the CSS after it.
				SUREFORMS_VER // filemtime( plugin_dir_path( __DIR__ ) . 'build/style-blocks.css' ) // Version: File modification time.
			);

			// $flow_id = wcf()->utils->get_flow_id_from_step_id( $post->ID );

			// // Return if no flow ID is found.
			// if ( empty( $flow_id ) ) {
			// return;
			// }

			// if ( Cartflows_Helper::is_gcp_styling_enabled( (int) $flow_id ) ) {

				// $gcp_vars = Cartflows_Helper::generate_gcp_css_style( (int) $flow_id );

				// Include the CSS/JS only if the CSS vars are set.
				// if ( ! empty( $gcp_vars ) ) {
					// Add editor helper css & JS files.
					wp_enqueue_style( 'wcf-editor-helper-style', SUREFORMS_URL . 'modules/gutenberg/assets/css/editor-assets.css', array( 'wp-edit-blocks', 'wp-editor' ), SUREFORMS_VER );
					wp_enqueue_script( 'wcf-editor-helper-script', SUREFORMS_URL . 'modules/gutenberg/assets/js/editor-assets.js', array( 'wp-editor', 'jquery' ), SUREFORMS_VER, true );
				// }
			// }
		// }
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
				'CF_block-cartflows-block-js', // Handle.
				SUREFORMS_URL . 'modules/gutenberg/build/blocks.js',
				$script_dep, // Dependencies, defined above.
				$script_ver, // Version: filemtime — Gets file modification time.
				true // Enqueue the script in the footer.
			);

			wp_set_script_translations( 'CF_block-cartflows-block-js', 'cartflows' );

			// Register block editor styles for backend.
			wp_enqueue_style(
				'CF_block-cartflows-block-editor-css', // Handle.
				SUREFORMS_URL . 'modules/gutenberg/build/blocks.style.css',
				array( 'wp-edit-blocks' ), // Dependency to include the CSS after it.
				SUREFORMS_VER // Version: File modification time.
			);

			// Common Editor style.
			wp_enqueue_style(
				'CF_block-common-editor-css', // Handle.
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
