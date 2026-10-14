<?php
/**
 * Sureforms Generate Form Class file.
 *
 * @package sureforms.
 * @since 0.0.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager;
use SRFM\Inc\Compatibility\Multilingual\String_Translator;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Load Defaults Class.
 *
 * @since 0.0.1
 */
class Generate_Form_Markup {
	use Get_Instance;

	/**
	 * Query arg marking an editor visit as arriving from the front-end "Edit Form"
	 * pill, so the click can be attributed without any front-end JavaScript.
	 *
	 * @since 2.12.6
	 */
	public const EDIT_FORM_BUTTON_SOURCE_ARG = 'srfm_edit_src';

	/**
	 * Current block attributes for the form being rendered.
	 * Used by child blocks (like inline button) to access parent form's embed styling.
	 *
	 * @var array<string,mixed>
	 * @since 2.7.0
	 */
	private static $current_block_attrs = [];

	/**
	 * ID of the form whose content get_form_markup() is rendering, or 0 outside one.
	 *
	 * Smart_Tags::render_form() resolves smart tags only inside this content.
	 *
	 * @var int
	 * @since 2.12.9
	 */
	private static $rendering_form_id = 0;

	/**
	 * IDs of the forms known to be on the current request, keyed by form ID.
	 *
	 * Seeded at the `wp` hook (collect_queried_form_ids(), before any output) by
	 * parsing the queried post, and added to at render time by get_form_markup().
	 * The seed is load-bearing: on modern themes the admin bar renders at
	 * wp_body_open (priority 0) — BEFORE the_content — so the render-time registry
	 * alone would be empty when the node is built.
	 *
	 * @var array<int,bool>
	 * @since 2.12.3
	 */
	private static $rendered_form_ids = [];

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_custom_endpoint' ] );
		// Seed the form registry from the queried post before any output, so the
		// admin bar (which renders at wp_body_open, before the_content) has the list.
		add_action( 'wp', [ $this, 'collect_queried_form_ids' ] );
		// Frontend admin-bar "Entries" deep-link. Priority 100 mirrors the
		// existing "Edit Form" node in Post_Types.
		add_action( 'admin_bar_menu', [ $this, 'add_entries_admin_bar_node' ], 100 );
	}

	/**
	 * Seed the rendered-form registry from the queried singular post's content,
	 * before any output.
	 *
	 * The admin bar renders at wp_body_open (priority 0) on modern themes — before
	 * the_content — so relying on the render-time registry alone would leave the
	 * node empty on essentially every embed. Parsing the queried post here (srfm/form
	 * blocks incl. reusable/synced patterns, and [sureforms] shortcodes, via the
	 * shared Form_Styling helper) covers those; get_form_markup() then adds anything
	 * a static parse can't see (page builders, FSE template parts).
	 *
	 * @since 2.12.3
	 * @return void
	 */
	public function collect_queried_form_ids() {
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		// The only consumer is the admin-bar node, which bails for anyone without
		// manage_options. Without this guard every anonymous front-end request ran
		// parse_blocks() plus recursive get_post() expansion of synced patterns for a
		// feature it could never see. The current user is already resolved at `wp`.
		if ( ! is_admin_bar_showing() || ! Helper::current_user_can() ) {
			return;
		}

		$post_id = absint( get_queried_object_id() );
		if ( 0 === $post_id ) {
			return;
		}

		// 'raw' context: the default 'display' context applies the post_content filter,
		// so the parsed list could disagree with Form_Styling::should_skip_frontend_styles(),
		// which reads raw.
		$content = Helper::get_string_value( get_post_field( 'post_content', $post_id, 'raw' ) );
		foreach ( Form_Styling::get_form_ids_from_content( $content ) as $form_id ) {
			$fid = absint( $form_id );
			if ( $fid > 0 ) {
				self::$rendered_form_ids[ $fid ] = true;
			}
		}
	}

	/**
	 * Get the current block attributes.
	 *
	 * @return array<string,mixed>
	 * @since 2.7.0
	 */
	public static function get_current_block_attrs() {
		return self::$current_block_attrs;
	}

	/**
	 * Get the ID of the form whose content is being rendered.
	 *
	 * @return int Form ID, or 0 when no form content is rendering.
	 * @since 2.12.9
	 */
	public static function get_rendering_form_id() {
		return self::$rendering_form_id;
	}

	/**
	 * Add an "Entries" node to the frontend admin bar on any page that contains a
	 * SureForms form, deep-linking to the Entries admin page pre-filtered to that
	 * form. The form list comes from collect_queried_form_ids() (seeded at `wp`)
	 * plus the render-time registry.
	 *
	 * ACTUAL COVERAGE: srfm/form blocks, synced/reusable patterns (core/block) and
	 * [sureforms] shortcodes in the queried post's content, plus a singular form CPT
	 * page. Page builders that store layout outside post_content (Elementor in
	 * _elementor_data, Bricks in _bricks_page_content_*) and FSE template parts are
	 * NOT covered: the render-time registry is written during the_content, which on
	 * block themes runs after wp_admin_bar_render() at wp_body_open, so the node is
	 * already built. On classic themes those paths happen to work via core's wp_footer
	 * fallback, which makes the feature silently theme-dependent. Use the
	 * `srfm_admin_bar_entries_form_ids` filter to contribute builder-sourced IDs until
	 * early builder detection lands. With multiple forms the node becomes a
	 * submenu (one child per form); the parent then links to the unfiltered page.
	 *
	 * Runs on admin_bar_menu, which fires as the bar renders (wp_body_open on modern
	 * themes). Gated to users who can view the Entries page (the same
	 * `manage_options` capability the admin page and entries REST endpoints use).
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The admin bar instance.
	 * @since 2.12.3
	 * @return void
	 */
	public function add_entries_admin_bar_node( $wp_admin_bar ) {
		// Frontend only, and only when the bar is actually shown for this user.
		if ( is_admin() || ! is_admin_bar_showing() || ! $wp_admin_bar instanceof \WP_Admin_Bar ) {
			return;
		}

		// Match who can view entries (admin page + entries REST capability).
		if ( ! Helper::current_user_can() ) {
			return;
		}

		$form_ids = array_map( 'absint', array_keys( self::$rendered_form_ids ) );

		// Fallback for a form's own singular page if nothing was recorded.
		if ( empty( $form_ids ) && is_singular( SRFM_FORMS_POST_TYPE ) ) {
			$singular_id = absint( get_the_ID() );
			if ( $singular_id > 0 ) {
				$form_ids[] = $singular_id;
			}
		}

		/**
		 * Filter the form IDs offered in the admin-bar Entries node. Lets sources a
		 * content parse / render can't see contribute — Elementor (_elementor_data),
		 * Bricks (_bricks_page_content_*), FSE template parts, or Pro's
		 * [srfm_show_entries] shortcode.
		 *
		 * @since 2.12.3
		 * @param array<int> $form_ids Form IDs detected on the current request.
		 */
		$form_ids = array_map( 'absint', (array) apply_filters( 'srfm_admin_bar_entries_form_ids', $form_ids ) );

		// Keep only real SureForms forms. The [sureforms] shortcode accepts any
		// published post ID, so esc_html() below must not be the only barrier
		// against a hostile post title (e.g. authored by an Editor with unfiltered_html).
		$form_ids = array_values(
			array_unique(
				array_filter(
					$form_ids,
					static function ( $fid ) {
						return $fid > 0 && SRFM_FORMS_POST_TYPE === get_post_type( $fid );
					}
				)
			)
		);
		if ( empty( $form_ids ) ) {
			return;
		}

		$entries_base = admin_url( 'admin.php?page=' . SRFM_ENTRIES );
		$node_id      = 'srfm-entries';
		$icon         = '<span class="ab-icon dashicons dashicons-list-view" style="line-height:1.2;margin-right:4px;"></span>';

		// Single form — link straight to its filtered entries.
		if ( 1 === count( $form_ids ) ) {
			$wp_admin_bar->add_node(
				[
					'id'    => $node_id,
					'title' => $icon . '<span class="ab-label">' . esc_html__( 'Entries', 'sureforms' ) . '</span>',
					'href'  => esc_url( $entries_base . '#/?form=' . $form_ids[0] ),
					// Core esc_attr()s meta['title'], so pass it unescaped here.
					'meta'  => [ 'title' => __( 'View entries for this form', 'sureforms' ) ],
				]
			);
			return;
		}

		// Multiple forms — parent links to unfiltered Entries, one child per form.
		$wp_admin_bar->add_node(
			[
				'id'    => $node_id,
				'title' => $icon . '<span class="ab-label">' . esc_html__( 'Entries', 'sureforms' ) . '</span>',
				'href'  => esc_url( $entries_base ),
				'meta'  => [ 'title' => __( 'View form entries', 'sureforms' ) ],
			]
		);

		// Cap the submenu; the parent's unfiltered link covers the overflow so a page
		// with many forms can't blow past the (non-scrolling) admin bar.
		foreach ( array_slice( $form_ids, 0, 10 ) as $form_id ) {
			$title = get_the_title( $form_id );
			// get_the_title() runs the_title filters that may inject markup, and
			// WP_Admin_Bar does not escape node titles — strip tags and escape here.
			$title = '' !== $title
				? esc_html( wp_strip_all_tags( $title ) )
				/* translators: %d: form ID. */
				: esc_html( sprintf( __( 'Form #%d', 'sureforms' ), $form_id ) );

			$wp_admin_bar->add_node(
				[
					'id'     => $node_id . '-' . $form_id,
					'parent' => $node_id,
					'title'  => $title,
					'href'   => esc_url( $entries_base . '#/?form=' . $form_id ),
				]
			);
		}
	}

	/**
	 * Add custom API Route to generate form markup.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register_custom_endpoint() {
		register_rest_route(
			'sureforms/v1',
			'/generate-form-markup',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'render_form_markup_endpoint' ],
				'permission_callback' => [ $this, 'render_form_markup_permissions_check' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => static function ( $value ) {
							return absint( $value ) > 0;
						},
					],
				],
			]
		);
	}

	/**
	 * Permission check for the form-markup endpoint.
	 *
	 * The endpoint exists for one purpose: rendering the editor preview when a user
	 * picks a form in the srfm/form block. So the caller must at least be able to
	 * edit content. A nonce is not sufficient — `srfm_form_markup` is minted in
	 * enqueue_block_editor_assets, so passing it proves only that the caller reached
	 * the editor, never what they are allowed to read.
	 *
	 * @since 2.12.3
	 * @return bool|\WP_Error True when allowed, WP_Error otherwise.
	 */
	public function render_form_markup_permissions_check() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error(
				'srfm_rest_cannot_render_form',
				__( 'Sorry, you are not allowed to render form markup.', 'sureforms' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Render the requested form for the block-editor preview.
	 *
	 * Constrains the requested ID to a SureForms form, and to one the caller is
	 * allowed to see: published forms are already public, anything else (draft,
	 * pending, private, trashed) needs the SureForms forms capability.
	 *
	 * @param \WP_REST_Request<array<string,mixed>> $request REST request.
	 *
	 * @since 2.12.3
	 * @return string|\WP_Error Form markup, or WP_Error when the form is not renderable for this caller.
	 */
	public function render_form_markup_endpoint( $request ) {
		$form_id = Helper::get_integer_value( $request->get_param( 'id' ) );
		$form    = $form_id > 0 ? get_post( $form_id ) : null;

		if ( ! $form instanceof \WP_Post || SRFM_FORMS_POST_TYPE !== $form->post_type ) {
			return new \WP_Error(
				'srfm_rest_form_not_found',
				__( 'No form was found with the given ID.', 'sureforms' ),
				[ 'status' => 404 ]
			);
		}

		if ( 'publish' !== $form->post_status && ! Helper::current_user_can() ) {
			return new \WP_Error(
				'srfm_rest_cannot_render_form',
				__( 'Sorry, you are not allowed to render this form.', 'sureforms' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return Helper::get_string_value( self::get_form_markup( $form_id ) );
	}

	/**
	 * Handle Form status
	 *
	 * @param int|string   $id Contains form ID.
	 * @param bool         $show_title_current_page Boolean to srfm-show/srfm-hide form title.
	 * @param string       $sf_classname additional class_name.
	 * @param string       $post_type Contains post type.
	 * @param bool         $do_blocks Boolean to enable/disable parsing dynamic blocks.
	 * @param array<mixed> $block_attrs Block attributes for per-embed styling.
	 *
	 * @return string|false
	 * @since 0.0.1
	 */
	public static function get_form_markup( $id, $show_title_current_page = true, $sf_classname = '', $post_type = 'post', $do_blocks = false, $block_attrs = [] ) {
		// Everything rendered here, including blocks rendered from hooks such as page break
		// and conversational forms, is form content: smart tags resolve in it. Only a real
		// form post qualifies, since get_post() accepts any post ID.
		$form_id                 = Helper::get_integer_value( $id );
		$previous_form_id        = self::$rendering_form_id;
		self::$rendering_form_id = $form_id > 0 && SRFM_FORMS_POST_TYPE === get_post_type( $form_id ) ? $form_id : 0;

		try {
			return self::render_form_markup( $id, $show_title_current_page, $sf_classname, $post_type, $do_blocks, $block_attrs );
		} finally {
			self::$rendering_form_id = $previous_form_id;
		}
	}

	/**
	 * Render the form markup for get_form_markup().
	 *
	 * Internal: call get_form_markup(), which marks the output as form content. Called
	 * directly, smart tags in the form are left unresolved.
	 *
	 * @param int|string   $id Contains form ID.
	 * @param bool         $show_title_current_page Boolean to srfm-show/srfm-hide form title.
	 * @param string       $sf_classname additional class_name.
	 * @param string       $post_type Contains post type.
	 * @param bool         $do_blocks Boolean to enable/disable parsing dynamic blocks.
	 * @param array<mixed> $block_attrs Block attributes for per-embed styling.
	 *
	 * @return string|false
	 * @since 2.12.9
	 */
	public static function render_form_markup( $id, $show_title_current_page, $sf_classname, $post_type, $do_blocks, $block_attrs ) {
		// SECURITY INVARIANT — a renderer must never read the request to decide what to
		// render. The caller's `$id` is the only source of truth here; the REST route
		// owns request parsing (see render_form_markup_endpoint). Reintroducing any
		// query-string override would let a URL change which form a page renders.
		$id = Helper::get_integer_value( $id );

		// Check for any form restrictions.
		$form_id = Helper::get_integer_value( $id );

		// Additively record the form for the admin-bar "Entries" node. The registry
		// is primarily seeded at `wp` (collect_queried_form_ids) because the bar
		// renders before the_content; this render-time write is what covers paths a
		// content parse can't see — page builders (Elementor/Bricks) and FSE template
		// parts. Recorded before the restriction check: a restricted form is still on
		// the page, and its admin still wants its entries link.
		if ( $form_id > 0 ) {
			self::$rendered_form_ids[ $form_id ] = true;
		}

		if ( Form_Restriction::is_form_restricted( $form_id ) ) {
			return Form_Restriction::display_form_restriction_message( $form_id );
		}

		// Store block_attrs for child blocks (like inline button) to access.
		self::$current_block_attrs = $block_attrs;

		do_action( 'srfm_localize_conditional_logic_data', $id );
		$post = get_post( Helper::get_integer_value( $id ) );

		$content     = '';
		$form_blocks = [];

		$active_plugins      = Helper::get_array_value( get_option( 'active_plugins', [] ) );
		$is_learndash_active = in_array( 'sfwd-lms/sfwd_lms.php', $active_plugins, true );

		if ( $is_learndash_active ) {
			$do_blocks = true;
		}

		if ( $post && ! empty( $post->post_content ) ) {
			// Filter to get the post content for the form.
			$post_content = apply_filters( 'srfm_get_form_post_content', $post->post_content, $id );

			// Pre-translate block-attribute strings (labels, placeholders, options, etc.)
			// before rendering, so the visitor's chosen language is honoured. Returns the
			// translated markup plus the parsed top-level blocks so we can derive the block
			// count without re-parsing the rendered HTML. No-op when no provider is active.
			[ $post_content, $form_blocks ] = String_Translator::get_instance()->translate_form_content_with_blocks( (int) $id, Helper::get_string_value( $post_content ), $post );

			if ( ! empty( $do_blocks ) ) {
				$content = do_blocks( $post_content );
			} else {
				$content = apply_filters( 'the_content', $post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wordpress hook
			}
		}

		// Reuse the translator's parse on the multilingual path; otherwise parse once here
		// (single-language path). Either way the content is parsed exactly once, never three times.
		$form_blocks       = ! empty( $form_blocks ) ? $form_blocks : parse_blocks( $content );
		$block_count       = count( $form_blocks );
		$current_post_type = get_post_type();

		// When enabled, the form renders without the SureForms inline CSS variables so
		// the site's own CSS fully controls its appearance. Per-form Custom CSS still applies.
		// Read ONCE through the canonical checker so the `srfm_disable_default_styles`
		// filter runs a single time per render and governs the enqueue path, the
		// marker class and the inline CSS guard alike.
		$disable_default_styles = Form_Styling::is_default_styling_disabled( $id );

		// load all the frontend assets. Skips the SureForms stylesheets when the form has default styling disabled.
		Frontend_Assets::enqueue_scripts_and_styles( $disable_default_styles );

		ob_start();
		if ( '' !== $id && 0 !== $block_count ) {

			// Create unique container ID using blockId if available (for multiple embeds of same form).
			// Base class (without blockId) is needed for JS compatibility - frontend.js and phone.js use form-id attribute to construct selectors.
			$base_container_class = 'srfm-form-container-' . Helper::get_string_value( $id );
			// blockId reaches a container id and a <style> selector, so it is kept to
			// characters that are safe in both.
			$block_id_raw    = Helper::get_string_value( preg_replace( '/[^A-Za-z0-9_-]/', '', Helper::get_string_value( $block_attrs['blockId'] ?? '' ) ) );
			$block_id_suffix = '' !== $block_id_raw ? '-' . $block_id_raw : '';
			$container_id    = $base_container_class . $block_id_suffix;
			$form_styling    = get_post_meta( $id, '_srfm_forms_styling', true );
			$form_styling    = ! empty( $form_styling ) && is_array( $form_styling ) ? $form_styling : [];

			// Apply per-embed styling customization when formTheme is not 'inherit'.
			if ( Form_Styling::has_custom_styling( $block_attrs ) ) {
				$form_styling = Form_Styling::map_block_attrs_to_styling( $form_styling, $block_attrs );
			}

			// Background Settings.
			$bg_type                   = $form_styling['bg_type'] ?? 'color';
			$bg_color                  = $form_styling['bg_color'] ?? '';
			$bg_image                  = $form_styling['bg_image'] ?? '';
			$bg_image_position         = $form_styling['bg_image_position'] ?? [];
			$bg_image_attachment       = $form_styling['bg_image_attachment'] ?? 'scroll';
			$bg_image_repeat           = $form_styling['bg_image_repeat'] ?? 'no-repeat';
			$bg_image_size             = $form_styling['bg_image_size'] ?? 'cover';
			$bg_image_size_custom      = $form_styling['bg_image_size_custom'] ?? 100;
			$bg_image_size_custom_unit = $form_styling['bg_image_size_custom_unit'] ?? '%';
			$bg_gradient               = $form_styling['bg_gradient'] ?? 'linear-gradient(90deg, #FFC9B2 0%, #C7CBFF 100%)';
			$gradient_type             = $form_styling['gradient_type'] ?? 'basic'; // Basic or advanced.
			$is_advanced_gradient      = 'advanced' === $gradient_type ? true : false;
			$bg_gradient_type          = $is_advanced_gradient && isset( $form_styling['bg_gradient_type'] ) ? $form_styling['bg_gradient_type'] : 'linear'; // linear or radial gradient.
			$bg_gradient_color_1       = $is_advanced_gradient && isset( $form_styling['bg_gradient_color_1'] ) ? $form_styling['bg_gradient_color_1'] : '';
			$bg_gradient_color_2       = $is_advanced_gradient && isset( $form_styling['bg_gradient_color_2'] ) ? $form_styling['bg_gradient_color_2'] : '';
			$bg_gradient_location_1    = $is_advanced_gradient && isset( $form_styling['bg_gradient_location_1'] ) ? $form_styling['bg_gradient_location_1'] : '';
			$bg_gradient_location_2    = $is_advanced_gradient && isset( $form_styling['bg_gradient_location_2'] ) ? $form_styling['bg_gradient_location_2'] : '';
			$bg_gradient_angle         = $is_advanced_gradient && isset( $form_styling['bg_gradient_angle'] ) ? $form_styling['bg_gradient_angle'] : '';
			// Overlay Settings.
			$overlay_type       = $form_styling['bg_gradient_overlay_type'] ?? '';
			$overlay_size       = $form_styling['bg_overlay_size'] ?? 'cover';
			$overlay_opacity    = $form_styling['bg_overlay_opacity'] ?? 1;
			$overlay_color      = $form_styling['bg_image_overlay_color'] ?? '';
			$overlay_image      = $form_styling['bg_overlay_image'] ?? '';
			$overlay_position   = $form_styling['bg_overlay_position'] ?? [];
			$overlay_attachment = $form_styling['bg_overlay_attachment'] ?? 'scroll';
			$overlay_repeat     = $form_styling['bg_overlay_repeat'] ?? 'no-repeat';
			$overlay_blend_mode = $form_styling['bg_overlay_blend_mode'] ?? 'normal';
			// Gradient Overlay.
			$bg_overlay_gradient            = $form_styling['bg_overlay_gradient'] ?? 'linear-gradient(90deg, #FFC9B2 0%, #C7CBFF 100%)';
			$overlay_gradient_type          = $form_styling['overlay_gradient_type'] ?? 'basic'; // Basic or advanced.
			$is_overlay_advanced_gradient   = 'advanced' === $overlay_gradient_type ? true : false;
			$bg_overlay_gradient_type       = $is_overlay_advanced_gradient && isset( $form_styling['bg_overlay_gradient_type'] ) ? $form_styling['bg_overlay_gradient_type'] : 'linear';
			$bg_overlay_gradient_color_1    = $is_overlay_advanced_gradient && isset( $form_styling['bg_overlay_gradient_color_1'] ) ? $form_styling['bg_overlay_gradient_color_1'] : '';
			$bg_overlay_gradient_color_2    = $is_overlay_advanced_gradient && isset( $form_styling['bg_overlay_gradient_color_2'] ) ? $form_styling['bg_overlay_gradient_color_2'] : '';
			$bg_overlay_gradient_location_1 = $is_overlay_advanced_gradient && isset( $form_styling['bg_overlay_gradient_location_1'] ) ? $form_styling['bg_overlay_gradient_location_1'] : '';
			$bg_overlay_gradient_location_2 = $is_overlay_advanced_gradient && isset( $form_styling['bg_overlay_gradient_location_2'] ) ? $form_styling['bg_overlay_gradient_location_2'] : '';
			$bg_overlay_gradient_angle      = $is_overlay_advanced_gradient && isset( $form_styling['bg_overlay_gradient_angle'] ) ? $form_styling['bg_overlay_gradient_angle'] : '';
			// Embed Form Settings.
			$form = [
				// Padding.
				'padding_top'          => isset( $form_styling['form_padding_top'] ) ? floatval( $form_styling['form_padding_top'] ) : 0,
				'padding_right'        => isset( $form_styling['form_padding_right'] ) ? floatval( $form_styling['form_padding_right'] ) : 0,
				'padding_bottom'       => isset( $form_styling['form_padding_bottom'] ) ? floatval( $form_styling['form_padding_bottom'] ) : 0,
				'padding_left'         => isset( $form_styling['form_padding_left'] ) ? floatval( $form_styling['form_padding_left'] ) : 0,
				'padding_unit'         => isset( $form_styling['form_padding_unit'] ) ? Helper::get_string_value( $form_styling['form_padding_unit'] ) : 'px',
				// Border Radius.
				'border_radius_top'    => isset( $form_styling['form_border_radius_top'] ) ? floatval( $form_styling['form_border_radius_top'] ) : 0,
				'border_radius_right'  => isset( $form_styling['form_border_radius_right'] ) ? floatval( $form_styling['form_border_radius_right'] ) : 0,
				'border_radius_bottom' => isset( $form_styling['form_border_radius_bottom'] ) ? floatval( $form_styling['form_border_radius_bottom'] ) : 0,
				'border_radius_left'   => isset( $form_styling['form_border_radius_left'] ) ? floatval( $form_styling['form_border_radius_left'] ) : 0,
				'border_radius_unit'   => isset( $form_styling['form_border_radius_unit'] ) ? Helper::get_string_value( $form_styling['form_border_radius_unit'] ) : 'px',
			];
			// Instant Form Settings.
			$instant_form = [
				// Padding.
				'padding_top'          => isset( $form_styling['instant_form_padding_top'] ) ? floatval( $form_styling['instant_form_padding_top'] ) : 32,
				'padding_right'        => isset( $form_styling['instant_form_padding_right'] ) ? floatval( $form_styling['instant_form_padding_right'] ) : 32,
				'padding_bottom'       => isset( $form_styling['instant_form_padding_bottom'] ) ? floatval( $form_styling['instant_form_padding_bottom'] ) : 32,
				'padding_left'         => isset( $form_styling['instant_form_padding_left'] ) ? floatval( $form_styling['instant_form_padding_left'] ) : 32,
				'padding_unit'         => isset( $form_styling['instant_form_padding_unit'] ) ? Helper::get_string_value( $form_styling['instant_form_padding_unit'] ) : 'px',
				// Border Radius.
				'border_radius_top'    => isset( $form_styling['instant_form_border_radius_top'] ) ? floatval( $form_styling['instant_form_border_radius_top'] ) : 12,
				'border_radius_right'  => isset( $form_styling['instant_form_border_radius_right'] ) ? floatval( $form_styling['instant_form_border_radius_right'] ) : 12,
				'border_radius_bottom' => isset( $form_styling['instant_form_border_radius_bottom'] ) ? floatval( $form_styling['instant_form_border_radius_bottom'] ) : 12,
				'border_radius_left'   => isset( $form_styling['instant_form_border_radius_left'] ) ? floatval( $form_styling['instant_form_border_radius_left'] ) : 12,
				'border_radius_unit'   => isset( $form_styling['instant_form_border_radius_unit'] ) ? Helper::get_string_value( $form_styling['instant_form_border_radius_unit'] ) : 'px',
			];

			if ( 'custom' === $overlay_size ) {
				$bg_overlay_custom_size      = $form_styling['bg_overlay_custom_size'] ?? 100;
				$bg_overlay_custom_size_unit = $form_styling['bg_overlay_custom_size_unit'] ?? '%';
				$overlay_size                = $bg_overlay_custom_size . $bg_overlay_custom_size_unit;
			}

			$background_classes = apply_filters( 'srfm_add_background_classes', Helper::get_background_classes( $bg_type, $overlay_type, $bg_image ), $id, $block_attrs );

			$neve_theme_margin_class_name = 'srfm-neve-theme-add-margin-bottom';
			$theme_name                   = wp_get_theme()->get( 'Name' );

			$form_classes = [
				'srfm-form-container',
				$base_container_class, // Base class for JS compatibility (frontend.js, phone.js).
				! empty( $block_id_suffix ) ? $container_id : '', // Unique class for CSS scoping when blockId exists.
				$sf_classname,
				'Neve' === $theme_name ? $neve_theme_margin_class_name : '', // compatibility with Neve theme for margin between main content and footer.
				$disable_default_styles ? 'srfm-styling-none' : '', // Marker class when default styling is disabled, so custom CSS can target the state.
				$background_classes,
			];

			$custom_added_classes = Helper::get_meta_value( $id, '_srfm_additional_classes' );
			if ( ! empty( $custom_added_classes ) && is_string( $custom_added_classes ) ) {
				$custom_added_classes = explode( ' ', $custom_added_classes );
				foreach ( $custom_added_classes as $class ) {
					if ( Helper::is_valid_css_class_name( $class ) ) {
						$form_classes[] = $class;
					}
				}
			}

			$page_break_settings = defined( 'SRFM_PRO_VER' ) && apply_filters( 'srfm_use_page_break_layout', true ) ? get_post_meta( $id, '_srfm_page_break_settings', true ) : [];
			$page_break_settings = ! empty( $page_break_settings ) && is_array( $page_break_settings ) ? $page_break_settings : [];
			$is_page_break       = ! empty( $page_break_settings ) ? $page_break_settings['is_page_break'] : false;
			// Auto-advance is read here rather than in Pro's button renderer because
			// save & resume replaces that whole container through the
			// srfm_page_break_buttons_html filter, which would drop the attributes.
			// The form tag is rendered exactly once and is already how both step
			// runtimes receive their per-form settings (form-id, ajaxurl,
			// data-submit-token).
			//
			// Two stored settings rather than one because the two layouts are
			// mutually exclusive: Pro filters srfm_use_page_break_layout to false
			// when the conversational layout is on, so $page_break_settings is
			// empty there and its editor panel is hidden. Each layout keeps the
			// toggle with the rest of its own settings, and only one can apply.
			$conversational_settings  = defined( 'SRFM_PRO_VER' ) ? get_post_meta( $id, '_srfm_conversational_form', true ) : [];
			$conversational_settings  = ! empty( $conversational_settings ) && is_array( $conversational_settings ) ? $conversational_settings : [];
			$is_conversational        = ! empty( $conversational_settings['is_cf_enabled'] );
			$active_step_settings     = $is_conversational ? $conversational_settings : ( $is_page_break ? $page_break_settings : [] );
			$auto_advance_key         = $is_conversational ? 'cf_auto_advance' : 'auto_advance';
			$auto_advance             = ! empty( $active_step_settings[ $auto_advance_key ] );
			$auto_advance_hide_next   = $auto_advance && ! empty( $active_step_settings[ $auto_advance_key . '_hide_next' ] );
			$page_break_progress_type = ! empty( $page_break_settings ) ? $page_break_settings['progress_indicator_type'] : 'none';
			$form_confirmation        = get_post_meta( $id, '_srfm_form_confirmation' );
			$confirmation_type        = '';
			$submission_action        = '';
			$success_url              = '';
			if ( is_array( $form_confirmation ) && isset( $form_confirmation[0][0] ) ) {
				$confirmation_data = $form_confirmation[0][0];
				$page_url          = $confirmation_data['page_url'] ?? '';
				$custom_url        = $confirmation_data['custom_url'] ?? '';
				$confirmation_type = $confirmation_data['confirmation_type'] ?? '';
				$submission_action = $confirmation_data['submission_action'] ?? '';
				$success_url       = '';
				if ( 'different page' === $confirmation_type ) {
					$success_url = $page_url;
				} elseif ( 'custom url' === $confirmation_type ) {
					$success_url = $custom_url;
				}
			}

			// Submit button.
			$button_text             = Helper::get_meta_value( $id, '_srfm_submit_button_text' );
			$button_text             = String_Translator::get_instance()->translate_submit_button( (int) $id, Helper::get_string_value( $button_text ) );
			$submit_button_alignment = ! empty( $form_styling['submit_button_alignment'] ) ? $form_styling['submit_button_alignment'] : 'left';

			if ( is_rtl() && ( 'left' === $submit_button_alignment || 'right' === $submit_button_alignment ) ) {
				$submit_button_alignment = 'right' === $submit_button_alignment ? 'left' : 'right';
			}

			$btn_from_theme       = Helper::get_meta_value( $id, '_srfm_inherit_theme_button' );
			$is_inline_button     = apply_filters( 'srfm_is_inline_button', Helper::get_meta_value( $id, '_srfm_is_inline_button' ) );
			$security_type        = Helper::get_meta_value( $id, '_srfm_captcha_security_type' );
			$form_custom_css_meta = Helper::get_meta_value( $id, '_srfm_form_custom_css' );
			$custom_css           = ! empty( $form_custom_css_meta ) && is_string( $form_custom_css_meta ) ? $form_custom_css_meta : '';

			$full                       = 'justify' === $submit_button_alignment ? true : false;
			$recaptcha_version          = 'g-recaptcha' === $security_type ? Helper::get_meta_value( $id, '_srfm_form_recaptcha' ) : '';
			$srfm_cf_appearance_mode    = '';
			$srfm_cf_turnstile_site_key = '';
			$srfm_hcaptcha_site_key     = '';

			$google_captcha_site_key = '';

			if ( 'none' !== $security_type ) {
				$global_setting_options = get_option( 'srfm_security_settings_options' );
			} else {
				$global_setting_options = [];
			}

			if ( is_array( $global_setting_options ) && 'cf-turnstile' === $security_type ) {
				$srfm_cf_turnstile_site_key = $global_setting_options['srfm_cf_turnstile_site_key'] ?? '';
				$srfm_cf_appearance_mode    = $global_setting_options['srfm_cf_appearance_mode'] ?? 'auto';
			}

			if ( is_array( $global_setting_options ) && 'hcaptcha' === $security_type ) {
				$srfm_hcaptcha_site_key = $global_setting_options['srfm_hcaptcha_site_key'] ?? '';
			}

			if ( is_array( $global_setting_options ) && 'g-recaptcha' === $security_type ) {
				switch ( $recaptcha_version ) {
					case 'v2-checkbox':
						$google_captcha_site_key = $global_setting_options['srfm_v2_checkbox_site_key'] ?? '';
						break;
					case 'v2-invisible':
						$google_captcha_site_key = $global_setting_options['srfm_v2_invisible_site_key'] ?? '';
						break;
					case 'v3-reCAPTCHA':
						$google_captcha_site_key = $global_setting_options['srfm_v3_site_key'] ?? '';
						break;
					default:
						break;
				}
			}

			// Ensure $google_captcha_site_key is not empty, and if not, trim any leading or trailing whitespace.
			$google_captcha_site_key = is_string( $google_captcha_site_key ) && ! empty( $google_captcha_site_key ) ? trim( $google_captcha_site_key ) : '';

			$primary_color    = $form_styling['primary_color'] ?? '';
			$help_color_var   = $form_styling['text_color'] ?? '';
			$label_text_color = $form_styling['text_color_on_primary'] ?? '';
			$field_spacing    = $form_styling['field_spacing'] ?? 'small';

			// New colors.

			$primary_color_var    = $primary_color ? $primary_color : '#046bd2';
			$label_text_color_var = $label_text_color ? $label_text_color : '#111827';

			$selected_size = Helper::get_css_vars( $field_spacing );

			$should_show_submit_button = apply_filters(
				'srfm_show_submit_button',
				0 !== $block_count && ! $is_inline_button || $is_page_break,
				$id
			);

			if ( ! $should_show_submit_button ) {
				$form_classes[] = 'srfm-submit-button-hidden';
			}

			// The scoped Custom CSS below is for embedded views only: on the form's own
			// single/instant view, templates/single-form.php already outputs the Custom
			// CSS (unscoped) in <head> — emitting it here too would duplicate it.
			$embed_custom_css = 'sureforms_form' !== $current_post_type ? $custom_css : '';
			?>
			<div class="<?php echo esc_attr( implode( ' ', array_filter( $form_classes ) ) ); ?>">
			<?php if ( ! $disable_default_styles || '' !== $embed_custom_css ) { // Nothing to print otherwise — avoid an empty style block. ?>
			<style>
				/* Need to check and remove the input variables related to the Style Tab. */
				<?php echo esc_html( ".{$container_id}" ); ?> {
					<?php if ( ! $disable_default_styles ) { ?>
					/* New test variables */
					--srfm-color-scheme-primary: <?php echo esc_html( $primary_color_var ); ?>;
					--srfm-color-scheme-text-on-primary: <?php echo esc_html( $label_text_color_var ); ?>;
					--srfm-color-scheme-text: <?php echo esc_html( $help_color_var ); ?>;
					--srfm-quill-editor-color: <?php echo esc_html( $primary_color_var ); ?>;

					--srfm-color-input-label: <?php echo esc_html( $help_color_var ); ?>;
					--srfm-color-input-description: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.65 );
					--srfm-color-input-placeholder: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.5 );
					--srfm-color-input-text: <?php echo esc_html( $help_color_var ); ?>;
					--srfm-color-input-prefix: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.65 );
					--srfm-color-input-background: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.02 );
					--srfm-color-input-background-hover: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.05 );
					--srfm-color-input-background-disabled: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.07 );
					--srfm-color-input-border: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.25 );
					--srfm-color-input-border-disabled: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.15 );
					--srfm-color-multi-choice-svg: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.7 );
					--srfm-color-input-border-hover: hsl( from <?php echo esc_html( $primary_color_var ); ?> h s l / 0.65 );
					--srfm-color-input-border-focus-glow: hsl( from <?php echo esc_html( $primary_color_var ); ?> h s l / 0.15 );
					--srfm-color-input-selected: hsl( from <?php echo esc_html( $primary_color_var ); ?> h s l / 0.1 );
					--srfm-btn-color-hover: hsl( from <?php echo esc_html( $primary_color_var ); ?> h s l / 0.9 );
					--srfm-btn-color-disabled: hsl( from <?php echo esc_html( $primary_color_var ); ?> h s l / 0.25 );

					/* Dropdown Variables */
					--srfm-dropdown-input-background-hover: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.05 );
					--srfm-dropdown-option-background-hover: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.10 );
					--srfm-dropdown-option-background-selected: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.05 );
					--srfm-dropdown-option-selected-icon: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.65 );
					--srfm-dropdown-option-text-color: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.80 );
					--srfm-dropdown-option-selected-text: <?php echo esc_html( $help_color_var ); ?>;
					--srfm-dropdown-badge-background: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.05 );
					--srfm-dropdown-badge-background-hover: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.10 );
					--srfm-dropdown-menu-border-color: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.10 );
					--srfm-dropdown-placeholder-color: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.50 );
					--srfm-dropdown-icon-color: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.65 );
					--srfm-dropdown-icon-disabled: hsl( from <?php echo esc_html( $help_color_var ); ?> h s l / 0.25 );

					/* Background Control Variables */
						<?php
						// Form Styles.
						$styling_vars = [
							// Instant Form Padding.
							'--srfm-instant-form-padding-top' => sanitize_text_field( "{$instant_form['padding_top']}{$instant_form['padding_unit']}" ),
							'--srfm-instant-form-padding-right' => sanitize_text_field( "{$instant_form['padding_right']}{$instant_form['padding_unit']}" ),
							'--srfm-instant-form-padding-bottom' => sanitize_text_field( "{$instant_form['padding_bottom']}{$instant_form['padding_unit']}" ),
							'--srfm-instant-form-padding-left' => sanitize_text_field( "{$instant_form['padding_left']}{$instant_form['padding_unit']}" ),
							// Instant Form Border Radius.
							'--srfm-instant-form-border-radius-top' => sanitize_text_field( "{$instant_form['border_radius_top']}{$instant_form['border_radius_unit']}" ),
							'--srfm-instant-form-border-radius-right' => sanitize_text_field( "{$instant_form['border_radius_right']}{$instant_form['border_radius_unit']}" ),
							'--srfm-instant-form-border-radius-bottom' => sanitize_text_field( "{$instant_form['border_radius_bottom']}{$instant_form['border_radius_unit']}" ),
							'--srfm-instant-form-border-radius-left' => sanitize_text_field( "{$instant_form['border_radius_left']}{$instant_form['border_radius_unit']}" ),
							// Embed Form Padding.
							'--srfm-form-padding-top'    => sanitize_text_field( "{$form['padding_top']}{$form['padding_unit']}" ),
							'--srfm-form-padding-right'  => sanitize_text_field( "{$form['padding_right']}{$form['padding_unit']}" ),
							'--srfm-form-padding-bottom' => sanitize_text_field( "{$form['padding_bottom']}{$form['padding_unit']}" ),
							'--srfm-form-padding-left'   => sanitize_text_field( "{$form['padding_left']}{$form['padding_unit']}" ),
							// Embed Form Border Radius.
							'--srfm-form-border-radius-top' => sanitize_text_field( "{$form['border_radius_top']}{$form['border_radius_unit']}" ),
							'--srfm-form-border-radius-right' => sanitize_text_field( "{$form['border_radius_right']}{$form['border_radius_unit']}" ),
							'--srfm-form-border-radius-bottom' => sanitize_text_field( "{$form['border_radius_bottom']}{$form['border_radius_unit']}" ),
							'--srfm-form-border-radius-left' => sanitize_text_field( "{$form['border_radius_left']}{$form['border_radius_unit']}" ),
						];
						// Background Styles.
						if ( 'image' === $bg_type && ! empty( $bg_image ) ) {
							$bg_size_merged = 'custom' === $bg_image_size ? "{$bg_image_size_custom}{$bg_image_size_custom_unit}" : $bg_image_size;
							$styling_vars  += [
								'--srfm-bg-image'      => 'url(' . esc_url_raw( $bg_image ) . ')',
								'--srfm-bg-position'   => sanitize_text_field(
									( ( ! empty( $bg_image_position['x'] ) ? $bg_image_position['x'] : 0.5 ) * 100 ) . '% ' .
									( ( ! empty( $bg_image_position['y'] ) ? $bg_image_position['y'] : 0.5 ) * 100 ) . '% '
								),
								'--srfm-bg-attachment' => sanitize_text_field( $bg_image_attachment ),
								'--srfm-bg-repeat'     => sanitize_text_field( $bg_image_repeat ),
								'--srfm-bg-size'       => sanitize_text_field( $bg_size_merged ),
							];
						} elseif ( 'color' === $bg_type && ! empty( $bg_color ) ) {
							$styling_vars['--srfm-bg-color'] = sanitize_text_field( $bg_color );
						} elseif ( 'gradient' === $bg_type && ! empty( $bg_gradient ) ) {
							if ( $is_advanced_gradient ) {
								$bg_gradient = Helper::get_gradient_css( $bg_gradient_type, $bg_gradient_color_1, $bg_gradient_color_2, $bg_gradient_location_1, $bg_gradient_location_2, $bg_gradient_angle );
							}
							$styling_vars['--srfm-bg-gradient'] = sanitize_text_field( $bg_gradient );
						}
							// Overlay Variables.
						if ( 'image' === $bg_type && 'image' === $overlay_type && ! empty( $overlay_image ) ) {
							$styling_vars += [
								'--srfm-bg-overlay-image'  => 'url(' . esc_url_raw( $overlay_image ) . ')',
								'--srfm-bg-overlay-position' => sanitize_text_field(
									( ( ! empty( $overlay_position['x'] ) ? $overlay_position['x'] : 0.5 ) * 100 ) . '% ' .
									( ( ! empty( $overlay_position['y'] ) ? $overlay_position['y'] : 0.5 ) * 100 ) . '%'
								),
								'--srfm-bg-overlay-attachment' => sanitize_text_field( $overlay_attachment ),
								'--srfm-bg-overlay-repeat' => sanitize_text_field( $overlay_repeat ),
								'--srfm-bg-overlay-size'   => sanitize_text_field( $overlay_size ),
								'--srfm-bg-overlay-blend-mode' => sanitize_text_field( $overlay_blend_mode ),
							];
						} elseif ( 'image' === $bg_type && 'color' === $overlay_type && ! empty( $overlay_color ) ) {
							$styling_vars += [
								'--srfm-bg-overlay-color' => sanitize_text_field( $overlay_color ),
							];
						} elseif ( 'image' === $bg_type && 'gradient' === $overlay_type && ! empty( $bg_overlay_gradient ) ) {
							if ( $is_overlay_advanced_gradient ) {
								$bg_overlay_gradient = Helper::get_gradient_css( $bg_overlay_gradient_type, $bg_overlay_gradient_color_1, $bg_overlay_gradient_color_2, $bg_overlay_gradient_location_1, $bg_overlay_gradient_location_2, $bg_overlay_gradient_angle );
							}
							$styling_vars += [
								'--srfm-bg-overlay-gradient' => sanitize_text_field( $bg_overlay_gradient ),
							];
						}
						$styling_vars['--srfm-bg-overlay-opacity'] = floatval( $overlay_opacity );
						// Output the CSS variables.
						foreach ( $styling_vars as $key => $value ) {
							echo esc_html( Helper::get_string_value( $key ) ) . ': ' . esc_html( Helper::get_string_value( $value ) ) . ';';
						}
						?>
						<?php
						// Echo the CSS variables for the form according to the field spacing selected.
						foreach ( $selected_size as $variable => $value ) {
							echo esc_html( Helper::get_string_value( $variable ) ) . ': ' . esc_html( Helper::get_string_value( $value ) ) . ';';
						}
						do_action(
							'srfm_form_css_variables',
							[
								'id'            => $id,
								'primary_color' => $primary_color_var,
								'help_color'    => $help_color_var,
								'form_styling'  => $form_styling,
								'block_attrs'   => $block_attrs,
							]
						);
					} // End if default styling is not disabled.
					echo wp_kses_post( $embed_custom_css );
					?>
				}
			</style>
			<?php } // End if the style block has content. ?>
			<?php
			if ( 'sureforms_form' !== $current_post_type && true === $show_title_current_page ) {
				$title = ! empty( get_the_title( (int) $id ) ) ? get_the_title( (int) $id ) : '';
				$title = String_Translator::get_instance()->translate_form_title( (int) $id, $title );
				?>
				<h2 class="srfm-form-title"><?php echo esc_html( $title ); ?></h2>
				<?php
			}

			// Password protected form check.
			if ( $post && post_password_required( $post ) ) {
				// Define allowed HTML tags for password form output.
				$allowed_password_form_tags = [
					'form'   => [
						'action' => true,
						'method' => true,
						'class'  => true,
						'id'     => true,
					],
					'label'  => [
						'for'   => true,
						'class' => true,
					],
					'input'  => [
						'type'        => true,
						'name'        => true,
						'id'          => true,
						'class'       => true,
						'value'       => true,
						'size'        => true,
						'placeholder' => true,
						'required'    => true,
					],
					'p'      => [
						'class' => true,
						'style' => true,
					],
					'button' => [
						'type'  => true,
						'name'  => true,
						'class' => true,
						'id'    => true,
						'style' => true,
					],
					'div'    => [
						'class' => true,
						'id'    => true,
						'style' => true,
					],
					'span'   => [
						'class'       => true,
						'aria-hidden' => true,
					],
					'svg'    => [
						'xmlns'   => true,
						'width'   => true,
						'height'  => true,
						'viewBox' => true,
						'fill'    => true,
					],
					'path'   => [
						'd'               => true,
						'stroke'          => true,
						'stroke-opacity'  => true,
						'stroke-width'    => true,
						'stroke-linecap'  => true,
						'stroke-linejoin' => true,
					],
				];
				echo wp_kses( get_the_password_form( $post ), $allowed_password_form_tags );
				?>
				</div>
				<?php
				self::$current_block_attrs = [];
				return ob_get_clean();
			}
			$submit_token = Submit_Token::generate( (int) $id );
			// Separately namespaced from the submission token: this one is only good
			// for incrementing a view counter, so scraping it from the page buys an
			// attacker nothing beyond what the beacon already does, and it cannot be
			// replayed against the submit endpoint.
			$view_token = Submit_Token::generate( (int) $id, Submit_Token::NAMESPACE_VIEW );

			// Admin-only shortcut into the form editor. Emitted here, immediately
			// above the <form>, so it occupies its own row in normal flow and can
			// never overlap a field. Already inside the `.srfm-form-container`
			// branch, so a zero-block form (no container) never reaches here and
			// cannot emit an orphaned pill. Works for every embed method (block,
			// shortcode, widget) because they all render through this function.
			self::render_edit_form_button( (int) $id );

			?>
				<form method="post" enctype="multipart/form-data" id="srfm-form-<?php echo esc_attr( Helper::get_string_value( $id ) ); ?>" class="srfm-form <?php echo esc_attr( 'sureforms_form' === $post_type ? 'srfm-single-form ' : '' ); ?>"
				form-id="<?php echo esc_attr( Helper::get_string_value( $id ) ); ?>" after-submission="<?php echo esc_attr( $submission_action ); ?>" message-type="<?php echo esc_attr( $confirmation_type ? $confirmation_type : 'same page' ); ?>" success-url="<?php echo esc_attr( $success_url ? $success_url : '' ); ?>" ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-submit-token="<?php echo esc_attr( $submit_token ); ?>" data-view-token="<?php echo esc_attr( $view_token ); ?>"<?php echo $auto_advance ? ' data-srfm-auto-advance="1"' : ''; ?><?php echo $auto_advance_hide_next ? ' data-srfm-hide-next="1"' : ''; ?>
				>
				<?php
					// Submission security is handled via the HMAC token in data-submit-token.
					$global_setting_options = get_option( 'srfm_security_settings_options' );
					$honeypot_spam          = is_array( $global_setting_options ) && isset( $global_setting_options['srfm_honeypot'] ) ? $global_setting_options['srfm_honeypot'] : '';

				if ( $is_page_break && 'none' !== $page_break_progress_type ) {
					do_action( 'srfm_page_break_header', $id );
				}
				?>

				<input type="hidden" value="<?php echo esc_attr( Helper::get_string_value( $id ) ); ?>" name="form-id">
				<?php
				/*
				 * Submission language. Captured client-side because the REST submit endpoint
				 * loses WPML's URL-based language context. The value is baked into the markup
				 * at render time, so accurate entry-language tagging requires the page cache to
				 * be language-aware (the default for WPML's language-per-URL modes). At submit
				 * time the server re-validates this value against the active language list and
				 * falls back to its own current_language() when it can't be confirmed
				 * (see Form_Submit::is_known_language()), so a stale/forged value is never
				 * trusted blindly.
				 */
				?>
				<input type="hidden" value="<?php echo esc_attr( Multilingual_Manager::get_instance()->provider()->current_language() ); ?>" name="srfm-form-language">
				<input type="hidden" value="" name="srfm-sender-email-field" id="srfm-sender-email">
				<input type="hidden" value="<?php echo esc_attr( Helper::get_string_value( $is_page_break ) ); ?>" id="srfm-page-break">
				<?php if ( $honeypot_spam ) { ?>
					<input type="hidden" value="" name="srfm-honeypot-field">
					<?php
				}
					self::common_error_message( 'head' );
				if ( $is_page_break ) {
					do_action( 'srfm_page_break_pagination', $post, $id );
				} elseif ( ! apply_filters( 'srfm_use_custom_field_content', false ) ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is filtered and sanitized by WordPress core blocks and filters.
					echo $content;
				}

				do_action( 'srfm_after_field_content', $post, $id );

				$captcha_container_hidden_class = ! empty( $google_captcha_site_key ) && ( 'v3-reCAPTCHA' === $recaptcha_version || 'v2-invisible' === $recaptcha_version ) ? 'srfm-display-none' : '';

				?>
					<?php if ( $should_show_submit_button && ! empty( $security_type ) && 'none' !== $security_type ) { ?>
						<div class="srfm-captcha-container <?php echo esc_attr( $captcha_container_hidden_class ); ?>">

						<?php

						if ( 'g-recaptcha' === $security_type ) {
							self::get_google_captcha_script( $recaptcha_version, $google_captcha_site_key );
						}

						if ( 'cf-turnstile' === $security_type ) {
							self::get_cf_turnstile_script( $srfm_cf_appearance_mode, $srfm_cf_turnstile_site_key );
						}

						if ( 'hcaptcha' === $security_type ) {
							self::get_h_captcha_script( $srfm_hcaptcha_site_key );
						}
						?>
						<div class="srfm-validation-error" id="captcha-error" style="display: none;"><?php echo esc_html__( 'Please verify that you are not a robot.', 'sureforms' ); ?></div>
					</div>
					<?php } ?>

					<?php
					if ( $is_page_break ) {
						do_action( 'srfm_page_break_btn', $id );
					}
					$srfm_button_classes = apply_filters( 'srfm_add_button_classes', [ '1' === $btn_from_theme ? 'wp-block-button__link' : 'srfm-btn-frontend srfm-button srfm-submit-button', 'v3-reCAPTCHA' === $recaptcha_version ? ' g-recaptcha' : '' ], $id, $block_attrs );
					?>

					<div class="srfm-submit-container <?php echo esc_attr( $is_page_break ? 'srfm-hide' : '' ); ?>" style="<?php echo ! $should_show_submit_button ? 'visibility:hidden;position:absolute;' : ''; ?>">
						<div style="width: <?php echo esc_attr( $full ? '100%' : '' ); ?>; text-align: <?php echo esc_attr( $submit_button_alignment ); ?>" class="wp-block-button">
						<?php do_action( 'srfm_before_submit_button', $id ); ?>
						<?php if ( $should_show_submit_button ) { ?>
							<button style="<?php echo esc_attr( $full ? 'width: 100%;' : '' ); ?>" id="srfm-submit-btn" class="<?php echo esc_attr( implode( ' ', array_filter( $srfm_button_classes ) ) ); ?>"
							<?php if ( 'v3-reCAPTCHA' === $recaptcha_version ) { ?>
								data-callback="recaptchaCallback"
								data-error-callback="onGCaptchaV3Error"
								recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>"
								data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>"
							<?php } ?>
							>
								<div class="srfm-submit-wrap">
									<?php echo esc_html( $button_text ); ?>
								<div class="srfm-loader"></div>
								</div>
							</button>
						<?php } ?>
						<?php do_action( 'srfm_after_submit_button', $id ); ?>
						</div>
					</div>
					<?php

					echo wp_kses_post(
						apply_filters(
							'srfm_after_submit_button_content',
							'',
							[
								'id'                      => $id,
								'should_show_submit_button' => $should_show_submit_button,
								'button_text'             => $button_text,
								'submit_button_alignment' => $submit_button_alignment,
								'full'                    => $full,
								'btn_from_theme'          => $btn_from_theme,
								'is_page_break'           => $is_page_break,
								'recaptcha_version'       => $recaptcha_version,
								'google_captcha_site_key' => $google_captcha_site_key,
								'srfm_button_classes'     => $srfm_button_classes,
							]
						)
					);
		}
				self::common_error_message( 'footer' );
		?>
			</form>
			<div class="srfm-single-form srfm-success-box in-page">
				<div aria-live="polite" aria-atomic="true" role="alert" id="srfm-success-message-page-<?php echo esc_attr( Helper::get_string_value( $id ) ); ?>" class="srfm-success-box-description"></div>
			</div>
			<?php
			// Add preview script for real-time styling updates from block editor.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a preview context, nonce not required.
			if ( isset( $_GET['form_preview'] ) && 'true' === $_GET['form_preview'] && isset( $container_id ) ) {
				self::enqueue_preview_styling_script( $container_id );
			}
			?>
			</div>
		<?php
		self::$current_block_attrs = [];
		return ob_get_clean();
	}

	/**
	 * Generate HCaptcha script markup
	 *
	 * @param string $srfm_hcaptcha_site_key site key.
	 * @since 1.7.0
	 * @return void
	 */
	public static function get_h_captcha_script( $srfm_hcaptcha_site_key ) {
		if ( ! empty( $srfm_hcaptcha_site_key ) ) {
			// hCaptcha script.
				wp_enqueue_script( 'hcaptcha', 'https://js.hcaptcha.com/1/api.js', [], null, [ 'strategy' => 'defer' ] ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			?>
			<div id="srfm-hcaptcha-sitekey" data-callback="onSuccess" data-error-callback="onHCaptchaError" class="h-captcha" data-sitekey="<?php echo esc_attr( $srfm_hcaptcha_site_key ); ?>"></div>
				<?php
		} else {
			Helper::render_missing_sitekey_error( 'HCaptcha' );
		}
	}

	/**
	 * Generate Google Recaptcha script markup
	 *
	 * @param string $recaptcha_version reCAPTCHA version.
	 * @param string $google_captcha_site_key site key.
	 * @since 1.7.0
	 * @return void
	 */
	public static function get_google_captcha_script( $recaptcha_version, $google_captcha_site_key ) {

		if ( empty( $google_captcha_site_key ) ) {
			Helper::render_missing_sitekey_error( 'Google reCAPTCHA' );
			return;
		}

		if ( 'v2-checkbox' === $recaptcha_version ) {
			?>
			<?php
			wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], SRFM_VER, true );
			?>
		<div class='g-recaptcha' data-callback="onSuccess" data-error-callback="onGCaptchaV2CheckBoxError" recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>" data-sitekey="<?php echo esc_attr( strval( $google_captcha_site_key ) ); ?>" ></div>
		<?php } ?>

		<?php if ( 'v2-invisible' === $recaptcha_version ) { ?>
			<?php
			wp_enqueue_script( 'google-recaptcha-invisible', 'https://www.google.com/recaptcha/api.js?onload=recaptchaCallback&render=explicit', [ SRFM_SLUG . '-form-submit' ], SRFM_VER, true );
			?>
		<div class='g-recaptcha' recaptcha-type="<?php echo esc_attr( $recaptcha_version ); ?>" data-sitekey="<?php echo esc_attr( $google_captcha_site_key ); ?>" data-size="invisible"></div>
		<?php } ?>

		<?php if ( 'v3-reCAPTCHA' === $recaptcha_version ) { ?>
			<?php
				// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion, PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent -- Google reCAPTCHA must be loaded from Google's servers for token verification; the version is controlled by Google, and passing null avoids stale caching.
				wp_enqueue_script(
					'srfm-google-recaptchaV3',
					'https://www.google.com/recaptcha/api.js?render=' . $google_captcha_site_key,
					[],
					null,
					true
				);
				// phpcs:enable WordPress.WP.EnqueuedResourceParameters.MissingVersion, PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent
			?>
			<?php
		}
	}

	/**
	 * Generate Cloudflare Turnstile script markup
	 *
	 * @param string $srfm_cf_appearance_mode appearance mode.
	 * @param string $srfm_cf_turnstile_site_key site key.
	 * @since 1.7.0
	 * @return void
	 */
	public static function get_cf_turnstile_script( $srfm_cf_appearance_mode, $srfm_cf_turnstile_site_key ) {
		if ( ! empty( $srfm_cf_turnstile_site_key ) ) {
			// Cloudflare Turnstile script.
			// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion, PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent -- Cloudflare Turnstile must be loaded from Cloudflare's servers for token verification; the version is controlled by Cloudflare.
			wp_enqueue_script(
				SRFM_SLUG . '-cf-turnstile',
				'https://challenges.cloudflare.com/turnstile/v0/api.js',
				[],
				null,
				[
					'strategy' => 'defer',
				]
			);
			// phpcs:enable WordPress.WP.EnqueuedResourceParameters.MissingVersion, PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent
			?>
			<!-- The callback methods below are available on frontend.js. onTurnstileError displays and error in place of recaptcha dialog.  -->
		<div id="srfm-cf-sitekey" class="cf-turnstile" data-callback="onSuccess" data-error-callback="onTurnstileError" data-theme="<?php echo esc_attr( $srfm_cf_appearance_mode ); ?>" data-sitekey="<?php echo esc_attr( $srfm_cf_turnstile_site_key ); ?>"></div>
			<?php
		} else {
			Helper::render_missing_sitekey_error( 'Turnstile' );
		}
	}

	/**
	 * Generate common error message markup
	 *
	 * @param string $position position of the error message.
	 * @since 1.5.0
	 * @return void
	 */
	public static function common_error_message( $position = 'footer' ) {
		$icon    = Helper::fetch_svg( 'info_circle', '', 'aria-hidden="true"' );
		$classes = "srfm-common-error-message srfm-error-message srfm-{$position}-error";
		?>
		<p id="srfm-error-message" class="<?php echo esc_attr( $classes ); ?>" hidden><?php echo wp_kses( $icon, Helper::$allowed_tags_svg ); ?><span class="srfm-error-content"><?php echo esc_html( String_Translator::get_instance()->translate_validation_message( 'srfm_submit_error', __( 'There was an error trying to submit your form. Please try again.', 'sureforms' ) ) ); ?></span></p>
		<?php
	}

	/**
	 * Enqueue the preview styling script for real-time updates from block editor.
	 *
	 * @param string $container_id The form container ID selector.
	 * @since 2.7.0
	 * @return void
	 */
	public static function enqueue_preview_styling_script( $container_id ) {
		$script_asset_path = SRFM_DIR . 'assets/build/previewStyling.asset.php';
		$script_asset      = file_exists( $script_asset_path ) ? require $script_asset_path : [
			'dependencies' => [],
			'version'      => SRFM_VER,
		];

		wp_enqueue_script(
			SRFM_SLUG . '-preview-styling',
			SRFM_URL . 'assets/build/previewStyling.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_localize_script(
			SRFM_SLUG . '-preview-styling',
			'srfmPreviewStyling',
			[
				'containerId'      => $container_id,
				'fieldSpacingVars' => Helper::get_css_vars(),
			]
		);

		/**
		 * Action to allow Pro to enqueue additional preview styling scripts.
		 *
		 * @since 2.7.0
		 */
		do_action( 'srfm_enqueue_preview_styling_scripts' );
	}

	/**
	 * Generate form confirmation markup
	 *
	 * @param array<mixed> $form_data contains form data.
	 * @param array<mixed> $submission_data contains submission data.
	 * @since 0.0.3
	 * @return string|false
	 */
	public static function get_confirmation_markup( $form_data = [], $submission_data = [] ) {

		$confirmation_message = '';

		if ( empty( $form_data ) ) {
			return $confirmation_message;
		}

		$form_id           = isset( $form_data['form-id'] ) ? Helper::get_integer_value( $form_data['form-id'] ) : 0;
		$form_confirmation = get_post_meta( $form_id, '_srfm_form_confirmation' );

		/**
		 * Filter the form confirmation data.
		 * Allows conditional confirmations to override the default confirmation settings.
		 *
		 * @param mixed $form_confirmation The form confirmation data from post meta.
		 * @param int   $form_id The form ID.
		 * @param array $submission_data The submission data.
		 * @since 2.4.0
		 */
		$form_confirmation = apply_filters( 'srfm_form_confirmation_data', $form_confirmation, $form_id, $submission_data );

		if ( ! is_array( $form_confirmation ) ) {
			return $confirmation_message;
		}

		$confirmation_data = is_array( $form_confirmation[0] ) && isset( $form_confirmation[0][0] ) ? $form_confirmation[0][0] : null;

		if ( is_array( $form_confirmation ) && isset( $confirmation_data['message'] ) && is_string( $confirmation_data['message'] ) ) {
			$confirmation_message = $confirmation_data['message'];
			$confirmation_message = String_Translator::get_instance()->translate_confirmation_message( (int) $form_id, 0, $confirmation_message );
		}
		if ( empty( $submission_data ) ) {
			return $confirmation_message;
		}
		$smart_tags           = Smart_Tags::get_instance();
		$confirmation_message = $smart_tags->process_smart_tags( $confirmation_message, $submission_data, $form_data );

		/**
		 * Filter whether confirmation message links should open in a new tab.
		 *
		 * @since 2.5.2
		 *
		 * @param bool $open_in_new_tab Whether links open in a new tab. Default true.
		 */
		$open_in_new_tab = (bool) apply_filters( 'srfm_confirmation_links_open_in_new_tab', true );

		$markup = Helper::strip_js_attributes(
			apply_filters( 'srfm_after_submit_confirmation_message', $confirmation_message, $form_data, $submission_data ),
			! $open_in_new_tab
		);

		if ( false !== strpos( $markup, 'src="image/svg+xml;base64' ) ) {
			// Handle Form Confirmation SVGs separately. We have planned to improve it in the future replacing it with image URL.
			$normalized_string = preg_replace( '/src="image\/svg\+xml;base64/', 'src="data:image/svg+xml;base64', $markup );

			if ( is_string( $normalized_string ) ) {
				$markup = $normalized_string;
			}
		}

		return $markup;
	}

	/**
	 * Get redirect url for form incase of different page or custom url is selected.
	 *
	 * @param array<mixed> $form_data contains form data.
	 * @param array<mixed> $submission_data contains submission data.
	 * @since 1.0.2
	 * @return string|false
	 */
	public static function get_redirect_url( $form_data = [], $submission_data = [] ) {
		$redirect_url = '';

		if ( empty( $form_data ) ) {
			return $redirect_url;
		}

		$form_id           = isset( $form_data['form-id'] ) ? Helper::get_integer_value( $form_data['form-id'] ) : 0;
		$form_confirmation = get_post_meta( $form_id, '_srfm_form_confirmation' );

		/**
		 * Filter the form confirmation data.
		 * Allows conditional confirmations to override the default confirmation settings.
		 *
		 * @param mixed $form_confirmation The form confirmation data from post meta.
		 * @param int   $form_id The form ID.
		 * @param array $submission_data The submission data.
		 * @since 2.4.0
		 */
		$form_confirmation = apply_filters( 'srfm_form_confirmation_data', $form_confirmation, $form_id, $submission_data );

		if ( ! is_array( $form_confirmation ) ) {
			return $redirect_url;
		}

		$confirmation_data = is_array( $form_confirmation[0] ) && isset( $form_confirmation[0][0] ) ? $form_confirmation[0][0] : null;

		$page_url          = $confirmation_data['page_url'] ?? '';
		$custom_url        = $confirmation_data['custom_url'] ?? '';
		$confirmation_type = $confirmation_data['confirmation_type'] ?? '';

		if ( 'different page' === $confirmation_type ) {
			$redirect_url = esc_url_raw( $page_url );
		} elseif ( 'custom url' === $confirmation_type ) {
			$redirect_url = esc_url_raw( $custom_url );
		}

		if ( empty( $redirect_url ) ) {
			return $redirect_url;
		}

		if ( empty( $confirmation_data['enable_query_params'] ) || true !== $confirmation_data['enable_query_params'] ) {
			return $redirect_url;
		}

		if ( empty( $confirmation_data['query_params'] ) && ! is_array( $confirmation_data['query_params'] ) ) {
			return $redirect_url;
		}

		$query_params = [];
		foreach ( $confirmation_data['query_params'] as $params ) {
			if ( is_array( $params ) && ! empty( array_keys( $params ) ) && ! empty( array_values( $params ) ) ) {
				$query_params[ sanitize_text_field( array_keys( $params )[0] ) ] = sanitize_text_field( array_values( $params )[0] );
			}
		}

		$redirect_url = add_query_arg( $query_params, $redirect_url );

		if ( ! empty( $submission_data ) ) {
			$smart_tags = Smart_Tags::get_instance();
			// Adding upload_format_type = 'raw' to retrieve urls as comma separated values.
			$form_data['upload_format_type'] = 'raw';
			// Skip auto-linking URLs in smart tag values — redirect query params need raw values, not HTML.
			$form_data['smart_tag_context'] = 'redirect';

			/*
			 * Resolve smart tags in the URL, normalize the multi-value delimiters
			 * left behind by the substitution, then decode any HTML entities.
			 *
			 * Multi-select dropdown values are packed as "Red | Blue" by the frontend
			 * (srfmUtility.prepareValue in assets/js/unminified/frontend.js), and
			 * checkbox multi-choice values are rendered as "Red<br>Blue" by
			 * Smart_Tags::parse_form_input. Neither delimiter is URL-friendly as-is:
			 * " | " leaks whitespace into the query string and "<br>" gets mangled
			 * by esc_url_raw below. Normalize both to a plain "|" so the final
			 * redirect URL carries a clean, URL-safe list that the receiver can
			 * split on "|".
			 *
			 * The str_replace runs before html_entity_decode so that any literal
			 * "<br>" character sequence inside an option label — which
			 * Smart_Tags::parse_form_input escapes to "&lt;br&gt;" before joining
			 * — survives intact. Only the actual delimiter (the unescaped "<br>"
			 * emitted by the implode) is converted to a pipe; html_entity_decode
			 * then restores the option's original text.
			 */
			$resolved_redirect_url  = Helper::get_string_value( $smart_tags->process_smart_tags( $redirect_url, $submission_data, $form_data ) );
			$multi_value_delimiters = [ '<br>', ' | ' ];
			$redirect_url           = html_entity_decode( str_replace( $multi_value_delimiters, '|', $resolved_redirect_url ) );
		}

		return esc_url_raw( apply_filters( 'srfm_after_submit_redirect_url', $redirect_url ) );
	}

	/**
	 * Print the admin-only "Edit Form" shortcut on an embedded form.
	 *
	 * Renders a small pill link that opens the block editor for this form, on its
	 * own right-aligned row directly above the form.
	 *
	 * It sits in normal flow rather than being absolutely positioned over the
	 * form's top-right corner, which is what it used to do. An overlay can only
	 * avoid the fields when the container happens to have enough top padding —
	 * with the default theme styling it landed on top of the first row's last
	 * field (#3062). Flow layout cannot overlap anything by construction, at any
	 * width, with any theme. The cost is that the form shifts down by the pill's
	 * height, which happens only for users who can edit the form; the markup and
	 * its styles remain entirely absent from the DOM for everyone else, so no
	 * regular visitor sees a layout change.
	 *
	 * Admin-only by construction: the `sureforms_form` CPT registers with
	 * `map_meta_cap => false`, so `edit_post` collapses to a blanket
	 * `manage_options` check with no per-post component — an editor never sees the
	 * pill on any form. For every other viewer the markup and its styles are
	 * entirely absent from the DOM.
	 *
	 * The stylesheet is attached to a registered inline-only handle so `WP_Styles`
	 * dedupes it by handle (surviving a discarded `the_content` pass, e.g. an SEO
	 * plugin building `og:description` during `wp_head`) and it survives a strict
	 * `style-src` CSP. It is not cache-signalled here: the payload is only a
	 * `wp-admin/post.php?post=N` link an anonymous visitor cannot act on, and a
	 * `DONOTCACHEPAGE` define from a fragment renderer is both inert on the normal
	 * (headers-already-sent) path and an irreversible process-global side effect.
	 *
	 * @param int $form_id Form post ID.
	 *
	 * @return void
	 * @since 2.12.4
	 */
	public static function render_edit_form_button( $form_id ) {
		$form_id = absint( $form_id );

		// Only for real SureForms forms — the [sureforms] shortcode accepts any
		// post ID, and a non-form target would map `edit_post` normally and leak
		// the pill to an ordinary editor.
		if ( 0 === $form_id || ! defined( 'SRFM_FORMS_POST_TYPE' ) || SRFM_FORMS_POST_TYPE !== get_post_type( $form_id ) ) {
			return;
		}

		// Capability gate first, before the suppression filter, so no work is done
		// for the anonymous visitors who make up almost every page view.
		if ( ! current_user_can( 'edit_post', $form_id ) ) {
			return;
		}

		// Contexts where the pill is redundant or wrong:
		// - the single-form / Instant Form page, where the form IS the whole page
		// and the admin bar already links to its editor. This is also what
		// suppresses the block editor's preview — that preview is an iframe to
		// the form's own permalink (an ordinary front-end request), NOT a REST
		// render, so `is_singular` is the load-bearing guard there;
		// - any admin / AJAX / REST / JSON request, or a feed (the markup would
		// otherwise land inside `content:encoded` CDATA).
		if (
			is_singular( SRFM_FORMS_POST_TYPE )
			|| is_admin()
			|| wp_doing_ajax()
			|| wp_is_json_request()
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| is_feed()
		) {
			return;
		}

		// Page-builder editor canvases render the form directly (not over REST),
		// where their own element-edit handles would collide with the pill.
		// `$instance` is checked as well as the class name: Elementor declares
		// `public static $instance = null` and only populates it on boot, so the
		// class can exist while the singleton is still null. Dereferencing it then
		// is a fatal Error, not a warning, and guarding only on class_exists() left
		// that reachable — test-generate-form-markup.php hit it. The bundled stub
		// types $instance as non-nullable, which is why PHPStan reads the isset()
		// as redundant and has to be told otherwise.
		//
		// ->editor is checked for the same reason one level down: Elementor assigns it
		// in init_components() on `init`, while the singleton itself is created on
		// `plugins_loaded`. Between those two hooks $instance is set and ->editor is
		// still null, so checking only the singleton reproduces the original fatal a
		// property later.
		// @phpstan-ignore-next-line -- Stub disagrees with runtime; see above.
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}
		if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) {
			return;
		}

		/**
		 * Allow integrations to suppress the admin "Edit Form" shortcut entirely.
		 *
		 * @param bool $show    Whether to render the shortcut. Default true.
		 * @param int  $form_id Form post ID.
		 *
		 * @since 2.12.4
		 */
		if ( ! apply_filters( 'srfm_show_edit_form_button', true, $form_id ) ) {
			return;
		}

		$edit_link = get_edit_post_link( $form_id, 'raw' );

		if ( empty( $edit_link ) ) {
			return;
		}

		// Attribution marker read back by Admin::maybe_track_edit_form_button_click()
		// when the editor loads. Added before the filter below so an integration that
		// replaces the link wholesale drops the marker with it, rather than having our
		// query arg appended to a third-party URL.
		// 'url' context, not the default 'display': the latter returns &amp;-escaped
		// separators, and feeding those to add_query_arg() only round-trips because
		// build_query() happens to re-emit the mangled `amp;action` key verbatim. The
		// raw form has no such dependency, and esc_url() below still escapes on output.
		$edit_link = add_query_arg( self::EDIT_FORM_BUTTON_SOURCE_ARG, 'embed', $edit_link );

		/**
		 * Filter the target of the admin "Edit Form" shortcut.
		 *
		 * @param string $edit_link Editor URL for the form.
		 * @param int    $form_id   Form post ID.
		 *
		 * @since 2.12.4
		 */
		$edit_link = Helper::get_string_value( apply_filters( 'srfm_edit_form_button_link', $edit_link, $form_id ) );

		if ( '' === $edit_link ) {
			return;
		}

		// Registered inline-only handle: WP_Styles dedupes by handle across every
		// embedded form and prints via print_late_styles() in the footer even when
		// enqueued this late (during the_content).
		$style_handle = 'srfm-edit-form-btn';
		if ( ! wp_style_is( $style_handle, 'registered' ) ) {
			wp_register_style( $style_handle, false, [], SRFM_VER );
			wp_add_inline_style( $style_handle, self::get_edit_form_button_css() );
		}
		wp_enqueue_style( $style_handle );
		?>
		<div class="srfm-edit-form-btn-wrap">
			<a class="srfm-edit-form-btn" href="<?php echo esc_url( $edit_link ); ?>" target="_blank" rel="noopener noreferrer">
				<svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
				<span><?php esc_html_e( 'Edit Form', 'sureforms' ); ?></span>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'sureforms' ); ?></span>
			</a>
		</div>
		<?php
	}

	/**
	 * Stylesheet for the admin "Edit Form" pill (#3029).
	 *
	 * The wrapper is a flow-level flex row rather than an absolute overlay, so the
	 * pill reserves its own space and cannot cover a field (#3062). `justify-content`
	 * uses the logical `flex-end`, which follows the writing direction and is
	 * therefore RTL-correct without a separate rule.
	 *
	 * No `position: relative` on the container any more: that rule existed solely to
	 * be the positioning context for the old overlay.
	 *
	 * @return string
	 * @since 2.12.4
	 */
	private static function get_edit_form_button_css() {
		return '
		.srfm-edit-form-btn-wrap {
			display: flex;
			justify-content: flex-end;
			margin-block-end: 8px;
		}
		.srfm-edit-form-btn {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 6px 12px;
			font-size: 13px;
			font-weight: 500;
			line-height: 1;
			color: #1e293b;
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 9999px;
			box-shadow: 0 2px 6px rgba( 0, 0, 0, 0.12 );
			text-decoration: none;
		}
		.srfm-edit-form-btn:hover { border-color: #cbd5e1; color: #0f172a; }
		.srfm-edit-form-btn:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
		.srfm-edit-form-btn svg { width: 14px; height: 14px; }
		';
	}
}
