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
		$form_container_width             = get_post_meta( intval( $custom_post_id ), '_srfm_form_container_width', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_srfm_form_container_width', true ) ) : 650;

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
				<form method="post" id="srfm-form-<?php echo esc_attr( $custom_post_id ); ?>" class="srfm-form srfm-single-form srfm-form-style-classic <?php echo esc_attr( '' !== $background_image_url ? 'srfm-form-background' : '' ); ?><?php echo esc_attr( $sureforms_form_class_name ); ?>" 
				form-id="<?php echo esc_attr( $custom_post_id ); ?>" message-type="<?php echo esc_attr( $success_submit_type ); ?>" success-url="<?php echo esc_attr( $success_url ); ?>" ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" nonce="<?php echo esc_attr( wp_create_nonce( 'unique_validation_nonce' ) ); ?>"
				style="background-image: url('<?php echo esc_url( $background_image_url ); ?>'); padding: 2rem; font-size:<?php echo esc_attr( $form_font_size . 'px;' ); ?> ">
				<?php
					$honeypot_spam = get_option( 'honeypot' );
				?>
					<input type="hidden" value="<?php echo esc_attr( $custom_post_id ); ?>" name="form-id">
					<?php if ( '1' === $honeypot_spam ) : ?>
						<input type="hidden" value="" name="srfm-honeypot-field">
					<?php endif; ?>		
					<?php
					while ( have_posts() ) :
						the_post();
						the_content();
					endwhile;
					?>
				</form>
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
	</body>
</html>
