<?php
/**
 * Class Test_Gutenberg_Hooks
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Gutenberg_Hooks;

/**
 * Tests for the Gutenberg_Hooks class.
 */
class Test_Gutenberg_Hooks extends TestCase {

	/**
	 * Test block_editor_assets enqueues the blocks script.
	 */
	public function test_block_editor_assets_enqueues_blocks_script() {
		$hooks = new Gutenberg_Hooks();
		$hooks->block_editor_assets();
		$this->assertTrue( wp_script_is( 'srfm-blocks', 'enqueued' ) );
		wp_dequeue_script( 'srfm-blocks' );
	}
}
