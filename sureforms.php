<?php
/**
 * Plugin Name: SureForms
 * Description: A simple yet powerful way to create modern forms for your website.
 * Author: SureCart
 * Author URI: https://surecart.com
 * Version: 0.0.1
 * License: GPL v2
 * Text Domain: sureforms
 *
 * @package sureforms
 */

/**
 * Set constants
 */
define( 'SUREFORMS_FILE', __FILE__ );
define( 'SUREFORMS_BASE', plugin_basename( SUREFORMS_FILE ) );
define( 'SUREFORMS_DIR', plugin_dir_path( SUREFORMS_FILE ) );
define( 'SUREFORMS_URL', plugins_url( '/', SUREFORMS_FILE ) );
define( 'SUREFORMS_VER', 'X.X.X' );
// ------ ADDITIONAL CONSTANTS ------- //
define( 'SUREFORMS_FORMS_POST_TYPE', 'sureforms_form' );

require_once 'plugin-loader.php';
