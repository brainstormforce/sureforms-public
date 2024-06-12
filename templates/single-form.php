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
		<?php
			$srfm_custom_post_id  = get_the_ID();
			$form_custom_css_meta = get_post_meta( $srfm_custom_post_id, '_srfm_form_custom_css', true );
			$custom_css           = ! empty( $form_custom_css_meta ) && is_string( $form_custom_css_meta ) ? $form_custom_css_meta : '';
		?>
		<style>
			<?php echo wp_kses_post( $custom_css ); ?>
		</style>
	</head>
	<body <?php body_class(); ?>>
	<?php
		$srfm_color1_val           = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_color1', true );
		$srfm_cover_image          = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_cover_image', true );
		$srfm_fontsize_val         = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_fontsize', true );
		$srfm_form_container_width = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_form_container_width', true ) ? strval( get_post_meta( intval( $srfm_custom_post_id ), '_srfm_form_container_width', true ) ) : 650;
		$show_title                = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_single_page_form_title', true ) ? strval( get_post_meta( intval( $srfm_custom_post_id ), '_srfm_single_page_form_title', true ) ) : '';
		$instant_form              = Helper::get_meta_value( $srfm_custom_post_id, '_srfm_instant_form' );

		$srfm_color_primary    = $srfm_color1_val ? strval( $srfm_color1_val ) : '#0284c7';
		$srfm_cover_image_url  = $srfm_cover_image ? rawurldecode( strval( $srfm_cover_image ) ) : '';
		$srfm_button_alignment = get_post_meta( intval( $srfm_custom_post_id ), '_srfm_submit_alignment', true ) ? strval( get_post_meta( intval( $srfm_custom_post_id ), '_srfm_submit_alignment', true ) ) : '';

	if ( ! $srfm_form_preview ) {



		if ( 'justify' === $srfm_button_alignment ) {
			$srfm_full = true;
		} else {
			$srfm_full = false;
		}
		?>
		<style>
			#srfm-single-page-container {
				--srfm-form-container-width: 
					<?php
					echo esc_attr( $srfm_form_container_width . 'px' );
					?>
					;
			}
			.single-sureforms_form .srfm-single-page-container .srfm-page-banner {
				<?php if ( ! empty( $srfm_cover_image_url ) ) : ?>
					background-image: url( <?php echo esc_attr( $srfm_cover_image_url ); ?> );
					background-position: center;
					background-size: cover;
					background-repeat: no-repeat;
				<?php else : ?>
					background-color: <?php echo esc_attr( $srfm_color_primary ); ?>;
				<?php endif; ?>
			}
		</style>
		<div id="srfm-single-page-container" class="srfm-single-page-container">
			<div class="srfm-page-banner">
				<?php if ( ! empty( $show_title ) && ! empty( $instant_form ) ) : ?>
					<h1 class="srfm-single-banner-title"><?php echo esc_html( get_the_title() ); ?></h1>
				<?php endif; ?>
			</div>
			<div class="srfm-form-wrapper">
				<?php
					// phpcs:ignore
					echo Generate_Form_Markup::get_form_markup( absint( $srfm_custom_post_id ), false,'', 'sureforms_form' );
					// phpcs:ignoreEnd
				?>
						<?php
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

				.srfm-form-container ~ div, .srfm-instant-form-wrn-ctn { 
					display: none !important; /* Needs to be important to remove any blocks added by external plugins in wp_footer() */	
				}
			</style>
			<?php

			show_admin_bar( false );

			// phpcs:ignore
			echo Generate_Form_Markup::get_form_markup( absint( $srfm_custom_post_id ), false, 'sureforms_form' );
			// phpcs:ignoreEnd

			wp_footer();
		}
		?>
	</body>
</html>
