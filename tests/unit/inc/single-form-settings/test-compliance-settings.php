<?php
/**
 * Class Test_Compliance_Settings
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Single_Form_Settings\Compliance_Settings;

class Test_Compliance_Settings extends TestCase {

	/**
	 * Test pre_auto_delete_entries does not throw warnings when compliance meta is empty array.
	 */
	public function test_pre_auto_delete_entries_with_empty_compliance_meta() {
		// Create a form post.
		$form_id = $this->factory()->post->create( [ 'post_type' => 'sureforms_form' ] );

		// Set compliance meta to an empty array (missing key 0).
		update_post_meta( $form_id, '_srfm_compliance', [] );

		$compliance = Compliance_Settings::get_instance();

		// Should not produce any PHP warnings.
		$compliance->pre_auto_delete_entries();

		// If we reach here without warnings/errors, the test passes.
		$this->assertTrue( true );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Test pre_auto_delete_entries does not throw warnings when compliance meta is not set.
	 */
	public function test_pre_auto_delete_entries_with_no_compliance_meta() {
		$form_id = $this->factory()->post->create( [ 'post_type' => 'sureforms_form' ] );

		// Do not set any compliance meta.
		$compliance = Compliance_Settings::get_instance();

		// Should not produce any PHP warnings.
		$compliance->pre_auto_delete_entries();

		$this->assertTrue( true );

		wp_delete_post( $form_id, true );
	}

	/**
	 * Test pre_auto_delete_entries works correctly with valid compliance meta.
	 */
	public function test_pre_auto_delete_entries_with_valid_compliance_meta() {
		$form_id = $this->factory()->post->create( [ 'post_type' => 'sureforms_form' ] );

		// Set valid compliance meta structure.
		update_post_meta(
			$form_id,
			'_srfm_compliance',
			[
				[
					'id'                   => 'gdpr',
					'gdpr'                 => false,
					'do_not_store_entries' => false,
					'auto_delete_entries'  => false,
					'auto_delete_days'     => '',
				],
			]
		);

		$compliance = Compliance_Settings::get_instance();

		// Should not produce any PHP warnings.
		$compliance->pre_auto_delete_entries();

		$this->assertTrue( true );

		wp_delete_post( $form_id, true );
	}
}
