<?php
/**
 * SureForms Learn Helper Class
 *
 * @package sureforms
 * @since 2.5.2
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Learn class.
 *
 * @since 2.5.2
 */
class Learn {
	use Get_Instance;

	/**
	 * Constructor.
	 *
	 * @since 2.5.2
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
	 * @since 2.5.2
	 */
	public static function get_chapters_structure() {
		$chapters = [
			[
				'id'          => 'setting-up-form',
				'title'       => __( 'Setting Up Your Form', 'sureforms' ),
				'description' => __( 'Get started with SureForms by building your first form, adding the right fields, and styling it to match your brand.', 'sureforms' ),
				'url'         => 'https://sureforms.com/docs/creating-and-publishing-forms/',
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
						'id'           => 'set-up-form-fields',
						'title'        => __( 'Set Up Your Form Fields', 'sureforms' ),
						'description'  => __( 'Adjust the field settings according to your requirements.', 'sureforms' ),
						'learn'        => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'gif-video',
								'data' => [
									'url' => 'https://www.youtube.com/embed/uQchuO6kMeY',
									'alt' => __( 'Set Up Your Form Fields', 'sureforms' ),
								],
							],
						],
						'docsUrl'      => 'https://sureforms.com/docs/',
						'headerAction' => [
							'label'   => __( 'Set Up Fields', 'sureforms' ),
							'url'     => 'post-new.php?post_type=sureforms_form',
							'dynamic' => 'latest-form',
							'source'  => 'learn-setup-fields',
						],
						'completed'    => false,
					],
					[
						'id'           => 'style-your-forms',
						'title'        => __( 'Style Your Forms', 'sureforms' ),
						'description'  => __( 'Customize the look and feel of your form to match your brand. Adjust background, colors, layout and etc. to create a seamless experience for your visitors.', 'sureforms' ),
						'learn'        => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'gif-video',
								'data' => [
									'url' => 'https://www.youtube.com/embed/n8JnocyZ3i4',
									'alt' => __( 'Style Your Forms', 'sureforms' ),
								],
							],
						],
						'docsUrl'      => 'https://sureforms.com/docs/',
						'headerAction' => [
							'label'   => __( 'Style Form', 'sureforms' ),
							'url'     => 'post-new.php?post_type=sureforms_form&source=learn-style-form',
							'dynamic' => 'latest-form',
							'source'  => 'learn-style-form',
						],
						'completed'    => false,
					],
				],
			],
			[
				'id'          => 'making-form-live',
				'title'       => __( 'Making Your Form Live', 'sureforms' ),
				'description' => __( 'Publish your form and make it accessible to your visitors using Instant Form or by embedding it on any page.', 'sureforms' ),
				'url'         => 'https://sureforms.com/docs/instant-forms/',
				'steps'       => [
					[
						'id'           => 'instant-form',
						'title'        => __( 'Instant Form', 'sureforms' ),
						'description'  => __( 'No need to design a separate page just for the forms. Publish your form instantly on a dedicated landing page. Just enable Instant Form option and share the link.', 'sureforms' ),
						'learn'        => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'gif-video',
								'data' => [
									'url' => 'https://www.youtube.com/embed/SyFYazEjJNA',
									'alt' => __( 'Instant Form', 'sureforms' ),
								],
							],
						],
						'docsUrl'      => 'https://sureforms.com/docs/',
						'headerAction' => [
							'label'   => __( 'Instant Form', 'sureforms' ),
							'url'     => 'post-new.php?post_type=sureforms_form&source=learn-instant-form',
							'dynamic' => 'latest-form',
							'source'  => 'learn-instant-form',
						],
						'completed'    => false,
					],
					[
						'id'           => 'embed-forms',
						'title'        => __( 'Embed Your Forms in a Page', 'sureforms' ),
						'description'  => __( 'Easily embed your form on any page using the built-in SureForms block.', 'sureforms' ),
						'learn'        => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'gif-video',
								'data' => [
									'url' => 'https://www.youtube.com/embed/vldMwp7rquM',
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
				],
			],
			[
				'id'          => 'managing-entries',
				'title'       => __( 'Managing Entries', 'sureforms' ),
				'description' => __( 'Stay on top of your form submissions with email notifications and the built-in entries manager.', 'sureforms' ),
				'url'         => 'https://sureforms.com/docs/adjust-form-notification-emails/',
				'steps'       => [
					[
						'id'           => 'email-notification',
						'title'        => __( 'Email Notifications', 'sureforms' ),
						'description'  => __( 'Get email notifications whenever someone fills out your form.', 'sureforms' ),
						'learn'        => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'gif-video',
								'data' => [
									'url' => 'https://www.youtube.com/embed/tvlGvgUZrhM',
									'alt' => __( 'Configure Your Email Notification', 'sureforms' ),
								],
							],
						],
						'docsUrl'      => 'https://sureforms.com/docs/adjust-form-notification-emails/',
						'headerAction' => [
							'label' => __( 'Configure', 'sureforms' ),
							'url'   => 'admin.php?page=sureforms_form_settings&tab=general-settings&source=learn',
						],
						'completed'    => false,
					],
					[
						'id'           => 'manage-entries',
						'title'        => __( 'Manage Entries', 'sureforms' ),
						'description'  => __( 'View, filter, and manage all form submissions from one place. Export entries, delete spam, and keep your data organized with the SureForms Entries manager.', 'sureforms' ),
						'learn'        => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'gif-video',
								'data' => [
									'url' => 'https://www.youtube.com/embed/8ycIcYvS--M',
									'alt' => __( 'Manage Entries', 'sureforms' ),
								],
							],
						],
						'docsUrl'      => 'https://sureforms.com/docs/',
						'headerAction' => [
							'label' => __( 'Go to Entries', 'sureforms' ),
							'url'   => 'admin.php?page=sureforms_entries&source=learn',
						],
						'completed'    => false,
					],
				],
			],
		];

		/**
		 * Filter learn chapters structure.
		 *
		 * @param array $chapters Learn chapters data.
		 * @since 2.5.2
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
	 * @since 2.5.2
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
	 * @since 2.5.2
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
					$step['completed'] = Helper::get_boolean_value( $saved_progress[ $chapter_id ][ $step_id ] );
				}
			}
		}

		return $chapters;
	}

	/**
	 * Register REST API endpoints for Learn functionality.
	 *
	 * @since 2.5.2
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'sureforms/v1',
			'/get-learn-chapters',
			[
				'methods'             => \WP_REST_Server::READABLE,
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
				'methods'             => \WP_REST_Server::CREATABLE,
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
	 * @since 2.5.2
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
	 * @since 2.5.2
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

		// Flag for analytics — next send cycle will include updated learn snapshot.
		set_transient( 'srfm_learn_progress_changed', 1, 0 );

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
