<?php
/**
 * Class Test_Spec_Image
 *
 * Covers the tag-name allowlist validation added in CVE-2026-7623.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Spec_Image extends TestCase {

	/**
	 * Build a base attribute set with all keys the render path accesses
	 * directly (without isset checks).
	 *
	 * @return array<string,mixed>
	 */
	protected function base_attributes() {
		return [
			'block_id'                => 'img-block',
			'layout'                  => 'default',
			'url'                     => 'https://example.com/image.jpg',
			'urlTablet'               => '',
			'urlMobile'               => '',
			'alt'                     => 'alt text',
			'enableCaption'           => false,
			'caption'                 => '',
			'align'                   => '',
			'id'                      => 0,
			'href'                    => '',
			'rel'                     => '',
			'linkClass'               => '',
			'linkTarget'              => '',
			'title'                   => '',
			'width'                   => 200,
			'height'                  => 150,
			'naturalWidth'            => 200,
			'naturalHeight'           => 150,
			'disableLazyLoad'         => false,
			'heading'                 => 'Caption Heading',
			'headingId'               => '',
			'overlayContentPosition'  => 'center center',
			'separatorStyle'          => 'none',
			'separatorPosition'       => 'after_title',
			'imageHoverEffect'        => 'static',
		];
	}

	/**
	 * Render the block and assert it produced a string.
	 *
	 * @param array<string,mixed> $attrs Block attributes.
	 * @return string
	 */
	protected function render( array $attrs ) {
		$output = Advanced_Image::get_instance()->render_html( $attrs );
		$this->assertIsString( $output );
		return (string) $output;
	}

	public function test_render_html(): void {
		// Malicious headingTag must be rejected and fall back to the default `h2`.
		$attrs               = $this->base_attributes();
		$attrs['headingTag'] = 'h1 onmouseover=alert(1)';
		$output              = $this->render( $attrs );

		$this->assertStringNotContainsString( 'onmouseover', $output );
		$this->assertStringNotContainsString( 'alert(1)', $output );
		$this->assertStringContainsString( '<h2 class="uagb-image-heading">', $output );

		// Allowlisted headingTag passes through.
		$attrs['headingTag'] = 'h4';
		$output              = $this->render( $attrs );
		$this->assertStringContainsString( '<h4 class="uagb-image-heading">', $output );
		$this->assertStringContainsString( '</h4>', $output );

		// Disallowed but harmless tag (`p`) also falls back to `h2`.
		$attrs['headingTag'] = 'p';
		$output              = $this->render( $attrs );
		$this->assertStringContainsString( '<h2 class="uagb-image-heading">', $output );

		// headingId is escaped and emitted as an id attribute.
		$attrs               = $this->base_attributes();
		$attrs['headingTag'] = 'h2';
		$attrs['headingId']  = 'my-heading';
		$output              = $this->render( $attrs );
		$this->assertStringContainsString( 'id="my-heading"', $output );
	}
}
