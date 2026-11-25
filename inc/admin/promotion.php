<?php
/**
 * Promotion class.
 *
 * A time-boxed sale message shown as a scrolling bar above the SureForms admin
 * header. The sale window is checked on every request, so the bar appears and
 * goes away on its own, without a plugin update.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Admin;

use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Promotion class.
 *
 * @since x.x.x
 */
class Promotion {
	use Get_Instance;

	/**
	 * Id of the running promotion. Also the dismissal value, so a later sale
	 * with a new id shows again for users who dismissed this one.
	 *
	 * @since x.x.x
	 */
	public const ID = 'sureforms-bfcm-2026';

	/**
	 * Start of the sale, UTC.
	 *
	 * @since x.x.x
	 */
	public const STARTS_AT = '2026-11-15 08:00:00';

	/**
	 * End of the sale, UTC: Dec 7, 00:00 PT, right after Dec 6, 11:59:59 PM PT.
	 *
	 * @since x.x.x
	 */
	public const ENDS_AT = '2026-12-07 08:00:00';

	/**
	 * User meta holding the id of the promotion the user dismissed.
	 *
	 * @since x.x.x
	 */
	public const DISMISSED_META = 'srfm_dismissed_promotion';

	/**
	 * Register the dismiss route through the SureForms REST registry.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		add_filter( 'srfm_rest_api_endpoints', [ $this, 'register_routes' ] );
	}

	/**
	 * Append the dismiss route to the SureForms REST registry.
	 *
	 * @since x.x.x
	 *
	 * @param array<string,array<string,mixed>> $endpoints Existing registry.
	 * @return array<string,array<string,mixed>>
	 */
	public function register_routes( $endpoints ) {
		if ( ! is_array( $endpoints ) ) {
			$endpoints = [];
		}
		$endpoints['promotion/dismiss'] = [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_dismiss' ],
			'permission_callback' => static function () {
				return Helper::current_user_can();
			},
		];
		return $endpoints;
	}

	/**
	 * REST callback: hide the running promotion for the current user, for good.
	 *
	 * Only the running promotion id is accepted, and the stored value is the
	 * class constant, never the submitted string.
	 *
	 * @since x.x.x
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_dismiss( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'srfm_invalid_nonce',
				__( 'Invalid or missing security token.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		if ( self::ID !== $request->get_param( 'id' ) ) {
			return new WP_Error(
				'srfm_invalid_promotion',
				__( 'Unknown promotion.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		update_user_meta( get_current_user_id(), self::DISMISSED_META, self::ID );

		return new WP_REST_Response( [ 'dismissed' => true ], 200 );
	}

	/**
	 * The promotion for the current user, or null when the sale is not live,
	 * SureForms Pro is active, promotions are hidden, the user cannot manage
	 * SureForms, or the user dismissed it.
	 *
	 * @since x.x.x
	 *
	 * @param int|null $now Unix timestamp to check the window against. Defaults to now.
	 * @return array<string,mixed>|null
	 */
	public function get_for_current_user( $now = null ) {
		$user_id = get_current_user_id();

		if ( ! $user_id || ! Helper::current_user_can() || Helper::has_pro() || Helper::hide_promotions() || ! $this->is_live( $now ) ) {
			return null;
		}

		if ( self::ID === get_user_meta( $user_id, self::DISMISSED_META, true ) ) {
			return null;
		}

		return [
			'id'      => self::ID,
			'ends_at' => (int) strtotime( self::ENDS_AT . ' UTC' ),
			'badge'   => __( 'Live now', 'sureforms' ),
			'items'   => $this->get_items(),
			'cta'     => [
				'label' => __( 'Grab the Deal', 'sureforms' ),
				'url'   => esc_url_raw(
					add_query_arg(
						[
							'utm_source'   => 'sureforms_plugin',
							'utm_medium'   => 'top-bar',
							'utm_campaign' => 'bfcm-2026',
						],
						SRFM_WEBSITE . 'pricing/'
					)
				),
			],
		];
	}

	/**
	 * Whether the sale window is open.
	 *
	 * @since x.x.x
	 *
	 * @param int|null $now Unix timestamp. Defaults to now.
	 * @return bool
	 */
	public function is_live( $now = null ) {
		$now = null === $now ? time() : (int) $now;

		return $now >= strtotime( self::STARTS_AT . ' UTC' ) && $now < strtotime( self::ENDS_AT . ' UTC' );
	}

	/**
	 * Scrolling segments, each a list of text parts with a style: plain,
	 * bold, accent or strike. Prices match sureforms.com/pricing.
	 *
	 * @since x.x.x
	 *
	 * @return array<int, array<int, array{text: string, style: string}>>
	 */
	private function get_items() {
		$plan = static function ( $name, $price, $was ) {
			return [
				[
					'text'  => $name,
					'style' => 'plain',
				],
				[
					'text'  => $price,
					'style' => 'accent',
				],
				[
					'text'  => $was,
					'style' => 'strike',
				],
			];
		};

		return [
			[
				[
					'text'  => __( 'Black Friday Sale', 'sureforms' ),
					'style' => 'bold',
				],
			],
			[
				[
					'text'  => __( 'Up to 50% OFF', 'sureforms' ),
					'style' => 'accent',
				],
				[
					'text'  => __( 'SureForms Pro', 'sureforms' ),
					'style' => 'plain',
				],
			],
			array_merge(
				[
					[
						'text'  => __( 'Annual', 'sureforms' ),
						'style' => 'bold',
					],
				],
				$plan( __( 'Solo', 'sureforms' ), '$49', '$69' ),
				$plan( __( 'Team', 'sureforms' ), '$79', '$149' ),
				$plan( __( 'Agency', 'sureforms' ), '$199', '$249' )
			),
			array_merge(
				[
					[
						'text'  => __( 'Lifetime', 'sureforms' ),
						'style' => 'bold',
					],
				],
				$plan( __( 'Solo', 'sureforms' ), '$199', '$349' ),
				$plan( __( 'Team', 'sureforms' ), '$279', '$449' ),
				$plan( __( 'Agency', 'sureforms' ), '$499', '$999' )
			),
		];
	}
}
