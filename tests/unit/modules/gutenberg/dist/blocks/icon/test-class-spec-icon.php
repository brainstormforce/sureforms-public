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
	public function test_render_html_keeps_description_inside_aria_label( $desc ): void {
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
	public function test_render_html_keeps_icon_geometry(): void {
		$svg = $this->render_svg( 'svg', 'Done' );

		$this->assertNotEmpty( $svg->get_attribute( 'viewbox' ) );
		$this->assertTrue( $svg->next_tag( 'path' ) );
		$this->assertNotEmpty( $svg->get_attribute( 'd' ) );
	}

	/**
	 * Each accessibility mode sets the matching role and aria attributes.
	 */
	public function test_render_html_sets_attributes_per_mode(): void {
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
	public function test_render_html_escapes_quotes_in_description(): void {
		$svg = $this->render_svg( 'image', '" onload="alert(1)' );

		$this->assertSame( '" onload="alert(1)', $svg->get_attribute( 'aria-label' ) );
		$this->assertNull( $svg->get_attribute( 'onload' ) );
	}

	/**
	 * The printed SVG is filtered, so markup outside the allowlist never reaches the page.
	 */
	public function test_render_html_strips_markup_outside_the_svg_allowlist(): void {
		$filtered = false;
		$inject   = static function ( $content ) use ( &$filtered ) {
			if ( is_string( $content ) && false !== strpos( $content, '<svg' ) ) {
				$filtered = true;
				$content  = str_replace( '<svg', '<svg onclick="x" class="x"', $content );
				$content  = str_replace( '</svg>', '<script>x</script></svg>', $content );
			}
			return $content;
		};

		add_filter( 'pre_kses', $inject );
		try {
			$html = Spec_Icon::get_instance()->render_html(
				[
					'block_id'              => 'icon-test',
					'icon'                  => 'circle-check',
					'iconAccessabilityMode' => 'svg',
					'iconAccessabilityDesc' => 'Done',
				]
			);
		} finally {
			remove_filter( 'pre_kses', $inject );
		}

		$this->assertTrue( $filtered, 'The icon markup must pass through wp_kses().' );
		$this->assertStringNotContainsString( '<script', $html );

		$svg = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $svg->next_tag( 'svg' ) );
		$this->assertNull( $svg->get_attribute( 'onclick' ) );
		$this->assertNull( $svg->get_attribute( 'class' ) );
	}
}
