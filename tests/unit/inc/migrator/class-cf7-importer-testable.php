<?php
/**
 * Test-only subclass of Cf7_Importer for PHPUnit fixtures.
 *
 * - exist() returns true so import_forms() can run without the CF7 plugin
 *   being active in the WP test environment.
 * - Exposes the protected build_form_content() for direct fixture testing.
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
}
