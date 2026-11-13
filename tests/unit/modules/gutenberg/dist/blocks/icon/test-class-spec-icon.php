<?php
/**
 * Class Test_Spec_Icon
 *
 * Covers the accessibility attributes Spec_Icon::render_html() adds to the
 * icon's <svg> tag.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Spec_Icon extends TestCase {

	/**
	 * Render the block with the attribute keys render_html() reads directly.
	 *
	 * @param string $mode iconAccessabilityMode.
	 * @param string $desc iconAccessabilityDesc.
	 * @return WP_HTML_Tag_Processor Processor positioned on the <svg> tag.
	 */
	protected function render_svg( $mode, $desc ) {
		$html = Spec_Icon::get_instance()->render_html(
			[
				'block_id'              => 'icon-test',
				'icon'                  => 'circle-check',
				'iconAccessabilityMode' => $mode,
				'iconAccessabilityDesc' => $desc,
			]
		);

		$this->assertIsString( $html );
		$processor = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $processor->next_tag( 'svg' ), 'The icon block must render an <svg>.' );
		return $processor;
	}

	/**
	 * Replacement-string tokens preg_replace() would expand.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function backreference_token_provider() {
		return [
			'braced dollar'  => [ '${1} onload=alert(1) x' ],
			'bare dollar'    => [ '$1 onload=alert(1) x' ],
			'whole match'    => [ '$0 onload=alert(1) x' ],
			'backslash'      => [ '\\1 onload=alert(1) x' ],
			'backslash zero' => [ '\\0 onload=alert(1) x' ],
		];
	}

	/**
	 * The description is written into aria-label verbatim and adds no other attribute.
	 *
	 * @dataProvider backreference_token_provider
	 *
	 * @param string $desc iconAccessabilityDesc.
	 */
	public function test_render_html_keeps_description_inside_aria_label( $desc ) {
		$svg = $this->render_svg( 'svg', $desc );

		$this->assertSame( $desc, $svg->get_attribute( 'aria-label' ) );
		$this->assertNull( $svg->get_attribute( 'onload' ) );
		$this->assertSame(
			[ 'xmlns', 'viewbox', 'role', 'aria-hidden', 'aria-label' ],
			$svg->get_attribute_names_with_prefix( '' )
		);
	}

	/**
	 * The icon keeps its viewBox and path after output filtering.
	 */
	public function test_render_html_keeps_icon_geometry() {
		$svg = $this->render_svg( 'svg', 'Done' );

		$this->assertNotEmpty( $svg->get_attribute( 'viewbox' ) );
		$this->assertTrue( $svg->next_tag( 'path' ) );
		$this->assertNotEmpty( $svg->get_attribute( 'd' ) );
	}

	/**
	 * Each accessibility mode sets the matching role and aria attributes.
	 */
	public function test_render_html_sets_attributes_per_mode() {
		$svg = $this->render_svg( 'image', 'A tick' );
		$this->assertSame( 'img', $svg->get_attribute( 'role' ) );
		$this->assertSame( 'false', $svg->get_attribute( 'aria-hidden' ) );
		$this->assertSame( 'A tick', $svg->get_attribute( 'aria-label' ) );

		$svg = $this->render_svg( 'svg', 'A tick' );
		$this->assertSame( 'graphics-symbol', $svg->get_attribute( 'role' ) );

		$svg = $this->render_svg( 'presentation', 'ignored' );
		$this->assertNull( $svg->get_attribute( 'role' ) );
		$this->assertSame( 'true', $svg->get_attribute( 'aria-hidden' ) );
		$this->assertNull( $svg->get_attribute( 'aria-label' ) );
	}

	/**
	 * Quotes in the description stay encoded inside aria-label.
	 */
	public function test_render_html_escapes_quotes_in_description() {
		$svg = $this->render_svg( 'image', '" onload="alert(1)' );

		$this->assertSame( '" onload="alert(1)', $svg->get_attribute( 'aria-label' ) );
		$this->assertNull( $svg->get_attribute( 'onload' ) );
	}
}
