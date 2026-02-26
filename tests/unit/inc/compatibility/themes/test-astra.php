<?php

use PHPUnit\Framework\TestCase;
use SRFM\Inc\Compatibility\Themes\Astra;

class AstraTest extends TestCase {

	protected $astra;

	protected function setUp(): void {
		$this->astra = new Astra();
	}

    /**
     * Test update_admin_menu_position method.
     * This function tests various scenarios for the update_admin_menu_position method.
     * It includes cases where:
     * 1. Dashboard found in global menu.
     * 2. Dashboard not found in global menu.
     * 3. Dashboard found in global menu and set Astra priority already exists.
     * 4. Astra priority is less than dashboard priority.
     *
     * @return void
     */
    public function test_update_admin_menu_position() {
        global $menu;

        // Test case 1: Dashboard found in global menu
        // Mock the global $menu variable
		$menu = [
			2 => [ '', 'read', 'index.php', '', 'menu-top', 'menu-dashboard' ],
			5 => [ '', 'read', 'edit.php', '', 'menu-top', 'menu-posts' ],
		];

		$astra_priority = $this->astra->update_admin_menu_position( 10 );

        // Astra priority will be 0.1 more than the dashboard priority
		$this->assertEquals( 2.1, $astra_priority, 'Failed asserting when dashboard found in global menu.' );

        // Test case 2: Dashboard not found in global menu
        // Mock the global $menu variable
        $menu = [
			5 => [ '', 'read', 'edit.php', '', 'menu-top', 'menu-posts' ],
		];

		$astra_priority = $this->astra->update_admin_menu_position( 10 );

        // We set the dashboard priority to 2.0 if it is not found in the menu
		$this->assertEquals( 2.1, $astra_priority, 'Failed asserting when dashboard not found in global menu' );

        // Test case 3: Dashboard found in global menu and set Astra priority already exists
        // Mock the global $menu variable
        $menu = [
			2 => [ '', 'read', 'index.php', '', 'menu-top', 'menu-dashboard' ],
			'2.1' => [ '', 'read', 'edit.php', '', 'menu-top', 'menu-posts' ],
		];

		$astra_priority = $this->astra->update_admin_menu_position( 10 );

        // Astra priority will be 2.0 if obtained priority already exists
		$this->assertEquals( 2.0, $astra_priority, 'Failed asserting when set astra priority already exists.' );

        // Test case 4: Astra priority is less than dashboard priority
        // Mock the global $menu variable
		$menu = [
			2 => [ '', 'read', 'index.php', '', 'menu-top', 'menu-dashboard' ],
		];

		$astra_priority = $this->astra->update_admin_menu_position( 1.5 );
        // Astra priority will returned without any changes if it is less than dashboard priority
		$this->assertEquals( 1.5, $astra_priority, 'Failed asserting when astra priority is less than dashboard priority' );
    }
}
