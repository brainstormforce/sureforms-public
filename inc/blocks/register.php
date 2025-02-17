<?php
/**
 * Call block registration.
 *
 * @package SureForms
 */

namespace SRFM\Inc\Blocks;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
		$blocks = [
			[
				'dir'       => SRFM_DIR . 'inc/blocks/**/*.php',
				'namespace' => 'SRFM\\Inc\\Blocks',
			],
		];

		if ( defined( 'SRFM_PRO_VER' ) ) {
			$blocks[] = [
				'dir'       => SRFM_PRO_DIR . 'inc/blocks/**/*.php',
				'namespace' => 'SRFM_PRO\\Inc\\Blocks',
			];
		}

		$blocks = apply_filters( 'srfm_registered_blocks', $blocks );

		foreach ( $blocks as $block ) {
			$this->register_block( glob( $block['dir'] ), $block['namespace'], 'Block' );
		}
	}

	/**
	 * Register Blocks
	 *
	 * @param array<int, string>|false $blocks_dir Block directory.
	 * @param string                   $namespace Namespace.
	 * @param string                   $base Base.
	 * @return void
	 * @since 0.0.1
	 */
	public static function register_block( $blocks_dir, $namespace, $base ) {
		if ( ! empty( $blocks_dir ) ) {
			foreach ( $blocks_dir as $filename ) {
				// Include the file.
				require_once $filename;

				// Replace hyphens with underscores.
				$classname = str_replace( '-', '_', basename( dirname( $filename ) ) );

				// Convert to title case (capitalizes the first letter of each word).
				$classname = ucwords( $classname, '_' );

				$full_class_name = $namespace . '\\' . $classname . '\\' . $base;

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
