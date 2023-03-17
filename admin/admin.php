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
	 * Enqueue Admin Scripts.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'sureforms-admin', SUREFORMS_URL . 'assets/build/admin.css', [], SUREFORMS_VER, 'all' );
	}
}
