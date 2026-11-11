<?php
/**
 * Class Test_Smart_Tags_Payment
 *
 * The {form-payment} smart tag must only resolve a payment row that belongs to
 * the form being rendered, so a client-chosen row id cannot disclose another
 * form's payment data.
 *
 * @package sureforms
 */

use SRFM\Inc\Smart_Tags;
use SRFM\Inc\Database\Tables\Payments;

class Test_Smart_Tags_Payment extends SRFM_Unit_Test_Case {

	public function test_payment_tag_only_resolves_rows_of_the_current_form() {
		$payment_id = Payments::add(
			[
				'form_id'        => 555,
				'gateway'        => 'stripe',
				'status'         => 'succeeded',
				'type'           => 'payment',
				'transaction_id' => 'pi_ctx',
				'customer_email' => 'victim@example.test',
				'customer_name'  => 'Victim',
				'total_amount'   => '12.34',
				'currency'       => 'USD',
			]
		);
		$this->assertIsInt( $payment_id );
		$this->assertGreaterThan( 0, $payment_id );

		$submission = [ 'srfm-payment-blk-lbl-e1-payment' => (string) $payment_id ];
		$tag        = '{form-payment:payment:email}';

		// Rendered in the context of a different form: the row is not disclosed.
		$this->assertSame(
			'',
			Smart_Tags::parse_payment_smart_tag( $tag, $submission, [ 'form-id' => 999 ] ),
			'a payment row from another form must not be resolved'
		);

		// Rendered in the context of its owning form: resolves as before.
		$this->assertSame(
			'victim@example.test',
			Smart_Tags::parse_payment_smart_tag( $tag, $submission, [ 'form-id' => 555 ] ),
			'a payment row of the current form resolves (backward compatible)'
		);
	}

	/**
	 * Without a known current form the row cannot be bound, so it is not resolved:
	 * omitting the context must not skip the check.
	 */
	public function test_payment_tag_resolves_nothing_without_a_form_context() {
		$payment_id = Payments::add(
			[
				'form_id'        => 556,
				'gateway'        => 'stripe',
				'status'         => 'succeeded',
				'type'           => 'payment',
				'transaction_id' => 'pi_noctx',
				'customer_email' => 'victim2@example.test',
				'total_amount'   => '12.34',
				'currency'       => 'USD',
			]
		);
		$submission = [ 'srfm-payment-blk-lbl-e1-payment' => (string) $payment_id ];
		$tag        = '{form-payment:payment:email}';

		foreach ( [ 'absent' => null, 'empty array' => [], 'no form-id' => [ 'other' => 1 ], 'zero' => [ 'form-id' => 0 ], 'non-numeric' => [ 'form-id' => 'abc' ], 'array' => [ 'form-id' => [ 556 ] ] ] as $label => $context ) {
			$this->assertSame( '', Smart_Tags::parse_payment_smart_tag( $tag, $submission, $context ), "context {$label} must not resolve the row" );
		}

		// The public entry point resolves it once the current form is passed.
		$this->assertSame( 'victim2@example.test', ( new Smart_Tags() )->process_smart_tags( $tag, $submission, [ 'form-id' => 556 ] ) );
		$this->assertSame( '', ( new Smart_Tags() )->process_smart_tags( $tag, $submission ) );
	}
}
