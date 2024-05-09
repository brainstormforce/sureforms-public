<?php
/**
 * SureForms - AI Form Builder.
 *
 * @package sureforms
 */

namespace SRFM\Inc\AI_Form_Builder;

use SRFM\Inc\Traits\Get_Instance;
use ZipAI\Classes\Helper as AI_Helper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SureForms AI Form Builder Class.
 */
class AI_Form_Builder {

	/**
	 * The namespace for the Rest Routes.
	 *
	 * @since x.x.x
	 * @var string
	 */
	private $namespace = 'sureforms/v1';

	/**
	 * Instance of this class.
	 *
	 * @since x.x.x
	 * @var object Class object.
	 */
	private static $instance;

	use Get_Instance;

	/**
	 * Constructor of this class.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_route' ] );
	}

	/**
	 * Register All Routes.
	 *
	 * @hooked - rest_api_init
	 * @since x.x.x
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			$this->namespace,
			'/generate-form',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'generate_ai_form' ],
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => [
						'use_system_message' => [
							'sanitize_callback' => [ $this, 'sanitize_boolean_field' ],
						],
					],
				],
			]
		);
	}

	/**
	 * Checks whether the value is boolean or not.
	 *
	 * @param mixed $value value to be checked.
	 * @since x.x.x
	 * @return boolean
	 */
	public function sanitize_boolean_field( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

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
		$token_count = 0;
		$messages    = [];

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
		}

		// Finally add the system message to the start of the array.
		if ( ! empty( $params['use_system_message'] ) ) {
			array_unshift(
				$messages,
				[
					'role'    => 'system',
					'content' => 'Based on the description, generate a survey with questions array where every element has five fields: label, fieldType, placeholder, required, helpText. fieldType can only be of these options ' . $field_types . '. fieldType can be used in any order and any number of times.
					
					Here are the field specific properties that you need to return with that specific field:
					
					1. For input type if needed you can return. defaultValue, isUnique, duplicateMsg, errorMsg, textLength.
					
					2) For dropdown type also return fieldOptions array as array of strings. For example, for dropdown the fieldOptions can be [ "Option 1", "Option 2", "Option 3" ].

					3) For multi-choice type also return fieldOptions array as array of objects. For example, for multi-choice the fieldOptions can be [ {"optionTitle":"Option 1"}, {"optionTitle":"Option 2"}, {"optionTitle":"Option 3"}, {"optionTitle":"Option 4"} ]. Also return singleSelection as boolean value.

					4) consider multi step form as page-break and you can use it multiple time if required and use it only if asked to.
					
					It is essential to provide a valid JSON structure. If there is an error, return an empty JSON. Make sure you don\'t entertain the empty cases, like Hey there, or kind of message, just check for the proper prompt and understand if the prompt has the word form or any kind of direction to create the form.',
				]
			);
		}

		// send the request to the open ai server.
		$endpoint = ZIP_AI_CREDIT_SERVER_API . 'chat/completions';
		$data     = [
			'model'    => 'gpt-3.5-turbo',
			'messages' => $messages,
		];

		$response = wp_remote_post(
			$endpoint,
			[
				'body'    => wp_json_encode( $data ) ? wp_json_encode( $data ) : '',
				'timeout' => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- 30 seconds is required sometime for open ai responses
				'headers' => [
					'Authorization' => 'Bearer ' . AI_Helper::get_decrypted_auth_token(),
					'Content-Type'  => 'application/json',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( [ 'message' => __( 'Something went wrong', 'sureforms' ) ] );
		} else {
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( is_array( $response_body ) && is_array( $response_body['choices'] ) && ! empty( $response_body['choices'][0]['message']['content'] ) ) {
				// send the response back to the client.
				wp_send_json_success( wp_remote_retrieve_body( $response ) );
			} elseif ( is_array( $response_body ) && ! empty( $response_body['error'] ) ) {
				$message = '';
				if ( ! empty( $response_body['error']['message'] ) ) { // If any error message received from OpenAI.
					$message = $response_body['error']['message'];
				} elseif ( is_string( $response_body['error'] ) ) {  // If any error message received from server.
					if ( ! empty( $response_body['code'] && is_string( $response_body['code'] ) ) ) {
						$message = $this->custom_message( $response_body['code'] );
					}
					$message = ! empty( $message ) ? $message : $response_body['error'];
				}

				wp_send_json_error( [ 'message' => $message ] );
			} else {
				wp_send_json_error( [ 'message' => __( 'Something went wrong', 'sureforms' ) ] );
			}
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
			'no_auth'              => __( 'Invalid auth token.', 'sureforms' ),
			'insufficient_credits' => __( 'You have no credits left.', 'sureforms' ),
		];

		return isset( $message_array[ $code ] ) ? $message_array[ $code ] : '';
	}
}
