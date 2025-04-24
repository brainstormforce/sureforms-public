<?php
/**
 * Astra Compatibility.
 *
 * @package sureforms
 * @since   1.6.1
 */

namespace SRFM\Inc\Compatibility\Themes;

use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sureforms Astra Compatibility.
 *
 * @since 1.6.1
 */
class Astra {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since 1.6.1
	 */
	public function __construct() {
		// Update Astra's menu priority to show after Dashboard menu.
		add_filter( 'astra_menu_priority', [ $this, 'update_admin_menu_position' ], 99999, 1 );
	}

	/**
	 * Update Astra's menu priority to show after Dashboard menu.
	 * Checks for existing menu items and assigns a new priority to Astra's menu item.
	 *
	 * @param int $astra_priority Astra menu priority.
	 *
	 * @return float
	 * @since 1.6.1
	 */
	public function update_admin_menu_position( $astra_priority ) {
		global $menu;

		$dashboard_priority = null;

		// Loop through the menu items to find the dashboard priority.
		foreach ( $menu as $position => $menu_item ) {
			// We are checking at index 5 because it is the position of the menu-dashboard slug and it is not affected by translation.
			if ( isset( $menu_item[5] ) && 'menu-dashboard' === $menu_item[5] ) {
				$dashboard_priority = (float) $position;
				break;
			}
		}

		// If we couldn't find the dashboard priority, set it to 2.0.
		if ( null === $dashboard_priority ) {
			$dashboard_priority = 2.0;
		}

		// Check if the Astra priority is numeric.
		// If not, set it to 2.0.
		if ( ! is_numeric( $astra_priority ) ) {
			$astra_priority = 2.0;
		} else {
			$astra_priority = (float) $astra_priority;
		}

		// If the Astra priority is already less than the dashboard priority, return it.
		if ( $astra_priority < $dashboard_priority ) {
			return $astra_priority;
		}

		// Increase the Astra priority by 0.1 so that it is placed right after the Dashboard.
		$astra_priority = $dashboard_priority + 0.1;

		$existing_priorities = array_keys( $menu );

		// If the computed astra_priority already exists, just default to 2.0.
		// We are converting the existing priorities to string to make sure the type is always same.
		if ( in_array( Helper::get_string_value( $astra_priority ), array_map( 'strval', $existing_priorities ), true ) ) {
			$astra_priority = 2.0;
		}

		return $astra_priority;
	}
}
