<?php
/**
 * SureForms Form Views tracker.
 *
 * Cache-safe, per-page-load form view (impression) tracking used to power the
 * Views and Conversion Rate columns on the Forms listing table.
 *
 * Views are recorded by a client-side beacon (see assets/js/unminified/form-submit.js)
 * that fires when a form actually becomes visible, so counting works even on
 * fully page-cached pages and non-JS crawlers are excluded naturally. The beacon
 * posts to the public `sureforms/v1/forms/track-view` route, which is authenticated with
 * the same HMAC Submit_Token used by form submission (not a nonce, which would break
 * under full-page caching).
 *
 * @package sureforms
 * @since 2.12.6
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
 * Form Views tracker.
 *
 * @since 2.12.6
 */
class Form_Views {
	use Get_Instance;

	/**
	 * Post meta key that stores the accumulated view count for a form.
	 */
	public const META_KEY = '_srfm_form_views';

	/**
	 * General-settings key that shows or hides the Views and Conversion Rate
	 * columns. Lives in the `srfm_general_settings_options` option and is absent
	 * until switched on, which reads as off — the feature is opt-in.
	 */
	public const SETTING_KEY = 'srfm_form_views_tracking';

	/**
	 * Option holding the unix time from which view counting has been running.
	 *
	 * Conversion rate divides entries by views, and views only start accruing when
	 * tracking is switched on — so counting entries from the beginning of time would
	 * compare two different periods and report a wildly inflated rate on any form
	 * that existed beforehand. Entries are therefore counted from this moment too.
	 *
	 * Deliberately its own option rather than a key inside
	 * `srfm_general_settings_options`: that array is rebuilt from an allowlist when
	 * settings are saved, so an unrecognised key added to it would be silently
	 * dropped on the next save.
	 */
	public const TRACKING_STARTED_OPTION = 'srfm_form_views_tracking_started_at';

	/**
	 * Max beacon hits accepted per visitor network + form within the rate-limit window.
	 */
	private const RATE_LIMIT_MAX = 20;

	/**
	 * Max beacon hits accepted for one form from all sources within the window.
	 *
	 * The per-network bucket alone cannot bound anything: an attacker on an IPv6 /64
	 * — standard on any VPS — gets a fresh bucket per address, and the first request
	 * to each new bucket is always allowed. This ceiling is what actually caps a
	 * distributed flood, both for the counter's accuracy and for the number of
	 * buckets that can be minted.
	 */
	private const RATE_LIMIT_FORM_MAX = 200;

	/**
	 * Constructor.
	 *
	 * @since 2.12.6
	 */
	public function __construct() {
		add_filter( 'srfm_rest_api_endpoints', [ $this, 'register_route' ] );
		// Localize on wp_footer (before wp_print_footer_scripts at priority 20) rather than
		// wp_enqueue_scripts: forms embedded via page builders, widget shortcodes, or a late
		// do_blocks() pass enqueue srfm-form-submit AFTER wp_enqueue_scripts has run, and
		// those would otherwise never receive the beacon flag.
		add_action( 'wp_footer', [ $this, 'localize_beacon' ], 5 );

		// Both hooks: update_option_* does not fire when the option row does not exist
		// yet, which is exactly the state of a fresh install saving settings for the
		// first time — the case this stamp exists for.
		add_action( 'update_option_srfm_general_settings_options', [ $this, 'maybe_start_tracking' ], 10, 2 );
		add_action( 'add_option_srfm_general_settings_options', [ $this, 'maybe_start_tracking' ], 10, 2 );

		// Repairs a toggle that was switched on by a route that fires neither hook.
		add_action( 'admin_init', [ $this, 'maybe_repair_tracking_window' ] );
	}

	/**
	 * Unix time from which views have been counted, or 0 if counting never started.
	 *
	 * This is a pure read. The stamp is written by maybe_start_tracking() the first
	 * time an administrator switches the feature on, and never rewritten — so it is
	 * also the answer to "has counting ever started", which is what should_track()
	 * uses. A lazy write here would open the window on any read, including one from
	 * a site that never enabled the feature.
	 *
	 * Never re-stamped on a later toggle. Counting does not stop when the columns are
	 * hidden, so the stamp always matches the period the stored view counts cover;
	 * moving it forward would measure those views against a shorter entry window.
	 *
	 * @since 2.12.6
	 * @return int Unix timestamp, or 0 when tracking has never been enabled.
	 */
	public function get_tracking_started_at() {
		return Helper::get_integer_value( get_option( self::TRACKING_STARTED_OPTION, 0 ) );
	}

	/**
	 * Open the counting window the first time the feature is switched on.
	 *
	 * Hooked to the General-settings option write rather than to the REST handler, so
	 * it fires for every route that flips the setting — the settings screen, the
	 * abilities/MCP update endpoint, or a direct update_option() from WP-CLI.
	 *
	 * add_option() rather than update_option(): it only creates the row when absent,
	 * so two concurrent saves cannot move a window that is already open, and a later
	 * off/on cycle leaves the original stamp intact. Not autoloaded — it is read on
	 * the Forms list screen and by the beacon, not on every request.
	 *
	 * The new value is read from the SECOND parameter because that is where both
	 * hooks put it, despite their first parameters differing:
	 * `do_action( "update_option_{$option}", $old_value, $value, $option )` and
	 * `do_action( "add_option_{$option}", $option, $value )`. Taking the first
	 * argument would read the pre-save value on one hook and the option name on
	 * the other.
	 *
	 * @param mixed $unused Previous value on update, option name on add. Unused.
	 * @param mixed $value  The general settings array being saved.
	 * @since 2.12.6
	 * @return void
	 */
	public function maybe_start_tracking( $unused, $value ) {
		unset( $unused );

		if ( ! is_array( $value ) || empty( $value[ self::SETTING_KEY ] ) ) {
			return;
		}

		add_option( self::TRACKING_STARTED_OPTION, time(), '', false );
	}

	/**
	 * Register the public `forms/track-view` REST route on the SureForms endpoints array.
	 *
	 * @param array<string,mixed> $endpoints Existing endpoint definitions.
	 * @since 2.12.6
	 * @return array<string,mixed> Endpoints with the track-view route added.
	 */
	public function register_route( $endpoints ) {
		if ( ! is_array( $endpoints ) ) {
			return $endpoints;
		}

		$endpoints['forms/track-view'] = [
			'methods'             => 'POST',
			'callback'            => [ $this, 'track_view' ],
			'permission_callback' => [ $this, 'permissions_check' ],
			'args'                => [
				'form_id' => [
					'type'     => 'integer',
					'required' => true,
					'minimum'  => 1,
				],
			],
		];

		return $endpoints;
	}

	/**
	 * Permission check: proof-of-origin via the HMAC Submit_Token header (cache-safe).
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request REST request.
	 * @since 2.12.6
	 * @return true|WP_Error
	 */
	public function permissions_check( $request ) {
		$token   = Helper::get_string_value( $request->get_header( 'X-WP-Submit-Token' ) );
		$form_id = absint( Helper::get_integer_value( $request->get_param( 'form_id' ) ) );

		if ( ! Submit_Token::verify( $token, $form_id, Submit_Token::NAMESPACE_VIEW ) ) {
			return new WP_Error(
				'srfm_view_token_invalid',
				__( 'Security verification failed.', 'sureforms' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Record a view for the given form.
	 *
	 * Silently no-ops (200) for excluded contexts so the beacon never surfaces an
	 * error to visitors; returns 429 only when the IP + form rate limit is exceeded.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request REST request.
	 * @since 2.12.6
	 * @return WP_REST_Response
	 */
	public function track_view( $request ) {
		$form_id = absint( Helper::get_integer_value( $request->get_param( 'form_id' ) ) );

		// Publicly viewable, not merely the right post type: get_post_type() answers
		// the same for a draft or trashed form, and a token minted while the form was
		// live stays valid for up to 48 hours afterwards. Without this, views keep
		// accruing against content nobody can reach — and the analytics denominator
		// counts published forms only, so those views skew the site-wide figures.
		if ( ! $form_id || SRFM_FORMS_POST_TYPE !== get_post_type( $form_id ) || ! is_post_publicly_viewable( $form_id ) ) {
			return new WP_REST_Response( [ 'counted' => false ], 200 );
		}

		// Exclude previews and logged-in privileged users (author/editor/admin).
		if ( ! $this->should_track( $request ) ) {
			return new WP_REST_Response( [ 'counted' => false ], 200 );
		}

		if ( $this->is_rate_limited( $form_id ) ) {
			return new WP_REST_Response( [ 'counted' => false ], 429 );
		}

		$this->increment_views( $form_id );

		return new WP_REST_Response( [ 'counted' => true ], 200 );
	}

	/**
	 * Localize the beacon enable flag onto the (already enqueued) form-submit script.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public function localize_beacon() {
		if ( ! wp_script_is( 'srfm-form-submit', 'enqueued' ) ) {
			return;
		}

		wp_localize_script(
			'srfm-form-submit',
			'srfm_view_beacon',
			// '1'/'0' strings, not a raw bool: WP_Scripts::localize() casts every
			// scalar with (string), so false arrives as '' and true as '1'. That
			// happens to work with a truthiness check today, but anyone later
			// 'tidying' this to send '0' would invert the gate, because '0' is a
			// truthy JS string. The JS side compares with '1' !== rather than
			// truthiness for exactly that reason.
			[
				// Gated on the tracking-started stamp, NOT on should_track(). This value
				// is printed into HTML that a full-page cache stores and replays to
				// everyone, so anything request-specific here is frozen at whichever
				// request happened to populate the cache: a page first cached while an
				// editor was logged in would bake in '0' and silently stop counting for
				// every visitor until the cache was purged. The stamp is site-wide and
				// only ever flips once, so it is safe to cache. Per-request exclusions
				// (privileged users, live previews) stay server-side in track_view(),
				// which re-checks should_track() on every call and cannot go stale.
				'enabled' => $this->get_tracking_started_at() > 0 ? '1' : '0',
				// Fully resolved so the beacon can use a plain fetch(). rest_url()
				// handles plain permalinks (?rest_route=), subdirectory installs and
				// multisite domain mapping — the only thing wp.apiFetch offered here.
				'url'     => esc_url_raw( rest_url( 'sureforms/v1/forms/track-view' ) ),
			]
		);
	}

	/**
	 * Read the current view count for a form.
	 *
	 * @param int $form_id Form post ID.
	 * @since 2.12.6
	 * @return int
	 */
	public function get_views( $form_id ) {
		return max( 0, Helper::get_integer_value( get_post_meta( $form_id, self::META_KEY, true ) ) );
	}

	/**
	 * Whether the Views and Conversion Rate columns are shown on the Forms list.
	 *
	 * Despite the setting key's name this governs display, not counting. Counting is
	 * gated on the tracking-started stamp instead: nothing is counted until the
	 * feature is first switched on, and once the window is open, hiding the columns
	 * again only hides them — so switching back on reveals the period rather than a
	 * gap. See should_track().
	 * Off until switched on. Deny is the fallthrough: a missing option, a corrupted
	 * non-array value, and an absent key all return false, so the columns only ever
	 * appear after a deliberate opt-in.
	 *
	 * @since 2.12.6
	 * @return bool
	 */
	public function is_tracking_enabled() {
		$general = get_option( 'srfm_general_settings_options', [] );

		if ( ! is_array( $general ) || ! isset( $general[ self::SETTING_KEY ] ) ) {
			return false;
		}

		return (bool) $general[ self::SETTING_KEY ];
	}

	/**
	 * Repair a toggle that is on while the counting window was never opened.
	 *
	 * The maybe_start_tracking() hooks cover every route that goes through
	 * update_option(), but the setting can also arrive by a path that fires no hook —
	 * a settings import, a partial restore, a direct $wpdb write. Left alone that state
	 * shows the columns while nothing ever counts, and re-saving the identical array
	 * would not repair it because update_option() short-circuits on an unchanged
	 * value.
	 *
	 * Deliberately hooked to admin_init rather than folded into is_tracking_enabled().
	 * That getter is reached from REST GETs and from the weekly analytics cron, so
	 * repairing there stamped the window at "whenever a reader happened to run first"
	 * — on an imported site, most likely a cron pass days later — and silently made a
	 * getter write. Here the write happens in a request that is already administrative
	 * and the stamp keeps meaning "when an administrator had this switched on".
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public function maybe_repair_tracking_window() {
		if ( ! $this->is_tracking_enabled() ) {
			return;
		}

		if ( $this->get_tracking_started_at() > 0 ) {
			return;
		}

		add_option( self::TRACKING_STARTED_OPTION, time(), '', false );
	}

	/**
	 * Whether the current request should be counted as a view.
	 *
	 * Excludes logged-in users who can edit content (author/editor/admin) and
	 * form-builder / Instant Form live previews.
	 *
	 * Gated on the tracking-started stamp, not on the display toggle. The stamp is
	 * written once, when the feature is first switched on, so:
	 *
	 * - a site that has never enabled it counts nothing, which is what "off by
	 *   default" has to mean for a counter — a hidden column that was silently
	 *   accumulating data was never really off;
	 * - once opened, the window stays open. Hiding the columns again only hides
	 *   them, so switching back on reveals the period rather than a gap, and the
	 *   stored counts always cover exactly the period the stamp claims.
	 *
	 * @param WP_REST_Request<array<string,mixed>>|null $request The beacon request, when called from track_view().
	 * @since 2.12.6
	 * @return bool
	 */
	private function should_track( ?WP_REST_Request $request = null ) {
		if ( $this->get_tracking_started_at() <= 0 ) {
			return false;
		}

		// Not is_user_logged_in()/current_user_can(): core's rest_cookie_check_errors()
		// calls wp_set_current_user( 0 ) for any cookie-bearing REST request that
		// carries no nonce, and the beacon deliberately sends only the HMAC token.
		// Reading the current user therefore sees 0 for an administrator browsing
		// their own site and counts them as a visitor. get_submitting_user_id()
		// falls back to wp_validate_auth_cookie(), which the reset does not touch.
		$user_id = Helper::get_submitting_user_id();

		if ( $user_id > 0 && user_can( $user_id, 'edit_posts' ) ) {
			return false;
		}

		// Live previews cannot be detected from $_GET here: should_track() runs on the
		// separate beacon POST, whose request carries none of the previewed page's
		// query string. The client derives the signal from the previewed page's own
		// live_mode and forwards it on the beacon, so read it off this request. Absent
		// or '0' means a normal front-end view.
		if ( $request instanceof WP_REST_Request ) {
			$live_preview = Helper::get_string_value( $request->get_param( 'live_preview' ) );
			if ( '' !== $live_preview && '0' !== $live_preview ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Atomically increment the stored view count for a form.
	 *
	 * @param int $form_id Form post ID.
	 * @since 2.12.6
	 * @return void
	 */
	private function increment_views( $form_id ) {
		global $wpdb;

		// CAST rather than a bare + 1: under STRICT_TRANS_TABLES a non-numeric
		// meta_value (left by an import or a mistaken update_post_meta) makes the
		// arithmetic an error, the query returns false, and this form would stop
		// counting permanently. CAST yields 0 for garbage and the count recovers.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = CAST( meta_value AS UNSIGNED ) + 1 WHERE post_id = %d AND meta_key = %s",
				$form_id,
				self::META_KEY
			)
		);

		// Distinguish 0 (no such row) from false (query failed): treating a failure as
		// a missing row sends it down the insert path, where it fails again silently.
		if ( 0 === $updated ) {
			// First view for this form. add_post_meta()'s $unique flag is a SELECT
			// followed by an INSERT and wp_postmeta carries no unique index, so two
			// concurrent first-views can both insert — after which the UPDATE above
			// would increment both rows forever while get_post_meta() reads only one.
			// Re-run the UPDATE afterwards: if a racing request already created the
			// row, that call increments it and this one adds nothing.
			if ( ! add_post_meta( $form_id, self::META_KEY, 1, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->postmeta} SET meta_value = CAST( meta_value AS UNSIGNED ) + 1 WHERE post_id = %d AND meta_key = %s",
						$form_id,
						self::META_KEY
					)
				);
			} else {
				// The insert succeeded, but so may a racing one: the $unique flag is a
				// SELECT then an INSERT and wp_postmeta has no unique index on
				// ( post_id, meta_key ). Two surviving rows are unrecoverable on their
				// own — every later UPDATE increments both while get_post_meta() reads
				// only the first, so the form silently reports about half its views for
				// the rest of its life. Collapse them now; this runs once per form.
				self::collapse_duplicate_view_rows( $form_id );
			}
		}

		// Keep the post-meta cache consistent after the direct write.
		wp_cache_delete( $form_id, 'post_meta' );
	}

	/**
	 * Fold duplicate view-counter rows for a form back into a single row.
	 *
	 * Only ever reachable when two first-views raced each other into add_post_meta().
	 * The surviving row keeps the SUM, so no counted view is discarded.
	 *
	 * @param int $form_id Form post ID.
	 * @since 2.12.6
	 * @return void
	 */
	private static function collapse_duplicate_view_rows( $form_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$meta_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id ASC",
				$form_id,
				self::META_KEY
			)
		);

		if ( ! is_array( $meta_ids ) || count( $meta_ids ) < 2 ) {
			return;
		}

		$total = 0;
		foreach ( $meta_ids as $meta_id ) {
			$meta = get_metadata_by_mid( 'post', $meta_id );

			// Returns false for a row that has gone since the ids were read. `??`
			// does not cover that: reading a property on false is a warning in its
			// own right, logged under WP_DEBUG_LOG even though the total stays correct.
			if ( ! is_object( $meta ) || ! isset( $meta->meta_value ) ) {
				continue;
			}

			$total += Helper::get_integer_value( $meta->meta_value );
		}

		$keep = array_shift( $meta_ids );

		foreach ( $meta_ids as $meta_id ) {
			delete_metadata_by_mid( 'post', $meta_id );
		}

		update_metadata_by_mid( 'post', $keep, (string) $total );
	}

	/**
	 * Per visitor-IP + form rate limit. Fails closed when the IP is undeterminable.
	 *
	 * @param int $form_id Form post ID.
	 * @since 2.12.6
	 * @return bool True when the request should be blocked.
	 */
	private function is_rate_limited( $form_id ) {
		// Use the connection's REMOTE_ADDR, not Helper::get_visitor_ip() (which trusts
		// client-supplied X-Forwarded-For / Client-IP headers). Otherwise an attacker
		// could send a fresh spoofed forwarded IP per request, get a new rate-limit
		// bucket each time, and inflate the view counter without bound.
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		/**
		 * Filters the client IP used to bucket view-tracking rate limits.
		 *
		 * REMOTE_ADDR is the only value that cannot be spoofed by the client, so it is
		 * the default. Behind a CDN or load balancer it is the proxy's address and is
		 * identical for every visitor, which collapses the whole site into one bucket
		 * and caps counted views at RATE_LIMIT_MAX per form per minute. Sites that
		 * terminate at a trusted proxy can return the real client address here — only
		 * do so when the header it comes from is set by infrastructure you control.
		 *
		 * @since 2.12.6
		 * @param string $ip The connection's REMOTE_ADDR.
		 */
		$ip = (string) apply_filters( 'srfm_form_views_client_ip', $ip );

		if ( empty( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return true; // Fail closed if IP cannot be determined.
		}

		// Bucket on the network, not the exact address. A single attacker routinely
		// controls every address in an IPv6 /64, and a per-address bucket would hand
		// them a fresh allowance — plus a fresh wp_options row — for each one.
		$bucket = self::network_bucket( $ip );

		// The caller's own bucket is charged FIRST, and a caller that is over its own
		// limit returns here without touching the shared per-form counter. Charging
		// the shared ceiling first let one address spend the whole form's budget and
		// then keep spending it: every rejected request still incremented it, so from
		// request 201 onward every genuine visitor was refused for the rest of the
		// window, and each refusal still cost two wp_options writes.
		/**
		 * Filters the per-network counted-view ceiling (per form, per minute).
		 *
		 * A high-traffic form behind a single NAT or CDN edge can organically exceed
		 * the default; raise it only when the extra writes are acceptable.
		 *
		 * @since 2.12.6
		 * @param int $max The default ceiling.
		 */
		$network_max = Helper::get_integer_value( apply_filters( 'srfm_form_views_rate_limit_max', self::RATE_LIMIT_MAX ) );

		if ( self::hit_counter( $bucket . '_' . $form_id ) > $network_max ) {
			return true;
		}

		/**
		 * Filters the per-form counted-view ceiling (summed across visitors, per minute).
		 *
		 * A genuinely popular landing-page form can organically pass the default and
		 * silently drop real views past it; raise it for such forms.
		 *
		 * @since 2.12.6
		 * @param int $max The default ceiling.
		 */
		$form_max = Helper::get_integer_value( apply_filters( 'srfm_form_views_rate_limit_form_max', self::RATE_LIMIT_FORM_MAX ) );

		// The per-form ceiling still bounds a distributed flood, where rotating
		// networks defeats the per-network counter above.
		return self::hit_counter( 'form_' . $form_id ) > $form_max;
	}

	/**
	 * Collapse an IP to the network an attacker would have to rotate out of.
	 *
	 * /24 for IPv4 and /64 for IPv6 — the smallest blocks normally allocated to a
	 * single subscriber, so this bounds one actor without pooling unrelated visitors
	 * any harder than a shared NAT already does.
	 *
	 * @param string $ip Validated IP address.
	 * @since 2.12.6
	 * @return string Opaque bucket key.
	 */
	private static function network_bucket( $ip ) {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$packed = inet_pton( $ip );
			// First 8 bytes = the /64 prefix.
			$prefix = false === $packed ? $ip : substr( $packed, 0, 8 );
		} else {
			$parts  = explode( '.', $ip );
			$prefix = count( $parts ) === 4 ? $parts[0] . '.' . $parts[1] . '.' . $parts[2] : $ip;
		}

		return md5( (string) $prefix );
	}

	/**
	 * Increment a rate-limit counter and return its new value.
	 *
	 * @param string $key Counter key, unique per bucket and form.
	 * @since 2.12.6
	 * @since x.x.x Delegates to Helper::hit_rate_counter(), shared with Form_Submit.
	 * @return int The counter value after this hit, or PHP_INT_MAX when the hit is denied.
	 */
	private static function hit_counter( $key ) {
		return Helper::hit_rate_counter( 'srfm_view_', $key );
	}
}
