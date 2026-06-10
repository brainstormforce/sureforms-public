<?php
/**
 * Class Test_String_Translator
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Compatibility\Multilingual\String_Translator;

/**
 * Stub provider that records every translate() call so tests can assert on
 * the name/value/domain passed in by the translator.
 */
class Srfm_String_Translator_Stub_Provider implements \SRFM\Inc\Compatibility\Multilingual\Providers\Provider {
	/**
	 * Recorded calls to translate().
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $calls = [];

	/**
	 * Value to return from translate(). When empty, translate() returns the original value.
	 *
	 * @var string
	 */
	public string $translate_returns = '';

	/**
	 * Whether this stub advertises String Package support. Defaults to false so the
	 * translator uses the flat translate() fallback (and existing assertions hold);
	 * flip to true in a test to exercise the package path.
	 *
	 * @var bool
	 */
	public bool $supports_packages = false;

	/**
	 * Recorded calls to translate_package_string().
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $package_calls = [];

	public function is_active(): bool {
		return true;
	}

	public function current_language(): string {
		return 'de';
	}

	public function default_language(): string {
		return 'en';
	}

	public function register_string( string $name, string $value, string $domain = 'sureforms' ): void {
		// No-op.
		unset( $name, $value, $domain );
	}

	public function translate( string $value, string $name, string $domain = 'sureforms', ?string $language = null ): string {
		$this->calls[] = compact( 'value', 'name', 'domain', 'language' );
		return '' !== $this->translate_returns ? $this->translate_returns : $value;
	}

	public function switch_language( string $language ): void {
		unset( $language );
	}

	public function restore_language(): void {
		// No-op.
	}

	public function render_language_switcher(): string {
		return '';
	}

	public function supports_packages(): bool {
		return $this->supports_packages;
	}

	public function start_package( array $package ): void {
		unset( $package );
	}

	public function finish_package( array $package ): void {
		unset( $package );
	}

	public function register_package_string( array $package, string $name, string $value, string $title = '', string $type = 'LINE' ): void {
		unset( $package, $name, $value, $title, $type );
	}

	public function translate_package_string( array $package, string $name, string $value ): string {
		$this->package_calls[] = compact( 'package', 'name', 'value' );
		return '' !== $this->translate_returns ? $this->translate_returns : $value;
	}
}

/**
 * Stub provider that returns "DE:" prefixed values, used to make translation
 * substitution visible in tests that round-trip block content through
 * parse_blocks/serialize_blocks.
 */
class Srfm_String_Translator_Translating_Stub implements \SRFM\Inc\Compatibility\Multilingual\Providers\Provider {
	public function is_active(): bool {
		return true;
	}

	public function current_language(): string {
		return 'de';
	}

	public function default_language(): string {
		return 'en';
	}

	public function register_string( string $name, string $value, string $domain = 'sureforms' ): void {
		unset( $name, $value, $domain );
	}

	public function translate( string $value, string $name, string $domain = 'sureforms', ?string $language = null ): string {
		unset( $name, $domain, $language );
		return 'DE:' . $value;
	}

	public function switch_language( string $language ): void {
		unset( $language );
	}

	public function restore_language(): void {
		// No-op.
	}

	public function render_language_switcher(): string {
		return '';
	}

	public function supports_packages(): bool {
		// Use the flat translate() path so the "DE:" prefix substitution stays visible.
		return false;
	}

	public function start_package( array $package ): void {
		unset( $package );
	}

	public function finish_package( array $package ): void {
		unset( $package );
	}

	public function register_package_string( array $package, string $name, string $value, string $title = '', string $type = 'LINE' ): void {
		unset( $package, $name, $value, $title, $type );
	}

	public function translate_package_string( array $package, string $name, string $value ): string {
		unset( $package, $name );
		return 'DE:' . $value;
	}
}

/**
 * Tests for String_Translator.
 */
class Test_String_Translator extends TestCase {

	/**
	 * Stub provider injected via the srfm_multilingual_provider filter.
	 *
	 * @var Srfm_String_Translator_Stub_Provider
	 */
	private $stub;

	/**
	 * Filter callback used to inject the stub. Stored so tearDown() can remove it.
	 *
	 * @var callable|null
	 */
	private $filter;

	protected function setUp(): void {
		parent::setUp();

		// Reset singleton caches so the filter applies to a fresh manager instance.
		$this->reset_singleton( \SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager::class );
		$this->reset_singleton( String_Translator::class );

		$this->stub   = new Srfm_String_Translator_Stub_Provider();
		$stub         = $this->stub;
		$this->filter = static function () use ( $stub ) {
			return $stub;
		};
		add_filter( 'srfm_multilingual_provider', $this->filter, 10, 1 );
	}

	protected function tearDown(): void {
		if ( null !== $this->filter ) {
			remove_filter( 'srfm_multilingual_provider', $this->filter, 10 );
			$this->filter = null;
		}

		// Clear singleton caches so we don't leak the stub into other tests.
		$this->reset_singleton( \SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager::class );
		$this->reset_singleton( String_Translator::class );

		parent::tearDown();
	}

	/**
	 * Reset a singleton's cached instance via reflection.
	 *
	 * @param string $fqcn Fully qualified class name using the Get_Instance trait.
	 */
	private function reset_singleton( string $fqcn ): void {
		if ( ! class_exists( $fqcn ) ) {
			return;
		}

		$ref = new \ReflectionClass( $fqcn );
		if ( $ref->hasProperty( 'instance' ) ) {
			$prop = $ref->getProperty( 'instance' );
			$prop->setAccessible( true );
			$prop->setValue( null, null );
		}
	}

	public function test_translate_submit_button_uses_correct_name() {
		$result = String_Translator::get_instance()->translate_submit_button( 42, 'Send' );

		$this->assertCount( 1, $this->stub->calls );
		$this->assertSame( 'form_42_submit_button', $this->stub->calls[0]['name'] );
		$this->assertSame( 'Send', $this->stub->calls[0]['value'] );
		$this->assertSame( 'sureforms', $this->stub->calls[0]['domain'] );
		$this->assertSame( 'Send', $result );
	}

	public function test_translate_submit_button_returns_original_when_empty() {
		$result = String_Translator::get_instance()->translate_submit_button( 42, '' );

		$this->assertCount( 0, $this->stub->calls );
		$this->assertSame( '', $result );
	}

	public function test_translate_confirmation_message_includes_index() {
		$result = String_Translator::get_instance()->translate_confirmation_message( 42, 1, 'Thanks' );

		$this->assertCount( 1, $this->stub->calls );
		$this->assertSame( 'form_42_confirmation_1_message', $this->stub->calls[0]['name'] );
		$this->assertSame( 'Thanks', $this->stub->calls[0]['value'] );
		$this->assertSame( 'sureforms', $this->stub->calls[0]['domain'] );
		$this->assertSame( 'Thanks', $result );
	}

	public function test_translate_notification_subject_uses_correct_name() {
		String_Translator::get_instance()->translate_notification_subject( 42, 0, 'Subject line' );

		$this->assertCount( 1, $this->stub->calls );
		$this->assertSame( 'form_42_notification_0_subject', $this->stub->calls[0]['name'] );
		$this->assertSame( 'Subject line', $this->stub->calls[0]['value'] );
		$this->assertSame( 'sureforms', $this->stub->calls[0]['domain'] );
	}

	public function test_translate_notification_body_uses_correct_name() {
		String_Translator::get_instance()->translate_notification_body( 42, 0, 'Email body' );

		$this->assertCount( 1, $this->stub->calls );
		$this->assertSame( 'form_42_notification_0_body', $this->stub->calls[0]['name'] );
		$this->assertSame( 'Email body', $this->stub->calls[0]['value'] );
		$this->assertSame( 'sureforms', $this->stub->calls[0]['domain'] );
	}

	public function test_translate_notification_from_name_uses_correct_name() {
		String_Translator::get_instance()->translate_notification_from_name( 42, 0, 'Admin' );

		$this->assertCount( 1, $this->stub->calls );
		$this->assertSame( 'form_42_notification_0_from_name', $this->stub->calls[0]['name'] );
		$this->assertSame( 'Admin', $this->stub->calls[0]['value'] );
		$this->assertSame( 'sureforms', $this->stub->calls[0]['domain'] );
	}

	public function test_translate_restriction_message_uses_correct_name() {
		String_Translator::get_instance()->translate_restriction_message( 42, 'Form closed' );

		$this->assertCount( 1, $this->stub->calls );
		$this->assertSame( 'form_42_restriction_message', $this->stub->calls[0]['name'] );
		$this->assertSame( 'Form closed', $this->stub->calls[0]['value'] );
		$this->assertSame( 'sureforms', $this->stub->calls[0]['domain'] );
	}

	public function test_translate_returns_provider_result() {
		$this->stub->translate_returns = 'DE-Send';

		$result = String_Translator::get_instance()->translate_submit_button( 42, 'Send' );

		$this->assertSame( 'DE-Send', $result );
		$this->assertCount( 1, $this->stub->calls );
		$this->assertSame( 'Send', $this->stub->calls[0]['value'] );
	}

	public function test_get_instance_returns_singleton() {
		$a = String_Translator::get_instance();
		$b = String_Translator::get_instance();

		$this->assertSame( $a, $b );
		$this->assertInstanceOf( String_Translator::class, $a );
	}

	public function test_translate_validation_message_uses_validation_prefix_in_name() {
		$result = String_Translator::get_instance()->translate_validation_message( 'srfm_valid_email', 'Enter a valid email address.' );

		$this->assertCount( 1, $this->stub->calls );
		$this->assertSame( 'validation_srfm_valid_email', $this->stub->calls[0]['name'] );
		$this->assertSame( 'Enter a valid email address.', $this->stub->calls[0]['value'] );
		$this->assertSame( 'sureforms', $this->stub->calls[0]['domain'] );
		$this->assertSame( 'Enter a valid email address.', $result );
	}

	public function test_translate_validation_message_returns_input_when_key_or_value_empty() {
		$translator = String_Translator::get_instance();

		$this->assertSame( 'fallback', $translator->translate_validation_message( '', 'fallback' ) );
		$this->assertSame( '', $translator->translate_validation_message( 'k', '' ) );
		$this->assertCount( 0, $this->stub->calls );
	}

	public function test_translate_validation_messages_preserves_keys_and_translates_each() {
		$input  = [
			'srfm_valid_email' => 'Enter a valid email address.',
			'srfm_valid_url'   => 'Enter a valid URL.',
		];
		$result = String_Translator::get_instance()->translate_validation_messages( $input );

		$this->assertSame( array_keys( $input ), array_keys( $result ) );
		$this->assertCount( 2, $this->stub->calls );
		$this->assertSame( 'validation_srfm_valid_email', $this->stub->calls[0]['name'] );
		$this->assertSame( 'validation_srfm_valid_url', $this->stub->calls[1]['name'] );
	}

	public function test_translate_validation_messages_passes_through_non_string_values() {
		$input  = [
			'ok'   => 'Hello',
			'bad'  => 12345, // non-string — should be left alone.
			0      => 'numeric-key', // numeric key — should still be processed (key is_string check passes via type juggling on cast).
		];
		$result = String_Translator::get_instance()->translate_validation_messages( $input );

		$this->assertSame( 12345, $result['bad'] );
		$this->assertArrayHasKey( 'ok', $result );
	}

	public function test_translatable_block_attributes_covers_known_field_blocks() {
		$map = String_Translator::translatable_block_attributes();

		// Spot-check a few representative blocks; the map must declare scalar
		// attrs for at least input, dropdown, multi-choice, and inline-button.
		$this->assertArrayHasKey( 'srfm/input', $map );
		$this->assertContains( 'label', $map['srfm/input'] );
		$this->assertContains( 'placeholder', $map['srfm/input'] );
		$this->assertArrayHasKey( 'srfm/dropdown', $map );
		$this->assertContains( 'label', $map['srfm/dropdown'] );
		$this->assertArrayHasKey( 'srfm/multi-choice', $map );
		$this->assertArrayHasKey( 'srfm/inline-button', $map );
		$this->assertContains( 'buttonText', $map['srfm/inline-button'] );
	}

	public function test_translatable_option_blocks_lists_dropdown_and_multi_choice() {
		$blocks = String_Translator::translatable_option_blocks();

		$this->assertContains( 'srfm/dropdown', $blocks );
		$this->assertContains( 'srfm/multi-choice', $blocks );
	}

	public function test_translate_block_attribute_uses_correct_name() {
		$result = String_Translator::get_instance()->translate_block_attribute( 42, 'abc123', 'label', 'Your Name' );

		$this->assertCount( 1, $this->stub->calls );
		$this->assertSame( 'form_42_block_abc123_label', $this->stub->calls[0]['name'] );
		$this->assertSame( 'Your Name', $this->stub->calls[0]['value'] );
		$this->assertSame( 'sureforms', $this->stub->calls[0]['domain'] );
		$this->assertSame( 'Your Name', $result );
	}

	public function test_translate_block_attribute_returns_original_when_inputs_missing() {
		$translator = String_Translator::get_instance();

		$this->assertSame( '', $translator->translate_block_attribute( 1, 'b', 'label', '' ) );
		$this->assertSame( 'x', $translator->translate_block_attribute( 1, '', 'label', 'x' ) );
		$this->assertSame( 'x', $translator->translate_block_attribute( 1, 'b', '', 'x' ) );
		$this->assertCount( 0, $this->stub->calls );
	}

	public function test_translate_block_option_label_uses_correct_name() {
		$result = String_Translator::get_instance()->translate_block_option_label( 42, 'abc123', 2, 'Friend' );

		$this->assertCount( 1, $this->stub->calls );
		$this->assertSame( 'form_42_block_abc123_option_2_label', $this->stub->calls[0]['name'] );
		$this->assertSame( 'Friend', $this->stub->calls[0]['value'] );
		$this->assertSame( 'Friend', $result );
	}

	public function test_translate_block_option_label_returns_original_when_inputs_missing() {
		$translator = String_Translator::get_instance();

		$this->assertSame( '', $translator->translate_block_option_label( 1, 'b', 0, '' ) );
		$this->assertSame( 'Friend', $translator->translate_block_option_label( 1, '', 0, 'Friend' ) );
		$this->assertCount( 0, $this->stub->calls );
	}

	public function test_translate_form_content_translates_label_and_option_labels() {
		// Stub returns "DE:<original>" so substitution is visible in the output.
		$this->stub->translate_returns = '';
		$translating_stub              = new Srfm_String_Translator_Translating_Stub();
		add_filter(
			'srfm_multilingual_provider',
			static function () use ( $translating_stub ) {
				return $translating_stub;
			},
			20
		);
		$this->reset_singleton( \SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager::class );
		$this->reset_singleton( String_Translator::class );

		$content = '<!-- wp:srfm/input {"block_id":"abc123","label":"Your Name","placeholder":"Enter"} /-->'
			. '<!-- wp:srfm/dropdown {"block_id":"def456","label":"Hear","options":[{"label":"Friend"},{"label":"Google"}]} /-->';

		$out = String_Translator::get_instance()->translate_form_content( 42, $content );

		$this->assertStringContainsString( '"label":"DE:Your Name"', $out );
		$this->assertStringContainsString( '"placeholder":"DE:Enter"', $out );
		$this->assertStringContainsString( '"label":"DE:Friend"', $out );
		$this->assertStringContainsString( '"label":"DE:Google"', $out );

		remove_all_filters( 'srfm_multilingual_provider' );
	}

	public function test_translate_form_content_noops_when_provider_inactive() {
		// Replace stub with an inactive provider.
		remove_filter( 'srfm_multilingual_provider', $this->filter, 10 );
		$this->filter = null;
		$this->reset_singleton( \SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager::class );
		$this->reset_singleton( String_Translator::class );

		$content = '<!-- wp:srfm/input {"block_id":"abc","label":"X"} /-->';
		$out     = String_Translator::get_instance()->translate_form_content( 42, $content );

		$this->assertSame( $content, $out );
	}

	public function test_translate_form_content_with_blocks_returns_markup_and_parsed_blocks() {
		$translating_stub = new Srfm_String_Translator_Translating_Stub();
		add_filter(
			'srfm_multilingual_provider',
			static function () use ( $translating_stub ) {
				return $translating_stub;
			},
			20
		);
		$this->reset_singleton( \SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager::class );
		$this->reset_singleton( String_Translator::class );

		// Two top-level blocks. Unique content keeps the per-content cache key isolated.
		$content = '<!-- wp:srfm/input {"block_id":"wb1","label":"With Blocks A"} /-->'
			. '<!-- wp:srfm/input {"block_id":"wb2","label":"With Blocks B"} /-->';

		[ $markup, $blocks ] = String_Translator::get_instance()->translate_form_content_with_blocks( 4242, $content );

		$this->assertStringContainsString( '"label":"DE:With Blocks A"', $markup );
		$this->assertIsArray( $blocks );
		// Top-level block count is returned for the render path to reuse (no re-parse).
		$this->assertCount( 2, $blocks );

		remove_all_filters( 'srfm_multilingual_provider' );
	}

	public function test_translate_form_content_with_blocks_passthrough_when_inactive() {
		// Remove the active stub so the resolved provider is inactive.
		remove_filter( 'srfm_multilingual_provider', $this->filter, 10 );
		$this->filter = null;
		$this->reset_singleton( \SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager::class );
		$this->reset_singleton( String_Translator::class );

		$content = '<!-- wp:srfm/input {"block_id":"pt1","label":"X"} /-->';

		[ $markup, $blocks ] = String_Translator::get_instance()->translate_form_content_with_blocks( 4243, $content );

		// Pure pass-through: markup unchanged and no parsed blocks returned (render path parses once itself).
		$this->assertSame( $content, $markup );
		$this->assertSame( [], $blocks );
	}

	public function test_translatable_block_attributes_filter_extends_and_falls_back() {
		// Filter can add a Pro block to the map.
		add_filter(
			'srfm_translatable_block_attributes',
			static function ( $map ) {
				$map['srfm/pro-field'] = [ 'label', 'help' ];
				return $map;
			}
		);
		$map = String_Translator::translatable_block_attributes();
		$this->assertArrayHasKey( 'srfm/pro-field', $map );
		$this->assertArrayHasKey( 'srfm/input', $map, 'Core entries remain.' );
		remove_all_filters( 'srfm_translatable_block_attributes' );

		// A non-array filter return falls back to the core map (no fatal).
		add_filter( 'srfm_translatable_block_attributes', '__return_false' );
		$fallback = String_Translator::translatable_block_attributes();
		$this->assertIsArray( $fallback );
		$this->assertArrayHasKey( 'srfm/input', $fallback );
		remove_all_filters( 'srfm_translatable_block_attributes' );
	}

	public function test_translatable_option_blocks_filter_extends_and_falls_back() {
		add_filter(
			'srfm_translatable_option_blocks',
			static function ( $blocks ) {
				$blocks[] = 'srfm/pro-rating';
				return $blocks;
			}
		);
		$blocks = String_Translator::translatable_option_blocks();
		$this->assertContains( 'srfm/pro-rating', $blocks );
		$this->assertContains( 'srfm/dropdown', $blocks, 'Core entries remain.' );
		remove_all_filters( 'srfm_translatable_option_blocks' );

		add_filter( 'srfm_translatable_option_blocks', '__return_null' );
		$fallback = String_Translator::translatable_option_blocks();
		$this->assertIsArray( $fallback );
		$this->assertContains( 'srfm/dropdown', $fallback );
		remove_all_filters( 'srfm_translatable_option_blocks' );
	}

	public function test_translatable_maps_stay_consistent() {
		$attribute_map = String_Translator::translatable_block_attributes();
		$option_blocks = String_Translator::translatable_option_blocks();

		// Every block name in either map must be a SureForms (`srfm/`) block.
		foreach ( array_keys( $attribute_map ) as $block_name ) {
			$this->assertStringStartsWith( 'srfm/', $block_name );
		}

		// Drift guard: any block that declares translatable option labels must also
		// appear in the attribute map, so the collector and translator never disagree
		// about which blocks they walk. Catches a new option block added to one map
		// but forgotten in the other.
		foreach ( $option_blocks as $block_name ) {
			$this->assertArrayHasKey(
				$block_name,
				$attribute_map,
				"Option block {$block_name} must also be present in translatable_block_attributes()."
			);
		}
	}

	public function test_translate_blocks_recursive_walks_inner_blocks() {
		$translator = String_Translator::get_instance();
		$reflection = new \ReflectionMethod( $translator, 'translate_blocks_recursive' );
		$reflection->setAccessible( true );

		$blocks = [
			[
				'blockName'   => 'core/group',
				'attrs'       => [],
				'innerBlocks' => [
					[
						'blockName'   => 'srfm/input',
						'attrs'       => [
							'block_id' => 'inner1',
							'label'    => 'Nested Label',
						],
						'innerBlocks' => [],
					],
				],
			],
		];

		$result = $reflection->invoke( $translator, 42, $blocks );

		$this->assertCount( 1, $this->stub->calls );
		$this->assertSame( 'form_42_block_inner1_label', $this->stub->calls[0]['name'] );
		$this->assertSame( 'Nested Label', $this->stub->calls[0]['value'] );
		// Returned structure shape preserved.
		$this->assertSame( 'core/group', $result[0]['blockName'] );
		$this->assertSame( 'Nested Label', $result[0]['innerBlocks'][0]['attrs']['label'] );
	}

	/**
	 * When the provider supports packages, per-form strings route through the
	 * form's String Package (unscoped name + form package descriptor) rather than
	 * the flat translate() path.
	 */
	public function test_per_form_strings_route_through_package_when_supported() {
		$this->stub->supports_packages = true;

		$result = String_Translator::get_instance()->translate_submit_button( 42, 'Send' );

		$this->assertCount( 0, $this->stub->calls, 'Flat translate() must not be used on the package path.' );
		$this->assertCount( 1, $this->stub->package_calls );

		$call = $this->stub->package_calls[0];
		$this->assertSame( String_Translator::submit_button_name(), $call['name'] );
		$this->assertSame( 'Send', $call['value'] );
		$this->assertSame( String_Translator::PACKAGE_KIND, $call['package']['kind'] );
		$this->assertSame( '42', $call['package']['name'] );
		$this->assertSame( 'Send', $result );
	}

	/**
	 * A block attribute also routes through the package with its unscoped name.
	 */
	public function test_block_attribute_routes_through_package_when_supported() {
		$this->stub->supports_packages = true;

		String_Translator::get_instance()->translate_block_attribute( 42, 'abc123', 'label', 'Name' );

		$this->assertCount( 1, $this->stub->package_calls );
		$this->assertSame( String_Translator::block_attribute_name( 'abc123', 'label' ), $this->stub->package_calls[0]['name'] );
		$this->assertSame( '42', $this->stub->package_calls[0]['package']['name'] );
	}

	public function test_block_type_label() {
		// Curated label from the built-in map.
		$this->assertSame( 'Email', String_Translator::block_type_label( 'srfm/email' ) );

		// Unknown block derives a Title-Cased label from the slug.
		$this->assertSame( 'Date Time Picker', String_Translator::block_type_label( 'srfm/date-time-picker' ) );
	}

	public function test_form_package() {
		$package = String_Translator::form_package( 99 );

		$this->assertArrayHasKey( 'kind', $package );
		$this->assertArrayHasKey( 'name', $package );
		$this->assertArrayHasKey( 'title', $package );
		$this->assertSame( 'SureForms Form', $package['kind'] );
		$this->assertSame( String_Translator::PACKAGE_KIND, $package['kind'] );
		$this->assertSame( '99', $package['name'] );
	}

	public function test_submit_button_name() {
		$this->assertSame( 'submit_button', String_Translator::submit_button_name() );
	}

	public function test_confirmation_name() {
		$this->assertSame( 'confirmation_0_message', String_Translator::confirmation_name( 0 ) );
		$this->assertSame( 'confirmation_3_message', String_Translator::confirmation_name( 3 ) );
	}

	public function test_notification_name() {
		$this->assertSame( 'notification_0_subject', String_Translator::notification_name( 0, 'subject' ) );
		$this->assertSame( 'notification_2_body', String_Translator::notification_name( 2, 'body' ) );
	}

	public function test_restriction_name() {
		$this->assertSame( 'restriction_message', String_Translator::restriction_name() );
	}

	public function test_block_attribute_name() {
		$this->assertSame( 'block_abc123_label', String_Translator::block_attribute_name( 'abc123', 'label' ) );
		$this->assertSame( 'block_def456_placeholder', String_Translator::block_attribute_name( 'def456', 'placeholder' ) );
	}

	public function test_block_option_name() {
		$this->assertSame( 'block_abc123_option_0_label', String_Translator::block_option_name( 'abc123', 0 ) );
		$this->assertSame( 'block_def456_option_2_label', String_Translator::block_option_name( 'def456', 2 ) );
	}
}
