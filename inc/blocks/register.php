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
 * @since 0.0.1
 */
class Register {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  0.0.1
	 */
	public function __construct() {
		$namespace  = 'SureForms\\Inc\\Blocks';
		$blocks_dir = glob( SUREFORMS_DIR . 'inc/blocks/**/*.php' );
		$this->register_block( $blocks_dir, $namespace );

		if ( defined( 'SUREFORMS_PRO_VER' ) ) {
			$blocks_dir = glob( SUREFORMS_PRO_DIR . 'inc/blocks/**/*.php' );
			$namespace  = 'SureForms_Pro\\Inc\\Blocks';
			$this->register_block( $blocks_dir, $namespace );
		}
	}

	/**
	 * Register Blocks
	 *
	 * @param string $blocks_dir Block directory.
	 * @param string $namespace Namespace.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public static function register_block( $blocks_dir, $namespace ) {
		if ( ! empty( $blocks_dir ) ) {
			foreach ( $blocks_dir as $filename ) {
				// Include the file.
				require_once $filename;
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
