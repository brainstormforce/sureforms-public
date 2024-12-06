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
	 * @since x.x.x
	 * @return void
	 */
	public static function manage_honeypot_option() {
		// Retrieve the previous general settings options.
		$general_options = get_option( 'srfm_general_settings_options' );
		if ( ! empty( $general_options ) && is_array( $general_options ) && isset( $general_options['srfm_honeypot'] ) ) {
			// Retrieve the security settings options.
			$security_options = get_option( 'srfm_security_settings_options', [] );
			// Set the honeypot setting in the security options.
			$security_options['srfm_honeypot'] = $general_options['srfm_honeypot'];
			// Update the security options.
			update_option( 'srfm_security_settings_options', $security_options );

			// Remove the honeypot setting from the general options and update it.
			unset( $general_options['srfm_honeypot'] );
			update_option( 'srfm_general_settings_options', $general_options );
		}
	}
}
