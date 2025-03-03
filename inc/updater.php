<?php
/**
 * SureForms Updater.
 * Manages important update related to the plugin.
 *
 * @package sureforms.
 * @since 0.0.12
 */

namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Updater class.
 *
 * @since 0.0.12
 */
class Updater {
	use Get_Instance;

	/**
	 * Current DB saved version of SureForms.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $old_version;

	/**
	 * Constructor.
	 *
	 * @since 0.0.12
	 * @return void
	 */
	public function __construct() {
		// Get auto saved version number.
		$this->old_version = Helper::get_string_value( get_option( 'srfm-version', '' ) );
		/**
		 * Sets the `init` action with a priority of 10.
		 * Ensures that this initialization runs before the Pro plugin's `init` action.
		 * Allows the necessary setup to complete prior to the Pro plugin's execution.
		 */
		add_action( 'init', [ $this, 'init' ], 10 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'in_plugin_update_message-' . SRFM_BASENAME, [ $this, 'plugin_update_notification' ], 10 );
	}

	/**
	 * Whether or not to call the DB update methods.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function needs_db_update() {
		if ( ! $this->old_version ) {
			if ( ! empty( get_posts( [ 'post_type' => SRFM_FORMS_POST_TYPE ] ) ) ) {
				// Run db update if users have forms created. Edge case for the users directly updating from v0.0.10 or below.
				return true;
			}

			// If old version is empty and no forms are created, it means it is fresh setup. Hence, DB upgrade is not needed.
			return false;
		}

		$updater_callbacks = $this->get_updater_callbacks();

		if ( empty( $updater_callbacks ) ) {
			return false;
		}

		$versions = array_keys( $updater_callbacks );
		$latest   = $versions[ count( $versions ) - 1 ];

		return version_compare( $this->old_version, $latest, '<' );
	}

	/**
	 * This function will help us to determine the plugin version and update it.
	 * Any major change in the option can be handed here on the basis of last plugin version found in the database.
	 *
	 * @since 0.0.12
	 * @since 1.0.0 -- Added db updater callbacks support.
	 * @return void
	 */
	public function init() {
		if ( version_compare( SRFM_VER, $this->old_version, '=' ) ) {
			// Bail early because saved version is already updated and no change detected.
			return;
		}

		if ( $this->needs_db_update() ) {
			foreach ( $this->get_updater_callbacks() as $updater_version => $updater_callback_functions ) {
				if ( ! is_array( $updater_callback_functions ) ) {
					continue;
				}

				if ( $this->old_version && ! version_compare( $this->old_version, $updater_version, '<' ) ) {
					// Skip as SRFM saved version is not less than updaters version so db upgrade is not needed here.
					continue;
				}

				foreach ( $updater_callback_functions as $updater_callback_function ) {
					call_user_func( $updater_callback_function, $this->old_version );
				}
			}
		}

		// Finally update cache and DB with current version.
		$this->old_version = SRFM_VER;
		update_option( 'srfm-version', $this->old_version );
	}

	/**
	 * Plugin update notification.
	 * This function will help us to display the update notice to the user.
	 * before updating the plugin.
	 * this info is fetched from latest available readme from repository.
	 *
	 * @param array<mixed> $data Plugin data.
	 * @since 0.0.12
	 * @return void
	 */
	public function plugin_update_notification( $data ) {
		if ( ! empty( $data['upgrade_notice'] ) ) { ?>
			<hr class="srfm-plugin-update-notification__separator" />
			<div class="srfm-plugin-update-notification">
				<div class="srfm-plugin-update-notification__icon">
					<span class="dashicons dashicons-info"></span>
				</div>
				<div>
					<div class="srfm-plugin-update-notification__title">
						<?php echo esc_html__( 'Heads up!', 'sureforms' ); ?>
					</div>
					<div class="srfm-plugin-update-notification__message">
						<?php
						$upgrade_notice = is_string( $data['upgrade_notice'] ) ? $data['upgrade_notice'] : '';
							printf(
								wp_kses(
									$upgrade_notice,
									[ 'a' => [ 'href' => [] ] ]
								)
							);
						?>
					</div>
				</div>
			</div>
			<?php
		} //end if
	}

	/**
	 * Enqueue styles.
	 * This function will help us to enqueue the styles for the update notice.
	 *
	 * @since 0.0.12
	 * @return void
	 */
	public function enqueue_styles() {

		$screen = get_current_screen();
		if ( empty( $screen->id ) || 'plugins' !== $screen->id ) {
			return;
		}
		wp_enqueue_style( 'srfm-update-notice', SRFM_URL . 'admin/assets/css/update-notice.css', [], SRFM_VER );
	}

	/**
	 * Returns an array of DB updater callback functions.
	 *
	 * @since 1.0.0
	 * @return array<string,array<callable>>> Array of DB updater callback functions
	 */
	public function get_updater_callbacks() {
		return [
			'1.0.2' => [
				'SRFM\Inc\Updater_Callbacks::manage_default_dynamic_options',
			],
			'1.0.4' => [
				'SRFM\Inc\Updater_Callbacks::manage_empty_default_dynamic_options',
			],
			'1.2.1' => [
				'SRFM\Inc\Updater_Callbacks::manage_honeypot_option',
				'SRFM\Inc\Updater_Callbacks::manage_empty_global_dynamic_options',
			],
		];
	}
}
