<?php
/**
 * Get Global Settings Ability.
 *
 * @package sureforms
 * @since 2.6.0
 */

namespace SRFM\Inc\Abilities\Settings;

use SRFM\Inc\Abilities\Abstract_Ability;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get_Global_Settings ability class.
 *
 * Retrieves SureForms global settings by category.
 *
 * @since 2.6.0
 */
class Get_Global_Settings extends Abstract_Ability {
	use Settings_Secret_Keys;

	/**
	 * Sentinel value used to represent masked secret keys in output.
	 *
	 * Must match the sentinel in Update_Global_Settings for round-trip safety.
	 *
	 * @since 2.6.0
	 */
	private const SECRET_SENTINEL = '@@SRFM_SECRET_UNCHANGED@@';

	/**
	 * Constructor.
	 *
	 * @since 2.6.0
	 */
	public function __construct() {
		$this->id          = 'sureforms/get-global-settings';
		$this->label       = __( 'Get Global Settings', 'sureforms' );
		$this->description = __( 'Retrieve SureForms global settings. Optionally filter by category: general, validation-messages, email-summary, security.', 'sureforms' );
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
				'categories' => [
					'type'        => 'array',
					'description' => __( 'Setting categories to retrieve. Omit for all categories.', 'sureforms' ),
					'items'       => [
						'type' => 'string',
						'enum' => [ 'general', 'validation-messages', 'email-summary', 'security' ],
					],
				],
			],
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
				'general'             => [ 'type' => 'object' ],
				'validation-messages' => [ 'type' => 'object' ],
				'email-summary'       => [ 'type' => 'object' ],
				'security'            => [ 'type' => 'object' ],
			],
		];
	}

	/**
	 * Execute the get-global-settings ability.
	 *
	 * @param array<string,mixed> $input Validated input data.
	 * @since 2.6.0
	 * @return array<string,mixed>|\WP_Error
	 */
	public function execute( $input ) {
		$categories = ! empty( $input['categories'] ) && is_array( $input['categories'] )
			? array_map( 'sanitize_text_field', $input['categories'] )
			: [ 'general', 'validation-messages', 'email-summary', 'security' ];

		$result = [];

		foreach ( $categories as $category ) {
			switch ( $category ) {
				case 'general':
					$result['general'] = $this->get_general_settings();
					break;
				case 'validation-messages':
					$result['validation-messages'] = $this->get_validation_messages();
					break;
				case 'email-summary':
					$result['email-summary'] = $this->get_email_summary_settings();
					break;
				case 'security':
					$result['security'] = $this->get_security_settings();
					break;
			}
		}

		return $result;
	}

	/**
	 * Get general settings.
	 *
	 * @since 2.6.0
	 * @return array<string,mixed>
	 */
	private function get_general_settings() {
		$settings = get_option( 'srfm_general_settings_options', [] );

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			$settings = [
				'srfm_ip_log'             => false,
				'srfm_form_analytics'     => false,
				'srfm_admin_notification' => true,
			];
		}

		if ( ! isset( $settings['srfm_admin_notification'] ) ) {
			$settings['srfm_admin_notification'] = true;
		}

		$settings['srfm_bsf_analytics'] = 'yes' === get_option( 'sureforms_usage_optin', false );

		return $settings;
	}

	/**
	 * Get validation messages settings.
	 *
	 * @since 2.6.0
	 * @return array<string,mixed>
	 */
	private function get_validation_messages() {
		$settings = get_option( 'srfm_default_dynamic_block_option', [] );

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			$settings = Helper::default_dynamic_block_option();
		}

		return $settings;
	}

	/**
	 * Get email summary settings.
	 *
	 * @since 2.6.0
	 * @return array<string,mixed>
	 */
	private function get_email_summary_settings() {
		$settings = get_option( 'srfm_email_summary_settings_options', [] );

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			$settings = [
				'srfm_email_summary'   => false,
				'srfm_email_sent_to'   => get_option( 'admin_email' ),
				'srfm_schedule_report' => __( 'Monday', 'sureforms' ),
			];
		}

		return $settings;
	}

	/**
	 * Get security settings with secret keys masked.
	 *
	 * @since 2.6.0
	 * @return array<string,mixed>
	 */
	private function get_security_settings() {
		$settings = get_option( 'srfm_security_settings_options', [] );

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			$settings = [
				'srfm_v2_checkbox_site_key'    => '',
				'srfm_v2_checkbox_secret_key'  => '',
				'srfm_v2_invisible_site_key'   => '',
				'srfm_v2_invisible_secret_key' => '',
				'srfm_v3_site_key'             => '',
				'srfm_v3_secret_key'           => '',
				'srfm_cf_appearance_mode'      => 'auto',
				'srfm_cf_turnstile_site_key'   => '',
				'srfm_cf_turnstile_secret_key' => '',
				'srfm_hcaptcha_site_key'       => '',
				'srfm_hcaptcha_secret_key'     => '',
				'srfm_honeypot'                => false,
			];
		}

		// Mask all secret key fields with the sentinel value.
		foreach ( self::$secret_keys as $key ) {
			if ( ! empty( $settings[ $key ] ) ) {
				$settings[ $key ] = self::SECRET_SENTINEL;
			}
		}

		return $settings;
	}
}
