<?php
/**
 * Multilingual Provider Interface.
 *
 * Defines the contract that every multilingual plugin adapter (WPML, Polylang, Null)
 * must implement so the rest of SureForms can stay agnostic of the underlying plugin.
 *
 * @package sureforms.
 * @since 2.11.0
 */

namespace SRFM\Inc\Compatibility\Multilingual\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Provider.
 *
 * Common surface area exposed by every multilingual provider adapter.
 *
 * @since 2.11.0
 */
interface Provider {
	/**
	 * Whether the underlying multilingual plugin is active and usable.
	 *
	 * @since 2.11.0
	 * @return bool True when the provider can perform translations, false otherwise.
	 */
	public function is_active(): bool;

	/**
	 * Current visitor language code.
	 *
	 * @since 2.11.0
	 * @return string Language code (e.g. 'en', 'de'). Empty string when not active.
	 */
	public function current_language(): string;

	/**
	 * Site default language code.
	 *
	 * @since 2.11.0
	 * @return string Language code (e.g. 'en'). Empty string when not active.
	 */
	public function default_language(): string;

	/**
	 * Register a translatable string with the multilingual plugin's String Translation registry.
	 *
	 * @param string $name   Unique string identifier within the domain.
	 * @param string $value  Original string value to register.
	 * @param string $domain Translation domain. Defaults to the sureforms text domain.
	 * @since 2.11.0
	 * @return void
	 */
	public function register_string( string $name, string $value, string $domain = 'sureforms' ): void;

	/**
	 * Translate a previously registered string.
	 *
	 * @param string      $value    Original string value (used as fallback).
	 * @param string      $name     Unique string identifier within the domain.
	 * @param string      $domain   Translation domain. Defaults to the sureforms text domain.
	 * @param string|null $language Optional target language code. When null, uses the current language.
	 * @since 2.11.0
	 * @return string Translated string, or the original value when no translation is found.
	 */
	public function translate( string $value, string $name, string $domain = 'sureforms', ?string $language = null ): string;

	/**
	 * Switch the active language context.
	 *
	 * Pushes the new language onto an internal stack so {@see restore_language()} can revert it.
	 *
	 * @param string $language Target language code to switch to.
	 * @since 2.11.0
	 * @return void
	 */
	public function switch_language( string $language ): void;

	/**
	 * Restore the previous language context after {@see switch_language()}.
	 *
	 * @since 2.11.0
	 * @return void
	 */
	public function restore_language(): void;

	/**
	 * Render a language switcher widget as HTML.
	 *
	 * Used by templates that don't include the theme's footer (e.g., the
	 * SureForms instant-form template) so visitors can still change languages
	 * without depending on theme integration.
	 *
	 * Returns an empty string when no provider is active or when the provider
	 * can't produce a switcher (e.g., only one language configured).
	 *
	 * @since 2.11.0
	 * @return string Rendered switcher HTML.
	 */
	public function render_language_switcher(): string;

	/**
	 * Whether the provider supports translation "string packages" — groups of
	 * strings bound to a single object (e.g. a form) that surface together in the
	 * multilingual plugin's Translation Editor, instead of as flat, global strings.
	 *
	 * @since 2.11.0
	 * @return bool True when package registration/translation is available.
	 */
	public function supports_packages(): bool;

	/**
	 * Begin (re)registering a string package. Call before registering its strings
	 * so strings no longer present can be pruned by {@see finish_package()}.
	 *
	 * @param array<string,string> $package Package descriptor: kind, name, title, edit_link.
	 * @since 2.11.0
	 * @return void
	 */
	public function start_package( array $package ): void;

	/**
	 * Finish registering a string package, removing any strings that were not
	 * re-registered since {@see start_package()} (e.g. fields deleted from a form).
	 *
	 * @param array<string,string> $package Package descriptor.
	 * @since 2.11.0
	 * @return void
	 */
	public function finish_package( array $package ): void;

	/**
	 * Register a single string within a package.
	 *
	 * @param array<string,string> $package Package descriptor.
	 * @param string               $name    Stable string identifier within the package.
	 * @param string               $value   Original string value.
	 * @param string               $title   Human-readable label shown in the Translation Editor.
	 * @param string               $type    Editor field type: LINE, AREA or VISUAL.
	 * @since 2.11.0
	 * @return void
	 */
	public function register_package_string( array $package, string $name, string $value, string $title = '', string $type = 'LINE' ): void;

	/**
	 * Translate a single package string for the current language.
	 *
	 * @param array<string,string> $package Package descriptor.
	 * @param string               $name    Stable string identifier within the package.
	 * @param string               $value   Original value (fallback when untranslated).
	 * @since 2.11.0
	 * @return string Translated value, or the original when no translation exists.
	 */
	public function translate_package_string( array $package, string $name, string $value ): string;
}
