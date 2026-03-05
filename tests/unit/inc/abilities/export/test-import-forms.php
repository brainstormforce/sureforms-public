<?php
/**
 * Class Test_Import_Forms
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Abilities\Export\Import_Forms;

/**
 * Tests Import_Forms ability.
 */
class Test_Import_Forms extends TestCase {

	/**
	 * The ability instance.
	 *
	 * @var Import_Forms
	 */
	protected $ability;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		$this->ability = new Import_Forms();
	}

	/**
	 * Test that the required capability is manage_options.
	 */
	public function test_capability_is_manage_options() {
		$reflection = new \ReflectionProperty( $this->ability, 'capability' );
		$reflection->setAccessible( true );

		$this->assertEquals( 'manage_options', $reflection->getValue( $this->ability ) );
	}

	/**
	 * Test MAX_IMPORT_FORMS constant exists and has expected value.
	 */
	public function test_max_import_forms_constant() {
		$this->assertEquals( 50, Import_Forms::MAX_IMPORT_FORMS );
	}

	/**
	 * Test execute returns error when too many forms are provided.
	 */
	public function test_execute_too_many_forms_returns_error() {
		$forms_data = [];
		for ( $i = 0; $i < 51; $i++ ) {
			$forms_data[] = [
				'post'      => [
					'post_title'   => 'Form ' . $i,
					'post_content' => '',
					'post_type'    => 'sureforms_form',
				],
				'post_meta' => [],
			];
		}

		$result = $this->ability->execute( [ 'forms_data' => $forms_data ] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'srfm_too_many_forms', $result->get_error_code() );
	}

	/**
	 * Test execute returns error for invalid forms data structure.
	 */
	public function test_execute_invalid_forms_data_returns_error() {
		// Missing forms_data entirely.
		$result = $this->ability->execute( [] );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'srfm_missing_forms_data', $result->get_error_code() );

		// Invalid structure — missing post_meta key.
		$result = $this->ability->execute(
			[
				'forms_data' => [
					[ 'post' => [ 'post_title' => 'Test' ] ],
				],
			]
		);
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'srfm_invalid_form_data', $result->get_error_code() );
	}
}
