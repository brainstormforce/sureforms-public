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
 * `srfm_bfcm_2026_banner_seen` / `srfm_bfcm_2026_banner_clicked` user meta.
 *
 * On multisite the dismissal and the "shown" and "clicked" flags are user
 * meta, so they hold across the network, while the counts are kept per site.
 *
 * @package sureforms
 * @since 2.14.3
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
 * @since 2.14.3
 */
class Promotion {
	use Get_Instance;

	/**
	 * Id of the running promotion. Also the dismissal value, so a later sale
	 * with a new id shows again for users who dismissed this one.
	 *
	 * @since 2.14.3
	 */
	public const ID = 'sureforms-bfcm-2026';

	/**
	 * Start of the sale, UTC.
	 *
	 * @since 2.14.3
	 */
	public const STARTS_AT = '2026-11-15 08:00:00';

	/**
	 * End of the sale, UTC: Monday Dec 7, 09:30 IST (Dec 6, 8:00 PM PT).
	 *
	 * @since 2.14.3
	 */
	public const ENDS_AT = '2026-12-07 04:00:00';

	/**
	 * User meta holding the id of the promotion the user dismissed.
	 *
	 * @since 2.14.3
	 */
	public const DISMISSED_META = 'srfm_dismissed_promotion';

	/**
	 * User meta holding the sale period the user was last counted as shown
	 * the bar in.
	 *
	 * @since 2.14.3
	 */
	public const SEEN_META = 'srfm_bfcm_2026_banner_seen';

	/**
	 * User meta holding the sale period the user was last counted as clicking
	 * the bar's button in.
	 *
	 * @since 2.14.3
	 */
	public const CLICKED_META = 'srfm_bfcm_2026_banner_clicked';

	/**
	 * Option holding the bar's analytics counts, per action and sale period.
	 *
	 * @since 2.14.3
	 */
	public const STATS_OPTION = 'srfm_bfcm_2026_banner_stats';

	/**
	 * Actions counted for analytics.
	 *
	 * @since 2.14.3
	 */
	public const ACTIONS = [ 'shown', 'click', 'dismiss' ];

	/**
	 * How long after ENDS_AT the counts keep being reported, in seconds
	 * (30 days). Long enough for the final totals to go out in a few analytics
	 * cycles; after that a site that never updates stops re-sending them.
	 *
	 * @since 2.14.3
	 */
	public const REPORTING_GRACE = 2592000;

	/**
	 * Sale periods from the BFCM banner calendar, each with its UTC start
	 * (00:00 PT). The last period that has started is the running one.
	 *
	 * @since 2.14.3
	 */
	public const PHASES = [
		'black-friday'       => '2026-11-15 08:00:00',
		'cyber-monday'       => '2026-11-30 08:00:00',
		'after-cyber-monday' => '2026-12-01 08:00:00',
		'final-hours'        => '2026-12-06 08:00:00',
	];

	/**
	 * Register the routes through the SureForms REST registry.
	 *
	 * @since 2.14.3
	 */
	public function __construct() {
		add_filter( 'srfm_rest_api_endpoints', [ $this, 'register_routes' ] );
	}

	/**
	 * Append the sale bar's routes to the SureForms REST registry.
	 *
	 * The `id` is checked in the callbacks rather than through an `args`
	 * schema, because core validates `args` before the permission callback.
	 *
	 * @since 2.14.3
	 *
	 * @param array<string,array<string,mixed>> $endpoints Existing registry.
	 * @return array<string,array<string,mixed>>
	 */
	public function register_routes( $endpoints ) {
		if ( ! is_array( $endpoints ) ) {
			$endpoints = [];
		}

		foreach ( self::ACTIONS as $action ) {
			$endpoints[ 'promotion/' . $action ] = [
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_' . $action ],
				'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
			];
		}

		return $endpoints;
	}

	/**
	 * REST callback: count the bar as shown, sent by the bar once it is on
	 * screen.
	 *
	 * @since 2.14.3
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_shown( $request ) {
		$error = $this->validate_request( $request );
		if ( $error ) {
			return $error;
		}

		$this->record_shown( get_current_user_id() );

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * REST callback: count a click on the bar's button for analytics. A click
	 * already counted in this sale period is answered but not counted again.
	 *
	 * @since 2.14.3
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_click( $request ) {
		$error = $this->validate_request( $request );
		if ( $error ) {
			return $error;
		}

		$user_id = get_current_user_id();
		$now     = time();

		if ( ! $this->should_show( $user_id, $now ) ) {
			return new WP_Error(
				'srfm_promotion_hidden',
				__( 'The promotion is not showing.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response( [ 'counted' => $this->record_click( $user_id, $now ) ], 200 );
	}

	/**
	 * REST callback: hide the running promotion for the current user, for good.
	 *
	 * Only the running promotion id is accepted, and the stored value is the
	 * class constant, never the submitted string.
	 *
	 * @since 2.14.3
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_dismiss( $request ) {
		$error = $this->validate_request( $request );
		if ( $error ) {
			return $error;
		}

		if ( ! $this->dismiss( get_current_user_id() ) ) {
			return new WP_Error(
				'srfm_promotion_dismiss_failed',
				__( 'Could not dismiss the promotion. Please try again.', 'sureforms' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response( [ 'dismissed' => true ], 200 );
	}

	/**
	 * Count the bar as shown to a user, once per user per sale period, and
	 * only while it is showing to them.
	 *
	 * @since 2.14.3
	 *
	 * @param int      $user_id User ID.
	 * @param int|null $now     Unix timestamp. Defaults to now.
	 * @return bool Whether it was counted.
	 */
	public function record_shown( $user_id, $now = null ) {
		return $this->record_once( 'shown', self::SEEN_META, $user_id, $now );
	}

	/**
	 * Count a click on the bar's button, once per user per sale period, and
	 * only while the bar is showing to the user. Counted like `shown`, so a
	 * period's click-through rate stays at or below 100%.
	 *
	 * @since 2.14.3
	 *
	 * @param int      $user_id User ID.
	 * @param int|null $now     Unix timestamp. Defaults to now.
	 * @return bool Whether it was counted.
	 */
	public function record_click( $user_id, $now = null ) {
		return $this->record_once( 'click', self::CLICKED_META, $user_id, $now );
	}

	/**
	 * Hide the running promotion for a user, for good. The first dismissal is
	 * counted, and only when the bar was showing to the user.
	 *
	 * @since 2.14.3
	 *
	 * @param int      $user_id User ID.
	 * @param int|null $now     Unix timestamp. Defaults to now.
	 * @return bool Whether the promotion is now dismissed for the user.
	 */
	public function dismiss( $user_id, $now = null ) {
		$now = null === $now ? time() : (int) $now;

		if ( self::ID === get_user_meta( $user_id, self::DISMISSED_META, true ) ) {
			return true;
		}

		$was_showing = $this->should_show( $user_id, $now );

		if ( ! update_user_meta( $user_id, self::DISMISSED_META, self::ID ) ) {
			return false;
		}

		if ( $was_showing ) {
			$this->record( 'dismiss', $now );
		}

		return true;
	}

	/**
	 * Add one to an analytics count for the running sale period.
	 *
	 * @since 2.14.3
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

		// Read-modify-write, so two requests in the same instant can lose a
		// count; fine for marketing totals.
		$stats                       = $this->get_stats();
		$stats[ $action ][ $phase ] += 1;

		return update_option( self::STATS_OPTION, $stats, false );
	}

	/**
	 * Analytics counts, every action and sale period present, zero when unset.
	 *
	 * @since 2.14.3
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
	 * Whether the counts are still reported to analytics: through the sale
	 * and for REPORTING_GRACE after it ends.
	 *
	 * @since 2.14.3
	 *
	 * @param int|null $now Unix timestamp. Defaults to now.
	 * @return bool
	 */
	public function is_reporting( $now = null ) {
		$now = null === $now ? time() : (int) $now;

		return $now < strtotime( self::ENDS_AT . ' UTC' ) + self::REPORTING_GRACE;
	}

	/**
	 * The promotion for the current user, or null when the sale is not live,
	 * SureForms Pro is active, promotions are hidden, the user cannot manage
	 * SureForms, or the user dismissed it.
	 *
	 * Read-only: it is built on every admin page, and the bar counts itself as
	 * shown through the `promotion/shown` route once it is on screen.
	 *
	 * @since 2.14.3
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

		$phase   = $this->get_phase( $now );
		$content = $this->get_content( $phase );

		return [
			'id'          => self::ID,
			'phase'       => $phase,
			// Already counted as shown in this period, so the bar skips the request.
			'seen'        => get_user_meta( $user_id, self::SEEN_META, true ) === $phase,
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
	 * @since 2.14.3
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
	 * @since 2.14.3
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
	 * Check the REST nonce and that the request names the running promotion.
	 *
	 * @since 2.14.3
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
	 * Count an action once per user per sale period, only while the bar is
	 * showing to the user. The user meta holds the period last counted.
	 *
	 * @since 2.14.3
	 *
	 * @param string   $action  One of self::ACTIONS.
	 * @param string   $meta    User meta key holding the period last counted.
	 * @param int      $user_id User ID.
	 * @param int|null $now     Unix timestamp. Defaults to now.
	 * @return bool Whether it was counted.
	 */
	private function record_once( $action, $meta, $user_id, $now ) {
		$now   = null === $now ? time() : (int) $now;
		$phase = $this->get_phase( $now );

		// Two requests at the same moment can both count the user; fine for
		// marketing totals.
		if ( '' === $phase || ! $this->should_show( $user_id, $now ) || get_user_meta( $user_id, $meta, true ) === $phase ) {
			return false;
		}

		update_user_meta( $user_id, $meta, $phase );

		return $this->record( $action, $now );
	}

	/**
	 * Whether the bar should show to a user at a given time.
	 *
	 * @since 2.14.3
	 *
	 * @param int $user_id User ID.
	 * @param int $now     Unix timestamp.
	 * @return bool
	 */
	private function should_show( $user_id, $now ) {
		return $user_id
			&& user_can( $user_id, 'manage_options' )
			&& ! Helper::has_pro()
			&& ! Helper::hide_promotions()
			&& $this->is_live( $now )
			&& self::ID !== get_user_meta( $user_id, self::DISMISSED_META, true );
	}

	/**
	 * Scrolling segments and button label for a sale period, from the BFCM
	 * banner calendar. Each segment is a list of text parts with a style:
	 * plain, bold or accent. The entry price always reads "Plans start at $49".
	 * A price inside a sentence is a placeholder, so translators can move it.
	 *
	 * @since 2.14.3
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

		// One translatable sentence with the highlighted value in place of %s,
		// split into plain text around an accent part.
		$sentence = static function ( $format, $value ) use ( $part ) {
			// A translation may number the placeholder (%1$s).
			$pieces = preg_split( '/%(?:1\$)?s/', $format, 2 );
			if ( ! is_array( $pieces ) || 2 !== count( $pieces ) ) {
				$pieces = [ $format, '' ];
			}

			$parts = [];
			if ( '' !== trim( $pieces[0] ) ) {
				$parts[] = $part( trim( $pieces[0] ) );
			}
			$parts[] = $part( $value, 'accent' );
			if ( '' !== trim( $pieces[1] ) ) {
				$parts[] = $part( trim( $pieces[1] ) );
			}
			return $parts;
		};

		/* translators: %s: lowest plan price, e.g. $49. */
		$from_49 = $sentence( __( 'Plans start at %s', 'sureforms' ), '$49' );
		/* translators: %s: discount, e.g. "up to 50%". */
		$save_50 = __( 'Save %s', 'sureforms' );

		switch ( $phase ) {
			case 'final-hours':
				return [
					'items' => [
						[ $part( __( 'Final hours', 'sureforms' ), 'bold' ) ],
						$sentence( $save_50, __( 'up to 50%', 'sureforms' ) ),
						$from_49,
					],
					'cta'   => __( 'Get the deal before it ends', 'sureforms' ),
				];
			case 'after-cyber-monday':
				return [
					'items' => [
						[ $part( __( 'Missed Cyber Monday?', 'sureforms' ), 'bold' ) ],
						/* translators: %s: discount, e.g. "up to 50%". */
						$sentence( __( 'You can still save %s', 'sureforms' ), __( 'up to 50%', 'sureforms' ) ),
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
						/* translators: %s: lowest lifetime plan price, e.g. $199. */
						$sentence( __( 'Lifetime plans from %s', 'sureforms' ), '$199' ),
					],
					'cta'   => __( 'View Black Friday deals', 'sureforms' ),
				];
		}
	}
}
