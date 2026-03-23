<?php
/**
 * Get Shortcode Ability.
 *
 * @package sureforms
 * @since 2.5.2
 */

namespace SRFM\Inc\Abilities\Forms;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get_Shortcode ability class.
 *
 * Returns the shortcode and block markup for embedding a SureForms form.
 *
 * @since 2.5.2
 */
class Get_Shortcode extends Abstract_Ability {
	/**
	 * Constructor.
	 *
	 * @since 2.5.2
	 */
	public function __construct() {
		$this->id          = 'sureforms/get-shortcode';
		$this->label       = __( 'Get Form Shortcode', 'sureforms' );
		$this->description = __( 'Get the shortcode and block markup needed to embed a SureForms form on any page or post.', 'sureforms' );
		$this->capability  = 'manage_options';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_annotations() {
		return [
			'readonly'      => true,
			'destructive'   => false,
			'idempotent'    => true,
			'priority'      => 1.0,
			'openWorldHint' => false,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_input_schema() {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
				'form_id' => [
					'type'        => 'integer',
					'description' => __( 'The ID of the form to get the shortcode for.', 'sureforms' ),
				],
			],
			'required'             => [ 'form_id' ],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_output_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id'      => [ 'type' => 'integer' ],
				'shortcode'    => [ 'type' => 'string' ],
				'block_markup' => [ 'type' => 'string' ],
			],
		];
	}

	/**
	 * Execute the get-shortcode ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$form_id = Helper::get_integer_value( $input['form_id'] ?? 0 );
		$post    = get_post( $form_id );

		if ( ! $post || SRFM_FORMS_POST_TYPE !== $post->post_type ) {
			return new \WP_Error(
				'srfm_form_not_found',
				__( 'Form not found.', 'sureforms' ),
				[ 'status' => 404 ]
			);
		}

		return [
			'form_id'      => $form_id,
			'shortcode'    => sprintf( '[sureforms id="%d"]', $form_id ),
			'block_markup' => sprintf( '<!-- wp:srfm/form {"id":%d} /-->', $form_id ),
		];
	}
}
