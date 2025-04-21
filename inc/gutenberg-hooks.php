<?php
/**
 * Gutenberg Hooks Manager Class.
 *
 * @package sureforms.
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Gutenberg hooks handler class.
 *
 * @since 0.0.1
 */
class Gutenberg_Hooks {
	use Get_Instance;
	/**
	 * Block patterns to register.
	 *
	 * @var array<mixed>
	 */
	protected $patterns = [];

	/**
	 * Class constructor.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function __construct() {
		// Setting Form default patterns.
		$this->patterns = [
			'blank-form',
			'contact-form',
			'newsletter-form',
			'support-form',
			'feedback-form',
			'event-rsvp-form',
			'subscription-form',
		];

		// Initializing hooks.
		add_action( 'enqueue_block_editor_assets', [ $this, 'form_editor_screen_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'block_editor_assets' ] );
		add_filter( 'block_categories_all', [ $this, 'register_block_categories' ], 10, 1 );
		add_action( 'init', [ $this, 'register_block_patterns' ], 9 );
		add_filter( 'allowed_block_types_all', [ $this, 'disable_forms_wrapper_block' ], 10, 2 );
		add_action( 'save_post_sureforms_form', [ $this, 'update_field_slug' ], 10, 2 );
		add_action( 'load-post.php', [ $this, 'maybe_migrate_form_stylings' ] );
	}

	/**
	 * Disable Sureforms_Form Block and allowed only sureforms block inside Sureform CPT editor.
	 *
	 * @param bool|array<string>       $allowed_block_types Array of block types.
	 * @param \WP_Block_Editor_Context $editor_context The current block editor context.
	 * @return array<mixed>|bool
	 * @since 0.0.1
	 */
	public function disable_forms_wrapper_block( $allowed_block_types, $editor_context ) {
		if ( ! empty( $editor_context->post->post_type ) && 'sureforms_form' === $editor_context->post->post_type ) {
			$allow_block_types = [
				'srfm/input',
				'srfm/email',
				'srfm/textarea',
				'srfm/number',
				'srfm/checkbox',
				'srfm/gdpr',
				'srfm/phone',
				'srfm/address',
				'srfm/dropdown',
				'srfm/multi-choice',
				'srfm/url',
				'srfm/separator',
				'srfm/icon',
				'srfm/image',
				'srfm/advanced-heading',
				'srfm/inline-button',
			];
			// Apply a filter to the $allow_block_types types array.
			return apply_filters( 'srfm_allowed_block_types', $allow_block_types, $editor_context );
		}

		// Return the default $allowed_block_types value.
		return $allowed_block_types;
	}

	/**
	 * Register our custom block category.
	 *
	 * @param array<mixed> $categories Array of categories.
	 * @return array<mixed>
	 * @since 0.0.1
	 */
	public function register_block_categories( $categories ) {
		$screen = get_current_screen();

		if ( $screen && SRFM_FORMS_POST_TYPE === $screen->post_type ) {
			$title = esc_html__( 'General Fields', 'sureforms' );
		} else {
			$title = esc_html__( 'SureForms', 'sureforms' );
		}

		$custom_categories = [
			[
				'slug'  => 'sureforms',
				'title' => $title,
			],
			[
				'slug'  => 'sureforms-pro',
				'title' => esc_html__( 'Advanced Fields', 'sureforms' ),
			],
		];

		return array_merge( $custom_categories, $categories );
	}

	/**
	 * Register our block patterns.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_block_patterns() {
		// Apply filters to the patterns.
		$this->patterns = apply_filters( 'srfm_block_patterns', $this->patterns );

		// Iterate over each block pattern.
		foreach ( $this->patterns as $block_pattern ) {
			// Attempt to register block pattern from the main directory.
			if ( ! $this->register_block_pattern_from_directory( $block_pattern, plugin_dir_path( SRFM_FILE ) . 'templates/forms/' ) ) {
				// If unsuccessful, attempt to register block pattern from the pro directory.
				if ( defined( 'SRFM_PRO_VER' ) && defined( 'SRFM_PRO_DIR' ) ) {
					$this->register_block_pattern_from_directory( $block_pattern, SRFM_PRO_DIR . 'templates/forms/' );
				}
			}
		}
	}

	/**
	 * Add Form Editor Scripts.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function form_editor_screen_assets() {
		$form_editor_script = '-formEditor';

		$screen     = get_current_screen();
		$post_types = [ SRFM_FORMS_POST_TYPE ];

		if ( is_null( $screen ) || ! in_array( $screen->post_type, $post_types, true ) ) {
			return;
		}

		$script_asset_path = SRFM_DIR . 'assets/build/formEditor.asset.php';
		$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: [
				'dependencies' => [],
				'version'      => SRFM_VER,
			];

		wp_enqueue_script( SRFM_SLUG . $form_editor_script, SRFM_URL . 'assets/build/formEditor.js', $script_info['dependencies'], $script_info['version'], true );
		wp_localize_script( SRFM_SLUG . $form_editor_script, 'scIcons', [ 'path' => SRFM_URL . 'assets/build/icon-assets' ] );

		// Enqueue the code editor for the Custom CSS Editor in SureForms.
		wp_enqueue_code_editor( [ 'type' => 'text/css' ] );
		wp_enqueue_script( 'wp-theme-plugin-editor' );
		wp_enqueue_style( 'wp-codemirror' );

		wp_localize_script(
			SRFM_SLUG . $form_editor_script,
			SRFM_SLUG . '_block_data',
			[
				'plugin_url'      => SRFM_URL,
				'admin_email'     => get_option( 'admin_email' ),
				'pro_plugin_name' => defined( 'SRFM_PRO_VER' ) && defined( 'SRFM_PRO_PRODUCT' ) ? SRFM_PRO_PRODUCT : 'free',
			]
		);

		Helper::register_script_translations( SRFM_SLUG . $form_editor_script );
	}

	/**
	 * Register all editor scripts.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function block_editor_assets() {
		$all_screen_blocks = '-blocks';
		$screen            = get_current_screen();

		$blocks_asset_path = SRFM_DIR . 'assets/build/blocks.asset.php';
		$blocks_info       = file_exists( $blocks_asset_path )
			? include $blocks_asset_path
			: [
				'dependencies' => [],
				'version'      => SRFM_VER,
			];
		wp_enqueue_script( SRFM_SLUG . $all_screen_blocks, SRFM_URL . 'assets/build/blocks.js', $blocks_info['dependencies'], SRFM_VER, true );

		Helper::register_script_translations( SRFM_SLUG . $all_screen_blocks );

		wp_localize_script(
			SRFM_SLUG . $all_screen_blocks,
			SRFM_SLUG . '_block_data',
			[
				'template_picker_url'               => admin_url( '/admin.php?page=add-new-form' ),
				'plugin_url'                        => SRFM_URL,
				'admin_email'                       => get_option( 'admin_email' ),
				'post_url'                          => admin_url( 'post.php' ),
				'current_screen'                    => $screen,
				'smart_tags_array'                  => Smart_Tags::smart_tag_list(),
				'smart_tags_array_email'            => Smart_Tags::email_smart_tag_list(),
				'srfm_form_markup_nonce'            => wp_create_nonce( 'srfm_form_markup' ),
				'get_form_markup_url'               => 'sureforms/v1/generate-form-markup',
				'is_pro_active'                     => defined( 'SRFM_PRO_VER' ),
				'srfm_default_dynamic_block_option' => get_option( 'srfm_default_dynamic_block_option', Helper::default_dynamic_block_option() ),
				'form_selector_nonce'               => current_user_can( 'edit_posts' ) ? wp_create_nonce( 'wp_rest' ) : '',
				'is_admin_user'                     => current_user_can( 'manage_options' ),
				'site_url'                          => wp_parse_url( esc_url( get_site_url() ), PHP_URL_HOST ),
				'is_suremails_active'               => is_plugin_active( 'suremails/suremails.php' ),
			]
		);

		// Localizing the field preview image links.
		wp_localize_script(
			SRFM_SLUG . $all_screen_blocks,
			SRFM_SLUG . '_fields_preview',
			apply_filters(
				'srfm_block_preview_images',
				[
					'input_preview'        => SRFM_URL . 'images/field-previews/input.svg',
					'email_preview'        => SRFM_URL . 'images/field-previews/email.svg',
					'url_preview'          => SRFM_URL . 'images/field-previews/url.svg',
					'textarea_preview'     => SRFM_URL . 'images/field-previews/textarea.svg',
					'multi_choice_preview' => SRFM_URL . 'images/field-previews/multi-choice.svg',
					'checkbox_preview'     => SRFM_URL . 'images/field-previews/checkbox.svg',
					'number_preview'       => SRFM_URL . 'images/field-previews/number.svg',
					'phone_preview'        => SRFM_URL . 'images/field-previews/phone.svg',
					'dropdown_preview'     => SRFM_URL . 'images/field-previews/dropdown.svg',
					'address_preview'      => SRFM_URL . 'images/field-previews/address.svg',
					'sureforms_preview'    => SRFM_URL . 'images/field-previews/sureforms.svg',
				]
			)
		);

		wp_localize_script(
			SRFM_SLUG . $all_screen_blocks,
			SRFM_SLUG . '_blocks_info',
			[
				'font_awesome_5_polyfill' => [],
				'collapse_panels'         => 'enabled',
				'is_site_editor'          => $screen ? $screen->id : null,
			]
		);
	}

	/**
	 * This function generates slug for sureforms blocks.
	 * Generates slug only if slug attribute of block is empty.
	 * Ensures that all sureforms blocks have unique slugs.
	 *
	 * @param int      $post_id current sureforms form post id.
	 * @param \WP_Post $post SureForms post object.
	 * @since 0.0.2
	 * @return void
	 */
	public function update_field_slug( $post_id, $post ) {
		$blocks = parse_blocks( $post->post_content );

		if ( empty( $blocks ) ) {
			return;
		}

		$updated = false;

		/**
		 * List of slugs already taken by processed blocks.
		 * used to maintain uniqueness of slugs.
		 */
		$slugs = [];

		[ $blocks, $slugs, $updated ] = Helper::process_blocks( $blocks, $slugs, $updated );

		if ( ! $updated ) {
			return;
		}

		$post_content = addslashes( serialize_blocks( $blocks ) );

		wp_update_post(
			[
				'ID'           => $post_id,
				'post_content' => $post_content,
			]
		);
	}

	/**
	 * Migrate the background type and associated values from
	 * instant form settings to form styling meta.
	 *
	 * @since 1.4.4
	 * @return void
	 */
	public function maybe_migrate_form_stylings() {
		$post_id = isset( $_GET['post'] ) ? Helper::get_integer_value( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET['post'] does not provide nonce.
		if ( empty( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );
		if ( SRFM_FORMS_POST_TYPE !== $post_type ) {
			return;
		}

		$instant_form_settings = get_post_meta( $post_id, '_srfm_instant_form_settings', true );
		if ( ! is_array( $instant_form_settings ) ) {
			return;
		}

		$form_styling = get_post_meta( $post_id, '_srfm_forms_styling', true );
		if ( ! is_array( $form_styling ) ) {
			$form_styling = [];
		}

		$migrated = false;
		$keys     = [ 'bg_type', 'bg_color', 'bg_image', 'bg_image_id' ];

		foreach ( $keys as $key ) {
			if ( ! isset( $form_styling[ $key ] ) && isset( $instant_form_settings[ $key ] ) ) {
				$form_styling[ $key ] = $instant_form_settings[ $key ];
				$migrated             = true;
			}
		}
		if ( $migrated ) {
			update_post_meta( $post_id, '_srfm_forms_styling', $form_styling );
		}
	}

	/**
	 * Register block pattern from the specified directory.
	 *
	 * @param string|mixed $block_pattern The block pattern name.
	 * @param string       $directory The directory path.
	 * @since 0.0.2
	 * @return bool True if the block pattern was registered, false otherwise.
	 */
	private function register_block_pattern_from_directory( $block_pattern, $directory ) {
		$pattern_file = $directory . $block_pattern . '.php';

		if ( is_readable( $pattern_file ) ) {
			register_block_pattern( 'srfm/' . $block_pattern, require $pattern_file );
			return true;
		}

		return false;
	}
}
