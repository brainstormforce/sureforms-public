<?php
/**
 * Add New Form start screen.
 *
 * The first step of Add New Form asks how to start: create with AI, choose a
 * template, or build from scratch. Forms created after that choice carry it
 * in `_srfm_start_choice`, and each step is counted for the
 * `form_start_funnel` event, so the share of each path and how far it gets
 * (created, published, receiving entries) can be read from the warehouse.
 *
 * @package sureforms
 * @since 2.13.0
 */

namespace SRFM\Inc;

use SRFM\Admin\Analytics;
use SRFM\Inc\Traits\Get_Instance;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add New Form start screen.
 *
 * @since 2.13.0
 */
class Form_Start {
	use Get_Instance;

	/**
	 * Meta key recording the start screen choice a form was created after.
	 * The way it was actually created can differ (picked AI, ran out of
	 * credits, used a template), which `_srfm_created_via` records.
	 *
	 * @since 2.13.0
	 */
	public const START_CHOICE_META = '_srfm_start_choice';

	/**
	 * Ways to start a form.
	 *
	 * @since 2.13.0
	 */
	public const CHOICES = [ 'ai', 'template', 'scratch' ];

	/**
	 * Steps inside a path that are counted while a choice is in play.
	 *
	 * @since 2.13.0
	 */
	public const STEPS = [ 'ai_generate', 'ai_error', 'ai_limit', 'template_open', 'template_preview' ];

	/**
	 * Query arg on post-new.php carrying the start screen choice for a blank form.
	 *
	 * @since 2.13.0
	 */
	public const SCRATCH_QUERY_ARG = 'srfm_start';

	/**
	 * Constructor
	 *
	 * @since 2.13.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'wp_insert_post', [ $this, 'tag_blank_form' ], 10, 3 );
		add_action( 'transition_post_status', [ $this, 'count_blank_form' ], 10, 3 );
	}

	/**
	 * Register the REST route the start screen reports its steps to.
	 *
	 * @since 2.13.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'sureforms/v1',
			'/form-start/track',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_track' ],
				'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				'args'                => [
					'action'   => [
						'type'     => 'string',
						'required' => true,
						'enum'     => array_merge( [ 'view', 'choose' ], self::STEPS ),
					],
					'choice'   => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
					'position' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);
	}

	/**
	 * Whether Add New Form opens on the start screen. When filtered off it
	 * opens on the AI screen, as before the start screen existed.
	 *
	 * @since 2.13.0
	 * @return bool
	 */
	public static function is_enabled() {
		/**
		 * Filters whether Add New Form opens on the start screen.
		 *
		 * @param bool $enabled Whether the start screen is shown.
		 * @since 2.13.0
		 */
		return (bool) apply_filters( 'srfm_show_form_start_screen', true );
	}

	/**
	 * Start screen settings for the Add New Form script.
	 *
	 * @since 2.13.0
	 * @return array{enabled: bool, tracking: bool}
	 */
	public static function get_settings_for_js() {
		return [
			'enabled'  => self::is_enabled(),
			'tracking' => Form_Templates::is_usage_sharing_on(),
		];
	}

	/**
	 * A known choice, or '' for anything else.
	 *
	 * @param mixed $choice Raw choice.
	 * @since 2.13.0
	 * @return string
	 */
	public static function sanitize_choice( $choice ) {
		$choice = sanitize_key( Helper::get_string_value( $choice ) );
		return in_array( $choice, self::CHOICES, true ) ? $choice : '';
	}

	/**
	 * POST /form-start/track
	 *
	 * Counts a start screen view, a choice (with the position its card was
	 * shown in), or a step inside a path.
	 *
	 * @param WP_REST_Request $request Request.
	 * @since 2.13.0
	 * @return WP_REST_Response
	 */
	public function rest_track( $request ) {
		if ( ! Form_Templates::is_usage_sharing_on() ) {
			return new WP_REST_Response( [ 'recorded' => false ] );
		}

		$recorded = Analytics::track_form_start_action(
			Helper::get_string_value( $request->get_param( 'action' ) ),
			self::sanitize_choice( $request->get_param( 'choice' ) ),
			Helper::get_string_value( $request->get_param( 'position' ) )
		);

		return new WP_REST_Response( [ 'recorded' => $recorded ] );
	}

	/**
	 * Record the start screen choice on a form created by AI or from a
	 * template, and count it as created for that choice.
	 *
	 * @param int    $post_id     New form ID.
	 * @param mixed  $choice      Start screen choice sent with the request.
	 * @param string $created_via How the form was actually created (`ai`, `template`).
	 * @since 2.13.0
	 * @return void
	 */
	public static function record_created( $post_id, $choice, $created_via ) {
		$choice = self::sanitize_choice( $choice );
		if ( '' === $choice || $post_id <= 0 ) {
			return;
		}

		update_post_meta( $post_id, self::START_CHOICE_META, $choice );
		Analytics::track_form_start_created( $choice, $created_via );
	}

	/**
	 * Tag a blank form when post-new.php creates it: `_srfm_created_via` =
	 * `scratch`, plus the start screen choice when the link carried one.
	 *
	 * WordPress inserts the form as an auto-draft as soon as the editor opens,
	 * and deletes it later if it is never saved, so it is only counted as
	 * created once it is saved (see count_blank_form()).
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 * @param bool    $update  Whether this is an update.
	 * @since 2.13.0
	 * @return void
	 */
	public function tag_blank_form( $post_id, $post, $update ) {
		global $pagenow;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check that this is the post-new.php screen for forms.
		$screen_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		if ( $update || 'post-new.php' !== $pagenow || SRFM_FORMS_POST_TYPE !== $screen_type || SRFM_FORMS_POST_TYPE !== $post->post_type || 'auto-draft' !== $post->post_status ) {
			return;
		}

		update_post_meta( $post_id, Form_Templates::CREATED_VIA_META, 'scratch' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tag for analytics; only a known choice is stored.
		$choice = isset( $_GET[ self::SCRATCH_QUERY_ARG ] ) ? self::sanitize_choice( sanitize_key( wp_unslash( $_GET[ self::SCRATCH_QUERY_ARG ] ) ) ) : '';
		if ( '' !== $choice ) {
			update_post_meta( $post_id, self::START_CHOICE_META, $choice );
		}
	}

	/**
	 * Count a blank form from the start screen as created the first time it
	 * is saved (leaves auto-draft).
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       Post.
	 * @since 2.13.0
	 * @return void
	 */
	public function count_blank_form( $new_status, $old_status, $post ) {
		if ( 'auto-draft' !== $old_status || in_array( $new_status, [ 'auto-draft', 'inherit', 'trash' ], true ) || SRFM_FORMS_POST_TYPE !== $post->post_type ) {
			return;
		}

		$choice = self::sanitize_choice( get_post_meta( $post->ID, self::START_CHOICE_META, true ) );
		if ( '' === $choice || 'scratch' !== get_post_meta( $post->ID, Form_Templates::CREATED_VIA_META, true ) ) {
			return;
		}

		Analytics::track_form_start_created( $choice, 'scratch' );
	}
}
