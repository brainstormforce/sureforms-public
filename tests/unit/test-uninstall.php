<?php
/**
 * Tests for uninstall.php.
 *
 * Covers the opt-in gate and the boundary between the analytics buffer, which
 * the file removes, and everything else in srfm_options, which it must not. The
 * file is a top-level script rather than a class, so each test includes it
 * directly, the way WordPress does in uninstall_plugin().
 *
 * The `! defined( 'WP_UNINSTALL_PLUGIN' ) ` guard itself is not covered: the
 * constant has to be defined for every other test here, and a constant cannot be
 * unset again within a process. Plugin Check enforces that guard separately.
 *
 * @package sureforms
 * @since 2.13.0
 */

/**
 * Class Test_Uninstall
 *
 * @since 2.13.0
 */
class Test_Uninstall extends WP_UnitTestCase {

	/**
	 * Absolute path to the file under test.
	 *
	 * @var string
	 */
	private $uninstall_file;

	/**
	 * The buffer keys the file is expected to take.
	 *
	 * @var array<string>
	 */
	private $buffer_keys = [
		'usage_events_pending',
		'action_item_events',
		'action_item_impressions',
		'ai_dashboard_widget_uses',
		'edit_form_button_clicks',
		'ai_starter_usage',
		'onboarding_analytics',
		'user_active',
		'email_delivery_state_tracked',
		'remove_analytics_data_on_uninstall',
	];

	/**
	 * The keys the file must leave alone.
	 *
	 * @var array<string>
	 */
	private $kept_keys = [
		'usage_events_pushed',
		'onboarding_event_v2_flushed',
		'first_form_created_at',
		'payment_settings',
		'forms_per_page',
		'dismissed_action_items',
		'pointer_popup_accepted',
		'pointer_popup_dismissed',
		'plugin_banner_rotation',
		'remaining_promos',
		'db_repair_attempts',
	];

	/**
	 * Set up.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->uninstall_file = dirname( __DIR__, 2 ) . '/uninstall.php';

		// WP_UNINSTALL_PLUGIN is normally defined by uninstall_plugin(). It is a
		// constant, so it survives for the rest of the process once set; that is
		// harmless here because every test in this class wants it defined.
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'sureforms/sureforms.php' );
		}

		delete_option( 'srfm_options' );
	}

	/**
	 * Seed a representative row: the whole analytics buffer, the analytics
	 * bookkeeping that must survive it, and ordinary settings.
	 *
	 * @param mixed $opt_in Value to store for the opt-in key, or null to omit it.
	 * @return void
	 */
	private function seed( $opt_in = true ) {
		$options = [
			// Buffer: counters and the queue of events not yet sent.
			'usage_events_pending'         => [ [ 'event' => 'form_created' ] ],
			'action_item_events'           => [ 'dismissed' => 3 ],
			'action_item_impressions'      => [ 'banner' => 11 ],
			'ai_dashboard_widget_uses'     => 7,
			'edit_form_button_clicks'      => 42,
			'ai_starter_usage'             => [ 'contact' => 2 ],
			'onboarding_analytics'         => [ 'step' => 'done' ],
			'user_active'                  => true,
			'email_delivery_state_tracked' => 'healthy|wp_mail||no|unknown',

			// Analytics bookkeeping that is a guard, not a counter.
			'usage_events_pushed'          => [ 'first_form_created' ],
			'onboarding_event_v2_flushed'  => true,
			'first_form_created_at'        => 1700000000,

			// Ordinary settings and UI state.
			'payment_settings'             => [ 'stripe_secret' => 'sk_live_keep_me' ],
			'forms_per_page'               => 20,
			'dismissed_action_items'       => [ 'upgrade' ],
			'pointer_popup_accepted'       => 1700000001,
			'pointer_popup_dismissed'      => 0,
			'plugin_banner_rotation'       => 2,
			'remaining_promos'             => [ 'blackfriday' ],
			'db_repair_attempts'           => 1,
		];

		if ( null !== $opt_in ) {
			$options['remove_analytics_data_on_uninstall'] = $opt_in;
		}

		update_option( 'srfm_options', $options );
	}

	/**
	 * Run the file under test in its own scope, so the variables it declares at
	 * top level cannot leak into a test method.
	 *
	 * @return void
	 */
	private function run_uninstall() {
		$file = $this->uninstall_file;

		( static function () use ( $file ) {
			include $file;
		} )();
	}

	/**
	 * With no opt-in stored at all - the default for every existing install -
	 * uninstalling changes nothing.
	 *
	 * @return void
	 */
	public function test_absent_opt_in_is_a_no_op() {
		$this->seed( null );
		$before = get_option( 'srfm_options' );

		$this->run_uninstall();

		$this->assertSame( $before, get_option( 'srfm_options' ), 'the row must be byte-identical when nobody opted in' );
	}

	/**
	 * An explicit false is the same no-op: unticking the box has to turn the
	 * sweep off again, not merely fail to turn it on.
	 *
	 * @return void
	 */
	public function test_opt_in_false_is_a_no_op() {
		$this->seed( false );
		$before = get_option( 'srfm_options' );

		$this->run_uninstall();

		$this->assertSame( $before, get_option( 'srfm_options' ) );
	}

	/**
	 * Only boolean true arms the sweep.
	 *
	 * The handler stores a real boolean, but a hand-edited row or an options
	 * editor can leave a string behind. Under `! empty()` every one of these
	 * would read as consent; under `true !==` none of them do.
	 *
	 * @param mixed $stored Value sitting in the opt-in key.
	 * @return void
	 *
	 * @dataProvider provide_non_boolean_true_values
	 */
	public function test_only_boolean_true_arms_the_sweep( $stored ) {
		$this->seed( $stored );
		$before = get_option( 'srfm_options' );

		$this->run_uninstall();

		$this->assertSame( $before, get_option( 'srfm_options' ), 'a non-boolean value must not be read as consent' );
	}

	/**
	 * Values that are truthy, or look like consent, but are not boolean true.
	 *
	 * @return array<string,array<mixed>>
	 */
	public function provide_non_boolean_true_values() {
		return [
			'string one'   => [ '1' ],
			'string true'  => [ 'true' ],
			'string yes'   => [ 'yes' ],
			'string no'    => [ 'no' ],
			'string false' => [ 'false' ],
			'integer one'  => [ 1 ],
			'empty string' => [ '' ],
			'zero'         => [ 0 ],
		];
	}

	/**
	 * A corrupted row is a no-op rather than a fatal inside uninstall_plugin().
	 *
	 * A fatal there aborts delete_plugins() before the files are removed, so the
	 * admin gets a deletion that fails again on every retry.
	 *
	 * @return void
	 */
	public function test_non_array_row_is_a_no_op() {
		update_option( 'srfm_options', 'corrupted' );

		$this->run_uninstall();

		$this->assertSame( 'corrupted', get_option( 'srfm_options' ) );
	}

	/**
	 * The whole point: with consent given, every buffer key goes and nothing
	 * else moves.
	 *
	 * @return void
	 */
	public function test_opt_in_removes_only_the_buffer_keys() {
		$this->seed();
		$before = get_option( 'srfm_options' );

		$this->run_uninstall();

		$after = get_option( 'srfm_options' );

		$this->assertIsArray( $after, 'the row survives, because settings remain in it' );

		foreach ( $this->buffer_keys as $key ) {
			$this->assertArrayNotHasKey( $key, $after, "{$key} is analytics buffer and must be removed" );
		}

		foreach ( $this->kept_keys as $key ) {
			$this->assertArrayHasKey( $key, $after, "{$key} must survive an uninstall" );
			$this->assertSame( $before[ $key ], $after[ $key ], "{$key} must survive unchanged" );
		}

		// Nothing beyond the buffer list was taken.
		$this->assertSame(
			array_values( array_diff( array_keys( $before ), $this->buffer_keys ) ),
			array_keys( $after ),
			'the surviving keys must be exactly the seeded keys minus the buffer list'
		);
	}

	/**
	 * Consent is not inherited.
	 *
	 * Re-persisting the live toggle would let a reinstall carry a previous
	 * operator's decision, so a new administrator could lose data on a choice
	 * they never made.
	 *
	 * @return void
	 */
	public function test_opt_in_is_not_carried_forward() {
		$this->seed();

		$this->run_uninstall();

		$after = get_option( 'srfm_options' );

		$this->assertArrayNotHasKey( 'remove_analytics_data_on_uninstall', $after );
	}

	/**
	 * A row holding nothing but the buffer is taken rather than left empty.
	 *
	 * @return void
	 */
	public function test_row_is_deleted_when_only_buffer_keys_remain() {
		update_option(
			'srfm_options',
			[
				'user_active'                        => true,
				'edit_form_button_clicks'            => 5,
				'remove_analytics_data_on_uninstall' => true,
			]
		);

		$this->run_uninstall();

		$this->assertFalse( get_option( 'srfm_options', false ), 'an otherwise empty row is removed, not left behind as []' );
	}

	/**
	 * Consent given, but nothing buffered: the row is left exactly as found
	 * rather than rewritten.
	 *
	 * @return void
	 */
	public function test_row_without_buffer_keys_still_loses_the_opt_in() {
		update_option(
			'srfm_options',
			[
				'payment_settings'                   => [ 'stripe_secret' => 'sk_live_keep_me' ],
				'usage_events_pushed'                => [ 'first_form_created' ],
				'remove_analytics_data_on_uninstall' => true,
			]
		);

		$this->run_uninstall();

		$this->assertSame(
			[
				'payment_settings'    => [ 'stripe_secret' => 'sk_live_keep_me' ],
				'usage_events_pushed' => [ 'first_form_created' ],
			],
			get_option( 'srfm_options' )
		);
	}

	/**
	 * Keys this file has never heard of survive.
	 *
	 * The remove list is explicit, so a setting added after this file was written
	 * is kept by default. That is the safe direction for the list to drift.
	 *
	 * @return void
	 */
	public function test_unknown_keys_survive() {
		update_option(
			'srfm_options',
			[
				'some_future_setting'                => [ 'enabled' => true ],
				'user_active'                        => true,
				'remove_analytics_data_on_uninstall' => true,
			]
		);

		$this->run_uninstall();

		$this->assertSame(
			[ 'some_future_setting' => [ 'enabled' => true ] ],
			get_option( 'srfm_options' )
		);
	}

	/**
	 * Other plugins' rows, and the shared Brainstorm Force keys, are never
	 * touched - not even the ones named after this plugin.
	 *
	 * @return void
	 */
	public function test_other_options_are_untouched() {
		$this->seed();

		$others = [
			'srfm_general_settings_options' => [ 'srfm_form_analytics' => true ],
			'srfm_email_summary_options'    => [ 'enabled' => true ],
			'bsf_analytics_optin'           => 'yes',
			'sureforms_usage_optin'         => 'yes',
			'bsf_product_referers'          => [ 'sureforms' => 'x' ],
			'some_other_plugin_options'     => [ 'keep' => true ],
		];

		foreach ( $others as $name => $value ) {
			update_option( $name, $value );
		}

		$this->run_uninstall();

		foreach ( $others as $name => $value ) {
			$this->assertSame( $value, get_option( $name ), "{$name} is not ours to remove" );
		}
	}
}
