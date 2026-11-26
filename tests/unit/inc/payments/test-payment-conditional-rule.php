<?php
/**
 * Class Test_Payment_Conditional_Rule
 *
 * A payment block under a conditional-logic rule is exempt from the verified-payment
 * requirement only when the stored rule hides it for the submitted values.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Payments\Front_End;
use SRFM\Inc\Payments\Payment_Helper;

class Test_Payment_Conditional_Rule extends TestCase {

	/**
	 * Submitted key of the dropdown the rules read.
	 */
	const PLAN_KEY = 'srfm-dropdown-plan0001-lbl-UGxhbg-plan';

	/**
	 * Form under test.
	 *
	 * @var int
	 */
	private $form_id = 0;

	protected function setUp(): void {
		parent::setUp();

		add_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );
		register_post_meta(
			SRFM_FORMS_POST_TYPE,
			'_srfm_conditional_logic',
			[
				'type'   => 'array',
				'single' => true,
			]
		);

		remove_all_actions( 'wp_insert_post_data' );

		$this->form_id = wp_insert_post(
			[
				'post_title'   => 'Conditional Payment Form',
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:srfm/dropdown {"block_id":"plan0001"} /-->'
					. '<!-- wp:srfm/payment ' . wp_json_encode(
						[
							'block_id'           => 'pay00001',
							'paymentType'        => 'one-time',
							'customerEmailField' => 'email',
							'fixedAmount'        => 25,
						]
					) . ' /-->',
			]
		);
	}

	protected function tearDown(): void {
		wp_delete_post( $this->form_id, true );
		unregister_post_meta( SRFM_FORMS_POST_TYPE, '_srfm_conditional_logic' );
		remove_filter( 'srfm_payment_methods_registry', [ $this, 'force_enabled_payment_method' ] );

		parent::tearDown();
	}

	/**
	 * Force a usable payment method so the payment block counts as active.
	 *
	 * @param array<string,mixed> $methods Registered methods.
	 * @return array<string,mixed>
	 */
	public function force_enabled_payment_method( $methods ) {
		$methods['stripe']['enabled'] = true;

		return $methods;
	}

	/**
	 * Store one rule for the payment block.
	 *
	 * @param string       $action   show|hide.
	 * @param string       $operator Operator.
	 * @param string|array $value    Condition value.
	 * @return void
	 */
	private function set_rule( $action, $operator, $value ) {
		// update_post_meta() unslashes, so slash first to store a pattern's backslashes.
		update_post_meta(
			$this->form_id,
			'_srfm_conditional_logic',
			wp_slash(
				[
					[
						'pay00001' => [
							'action' => $action,
							'logic'  => [ [ [ 'field' => 'plan0001', 'operator' => $operator, 'value' => $value ] ] ],
						],
					],
				]
			)
		);
	}

	/**
	 * Submit the form with the given plan and no payment field.
	 *
	 * @param string|null $plan Plan value, null to omit the key.
	 * @return array<mixed>
	 */
	private function submit_without_payment( $plan ) {
		$form_data = [ 'form-id' => $this->form_id ];
		if ( null !== $plan ) {
			$form_data[ self::PLAN_KEY ] = $plan;
		}

		return Front_End::get_instance()->validate_payment_fields( $form_data );
	}

	public function test_validate_payment_fields_requires_payment_when_show_rule_matches() {
		$this->set_rule( 'show', '==', 'Premium' );

		$this->assertArrayHasKey( 'error', $this->submit_without_payment( 'Premium' ), 'A shown payment field must be paid.' );
		$this->assertArrayNotHasKey( 'error', $this->submit_without_payment( 'Free' ), 'A hidden payment field needs no payment.' );
		$this->assertArrayNotHasKey( 'error', $this->submit_without_payment( null ), 'An absent trigger reads as empty, as in the browser.' );
	}

	public function test_validate_payment_fields_requires_payment_when_hide_rule_does_not_match() {
		$this->set_rule( 'hide', '==', 'Free' );

		$this->assertArrayHasKey( 'error', $this->submit_without_payment( 'Premium' ), 'A hide rule that does not match leaves the field visible.' );
		$this->assertArrayHasKey( 'error', $this->submit_without_payment( null ) );
		$this->assertArrayNotHasKey( 'error', $this->submit_without_payment( 'Free' ) );
	}

	public function test_validate_payment_fields_requires_payment_when_rule_cannot_be_evaluated() {
		// An operator the server does not know, or a pattern it cannot compile, never exempts.
		foreach ( [ 'not-an-operator' => 'Premium', 'matchesPattern' => '(', 'doesNotMatchPattern' => '[a-' ] as $operator => $value ) {
			$this->set_rule( 'show', $operator, $value );
			$this->assertArrayHasKey( 'error', $this->submit_without_payment( 'Free' ), $operator );
		}

		// An unknown action never exempts.
		$this->set_rule( 'toggle', '==', 'Premium' );
		$this->assertArrayHasKey( 'error', $this->submit_without_payment( 'Free' ) );

		// A structured value for the trigger cannot be compared.
		$this->set_rule( 'show', '==', 'Premium' );
		$this->assertArrayHasKey( 'error', $this->submit_without_payment( [ 'Free' ] ) );
	}

	public function test_validate_payment_fields_requires_payment_when_pattern_is_pcre_only() {
		// Group syntax `new RegExp()` throws on never exempts, though PCRE would compile it.
		foreach ( [ '(?i)free', '(*UCP)free', '(?>free)', '(?P<p>free)', '(?#note)free' ] as $pattern ) {
			$this->set_rule( 'show', 'doesNotMatchPattern', $pattern );
			$this->assertArrayHasKey( 'error', $this->submit_without_payment( 'Free' ), $pattern );
		}
	}

	public function test_get_required_payment_block_ids_without_form_data_keeps_conditional_blocks() {
		$this->set_rule( 'show', '==', 'Premium' );

		$this->assertSame( [ 'pay00001' ], Payment_Helper::get_required_payment_block_ids( $this->form_id ) );
		$this->assertSame( [], Payment_Helper::get_required_payment_block_ids( $this->form_id, [ self::PLAN_KEY => 'Free' ] ) );
	}

	public function test_is_hidden_by_conditional_rules_requires_every_rule_to_hide() {
		update_post_meta(
			$this->form_id,
			'_srfm_conditional_logic',
			[
				[ 'pay00001' => [ 'action' => 'show', 'logic' => [ [ [ 'field' => 'plan0001', 'operator' => '==', 'value' => 'Premium' ] ] ] ] ],
				[ 'pay00001' => [ 'action' => 'hide', 'logic' => [ [ [ 'field' => 'plan0001', 'operator' => '==', 'value' => 'Gold' ] ] ] ] ],
			]
		);

		$this->assertSame( [ 'pay00001' ], Payment_Helper::get_required_payment_block_ids( $this->form_id, [ self::PLAN_KEY => 'Free' ] ) );
	}

	/**
	 * Operator semantics mirror the browser evaluator in SureForms Pro conditional-logic.js.
	 *
	 * @return array<string, array{0:string,1:string|array<string>,2:string|null,3:bool}>
	 */
	public function condition_provider() {
		return [
			'== exact'                   => [ '==', 'Premium', 'Premium', true ],
			'== multi value'             => [ '==', 'Premium', 'Basic | Premium', true ],
			'== miss'                    => [ '==', 'Premium', 'Basic', false ],
			'!= hit'                     => [ '!=', 'Premium', 'Basic', true ],
			'!= multi value contains'    => [ '!=', 'Premium', 'Basic | Premium', false ],
			'null empty'                 => [ 'null', '', '', true ],
			'null absent'                => [ 'null', '', null, true ],
			'!null'                      => [ '!null', '', 'x', true ],
			'includes'                   => [ 'includes', 'rem', 'Premium', true ],
			'!includes'                  => [ '!includes', 'rem', 'Premium', false ],
			'startWith'                  => [ 'startWith', 'Pre', 'Premium', true ],
			'endWith'                    => [ 'endWith', 'ium', 'Premium', true ],
			'endWith miss'               => [ 'endWith', 'Pre', 'Premium', false ],
			'> numeric'                  => [ '>', '10', '11', true ],
			'<= numeric'                 => [ '<=', '10', '11', false ],
			'< empty reads as zero'      => [ '<', '1', '', true ],
			'between'                    => [ 'between', '5, 10', '7', true ],
			'between outside'            => [ 'between', '5,10', '11', false ],
			'isChecked on a non-checkbox' => [ 'isChecked', '', 'on', false ],
			'isChecked reads "true"'      => [ 'isChecked', '', 'true', true ],
			'matchesPattern'              => [ 'matchesPattern', '^Pre', 'Premium', true ],
			'matchesPattern miss'         => [ 'matchesPattern', '^Pre', 'Basic', false ],
			'matchesPattern $ is the end' => [ 'matchesPattern', 'um$', "Premium\n", false ],
			'doesNotMatchPattern'         => [ 'doesNotMatchPattern', '\\d', 'Premium', true ],
			'pattern \\d is ASCII'        => [ 'matchesPattern', '^\\d$', "\u{0663}", false ],
			'pattern \\w is ASCII'        => [ 'matchesPattern', '^\\w$', "\u{00E9}", false ],
			'pattern . is one character'  => [ 'matchesPattern', '^.$', "\u{00E9}", true ],
			'pattern lookbehind'          => [ 'matchesPattern', '(?<=P)re', 'Premium', true ],
			'pattern named group'         => [ 'matchesPattern', '^(?<p>Pre)', 'Premium', true ],
			'pattern (? in a class'       => [ 'matchesPattern', '^[(?i)]', '(Premium', true ],
			'pattern escaped (?'          => [ 'matchesPattern', '\\(?i', 'Premium', true ],
			'date operator on a non-date' => [ 'isAfter', '2025-01-01', '2025-06-01', false ],
			'in array'                   => [ 'in', [ 'Gold', 'Premium' ], 'Premium', true ],
			'in multi value'             => [ 'in', [ 'Gold' ], 'Basic | Gold', true ],
			'!in array'                  => [ '!in', [ 'Gold' ], 'Premium', true ],
			'isSelected'                 => [ 'isSelected', '', ' Premium ', true ],
			'!isSelected whitespace'     => [ '!isSelected', '', '  ', true ],
		];
	}

	/**
	 * A show rule hides the block exactly when its condition does not hold.
	 *
	 * @dataProvider condition_provider
	 *
	 * @param string               $operator  Operator.
	 * @param string|array<string> $value     Condition value.
	 * @param string|null          $submitted Submitted trigger value, null when absent.
	 * @param bool                 $matches   Whether the condition holds.
	 */
	public function test_evaluate_conditional_condition_mirrors_browser( $operator, $value, $submitted, $matches ) {
		$this->set_rule( 'show', $operator, $value );

		$form_data = null === $submitted ? [] : [ self::PLAN_KEY => $submitted ];
		$required  = Payment_Helper::get_required_payment_block_ids( $this->form_id, $form_data );

		$this->assertSame( $matches ? [ 'pay00001' ] : [], $required );
	}

	public function test_evaluate_conditional_logic_ors_groups_and_ands_conditions() {
		$premium = [ 'field' => 'plan0001', 'operator' => '==', 'value' => 'Premium' ];
		$gold    = [ 'field' => 'plan0001', 'operator' => '==', 'value' => 'Gold' ];
		$pattern = [ 'field' => 'plan0001', 'operator' => 'not-an-operator', 'value' => '.*' ];

		$required = function ( $logic ) {
			update_post_meta(
				$this->form_id,
				'_srfm_conditional_logic',
				[ [ 'pay00001' => [ 'action' => 'show', 'logic' => $logic ] ] ]
			);

			return Payment_Helper::get_required_payment_block_ids( $this->form_id, [ self::PLAN_KEY => 'Premium' ] );
		};

		// OR of groups: one matching group shows the field.
		$this->assertSame( [ 'pay00001' ], $required( [ [ $gold ], [ $premium ] ] ) );
		// AND within a group: one failing condition fails the group, so the field hides.
		$this->assertSame( [], $required( [ [ $premium, $gold ] ] ) );
		// A false condition settles the group even beside one that cannot be evaluated.
		$this->assertSame( [], $required( [ [ $gold, $pattern ] ] ) );
		// Otherwise an unevaluable condition leaves visibility unknown, so payment stays required.
		$this->assertSame( [ 'pay00001' ], $required( [ [ $premium, $pattern ] ] ) );
		$this->assertSame( [ 'pay00001' ], $required( [ [ $gold ], [ $pattern ] ] ) );
	}

	public function test_to_conditional_number_fails_closed_on_non_numeric_operands() {
		$this->set_rule( 'show', '>', '10' );
		$this->assertSame( [ 'pay00001' ], Payment_Helper::get_required_payment_block_ids( $this->form_id, [ self::PLAN_KEY => '1,000' ] ) );

		$this->set_rule( 'show', 'between', '10' );
		$this->assertSame( [ 'pay00001' ], Payment_Helper::get_required_payment_block_ids( $this->form_id, [ self::PLAN_KEY => '5' ] ) );
	}

	public function test_get_conditional_logic_rules_ignores_rules_without_conditions() {
		update_post_meta(
			$this->form_id,
			'_srfm_conditional_logic',
			[
				[ 'pay00001' => [ 'action' => 'show', 'logic' => [ [ [ 'field' => 'plan0001', 'operator' => '==', 'value' => 'Premium' ] ] ] ] ],
				[ 'pay00001' => [ 'action' => 'hide', 'logic' => [] ] ],
			]
		);

		// Only the actionable rule is evaluated, and it hides the block for 'Free'.
		$this->assertSame( [], Payment_Helper::get_required_payment_block_ids( $this->form_id, [ self::PLAN_KEY => 'Free' ] ) );
	}

	public function test_has_actionable_conditional_rule_keeps_a_rule_without_conditions_required() {
		update_post_meta(
			$this->form_id,
			'_srfm_conditional_logic',
			[ [ 'pay00001' => [ 'action' => 'show', 'logic' => [ [ [ 'operator' => '==', 'value' => 'Premium' ] ] ] ] ] ]
		);

		$this->assertSame( [ 'pay00001' ], Payment_Helper::get_required_payment_block_ids( $this->form_id, [ self::PLAN_KEY => 'Free' ] ) );
	}

	public function test_get_conditional_field_value_fails_closed_on_ambiguous_keys() {
		$this->set_rule( 'show', '==', 'Premium' );

		$form_data = [
			self::PLAN_KEY                          => 'Free',
			'srfm-input-plan0001-lbl-T3RoZXI-other' => 'Free',
		];

		$this->assertSame( [ 'pay00001' ], Payment_Helper::get_required_payment_block_ids( $this->form_id, $form_data ) );
	}

	/**
	 * Add trigger fields to the form and store one show rule for the payment block.
	 *
	 * @param string       $blocks   Block markup to add before the payment block.
	 * @param string       $field    Block ID the rule reads.
	 * @param string       $operator Operator.
	 * @param string|array $value    Condition value.
	 * @param string       $action   show|hide.
	 * @return void
	 */
	private function rule_on( $blocks, $field, $operator, $value, $action = 'show' ) {
		$post = get_post( $this->form_id );
		if ( false === strpos( $post->post_content, $blocks ) ) {
			wp_update_post(
				[
					'ID'           => $this->form_id,
					'post_content' => $blocks . $post->post_content,
				]
			);
		}

		update_post_meta(
			$this->form_id,
			'_srfm_conditional_logic',
			[ [ 'pay00001' => [ 'action' => $action, 'logic' => [ [ [ 'field' => $field, 'operator' => $operator, 'value' => $value ] ] ] ] ] ]
		);
	}

	/**
	 * Whether the payment block is required for this submission.
	 *
	 * @param array<string,mixed> $form_data Submitted fields.
	 * @return bool
	 */
	private function payment_required( $form_data ) {
		return [ 'pay00001' ] === Payment_Helper::get_required_payment_block_ids( $this->form_id, $form_data );
	}

	public function test_get_conditional_field_value_reads_a_field_missing_from_the_form_as_empty() {
		$this->rule_on( '', 'gone0001', '==', 'yes', 'hide' );

		$this->assertTrue( $this->payment_required( [ 'srfm-input-gone0001-lbl-eA-plan' => 'yes' ] ), 'the browser finds no such field, so it reads as empty' );
	}

	public function test_get_conditional_field_value_reads_numbers_in_the_field_format() {
		$eu = '<!-- wp:srfm/number {"block_id":"qty00001","formatType":"eu-style"} /-->';
		$us = '<!-- wp:srfm/number {"block_id":"qty00002"} /-->';

		$this->rule_on( $eu . $us, 'qty00001', '>=', '100' );
		$this->assertTrue( $this->payment_required( [ 'srfm-number-qty00001-lbl-UXR5-qty' => '1.000' ] ), 'EU 1.000 is one thousand' );
		$this->assertTrue( $this->payment_required( [ 'srfm-number-qty00001-lbl-UXR5-qty' => '1.000,5' ] ), 'EU 1.000,5' );
		$this->assertTrue( $this->payment_required( [ 'srfm-input-qty00001-lbl-UXR5-qty' => '1.000' ] ), 'the stored block, not the key type, sets the format' );
		$this->assertFalse( $this->payment_required( [ 'srfm-number-qty00001-lbl-UXR5-qty' => '99' ] ), 'EU 99' );
		$this->assertFalse( $this->payment_required( [ 'srfm-number-qty00001-lbl-UXR5-qty' => '0,5' ] ), 'EU 0,5 is a half' );

		$this->rule_on( $eu . $us, 'qty00002', '>=', '100' );
		$this->assertTrue( $this->payment_required( [ 'srfm-number-qty00002-lbl-UXR5-qty' => '1,000' ] ), 'US 1,000' );
		$this->assertFalse( $this->payment_required( [ 'srfm-number-qty00002-lbl-UXR5-qty' => '99.5' ] ), 'US 99.5' );
	}

	public function test_get_conditional_field_value_reads_phone_checkbox_and_rich_text_as_the_browser_does() {
		$fields = '<!-- wp:srfm/phone {"block_id":"tel00001"} /-->'
			. '<!-- wp:srfm/checkbox {"block_id":"chk00001"} /-->'
			. '<!-- wp:srfm/textarea {"block_id":"txt00001","isRichText":true} /-->'
			. '<!-- wp:srfm/textarea {"block_id":"txt00002"} /-->';
		$phone  = 'srfm-phone-tel00001-lbl-UGhvbmU-phone';

		// The browser reads the national number without spaces or hyphens.
		$this->rule_on( $fields, 'tel00001', '==', '5551234567', 'hide' );
		$this->assertFalse( $this->payment_required( [ $phone => '555 123-4567' ] ), 'spaces and hyphens are dropped' );
		// The dial code is not part of the browser's reading; where it changes the answer, payment stays required.
		$this->assertTrue( $this->payment_required( [ $phone => '+15551234567' ] ), 'dial code length is ambiguous for ==' );
		$this->rule_on( $fields, 'tel00001', '!null', '', 'hide' );
		$this->assertFalse( $this->payment_required( [ $phone => '+15551234567' ] ), 'every reading of the number is non-empty' );

		// A checkbox reads as "true" or "false".
		$this->rule_on( $fields, 'chk00001', 'isChecked', '' );
		$this->assertTrue( $this->payment_required( [ 'srfm-checkbox-chk00001-lbl-Q2hr-agree' => 'on' ] ), 'ticked' );
		$this->assertFalse( $this->payment_required( [] ), 'an unticked checkbox is not posted' );
		$this->rule_on( $fields, 'chk00001', '!isChecked', '', 'hide' );
		$this->assertFalse( $this->payment_required( [] ), 'unticked reads as false' );

		// A rich-text textarea reads as its text, a plain one as its value.
		$this->rule_on( $fields, 'txt00001', '==', 'Hello & bye' );
		$this->assertTrue( $this->payment_required( [ 'srfm-textarea-txt00001-lbl-VHh0-msg' => '<p>Hello &amp; bye</p>' ] ), 'rich text' );
		$this->rule_on( $fields, 'txt00002', '==', 'Hello' );
		$this->assertFalse( $this->payment_required( [ 'srfm-textarea-txt00002-lbl-VHh0-msg' => '<p>Hello</p>' ] ), 'plain textarea' );
	}

	public function test_evaluate_conditional_condition_evaluates_date_and_time_operators() {
		$fields = '<!-- wp:srfm/date-picker {"block_id":"day00001","dateFormat":"dd/mm/yyyy"} /-->'
			. '<!-- wp:srfm/date-picker {"block_id":"day00002","dateFormat":"yyyy/mm/dd"} /-->'
			. '<!-- wp:srfm/time-picker {"block_id":"tim00001"} /-->';
		$day    = 'srfm-date-picker-day00001-lbl-RGF5-day';
		$time   = 'srfm-time-picker-tim00001-lbl-VGltZQ-time';

		$this->rule_on( $fields, 'day00001', 'isAfter', '2025-06-01' );
		$this->assertTrue( $this->payment_required( [ $day => '15/06/2025' ] ), 'after, in the field format' );
		$this->assertFalse( $this->payment_required( [ $day => '15/05/2025' ] ), 'before' );
		$this->assertFalse( $this->payment_required( [] ), 'an empty date is never after' );

		$this->rule_on( $fields, 'day00001', 'datePickerIs', '2025-06-15' );
		$this->assertTrue( $this->payment_required( [ $day => '15/06/2025' ] ), 'same day' );
		$this->assertFalse( $this->payment_required( [ $day => '16/06/2025' ] ), 'another day' );

		$this->rule_on( $fields, 'day00002', 'isOnOrBefore', '2025-06-15' );
		$this->assertTrue( $this->payment_required( [ 'srfm-date-picker-day00002-lbl-RGF5-day' => '2025/06/15' ] ), 'yyyy/mm/dd' );

		$this->rule_on( $fields, 'tim00001', 'timePickerIs', '14:30' );
		$this->assertTrue( $this->payment_required( [ $time => '02:30 PM' ] ), '12-hour reading of 14:30' );
		$this->assertFalse( $this->payment_required( [ $time => '02:30 AM' ] ), 'AM' );

		$this->rule_on( $fields, 'tim00001', 'isBefore', '09:00' );
		$this->assertTrue( $this->payment_required( [ $time => '08:15 AM' ] ), 'before 9' );
		$this->assertTrue( $this->payment_required( [ $time => '12:15 AM' ] ), '12 AM is midnight' );
		$this->assertFalse( $this->payment_required( [ $time => '10:00' ] ), 'after 9' );

		// A time compared with a date is not modelled server-side, so payment stays required.
		$this->rule_on( $fields, 'tim00001', 'isAfter', '2025-06-01', 'hide' );
		$this->assertTrue( $this->payment_required( [ $time => '10:00' ] ), 'time against a date' );
	}
}
