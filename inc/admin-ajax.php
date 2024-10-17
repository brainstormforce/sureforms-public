<?php
/**
 * Sureforms Admin Ajax Class.
 *
 * Class file for public functions.
 *
 * @package sureforms
 */

namespace SRFM\Inc;

use SRFM\Admin\Views\Single_Entry;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Helper;

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
		add_action( 'wp_ajax_sureforms_resend_email_notifications', [ $this, 'resend_email_notifications' ] );
		add_action( 'wp_ajax_sureforms_recommended_plugin_activate', [ $this, 'required_plugin_activate' ] );
		add_action( 'wp_ajax_sureforms_recommended_plugin_install', 'wp_ajax_install_plugin' );
		add_action( 'wp_ajax_sureforms_integration', [ $this, 'generate_data_for_suretriggers_integration' ] );

		add_action( 'wp_ajax_sureforms_save_entry_notes', [ $this, 'save_entry_notes' ] );

		add_filter( SRFM_SLUG . '_admin_filter', [ $this, 'localize_script_integration' ] );
	}

	public function resend_email_notifications() {
		$response_data = [ 'message' => $this->get_error_msg( 'permission' ) ];

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( $response_data );
		}

		if ( empty( $_POST['entry_ids'] ) && empty( $_POST['form_id'] ) && empty( $_POST['email_notification'] ) ) {
			$response_data = [ 'message' => $this->get_error_msg( 'invalid' ) ];
			wp_send_json_error( $response_data );
		}

		/**
		 * Nonce verification.
		 */
		if ( ! check_ajax_referer( '_srfm_resend_email_notifications_nonce', 'security' ) ) {
			$response_data = [ 'message' => $this->get_error_msg( 'nonce' ) ];
			wp_send_json_error( $response_data );
		}

		$entry_ids = sanitize_text_field( wp_unslash( $_POST['entry_ids'] ) );
		$entry_ids = array_map( 'absint', explode( ',', $entry_ids ) );

		if ( empty( $entry_ids ) ) {
			wp_send_json_error();
		}

		$recipient = '';
		$send_to   = ! empty( $_POST['send_to'] ) ? sanitize_text_field( wp_unslash( $_POST['send_to'] ) ) : 'default';

		if ( 'other' === $send_to ) {
			$recipient = ! empty( $_POST['recipient'] ) ? sanitize_email( wp_unslash( $_POST['recipient'] ) ) : '';

			if ( ! is_email( $recipient ) ) {
				wp_send_json_error();
			}
		}

		$form_id               = absint( wp_unslash( $_POST['form_id'] ) );
		$email_notification_id = absint( wp_unslash( $_POST['email_notification'] ) );

		$email_notification = get_post_meta( $form_id, '_srfm_email_notification', true );

		$display_name = wp_get_current_user()->display_name;

		if ( ! empty( $email_notification ) && is_array( $email_notification ) ) {
			foreach ( $email_notification as $notification ) {
				if ( $email_notification_id !== absint( $notification['id'] ) ) {
					continue;
				}

				if ( true !== $notification['status'] ) {
					continue;
				}

				foreach ( $entry_ids as $entry_id ) {
					$entries_db = new Entries(); // We don't want the same instance here, instead we need to init new object for each entry id here.
					$log_key    = $entries_db->add_log( sprintf( __( 'Resend email notification "%1$s" initiated by %2$s', 'sureforms' ), esc_html( $notification['name'] ), esc_html( $display_name ) ) );
					$form_data  = $entries_db::get( $entry_id )['form_data'];
					$parsed     = Form_Submit::parse_email_notification_template( $form_data, $notification );
					$sent       = wp_mail(
						$recipient ? $recipient : $parsed['to'], // If user has provided recipient then reroute email to user provided recipient.
						$parsed['subject'],
						$parsed['message'],
						$parsed['headers']
					);

					if ( is_int( $log_key ) ) {
						$entries_db->update_log(
							$log_key,
							null,
							[
								/* translators: Here, %s is email address. */
								$sent ? sprintf( __( 'Email notification sent to %s', 'sureforms' ), esc_html( $parsed['to'] ) ) : sprintf( __( 'Failed sending email notification to %s', 'sureforms' ) ),
							]
						);
					}

					$entries_db::update(
						$entry_id,
						[
							'logs' => $entries_db->get_logs()
						]
					);
				}
			}
		}

		wp_send_json_success();
	}

	public function save_entry_notes() {
		$response_data = [ 'message' => $this->get_error_msg( 'permission' ) ];

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( $response_data );
		}

		if ( empty( $_POST['note'] ) && empty( $_POST['entryID'] ) ) {
			$response_data = [ 'message' => $this->get_error_msg( 'invalid' ) ];
			wp_send_json_error( $response_data );
		}

		/**
		 * Nonce verification.
		 */
		if ( ! check_ajax_referer( '_srfm_entry_notes_nonce', 'security' ) ) {
			$response_data = [ 'message' => $this->get_error_msg( 'nonce' ) ];
			wp_send_json_error( $response_data );
		}

		$entry_id = sanitize_text_field( wp_unslash( $_POST['entryID'] ) );

		Entries::add_note( $entry_id, sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) );

		ob_start();
		$notes = Entries::get( $entry_id )['notes'];
		if ( ! empty( $notes ) && is_array( $notes ) ) {
			foreach ( $notes as $note ) {
				Single_Entry::entry_note_item_markup( $note );
			}
		}
		$data = ob_get_clean();

		wp_send_json_success( $data );
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
				'suretriggers_nonce'     => wp_create_nonce( 'suretriggers_nonce' ),
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
		$suretrigger_connected = apply_filters( 'suretriggers_is_user_connected', '' );
		return apply_filters(
			'srfm_integrated_plugins',
			[
				[
					'title'       => __( 'SureTriggers', 'sureforms' ),
					'subtitle'    => __( 'Connect SureForms to hundreds of apps, CRMs and tools such as Slack, Mailchimp, etc.', 'sureforms' ),
					'description' => __( 'SureTriggers is a powerful automation platform that helps you connect your various plugins and apps together. It allows you to automate repetitive tasks, so you can focus on more important work.', 'sureforms' ),
					'status'      => self::get_plugin_status( 'suretriggers/suretriggers.php' ),
					'slug'        => 'suretriggers',
					'path'        => 'suretriggers/suretriggers.php',
					'redirection' => admin_url( 'admin.php?page=suretriggers' ),
					'logo'        => self::encode_svg( is_string( file_get_contents( plugin_dir_path( SRFM_FILE ) . 'images/suretriggers.svg' ) ) ? file_get_contents( plugin_dir_path( SRFM_FILE ) . 'images/suretriggers.svg' ) : '' ),
					'logo_full'   => self::encode_svg( is_string( file_get_contents( plugin_dir_path( SRFM_FILE ) . 'images/suretriggers_full.svg' ) ) ? file_get_contents( plugin_dir_path( SRFM_FILE ) . 'images/suretriggers_full.svg' ) : '' ),
					'connected'   => $suretrigger_connected,
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
	 * @param  string $plugin_init_file Plugin init file.
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

	/**
	 * Generates data required for suretriggers integration
	 *
	 * @since 0.0.8
	 * @return void
	 */
	public function generate_data_for_suretriggers_integration() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'You do not have permission to access this page.' ] );
		}

		if ( ! check_ajax_referer( 'suretriggers_nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => 'Invalid nonce.' ] );
		}

		if ( empty( $_POST['formId'] ) ) {
			wp_send_json_error( [ 'message' => 'Form ID is required.' ] );
		}

		$suretriggers_data = get_option( 'suretrigger_options', [] );
		if ( ! is_array( $suretriggers_data ) || empty( $suretriggers_data['secret_key'] ) || ! is_string( $suretriggers_data['secret_key'] ) ) {
			wp_send_json_error(
				[
					'code'    => 'invalid_secret_key',
					'message' => 'SureTriggers is not configured properly.',
				]
			);
		}

		$form_id = Helper::get_integer_value( sanitize_text_field( wp_unslash( $_POST['formId'] ) ) );
		$form    = get_post( $form_id );

		if ( is_null( $form ) || SRFM_FORMS_POST_TYPE !== $form->post_type ) {
			wp_send_json_error( [ 'message' => __( 'Invalid form ID.', 'sureforms' ) ] );
		}

		$form_name = ! empty( $form->post_title ) ? $form->post_title : 'SureForms id: ' . $form_id;
		$api_url   = apply_filters( 'suretriggers_get_iframe_url', SRFM_SURETRIGGERS_INTERGATION_BASE_URL );

		// This is the format of data required by SureTriggers for adding iframe in target id.
		$body = [
			'client_id'           => 'SureForms',
			'st_embed_url'        => $api_url,
			'embedded_identifier' => $form_id,
			'target'              => 'suretriggers-iframe-wrapper', // div where we want SureTriggers to add iframe should have this target id.
			'event'               => [
				'label'       => 'Form Submitted',
				'value'       => 'sureforms_form_submitted',
				'description' => 'Runs when a form is submitted',
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

		wp_send_json_success(
			[
				'message' => 'success',
				'data'    => $body,
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

		if ( empty( $blocks ) ) {
			return [];
		}

		$data = [];

		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) && 0 === strpos( $block['blockName'], 'srfm/' ) ) {
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
			return 'Sample data';
		}

		$dummy_data = [
			'srfm/input'            => 'Sample input data',
			'srfm/email'            => 'noreply@sureforms.com',
			'srfm/textarea'         => 'Sample textarea data',
			'srfm/number'           => 123,
			'srfm/checkbox'         => 'checkbox value',
			'srfm/gdpr'             => 'GDPR value',
			'srfm/phone'            => '1234567890',
			'srfm/address'          => 'Address data',
			'srfm/address-compact'  => 'Address data',
			'srfm/dropdown'         => 'Selected dropdown option',
			'srfm/multi-choice'     => 'Selected Multichoice option',
			'srfm/radio'            => 'Selected radio option',
			'srfm/submit'           => 'Submit',
			'srfm/url'              => 'https://example.com',
			'srfm/date-time-picker' => '2022-01-01 12:00:00',
			'srfm/hidden'           => 'Hidden Value',
			'srfm/slider'           => 50,
			'srfm/password'         => 'DummyPassword123',
			'srfm/rating'           => 4,
			'srfm/upload'           => 'https://example.com/uploads/file.pdf',
		];

		if ( ! empty( $dummy_data[ $block_name ] ) ) {
			return $dummy_data[ $block_name ];
		} else {
			return 'Sample data';
		}
	}
}

