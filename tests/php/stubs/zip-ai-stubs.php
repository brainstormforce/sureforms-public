<?php

namespace ZipAI;

/**
 * Plugin_Loader
 *
 * @since 1.0.0
 */
class Loader
{
    /**
     * Initiator
     *
     * @since 1.0.0
     * @return object initialized object of class.
     */
    public static function get_instance()
    {
    }
    /**
     * Autoload classes.
     *
     * @param string $class class name.
     */
    public function autoload($class)
    {
    }
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
    }
    /**
     * Load Plugin Text Domain.
     * This will load the translation textdomain depending on the file priorities.
     *      1. Global Languages /wp-content/languages/zip-ai/ folder
     *      2. Local directory /wp-content/plugins/zip-ai/languages/ folder
     *
     * @since 1.0.0
     * @return void
     */
    public function load_textdomain()
    {
    }
    /**
     * Define the required constants.
     *
     * @since 1.0.0
     * @return void
     */
    public function define_constants()
    {
    }
    /**
     * Setup the required classes.
     *
     * @since 1.0.0
     * @return void
     */
    public function setup_classes()
    {
    }
}
namespace ZipAI\Classes;

/**
 * The Sidebar_Configurations Class.
 */
class Sidebar_Configurations
{
    /**
     * Initiator of this class.
     *
     * @since 1.0.0
     * @return object initialized object of this class.
     */
    public static function get_instance()
    {
    }
    /**
     * Constructor of this class.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct()
    {
    }
    /**
     * Register All Routes.
     *
     * @hooked - rest_api_init
     * @since 1.0.0
     * @return void
     */
    public function register_route()
    {
    }
    /**
     * Checks whether the value is boolean or not.
     *
     * @param mixed $value value to be checked.
     * @since 1.0.0
     * @return boolean
     */
    public function sanitize_boolean_field($value)
    {
    }
    /**
     * Fetches ai data from the middleware server - this will be merged with the get_credit_server_response() function.
     *
     * @param \WP_REST_Request $request request object.
     * @since 1.0.0
     * @return void
     */
    public function generate_ai_content($request)
    {
    }
    /**
     * Ajax handeler to verify the Zip AI authorization.
     *
     * @since 1.0.0
     * @return void
     */
    public function verify_authenticity()
    {
    }
    /**
     * Enqueue the AI Asssitant Sidebar assets.
     *
     * @return void
     * @since 1.0.0
     */
    public function load_sidebar_assets()
    {
    }
    /**
     * Add the Zip AI Assistant Sidebar to the admin bar.
     *
     * @param object $admin_bar The admin bar object.
     * @since 1.1.0
     * @return void
     */
    public function add_admin_trigger($admin_bar)
    {
    }
    /**
     * Render the AI Assistant Sidebar markup.
     *
     * @since 1.1.0
     * @return void
     */
    public static function render_sidebar_markup()
    {
    }
}
/**
 * The Module Class.
 */
class Module
{
    /**
     * Function to migrate older Zip AI settings into the new modular format.
     *
     * @since 1.0.5
     * @return void
     */
    public static function migrate_options()
    {
    }
    /**
     * Function to get all the availabe Zip AI Modules, after applying the filter.
     *
     * First all the filtered modules and the modules from the database will be fetched.
     * Then the database modules will be cross-checked against the valid filtered modules.
     * This is done so that even if a value exists in the database, if the product that is adding the filter is disabled, the feature will be considered as non-existent.
     * Finally the required data from the database will overwrite the filtered defaults, and only the valid modules will be returned for use.
     *
     * @since 1.0.5
     * @return array Array of all the available Zip AI Modules and their details.
     */
    public static function get_all_modules()
    {
    }
    /**
     * Enable Zip AI Module(s).
     *
     * If a string is passed, that module will be enabled if valid.
     * If an array is passed, all valid modules will be enabled.
     *
     * @param string|array $module_name Name of the module or an array of module names.
     * @since 1.0.5
     * @return boolean True if Zip AI module(s) has been enabled, false otherwise.
     */
    public static function enable($module_name)
    {
    }
    /**
     * Function to disable Zip AI Module(s).
     *
     * If a string is passed, that module will be disabled if valid.
     * If an array is passed, all valid modules will be disabled.
     *
     * @param string|array $module_name Name of the module or an array of module names.
     * @since 1.0.5
     * @return boolean True if Zip AI module(s) has been enabled, false otherwise.
     */
    public static function disable($module_name)
    {
    }
    /**
     * Function to check if Zip AI Module is enabled.
     *
     * @param string $module_name Name of the module.
     * @since 1.0.5
     * @return boolean True if Zip AI is enabled, false otherwise.
     */
    public static function is_enabled($module_name)
    {
    }
    /**
     * Enable the given Zip AI module if it exists, else create and enable it.
     *
     * @param array  $modules     The reference to the modules array that will be modified.
     * @param string $module_name The module name.
     * @since 1.1.0
     * @return void
     */
    public static function force_enabled(&$modules, $module_name)
    {
    }
}
/**
 * The Helper Class.
 */
class Helper
{
    /**
     * Get an option from the database.
     *
     * @param string  $key              The option key.
     * @param mixed   $default          The option default value if option is not available.
     * @param boolean $network_override Whether to allow the network admin setting to be overridden on subsites.
     * @since 1.0.0
     * @return mixed  The option value.
     */
    public static function get_admin_settings_option($key, $default = false, $network_override = false)
    {
    }
    /**
     * Update an option from the database.
     *
     * @param string $key              The option key.
     * @param mixed  $value            The value to update.
     * @param bool   $network_override Whether to allow the network_override admin setting to be overridden on subsites.
     * @since 1.0.0
     * @return bool True if the option was updated, false otherwise.
     */
    public static function update_admin_settings_option($key, $value, $network_override = false)
    {
    }
    /**
     * Delete an option from the database for.
     *
     * @param string  $key              The option key.
     * @param boolean $network_override Whether to allow the network admin setting to be overridden on subsites.
     * @since 1.0.0
     * @return void
     */
    public static function delete_admin_settings_option($key, $network_override = false)
    {
    }
    /**
     * Check if Zip AI is authorized.
     *
     * @since 1.0.0
     * @return boolean True if Zip AI is authorized, false otherwise.
     */
    public static function is_authorized()
    {
    }
    /**
     * Get the Zip AI Settings.
     *
     * If used with a key, it will return that specific setting.
     * If used without a key, it will return the entire settings array.
     *
     * @param string $key The setting key.
     * @param mixed  $default The default value to return if the setting is not found.
     * @since 1.0.0
     * @return mixed|array The setting value, or the default.
     */
    public static function get_setting($key = '', $default = array())
    {
    }
    /**
     * Get the Token Count for a given message.
     *
     * @param string $message The message to get the token count for.
     * @since 1.0.0
     * @return int The token count.
     */
    public static function get_token_count($message)
    {
    }
    /**
     * Get the Zip AI Response from the Zip Credit Server.
     *
     * @param string $endpoint The endpoint to get the response from.
     * @since 1.0.0
     * @return array The Zip AI Response.
     */
    public static function get_credit_server_response($endpoint)
    {
    }
    /**
     * Get a response from the ZipWP API server.
     *
     * @param string $endpoint The endpoint to get the response from.
     * @since 1.1.2
     * @return array The ZipWP API Response.
     */
    public static function get_zipwp_api_response($endpoint)
    {
    }
    /**
     * Get the decrypted auth token.
     *
     * @since 1.0.0
     * @return string The decrypted auth token.
     */
    public static function get_decrypted_auth_token()
    {
    }
    /**
     * Get the decrypted ZipWP token.
     *
     * @since 1.1.2
     * @return string The decrypted ZipWP token.
     */
    public static function get_decrypted_zipwp_token()
    {
    }
    /**
     * This helper function returns credit details.
     *
     * @since 1.0.0
     * @return array
     */
    public static function get_credit_details()
    {
    }
    /**
     * This helper function returns the current plan details.
     *
     * @since 1.1.2
     * @return array
     */
    public static function get_current_plan_details()
    {
    }
    /**
     * Get the authorization middleware url.
     *
     * @param array $params An array of parameters to add to the middleware URL.
     * @since 1.0.0
     * @return string The authorization middleware url.
     */
    public static function get_auth_middleware_url($params = [])
    {
    }
    /**
     * Get the revoke url for the auth token.
     *
     * @since 1.0.0
     * @return string The authorization revoke url.
     */
    public static function get_auth_revoke_url()
    {
    }
}
/**
 * The Utils Class.
 */
class Utils
{
    /**
     * Encrypt data using base64.
     *
     * @param string $input The input string which needs to be encrypted.
     * @since 1.0.0
     * @return string The encrypted string.
     */
    public static function encrypt($input)
    {
    }
    /**
     * Decrypt data using base64.
     *
     * @param string $input The input string which needs to be decrypted.
     * @since 1.0.0
     * @return string The decrypted string.
     */
    public static function decrypt($input)
    {
    }
}
/**
 * The Admin_Configurations Class.
 */
class Admin_Configurations
{
    /**
     * Initiator of this class.
     *
     * @since 1.0.0
     * @return object initialized object of this class.
     */
    public static function get_instance()
    {
    }
    /**
     * Constructor of this class.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct()
    {
    }
    /**
     * Add the Zip AI menu page.
     *
     * @since 1.0.0
     * @return void
     */
    public function setup_menu_page()
    {
    }
    /**
     * Verify if the user was given authorization to use Zip AI.
     *
     * @since 1.0.0
     * @return void
     */
    public function verify_authorization()
    {
    }
    /**
     * Setup the Ajax Event to Entirely Disable the Zip AI Library from loading.
     *
     * @since 1.0.5
     * @return void
     */
    public function disabler_ajax()
    {
    }
    /**
     * Setup the AI Assistant Toggle Ajax.
     *
     * @since 1.0.0
     * @return void
     */
    public function toggle_assistant_status_ajax()
    {
    }
    /**
     * Render the Zip AI Admin Settings Page.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_dashboard()
    {
    }
    /**
     * Load the Admin Settings and Scripts on initialization.
     *
     * @since 1.0.0
     * @return void
     */
    public function settings_admin_scripts()
    {
    }
    /**
     * Enqueues the needed CSS/JS for Zip AI's admin settings page.
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_styles_and_scripts()
    {
    }
    /**
     * Localize and Enqueue the Admin Scripts.
     *
     * @param array $localize The data to localize.
     * @since 1.0.0
     * @return void
     */
    public function localize_and_enqueue_admin_scripts($localize)
    {
    }
    /**
     * Add the footer link.
     *
     * @since 1.0.0
     * @return string The footer link.
     */
    public function add_footer_link()
    {
    }
}
/**
 * The Token_Calculator Class.
 *
 * @since 1.0.0
 */
class Token_Calculator
{
    /**
     * Get the GPT encoded tokens.
     *
     * @param string $text The text to encode.
     * @since 1.0.0
     * @return array The encoded tokens.
     */
    public static function gpt_encode($text)
    {
    }
    /**
     * Get the ranks of the BPE merges.
     *
     * @param array $x The BPE merges.
     * @param array $y The range.
     * @since 1.0.0
     * @return array The ranks.
     */
    public static function gpt_dict_zip($x, $y)
    {
    }
    /**
     * Get the UTF-8 character of the given string/token.
     *
     * @param string $str The string/token.
     * @since 1.0.0
     * @return string The UTF-8 character.
     */
    public static function gpt_utf8_encode($str)
    {
    }
    /**
     * Get the byte size of the given character.
     *
     * @param string $c The character.
     * @since 1.0.0
     * @return int The byte size.
     */
    public static function gpt_unichr($c)
    {
    }
    /**
     * Get the encoded BPE tokens.
     *
     * @param string $token The token.
     * @param array  $bpe_ranks The BPE ranks.
     * @param array  $cache The cache.
     */
    public static function gpt_bpe($token, $bpe_ranks, &$cache)
    {
    }
    /**
     * Split a string into UTF-8 characters.
     *
     * @param string $str The string.
     * @param int    $len The length - default 1.
     * @since 1.0.0
     * @return array The UTF-8 characters.
     */
    public static function gpt_split($str, $len = 1)
    {
    }
    /**
     * Get the pairs of a word.
     *
     * @param string $word The word.
     * @since 1.0.0
     * @return array The pairs.
     */
    public static function gpt_get_pairs($word)
    {
    }
    /**
     * Get the index of a value in an array.
     *
     * @param array  $arrax The array.
     * @param string $search_element The value to search for.
     * @param int    $from_index The index to start searching from.
     * @since 1.0.0
     * @return int The index.
     */
    public static function gpt_index_of($arrax, $search_element, $from_index)
    {
    }
    /**
     * Filter a variable.
     *
     * @param mixed $var The variable.
     * @since 1.0.0
     * @return bool Whether the variable is not null, false, or empty.
     */
    public static function gpt_filter($var)
    {
    }
}