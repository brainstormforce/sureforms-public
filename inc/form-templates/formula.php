<?php
/**
 * Arithmetic for template previews.
 *
 * Works out a calculated field's example result from the example answers, so
 * the thank-you message and emails in the template library can show a real
 * looking number. Plain arithmetic only, parsed by hand: nothing is ever run
 * through `eval`.
 *
 * @package sureforms
 * @since 2.13.0
 */

namespace SRFM\Inc\Form_Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Safe evaluator for numbers, `+ - * / ^ %` and parentheses.
 *
 * `^` binds tightest and to the right (`2^3^2` is 512, `-2^2` is -4), `%` is
 * the remainder, like `*` and `/` in precedence, as in the form's calculator.
 *
 * @since 2.13.0
 */
class Formula {
	/**
	 * Deepest nesting of parentheses and signs that is parsed.
	 *
	 * @since 2.13.0
	 */
	private const MAX_DEPTH = 32;

	/**
	 * Tokens of the expression being parsed.
	 *
	 * @var array<int, float|string>
	 */
	private $tokens = [];

	/**
	 * Index of the next token.
	 *
	 * @var int
	 */
	private $pos = 0;

	/**
	 * Current nesting depth.
	 *
	 * @var int
	 */
	private $depth = 0;

	/**
	 * Evaluate an arithmetic expression.
	 *
	 * @param string $expression Numbers, `+ - * / ^ %`, parentheses and spaces.
	 * @since 2.13.0
	 * @return float|null The result, or null when the expression is invalid
	 *                    (letters, unbalanced parentheses, division by zero, …).
	 */
	public static function evaluate( $expression ) {
		$parser         = new self();
		$parser->tokens = self::tokenize( $expression );
		if ( empty( $parser->tokens ) ) {
			return null;
		}
		$result = $parser->parse_sum();
		if ( null === $result || count( $parser->tokens ) !== $parser->pos || ! is_finite( $result ) ) {
			return null;
		}
		return $result;
	}

	/**
	 * Split an expression into numbers and operators.
	 *
	 * @param string $expression Expression.
	 * @since 2.13.0
	 * @return array<int, float|string> Tokens; empty when a character isn't allowed.
	 */
	private static function tokenize( $expression ) {
		if ( ! preg_match_all( '/\s*(?:(\d+(?:\.\d*)?|\.\d+)|([-+*\/^%()]))\s*/A', $expression, $matches, PREG_SET_ORDER ) ) {
			return [];
		}
		$tokens = [];
		$length = 0;
		foreach ( $matches as $match ) {
			$length  += strlen( $match[0] );
			$tokens[] = isset( $match[2] ) && '' !== $match[2] ? $match[2] : (float) ( $match[1] ?? 0 );
		}
		// Anything the pattern stopped at (a letter, a comma, …) rejects the whole expression.
		return strlen( $expression ) === $length ? $tokens : [];
	}

	/**
	 * The next token, or null at the end.
	 *
	 * @since 2.13.0
	 * @return float|string|null
	 */
	private function peek() {
		return $this->tokens[ $this->pos ] ?? null;
	}

	/**
	 * Sum: product (('+'|'-') product)*.
	 *
	 * @since 2.13.0
	 * @return float|null
	 */
	private function parse_sum() {
		$value = $this->parse_product();
		while ( null !== $value && in_array( $this->peek(), [ '+', '-' ], true ) ) {
			$operator = $this->tokens[ $this->pos ];
			$this->pos++;
			$right = $this->parse_product();
			if ( null === $right ) {
				return null;
			}
			$value = '+' === $operator ? $value + $right : $value - $right;
		}
		return $value;
	}

	/**
	 * Product: unary (('*'|'/'|'%') unary)*.
	 *
	 * @since 2.13.0
	 * @return float|null
	 */
	private function parse_product() {
		$value = $this->parse_unary();
		while ( null !== $value && in_array( $this->peek(), [ '*', '/', '%' ], true ) ) {
			$operator = $this->tokens[ $this->pos ];
			$this->pos++;
			$right = $this->parse_unary();
			if ( null === $right ) {
				return null;
			}
			if ( '*' === $operator ) {
				$value *= $right;
				continue;
			}
			if ( 0.0 === (float) $right ) {
				return null;
			}
			$value = '/' === $operator ? $value / $right : fmod( $value, $right );
		}
		return $value;
	}

	/**
	 * Signed value: ('+'|'-') unary | power.
	 *
	 * @since 2.13.0
	 * @return float|null
	 */
	private function parse_unary() {
		$sign = $this->peek();
		if ( '+' !== $sign && '-' !== $sign ) {
			return $this->parse_power();
		}
		$this->depth++;
		if ( $this->depth > self::MAX_DEPTH ) {
			return null;
		}
		$this->pos++;
		$value = $this->parse_unary();
		$this->depth--;
		if ( null === $value ) {
			return null;
		}
		return '-' === $sign ? -$value : $value;
	}

	/**
	 * Power: primary ('^' unary)?, so it binds to the right.
	 *
	 * @since 2.13.0
	 * @return float|null
	 */
	private function parse_power() {
		$base = $this->parse_primary();
		if ( null === $base || '^' !== $this->peek() ) {
			return $base;
		}
		$this->pos++;
		$exponent = $this->parse_unary();
		if ( null === $exponent ) {
			return null;
		}
		$value = $base ** $exponent;
		return is_float( $value ) || is_int( $value ) ? (float) $value : null;
	}

	/**
	 * Number or a parenthesized sum.
	 *
	 * @since 2.13.0
	 * @return float|null
	 */
	private function parse_primary() {
		$token = $this->peek();
		if ( is_float( $token ) ) {
			$this->pos++;
			return $token;
		}
		if ( '(' !== $token ) {
			return null;
		}
		$this->depth++;
		if ( $this->depth > self::MAX_DEPTH ) {
			return null;
		}
		$this->pos++;
		$value = $this->parse_sum();
		$this->depth--;
		if ( null === $value || ')' !== $this->peek() ) {
			return null;
		}
		$this->pos++;
		return $value;
	}
}
