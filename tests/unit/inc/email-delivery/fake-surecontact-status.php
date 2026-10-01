<?php
/**
 * Stand-in for SureMail's SureContactStatus REST handler, which the suite does
 * not load. Only what Suremail_Adapter::refresh_surecontact_quota() calls.
 *
 * @package sureforms
 */

namespace SureMails\Inc\API;

if ( ! class_exists( __NAMESPACE__ . '\\SureContactStatus' ) ) {
	/**
	 * Fake SureContactStatus.
	 */
	class SureContactStatus {
		/**
		 * Response the next status() call returns.
		 *
		 * @var mixed
		 */
		public static $response = null;

		/**
		 * connection_id the last call asked for.
		 *
		 * @var string
		 */
		public static $asked_for = '';

		/**
		 * Singleton, as in SureMail.
		 *
		 * @return self
		 */
		public static function instance() {
			return new self();
		}

		/**
		 * Return the canned response.
		 *
		 * @param \WP_REST_Request $request Request.
		 * @return mixed
		 */
		public function status( $request ) {
			self::$asked_for = (string) $request->get_param( 'connection_id' );
			return self::$response;
		}
	}
}
