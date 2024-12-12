<?php
/**
 * Class Test_Single_Entry
 *
 * @package sureforms
 */

use SRFM\Admin\Views\Single_Entry;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Tests Plugin Initialization.
 *
 */
class Test_Single_Entry extends TestCase {

    /**
     * Test if paginate_array is calculating array for pagination properly or not.
     */
    public function test_paginate_array() {
        // Six dummy items.
        $array = array(
            array(
                'title' => 'Resend email notification "Admin Notification Email" initiated by admin',
                'messages' => array(
                    'Email notification sent to dev-email@wpengine.local'
                ),
                'timestamp' => 1733919705
            ),
            array(
                'title' => 'Entry edited by admin',
                'messages' => array(
                    '<del>John Doe</del> &#8594; Ram Prasad'
                ),
                'timestamp' => 1733803174
            ),
            array(
                'title' => 'Resend email notification "Admin Notification Email" initiated by admin',
                'messages' => array(
                    'Email notification sent to dev-email@wpengine.local'
                ),
                'timestamp' => 1733803154
            ),
            array(
                'title' => 'User registration completed by admin',
                'messages' => array(
                    'New user registered: Alice Johnson'
                ),
                'timestamp' => 1733920705
            ),
            array(
                'title' => 'Resend email notification "Admin Notification Email" initiated by admin',
                'messages' => array(
                    'Email notification sent to support-email@wpengine.local'
                ),
                'timestamp' => 1733932105
            ),
            array(
                'title' => 'Entry deleted by admin',
                'messages' => array(
                    'Deleted user entry for Jane Smith'
                ),
                'timestamp' => 1733945305
            )
        );

        $items_per_page       = 4;
        $expected_total_pages = 2;

        // Simulate page change env.
        for ( $current_page = 1; $current_page <= $expected_total_pages; $current_page++ ) { 
            $paginated_array = Single_Entry::paginate_array( $array, $current_page, $items_per_page );

            $expected_count = $items_per_page / $current_page;

            $this->assertNotEmpty( $paginated_array['items'] );
            $this->assertIsArray( $paginated_array['items'] );
            $this->assertCount( $expected_count, $paginated_array['items'] );
            $this->assertEquals( $expected_total_pages, $paginated_array['total_pages'] );
            $this->assertEquals( $current_page, $paginated_array['current_page'] );

            if ( 1 === $current_page ) {
                $this->assertEquals( $current_page + 1, $paginated_array['next_page'] );
                $this->assertFalse( $paginated_array['prev_page'] );
            } else {
                $this->assertFalse( $paginated_array['next_page'] );
                $this->assertEquals( $current_page - 1, $paginated_array['prev_page'] );
            }
        }
    }
}