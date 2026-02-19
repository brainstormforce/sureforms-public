<?php
/**
 * SureForms Learn Helper Class
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Learn class.
 *
 * @since x.x.x
 */
class Learn {
	use Get_Instance;

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Get default learn modules structure.
	 *
	 * Returns the complete structure of all available modules and their lessons.
	 * This serves as the source of truth for module definitions used across
	 * the plugin for both frontend display and analytics validation.
	 *
	 * @return array Array of module objects with their lessons.
	 * @since x.x.x
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
						'id'           => 'creating-first-form',
						'title'        => __( 'Creating Your First Form', 'sureforms' ),
						'description'  => __( 'Creating a form with SureForms takes just a few minutes. Just describe the kind of form you need in a simple prompt, and let SureForms AI handle the heavy lifting for you.', 'sureforms' ),
						'learn'        => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'gif-video',
								'data' => [
									'url' => 'https://www.youtube.com/embed/pMWZ2ko3G1k',
									'alt' => __( 'Creating Your First Form', 'sureforms' ),
								],
							],
						],
						'docsUrl'      => 'https://sureforms.com/docs/creating-and-publishing-forms/',
						'headerAction' => [
							'label' => __( 'Create a Form', 'sureforms' ),
							'url'   => 'admin.php?page=add-new-form&source=learn',
						],
						'completed'    => false,
					],
					[
						'id'           => 'embed-forms',
						'title'        => __( 'Embed Your Forms in a Page', 'sureforms' ),
						'description'  => __( 'Instantly publish your form using the Instant Form feature in SureForms, or easily embed it on any page with the SureForms block.', 'sureforms' ),
						'learn'        => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'gif-video',
								'data' => [
									'url' => 'https://www.youtube.com/embed/PzkdlJ5jbIA',
									'alt' => __( 'Embed Your Forms in a Page', 'sureforms' ),
								],
							],
						],
						'docsUrl'      => 'https://sureforms.com/docs/displaying-forms/',
						'headerAction' => [
							'label' => __( 'Embed Forms', 'sureforms' ),
							'url'   => self::get_embed_forms_url(),
						],
						'completed'    => false,
					],
					[
						'id'           => 'secure-forms',
						'title'        => __( 'Protect Your Forms From Bots', 'sureforms' ),
						'description'  => __( 'Protect your forms from spam and bot submissions using Google reCAPTCHA, hCaptcha, Cloudflare and Honeypot.', 'sureforms' ),
						'learn'        => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'gif-video',
								'data' => [
									'url' => 'https://www.youtube.com/embed/HDf2sTt9dp4',
									'alt' => __( 'Protect Your Forms From Bots', 'sureforms' ),
								],
							],
						],
						'docsUrl'      => 'https://sureforms.com/docs/google-recaptcha/',
						'headerAction' => [
							'label' => __( 'Protect Forms', 'sureforms' ),
							'url'   => 'admin.php?page=sureforms_form_settings&tab=security-settings&subpage=recaptcha',
						],
						'completed'    => false,
					],
					[
						'id'           => 'email-notification',
						'title'        => __( 'Configure Your Email Notification', 'sureforms' ),
						'description'  => __( 'Get email notifications whenever someone fills out your form.', 'sureforms' ),
						'learn'        => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'gif-video',
								'data' => [
									'url' => 'https://www.youtube.com/embed/FkEEKP0VRaA',
									'alt' => __( 'Configure Your Email Notification', 'sureforms' ),
								],
							],
						],
						'docsUrl'      => 'https://sureforms.com/docs/adjust-form-notification-emails/',
						'headerAction' => [
							'label' => __( 'SET UP SMTP', 'sureforms' ),
							'url'   => 'admin.php?page=sureforms_smtp',
						],
						'completed'    => false,
					],
					[
						'id'           => 'payment-forms',
						'title'        => __( 'Payment Forms', 'sureforms' ),
						'description'  => __( 'You no longer need to set up a full store just to accept payments. With SureForms, you can collect payments using a simple form. Learn how to set up a Payment Form with Stripe and PayPal integration and start accepting payments.', 'sureforms' ),
						'learn'        => [
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
						'docsUrl'      => 'https://sureforms.com/docs/how-to-create-a-payment-form-in-sureforms/',
						'headerAction' => [
							'label' => __( 'Accept Payment', 'sureforms' ),
							'url'   => 'admin.php?page=sureforms_payments',
						],
						'completed'    => false,
					],
				],
			],
			[
				'id'          => 'sureforms-advanced',
				'title'       => __( 'SureForms Advanced', 'sureforms' ),
				'description' => __( 'Help users unlock SureForms full power with advanced features and creative use cases.', 'sureforms' ),
				'url'         => 'https://sureforms.com/docs/',
				'steps'       => [
					[
						'id'          => 'multi-step-forms',
						'title'       => __( 'Multi-Step Forms', 'sureforms' ),
						'description' => __( 'Did you know? You can boost form conversions by up to 3 times just by breaking one long form into multiple, shorter steps. In this video, Learn how to split forms into multiple steps with progress indicators.', 'sureforms' ),
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
						'description' => __( 'Conversational Form is another great way to keep your users engaged. In this lesson, learn how to create chat-style form interactions for better engagement.', 'sureforms' ),
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
						'docsUrl'     => 'https://sureforms.com/docs/conversational-form/',
						'completed'   => false,
					],
					[
						'id'          => 'conditional-logic',
						'title'       => __( 'Conditional Logic & Smart Fields', 'sureforms' ),
						'description' => __( 'Using Conditional Logic, you can make your forms more relevant for a user and reduce drop-offs. Here, learn how to show or hide fields dynamically based on user input and conditions.', 'sureforms' ),
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
						'description' => __( 'Sometimes you need a clean record of your form entries, right? PDF Generation in SureForms makes it easy. Learn how to automatically generate and download PDFs from form submissions.', 'sureforms' ),
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
						'docsUrl'     => 'https://sureforms.com/docs/sureforms-pdf-generation-feature/',
						'completed'   => false,
					],
					[
						'id'          => 'login-forms',
						'title'       => __( 'Custom Login Forms', 'sureforms' ),
						'description' => __( 'WordPress\'s default login page is boring, so we built a better one. This video shows how you can create a fully custom login page for your website in just a few seconds.', 'sureforms' ),
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
						'docsUrl'     => 'https://sureforms.com/docs/sureforms-login-block-a-step-by-step-guide/',
						'completed'   => false,
					],
					[
						'id'          => 'registration-forms',
						'title'       => __( 'Custom Registration Forms', 'sureforms' ),
						'description' => __( 'Your Custom Login Page also requires a Custom Registration Page, right? We have that too. Learn to create branded registration pages for new users.', 'sureforms' ),
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
						'docsUrl'     => 'https://sureforms.com/docs/sureforms-registration-block-step-by-step-guide/',
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
						'description' => __( 'SureForms lets you connect with 23+ tools natively. That means you can link your forms to your favorite apps and automate workflows, without relying on any third-party tools in between. Here, learn how to connect SureForms with popular tools and services.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'      => 'https://www.youtube.com/embed/JHjrYAWuUdQ',
									'title'    => __( 'Introduction to Native Integrations', 'sureforms' ),
									'duration' => 240, // 4 minutes.
								],
							],
						],
						'docsUrl'     => 'https://sureforms.com/docs-category/integrations/',
						'completed'   => false,
					],
				],
			],
		];

		/**
		 * Filter learn chapters structure.
		 *
		 * @param array $chapters Learn chapters data.
		 * @since x.x.x
		 */
		return apply_filters( 'srfm_learn_chapters', $chapters );
	}

	/**
	 * Get the edit URL for an existing page to use for the "Embed Forms" lesson.
	 *
	 * Finds the "Sample Page" (default WordPress page) or falls back to the
	 * most recent published page. If no pages exist, falls back to creating a new page.
	 *
	 * @return string The admin edit URL for the page.
	 * @since x.x.x
	 */
	public static function get_embed_forms_url() {
		// Try to find the default "Sample Page" by slug.
		$sample_page = get_page_by_path( 'sample-page' );

		if ( $sample_page ) {
			return 'post.php?post=' . $sample_page->ID . '&action=edit&source=learn';
		}

		// Fallback: get the most recent published page.
		$pages = get_pages(
			[
				'sort_column' => 'post_date',
				'sort_order'  => 'DESC',
				'number'      => 1,
			]
		);

		if ( ! empty( $pages ) ) {
			return 'post.php?post=' . $pages[0]->ID . '&action=edit&source=learn';
		}

		// Last fallback: create a new page.
		return 'post-new.php?post_type=page&source=learn';
	}

	/**
	 * Get learn chapters with user progress merged.
	 *
	 * @param int $user_id Optional. User ID to get progress for. Defaults to current user.
	 * @return array Chapters array with progress data merged.
	 * @since x.x.x
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
	 * @since x.x.x
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'sureforms/v1',
			'/get-learn-chapters',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_get_learn_chapters' ],
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
				'callback'            => [ $this, 'rest_update_learn_progress' ],
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
	 * @since x.x.x
	 */
	public function rest_get_learn_chapters() {
		$user_id  = get_current_user_id();
		$chapters = self::get_learn_chapters( $user_id );

		return rest_ensure_response( $chapters );
	}

	/**
	 * REST API callback to update learn progress.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object or error.
	 * @since x.x.x
	 */
	public function rest_update_learn_progress( $request ) {
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
