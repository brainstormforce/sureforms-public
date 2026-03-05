<?php
/**
 * Settings Secret Keys Trait.
 *
 * Provides the shared list of secret key option names used by both
 * get-global-settings and update-global-settings abilities.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Settings_Secret_Keys trait.
 *
 * @since x.x.x
 */
trait Settings_Secret_Keys {
	/**
	 * Secret key option names that should be masked in output
	 * and preserved (sentinel check) on update.
	 *
	 * @since x.x.x
	 * @var array<string>
	 */
	private static $secret_keys = [
		'srfm_v2_checkbox_secret_key',
		'srfm_v2_invisible_secret_key',
		'srfm_v3_secret_key',
		'srfm_cf_turnstile_secret_key',
		'srfm_hcaptcha_secret_key',
	];
}
