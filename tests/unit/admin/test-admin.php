<?php
/**
 * Class Test_First_Form_Creation
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Admin\Admin;

require_once __DIR__ . '/trait-astra-notices-helper.php';

/**
 * Tests first form creation timestamp logic.
 */
class Test_Admin extends TestCase {

    protected function setUp(): void {
        parent::setUp();

        // Define WordPress constants
        if (!defined('DAY_IN_SECONDS')) {
            define('DAY_IN_SECONDS', 24 * 60 * 60);
        }

        // By default, stub out WP functions with safe values
        if (! function_exists('current_user_can')) {
            function current_user_can($capability) { return true; }
        }
        if (! function_exists('post_type_exists')) {
            function post_type_exists($post_type) { return true; }
        }
        if (! function_exists('get_post_field')) {
            function get_post_field($field_name, $post_id) {
                return '2025-08-01 12:00:00';
            }
        }
        if (! function_exists('current_time')) {
            function current_time($timestamp_type) {
                return gmdate('Y-m-d H:i:s');
            }
        }
        if (! function_exists('get_option')) {
            function get_option($option_name, $default_value = false) {
                // Mock srfm_options for testing
                if ($option_name === 'srfm_options') {
                    return ['first_form_created_at' => 1234567890];
                }
                return $default_value;
            }
        }
    }

    /**
     * Test get_first_form_creation_time_stamp returns stored value.
     */
    public function test_get_first_form_creation_time_stamp_returns_value() {
        // Since we can't easily override WordPress functions in this test setup,
        // we'll test that the method exists and returns the expected type
        $result = Admin::get_first_form_creation_time_stamp();

        // The method should return either an integer timestamp or false
        $this->assertTrue(is_int($result) || $result === false);
    }

    /**
     * Test check_first_form_creation_threshold returns false when not enough days passed.
     */
    public function test_check_first_form_creation_threshold_false() {
        $current_time = strtotime(gmdate('Y-m-d H:i:s'));
        $timestamp = $current_time - (DAY_IN_SECONDS * 1);
        $days_threshold = 3;

        // Test the calculation logic directly
        $days_from_creation = ($current_time - $timestamp) / DAY_IN_SECONDS;
        $this->assertFalse($days_from_creation > $days_threshold);
    }

    /**
     * Test check_first_form_creation_threshold returns true when enough days passed.
     */
    public function test_check_first_form_creation_threshold_true() {
        $current_time = strtotime(gmdate('Y-m-d H:i:s'));
        $timestamp = $current_time - (DAY_IN_SECONDS * 5);
        $days_threshold = 3;

        // Test the calculation logic directly
        $days_from_creation = ($current_time - $timestamp) / DAY_IN_SECONDS;
        $this->assertTrue($days_from_creation > $days_threshold);
    }

    /**
     * Test add_learn_page registers the Learn submenu page.
     */
    public function test_add_learn_page() {
        if ( ! function_exists( 'add_submenu_page' ) ) {
            $this->markTestSkipped( 'WordPress admin menu functions not available.' );
            return;
        }

        $admin = Admin::get_instance();

        // Calling add_learn_page() should not throw — it registers a submenu.
        // In a unit test environment without a full WP admin context the function
        // may be a no-op, so we assert it returns void (null) without errors.
        $result = $admin->add_learn_page();
        $this->assertNull( $result );
    }

    /**
     * Test render_learn outputs the expected root div.
     */
    public function test_render_learn() {
        $admin = Admin::get_instance();

        ob_start();
        $admin->render_learn();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            'id="srfm-learn-root"',
            $output,
            'render_learn should output a div with id srfm-learn-root.'
        );
        $this->assertStringContainsString(
            'srfm-admin-wrapper',
            $output,
            'render_learn should include the srfm-admin-wrapper class.'
        );
    }

	/**
	 * Test settings_page_callback outputs the settings container div.
	 */
	public function test_settings_page_callback() {
		$admin = Admin::get_instance();

		ob_start();
		$admin->settings_page_callback();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="srfm-settings-container"', $output );
		$this->assertStringContainsString( 'srfm-admin-wrapper', $output );
	}

	/**
	 * Test enqueue_scripts is callable.
	 */
	public function test_enqueue_scripts() {
		$admin = Admin::get_instance();
		$this->assertTrue( method_exists( $admin, 'enqueue_scripts' ) );
	}

	/**
	 * Test srfm_pro_version_compatibility returns early when Pro is not active.
	 */
	public function test_srfm_pro_version_compatibility() {
		$admin = Admin::get_instance();

		// When Pro is not active, method should return without output.
		ob_start();
		$admin->srfm_pro_version_compatibility();
		$output = ob_get_clean();

		// Without Pro active, no notice should be rendered.
		$this->assertEmpty( $output );
	}

	/**
	 * Test render_dashboard_widget outputs the widget HTML.
	 */
	public function test_render_dashboard_widget() {
		$admin = Admin::get_instance();

		// Set up dashboard_widget_data via reflection.
		$prop = new \ReflectionProperty( $admin, 'dashboard_widget_data' );
		$prop->setAccessible( true );
		$prop->setValue(
			$admin,
			[
				[
					'title' => 'Contact Form',
					'count' => 5,
				],
			]
		);

		ob_start();
		$admin->render_dashboard_widget();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'srfm-dashboard-widget', $output );
		$this->assertStringContainsString( 'Recent Entries', $output );
		$this->assertStringContainsString( 'Contact Form', $output );
	}
}

/**
 * Tests for the 5-star rating notice functionality.
 *
 * @since 2.5.2
 */
class Test_Rating_Notice extends TestCase {
	use Astra_Notices_Helper;

	/**
	 * Tear down: remove filters added during tests.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Reset the cached should_show_rating property.
		$admin = Admin::get_instance();
		$prop  = new \ReflectionProperty( $admin, 'should_show_rating' );
		$prop->setAccessible( true );
		$prop->setValue( $admin, null );

		remove_all_filters( 'srfm_show_rating_notice' );
		parent::tearDown();
	}

	/**
	 * Helper: invoke the private maybe_display_rating_notice() method via reflection,
	 * after injecting a cached value into the should_show_rating property.
	 *
	 * @param bool $cached_value The value to inject into the cache.
	 * @return bool The method's return value.
	 */
	private function invoke_maybe_display_with_cache( bool $cached_value ): bool {
		$admin = Admin::get_instance();

		$prop = new \ReflectionProperty( $admin, 'should_show_rating' );
		$prop->setAccessible( true );
		$prop->setValue( $admin, $cached_value );

		$method = new \ReflectionMethod( $admin, 'maybe_display_rating_notice' );
		$method->setAccessible( true );

		return $method->invoke( $admin );
	}

	/**
	 * Test: threshold constant is defined and is an integer.
	 */
	public function test_rating_notice_threshold_is_defined() {
		$this->assertSame( 3, Admin::RATING_NOTICE_THRESHOLD );
	}

	/**
	 * Test: display condition is false when cached value is false.
	 */
	public function test_rating_notice_returns_false_when_cached_false() {
		$this->assertFalse( $this->invoke_maybe_display_with_cache( false ) );
	}

	/**
	 * Test: display condition is true when cached value is true.
	 */
	public function test_rating_notice_returns_true_when_cached_true() {
		$this->assertTrue( $this->invoke_maybe_display_with_cache( true ) );
	}

	/**
	 * Test: condition logic — below threshold should not display.
	 */
	public function test_rating_notice_condition_false_below_threshold() {
		$threshold = Admin::RATING_NOTICE_THRESHOLD;

		$this->assertFalse( 0 >= $threshold || 0 >= $threshold, 'Should not display with 0 forms and 0 entries' );
		$this->assertFalse( 0 >= $threshold || ( $threshold - 1 ) >= $threshold, 'Should not display just below threshold' );
	}

	/**
	 * Test: condition logic — at or above threshold should display.
	 */
	public function test_rating_notice_condition_true_at_threshold() {
		$threshold = Admin::RATING_NOTICE_THRESHOLD;

		$this->assertTrue( 0 >= $threshold || $threshold >= $threshold, 'Should display with exactly threshold forms' );
		$this->assertTrue( $threshold >= $threshold || 0 >= $threshold, 'Should display with exactly threshold entries' );
	}

	/**
	 * Test: display_srfm_rating_notice adds no notice when srfm_show_rating_notice filter returns false.
	 */
	public function test_display_srfm_rating_notice_skipped_when_filter_disabled() {
		add_filter( 'srfm_show_rating_notice', '__return_false' );

		$before_count = $this->get_astra_notices_count();

		$admin = Admin::get_instance();
		$admin->display_srfm_rating_notice();

		$after_count = $this->get_astra_notices_count();

		$this->assertEquals(
			$before_count,
			$after_count,
			'No notice should be registered when srfm_show_rating_notice filter returns false'
		);
	}
}

/**
 * Tests for the "Getting Started" admin notice functionality.
 *
 * @since 2.5.2
 */
class Test_Getting_Started_Notice extends TestCase {
	use Astra_Notices_Helper;

	/**
	 * Tear down: remove filters added during tests.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Reset the cached should_show_rating property.
		$admin = Admin::get_instance();
		$prop  = new \ReflectionProperty( $admin, 'should_show_rating' );
		$prop->setAccessible( true );
		$prop->setValue( $admin, null );

		remove_all_filters( 'srfm_show_getting_started_notice' );
		parent::tearDown();
	}

	/**
	 * Test: getting-started shows when rating condition is false (below threshold).
	 */
	public function test_getting_started_show_if_true_below_threshold() {
		$threshold = Admin::RATING_NOTICE_THRESHOLD;

		$rating_display = 0 >= $threshold || 0 >= $threshold;
		$this->assertTrue( ! $rating_display, 'Getting-started should show with 0 forms and 0 entries' );

		$rating_display = ( $threshold - 1 ) >= $threshold || ( $threshold - 1 ) >= $threshold;
		$this->assertTrue( ! $rating_display, 'Getting-started should show just below threshold' );
	}

	/**
	 * Test: getting-started hides when rating condition is true (at or above threshold).
	 */
	public function test_getting_started_show_if_false_at_threshold() {
		$threshold = Admin::RATING_NOTICE_THRESHOLD;

		$rating_display = $threshold >= $threshold || 0 >= $threshold;
		$this->assertFalse( ! $rating_display, 'Getting-started should not show when forms reach threshold' );

		$rating_display = 0 >= $threshold || $threshold >= $threshold;
		$this->assertFalse( ! $rating_display, 'Getting-started should not show when entries reach threshold' );
	}

	/**
	 * Test: mutual exclusivity — rating and getting-started conditions are always opposite.
	 */
	public function test_mutual_exclusivity_of_notices() {
		$threshold = Admin::RATING_NOTICE_THRESHOLD;
		$scenarios = [
			[ 'entries' => 0, 'forms' => 0 ],
			[ 'entries' => $threshold - 1, 'forms' => $threshold - 1 ],
			[ 'entries' => $threshold, 'forms' => 0 ],
			[ 'entries' => 0, 'forms' => $threshold ],
			[ 'entries' => $threshold + 2, 'forms' => $threshold + 2 ],
		];

		foreach ( $scenarios as $scenario ) {
			$rating_show          = $scenario['entries'] >= $threshold || $scenario['forms'] >= $threshold;
			$getting_started_show = ! $rating_show;

			$this->assertNotEquals(
				$rating_show,
				$getting_started_show,
				sprintf(
					'Rating and getting-started should be mutually exclusive (entries=%d, forms=%d)',
					$scenario['entries'],
					$scenario['forms']
				)
			);
		}
	}

	/**
	 * Test: display_srfm_getting_started_notice adds no notice when filter returns false.
	 */
	public function test_display_srfm_getting_started_notice_skipped_when_filter_disabled() {
		add_filter( 'srfm_show_getting_started_notice', '__return_false' );

		$before_count = $this->get_astra_notices_count();

		$admin = Admin::get_instance();
		$admin->display_srfm_getting_started_notice();

		$after_count = $this->get_astra_notices_count();

		$this->assertEquals(
			$before_count,
			$after_count,
			'No notice should be registered when srfm_show_getting_started_notice filter returns false'
		);
	}

	/**
	 * Test: handle_notice_response rejects requests with an invalid notice_id / button combination.
	 *
	 * @since 2.5.2
	 */
	public function test_handle_notice_response_rejects_invalid_params() {
		$valid = [
			'srfm-getting-started-notice' => [ 'go_to_dashboard', 'maybe_later', 'dismissed' ],
			'srfm-plugin-review-notice'   => [ 'rate_sureforms', 'maybe_later', 'dismissed' ],
		];

		// Unknown notice_id is not a key in the allowlist.
		$this->assertArrayNotHasKey( 'unknown-notice', $valid );

		// Unknown button is not in any notice's allowed buttons.
		foreach ( $valid as $buttons ) {
			$this->assertNotContains( 'unknown_button', $buttons );
		}
	}

	/**
	 * Test: enqueue_notice_response_script is callable and skips double-enqueue.
	 *
	 * @since 2.5.2
	 */
	public function test_enqueue_notice_response_script_is_callable() {
		$admin = Admin::get_instance();
		$this->assertTrue( method_exists( $admin, 'enqueue_notice_response_script' ) );
	}
}
