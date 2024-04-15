<?php
/**
 * Gutenberg Hooks Manager Class.
 *
 * @package sureforms.
 */

namespace SRFM\Inc;

use Spec_Gb_Helper;
use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Smart_Tags;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Gutenberg hooks handler class.
 *
 * @since 0.0.1
 */
class Gutenberg_Hooks {

	/**
	 * Block patterns to register.
	 *
	 * @var array<mixed>
	 */
	protected $patterns = [];

	/**
	 * Array of SureForms blocks which get have user input.
	 *
	 * @var array<string>
	 * @since x.x.x
	 */
	protected $srfm_blocks = [
		'srfm/input',
		'srfm/email',
		'srfm/textarea',
		'srfm/number',
		'srfm/checkbox',
		'srfm/phone',
		'srfm/address',
		'srfm/dropdown',
		'srfm/multi-choice',
		'srfm/radio',
		'srfm/submit',
		'srfm/url',
		// pro blocks.
		'srfm/date-time-picker',
		'srfm/hidden',
		'srfm/number-slider',
		'srfm/password',
		'srfm/rating',
		'srfm/upload',
	];

	use Get_Instance;

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
	}

	/**
	 * Disable Sureforms_Form Block and allowed only sureforms block inside Sureform CPT editor.
	 *
	 * @param bool|string[]            $allowed_block_types Array of block types.
	 * @param \WP_Block_Editor_Context $editor_context The current block editor context.
	 * @return array<mixed>|void
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
				'srfm/radio',
				'srfm/submit',
				'srfm/url',
				'srfm/separator',
				'srfm/icon',
				'srfm/image',
				'srfm/advanced-heading',
				'srfm/inline-button',

			];
			// Apply a filter to the $allow_block_types types array.
			$allow_block_types = apply_filters( 'srfm_allowed_block_types', $allow_block_types, $editor_context );
			return $allow_block_types;
		}
	}

	/**
	 * Register our custom block category.
	 *
	 * @param array<mixed> $categories Array of categories.
	 * @return array<mixed>
	 * @since 0.0.1
	 */
	public function register_block_categories( $categories ) {
		$custom_categories = [
			[
				'slug'  => 'sureforms',
				'title' => esc_html__( 'SureForms', 'sureforms' ),
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
		/**
		 * Filters the plugin block patterns.
		 *
		 * @param array<mixed> $patterns List of block patterns by name.
		 */
		$this->patterns = apply_filters( 'srfm_block_patterns', $this->patterns );

		// loop through patterns and register.
		foreach ( $this->patterns as $block_pattern ) {
			$pattern_file = plugin_dir_path( SRFM_FILE ) . 'templates/forms/' . $block_pattern . '.php';
			if ( is_readable( $pattern_file ) ) {
				register_block_pattern(
					'srfm/' . $block_pattern,
					require $pattern_file
				);
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
		wp_enqueue_script( SRFM_SLUG . $form_editor_script, SRFM_URL . 'assets/build/formEditor.js', $script_info['dependencies'], SRFM_VER, true );

		wp_localize_script(
			SRFM_SLUG . $form_editor_script,
			SRFM_SLUG . '_block_data',
			[
				'plugin_url'  => SRFM_URL,
				'admin_email' => get_option( 'admin_email' ),
			]
		);
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

		$plugin_path = 'sureforms-pro/sureforms-pro.php';

		// Check if the sureforms-pro plugin is active.
		$is_pro_active = defined( 'SRFM_PRO_VER' ) ? true : false;

		wp_localize_script(
			SRFM_SLUG . $all_screen_blocks,
			SRFM_SLUG . '_block_data',
			[
				'template_picker_url'              => admin_url( '/admin.php?page=add-new-form' ),
				'plugin_url'                       => SRFM_URL,
				'admin_email'                      => get_option( 'admin_email' ),
				'post_url'                         => admin_url( 'post.php' ),
				'current_screen'                   => $screen,
				'smart_tags_array'                 => Smart_Tags::smart_tag_list(),
				'srfm_form_markup_nonce'           => wp_create_nonce( 'srfm_form_markup' ),
				'get_form_markup_url'              => 'sureforms/v1/generate-form-markup',
				'is_pro_active'                    => $is_pro_active,
				'get_default_dynamic_block_option' => get_option( 'get_default_dynamic_block_option', Helper::default_dynamic_block_option() ),
				'form_selector_nonce'              => current_user_can( 'edit_posts' ) ? wp_create_nonce( 'wp_rest' ) : '',
				'is_admin_user'                    => current_user_can( 'manage_options' ),
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
	 * @since x.x.x
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

		foreach ( $blocks as $index => $block ) {
			// Checking only for SureForms blocks which can have user input.
			if ( ! in_array( $block['blockName'], $this->srfm_blocks, true ) ) {
				continue;
			}

			/**
			 * Lets continue if slug already exists.
			 * This will ensure that we don't update already existing slugs.
			 */
			if ( ! empty( $block['attrs']['slug'] ) ) {
				$slugs[] = $block['attrs']['slug'];
				continue;
			}

			$blocks[ $index ]['attrs']['slug'] = $this->generate_unique_block_slug( $block, $slugs );
			$slugs[]                           = $blocks[ $index ]['attrs']['slug'];
			$updated                           = true;
		}

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
	 * Generates slug based on the provided block and existing slugs.
	 *
	 * @param array<string,string|array<string,mixed>> $block The block data.
	 * @param array<string>                            $slugs The array of existing slugs.
	 * @since x.x.x
	 * @return string The generated unique block slug.
	 */
	public function generate_unique_block_slug( $block, $slugs ) {
		$slug = is_string( $block['blockName'] ) ? $block['blockName'] : '';

		if ( ! empty( $block['attrs']['label'] ) && is_string( $block['attrs']['label'] ) ) {
			$slug = sanitize_title( $block['attrs']['label'] );
		}

		$slug = $this->generate_slug( $slug, $slugs );

		return $slug;
	}

	/**
	 * This function ensures that the slug is unique.
	 * If the slug is already taken, it appends a number to the slug to make it unique.
	 *
	 * @param string        $slug test to be converted to slug.
	 * @param array<string> $slugs An array of existing slugs.
	 * @since x.x.x
	 * @return string The unique slug.
	 */
	public function generate_slug( $slug, $slugs ) {
		$slug = sanitize_title( $slug );

		if ( ! in_array( $slug, $slugs, true ) ) {
			return $slug;
		}

		$index = 1;

		while ( in_array( $slug . '-' . $index, $slugs, true ) ) {
			$index++;
		}

		return $slug . '-' . $index;
	}
}
