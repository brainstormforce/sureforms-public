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

	/**
	 * Resend email notifications for specified entry IDs and log the results.
	 *
	 * This method checks user permissions, validates input, verifies a nonce, and processes the
	 * resending of email notifications for specified entries. It logs successes and failures for each
	 * notification sent, returning a summary of the results.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function resend_email_notifications() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( $this->resend_email_notifications_alert( $this->get_error_msg( 'permission' ), false ) );
		}

		if ( empty( $_POST['entry_ids'] ) && empty( $_POST['form_id'] ) && empty( $_POST['email_notification'] ) ) {
			wp_send_json_error( $this->resend_email_notifications_alert( $this->get_error_msg( 'invalid' ), false ) );
		}

		/**
		 * Nonce verification.
		 */
		if ( ! check_ajax_referer( '_srfm_resend_email_notifications_nonce', 'security' ) ) {
			wp_send_json_error( $this->resend_email_notifications_alert( $this->get_error_msg( 'nonce' ), false ) );
		}

		$entry_ids = sanitize_text_field( wp_unslash( $_POST['entry_ids'] ) );
		$entry_ids = array_map( 'absint', explode( ',', $entry_ids ) );

		if ( empty( $entry_ids ) ) {
			wp_send_json_error( $this->resend_email_notifications_alert( __( 'Entry IDs cannot be empty.', 'sureforms' ), false ) );
		}

		$recipient = '';
		$send_to   = ! empty( $_POST['send_to'] ) ? sanitize_text_field( wp_unslash( $_POST['send_to'] ) ) : 'default';

		if ( 'other' === $send_to ) {
			$recipient = ! empty( $_POST['recipient'] ) ? sanitize_email( wp_unslash( $_POST['recipient'] ) ) : '';

			if ( ! is_email( $recipient ) ) {
				// Bail if not a valid email address.
				wp_send_json_error( $this->resend_email_notifications_alert( __( 'You must provide a valid email for the recipient.', 'sureforms' ), false ) );
			}
		}

		$form_id               = absint( wp_unslash( $_POST['form_id'] ) );
		$email_notification_id = absint( wp_unslash( $_POST['email_notification'] ) );

		$email_notification = Helper::get_array_value( get_post_meta( $form_id, '_srfm_email_notification', true ) );

		$display_name = wp_get_current_user()->display_name;

		if ( ! empty( $email_notification ) && is_array( $email_notification ) ) {
			foreach ( $email_notification as $notification ) {
				if ( absint( $notification['id'] ) !== $email_notification_id ) {
					continue;
				}

				if ( true !== $notification['status'] ) {
					// It means email notification is toggled off and we can ignore and move to next step.
					continue;
				}

				foreach ( $entry_ids as $entry_id ) {
					// We don't want the same instance here, instead we need to init new object for each entry id here.
					$entries_db = new Entries();

					/* translators: Here %1$s is email notification label and %2$s is the user display name. */
					$log_key   = $entries_db->add_log( sprintf( __( 'Resend email notification "%1$s" initiated by %2$s', 'sureforms' ), esc_html( $notification['name'] ), esc_html( $display_name ) ) );
					$form_data = Helper::get_array_value( $entries_db::get( $entry_id )['form_data'] );
					$parsed    = Form_Submit::parse_email_notification_template( $form_data, $notification );

					// If user has provided recipient then reroute email to user provided recipient.
					$email_to = $recipient ? $recipient : $parsed['to'];
					$sent     = wp_mail( $email_to, $parsed['subject'], $parsed['message'], $parsed['headers'] );

					/* translators: Here, %s is email address. */
					$log_message = $sent ? sprintf( __( 'Email notification sent to %s', 'sureforms' ), esc_html( $email_to ) ) : sprintf( __( 'Failed sending email notification to %s', 'sureforms' ) );

					if ( is_int( $log_key ) ) {
						$entries_db->update_log( $log_key, null, [ $log_message ] );
					}

					$entries_db::update(
						$entry_id,
						[
							'logs' => $entries_db->get_logs(),
						]
					);
				}
			}
		}

		wp_send_json_success( $this->resend_email_notifications_alert( __( 'The notification was sent successfully to the recipients.', 'sureforms' ) ) );
	}

	/**
	 * Save notes for a specified entry and return the updated notes.
	 *
	 * This method checks user permissions, validates input, verifies a nonce,
	 * and saves the provided note for the specified entry. It then retrieves
	 * the updated list of notes and sends it back in the response.
	 *
	 * @since x.x.x
	 * @return void
	 */
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

		$entry_id = absint( wp_unslash( $_POST['entryID'] ) );

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

		$plugin_init = isset( $_POST['init'] ) ? sanitize_text_field( wp_unslash( $_POST['init'] ) ) : '';

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
		}
		if ( is_plugin_active( $plugin_init_file ) ) {
			return 'Activated';
		}
			return 'Installed';
	}

	/**
	 * Generates data required for suretriggers integration
	 *
	 * @since 0.0.8
	 * @return void
	 */
	public function generate_data_for_suretriggers_integration() {
		if ( ! current_user_can( 'manage_options' ) ) {
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
					'message' => __( 'SureTriggers is not configured properly.', 'sureforms' ),
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
		$api_url   = apply_filters( 'suretriggers_get_iframe_url', SRFM_SURETRIGGERS_INTEGRATION_BASE_URL );

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

		if ( ! empty( $dummy_data[ $block_name ] ) ) {
			return $dummy_data[ $block_name ];
		}
			return __( 'Sample data', 'sureforms' );
	}

	/**
	 * Returns resend email notification alert content.
	 *
	 * @param string  $message Message to print in the alert box.
	 * @param bool $is_success Whether or not is resend email succeed.
	 * @return string Email notification alert box html content with message.
	 */
	protected function resend_email_notifications_alert( $message, $is_success = true ) {
		ob_start();
		?>
		<div class="srfm-resend-notification-message">
			<div class="icon">
				<?php
				if ( $is_success ) {
					?>
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<g clip-path="url(#clip0_16958_8537)">
						<path d="M10.0003 18.3307C14.6028 18.3307 18.3337 14.5999 18.3337 9.9974C18.3337 5.3949 14.6028 1.66406 10.0003 1.66406C5.39783 1.66406 1.66699 5.3949 1.66699 9.9974C1.66699 14.5999 5.39783 18.3307 10.0003 18.3307Z" stroke="#16A34A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M7.5 10.0026L9.16667 11.6693L12.5 8.33594" stroke="#16A34A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</g>
						<defs>
						<clipPath id="clip0_16958_8537">
						<rect width="20" height="20" fill="white"/>
						</clipPath>
						</defs>
					</svg>
					<?php
				} else {
					?>
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M12.0003 8.9975V12.7475M2.69702 16.1231C1.83163 17.6231 2.9142 19.4975 4.64593 19.4975H19.3546C21.0863 19.4975 22.1689 17.6231 21.3035 16.1231L13.9492 3.37562C13.0833 1.87479 10.9172 1.87479 10.0513 3.37562L2.69702 16.1231ZM12.0003 15.7475H12.0078V15.755H12.0003V15.7475Z" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<?php
				}
				?>
			</div>
			<div class="message">
				<?php
				echo $is_success ? '<h4>' . esc_html__( 'Notification Sent', 'sureforms' ) . '</h4>' : '<h4>' . esc_html__( 'Error', 'sureforms' ) . '</h4>';
				echo '<p>' . esc_html( $message ) . '</p>';
				?>
			</div>
			<button type="button" class="close">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M14 6L6 14" stroke="#6B7280" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M6 6L14 14" stroke="#6B7280" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		</div>
		<?php
		$content = ob_get_clean();
		return is_string( $content ) ? $content : '';
	}
}
