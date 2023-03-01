<?php
/**
 * Gutenberg Hooks Manager Class.
 *
 * @package sureforms.
 */

namespace SureForms\Admin;

use SureForms\Inc\Traits\Get_Instance;
/**
 * Gutenberg hooks handler class.
 *
 * @since X.X.X
 */
class Gutenberg_Hooks {

	use Get_Instance;

	/**
	 * Class constructor.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function __construct() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'add_editor_assets' ] );
		add_action( 'block_categories_all', [ $this, 'register_block_categories' ] );
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
	 * Add Editor Scripts.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function add_editor_assets() {
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

		wp_enqueue_style( 'sureforms-' . $script_name, SUREFORMS_URL . 'assets/build/editor.css', [], SUREFORMS_VER, 'all' );

		$block_scripts = 'blocks';

		$blocks_asset_path = SUREFORMS_DIR . 'assets/build/' . $script_name . '.asset.php';
		$blocks_info       = file_exists( $blocks_asset_path )
			? include $blocks_asset_path
			: array(
				'dependencies' => [],
				'version'      => SUREFORMS_VER,
			);
		wp_enqueue_script( 'sureforms-' . $block_scripts, SUREFORMS_URL . 'assets/build/blocks.js', $blocks_info['dependencies'], SUREFORMS_VER, true );
	}
}
