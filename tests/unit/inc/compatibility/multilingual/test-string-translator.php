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
}
