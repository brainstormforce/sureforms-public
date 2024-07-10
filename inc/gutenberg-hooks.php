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
	 * @since 0.0.2
	 */
	protected $srfm_blocks = [
		'srfm/input',
		'srfm/email',
		'srfm/textarea',
		'srfm/number',
		'srfm/checkbox',
		'srfm/gdpr',
		'srfm/phone',
		'srfm/address',
		'srfm/address-compact',
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
				'srfm/address-compact',
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

		// Enqueue the code editor for the Custom CSS Editor in SureForms.
		wp_enqueue_code_editor( [ 'type' => 'text/css' ] );
		wp_enqueue_script( 'wp-theme-plugin-editor' );
		wp_enqueue_style( 'wp-codemirror' );

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
				'smart_tags_array_email'           => Smart_Tags::email_smart_tag_list(),
				'srfm_form_markup_nonce'           => wp_create_nonce( 'srfm_form_markup' ),
				'get_form_markup_url'              => 'sureforms/v1/generate-form-markup',
				'is_pro_active'                    => defined( 'SRFM_PRO_VER' ),
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
					'input_preview'           => SRFM_URL . 'images/field-previews/input.svg',
					'email_preview'           => SRFM_URL . 'images/field-previews/email.svg',
					'url_preview'             => SRFM_URL . 'images/field-previews/url.svg',
					'textarea_preview'        => SRFM_URL . 'images/field-previews/textarea.svg',
					'multi_choice_preview'    => SRFM_URL . 'images/field-previews/multi-choice.svg',
					'checkbox_preview'        => SRFM_URL . 'images/field-previews/checkbox.svg',
					'number_preview'          => SRFM_URL . 'images/field-previews/number.svg',
					'phone_preview'           => SRFM_URL . 'images/field-previews/phone.svg',
					'dropdown_preview'        => SRFM_URL . 'images/field-previews/dropdown.svg',
					'address_preview'         => SRFM_URL . 'images/field-previews/address.svg',
					'address_compact_preview' => SRFM_URL . 'images/field-previews/address-compact.svg',
					'sureforms_preview'       => SRFM_URL . 'images/field-previews/sureforms.svg',
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

		list( $blocks, $slugs, $updated ) = $this->process_blocks( $blocks, $slugs, $updated );

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
	 * Process blocks and inner blocks.
	 *
	 * @param array<array<array<mixed>>> $blocks The block data.
	 * @param array<string>              $slugs The array of existing slugs.
	 * @param bool                       $updated The array of existing slugs.
	 * @param string                     $prefix The array of existing slugs.
	 * @since 0.0.3
	 * @return array{array<array<array<mixed>>>,array<string>,bool}
	 */
	public function process_blocks( $blocks, $slugs, $updated, $prefix = '' ) {

		if ( ! is_array( $blocks ) ) {
			return [ $blocks, $slugs, $updated ];
		}

		foreach ( $blocks as $index => $block ) {

			if ( ! is_array( $block ) ) {
				continue;
			}
			// Checking only for SureForms blocks which can have user input.
			if ( empty( $block['blockName'] ) || ! in_array( $block['blockName'], $this->srfm_blocks, true ) ) {
				continue;
			}

			/**
			 * Lets continue if slug already exists.
			 * This will ensure that we don't update already existing slugs.
			 */
			if ( isset( $block['attrs'] ) && ! empty( $block['attrs']['slug'] ) && ! in_array( $block['attrs']['slug'], $slugs, true ) ) {

				$slugs[] = Helper::get_string_value( $block['attrs']['slug'] );
				continue;
			}

			if ( is_array( $blocks[ $index ]['attrs'] ) ) {

				$blocks[ $index ]['attrs']['slug'] = $this->generate_unique_block_slug( $block, $slugs, $prefix );
				$slugs[]                           = $blocks[ $index ]['attrs']['slug'];
				$updated                           = true;
				if ( is_array( $block['innerBlocks'] ) && ! empty( $block['innerBlocks'] ) ) {

					list( $blocks[ $index ]['innerBlocks'], $slugs, $updated ) = $this->process_blocks( $block['innerBlocks'], $slugs, $updated, $blocks[ $index ]['attrs']['slug'] );

				}
			}
		}
		return [ $blocks, $slugs, $updated ];
	}

	/**
	 * Generates slug based on the provided block and existing slugs.
	 *
	 * @param array<mixed>  $block The block data.
	 * @param array<string> $slugs The array of existing slugs.
	 * @param string        $prefix The array of existing slugs.
	 * @since 0.0.2
	 * @return string The generated unique block slug.
	 */
	public function generate_unique_block_slug( $block, $slugs, $prefix ) {
		$slug = is_string( $block['blockName'] ) ? $block['blockName'] : '';

		if ( ! empty( $block['attrs']['label'] ) && is_string( $block['attrs']['label'] ) ) {
			$slug = sanitize_title( $block['attrs']['label'] );
		}

		if ( ! empty( $prefix ) ) {
			$slug = $prefix . '-' . $slug;
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
	 * @since 0.0.2
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
