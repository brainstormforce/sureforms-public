<?php
/**
 * Date Validation Trait.
 *
 * Provides date format validation for abilities that accept date parameters.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Date_Validation trait.
 *
 * @since x.x.x
 */
trait Date_Validation {
	/**
	 * Validate a date string in Y-m-d format.
	 *
	 * @param string $date The date string to validate.
	 * @since x.x.x
	 * @return bool
	 */
	private function validate_date( string $date ): bool {
		$d = \DateTime::createFromFormat( 'Y-m-d', $date );
		return $d && $d->format( 'Y-m-d' ) === $date;
	}
}
