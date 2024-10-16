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
	 * Constructor.
	 *
	 * @since 0.0.12
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'init' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'in_plugin_update_message-' . SRFM_BASENAME, [ $this, 'plugin_update_notification' ], 10 );
	}

	/**
	 * This function will help us to determine the plugin version and update it.
	 * Any major change in the option can be handed here on the basis of last plugin version found in the database.
	 *
	 * @since 0.0.12
	 * @return void
	 */
	public function init() {
		// Get auto saved version number.
		$saved_version = get_option( 'srfm-version', false );

		// Update auto saved version number.
		if ( ! $saved_version || ! is_string( $saved_version ) ) {

			// Update current version.
			update_option( 'srfm-version', SRFM_VER );
			return;
		}
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

}
