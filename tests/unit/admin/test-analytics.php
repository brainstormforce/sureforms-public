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
use SRFM\Inc\Helper;
use SRFM\Inc\Onboarding;

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

		/*
		 * did_action( 'shutdown' ) is process-global, and Analytics deliberately
		 * tracks plugin_activated immediately once it is non-zero. The tests below
		 * fire shutdown by hand, so without this reset the first of them changes
		 * the behaviour under test for every later one - the event gets recorded
		 * at construction, before the test has set up the state it is asserting on.
		 * Leaked callbacks from a previous instance are dropped for the same reason.
		 */
		remove_all_actions( 'shutdown' );
		unset( $GLOBALS['wp_actions']['shutdown'] );
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
		Analytics::events()->flush_pending();
		Analytics::events()->flush_pushed();

		// Reset MCP settings and options.
		delete_option( 'srfm_mcp_settings_options' );
		Helper::update_srfm_option( 'usage_events_pushed', [] );
		Helper::update_srfm_option( 'usage_events_pending', [] );

		// Only the onboarding_completed tests touch these, and this class has no
		// DB rollback, so put back exactly what was there rather than blanketing
		// every test in the class with a write.
		if ( null !== $this->onboarding_options_backup ) {
			foreach ( $this->onboarding_options_backup as $key => $value ) {
				Helper::update_srfm_option( $key, $value );
			}
			$this->onboarding_options_backup = null;
		}

		parent::tearDown();
	}

	// ─── onboarding_completed (state event) ───────────────────────

	/**
	 * Onboarding options as they were before a test overwrote them, or null
	 * when the running test never touched them.
	 *
	 * @var array<string,mixed>|null
	 */
	private $onboarding_options_backup = null;

	/**
	 * Store an onboarding analytics blob, run state detection, and return the
	 * properties the onboarding_completed event was tracked with.
	 *
	 * @param array<string,mixed> $blob The onboarding_analytics option value.
	 * @return array<string,mixed>
	 */
	private function detect_onboarding_completed_props( array $blob ) {
		$this->onboarding_options_backup = [
			'onboarding_completed'       => Helper::get_srfm_option( 'onboarding_completed', 'no' ),
			'onboarding_analytics'       => Helper::get_srfm_option( 'onboarding_analytics', [] ),
			'onboarding_event_v2_flushed' => Helper::get_srfm_option( 'onboarding_event_v2_flushed', false ),
		];

		Onboarding::get_instance()->set_onboarding_status( 'yes' );
		Helper::update_srfm_option( 'onboarding_event_v2_flushed', true );
		Helper::update_srfm_option( 'onboarding_analytics', $blob );

		// Invoke the detector on the existing singleton rather than constructing
		// a second Analytics. The constructor registers four callbacks, and a new
		// object identity means add_action cannot dedupe them -- they would
		// survive this test and fire on every later test's save_post.
		$detect = new \ReflectionMethod( Analytics::class, 'detect_state_events' );
		$detect->setAccessible( true );
		$detect->invoke( Analytics::get_instance() );

		$pending = get_option( 'srfm_options', [] )['usage_events_pending'] ?? [];
		foreach ( $pending as $event ) {
			if ( 'onboarding_completed' === ( $event['event_name'] ?? '' ) ) {
				return $event['properties'] ?? [];
			}
		}

		$this->fail( 'onboarding_completed was not tracked.' );
	}

	/**
	 * The add-ons step reports the tabs the user opened and whether they clicked
	 * Upgrade; the cache step reports whether the warning was acknowledged.
	 */
	public function test_onboarding_completed_maps_viewed_tabs_upgrade_and_cache_ack() {
		$props = $this->detect_onboarding_completed_props(
			[
				'skippedSteps'             => [ 'connect' ],
				'premiumFeatures'          => [
					'viewedTabs'     => [ 'multistep', 'conditional' ],
					'upgradeClicked' => true,
					// Present on purpose. The assertions below claim the old
					// mapping is gone, and without this key in the blob they pass
					// against the old code too.
					'selectedFeatures' => [ 'multistep', 'calculations' ],
				],
				'cacheConflictAcknowledged' => true,
				'completed'                => true,
			]
		);

		$this->assertSame( 'connect', $props['skipped_steps'] );
		$this->assertSame( 'multistep,conditional', $props['viewed_premium_tabs'] );
		$this->assertSame( 'yes', $props['premium_upgrade_clicked'] );
		$this->assertSame( 'yes', $props['cache_conflict_acknowledged'] );
		$this->assertArrayNotHasKey( 'selected_premium_features', $props );
		$this->assertArrayNotHasKey( 'premium_features_count', $props );
	}

	/**
	 * A blob written by an older wizard (no tab / cache keys) must not emit the
	 * new properties, so dashboards can tell "not tracked" from "no".
	 */
	public function test_onboarding_completed_omits_new_props_when_absent() {
		$props = $this->detect_onboarding_completed_props(
			[
				'skippedSteps' => [ 'emailDelivery' ],
				'completed'    => true,
			]
		);

		$this->assertSame( 'emailDelivery', $props['skipped_steps'] );
		$this->assertArrayNotHasKey( 'viewed_premium_tabs', $props );
		$this->assertArrayNotHasKey( 'premium_upgrade_clicked', $props );
		$this->assertArrayNotHasKey( 'cache_conflict_acknowledged', $props );
	}

	/**
	 * A null flag means "this step never rendered" and must emit nothing.
	 *
	 * Distinct from the absent-key case above: that one passes because the key is
	 * missing, this one because the value is null, and they are different paths
	 * through isset(). Both are needed -- a refactor to array_key_exists() would
	 * invert the meaning of both properties and leave the absent-key test green.
	 */
	public function test_onboarding_completed_omits_new_props_when_null() {
		$props = $this->detect_onboarding_completed_props(
			[
				'skippedSteps'              => [ 'connect' ],
				'premiumFeatures'           => [ 'upgradeClicked' => null ],
				'cacheConflictAcknowledged' => null,
				'completed'                 => true,
			]
		);

		$this->assertArrayNotHasKey( 'premium_upgrade_clicked', $props );
		$this->assertArrayNotHasKey( 'cache_conflict_acknowledged', $props );
	}

	/**
	 * false is a real answer and must be reported, not swallowed with null.
	 */
	public function test_onboarding_completed_reports_false_flags_as_no() {
		$props = $this->detect_onboarding_completed_props(
			[
				'premiumFeatures'           => [ 'upgradeClicked' => false ],
				'cacheConflictAcknowledged' => false,
				'completed'                 => true,
			]
		);

		$this->assertSame( 'no', $props['premium_upgrade_clicked'] );
		$this->assertSame( 'no', $props['cache_conflict_acknowledged'] );
	}

	/**
	 * viewedTabs is whatever the wizard POSTed, so only known slugs get through.
	 *
	 * The tabs are a closed set of four. Intersecting against it drops arrays,
	 * nulls and booleans that would otherwise stringify into the property, and
	 * caps its length at the same time.
	 */
	public function test_onboarding_completed_keeps_only_known_premium_tabs() {
		$props = $this->detect_onboarding_completed_props(
			[
				'premiumFeatures' => [
					'viewedTabs' => [
						'multistep',
						[ 'x' ],
						null,
						true,
						'not-a-tab',
						'conversational',
					],
				],
				'completed'       => true,
			]
		);

		$this->assertSame(
			'multistep,conversational',
			$props['viewed_premium_tabs']
		);
	}

	/**
	 * Which wizard produced the blob. Every onboarding_completed event must carry
	 * it, so old and new onboarding can be split with one filter instead of
	 * inferred from which properties happen to be present -- a v2 run on a Pro
	 * install with no caching plugin emits none of the new properties and would
	 * otherwise be indistinguishable from a v1 run.
	 *
	 * Three tests rather than three calls in one: track() dedupes per event name
	 * and only setUp() resets that, so a second detect in the same test would
	 * hand back the first call's properties.
	 */
	public function test_onboarding_completed_reports_onboarding_v2_yes() {
		$props = $this->detect_onboarding_completed_props(
			[
				'onboardingV2' => true,
				'completed'    => true,
			]
		);

		$this->assertSame( 'yes', $props['onboarding_v2'] );
	}

	/**
	 * A blob from the old wizard has no onboardingV2 key. Deliberately not the
	 * isset() shape the other flags use: absence IS the legacy case, so it has to
	 * produce a value rather than nothing.
	 */
	public function test_onboarding_completed_reports_onboarding_v2_no_for_a_legacy_blob() {
		$props = $this->detect_onboarding_completed_props(
			[
				'premiumFeatures' => [ 'selectedFeatures' => [ 'multistep' ] ],
				'completed'       => true,
			]
		);

		$this->assertSame( 'no', $props['onboarding_v2'] );
	}

	/**
	 * No blob at all: onboarding was completed before analytics were captured.
	 * The other properties are all inside the non-empty guard; this one must not be.
	 */
	public function test_onboarding_completed_reports_onboarding_v2_no_without_a_blob() {
		$props = $this->detect_onboarding_completed_props( [] );

		$this->assertSame( 'no', $props['onboarding_v2'] );
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

		$this->assertTrue( Analytics::events()->is_tracked( 'embed_styling_configured' ) );
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

		$this->assertFalse( Analytics::events()->is_tracked( 'embed_styling_configured' ) );
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

		$this->assertFalse( Analytics::events()->is_tracked( 'embed_styling_configured' ) );
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
		$this->assertTrue( Analytics::events()->is_tracked( 'embed_styling_configured' ) );

		// Simulate analytics flush (event sent).
		Analytics::events()->flush_pending();

		// Second save should re-track because method calls flush_pushed.
		$analytics->track_embed_styling_configured( $post_id, $post );
		$this->assertTrue( Analytics::events()->is_tracked( 'embed_styling_configured' ) );
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

		$this->assertTrue( Analytics::events()->is_tracked( 'embed_styling_configured' ) );
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

		$this->assertFalse( Analytics::events()->is_tracked( 'embed_styling_configured' ) );
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

		$this->assertTrue( Analytics::events()->is_tracked( 'embed_styling_configured' ) );
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

		$this->assertFalse( Analytics::events()->is_tracked( 'embed_styling_configured' ) );
	}

	// ─── flush_pushed ─────────────────────────────────────────────

	/**
	 * Test flush_pushed removes specific event names.
	 */
	public function test_flush_pushed_removes_specific_events() {
		Analytics::events()->track( 'event_a' );
		Analytics::events()->track( 'event_b' );
		Analytics::events()->flush_pending();

		// Both should be in pushed list.
		$this->assertTrue( Analytics::events()->is_tracked( 'event_a' ) );
		$this->assertTrue( Analytics::events()->is_tracked( 'event_b' ) );

		// Flush only event_a.
		Analytics::events()->flush_pushed( [ 'event_a' ] );

		$this->assertFalse( Analytics::events()->is_tracked( 'event_a' ) );
		$this->assertTrue( Analytics::events()->is_tracked( 'event_b' ) );
	}

	/**
	 * Test flush_pushed with empty array clears all.
	 */
	public function test_flush_pushed_clears_all_when_empty() {
		Analytics::events()->track( 'event_a' );
		Analytics::events()->track( 'event_b' );
		Analytics::events()->flush_pending();

		Analytics::events()->flush_pushed();

		$this->assertFalse( Analytics::events()->is_tracked( 'event_a' ) );
		$this->assertFalse( Analytics::events()->is_tracked( 'event_b' ) );
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

		$this->assertTrue( Analytics::events()->is_tracked( 'abilities_api_enabled' ) );
		$this->assertTrue( Analytics::events()->is_tracked( 'mcp_server_enabled' ) );
	}

	/**
	 * Test detect_state_events does not track MCP events when toggles are disabled.
	 *
	 * @return void
	 */
	public function test_detect_state_events_skips_mcp_events_when_disabled() {
		// No MCP settings — all disabled.
		new Analytics();

		$this->assertFalse( Analytics::events()->is_tracked( 'abilities_api_enabled' ) );
		$this->assertFalse( Analytics::events()->is_tracked( 'mcp_server_enabled' ) );
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

		$this->assertTrue( Analytics::events()->is_tracked( 'abilities_api_enabled' ) );
		$this->assertFalse( Analytics::events()->is_tracked( 'mcp_server_enabled' ) );
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

	// ─── plugin_activated referer race (shutdown deferral) ────────

	/**
	 * Test track_plugin_activated_event is deferred to 'shutdown' rather
	 * than firing immediately when Analytics boots.
	 *
	 * @return void
	 */
	public function test_track_plugin_activated_event_defers_to_shutdown() {
		delete_option( 'bsf_product_referers' );

		new Analytics();

		$this->assertFalse( Analytics::events()->is_tracked( 'plugin_activated' ), 'plugin_activated should not be tracked until shutdown fires.' );

		do_action( 'shutdown' );

		$this->assertTrue( Analytics::events()->is_tracked( 'plugin_activated' ), 'plugin_activated should be tracked once shutdown fires.' );
	}

	/**
	 * Regression test: a referrer plugin/theme that writes bsf_product_referers
	 * AFTER SureForms has already booted (but before the request ends) must
	 * still be picked up as the source, because the read is deferred to
	 * 'shutdown' rather than happening synchronously at boot.
	 *
	 * @return void
	 */
	public function test_track_plugin_activated_event_reads_referer_set_after_boot() {
		delete_option( 'bsf_product_referers' );

		// SureForms boots — e.g. mid-request, while another plugin is
		// silently activating it via activate_plugin().
		new Analytics();

		// The referring plugin writes its stamp only now, after SureForms
		// has already booted, but still within the same request.
		update_option( 'bsf_product_referers', [ 'sureforms' => 'astra' ] );

		// End of request.
		do_action( 'shutdown' );

		$pending = Helper::get_srfm_option( 'usage_events_pending', [] );
		$event   = current(
			array_filter(
				$pending,
				static function ( $e ) {
					return 'plugin_activated' === $e['event_name'];
				}
			)
		);

		$this->assertNotFalse( $event, 'plugin_activated event should have been queued.' );
		$this->assertSame( 'astra', $event['properties']['source'] );
	}

	/**
	 * Test track_plugin_activated_event falls back to 'self' when no
	 * referrer was ever recorded.
	 *
	 * @return void
	 */
	public function test_track_plugin_activated_event_defaults_to_self_when_no_referer() {
		delete_option( 'bsf_product_referers' );

		new Analytics();
		do_action( 'shutdown' );

		$pending = Helper::get_srfm_option( 'usage_events_pending', [] );
		$event   = current(
			array_filter(
				$pending,
				static function ( $e ) {
					return 'plugin_activated' === $e['event_name'];
				}
			)
		);

		$this->assertNotFalse( $event, 'plugin_activated event should have been queued.' );
		$this->assertSame( 'self', $event['properties']['source'] );
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

		$this->assertTrue( Analytics::events()->is_tracked( 'first_form_published' ) );
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

		$this->assertFalse( Analytics::events()->is_tracked( 'first_form_published' ) );
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

		$this->assertFalse( Analytics::events()->is_tracked( 'first_form_published' ) );
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

		$this->assertFalse( Analytics::events()->is_tracked( 'first_form_published' ) );
	}

	/**
	 * Test events() returns a BSF_Analytics_Events instance.
	 *
	 * @return void
	 */
	public function test_events() {
		$events = Analytics::events();
		$this->assertInstanceOf( \BSF_Analytics_Events::class, $events );
	}

	/**
	 * Test add_srfm_analytics_data adds plugin data to stats.
	 *
	 * @return void
	 */
	public function test_add_srfm_analytics_data() {
		$analytics  = new Analytics();
		$stats_data = $analytics->add_srfm_analytics_data( [] );

		$this->assertArrayHasKey( 'plugin_data', $stats_data );
		$this->assertArrayHasKey( 'sureforms', $stats_data['plugin_data'] );
		$this->assertArrayHasKey( 'free_version', $stats_data['plugin_data']['sureforms'] );
	}

	/**
	 * Test track_first_editor_open tracks event on correct screen.
	 *
	 * @return void
	 */
	public function test_track_first_editor_open() {
		// Simulate the sureforms_form screen.
		$screen     = \WP_Screen::get( 'sureforms_form' );
		$screen->id = 'sureforms_form';
		set_current_screen( $screen );

		$analytics = new Analytics();
		$analytics->track_first_editor_open();

		$this->assertTrue( Analytics::events()->is_tracked( 'first_form_editor_opened' ) );
	}

	// ─── add_srfm_analytics_data ─────────────────────────────────

	/**
	 * Test add_srfm_analytics_data returns expected structure.
	 */
	public function test_add_srfm_analytics_data_returns_sureforms_key() {
		$analytics = Analytics::get_instance();
		$result    = $analytics->add_srfm_analytics_data( [ 'plugin_data' => [] ] );

		$this->assertArrayHasKey( 'sureforms', $result['plugin_data'] );
		$this->assertArrayHasKey( 'free_version', $result['plugin_data']['sureforms'] );
		$this->assertArrayHasKey( 'numeric_values', $result['plugin_data']['sureforms'] );
		$this->assertArrayHasKey( 'total_forms', $result['plugin_data']['sureforms']['numeric_values'] );
		$this->assertArrayHasKey( 'forms_using_custom_css', $result['plugin_data']['sureforms']['numeric_values'] );
		$this->assertArrayHasKey( 'embed_styling_gb_default', $result['plugin_data']['sureforms']['numeric_values'] );
	}

	/**
	 * Test add_srfm_analytics_data preserves existing stats data.
	 */
	public function test_add_srfm_analytics_data_preserves_existing_data() {
		$analytics  = Analytics::get_instance();
		$stats_data = [
			'plugin_data' => [
				'other_plugin' => [ 'version' => '1.0' ],
			],
		];
		$result     = $analytics->add_srfm_analytics_data( $stats_data );

		$this->assertArrayHasKey( 'other_plugin', $result['plugin_data'] );
		$this->assertArrayHasKey( 'sureforms', $result['plugin_data'] );
	}

	// ─── forms_using_custom_css ──────────────────────────────────

	/**
	 * Test forms_using_custom_css returns 0 when no forms have custom CSS.
	 */
	public function test_forms_using_custom_css_returns_zero_with_no_custom_css() {
		$analytics = Analytics::get_instance();
		$this->assertSame( 0, $analytics->forms_using_custom_css() );
	}

	/**
	 * Test forms_using_custom_css counts forms with custom CSS meta.
	 */
	public function test_forms_using_custom_css_counts_forms() {
		// Create a published form with custom CSS.
		$form_id = wp_insert_post(
			[
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'CSS Test Form ' . wp_rand(),
			]
		);
		update_post_meta( $form_id, '_srfm_test_post', true );
		update_post_meta( $form_id, '_srfm_form_custom_css', '.my-form { color: red; }' );

		$analytics = Analytics::get_instance();
		$this->assertSame( 1, $analytics->forms_using_custom_css() );
	}

	/**
	 * Test forms_using_custom_css excludes forms with empty CSS.
	 */
	public function test_forms_using_custom_css_excludes_empty_css() {
		$form_id = wp_insert_post(
			[
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Empty CSS Form ' . wp_rand(),
			]
		);
		update_post_meta( $form_id, '_srfm_test_post', true );
		update_post_meta( $form_id, '_srfm_form_custom_css', '' );

		$analytics = Analytics::get_instance();
		$this->assertSame( 0, $analytics->forms_using_custom_css() );
	}

	// ─── Form views & conversion analytics ───────────────────────

	/**
	 * `total_form_views` sums the view meta across published forms only.
	 *
	 * Trashed and draft forms are excluded deliberately: their views would inflate
	 * the numerator while `total_entries` and `forms_with_views` no longer count
	 * them, which is how a warehouse ends up with conversion rates above 100%.
	 */
	public function test_total_form_views() {
		$analytics = Analytics::get_instance();

		$this->assertSame( 0, $analytics->total_form_views(), 'No view meta should sum to zero, not null.' );

		$form_a = $this->create_form_with_views( 7 );
		$form_b = $this->create_form_with_views( 5 );
		$draft  = $this->create_form_with_views( 100, 'draft' );

		$this->assertSame( 12, $analytics->total_form_views(), 'Published forms should be summed; the draft must not count.' );

		// Read straight through — no cache to go stale between analytics passes.
		update_post_meta( $form_a, \SRFM\Inc\Form_Views::META_KEY, 1000 );
		$this->assertSame( 1005, $analytics->total_form_views(), 'A later change should be visible immediately.' );

		wp_delete_post( $form_a, true );
		wp_delete_post( $form_b, true );
		wp_delete_post( $draft, true );
	}

	/**
	 * `forms_with_views` counts forms viewed at least once, not forms that exist.
	 *
	 * A form sitting at zero views must not count, otherwise the metric cannot
	 * distinguish "the beacon is firing" from "forms exist but are never embedded",
	 * which is the whole reason it sits alongside the raw total.
	 */
	public function test_forms_with_views() {
		$analytics = Analytics::get_instance();

		$this->assertSame( 0, $analytics->forms_with_views() );

		$viewed = $this->create_form_with_views( 3 );
		$zero   = $this->create_form_with_views( 0 );
		$draft  = $this->create_form_with_views( 9, 'draft' );

		$this->assertSame( 1, $analytics->forms_with_views(), 'Only the published form with a non-zero count should be included.' );

		wp_delete_post( $viewed, true );
		wp_delete_post( $zero, true );
		wp_delete_post( $draft, true );
	}

	/**
	 * The columns toggle reported to analytics must match what the Forms list does.
	 *
	 * The value is delegated to Form_Views rather than read from the option array,
	 * so the two can never drift — which matters precisely because the default has
	 * changed once already. This asserts the delegation, not a hardcoded default:
	 * the absent-key case is compared against `is_tracking_enabled()` itself.
	 */
	public function test_global_settings_data() {
		$analytics = Analytics::get_instance();
		$original  = get_option( 'srfm_general_settings_options' );

		// Never saved → opt-in, so reported as disabled, matching is_tracking_enabled().
		delete_option( 'srfm_general_settings_options' );
		$data = $analytics->global_settings_data();
		$this->assertFalse( $data['boolean_values']['form_views_columns_enabled'], 'An absent setting must report as disabled.' );
		$this->assertSame(
			\SRFM\Inc\Form_Views::get_instance()->is_tracking_enabled(),
			$data['boolean_values']['form_views_columns_enabled'],
			'Analytics must not diverge from the Forms list.'
		);

		// Explicitly off.
		update_option( 'srfm_general_settings_options', [ 'srfm_form_views_tracking' => false ] );
		$data = $analytics->global_settings_data();
		$this->assertFalse( $data['boolean_values']['form_views_columns_enabled'] );

		// Explicitly on.
		update_option( 'srfm_general_settings_options', [ 'srfm_form_views_tracking' => true ] );
		$data = $analytics->global_settings_data();
		$this->assertTrue( $data['boolean_values']['form_views_columns_enabled'] );

		// Enabling above fires maybe_start_tracking(), which opens the tracking
		// window. Left behind, that leaks into any sibling test that depends on the
		// never-enabled state and makes the failure order-dependent.
		delete_option( \SRFM\Inc\Form_Views::TRACKING_STARTED_OPTION );

		if ( false === $original ) {
			delete_option( 'srfm_general_settings_options' );
		} else {
			update_option( 'srfm_general_settings_options', $original );
		}
	}

	// ─── Helpers ─────────────────────────────────────────────────

	/**
	 * Create a published form carrying the given view count.
	 *
	 * @param int    $views       View count to store.
	 * @param string $post_status Post status. Default 'publish'.
	 * @return int Post ID.
	 */
	private function create_form_with_views( $views, $post_status = 'publish' ) {
		$form_id = wp_insert_post(
			[
				'post_title'  => 'Views Analytics Form',
				'post_type'   => SRFM_FORMS_POST_TYPE,
				'post_status' => $post_status,
			]
		);

		update_post_meta( $form_id, \SRFM\Inc\Form_Views::META_KEY, $views );

		return (int) $form_id;
	}

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

	// ---------------------------------------------------------------
	// Missing entries table
	// ---------------------------------------------------------------

	/**
	 * The daily payload must carry the missing-table state. The impression event
	 * fires once per site, so without this KPI a site that stays broken for months
	 * looks identical to one that fixed itself the same day.
	 */
	public function test_analytics_payload_reports_the_entries_table_state() {
		$data = Analytics::get_instance()->add_srfm_analytics_data( [] );

		$this->assertArrayHasKey(
			'db_entries_table_missing',
			$data['plugin_data']['sureforms']['boolean_values']
		);
		$this->assertFalse( $data['plugin_data']['sureforms']['boolean_values']['db_entries_table_missing'] );
	}

	/**
	 * Every query against the entries table errors while it is missing. Before this
	 * guard the daily send tripped over get_total_entries_by_status() on exactly the
	 * sites whose breakage we most need reported.
	 */
	public function test_analytics_does_not_query_a_missing_entries_table() {
		global $wpdb;

		$table = \SRFM\Inc\Database\Tables\Entries::get_instance()->get_tablename();

		$versions            = (array) get_option( 'srfm_database_table_versions', [] );
		$versions['entries'] = 2;
		update_option( 'srfm_database_table_versions', $versions );

		$wpdb->query( "CREATE TABLE `{$table}_srfmbak` LIKE `{$table}`" ); // phpcs:ignore -- Preserving the schema across the drop under test.
		$wpdb->query( "DROP TABLE `{$table}`" ); // phpcs:ignore -- Reproducing the dropped-table state under test.
		$this->reset_table_cache();

		// Record every query the payload runs. Asserting on $wpdb->last_error would
		// pass by accident, because a later successful query clears it.
		$seen = [];
		$spy  = static function ( $query ) use ( &$seen, $table ) {
			if ( false !== strpos( (string) $query, $table ) ) {
				$seen[] = $query;
			}
			return $query;
		};

		add_filter( 'query', $spy );
		$data = Analytics::get_instance()->add_srfm_analytics_data( [] );
		remove_filter( 'query', $spy );

		$wpdb->query( "RENAME TABLE `{$table}_srfmbak` TO `{$table}`" ); // phpcs:ignore -- Restoring the table this test dropped.
		$this->reset_table_cache();

		$this->assertSame( [], $seen, 'The analytics payload must not query a missing entries table.' );
		$this->assertSame( 0, $data['plugin_data']['sureforms']['numeric_values']['total_entries'] );
		$this->assertTrue( $data['plugin_data']['sureforms']['boolean_values']['db_entries_table_missing'] );
	}

	/**
	 * Clear the per-request memo and the transient behind is_entries_table_missing().
	 *
	 * @return void
	 */
	private function reset_table_cache() {
		delete_transient( \SRFM\Inc\Database\Register::ENTRIES_TABLE_CHECK_TRANSIENT );

		$memo = new ReflectionProperty( \SRFM\Inc\Database\Register::class, 'entries_table_present' );
		$memo->setAccessible( true );
		$memo->setValue( null, null );
	}
}
