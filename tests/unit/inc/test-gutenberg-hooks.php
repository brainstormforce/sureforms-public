<?php
/**
 * Class Test_Gutenberg_Hooks
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Gutenberg_Hooks;

class Test_Gutenberg_Hooks extends TestCase {

	/**
	 * Gutenberg_Hooks instance.
	 *
	 * @var Gutenberg_Hooks
	 */
	protected $gutenberg_hooks;

	protected function setUp(): void {
		parent::setUp();
		$this->gutenberg_hooks = new Gutenberg_Hooks();
	}

	/**
	 * Test that block_editor_assets hooks are registered.
	 */
	public function test_block_editor_assets_hook_registered() {
		$this->assertIsInt(
			has_action( 'enqueue_block_editor_assets', [ $this->gutenberg_hooks, 'block_editor_assets' ] ),
			'block_editor_assets should be hooked to enqueue_block_editor_assets'
		);
	}

	/**
	 * Test that form_editor_screen_assets hooks are registered.
	 */
	public function test_form_editor_screen_assets_hook_registered() {
		$this->assertIsInt(
			has_action( 'enqueue_block_editor_assets', [ $this->gutenberg_hooks, 'form_editor_screen_assets' ] ),
			'form_editor_screen_assets should be hooked to enqueue_block_editor_assets'
		);
	}

	/**
	 * Test that block_editor_assets method exists and is callable.
	 */
	public function test_block_editor_assets_is_callable() {
		$this->assertTrue(
			method_exists( Gutenberg_Hooks::class, 'block_editor_assets' ),
			'The block_editor_assets method should exist on the Gutenberg_Hooks class.'
		);

		$reflection = new ReflectionClass( Gutenberg_Hooks::class );
		$method     = $reflection->getMethod( 'block_editor_assets' );
		$this->assertTrue( $method->isPublic(), 'block_editor_assets should be public.' );
	}

	/**
	 * Test that block_editor_assets localizes google_maps_api_key data.
	 */
	public function test_block_editor_assets_localizes_google_maps_key() {
		// Set up a Google Maps API key.
		update_option( 'srfm_google_maps_settings', [ 'srfm_google_maps_api_key' => 'AIzaSyTestKey123' ] );

		// Mock the screen to avoid null issues.
		$screen = WP_Screen::get( 'post' );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required for unit test to simulate editor screen.
		$GLOBALS['current_screen'] = $screen;

		$this->gutenberg_hooks->block_editor_assets();

		$localized_data = wp_scripts()->get_data( 'sureforms-blocks', 'data' );

		$this->assertNotFalse( $localized_data, 'Block editor assets should localize script data.' );
		$this->assertStringContainsString( 'google_maps_api_key', $localized_data );

		// Clean up.
		delete_option( 'srfm_google_maps_settings' );
	}

	/**
	 * Test that block_editor_assets handles empty google maps settings gracefully.
	 */
	public function test_block_editor_assets_handles_empty_google_maps() {
		delete_option( 'srfm_google_maps_settings' );

		$screen = WP_Screen::get( 'post' );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required for unit test to simulate editor screen.
		$GLOBALS['current_screen'] = $screen;

		$this->gutenberg_hooks->block_editor_assets();

		$localized_data = wp_scripts()->get_data( 'sureforms-blocks', 'data' );

		$this->assertNotFalse( $localized_data, 'Block editor assets should still localize data when google maps settings are empty.' );
	}

	/**
	 * Test that register_block_categories adds the sureforms category.
	 */
	public function test_register_block_categories() {
		$context       = new stdClass();
		$context->post = (object) [ 'post_type' => 'post' ];
		$editor_context = new WP_Block_Editor_Context( [ 'post' => get_post( wp_insert_post( [ 'post_type' => 'post', 'post_title' => 'Test' ] ) ) ] );

		$categories = $this->gutenberg_hooks->register_block_categories( [], $editor_context );

		$slugs = array_column( $categories, 'slug' );
		$this->assertContains( 'sureforms', $slugs, 'Should register sureforms block category.' );
		$this->assertContains( 'sureforms-pro', $slugs, 'Should register sureforms-pro block category.' );
	}

	/**
	 * Test that disable_forms_wrapper_block restricts blocks for sureforms_form post type.
	 */
	public function test_disable_forms_wrapper_block_restricts_for_sureforms() {
		$editor_context       = new stdClass();
		$editor_context->post = (object) [ 'post_type' => 'sureforms_form' ];

		$result = $this->gutenberg_hooks->disable_forms_wrapper_block( true, $editor_context );

		$this->assertIsArray( $result, 'Should return an array of allowed blocks for sureforms_form.' );
		$this->assertContains( 'srfm/input', $result );
		$this->assertContains( 'srfm/email', $result );
	}

	/**
	 * Test that disable_forms_wrapper_block allows all blocks for non-sureforms post types.
	 */
	public function test_disable_forms_wrapper_block_allows_all_for_other_types() {
		$editor_context       = new stdClass();
		$editor_context->post = (object) [ 'post_type' => 'post' ];

		$result = $this->gutenberg_hooks->disable_forms_wrapper_block( true, $editor_context );

		$this->assertTrue( $result, 'Should return the default value for non-sureforms post types.' );
	}
}
