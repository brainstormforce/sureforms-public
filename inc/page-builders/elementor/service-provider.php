<?php
/**
 * Elementor service provider.
 *
 * @package sureforms.
 * @since x.x.x
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
	 * @since x.x.x
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
	 * @return void
	 * @since x.x.x
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
	 *
	 * @return void
	 */
	public function categories_registered( $elements_manager ) {
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
	 * @return void
	 */
	public function widget( $widgets_manager ) {
		$widgets_manager->register( new Form_Widget() );
	}

}
