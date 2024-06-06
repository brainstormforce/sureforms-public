<?php
/**
 * Bricks SureForms service provider.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc\Page_Builders\Bricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SureForms Bricks service provider.
 */
class Service_Provider {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'widget' ], 11 );
	}

	/**
	 * Register SureForms widget.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function widget() {
		if ( ! class_exists( '\Bricks\Elements' ) ) {
			return;
		}
		add_filter(
			'bricks/builder/i18n',
			function( $i18n ) {
				$i18n['sureforms'] = 'sureforms';
				return $i18n;
			}
		);
		\Bricks\Elements::register_element( __DIR__ . '/elements/form-widget.php' );
	}
}
