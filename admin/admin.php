<?php
/**
 * Admin Class.
 *
 * @package sureforms.
 */

namespace SureForms\Admin;

use SureForms\Inc\Traits\Get_Instance;
/**
 * Admin handler class.
 *
 * @since X.X.X
 */
class Admin {

	use Get_Instance;

	/**
	 * Class constructor.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_menu', [ $this, 'sureforms_settings_page' ] );
		add_filter( 'plugin_action_links', [ $this, 'add_settings_link' ], 10, 2 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'sureforms_enqueue_styles' ] );
	}

	/**
	 * Add menu page.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function add_menu_page() {
		add_menu_page(
			__( 'SureForms', 'sureforms' ),
			__( 'SureForms', 'sureforms' ),
			'edit_others_posts',
			'sureforms_menu',
			function() {
				echo esc_html__( 'SureForms', 'sureforms' );
			},
			'dashicons-format-quote',
			30
		);
	}

	/**
	 * Add Settings page.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function sureforms_settings_page() {
		add_submenu_page(
			'sureforms_menu',
			__( 'Settings', 'sureforms' ),
			__( 'Settings', 'sureforms' ),
			'edit_others_posts',
			'sureforms_form_settings',
			[ $this, 'sureforms_settings_page_callback' ],
		);
	}

	/**
	 * My plugin submenu callback.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function sureforms_settings_page_callback() {
		echo '<div id="sureforms-settings-container"></div>';
	}


	/**
	 * Adds a settings link to the plugin action links on the plugins page.
	 *
	 * @param array  $links An array of plugin action links.
	 * @param string $file The plugin file path.
	 * @return array The updated array of plugin action links.
	 * @since X.X.X
	 */
	public function add_settings_link( $links, $file ) {
		if ( 'sureforms/sureforms.php' === $file ) {
			$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=sureforms_form_settings&tab=general-settings' ) ) . '">' . esc_html__( 'Settings', 'sureforms' ) . '</a>';
			array_push( $links, $settings_link );
		}
		return $links;
	}

	/**
	 * Sureforms block editor styles.
	 *
	 * @since X.X.X
	 */
	public function sureforms_enqueue_styles() {
		$current_screen = get_current_screen();
		if ( 'sureforms_form' === $current_screen->post_type ) {
			wp_enqueue_style( 'sureforms-block-styles', SUREFORMS_URL . 'assets/build/block_styles.css', [], SUREFORMS_VER, 'all' );
		}
	}


	/**
	 * Enqueue Admin Scripts.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'settings', SUREFORMS_URL . 'assets/build/settings.js', array( 'wp-components', 'wp-element', 'react-dom' ), SUREFORMS_VER, true );
		$current_screen = get_current_screen();
		if ( 'sureforms_page_sureforms_form_settings' === $current_screen->base ) {
			wp_enqueue_style( 'sureforms-setting-styles', SUREFORMS_URL . 'assets/build/settings.css', [ 'wp-components' ], SUREFORMS_VER, 'all' );
		}
		wp_enqueue_style( 'sureforms-admin', SUREFORMS_URL . 'assets/build/admin.css', [], SUREFORMS_VER, 'all' );
		wp_enqueue_style( 'sureforms-form-archive-styles', SUREFORMS_URL . 'assets/build/form_archive_styles.css', [], SUREFORMS_VER, 'all' );
		wp_enqueue_script( 'form-archive-script', SUREFORMS_URL . 'inc/form-archive-script.js', [], SUREFORMS_VER, true );
	}
}
