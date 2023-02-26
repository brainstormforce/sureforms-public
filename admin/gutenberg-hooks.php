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
	}

	/**
	 * Add Editor Scripts.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function add_editor_assets() {
		$script_name       = 'editor';
		$script_asset_path = SUREFORMS_DIR . 'assets/build/' . $script_name . '.asset.php';
		$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: array(
				'dependencies' => [],
				'version'      => SUREFORMS_VER,
			);
		wp_enqueue_script( 'sureforms-' . $script_name, SUREFORMS_URL . 'assets/build/editor.js', $script_info['dependencies'], SUREFORMS_VER, true );
	}
}
