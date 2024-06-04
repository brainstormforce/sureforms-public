<?php
/**
 * Elementor form widget.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc\Integrations\Elementor;

use Elementor\Widget_Base;
use SRFM\Inc\Generate_Form_Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms form form widget.
 *
 * SureForms widget that displays a form.
 */
class Form_Widget extends Widget_Base {
	/**
	 * Get widget name.
	 *
	 * Retrieve form widget name.
	 *
	 * @since x.x.x
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'sureforms_form';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve form widget title.
	 *
	 * @since x.x.x
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Form', 'sureforms' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve form widget icon.
	 *
	 * @since x.x.x
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-form-horizontal surecart-checkout-icon';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the form widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * @since x.x.x
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'sureforms-elementor' ];
	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the widget belongs to.
	 *
	 * @since x.x.x
	 * @access public
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [ 'form', 'sureforms', 'contact' ];
	}

	/**
	 * Register form widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since x.x.x
	 * @access protected
	 */
	protected function _register_controls() { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		$this->start_controls_section(
			'section_form',
			[
				'label' => __( 'Form', 'sureforms' ),
			]
		);

		$options = $this->get_forms_options();

		$this->add_control(
			'sf_form_block',
			[
				'label'   => __( 'Select Form', 'sureforms' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => $options,
			]
		);

		$this->add_control(
			'sf_edit_form',
			[
				'label' => __( 'Edit Form', 'sureforms' ),
				'type'  => \Elementor\Controls_Manager::BUTTON,
				'text'  => __( 'Edit', 'sureforms' ),
				'event' => 'sureforms:form:edit',
			]
		);

		$this->add_control(
			'sf_create_form',
			[
				'label'     => __( 'Create New Form', 'sureforms' ),
				'separator' => 'before',
				'classes'   => 'testclass',
				'type'      => \Elementor\Controls_Manager::BUTTON,
				'text'      => __( 'Create', 'sureforms' ),
				'event'     => 'sureforms:form:create',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Get froms options.
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	public function get_forms_options() {
		$forms = get_posts(
			[
				'post_type'      => 'sureforms_form',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			]
		);

		$options = [];

		foreach ( $forms as $form ) {
			$options[ $form->ID ] = $form->post_title;
		}

		return $options;
	}

	/**
	 * Render form widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since x.x.x
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		echo do_shortcode( '[sureforms id="' . $settings['sf_form_block'] . '"]' );
	}

}
