<?php
/**
 * Sureforms Submit Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Traits\Get_Instance;
use WP_Error;
use WP_Post;
use WP_Post_Type;

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
	 * Convert a file URL to a file path.
	 *
	 * @param string $file_url The URL of the file.
	 *
	 * @since 1.3.0
	 * @return string The file path.
	 */
	public static function convert_fileurl_to_filepath( $file_url ) {
		static $upload_dir = null;
		if ( ! $upload_dir ) {
			// Internally cache the upload directory.
			$upload_dir = wp_get_upload_dir();
		}
		return wp_normalize_path( str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_url ) );
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
		}
		if ( is_object( $data ) && method_exists( $data, '__toString' ) ) {
			return $data->__toString();
		}
		if ( is_null( $data ) ) {
			return '';
		}
			return '';
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
		}
		if ( is_string( $value ) ) {
			$trimmed_value = trim( $value );
			return intval( $trimmed_value, $base );
		}
			return 0;
	}

	/**
	 * Checks if current value is an array or else returns default value
	 *
	 * @param mixed $data Data which needs to be checked if it is an array.
	 *
	 * @since 0.0.3
	 * @return array
	 */
	public static function get_array_value( $data ) {
		if ( is_array( $data ) ) {
			return $data;
		}
		if ( is_null( $data ) ) {
			return [];
		}
			return (array) $data;
	}

	/**
	 * Extracts the field type from the dynamic field key ( or field slug ).
	 *
	 * @param string $field_key Dynamic field key.
	 * @since 0.0.6
	 * @return string Extracted field type.
	 */
	public static function get_field_type_from_key( $field_key ) {

		if ( false === strpos( $field_key, '-lbl-' ) ) {
			return '';
		}

		return trim( explode( '-', $field_key )[1] );
	}

	/**
	 * Extracts the field label from the dynamic field key ( or field slug ).
	 *
	 * @param string $field_key Dynamic field key.
	 * @since 1.1.1
	 * @return string Extracted field label.
	 */
	public static function get_field_label_from_key( $field_key ) {
		if ( false === strpos( $field_key, '-lbl-' ) ) {
			return '';
		}

		$label = explode( '-lbl-', $field_key )[1];
		// Getting the encrypted label. we are removing the block slug here.
		$label = explode( '-', $label )[0];

		return $label ? html_entity_decode( self::decrypt( $label ) ) : '';
	}

	/**
	 * Returns the proper sanitize callback functions according to the field type.
	 *
	 * @param string $field_type HTML field type.
	 * @since 0.0.6
	 * @return callable Returns sanitize callbacks according to the provided field type.
	 */
	public static function get_field_type_sanitize_function( $field_type ) {
		$callbacks = apply_filters(
			'srfm_field_type_sanitize_functions',
			[
				'url'      => 'esc_url_raw',
				'input'    => 'sanitize_text_field',
				'number'   => [ self::class, 'sanitize_number' ],
				'email'    => 'sanitize_email',
				'textarea' => 'sanitize_textarea_field',
			]
		);

		return $callbacks[ $field_type ] ?? 'sanitize_text_field';
	}

	/**
	 * Sanitizes a numeric value.
	 *
	 * This function checks if the input value is numeric. If it is numeric, it sanitizes
	 * the value to ensure it's a float or integer, allowing for fractions and thousand separators.
	 * If the value is not numeric, it sanitizes it as a text field.
	 *
	 * @param mixed $value The value to be sanitized.
	 * @since 0.0.6
	 * @return int|float|string The sanitized value.
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
	 * @since 0.0.6
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
		$duplicate_msg = $duplicate_msg ? ' data-unique-msg="' . esc_attr( $duplicate_msg ) . '"' : '';

		$markup                     = '';
		$show_labels_as_placeholder = get_post_meta( self::get_integer_value( $form_id ), '_srfm_use_label_as_placeholder', true );
		$show_labels_as_placeholder = $show_labels_as_placeholder ? self::get_string_value( $show_labels_as_placeholder ) : false;

		switch ( $type ) {
			case 'label':
				$markup = $label ? '<label id="srfm-label-' . esc_attr( $block_id ) . '" for="srfm-' . $slug . '-' . esc_attr( $block_id ) . '" class="srfm-block-label">' . htmlspecialchars_decode( esc_html( $label ) ) . ( $required ? '<span class="srfm-required" aria-label="' . esc_attr__( 'Required', 'sureforms' ) . '"><span aria-hidden="true"> *</span></span>' : '' ) . '</label>' : '';
				break;
			case 'help':
				$markup = $help ? '<div class="srfm-description" id="srfm-description-' . esc_attr( $block_id ) . '">' . wp_kses_post( htmlspecialchars_decode( $help ) ) . '</div>' : '';
				break;
			case 'error':
				$markup = $required || $override ? '<div class="srfm-error-message" data-srfm-id="srfm-error-' . esc_attr( $block_id ) . '" data-error-msg="' . esc_attr( $error_msg ) . '"' . $duplicate_msg . '>' . esc_html( $error_msg ) . '</div>' : '';
				break;
			case 'is_unique':
				$markup = $is_unique ? '<div class="srfm-error">' . esc_html( $duplicate_msg ) . '</div>' : '';
				break;
			case 'placeholder':
				$markup = $label && '1' === $show_labels_as_placeholder ? htmlspecialchars_decode( esc_html( $label ) ) . ( $required ? ' *' : '' ) : '';
				break;
			case 'label_text':
				// This has been added for generating label text for the form markup instead of adding it in the label tag.
				$markup = $label ? htmlspecialchars_decode( esc_html( $label ) ) . ( $required ? '<span class="srfm-required" aria-label=",' . esc_attr__( 'Required', 'sureforms' ) . ',"><span aria-hidden="true"> *</span></span>' : '' ) . '</label>' : '';
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

		$output .= self::$srfm_svgs[ $icon ] ?? '';
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
		return rtrim( $base_64, '=' );
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
		return base64_decode( $base_64 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
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
		$srfm_live_mode_data = self::get_instant_form_live_data();

		if ( isset( $srfm_live_mode_data[ $key ] ) ) {
			// Give priority to live mode data if we have one set from the Instant Form.
			return self::get_string_value( $srfm_live_mode_data[ $key ] );
		}

		return get_post_meta( self::get_integer_value( $post_id ), $key, $single ) ? self::get_string_value( get_post_meta( self::get_integer_value( $post_id ), $key, $single ) ) : self::get_string_value( $default );
	}

	/**
	 * Wrapper for the WordPress's get_post_meta function with the support for default values.
	 *
	 * @param int|string $post_id Post ID.
	 * @param string     $key The meta key to retrieve.
	 * @param mixed      $default Default value.
	 * @param bool       $single Optional. Whether to return a single value.
	 * @since 0.0.8
	 * @return mixed Meta value.
	 */
	public static function get_post_meta( $post_id, $key, $default = null, $single = true ) {
		$meta_value = get_post_meta( self::get_integer_value( $post_id ), $key, $single );
		return $meta_value ? $meta_value : $default;
	}

	/**
	 * Returns query params data for instant form live preview.
	 *
	 * @since 0.0.8
	 * @return array<mixed> Live preview data.
	 */
	public static function get_instant_form_live_data() {
		$srfm_live_mode_data = isset( $_GET['live_mode'] ) && current_user_can( 'edit_posts' ) ? self::sanitize_recursively( 'sanitize_text_field', wp_unslash( $_GET ) ) : []; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return $srfm_live_mode_data ? array_map(
			// Normalize falsy values.
			static function( $live_data ) {
				return 'false' === $live_data ? false : $live_data;
			},
			$srfm_live_mode_data
		) : [];
	}

	/**
	 * Default dynamic block value.
	 *
	 * @since 0.0.1
	 * @return array<string> Meta value.
	 */
	public static function default_dynamic_block_option() {

		$common_err_msg = self::get_common_err_msg();

		$default_values = [
			'srfm_url_block_required_text'          => $common_err_msg['required'],
			'srfm_input_block_required_text'        => $common_err_msg['required'],
			'srfm_input_block_unique_text'          => $common_err_msg['unique'],
			'srfm_address_block_required_text'      => $common_err_msg['required'],
			'srfm_phone_block_required_text'        => $common_err_msg['required'],
			'srfm_phone_block_unique_text'          => $common_err_msg['unique'],
			'srfm_number_block_required_text'       => $common_err_msg['required'],
			'srfm_textarea_block_required_text'     => $common_err_msg['required'],
			'srfm_multi_choice_block_required_text' => $common_err_msg['required'],
			'srfm_checkbox_block_required_text'     => $common_err_msg['required'],
			'srfm_gdpr_block_required_text'         => $common_err_msg['required'],
			'srfm_email_block_required_text'        => $common_err_msg['required'],
			'srfm_email_block_unique_text'          => $common_err_msg['unique'],
			'srfm_dropdown_block_required_text'     => $common_err_msg['required'],
			'srfm_rating_block_required_text'       => $common_err_msg['required'],
		];

		$default_values = array_merge( $default_values, Translatable::dynamic_validation_messages() );

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
		$option                 = get_option( 'srfm_default_dynamic_block_option', $default_dynamic_values );

		if ( is_array( $option ) && array_key_exists( $key, $option ) ) {
			return $option[ $key ];
		}
			return '';
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
	 * @return array<mixed> the entries matching the criteria.
	 */
	public static function get_entries_from_form_ids( $days_old = 0, $sf_form_ids = [] ) {

		$entries       = [];
		$days_old_date = ( new \DateTime() )->modify( "-{$days_old} days" )->format( 'Y-m-d H:i:s' );

		foreach ( $sf_form_ids as $form_id ) {
			// args according to the get_all() function in the Entries class.
			$args = [
				'where' => [
					[
						[
							'key'     => 'form_id',
							'value'   => $form_id,
							'compare' => '=',
						],
						[
							'key'     => 'created_at',
							'value'   => $days_old_date,
							'compare' => '<=',
						],
					],
				],
			];

			// store all the entries in a single array.
			$entries = array_merge( $entries, Entries::get_all( $args, false ) );
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
			if ( false === strpos( $key, '-lbl-' ) ) {
				continue;
			}
			$label                = explode( '-lbl-', $key )[1];
			$slug                 = implode( '-', array_slice( explode( '-', $label ), 1 ) );
			$mapped_data[ $slug ] = $value;
		}
		return $mapped_data;
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

	/**
	 * Get the CSS variables based on different field spacing sizes.
	 *
	 * @param string|null $field_spacing The field spacing size or boolean false to return complete sizes array.
	 *
	 * @since 0.0.7
	 * @return array<string|mixed>
	 */
	public static function get_css_vars( $field_spacing = null ) {
		/**
		 * $sizes - Field Spacing Sizes Variables.
		 * The array contains the CSS variables for different field spacing sizes.
		 * Each key corresponds to the field spacing size, and the value is an array of CSS variables.
		 *
		 * For future variables depending on the field spacing size, add the variable to the array respectively.
		 */
		$sizes = apply_filters(
			'srfm_css_vars_sizes',
			[
				'small'  => [
					'--srfm-row-gap-between-blocks'        => '16px',
					// Address block gap and spacing variables.
					'--srfm-col-gap-between-fields'        => '12px',
					'--srfm-row-gap-between-fields'        => '12px',
					'--srfm-gap-below-address-label'       => '12px',
					// Dropdown Variables.
					'--srfm-dropdown-font-size'            => '14px',
					'--srfm-dropdown-gap-between-input-menu' => '4px',
					'--srfm-dropdown-badge-padding'        => '2px 6px',
					'--srfm-dropdown-multiselect-font-size' => '12px',
					'--srfm-dropdown-multiselect-line-height' => '16px',
					'--srfm-dropdown-padding-right'        => '12px',
					// initial padding and from 20px - 12px for dropdown arrow width and 8px for gap before dropdown arrow.
					'--srfm-dropdown-padding-right-icon'   => 'calc( var( --srfm-dropdown-padding-right ) + 20px )',
					'--srfm-dropdown-multiselect-padding'  => '8px var( --srfm-dropdown-padding-right-icon ) 8px 8px',
					// Input Field Variables.
					'--srfm-input-height'                  => '40px',
					'--srfm-input-field-padding'           => '10px 12px',
					'--srfm-input-field-font-size'         => '14px',
					'--srfm-input-field-line-height'       => '20px',
					'--srfm-input-field-margin'            => '4px 0',
					// Checkbox and GDPR Variables.
					'--srfm-check-ctn-width'               => '16px',
					'--srfm-check-ctn-height'              => '16px',
					'--srfm-check-svg-size'                => '10px',
					'--srfm-checkbox-margin-top-frontend'  => '2px',
					'--srfm-checkbox-margin-top-editor'    => '3px',
					'--srfm-check-gap'                     => '8px',
					'--srfm-checkbox-description-margin-left' => '24px',
					// Phone Number field variables.
					'--srfm-flag-section-padding'          => '10px 0 10px 12px',
					'--srfm-gap-between-icon-text'         => '8px',
					// Label Variables.
					'--srfm-label-font-size'               => '14px',
					'--srfm-label-line-height'             => '20px',
					// Description Variables.
					'--srfm-description-font-size'         => '12px',
					'--srfm-description-line-height'       => '16px',
					// Button Variables.
					'--srfm-btn-padding'                   => '8px 14px',
					'--srfm-btn-font-size'                 => '14px',
					'--srfm-btn-line-height'               => '20px',
					// Multi Choice Variables.
					'--srfm-multi-choice-horizontal-padding' => '16px',
					'--srfm-multi-choice-vertical-padding' => '16px',
					'--srfm-multi-choice-internal-option-gap' => '8px',
					'--srfm-multi-choice-vertical-svg-size' => '32px',
					'--srfm-multi-choice-horizontal-image-size' => '20px',
					'--srfm-multi-choice-vertical-image-size' => '100px',
					'--srfm-multi-choice-outer-padding'    => '0',
				],
				'medium' => [
					'--srfm-row-gap-between-blocks'        => '18px',
					// Address block gap and spacing variables.
					'--srfm-col-gap-between-fields'        => '16px',
					'--srfm-row-gap-between-fields'        => '16px',
					'--srfm-gap-below-address-label'       => '14px',
					// Input Field Variables.
					'--srfm-input-height'                  => '44px',
					'--srfm-input-field-font-size'         => '16px',
					'--srfm-input-field-line-height'       => '24px',
					'--srfm-input-field-margin'            => '6px 0',
					// Checkbox and GDPR Variables.
					'--srfm-checkbox-margin-top-frontend'  => '4px',
					'--srfm-checkbox-margin-top-editor'    => '6px',
					'--srfm-checkbox-description-margin-left' => '24px',
					// Label Variables.
					'--srfm-label-font-size'               => '16px',
					'--srfm-label-line-height'             => '24px',
					// Description Variables.
					'--srfm-description-font-size'         => '14px',
					'--srfm-description-line-height'       => '20px',
					// Button Variables.
					'--srfm-btn-padding'                   => '10px 14px',
					'--srfm-btn-font-size'                 => '16px',
					'--srfm-btn-line-height'               => '24px',
					// Multi Choice Variables.
					'--srfm-multi-choice-horizontal-padding' => '20px',
					'--srfm-multi-choice-vertical-padding' => '20px',
					'--srfm-multi-choice-vertical-svg-size' => '40px',
					'--srfm-multi-choice-horizontal-image-size' => '24px',
					'--srfm-multi-choice-vertical-image-size' => '120px',
					'--srfm-multi-choice-outer-padding'    => '2px',
				],
				'large'  => [
					'--srfm-row-gap-between-blocks'        => '20px',
					// Address Block Gap and Spacing Variables.
					'--srfm-col-gap-between-fields'        => '16px',
					'--srfm-row-gap-between-fields'        => '20px',
					'--srfm-gap-below-address-label'       => '16px',
					// Dropdown Variables.
					'--srfm-dropdown-font-size'            => '16px',
					'--srfm-dropdown-gap-between-input-menu' => '6px',
					'--srfm-dropdown-badge-padding'        => '6px 6px',
					'--srfm-dropdown-multiselect-font-size' => '14px',
					'--srfm-dropdown-multiselect-line-height' => '20px',
					'--srfm-dropdown-padding-right'        => '14px',
					// Input Field Variables.
					'--srfm-input-height'                  => '48px',
					'--srfm-input-field-padding'           => '10px 14px',
					'--srfm-input-field-font-size'         => '18px',
					'--srfm-input-field-line-height'       => '28px',
					'--srfm-input-field-margin'            => '8px 0',
					// Checkbox and GDPR Variables.
					'--srfm-check-ctn-width'               => '20px',
					'--srfm-check-ctn-height'              => '20px',
					'--srfm-check-svg-size'                => '14px',
					'--srfm-check-gap'                     => '10px',
					'--srfm-checkbox-margin-top-frontend'  => '4px',
					'--srfm-checkbox-margin-top-editor'    => '5px',
					'--srfm-checkbox-description-margin-left' => '30px',
					// Label Variables.
					'--srfm-label-font-size'               => '18px',
					'--srfm-label-line-height'             => '28px',
					// Description Variables.
					'--srfm-description-font-size'         => '16px',
					'--srfm-description-line-height'       => '24px',
					// Button Variables.
					'--srfm-btn-padding'                   => '10px 14px',
					'--srfm-btn-font-size'                 => '18px',
					'--srfm-btn-line-height'               => '28px',
					// Multi Choice Variables.
					'--srfm-multi-choice-horizontal-padding' => '24px',
					'--srfm-multi-choice-vertical-padding' => '24px',
					'--srfm-multi-choice-internal-option-gap' => '12px',
					'--srfm-multi-choice-vertical-svg-size' => '48px',
					'--srfm-multi-choice-horizontal-image-size' => '28px',
					'--srfm-multi-choice-vertical-image-size' => '140px',
					'--srfm-multi-choice-outer-padding'    => '4px',
				],
			]
		);
		// Return complete sizes array if field_spacing is false. Required in case of JS for Editor changes.
		if ( ! $field_spacing ) {
			return $sizes;
		}

		$selected_size = $sizes['small'];
		if ( 'small' !== $field_spacing && isset( $sizes[ $field_spacing ] ) ) {
			$selected_size = array_merge( $selected_size, $sizes[ $field_spacing ] );
		}

		return $selected_size;
	}

	/**
	 * Array of SureForms blocks which get have user input.
	 *
	 * @since 0.0.10
	 * @return array<string>
	 */
	public static function get_sureforms_blocks() {
		return apply_filters(
			'srfm_blocks',
			[
				'srfm/input',
				'srfm/email',
				'srfm/textarea',
				'srfm/number',
				'srfm/checkbox',
				'srfm/gdpr',
				'srfm/phone',
				'srfm/address',
				'srfm/dropdown',
				'srfm/multi-choice',
				'srfm/radio',
				'srfm/submit',
				'srfm/url',
			]
		);
	}

	/**
	 * Process blocks and inner blocks.
	 *
	 * @param array<mixed>  $blocks The block data.
	 * @param array<string> $slugs The array of existing slugs.
	 * @param bool          $updated The array of existing slugs.
	 * @param string        $prefix The array of existing slugs.
	 * @param bool          $skip_checking_existing_slug Skips the checking of existing slug if passed true. More information documented inside this function.
	 * @since 0.0.10
	 * @return array
	 */
	public static function process_blocks( $blocks, &$slugs, &$updated, $prefix = '', $skip_checking_existing_slug = false ) {

		if ( ! is_array( $blocks ) ) {
			return [ $blocks, $slugs, $updated ];
		}

		foreach ( $blocks as $index => $block ) {

			if ( ! is_array( $block ) ) {
				continue;
			}
			// Checking only for SureForms blocks which can have user input.
			if ( empty( $block['blockName'] ) || ! in_array( $block['blockName'], self::get_sureforms_blocks(), true ) ) {
				continue;
			}

			/**
			 * Lets continue if slug already exists.
			 * This will ensure that we don't update already existing slugs.
			 */
			if ( isset( $block['attrs'] ) && ! empty( $block['attrs']['slug'] ) && ! in_array( $block['attrs']['slug'], $slugs, true ) ) {

				// Made it associative array, so that we can directly check it using block_id rather than mapping or using "in_array" for the checks.
				$slugs[ $block['attrs']['block_id'] ] = self::get_string_value( $block['attrs']['slug'] );
				continue;
			}

			if ( $skip_checking_existing_slug && empty( $block['innerBlocks'] ) && isset( $slugs[ $block['attrs']['block_id'] ] ) ) {
				/**
				 * Skip re-processing of the already process or existing slugs if above parameter "$skip_checking_existing_slug" is passed as true.
				 * This is helpful in the scenarios where we need to compare and verify between already saved blocks and new unsaved blocks parsed
				 * from the contents.
				 *
				 * However, it is also necessary to make sure if that current block is not a parent / wrapper block
				 * by checking "$block['innerBlocks']" empty.
				 *
				 * And finally, checking if the block-id "$block['attrs']['block_id']" is already set in the list of "$slugs",
				 * making sure that we are only processing the new blocks.
				 */
				continue;
			}

			if ( is_array( $blocks[ $index ]['attrs'] ) ) {

				$blocks[ $index ]['attrs']['slug']    = self::generate_unique_block_slug( $block, $slugs, $prefix );
				$slugs[ $block['attrs']['block_id'] ] = $blocks[ $index ]['attrs']['slug']; // Made it associative array, so that we can directly check it using block_id rather than mapping or using "in_array" for the checks.
				$updated                              = true;
				if ( is_array( $block['innerBlocks'] ) && ! empty( $block['innerBlocks'] ) ) {

					[ $blocks[ $index ]['innerBlocks'], $slugs, $updated ] = self::process_blocks( $block['innerBlocks'], $slugs, $updated, $blocks[ $index ]['attrs']['slug'] );

				}
			}
		}
		return [ $blocks, $slugs, $updated ];
	}

	/**
	 * Generates slug based on the provided block and existing slugs.
	 *
	 * @param array<mixed>  $block The block data.
	 * @param array<string> $slugs The array of existing slugs.
	 * @param string        $prefix The array of existing slugs.
	 * @since 0.0.10
	 * @return string The generated unique block slug.
	 */
	public static function generate_unique_block_slug( $block, $slugs, $prefix ) {
		$slug = is_string( $block['blockName'] ) ? $block['blockName'] : '';

		if ( ! empty( $block['attrs']['label'] ) && is_string( $block['attrs']['label'] ) ) {
			$slug = sanitize_title( $block['attrs']['label'] );
		}

		if ( ! empty( $prefix ) ) {
			$slug = $prefix . '-' . $slug;
		}

		return self::generate_slug( $slug, $slugs );
	}

	/**
	 * This function ensures that the slug is unique.
	 * If the slug is already taken, it appends a number to the slug to make it unique.
	 *
	 * @param string        $slug test to be converted to slug.
	 * @param array<string> $slugs An array of existing slugs.
	 * @since 0.0.10
	 * @return string The unique slug.
	 */
	public static function generate_slug( $slug, $slugs ) {
		$slug = sanitize_title( $slug );

		if ( ! in_array( $slug, $slugs, true ) ) {
			return $slug;
		}

		$index = 1;

		while ( in_array( $slug . '-' . $index, $slugs, true ) ) {
			$index++;
		}

		return $slug . '-' . $index;
	}

	/**
	 * Encode data to JSON. This function will encode the data with JSON_UNESCAPED_SLASHES and JSON_UNESCAPED_UNICODE.
	 *
	 * @since 0.0.11
	 * @param array<mixed> $data The data to encode.
	 * @return string|false The JSON representation of the value on success or false on failure.
	 */
	public static function encode_json( $data ) {
		return wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Returns true if SureTriggers plugin is ready for the custom app.
	 *
	 * @since 1.0.3
	 * @return bool Returns true if SureTriggers plugin is ready for the custom app.
	 */
	public static function is_suretriggers_ready() {
		if ( ! defined( 'SURE_TRIGGERS_FILE' ) ) {
			// Probably plugin is de-activated or not installed at all.
			return false;
		}

		$suretriggers_data = get_option( 'suretrigger_options', [] );
		if ( ! is_array( $suretriggers_data ) || empty( $suretriggers_data['secret_key'] ) || ! is_string( $suretriggers_data['secret_key'] ) ) {
			// SureTriggers is not authenticated yet.
			return false;
		}

		return true;
	}

	/**
	 * Registers script translations for a specific handle.
	 *
	 * This function sets the script translations for a given script handle, allowing
	 * localization of JavaScript strings using the specified text domain and path.
	 *
	 * @param string $handle The script handle to apply translations to.
	 * @param string $domain Optional. The text domain for translations. Default is 'sureforms'.
	 * @param string $path   Optional. The path to the translation files. Default is the 'languages' folder in the SureForms directory.
	 *
	 * @since 1.0.5
	 * @return void
	 */
	public static function register_script_translations( $handle, $domain = 'sureforms', $path = SRFM_DIR . 'languages' ) {
		wp_set_script_translations( $handle, $domain, $path );
	}

	/**
	 * Validates whether the specified conditions or a single key-value pair exist in the request context.
	 *
	 * - If `$conditions` is provided as an array, it will validate all key-value pairs in `$conditions`
	 *   against the `$_REQUEST` superglobal.
	 * - If `$conditions` is empty, it validates a single key-value pair from `$key` and `$value`.
	 *
	 * @param string                $value      The expected value to match in the request if `$conditions` is not used.
	 * @param string                $key        The key to check for in the request if `$conditions` is not used.
	 * @param array<string, string> $conditions An optional associative array of key-value pairs to validate.
	 * @since 1.1.1
	 * @return bool Returns true if all conditions are met or the single key-value pair is valid, otherwise false.
	 */
	public static function validate_request_context( $value, $key = 'post_type', array $conditions = [] ) {
		// If conditions are provided, validate all key-value pairs in the conditions array.
		if ( ! empty( $conditions ) ) {
			foreach ( $conditions as $condition_key => $condition_value ) {
				if ( ! isset( $_REQUEST[ $condition_key ] ) || $_REQUEST[ $condition_key ] !== $condition_value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a controlled comparison of request values.
					// Return false if any condition is not satisfied.
					return false;
				}
			}
			// Return true if all conditions are satisfied.
			return true;
		}

		// Validate $value and $key when no conditions are provided.
		if ( empty( $key ) || empty( $value ) ) {
			return false;
		}

		// Validate a single key-value pair when no conditions are provided.
		return isset( $_REQUEST[ $key ] ) && $_REQUEST[ $key ] === $value; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input is validated via strict comparison.
	}

	/**
	 * Retrieve the list of excluded fields for form data processing.
	 *
	 * This method returns an array of field keys that should be excluded when
	 * processing form data.
	 *
	 * @since 1.1.1
	 * @return array<string> Returns the string array of excluded fields.
	 */
	public static function get_excluded_fields() {
		$excluded_fields = [ 'srfm-honeypot-field', 'g-recaptcha-response', 'srfm-sender-email-field', 'form-id' ];

		return apply_filters( 'srfm_excluded_fields', $excluded_fields );
	}

	/**
	 * Check whether the current page is a SureForms admin page.
	 *
	 * @since 1.2.2
	 * @return bool Returns true if the current page is a SureForms admin page, otherwise false.
	 */
	public static function is_sureforms_admin_page() {
		$current_screen                    = get_current_screen();
		$is_screen_sureforms_menu          = self::validate_request_context( 'sureforms_menu', 'page' );
		$is_screen_add_new_form            = self::validate_request_context( 'add-new-form', 'page' );
		$is_screen_sureforms_form_settings = self::validate_request_context( 'sureforms_form_settings', 'page' );
		$is_screen_sureforms_entries       = self::validate_request_context( SRFM_ENTRIES, 'page' );
		$is_post_type_sureforms_form       = $current_screen && SRFM_FORMS_POST_TYPE === $current_screen->post_type;

		return $is_screen_sureforms_menu || $is_screen_add_new_form || $is_screen_sureforms_form_settings || $is_screen_sureforms_entries || $is_post_type_sureforms_form;
	}
}
