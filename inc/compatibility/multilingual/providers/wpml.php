<?php
/**
 * WPML Multilingual Provider.
 *
 * Adapter that bridges SureForms to the WPML plugin via its public hook surface.
 * Requires WPML 4.5+; older versions are treated as inactive.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc\Compatibility\Multilingual\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WPML_Provider.
 *
 * Implements {@see Provider} on top of WPML's filter/action API.
 *
 * @since x.x.x
 */
class WPML_Provider implements Provider {
	/**
	 * Minimum supported WPML version.
	 *
	 * @since x.x.x
	 */
	public const MIN_WPML_VERSION = '4.5';

	/**
	 * Memoized active state. Null means "not yet computed".
	 *
	 * @since x.x.x
	 * @var bool|null
	 */
	private $is_active_cache = null;

	/**
	 * Stack of languages pushed by {@see switch_language()}, used to restore previous context.
	 *
	 * @since x.x.x
	 * @var array<int, string>
	 */
	private $previous_language = [];

	/**
	 * Whether WPML is active and at the minimum supported version.
	 *
	 * @since x.x.x
	 * @return bool True when WPML 4.5+ is available, false otherwise.
	 */
	public function is_active(): bool {
		if ( null !== $this->is_active_cache ) {
			return $this->is_active_cache;
		}

		$active = defined( 'ICL_SITEPRESS_VERSION' )
			&& class_exists( '\SitePress' )
			&& version_compare( (string) constant( 'ICL_SITEPRESS_VERSION' ), self::MIN_WPML_VERSION, '>=' );

		$this->is_active_cache = $active;
		return $this->is_active_cache;
	}

	/**
	 * Current visitor language code as reported by WPML.
	 *
	 * @since x.x.x
	 * @return string Language code, or empty string when WPML is inactive or the filter returns a non-string.
	 */
	public function current_language(): string {
		if ( ! $this->is_active() ) {
			return '';
		}

		$language = apply_filters( 'wpml_current_language', null );
		return is_string( $language ) ? $language : '';
	}

	/**
	 * Site default language code as reported by WPML.
	 *
	 * @since x.x.x
	 * @return string Language code, or empty string when WPML is inactive or the filter returns a non-string.
	 */
	public function default_language(): string {
		if ( ! $this->is_active() ) {
			return '';
		}

		$language = apply_filters( 'wpml_default_language', null );
		return is_string( $language ) ? $language : '';
	}

	/**
	 * Register a string with WPML's String Translation registry.
	 *
	 * @param string $name   Unique string identifier within the domain.
	 * @param string $value  Original string value to register.
	 * @param string $domain Translation domain. Defaults to the sureforms text domain.
	 * @since x.x.x
	 * @return void
	 */
	public function register_string( string $name, string $value, string $domain = 'sureforms' ): void {
		if ( ! $this->is_active() ) {
			return;
		}

		do_action( 'wpml_register_single_string', $domain, $name, $value );
	}

	/**
	 * Translate a registered string via WPML.
	 *
	 * @param string      $value    Original string value (used as fallback).
	 * @param string      $name     Unique string identifier within the domain.
	 * @param string      $domain   Translation domain. Defaults to the sureforms text domain.
	 * @param string|null $language Optional target language code. When null, uses the current language.
	 * @since x.x.x
	 * @return string Translated value, or the original $value when WPML is inactive or no translation exists.
	 */
	public function translate( string $value, string $name, string $domain = 'sureforms', ?string $language = null ): string {
		if ( ! $this->is_active() ) {
			return $value;
		}

		if ( null !== $language ) {
			$translated = apply_filters( 'wpml_translate_single_string', $value, $domain, $name, $language );
		} else {
			$translated = apply_filters( 'wpml_translate_single_string', $value, $domain, $name );
		}

		if ( null === $translated || ! is_string( $translated ) ) {
			return $value;
		}

		return $translated;
	}

	/**
	 * Push the current language onto an internal stack and switch WPML to $language.
	 *
	 * @param string $language Target language code to switch to.
	 * @since x.x.x
	 * @return void
	 */
	public function switch_language( string $language ): void {
		if ( ! $this->is_active() ) {
			return;
		}

		$this->previous_language[] = $this->current_language();
		do_action( 'wpml_switch_language', $language );
	}

	/**
	 * Pop the previous language off the internal stack and restore WPML's context.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function restore_language(): void {
		if ( ! $this->is_active() ) {
			return;
		}

		if ( empty( $this->previous_language ) ) {
			return;
		}

		$previous = array_pop( $this->previous_language );
		do_action( 'wpml_switch_language', $previous );
	}
}
