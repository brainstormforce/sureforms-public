<?php
namespace SRFM\Inc\Integrations\Elementor;

use SRFM\Inc\Traits\Get_Instance;

/**
 * Elementor service provider.
 */
class Elementor_Service_Provider {
	use Get_Instance;

	/**
	 * {@inheritDoc}
	 *
	 * @param  \Pimple\Container $container Service Container.
	 */
	public function __construct() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		// Elementor integration.
		add_action( 'elementor/widgets/register', [ $this, 'widget' ] );
		add_action( 'elementor/elements/categories_registered', [ $this, 'categories_registered' ] );
	}

	/**
	 * Elementor surecart categories register
	 *
	 * @param Obj $elements_manager Elementor category manager.
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
	 * @return void
	 */
	public function widget( $widgets_manager ) {
		$widgets_manager->register( new Form_Widget() );
	}

}
