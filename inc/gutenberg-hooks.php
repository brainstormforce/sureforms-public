<?php
/**
 * Gutenberg Hooks Manager Class.
 *
 * @package sureforms.
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;
/**
 * Gutenberg hooks handler class.
 *
 * @since X.X.X
 */
class Gutenberg_Hooks {

	/**
	 * Block patterns to register.
	 *
	 * @var array<mixed>
	 */
	protected $patterns = [];

	use Get_Instance;

	/**
	 * Class constructor.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function __construct() {
		// Setting Form default patterns.
		$this->patterns = [
			'blank-form',
			'contact-form',
			'it-service-ticket',
			'rsvp-form',
			'information-request-form',
			'seo-request-form',
			'medical-history-form',
			'customer-satisfaction-survey',
		];

		// Initializing hooks.
		add_action( 'enqueue_block_editor_assets', [ $this, 'form_editor_screen_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'block_editor_assets' ] );
		add_filter( 'block_categories_all', [ $this, 'register_block_categories' ], 10, 1 );
		add_action( 'init', [ $this, 'register_block_patterns' ], 9 );
		// TODO: Need to check this later.
		// add_filter( 'allowed_block_types_all', [ $this, 'disable_forms_wrapper_block' ], 10, 2 );.
	}

	/**
	 * Register our custom block category.
	 *
	 * @param array<mixed> $categories Array of categories.
	 * @return array<mixed>
	 * @since X.X.X
	 */
	public function register_block_categories( $categories ) {
		return [
			...[
				[
					'slug'  => 'sureforms',
					'title' => esc_html__( 'SureForms', 'sureforms' ),
				],
			],
			...$categories,
		];
	}

	/**
	 * Register our block patterns.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function register_block_patterns() {
		/**
		 * Filters the plugin block patterns.
		 *
		 * @param array<mixed> $patterns List of block patterns by name.
		 */
		$this->patterns = apply_filters( 'sureforms_block_patterns', $this->patterns );

		// loop through patterns and register.
		foreach ( $this->patterns as $block_pattern ) {
			$pattern_file = plugin_dir_path( SUREFORMS_FILE ) . 'templates/forms/' . $block_pattern . '.php';
			if ( is_readable( $pattern_file ) ) {
				register_block_pattern(
					'sureforms/' . $block_pattern,
					require $pattern_file
				);
			}
		}
	}

	/**
	 * Add Form Editor Scripts.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function form_editor_screen_assets() {
		$form_editor_script = 'formEditor';

		$screen     = get_current_screen();
		$post_types = array( SUREFORMS_FORMS_POST_TYPE );

		if ( is_null( $screen ) || ! in_array( $screen->post_type, $post_types, true ) ) {
			return;
		}

		$script_asset_path = SUREFORMS_DIR . 'assets/build/' . $form_editor_script . '.asset.php';
		$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: array(
				'dependencies' => [],
				'version'      => SUREFORMS_VER,
			);
		wp_enqueue_script( 'sureforms-' . $form_editor_script, SUREFORMS_URL . 'assets/build/' . $form_editor_script . '.js', $script_info['dependencies'], SUREFORMS_VER, true );

		wp_localize_script(
			'sureforms-' . $form_editor_script,
			'sfBlockData',
			[
				'plugin_url' => SUREFORMS_URL,
			]
		);
	}

	/**
	 * Register all editor scripts.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function block_editor_assets() {
		$all_screen_blocks = 'blocks';

		$blocks_asset_path = SUREFORMS_DIR . 'assets/build/' . $all_screen_blocks . '.asset.php';
		$blocks_info       = file_exists( $blocks_asset_path )
			? include $blocks_asset_path
			: array(
				'dependencies' => [],
				'version'      => SUREFORMS_VER,
			);
		wp_enqueue_script( 'sureforms-' . $all_screen_blocks, SUREFORMS_URL . 'assets/build/' . $all_screen_blocks . '.js', $blocks_info['dependencies'], SUREFORMS_VER, true );

		wp_localize_script(
			'sureforms-' . $all_screen_blocks,
			'sfBlockData',
			[
				'plugin_url'  => SUREFORMS_URL,
				'admin_email' => get_option( 'admin_email' ),

			]
		);

		$formats = array();
		$mimes   = get_allowed_mime_types();
		$maxsize = wp_max_upload_size() / 1048576;
		if ( ! empty( $mimes ) ) {
			foreach ( $mimes as $type => $mime ) {
				$multiple = explode( '|', $type );
				foreach ( $multiple as $single ) {
					$formats[] = $single;
				}
			}
		}

		wp_localize_script(
			'sureforms-' . $all_screen_blocks,
			'upload_field',
			array(
				'upload_formats'   => $formats,
				'upload_max_limit' => $maxsize,
			)
		);
	}
}
