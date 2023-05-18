<?php
/**
 * SureForms Public Class.
 *
 * Class file for public functions.
 *
 * @package SureForms
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Form_Builder\Form_Base;
/**
 * Public Class
 *
 * @since X.X.X
 */
class SF_Public {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  X.X.X
	 */
	public function __construct() {
		add_filter( 'template_include', [ $this, 'page_template' ], PHP_INT_MAX );
		add_filter( 'the_content', [ $this, 'print_form' ], PHP_INT_MAX );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], PHP_INT_MAX );
	}

	/**
	 * Load Font Awesome Icons Classes.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '5.15.3' );
	}

	/**
	 * Form Template filter.
	 *
	 * @param string $template Template.
	 * @return string Template.
	 * @since X.X.X
	 */
	public function page_template( $template ) {
		if ( is_singular( SUREFORMS_FORMS_POST_TYPE ) ) {
			$file_name = 'single-form.php';
			$template  = locate_template( $file_name ) ? locate_template( $file_name ) : SUREFORMS_DIR . '/templates/' . $file_name;
			$template  = apply_filters( 'sureforms_form_template', $template );
		}
		return $template;
	}

	/**
	 * Print form template.
	 *
	 * @param string $content Content.
	 * @since X.X.X
	 * @return string Modified Content.
	 */
	public function print_form( $content ) {
		if ( get_post_type() === SUREFORMS_FORMS_POST_TYPE ) {
			if ( has_blocks() ) {
				$blocks = parse_blocks( get_the_content() );
			}
		}
		return $content;
	}
}

