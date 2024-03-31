<?php
/**
 * SureForms single form settings - Compliance settings.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc\Single_Form_Settings;

use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Helper;

/**
 * SureForms single form settings - Compliance settings.
 *
 * @since x.x.x
 */
class Compliance_Settings {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since x.x.x
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
	 * @since x.x.x
	 * @return void
	 */
	public static function pre_auto_delete_entries() {

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

			if ( is_array( $compliance_settings ) && is_array( $compliance_settings[0] ) && isset( $compliance_settings[0]['auto_delete_entries'] ) ) {
				$is_auto_delete_entries = $compliance_settings[0]['auto_delete_entries'];
				$days_old               = $compliance_settings[0]['auto_delete_days'];
			} else {
				$is_auto_delete_entries = false;
				$days_old               = 0;
			}

			if ( $is_auto_delete_entries ) {
				self::delete_old_entries( $form_id, $days_old );
			}
		}

	}

	/**
	 * Delete all the entries that are older than the days_old.
	 *
	 * @param int $days_old Number of days old.
	 * @param int $form_id Form ID.
	 * @since x.x.x
	 * @return void
	 */
	public static function delete_old_entries( $days_old, $form_id ) {
		$entries = Helper::get_entries_form_ids( $days_old, [ $form_id ] );

		if ( empty( $entries ) ) {
			return;
		}

		foreach ( $entries as $entry ) {
			$entry_id = isset( $entry->ID ) ? $entry->ID : 0;
			wp_delete_post( $entry_id, true );
		}
	}

}
