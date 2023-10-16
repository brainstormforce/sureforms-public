<?php
/**
 * Sureforms Admin Ajax Class.
 *
 * Class file for public functions.
 *
 * @package sureforms
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;

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
class SF_Admin_Ajax {

	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_action( 'wp_ajax_sureforms_recommended_plugin_activate', [ $this, 'required_plugin_activate' ] );
		add_action( 'wp_ajax_sureforms_recommended_plugin_install', 'wp_ajax_install_plugin' );
		add_action( 'init', [ $this, 'localize_script_integration' ] );
	}

	/**
	 * Required Plugin Activate
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function required_plugin_activate() {

		$response_data = array( 'message' => $this->get_error_msg( 'permission' ) );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( $response_data );
		}

		if ( empty( $_POST ) ) {
			$response_data = array( 'message' => $this->get_error_msg( 'invalid' ) );
			wp_send_json_error( $response_data );
		}

		/**
		 * Nonce verification.
		 */
		if ( ! check_ajax_referer( 'sf_plugin_manager_nonce', 'security', false ) ) {
			$response_data = array( 'message' => $this->get_error_msg( 'nonce' ) );
			wp_send_json_error( $response_data );
		}

		if ( ! current_user_can( 'install_plugins' ) || ! isset( $_POST['init'] ) || ! sanitize_text_field( wp_unslash( $_POST['init'] ) ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => __( 'No plugin specified', 'sureforms' ),
				)
			);
		}

		$plugin_init = ( isset( $_POST['init'] ) ) ? sanitize_text_field( wp_unslash( $_POST['init'] ) ) : '';

		$activate = activate_plugin( $plugin_init, '', false, true );

		if ( is_wp_error( $activate ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => $activate->get_error_message(),
				)
			);
		}

		wp_send_json_success(
			array(
				'success' => true,
				'message' => __( 'Plugin Successfully Activated', 'sureforms' ),
			)
		);
	}

	/**
	 * Get ajax error message.
	 *
	 * @param string $type Message type.
	 * @return string
	 * @since 1.0.0
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
	 * @return void
	 * @since 1.0.0
	 */
	public function localize_script_integration() {
		$asset_handle      = 'dashboard';
		$script_asset_path = SUREFORMS_DIR . 'assets/build/' . $asset_handle . '.asset.php';
			$script_info   = file_exists( $script_asset_path )
			? include $script_asset_path
			: [
				'dependencies' => [],
				'version'      => SUREFORMS_VER,
			];
			wp_enqueue_script( 'sureforms-integration', SUREFORMS_URL . 'assets/build/' . $asset_handle . '.js', $script_info['dependencies'], SUREFORMS_VER, true );
			wp_localize_script(
				'sureforms-integration',
				'sf_admin',
				array(
					'ajax_url'               => admin_url( 'admin-ajax.php' ),
					'sfPluginManagerNonce'   => wp_create_nonce( 'sf_plugin_manager_nonce' ),
					'plugin_installer_nonce' => wp_create_nonce( 'updates' ),
					'plugin_activating_text' => __( 'Activating...', 'sureforms' ),
					'plugin_activated_text'  => __( 'Activated', 'sureforms' ),
					'plugin_activate_text'   => __( 'Activate', 'sureforms' ),
					'integrations'           => self::sureforms_get_integration(),
					'plugin_installing_text' => __( 'Installing...', 'sureforms' ),
					'plugin_installed_text'  => __( 'Installed', 'sureforms' ),
				)
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
			'sureforms_integrated_plugins',
			array(
				array(
					'title'       => __( 'Spectra', 'sureforms' ),
					'subtitle'    => __( 'Free WordPress Page Builder Plugin.', 'sureforms' ),
					'isPro'       => false,
					'status'      => self::get_plugin_status( 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php' ),
					'slug'        => 'ultimate-addons-for-gutenberg',
					'path'        => 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php',
					'redirection' => admin_url( 'options-general.php?page=spectra' ),
					'logo'        => self::encode_svg( is_string( file_get_contents( plugin_dir_path( SUREFORMS_FILE ) . 'images/spectra.svg' ) ) ? file_get_contents( plugin_dir_path( SUREFORMS_FILE ) . 'images/spectra.svg' ) : '' ),
				),
				array(
					'title'       => __( 'SureCart', 'sureforms' ),
					'subtitle'    => __( 'Simplifying selling online with WordPress.', 'sureforms' ),
					'isPro'       => false,
					'status'      => self::get_plugin_status( 'surecart/surecart.php' ),
					'redirection' => admin_url( 'admin.php?page=' . esc_attr( $surecart_redirection ) ),
					'slug'        => 'surecart',
					'path'        => 'surecart/surecart.php',
					'logo'        => self::encode_svg( is_string( file_get_contents( plugin_dir_path( SUREFORMS_FILE ) . 'images/surecart.svg' ) ) ? file_get_contents( plugin_dir_path( SUREFORMS_FILE ) . 'images/surecart.svg' ) : '' ),
				),
				array(
					'title'       => __( 'SureTriggers', 'sureforms' ),
					'subtitle'    => __( 'Automate your WordPress setup.', 'sureforms' ),
					'status'      => self::get_plugin_status( 'suretriggers/suretriggers.php' ),
					'slug'        => 'suretriggers',
					'path'        => 'suretriggers/suretriggers.php',
					'redirection' => admin_url( 'admin.php?page=suretriggers' ),
					'logo'        => self::encode_svg( is_string( file_get_contents( plugin_dir_path( SUREFORMS_FILE ) . 'images/suretriggers.svg' ) ) ? file_get_contents( plugin_dir_path( SUREFORMS_FILE ) . 'images/suretriggers.svg' ) : '' ),
				),
			)
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
	 * @since 1.0.0
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
}

