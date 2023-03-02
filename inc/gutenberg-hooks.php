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
	 * @var array
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
			'contact-form',
			'newsletter-form',
		];

		// Initializing hooks.
		add_action( 'enqueue_block_editor_assets', [ $this, 'form_editor_screen_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'block_editor_assets' ] );
		add_action( 'block_categories_all', [ $this, 'register_block_categories' ] );
		add_action( 'init', [ $this, 'register_block_patterns' ], 9 );
	}

	/**
	 * Register our custom block category.
	 *
	 * @param array $categories Array of categories.
	 * @return array
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
		 * @param array $patterns List of block patterns by name.
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
		$script_name = 'editor';

		$screen     = get_current_screen();
		$post_types = array( SUREFORMS_FORMS_POST_TYPE );

		if ( is_null( $screen ) || ! in_array( $screen->post_type, $post_types, true ) ) {
			return;
		}

		$script_asset_path = SUREFORMS_DIR . 'assets/build/' . $script_name . '.asset.php';
		$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: array(
				'dependencies' => [],
				'version'      => SUREFORMS_VER,
			);
		wp_enqueue_script( 'sureforms-' . $script_name, SUREFORMS_URL . 'assets/build/editor.js', $script_info['dependencies'], SUREFORMS_VER, true );

		wp_localize_script(
			'sureforms-' . $script_name,
			'sfBlockData',
			[
				'plugin_url' => SUREFORMS_URL,
			]
		);

		wp_enqueue_style( 'sureforms-' . $script_name, SUREFORMS_URL . 'assets/build/editor.css', [], SUREFORMS_VER, 'all' );
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
				'plugin_url' => SUREFORMS_URL,
			]
		);
	}
}
