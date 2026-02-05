<?php
/**
 * Class Test_Notice_Manager
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Admin\Notice_Manager;

/**
 * Tests Notice Manager class functionality.
 *
 * @since 2.5.0
 */
class Test_Notice_Manager extends TestCase {

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Clear notices before each test to ensure clean state.
		Notice_Manager::clear_notices();
	}

	/**
	 * Tear down test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Clear notices after each test.
		Notice_Manager::clear_notices();

		parent::tearDown();
	}

	/**
	 * Test register_notice with valid arguments.
	 */
	public function test_register_notice_with_valid_args() {
		$notice_args = [
			'id'      => 'test-notice',
			'variant' => 'warning',
			'message' => 'This is a test notice.',
		];

		Notice_Manager::register_notice( $notice_args );

		$notices = Notice_Manager::get_notices();

		$this->assertCount( 1, $notices, 'Should have one notice registered' );
		$this->assertEquals( 'test-notice', $notices[0]['id'], 'Notice ID should match' );
		$this->assertEquals( 'warning', $notices[0]['variant'], 'Variant should match' );
		$this->assertEquals( 'This is a test notice.', $notices[0]['message'], 'Message should match' );
	}

	/**
	 * Test register_notice applies default values.
	 */
	public function test_register_notice_applies_defaults() {
		$notice_args = [
			'id'      => 'test-notice',
			'message' => 'Test message',
		];

		Notice_Manager::register_notice( $notice_args );

		$notices = Notice_Manager::get_notices();

		$this->assertCount( 1, $notices, 'Should have one notice registered' );
		$this->assertEquals( 'info', $notices[0]['variant'], 'Default variant should be info' );
		$this->assertEquals( '', $notices[0]['title'], 'Default title should be empty string' );
		$this->assertEquals( [], $notices[0]['actions'], 'Default actions should be empty array' );
		$this->assertTrue( $notices[0]['dismissible'], 'Default dismissible should be true' );
		$this->assertEquals( [ 'all' ], $notices[0]['pages'], 'Default pages should be ["all"]' );
	}

	/**
	 * Test register_notice fails without required ID.
	 */
	public function test_register_notice_fails_without_id() {
		$notice_args = [
			'message' => 'Test message',
		];

		Notice_Manager::register_notice( $notice_args );

		$notices = Notice_Manager::get_notices();

		$this->assertCount( 0, $notices, 'Should not register notice without ID' );
	}

	/**
	 * Test register_notice fails without required message.
	 */
	public function test_register_notice_fails_without_message() {
		$notice_args = [
			'id' => 'test-notice',
		];

		Notice_Manager::register_notice( $notice_args );

		$notices = Notice_Manager::get_notices();

		$this->assertCount( 0, $notices, 'Should not register notice without message' );
	}

	/**
	 * Test register_notice fails with empty ID.
	 */
	public function test_register_notice_fails_with_empty_id() {
		$notice_args = [
			'id'      => '',
			'message' => 'Test message',
		];

		Notice_Manager::register_notice( $notice_args );

		$notices = Notice_Manager::get_notices();

		$this->assertCount( 0, $notices, 'Should not register notice with empty ID' );
	}

	/**
	 * Test register_notice fails with empty message.
	 */
	public function test_register_notice_fails_with_empty_message() {
		$notice_args = [
			'id'      => 'test-notice',
			'message' => '',
		];

		Notice_Manager::register_notice( $notice_args );

		$notices = Notice_Manager::get_notices();

		$this->assertCount( 0, $notices, 'Should not register notice with empty message' );
	}

	/**
	 * Test register_notice converts string pages to array.
	 */
	public function test_register_notice_converts_pages_string_to_array() {
		$notice_args = [
			'id'      => 'test-notice',
			'message' => 'Test message',
			'pages'   => 'sureforms_entries',
		];

		Notice_Manager::register_notice( $notice_args );

		$notices = Notice_Manager::get_notices();

		$this->assertIsArray( $notices[0]['pages'], 'Pages should be converted to array' );
		$this->assertEquals( [ 'sureforms_entries' ], $notices[0]['pages'], 'Pages should contain the string value' );
	}

	/**
	 * Test register_notice overwrites existing notice with same ID.
	 */
	public function test_register_notice_overwrites_existing() {
		// Register first notice.
		Notice_Manager::register_notice( [
			'id'      => 'test-notice',
			'message' => 'First message',
			'variant' => 'info',
		] );

		// Register second notice with same ID.
		Notice_Manager::register_notice( [
			'id'      => 'test-notice',
			'message' => 'Second message',
			'variant' => 'error',
		] );

		$notices = Notice_Manager::get_notices();

		$this->assertCount( 1, $notices, 'Should only have one notice' );
		$this->assertEquals( 'Second message', $notices[0]['message'], 'Message should be overwritten' );
		$this->assertEquals( 'error', $notices[0]['variant'], 'Variant should be overwritten' );
	}

	/**
	 * Test register_notice with all fields populated.
	 */
	public function test_register_notice_with_all_fields() {
		$notice_args = [
			'id'          => 'full-notice',
			'variant'     => 'success',
			'message'     => 'Success message',
			'title'       => 'Success Title',
			'actions'     => [
				[
					'label' => 'Click Me',
					'url'   => 'https://example.com',
				],
			],
			'dismissible' => false,
			'pages'       => [ 'sureforms_forms', 'sureforms_entries' ],
		];

		Notice_Manager::register_notice( $notice_args );

		$notices = Notice_Manager::get_notices();

		$this->assertCount( 1, $notices, 'Should have one notice' );
		$this->assertEquals( 'full-notice', $notices[0]['id'] );
		$this->assertEquals( 'success', $notices[0]['variant'] );
		$this->assertEquals( 'Success message', $notices[0]['message'] );
		$this->assertEquals( 'Success Title', $notices[0]['title'] );
		$this->assertCount( 1, $notices[0]['actions'] );
		$this->assertEquals( 'Click Me', $notices[0]['actions'][0]['label'] );
		$this->assertFalse( $notices[0]['dismissible'] );
		$this->assertEquals( [ 'sureforms_forms', 'sureforms_entries' ], $notices[0]['pages'] );
	}

	/**
	 * Test get_notices returns empty array when no notices.
	 */
	public function test_get_notices_returns_empty_array() {
		$notices = Notice_Manager::get_notices();

		$this->assertIsArray( $notices, 'Should return array' );
		$this->assertEmpty( $notices, 'Should return empty array when no notices' );
	}

	/**
	 * Test get_notices returns indexed array.
	 */
	public function test_get_notices_returns_indexed_array() {
		Notice_Manager::register_notice( [
			'id'      => 'notice-1',
			'message' => 'Message 1',
		] );

		Notice_Manager::register_notice( [
			'id'      => 'notice-2',
			'message' => 'Message 2',
		] );

		$notices = Notice_Manager::get_notices();

		$this->assertCount( 2, $notices, 'Should have two notices' );
		// Check that array is indexed (not associative by ID).
		$this->assertArrayHasKey( 0, $notices );
		$this->assertArrayHasKey( 1, $notices );
	}

	/**
	 * Test remove_notice removes existing notice.
	 */
	public function test_remove_notice_removes_existing() {
		Notice_Manager::register_notice( [
			'id'      => 'notice-to-remove',
			'message' => 'Test message',
		] );

		$this->assertCount( 1, Notice_Manager::get_notices(), 'Should have one notice before removal' );

		Notice_Manager::remove_notice( 'notice-to-remove' );

		$this->assertCount( 0, Notice_Manager::get_notices(), 'Should have no notices after removal' );
	}

	/**
	 * Test remove_notice handles non-existent notice gracefully.
	 */
	public function test_remove_notice_handles_nonexistent() {
		Notice_Manager::register_notice( [
			'id'      => 'existing-notice',
			'message' => 'Test message',
		] );

		// This should not throw an error.
		Notice_Manager::remove_notice( 'nonexistent-notice' );

		$notices = Notice_Manager::get_notices();
		$this->assertCount( 1, $notices, 'Existing notice should remain' );
		$this->assertEquals( 'existing-notice', $notices[0]['id'] );
	}

	/**
	 * Test remove_notice only removes specified notice.
	 */
	public function test_remove_notice_only_removes_specified() {
		Notice_Manager::register_notice( [
			'id'      => 'notice-1',
			'message' => 'Message 1',
		] );

		Notice_Manager::register_notice( [
			'id'      => 'notice-2',
			'message' => 'Message 2',
		] );

		Notice_Manager::register_notice( [
			'id'      => 'notice-3',
			'message' => 'Message 3',
		] );

		Notice_Manager::remove_notice( 'notice-2' );

		$notices = Notice_Manager::get_notices();
		$notice_ids = array_column( $notices, 'id' );

		$this->assertCount( 2, $notices, 'Should have two notices remaining' );
		$this->assertContains( 'notice-1', $notice_ids, 'Notice 1 should remain' );
		$this->assertContains( 'notice-3', $notice_ids, 'Notice 3 should remain' );
		$this->assertNotContains( 'notice-2', $notice_ids, 'Notice 2 should be removed' );
	}

	/**
	 * Test clear_notices removes all notices.
	 */
	public function test_clear_notices_removes_all() {
		Notice_Manager::register_notice( [
			'id'      => 'notice-1',
			'message' => 'Message 1',
		] );

		Notice_Manager::register_notice( [
			'id'      => 'notice-2',
			'message' => 'Message 2',
		] );

		$this->assertCount( 2, Notice_Manager::get_notices(), 'Should have two notices before clearing' );

		Notice_Manager::clear_notices();

		$this->assertCount( 0, Notice_Manager::get_notices(), 'Should have no notices after clearing' );
	}

	/**
	 * Test clear_notices works when no notices exist.
	 */
	public function test_clear_notices_handles_empty() {
		// This should not throw an error.
		Notice_Manager::clear_notices();

		$this->assertCount( 0, Notice_Manager::get_notices(), 'Should have no notices' );
	}

	/**
	 * Test add_notices_to_localized_data adds notices to data.
	 */
	public function test_add_notices_to_localized_data() {
		Notice_Manager::register_notice( [
			'id'      => 'test-notice',
			'message' => 'Test message',
		] );

		$notice_manager = Notice_Manager::get_instance();

		$localization_data = [
			'existing_key' => 'existing_value',
		];

		$result = $notice_manager->add_notices_to_localized_data( $localization_data );

		$this->assertArrayHasKey( 'notices', $result, 'Result should have notices key' );
		$this->assertArrayHasKey( 'existing_key', $result, 'Result should preserve existing data' );
		$this->assertEquals( 'existing_value', $result['existing_key'], 'Existing data should be unchanged' );
		$this->assertCount( 1, $result['notices'], 'Should have one notice' );
	}

	/**
	 * Test add_notices_to_localized_data with empty data.
	 */
	public function test_add_notices_to_localized_data_empty_input() {
		Notice_Manager::register_notice( [
			'id'      => 'test-notice',
			'message' => 'Test message',
		] );

		$notice_manager = Notice_Manager::get_instance();

		$result = $notice_manager->add_notices_to_localized_data( [] );

		$this->assertArrayHasKey( 'notices', $result, 'Result should have notices key' );
		$this->assertCount( 1, $result['notices'], 'Should have one notice' );
	}

	/**
	 * Test register_from_php_notice helper method.
	 */
	public function test_register_from_php_notice() {
		Notice_Manager::register_from_php_notice(
			'php-notice',
			'error',
			'Error message from PHP',
			[
				[
					'label' => 'Fix Now',
					'url'   => 'https://example.com/fix',
				],
			],
			[ 'sureforms_menu' ]
		);

		$notices = Notice_Manager::get_notices();

		$this->assertCount( 1, $notices, 'Should have one notice' );
		$this->assertEquals( 'php-notice', $notices[0]['id'] );
		$this->assertEquals( 'error', $notices[0]['variant'] );
		$this->assertEquals( 'Error message from PHP', $notices[0]['message'] );
		$this->assertCount( 1, $notices[0]['actions'] );
		$this->assertEquals( [ 'sureforms_menu' ], $notices[0]['pages'] );
	}

	/**
	 * Test register_from_php_notice with default values.
	 */
	public function test_register_from_php_notice_defaults() {
		Notice_Manager::register_from_php_notice(
			'simple-notice',
			'warning',
			'Simple warning message'
		);

		$notices = Notice_Manager::get_notices();

		$this->assertCount( 1, $notices, 'Should have one notice' );
		$this->assertEquals( [], $notices[0]['actions'], 'Default actions should be empty' );
		$this->assertEquals( [ 'all' ], $notices[0]['pages'], 'Default pages should be ["all"]' );
	}

	/**
	 * Test multiple notices registration order.
	 */
	public function test_multiple_notices_order() {
		Notice_Manager::register_notice( [
			'id'      => 'first',
			'message' => 'First notice',
		] );

		Notice_Manager::register_notice( [
			'id'      => 'second',
			'message' => 'Second notice',
		] );

		Notice_Manager::register_notice( [
			'id'      => 'third',
			'message' => 'Third notice',
		] );

		$notices = Notice_Manager::get_notices();
		$notice_ids = array_column( $notices, 'id' );

		$this->assertCount( 3, $notices, 'Should have three notices' );
		// Verify all notices are present.
		$this->assertContains( 'first', $notice_ids );
		$this->assertContains( 'second', $notice_ids );
		$this->assertContains( 'third', $notice_ids );
	}

	/**
	 * Test notice variant values.
	 *
	 * @dataProvider provideVariants
	 */
	public function test_notice_variants( $variant ) {
		Notice_Manager::register_notice( [
			'id'      => 'variant-test',
			'message' => 'Test message',
			'variant' => $variant,
		] );

		$notices = Notice_Manager::get_notices();

		$this->assertEquals( $variant, $notices[0]['variant'], "Variant should be $variant" );
	}

	/**
	 * Data provider for variant tests.
	 */
	public function provideVariants() {
		return [
			'error variant'   => [ 'error' ],
			'warning variant' => [ 'warning' ],
			'info variant'    => [ 'info' ],
			'success variant' => [ 'success' ],
		];
	}

	/**
	 * Test notice with HTML message.
	 */
	public function test_notice_with_html_message() {
		$html_message = '<p>This is a <strong>bold</strong> message with <a href="#">link</a></p>';

		Notice_Manager::register_notice( [
			'id'      => 'html-notice',
			'message' => $html_message,
		] );

		$notices = Notice_Manager::get_notices();

		$this->assertEquals( $html_message, $notices[0]['message'], 'HTML message should be preserved' );
	}

	/**
	 * Test notice with multiple actions.
	 */
	public function test_notice_with_multiple_actions() {
		$actions = [
			[
				'label'   => 'Primary Action',
				'url'     => 'https://example.com/primary',
				'variant' => 'primary',
			],
			[
				'label'   => 'Secondary Action',
				'url'     => 'https://example.com/secondary',
				'variant' => 'secondary',
			],
			[
				'label'  => 'Link Action',
				'url'    => 'https://example.com/link',
				'target' => '_blank',
			],
		];

		Notice_Manager::register_notice( [
			'id'      => 'multi-action-notice',
			'message' => 'Notice with multiple actions',
			'actions' => $actions,
		] );

		$notices = Notice_Manager::get_notices();

		$this->assertCount( 3, $notices[0]['actions'], 'Should have three actions' );
		$this->assertEquals( 'Primary Action', $notices[0]['actions'][0]['label'] );
		$this->assertEquals( 'Secondary Action', $notices[0]['actions'][1]['label'] );
		$this->assertEquals( '_blank', $notices[0]['actions'][2]['target'] );
	}

	/**
	 * Test notice pages array with multiple values.
	 */
	public function test_notice_with_multiple_pages() {
		$pages = [ 'sureforms_menu', 'sureforms_entries', 'sureforms_forms', 'sureforms_payments' ];

		Notice_Manager::register_notice( [
			'id'      => 'multi-page-notice',
			'message' => 'Notice for multiple pages',
			'pages'   => $pages,
		] );

		$notices = Notice_Manager::get_notices();

		$this->assertEquals( $pages, $notices[0]['pages'], 'Pages should match input array' );
		$this->assertCount( 4, $notices[0]['pages'], 'Should have four pages' );
	}
}
