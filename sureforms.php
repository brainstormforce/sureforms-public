<?php
/**
 * Plugin Name: SureForms
 * Plugin URI: https://www.brainstormforce.com
 * Description: A simple yet powerful way to create modern forms for your website.
 * Author: SureForms
 * Author URI: https://brainstormforce.com/
 * Version: 0.0.1
 * License: GPL v2
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
define( 'SUREFORMS_FILE', __FILE__ );
define( 'SUREFORMS_BASE', plugin_basename( SUREFORMS_FILE ) );
define( 'SUREFORMS_DIR', plugin_dir_path( SUREFORMS_FILE ) );
define( 'SUREFORMS_URL', plugins_url( '/', SUREFORMS_FILE ) );
define( 'SUREFORMS_VER', '0.0.1' );
define( 'SUREFORMS_SLUG', 'srfm' );
define( 'SUREFORMS_LOC', 'SureForms' );
// ------ ADDITIONAL CONSTANTS ------- //
define( 'SUREFORMS_FORMS_POST_TYPE', 'sureforms_form' );
define( 'SUREFORMS_ENTRIES_POST_TYPE', 'sureforms_entry' );

require_once 'plugin-loader.php';
