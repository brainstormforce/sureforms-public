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
	use Settings_Secret_Keys;

	/**
<<<<<<< Updated upstream
=======
	 * Sentinel value used to represent masked secret keys.
	 *
	 * @since x.x.x
	 */
	private const SECRET_SENTINEL = '@@SRFM_SECRET_UNCHANGED@@';

	/**
	 * Allowed setting keys per category.
	 *
	 * Only keys in this whitelist will be passed to the save functions.
	 * This prevents arbitrary option injection via crafted key names.
	 *
	 * @since x.x.x
	 * @var array<string,array<string>>
	 */
	private const ALLOWED_KEYS = [
		'general'             => [
			'srfm_ip_log',
			'srfm_form_analytics',
			'srfm_bsf_analytics',
			'srfm_admin_notification',
		],
		'validation-messages' => [
			'srfm_url_block_required_text',
			'srfm_input_block_required_text',
			'srfm_input_block_unique_text',
			'srfm_address_block_required_text',
			'srfm_phone_block_required_text',
			'srfm_phone_block_unique_text',
			'srfm_number_block_required_text',
			'srfm_textarea_block_required_text',
			'srfm_multi_choice_block_required_text',
			'srfm_checkbox_block_required_text',
			'srfm_gdpr_block_required_text',
			'srfm_email_block_required_text',
			'srfm_email_block_unique_text',
			'srfm_dropdown_block_required_text',
			'srfm_valid_phone_number',
			'srfm_valid_url',
			'srfm_confirm_email_same',
			'srfm_valid_email',
			'srfm_input_min_value',
			'srfm_input_max_value',
			'srfm_dropdown_min_selections',
			'srfm_dropdown_max_selections',
			'srfm_multi_choice_min_selections',
			'srfm_multi_choice_max_selections',
		],
		'email-summary'       => [
			'srfm_email_summary',
			'srfm_email_sent_to',
			'srfm_schedule_report',
		],
		'security'            => [
			'srfm_v2_checkbox_site_key',
			'srfm_v2_checkbox_secret_key',
			'srfm_v2_invisible_site_key',
			'srfm_v2_invisible_secret_key',
			'srfm_v3_site_key',
			'srfm_v3_secret_key',
			'srfm_cf_appearance_mode',
			'srfm_cf_turnstile_site_key',
			'srfm_cf_turnstile_secret_key',
			'srfm_hcaptcha_site_key',
			'srfm_hcaptcha_secret_key',
			'srfm_honeypot',
		],
	];

	/**
>>>>>>> Stashed changes
	 * Constructor.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		$this->id          = 'sureforms/update-global-settings';
		$this->label       = __( 'Update Global Settings', 'sureforms' );
		$this->description = __( 'Update SureForms global settings for a specific category: general, validation-messages, email-summary, or security.', 'sureforms' );
		$this->capability  = 'manage_options';
<<<<<<< Updated upstream
=======
		$this->gated       = 'srfm_abilities_api_edit';
>>>>>>> Stashed changes
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function get_annotations() {
		return [
<<<<<<< Updated upstream
			'readonly'    => false,
			'destructive' => false,
			'idempotent'  => true,
=======
			'readonly'      => false,
			'destructive'   => true,
			'idempotent'    => true,
			'priority'      => 2.0,
			'openWorldHint' => false,
>>>>>>> Stashed changes
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since x.x.x
	 */
	public function get_input_schema() {
		return [
<<<<<<< Updated upstream
			'type'       => 'object',
			'properties' => [
=======
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
			'required'   => [ 'category', 'settings' ],
=======
			'required'             => [ 'category', 'settings' ],
>>>>>>> Stashed changes
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

<<<<<<< Updated upstream
=======
		// Filter to allowed keys only — prevents arbitrary option injection.
		$settings = $this->filter_allowed_keys( $category, $settings );

		if ( empty( $settings ) ) {
			return new \WP_Error(
				'srfm_no_valid_keys',
				__( 'No valid settings keys provided for this category.', 'sureforms' ),
				[ 'status' => 400 ]
			);
		}

>>>>>>> Stashed changes
		// Sanitize settings per category before saving.
		$settings = $this->sanitize_settings( $category, $settings );

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
<<<<<<< Updated upstream
=======
	 * Filter settings to only include allowed keys for the given category.
	 *
	 * @param string              $category Settings category.
	 * @param array<string,mixed> $settings Raw settings values.
	 * @since x.x.x
	 * @return array<string,mixed> Filtered settings containing only whitelisted keys.
	 */
	private function filter_allowed_keys( $category, $settings ) {
		if ( ! isset( self::ALLOWED_KEYS[ $category ] ) ) {
			return [];
		}

		return array_intersect_key( $settings, array_flip( self::ALLOWED_KEYS[ $category ] ) );
	}

	/**
>>>>>>> Stashed changes
	 * Sanitize settings values based on category and known key types.
	 *
	 * @param string              $category Settings category.
	 * @param array<string,mixed> $settings Raw settings values.
	 * @since x.x.x
	 * @return array<string,mixed> Sanitized settings.
	 */
	private function sanitize_settings( $category, $settings ) {
		switch ( $category ) {
			case 'general':
				return $this->sanitize_general_settings( $settings );
			case 'email-summary':
				return $this->sanitize_email_summary_settings( $settings );
			case 'security':
				return $this->sanitize_security_settings( $settings );
			case 'validation-messages':
				return $this->sanitize_validation_messages( $settings );
			default:
				return $settings;
		}
	}

	/**
	 * Sanitize general settings.
	 *
	 * @param array<string,mixed> $settings Raw settings.
	 * @since x.x.x
	 * @return array<string,mixed>
	 */
	private function sanitize_general_settings( $settings ) {
		$boolean_keys = [ 'srfm_ip_log', 'srfm_form_analytics', 'srfm_admin_notification' ];

		foreach ( $boolean_keys as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$settings[ $key ] = rest_sanitize_boolean( Helper::get_string_value( $settings[ $key ] ) );
			}
		}

		return $settings;
	}

	/**
	 * Sanitize email summary settings.
	 *
	 * @param array<string,mixed> $settings Raw settings.
	 * @since x.x.x
	 * @return array<string,mixed>
	 */
	private function sanitize_email_summary_settings( $settings ) {
		if ( isset( $settings['srfm_email_summary'] ) ) {
			$settings['srfm_email_summary'] = rest_sanitize_boolean( Helper::get_string_value( $settings['srfm_email_summary'] ) );
		}

		if ( isset( $settings['srfm_email_sent_to'] ) ) {
			$settings['srfm_email_sent_to'] = sanitize_email( Helper::get_string_value( $settings['srfm_email_sent_to'] ) );
		}

		if ( isset( $settings['srfm_schedule_report'] ) ) {
			$settings['srfm_schedule_report'] = sanitize_text_field( Helper::get_string_value( $settings['srfm_schedule_report'] ) );
		}

		return $settings;
	}

	/**
	 * Sanitize security settings (applied before sentinel check).
	 *
	 * @param array<string,mixed> $settings Raw settings.
	 * @since x.x.x
	 * @return array<string,mixed>
	 */
	private function sanitize_security_settings( $settings ) {
		// Sanitize all site key and secret key values as text fields.
		$text_keys = [
			'srfm_v2_checkbox_site_key',
			'srfm_v2_checkbox_secret_key',
			'srfm_v2_invisible_site_key',
			'srfm_v2_invisible_secret_key',
			'srfm_v3_site_key',
			'srfm_v3_secret_key',
			'srfm_cf_turnstile_site_key',
			'srfm_cf_turnstile_secret_key',
			'srfm_hcaptcha_site_key',
			'srfm_hcaptcha_secret_key',
			'srfm_cf_appearance_mode',
		];

		foreach ( $text_keys as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$settings[ $key ] = sanitize_text_field( Helper::get_string_value( $settings[ $key ] ) );
			}
		}

		if ( isset( $settings['srfm_honeypot'] ) ) {
			$settings['srfm_honeypot'] = rest_sanitize_boolean( Helper::get_string_value( $settings['srfm_honeypot'] ) );
		}

		return $settings;
	}

	/**
	 * Sanitize validation message settings.
	 *
	 * @param array<string,mixed> $settings Raw settings.
	 * @since x.x.x
	 * @return array<string,mixed>
	 */
	private function sanitize_validation_messages( $settings ) {
		foreach ( $settings as $key => $value ) {
			if ( is_string( $value ) ) {
				$settings[ $key ] = sanitize_text_field( $value );
			}
		}

		return $settings;
	}

	/**
	 * Save security settings, preserving masked sentinel values.
	 *
<<<<<<< Updated upstream
	 * When the caller sends '********' for a secret key, the stored value
	 * is preserved instead of being overwritten with the sentinel.
=======
	 * When the caller sends the SECRET_SENTINEL for a secret key, the stored
	 * value is preserved instead of being overwritten with the sentinel.
>>>>>>> Stashed changes
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

		// Replace masked sentinel values with stored values.
		foreach ( self::$secret_keys as $key ) {
<<<<<<< Updated upstream
			if ( isset( $settings[ $key ] ) && '********' === $settings[ $key ] && isset( $existing[ $key ] ) ) {
=======
			if ( isset( $settings[ $key ] ) && self::SECRET_SENTINEL === $settings[ $key ] && isset( $existing[ $key ] ) ) {
>>>>>>> Stashed changes
				$settings[ $key ] = $existing[ $key ];
			}
		}

		return Global_Settings::srfm_save_security_settings( $settings );
	}
}
