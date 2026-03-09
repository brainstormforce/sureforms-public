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
	 * Test get_allowed_orderby_columns returns an array.
	 */
	public function test_get_allowed_orderby_columns() {
		$columns = $this->base->get_allowed_orderby_columns();
		$this->assertIsArray( $columns );
		$this->assertNotEmpty( $columns );
	}

	/**
	 * Test get_allowed_orderby_columns includes all schema keys.
	 */
	public function test_get_allowed_orderby_columns_includes_schema_keys() {
		$columns = $this->base->get_allowed_orderby_columns();
		$schema  = $this->base->get_schema();
		foreach ( array_keys( $schema ) as $key ) {
			$this->assertContains( $key, $columns, "Expected schema key '{$key}' to be in the allowlist." );
		}
	}

	/**
	 * Test get_allowed_orderby_columns always includes updated_at.
	 */
	public function test_get_allowed_orderby_columns_includes_updated_at() {
		$columns = $this->base->get_allowed_orderby_columns();
		$this->assertContains( 'updated_at', $columns );
	}

	/**
	 * Test get_allowed_orderby_columns does not include invalid columns.
	 */
	public function test_get_allowed_orderby_columns_rejects_invalid_column() {
		$columns = $this->base->get_allowed_orderby_columns();
		$this->assertNotContains( 'nonexistent_column', $columns );
		$this->assertNotContains( 'SLEEP(5)', $columns );
	}
}
