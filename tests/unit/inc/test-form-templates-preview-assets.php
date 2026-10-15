<?php
/**
 * Class Test_Form_Templates_Preview_Assets
 *
 * What a template preview loads: card pictures for the library grid, and only
 * SureForms' own scripts and styles on the preview page.
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

	public function test_preview_keeps_only_sureforms_core_and_theme_assets() {
		$this->assertTrue( Form_Templates::is_preview_asset( 'srfm-form-submit', SRFM_URL . 'assets/js/x.js', 'script' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( 'sureforms-pro-page-break-deps', 'https://example.com/pro.js', 'script' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( 'srfm-pro-signature-pad-lib', 'https://example.com/lib.js', 'script' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( 'any-handle', SRFM_URL . 'assets/js/x.js', 'script' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( 'hcaptcha', 'https://js.hcaptcha.com/1/api.js', 'script' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( 'global-styles', '', 'style' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( get_stylesheet() . '-style', '', 'style' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( 'theme-css', trailingslashit( get_stylesheet_directory_uri() ) . 'style.css', 'style' ) );

		$this->assertFalse( Form_Templates::is_preview_asset( 'third-party-test', 'https://example.com/pixel.js', 'script' ) );
		$this->assertFalse( Form_Templates::is_preview_asset( 'chat-widget', 'https://example.com/chat.css', 'style' ) );
		$this->assertFalse( Form_Templates::is_preview_asset( 'admin-bar', '/wp-includes/css/admin-bar.min.css', 'style' ) );
		// The theme's scripts don't run either.
		$this->assertFalse( Form_Templates::is_preview_asset( get_stylesheet() . '-navigation', trailingslashit( get_stylesheet_directory_uri() ) . 'nav.js', 'script' ) );
		$this->assertFalse( Form_Templates::is_preview_asset( 'global-styles', '', 'script' ) );
	}

	public function test_filter_preview_assets_drops_other_plugins_and_keeps_dependencies() {
		wp_enqueue_script( 'srfm-test-form', SRFM_URL . 'assets/js/test.js', [ 'wp-i18n', 'wp-hooks' ], '1', true );
		wp_enqueue_script( 'third-party-test', 'https://example.com/third-party-test.js', [], '1', true );
		wp_enqueue_style( 'srfm-test-form', SRFM_URL . 'assets/css/test.css', [], '1' );
		wp_enqueue_style( 'third-party-test', 'https://example.com/third-party-test.css', [], '1' );
		wp_register_style( 'global-styles', false, [], '1' );
		wp_enqueue_style( 'global-styles' );

		Form_Templates::filter_preview_assets();

		$this->assertSame( [ 'srfm-test-form' ], wp_scripts()->queue );
		$this->assertSame( [ 'srfm-test-form', 'global-styles' ], wp_styles()->queue );

		// The kept scripts' dependencies still print.
		wp_scripts()->all_deps( wp_scripts()->queue );
		$this->assertContains( 'wp-i18n', wp_scripts()->to_do );
		$this->assertContains( 'wp-hooks', wp_scripts()->to_do );
		$this->assertNotContains( 'third-party-test', wp_scripts()->to_do );
	}

	public function test_third_party_head_and_footer_callbacks_are_removed() {
		// Defined outside WordPress, SureForms and the theme, like another
		// plugin's tracking snippet. (This test file is inside SureForms.)
		$file = trailingslashit( get_temp_dir() ) . 'srfm-third-party-pixel.php';
		file_put_contents( $file, '<?php return static function () { echo "<script>window.thirdPartyPixel = 1;</script>"; };' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture.
		$pixel = require $file;
		unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test fixture.
		add_action( 'wp_head', $pixel );
		add_action( 'wp_footer', $pixel, 30 );
		add_action( 'wp_enqueue_scripts', $pixel );
		add_action( 'wp_head', [ Form_Templates::class, 'filter_preview_assets' ], 5 );

		Form_Templates::remove_third_party_hooks();

		$this->assertFalse( has_action( 'wp_head', $pixel ) );
		$this->assertFalse( has_action( 'wp_footer', $pixel ) );
		$this->assertFalse( has_action( 'wp_enqueue_scripts', $pixel ) );
		// WordPress core and SureForms keep theirs.
		$this->assertSame( 8, has_action( 'wp_head', 'wp_print_styles' ) );
		$this->assertSame( 20, has_action( 'wp_footer', 'wp_print_footer_scripts' ) );
		$this->assertSame( 5, has_action( 'wp_head', [ Form_Templates::class, 'filter_preview_assets' ] ) );
	}
}
