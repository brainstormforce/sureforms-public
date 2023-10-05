<?php
/**
 * PHP render form Phone Block.
 *
 * @package SureForms.
 */

namespace SureForms\Inc\Blocks\Phone;

use SureForms\Inc\Blocks\Base;
use SureForms\Inc\Sureforms_Helper;
use SureForms\Inc\SureForms_Phone_Markup;

/**
 * Phone Block.
 */
class Block extends Base {
	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @return string|boolean
	 */
	public function render( $attributes, $content = '' ) {
		$upload_dir = wp_upload_dir();
		$file_path  = plugin_dir_url( __FILE__ ) . '/phone_codes.json';
		$response   = wp_remote_get( $file_path );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$json_string = wp_remote_retrieve_body( $response );
			$data        = json_decode( $json_string, true );
		} else {
			$data = array();
		}
		if ( ! empty( $attributes ) ) {
			$form_id = isset( $attributes['formId'] ) ? intval( $attributes['formId'] ) : '';
			$styling = get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_sureforms_form_styling', true ) ? Sureforms_Helper::get_string_value( get_post_meta( Sureforms_Helper::get_integer_value( $form_id ), '_sureforms_form_styling', true ) ) : '';
			ob_start();
			switch ( $styling ) {
				case 'inherit':
					// @phpstan-ignore-next-line
					echo SureForms_Phone_Markup::phone_default_styling( $attributes, $data ); // phpcs:ignore
					break;
				case 'classic':
					// @phpstan-ignore-next-line
					echo SureForms_Phone_Markup::phone_classic_styling( $attributes, $data ); // phpcs:ignore
					break;
				default:
					// @phpstan-ignore-next-line
					echo SureForms_Phone_Markup::phone_default_styling( $attributes, $data ); // phpcs:ignore
					break;
			}
		}
		return ob_get_clean();
	}
}
