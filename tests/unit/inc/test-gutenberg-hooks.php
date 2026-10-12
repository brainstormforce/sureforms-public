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

	/**
	 * The email delivery status names the mailer and SureMail's admin URLs, so
	 * it reaches only admins in the SureForms form editor, not every
	 * block-editor screen.
	 */
	public function test_block_editor_assets_scopes_email_delivery_to_the_form_editor() {
		$admin  = wp_insert_user( [ 'user_login' => 'srfm_ed_admin_' . wp_rand(), 'user_pass' => wp_generate_password(), 'role' => 'administrator' ] );
		$author = wp_insert_user( [ 'user_login' => 'srfm_ed_author_' . wp_rand(), 'user_pass' => wp_generate_password(), 'role' => 'author' ] );
		$hooks  = \SRFM\Inc\Gutenberg_Hooks::get_instance();

		$localized = function () use ( $hooks ) {
			wp_scripts()->registered = [];
			$hooks->block_editor_assets();
			$data = wp_scripts()->get_data( SRFM_SLUG . '-blocks', 'data' );
			preg_match( '/"email_delivery":(null|\{)/', (string) $data, $m );
			return $m[1] ?? 'missing';
		};

		wp_set_current_user( $admin );
		set_current_screen( 'post' );
		$this->assertSame( 'null', $localized(), 'Not on other post types.' );

		set_current_screen( SRFM_FORMS_POST_TYPE );
		$this->assertSame( '{', $localized(), 'Admins in the form editor get it.' );

		wp_set_current_user( $author );
		$this->assertSame( 'null', $localized(), 'Not for non-admins.' );

		wp_set_current_user( 0 );
		set_current_screen( 'front' );
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $admin );
		wp_delete_user( $author );
	}
}
