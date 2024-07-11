<?php
/**
 * Sureforms Submit Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;
use WP_Error;
use WP_REST_Request;
use WP_Post_Type;
use WP_Query;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Helper Class.
 *
 * @since 0.0.1
 */
class Helper {
	use Get_Instance;

	/**
	 * Sureforms SVGs.
	 *
	 * @var mixed srfm_svgs
	 */
	private static $srfm_svgs = null;

	/**
	 * Get common error message.
	 *
	 * @since 0.0.2
	 * @return array<string>
	 */
	public static function get_common_err_msg() {
		return [
			'required' => __( 'This field is required.', 'sureforms' ),
			'unique'   => __( 'Value needs to be unique.', 'sureforms' ),
		];
	}


	/**
	 * Checks if current value is string or else returns default value
	 *
	 * @param mixed $data data which need to be checked if is string.
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public static function get_string_value( $data ) {
		if ( is_scalar( $data ) ) {
			return (string) $data;
		} elseif ( is_object( $data ) && method_exists( $data, '__toString' ) ) {
			return $data->__toString();
		} elseif ( is_null( $data ) ) {
			return '';
		} else {
			return '';
		}
	}
	/**
	 * Checks if current value is number or else returns default value
	 *
	 * @param mixed $value data which need to be checked if is string.
	 * @param int   $base value can be set is $data is not a string, defaults to empty string.
	 *
	 * @since 0.0.1
	 * @return int
	 */
	public static function get_integer_value( $value, $base = 10 ) {
		if ( is_numeric( $value ) ) {
			return (int) $value;
		} elseif ( is_string( $value ) ) {
			$trimmed_value = trim( $value );
			return intval( $trimmed_value, $base );
		} else {
			return 0;
		}
	}

	/**
	 * Extracts the field type from the dynamic field key ( or field slug ).
	 *
	 * @param string $field_key Dynamic field key.
	 * @since x.x.x
	 * @return string Extracted field type.
	 */
	public static function get_field_type_from_key( $field_key ) {

		if ( false === strpos( $field_key, '-lbl-' ) ) {
			return '';
		}

		return trim( explode( '-', $field_key )[1] );
	}

	/**
	 * Returns the proper sanitize callback functions according to the field type.
	 *
	 * @param string $field_type HTML field type.
	 * @since x.x.x
	 * @return callable Returns sanitize callbacks according to the provided field type.
	 */
	public static function get_field_type_sanitize_function( $field_type ) {
		$callbacks = apply_filters(
			'srfm_field_type_sanitize_functions',
			[
				'url'      => 'esc_url_raw',
				'input'    => 'sanitize_text_field',
				'number'   => [ __CLASS__, 'sanitize_number' ],
				'email'    => 'sanitize_email',
				'textarea' => 'sanitize_textarea_field',
			]
		);

		return isset( $callbacks[ $field_type ] ) ? $callbacks[ $field_type ] : 'sanitize_text_field';

	}

	/**
	 * Sanitizes a numeric value.
	 *
	 * This function checks if the input value is numeric. If it is numeric, it sanitizes
	 * the value to ensure it's a float or integer, allowing for fractions and thousand separators.
	 * If the value is not numeric, it sanitizes it as a text field.
	 *
	 * @param mixed $value The value to be sanitized.
	 * @since x.x.x
	 * @return integer|float|string The sanitized value.
	 */
	public static function sanitize_number( $value ) {
		if ( ! is_numeric( $value ) ) {
			// phpcs:ignore /** @phpstan-ignore-next-line */
			return sanitize_text_field( $value ); // If it is not numeric, then let user get some sanitized data to view.
		}

		// phpcs:ignore /** @phpstan-ignore-next-line */
		return sanitize_text_field( filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND ) );
	}

	/**
	 * This function sanitizes the submitted form data according to the field type.
	 *
	 * @param array<mixed> $form_data $form_data User submitted form data.
	 * @since x.x.x
	 * @return array<mixed> $result Sanitized form data.
	 */
	public static function sanitize_by_field_type( $form_data ) {
		$result = [];

		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			return $result;
		}

		foreach ( $form_data as $field_key => &$value ) {
			$field_type        = self::get_field_type_from_key( $field_key );
			$sanitize_function = self::get_field_type_sanitize_function( $field_type );
			$sanitized_data    = is_array( $value ) ? self::sanitize_by_field_type( $value ) : call_user_func( $sanitize_function, $value );

			$result[ $field_key ] = $sanitized_data;
		}

		return $result;

	}

	/**
	 * This function performs array_map for multi dimensional array
	 *
	 * @param string       $function function name to be applied on each element on array.
	 * @param array<mixed> $data_array array on which function needs to be performed.
	 * @return array<mixed>
	 * @since 0.0.1
	 */
	public static function sanitize_recursively( $function, $data_array ) {
		$response = [];
		if ( is_array( $data_array ) ) {
			if ( ! is_callable( $function ) ) {
				return $data_array;
			}
			foreach ( $data_array as $key => $data ) {
				$val              = is_array( $data ) ? self::sanitize_recursively( $function, $data ) : $function( $data );
				$response[ $key ] = $val;
			}
		}

		return $response;
	}

	/**
	 * Generates common markup liked label, etc
	 *
	 * @param int|string $form_id form id.
	 * @param string     $type Type of form markup.
	 * @param string     $label Label for the form markup.
	 * @param string     $slug Slug for the form markup.
	 * @param string     $block_id Block id for the form markup.
	 * @param bool       $required If field is required or not.
	 * @param string     $help Help for the form markup.
	 * @param string     $error_msg Error message for the form markup.
	 * @param bool       $is_unique Check if the field is unique.
	 * @param string     $duplicate_msg Duplicate message for field.
	 * @param bool       $override Override for error markup.
	 * @return string
	 * @since 0.0.1
	 */
	public static function generate_common_form_markup( $form_id, $type, $label = '', $slug = '', $block_id = '', $required = false, $help = '', $error_msg = '', $is_unique = false, $duplicate_msg = '', $override = false ) {
		$duplicate_msg = $duplicate_msg ? ' data-unique-msg="' . $duplicate_msg . '"' : '';

		$markup         = '';
		$show_labels    = get_post_meta( self::get_integer_value( $form_id ), '_srfm_show_labels', true ) ? self::get_string_value( get_post_meta( self::get_integer_value( $form_id ), '_srfm_show_labels', true ) ) : true;
		$show_asterisks = get_post_meta( self::get_integer_value( $form_id ), '_srfm_show_asterisk', true ) ? self::get_string_value( get_post_meta( self::get_integer_value( $form_id ), '_srfm_show_asterisk', true ) ) : true;

		switch ( $type ) {
			case 'label':
				$markup = $label && '1' === $show_labels ? '<label for="srfm-' . $slug . '-' . esc_attr( $block_id ) . '" class="srfm-block-label">' . htmlspecialchars_decode( esc_html( $label ) ) . ( $required && '1' === $show_asterisks ? '<span class="srfm-required"> *</span>' : '' ) . '</label>' : '';
				break;
			case 'help':
				$markup = $help ? '<div class="srfm-description" id="srfm-description-' . esc_attr( $block_id ) . '">' . esc_html( $help ) . '</div>' : '';
				break;
			case 'error':
				$markup = $required || $override ? '<div class="srfm-error-message" id="srfm-error-' . esc_attr( $block_id ) . '" data-error-msg="' . esc_attr( $error_msg ) . '"' . esc_attr( $duplicate_msg ) . '>' . esc_html( $error_msg ) . '</div>' : '';
				break;
			case 'is_unique':
				$markup = $is_unique ? '<div class="srfm-error">' . esc_html( $duplicate_msg ) . '</div>' : '';
				break;
			default:
				$markup = '';
		}

		return $markup;
	}


	/**
	 * Get an SVG Icon
	 *
	 * @since 0.0.1
	 * @param string $icon the icon name.
	 * @param string $class if the baseline class should be added.
	 * @param string $html Custom attributes inside svg wrapper.
	 * @return string
	 */
	public static function fetch_svg( $icon = '', $class = '', $html = '' ) {
		$class = $class ? ' ' . $class : '';

		$output = '<span class="srfm-icon' . $class . '" ' . $html . '>';
		if ( ! self::$srfm_svgs ) {
			ob_start();

			include_once SRFM_DIR . 'assets/svg/svgs.json';
			self::$srfm_svgs = json_decode( self::get_string_value( ob_get_clean() ), true );
			self::$srfm_svgs = apply_filters( 'srfm_svg_icons', self::$srfm_svgs );
		}

		$output .= isset( self::$srfm_svgs[ $icon ] ) ? self::$srfm_svgs[ $icon ] : '';
		$output .= '</span>';

		return $output;
	}


	/**
	 * Encrypt data using base64.
	 *
	 * @param string $input The input string which needs to be encrypted.
	 * @since 0.0.1
	 * @return string The encrypted string.
	 */
	public static function encrypt( $input ) {
		// If the input is empty or not a string, then abandon ship.
		if ( empty( $input ) || ! is_string( $input ) ) {
			return '';
		}

		// Encrypt the input and return it.
		$base_64 = base64_encode( $input ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$encode  = rtrim( $base_64, '=' );
		return $encode;
	}

	/**
	 * Decrypt data using base64.
	 *
	 * @param string $input The input string which needs to be decrypted.
	 * @since 0.0.1
	 * @return string The decrypted string.
	 */
	public static function decrypt( $input ) {
		// If the input is empty or not a string, then abandon ship.
		if ( empty( $input ) || ! is_string( $input ) ) {
			return '';
		}

		// Decrypt the input and return it.
		$base_64 = $input . str_repeat( '=', strlen( $input ) % 4 );
		$decode  = base64_decode( $base_64 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		return $decode;
	}

	/**
	 * Update an option from the database.
	 *
	 * @param string $key              The option key.
	 * @param mixed  $value            The value to update.
	 * @param bool   $network_override Whether to allow the network_override admin setting to be overridden on subsites.
	 * @since 0.0.1
	 * @return bool True if the option was updated, false otherwise.
	 */
	public static function update_admin_settings_option( $key, $value, $network_override = false ) {
		// Update the site-wide option if we're in the network admin, and return the updated status.
		return $network_override && is_multisite() ? update_site_option( $key, $value ) : update_option( $key, $value );
	}

	/**
	 * Update an option from the database.
	 *
	 * @param int|string $post_id post id / form id.
	 * @param string     $key meta key name.
	 * @param bool       $single single or multiple.
	 * @param mixed      $default default value.
	 *
	 * @since 0.0.1
	 * @return string Meta value.
	 */
	public static function get_meta_value( $post_id, $key, $single = true, $default = '' ) {
		$meta_value = get_post_meta( self::get_integer_value( $post_id ), $key, $single ) ? self::get_string_value( get_post_meta( self::get_integer_value( $post_id ), $key, $single ) ) : self::get_string_value( $default );
		return $meta_value;
	}


	/**
	 * Default dynamic block value.
	 *
	 * @since 0.0.1
	 * @return string[] Meta value.
	 */
	public static function default_dynamic_block_option() {

		$common_err_msg = self::get_common_err_msg();

		$default_values = [
			'srfm_url_block_required_text'             => $common_err_msg['required'],
			'srfm_input_block_required_text'           => $common_err_msg['required'],
			'srfm_input_block_unique_text'             => $common_err_msg['unique'],
			'srfm_address_block_required_text'         => $common_err_msg['required'],
			'srfm_address_compact_block_required_text' => $common_err_msg['required'],
			'srfm_phone_block_required_text'           => $common_err_msg['required'],
			'srfm_phone_block_unique_text'             => $common_err_msg['unique'],
			'srfm_number_block_required_text'          => $common_err_msg['required'],
			'srfm_textarea_block_required_text'        => $common_err_msg['required'],
			'srfm_multi_choice_block_required_text'    => $common_err_msg['required'],
			'srfm_checkbox_block_required_text'        => $common_err_msg['required'],
			'srfm_gdpr_block_required_text'            => $common_err_msg['required'],
			'srfm_email_block_required_text'           => $common_err_msg['required'],
			'srfm_email_block_unique_text'             => $common_err_msg['unique'],
			'srfm_dropdown_block_required_text'        => $common_err_msg['required'],
		];

		return apply_filters( 'srfm_default_dynamic_block_option', $default_values, $common_err_msg );

	}

	/**
	 * Get default dynamic block value.
	 *
	 * @param string $key meta key name.
	 * @since 0.0.1
	 * @return string Meta value.
	 */
	public static function get_default_dynamic_block_option( $key ) {
		$default_dynamic_values = self::default_dynamic_block_option();
		$option                 = get_option( 'get_default_dynamic_block_option', $default_dynamic_values );

		if ( is_array( $option ) && array_key_exists( $key, $option ) ) {
			return $option[ $key ];
		} else {
			return '';
		}
	}

	/**
	 * Checks whether a given request has appropriate permissions.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 * @since 0.0.1
	 */
	public static function get_items_permissions_check() {
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

		return new WP_Error(
			'rest_cannot_view',
			__( 'Sorry, you are not allowed to perform this action.', 'sureforms' ),
			[ 'status' => \rest_authorization_required_code() ]
		);
	}

	/**
	 * Check if the current user has a given capability.
	 *
	 * @param string $capability The capability to check.
	 * @since 0.0.3
	 * @return bool Whether the current user has the given capability or role.
	 */
	public static function current_user_can( $capability = '' ) {

		if ( ! function_exists( 'current_user_can' ) ) {
			return false;
		}

		if ( ! is_string( $capability ) || empty( $capability ) ) {
			$capability = 'edit_posts';
		}

		return current_user_can( $capability );
	}

	/**
	 * Get all the entries for the given form ids. The entries are older than the given days_old.
	 *
	 * @param int        $days_old The number of days old the entries should be.
	 * @param array<int> $sf_form_ids The form ids for which the entries need to be fetched.
	 * @since 0.0.2
	 * @return array<int|WP_Post> the entries matching the criteria.
	 */
	public static function get_entries_from_form_ids( $days_old = 0, $sf_form_ids = [] ) {

		$entries = [];

		foreach ( $sf_form_ids as $form_id ) {
			$args = [
				'post_type'   => 'sureforms_entry',
				'post_status' => 'publish',
				'date_query'  => [
					[
						'before' => $days_old . ' days ago',
					],
				],
				'meta_query' // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query. -- We require meta_query for this function to work.
				=> [
					[
						'key'     => '_srfm_entry_form_id',
						'value'   => $form_id,
						'compare' => '=',
					],
				],
			];

			$query = new WP_Query( $args );

			// store all the entries in an single array.
			$entries = array_merge( $entries, $query->posts );
		}

		return $entries;

	}

	/**
	 * Decode block attributes.
	 * The function reverses the effect of serialize_block_attributes()
	 *
	 * @link https://developer.wordpress.org/reference/functions/serialize_block_attributes/
	 * @param string $encoded_data the encoded block attribute.
	 * @since 0.0.2
	 * @return string decoded block attribute
	 */
	public static function decode_block_attribute( $encoded_data = '' ) {
		$decoded_data = preg_replace( '/\\\\u002d\\\\u002d/', '--', self::get_string_value( $encoded_data ) );
		$decoded_data = preg_replace( '/\\\\u003c/', '<', self::get_string_value( $decoded_data ) );
		$decoded_data = preg_replace( '/\\\\u003e/', '>', self::get_string_value( $decoded_data ) );
		$decoded_data = preg_replace( '/\\\\u0026/', '&', self::get_string_value( $decoded_data ) );
		$decoded_data = preg_replace( '/\\\\\\\\"/', '"', self::get_string_value( $decoded_data ) );
		return self::get_string_value( $decoded_data );
	}

	/**
	 * Map slugs to submission data.
	 *
	 * @param array<mixed> $submission_data submission_data.
	 * @since 0.0.3
	 * @return array<mixed>
	 */
	public static function map_slug_to_submission_data( $submission_data = [] ) {
		$mapped_data = [];
		foreach ( $submission_data as $key => $value ) {
			$label                = explode( '-lbl-', $key )[1];
			$slug                 = implode( '-', array_slice( explode( '-', $label ), 1 ) );
			$mapped_data[ $slug ] = $value;
		}
		return $mapped_data;
	}

	/**
	 * Checks if current value is an array or else returns default value
	 *
	 * @param mixed $data Data which needs to be checked if it is an array.
	 *
	 * @since 0.0.3
	 * @return array<mixed>
	 */
	public static function get_array_value( $data ) {
		if ( is_array( $data ) ) {
			return $data;
		} elseif ( is_null( $data ) ) {
			return [];
		} else {
			return (array) $data;
		}
	}

	/**
	 * Get forms options. Shows all the available forms in the dropdown.
	 *
	 * @since 0.0.5
	 * @param string $key Determines the type of data to return.
	 * @return array<mixed>
	 */
	public static function get_sureforms( $key = '' ) {
		$forms = get_posts(
			apply_filters(
				'srfm_get_sureforms_query_args',
				[
					'post_type'      => SRFM_FORMS_POST_TYPE,
					'posts_per_page' => -1,
					'post_status'    => 'publish',
				]
			)
		);

		$options = [];

		foreach ( $forms as $form ) {
			if ( $form instanceof WP_Post ) {
				if ( 'all' === $key ) {
					$options[ $form->ID ] = $form;
				} elseif ( ! empty( $key ) && is_string( $key ) && isset( $form->$key ) ) {
					$options[ $form->ID ] = $form->$key;
				} else {
					$options[ $form->ID ] = $form->post_title;
				}
			}
		}

		return $options;
	}

	/**
	 * Get all the forms.
	 *
	 * @since 0.0.5
	 * @return array<mixed>
	 */
	public static function get_sureforms_title_with_ids() {
		$form_options = self::get_sureforms();

		foreach ( $form_options as $key => $value ) {
			$form_options[ $key ] = $value . ' #' . $key;
		}

		return $form_options;
	}

}
