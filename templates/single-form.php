<?php
/**
 * Form Single template.
 *
 * @package SureForms
 */

?>
<!DOCTYPE html>
<html class="sureforms-html" <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
	<?php
		$custom_post_id                 = get_the_ID();
		$sureforms_color1_val           = get_post_meta( intval( $custom_post_id ), '_sureforms_color1', true );
		$sureforms_textcolor1_val       = get_post_meta( intval( $custom_post_id ), '_sureforms_textcolor1', true );
		$sureforms_color2_val           = get_post_meta( intval( $custom_post_id ), '_sureforms_color2', true );
		$sureforms_bg_val               = get_post_meta( intval( $custom_post_id ), '_sureforms_bg', true );
		$sureforms_fontsize_val         = get_post_meta( intval( $custom_post_id ), '_sureforms_fontsize', true );
		$sureforms_submit_type_val      = get_post_meta( intval( $custom_post_id ), '_sureforms_submit_type', true );
		$sureforms_thankyou_message_val = get_post_meta( intval( $custom_post_id ), '_sureforms_thankyou_message', true );
		$sureforms_submit_url_val       = get_post_meta( intval( $custom_post_id ), '_sureforms_submit_url', true );
		$button_styling_from_theme_val  = get_post_meta( intval( $custom_post_id ), '_sureforms_submit_styling_inherit_from_theme', true );
		$sureforms_form_class_name      = get_post_meta( intval( $custom_post_id ), '_sureforms_form_class_name', true );

		$color_primary             = $sureforms_color1_val ? strval( $sureforms_color1_val ) : '';
		$color_textprimary         = $sureforms_textcolor1_val ? strval( $sureforms_textcolor1_val ) : '';
		$color_secondary           = $sureforms_color2_val ? strval( $sureforms_color2_val ) : '';
		$background_image_url      = $sureforms_bg_val ? rawurldecode( strval( $sureforms_bg_val ) ) : '';
		$form_font_size            = $sureforms_fontsize_val ? $sureforms_fontsize_val : '';
		$success_submit_type       = $sureforms_submit_type_val ? strval( $sureforms_submit_type_val ) : '';
		$success_message           = $sureforms_thankyou_message_val ? strval( $sureforms_thankyou_message_val ) : '';
		$success_url               = $sureforms_submit_url_val ? strval( $sureforms_submit_url_val ) : '';
		$button_styling_from_theme = $button_styling_from_theme_val ? strval( $button_styling_from_theme_val ) : '';
	?>
		<style>
			#sforms-single-form-page {
				--secondary-color: 
				<?php
					echo esc_attr( $color_secondary );
				?>
					;
				--primary-color: 
				<?php
					echo empty( $color_primary ) && '' === $button_styling_from_theme ? '#0284C7' : esc_attr( $color_primary );
				?>
				;
				--primary-textcolor: 
					<?php
					echo empty( $color_textprimary ) && '' === $button_styling_from_theme ? '#ffffff' : esc_attr( $color_textprimary );
					?>

			}
		</style>
		<div id="sforms-single-form-page">
			<div class="sureforms-page-banner" style="background-color: <?php echo esc_attr( $color_primary ); ?>">
				<h2 class="sureforms-page-banner-title" ><?php echo esc_attr( get_the_Title() ); ?> </h2>
			</div>
			<div>
				<form method="post" id="sureforms-form-<?php echo esc_attr( $custom_post_id ); ?>" class="sureforms-form sureforms-single-form sf-form-style-classic <?php echo esc_attr( '' !== $background_image_url ? 'sureforms-form-background' : '' ); ?>" 
				form-id="<?php echo esc_attr( $custom_post_id ); ?>" message-type="<?php echo esc_attr( $success_submit_type ); ?>" success-url="<?php echo esc_attr( $success_url ); ?>" ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" nonce="<?php echo esc_attr( wp_create_nonce( 'unique_validation_nonce' ) ); ?>"
				style="background-image: url('<?php echo esc_url( $background_image_url ); ?>'); padding: 2rem; font-size:<?php echo esc_attr( $form_font_size . 'px;' ); ?> ">
				<?php
					wp_nonce_field( 'sureforms-form-submit', 'sureforms_form_submit' );
					$honeypot_spam = get_option( 'honeypot' );
				?>
					<input type="hidden" value="<?php echo esc_attr( $custom_post_id ); ?>" name="form-id">
					<?php if ( '1' === $honeypot_spam ) : ?>
						<input type="hidden" value="" name="sureforms-honeypot-field">
					<?php endif; ?>		
					<?php
					while ( have_posts() ) :
						the_post();
						the_content();
					endwhile;
					?>
				</form>
				<?php
				if ( isset( $success_message ) ) {
					?>
				<div id="sureforms-success-message-page-<?php echo esc_attr( $custom_post_id ); ?>" style="display:none;" class="sureforms-single-form sureforms-success-box"> 
					<i class="fa-regular fa-circle-check"></i>
					<article class="sureforms-success-box-header">
						<?php esc_html_e( 'Thank you', 'sureforms' ); ?>
					</article>
					<article class="sureforms-success-box-subtxt text-gray-900">
						<?php echo esc_html( $success_message ); ?>
					</article>
				</div>
					<?php
				}
				?>
				<p id="sureforms-error-message" class="sureforms-error-message" hidden="true"><?php echo esc_attr__( 'There was an error trying to submit your form. Please try again.', 'sureforms' ); ?></p>
					<?php
					$page_url  = $_SERVER['REQUEST_URI'];
					$page_path = strval( wp_parse_url( $page_url, PHP_URL_PATH ) );
					$segments  = explode( '/', $page_path );
					$form_path = isset( $segments[1] ) ? $segments[1] : '';
					wp_footer();
					?>
			</div>
		</div>
	</body>
</html>
