<?php
/**
 * Email delivery status.
 *
 * One answer to "will this site's form notifications reach the owner, and if
 * not, what should they do?" — shared by onboarding, the dashboard, Form Checks
 * and the editor so they never disagree.
 *
 * Wording rule for everything this returns: a true wp_mail() means the email was
 * accepted by, or handed off to, the mailer. It is never described as delivered.
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
 * Status
 *
 * States, highest priority first:
 *
 *  - attention              A recent notification was not accepted, or a SureMail
 *                           connection that existed is gone.
 *  - not_configured         Nothing beyond PHP's mail() carries wp_mail().
 *  - suremail_no_connection SureMail is active with no connection.
 *  - limited                SureContact is connected but its account email is
 *                           unverified, so it accepts only a few sends a day.
 *  - other_mailer           Another mail plugin owns sending. SureForms does not
 *                           second-guess its configuration.
 *  - ready                  SureMail has a connection that is not limited.
 *
 * @since 2.12.9
 */
class Status {
	/**
	 * Option holding the outcome of recent notification sends.
	 *
	 * Timestamps, a form id and a reason code only — no addresses or content.
	 *
	 * @since 2.12.9
	 */
	public const OUTCOMES_OPTION = 'srfm_email_delivery_outcomes';

	/**
	 * How long an unrecovered failure keeps the status at "attention".
	 *
	 * A low-traffic form may not send again for days, and without a later success
	 * nothing proves the problem went away. Two weeks is long enough to still be
	 * there when the owner next logs in, short enough not to nag about a fault
	 * fixed outside SureForms' sight.
	 *
	 * @since 2.12.9
	 */
	public const ATTENTION_WINDOW = 14 * DAY_IN_SECONDS;

	/**
	 * Minimum gap between success writes while nothing is wrong.
	 *
	 * Successes are recorded on the public submit path, so a busy form must not
	 * write an option on every submission. A success that follows a failure is
	 * always written: it is the one that clears the warning.
	 *
	 * @since 2.12.9
	 */
	public const SUCCESS_WRITE_INTERVAL = HOUR_IN_SECONDS;

	/**
	 * Repeat failures from one form within this window are not written again.
	 *
	 * The submit path is public, so a visitor who can make wp_mail() fail must
	 * not be able to turn every submission into an option write.
	 *
	 * @since 2.12.9
	 */
	public const FAILURE_WRITE_INTERVAL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Most forms tracked with an open failure; the oldest is dropped first.
	 *
	 * @since 2.12.9
	 */
	public const MAX_OPEN_FORMS = 20;

	/**
	 * Memoised status for this request.
	 *
	 * @var array<string,mixed>|null
	 * @since 2.12.9
	 */
	private static $memo = null;

	/**
	 * Current email delivery status.
	 *
	 * @since 2.12.9
	 * @return array<string,mixed>
	 */
	public static function get_status() {
		if ( null !== self::$memo ) {
			return self::$memo;
		}

		$status = self::compute();

		/**
		 * Filters the email delivery status.
		 *
		 * @since 2.12.9
		 * @param array<string,mixed> $status Status array.
		 */
		$filtered = apply_filters( 'srfm_email_delivery_status', $status );

		self::$memo = is_array( $filtered ) ? $filtered : $status;

		return self::$memo;
	}

	/**
	 * The free SureContact offer shown beside the one-click sign-up.
	 *
	 * One number, read by every screen that mentions it (onboarding, the SMTP
	 * page, the final onboarding step), so a change to the offer is one filter
	 * and never a copy edit. 0 hides the offer.
	 *
	 * @since 2.12.9
	 * @return array<string,int>
	 */
	public static function get_offer() {
		/**
		 * Filters how many free emails a new SureContact account comes with.
		 *
		 * @since 2.12.9
		 * @param int $free_emails Free emails; 0 hides the offer.
		 */
		$free_emails = apply_filters( 'srfm_surecontact_free_emails', 100 );

		return [
			'free_emails' => max( 0, is_numeric( $free_emails ) ? (int) $free_emails : 0 ),
		];
	}

	/**
	 * Record whether a notification was accepted by the mail system.
	 *
	 * Recorded whether or not debug logging is on: this is the site's current
	 * state, not a log, and the attention window bounds how stale it can get.
	 *
	 * SureMail retries a rejected email itself, on its own cron. A retry that
	 * succeeds is not reported back, so the failure stays open until the next
	 * notification from that form is accepted (or the window passes); the
	 * message points at SureMail's logs, which show the retry.
	 *
	 * @since 2.12.9
	 * @param bool   $accepted    Whether wp_mail() accepted the email.
	 * @param int    $form_id     Form the notification belongs to.
	 * @param string $reason      Short reason code for a failure.
	 * @param bool   $entry_saved Whether the submission was stored as an entry.
	 * @return void
	 */
	public static function record_outcome( $accepted, $form_id = 0, $reason = '', $entry_saved = true ) {
		$outcomes = self::get_outcomes();
		$now      = time();

		$form_id = absint( $form_id );

		if ( $accepted ) {
			// A success clears only its own form's failure: a working form B does
			// not prove form A's notifications (often other recipients) go out.
			// Without a form id it is not tied to one form, so it clears them all.
			$recovering = 0 === $form_id ? ! empty( $outcomes['open_forms'] ) : isset( $outcomes['open_forms'][ $form_id ] );
			if ( ! $recovering && $now - $outcomes['last_success_at'] < self::SUCCESS_WRITE_INTERVAL ) {
				return;
			}

			if ( 0 === $form_id ) {
				$outcomes['open_forms']   = [];
				$outcomes['open_details'] = [];
			} else {
				unset( $outcomes['open_forms'][ $form_id ], $outcomes['open_details'][ $form_id ] );
			}

			$outcomes['last_success_at']      = $now;
			$outcomes['consecutive_failures'] = 0;

			if ( empty( $outcomes['open_forms'] ) ) {
				$outcomes['last_result'] = 'success';
			} else {
				// Another form is still failing: the status describes that one, the
				// newest still open, not the failure this success just cleared.
				$outcomes = self::describe_newest_open_failure( $outcomes );
			}
		} else {
			$details = [
				'reason'      => substr( sanitize_key( (string) $reason ), 0, 64 ),
				'mailer'      => Mailer::get_active()['slug'],
				'entry_saved' => (bool) $entry_saved,
			];

			// A connection in place now was made before this failure, even if no
			// SureForms screen has noticed it yet. Recorded before the throttle
			// below, which must know whether the open failure is this setup's.
			if ( 'suremail' === $details['mailer'] ) {
				self::track_suremail_connection( $outcomes, Suremail_Adapter::get_readiness()['connections'], $now - 1 );
			}

			// Throttle repeats of a failure from the same setup only. One that
			// predates the current SureMail connection belongs to an older setup and
			// is ignored by the status, so this failure must be written with its own
			// time -- or a new connection that also fails reads as working.
			$last_for_form = $outcomes['open_forms'][ $form_id ] ?? 0;
			$same_setup    = $last_for_form > (int) ( $outcomes['suremail_connected_at'] ?? 0 );
			if ( $last_for_form > 0 && $same_setup && $now - $last_for_form < self::FAILURE_WRITE_INTERVAL ) {
				// Throttled, but a repeat that says something new (another reason,
				// mailer, or a submission not stored) still updates what is shown.
				if ( ( $outcomes['open_details'][ $form_id ] ?? null ) === $details ) {
					return;
				}
				$outcomes['open_details'][ $form_id ] = $details;
				$outcomes                             = self::describe_newest_open_failure( $outcomes );
				update_option( self::OUTCOMES_OPTION, $outcomes, false );
				self::$memo = null;
				return;
			}

			$outcomes['open_forms'][ $form_id ]   = $now;
			$outcomes['open_details'][ $form_id ] = $details;
			if ( count( $outcomes['open_forms'] ) > self::MAX_OPEN_FORMS ) {
				asort( $outcomes['open_forms'] );
				$outcomes['open_forms']   = array_slice( $outcomes['open_forms'], -self::MAX_OPEN_FORMS, null, true );
				$outcomes['open_details'] = array_intersect_key( $outcomes['open_details'], $outcomes['open_forms'] );
			}

			$outcomes['last_result']              = 'failure';
			$outcomes['last_failure_at']          = $now;
			$outcomes['last_failure_form_id']     = $form_id;
			$outcomes['last_failure_reason']      = $details['reason'];
			$outcomes['last_failure_mailer']      = $details['mailer'];
			$outcomes['last_failure_entry_saved'] = $details['entry_saved'];

			// SureContact may have refused it for its daily allowance: ask for a
			// fresh quota now instead of trusting a copy up to hours old.
			if ( 'suremail' === $details['mailer'] ) {
				Suremail_Adapter::request_quota_refresh();
			}
			++$outcomes['consecutive_failures'];
		}

		update_option( self::OUTCOMES_OPTION, $outcomes, false );
		self::$memo = null;
	}

	/**
	 * Stored send outcomes with defaults filled in.
	 *
	 * @since 2.12.9
	 * @return array{last_result:string,last_success_at:int,last_failure_at:int,last_failure_form_id:int,last_failure_reason:string,last_failure_mailer:string,last_failure_entry_saved:bool,consecutive_failures:int,suremail_connected_at:int,suremail_lost_at:int,open_forms:array<int,int>,open_details:array<int,array{reason:string,mailer:string,entry_saved:bool}>}
	 */
	public static function get_outcomes() {
		$stored = get_option( self::OUTCOMES_OPTION, [] );
		$stored = is_array( $stored ) ? $stored : [];

		return [
			'last_result'              => isset( $stored['last_result'] ) && in_array( $stored['last_result'], [ 'success', 'failure' ], true ) ? $stored['last_result'] : '',
			'last_success_at'          => isset( $stored['last_success_at'] ) ? absint( $stored['last_success_at'] ) : 0,
			'last_failure_at'          => isset( $stored['last_failure_at'] ) ? absint( $stored['last_failure_at'] ) : 0,
			'last_failure_form_id'     => isset( $stored['last_failure_form_id'] ) ? absint( $stored['last_failure_form_id'] ) : 0,
			'last_failure_reason'      => isset( $stored['last_failure_reason'] ) && is_string( $stored['last_failure_reason'] ) ? $stored['last_failure_reason'] : '',
			'last_failure_mailer'      => isset( $stored['last_failure_mailer'] ) && is_string( $stored['last_failure_mailer'] ) ? $stored['last_failure_mailer'] : '',
			'last_failure_entry_saved' => ! isset( $stored['last_failure_entry_saved'] ) || (bool) $stored['last_failure_entry_saved'],
			'consecutive_failures'     => isset( $stored['consecutive_failures'] ) ? absint( $stored['consecutive_failures'] ) : 0,
			'suremail_connected_at'    => isset( $stored['suremail_connected_at'] ) ? absint( $stored['suremail_connected_at'] ) : 0,
			'suremail_lost_at'         => isset( $stored['suremail_lost_at'] ) ? absint( $stored['suremail_lost_at'] ) : 0,
			'open_forms'               => self::read_open_forms( $stored ),
			'open_details'             => self::read_open_details( $stored ),
		];
	}

	/**
	 * Drop the memoised status.
	 *
	 * @since 2.12.9
	 * @return void
	 */
	public static function reset() {
		self::$memo = null;
	}

	/**
	 * Point the last_failure_* fields at the newest form still failing.
	 *
	 * @since 2.12.9
	 * @param array<string,mixed> $outcomes Outcomes with at least one open form.
	 * @return array<string,mixed>
	 */
	private static function describe_newest_open_failure( array $outcomes ) {
		$open = array_map( [ Helper::class, 'get_integer_value' ], Helper::get_array_value( $outcomes['open_forms'] ?? [] ) );
		arsort( $open );
		$form_id     = Helper::get_integer_value( array_key_first( $open ) );
		$all_details = Helper::get_array_value( $outcomes['open_details'] ?? [] );
		$details     = Helper::get_array_value( $all_details[ $form_id ] ?? [] );

		$outcomes['last_result']              = 'failure';
		$outcomes['last_failure_at']          = $open[ $form_id ] ?? 0;
		$outcomes['last_failure_form_id']     = $form_id;
		$outcomes['last_failure_reason']      = Helper::get_string_value( $details['reason'] ?? $outcomes['last_failure_reason'] ?? '' );
		$outcomes['last_failure_mailer']      = Helper::get_string_value( $details['mailer'] ?? $outcomes['last_failure_mailer'] ?? '' );
		$outcomes['last_failure_entry_saved'] = (bool) ( $details['entry_saved'] ?? $outcomes['last_failure_entry_saved'] ?? true );
		return $outcomes;
	}

	/**
	 * Per-form failure details, as form id => reason, mailer, entry_saved.
	 *
	 * @since 2.12.9
	 * @param array<string,mixed> $stored Raw stored outcomes.
	 * @return array<int,array{reason:string,mailer:string,entry_saved:bool}>
	 */
	private static function read_open_details( array $stored ) {
		$details = [];
		if ( isset( $stored['open_details'] ) && is_array( $stored['open_details'] ) ) {
			foreach ( $stored['open_details'] as $form_id => $row ) {
				if ( is_numeric( $form_id ) && is_array( $row ) ) {
					$details[ absint( $form_id ) ] = [
						'reason'      => isset( $row['reason'] ) && is_string( $row['reason'] ) ? $row['reason'] : '',
						'mailer'      => isset( $row['mailer'] ) && is_string( $row['mailer'] ) ? $row['mailer'] : '',
						'entry_saved' => ! isset( $row['entry_saved'] ) || (bool) $row['entry_saved'],
					];
				}
			}
		}
		return $details;
	}

	/**
	 * The open failures from stored outcomes, as form id => failed-at.
	 *
	 * Outcomes stored before failures were kept per form have only the last
	 * failure: it becomes that form's entry, so an open failure survives the
	 * update.
	 *
	 * @since 2.12.9
	 * @param array<string,mixed> $stored Raw stored outcomes.
	 * @return array<int,int>
	 */
	private static function read_open_forms( array $stored ) {
		if ( ! isset( $stored['open_forms'] ) ) {
			$legacy = isset( $stored['last_result'] ) && 'failure' === $stored['last_result'] && ! empty( $stored['last_failure_at'] );
			return $legacy ? [ Helper::get_integer_value( $stored['last_failure_form_id'] ?? 0 ) => Helper::get_integer_value( $stored['last_failure_at'] ) ] : [];
		}

		$open = [];
		if ( is_array( $stored['open_forms'] ) ) {
			foreach ( $stored['open_forms'] as $form_id => $failed_at ) {
				if ( is_numeric( $form_id ) && is_numeric( $failed_at ) && (int) $failed_at > 0 ) {
					$open[ absint( $form_id ) ] = absint( $failed_at );
				}
			}
		}
		return $open;
	}

	/**
	 * Build the status.
	 *
	 * @since 2.12.9
	 * @return array<string,mixed>
	 */
	private static function compute() {
		$mailer   = Mailer::get_active();
		$outcomes = self::get_outcomes();
		$now      = time();

		// From the mailer, not SUREMAILS_VERSION, so every part of the status agrees
		// on one answer — including when a host declares SureMail via the filter.
		$suremail_active = 'suremail' === $mailer['slug'];
		$readiness       = $suremail_active ? Suremail_Adapter::get_readiness() : null;
		$connections     = null !== $readiness ? $readiness['connections'] : 0;

		$connection_lost = false;
		if ( null !== $readiness ) {
			$connection_lost = self::track_suremail_connection( $outcomes, $connections ) || $readiness['default_missing'];
		}

		// A failure from before the current SureMail connection was made belongs to
		// the old setup (often PHP mail() on a fresh ZipWP site), not to this one:
		// connecting is the fix, so it must not keep the site in "attention".
		$connected_at = $connections > 0 ? $outcomes['suremail_connected_at'] : 0;

		// Any form with a failure inside the window, after the connection, that
		// no later success from the same form has cleared.
		$failure_open = false;
		foreach ( $outcomes['open_forms'] as $failed_at ) {
			if ( $failed_at >= $now - self::ATTENTION_WINDOW && $failed_at > $connected_at ) {
				$failure_open = true;
				break;
			}
		}

		$surecontact = null !== $readiness ? $readiness['surecontact'] : [
			'connected'      => false,
			'email_verified' => null,
			'is_paid'        => null,
		];

		// Refreshed in the background by Suremail_Adapter, never fetched here, so
		// building the status stays free of remote calls.
		$surecontact['quota']     = $suremail_active && $surecontact['connected'] ? Suremail_Adapter::get_surecontact_quota() : null;
		$surecontact['quota_low'] = Suremail_Adapter::is_quota_low( $surecontact['quota'] );

		if ( $failure_open || $connection_lost ) {
			$state = 'attention';
		} elseif ( 'none' === $mailer['slug'] ) {
			$state = 'not_configured';
		} elseif ( $suremail_active && 0 === $connections ) {
			$state = 'suremail_no_connection';
		} elseif ( $suremail_active && $surecontact['connected'] && false === $surecontact['email_verified'] ) {
			$state = 'limited';
		} elseif ( $suremail_active ) {
			$state = 'ready';
		} else {
			$state = 'other_mailer';
		}

		// SureMail with nothing to send through: the fix is a connection, whether
		// one was lost or never made -- not SureMail's (empty) email logs.
		$needs_connection = $connection_lost || ( $suremail_active && 0 === $connections );

		// Why mail is failing, only when SureContact itself says so: an
		// unverified account whose daily allowance is used up. A failure asks for
		// a fresh quota (Suremail_Adapter::request_quota_refresh()), which also
		// refreshes email_verified, and a used-up quota is cached for at most
		// half an hour, so neither a verified account nor a reset allowance gets
		// this advice. Any other failure keeps pointing at SureMail's logs.
		$cause = '';
		$quota = $surecontact['quota'];
		if ( 'attention' === $state && $suremail_active && ! $needs_connection && $surecontact['connected'] && false === $surecontact['email_verified']
			&& is_array( $quota ) && 'daily' === $quota['period'] && 0 === $quota['remaining'] ) {
			$cause = 'surecontact_cap';
		}

		$plugin_status = $suremail_active ? 'Activated' : self::get_suremail_plugin_status();
		$provider      = null !== $readiness ? $readiness['provider_label'] : '';
		$copy          = self::get_copy( $state, $mailer, $provider, $needs_connection, $failure_open, $outcomes, $cause );

		return [
			'state'        => $state,
			'cause'        => $cause,
			'severity'     => $copy['severity'],
			'title'        => $copy['title'],
			'message'      => $copy['message'],
			'mailer'       => [
				'slug'  => $mailer['slug'],
				'label' => $mailer['label'],
			],
			'provider'     => $provider,
			'suremail'     => [
				'plugin_status'        => $plugin_status,
				'version'              => Suremail_Adapter::get_version(),
				'connections'          => $connections,
				'onboarding_completed' => $suremail_active && Suremail_Adapter::is_onboarding_completed(),
				'supports_surecontact' => Suremail_Adapter::supports_surecontact(),
			],
			'surecontact'  => $surecontact,
			'last_outcome' => [
				'accepted_at'    => $outcomes['last_success_at'],
				'failed_at'      => $outcomes['last_failure_at'],
				'failed_form_id' => $outcomes['last_failure_form_id'],
				'reason'         => $outcomes['last_failure_reason'],
				'entry_saved'    => $outcomes['last_failure_entry_saved'],
			],
			'action'       => self::get_action( $state, $mailer, $plugin_status, $needs_connection, $cause ),
			'links'        => [
				'setup' => $suremail_active ? Suremail_Adapter::setup_url() : '',
				'logs'  => $suremail_active ? Suremail_Adapter::logs_url() : '',
				'guide' => Helper::get_sureforms_website_url(
					'docs/troubleshooting-email-sending-in-sureforms/',
					[ 'utm_medium' => 'email_delivery_status' ]
				),
			],
			'can_install'  => function_exists( 'wp_get_current_user' ) && current_user_can( 'install_plugins' ),
			'source'       => null !== $readiness ? $readiness['source'] : 'heuristic',
		];
	}

	/**
	 * Remember when SureMail got its connection, and report losing it.
	 *
	 * SureMail rebalances its storage when a connection is deleted — including
	 * when SureContact revokes it remotely — so zero connections looks the same
	 * as never having set one up. The only way to tell is to have seen one.
	 *
	 * `suremail_connected_at` moves forward on every (re)connection, so failures
	 * from before it can be told apart from failures through it. Writes only on
	 * the transitions, not on every read; updates `$outcomes` in place.
	 *
	 * @since 2.12.9
	 * @param array<string,mixed> $outcomes    Stored outcomes, updated in place.
	 * @param int                 $connections Current connection count.
	 * @param int|null            $at          When the connection was made, if known; now otherwise.
	 * @return bool Whether a previously seen connection is gone.
	 */
	private static function track_suremail_connection( array &$outcomes, $connections, $at = null ) {
		$seen = ! empty( $outcomes['suremail_connected_at'] );

		if ( $connections > 0 ) {
			if ( ! $seen || ! empty( $outcomes['suremail_lost_at'] ) ) {
				$outcomes['suremail_connected_at'] = null === $at ? time() : (int) $at;
				$outcomes['suremail_lost_at']      = 0;
				update_option( self::OUTCOMES_OPTION, $outcomes, false );
			}
			return false;
		}

		if ( ! $seen ) {
			return false;
		}

		if ( empty( $outcomes['suremail_lost_at'] ) ) {
			$outcomes['suremail_lost_at'] = time();
			update_option( self::OUTCOMES_OPTION, $outcomes, false );
		}
		return true;
	}

	/**
	 * SureMail's install state when it is not loaded.
	 *
	 * @since 2.12.9
	 * @return string Install | Installed | Activated.
	 */
	private static function get_suremail_plugin_status() {
		if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return Helper::get_plugin_status( Mailer::SUREMAIL_FILE );
	}

	/**
	 * The next step to offer the owner.
	 *
	 * The install_activate and activate steps are carried out by the SureForms UI
	 * through the existing plugin installer, which is why they have no URL.
	 *
	 * @since 2.12.9
	 * @param string                                      $state           Status state.
	 * @param array{slug:string,label:string,file:string} $mailer          Active mailer.
	 * @param string                                      $plugin_status   SureMail install state.
	 * @param bool                                        $needs_connection Whether SureMail has no connection to send through.
	 * @param string                                      $cause            Why mail is failing, from compute(); '' when unknown.
	 * @return array{type:string,label:string,url:string}
	 */
	private static function get_action( $state, array $mailer, $plugin_status, $needs_connection, $cause = '' ) {
		$none = [
			'type'  => 'none',
			'label' => '',
			'url'   => '',
		];

		// SureMail's dashboard carries the verify banner with "Resend verification".
		if ( '' !== $cause ) {
			return [
				'type'  => 'verify_surecontact',
				'label' => __( 'Verify in SureMail', 'sureforms' ),
				'url'   => Suremail_Adapter::admin_url( '/dashboard' ),
			];
		}

		if ( 'not_configured' === $state || ( 'attention' === $state && 'none' === $mailer['slug'] ) ) {
			// Installing SureMail is a promotion, so it follows the same switch as
			// the SureMail menu. The status text stays honest either way.
			if ( Helper::hide_promotions() ) {
				return $none;
			}

			return [
				'type'  => 'Installed' === $plugin_status ? 'activate' : 'install_activate',
				'label' => 'Installed' === $plugin_status ? __( 'Activate SureMail', 'sureforms' ) : __( 'Set up email delivery', 'sureforms' ),
				'url'   => '',
			];
		}

		if ( 'suremail_no_connection' === $state || ( 'attention' === $state && $needs_connection ) ) {
			return [
				'type'  => 'open_suremail',
				'label' => Suremail_Adapter::supports_surecontact() ? __( 'Connect SureContact in SureMail', 'sureforms' ) : __( 'Add a connection in SureMail', 'sureforms' ),
				'url'   => Suremail_Adapter::setup_url(),
			];
		}

		if ( 'limited' === $state ) {
			return [
				'type'  => 'open_suremail',
				'label' => __( 'Finish in SureMail', 'sureforms' ),
				'url'   => Suremail_Adapter::admin_url( '/dashboard' ),
			];
		}

		if ( 'attention' === $state && 'suremail' === $mailer['slug'] ) {
			return [
				'type'  => 'view_logs',
				'label' => __( 'View SureMail logs', 'sureforms' ),
				'url'   => Suremail_Adapter::logs_url(),
			];
		}

		return $none;
	}

	/**
	 * Owner-facing title, message and severity.
	 *
	 * @since 2.12.9
	 * @param string                                      $state           Status state.
	 * @param array{slug:string,label:string,file:string} $mailer          Active mailer.
	 * @param string                                      $provider        SureMail provider label.
	 * @param bool                                        $needs_connection Whether SureMail has no connection to send through.
	 * @param bool                                        $failure_open    Whether a notification failure is unresolved.
	 * @param array<string,mixed>                         $outcomes        Stored outcomes.
	 * @param string                                      $cause            Why mail is failing, from compute(); '' when unknown.
	 * @return array{severity:string,title:string,message:string}
	 */
	private static function get_copy( $state, array $mailer, $provider, $needs_connection, $failure_open, array $outcomes, $cause = '' ) {
		switch ( $state ) {
			case 'attention':
				if ( '' !== $cause ) {
					return [
						'severity' => 'error',
						'title'    => __( 'Verify your SureContact email to keep sending', 'sureforms' ),
						'message'  => __( 'Your SureContact account is not verified, so it accepts only a few emails a day, and today\'s are used up. New form notifications are not sent until you verify the account email or the allowance resets.', 'sureforms' ),
					];
				}

				if ( $needs_connection && ! $failure_open ) {
					return [
						'severity' => 'error',
						'title'    => __( 'SureMail lost its sending connection', 'sureforms' ),
						'message'  => __( 'SureMail no longer has a connection, so form notifications are not handed to an email service. Reconnect in SureMail.', 'sureforms' ),
					];
				}

				if ( 'suremail' === $mailer['slug'] && $needs_connection ) {
					$message = __( 'A recent form notification was not sent, and SureMail has no sending connection yet. Connect one so notifications are handed to an email service.', 'sureforms' );
				} elseif ( 'suremail' === $mailer['slug'] ) {
					$message = __( 'SureMail did not accept a recent form notification. It may retry automatically; SureMail\'s email logs show the reason.', 'sureforms' );
				} elseif ( 'none' === $mailer['slug'] ) {
					$message = __( 'A recent form notification could not be handed to your server\'s mail function. Set up email delivery so notifications are sent through a proper email service.', 'sureforms' );
				} else {
					/* translators: %s: name of the site's mail plugin. */
					$message = sprintf( __( '%s did not accept a recent form notification. Its logs show the reason.', 'sureforms' ), $mailer['label'] );
				}

				if ( empty( $outcomes['last_failure_entry_saved'] ) && 'failure' === $outcomes['last_result'] ) {
					$message .= ' ' . __( 'That form does not store entries, so the submission was not saved anywhere.', 'sureforms' );
				}

				return [
					'severity' => 'error',
					'title'    => __( 'A form notification was not sent', 'sureforms' ),
					'message'  => $message,
				];

			case 'not_configured':
				return [
					'severity' => 'warning',
					'title'    => __( 'Email delivery is not set up', 'sureforms' ),
					'message'  => __( 'Form notifications are handed to your server\'s built-in mail function, which many hosts block or send to spam. Set up email delivery so new entries reach your inbox.', 'sureforms' ),
				];

			case 'suremail_no_connection':
				return [
					'severity' => 'warning',
					'title'    => __( 'SureMail needs a sending connection', 'sureforms' ),
					'message'  => __( 'SureMail is active but has no connection yet, so form notifications are not handed to an email service.', 'sureforms' ),
				];

			case 'limited':
				return [
					'severity' => 'warning',
					'title'    => __( 'SureContact sending is limited', 'sureforms' ),
					'message'  => __( 'Your SureContact account email is not verified yet, so only a few emails a day are accepted. Verify it from the email SureContact sent you.', 'sureforms' ),
				];

			case 'other_mailer':
				return [
					'severity' => 'info',
					/* translators: %s: name of the site's mail plugin. */
					'title'    => sprintf( __( 'Emails are sent through %s', 'sureforms' ), '' !== $mailer['label'] ? $mailer['label'] : __( 'your mail plugin', 'sureforms' ) ),
					'message'  => __( 'Form notifications are handed off to your mail plugin. Manage sending and logs there.', 'sureforms' ),
				];

			default:
				return [
					'severity' => 'success',
					'title'    => __( 'Email delivery is set up', 'sureforms' ),
					'message'  => '' !== $provider
						/* translators: %s: SureMail connection name, e.g. SureContact. */
						? sprintf( __( 'Form notifications are handed off to SureMail (%s).', 'sureforms' ), $provider )
						: __( 'Form notifications are handed off to SureMail.', 'sureforms' ),
				];
		}
	}
}
