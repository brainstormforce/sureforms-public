<?php
/**
 * Trait.
 *
 * @package sureforms
 */

namespace SRFM\Inc\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Trait Get_Instance.
 */
trait Get_Instance {
	/**
	 * Instance object.
	 *
	 * @var self|null Class Instance.
	 */
	private static $instance = null;

	/**
	 * Initiator
	 *
	 * @since 0.0.1
	 * @return self initialized object of class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Reset the cached instance.
	 *
	 * Primarily for tests, which need to re-resolve a singleton after changing
	 * the environment it reads at construction time (e.g. activating a
	 * multilingual provider via a filter). The next get_instance() call rebuilds
	 * the object.
	 *
	 * @since 2.11.0
	 * @return void
	 */
	public static function reset_instance() {
		self::$instance = null;
	}
}
