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
	 * Get default learn chapters structure.
	 *
	 * Returns the complete structure of all available chapters and their steps.
	 * This serves as the source of truth for chapter definitions used across
	 * the plugin for both frontend display and analytics validation.
	 *
	 * @return array Array of chapter objects with their steps.
	 * @since 1.0.0
	 */
	public static function get_chapters_structure() {
		$chapters = [
			[
				'id'          => 'brand-basics',
				'title'       => __( 'Brand Basics', 'sureforms' ),
				'description' => __( 'Make your website instantly recognizable and aligned with your brand identity.', 'sureforms' ),
				'url'         => 'https://wpastra.com/docs/style-guide/',
				'steps'       => [
					[
						'id'          => 'logo-tagline',
						'title'       => __( 'Add Logo, Tagline & Site Icon', 'sureforms' ),
						'description' => __( 'Help visitors identify your brand quickly by personalizing your core brand elements.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'image',
								'data' => [
									'src' => 'https://wpastra.com/wp-content/uploads/2025/12/astra-learn-logo-tagline.png',
									'alt' => __( 'Add Logo, Tagline & Site Icon in Astra', 'sureforms' ),
								],
							],
						],
						'action'      => [
							'label'      => __( 'Add Branding', 'sureforms' ),
							'url'        => admin_url( 'customize.php?autofocus[section]=title_tagline' ),
							'isExternal' => true,
						],
						'completed'   => false,
					],
					[
						'id'          => 'style-guide',
						'title'       => __( 'Update Brand Style Guide', 'sureforms' ),
						'description' => __( 'Bring consistency across your entire site by setting your brand colors, fonts, and design rules.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'image',
								'data' => [
									'src' => 'https://wpastra.com/wp-content/uploads/2025/12/astra-learn-style-guide.png',
									'alt' => __( 'Update Brand Style Guide in Astra', 'sureforms' ),
								],
							],
						],
						'action'      => [
							'label'      => __( 'Update Style Guide', 'sureforms' ),
							'url'        => admin_url( 'customize.php?autofocus=astra-tour' ),
							'isExternal' => true,
						],
						'completed'   => false,
					],
				],
			],
			[
				'id'          => 'navigation-header',
				'title'       => __( 'Navigation & Header', 'sureforms' ),
				'description' => __( 'Guide visitors effortlessly with a clear, modern, and intuitive header experience.', 'sureforms' ),
				'url'         => 'https://wpastra.com/docs/header-builder-options/',
				'steps'       => [
					[
						'id'          => 'header-layout',
						'title'       => __( 'Customize Header Layout', 'sureforms' ),
						'description' => __( 'Adjust your header structure: placement of logo, site title, buttons, menu and other elements', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'image',
								'data' => [
									'src' => 'https://wpastra.com/wp-content/uploads/2025/12/astra-learn-navigation-header.png',
									'alt' => __( 'Customize Header Layout in Astra', 'sureforms' ),
								],
							],
						],
						'action'      => [
							'label'      => __( 'Customize Header', 'sureforms' ),
							'url'        => admin_url( 'customize.php?autofocus[panel]=panel-header-builder-group' ),
							'isExternal' => true,
						],
						'completed'   => false,
					],
					[
						'id'          => 'organize-menu',
						'title'       => __( 'Organize Your Menu', 'sureforms' ),
						'description' => __( 'Create a simple, logical menu so visitors can find what they need without friction.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'image',
								'data' => [
									'src' => 'https://wpastra.com/wp-content/uploads/2025/12/astra-learn-organize-menu.png',
									'alt' => __( 'Organize Your Menu in Astra', 'sureforms' ),
								],
							],
						],
						'action'      => [
							'label'      => __( 'Configure Menu', 'sureforms' ),
							'url'        => admin_url( 'customize.php?autofocus[section]=section-hb-menu-1' ),
							'isExternal' => true,
						],
						'completed'   => false,
					],
					[
						'id'          => 'mobile-header',
						'title'       => __( 'Set Up Your Mobile Header', 'sureforms' ),
						'description' => __( 'Optimize the header experience for small screens to ensure a seamless mobile journey.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'image',
								'data' => [
									'src' => 'https://wpastra.com/wp-content/uploads/2025/12/astra-learn-mobile-header.png',
									'alt' => __( 'Set Up Your Mobile Header in Astra', 'sureforms' ),
								],
							],
						],
						'action'      => [
							'label'      => __( 'Configure Mobile Menu', 'sureforms' ),
							'url'        => admin_url( 'customize.php?autofocus[section]=section-header-mobile-menu&preview-device=mobile' ),
							'isExternal' => true,
						],
						'isPro'       => false,
						'completed'   => false,
					],
				],
			],
			[
				'id'          => 'footer-customization',
				'title'       => __( 'Footer Customization', 'sureforms' ),
				'description' => __( 'Create a clean, modern footer that builds trust and improves browsing.', 'sureforms' ),
				'url'         => 'https://wpastra.com/docs/footer-builder/',
				'steps'       => [
					[
						'id'          => 'footer-layout',
						'title'       => __( 'Customize Footer Layout', 'sureforms' ),
						'description' => __( 'Add your social handles, links, contact info, copyrights, or widgets to create a professional closing section.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'image',
								'data' => [
									'src' => 'https://wpastra.com/wp-content/uploads/2025/12/astra-learn-footer-layout.png',
									'alt' => __( 'Customize Footer Layout in Astra', 'sureforms' ),
								],
							],
						],
						'action'      => [
							'label'      => __( 'Customize Footer', 'sureforms' ),
							'url'        => admin_url( 'customize.php?autofocus[panel]=panel-footer-builder-group' ),
							'isExternal' => true,
						],
						'completed'   => false,
					],
				],
			],
			[
				'id'          => 'page-layout-settings',
				'title'       => __( 'Page & Layout Settings', 'sureforms' ),
				'description' => __( 'Give your pages a clean, consistent visual flow that feels polished and professional.', 'sureforms' ),
				'url'         => 'https://wpastra.com/docs/page-layout-settings-guide/',
				'steps'       => [
					[
						'id'          => 'sidebar-layout',
						'title'       => __( 'Choose default sidebar layout and style', 'sureforms' ),
						'description' => __( 'Select left, right, or no sidebar depending on your content needs.', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'image',
								'data' => [
									'src' => 'https://wpastra.com/wp-content/uploads/2025/12/astra-learn-sidebar-layout.png',
									'alt' => __( 'Customize Sidebar Layout in Astra', 'sureforms' ),
								],
							],
						],
						'action'      => [
							'label'      => __( 'Configure Sidebar', 'sureforms' ),
							'url'        => admin_url( 'customize.php?autofocus[section]=section-sidebars' ),
							'isExternal' => true,
						],
						'completed'   => false,
					],
					[
						'id'          => 'blog-layout',
						'title'       => __( 'Customize Blog Layout', 'sureforms' ),
						'description' => __( 'Choose how your posts appear - customize everything like layout, style, width and much more', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'image',
								'data' => [
									'src' => 'https://wpastra.com/wp-content/uploads/2025/12/astra-learn-blog-layout.png',
									'alt' => __( 'Customize Blog Layout in Astra', 'sureforms' ),
								],
							],
						],
						'action'      => [
							'label'      => __( 'Customize Blog', 'sureforms' ),
							'url'        => admin_url( 'customize.php?autofocus[section]=section-blog' ),
							'isExternal' => true,
						],
						'completed'   => false,
					],
					[
						'id'          => 'single-page-layout',
						'title'       => __( 'Customize Single Page Layout', 'sureforms' ),
						'description' => __( 'Fine-tune individual pages for layout, style to suite your storytelling, SEO, and user experience', 'sureforms' ),
						'learn'       => [
							'type'    => 'dialog',
							'content' => [
								'type' => 'image',
								'data' => [
									'src' => 'https://wpastra.com/wp-content/uploads/2025/12/astra-learn-single-page-layout.png',
									'alt' => __( 'Customize Single Page Layout in Astra', 'sureforms' ),
								],
							],
						],
						'action'      => [
							'label'      => __( 'Customize Page', 'sureforms' ),
							'url'        => admin_url( 'customize.php?autofocus[section]=section-single-page' ),
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
				'callback'            => [ __CLASS__, 'rest_get_learn_chapters' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'sureforms/v1',
			'/update-learn-progress',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'rest_update_learn_progress' ],
				'permission_callback' => function () {
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
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response object.
	 * @since 1.0.0
	 */
	public static function rest_get_learn_chapters( $request ) {
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
