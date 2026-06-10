<?php
/**
 * Onboarding Class
 *
 * Handles the onboarding process for the SureForms plugin.
 *
 * @package sureforms
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Onboarding Class
 *
 * Handles the onboarding process for the SureForms plugin.
 */
class Onboarding {
	use Get_Instance;

	/**
	 * User-meta key recording that the user dismissed the migration
	 * banner on the Forms listing page. The banner stays gone until a
	 * fresh install or a manual delete of the meta.
	 *
	 * @since 2.11.0
	 */
	public const MIGRATION_BANNER_DISMISSED_META_KEY = 'srfm_onboarding_migration_banner_dismissed';

	/**
	 * Onboarding completion setting key.
	 *
	 * @var string
	 */
	private $onboarding_status_key = 'onboarding_completed';

	/**
	 * Constructor — wire the dismiss-migration-banner REST endpoint via
	 * the existing `srfm_rest_api_endpoints` filter. Keeps the route
	 * registration consistent with the migrator + rest-api modules.
	 *
	 * @since 2.11.0
	 */
	public function __construct() {
		add_filter( 'srfm_rest_api_endpoints', [ $this, 'register_routes' ] );
	}

	/**
	 * Append onboarding-related REST routes to the SureForms registry.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,array<string,mixed>> $endpoints Existing registry.
	 * @return array<string,array<string,mixed>>
	 */
	public function register_routes( $endpoints ) {
		if ( ! is_array( $endpoints ) ) {
			$endpoints = [];
		}
		$endpoints['user-meta/dismiss-migration-banner'] = [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_dismiss_migration_banner' ],
			// Dismissing one's own banner is a per-user UX-preference write, so a
			// logged-in check is the right scope (not the heavier manage_options
			// the migrator endpoints need to create posts) — review #6.
			'permission_callback' => static function () {
				return is_user_logged_in();
			},
		];
		return $endpoints;
	}

	/**
	 * REST callback — persist the dismissal flag on the current user's
	 * meta so the migration banner stays gone across reloads.
	 *
	 * @since 2.11.0
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_dismiss_migration_banner( $request ) {
		// Verify the REST nonce, matching the migrator callbacks' convention
		// (the JS sends X-WP-Nonce via apiFetch) — review #3.
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'srfm_invalid_nonce',
				__( 'Invalid or missing security token.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}
		// permission_callback (is_user_logged_in) guarantees a real user here.
		update_user_meta( get_current_user_id(), self::MIGRATION_BANNER_DISMISSED_META_KEY, '1' );
		return new WP_REST_Response(
			[ 'dismissed' => true ],
			200
		);
	}

	/**
	 * Whether the current user has dismissed the migration banner.
	 * Surfaced to the admin React app via `srfm_admin.migration_banner_dismissed`.
	 *
	 * @since 2.11.0
	 *
	 * @return bool
	 */
	public function is_migration_banner_dismissed() {
		$user_id = get_current_user_id();
		if ( 0 === $user_id ) {
			return false;
		}
		return (bool) get_user_meta( $user_id, self::MIGRATION_BANNER_DISMISSED_META_KEY, true );
	}

	/**
	 * Set onboarding completion status.
	 *
	 * @since 1.9.1
	 * @param string $completed Whether the onboarding is completed.
	 * @return void
	 */
	public function set_onboarding_status( $completed = 'no' ) {
		Helper::update_srfm_option( $this->onboarding_status_key, $completed );
	}

	/**
	 * Get onboarding completion status.
	 *
	 * @since 1.9.1
	 * @return bool
	 */
	public function get_onboarding_status() {
		return Helper::get_srfm_option( $this->onboarding_status_key, 'no' ) === 'yes';
	}
}
