<?php
/**
 * Post Types Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use WP_Query;
use WP_Admin_Bar;
use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Generate_Form_Markup;
use SRFM\Inc\Helper;

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
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'init', [ $this, 'register_post_metas' ] );
		add_filter( 'manage_sureforms_form_posts_columns', [ $this, 'custom_form_columns' ] );
		add_action( 'manage_sureforms_form_posts_custom_column', [ $this, 'custom_form_column_data' ], 10, 2 );
		add_filter( 'manage_sureforms_entry_posts_columns', [ $this, 'custom_entry_columns' ] );
		add_action( 'manage_sureforms_entry_posts_custom_column', [ $this, 'custom_entry_column_data' ], 10, 2 );
		add_shortcode( 'sureforms', [ $this, 'forms_shortcode' ] );
		add_action( 'add_meta_boxes', [ $this, 'entries_meta_box' ] );
		add_action( 'restrict_manage_posts', [ $this, 'add_tax_filter' ] );
		add_action( 'manage_posts_extra_tablenav', [ $this, 'maybe_render_blank_form_state' ] );
		add_action( 'in_admin_header', [ $this, 'embed_page_header' ] );
		add_action( 'admin_head', [ $this, 'remove_entries_publishing_actions' ] );
		add_filter( 'post_row_actions', [ $this, 'modify_entries_list_row_actions' ], 10, 2 );
		add_filter( 'post_updated_messages', [ $this, 'entries_updated_message' ] );
		add_filter( 'bulk_actions-edit-sureforms_form', [ $this, 'register_modify_bulk_actions' ] );
		add_action( 'admin_notices', [ $this, 'import_form_popup' ] );
		add_action( 'admin_bar_menu', [ $this, 'remove_admin_bar_menu_item' ], 80, 1 );
		add_action( 'template_redirect', [ $this, 'srfm_instant_form_redirect' ] );}

	/**
	 * Add SureForms menu.
	 *
	 * @param string $title Parent slug.
	 * @param string $subtitle Parent slug.
	 * @param string $image Parent slug.
	 * @param string $button_text Parent slug.
	 * @param string $button_url Parent slug.
	 * @return void
	 * @since 0.0.1
	 */
	public function get_blank_page_markup( $title, $subtitle, $image, $button_text = '', $button_url = '' ) {
		echo '<div class="sureform-add-new-form">';

		echo '<p class="sureform-blank-page-title">' . esc_html( $title ) . '</p>';

		echo '<p class="sureform-blank-page-subtitle">' . esc_html( $subtitle ) . '</p>';

		echo '<img src="' . esc_url( SRFM_URL . '/images/' . $image . '.svg' ) . '">';

		if ( ! empty( $button_text ) && ! empty( $button_url ) ) {
			echo '<a class="sf-add-new-form-button" href="' . esc_url( $button_url ) . '"><div class="button-primary">' . esc_html( $button_text ) . '</div></a>';
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
			$page_name    = 'add-new-form';
			$new_form_url = admin_url( 'admin.php?page=' . $page_name );

			$this->get_blank_page_markup(
				esc_html__( 'Let’s build your first form', 'sureforms' ),
				esc_html__(
					'Craft beautiful and functional forms in minutes',
					'sureforms'
				),
				'add-new-form',
				esc_html__( 'Add New Form', 'sureforms' ),
				$new_form_url
			);
		}

		if ( SRFM_ENTRIES_POST_TYPE === $post_type ) {

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
				'has_archive'       => false,
				'show_ui'           => true,
				'supports'          => [ 'title', 'author', 'editor', 'custom-fields' ],
				'show_in_menu'      => 'sureforms_menu',
				'show_in_nav_menus' => true,
			]
		);

		$result_labels = [
			'name'               => _x( 'Entries', 'post type general name', 'sureforms' ),
			'singular_name'      => _x( 'Entry', 'post type singular name', 'sureforms' ),
			'menu_name'          => _x( 'Entries', 'admin menu', 'sureforms' ),
			'name_admin_bar'     => _x( 'Entry', 'add new on admin bar', 'sureforms' ),
			'add_new'            => _x( 'Add New', 'Entry', 'sureforms' ),
			'add_new_item'       => __( 'Add New Entry', 'sureforms' ),
			'new_item'           => __( 'New Entry', 'sureforms' ),
			'edit_item'          => __( 'View Entry', 'sureforms' ),
			'view_item'          => __( 'View Entry', 'sureforms' ),
			'all_items'          => __( 'Entries', 'sureforms' ),
			'search_items'       => __( 'Search Entries', 'sureforms' ),
			'parent_item_colon'  => __( 'Parent Entries:', 'sureforms' ),
			'not_found'          => __( 'No results found.', 'sureforms' ),
			'not_found_in_trash' => __( 'No results found in Trash.', 'sureforms' ),
		];
		register_post_type(
			SRFM_ENTRIES_POST_TYPE,
			[
				'labels'              => $result_labels,
				'supports'            => [ 'title' ],
				'public'              => false,
				'show_in_rest'        => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => true,
				'capability_type'     => 'post',
				'capabilities'        => [
					'create_posts' => 'do_not_allow',
				],
				'map_meta_cap'        => true,
				'show_ui'             => true,
				'show_in_menu'        => 'sureforms_menu',
			]
		);
		register_taxonomy(
			'sureforms_tax',
			'sureforms_entry',
			[
				'label'             => __( 'Form ID', 'sureforms' ),
				'hierarchical'      => true,
				'capabilities'      => [
					'assign_terms' => 'god',
					'edit_terms'   => 'god',
					'manage_terms' => 'god',
				],
				'public'            => false,
				'show_in_rest'      => true,
				'show_admin_column' => false,
				'show_in_nav_menus' => false,
				'show_ui'           => false,
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
	 * Modify post update message for Entry post type.
	 *
	 * @param string $messages Post type.
	 * @return string
	 * @since  0.0.1
	 */
	public function entries_updated_message( $messages ) {
		global $post_ID;

		$post_type = get_post_type( $post_ID );

		if ( SRFM_ENTRIES_POST_TYPE === $post_type ) {
			// @phpstan-ignore-next-line -- False positive
			$messages['post'][1] = __( 'Entry updated.', 'sureforms' );
		}

		return $messages;
	}

	/**
	 * Remove publishing actions from single entries page.
	 *
	 * @return void
	 * @since  0.0.1
	 */
	public function remove_entries_publishing_actions() {
		global $typenow;
		if ( 'sureforms_entry' === $typenow ) { ?>
			<style>
				.misc-pub-post-status {
					display: none !important;
				}
				.misc-pub-visibility {
					display: none !important;
				}
			</style>
			<?php
		}
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
		if ( 'sureforms_entry' === $post->post_type ) {
			$actions['edit'] = '<a href="' . get_edit_post_link( $post->ID ) . '">View</a>';
		}
		if ( 'sureforms_form' === $post->post_type ) {
			$actions['export'] = '<a href="#" onclick="exportForm(' . $post->ID . ')">Export</a>';
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
		$bulk_actions['export'] = __( 'Export', 'sureforms' );
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

		if ( SRFM_ENTRIES_POST_TYPE === $post_type && 'bottom' === $which ) {

			$counts = (array) wp_count_posts( SRFM_ENTRIES_POST_TYPE );
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

		if ( 'edit-' . SRFM_FORMS_POST_TYPE === $screen_id || 'edit-' . SRFM_ENTRIES_POST_TYPE === $screen_id ) {
			?>
		<style>
			.srfm-page-header {
				@media screen and ( max-width: 600px ) {
					padding-top: 46px;
				}
			}
		</style>
		<div id="srfm-page-header" class="srfm-page-header"></div>
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

		$metas = apply_filters(
			'srfm_register_post_meta',
			[
				// General tab metas.
				'_srfm_show_labels'               => 'boolean',
				'_srfm_show_asterisk'             => 'boolean',
				'_srfm_single_page_form_title'    => 'boolean',
				'_srfm_submit_button_text'        => 'string',
				'_srfm_instant_form'              => 'boolean',
				'_srfm_is_inline_button'          => 'boolean',

				// Styling tab metas.
				// Form Container.
				'_srfm_form_container_width'      => 'integer',
				'_srfm_color1'                    => 'string',
				'_srfm_bg_type'                   => 'string',
				'_srfm_bg_image'                  => 'string',
				'_srfm_cover_image'               => 'string',
				'_srfm_bg_color'                  => 'string',
				'_srfm_fontsize'                  => 'integer',
				'_srfm_label_color'               => 'string',
				'_srfm_help_color'                => 'string',
				// Input Fields.
				'_srfm_input_text_color'          => 'string',
				'_srfm_input_placeholder_color'   => 'string',
				'_srfm_input_bg_color'            => 'string',
				'_srfm_input_border_color'        => 'string',
				'_srfm_input_shadow_color'        => 'string',
				'_srfm_input_border_width'        => 'integer',
				'_srfm_input_border_radius'       => 'integer',
				// Error.
				'_srfm_field_error_color'         => 'string',
				'_srfm_field_error_surface_color' => 'string',
				'_srfm_field_error_shadow_color'  => 'string',
				'_srfm_field_error_bg_color'      => 'string',
				// Submit Button.
				'_srfm_button_text_color'         => 'string',
				'_srfm_btn_bg_type'               => 'string',
				'_srfm_button_bg_color'           => 'string',
				'_srfm_button_border_color'       => 'string',
				'_srfm_button_border_width'       => 'integer',
				'_srfm_submit_width_backend'      => 'string',
				'_srfm_button_border_radius'      => 'integer',
				'_srfm_submit_alignment'          => 'string',
				'_srfm_submit_alignment_backend'  => 'string',
				'_srfm_submit_width'              => 'string',
				'_srfm_inherit_theme_button'      => 'boolean',
				// Additional Classes.
				'_srfm_additional_classes'        => 'string',

				// Advanced tab metas.
				// Success Message.
				'_srfm_submit_type'               => 'string',
				'_srfm_thankyou_message_title'    => 'string',
				'_srfm_thankyou_message'          => 'string',
				'_srfm_submit_url'                => 'string',
				// Security.
				'_srfm_captcha_security_type'     => 'string',
				'_srfm_form_recaptcha'            => 'string',
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
				'auth_callback'     => function() {
					return current_user_can( 'edit_posts' );
				},
				'sanitize_callback' => function( $meta_value ) {
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
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				]
			);
		}

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
						'name'           => 'Admin Notification Email',
						'email_to'       => '{admin_email}',
						'email_reply_to' => '{admin_email}',
						'email_cc'       => '{admin_email}',
						'email_bcc'      => '{admin_email}',
						'subject'        => 'New Form Submission',
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
				'single'        => true,
				'type'          => 'array',
				'auth_callback' => '__return_true',
				'show_in_rest'  => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id'                => [
									'type' => 'integer',
								],
								'confirmation_type' => [
									'type' => 'string',
								],
								'page_url'          => [
									'type' => 'string',
								],
								'custom_url'        => [
									'type' => 'string',
								],
								'message'           => [
									'type' => 'string',
								],
								'submission_action' => [
									'type' => 'string',
								],
							],
						],
					],
				],
				'default'       => [
					[
						'id'                => 1,
						'confirmation_type' => 'same page',
						'page_url'          => '',
						'custom_url'        => '',
						'message'           => '<p>Form submitted successfully!</p>',
						'submission_action' => 'hide form',
					],
				],
			]
		);

		// Sureforms entry metas.
		register_post_meta(
			'sureforms_entry',
			'_srfm_submission_info',
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
								'user_ip'      => [
									'type' => 'string',
								],
								'browser_name' => [
									'type' => 'string',
								],
								'device_name'  => [
									'type' => 'string',
								],
							],
						],
					],
				],
			]
		);

		// store form id in entry.
		register_post_meta(
			'sureforms_entry',
			'_srfm_entry_form_id',
			[
				'single'        => true,
				'type'          => 'integer',
				'auth_callback' => '__return_true',
				'show_in_rest'  => true,
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
	 * Sureforms entries meta box callback.
	 *
	 * @param \WP_Post $post Template.
	 * @return void
	 * @since 0.0.1
	 */
	public function sureforms_meta_box_callback( \WP_Post $post ) {
		$meta_data = get_post_meta( $post->ID, 'srfm_entry_meta', true );
		if ( ! is_array( $meta_data ) ) {
			return;
		}
		$excluded_fields = [ 'srfm-honeypot-field', 'g-recaptcha-response', 'srfm-sender-email-field' ];

		?>
		<table class="widefat striped">
			<tbody>
				<tr><th><b><?php esc_html_e( 'Fields', 'sureforms' ); ?></b></th><th><b><?php esc_html_e( 'Values', 'sureforms' ); ?></b></th></tr>
			<?php
			foreach ( $meta_data as $field_name => $value ) :
				if ( in_array( $field_name, $excluded_fields, true ) ) {
					continue;
				}

				if ( false === str_contains( $field_name, '-lbl-' ) ) {
					continue;
				}

				$label = explode( '-lbl-', $field_name )[1];
				// Getting the encrypted label. we are removing the block slug here.
				$label = explode( '-', $label )[0];

				?>
				<tr class="">
				<?php if ( strpos( $field_name, 'srfm-upload' ) !== false ) : ?>
						<td><b><?php echo $label ? esc_html( Helper::decrypt( $label ) ) : ''; ?><b></td>
						<?php if ( ! $value ) : ?>
							<td><?php echo ''; ?></td>
						<?php elseif ( in_array( pathinfo( $value, PATHINFO_EXTENSION ), [ 'gif', 'png', 'bmp', 'jpg', 'jpeg', 'svg' ], true ) ) : ?>
							<td><a target="_blank" href="<?php echo esc_url( $value ); ?>"><img style="max-width:100px; height:auto;" src="<?php echo esc_url( $value ); ?>" alt="img" /></a></td>
						<?php else : ?>
							<td><a target="_blank" href="<?php echo esc_url( $value ); ?>"><?php echo esc_html__( 'View', 'sureforms' ); ?></a></td>
						<?php endif; ?>
					<?php elseif ( strpos( $field_name, 'srfm-url' ) !== false ) : ?>
						<td><b><?php echo $label ? esc_html( Helper::decrypt( $label ) ) : ''; ?><b></td>
						<?php if ( ! $value ) : ?>
							<td><?php echo ''; ?></td>
						<?php else : ?>
							<?php
							if (
									substr( $value, 0, 7 ) !== 'http://' &&
									substr( $value, 0, 8 ) !== 'https://'
								) {
								$value = 'https://' . $value;
							}
							?>
							<td><a target="_blank" href="<?php echo esc_url( $value ); ?>"><?php echo esc_url( $value ); ?></a></td>
						<?php endif; ?>
					<?php else : ?>
						<td><b><?php echo $label ? esc_html( Helper::decrypt( $label ) ) : ''; ?><b></td>
						<td><?php echo wp_kses_post( $value ); ?></td>
					<?php endif; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
			<?php
	}


	/**
	 * Add Sureforms entries meta box.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function entries_meta_box() {
		add_meta_box(
			'sureform_entry_meta',
			'Form Data',
			[ $this, 'sureforms_meta_box_callback' ],
			'sureforms_entry',
			'normal',
			'high'
		);
		add_meta_box(
			'sureform_form_name_meta',
			'Submission Info',
			[ $this, 'sureforms_form_name_meta_callback' ],
			'sureforms_entry',
			'side',
			'low'
		);
	}

	/**
	 * Sureforms box Form Name meta box callback.
	 *
	 * @param \WP_Post $post Template.
	 * @return void
	 * @since 0.0.1
	 */
	public function sureforms_form_name_meta_callback( \WP_Post $post ) {
		$post_id  = $post->ID;
		$taxonomy = 'sureforms_tax';
		$terms    = wp_get_post_terms( $post_id, $taxonomy );
		if ( is_array( $terms ) && count( $terms ) > 0 ) {
			$form_id         = intval( $terms[0]->slug );
			$form_name       = ! empty( get_the_title( $form_id ) ) ? get_the_title( $form_id ) : 'SureForms Form';
			$submission_info = get_post_meta( $post_id, '_srfm_submission_info', true );
			if ( is_array( $submission_info ) && count( $submission_info ) > 0 ) {
				$user_ip       = $submission_info[0]['user_ip'] ? $submission_info[0]['user_ip'] : '';
				$browser_name  = $submission_info[0]['browser_name'] ? $submission_info[0]['browser_name'] : '';
				$device_name   = $submission_info[0]['device_name'] ? $submission_info[0]['device_name'] : '';
				$entry_form_id = Helper::get_string_value( get_post_meta( $post_id, '_srfm_entry_form_id', true ) );
			} else {
				$user_ip       = '';
				$browser_name  = '';
				$device_name   = '';
				$entry_form_id = '';
			}
			?>
			<table style="border-collapse: separate; border-spacing: 5px 5px;">
			<tr style="margin-bottom: 10px;">
				<td><b><?php echo esc_html( __( 'Form Name:', 'sureforms' ) ); ?></b></td>
				<td><?php echo esc_html( $form_name ); ?></td>
			</tr>
			<tr style="margin-bottom: 10px;">
				<td><b><?php echo esc_html( __( 'Form ID:', 'sureforms' ) ); ?></b></td>
				<td><?php echo esc_html( $entry_form_id ); ?></td>
			</tr>
			<tr style="margin-bottom: 10px;">
				<td><b><?php echo esc_html( __( 'User IP:', 'sureforms' ) ); ?></b></td>
				<td><a target="_blank" rel="noopener" href="https://ipinfo.io/<?php echo esc_html( $user_ip ); ?>"><?php echo esc_html( $user_ip ); ?></a></td>
			</tr>
			<tr style="margin-bottom: 10px;">
				<td><b><?php echo esc_html( __( 'Browser:', 'sureforms' ) ); ?></b></td>
				<td><?php echo esc_html( $browser_name ); ?></td>
			</tr>
			<tr style="margin-bottom: 10px;">
				<td><b><?php echo esc_html( __( 'Device:', 'sureforms' ) ); ?></b></td>
				<td><?php echo esc_html( $device_name ); ?></td>
			</tr>
			</table>
			<?php
		} else {
			?>
			<p><?php echo esc_html__( 'SureForms Form', 'sureforms' ); ?></p>
			<?php
		}
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

		if ( $post ) {
			$content = Generate_Form_Markup::get_form_markup( $id, ! filter_var( $atts['show_title'], FILTER_VALIDATE_BOOLEAN ) );
			return $content;
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
		$columns = [
			'cb'        => $columns['cb'],
			'title'     => $columns['title'],
			'sureforms' => __( 'Shortcode', 'sureforms' ),
			'entries'   => __( 'Entries', 'sureforms' ),
			'author'    => $columns['author'],
			'date'      => $columns['date'],
		];
		return $columns;
	}

	/**
	 * Populate custom column with data.
	 *
	 * @param string  $column Attributes.
	 * @param integer $post_id Attributes.
	 * @return void
	 * @since 0.0.1
	 */
	public function custom_form_column_data( $column, $post_id ) {
		$post_id_formatted = strval( $post_id );
		if ( 'sureforms' === $column ) {
			ob_start();
			?>
			<div class="srfm-shortcode-container">
				<input id="srfm-shortcode-input-<?php echo esc_attr( strval( $post_id ) ); ?>" class="srfm-shortcode-input" type="text" readonly value="[sureforms id='<?php echo esc_attr( $post_id_formatted ); ?>']" />
				<button type="button" class="components-button components-clipboard-button has-icon srfm-shortcode" onclick="handleFormShortcode(this)">
					<span id="srfm-copy-icon" class="dashicon dashicons dashicons-admin-page"></span>
				</button>
			</div>
			<?php
			ob_end_flush();
		}
		if ( 'entries' === $column ) {
			$entries_url = admin_url( 'edit.php?post_status=all&post_type=' . SRFM_ENTRIES_POST_TYPE . '&sureforms_tax=' . $post_id_formatted . '&filter_action=Filter&paged=1' );

			$taxonomy = 'sureforms_tax';

			$args = [
				'post_type' => SRFM_ENTRIES_POST_TYPE,
				'tax_query' // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query. -- We require tax_query for this function to work.
				=> [
					[
						'taxonomy' => $taxonomy,
						'field'    => 'slug',
						'terms'    => $post_id_formatted,
					],
				],
			];

			$key   = 'sureforms_entries_count_' . $post_id_formatted;
			$query = wp_cache_get( $key );

			if ( ! $query ) {
				$query = new WP_Query( $args );
				wp_cache_set( $key, $query, '', 3600 );
			}

			if ( $query instanceof WP_Query ) {
				$post_count = $query->post_count;

				$post_count = strval( $post_count );

				ob_start();
				?>
					<p class="srfm-entries-number"><a href="<?php echo esc_url( $entries_url ); ?>"><?php echo esc_html( $post_count ); ?></a></p>
				<?php
				ob_end_flush();
			}
		}
	}

	/**
	 * Add custom column header.
	 *
	 * @param array<mixed> $columns Attributes.
	 * @return array<mixed> $columns Post Content.
	 * @since 0.0.1
	 */
	public function custom_entry_columns( $columns ) {
		$columns = [
			'cb'        => $columns['cb'],
			'title'     => __( 'First Field', 'sureforms' ),
			'form_name' => __( 'Form Name', 'sureforms' ),
			'entry_id'  => __( 'ID', 'sureforms' ),
			'date'      => __( 'Submitted On', 'sureforms' ),
		];
		return $columns;
	}

	/**
	 * Populate custom column with data.
	 *
	 * @param string  $column Attributes.
	 * @param integer $post_id Attributes.
	 * @return void
	 * @since 0.0.1
	 */
	public function custom_entry_column_data( $column, $post_id ) {
		if ( 'entry_id' === $column ) {
			$entry_id = strval( $post_id );
			echo '<p>#' . esc_html( $entry_id ) . '</p>';
		}
		if ( 'form_name' === $column ) {
			$taxonomy = 'sureforms_tax';
			$terms    = wp_get_post_terms( $post_id, $taxonomy );

			if ( is_array( $terms ) && count( $terms ) > 0 ) {
				$form_id   = intval( $terms[0]->slug );
				$form_name = ! empty( get_the_title( $form_id ) ) ? get_the_title( $form_id ) : 'SureForms Form';
				echo '<p>' . esc_html( $form_name . ' #' . $form_id ) . '</p>';
			} else {
				?>
				<p><?php echo esc_html__( 'SureForms Form', 'sureforms' ); ?></p>
				<?php
			}
		}
	}

	/**
	 * Add SureForms taxonomy filter.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function add_tax_filter() {
		$screen = get_current_screen();

		if ( ! is_null( $screen ) && 'edit-sureforms_entry' === $screen->id ) {
			$forms = get_posts(
				[
					'post_type'      => SRFM_FORMS_POST_TYPE,
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
				]
			);

			if ( ! empty( $forms ) ) {
				$selected = isset( $_GET['sureforms_tax'] ) ? sanitize_key( wp_unslash( $_GET['sureforms_tax'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce Verification is not needed in this case. We are not getting the nonce value.
				echo '<select name="sureforms_tax" id="srfm-tax-filter">';
				echo '<option value="">' . esc_html__( ' All Form Entries', 'sureforms' ) . '</option>';

				foreach ( $forms as $form ) {
					$selected_attr = selected( $selected, $form->ID, false );
					echo '<option value="' . esc_attr( strval( $form->ID ) ) . '" ' . esc_attr( $selected_attr ) . '>' . esc_html( $form->post_title ) . '</option>';
				}

				echo '</select>';

			}
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
						<input type="submit" name="import-form-submit" id="import-form-submit" class="srfm-import-button" value="Import Now" disabled>
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

		$is_instant_form = get_post_meta( $form_id, '_srfm_instant_form', true );

		if ( $is_instant_form ) {
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

}
