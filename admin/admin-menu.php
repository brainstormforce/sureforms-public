<?php
/**
 * Admin Menu Class.
 *
 * @package sureforms.
 */

namespace SureForms\Admin;

use SureForms\Inc\Traits\Get_Instance;
/**
 * Admin Menu handler class.
 *
 * @since X.X.X
 */
class Admin_Menu {

	use Get_Instance;

	/**
	 * Class constructor.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
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
}
