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
		$custom_post_id       = get_the_ID();
		$color_primary        = get_post_meta( intval( $custom_post_id ), '_sureforms_color1', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_sureforms_color1', true ) ) : '';
		$color_secondary      = get_post_meta( intval( $custom_post_id ), '_sureforms_color2', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_sureforms_color2', true ) ) : '';
		$background_image_url = get_post_meta( intval( $custom_post_id ), '_sureforms_bg', true ) ? rawurldecode( strval( get_post_meta( intval( $custom_post_id ), '_sureforms_bg', true ) ) ) : '';
		$form_font_size       = get_post_meta( intval( $custom_post_id ), '_sureforms_fontsize', true ) ? get_post_meta( intval( $custom_post_id ), '_sureforms_fontsize', true ) : '';
		$success_message      = get_post_meta( intval( $custom_post_id ), '_sureforms_thankyou_message', true ) ? strval( get_post_meta( intval( $custom_post_id ), '_sureforms_thankyou_message', true ) ) : '';
	?>
	<form method="post" action="../../sureforms-submit.php" id="sureforms-form-<?php echo esc_attr( $custom_post_id ); ?>" class="sureforms-form sureforms-single-form <?php echo esc_attr( '' !== $background_image_url ? 'sureforms-form-background' : '' ); ?>" style="background-image: url('<?php echo esc_url( $background_image_url ); ?>'); padding: 2rem; font-size:<?php echo esc_attr( $form_font_size . 'px;' ); ?> ">
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
	<h2 id="sureforms-success-message" hidden="true" style="text-align:center"><?php echo esc_html( $success_message ); ?></h2>
	<h2 id="sureforms-error-message" hidden="true" style="text-align:center"><?php echo esc_attr__( 'Error Submiting Form', 'sureforms' ); ?></h2>
	<?php
	$page_url  = $_SERVER['REQUEST_URI'];
	$page_path = strval( wp_parse_url( $page_url, PHP_URL_PATH ) );
	$segments  = explode( '/', $page_path );
	$form_path = isset( $segments[1] ) ? $segments[1] : '';
	wp_footer();
	?>
	<script type="text/javascript">
				<?php if ( isset( $form_path ) && 'sf-form' !== $form_path ) { ?>
					document.addEventListener('DOMContentLoaded', function() {
					// Capture the form submission event
					var form = document.querySelector('#sureforms-form-<?php echo esc_attr( $custom_post_id ); ?>');
					form.addEventListener('submit', function(e) {
						e.preventDefault(); // Prevent the default form submission
						document.querySelector(".sureforms-loader").removeAttribute("style");
						var formData = new FormData(form);
						fetch('/wp-json/sureforms/v1/submit-form', {
							method: 'POST',
							body: formData
							})
							.then(response => {
							if (response.ok) {
							// Handle the successful response
							document.querySelector('.sureforms-loader').setAttribute('style', 'display: none');
							document.querySelector('#sureforms-success-message').removeAttribute('hidden');
							setTimeout(() => {
								document.querySelector('#sureforms-success-message').setAttribute('hidden', 'true');
							}, 2000);
							} else {
							// Handle the error response
							document.querySelector('.sureforms-loader').setAttribute('style', 'display: none');
							document.querySelector('#sureforms-error-message').removeAttribute('hidden');
							console.error('Error:', response.statusText);
							}
							})
							.catch(error => {
							// Handle the network error
							document.querySelector('.sureforms-loader').setAttribute('style', 'display: none');
							document.querySelector('#sureforms-error-message').removeAttribute('hidden');
							console.error('Network Error:', error);
							});
					});
					});
				<?php } ?>
			</script>
</body>
</html>
