<?php
/**
 * SureForms - AI Form Builder.
 *
 * @package sureforms
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
	 * @since x.x.x
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

		$field_types = 'input, email, url, textarea,  multi-choice, checkbox, gdpr, number, phone, dropdown, address';

		// if pro is active then add pro field types.
		if ( defined( 'SRFM_PRO_VER' ) ) {
			$field_types .= ', hidden, rating, upload, date-time-picker, number-slider, page-break';
		} else {
			$field_types .= 'and make sure fields like hidden, rating, upload, date-time-picker, number-slider fields should not be included STRICTLY in the response';
		}

		// Finally add the system message to the start of the array.
		if ( ! empty( $params['use_system_message'] ) ) {
			array_unshift(
				$messages,
				[
					'role'    => 'system',
					'content' => 'Based on the description, generate a survey with questions array where every element has five fields: label, fieldType, placeholder, required (add only if it is mentioned by user or if it is mandatory for form submission), helpText (add only if it is mentioned by user or if it is needed based on the field). fieldType can only be of these options ' . $field_types . '. fieldType can be used in any order and any number of times. Also return formTitle in the response.
					
					Here are the field specific properties that you need to return with that specific field:
					
					1. For input type if needed you can return. defaultValue, isUnique, duplicateMsg, errorMsg, textLength.
					
					2) For dropdown type also return fieldOptions array as array of strings. For example, for dropdown the fieldOptions can be [ {"label":"Option 1"}, {"label":"Option 2"}, {"label":"Option 3"} ].

					3) For multi-choice type also return fieldOptions array as array of objects. For example, for multi-choice the fieldOptions can be [ {"optionTitle":"Option 1"}, {"optionTitle":"Option 2"}, {"optionTitle":"Option 3"}, {"optionTitle":"Option 4"} ]. Also return singleSelection as boolean value.

					4) consider multi step form as page-break and you can use it multiple time if required and use it only if asked to.

					5) For upload field also return fileSizeLimit, allowedFormats. For example, for upload field the allowedFileTypes can be [ { "value": "jpg", "label": "jpg" }, { "value": "jpeg", "label": "jpeg" }] and fileSizeLimit number can be between 10 to 300.

					6) Never give labels as generic names Field 1, Field 2, etc.

					7) Do not generate more than 20 fields in a form. If the user enter number of fields more than 20, then just return the first 20 fields.

					
					It is essential to provide a valid JSON structure. If there is an error, return an empty JSON. Make sure you don\'t entertain the empty cases, like Hey there, or kind of message, just check for the proper prompt and understand if the prompt has the word form or any kind of direction to create the form.',
				]
			);
		}

		// send the request to the open ai server.
		$data = [
			'source'   => 'openai',
			'messages' => $messages,
		];

		// Get the response from the endpoint.
		$response = AI_Helper::get_chat_completions_response( $data );

		// check if response is an array if not then send error.
		if ( ! is_array( $response ) ) {
			wp_send_json_error( [ 'message' => __( 'The SureForms AI Middleware encountered an error.', 'sureforms' ) ] );
		}

		if ( ! empty( $response['error'] ) ) {
			// If the response has an error, handle it and report it back.
			$message = '';
			if ( ! empty( $response['error']['message'] ) ) { // If any error message received from OpenAI.
				$message = $response['error']['message'];
			} elseif ( is_string( $response['error'] ) ) {  // If any error message received from server.
				if ( ! empty( $response['code'] && is_string( $response['code'] ) ) ) {
					$message = $this->custom_message( $response['code'] );
				}
				$message = ! empty( $message ) ? $message : $response['error'];
			}
			wp_send_json_error( [ 'message' => $message ] );
		} elseif ( is_array( $response['choices'] ) && ! empty( $response['choices'][0]['message']['content'] ) ) {
			// If the message was sent successfully, send it successfully.
			wp_send_json_success( $response );
		} else {
			// If you've reached here, then something has definitely gone amuck. Abandon ship.
			wp_send_json_error( [ 'message' => __( 'Something went wrong', 'sureforms' ) ] );
		}//end if
	}

	/**
	 * This function converts the code received from scs to a readable error message.
	 * Useful to provide better language for error codes.
	 *
	 * @param string $code error code received from SCS ( Credits server ).
	 * @since x.x.x
	 * @return string
	 */
	private function custom_message( $code ) {
		$message_array = [
			'insufficient_credits' => __( 'You have no forms left.', 'sureforms' ),
		];

		return isset( $message_array[ $code ] ) ? $message_array[ $code ] : '';
	}

}
