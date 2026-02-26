<?php
/**
 * Class Test_Field_Validation
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Inc\Field_Validation;

/**
 * Tests for the Field_Validation class.
 */
class Test_Field_Validation extends TestCase {

	/**
	 * Test add_block_config skips non-array blocks.
	 */
	public function test_add_block_config_skips_non_array_blocks() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		Field_Validation::add_block_config( [ 'not-an-array', 42, null ], $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertEmpty( $config );
	}

	/**
	 * Test add_block_config skips blocks without blockName.
	 */
	public function test_add_block_config_skips_blocks_without_blockname() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$blocks  = [
			[
				'attrs' => [ 'block_id' => 'abc123' ],
			],
		];
		Field_Validation::add_block_config( $blocks, $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertEmpty( $config );
	}

	/**
	 * Test add_block_config skips blocks without attrs.
	 */
	public function test_add_block_config_skips_blocks_without_attrs() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$blocks  = [
			[
				'blockName' => 'srfm/text',
			],
		];
		Field_Validation::add_block_config( $blocks, $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertEmpty( $config );
	}

	/**
	 * Test add_block_config skips blocks with empty block_id.
	 */
	public function test_add_block_config_skips_empty_block_id() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$blocks  = [
			[
				'blockName' => 'srfm/text',
				'attrs'     => [ 'block_id' => '' ],
			],
		];
		Field_Validation::add_block_config( $blocks, $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertEmpty( $config );
	}

	/**
	 * Test add_block_config processes dropdown block.
	 */
	public function test_add_block_config_processes_dropdown_block() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$blocks  = [
			[
				'blockName' => 'srfm/dropdown',
				'attrs'     => [
					'block_id'  => 'dd123',
					'required'  => true,
					'options'   => [
						[ 'label' => 'Option A', 'icon' => '', 'value' => 'a' ],
						[ 'label' => 'Option B', 'icon' => '', 'value' => 'b' ],
					],
					'showValues' => true,
				],
			],
		];
		Field_Validation::add_block_config( $blocks, $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'dd123', $config );
		$this->assertTrue( $config['dd123']['required'] );
		$this->assertTrue( $config['dd123']['show_values'] );
		$this->assertCount( 2, $config['dd123']['options'] );
		$this->assertEquals( 'srfm/dropdown', $config['dd123']['block_name'] );
	}

	/**
	 * Test add_block_config processes multi-choice block.
	 */
	public function test_add_block_config_processes_multichoice_block() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$blocks  = [
			[
				'blockName' => 'srfm/multi-choice',
				'attrs'     => [
					'block_id'        => 'mc456',
					'required'        => true,
					'singleSelection' => false,
					'minValue'        => 1,
					'maxValue'        => 3,
					'options'         => [
						[ 'optionTitle' => 'Choice 1', 'icon' => '', 'value' => 'c1' ],
						[ 'optionTitle' => 'Choice 2', 'icon' => '', 'value' => 'c2' ],
					],
				],
			],
		];
		Field_Validation::add_block_config( $blocks, $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'mc456', $config );
		$this->assertTrue( $config['mc456']['required'] );
		$this->assertFalse( $config['mc456']['single_selection'] );
		$this->assertEquals( 1, $config['mc456']['min_value'] );
		$this->assertEquals( 3, $config['mc456']['max_value'] );
		$this->assertCount( 2, $config['mc456']['options'] );
	}

	/**
	 * Test add_block_config processes number block.
	 */
	public function test_add_block_config_processes_number_block() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$blocks  = [
			[
				'blockName' => 'srfm/number',
				'attrs'     => [
					'block_id'   => 'num789',
					'required'   => true,
					'formatType' => 'eu-style',
					'min'        => 5,
					'max'        => 100,
				],
			],
		];
		Field_Validation::add_block_config( $blocks, $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'num789', $config );
		$this->assertTrue( $config['num789']['required'] );
		$this->assertEquals( 'eu-style', $config['num789']['format_type'] );
		$this->assertEquals( 5.0, $config['num789']['min'] );
		$this->assertEquals( 100.0, $config['num789']['max'] );
	}

	/**
	 * Test add_block_config processes payment block.
	 */
	public function test_add_block_config_processes_payment_block() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$blocks  = [
			[
				'blockName' => 'srfm/payment',
				'attrs'     => [
					'block_id'      => 'pay001',
					'paymentType'   => 'one-time',
					'amountType'    => 'fixed',
					'fixedAmount'   => 25.50,
					'minimumAmount' => 5,
				],
			],
		];
		Field_Validation::add_block_config( $blocks, $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'pay001', $config );
		$this->assertEquals( 'one-time', $config['pay001']['payment_type'] );
		$this->assertEquals( 'fixed', $config['pay001']['amount_type'] );
		$this->assertEquals( 25.50, $config['pay001']['fixed_amount'] );
		$this->assertEquals( 5.0, $config['pay001']['minimum_amount'] );
	}

	/**
	 * Test add_block_config stores slug when present.
	 */
	public function test_add_block_config_stores_slug() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$blocks  = [
			[
				'blockName' => 'srfm/number',
				'attrs'     => [
					'block_id' => 'slug-test',
					'slug'     => 'my-number-field',
					'min'      => 0,
					'max'      => 10,
				],
			],
		];
		Field_Validation::add_block_config( $blocks, $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertEquals( 'my-number-field', $config['slug-test']['slug'] );
	}

	/**
	 * Test get_or_migrate_block_config_for_legacy_form with invalid form_id.
	 */
	public function test_get_or_migrate_block_config_invalid_form_id() {
		$this->assertNull( Field_Validation::get_or_migrate_block_config_for_legacy_form( 0 ) );
		$this->assertNull( Field_Validation::get_or_migrate_block_config_for_legacy_form( -1 ) );
	}

	/**
	 * Test get_or_migrate_block_config_for_legacy_form with non-integer.
	 */
	public function test_get_or_migrate_block_config_non_integer() {
		$this->assertNull( Field_Validation::get_or_migrate_block_config_for_legacy_form( 'abc' ) );
	}

	/**
	 * Test get_or_migrate_block_config returns existing config.
	 */
	public function test_get_or_migrate_block_config_returns_existing() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$stored  = [ 'block1' => [ 'blockName' => 'srfm/text' ] ];
		update_post_meta( $form_id, '_srfm_block_config', $stored );
		$result = Field_Validation::get_or_migrate_block_config_for_legacy_form( $form_id );
		$this->assertEquals( $stored, $result );
	}

	/**
	 * Test get_or_migrate_block_config returns null for post with no content.
	 */
	public function test_get_or_migrate_block_config_no_content() {
		$form_id = wp_insert_post( [
			'post_type'    => 'sureforms_form',
			'post_status'  => 'publish',
			'post_title'   => 'Test Form',
			'post_content' => '',
		] );
		$result = Field_Validation::get_or_migrate_block_config_for_legacy_form( $form_id );
		$this->assertNull( $result );
	}

	/**
	 * Test prepared_validation_data returns empty array for invalid form.
	 */
	public function test_prepared_validation_data_invalid_form() {
		$result = Field_Validation::prepared_validation_data( 0 );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test prepared_validation_data adds name_with_id to blocks.
	 */
	public function test_prepared_validation_data_adds_name_with_id() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$config  = [
			'block-abc' => [
				'blockName' => 'srfm/dropdown',
				'required'  => true,
			],
		];
		update_post_meta( $form_id, '_srfm_block_config', $config );
		$result = Field_Validation::prepared_validation_data( $form_id );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'block-abc', $result );
		$this->assertArrayHasKey( 'name_with_id', $result['block-abc'] );
		$this->assertEquals( 'srfm-dropdown-block-abc', $result['block-abc']['name_with_id'] );
	}

	/**
	 * Test validate_form_data with non-array form data.
	 */
	public function test_validate_form_data_non_array() {
		$result = Field_Validation::validate_form_data( 'not-array', 1 );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test validate_form_data with non-numeric form id.
	 */
	public function test_validate_form_data_non_numeric_form_id() {
		$result = Field_Validation::validate_form_data( [ 'key' => 'val' ], 'abc' );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test validate_form_data skips non-sureforms keys.
	 */
	public function test_validate_form_data_skips_non_sureforms_keys() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$form_data = [
			'form-id' => $form_id,
			'nonce'   => 'test',
			'regular' => 'value',
		];
		$result = Field_Validation::validate_form_data( $form_data, $form_id );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test process_number_block defaults format_type to us-style.
	 */
	public function test_process_number_block_default_format_type() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$blocks  = [
			[
				'blockName' => 'srfm/number',
				'attrs'     => [
					'block_id' => 'numdef',
				],
			],
		];
		Field_Validation::add_block_config( $blocks, $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'numdef', $config );
		$this->assertEquals( 'us-style', $config['numdef']['format_type'] );
	}

	/**
	 * Test process_dropdown_block with multiSelect and min/max values.
	 */
	public function test_process_dropdown_block_multiselect_min_max() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$blocks  = [
			[
				'blockName' => 'srfm/dropdown',
				'attrs'     => [
					'block_id'    => 'ddmulti',
					'required'    => false,
					'multiSelect' => true,
					'minValue'    => 2,
					'maxValue'    => 5,
					'options'     => [],
				],
			],
		];
		Field_Validation::add_block_config( $blocks, $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertIsArray( $config );
		$this->assertTrue( $config['ddmulti']['multi_select'] );
		$this->assertEquals( 2, $config['ddmulti']['min_value'] );
		$this->assertEquals( 5, $config['ddmulti']['max_value'] );
		$this->assertFalse( $config['ddmulti']['required'] );
	}

	/**
	 * Test process_payment_block with variable amount field.
	 */
	public function test_process_payment_block_variable_amount_field() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$blocks  = [
			[
				'blockName' => 'srfm/number',
				'attrs'     => [
					'block_id' => 'amount-field',
					'slug'     => 'custom-amount',
				],
			],
			[
				'blockName' => 'srfm/payment',
				'attrs'     => [
					'block_id'            => 'pay-var',
					'amountType'          => 'variable',
					'variableAmountField' => 'custom-amount',
				],
			],
		];
		Field_Validation::add_block_config( $blocks, $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'pay-var', $config );
		$this->assertEquals( 'custom-amount', $config['pay-var']['variable_amount_field'] );
		$this->assertEquals( 'srfm/number', $config['pay-var']['variable_amount_field_block_name'] );
	}

	/**
	 * Test add_block_config with multiple blocks.
	 */
	public function test_add_block_config_multiple_blocks() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$blocks  = [
			[
				'blockName' => 'srfm/dropdown',
				'attrs'     => [
					'block_id' => 'dd1',
					'required' => true,
					'options'  => [ [ 'label' => 'A', 'icon' => '', 'value' => 'a' ] ],
				],
			],
			[
				'blockName' => 'srfm/number',
				'attrs'     => [
					'block_id' => 'num1',
					'min'      => 0,
					'max'      => 50,
				],
			],
			[
				'blockName' => 'srfm/text',
				'attrs'     => [
					'block_id' => 'txt1',
				],
			],
		];
		Field_Validation::add_block_config( $blocks, $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertIsArray( $config );
		// Only dropdown and number get processed by the switch - text does not.
		$this->assertArrayHasKey( 'dd1', $config );
		$this->assertArrayHasKey( 'num1', $config );
	}

	/**
	 * Test process_payment_block defaults.
	 */
	public function test_process_payment_block_defaults() {
		$form_id = wp_insert_post( [ 'post_type' => 'sureforms_form', 'post_status' => 'publish', 'post_title' => 'Test Form' ] );
		$blocks  = [
			[
				'blockName' => 'srfm/payment',
				'attrs'     => [
					'block_id' => 'pay-default',
				],
			],
		];
		Field_Validation::add_block_config( $blocks, $form_id );
		$config = get_post_meta( $form_id, '_srfm_block_config', true );
		$this->assertIsArray( $config );
		$this->assertEquals( 'one-time', $config['pay-default']['payment_type'] );
		$this->assertEquals( 'fixed', $config['pay-default']['amount_type'] );
		$this->assertEquals( 10.0, $config['pay-default']['fixed_amount'] );
		$this->assertEquals( 0.0, $config['pay-default']['minimum_amount'] );
	}
}
