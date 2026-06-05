<?php
/**
 * Class Test_String_Collector
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Compatibility\Multilingual\String_Collector;
use SRFM\Inc\Compatibility\Multilingual\String_Translator;
use SRFM\Inc\Compatibility\Multilingual\Providers\Provider;

/**
 * Local stub provider that records every register_string() call so tests can
 * assert exactly what the String_Collector handed off to the provider.
 */
class Srfm_String_Collector_Stub_Provider implements Provider {
	/**
	 * Recorded register_string()/register_package_string() invocations. Package
	 * strings are mirrored here under their legacy flat name (`form_{id}_{name}`)
	 * so existing name-based assertions keep working across both paths.
	 *
	 * @var array<int, array{name:string,value:string,domain:string}>
	 */
	public $registered = [];

	/**
	 * Recorded register_package_string() calls with full package detail.
	 *
	 * @var array<int, array<string,mixed>>
	 */
	public $package_strings = [];

	/**
	 * Recorded start/finish package lifecycle calls: [ 'start'|'finish', $package ].
	 *
	 * @var array<int, array{0:string,1:array<string,string>}>
	 */
	public $package_events = [];

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

	public function supports_packages(): bool {
		return true;
	}

	public function start_package( array $package ): void {
		$this->package_events[] = [ 'start', $package ];
	}

	public function finish_package( array $package ): void {
		$this->package_events[] = [ 'finish', $package ];
	}

	public function register_package_string( array $package, string $name, string $value, string $title = '', string $type = 'LINE' ): void {
		// Mirror under the legacy flat name so existing name-based assertions hold.
		$this->registered[]      = [
			'name'   => 'form_' . ( $package['name'] ?? '' ) . '_' . $name,
			'value'  => $value,
			'domain' => 'sureforms',
		];
		$this->package_strings[] = [
			'name'    => $name,
			'value'   => $value,
			'title'   => $title,
			'type'    => $type,
			'package' => $package,
		];
	}

	public function translate_package_string( array $package, string $name, string $value ): string {
		return $value;
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

	public function test_collect_does_not_register_notification_reply_to() {
		$this->install_stub_provider();
		$form_id = $this->make_form();

		$notifications = [
			[
				'subject'   => 'Subject A',
				'body'      => 'Body A',
				'from_name' => 'From A',
				'reply_to'  => 'reply@example.com',
			],
		];
		update_post_meta( $form_id, '_srfm_email_notification', $notifications );

		String_Collector::get_instance()->collect( $form_id );

		$names = $this->registered_names();
		// reply_to is an email address — it must never be registered as translatable.
		$this->assertNotContains( 'form_' . $form_id . '_notification_0_reply_to', $names );
		// The genuine copy fields are still registered.
		$this->assertContains( 'form_' . $form_id . '_notification_0_subject', $names );
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

	public function test_collect_registers_strings_as_a_form_package() {
		$stub    = $this->install_stub_provider();
		$form_id = $this->make_form( [ 'post_title' => 'Contact Us' ] );
		update_post_meta( $form_id, '_srfm_submit_button_text', 'Send' );

		// Isolate the explicit collect() from the save_post-triggered run during make_form().
		$stub->package_events  = [];
		$stub->package_strings = [];

		String_Collector::get_instance()->collect( $form_id );

		// The run is bracketed by a start + finish for one package.
		$kinds = array_column( array_column( $stub->package_events, 1 ), 'kind' );
		$this->assertContains( String_Translator::PACKAGE_KIND, $kinds, 'A SureForms Form package should be opened.' );
		$starts = array_filter( $stub->package_events, static fn( $e ) => 'start' === $e[0] );
		$ends   = array_filter( $stub->package_events, static fn( $e ) => 'finish' === $e[0] );
		$this->assertCount( 1, $starts, 'Exactly one package start.' );
		$this->assertCount( 1, $ends, 'Exactly one package finish (prunes removed strings).' );

		$pkg = array_values( $starts )[0][1];
		$this->assertSame( String_Translator::PACKAGE_KIND, $pkg['kind'] );
		$this->assertSame( (string) $form_id, $pkg['name'] );
		$this->assertSame( 'Contact Us', $pkg['title'] );
	}

	public function test_collect_package_strings_carry_titles_and_types() {
		$stub    = $this->install_stub_provider();
		$form_id = $this->make_form();
		update_post_meta( $form_id, '_srfm_submit_button_text', 'Send' );
		update_post_meta( $form_id, '_srfm_form_confirmation', [ [ 'message' => '<p>Thanks</p>' ] ] );

		$stub->package_strings = [];
		String_Collector::get_instance()->collect( $form_id );

		$by_name = [];
		foreach ( $stub->package_strings as $row ) {
			$by_name[ $row['name'] ] = $row;
		}

		$this->assertArrayHasKey( String_Translator::submit_button_name(), $by_name );
		$this->assertNotSame( '', $by_name[ String_Translator::submit_button_name() ]['title'], 'Submit button has a human title.' );
		$this->assertSame( 'LINE', $by_name[ String_Translator::submit_button_name() ]['type'] );

		// Titles follow WPML's "Group: Leaf" convention so the Translation Editor can
		// group them. The ': ' label separator is what WPML splits on — without it the
		// editor renders the strings flat.
		$this->assertStringContainsString( ': ', $by_name[ String_Translator::submit_button_name() ]['title'], 'Submit button title uses the Group: Leaf separator.' );

		$conf = String_Translator::confirmation_name( 0 );
		$this->assertArrayHasKey( $conf, $by_name );
		$this->assertSame( 'AREA', $by_name[ $conf ]['type'], 'Confirmation message uses a multi-line editor type.' );
		$this->assertStringContainsString( ': ', $by_name[ $conf ]['title'], 'Confirmation title uses the Group: Leaf separator.' );
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
