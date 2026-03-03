<?php
/**
 * Class Test_Email_Summary
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Global_Settings\Email_Summary;

class Test_Email_Summary extends TestCase {

	public function test_get_promo_banners_returns_array() {
		$result = Email_Summary::get_promo_banners();
		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		$this->assertArrayHasKey( 'ottokit', $result );
		$this->assertArrayHasKey( 'surerank', $result );
		$this->assertArrayHasKey( 'suremail', $result );
		$this->assertArrayHasKey( 'surecart', $result );
		$this->assertArrayHasKey( 'suredash', $result );

		foreach ( $result as $key => $banner ) {
			$this->assertArrayHasKey( 'logo', $banner );
			$this->assertArrayHasKey( 'title', $banner );
			$this->assertArrayHasKey( 'description', $banner );
			$this->assertArrayHasKey( 'link', $banner );
			$this->assertArrayHasKey( 'link_text', $banner );
			$this->assertArrayHasKey( 'plugin_path', $banner );
		}
	}

	public function test_get_total_entries_for_week_with_empty_data() {
		$result = Email_Summary::get_total_entries_for_week( [] );
		$this->assertIsString( $result );
		$this->assertStringContainsString( '<html', $result );
		$this->assertStringContainsString( 'Weekly Summary', $result );
	}
}
