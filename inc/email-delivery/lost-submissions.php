<?php
/**
 * Submissions lost because they were never stored and never emailed.
 *
 * A form set to never store entries keeps no copy of a submission: the email
 * notification is the only record. When no notification for it is accepted,
 * the submission is gone. That has to stay visible until the owner has seen
 * it, so it is kept apart from the notification fault (which a later success
 * clears) and from the debug log (which can be switched off).
 *
 * @package SureForms
 * @since   2.12.9
 */

namespace SRFM\Inc\Email_Delivery;

use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lost_Submissions
 *
 * @since 2.12.9
 */
class Lost_Submissions {
	/**
	 * Option holding the record. Not autoloaded; no personal data.
	 *
	 * @since 2.12.9
	 */
	public const OPTION = 'srfm_lost_submissions';

	/**
	 * Most form ids kept; the oldest is dropped first.
	 *
	 * @since 2.12.9
	 */
	public const MAX_FORMS = 10;

	/**
	 * Record one lost submission.
	 *
	 * @since 2.12.9
	 * @param int $form_id Form the submission belonged to.
	 * @return void
	 */
	public static function record( $form_id ) {
		$record  = self::get();
		$form_id = absint( $form_id );

		$forms = array_values( array_diff( $record['form_ids'], [ $form_id ] ) );
		if ( $form_id > 0 ) {
			$forms[] = $form_id;
		}

		update_option(
			self::OPTION,
			[
				'count'    => $record['count'] + 1,
				'form_ids' => array_slice( $forms, -self::MAX_FORMS ),
				'last_at'  => time(),
			],
			false
		);
	}

	/**
	 * The record, normalised. A count of 0 means nothing was lost.
	 *
	 * @since 2.12.9
	 * @return array{count:int,form_ids:array<int,int>,last_at:int}
	 */
	public static function get() {
		$stored = get_option( self::OPTION, [] );
		$stored = is_array( $stored ) ? $stored : [];

		$forms = [];
		if ( isset( $stored['form_ids'] ) && is_array( $stored['form_ids'] ) ) {
			foreach ( $stored['form_ids'] as $form_id ) {
				if ( is_numeric( $form_id ) && (int) $form_id > 0 ) {
					$forms[] = absint( $form_id );
				}
			}
		}

		return [
			'count'    => Helper::get_integer_value( $stored['count'] ?? 0 ),
			'form_ids' => $forms,
			'last_at'  => Helper::get_integer_value( $stored['last_at'] ?? 0 ),
		];
	}

	/**
	 * The owner has seen it: start counting again from zero.
	 *
	 * @since 2.12.9
	 * @return void
	 */
	public static function acknowledge() {
		delete_option( self::OPTION );
	}
}
