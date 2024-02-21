<?php
/**
 * Gutenberg Hooks Manager Class.
 *
 * @package sureforms.
 */

namespace SRFM\Inc;

use SRFM_Spec_Gb_Helper;
use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\SRFM_Smart_Tags;
/**
 * Gutenberg hooks handler class.
 *
 * @since 0.0.1
 */
class SRFM_Gutenberg_Hooks {

	/**
	 * Block patterns to register.
	 *
	 * @var array<mixed>
	 */
	protected $patterns = [];

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
			'job-application-form',
			'feedback-form',
			// Will be used in future.
			// 'login-form',.
		];

		// Initializing hooks.
		add_action( 'enqueue_block_editor_assets', [ $this, 'form_editor_screen_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'block_editor_assets' ] );
		add_filter( 'block_categories_all', [ $this, 'register_block_categories' ], 10, 1 );
		add_action( 'init', [ $this, 'register_block_patterns' ], 9 );
		add_filter( 'allowed_block_types_all', [ $this, 'disable_forms_wrapper_block' ], 10, 2 );
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
				'sureforms/input',
				'sureforms/email',
				'sureforms/textarea',
				'sureforms/number',
				'sureforms/checkbox',
				'sureforms/phone',
				'sureforms/address',
				'sureforms/dropdown',
				'sureforms/multi-choice',
				'sureforms/radio',
				'sureforms/submit',
				'sureforms/url',
				'sureforms/separator',
				'sureforms/icon',
				'sureforms/image',
				'sureforms/advanced-heading',

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
		return [
			...[
				[
					'slug'  => 'sureforms',
					'title' => esc_html__( 'SureForms', 'sureforms' ),
				],
			],
			...$categories,
		];
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
					'sureforms/' . $block_pattern,
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
		$form_editor_script = 'formEditor';

		$screen     = get_current_screen();
		$post_types = [ SRFM_FORMS_POST_TYPE ];

		if ( is_null( $screen ) || ! in_array( $screen->post_type, $post_types, true ) ) {
			return;
		}

		$script_asset_path = SRFM_DIR . 'assets/build/' . $form_editor_script . '.asset.php';
		$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: [
				'dependencies' => [],
				'version'      => SRFM_VER,
			];
		wp_enqueue_script( 'sureforms-' . $form_editor_script, SRFM_URL . 'assets/build/' . $form_editor_script . '.js', $script_info['dependencies'], SRFM_VER, true );

		wp_localize_script(
			'sureforms-' . $form_editor_script,
			'sfBlockData',
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
		$all_screen_blocks = 'blocks';
		$screen            = get_current_screen();

		$blocks_asset_path = SRFM_DIR . 'assets/build/' . $all_screen_blocks . '.asset.php';
		$blocks_info       = file_exists( $blocks_asset_path )
			? include $blocks_asset_path
			: [
				'dependencies' => [],
				'version'      => SRFM_VER,
			];
		wp_enqueue_script( 'sureforms-' . $all_screen_blocks, SRFM_URL . 'assets/build/' . $all_screen_blocks . '.js', $blocks_info['dependencies'], SRFM_VER, true );

		$plugin_path = 'sureforms-pro/sureforms-pro.php';

		// Check if the sureforms-pro plugin is active.
		$is_pro_active = defined( 'SUREFORMS_PRO_VER' ) ? true : false;

		wp_localize_script(
			'sureforms-' . $all_screen_blocks,
			'sfBlockData',
			[
				'template_picker_url'    => admin_url( '/admin.php?page=add-new-form' ),
				'plugin_url'             => SRFM_URL,
				'admin_email'            => get_option( 'admin_email' ),
				'post_url'               => admin_url( 'post.php' ),
				'current_screen'         => $screen,
				'smart_tags_array'       => SRFM_Smart_Tags::smart_tag_list(),
				'srfm_form_markup_nonce' => wp_create_nonce( 'srfm_form_markup' ),
				'get_form_markup_url'    => 'sureforms/v1/generate-form-markup',
				'is_pro_active'          => $is_pro_active,
			]
		);

		// Localizing the field preview image links.
		wp_localize_script(
			'sureforms-' . $all_screen_blocks,
			'fieldsPreview',
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
			),
		);

		wp_localize_script(
			'sureforms-' . $all_screen_blocks,
			'srfm_blocks_info',
			[
				'font_awesome_5_polyfill' => [],
				'collapse_panels'         => 'enabled',
				'is_site_editor'          => $screen ? $screen->id : null,
			]
		);
	}
}
