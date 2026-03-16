<?php
/**
 * Form Metadata Trait.
 *
 * Shared metadata override logic for form abilities.
 *
 * @package sureforms
 * @since 2.5.2
 */

namespace SRFM\Inc\Abilities\Forms;

use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Trait Form_Metadata
 *
 * Provides the shared apply_metadata_overrides() method used by
 * Create_Form and Update_Form abilities.
 *
 * @since 2.5.2
 */
trait Form_Metadata {
	/**
	 * Apply metadata overrides from ability input to post meta.
	 *
	 * @param array<string,mixed> $post_metas Default or current post meta values.
	 * @param array<string,mixed> $meta_data  Metadata from ability input.
	 * @since 2.5.2
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
			$confirmation_data = Helper::get_array_value( $post_metas['_srfm_form_confirmation'] ?? [] );
			$item              = ! empty( $confirmation_data[0] ) && is_array( $confirmation_data[0] )
				? $confirmation_data[0]
				: [
					'id'                => 1,
					'confirmation_type' => 'same page',
					'page_url'          => '',
					'custom_url'        => '',
					'message'           => '',
					'submission_action' => 'hide form',
				];

			$item['message']      = wp_kses_post( Helper::get_string_value( $form_confirmation['confirmationMessage'] ) );
			$confirmation_data[0] = $item;

			$post_metas['_srfm_form_confirmation'] = $confirmation_data;
		}

		// Instant form settings.
		$instant = Helper::get_array_value( $meta_data['instantForm'] ?? [] );
		if ( ! empty( $instant ) ) {
			$instant_settings = Helper::get_array_value( $post_metas['_srfm_instant_form_settings'] ?? [] );

			if ( isset( $instant['instantForm'] ) ) {
				$instant_settings['enable_instant_form'] = (bool) $instant['instantForm'];
			}
			if ( isset( $instant['showTitle'] ) ) {
				$instant_settings['single_page_form_title'] = (bool) $instant['showTitle'];
			}
			if ( ! empty( $instant['formWidth'] ) ) {
				$width = Helper::get_integer_value( $instant['formWidth'] );
				if ( $width >= 560 && $width <= 1000 ) {
					$instant_settings['form_container_width'] = $width;
				}
			}
			if ( ! empty( $instant['formBackgroundColor'] ) ) {
				$instant_settings['bg_color'] = sanitize_hex_color( Helper::get_string_value( $instant['formBackgroundColor'] ) );
			}

			$post_metas['_srfm_instant_form_settings'] = $instant_settings;
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
			$compliance_data = Helper::get_array_value( $post_metas['_srfm_compliance'] ?? [] );
			$item            = ! empty( $compliance_data[0] ) && is_array( $compliance_data[0] )
				? $compliance_data[0]
				: [
					'id'                   => 'gdpr',
					'gdpr'                 => false,
					'do_not_store_entries' => false,
					'auto_delete_entries'  => false,
					'auto_delete_days'     => '',
				];

			if ( ! empty( $compliance['enableCompliance'] ) ) {
				$item['gdpr'] = true;

				if ( ! empty( $compliance['neverStoreEntries'] ) ) {
					$item['do_not_store_entries'] = true;
					$item['auto_delete_entries']  = false;
				} elseif ( ! empty( $compliance['autoDeleteEntries'] ) ) {
					$item['do_not_store_entries'] = false;
					$item['auto_delete_entries']  = true;
					if ( ! empty( $compliance['autoDeleteEntriesDays'] ) ) {
						$item['auto_delete_days'] = sanitize_text_field( Helper::get_string_value( $compliance['autoDeleteEntriesDays'] ) );
					}
				}
			}

			$post_metas['_srfm_compliance'] = [ $item ];
		}

		// Form styling.
		$form_styling = Helper::get_array_value( $meta_data['formStyling'] ?? [] );
		if ( ! empty( $form_styling ) ) {
			$styling_data = Helper::get_array_value( $post_metas['_srfm_forms_styling'] ?? [] );

			if ( ! empty( $form_styling['primaryColor'] ) ) {
				$styling_data['primary_color'] = sanitize_hex_color( Helper::get_string_value( $form_styling['primaryColor'] ) );
			}
			if ( ! empty( $form_styling['textColor'] ) ) {
				$styling_data['text_color'] = sanitize_hex_color( Helper::get_string_value( $form_styling['textColor'] ) );
			}
			if ( ! empty( $form_styling['textColorOnPrimary'] ) ) {
				$styling_data['text_color_on_primary'] = sanitize_hex_color( Helper::get_string_value( $form_styling['textColorOnPrimary'] ) );
			}
			if ( ! empty( $form_styling['fieldSpacing'] ) ) {
				$valid = [ 'small', 'medium', 'large' ];
				if ( in_array( $form_styling['fieldSpacing'], $valid, true ) ) {
					$styling_data['field_spacing'] = $form_styling['fieldSpacing'];
				}
			}

			$post_metas['_srfm_forms_styling'] = $styling_data;
		}

		// Email notification — merges into first notification entry.
		$email = Helper::get_array_value( $meta_data['emailNotification'] ?? [] );
		if ( ! empty( $email ) ) {
			$notif_data = Helper::get_array_value( $post_metas['_srfm_email_notification'] ?? [] );
			$item       = ! empty( $notif_data[0] ) && is_array( $notif_data[0] )
				? $notif_data[0]
				: [
					'id'             => 1,
					'status'         => true,
					'is_raw_format'  => false,
					'name'           => '',
					'email_to'       => '{admin_email}',
					'email_reply_to' => '{admin_email}',
					'from_name'      => '{site_title}',
					'from_email'     => '{admin_email}',
					'email_cc'       => '',
					'email_bcc'      => '',
					'subject'        => '',
					'email_body'     => '{all_data}',
				];

			$field_map = [
				'status'       => 'status',
				'name'         => 'name',
				'emailTo'      => 'email_to',
				'emailReplyTo' => 'email_reply_to',
				'fromName'     => 'from_name',
				'fromEmail'    => 'from_email',
				'emailCc'      => 'email_cc',
				'emailBcc'     => 'email_bcc',
				'subject'      => 'subject',
				'emailBody'    => 'email_body',
			];

			foreach ( $field_map as $input_key => $meta_key ) {
				if ( ! isset( $email[ $input_key ] ) ) {
					continue;
				}
				if ( 'status' === $input_key ) {
					$item[ $meta_key ] = (bool) $email[ $input_key ];
				} else {
					$item[ $meta_key ] = sanitize_text_field( Helper::get_string_value( $email[ $input_key ] ) );
				}
			}

			$notif_data[0]                          = $item;
			$post_metas['_srfm_email_notification'] = $notif_data;
		}

		// Form restriction — stored as JSON string.
		$restriction = Helper::get_array_value( $meta_data['formRestriction'] ?? [] );
		if ( ! empty( $restriction ) ) {
			$restriction_json = Helper::get_string_value( $post_metas['_srfm_form_restriction'] ?? '' );
			$restriction_data = ! empty( $restriction_json ) ? json_decode( $restriction_json, true ) : [];
			if ( ! is_array( $restriction_data ) ) {
				$restriction_data = [];
			}

			$bool_fields   = [ 'status', 'schedulingStatus' ];
			$int_fields    = [ 'maxEntries' ];
			$string_fields = [
				'date',
				'hours',
				'minutes',
				'meridiem',
				'message',
				'startDate',
				'startHours',
				'startMinutes',
				'startMeridiem',
				'schedulingNotStartedMessage',
				'schedulingEndedMessage',
			];

			foreach ( $bool_fields as $key ) {
				if ( isset( $restriction[ $key ] ) ) {
					$restriction_data[ $key ] = (bool) $restriction[ $key ];
				}
			}
			foreach ( $int_fields as $key ) {
				if ( isset( $restriction[ $key ] ) ) {
					$restriction_data[ $key ] = absint( $restriction[ $key ] );
				}
			}
			foreach ( $string_fields as $key ) {
				if ( isset( $restriction[ $key ] ) ) {
					$restriction_data[ $key ] = sanitize_text_field( Helper::get_string_value( $restriction[ $key ] ) );
				}
			}

			$post_metas['_srfm_form_restriction'] = wp_json_encode( $restriction_data );
		}

		// Form custom CSS.
		if ( ! empty( $meta_data['formCustomCss'] ) ) {
			$post_metas['_srfm_form_custom_css'] = wp_kses_post( Helper::get_string_value( $meta_data['formCustomCss'] ) );
		}

		return $post_metas;
	}
}
