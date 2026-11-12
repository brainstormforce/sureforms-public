<?php
/**
 * Bricks SureForms service provider.
 *
 * @package sureforms.
 * @since 0.0.5
 */

namespace SRFM\Inc\Page_Builders\Bricks;

use SRFM\Inc\Features;
use SRFM\Inc\Payments\Payment_History_Shortcode;
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

		// Payment history belongs to Payments; it is not offered while that is off.
		if ( ! Features::is_enabled( 'payments' ) ) {
			return;
		}
		\Bricks\Elements::register_element( __DIR__ . '/elements/payment-history-widget.php' );

		// Head-load the Payment History stylesheet when the element is on the page.
		// Priority 5 runs after Payment_History_Shortcode::register_assets() (priority 1)
		// so the handle exists, and before wp_head prints styles.
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_payment_history_assets' ], 5 );
	}

	/**
	 * Enqueue the Payment History assets in the <head> when a Bricks Payment History
	 * element is present on the current page.
	 *
	 * Bricks instantiates elements — and so fires their `enqueue_scripts()` — only
	 * during render (`Frontend::render_element()` → `Element::init()`), which runs
	 * after `wp_head`. An element-side enqueue therefore lands in the footer and the
	 * dashboard flashes unstyled. Bricks stores element data in postmeta, so detect our
	 * element in the head the same way Bricks detects its own setting-specific assets:
	 * sniff the serialised template data of each area for the element name.
	 *
	 * @since 2.12.3
	 * @return void
	 */
	public function maybe_enqueue_payment_history_assets() {
		if ( ! class_exists( '\Bricks\Database' ) ) {
			return;
		}

		foreach ( [ 'header', 'content', 'footer' ] as $area ) {
			$data = \Bricks\Database::get_template_data( $area );
			if ( ! is_array( $data ) ) {
				continue;
			}
			if ( false !== strpos( (string) wp_json_encode( $data ), '"sureforms-payment-history"' ) ) {
				Payment_History_Shortcode::get_instance()->enqueue_assets( true );
				return;
			}
		}
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
