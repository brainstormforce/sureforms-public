<?php
/**
 * Post Types Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;
use WP_Admin_Bar;
use WP_Post;
use WP_REST_Response;

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
		add_shortcode( 'sureforms', [ $this, 'forms_shortcode' ] );
		add_action( 'manage_posts_extra_tablenav', [ $this, 'maybe_render_blank_form_state' ] );
		add_action( 'admin_bar_menu', [ $this, 'remove_admin_bar_menu_item' ], 80, 1 );
		add_action( 'template_redirect', [ $this, 'srfm_instant_form_redirect' ] );
		add_action( 'template_redirect', [ $this, 'disable_sureforms_archive_page' ], 9 );
		add_action( 'load-edit.php', [ $this, 'redirect_forms_listing_page' ] );

		add_filter( 'rest_prepare_sureforms_form', [ $this, 'sureforms_normalize_meta_for_rest' ], 10, 2 );
		add_action( 'admin_bar_menu', [ $this, 'add_edit_form_to_admin_bar_menu' ], 100 );
	}

	/**
	 * Redirect the forms listing page to the updated forms page.
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function redirect_forms_listing_page() {
		global $pagenow;

		if ( 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && SRFM_FORMS_POST_TYPE === $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is not required for the redirection.
			wp_safe_redirect( admin_url( 'admin.php?page=sureforms_forms' ) );
			exit;
		}
	}

	/**
	 * Add "Edit Form" link to the admin bar menu.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance.
	 * @since 2.0.0
	 * @return void
	 */
	public function add_edit_form_to_admin_bar_menu( $wp_admin_bar ) {

		// Bail early if admin bar or user isn’t available.
		if ( ! is_user_logged_in() || ! is_admin_bar_showing() || ! $wp_admin_bar instanceof WP_Admin_Bar ) {
			return;
		}

		global $post;

		// Bail if no valid post or wrong post type.
		if ( empty( $post ) || SRFM_FORMS_POST_TYPE !== $post->post_type ) {
			return;
		}

		$edit_link = get_edit_post_link( $post->ID );
		if ( ! $edit_link ) {
			return;
		}

		$wp_admin_bar->add_node(
			[
				'id'    => 'edit-form',
				'title' => sprintf(
					'<span class="ab-icon dashicons dashicons-edit" style="line-height:1.2;margin-right:4px;"></span>
				<span class="ab-label" style="position:relative;top:-1px;">%s</span>',
					esc_html__( 'Edit Form', 'sureforms' )
				),
				'href'  => esc_url( $edit_link ),
				'meta'  => [
					'title' => esc_attr__( 'Edit this form', 'sureforms' ),
				],
				'html'  => true,
			]
		);
	}

	/**
	 * Remove this method in the future once _srfm_form_confirmation meta is updated.
	 * Normalize the _srfm_form_confirmation meta before it's sent to the REST API.
	 * Ensures the meta data is type-safe and includes necessary defaults like `hide_copy`.
	 *
	 * @param WP_REST_Response $response The REST response object.
	 * @param WP_Post          $post     The post object.
	 *
	 * @return WP_REST_Response Modified REST response with normalized meta.
	 * @since 1.7.3
	 */
	public function sureforms_normalize_meta_for_rest( $response, $post ) {
		$meta_raw          = get_post_meta( $post->ID, '_srfm_form_confirmation', true );
		$form_confirmation = maybe_unserialize( is_string( $meta_raw ) ? $meta_raw : '' );

		if ( ! is_array( $form_confirmation ) ) {
			return $response;
		}

		foreach ( $form_confirmation as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$form_confirmation[ $index ]['hide_copy']         = ! empty( $item['hide_copy'] );
			$form_confirmation[ $index ]['hide_download_all'] = ! empty( $item['hide_download_all'] );
		}

		$response_data = $response->get_data();
		if ( is_array( $response_data ) ) {
			if ( ! isset( $response_data['meta'] ) || ! is_array( $response_data['meta'] ) ) {
				$response_data['meta'] = [];
			}

			$response_data['meta']['_srfm_form_confirmation'] = $form_confirmation;
			$response->set_data( $response_data );
		}
		return $response;
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
		?>
		<div class="sureform-add-new-form">
			<p class="sureform-blank-page-title"><?php echo esc_html( $title ); ?></p>
			<p class="sureform-blank-page-subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<img src="<?php echo esc_url( SRFM_URL . '/images/' . $image . '.svg' ); ?>" alt=""/>
			<?php if ( ! empty( $button_text ) && ! empty( $button_url ) ) { ?>
				<div class="sureforms-add-new-form-container">
					<a class="sf-add-new-form-button" href="<?php echo esc_url( $button_url ); ?>">
						<div class="button-secondary"><?php echo esc_html( $button_text ); ?></div>
					</a>
					<?php echo wp_kses_post( $after_button ); ?>
				</div>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Render blank state for add new form screen.
	 *
	 * @param string $post_type Post type.
	 * @return void
	 * @since  0.0.1
	 */
	public function sureforms_render_blank_state( $post_type ) {
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
				'show_in_menu'      => false,
				'show_in_nav_menus' => true,
				'capabilities'      => [
					'edit_post'          => 'manage_options',
					'read_post'          => 'manage_options',
					'delete_post'        => 'manage_options',
					'edit_posts'         => 'manage_options',
					'edit_others_posts'  => 'manage_options',
					'publish_posts'      => 'manage_options',
					'read_private_posts' => 'manage_options',
					'create_posts'       => 'manage_options',
				],
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
	 * Show blank slate styles.
	 *
	 * @return void
	 * @since  0.0.1
	 */
	public function get_blank_state_styles() {
		?>
		<style type="text/css">
			.sf-add-new-form-button:focus { 
				box-shadow: none !important; 
				outline: none !important; 
			} 
			#posts-filter .wp-list-table, 
			#posts-filter .tablenav.top, 
			.tablenav.bottom .actions, 
			.wrap .subsubsub { 
				display: none; 
			} 
			#posts-filter .tablenav.bottom { 
				height: auto; 
			} 
			.sureform-add-new-form { 
				display: flex; 
				flex-direction: column; 
				gap: 8px; 
				justify-content: center; 
				align-items: center; 
				padding: 24px 0 24px 0; 
			} 
			.sureform-blank-page-title { 
				color: var(--dashboard-heading); 
				font-family: Inter; 
				font-size: 22px; 
				font-style: normal; 
				font-weight: 600; 
				line-height: 28px; 
				margin: 0; 
			} 
			.sureform-blank-page-subtitle { 
				color: var(--dashboard-text); 
				margin: 0; 
				font-family: Inter; 
				font-size: 14px; 
				font-style: normal; 
				font-weight: 400; 
				line-height: 16px; 
			}
		</style>
		<?php
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
				'show_in_rest'      => [
					'schema' => [
						'type'    => 'string',
						'context' => [ 'edit' ],
					],
				],
				'type'              => 'string',
				'single'            => true,
				'auth_callback'     => static function() {
					return Helper::current_user_can();
				},
				'sanitize_callback' => static function( $meta_value ) {
					return wp_kses_post( $meta_value );
				},
			]
		);

		// Get default values for meta keys.
		$default_meta_keys = Create_New_Form::get_default_meta_keys();

		foreach ( $metas as $meta => $type ) {
			// Get default value if exists.
			$default_value = $default_meta_keys[ $meta ] ?? null;

			$meta_args = [
				'show_in_rest'      => [
					'schema' => [
						'type'    => $type,
						'context' => [ 'edit' ],
					],
				],
				'single'            => true,
				'type'              => $type,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => static function() {
					return Helper::current_user_can();
				},
			];

			// Add default value if it exists.
			if ( null !== $default_value ) {
				$meta_args['default'] = $default_value;
			}

			register_post_meta(
				SRFM_FORMS_POST_TYPE,
				$meta,
				$meta_args
			);
		}

		// Registers meta to handle values associated with form styling.
		register_post_meta(
			SRFM_FORMS_POST_TYPE,
			'_srfm_instant_form_settings',
			[
				'single'        => true,
				'type'          => 'object',
				'auth_callback' => static function() {
					return Helper::current_user_can();
				},
				'show_in_rest'  => [
					'schema' => [
						'type'       => 'object',
						'context'    => [ 'edit' ],
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
				'auth_callback' => static function() {
					return Helper::current_user_can();
				},
				'show_in_rest'  => [
					'schema' => [
						'type'       => 'object',
						'context'    => [ 'edit' ],
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
				'auth_callback' => static function() {
					return Helper::current_user_can();
				},
				'show_in_rest'  => [
					'schema' => [
						'type'    => 'array',
						'context' => [ 'edit' ],
						'items'   => [
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
				'auth_callback' => static function() {
					return Helper::current_user_can();
				},
				'show_in_rest'  => [
					'schema' => [
						'type'    => 'array',
						'context' => [ 'edit' ],
						'items'   => [
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

		ob_start();
		?>
		<p style="text-align: center;"><img src="<?php echo esc_attr( $check_icon ); ?>" alt="" aria-hidden="true" /></p><h2 style="text-align: center;"><?php echo esc_html__( 'Thank you', 'sureforms' ); ?></h2><p style="text-align: center;"><?php echo esc_html__( 'Your form has been submitted successfully. We\'ll review your details and get back to you soon.', 'sureforms' ); ?></p>
		<?php
		$default_confirmation_message = ob_get_clean();

		// form confirmation.
		register_post_meta(
			'sureforms_form',
			'_srfm_form_confirmation',
			[
				'single'            => true,
				'type'              => 'array',
				'auth_callback'     => static function() {
					return Helper::current_user_can();
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
							'query_params'        => isset( $item['query_params'] ) && is_array( $item['query_params'] )
								? array_map(
									static function ( $pair ) {
										if ( ! is_array( $pair ) ) {
											return [];
										}
										$key   = key( $pair );
										$value = current( $pair );

										return [
											sanitize_text_field( Helper::get_string_value( $key ) ) => sanitize_text_field( Helper::get_string_value( $value ) ),
										];
									},
									$item['query_params']
								)
								: [],
						];

						$sanitized_item = apply_filters( 'srfm_form_confirmation_params', $sanitized_item, $item );

						$sanitized[] = $sanitized_item;
					}
					return $sanitized;
				},
				'show_in_rest'      => [
					'schema' => [
						'type'    => 'array',
						'context' => [ 'edit' ],
						'items'   => [
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
						'message'           => $default_confirmation_message,
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

		register_meta(
			'post',
			'_srfm_form_restriction',
			[
				'type'              => 'string',  // Will store as JSON string.
				'single'            => true,    // Store as single value.
				'show_in_rest'      => [
					'schema' => [
						'type'    => 'string',
						'context' => [ 'edit' ],
					],
				],
				// Custom callback to sanitize the data.
				'sanitize_callback' => [ $this, 'sanitize_form_restriction_data' ],
				'object_subtype'    => SRFM_FORMS_POST_TYPE,
				'auth_callback'     => static function () {
					return Helper::current_user_can();
				},
				'default'           => wp_json_encode(
					[
						'status'     => false,
						'maxEntries' => 0,
						'date'       => '',
						'hours'      => '12',
						'minutes'    => '00',
						'meridiem'   => 'AM',
						'message'    => Translatable::get_default_form_restriction_message(),
					]
				),
			]
		);
	}

	/**
	 * Sanitizes the form restriction data.
	 *
	 * @param mixed $meta_value The meta value to sanitize.
	 * @return string|false Sanitized JSON string.
	 */
	public function sanitize_form_restriction_data( $meta_value ) {
		if ( empty( $meta_value ) || ! is_string( $meta_value ) ) {
			return wp_json_encode( [] );
		}

		$meta_value = json_decode( $meta_value, true );

		if ( ! is_array( $meta_value ) || json_last_error() !== JSON_ERROR_NONE ) {
			// If the JSON is invalid, return an empty array as JSON.
			return wp_json_encode( [] );
		}

		$sanitized = [
			'status'     => isset( $meta_value['status'] ) ? wp_validate_boolean( $meta_value['status'] ) : false,
			'maxEntries' => isset( $meta_value['maxEntries'] ) ? absint( $meta_value['maxEntries'] ) : 0,
			'date'       => isset( $meta_value['date'] ) ? sanitize_text_field( $meta_value['date'] ) : '',
			'hours'      => isset( $meta_value['hours'] ) ? sanitize_text_field( $meta_value['hours'] ) : '12',
			'minutes'    => isset( $meta_value['minutes'] ) ? sanitize_text_field( $meta_value['minutes'] ) : '00',
			'meridiem'   => isset( $meta_value['meridiem'] ) ? sanitize_text_field( $meta_value['meridiem'] ) : 'AM',
			'message'    => isset( $meta_value['message'] ) ? sanitize_textarea_field( $meta_value['message'] ) : Translatable::get_default_form_restriction_message(),
		];

		// Return the sanitized data as a JSON string.
		return wp_json_encode( $sanitized );
	}

	/**
	 * Custom Shortcode.
	 *
	 * @param array<mixed> $atts Attributes.
	 * @return string|false. $content Post Content.
	 * @since 0.0.1
	 */
	public function forms_shortcode( $atts ) {
		$atts = shortcode_atts(
			[
				'id'         => '',
				'show_title' => true,
			],
			$atts
		);

		$id   = intval( $atts['id'] );
		$post = get_post( $id );

		if ( ! empty( $id ) && $post && 'publish' === $post->post_status ) {
			return Generate_Form_Markup::get_form_markup( $id, ! filter_var( $atts['show_title'], FILTER_VALIDATE_BOOLEAN ), '', 'post', true );
		}

		return esc_html__( 'This form has been deleted or is unavailable.', 'sureforms' );
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

		if ( is_singular( 'sureforms_form' ) && ! $form_preview && ! Helper::current_user_can() ) {
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
