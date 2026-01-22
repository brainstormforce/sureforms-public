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
				'id'          => 'welcome-to-sureforms',
				'title'       => __( 'Welcome to SureForms', 'sureforms' ),
				'description' => __( 'Help users get familiar with SureForms, build their first form, and understand the basics of form creation, customization, and management.', 'sureforms' ),
				'url'         => 'https://sureforms.com/docs/',
				'steps'       => [
					[
						'id'          => 'dashboard-tour',
						'title'       => __( 'SureForms Dashboard Tour', 'sureforms' ),
						'description' => __( 'Overview of dashboard layout and interface philosophy.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'image',
								'data' => [
									'src' => 'https://sureforms.com/wp-content/uploads/example-dashboard-tour.png',
									'alt' => __( 'SureForms Dashboard Tour', 'sureforms' ),
								],
							],
						],
						'action'      => [
							'label'      => __( 'Explore Dashboard', 'sureforms' ),
							'url'        => admin_url( 'admin.php?page=sureforms_menu' ),
							'isExternal' => false,
						],
						'completed'   => false,
					],
					[
						'id'          => 'creating-first-form',
						'title'       => __( 'Creating Your First Form', 'sureforms' ),
						'description' => __( 'Step-by-step guide using AI builder and WordPress editor.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'   => 'https://www.youtube.com/embed/uDLF4dk3YHI',
									'title' => __( 'Creating Your First Form', 'sureforms' ),
								],
							],
						],
						'action'      => [
							'label'      => __( 'Create Form', 'sureforms' ),
							'url'        => admin_url( 'post-new.php?post_type=sureforms_form' ),
							'isExternal' => false,
						],
						'docsUrl'     => 'https://sureforms.com/docs/create-your-first-form/',
						'completed'   => false,
					],
					[
						'id'          => 'form-customization-basics',
						'title'       => __( 'Form Customization Basics', 'sureforms' ),
						'description' => __( 'Design styling and branding for your forms.', 'sureforms' ),
						'learn'       => [
							'type' => 'link',
							'url'  => 'https://sureforms.com/docs/advanced-style-options/',
						],
						'action'      => [
							'label'      => __( 'Read Docs', 'sureforms' ),
							'url'        => 'https://sureforms.com/docs/advanced-style-options/',
							'isExternal' => true,
						],
						'completed'   => false,
					],
					[
						'id'          => 'form-confirmation-message',
						'title'       => __( 'Form Confirmation Message', 'sureforms' ),
						'description' => __( 'Set up confirmation messages after form submission.', 'sureforms' ),
						'learn'       => [
							'type' => 'link',
							'url'  => 'https://sureforms.com/docs/form-confirmation/',
						],
						'action'      => [
							'label'      => __( 'Read Docs', 'sureforms' ),
							'url'        => 'https://sureforms.com/docs/form-confirmation/',
							'isExternal' => true,
						],
						'completed'   => false,
					],
					[
						'id'          => 'anti-spam-settings',
						'title'       => __( 'Anti-Spam Settings', 'sureforms' ),
						'description' => __( 'Protect your forms from spam submissions.', 'sureforms' ),
						'learn'       => [
							'type' => 'link',
							'url'  => 'https://sureforms.com/docs-category/anti-spam-settings/',
						],
						'action'      => [
							'label'      => __( 'Read Docs', 'sureforms' ),
							'url'        => 'https://sureforms.com/docs-category/anti-spam-settings/',
							'isExternal' => true,
						],
						'completed'   => false,
					],
					[
						'id'          => 'email-notification',
						'title'       => __( 'Setting Up Email Notification', 'sureforms' ),
						'description' => __( 'Configure email notifications for form submissions.', 'sureforms' ),
						'learn'       => [
							'type' => 'link',
							'url'  => 'https://sureforms.com/docs/adjust-form-notification-emails/',
						],
						'action'      => [
							'label'      => __( 'Read Docs', 'sureforms' ),
							'url'        => 'https://sureforms.com/docs/adjust-form-notification-emails/',
							'isExternal' => true,
						],
						'completed'   => false,
					],
				],
			],
			[
				'id'          => 'advanced-form-features',
				'title'       => __( 'Advanced Form Features', 'sureforms' ),
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
									'url'   => 'https://www.youtube.com/embed/51q5a_Pk1XA',
									'title' => __( 'Multi-Step Forms', 'sureforms' ),
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
									'url'   => 'https://www.youtube.com/embed/9C5ePZ6VpoA',
									'title' => __( 'Conversational Forms', 'sureforms' ),
								],
							],
						],
						'docsUrl'     => 'https://sureforms.com/docs/conversational-forms/',
						'completed'   => false,
					],
					[
						'id'          => 'pdf-generation',
						'title'       => __( 'PDF Generation', 'sureforms' ),
						'description' => __( 'Learn how to automatically generate downloadable PDFs from submissions by creating an invoice template using ChatGPT.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'   => 'https://www.youtube.com/embed/example-pdf-generation',
									'title' => __( 'PDF Generation', 'sureforms' ),
								],
							],
						],
						'docsUrl'     => 'https://sureforms.com/docs/pdf-generation/',
						'completed'   => false,
					],
					[
						'id'          => 'payment-forms',
						'title'       => __( 'Payment Forms', 'sureforms' ),
						'description' => __( 'Demonstrate setting up Stripe/PayPal payment integration.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'   => 'https://www.youtube.com/embed/example-payment-forms',
									'title' => __( 'Payment Forms', 'sureforms' ),
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
							'label'      => __( 'Read Docs', 'sureforms' ),
							'url'        => 'https://sureforms.com/docs/form-restriction-in-sureforms/',
							'isExternal' => true,
						],
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
									'url'   => 'https://www.youtube.com/embed/AACFRtDjXHs',
									'title' => __( 'Conditional Logic & Smart Fields', 'sureforms' ),
								],
							],
						],
						'docsUrl'     => 'https://sureforms.com/docs/conditional-logic/',
						'completed'   => false,
					],
					[
						'id'          => 'calculator-forms',
						'title'       => __( 'Calculator Forms', 'sureforms' ),
						'description' => __( 'Build custom calculators like loan, price quote, ROI, and more.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'   => 'https://www.youtube.com/embed/example-calculator-forms',
									'title' => __( 'Calculator Forms', 'sureforms' ),
								],
							],
						],
						'docsUrl'     => 'https://sureforms.com/docs/calculator-forms/',
						'completed'   => false,
					],
					[
						'id'          => 'login-forms',
						'title'       => __( 'Login Forms', 'sureforms' ),
						'description' => __( 'Create branded login pages for your users.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'   => 'https://www.youtube.com/embed/H2DRn8A8LQI',
									'title' => __( 'Login Forms', 'sureforms' ),
								],
							],
						],
						'docsUrl'     => 'https://sureforms.com/docs/login-forms/',
						'completed'   => false,
					],
					[
						'id'          => 'registration-forms',
						'title'       => __( 'Registration Forms', 'sureforms' ),
						'description' => __( 'Create branded registration pages for new users.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'video',
								'data' => [
									'url'   => 'https://www.youtube.com/embed/H2DRn8A8LQI',
									'title' => __( 'Registration Forms', 'sureforms' ),
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
				'title'       => __( 'Native Integrations & Automation', 'sureforms' ),
				'description' => __( 'Help users automate workflows and connect SureForms with their favorite apps.', 'sureforms' ),
				'url'         => 'https://sureforms.com/docs/integrations/',
				'steps'       => [
					[
						'id'          => 'intro-integrations',
						'title'       => __( 'Introduction to Native Integrations', 'sureforms' ),
						'description' => __( 'Learn how to connect SureForms with 15+ popular tools and services.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								[
									'type' => 'paragraph',
									'text' => __( 'SureForms integrates with a wide range of tools to help you automate your workflows. Click on any integration below to learn how to set it up:', 'sureforms' ),
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to Google Sheets', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/sureforms-integration-with-google-sheets',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to FluentCRM', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/fluentcrm-integration/',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to Telegram', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/telegram-integration/',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to MailerPress', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/mailerpress-integration/',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to Zoho CRM', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/zoho-crm-integration/',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to OttoKit', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/ottokit-integration/',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to Zapier', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/zapier-integration/',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to Webhooks', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/webhooks-integration/',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to MailChimp', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/mailchimp-integration/',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to Salesflare', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/salesflare-integration/',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to MailPoet', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/mailpoet-integration/',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to GetResponse', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/getresponse-integration/',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to Brevo', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/brevo-integration/',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to AgileCRM', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/agilecrm-integration/',
										'target' => '_blank',
									],
								],
								[
									'type' => 'link',
									'data' => [
										'text'   => __( 'How to Connect to ActiveCampaign', 'sureforms' ),
										'url'    => 'https://sureforms.com/docs/activecampaign-integration/',
										'target' => '_blank',
									],
								],
							],
						],
						'action'      => [
							'label'      => __( 'View Integrations', 'sureforms' ),
							'url'        => admin_url( 'admin.php?page=sureforms_form_settings&tab=integrations' ),
							'isExternal' => false,
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
	 * @return \WP_REST_Response Response object.
	 * @since 1.0.0
	 */
	public static function rest_update_learn_progress( $request ) {
		$chapter_id = $request->get_param( 'chapterId' );
		$step_id    = $request->get_param( 'stepId' );
		$completed  = $request->get_param( 'completed' );
		$user_id    = get_current_user_id();

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
