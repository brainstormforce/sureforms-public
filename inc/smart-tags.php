<?php
/**
 * Sureforms Smart Tags.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Database\Tables\Payments;
use SRFM\Inc\Lib\Browser\Browser;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Smart Tags Class.
 *
 * @since 0.0.1
 */
class Smart_Tags {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_filter( 'render_block', [ $this, 'render_form' ], 10, 2 );
	}

	/**
	 * Smart Tag Render Function.
	 *
	 * @param string       $block_content Entire Block Content.
	 * @param array<mixed> $block Block Properties As An Array.
	 * @since 0.0.1
	 * @return string
	 */
	public function render_form( $block_content, $block ) {
		$id = get_the_id();

		// Simulating $form_data required form some of the smart tags.
		$form_data = [ 'form-id' => $id ];

		if ( self::check_form_by_id( $id ) ) {
			return Helper::get_string_value( self::process_smart_tags( $block_content, null, $form_data ) );
		}

		if ( isset( $block['blockName'] ) && ( 'srfm/form' === $block['blockName'] ) ) {
			if ( isset( $block['attrs']['id'] ) && $block['attrs']['id'] ) {
				return Helper::get_string_value( self::process_smart_tags( $block_content, null, $form_data ) );
			}
		}

		return $block_content;
	}

	/**
	 * Check Form By ID.
	 *
	 * @param int|false $id Form id.
	 * @since 0.0.1
	 * @return bool
	 */
	public function check_form_by_id( $id ) {
		return get_post_type( Helper::get_integer_value( $id ) ) ? true : false;
	}

	/**
	 * Smart Tag List.
	 *
	 * @since 0.0.1
	 * @return array<mixed>
	 */
	public static function smart_tag_list() {
		return apply_filters(
			'srfm_smart_tag_list',
			[
				'{site_url}'               => __( 'Site URL', 'sureforms' ),
				'{current_page_url}'       => __( 'Current Page URL', 'sureforms' ),
				'{admin_email}'            => __( 'Admin Email', 'sureforms' ),
				'{site_title}'             => __( 'Site Title', 'sureforms' ),
				'{form_title}'             => __( 'Form Title', 'sureforms' ),
				'{ip}'                     => __( 'IP Address', 'sureforms' ),
				'{http_referer}'           => __( 'HTTP Referer URL', 'sureforms' ),
				'{date_mdy}'               => __( 'Date (mm/dd/yyyy)', 'sureforms' ),
				'{date_dmy}'               => __( 'Date (dd/mm/yyyy)', 'sureforms' ),
				'{user_id}'                => __( 'User ID', 'sureforms' ),
				'{user_display_name}'      => __( 'User Display Name', 'sureforms' ),
				'{user_first_name}'        => __( 'User First Name', 'sureforms' ),
				'{user_last_name}'         => __( 'User Last Name', 'sureforms' ),
				'{user_email}'             => __( 'User Email', 'sureforms' ),
				'{user_login}'             => __( 'User Username', 'sureforms' ),
				'{browser_name}'           => __( 'Browser Name', 'sureforms' ),
				'{browser_platform}'       => __( 'Browser Platform', 'sureforms' ),
				'{embed_post_id}'          => __( 'Embedded Post/Page ID', 'sureforms' ),
				'{embed_post_title}'       => __( 'Embedded Post/Page Title', 'sureforms' ),
				'{get_input:param}'        => __( 'Populate by GET Param', 'sureforms' ),
				'{get_cookie:cookie_name}' => __( 'Cookie Value', 'sureforms' ),
			]
		);
	}

	/**
	 * Email smart Tag List.
	 *
	 * @since 0.0.1
	 * @return array<mixed>
	 */
	public static function email_smart_tag_list() {
		return [
			'{admin_email}' => __( 'Admin Email', 'sureforms' ),
			'{user_email}'  => __( 'User Email', 'sureforms' ),
		];
	}

	/**
	 * Process Start Tag.
	 *
	 * @param string            $content Form content.
	 * @param array<mixed>|null $submission_data data from submission.
	 * @param array<mixed>|null $form_data data from form.
	 * @since 0.0.1 - return string
	 * @since 1.11.0 - return array<mixed>
	 * @return string|array<mixed>
	 */
	public function process_smart_tags( $content, $submission_data = null, $form_data = null ) {

		if ( ! $content ) {
			return $content;
		}

		preg_match_all( '/{(.*?)}/', $content, $matches );

		if ( empty( $matches[0] ) ) {
			return $content;
		}

		$get_smart_tag_list = self::smart_tag_list();

		foreach ( $matches[0] as $tag ) {
			$is_valid_tag = isset( $get_smart_tag_list[ $tag ] ) ||
			strpos( $tag, 'get_input:' ) ||
			strpos( $tag, 'get_cookie:' ) ||
			0 === strpos( $tag, '{form:' ) ||
			0 === strpos( $tag, '{form-payment:' );

			if ( ! $is_valid_tag ) {
				continue;
			}

			// Get the only smart tag value.
			$smart_tag_value = self::smart_tags_callback( $tag, $submission_data, $form_data );

			/**
			 * Check if smart tag value is an array, which can happen for special field types
			 * like repeater fields that need to preserve their array structure.
			 */
			if ( is_array( $smart_tag_value ) ) {
				/**
				 * Filters whether a smart tag value should be returned as an array.
				 *
				 * Default is false to maintain backwards compatibility.
				 * Filter must explicitly return true to allow array values.
				 *
				 * @since 1.11.0
				 *
				 * @param bool  $is_verified_value Default false
				 * @param array $args {
				 *     Arguments passed to the filter.
				 *
				 *     @type string     $tag             The smart tag being processed
				 *     @type array|null $submission_data The form submission data
				 *     @type array|null $form_data       The form configuration data
				 *     @type mixed      $value           The smart tag value
				 * }
				 */
				$is_verified_value = apply_filters(
					'srfm_is_smart_tag_value_verified_as_array',
					false,
					[
						'tag'             => $tag,
						'submission_data' => $submission_data,
						'form_data'       => $form_data,
						'value'           => $smart_tag_value,
					]
				);

				/**
				 * If filter verification passes, return the original array value.
				 * This allows special field types to maintain their data structure.
				 */
				if ( true === $is_verified_value ) {
					return $smart_tag_value;
				}
			}

			$replace = Helper::get_string_value( $smart_tag_value );
			$content = str_replace( $tag, $replace, $content );
		}

		// Replace <p> around block-level elements with <div>.
		$content = preg_replace(
			'/<p\b([^>]*)>\s*(<(table|div|h[1-6]|ul|ol|style|script)\b)/i',
			'<div$1>$2',
			Helper::get_string_value( $content )
		);

		$content = preg_replace(
			'/(<\/(table|div|h[1-6]|ul|ol|style|script)>)\s*<\/p\b[^>]*>/i',
			'$1</div>',
			Helper::get_string_value( $content )
		);

		return Helper::get_string_value( $content );
	}

	/**
	 *  Smart Tag Callback.
	 *
	 * @param string            $tag smart tag.
	 * @param array<mixed>|null $submission_data data from submission.
	 * @param array<mixed>|null $form_data data from form.
	 * @since 0.0.1
	 * @return mixed
	 */
	public static function smart_tags_callback( $tag, $submission_data = null, $form_data = null ) {
		$parsed_tag = apply_filters( 'srfm_parse_smart_tags', null, compact( 'tag', 'submission_data', 'form_data' ) );

		if ( ! is_null( $parsed_tag ) ) {
			return $parsed_tag;
		}

		switch ( $tag ) {
			case '{site_url}':
				return site_url();

			case '{admin_email}':
				return get_option( 'admin_email' );

			case '{site_title}':
				return get_option( 'blogname' );

			case '{form_title}':
				if ( ! empty( $form_data ) && is_array( $form_data ) && ! empty( $form_data['form-id'] ) ) {
					$id   = absint( $form_data['form-id'] );
					$post = get_post( $id );

					if ( $post instanceof \WP_Post ) {
						return esc_html( $post->post_title ) ?? '';
					}
				}

				return '';

			case '{http_referer}':
				return wp_get_referer();

			case '{ip}':
				return self::get_the_user_ip();

			case '{date_dmy}':
			case '{date_mdy}':
				return self::parse_date( $tag );

			case '{user_id}':
			case '{user_display_name}':
			case '{user_first_name}':
			case '{user_last_name}':
			case '{user_email}':
			case '{user_login}':
				return self::parse_user_props( $tag );

			case '{browser_name}':
			case '{browser_platform}':
				return self::parse_browser_props( $tag );

			case '{embed_post_id}':
			case '{embed_post_title}':
			case '{embed_post_url}':
				return self::parse_post_props( $tag );
			case '{current_page_url}':
				if ( isset( $form_data['_wp_http_referer'] ) ) {
					$request_uri = sanitize_text_field( Helper::get_string_value( wp_unslash( $form_data['_wp_http_referer'] ) ) );
					return esc_url( site_url( $request_uri ) );
				}

				if ( isset( $_SERVER['REQUEST_URI'] ) ) {
					$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
					return esc_url( site_url( $request_uri ) );
				}
				return '';

			default:
				if ( strpos( $tag, 'get_input:' ) || strpos( $tag, 'get_cookie:' ) ) {
					return self::parse_request_param( $tag );
				}

				if ( 0 === strpos( $tag, '{form:' ) ) {
					return self::parse_form_input( $tag, $submission_data, $form_data );
				}

				if ( 0 === strpos( $tag, '{form-payment:' ) ) {
					return self::parse_payment_smart_tag( $tag, $submission_data );
				}
				break;
		}
	}

	/**
	 * Get User IP.
	 *
	 * @since  0.0.1
	 * @return string
	 */
	public static function get_the_user_ip() {
		$ip = '';
		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = filter_var( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ), FILTER_VALIDATE_IP );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = filter_var( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ), FILTER_VALIDATE_IP );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			$ip = filter_var( wp_unslash( $_SERVER['HTTP_X_FORWARDED'] ), FILTER_VALIDATE_IP );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
			$ip = filter_var( wp_unslash( $_SERVER['HTTP_FORWARDED_FOR'] ), FILTER_VALIDATE_IP );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
			$ip = filter_var( wp_unslash( $_SERVER['HTTP_FORWARDED'] ), FILTER_VALIDATE_IP );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP );
		} else {
			$ip = 'UNKNOWN';
		}

		return apply_filters( 'srfm_get_the_ip', $ip );
	}

	/**
	 * Parse Request Query properties.
	 *
	 * @param string $value tag.
	 * @since  0.0.1
	 * @return array<mixed>|string
	 */
	public static function parse_request_param( $value ) {
		if ( ! $value ) {
			return '';
		}

		$exploded = explode( ':', $value );
		$param    = array_pop( $exploded );

		if ( ! $param ) {
			return '';
		}

		$param = str_replace( '}', '', $param );

		if ( $param && strpos( $value, 'get_input:' ) !== false ) {
			$var = '';
			if ( isset( $_SERVER['QUERY_STRING'] ) ) {
				$var = Helper::get_string_value( filter_var( wp_unslash( $_SERVER['QUERY_STRING'] ), FILTER_SANITIZE_URL ) );
			}
			parse_str( $var, $parameters );
			return isset( $parameters[ $param ] ) ? esc_attr( sanitize_text_field( Helper::get_string_value( $parameters[ $param ] ) ) ) : '';
		}

		if ( $param && strpos( $value, 'get_cookie:' ) !== false ) {
			return isset( $_COOKIE[ $param ] ) ? esc_attr( sanitize_text_field( wp_unslash( $_COOKIE[ $param ] ) ) ) : '';
		}

		return '';
	}

	/**
	 * Parse User Input in the Form Submission.
	 *
	 * @param string            $value tag.
	 * @param array<mixed>|null $submission_data data from submission.
	 * @param array<mixed>|null $form_data data from form.
	 * @since  0.0.3
	 * @return mixed
	 */
	public static function parse_form_input( $value, $submission_data = null, $form_data = null ) {

		if ( ! $submission_data && ! $form_data ) {
			return $value;
		}

		if ( ! preg_match( '/\{form:(.*?)}/', $value, $matches ) ) {
			return $value;
		}

		$target_slug      = $matches[1];
		$replacement_data = '';
		if ( ! is_array( $submission_data ) ) {
			return $replacement_data;
		}
		foreach ( $submission_data as $submission_item_key => $submission_item_value ) {
			// If submission item key has not "-lbl-" then continue.
			if ( strpos( $submission_item_key, '-lbl-' ) === false ) {
				continue;
			}

			$label      = explode( '-lbl-', $submission_item_key )[1];
			$slug       = implode( '-', array_slice( explode( '-', $label ), 1 ) );
			$block_type = explode( '-lbl-', $submission_item_key )[0];
			if ( $slug === $target_slug ) {

				/**
				 * Filter to allow external processing of specific block types.
				 *
				 * Allows other components to process certain block types differently by providing
				 * their own processing logic. If a block is processed externally, the filter should
				 * return an array containing 'processed_value'.
				 *
				 * @param array $args {
				 *     Arguments passed to the filter.
				 *
				 *     @type string       $submission_item_key   The key of the current submission item
				 *     @type mixed        $submission_item_value The value of the current submission item
				 *     @type string       $target_slug          The target field slug being processed
				 *     @type string       $block_type           The type of block being processed
				 *     @type array        $form_data            The complete form configuration data
				 *     @type array        $submission_data      The complete form submission data
				 *     @type string       $value                The original smart tag value
				 * }
				 */
				$is_processed_externally = Helper::apply_filters_as_array(
					'srfm_smart_tags_is_block_processed_externally',
					[
						'submission_item_key'   => $submission_item_key,
						'submission_item_value' => $submission_item_value,
						'target_slug'           => $target_slug,
						'block_type'            => $block_type,
						'form_data'             => $form_data,
						'submission_data'       => $submission_data,
						'value'                 => $value,
					]
				);

				if ( isset( $is_processed_externally['processed_value'] ) ) {
					$replacement_data = $is_processed_externally['processed_value'];
					break;
				}

					// if $submission_item_value is an array, make a tag for each item.
				if ( 0 === strpos( $block_type, 'srfm-upload' ) && is_array( $submission_item_value ) ) {
					// Implemented key upload_format_type to determine what to return for urls.
					// for raw urls are return as comma separated strings.
					if ( ! empty( $form_data['upload_format_type'] ) && 'raw' === $form_data['upload_format_type'] ) {
						$replacement_data = urldecode( implode( ', ', $submission_item_value ) );
					} else {
						if ( count( $submission_item_value ) === 1 ) {
							$decoded_value    = urldecode( reset( $submission_item_value ) );
							$replacement_data = '<a rel="noopener noreferrer" href="' . esc_url( $decoded_value ) . '" target="_blank">' . esc_html( $decoded_value ) . '</a>';
						} else {
							$replacement_data = '<ul style="margin: 0;">';
							foreach ( $submission_item_value as $file_url ) {
								$decoded_value     = urldecode( $file_url );
								$replacement_data .= '<li><a rel="noopener noreferrer" href="' . esc_url( $decoded_value ) . '" target="_blank">' . esc_html( $decoded_value ) . '</a></li>';
							}
							$replacement_data .= '</ul>';
						}
					}
				} elseif ( 0 === strpos( $block_type, 'srfm-textarea' ) ) {
					// Special handling for textarea blocks to preserve HTML formatting.
					// Step 1 - Sanitize raw input.
					$sanitized_content = Helper::sanitize_textarea( Helper::get_string_value( $submission_item_value ) );
					// Step 2 - Decode HTML entities to preserve formatting.
					$decoded_content   = html_entity_decode( $sanitized_content );
					$replacement_data .= $decoded_content;
				} else {
					// if $submission_item_value is a url then add <a> tag. with view text.
					if ( is_string( $submission_item_value ) && filter_var( $submission_item_value, FILTER_VALIDATE_URL ) ) {
						ob_start();
						?>
						<a href="<?php echo esc_url( urldecode( $submission_item_value ) ); ?>" target="_blank"><?php echo esc_html( esc_url( $submission_item_value ) ); ?></a>
						<?php
						$view_link = ob_get_clean();

						// Add filter to modify the view link.
						$view_link = apply_filters(
							'srfm_smart_tag_view_link',
							$view_link,
							[
								'submission_item_key'   => $submission_item_key,
								'submission_item_value' => $submission_item_value,
								'target_slug'           => $target_slug,
								'block_type'            => $block_type,
								'form_data'             => $form_data,
								'submission_data'       => $submission_data,
								'value'                 => $value,
							]
						);

						$replacement_data .= $view_link;
					} else {
						$replacement_data .= $submission_item_value;
					}
				}

				break;
			}
		}
		return $replacement_data;
	}

	/**
	 * Parse Payment Smart Tag.
	 *
	 * Parses payment smart tags in the format: {form-payment:slug:property}
	 * where property can be: order-id, amount, email, name, status
	 *
	 * @param string            $value tag.
	 * @param array<mixed>|null $submission_data data from submission.
	 * @since  2.0.0
	 * @return string
	 */
	public static function parse_payment_smart_tag( $value, $submission_data = null ) {
		if ( ! $submission_data ) {
			return '';
		}

		// Extract slug and property from tag: {form-payment:slug:property}.
		if ( ! preg_match( '/\{form-payment:(.*?):(.*?)}/', $value, $matches ) ) {
			return '';
		}

		$target_slug = $matches[1];
		$property    = $matches[2];

		// Valid properties for payment smart tags.
		$valid_properties = [ 'order-id', 'amount', 'email', 'name', 'status' ];
		if ( ! in_array( $property, $valid_properties, true ) ) {
			return '';
		}

		if ( ! is_array( $submission_data ) ) {
			return '';
		}

		// Find payment entry ID from submission data.
		$payment_entry_id = null;

		foreach ( $submission_data as $submission_item_key => $submission_item_value ) {
			// Check if this is a payment field.
			if ( strpos( $submission_item_key, '-lbl-' ) === false ) {
				continue;
			}

			// Split to get block type and label.
			$name_parts = explode( '-lbl-', $submission_item_key );
			if ( count( $name_parts ) < 2 ) {
				continue;
			}

			// Check if it's a payment block.
			if ( strpos( $name_parts[0], 'srfm-payment-' ) !== 0 ) {
				continue;
			}

			// Get the slug from the label part.
			$label = $name_parts[1];
			$slug  = implode( '-', array_slice( explode( '-', $label ), 1 ) );

			// Check if this is the payment block we're looking for.
			if ( $slug === $target_slug ) {
				// The submission value should be the payment entry ID.
				$payment_entry_id = is_numeric( $submission_item_value ) ? intval( $submission_item_value ) : null;
				break;
			}
		}

		// If no payment entry found, return empty.
		if ( ! $payment_entry_id ) {
			return '';
		}

		// Load the Payments class if not already loaded.
		if ( ! class_exists( 'SRFM\Inc\Database\Tables\Payments' ) ) {
			return '';
		}

		// Get payment entry from database.
		$payment_entry = Payments::get( $payment_entry_id );

		if ( ! $payment_entry || ! is_array( $payment_entry ) ) {
			return '';
		}

		// Return the requested property.
		switch ( $property ) {
			case 'order-id':
				// Return formatted order ID: SF-#{srfm_txn_id} or SF-#{id}.
				$order_id = ! empty( $payment_entry['srfm_txn_id'] ) ? $payment_entry['srfm_txn_id'] : $payment_entry['id'];
				$order_id = sanitize_text_field( $order_id );
				return ! empty( $order_id ) ? 'SF-#' . $order_id : '';

			case 'amount':
				// Return formatted amount with currency symbol.
				$amount           = ! empty( $payment_entry['total_amount'] ) ? floatval( $payment_entry['total_amount'] ) : 0;
				$currency         = ! empty( $payment_entry['currency'] ) ? strtoupper( $payment_entry['currency'] ) : 'USD';
				$currency_symbol  = \SRFM\Inc\Payments\Stripe\Stripe_Helper::get_currency_symbol( $currency );
				$formatted_amount = Helper::get_string_value( number_format( $amount, 2 ) );
				return ! empty( $currency_symbol ) ? $currency_symbol . $formatted_amount : $currency . ' ' . $formatted_amount;

			case 'email':
				// Return customer email.
				return ! empty( $payment_entry['customer_email'] ) ? sanitize_email( $payment_entry['customer_email'] ) : '';

			case 'name':
				// Return customer name.
				return ! empty( $payment_entry['customer_name'] ) ? sanitize_text_field( $payment_entry['customer_name'] ) : '';

			case 'status':
				// Return payment status or subscription status based on type.
				if ( 'subscription' === $payment_entry['type'] && ! empty( $payment_entry['subscription_status'] ) ) {
					return sanitize_text_field( $payment_entry['subscription_status'] );
				}
				return ! empty( $payment_entry['status'] ) ? sanitize_text_field( $payment_entry['status'] ) : '';

			default:
				return '';
		}
	}

	/**
	 * Parse Date Properties.
	 *
	 * @param string $value date tag.
	 * @since  0.0.1
	 * @return string
	 */
	private static function parse_date( $value ) {

		$format = '';

		if ( '{date_mdy}' === $value ) {
			$format = 'm/d/Y';
		}

		if ( '{date_dmy}' === $value ) {
			$format = 'd/m/Y';
		}

		$date = gmdate( $format, Helper::get_integer_value( strtotime( current_time( 'mysql' ) ) ) );
		return $date ? $date : '';
	}

	/**
	 * Parse user properties.
	 *
	 * @param string $value user tag.
	 * @since  0.0.1
	 * @return mixed
	 */
	private static function parse_user_props( $value ) {
		$user = wp_get_current_user();

		$user_info = get_user_meta( $user->ID );

		if ( ! $user_info ) {
			return '';
		}

		if ( '{user_id}' === $value ) {
			return null !== $user->ID ? $user->ID : '';
		}

		if ( '{user_display_name}' === $value ) {
			return esc_attr( $user->data->display_name ?? '' );
		}

		if ( '{user_first_name}' === $value ) {
			return is_array( $user_info ) && isset( $user_info['first_name'][0] ) ? esc_attr( $user_info['first_name'][0] ) : '';
		}

		if ( '{user_last_name}' === $value ) {
			return is_array( $user_info ) && isset( $user_info['last_name'][0] ) ? esc_attr( $user_info['last_name'][0] ) : '';
		}

		if ( '{user_email}' === $value ) {
			return $user->data->user_email ?? '';
		}

		if ( '{user_login}' === $value ) {
			return $user->data->user_login ?? '';
		}

		return '';
	}

	/**
	 * Parse Post properties.
	 *
	 * @param string $value post tag.
	 * @since  0.0.1
	 * @return string
	 */
	private static function parse_post_props( $value ) {
		global $post;

		if ( ! $post ) {
			return '';
		}

		if ( '{embed_post_url}' === $value && isset( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			return esc_url( site_url( $request_uri ) );
		}

		if ( '{embed_post_title}' === $value ) {
			$value = 'post_title';
		}

		if ( '{embed_post_id}' === $value ) {
			$value = 'ID';
		}

		if ( property_exists( $post, $value ) ) {
			return $post->{$value};
		}

		return '';
	}

	/**
	 * Parse browser/user-agent properties.
	 *
	 * @param string $value browser tag.
	 * @since  0.0.1
	 * @return string
	 */
	private static function parse_browser_props( $value ) {
		$browser = new Browser();

		if ( '{browser_name}' === $value ) {
			return sanitize_text_field( $browser->getBrowser() );
		}

		if ( '{browser_platform}' === $value ) {
			return sanitize_text_field( $browser->getPlatform() );
		}

		return '';
	}
}
