<?php
/**
 * Form Single template.
 *
 * @package SureForms
 */

use SRFM\Inc\Generate_Form_Markup;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$srfm_custom_post_id = get_the_ID();
$srfm_form_preview   = isset( $_GET['form_preview'] ) ? boolval( sanitize_text_field( wp_unslash( $_GET['form_preview'] ) ) ) : false;  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$srfm_live_mode_data = Helper::get_instant_form_live_data();

?>
<!DOCTYPE html>
<html class="srfm-html" <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<?php wp_head(); ?>
	<style>
		<?php
		echo wp_kses_post( Helper::get_meta_value( $srfm_custom_post_id, '_srfm_form_custom_css' ) );

		if ( $srfm_live_mode_data ) {
			?>
			html {
				margin: 0 !important;
				opacity: 0;
				transition: all 0.5s ease-in-out;
			}
			#wpadminbar {
				display: none;
			}
			<?php
		}
		?>
	</style>
</head>

<body <?php body_class(); ?>>
	<?php
	$form_styling = Helper::get_meta_value(
		$srfm_custom_post_id,
		'_srfm_forms_styling',
		true,
		[
			'primary_color'           => '#0C78FB',
			'text_color'              => '#1E1E1E',
			'text_color_on_primary'   => '#FFFFFF',
			'field_spacing'           => 'small',
			'submit_button_alignment' => 'left',
		]
	);

	$instant_form_settings         = ! empty( $srfm_live_mode_data ) ? $srfm_live_mode_data : Helper::get_meta_value( absint( $srfm_custom_post_id ), '_srfm_instant_form_settings' );
	$site_logo                     = $instant_form_settings['site_logo'];
	$cover_type                    = $instant_form_settings['cover_type'];
	$cover_color                   = $instant_form_settings['cover_color'];
	$cover_image                   = $instant_form_settings['cover_image'];
	$enable_instant_form           = $instant_form_settings['enable_instant_form'];
	$form_container_width          = $instant_form_settings['form_container_width'];
	$single_page_form_title        = $instant_form_settings['single_page_form_title'];
	$use_banner_as_page_background = $instant_form_settings['use_banner_as_page_background'];

	$srfm_cover_image_url = $cover_image ? rawurldecode( strval( $cover_image ) ) : '';

	if ( ! $srfm_form_preview ) {

		$selector = '.single-sureforms_form .srfm-single-page-container';

		if ( ! $use_banner_as_page_background ) {
			$selector = $selector . ' .srfm-page-banner';
		}

		?>
		<style>
			#srfm-single-page-container {
				--srfm-form-container-width: <?php echo esc_attr( $form_container_width . 'px' ); ?>;
			}
			<?php echo esc_html( $selector ); ?> {
				<?php if ( 'image' === $cover_type && ! empty( $srfm_cover_image_url ) ) : ?>
					background-image: url(<?php echo esc_attr( $srfm_cover_image_url ); ?> );
					background-position: center;
					background-size: cover;
					background-repeat: no-repeat;
				<?php else : ?>
					background-color: <?php echo esc_attr( $cover_color ); ?>;
				<?php endif; ?>
			}
		</style>
		<div id="srfm-single-page-container" class="srfm-single-page-container <?php echo ! ! $single_page_form_title ? 'has-form-title' : ''; ?>">
			<div class="srfm-page-banner">
				<?php
				if ( ! empty( $site_logo ) ) {
					?>
					<img class="srfm-site-logo" src="<?php echo esc_url( $site_logo ); ?>">
					<?php
				}

				if ( ! empty( $single_page_form_title ) ) {
					?>
					<h1 class="srfm-single-banner-title"><?php echo esc_html( get_the_title() ); ?></h1>
					<?php
				}

				if ( empty( $enable_instant_form ) ) {
					?>
					<div class="srfm-form-status-badge"><?php esc_html_e( 'Unpublished', 'sureforms' ); ?></div>
					<?php
				}
				?>
			</div>
			<div class="srfm-form-wrapper">
				<?php
				// phpcs:ignore
				echo Generate_Form_Markup::get_form_markup( absint( $srfm_custom_post_id ), false, '', 'sureforms_form' );
				// phpcs:ignoreEnd
				?>
			</div>
		</div>
	<?php } else { ?>
		<style>
			html.srfm-html {
				margin-top: 0 !important;
				/* Needs to be important to remove margin-top added by WordPress admin bar  */
			}

			body.single.single-sureforms_form {
				background-color: transparent;
			}

			.srfm-form-container~div,
			.srfm-instant-form-wrn-ctn {
				display: none !important;
				/* Needs to be important to remove any blocks added by external plugins in wp_footer() */
			}
		</style>
		<?php

		show_admin_bar( false );

		// phpcs:ignore
		echo Generate_Form_Markup::get_form_markup( absint( $srfm_custom_post_id ), false, 'sureforms_form' );
		// phpcs:ignoreEnd
	}

	wp_footer();

	if ( $srfm_live_mode_data ) {
		?>
		<script>
			(function() {
				document.addEventListener('DOMContentLoaded', function() {
					document.querySelector('html').style.opacity = 1;
				});
			}());
		</script>
		<?php
	}
	?>
</body>

</html>
<?php
