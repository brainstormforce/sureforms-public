<?php
/**
 * Editor Nudge.
 *
 * Marketing/education banner shown inside the block editor when a user is
 * editing a post/page whose title hints at a form ("contact", "form",
 * "forms"). Encourages them to create a SureForms form.
 *
 * Script is enqueued when:
 *  - User can manage SureForms forms (the `sureforms_form` CPT requires
 *    `manage_options` for create/edit, so the nudge is gated on the same
 *    cap — otherwise we would surface a "Create Form" CTA to users who
 *    cannot reach the form-creation flow).
 *  - Current screen is the block editor and not the SureForms form CPT.
 *  - The current post does not have an active dismissal recorded against
 *    it (dismissals are stored as post meta so each post tracks its own
 *    state — dismissing on one page does not silence the nudge on others).
 *
 * The remaining rule — "current post must not already contain a srfm/form
 * block" — is evaluated in JS against live editor state.
 *
 * @package sureforms.
 */

namespace SRFM\Inc\Admin;

use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Editor nudge handler.
 *
 * @since 2.8.2
 */
class Editor_Nudge {
	use Get_Instance;

	/**
	 * Post meta key that stores the dismissed timestamp for a given post.
	 *
	 * Per-post (rather than per-user) so dismissing the nudge on one
	 * page does not silence it on every other page in the same site.
	 *
	 * @since 2.8.2
	 */
	public const DISMISS_META_KEY = 'srfm_editor_nudge_dismissed';

	/**
	 * Nonce action used by the dismiss AJAX endpoint.
	 *
	 * @since 2.8.2
	 */
	public const NONCE_ACTION = 'srfm_editor_nudge_dismiss';

	/**
	 * How long a dismissal suppresses the nudge on a single post, in seconds.
	 *
	 * Stored as a timestamp in post meta; once `time() - dismissed_at`
	 * exceeds this value the nudge can surface again on that post.
	 * Prevents a single mis-click from hiding the banner forever on a
	 * post with no recovery path.
	 *
	 * @since 2.8.2
	 */
	public const DISMISS_EXPIRY_SECONDS = 90 * DAY_IN_SECONDS;

	/**
	 * Constructor.
	 *
	 * @since 2.8.2
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_srfm_editor_nudge_dismiss', [ $this, 'handle_dismiss' ] );
	}

	/**
	 * Decide whether the nudge script should be loaded for the current request.
	 *
	 * @since 2.8.2
	 * @return bool
	 */
	public function allow_load() {
		if ( ! is_admin() ) {
			return false;
		}

		// Gate on the cap required to actually manage SureForms forms
		// (`manage_options` per the CPT registration in inc/post-types.php).
		// Pass the cap explicitly rather than relying on the helper default
		// so the rule survives any future change to Helper::current_user_can().
		// Editor / Author / Subscriber roles do NOT have manage_options on a
		// stock WordPress install, so they will always fail this check.
		if ( ! Helper::current_user_can( 'manage_options' ) ) {
			return false;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! method_exists( $screen, 'is_block_editor' ) || ! $screen->is_block_editor() ) {
			return false;
		}

		// Skip on the SureForms form editor itself.
		if ( SRFM_FORMS_POST_TYPE === $screen->post_type ) {
			return false;
		}

		// Skip if the current post has an active dismissal recorded against it.
		// Best-effort — when no post is in scope (e.g. the Site Editor) we
		// still enqueue and let the JS layer surface or hide based on live
		// editor state.
		$post_id = $this->get_current_post_id();
		if ( $post_id > 0 && $this->is_dismissal_active( $post_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Whether the given post has an active dismissal within the expiry window.
	 *
	 * Treats any non-numeric / zero / future-timestamp value as inactive,
	 * and rolls over once the recorded timestamp is older than
	 * `DISMISS_EXPIRY_SECONDS`.
	 *
	 * @since 2.8.2
	 * @param int $post_id Post ID to check.
	 * @return bool
	 */
	public function is_dismissal_active( $post_id ) {
		$dismissed_at = Helper::get_integer_value( get_post_meta( $post_id, self::DISMISS_META_KEY, true ) );

		if ( $dismissed_at <= 0 ) {
			return false;
		}

		return time() - $dismissed_at < self::DISMISS_EXPIRY_SECONDS;
	}

	/**
	 * Enqueue the nudge script when allowed.
	 *
	 * @since 2.8.2
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! $this->allow_load() ) {
			return;
		}

		$handle     = SRFM_SLUG . '-editor-nudge';
		$asset_path = SRFM_DIR . 'assets/build/editorNudge.asset.php';
		$asset      = file_exists( $asset_path )
			? include $asset_path
			: [
				'dependencies' => [ 'wp-data', 'wp-i18n', 'wp-dom-ready' ],
				'version'      => SRFM_VER,
			];

		wp_enqueue_script(
			$handle,
			SRFM_URL . 'assets/build/editorNudge.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';

		wp_enqueue_style(
			$handle,
			SRFM_URL . 'assets/css/' . $dir_name . '/editor-nudge' . $file_prefix . '.css',
			[],
			SRFM_VER
		);

		wp_localize_script(
			$handle,
			'srfm_editor_nudge',
			[
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( self::NONCE_ACTION ),
				'create_form_url' => admin_url( 'admin.php?page=add-new-form' ),
				'message'         => __( 'Hey! It looks like you\'re creating a form. Build a ready-to-use form in under 30 seconds with SureForms AI, with no extra setup required.', 'sureforms' ),
				'button_label'    => __( 'Create Form', 'sureforms' ),
				'logo_url'        => SRFM_URL . 'admin/assets/sureforms-logo.png',
			]
		);

		Helper::register_script_translations( $handle );
	}

	/**
	 * AJAX handler to persist the dismissed state for the post being edited.
	 *
	 * @since 2.8.2
	 * @return void
	 */
	public function handle_dismiss() {
		if ( ! Helper::current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You are not allowed to perform this action.', 'sureforms' ) ],
				403
			);
		}

		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Invalid security token.', 'sureforms' ) ],
				400
			);
		}

		$post_id = isset( $_POST['post_id'] )
			? Helper::get_integer_value( sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) )
			: 0;

		$post = $post_id > 0 ? get_post( $post_id ) : null;
		if ( ! $post ) {
			wp_send_json_error(
				[ 'message' => __( 'Invalid post.', 'sureforms' ) ],
				400
			);
		}

		// The nudge never surfaces on the SureForms form CPT, so dismissals
		// for it are never legitimate — reject before touching post meta.
		if ( SRFM_FORMS_POST_TYPE === $post->post_type ) {
			wp_send_json_error(
				[ 'message' => __( 'Invalid post.', 'sureforms' ) ],
				400
			);
		}

		// Granular per-post authorization — `manage_options` alone is not
		// enough; the requesting user must also be able to edit THIS post.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You cannot edit this post.', 'sureforms' ) ],
				403
			);
		}

		update_post_meta( $post_id, self::DISMISS_META_KEY, time() );

		wp_send_json_success();
	}

	/**
	 * Resolve the post ID being edited on the current admin request.
	 *
	 * @since 2.8.2
	 * @return int Post ID, or 0 when no post is in scope.
	 */
	protected function get_current_post_id() {
		global $post;
		if ( $post instanceof \WP_Post && $post->ID > 0 ) {
			return (int) $post->ID;
		}
		return 0;
	}
}
