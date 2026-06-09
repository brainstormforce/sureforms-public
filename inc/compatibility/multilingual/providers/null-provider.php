<?php
/**
 * Null Multilingual Provider.
 *
 * Used when no supported multilingual plugin is detected. Returns inputs unchanged so
 * existing single-language behavior is preserved without conditional checks at call sites.
 *
 * @package sureforms.
 * @since 2.11.0
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
 * @since 2.11.0
 */
class Null_Provider implements Provider {
	/**
	 * Whether the underlying multilingual plugin is active.
	 *
	 * @since 2.11.0
	 * @return bool Always false for the Null provider.
	 */
	public function is_active(): bool {
		return false;
	}

	/**
	 * Current visitor language code.
	 *
	 * @since 2.11.0
	 * @return string Always an empty string.
	 */
	public function current_language(): string {
		return '';
	}

	/**
	 * Site default language code.
	 *
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
	 * @return void
	 */
	public function switch_language( string $language ): void {
		// No-op: there is no language context to switch.
		unset( $language );
	}

	/**
	 * Restore the previous language context. No-op for the Null provider.
	 *
	 * @since 2.11.0
	 * @return void
	 */
	public function restore_language(): void {
		// No-op: there is no language context to restore.
	}

	/**
	 * Render a language switcher. No-op for the Null provider.
	 *
	 * @since 2.11.0
	 * @return string Empty string — no switcher to render when no provider is active.
	 */
	public function render_language_switcher(): string {
		return '';
	}

	/**
	 * No package support without an active multilingual provider.
	 *
	 * @since 2.11.0
	 * @return bool Always false.
	 */
	public function supports_packages(): bool {
		return false;
	}

	/**
	 * Begin a package registration. No-op for the Null provider.
	 *
	 * @param array<string,string> $package Package descriptor.
	 * @since 2.11.0
	 * @return void
	 */
	public function start_package( array $package ): void {
		unset( $package );
	}

	/**
	 * Finish a package registration. No-op for the Null provider.
	 *
	 * @param array<string,string> $package Package descriptor.
	 * @since 2.11.0
	 * @return void
	 */
	public function finish_package( array $package ): void {
		unset( $package );
	}

	/**
	 * Register a package string. No-op for the Null provider.
	 *
	 * @param array<string,string> $package Package descriptor.
	 * @param string               $name    String identifier.
	 * @param string               $value   Original value.
	 * @param string               $title   Editor label.
	 * @param string               $type    Editor field type.
	 * @since 2.11.0
	 * @return void
	 */
	public function register_package_string( array $package, string $name, string $value, string $title = '', string $type = 'LINE' ): void {
		unset( $package, $name, $value, $title, $type );
	}

	/**
	 * Translate a package string. Returns the original value for the Null provider.
	 *
	 * @param array<string,string> $package Package descriptor.
	 * @param string               $name    String identifier.
	 * @param string               $value   Original value.
	 * @since 2.11.0
	 * @return string The original value unchanged.
	 */
	public function translate_package_string( array $package, string $name, string $value ): string {
		unset( $package, $name );
		return $value;
	}
}
