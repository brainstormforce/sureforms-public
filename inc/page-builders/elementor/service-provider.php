<?php
/**
 * Elementor service provider.
 *
 * @package sureforms.
 * @since 0.0.5
 */

namespace SRFM\Inc\Page_Builders\Elementor;

use SRFM\Inc\Traits\Get_Instance;

/**
 * Elementor service provider.
 */
class Service_Provider {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * Load Elementor integration.
	 *
	 * @since 0.0.5
	 * @return void
	 */
	public function __construct() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		add_action( 'elementor/widgets/register', [ $this, 'widget' ] );
		add_action( 'elementor/elements/categories_registered', [ $this, 'categories_registered' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'load_scripts' ] );
	}

	/**
	 * Elementor load scripts
	 *
	 * @since 0.0.5
	 * @return void
	 */
	public function load_scripts() {
		wp_enqueue_script( 'sureforms-elementor-editor', plugins_url( 'assets/editor.js', __FILE__ ), [], SRFM_VER, true );
		wp_enqueue_style( 'sureforms-elementor-style', plugins_url( 'assets/editor.css', __FILE__ ), [], SRFM_VER, 'all' );
		wp_localize_script(
			'sureforms-elementor-editor',
			'srfmElementorData',
			[
				'admin_url'        => admin_url(),
				'add_new_form_url' => admin_url( 'admin.php?page=add-new-form' ),
			]
		);
	}

	/**
	 * Elementor surecart categories register
	 *
	 * @param object $elements_manager Elementor category manager.
	 * @since 0.0.5
	 * @return void
	 */
	public function categories_registered( $elements_manager ) {

		if ( ! method_exists( $elements_manager, 'add_category' ) ) {
			return;
		}

		$elements_manager->add_category(
			'sureforms-elementor',
			[
				'title' => esc_html__( 'SureForms', 'sureforms' ),
				'icon'  => 'fa fa-plug',
			]
		);
	}

	/**
	 * Elementor widget register
	 *
	 * @param object $widgets_manager Elementor widget manager.
	 * @since 0.0.5
	 * @return void
	 */
	public function widget( $widgets_manager ) {
		if ( ! method_exists( $widgets_manager, 'register' ) ) {
			return;
		}
		$widgets_manager->register( new Form_Widget() );
	}

}
