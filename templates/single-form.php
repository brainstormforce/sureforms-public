<?php
/**
 * Form Single template.
 *
 * @package SureForms
 */

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
		$styling                          = get_post_meta( intval( $custom_post_id ), '_srfm_form_styling', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_srfm_form_styling', true ) ) : '';
		$form_container_width             = get_post_meta( intval( $custom_post_id ), '_srfm_form_container_width', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_srfm_form_container_width', true ) ) : 650;
		$submit_button_text               = get_post_meta( intval( $custom_post_id ), '_srfm_submit_button_text', true );

		wp_enqueue_style( 'srfm-tailwind-styles', SUREFORMS_URL . 'assets/build/tailwind_frontend_styles.css', [], SUREFORMS_VER, 'all' );

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
				--srfm-secondary-color: 
				<?php
					echo esc_attr( $color_secondary );
				?>
					;
				--srfm-primary-color: 
				<?php
					echo empty( $color_primary ) && '' === $button_styling_from_theme ? '#0284C7' : esc_attr( $color_primary );
				?>
				;
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
		<div id="srfm-single-form-page">
			<div class="srfm-page-banner" style="background-color: <?php echo esc_attr( $color_primary ); ?>">
				<h2 class="srfm-page-banner-title" ><?php echo esc_attr( get_the_Title() ); ?> </h2>
			</div>
			<div>
			<form method="post" id="srfm-form-<?php echo esc_attr( $custom_post_id ); ?>" class="srfm-form <?php echo esc_attr( 'classic' === $styling ? 'srfm-form-style-classic ' : '' ); ?> <?php
			// phpcs:ignore
			// echo esc_attr( $classname );
			?>
			<?php echo esc_attr( '' !== $background_image_url ? 'srfm-form-background' : '' ); ?>"
				form-id="<?php echo esc_attr( $custom_post_id ); ?>" message-type="<?php echo esc_attr( $success_submit_type ? $success_submit_type : 'message' ); ?>" success-url="<?php echo esc_attr( $success_url ? $success_url : '' ); ?>" ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" nonce="<?php echo esc_attr( wp_create_nonce( 'unique_validation_nonce' ) ); ?>"
				>
				<?php
					wp_nonce_field( 'srfm-form-submit', 'sureforms_form_submit' );
					$honeypot_spam = get_option( 'honeypot' );
				?>
				<input type="hidden" value="<?php echo esc_attr( $custom_post_id ); ?>" name="form-id">
				<input type="hidden" value="" name="srfm-sender-email-field" id="srfm-sender-email">
				<?php if ( '1' === $honeypot_spam ) : ?>
					<input type="hidden" value="" name="srfm-honeypot-field">
				<?php endif; ?>
				<?php
					// phpcs:ignore
					while ( have_posts() ) :
					the_post();
					the_content();
					endwhile;
					// phpcs:ignoreEnd
				?>
				<?php // phpcs:ignore // if ( true ) : ?>
					<div class="srfm-submit-container <?php echo '#0284c7' !== $color_primary ? 'srfm-frontend-inputs-holder' : ''; ?>">
					<div style="width: <?php echo esc_attr( $full ? '100%;' : ';' ); ?> text-align: <?php echo esc_attr( $button_alignment ? $button_alignment : 'left' ); ?>" class="<?php echo 'inherit' === $styling ? 'wp-block-button' : ''; ?>">
						<?php if ( '' !== $google_captcha_site_key ) : ?>
							<?php if ( 'v2-checkbox' === $recaptcha_version ) : ?>
							<div class='g-recaptcha' data-sitekey="<?php echo esc_attr( strval( $google_captcha_site_key ) ); ?>" ></div>
								<?php
								switch ( $styling ) {
									case 'inherit':
										// @phpstan-ignore-next-line
										echo '<button style="margin-top: 24px; width: ' . esc_attr( $full ? '100%' : '' ) . ';" type="submit" id="srfm-submit-btn" class="srfm-button ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '' ) . '">
										<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
											' . esc_html( $button_text ) . '
											<div style="display: none;" class="srfm-loader"></div>
										</div>
									</button>';
										break;
									case 'classic':
										// @phpstan-ignore-next-line
										echo '<button style="color: #ffffff; width: ' . esc_attr( $full ? '100%' : '' ) . ';' . ( '#0284c7' === $color_primary && 'inherit' === $styling ? 'background-color: #0284C7; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" id="srfm-submit-btn" class="srfm-button !srfm-mt-[24px] !srfm-rounded-md !srfm-px-3.5 !srfm-py-2 !srfm-text-sm !font-semibold text-srfm_primary_text_color !srfm-shadow-sm hover:!opacity-80 ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '!srfm-bg-srfm_primary_color' ) . '">
										<div class="!srfm-flex !srfm-gap-[6px] !srfm-justify-center !srfm-items-center !srfm-min-h-[24px]">
											' . esc_html( $button_text ) . '
											<div style="display: none;" class="srfm-loader"></div>
										</div>
										</button>';
										break;
									default:
										echo '<button style="margin-top: 24px; width: ' . esc_attr( $full ? '100%' : '' ) . ';' . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" id="srfm-submit-btn" class="srfm-button ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '' ) . '">
									<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
										' . esc_html( $button_text ) . '
										<div style="display: none;" class="srfm-loader"></div>
									</div>
								</button>';
										break;
								};
								?>
						<?php endif; ?>
							<?php if ( 'v2-invisible' === $recaptcha_version ) : ?>
								<?php
								switch ( $styling ) {
									case 'inherit':
										// @phpstan-ignore-next-line
										echo '<button style="width: ' . esc_attr( $full ? '100%;' : '' ) . ';' . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" class="srfm-button ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '' ) . '" recaptcha-type="' . esc_attr( $recaptcha_version ) . '" data-sitekey="' . esc_attr( $google_captcha_site_key ) . '" id="srfm-submit-btn">
											<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
												' . esc_html( $button_text ) . '
												<div style="display: none;" class="srfm-loader"></div>
											</div>
										</button>';
										break;
									case 'classic':
										// @phpstan-ignore-next-line
										echo '<button style="color: #ffffff; width: ' . esc_attr( $full ? '100%;' : '' ) . ';' . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" class="srfm-button !srfm-rounded-md !srfm-px-3.5 !srfm-py-2 !srfm-text-sm !font-semibold text-srfm_primary_text_color !srfm-shadow-sm hover:!opacity-80 ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '!srfm-bg-srfm_primary_color' ) . '" recaptcha-type="' . esc_attr( $recaptcha_version ) . '" data-sitekey="' . esc_attr( $google_captcha_site_key ) . '" id="srfm-submit-btn">
											<div class="!srfm-flex !srfm-gap-[6px] !srfm-justify-center !srfm-items-center !srfm-min-h-[24px]">
												' . esc_html( $button_text ) . '
												<div style="display: none;" class="srfm-loader"></div>
											</div>
										</button>';
										break;
									default:
										echo '<button style="width: ' . esc_attr( $full ? '100%;' : '' ) . ';' . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" class="srfm-button ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '' ) . '" recaptcha-type="' . esc_attr( $recaptcha_version ) . '" data-sitekey="' . esc_attr( $google_captcha_site_key ) . '" id="srfm-submit-btn">
											<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
												' . esc_html( $button_text ) . '
												<div style="display: none;" class="srfm-loader"></div>
											</div>
										</button>';
										break;
								};
								?>
							<?php endif; ?>
							<?php if ( 'v3-reCAPTCHA' === $recaptcha_version ) : ?>
								<?php wp_enqueue_script( 'srfm-google-recaptchaV3', 'https://www.google.com/recaptcha/api.js?render=' . esc_js( $google_captcha_site_key ), array(), SUREFORMS_VER, true ); ?>
									<?php
									switch ( $styling ) {
										case 'inherit':
											// @phpstan-ignore-next-line
											echo '<button style="width: ' . ( esc_attr( $full ? '100%;' : ';' ) ) . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" class="g-recaptcha srfm-button ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '' ) . '" recaptcha-type="' . esc_attr( $recaptcha_version ) . '" data-sitekey="' . esc_attr( $google_captcha_site_key ) . '" id="srfm-submit-btn">
											<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
												' . esc_html( $button_text ) . '
												<div style="display: none;" class="srfm-loader"></div>
											</div>
										</button>';
											break;
										case 'classic':
											// @phpstan-ignore-next-line
											echo '<button style="color: #ffffff; width: ' . ( esc_attr( $full ? '100%;' : ';' ) ) . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" class="g-recaptcha srfm-button !srfm-rounded-md !srfm-px-3.5 !srfm-py-2 !srfm-text-sm !font-semibold text-srfm_primary_text_color !srfm-shadow-sm hover:!opacity-80 ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '!srfm-bg-srfm_primary_color' ) . '" recaptcha-type="' . esc_attr( $recaptcha_version ) . '" data-sitekey="' . esc_attr( $google_captcha_site_key ) . '" id="srfm-submit-btn">
											<div class="!srfm-flex !srfm-gap-[6px] !srfm-justify-center !srfm-items-center !srfm-min-h-[24px]">
												' . esc_html( $button_text ) . '
												<div style="display: none;" class="srfm-loader"></div>
											</div>
										</button>';
											break;
										default:
											echo '<button style="width: ' . ( esc_attr( $full ? '100%;' : ';' ) ) . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" class="g-recaptcha srfm-button ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '' ) . '" recaptcha-type="' . esc_attr( $recaptcha_version ) . '" data-sitekey="' . esc_attr( $google_captcha_site_key ) . '" id="srfm-submit-btn">
											<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
												' . esc_html( $button_text ) . '
												<div style="display: none;" class="srfm-loader"></div>
											</div>
										</button>';
											break;
									};
									?>
						<?php endif; ?>
						<?php endif; ?>
						<?php if ( 'none' === $recaptcha_version || '' === $recaptcha_version ) : ?>
							<?php
							switch ( $styling ) {
								case 'inherit':
									// @phpstan-ignore-next-line
									echo '<button style="width: ' . ( esc_attr( $full ? '100%;' : ';' ) ) . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" id="srfm-submit-btn" class="srfm-button ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '' ) . '">
									<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
									' . esc_html( $button_text ) . '
										<div style="display: none;" class="srfm-loader"></div>
									</div>
								</button>';
									break;
								case 'classic':
									// @phpstan-ignore-next-line
									echo '<button style="color: #ffffff; width: ' . ( esc_attr( $full ? '100%;' : ';' ) ) . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" id="srfm-submit-btn" class="srfm-button !srfm-rounded-md !srfm-px-3.5 !srfm-py-2 !srfm-text-sm !font-semibold text-srfm_primary_text_color !srfm-shadow-sm hover:!opacity-80 ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '!srfm-bg-srfm_primary_color' ) . '">
									<div class="!srfm-flex !srfm-gap-[6px] !srfm-justify-center !srfm-items-center !srfm-min-h-[24px]">
									' . esc_html( $button_text ) . '
									<div style="display: none;" class="srfm-loader"></div>
									</div>
								</button>';
									break;
								default:
									echo '<button style="width: ' . ( esc_attr( $full ? '100%;' : ';' ) ) . ( empty( $color_primary ) && 'inherit' === $styling ? 'background-color: #0284C7; color: #ffffff; border: none; padding: 13px 25px; border-radius: 4px;' : '' ) . '" type="submit" id="srfm-submit-btn" class="srfm-button ' . ( 'inherit' === $styling ? 'wp-block-button__link' : '' ) . '">
									<div style="display: flex; gap: 6px; align-items: center; min-height: 24px; justify-content: center;">
									' . esc_html( $button_text ) . '
									<div style="display: none;" class="srfm-loader"></div>
									</div>
								</button>';
									break;
							};
							?>
						<?php endif; ?>
					</div>
					</div>
				<?php // phpcs:ignore // endif; ?>
				<p id="srfm-error-message" class="srfm-error-message" hidden="true"><?php echo esc_html__( 'There was an error trying to submit your form. Please try again.', 'sureforms' ); ?></p>
			</form>
			<div id="srfm-success-message-page-<?php echo esc_attr( $id ); ?>" style="height:0; opacity:0; min-height:0;" class="srfm-single-form srfm-success-box in-page"> 
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
						<style>
				<?php echo esc_attr( '#srfm-form-' . $id . ', #srfm-success-message-page-' . $id ); ?> {
					--srfm-secondary-color: 
					<?php
						echo esc_attr( $color_secondary );
					?>
						;
					--srfm-primary-color: 
					<?php
						echo esc_attr( $color_primary );
					?>
						;
					--srfm-primary-text-color:
					<?php
						echo esc_attr( $color_text_primary );
					?>
						;
					font-size: <?php echo esc_attr( isset( $form_font_size ) ? $form_font_size . 'px;' : '' ); ?>

				}
				<?php echo esc_attr( '#srfm-form-' . $id ); ?> {
					background-image: url('<?php echo esc_url( isset( $background_image_url ) ? $background_image_url : '' ); ?>'); 

				}
			</style>
			<?php
			// phpcs:ignore
			// if ( ! empty( $styling ) && 'classic' === $styling ) {
				wp_enqueue_style( 'srfm-tailwind-styles', SUREFORMS_URL . 'assets/build/tailwind_frontend_styles.css', [], SUREFORMS_VER, 'all' );
			// }
			?>
			</div>
		</div>
	</body>
</html>
