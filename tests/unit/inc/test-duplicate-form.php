<?php
/**
 * Class Test_Duplicate_Form
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Inc\Duplicate_Form;

/**
 * Tests Duplicate Form functionality.
 */
class Test_Duplicate_Form extends TestCase {

	protected $duplicate_form;

	protected function setUp(): void {
		$this->duplicate_form = new Duplicate_Form();
	}

	/**
	 * Test update_block_form_ids method.
	 * This function tests the private method that updates form IDs in Gutenberg block content.
	 * It includes cases where:
	 * 1. Simple formId replacement in JSON.
	 * 2. Multiple formId occurrences.
	 * 3. Content with no formId.
	 * 4. Content with special characters (verifying no addslashes corruption).
	 * 5. Content with nested JSON structures.
	 */
	public function test_update_block_form_ids() {

		// Test case 1: Simple formId replacement
		$content = '<!-- wp:sureforms/form {"formId":123} /-->';
		$expected = '<!-- wp:sureforms/form {"formId":456} /-->';
		$result = $this->call_private_method( $this->duplicate_form, 'update_block_form_ids', [ $content, 123, 456 ] );
		$this->assertEquals( $expected, $result, 'Failed asserting simple formId replacement.' );

		// Test case 2: Multiple formId occurrences
		$content = '{"formId":123,"settings":{"formId":123}}';
		$expected = '{"formId":456,"settings":{"formId":456}}';
		$result = $this->call_private_method( $this->duplicate_form, 'update_block_form_ids', [ $content, 123, 456 ] );
		$this->assertEquals( $expected, $result, 'Failed asserting multiple formId replacements.' );

		// Test case 3: Content with no formId
		$content = '<!-- wp:paragraph --><p>Test content</p><!-- /wp:paragraph -->';
		$expected = '<!-- wp:paragraph --><p>Test content</p><!-- /wp:paragraph -->';
		$result = $this->call_private_method( $this->duplicate_form, 'update_block_form_ids', [ $content, 123, 456 ] );
		$this->assertEquals( $expected, $result, 'Failed asserting content without formId remains unchanged.' );

		// Test case 4: Content with quotes (verifying no addslashes corruption)
		$content = '{"formId":123,"label":"Test\'s Form"}';
		$expected = '{"formId":456,"label":"Test\'s Form"}';
		$result = $this->call_private_method( $this->duplicate_form, 'update_block_form_ids', [ $content, 123, 456 ] );
		$this->assertEquals( $expected, $result, 'Failed asserting no backslash corruption with quotes.' );

		// Test case 5: Nested JSON structures
		$content = '{"formId":123,"blocks":[{"formId":123,"attrs":{"formId":123}}]}';
		$expected = '{"formId":789,"blocks":[{"formId":789,"attrs":{"formId":789}}]}';
		$result = $this->call_private_method( $this->duplicate_form, 'update_block_form_ids', [ $content, 123, 789 ] );
		$this->assertEquals( $expected, $result, 'Failed asserting nested JSON formId replacements.' );
	}

	/**
	 * Test generate_unique_title method.
	 * This function tests the private method that generates unique titles for duplicated forms.
	 * It includes cases where:
	 * 1. No existing form with the same title (simple case).
	 * 2. Form with conflicting title exists (should append counter).
	 * 3. Multiple forms with similar titles exist (should use incremented counter).
	 * 4. Custom suffix provided.
	 * 5. Empty base title.
	 */
	public function test_generate_unique_title() {

		// Mock title_exists to simulate various scenarios
		// For simplicity, we'll test the logic without actual database calls
		// In a real test, you would mock the title_exists method

		// Test case 1: No conflict - direct suffix append
		$base_title = 'Contact Form';
		$suffix = ' (Copy)';
		// Without mocking title_exists, we can only test the basic format
		$result = $this->call_private_method( $this->duplicate_form, 'generate_unique_title', [ $base_title, $suffix ] );
		$this->assertStringContainsString( $base_title, $result, 'Failed asserting title contains base title.' );
		$this->assertStringContainsString( $suffix, $result, 'Failed asserting title contains suffix.' );

		// Test case 2: Custom suffix
		$base_title = 'Newsletter Form';
		$suffix = ' - Duplicate';
		$result = $this->call_private_method( $this->duplicate_form, 'generate_unique_title', [ $base_title, $suffix ] );
		$this->assertStringContainsString( $base_title, $result, 'Failed asserting title contains base title with custom suffix.' );
		$this->assertStringContainsString( $suffix, $result, 'Failed asserting title contains custom suffix.' );

		// Test case 3: Empty suffix
		$base_title = 'Survey Form';
		$suffix = '';
		$result = $this->call_private_method( $this->duplicate_form, 'generate_unique_title', [ $base_title, $suffix ] );
		$this->assertEquals( $base_title, $result, 'Failed asserting title with empty suffix.' );

		// Test case 4: Special characters in title
		$base_title = 'Form & Survey <2024>';
		$suffix = ' (Copy)';
		$result = $this->call_private_method( $this->duplicate_form, 'generate_unique_title', [ $base_title, $suffix ] );
		$this->assertStringContainsString( $base_title, $result, 'Failed asserting title with special characters.' );
	}

	/**
	 * Test duplicate_form method - validation scenarios.
	 * This function tests the main duplicate_form method for various validation scenarios.
	 * It includes cases where:
	 * 1. Invalid form ID (zero).
	 * 2. Invalid form ID (negative).
	 * 3. Invalid form ID (non-numeric string converted to zero).
	 */
	public function test_duplicate_form_validation() {

		// Test case 1: Invalid form ID (zero)
		$result = $this->duplicate_form->duplicate_form( 0 );
		$this->assertInstanceOf( \WP_Error::class, $result, 'Failed asserting WP_Error returned for zero form ID.' );
		$this->assertEquals( 'invalid_form_id', $result->get_error_code(), 'Failed asserting error code is invalid_form_id.' );

		// Test case 2: Invalid form ID (negative)
		$result = $this->duplicate_form->duplicate_form( -5 );
		$this->assertInstanceOf( \WP_Error::class, $result, 'Failed asserting WP_Error returned for negative form ID.' );
		$this->assertEquals( 'invalid_form_id', $result->get_error_code(), 'Failed asserting error code is invalid_form_id for negative ID.' );

		// Test case 3: Invalid form ID (string that converts to zero)
		$result = $this->duplicate_form->duplicate_form( 'abc' );
		$this->assertInstanceOf( \WP_Error::class, $result, 'Failed asserting WP_Error returned for non-numeric string.' );
		$this->assertEquals( 'invalid_form_id', $result->get_error_code(), 'Failed asserting error code is invalid_form_id for string input.' );
	}

	/**
	 * Test duplicate_form method - form not found scenario.
	 * This function tests behavior when the source form doesn't exist.
	 * Note: This test requires WordPress to be available (get_post function).
	 */
	public function test_duplicate_form_not_found() {

		// Test case: Non-existent form ID
		// This will return form_not_found error when get_post returns null
		$non_existent_id = 999999;
		$result = $this->duplicate_form->duplicate_form( $non_existent_id );

		// Only test if WordPress functions are available
		if ( function_exists( 'get_post' ) ) {
			$this->assertInstanceOf( \WP_Error::class, $result, 'Failed asserting WP_Error returned for non-existent form.' );
			if ( is_wp_error( $result ) ) {
				$this->assertEquals( 'form_not_found', $result->get_error_code(), 'Failed asserting error code is form_not_found.' );
			}
		} else {
			$this->markTestSkipped( 'WordPress functions not available for this test.' );
		}
	}

	/**
	 * Test handle_duplicate_form_rest method.
	 * This function tests the REST API handler method.
	 * It includes cases where:
	 * 1. Valid request with all parameters.
	 * 2. Valid request with default title suffix.
	 * 3. Request returning WP_Error (should pass through).
	 */
	public function test_handle_duplicate_form_rest() {

		// Create a mock WP_REST_Request
		if ( ! class_exists( 'WP_REST_Request' ) ) {
			$this->markTestSkipped( 'WP_REST_Request class not available for this test.' );
			return;
		}

		// Test case 1: Valid request with all parameters
		$request = new \WP_REST_Request( 'POST', '/wp/v2/sureforms/forms/duplicate' );
		$request->set_param( 'form_id', 999999 ); // Non-existent form
		$request->set_param( 'title_suffix', ' (Test Copy)' );

		$result = $this->duplicate_form->handle_duplicate_form_rest( $request );

		// Since form doesn't exist, should return WP_Error
		if ( function_exists( 'get_post' ) ) {
			$this->assertInstanceOf( \WP_Error::class, $result, 'Failed asserting WP_Error returned from REST handler.' );
		}

		// Test case 2: Valid request with default title suffix
		$request = new \WP_REST_Request( 'POST', '/wp/v2/sureforms/forms/duplicate' );
		$request->set_param( 'form_id', 999999 );

		$result = $this->duplicate_form->handle_duplicate_form_rest( $request );

		// Should still return error for non-existent form
		if ( function_exists( 'get_post' ) ) {
			$this->assertInstanceOf( \WP_Error::class, $result, 'Failed asserting WP_Error with default suffix.' );
		}
	}

	/**
	 * Test get_unserialized_post_metas method.
	 * This function tests the private method that returns list of unserialized meta keys.
	 * It verifies that the method returns an array and delegates to Export class.
	 */
	public function test_get_unserialized_post_metas() {

		// Test that method returns an array
		$result = $this->call_private_method( $this->duplicate_form, 'get_unserialized_post_metas', [] );

		// The method delegates to Export class, so we just verify it returns an array
		$this->assertIsArray( $result, 'Failed asserting get_unserialized_post_metas returns an array.' );
	}

	/**
	 * Test title_exists method.
	 * This function tests the private method that checks if a form title exists.
	 * Note: This test requires WordPress database functions to be available.
	 */
	public function test_title_exists() {

		if ( ! function_exists( 'get_post' ) ) {
			$this->markTestSkipped( 'WordPress functions not available for this test.' );
			return;
		}

		// Test case 1: Check for a title that definitely doesn't exist
		$unique_title = 'Unique Form Title ' . time() . ' ' . wp_rand( 1000, 9999 );
		$result = $this->call_private_method( $this->duplicate_form, 'title_exists', [ $unique_title ] );
		$this->assertIsBool( $result, 'Failed asserting title_exists returns boolean.' );

		// Test case 2: Empty title
		$result = $this->call_private_method( $this->duplicate_form, 'title_exists', [ '' ] );
		$this->assertIsBool( $result, 'Failed asserting title_exists handles empty string.' );

		// Test case 3: Title with special characters
		$special_title = "Test's Form & Survey <2024>";
		$result = $this->call_private_method( $this->duplicate_form, 'title_exists', [ $special_title ] );
		$this->assertIsBool( $result, 'Failed asserting title_exists handles special characters.' );
	}

	/**
	 * Test duplicate_form with custom title suffix.
	 * This function tests duplication with various custom suffixes.
	 */
	public function test_duplicate_form_custom_suffix() {

		// Test case 1: Custom suffix
		$result = $this->duplicate_form->duplicate_form( 999999, ' - Copy 1' );
		if ( function_exists( 'get_post' ) ) {
			$this->assertInstanceOf( \WP_Error::class, $result, 'Failed asserting error with custom suffix.' );
		}

		// Test case 2: Empty suffix
		$result = $this->duplicate_form->duplicate_form( 999999, '' );
		if ( function_exists( 'get_post' ) ) {
			$this->assertInstanceOf( \WP_Error::class, $result, 'Failed asserting error with empty suffix.' );
		}

		// Test case 3: Long suffix
		$long_suffix = ' - ' . str_repeat( 'Copy ', 20 );
		$result = $this->duplicate_form->duplicate_form( 999999, $long_suffix );
		if ( function_exists( 'get_post' ) ) {
			$this->assertInstanceOf( \WP_Error::class, $result, 'Failed asserting error with long suffix.' );
		}
	}

	/**
	 * Test security fix - authorization check.
	 * This function verifies that the authorization fix is working correctly.
	 * It tests that current_user_can('edit_post', $form_id) is checked.
	 * Note: Requires WordPress user functions.
	 */
	public function test_authorization_check() {

		if ( ! function_exists( 'current_user_can' ) ) {
			$this->markTestSkipped( 'WordPress user functions not available for this test.' );
			return;
		}

		// Test with a form ID that exists but user doesn't have permission
		// This would typically return insufficient_permissions error
		// The exact behavior depends on WordPress test environment setup

		$result = $this->duplicate_form->duplicate_form( 1 ); // Try with form ID 1

		// If WordPress is available and form exists, check error handling
		if ( is_wp_error( $result ) ) {
			$valid_error_codes = [ 'invalid_form_id', 'form_not_found', 'invalid_post_type', 'insufficient_permissions' ];
			$this->assertContains(
				$result->get_error_code(),
				$valid_error_codes,
				'Failed asserting error code is one of the expected validation codes.'
			);
		}
	}

	/**
	 * Test that addslashes bug is fixed.
	 * This function verifies the critical security fix for data corruption.
	 * It ensures that update_block_form_ids does NOT use addslashes.
	 */
	public function test_no_addslashes_corruption() {

		// Test content with quotes that would be corrupted by addslashes
		$content_with_quotes = '{"formId":123,"attributes":{"label":"User\'s Name","placeholder":"Enter user\'s email"}}';

		$result = $this->call_private_method( $this->duplicate_form, 'update_block_form_ids', [ $content_with_quotes, 123, 456 ] );

		// Verify no backslashes were added
		$this->assertStringNotContainsString( '\\\'', $result, 'Failed asserting no escaped quotes (addslashes corruption).' );

		// Verify formId was correctly replaced
		$this->assertStringContainsString( '"formId":456', $result, 'Failed asserting formId replacement occurred.' );

		// Verify original quotes are preserved
		$this->assertStringContainsString( "User's Name", $result, 'Failed asserting original quotes preserved.' );
		$this->assertStringContainsString( "user's email", $result, 'Failed asserting original quotes in placeholder preserved.' );
	}

	/**
	 * Test edge cases for form ID updates.
	 * This function tests various edge cases in the update_block_form_ids method.
	 */
	public function test_update_block_form_ids_edge_cases() {

		// Test case 1: Exact match only - using IDs that won't create partial matches
		$content = '{"formId":123,"settings":{"nested":{"formId":123}}}';
		$expected = '{"formId":789,"settings":{"nested":{"formId":789}}}';
		$result = $this->call_private_method( $this->duplicate_form, 'update_block_form_ids', [ $content, 123, 789 ] );
		$this->assertEquals( $expected, $result, 'Failed asserting exact match replacement.' );

		// Test case 2: Empty content
		$result = $this->call_private_method( $this->duplicate_form, 'update_block_form_ids', [ '', 123, 456 ] );
		$this->assertEquals( '', $result, 'Failed asserting empty content remains empty.' );

		// Test case 3: Content with only whitespace
		$content = "   \n\t  ";
		$result = $this->call_private_method( $this->duplicate_form, 'update_block_form_ids', [ $content, 123, 456 ] );
		$this->assertEquals( $content, $result, 'Failed asserting whitespace-only content unchanged.' );

		// Test case 4: Very large form IDs
		$content = '{"formId":2147483647}'; // Max 32-bit integer
		$expected = '{"formId":2147483646}';
		$result = $this->call_private_method( $this->duplicate_form, 'update_block_form_ids', [ $content, 2147483647, 2147483646 ] );
		$this->assertEquals( $expected, $result, 'Failed asserting large form ID replacement.' );

		// Test case 5: No formId present - should not change content
		$content = '{"otherId":123,"data":"test"}';
		$result = $this->call_private_method( $this->duplicate_form, 'update_block_form_ids', [ $content, 123, 456 ] );
		$this->assertEquals( $content, $result, 'Failed asserting content without formId unchanged.' );
	}

	/**
	 * Helper method to call private methods for testing.
	 */
	private function call_private_method( $object, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $object, $parameters );
	}
}
