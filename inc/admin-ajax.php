<?php
/**
 * Sureforms Admin Ajax Class.
 *
 * Class file for public functions.
 *
 * @package sureforms
 */

namespace SRFM\Inc;

use BSF_UTM_Analytics;
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
		add_action( 'wp_ajax_sureforms_integration', [ $this, 'generate_data_for_suretriggers_integration' ] );
		add_action( 'wp_ajax_srfm_download_export', [ $this, 'download_export_file' ] );
		add_action( 'wp_ajax_srfm_download_logs', [ $this, 'download_client_log' ] );
		add_action( 'wp_ajax_srfm_clear_logs', [ $this, 'clear_client_log' ] );

		add_filter( SRFM_SLUG . '_admin_filter', [ $this, 'localize_script_integration' ] );
	}

	/**
	 * Required Plugin Activate
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function required_plugin_activate() {

		$response_data = [ 'message' => $this->get_error_msg( 'permission' ) ];

		if ( ! Helper::current_user_can() ) {
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

		if ( ! isset( $_POST['init'] ) || ! sanitize_text_field( wp_unslash( $_POST['init'] ) ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'No plugin specified', 'sureforms' ),
				]
			);
		}

		$plugin_init = isset( $_POST['init'] ) ? sanitize_text_field( wp_unslash( $_POST['init'] ) ) : '';

		// Activation runs the plugin's own activation code, so this needs the
		// real capability (a multisite sub-site admin has manage_options but not
		// activate_plugins) and only plugins SureForms itself offers.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( $response_data );
		}

		if ( ! in_array( $plugin_init, self::get_activatable_plugins(), true ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => __( 'This plugin cannot be activated from SureForms.', 'sureforms' ),
				]
			);
		}

		$plugin_slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

		// Not silent: a silent activation skips the plugin's activation hook, so
		// whatever it sets up there never happens. SureMail, for one, creates its
		// email log table on activation; installed from SureForms, its Email Logs
		// screen failed with "Failed to retrieve email logs." Output from the
		// hook is caught by activate_plugin() and returned as an error -- but the
		// plugin is active by then, so that one error is not a failure.
		$activate = activate_plugin( $plugin_init, '', false, false );

		if ( is_wp_error( $activate ) && ! ( 'unexpected_output' === $activate->get_error_code() && is_plugin_active( $plugin_init ) ) ) {
			wp_send_json_error(
				[
					'success' => false,
					'message' => $activate->get_error_message(),
				]
			);
		}

		self::clear_activation_redirect( $plugin_init );

		if ( class_exists( 'BSF_UTM_Analytics' ) && is_callable( 'BSF_UTM_Analytics::update_referer' ) ) {
			$plugin_slug = pathinfo( $plugin_slug, PATHINFO_FILENAME );
			BSF_UTM_Analytics::update_referer( 'sureforms', $plugin_slug );
		}

		wp_send_json_success(
			[
				'success' => true,
				'message' => __( 'Plugin Successfully Activated', 'sureforms' ),
			]
		);
	}

	/**
	 * Plugins the recommended-plugin installer may activate: the ones SureForms
	 * offers (Helper::sureforms_get_integration(), both Starter Templates
	 * editions) and the MCP adapter from Settings.
	 *
	 * @since 2.12.9
	 * @return array<int,string> Plugin files.
	 */
	public static function get_activatable_plugins() {
		$plugins = [ 'mcp-adapter/mcp-adapter.php', 'astra-sites/astra-sites.php', 'astra-pro-sites/astra-pro-sites.php' ];

		foreach ( Helper::sureforms_get_integration() as $integration ) {
			if ( is_array( $integration ) && ! empty( $integration['path'] ) && is_string( $integration['path'] ) ) {
				$plugins[] = $integration['path'];
			}
		}

		/**
		 * Filters the plugins SureForms' installer may activate.
		 *
		 * @since 2.12.9
		 * @param array<int,string> $plugins Plugin files.
		 */
		$plugins = apply_filters( 'srfm_activatable_plugins', $plugins );

		return array_values( array_unique( array_filter( is_array( $plugins ) ? $plugins : [], 'is_string' ) ) );
	}

	/**
	 * Clear the "show my onboarding" flag a plugin sets on activation.
	 *
	 * Activation now runs the plugin's activation hook, and most of these plugins
	 * set a flag there that sends the owner to their onboarding on the next admin
	 * page load -- pulling them out of SureForms' own onboarding mid-step.
	 *
	 * @since 2.12.9
	 * @param string $plugin_init Plugin file that was activated.
	 * @return void
	 */
	public static function clear_activation_redirect( $plugin_init ) {
		/**
		 * Filters the redirect flags cleared after activating a plugin.
		 *
		 * @since 2.12.9
		 * @param array<string,array{option?:string,transient?:string}> $flags Plugin file => flag.
		 */
		$flags = apply_filters(
			'srfm_plugin_activation_redirect_flags',
			[
				'suremails/suremails.php'             => [ 'option' => 'suremails_do_redirect' ],
				'surerank/surerank.php'               => [ 'option' => 'surerank_redirect_on_activation' ],
				'suretriggers/suretriggers.php'       => [ 'transient' => 'st-redirect-after-activation' ],
				'suredonation/suredonation.php'       => [ 'option' => '__suredonation_do_redirect' ],
				'header-footer-elementor/header-footer-elementor.php' => [ 'option' => 'hfe_start_onboarding' ],
				'astra-sites/astra-sites.php'         => [ 'option' => 'st_start_onboarding' ],
				'astra-pro-sites/astra-pro-sites.php' => [ 'option' => 'st_start_onboarding' ],
			]
		);

		$flag = is_array( $flags ) && isset( $flags[ $plugin_init ] ) && is_array( $flags[ $plugin_init ] ) ? $flags[ $plugin_init ] : [];

		if ( ! empty( $flag['option'] ) && is_string( $flag['option'] ) ) {
			delete_option( $flag['option'] );
		}
		if ( ! empty( $flag['transient'] ) && is_string( $flag['transient'] ) ) {
			delete_transient( $flag['transient'] );
		}
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
		$is_screen_sureforms_menu = Helper::validate_request_context( 'sureforms_menu', 'page' );
		return array_merge(
			$values,
			[
				'ajax_url'               => admin_url( 'admin-ajax.php' ),
				'sfPluginManagerNonce'   => wp_create_nonce( 'sf_plugin_manager_nonce' ),
				'plugin_installer_nonce' => wp_create_nonce( 'updates' ),
				'isRTL'                  => is_rtl(),
				'current_screen_id'      => $is_screen_sureforms_menu ? 'sureforms_menu' : '',
				'form_id'                => get_post() ? get_post()->ID : '',
				'suretriggers_nonce'     => wp_create_nonce( 'suretriggers_nonce' ),
			]
		);
	}

	/**
	 * Generates data required for suretriggers integration
	 *
	 * @since 0.0.8
	 * @return void
	 */
	public function generate_data_for_suretriggers_integration() {
		if ( ! Helper::current_user_can() ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to access this page.', 'sureforms' ) ] );
		}

		if ( ! check_ajax_referer( 'suretriggers_nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'sureforms' ) ] );
		}

		if ( empty( $_POST['formId'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Form ID is required.', 'sureforms' ) ] );
		}

		if ( ! Helper::is_suretriggers_ready() ) {
			wp_send_json_error(
				[
					'code'    => 'invalid_secret_key',
					'message' => __( 'OttoKit is not configured properly.', 'sureforms' ),
				]
			);
		}

		$form_id = Helper::get_integer_value( sanitize_text_field( wp_unslash( $_POST['formId'] ) ) );
		$form    = get_post( $form_id );

		if ( is_null( $form ) || SRFM_FORMS_POST_TYPE !== $form->post_type ) {
			wp_send_json_error( [ 'message' => __( 'Invalid form ID.', 'sureforms' ) ] );
		}

		// Translators: %s: Form ID.
		$form_name = ! empty( $form->post_title ) ? $form->post_title : sprintf( __( 'SureForms id: %s', 'sureforms' ), $form_id );
		$api_url   = apply_filters( 'suretriggers_get_iframe_url', SRFM_SURETRIGGERS_INTEGRATION_BASE_URL ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- SureTriggers' own filter; the name must match SureTriggers exactly to integrate.

		// This is the format of data required by SureTriggers for adding iframe in target id.
		$body = [
			'client_id'           => 'SureForms',
			'st_embed_url'        => $api_url,
			'embedded_identifier' => $form_id,
			'target'              => 'suretriggers-iframe-wrapper', // div where we want SureTriggers to add iframe should have this target id.
			'event'               => [
				'label'       => __( 'Form Submitted', 'sureforms' ),
				'value'       => 'sureforms_form_submitted',
				'description' => __( 'Runs when a form is submitted', 'sureforms' ),
			],
			'summary'             => $form_name,
			'selected_options'    => [
				'form_id' => [
					'value' => $form_id,
					'label' => $form_name,
				],
			],
			'integration'         => 'SureForms',
			'sample_response'     => [
				'form_id'   => $form_id,
				'to_emails' => [
					'dev-email@wpengine.local',
				],
				'form_name' => $form_name,
				'data'      => $this->get_form_fields( $form_id ),
			],
		];

		// Adding entry_id in body sample response if do_not_store_entries is not enabled.
		$compliance           = get_post_meta( $form_id, '_srfm_compliance', true );
		$do_not_store_entries = is_array( $compliance ) && isset( $compliance[0]['do_not_store_entries'] )
		? $compliance[0]['do_not_store_entries']
		: null;

		if ( ! $do_not_store_entries ) {
			$body['sample_response']['entry_id'] = 12;
		}

		wp_send_json_success(
			[
				'message' => 'success',
				'data'    => apply_filters( 'srfm_suretriggers_integration_data_filter', $body, $form_id ),
			]
		);
	}

	/**
	 * This function populates data for particular form.
	 *
	 * @param  int $form_id Form ID.
	 * @since 0.0.8
	 * @return array<mixed>
	 */
	public function get_form_fields( $form_id ) {
		if ( empty( $form_id ) || ! is_int( $form_id ) ) {
			return [];
		}

		if ( SRFM_FORMS_POST_TYPE !== get_post_type( $form_id ) ) {
			return [];
		}

		$post = get_post( $form_id );

		if ( is_null( $post ) ) {
			return [];
		}

		$blocks = parse_blocks( $post->post_content );

		$blocks = array_filter(
			$blocks,
			static function( $block ) {
				if ( 'srfm/html' === $block['blockName'] ) {
					return false;
				}
				return true;
			}
		);

		$blocks = array_values( $blocks );

		if ( empty( $blocks ) ) {
			return [];
		}

		$data = [];

		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) && 0 === strpos( $block['blockName'], 'srfm/' ) ) {

				/**
				 * Determine whether to skip this field from the sample data.
				 *
				 * @param bool  $should_skip           Default value indicating if field should be skipped.
				 * @param array $block_details         Array containing block attributes, including 'block_name'.
				 *
				 * @since 2.0.0
				 *
				 * @hook srfm_should_skip_field_from_sample_data
				 */
				$should_skip_this_field = apply_filters(
					'srfm_should_skip_field_from_sample_data',
					false,
					[
						'block_name' => $block['blockName'],
					]
				);

				if ( $should_skip_this_field ) {
					continue;
				}

				if ( ! empty( $block['attrs']['slug'] ) ) {
					$data[ $block['attrs']['slug'] ] = $this->get_sample_data( $block['blockName'] );
				}
			}
		}

		if ( empty( $data ) ) {
			return [];
		}

		return $data;
	}

	/**
	 * Returns sample data for a block.
	 *
	 * @param  string $block_name Block name.
	 * @since 0.0.8
	 * @return mixed
	 */
	public function get_sample_data( $block_name ) {
		if ( empty( $block_name ) ) {
			return __( 'Sample data', 'sureforms' );
		}

		$dummy_data = [
			'srfm/input'            => __( 'Sample input data', 'sureforms' ),
			'srfm/email'            => 'noreply@sureforms.com',
			'srfm/textarea'         => __( 'Sample textarea data', 'sureforms' ),
			'srfm/number'           => 123,
			'srfm/checkbox'         => 'checkbox value',
			'srfm/gdpr'             => 'GDPR value',
			'srfm/phone'            => '1234567890',
			'srfm/address'          => __( 'Address data', 'sureforms' ),
			'srfm/address-compact'  => __( 'Address data', 'sureforms' ),
			'srfm/dropdown'         => __( 'Selected dropdown option', 'sureforms' ),
			'srfm/multi-choice'     => __( 'Selected Multichoice option', 'sureforms' ),
			'srfm/radio'            => __( 'Selected radio option', 'sureforms' ),
			'srfm/submit'           => __( 'Submit', 'sureforms' ),
			'srfm/url'              => 'https://example.com',
			'srfm/date-time-picker' => '2022-01-01 12:00:00',
			'srfm/hidden'           => __( 'Hidden Value', 'sureforms' ),
			'srfm/slider'           => 50,
			'srfm/password'         => 'DummyPassword123',
			'srfm/rating'           => 4,
			'srfm/upload'           => 'https://example.com/uploads/file.pdf',
		];

		/**
		 * Filter the sample data for specific block types.
		 *
		 * Allows plugins and themes to add custom sample data for their block types
		 * or modify existing sample data. This is particularly useful for dynamic
		 * block types that require complex sample data structures.
		 *
		 * @since 0.0.8
		 *
		 * @param array $dummy_data {
		 *     Array of sample data keyed by block name.
		 *
		 *     @type string|array $block_name Sample data for the block.
		 * }
		 * @param array $filter_args {
		 *     Additional filter arguments.
		 *
		 *     @type string $block_name The name of the block being processed.
		 * }
		 */
		$dummy_data = Helper::apply_filters_as_array( 'srfm_sample_data_filter', $dummy_data, [ 'block_name' => $block_name ] );

		if ( ! empty( $dummy_data[ $block_name ] ) ) {
			return $dummy_data[ $block_name ];
		}
			return __( 'Sample data', 'sureforms' );
	}

	/**
	 * Download exported file.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function download_export_file() {
		// Check user permissions.
		if ( ! Helper::current_user_can() ) {
			wp_die( esc_html__( 'You do not have permission to access this file.', 'sureforms' ) );
		}

		// Verify nonce for security.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'srfm_download_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'sureforms' ) );
		}

		// Get and sanitize the file parameter.
		$file = isset( $_GET['file'] ) ? sanitize_file_name( wp_unslash( $_GET['file'] ) ) : '';

		if ( empty( $file ) ) {
			wp_die( esc_html__( 'Invalid file request.', 'sureforms' ) );
		}

		// Build the full file path.
		$temp_dir = wp_normalize_path( trailingslashit( get_temp_dir() ) );
		$filepath = $temp_dir . $file;

		// Security check: ensure the file is in the temp directory.
		if ( strpos( wp_normalize_path( $filepath ), $temp_dir ) !== 0 ) {
			wp_die( esc_html__( 'Invalid file path.', 'sureforms' ) );
		}

		// Check if file exists.
		if ( ! file_exists( $filepath ) ) {
			wp_die( esc_html__( 'File not found.', 'sureforms' ) );
		}

		// Get file info.
		$file_size = filesize( $filepath );
		$file_info = pathinfo( $filepath );

		// Determine content type and filename based on file extension.
		$content_type = 'application/octet-stream';
		$filename     = $file_info['basename'];
		if ( isset( $file_info['extension'] ) ) {
			if ( 'csv' === $file_info['extension'] ) {
				$content_type = 'text/csv';
			} elseif ( 'zip' === $file_info['extension'] ) {
				$content_type = 'application/zip';
				/**
				 * Filter the user-facing filename used when serving an exported ZIP archive.
				 *
				 * @since 2.9.0
				 *
				 * @param string             $filename  Default ZIP filename.
				 * @param array<string,mixed> $file_info pathinfo() result for the file being served.
				 */
				$filename = (string) apply_filters( 'srfm_export_zip_filename', 'SureForms Entries.zip', $file_info );
			}
		}

		// Set headers for download.
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . $file_size );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		header( 'Pragma: public' );

		// Clear output buffers.
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		// Output the file.
		readfile( $filepath ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile, WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Direct file output is required to stream the download.

		// Clean up the temporary file.
		wp_delete_file( $filepath );

		exit;
	}
	/**
	 * Stream the client debug log to an administrator.
	 *
	 * Takes no filename parameter. There is exactly one log file and the server
	 * derives its path, which removes the path-traversal question entirely rather
	 * than guarding against it -- and keeps the unguessable file name, which is
	 * what actually protects the log on nginx, out of the page.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public function download_client_log() {
		$this->verify_log_request();

		$path    = Client_Logger::get_log_path( false );
		$has_log = '' !== $path && file_exists( $path );

		// The buttons are always offered while logging is on, so downloading before
		// anything has failed is a normal thing to do. Hand back an explanatory file
		// rather than a wp_die() screen -- an empty log is the good outcome.
		if ( ! $has_log ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
			header( 'X-Content-Type-Options: nosniff' );
			header( 'Content-Disposition: attachment; filename="sureforms-debug-log.txt"' );

			if ( ob_get_level() ) {
				ob_end_clean();
			}

			echo esc_html__( 'No form submission failures have been recorded.', 'sureforms' );
			exit;
		}

		$size = filesize( $path );

		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Disposition: attachment; filename="sureforms-debug-log.txt"' );

		if ( is_int( $size ) ) {
			header( 'Content-Length: ' . $size );
		}

		header( 'Cache-Control: private, max-age=0, must-revalidate' );

		if ( ob_get_level() ) {
			ob_end_clean();
		}

		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile, WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Direct file output is required to stream the download.
		exit;
	}

	/**
	 * Delete the client debug log.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public function clear_client_log() {
		$this->verify_log_request();

		Client_Logger::clear();

		wp_send_json_success();
	}

	/**
	 * Capability and nonce gate shared by both log actions.
	 *
	 * Capability first, ahead of the nonce, matching the ordering of the sibling
	 * handlers in this class.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	private function verify_log_request() {
		if ( ! Helper::current_user_can() ) {
			wp_die( esc_html__( 'You do not have permission to access this file.', 'sureforms' ) );
		}

		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'srfm_client_logs' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'sureforms' ) );
		}
	}

}
