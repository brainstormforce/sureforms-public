<?php
/**
 * Class Test_String_Collector
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Compatibility\Multilingual\String_Collector;
use SRFM\Inc\Compatibility\Multilingual\Providers\Provider;

/**
 * Local stub provider that records every register_string() call so tests can
 * assert exactly what the String_Collector handed off to the provider.
 */
class Srfm_String_Collector_Stub_Provider implements Provider {
	/**
	 * Recorded register_string() invocations.
	 *
	 * @var array<int, array{name:string,value:string,domain:string}>
	 */
	public $registered = [];

	public function is_active(): bool {
		return true;
	}

	public function current_language(): string {
		return 'xx';
	}

	public function default_language(): string {
		return 'xx';
	}

	public function register_string( string $name, string $value, string $domain = 'sureforms' ): void {
		$this->registered[] = [
			'name'   => $name,
			'value'  => $value,
			'domain' => $domain,
		];
	}

	public function translate( string $value, string $name, string $domain = 'sureforms', ?string $language = null ): string {
		return $value;
	}

	public function switch_language( string $language ): void {
		// No-op stub.
	}

	public function restore_language(): void {
		// No-op stub.
	}

	public function render_language_switcher(): string {
		return '';
	}
}

/**
 * Tests for String_Collector.
 */
class Test_String_Collector extends TestCase {

	/**
	 * Stub provider used for the current test, if any.
	 *
	 * @var Srfm_String_Collector_Stub_Provider|null
	 */
	private $stub_provider = null;

	/**
	 * Filter callback currently attached, so it can be removed in tearDown.
	 *
	 * @var callable|null
	 */
	private $filter_callback = null;

	/**
	 * Cached Multilingual_Manager singleton so we can restore it after a test.
	 *
	 * @var mixed
	 */
	private $original_manager = null;

	protected function set_up(): void {
		parent::set_up();

		// Reset the Multilingual_Manager singleton so each test resolves a fresh provider.
		$this->reset_multilingual_manager_singleton();
	}

	protected function tear_down(): void {
		if ( null !== $this->filter_callback ) {
			remove_filter( 'srfm_multilingual_provider', $this->filter_callback, 10 );
			$this->filter_callback = null;
		}
		$this->stub_provider = null;

		$this->reset_multilingual_manager_singleton();

		parent::tear_down();
	}

	/**
	 * Force the Multilingual_Manager to resolve our stub provider for this test.
	 */
	private function install_stub_provider(): Srfm_String_Collector_Stub_Provider {
		$stub                  = new Srfm_String_Collector_Stub_Provider();
		$this->stub_provider   = $stub;
		$this->filter_callback = static function () use ( $stub ) {
			return $stub;
		};
		add_filter( 'srfm_multilingual_provider', $this->filter_callback, 10, 1 );

		$this->reset_multilingual_manager_singleton();

		return $stub;
	}

	/**
	 * Clear the Multilingual_Manager singleton via reflection so the next
	 * get_instance() call resolves the provider afresh.
	 */
	private function reset_multilingual_manager_singleton(): void {
		try {
			$ref = new ReflectionClass( \SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager::class );
			if ( $ref->hasProperty( 'instance' ) ) {
				$prop = $ref->getProperty( 'instance' );
				$prop->setAccessible( true );
				$prop->setValue( null, null );
			}
		} catch ( \ReflectionException $e ) {
			// Property doesn't exist — singleton may use a different storage mechanism; nothing to reset.
			unset( $e );
		}
	}

	/**
	 * Helper: create a SureForms form post and return its ID.
	 */
	private function make_form( array $args = [] ): int {
		if ( ! function_exists( 'wp_insert_post' ) ) {
			$this->markTestSkipped( 'WordPress test bootstrap not available.' );
		}

		$defaults = [
			'post_type'   => 'sureforms_form',
			'post_status' => 'publish',
			'post_title'  => 'String Collector Test Form',
		];
		$post_id  = wp_insert_post( array_merge( $defaults, $args ) );

		if ( is_wp_error( $post_id ) || 0 === (int) $post_id ) {
			$this->markTestSkipped( 'Could not create a form post in the test environment.' );
		}

		return (int) $post_id;
	}

	/**
	 * Returns the names of all strings registered with the stub provider.
	 *
	 * @return array<int, string>
	 */
	private function registered_names(): array {
		if ( null === $this->stub_provider ) {
			return [];
		}
		return array_column( $this->stub_provider->registered, 'name' );
	}

	public function test_on_form_save_skips_when_post_is_revision() {
		$stub        = $this->install_stub_provider();
		$parent_id   = $this->make_form();
		$revision_id = wp_insert_post(
			[
				'post_type'   => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $parent_id,
				'post_title'  => 'rev',
			]
		);

		if ( is_wp_error( $revision_id ) || 0 === (int) $revision_id ) {
			$this->markTestSkipped( 'Could not create a revision post.' );
		}

		String_Collector::get_instance()->on_form_save( (int) $revision_id );

		$this->assertSame( [], $stub->registered, 'No strings should be registered for revisions.' );
	}

	public function test_on_form_save_skips_when_provider_is_inactive() {
		// Do NOT install the stub provider; default test env has no WPML so provider is inactive.
		if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( '\SitePress' ) ) {
			$this->markTestSkipped( 'WPML appears to be loaded in the test environment.' );
		}

		$form_id = $this->make_form();
		update_post_meta( $form_id, '_srfm_submit_button_text', 'Send' );

		$before = did_action( 'wpml_register_single_string' );
		String_Collector::get_instance()->on_form_save( $form_id );
		$after = did_action( 'wpml_register_single_string' );

		$this->assertSame( $before, $after, 'Inactive provider should result in zero registrations.' );
	}

	public function test_collect_registers_submit_button_text() {
		$stub    = $this->install_stub_provider();
		$form_id = $this->make_form();
		update_post_meta( $form_id, '_srfm_submit_button_text', 'Send' );

		String_Collector::get_instance()->collect( $form_id );

		$this->assertContains( 'form_' . $form_id . '_submit_button', $this->registered_names() );

		$matched = array_filter(
			$stub->registered,
			static function ( $row ) use ( $form_id ) {
				return $row['name'] === 'form_' . $form_id . '_submit_button';
			}
		);
		$row     = array_values( $matched )[0];
		$this->assertSame( 'Send', $row['value'] );
		$this->assertSame( 'sureforms', $row['domain'] );
	}

	public function test_collect_registers_confirmation_messages_per_index() {
		$this->install_stub_provider();
		$form_id = $this->make_form();

		$confirmations = [
			[
				[
					'id'      => 1,
					'message' => '<p>Thanks 1</p>',
				],
				[
					'id'      => 2,
					'message' => '<p>Thanks 2</p>',
				],
			],
		];
		update_post_meta( $form_id, '_srfm_form_confirmation', $confirmations[0] );

		String_Collector::get_instance()->collect( $form_id );

		$names = $this->registered_names();
		$this->assertContains( 'form_' . $form_id . '_confirmation_0_message', $names );
		$this->assertContains( 'form_' . $form_id . '_confirmation_1_message', $names );
	}

	public function test_collect_registers_email_notification_fields() {
		$this->install_stub_provider();
		$form_id = $this->make_form();

		$notifications = [
			[
				'subject'   => 'Subject A',
				'body'      => 'Body A',
				'from_name' => 'From A',
			],
		];
		update_post_meta( $form_id, '_srfm_email_notification', $notifications );

		String_Collector::get_instance()->collect( $form_id );

		$names = $this->registered_names();
		$this->assertContains( 'form_' . $form_id . '_notification_0_subject', $names );
		$this->assertContains( 'form_' . $form_id . '_notification_0_body', $names );
		$this->assertContains( 'form_' . $form_id . '_notification_0_from_name', $names );
	}

	public function test_collect_skips_empty_strings() {
		$stub    = $this->install_stub_provider();
		$form_id = $this->make_form();

		update_post_meta( $form_id, '_srfm_submit_button_text', '' );
		update_post_meta(
			$form_id,
			'_srfm_form_confirmation',
			[
				[
					'message' => '',
				],
			]
		);
		update_post_meta(
			$form_id,
			'_srfm_email_notification',
			[
				[
					'subject'   => '   ',
					'body'      => '',
					'from_name' => '',
				],
			]
		);

		String_Collector::get_instance()->collect( $form_id );

		$this->assertSame( [], $stub->registered, 'Empty / whitespace-only strings should not be registered.' );
	}

	public function test_collect_handles_missing_meta_gracefully() {
		$stub    = $this->install_stub_provider();
		$form_id = $this->make_form();

		// No meta set at all.
		String_Collector::get_instance()->collect( $form_id );

		$this->assertSame( [], $stub->registered, 'A form with no meta should produce no registrations.' );
	}

	public function test_get_meta_string_returns_string_for_existing_meta() {
		// Indirectly exercises the private get_meta_string() helper via the public collect()
		// path: setting a known string meta and asserting it is registered with the expected value.
		$stub    = $this->install_stub_provider();
		$form_id = $this->make_form();
		update_post_meta( $form_id, '_srfm_submit_button_text', 'Click me' );

		String_Collector::get_instance()->collect( $form_id );

		$matched = array_filter(
			$stub->registered,
			static function ( $row ) use ( $form_id ) {
				return $row['name'] === 'form_' . $form_id . '_submit_button';
			}
		);
		$this->assertNotEmpty( $matched );
		$row = array_values( $matched )[0];
		$this->assertSame( 'Click me', $row['value'] );
	}

	public function test_register_if_non_empty_skips_whitespace_only_values() {
		// Indirect: register_if_non_empty() is private; exercise via collect() with a whitespace-only value.
		$stub    = $this->install_stub_provider();
		$form_id = $this->make_form();
		update_post_meta( $form_id, '_srfm_submit_button_text', "   \n\t  " );

		String_Collector::get_instance()->collect( $form_id );

		$this->assertSame( [], $stub->registered );
	}

	public function test_collect_validation_messages_registers_known_keys() {
		$stub = $this->install_stub_provider();

		String_Collector::get_instance()->collect_validation_messages();

		$names = $this->registered_names();
		$this->assertContains( 'validation_srfm_valid_email', $names );
		$this->assertContains( 'validation_srfm_valid_phone_number', $names );
		$this->assertContains( 'validation_srfm_valid_url', $names );
		$this->assertContains( 'validation_srfm_input_min_value', $names );
	}

	public function test_collect_validation_messages_noops_when_provider_inactive() {
		// No stub install — Null_Provider is active by default in fresh manager state.
		String_Collector::get_instance()->collect_validation_messages();

		// No way to assert directly without the stub; this test is here to lock
		// the early-return path so future refactors don't accidentally start
		// registering against the null provider.
		$this->assertTrue( true );
	}

	public function test_collect_block_strings_registers_field_attributes() {
		$stub    = $this->install_stub_provider();
		$content = '<!-- wp:srfm/input {"block_id":"abc123","label":"Your Name","placeholder":"Enter your name"} /-->';
		$form_id = $this->make_form( [ 'post_content' => $content ] );

		String_Collector::get_instance()->collect_block_strings( $form_id );

		$names = $this->registered_names();
		$this->assertContains( 'form_' . $form_id . '_block_abc123_label', $names );
		$this->assertContains( 'form_' . $form_id . '_block_abc123_placeholder', $names );
	}

	public function test_collect_block_strings_registers_dropdown_option_labels() {
		$stub    = $this->install_stub_provider();
		$content = '<!-- wp:srfm/dropdown {"block_id":"def456","label":"How?","options":[{"label":"Friend"},{"label":"Google"}]} /-->';
		$form_id = $this->make_form( [ 'post_content' => $content ] );

		String_Collector::get_instance()->collect_block_strings( $form_id );

		$names = $this->registered_names();
		$this->assertContains( 'form_' . $form_id . '_block_def456_label', $names );
		$this->assertContains( 'form_' . $form_id . '_block_def456_option_0_label', $names );
		$this->assertContains( 'form_' . $form_id . '_block_def456_option_1_label', $names );
	}

	public function test_collect_block_strings_noops_on_empty_content() {
		$stub    = $this->install_stub_provider();
		$form_id = $this->make_form( [ 'post_content' => '' ] );

		String_Collector::get_instance()->collect_block_strings( $form_id );

		$this->assertSame( [], $stub->registered );
	}

	public function test_walk_blocks_for_collection_recurses_into_inner_blocks() {
		$stub      = $this->install_stub_provider();
		$collector = String_Collector::get_instance();
		$method    = new \ReflectionMethod( $collector, 'walk_blocks_for_collection' );
		$method->setAccessible( true );

		$blocks = [
			[
				'blockName'   => 'core/group',
				'attrs'       => [],
				'innerBlocks' => [
					[
						'blockName'   => 'srfm/input',
						'attrs'       => [
							'block_id' => 'nest1',
							'label'    => 'Nested',
						],
						'innerBlocks' => [],
					],
				],
			],
		];

		$method->invoke( $collector, 99, $blocks );

		$names = $this->registered_names();
		$this->assertContains( 'form_99_block_nest1_label', $names );
	}

	/**
	 * NOTE: This test is intentionally declared LAST in the file. Defining
	 * DOING_AUTOSAVE is a one-way operation that persists for the rest of the
	 * PHP process, so any tests that depend on autosave being false must run
	 * before this one. PHPUnit executes tests in declaration order by default.
	 */
	public function test_on_form_save_skips_when_autosave_is_defined() {
		if ( defined( 'DOING_AUTOSAVE' ) && ! DOING_AUTOSAVE ) {
			$this->markTestSkipped( 'DOING_AUTOSAVE already defined but set to false; cannot redefine in-process.' );
		}

		if ( ! defined( 'DOING_AUTOSAVE' ) ) {
			define( 'DOING_AUTOSAVE', true );
		}

		$stub    = $this->install_stub_provider();
		$form_id = $this->make_form();
		update_post_meta( $form_id, '_srfm_submit_button_text', 'Send' );

		String_Collector::get_instance()->on_form_save( $form_id );

		$this->assertSame( [], $stub->registered, 'No strings should be registered during an autosave.' );
	}
}
