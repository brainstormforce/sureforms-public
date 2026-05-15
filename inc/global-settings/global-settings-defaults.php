<?php
/**
 * Global Settings Defaults Handler.
 *
 * Applies global settings as defaults when creating new forms.
 *
 * @package sureforms.
 * @since 2.9.0
 */

namespace SRFM\Inc\Global_Settings;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Global Settings Defaults Class.
 *
 * Handles applying global settings as defaults to newly created forms.
 *
 * @since 2.9.0
 */
class Global_Settings_Defaults {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 2.9.0
	 */
	public function __construct() {
		// SF-2815 start: live inheritance via read-time filter.
		// We hook default_post_metadata so get_post_meta() returns the
		// current global value when no row exists in wp_postmeta. The
		// moment a user edits a group, editPost writes a real row and the
		// filter no longer fires for that key — the form is detached.
		//
		// Priority 20 (not 10) is deliberate: WordPress's own
		// filter_default_metadata, which applies the hardcoded `default`
		// from register_post_meta, runs at priority 10. We need to run
		// after it so our global value overrides the static fallback.
		add_filter( 'default_post_metadata', [ $this, 'inject_global_defaults' ], 20, 4 );
		// SF-2815 end.
	}

	/**
	 * Return the current global value for one of the four group meta keys
	 * when the form has no stored value yet.
	 *
	 * Hooked to default_post_metadata, which fires only when no row exists
	 * for the requested key — so the moment a user edits a group, this
	 * filter no longer runs for that key and the form's stored value wins.
	 *
	 * Returns $value unchanged when (a) the meta is not on a sureforms_form
	 * post, (b) the key is not one of ours, or (c) the admin has not
	 * configured a global for that group — in (c) the existing
	 * register_post_meta hardcoded default still applies.
	 *
	 * @param mixed  $value     Default value (passed in by WP).
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 * @param bool   $single    Whether single value is requested.
	 * @return mixed
	 * @since 2.9.0
	 */
	public function inject_global_defaults( $value, $object_id, $meta_key, $single ) {
		if ( SRFM_FORMS_POST_TYPE !== get_post_type( $object_id ) ) {
			return $value;
		}

		switch ( $meta_key ) {
			case '_srfm_email_notification':
				$built = self::build_email_notification_meta();
				break;

			case '_srfm_form_confirmation':
				$built = self::build_form_confirmation_meta();
				break;

			case '_srfm_compliance':
				$built = self::build_compliance_meta();
				break;

			case '_srfm_form_restriction':
				$built = self::build_form_restriction_meta();
				$built = null === $built ? null : wp_json_encode( $built );
				break;

			default:
				return $value;
		}

		if ( null === $built ) {
			return $value;
		}

		// $single=false is what the REST API uses (it then takes $all_values[0]),
		// so we have to wrap our return in an outer array when $single is false —
		// otherwise REST sees a single object where the schema expects an array
		// and the editor renders an empty list.
		return $single ? $built : [ $built ];
	}

	/**
	 * Build the pre-transformed form meta array from all configured global settings.
	 *
	 * Returns an associative array of meta_key => meta_value in the exact shape
	 * the form post meta expects. Only includes keys where the admin has actually
	 * configured global settings — missing options are omitted so the form falls
	 * back to the hardcoded defaults from register_post_meta().
	 *
	 * Consumed by (a) the block editor localization to seed new-form defaults,
	 * and (b) the save_post hook to write defaults on first save.
	 *
	 * @return array<string, mixed> Map of meta_key => meta_value.
	 * @since 2.9.0
	 */
	public static function get_global_defaults_as_form_meta() {
		$meta = [];

		$email_meta = self::build_email_notification_meta();
		if ( null !== $email_meta ) {
			$meta['_srfm_email_notification'] = $email_meta;
		}

		$confirmation_meta = self::build_form_confirmation_meta();
		if ( null !== $confirmation_meta ) {
			$meta['_srfm_form_confirmation'] = $confirmation_meta;
		}

		$compliance_meta = self::build_compliance_meta();
		if ( null !== $compliance_meta ) {
			$meta['_srfm_compliance'] = $compliance_meta;
		}

		$restriction_meta = self::build_form_restriction_meta();
		if ( null !== $restriction_meta ) {
			// Form restriction meta is stored as a JSON string (register_meta type 'string').
			$meta['_srfm_form_restriction'] = wp_json_encode( $restriction_meta );
		}

		return $meta;
	}

	/**
	 * Build email notification form meta from global settings.
	 *
	 * @return array<int, array<string, mixed>>|null Form meta array or null if no global settings configured.
	 * @since 2.9.0
	 */
	private static function build_email_notification_meta() {
		$global_settings = get_option( 'srfm_email_notification_settings_options', [] );

		if ( empty( $global_settings ) || ! is_array( $global_settings ) ) {
			return null;
		}

		return [
			[
				'id'             => 1,
				'status'         => true,
				'is_raw_format'  => false,
				'name'           => __( 'Admin Notification Email', 'sureforms' ),
				'email_to'       => ! empty( $global_settings['email_to'] ) ? $global_settings['email_to'] : '{admin_email}',
				'email_reply_to' => ! empty( $global_settings['email_reply_to'] ) ? $global_settings['email_reply_to'] : '{admin_email}',
				'from_name'      => ! empty( $global_settings['from_name'] ) ? $global_settings['from_name'] : '{site_title}',
				'from_email'     => ! empty( $global_settings['from_email'] ) ? $global_settings['from_email'] : '{admin_email}',
				'email_cc'       => ! empty( $global_settings['email_cc'] ) ? $global_settings['email_cc'] : '{admin_email}',
				'email_bcc'      => ! empty( $global_settings['email_bcc'] ) ? $global_settings['email_bcc'] : '{admin_email}',
				'subject'        => ! empty( $global_settings['subject'] ) ? $global_settings['subject'] : sprintf( /* translators: %s: Form title smart tag */ __( 'New Form Submission - %s', 'sureforms' ), '{form_title}' ),
				'email_body'     => ! empty( $global_settings['email_body'] ) ? $global_settings['email_body'] : '{all_data}',
			],
		];
	}

	/**
	 * Build form confirmation form meta from global settings.
	 *
	 * @return array<int, array<string, mixed>>|null Form meta array or null if no global settings configured.
	 * @since 2.9.0
	 */
	private static function build_form_confirmation_meta() {
		$global_settings = get_option( 'srfm_form_confirmation_settings_options', [] );

		if ( empty( $global_settings ) || ! is_array( $global_settings ) ) {
			return null;
		}

		$default_confirmation_message = Global_Settings::get_default_confirmation_message();

		return [
			[
				'id'                  => 1,
				'confirmation_type'   => ! empty( $global_settings['confirmation_type'] ) ? $global_settings['confirmation_type'] : 'same page',
				'page_url'            => ! empty( $global_settings['page_url'] ) ? $global_settings['page_url'] : '',
				'custom_url'          => ! empty( $global_settings['custom_url'] ) ? $global_settings['custom_url'] : '',
				'message'             => ! empty( $global_settings['message'] ) ? $global_settings['message'] : $default_confirmation_message,
				'submission_action'   => ! empty( $global_settings['submission_action'] ) ? $global_settings['submission_action'] : 'hide form',
				'enable_query_params' => ! empty( $global_settings['enable_query_params'] ),
				'query_params'        => ! empty( $global_settings['query_params'] ) && is_array( $global_settings['query_params'] ) ? $global_settings['query_params'] : [],
			],
		];
	}

	/**
	 * Build compliance form meta from global settings.
	 *
	 * @return array<int, array<string, mixed>>|null Form meta array or null if no global settings configured.
	 * @since 2.9.0
	 */
	private static function build_compliance_meta() {
		$global_settings = get_option( 'srfm_compliance_settings_options', [] );

		if ( empty( $global_settings ) || ! is_array( $global_settings ) ) {
			return null;
		}

		return [
			[
				'id'                   => 'gdpr',
				'gdpr'                 => ! empty( $global_settings['gdpr'] ) ? (bool) $global_settings['gdpr'] : false,
				'do_not_store_entries' => ! empty( $global_settings['do_not_store_entries'] ) ? (bool) $global_settings['do_not_store_entries'] : false,
				'auto_delete_entries'  => ! empty( $global_settings['auto_delete_entries'] ) ? (bool) $global_settings['auto_delete_entries'] : false,
				'auto_delete_days'     => isset( $global_settings['auto_delete_days'] ) ? strval( $global_settings['auto_delete_days'] ) : '',
			],
		];
	}

	/**
	 * Build form restriction form meta from global settings.
	 *
	 * Global settings are organized by restriction type (max_entries, ip_restriction, etc.)
	 * while form-level settings are flat. Only max_entries is mapped — form-level meta
	 * has a simpler structure focused on entry limits and scheduling.
	 *
	 * @return array<string, mixed>|null Form meta array (unencoded) or null if no global settings configured.
	 * @since 2.9.0
	 */
	private static function build_form_restriction_meta() {
		$global_settings = get_option( 'srfm_form_restriction_settings_options', [] );

		if ( empty( $global_settings ) || ! is_array( $global_settings ) ) {
			return null;
		}

		$max_entries = isset( $global_settings['max_entries'] ) && is_array( $global_settings['max_entries'] )
			? $global_settings['max_entries']
			: [];

		return [
			'status'                      => ! empty( $max_entries['status'] ) ? (bool) $max_entries['status'] : false,
			'maxEntries'                  => isset( $max_entries['maxEntries'] ) ? absint( $max_entries['maxEntries'] ) : 0,
			'date'                        => '',
			'hours'                       => '12',
			'minutes'                     => '00',
			'meridiem'                    => 'AM',
			'message'                     => ! empty( $max_entries['message'] ) ? $max_entries['message'] : __( "This form is now closed as we've received all the entries.", 'sureforms' ),
			// Form Scheduling meta - not part of global settings, use defaults.
			'schedulingStatus'            => false,
			'startDate'                   => '',
			'startHours'                  => '12',
			'startMinutes'                => '00',
			'startMeridiem'               => 'AM',
			'schedulingNotStartedMessage' => __( 'This form is not yet available. Please check back after the scheduled start time.', 'sureforms' ),
			'schedulingEndedMessage'      => __( 'This form is no longer accepting submissions. The submission period has ended.', 'sureforms' ),
		];
	}

}
