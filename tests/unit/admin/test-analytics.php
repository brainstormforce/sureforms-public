<?php
/**
 * Class Test_Analytics
 *
 * Tests for MCP analytics boolean_values and state-based events.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Admin\Analytics;
use SRFM\Inc\Analytics_Events;
use SRFM\Inc\Helper;

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
		delete_option( 'srfm_mcp_settings_options' );
		Helper::update_srfm_option( 'usage_events_pushed', [] );
		Helper::update_srfm_option( 'usage_events_pending', [] );
		parent::tearDown();
	}

	/**
	 * Test global_settings_data returns MCP boolean_values as false when no settings exist.
	 *
	 * @return void
	 */
	public function test_global_settings_data_mcp_defaults_to_false() {
		$analytics = new Analytics();
		$reflection = new ReflectionClass( Analytics::class );
		$method = $reflection->getMethod( 'global_settings_data' );
		$method->setAccessible( true );

		$data = $method->invoke( $analytics );

		$this->assertArrayHasKey( 'boolean_values', $data );
		$this->assertFalse( $data['boolean_values']['abilities_api_enabled'] );
		$this->assertFalse( $data['boolean_values']['abilities_api_edit_enabled'] );
		$this->assertFalse( $data['boolean_values']['abilities_api_delete_enabled'] );
		$this->assertFalse( $data['boolean_values']['mcp_server_enabled'] );
	}

	/**
	 * Test global_settings_data returns MCP boolean_values as true when all toggles are enabled.
	 *
	 * @return void
	 */
	public function test_global_settings_data_mcp_all_enabled() {
		update_option(
			'srfm_mcp_settings_options',
			[
				'srfm_abilities_api'        => true,
				'srfm_abilities_api_edit'   => true,
				'srfm_abilities_api_delete' => true,
				'srfm_mcp_server'           => true,
			]
		);

		$analytics = new Analytics();
		$reflection = new ReflectionClass( Analytics::class );
		$method = $reflection->getMethod( 'global_settings_data' );
		$method->setAccessible( true );

		$data = $method->invoke( $analytics );

		$this->assertTrue( $data['boolean_values']['abilities_api_enabled'] );
		$this->assertTrue( $data['boolean_values']['abilities_api_edit_enabled'] );
		$this->assertTrue( $data['boolean_values']['abilities_api_delete_enabled'] );
		$this->assertTrue( $data['boolean_values']['mcp_server_enabled'] );
	}

	/**
	 * Test global_settings_data returns mixed MCP boolean_values.
	 *
	 * @return void
	 */
	public function test_global_settings_data_mcp_partial_enabled() {
		update_option(
			'srfm_mcp_settings_options',
			[
				'srfm_abilities_api'        => true,
				'srfm_abilities_api_edit'   => false,
				'srfm_abilities_api_delete' => true,
				'srfm_mcp_server'           => false,
			]
		);

		$analytics = new Analytics();
		$reflection = new ReflectionClass( Analytics::class );
		$method = $reflection->getMethod( 'global_settings_data' );
		$method->setAccessible( true );

		$data = $method->invoke( $analytics );

		$this->assertTrue( $data['boolean_values']['abilities_api_enabled'] );
		$this->assertFalse( $data['boolean_values']['abilities_api_edit_enabled'] );
		$this->assertTrue( $data['boolean_values']['abilities_api_delete_enabled'] );
		$this->assertFalse( $data['boolean_values']['mcp_server_enabled'] );
	}

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
}
