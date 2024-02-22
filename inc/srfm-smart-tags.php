<?php
/**
 * Sureforms Smart Tags.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SureForms\Inc;

use SureForms\Inc\Traits\Get_Instance;
use SureForms\Inc\Lib\Browser\Browser;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sureforms Smart Tags Class.
 *
 * @since 0.0.1
 */
class SRFM_Smart_Tags {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_filter( 'render_block', array( $this, 'render_form' ), 10, 2 );
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

		if ( self::check_form_by_id( $id ) ) {
			return self::process_smart_tags( $block_content );
		}

		if ( isset( $block['blockName'] ) && ( 'sureforms/form' === $block['blockName'] ) ) {
			if ( isset( $block['attrs']['id'] ) && $block['attrs']['id'] ) {
				return self::process_smart_tags( $block_content );
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
		/** @phpstan-ignore-next-line */ // phpcs:ignore -- False positive
		return get_post_type( $id ) ? true : false;
	}

	/**
	 * Smart Tag List.
	 *
	 * @since 0.0.1
	 * @return array<mixed>
	 */
	public static function smart_tag_list() {
		return [
			'{site_url}'               => __( 'Site URL', 'sureforms' ),
			'{admin_email}'            => __( 'Admin Email', 'sureforms' ),
			'{site_title}'             => __( 'Site Title', 'sureforms' ),
			'{ip}'                     => __( 'IP Address', 'sureforms' ),
			'{http_referer}'           => __( 'HTTP Referer URL', 'sureforms' ),
			'{date_mdy}'               => __( 'Date (mm/dd/yyyy)', 'sureforms' ),
			'{date_dmy}'               => __( 'Date (dd/mm/yyyy', 'sureforms' ),
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
		];
	}

	/**
	 * Process Start Tag.
	 *
	 * @param string $content Form content.
	 * @since 0.0.1
	 * @return string
	 */
	public function process_smart_tags( $content ) {

		if ( ! $content ) {
			return $content;
		}

		$get_smart_tag_list = self::smart_tag_list();
		preg_match_all( '/{(.*?)}/', $content, $matches );

		if ( ! $matches[0] ) {
			return $content;
		}

		foreach ( $matches[0] as $match ) {

			$replace = '';
			if ( isset( $get_smart_tag_list[ $match ] ) || strpos( $match, 'get_input:' ) || strpos( $match, 'get_cookie:' ) ) {
				$replace = self::smart_tags_callback( $match );
			}

			/** @phpstan-ignore-next-line */ // phpcs:ignore -- False positive
			$content = str_replace( $match, $replace, $content );
		}

		return $content;
	}

	/**
	 *  Smart Tag Callback.
	 *
	 * @param string $tags smart tag.
	 * @since 0.0.1
	 * @return mixed
	 */
	public function smart_tags_callback( $tags ) {

		if ( '{site_url}' === $tags ) {
			return site_url();
		}

		if ( '{admin_email}' === $tags ) {
			return get_option( 'admin_email' );
		}

		if ( '{site_title}' === $tags ) {
			return get_option( 'blogname' );
		}

		if ( '{http_referer}' === $tags ) {
			return wp_get_referer();
		}

		if ( '{ip}' === $tags ) {
			return self::get_the_user_ip();
		}

		if ( '{date_dmy}' === $tags ) {
			return self::parse_date( $tags );
		}

		if ( '{date_mdy}' === $tags ) {
			return self::parse_date( $tags );
		}

		if ( '{user_id}' === $tags ) {
			return self::parse_user_props( $tags );
		}

		if ( '{user_display_name}' === $tags ) {
			return self::parse_user_props( $tags );
		}

		if ( '{user_first_name}' === $tags ) {
			return self::parse_user_props( $tags );
		}

		if ( '{user_last_name}' === $tags ) {
			return self::parse_user_props( $tags );
		}

		if ( '{user_email}' === $tags ) {
			return self::parse_user_props( $tags );
		}

		if ( '{user_login}' === $tags ) {
			return self::parse_user_props( $tags );
		}

		if ( '{browser_name}' === $tags ) {
			return self::parse_browser_props( $tags );
		}

		if ( '{browser_platform}' === $tags ) {
			return self::parse_browser_props( $tags );
		}

		if ( '{embed_post_id}' === $tags ) {
			return self::parse_post_props( $tags );
		}

		if ( '{embed_post_title}' === $tags ) {
			return self::parse_post_props( $tags );
		}

		if ( '{embed_post_url}' === $tags ) {
			return self::parse_post_props( $tags );
		}

		if ( strpos( $tags, 'get_input:' ) ) {
			return self::parse_request_param( $tags );
		}

		if ( strpos( $tags, 'get_cookie:' ) ) {
			return self::parse_request_param( $tags );
		}
	}

	/**
	 * Get User IP.
	 *
	 * @since  0.0.1
	 * @return string
	 */
	public function get_the_user_ip() {

		$ip = '';
		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED'];
		} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_FORWARDED_FOR'];
		} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
			$ip = $_SERVER['HTTP_FORWARDED'];
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			$ip = 'UNKNOWN';
		}

		return apply_filters( 'sureforms_get_the_ip', $ip );
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

		/** @phpstan-ignore-next-line */  // phpcs:ignore -- False positive
		$date = gmdate( $format, strtotime( current_time( 'mysql' ) ) );
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
			return ( null !== $user->ID ) ? $user->ID : '';
		}

		if ( '{user_display_name}' === $value ) {
			return isset( $user->data->display_name ) ? $user->data->display_name : '';
		}

		if ( '{user_first_name}' === $value ) {
			return ( is_array( $user_info ) && isset( $user_info['first_name'][0] ) ) ? $user_info['first_name'][0] : '';
		}

		if ( '{user_last_name}' === $value ) {
			return ( is_array( $user_info ) && isset( $user_info['last_name'][0] ) ) ? $user_info['last_name'][0] : '';
		}

		if ( '{user_email}' === $value ) {
			return isset( $user->data->user_email ) ? $user->data->user_email : '';
		}

		if ( '{user_login}' === $value ) {
			return isset( $user->data->user_login ) ? $user->data->user_login : '';
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
			return site_url( esc_attr( $_SERVER['REQUEST_URI'] ) );
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
			return $browser->getBrowser();
		}

		if ( '{browser_platform}' === $value ) {
			return $browser->getPlatform();
		}

		return '';
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

		if ( $param && strpos( $value, 'get_input:' ) ) {
			parse_str( $_SERVER['QUERY_STRING'], $parameters );
			return isset( $parameters[ $param ] ) ? $parameters[ $param ] : '';
		}

		if ( $param && strpos( $value, 'get_cookie:' ) ) {
			return isset( $_COOKIE[ $param ] ) ? $_COOKIE[ $param ] : '';
		}

		return '';
	}
}
