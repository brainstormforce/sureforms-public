<?php
/**
 * Sureforms Submit Class file.
 *
 * @package sureforms.
 * @since X.X.X
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms Submit Class.
 *
 * @since X.X.X
 */
class Sureforms_Submit {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  X.X.X
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
	}

	/**
	 * Add custom API Route submit-form
	 *
	 * @return void
	 * @since    1.0
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/submit-form',
			array(
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle_form_submission' ],
				'permission_callback' => '__return_true',
			)
		);
	}


	/**
	 * Handle Form Submission
	 *
	 * @param object $request Request object or array containing form data.
	 *
	 * @since    1.0
	 * @return array Array containing the response data.
	 * @phpstan-ignore-next-line
	 */
	public function handle_form_submission( $request ) {
		// @phpstan-ignore-next-line
		$form_data = $request->get_params();

		$id          = wp_kses_post( $form_data['form-id'] );
		$form_markup = get_the_content( null, false, $form_data['form-id'] );

		$pattern = '/"label":"(.*?)"/';
		preg_match_all( $pattern, $form_markup, $matches );
		$labels = $matches[1];

		$html = '<table><tr><th>Field</th><th>Value</th></tr>';

		foreach ( $form_data as $key => $value ) {
			$html .= '<tr>';
			$html .= '<td>' . htmlspecialchars( str_replace( '_', ' ', $key ) ) . '</td>';
			$html .= '<td>' . htmlspecialchars( $value ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</table>';

		$first_input = str_replace( ' ', '_', $labels[0] );
		$name        = isset( $form_data[ $first_input ] ) ? sanitize_text_field( $form_data[ $first_input ] ) : '';

		$new_post = array(
			'post_title'   => $name,
			'post_content' => $html,
			'post_status'  => 'unread',
			'post_type'    => 'sureforms_entry',
		);

		$post_id = wp_insert_post( $new_post );

		if ( $post_id ) {
			wp_set_object_terms( $post_id, $id, 'sureforms_tax' );
			$response = array(
				'success' => true,
				'message' => 'Form submitted successfully',
				'data'    => array(
					'name' => $name,
				),
			);

			$admin_email = strval( get_option( 'admin_email' ) );
			$subject     = 'Notification from Sureforms Form ID:' . $id;

			$message = $html;

			$sent = wp_mail( $admin_email, $subject, $message );

			if ( $sent ) {
				wp_send_json_success( 'Email sent successfully.' );
			} else {
				wp_send_json_error( 'Failed to send form data.' );
			}
		} else {
			$response = array(
				'success' => false,
				'message' => 'Error submitting form',
			);
		}

		return $response;
	}

}
