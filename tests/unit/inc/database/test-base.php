<?php
/**
 * Class Test_Database_Base
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Database\Tables\Payments;

/**
 * Tests for Base::get_allowed_orderby_columns() via a concrete child class
 * that does not override the method (Payments).
 */
class Test_Database_Base extends TestCase {

	protected $base;

	protected function setUp(): void {
		parent::setUp();
		$this->base = Payments::get_instance();
	}

	/**
	 * Helper to invoke the protected get_allowed_orderby_columns method.
	 *
	 * @return array<string>
	 */
	private function get_allowed_columns() {
		$method = new \ReflectionMethod( $this->base, 'get_allowed_orderby_columns' );
		$method->setAccessible( true );
		return $method->invoke( $this->base );
	}

	/**
	 * Test get_allowed_orderby_columns returns an array.
	 */
	public function test_get_allowed_orderby_columns() {
		$columns = $this->get_allowed_columns();
		$this->assertIsArray( $columns );
		$this->assertNotEmpty( $columns );
	}

	/**
	 * Test get_allowed_orderby_columns includes all schema keys.
	 */
	public function test_get_allowed_orderby_columns_includes_schema_keys() {
		$columns = $this->get_allowed_columns();
		$schema  = $this->base->get_schema();
		foreach ( array_keys( $schema ) as $key ) {
			$this->assertContains( $key, $columns, "Expected schema key '{$key}' to be in the allowlist." );
		}
	}

	/**
	 * Test get_allowed_orderby_columns always includes updated_at.
	 */
	public function test_get_allowed_orderby_columns_includes_updated_at() {
		$columns = $this->get_allowed_columns();
		$this->assertContains( 'updated_at', $columns );
	}

	/**
	 * Test get_allowed_orderby_columns does not include invalid columns.
	 */
	public function test_get_allowed_orderby_columns_rejects_invalid_column() {
		$columns = $this->get_allowed_columns();
		$this->assertNotContains( 'nonexistent_column', $columns );
		$this->assertNotContains( 'SLEEP(5)', $columns );
	}

	/**
	 * Test get_records_by_args returns an array with default args.
	 */
	public function test_get_records_by_args() {
		$result = $this->base->get_records_by_args();
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_records_by_args rejects SQL injection in orderby and still returns array.
	 */
	public function test_get_records_by_args_rejects_invalid_orderby() {
		$result = $this->base->get_records_by_args( [ 'orderby' => 'id` DESC; DROP TABLE wp_posts; --' ] );
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_records_by_args accepts a valid orderby column.
	 */
	public function test_get_records_by_args_with_valid_orderby() {
		$result = $this->base->get_records_by_args( [ 'orderby' => 'created_at', 'order' => 'ASC' ] );
		$this->assertIsArray( $result );
	}

	/**
	 * Test get_records_by_args normalises an invalid order direction to DESC.
	 */
	public function test_get_records_by_args_normalises_invalid_order() {
		$result = $this->base->get_records_by_args( [ 'order' => 'INVALID; DROP TABLE--' ] );
		$this->assertIsArray( $result );
	}
}
