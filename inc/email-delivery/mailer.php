<?php
/**
 * Active mailer detection.
 *
 * Answers "what will actually carry a wp_mail() call on this site?" — SureMail,
 * another mail plugin, something that replaced wp_mail() outright, or nothing
 * beyond PHP's own mail(). The answer decides what SureForms may offer: an owner
 * who already configured WP Mail SMTP must never be pushed to install SureMail.
 *
 * @package SureForms
 * @since   x.x.x
 */

namespace SRFM\Inc\Email_Delivery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mailer
 *
 * @since x.x.x
 */
class Mailer {
	/**
	 * SureMail's plugin file.
	 *
	 * @since x.x.x
	 */
	public const SUREMAIL_FILE = 'suremails/suremails.php';

	/**
	 * Mail plugins SureForms recognises, keyed by plugin file.
	 *
	 * Only plugins that take over sending belong here. `newsletter/plugin.php` was
	 * on the old list and is a newsletter sender, not a transport for wp_mail(),
	 * so it made "No SMTP plugin detected" silently disappear on sites that had
	 * none.
	 *
	 * @since x.x.x
	 * @return array<string,string> Plugin file => display name.
	 */
	public static function get_known_plugins() {
		$plugins = [
			self::SUREMAIL_FILE                    => 'SureMail',
			'wp-mail-smtp/wp_mail_smtp.php'        => 'WP Mail SMTP',
			'wp-mail-smtp-pro/wp_mail_smtp.php'    => 'WP Mail SMTP Pro',
			'post-smtp/postman-smtp.php'           => 'Post SMTP',
			'easy-wp-smtp/easy-wp-smtp.php'        => 'Easy WP SMTP',
			'wp-smtp/wp-smtp.php'                  => 'WP SMTP',
			'fluent-smtp/fluent-smtp.php'          => 'FluentSMTP',
			'pepipost-smtp/pepipost-smtp.php'      => 'Pepipost',
			'mail-bank/wp-mail-bank.php'           => 'Mail Bank',
			'smtp-mailer/smtp-mailer.php'          => 'SMTP Mailer',
			'site-mailer/site-mailer.php'          => 'Site Mailer',
			'gosmtp/gosmtp.php'                    => 'GoSMTP',
			'wp-ses/wp-ses.php'                    => 'WP Offload SES Lite',
			'wp-offload-ses/wp-offload-ses.php'    => 'WP Offload SES',
			'smtp2go/smtp2go-wordpress-plugin.php' => 'SMTP2GO',
			'postmark-approved-wordpress-plugin/postmark.php' => 'Postmark',
			'mailgun/mailgun.php'                  => 'Mailgun',
		];

		/**
		 * Filters the mail plugins SureForms recognises.
		 *
		 * @since x.x.x
		 * @param array<string,string> $plugins Plugin file => display name.
		 */
		$filtered = apply_filters( 'srfm_smtp_plugins', $plugins );

		return is_array( $filtered ) ? $filtered : $plugins;
	}

	/**
	 * Identify the mailer that will carry wp_mail() on this site.
	 *
	 * SureMail is checked first: it is the one mailer SureForms can read further
	 * state from, and a site running it next to another mail plugin is still
	 * sending through SureMail's connections.
	 *
	 * @since x.x.x
	 * @return array{slug:string,label:string,file:string} slug is one of
	 *         suremail | other | wp_mail_override | none.
	 */
	public static function get_active() {
		$mailer = self::detect();

		/**
		 * Filters the detected mailer.
		 *
		 * For hosts that deliver mail through something SureForms cannot see — a
		 * platform relay, an mu-plugin — so the owner is not told email is unset.
		 *
		 * @since x.x.x
		 * @param array{slug:string,label:string,file:string} $mailer Detected mailer.
		 */
		$filtered = apply_filters( 'srfm_active_mailer', $mailer );

		if ( ! is_array( $filtered ) || empty( $filtered['slug'] ) || ! is_string( $filtered['slug'] ) ) {
			return $mailer;
		}

		return [
			'slug'  => $filtered['slug'],
			'label' => isset( $filtered['label'] ) && is_string( $filtered['label'] ) ? $filtered['label'] : '',
			'file'  => isset( $filtered['file'] ) && is_string( $filtered['file'] ) ? $filtered['file'] : '',
		];
	}

	/**
	 * Whether anything beyond PHP's own mail() will carry wp_mail().
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public static function has_mailer() {
		return 'none' !== self::get_active()['slug'];
	}

	/**
	 * Detection without the filter.
	 *
	 * @since x.x.x
	 * @return array{slug:string,label:string,file:string}
	 */
	private static function detect() {
		$active = self::get_active_plugin_files();
		$known  = self::get_known_plugins();

		if ( in_array( self::SUREMAIL_FILE, $active, true ) ) {
			return [
				'slug'  => 'suremail',
				'label' => 'SureMail',
				'file'  => self::SUREMAIL_FILE,
			];
		}

		foreach ( $known as $file => $label ) {
			if ( in_array( $file, $active, true ) ) {
				return [
					'slug'  => 'other',
					'label' => (string) $label,
					'file'  => (string) $file,
				];
			}
		}

		$override = self::get_wp_mail_override_file();
		if ( '' !== $override ) {
			return [
				'slug'  => 'wp_mail_override',
				'label' => basename( dirname( $override ) ),
				'file'  => $override,
			];
		}

		return [
			'slug'  => 'none',
			'label' => '',
			'file'  => '',
		];
	}

	/**
	 * Active plugin files, including network-activated ones.
	 *
	 * @since x.x.x
	 * @return array<int,string>
	 */
	private static function get_active_plugin_files() {
		$active = (array) get_option( 'active_plugins', [] );

		if ( is_multisite() ) {
			$network = (array) get_site_option( 'active_sitewide_plugins', [] );
			$active  = array_merge( $active, array_keys( $network ) );
		}

		return array_values( array_filter( $active, 'is_string' ) );
	}

	/**
	 * The file that defines wp_mail(), when it is not core's.
	 *
	 * Core's wp_mail() is pluggable, so a host or plugin not on the list can own
	 * sending by redefining it. Empty when core's own definition is in use, or when
	 * pluggable functions have not been loaded yet.
	 *
	 * @since x.x.x
	 * @return string
	 */
	private static function get_wp_mail_override_file() {
		if ( ! function_exists( 'wp_mail' ) ) {
			return '';
		}

		try {
			$file = ( new \ReflectionFunction( 'wp_mail' ) )->getFileName();
		} catch ( \ReflectionException $e ) {
			return '';
		}

		if ( ! is_string( $file ) || '' === $file ) {
			return '';
		}

		$inc  = defined( 'WPINC' ) ? (string) constant( 'WPINC' ) : 'wp-includes';
		$core = realpath( ABSPATH . $inc . '/pluggable.php' );
		$real = realpath( $file );

		if ( false === $core || false === $real || $core === $real ) {
			return '';
		}

		return wp_normalize_path( $real );
	}
}
