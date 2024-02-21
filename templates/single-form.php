<?php
/**
 * Form Single template.
 *
 * @package SureForms
 */

use SureForms\Inc\Generate_Form_Markup;


$form_preview = '';

$form_preview_attr = isset( $_GET['form_preview'] ) ? sanitize_text_field( wp_unslash( $_GET['form_preview'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

if ( $form_preview_attr ) {
	$form_preview = filter_var( $form_preview_attr, FILTER_VALIDATE_BOOLEAN );
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
		$custom_post_id                   = get_the_ID();
		$sureforms_color1_val             = get_post_meta( intval( $custom_post_id ), '_srfm_color1', true );
		$sureforms_bg_val                 = get_post_meta( intval( $custom_post_id ), '_srfm_bg_image', true );
		$sureforms_fontsize_val           = get_post_meta( intval( $custom_post_id ), '_srfm_fontsize', true );
		$sureforms_submit_type_val        = get_post_meta( intval( $custom_post_id ), '_srfm_submit_type', true );
		$sureforms_thankyou_message_title = get_post_meta( intval( $custom_post_id ), '_srfm_thankyou_message_title', true );
		$sureforms_thankyou_message_val   = get_post_meta( intval( $custom_post_id ), '_srfm_thankyou_message', true );
		$sureforms_submit_url_val         = get_post_meta( intval( $custom_post_id ), '_srfm_submit_url', true );
		$form_container_width             = get_post_meta( intval( $custom_post_id ), '_srfm_form_container_width', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_srfm_form_container_width', true ) ) : 650;
		$submit_button_text               = get_post_meta( intval( $custom_post_id ), '_srfm_submit_button_text', true );
		$show_title_on_single_form_page   = get_post_meta( intval( $custom_post_id ), '_srfm_single_page_form_title', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_srfm_single_page_form_title', true ) ) : '';

		$color_primary         = $sureforms_color1_val ? strval( $sureforms_color1_val ) : '#0284c7';
		$background_image_url  = $sureforms_bg_val ? rawurldecode( strval( $sureforms_bg_val ) ) : '';
		$form_font_size        = $sureforms_fontsize_val ? $sureforms_fontsize_val : '';
		$success_submit_type   = $sureforms_submit_type_val ? strval( $sureforms_submit_type_val ) : '';
		$success_message_title = $sureforms_thankyou_message_title ? strval( $sureforms_thankyou_message_title ) : '';
		$success_message       = $sureforms_thankyou_message_val ? strval( $sureforms_thankyou_message_val ) : '';
		$success_url           = $sureforms_submit_url_val ? strval( $sureforms_submit_url_val ) : '';

		// Submit button.
		$button_text      = $submit_button_text ? strval( $submit_button_text ) : '';
		$button_alignment = get_post_meta( intval( $custom_post_id ), '_srfm_submit_alignment', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_srfm_submit_alignment', true ) ) : '';

	if ( ! $form_preview ) {



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
			#srfm-single-page-container {
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
				<?php
					// phpcs:ignore
					echo Generate_Form_Markup::get_form_markup( absint( $custom_post_id ), false,'', 'sureforms_form' );
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
						$page_url  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
						$page_path = strval( wp_parse_url( $page_url, PHP_URL_PATH ) );
						$segments  = explode( '/', $page_path );
						$form_path = isset( $segments[1] ) ? $segments[1] : '';
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
			echo Generate_Form_Markup::get_form_markup( absint( $custom_post_id ), false, 'sureforms_form' );
			// phpcs:ignoreEnd

			wp_footer();
		}
		?>
	</body>
</html>
