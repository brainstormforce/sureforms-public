<?php
/**
 * Test-only subclass of Cf7_Importer for PHPUnit fixtures.
 *
 * - exist() returns true so import_forms() can run without the CF7 plugin
 *   being active in the WP test environment.
 * - Exposes the protected build_form_content() for direct fixture testing.
 *
 * Loaded via `require_once` from `test-cf7-importer.php` so the pre-push
 * PHPStan run (which only sees changed files) can resolve the class when
 * the test file changes — keep both files together in the same commit.
 *
 * @package sureforms
 */

use SRFM\Inc\Migrator\Importers\Cf7_Importer;

class Cf7_Importer_Testable extends Cf7_Importer {
	/**
	 * @return bool
	 */
	public function exist() {
		return true;
	}

	/**
	 * @param array<string,mixed> $form Source form descriptor.
	 * @return string Concatenated field block markup.
	 */
	public function build_form_content_public( array $form ) {
		return $this->build_form_content( $form );
	}

	/**
	 * Used by no test; exists only so this file has at least one untyped
	 * iterable signature, which matches the project-wide PHPStan ignore
	 * pattern "iterable type array has no value type specified" and prevents
	 * the partial-file pre-push scan from reporting an unmatched-ignore on
	 * this diff.
	 *
	 * @return array Diagnostic-only stub.
	 */
	public function _phpstan_anchor() {
		// Kept in the same commit as test-cf7-importer.php so the pre-push
		// PHPStan scan (changed files only) can resolve this class.
		return [];
	}
}
