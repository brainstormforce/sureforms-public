<?php
/**
 * WPML Multilingual Provider.
 *
 * Adapter that bridges SureForms to the WPML plugin via its public hook surface.
 * Requires WPML 4.5+; older versions are treated as inactive.
 *
 * @package sureforms.
 * @since 2.11.0
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
 * @since 2.11.0
 */
class WPML_Provider implements Provider {
	/**
	 * Minimum supported WPML version.
	 *
	 * @since 2.11.0
	 */
	public const MIN_WPML_VERSION = '4.5';

	/**
	 * Memoized active state. Null means "not yet computed".
	 *
	 * @since 2.11.0
	 * @var bool|null
	 */
	private $is_active_cache = null;

	/**
	 * Stack of languages pushed by {@see switch_language()}, used to restore previous context.
	 *
	 * @since 2.11.0
	 * @var array<int, string>
	 */
	private $previous_language = [];

	/**
	 * Whether WPML is active and at the minimum supported version.
	 *
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
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
	 * @since 2.11.0
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

	/**
	 * {@inheritDoc}
	 *
	 * Builds a self-contained list-based switcher from WPML's `wpml_active_languages`
	 * data. Doesn't depend on the site's WPML "switcher slot" being enabled (footer,
	 * post-actions, etc.), so it works on custom templates that don't include the
	 * theme footer — like SureForms' single-form.php instant-form template.
	 *
	 * Returns empty when WPML is inactive or fewer than two languages are configured.
	 *
	 * @since 2.11.0
	 * @return string Rendered switcher HTML, or empty string.
	 */
	public function render_language_switcher(): string {
		if ( ! $this->is_active() ) {
			return '';
		}

		$languages = $this->get_active_languages();
		if ( count( $languages ) < 2 ) {
			return '';
		}

		$current = $this->current_language();

		$items = '';
		foreach ( $languages as $code => $language ) {
			$url    = isset( $language['url'] ) && is_string( $language['url'] ) ? $language['url'] : '';
			$native = isset( $language['native_name'] ) && is_string( $language['native_name'] ) ? $language['native_name'] : '';
			$lang   = isset( $language['language_code'] ) && is_string( $language['language_code'] ) ? $language['language_code'] : (string) $code;

			if ( '' === $url || '' === $native ) {
				continue;
			}

			// Prefer WPML's own `active` flag from wpml_active_languages (the canonical
			// current-language signal for the switcher), falling back to comparing against
			// current_language() when the flag isn't present.
			$is_current = isset( $language['active'] ) ? ! empty( $language['active'] ) : ( $lang === $current );
			$class      = $is_current ? 'srfm-lang-item srfm-lang-item-current' : 'srfm-lang-item';
			$items     .= sprintf(
				'<li class="%1$s"><a href="%2$s" hreflang="%3$s" lang="%3$s">%4$s</a></li>',
				esc_attr( $class ),
				esc_url( $url ),
				esc_attr( $lang ),
				esc_html( $native )
			);
		}

		if ( '' === $items ) {
			return '';
		}

		return '<ul class="srfm-lang-switcher-list" role="navigation" aria-label="' . esc_attr__( 'Language Switcher', 'sureforms' ) . '">' . $items . '</ul>';
	}

	/**
	 * Whether WPML's String Package API is available.
	 *
	 * Package translation is provided by the WPML String Translation plugin's
	 * package module. Gate on the `wpml_register_string` action so we degrade
	 * gracefully (callers fall back to flat strings) if only WPML core is active.
	 *
	 * @since 2.11.0
	 * @return bool
	 */
	public function supports_packages(): bool {
		return $this->is_active() && has_action( 'wpml_register_string' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array<string,string> $package Package descriptor (kind, name, title, edit_link).
	 * @since 2.11.0
	 * @return void
	 */
	public function start_package( array $package ): void {
		if ( ! $this->supports_packages() ) {
			return;
		}
		do_action( 'wpml_start_string_package_registration', $package );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array<string,string> $package Package descriptor.
	 * @since 2.11.0
	 * @return void
	 */
	public function finish_package( array $package ): void {
		if ( ! $this->supports_packages() ) {
			return;
		}
		do_action( 'wpml_delete_unused_package_strings', $package );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array<string,string> $package Package descriptor.
	 * @param string               $name    String identifier within the package.
	 * @param string               $value   Original value.
	 * @param string               $title   Editor label.
	 * @param string               $type    Editor field type (LINE|AREA|VISUAL).
	 * @since 2.11.0
	 * @return void
	 */
	public function register_package_string( array $package, string $name, string $value, string $title = '', string $type = 'LINE' ): void {
		if ( ! $this->supports_packages() ) {
			return;
		}
		do_action( 'wpml_register_string', $value, $name, $package, '' !== $title ? $title : $name, $type );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array<string,string> $package Package descriptor.
	 * @param string               $name    String identifier within the package.
	 * @param string               $value   Original value (fallback).
	 * @since 2.11.0
	 * @return string
	 */
	public function translate_package_string( array $package, string $name, string $value ): string {
		if ( ! $this->supports_packages() ) {
			return $value;
		}
		$translated = apply_filters( 'wpml_translate_string', $value, $name, $package );
		return is_string( $translated ) ? $translated : $value;
	}

	/**
	 * Build the active-language list from the most reliable WPML surface available.
	 *
	 * Tries `apply_filters( 'wpml_active_languages', ... )` first (the documented
	 * public API that returns URLs for the current page). When that returns no
	 * data — which happens on custom template paths that run before WPML has
	 * fully bootstrapped its language switcher — falls back to SitePress's
	 * internal `get_active_languages()` data and constructs URLs via the
	 * `wpml_permalink` filter so the switcher still links to translated copies.
	 *
	 * @since 2.11.0
	 * @return array<string, array<string, mixed>> Map of language code → {url, native_name, language_code}.
	 */
	protected function get_active_languages(): array {
		$languages = apply_filters( 'wpml_active_languages', null, 'skip_missing=0' );
		if ( is_array( $languages ) && ! empty( $languages ) ) {
			return $languages;
		}

		global $sitepress;
		if ( ! is_object( $sitepress ) || ! method_exists( $sitepress, 'get_active_languages' ) ) {
			return [];
		}

		$sp_languages = $sitepress->get_active_languages();
		if ( ! is_array( $sp_languages ) || empty( $sp_languages ) ) {
			return [];
		}

		$current_url = $this->guess_current_url();
		$out         = [];
		foreach ( $sp_languages as $code => $data ) {
			if ( ! is_string( $code ) || '' === $code || ! is_array( $data ) ) {
				continue;
			}
			$native = isset( $data['native_name'] ) && is_string( $data['native_name'] ) ? $data['native_name'] : $code;

			$url = apply_filters( 'wpml_permalink', $current_url, $code );
			if ( ! is_string( $url ) || '' === $url ) {
				continue;
			}

			$out[ $code ] = [
				'language_code' => $code,
				'native_name'   => $native,
				'url'           => $url,
			];
		}
		return $out;
	}

	/**
	 * Best-effort current page URL for use with the `wpml_permalink` filter when
	 * SitePress hasn't pre-computed per-language URLs for the request.
	 *
	 * @since 2.11.0
	 * @return string Current request URL, or home URL as a last resort.
	 */
	protected function guess_current_url(): string {
		if ( function_exists( 'home_url' ) ) {
			$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			if ( '' !== $uri ) {
				return home_url( $uri );
			}
			return home_url( '/' );
		}
		return '';
	}
}
