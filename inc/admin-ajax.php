<?php
/**
 * Sureforms Admin Ajax Class.
 *
 * Class file for public functions.
 *
 * @package sureforms
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Public Class
 *
 * @since 0.0.1
 */
class Admin_Ajax {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_action( 'wp_ajax_sureforms_recommended_plugin_activate', [ $this, 'required_plugin_activate' ] );
		add_action( 'wp_ajax_sureforms_recommended_plugin_install', 'wp_ajax_install_plugin' );
		add_filter( SRFM_SLUG . '_admin_filter', [ $this, 'localize_script_integration' ] );

		add_action( 'wp_ajax_sureforms_test_integration', [ $this, 'send_test_data_to_suretriggers' ] );
	}

	/**
	 * Required Plugin Activate
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function required_plugin_activate() {

		$response_data = [ 'message' => $this->get_error_msg( 'permission' ) ];

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( $response_data );
		}

		if ( empty( $_POST ) ) {
			$response_data = [ 'message' => $this->get_error_msg( 'invalid' ) ];
			wp_send_json_error( $response_data );
		}

		/**
		 * Nonce verification.
		 */
		if ( ! check_ajax_referer( 'sf_plugin_manager_nonce', 'security', false ) ) {
			$response_data = [ 'message' => $this->get_error_msg( 'nonce' ) ];
			wp_send_json_error( $response_data );
		}

		if ( ! current_user_can( 'install_plugins' ) || ! isset( $_POST['init'] ) || ! sanitize_text_field( wp_unslash( $_POST['init'] ) ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'No plugin specified', 'sureforms' ),
				]
			);
		}

		$plugin_init = ( isset( $_POST['init'] ) ) ? sanitize_text_field( wp_unslash( $_POST['init'] ) ) : '';

		$activate = activate_plugin( $plugin_init, '', false, true );

		if ( is_wp_error( $activate ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => $activate->get_error_message(),
				]
			);
		}

		wp_send_json_success(
			[
				'success' => true,
				'message' => __( 'Plugin Successfully Activated', 'sureforms' ),
			]
		);
	}

	/**
	 * Get ajax error message.
	 *
	 * @param string $type Message type.
	 * @return string
	 * @since 0.0.2
	 */
	public function get_error_msg( $type ) {

		if ( ! isset( $this->errors[ $type ] ) ) {
			$type = 'default';
		}
		if ( ! isset( $this->errors ) ) {
			return '';
		}
		return $this->errors[ $type ];
	}

	/**
	 * Localize the variables required for integration plugins.
	 *
	 * @param array<mixed> $values localized values.
	 * @return array<mixed>
	 * @since 0.0.1
	 */
	public function localize_script_integration( $values ) {
		return array_merge(
			$values,
			[
				'ajax_url'               => admin_url( 'admin-ajax.php' ),
				'sfPluginManagerNonce'   => wp_create_nonce( 'sf_plugin_manager_nonce' ),
				'plugin_installer_nonce' => wp_create_nonce( 'updates' ),
				'plugin_activating_text' => __( 'Activating...', 'sureforms' ),
				'plugin_activated_text'  => __( 'Activated', 'sureforms' ),
				'plugin_activate_text'   => __( 'Activate', 'sureforms' ),
				'integrations'           => self::sureforms_get_integration(),
				'plugin_installing_text' => __( 'Installing...', 'sureforms' ),
				'plugin_installed_text'  => __( 'Installed', 'sureforms' ),
				'isRTL'                  => is_rtl(),
				'current_screen_id'      => get_current_screen() ? get_current_screen()->id : '',
				'form_id'                => get_post() ? get_post()->ID : '',
			]
		);
	}

	/**
	 *  Get sureforms recommended integrations.
	 *
	 * @since 0.0.1
	 * @return array<mixed>
	 */
	public function sureforms_get_integration() {
		$sc_api_token         = get_option( 'sc_api_token', '' );
		$surecart_redirection = empty( $sc_api_token ) ? 'sc-getting-started' : 'sc-dashboard';

		return apply_filters(
			'srfm_integrated_plugins',
			[
				[
					'title'       => __( 'SureTriggers', 'sureforms' ),
					'subtitle'    => __( 'Automate your WordPress setup.', 'sureforms' ),
					'description' => __( 'SureTriggers is a powerful automation platform that helps you connect your various plugins and apps together. It allows you to automate repetitive tasks, so you can focus on more important work.', 'sureforms' ),
					'status'      => self::get_plugin_status( 'suretriggers/suretriggers.php' ),
					'slug'        => 'suretriggers',
					'path'        => 'suretriggers/suretriggers.php',
					'redirection' => admin_url( 'admin.php?page=suretriggers' ),
					'logo'        => self::encode_svg( is_string( file_get_contents( plugin_dir_path( SRFM_FILE ) . 'images/suretriggers.svg' ) ) ? file_get_contents( plugin_dir_path( SRFM_FILE ) . 'images/suretriggers.svg' ) : '' ),
					'logo_full'   => self::encode_svg( is_string( file_get_contents( plugin_dir_path( SRFM_FILE ) . 'images/suretriggers_full.svg' ) ) ? file_get_contents( plugin_dir_path( SRFM_FILE ) . 'images/suretriggers_full.svg' ) : '' ),
				],
			]
		);
	}

	/**
	 * Encodes the given string with base64.
	 *
	 * @param  string $logo contains svg's.
	 * @return string
	 */
	public function encode_svg( $logo ) {
		return 'data:image/svg+xml;base64,' . base64_encode( $logo ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}
	/**
	 * Get plugin status
	 *
	 * @since 0.0.1
	 *
	 * @param  string $plugin_init_file Plguin init file.
	 * @return string
	 */
	public static function get_plugin_status( $plugin_init_file ) {

		$installed_plugins = get_plugins();

		if ( ! isset( $installed_plugins[ $plugin_init_file ] ) ) {
			return 'Install';
		} elseif ( is_plugin_active( $plugin_init_file ) ) {
			return 'Activated';
		} else {
			return 'Installed';
		}
	}

	public function send_test_data_to_suretriggers() {
		if ( empty( $_POST['formId'] ) ) {
			wp_send_json_error( [ 'message' => 'Form ID is required.' ] );
		}

		$suretriggers_data = get_option( 'suretrigger_options', [] );
		if ( empty( $suretriggers_data['secret_key'] ) || ! is_string( $suretriggers_data['secret_key'] ) ) {
			wp_send_json_error( [ 'message' => 'SureTriggers is not configured properly.' ] );
		}

		$form_id = Helper::get_integer_value( sanitize_text_field( $_POST['formId'] ) );

		$form = get_post( $form_id );

		$secret_key = '1007|OodR3OTaZksvUEMHf7u2JlpbSzojOOkPRgzqyRkbc3b69013'; // $suretriggers_data['secret_key'];
		$api_url    = 'https://api-qaing.suretriggers.com/automation/embeded/create';
		$header     = [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $secret_key,
			'Accept'        => 'application/json',
		];

		$base_url = 'https://tested-tatiya-nd.zipwp.link'; // get_site_url();

		$body = [
			'event'                    => [
				'label'             => 'Form Submitted',
				'value'             => 'sureforms_form_submitted',
				'description'       => 'Runs when a form is submitted',
				'schedule_callback' => false,
			],
			'connection_base_url'      => $base_url,
			'summery'                  => $form->post_title,
			'integration'              => 'SureForms',
			'integration_display_name' => 'SureForms',
			'form_data'                => [
				'success'   => '1',
				'form_id'   => $form_id,
				'emails'    => [
					'dev-email@wpengine.local',
				],
				'form_name' => $form->post_title,
				'message'   => '<p>Form submitted successfully!</p>',
				'data'      => $this->get_form_fields( $form_id ),
			],
		];

		if ( ! empty( $_POST['force'] ) ) {
			$body['force_create'] = sanitize_text_field( $_POST['force'] );
		}

		$request = wp_remote_post(
			$api_url,
			[
				'headers' => $header,
				'body'    => json_encode( $body ),
			]
		);

		if ( is_wp_error( $request ) ) {
			wp_send_json_error( [ 'message' => 'Error while sending test data to SureTriggers.' ] );
		}

		$data = json_decode( wp_remote_retrieve_body( $request ) );
		if ( empty( $data->data->iframe_url ) ) {
			wp_send_json_error( [ 'message' => 'Error while sending test data to SureTriggers.' ] );
		}
		$iframe_url = add_query_arg(
			[
				'st-code'   => $secret_key,
				'base_url'  => $base_url,
				'reset_url' => base64_encode( $base_url ),
			],
			$data->data->iframe_url
		);

		wp_send_json_success(
			[
				'message'    => 'success',
				'iframe_url' => $iframe_url,
			]
		);
	}

	public function get_form_fields( $form_id ) {
		if ( empty( $form_id ) ) {
			return [];
		}

		if ( 0 === $form_id || SRFM_FORMS_POST_TYPE !== get_post_type( $form_id ) ) {
			return [];
		}

		$post = get_post( $form_id );

		$blocks = parse_blocks( $post->post_content );

		if ( empty( $blocks ) ) {
			return [];
		}

		$data = [];

		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) && 0 === strpos( $block['blockName'], 'srfm/' ) ) {
				if ( ! empty( $block['attrs']['slug'] ) ) {
					$data[ $block['attrs']['slug'] ] = ! empty( $block['attrs']['label'] ) ? $block['attrs']['label'] : wp_rand( 10, 1000 );
				}
			}
		}

		if ( empty( $data ) ) {
			return [];
		}

		return $data;

	}
}

