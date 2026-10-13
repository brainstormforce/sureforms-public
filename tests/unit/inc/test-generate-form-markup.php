<?php
/**
 * Class Test_Generate_Form_Markup
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Generate_Form_Markup;

class Test_Generate_Form_Markup extends TestCase {

	protected $generate_form_markup;

	protected function setUp(): void {
		parent::setUp();

		/*
		 * These tests are all front-end rendering. is_admin() reads the global
		 * current screen, which any earlier test that called set_current_screen()
		 * leaves behind for the rest of the process - and both the Edit Form pill
		 * and collect_queried_form_ids() bail out on is_admin(). Clear it so the
		 * context is the one under test rather than whatever ran before.
		 */
		set_current_screen( 'front' );

		$this->generate_form_markup = new Generate_Form_Markup();
	}

	protected function tearDown(): void {
		// Reset state a failing assertion mid-test could otherwise leak into the
		// rest of the process — a logged-in admin, or the Edit Form filters.
		wp_set_current_user( 0 );
		remove_all_filters( 'srfm_show_edit_form_button' );
		remove_all_filters( 'srfm_edit_form_button_link' );
		parent::tearDown();
	}

	/**
	 * Outside get_form_markup() no form content is rendering, and the mark is set while it runs.
	 */
	public function test_get_rendering_form_id_tracks_get_form_markup() {
		$form_id = wp_insert_post(
			[
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => 'Rendering Id Form',
				'post_content' => "<!-- wp:paragraph -->\n<p>Body</p>\n<!-- /wp:paragraph -->",
			]
		);
		$seen    = null;
		$record  = static function () use ( &$seen ) {
			$seen = Generate_Form_Markup::get_rendering_form_id();
		};
		add_action( 'srfm_after_field_content', $record );

		try {
			Generate_Form_Markup::get_form_markup( $form_id );
		} finally {
			remove_action( 'srfm_after_field_content', $record );
			wp_delete_post( $form_id, true );
		}

		$this->assertSame( $form_id, $seen );
		$this->assertSame( 0, Generate_Form_Markup::get_rendering_form_id() );
	}

	/**
	 * Called directly, render_form_markup() renders the form without marking it as form content.
	 */
	public function test_render_form_markup_does_not_mark_form_content() {
		$form_id = wp_insert_post(
			[
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => 'Direct Render Form',
				'post_content' => "<!-- wp:paragraph -->\n<p>Body</p>\n<!-- /wp:paragraph -->",
			]
		);
		$seen    = null;
		$record  = static function () use ( &$seen ) {
			$seen = Generate_Form_Markup::get_rendering_form_id();
		};
		add_action( 'srfm_after_field_content', $record );

		try {
			$markup = Generate_Form_Markup::render_form_markup( $form_id, true, '', 'post', false, [] );
		} finally {
			remove_action( 'srfm_after_field_content', $record );
			wp_delete_post( $form_id, true );
		}

		$this->assertIsString( $markup );
		$this->assertSame( 0, $seen );
	}

	public function test_get_form_markup_empty_id() {
		$result = Generate_Form_Markup::get_form_markup( 0 );
		$this->assertIsString( $result );
	}

	public function test_get_form_markup_invalid_id() {
		$result = Generate_Form_Markup::get_form_markup( 999999 );
		$this->assertIsString( $result );
	}

	public function test_get_form_markup_with_valid_form() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
			$this->markTestSkipped( 'SRFM_FORMS_POST_TYPE not defined' );
		}

		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post( [
			'post_title'   => 'Test Markup Form',
			'post_type'    => SRFM_FORMS_POST_TYPE,
			'post_status'  => 'publish',
			'post_content' => 'simple content',
		] );

		$result = Generate_Form_Markup::get_form_markup( $form_id );
		$this->assertIsString( $result );

		wp_delete_post( $form_id, true );
	}

	/**
	 * The admin-only "Edit Form" pill (#3029) is capability-gated and per-form.
	 *
	 * The sureforms_form CPT maps edit_post to manage_options, so only
	 * administrators qualify: the pill is absent for anonymous visitors and
	 * subscribers, present for an administrator with an href bound to THIS form,
	 * enqueues its stylesheet via a registered handle, a second form links to its
	 * own editor (AC #5, multiple forms on a page), and the suppression filter
	 * removes it. `tearDown()` resets the current user and filters.
	 */
	public function test_render_edit_form_button() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
			$this->markTestSkipped( 'SRFM_FORMS_POST_TYPE not defined' );
		}

		remove_all_actions( 'wp_insert_post_data' );

		// A block is required so the container opens and the pill is emitted.
		$block    = '<!-- wp:paragraph -->x<!-- /wp:paragraph -->';
		$form_id  = wp_insert_post( [ 'post_title' => 'Edit Pill A', 'post_type' => SRFM_FORMS_POST_TYPE, 'post_status' => 'publish', 'post_content' => $block ] );
		$form_id2 = wp_insert_post( [ 'post_title' => 'Edit Pill B', 'post_type' => SRFM_FORMS_POST_TYPE, 'post_status' => 'publish', 'post_content' => $block ] );

		// Anonymous visitors never receive the shortcut.
		wp_set_current_user( 0 );
		$this->assertStringNotContainsString( 'srfm-edit-form-btn', Generate_Form_Markup::get_form_markup( $form_id ) );

		// A subscriber cannot edit the form, so still no shortcut.
		$subscriber = $this->set_current_user_with_role( 'subscriber' );
		$this->assertStringNotContainsString( 'srfm-edit-form-btn', Generate_Form_Markup::get_form_markup( $form_id ) );

		// An administrator gets the pill, its href bound to THIS form, with the
		// stylesheet enqueued via its registered handle.
		$admin  = $this->set_current_user_with_role( 'administrator' );
		$markup = Generate_Form_Markup::get_form_markup( $form_id );
		$this->assertStringContainsString( 'class="srfm-edit-form-btn"', $markup );

		// Assert the whole href, not just `post=<id>`. The marker is appended with
		// add_query_arg(), so the thing worth guarding is that the link still points
		// at the editor — a refactor that lost `action=edit` would leave a bare
		// `post=<id>` assertion green while the pill stopped opening the editor.
		$expected_href = esc_url(
			add_query_arg(
				Generate_Form_Markup::EDIT_FORM_BUTTON_SOURCE_ARG,
				'embed',
				get_edit_post_link( $form_id, 'raw' )
			)
		);
		$this->assertStringContainsString( 'href="' . $expected_href . '"', $markup, 'The pill href should be the editor link for this form, carrying the marker.' );
		$this->assertMatchesRegularExpression( '/post=' . $form_id . '&(amp;|#0?38;)?action=edit/', $markup, 'The link must still open the editor.' );
		$this->assertTrue( wp_style_is( 'srfm-edit-form-btn', 'enqueued' ), 'The Edit Form stylesheet should be enqueued.' );

		// The pill sits in normal flow ABOVE the form, which is what stops it
		// overlapping a field (#3062). DOM order is the guarantee — an overlay only
		// clears the fields when the container happens to have enough top padding.
		$pill_pos = strpos( $markup, 'srfm-edit-form-btn-wrap' );
		$form_pos = strpos( $markup, '<form ' );
		$this->assertNotFalse( $pill_pos, 'The pill should be wrapped in its flow-level row.' );
		$this->assertNotFalse( $form_pos, 'The form element should be present.' );
		$this->assertLessThan( $form_pos, $pill_pos, 'The pill must render before the <form>, not overlaid on it.' );

		// The link carries the attribution marker that Admin reads back on load-post.php.
		$this->assertStringContainsString( Generate_Form_Markup::EDIT_FORM_BUTTON_SOURCE_ARG . '=embed', html_entity_decode( $markup ), 'The edit link should carry the analytics source marker.' );

		// A second form links to its OWN editor, not the first — the multiple-forms
		// acceptance criterion, and a guard against passing the wrong post ID.
		$markup2 = Generate_Form_Markup::get_form_markup( $form_id2 );
		$this->assertStringContainsString( 'post=' . $form_id2, $markup2 );

		// Anchored on the full param rather than a `post=<id>&` prefix, which only
		// avoided a collision between the two IDs by the shape they happen to have.
		preg_match_all( '/post=(\d+)/', html_entity_decode( $markup2 ), $matches );
		$this->assertNotEmpty( $matches[1], 'The second form should link to an editor.' );
		$this->assertNotContains( (string) $form_id, $matches[1], 'The second form must not link to the first form\'s editor.' );

		// A suppression filter removes it even for an administrator.
		add_filter( 'srfm_show_edit_form_button', '__return_false' );
		$this->assertStringNotContainsString( 'srfm-edit-form-btn', Generate_Form_Markup::get_form_markup( $form_id ) );

		wp_delete_user( $subscriber );
		wp_delete_user( $admin );
		wp_delete_post( $form_id, true );
		wp_delete_post( $form_id2, true );
	}

	/**
	 * The pill's stylesheet must keep it in normal flow.
	 *
	 * This is the half of the overlap fix (#3062) that DOM order alone cannot
	 * guarantee: rendering above the form is pointless if the CSS lifts the pill
	 * back out of flow and drops it onto the first row of fields. The container's
	 * `position: relative` rule goes with it — it existed only to anchor the old
	 * overlay, so leaving it behind would be dead CSS shipped to every page with
	 * an embedded form.
	 */
	public function test_get_edit_form_button_css() {
		$css = $this->call_private_static( Generate_Form_Markup::class, 'get_edit_form_button_css' );

		$this->assertStringNotContainsString( 'position: absolute', $css, 'The pill must not be absolutely positioned over the form.' );
		$this->assertStringNotContainsString( 'position: relative', $css, 'The container no longer needs a positioning context.' );
		$this->assertStringContainsString( '.srfm-edit-form-btn-wrap', $css, 'The flow-level wrapper must be styled.' );
		$this->assertStringContainsString( 'justify-content: flex-end', $css, 'The pill should sit at the inline end of its own row.' );

		// Logical, not physical — the row has to read correctly in RTL without a
		// second rule, which is why the old build used inset-inline-end.
		$this->assertStringNotContainsString( 'margin-bottom', $css, 'Spacing should use the logical margin-block-end.' );
		$this->assertStringContainsString( 'margin-block-end', $css );
	}

	public function test_add_entries_admin_bar_node() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) || ! defined( 'SRFM_ENTRIES' ) ) {
			$this->markTestSkipped( 'SureForms constants not defined' );
		}

		// Only users who can view entries (manage_options) get the node.
		$admin = wp_insert_user(
			[
				'user_login' => 'srfm_entries_admin_' . wp_rand(),
				'user_pass'  => 'password',
				'user_email' => 'srfm_entries_admin_' . wp_rand() . '@example.com',
				'role'       => 'administrator',
			]
		);
		wp_set_current_user( is_wp_error( $admin ) ? 0 : (int) $admin );
		add_filter( 'show_admin_bar', '__return_true' );

		// Drive the node from the rendered-form registry directly (set via
		// reflection) — this is what get_form_markup() populates for every embed
		// path, and avoids mutating query globals.
		$registry = new ReflectionProperty( Generate_Form_Markup::class, 'rendered_form_ids' );
		$registry->setAccessible( true );
		$registry->setValue( null, [] );

		require_once ABSPATH . 'wp-includes/class-wp-admin-bar.php';

		$form_id  = wp_insert_post(
			[
				'post_title'  => 'Admin Bar Entries Form',
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_status' => 'publish',
			]
		);
		$form_id2 = wp_insert_post(
			[
				// Author-controlled title with HTML — must be escaped in the node.
				'post_title'  => '<b>XSS</b> Form',
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_status' => 'publish',
			]
		);

		// One form on the page → a single node linking to its filtered entries.
		$registry->setValue( null, [ $form_id => true ] );
		$bar  = new WP_Admin_Bar();
		$this->generate_form_markup->add_entries_admin_bar_node( $bar );
		$node = $bar->get_node( 'srfm-entries' );
		$this->assertNotNull( $node, 'Entries node should be added when a form is on the page.' );
		$this->assertStringContainsString( 'page=' . SRFM_ENTRIES, $node->href );
		$this->assertStringContainsString( 'form=' . $form_id, $node->href );

		// Two forms → a submenu: parent unfiltered, one child per form. The child
		// title is escaped (WP_Admin_Bar does not escape node titles).
		$registry->setValue( null, [ $form_id => true, $form_id2 => true ] );
		$bar_multi = new WP_Admin_Bar();
		$this->generate_form_markup->add_entries_admin_bar_node( $bar_multi );
		$parent = $bar_multi->get_node( 'srfm-entries' );
		$this->assertNotNull( $parent );
		$this->assertStringNotContainsString( 'form=', $parent->href, 'Parent links to unfiltered Entries.' );
		$child = $bar_multi->get_node( 'srfm-entries-' . $form_id2 );
		$this->assertNotNull( $child, 'Each form gets a submenu child.' );
		$this->assertStringContainsString( 'form=' . $form_id2, $child->href );
		// Pin the exact escaping contract: strip tags, then esc_html. Swapping
		// either step (e.g. for wp_kses_post or strip_tags alone) fails this.
		$this->assertSame(
			esc_html( wp_strip_all_tags( get_the_title( $form_id2 ) ) ),
			(string) $child->title,
			'Form titles must be tag-stripped and escaped in the node.'
		);
		$this->assertStringNotContainsString( '<b>', (string) $child->title, 'Form titles must be escaped in the node.' );

		// Subscriber (no manage_options) → no node.
		$subscriber = wp_insert_user(
			[
				'user_login' => 'srfm_entries_sub_' . wp_rand(),
				'user_pass'  => 'password',
				'user_email' => 'srfm_entries_sub_' . wp_rand() . '@example.com',
				'role'       => 'subscriber',
			]
		);
		wp_set_current_user( is_wp_error( $subscriber ) ? 0 : (int) $subscriber );
		$bar_sub = new WP_Admin_Bar();
		$this->generate_form_markup->add_entries_admin_bar_node( $bar_sub );
		$this->assertNull( $bar_sub->get_node( 'srfm-entries' ), 'Users without the entries capability must not see the node.' );

		// Cleanup.
		$registry->setValue( null, [] );
		remove_filter( 'show_admin_bar', '__return_true' );
		wp_delete_post( $form_id, true );
		wp_delete_post( $form_id2, true );
		wp_set_current_user( 0 );
		if ( ! is_wp_error( $admin ) ) {
			wp_delete_user( (int) $admin );
		}
		if ( ! is_wp_error( $subscriber ) ) {
			wp_delete_user( (int) $subscriber );
		}
	}

	public function test_collect_queried_form_ids() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
			$this->markTestSkipped( 'SRFM_FORMS_POST_TYPE not defined' );
		}

		$registry = new ReflectionProperty( Generate_Form_Markup::class, 'rendered_form_ids' );
		$registry->setAccessible( true );
		$registry->setValue( null, [] );

		$form_id = wp_insert_post(
			[
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_title'  => 'Collected Form',
				'post_status' => 'publish',
			]
		);
		// A page embedding the form via the srfm/form block (no shortcode-registration
		// dependency — has_block() is a content scan).
		$page_id = wp_insert_post(
			[
				'post_type'    => 'page',
				'post_title'   => 'Has Form',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:srfm/form {"id":' . $form_id . '} /-->',
			]
		);

		global $wp_query;
		$prev_is_singular    = $wp_query->is_singular;
		$prev_queried_object = $wp_query->get_queried_object();
		$prev_queried_id     = $wp_query->get_queried_object_id();

		// Simulate being on the page that embeds the form (the `wp`-hook context).
		$wp_query->is_singular       = true;
		$wp_query->queried_object    = get_post( $page_id );
		$wp_query->queried_object_id = $page_id;

		/*
		 * collect_queried_form_ids() only does the parse when the admin bar is
		 * actually going to be rendered for a user who can see its node - that
		 * guard is what keeps parse_blocks() off every anonymous request. Stand
		 * both up, or the method returns before collecting anything.
		 */
		$admin_user = $this->set_current_user_with_role( 'administrator' );
		$show_bar   = static function () {
			return true;
		};
		add_filter( 'show_admin_bar', $show_bar );

		try {
			$this->generate_form_markup->collect_queried_form_ids();
			$this->assertArrayHasKey( $form_id, $registry->getValue(), 'The embedded form ID should be collected from the queried post at `wp`.' );
		} finally {
			remove_filter( 'show_admin_bar', $show_bar );
		}

		// Not a singular view → no-op.
		$registry->setValue( null, [] );
		$wp_query->is_singular = false;
		$this->generate_form_markup->collect_queried_form_ids();
		$this->assertSame( [], $registry->getValue(), 'Nothing is collected outside a singular view.' );

		// Restore query globals + clean up.
		$wp_query->is_singular       = $prev_is_singular;
		$wp_query->queried_object    = $prev_queried_object;
		$wp_query->queried_object_id = $prev_queried_id;
		$registry->setValue( null, [] );
		wp_delete_post( $page_id, true );
		wp_delete_post( $form_id, true );
	}

	public function test_register_custom_endpoint() {
		do_action( 'rest_api_init' );
		$routes = rest_get_server()->get_routes();
		$found = false;
		foreach ( array_keys( $routes ) as $route ) {
			if ( strpos( $route, 'sureforms/v1/generate-form-markup' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'The generate-form-markup endpoint should be registered' );
	}

	/**
	 * Invoke a private static method.
	 *
	 * @param string $class_name  Fully-qualified class name.
	 * @param string $method_name Method to invoke.
	 * @return mixed
	 */
	private function call_private_static( $class_name, $method_name ) {
		$method = new ReflectionMethod( $class_name, $method_name );
		$method->setAccessible( true );
		return $method->invoke( null );
	}

	/**
	 * Create a user with the given role and make it the current user.
	 *
	 * @param string $role Role name.
	 * @return int User ID.
	 */
	private function set_current_user_with_role( $role ) {
		$user_id = wp_insert_user(
			[
				'user_login' => 'srfm_markup_' . $role . '_' . wp_rand(),
				'user_pass'  => 'password',
				'user_email' => 'srfm_markup_' . $role . '_' . wp_rand() . '@example.com',
				'role'       => $role,
			]
		);

		$user_id = is_wp_error( $user_id ) ? 0 : (int) $user_id;
		wp_set_current_user( $user_id );

		return $user_id;
	}

	/**
	 * Build a request for the form-markup endpoint.
	 *
	 * @param int $form_id Requested ID.
	 * @return WP_REST_Request
	 */
	private function make_markup_request( $form_id ) {
		$request = new WP_REST_Request( 'GET', '/sureforms/v1/generate-form-markup' );
		$request->set_param( 'id', $form_id );

		return $request;
	}

	/**
	 * The endpoint requires a user who can edit content — a nonce is not authorization.
	 *
	 * See #2995: the route was registered with `__return_true` and gated only by the
	 * `srfm_form_markup` nonce, which every user who can open the block editor holds.
	 */
	public function test_render_form_markup_permissions_check_requires_edit_posts() {
		// Anonymous.
		wp_set_current_user( 0 );
		$this->assertInstanceOf( WP_Error::class, $this->generate_form_markup->render_form_markup_permissions_check() );

		// Subscriber — cannot edit content.
		$subscriber = $this->set_current_user_with_role( 'subscriber' );
		$this->assertInstanceOf( WP_Error::class, $this->generate_form_markup->render_form_markup_permissions_check() );

		// Editor — can edit content, so the preview endpoint is available.
		$editor = $this->set_current_user_with_role( 'editor' );
		$this->assertTrue( $this->generate_form_markup->render_form_markup_permissions_check() );

		wp_set_current_user( 0 );
		wp_delete_user( $subscriber );
		wp_delete_user( $editor );
	}

	/**
	 * The endpoint must refuse any post that is not a SureForms form.
	 *
	 * This was the disclosure: `get_post( $id )` was unconstrained, so drafts,
	 * pending, private posts and private CPTs of any author rendered in full.
	 */
	public function test_render_form_markup_endpoint_rejects_non_form_post() {
		$admin = $this->set_current_user_with_role( 'administrator' );

		$private_post = wp_insert_post(
			[
				'post_title'   => 'Unpublished secret',
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_content' => 'CONFIDENTIAL-MARKER',
			]
		);

		$result = $this->generate_form_markup->render_form_markup_endpoint( $this->make_markup_request( $private_post ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_rest_form_not_found', $result->get_error_code() );

		// A non-existent ID behaves the same way.
		$missing = $this->generate_form_markup->render_form_markup_endpoint( $this->make_markup_request( 999999 ) );
		$this->assertInstanceOf( WP_Error::class, $missing );

		wp_delete_post( $private_post, true );
		wp_set_current_user( 0 );
		wp_delete_user( $admin );
	}

	/**
	 * An unpublished form is only renderable by users with the SureForms forms capability.
	 */
	public function test_render_form_markup_endpoint_rejects_unpublished_form_for_editor() {
		remove_all_actions( 'wp_insert_post_data' );

		$draft_form = wp_insert_post(
			[
				'post_title'   => 'Draft Form',
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'draft',
				'post_content' => 'simple content',
			]
		);

		// Editor passes the endpoint capability but has no manage_options.
		$editor = $this->set_current_user_with_role( 'editor' );
		$result = $this->generate_form_markup->render_form_markup_endpoint( $this->make_markup_request( $draft_form ) );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_rest_cannot_render_form', $result->get_error_code() );

		// Administrator can render it.
		$admin = $this->set_current_user_with_role( 'administrator' );
		$this->assertIsString( $this->generate_form_markup->render_form_markup_endpoint( $this->make_markup_request( $draft_form ) ) );

		wp_delete_post( $draft_form, true );
		wp_set_current_user( 0 );
		wp_delete_user( $editor );
		wp_delete_user( $admin );
	}

	/**
	 * A published form still renders — the legitimate editor-preview path.
	 */
	public function test_render_form_markup_endpoint_renders_published_form() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post(
			[
				'post_title'   => 'Published Form',
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => 'simple content',
			]
		);

		$editor = $this->set_current_user_with_role( 'editor' );
		$result = $this->generate_form_markup->render_form_markup_endpoint( $this->make_markup_request( $form_id ) );

		$this->assertIsString( $result );
		$this->assertStringContainsString( 'srfm-form-container', $result );

		wp_delete_post( $form_id, true );
		wp_set_current_user( 0 );
		wp_delete_user( $editor );
	}

	/**
	 * get_form_markup() must render the ID it was given, never one from the query string.
	 *
	 * The query-string override let `?id=&srfm_form_markup_nonce=` on any page
	 * embedding a form swap in a different post — see #2995.
	 */
	public function test_get_form_markup_ignores_query_string_id() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post(
			[
				'post_title'   => 'Embedded Form',
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => 'simple content',
			]
		);
		$other   = wp_insert_post(
			[
				'post_title'   => 'Other post',
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_content' => 'CONFIDENTIAL-MARKER',
			]
		);

		$_GET['id']                     = $other;
		$_GET['srfm_form_markup_nonce'] = wp_create_nonce( 'srfm_form_markup' );

		$markup = Generate_Form_Markup::get_form_markup( $form_id );

		$this->assertStringNotContainsString( 'CONFIDENTIAL-MARKER', $markup, 'The query string must not redirect the render to another post.' );
		$this->assertStringContainsString( 'srfm-form-container-' . $form_id, $markup );

		unset( $_GET['id'], $_GET['srfm_form_markup_nonce'] );
		wp_delete_post( $form_id, true );
		wp_delete_post( $other, true );
	}

	/**
	 * Test get_confirmation_markup returns string.
	 */
	public function test_get_confirmation_markup() {
		$result = Generate_Form_Markup::get_confirmation_markup();
		$this->assertIsString( $result );
	}

	/**
	 * Test get_current_block_attrs returns an array.
	 */
	public function test_get_current_block_attrs_returns_array() {
		$result = Generate_Form_Markup::get_current_block_attrs();
		$this->assertIsArray( $result );
	}

	/**
	 * Test enqueue_preview_styling_script enqueues the script.
	 */
	public function test_enqueue_preview_styling_script_enqueues_script() {
		Generate_Form_Markup::enqueue_preview_styling_script( '#srfm-container-1' );
		$this->assertTrue( wp_script_is( 'srfm-preview-styling', 'enqueued' ) );
		wp_dequeue_script( 'srfm-preview-styling' );
	}

	/**
	 * Test common_error_message outputs error markup.
	 */
	public function test_common_error_message_outputs_markup() {
		ob_start();
		Generate_Form_Markup::common_error_message();
		$output = ob_get_clean();
		$this->assertStringContainsString( 'srfm-error-message', $output );
		$this->assertStringContainsString( 'srfm-footer-error', $output );
	}

	public function test_get_form_markup_returns_string() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post( [
			'post_title'   => 'String Test Form',
			'post_type'    => 'sureforms_form',
			'post_status'  => 'publish',
			'post_content' => '',
		] );

		$result = Generate_Form_Markup::get_form_markup( $form_id );
		$this->assertIsString( $result );

		// The render-time path additively records the form ID in the admin-bar
		// registry (covers page-builder / FSE embeds a content parse can't see).
		$registry = new ReflectionProperty( Generate_Form_Markup::class, 'rendered_form_ids' );
		$registry->setAccessible( true );
		$registry->setValue( null, [] );
		Generate_Form_Markup::get_form_markup( $form_id );
		$this->assertArrayHasKey( $form_id, $registry->getValue(), 'get_form_markup() should record the form ID.' );
		$registry->setValue( null, [] );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Test form markup contains the HMAC submit token attribute.
	 */
	public function test_form_markup_contains_submit_token() {
		remove_all_actions( 'wp_insert_post_data' );

		// A block is required so the container - and with it the <form> tag the
		// token lives on - is emitted at all.
		$form_id = wp_insert_post( [
			'post_title'   => 'Token Markup Test',
			'post_type'    => 'sureforms_form',
			'post_status'  => 'publish',
			'post_content' => '<!-- wp:paragraph -->x<!-- /wp:paragraph -->',
		] );

		$markup = Generate_Form_Markup::get_form_markup( $form_id );
		$this->assertStringContainsString( 'data-submit-token=', $markup, 'Form markup should contain data-submit-token attribute.' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Test form markup does not contain old nonce attributes.
	 */
	public function test_form_markup_does_not_contain_old_nonce_attributes() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post( [
			'post_title'   => 'No Nonce Markup Test',
			'post_type'    => 'sureforms_form',
			'post_status'  => 'publish',
			'post_content' => '',
		] );

		$markup = Generate_Form_Markup::get_form_markup( $form_id );
		$this->assertStringNotContainsString( 'data-nonce=', $markup, 'Form markup should not contain old data-nonce attribute.' );
		$this->assertStringNotContainsString( 'data-update-nonce=', $markup, 'Form markup should not contain old data-update-nonce attribute.' );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Test get_redirect_url returns empty string when form_data is empty.
	 */
	public function test_get_redirect_url_empty_form_data() {
		$result = Generate_Form_Markup::get_redirect_url();
		$this->assertSame( '', $result );
	}

	/**
	 * Test get_redirect_url returns empty string when no confirmation meta exists.
	 */
	public function test_get_redirect_url_no_confirmation_meta() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post( [
			'post_title'  => 'Redirect URL Test Form',
			'post_type'   => 'sureforms_form',
			'post_status' => 'publish',
		] );

		$result = Generate_Form_Markup::get_redirect_url( [ 'form-id' => $form_id ] );
		$this->assertSame( '', $result );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Test get_redirect_url returns page URL for "different page" confirmation type.
	 */
	public function test_get_redirect_url_different_page() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post( [
			'post_title'  => 'Redirect Page Test',
			'post_type'   => 'sureforms_form',
			'post_status' => 'publish',
		] );

		$confirmation = [
			[
				'confirmation_type' => 'different page',
				'page_url'          => 'https://example.com/thank-you',
				'custom_url'        => '',
			],
		];
		update_post_meta( $form_id, '_srfm_form_confirmation', $confirmation );

		$result = Generate_Form_Markup::get_redirect_url( [ 'form-id' => $form_id ] );
		$this->assertSame( 'https://example.com/thank-you', $result );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Test get_redirect_url returns custom URL for "custom url" confirmation type.
	 */
	public function test_get_redirect_url_custom_url() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post( [
			'post_title'  => 'Redirect Custom URL Test',
			'post_type'   => 'sureforms_form',
			'post_status' => 'publish',
		] );

		$confirmation = [
			[
				'confirmation_type' => 'custom url',
				'page_url'          => '',
				'custom_url'        => 'https://example.com/custom-redirect',
			],
		];
		update_post_meta( $form_id, '_srfm_form_confirmation', $confirmation );

		$result = Generate_Form_Markup::get_redirect_url( [ 'form-id' => $form_id ] );
		$this->assertSame( 'https://example.com/custom-redirect', $result );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Test get_redirect_url appends query params when enabled.
	 */
	public function test_get_redirect_url_with_query_params() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post( [
			'post_title'  => 'Redirect Query Params Test',
			'post_type'   => 'sureforms_form',
			'post_status' => 'publish',
		] );

		$confirmation = [
			[
				'confirmation_type'    => 'custom url',
				'custom_url'           => 'https://example.com/result',
				'page_url'             => '',
				'enable_query_params'  => true,
				'query_params'         => [
					[ 'status' => 'submitted' ],
					[ 'ref' => 'form' ],
				],
			],
		];
		update_post_meta( $form_id, '_srfm_form_confirmation', $confirmation );

		$result = Generate_Form_Markup::get_redirect_url( [ 'form-id' => $form_id ] );
		$this->assertStringContainsString( 'status=submitted', $result );
		$this->assertStringContainsString( 'ref=form', $result );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Test get_redirect_url returns URL without query params when disabled.
	 */
	public function test_get_redirect_url_query_params_disabled() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post( [
			'post_title'  => 'Redirect No Params Test',
			'post_type'   => 'sureforms_form',
			'post_status' => 'publish',
		] );

		$confirmation = [
			[
				'confirmation_type'    => 'custom url',
				'custom_url'           => 'https://example.com/result',
				'page_url'             => '',
				'enable_query_params'  => false,
				'query_params'         => [
					[ 'status' => 'submitted' ],
				],
			],
		];
		update_post_meta( $form_id, '_srfm_form_confirmation', $confirmation );

		$result = Generate_Form_Markup::get_redirect_url( [ 'form-id' => $form_id ] );
		$this->assertStringNotContainsString( 'status=submitted', $result );
		$this->assertSame( 'https://example.com/result', $result );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Test get_google_captcha_script renders the widget + enqueues the right
	 * Google script per version, and falls back to the missing-sitekey error.
	 */
	public function test_get_google_captcha_script() {
		// Empty site key => missing-sitekey error, no widget rendered.
		ob_start();
		Generate_Form_Markup::get_google_captcha_script( 'v2-checkbox', '' );
		$empty_output = ob_get_clean();
		$this->assertStringContainsString( 'sitekey-error', $empty_output );
		$this->assertStringNotContainsString( 'g-recaptcha', $empty_output );

		// v2-checkbox => g-recaptcha widget carrying the site key, and the
		// google-recaptcha script enqueued.
		ob_start();
		Generate_Form_Markup::get_google_captcha_script( 'v2-checkbox', 'test-site-key-v2' );
		$v2_output = ob_get_clean();
		$this->assertStringContainsString( 'g-recaptcha', $v2_output );
		$this->assertStringContainsString( 'test-site-key-v2', $v2_output );
		$this->assertTrue( wp_script_is( 'google-recaptcha', 'enqueued' ) );
		wp_dequeue_script( 'google-recaptcha' );

		// v3 => the dedicated v3 handle is enqueued.
		ob_start();
		Generate_Form_Markup::get_google_captcha_script( 'v3-reCAPTCHA', 'test-site-key-v3' );
		ob_get_clean();
		$this->assertTrue( wp_script_is( 'srfm-google-recaptchaV3', 'enqueued' ) );
		wp_dequeue_script( 'srfm-google-recaptchaV3' );
	}

	/**
	 * Test get_cf_turnstile_script renders the Turnstile widget + enqueues the
	 * Cloudflare script, and falls back to the missing-sitekey error.
	 */
	public function test_get_cf_turnstile_script() {
		// The Turnstile enqueue passes a legacy $args shape that WordPress trunk
		// flags via _doing_it_wrong (a PHP notice that PHPUnit would convert to a
		// failure). Suppress just the triggered notice for the duration of this
		// test so the rendered markup can still be asserted.
		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );

		// Empty site key => missing-sitekey error, no widget.
		ob_start();
		Generate_Form_Markup::get_cf_turnstile_script( 'light', '' );
		$empty_output = ob_get_clean();
		$this->assertStringContainsString( 'sitekey-error', $empty_output );
		$this->assertStringNotContainsString( 'cf-turnstile', $empty_output );

		// Valid site key => cf-turnstile widget with the key + appearance mode,
		// and the Cloudflare Turnstile script enqueued.
		ob_start();
		Generate_Form_Markup::get_cf_turnstile_script( 'dark', 'test-turnstile-key' );
		$output = ob_get_clean();
		$this->assertStringContainsString( 'cf-turnstile', $output );
		$this->assertStringContainsString( 'test-turnstile-key', $output );
		$this->assertStringContainsString( 'dark', $output );
		$this->assertTrue( wp_script_is( SRFM_SLUG . '-cf-turnstile', 'enqueued' ) );
		wp_dequeue_script( SRFM_SLUG . '-cf-turnstile' );

		remove_filter( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	/**
	 * Create a published form with optional Custom CSS / disable-styling meta.
	 *
	 * @param string $custom_css Custom CSS meta value.
	 * @param bool   $disable    Whether default styling is disabled.
	 * @return int Form ID.
	 */
	private function make_form_with_styling( $custom_css = '', $disable = false ) {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post(
			[
				'post_title'   => 'Styling test form',
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => 'simple content',
			]
		);

		if ( '' !== $custom_css ) {
			update_post_meta( $form_id, '_srfm_form_custom_css', $custom_css );
		}
		if ( $disable ) {
			update_post_meta( $form_id, '_srfm_forms_styling', [ 'disable_default_styles' => true ] );
		}

		return $form_id;
	}

	/**
	 * Custom CSS appears exactly once on embedded views and is NOT emitted by
	 * get_form_markup() on the form's own single/instant view — there
	 * templates/single-form.php owns the (head, unscoped) output, so a second
	 * copy here would duplicate it.
	 */
	public function test_custom_css_once_embedded_and_absent_on_single_view() {
		$css     = '.srfm-test-marker { color: red; }';
		$form_id = $this->make_form_with_styling( $css );

		// Embedded context: global post is not the form.
		$GLOBALS['post'] = null;
		$markup          = Generate_Form_Markup::get_form_markup( $form_id );
		$this->assertSame( 1, substr_count( $markup, '.srfm-test-marker' ), 'Embedded markup must contain the Custom CSS exactly once.' );

		// Single/instant view context: the form itself is the queried post.
		$GLOBALS['post'] = get_post( $form_id );
		$markup          = Generate_Form_Markup::get_form_markup( $form_id );
		$this->assertStringNotContainsString( '.srfm-test-marker', $markup, 'On the single view the template outputs the Custom CSS; the markup must not duplicate it.' );

		$GLOBALS['post'] = null;
		wp_delete_post( $form_id, true );
	}

	/**
	 * Custom CSS still applies when default styling is disabled, the marker
	 * class renders, and the inline --srfm-* variable block is skipped.
	 */
	public function test_custom_css_and_marker_class_when_styling_disabled() {
		$css     = '.srfm-test-marker { color: red; }';
		$form_id = $this->make_form_with_styling( $css, true );

		$GLOBALS['post'] = null;
		$markup          = Generate_Form_Markup::get_form_markup( $form_id );

		$this->assertStringContainsString( '.srfm-test-marker', $markup );
		$this->assertStringContainsString( 'srfm-styling-none', $markup );
		$this->assertStringNotContainsString( '--srfm-color-scheme-primary', $markup );

		wp_delete_post( $form_id, true );
	}

	/**
	 * With default styling enabled the inline variable block renders and the
	 * marker class is absent.
	 */
	public function test_inline_variables_present_when_styling_enabled() {
		$form_id = $this->make_form_with_styling();

		$GLOBALS['post'] = null;
		$markup          = Generate_Form_Markup::get_form_markup( $form_id );

		$this->assertStringContainsString( '--srfm-color-scheme-primary', $markup );
		$this->assertStringNotContainsString( 'srfm-styling-none', $markup );

		wp_delete_post( $form_id, true );
	}

	/**
	 * When styling is disabled and there is no Custom CSS, no style tag is
	 * emitted at all (no empty scoped ruleset).
	 */
	public function test_no_style_tag_when_disabled_without_custom_css() {
		$form_id = $this->make_form_with_styling( '', true );

		$GLOBALS['post'] = null;
		$markup          = Generate_Form_Markup::get_form_markup( $form_id );

		$this->assertStringNotContainsString( '<style', $markup );
		$this->assertStringContainsString( 'srfm-styling-none', $markup );

		wp_delete_post( $form_id, true );
	}

	/**
	 * blockId comes from block attributes and reaches a container id and a <style>
	 * selector, so anything outside the CSS-safe set is dropped rather than printed.
	 */
	public function test_get_form_markup_sanitises_block_id_in_the_container_id() {
		remove_all_actions( 'wp_insert_post_data' );

		$form_id = wp_insert_post(
			[
				'post_title'   => 'Block id scoping',
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => 'content',
			]
		);

		$markup = Generate_Form_Markup::get_form_markup( $form_id, true, '', 'post', false, [ 'blockId' => 'abc"><style>x{}' ] );

		$this->assertStringContainsString( 'srfm-form-container-' . $form_id . '-abcstylex', $markup );
		$this->assertStringNotContainsString( '<style>x{}', $markup );

		wp_delete_post( $form_id, true );
	}
}
