<?php
/**
 * Get Form Analytics Ability.
 *
 * @package sureforms
 * @since 2.6.0
 */

namespace SRFM\Inc\Abilities\Analytics;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get_Form_Analytics ability class.
 *
 * Retrieves form submission analytics for a given date range.
 *
 * @since 2.6.0
 */
class Get_Form_Analytics extends Abstract_Ability {
	/**
	 * Maximum number of analytics results to return.
	 *
	 * @since 2.6.0
	 */
	public const MAX_RESULTS = 10000;

	/**
	 * Constructor.
	 *
	 * @since 2.6.0
	 */
	public function __construct() {
		$this->id          = 'sureforms/get-form-analytics';
		$this->label       = __( 'Get Form Analytics', 'sureforms' );
		$this->description = __( 'Retrieve form submission analytics data for a specified date range, optionally filtered by form ID.', 'sureforms' );
		$this->capability  = 'manage_options';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.6.0
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
	 *
	 * @since 2.6.0
	 */
	public function get_input_schema() {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
				'date_from' => [
					'type'        => 'string',
					'description' => __( 'Start date for analytics range (Y-m-d).', 'sureforms' ),
				],
				'date_to'   => [
					'type'        => 'string',
					'description' => __( 'End date for analytics range (Y-m-d).', 'sureforms' ),
				],
				'form_id'   => [
					'type'        => 'integer',
					'description' => __( 'Optional form ID to filter analytics by.', 'sureforms' ),
				],
			],
			'required'             => [ 'date_from', 'date_to' ],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.6.0
	 */
	public function get_output_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'submissions' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'created_at' => [ 'type' => 'string' ],
						],
					],
				],
				'total_count' => [ 'type' => 'integer' ],
				'truncated'   => [ 'type' => 'boolean' ],
				'form_id'     => [ 'type' => [ 'integer', 'null' ] ],
				'date_from'   => [ 'type' => 'string' ],
				'date_to'     => [ 'type' => 'string' ],
			],
		];
	}

	/**
	 * Execute the get-form-analytics ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since 2.6.0
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$date_from = sanitize_text_field( Helper::get_string_value( $input['date_from'] ?? '' ) );
		$date_to   = sanitize_text_field( Helper::get_string_value( $input['date_to'] ?? '' ) );
		$form_id   = ! empty( $input['form_id'] ) ? absint( Helper::get_integer_value( $input['form_id'] ) ) : 0;

		if ( empty( $date_from ) || empty( $date_to ) ) {
			return new \WP_Error(
				'srfm_missing_dates',
				__( 'Both date_from and date_to are required.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! Helper::validate_date( $date_from ) || ! Helper::validate_date( $date_to ) ) {
			return new \WP_Error(
				'srfm_invalid_date_format',
				__( 'Dates must be in Y-m-d format.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		$where = [
			[
				[
					'key'     => 'created_at',
					'value'   => $date_from,
					'compare' => '>=',
				],
				[
					'key'     => 'created_at',
					'value'   => $date_to,
					'compare' => '<=',
				],
			],
		];

		if ( $form_id ) {
			$where[0][] = [
				'key'     => 'form_id',
				'value'   => $form_id,
				'compare' => '=',
			];
		}

		$submissions = Entries::get_instance()->get_results(
			$where,
			'created_at',
			[ 'ORDER BY created_at DESC', 'LIMIT ' . ( self::MAX_RESULTS + 1 ) ]
		);

		$truncated = count( $submissions ) > self::MAX_RESULTS;

		if ( $truncated ) {
			$submissions = array_slice( $submissions, 0, self::MAX_RESULTS );
		}

		return [
			'submissions' => $submissions,
			'total_count' => count( $submissions ),
			'truncated'   => $truncated,
			'form_id'     => $form_id ? $form_id : null,
			'date_from'   => $date_from,
			'date_to'     => $date_to,
		];
	}
}
