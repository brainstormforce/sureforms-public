<?php
/**
 * Form Single template.
 *
 * @package SureForms
 */

use SureForms\Inc\Generate_Form_Markup;

?>
<!DOCTYPE html>
<html class="srfm-html" <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
	<?php
		$custom_post_id                   = get_the_ID();
		$sureforms_color1_val             = get_post_meta( intval( $custom_post_id ), '_srfm_color1', true );
		$sureforms_textcolor1_val         = get_post_meta( intval( $custom_post_id ), '_srfm_textcolor1', true );
		$sureforms_color2_val             = get_post_meta( intval( $custom_post_id ), '_srfm_color2', true );
		$sureforms_bg_val                 = get_post_meta( intval( $custom_post_id ), '_srfm_bg', true );
		$sureforms_fontsize_val           = get_post_meta( intval( $custom_post_id ), '_srfm_fontsize', true );
		$sureforms_submit_type_val        = get_post_meta( intval( $custom_post_id ), '_srfm_submit_type', true );
		$sureforms_thankyou_message_title = get_post_meta( intval( $custom_post_id ), '_srfm_thankyou_message_title', true );
		$sureforms_thankyou_message_val   = get_post_meta( intval( $custom_post_id ), '_srfm_thankyou_message', true );
		$sureforms_submit_url_val         = get_post_meta( intval( $custom_post_id ), '_srfm_submit_url', true );
		$button_styling_from_theme_val    = get_post_meta( intval( $custom_post_id ), '_srfm_submit_styling_inherit_from_theme', true );
		$sureforms_form_class_name        = get_post_meta( intval( $custom_post_id ), '_srfm_form_class_name', true );
		$form_container_width             = get_post_meta( intval( $custom_post_id ), '_srfm_form_container_width', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_srfm_form_container_width', true ) ) : 650;
		$submit_button_text               = get_post_meta( intval( $custom_post_id ), '_srfm_submit_button_text', true );
		$show_title_on_single_form_page   = get_post_meta( intval( $custom_post_id ), '_srfm_single_page_form_title', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_srfm_single_page_form_title', true ) ) : '';

		$color_primary             = $sureforms_color1_val ? strval( $sureforms_color1_val ) : '#0284c7';
		$color_textprimary         = $sureforms_textcolor1_val ? strval( $sureforms_textcolor1_val ) : '#fff';
		$color_secondary           = $sureforms_color2_val ? strval( $sureforms_color2_val ) : '';
		$background_image_url      = $sureforms_bg_val ? rawurldecode( strval( $sureforms_bg_val ) ) : '';
		$form_font_size            = $sureforms_fontsize_val ? $sureforms_fontsize_val : '';
		$success_submit_type       = $sureforms_submit_type_val ? strval( $sureforms_submit_type_val ) : '';
		$success_message_title     = $sureforms_thankyou_message_title ? strval( $sureforms_thankyou_message_title ) : '';
		$success_message           = $sureforms_thankyou_message_val ? strval( $sureforms_thankyou_message_val ) : '';
		$success_url               = $sureforms_submit_url_val ? strval( $sureforms_submit_url_val ) : '';
		$button_styling_from_theme = $button_styling_from_theme_val ? strval( $button_styling_from_theme_val ) : '';

		// Submit button.
		$button_text      = $submit_button_text ? strval( $submit_button_text ) : '';
		$button_alignment = get_post_meta( intval( $custom_post_id ), '_srfm_submit_alignment', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_srfm_submit_alignment', true ) ) : '';
		$styling          = get_post_meta( intval( $custom_post_id ), '_srfm_form_styling', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_srfm_form_styling', true ) ) : '';

	if ( 'justify' === $button_alignment ) {
		$full = true;
	} else {
		$full = false;
	}

		$recaptcha_version       = get_post_meta( intval( $custom_post_id ), '_srfm_form_recaptcha', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_srfm_form_recaptcha', true ) ) : '';
		$google_captcha_site_key = '';
	switch ( $recaptcha_version ) {
		case 'v2-checkbox':
			$google_captcha_site_key = ! empty( get_option( 'sureforms_v2_checkbox_site' ) ) ? strval( get_option( 'sureforms_v2_checkbox_site' ) ) : '';
			break;
		case 'v2-invisible':
			$google_captcha_site_key = ! empty( get_option( 'sureforms_v2_invisible_site' ) ) ? strval( get_option( 'sureforms_v2_invisible_site' ) ) : '';
			break;
		case 'v3-reCAPTCHA':
			$google_captcha_site_key = ! empty( get_option( 'sureforms_v3_site' ) ) ? strval( get_option( 'sureforms_v3_site' ) ) : '';
			break;
		default:
			break;
	}
	?>
		<style>
			#srfm-single-form-page {
				--srfm-primary-text-color: 
					<?php
					echo empty( $color_textprimary ) && '' === $button_styling_from_theme ? '#ffffff' : esc_attr( $color_textprimary );
					?>
				;
				--srfm-form-container-width: 
					<?php
					echo esc_attr( $form_container_width . 'px' );
					?>
				
			}
		</style>
		<div id="srfm-single-page-container" class="srfm-single-page-container">
			<div class="srfm-page-banner" style="background-color: <?php echo esc_attr( $color_primary ); ?>">
				<?php if ( '1' !== $show_title_on_single_form_page ) : ?>
					<h1 class="srfm-single-banner-title"><?php echo esc_html( get_the_title() ); ?></h1>
				<?php endif; ?>
			</div>
			<div class="srfm-form-wrapper">
			<div class="srfm-form-inner-wrapper">
				<?php
					// phpcs:ignore
					echo Generate_Form_Markup::get_form_markup( absint( $custom_post_id ), false, 'sureforms_form' );
					// phpcs:ignoreEnd
				?>
				<div id="srfm-success-message-page-<?php echo esc_attr( $custom_post_id ); ?>" style="height:0; opacity:0; min-height:0;" class="srfm-single-form srfm-success-box in-page"> 
					<i class="fa-regular fa-circle-check"></i>
					<article class="srfm-success-box-header">
						<?php echo esc_html( $success_message_title ); ?>
					</article>
					<article class="srfm-success-box-subtxt srfm-text-gray-900">
						<?php echo esc_html( $success_message ); ?>
					</article>
				</div>
					<?php
					if ( isset( $success_message ) && isset( $success_message_title ) ) {
						?>
					<div id="srfm-success-message-page-<?php echo esc_attr( $custom_post_id ); ?>" style="display:none;" class="srfm-single-form srfm-success-box"> 
						<i class="fa-regular fa-circle-check"></i>
						<article class="srfm-success-box-header">
							<?php echo esc_html( $success_message_title ); ?>
						</article>
						<article class="srfm-success-box-subtxt srfm-text-gray-900">
							<?php echo esc_html( $success_message ); ?>
						</article>
					</div>
						<?php
					}
					?>
					<p id="srfm-error-message" class="srfm-error-message" hidden="true"><?php echo esc_attr__( 'There was an error trying to submit your form. Please try again.', 'sureforms' ); ?></p>
						<?php
						$page_url  = $_SERVER['REQUEST_URI'];
						$page_path = strval( wp_parse_url( $page_url, PHP_URL_PATH ) );
						$segments  = explode( '/', $page_path );
						$form_path = isset( $segments[1] ) ? $segments[1] : '';
						wp_footer();
						?>
			</div>
		</div>
		</div>
	</body>
</html>
