<?php
/**
 * Read-only view of SureMail's sending state.
 *
 * SureMail has no public "is sending ready?" API yet, so this reads the option
 * SureMail stores its connections in. Everything here is defensive: the option
 * is SureMail's private storage, and a shape change on their side must degrade
 * to "unknown", never to a fatal on a SureForms screen.
 *
 * When SureMail ships a readiness contract (a `suremails_delivery_readiness`
 * filter returning `contract >= 1`), it is used instead and the option read
 * becomes dead code to remove.
 *
 * @package SureForms
 * @since   2.12.9
 */

namespace SRFM\Inc\Email_Delivery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Suremail_Adapter
 *
 * @since 2.12.9
 */
class Suremail_Adapter {
	/**
	 * SureMail's connections option.
	 *
	 * @since 2.12.9
	 */
	public const CONNECTIONS_OPTION = 'suremails_connections';

	/**
	 * SureMail's onboarding-completed option ('yes' once finished).
	 *
	 * @since 2.12.9
	 */
	public const ONBOARDING_OPTION = 'suremails_onboarding_completed';

	/**
	 * First SureMail release with the SureContact provider.
	 *
	 * @since 2.12.9
	 */
	public const SURECONTACT_MIN_VERSION = '2.0.0';

	/**
	 * SureMail's connection type for SureContact.
	 *
	 * @since 2.12.9
	 */
	public const SURECONTACT_TYPE = 'SURECONTACT';

	/**
	 * Transient holding the last known SureContact quota.
	 *
	 * @since 2.12.9
	 */
	public const QUOTA_TRANSIENT = 'srfm_surecontact_quota';

	/**
	 * Cron hook that refreshes the quota in the background.
	 *
	 * @since 2.12.9
	 */
	public const QUOTA_REFRESH_HOOK = 'srfm_refresh_surecontact_quota';

	/**
	 * How long a known quota is trusted, in seconds.
	 *
	 * @since 2.12.9
	 */
	public const QUOTA_TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * How long a nearly used-up quota is trusted, in seconds. Shorter, so an
	 * upgrade or a period reset clears the warning soon.
	 *
	 * @since 2.12.9
	 */
	public const QUOTA_LOW_TTL = HOUR_IN_SECONDS;

	/**
	 * How long a used-up quota is trusted, in seconds. Its warning cannot be
	 * dismissed, so it must not outlive an upgrade by long.
	 *
	 * @since 2.12.9
	 */
	public const QUOTA_EXHAUSTED_TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Share of the cap left at which the quota counts as nearly used up.
	 *
	 * @since 2.12.9
	 */
	public const QUOTA_LOW_SHARE = 0.2;

	/**
	 * SureMail's admin page slug.
	 *
	 * @since 2.12.9
	 */
	private const PAGE_SLUG = 'suremail';

	/**
	 * Whether SureMail is loaded.
	 *
	 * @since 2.12.9
	 * @return bool
	 */
	public static function is_active() {
		return defined( 'SUREMAILS_VERSION' );
	}

	/**
	 * Loaded SureMail version, or '' when it is not active.
	 *
	 * @since 2.12.9
	 * @return string
	 */
	public static function get_version() {
		return self::is_active() && is_string( constant( 'SUREMAILS_VERSION' ) ) ? (string) constant( 'SUREMAILS_VERSION' ) : '';
	}

	/**
	 * Whether the loaded SureMail can connect SureContact.
	 *
	 * @since 2.12.9
	 * @return bool
	 */
	public static function supports_surecontact() {
		$version = self::get_version();
		return '' !== $version && version_compare( $version, self::SURECONTACT_MIN_VERSION, '>=' );
	}

	/**
	 * Whether the owner finished SureMail's own onboarding.
	 *
	 * @since 2.12.9
	 * @return bool
	 */
	public static function is_onboarding_completed() {
		return 'yes' === get_option( self::ONBOARDING_OPTION, 'no' );
	}

	/**
	 * SureMail's sending readiness, normalised.
	 *
	 * `default_missing` flags a default that points at a row which no longer
	 * exists. SureMail's own delete paths (local and the SureContact-side
	 * disconnect webhook) rebalance the default, so this only catches storage
	 * left inconsistent some other way. A disconnect therefore reads the same as
	 * "never set up" here; Status remembers having seen a connection to tell the
	 * two apart.
	 *
	 * `email_verified` and `is_paid` are whatever SureMail last cached on the row,
	 * refreshed only when its own screens call SureContact. Null means unknown.
	 *
	 * @since 2.12.9
	 * @return array{source:string,connections:int,default_type:string,default_missing:bool,provider_label:string,surecontact:array{connected:bool,email_verified:bool|null,is_paid:bool|null}}
	 */
	public static function get_readiness() {
		$contract = self::get_contract_readiness();
		if ( null !== $contract ) {
			return $contract;
		}

		$result = self::empty_readiness( 'option' );

		$settings = get_option( self::CONNECTIONS_OPTION, [] );
		if ( ! is_array( $settings ) ) {
			return $result;
		}

		$connections = isset( $settings['connections'] ) && is_array( $settings['connections'] ) ? $settings['connections'] : [];
		$connections = array_filter( $connections, 'is_array' );

		$result['connections'] = count( $connections );

		$default    = isset( $settings['default_connection'] ) && is_array( $settings['default_connection'] ) ? $settings['default_connection'] : [];
		$default_id = isset( $default['id'] ) && is_scalar( $default['id'] ) ? (string) $default['id'] : '';

		$row = '' !== $default_id && isset( $connections[ $default_id ] ) ? $connections[ $default_id ] : null;

		if ( null === $row ) {
			$result['default_missing'] = '' !== $default_id;
			// No usable default: SureMail sends through the highest-priority row.
			$row = self::first_by_priority( $connections );
		}

		if ( null === $row ) {
			return $result;
		}

		$type                   = isset( $row['type'] ) && is_string( $row['type'] ) ? strtoupper( $row['type'] ) : '';
		$result['default_type'] = $type;

		if ( self::SURECONTACT_TYPE === $type ) {
			$result['provider_label'] = 'SureContact';
			$result['surecontact']    = [
				'connected'      => true,
				'email_verified' => array_key_exists( 'email_verified', $row ) ? (bool) $row['email_verified'] : null,
				'is_paid'        => array_key_exists( 'is_paid', $row ) ? (bool) $row['is_paid'] : null,
			];
		} else {
			$title                    = isset( $row['connection_title'] ) && is_string( $row['connection_title'] ) ? $row['connection_title'] : '';
			$result['provider_label'] = '' !== $title ? $title : ucwords( strtolower( str_replace( '_', ' ', $type ) ) );
		}

		return $result;
	}

	/**
	 * Link into SureMail's admin app.
	 *
	 * SureMail lives under Settings or as a top-level menu depending on its own
	 * setting, so its helper builds the base URL when loaded.
	 *
	 * @since 2.12.9
	 * @param string $fragment Hash route, e.g. '/logs'.
	 * @return string
	 */
	public static function admin_url( $fragment = '' ) {
		$fragment = ltrim( (string) $fragment, '#' );

		$builder = [ '\SureMails\Inc\Utils\Utils', 'get_admin_url' ];
		if ( is_callable( $builder ) ) {
			$url = call_user_func( $builder, $fragment );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		$url = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
		return '' === $fragment ? $url : $url . '#' . $fragment;
	}

	/**
	 * Where the owner goes to set up sending.
	 *
	 * The provider picker, which leads with SureContact, until SureMail's own
	 * onboarding is done; the connections screen after. `#/onboarding/connect`
	 * is avoided: it relies on state SureMail's wizard sets on the way in.
	 *
	 * @since 2.12.9
	 * @return string
	 */
	public static function setup_url() {
		return self::admin_url( self::is_onboarding_completed() ? '/connections' : '/onboarding/connection' );
	}

	/**
	 * SureMail's email log screen.
	 *
	 * @since 2.12.9
	 * @return string
	 */
	public static function logs_url() {
		return self::admin_url( '/logs' );
	}

	/**
	 * Cached SureContact sending quota, or null when unknown.
	 *
	 * SureMail does not store the quota: its own meter asks SureContact live.
	 * SureForms keeps a copy refreshed in the background by
	 * refresh_surecontact_quota(), so no admin page waits on SureContact.
	 *
	 * @since 2.12.9
	 * @return array{used:int,limit:int,remaining:int,period:string,checked_at:int}|null
	 */
	public static function get_surecontact_quota() {
		$cached = get_transient( self::QUOTA_TRANSIENT );
		if ( ! is_array( $cached ) || empty( $cached['known'] ) ) {
			return null;
		}

		return [
			'used'       => isset( $cached['used'] ) ? (int) $cached['used'] : 0,
			'limit'      => isset( $cached['limit'] ) ? (int) $cached['limit'] : 0,
			'remaining'  => isset( $cached['remaining'] ) ? (int) $cached['remaining'] : 0,
			'period'     => isset( $cached['period'] ) && is_string( $cached['period'] ) ? $cached['period'] : '',
			'checked_at' => isset( $cached['checked_at'] ) ? (int) $cached['checked_at'] : 0,
		];
	}

	/**
	 * Whether a quota is nearly used up: a real, finite cap with a fifth or
	 * less left. The daily allowance of an unverified account is left to the
	 * 'limited' state, which already says to verify.
	 *
	 * @since 2.12.9
	 * @param array{used:int,limit:int,remaining:int,period:string,checked_at:int}|null $quota Quota from get_surecontact_quota().
	 * @return bool
	 */
	public static function is_quota_low( $quota ) {
		if ( ! is_array( $quota ) || $quota['limit'] <= 0 || 'daily' === $quota['period'] ) {
			return false;
		}

		return $quota['remaining'] <= (int) ceil( $quota['limit'] * self::QUOTA_LOW_SHARE );
	}

	/**
	 * Queue a background quota refresh when the cached copy has expired.
	 *
	 * Called from SureForms admin screens. The refresh itself runs on cron.
	 *
	 * @since 2.12.9
	 * @return void
	 */
	public static function maybe_schedule_quota_refresh() {
		if ( '' === self::get_surecontact_connection_id() || false !== get_transient( self::QUOTA_TRANSIENT ) ) {
			return;
		}

		if ( ! wp_next_scheduled( self::QUOTA_REFRESH_HOOK ) ) {
			wp_schedule_single_event( time(), self::QUOTA_REFRESH_HOOK );
		}
	}

	/**
	 * Fetch the quota again in the background, now.
	 *
	 * Called when a notification through SureMail fails, so the status can
	 * tell whether SureContact's allowance is why. The cached copy is kept
	 * until the fetch replaces it: deleting it would leave nothing at all on
	 * sites where cron never runs (DISABLE_WP_CRON without a real cron).
	 *
	 * @since 2.12.9
	 * @return void
	 */
	public static function request_quota_refresh() {
		if ( '' === self::get_surecontact_connection_id() ) {
			return;
		}

		if ( ! wp_next_scheduled( self::QUOTA_REFRESH_HOOK ) ) {
			wp_schedule_single_event( time(), self::QUOTA_REFRESH_HOOK );
		}
	}

	/**
	 * Ask SureContact for the quota through SureMail's status handler.
	 *
	 * Calls the handler behind SureMail's `surecontact/status` route directly,
	 * not over REST, because cron has no logged-in user to pass the route's
	 * permission check. SureMail reads its own encrypted key, so SureForms never
	 * touches it. A missing handler or a failed call caches "unknown" for an
	 * hour so a broken setup is not retried on every screen.
	 *
	 * @since 2.12.9
	 * @return void
	 */
	public static function refresh_surecontact_quota() {
		$connection_id = self::get_surecontact_connection_id();
		$instance      = [ '\SureMails\Inc\API\SureContactStatus', 'instance' ];

		if ( '' === $connection_id || ! is_callable( $instance ) ) {
			set_transient( self::QUOTA_TRANSIENT, [ 'known' => false ], HOUR_IN_SECONDS );
			return;
		}

		$request = new \WP_REST_Request( 'GET', '/suremails/v1/surecontact/status' );
		$request->set_param( 'connection_id', $connection_id );

		$handler  = call_user_func( $instance );
		$response = is_object( $handler ) && is_callable( [ $handler, 'status' ] ) ? call_user_func( [ $handler, 'status' ], $request ) : null;
		$data     = $response instanceof \WP_REST_Response && 200 === $response->get_status() ? $response->get_data() : null;
		$cap      = is_array( $data ) && isset( $data['cap'] ) && is_array( $data['cap'] ) ? $data['cap'] : null;

		if ( null === $cap || ! isset( $cap['limit'] ) || ! is_numeric( $cap['limit'] ) ) {
			set_transient( self::QUOTA_TRANSIENT, [ 'known' => false ], HOUR_IN_SECONDS );
			return;
		}

		$limit = (int) $cap['limit'];
		$used  = isset( $cap['used'] ) && is_numeric( $cap['used'] ) ? max( 0, (int) $cap['used'] ) : 0;
		$quota = [
			'used'       => $used,
			'limit'      => $limit,
			'remaining'  => isset( $cap['remaining'] ) && is_numeric( $cap['remaining'] ) ? max( 0, (int) $cap['remaining'] ) : max( 0, $limit - $used ),
			'period'     => isset( $cap['period'] ) && is_string( $cap['period'] ) ? sanitize_key( $cap['period'] ) : '',
			'checked_at' => time(),
		];

		// Used up first, whatever the period: a daily allowance is never "low"
		// (that is the limited state's job), but at zero it must be rechecked
		// as soon as a monthly one.
		if ( $limit > 0 && 0 === $quota['remaining'] ) {
			$ttl = self::QUOTA_EXHAUSTED_TTL;
		} elseif ( self::is_quota_low( $quota ) ) {
			$ttl = self::QUOTA_LOW_TTL;
		} else {
			$ttl = self::QUOTA_TTL;
		}

		set_transient( self::QUOTA_TRANSIENT, array_merge( [ 'known' => true ], $quota ), $ttl );
	}

	/**
	 * Where the owner upgrades: SureMail's dashboard, whose SureContact meter
	 * carries the upgrade button. `source` tells SureMail the click came from
	 * SureForms, ready for when it forwards attribution to SureContact.
	 *
	 * @since 2.12.9
	 * @return string
	 */
	public static function upgrade_url() {
		return add_query_arg( 'source', 'sureforms', self::admin_url( '/dashboard' ) );
	}

	/**
	 * The SureContact connection to read the quota for: the default one when it
	 * is SureContact, else the first SureContact row. '' when there is none or
	 * SureMail is not active.
	 *
	 * @since 2.12.9
	 * @return string
	 */
	private static function get_surecontact_connection_id() {
		// From the active plugins, like the rest of the status; whether SureMail's
		// handler can be called is checked where it is called.
		if ( 'suremail' !== Mailer::get_active()['slug'] ) {
			return '';
		}

		$settings = get_option( self::CONNECTIONS_OPTION, [] );
		if ( ! is_array( $settings ) || ! isset( $settings['connections'] ) || ! is_array( $settings['connections'] ) ) {
			return '';
		}

		$default_id = isset( $settings['default_connection']['id'] ) && is_scalar( $settings['default_connection']['id'] ) ? (string) $settings['default_connection']['id'] : '';
		$rows       = array_filter(
			$settings['connections'],
			static function ( $row ) {
				return is_array( $row ) && isset( $row['type'] ) && is_string( $row['type'] ) && self::SURECONTACT_TYPE === strtoupper( $row['type'] );
			}
		);

		if ( '' !== $default_id && isset( $rows[ $default_id ] ) ) {
			return $default_id;
		}

		$first = array_key_first( $rows );
		return null === $first ? '' : (string) $first;
	}

	/**
	 * Readiness from SureMail's own contract, when it provides one.
	 *
	 * SureMail 2.0.2 does not ship this filter yet. It is the contract requested
	 * in brainstormforce/suremails#809; until it lands, has_filter() keeps this
	 * branch inert and the option read above is what runs.
	 *
	 * @since 2.12.9
	 * @return array{source:string,connections:int,default_type:string,default_missing:bool,provider_label:string,surecontact:array{connected:bool,email_verified:bool|null,is_paid:bool|null}}|null
	 */
	private static function get_contract_readiness() {
		if ( ! has_filter( 'suremails_delivery_readiness' ) ) {
			return null;
		}

		$data = apply_filters( 'suremails_delivery_readiness', null );

		if ( ! is_array( $data ) || ! isset( $data['contract'] ) || (int) $data['contract'] < 1 ) {
			return null;
		}

		$result                = self::empty_readiness( 'contract' );
		$result['connections'] = isset( $data['connections_count'] ) ? max( 0, (int) $data['connections_count'] ) : 0;

		$default = isset( $data['default_connection'] ) && is_array( $data['default_connection'] ) ? $data['default_connection'] : [];
		$type    = isset( $default['type'] ) && is_string( $default['type'] ) ? strtoupper( $default['type'] ) : '';

		$result['default_type']    = $type;
		$result['default_missing'] = isset( $data['status'] ) && 'disconnected' === $data['status'];
		$result['provider_label']  = isset( $default['label'] ) && is_string( $default['label'] ) ? $default['label'] : '';

		$provider = isset( $data['provider'] ) && is_array( $data['provider'] ) ? $data['provider'] : [];
		if ( self::SURECONTACT_TYPE === $type ) {
			$result['provider_label'] = 'SureContact';
			$result['surecontact']    = [
				'connected'      => true,
				'email_verified' => array_key_exists( 'email_verified', $provider ) ? (bool) $provider['email_verified'] : null,
				'is_paid'        => array_key_exists( 'is_paid', $provider ) ? (bool) $provider['is_paid'] : null,
			];
		}

		return $result;
	}

	/**
	 * Readiness with nothing connected.
	 *
	 * @since 2.12.9
	 * @param string $source Where the data came from.
	 * @return array{source:string,connections:int,default_type:string,default_missing:bool,provider_label:string,surecontact:array{connected:bool,email_verified:bool|null,is_paid:bool|null}}
	 */
	private static function empty_readiness( $source ) {
		return [
			'source'          => $source,
			'connections'     => 0,
			'default_type'    => '',
			'default_missing' => false,
			'provider_label'  => '',
			'surecontact'     => [
				'connected'      => false,
				'email_verified' => null,
				'is_paid'        => null,
			],
		];
	}

	/**
	 * Connection row with the lowest priority number.
	 *
	 * @since 2.12.9
	 * @param array<mixed> $connections Connection rows.
	 * @return array<mixed>|null
	 */
	private static function first_by_priority( array $connections ) {
		$best      = null;
		$best_rank = PHP_INT_MAX;

		foreach ( $connections as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$rank = isset( $row['priority'] ) && is_numeric( $row['priority'] ) ? (int) $row['priority'] : PHP_INT_MAX - 1;
			if ( $rank < $best_rank ) {
				$best      = $row;
				$best_rank = $rank;
			}
		}

		return $best;
	}
}
