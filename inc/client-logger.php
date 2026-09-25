<?php
/**
 * Client error logging.
 *
 * Owns the debug log file that the "Enable Logs" setting writes to: where it
 * lives, what may be written to it, and how large it is allowed to get. Nothing
 * else in the plugin should open that file.
 *
 * The log records form-submission failures reported by the visitor's browser —
 * the HTTP status and duration of the submit request, and the error behind a
 * failure — so a site owner can reproduce a bug and hand the file to support
 * instead of being talked through DevTools.
 *
 * @package SureForms
 * @since   2.12.6
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Client_Logger
 *
 * Design notes that are load-bearing:
 *
 *  - Every method fails silently. The uploads directory is not writable on
 *    hardened or read-only-deploy hosts, and a logger that warns or fatals on a
 *    visitor-facing request is worse than one that records nothing.
 *  - The file name is unguessable and never disclosed. `.htaccess` does nothing
 *    on nginx, so the name is the real protection; the download handler derives
 *    the path itself and takes no filename parameter.
 *  - Over the size cap the log stops accepting writes rather than trimming or
 *    rotating. Someone reproducing a bug must not have the tail of their repro
 *    evicted by newer noise from an unrelated visitor.
 *
 * @since 2.12.6
 */
class Client_Logger {
	/**
	 * Option holding the random component of the log file name.
	 *
	 * @since 2.12.6
	 */
	public const FILENAME_OPTION = 'srfm_client_log_file';

	/**
	 * Consecutive faults before the site owner is told something is wrong.
	 *
	 * One. A fault is already filtered down to what a visitor cannot fix by trying
	 * again -- the request never reached PHP, the response was not JSON, the server
	 * errored, an email could not be sent -- so waiting for a run of them means
	 * staying quiet through the first several lost submissions.
	 *
	 * @since 2.12.6
	 */
	public const FAULT_THRESHOLD = 1;

	/**
	 * Maximum size of the log file in bytes.
	 *
	 * @since 2.12.6
	 */
	public const MAX_FILE_SIZE = 1048576;

	/**
	 * Longest free-text value stored on a single entry, in characters.
	 *
	 * @since 2.12.6
	 */
	public const MAX_TEXT_LENGTH = 500;

	/**
	 * Longest single field key stored on an entry, in characters.
	 *
	 * @since 2.12.6
	 */
	public const MAX_KEY_LENGTH = 100;

	/**
	 * Option holding per-category failure state, keyed by category.
	 *
	 * Shape: [ category => [ 'count' => int, 'form_id' => int, 'form_title' => string,
	 * 'at' => int, 'acked' => int, 'acked_at' => int ] ].
	 *
	 * Kept per category because the three read completely differently to a site
	 * owner: submissions failing means visitors cannot reach you, a notification
	 * failing means you are not hearing about entries that did save, and an
	 * integration failing means a third party is not receiving them. Collapsing
	 * them into one warning would describe none of those accurately.
	 *
	 * @since 2.12.6
	 */
	public const FAILURES_OPTION = 'srfm_client_log_failures';

	/**
	 * Categories a failure can belong to.
	 *
	 * @since 2.12.6
	 */
	public const CATEGORIES = [ 'submission', 'notification', 'integration' ];

	/**
	 * Memoised get_tail() results for this request, keyed by character budget.
	 *
	 * A class property rather than a static inside the method so append() and
	 * clear() can invalidate it: a request that writes to the log and then reads a
	 * tail must not be handed the tail from before the write.
	 *
	 * @var array<string,array{text:string,shown:int,total:int}>
	 * @since 2.12.7
	 */
	private static $tail_memo = [];

	/**
	 * Whether client error logging is currently switched on.
	 *
	 * On by default, including on installs whose stored settings predate the
	 * option. The point of the log is that the evidence already exists when a
	 * support ticket arrives -- a default of off would mean asking the reporter to
	 * enable it and reproduce, which is the round trip this feature removes.
	 *
	 * Costs nothing on a healthy site: only failures are ever written, so a site
	 * whose forms work never creates the file at all.
	 *
	 * This is the one authority. The frontend also carries a flag, but that flag is
	 * baked into cached HTML and can be a full cache TTL out of date, so every
	 * write path re-checks here.
	 *
	 * @since 2.12.6
	 * @since 2.12.8 Filterable through `srfm_enable_logs`.
	 * @return bool
	 */
	public static function is_enabled() {
		$general = get_option( 'srfm_general_settings_options', [] );
		$enabled = ! is_array( $general ) || ! isset( $general['srfm_enable_logs'] ) || (bool) $general['srfm_enable_logs'];

		/**
		 * Filter whether client logging is on, without changing the stored
		 * setting. SureForms Pro's Distraction Free mode turns it off here.
		 *
		 * @since 2.12.8
		 *
		 * @param bool $enabled The stored setting (true when never saved).
		 */
		return (bool) apply_filters( 'srfm_enable_logs', $enabled );
	}

	/**
	 * Whether an entry means the site is broken, rather than the visitor.
	 *
	 * This distinction is the whole basis of the failure notice. Most of what the
	 * log records is routine: a mistyped email, an expired captcha, a declined
	 * card, a submission the server rejected by naming the field to fix. Those are
	 * the form working correctly, and counting them would tell healthy sites to
	 * contact support -- on every install, because logging is on by default.
	 *
	 * A fault is what a visitor cannot resolve by trying again correctly: the
	 * request never reached PHP, the response was not JSON, the server returned an
	 * error status, or a notification email could not be sent.
	 *
	 * @param array<string,mixed> $entry Entry as returned by sanitize_entry().
	 * @since 2.12.6
	 * @return bool
	 */
	public static function is_fault( array $entry ) {
		$type = $entry['type'] ?? '';

		// Allowlist, so an unrecognised or new category is not a fault by default.
		// A visitor-correctable stop never reaches here: sanitize_entry() drops the
		// browser's 'blocked' type before anything is written.
		if ( in_array( $type, [ 'error', 'response', 'message' ], true ) ) {
			return true;
		}

		// 'after_submission' runs after the entry is already saved, so it is only a
		// fault when the server said so. A browser-side failure there -- an aborted
		// fetch as the page unloads, which is what a redirect confirmation does and
		// what `keepalive` exists to survive -- tells us nothing about whether the
		// work ran: the endpoint is guarded by is_after_submission_process_triggered
		// and usually has. Safari spells that abort "TypeError: Load failed", and
		// alarming on it reported healthy sites as broken.
		if ( ! in_array( $type, [ 'network', 'after_submission' ], true ) ) {
			return false;
		}

		$status = isset( $entry['status'] ) ? Helper::get_integer_value( $entry['status'] ) : 0;

		// 403 is the submit token being refused, which on a cached site means the
		// page is serving a token the server will not accept.
		return $status >= 500 || 403 === $status;
	}

	/**
	 * Record a failure against a category and the form it happened on.
	 *
	 * The form is carried because "a form is failing" is not actionable on a site
	 * with twenty of them -- the first thing anyone asks is which one.
	 *
	 * @param string $category   One of self::CATEGORIES.
	 * @param int    $form_id    Form the failure happened on.
	 * @param string $form_title Form title, resolved by the caller.
	 * @since 2.12.6
	 * @return void
	 */
	public static function record_failure( $category, $form_id = 0, $form_title = '' ) {
		// Logging off means nothing is recorded, not merely nothing displayed.
		//
		// The display side was already gated -- get_action_items() skips the
		// first-party items and has_action_item_warnings() returns false -- but the
		// counter kept being written, from the two call sites in form-submit.php
		// that sit beside an append() the enabled check does stop. So a site with
		// logging switched off still accumulated failure state, and switching
		// logging on surfaced every fault recorded while it was off, behind a View
		// details report whose debug log is empty because nothing was written.
		//
		// Gated here rather than at the call sites, for the reason append() states:
		// the guard belongs on the function that writes, not on today's callers.
		// clear_category() and the acknowledge helpers are deliberately left
		// ungated -- they only remove state, and must keep working so nothing is
		// stranded by the toggle.
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( ! in_array( $category, self::CATEGORIES, true ) ) {
			return;
		}

		$failures = self::get_failures();
		$existing = $failures[ $category ] ?? [];

		$failures[ $category ] = [
			'count'      => Helper::get_integer_value( $existing['count'] ?? 0 ) + 1,
			'form_id'    => absint( $form_id ),
			'form_title' => mb_substr( sanitize_text_field( $form_title ), 0, 100 ),
			'at'         => time(),
			// Preserved: a report already made still stands until this new count
			// overtakes it, which is what get_open_failures() compares.
			'acked'      => Helper::get_integer_value( $existing['acked'] ?? 0 ),
			// Carried forward too. This array is rebuilt from a fixed set of keys, so
			// anything not named here is dropped -- and "when did I last report
			// this" quietly disappearing on the next failure is exactly the kind of
			// loss nobody notices until support asks.
			'acked_at'   => Helper::get_integer_value( $existing['acked_at'] ?? 0 ),
		];

		update_option( self::FAILURES_OPTION, $failures, false );
	}

	/**
	 * All recorded failure state.
	 *
	 * @since 2.12.6
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_failures() {
		return Helper::get_array_value( get_option( self::FAILURES_OPTION, [] ) );
	}

	/**
	 * Categories with failures the site owner has not already reported.
	 *
	 * @since 2.12.6
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_open_failures() {
		$open = [];

		foreach ( self::get_failures() as $category => $failure ) {
			if ( ! in_array( $category, self::CATEGORIES, true ) ) {
				continue;
			}

			$count = Helper::get_integer_value( $failure['count'] ?? 0 );

			// Compared on the count, not the clock: both are written to the second,
			// so a failure landing in the same second as the report would look
			// not-newer and be hidden.
			if ( $count > 0 && $count > Helper::get_integer_value( $failure['acked'] ?? 0 ) ) {
				$open[ $category ] = $failure;
			}
		}

		return $open;
	}

	/**
	 * Mark one category as reported.
	 *
	 * @param string $category One of self::CATEGORIES.
	 * @since 2.12.6
	 * @return void
	 */
	public static function acknowledge_category( $category ) {
		$failures = self::get_failures();

		if ( ! isset( $failures[ $category ] ) ) {
			return;
		}

		$failures[ $category ]['acked'] = Helper::get_integer_value( $failures[ $category ]['count'] ?? 0 );

		// Recorded for the report -- "you told us at 14:12" is worth having when
		// support reads the ticket -- but deliberately not what decides whether the
		// notice comes back. The count does that.
		//
		// A timestamp cannot: it is written to the second, so a failure recorded in
		// the same second as the acknowledgement compares equal and gets swallowed.
		// That is the one moment it matters most, because a fault arriving as
		// someone reports the last one is a fault still happening.
		$failures[ $category ]['acked_at'] = time();

		update_option( self::FAILURES_OPTION, $failures, false );
	}

	/**
	 * Forget one category's failures entirely.
	 *
	 * @param string $category One of self::CATEGORIES.
	 * @since 2.12.6
	 * @return void
	 */
	public static function clear_category( $category ) {
		$failures = self::get_failures();

		if ( ! isset( $failures[ $category ] ) ) {
			return;
		}

		unset( $failures[ $category ] );

		update_option( self::FAILURES_OPTION, $failures, false );
	}

	/**
	 * How many submission faults have been recorded.
	 *
	 * @since 2.12.6
	 * @return int
	 */
	public static function get_fault_streak() {
		$failures = self::get_failures();

		return Helper::get_integer_value( $failures['submission']['count'] ?? 0 );
	}

	/**
	 * Whether submissions are failing and it has not already been reported.
	 *
	 * @since 2.12.6
	 * @return bool
	 */
	public static function has_persistent_failures() {
		return self::get_fault_streak() >= self::FAULT_THRESHOLD
			&& isset( self::get_open_failures()['submission'] );
	}

	/**
	 * The recorded acknowledgement for submission failures, if there is one.
	 *
	 * @since 2.12.6
	 * @return array<string,mixed> Empty when nothing has been acknowledged.
	 */
	public static function get_acknowledgement() {
		$failures = self::get_failures();
		$acked    = Helper::get_integer_value( $failures['submission']['acked'] ?? 0 );

		if ( $acked < 1 ) {
			return [];
		}

		return [
			// The last fault time, not the acknowledgement time. Kept under this
			// key because callers already read it as "when the thing happened".
			'at'       => Helper::get_integer_value( $failures['submission']['at'] ?? 0 ),
			// When the owner reported it. Separate, because the two answer
			// different questions and are usually seconds apart on a fresh fault
			// and days apart on an old one.
			'acked_at' => Helper::get_integer_value( $failures['submission']['acked_at'] ?? 0 ),
			'streak'   => $acked,
		];
	}

	/**
	 * When the most recent submission fault happened.
	 *
	 * @since 2.12.6
	 * @return int Unix timestamp, or 0 when nothing has failed.
	 */
	public static function get_last_fault_time() {
		$failures = self::get_failures();

		return Helper::get_integer_value( $failures['submission']['at'] ?? 0 );
	}

	/**
	 * Record that the site owner has reported the current submission failures.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public static function acknowledge_failures() {
		self::acknowledge_category( 'submission' );
	}

	/**
	 * Forget the submission failures after one gets through.
	 *
	 * Hooked - srfm_form_submit, which fires only on the success path.
	 *
	 * Only the submission category is cleared. A submission getting through says
	 * nothing about whether its notification email sent or its integrations ran.
	 * Notification clears on its own path, in Form_Submit::send_email(), once every
	 * recipient for a submission has sent. Integration has no success signal to
	 * clear on yet -- the failures are recorded by pro through
	 * Form_Submit::log_integration_failure() and there is no matching
	 * "it worked" call -- so that category still clears only when the owner
	 * reports it.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public static function reset_fault_streak() {
		self::clear_category( 'submission' );
	}

	/**
	 * Absolute path to the log file, creating its directory if needed.
	 *
	 * @param bool $create Whether to create the directory when it is absent.
	 * @since 2.12.6
	 * @return string Absolute path, or '' when the location is unusable.
	 */
	public static function get_log_path( $create = true ) {
		$uploads = wp_upload_dir();

		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
			return '';
		}

		$dir = trailingslashit( $uploads['basedir'] ) . 'sureforms/logs/';

		if ( ! is_dir( $dir ) ) {
			if ( ! $create || ! wp_mkdir_p( $dir ) ) {
				return '';
			}

			self::protect_directory( $dir );
		}

		return $dir . 'srfm-debug-' . self::get_filename_hash() . '.log';
	}

	/**
	 * Append one validated entry to the log.
	 *
	 * @param array<string,mixed> $entry Entry as returned by sanitize_entry().
	 * @since 2.12.6
	 * @return bool True when the line was written.
	 */
	public static function append( array $entry ) {
		// Any write invalidates a memoised tail, whether or not this one lands.
		self::$tail_memo = [];

		// Checked here as well as at the route, so the guard sits on the function
		// that writes rather than only on today's single caller. Without it any
		// future caller writes to disk on a site that never switched logging on.
		if ( ! self::is_enabled() ) {
			return false;
		}

		if ( empty( $entry ) ) {
			return false;
		}

		// Counted before the file is touched. A full log or an unwritable uploads
		// directory must not stop the site owner being told the form is failing --
		// on a badly broken site those are exactly the conditions that occur.
		// A notification or integration failure records its own category at the call
		// site; everything else reaching here is the submission itself.
		$type = Helper::get_string_value( $entry['type'] ?? '' );

		if ( self::is_fault( $entry ) && 'message' !== $type ) {
			// The after-submission step runs on an entry that is already saved and
			// fires srfm_after_submission_process, which is where integrations and
			// webhooks hook in. Calling that a submission failure told the site owner
			// "their entries were not saved" about entries that were -- the wrong
			// message on the one notice that cannot be dismissed. The category is
			// derived here rather than taken from the entry: the client names what
			// happened, the server decides what it means.
			self::record_failure(
				'after_submission' === $type ? 'integration' : 'submission',
				Helper::get_integer_value( $entry['form_id'] ?? 0 ),
				Helper::get_string_value( $entry['form_title'] ?? '' )
			);
		}

		$path = self::get_log_path();

		if ( '' === $path ) {
			return false;
		}

		// Cap and stop. Deliberately not a trim or a rotate: the person who
		// reproduced the bug is the one whose lines would be discarded.
		if ( self::is_full() ) {
			return false;
		}

		$entry['time'] = gmdate( 'Y-m-d H:i:s' );

		$line = wp_json_encode( $entry );

		if ( ! is_string( $line ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents -- WP_Filesystem can prompt for credentials and is not initialised on a public REST request; this mirrors the raw-handle pattern already used in inc/entries.php. LOCK_EX is insurance for NFS/Windows -- appends of this size are already atomic on POSIX.
		return false !== file_put_contents( $path, $line . "\n", FILE_APPEND | LOCK_EX );
	}

	/**
	 * The most recent whole log lines, up to a character budget.
	 *
	 * An excerpt for a support email. The budget is the caller's, and it is a
	 * ceiling: the excerpt goes into a mailto: URL, which a mail client drops
	 * whole when it runs past its length limit. Newest entries are the ones that
	 * describe the failure being reported, so the tail is the useful end and the
	 * oldest are what a smaller budget drops.
	 *
	 * Whole lines only -- half a JSON object helps nobody -- except when the
	 * newest line alone is over the budget. That one is cut and marked, because
	 * an empty excerpt helps nobody either.
	 *
	 * @param int $max_chars Character budget for the returned text.
	 * @since 2.12.6
	 * @return array{text:string,shown:int,total:int}
	 */
	public static function get_tail( $max_chars = 1200 ) {
		// Memoised per request and per budget. get_action_items() asks once per
		// open failure category and runs twice per admin request -- building the
		// localisation payload and again in the classic renderer -- so a site with
		// three open failures was reading a file capped at 1 MB six times to render
		// one page.
		$max_chars = (int) $max_chars;

		// Keyed by blog as well as budget: get_log_path() hashes the blog id into
		// the filename, so after a switch_to_blog() the same budget is a different
		// file. Unreachable today; nothing switches blogs on this path.
		//
		// Its own variable, not $max_chars reused -- that key is a string, and the
		// byte-budget comparison below coerces "1:1200" to 1, which silently
		// reduces every excerpt to a single line.
		$memo_key = get_current_blog_id() . ':' . $max_chars;

		if ( isset( self::$tail_memo[ $memo_key ] ) ) {
			return self::$tail_memo[ $memo_key ];
		}

		$empty = [
			'text'  => '',
			'shown' => 0,
			'total' => 0,
		];

		$path = self::get_log_path( false );

		if ( '' === $path || ! file_exists( $path ) ) {
			self::$tail_memo[ $memo_key ] = $empty;

			return $empty;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file, WordPress.WP.AlternativeFunctions.file_system_read_file -- Reading a file this class owns; WP_Filesystem would prompt for credentials and is unavailable here.
		$lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

		if ( ! is_array( $lines ) || empty( $lines ) ) {
			self::$tail_memo[ $memo_key ] = $empty;

			return $empty;
		}

		$total = count( $lines );
		$kept  = [];
		$used  = 0;

		foreach ( array_reverse( $lines ) as $line ) {
			$length = strlen( $line ) + 1;

			if ( $used + $length > $max_chars && ! empty( $kept ) ) {
				break;
			}

			// Always keep one line, since an empty excerpt is worse than a long one --
			// but never past the budget, which is a ceiling the caller relies on. The
			// start is kept because it names what failed. Lines are wp_json_encode()d,
			// so they are ASCII and a byte cut cannot split a character.
			if ( $length > $max_chars ) {
				$marker = ' [truncated]';
				$line   = substr( $line, 0, max( 0, $max_chars - strlen( $marker ) ) ) . $marker;
			}

			array_unshift( $kept, $line );
			$used += $length;
		}

		self::$tail_memo[ $memo_key ] = [
			'text'  => implode( "\n", $kept ),
			'shown' => count( $kept ),
			'total' => $total,
		];

		return self::$tail_memo[ $memo_key ];
	}

	/**
	 * Whether the log has reached its size cap.
	 *
	 * @since 2.12.6
	 * @return bool
	 */
	public static function is_full() {
		return self::get_file_size() >= self::MAX_FILE_SIZE;
	}

	/**
	 * Current size of the log file in bytes.
	 *
	 * @since 2.12.6
	 * @return int
	 */
	public static function get_file_size() {
		$path = self::get_log_path( false );

		if ( '' === $path || ! file_exists( $path ) ) {
			return 0;
		}

		$size = filesize( $path );

		return is_int( $size ) ? $size : 0;
	}

	/**
	 * Delete the log file.
	 *
	 * @since 2.12.6
	 * @return bool
	 */
	public static function clear() {
		self::$tail_memo = [];

		$path = self::get_log_path( false );

		if ( '' === $path || ! file_exists( $path ) ) {
			return true;
		}

		return wp_delete_file_from_directory( $path, dirname( $path ) );
	}

	/**
	 * Reduce a caller-supplied payload to the fixed shape the log accepts.
	 *
	 * The endpoint never appends caller text directly. Redaction governs values;
	 * this governs shape. Without it, "we redact the field values" would still
	 * leave an anonymous caller writing arbitrary content into a file an
	 * administrator later opens.
	 *
	 * @param array<string,mixed> $raw Decoded request payload.
	 * @since 2.12.6
	 * @return array<string,mixed> Empty when nothing usable survived.
	 */
	public static function sanitize_entry( array $raw ) {
		// 'blocked' is deliberately absent. It is the browser's label for a stop the
		// visitor can clear themselves -- a required field left empty, an expired
		// captcha, a declined card, a rejection naming the field to fix -- and those
		// were the most common lines in a real log. Every one of them was pasted into
		// support reports about some other failure and consumed budget the log does
		// not give back, because it stops at its cap rather than rotating. Dropped at
		// the shape gate so no caller, present or future, can write one.
		$allowed_types = [ 'network', 'response', 'error', 'message', 'after_submission' ];
		$type          = isset( $raw['type'] ) ? sanitize_key( Helper::get_string_value( $raw['type'] ) ) : '';

		if ( ! in_array( $type, $allowed_types, true ) ) {
			return [];
		}

		$entry = [
			'type'    => $type,
			'form_id' => isset( $raw['form_id'] ) ? absint( Helper::get_integer_value( $raw['form_id'] ) ) : 0,
		];

		foreach ( [ 'message', 'source', 'body', 'form_title' ] as $key ) {
			if ( ! isset( $raw[ $key ] ) ) {
				continue;
			}

			$text = self::scrub_text( Helper::get_string_value( $raw[ $key ] ) );

			if ( '' !== $text ) {
				$entry[ $key ] = $text;
			}
		}

		foreach ( [ 'status', 'duration_ms', 'line' ] as $key ) {
			if ( isset( $raw[ $key ] ) ) {
				$entry[ $key ] = absint( Helper::get_integer_value( $raw[ $key ] ) );
			}
		}

		if ( isset( $raw['field_keys'] ) && is_array( $raw['field_keys'] ) ) {
			$keys = [];

			// Both the count and each key's length. Capping only the count left one
			// request able to write ~1MB of field keys and fill the log in a single
			// call -- and because the log stops rather than evicting, that silently
			// disabled the feature until an admin cleared it. A real key is
			// `srfm-input-lbl-<base64>`, far inside this bound.
			foreach ( array_slice( $raw['field_keys'], 0, 100 ) as $field_key ) {
				// Through scrub_text() like every other free-text value. A key is
				// supposed to be `srfm-input-lbl-<base64>`, but the array arrives
				// from the browser and nothing server-side guarantees that, so a
				// caller is free to put an address or a token in one.
				//
				// wp_check_invalid_utf8() is kept because scrub_text() is not a
				// drop-in for sanitize_text_field(): invalid UTF-8 reaching
				// wp_json_encode() in append() makes it return false and drop the
				// whole line -- after record_failure() has already incremented the
				// counter, leaving a banner with no log line behind it.
				$keys[] = mb_substr(
					self::scrub_text( wp_check_invalid_utf8( Helper::get_string_value( $field_key ) ) ),
					0,
					self::MAX_KEY_LENGTH
				);
			}

			$entry['field_keys'] = $keys;
		}

		// A type alone says nothing. Require at least one substantive value.
		$has_detail = isset( $entry['message'] ) || isset( $entry['body'] ) || isset( $entry['status'] );

		return $has_detail ? $entry : [];
	}

	/**
	 * Strip identifying detail out of free text and clamp its length.
	 *
	 * Redacting submitted field values is not sufficient on its own: error text
	 * interpolates user input constantly ("Invalid email: someone@example.com"),
	 * and a page URL routinely carries an address or a reset key in its query
	 * string. Whitespace is collapsed as a log-injection guard, matching
	 * inc/ai-form-builder/ai-helper.php.
	 *
	 * Removes, in order: JSON slash-escaping, so the rules below can see URLs at
	 * all; credentials in a URL's userinfo; query strings and fragments; a foreign
	 * URL's path past its first segment, keeping same-origin paths intact because
	 * those are stack frames and the path is the diagnosis; the value following a
	 * name that identifies a credential; email addresses; and long digit runs.
	 *
	 * A single-segment foreign path is truncated whole rather than kept, which is
	 * the safe direction.
	 *
	 * What it cannot remove is a name, a street address or a free-text message
	 * body -- those have no shape to match, so the log excerpt this produces
	 * should still be treated as personal data.
	 *
	 * @param string $text Raw text.
	 * @since 2.12.6
	 * @return string
	 */
	public static function scrub_text( $text ) {
		if ( '' === $text ) {
			return '';
		}

		// Clamped before the rules run, not after. Without it every pattern below
		// is applied to whatever the caller sent, however long that is.
		$text = mb_substr( $text, 0, self::MAX_TEXT_LENGTH * 4 );

		// A WP REST error body arrives slash-escaped -- wp_json_encode() escapes
		// "/" and WP_REST_Server::serve_request() does not pass
		// JSON_UNESCAPED_SLASHES -- and two of the four body sinks log the raw
		// response text rather than the decoded object. Without this every URL
		// rule below misses every URL in the largest sink, including the webhook
		// tokens they exist for.
		$text = str_replace( '\\/', '/', $text );

		// Credentials in the userinfo position, before the host rules see them.
		$text = (string) preg_replace( '#(https?://)[^\s/@]+@#i', '$1[credentials]@', $text );

		// Drop query strings and fragments wholesale rather than allowlisting
		// parameters. A token after # is just as sensitive as one after ?.
		$text = (string) preg_replace( '#(https?://[^\s?\#]+)[?\#][^\s"\'<>,;)\]}]*#i', '$1', $text );

		$site_host = Helper::get_string_value( wp_parse_url( home_url(), PHP_URL_HOST ) );

		// Keep the origin and the first path segment of a foreign URL, drop the
		// rest: a webhook credential sits in the path as often as in the query,
		// and Slack and Discord both put theirs there.
		//
		// Same-origin URLs are exempt. `source` is a stack frame, not a page
		// address -- assets/js/unminified/form-submit.js takes
		// error.stack.split( "\n" )[1] -- so truncating our own paths deletes the
		// filename, the line and column, and which plugin threw, which is the
		// whole diagnosis. A third-party credential is never same-origin.
		//
		// The port is stripped before comparing. The pattern captures the whole
		// authority, so a site served on a non-default port produced frames reading
		// `example.test:8443`, while $site_host is PHP_URL_HOST and never carries a
		// port -- every own frame failed the check and was truncated to /[path],
		// which is the case this exemption exists for. Same host, different port is
		// treated as ours: on a WordPress install that is the same site behind a dev
		// server or a proxy, and the alternative is deleting the diagnosis.
		$text = (string) preg_replace_callback(
			'#(https?://)([^\s/]+)((?:/[^\s/]*)?)/[^\s"\'<>,;)\]}]+#i',
			static function ( $matches ) use ( $site_host ) {
				// Trailing :digits only, so an IPv6 literal keeps its brackets and
				// its own colons -- [::1]:8080 becomes [::1], which is the form
				// wp_parse_url() returns for one.
				$host = (string) preg_replace( '/:\d+$/', '', $matches[2] );

				if ( '' !== $site_host && 0 === strcasecmp( $host, $site_host ) ) {
					return $matches[0];
				}

				return $matches[1] . $matches[2] . $matches[3] . '/[path]';
			},
			$text
		);

		// Credentials named in the text itself. The name is matched as a whole
		// identifier, so a keyword with a prefix or suffix is still caught --
		// AWS_SECRET_ACCESS_KEY, stripe_secret_key, X-Hub-Signature. And a
		// separator is required, so ordinary prose survives: "Invalid token
		// provided" and "password protected" are the most common things support
		// reads out of this log, and an earlier version redacted both. `bearer`
		// and `basic` are the exception, because those carry the value after a
		// space with no separator at all.
		$text = (string) preg_replace(
			'/(\b[\w.-]*(?:api[_-]?key|key|secret|token|password|passwd|pwd|auth|credential|signature)[\w.-]*["\']?\s*[:=]\s*["\']?|\b(?:bearer|basic)\s+)[^\s"\',;&]{8,}/i',
			'$1[redacted]',
			$text
		);

		// Email addresses.
		$text = (string) preg_replace( '/[\w.+-]+@[\w-]+\.[\w.-]+/', '[email]', $text );

		// Long digit runs: card numbers, phone numbers, ids. Separators are matched
		// too, because a real phone number is written 555-123-4567 or (555) 123-4567
		// and a contiguous-digits rule never sees it.
		$text = (string) preg_replace( '/\+?\d[\d\s().-]{5,}\d/', '[number]', $text );

		$text = (string) preg_replace( '/\s+/', ' ', $text );

		return mb_substr( trim( wp_strip_all_tags( $text ) ), 0, self::MAX_TEXT_LENGTH );
	}

	/**
	 * Random component of the log file name, generated once and reused.
	 *
	 * The blog id is part of the input because wp_salt() is network-wide: a
	 * salt-only hash would be identical on every site of a multisite network, and
	 * older subdirectory installs can share one uploads directory.
	 *
	 * @since 2.12.6
	 * @return string
	 */
	private static function get_filename_hash() {
		$hash = get_option( self::FILENAME_OPTION, '' );

		if ( is_string( $hash ) && 32 === strlen( $hash ) && ctype_xdigit( $hash ) ) {
			return $hash;
		}

		$hash = hash_hmac( 'md5', 'srfm-client-log|' . get_current_blog_id(), wp_salt( 'auth' ) );

		update_option( self::FILENAME_OPTION, $hash, false );

		return $hash;
	}

	/**
	 * Write the directory guards, best effort.
	 *
	 * An index.html rather than index.php: the nginx failure mode is `autoindex on`
	 * producing a listing, and an index.html suppresses that. .htaccess covers
	 * Apache and is inert on nginx, which is why the unguessable file name — not
	 * these files — is what actually protects the log.
	 *
	 * @param string $dir Directory to guard.
	 * @since 2.12.6
	 * @return void
	 */
	private static function protect_directory( $dir ) {
		$guards = [
			'.htaccess'  => "# Apache 2.4\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n# Apache 2.2\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n",
			'index.html' => '',
		];

		foreach ( $guards as $name => $contents ) {
			if ( file_exists( $dir . $name ) ) {
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents -- Best-effort directory guard written at creation time; WP_Filesystem may prompt for credentials and is unavailable on the public request that first creates this directory.
			file_put_contents( $dir . $name, $contents );
		}
	}
}
