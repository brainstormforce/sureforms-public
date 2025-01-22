<?php
/**
 * Plugin Name: SureForms
 * Plugin URI: https://sureforms.com
 * Description: A simple yet powerful way to create modern forms for your website.
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: SureForms
 * Author URI: https://sureforms.com/
 * Version: 1.3.0
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
define( 'SRFM_VER', '1.3.0' );
define( 'SRFM_SLUG', 'srfm' );
// ------ ADDITIONAL CONSTANTS ------- //
define( 'SRFM_FORMS_POST_TYPE', 'sureforms_form' );
define( 'SRFM_ENTRIES', 'sureforms_entries' );
define( 'SRFM_WEBSITE', 'https://sureforms.com/' );
define( 'SRFM_AI_MIDDLEWARE', 'https://credits.startertemplates.com/sureforms/' );
define( 'SRFM_BILLING_PORTAL', 'https://billing.sureforms.com/' );
define( 'SRFM_PRO_RECOMMENDED_VER', '1.3.0' );

define( 'SRFM_SURETRIGGERS_INTEGRATION_BASE_URL', 'https://app.suretriggers.com/' );

require_once 'plugin-loader.php';
