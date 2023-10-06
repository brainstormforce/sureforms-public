<?php
/**
 * Form Field Base Class.
 *
 * This file defines the base class for form fields in the SureForms package.
 *
 * @package SureForms
 * @since X.X.X
 */

namespace SureForms\Inc\Fields;

/**
 * Field Base Class
 *
 * Defines the base class for form fields.
 *
 * @since X.X.X
 */
class Base {

	/**
	 * Render the sureforms input default styling block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function default_styling( $attributes ) {
		return '';
	}

	/**
	 * Render the sureforms input classic styling
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string|boolean
	 */
	public function classic_styling( $attributes ) {
		return '';
	}
}
