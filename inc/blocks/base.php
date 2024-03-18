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
	 * Holds the block.
	 *
	 * @var object
	 * @since 0.0.1
	 */
	protected $block;

	/**
	 * Register the block for dynamic output
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function register() {
		$dir = $this->get_dir();
		register_block_type_from_metadata(
			$this->get_dir(),
			apply_filters(
				'srfm_block_registration_args',
				[ 'render_callback' => [ $this, 'pre_render' ] ]
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
	 * Optionally run a function to modify attributes before rendering.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content   Post content.
	 * @param array<mixed> $block Block attributes.
	 *
	 * @return boolean|string
	 * @since 0.0.1
	 */
	public function pre_render( $attributes, $content, $block ) {
		$this->block = (object) $block;

		// run middlware.
		$render = $this->middleware( $attributes, $content );

		if ( is_wp_error( $render ) ) {
			return $render->get_error_message();
		}

		if ( true !== $render ) {
			return $render;
		}

		/** $attributes = $this->getAttributes( $attributes ); */

		// render.
		return $this->render( $attributes, $content );
	}

	/**
	 * Run any block middleware before rendering.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content   Post content.
	 * @return boolean|\WP_Error;
	 * @since 0.0.1
	 */
	protected function middleware( $attributes, $content ) {
		return true;
	}

	/**
	 * Allows filtering of attributes before rendering.
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @return array<mixed> $attributes
	 * @since 0.0.1
	 */
	public function get_attributes( $attributes ) {
		return $attributes;
	}

	/**
	 * Render the block
	 *
	 * @param array<mixed> $attributes Block attributes.
	 * @param string       $content Post content.
	 *
	 * @return string
	 * @since 0.0.1
	 */
	public function render( $attributes, $content ) {
		return '';

	}
}
