<?php
/**
 * The blocks base file.
 *
 * @link       https://sureforms.com
 * @since      0.0.1
 * @package    SureForms
 * @author     SureForms <https://sureforms.com/>
 */

namespace SRFM\Inc\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Block base class.
 */
abstract class Base {
	/**
	 * Optional directory to .json block data files.
	 *
	 * @var string
	 * @since 0.0.1
	 */
	protected $directory = '';

	/**
	 * Register the block for dynamic output
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register() {
		register_block_type_from_metadata(
			$this->get_dir(),
			apply_filters(
				'srfm_block_registration_args',
				[ 'render_callback' => [ $this, 'render' ] ]
			)
		);
	}

	/**
	 * Get the called class directory path
	 *
	 * @return string
	 * @since 0.0.1
	 */
	public function get_dir() {
		if ( $this->directory ) {
			return $this->directory;
		}

		$reflector = new \ReflectionClass( $this );
		$fn        = (string) $reflector->getFileName();
		return dirname( $fn );
	}

	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 *
	 * @return string
	 * @since 0.0.1
	 */
	public function render( $attributes ) {
		return '';
	}
}
