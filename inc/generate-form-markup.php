<?php
/**
 * Sureforms Generate Form Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Load Defaults Class.
 *
 * @since 0.0.1
 */
class Generate_Form_Markup {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
	}

	/**
	 * Add custom API Route to generate form markup.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/generate-form-markup',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_form_markup' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Handle Form status
	 *
	 * @param int|string $id Contains form ID.
	 * @param bool       $show_title_current_page Boolean to srfm-show/srfm-hide form title.
	 * @param string     $sf_classname additional class_name.
	 * @param string     $post_type Contains post type.
	 * @param bool       $do_blocks Boolean to enable/disable parsing dynamic blocks.
	 *
	 * @return string|false
	 * @since 0.0.1
	 */
	public static function get_form_markup( $id, $show_title_current_page = true, $sf_classname = '', $post_type = 'post', $do_blocks = false ) {
		if ( isset( $_GET['id'] ) && isset( $_GET['srfm_form_markup_nonce'] ) ) {
			$nonce = isset( $_GET['srfm_form_markup_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['srfm_form_markup_nonce'] ) ) : '';
			$id    = wp_verify_nonce( $nonce, 'srfm_form_markup' ) && ! empty( $_GET['srfm_form_markup_nonce'] ) ? Helper::get_integer_value( sanitize_text_field( wp_unslash( $_GET['id'] ) ) ) : '';
		} else {
			$id = Helper::get_integer_value( $id );
		}
		do_action( 'srfm_localize_conditional_logic_data', $id );
		$post = get_post( Helper::get_integer_value( $id ) );

		$content = '';

		if ( $post && ! empty( $post->post_content ) ) {
			if ( ! empty( $do_blocks ) ) {
				$content = do_blocks( $post->post_content );
			} else {
				$content = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wordpress hook
			}
		}

		$blocks            = parse_blocks( $content );
		$block_count       = count( $blocks );
		$current_post_type = get_post_type();

		// load all the frontend assets.
		Frontend_Assets::enqueue_scripts_and_styles();

		ob_start();
		if ( '' !== $id && 0 !== $block_count ) {

			$container_id = 'srfm-form-container-' . Helper::get_string_value( $id );
			$form_styling = get_post_meta( $id, '_srfm_forms_styling', true );
			$form_styling = ! empty( $form_styling ) && is_array( $form_styling ) ? $form_styling : [];
			// Background Settings.
			$bg_type                   = $form_styling['bg_type'] ?? 'color';
			$bg_color                  = $form_styling['bg_color'] ?? '';
			$bg_image                  = $form_styling['bg_image'] ?? '';
			$bg_image_position         = $form_styling['bg_image_position'] ?? [];
			$bg_image_attachment       = $form_styling['bg_image_attachment'] ?? 'scroll';
			$bg_image_repeat           = $form_styling['bg_image_repeat'] ?? 'no-repeat';
			$bg_image_size             = $form_styling['bg_image_size'] ?? 'cover';
			$bg_image_size_custom      = $form_styling['bg_image_size_custom'] ?? 100;
			$bg_image_size_custom_unit = $form_styling['bg_image_size_custom_unit'] ?? '%';
			$bg_gradient               = $form_styling['bg_gradient'] ?? 'linear-gradient(90deg, #FFC9B2 0%, #C7CBFF 100%)';
			$gradient_type             = $form_styling['gradient_type'] ?? 'basic'; // Basic or advanced.
			$is_advanced_gradient      = 'advanced' === $gradient_type ? true : false;
			$bg_gradient_type          = $is_advanced_gradient && isset( $form_styling['bg_gradient_type'] ) ? $form_styling['bg_gradient_type'] : 'linear'; // linear or radial gradient.
			$bg_gradient_color_1       = $is_advanced_gradient && isset( $form_styling['bg_gradient_color_1'] ) ? $form_styling['bg_gradient_color_1'] : '';
			$bg_gradient_color_2       = $is_advanced_gradient && isset( $form_styling['bg_gradient_color_2'] ) ? $form_styling['bg_gradient_color_2'] : '';
			$bg_gradient_location_1    = $is_advanced_gradient && isset( $form_styling['bg_gradient_location_1'] ) ? $form_styling['bg_gradient_location_1'] : '';
			$bg_gradient_location_2    = $is_advanced_gradient && isset( $form_styling['bg_gradient_location_2'] ) ? $form_styling['bg_gradient_location_2'] : '';
			$bg_gradient_angle         = $is_advanced_gradient && isset( $form_styling['bg_gradient_angle'] ) ? $form_styling['bg_gradient_angle'] : '';
			// Overlay Settings.
			$overlay_type       = $form_styling['bg_gradient_overlay_type'] ?? '';
			$overlay_size       = $form_styling['bg_overlay_size'] ?? 'cover';
			$overlay_opacity    = $form_styling['bg_overlay_opacity'] ?? 1;
			$overlay_color      = $form_styling['bg_image_overlay_color'] ?? '';
			$overlay_image      = $form_styling['bg_overlay_image'] ?? '';
			$overlay_position   = $form_styling['bg_overlay_position'] ?? [];
			$overlay_attachment = $form_styling['bg_overlay_attachment'] ?? 'scroll';
			$overlay_repeat     = $form_styling['bg_overlay_repeat'] ?? 'no-repeat';
			$overlay_blend_mode = $form_styling['bg_overlay_blend_mode'] ?? 'normal';
			// Gradient Overlay.
			$bg_overlay_gradient            = $form_styling['bg_overlay_gradient'] ?? 'linear-gradient(90deg, #FFC9B2 0%, #C7CBFF 100%)';
			$overlay_gradient_type          = $form_styling['overlay_gradient_type'] ?? 'basic'; // Basic or advanced.
			$is_overlay_advanced_gradient   = 'advanced' === $overlay_gradient_type ? true : false;
			$bg_overlay_gradient_type       = $is_overlay_advanced_gradient && isset( $form_styling['bg_overlay_gradient_type'] ) ? $form_styling['bg_overlay_gradient_type'] : 'linear';
			$bg_overlay_gradient_color_1    = $is_overlay_advanced_gradient && isset( $form_styling['bg_overlay_gradient_color_1'] ) ? $form_styling['bg_overlay_gradient_color_1'] : '';
			$bg_overlay_gradient_color_2    = $is_overlay_advanced_gradient && isset( $form_styling['bg_overlay_gradient_color_2'] ) ? $form_styling['bg_overlay_gradient_color_2'] : '';
			$bg_overlay_gradient_location_1 = $is_overlay_advanced_gradient && isset( $form_styling['bg_overlay_gradient_location_1'] ) ? $form_styling['bg_overlay_gradient_location_1'] : '';
			$bg_overlay_gradient_location_2 = $is_overlay_advanced_gradient && isset( $form_styling['bg_overlay_gradient_location_2'] ) ? $form_styling['bg_overlay_gradient_location_2'] : '';
			$bg_overlay_gradient_angle      = $is_overlay_advanced_gradient && isset( $form_styling['bg_overlay_gradient_angle'] ) ? $form_styling['bg_overlay_gradient_angle'] : '';
			// Embed Form Settings.
			$form = [
				// Padding.
				'padding_top'          => isset( $form_styling['form_padding_top'] ) ? floatval( $form_styling['form_padding_top'] ) : 0,
				'padding_right'        => isset( $form_styling['form_padding_right'] ) ? floatval( $form_styling['form_padding_right'] ) : 0,
				'padding_bottom'       => isset( $form_styling['form_padding_bottom'] ) ? floatval( $form_styling['form_padding_bottom'] ) : 0,
				'padding_left'         => isset( $form_styling['form_padding_left'] ) ? floatval( $form_styling['form_padding_left'] ) : 0,
				'padding_unit'         => isset( $form_styling['form_padding_unit'] ) ? Helper::get_string_value( $form_styling['form_padding_unit'] ) : 'px',
				// Border Radius.
				'border_radius_top'    => isset( $form_styling['form_border_radius_top'] ) ? floatval( $form_styling['form_border_radius_top'] ) : 0,
				'border_radius_right'  => isset( $form_styling['form_border_radius_right'] ) ? floatval( $form_styling['form_border_radius_right'] ) : 0,
				'border_radius_bottom' => isset( $form_styling['form_border_radius_bottom'] ) ? floatval( $form_styling['form_border_radius_bottom'] ) : 0,
				'border_radius_left'   => isset( $form_styling['form_border_radius_left'] ) ? floatval( $form_styling['form_border_radius_left'] ) : 0,
				'border_radius_unit'   => isset( $form_styling['form_border_radius_unit'] ) ? Helper::get_string_value( $form_styling['form_border_radius_unit'] ) : 'px',
			];
			// Instant Form Settings.
			$instant_form = [
				// Padding.
				'padding_top'          => isset( $form_styling['instant_form_padding_top'] ) ? floatval( $form_styling['instant_form_padding_top'] ) : 32,
				'padding_right'        => isset( $form_styling['instant_form_padding_right'] ) ? floatval( $form_styling['instant_form_padding_right'] ) : 32,
				'padding_bottom'       => isset( $form_styling['instant_form_padding_bottom'] ) ? floatval( $form_styling['instant_form_padding_bottom'] ) : 32,
				'padding_left'         => isset( $form_styling['instant_form_padding_left'] ) ? floatval( $form_styling['instant_form_padding_left'] ) : 32,
				'padding_unit'         => isset( $form_styling['instant_form_padding_unit'] ) ? Helper::get_string_value( $form_styling['instant_form_padding_unit'] ) : 'px',
				// Border Radius.
				'border_radius_top'    => isset( $form_styling['instant_form_border_radius_top'] ) ? floatval( $form_styling['instant_form_border_radius_top'] ) : 12,
				'border_radius_right'  => isset( $form_styling['instant_form_border_radius_right'] ) ? floatval( $form_styling['instant_form_border_radius_right'] ) : 12,
				'border_radius_bottom' => isset( $form_styling['instant_form_border_radius_bottom'] ) ? floatval( $form_styling['instant_form_border_radius_bottom'] ) : 12,
				'border_radius_left'   => isset( $form_styling['instant_form_border_radius_left'] ) ? floatval( $form_styling['instant_form_border_radius_left'] ) : 12,
				'border_radius_unit'   => isset( $form_styling['instant_form_border_radius_unit'] ) ? Helper::get_string_value( $form_styling['instant_form_border_radius_unit'] ) : 'px',
			];

			if ( 'custom' === $overlay_size ) {
				$bg_overlay_custom_size      = $form_styling['bg_overlay_custom_size'] ?? 100;
				$bg_overlay_custom_size_unit = $form_styling['bg_overlay_custom_size_unit'] ?? '%';
				$overlay_size                = $bg_overlay_custom_size . $bg_overlay_custom_size_unit;
			}

			$background_classes = apply_filters( 'srfm_add_background_classes', Helper::get_background_classes( $bg_type, $overlay_type, $bg_image ), $id );

			$neve_theme_margin_class_name = 'srfm-neve-theme-add-margin-bottom';
			$theme_name                   = wp_get_theme()->get( 'Name' );

			$form_classes = [
				'srfm-form-container',
				$container_id,
				$sf_classname,
				'Neve' === $theme_name ? $neve_theme_margin_class_name : '', // compatibility with Neve theme for margin between main content and footer.
				$background_classes,
			];

			$custom_added_classes = Helper::get_meta_value( $id, '_srfm_additional_classes' );
			if ( ! empty( $custom_added_classes ) && is_string( $custom_added_classes ) ) {
				$custom_added_classes = explode( ' ', $custom_added_classes );
				foreach ( $custom_added_classes as $class ) {
					if ( Helper::is_valid_css_class_name( $class ) ) {
						$form_classes[] = $class;
					}
				}
			}

			$page_break_settings      = defined( 'SRFM_PRO_VER' ) && apply_filters( 'srfm_use_page_break_layout', true ) ? get_post_meta( $id, '_srfm_page_break_settings', true ) : [];
			$page_break_settings      = ! empty( $page_break_settings ) && is_array( $page_break_settings ) ? $page_break_settings : [];
			$is_page_break            = ! empty( $page_break_settings ) ? $page_break_settings['is_page_break'] : false;
			$page_break_progress_type = ! empty( $page_break_settings ) ? $page_break_settings['progress_indicator_type'] : 'none';
			$form_confirmation        = get_post_meta( $id, '_srfm_form_confirmation' );
			$confirmation_type        = '';
			$submission_action        = '';
			$success_url              = '';
			if ( is_array( $form_confirmation ) && isset( $form_confirmation[0][0] ) ) {
				$confirmation_data = $form_confirmation[0][0];
				$page_url          = $confirmation_data['page_url'] ?? '';
				$custom_url        = $confirmation_data['custom_url'] ?? '';
				$confirmation_type = $confirmation_data['confirmation_type'] ?? '';
				$submission_action = $confirmation_data['submission_action'] ?? '';
				$success_url       = '';
				if ( 'different page' === $confirmation_type ) {
					$success_url = $page_url;
				} elseif ( 'custom url' === $confirmation_type ) {
					$success_url = $custom_url;
				}
			}

			// Submit button.
			$button_text             = Helper::get_meta_value( $id, '_srfm_submit_button_text' );
			$submit_button_alignment = ! empty( $form_styling['submit_button_alignment'] ) ? $form_styling['submit_button_alignment'] : 'left';

			if ( is_rtl() && ( 'left' === $submit_button_alignment || 'right' === $submit_button_alignment ) ) {
				$submit_button_alignment = 'right' === $submit_button_alignment ? 'left' : 'right';
			}

			$btn_from_theme       = Helper::get_meta_value( $id, '_srfm_inherit_theme_button' );
			$is_inline_button     = apply_filters( 'srfm_is_inline_button', Helper::get_meta_value( $id, '_srfm_is_inline_button' ) );
			$security_type        = Helper::get_meta_value( $id, '_srfm_captcha_security_type' );
			$form_custom_css_meta = Helper::get_meta_value( $id, '_srfm_form_custom_css' );
			$custom_css           = ! empty( $form_custom_css_meta ) && is_string( $form_custom_css_meta ) ? $form_custom_css_meta : '';

			$full                       = 'justify' === $submit_button_alignment ? true : false;
			$recaptcha_version          = 'g-recaptcha' === $security_type ? Helper::get_meta_value( $id, '_srfm_form_recaptcha' ) : '';
			$srfm_cf_appearance_mode    = '';
			$srfm_cf_turnstile_site_key = '';
			$srfm_hcaptcha_site_key     = '';

			$google_captcha_site_key = '';

			if ( 'none' !== $security_type ) {
				$global_setting_options = get_option( 'srfm_security_settings_options' );
			} else {
				$global_setting_options = [];
			}

			if ( is_array( $global_setting_options ) && 'cf-turnstile' === $security_type ) {
				$srfm_cf_turnstile_site_key = $global_setting_options['srfm_cf_turnstile_site_key'] ?? '';
				$srfm_cf_appearance_mode    = $global_setting_options['srfm_cf_appearance_mode'] ?? 'auto';
			}

			if ( is_array( $global_setting_options ) && 'hcaptcha' === $security_type ) {
				$srfm_hcaptcha_site_key = $global_setting_options['srfm_hcaptcha_site_key'] ?? '';
			}

			if ( is_array( $global_setting_options ) && 'g-recaptcha' === $security_type ) {
				switch ( $recaptcha_version ) {
					case 'v2-checkbox':
						$google_captcha_site_key = $global_setting_options['srfm_v2_checkbox_site_key'] ?? '';
						break;
					case 'v2-invisible':
						$google_captcha_site_key = $global_setting_options['srfm_v2_invisible_site_key'] ?? '';
						break;
					case 'v3-reCAPTCHA':
						$google_captcha_site_key = $global_setting_options['srfm_v3_site_key'] ?? '';
						break;
					default:
						break;
				}
			}

			// Ensure $google_captcha_site_key is not empty, and if not, trim any leading or trailing whitespace.
			$google_captcha_site_key = is_string( $google_captcha_site_key ) && ! empty( $google_captcha_site_key ) ? trim( $google_captcha_site_key ) : '';

			$primary_color    = $form_styling['primary_color'];
			$help_color_var   = $form_styling['text_color'];
			$label_text_color = $form_styling['text_color_on_primary'];
			$field_spacing    = $form_styling['field_spacing'];

			// New colors.

			$primary_color_var    = $primary_color ? $primary_color : '#046bd2';
			$label_text_color_var = $label_text_color ? $label_text_color : '#111827';

			$selected_size = Helper::get_css_vars( $field_spacing );

			$should_show_submit_button = apply_filters(
				'srfm_show_submit_button',
				0 !== $block_count && ! $is_inline_button || $is_page_break,
				$id
			);

			if ( ! $should_show_submit_button ) {
				$form_classes[] = 'srfm-submit-button-hidden';
			}

			?>
			<div class="<?php echo esc_attr( implode( ' ', array_filter( $form_classes ) ) ); ?>">
			<style>
				/* Need to check and remove the input variables related to the Style Tab. */
				<?php echo esc_html( ".{$container_id}" ); ?> {
					/* New test variables */
					--srfm-color-scheme-primary: <?php echo esc_html( $primary_color_var ); ?>;
					--srfm-color-scheme-text-on-primary: <?php echo esc_html( $label_text_color_var ); ?>;
					--srfm-color-scheme-text: <?php echo esc_html( $help_color_var ); ?>;

					--srfm-color-input-label: <?php echo esc_html( $help_color_var ); ?>;
					--srfm-color-input-description: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.65 );
					--srfm-color-input-placeholder: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.5 );
					--srfm-color-input-text: <?php echo esc_html( $help_color_var ); ?>;
					--srfm-color-input-prefix: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.65 );
					--srfm-color-input-background: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.02 );
					--srfm-color-input-background-hover: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.05 );
					--srfm-color-input-background-disabled: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.07 );
					--srfm-color-input-border: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.25 );
					--srfm-color-input-border-disabled: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.15 );
					--srfm-color-multi-choice-svg: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.7 );
					--srfm-color-input-border-hover: hsl( from <?php echo esc_html( $primary_color_var ); ?> h s l / 0.65 );
					--srfm-color-input-border-focus-glow: hsl( from <?php echo esc_html( $primary_color_var ); ?> h s l / 0.15 );
					--srfm-color-input-selected: hsl( from <?php echo esc_html( $primary_color_var ); ?> h s l / 0.1 );
					--srfm-btn-color-hover: hsl( from <?php echo esc_html( $primary_color_var ); ?> h s l / 0.9 );
					--srfm-btn-color-disabled: hsl( from <?php echo esc_html( $primary_color_var ); ?> h s l / 0.25 );

					/* Dropdown Variables */
					--srfm-dropdown-input-background-hover: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.05 );
					--srfm-dropdown-option-background-hover: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.10 );
					--srfm-dropdown-option-background-selected: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.05 );
					--srfm-dropdown-option-selected-icon: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.65 );
					--srfm-dropdown-option-text-color: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.80 );
					--srfm-dropdown-option-selected-text: <?php echo esc_html( $help_color_var ); ?>;
					--srfm-dropdown-badge-background: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.05 );
					--srfm-dropdown-badge-background-hover: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.10 );
					--srfm-dropdown-menu-border-color: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.10 );
					--srfm-dropdown-placeholder-color: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.50 );
					--srfm-dropdown-icon-color: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.65 );
					--srfm-dropdown-icon-disabled: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.25 );

					/* Background Control Variables */
					<?php
						// Form Styles.
						$styling_vars = [
							// Instant Form Padding.
							'--srfm-instant-form-padding-top' => sanitize_text_field( "{$instant_form['padding_top']}{$instant_form['padding_unit']}" ),
							'--srfm-instant-form-padding-right' => sanitize_text_field( "{$instant_form['padding_right']}{$instant_form['padding_unit']}" ),
							'--srfm-instant-form-padding-bottom' => sanitize_text_field( "{$instant_form['padding_bottom']}{$instant_form['padding_unit']}" ),
							'--srfm-instant-form-padding-left' => sanitize_text_field( "{$instant_form['padding_left']}{$instant_form['padding_unit']}" ),
							// Instant Form Border Radius.
							'--srfm-instant-form-border-radius-top' => sanitize_text_field( "{$instant_form['border_radius_top']}{$instant_form['border_radius_unit']}" ),
							'--srfm-instant-form-border-radius-right' => sanitize_text_field( "{$instant_form['border_radius_right']}{$instant_form['border_radius_unit']}" ),
							'--srfm-instant-form-border-radius-bottom' => sanitize_text_field( "{$instant_form['border_radius_bottom']}{$instant_form['border_radius_unit']}" ),
							'--srfm-instant-form-border-radius-left' => sanitize_text_field( "{$instant_form['border_radius_left']}{$instant_form['border_radius_unit']}" ),
							// Embed Form Padding.
							'--srfm-form-padding-top'    => sanitize_text_field( "{$form['padding_top']}{$form['padding_unit']}" ),
							'--srfm-form-padding-right'  => sanitize_text_field( "{$form['padding_right']}{$form['padding_unit']}" ),
							'--srfm-form-padding-bottom' => sanitize_text_field( "{$form['padding_bottom']}{$form['padding_unit']}" ),
							'--srfm-form-padding-left'   => sanitize_text_field( "{$form['padding_left']}{$form['padding_unit']}" ),
							// Embed Form Border Radius.
							'--srfm-form-border-radius-top' => sanitize_text_field( "{$form['border_radius_top']}{$form['border_radius_unit']}" ),
							'--srfm-form-border-radius-right' => sanitize_text_field( "{$form['border_radius_right']}{$form['border_radius_unit']}" ),
							'--srfm-form-border-radius-bottom' => sanitize_text_field( "{$form['border_radius_bottom']}{$form['border_radius_unit']}" ),
							'--srfm-form-border-radius-left' => sanitize_text_field( "{$form['border_radius_left']}{$form['border_radius_unit']}" ),
						];
						// Background Styles.
						if ( 'image' === $bg_type && ! empty( $bg_image ) ) {
							$bg_size_merged = 'custom' === $bg_image_size ? "{$bg_image_size_custom}{$bg_image_size_custom_unit}" : $bg_image_size;
							$styling_vars  += [
								'--srfm-bg-image'      => 'url(' . esc_url_raw( $bg_image ) . ')',
								'--srfm-bg-position'   => sanitize_text_field(
									( ( ! empty( $bg_image_position['x'] ) ? $bg_image_position['x'] : 0.5 ) * 100 ) . '% ' .
									( ( ! empty( $bg_image_position['y'] ) ? $bg_image_position['y'] : 0.5 ) * 100 ) . '% '
								),
								'--srfm-bg-attachment' => sanitize_text_field( $bg_image_attachment ),
								'--srfm-bg-repeat'     => sanitize_text_field( $bg_image_repeat ),
								'--srfm-bg-size'       => sanitize_text_field( $bg_size_merged ),
							];
						} elseif ( 'color' === $bg_type && ! empty( $bg_color ) ) {
							$styling_vars['--srfm-bg-color'] = sanitize_text_field( $bg_color );
						} elseif ( 'gradient' === $bg_type && ! empty( $bg_gradient ) ) {
							if ( $is_advanced_gradient ) {
								$bg_gradient = Helper::get_gradient_css( $bg_gradient_type, $bg_gradient_color_1, $bg_gradient_color_2, $bg_gradient_location_1, $bg_gradient_location_2, $bg_gradient_angle );
							}
							$styling_vars['--srfm-bg-gradient'] = sanitize_text_field( $bg_gradient );
						}
							// Overlay Variables.
						if ( 'image' === $bg_type && 'image' === $overlay_type && ! empty( $overlay_image ) ) {
							$styling_vars += [
								'--srfm-bg-overlay-image'  => 'url(' . esc_url_raw( $overlay_image ) . ')',
								'--srfm-bg-overlay-position' => sanitize_text_field(
									( ( ! empty( $overlay_position['x'] ) ? $overlay_position['x'] : 0.5 ) * 100 ) . '% ' .
									( ( ! empty( $overlay_position['y'] ) ? $overlay_position['y'] : 0.5 ) * 100 ) . '%'
								),
								'--srfm-bg-overlay-attachment' => sanitize_text_field( $overlay_attachment ),
								'--srfm-bg-overlay-repeat' => sanitize_text_field( $overlay_repeat ),
								'--srfm-bg-overlay-size'   => sanitize_text_field( $overlay_size ),
								'--srfm-bg-overlay-blend-mode' => sanitize_text_field( $overlay_blend_mode ),
							];
						} elseif ( 'image' === $bg_type && 'color' === $overlay_type && ! empty( $overlay_color ) ) {
							$styling_vars += [
								'--srfm-bg-overlay-color' => sanitize_text_field( $overlay_color ),
							];
						} elseif ( 'image' === $bg_type && 'gradient' === $overlay_type && ! empty( $bg_overlay_gradient ) ) {
							if ( $is_overlay_advanced_gradient ) {
								$bg_overlay_gradient = Helper::get_gradient_css( $bg_overlay_gradient_type, $bg_overlay_gradient_color_1, $bg_overlay_gradient_color_2, $bg_overlay_gradient_location_1, $bg_overlay_gradient_location_2, $bg_overlay_gradient_angle );
							}
							$styling_vars += [
								'--srfm-bg-overlay-gradient' => sanitize_text_field( $bg_overlay_gradient ),
							];
						}
						$styling_vars['--srfm-bg-overlay-opacity'] = floatval( $overlay_opacity );
						// Output the CSS variables.
						foreach ( $styling_vars as $key => $value ) {
							echo esc_html( Helper::get_string_value( $key ) ) . ': ' . esc_html( Helper::get_string_value( $value ) ) . ';';
						}
						?>
					<?php
					// Echo the CSS variables for the form according to the field spacing selected.
					foreach ( $selected_size as $variable => $value ) {
						echo esc_html( Helper::get_string_value( $variable ) ) . ': ' . esc_html( Helper::get_string_value( $value ) ) . ';';
					}
					do_action(
						'srfm_form_css_variables',
						[
							'id'            => $id,
							'primary_color' => $primary_color_var,
							'help_color'    => $help_color_var,
							'form_styling'  => $form_styling,
						]
					);
					// echo custom css on page/post.
					if ( 'sureforms_form' !== $current_post_type ) {
						echo wp_kses_post( $custom_css );
					}
					?>
				}
			</style>
			<?php
			if ( 'sureforms_form' !== $current_post_type && true === $show_title_current_page ) {
				$title = ! empty( get_the_title( (int) $id ) ) ? get_the_title( (int) $id ) : '';
				?>
				<h2 class="srfm-form-title"><?php echo esc_html( $title ); ?></h2>
				<?php
			}
			?>
				<form method="post" enctype="multipart/form-data" id="srfm-form-<?php echo esc_attr( Helper::get_string_value( $id ) ); ?>" class="srfm-form <?php echo esc_attr( 'sureforms_form' === $post_type ? 'srfm-single-form ' : '' ); ?>"
				form-id="<?php echo esc_attr( Helper::get_string_value( $id ) ); ?>" after-submission="<?php echo esc_attr( $submission_action ); ?>" message-type="<?php echo esc_attr( $confirmation_type ? $confirmation_type : 'same page' ); ?>" success-url="<?php echo esc_attr( $success_url ? $success_url : '' ); ?>" ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'unique_validation_nonce' ) ); ?>"
				>
				<?php
					wp_nonce_field( 'srfm-form-submit', 'sureforms_form_submit' );
					$global_setting_options = get_option( 'srfm_security_settings_options' );
					$honeypot_spam          = is_array( $global_setting_options ) && isset( $global_setting_options['srfm_honeypot'] ) ? $global_setting_options['srfm_honeypot'] : '';

				if ( $is_page_break && 'none' !== $page_break_progress_type ) {
					do_action( 'srfm_page_break_header', $id );
				}
				?>

				<input type="hidden" value="<?php echo esc_attr( Helper::get_string_value( $id ) ); ?>" name="form-id">
				<input type="hidden" value="" name="srfm-sender-email-field" id="srfm-sender-email">
				<input type="hidden" value="<?php echo esc_attr( Helper::get_string_value( $is_page_break ) ); ?>" id="srfm-page-break">
				<?php if ( $honeypot_spam ) { ?>
					<input type="hidden" value="" name="srfm-honeypot-field">
					<?php
				}
					self::common_error_message( 'head' );
				if ( $is_page_break ) {
					do_action( 'srfm_page_break_pagination', $post, $id );
				} elseif ( ! apply_filters( 'srfm_use_custom_field_content', false ) ) {
					// phpcs:ignore
					echo $content;
					// phpcs:ignoreEnd
				}

				do_action( 'srfm_after_field_content', $post, $id );

				$captcha_container_hidden_class = ! empty( $google_captcha_site_key ) && ( 'v3-reCAPTCHA' === $recaptcha_version || 'v2-invisible' === $recaptcha_version ) ? 'srfm-display-none' : '';

				?>
					<?php if ( $should_show_submit_button && ! empty( $security_type ) && 'none' !== $security_type ) { ?>
						<div class="srfm-captcha-container <?php echo esc_attr( $captcha_container_hidden_class ); ?>">

						<?php

						if ( 'g-recaptcha' === $security_type ) {
							self::get_google_captcha_script( $recaptcha_version, $google_captcha_site_key );
						}

						if ( 'cf-turnstile' === $security_type ) {
							self::get_cf_turnstile_script( $srfm_cf_appearance_mode, $srfm_cf_turnstile_site_key );
						}

						if ( 'hcaptcha' === $security_type ) {
							self::get_h_captcha_script( $srfm_hcaptcha_site_key );
						}
						?>
						<div class="srfm-validation-error" id="captcha-error" style="display: none;"><?php echo esc_attr__( 'Please verify that you are not a robot.', 'sureforms' ); ?></div>
					</div>
					<?php } ?>

					<?php
					if ( $is_page_break ) {
						do_action( 'srfm_page_break_btn', $id );
					}
					$srfm_button_classes = apply_filters( 'srfm_add_button_classes', [ '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-frontend srfm-button srfm-submit-button', 'v3-reCAPTCHA' === $recaptcha_version ? ' g-recaptcha' : '' ] );
					?>

					<div class="srfm-submit-container <?php echo esc_attr( $is_page_break ? 'srfm-hide' : '' ); ?>" style="<?php echo ! $should_show_submit_button ? 'visibility:hidden;position:absolute;' : ''; ?>">
						<div style="width: <?php echo esc_attr( $full ? '100%' : '' ); ?>; text-align: <?php echo esc_attr( $submit_button_alignment ); ?>" class="wp-block-button">
						<?php do_action( 'srfm_before_submit_button', $id ); ?>
						<button style="<?php echo esc_attr( $full ? 'width: 100%;' : '' ); ?>" id="srfm-submit-btn" class="<?php echo esc_attr( implode( ' ', array_filter( $srfm_button_classes ) ) ); ?>"
						<?php if ( 'v3-reCAPTCHA' === $recaptcha_version ) { ?>
							data-callback="recaptchaCallback"
							data-error-callback="onGCaptchaV3Error"
							recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>"
							data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>"
						<?php } ?>
						>
							<div class="srfm-submit-wrap">
								<?php echo esc_html( $button_text ); ?>
							<div class="srfm-loader"></div>
							</div>
						</button>
						<?php do_action( 'srfm_after_submit_button', $id ); ?>
						</div>
					</div>
					<?php
		}
				self::common_error_message( 'footer' );
		?>
			</form>
			<div class="srfm-single-form srfm-success-box in-page">
				<div aria-live="polite" aria-atomic="true" role="alert" id="srfm-success-message-page-<?php echo esc_attr( Helper::get_string_value( $id ) ); ?>" class="srfm-success-box-description"></div>
			</div>
			</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a site key missing error message.
	 *
	 * @param string $provider_name Name of the captcha provider (e.g., HCaptcha, Google reCAPTCHA, Turnstile).
	 * @since 1.7.0
	 * @return void
	 */
	public static function render_missing_sitekey_error( $provider_name ) {
		$icon = Helper::fetch_svg( 'info_circle', '', 'aria-hidden="true"' );
		?>
		<p id="sitekey-error" class="srfm-common-error-message srfm-error-message" hidden="false">
			<?php echo wp_kses( $icon, Helper::$allowed_tags_svg ); ?>
			<span class="srfm-error-content">
				<?php
				echo esc_html(
					sprintf(
					/* translators: %s: Provider name like HCaptcha, Google reCAPTCHA, Turnstile */
						__( '%s sitekey is missing. Please contact your site administrator.', 'sureforms' ),
						$provider_name
					)
				);
				?>
			</span>
		</p>
		<?php
	}

	/**
	 * Generate HCaptcha script markup
	 *
	 * @param string $srfm_hcaptcha_site_key site key.
	 * @since 1.7.0
	 * @return void
	 */
	public static function get_h_captcha_script( $srfm_hcaptcha_site_key ) {
		if ( ! empty( $srfm_hcaptcha_site_key ) ) {
			// hCaptcha script.
				wp_enqueue_script( 'hcaptcha', 'https://js.hcaptcha.com/1/api.js', [], null, [ 'strategy' => 'defer' ] ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			?>
			<div id="srfm-hcaptcha-sitekey" data-callback="onSuccess" data-error-callback="onHCaptchaError" class="h-captcha" data-sitekey="<?php echo esc_attr( $srfm_hcaptcha_site_key ); ?>"></div>
				<?php
		} else {
			self::render_missing_sitekey_error( 'HCaptcha' );
		}
	}

	/**
	 * Generate Google Recaptcha script markup
	 *
	 * @param string $recaptcha_version reCAPTCHA version.
	 * @param string $google_captcha_site_key site key.
	 * @since 1.7.0
	 * @return void
	 */
	public static function get_google_captcha_script( $recaptcha_version, $google_captcha_site_key ) {

		if ( empty( $google_captcha_site_key ) ) {
			self::render_missing_sitekey_error( 'Google reCAPTCHA' );
			return;
		}

		if ( 'v2-checkbox' === $recaptcha_version ) {
			?>
			<?php
			wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], SRFM_VER, true );
			?>
		<div class='g-recaptcha' data-callback="onSuccess" data-error-callback="onGCaptchaV2CheckBoxError" recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>" data-sitekey="<?php echo esc_attr( strval( $google_captcha_site_key ) ); ?>" ></div>
		<?php } ?>

		<?php if ( 'v2-invisible' === $recaptcha_version ) { ?>
			<?php
			wp_enqueue_script( 'google-recaptcha-invisible', 'https://www.google.com/recaptcha/api.js?onload=recaptchaCallback&render=explicit', [ SRFM_SLUG . '-form-submit' ], SRFM_VER, true );
			?>
		<div class='g-recaptcha' recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>" data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>" data-size="invisible"></div>
		<?php } ?>

		<?php if ( 'v3-reCAPTCHA' === $recaptcha_version ) { ?>
			<?php
				wp_enqueue_script( // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- This is a third-party script, and specifying a version may lead to caching issues. Using null ensures the latest version is always loaded.
					'srfm-google-recaptchaV3',
					'https://www.google.com/recaptcha/api.js?render=' . $google_captcha_site_key,
					[],
					null,
					true
				);
			?>
			<?php
		}
	}

	/**
	 * Generate Cloudflare Turnstile script markup
	 *
	 * @param string $srfm_cf_appearance_mode appearance mode.
	 * @param string $srfm_cf_turnstile_site_key site key.
	 * @since 1.7.0
	 * @return void
	 */
	public static function get_cf_turnstile_script( $srfm_cf_appearance_mode, $srfm_cf_turnstile_site_key ) {
		if ( ! empty( $srfm_cf_turnstile_site_key ) ) {
			// Cloudflare Turnstile script.
			wp_enqueue_script( // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Third-party script; version not under our control.
				SRFM_SLUG . '-cf-turnstile',
				'https://challenges.cloudflare.com/turnstile/v0/api.js',
				[],
				null,
				[
					false,
					'defer' => true,
				]
			);
			?>
			<!-- The callback methods below are available on frontend.js. onTurnstileError displays and error in place of recaptcha dialog.  -->
		<div id="srfm-cf-sitekey" class="cf-turnstile" data-callback="onSuccess" data-error-callback="onTurnstileError" data-theme="<?php echo esc_attr( $srfm_cf_appearance_mode ); ?>" data-sitekey="<?php echo esc_attr( $srfm_cf_turnstile_site_key ); ?>"></div>
			<?php
		} else {
			self::render_missing_sitekey_error( 'Turnstile' );
		}
	}

	/**
	 * Generate common error message markup
	 *
	 * @param string $position position of the error message.
	 * @since 1.5.0
	 * @return void
	 */
	public static function common_error_message( $position = 'footer' ) {
		$icon    = Helper::fetch_svg( 'info_circle', '', 'aria-hidden="true"' );
		$classes = "srfm-common-error-message srfm-error-message srfm-{$position}-error";
		?>
		<p id="srfm-error-message" class="<?php echo esc_attr( $classes ); ?>" hidden="true"><?php echo wp_kses( $icon, Helper::$allowed_tags_svg ); ?><span class="srfm-error-content"><?php echo esc_html__( 'There was an error trying to submit your form. Please try again.', 'sureforms' ); ?></span></p>
		<?php
	}

	/**
	 * Generate form confirmation markup
	 *
	 * @param array<mixed> $form_data contains form data.
	 * @param array<mixed> $submission_data contains submission data.
	 * @since 0.0.3
	 * @return string|false
	 */
	public static function get_confirmation_markup( $form_data = [], $submission_data = [] ) {

		$confirmation_message = '';

		if ( empty( $form_data ) ) {
			return $confirmation_message;
		}

		$form_confirmation = isset( $form_data['form-id'] ) ?
			get_post_meta( Helper::get_integer_value( $form_data['form-id'] ), '_srfm_form_confirmation' ) : null;

		if ( ! is_array( $form_confirmation ) ) {
			return $confirmation_message;
		}

		$confirmation_data = is_array( $form_confirmation[0] ) && isset( $form_confirmation[0][0] ) ? $form_confirmation[0][0] : null;

		if ( is_array( $form_confirmation ) && isset( $confirmation_data['message'] ) && is_string( $confirmation_data['message'] ) ) {
			$confirmation_message = $confirmation_data['message'];
		}
		if ( empty( $submission_data ) ) {
			return $confirmation_message;
		}
		$smart_tags           = new Smart_Tags();
		$confirmation_message = $smart_tags->process_smart_tags( $confirmation_message, $submission_data, $form_data );

		$markup = wp_kses_post( apply_filters( 'srfm_after_submit_confirmation_message', $confirmation_message ) );

		if ( false !== strpos( $markup, 'src="image/svg+xml;base64' ) ) {
			// Handle Form Confirmation SVGs separately. We have planned to improve it in the future replacing it with image URL.
			$normalized_string = preg_replace( '/src="image\/svg\+xml;base64/', 'src="data:image/svg+xml;base64', $markup );

			if ( is_string( $normalized_string ) ) {
				$markup = $normalized_string;
			}
		}

		return $markup;
	}

	/**
	 * Get redirect url for form incase of different page or custom url is selected.
	 *
	 * @param array<mixed> $form_data contains form data.
	 * @param array<mixed> $submission_data contains submission data.
	 * @since 1.0.2
	 * @return string|false
	 */
	public static function get_redirect_url( $form_data = [], $submission_data = [] ) {
		$redirect_url = '';

		if ( empty( $form_data ) ) {
			return $redirect_url;
		}

		$form_confirmation = isset( $form_data['form-id'] ) ?
			get_post_meta( Helper::get_integer_value( $form_data['form-id'] ), '_srfm_form_confirmation' ) : null;

		if ( ! is_array( $form_confirmation ) ) {
			return $redirect_url;
		}

		$confirmation_data = is_array( $form_confirmation[0] ) && isset( $form_confirmation[0][0] ) ? $form_confirmation[0][0] : null;

		$page_url          = $confirmation_data['page_url'] ?? '';
		$custom_url        = $confirmation_data['custom_url'] ?? '';
		$confirmation_type = $confirmation_data['confirmation_type'] ?? '';
		if ( 'different page' === $confirmation_type ) {
			$redirect_url = esc_url( $page_url );
		} elseif ( 'custom url' === $confirmation_type ) {
			$redirect_url = esc_url( $custom_url );
		}

		if ( empty( $redirect_url ) ) {
			return $redirect_url;
		}

		if ( empty( $confirmation_data['enable_query_params'] ) || true !== $confirmation_data['enable_query_params'] ) {
			return $redirect_url;
		}

		if ( empty( $confirmation_data['query_params'] ) && ! is_array( $confirmation_data['query_params'] ) ) {
			return $redirect_url;
		}

		$query_params = [];
		foreach ( $confirmation_data['query_params'] as $params ) {
			if ( is_array( $params ) && ! empty( array_keys( $params ) ) && ! empty( array_values( $params ) ) ) {
				$query_params[ esc_attr( array_keys( $params )[0] ) ] = esc_attr( array_values( $params )[0] );
			}
		}

		$redirect_url = add_query_arg( $query_params, $redirect_url );

		if ( ! empty( $submission_data ) ) {
			$smart_tags = new Smart_Tags();
			// Adding upload_format_type = 'raw' to retrieve urls as comma separated values.
			$form_data['upload_format_type'] = 'raw';
			$redirect_url                    = html_entity_decode( $smart_tags->process_smart_tags( $redirect_url, $submission_data, $form_data ) );
		}

		return apply_filters( 'srfm_after_submit_redirect_url', $redirect_url );
	}
}
