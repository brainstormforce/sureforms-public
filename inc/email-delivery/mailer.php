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
 * @since   2.12.9
 */

namespace SRFM\Inc\Email_Delivery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mailer
 *
 * @since 2.12.9
 */
class Mailer {
	/**
	 * SureMail's plugin file.
	 *
	 * @since 2.12.9
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
	 * @since 2.12.9
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
		 * @since 2.12.9
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
	 * @since 2.12.9
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
		 * @since 2.12.9
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
	 * @since 2.12.9
	 * @return bool
	 */
	public static function has_mailer() {
		return 'none' !== self::get_active()['slug'];
	}

	/**
	 * Whether a failed wp_mail() may be retried through PHP's own mail().
	 *
	 * Off by default. Core's wp_mail() already sends through mail() when no mail
	 * plugin is active, so a raw mail() that succeeds after it failed has usually
	 * skipped the validation that caught a broken From address — the email
	 * "works" and the misconfiguration stays hidden. With a mail plugin active it
	 * is worse: the email bypasses that plugin's routing and logs, and SureMail,
	 * which retries a rejected email itself, would send it twice.
	 *
	 * So the filter is only consulted when no mail plugin is active, for hosts
	 * where raw mail() genuinely is the only thing that works.
	 *
	 * @since 2.12.9
	 * @param int    $form_id Form the email belongs to.
	 * @param string $context What is being sent, e.g. 'notification'.
	 * @return bool
	 */
	public static function allow_php_mail_fallback( $form_id = 0, $context = 'notification' ) {
		if ( self::has_mailer() ) {
			return false;
		}

		/**
		 * Filters whether a failed wp_mail() is retried through PHP's mail().
		 *
		 * Only applied when no mail plugin is active.
		 *
		 * @since 2.12.9
		 * @param bool   $allow   Whether to retry. Default false.
		 * @param int    $form_id Form the email belongs to.
		 * @param string $context What is being sent, e.g. 'notification'.
		 */
		return (bool) apply_filters( 'srfm_allow_php_mail_fallback', false, (int) $form_id, (string) $context );
	}

	/**
	 * Whether a mailer error is about a recipient address rather than the
	 * site's mail setup.
	 *
	 * Recipients often come from what a visitor typed ({form:email}), so an
	 * address PHPMailer rejects says nothing about whether the site can send:
	 * it is logged on the entry, but must not put the whole site into
	 * "attention". An invalid From address is a site problem and stays one.
	 *
	 * @since 2.12.9
	 * @param string $message Error message from wp_mail_failed.
	 * @return bool
	 */
	public static function is_recipient_error( $message ) {
		$message = (string) $message;
		if ( '' === $message ) {
			return false;
		}

		// PHPMailer names the header in parentheses, untranslated: "(to)", "(cc)".
		if ( preg_match( '/\\((to|cc|bcc|reply-to)\\)/i', $message ) ) {
			return true;
		}

		// No recipient at all, or the mail server refusing the recipient (RCPT)
		// over SMTP. API-based mailers word recipient rejections their own way and
		// are not recognised here; those still count, which errs towards telling
		// the owner.
		$recipient_texts = [
			'You must provide at least one recipient email address.',
			__( 'You must provide at least one recipient email address.' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Matching WordPress core's own PHPMailer string.
			'SMTP Error: The following recipients failed:',
			trim( __( 'SMTP Error: The following recipients failed: ' ) ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Matching WordPress core's own PHPMailer string.
		];
		foreach ( $recipient_texts as $recipient_text ) {
			if ( '' !== $recipient_text && false !== strpos( $message, $recipient_text ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The addresses a recipient error names, lowercased.
	 *
	 * PHPMailer lists refused recipients ("SMTP Error: The following recipients
	 * failed: a@b.c: 554 ...") and names invalid ones by header ("(to): x@").
	 *
	 * @since 2.12.9
	 * @param string $message Error message from wp_mail_failed.
	 * @return array<int,string>
	 */
	public static function get_refused_addresses( $message ) {
		$message   = (string) $message;
		$addresses = [];

		if ( preg_match_all( '/\\((?:to|cc|bcc)\\):\\s*([^\\s,]+)/i', $message, $named ) ) {
			$addresses = array_merge( $addresses, $named[1] );
		}
		if ( preg_match_all( '/[a-z0-9._%+\\-\']+@[a-z0-9.\\-]+\\.[a-z]{2,}/i', $message, $emails ) ) {
			$addresses = array_merge( $addresses, $emails[0] );
		}

		return array_values( array_unique( array_map( 'strtolower', array_map( 'trim', $addresses ) ) ) );
	}

	/**
	 * Whether a failed send is down only to addresses the visitor typed.
	 *
	 * Decided by where each refused address came from, not by the error text:
	 * PHPMailer reports every refusal at the recipient step the same way,
	 * including a host's "554 Relay access denied" or "530 Authentication
	 * required", which are the site's setup failing. So a failure is the
	 * visitor's only when every address it names came from a field they filled
	 * in ({form:*}). An address the owner typed into the notification still
	 * counts, typos included.
	 *
	 * @since 2.12.9
	 * @param string            $message           Error message from wp_mail_failed.
	 * @param array<int,string> $visitor_addresses Recipients that came from the visitor's fields.
	 * @param bool              $only_visitor      Whether every recipient template is a visitor field.
	 * @return bool
	 */
	public static function is_visitor_recipient_failure( $message, $visitor_addresses, $only_visitor ) {
		if ( ! self::is_recipient_error( $message ) ) {
			return false;
		}

		$refused = self::get_refused_addresses( $message );
		if ( empty( $refused ) ) {
			// No address named ("You must provide at least one recipient"): the
			// visitor's only if every recipient was theirs to fill in.
			return (bool) $only_visitor;
		}

		$visitor = array_map( 'strtolower', array_map( 'trim', (array) $visitor_addresses ) );
		foreach ( $refused as $address ) {
			if ( ! in_array( $address, $visitor, true ) ) {
				return false;
			}
			// Excused only when the address itself is wrong: PHPMailer rejected
			// its syntax, or the server says there is no such mailbox. Anything
			// else, including a refusal with no reason we can read, counts: a
			// "Relay access denied" or "Authentication required" is the site.
			if ( ! self::is_invalid_address_error( $message, $address ) && ! self::is_mailbox_refusal( self::get_refusal_reason( $message, $address ) ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether PHPMailer itself rejected an address as malformed, before any
	 * server saw it ("Invalid address:  (to): x@").
	 *
	 * @since 2.12.9
	 * @param string $message Error message from wp_mail_failed.
	 * @param string $address A refused address, lowercased.
	 * @return bool
	 */
	public static function is_invalid_address_error( $message, $address ) {
		$pattern = '/\\((?:to|cc|bcc)\\):\\s*' . preg_quote( (string) $address, '/' ) . '(?:[\\s,]|$)/i';
		return 1 === preg_match( $pattern, (string) $message );
	}

	/**
	 * What the mail server said about one refused address.
	 *
	 * PHPMailer lists refusals as "address: reason", and strips the SMTP reply
	 * code and enhanced status code from the reason before reporting it
	 * (PHPMailer 7.1.1, SMTP::sendCommand()): "v@x.org: <v@x.org>: Relay
	 * access denied". So the reason is all there is to go on.
	 *
	 * @since 2.12.9
	 * @param string $message Error message from wp_mail_failed.
	 * @param string $address A refused address, lowercased.
	 * @return string The reason, '' when none is given.
	 */
	public static function get_refusal_reason( $message, $address ) {
		$message = (string) $message;
		$marker  = 'recipients failed:';
		$start   = stripos( $message, $marker );
		$list    = false === $start ? $message : substr( $message, $start + strlen( $marker ) );

		$pattern = '/(?:^|[\\s,])' . preg_quote( (string) $address, '/' ) . '\\s*:\\s*(.*?)(?=(?:[\\r\\n]+|\\s)[^\\s:<>]+@[^\\s:<>]+\\s*:\\s|$)/is';
		if ( ! preg_match( $pattern, $list, $match ) ) {
			return '';
		}
		return trim( $match[1] );
	}

	/**
	 * Whether a server's reason says the mailbox itself does not exist, rather
	 * than the site being refused (relay, authentication, rate limits, policy).
	 * Kept to clear wording, so an unknown reason counts as the site's.
	 *
	 * @since 2.12.9
	 * @param string $reason Reason from get_refusal_reason().
	 * @return bool
	 */
	public static function is_mailbox_refusal( $reason ) {
		$reason = strtolower( (string) $reason );
		if ( '' === $reason ) {
			return false;
		}

		// Some servers repeat the enhanced status in the text: 5.1.x is about
		// the address (5.1.1 bad mailbox, 5.1.2 bad domain, 5.1.3 bad syntax).
		if ( preg_match( '/\\b5\\.1\\.\\d{1,3}\\b/', $reason ) ) {
			return true;
		}

		$mailbox_wording = [
			'user unknown',
			'unknown user',
			'no such user',
			'no such mailbox',
			'no such recipient',
			'unknown recipient',
			'recipient unknown',
			'user not found',
			'recipient not found',
			'mailbox not found',
			'mailbox does not exist',
			'does not exist',
			'invalid mailbox',
			'address rejected: user unknown',
		];
		foreach ( $mailbox_wording as $wording ) {
			if ( false !== strpos( $reason, $wording ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Why a mailer did not accept an email, for when it gave no reason itself.
	 *
	 * @since 2.12.9
	 * @return array{code:string,message:string}
	 */
	public static function get_rejection_reason() {
		$mailer = self::get_active();

		if ( 'suremail' === $mailer['slug'] ) {
			return [
				'code'    => 'suremail_rejected',
				'message' => __( 'SureMail did not accept this email. It may retry it automatically; SureMail\'s email logs show the reason.', 'sureforms' ),
			];
		}

		if ( 'none' !== $mailer['slug'] ) {
			return [
				'code'    => 'mailer_rejected',
				'message' => sprintf(
					/* translators: %s: name of the site's mail plugin. */
					__( '%s did not accept this email. Its logs show the reason.', 'sureforms' ),
					'' !== $mailer['label'] ? $mailer['label'] : __( 'Your mail plugin', 'sureforms' )
				),
			];
		}

		return [
			'code'    => 'php_mail_failed',
			'message' => __( 'No SMTP plugin detected. Please configure an SMTP plugin to enable email sending.', 'sureforms' ),
		];
	}

	/**
	 * Detection without the filter.
	 *
	 * @since 2.12.9
	 * @return array{slug:string,label:string,file:string}
	 */
	private static function detect() {
		$active = self::get_active_plugin_files();
		$known  = self::get_known_plugins();

		$other = null;
		foreach ( $known as $file => $label ) {
			if ( self::SUREMAIL_FILE !== $file && in_array( $file, $active, true ) ) {
				$other = [
					'slug'  => 'other',
					'label' => (string) $label,
					'file'  => (string) $file,
				];
				break;
			}
		}

		if ( in_array( self::SUREMAIL_FILE, $active, true ) ) {
			// SureMail with no connection hands mail to WordPress's default
			// handler, which fires phpmailer_init, so another SMTP plugin that is
			// also active is what actually sends. Report that one.
			if ( null !== $other && 0 === Suremail_Adapter::get_readiness()['connections'] ) {
				return $other;
			}

			return [
				'slug'  => 'suremail',
				'label' => 'SureMail',
				'file'  => self::SUREMAIL_FILE,
			];
		}

		if ( null !== $other ) {
			return $other;
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
	 * @since 2.12.9
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
	 * @since 2.12.9
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
