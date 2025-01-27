<?php
/**
 * Class Test_Sureforms
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Tests Plugin Initialization.
 *
 */
class Test_Sureforms extends TestCase {
	/**
	 * Testing if all required constants are defined.
	 *
	 * @return void
	 */
	public function test_constants() {
		$this->assertTrue( defined( 'SRFM_FILE' ) );
		$this->assertTrue( defined( 'SRFM_BASENAME' ) );
		$this->assertTrue( defined( 'SRFM_DIR' ) );
		$this->assertTrue( defined( 'SRFM_URL' ) );
		$this->assertTrue( defined( 'SRFM_VER' ) );
		$this->assertTrue( defined( 'SRFM_SLUG' ) );
		$this->assertTrue( defined( 'SRFM_FORMS_POST_TYPE' ) );
		$this->assertTrue( defined( 'SRFM_ENTRIES' ) );
		$this->assertTrue( defined( 'SRFM_WEBSITE' ) );
		$this->assertTrue( defined( 'SRFM_AI_MIDDLEWARE' ) );
		$this->assertTrue( defined( 'SRFM_BILLING_PORTAL' ) );
		$this->assertTrue( defined( 'SRFM_SURETRIGGERS_INTEGRATION_BASE_URL' ) );
		$this->assertTrue( defined( 'SRFM_PRO_RECOMMENDED_VER' ) );
		$this->assertTrue( defined( 'SRFM_NPS_SURVEY_URL' ) );
	}
}
