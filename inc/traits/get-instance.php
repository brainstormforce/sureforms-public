<?php
/**
 * Trait.
 *
 * @package sureforms
 */

namespace SureForms\Inc\Traits;

/**
 * Trait Get_Instance.
 */
trait Get_Instance {

	/**
	 * Instance object.
	 *
	 * @var object Class Instance.
	 */
	private static $instance = null;

	/**
	 * Initiator
	 *
	 * @since X.X.X
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
