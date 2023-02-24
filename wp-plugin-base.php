<?php
/**
 * Plugin Name: Plugin starter code
 * Description: It is a started and simple which helps you to speedup the process.
 * Author: Sandesh
 * Version: X.X.X
 * License: GPL v2
 * Text Domain: wp-plugin-base
 *
 * @package {{package}}
 */

/**
 * Set constants
 */
define( 'WPB_FILE', __FILE__ );
define( 'WPB_BASE', plugin_basename( WPB_FILE ) );
define( 'WPB_DIR', plugin_dir_path( WPB_FILE ) );
define( 'WPB_URL', plugins_url( '/', WPB_FILE ) );
define( 'WPB_VER', 'X.X.X' );

require_once 'plugin-loader.php';
