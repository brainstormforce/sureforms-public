<?php
/**
 * Update Global Settings Ability.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\Abilities\Settings;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Global_Settings\Global_Settings;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Update_Global_Settings ability class.
 *
 * Updates SureForms global settings by category.
 *
 * @since x.x.x
 */
class Update_Global_Settings extends Abstract_Ability {

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id          = 'sureforms/update-global-settings';
		$this->label       = __( 'Update Global Settings', 'sureforms' );
		$this->description = __( 'Update SureForms global settings for a specific category: general, validation-messages, email-summary, or security.', 'sureforms' );
		$this->capability  = 'manage_options';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function get_annotations() {
		return [
			'readonly'    => false,
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
				'category' => [
					'type'        => 'string',
					'description' => __( 'The settings category to update.', 'sureforms' ),
					'enum'        => [ 'general', 'validation-messages', 'email-summary', 'security' ],
				],
				'settings' => [
					'type'        => 'object',
					'description' => __( 'Key-value pairs of settings to update.', 'sureforms' ),
				],
			],
			'required'   => [ 'category', 'settings' ],
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
				'saved'    => [ 'type' => 'boolean' ],
				'category' => [ 'type' => 'string' ],
			],
		];
	}

	/**
	 * Execute the update-global-settings ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since x.x.x
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$category = sanitize_text_field( Helper::get_string_value( $input['category'] ?? '' ) );
		$settings = $input['settings'] ?? [];

		if ( empty( $category ) ) {
			return new \WP_Error(
				'srfm_missing_category',
				__( 'Settings category is required.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return new \WP_Error(
				'srfm_missing_settings',
				__( 'Settings data is required.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

		$saved = false;

		switch ( $category ) {
			case 'general':
				$saved = Global_Settings::srfm_save_general_settings( $settings );
				break;
			case 'validation-messages':
				$saved = Global_Settings::srfm_save_general_settings_dynamic_opt( $settings );
				break;
			case 'email-summary':
				$saved = Global_Settings::srfm_save_email_summary_settings( $settings );
				break;
			case 'security':
				$saved = $this->save_security_settings( $settings );
				break;
			default:
				return new \WP_Error(
					'srfm_invalid_category',
					__( 'Invalid settings category.', 'sureforms' ),
					[ 'status' => 400 ]
				);
		}

		return [
			'saved'    => (bool) $saved,
			'category' => $category,
		];
	}

	/**
	 * Save security settings, preserving masked sentinel values.
	 *
	 * When the caller sends '********' for a secret key, the stored value
	 * is preserved instead of being overwritten with the sentinel.
	 *
	 * @param array<string,mixed> $settings Settings to save.
	 * @since x.x.x
	 * @return bool
	 */
	private function save_security_settings( $settings ) {
		$existing = get_option( 'srfm_security_settings_options', [] );

		if ( ! is_array( $existing ) ) {
			$existing = [];
		}

		$secret_keys = [
			'srfm_v2_checkbox_secret_key',
			'srfm_v2_invisible_secret_key',
			'srfm_v3_secret_key',
			'srfm_cf_turnstile_secret_key',
			'srfm_hcaptcha_secret_key',
		];

		// Replace masked sentinel values with stored values.
		foreach ( $secret_keys as $key ) {
			if ( isset( $settings[ $key ] ) && '********' === $settings[ $key ] && isset( $existing[ $key ] ) ) {
				$settings[ $key ] = $existing[ $key ];
			}
		}

		return Global_Settings::srfm_save_security_settings( $settings );
	}
}
