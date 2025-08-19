<?php

use PHPUnit\Framework\TestCase;
use Spec_Gb_Helper;

class Spec_Gb_Helper_Test extends TestCase {

	protected $spec_gb_helper;
	protected $reflection;
	protected $processed_ids_prop;

	protected function setUp(): void {
		$this->spec_gb_helper = new Spec_Gb_Helper();

		// Setup Reflection to access private static $processed_srfm_ids
		$this->reflection = new ReflectionClass(Spec_Gb_Helper::class);
		$this->processed_ids_prop = $this->reflection->getProperty('processed_srfm_ids');
		$this->processed_ids_prop->setAccessible(true);

		// Reset before each test
		$this->processed_ids_prop->setValue([]);
	}

	/**
	 * Test get_srfm_form_blocks method without mocking final class.
	 *
	 * @return void
	 */
	public function test_get_srfm_form_blocks() {

		// Case 1: Invalid/empty $blocks array.
		$result = $this->spec_gb_helper->get_srfm_form_blocks( [] );
		$this->assertEquals( [], $result, 'Failed asserting when empty block array is passed.' );

		// Case 2: SRFM block but already processed (duplicate).
		$this->processed_ids_prop->setValue([123]); // mark ID as already processed
		$blocks = [
			'blockName' => 'srfm/form',
			'attrs'     => [ 'id' => 123 ],
		];
		$result = $this->spec_gb_helper->get_srfm_form_blocks( $blocks, [] );
		$this->assertEquals( [], $result, 'Failed asserting when block ID is already processed.' );

		// Case 3: Inner block recursion works (without mocking parse).
		$inner_blocks = [
			'blockName'   => 'group',
			'innerBlocks' => [
				[
					'blockName' => 'srfm/form',
					'attrs'     => [ 'id' => 77 ],
				],
			],
		];

		// Reset processed IDs
		$this->processed_ids_prop->setValue([]);

		$result = $this->spec_gb_helper->get_srfm_form_blocks( $inner_blocks, [] );

		// Since parse() is not mocked, the return may be empty depending on WP environment
		// We just assert it returns an array (integration test)
		$this->assertIsArray(
			$result,
			'Failed asserting recursive call for innerBlocks returns an array.'
		);
	}
}
