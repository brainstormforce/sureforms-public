<?php
/**
 * Shared helper for Astra Notices test classes.
 *
 * @package sureforms
 */

/**
 * Provides a helper to count registered Astra Notices via reflection.
 */
trait Astra_Notices_Helper {

	/**
	 * Return count of notices registered in Astra_Notices via reflection.
	 *
	 * @return int
	 */
	private function get_astra_notices_count(): int {
		if ( ! class_exists( 'Astra_Notices' ) ) {
			return 0;
		}
		$prop = new \ReflectionProperty( 'Astra_Notices', 'notices' );
		$prop->setAccessible( true );
		$notices = $prop->getValue( null );
		return is_array( $notices ) ? count( $notices ) : 0;
	}
}
