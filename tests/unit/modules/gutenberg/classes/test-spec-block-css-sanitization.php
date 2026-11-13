<?php
/**
 * Tests that block attributes cannot escape their declaration or selector
 * in the CSS built by Spec_Gb_Helper::get_block_css_and_js().
 *
 * @package sureforms
 */

use PHPUnit\Framework\TestCase;

class Test_Spec_Block_Css_Sanitization extends TestCase {

	/**
	 * Block name => colour attribute that reaches a declaration unconditionally.
	 */
	const COLOUR_ATTR = [
		'srfm/icon'             => 'iconColor',
		'srfm/image'            => 'imageBoxShadowColor',
		'srfm/separator'        => 'separatorColor',
		'srfm/advanced-heading' => 'headingColor',
	];

	protected function setUp(): void {
		if ( empty( Spec_Gb_Helper::$block_list ) ) {
			Spec_Gb_Helper::init_block_attributes();
		}
	}

	/**
	 * Build the block's CSS (all devices) for the given attributes.
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

	public function hostile_value_provider() {
		$values = [
			'brace breakout'   => 'red} body{display:none} x{color:red',
			'semicolon'        => 'red;display:none',
			'style close tag'  => 'red</style><b>',
			'escaped brace'    => 'red\\7d body\\7b display:none',
			'comment'          => 'red/*',
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
	 * @dataProvider hostile_value_provider
	 */
	public function test_hostile_value_is_dropped( $block, $attr, $value ) {
		$attrs = [
			'block_id'         => 'abc123',
			$attr              => $value,
			// Box shadow only renders with a non-zero offset.
			'imageBoxShadowHOffset' => 5,
		];
		$css   = $this->css_for( $block, $attrs );

		$this->assertStringNotContainsString( 'display:none', $css );
		$this->assertStringNotContainsString( '</style', $css );
		$this->assertStringNotContainsString( '\\', $css );
		$this->assertStringNotContainsString( '/*', $css );
		$this->assertStringNotContainsString( 'red', $css, 'The declaration carrying the hostile value is dropped, not trimmed.' );
		$this->assert_every_rule_scoped_to( 'abc123', $css );
	}

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
	 * @dataProvider legit_value_provider
	 */
	public function test_legit_value_is_kept( $block, $attr, $value ) {
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
	 * @dataProvider hostile_block_id_provider
	 */
	public function test_block_id_cannot_change_selector( $block, $block_id, $expected_id ) {
		$css = $this->css_for(
			$block,
			[
				'block_id'              => $block_id,
				self::COLOUR_ATTR[ $block ] => '#000',
				'imageBoxShadowHOffset' => 5,
				// Mask rule puts the block id inside a selector key too.
				'maskShape'             => 'circle',
			]
		);

		$this->assertNotSame( '', $css );
		$this->assert_every_rule_scoped_to( $expected_id, $css );
	}

	public function test_non_scalar_block_id_yields_empty_scope() {
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
	 * Every selector in every rule must be a descendant of .uagb-block-{$id}.
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
