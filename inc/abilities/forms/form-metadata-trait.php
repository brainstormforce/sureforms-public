<?php
/**
 * Form Metadata Trait.
 *
 * Shared metadata override logic for form abilities.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Forms;

use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Trait Form_Metadata_Trait
 *
 * Provides the shared apply_metadata_overrides() method used by
 * Create_Form and Update_Form abilities.
 *
 * @since x.x.x
 */
trait Form_Metadata_Trait {

	/**
	 * Apply metadata overrides from ability input to post meta.
	 *
	 * @param array<string,mixed> $post_metas Default or current post meta values.
	 * @param array<string,mixed> $meta_data  Metadata from ability input.
	 * @since x.x.x
	 * @return array<string,mixed>
	 */
	protected function apply_metadata_overrides( $post_metas, $meta_data ) {
		if ( empty( $meta_data ) ) {
			return $post_metas;
		}

		// General settings.
		$general = Helper::get_array_value( $meta_data['general'] ?? [] );
		if ( ! empty( $general ) ) {
			if ( isset( $general['submitText'] ) ) {
				$post_metas['_srfm_submit_button_text'] = sanitize_text_field( Helper::get_string_value( $general['submitText'] ) );
			}
			if ( isset( $general['useLabelAsPlaceholder'] ) ) {
				$post_metas['_srfm_use_label_as_placeholder'] = (bool) $general['useLabelAsPlaceholder'];
			}
		}

		// Confirmation message.
		$form_confirmation = Helper::get_array_value( $meta_data['formConfirmation'] ?? [] );
		if ( ! empty( $form_confirmation['confirmationMessage'] ) ) {
			$post_metas['_srfm_confirmation_message'] = wp_kses_post( Helper::get_string_value( $form_confirmation['confirmationMessage'] ) );
		}

		// Instant form settings.
		$instant = Helper::get_array_value( $meta_data['instantForm'] ?? [] );
		if ( ! empty( $instant ) ) {
			if ( isset( $instant['instantForm'] ) && $instant['instantForm'] ) {
				$post_metas['_srfm_instant_form'] = 'enabled';
			}
			if ( isset( $instant['showTitle'] ) ) {
				$post_metas['_srfm_single_page_form_title'] = $instant['showTitle'] ? 1 : 0;
			}
			if ( ! empty( $instant['formWidth'] ) ) {
				$width = Helper::get_integer_value( $instant['formWidth'] );
				if ( $width >= 560 && $width <= 1000 ) {
					$post_metas['_srfm_form_container_width'] = $width;
				}
			}
			if ( ! empty( $instant['formBackgroundColor'] ) ) {
				$post_metas['_srfm_bg_color'] = sanitize_hex_color( Helper::get_string_value( $instant['formBackgroundColor'] ) );
			}
		}

		// Styling.
		$styling = Helper::get_array_value( $meta_data['styling'] ?? [] );
		if ( ! empty( $styling ) ) {
			if ( ! empty( $styling['submitAlignment'] ) ) {
				$valid_alignments = [ 'left', 'center', 'right', 'full-width' ];
				if ( in_array( $styling['submitAlignment'], $valid_alignments, true ) ) {
					$post_metas['_srfm_submit_alignment'] = sanitize_text_field( Helper::get_string_value( $styling['submitAlignment'] ) );
				}
			}

			if ( 'full-width' === ( $styling['submitAlignment'] ?? '' ) ) {
				$post_metas['_srfm_submit_width']         = '100%';
				$post_metas['_srfm_submit_width_backend'] = '100%';
			}
		}

		// Compliance settings.
		$compliance = Helper::get_array_value( $meta_data['compliance'] ?? [] );
		if ( ! empty( $compliance ) ) {
			if ( ! empty( $compliance['enableCompliance'] ) ) {
				$post_metas['_srfm_compliance'] = true;

				if ( ! empty( $compliance['neverStoreEntries'] ) ) {
					$post_metas['_srfm_compliance_opt'] = 'do-not-store';
				} elseif ( ! empty( $compliance['autoDeleteEntries'] ) && ! empty( $compliance['autoDeleteEntriesDays'] ) ) {
					$post_metas['_srfm_compliance_opt']  = 'auto-delete';
					$post_metas['_srfm_compliance_days'] = Helper::get_integer_value( $compliance['autoDeleteEntriesDays'] );
				}
			}
		}

		return $post_metas;
	}
}
