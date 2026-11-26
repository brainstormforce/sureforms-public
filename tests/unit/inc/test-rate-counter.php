<?php
/**
 * Class Test_Rate_Counter
 *
 * Covers Form_Submit::is_rate_limited(), the consumer of the shared per-bucket
 * counter. Helper::hit_rate_counter() itself is covered in test-helper.php.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Submit;

class Test_Rate_Counter extends TestCase {

	/**
	 * REMOTE_ADDR before the test, restored in tearDown.
	 *
	 * @var string|null
	 */
	private $remote_addr;

	protected function setUp(): void {
		parent::setUp();
		$this->remote_addr      = $_SERVER['REMOTE_ADDR'] ?? null;
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	}

	protected function tearDown(): void {
		wp_using_ext_object_cache( false );

		if ( null === $this->remote_addr ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $this->remote_addr;
		}

		parent::tearDown();
	}

	/**
	 * Call the private Form_Submit::is_rate_limited().
	 *
	 * @param string $prefix  Bucket prefix.
	 * @param int    $form_id Form id.
	 * @return bool
	 */
	private function is_rate_limited( $prefix, $form_id ) {
		$m = new ReflectionMethod( Form_Submit::class, 'is_rate_limited' );
		$m->setAccessible( true );
		return $m->invoke( Form_Submit::get_instance(), $prefix, $form_id );
	}

	/**
	 * Open a second database connection, so an advisory lock taken on it is
	 * held by "another request" from the point of view of the test's $wpdb.
	 *
	 * @return wpdb
	 */
	private function other_connection() {
		$other = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
		$other->suppress_errors( true );
		return $other;
	}

	/**
	 * Ten requests per IP + form are admitted, the eleventh is not; other forms and
	 * other prefixes keep their own allowance.
	 */
	public function test_is_rate_limited_admits_ten_per_bucket() {
		$form_id = wp_rand( 100000, 999999 );

		for ( $i = 1; $i <= 10; $i++ ) {
			$this->assertFalse( $this->is_rate_limited( 'srfm_uv_', $form_id ), "Request {$i} must be admitted." );
		}

		$this->assertTrue( $this->is_rate_limited( 'srfm_uv_', $form_id ), 'The eleventh request must be limited.' );
		$this->assertFalse( $this->is_rate_limited( 'srfm_uv_', $form_id + 1 ), 'Another form is not limited.' );
		$this->assertFalse( $this->is_rate_limited( 'srfm_cl_', $form_id ), 'Another prefix is not limited.' );
	}

	/**
	 * A request that arrives while another request holds the same bucket is limited.
	 */
	public function test_is_rate_limited_denies_concurrent_request_on_same_bucket() {
		$form_id = wp_rand( 100000, 999999 );
		$lock    = substr( 'srfm_uv_' . md5( '127.0.0.1_' . $form_id ), 0, 64 );
		$other   = $this->other_connection();

		$this->assertSame( '1', (string) $other->get_var( $other->prepare( 'SELECT GET_LOCK(%s, 0)', $lock ) ), 'Fixture: the second connection must hold the lock.' );

		try {
			$this->assertTrue( $this->is_rate_limited( 'srfm_uv_', $form_id ) );
			$this->assertFalse( $this->is_rate_limited( 'srfm_cl_', $form_id ), 'Another prefix has its own lock.' );
		} finally {
			$other->query( $other->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
			$other->close();
		}
	}

	/**
	 * No usable client IP means the request is limited.
	 */
	public function test_is_rate_limited_fails_closed_without_ip() {
		unset( $_SERVER['REMOTE_ADDR'] );
		$this->assertTrue( $this->is_rate_limited( 'srfm_uv_', 1 ) );

		$_SERVER['REMOTE_ADDR'] = 'not-an-ip';
		$this->assertTrue( $this->is_rate_limited( 'srfm_uv_', 1 ) );
	}
}
