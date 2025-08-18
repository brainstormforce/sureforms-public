<?php
/**
 * Create new Form with Template and return the form ID.
 *
 * @package sureforms.
 * @since 1.10.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Create New Form.
 *
 * @since 1.10.1
 */
class Form_Restriction {
	use Get_Instance;

	/**
	 * Get the restriction settings for a given form.
	 *
	 * @param int $form_id The ID of the form.
	 * @since 1.10.1
	 * @return array Associative array of restriction settings or empty array if invalid.
	 */
	public static function get_form_restriction_setting( $form_id ) {
		// Validate the form ID. Must be numeric and non-empty.
		if ( empty( $form_id ) || ! is_int( $form_id ) ) {
			return [];
		}

		// Get the raw restriction meta.
		$form_restriction_meta = get_post_meta( $form_id, '_srfm_form_restriction', true );

		if ( empty( $form_restriction_meta ) || ! is_string( $form_restriction_meta ) ) {
			return [];
		}

		// Decode the meta to an array.
		$form_restriction = json_decode( $form_restriction_meta, true );

		// Ensure it's a valid array.
		return is_array( $form_restriction ) ? $form_restriction : [];
	}

	/**
	 * Check if the form has reached the entry limit.
	 *
	 * @param int $form_id The ID of the form.
	 * @since 1.10.1
	 * @return bool True if form is restricted, false otherwise.
	 */
	public static function is_form_restricted( $form_id ) {

		if ( empty( $form_id ) || ! is_int( $form_id ) ) {
			return false; // Invalid form ID.
		}

		// Check for instant fom preview mode.
		$srfm_live_mode_data = Helper::get_instant_form_live_data();

		// Skip check in live mode.
		if (
		! empty( $srfm_live_mode_data ) &&
		is_array( $srfm_live_mode_data ) &&
		isset( $srfm_live_mode_data['live_mode'] )
		) {
			return false; // Skip check in live mode.
		}

		// Get parsed restriction settings.
		$form_restriction = self::get_form_restriction_setting( $form_id );

		// If the form restriction is empty or not an array, or if the status is not set, return false.
		if ( empty( $form_restriction ) || ! is_array( $form_restriction ) || empty( $form_restriction['status'] ) ) {
			return false; // No limit set.
		}

		$has_entries_limit_reached = self::has_entries_limit_reached( $form_id, $form_restriction );
		$has_time_limit_reached    = self::has_time_limit_reached( $form_restriction );

		/**
		 * If the form has reached the entries limit or the time limit, return true.
		 *
		 * @since 1.10.1
		 */
		return apply_filters(
			'srfm_is_form_restricted',
			$has_entries_limit_reached || $has_time_limit_reached,
			$form_id,
			$form_restriction,
			$has_entries_limit_reached,
			$has_time_limit_reached
		);
	}

	/**
	 * Check if the entries limit has been reached for a given form.
	 *
	 * @param int                  $form_id The ID of the form.
	 * @param array<string, mixed> $form_restriction The form restriction settings.
	 * @since 1.10.1
	 * @return bool True if the entries limit is reached, false otherwise.
	 */
	public static function has_entries_limit_reached( $form_id, $form_restriction = [] ) {

		if ( ! isset( $form_restriction['maxEntries'] ) || ! is_int( $form_restriction['maxEntries'] ) ) {
			return false; // Invalid form ID or restriction settings.
		}

		$max_entries   = $form_restriction['maxEntries'];
		$entries_count = Entries::get_total_entries_by_status( 'all', $form_id );

		if ( ! is_int( $entries_count ) ) {
			$entries_count = 0; // Ensure entries count is a non-negative integer.
		}

		return $entries_count >= $max_entries;
	}

	/**
	 * Check if the time limit has been reached based on the provided time period.
	 *
	 * @param array<string, mixed> $form_restriction The form restriction settings containing date, hours, minutes, and meridiem.
	 *
	 * @since 1.10.1
	 * @return bool True if the time limit has been reached, false otherwise.
	 */
	public static function has_time_limit_reached( $form_restriction ) {

		// Get date, hours, minutes, meridiem from the form restriction settings.
		$date     = $form_restriction['date'] ?? '';
		$hours    = Helper::get_string_value( $form_restriction['hours'] ?? '12' );
		$minutes  = Helper::get_string_value( $form_restriction['minutes'] ?? '00' );
		$meridiem = Helper::get_string_value( $form_restriction['meridiem'] ?? 'AM' );

		if ( empty( $date ) || ! is_string( $date ) ) {
			return false; // No time limit set.
		}

		// Convert the time period to a timestamp.
		$date_timestamp = Helper::get_timestamp_from_string( $date, $hours, $minutes, $meridiem );

		$has_time_limit_reached = false;

		// If the timestamp is valid, check if the current time is greater than the time period timestamp.
		// Ensure the timestamp is a valid integer and not false.
		if ( false !== $date_timestamp && is_int( $date_timestamp ) ) {
			$current_time_timestamp = strtotime( current_time( 'mysql' ) );
			// Check if the current time is greater than the time period timestamp.
			$has_time_limit_reached = $current_time_timestamp > $date_timestamp;
		}

		return $has_time_limit_reached;
	}

	/**
	 * Display the form restriction message.
	 *
	 * @param int $form_id The ID of the form.
	 * @since 1.10.1
	 * @return string|false The HTML markup for the restriction message or false if no restriction is set.
	 */
	public static function display_form_restriction_message( $form_id ) {
		// Get parsed restriction settings.
		$form_restriction = self::get_form_restriction_setting( $form_id );

		// Get the description text.
		$form_restriction_message = $form_restriction['message'] ?? Translatable::get_default_form_restriction_message();

		ob_start();
		?>
			<div class="srfm-form-container srfm-form-restriction-wrapper">
				<div class="srfm-form-restriction-message" role="alert" aria-live="assertive">
					<span class="srfm-form-restriction-icon" aria-hidden="true">
						<?php
							echo wp_kses(
								Helper::fetch_svg( 'instant-form-warning', 'srfm-form-restriction-icon', 'aria-hidden="true"' ),
								Helper::$allowed_tags_svg
							);
						?>
					</span>
					<p class="srfm-form-restriction-text">
						<?php echo esc_html( $form_restriction_message ); ?>
					</p>
				</div>
			</div>
		<?php
		return ob_get_clean();
	}

}
