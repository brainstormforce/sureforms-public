<?php
/**
 * SureForms Learn Helper Class
 *
 * @package sureforms
 * @since 1.0.0
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Learn class.
 *
 * @since 1.0.0
 */
class Learn {
	/**
	 * Get default learn modules structure.
	 *
	 * Returns the complete structure of all available modules and their lessons.
	 * This serves as the source of truth for module definitions used across
	 * the plugin for both frontend display and analytics validation.
	 *
	 * @return array Array of module objects with their lessons.
	 * @since 1.0.0
	 */
	public static function get_chapters_structure() {
		$chapters = [
			[
				'id'          => 'sureforms-basics',
				'title'       => __( 'SureForms Basics', 'sureforms' ),
				'description' => __( 'Help users get familiar with SureForms, build their first form, and understand the basics of form creation, customization, and management.', 'sureforms' ),
				'url'         => 'https://sureforms.com/docs/',
				'steps'       => [
					[
						'id'          => 'creating-first-form',
						'title'       => __( 'Creating Your First Form', 'sureforms' ),
						'description' => __( 'Learn how to create and publish your first form with SureForms.', 'sureforms' ),
						'learn'       => [
							'type' => 'link',
							'url'  => 'https://sureforms.com/docs/creating-and-publishing-forms/',
						],
						'action'      => [
							'label'      => __( 'View Documentation', 'sureforms' ),
							'url'        => 'https://sureforms.com/docs/creating-and-publishing-forms/',
							'isExternal' => true,
						],
						'completed'   => false,
					],
					[
						'id'          => 'customize-forms',
						'title'       => __( 'Customize Your Forms', 'sureforms' ),
						'description' => __( 'Learn how to style and customize the appearance of your forms.', 'sureforms' ),
						'learn'       => [
							'type' => 'link',
							'url'  => 'https://sureforms.com/docs/background-styling/',
						],
						'action'      => [
							'label'      => __( 'View Documentation', 'sureforms' ),
							'url'        => 'https://sureforms.com/docs/background-styling/',
							'isExternal' => true,
						],
						'completed'   => false,
					],
					[
						'id'          => 'publish-forms',
						'title'       => __( 'Publish Your Forms', 'sureforms' ),
						'description' => __( 'Learn different ways to display and embed your forms on your website.', 'sureforms' ),
						'learn'       => [
							'type' => 'link',
							'url'  => 'https://sureforms.com/docs/displaying-forms/',
						],
						'action'      => [
							'label'      => __( 'View Documentation', 'sureforms' ),
							'url'        => 'https://sureforms.com/docs/displaying-forms/',
							'isExternal' => true,
						],
						'completed'   => false,
					],
					[
						'id'          => 'secure-forms',
						'title'       => __( 'Secure Forms from Bots', 'sureforms' ),
						'description' => __( 'Protect your forms from spam and bot submissions using Google reCAPTCHA, hCaptcha, Cloudflare and Honeypot.', 'sureforms' ),
						'learn'       => [
							'type' => 'link',
							'url'  => 'https://sureforms.com/docs/google-recaptcha/',
						],
						'action'      => [
							'label'      => __( 'View Documentation', 'sureforms' ),
							'url'        => 'https://sureforms.com/docs/google-recaptcha/',
							'isExternal' => true,
						],
						'completed'   => false,
					],
				],
			],
			[
				'id'          => 'sureforms-advanced',
				'title'       => __( 'SureForms Advanced', 'sureforms' ),
				'description' => __( 'Help users unlock SureForms\' full power with advanced features and creative use cases.', 'sureforms' ),
				'url'         => 'https://sureforms.com/docs/',
				'steps'       => [
					[
						'id'          => 'multi-step-forms',
						'title'       => __( 'Multi-Step Forms', 'sureforms' ),
						'description' => __( 'Learn how to split forms into multiple steps with progress indicators.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'      => 'https://www.youtube.com/embed/51q5a_Pk1XA',
									'title'    => __( 'Multi-Step Forms', 'sureforms' ),
									'duration' => 600, // 10 minutes.
								],
							],
						],
						'docsUrl'     => 'https://sureforms.com/docs/multi-step-forms/',
						'completed'   => false,
					],
					[
						'id'          => 'conversational-forms',
						'title'       => __( 'Conversational Forms', 'sureforms' ),
						'description' => __( 'Create chat-style form interactions for better engagement.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'      => 'https://www.youtube.com/embed/9C5ePZ6VpoA',
									'title'    => __( 'Conversational Forms', 'sureforms' ),
									'duration' => 300, // 5 minutes.
								],
							],
						],
						'docsUrl'     => 'https://sureforms.com/docs/conversational-forms/',
						'completed'   => false,
					],
					[
						'id'          => 'conditional-logic',
						'title'       => __( 'Conditional Logic & Smart Fields', 'sureforms' ),
						'description' => __( 'Show/hide fields based on user input and conditions.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'      => 'https://www.youtube.com/embed/AACFRtDjXHs',
									'title'    => __( 'Conditional Logic & Smart Fields', 'sureforms' ),
									'duration' => 480, // 8 minutes.
								],
							],
						],
						'docsUrl'     => 'https://sureforms.com/docs/conditional-logic/',
						'completed'   => false,
					],
					[
						'id'          => 'pdf-generation',
						'title'       => __( 'PDF Generation', 'sureforms' ),
						'description' => __( 'Learn how to automatically generate downloadable PDFs from form submissions.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'      => 'https://www.youtube.com/embed/qdomuGwWTFA',
									'title'    => __( 'PDF Generation', 'sureforms' ),
									'duration' => 300, // 5 minutes.
								],
							],
						],
						'docsUrl'     => 'https://sureforms.com/docs/pdf-generation/',
						'completed'   => false,
					],
					[
						'id'          => 'payment-forms',
						'title'       => __( 'Payment Forms', 'sureforms' ),
						'description' => __( 'Learn how to set up Stripe and PayPal payment integration.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'      => 'https://www.youtube.com/embed/XnsBNXwoZe8',
									'title'    => __( 'Payment Forms', 'sureforms' ),
									'duration' => 420, // 7 minutes.
								],
							],
						],
						'docsUrl'     => 'https://sureforms.com/docs/payment-forms/',
						'completed'   => false,
					],
					[
						'id'          => 'form-restrictions',
						'title'       => __( 'Form Restrictions', 'sureforms' ),
						'description' => __( 'Set entry limits and deadlines for your forms.', 'sureforms' ),
						'learn'       => [
							'type' => 'link',
							'url'  => 'https://sureforms.com/docs/form-restriction-in-sureforms/',
						],
						'action'      => [
							'label'      => __( 'View Documentation', 'sureforms' ),
							'url'        => 'https://sureforms.com/docs/form-restriction-in-sureforms/',
							'isExternal' => true,
						],
						'completed'   => false,
					],
					[
						'id'          => 'login-forms',
						'title'       => __( 'Custom Login Forms', 'sureforms' ),
						'description' => __( 'Create branded login pages for your users.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'      => 'https://www.youtube.com/embed/H2DRn8A8LQI',
									'title'    => __( 'Custom Login Forms', 'sureforms' ),
									'duration' => 240, // 4 minutes.
								],
							],
						],
						'docsUrl'     => 'https://sureforms.com/docs/login-forms/',
						'completed'   => false,
					],
					[
						'id'          => 'registration-forms',
						'title'       => __( 'Custom Registration Forms', 'sureforms' ),
						'description' => __( 'Create branded registration pages for new users.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'      => 'https://www.youtube.com/embed/vhzgqLJgKrw',
									'title'    => __( 'Custom Registration Forms', 'sureforms' ),
									'duration' => 240, // 4 minutes.
								],
							],
						],
						'docsUrl'     => 'https://sureforms.com/docs/registration-forms/',
						'completed'   => false,
					],
				],
			],
			[
				'id'          => 'integrations-automation',
				'title'       => __( 'SureForms Native Integrations & Automation', 'sureforms' ),
				'description' => __( 'Help users automate workflows and connect SureForms with their favorite apps.', 'sureforms' ),
				'url'         => 'https://sureforms.com/docs/integrations/',
				'steps'       => [
					[
						'id'          => 'intro-integrations',
						'title'       => __( 'Introduction to Native Integrations', 'sureforms' ),
						'description' => __( 'Learn how to connect SureForms with popular tools and services.', 'sureforms' ),
						'learn'       => [
							'type' => 'link',
							'url'  => 'https://sureforms.com/docs/integrations/',
						],
						'action'      => [
							'label'      => __( 'View Documentation', 'sureforms' ),
							'url'        => 'https://sureforms.com/docs/integrations/',
							'isExternal' => true,
						],
						'completed'   => false,
					],
				],
			],
		];

		/**
		 * Filter learn chapters structure.
		 *
		 * @param array $chapters Learn chapters data.
		 * @since 1.0.0
		 */
		return apply_filters( 'srfm_learn_chapters', $chapters );
	}

	/**
	 * Get learn chapters with user progress merged.
	 *
	 * @param int $user_id Optional. User ID to get progress for. Defaults to current user.
	 * @return array Chapters array with progress data merged.
	 * @since 1.0.0
	 */
	public static function get_learn_chapters( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		// Get chapters structure.
		$chapters = self::get_chapters_structure();

		// Get saved progress from user meta.
		$saved_progress = get_user_meta( $user_id, 'srfm_learn_progress', true );
		if ( ! is_array( $saved_progress ) ) {
			$saved_progress = [];
		}

		// Merge saved progress with chapters.
		foreach ( $chapters as &$chapter ) {
			// Validate chapter structure.
			if ( ! isset( $chapter['id'], $chapter['steps'] ) || ! is_array( $chapter['steps'] ) ) {
				continue;
			}

			$chapter_id = $chapter['id'];

			foreach ( $chapter['steps'] as &$step ) {
				if ( ! isset( $step['id'] ) ) {
					continue;
				}

				$step_id = $step['id'];
				if ( isset( $saved_progress[ $chapter_id ][ $step_id ] ) ) {
					$step['completed'] = $saved_progress[ $chapter_id ][ $step_id ];
				}
			}
		}

		return $chapters;
	}

	/**
	 * Register REST API endpoints for Learn functionality.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'sureforms/v1',
			'/get-learn-chapters',
			[
				'methods'             => 'GET',
				'callback'            => [ self::class, 'rest_get_learn_chapters' ],
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'sureforms/v1',
			'/update-learn-progress',
			[
				'methods'             => 'POST',
				'callback'            => [ self::class, 'rest_update_learn_progress' ],
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'chapterId' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'stepId'    => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'completed' => [
						'required'          => true,
						'type'              => 'boolean',
						'sanitize_callback' => 'rest_sanitize_boolean',
					],
				],
			]
		);
	}

	/**
	 * REST API callback to get learn chapters with user progress.
	 *
	 * @return \WP_REST_Response Response object.
	 * @since 1.0.0
	 */
	public static function rest_get_learn_chapters() {
		$user_id  = get_current_user_id();
		$chapters = self::get_learn_chapters( $user_id );

		return rest_ensure_response( $chapters );
	}

	/**
	 * REST API callback to update learn progress.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object or error.
	 * @since 1.0.0
	 */
	public static function rest_update_learn_progress( $request ) {
		$chapter_id = $request->get_param( 'chapterId' );
		$step_id    = $request->get_param( 'stepId' );
		$completed  = $request->get_param( 'completed' );
		$user_id    = get_current_user_id();

		// Validate that chapterId and stepId exist in the defined structure.
		$chapters   = self::get_chapters_structure();
		$valid_step = false;

		foreach ( $chapters as $chapter ) {
			if ( $chapter['id'] !== $chapter_id ) {
				continue;
			}
			foreach ( $chapter['steps'] as $step ) {
				if ( $step['id'] === $step_id ) {
					$valid_step = true;
					break 2;
				}
			}
		}

		if ( ! $valid_step ) {
			return new \WP_Error(
				'invalid_step',
				__( 'Invalid chapter or step ID.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		// Get current progress.
		$saved_progress = get_user_meta( $user_id, 'srfm_learn_progress', true );
		if ( ! is_array( $saved_progress ) ) {
			$saved_progress = [];
		}

		// Update progress.
		if ( ! isset( $saved_progress[ $chapter_id ] ) ) {
			$saved_progress[ $chapter_id ] = [];
		}
		$saved_progress[ $chapter_id ][ $step_id ] = $completed;

		// Save to user meta.
		update_user_meta( $user_id, 'srfm_learn_progress', $saved_progress );

		return rest_ensure_response(
			[
				'success'   => true,
				'chapterId' => $chapter_id,
				'stepId'    => $step_id,
				'completed' => $completed,
			]
		);
	}
}
