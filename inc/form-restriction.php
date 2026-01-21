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
			// Check for form scheduling even if entry restriction is not enabled.
			$is_outside_schedule = self::is_form_outside_schedule( $form_restriction );
			return apply_filters( 'srfm_is_form_restricted', $is_outside_schedule, $form_id, $form_restriction, false, false, $is_outside_schedule );
		}

		$has_entries_limit_reached = self::has_entries_limit_reached( $form_id, $form_restriction );
		$scheduling_state          = self::get_form_scheduling_state( $form_restriction );
		$is_outside_schedule       = 'not_started' === $scheduling_state || 'ended' === $scheduling_state;

		$conversational_form            = get_post_meta( $form_id, '_srfm_conversational_form', true );
		$is_conversational_form_enabled = is_array( $conversational_form ) && isset( $conversational_form['is_cf_enabled'] ) ? $conversational_form['is_cf_enabled'] : false;
		if ( ( $has_entries_limit_reached || $is_outside_schedule ) && $is_conversational_form_enabled ) {
			add_filter( 'srfm_show_conversational_form_footer', '__return_false' );
		}

		/**
		 * If the form has reached the entries limit or is outside schedule, return true.
		 *
		 * @since 1.10.1
		 */
		return apply_filters(
			'srfm_is_form_restricted',
			$has_entries_limit_reached || $is_outside_schedule,
			$form_id,
			$form_restriction,
			$has_entries_limit_reached,
			false, // Deprecated: $has_time_limit_reached - now handled by scheduling.
			$is_outside_schedule
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
	 * Check if the form is outside its scheduled time period.
	 *
	 * @param array<string, mixed> $form_restriction The form restriction settings.
	 * @since 2.4.0
	 * @return bool True if form is outside schedule, false otherwise.
	 */
	public static function is_form_outside_schedule( $form_restriction ) {
		$scheduling_state = self::get_form_scheduling_state( $form_restriction );
		return 'not_started' === $scheduling_state || 'ended' === $scheduling_state;
	}

	/**
	 * Get the appropriate restriction message based on scheduling state.
	 *
	 * @param string               $scheduling_state The scheduling state ('not_started', 'ended', 'active', or 'disabled').
	 * @param array<string, mixed> $form_restriction The form restriction settings.
	 * @since 2.4.0
	 * @return string The appropriate restriction message.
	 */
	public static function get_restriction_message_by_state( $scheduling_state, $form_restriction ) {
		if ( 'not_started' === $scheduling_state ) {
			$message = $form_restriction['schedulingNotStartedMessage'] ?? __( 'This form is not yet available. Please check back after the scheduled start time.', 'sureforms' );
			return is_string( $message ) ? $message : __( 'This form is not yet available. Please check back after the scheduled start time.', 'sureforms' );
		}

		if ( 'ended' === $scheduling_state ) {
			$message = $form_restriction['schedulingEndedMessage'] ?? __( 'This form is no longer accepting submissions. The submission period has ended.', 'sureforms' );
			return is_string( $message ) ? $message : __( 'This form is no longer accepting submissions. The submission period has ended.', 'sureforms' );
		}

		// Default to entry limit message.
		$message = $form_restriction['message'] ?? Translatable::get_default_form_restriction_message();
		return is_string( $message ) ? $message : Translatable::get_default_form_restriction_message();
	}

	/**
	 * Get the scheduling state for a form.
	 *
	 * @param array<string, mixed> $form_restriction The form restriction settings.
	 * @since 2.4.0
	 * @return string 'not_started', 'ended', 'active', or 'disabled'.
	 */
	public static function get_form_scheduling_state( $form_restriction ) {
		$scheduling_status = $form_restriction['schedulingStatus'] ?? false;

		// Start date/time.
		$start_date     = $form_restriction['startDate'] ?? '';
		$start_hours    = Helper::get_string_value( $form_restriction['startHours'] ?? '12' );
		$start_minutes  = Helper::get_string_value( $form_restriction['startMinutes'] ?? '00' );
		$start_meridiem = Helper::get_string_value( $form_restriction['startMeridiem'] ?? 'AM' );

		// End date/time.
		$end_date     = $form_restriction['date'] ?? '';
		$end_hours    = Helper::get_string_value( $form_restriction['hours'] ?? '12' );
		$end_minutes  = Helper::get_string_value( $form_restriction['minutes'] ?? '00' );
		$end_meridiem = Helper::get_string_value( $form_restriction['meridiem'] ?? 'PM' );

		$dt                = new \DateTime( 'now', wp_timezone() );
		$current_timestamp = $dt->getTimestamp();

		// Check if before start time (only if scheduling is enabled).
		if ( $scheduling_status && ! empty( $start_date ) && is_string( $start_date ) ) {
			$start_timestamp = Helper::get_timestamp_from_string( $start_date, $start_hours, $start_minutes, $start_meridiem );

			if ( false !== $start_timestamp && is_int( $start_timestamp ) && $current_timestamp < $start_timestamp ) {
				return 'not_started';
			}
		}

		// Check if after end time (works for both scheduling and simple time limit).
		if ( $scheduling_status && ! empty( $end_date ) && is_string( $end_date ) ) {
			$end_timestamp = Helper::get_timestamp_from_string( $end_date, $end_hours, $end_minutes, $end_meridiem );

			if ( false !== $end_timestamp && is_int( $end_timestamp ) && $current_timestamp > $end_timestamp ) {
				return 'ended';
			}
		}

		// If scheduling is disabled but we haven't passed the end date, return 'disabled'.
		// If scheduling is enabled and we're within the window, return 'active'.
		return $scheduling_status ? 'active' : 'disabled';
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

		// Get the scheduling state and appropriate message.
		$scheduling_state         = self::get_form_scheduling_state( $form_restriction );
		$form_restriction_message = self::get_restriction_message_by_state( $scheduling_state, $form_restriction );

		$form_restriction_message = apply_filters( 'srfm_form_restriction_message', $form_restriction_message, $form_id, $form_restriction );

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
