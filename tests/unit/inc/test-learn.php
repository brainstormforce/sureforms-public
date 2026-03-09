<?php
/**
 * Class Test_Learn
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Inc\Learn;

/**
 * Tests Learn functionality.
 */
class Test_Learn extends TestCase {

	/**
	 * Test user ID for progress tests.
	 *
	 * @var int
	 */
	protected $test_user_id;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create a test user for progress-related tests.
		if ( function_exists( 'wp_create_user' ) ) {
			$this->test_user_id = wp_create_user( 'testuser_' . time(), 'password', 'test_' . time() . '@example.com' );
		}
	}

	/**
	 * Clean up after tests.
	 */
	protected function tearDown(): void {
		// Clean up test user.
		if ( $this->test_user_id && function_exists( 'wp_delete_user' ) ) {
			wp_delete_user( $this->test_user_id );
		}

		parent::tearDown();
	}

	/**
	 * Test get_chapters_structure returns an array.
	 */
	public function test_get_chapters_structure_returns_array() {
		$chapters = Learn::get_chapters_structure();

		$this->assertIsArray( $chapters, 'get_chapters_structure should return an array.' );
		$this->assertNotEmpty( $chapters, 'get_chapters_structure should return a non-empty array.' );
	}

	/**
	 * Test get_chapters_structure returns expected structure.
	 */
	public function test_get_chapters_structure_has_expected_structure() {
		$chapters = Learn::get_chapters_structure();

		// Test first chapter has required keys.
		$first_chapter = $chapters[0];

		$this->assertArrayHasKey( 'id', $first_chapter, 'Chapter should have id key.' );
		$this->assertArrayHasKey( 'title', $first_chapter, 'Chapter should have title key.' );
		$this->assertArrayHasKey( 'description', $first_chapter, 'Chapter should have description key.' );
		$this->assertArrayHasKey( 'steps', $first_chapter, 'Chapter should have steps key.' );
		$this->assertIsArray( $first_chapter['steps'], 'Steps should be an array.' );
	}

	/**
	 * Test get_chapters_structure returns expected chapter IDs.
	 */
	public function test_get_chapters_structure_has_expected_chapters() {
		$chapters = Learn::get_chapters_structure();

		$chapter_ids = array_column( $chapters, 'id' );

		$this->assertContains( 'setting-up-form', $chapter_ids, 'Should contain setting-up-form chapter.' );
		$this->assertContains( 'making-form-live', $chapter_ids, 'Should contain making-form-live chapter.' );
		$this->assertContains( 'managing-entries', $chapter_ids, 'Should contain managing-entries chapter.' );
	}

	/**
	 * Test get_chapters_structure steps have expected structure.
	 */
	public function test_get_chapters_structure_steps_have_expected_structure() {
		$chapters = Learn::get_chapters_structure();

		$first_step = $chapters[0]['steps'][0];

		$this->assertArrayHasKey( 'id', $first_step, 'Step should have id key.' );
		$this->assertArrayHasKey( 'title', $first_step, 'Step should have title key.' );
		$this->assertArrayHasKey( 'description', $first_step, 'Step should have description key.' );
		$this->assertArrayHasKey( 'completed', $first_step, 'Step should have completed key.' );
		$this->assertFalse( $first_step['completed'], 'Default completed status should be false.' );
	}

	/**
	 * Test get_learn_chapters returns chapters with progress.
	 */
	public function test_get_learn_chapters_returns_chapters() {
		if ( ! function_exists( 'get_current_user_id' ) ) {
			$this->markTestSkipped( 'WordPress user functions not available.' );
			return;
		}

		$chapters = Learn::get_learn_chapters();

		$this->assertIsArray( $chapters, 'get_learn_chapters should return an array.' );
		$this->assertNotEmpty( $chapters, 'get_learn_chapters should return a non-empty array.' );
	}

	/**
	 * Test get_learn_chapters with specific user ID.
	 */
	public function test_get_learn_chapters_with_user_id() {
		if ( ! $this->test_user_id || ! function_exists( 'get_user_meta' ) ) {
			$this->markTestSkipped( 'WordPress user functions not available.' );
			return;
		}

		// Set progress for the test user.
		$progress = [
			'setting-up-form' => [
				'creating-first-form' => true,
			],
		];
		update_user_meta( $this->test_user_id, 'srfm_learn_progress', $progress );

		$chapters = Learn::get_learn_chapters( $this->test_user_id );

		// Find the setting-up-form chapter.
		$basics_chapter = null;
		foreach ( $chapters as $chapter ) {
			if ( 'setting-up-form' === $chapter['id'] ) {
				$basics_chapter = $chapter;
				break;
			}
		}

		$this->assertNotNull( $basics_chapter, 'Should find setting-up-form chapter.' );

		// Find the creating-first-form step.
		$first_step = null;
		foreach ( $basics_chapter['steps'] as $step ) {
			if ( 'creating-first-form' === $step['id'] ) {
				$first_step = $step;
				break;
			}
		}

		$this->assertNotNull( $first_step, 'Should find creating-first-form step.' );
		$this->assertTrue( $first_step['completed'], 'Step should be marked as completed.' );

		// Clean up.
		delete_user_meta( $this->test_user_id, 'srfm_learn_progress' );
	}

	/**
	 * Test get_learn_chapters handles empty progress.
	 */
	public function test_get_learn_chapters_handles_empty_progress() {
		if ( ! $this->test_user_id || ! function_exists( 'get_user_meta' ) ) {
			$this->markTestSkipped( 'WordPress user functions not available.' );
			return;
		}

		// Ensure no progress is set.
		delete_user_meta( $this->test_user_id, 'srfm_learn_progress' );

		$chapters = Learn::get_learn_chapters( $this->test_user_id );

		// All steps should have completed = false.
		foreach ( $chapters as $chapter ) {
			foreach ( $chapter['steps'] as $step ) {
				$this->assertFalse( $step['completed'], 'Step should have completed = false when no progress is saved.' );
			}
		}
	}

	/**
	 * Test get_learn_chapters handles invalid progress data.
	 */
	public function test_get_learn_chapters_handles_invalid_progress() {
		if ( ! $this->test_user_id || ! function_exists( 'update_user_meta' ) ) {
			$this->markTestSkipped( 'WordPress user functions not available.' );
			return;
		}

		// Set invalid (non-array) progress.
		update_user_meta( $this->test_user_id, 'srfm_learn_progress', 'invalid_string' );

		$chapters = Learn::get_learn_chapters( $this->test_user_id );

		// Should return valid chapters without error.
		$this->assertIsArray( $chapters, 'Should return array even with invalid progress data.' );
		$this->assertNotEmpty( $chapters, 'Should return chapters even with invalid progress data.' );

		// Clean up.
		delete_user_meta( $this->test_user_id, 'srfm_learn_progress' );
	}

	/**
	 * Test rest_update_learn_progress validates chapter ID.
	 */
	public function test_rest_update_learn_progress_validates_chapter_id() {
		if ( ! class_exists( 'WP_REST_Request' ) ) {
			$this->markTestSkipped( 'WP_REST_Request class not available.' );
			return;
		}

		// Create a mock request with invalid chapter ID.
		$request = new \WP_REST_Request( 'POST', '/sureforms/v1/update-learn-progress' );
		$request->set_param( 'chapterId', 'invalid-chapter-id' );
		$request->set_param( 'stepId', 'creating-first-form' );
		$request->set_param( 'completed', true );

		$learn  = Learn::get_instance();
		$result = $learn->rest_update_learn_progress( $request );

		$this->assertInstanceOf( \WP_Error::class, $result, 'Should return WP_Error for invalid chapter ID.' );
		$this->assertEquals( 'invalid_step', $result->get_error_code(), 'Error code should be invalid_step.' );
	}

	/**
	 * Test rest_update_learn_progress validates step ID.
	 */
	public function test_rest_update_learn_progress_validates_step_id() {
		if ( ! class_exists( 'WP_REST_Request' ) ) {
			$this->markTestSkipped( 'WP_REST_Request class not available.' );
			return;
		}

		// Create a mock request with valid chapter but invalid step ID.
		$request = new \WP_REST_Request( 'POST', '/sureforms/v1/update-learn-progress' );
		$request->set_param( 'chapterId', 'setting-up-form' );
		$request->set_param( 'stepId', 'invalid-step-id' );
		$request->set_param( 'completed', true );

		$learn  = Learn::get_instance();
		$result = $learn->rest_update_learn_progress( $request );

		$this->assertInstanceOf( \WP_Error::class, $result, 'Should return WP_Error for invalid step ID.' );
		$this->assertEquals( 'invalid_step', $result->get_error_code(), 'Error code should be invalid_step.' );
	}

	/**
	 * Test rest_update_learn_progress saves progress successfully.
	 */
	public function test_rest_update_learn_progress_saves_progress() {
		if ( ! class_exists( 'WP_REST_Request' ) || ! function_exists( 'wp_set_current_user' ) ) {
			$this->markTestSkipped( 'WordPress REST API or user functions not available.' );
			return;
		}

		if ( ! $this->test_user_id ) {
			$this->markTestSkipped( 'Test user not available.' );
			return;
		}

		// Set current user.
		wp_set_current_user( $this->test_user_id );

		// Create a valid request.
		$request = new \WP_REST_Request( 'POST', '/sureforms/v1/update-learn-progress' );
		$request->set_param( 'chapterId', 'setting-up-form' );
		$request->set_param( 'stepId', 'creating-first-form' );
		$request->set_param( 'completed', true );

		$learn  = Learn::get_instance();
		$result = $learn->rest_update_learn_progress( $request );

		// Check the response.
		$this->assertNotInstanceOf( \WP_Error::class, $result, 'Should not return WP_Error for valid request.' );

		$response_data = $result->get_data();
		$this->assertTrue( $response_data['success'], 'Response should indicate success.' );
		$this->assertEquals( 'setting-up-form', $response_data['chapterId'], 'Response should contain chapterId.' );
		$this->assertEquals( 'creating-first-form', $response_data['stepId'], 'Response should contain stepId.' );
		$this->assertTrue( $response_data['completed'], 'Response should contain completed status.' );

		// Verify progress was saved.
		$saved_progress = get_user_meta( $this->test_user_id, 'srfm_learn_progress', true );
		$this->assertIsArray( $saved_progress, 'Saved progress should be an array.' );
		$this->assertTrue( $saved_progress['setting-up-form']['creating-first-form'], 'Progress should be saved.' );

		// Clean up.
		delete_user_meta( $this->test_user_id, 'srfm_learn_progress' );
		wp_set_current_user( 0 );
	}

	/**
	 * Test rest_update_learn_progress can mark step as incomplete.
	 */
	public function test_rest_update_learn_progress_can_mark_incomplete() {
		if ( ! class_exists( 'WP_REST_Request' ) || ! function_exists( 'wp_set_current_user' ) ) {
			$this->markTestSkipped( 'WordPress REST API or user functions not available.' );
			return;
		}

		if ( ! $this->test_user_id ) {
			$this->markTestSkipped( 'Test user not available.' );
			return;
		}

		// Set current user.
		wp_set_current_user( $this->test_user_id );

		// First, mark as complete.
		$progress = [
			'setting-up-form' => [
				'creating-first-form' => true,
			],
		];
		update_user_meta( $this->test_user_id, 'srfm_learn_progress', $progress );

		// Now mark as incomplete.
		$request = new \WP_REST_Request( 'POST', '/sureforms/v1/update-learn-progress' );
		$request->set_param( 'chapterId', 'setting-up-form' );
		$request->set_param( 'stepId', 'creating-first-form' );
		$request->set_param( 'completed', false );

		$learn  = Learn::get_instance();
		$result = $learn->rest_update_learn_progress( $request );

		$this->assertNotInstanceOf( \WP_Error::class, $result, 'Should not return WP_Error.' );

		// Verify progress was updated.
		$saved_progress = get_user_meta( $this->test_user_id, 'srfm_learn_progress', true );
		$this->assertFalse( $saved_progress['setting-up-form']['creating-first-form'], 'Progress should be marked as incomplete.' );

		// Clean up.
		delete_user_meta( $this->test_user_id, 'srfm_learn_progress' );
		wp_set_current_user( 0 );
	}

	/**
	 * Test srfm_learn_chapters filter works.
	 */
	public function test_srfm_learn_chapters_filter() {
		// Add a filter to modify chapters.
		add_filter( 'srfm_learn_chapters', function( $chapters ) {
			$chapters[] = [
				'id'          => 'custom-chapter',
				'title'       => 'Custom Chapter',
				'description' => 'A custom chapter added via filter.',
				'steps'       => [],
			];
			return $chapters;
		} );

		$chapters = Learn::get_chapters_structure();

		// Find the custom chapter.
		$custom_chapter = null;
		foreach ( $chapters as $chapter ) {
			if ( 'custom-chapter' === $chapter['id'] ) {
				$custom_chapter = $chapter;
				break;
			}
		}

		$this->assertNotNull( $custom_chapter, 'Custom chapter should be added via filter.' );
		$this->assertEquals( 'Custom Chapter', $custom_chapter['title'], 'Custom chapter should have correct title.' );

		// Remove the filter.
		remove_all_filters( 'srfm_learn_chapters' );
	}

	/**
	 * Test get_chapters_structure contains specific steps.
	 */
	public function test_get_chapters_structure_contains_expected_steps() {
		$chapters = Learn::get_chapters_structure();

		// Find setting-up-form chapter.
		$basics_chapter = null;
		foreach ( $chapters as $chapter ) {
			if ( 'setting-up-form' === $chapter['id'] ) {
				$basics_chapter = $chapter;
				break;
			}
		}

		$this->assertNotNull( $basics_chapter, 'Should find setting-up-form chapter.' );

		$step_ids = array_column( $basics_chapter['steps'], 'id' );

		$this->assertContains( 'creating-first-form', $step_ids, 'Should contain creating-first-form step.' );
		$this->assertContains( 'set-up-form-fields', $step_ids, 'Should contain set-up-form-fields step.' );
		$this->assertContains( 'style-your-forms', $step_ids, 'Should contain style-your-forms step.' );
	}

	/**
	 * Test steps have learn property with valid type.
	 */
	public function test_steps_have_valid_learn_type() {
		$chapters = Learn::get_chapters_structure();

		$valid_types = [ 'link', 'dialog' ];

		foreach ( $chapters as $chapter ) {
			foreach ( $chapter['steps'] as $step ) {
				$this->assertArrayHasKey( 'learn', $step, "Step {$step['id']} should have learn key." );
				$this->assertArrayHasKey( 'type', $step['learn'], "Step {$step['id']} learn should have type key." );
				$this->assertContains(
					$step['learn']['type'],
					$valid_types,
					"Step {$step['id']} learn type should be 'link' or 'dialog'."
				);
			}
		}
	}

	/**
	 * Test get_embed_forms_url returns a string URL.
	 */
	public function test_get_embed_forms_url() {
		if ( ! function_exists( 'get_page_by_path' ) ) {
			$this->markTestSkipped( 'WordPress page functions not available.' );
			return;
		}

		$url = Learn::get_embed_forms_url();

		$this->assertIsString( $url, 'get_embed_forms_url should return a string.' );
		$this->assertNotEmpty( $url, 'get_embed_forms_url should return a non-empty string.' );

		// The URL must end with one of the three known patterns.
		$valid_endings = [
			'action=edit&source=learn',
			'post_type=page&source=learn',
		];
		$matches_pattern = false;
		foreach ( $valid_endings as $ending ) {
			if ( str_contains( $url, $ending ) ) {
				$matches_pattern = true;
				break;
			}
		}
		$this->assertTrue( $matches_pattern, "get_embed_forms_url returned unexpected URL: $url" );
	}

	/**
	 * Test register_rest_routes registers expected routes.
	 */
	public function test_register_rest_routes() {
		if ( ! function_exists( 'rest_get_server' ) ) {
			$this->markTestSkipped( 'WordPress REST server not available.' );
			return;
		}

		$learn = Learn::get_instance();
		$learn->register_rest_routes();

		$server = rest_get_server();
		$routes = $server->get_routes();

		$this->assertArrayHasKey(
			'/sureforms/v1/get-learn-chapters',
			$routes,
			'GET /sureforms/v1/get-learn-chapters route should be registered.'
		);
		$this->assertArrayHasKey(
			'/sureforms/v1/update-learn-progress',
			$routes,
			'POST /sureforms/v1/update-learn-progress route should be registered.'
		);
	}

	/**
	 * Test rest_get_learn_chapters returns a REST response with chapters data.
	 */
	public function test_rest_get_learn_chapters() {
		if ( ! function_exists( 'rest_ensure_response' ) || ! function_exists( 'wp_set_current_user' ) ) {
			$this->markTestSkipped( 'WordPress REST API functions not available.' );
			return;
		}

		if ( $this->test_user_id ) {
			wp_set_current_user( $this->test_user_id );
		}

		$learn    = Learn::get_instance();
		$response = $learn->rest_get_learn_chapters();

		$this->assertNotInstanceOf( \WP_Error::class, $response, 'rest_get_learn_chapters should not return WP_Error.' );

		$data = $response->get_data();

		$this->assertIsArray( $data, 'Response data should be an array.' );
		$this->assertNotEmpty( $data, 'Response data should not be empty.' );

		// Each item should have the expected chapter keys.
		$first = $data[0];
		$this->assertArrayHasKey( 'id', $first, 'Chapter should have id key.' );
		$this->assertArrayHasKey( 'steps', $first, 'Chapter should have steps key.' );

		if ( $this->test_user_id ) {
			wp_set_current_user( 0 );
		}
	}
}
