<?php
/**
 * Class Test_Field_Mapping_Template_Cases
 *
 * Mapper cases used by form templates: default-case attributes, html,
 * password, hidden, time picker, slider text mode and container blocks
 * (address, repeater) built from `innerFields`.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\AI_Form_Builder\Field_Mapping;

class Test_Field_Mapping_Template_Cases extends TestCase {

	/**
	 * Filters added by a test, removed in tear_down().
	 *
	 * @var array<int, array{0: string, 1: callable, 2: int}>
	 */
	private $filters = [];

	protected function tear_down() {
		foreach ( $this->filters as $filter ) {
			remove_filter( $filter[0], $filter[1], $filter[2] );
		}
		$this->filters = [];
		parent::tear_down();
	}

	/**
	 * Add a filter that tear_down() removes again.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @param int      $args     Accepted args.
	 */
	private function filter( $hook, $callback, $priority = 10, $args = 1 ) {
		add_filter( $hook, $callback, $priority, $args );
		$this->filters[] = [ $hook, $callback, $priority ];
	}

	/**
	 * Simulate the active SureForms Pro tier through the mapper's seams.
	 *
	 * @param bool $pro      Pro (any tier) active.
	 * @param bool $business Business build active.
	 */
	private function tier( $pro, $business = false ) {
		$this->filter( 'srfm_ai_field_mapping_has_pro', $pro ? '__return_true' : '__return_false' );
		$this->filter( 'srfm_ai_field_mapping_has_business', $business ? '__return_true' : '__return_false' );
	}

	/**
	 * Pass `blockAttributes` through, like Form_Templates::build_content() does.
	 */
	private function with_block_attributes() {
		$this->filter(
			'srfm_ai_form_builder_modify_merged_attributes',
			static function ( $attributes, $question ) {
				if ( ! empty( $question['blockAttributes'] ) && is_array( $question['blockAttributes'] ) ) {
					$attributes = array_merge( $attributes, $question['blockAttributes'] );
				}
				return $attributes;
			},
			20,
			2
		);
	}

	/**
	 * Run the mapper over a list of fields.
	 *
	 * @param array<int, mixed> $fields AI-schema fields.
	 * @return string|WP_Error
	 */
	private function map( array $fields ) {
		$request = new WP_REST_Request();
		$request->set_param( 'form_data', [ 'form' => [ 'formFields' => $fields ] ] );
		return Field_Mapping::generate_gutenberg_fields_from_questions( $request );
	}

	/**
	 * Parsed blocks without the whitespace-only freeform blocks.
	 *
	 * @param string $content Block markup.
	 * @return array<int, array<string, mixed>>
	 */
	private function blocks( $content ) {
		$this->assertIsString( $content );
		return array_values(
			array_filter(
				parse_blocks( $content ),
				static function ( $block ) {
					return null !== $block['blockName'];
				}
			)
		);
	}

	/**
	 * Every block_id in a parsed tree.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @return array<int, string>
	 */
	private function block_ids( $blocks ) {
		$ids = [];
		foreach ( $blocks as $block ) {
			if ( null === $block['blockName'] ) {
				continue;
			}
			$ids[] = $block['attrs']['block_id'] ?? '';
			$ids   = array_merge( $ids, $this->block_ids( $block['innerBlocks'] ) );
		}
		return $ids;
	}

	/**
	 * Assert block ids are present, 8 hex characters and unique.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 */
	private function assert_unique_block_ids( $blocks ) {
		$ids = $this->block_ids( $blocks );
		foreach ( $ids as $id ) {
			$this->assertMatchesRegularExpression( '/^[0-9a-f]{8}$/', $id );
		}
		$this->assertSame( count( $ids ), count( array_unique( $ids ) ), 'Block ids must be unique.' );
	}

	public function test_default_case_keeps_block_attributes() {
		$this->with_block_attributes();

		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'           => 'Mystery',
						'fieldType'       => 'not-a-real-type',
						'required'        => false,
						'blockAttributes' => [ 'fieldWidth' => 50 ],
					],
				]
			)
		);

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'srfm/input', $blocks[0]['blockName'] );
		$this->assertSame( 50, $blocks[0]['attrs']['fieldWidth'] );
	}

	public function test_missing_label_required_and_field_type_do_not_warn() {
		// convertWarningsToExceptions is on, so an undefined index fails the test.
		$blocks = $this->blocks( $this->map( [ [ 'helpText' => 'Only help text' ] ] ) );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'srfm/input', $blocks[0]['blockName'] );
		$this->assertSame( '', $blocks[0]['attrs']['label'] );
		$this->assertFalse( $blocks[0]['attrs']['required'] );
	}

	public function test_address_without_inner_fields_gets_default_sub_fields() {
		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'     => 'Shipping address',
						'fieldType' => 'address',
						'required'  => true,
						'slug'      => 'shipping',
					],
				]
			)
		);

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'srfm/address', $blocks[0]['blockName'] );
		$this->assertSame( 'shipping', $blocks[0]['attrs']['slug'] );

		$inner = $blocks[0]['innerBlocks'];
		$this->assertSame(
			[ 'srfm/input', 'srfm/input', 'srfm/input', 'srfm/input', 'srfm/input', 'srfm/dropdown' ],
			array_column( $inner, 'blockName' )
		);
		$this->assertSame(
			[ 'Address Line 1', 'Address Line 2', 'City', 'State', 'Postal Code', 'Country' ],
			array_map(
				static function ( $block ) {
					return $block['attrs']['label'];
				},
				$inner
			)
		);
		$this->assertSame(
			[ 'line1', 'line2', 'city', 'state', 'postal_code', 'country' ],
			array_map(
				static function ( $block ) {
					return $block['attrs']['addressComponent'];
				},
				$inner
			)
		);
		foreach ( $inner as $block ) {
			$this->assertSame( 50, $block['attrs']['fieldWidth'] );
			$this->assertSame( 0, $block['attrs']['formId'] );
		}

		// Line 2 stays optional when the address is required.
		$this->assertTrue( $inner[0]['attrs']['required'] );
		$this->assertFalse( $inner[1]['attrs']['required'] );
		$this->assertTrue( $inner[5]['attrs']['required'] );

		// Country is a dropdown with the full country list.
		$this->assertGreaterThan( 200, count( $inner[5]['attrs']['options'] ) );
		$this->assertSame(
			[
				'label' => 'Afghanistan',
				'icon'  => '',
			],
			$inner[5]['attrs']['options'][0]
		);

		$this->assert_unique_block_ids( $blocks );
	}

	public function test_optional_address_default_sub_fields_are_optional() {
		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'     => 'Address',
						'fieldType' => 'address',
					],
				]
			)
		);

		foreach ( $blocks[0]['innerBlocks'] as $block ) {
			$this->assertFalse( $block['attrs']['required'] );
		}
	}

	public function test_address_with_inner_fields() {
		$this->with_block_attributes();

		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'       => 'Venue',
						'fieldType'   => 'address',
						'required'    => false,
						'innerFields' => [
							[
								'label'           => 'Street',
								'fieldType'       => 'input',
								'required'        => true,
								'slug'            => 'street',
								'blockAttributes' => [
									'fieldWidth'       => 100,
									'addressComponent' => 'line1',
								],
							],
							[
								// Not allowed inside an address: dropped.
								'label'     => 'Email',
								'fieldType' => 'email',
								'required'  => true,
							],
							[
								'label'        => 'Region',
								'fieldType'    => 'dropdown',
								'required'     => false,
								'fieldOptions' => [
									[ 'label' => 'North' ],
									[ 'label' => 'South' ],
								],
							],
						],
					],
					[
						'label'     => 'After',
						'fieldType' => 'input',
					],
				]
			)
		);

		$this->assertSame( [ 'srfm/address', 'srfm/input' ], array_column( $blocks, 'blockName' ) );

		$inner = $blocks[0]['innerBlocks'];
		$this->assertSame( [ 'srfm/input', 'srfm/dropdown' ], array_column( $inner, 'blockName' ) );
		$this->assertSame( 'Street', $inner[0]['attrs']['label'] );
		$this->assertTrue( $inner[0]['attrs']['required'] );
		$this->assertSame( 'street', $inner[0]['attrs']['slug'] );
		$this->assertSame( 100, $inner[0]['attrs']['fieldWidth'] );
		$this->assertSame( 'line1', $inner[0]['attrs']['addressComponent'] );
		$this->assertSame( [ 'North', 'South' ], array_column( $inner[1]['attrs']['options'], 'label' ) );

		$this->assert_unique_block_ids( $blocks );
	}

	public function test_address_falls_back_to_defaults_when_no_inner_field_is_allowed() {
		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'       => 'Address',
						'fieldType'   => 'address',
						'innerFields' => [
							[
								'label'     => 'Notes',
								'fieldType' => 'textarea',
							],
						],
					],
				]
			)
		);

		$this->assertCount( 6, $blocks[0]['innerBlocks'] );
	}

	public function test_malformed_inner_field_is_an_error() {
		$result = $this->map(
			[
				[
					'label'       => 'Address',
					'fieldType'   => 'address',
					'innerFields' => [ 'not-an-array' ],
				],
			]
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_ai_mapping_invalid_field', $result->get_error_code() );
	}

	public function test_address_from_mapper_renders_its_sub_fields() {
		$content = $this->map(
			[
				[
					'label'     => 'Home address',
					'fieldType' => 'address',
					'required'  => true,
				],
			]
		);
		$blocks  = $this->blocks( $content );

		$html = do_blocks( $content );

		$this->assertStringContainsString( 'data-block-id="' . $blocks[0]['attrs']['block_id'] . '"', $html );
		$this->assertStringContainsString( 'Home address', $html );
		foreach ( $blocks[0]['innerBlocks'] as $inner ) {
			$this->assertStringContainsString( 'data-block-id="' . $inner['attrs']['block_id'] . '"', $html );
		}
		$this->assertStringContainsString( 'Address Line 1', $html );
		$this->assertStringContainsString( 'Postal Code', $html );
		$this->assertStringContainsString( 'Afghanistan', $html );

		// The sub-fields render inside the address wrapper, not after it.
		$wrap = strpos( $html, 'srfm-block-wrap' );
		$this->assertNotFalse( $wrap );
		$this->assertGreaterThan( $wrap, strpos( $html, 'Address Line 1' ) );
	}

	public function test_time_picker_twelve_hour_defaults_to_block_default() {
		$this->tier( true );

		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'     => 'Time',
						'fieldType' => 'time-picker',
					],
					[
						'label'                => '24h',
						'fieldType'            => 'time-picker',
						'showTwelveHourFormat' => false,
					],
				]
			)
		);

		$this->assertTrue( $blocks[0]['attrs']['showTwelveHourFormat'] );
		$this->assertFalse( $blocks[1]['attrs']['showTwelveHourFormat'] );
	}

	public function test_hidden_default_value() {
		$this->tier( true );
		$this->with_block_attributes();

		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'        => 'UTM source',
						'fieldType'    => 'hidden',
						'defaultValue' => '{get_input:utm_source}<b>',
					],
					[
						'label'           => 'Campaign',
						'fieldType'       => 'hidden',
						'defaultValue'    => 'question',
						'blockAttributes' => [ 'defaultValue' => 'block' ],
					],
				]
			)
		);

		$this->assertSame( '{get_input:utm_source}', $blocks[0]['attrs']['defaultValue'] );
		$this->assertSame( 'block', $blocks[1]['attrs']['defaultValue'] );
	}

	public function test_slider_text_mode_passes_through_block_attributes() {
		$this->tier( true );
		$this->with_block_attributes();

		$options = [
			[ 'label' => 'Low' ],
			[ 'label' => 'Medium' ],
			[ 'label' => 'High' ],
		];
		$blocks  = $this->blocks(
			$this->map(
				[
					[
						'label'           => 'Priority',
						'fieldType'       => 'slider',
						'blockAttributes' => [
							'type'             => 'text',
							'options'          => $options,
							'textDefaultValue' => 'Medium',
						],
					],
				]
			)
		);

		$this->assertSame( 'srfm/slider', $blocks[0]['blockName'] );
		$this->assertSame( 'text', $blocks[0]['attrs']['type'] );
		$this->assertSame( $options, $blocks[0]['attrs']['options'] );
		$this->assertSame( 'Medium', $blocks[0]['attrs']['textDefaultValue'] );
	}

	public function test_pro_fields_without_pro_are_dropped_or_fall_back_to_input() {
		$this->tier( false );

		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'     => 'Slider',
						'fieldType' => 'slider',
					],
					[
						'label'       => 'Intro',
						'fieldType'   => 'html',
						'htmlContent' => '<p>Hi</p>',
					],
					[
						'label'     => 'Password',
						'fieldType' => 'password',
					],
					[
						'label'     => 'Guests',
						'fieldType' => 'repeater',
					],
					[
						'label'     => 'Name',
						'fieldType' => 'input',
					],
				]
			)
		);

		// Slider has no text equivalent and is dropped, as before. HTML, password
		// and repeater fall back to a text field, as they did before the mapper
		// knew them, so the question isn't lost.
		$this->assertSame( [ 'srfm/input', 'srfm/input', 'srfm/input', 'srfm/input' ], array_column( $blocks, 'blockName' ) );
		$this->assertSame( [ 'Intro', 'Password', 'Guests', 'Name' ], array_column( array_column( $blocks, 'attrs' ), 'label' ) );
		$this->assertSame( [], $blocks[2]['innerBlocks'] );
	}

	public function test_html_content_is_kses_filtered() {
		$this->tier( true );

		$content = $this->map(
			[
				[
					'fieldType'   => 'html',
					'slug'        => 'intro',
					'htmlContent' => '<h3>Welcome</h3><p onclick="alert(1)">Total: {calculation-result}</p><script>alert(1)</script><!-- note -->',
				],
				[
					'label'     => 'Name',
					'fieldType' => 'input',
				],
			]
		);
		$blocks  = $this->blocks( $content );

		$this->assertSame( [ 'srfm/html', 'srfm/input' ], array_column( $blocks, 'blockName' ) );

		$attrs = $blocks[0]['attrs'];
		$this->assertStringContainsString( '<h3>Welcome</h3>', $attrs['htmlContent'] );
		$this->assertStringContainsString( '{calculation-result}', $attrs['htmlContent'] );
		$this->assertStringNotContainsString( '<script', $attrs['htmlContent'] );
		$this->assertStringNotContainsString( 'onclick', $attrs['htmlContent'] );
		$this->assertSame( 'intro', $attrs['slug'] );

		// A display block: no label (block default applies), required flag or help text.
		$this->assertArrayNotHasKey( 'label', $attrs );
		$this->assertArrayNotHasKey( 'required', $attrs );
		$this->assertArrayNotHasKey( 'help', $attrs );

		$this->assert_unique_block_ids( $blocks );
	}

	public function test_password_without_business_falls_back_to_input() {
		$this->tier( true, false );

		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'             => 'Password',
						'fieldType'         => 'password',
						'required'          => true,
						'isConfirmPassword' => true,
					],
				]
			)
		);

		$this->assertSame( [ 'srfm/input' ], array_column( $blocks, 'blockName' ) );
		$this->assertSame( 'Password', $blocks[0]['attrs']['label'] );
		$this->assertTrue( $blocks[0]['attrs']['required'] );
		$this->assertArrayNotHasKey( 'isConfirmPassword', $blocks[0]['attrs'], 'Password-only attributes are not set on the text field.' );
	}

	public function test_password_attributes() {
		$this->tier( true, true );

		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'                    => 'Password',
						'fieldType'                => 'password',
						'required'                 => true,
						'isConfirmPassword'        => true,
						'confirmLabel'             => 'Repeat <b>password</b>',
						'checkPasswordStrength'    => 'true',
						'togglePasswordVisibility' => true,
					],
					[
						'label'     => 'Plain',
						'fieldType' => 'password',
					],
				]
			)
		);

		$this->assertSame( 'srfm/password', $blocks[0]['blockName'] );
		$attrs = $blocks[0]['attrs'];
		$this->assertTrue( $attrs['required'] );
		$this->assertTrue( $attrs['isConfirmPassword'] );
		$this->assertSame( 'Repeat password', $attrs['confirmLabel'] );
		$this->assertTrue( $attrs['checkPasswordStrength'] );
		$this->assertTrue( $attrs['togglePasswordVisibility'] );

		// Defaults match block.json; confirmLabel is left to the block default.
		$plain = $blocks[1]['attrs'];
		$this->assertFalse( $plain['isConfirmPassword'] );
		$this->assertFalse( $plain['checkPasswordStrength'] );
		$this->assertFalse( $plain['togglePasswordVisibility'] );
		$this->assertArrayNotHasKey( 'confirmLabel', $plain );
	}

	public function test_repeater_without_business_falls_back_to_input() {
		$this->tier( true, false );

		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'       => 'Guests',
						'fieldType'   => 'repeater',
						'innerFields' => [
							[
								'label'     => 'Guest name',
								'fieldType' => 'input',
							],
						],
					],
				]
			)
		);

		$this->assertSame( [ 'srfm/input' ], array_column( $blocks, 'blockName' ) );
		$this->assertSame( 'Guests', $blocks[0]['attrs']['label'] );
		$this->assertSame( [], $blocks[0]['innerBlocks'], 'The inner fields need the repeater, so they are not added.' );
	}

	public function test_repeater_with_inner_fields() {
		$this->tier( true, true );
		$this->with_block_attributes();

		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'       => 'Guests',
						'fieldType'   => 'repeater',
						'required'    => true,
						'helpText'    => 'Add each guest.',
						'minItems'    => 2,
						'maxItems'    => 1,
						'innerFields' => [
							[
								'label'           => 'Guest name',
								'fieldType'       => 'input',
								'required'        => true,
								'blockAttributes' => [ 'fieldWidth' => 50 ],
							],
							[
								'label'     => 'Arrival',
								'fieldType' => 'date-picker',
							],
							[
								// Not in the repeater editor whitelist: dropped.
								'label'     => 'Photo',
								'fieldType' => 'upload',
							],
							[
								// Containers cannot nest.
								'label'     => 'Address',
								'fieldType' => 'address',
							],
						],
					],
				]
			)
		);

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'srfm/repeater', $blocks[0]['blockName'] );

		$attrs = $blocks[0]['attrs'];
		$this->assertSame( 'Guests', $attrs['label'] );
		$this->assertSame( 'Add each guest.', $attrs['help'] );
		$this->assertSame( 2, $attrs['minItems'] );
		$this->assertSame( 2, $attrs['maxItems'], 'maxItems is never below minItems.' );
		$this->assertArrayNotHasKey( 'required', $attrs );

		$inner = $blocks[0]['innerBlocks'];
		$this->assertSame( [ 'srfm/input', 'srfm/date-picker' ], array_column( $inner, 'blockName' ) );
		$this->assertTrue( $inner[0]['attrs']['required'] );
		$this->assertSame( 50, $inner[0]['attrs']['fieldWidth'] );

		$this->assert_unique_block_ids( $blocks );
	}

	public function test_repeater_without_inner_fields_gets_the_editor_default_row() {
		$this->tier( true, true );

		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'     => 'Items',
						'fieldType' => 'repeater',
					],
				]
			)
		);

		$this->assertSame( 1, $blocks[0]['attrs']['minItems'] );
		$this->assertArrayNotHasKey( 'maxItems', $blocks[0]['attrs'] );
		$this->assertCount( 1, $blocks[0]['innerBlocks'] );
		$this->assertSame( 'srfm/input', $blocks[0]['innerBlocks'][0]['blockName'] );
		$this->assertSame( 'Text Field', $blocks[0]['innerBlocks'][0]['attrs']['label'] );
		$this->assert_unique_block_ids( $blocks );
	}

	public function test_register_holds_the_account_fields_on_business() {
		$this->tier( true, true );

		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'       => 'Account',
						'fieldType'   => 'register',
						'required'    => true,
						'innerFields' => [
							[
								'label'     => 'Email',
								'fieldType' => 'email',
								'slug'      => 'email',
							],
							[
								'label'     => 'Password',
								'fieldType' => 'password',
								'slug'      => 'password',
							],
							[
								// Not allowed in the register block: dropped.
								'label'     => 'Bio',
								'fieldType' => 'textarea',
							],
						],
					],
				]
			)
		);

		$this->assertSame( [ 'srfm/register' ], array_column( $blocks, 'blockName' ) );
		$this->assertSame( 'register', $blocks[0]['attrs']['slug'] );
		$this->assertArrayNotHasKey( 'label', $blocks[0]['attrs'] );
		$this->assertArrayNotHasKey( 'required', $blocks[0]['attrs'] );
		$this->assertSame( [ 'srfm/email', 'srfm/password' ], array_column( $blocks[0]['innerBlocks'], 'blockName' ) );
		$this->assert_unique_block_ids( $blocks );
	}

	public function test_register_without_account_fields_falls_back_to_input() {
		$this->tier( true, true );

		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'     => 'Register',
						'fieldType' => 'register',
					],
				]
			)
		);

		// Like any type the mapper can't build: kept as a text field, not dropped.
		$this->assertSame( [ 'srfm/input' ], array_column( $blocks, 'blockName' ) );
		$this->assertSame( 'Register', $blocks[0]['attrs']['label'] );
	}

	public function test_register_is_skipped_with_the_skip_list() {
		$this->tier( true, true );
		$this->filter( 'srfm_ai_field_map_skip_fields', static fn() => [ 'register' ] );

		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'     => 'Name',
						'fieldType' => 'input',
					],
					[
						'fieldType'   => 'register',
						'innerFields' => [
							[
								'label'     => 'Email',
								'fieldType' => 'email',
							],
						],
					],
				]
			)
		);

		$this->assertSame( [ 'srfm/input' ], array_column( $blocks, 'blockName' ) );
	}

	public function test_default_address_sub_fields_get_slugs_from_the_address() {
		$blocks = $this->blocks(
			$this->map(
				[
					[
						'label'     => 'Billing address',
						'fieldType' => 'address',
						'slug'      => 'billing-address',
					],
					[
						'label'     => 'Delivery address',
						'fieldType' => 'address',
						'slug'      => 'delivery-address',
					],
				]
			)
		);

		$this->assertSame(
			[ 'billing-address-line-1', 'billing-address-line-2', 'billing-address-city', 'billing-address-state', 'billing-address-postal-code', 'billing-address-country' ],
			array_map( static fn( $block ) => $block['attrs']['slug'], $blocks[0]['innerBlocks'] )
		);
		$this->assertSame( 'delivery-address-city', $blocks[1]['innerBlocks'][2]['attrs']['slug'] );

		// Saving the form keeps them: they are already unique.
		$slugs   = [];
		$updated = false;
		[ $saved ] = \SRFM\Inc\Helper::process_blocks( $blocks, $slugs, $updated );
		$this->assertFalse( $updated );
		$this->assertSame( 'billing-address-postal-code', $saved[0]['innerBlocks'][4]['attrs']['slug'] );

		// Without the address's slug, the save names them, as before.
		$unnamed = $this->blocks( $this->map( [ [ 'label' => 'Address', 'fieldType' => 'address' ] ] ) );
		$this->assertArrayNotHasKey( 'slug', $unnamed[0]['innerBlocks'][0]['attrs'] );
	}
}
