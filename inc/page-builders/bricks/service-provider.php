<?php
/**
 * Bricks SureForms service provider.
 *
 * @package sureforms.
 * @since 0.0.5
 */

namespace SRFM\Inc\Page_Builders\Bricks;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms Bricks service provider.
 */
class Service_Provider {
	use Get_Instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'widget' ], 11 );
	}

	/**
	 * Register SureForms widget.
	 *
	 * @since 0.0.5
	 * @return void
	 */
	public function widget() {
		if ( ! class_exists( '\Bricks\Elements' ) ) {
			return;
		}
		add_filter(
			'bricks/builder/i18n',
			[ $this, 'bricks_translatable_strings' ]
		);
		\Bricks\Elements::register_element( __DIR__ . '/elements/form-widget.php' );
	}

	/**
	 * Filter to add translatable string to the builder.
	 *
	 * @param array<string> $i18n Array of translatable strings.
	 * @since 0.0.5
	 * @return array<string> $i18n
	 */
	public function bricks_translatable_strings( $i18n ) {
		$i18n['sureforms'] = __( 'SureForms', 'sureforms' );
		return $i18n;
	}
}
