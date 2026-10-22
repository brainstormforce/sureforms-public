<?php
/**
 * Multilingual String Backfill.
 *
 * Registers String Packages for forms that already existed before the
 * multilingual integration became active. Without this, a form's strings only
 * reach the multilingual provider when the form is next re-saved, so existing
 * forms stay invisible to WPML until manually opened and saved again.
 *
 * @package sureforms.
 * @since 2.12.3
 */

namespace SRFM\Inc\Compatibility\Multilingual;

use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * String_Backfill.
 *
 * One-time (per {@see self::SCHEMA_VERSION}) pass that runs
 * {@see String_Collector::collect()} for every existing form once a multilingual
 * provider is active, so their String Packages are registered without needing a
 * manual re-save.
 *
 * @since 2.12.3
 */
class String_Backfill {
	use Get_Instance;

	/**
	 * Option that records the schema version the backfill last completed for.
	 *
	 * @since 2.12.3
	 */
	public const DONE_OPTION = 'srfm_wpml_backfill_done';

	/**
	 * Backfill schema version. Bump this ONLY when the set of strings registered
	 * by String_Collector changes, to force a one-time re-backfill. Deliberately
	 * NOT tied to SRFM_VER, so ordinary plugin releases don't re-enqueue a job
	 * per form on every update.
	 *
	 * @since 2.12.3
	 */
	public const SCHEMA_VERSION = '3';

	/**
	 * Option holding the in-progress run lock: [ 'schema' => string, 'started' => int ].
	 *
	 * Separate from DONE_OPTION on purpose. DONE_OPTION means "the whole pass finished";
	 * this means "a pass is currently running". Conflating them meant a run that died
	 * mid-way looked complete forever.
	 *
	 * @since 2.12.3
	 */
	public const LOCK_OPTION = 'srfm_wpml_backfill_lock';

	/**
	 * How long a run lock is honoured before it is treated as stale and reclaimed.
	 *
	 * Bounds the failure mode where a worker dies without chaining: the next admin load
	 * after this window restarts the pass, and the per-form markers make the replay cheap.
	 *
	 * @since 2.12.3
	 */
	public const LOCK_TTL = 3600;

	/**
	 * Action Scheduler hook that backfills a single form.
	 *
	 * @since 2.12.3
	 */
	public const HOOK = 'srfm_wpml_backfill_form';

	/**
	 * Action Scheduler hook that backfills one page of forms and chains the next.
	 *
	 * @since 2.12.3
	 */
	public const HOOK_BATCH = 'srfm_wpml_backfill_batch';

	/**
	 * Per-form meta key recording the schema version a form was last backfilled for.
	 *
	 * Makes the pass idempotent per form, so a replayed or partially-failed batch
	 * never re-registers packages it already handled.
	 *
	 * @since 2.12.3
	 */
	public const FORM_MARKER = '_srfm_wpml_backfilled';

	/**
	 * Forms processed per batch action.
	 *
	 * @since 2.12.3
	 */
	private const BATCH_SIZE = 50;

	/**
	 * Constructor. Schedules the backfill on admin load and handles each queued form.
	 *
	 * @since 2.12.3
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'maybe_schedule' ] );
		add_action( self::HOOK_BATCH, [ $this, 'backfill_batch' ], 10, 1 );
		// Void wrapper: backfill_one() returns a bool for the batch's progress accounting,
		// and an action callback must not return a value. HOOK stays registered so any
		// per-form actions still pending from the previous implementation drain cleanly.
		add_action( self::HOOK, [ $this, 'handle_backfill_action' ], 10, 1 );
	}

	/**
	 * Queue a one-time backfill of all existing forms when a provider is active.
	 *
	 * Bails when the request is not a privileged admin page load, when no
	 * multilingual provider is active, when the backfill has already completed for
	 * the current schema version, or when Action Scheduler is unavailable.
	 *
	 * Enqueues ONE paginating batch action rather than one action per form. Action
	 * Scheduler's `$unique` flag matches on (status, hook, group_id) only — it does
	 * NOT consider `args` — so a per-form fan-out with `$unique = true` inserted the
	 * first form and then silently wrote 0 rows for every subsequent one, leaving
	 * exactly one form backfilled per site. A single self-chaining batch action never
	 * has more than one pending row for this hook, so uniqueness is irrelevant and
	 * the query stays bounded.
	 *
	 * @since 2.12.3
	 * @return void
	 */
	public function maybe_schedule(): void {
		// Only a genuine, privileged admin page load may start this. `admin_init`
		// also fires inside admin-ajax.php, which is reachable unauthenticated, and
		// this pass is state-changing and touches every form.
		if ( wp_doing_ajax() || wp_doing_cron() || ! Helper::current_user_can() ) {
			return;
		}

		if ( ! Multilingual_Manager::get_instance()->provider()->is_active() ) {
			return;
		}

		if ( self::SCHEMA_VERSION === get_option( self::DONE_OPTION ) ) {
			return;
		}

		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return;
		}

		// A run already in flight (a fresh, non-stale lock) — don't start a second chain.
		if ( $this->is_run_in_flight() ) {
			return;
		}

		// A stale lock left by a worker that died without chaining. Clear it so the
		// atomic add_option() below can re-take it.
		delete_option( self::LOCK_OPTION );

		// Take the lock atomically BEFORE enqueuing. add_option() is an INSERT that returns
		// false when the row already exists, so two concurrent admin requests can't both
		// pass is_run_in_flight() and both start a chain — exactly one wins. Completion is
		// NOT recorded here; that only happens when the final page comes back empty.
		// autoload=false: this admin-only marker never needs to load on the front end.
		$acquired = add_option(
			self::LOCK_OPTION,
			[
				'schema'  => self::SCHEMA_VERSION,
				'started' => time(),
			],
			'',
			false
		);

		if ( ! $acquired ) {
			// Another request won the race.
			return;
		}

		// Verify the action actually got queued. as_enqueue_async_action() returns 0 when
		// the insert wrote no rows, silently and without raising — so an unchecked call
		// could leave the pass never started with the lock held.
		$action_id = as_enqueue_async_action( self::HOOK_BATCH, [ 'paged' => 1 ], 'srfm' );

		if ( empty( $action_id ) ) {
			// Release immediately so the next admin load retries rather than waiting out
			// the whole lock TTL.
			delete_option( self::LOCK_OPTION );

			/**
			 * Fires when the backfill's first batch action could not be queued.
			 *
			 * @since 2.12.3
			 * @param string $schema_version The schema version being backfilled.
			 */
			do_action( 'srfm_wpml_backfill_enqueue_failed', self::SCHEMA_VERSION );
		}
	}

	/**
	 * Backfill one page of forms, then chain the next page.
	 *
	 * Runs String_Collector directly per form (rather than enqueuing a child action
	 * each) and re-enqueues itself for the following page until a page comes back
	 * empty. Each form is marked with self::FORM_MARKER on success, so a batch that
	 * dies part-way can be replayed without re-registering packages it already did.
	 *
	 * @param mixed $paged 1-based page number.
	 * @since 2.12.3
	 * @return void
	 */
	public function backfill_batch( $paged = 1 ): void {
		// Args arrive from Action Scheduler, so the type is not guaranteed.
		$paged = is_numeric( $paged ) ? max( 1, (int) $paged ) : 1;

		// The provider can be deactivated between scheduling and execution. Abandon the
		// run and release the lock WITHOUT recording completion, so a later admin load
		// restarts the pass once a provider is active again.
		if ( ! Multilingual_Manager::get_instance()->provider()->is_active() ) {
			delete_option( self::LOCK_OPTION );

			/**
			 * Fires when a backfill batch aborts because no multilingual provider is active.
			 *
			 * @since 2.12.3
			 * @param int $paged The page the run stopped on.
			 */
			do_action( 'srfm_wpml_backfill_aborted', $paged );
			return;
		}

		// Refresh the lock so a long multi-page run isn't reclaimed as stale mid-flight.
		update_option(
			self::LOCK_OPTION,
			[
				'schema'  => self::SCHEMA_VERSION,
				'started' => time(),
			],
			false
		);

		$form_ids = get_posts(
			[
				'post_type'      => SRFM_FORMS_POST_TYPE,
				'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future' ],
				'fields'         => 'ids',
				'posts_per_page' => self::BATCH_SIZE, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- Bounded constant (50); batching is the point of this query.
				'paged'          => $paged,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			]
		);

		// An empty page means every form has been walked — THIS is the only place the pass
		// is recorded as complete, and only after all pages actually finished.
		if ( empty( $form_ids ) || ! is_array( $form_ids ) ) {
			update_option( self::DONE_OPTION, self::SCHEMA_VERSION, false );
			delete_option( self::LOCK_OPTION );

			/**
			 * Fires when the multilingual string backfill has completed every page.
			 *
			 * @since 2.12.3
			 * @param string $schema_version The schema version that completed.
			 */
			do_action( 'srfm_wpml_backfill_completed', self::SCHEMA_VERSION );
			return;
		}

		$collected = 0;
		foreach ( $form_ids as $form_id ) {
			if ( $this->backfill_one( (int) $form_id ) ) {
				$collected++;
			}
		}

		// Nothing on a non-empty page could be collected — the provider went away
		// mid-page. Stop and release rather than chaining and marking progress.
		if ( 0 === $collected ) {
			delete_option( self::LOCK_OPTION );
			do_action( 'srfm_wpml_backfill_aborted', $paged );
			return;
		}

		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			// Cannot chain — release the lock so the next admin load resumes. Already-marked
			// forms are skipped, so the replay only redoes the unfinished tail.
			delete_option( self::LOCK_OPTION );
			return;
		}

		// One pending action at a time, so $unique is unnecessary here.
		$next_id = as_enqueue_async_action( self::HOOK_BATCH, [ 'paged' => $paged + 1 ], 'srfm' );

		if ( empty( $next_id ) ) {
			// The chain broke. Release so a later admin load restarts; FORM_MARKER means it
			// resumes rather than starting over.
			delete_option( self::LOCK_OPTION );
			do_action( 'srfm_wpml_backfill_enqueue_failed', self::SCHEMA_VERSION );
		}
	}

	/**
	 * Action-hook entry point for a single-form backfill.
	 *
	 * Exists so backfill_one() can report success to the batch loop while the hook
	 * callback itself returns nothing.
	 *
	 * @param mixed $form_id The form post ID to backfill.
	 * @since 2.12.3
	 * @return void
	 */
	public function handle_backfill_action( $form_id ): void {
		$this->backfill_one( is_numeric( $form_id ) ? (int) $form_id : 0 );
	}

	/**
	 * Backfill a single form's String Package.
	 *
	 * @param int $form_id The form post ID to backfill.
	 * @since 2.12.3
	 * @since 2.12.3 Returns whether the form was (or already had been) collected, and only
	 *               writes the marker on a confirmed collection.
	 * @return bool True when this form is backfilled for the current schema version.
	 */
	public function backfill_one( int $form_id ): bool {
		if ( $form_id <= 0 ) {
			return false;
		}

		// Only ever touch SureForms forms. backfill_batch()'s get_posts() is already
		// post-type-scoped, but self::HOOK stays registered for actions queued by the
		// previous implementation and routes any numeric arg through
		// handle_backfill_action() — so guard here too, mirroring
		// String_Collector::on_form_delete(), so a stray do_action( self::HOOK, $id )
		// can't write our meta onto (or run collect() against) an arbitrary post.
		if ( SRFM_FORMS_POST_TYPE !== get_post_type( $form_id ) ) {
			return false;
		}

		// Idempotency guard: a replayed batch must not re-register packages for forms
		// it already processed under this schema version.
		if ( self::SCHEMA_VERSION === get_post_meta( $form_id, self::FORM_MARKER, true ) ) {
			return true;
		}

		// collect() returns false when the provider is inactive, in which case it did
		// nothing. Marking the form regardless would permanently skip it — the exact
		// failure this backfill exists to prevent — so the marker is only written on a
		// confirmed collection.
		if ( ! String_Collector::get_instance()->collect( $form_id ) ) {
			return false;
		}

		update_post_meta( $form_id, self::FORM_MARKER, self::SCHEMA_VERSION );

		return true;
	}
	/**
	 * Whether a backfill run is currently in flight for this schema version.
	 *
	 * A lock older than self::LOCK_TTL is treated as stale and ignored, which is how an
	 * interrupted run (worker died before chaining the next page, host restarted, action
	 * purged) recovers instead of stalling forever. Replaying is cheap because
	 * self::FORM_MARKER skips forms already done.
	 *
	 * @since 2.12.3
	 * @return bool
	 */
	private function is_run_in_flight(): bool {
		$lock = get_option( self::LOCK_OPTION );

		if ( ! is_array( $lock ) ) {
			return false;
		}

		// A lock from an older schema version is irrelevant to this pass.
		if ( self::SCHEMA_VERSION !== ( $lock['schema'] ?? '' ) ) {
			return false;
		}

		$started = isset( $lock['started'] ) && is_numeric( $lock['started'] ) ? (int) $lock['started'] : 0;

		return time() - $started < self::LOCK_TTL;
	}
}
