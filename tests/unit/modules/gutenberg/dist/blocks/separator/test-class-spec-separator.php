<?php
/**
 * Class Test_Spec_Separator
 *
 * Covers the block id Spec_Separator::render_html() prints.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Spec_Separator extends TestCase {

	/**
	 * The wrapper class carries the same sanitised block id as the CSS selector.
	 */
	public function test_render_html_prints_sanitized_block_id(): void {
		$html = render_block(
			[
				'blockName'    => 'srfm/separator',
				'attrs'        => [ 'block_id' => 'ab12{} body{display:none}' ],
				'innerBlocks'  => [],
				'innerHTML'    => '',
				'innerContent' => [],
			]
		);

		$processor = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $processor->next_tag() );
		$this->assertTrue( $processor->has_class( 'uagb-block-ab12bodydisplaynone' ), (string) $processor->get_attribute( 'class' ) );
		$this->assertStringNotContainsString( 'display:none', $html );
	}
}
