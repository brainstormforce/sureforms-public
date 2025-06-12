<?php
/**
 * Post Types Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Traits\Get_Instance;
use WP_Admin_Bar;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Post Types Main Class.
 *
 * @since 0.0.1
 */
class Post_Types {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		$this->restrict_unwanted_insertions();
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'init', [ $this, 'register_post_metas' ] );
		add_filter( 'manage_sureforms_form_posts_columns', [ $this, 'custom_form_columns' ] );
		add_action( 'manage_sureforms_form_posts_custom_column', [ $this, 'custom_form_column_data' ], 10, 2 );
		add_shortcode( 'sureforms', [ $this, 'forms_shortcode' ] );
		add_action( 'manage_posts_extra_tablenav', [ $this, 'maybe_render_blank_form_state' ] );
		add_action( 'in_admin_header', [ $this, 'embed_page_header' ] );
		add_filter( 'post_row_actions', [ $this, 'modify_entries_list_row_actions' ], 10, 2 );
		add_filter( 'bulk_actions-edit-sureforms_form', [ $this, 'register_modify_bulk_actions' ], 99 );
		add_action( 'admin_notices', [ $this, 'import_form_popup' ] );
		add_action( 'admin_bar_menu', [ $this, 'remove_admin_bar_menu_item' ], 80, 1 );
		add_action( 'template_redirect', [ $this, 'srfm_instant_form_redirect' ] );
		add_action( 'template_redirect', [ $this, 'disable_sureforms_archive_page' ], 9 );
	}

	/**
	 * Add SureForms menu.
	 *
	 * @param string $title Parent slug.
	 * @param string $subtitle Parent slug.
	 * @param string $image Parent slug.
	 * @param string $button_text Parent slug.
	 * @param string $button_url Parent slug.
	 * @param string $after_button After button content.
	 * @return void
	 * @since 0.0.1
	 */
	public function get_blank_page_markup( $title, $subtitle, $image, $button_text = '', $button_url = '', $after_button = '' ) {
		echo '<div class="sureform-add-new-form">';

		echo '<p class="sureform-blank-page-title">' . esc_html( $title ) . '</p>';

		echo '<p class="sureform-blank-page-subtitle">' . esc_html( $subtitle ) . '</p>';

		echo '<img src="' . esc_url( SRFM_URL . '/images/' . $image . '.svg' ) . '">';

		if ( ! empty( $button_text ) && ! empty( $button_url ) ) {
			echo '<div class="sureforms-add-new-form-container"><a class="sf-add-new-form-button" href="' . esc_url( $button_url ) . '"><div class="button-secondary">' . esc_html( $button_text ) . '</div></a>' . wp_kses_post( $after_button ) . '</div>';
		}
		echo '</div>';
	}

	/**
	 * Render blank state for add new form screen.
	 *
	 * @param string $post_type Post type.
	 * @return void
	 * @since  0.0.1
	 */
	public function sureforms_render_blank_state( $post_type ) {

		if ( SRFM_FORMS_POST_TYPE === $post_type ) {
			$page_name     = 'add-new-form';
			$new_form_url  = admin_url( 'admin.php?page=' . $page_name );
			$import_button = '<button class="button button-secondary srfm-import-btn">' . __( 'Import Form', 'sureforms' ) . '</button>';

			$this->get_blank_page_markup(
				esc_html__( 'Let’s build your first form', 'sureforms' ),
				esc_html__(
					'Craft beautiful and functional forms in minutes',
					'sureforms'
				),
				'add-new-form',
				esc_html__( 'Add New Form', 'sureforms' ),
				$new_form_url,
				$import_button
			);
		}

		if ( SRFM_ENTRIES === $post_type ) {

			$this->get_blank_page_markup(
				esc_html__( 'No records found', 'sureforms' ),
				esc_html__(
					'This is where your form entries will appear',
					'sureforms'
				),
				'blank-entries'
			);
		}
	}

	/**
	 * Registers the forms and submissions post types.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_post_types() {
		$form_labels = [
			'name'               => _x( 'Forms', 'post type general name', 'sureforms' ),
			'singular_name'      => _x( 'Form', 'post type singular name', 'sureforms' ),
			'menu_name'          => _x( 'Forms', 'admin menu', 'sureforms' ),
			'add_new'            => _x( 'Add New', 'form', 'sureforms' ),
			'add_new_item'       => __( 'Add New Form', 'sureforms' ),
			'new_item'           => __( 'New Form', 'sureforms' ),
			'edit_item'          => __( 'Edit Form', 'sureforms' ),
			'view_item'          => __( 'View Form', 'sureforms' ),
			'view_items'         => __( 'View Forms', 'sureforms' ),
			'all_items'          => __( 'Forms', 'sureforms' ),
			'search_items'       => __( 'Search Forms', 'sureforms' ),
			'parent_item_colon'  => __( 'Parent Forms:', 'sureforms' ),
			'not_found'          => __( 'No forms found.', 'sureforms' ),
			'not_found_in_trash' => __( 'No forms found in Trash.', 'sureforms' ),
			'item_published'     => __( 'Form published.', 'sureforms' ),
			'item_updated'       => __( 'Form updated.', 'sureforms' ),
		];
		register_post_type(
			SRFM_FORMS_POST_TYPE,
			[
				'labels'            => $form_labels,
				'rewrite'           => [ 'slug' => 'form' ],
				'public'            => true,
				'show_in_rest'      => true,
				'has_archive'       => true,
				'show_ui'           => true,
				'supports'          => [ 'title', 'author', 'editor', 'custom-fields' ],
				'show_in_menu'      => 'sureforms_menu',
				'show_in_nav_menus' => true,
			]
		);
		// will be used later.
		// register_post_status(
		// 'unread',
		// array(
		// 'label'                     => _x( 'Unread', 'sureforms', 'sureforms' ),
		// 'public'                    => true,
		// 'exclude_from_search'       => false,
		// 'show_in_admin_all_list'    => true,
		// 'show_in_admin_status_list' => true,
		// Translators: %s is the number of unread items.
		// 'label_count'               => _n_noop( 'Unread (%s)', 'Unread (%s)', 'sureforms' ),
		// )
		// );.
	}

	/**
	 * Redirects requests for SureForms archieve page to homeurl
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function disable_sureforms_archive_page() {
		if ( is_post_type_archive( SRFM_FORMS_POST_TYPE ) ) {
			wp_safe_redirect( home_url(), 301 );
			exit;
		}
	}

	/**
	 * Remove add new form menu item.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function remove_admin_bar_menu_item( $wp_admin_bar ) {
		$wp_admin_bar->remove_node( 'new-sureforms_form' );
	}

	/**
	 * Modify list row actions.
	 *
	 * @param array<mixed> $actions An array of row action links.
	 * @param \WP_Post     $post  The current WP_Post object.
	 *
	 * @return array<mixed> $actions Modified row action links.
	 * @since  0.0.1
	 */
	public function modify_entries_list_row_actions( $actions, $post ) {
		if ( 'sureforms_form' === $post->post_type ) {
			$actions['export'] = '<a href="#" onclick="exportForm(' . $post->ID . ')">' . __( 'Export', 'sureforms' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Modify list bulk actions.
	 *
	 * @param array<mixed> $bulk_actions An array of bulk action links.
	 * @since 0.0.1
	 * @return array<mixed> $bulk_actions Modified action links.
	 */
	public function register_modify_bulk_actions( $bulk_actions ) {

		$white_listed_actions = [
			'edit',
			'trash',
			'delete',
			'untrash',
		];

		// remove all actions except white listed actions.
		$bulk_actions = array_intersect_key( $bulk_actions, array_flip( $white_listed_actions ) );

		// Add export action only if edit and trash actions are present in bulk actions.
		if ( isset( $bulk_actions['edit'] ) && isset( $bulk_actions['trash'] ) ) {
			$bulk_actions['export'] = __( 'Export', 'sureforms' );
		}

		return $bulk_actions;
	}

	/**
	 * Show blank slate styles.
	 *
	 * @return void
	 * @since  0.0.1
	 */
	public function get_blank_state_styles() {
		echo '<style type="text/css">.sf-add-new-form-button:focus { box-shadow:none !important; outline:none !important; } #posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions, .wrap .subsubsub  { display: none; } #posts-filter .tablenav.bottom { height: auto; } .sureform-add-new-form{ display: flex; flex-direction: column; gap: 8px; justify-content: center; align-items: center; padding: 24px 0 24px 0; } .sureform-blank-page-title { color: var(--dashboard-heading); font-family: Inter; font-size: 22px; font-style: normal; font-weight: 600; line-height: 28px; margin: 0; } .sureform-blank-page-subtitle { color: var(--dashboard-text); margin: 0; font-family: Inter; font-size: 14px; font-style: normal; font-weight: 400; line-height: 16px; }</style>';
	}

	/**
	 * Show blank slate.
	 *
	 * @param string $which String which tablenav is being shown.
	 * @return void
	 * @since  0.0.1
	 */
	public function maybe_render_blank_form_state( $which ) {
		$screen    = get_current_screen();
		$post_type = $screen ? $screen->post_type : '';

		if ( SRFM_FORMS_POST_TYPE === $post_type && 'bottom' === $which ) {

			$counts = (array) wp_count_posts( SRFM_FORMS_POST_TYPE );
			unset( $counts['auto-draft'] );
			$count = array_sum( $counts );

			if ( 0 < $count ) {
				return;
			}

			$this->sureforms_render_blank_state( $post_type );

			$this->get_blank_state_styles();

		}
	}

	/**
	 * Set up a div for the header to render into it.
	 *
	 * @return void
	 * @since  0.0.1
	 */
	public static function embed_page_header() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$is_screen_sureforms_entries = Helper::validate_request_context( SRFM_ENTRIES, 'page' );

		if ( 'edit-' . SRFM_FORMS_POST_TYPE === $screen_id || $is_screen_sureforms_entries ) {
			?>
		<style>
			.srfm-page-header {
				min-height: 56px;
				@media screen and ( max-width: 600px ) {
					padding-top: 46px;
				}
			}
		</style>
		<div id="srfm-page-header" class="srfm-page-header srfm-admin-wrapper">
			<div class="srfm-page-pre-nav-content"></div>
		</div>
			<?php
		}
	}

	/**
	 * Registers the sureforms metas.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_post_metas() {
		$check_icon = 'data:image/svg+xml;base64,' . base64_encode( strval( file_get_contents( plugin_dir_path( SRFM_FILE ) . 'images/check-icon.svg' ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		$metas = apply_filters(
			'srfm_register_post_meta',
			[
				// General tab metas.
				'_srfm_use_label_as_placeholder' => 'boolean',
				'_srfm_submit_button_text'       => 'string',
				'_srfm_is_inline_button'         => 'boolean',
				// Submit Button.
				'_srfm_submit_width_backend'     => 'string',
				'_srfm_button_border_radius'     => 'integer',
				'_srfm_submit_alignment'         => 'string',
				'_srfm_submit_alignment_backend' => 'string',
				'_srfm_submit_width'             => 'string',
				'_srfm_inherit_theme_button'     => 'boolean',
				// Additional Classes.
				'_srfm_additional_classes'       => 'string',

				// Advanced tab metas.
				// Success Message.
				'_srfm_submit_type'              => 'string',
				// Security.
				'_srfm_captcha_security_type'    => 'string',
				'_srfm_form_recaptcha'           => 'string',
				// post meta to store if the form is AI generated.
				'_srfm_is_ai_generated'          => 'boolean',
			]
		);

		// Form Custom CSS meta.
		register_post_meta(
			'sureforms_form',
			'_srfm_form_custom_css',
			[
				'show_in_rest'      => true,
				'type'              => 'string',
				'single'            => true,
				'auth_callback'     => static function() {
					return current_user_can( 'edit_posts' );
				},
				'sanitize_callback' => static function( $meta_value ) {
					return wp_kses_post( $meta_value );
				},
			]
		);

		foreach ( $metas as $meta => $type ) {
			register_meta(
				'post',
				$meta,
				[
					'object_subtype'    => SRFM_FORMS_POST_TYPE,
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => $type,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => static function() {
						return current_user_can( 'edit_posts' );
					},
				]
			);
		}

		// Registers meta to handle values associated with form styling.
		register_post_meta(
			SRFM_FORMS_POST_TYPE,
			'_srfm_instant_form_settings',
			[
				'single'        => true,
				'type'          => 'object',
				'auth_callback' => '__return_true',
				'show_in_rest'  => [
					'schema' => [
						'type'       => 'object',
						'properties' => [
							'site_logo'              => [
								'type' => 'string',
							],
							'site_logo_id'           => [
								'type' => 'integer',
							],
							// Form page banner settings.
							'cover_type'             => [
								'type' => 'string',
							],
							'cover_color'            => [
								'type' => 'string',
							],
							'cover_image'            => [
								'type' => 'string',
							],
							'cover_image_id'         => [
								'type' => 'integer',
							],
							// Form page background settings.
							'bg_type'                => [
								'type' => 'string',
							],
							'bg_color'               => [
								'type' => 'string',
							],
							'bg_image'               => [
								'type' => 'string',
							],
							'bg_image_id'            => [
								'type' => 'integer',
							],
							'enable_instant_form'    => [
								'type' => 'boolean',
							],
							'form_container_width'   => [
								'type' => 'integer',
							],
							'single_page_form_title' => [
								'type' => 'boolean',
							],
							'use_banner_as_page_background' => [
								'type' => 'boolean',
							],
						],
					],
				],
				'default'       => [
					'bg_type'                       => 'color',
					'bg_color'                      => '#ffffff',
					'bg_image'                      => '',
					'site_logo'                     => '',
					'cover_type'                    => 'color',
					'cover_color'                   => '#111C44',
					'cover_image'                   => '',
					'enable_instant_form'           => false,
					'form_container_width'          => 620,
					'single_page_form_title'        => true,
					'use_banner_as_page_background' => false,
				],
			]
		);

		register_post_meta(
			SRFM_FORMS_POST_TYPE,
			'_srfm_forms_styling',
			[
				'single'        => true,
				'type'          => 'object',
				'auth_callback' => '__return_true',
				'show_in_rest'  => [
					'schema' => [
						'type'       => 'object',
						'properties' => [
							'primary_color'               => [
								'type' => 'string',
							],
							'text_color'                  => [
								'type' => 'string',
							],
							'text_color_on_primary'       => [
								'type' => 'string',
							],
							'field_spacing'               => [
								'type' => 'string',
							],
							'submit_button_alignment'     => [
								'type' => 'string',
							],
							'bg_type'                     => [
								'type' => 'string',
							],
							'bg_color'                    => [
								'type' => 'string',
							],
							'bg_image'                    => [
								'type' => 'string',
							],
							'bg_image_id'                 => [
								'type' => 'integer',
							],
							// Image Properties.
							'bg_image_position'           => [
								'type'       => 'object',
								'properties' => [
									'x' => [
										'type'   => 'number',
										'format' => 'float',
									],
									'y' => [
										'type'   => 'number',
										'format' => 'float',
									],
								],
							],
							'bg_image_attachment'         => [
								'type' => 'string',
							],
							'bg_image_repeat'             => [
								'type' => 'string',
							],
							'bg_image_size'               => [
								'type' => 'string',
							],
							'bg_image_size_custom'        => [
								'type' => 'integer',
							],
							'bg_image_size_custom_unit'   => [
								'type' => 'string',
							],
							// Gradient Properties.
							'bg_gradient'                 => [
								'type' => 'string',
							],
							'gradient_type'               => [
								'type' => 'string',
							],
							'bg_gradient_type'            => [
								'type' => 'string',
							],
							'bg_gradient_color_1'         => [
								'type' => 'string',
							],
							'bg_gradient_color_2'         => [
								'type' => 'string',
							],
							'bg_gradient_angle'           => [
								'type' => 'integer',
							],
							'bg_gradient_location_1'      => [
								'type' => 'integer',
							],
							'bg_gradient_location_2'      => [
								'type' => 'integer',
							],
							// Overlay Properties.
							'bg_overlay_size'             => [
								'type' => 'string',
							],
							'bg_gradient_overlay_type'    => [
								'type' => 'string',
							],
							'bg_overlay_opacity'          => [
								'type' => 'number',
							],
							'bg_overlay_image'            => [
								'type' => 'string',
							],
							'bg_overlay_image_id'         => [
								'type' => 'integer',
							],
							'bg_image_overlay_color'      => [
								'type' => 'string',
							],
							'bg_overlay_custom_size_unit' => [
								'type' => 'string',
							],
							'bg_overlay_custom_size'      => [
								'type' => 'integer',
							],
							'bg_overlay_blend_mode'       => [
								'type' => 'string',
							],
							'bg_overlay_position'         => [
								'type'       => 'object',
								'properties' => [
									'x' => [
										'type'   => 'number',
										'format' => 'float',
									],
									'y' => [
										'type'   => 'number',
										'format' => 'float',
									],
								],
							],
							'bg_overlay_attachment'       => [
								'type' => 'string',
							],
							'bg_overlay_repeat'           => [
								'type' => 'string',
							],
							// Gradient Overlay Properties.
							'bg_overlay_gradient'         => [
								'type' => 'string',
							],
							'overlay_gradient_type'       => [
								'type' => 'string',
							],
							'bg_overlay_gradient_type'    => [
								'type' => 'string',
							],
							'bg_overlay_gradient_color_1' => [
								'type' => 'string',
							],
							'bg_overlay_gradient_color_2' => [
								'type' => 'string',
							],
							'bg_overlay_gradient_angle'   => [
								'type' => 'integer',
							],
							'bg_overlay_gradient_location_1' => [
								'type' => 'integer',
							],
							'bg_overlay_gradient_location_2' => [
								'type' => 'integer',
							],
							// Form Padding.
							'form_padding_top'            => [
								'type' => 'number',
							],
							'form_padding_right'          => [
								'type' => 'number',
							],
							'form_padding_bottom'         => [
								'type' => 'number',
							],
							'form_padding_left'           => [
								'type' => 'number',
							],
							'form_padding_unit'           => [
								'type' => 'string',
							],
							'form_padding_link'           => [
								'type' => 'boolean',
							],
							// Border Radius.
							'form_border_radius_top'      => [
								'type' => 'number',
							],
							'form_border_radius_right'    => [
								'type' => 'number',
							],
							'form_border_radius_bottom'   => [
								'type' => 'number',
							],
							'form_border_radius_left'     => [
								'type' => 'number',
							],
							'form_border_radius_unit'     => [
								'type' => 'string',
							],
							'form_border_radius_link'     => [
								'type' => 'boolean',
							],
							// Form Padding and Border Radius.
							// Form Padding.
							'instant_form_padding_top'    => [
								'type' => 'number',
							],
							'instant_form_padding_right'  => [
								'type' => 'number',
							],
							'instant_form_padding_bottom' => [
								'type' => 'number',
							],
							'instant_form_padding_left'   => [
								'type' => 'number',
							],
							'instant_form_padding_unit'   => [
								'type' => 'string',
							],
							'instant_form_padding_link'   => [
								'type' => 'boolean',
							],
							// Border Radius.
							'instant_form_border_radius_top' => [
								'type' => 'number',
							],
							'instant_form_border_radius_right' => [
								'type' => 'number',
							],
							'instant_form_border_radius_bottom' => [
								'type' => 'number',
							],
							'instant_form_border_radius_left' => [
								'type' => 'number',
							],
							'instant_form_border_radius_unit' => [
								'type' => 'string',
							],
							'instant_form_border_radius_link' => [
								'type' => 'boolean',
							],
						],
					],
				],
				'default'       => [
					'primary_color'                     => '#111C44',
					'text_color'                        => '#1E1E1E',
					'text_color_on_primary'             => '#FFFFFF',
					'field_spacing'                     => 'medium',
					'submit_button_alignment'           => 'left',
					'bg_type'                           => 'color',
					'bg_color'                          => '#ffffff',
					'bg_image'                          => '',
					'bg_image_position'                 => [
						'x' => 0.5,
						'y' => 0.5,
					],
					'bg_image_attachment'               => 'scroll',
					'bg_image_repeat'                   => 'no-repeat',
					'bg_image_size'                     => 'cover',
					'bg_image_size_custom'              => 100, // Image width when set to custom.
					'bg_image_size_custom_unit'         => '%',
					'gradient_type'                     => 'basic',
					'bg_gradient_type'                  => 'linear',
					'bg_gradient_color_1'               => '#FFC9B2',
					'bg_gradient_color_2'               => '#C7CBFF',
					'bg_gradient_angle'                 => 90,
					'bg_gradient_location_1'            => 0,
					'bg_gradient_location_2'            => 100,
					'bg_overlay_size'                   => 'cover',
					'bg_gradient_overlay_type'          => '',
					'bg_overlay_opacity'                => 1,
					'bg_overlay_image'                  => '',
					'bg_image_overlay_color'            => '#FFFFFF75',
					'bg_overlay_custom_size_unit'       => '%',
					'bg_overlay_custom_size'            => 100,
					'bg_overlay_position'               => [
						'x' => 0.5,
						'y' => 0.5,
					],
					'bg_overlay_attachment'             => 'scroll',
					'bg_overlay_repeat'                 => 'no-repeat',
					'bg_overlay_blend_mode'             => 'normal',
					// Gradient Overlay Properties.
					'overlay_gradient_type'             => 'basic',
					'bg_overlay_gradient_type'          => 'linear',
					'bg_overlay_gradient_color_1'       => '#FFC9B2',
					'bg_overlay_gradient_color_2'       => '#C7CBFF',
					'bg_overlay_gradient_angle'         => 90,
					'bg_overlay_gradient_location_1'    => 0,
					'bg_overlay_gradient_location_2'    => 100,
					// Form Properties.
					// Padding.
					'form_padding_top'                  => 0,
					'form_padding_right'                => 0,
					'form_padding_bottom'               => 0,
					'form_padding_left'                 => 0,
					'form_padding_unit'                 => 'px',
					'form_padding_link'                 => true,
					// Border Radius.
					'form_border_radius_top'            => 0,
					'form_border_radius_right'          => 0,
					'form_border_radius_bottom'         => 0,
					'form_border_radius_left'           => 0,
					'form_border_radius_unit'           => 'px',
					'form_border_radius_link'           => true,
					// Form Properties.
					// Padding.
					'instant_form_padding_top'          => 32,
					'instant_form_padding_right'        => 32,
					'instant_form_padding_bottom'       => 32,
					'instant_form_padding_left'         => 32,
					'instant_form_padding_unit'         => 'px',
					'instant_form_padding_link'         => true,
					// Border Radius.
					'instant_form_border_radius_top'    => 12,
					'instant_form_border_radius_right'  => 12,
					'instant_form_border_radius_bottom' => 12,
					'instant_form_border_radius_left'   => 12,
					'instant_form_border_radius_unit'   => 'px',
					'instant_form_border_radius_link'   => true,
				],
			]
		);

		// Email notification Metas.
		register_post_meta(
			'sureforms_form',
			'_srfm_email_notification',
			[
				'single'        => true,
				'type'          => 'array',
				'auth_callback' => '__return_true',
				'show_in_rest'  => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id'             => [
									'type' => 'integer',
								],
								'status'         => [
									'type' => 'boolean',
								],
								'is_raw_format'  => [
									'type' => 'boolean',
								],
								'name'           => [
									'type' => 'string',
								],
								'email_to'       => [
									'type' => 'string',
								],
								'email_reply_to' => [
									'type' => 'string',
								],
								'from_name'      => [
									'type' => 'string',
								],
								'from_email'     => [
									'type' => 'string',
								],
								'email_cc'       => [
									'type' => 'string',
								],
								'email_bcc'      => [
									'type' => 'string',
								],
								'subject'        => [
									'type' => 'string',
								],
								'email_body'     => [
									'type' => 'string',
								],
							],
						],
					],
				],
				'default'       => [
					[
						'id'             => 1,
						'status'         => true,
						'is_raw_format'  => false,
						'name'           => __( 'Admin Notification Email', 'sureforms' ),
						'email_to'       => '{admin_email}',
						'email_reply_to' => '{admin_email}',
						'from_name'      => '{site_title}',
						'from_email'     => '{admin_email}',
						'email_cc'       => '{admin_email}',
						'email_bcc'      => '{admin_email}',
						'subject'        => sprintf( /* translators: %s: Form title smart tag */ __( 'New Form Submission - %s', 'sureforms' ), '{form_title}' ),
						'email_body'     => '{all_data}',
					],
				],
			]
		);

		// Compliance Settings metas.
		register_post_meta(
			'sureforms_form',
			'_srfm_compliance',
			[
				'single'        => true,
				'type'          => 'array',
				'auth_callback' => '__return_true',
				'show_in_rest'  => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id'                   => [
									'type' => 'string',
								],
								'gdpr'                 => [
									'type' => 'boolean',
								],
								'do_not_store_entries' => [
									'type' => 'boolean',
								],
								'auto_delete_entries'  => [
									'type' => 'boolean',
								],
								'auto_delete_days'     => [
									'type' => 'string',
								],
							],
						],
					],
				],
				'default'       => [
					[
						'id'                   => 'gdpr',
						'gdpr'                 => false,
						'do_not_store_entries' => false,
						'auto_delete_entries'  => false,
						'auto_delete_days'     => '',
					],
				],
			]
		);

		// form confirmation.
		register_post_meta(
			'sureforms_form',
			'_srfm_form_confirmation',
			[
				'single'            => true,
				'type'              => 'array',
				'auth_callback'     => static function() {
					return current_user_can( 'manage_options' );
				},
				'sanitize_callback' => static function( $meta_value ) {
					if ( ! is_array( $meta_value ) ) {
						return [];
					}
					$sanitized = [];
					foreach ( $meta_value as $item ) {
						if ( ! is_array( $item ) ) {
							continue;
						}
						$sanitized_item = [
							'id'                  => isset( $item['id'] ) ? intval( $item['id'] ) : 0,
							'confirmation_type'   => isset( $item['confirmation_type'] ) ? sanitize_text_field( $item['confirmation_type'] ) : '',
							'page_url'            => isset( $item['page_url'] ) ? esc_url_raw( $item['page_url'] ) : '',
							'custom_url'          => isset( $item['custom_url'] ) ? esc_url_raw( $item['custom_url'] ) : '',
							'message'             => isset( $item['message'] ) ? Helper::strip_js_attributes( $item['message'] ) : '',
							'submission_action'   => isset( $item['submission_action'] ) ? sanitize_text_field( $item['submission_action'] ) : '',
							'enable_query_params' => isset( $item['enable_query_params'] ) ? filter_var( $item['enable_query_params'], FILTER_VALIDATE_BOOLEAN ) : false,
							'query_params'        => isset( $item['query_params'] ) ? array_map( 'sanitize_text_field', (array) $item['query_params'] ) : [],
						];

						$sanitized_item = apply_filters( 'srfm_form_confirmation_params', $sanitized_item, $item );

						$sanitized[] = $sanitized_item;
					}
					return $sanitized;
				},
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id'                  => [
									'type' => 'integer',
								],
								'confirmation_type'   => [
									'type' => 'string',
								],
								'page_url'            => [
									'type' => 'string',
								],
								'custom_url'          => [
									'type' => 'string',
								],
								'message'             => [
									'type' => 'string',
								],
								'submission_action'   => [
									'type' => 'string',
								],
								'enable_query_params' => [
									'type' => 'boolean',
								],
								'query_params'        => [
									'type' => 'array',
								],
							],
						],
					],
				],
				'default'           => [
					[
						'id'                => 1,
						'confirmation_type' => 'same page',
						'page_url'          => '',
						'custom_url'        => '',
						'message'           => '<p style="text-align: center;"><img src="' . esc_attr( $check_icon ) . '" alt="" aria-hidden="true"></img></p><h2 style="text-align: center;">' . esc_html__( 'Thank you', 'sureforms' ) . '</h2>',
						'submission_action' => 'hide form',
					],
				],
			]
		);

		// conditional logic.
		do_action( 'srfm_register_conditional_logic_post_meta' );
		/**
		 * Hook for registering additional Post Meta
		 */
		do_action( 'srfm_register_additional_post_meta' );
	}

	/**
	 * Custom Shortcode.
	 *
	 * @param array<mixed> $atts Attributes.
	 * @return string|false. $content Post Content.
	 * @since 0.0.1
	 */
	public function forms_shortcode( array $atts ) {
		$atts = shortcode_atts(
			[
				'id'         => '',
				'show_title' => true,
			],
			$atts
		);

		$id   = intval( $atts['id'] );
		$post = get_post( $id );

		if ( ! empty( $id ) && $post ) {
			return Generate_Form_Markup::get_form_markup( $id, ! filter_var( $atts['show_title'], FILTER_VALIDATE_BOOLEAN ), '', 'post', true );
		}

		return '';
	}

	/**
	 * Add custom column header.
	 *
	 * @param array<mixed> $columns Attributes.
	 * @return array<mixed> $columns Post Content.
	 * @since 0.0.1
	 */
	public function custom_form_columns( $columns ) {
		return [
			'cb'        => $columns['cb'],
			'title'     => $columns['title'],
			'sureforms' => __( 'Shortcode', 'sureforms' ),
			'entries'   => __( 'Entries', 'sureforms' ),
			'author'    => $columns['author'],
			'date'      => $columns['date'],
		];
	}

	/**
	 * Populate custom column with data.
	 *
	 * @param string $column Attributes.
	 * @param int    $post_id Attributes.
	 * @return void
	 * @since 0.0.1
	 */
	public function custom_form_column_data( $column, $post_id ) {
		if ( 'sureforms' === $column ) {
			ob_start();
			?>
			<div class="srfm-shortcode-container">
				<input id="srfm-shortcode-input-<?php echo esc_attr( Helper::get_string_value( $post_id ) ); ?>" class="srfm-shortcode-input" type="text" readonly value="[sureforms id='<?php echo esc_attr( Helper::get_string_value( $post_id ) ); ?>']" />
				<button type="button" class="components-button components-clipboard-button has-icon srfm-shortcode" onclick="handleFormShortcode(this)">
					<span id="srfm-copy-icon" class="dashicon dashicons dashicons-admin-page"></span>
				</button>
			</div>
			<?php
			ob_end_flush();
		}
		if ( 'entries' === $column ) {
			// Entries URL to redirect user based on the form ID.
			$entries_url = wp_nonce_url(
				add_query_arg(
					[
						'form_filter' => $post_id,
					],
					admin_url( 'admin.php?page=sureforms_entries' )
				),
				'srfm_entries_action'
			);

			// Get the entry count for the form.
			$entries_count = Entries::get_total_entries_by_status( 'all', $post_id );

			ob_start();
			?>
				<p class="srfm-entries-number"><a href="<?php echo esc_url( $entries_url ); ?>"><?php echo esc_html( Helper::get_string_value( $entries_count ) ); ?></a></p>
			<?php
			ob_end_flush();
		}
	}

	/**
	 * Show the import form popup
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function import_form_popup() {
		$screen = get_current_screen();
		$id     = $screen ? $screen->id : '';
		if ( 'edit-sureforms_form' === $id ) {
			?>
			<div class="srfm-import-plugin-wrap">
				<div class="srfm-import-wrap">
					<p class="srfm-import-help"><?php echo esc_html__( 'Please choose the SureForms export file (.json) that you wish to import.', 'sureforms' ); ?></p>
					<form method="post" enctype="multipart/form-data" class="srfm-import-form">
						<input type="file" id="srfm-import-file" onchange="handleFileChange(event)" name="import form" accept=".json">
						<input type="submit" name="import-form-submit" id="import-form-submit" class="srfm-import-button" value="<?php esc_attr_e( 'Import Now', 'sureforms' ); ?>" disabled>
					</form>
					<p id="srfm-import-error"><?php echo esc_html__( 'There is some error in json file, please export the SureForms Forms again.', 'sureforms' ); ?></p>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Redirect to home page if instant form is not enabled.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function srfm_instant_form_redirect() {

		$form_id = Helper::get_integer_value( get_the_ID() );

		$instant_form_settings = Helper::get_array_value( Helper::get_post_meta( $form_id, '_srfm_instant_form_settings' ) );
		$enable_instant_form   = ! empty( $instant_form_settings['enable_instant_form'] ) ? boolval( $instant_form_settings['enable_instant_form'] ) : false;

		if ( $enable_instant_form ) {
			return;
		}

		$form_preview = '';

		$form_preview_attr = isset( $_GET['preview'] ) ? sanitize_text_field( wp_unslash( $_GET['preview'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not needed here.

		if ( $form_preview_attr ) {
			$form_preview = filter_var( $form_preview_attr, FILTER_VALIDATE_BOOLEAN );
		}

		if ( is_singular( 'sureforms_form' ) && ! $form_preview && ! current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( home_url() );
			return;
		}
	}

	/**
	 * Restrict RankMath meta boxes in edit page.
	 *
	 * @since 0.0.5
	 * @return void
	 */
	public function restrict_data() {
		add_filter( 'rank_math/excluded_post_types', [ $this, 'unset_sureforms_post_type' ] );
	}

	/**
	 * Remove SureForms post type from RankMath and Yoast.
	 *
	 * @param array<mixed> $post_types Post types.
	 * @since 0.0.5
	 * @return array<mixed> $post_types Modified post types.
	 */
	public function unset_sureforms_post_type( $post_types ) {
		return array_filter(
			$post_types,
			static function( $post_type ) {
				if ( is_array( $post_type ) && isset( $post_type['name'] ) ) {
					return SRFM_FORMS_POST_TYPE !== $post_type['name'];
				}
					return SRFM_FORMS_POST_TYPE !== $post_type;
			}
		);
	}

	/**
	 * Restrict unwanted insertions from the AIOSEO plugin.
	 *
	 * This method ensures that the SureForms post type is excluded from AIOSEO's
	 * public post types unless the current page is related to AIOSEO settings.
	 *
	 * @return void
	 * @since 1.6.0
	 */
	public function restrict_in_aioseo_plugin() {
		/**
		 * Checks if the AIOSEO plugin is installed and excludes the SureForms post type from AIOSEO's public post types.
		 *
		 * - Verifies the presence of the AIOSEO_DIR constant to ensure AIOSEO is installed.
		 * - Allows AIOSEO functionality on its own settings pages by checking the `REQUEST_URI` and `page` query parameter.
		 * - Excludes the SureForms post type from AIOSEO's public post types using the `aioseo_public_post_types` filter.
		 *
		 * Security Note:
		 * - Nonce verification is intentionally skipped (`phpcs:ignore WordPress.Security.NonceVerification.Recommended`)
		 *   because this code is only performing a read operation to check the current request URI and query parameters.
		 *   It does not modify or process sensitive data, making nonce verification unnecessary in this context.
		 */
		// Check if AIOSEO is installed by verifying the AIOSEO_DIR constant.
		if ( ! defined( 'AIOSEO_DIR' ) ) {
			return;
		}

		// Allow AIOSEO functionality on its own settings pages.
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			if ( strpos( $request_uri, 'admin.php' ) !== false ) {
				if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here because Safe here as we're only reading the `page` parameter.
					$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here because only we are reading the `page` parameter.
					if ( strpos( $page, 'aioseo' ) !== false ) {
						return;
					}
				}
			}
		}

		// Exclude the SureForms post type from AIOSEO's public post types.
		add_filter( 'aioseo_public_post_types', [ $this, 'unset_sureforms_post_type' ] );
	}

	/**
	 * Restrict interference of other plugins with SureForms.
	 *
	 * @since 0.0.5
	 * @return void
	 */
	private function restrict_unwanted_insertions() {
		// Restrict RankMath metaboxes in edit page.
		add_action( 'cmb2_admin_init', [ $this, 'restrict_data' ] );

		// Restrict Yoast columns.
		add_filter( 'wpseo_accessible_post_types', [ $this, 'unset_sureforms_post_type' ] );
		add_filter( 'wpseo_metabox_prio', '__return_false' );

		// Restrict AIOSEO columns.
		$this->restrict_in_aioseo_plugin();
	}
}
