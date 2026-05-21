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

	public function test_translate_notification_reply_to_uses_correct_name() {
		String_Translator::get_instance()->translate_notification_reply_to( 42, 0, 'reply@example.com' );

		$this->assertCount( 1, $this->stub->calls );
		$this->assertSame( 'form_42_notification_0_reply_to', $this->stub->calls[0]['name'] );
		$this->assertSame( 'reply@example.com', $this->stub->calls[0]['value'] );
		$this->assertSame( 'sureforms', $this->stub->calls[0]['domain'] );
	}

	public function test_translate_compliance_message_uses_correct_name() {
		String_Translator::get_instance()->translate_compliance_message( 42, 0, 'I agree' );

		$this->assertCount( 1, $this->stub->calls );
		$this->assertSame( 'form_42_compliance_0_message', $this->stub->calls[0]['name'] );
		$this->assertSame( 'I agree', $this->stub->calls[0]['value'] );
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
}
