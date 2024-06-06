<?php

namespace SRFM\Inc\Page_Builders\Bricks;

class Service_Provider {
	public function __construct() {
		add_action( 'init', [ $this, 'widget' ], 11 );
	}

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
