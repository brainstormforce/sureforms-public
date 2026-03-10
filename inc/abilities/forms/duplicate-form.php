<?php
/**
 * Duplicate Form Ability.
 *
 * @package sureforms
 * @since 2.5.2
 */

namespace SRFM\Inc\Abilities\Forms;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Duplicate_Form as Duplicate_Form_Handler;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Duplicate_Form ability class.
 *
 * Duplicates an existing SureForms form with all its fields and metadata.
 *
 * @since 2.5.2
 */
class Duplicate_Form extends Abstract_Ability {
	/**
	 * Constructor.
	 *
	 * @since 2.5.2
	 */
	public function __construct() {
		$this->id          = 'sureforms/duplicate-form';
		$this->label       = __( 'Duplicate SureForms Form', 'sureforms' );
		$this->description = __( 'Duplicate an existing SureForms form with all its fields, metadata, and settings. The new form is created as a draft.', 'sureforms' );
		$this->capability  = 'manage_options';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.5.2
	 */
	public function get_annotations() {
		return [
			'readonly'    => false,
			'destructive' => false,
			'idempotent'  => false,
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.5.2
	 */
	public function get_input_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id'      => [
					'type'        => 'integer',
					'description' => __( 'The ID of the form to duplicate.', 'sureforms' ),
				],
				'title_suffix' => [
					'type'        => 'string',
					'description' => __( 'Suffix to append to the duplicated form title.', 'sureforms' ),
					'default'     => ' (Copy)',
				],
			],
			'required'   => [ 'form_id' ],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.5.2
	 */
	public function get_output_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'original_form_id' => [ 'type' => 'integer' ],
				'new_form_id'      => [ 'type' => 'integer' ],
				'new_form_title'   => [ 'type' => 'string' ],
				'edit_url'         => [ 'type' => 'string' ],
				'shortcode'        => [ 'type' => 'string' ],
			],
		];
	}

	/**
	 * Execute the duplicate-form ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since 2.5.2
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$form_id      = Helper::get_integer_value( $input['form_id'] ?? 0 );
		$title_suffix = ! empty( $input['title_suffix'] ) ? sanitize_text_field( Helper::get_string_value( $input['title_suffix'] ) ) : ' (Copy)';

		$result = Duplicate_Form_Handler::get_instance()->duplicate_form( $form_id, $title_suffix );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result['shortcode'] = sprintf( '[sureforms id="%d"]', Helper::get_integer_value( $result['new_form_id'] ?? 0 ) );

		return $result;
	}
}
