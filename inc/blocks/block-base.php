<?php
/**
 * The blocks base file.
 *
 * @link       https://surecart.com
 * @since      X.X.X
 * @package    SureForms
 * @author     SureCart <https://surecart.com/>
 */

namespace SureForms\Inc\Blocks;

/**
 * Block base class.
 */
abstract class Base {
	/**
	 * Optional directory to .json block data files.
	 *
	 * @var string
	 * @since X.X.X
	 */
	protected $directory = '';

	/**
	 * Holds the block.
	 *
	 * @var object
	 * @since X.X.X
	 */
	protected $block;

	/**
	 * Register the block for dynamic output
	 *
	 * @return void
	 * @since X.X.X
	 */
	public function register() {
		register_block_type_from_metadata(
			$this->get_dir(),
			apply_filters(
				'sureforms_block_registration_args',
				[ 'render_callback' => [ $this, 'pre_render' ] ],
			),
		);
	}

	/**
	 * Get the called class directory path
	 *
	 * @return string
	 * @since X.X.X
	 */
	public function get_dir() {
		if ( $this->directory ) {
			return $this->directory;
		}

		$reflector = new \ReflectionClass( $this );
		$fn        = $reflector->getFileName();
		return dirname( $fn );
	}


	/**
	 * Optionally run a function to modify attributes before rendering.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content   Post content.
	 * @param array  $block Block attributes.
	 *
	 * @return function
	 * @since X.X.X
	 */
	public function pre_render( $attributes, $content, $block ) {
		$this->block = $block;

		// run middlware.
		$render = $this->middleware( $attributes, $content );

		if ( is_wp_error( $render ) ) {
			return $render->get_error_message();
		}

		if ( true !== $render ) {
			return $render;
		}

		$attributes = $this->getAttributes( $attributes );

		// render.
		return $this->render( $attributes, $content, $block );
	}

	/**
	 * Run any block middleware before rendering.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content   Post content.
	 * @return boolean|\WP_Error;
	 * @since X.X.X
	 */
	protected function middleware( $attributes, $content ) {
		return true;
	}

	/**
	 * Allows filtering of attributes before rendering.
	 *
	 * @param array $attributes Block attributes.
	 * @return array $attributes
	 * @since X.X.X
	 */
	public function get_attributes( $attributes ) {
		return $attributes;
	}

	/**
	 * Render the block
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content Post content.
	 *
	 * @return string
	 * @since X.X.X
	 */
	public function render( $attributes, $content ) {
		return '';
	}
}
