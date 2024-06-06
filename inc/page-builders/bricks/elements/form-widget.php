<?php

namespace SRFM\Inc\Page_Builders\Bricks\Elements;

use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Form_Widget extends \Bricks\Element {
	// Element properties
	public $category = 'sureforms';
	public $name     = 'sureforms';
	public $icon     = 'ti-view-list';

	/**
	 * Get widget name.
	 *
	 * @since x.x.x
	 * @return string Widget name.
	 */
	public function get_label() {
		return __( 'SureForms', 'sureforms' );
	}

	public function set_controls() {

		// Select Form.
		$this->controls['form-id'] = [

			'tab'         => 'content',
			'label'       => __( 'Form', 'sureforms' ),
			'type'        => 'select',
			'options'     => Helper::get_forms_options(),
			'placeholder' => __( 'Select a form', 'ws-form' ),
		];

		// Show Form Title Toggle.
		$this->controls['form-title'] = [
			'tab'   => 'content',
			'label' => __( 'Form Title', 'sureforms' ),
			'type'  => 'checkbox',
			'info'  => __( 'Enable this to show form title.', 'sureforms' ),
		];

	}

	public function render() {
		$settings   = $this->settings;
		$form_id    = isset( $settings['form-id'] ) ? $settings['form-id'] : '';
		$form_title = isset( $settings['form-title'] ) ? true : false;

		if ( $form_id > 0 ) {
			echo '<div ' . $this->render_attributes( '_root' ) . '>' . do_shortcode( sprintf( '[sureforms id="%s" show_title="%s"]', $form_id, ! $form_title ) ) . '</div>';
		} else {
			// Show placeholder when no form is selected
			echo $this->render_element_placeholder(
				[
					'icon-class'  => $this->icon,
					'title'       => esc_html__( 'No form selected', 'sureforms' ),
					'description' => esc_html__( 'Select the form that you wish to add here.', 'sureforms' ),
					'text'        => esc_html__( 'No form selected', 'sureforms' ),
				]
			);
		}
	}
}
