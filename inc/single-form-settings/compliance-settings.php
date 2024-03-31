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
		add_action( 'srfm_daily_scheduled_action', [ $this, 'pre_auto_delete_entries' ], 10 );
		add_action( 'init', [ $this, 'pre_auto_delete_entries' ], 10 );
	}

	/**
	 * Runs every 24 hours for SureForms.
	 * And check if auto delete entries are enabled for any forms.
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

		// For each form post id check if _srfm_auto_delete_entries is enabled.
		foreach ( $form_ids as $form_id ) {
			$compliance_settings    = get_post_meta( $form_id, '_srfm_compliance', true );
			$is_auto_delete_entries = is_array( $compliance_settings ) ? $compliance_settings['auto_delete_entries'] : '0';
			$days_old               = is_array( $compliance_settings ) ? $compliance_settings['auto_delete_days'] : '0';

			if ( '1' === $is_auto_delete_entries ) {
				self::auto_delete_entries( $form_id, $days_old );
			}
		}

	}

	/**
	 * Auto delete entries
	 *
	 * @since 0.0.1
	 * @param int $form_id Form id.
	 * @param int $days_old Days old.
	 * @return void
	 */
	public static function auto_delete_entries( $form_id, $days_old ) {

		if ( empty( $days_old ) ) {
			return;
		}

		$sf_form_ids = [ $form_id ];

		$entries = Helper::get_entries_form_ids( $days_old, $sf_form_ids );

		self::delete_old_entries( $entries, $days_old, $sf_form_ids );
	}

	/**
	 * Delete old entries
	 *
	 * @since 0.0.1
	 * @param array<mixed> $entries Entries.
	 * @param int          $days_old Days old.
	 * @param array<int>   $sf_form_ids Sureform form ids.
	 * @return void
	 */
	public static function delete_old_entries( $entries, $days_old = 1, $sf_form_ids = [] ) {
	}

}
