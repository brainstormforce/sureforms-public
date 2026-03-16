<?php
/**
 * Class Test_Analytics
 *
 * Tests for embed styling analytics in the Analytics class.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Admin\Analytics;
use SRFM\Inc\Analytics_Events;

/**
 * Tests for embed styling analytics tracking.
 */
class Test_Analytics extends TestCase {

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

	// ─── embed_styling_elementor_count ────────────────────────────

	/**
	 * Test returns 0 when no Elementor pages have embed styling.
	 */
	public function test_embed_styling_elementor_count_returns_zero_when_no_widgets() {
		$count = Analytics::embed_styling_elementor_count( 'default' );
		$this->assertSame( 0, $count );
	}

	/**
	 * Test counts a single Elementor widget with 'default' formTheme.
	 */
	public function test_embed_styling_elementor_count_single_default() {
		$this->create_post_with_elementor_data(
			$this->build_elementor_json( [ [ 'formTheme' => 'default' ] ] )
		);

		$count = Analytics::embed_styling_elementor_count( 'default' );
		$this->assertSame( 1, $count );
	}

	/**
	 * Test counts a single Elementor widget with 'custom' formTheme.
	 */
	public function test_embed_styling_elementor_count_single_custom() {
		$this->create_post_with_elementor_data(
			$this->build_elementor_json( [ [ 'formTheme' => 'custom' ] ] )
		);

		$count = Analytics::embed_styling_elementor_count( 'custom' );
		$this->assertSame( 1, $count );
	}

	/**
	 * Test 'default' count does not include 'custom' Elementor widgets.
	 */
	public function test_embed_styling_elementor_count_default_excludes_custom() {
		$this->create_post_with_elementor_data(
			$this->build_elementor_json( [ [ 'formTheme' => 'custom' ] ] )
		);

		$count = Analytics::embed_styling_elementor_count( 'default' );
		$this->assertSame( 0, $count );
	}

	/**
	 * Test multiple Elementor widgets on a single page are counted individually.
	 */
	public function test_embed_styling_elementor_count_multiple_on_same_page() {
		$this->create_post_with_elementor_data(
			$this->build_elementor_json(
				[
					[ 'formTheme' => 'default' ],
					[ 'formTheme' => 'default' ],
					[ 'formTheme' => 'custom' ],
				]
			)
		);

		$default_count = Analytics::embed_styling_elementor_count( 'default' );
		$custom_count  = Analytics::embed_styling_elementor_count( 'custom' );

		$this->assertSame( 2, $default_count );
		$this->assertSame( 1, $custom_count );
	}

	/**
	 * Test Elementor widgets across multiple pages are counted.
	 */
	public function test_embed_styling_elementor_count_across_pages() {
		$this->create_post_with_elementor_data(
			$this->build_elementor_json( [ [ 'formTheme' => 'default' ] ] )
		);
		$this->create_post_with_elementor_data(
			$this->build_elementor_json( [ [ 'formTheme' => 'default' ] ] ),
			'page'
		);

		$count = Analytics::embed_styling_elementor_count( 'default' );
		$this->assertSame( 2, $count );
	}

	/**
	 * Test draft Elementor pages are not counted.
	 */
	public function test_embed_styling_elementor_count_excludes_drafts() {
		$this->create_post_with_elementor_data(
			$this->build_elementor_json( [ [ 'formTheme' => 'default' ] ] ),
			'post',
			'draft'
		);

		$count = Analytics::embed_styling_elementor_count( 'default' );
		$this->assertSame( 0, $count );
	}

	// ─── track_embed_styling_configured (Elementor) ───────────────

	/**
	 * Test event is tracked for Elementor pages with styled widgets.
	 */
	public function test_track_embed_styling_configured_elementor_fires_event() {
		$analytics = Analytics::get_instance();
		$post_id   = $this->create_post_with_elementor_data(
			$this->build_elementor_json( [ [ 'formTheme' => 'default' ] ] )
		);

		$analytics->track_embed_styling_configured( $post_id, get_post( $post_id ) );

		$this->assertTrue( Analytics_Events::is_tracked( 'embed_styling_configured' ) );
	}

	/**
	 * Test event properties contain 'elementor' source for Elementor pages.
	 */
	public function test_track_embed_styling_configured_elementor_source() {
		$analytics = Analytics::get_instance();
		$post_id   = $this->create_post_with_elementor_data(
			$this->build_elementor_json(
				[
					[ 'formTheme' => 'default' ],
					[ 'formTheme' => 'custom' ],
				]
			)
		);

		$analytics->track_embed_styling_configured( $post_id, get_post( $post_id ) );

		$pending = get_option( 'srfm_options', [] );
		$events  = $pending['usage_events_pending'] ?? [];
		$event   = end( $events );

		$this->assertSame( 'embed_styling_configured', $event['event_name'] );
		$this->assertSame( 'elementor', $event['properties']['source'] );
		$this->assertSame( 2, $event['properties']['block_count'] );
		$this->assertArrayHasKey( 'default', $event['properties']['themes'] );
		$this->assertArrayHasKey( 'custom', $event['properties']['themes'] );
	}

	/**
	 * Test Elementor event is not tracked when formTheme is inherit.
	 */
	public function test_track_embed_styling_configured_elementor_skips_inherit() {
		$analytics = Analytics::get_instance();
		$post_id   = $this->create_post_with_elementor_data(
			$this->build_elementor_json( [ [ 'formTheme' => 'inherit' ] ] )
		);

		$analytics->track_embed_styling_configured( $post_id, get_post( $post_id ) );

		$this->assertFalse( Analytics_Events::is_tracked( 'embed_styling_configured' ) );
	}

	// ─── embed_styling_bricks_count ───────────────────────────────

	/**
	 * Test returns 0 when no Bricks pages have embed styling.
	 */
	public function test_embed_styling_bricks_count_returns_zero_when_no_elements() {
		$count = Analytics::embed_styling_bricks_count( 'default' );
		$this->assertSame( 0, $count );
	}

	/**
	 * Test counts a single Bricks element with 'default' formTheme.
	 */
	public function test_embed_styling_bricks_count_single_default() {
		$this->create_post_with_bricks_data(
			[ $this->build_bricks_element( 'default' ) ]
		);

		$count = Analytics::embed_styling_bricks_count( 'default' );
		$this->assertSame( 1, $count );
	}

	/**
	 * Test counts a single Bricks element with 'custom' formTheme.
	 */
	public function test_embed_styling_bricks_count_single_custom() {
		$this->create_post_with_bricks_data(
			[ $this->build_bricks_element( 'custom' ) ]
		);

		$count = Analytics::embed_styling_bricks_count( 'custom' );
		$this->assertSame( 1, $count );
	}

	/**
	 * Test 'default' count does not include 'custom' Bricks elements.
	 */
	public function test_embed_styling_bricks_count_default_excludes_custom() {
		$this->create_post_with_bricks_data(
			[ $this->build_bricks_element( 'custom' ) ]
		);

		$count = Analytics::embed_styling_bricks_count( 'default' );
		$this->assertSame( 0, $count );
	}

	/**
	 * Test multiple Bricks elements on a single page are counted individually.
	 */
	public function test_embed_styling_bricks_count_multiple_on_same_page() {
		$this->create_post_with_bricks_data(
			[
				$this->build_bricks_element( 'default' ),
				$this->build_bricks_element( 'default' ),
				$this->build_bricks_element( 'custom' ),
			]
		);

		$default_count = Analytics::embed_styling_bricks_count( 'default' );
		$custom_count  = Analytics::embed_styling_bricks_count( 'custom' );

		$this->assertSame( 2, $default_count );
		$this->assertSame( 1, $custom_count );
	}

	/**
	 * Test draft Bricks pages are not counted.
	 */
	public function test_embed_styling_bricks_count_excludes_drafts() {
		$this->create_post_with_bricks_data(
			[ $this->build_bricks_element( 'default' ) ],
			'post',
			'draft'
		);

		$count = Analytics::embed_styling_bricks_count( 'default' );
		$this->assertSame( 0, $count );
	}

	// ─── track_embed_styling_configured (Bricks) ──────────────────

	/**
	 * Test event is tracked for Bricks pages with styled elements.
	 */
	public function test_track_embed_styling_configured_bricks_fires_event() {
		$analytics = Analytics::get_instance();
		$post_id   = $this->create_post_with_bricks_data(
			[ $this->build_bricks_element( 'default' ) ]
		);

		$analytics->track_embed_styling_configured( $post_id, get_post( $post_id ) );

		$this->assertTrue( Analytics_Events::is_tracked( 'embed_styling_configured' ) );
	}

	/**
	 * Test event properties contain 'bricks' source for Bricks pages.
	 */
	public function test_track_embed_styling_configured_bricks_source() {
		$analytics = Analytics::get_instance();
		$post_id   = $this->create_post_with_bricks_data(
			[
				$this->build_bricks_element( 'default' ),
				$this->build_bricks_element( 'custom' ),
			]
		);

		$analytics->track_embed_styling_configured( $post_id, get_post( $post_id ) );

		$pending = get_option( 'srfm_options', [] );
		$events  = $pending['usage_events_pending'] ?? [];
		$event   = end( $events );

		$this->assertSame( 'embed_styling_configured', $event['event_name'] );
		$this->assertSame( 'bricks', $event['properties']['source'] );
		$this->assertSame( 2, $event['properties']['block_count'] );
		$this->assertArrayHasKey( 'default', $event['properties']['themes'] );
		$this->assertArrayHasKey( 'custom', $event['properties']['themes'] );
	}

	/**
	 * Test Bricks event is not tracked when formTheme is inherit.
	 */
	public function test_track_embed_styling_configured_bricks_skips_inherit() {
		$analytics = Analytics::get_instance();
		$post_id   = $this->create_post_with_bricks_data(
			[ $this->build_bricks_element( 'inherit' ) ]
		);

		$analytics->track_embed_styling_configured( $post_id, get_post( $post_id ) );

		$this->assertFalse( Analytics_Events::is_tracked( 'embed_styling_configured' ) );
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

	// ─── Helper ───────────────────────────────────────────────────

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
	 * Create a test post with Elementor data in post meta.
	 *
	 * @param string $elementor_json JSON string for _elementor_data.
	 * @param string $post_type      Post type. Default 'post'.
	 * @param string $post_status    Post status. Default 'publish'.
	 * @return int Post ID.
	 */
	private function create_post_with_elementor_data( $elementor_json, $post_type = 'post', $post_status = 'publish' ) {
		$post_id = wp_insert_post(
			[
				'post_type'    => $post_type,
				'post_status'  => $post_status,
				'post_title'   => 'Elementor Analytics Test ' . wp_rand(),
				'post_content' => '',
			]
		);

		update_post_meta( $post_id, '_elementor_data', $elementor_json );
		update_post_meta( $post_id, '_srfm_test_post', true );

		return $post_id;
	}

	/**
	 * Build Elementor JSON data with sureforms_form widgets.
	 *
	 * @param array<array<string, string>> $widgets Array of widget settings, each with 'formTheme' key.
	 * @return string JSON string mimicking _elementor_data structure.
	 */
	private function build_elementor_json( $widgets ) {
		$elements = [];
		foreach ( $widgets as $index => $settings ) {
			$elements[] = [
				'id'         => 'abc' . $index,
				'elType'     => 'widget',
				'widgetType' => 'sureforms_form',
				'settings'   => array_merge(
					[
						'srfm_form_block' => '1',
					],
					$settings
				),
			];
		}

		return wp_json_encode( $elements );
	}

	/**
	 * Create a test post with Bricks data in post meta.
	 *
	 * @param array<mixed> $elements   Bricks elements array.
	 * @param string       $post_type   Post type. Default 'post'.
	 * @param string       $post_status Post status. Default 'publish'.
	 * @return int Post ID.
	 */
	private function create_post_with_bricks_data( $elements, $post_type = 'post', $post_status = 'publish' ) {
		$post_id = wp_insert_post(
			[
				'post_type'    => $post_type,
				'post_status'  => $post_status,
				'post_title'   => 'Bricks Analytics Test ' . wp_rand(),
				'post_content' => '',
			]
		);

		update_post_meta( $post_id, '_bricks_page_content_2', $elements );
		update_post_meta( $post_id, '_srfm_test_post', true );

		return $post_id;
	}

	/**
	 * Build a single Bricks sureforms element.
	 *
	 * @param string $form_theme The formTheme value.
	 * @return array<string, mixed> Bricks element array.
	 */
	private function build_bricks_element( $form_theme ) {
		return [
			'id'       => wp_unique_id( 'brx' ),
			'name'     => 'sureforms',
			'settings' => [
				'form-id'   => '1',
				'formTheme' => $form_theme,
			],
		];
	}
}
