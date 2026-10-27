<?php
/**
 * Class Test_Background_Process
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Background_Process;
use SRFM\Inc\Database\Tables\Entries;

class Test_Background_Process extends TestCase {

	protected $background_process;

	/**
	 * Entry IDs created by a test, deleted in tear_down.
	 *
	 * @var int[]
	 */
	protected $entry_ids = [];

	/**
	 * Number of times srfm_after_submission_process fired.
	 *
	 * @var int
	 */
	protected $hook_runs = 0;

	protected function setUp(): void {
		$this->background_process = new Background_Process();
		$this->hook_runs          = 0;
	}

	protected function tearDown(): void {
		remove_all_actions( 'srfm_after_submission_process' );
		remove_all_filters( 'query' );
		foreach ( $this->entry_ids as $entry_id ) {
			Entries::delete( $entry_id );
		}
		$this->entry_ids = [];
		parent::tearDown();
	}

	/**
	 * Create a stored entry and return its ID.
	 *
	 * @param array $extras Extras to store with the entry.
	 * @return int
	 */
	protected function create_entry( $extras = [] ) {
		$entry_id = Entries::add(
			[
				'form_id'   => 1,
				'form_data' => [ 'srfm-input-name' => 'Test' ],
				'extras'    => $extras,
			]
		);
		$this->assertIsInt( $entry_id, 'The entries table must accept the test entry.' );
		$this->entry_ids[] = $entry_id;
		return $entry_id;
	}

	/**
	 * Build the request the after-submission URL produces for an entry.
	 *
	 * @param int $entry_id Entry ID.
	 * @return WP_REST_Request
	 */
	protected function make_request( $entry_id ) {
		$request = new WP_REST_Request( 'GET', '/sureforms/v1/after-submission/' . $entry_id );
		$request->set_param( 'submission_id', $entry_id );
		$request->set_param( 'after_submit_nonce', wp_create_nonce( 'srfm_after_submission_' . $entry_id ) );
		return $request;
	}

	/**
	 * Invoke a protected method.
	 *
	 * @param string $method Method name.
	 * @param array  $args   Arguments.
	 * @return mixed
	 */
	protected function invoke( $method, $args = [] ) {
		$reflection = new ReflectionMethod( Background_Process::class, $method );
		$reflection->setAccessible( true );
		return $reflection->invokeArgs( $this->background_process, $args );
	}

	public function test_register_custom_endpoint() {
		do_action( 'rest_api_init' );
		$routes = rest_get_server()->get_routes();
		$found = false;
		foreach ( array_keys( $routes ) as $route ) {
			if ( strpos( $route, 'sureforms/v1/after-submission' ) !== false ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'The after-submission endpoint should be registered' );
	}

	/**
	 * A request that overlaps one still running the hooks must not run them again.
	 *
	 * The second request is issued from inside the first one's
	 * srfm_after_submission_process callback, which is the window two concurrent
	 * requests share: the first has passed every check but not finished its hooks.
	 */
	public function test_handle_after_submission() {
		$entry_id = $this->create_entry();
		$nested   = null;

		add_action(
			'srfm_after_submission_process',
			function () use ( $entry_id, &$nested ) {
				$this->hook_runs++;
				if ( null === $nested ) {
					$nested = false;
					$nested = ( new Background_Process() )->handle_after_submission( $this->make_request( $entry_id ) );
				}
			}
		);

		$first = $this->background_process->handle_after_submission( $this->make_request( $entry_id ) );

		$this->assertInstanceOf( WP_REST_Response::class, $first );
		$this->assertSame( 1, $this->hook_runs, 'The overlapping request must not run the hooks a second time.' );
		$this->assertInstanceOf( WP_Error::class, $nested );
		$this->assertSame( 'process_already_triggered', $nested->get_error_code() );

		// A later sequential retry is refused too.
		$retry = ( new Background_Process() )->handle_after_submission( $this->make_request( $entry_id ) );
		$this->assertInstanceOf( WP_Error::class, $retry );
		$this->assertSame( 'process_already_triggered', $retry->get_error_code() );
		$this->assertSame( 1, $this->hook_runs );
	}

	/**
	 * An invalid nonce neither runs the hooks nor consumes the claim.
	 */
	public function test_handle_after_submission_invalid_nonce_keeps_entry_unclaimed() {
		$entry_id = $this->create_entry();
		add_action(
			'srfm_after_submission_process',
			function () {
				$this->hook_runs++;
			}
		);

		$request = $this->make_request( $entry_id );
		$request->set_param( 'after_submit_nonce', 'invalid' );
		$result = $this->background_process->handle_after_submission( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'rest_nonce_failed', $result->get_error_code() );
		$this->assertSame( 0, $this->hook_runs );

		$valid = ( new Background_Process() )->handle_after_submission( $this->make_request( $entry_id ) );
		$this->assertInstanceOf( WP_REST_Response::class, $valid );
		$this->assertSame( 1, $this->hook_runs );
	}

	/**
	 * Only the first claim for an entry succeeds, and it keeps the other extras.
	 */
	public function test_claim_submission() {
		$entry_id = $this->create_entry( [ 'existing_key' => 'kept' ] );

		$this->assertTrue( $this->invoke( 'claim_submission', [ $entry_id ] ) );
		$this->assertFalse( $this->invoke( 'claim_submission', [ $entry_id ] ) );

		$extras = Entries::get( $entry_id )['extras'];
		$this->assertTrue( $extras['is_after_submission_process_triggered'] );
		$this->assertSame( 'kept', $extras['existing_key'] );

		// An entry that does not exist is never claimed.
		$this->assertFalse( $this->invoke( 'claim_submission', [ PHP_INT_MAX ] ) );
	}

	/**
	 * A request that read the entry before another request claimed it loses.
	 *
	 * The other request's claim is written between this request's read and its
	 * UPDATE, which is the window a read-then-write check leaves open.
	 */
	public function test_claim_submission_loses_to_claim_written_after_its_read() {
		global $wpdb;
		$entry_id = $this->create_entry();
		$table    = Entries::get_instance()->get_tablename();
		$injected = false;

		add_filter(
			'query',
			static function ( $query ) use ( $wpdb, $table, $entry_id, &$injected ) {
				if ( ! $injected && 0 === strpos( $query, "UPDATE `{$table}`" ) ) {
					$injected = true;
					$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET extras = %s WHERE ID = %d", wp_json_encode( [ 'is_after_submission_process_triggered' => true, 'claimed_by' => 'other' ] ), $entry_id ) ); // phpcs:ignore
				}
				return $query;
			}
		);

		$this->assertFalse( $this->invoke( 'claim_submission', [ $entry_id ] ) );
		$this->assertTrue( $injected, 'The competing claim must have been written.' );
		$this->assertSame( 'other', Entries::get( $entry_id )['extras']['claimed_by'], 'The winning claim must not be overwritten.' );
	}

	/**
	 * The hooks receive the stored submission with its form and entry IDs.
	 */
	public function test_trigger_after_submission_process() {
		$received = null;
		add_action(
			'srfm_after_submission_process',
			function ( $form_data ) use ( &$received ) {
				$this->hook_runs++;
				$received = $form_data;
			}
		);

		$set = function ( $name, $value ) {
			$property = new ReflectionProperty( Background_Process::class, $name );
			$property->setAccessible( true );
			$property->setValue( $this->background_process, $value );
		};
		$set( 'form_id', 7 );
		$set( 'submission_id', 11 );
		$set( 'submission_data', [ 'srfm-input-name' => 'Test' ] );

		$this->assertTrue( $this->background_process->trigger_after_submission_process() );
		$this->assertSame( 1, $this->hook_runs );
		$this->assertSame( 7, $received['form_id'] );
		$this->assertSame( 11, $received['submission_id'] );
		$this->assertSame( 'Test', $received['srfm-input-name'] );
	}
}
