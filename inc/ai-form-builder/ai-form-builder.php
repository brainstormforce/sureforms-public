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
	 * Instance of this class.
	 *
	 * @since x.x.x
	 * @var object Class object.
	 */
	private static $instance;

	/**
	 * Fetches ai data from the middleware server - this will be merged with the get_credit_server_response() function.
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

		$field_types = 'input, email, url, textarea,  multi-choice, checkbox, gdpr, number, phone, dropdown, address, address-compact';

		// if pro is active then add pro field types.
		if ( defined( 'SRFM_PRO_VER' ) ) {
			$field_types .= ', hidden, rating, upload, date-time, number-slider, page-break';
		} else{
			$field_types .= 'and make sure fields like hidden, rating, upload, date-time, number-slider fields should not be included STRICTLY in the response';
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
					
					2) For dropdown type also return fieldOptions array as array of strings. For example, for dropdown the fieldOptions can be [ "Option 1", "Option 2", "Option 3" ].

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
		$data     = [
			// 'model'    => 'gpt-3.5-turbo',
			'source'   => 'openai',
			'messages' => $messages,
		];

		// Get the response from the endpoint.
		$response = $this->get_credit_server_response( $data );

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
			update_option( 'zip_ai_form_creation_count', get_option( 'zip_ai_form_creation_count', 0 ) + 1 );
			// If the message was sent successfully, send it successfully.
			wp_send_json_success( $response );
		} else {
			// If you've reached here, then something has definitely gone amuck. Abandon ship.
			wp_send_json_error( [ 'message' => __( 'Something went wrong', 'sureforms' ) ] );
		}//end if
	}

		/**
	 * Get the Zip AI Response from the Zip Credit Server.
	 *
	 * @param string $endpoint The endpoint to get the response from.
	 * @param array  $body The data to be passed as the request body, if any.
	 * @param array  $extra_args Extra arguments to be passed to the request, if any.
	 * @since 1.0.0
	 * @return array The Zip AI Response.
	 */
	public static function get_credit_server_response( $body = [], $extra_args = [] ) {
		// Set the API URL.
		$api_url  = "https://credits.startertemplates.com/sureforms/chat/completions";
		$api_args = array(
			'headers' => array(
				'X-Token' => 'aHR0cHM6Ly9kZXZlbG9wZXIuc3VyZWNhcnQuY29tLw==', // For now, this is a dummy token. Once we go live, we will replace this with the actual token. That will be user's SureCart license or base64 encoded site URL.
				'Content-Type' => 'application/json',
			),
			'timeout' => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- 30 seconds is required sometime for open ai responses
		);
	
		// If the data array was passed, add it to the args.
		if ( ! empty( $body ) && is_array( $body ) ) {
			$api_args['body'] = json_encode( $body );
			// $api_args['body'] = $body;
		}
	
		// If there are any extra arguments, then we can overwrite the required arguments.
		if ( ! empty( $extra_args ) && is_array( $extra_args ) ) {
			$api_args = array_merge( $api_args, $extra_args );
		}
	
		// Get the response from the endpoint.
		$response = wp_remote_post( $api_url, $api_args );
	
		// If the response was an error, or not a 200 status code, then abandon ship.
		if ( is_wp_error( $response ) || empty( $response['response'] ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array(
				'error' => __( 'The Zip AI Middleware is not responding.', 'zip-ai' ),
			);
		}
	
		// Get the response body.
		$response_body = wp_remote_retrieve_body( $response );
	
		// If the response body is not a JSON, then abandon ship.
		if ( empty( $response_body ) || ! json_decode( $response_body ) ) {
			return array(
				'error' => __( 'The Zip AI Middleware encountered an error.', 'zip-ai' ),
			);
		}
	
		// Return the response body.
		return json_decode( $response_body, true );
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
			'no_auth'              => __( 'Invalid auth token.', 'sureforms' ),
			'insufficient_credits' => __( 'You have no credits left.', 'sureforms' ),
		];

		return isset( $message_array[ $code ] ) ? $message_array[ $code ] : '';
	}

	/**
	 * Get the ZipWP Token from the Zip AI Settings.
	 *
	 * @since 1.1.2
	 * @return string The ZipWP Token.
	 */
   public static function get_current_plan_details() {
	   $current_plan_details = [];

	   // Get the response from the endpoint.
	   $response = self::get_zipwp_api_response( 'plan/current-plan' );

	   // If the response is not an error, then use it - else create an error response array.
	   if ( empty( $response['error'] ) && is_array( $response ) ) {
		   $current_plan_details = $response;
		   if ( empty( $current_plan_details['status'] ) ) {
			   $current_plan_details['status'] = 'ok';
		   }
	   } else {
		   $current_plan_details['status'] = 'error';
		   if ( ! empty( $response['error'] ) ) {
			   $current_plan_details['error'] = $response['error'];
		   }
	   }

	   return $current_plan_details;
   }

	/**
	 * Get a response from the ZipWP API server.
	 *
	 * @param string $endpoint The endpoint to get the response from.
	 * @since 1.1.2
	 * @return array The ZipWP API Response.
	 */
	public static function get_zipwp_api_response( $endpoint ) {
		// If the endpoint is not a string, then abandon ship.
		if ( ! is_string( $endpoint ) ) {
			return array(
				'error' => __( 'The ZipWP Endpoint was not declared', 'zip-ai' ),
			);
		}

		// Get the ZipWP Token from the Zip AI Settings.
		$zipwp_token = self::get_decrypted_zipwp_token();

		// If the ZipWP Token is not set, then abandon ship.
		if ( empty( $zipwp_token ) || ! is_string( $zipwp_token ) ) {
			return array(
				'error' => __( 'The ZipWP Token is not set.', 'zip-ai' ),
			);
		}

		// Set the API URL.
		$api_url = ZIP_AI_ZIPWP_API . $endpoint;

		// Get the response from the endpoint.
		$response = wp_remote_get(
			$api_url,
			array(
				'headers'   => array(
					'X-Token'       => 'aHR0cHM6Ly9kZXZlbG9wZXIuc3VyZWNhcnQuY29tLw==', // For now, this is a dummy token. Once we go live, we will replace this with the actual token. That will be user's SureCart license or base64 encoded site URL.
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer ' . $zipwp_token,
				),
				'sslverify' => false,
				'timeout'   => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- 30 seconds is required sometime for the ZipWP API response
			)
		);

		// If the response was an error, or not a 200 status code, then abandon ship.
		if ( is_wp_error( $response ) || empty( $response['response'] ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array(
				'error' => __( 'The ZipWP API server is not responding.', 'zip-ai' ),
			);
		}

		// Get the response body.
		$response_body = wp_remote_retrieve_body( $response );

		// If the response body is not a JSON, then abandon ship.
		if ( empty( $response_body ) || ! json_decode( $response_body ) ) {
			return array(
				'error' => __( 'The ZipWP API server encountered an error.', 'zip-ai' ),
			);
		}

		// Return the response body.
		return json_decode( $response_body, true );
	}
}
