<?php
/**
 * Post Types Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc;

use WP_REST_Response;
use WP_Screen;
use WP_Query;
use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Generate_Form_Markup;

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
		add_filter( 'manage_sureforms_form_posts_columns', [ $this, 'custom_sureforms_form_columns' ] );
		add_action( 'manage_sureforms_form_posts_custom_column', [ $this, 'custom_sureforms_form_column_data' ], 10, 2 );
		add_filter( 'manage_sureforms_entry_posts_columns', [ $this, 'custom_sureforms_entry_columns' ] );
		add_action( 'manage_sureforms_entry_posts_custom_column', [ $this, 'custom_sureforms_entry_column_data' ], 10, 2 );
		add_shortcode( 'sureforms', [ $this, 'sureforms_shortcode' ] );
		add_action( 'add_meta_boxes', [ $this, 'sureform_entries_meta_box' ] );
		add_action( 'restrict_manage_posts', [ $this, 'add_sureforms_tax_filter' ] );
		add_action( 'manage_posts_extra_tablenav', [ $this, 'maybe_render_blank_form_state' ] );
		add_action( 'in_admin_header', [ $this, 'embed_page_header' ] );
		add_action( 'admin_head', [ $this, 'sureforms_remove_entries_publishing_actions' ] );
		add_filter( 'post_row_actions', [ $this, 'sureforms_modify_entries_list_row_actions' ], 10, 2 );
		add_filter( 'default_title', [ $this, 'sureforms_default_cpt_title_filter' ], 10, 2 );
		add_filter( 'post_updated_messages', [ $this, 'sureforms_entries_updated_message' ] );
		add_filter( 'bulk_actions-edit-sureforms_form', [ $this, 'register_modify_bulk_actions' ] );
		add_action( 'admin_notices', [ $this, 'import_form_popup' ] );
	}

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

		echo '<img src="' . esc_url( SUREFORMS_URL . '/images/' . $image . '.svg' ) . '">';

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

		if ( SUREFORMS_FORMS_POST_TYPE === $post_type ) {
			$new_form_url = admin_url( 'post-new.php?post_type=' . SUREFORMS_FORMS_POST_TYPE );

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

		if ( SUREFORMS_ENTRIES_POST_TYPE === $post_type ) {

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
		$form_labels = array(
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
		);
		register_post_type(
			SUREFORMS_FORMS_POST_TYPE,
			array(
				'labels'            => $form_labels,
				'rewrite'           => array( 'slug' => 'form' ),
				'public'            => true,
				'show_in_rest'      => true,
				'has_archive'       => false,
				'show_ui'           => true,
				'supports'          => array( 'title', 'author', 'editor', 'custom-fields' ),
				'show_in_menu'      => 'sureforms_menu',
				'show_in_nav_menus' => true,
			)
		);

		$result_labels = array(
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
		);
		register_post_type(
			SUREFORMS_ENTRIES_POST_TYPE,
			array(
				'labels'              => $result_labels,
				'supports'            => array( 'title' ),
				'public'              => false,
				'show_in_rest'        => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => true,
				'capability_type'     => 'post',
				'capabilities'        => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'        => true,
				'show_ui'             => true,
				'show_in_menu'        => 'sureforms_menu',
			)
		);
		register_taxonomy(
			'sureforms_tax',
			'sureforms_entry',
			array(
				'label'             => __( 'Form ID', 'sureforms' ),
				'hierarchical'      => true,
				'capabilities'      => array(
					'assign_terms' => 'god',
					'edit_terms'   => 'god',
					'manage_terms' => 'god',
				),
				'show_in_rest'      => true,
				'show_admin_column' => false,
				'show_in_nav_menus' => false,
				'show_ui'           => false,
			)
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
	 * Modify post update message for Entry post type.
	 *
	 * @param string $messages Post type.
	 * @return string
	 * @since  0.0.1
	 */
	public function sureforms_entries_updated_message( $messages ) {
		global $post_ID;

		$post_type = get_post_type( $post_ID );

		if ( SUREFORMS_ENTRIES_POST_TYPE === $post_type ) {
			$messages['post'][1] = __( 'Entry updated.', 'sureforms' );
		}

		return $messages;
	}

	/**
	 * Default Form CPT Form title.
	 *
	 * @param string   $title Post title.
	 * @param \WP_Post $post The current WP_Post object.
	 * @return string
	 * @since  0.0.1
	 */
	public function sureforms_default_cpt_title_filter( $title, $post ) {
		$post_type = $post->post_type;

		if ( SUREFORMS_FORMS_POST_TYPE === $post_type && empty( $title ) ) {
			$title = __( 'Untitled Form', 'sureforms' );
		}

		return $title;
	}

	/**
	 * Remove publishing actions from single entries page.
	 *
	 * @return void
	 * @since  0.0.1
	 */
	public function sureforms_remove_entries_publishing_actions() {
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
	 * @param array<Mixed> $actions An array of row action links.
	 * @param \WP_Post     $post  The current WP_Post object.
	 *
	 * @return array<Mixed> $actions Modified row action links.
	 * @since  0.0.1
	 */
	public function sureforms_modify_entries_list_row_actions( $actions, $post ) {
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
	 * @param array<Mixed> $bulk_actions An array of bulk action links.
	 * @since x.x.x
	 * @return array<Mixed> $bulk_actions Modified action links.
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

		if ( SUREFORMS_FORMS_POST_TYPE === $post_type && 'bottom' === $which ) {

			$counts = (array) wp_count_posts( SUREFORMS_FORMS_POST_TYPE );
			unset( $counts['auto-draft'] );
			$count = array_sum( $counts );

			if ( 0 < $count ) {
				return;
			}

			$this->sureforms_render_blank_state( $post_type );

			$this->get_blank_state_styles();

		}

		if ( SUREFORMS_ENTRIES_POST_TYPE === $post_type && 'bottom' === $which ) {

			$counts = (array) wp_count_posts( SUREFORMS_ENTRIES_POST_TYPE );
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

		if ( 'edit-' . SUREFORMS_FORMS_POST_TYPE === $screen_id || 'edit-' . SUREFORMS_ENTRIES_POST_TYPE === $screen_id ) {
			?>
		<div id="srfm-page-header"></div>
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
		$metas = array(
			'_srfm_color1'                            => 'string',
			'_srfm_textcolor1'                        => 'string',
			'_srfm_color2'                            => 'string',
			'_srfm_fontsize'                          => 'integer',
			'_srfm_bg'                                => 'string',
			'_srfm_bg_id'                             => 'integer',
			'_srfm_thankyou_message'                  => 'string',
			'_srfm_email'                             => 'string',
			'_srfm_submit_type'                       => 'string',
			'_srfm_submit_url'                        => 'string',
			'_srfm_sender_notification'               => 'string',
			'_srfm_form_recaptcha'                    => 'string',
			'_srfm_submit_alignment'                  => 'string',
			'_srfm_submit_width'                      => 'string',
			'_srfm_submit_styling_inherit_from_theme' => 'boolean',
			'_srfm_form_class_name'                   => 'string',
			'_srfm_form_styling'                      => 'string',
			'_srfm_form_container_width'              => 'integer',
			'_srfm_thankyou_message_title'            => 'string',
			'_srfm_submit_button_text'                => 'string',
			'_srfm_additional_classes'                => 'string',
			'_srfm_hide_title_post_specific'          => 'boolean',
			'_srfm_page_form_title'                   => 'boolean',
			'_srfm_single_page_form_title'            => 'boolean',
			'_srfm_submit_width_backend'              => 'string',
			'_srfm_submit_alignment_backend'          => 'string',
			'_srfm_is_page_break'                     => 'boolean',
			'_srfm_first_page_label'                  => 'string',
			'_srfm_page_break_progress_indicator'     => 'string',
			'_srfm_page_break_toggle_label'           => 'boolean',
		);
		foreach ( $metas as $meta => $type ) {
			register_meta(
				'post',
				$meta,
				array(
					'object_subtype'    => SUREFORMS_FORMS_POST_TYPE,
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => $type,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
		register_post_meta(
			'sureforms_form',
			'_srfm_email_notification',
			array(
				'single'        => true,
				'type'          => 'array',
				'auth_callback' => '__return_true',
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'id'            => array(
									'type' => 'integer',
								),
								'status'        => array(
									'type' => 'boolean',
								),
								'is_raw_format' => array(
									'type' => 'boolean',
								),
								'name'          => array(
									'type' => 'string',
								),
								'email_to'      => array(
									'type' => 'string',
								),
								'subject'       => array(
									'type' => 'string',
								),
								'email_body'    => array(
									'type' => 'string',
								),
							),
						),
					),
				),
				'default'       => array(
					array(
						'id'            => 1,
						'status'        => false,
						'is_raw_format' => false,
						'name'          => 'Admin Notification Email',
						'email_to'      => '{admin_email}',
						'subject'       => 'New Form Submission',
						'email_body'    => '',
					),
				),
			)
		);
	}

	/**
	 * Sureforms entries meta box callback.
	 *
	 * @param \WP_Post $post Template.
	 * @return void
	 * @since 0.0.1
	 */
	public function sureforms_meta_box_callback( \WP_Post $post ) {
		$meta_data = get_post_meta( $post->ID, 'sureforms_entry_meta', true );
		if ( ! is_array( $meta_data ) ) {
			return;
		}
		$excluded_fields = [ 'srfm-honeypot-field', 'g-recaptcha-response', 'srfm-sender-email-field' ];
		?>
		<table class="widefat striped">
			<tbody>
				<tr><th><b>FIELD</b></th><th><b>VALUE</b></th></tr>
			<?php
			foreach ( $meta_data as $field_name => $value ) :
				if ( in_array( $field_name, $excluded_fields, true ) || false !== strpos( $field_name, 'sf-radio' ) ) {
					continue;
				}
				?>
				<tr class="">
				<?php if ( strpos( $field_name, 'SF-upload' ) !== false ) : ?>
						<td><b><?php echo esc_html( explode( 'SF-upload', $field_name )[0] ); ?></b></td>
						<?php if ( ! $value ) : ?>
							<td><?php echo ''; ?></td>
						<?php elseif ( in_array( pathinfo( $value, PATHINFO_EXTENSION ), array( 'gif', 'png', 'bmp', 'jpg', 'jpeg', 'svg' ), true ) ) : ?>
							<td><a target="_blank" href="<?php echo esc_url( $value ); ?>"><img style="max-width:100px; height:auto;" src="<?php echo esc_url( $value ); ?>" alt="img" /></a></td>
						<?php else : ?>
							<td><a target="_blank" href="<?php echo esc_url( $value ); ?>"><?php echo esc_html__( 'View', 'sureforms' ); ?></a></td>
						<?php endif; ?>
					<?php elseif ( strpos( $field_name, 'SF-url' ) !== false ) : ?>
						<td><b><?php echo esc_html( explode( 'SF-url', $field_name )[0] ); ?></b></td>
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
						<td><b><?php echo esc_html( explode( 'SF-divider', $field_name )[0] ); ?></b></td>
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
	public function sureform_entries_meta_box() {
		add_meta_box(
			'sureform_entry_meta',
			'Form Data',
			array( $this, 'sureforms_meta_box_callback' ),
			'sureforms_entry',
			'normal',
			'high'
		);
		add_meta_box(
			'sureform_form_name_meta',
			'Form Name',
			array( $this, 'sureforms_form_name_meta_callback' ),
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
			$form_id   = intval( $terms[0]->slug );
			$form_name = ! empty( get_the_title( $form_id ) ) ? get_the_title( $form_id ) : 'SureForms Form';
			?>
		<p><?php echo esc_html( $form_name ); ?></p>
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
	public function sureforms_shortcode( array $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => '',
			),
			$atts
		);

		$id   = intval( $atts['id'] );
		$post = get_post( $id );

		if ( $post ) {
			$content = Generate_Form_Markup::get_form_markup( $id );
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
	public function custom_sureforms_form_columns( $columns ) {
		$columns = array(
			'cb'        => $columns['cb'],
			'title'     => $columns['title'],
			'sureforms' => __( 'Shortcode', 'sureforms' ),
			'entries'   => __( 'Entries', 'sureforms' ),
			'author'    => $columns['author'],
			'date'      => $columns['date'],
		);
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
	public function custom_sureforms_form_column_data( $column, $post_id ) {
		$post_id_formatted = strval( $post_id );
		if ( 'sureforms' === $column ) {
			ob_start();
			?>
			<div class="srfm-shortcode-container">
				<button type="button" class="components-button components-clipboard-button has-icon srfm-shortcode" onclick="handleFormShortcode(this)">
					<span id="srfm-copy-icon" class="dashicon dashicons dashicons-admin-page"></span>
				</button>
				<input id="srfm-shortcode-input-<?php echo esc_attr( strval( $post_id ) ); ?>" class="srfm-shortcode-input" type="text" readonly value="[sureforms id='<?php echo esc_html( $post_id_formatted ); ?>']" />
			<div>
			<?php
			ob_end_flush();
		}
		if ( 'entries' === $column ) {
			$entries_url = admin_url( 'edit.php?post_status=all&post_type=' . SUREFORMS_ENTRIES_POST_TYPE . '&sureforms_tax=' . $post_id_formatted . '&filter_action=Filter&paged=1' );

			$taxonomy = 'sureforms_tax';

			$args = array(
				'post_type' => SUREFORMS_ENTRIES_POST_TYPE,
				'tax_query' => array(
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'slug',
						'terms'    => $post_id_formatted,
					),
				),
			);

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
					<p class="srfm-entries-number"><a href="<?php echo esc_url( $entries_url ); ?>" target="_blank"><?php echo esc_html( $post_count ); ?></a></p>
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
	public function custom_sureforms_entry_columns( $columns ) {
		$columns = array(
			'cb'        => $columns['cb'],
			'title'     => __( 'First Field', 'sureforms' ),
			'form_name' => __( 'Form Name', 'sureforms' ),
			'entry_id'  => __( 'ID', 'sureforms' ),
			'date'      => __( 'Submitted On', 'sureforms' ),
		);
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
	public function custom_sureforms_entry_column_data( $column, $post_id ) {
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
				echo '<p>' . esc_html( $form_name ) . '</p>';
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
	public function add_sureforms_tax_filter() {
		$screen = get_current_screen();

		if ( ! is_null( $screen ) && 'edit-sureforms_entry' === $screen->id ) {
			$forms = get_posts(
				array(
					'post_type'      => SUREFORMS_FORMS_POST_TYPE,
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);

			if ( ! empty( $forms ) ) {
				// Nonce Verification is not needed in this case.
				$selected = isset( $_GET['sureforms_tax'] ) ? $_GET['sureforms_tax'] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				echo '<select name="sureforms_tax" id="srfm-tax-filter">';
				echo '<option value="">All Form Entries</option>';

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
	 * @since x.x.x
	 * @return void
	 */
	public function import_form_popup() {
		$screen = get_current_screen();
		$id     = $screen ? $screen->id : '';
		if ( 'edit-sureforms_form' === $id ) {
			?>
			<div class="srfm-import-plugin-wrap">
				<div class="srfm-import-wrap">
					<p class="srfm-import-help"><?php echo esc_html__( 'Select the SureForms Forms export file(.json) you would like to import.', 'sureforms' ); ?></p>
					<form method="post" enctype="multipart/form-data" class="srfm-import-form">
						<?php wp_nonce_field( 'srfm_import_nonce', '_wpnonce' ); ?>
						<input type="file" id="srfm-import-file" onchange="handleFileChange(event)" name="import form" accept=".json">
						<input type="submit" name="import-form-submit" id="import-form-submit" class="srfm-import-button" value="Import Now" disabled>
					</form>
					<p id="srfm-import-error"><?php echo esc_html__( 'There is some error in json file, please export the SureForms Forms again.', 'sureforms' ); ?></p>
				</div>
			</div>
			<?php
		}
	}

}
