<?php
/**
 * Class Test_Class_Spec_Filesystem
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Class_Spec_Filesystem extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		// The Spectra/UAG filesystem helper is loaded on demand by the block
		// loader; ensure it is available when running this test in isolation.
		if ( ! class_exists( 'Spec_Filesystem' ) ) {
			require_once SRFM_DIR . 'modules/gutenberg/classes/class-spec-filesystem.php';
		}
	}

	/**
	 * request_filesystem_credentials() forces credentials to true so that
	 * WP_Filesystem can initialize via the 'direct' method without prompting
	 * for FTP credentials.
	 */
	public function test_request_filesystem_credentials() {
		$this->assertTrue( Spec_Filesystem::get_instance()->request_filesystem_credentials() );
	}
}
