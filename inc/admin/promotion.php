<?php
/**
 * Promotion class.
 *
 * A time-boxed sale message shown as a scrolling bar above the SureForms admin
 * header. The sale window is checked on every request, so the bar appears and
 * goes away on its own, without a plugin update.
 *
 * Temporary: remove in the first release after the sale ends (2026-12-07),
 * together with its analytics event (`Analytics::track_bfcm_banner()`), the
 * `PromoBar` component and its `Header` hook, the `srfm-marquee` keyframes, and
 * the `srfm_bfcm_2026_banner_stats` option and `srfm_dismissed_promotion` /
 * `srfm_bfcm_2026_banner_seen` user meta.
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
	 * User meta marking that the user has been counted as shown the bar.
	 *
	 * @since x.x.x
	 */
	public const SEEN_META = 'srfm_bfcm_2026_banner_seen';

	/**
	 * Option holding the bar's analytics counts, per action and sale period.
	 *
	 * @since x.x.x
	 */
	public const STATS_OPTION = 'srfm_bfcm_2026_banner_stats';

	/**
	 * Actions counted for analytics.
	 *
	 * @since x.x.x
	 */
	public const ACTIONS = [ 'shown', 'click', 'dismiss' ];

	/**
	 * Sale periods from the BFCM banner calendar, each with its UTC start
	 * (00:00 PT). The last period that has started is the running one.
	 *
	 * @since x.x.x
	 */
	public const PHASES = [
		'black-friday'       => '2026-11-15 08:00:00',
		'cyber-monday'       => '2026-11-30 08:00:00',
		'after-cyber-monday' => '2026-12-01 08:00:00',
		'final-hours'        => '2026-12-06 08:00:00',
	];

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
		$can = static function () {
			return Helper::current_user_can();
		};

		$endpoints['promotion/dismiss'] = [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_dismiss' ],
			'permission_callback' => $can,
		];
		$endpoints['promotion/click']   = [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_click' ],
			'permission_callback' => $can,
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
		$error = $this->validate_request( $request );
		if ( $error ) {
			return $error;
		}

		$user_id = get_current_user_id();
		if ( self::ID !== get_user_meta( $user_id, self::DISMISSED_META, true ) ) {
			update_user_meta( $user_id, self::DISMISSED_META, self::ID );
			$this->record( 'dismiss' );
		}

		return new WP_REST_Response( [ 'dismissed' => true ], 200 );
	}

	/**
	 * REST callback: count a click on the bar's button for analytics.
	 *
	 * Counted only while the bar is showing to the current user, in the sale
	 * period the server is in.
	 *
	 * @since x.x.x
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_click( $request ) {
		$error = $this->validate_request( $request );
		if ( $error ) {
			return $error;
		}

		if ( ! $this->should_show( get_current_user_id(), time() ) ) {
			return new WP_Error(
				'srfm_promotion_hidden',
				__( 'The promotion is not showing.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		$this->record( 'click' );

		return new WP_REST_Response( [ 'counted' => true ], 200 );
	}

	/**
	 * Add one to an analytics count for the running sale period.
	 *
	 * @since x.x.x
	 *
	 * @param string   $action One of self::ACTIONS.
	 * @param int|null $now    Unix timestamp. Defaults to now.
	 * @return bool Whether it was counted.
	 */
	public function record( $action, $now = null ) {
		$phase = $this->get_phase( $now );
		if ( '' === $phase || ! in_array( $action, self::ACTIONS, true ) ) {
			return false;
		}

		// ponytail: read-modify-write, so two requests in the same instant can
		// lose a count; fine for marketing totals.
		$stats                      = $this->get_stats();
		$stats[ $action ][ $phase ] = $stats[ $action ][ $phase ] + 1;

		return update_option( self::STATS_OPTION, $stats, false );
	}

	/**
	 * Analytics counts, every action and sale period present, zero when unset.
	 *
	 * @since x.x.x
	 *
	 * @return array<string, array<string, int>>
	 */
	public function get_stats() {
		$stored = get_option( self::STATS_OPTION, [] );
		$stats  = [];
		foreach ( self::ACTIONS as $action ) {
			foreach ( array_keys( self::PHASES ) as $phase ) {
				$value                      = is_array( $stored ) && is_array( $stored[ $action ] ?? null ) ? ( $stored[ $action ][ $phase ] ?? 0 ) : 0;
				$stats[ $action ][ $phase ] = is_numeric( $value ) ? (int) $value : 0;
			}
		}
		return $stats;
	}

	/**
	 * Check the REST nonce and that the request names the running promotion.
	 *
	 * @since x.x.x
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request REST request.
	 * @return WP_Error|null Error to return, or null when the request is valid.
	 */
	private function validate_request( $request ) {
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

		return null;
	}

	/**
	 * Whether the bar should show to a user at a given time.
	 *
	 * @since x.x.x
	 *
	 * @param int $user_id User ID.
	 * @param int $now     Unix timestamp.
	 * @return bool
	 */
	private function should_show( $user_id, $now ) {
		return $user_id
			&& Helper::current_user_can()
			&& ! Helper::has_pro()
			&& ! Helper::hide_promotions()
			&& $this->is_live( $now )
			&& self::ID !== get_user_meta( $user_id, self::DISMISSED_META, true );
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
		$now     = null === $now ? time() : (int) $now;
		$user_id = get_current_user_id();

		if ( ! $this->should_show( $user_id, $now ) ) {
			return null;
		}

		if ( ! get_user_meta( $user_id, self::SEEN_META, true ) ) {
			update_user_meta( $user_id, self::SEEN_META, '1' );
			$this->record( 'shown', $now );
		}

		$phase   = $this->get_phase( $now );
		$content = $this->get_content( $phase );

		return [
			'id'          => self::ID,
			'phase'       => $phase,
			'ends_at'     => (int) strtotime( self::ENDS_AT . ' UTC' ),
			'timer_label' => __( 'Sale ends in', 'sureforms' ),
			'items'       => $content['items'],
			'cta'         => [
				'label' => $content['cta'],
				'url'   => esc_url_raw(
					add_query_arg(
						[
							'utm_source'   => 'sureforms_plugin',
							'utm_medium'   => 'top-bar',
							'utm_campaign' => 'bfcm-2026',
							'utm_content'  => $phase,
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
	 * Key of the running sale period, or an empty string outside the sale.
	 *
	 * @since x.x.x
	 *
	 * @param int|null $now Unix timestamp. Defaults to now.
	 * @return string
	 */
	public function get_phase( $now = null ) {
		$now = null === $now ? time() : (int) $now;

		if ( ! $this->is_live( $now ) ) {
			return '';
		}

		$running = '';
		foreach ( self::PHASES as $phase => $starts_at ) {
			if ( $now >= strtotime( $starts_at . ' UTC' ) ) {
				$running = $phase;
			}
		}

		return $running;
	}

	/**
	 * Scrolling segments and button label for a sale period, from the BFCM
	 * banner calendar. Each segment is a list of text parts with a style:
	 * plain, bold or accent. The entry price always reads "Plans start at $49".
	 *
	 * @since x.x.x
	 *
	 * @param string $phase Sale period key.
	 * @return array{items: array<int, array<int, array{text: string, style: string}>>, cta: string}
	 */
	private function get_content( $phase ) {
		$part = static function ( $text, $style = 'plain' ) {
			return [
				'text'  => $text,
				'style' => $style,
			];
		};

		$from_49 = [ $part( __( 'Plans start at', 'sureforms' ) ), $part( '$49', 'accent' ) ];

		switch ( $phase ) {
			case 'final-hours':
				return [
					'items' => [
						[ $part( __( 'Final hours', 'sureforms' ), 'bold' ) ],
						[ $part( __( 'Save', 'sureforms' ) ), $part( __( 'up to 50%', 'sureforms' ), 'accent' ) ],
						$from_49,
					],
					'cta'   => __( 'Get the deal before it ends', 'sureforms' ),
				];
			case 'after-cyber-monday':
				return [
					'items' => [
						[ $part( __( 'Missed Cyber Monday?', 'sureforms' ), 'bold' ) ],
						[ $part( __( 'You can still save', 'sureforms' ) ), $part( __( 'up to 50%', 'sureforms' ), 'accent' ) ],
						$from_49,
					],
					'cta'   => __( 'Pick a plan and save', 'sureforms' ),
				];
			case 'cyber-monday':
				return [
					'items' => [
						[ $part( __( 'Cyber Monday Sale', 'sureforms' ), 'bold' ) ],
						[ $part( __( 'Pay yearly or pay once.', 'sureforms' ) ), $part( __( 'Save up to 50%', 'sureforms' ), 'accent' ) ],
						[ $part( __( 'Save up to $500', 'sureforms' ), 'accent' ) ],
					],
					'cta'   => __( 'View Cyber Monday deals', 'sureforms' ),
				];
			default:
				return [
					'items' => [
						[ $part( __( 'Black Friday Sale', 'sureforms' ), 'bold' ) ],
						[ $part( __( 'Up to 50% off', 'sureforms' ), 'accent' ) ],
						$from_49,
						[ $part( __( 'Lifetime plans from', 'sureforms' ) ), $part( '$199', 'accent' ) ],
					],
					'cta'   => __( 'View Black Friday deals', 'sureforms' ),
				];
		}
	}
}
