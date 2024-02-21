<?php
/**
 * Form Single template.
 *
 * @package SureForms
 */

use SRFM\Inc\SRFM_Generate_Form_Markup;


$srfm_form_preview = '';

$srfm_form_preview_attr = isset( $_GET['form_preview'] ) ? sanitize_text_field( wp_unslash( $_GET['form_preview'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

if ( $srfm_form_preview_attr ) {
	$srfm_form_preview = filter_var( $srfm_form_preview_attr, FILTER_VALIDATE_BOOLEAN );
}

?>
<!DOCTYPE html>
<html class="srfm-html" <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
	<?php
		$srfm_custom_post_id                 = get_the_ID();
		$srfm_color1_val                     = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_color1', true );
		$srfm_bg_val                         = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_bg_image', true );
		$srfm_fontsize_val                   = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_fontsize', true );
		$srfm_submit_type_val                = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_submit_type', true );
		$srfm_thankyou_message_title         = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_thankyou_message_title', true );
		$srfm_thankyou_message_val           = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_thankyou_message', true );
		$srfm_submit_url_val                 = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_submit_url', true );
		$srfm_form_container_width           = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_form_container_width', true ) ? strval( get_post_meta( intval( $srfm_custom_post_id ), '_srfm_form_container_width', true ) ) : 650;
		$srfm_submit_button_text             = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_submit_button_text', true );
		$srfm_show_title_on_single_form_page = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_single_page_form_title', true ) ? strval( get_post_meta( intval( $srfm_custom_post_id ), '_srfm_single_page_form_title', true ) ) : '';

		$srfm_color_primary         = $srfm_color1_val ? strval( $srfm_color1_val ) : '#0284c7';
		$srfm_background_image_url  = $srfm_bg_val ? rawurldecode( strval( $srfm_bg_val ) ) : '';
		$srfm_form_font_size        = $srfm_fontsize_val ? $srfm_fontsize_val : '';
		$srfm_success_submit_type   = $srfm_submit_type_val ? strval( $srfm_submit_type_val ) : '';
		$srfm_success_message_title = $srfm_thankyou_message_title ? strval( $srfm_thankyou_message_title ) : '';
		$srfm_success_message       = $srfm_thankyou_message_val ? strval( $srfm_thankyou_message_val ) : '';
		$srfm_success_url           = $srfm_submit_url_val ? strval( $srfm_submit_url_val ) : '';

		// Submit button.
		$srfm_button_text      = $srfm_submit_button_text ? strval( $srfm_submit_button_text ) : '';
		$srfm_button_alignment = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_submit_alignment', true ) ? strval( get_post_meta( intval( $srfm_custom_post_id ), '_srfm_submit_alignment', true ) ) : '';

	if ( ! $srfm_form_preview ) {



		if ( 'justify' === $srfm_button_alignment ) {
			$srfm_full = true;
		} else {
			$srfm_full = false;
		}

		$srfm_recaptcha_version       = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_form_recaptcha', true ) ? strval( get_post_meta( intval( $srfm_custom_post_id ), '_srfm_form_recaptcha', true ) ) : '';
		$srfm_google_captcha_site_key = '';
		switch ( $srfm_recaptcha_version ) {
			case 'v2-checkbox':
				$srfm_google_captcha_site_key = ! empty( get_option( 'srfm_v2_checkbox_site' ) ) ? strval( get_option( 'srfm_v2_checkbox_site' ) ) : '';
				break;
			case 'v2-invisible':
				$srfm_google_captcha_site_key = ! empty( get_option( 'srfm_v2_invisible_site' ) ) ? strval( get_option( 'srfm_v2_invisible_site' ) ) : '';
				break;
			case 'v3-reCAPTCHA':
				$srfm_google_captcha_site_key = ! empty( get_option( 'srfm_v3_site' ) ) ? strval( get_option( 'srfm_v3_site' ) ) : '';
				break;
			default:
				break;
		}
		?>
		<style>
			#srfm-single-page-container {
				--srfm-form-container-width: 
					<?php
					echo esc_attr( $srfm_form_container_width . 'px' );
					?>
			}
		</style>
		<div id="srfm-single-page-container" class="srfm-single-page-container">
			<div class="srfm-page-banner" style="background-color: <?php echo esc_attr( $srfm_color_primary ); ?>">
				<?php if ( '1' !== $srfm_show_title_on_single_form_page ) : ?>
					<h1 class="srfm-single-banner-title"><?php echo esc_html( get_the_title() ); ?></h1>
				<?php endif; ?>
			</div>
			<div class="srfm-form-wrapper">
				<?php
					// phpcs:ignore
					echo SRFM_Generate_Form_Markup::get_form_markup( absint( $srfm_custom_post_id ), false,'', 'sureforms_form' );
					// phpcs:ignoreEnd
				?>
				<div id="srfm-success-message-page-<?php echo esc_attr( $srfm_custom_post_id ); ?>" style="height:0; opacity:0; min-height:0;" class="srfm-single-form srfm-success-box in-page"> 
					<i class="fa-regular fa-circle-check"></i>
					<article class="srfm-success-box-header">
						<?php echo esc_html( $srfm_success_message_title ); ?>
					</article>
					<article class="srfm-success-box-subtxt srfm-text-gray-900">
						<?php echo esc_html( $srfm_success_message ); ?>
					</article>
				</div>
					<?php
					if ( isset( $srfm_success_message ) && isset( $srfm_success_message_title ) ) {
						?>
					<div id="srfm-success-message-page-<?php echo esc_attr( $srfm_custom_post_id ); ?>" style="display:none;" class="srfm-single-form srfm-success-box"> 
						<i class="fa-regular fa-circle-check"></i>
						<article class="srfm-success-box-header">
							<?php echo esc_html( $srfm_success_message_title ); ?>
						</article>
						<article class="srfm-success-box-subtxt srfm-text-gray-900">
							<?php echo esc_html( $srfm_success_message ); ?>
						</article>
					</div>
						<?php
					}
					?>
					<p id="srfm-error-message" class="srfm-error-message" hidden="true"><?php echo esc_attr__( 'There was an error trying to submit your form. Please try again.', 'sureforms' ); ?></p>
						<?php
						$srfm_page_url  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
						$srfm_page_path = strval( wp_parse_url( $srfm_page_url, PHP_URL_PATH ) );
						$srfm_segments  = explode( '/', $srfm_page_path );
						$srfm_form_path = isset( $srfm_segments[1] ) ? $srfm_segments[1] : '';
						wp_footer();
						?>
		</div>
		</div>

		<?php } else { ?>
			<style>
				html.srfm-html {
					margin-top: 0 !important; /* Needs to be important to remove margin-top added by WordPress admin bar  */
				}

				body.single.single-sureforms_form {
					background-color: transparent;
				}

				.srfm-form-container ~ div { 
					display: none !important; /* Needs to be important to remove any blocks added by external plugins in wp_footer() */
				}
			</style>
			<?php

			show_admin_bar( false );

			// phpcs:ignore
			echo SRFM_Generate_Form_Markup::get_form_markup( absint( $srfm_custom_post_id ), false, 'sureforms_form' );
			// phpcs:ignoreEnd

			wp_footer();
		}
		?>
	</body>
</html>
