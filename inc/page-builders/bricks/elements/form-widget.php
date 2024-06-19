<?php
/**
 * Bricks SureForms form element.
 *
 * @package sureforms.
 * @since 0.0.5
 */

namespace SRFM\Inc\Page_Builders\Bricks\Elements;

use SRFM\Inc\Helper;
use SRFM\Inc\Frontend_Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms Bricks element.
 */
class Form_Widget extends \Bricks\Element {

	/**
	 * Element category.
	 *
	 * @var string
	 */
	public $category = 'sureforms';

	/**
	 * Element name.
	 *
	 * @var string
	 */
	public $name = 'sureforms';

	/**
	 * Element icon.
	 *
	 * @var string
	 */
	public $icon = 'ti-layout-accordion-separated';

	/**
	 * Constructor.
	 *
	 * @param array<mixed> $element Element data.
	 */
	public function __construct( $element = null ) {

		if ( bricks_is_builder() ) {

			$this->scripts = [ 'handleBricksPreviewFormSubmission', 'loadPageBreak' ];
		}

		parent::__construct( $element );
	}

	/**
	 * Get element name.
	 *
	 * @since 0.0.5
	 * @return string element name.
	 */
	public function get_label() {
		return __( 'SureForms', 'sureforms' );
	}

	/**
	 * Get element keywords.
	 *
	 * @since 0.0.5
	 * @return array<string> element keywords.
	 */
	public function get_keywords() {
		return [
			'sureforms',
			'contact form',
			'form',
			'bricks form',
		];
	}

	/**
	 * Set element controls.
	 *
	 * @since 0.0.5
	 * @return void
	 */
	public function set_controls() {

		// Select Form.
		$this->controls['form-id'] = [

			'tab'         => 'content',
			'label'       => __( 'Form', 'sureforms' ),
			'type'        => 'select',
			'options'     => Helper::get_sureforms_title_with_ids(),
			'placeholder' => __( 'Select Form', 'sureforms' ),
		];

		// Show Form Title Toggle.
		$this->controls['form-title'] = [
			'tab'      => 'content',
			'label'    => __( 'Show Form Title', 'sureforms' ),
			'type'     => 'checkbox',
			'info'     => __( 'Enable this to show form title.', 'sureforms' ),
			'required' => [ 'form-id', '!=', '' ],
		];

		$this->controls['srfm_form_submission_info'] = [
			'tab'      => 'content',
			'content'  => __( 'Form submission will be possible on the frontend.', 'sureforms' ),
			'type'     => 'info',
			'required' => [ 'form-id', '!=', '' ],
		];

	}

	/**
	 * Render element.
	 *
	 * @since 0.0.5
	 * @return void
	 */
	public function render() {
		$settings   = $this->settings;
		$form_id    = isset( $settings['form-id'] ) ? $settings['form-id'] : '';
		$form_title = isset( $settings['form-title'] );

		if ( $form_id > 0 ) {
			// phpcs:ignore -- WordPress.Security.EscapeOutput.OutputNotEscaped - Escaping not required.
			echo '<div ' . $this->render_attributes( '_root' ) . '>' . do_shortcode( sprintf( '[sureforms id="%s" show_title="%s"]', $form_id, ! $form_title ) ) . '</div>';
			// phpcs:ignoreEnd
		} else {
			// Show placeholder when no form is selected.
			// phpcs:ignore -- WordPress.Security.EscapeOutput.OutputNotEscaped - Escaping not required.
			echo $this->render_element_placeholder(
				[
					'icon-class'  => $this->icon,
					'description' => esc_html__( 'Select the form that you wish to add here.', 'sureforms' ),
				]
			);
			// phpcs:ignoreEnd
		}
	}
}
