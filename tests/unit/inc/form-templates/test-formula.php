<?php
/**
 * Class Test_Formula
 *
 * The arithmetic evaluator behind the template library's calculation samples.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Templates\Formula;

class Test_Formula extends TestCase {

	/**
	 * @dataProvider formula_provider
	 */
	public function test_evaluate( $expression, $expected ) {
		$result = Formula::evaluate( $expression );
		if ( null === $expected ) {
			$this->assertNull( $result );
			return;
		}
		$this->assertEqualsWithDelta( $expected, $result, 0.000001 );
	}

	public function formula_provider() {
		return [
			'precedence'          => [ '2 + 3 * 4', 14.0 ],
			'parentheses'         => [ '(2 + 3) * 4', 20.0 ],
			'nested parentheses'  => [ '((1 + 1) * (2 + 2)) / 4', 2.0 ],
			'power'               => [ '2 ^ 10', 1024.0 ],
			'power right assoc'   => [ '2 ^ 3 ^ 2', 512.0 ],
			'power over minus'    => [ '-2 ^ 2', -4.0 ],
			'negative exponent'   => [ '2 ^ -1', 0.5 ],
			'loan payment'        => [ '1000 * 0.01 / (1 - (1 + 0.01) ^ -12)', 88.84878867834168 ],
			'modulo'              => [ '10 % 3', 1.0 ],
			'decimals'            => [ '.5 + 1.25', 1.75 ],
			'unary'               => [ '-(3 - 5)', 2.0 ],
			'division by zero'    => [ '1 / 0', null ],
			'modulo by zero'      => [ '5 % 0', null ],
			'division by -0'      => [ '1 / (0 * -1)', null ],
			'letters'             => [ '2 + abc', null ],
			'function call'       => [ 'max(1, 2)', null ],
			'php code'            => [ 'system("id")', null ],
			'unbalanced'          => [ '(1 + 2', null ],
			'stray close'         => [ '1 + 2)', null ],
			'dangling operator'   => [ '1 +', null ],
			'empty'               => [ '', null ],
			'too deep'            => [ str_repeat( '(', 40 ) . '1' . str_repeat( ')', 40 ), null ],
			'complex root'        => [ '(-8) ^ 0.5', null ],
		];
	}
}
