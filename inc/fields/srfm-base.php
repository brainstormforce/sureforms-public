<?php
/**
 * Form Field Base Class.
 *
 * This file defines the base class for form fields in the SureForms package.
 *
 * @package SureForms
 * @since 0.0.1
 */

namespace SRFM\Inc\Fields;

/**
 * Field Base Class
 *
 * Defines the base class for form fields.
 *
 * @since 0.0.1
 */
class SRFM_Base {
	/**
	 * Render the sureforms default
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $form_id form id.
	 *
	 * @return string|boolean
	 */
	public function default( $attributes, $form_id ) {
		return '';
	}
}
