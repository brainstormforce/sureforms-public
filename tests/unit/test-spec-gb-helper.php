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
	 * Test get_srfm_form_blocks method.
	 * This function tests the recursion, duplicate prevention and block parsing
	 * logic in get_srfm_form_blocks.
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

		// Case 3: SRFM block – get_post returns a post and parse() returns blocks.
		$post_content = '<!-- wp:srfm/form {"id":999} /-->';
		$mock_post    = (object) [
			'ID'           => 999,
			'post_content' => $post_content,
		];

		// Recreate spec_gb_helper as mock to override parse()
		$this->spec_gb_helper = $this->getMockBuilder( Spec_Gb_Helper::class )
			->onlyMethods( [ 'parse' ] )
			->getMock();

		$this->spec_gb_helper->method( 'parse' )
			->willReturn( [ [ 'blockName' => 'test/inner' ] ] );

		// Mock global get_post
		$GLOBALS['wp_filter']['get_post'][0] = function () use ( $mock_post ) {
			return $mock_post;
		};

		$blocks = [
			'blockName' => 'srfm/form',
			'attrs'     => [ 'id' => 999 ],
		];
		$this->processed_ids_prop->setValue([]); // reset processed IDs

		$result = $this->spec_gb_helper->get_srfm_form_blocks( $blocks, [] );
		$this->assertEquals( [ [ 'blockName' => 'test/inner' ] ], $result, 'Failed asserting SRFM parsing and merging.' );

		// Case 4: Inner block recursion works.
		$inner_blocks = [
			'blockName'   => 'group',
			'innerBlocks' => [
				[
					'blockName' => 'srfm/form',
					'attrs'     => [ 'id' => 77 ],
				],
			],
		];

		// Mock parse() again for ID 77.
		$this->spec_gb_helper = $this->getMockBuilder( Spec_Gb_Helper::class )
			->onlyMethods( [ 'parse' ] )
			->getMock();

		$this->spec_gb_helper->method( 'parse' )
			->willReturn( [ [ 'blockName' => 'inner/parsed' ] ] );

		// Mock global get_post for ID 77.
		$GLOBALS['wp_filter']['get_post'][0] = function () {
			return (object) [
				'ID'           => 77,
				'post_content' => 'content',
			];
		};

		$this->processed_ids_prop->setValue([]); // reset processed IDs

		$result = $this->spec_gb_helper->get_srfm_form_blocks( $inner_blocks, [] );

		$this->assertEquals(
			[ [ 'blockName' => 'inner/parsed' ] ],
			$result,
			'Failed asserting recursive call for innerBlocks.'
		);
	}
}
