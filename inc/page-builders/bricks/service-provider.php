<?php

namespace SRFM\Inc\Page_Builders\Bricks;

class Service_Provider {
	public function __construct() {
		add_action( 'init', [ $this, 'init' ], 11 );
	}

	public function init() {

		if ( class_exists( '\Bricks\Elements' ) ) {
			try {
				// i18n category title
				add_filter(
					'bricks/builder/i18n',
					function( $i18n ) {
						$i18n['sureforms'] = 'sureforms';
						return $i18n;
					}
				);

				// Register element
				\Bricks\Elements::register_element( __DIR__ . '/elements/form-widget.php' );
			} catch ( Exception $e ) {
				// Handle exception
			}
		}
	}
}
