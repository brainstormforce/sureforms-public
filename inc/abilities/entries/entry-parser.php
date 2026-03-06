<?php
/**
 * Entry Parser Trait.
 *
 * Shared entry parsing logic for abilities that need to transform
 * raw entry data into structured output with decoded field labels.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Entries;

use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Entry_Parser trait.
 *
 * @since x.x.x
 */
trait Entry_Parser {
	/**
	 * Parse a raw entry into structured output with decoded field labels.
	 *
	 * @param array<string,mixed> $entry Raw entry data from the database.
	 * @since x.x.x
	 * @return array<string,mixed> Parsed entry data.
	 */
	private function parse_entry( $entry ) {
		$form_data       = [];
		$excluded_fields = Helper::get_excluded_fields();
		$entry_form_data = $entry['form_data'] ?? [];

		if ( is_array( $entry_form_data ) ) {
			foreach ( $entry_form_data as $field_name => $value ) {
				if ( ! is_string( $field_name ) || in_array( $field_name, $excluded_fields, true ) ) {
					continue;
				}
				if ( false === str_contains( $field_name, '-lbl-' ) ) {
					continue;
				}

				$label_parts      = explode( '-lbl-', $field_name );
				$label            = isset( $label_parts[1] ) ? explode( '-', $label_parts[1] )[0] : '';
				$label            = $label ? Helper::decrypt( $label ) : '';
				$field_block_name = Helper::get_block_name_from_field( $field_name );

				$form_data[] = [
					'label'      => $label,
					'value'      => $value,
					'block_name' => $field_block_name,
				];
			}
		}

		// Get form info.
		$form_id    = absint( $entry['form_id'] ?? 0 );
		$form_title = get_post_field( 'post_title', $form_id );
		// Translators: %d is the form ID.
		$form_name = ! empty( $form_title ) ? $form_title : sprintf( __( 'SureForms Form #%d', 'sureforms' ), $form_id );

		// Build submission info.
		$submission_info = [
			'user_ip'      => $entry['submission_info']['user_ip'] ?? '',
			'browser_name' => $entry['submission_info']['browser_name'] ?? '',
			'device_name'  => $entry['submission_info']['device_name'] ?? '',
		];

		// Build user info.
		$user_id   = Helper::get_integer_value( $entry['user_id'] ?? 0 );
		$user_info = null;

		if ( 0 !== $user_id ) {
			$user_data = get_userdata( $user_id );

			if ( $user_data ) {
				$user_info = [
					'id'           => $user_id,
					'display_name' => $user_data->display_name,
					'profile_url'  => get_author_posts_url( $user_id ),
				];
			}
		}

		return [
			'form_id'         => $form_id,
			'form_name'       => $form_name,
			'status'          => $entry['status'] ?? '',
			'created_at'      => $entry['created_at'] ?? '',
			'form_data'       => $form_data,
			'submission_info' => $submission_info,
			'user'            => $user_info,
		];
	}
}
