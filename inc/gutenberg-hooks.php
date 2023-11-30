<?php
/**
 * Gutenberg Hooks Manager Class.
 *
 * @package sureforms.
 */

namespace SureForms\Inc;

use Sureforms_Spec_Gb_Helper;
use SureForms\Inc\Traits\Get_Instance;
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
			'contact-form',
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
			$allow_block_types = array(
				'sureforms/input',
				'sureforms/email',
				'sureforms/textarea',
				'sureforms/number',
				'sureforms/switch',
				'sureforms/checkbox',
				'sureforms/phone',
				'sureforms/address',
				'sureforms/dropdown',
				'sureforms/multi-choice',
				'sureforms/radio',
				'sureforms/rating',
				'sureforms/submit',
				'sureforms/upload',
				'sureforms/url',
				'sureforms/password',
				'sureforms/date-time-picker',
				'sureforms/separator',
				'sureforms/icon',
				'sureforms/image',
				'sureforms/advanced-heading',
				'sureforms/number-slider',
				'sureforms/form',
			);
			// Apply a filter to the $allow_block_types types array.
			$allow_block_types = apply_filters( 'sureforms_allowed_block_types', $allow_block_types, $editor_context );
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
		$this->patterns = apply_filters( 'sureforms_block_patterns', $this->patterns );

		// loop through patterns and register.
		foreach ( $this->patterns as $block_pattern ) {
			$pattern_file = plugin_dir_path( SUREFORMS_FILE ) . 'templates/forms/' . $block_pattern . '.php';
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
		$post_types = array( SUREFORMS_FORMS_POST_TYPE );

		if ( is_null( $screen ) || ! in_array( $screen->post_type, $post_types, true ) ) {
			return;
		}

		$script_asset_path = SUREFORMS_DIR . 'assets/build/' . $form_editor_script . '.asset.php';
		$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: array(
				'dependencies' => [],
				'version'      => SUREFORMS_VER,
			);
		wp_enqueue_script( 'sureforms-' . $form_editor_script, SUREFORMS_URL . 'assets/build/' . $form_editor_script . '.js', $script_info['dependencies'], SUREFORMS_VER, true );

		wp_localize_script(
			'sureforms-' . $form_editor_script,
			'sfBlockData',
			[
				'plugin_url'  => SUREFORMS_URL,
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

		$blocks_asset_path = SUREFORMS_DIR . 'assets/build/' . $all_screen_blocks . '.asset.php';
		$blocks_info       = file_exists( $blocks_asset_path )
			? include $blocks_asset_path
			: array(
				'dependencies' => [],
				'version'      => SUREFORMS_VER,
			);
		wp_enqueue_script( 'sureforms-' . $all_screen_blocks, SUREFORMS_URL . 'assets/build/' . $all_screen_blocks . '.js', $blocks_info['dependencies'], SUREFORMS_VER, true );

		wp_localize_script(
			'sureforms-' . $all_screen_blocks,
			'sfBlockData',
			[
				'plugin_url'     => SUREFORMS_URL,
				'admin_email'    => get_option( 'admin_email' ),
				'post_url'       => admin_url( 'post.php' ),
				'current_screen' => get_current_screen(),

			]
		);

		// Localizing the field preview image links.
		wp_localize_script(
			'sureforms-' . $all_screen_blocks,
			'fieldsPreview',
			array(
				'input_preview'         => SUREFORMS_URL . 'images/field-previews/input.svg',
				'email_preview'         => SUREFORMS_URL . 'images/field-previews/email.svg',
				'url_preview'           => SUREFORMS_URL . 'images/field-previews/url.svg',
				'textarea_preview'      => SUREFORMS_URL . 'images/field-previews/textarea.svg',
				'multi_choice_preview'  => SUREFORMS_URL . 'images/field-previews/multi-choice.svg',
				'switch_preview'        => SUREFORMS_URL . 'images/field-previews/switch.svg',
				'checkbox_preview'      => SUREFORMS_URL . 'images/field-previews/checkbox.svg',
				'number_preview'        => SUREFORMS_URL . 'images/field-previews/number.svg',
				'rating_preview'        => SUREFORMS_URL . 'images/field-previews/rating.svg',
				'upload_preview'        => SUREFORMS_URL . 'images/field-previews/upload.svg',
				'phone_preview'         => SUREFORMS_URL . 'images/field-previews/phone.svg',
				'dropdown_preview'      => SUREFORMS_URL . 'images/field-previews/dropdown.svg',
				'address_preview'       => SUREFORMS_URL . 'images/field-previews/address.svg',
				'password_preview'      => SUREFORMS_URL . 'images/field-previews/password.svg',
				'date_time_preview'     => SUREFORMS_URL . 'images/field-previews/date-time.svg',
				'number_slider_preview' => SUREFORMS_URL . 'images/field-previews/number-slider.svg',
				'sureforms_preview'     => SUREFORMS_URL . 'images/field-previews/sureforms.svg',
			)
		);

		$formats = array();
		$mimes   = get_allowed_mime_types();
		$maxsize = wp_max_upload_size() / 1048576;
		if ( ! empty( $mimes ) ) {
			foreach ( $mimes as $type => $mime ) {
				$multiple = explode( '|', $type );
				foreach ( $multiple as $single ) {
					$formats[] = $single;
				}
			}
		}

		wp_localize_script(
			'sureforms-' . $all_screen_blocks,
			'srfm_blocks_info',
			[
				'font_awesome_5_polyfill' => array(),
			]
		);

		wp_localize_script(
			'sureforms-' . $all_screen_blocks,
			'upload_field',
			array(
				'upload_formats'   => $formats,
				'upload_max_limit' => $maxsize,
			)
		);
	}
}
