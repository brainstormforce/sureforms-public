<?php
/**
 * Get Form Ability.
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
 * Get_Form ability class.
 *
 * Retrieves detailed information about a specific SureForms form,
 * including its field structure parsed from Gutenberg blocks.
 *
 * @since 2.5.2
 */
class Get_Form extends Abstract_Ability {
	/**
	 * Constructor.
	 *
	 * @since 2.5.2
	 */
	public function __construct() {
		$this->id          = 'sureforms/get-form';
		$this->label       = __( 'Get SureForms Form Details', 'sureforms' );
		$this->description = __( 'Retrieve detailed information about a specific SureForms form including its fields, settings, and shortcode.', 'sureforms' );
		$this->capability  = 'manage_options';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_annotations() {
		return [
			'readonly'    => true,
			'destructive' => false,
			'idempotent'  => true,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_input_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id' => [
					'type'        => 'integer',
					'description' => __( 'The ID of the form to retrieve.', 'sureforms' ),
				],
			],
			'required'   => [ 'form_id' ],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_output_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id'   => [ 'type' => 'integer' ],
				'title'     => [ 'type' => 'string' ],
				'status'    => [ 'type' => 'string' ],
				'fields'    => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'type'     => [ 'type' => 'string' ],
							'label'    => [ 'type' => 'string' ],
							'slug'     => [ 'type' => 'string' ],
							'required' => [ 'type' => 'boolean' ],
						],
					],
				],
				'settings'  => [ 'type' => 'object' ],
				'shortcode' => [ 'type' => 'string' ],
			],
		];
	}

	/**
	 * Execute the get-form ability.
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

		// Parse blocks to extract field structure.
		$blocks = parse_blocks( $post->post_content );
		$fields = $this->extract_fields_from_blocks( $blocks );

		// Gather form settings from post meta.
		$settings = [
			'submit_button_text'       => Helper::get_meta_value( $form_id, '_srfm_submit_button_text' ),
			'use_label_as_placeholder' => Helper::get_meta_value( $form_id, '_srfm_use_label_as_placeholder' ),
			'form_container_width'     => Helper::get_meta_value( $form_id, '_srfm_form_container_width' ),
			'instant_form'             => Helper::get_meta_value( $form_id, '_srfm_instant_form' ),
			'submit_alignment'         => Helper::get_meta_value( $form_id, '_srfm_submit_alignment' ),
			'form_recaptcha'           => Helper::get_meta_value( $form_id, '_srfm_form_recaptcha' ),
		];

		return [
			'form_id'   => $form_id,
			'title'     => $post->post_title,
			'status'    => $post->post_status,
			'fields'    => $fields,
			'settings'  => $settings,
			'shortcode' => sprintf( '[sureforms id="%d"]', $form_id ),
		];
	}

	/**
	 * Extract field information from parsed Gutenberg blocks.
	 *
	 * @param array<mixed> $blocks Parsed blocks array.
	 * @since 2.5.2
	 * @return array<array<string,mixed>>
	 */
	private function extract_fields_from_blocks( $blocks ) {
		$fields = [];

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$block_name = Helper::get_string_value( $block['blockName'] ?? '' );

			if ( empty( $block_name ) ) {
				continue;
			}

			$inner_blocks = Helper::get_array_value( $block['innerBlocks'] ?? [] );

			// Only process srfm/* blocks.
			if ( 0 !== strpos( $block_name, 'srfm/' ) ) {
				// Check inner blocks for nested structures.
				if ( ! empty( $inner_blocks ) ) {
					$fields = array_merge( $fields, $this->extract_fields_from_blocks( $inner_blocks ) );
				}
				continue;
			}

			// Skip the form wrapper block itself.
			if ( 'srfm/form' === $block_name ) {
				if ( ! empty( $inner_blocks ) ) {
					$fields = array_merge( $fields, $this->extract_fields_from_blocks( $inner_blocks ) );
				}
				continue;
			}

			$attrs = Helper::get_array_value( $block['attrs'] ?? [] );

			$fields[] = [
				'type'     => str_replace( 'srfm/', '', $block_name ),
				'label'    => $attrs['label'] ?? '',
				'slug'     => $attrs['slug'] ?? '',
				'required' => ! empty( $attrs['required'] ),
			];
		}

		return $fields;
	}
}
