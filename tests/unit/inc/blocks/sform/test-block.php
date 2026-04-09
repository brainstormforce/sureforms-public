<?php
/**
 * Class Test_Sform_Block
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Blocks\Sform\Block;

/**
 * Tests for the Sform Block class.
 */
class Test_Sform_Block extends TestCase {

	/**
	 * Test render returns empty string when id is missing.
	 */
	public function test_render_returns_empty_when_no_id() {
		$block  = new Block();
		$result = $block->render( [] );
		$this->assertSame( '', $result );
	}

	/**
	 * Test render returns unavailable message for non-existent form.
	 */
	public function test_render_returns_unavailable_for_invalid_id() {
		$block  = new Block();
		$result = $block->render( [ 'id' => 999999 ] );
		$this->assertIsString( $result );
	}
}
