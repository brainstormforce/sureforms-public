<?php
/**
 * Class Test_Form_Templates_Preview_Assets
 *
 * What a template preview loads: card pictures for the library grid.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Templates;

class Test_Form_Templates_Preview_Assets extends TestCase {

	/**
	 * Script and style queues before the test.
	 *
	 * @var array{0: mixed, 1: mixed}
	 */
	private $assets;

	/**
	 * Hooks before the test, by name.
	 *
	 * @var array<string, mixed>
	 */
	private $hooks = [];

	public function set_up() {
		parent::set_up();
		global $wp_scripts, $wp_styles, $wp_filter;
		$this->assets = [ $wp_scripts, $wp_styles ];
		$wp_scripts   = null;
		$wp_styles    = null;
		foreach ( [ 'wp_enqueue_scripts', 'wp_head', 'wp_print_styles', 'wp_print_scripts', 'wp_body_open', 'wp_footer', 'wp_print_footer_scripts' ] as $hook ) {
			$this->hooks[ $hook ] = isset( $wp_filter[ $hook ] ) ? clone $wp_filter[ $hook ] : null;
		}
	}

	public function tear_down() {
		global $wp_scripts, $wp_styles, $wp_filter;
		list( $wp_scripts, $wp_styles ) = $this->assets;
		foreach ( $this->hooks as $hook => $saved ) {
			if ( null === $saved ) {
				unset( $wp_filter[ $hook ] );
			} else {
				$wp_filter[ $hook ] = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the hooks the test changed.
			}
		}
		remove_all_filters( 'srfm_form_templates' );
		$property = new ReflectionProperty( Form_Templates::class, 'templates' );
		$property->setAccessible( true );
		$property->setValue( null, null );
		parent::tear_down();
	}

	public function test_every_template_card_has_a_small_picture() {
		foreach ( Form_Templates::get_templates_for_js() as $item ) {
			$this->assertNotSame( '', $item['thumbnail'], "Template {$item['id']}: no card picture." );
			$this->assertStringStartsWith( SRFM_URL . 'images/templates/', $item['thumbnail'] );
			$path = SRFM_DIR . substr( $item['thumbnail'], strlen( SRFM_URL ) );
			$this->assertFileExists( $path, "Template {$item['id']}: card picture missing." );
			$this->assertLessThan( 60 * 1024, filesize( $path ), "Template {$item['id']}: card picture is 60 KB or more." );
		}
	}

	public function test_card_picture_of_a_free_template_is_its_own_file() {
		$items = array_column( Form_Templates::get_templates_for_js(), null, 'id' );

		$this->assertSame( SRFM_URL . 'images/templates/contact-us.webp', $items['contact-us']['thumbnail'] );
		// A free template's preview stays live: it has no Premium picture.
		$this->assertSame( '', $items['contact-us']['preview_image'] );
		// A Premium template's card shows its Premium picture.
		$this->assertSame( $items['loan-emi-calculator']['preview_image'], $items['loan-emi-calculator']['thumbnail'] );
	}

	public function test_card_picture_of_an_added_template() {
		$this->assertSame( '', Form_Templates::get_thumbnail_url( [ 'id' => 'no-picture-here' ] ) );
		$this->assertSame( 'https://example.com/card.webp', Form_Templates::get_thumbnail_url( [ 'id' => 'added', 'preview_image' => 'https://example.com/card.webp' ] ) );
		// The id can't point outside the pictures folder.
		$this->assertSame( '', Form_Templates::get_thumbnail_url( [ 'id' => '../images/templates/contact-us' ] ) );
	}
}
