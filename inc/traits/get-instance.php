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
	 * @var self Class Instance.
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
}
