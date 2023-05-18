<?php
/**
 * Call block registration.
 *
 * @package SureForms
 */

namespace SureForms\Inc\Blocks;

use SureForms\Inc\Traits\Get_Instance;

/**
 * Manage Blocks registrations.
 *
 * @since X.X.X
 */
class Register {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  X.X.X
	 */
	public function __construct() {
		$blocks_dir = glob( SUREFORMS_DIR . 'inc/blocks/**/*.php' );
		if ( ! empty( $blocks_dir ) ) {
			foreach ( $blocks_dir as $filename ) {
				// Include the file.
				require_once $filename;

				$namespace       = 'SureForms\\Inc\\Blocks';
				$classname       = ucfirst( basename( dirname( $filename ) ) );
				$full_class_name = $namespace . '\\' . $classname . '\\Block';
				// Check if the class exists.
				if ( class_exists( $full_class_name ) ) {
					$block = new $full_class_name();
					// Check if the register method exists.
					if ( method_exists( $block, 'register' ) ) {
						// Call register on the block object.
						$block->register();
					}
				}
			}
		}
	}
}
