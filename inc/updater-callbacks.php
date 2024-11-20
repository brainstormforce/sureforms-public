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

}
