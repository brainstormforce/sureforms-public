<?php
/**
 * Tests for Admin_Handler class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Payments\Admin\Admin_Handler;

/**
 * Test_Admin_Handler class.
 */
class Test_Admin_Handler extends TestCase {

	/**
	 * Handler instance.
	 *
	 * @var Admin_Handler
	 */
	protected $handler;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->handler = Admin_Handler::get_instance();
	}

	/**
	 * Test fetch_payments is callable.
	 */
	public function test_fetch_payments() {
		$this->assertTrue( method_exists( $this->handler, 'fetch_payments' ) );
	}

	/**
	 * Test fetch_single_payment is callable.
	 */
	public function test_fetch_single_payment() {
		$this->assertTrue( method_exists( $this->handler, 'fetch_single_payment' ) );
	}

	/**
	 * Test fetch_subscription is callable.
	 */
	public function test_fetch_subscription() {
		$this->assertTrue( method_exists( $this->handler, 'fetch_subscription' ) );
	}

	/**
	 * Test fetch_forms_list is callable.
	 */
	public function test_fetch_forms_list() {
		$this->assertTrue( method_exists( $this->handler, 'fetch_forms_list' ) );
	}

	/**
	 * Test ajax_add_note is callable.
	 */
	public function test_ajax_add_note() {
		$this->assertTrue( method_exists( $this->handler, 'ajax_add_note' ) );
	}

	/**
	 * Test ajax_delete_note is callable.
	 */
	public function test_ajax_delete_note() {
		$this->assertTrue( method_exists( $this->handler, 'ajax_delete_note' ) );
	}

	/**
	 * Test ajax_delete_log is callable.
	 */
	public function test_ajax_delete_log() {
		$this->assertTrue( method_exists( $this->handler, 'ajax_delete_log' ) );
	}

	/**
	 * Test ajax_bulk_delete_payments is callable.
	 */
	public function test_ajax_bulk_delete_payments() {
		$this->assertTrue( method_exists( $this->handler, 'ajax_bulk_delete_payments' ) );
	}

	/**
	 * Test ajax_refund_payment is callable.
	 */
	public function test_ajax_refund_payment() {
		$this->assertTrue( method_exists( $this->handler, 'ajax_refund_payment' ) );
	}
}
