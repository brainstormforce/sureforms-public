<?php
/**
 * Class Test_Block_Templates
 *
 * Smoke tests for each Block_Templates emitter — every public method must
 * return a Gutenberg block-comment string for the corresponding srfm/* block.
 * Function names follow the project's coverage-naming convention so the
 * `check-test-coverage` action recognises them as covering each method.
 *
 * @package sureforms
 */

use SRFM\Inc\Migrator\Block_Templates;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Block_Templates extends TestCase {

	/**
	 * @return void
	 */
	public function test_input() {
		$markup = Block_Templates::input( [ 'label' => 'Name', 'required' => true ] );
		$this->assertStringContainsString( 'wp:srfm/input', $markup );
		$this->assertStringContainsString( '"label":"Name"', $markup );
		$this->assertStringContainsString( '"required":true', $markup );
	}

	/**
	 * @return void
	 */
	public function test_email() {
		$markup = Block_Templates::email( [ 'label' => 'Email' ] );
		$this->assertStringContainsString( 'wp:srfm/email', $markup );
		$this->assertStringContainsString( '"label":"Email"', $markup );
	}

	/**
	 * @return void
	 */
	public function test_url() {
		$markup = Block_Templates::url( [ 'label' => 'Website' ] );
		$this->assertStringContainsString( 'wp:srfm/url', $markup );
		$this->assertStringContainsString( '"label":"Website"', $markup );
	}

	/**
	 * @return void
	 */
	public function test_phone() {
		$markup = Block_Templates::phone( [ 'label' => 'Phone' ] );
		$this->assertStringContainsString( 'wp:srfm/phone', $markup );
		$this->assertStringContainsString( '"label":"Phone"', $markup );
	}

	/**
	 * @return void
	 */
	public function test_number() {
		$markup = Block_Templates::number( [ 'label' => 'Age', 'min' => 1, 'max' => 99 ] );
		$this->assertStringContainsString( 'wp:srfm/number', $markup );
		$this->assertStringContainsString( '"minValue":1', $markup );
		$this->assertStringContainsString( '"maxValue":99', $markup );
	}

	/**
	 * @return void
	 */
	public function test_textarea() {
		$markup = Block_Templates::textarea( [ 'label' => 'Notes', 'max_length' => 200 ] );
		$this->assertStringContainsString( 'wp:srfm/textarea', $markup );
		$this->assertStringContainsString( '"label":"Notes"', $markup );
	}

	/**
	 * @return void
	 */
	public function test_dropdown() {
		$markup = Block_Templates::dropdown(
			[ 'label' => 'Country', 'options' => [ 'USA', 'Canada' ] ]
		);
		$this->assertStringContainsString( 'wp:srfm/dropdown', $markup );
		$this->assertStringContainsString( 'USA', $markup );
		$this->assertStringContainsString( 'Canada', $markup );
	}

	/**
	 * @return void
	 */
	public function test_multi_choice() {
		$markup = Block_Templates::multi_choice(
			[ 'label' => 'Gender', 'options' => [ 'Male', 'Female' ], 'multiple' => false ]
		);
		$this->assertStringContainsString( 'wp:srfm/multi-choice', $markup );
		$this->assertStringContainsString( '"singleSelection":true', $markup );
		$this->assertStringContainsString( 'Male', $markup );
	}

	/**
	 * @return void
	 */
	public function test_checkbox() {
		$markup = Block_Templates::checkbox( [ 'label' => 'I agree' ] );
		$this->assertStringContainsString( 'wp:srfm/checkbox', $markup );
		$this->assertStringContainsString( '"label":"I agree"', $markup );
	}

	/**
	 * @return void
	 */
	public function test_gdpr() {
		$markup = Block_Templates::gdpr( [ 'label' => 'Consent to processing' ] );
		$this->assertStringContainsString( 'wp:srfm/gdpr', $markup );
		$this->assertStringContainsString( 'Consent to processing', $markup );
	}

	/**
	 * @return void
	 */
	public function test_block_id() {
		$id1 = Block_Templates::block_id();
		$id2 = Block_Templates::block_id();
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{8}$/', $id1 );
		// Two consecutive calls should produce distinct ids — the function is
		// the source of uniqueness for emitted block markers.
		$this->assertNotSame( $id1, $id2 );
	}
}
