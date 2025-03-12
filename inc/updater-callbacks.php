<?php
/**
 * SureForms Updater Callbacks.
 * Provides static methods for the updater class.
 *
 * @package sureforms.
 * @since 1.0.0
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Updater class.
 *
 * @since 1.0.0
 */
class Updater_Callbacks {
	/**
	 * Update callback method to handle the default dynamic block options in the global settings.
	 *
	 * @since 1.0.2
	 * @return void
	 */
	public static function manage_default_dynamic_options() {

		$previous_options = get_option( 'get_default_dynamic_block_option' );

		if ( ! empty( $previous_options ) && is_array( $previous_options ) ) {
			update_option( 'srfm_default_dynamic_block_option', $previous_options );
			delete_option( 'get_default_dynamic_block_option' );
		}
	}

	/**
	 * Update callback method to handle the default dynamic block options in the global settings.
	 *
	 * @since 1.0.4
	 * @return void
	 */
	public static function manage_empty_default_dynamic_options() {

		$previous_options = get_option( 'srfm_default_dynamic_block_option' );

		if ( ! empty( $previous_options ) && is_array( $previous_options ) ) {
			// get default options values.
			$default_options = Helper::default_dynamic_block_option();
			// merge previous options with default options after filtering empty values.
			$previous_options = array_merge( $default_options, array_filter( $previous_options ) );
			// update the options.
			update_option( 'srfm_default_dynamic_block_option', $previous_options );
		}
	}

	/**
	 * Update callback method to handle the honeypot option in the global settings.
	 *
	 * @since 1.2.1
	 * @return void
	 */
	public static function manage_honeypot_option() {
		// Retrieve the previous general settings options.
		$general_options = get_option( 'srfm_general_settings_options' );
		if ( ! empty( $general_options ) && is_array( $general_options ) && isset( $general_options['srfm_honeypot'] ) ) {
			// Retrieve the security settings options.
			$security_options = get_option( 'srfm_security_settings_options' );
			if ( is_array( $security_options ) ) {
				// Set the honeypot setting in the security options.
				$security_options['srfm_honeypot'] = $general_options['srfm_honeypot'];
				// Update the security options.
				update_option( 'srfm_security_settings_options', $security_options );
			}
			// Remove the honeypot setting from the general options and update it.
			unset( $general_options['srfm_honeypot'] );
			update_option( 'srfm_general_settings_options', $general_options );
		}
	}

	/**
	 * Update callback method to handle the default dynamic block options in the global settings.
	 *
	 * @since 1.2.1
	 * @return void
	 */
	public static function manage_empty_global_dynamic_options() {
		$previous_options = get_option( 'srfm_default_dynamic_block_option' );
		$new_options      = Translatable::dynamic_messages();

		if ( ! empty( $previous_options ) && is_array( $previous_options ) ) {
			// Iterate and update the options.
			foreach ( $new_options as $key => $value ) {
				if ( ! isset( $previous_options[ $key ] ) ) {
					$previous_options[ $key ] = $value;
				}
			}
		} else {
			$previous_options = Helper::default_dynamic_block_option();
		}

		// update the options.
		update_option( 'srfm_default_dynamic_block_option', $previous_options );
	}
}
