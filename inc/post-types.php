<?php
/**
 * Post Types Class file.
 *
 * @package sureforms.
 * @since X.X.X
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;
/**
 * Post Types Main Class.
 *
 * @since X.X.X
 */
class Post_Types {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  X.X.X
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_filter( 'template_include', [ $this, 'page_template' ], PHP_INT_MAX );
		add_filter( 'manage_sureforms_form_posts_columns', [ $this, 'custom_sureforms_form_columns' ] );
		add_action( 'manage_sureforms_form_posts_custom_column', [ $this, 'custom_sureforms_form_column_data' ], 10, 2 );
		add_shortcode( 'sureforms', [ $this, 'sureforms_shortcode' ] );
		add_action( 'add_meta_boxes', [ $this, 'sureform_entries_meta_box' ] );

	}

	/**
	 * Registers the forms and submissions post types.
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function register_post_types() {
		$form_labels = array(
			'name'               => _x( 'Forms', 'post type general name', 'sureforms' ),
			'singular_name'      => _x( 'Form', 'post type singular name', 'sureforms' ),
			'menu_name'          => _x( 'Forms', 'admin menu', 'sureforms' ),
			'name_admin_bar'     => _x( 'Form', 'add new on admin bar', 'sureforms' ),
			'add_new'            => _x( 'Add New', 'form', 'sureforms' ),
			'add_new_item'       => __( 'Add New Form', 'sureforms' ),
			'new_item'           => __( 'New Form', 'sureforms' ),
			'edit_item'          => __( 'Edit Form', 'sureforms' ),
			'view_item'          => __( 'View Form', 'sureforms' ),
			'all_items'          => __( 'Forms', 'sureforms' ),
			'search_items'       => __( 'Search Forms', 'sureforms' ),
			'parent_item_colon'  => __( 'Parent Forms:', 'sureforms' ),
			'not_found'          => __( 'No forms found.', 'sureforms' ),
			'not_found_in_trash' => __( 'No forms found in Trash.', 'sureforms' ),
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
				'template'          => [
					[
						'sureforms/form',
						[],
						[],
					],
				],
				'template_lock'     => 'all',
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
			'edit_item'          => __( 'Edit Entry', 'sureforms' ),
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
				'supports'            => array( 'title', 'author' ),
				'public'              => false,
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
				'show_admin_column' => true,
				'show_in_nav_menus' => false,
			)
		);
		register_post_status(
			'unread',
			array(
				'label'                     => _x( 'Unread', 'sureforms', 'sureforms' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				// Translators: %s is the number of unread items.
				'label_count'               => _n_noop( 'Unread (%s)', 'Unread (%s)', 'sureforms' ),
			)
		);
	}

	/**
	 * Sureforms entries meta box callback.
	 *
	 * @param \WP_Post $post Template.
	 * @return void
	 * @since X.X.X
	 */
	public function sureforms_meta_box_callback( \WP_Post $post ) {
		$meta_data = get_post_meta( $post->ID, 'sureforms_entry_meta', true );

		if ( ! is_array( $meta_data ) ) {
			return;
		}
		?>
		<table class="widefat striped">
			<tbody>
				<tr><th><b>FIELD</b></th><th><b>VALUE</b></th></tr>
				<?php foreach ( $meta_data as $field_name => $value ) : ?>
					<?php
					if ( strpos( $field_name, 'radio' ) !== false ) {
						continue;}
					?>
					<tr class="">
						<td><b><?php echo esc_html( explode( 'SF-divider', $field_name )[0] ); ?></b></td>
						<td><?php echo wp_kses_post( $value ); ?></td>
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
	 * @since X.X.X
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
	}


	/**
	 * Form Template filter.
	 *
	 * @param string $template Template.
	 * @return string Template.
	 * @since X.X.X
	 */
	public function page_template( $template ) {
		if ( is_singular( SUREFORMS_FORMS_POST_TYPE ) ) {
			$file_name = 'single-form.php';
			$template  = locate_template( $file_name ) ? locate_template( $file_name ) : SUREFORMS_DIR . '/templates/' . $file_name;
			$template  = apply_filters( 'sureforms_form_template', $template );
		}
		return $template;
	}

	/**
	 * Custom Shortcode.
	 *
	 * @param array<mixed> $atts Attributes.
	 * @return string|void $content Post Content.
	 * @since X.X.X
	 */
	public function sureforms_shortcode( array $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => '',
				'title' => '',
			),
			$atts
		);

		$id    = intval( $atts['id'] );
		$title = $atts['title'];

		$post = get_post( $id );

		if ( $post ) {
			$content = $post->post_content;
			$content = apply_filters( 'the_content', $content );
			return $content;
		}

		return '';
	}

	/**
	 * Add custom column header.
	 *
	 * @param array<mixed> $columns Attributes.
	 * @return array<mixed> $columns Post Content.
	 * @since X.X.X
	 */
	public function custom_sureforms_form_columns( $columns ) {
		$columns = array(
			'cb'        => $columns['cb'],
			'title'     => $columns['title'],
			'sureforms' => __( 'Shortcode', 'sureforms' ),
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
	 * @since X.X.X
	 */
	public function custom_sureforms_form_column_data( $column, $post_id ) {
		$post_title        = get_the_title( $post_id );
		$post_id_formatted = sprintf( '%d', $post_id );
		if ( 'sureforms' === $column ) {
			ob_start();
			?>
			<input type="text" readonly value="[sureforms id='<?php echo esc_html( $post_id_formatted ); ?>' title='<?php echo esc_html( $post_title ); ?>']" onClick="this.select();" style="cursor: pointer; width: -webkit-fill-available; border: none; background: transparent; font-family: Consolas,Monaco,monospace;
			" />
			<?php
			ob_end_flush();
		}
	}
}
