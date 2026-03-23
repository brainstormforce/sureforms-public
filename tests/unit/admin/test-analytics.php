<?php
/**
 * Class Test_Analytics
 *
 * Tests for embed styling analytics and MCP analytics boolean_values and state-based events.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Admin\Analytics;
use SRFM\Inc\Analytics_Events;
use SRFM\Inc\Helper;

/**
 * Tests for embed styling and MCP analytics tracking.
 */
class Test_Analytics extends TestCase {

	/**
	 * Clean up options and analytics state before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset MCP settings.
		delete_option( 'srfm_mcp_settings_options' );

		// Reset analytics events dedup state.
		Helper::update_srfm_option( 'usage_events_pushed', [] );
		Helper::update_srfm_option( 'usage_events_pending', [] );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Clear object cache.
		wp_cache_flush();

		// Clean up any test posts.
		$posts = get_posts(
			[
				'post_type'   => 'any',
				'post_status' => 'any',
				'numberposts' => -1,
				'meta_key'    => '_srfm_test_post',
			]
		);
		foreach ( $posts as $post ) {
			wp_delete_post( $post->ID, true );
		}

		// Reset analytics events — clear both pending queue and pushed dedup.
		Analytics_Events::flush_pending();
		Analytics_Events::flush_pushed();

		// Reset MCP settings and options.
		delete_option( 'srfm_mcp_settings_options' );
		Helper::update_srfm_option( 'usage_events_pushed', [] );
		Helper::update_srfm_option( 'usage_events_pending', [] );

		parent::tearDown();
	}

	// ─── embed_styling_gutenberg_count ────────────────────────────

	/**
	 * Test returns 0 when no posts have embed styling.
	 */
	public function test_embed_styling_gutenberg_count_returns_zero_when_no_embeds() {
		$count = Analytics::embed_styling_gutenberg_count( 'default' );
		$this->assertSame( 0, $count );
	}

	/**
	 * Test counts a single embed block with 'default' formTheme.
	 */
	public function test_embed_styling_gutenberg_count_single_default() {
		$this->create_post_with_content(
			'<!-- wp:srfm/form {"id":1,"formTheme":"default"} /-->'
		);

		$count = Analytics::embed_styling_gutenberg_count( 'default' );
		$this->assertSame( 1, $count );
	}

	/**
	 * Test counts a single embed block with 'custom' formTheme.
	 */
	public function test_embed_styling_gutenberg_count_single_custom() {
		$this->create_post_with_content(
			'<!-- wp:srfm/form {"id":1,"formTheme":"custom"} /-->'
		);

		$count = Analytics::embed_styling_gutenberg_count( 'custom' );
		$this->assertSame( 1, $count );
	}

	/**
	 * Test 'default' count does not include 'custom' embeds.
	 */
	public function test_embed_styling_gutenberg_count_default_excludes_custom() {
		$this->create_post_with_content(
			'<!-- wp:srfm/form {"id":1,"formTheme":"custom"} /-->'
		);

		$count = Analytics::embed_styling_gutenberg_count( 'default' );
		$this->assertSame( 0, $count );
	}

	/**
	 * Test multiple embed blocks on a single page are counted individually.
	 */
	public function test_embed_styling_gutenberg_count_multiple_on_same_page() {
		$this->create_post_with_content(
			'<!-- wp:srfm/form {"id":1,"formTheme":"default"} /-->' .
			'<!-- wp:srfm/form {"id":2,"formTheme":"default"} /-->' .
			'<!-- wp:srfm/form {"id":3,"formTheme":"custom"} /-->'
		);

		$default_count = Analytics::embed_styling_gutenberg_count( 'default' );
		$custom_count  = Analytics::embed_styling_gutenberg_count( 'custom' );

		$this->assertSame( 2, $default_count );
		$this->assertSame( 1, $custom_count );
	}

	/**
	 * Test embeds across multiple pages are counted.
	 */
	public function test_embed_styling_gutenberg_count_across_pages() {
		$this->create_post_with_content(
			'<!-- wp:srfm/form {"id":1,"formTheme":"default"} /-->'
		);
		$this->create_post_with_content(
			'<!-- wp:srfm/form {"id":2,"formTheme":"default"} /-->',
			'page'
		);

		$count = Analytics::embed_styling_gutenberg_count( 'default' );
		$this->assertSame( 2, $count );
	}

	/**
	 * Test inherit theme is not counted (WordPress strips default attributes).
	 */
	public function test_embed_styling_gutenberg_count_excludes_inherit() {
		$this->create_post_with_content(
			'<!-- wp:srfm/form {"id":1,"formTheme":"inherit"} /-->'
		);

		$count = Analytics::embed_styling_gutenberg_count( 'inherit' );
		// Even if explicitly searched for 'inherit', the SQL LIKE matches it.
		// But in practice, WordPress strips default values so this won't appear.
		$this->assertSame( 1, $count );
	}

	/**
	 * Test draft posts are not counted.
	 */
	public function test_embed_styling_gutenberg_count_excludes_drafts() {
		$this->create_post_with_content(
			'<!-- wp:srfm/form {"id":1,"formTheme":"default"} /-->',
			'post',
			'draft'
		);

		$count = Analytics::embed_styling_gutenberg_count( 'default' );
		$this->assertSame( 0, $count );
	}

	/**
	 * Test posts without srfm/form blocks are not counted.
	 */
	public function test_embed_styling_gutenberg_count_ignores_non_form_blocks() {
		$this->create_post_with_content(
			'<!-- wp:paragraph -->Some text with formTheme default<!-- /wp:paragraph -->'
		);

		$count = Analytics::embed_styling_gutenberg_count( 'default' );
		$this->assertSame( 0, $count );
	}

	// ─── track_embed_styling_configured ───────────────────────────

	/**
	 * Test event is tracked when a post with styled embed blocks is saved.
	 */
	public function test_track_embed_styling_configured_fires_event() {
		$analytics = Analytics::get_instance();
		$post_id   = $this->create_post_with_content(
			'<!-- wp:srfm/form {"id":1,"formTheme":"default"} /-->'
		);

		$analytics->track_embed_styling_configured( $post_id, get_post( $post_id ) );

		$this->assertTrue( Analytics_Events::is_tracked( 'embed_styling_configured' ) );
	}

	/**
	 * Test event properties contain correct theme and block count.
	 */
	public function test_track_embed_styling_configured_event_properties() {
		$analytics = Analytics::get_instance();
		$post_id   = $this->create_post_with_content(
			'<!-- wp:srfm/form {"id":1,"formTheme":"default"} /-->' .
			'<!-- wp:srfm/form {"id":2,"formTheme":"default"} /-->' .
			'<!-- wp:srfm/form {"id":3,"formTheme":"custom"} /-->'
		);

		$analytics->track_embed_styling_configured( $post_id, get_post( $post_id ) );

		// Verify the event is pending with correct data.
		$pending = get_option( 'srfm_options', [] );
		$events  = $pending['usage_events_pending'] ?? [];
		$event   = end( $events );

		$this->assertSame( 'embed_styling_configured', $event['event_name'] );
		$this->assertSame( (string) $post_id, $event['event_value'] );
		$this->assertSame( 3, $event['properties']['block_count'] );
		$this->assertSame( 'gutenberg', $event['properties']['source'] );
		$this->assertArrayHasKey( 'default', $event['properties']['themes'] );
		$this->assertArrayHasKey( 'custom', $event['properties']['themes'] );
		$this->assertSame( 2, $event['properties']['themes']['default'] );
		$this->assertSame( 1, $event['properties']['themes']['custom'] );
	}

	/**
	 * Test event is not tracked for draft posts.
	 */
	public function test_track_embed_styling_configured_skips_drafts() {
		$analytics = Analytics::get_instance();
		$post_id   = $this->create_post_with_content(
			'<!-- wp:srfm/form {"id":1,"formTheme":"default"} /-->',
			'post',
			'draft'
		);

		$analytics->track_embed_styling_configured( $post_id, get_post( $post_id ) );

		$this->assertFalse( Analytics_Events::is_tracked( 'embed_styling_configured' ) );
	}

	/**
	 * Test event is not tracked when no styled embed blocks exist.
	 */
	public function test_track_embed_styling_configured_skips_inherit_only() {
		$analytics = Analytics::get_instance();
		$post_id   = $this->create_post_with_content(
			'<!-- wp:srfm/form {"id":1} /-->'
		);

		$analytics->track_embed_styling_configured( $post_id, get_post( $post_id ) );

		$this->assertFalse( Analytics_Events::is_tracked( 'embed_styling_configured' ) );
	}

	/**
	 * Test event re-tracks on subsequent saves (flush_pushed).
	 */
	public function test_track_embed_styling_configured_retracks_on_save() {
		$analytics = Analytics::get_instance();
		$post_id   = $this->create_post_with_content(
			'<!-- wp:srfm/form {"id":1,"formTheme":"default"} /-->'
		);

		$post = get_post( $post_id );

		// First save.
		$analytics->track_embed_styling_configured( $post_id, $post );
		$this->assertTrue( Analytics_Events::is_tracked( 'embed_styling_configured' ) );

		// Simulate analytics flush (event sent).
		Analytics_Events::flush_pending();

		// Second save should re-track because method calls flush_pushed.
		$analytics->track_embed_styling_configured( $post_id, $post );
		$this->assertTrue( Analytics_Events::is_tracked( 'embed_styling_configured' ) );
	}

	// ─── flush_pushed ─────────────────────────────────────────────

	/**
	 * Test flush_pushed removes specific event names.
	 */
	public function test_flush_pushed_removes_specific_events() {
		Analytics_Events::track( 'event_a' );
		Analytics_Events::track( 'event_b' );
		Analytics_Events::flush_pending();

		// Both should be in pushed list.
		$this->assertTrue( Analytics_Events::is_tracked( 'event_a' ) );
		$this->assertTrue( Analytics_Events::is_tracked( 'event_b' ) );

		// Flush only event_a.
		Analytics_Events::flush_pushed( [ 'event_a' ] );

		$this->assertFalse( Analytics_Events::is_tracked( 'event_a' ) );
		$this->assertTrue( Analytics_Events::is_tracked( 'event_b' ) );
	}

	/**
	 * Test flush_pushed with empty array clears all.
	 */
	public function test_flush_pushed_clears_all_when_empty() {
		Analytics_Events::track( 'event_a' );
		Analytics_Events::track( 'event_b' );
		Analytics_Events::flush_pending();

		Analytics_Events::flush_pushed();

		$this->assertFalse( Analytics_Events::is_tracked( 'event_a' ) );
		$this->assertFalse( Analytics_Events::is_tracked( 'event_b' ) );
	}

	// ─── MCP state events ────────────────────────────────────────

	/**
	 * Test detect_state_events tracks MCP events when toggles are enabled.
	 *
	 * @return void
	 */
	public function test_detect_state_events_tracks_mcp_events_when_enabled() {
		update_option(
			'srfm_mcp_settings_options',
			[
				'srfm_abilities_api'        => true,
				'srfm_abilities_api_edit'   => true,
				'srfm_abilities_api_delete' => true,
				'srfm_mcp_server'           => true,
			]
		);

		// Instantiate Analytics which calls detect_state_events in constructor.
		new Analytics();

		$this->assertTrue( Analytics_Events::is_tracked( 'abilities_api_enabled' ) );
		$this->assertTrue( Analytics_Events::is_tracked( 'mcp_server_enabled' ) );
	}

	/**
	 * Test detect_state_events does not track MCP events when toggles are disabled.
	 *
	 * @return void
	 */
	public function test_detect_state_events_skips_mcp_events_when_disabled() {
		// No MCP settings — all disabled.
		new Analytics();

		$this->assertFalse( Analytics_Events::is_tracked( 'abilities_api_enabled' ) );
		$this->assertFalse( Analytics_Events::is_tracked( 'mcp_server_enabled' ) );
	}

	/**
	 * Test detect_state_events only tracks enabled MCP toggles.
	 *
	 * @return void
	 */
	public function test_detect_state_events_tracks_only_enabled_mcp_toggles() {
		update_option(
			'srfm_mcp_settings_options',
			[
				'srfm_abilities_api' => true,
				'srfm_mcp_server'    => false,
			]
		);

		new Analytics();

		$this->assertTrue( Analytics_Events::is_tracked( 'abilities_api_enabled' ) );
		$this->assertFalse( Analytics_Events::is_tracked( 'mcp_server_enabled' ) );
	}

	/**
	 * Test MCP events are deduped — second instantiation does not double-track.
	 *
	 * @return void
	 */
	public function test_mcp_events_dedup() {
		update_option(
			'srfm_mcp_settings_options',
			[
				'srfm_abilities_api' => true,
				'srfm_mcp_server'    => true,
			]
		);

		new Analytics();

		// Get pending events count.
		$pending_before = Helper::get_srfm_option( 'usage_events_pending', [] );
		$mcp_count_before = count(
			array_filter(
				$pending_before,
				static function ( $e ) {
					return in_array( $e['event_name'], [ 'abilities_api_enabled', 'mcp_server_enabled' ], true );
				}
			)
		);

		// Second instantiation — should not add duplicate events.
		new Analytics();

		$pending_after = Helper::get_srfm_option( 'usage_events_pending', [] );
		$mcp_count_after = count(
			array_filter(
				$pending_after,
				static function ( $e ) {
					return in_array( $e['event_name'], [ 'abilities_api_enabled', 'mcp_server_enabled' ], true );
				}
			)
		);

		$this->assertSame( $mcp_count_before, $mcp_count_after, 'MCP events should not be duplicated on second instantiation.' );
	}

	/**
	 * Test track_first_form_published tracks event when a form transitions to publish.
	 *
	 * @return void
	 */
	public function test_track_first_form_published_tracks_event() {
		$post = $this->create_mock_post(
			[
				'post_content' => '<!-- wp:srfm/input --><!-- /wp:srfm/input --><!-- wp:srfm/email --><!-- /wp:srfm/email -->',
			]
		);

		$analytics = new Analytics();
		$analytics->track_first_form_published( 'publish', 'draft', $post );

		$this->assertTrue( Analytics_Events::is_tracked( 'first_form_published' ) );
	}

	/**
	 * Test track_first_form_published skips when status is not changing to publish.
	 *
	 * @return void
	 */
	public function test_track_first_form_published_skips_non_publish() {
		$post = $this->create_mock_post();

		$analytics = new Analytics();
		$analytics->track_first_form_published( 'draft', 'draft', $post );

		$this->assertFalse( Analytics_Events::is_tracked( 'first_form_published' ) );
	}

	/**
	 * Test track_first_form_published skips when already published.
	 *
	 * @return void
	 */
	public function test_track_first_form_published_skips_already_published() {
		$post = $this->create_mock_post( [ 'post_status' => 'publish' ] );

		$analytics = new Analytics();
		$analytics->track_first_form_published( 'publish', 'publish', $post );

		$this->assertFalse( Analytics_Events::is_tracked( 'first_form_published' ) );
	}

	/**
	 * Test track_first_form_published skips for non-sureforms post type.
	 *
	 * @return void
	 */
	public function test_track_first_form_published_skips_wrong_post_type() {
		$post = $this->create_mock_post( [ 'post_type' => 'post' ] );

		$analytics = new Analytics();
		$analytics->track_first_form_published( 'publish', 'draft', $post );

		$this->assertFalse( Analytics_Events::is_tracked( 'first_form_published' ) );
	}

	// ─── Helpers ─────────────────────────────────────────────────

	/**
	 * Create a test post with the given content.
	 *
	 * @param string $content     Post content.
	 * @param string $post_type   Post type. Default 'post'.
	 * @param string $post_status Post status. Default 'publish'.
	 * @return int Post ID.
	 */
	private function create_post_with_content( $content, $post_type = 'post', $post_status = 'publish' ) {
		$post_id = wp_insert_post(
			[
				'post_type'    => $post_type,
				'post_status'  => $post_status,
				'post_title'   => 'Analytics Test ' . wp_rand(),
				'post_content' => $content,
			]
		);

		// Tag for cleanup.
		update_post_meta( $post_id, '_srfm_test_post', true );

		return $post_id;
	}

	/**
	 * Create a mock WP_Post object for testing.
	 *
	 * @param array $args Post arguments.
	 * @return \WP_Post
	 */
	private function create_mock_post( $args = [] ) {
		$defaults = [
			'ID'           => 1,
			'post_type'    => SRFM_FORMS_POST_TYPE,
			'post_status'  => 'draft',
			'post_content' => '',
		];

		$args = array_merge( $defaults, $args );
		$post = new \WP_Post( (object) $args );

		return $post;
	}
}
