<?php
/**
 * Create new Form with Template and return the form ID.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WP_Post_Type;
use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Create New Form.
 *
 * @since 0.0.1
 */
class Create_New_Form {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
	}

	/**
	 * Add custom API Route create-new-form.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/create-new-form',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_form' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			]
		);
	}

	/**
	 * Checks whether a given request has permission to create new forms.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 * @since 0.0.1
	 */
	public function get_items_permissions_check( $request ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}
		foreach ( get_post_types( [ 'show_in_rest' => true ], 'objects' ) as $post_type ) {
			/**
			 * The post type.
			 *
			 * @var WP_Post_Type $post_type
			 */
			if ( current_user_can( $post_type->cap->edit_posts ) ) {
				return true;
			}
		}

		return new \WP_Error(
			'rest_cannot_view',
			__( 'Sorry, you are not allowed to create forms.', 'sureforms' ),
			[ 'status' => \rest_authorization_required_code() ]
		);
	}

	/**
	 * Get default post metas for form when creating using template.
	 *
	 * @since 0.0.2
	 * @return array<mixed>
	 */
	public static function get_default_meta_keys() {
		return [
			'_srfm_submit_button_text'       => 'Submit',
			'_srfm_use_label_as_placeholder' => false,
			'_srfm_is_inline_button'         => false,
			'_srfm_single_page_form_title'   => true,
			'_srfm_instant_form'             => false,
			'_srfm_form_container_width'     => 650,
			'_srfm_bg_type'                  => 'image',
			'_srfm_bg_image'                 => '',
			'_srfm_cover_image'              => '',
			'_srfm_bg_color'                 => '#ffffff',
			'_srfm_submit_width_backend'     => 'max-content',
			'_srfm_submit_alignment'         => 'left',
			'_srfm_submit_alignment_backend' => '100%',
			'_srfm_submit_width'             => '',
			'_srfm_inherit_theme_button'     => false,
			'_srfm_additional_classes'       => '',
			'_srfm_submit_type'              => 'message',
			'_srfm_form_recaptcha'           => 'none',
			'_srfm_button_border_radius'     => 0,
			'_srfm_captcha_security_type'    => '',

			// Instant Form Settings.
			'_srfm_instant_form_settings'    =>
				[
					'bg_type'                       => 'color',
					'bg_color'                      => '#ffffff',
					'bg_image'                      => '',
					'site_logo'                     => '',
					'cover_type'                    => 'color',
					'cover_color'                   => '#0C78FB',
					'cover_image'                   => '',
					'enable_instant_form'           => false,
					'form_container_width'          => 620,
					'single_page_form_title'        => true,
					'use_banner_as_page_background' => false,
				],

			// Form Styling Settings.
			'_srfm_forms_styling'            =>
				[
					'primary_color'           => '#0C78FB',
					'text_color'              => '#1E1E1E',
					'text_color_on_primary'   => '#FFFFFF',
					'field_spacing'           => 'medium',
					'submit_button_alignment' => 'left',
				],

			// Email Notification.
			'_srfm_email_notification'       =>
				[
					'id'             => 1,
					'status'         => true,
					'is_raw_format'  => false,
					'name'           => 'Admin Notification Email',
					'email_to'       => '{admin_email}',
					'email_reply_to' => '{admin_email}',
					'email_cc'       => '{admin_email}',
					'email_bcc'      => '{admin_email}',
					'subject'        => 'New Form Submission',
					'email_body'     => '{all_data}',
				],

			// Compliance Settings.
			'_srfm_compliance'               =>
				[
					'id'                   => 'gdpr',
					'gdpr'                 => false,
					'do_not_store_entries' => false,
					'auto_delete_entries'  => false,
					'auto_delete_days'     => '',
				],

			// Form Confirmation.
			'_srfm_form_confirmation'        =>
				[
					'id'                => 1,
					'confirmation_type' => 'same page',
					'page_url'          => '',
					'custom_url'        => '',
					'message'           => '<p style="text-align: center;"><img src=""></img></p><h2 style="text-align: center;">Thank you</h2><p style="text-align: center;">We have received your email. You\'ll hear from us as soon as possible.</p><p style="text-align: center;">Please be sure to whitelist our {admin_email} email address to ensure our replies reach your inbox safely.</p>',
					'submission_action' => 'hide form',
				],

			'_srfm_page_break_settings'      =>
				[
					'is_page_break'           => false,
					'first_page_label'        => 'Page Break Label',
					'progress_indicator_type' => 'connector',
					'toggle_label'            => false,
					'next_button_text'        => 'Next',
					'back_button_text'        => 'Back',
				],
		];
	}

	/**
	 * Create new form post from selected template.
	 *
	 * @param \WP_REST_Request $data Form Markup Data.
	 *
	 * @return WP_Error|WP_REST_Response
	 * @since 0.0.1
	 */
	public static function create_form( $data ) {
		$nonce = Helper::get_string_value( $data->get_header( 'X-WP-Nonce' ) );
		$nonce = sanitize_text_field( $nonce );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Nonce verification failed.', 'sureforms' ),
				]
			);
		}

		$form_info     = $data->get_body();
		$form_info_obj = json_decode( $form_info );

		// Check if JSON decoding was successful and $form_info_obj is an object.
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_object( $form_info_obj ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Invalid JSON format.', 'sureforms' ),
				]
			);
		}

		$required_properties = [ 'template_name', 'form_data' ];

		// Check if required properties exist in the $form_info_obj.
		foreach ( $required_properties as $property ) {
			if ( ! property_exists( $form_info_obj, $property ) ) {
				wp_send_json_error(
					[
						'message' => __( 'Missing required properties in form info.', 'sureforms' ),
					]
				);
			}
		}

		$title          = isset( $form_info_obj->template_name ) ? $form_info_obj->template_name : '';
		$content        = isset( $form_info_obj->form_data ) ? $form_info_obj->form_data : '';
		$template_metas = isset( $form_info_obj->template_metas ) ? (array) $form_info_obj->template_metas[0] : [];

		$post_id = wp_insert_post(
			[
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'sureforms_form',
			]
		);

		// if post id is empty then return error.
		if ( empty( $post_id ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Error creating SureForms Form, ', 'sureforms' ),
				]
			);
		}

		if ( ! empty( $template_metas ) && is_array( $template_metas ) ) {
			// Get default post metas.
			$default_post_metas = self::get_default_meta_keys();

			if ( ! is_array( $default_post_metas ) ) {
				return new WP_Error(
					'error',
					__( 'Error creating SureForms got invalid default post meta type, ', 'sureforms' )
				);
			}

			foreach ( $template_metas as $meta_key => $value ) {

				$meta_value = '';

				if ( empty( $value ) || ! is_array( $value ) ) {
					continue;
				}

				switch ( $meta_key ) {
					case '_srfm_instant_form_settings':
					case '_srfm_forms_styling':
						$meta_value = array_merge( $default_post_metas[ $meta_key ], (array) $value[0] );
						break;

					case '_srfm_form_confirmation':
						$meta_value               = [
							array_merge( $default_post_metas[ $meta_key ], (array) $value[0] ),
						];
						$check_icon               = 'data:image/svg+xml;base64,' . base64_encode( strval( file_get_contents( plugin_dir_path( SRFM_FILE ) . 'images/check-icon.svg' ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
						$meta_value[0]['message'] = '<p style="text-align: center;"><img src="' . $check_icon . '"></img></p><h2 style="text-align: center;">Thank you</h2><p style="text-align: center;">' . $meta_value[0]['message'] . '</p><p style="text-align: center;">Please be sure to whitelist our {admin_email} email address to ensure our replies reach your inbox safely.</p>';
						break;

					case '_srfm_email_notification':
						$meta_value                  = [
							array_merge( $default_post_metas[ $meta_key ], (array) $value[0] ),
						];
						$meta_value[0]['email_body'] = '<p style="text-align: center;">' . $meta_value[0]['email_body'] . '</p><p style="text-align: center;">{all_data}</p>';
						break;

					case '_srfm_compliance':
						$meta_value = [
							array_merge( $default_post_metas[ $meta_key ], (array) $value[0] ),
						];
						break;

					case '_srfm_page_break_settings':
						// Add logic if necessary will be added after testing
						break;

					default:
						$meta_value = $value;
						break;
				}

					// pass meta value to function which checks if the the values inside it are of correct type according to the default meta keys. and check if any key is empty then set it to default value.
					// TODO: I will uncomment this line after initial testing.
					// $meta_value = self::validate_meta_values( $default_post_metas[ $meta_key ], $meta_value );

					add_post_meta( $post_id, $meta_key, $meta_value );

			}
		}

		return new WP_REST_Response(
			[
				'message' => __( 'SureForms Form created successfully.', 'sureforms' ),
				'id'      => $post_id,
			]
		);

	}

	/**
	 * Validate meta values.
	 *
	 * @param array<mixed>              $default_meta_keys Default meta keys.
	 * @param array<mixed>|string|mixed $meta_values Meta values.
	 *
	 * @return array<mixed>|string|mixed
	 * @since 0.0.2
	 */
	public static function validate_meta_values( $default_meta_keys, $meta_values ) {
		// Check if the meta values are of correct type according to the default meta keys.
		foreach ( $default_meta_keys as $key => $value ) {

			if ( ! is_array( $meta_values ) ) {
				// check if the meta values are empty then set it to default value.
				if ( empty( $meta_values ) ) {
					$meta_values = $value;

				}

				return $meta_values;

			}

			// add special case for email_notification.
			if ( '_srfm_email_notification' === $key ) {
				if ( ! array_key_exists( $key, $meta_values ) ) {
					$meta_values[ $key ] = $value;
				} else {
					if ( is_array( $value ) ) {
						$meta_values[ $key ] = self::validate_meta_values( $value, $meta_values[ $key ] );
					}
				}

				return $meta_values;
			}

			if ( ! array_key_exists( $key, $meta_values ) ) {
				$meta_values[ $key ] = $value;
			} else {
				if ( is_array( $value ) ) {
					$meta_values[ $key ] = self::validate_meta_values( $value, $meta_values[ $key ] );
				} else {
					if ( gettype( $value ) !== gettype( $meta_values[ $key ] ) ) {
						$meta_values[ $key ] = $value;
					}
				}
			}
		}

		return $meta_values;
	}


}
