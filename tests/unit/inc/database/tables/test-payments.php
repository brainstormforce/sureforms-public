<?php
/**
 * Class Test_Payments_Table
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Database\Tables\Payments;

class Test_Payments_Table extends TestCase {

	protected $payments;

	protected function setUp(): void {
		parent::setUp();
		$this->payments = Payments::get_instance();
	}

	// --- Schema ---

	public function test_get_schema_returns_array() {
		$schema = $this->payments->get_schema();
		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );
	}

	public function test_get_schema_has_required_fields() {
		$schema = $this->payments->get_schema();
		$required_keys = [
			'id', 'form_id', 'block_id', 'status', 'total_amount',
			'refunded_amount', 'currency', 'entry_id', 'gateway',
			'type', 'mode', 'transaction_id', 'customer_id',
			'subscription_id', 'subscription_status', 'parent_subscription_id',
			'payment_data', 'extra', 'log', 'created_at', 'updated_at',
			'srfm_txn_id', 'customer_email', 'customer_name',
		];
		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $schema, "Schema missing key: {$key}" );
		}
	}

	public function test_get_schema_status_default_is_pending() {
		$schema = $this->payments->get_schema();
		$this->assertEquals( 'pending', $schema['status']['default'] );
	}

	public function test_get_schema_total_amount_default() {
		$schema = $this->payments->get_schema();
		$this->assertEquals( '0.00000000', $schema['total_amount']['default'] );
	}

	public function test_get_schema_refunded_amount_default() {
		$schema = $this->payments->get_schema();
		$this->assertEquals( '0.00000000', $schema['refunded_amount']['default'] );
	}

	// --- Column definitions ---

	public function test_get_columns_definition_returns_array() {
		$columns = $this->payments->get_columns_definition();
		$this->assertIsArray( $columns );
		$this->assertNotEmpty( $columns );
	}

	public function test_get_columns_definition_has_primary_key() {
		$columns = $this->payments->get_columns_definition();
		$found   = false;
		foreach ( $columns as $col ) {
			if ( strpos( $col, 'PRIMARY KEY' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Column definitions must include a PRIMARY KEY' );
	}

	public function test_get_columns_definition_has_total_amount_decimal() {
		$columns = $this->payments->get_columns_definition();
		$found   = false;
		foreach ( $columns as $col ) {
			if ( strpos( $col, 'total_amount DECIMAL' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'total_amount should be DECIMAL type' );
	}

	// --- Validation: is_valid_status ---

	public function test_is_valid_status_pending() {
		$this->assertTrue( Payments::is_valid_status( 'pending' ) );
	}

	public function test_is_valid_status_succeeded() {
		$this->assertTrue( Payments::is_valid_status( 'succeeded' ) );
	}

	public function test_is_valid_status_failed() {
		$this->assertTrue( Payments::is_valid_status( 'failed' ) );
	}

	public function test_is_valid_status_canceled() {
		$this->assertTrue( Payments::is_valid_status( 'canceled' ) );
	}

	public function test_is_valid_status_requires_action() {
		$this->assertTrue( Payments::is_valid_status( 'requires_action' ) );
	}

	public function test_is_valid_status_refunded() {
		$this->assertTrue( Payments::is_valid_status( 'refunded' ) );
	}

	public function test_is_valid_status_partially_refunded() {
		$this->assertTrue( Payments::is_valid_status( 'partially_refunded' ) );
	}

	public function test_is_valid_status_invalid() {
		$this->assertFalse( Payments::is_valid_status( 'unknown_status' ) );
	}

	public function test_is_valid_status_empty() {
		$this->assertFalse( Payments::is_valid_status( '' ) );
	}

	// --- Validation: is_valid_currency ---

	public function test_is_valid_currency_usd() {
		$this->assertTrue( Payments::is_valid_currency( 'USD' ) );
	}

	public function test_is_valid_currency_lowercase() {
		$this->assertTrue( Payments::is_valid_currency( 'eur' ) );
	}

	public function test_is_valid_currency_invalid() {
		$this->assertFalse( Payments::is_valid_currency( 'XYZ' ) );
	}

	public function test_is_valid_currency_empty() {
		$this->assertFalse( Payments::is_valid_currency( '' ) );
	}

	// --- Validation: is_valid_gateway ---

	public function test_is_valid_gateway_stripe() {
		$this->assertTrue( Payments::is_valid_gateway( 'stripe' ) );
	}

	public function test_is_valid_gateway_invalid() {
		$this->assertFalse( Payments::is_valid_gateway( 'paypal' ) );
	}

	public function test_is_valid_gateway_empty() {
		$this->assertFalse( Payments::is_valid_gateway( '' ) );
	}

	// --- Validation: is_valid_mode ---

	public function test_is_valid_mode_test() {
		$this->assertTrue( Payments::is_valid_mode( 'test' ) );
	}

	public function test_is_valid_mode_live() {
		$this->assertTrue( Payments::is_valid_mode( 'live' ) );
	}

	public function test_is_valid_mode_invalid() {
		$this->assertFalse( Payments::is_valid_mode( 'staging' ) );
	}

	public function test_is_valid_mode_empty() {
		$this->assertFalse( Payments::is_valid_mode( '' ) );
	}

	// --- Validation: is_valid_subscription_status ---

	public function test_is_valid_subscription_status_active() {
		$this->assertTrue( Payments::is_valid_subscription_status( 'active' ) );
	}

	public function test_is_valid_subscription_status_canceled() {
		$this->assertTrue( Payments::is_valid_subscription_status( 'canceled' ) );
	}

	public function test_is_valid_subscription_status_past_due() {
		$this->assertTrue( Payments::is_valid_subscription_status( 'past_due' ) );
	}

	public function test_is_valid_subscription_status_trialing() {
		$this->assertTrue( Payments::is_valid_subscription_status( 'trialing' ) );
	}

	public function test_is_valid_subscription_status_paused() {
		$this->assertTrue( Payments::is_valid_subscription_status( 'paused' ) );
	}

	public function test_is_valid_subscription_status_invalid() {
		$this->assertFalse( Payments::is_valid_subscription_status( 'expired' ) );
	}

	public function test_is_valid_subscription_status_empty() {
		$this->assertFalse( Payments::is_valid_subscription_status( '' ) );
	}

	// --- get_valid_subscription_statuses ---

	public function test_get_valid_subscription_statuses_returns_array() {
		$statuses = Payments::get_valid_subscription_statuses();
		$this->assertIsArray( $statuses );
		$this->assertNotEmpty( $statuses );
		$this->assertContains( 'active', $statuses );
		$this->assertContains( 'canceled', $statuses );
	}

	// --- CRUD: empty/null param guards ---

	public function test_update_empty_payment_id_returns_false() {
		$this->assertFalse( Payments::update( 0 ) );
	}

	public function test_get_empty_payment_id_returns_null() {
		$this->assertNull( Payments::get( 0 ) );
	}

	public function test_delete_empty_payment_id_returns_false() {
		$this->assertFalse( Payments::delete( 0 ) );
	}

	public function test_get_extra_data_empty_id_returns_empty_array() {
		$this->assertEquals( [], Payments::get_extra_data( 0 ) );
	}

	public function test_update_extra_key_empty_id_returns_false() {
		$this->assertFalse( Payments::update_extra_key( 0, 'key', 'value' ) );
	}

	public function test_update_extra_key_empty_key_returns_false() {
		$this->assertFalse( Payments::update_extra_key( 1, '', 'value' ) );
	}

	public function test_add_extra_data_empty_id_returns_false() {
		$this->assertFalse( Payments::add_extra_data( 0, [ 'key' => 'val' ] ) );
	}

	public function test_add_extra_data_empty_data_returns_false() {
		$this->assertFalse( Payments::add_extra_data( 1, [] ) );
	}

	public function test_add_extra_data_non_array_returns_false() {
		$this->assertFalse( Payments::add_extra_data( 1, 'string' ) );
	}

	public function test_remove_extra_key_empty_id_returns_false() {
		$this->assertFalse( Payments::remove_extra_key( 0, 'key' ) );
	}

	public function test_remove_extra_key_empty_key_returns_false() {
		$this->assertFalse( Payments::remove_extra_key( 1, '' ) );
	}

	public function test_get_extra_value_empty_id_returns_default() {
		$this->assertEquals( 'default_val', Payments::get_extra_value( 0, 'key', 'default_val' ) );
	}

	public function test_get_extra_value_empty_key_returns_default() {
		$this->assertNull( Payments::get_extra_value( 1, '' ) );
	}

	// --- Refund guards ---

	public function test_get_refunded_amount_empty_id_returns_zero() {
		$this->assertEquals( 0.0, Payments::get_refunded_amount( 0 ) );
	}

	public function test_get_refundable_amount_empty_id_returns_zero() {
		$this->assertEquals( 0.0, Payments::get_refundable_amount( 0 ) );
	}

	public function test_is_fully_refunded_empty_id_returns_false() {
		$this->assertFalse( Payments::is_fully_refunded( 0 ) );
	}

	public function test_is_partially_refunded_empty_id_returns_false() {
		$this->assertFalse( Payments::is_partially_refunded( 0 ) );
	}

	public function test_add_refund_amount_empty_id_returns_false() {
		$this->assertFalse( Payments::add_refund_amount( 0, 10.00 ) );
	}

	public function test_add_refund_amount_zero_amount_returns_false() {
		$this->assertFalse( Payments::add_refund_amount( 1, 0 ) );
	}

	public function test_add_refund_amount_negative_amount_returns_false() {
		$this->assertFalse( Payments::add_refund_amount( 1, -5.00 ) );
	}

	// --- add_refund_to_payment_data guards ---

	public function test_add_refund_to_payment_data_empty_id_returns_false() {
		$this->assertFalse( Payments::add_refund_to_payment_data( 0, [ 'refund_id' => 'r1' ] ) );
	}

	public function test_add_refund_to_payment_data_empty_data_returns_false() {
		$this->assertFalse( Payments::add_refund_to_payment_data( 1, [] ) );
	}

	public function test_add_refund_to_payment_data_non_array_returns_false() {
		$this->assertFalse( Payments::add_refund_to_payment_data( 1, 'not_array' ) );
	}

	public function test_add_refund_to_payment_data_missing_refund_id_returns_false() {
		$this->assertFalse( Payments::add_refund_to_payment_data( 1, [ 'amount' => 10 ] ) );
	}

	// --- get_payment_data empty guard ---

	public function test_get_payment_data_empty_id_returns_empty_array() {
		$this->assertEquals( [], Payments::get_payment_data( 0 ) );
	}

	// --- Lookup guards ---

	public function test_get_by_entry_id_empty_returns_empty_array() {
		$this->assertEquals( [], Payments::get_by_entry_id( 0 ) );
	}

	public function test_get_by_transaction_id_empty_returns_null() {
		$this->assertNull( Payments::get_by_transaction_id( '' ) );
	}

	public function test_get_all_payment_ids_for_form_empty_returns_empty() {
		$this->assertEquals( [], Payments::get_all_payment_ids_for_form( 0 ) );
	}

	public function test_get_form_ids_by_payments_empty_returns_empty() {
		$this->assertEquals( [], Payments::get_form_ids_by_payments( [] ) );
	}

	public function test_get_form_ids_by_payments_non_array_returns_empty() {
		$this->assertEquals( [], Payments::get_form_ids_by_payments( 'not_array' ) );
	}

	// --- Subscription record checks with empty ID ---

	public function test_is_subscription_record_empty_id_returns_false() {
		$this->assertFalse( Payments::is_subscription_record( 0 ) );
	}

	public function test_is_subscription_payment_transaction_empty_id_returns_false() {
		$this->assertFalse( Payments::is_subscription_payment_transaction( 0 ) );
	}

	// --- Subscription related payments ---

	public function test_get_subscription_related_payments_empty_id_returns_empty() {
		$this->assertEquals( [], Payments::get_subscription_related_payments( '' ) );
	}

	public function test_get_main_subscription_record_empty_id_returns_null() {
		$this->assertNull( Payments::get_main_subscription_record( '' ) );
	}

	// --- get_all defaults ---

	public function test_get_all_returns_array() {
		$result = Payments::get_all();
		$this->assertIsArray( $result );
	}

	// --- get_total_payments_by_status ---

	public function test_get_total_payments_by_status_all_returns_int() {
		$result = Payments::get_total_payments_by_status( 'all' );
		$this->assertIsInt( $result );
	}

	// --- CRUD integration (add, get, update, delete) ---

	public function test_add_and_get_payment() {
		$data = [
			'form_id'        => 100,
			'block_id'       => 'test_block',
			'status'         => 'pending',
			'total_amount'   => '50.00000000',
			'refunded_amount' => '0.00000000',
			'currency'       => 'USD',
			'entry_id'       => 0,
			'gateway'        => 'stripe',
			'type'           => 'payment',
			'mode'           => 'test',
			'transaction_id' => 'pi_test_abc123',
			'customer_id'    => 'cus_test_123',
			'customer_email' => 'test@example.com',
			'customer_name'  => 'Test User',
			'srfm_txn_id'    => 'SRFM-001',
		];

		$payment_id = Payments::add( $data );
		$this->assertNotFalse( $payment_id );
		$this->assertIsInt( $payment_id );

		$payment = Payments::get( $payment_id );
		$this->assertIsArray( $payment );
		$this->assertEquals( 100, $payment['form_id'] );
		$this->assertEquals( 'pending', $payment['status'] );
		$this->assertEquals( 'stripe', $payment['gateway'] );
		$this->assertEquals( 'test@example.com', $payment['customer_email'] );

		// Update.
		$updated = Payments::update( $payment_id, [ 'status' => 'succeeded' ] );
		$this->assertNotFalse( $updated );

		$refreshed = Payments::get( $payment_id );
		$this->assertEquals( 'succeeded', $refreshed['status'] );

		// Delete.
		$deleted = Payments::delete( $payment_id );
		$this->assertNotFalse( $deleted );
	}

	public function test_get_by_transaction_id_found() {
		$data = [
			'form_id'        => 200,
			'block_id'       => 'txn_block',
			'status'         => 'succeeded',
			'total_amount'   => '25.00000000',
			'refunded_amount' => '0.00000000',
			'currency'       => 'EUR',
			'entry_id'       => 0,
			'gateway'        => 'stripe',
			'type'           => 'payment',
			'mode'           => 'test',
			'transaction_id' => 'pi_unique_txn_test',
			'customer_id'    => 'cus_200',
			'customer_email' => 'txn@example.com',
			'customer_name'  => 'Txn User',
			'srfm_txn_id'    => 'SRFM-TXN-001',
		];

		$payment_id = Payments::add( $data );
		$this->assertNotFalse( $payment_id );

		$found = Payments::get_by_transaction_id( 'pi_unique_txn_test' );
		$this->assertIsArray( $found );
		$this->assertEquals( 'pi_unique_txn_test', $found['transaction_id'] );

		// Cleanup.
		Payments::delete( $payment_id );
	}

	public function test_refund_workflow() {
		$data = [
			'form_id'        => 300,
			'block_id'       => 'refund_block',
			'status'         => 'succeeded',
			'total_amount'   => '100.00000000',
			'refunded_amount' => '0.00000000',
			'currency'       => 'USD',
			'entry_id'       => 0,
			'gateway'        => 'stripe',
			'type'           => 'payment',
			'mode'           => 'test',
			'transaction_id' => 'pi_refund_test',
			'customer_id'    => 'cus_300',
			'customer_email' => 'refund@example.com',
			'customer_name'  => 'Refund User',
			'srfm_txn_id'    => 'SRFM-REFUND-001',
		];

		$payment_id = Payments::add( $data );
		$this->assertNotFalse( $payment_id );

		// Not refunded yet.
		$this->assertFalse( Payments::is_fully_refunded( $payment_id ) );
		$this->assertFalse( Payments::is_partially_refunded( $payment_id ) );
		$this->assertEquals( 0.0, Payments::get_refunded_amount( $payment_id ) );
		$this->assertEquals( 100.0, Payments::get_refundable_amount( $payment_id ) );

		// Partial refund.
		Payments::add_refund_amount( $payment_id, 30.00 );
		$this->assertTrue( Payments::is_partially_refunded( $payment_id ) );
		$this->assertFalse( Payments::is_fully_refunded( $payment_id ) );
		$this->assertEquals( 30.0, Payments::get_refunded_amount( $payment_id ) );
		$this->assertEquals( 70.0, Payments::get_refundable_amount( $payment_id ) );

		// Full refund.
		Payments::add_refund_amount( $payment_id, 70.00 );
		$this->assertTrue( Payments::is_fully_refunded( $payment_id ) );
		$this->assertEquals( 100.0, Payments::get_refunded_amount( $payment_id ) );
		$this->assertEquals( 0.0, Payments::get_refundable_amount( $payment_id ) );

		// Cleanup.
		Payments::delete( $payment_id );
	}

	public function test_add_refund_to_payment_data_workflow() {
		$data = [
			'form_id'        => 400,
			'block_id'       => 'refund_data_block',
			'status'         => 'succeeded',
			'total_amount'   => '200.00000000',
			'refunded_amount' => '0.00000000',
			'currency'       => 'USD',
			'entry_id'       => 0,
			'gateway'        => 'stripe',
			'type'           => 'payment',
			'mode'           => 'test',
			'transaction_id' => 'pi_refund_data_test',
			'customer_id'    => 'cus_400',
			'customer_email' => 'data@example.com',
			'customer_name'  => 'Data User',
			'srfm_txn_id'    => 'SRFM-DATA-001',
		];

		$payment_id = Payments::add( $data );
		$this->assertNotFalse( $payment_id );

		// Add refund to payment_data.
		$refund_data = [
			'refund_id' => 're_test_123',
			'amount'    => 50.00,
			'reason'    => 'Customer request',
		];

		$result = Payments::add_refund_to_payment_data( $payment_id, $refund_data );
		$this->assertNotFalse( $result );

		// Verify refund data stored.
		$payment_data = Payments::get_payment_data( $payment_id );
		$this->assertIsArray( $payment_data );
		$this->assertArrayHasKey( 'refunds', $payment_data );
		$this->assertArrayHasKey( 're_test_123', $payment_data['refunds'] );

		// Add second refund - should not overwrite first.
		$refund_data_2 = [
			'refund_id' => 're_test_456',
			'amount'    => 25.00,
			'reason'    => 'Partial return',
		];
		Payments::add_refund_to_payment_data( $payment_id, $refund_data_2 );

		$payment_data = Payments::get_payment_data( $payment_id );
		$this->assertCount( 2, $payment_data['refunds'] );
		$this->assertArrayHasKey( 're_test_123', $payment_data['refunds'] );
		$this->assertArrayHasKey( 're_test_456', $payment_data['refunds'] );

		// Cleanup.
		Payments::delete( $payment_id );
	}

	public function test_get_nonexistent_payment_returns_null() {
		$this->assertNull( Payments::get( 999999 ) );
	}

	public function test_get_refunded_amount_nonexistent_returns_zero() {
		$this->assertEquals( 0.0, Payments::get_refunded_amount( 999999 ) );
	}

	public function test_get_refundable_amount_nonexistent_returns_zero() {
		$this->assertEquals( 0.0, Payments::get_refundable_amount( 999999 ) );
	}

	public function test_is_fully_refunded_nonexistent_returns_false() {
		$this->assertFalse( Payments::is_fully_refunded( 999999 ) );
	}

	public function test_is_subscription_record_nonexistent_returns_false() {
		$this->assertFalse( Payments::is_subscription_record( 999999 ) );
	}

	public function test_is_subscription_payment_transaction_nonexistent_returns_false() {
		$this->assertFalse( Payments::is_subscription_payment_transaction( 999999 ) );
	}
}
