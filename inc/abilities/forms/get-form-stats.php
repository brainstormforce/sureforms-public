<?php
/**
 * Get Form Stats Ability.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Forms;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Database\Tables\Entries as EntriesTable;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get_Form_Stats ability class.
 *
 * Retrieves submission statistics for a specific form or all forms.
 *
 * @since x.x.x
 */
class Get_Form_Stats extends Abstract_Ability {
	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id          = 'sureforms/get-form-stats';
		$this->label       = __( 'Get Form Statistics', 'sureforms' );
		$this->description = __( 'Get submission statistics for a specific SureForms form or all forms. Returns total entries, unread, read, and trash counts.', 'sureforms' );
		$this->capability  = 'edit_posts';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
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
	 *
	 * @since x.x.x
	 */
	public function get_input_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id' => [
					'type'        => 'integer',
					'description' => __( 'The ID of the form to get stats for. Use 0 or omit for all forms.', 'sureforms' ),
					'default'     => 0,
				],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function get_output_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id'       => [ 'type' => 'integer' ],
				'form_title'    => [ 'type' => 'string' ],
				'form_status'   => [ 'type' => 'string' ],
				'total_entries' => [ 'type' => 'integer' ],
				'unread_count'  => [ 'type' => 'integer' ],
				'read_count'    => [ 'type' => 'integer' ],
				'trash_count'   => [ 'type' => 'integer' ],
			],
		];
	}

	/**
	 * Execute the get-form-stats ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since x.x.x
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$form_id = Helper::get_integer_value( $input['form_id'] ?? 0 );

		// If a specific form is requested, validate it exists.
		$form_title  = '';
		$form_status = '';

		if ( $form_id > 0 ) {
			$post = get_post( $form_id );

			if ( ! $post || SRFM_FORMS_POST_TYPE !== $post->post_type ) {
				return new \WP_Error(
					'srfm_form_not_found',
					__( 'Form not found.', 'sureforms' ),
					[ 'status' => 404 ]
				);
			}

			$form_title  = $post->post_title;
			$form_status = $post->post_status;
		}

		$entries_table = EntriesTable::get_instance();

		// Build form_id condition.
		$form_condition = [];
		if ( $form_id > 0 ) {
			$form_condition = [
				[
					'key'     => 'form_id',
					'compare' => '=',
					'value'   => $form_id,
				],
			];
		}

		// Total entries (excluding trash).
		$total_where   = [];
		$total_where[] = [
			[
				'key'     => 'status',
				'compare' => '!=',
				'value'   => 'trash',
			],
		];
		if ( ! empty( $form_condition ) ) {
			$total_where[] = $form_condition;
		}
		$total_entries = $entries_table->get_total_count( $total_where );

		// Unread count.
		$unread_where   = [];
		$unread_where[] = [
			[
				'key'     => 'status',
				'compare' => '=',
				'value'   => 'unread',
			],
		];
		if ( ! empty( $form_condition ) ) {
			$unread_where[] = $form_condition;
		}
		$unread_count = $entries_table->get_total_count( $unread_where );

		// Read count.
		$read_where   = [];
		$read_where[] = [
			[
				'key'     => 'status',
				'compare' => '=',
				'value'   => 'read',
			],
		];
		if ( ! empty( $form_condition ) ) {
			$read_where[] = $form_condition;
		}
		$read_count = $entries_table->get_total_count( $read_where );

		// Trash count.
		$trash_where   = [];
		$trash_where[] = [
			[
				'key'     => 'status',
				'compare' => '=',
				'value'   => 'trash',
			],
		];
		if ( ! empty( $form_condition ) ) {
			$trash_where[] = $form_condition;
		}
		$trash_count = $entries_table->get_total_count( $trash_where );

		return [
			'form_id'       => $form_id,
			'form_title'    => $form_title,
			'form_status'   => $form_status,
			'total_entries' => $total_entries,
			'unread_count'  => $unread_count,
			'read_count'    => $read_count,
			'trash_count'   => $trash_count,
		];
	}
}
