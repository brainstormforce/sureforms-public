<?php
/**
 * Class Test_Advanced_Heading
 *
 * Covers the tag-name allowlist validation added in CVE-2026-7623.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Advanced_Heading extends TestCase {

	/**
	 * Build a base attribute set with all keys the render path accesses
	 * directly (without isset checks).
	 *
	 * @return array<string,mixed>
	 */
	protected function base_attributes() {
		return [
			'block_id'             => 'test-block',
			'headingTitle'         => 'Hello',
			'headingTitleToggle'   => true,
			'headingDescToggle'    => false,
			'headingDescPosition'  => 'below-heading',
			'separatorStyle'       => 'none',
			'separatorPosition'    => 'below-heading',
		];
	}

	/**
	 * Render the block and assert it produced a string.
	 *
	 * @param array<string,mixed> $attrs Block attributes.
	 * @return string
	 */
	protected function render( array $attrs ) {
		$output = Advanced_Heading::get_instance()->render_html( $attrs );
		$this->assertIsString( $output );
		return (string) $output;
	}

	public function test_render_html(): void {
		// Malicious headingTag must be rejected and fall back to the default `h2`.
		$attrs                = $this->base_attributes();
		$attrs['headingTag']  = 'script>alert(1)</script';
		$output               = $this->render( $attrs );

		$this->assertStringContainsString( '<h2 class="uagb-heading-text"', $output );
		$this->assertStringNotContainsString( 'alert(1)', $output );
		$this->assertStringNotContainsString( '<script', $output );

		// Allowlisted headingTag passes through.
		$attrs['headingTag'] = 'h3';
		$output              = $this->render( $attrs );
		$this->assertStringContainsString( '<h3 class="uagb-heading-text"', $output );

		// Malicious headingWrapper must fall back to `div`.
		$attrs                    = $this->base_attributes();
		$attrs['headingWrapper']  = 'div onmouseover=alert(1)';
		$output                   = $this->render( $attrs );
		$this->assertStringNotContainsString( 'onmouseover', $output );
		$this->assertMatchesRegularExpression( '/<div [^>]*data-block-id="test-block"/', $output );

		// Allowlisted headingWrapper `header` passes through.
		$attrs['headingWrapper'] = 'header';
		$output                  = $this->render( $attrs );
		$this->assertMatchesRegularExpression( '/<header [^>]*data-block-id="test-block"/', $output );
		$this->assertStringContainsString( '</header>', $output );
	}
}
