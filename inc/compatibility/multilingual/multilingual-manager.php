<?php
/**
 * Multilingual Manager.
 *
 * Resolves the active multilingual provider (WPML for v1). Returns Null_Provider when
 * no supported multilingual plugin is active, so call sites can stay free of conditionals.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc\Compatibility\Multilingual;

use SRFM\Inc\Compatibility\Multilingual\Providers\Null_Provider;
use SRFM\Inc\Compatibility\Multilingual\Providers\Provider;
use SRFM\Inc\Compatibility\Multilingual\Providers\WPML_Provider;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Multilingual_Manager.
 *
 * Resolves the active multilingual provider (WPML for v1). Returns Null_Provider when
 * no supported multilingual plugin is active.
 *
 * @since x.x.x
 */
class Multilingual_Manager {
	use Get_Instance;

	/**
	 * Resolved provider instance.
	 *
	 * @since x.x.x
	 * @var Provider
	 */
	private $provider;

	/**
	 * Constructor. Resolves and caches the active multilingual provider.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->provider = $this->resolve_provider();
	}

	/**
	 * Get the resolved multilingual provider.
	 *
	 * @since x.x.x
	 * @return Provider Active provider instance, or Null_Provider when none is available.
	 */
	public function provider(): Provider {
		return $this->provider;
	}

	/**
	 * Resolve which provider to use based on the active multilingual plugin.
	 *
	 * Resolution order:
	 *  1. WPML_Provider when WPML 4.5+ is active.
	 *  2. Null_Provider otherwise.
	 *  3. The `srfm_multilingual_provider` filter may override the default with any
	 *     {@see Provider} implementation; non-conforming values are ignored.
	 *
	 * @since x.x.x
	 * @return Provider Resolved provider instance.
	 */
	protected function resolve_provider(): Provider {
		$wpml = new WPML_Provider();

		$provider = $wpml->is_active() ? $wpml : new Null_Provider();

		/**
		 * Filter the resolved multilingual provider.
		 *
		 * Third parties can return their own {@see Provider} implementation to
		 * replace SureForms' default resolution. Non-conforming return values are ignored.
		 *
		 * @since x.x.x
		 * @param Provider $provider The resolved provider instance.
		 */
		$filtered = apply_filters( 'srfm_multilingual_provider', $provider );

		if ( $filtered instanceof Provider ) {
			return $filtered;
		}

		return $provider;
	}
}
