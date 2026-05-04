<?php
/**
 * SureForms - AI Form Builder.
 *
 * @package sureforms
 * @since 0.0.8
 */

namespace SRFM\Inc\AI_Form_Builder;

use SRFM\Inc\Traits\Get_Instance;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SureForms AI Form Builder Class.
 */
class AI_Form_Builder {
	use Get_Instance;

	/**
	 * Fetches ai data from the middleware server
	 *
	 * @param \WP_REST_Request $request request object.
	 * @since 0.0.8
	 * @return void
	 */
	public function generate_ai_form( $request ) {

		// Get the params.
		$params = $request->get_params();

		// If the message array doesn't exist, abandon ship.
		if ( empty( $params['message_array'] ) || ! is_array( $params['message_array'] ) ) {
			wp_send_json_error( [ 'message' => __( 'The message array was not supplied', 'sureforms' ) ] );
		}

		// Set the token count to 0, and create messages array.
		$messages = [];

		// Start with the last message - going upwards until the token count hits 2000.
		foreach ( array_reverse( $params['message_array'] ) as $current_message ) {
			// If the message content doesn't exist, skip it.
			if ( empty( $current_message['content'] ) ) {
				continue;
			}

			// Add the message to the start of the messages to send to the SCS Middleware.
			array_unshift( $messages, $current_message );
		}

		// Get the response from the endpoint.
		$response = AI_Helper::get_chat_completions_response(
			apply_filters(
				'srfm_ai_form_generator_body',
				[
					'query' => $messages[0]['content'],
				],
				$params
			)
		);

		// check if response is an array if not then send error.
		if ( ! is_array( $response ) ) {
			wp_send_json_error( [ 'message' => __( 'The SureForms AI Middleware encountered an error.', 'sureforms' ) ] );
		}

		if ( ! empty( $response['error'] ) ) {
			// If the response has an error, handle it and report it back.
			// We sanitize before returning so OpenAI / middleware infra details
			// (URLs, request IDs, model names, account IDs) do not leak to the
			// client; the raw message is preserved in the debug log via
			// AI_Helper::sanitize_ai_error_message() when WP_DEBUG[_LOG] is on.
			$raw = '';
			if ( is_array( $response['error'] ) && ! empty( $response['error']['message'] ) ) {
				// If any error message received from OpenAI.
				$raw = $response['error']['message'];
			} elseif ( is_string( $response['error'] ) ) {
				// If any error message received from the middleware server.
				$raw = $response['error'];
			}
			$message = AI_Helper::sanitize_ai_error_message( $raw, 'generate/form' );
			if ( '' === $message ) {
				$message = __( 'The SureForms AI Middleware encountered an error.', 'sureforms' );
			}
			wp_send_json_error( [ 'message' => $message ] );
		}

		// Validate the expected form structure piece by piece so we can return specific errors.
		if ( empty( $response['form'] ) || ! is_array( $response['form'] ) ) {
			wp_send_json_error(
				[
					'message' => __( 'The AI did not return a form. Please refine your prompt and try again.', 'sureforms' ),
				]
			);
		}

		if ( empty( $response['form']['formTitle'] ) ) {
			wp_send_json_error(
				[
					'message' => __( 'The AI response is missing a form title. Please try again.', 'sureforms' ),
				]
			);
		}

		if (
			empty( $response['form']['formFields'] ) ||
			! is_array( $response['form']['formFields'] )
		) {
			wp_send_json_error(
				[
					'message' => __( 'The AI was unable to generate form fields. Please try again.', 'sureforms' ),
				]
			);
		}

		wp_send_json_success( $response );
	}

}
