<?php
/**
 * SureForms single form settings - Compliance settings.
 *
 * @package sureforms.
 * @since 0.0.2
 */

namespace SRFM\Inc\Single_Form_Settings;

use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;

/**
 * SureForms single form settings - Compliance settings.
 *
 * @since 0.0.2
 */
class Compliance_Settings {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 0.0.2
	 */
	public function __construct() {
		add_action( 'srfm_daily_scheduled_action', [ $this, 'pre_auto_delete_entries' ] );
	}

	/**
	 * Runs every 24 hours for SureForms.
	 * And check if auto delete entries are enabled for any forms.
	 * If enabled then delete the entries that are older than the days_old.
	 *
	 * @hooked - srfm_daily_scheduled_action
	 * @since 0.0.2
	 * @return void
	 */
	public function pre_auto_delete_entries() {

		// Get all the sureforms form post ids.
		$form_ids = [];

		$args = [
			'post_type'      => 'sureforms_form',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		];

		$form_ids = get_posts( $args );

		if ( empty( $form_ids ) ) {
			return;
		}

		// For each form post id check if auto_delete_entries is enabled. If enabled then delete the entries that are older than the days_old.
		foreach ( $form_ids as $form_id ) {
			$compliance_settings = get_post_meta( $form_id, '_srfm_compliance', true );

			$gdpr                   = false;
			$do_not_store_entries   = false;
			$is_auto_delete_entries = false;
			$days_old               = 0;

			if ( is_array( $compliance_settings ) && is_array( $compliance_settings[0] ) && isset( $compliance_settings[0]['gdpr'], $compliance_settings[0]['do_not_store_entries'], $compliance_settings[0]['auto_delete_entries'] ) ) {
				$gdpr                   = $compliance_settings[0]['gdpr'];
				$do_not_store_entries   = $compliance_settings[0]['do_not_store_entries'];
				$is_auto_delete_entries = $compliance_settings[0]['auto_delete_entries'];
				$days_old               = $compliance_settings[0]['auto_delete_days'];
			}

			// Only delete entries if gdpr, is_auto_delete_entries are enabled and do_not_store_entries is not enabled.
			if ( $gdpr && ! $do_not_store_entries && $is_auto_delete_entries ) {
				self::delete_old_entries( $days_old, $form_id );
			}
		}
	}

	/**
	 * Delete all the entries that are older than the days_old.
	 *
	 * @param int $days_old Number of days old.
	 * @param int $form_id Form ID.
	 * @since 0.0.2
	 * @return void
	 */
	public static function delete_old_entries( $days_old, $form_id ) {
		$entries = Helper::get_entries_from_form_ids( $days_old, [ $form_id ] );

		if ( ! is_array( $entries ) || empty( $entries ) ) {
			return;
		}

		foreach ( $entries as $entry ) {
			$entry_id = isset( $entry['ID'] ) ? Helper::get_integer_value( $entry['ID'] ) : 0;
			Entries::delete( $entry_id );
		}
	}

}
