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
	}

	/**
	 * Form Template filter.
	 *
	 * @param string $template Template.
	 * @return string Template.
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
	 * @param array $atts Attributes.
	 * @return $content Post Content.
	 */
	public function sureforms_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => '',
				'title' => '',
			),
			$atts
		);

		$id    = $atts['id'];
		$title = $atts['title'];

		$post = get_post( $id );

		if ( $post ) {
			$content = $post->post_content;
			return $content;
		}

	}

	/**
	 * Add custom column header.
	 *
	 * @param array $columns Attributes.
	 * @return array $columns Post Content.
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
	 */
	public function custom_sureforms_form_column_data( $column, $post_id ) {
		$post_title = esc_html( get_the_title( $post_id ) );
		if ( 'sureforms' === $column ) {
			ob_start();
			?>
			<input type="text" readonly value="[sureforms id='<?php echo esc_html( $post_id ); ?>' title='<?php echo esc_html( $post_title ); ?>']" onClick="this.select();" style="cursor: pointer; width: -webkit-fill-available; border: none; background: transparent; font-family: Consolas,Monaco,monospace;
			" />
			<?php
			ob_end_flush();
		}
	}
}
