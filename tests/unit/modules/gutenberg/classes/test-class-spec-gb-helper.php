<?php
/**
 * Class Test_Class_Spec_Gb_Helper
 *
 * Covers the CSS Spec_Gb_Helper builds from srfm block attributes, and the
 * icon markup it renders.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Class_Spec_Gb_Helper extends TestCase {

	/**
	 * Block name => colour attribute that always reaches a declaration.
	 *
	 * @var array<string, string>
	 */
	const COLOUR_ATTR = [
		'srfm/icon'             => 'iconColor',
		'srfm/image'            => 'imageBoxShadowColor',
		'srfm/separator'        => 'separatorColor',
		'srfm/advanced-heading' => 'headingColor',
	];

	/**
	 * Load the block attribute defaults the CSS builders read.
	 */
	protected function set_up(): void {
		parent::set_up();
		if ( empty( Spec_Gb_Helper::$block_list ) ) {
			Spec_Gb_Helper::init_block_attributes();
		}
	}

	/**
	 * Build the block's CSS for every device.
	 *
	 * @param string              $name  Block name.
	 * @param array<string,mixed> $attrs Block attributes.
	 * @return string
	 */
	private function css_for( $name, array $attrs ) {
		$assets = ( new ReflectionClass( Spec_Gb_Helper::class ) )->newInstanceWithoutConstructor()->get_block_css_and_js(
			[
				'blockName' => $name,
				'attrs'     => $attrs,
			]
		);
		unset( $assets['css']['common'] );

		return implode( '', $assets['css'] );
	}

	/**
	 * Hostile colour values for each block.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function hostile_value_provider() {
		$values = [
			'brace breakout'  => 'red} body{display:none} x{color:red',
			'semicolon'       => 'red;display:none',
			'style close tag' => 'red</style><b>',
			'escaped brace'   => 'red\\7d body\\7b display:none',
			'comment'         => 'red/*',
		];
		$cases  = [];
		foreach ( self::COLOUR_ATTR as $block => $attr ) {
			foreach ( $values as $label => $value ) {
				$cases[ "$block $label" ] = [ $block, $attr, $value ];
			}
		}
		return $cases;
	}

	/**
	 * A value that would leave its declaration is dropped.
	 *
	 * @dataProvider hostile_value_provider
	 *
	 * @param string $block Block name.
	 * @param string $attr  Colour attribute.
	 * @param string $value Attribute value.
	 */
	public function test_generate_css_drops_value_that_leaves_its_declaration( $block, $attr, $value ): void {
		$css = $this->css_for(
			$block,
			[
				'block_id'              => 'abc123',
				$attr                   => $value,
				// Box shadow only renders with a non-zero offset.
				'imageBoxShadowHOffset' => 5,
			]
		);

		$this->assertStringNotContainsString( 'display:none', $css );
		$this->assertStringNotContainsString( '</style', $css );
		$this->assertStringNotContainsString( '\\', $css );
		$this->assertStringNotContainsString( '/*', $css );
		$this->assertStringNotContainsString( 'red', $css, 'The declaration carrying the value is dropped, not trimmed.' );
		$this->assert_every_rule_scoped_to( 'abc123', $css );
	}

	/**
	 * Colour values the block controls produce.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function legit_value_provider() {
		$values = [
			'hex'      => '#ff00aa',
			'rgba'     => 'rgba(10, 20, 30, 0.5)',
			'var'      => 'var(--ast-global-color-0)',
			'gradient' => 'linear-gradient(90deg, rgba(6,147,227,1) 0%, rgb(155,81,224) 100%)',
		];
		$cases  = [];
		foreach ( self::COLOUR_ATTR as $block => $attr ) {
			foreach ( $values as $label => $value ) {
				$cases[ "$block $label" ] = [ $block, $attr, $value ];
			}
		}
		return $cases;
	}

	/**
	 * Legitimate values are emitted unchanged.
	 *
	 * @dataProvider legit_value_provider
	 *
	 * @param string $block Block name.
	 * @param string $attr  Colour attribute.
	 * @param string $value Attribute value.
	 */
	public function test_generate_css_keeps_legitimate_value( $block, $attr, $value ): void {
		$css = $this->css_for(
			$block,
			[
				'block_id'              => 'abc123',
				$attr                   => $value,
				'imageBoxShadowHOffset' => 5,
			]
		);

		$this->assertStringContainsString( $value, $css );
	}

	/**
	 * Hostile block ids and what each is reduced to.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function hostile_block_id_provider() {
		$ids   = [
			'selector list'  => [ 'x, body', 'xbody' ],
			'brace breakout' => [ 'x{} body{display:none} y', 'xbodydisplaynoney' ],
			'style close'    => [ 'x</style>', 'xstyle' ],
		];
		$cases = [];
		foreach ( array_keys( self::COLOUR_ATTR ) as $block ) {
			foreach ( $ids as $label => $pair ) {
				$cases[ "$block $label" ] = [ $block, $pair[0], $pair[1] ];
			}
		}
		return $cases;
	}

	/**
	 * The block id cannot change which elements a rule selects.
	 *
	 * @dataProvider hostile_block_id_provider
	 *
	 * @param string $block       Block name.
	 * @param string $block_id    Submitted block_id.
	 * @param string $expected_id block_id after sanitising.
	 */
	public function test_get_block_css_and_js_keeps_block_id_inside_selector( $block, $block_id, $expected_id ): void {
		$css = $this->css_for(
			$block,
			[
				'block_id'                  => $block_id,
				self::COLOUR_ATTR[ $block ] => '#000',
				'imageBoxShadowHOffset'     => 5,
				// The mask rule puts the block id inside a selector key too.
				'maskShape'                 => 'circle',
			]
		);

		$this->assertNotSame( '', $css );
		$this->assert_every_rule_scoped_to( $expected_id, $css );
	}

	/**
	 * A non-scalar block id is treated as empty.
	 */
	public function test_get_block_css_and_js_ignores_non_scalar_block_id(): void {
		$css = $this->css_for(
			'srfm/icon',
			[
				'block_id'  => [ 'x' ],
				'iconColor' => '#000',
			]
		);

		$this->assert_every_rule_scoped_to( '', $css );
	}

	/**
	 * The icon template emits only the markup Spec_Icon::ALLOWED_SVG_HTML keeps.
	 */
	public function test_render_svg_html_emits_only_allowlisted_markup(): void {
		$html = Spec_Gb_Helper::render_svg_html( 'circle-check', true );
		$this->assertIsString( $html );

		$allowed   = [
			'SVG'  => [ 'viewbox', 'xmlns' ],
			'PATH' => [ 'd' ],
		];
		$seen      = [];
		$processor = new WP_HTML_Tag_Processor( $html );
		while ( $processor->next_tag() ) {
			$tag = $processor->get_tag();
			$this->assertArrayHasKey( $tag, $allowed, "Unexpected <$tag> in the icon template." );
			$names = $processor->get_attribute_names_with_prefix( '' );
			sort( $names );
			$this->assertSame( $allowed[ $tag ], $names, "Unexpected attributes on <$tag>." );
			$this->assertNotEmpty( $processor->get_attribute( 'SVG' === $tag ? 'viewbox' : 'd' ) );
			$seen[] = $tag;
		}

		$this->assertSame( [ 'SVG', 'PATH' ], $seen );
	}

	/**
	 * Every selector in every rule must be a descendant of .uagb-block-{$id}.
	 *
	 * @param string $id  Expected block id.
	 * @param string $css Generated CSS.
	 */
	private function assert_every_rule_scoped_to( $id, $css ) {
		$this->assertSame( substr_count( $css, '{' ), substr_count( $css, '}' ), 'Braces stay balanced.' );
		preg_match_all( '/(?:^|})([^{}]+){/', $css, $m );
		$this->assertNotEmpty( $m[1] );
		foreach ( $m[1] as $prelude ) {
			foreach ( explode( ',', $prelude ) as $selector ) {
				$this->assertMatchesRegularExpression( '/^\.uagb-block-' . preg_quote( $id, '/' ) . '(?![\w-])/', ltrim( $selector ), "Selector escaped its block scope: $prelude" );
			}
		}
	}
}
