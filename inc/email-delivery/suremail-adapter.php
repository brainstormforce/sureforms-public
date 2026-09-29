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
 * @since   x.x.x
 */

namespace SRFM\Inc\Email_Delivery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Suremail_Adapter
 *
 * @since x.x.x
 */
class Suremail_Adapter {
	/**
	 * SureMail's connections option.
	 *
	 * @since x.x.x
	 */
	public const CONNECTIONS_OPTION = 'suremails_connections';

	/**
	 * SureMail's onboarding-completed option ('yes' once finished).
	 *
	 * @since x.x.x
	 */
	public const ONBOARDING_OPTION = 'suremails_onboarding_completed';

	/**
	 * First SureMail release with the SureContact provider.
	 *
	 * @since x.x.x
	 */
	public const SURECONTACT_MIN_VERSION = '2.0.0';

	/**
	 * SureMail's connection type for SureContact.
	 *
	 * @since x.x.x
	 */
	public const SURECONTACT_TYPE = 'SURECONTACT';

	/**
	 * SureMail's admin page slug.
	 *
	 * @since x.x.x
	 */
	private const PAGE_SLUG = 'suremail';

	/**
	 * Whether SureMail is loaded.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public static function is_active() {
		return defined( 'SUREMAILS_VERSION' );
	}

	/**
	 * Loaded SureMail version, or '' when it is not active.
	 *
	 * @since x.x.x
	 * @return string
	 */
	public static function get_version() {
		return self::is_active() && is_string( constant( 'SUREMAILS_VERSION' ) ) ? (string) constant( 'SUREMAILS_VERSION' ) : '';
	}

	/**
	 * Whether the loaded SureMail can connect SureContact.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public static function supports_surecontact() {
		$version = self::get_version();
		return '' !== $version && version_compare( $version, self::SURECONTACT_MIN_VERSION, '>=' );
	}

	/**
	 * Whether the owner finished SureMail's own onboarding.
	 *
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
	 * @return string
	 */
	public static function setup_url() {
		return self::admin_url( self::is_onboarding_completed() ? '/connections' : '/onboarding/connection' );
	}

	/**
	 * SureMail's email log screen.
	 *
	 * @since x.x.x
	 * @return string
	 */
	public static function logs_url() {
		return self::admin_url( '/logs' );
	}

	/**
	 * Readiness from SureMail's own contract, when it provides one.
	 *
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
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
