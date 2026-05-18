<?php
/**
 * Null Multilingual Provider.
 *
 * Used when no supported multilingual plugin is detected. Returns inputs unchanged so
 * existing single-language behavior is preserved without conditional checks at call sites.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc\Compatibility\Multilingual\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Null_Provider.
 *
 * Pass-through implementation of {@see Provider}.
 *
 * @since x.x.x
 */
class Null_Provider implements Provider {
	/**
	 * Whether the underlying multilingual plugin is active.
	 *
	 * @since x.x.x
	 * @return bool Always false for the Null provider.
	 */
	public function is_active(): bool {
		return false;
	}

	/**
	 * Current visitor language code.
	 *
	 * @since x.x.x
	 * @return string Always an empty string.
	 */
	public function current_language(): string {
		return '';
	}

	/**
	 * Site default language code.
	 *
	 * @since x.x.x
	 * @return string Always an empty string.
	 */
	public function default_language(): string {
		return '';
	}

	/**
	 * Register a translatable string. No-op for the Null provider.
	 *
	 * @param string $name   Unique string identifier within the domain.
	 * @param string $value  Original string value to register.
	 * @param string $domain Translation domain. Defaults to the sureforms text domain.
	 * @since x.x.x
	 * @return void
	 */
	public function register_string( string $name, string $value, string $domain = 'sureforms' ): void {
		// No-op: there is no String Translation registry to talk to.
		unset( $name, $value, $domain );
	}

	/**
	 * Translate a string. The Null provider returns the original value unchanged.
	 *
	 * @param string      $value    Original string value.
	 * @param string      $name     Unique string identifier within the domain.
	 * @param string      $domain   Translation domain. Defaults to the sureforms text domain.
	 * @param string|null $language Optional target language code.
	 * @since x.x.x
	 * @return string The unchanged input value.
	 */
	public function translate( string $value, string $name, string $domain = 'sureforms', ?string $language = null ): string {
		unset( $name, $domain, $language );
		return $value;
	}

	/**
	 * Switch the active language context. No-op for the Null provider.
	 *
	 * @param string $language Target language code.
	 * @since x.x.x
	 * @return void
	 */
	public function switch_language( string $language ): void {
		// No-op: there is no language context to switch.
		unset( $language );
	}

	/**
	 * Restore the previous language context. No-op for the Null provider.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function restore_language(): void {
		// No-op: there is no language context to restore.
	}
}
