<?php
/**
 * SureForms - AI Form Builder.
 *
 * @package sureforms
 */

namespace SRFM\Inc\AI_Form_Builder;
use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Helper;
use SRFM\Inc\AI_Form_Builder\Field_Mapping;
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
	 * @since 1.0.0
	 * @var string
	 */
	private $namespace = 'sureforms/v1';

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 * @var object Class object.
	 */
	private static $instance;

	use Get_Instance;

	/**
	 * Constructor of this class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		// Setup the Sidebar Rest Routes.
		add_action( 'rest_api_init', array( $this, 'register_route' ) );

		// Setup the Sidebar Auth Ajax.
		// add_action( 'wp_ajax_verify_zip_ai_authenticity', array( $this, 'verify_authenticity' ) );

		// add_action( 'admin_bar_menu', array( $this, 'add_admin_trigger' ), 999 );

		// Render the Sidebar React App in the Footer in the Gutenberg Editor, Admin, and the Front-end.
		// add_action( 'admin_footer', array( $this, 'render_sidebar_markup' ) );
		// add_action( 'wp_footer', array( $this, 'render_sidebar_markup' ) );

		// Add the Sidebar to the Gutenberg Editor, Admin, and the Front-end.
		// add_action( 'admin_enqueue_scripts', array( $this, 'load_sidebar_assets' ) );
		// add_action( 'wp_enqueue_scripts', array( $this, 'load_sidebar_assets' ) );
	}

	/**
	 * Register All Routes.
	 *
	 * @hooked - rest_api_init
	 * @since 1.0.0
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			$this->namespace,
			'/generate-form',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'generate_ai_form' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => array(
						'use_system_message' => array(
							'sanitize_callback' => array( $this, 'sanitize_boolean_field' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Checks whether the value is boolean or not.
	 *
	 * @param mixed $value value to be checked.
	 * @since 1.0.0
	 * @return boolean
	 */
	public function sanitize_boolean_field( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Fetches ai data from the middleware server - this will be merged with the get_credit_server_response() function.
	 *
	 * @param \WP_REST_Request $request request object.
	 * @since 1.0.0
	 * @return void
	 */
	public function generate_ai_form( $request ) {

		// Get the params.
		$params = $request->get_params();

		// If the nessage array doesn't exist, abandon ship.
		if ( empty( $params['message_array'] ) || ! is_array( $params['message_array'] ) ) {
			wp_send_json_error( array( 'message' => __( 'The message array was not supplied', 'zip-ai' ) ) );
		}

		// Set the token count to 0, and create messages array.
		$token_count = 0;
		$messages    = array();

		// Start with the last message - going upwards until the token count hits 2000.
		foreach ( array_reverse( $params['message_array'] ) as $current_message ) {
			// If the message content doesn't exist, skip it.
			if ( empty( $current_message['content'] ) ) {
				continue;
			}

			// Get the token count, and if it's greater than 2000, break out of the loop.
			// $token_count += Helper::get_token_count( $current_message['content'] );
			// if ( $token_count >= 2000 ) {
			// 	break;
			// }

			// Add the message to the start of the messages to send to the SCS Middleware.
			array_unshift( $messages, $current_message );
		}

		// Finally add the system message to the start of the array.
        if (!empty($params['use_system_message'])) {
            array_unshift(
                $messages,
                array(
                    'role'    => 'system',
                    'content' => 'You are expert form designer. I will tell you a form description and you have to provide form structure in json format only. Choose the standard HTML form fiields Be specific. Do not mention any description. Respond in the valid JSON format: { "row1": { "0": { "first_name": { "type": "text", "width": 50, "label": "First Name", "placeholder": "Enter your first name", "required": true } }, "1": { "last_name": { "type": "text", "width": 50, "label": "Last Name", "placeholder": "Enter your last name", "required": true } } }, "row2": { "0": { "email": { "type": "email", "width": 100, "label": "Email", "placeholder": "Enter your email", "required": true } } } } and so on... If any error return an empty json.',
                )
            );
        }


		// Out custom endpoint to get OpenAi data.
		// $endpoint = ZIP_AI_CREDIT_SERVER_API . 'chat/completions';
        $endpoint = 'https://api.openai.com/v1/chat/completions';
		$data     = array(
			// 'temperature'       => 0.7,
			// 'top_p'             => 1,
			// 'frequency_penalty' => 0.8,
			// 'presence_penalty'  => 1,
			'model'             => 'gpt-3.5-turbo',
            // 'max_tokens'        => 2048,
			'messages'          => [
                array(
                    'role'    => 'system',
                    // 'content' => 'You are an expert form designer. Your expertise is needed to craft a JSON structure that accurately represents a form based on the following description. Your JSON structure should utilize standard HTML form fields and adhere to specific requirements. It\'s essential to ensure the JSON structure you provide is valid and easily interpretable for implementation.',
					'content' => 'Based on the description, generate a survey with questions array where every element has two fields: text and the fieldType and fieldType can be of these options input, email, number, textarea, checkbox, dropdown, date-time, upload. For dropdown type also return fieldOptions array as array of strings. For example, for dropdown the fieldOptions can be [ "Option 1", "Option 2", "Option 3" ]. It is essential to provide a valid JSON structure. If there is an error, return an empty JSON.',
                ),
                array(
                    'role'    => 'user',
                    'content' => $params['message_array'][0]['content'],
                ),
            ],
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . 'sk-proj-LgSXu9dbBmhYYjYNRdDkT3BlbkFJlvtzD9BxqtrXze3ya9cg',
                    'Content-Type'  => 'application/json',
				),
                'body'    => json_encode( $data ),
                // 'body'    => $data,
                'timeout' => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- 30 seconds is required sometime for open ai responses
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => __( 'Something went wrong', 'zip-ai' ) ) );
		} else {
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( is_array( $response_body ) && is_array( $response_body['choices'] ) && ! empty( $response_body['choices'][0]['message']['content'] ) ) {

                // create post content by mapping the json data to the form fields. Pass the data as array.
				// $post_content = Field_Mapping::generate_gutenberg_fields_from_json($response_body['choices'][0]['message']['content']);

				$request_count = get_option( 'srfm_ai_request_count', 0 );

				// Update the request count.
				update_option( 'srfm_ai_request_count', $request_count + 1 );

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

				wp_send_json_error( array( 'message' => $message ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Something went wrong', 'zip-ai' ) ) );
			}
		}//end if
	}

	/**
	 * This function converts the code recieved from scs to a readable error message.
	 * Useful to provide better language for error codes.
	 *
	 * @param string $code error code received from SCS ( Credits server ).
	 * @since 1.0.0
	 * @return string
	 */
	private function custom_message( $code ) {
		$message_array = array(
			'no_auth'              => __( 'Invalid auth token.', 'zip-ai' ),
			'insufficient_credits' => __( 'You have no credits left.', 'zip-ai' ),
		);

		return isset( $message_array[ $code ] ) ? $message_array[ $code ] : '';
	}
}
