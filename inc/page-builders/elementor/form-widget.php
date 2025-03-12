<?php
/**
 * Elementor SureForms form widget.
 *
 * @package sureforms.
 * @since 0.0.5
 */

namespace SRFM\Inc\Page_Builders\Elementor;

use Elementor\Plugin;
use Elementor\Widget_Base;
use Spec_Gb_Helper;
use SRFM\Inc\Helper;
use SRFM\Inc\Page_Builders\Page_Builders;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms widget that displays a form.
 */
class Form_Widget extends Widget_Base {
	/**
	 * Whether we are in the preview mode.
	 *
	 * @var bool
	 */
	public $is_preview_mode;

	/**
	 * Constructor.
	 *
	 * @param array<mixed> $data Widget data.
	 * @param array<mixed> $args Widget arguments.
	 */
	public function __construct( $data = [], $args = null ) {

		parent::__construct( $data, $args );

		// (Preview iframe)
		$this->is_preview_mode = \Elementor\Plugin::$instance->preview->is_preview_mode();

		if ( $this->is_preview_mode ) {

			// enqueue common fields assets for the dropdown and phone fields.
			Page_Builders::enqueue_common_fields_assets();

			wp_register_script( 'srfm-elementor-preview', SRFM_URL . 'inc/page-builders/elementor/assets/elementor-editor-preview.js', [ 'elementor-frontend' ], SRFM_VER, true );
			wp_localize_script(
				'srfm-elementor-preview',
				'srfmElementorData',
				[
					'isProActive' => defined( 'SRFM_PRO_VER' ),
				]
			);

		}
	}

	/**
	 * Get script depends.
	 *
	 * @since 0.0.5
	 * @return array<string> Script dependencies.
	 */
	public function get_script_depends() {
		if ( $this->is_preview_mode ) {
			return [ 'srfm-elementor-preview' ];
		}

		return [];
	}

	/**
	 * Get widget name.
	 *
	 * @since 0.0.5
	 * @return string Widget name.
	 */
	public function get_name() {
		return SRFM_FORMS_POST_TYPE;
	}

	/**
	 * Get widget title.
	 *
	 * @since 0.0.5
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'SureForms', 'sureforms' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 0.0.5
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-form-horizontal srfm-elementor-widget-icon';
	}

	/**
	 * Get widget categories. Used to determine where to display the widget in the editor.
	 *
	 * @since 0.0.5
	 * @return array<string> Widget categories.
	 */
	public function get_categories() {
		return [ 'sureforms-elementor' ];
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 0.0.5
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
	 * @since 0.0.5
	 * @return void
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'section_form',
			[
				'label' => __( 'SureForms', 'sureforms' ),
			]
		);

		$this->add_control(
			'srfm_form_block',
			[
				'label'   => __( 'Select Form', 'sureforms' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => Helper::get_sureforms_title_with_ids(),
				'default' => '',
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
				'condition'    => [
					'srfm_form_block!' => [ '' ],
				],
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
				'condition' => [
					'srfm_form_block!' => [ '' ],
				],
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

		$this->add_control(
			'srfm_form_submission_info',
			[
				'content'   => __( 'Form submission will be possible on the frontend.', 'sureforms' ),
				'type'      => \Elementor\Controls_Manager::ALERT,
				'condition' => [
					'srfm_form_block!' => [ '' ],
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render form widget output on the frontend.
	 *
	 * @since 0.0.5
	 * @return void|string
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( ! is_array( $settings ) ) {
			return;
		}

		$is_editor = Plugin::instance()->editor->is_edit_mode();

		if ( $is_editor && isset( $settings['srfm_form_block'] ) && '' === $settings['srfm_form_block'] ) {
			echo '<div style="background: #D9DEE1; color: #9DA5AE; padding: 10px; font-family: Roboto, sans-serif">' .
					esc_html__( 'Select the form that you wish to add here.', 'sureforms' ) .
				'</div>';
			return;
		}

		if ( ! isset( $settings['srfm_show_form_title'] ) || ! isset( $settings['srfm_form_block'] ) ) {
			return;
		}

		$show_form_title = 'true' === $settings['srfm_show_form_title'];
		// get spectra blocks and add css and js.
		$blocks = parse_blocks( get_post_field( 'post_content', $settings['srfm_form_block'] ) );
		$styles = Spec_Gb_Helper::get_instance()->get_assets( $blocks );

		// phpcs:ignore -- WordPress.Security.EscapeOutput.OutputNotEscaped - Escaping not required.
		echo do_shortcode( '[sureforms id="' . $settings['srfm_form_block'] . '" show_title="' . ! $show_form_title . '"]' );
		// phpcs:ignore -- WordPress.Security.EscapeOutput.OutputNotEscaped - Escaping not required.
		echo '<style>' . $styles['css'] . '</style>';
		// phpcs:ignore -- WordPress.Security.EscapeOutput.OutputNotEscaped - Escaping not required.
		echo '<script>' . $styles['js'] . '</script>';
	}

}
