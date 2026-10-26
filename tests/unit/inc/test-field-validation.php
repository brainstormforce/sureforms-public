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

	/**
	 * Test get_email_char_limits returns RFC 5321 defaults and honors the filter override.
	 *
	 * @since 2.12.1
	 */
	public function test_get_email_char_limits() {
		// Defaults when no filter is attached.
		$this->assertSame(
			[
				'local'  => 64,
				'domain' => 255,
			],
			Field_Validation::get_email_char_limits(),
			'Should return the RFC 5321 defaults (64 local / 255 domain).'
		);

		// A filter override is reflected and run through absint.
		$override = static function () {
			return [
				'local'  => 128,
				'domain' => 320,
			];
		};
		add_filter( 'srfm_email_field_char_limits', $override );
		$limits = Field_Validation::get_email_char_limits();
		remove_filter( 'srfm_email_field_char_limits', $override );

		$this->assertSame(
			[
				'local'  => 128,
				'domain' => 320,
			],
			$limits,
			'A srfm_email_field_char_limits override should be reflected in the resolved limits.'
		);
	}

	/**
	 * get_known_field_block_ids() returns the block ids present in the form's markup,
	 * including fields nested inside a repeater/container (innerBlocks).
	 */
	public function test_get_known_field_block_ids_includes_nested_fields() {
		$content  = '<!-- wp:srfm/input {"block_id":"aaaa1111"} /-->';
		$content .= '<!-- wp:srfm/repeater {"block_id":"bbbb2222"} -->';
		$content .= '<!-- wp:srfm/input {"block_id":"cccc3333"} /-->';
		$content .= '<!-- /wp:srfm/repeater -->';

		$form_id = wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'Known Fields Form',
				'post_content' => $content,
			]
		);

		$ids = Field_Validation::get_known_field_block_ids( $form_id );

		$this->assertArrayHasKey( 'aaaa1111', $ids, 'top-level field id present' );
		$this->assertArrayHasKey( 'bbbb2222', $ids, 'repeater id present' );
		$this->assertArrayHasKey( 'cccc3333', $ids, 'nested field id present (innerBlocks walked)' );
		$this->assertArrayNotHasKey( 'deadbeef', $ids, 'an id the form does not define is absent' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * REGRESSION: a submitted key whose block id is not all-alphanumeric must still
	 * resolve to that block id, so a legitimate field is not dropped as unknown.
	 *
	 * The guard extracts the id with Helper::get_block_id_from_key() — the same helper
	 * every downstream consumer uses. A local regex would return nothing here and the
	 * field would be dropped.
	 */
	public function test_strip_unknown_field_keys_keeps_non_alphanumeric_block_id() {
		$form_id = wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'Odd Block Id Form',
				'post_content' => '<!-- wp:srfm/input {"block_id":"ab_12cd"} /-->',
			]
		);

		$key  = 'srfm-input-ab_12cd-lbl-UmVhbA-real';
		$kept = Field_Validation::strip_unknown_field_keys( [ $key => 'ok' ], $form_id );

		$this->assertArrayHasKey( $key, $kept, 'A field whose block id contains an underscore must be kept.' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Both allowlists come from one walk, not one each.
	 *
	 * walk_field_identifiers() returns ids and slugs together precisely so the form is
	 * parsed once, but each public getter used to hold its own `static $cache`, so
	 * parse_blocks() plus the recursive collect ran twice on every submission.
	 *
	 * Observed rather than counted: the content is removed between the two calls, so a
	 * second walk has nothing to find. With one shared memo the slugs are already
	 * cached and survive; with a cache per getter the second call re-walks an empty
	 * form and returns nothing.
	 */
	public function test_both_allowlists_come_from_a_single_walk() {
		$form_id = wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'One Walk',
				'post_content' => '<!-- wp:srfm/input {"block_id":"aaa111","slug":"finish-time"} /-->',
			]
		);

		$ids = Field_Validation::get_known_field_block_ids( $form_id );
		$this->assertArrayHasKey( 'aaa111', $ids, 'Fixture must produce a block id to begin with.' );

		// Anything a second walk would read is now gone.
		wp_update_post(
			[
				'ID'           => $form_id,
				'post_content' => '',
			]
		);

		$this->assertArrayHasKey(
			'finish-time',
			Field_Validation::get_known_field_slugs( $form_id ),
			'The slugs must come from the walk the block-id call already did, not from a second one.'
		);

		wp_delete_post( $form_id, true );
	}

	/**
	 * get_known_field_slugs() collects the slug of every SureForms field in the form,
	 * recursing into innerBlocks, so a submitted field can be matched by its stable
	 * slug when its block_id has drifted.
	 */
	public function test_get_known_field_slugs_collects_field_slugs() {
		$form_id = wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'Slug Set Form',
				'post_content' => '<!-- wp:srfm/input {"block_id":"aaa111","slug":"finish-time"} /-->'
					. '<!-- wp:srfm/url {"block_id":"bbb222","slug":"result-url"} /-->',
			]
		);

		$slugs = Field_Validation::get_known_field_slugs( $form_id );

		$this->assertArrayHasKey( 'finish-time', $slugs );
		$this->assertArrayHasKey( 'result-url', $slugs );
		$this->assertArrayNotHasKey( 'not-a-field', $slugs );
		$this->assertSame( [], Field_Validation::get_known_field_slugs( 0 ), 'invalid form id yields an empty set' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Invalid form ids return an empty set (callers fail open).
	 */
	public function test_get_known_field_block_ids_empty_for_invalid_form() {
		$this->assertSame( [], Field_Validation::get_known_field_block_ids( 0 ) );
		$this->assertSame( [], Field_Validation::get_known_field_block_ids( -5 ) );
		$this->assertSame( [], Field_Validation::get_known_field_block_ids( 'x' ) );
	}

	/**
	 * REGRESSION (#2993): a submitted key whose block id is not part of the form is
	 * dropped — an anonymous submitter can otherwise invent fields on any published
	 * form and have them stored, mailed and exported. Known fields survive untouched.
	 *
	 * Dropped rather than rejected on purpose: the allowlist is derived at submit time
	 * while the visitor's HTML was rendered earlier, so rejecting would make a cached
	 * or re-saved form unsubmittable. See strip_unknown_field_keys()'s docblock.
	 */
	public function test_strip_unknown_field_keys_drops_invented_keys() {
		$form_id = wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'Strip Unknown Form',
				'post_content' => '<!-- wp:srfm/input {"block_id":"realfield"} /-->',
			]
		);

		$stripped = Field_Validation::strip_unknown_field_keys(
			[
				'srfm-input-realfield-lbl-UmVhbA-real'   => 'ok',
				'srfm-input-deadbeef-lbl-R2hvc3Q-ghost'  => 'injected',
				'form-id'                                => $form_id,
			],
			$form_id
		);

		$this->assertArrayNotHasKey( 'srfm-input-deadbeef-lbl-R2hvc3Q-ghost', $stripped, 'invented field is dropped' );
		$this->assertArrayHasKey( 'srfm-input-realfield-lbl-UmVhbA-real', $stripped, 'known field survives' );
		$this->assertArrayHasKey( 'form-id', $stripped, 'non-field keys are left alone' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * REGRESSION (#1517643): a field whose block_id drifted between the rendered HTML
	 * the visitor submitted and the current post_content — the systematic case on a
	 * full-page-cached site, or after an editor rebuild — must survive on its slug.
	 *
	 * Matching on block_id alone silently deleted real submission data (keys absent,
	 * success message shown). The slug is the stable field identifier, so a slug match
	 * keeps the field.
	 */
	public function test_strip_unknown_field_keys_keeps_a_drifted_block_id_via_slug() {
		$form_id = wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'Drift Form',
				'post_content' => '<!-- wp:srfm/input {"block_id":"currentid","slug":"finish-time"} /-->',
			]
		);

		// Different block_id (drift), same stable slug 'finish-time'.
		$drifted = 'srfm-input-cachedid-lbl-RmluaXNo-finish-time';

		$kept = Field_Validation::strip_unknown_field_keys( [ $drifted => 'value', 'form-id' => $form_id ], $form_id );

		$this->assertArrayHasKey( $drifted, $kept, 'a field whose block_id drifted must survive on its slug' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * The slug fallback must not weaken the anti-injection guard: a key with neither a
	 * known block_id nor a known slug is still foreign and dropped.
	 */
	public function test_strip_unknown_field_keys_still_drops_foreign_block_id_and_slug() {
		$form_id = wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'Drift Form 2',
				'post_content' => '<!-- wp:srfm/input {"block_id":"currentid","slug":"finish-time"} /-->',
			]
		);

		$foreign  = 'srfm-input-deadbeef-lbl-R2hvc3Q-ghost';
		$stripped = Field_Validation::strip_unknown_field_keys( [ $foreign => 'x', 'form-id' => $form_id ], $form_id );

		$this->assertArrayNotHasKey( $foreign, $stripped, 'a key with neither a known block_id nor a known slug is dropped' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * REGRESSION (#2993, review H1): repeater child keys must be validated too.
	 *
	 * Pro submits repeater rows as `repeaterKey[index][childKey]`, which PHP collapses
	 * into one top-level key holding nested arrays, and process_repeater_field() copies
	 * those child keys verbatim before they are label-decoded downstream. A top-level
	 * only walk therefore left the identical hole one level down: copy the real repeater
	 * block_id out of page source, then nest forged child keys inside it.
	 */
	public function test_strip_unknown_field_keys_drops_unknown_repeater_child_keys() {
		$content  = '<!-- wp:srfm/repeater {"block_id":"bbbb2222"} -->';
		$content .= '<!-- wp:srfm/input {"block_id":"cccc3333"} /-->';
		$content .= '<!-- /wp:srfm/repeater -->';

		$form_id = wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'Repeater Strip Form',
				'post_content' => $content,
			]
		);

		$repeater_key = 'srfm-repeater-bbbb2222-lbl-Um93cw-rows';
		$child_key    = 'srfm-input-cccc3333-lbl-Q2hpbGQ-child';
		$forged_key   = 'srfm-input-deadbeef-lbl-R2hvc3Q-ghost';

		$stripped = Field_Validation::strip_unknown_field_keys(
			[
				$repeater_key => [
					[
						$child_key  => 'legit',
						$forged_key => 'injected',
					],
				],
			],
			$form_id
		);

		$this->assertArrayHasKey( $repeater_key, $stripped, 'the repeater itself is known and kept' );
		$this->assertArrayHasKey( $child_key, $stripped[ $repeater_key ][0], 'legitimate child key survives' );
		$this->assertArrayNotHasKey( $forged_key, $stripped[ $repeater_key ][0], 'forged nested child key is dropped' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * The most load-bearing back-compat behaviour in this change: when the form's block
	 * set cannot be derived, nothing is stripped. Enforcing on an empty allowlist would
	 * silently delete every submitted field.
	 */
	public function test_strip_unknown_field_keys_fails_open_when_blocks_underivable() {
		$form_id = wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'Empty Form',
				'post_content' => '',
			]
		);

		$key      = 'srfm-input-deadbeef-lbl-R2hvc3Q-ghost';
		$stripped = Field_Validation::strip_unknown_field_keys( [ $key => 'x' ], $form_id );

		$this->assertArrayHasKey( $key, $stripped, 'An empty block set must not strip anything.' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * The srfm_known_field_block_ids filter must not be a footgun: a callback returning
	 * a plain list instead of a map would otherwise make every real field look unknown,
	 * and a non-array return would drop the allowlist entirely.
	 */
	public function test_known_field_block_ids_filter_coerces_defensively() {
		$form_id = wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'Filter Coercion Form',
				'post_content' => '<!-- wp:srfm/input {"block_id":"realfield"} /-->',
			]
		);

		// A list return is converted to a map rather than taken literally.
		$as_list = static function () {
			return [ 'realfield', 'extrafield' ];
		};
		add_filter( 'srfm_known_field_block_ids', $as_list );
		$ids = Field_Validation::get_known_field_block_ids( $form_id );
		remove_filter( 'srfm_known_field_block_ids', $as_list );

		$this->assertArrayHasKey( 'realfield', $ids, 'list entries become map keys' );
		$this->assertArrayHasKey( 'extrafield', $ids, 'filter-added id is honoured' );

		// A non-array return falls back to the walked set instead of disabling the check.
		$as_garbage = static function () {
			return 'nonsense';
		};
		add_filter( 'srfm_known_field_block_ids', $as_garbage );
		$ids2 = Field_Validation::get_known_field_block_ids( $form_id );
		remove_filter( 'srfm_known_field_block_ids', $as_garbage );

		$this->assertSame( [ 'realfield' => true ], $ids2, 'a bad return falls back to the walked set' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Build a form whose content is one checkbox block.
	 *
	 * @param string $label    Checkbox label, or '' to leave it unset.
	 * @param string $block_id Block id.
	 * @param string $slug     Field slug.
	 * @return int Form post id.
	 */
	private function make_checkbox_form( $label = 'Seasonal offers', $block_id = 'cb123456', $slug = 'seasonal-offers' ) {
		$attrs = [
			'block_id' => $block_id,
			'slug'     => $slug,
		];
		if ( '' !== $label ) {
			$attrs['label'] = $label;
		}

		return wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'Checkbox Form',
				'post_content' => '<!-- wp:srfm/checkbox ' . wp_json_encode( $attrs ) . ' /-->',
			]
		);
	}

	/**
	 * An unticked checkbox is restored as an empty value instead of vanishing.
	 *
	 * A browser posts nothing for it, so without this the field has no key at all and
	 * is missing from the entry and from notification emails, while every other empty
	 * field is stored as '' and shown as '-'.
	 */
	public function test_backfill_unsubmitted_fields_adds_an_unticked_checkbox() {
		$form_id = $this->make_checkbox_form();
		$key     = 'srfm-checkbox-cb123456-lbl-' . \SRFM\Inc\Helper::encode( 'Seasonal offers' ) . '-seasonal-offers';

		$result = Field_Validation::backfill_unsubmitted_fields( [ 'form-id' => (string) $form_id ], $form_id );

		$this->assertArrayHasKey( $key, $result );
		$this->assertSame( '', $result[ $key ] );

		wp_delete_post( $form_id, true );
	}

	/**
	 * The rebuilt key is the one the field actually renders.
	 *
	 * backfill_unsubmitted_fields() assembles the key the same way Base::set_input_label()
	 * and checkbox-markup.php do. If either side changes, the back-filled key stops
	 * matching the submitted one and a ticked box would be stored twice -- once under
	 * each spelling. Rendering the real field and comparing is what keeps them in step.
	 */
	public function test_backfill_key_matches_the_rendered_field_name() {
		$attrs = [
			'block_id' => 'cb123456',
			'slug'     => 'seasonal-offers',
			'label'    => 'Seasonal offers',
		];

		$markup = ( new \SRFM\Inc\Fields\Checkbox_Markup( $attrs ) )->markup();
		$this->assertIsString( $markup );
		$this->assertSame( 1, preg_match( '/name="(srfm-checkbox-[^"]+)"/', $markup, $matches ), 'the checkbox renders a name' );

		$form_id = $this->make_checkbox_form();
		$result  = Field_Validation::backfill_unsubmitted_fields( [], $form_id );

		$this->assertArrayHasKey( $matches[1], $result, 'the back-filled key must be the key the browser would post' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * The same holds when the form leaves the label empty and the markup falls back.
	 */
	public function test_backfill_key_matches_the_rendered_name_without_a_label() {
		$attrs = [
			'block_id' => 'cb999999',
			'slug'     => 'plain-checkbox',
		];

		$markup = ( new \SRFM\Inc\Fields\Checkbox_Markup( $attrs ) )->markup();
		$this->assertSame( 1, preg_match( '/name="(srfm-checkbox-[^"]+)"/', $markup, $matches ) );

		$form_id = $this->make_checkbox_form( '', 'cb999999', 'plain-checkbox' );
		$result  = Field_Validation::backfill_unsubmitted_fields( [], $form_id );

		$this->assertArrayHasKey( $matches[1], $result );

		wp_delete_post( $form_id, true );
	}

	/**
	 * A ticked checkbox keeps the value the browser sent.
	 */
	public function test_backfill_unsubmitted_fields_never_replaces_a_submitted_value() {
		$form_id = $this->make_checkbox_form();
		$key     = 'srfm-checkbox-cb123456-lbl-' . \SRFM\Inc\Helper::encode( 'Seasonal offers' ) . '-seasonal-offers';

		$result = Field_Validation::backfill_unsubmitted_fields( [ $key => 'on' ], $form_id );

		$this->assertSame( 'on', $result[ $key ] );

		wp_delete_post( $form_id, true );
	}

	/**
	 * A GDPR field left at its default text is stored once, not twice.
	 *
	 * The block comment carries no `label` when the text was never edited, while the
	 * rendered field encodes the block.json default in its name. Rendering the real
	 * block (so the default is applied) and submitting that key must not gain a second
	 * GDPR key under a different label.
	 */
	public function test_backfill_does_not_duplicate_a_gdpr_field_at_its_default_label() {
		$content = '<!-- wp:srfm/gdpr {"block_id":"gd123456","slug":"gdpr"} /-->';
		$form_id = wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'GDPR Form',
				'post_content' => $content,
			]
		);

		$markup = render_block( parse_blocks( $content )[0] );
		$this->assertSame( 1, preg_match( '/name="(srfm-gdpr-[^"]+)"/', $markup, $matches ), 'the GDPR field renders a name' );

		$result = Field_Validation::backfill_unsubmitted_fields( [ $matches[1] => 'on' ], $form_id );

		$this->assertSame( [ $matches[1] => 'on' ], $result );

		// When it is not sent at all, it is rebuilt under the same rendered key.
		$this->assertSame( [ $matches[1] => '' ], Field_Validation::backfill_unsubmitted_fields( [], $form_id ) );

		wp_delete_post( $form_id, true );
	}

	/**
	 * An unticked checkbox left at its default label is rebuilt under the key it renders.
	 */
	public function test_backfill_key_matches_the_rendered_name_at_the_block_default() {
		$content = '<!-- wp:srfm/checkbox {"block_id":"cb777777","slug":"checkbox"} /-->';
		$form_id = wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'Checkbox Form',
				'post_content' => $content,
			]
		);

		$markup = render_block( parse_blocks( $content )[0] );
		$this->assertSame( 1, preg_match( '/name="(srfm-checkbox-[^"]+)"/', $markup, $matches ) );

		$result = Field_Validation::backfill_unsubmitted_fields( [], $form_id );

		$this->assertSame( [ $matches[1] => '' ], $result );

		wp_delete_post( $form_id, true );
	}

	/**
	 * A ticked checkbox whose label was translated at render time is stored once.
	 *
	 * Multilingual plugins swap the label before the field renders, so the submitted
	 * key encodes the translated text. The field is identified by its block id and
	 * slug, so that key counts as the field being submitted.
	 */
	public function test_backfill_does_not_duplicate_a_checkbox_with_a_translated_label() {
		$form_id = $this->make_checkbox_form();
		$key     = 'srfm-checkbox-cb123456-lbl-' . \SRFM\Inc\Helper::encode( 'Saisonale Angebote' ) . '-seasonal-offers';

		$result = Field_Validation::backfill_unsubmitted_fields( [ $key => 'on' ], $form_id );

		$this->assertSame( [ $key => 'on' ], $result );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Forms without a checkbox are untouched, and no other field type is invented.
	 */
	public function test_backfill_unsubmitted_fields_leaves_other_forms_alone() {
		$form_id = wp_insert_post(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_title'   => 'Input Form',
				'post_content' => '<!-- wp:srfm/input {"block_id":"in123456","slug":"name","label":"Name"} /-->',
			]
		);

		$submitted = [ 'srfm-input-in123456-lbl-TmFtZQ-name' => 'Alan' ];

		$this->assertSame( $submitted, Field_Validation::backfill_unsubmitted_fields( $submitted, $form_id ) );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Nothing is added when the form id cannot be resolved, so a malformed submission
	 * is not decorated with fields it never had.
	 */
	public function test_backfill_unsubmitted_fields_needs_a_form() {
		$this->assertSame( [ 'a' => 'b' ], Field_Validation::backfill_unsubmitted_fields( [ 'a' => 'b' ], 0 ) );
		$this->assertSame( [ 'a' => 'b' ], Field_Validation::backfill_unsubmitted_fields( [ 'a' => 'b' ], 'not-an-id' ) );
	}
}
