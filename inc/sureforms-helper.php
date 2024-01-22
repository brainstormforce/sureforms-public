<?php
/**
 * Sureforms Submit Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Sureforms Helper Class.
 *
 * @since 0.0.1
 */
class Sureforms_Helper {
	use Get_Instance;

	/**
	 * Sureforms SVGs.
	 *
	 * @var mixed srfm_svgs
	 */
	private static $srfm_svgs = null;

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
	 * This function performs array_map for multi dimensional array
	 *
	 * @param string       $function function name to be applied on each element on array.
	 * @param array<mixed> $data_array array on which function needs to be performed.
	 * @return array<mixed>
	 * @since 1.0.0
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
	 * @param string $type Type of form markup.
	 * @param string $label Label for the form markup.
	 * @param string $slug Slug for the form markup.
	 * @param string $block_id Block id for the form markup.
	 * @param bool   $required If field is required or not.
	 * @param string $help Help for the form markup.
	 * @param string $error_msg Error message for the form markup.
	 * @param bool   $is_unique Check if the field is unique.
	 * @param string $duplicate_msg Duplicate message for field.
	 * @param bool   $override Override for error markup.
	 * @return string
	 * @since 0.0.1
	 */
	public static function generate_common_form_markup( $type, $label = '', $slug = '', $block_id = '', $required = false, $help = '', $error_msg = '', $is_unique = false, $duplicate_msg = '', $override = false ) {
		$duplicate_msg = $duplicate_msg ? ' data-unique-msg="' . $duplicate_msg . '"' : '';

		$markup = '';

		switch ( $type ) {
			case 'label':
				$markup = $label ? '<label for="srfm-' . $slug . '-' . esc_attr( $block_id ) . '" class="srfm-block-label">' . esc_html( $label ) . ( $required ? '<span class="srfm-required"> *</span>' : '' ) . '</label>' : '';
				break;
			case 'help':
				$markup = $help ? '<div class="srfm-description">' . esc_html( $help ) . '</div>' : '';
				break;
			case 'error':
				$markup = $required || $override ? '<div class="srfm-error-message" data-error-msg="' . $error_msg . '"' . $duplicate_msg . '>' . esc_html( $error_msg ) . '</div>' : '';
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

			include_once SUREFORMS_DIR . 'assets/svg/svgs.json'; // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound
			// phpcs:ignore /** @phpstan-ignore-next-line */
			self::$srfm_svgs = json_decode( ob_get_clean(), true );
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
}
