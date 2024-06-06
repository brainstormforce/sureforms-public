<?php
/**
 * Elementor SureForms form widget.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc\Page_Builders\Elementor;

use Elementor\Widget_Base;
use Elementor\Plugin;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 *
 * SureForms widget that displays a form.
 */
class Form_Widget extends Widget_Base {

	/**
	 * Stores Elementor Pro Conditions_Manager instance.
	 *
	 * @var false|\ElementorPro\Modules\ThemeBuilder\Classes\Conditions_Manager
	 * @since 1.6.1
	 */
	private static $elementor_conditions_manager = false;

	/**
	 * Get widget name.
	 *
	 * @since x.x.x
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'sureforms_form';
	}

	/**
	 * Get widget title.
	 *
	 * @since x.x.x
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'SureForms', 'sureforms' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since x.x.x
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-form-horizontal srfm-elementor-widget-icon';
	}

	/**
	 * Get widget categories. Used to determine where to display the widget in the editor.
	 *
	 * @since x.x.x
	 * @return array<string> Widget categories.
	 */
	public function get_categories() {
		return [ 'sureforms-elementor' ];
	}

	/**
	 * Get widget keywords.
	 *
	 * @since x.x.x
	 * @return array<string> Widget keywords.
	 */
	public function get_keywords() {
		return [
			'sureforms',
			'contact form',
			'form',
			'elementor form',
		];
	}

	/**
	 * Register form widget controls.
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since x.x.x
	 * @return void
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'section_form',
			[
				'label' => __( 'SureForms', 'sureforms' ),
			]
		);

		$options = Helper::get_forms_options();

		$this->add_control(
			'srfm_form_block',
			[
				'label'   => __( 'Select Form', 'sureforms' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => $options,
			]
		);

		$this->add_control(
			'srfm_show_form_title',
			[
				'label'        => __( 'Form Title', 'sureforms' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'sureforms' ),
				'label_off'    => __( 'Hide', 'sureforms' ),
				'return_value' => 'true',
			]
		);

		$this->add_control(
			'srfm_edit_form',
			[
				'label'     => __( 'Edit Form', 'sureforms' ),
				'separator' => 'before',
				'type'      => \Elementor\Controls_Manager::BUTTON,
				'text'      => __( 'Edit', 'sureforms' ),
				'event'     => 'sureforms:form:edit',
			]
		);

		$this->add_control(
			'srfm_create_form',
			[
				'label' => __( 'Create New Form', 'sureforms' ),
				'type'  => \Elementor\Controls_Manager::BUTTON,
				'text'  => __( 'Create', 'sureforms' ),
				'event' => 'sureforms:form:create',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render form widget output on the frontend.
	 *
	 * @since x.x.x
	 * @return void|string
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( ! is_array( $settings ) || ! array_key_exists( 'srfm_show_form_title', $settings ) || ! array_key_exists( 'srfm_form_block', $settings ) ) {
			return;
		}

		$is_editor = Plugin::instance()->editor->is_edit_mode();

		if ( $is_editor && '' === $settings['srfm_form_block'] ) {
			echo '<div style="background: #D9DEE1; color: #9DA5AE; padding: 10px; font-family: Roboto, sans-serif">' .
					esc_html__( 'Select the form that you wish to add here.', 'sureforms' ) .
				'</div>';
			return;
		}

		$form_title = 'true' === $settings['srfm_show_form_title'] ? true : false;

		$shortcode =  sprintf( '[sureforms id="%s" show_title="%s"]', $settings['srfm_form_block'], ! $form_title );
					//   sprintf( '[sureforms id="%s" show_title="%s"]', $form_id, ! $form_title )

		echo do_shortcode( $shortcode );
	}

		/**
	 * Returns Condition_Manager instance of the Elementor Pro.
	 *
	 * @return false|\ElementorPro\Modules\ThemeBuilder\Classes\Conditions_Manager
	 * @since 1.6.1
	 */
	private static function get_condition_manager() {
		if ( false !== self::$elementor_conditions_manager ) {
			return self::$elementor_conditions_manager;
		}

		if ( ! method_exists( '\ElementorPro\Modules\ThemeBuilder\Module', 'instance' ) ) {
			return false;
		}

		$theme_builder = (object) \ElementorPro\Modules\ThemeBuilder\Module::instance();

		if ( ! method_exists( $theme_builder, 'get_conditions_manager' ) ) {
			return false;
		}

		self::$elementor_conditions_manager = $theme_builder->get_conditions_manager();
		return self::$elementor_conditions_manager;
	}

}
