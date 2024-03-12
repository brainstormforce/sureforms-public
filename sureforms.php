<?php
/**
 * Plugin Name: SureForms
 * Plugin URI: https://www.brainstormforce.com
 * Description: A simple yet powerful way to create modern forms for your website.
 * Author: SureForms
 * Author URI: https://brainstormforce.com/
 * Version: 0.0.4
 * License: GPLv2 or later
 * Text Domain: sureforms
 *
 * @package sureforms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Set constants
 */
define( 'SRFM_FILE', __FILE__ );
define( 'SRFM_BASENAME', plugin_basename( SRFM_FILE ) );
define( 'SRFM_DIR', plugin_dir_path( SRFM_FILE ) );
define( 'SRFM_URL', plugins_url( '/', SRFM_FILE ) );
define( 'SRFM_VER', '0.0.4' );
define( 'SRFM_SLUG', 'srfm' );
// ------ ADDITIONAL CONSTANTS ------- //
define( 'SRFM_FORMS_POST_TYPE', 'sureforms_form' );
define( 'SRFM_ENTRIES_POST_TYPE', 'sureforms_entry' );

require_once 'plugin-loader.php';
