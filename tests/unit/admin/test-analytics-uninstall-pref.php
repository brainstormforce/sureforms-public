<?php
/**
 * Tests for the deactivation-survey uninstall checkbox.
 *
 * Covers the ajax handler that stores the preference and the survey config that
 * surfaces the control, which together are the only way the flag uninstall.php
 * reads can ever be set.
 *
 * The handler is called directly rather than through WP_Ajax_UnitTestCase: the
 * WordPress test suite excludes the `ajax` group by default, so those tests can
 * silently not run at all, and this suite's bootstrap installs its own
 * wp_die_ajax_handler which throws WPDieException instead of the exceptions that
 * base class expects. This follows the pattern already used in
 * tests/unit/inc/test-admin-ajax.php.
 *
 * @package sureforms
 * @since 2.13.0
 */

use SRFM\Admin\Analytics;
use SRFM\Inc\Helper;

/**
 * Class Test_Analytics_Uninstall_Pref
 *
 * @since 2.13.0
 */
class Test_Analytics_Uninstall_Pref extends SRFM_Unit_Test_Case {

	/**
	 * The class under test.
	 *
	 * @var Analytics
	 */
	private $analytics;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// The singleton, not a new instance: the constructor registers every one
		// of the class's hooks, and a second set would fire each tracker twice
		// for the rest of the run.
		$this->analytics = Analytics::get_instance();

		delete_option( 'srfm_options' );
	}

	/**
	 * Tear down.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $_POST['security'], $_POST['checked'], $_REQUEST['security'] );

		wp_set_current_user( 0 );
		delete_option( 'srfm_options' );

		parent::tearDown();
	}

	/**
	 * Log in as a user of the given role.
	 *
	 * @param string $role Role to create the user with.
	 * @return void
	 */
	private function login_as( $role ) {
		wp_set_current_user( self::factory()->user->create( [ 'role' => $role ] ) );
	}

	/**
	 * Call the handler the way the library would and return the JSON it sent.
	 *
	 * Every path ends in wp_send_json_*(), which dies - WPDieException here. A
	 * return rather than a throw means the handler fell through all of its
	 * guards without responding, which is a failure in itself.
	 *
	 * @param mixed  $checked Value the library would post as `checked`, or null to omit it.
	 * @param string $nonce   Nonce to send; defaults to a valid one.
	 * @return array<mixed> The decoded JSON response.
	 */
	private function post( $checked, $nonce = null ) {
		$_POST['security']    = null === $nonce ? wp_create_nonce( Analytics::UNINSTALL_PREF_ACTION ) : $nonce;
		$_REQUEST['security'] = $_POST['security'];

		if ( null === $checked ) {
			unset( $_POST['checked'] );
		} else {
			$_POST['checked'] = $checked;
		}

		ob_start();

		try {
			$this->analytics->save_uninstall_preference();
			ob_end_clean();
			$this->fail( 'the handler must always answer with wp_send_json_*()' );
		} catch ( \WPDieException $e ) {
			unset( $e );
			return (array) json_decode( (string) ob_get_clean(), true );
		}
	}

	/**
	 * Ticking the box stores a real boolean true - the only value uninstall.php
	 * accepts as consent.
	 *
	 * @return void
	 */
	public function test_checked_stores_boolean_true() {
		$this->login_as( 'administrator' );

		$response = $this->post( '1' );

		$this->assertTrue( $response['success'] );
		$this->assertTrue( Helper::get_srfm_option( Analytics::UNINSTALL_PREF_KEY ), 'must be boolean true, not the posted string' );
	}

	/**
	 * Unticking it stores false, so the decision stays reversible.
	 *
	 * @return void
	 */
	public function test_unchecked_stores_boolean_false() {
		$this->login_as( 'administrator' );
		Helper::update_srfm_option( Analytics::UNINSTALL_PREF_KEY, true );

		$response = $this->post( '0' );

		$this->assertTrue( $response['success'] );
		$this->assertFalse( Helper::get_srfm_option( Analytics::UNINSTALL_PREF_KEY ) );
	}

	/**
	 * Anything other than '1' stores false.
	 *
	 * The comparison is strict on purpose: a cast would read 'no' and 'off' as
	 * consent, and this handler is the only writer of a value that arms a
	 * destructive sweep.
	 *
	 * @param mixed $posted Value posted as `checked`, or null to omit the key.
	 * @return void
	 *
	 * @dataProvider provide_non_one_values
	 */
	public function test_anything_but_one_stores_false( $posted ) {
		$this->login_as( 'administrator' );
		Helper::update_srfm_option( Analytics::UNINSTALL_PREF_KEY, true );

		$this->post( $posted );

		$this->assertFalse(
			Helper::get_srfm_option( Analytics::UNINSTALL_PREF_KEY ),
			'only the literal string 1 may read as consent'
		);
	}

	/**
	 * Values a hand-rolled request could post instead of '1'.
	 *
	 * The `absent` case is the one that matters most: omitting an input must not
	 * be a way to leave consent armed.
	 *
	 * @return array<string,array<mixed>>
	 */
	public function provide_non_one_values() {
		return [
			'no'     => [ 'no' ],
			'off'    => [ 'off' ],
			'false'  => [ 'false' ],
			'true'   => [ 'true' ],
			'yes'    => [ 'yes' ],
			'on'     => [ 'on' ],
			'empty'  => [ '' ],
			'array'  => [ [ '1' ] ],
			'absent' => [ null ],
		];
	}

	/**
	 * A bad nonce is rejected, and nothing is written.
	 *
	 * @return void
	 */
	public function test_invalid_nonce_is_rejected() {
		$this->login_as( 'administrator' );

		$response = $this->post( '1', 'not-a-real-nonce' );

		$this->assertFalse( $response['success'] );
		$this->assertNull( Helper::get_srfm_option( Analytics::UNINSTALL_PREF_KEY ), 'nothing may be written on a failed nonce' );
	}

	/**
	 * A user without manage_options cannot arm the sweep, even holding a valid
	 * nonce - which a subscriber can mint for themselves.
	 *
	 * @return void
	 */
	public function test_user_without_manage_options_is_rejected() {
		$this->login_as( 'subscriber' );

		$response = $this->post( '1' );

		$this->assertFalse( $response['success'] );
		$this->assertNull( Helper::get_srfm_option( Analytics::UNINSTALL_PREF_KEY ) );
	}

	/**
	 * A logged-out request is rejected too.
	 *
	 * @return void
	 */
	public function test_logged_out_request_is_rejected() {
		wp_set_current_user( 0 );

		$response = $this->post( '1' );

		$this->assertFalse( $response['success'] );
		$this->assertNull( Helper::get_srfm_option( Analytics::UNINSTALL_PREF_KEY ) );
	}

	/**
	 * The control is only rendered for a user the handler would accept, so
	 * nobody is offered a box that silently fails to save.
	 *
	 * @return void
	 */
	public function test_survey_offers_the_checkbox_only_to_an_administrator() {
		$this->login_as( 'administrator' );
		$this->assertNotSame( [], $this->extra_options(), 'an administrator is offered the checkbox' );

		$this->login_as( 'subscriber' );
		$this->assertSame( [], $this->extra_options(), 'a subscriber is offered nothing' );
	}

	/**
	 * The rendered box reflects the stored value, so reopening the popup shows
	 * the decision as it stands.
	 *
	 * @return void
	 */
	public function test_checkbox_reflects_the_stored_value() {
		$this->login_as( 'administrator' );

		$options = $this->extra_options();
		$this->assertFalse( $options[0]['checked'], 'unticked by default' );

		Helper::update_srfm_option( Analytics::UNINSTALL_PREF_KEY, true );
		$options = $this->extra_options();
		$this->assertTrue( $options[0]['checked'] );

		// The library reads `checked` with FILTER_VALIDATE_BOOLEAN, which would
		// tick the box for 'yes' - a value the gate in uninstall.php refuses to
		// act on. Showing it ticked would promise a cleanup that never happens.
		Helper::update_srfm_option( Analytics::UNINSTALL_PREF_KEY, 'yes' );
		$options = $this->extra_options();
		$this->assertFalse( $options[0]['checked'], 'only boolean true ticks the box, matching the gate in uninstall.php' );
	}

	/**
	 * The name and action the library will render must be the ones the handler
	 * and uninstall.php actually consume.
	 *
	 * @return void
	 */
	public function test_checkbox_names_match_what_is_consumed() {
		$this->login_as( 'administrator' );

		$option = $this->extra_options()[0];

		$this->assertSame( 'checkbox', $option['type'] );
		$this->assertSame( 'remove_analytics_data_on_uninstall', $option['name'], 'the key uninstall.php reads' );
		$this->assertSame( Analytics::UNINSTALL_PREF_ACTION, $option['ajax_action'] );
		$this->assertNotFalse( wp_verify_nonce( $option['nonce'], Analytics::UNINSTALL_PREF_ACTION ), 'the rendered nonce must verify for the user it was minted for' );

		// The library silently skips any entry whose name or action is not of
		// this shape, which would leave the popup with no control and no error.
		$this->assertMatchesRegularExpression( '/^[A-Za-z0-9_-]+\z/', $option['name'] );
		$this->assertMatchesRegularExpression( '/^[A-Za-z0-9_-]+\z/', $option['ajax_action'] );
	}

	/**
	 * A front-end request never builds the control.
	 *
	 * The popup only renders on the plugins screen, so asking for the current
	 * user on every page view would be pure cost - and would pin the user before
	 * anything that sets it on init.
	 *
	 * @return void
	 */
	public function test_front_end_request_builds_no_control() {
		$this->login_as( 'administrator' );

		$captured = [];
		$capture  = static function ( $survey ) use ( &$captured ) {
			$captured = $survey;
			return $survey;
		};

		add_filter( 'srfm_deactivation_survey_data', $capture );
		set_current_screen( 'front' );

		$this->analytics->register_analytics_entity();

		remove_filter( 'srfm_deactivation_survey_data', $capture );

		$this->assertFalse( is_admin(), 'guard against the screen helper changing under us' );
		$this->assertSame( [], $captured[0]['extra_options'], 'nothing is built for a front-end view' );
	}

	/**
	 * The survey config is built on plugins_loaded, never at plugin-load time.
	 *
	 * It calls current_user_can() and wp_create_nonce(), and neither exists
	 * until wp-includes/pluggable.php is required - which happens after every
	 * plugin file has been included but before plugins_loaded fires. Building it
	 * in the constructor fatals the entire site on every request.
	 *
	 * The real regression guard is this suite's own bootstrap, which loads the
	 * plugin at muplugins_loaded and dies outright if the call moves back. This
	 * asserts the hook so the reason is written down where the next person looks.
	 *
	 * @return void
	 */
	public function test_entity_registration_waits_for_pluggable_functions() {
		$this->assertNotFalse(
			has_action( 'plugins_loaded', [ $this->analytics, 'register_analytics_entity' ] ),
			'the survey config needs current_user_can() and wp_create_nonce(), so it cannot be built at plugin-load time'
		);
	}

	/**
	 * Build the survey config the way Analytics does and return its extra options.
	 *
	 * The array is assembled in register_analytics_entity() and handed straight
	 * to BSF_Analytics_Loader, so the only seam is the filter it passes through
	 * on the way - which is also the surface a third party could change. Capture
	 * the value going in rather than re-deriving it here, so a change to the real
	 * config cannot pass this test.
	 *
	 * @return array<mixed>
	 */
	private function extra_options() {
		$captured = [];

		$capture = static function ( $survey ) use ( &$captured ) {
			$captured = $survey;
			return $survey;
		};

		add_filter( 'srfm_deactivation_survey_data', $capture );

		// The config is only built in the admin, which is where the popup can
		// render; without a screen set, is_admin() is false under PHPUnit.
		set_current_screen( 'plugins' );

		$this->analytics->register_analytics_entity();

		set_current_screen( 'front' );

		remove_filter( 'srfm_deactivation_survey_data', $capture );

		return $captured[0]['extra_options'] ?? [];
	}
}
