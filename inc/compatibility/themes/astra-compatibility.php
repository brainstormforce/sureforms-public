<?php
/**
 * Astra Compatibility.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Compatibility\Themes;

use SRFM\Inc\Traits\Get_Instance;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sureforms Astra Compatibility.
 *
 * @since x.x.x
 */
class Astra_Compatibility {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since x.x.x
	 */
	public function __construct() {
        // Update Astra's menu priority to show after Dashboard menu.
		add_filter( 'astra_menu_priority', [ $this, 'update_admin_menu_position' ] );
    }

    /**
     * Update Astra's menu priority to show after Dashboard menu.
     * Checks for existing menu items and assigns a new priority to Astra's menu item.
     * Decreases the priority by 0.1 until it finds a unique value.
     * If astra priority becomes less than dashboard priority, set it to 2 by default.
     *
     * @since x.x.x
     */
    public function update_admin_menu_position(): float {
        global $menu;

        $dashboard_priority = null;

        foreach ( $menu as $position => $menu_item ) {
            if ( isset( $menu_item[0] ) && 'Dashboard' === $menu_item[0] ) {
                $dashboard_priority = (float) $position;
                break;
            }
        }

        if ( null === $dashboard_priority ) {
            $dashboard_priority = 2.0;
        }

        $astra_priority = $dashboard_priority + 0.1;

        $existing_priorities = array_keys( $menu );

        while ( in_array( (string) $astra_priority, array_map( 'strval', $existing_priorities ), true ) ) {
            $astra_priority -= 0.1;
        }

        if ( $astra_priority < $dashboard_priority ) {
            $astra_priority = 2.0;
        }

        $astra_priority = round( $astra_priority, 1 );

        return $astra_priority;
    }
}
