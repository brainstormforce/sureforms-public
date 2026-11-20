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
	 * Reset the static state get_block_css_and_js() accumulates.
	 */
	protected function tear_down(): void {
		Spec_Gb_Helper::$current_block_list = [];
		Spec_Gb_Helper::$srfm_flag          = false;
		Spec_Gb_Helper::$gfonts             = [];
		parent::tear_down();
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
			'brace breakout'  => 'srfmprobe} body{display:none} x{color:blue',
			'semicolon'       => 'srfmprobe;display:none',
			'style close tag' => 'srfmprobe</style><b>',
			'escaped brace'   => 'srfmprobe\\7d body\\7b display:none',
			'comment'         => 'srfmprobe/*',
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
		$this->assertStringNotContainsString( 'srfmprobe', $css, 'The declaration carrying the value is dropped, not trimmed.' );
		$this->assert_every_rule_scoped_to( 'abc123', $css );
	}

	/**
	 * Each block with its colour attribute.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function colour_attr_provider() {
		$cases = [];
		foreach ( self::COLOUR_ATTR as $block => $attr ) {
			$cases[ $block ] = [ $block, $attr ];
		}
		return $cases;
	}

	/**
	 * An object where a colour string is expected emits neither its keys nor its values.
	 *
	 * @dataProvider colour_attr_provider
	 *
	 * @param string $block Block name.
	 * @param string $attr  Colour attribute.
	 */
	public function test_generate_css_drops_non_scalar_value( $block, $attr ): void {
		$css = $this->css_for(
			$block,
			[
				'block_id'              => 'abc123',
				$attr                   => [ 'x{}body{display:none}srfmprobe' => 'srfmprobe' ],
				'imageBoxShadowHOffset' => 5,
			]
		);

		$this->assertStringNotContainsString( 'display:none', $css );
		$this->assertStringNotContainsString( 'srfmprobe', $css );
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
	 * block_id values and what sanitize_block_id() keeps.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function block_id_provider() {
		return [
			'saved hex id'   => [ 'a1b2c3d4', 'a1b2c3d4' ],
			'dash and score' => [ 'a-b_c', 'a-b_c' ],
			'integer'        => [ 123, '123' ],
			'selector chars' => [ 'x{} body, y</style>', 'xbodyystyle' ],
			'array'          => [ [ 'x' ], '' ],
			'null'           => [ null, '' ],
		];
	}

	/**
	 * Only identifier characters of a scalar block_id are kept.
	 *
	 * @dataProvider block_id_provider
	 *
	 * @param mixed  $block_id Saved block_id.
	 * @param string $expected Sanitised id.
	 */
	public function test_sanitize_block_id( $block_id, $expected ): void {
		$this->assertSame( $expected, Spec_Gb_Helper::sanitize_block_id( $block_id ) );
	}

	/**
	 * A non-scalar shadow colour is treated as unset.
	 */
	public function test_generate_shadow_css_ignores_non_scalar_color(): void {
		$shadow = [
			'horizontal' => 5,
			'color'      => [ 'x' => 'srfmprobe' ],
		];

		$this->assertSame( '', Spec_Gb_Helper::generate_shadow_css( $shadow ) );

		$shadow['alt_color'] = '#000';
		$this->assertSame( '5px 0 0 #000', Spec_Gb_Helper::generate_shadow_css( $shadow ) );
	}

	/**
	 * Each block's render_html() prints the same sanitised block id the CSS selectors use.
	 *
	 * @dataProvider hostile_block_id_provider
	 *
	 * @param string $block       Block name.
	 * @param string $block_id    Submitted block_id.
	 * @param string $expected_id block_id after sanitising.
	 */
	public function test_render_html_class_matches_css_block_id( $block, $block_id, $expected_id ): void {
		$html = render_block(
			[
				'blockName'    => $block,
				'attrs'        => [ 'block_id' => $block_id ],
				'innerBlocks'  => [],
				'innerHTML'    => '',
				'innerContent' => [],
			]
		);

		$processor = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $processor->next_tag(), "$block rendered no markup." );
		$this->assertTrue( $processor->has_class( 'uagb-block-' . $expected_id ), "$block wrapper class: " . $processor->get_attribute( 'class' ) );
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
