<?php

namespace SRFM\Inc\Traits {
    /**
     * Trait Get_Instance.
     */
    trait Get_Instance
    {
        /**
         * Instance object.
         *
         * @var object Class Instance.
         */
        private static $instance = null;
        /**
         * Initiator
         *
         * @since 0.0.1
         * @return object initialized object of class.
         */
        public static function get_instance()
        {
        }
    }
}
namespace SRFM\Admin {
    /**
     * Admin handler class.
     *
     * @since 0.0.1
     */
    class Admin
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Class constructor.
         *
         * @return void
         * @since 0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Sureforms editor header styles.
         *
         * @since 0.0.1
         */
        public function enqueue_header_styles()
        {
        }
        /**
         * Add menu page.
         *
         * @return void
         * @since 0.0.1
         */
        public function add_menu_page()
        {
        }
        /**
         * Add Settings page.
         *
         * @return void
         * @since 0.0.1
         */
        public function settings_page()
        {
        }
        /**
         * Render Admin Dashboard.
         *
         * @return void
         * @since 0.0.1
         */
        public function render_dashboard()
        {
        }
        /**
         * Settings page callback.
         *
         * @return void
         * @since 0.0.1
         */
        public function settings_page_callback()
        {
        }
        /**
         * Add new form menu item.
         *
         * @return void
         * @since 0.0.1
         */
        public function add_new_form()
        {
        }
        /**
         * Add new form mentu item callback.
         *
         * @return void
         * @since 0.0.1
         */
        public function add_new_form_callback()
        {
        }
        /**
         * Adds a settings link to the plugin action links on the plugins page.
         *
         * @param array  $links An array of plugin action links.
         * @param string $file The plugin file path.
         * @return array The updated array of plugin action links.
         * @since 0.0.1
         */
        public function add_settings_link($links, $file)
        {
        }
        /**
         * Sureforms block editor styles.
         *
         * @since 0.0.1
         */
        public function enqueue_styles()
        {
        }
        /**
         * Get Breadcrumbs for current page.
         *
         * @since 0.0.1
         * @return array Breadcrumbs Array.
         */
        public function get_breadcrumbs_for_current_page()
        {
        }
        /**
         * Enqueue Admin Scripts.
         *
         * @return void
         * @since 0.0.1
         */
        public function enqueue_scripts()
        {
        }
        /**
         * Form Template Picker Admin Body Classes
         *
         * @since 0.0.1
         * @param string $classes Space separated class string.
         */
        public function admin_template_picker_body_class($classes = '')
        {
        }
        /**
         * Disable spectra's quick action bar in sureforms CPT.
         *
         * @param string $status current status of the quick action bar.
         * @since 0.0.3
         * @return string
         */
        public function restrict_spectra_quick_action_bar($status)
        {
        }
    }
}
namespace SRFM\Inc\Email {
    /**
     * Email Class
     *
     * @since 0.0.1
     */
    class Email_Template
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Class Constructor
         *
         * @since 0.0.1
         * @return void
         */
        public function __construct()
        {
        }
        /**
         * Get email header.
         *
         * @since 0.0.1
         * @return string|false
         */
        public function get_header()
        {
        }
        /**
         * Get email footer.
         *
         * @since 0.0.1
         * @return string|false footer tags.
         */
        public function get_footer()
        {
        }
        /**
         * Render email template.
         *
         * @param array<string, string> $fields Submission fields.
         * @param string                $email_body email body.
         * @since 0.0.1
         * @return string
         */
        public function render($fields, $email_body)
        {
        }
    }
}
namespace SRFM\Inc\Single_Form_Settings {
    /**
     * SureForms single form settings - Compliance settings.
     *
     * @since 0.0.3
     */
    class Compliance_Settings
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Constructor
         *
         * @since 0.0.3
         */
        public function __construct()
        {
        }
        /**
         * Runs every 24 hours for SureForms.
         * And check if auto delete entries are enabled for any forms.
         * If enabled then delete the entries that are older than the days_old.
         *
         * @hooked - srfm_daily_scheduled_action
         * @since 0.0.3
         * @return void
         */
        public function pre_auto_delete_entries()
        {
        }
        /**
         * Delete all the entries that are older than the days_old.
         *
         * @param int $days_old Number of days old.
         * @param int $form_id Form ID.
         * @since 0.0.3
         * @return void
         */
        public static function delete_old_entries($days_old, $form_id)
        {
        }
    }
}
namespace SRFM\Inc {
    /**
     * Sureforms Smart Tags Class.
     *
     * @since 0.0.1
     */
    class Smart_Tags
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Constructor
         *
         * @since  0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Smart Tag Render Function.
         *
         * @param string       $block_content Entire Block Content.
         * @param array<mixed> $block Block Properties As An Array.
         * @since 0.0.1
         * @return string
         */
        public function render_form($block_content, $block)
        {
        }
        /**
         * Check Form By ID.
         *
         * @param int|false $id Form id.
         * @since 0.0.1
         * @return bool
         */
        public function check_form_by_id($id)
        {
        }
        /**
         * Smart Tag List.
         *
         * @since 0.0.1
         * @return array<mixed>
         */
        public static function smart_tag_list()
        {
        }
        /**
         * Process Start Tag.
         *
         * @param string $content Form content.
         * @since 0.0.1
         * @return string
         */
        public function process_smart_tags($content)
        {
        }
        /**
         *  Smart Tag Callback.
         *
         * @param string $tags smart tag.
         * @since 0.0.1
         * @return mixed
         */
        public function smart_tags_callback($tags)
        {
        }
        /**
         * Get User IP.
         *
         * @since  0.0.1
         * @return string
         */
        public function get_the_user_ip()
        {
        }
        /**
         * Parse Request Query properties.
         *
         * @param string $value tag.
         * @since  0.0.1
         * @return array<mixed>|string
         */
        public static function parse_request_param($value)
        {
        }
    }
    /**
     * Public Class
     *
     * @since 0.0.1
     */
    class Admin_Ajax
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Constructor
         *
         * @since  0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Required Plugin Activate
         *
         * @return void
         * @since 0.0.1
         */
        public function required_plugin_activate()
        {
        }
        /**
         * Get ajax error message.
         *
         * @param string $type Message type.
         * @return string
         * @since 0.0.3
         */
        public function get_error_msg($type)
        {
        }
        /**
         * Localize the variables required for integration plugins.
         *
         * @param array<mixed> $values localized values.
         * @return array<mixed>
         * @since 0.0.1
         */
        public function localize_script_integration($values)
        {
        }
        /**
         *  Get sureforms recommended integrations.
         *
         * @since 0.0.1
         * @return array<mixed>
         */
        public function sureforms_get_integration()
        {
        }
        /**
         * Encodes the given string with base64.
         *
         * @param  string $logo contains svg's.
         * @return string
         */
        public function encode_svg($logo)
        {
        }
        /**
         * Get plugin status
         *
         * @since 0.0.1
         *
         * @param  string $plugin_init_file Plguin init file.
         * @return string
         */
        public static function get_plugin_status($plugin_init_file)
        {
        }
    }
}
namespace SRFM\Inc\Fields {
    /**
     * Field Base Class
     *
     * Defines the base class for form fields.
     *
     * @since 0.0.1
     */
    class Base
    {
        /**
         * Render the sureforms default
         *
         * @param array<mixed> $attributes Block attributes.
         *
         * @return string|boolean
         */
        public function markup($attributes)
        {
        }
    }
    /**
     * Sureforms Checkbox Markup Class.
     *
     * @since 0.0.1
     */
    class Checkbox_Markup extends \SRFM\Inc\Fields\Base
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Render the sureforms checkbox classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         *
         * @return string|boolean
         */
        public function markup($attributes)
        {
        }
    }
    /**
     * Sureforms Inline Button Markup Class.
     *
     * @since 0.0.3
     */
    class Inlinebutton_Markup extends \SRFM\Inc\Fields\Base
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Render inline button markup
         *
         * @param array<mixed> $attributes Block attributes.
         *
         * @return string|boolean|void
         * @since 0.0.3
         */
        public function markup($attributes)
        {
        }
    }
    /**
     * SureForms Email Markup Class.
     *
     * @since 0.0.1
     */
    class Email_Markup extends \SRFM\Inc\Fields\Base
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Render the sureforms email classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         * @return string|boolean
         */
        public function markup($attributes)
        {
        }
    }
    /**
     * SureForms Multichoice Markup Class.
     *
     * @since 0.0.1
     */
    class Multichoice_Markup extends \SRFM\Inc\Fields\Base
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Render the sureforms Multichoice classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         *
         * @return string|boolean
         */
        public function markup($attributes)
        {
        }
    }
    /**
     * Sureforms Dropdown Markup Class.
     *
     * @since 0.0.1
     */
    class Dropdown_Markup extends \SRFM\Inc\Fields\Base
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Render the sureforms dropdown classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         *
         * @return string|boolean
         */
        public function markup($attributes)
        {
        }
    }
    /**
     * Sureforms Url Field Markup Class.
     *
     * @since 0.0.1
     */
    class Url_Markup extends \SRFM\Inc\Fields\Base
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Render the sureforms url classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         *
         * @return string|boolean
         */
        public function markup($attributes)
        {
        }
    }
    /**
     * Sureforms GDPR Markup Class.
     *
     * @since 0.0.3
     */
    class GDPR_Markup extends \SRFM\Inc\Fields\Base
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Render the sureforms GDPR classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         *
         * @return string|boolean
         */
        public function markup($attributes)
        {
        }
    }
    /**
     * Sureforms Number Field Markup Class.
     *
     * @since 0.0.1
     */
    class Number_Markup extends \SRFM\Inc\Fields\Base
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Render the sureforms number classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         *
         * @return string|boolean
         */
        public function markup($attributes)
        {
        }
    }
    /**
     * Sureforms Textarea Markup Class.
     *
     * @since 0.0.1
     */
    class Textarea_Markup extends \SRFM\Inc\Fields\Base
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Render the sureforms textarea classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         *
         * @return string|boolean
         */
        public function markup($attributes)
        {
        }
    }
    /**
     * Sureforms_Phone_Markup Class.
     *
     * @since 0.0.1
     */
    class Phone_Markup extends \SRFM\Inc\Fields\Base
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Render the sureforms phone classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         *
         * @return string|boolean
         */
        public function markup($attributes)
        {
        }
    }
    /**
     * Sureforms Address Markup Class.
     *
     * @since 0.0.1
     */
    class Address_Markup extends \SRFM\Inc\Fields\Base
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Render the sureforms address classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content inner block content.
         *
         * @return string|boolean
         */
        public function markup($attributes, $content = '')
        {
        }
    }
    /**
     * Sureforms Address Compact Markup Class.
     *
     * @since 0.0.1
     */
    class Address_Compact_Markup extends \SRFM\Inc\Fields\Base
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Return Phone codes
         *
         * @return mixed|array<mixed|string> $data with phone codes
         */
        public function get_countries()
        {
        }
        /**
         * Render the sureforms address compact styling
         *
         * @param array<mixed> $attributes Block attributes.
         *
         * @return string|boolean
         */
        public function markup($attributes)
        {
        }
    }
    /**
     * Sureforms Input Markup Class.
     *
     * @since 0.0.1
     */
    class Input_Markup extends \SRFM\Inc\Fields\Base
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Render input markup
         *
         * @param array<mixed> $attributes Block attributes.
         *
         * @return string|boolean
         */
        public function markup($attributes)
        {
        }
    }
}
namespace SRFM\Inc {
    /**
     * Public Class
     *
     * @since 0.0.1
     */
    class Frontend_Assets
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Constructor
         *
         * @since  0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Enqueue Script.
         *
         * @return void
         * @since 0.0.1
         */
        public function enqueue_scripts()
        {
        }
        /**
         * Enqueue block scripts
         *
         * @param string $block_type block name.
         * @since 0.0.1
         * @return void
         */
        public function enqueue_srfm_script($block_type)
        {
        }
        /**
         * Render function.
         *
         * @param string        $block_content Entire Block Content.
         * @param array<string> $block Block Properties As An Array.
         * @return string
         */
        public function generate_render_script($block_content, $block)
        {
        }
        /**
         * Form Template filter.
         *
         * @param string $template Template.
         * @return string Template.
         * @since 0.0.1
         */
        public function page_template($template)
        {
        }
    }
    /**
     * Load Defaults Class.
     *
     * @since 0.0.1
     */
    class Export
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Constructor
         *
         * @since  0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Handle Export form
         *
         * @since 0.0.1
         * @return void
         */
        public function handle_export_form()
        {
        }
        /**
         * Handle Import Form
         *
         * @param \WP_REST_Request $request Full details about the request.
         *
         * @since 0.0.1
         * @return void
         */
        public function handle_import_form($request)
        {
        }
        /**
         * Add custom API Route submit-form
         *
         * @return void
         * @since 0.0.1
         */
        public function register_custom_endpoint()
        {
        }
    }
}
namespace {
    /**
     * ActionScheduler Exception Interface.
     *
     * Facilitates catching Exceptions unique to Action Scheduler.
     *
     * @package ActionScheduler
     * @since 2.1.0
     */
    interface ActionScheduler_Exception
    {
    }
    /**
     * InvalidAction Exception.
     *
     * Used for identifying actions that are invalid in some way.
     *
     * @package ActionScheduler
     */
    class ActionScheduler_InvalidActionException extends \InvalidArgumentException implements \ActionScheduler_Exception
    {
        /**
         * Create a new exception when the action's schedule cannot be fetched.
         *
         * @param string $action_id The action ID with bad args.
         * @return static
         */
        public static function from_schedule($action_id, $schedule)
        {
        }
        /**
         * Create a new exception when the action's args cannot be decoded to an array.
         *
         * @author Jeremy Pry
         *
         * @param string $action_id The action ID with bad args.
         * @return static
         */
        public static function from_decoding_args($action_id, $args = array())
        {
        }
    }
    /**
     * Class ActionScheduler_WPCommentCleaner
     *
     * @since 3.0.0
     */
    class ActionScheduler_WPCommentCleaner
    {
        /**
         * Post migration hook used to cleanup the WP comment table.
         *
         * @var string
         */
        protected static $cleanup_hook = 'action_scheduler/cleanup_wp_comment_logs';
        /**
         * An instance of the ActionScheduler_wpCommentLogger class to interact with the comments table.
         *
         * This instance should only be used as an interface. It should not be initialized.
         *
         * @var ActionScheduler_wpCommentLogger
         */
        protected static $wp_comment_logger = \null;
        /**
         * The key used to store the cached value of whether there are logs in the WP comment table.
         *
         * @var string
         */
        protected static $has_logs_option_key = 'as_has_wp_comment_logs';
        /**
         * Initialize the class and attach callbacks.
         */
        public static function init()
        {
        }
        /**
         * Determines if there are log entries in the wp comments table.
         *
         * Uses the flag set on migration completion set by @see self::maybe_schedule_cleanup().
         *
         * @return boolean Whether there are scheduled action comments in the comments table.
         */
        public static function has_logs()
        {
        }
        /**
         * Schedules the WP Post comment table cleanup to run in 6 months if it's not already scheduled.
         * Attached to the migration complete hook 'action_scheduler/migration_complete'.
         */
        public static function maybe_schedule_cleanup()
        {
        }
        /**
         * Delete all action comments from the WP Comments table.
         */
        public static function delete_all_action_comments()
        {
        }
        /**
         * Registers admin notices about the orphaned action logs.
         */
        public static function register_admin_notice()
        {
        }
        /**
         * Prints details about the orphaned action logs and includes information on where to learn more.
         */
        public static function print_admin_notice()
        {
        }
    }
    /**
     * Abstract WP_Async_Request class.
     *
     * @abstract
     */
    abstract class WP_Async_Request
    {
        /**
         * Prefix
         *
         * (default value: 'wp')
         *
         * @var string
         * @access protected
         */
        protected $prefix = 'wp';
        /**
         * Action
         *
         * (default value: 'async_request')
         *
         * @var string
         * @access protected
         */
        protected $action = 'async_request';
        /**
         * Identifier
         *
         * @var mixed
         * @access protected
         */
        protected $identifier;
        /**
         * Data
         *
         * (default value: array())
         *
         * @var array
         * @access protected
         */
        protected $data = array();
        /**
         * Initiate new async request
         */
        public function __construct()
        {
        }
        /**
         * Set data used during the request
         *
         * @param array $data Data.
         *
         * @return $this
         */
        public function data($data)
        {
        }
        /**
         * Dispatch the async request
         *
         * @return array|WP_Error
         */
        public function dispatch()
        {
        }
        /**
         * Get query args
         *
         * @return array
         */
        protected function get_query_args()
        {
        }
        /**
         * Get query URL
         *
         * @return string
         */
        protected function get_query_url()
        {
        }
        /**
         * Get post args
         *
         * @return array
         */
        protected function get_post_args()
        {
        }
        /**
         * Maybe handle
         *
         * Check for correct nonce and pass to handler.
         */
        public function maybe_handle()
        {
        }
        /**
         * Handle
         *
         * Override this method to perform any actions required
         * during the async request.
         */
        protected abstract function handle();
    }
    /**
     * ActionScheduler_AsyncRequest_QueueRunner class.
     */
    class ActionScheduler_AsyncRequest_QueueRunner extends \WP_Async_Request
    {
        /**
         * Data store for querying actions
         *
         * @var ActionScheduler_Store
         * @access protected
         */
        protected $store;
        /**
         * Prefix for ajax hooks
         *
         * @var string
         * @access protected
         */
        protected $prefix = 'as';
        /**
         * Action for ajax hooks
         *
         * @var string
         * @access protected
         */
        protected $action = 'async_request_queue_runner';
        /**
         * Initiate new async request
         */
        public function __construct(\ActionScheduler_Store $store)
        {
        }
        /**
         * Handle async requests
         *
         * Run a queue, and maybe dispatch another async request to run another queue
         * if there are still pending actions after completing a queue in this request.
         */
        protected function handle()
        {
        }
        /**
         * If the async request runner is needed and allowed to run, dispatch a request.
         */
        public function maybe_dispatch()
        {
        }
        /**
         * Only allow async requests when needed.
         *
         * Also allow 3rd party code to disable running actions via async requests.
         */
        protected function allow()
        {
        }
        /**
         * Chaining async requests can crash MySQL. A brief sleep call in PHP prevents that.
         */
        protected function get_sleep_seconds()
        {
        }
    }
    /**
     * Class ActionScheduler_Logger
     *
     * @codeCoverageIgnore
     */
    abstract class ActionScheduler_Logger
    {
        /**
         * @return ActionScheduler_Logger
         */
        public static function instance()
        {
        }
        /**
         * @param string   $action_id
         * @param string   $message
         * @param DateTime $date
         *
         * @return string The log entry ID
         */
        public abstract function log($action_id, $message, \DateTime $date = \null);
        /**
         * @param string $entry_id
         *
         * @return ActionScheduler_LogEntry
         */
        public abstract function get_entry($entry_id);
        /**
         * @param string $action_id
         *
         * @return ActionScheduler_LogEntry[]
         */
        public abstract function get_logs($action_id);
        /**
         * @codeCoverageIgnore
         */
        public function init()
        {
        }
        public function hook_stored_action()
        {
        }
        public function unhook_stored_action()
        {
        }
        public function log_stored_action($action_id)
        {
        }
        public function log_canceled_action($action_id)
        {
        }
        public function log_started_action($action_id, $context = '')
        {
        }
        public function log_completed_action($action_id, $action = \null, $context = '')
        {
        }
        public function log_failed_action($action_id, \Exception $exception, $context = '')
        {
        }
        public function log_timed_out_action($action_id, $timeout)
        {
        }
        public function log_unexpected_shutdown($action_id, $error)
        {
        }
        public function log_reset_action($action_id)
        {
        }
        public function log_ignored_action($action_id, $context = '')
        {
        }
        /**
         * @param string         $action_id
         * @param Exception|NULL $exception The exception which occured when fetching the action. NULL by default for backward compatibility.
         *
         * @return ActionScheduler_LogEntry[]
         */
        public function log_failed_fetch_action($action_id, \Exception $exception = \null)
        {
        }
        public function log_failed_schedule_next_instance($action_id, \Exception $exception)
        {
        }
        /**
         * Bulk add cancel action log entries.
         *
         * Implemented here for backward compatibility. Should be implemented in parent loggers
         * for more performant bulk logging.
         *
         * @param array $action_ids List of action ID.
         */
        public function bulk_log_cancel_actions($action_ids)
        {
        }
    }
    /**
     * Class ActionScheduler_wpCommentLogger
     */
    class ActionScheduler_wpCommentLogger extends \ActionScheduler_Logger
    {
        const AGENT = 'ActionScheduler';
        const TYPE = 'action_log';
        /**
         * @param string   $action_id
         * @param string   $message
         * @param DateTime $date
         *
         * @return string The log entry ID
         */
        public function log($action_id, $message, \DateTime $date = \null)
        {
        }
        protected function create_wp_comment($action_id, $message, \DateTime $date)
        {
        }
        /**
         * @param string $entry_id
         *
         * @return ActionScheduler_LogEntry
         */
        public function get_entry($entry_id)
        {
        }
        /**
         * @param string $action_id
         *
         * @return ActionScheduler_LogEntry[]
         */
        public function get_logs($action_id)
        {
        }
        protected function get_comment($comment_id)
        {
        }
        /**
         * @param WP_Comment_Query $query
         */
        public function filter_comment_queries($query)
        {
        }
        /**
         * @param array            $clauses
         * @param WP_Comment_Query $query
         *
         * @return array
         */
        public function filter_comment_query_clauses($clauses, $query)
        {
        }
        /**
         * Make sure Action Scheduler logs are excluded from comment feeds, which use WP_Query, not
         * the WP_Comment_Query class handled by @see self::filter_comment_queries().
         *
         * @param string   $where
         * @param WP_Query $query
         *
         * @return string
         */
        public function filter_comment_feed($where, $query)
        {
        }
        /**
         * Return a SQL clause to exclude Action Scheduler comments.
         *
         * @return string
         */
        protected function get_where_clause()
        {
        }
        /**
         * Remove action log entries from wp_count_comments()
         *
         * @param array $stats
         * @param int   $post_id
         *
         * @return object
         */
        public function filter_comment_count($stats, $post_id)
        {
        }
        /**
         * Retrieve the comment counts from our cache, or the database if the cached version isn't set.
         *
         * @return object
         */
        protected function get_comment_count()
        {
        }
        /**
         * Delete comment count cache whenever there is new comment or the status of a comment changes. Cache
         * will be regenerated next time ActionScheduler_wpCommentLogger::filter_comment_count() is called.
         */
        public function delete_comment_count_cache()
        {
        }
        /**
         * @codeCoverageIgnore
         */
        public function init()
        {
        }
        public function disable_comment_counting()
        {
        }
        public function enable_comment_counting()
        {
        }
    }
    /**
     * Class ActionScheduler_Store_Deprecated
     *
     * @codeCoverageIgnore
     */
    abstract class ActionScheduler_Store_Deprecated
    {
        /**
         * Mark an action that failed to fetch correctly as failed.
         *
         * @since 2.2.6
         *
         * @param int $action_id The ID of the action.
         */
        public function mark_failed_fetch_action($action_id)
        {
        }
        /**
         * Add base hooks
         *
         * @since 2.2.6
         */
        protected static function hook()
        {
        }
        /**
         * Remove base hooks
         *
         * @since 2.2.6
         */
        protected static function unhook()
        {
        }
        /**
         * Get the site's local time.
         *
         * @deprecated 2.1.0
         * @return DateTimeZone
         */
        protected function get_local_timezone()
        {
        }
    }
    /**
     * Class ActionScheduler_Store
     *
     * @codeCoverageIgnore
     */
    abstract class ActionScheduler_Store extends \ActionScheduler_Store_Deprecated
    {
        const STATUS_COMPLETE = 'complete';
        const STATUS_PENDING = 'pending';
        const STATUS_RUNNING = 'in-progress';
        const STATUS_FAILED = 'failed';
        const STATUS_CANCELED = 'canceled';
        const DEFAULT_CLASS = 'ActionScheduler_wpPostStore';
        /** @var int */
        protected static $max_args_length = 191;
        /**
         * @param ActionScheduler_Action $action
         * @param DateTime               $scheduled_date Optional Date of the first instance
         *                      to store. Otherwise uses the first date of the action's
         *                      schedule.
         *
         * @return int The action ID
         */
        public abstract function save_action(\ActionScheduler_Action $action, \DateTime $scheduled_date = \null);
        /**
         * @param string $action_id
         *
         * @return ActionScheduler_Action
         */
        public abstract function fetch_action($action_id);
        /**
         * Find an action.
         *
         * Note: the query ordering changes based on the passed 'status' value.
         *
         * @param string $hook Action hook.
         * @param array  $params Parameters of the action to find.
         *
         * @return string|null ID of the next action matching the criteria or NULL if not found.
         */
        public function find_action($hook, $params = array())
        {
        }
        /**
         * Query for action count or list of action IDs.
         *
         * @since 3.3.0 $query['status'] accepts array of statuses instead of a single status.
         *
         * @param array  $query {
         *      Query filtering options.
         *
         *      @type string       $hook             The name of the actions. Optional.
         *      @type string|array $status           The status or statuses of the actions. Optional.
         *      @type array        $args             The args array of the actions. Optional.
         *      @type DateTime     $date             The scheduled date of the action. Used in UTC timezone. Optional.
         *      @type string       $date_compare     Operator for selecting by $date param. Accepted values are '!=', '>', '>=', '<', '<=', '='. Defaults to '<='.
         *      @type DateTime     $modified         The last modified date of the action. Used in UTC timezone. Optional.
         *      @type string       $modified_compare Operator for comparing $modified param. Accepted values are '!=', '>', '>=', '<', '<=', '='. Defaults to '<='.
         *      @type string       $group            The group the action belongs to. Optional.
         *      @type bool|int     $claimed          TRUE to find claimed actions, FALSE to find unclaimed actions, an int to find a specific claim ID. Optional.
         *      @type int          $per_page         Number of results to return. Defaults to 5.
         *      @type int          $offset           The query pagination offset. Defaults to 0.
         *      @type int          $orderby          Accepted values are 'hook', 'group', 'modified', 'date' or 'none'. Defaults to 'date'.
         *      @type string       $order            Accepted values are 'ASC' or 'DESC'. Defaults to 'ASC'.
         * }
         * @param string $query_type Whether to select or count the results. Default, select.
         *
         * @return string|array|null The IDs of actions matching the query. Null on failure.
         */
        public abstract function query_actions($query = array(), $query_type = 'select');
        /**
         * Run query to get a single action ID.
         *
         * @since 3.3.0
         *
         * @see ActionScheduler_Store::query_actions for $query arg usage but 'per_page' and 'offset' can't be used.
         *
         * @param array $query Query parameters.
         *
         * @return int|null
         */
        public function query_action($query)
        {
        }
        /**
         * Get a count of all actions in the store, grouped by status
         *
         * @return array
         */
        public abstract function action_counts();
        /**
         * Get additional action counts.
         *
         * - add past-due actions
         *
         * @return array
         */
        public function extra_action_counts()
        {
        }
        /**
         * @param string $action_id
         */
        public abstract function cancel_action($action_id);
        /**
         * @param string $action_id
         */
        public abstract function delete_action($action_id);
        /**
         * @param string $action_id
         *
         * @return DateTime The date the action is schedule to run, or the date that it ran.
         */
        public abstract function get_date($action_id);
        /**
         * @param int      $max_actions
         * @param DateTime $before_date Claim only actions schedule before the given date. Defaults to now.
         * @param array    $hooks       Claim only actions with a hook or hooks.
         * @param string   $group       Claim only actions in the given group.
         *
         * @return ActionScheduler_ActionClaim
         */
        public abstract function stake_claim($max_actions = 10, \DateTime $before_date = \null, $hooks = array(), $group = '');
        /**
         * @return int
         */
        public abstract function get_claim_count();
        /**
         * @param ActionScheduler_ActionClaim $claim
         */
        public abstract function release_claim(\ActionScheduler_ActionClaim $claim);
        /**
         * @param string $action_id
         */
        public abstract function unclaim_action($action_id);
        /**
         * @param string $action_id
         */
        public abstract function mark_failure($action_id);
        /**
         * @param string $action_id
         */
        public abstract function log_execution($action_id);
        /**
         * @param string $action_id
         */
        public abstract function mark_complete($action_id);
        /**
         * @param string $action_id
         *
         * @return string
         */
        public abstract function get_status($action_id);
        /**
         * @param string $action_id
         * @return mixed
         */
        public abstract function get_claim_id($action_id);
        /**
         * @param string $claim_id
         * @return array
         */
        public abstract function find_actions_by_claim_id($claim_id);
        /**
         * @param string $comparison_operator
         * @return string
         */
        protected function validate_sql_comparator($comparison_operator)
        {
        }
        /**
         * Get the time MySQL formated date/time string for an action's (next) scheduled date.
         *
         * @param ActionScheduler_Action $action
         * @param DateTime               $scheduled_date (optional)
         * @return string
         */
        protected function get_scheduled_date_string(\ActionScheduler_Action $action, \DateTime $scheduled_date = \null)
        {
        }
        /**
         * Get the time MySQL formated date/time string for an action's (next) scheduled date.
         *
         * @param ActionScheduler_Action $action
         * @param DateTime               $scheduled_date (optional)
         * @return string
         */
        protected function get_scheduled_date_string_local(\ActionScheduler_Action $action, \DateTime $scheduled_date = \null)
        {
        }
        /**
         * Validate that we could decode action arguments.
         *
         * @param mixed $args      The decoded arguments.
         * @param int   $action_id The action ID.
         *
         * @throws ActionScheduler_InvalidActionException When the decoded arguments are invalid.
         */
        protected function validate_args($args, $action_id)
        {
        }
        /**
         * Validate a ActionScheduler_Schedule object.
         *
         * @param mixed $schedule  The unserialized ActionScheduler_Schedule object.
         * @param int   $action_id The action ID.
         *
         * @throws ActionScheduler_InvalidActionException When the schedule is invalid.
         */
        protected function validate_schedule($schedule, $action_id)
        {
        }
        /**
         * InnoDB indexes have a maximum size of 767 bytes by default, which is only 191 characters with utf8mb4.
         *
         * Previously, AS wasn't concerned about args length, as we used the (unindex) post_content column. However,
         * with custom tables, we use an indexed VARCHAR column instead.
         *
         * @param  ActionScheduler_Action $action Action to be validated.
         * @throws InvalidArgumentException When json encoded args is too long.
         */
        protected function validate_action(\ActionScheduler_Action $action)
        {
        }
        /**
         * Cancel pending actions by hook.
         *
         * @since 3.0.0
         *
         * @param string $hook Hook name.
         *
         * @return void
         */
        public function cancel_actions_by_hook($hook)
        {
        }
        /**
         * Cancel pending actions by group.
         *
         * @since 3.0.0
         *
         * @param string $group Group slug.
         *
         * @return void
         */
        public function cancel_actions_by_group($group)
        {
        }
        /**
         * @return array
         */
        public function get_status_labels()
        {
        }
        /**
         * Check if there are any pending scheduled actions due to run.
         *
         * @param ActionScheduler_Action $action
         * @param DateTime               $scheduled_date (optional)
         * @return string
         */
        public function has_pending_actions_due()
        {
        }
        /**
         * Callable initialization function optionally overridden in derived classes.
         */
        public function init()
        {
        }
        /**
         * Callable function to mark an action as migrated optionally overridden in derived classes.
         */
        public function mark_migrated($action_id)
        {
        }
        /**
         * @return ActionScheduler_Store
         */
        public static function instance()
        {
        }
    }
    /**
     * Class ActionScheduler_HybridStore
     *
     * A wrapper around multiple stores that fetches data from both.
     *
     * @since 3.0.0
     */
    class ActionScheduler_HybridStore extends \ActionScheduler_Store
    {
        const DEMARKATION_OPTION = 'action_scheduler_hybrid_store_demarkation';
        /**
         * ActionScheduler_HybridStore constructor.
         *
         * @param Config $config Migration config object.
         */
        public function __construct(\Action_Scheduler\Migration\Config $config = \null)
        {
        }
        /**
         * Initialize the table data store tables.
         *
         * @codeCoverageIgnore
         */
        public function init()
        {
        }
        /**
         * When the actions table is created, set its autoincrement
         * value to be one higher than the posts table to ensure that
         * there are no ID collisions.
         *
         * @param string $table_name
         * @param string $table_suffix
         *
         * @return void
         * @codeCoverageIgnore
         */
        public function set_autoincrement($table_name, $table_suffix)
        {
        }
        /**
         * Find the first matching action from the secondary store.
         * If it exists, migrate it to the primary store immediately.
         * After it migrates, the secondary store will logically contain
         * the next matching action, so return the result thence.
         *
         * @param string $hook
         * @param array  $params
         *
         * @return string
         */
        public function find_action($hook, $params = [])
        {
        }
        /**
         * Find actions matching the query in the secondary source first.
         * If any are found, migrate them immediately. Then the secondary
         * store will contain the canonical results.
         *
         * @param array  $query
         * @param string $query_type Whether to select or count the results. Default, select.
         *
         * @return int[]
         */
        public function query_actions($query = [], $query_type = 'select')
        {
        }
        /**
         * Get a count of all actions in the store, grouped by status
         *
         * @return array Set of 'status' => int $count pairs for statuses with 1 or more actions of that status.
         */
        public function action_counts()
        {
        }
        /**
         * If any actions would have been claimed by the secondary store,
         * migrate them immediately, then ask the primary store for the
         * canonical claim.
         *
         * @param int           $max_actions
         * @param DateTime|null $before_date
         *
         * @return ActionScheduler_ActionClaim
         */
        public function stake_claim($max_actions = 10, \DateTime $before_date = \null, $hooks = array(), $group = '')
        {
        }
        /**
         * Save an action to the primary store.
         *
         * @param ActionScheduler_Action $action Action object to be saved.
         * @param DateTime               $date Optional. Schedule date. Default null.
         *
         * @return int The action ID
         */
        public function save_action(\ActionScheduler_Action $action, \DateTime $date = \null)
        {
        }
        /**
         * Retrieve an existing action whether migrated or not.
         *
         * @param int $action_id Action ID.
         */
        public function fetch_action($action_id)
        {
        }
        /**
         * Cancel an existing action whether migrated or not.
         *
         * @param int $action_id Action ID.
         */
        public function cancel_action($action_id)
        {
        }
        /**
         * Delete an existing action whether migrated or not.
         *
         * @param int $action_id Action ID.
         */
        public function delete_action($action_id)
        {
        }
        /**
         * Get the schedule date an existing action whether migrated or not.
         *
         * @param int $action_id Action ID.
         */
        public function get_date($action_id)
        {
        }
        /**
         * Mark an existing action as failed whether migrated or not.
         *
         * @param int $action_id Action ID.
         */
        public function mark_failure($action_id)
        {
        }
        /**
         * Log the execution of an existing action whether migrated or not.
         *
         * @param int $action_id Action ID.
         */
        public function log_execution($action_id)
        {
        }
        /**
         * Mark an existing action complete whether migrated or not.
         *
         * @param int $action_id Action ID.
         */
        public function mark_complete($action_id)
        {
        }
        /**
         * Get an existing action status whether migrated or not.
         *
         * @param int $action_id Action ID.
         */
        public function get_status($action_id)
        {
        }
        /**
         * Return which store an action is stored in.
         *
         * @param int  $action_id ID of the action.
         * @param bool $primary_first Optional flag indicating search the primary store first.
         * @return ActionScheduler_Store
         */
        protected function get_store_from_action_id($action_id, $primary_first = \false)
        {
        }
        /*
         * * * * * * * * * * * * * * * * * * * * * * * * * *
         * All claim-related functions should operate solely
         * on the primary store.
         * * * * * * * * * * * * * * * * * * * * * * * * * * */
        /**
         * Get the claim count from the table data store.
         */
        public function get_claim_count()
        {
        }
        /**
         * Retrieve the claim ID for an action from the table data store.
         *
         * @param int $action_id Action ID.
         */
        public function get_claim_id($action_id)
        {
        }
        /**
         * Release a claim in the table data store.
         *
         * @param ActionScheduler_ActionClaim $claim Claim object.
         */
        public function release_claim(\ActionScheduler_ActionClaim $claim)
        {
        }
        /**
         * Release claims on an action in the table data store.
         *
         * @param int $action_id Action ID.
         */
        public function unclaim_action($action_id)
        {
        }
        /**
         * Retrieve a list of action IDs by claim.
         *
         * @param int $claim_id Claim ID.
         */
        public function find_actions_by_claim_id($claim_id)
        {
        }
    }
    /**
     * Class ActionScheduler_wpPostStore_PostStatusRegistrar
     *
     * @codeCoverageIgnore
     */
    class ActionScheduler_wpPostStore_PostStatusRegistrar
    {
        public function register()
        {
        }
        /**
         * Build the args array for the post type definition
         *
         * @return array
         */
        protected function post_status_args()
        {
        }
        /**
         * Build the args array for the post type definition
         *
         * @return array
         */
        protected function post_status_failed_labels()
        {
        }
        /**
         * Build the args array for the post type definition
         *
         * @return array
         */
        protected function post_status_running_labels()
        {
        }
    }
    /**
     * Class ActionScheduler_wpPostStore_PostTypeRegistrar
     *
     * @codeCoverageIgnore
     */
    class ActionScheduler_wpPostStore_PostTypeRegistrar
    {
        public function register()
        {
        }
        /**
         * Build the args array for the post type definition
         *
         * @return array
         */
        protected function post_type_args()
        {
        }
    }
    /**
     * Class ActionScheduler_DBLogger
     *
     * Action logs data table data store.
     *
     * @since 3.0.0
     */
    class ActionScheduler_DBLogger extends \ActionScheduler_Logger
    {
        /**
         * Add a record to an action log.
         *
         * @param int      $action_id Action ID.
         * @param string   $message Message to be saved in the log entry.
         * @param DateTime $date Timestamp of the log entry.
         *
         * @return int     The log entry ID.
         */
        public function log($action_id, $message, \DateTime $date = \null)
        {
        }
        /**
         * Retrieve an action log entry.
         *
         * @param int $entry_id Log entry ID.
         *
         * @return ActionScheduler_LogEntry
         */
        public function get_entry($entry_id)
        {
        }
        /**
         * Retrieve an action's log entries from the database.
         *
         * @param int $action_id Action ID.
         *
         * @return ActionScheduler_LogEntry[]
         */
        public function get_logs($action_id)
        {
        }
        /**
         * Initialize the data store.
         *
         * @codeCoverageIgnore
         */
        public function init()
        {
        }
        /**
         * Delete the action logs for an action.
         *
         * @param int $action_id Action ID.
         */
        public function clear_deleted_action_logs($action_id)
        {
        }
        /**
         * Bulk add cancel action log entries.
         *
         * @param array $action_ids List of action ID.
         */
        public function bulk_log_cancel_actions($action_ids)
        {
        }
    }
    /**
     * Class ActionScheduler_wpPostStore_TaxonomyRegistrar
     *
     * @codeCoverageIgnore
     */
    class ActionScheduler_wpPostStore_TaxonomyRegistrar
    {
        public function register()
        {
        }
        protected function taxonomy_args()
        {
        }
    }
    /**
     * Class ActionScheduler_wpPostStore
     */
    class ActionScheduler_wpPostStore extends \ActionScheduler_Store
    {
        const POST_TYPE = 'scheduled-action';
        const GROUP_TAXONOMY = 'action-group';
        const SCHEDULE_META_KEY = '_action_manager_schedule';
        const DEPENDENCIES_MET = 'as-post-store-dependencies-met';
        /**
         * Local Timezone.
         *
         * @var DateTimeZone
         */
        protected $local_timezone = \null;
        /**
         * Save action.
         *
         * @param ActionScheduler_Action $action Scheduled Action.
         * @param DateTime               $scheduled_date Scheduled Date.
         *
         * @throws RuntimeException Throws an exception if the action could not be saved.
         * @return int
         */
        public function save_action(\ActionScheduler_Action $action, \DateTime $scheduled_date = \null)
        {
        }
        /**
         * Create post array.
         *
         * @param ActionScheduler_Action $action Scheduled Action.
         * @param DateTime               $scheduled_date Scheduled Date.
         *
         * @return array Returns an array of post data.
         */
        protected function create_post_array(\ActionScheduler_Action $action, \DateTime $scheduled_date = \null)
        {
        }
        /**
         * Save post array.
         *
         * @param array $post_array Post array.
         * @return int Returns the post ID.
         * @throws RuntimeException Throws an exception if the action could not be saved.
         */
        protected function save_post_array($post_array)
        {
        }
        /**
         * Filter insert post data.
         *
         * @param array $postdata Post data to filter.
         *
         * @return array
         */
        public function filter_insert_post_data($postdata)
        {
        }
        /**
         * Create a (probably unique) post name for scheduled actions in a more performant manner than wp_unique_post_slug().
         *
         * When an action's post status is transitioned to something other than 'draft', 'pending' or 'auto-draft, like 'publish'
         * or 'failed' or 'trash', WordPress will find a unique slug (stored in post_name column) using the wp_unique_post_slug()
         * function. This is done to ensure URL uniqueness. The approach taken by wp_unique_post_slug() is to iterate over existing
         * post_name values that match, and append a number 1 greater than the largest. This makes sense when manually creating a
         * post from the Edit Post screen. It becomes a bottleneck when automatically processing thousands of actions, with a
         * database containing thousands of related post_name values.
         *
         * WordPress 5.1 introduces the 'pre_wp_unique_post_slug' filter for plugins to address this issue.
         *
         * We can short-circuit WordPress's wp_unique_post_slug() approach using the 'pre_wp_unique_post_slug' filter. This
         * method is available to be used as a callback on that filter. It provides a more scalable approach to generating a
         * post_name/slug that is probably unique. Because Action Scheduler never actually uses the post_name field, or an
         * action's slug, being probably unique is good enough.
         *
         * For more backstory on this issue, see:
         * - https://github.com/woocommerce/action-scheduler/issues/44 and
         * - https://core.trac.wordpress.org/ticket/21112
         *
         * @param string $override_slug Short-circuit return value.
         * @param string $slug          The desired slug (post_name).
         * @param int    $post_ID       Post ID.
         * @param string $post_status   The post status.
         * @param string $post_type     Post type.
         * @return string
         */
        public function set_unique_post_slug($override_slug, $slug, $post_ID, $post_status, $post_type)
        {
        }
        /**
         * Save post schedule.
         *
         * @param int    $post_id  Post ID of the scheduled action.
         * @param string $schedule Schedule to save.
         *
         * @return void
         */
        protected function save_post_schedule($post_id, $schedule)
        {
        }
        /**
         * Save action group.
         *
         * @param int    $post_id Post ID.
         * @param string $group   Group to save.
         * @return void
         */
        protected function save_action_group($post_id, $group)
        {
        }
        /**
         * Fetch actions.
         *
         * @param int $action_id Action ID.
         * @return object
         */
        public function fetch_action($action_id)
        {
        }
        /**
         * Get post.
         *
         * @param string $action_id - Action ID.
         * @return WP_Post|null
         */
        protected function get_post($action_id)
        {
        }
        /**
         * Get NULL action.
         *
         * @return ActionScheduler_NullAction
         */
        protected function get_null_action()
        {
        }
        /**
         * Make action from post.
         *
         * @param WP_Post $post Post object.
         * @return WP_Post
         */
        protected function make_action_from_post($post)
        {
        }
        /**
         * Get action status by post status.
         *
         * @param string $post_status Post status.
         *
         * @throws InvalidArgumentException Throw InvalidArgumentException if $post_status not in known status fields returned by $this->get_status_labels().
         * @return string
         */
        protected function get_action_status_by_post_status($post_status)
        {
        }
        /**
         * Get post status by action status.
         *
         * @param string $action_status Action status.
         *
         * @throws InvalidArgumentException Throws InvalidArgumentException if $post_status not in known status fields returned by $this->get_status_labels().
         * @return string
         */
        protected function get_post_status_by_action_status($action_status)
        {
        }
        /**
         * Returns the SQL statement to query (or count) actions.
         *
         * @param array  $query            - Filtering options.
         * @param string $select_or_count  - Whether the SQL should select and return the IDs or just the row count.
         *
         * @throws InvalidArgumentException - Throw InvalidArgumentException if $select_or_count not count or select.
         * @return string SQL statement. The returned SQL is already properly escaped.
         */
        protected function get_query_actions_sql(array $query, $select_or_count = 'select')
        {
        }
        /**
         * Query for action count or list of action IDs.
         *
         * @since 3.3.0 $query['status'] accepts array of statuses instead of a single status.
         *
         * @see ActionScheduler_Store::query_actions for $query arg usage.
         *
         * @param array  $query      Query filtering options.
         * @param string $query_type Whether to select or count the results. Defaults to select.
         *
         * @return string|array|null The IDs of actions matching the query. Null on failure.
         */
        public function query_actions($query = array(), $query_type = 'select')
        {
        }
        /**
         * Get a count of all actions in the store, grouped by status
         *
         * @return array
         */
        public function action_counts()
        {
        }
        /**
         * Cancel action.
         *
         * @param int $action_id Action ID.
         *
         * @throws InvalidArgumentException If $action_id is not identified.
         */
        public function cancel_action($action_id)
        {
        }
        /**
         * Delete action.
         *
         * @param int $action_id Action ID.
         * @return void
         * @throws InvalidArgumentException If action is not identified.
         */
        public function delete_action($action_id)
        {
        }
        /**
         * Get date for claim id.
         *
         * @param int $action_id Action ID.
         * @return ActionScheduler_DateTime The date the action is schedule to run, or the date that it ran.
         */
        public function get_date($action_id)
        {
        }
        /**
         * Get Date GMT.
         *
         * @param int $action_id Action ID.
         *
         * @throws InvalidArgumentException If $action_id is not identified.
         * @return ActionScheduler_DateTime The date the action is schedule to run, or the date that it ran.
         */
        public function get_date_gmt($action_id)
        {
        }
        /**
         * Stake claim.
         *
         * @param int      $max_actions Maximum number of actions.
         * @param DateTime $before_date Jobs must be schedule before this date. Defaults to now.
         * @param array    $hooks       Claim only actions with a hook or hooks.
         * @param string   $group       Claim only actions in the given group.
         *
         * @return ActionScheduler_ActionClaim
         * @throws RuntimeException When there is an error staking a claim.
         * @throws InvalidArgumentException When the given group is not valid.
         */
        public function stake_claim($max_actions = 10, \DateTime $before_date = \null, $hooks = array(), $group = '')
        {
        }
        /**
         * Get claim count.
         *
         * @return int
         */
        public function get_claim_count()
        {
        }
        /**
         * Generate claim id.
         *
         * @return string
         */
        protected function generate_claim_id()
        {
        }
        /**
         * Claim actions.
         *
         * @param string   $claim_id    Claim ID.
         * @param int      $limit       Limit.
         * @param DateTime $before_date Should use UTC timezone.
         * @param array    $hooks       Claim only actions with a hook or hooks.
         * @param string   $group       Claim only actions in the given group.
         *
         * @return int The number of actions that were claimed.
         * @throws RuntimeException  When there is a database error.
         */
        protected function claim_actions($claim_id, $limit, \DateTime $before_date = \null, $hooks = array(), $group = '')
        {
        }
        /**
         * Get IDs of actions within a certain group and up to a certain date/time.
         *
         * @param string   $group The group to use in finding actions.
         * @param int      $limit The number of actions to retrieve.
         * @param DateTime $date  DateTime object representing cutoff time for actions. Actions retrieved will be
         *                        up to and including this DateTime.
         *
         * @return array IDs of actions in the appropriate group and before the appropriate time.
         * @throws InvalidArgumentException When the group does not exist.
         */
        protected function get_actions_by_group($group, $limit, \DateTime $date)
        {
        }
        /**
         * Find actions by claim ID.
         *
         * @param string $claim_id Claim ID.
         * @return array
         */
        public function find_actions_by_claim_id($claim_id)
        {
        }
        /**
         * Release claim.
         *
         * @param ActionScheduler_ActionClaim $claim Claim object to release.
         * @return void
         * @throws RuntimeException When the claim is not unlocked.
         */
        public function release_claim(\ActionScheduler_ActionClaim $claim)
        {
        }
        /**
         * Unclaim action.
         *
         * @param string $action_id Action ID.
         * @throws RuntimeException When unable to unlock claim on action ID.
         */
        public function unclaim_action($action_id)
        {
        }
        /**
         * Mark failure on action.
         *
         * @param int $action_id Action ID.
         *
         * @return void
         * @throws RuntimeException When unable to mark failure on action ID.
         */
        public function mark_failure($action_id)
        {
        }
        /**
         * Return an action's claim ID, as stored in the post password column
         *
         * @param int $action_id Action ID.
         * @return mixed
         */
        public function get_claim_id($action_id)
        {
        }
        /**
         * Return an action's status, as stored in the post status column
         *
         * @param int $action_id Action ID.
         *
         * @return mixed
         * @throws InvalidArgumentException When the action ID is invalid.
         */
        public function get_status($action_id)
        {
        }
        /**
         * Log Execution.
         *
         * @throws Exception If the action status cannot be updated to self::STATUS_RUNNING ('in-progress').
         *
         * @param string $action_id Action ID.
         */
        public function log_execution($action_id)
        {
        }
        /**
         * Record that an action was completed.
         *
         * @param string $action_id ID of the completed action.
         *
         * @throws InvalidArgumentException When the action ID is invalid.
         * @throws RuntimeException         When there was an error executing the action.
         */
        public function mark_complete($action_id)
        {
        }
        /**
         * Mark action as migrated when there is an error deleting the action.
         *
         * @param int $action_id Action ID.
         */
        public function mark_migrated($action_id)
        {
        }
        /**
         * Determine whether the post store can be migrated.
         *
         * @param [type] $setting - Setting value.
         * @return bool
         */
        public function migration_dependencies_met($setting)
        {
        }
        /**
         * InnoDB indexes have a maximum size of 767 bytes by default, which is only 191 characters with utf8mb4.
         *
         * Previously, AS wasn't concerned about args length, as we used the (unindex) post_content column. However,
         * as we prepare to move to custom tables, and can use an indexed VARCHAR column instead, we want to warn
         * developers of this impending requirement.
         *
         * @param ActionScheduler_Action $action Action object.
         */
        protected function validate_action(\ActionScheduler_Action $action)
        {
        }
        /**
         * (@codeCoverageIgnore)
         */
        public function init()
        {
        }
    }
    /**
     * Class ActionScheduler_DBStore
     *
     * Action data table data store.
     *
     * @since 3.0.0
     */
    class ActionScheduler_DBStore extends \ActionScheduler_Store
    {
        /** @var int */
        protected static $max_args_length = 8000;
        /** @var int */
        protected static $max_index_length = 191;
        /** @var array List of claim filters. */
        protected $claim_filters = ['group' => '', 'hooks' => '', 'exclude-groups' => ''];
        /**
         * Initialize the data store
         *
         * @codeCoverageIgnore
         */
        public function init()
        {
        }
        /**
         * Save an action, checks if this is a unique action before actually saving.
         *
         * @param ActionScheduler_Action $action         Action object.
         * @param \DateTime              $scheduled_date Optional schedule date. Default null.
         *
         * @return int                  Action ID.
         * @throws RuntimeException     Throws exception when saving the action fails.
         */
        public function save_unique_action(\ActionScheduler_Action $action, \DateTime $scheduled_date = \null)
        {
        }
        /**
         * Save an action. Can save duplicate action as well, prefer using `save_unique_action` instead.
         *
         * @param ActionScheduler_Action $action Action object.
         * @param \DateTime              $scheduled_date Optional schedule date. Default null.
         *
         * @return int Action ID.
         * @throws RuntimeException     Throws exception when saving the action fails.
         */
        public function save_action(\ActionScheduler_Action $action, \DateTime $scheduled_date = \null)
        {
        }
        /**
         * Generate a hash from json_encoded $args using MD5 as this isn't for security.
         *
         * @param string $args JSON encoded action args.
         * @return string
         */
        protected function hash_args($args)
        {
        }
        /**
         * Get action args query param value from action args.
         *
         * @param array $args Action args.
         * @return string
         */
        protected function get_args_for_query($args)
        {
        }
        /**
         * Get a group's ID based on its name/slug.
         *
         * @param string|array $slugs                The string name of a group, or names for several groups.
         * @param bool         $create_if_not_exists Whether to create the group if it does not already exist. Default, true - create the group.
         *
         * @return array The group IDs, if they exist or were successfully created. May be empty.
         */
        protected function get_group_ids($slugs, $create_if_not_exists = \true)
        {
        }
        /**
         * Create an action group.
         *
         * @param string $slug Group slug.
         *
         * @return int Group ID.
         */
        protected function create_group($slug)
        {
        }
        /**
         * Retrieve an action.
         *
         * @param int $action_id Action ID.
         *
         * @return ActionScheduler_Action
         */
        public function fetch_action($action_id)
        {
        }
        /**
         * Create a null action.
         *
         * @return ActionScheduler_NullAction
         */
        protected function get_null_action()
        {
        }
        /**
         * Create an action from a database record.
         *
         * @param object $data Action database record.
         *
         * @return ActionScheduler_Action|ActionScheduler_CanceledAction|ActionScheduler_FinishedAction
         */
        protected function make_action_from_db_record($data)
        {
        }
        /**
         * Returns the SQL statement to query (or count) actions.
         *
         * @since 3.3.0 $query['status'] accepts array of statuses instead of a single status.
         *
         * @param array  $query Filtering options.
         * @param string $select_or_count  Whether the SQL should select and return the IDs or just the row count.
         *
         * @return string SQL statement already properly escaped.
         * @throws InvalidArgumentException If the query is invalid.
         */
        protected function get_query_actions_sql(array $query, $select_or_count = 'select')
        {
        }
        /**
         * Query for action count or list of action IDs.
         *
         * @since 3.3.0 $query['status'] accepts array of statuses instead of a single status.
         *
         * @see ActionScheduler_Store::query_actions for $query arg usage.
         *
         * @param array  $query      Query filtering options.
         * @param string $query_type Whether to select or count the results. Defaults to select.
         *
         * @return string|array|null The IDs of actions matching the query. Null on failure.
         */
        public function query_actions($query = array(), $query_type = 'select')
        {
        }
        /**
         * Get a count of all actions in the store, grouped by status.
         *
         * @return array Set of 'status' => int $count pairs for statuses with 1 or more actions of that status.
         */
        public function action_counts()
        {
        }
        /**
         * Cancel an action.
         *
         * @param int $action_id Action ID.
         *
         * @return void
         * @throws \InvalidArgumentException If the action update failed.
         */
        public function cancel_action($action_id)
        {
        }
        /**
         * Cancel pending actions by hook.
         *
         * @since 3.0.0
         *
         * @param string $hook Hook name.
         *
         * @return void
         */
        public function cancel_actions_by_hook($hook)
        {
        }
        /**
         * Cancel pending actions by group.
         *
         * @param string $group Group slug.
         *
         * @return void
         */
        public function cancel_actions_by_group($group)
        {
        }
        /**
         * Bulk cancel actions.
         *
         * @since 3.0.0
         *
         * @param array $query_args Query parameters.
         */
        protected function bulk_cancel_actions($query_args)
        {
        }
        /**
         * Delete an action.
         *
         * @param int $action_id Action ID.
         * @throws \InvalidArgumentException If the action deletion failed.
         */
        public function delete_action($action_id)
        {
        }
        /**
         * Get the schedule date for an action.
         *
         * @param string $action_id Action ID.
         *
         * @return \DateTime The local date the action is scheduled to run, or the date that it ran.
         */
        public function get_date($action_id)
        {
        }
        /**
         * Get the GMT schedule date for an action.
         *
         * @param int $action_id Action ID.
         *
         * @throws \InvalidArgumentException If action cannot be identified.
         * @return \DateTime The GMT date the action is scheduled to run, or the date that it ran.
         */
        protected function get_date_gmt($action_id)
        {
        }
        /**
         * Stake a claim on actions.
         *
         * @param int       $max_actions Maximum number of action to include in claim.
         * @param \DateTime $before_date Jobs must be schedule before this date. Defaults to now.
         * @param array     $hooks Hooks to filter for.
         * @param string    $group Group to filter for.
         *
         * @return ActionScheduler_ActionClaim
         */
        public function stake_claim($max_actions = 10, \DateTime $before_date = \null, $hooks = array(), $group = '')
        {
        }
        /**
         * Generate a new action claim.
         *
         * @return int Claim ID.
         */
        protected function generate_claim_id()
        {
        }
        /**
         * Set a claim filter.
         *
         * @param string $filter_name Claim filter name.
         * @param mixed  $filter_values Values to filter.
         * @return void
         */
        public function set_claim_filter($filter_name, $filter_values)
        {
        }
        /**
         * Get the claim filter value.
         *
         * @param string $filter_name Claim filter name.
         * @return mixed
         */
        public function get_claim_filter($filter_name)
        {
        }
        /**
         * Mark actions claimed.
         *
         * @param string    $claim_id Claim Id.
         * @param int       $limit Number of action to include in claim.
         * @param \DateTime $before_date Should use UTC timezone.
         * @param array     $hooks Hooks to filter for.
         * @param string    $group Group to filter for.
         *
         * @return int The number of actions that were claimed.
         * @throws \InvalidArgumentException Throws InvalidArgumentException if group doesn't exist.
         * @throws \RuntimeException Throws RuntimeException if unable to claim action.
         */
        protected function claim_actions($claim_id, $limit, \DateTime $before_date = \null, $hooks = array(), $group = '')
        {
        }
        /**
         * Get the number of active claims.
         *
         * @return int
         */
        public function get_claim_count()
        {
        }
        /**
         * Return an action's claim ID, as stored in the claim_id column.
         *
         * @param string $action_id Action ID.
         * @return mixed
         */
        public function get_claim_id($action_id)
        {
        }
        /**
         * Retrieve the action IDs of action in a claim.
         *
         * @param  int $claim_id Claim ID.
         * @return int[]
         */
        public function find_actions_by_claim_id($claim_id)
        {
        }
        /**
         * Release actions from a claim and delete the claim.
         *
         * @param ActionScheduler_ActionClaim $claim Claim object.
         */
        public function release_claim(\ActionScheduler_ActionClaim $claim)
        {
        }
        /**
         * Remove the claim from an action.
         *
         * @param int $action_id Action ID.
         *
         * @return void
         */
        public function unclaim_action($action_id)
        {
        }
        /**
         * Mark an action as failed.
         *
         * @param int $action_id Action ID.
         * @throws \InvalidArgumentException Throw an exception if action was not updated.
         */
        public function mark_failure($action_id)
        {
        }
        /**
         * Add execution message to action log.
         *
         * @throws Exception If the action status cannot be updated to self::STATUS_RUNNING ('in-progress').
         *
         * @param int $action_id Action ID.
         *
         * @return void
         */
        public function log_execution($action_id)
        {
        }
        /**
         * Mark an action as complete.
         *
         * @param int $action_id Action ID.
         *
         * @return void
         * @throws \InvalidArgumentException Throw an exception if action was not updated.
         */
        public function mark_complete($action_id)
        {
        }
        /**
         * Get an action's status.
         *
         * @param int $action_id Action ID.
         *
         * @return string
         * @throws \InvalidArgumentException Throw an exception if not status was found for action_id.
         * @throws \RuntimeException Throw an exception if action status could not be retrieved.
         */
        public function get_status($action_id)
        {
        }
    }
    /**
     * Class ActionScheduler_LogEntry
     */
    class ActionScheduler_LogEntry
    {
        /**
         * @var int $action_id
         */
        protected $action_id = '';
        /**
         * @var string $message
         */
        protected $message = '';
        /**
         * @var Datetime $date
         */
        protected $date;
        /**
         * Constructor
         *
         * @param mixed    $action_id Action ID
         * @param string   $message   Message
         * @param Datetime $date    Datetime object with the time when this log entry was created. If this parameter is
         *                          not provided a new Datetime object (with current time) will be created.
         */
        public function __construct($action_id, $message, $date = \null)
        {
        }
        /**
         * Returns the date when this log entry was created
         *
         * @return Datetime
         */
        public function get_date()
        {
        }
        public function get_action_id()
        {
        }
        public function get_message()
        {
        }
    }
    /**
     * Abstract class for setting a basic lock to throttle some action.
     *
     * Class ActionScheduler_Lock
     */
    abstract class ActionScheduler_Lock
    {
        /** @var int */
        protected static $lock_duration = \MINUTE_IN_SECONDS;
        /**
         * Check if a lock is set for a given lock type.
         *
         * @param string $lock_type A string to identify different lock types.
         * @return bool
         */
        public function is_locked($lock_type)
        {
        }
        /**
         * Set a lock.
         *
         * To prevent race conditions, implementations should avoid setting the lock if the lock is already held.
         *
         * @param string $lock_type A string to identify different lock types.
         * @return bool
         */
        public abstract function set($lock_type);
        /**
         * If a lock is set, return the timestamp it was set to expiry.
         *
         * @param string $lock_type A string to identify different lock types.
         * @return bool|int False if no lock is set, otherwise the timestamp for when the lock is set to expire.
         */
        public abstract function get_expiration($lock_type);
        /**
         * Get the amount of time to set for a given lock. 60 seconds by default.
         *
         * @param string $lock_type A string to identify different lock types.
         * @return int
         */
        protected function get_duration($lock_type)
        {
        }
        /**
         * @return ActionScheduler_Lock
         */
        public static function instance()
        {
        }
    }
    /**
     * Provide a way to set simple transient locks to block behaviour
     * for up-to a given duration.
     *
     * Class ActionScheduler_OptionLock
     *
     * @since 3.0.0
     */
    class ActionScheduler_OptionLock extends \ActionScheduler_Lock
    {
        /**
         * Set a lock using options for a given amount of time (60 seconds by default).
         *
         * Using an autoloaded option avoids running database queries or other resource intensive tasks
         * on frequently triggered hooks, like 'init' or 'shutdown'.
         *
         * For example, ActionScheduler_QueueRunner->maybe_dispatch_async_request() uses a lock to avoid
         * calling ActionScheduler_QueueRunner->has_maximum_concurrent_batches() every time the 'shutdown',
         * hook is triggered, because that method calls ActionScheduler_QueueRunner->store->get_claim_count()
         * to find the current number of claims in the database.
         *
         * @param string $lock_type A string to identify different lock types.
         * @bool True if lock value has changed, false if not or if set failed.
         */
        public function set($lock_type)
        {
        }
        /**
         * If a lock is set, return the timestamp it was set to expiry.
         *
         * @param string $lock_type A string to identify different lock types.
         * @return bool|int False if no lock is set, otherwise the timestamp for when the lock is set to expire.
         */
        public function get_expiration($lock_type)
        {
        }
        /**
         * Get the key to use for storing the lock in the transient
         *
         * @param string $lock_type A string to identify different lock types.
         * @return string
         */
        protected function get_key($lock_type)
        {
        }
    }
    /**
     * Abstract class with common Queue Cleaner functionality.
     */
    abstract class ActionScheduler_Abstract_QueueRunner_Deprecated
    {
        /**
         * Get the maximum number of seconds a batch can run for.
         *
         * @deprecated 2.1.1
         * @return int The number of seconds.
         */
        protected function get_maximum_execution_time()
        {
        }
    }
    /**
     * Abstract class with common Queue Cleaner functionality.
     */
    abstract class ActionScheduler_Abstract_QueueRunner extends \ActionScheduler_Abstract_QueueRunner_Deprecated
    {
        /** @var ActionScheduler_QueueCleaner */
        protected $cleaner;
        /** @var ActionScheduler_FatalErrorMonitor */
        protected $monitor;
        /** @var ActionScheduler_Store */
        protected $store;
        /**
         * ActionScheduler_Abstract_QueueRunner constructor.
         *
         * @param ActionScheduler_Store             $store
         * @param ActionScheduler_FatalErrorMonitor $monitor
         * @param ActionScheduler_QueueCleaner      $cleaner
         */
        public function __construct(\ActionScheduler_Store $store = \null, \ActionScheduler_FatalErrorMonitor $monitor = \null, \ActionScheduler_QueueCleaner $cleaner = \null)
        {
        }
        /**
         * Process an individual action.
         *
         * @param int    $action_id The action ID to process.
         * @param string $context Optional identifer for the context in which this action is being processed, e.g. 'WP CLI' or 'WP Cron'
         *        Generally, this should be capitalised and not localised as it's a proper noun.
         */
        public function process_action($action_id, $context = '')
        {
        }
        /**
         * Schedule the next instance of the action if necessary.
         *
         * @param ActionScheduler_Action $action
         * @param int                    $action_id
         */
        protected function schedule_next_instance(\ActionScheduler_Action $action, $action_id)
        {
        }
        /**
         * Run the queue cleaner.
         *
         * @author Jeremy Pry
         */
        protected function run_cleanup()
        {
        }
        /**
         * Get the number of concurrent batches a runner allows.
         *
         * @return int
         */
        public function get_allowed_concurrent_batches()
        {
        }
        /**
         * Check if the number of allowed concurrent batches is met or exceeded.
         *
         * @return bool
         */
        public function has_maximum_concurrent_batches()
        {
        }
        /**
         * Get the maximum number of seconds a batch can run for.
         *
         * @return int The number of seconds.
         */
        protected function get_time_limit()
        {
        }
        /**
         * Get the number of seconds the process has been running.
         *
         * @return int The number of seconds.
         */
        protected function get_execution_time()
        {
        }
        /**
         * Check if the host's max execution time is (likely) to be exceeded if processing more actions.
         *
         * @param int $processed_actions The number of actions processed so far - used to determine the likelihood of exceeding the time limit if processing another action
         * @return bool
         */
        protected function time_likely_to_be_exceeded($processed_actions)
        {
        }
        /**
         * Get memory limit
         *
         * Based on WP_Background_Process::get_memory_limit()
         *
         * @return int
         */
        protected function get_memory_limit()
        {
        }
        /**
         * Memory exceeded
         *
         * Ensures the batch process never exceeds 90% of the maximum WordPress memory.
         *
         * Based on WP_Background_Process::memory_exceeded()
         *
         * @return bool
         */
        protected function memory_exceeded()
        {
        }
        /**
         * See if the batch limits have been exceeded, which is when memory usage is almost at
         * the maximum limit, or the time to process more actions will exceed the max time limit.
         *
         * Based on WC_Background_Process::batch_limits_exceeded()
         *
         * @param int $processed_actions The number of actions processed so far - used to determine the likelihood of exceeding the time limit if processing another action
         * @return bool
         */
        protected function batch_limits_exceeded($processed_actions)
        {
        }
        /**
         * Process actions in the queue.
         *
         * @author Jeremy Pry
         * @param string $context Optional identifer for the context in which this action is being processed, e.g. 'WP CLI' or 'WP Cron'
         *        Generally, this should be capitalised and not localised as it's a proper noun.
         * @return int The number of actions processed.
         */
        public abstract function run($context = '');
    }
    /**
     * Class ActionScheduler_QueueRunner
     */
    class ActionScheduler_QueueRunner extends \ActionScheduler_Abstract_QueueRunner
    {
        const WP_CRON_HOOK = 'action_scheduler_run_queue';
        const WP_CRON_SCHEDULE = 'every_minute';
        /** @var ActionScheduler_AsyncRequest_QueueRunner */
        protected $async_request;
        /**
         * @return ActionScheduler_QueueRunner
         * @codeCoverageIgnore
         */
        public static function instance()
        {
        }
        /**
         * ActionScheduler_QueueRunner constructor.
         *
         * @param ActionScheduler_Store             $store
         * @param ActionScheduler_FatalErrorMonitor $monitor
         * @param ActionScheduler_QueueCleaner      $cleaner
         */
        public function __construct(\ActionScheduler_Store $store = \null, \ActionScheduler_FatalErrorMonitor $monitor = \null, \ActionScheduler_QueueCleaner $cleaner = \null, \ActionScheduler_AsyncRequest_QueueRunner $async_request = \null)
        {
        }
        /**
         * @codeCoverageIgnore
         */
        public function init()
        {
        }
        /**
         * Hook check for dispatching an async request.
         */
        public function hook_dispatch_async_request()
        {
        }
        /**
         * Unhook check for dispatching an async request.
         */
        public function unhook_dispatch_async_request()
        {
        }
        /**
         * Check if we should dispatch an async request to process actions.
         *
         * This method is attached to 'shutdown', so is called frequently. To avoid slowing down
         * the site, it mitigates the work performed in each request by:
         * 1. checking if it's in the admin context and then
         * 2. haven't run on the 'shutdown' hook within the lock time (60 seconds by default)
         * 3. haven't exceeded the number of allowed batches.
         *
         * The order of these checks is important, because they run from a check on a value:
         * 1. in memory - is_admin() maps to $GLOBALS or the WP_ADMIN constant
         * 2. in memory - transients use autoloaded options by default
         * 3. from a database query - has_maximum_concurrent_batches() run the query
         *    $this->store->get_claim_count() to find the current number of claims in the DB.
         *
         * If all of these conditions are met, then we request an async runner check whether it
         * should dispatch a request to process pending actions.
         */
        public function maybe_dispatch_async_request()
        {
        }
        /**
         * Process actions in the queue. Attached to self::WP_CRON_HOOK i.e. 'action_scheduler_run_queue'
         *
         * The $context param of this method defaults to 'WP Cron', because prior to Action Scheduler 3.0.0
         * that was the only context in which this method was run, and the self::WP_CRON_HOOK hook had no context
         * passed along with it. New code calling this method directly, or by triggering the self::WP_CRON_HOOK,
         * should set a context as the first parameter. For an example of this, refer to the code seen in
         *
         * @see ActionScheduler_AsyncRequest_QueueRunner::handle()
         *
         * @param string $context Optional identifer for the context in which this action is being processed, e.g. 'WP CLI' or 'WP Cron'
         *        Generally, this should be capitalised and not localised as it's a proper noun.
         * @return int The number of actions processed.
         */
        public function run($context = 'WP Cron')
        {
        }
        /**
         * Process a batch of actions pending in the queue.
         *
         * Actions are processed by claiming a set of pending actions then processing each one until either the batch
         * size is completed, or memory or time limits are reached, defined by @see $this->batch_limits_exceeded().
         *
         * @param int    $size The maximum number of actions to process in the batch.
         * @param string $context Optional identifer for the context in which this action is being processed, e.g. 'WP CLI' or 'WP Cron'
         *        Generally, this should be capitalised and not localised as it's a proper noun.
         * @return int The number of actions processed.
         */
        protected function do_batch($size = 100, $context = '')
        {
        }
        /**
         * Flush the cache if possible (intended for use after a batch of actions has been processed).
         *
         * This is useful because running large batches can eat up memory and because invalid data can accrue in the
         * runtime cache, which may lead to unexpected results.
         */
        protected function clear_caches()
        {
        }
        public function add_wp_cron_schedule($schedules)
        {
        }
    }
    /**
     * Class ActionScheduler_Abstract_Schema
     *
     * @package Action_Scheduler
     *
     * @codeCoverageIgnore
     *
     * Utility class for creating/updating custom tables
     */
    abstract class ActionScheduler_Abstract_Schema
    {
        /**
         * @var int Increment this value in derived class to trigger a schema update.
         */
        protected $schema_version = 1;
        /**
         * @var string Schema version stored in database.
         */
        protected $db_version;
        /**
         * @var array Names of tables that will be registered by this class.
         */
        protected $tables = array();
        /**
         * Can optionally be used by concrete classes to carry out additional initialization work
         * as needed.
         */
        public function init()
        {
        }
        /**
         * Register tables with WordPress, and create them if needed.
         *
         * @param bool $force_update Optional. Default false. Use true to always run the schema update.
         *
         * @return void
         */
        public function register_tables($force_update = \false)
        {
        }
        /**
         * @param string $table The name of the table
         *
         * @return string The CREATE TABLE statement, suitable for passing to dbDelta
         */
        protected abstract function get_table_definition($table);
        /**
         * @param string $table
         *
         * @return string The full name of the table, including the
         *                table prefix for the current blog
         */
        protected function get_full_table_name($table)
        {
        }
        /**
         * Confirms that all of the tables registered by this schema class have been created.
         *
         * @return bool
         */
        public function tables_exist()
        {
        }
    }
    /**
     * Class ActionScheduler_StoreSchema
     *
     * @codeCoverageIgnore
     *
     * Creates custom tables for storing scheduled actions
     */
    class ActionScheduler_StoreSchema extends \ActionScheduler_Abstract_Schema
    {
        const ACTIONS_TABLE = 'actionscheduler_actions';
        const CLAIMS_TABLE = 'actionscheduler_claims';
        const GROUPS_TABLE = 'actionscheduler_groups';
        const DEFAULT_DATE = '0000-00-00 00:00:00';
        /**
         * @var int Increment this value to trigger a schema update.
         */
        protected $schema_version = 7;
        public function __construct()
        {
        }
        /**
         * Performs additional setup work required to support this schema.
         */
        public function init()
        {
        }
        protected function get_table_definition($table)
        {
        }
        /**
         * Update the actions table schema, allowing datetime fields to be NULL.
         *
         * This is needed because the NOT NULL constraint causes a conflict with some versions of MySQL
         * configured with sql_mode=NO_ZERO_DATE, which can for instance lead to tables not being created.
         *
         * Most other schema updates happen via ActionScheduler_Abstract_Schema::update_table(), however
         * that method relies on dbDelta() and this change is not possible when using that function.
         *
         * @param string $table Name of table being updated.
         * @param string $db_version The existing schema version of the table.
         */
        public function update_schema_5_0($table, $db_version)
        {
        }
    }
    /**
     * Class ActionScheduler_LoggerSchema
     *
     * @codeCoverageIgnore
     *
     * Creates a custom table for storing action logs
     */
    class ActionScheduler_LoggerSchema extends \ActionScheduler_Abstract_Schema
    {
        const LOG_TABLE = 'actionscheduler_logs';
        /**
         * @var int Increment this value to trigger a schema update.
         */
        protected $schema_version = 3;
        public function __construct()
        {
        }
        /**
         * Performs additional setup work required to support this schema.
         */
        public function init()
        {
        }
        protected function get_table_definition($table)
        {
        }
        /**
         * Update the logs table schema, allowing datetime fields to be NULL.
         *
         * This is needed because the NOT NULL constraint causes a conflict with some versions of MySQL
         * configured with sql_mode=NO_ZERO_DATE, which can for instance lead to tables not being created.
         *
         * Most other schema updates happen via ActionScheduler_Abstract_Schema::update_table(), however
         * that method relies on dbDelta() and this change is not possible when using that function.
         *
         * @param string $table Name of table being updated.
         * @param string $db_version The existing schema version of the table.
         */
        public function update_schema_3_0($table, $db_version)
        {
        }
    }
    /**
     * Class ActionScheduler_FatalErrorMonitor
     */
    class ActionScheduler_FatalErrorMonitor
    {
        public function __construct(\ActionScheduler_Store $store)
        {
        }
        public function attach(\ActionScheduler_ActionClaim $claim)
        {
        }
        public function detach()
        {
        }
        public function track_current_action($action_id)
        {
        }
        public function untrack_action()
        {
        }
        public function handle_unexpected_shutdown()
        {
        }
    }
    /**
     * Class ActionScheduler_Action
     */
    class ActionScheduler_Action
    {
        protected $hook = '';
        protected $args = array();
        /** @var ActionScheduler_Schedule */
        protected $schedule = \null;
        protected $group = '';
        /**
         * Priorities are conceptually similar to those used for regular WordPress actions.
         * Like those, a lower priority takes precedence over a higher priority and the default
         * is 10.
         *
         * Unlike regular WordPress actions, the priority of a scheduled action is strictly an
         * integer and should be kept within the bounds 0-255 (anything outside the bounds will
         * be brought back into the acceptable range).
         *
         * @var int
         */
        protected $priority = 10;
        public function __construct($hook, array $args = array(), \ActionScheduler_Schedule $schedule = \null, $group = '')
        {
        }
        /**
         * Executes the action.
         *
         * If no callbacks are registered, an exception will be thrown and the action will not be
         * fired. This is useful to help detect cases where the code responsible for setting up
         * a scheduled action no longer exists.
         *
         * @throws Exception If no callbacks are registered for this action.
         */
        public function execute()
        {
        }
        /**
         * @param string $hook
         */
        protected function set_hook($hook)
        {
        }
        public function get_hook()
        {
        }
        protected function set_schedule(\ActionScheduler_Schedule $schedule)
        {
        }
        /**
         * @return ActionScheduler_Schedule
         */
        public function get_schedule()
        {
        }
        protected function set_args(array $args)
        {
        }
        public function get_args()
        {
        }
        /**
         * @param string $group
         */
        protected function set_group($group)
        {
        }
        /**
         * @return string
         */
        public function get_group()
        {
        }
        /**
         * @return bool If the action has been finished
         */
        public function is_finished()
        {
        }
        /**
         * Sets the priority of the action.
         *
         * @param int $priority Priority level (lower is higher priority). Should be in the range 0-255.
         *
         * @return void
         */
        public function set_priority($priority)
        {
        }
        /**
         * Gets the action priority.
         *
         * @return int
         */
        public function get_priority()
        {
        }
    }
    /**
     * Class ActionScheduler_NullAction
     */
    class ActionScheduler_NullAction extends \ActionScheduler_Action
    {
        public function __construct($hook = '', array $args = array(), \ActionScheduler_Schedule $schedule = \null)
        {
        }
        public function execute()
        {
        }
    }
    /**
     * Class ActionScheduler_FinishedAction
     */
    class ActionScheduler_FinishedAction extends \ActionScheduler_Action
    {
        public function execute()
        {
        }
        public function is_finished()
        {
        }
    }
    /**
     * Class ActionScheduler_CanceledAction
     *
     * Stored action which was canceled and therefore acts like a finished action but should always return a null schedule,
     * regardless of schedule passed to its constructor.
     */
    class ActionScheduler_CanceledAction extends \ActionScheduler_FinishedAction
    {
        /**
         * @param string                   $hook
         * @param array                    $args
         * @param ActionScheduler_Schedule $schedule
         * @param string                   $group
         */
        public function __construct($hook, array $args = array(), \ActionScheduler_Schedule $schedule = \null, $group = '')
        {
        }
    }
    /**
     * Class ActionScheduler_Versions
     */
    class ActionScheduler_Versions
    {
        public function register($version_string, $initialization_callback)
        {
        }
        public function get_versions()
        {
        }
        public function latest_version()
        {
        }
        public function latest_version_callback()
        {
        }
        /**
         * @return ActionScheduler_Versions
         * @codeCoverageIgnore
         */
        public static function instance()
        {
        }
        /**
         * @codeCoverageIgnore
         */
        public static function initialize_latest_version()
        {
        }
    }
    /**
     * Class ActionScheduler_DataController
     *
     * The main plugin/initialization class for the data stores.
     *
     * Responsible for hooking everything up with WordPress.
     *
     * @package Action_Scheduler
     *
     * @since 3.0.0
     */
    class ActionScheduler_DataController
    {
        /** Action data store class name. */
        const DATASTORE_CLASS = 'ActionScheduler_DBStore';
        /** Logger data store class name. */
        const LOGGER_CLASS = 'ActionScheduler_DBLogger';
        /** Migration status option name. */
        const STATUS_FLAG = 'action_scheduler_migration_status';
        /** Migration status option value. */
        const STATUS_COMPLETE = 'complete';
        /** Migration minimum required PHP version. */
        const MIN_PHP_VERSION = '5.5';
        /**
         * Get a flag indicating whether the migration environment dependencies are met.
         *
         * @return bool
         */
        public static function dependencies_met()
        {
        }
        /**
         * Get a flag indicating whether the migration is complete.
         *
         * @return bool Whether the flag has been set marking the migration as complete
         */
        public static function is_migration_complete()
        {
        }
        /**
         * Mark the migration as complete.
         */
        public static function mark_migration_complete()
        {
        }
        /**
         * Unmark migration when a plugin is de-activated. Will not work in case of silent activation, for example in an update.
         * We do this to mitigate the bug of lost actions which happens if there was an AS 2.x to AS 3.x migration in the past, but that plugin is now
         * deactivated and the site was running on AS 2.x again.
         */
        public static function mark_migration_incomplete()
        {
        }
        /**
         * Set the action store class name.
         *
         * @param string $class Classname of the store class.
         *
         * @return string
         */
        public static function set_store_class($class)
        {
        }
        /**
         * Set the action logger class name.
         *
         * @param string $class Classname of the logger class.
         *
         * @return string
         */
        public static function set_logger_class($class)
        {
        }
        /**
         * Set the sleep time in seconds.
         *
         * @param integer $sleep_time The number of seconds to pause before resuming operation.
         */
        public static function set_sleep_time($sleep_time)
        {
        }
        /**
         * Set the tick count required for freeing memory.
         *
         * @param integer $free_ticks The number of ticks to free memory on.
         */
        public static function set_free_ticks($free_ticks)
        {
        }
        /**
         * Free memory if conditions are met.
         *
         * @param int $ticks Current tick count.
         */
        public static function maybe_free_memory($ticks)
        {
        }
        /**
         * Reduce memory footprint by clearing the database query and object caches.
         */
        public static function free_memory()
        {
        }
        /**
         * Connect to table datastores if migration is complete.
         * Otherwise, proceed with the migration if the dependencies have been met.
         */
        public static function init()
        {
        }
        /**
         * Singleton factory.
         */
        public static function instance()
        {
        }
    }
    /**
     * Class ActionScheduler_AdminView_Deprecated
     *
     * Store deprecated public functions previously found in the ActionScheduler_AdminView class.
     * Keeps them out of the way of the main class.
     *
     * @codeCoverageIgnore
     */
    class ActionScheduler_AdminView_Deprecated
    {
        public function action_scheduler_post_type_args($args)
        {
        }
        /**
         * Customise the post status related views displayed on the Scheduled Actions administration screen.
         *
         * @param array $views An associative array of views and view labels which can be used to filter the 'scheduled-action' posts displayed on the Scheduled Actions administration screen.
         * @return array $views An associative array of views and view labels which can be used to filter the 'scheduled-action' posts displayed on the Scheduled Actions administration screen.
         */
        public function list_table_views($views)
        {
        }
        /**
         * Do not include the "Edit" action for the Scheduled Actions administration screen.
         *
         * Hooked to the 'bulk_actions-edit-action-scheduler' filter.
         *
         * @param array $actions An associative array of actions which can be performed on the 'scheduled-action' post type.
         * @return array $actions An associative array of actions which can be performed on the 'scheduled-action' post type.
         */
        public function bulk_actions($actions)
        {
        }
        /**
         * Completely customer the columns displayed on the Scheduled Actions administration screen.
         *
         * Because we can't filter the content of the default title and date columns, we need to recreate our own
         * custom columns for displaying those post fields. For the column content, @see self::list_table_column_content().
         *
         * @param array $columns An associative array of columns that are use for the table on the Scheduled Actions administration screen.
         * @return array $columns An associative array of columns that are use for the table on the Scheduled Actions administration screen.
         */
        public function list_table_columns($columns)
        {
        }
        /**
         * Make our custom title & date columns use defaulting title & date sorting.
         *
         * @param array $columns An associative array of columns that can be used to sort the table on the Scheduled Actions administration screen.
         * @return array $columns An associative array of columns that can be used to sort the table on the Scheduled Actions administration screen.
         */
        public static function list_table_sortable_columns($columns)
        {
        }
        /**
         * Print the content for our custom columns.
         *
         * @param string $column_name The key for the column for which we should output our content.
         * @param int    $post_id The ID of the 'scheduled-action' post for which this row relates.
         */
        public static function list_table_column_content($column_name, $post_id)
        {
        }
        /**
         * Hide the inline "Edit" action for all 'scheduled-action' posts.
         *
         * Hooked to the 'post_row_actions' filter.
         *
         * @param array $actions An associative array of actions which can be performed on the 'scheduled-action' post type.
         * @return array $actions An associative array of actions which can be performed on the 'scheduled-action' post type.
         */
        public static function row_actions($actions, $post)
        {
        }
        /**
         * Run an action when triggered from the Action Scheduler administration screen.
         *
         * @codeCoverageIgnore
         */
        public static function maybe_execute_action()
        {
        }
        /**
         * Convert an interval of seconds into a two part human friendly string.
         *
         * The WordPress human_time_diff() function only calculates the time difference to one degree, meaning
         * even if an action is 1 day and 11 hours away, it will display "1 day". This funciton goes one step
         * further to display two degrees of accuracy.
         *
         * Based on Crontrol::interval() function by Edward Dale: https://wordpress.org/plugins/wp-crontrol/
         *
         * @param int $interval A interval in seconds.
         * @return string A human friendly string representation of the interval.
         */
        public static function admin_notices()
        {
        }
        /**
         * Filter search queries to allow searching by Claim ID (i.e. post_password).
         *
         * @param string   $orderby MySQL orderby string.
         * @param WP_Query $query Instance of a WP_Query object
         * @return string MySQL orderby string.
         */
        public function custom_orderby($orderby, $query)
        {
        }
        /**
         * Filter search queries to allow searching by Claim ID (i.e. post_password).
         *
         * @param string   $search MySQL search string.
         * @param WP_Query $query Instance of a WP_Query object
         * @return string MySQL search string.
         */
        public function search_post_password($search, $query)
        {
        }
        /**
         * Change messages when a scheduled action is updated.
         *
         * @param  array $messages
         * @return array
         */
        public function post_updated_messages($messages)
        {
        }
    }
    /**
     * Class ActionScheduler_AdminView
     *
     * @codeCoverageIgnore
     */
    class ActionScheduler_AdminView extends \ActionScheduler_AdminView_Deprecated
    {
        /** @var ActionScheduler_ListTable */
        protected $list_table;
        /**
         * @return ActionScheduler_AdminView
         * @codeCoverageIgnore
         */
        public static function instance()
        {
        }
        /**
         * @codeCoverageIgnore
         */
        public function init()
        {
        }
        public function system_status_report()
        {
        }
        /**
         * Registers action-scheduler into WooCommerce > System status.
         *
         * @param array $tabs An associative array of tab key => label.
         * @return array $tabs An associative array of tab key => label, including Action Scheduler's tabs
         */
        public function register_system_status_tab(array $tabs)
        {
        }
        /**
         * Include Action Scheduler's administration under the Tools menu.
         *
         * A menu under the Tools menu is important for backward compatibility (as that's
         * where it started), and also provides more convenient access than the WooCommerce
         * System Status page, and for sites where WooCommerce isn't active.
         */
        public function register_menu()
        {
        }
        /**
         * Triggers processing of any pending actions.
         */
        public function process_admin_ui()
        {
        }
        /**
         * Renders the Admin UI
         */
        public function render_admin_ui()
        {
        }
        /**
         * Get the admin UI object and process any requested actions.
         *
         * @return ActionScheduler_ListTable
         */
        protected function get_list_table()
        {
        }
        /**
         * Action: admin_notices
         *
         * Maybe check past-due actions, and print notice.
         *
         * @uses $this->check_pastdue_actions()
         */
        public function maybe_check_pastdue_actions()
        {
        }
        /**
         * Check past-due actions, and print notice.
         *
         * @todo update $link_url to "Past-due" filter when released (see issue #510, PR #511)
         */
        protected function check_pastdue_actions()
        {
        }
        /**
         * Provide more information about the screen and its data in the help tab.
         */
        public function add_help_tabs()
        {
        }
    }
    /**
     * Class ActionScheduler_Schedule
     */
    interface ActionScheduler_Schedule
    {
        /**
         * @param DateTime $after
         * @return DateTime|null
         */
        public function next(\DateTime $after = \null);
        /**
         * @return bool
         */
        public function is_recurring();
    }
    /**
     * Class ActionScheduler_Abstract_Schedule
     */
    abstract class ActionScheduler_Schedule_Deprecated implements \ActionScheduler_Schedule
    {
        /**
         * Get the date & time this schedule was created to run, or calculate when it should be run
         * after a given date & time.
         *
         * @param DateTime $after DateTime to calculate against.
         *
         * @return DateTime|null
         */
        public function next(\DateTime $after = \null)
        {
        }
    }
    /**
     * Class ActionScheduler_Abstract_Schedule
     */
    abstract class ActionScheduler_Abstract_Schedule extends \ActionScheduler_Schedule_Deprecated
    {
        /**
         * Timestamp equivalent of @see $this->scheduled_date
         *
         * @var int
         */
        protected $scheduled_timestamp = \null;
        /**
         * @param DateTime $date The date & time to run the action.
         */
        public function __construct(\DateTime $date)
        {
        }
        /**
         * Check if a schedule should recur.
         *
         * @return bool
         */
        public abstract function is_recurring();
        /**
         * Calculate when the next instance of this schedule would run based on a given date & time.
         *
         * @param DateTime $after
         * @return DateTime
         */
        protected abstract function calculate_next(\DateTime $after);
        /**
         * Get the next date & time when this schedule should run after a given date & time.
         *
         * @param DateTime $after
         * @return DateTime|null
         */
        public function get_next(\DateTime $after)
        {
        }
        /**
         * Get the date & time the schedule is set to run.
         *
         * @return DateTime|null
         */
        public function get_date()
        {
        }
        /**
         * For PHP 5.2 compat, since DateTime objects can't be serialized
         *
         * @return array
         */
        public function __sleep()
        {
        }
        public function __wakeup()
        {
        }
    }
    /**
     * Class ActionScheduler_SimpleSchedule
     */
    class ActionScheduler_SimpleSchedule extends \ActionScheduler_Abstract_Schedule
    {
        /**
         * @param DateTime $after
         *
         * @return DateTime|null
         */
        public function calculate_next(\DateTime $after)
        {
        }
        /**
         * @return bool
         */
        public function is_recurring()
        {
        }
        /**
         * Serialize schedule with data required prior to AS 3.0.0
         *
         * Prior to Action Scheduler 3.0.0, schedules used different property names to refer
         * to equivalent data. For example, ActionScheduler_IntervalSchedule::start_timestamp
         * was the same as ActionScheduler_SimpleSchedule::timestamp. Action Scheduler 3.0.0
         * aligned properties and property names for better inheritance. To guard against the
         * scheduled date for single actions always being seen as "now" if downgrading to
         * Action Scheduler < 3.0.0, we need to also store the data with the old property names
         * so if it's unserialized in AS < 3.0, the schedule doesn't end up with a null recurrence.
         *
         * @return array
         */
        public function __sleep()
        {
        }
        /**
         * Unserialize recurring schedules serialized/stored prior to AS 3.0.0
         *
         * Prior to Action Scheduler 3.0.0, schedules used different property names to refer
         * to equivalent data. For example, ActionScheduler_IntervalSchedule::start_timestamp
         * was the same as ActionScheduler_SimpleSchedule::timestamp. Action Scheduler 3.0.0
         * aligned properties and property names for better inheritance. To maintain backward
         * compatibility with schedules serialized and stored prior to 3.0, we need to correctly
         * map the old property names with matching visibility.
         */
        public function __wakeup()
        {
        }
    }
    /**
     * Class ActionScheduler_Abstract_RecurringSchedule
     */
    abstract class ActionScheduler_Abstract_RecurringSchedule extends \ActionScheduler_Abstract_Schedule
    {
        /**
         * Timestamp equivalent of @see $this->first_date
         *
         * @var int
         */
        protected $first_timestamp = \null;
        /**
         * The recurrance between each time an action is run using this schedule.
         * Used to calculate the start date & time. Can be a number of seconds, in the
         * case of ActionScheduler_IntervalSchedule, or a cron expression, as in the
         * case of ActionScheduler_CronSchedule. Or something else.
         *
         * @var mixed
         */
        protected $recurrence;
        /**
         * @param DateTime      $date The date & time to run the action.
         * @param mixed         $recurrence The data used to determine the schedule's recurrance.
         * @param DateTime|null $first (Optional) The date & time the first instance of this interval schedule ran. Default null, meaning this is the first instance.
         */
        public function __construct(\DateTime $date, $recurrence, \DateTime $first = \null)
        {
        }
        /**
         * @return bool
         */
        public function is_recurring()
        {
        }
        /**
         * Get the date & time of the first schedule in this recurring series.
         *
         * @return DateTime|null
         */
        public function get_first_date()
        {
        }
        /**
         * @return string
         */
        public function get_recurrence()
        {
        }
        /**
         * For PHP 5.2 compat, since DateTime objects can't be serialized
         *
         * @return array
         */
        public function __sleep()
        {
        }
        /**
         * Unserialize recurring schedules serialized/stored prior to AS 3.0.0
         *
         * Prior to Action Scheduler 3.0.0, schedules used different property names to refer
         * to equivalent data. For example, ActionScheduler_IntervalSchedule::start_timestamp
         * was the same as ActionScheduler_SimpleSchedule::timestamp. This was addressed in
         * Action Scheduler 3.0.0, where properties and property names were aligned for better
         * inheritance. To maintain backward compatibility with scheduled serialized and stored
         * prior to 3.0, we need to correctly map the old property names.
         */
        public function __wakeup()
        {
        }
    }
    /**
     * Class ActionScheduler_CronSchedule
     */
    class ActionScheduler_CronSchedule extends \ActionScheduler_Abstract_RecurringSchedule implements \ActionScheduler_Schedule
    {
        /**
         * Wrapper for parent constructor to accept a cron expression string and map it to a CronExpression for this
         * objects $recurrence property.
         *
         * @param DateTime              $start The date & time to run the action at or after. If $start aligns with the CronSchedule passed via $recurrence, it will be used. If it does not align, the first matching date after it will be used.
         * @param CronExpression|string $recurrence The CronExpression used to calculate the schedule's next instance.
         * @param DateTime|null         $first (Optional) The date & time the first instance of this interval schedule ran. Default null, meaning this is the first instance.
         */
        public function __construct(\DateTime $start, $recurrence, \DateTime $first = \null)
        {
        }
        /**
         * Calculate when an instance of this schedule would start based on a given
         * date & time using its the CronExpression.
         *
         * @param DateTime $after
         * @return DateTime
         */
        protected function calculate_next(\DateTime $after)
        {
        }
        /**
         * @return string
         */
        public function get_recurrence()
        {
        }
        /**
         * Serialize cron schedules with data required prior to AS 3.0.0
         *
         * Prior to Action Scheduler 3.0.0, reccuring schedules used different property names to
         * refer to equivalent data. For example, ActionScheduler_IntervalSchedule::start_timestamp
         * was the same as ActionScheduler_SimpleSchedule::timestamp. Action Scheduler 3.0.0
         * aligned properties and property names for better inheritance. To guard against the
         * possibility of infinite loops if downgrading to Action Scheduler < 3.0.0, we need to
         * also store the data with the old property names so if it's unserialized in AS < 3.0,
         * the schedule doesn't end up with a null recurrence.
         *
         * @return array
         */
        public function __sleep()
        {
        }
        /**
         * Unserialize cron schedules serialized/stored prior to AS 3.0.0
         *
         * For more background, @see ActionScheduler_Abstract_RecurringSchedule::__wakeup().
         */
        public function __wakeup()
        {
        }
    }
    /**
     * Class ActionScheduler_NullSchedule
     */
    class ActionScheduler_NullSchedule extends \ActionScheduler_SimpleSchedule
    {
        /** @var DateTime|null */
        protected $scheduled_date;
        /**
         * Make the $date param optional and default to null.
         *
         * @param null $date The date & time to run the action.
         */
        public function __construct(\DateTime $date = \null)
        {
        }
        /**
         * This schedule has no scheduled DateTime, so we need to override the parent __sleep()
         *
         * @return array
         */
        public function __sleep()
        {
        }
        public function __wakeup()
        {
        }
    }
    /**
     * Class ActionScheduler_SimpleSchedule
     */
    class ActionScheduler_CanceledSchedule extends \ActionScheduler_SimpleSchedule
    {
        /**
         * @param DateTime $after
         *
         * @return DateTime|null
         */
        public function calculate_next(\DateTime $after)
        {
        }
        /**
         * Cancelled actions should never have a next schedule, even if get_next()
         * is called with $after < $this->scheduled_date.
         *
         * @param DateTime $after
         * @return DateTime|null
         */
        public function get_next(\DateTime $after)
        {
        }
        /**
         * @return bool
         */
        public function is_recurring()
        {
        }
        /**
         * Unserialize recurring schedules serialized/stored prior to AS 3.0.0
         *
         * Prior to Action Scheduler 3.0.0, schedules used different property names to refer
         * to equivalent data. For example, ActionScheduler_IntervalSchedule::start_timestamp
         * was the same as ActionScheduler_SimpleSchedule::timestamp. Action Scheduler 3.0.0
         * aligned properties and property names for better inheritance. To maintain backward
         * compatibility with schedules serialized and stored prior to 3.0, we need to correctly
         * map the old property names with matching visibility.
         */
        public function __wakeup()
        {
        }
    }
    /**
     * Class ActionScheduler_IntervalSchedule
     */
    class ActionScheduler_IntervalSchedule extends \ActionScheduler_Abstract_RecurringSchedule implements \ActionScheduler_Schedule
    {
        /**
         * Calculate when this schedule should start after a given date & time using
         * the number of seconds between recurrences.
         *
         * @param DateTime $after
         * @return DateTime
         */
        protected function calculate_next(\DateTime $after)
        {
        }
        /**
         * @return int
         */
        public function interval_in_seconds()
        {
        }
        /**
         * Serialize interval schedules with data required prior to AS 3.0.0
         *
         * Prior to Action Scheduler 3.0.0, reccuring schedules used different property names to
         * refer to equivalent data. For example, ActionScheduler_IntervalSchedule::start_timestamp
         * was the same as ActionScheduler_SimpleSchedule::timestamp. Action Scheduler 3.0.0
         * aligned properties and property names for better inheritance. To guard against the
         * possibility of infinite loops if downgrading to Action Scheduler < 3.0.0, we need to
         * also store the data with the old property names so if it's unserialized in AS < 3.0,
         * the schedule doesn't end up with a null/false/0 recurrence.
         *
         * @return array
         */
        public function __sleep()
        {
        }
        /**
         * Unserialize interval schedules serialized/stored prior to AS 3.0.0
         *
         * For more background, @see ActionScheduler_Abstract_RecurringSchedule::__wakeup().
         */
        public function __wakeup()
        {
        }
    }
    /**
     * Class ActionScheduler_wcSystemStatus
     */
    class ActionScheduler_wcSystemStatus
    {
        /**
         * The active data stores
         *
         * @var ActionScheduler_Store
         */
        protected $store;
        /**
         * Constructor method for ActionScheduler_wcSystemStatus.
         *
         * @param ActionScheduler_Store $store Active store object.
         *
         * @return void
         */
        public function __construct($store)
        {
        }
        /**
         * Display action data, including number of actions grouped by status and the oldest & newest action in each status.
         *
         * Helpful to identify issues, like a clogged queue.
         */
        public function render()
        {
        }
        /**
         * Get oldest and newest scheduled dates for a given set of statuses.
         *
         * @param array $status_keys Set of statuses to find oldest & newest action for.
         * @return array
         */
        protected function get_oldest_and_newest($status_keys)
        {
        }
        /**
         * Get oldest or newest scheduled date for a given status.
         *
         * @param string $status Action status label/name string.
         * @param string $date_type Oldest or Newest.
         * @return DateTime
         */
        protected function get_action_status_date($status, $date_type = 'oldest')
        {
        }
        /**
         * Get oldest or newest scheduled date for a given status.
         *
         * @param array $status_labels Set of statuses to find oldest & newest action for.
         * @param array $action_counts Number of actions grouped by status.
         * @param array $oldest_and_newest Date of the oldest and newest action with each status.
         */
        protected function get_template($status_labels, $action_counts, $oldest_and_newest)
        {
        }
        /**
         * Is triggered when invoking inaccessible methods in an object context.
         *
         * @param string $name Name of method called.
         * @param array  $arguments Parameters to invoke the method with.
         *
         * @return mixed
         * @link https://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.methods
         */
        public function __call($name, $arguments)
        {
        }
    }
    /**
     * Class ActionScheduler_ActionFactory
     */
    class ActionScheduler_ActionFactory
    {
        /**
         * Return stored actions for given params.
         *
         * @param string                   $status The action's status in the data store.
         * @param string                   $hook The hook to trigger when this action runs.
         * @param array                    $args Args to pass to callbacks when the hook is triggered.
         * @param ActionScheduler_Schedule $schedule The action's schedule.
         * @param string                   $group A group to put the action in.
         *
         * phpcs:ignore Squiz.Commenting.FunctionComment.ExtraParamComment
         * @param int                      $priority The action priority.
         *
         * @return ActionScheduler_Action An instance of the stored action.
         */
        public function get_stored_action($status, $hook, array $args = array(), \ActionScheduler_Schedule $schedule = \null, $group = '')
        {
        }
        /**
         * Enqueue an action to run one time, as soon as possible (rather a specific scheduled time).
         *
         * This method creates a new action using the NullSchedule. In practice, this results in an action scheduled to
         * execute "now". Therefore, it will generally run as soon as possible but is not prioritized ahead of other actions
         * that are already past-due.
         *
         * @param string $hook The hook to trigger when this action runs.
         * @param array  $args Args to pass when the hook is triggered.
         * @param string $group A group to put the action in.
         *
         * @return int The ID of the stored action.
         */
        public function async($hook, $args = array(), $group = '')
        {
        }
        /**
         * Same as async, but also supports $unique param.
         *
         * @param string $hook The hook to trigger when this action runs.
         * @param array  $args Args to pass when the hook is triggered.
         * @param string $group A group to put the action in.
         * @param bool   $unique Whether to ensure the action is unique.
         *
         * @return int The ID of the stored action.
         */
        public function async_unique($hook, $args = array(), $group = '', $unique = \true)
        {
        }
        /**
         * Create single action.
         *
         * @param string $hook  The hook to trigger when this action runs.
         * @param array  $args  Args to pass when the hook is triggered.
         * @param int    $when  Unix timestamp when the action will run.
         * @param string $group A group to put the action in.
         *
         * @return int The ID of the stored action.
         */
        public function single($hook, $args = array(), $when = \null, $group = '')
        {
        }
        /**
         * Create single action only if there is no pending or running action with same name and params.
         *
         * @param string $hook The hook to trigger when this action runs.
         * @param array  $args Args to pass when the hook is triggered.
         * @param int    $when Unix timestamp when the action will run.
         * @param string $group A group to put the action in.
         * @param bool   $unique Whether action scheduled should be unique.
         *
         * @return int The ID of the stored action.
         */
        public function single_unique($hook, $args = array(), $when = \null, $group = '', $unique = \true)
        {
        }
        /**
         * Create the first instance of an action recurring on a given interval.
         *
         * @param string $hook The hook to trigger when this action runs.
         * @param array  $args Args to pass when the hook is triggered.
         * @param int    $first Unix timestamp for the first run.
         * @param int    $interval Seconds between runs.
         * @param string $group A group to put the action in.
         *
         * @return int The ID of the stored action.
         */
        public function recurring($hook, $args = array(), $first = \null, $interval = \null, $group = '')
        {
        }
        /**
         * Create the first instance of an action recurring on a given interval only if there is no pending or running action with same name and params.
         *
         * @param string $hook The hook to trigger when this action runs.
         * @param array  $args Args to pass when the hook is triggered.
         * @param int    $first Unix timestamp for the first run.
         * @param int    $interval Seconds between runs.
         * @param string $group A group to put the action in.
         * @param bool   $unique Whether action scheduled should be unique.
         *
         * @return int The ID of the stored action.
         */
        public function recurring_unique($hook, $args = array(), $first = \null, $interval = \null, $group = '', $unique = \true)
        {
        }
        /**
         * Create the first instance of an action recurring on a Cron schedule.
         *
         * @param string $hook The hook to trigger when this action runs.
         * @param array  $args Args to pass when the hook is triggered.
         * @param int    $base_timestamp The first instance of the action will be scheduled
         *        to run at a time calculated after this timestamp matching the cron
         *        expression. This can be used to delay the first instance of the action.
         * @param int    $schedule A cron definition string.
         * @param string $group A group to put the action in.
         *
         * @return int The ID of the stored action.
         */
        public function cron($hook, $args = array(), $base_timestamp = \null, $schedule = \null, $group = '')
        {
        }
        /**
         * Create the first instance of an action recurring on a Cron schedule only if there is no pending or running action with same name and params.
         *
         * @param string $hook The hook to trigger when this action runs.
         * @param array  $args Args to pass when the hook is triggered.
         * @param int    $base_timestamp The first instance of the action will be scheduled
         *        to run at a time calculated after this timestamp matching the cron
         *        expression. This can be used to delay the first instance of the action.
         * @param int    $schedule A cron definition string.
         * @param string $group A group to put the action in.
         * @param bool   $unique Whether action scheduled should be unique.
         *
         * @return int The ID of the stored action.
         **/
        public function cron_unique($hook, $args = array(), $base_timestamp = \null, $schedule = \null, $group = '', $unique = \true)
        {
        }
        /**
         * Create a successive instance of a recurring or cron action.
         *
         * Importantly, the action will be rescheduled to run based on the current date/time.
         * That means when the action is scheduled to run in the past, the next scheduled date
         * will be pushed forward. For example, if a recurring action set to run every hour
         * was scheduled to run 5 seconds ago, it will be next scheduled for 1 hour in the
         * future, which is 1 hour and 5 seconds from when it was last scheduled to run.
         *
         * Alternatively, if the action is scheduled to run in the future, and is run early,
         * likely via manual intervention, then its schedule will change based on the time now.
         * For example, if a recurring action set to run every day, and is run 12 hours early,
         * it will run again in 24 hours, not 36 hours.
         *
         * This slippage is less of an issue with Cron actions, as the specific run time can
         * be set for them to run, e.g. 1am each day. In those cases, and entire period would
         * need to be missed before there was any change is scheduled, e.g. in the case of an
         * action scheduled for 1am each day, the action would need to run an entire day late.
         *
         * @param ActionScheduler_Action $action The existing action.
         *
         * @return string The ID of the stored action
         * @throws InvalidArgumentException If $action is not a recurring action.
         */
        public function repeat($action)
        {
        }
        /**
         * Creates a scheduled action.
         *
         * This general purpose method can be used in place of specific methods such as async(),
         * async_unique(), single() or single_unique(), etc.
         *
         * @internal Not intended for public use, should not be overriden by subclasses.
         *
         * @param array $options {
         *     Describes the action we wish to schedule.
         *
         *     @type string     $type      Must be one of 'async', 'cron', 'recurring', or 'single'.
         *     @type string     $hook      The hook to be executed.
         *     @type array      $arguments Arguments to be passed to the callback.
         *     @type string     $group     The action group.
         *     @type bool       $unique    If the action should be unique.
         *     @type int        $when      Timestamp. Indicates when the action, or first instance of the action in the case
         *                                 of recurring or cron actions, becomes due.
         *     @type int|string $pattern   Recurrence pattern. This is either an interval in seconds for recurring actions
         *                                 or a cron expression for cron actions.
         *     @type int        $priority  Lower values means higher priority. Should be in the range 0-255.
         * }
         *
         * @return int The action ID. Zero if there was an error scheduling the action.
         */
        public function create(array $options = array())
        {
        }
        /**
         * Save action to database.
         *
         * @param ActionScheduler_Action $action Action object to save.
         *
         * @return int The ID of the stored action
         */
        protected function store(\ActionScheduler_Action $action)
        {
        }
        /**
         * Store action if it's unique.
         *
         * @param ActionScheduler_Action $action Action object to store.
         *
         * @return int ID of the created action. Will be 0 if action was not created.
         */
        protected function store_unique_action(\ActionScheduler_Action $action)
        {
        }
    }
}
namespace Action_Scheduler\Migration {
    /**
     * Class LogMigrator
     *
     * @package Action_Scheduler\Migration
     *
     * @since 3.0.0
     *
     * @codeCoverageIgnore
     */
    class LogMigrator
    {
        /**
         * ActionMigrator constructor.
         *
         * @param ActionScheduler_Logger $source_logger Source logger object.
         * @param ActionScheduler_Logger $destination_Logger Destination logger object.
         */
        public function __construct(\ActionScheduler_Logger $source_logger, \ActionScheduler_Logger $destination_Logger)
        {
        }
        /**
         * Migrate an action log.
         *
         * @param int $source_action_id Source logger object.
         * @param int $destination_action_id Destination logger object.
         */
        public function migrate($source_action_id, $destination_action_id)
        {
        }
    }
    /**
     * Class DryRun_LogMigrator
     *
     * @package Action_Scheduler\Migration
     *
     * @codeCoverageIgnore
     */
    class DryRun_LogMigrator extends \Action_Scheduler\Migration\LogMigrator
    {
        /**
         * Simulate migrating an action log.
         *
         * @param int $source_action_id Source logger object.
         * @param int $destination_action_id Destination logger object.
         */
        public function migrate($source_action_id, $destination_action_id)
        {
        }
    }
    /**
     * Class Config
     *
     * @package Action_Scheduler\Migration
     *
     * @since 3.0.0
     *
     * A config builder for the ActionScheduler\Migration\Runner class
     */
    class Config
    {
        /**
         * Config constructor.
         */
        public function __construct()
        {
        }
        /**
         * Get the configured source store.
         *
         * @return ActionScheduler_Store
         */
        public function get_source_store()
        {
        }
        /**
         * Set the configured source store.
         *
         * @param ActionScheduler_Store $store Source store object.
         */
        public function set_source_store(\ActionScheduler_Store $store)
        {
        }
        /**
         * Get the configured source loger.
         *
         * @return ActionScheduler_Logger
         */
        public function get_source_logger()
        {
        }
        /**
         * Set the configured source logger.
         *
         * @param ActionScheduler_Logger $logger
         */
        public function set_source_logger(\ActionScheduler_Logger $logger)
        {
        }
        /**
         * Get the configured destination store.
         *
         * @return ActionScheduler_Store
         */
        public function get_destination_store()
        {
        }
        /**
         * Set the configured destination store.
         *
         * @param ActionScheduler_Store $store
         */
        public function set_destination_store(\ActionScheduler_Store $store)
        {
        }
        /**
         * Get the configured destination logger.
         *
         * @return ActionScheduler_Logger
         */
        public function get_destination_logger()
        {
        }
        /**
         * Set the configured destination logger.
         *
         * @param ActionScheduler_Logger $logger
         */
        public function set_destination_logger(\ActionScheduler_Logger $logger)
        {
        }
        /**
         * Get flag indicating whether it's a dry run.
         *
         * @return bool
         */
        public function get_dry_run()
        {
        }
        /**
         * Set flag indicating whether it's a dry run.
         *
         * @param bool $dry_run
         */
        public function set_dry_run($dry_run)
        {
        }
        /**
         * Get progress bar object.
         *
         * @return ActionScheduler\WPCLI\ProgressBar
         */
        public function get_progress_bar()
        {
        }
        /**
         * Set progress bar object.
         *
         * @param ActionScheduler\WPCLI\ProgressBar $progress_bar
         */
        public function set_progress_bar(\Action_Scheduler\WP_CLI\ProgressBar $progress_bar)
        {
        }
    }
}
namespace {
    /**
     * Class ActionScheduler_DBStoreMigrator
     *
     * A  class for direct saving of actions to the table data store during migration.
     *
     * @since 3.0.0
     */
    class ActionScheduler_DBStoreMigrator extends \ActionScheduler_DBStore
    {
        /**
         * Save an action with optional last attempt date.
         *
         * Normally, saving an action sets its attempted date to 0000-00-00 00:00:00 because when an action is first saved,
         * it can't have been attempted yet, but migrated completed actions will have an attempted date, so we need to save
         * that when first saving the action.
         *
         * @param ActionScheduler_Action $action
         * @param \DateTime              $scheduled_date Optional date of the first instance to store.
         * @param \DateTime              $last_attempt_date Optional date the action was last attempted.
         *
         * @return string The action ID
         * @throws \RuntimeException When the action is not saved.
         */
        public function save_action(\ActionScheduler_Action $action, \DateTime $scheduled_date = \null, \DateTime $last_attempt_date = \null)
        {
        }
    }
}
namespace Action_Scheduler\Migration {
    /**
     * Class ActionMigrator
     *
     * @package Action_Scheduler\Migration
     *
     * @since 3.0.0
     *
     * @codeCoverageIgnore
     */
    class ActionMigrator
    {
        /**
         * ActionMigrator constructor.
         *
         * @param ActionScheduler_Store $source_store Source store object.
         * @param ActionScheduler_Store $destination_store Destination store object.
         * @param LogMigrator           $log_migrator Log migrator object.
         */
        public function __construct(\ActionScheduler_Store $source_store, \ActionScheduler_Store $destination_store, \Action_Scheduler\Migration\LogMigrator $log_migrator)
        {
        }
        /**
         * Migrate an action.
         *
         * @param int $source_action_id Action ID.
         *
         * @return int 0|new action ID
         */
        public function migrate($source_action_id)
        {
        }
    }
    /**
     * Class BatchFetcher
     *
     * @package Action_Scheduler\Migration
     *
     * @since 3.0.0
     *
     * @codeCoverageIgnore
     */
    class BatchFetcher
    {
        /**
         * BatchFetcher constructor.
         *
         * @param ActionScheduler_Store $source_store Source store object.
         */
        public function __construct(\ActionScheduler_Store $source_store)
        {
        }
        /**
         * Retrieve a list of actions.
         *
         * @param int $count The number of actions to retrieve
         *
         * @return int[] A list of action IDs
         */
        public function fetch($count = 10)
        {
        }
    }
    /**
     * Class Runner
     *
     * @package Action_Scheduler\Migration
     *
     * @since 3.0.0
     *
     * @codeCoverageIgnore
     */
    class Runner
    {
        /**
         * Runner constructor.
         *
         * @param Config $config Migration configuration object.
         */
        public function __construct(\Action_Scheduler\Migration\Config $config)
        {
        }
        /**
         * Run migration batch.
         *
         * @param int $batch_size Optional batch size. Default 10.
         *
         * @return int Size of batch processed.
         */
        public function run($batch_size = 10)
        {
        }
        /**
         * Migration a batch of actions.
         *
         * @param array $action_ids List of action IDs to migrate.
         */
        public function migrate_actions(array $action_ids)
        {
        }
        /**
         * Initialize destination store and logger.
         */
        public function init_destination()
        {
        }
    }
    /**
     * Class DryRun_ActionMigrator
     *
     * @package Action_Scheduler\Migration
     *
     * @since 3.0.0
     *
     * @codeCoverageIgnore
     */
    class DryRun_ActionMigrator extends \Action_Scheduler\Migration\ActionMigrator
    {
        /**
         * Simulate migrating an action.
         *
         * @param int $source_action_id Action ID.
         *
         * @return int
         */
        public function migrate($source_action_id)
        {
        }
    }
    /**
     * Class Controller
     *
     * The main plugin/initialization class for migration to custom tables.
     *
     * @package Action_Scheduler\Migration
     *
     * @since 3.0.0
     *
     * @codeCoverageIgnore
     */
    class Controller
    {
        /**
         * Controller constructor.
         *
         * @param Scheduler $migration_scheduler Migration scheduler object.
         */
        protected function __construct(\Action_Scheduler\Migration\Scheduler $migration_scheduler)
        {
        }
        /**
         * Set the action store class name.
         *
         * @param string $class Classname of the store class.
         *
         * @return string
         */
        public function get_store_class($class)
        {
        }
        /**
         * Set the action logger class name.
         *
         * @param string $class Classname of the logger class.
         *
         * @return string
         */
        public function get_logger_class($class)
        {
        }
        /**
         * Get flag indicating whether a custom datastore is in use.
         *
         * @return bool
         */
        public function has_custom_datastore()
        {
        }
        /**
         * Set up the background migration process.
         *
         * @return void
         */
        public function schedule_migration()
        {
        }
        /**
         * Get the default migration config object
         *
         * @return ActionScheduler\Migration\Config
         */
        public function get_migration_config_object()
        {
        }
        /**
         * Hook dashboard migration notice.
         */
        public function hook_admin_notices()
        {
        }
        /**
         * Show a dashboard notice that migration is in progress.
         */
        public function display_migration_notice()
        {
        }
        /**
         * Possibly hook the migration scheduler action.
         *
         * @author Jeremy Pry
         */
        public function maybe_hook_migration()
        {
        }
        /**
         * Allow datastores to enable migration to AS tables.
         */
        public function allow_migration()
        {
        }
        /**
         * Proceed with the migration if the dependencies have been met.
         */
        public static function init()
        {
        }
        /**
         * Singleton factory.
         */
        public static function instance()
        {
        }
    }
    /**
     * Class Scheduler
     *
     * @package Action_Scheduler\WP_CLI
     *
     * @since 3.0.0
     *
     * @codeCoverageIgnore
     */
    class Scheduler
    {
        /** Migration action hook. */
        const HOOK = 'action_scheduler/migration_hook';
        /** Migration action group. */
        const GROUP = 'action-scheduler-migration';
        /**
         * Set up the callback for the scheduled job.
         */
        public function hook()
        {
        }
        /**
         * Remove the callback for the scheduled job.
         */
        public function unhook()
        {
        }
        /**
         * The migration callback.
         */
        public function run_migration()
        {
        }
        /**
         * Mark the migration complete.
         */
        public function mark_complete()
        {
        }
        /**
         * Get a flag indicating whether the migration is scheduled.
         *
         * @return bool Whether there is a pending action in the store to handle the migration
         */
        public function is_migration_scheduled()
        {
        }
        /**
         * Schedule the migration.
         *
         * @param int $when Optional timestamp to run the next migration batch. Defaults to now.
         *
         * @return string The action ID
         */
        public function schedule_migration($when = 0)
        {
        }
        /**
         * Remove the scheduled migration action.
         */
        public function unschedule_migration()
        {
        }
    }
}
namespace {
    /**
     * Class ActionScheduler_NullLogEntry
     */
    class ActionScheduler_NullLogEntry extends \ActionScheduler_LogEntry
    {
        public function __construct($action_id = '', $message = '')
        {
        }
    }
    /**
     * Class ActionScheduler_Compatibility
     */
    class ActionScheduler_Compatibility
    {
        /**
         * Converts a shorthand byte value to an integer byte value.
         *
         * Wrapper for wp_convert_hr_to_bytes(), moved to load.php in WordPress 4.6 from media.php
         *
         * @link https://secure.php.net/manual/en/function.ini-get.php
         * @link https://secure.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
         *
         * @param string $value A (PHP ini) byte value, either shorthand or ordinary.
         * @return int An integer byte value.
         */
        public static function convert_hr_to_bytes($value)
        {
        }
        /**
         * Attempts to raise the PHP memory limit for memory intensive processes.
         *
         * Only allows raising the existing limit and prevents lowering it.
         *
         * Wrapper for wp_raise_memory_limit(), added in WordPress v4.6.0
         *
         * @return bool|int|string The limit that was set or false on failure.
         */
        public static function raise_memory_limit()
        {
        }
        /**
         * Attempts to raise the PHP timeout for time intensive processes.
         *
         * Only allows raising the existing limit and prevents lowering it. Wrapper for wc_set_time_limit(), when available.
         *
         * @param int $limit The time limit in seconds.
         */
        public static function raise_time_limit($limit = 0)
        {
        }
    }
    /**
     * Class ActionScheduler
     *
     * @codeCoverageIgnore
     */
    abstract class ActionScheduler
    {
        public static function factory()
        {
        }
        public static function store()
        {
        }
        public static function lock()
        {
        }
        public static function logger()
        {
        }
        public static function runner()
        {
        }
        public static function admin_view()
        {
        }
        /**
         * Get the absolute system path to the plugin directory, or a file therein
         *
         * @static
         * @param string $path
         * @return string
         */
        public static function plugin_path($path)
        {
        }
        /**
         * Get the absolute URL to the plugin directory, or a file therein
         *
         * @static
         * @param string $path
         * @return string
         */
        public static function plugin_url($path)
        {
        }
        public static function autoload($class)
        {
        }
        /**
         * Initialize the plugin
         *
         * @static
         * @param string $plugin_file
         */
        public static function init($plugin_file)
        {
        }
        /**
         * Check whether the AS data store has been initialized.
         *
         * @param string $function_name The name of the function being called. Optional. Default `null`.
         * @return bool
         */
        public static function is_initialized($function_name = \null)
        {
        }
        /**
         * Determine if the class is one of our abstract classes.
         *
         * @since 3.0.0
         *
         * @param string $class The class name.
         *
         * @return bool
         */
        protected static function is_class_abstract($class)
        {
        }
        /**
         * Determine if the class is one of our migration classes.
         *
         * @since 3.0.0
         *
         * @param string $class The class name.
         *
         * @return bool
         */
        protected static function is_class_migration($class)
        {
        }
        /**
         * Determine if the class is one of our WP CLI classes.
         *
         * @since 3.0.0
         *
         * @param string $class The class name.
         *
         * @return bool
         */
        protected static function is_class_cli($class)
        {
        }
        public final function __clone()
        {
        }
        public final function __wakeup()
        {
        }
        /** Deprecated **/
        public static function get_datetime_object($when = \null, $timezone = 'UTC')
        {
        }
        /**
         * Issue deprecated warning if an Action Scheduler function is called in the shutdown hook.
         *
         * @param string $function_name The name of the function being called.
         * @deprecated 3.1.6.
         */
        public static function check_shutdown_hook($function_name)
        {
        }
    }
    /**
     * Action Scheduler Abstract List Table class
     *
     * This abstract class enhances WP_List_Table making it ready to use.
     *
     * By extending this class we can focus on describing how our table looks like,
     * which columns needs to be shown, filter, ordered by and more and forget about the details.
     *
     * This class supports:
     *  - Bulk actions
     *  - Search
     *  - Sortable columns
     *  - Automatic translations of the columns
     *
     * @codeCoverageIgnore
     * @since  2.0.0
     */
    abstract class ActionScheduler_Abstract_ListTable extends \WP_List_Table
    {
        /**
         * The table name
         *
         * @var string
         */
        protected $table_name;
        /**
         * Package name, used to get options from WP_List_Table::get_items_per_page.
         *
         * @var string
         */
        protected $package;
        /**
         * How many items do we render per page?
         *
         * @var int
         */
        protected $items_per_page = 10;
        /**
         * Enables search in this table listing. If this array
         * is empty it means the listing is not searchable.
         *
         * @var array
         */
        protected $search_by = array();
        /**
         * Columns to show in the table listing. It is a key => value pair. The
         * key must much the table column name and the value is the label, which is
         * automatically translated.
         *
         * @var array
         */
        protected $columns = array();
        /**
         * Defines the row-actions. It expects an array where the key
         * is the column name and the value is an array of actions.
         *
         * The array of actions are key => value, where key is the method name
         * (with the prefix row_action_<key>) and the value is the label
         * and title.
         *
         * @var array
         */
        protected $row_actions = array();
        /**
         * The Primary key of our table
         *
         * @var string
         */
        protected $ID = 'ID';
        /**
         * Enables sorting, it expects an array
         * of columns (the column names are the values)
         *
         * @var array
         */
        protected $sort_by = array();
        /**
         * The default sort order
         *
         * @var string
         */
        protected $filter_by = array();
        /**
         * The status name => count combinations for this table's items. Used to display status filters.
         *
         * @var array
         */
        protected $status_counts = array();
        /**
         * Notices to display when loading the table. Array of arrays of form array( 'class' => {updated|error}, 'message' => 'This is the notice text display.' ).
         *
         * @var array
         */
        protected $admin_notices = array();
        /**
         * Localised string displayed in the <h1> element above the able.
         *
         * @var string
         */
        protected $table_header;
        /**
         * Enables bulk actions. It must be an array where the key is the action name
         * and the value is the label (which is translated automatically). It is important
         * to notice that it will check that the method exists (`bulk_$name`) and will throw
         * an exception if it does not exists.
         *
         * This class will automatically check if the current request has a bulk action, will do the
         * validations and afterwards will execute the bulk method, with two arguments. The first argument
         * is the array with primary keys, the second argument is a string with a list of the primary keys,
         * escaped and ready to use (with `IN`).
         *
         * @var array
         */
        protected $bulk_actions = array();
        /**
         * Makes translation easier, it basically just wraps
         * `_x` with some default (the package name).
         *
         * @param string $text The new text to translate.
         * @param string $context The context of the text.
         * @return string|void The translated text.
         *
         * @deprecated 3.0.0 Use `_x()` instead.
         */
        protected function translate($text, $context = '')
        {
        }
        /**
         * Reads `$this->bulk_actions` and returns an array that WP_List_Table understands. It
         * also validates that the bulk method handler exists. It throws an exception because
         * this is a library meant for developers and missing a bulk method is a development-time error.
         *
         * @return array
         *
         * @throws RuntimeException Throws RuntimeException when the bulk action does not have a callback method.
         */
        protected function get_bulk_actions()
        {
        }
        /**
         * Checks if the current request has a bulk action. If that is the case it will validate and will
         * execute the bulk method handler. Regardless if the action is valid or not it will redirect to
         * the previous page removing the current arguments that makes this request a bulk action.
         */
        protected function process_bulk_action()
        {
        }
        /**
         * Default code for deleting entries.
         * validated already by process_bulk_action()
         *
         * @param array  $ids ids of the items to delete.
         * @param string $ids_sql the sql for the ids.
         * @return void
         */
        protected function bulk_delete(array $ids, $ids_sql)
        {
        }
        /**
         * Prepares the _column_headers property which is used by WP_Table_List at rendering.
         * It merges the columns and the sortable columns.
         */
        protected function prepare_column_headers()
        {
        }
        /**
         * Reads $this->sort_by and returns the columns name in a format that WP_Table_List
         * expects
         */
        public function get_sortable_columns()
        {
        }
        /**
         * Returns the columns names for rendering. It adds a checkbox for selecting everything
         * as the first column
         */
        public function get_columns()
        {
        }
        /**
         * Get prepared LIMIT clause for items query
         *
         * @global wpdb $wpdb
         *
         * @return string Prepared LIMIT clause for items query.
         */
        protected function get_items_query_limit()
        {
        }
        /**
         * Returns the number of items to offset/skip for this current view.
         *
         * @return int
         */
        protected function get_items_offset()
        {
        }
        /**
         * Get prepared OFFSET clause for items query
         *
         * @global wpdb $wpdb
         *
         * @return string Prepared OFFSET clause for items query.
         */
        protected function get_items_query_offset()
        {
        }
        /**
         * Prepares the ORDER BY sql statement. It uses `$this->sort_by` to know which
         * columns are sortable. This requests validates the orderby $_GET parameter is a valid
         * column and sortable. It will also use order (ASC|DESC) using DESC by default.
         */
        protected function get_items_query_order()
        {
        }
        /**
         * Return the sortable column specified for this request to order the results by, if any.
         *
         * @return string
         */
        protected function get_request_orderby()
        {
        }
        /**
         * Return the sortable column order specified for this request.
         *
         * @return string
         */
        protected function get_request_order()
        {
        }
        /**
         * Return the status filter for this request, if any.
         *
         * @return string
         */
        protected function get_request_status()
        {
        }
        /**
         * Return the search filter for this request, if any.
         *
         * @return string
         */
        protected function get_request_search_query()
        {
        }
        /**
         * Process and return the columns name. This is meant for using with SQL, this means it
         * always includes the primary key.
         *
         * @return array
         */
        protected function get_table_columns()
        {
        }
        /**
         * Check if the current request is doing a "full text" search. If that is the case
         * prepares the SQL to search texts using LIKE.
         *
         * If the current request does not have any search or if this list table does not support
         * that feature it will return an empty string.
         *
         * @return string
         */
        protected function get_items_query_search()
        {
        }
        /**
         * Prepares the SQL to filter rows by the options defined at `$this->filter_by`. Before trusting
         * any data sent by the user it validates that it is a valid option.
         */
        protected function get_items_query_filters()
        {
        }
        /**
         * Prepares the data to feed WP_Table_List.
         *
         * This has the core for selecting, sorting and filting data. To keep the code simple
         * its logic is split among many methods (get_items_query_*).
         *
         * Beside populating the items this function will also count all the records that matches
         * the filtering criteria and will do fill the pagination variables.
         */
        public function prepare_items()
        {
        }
        /**
         * Display the table.
         *
         * @param string $which The name of the table.
         */
        public function extra_tablenav($which)
        {
        }
        /**
         * Set the data for displaying. It will attempt to unserialize (There is a chance that some columns
         * are serialized). This can be override in child classes for futher data transformation.
         *
         * @param array $items Items array.
         */
        protected function set_items(array $items)
        {
        }
        /**
         * Renders the checkbox for each row, this is the first column and it is named ID regardless
         * of how the primary key is named (to keep the code simpler). The bulk actions will do the proper
         * name transformation though using `$this->ID`.
         *
         * @param array $row The row to render.
         */
        public function column_cb($row)
        {
        }
        /**
         * Renders the row-actions.
         *
         * This method renders the action menu, it reads the definition from the $row_actions property,
         * and it checks that the row action method exists before rendering it.
         *
         * @param array  $row Row to be rendered.
         * @param string $column_name Column name.
         * @return string
         */
        protected function maybe_render_actions($row, $column_name)
        {
        }
        /**
         * Process the bulk actions.
         *
         * @return void
         */
        protected function process_row_actions()
        {
        }
        /**
         * Default column formatting, it will escape everythig for security.
         *
         * @param array  $item The item array.
         * @param string $column_name Column name to display.
         *
         * @return string
         */
        public function column_default($item, $column_name)
        {
        }
        /**
         * Display the table heading and search query, if any
         */
        protected function display_header()
        {
        }
        /**
         * Display the table heading and search query, if any
         */
        protected function display_admin_notices()
        {
        }
        /**
         * Prints the available statuses so the user can click to filter.
         */
        protected function display_filter_by_status()
        {
        }
        /**
         * Renders the table list, we override the original class to render the table inside a form
         * and to render any needed HTML (like the search box). By doing so the callee of a function can simple
         * forget about any extra HTML.
         */
        protected function display_table()
        {
        }
        /**
         * Process any pending actions.
         */
        public function process_actions()
        {
        }
        /**
         * Render the list table page, including header, notices, status filters and table.
         */
        public function display_page()
        {
        }
        /**
         * Get the text to display in the search box on the list table.
         */
        protected function get_search_box_placeholder()
        {
        }
        /**
         * Gets the screen per_page option name.
         *
         * @return string
         */
        protected function get_per_page_option_name()
        {
        }
    }
    /**
     * Class ActionScheduler_TimezoneHelper
     */
    abstract class ActionScheduler_TimezoneHelper
    {
        /**
         * Set a DateTime's timezone to the WordPress site's timezone, or a UTC offset
         * if no timezone string is available.
         *
         * @since  2.1.0
         *
         * @param DateTime $date
         * @return ActionScheduler_DateTime
         */
        public static function set_local_timezone(\DateTime $date)
        {
        }
        /**
         * Helper to retrieve the timezone string for a site until a WP core method exists
         * (see https://core.trac.wordpress.org/ticket/24730).
         *
         * Adapted from wc_timezone_string() and https://secure.php.net/manual/en/function.timezone-name-from-abbr.php#89155.
         *
         * If no timezone string is set, and its not possible to match the UTC offset set for the site to a timezone
         * string, then an empty string will be returned, and the UTC offset should be used to set a DateTime's
         * timezone.
         *
         * @since 2.1.0
         * @return string PHP timezone string for the site or empty if no timezone string is available.
         */
        protected static function get_local_timezone_string($reset = \false)
        {
        }
        /**
         * Get timezone offset in seconds.
         *
         * @since  2.1.0
         * @return float
         */
        protected static function get_local_timezone_offset()
        {
        }
        /**
         * @deprecated 2.1.0
         */
        public static function get_local_timezone($reset = \false)
        {
        }
    }
    /**
     * Commands for Action Scheduler.
     */
    class ActionScheduler_WPCLI_Scheduler_command extends \WP_CLI_Command
    {
        /**
         * Force tables schema creation for Action Scheduler
         *
         * ## OPTIONS
         *
         * @param array $args Positional arguments.
         * @param array $assoc_args Keyed arguments.
         *
         * @subcommand fix-schema
         */
        public function fix_schema($args, $assoc_args)
        {
        }
        /**
         * Run the Action Scheduler
         *
         * ## OPTIONS
         *
         * [--batch-size=<size>]
         * : The maximum number of actions to run. Defaults to 100.
         *
         * [--batches=<size>]
         * : Limit execution to a number of batches. Defaults to 0, meaning batches will continue being executed until all actions are complete.
         *
         * [--cleanup-batch-size=<size>]
         * : The maximum number of actions to clean up. Defaults to the value of --batch-size.
         *
         * [--hooks=<hooks>]
         * : Only run actions with the specified hook. Omitting this option runs actions with any hook. Define multiple hooks as a comma separated string (without spaces), e.g. `--hooks=hook_one,hook_two,hook_three`
         *
         * [--group=<group>]
         * : Only run actions from the specified group. Omitting this option runs actions from all groups.
         *
         * [--exclude-groups=<groups>]
         * : Run actions from all groups except the specified group(s). Define multiple groups as a comma separated string (without spaces), e.g. '--group_a,group_b'. This option is ignored when `--group` is used.
         *
         * [--free-memory-on=<count>]
         * : The number of actions to process between freeing memory. 0 disables freeing memory. Default 50.
         *
         * [--pause=<seconds>]
         * : The number of seconds to pause when freeing memory. Default no pause.
         *
         * [--force]
         * : Whether to force execution despite the maximum number of concurrent processes being exceeded.
         *
         * @param array $args Positional arguments.
         * @param array $assoc_args Keyed arguments.
         * @throws \WP_CLI\ExitException When an error occurs.
         *
         * @subcommand run
         */
        public function run($args, $assoc_args)
        {
        }
        /**
         * Print WP CLI message about how many actions are about to be processed.
         *
         * @author Jeremy Pry
         *
         * @param int $total
         */
        protected function print_total_actions($total)
        {
        }
        /**
         * Print WP CLI message about how many batches of actions were processed.
         *
         * @author Jeremy Pry
         *
         * @param int $batches_completed
         */
        protected function print_total_batches($batches_completed)
        {
        }
        /**
         * Convert an exception into a WP CLI error.
         *
         * @author Jeremy Pry
         *
         * @param Exception $e The error object.
         *
         * @throws \WP_CLI\ExitException
         */
        protected function print_error(\Exception $e)
        {
        }
        /**
         * Print a success message with the number of completed actions.
         *
         * @author Jeremy Pry
         *
         * @param int $actions_completed
         */
        protected function print_success($actions_completed)
        {
        }
    }
}
namespace Action_Scheduler\WP_CLI {
    /**
     * Class Migration_Command
     *
     * @package Action_Scheduler\WP_CLI
     *
     * @since 3.0.0
     *
     * @codeCoverageIgnore
     */
    class Migration_Command extends \WP_CLI_Command
    {
        /**
         * Register the command with WP-CLI
         */
        public function register()
        {
        }
        /**
         * Process the data migration.
         *
         * @param array $positional_args Required for WP CLI. Not used in migration.
         * @param array $assoc_args Optional arguments.
         *
         * @return void
         */
        public function migrate($positional_args, $assoc_args)
        {
        }
    }
}
namespace {
    /**
     * Commands for Action Scheduler.
     */
    class ActionScheduler_WPCLI_Clean_Command extends \WP_CLI_Command
    {
        /**
         * Run the Action Scheduler Queue Cleaner
         *
         * ## OPTIONS
         *
         * [--batch-size=<size>]
         * : The maximum number of actions to delete per batch. Defaults to 20.
         *
         * [--batches=<size>]
         * : Limit execution to a number of batches. Defaults to 0, meaning batches will continue all eligible actions are deleted.
         *
         * [--status=<status>]
         * : Only clean actions with the specified status. Defaults to Canceled, Completed. Define multiple statuses as a comma separated string (without spaces), e.g. `--status=complete,failed,canceled`
         *
         * [--before=<datestring>]
         * : Only delete actions with scheduled date older than this. Defaults to 31 days. e.g `--before='7 days ago'`, `--before='02-Feb-2020 20:20:20'`
         *
         * [--pause=<seconds>]
         * : The number of seconds to pause between batches. Default no pause.
         *
         * @param array $args Positional arguments.
         * @param array $assoc_args Keyed arguments.
         * @throws \WP_CLI\ExitException When an error occurs.
         *
         * @subcommand clean
         */
        public function clean($args, $assoc_args)
        {
        }
        /**
         * Print WP CLI message about how many batches of actions were processed.
         *
         * @param int $batches_processed
         */
        protected function print_total_batches(int $batches_processed)
        {
        }
        /**
         * Convert an exception into a WP CLI error.
         *
         * @param Exception $e The error object.
         *
         * @throws \WP_CLI\ExitException
         */
        protected function print_error(\Exception $e)
        {
        }
        /**
         * Print a success message with the number of completed actions.
         *
         * @param int $actions_deleted
         */
        protected function print_success(int $actions_deleted)
        {
        }
    }
}
namespace Action_Scheduler\WP_CLI {
    /**
     * WP_CLI progress bar for Action Scheduler.
     */
    /**
     * Class ProgressBar
     *
     * @package Action_Scheduler\WP_CLI
     *
     * @since 3.0.0
     *
     * @codeCoverageIgnore
     */
    class ProgressBar
    {
        /** @var integer */
        protected $total_ticks;
        /** @var integer */
        protected $count;
        /** @var integer */
        protected $interval;
        /** @var string */
        protected $message;
        /** @var \cli\progress\Bar */
        protected $progress_bar;
        /**
         * ProgressBar constructor.
         *
         * @param string  $message    Text to display before the progress bar.
         * @param integer $count      Total number of ticks to be performed.
         * @param integer $interval   Optional. The interval in milliseconds between updates. Default 100.
         *
         * @throws Exception When this is not run within WP CLI
         */
        public function __construct($message, $count, $interval = 100)
        {
        }
        /**
         * Increment the progress bar ticks.
         */
        public function tick()
        {
        }
        /**
         * Get the progress bar tick count.
         *
         * @return int
         */
        public function current()
        {
        }
        /**
         * Finish the current progress bar.
         */
        public function finish()
        {
        }
        /**
         * Set the message used when creating the progress bar.
         *
         * @param string $message The message to be used when the next progress bar is created.
         */
        public function set_message($message)
        {
        }
        /**
         * Set the count for a new progress bar.
         *
         * @param integer $count The total number of ticks expected to complete.
         */
        public function set_count($count)
        {
        }
        /**
         * Set up the progress bar.
         */
        protected function setup_progress_bar()
        {
        }
    }
}
namespace {
    /**
     * WP CLI Queue runner.
     *
     * This class can only be called from within a WP CLI instance.
     */
    class ActionScheduler_WPCLI_QueueRunner extends \ActionScheduler_Abstract_QueueRunner
    {
        /** @var array */
        protected $actions;
        /** @var  ActionScheduler_ActionClaim */
        protected $claim;
        /** @var \cli\progress\Bar */
        protected $progress_bar;
        /**
         * ActionScheduler_WPCLI_QueueRunner constructor.
         *
         * @param ActionScheduler_Store             $store
         * @param ActionScheduler_FatalErrorMonitor $monitor
         * @param ActionScheduler_QueueCleaner      $cleaner
         *
         * @throws Exception When this is not run within WP CLI
         */
        public function __construct(\ActionScheduler_Store $store = \null, \ActionScheduler_FatalErrorMonitor $monitor = \null, \ActionScheduler_QueueCleaner $cleaner = \null)
        {
        }
        /**
         * Set up the Queue before processing.
         *
         * @author Jeremy Pry
         *
         * @param int    $batch_size The batch size to process.
         * @param array  $hooks      The hooks being used to filter the actions claimed in this batch.
         * @param string $group      The group of actions to claim with this batch.
         * @param bool   $force      Whether to force running even with too many concurrent processes.
         *
         * @return int The number of actions that will be run.
         * @throws \WP_CLI\ExitException When there are too many concurrent batches.
         */
        public function setup($batch_size, $hooks = array(), $group = '', $force = \false)
        {
        }
        /**
         * Add our hooks to the appropriate actions.
         *
         * @author Jeremy Pry
         */
        protected function add_hooks()
        {
        }
        /**
         * Set up the WP CLI progress bar.
         *
         * @author Jeremy Pry
         */
        protected function setup_progress_bar()
        {
        }
        /**
         * Process actions in the queue.
         *
         * @author Jeremy Pry
         *
         * @param string $context Optional runner context. Default 'WP CLI'.
         *
         * @return int The number of actions processed.
         */
        public function run($context = 'WP CLI')
        {
        }
        /**
         * Handle WP CLI message when the action is starting.
         *
         * @author Jeremy Pry
         *
         * @param $action_id
         */
        public function before_execute($action_id)
        {
        }
        /**
         * Handle WP CLI message when the action has completed.
         *
         * @author Jeremy Pry
         *
         * @param int                         $action_id
         * @param null|ActionScheduler_Action $action The instance of the action. Default to null for backward compatibility.
         */
        public function after_execute($action_id, $action = \null)
        {
        }
        /**
         * Handle WP CLI message when the action has failed.
         *
         * @author Jeremy Pry
         *
         * @param int       $action_id
         * @param Exception $exception
         * @throws \WP_CLI\ExitException With failure message.
         */
        public function action_failed($action_id, $exception)
        {
        }
        /**
         * Sleep and help avoid hitting memory limit
         *
         * @param int $sleep_time Amount of seconds to sleep
         * @deprecated 3.0.0
         */
        protected function stop_the_insanity($sleep_time = 0)
        {
        }
        /**
         * Maybe trigger the stop_the_insanity() method to free up memory.
         */
        protected function maybe_stop_the_insanity()
        {
        }
    }
    /**
     * Implements the admin view of the actions.
     *
     * @codeCoverageIgnore
     */
    class ActionScheduler_ListTable extends \ActionScheduler_Abstract_ListTable
    {
        /**
         * The package name.
         *
         * @var string
         */
        protected $package = 'action-scheduler';
        /**
         * Columns to show (name => label).
         *
         * @var array
         */
        protected $columns = array();
        /**
         * Actions (name => label).
         *
         * @var array
         */
        protected $row_actions = array();
        /**
         * The active data stores
         *
         * @var ActionScheduler_Store
         */
        protected $store;
        /**
         * A logger to use for getting action logs to display
         *
         * @var ActionScheduler_Logger
         */
        protected $logger;
        /**
         * A ActionScheduler_QueueRunner runner instance (or child class)
         *
         * @var ActionScheduler_QueueRunner
         */
        protected $runner;
        /**
         * Bulk actions. The key of the array is the method name of the implementation:
         *
         *     bulk_<key>(array $ids, string $sql_in).
         *
         * See the comments in the parent class for further details
         *
         * @var array
         */
        protected $bulk_actions = array();
        /**
         * Flag variable to render our notifications, if any, once.
         *
         * @var bool
         */
        protected static $did_notification = \false;
        /**
         * Sets the current data store object into `store->action` and initialises the object.
         *
         * @param ActionScheduler_Store       $store
         * @param ActionScheduler_Logger      $logger
         * @param ActionScheduler_QueueRunner $runner
         */
        public function __construct(\ActionScheduler_Store $store, \ActionScheduler_Logger $logger, \ActionScheduler_QueueRunner $runner)
        {
        }
        /**
         * Handles setting the items_per_page option for this screen.
         *
         * @param mixed  $status Default false (to skip saving the current option).
         * @param string $option Screen option name.
         * @param int    $value  Screen option value.
         * @return int
         */
        public function set_items_per_page_option($status, $option, $value)
        {
        }
        /**
         * Returns the recurrence of an action or 'Non-repeating'. The output is human readable.
         *
         * @param ActionScheduler_Action $action
         *
         * @return string
         */
        protected function get_recurrence($action)
        {
        }
        /**
         * Serializes the argument of an action to render it in a human friendly format.
         *
         * @param array $row The array representation of the current row of the table
         *
         * @return string
         */
        public function column_args(array $row)
        {
        }
        /**
         * Prints the logs entries inline. We do so to avoid loading Javascript and other hacks to show it in a modal.
         *
         * @param array $row Action array.
         * @return string
         */
        public function column_log_entries(array $row)
        {
        }
        /**
         * Prints the logs entries inline. We do so to avoid loading Javascript and other hacks to show it in a modal.
         *
         * @param ActionScheduler_LogEntry $log_entry
         * @param DateTimezone             $timezone
         * @return string
         */
        protected function get_log_entry_html(\ActionScheduler_LogEntry $log_entry, \DateTimezone $timezone)
        {
        }
        /**
         * Only display row actions for pending actions.
         *
         * @param array  $row         Row to render
         * @param string $column_name Current row
         *
         * @return string
         */
        protected function maybe_render_actions($row, $column_name)
        {
        }
        /**
         * Renders admin notifications
         *
         * Notifications:
         *  1. When the maximum number of tasks are being executed simultaneously.
         *  2. Notifications when a task is manually executed.
         *  3. Tables are missing.
         */
        public function display_admin_notices()
        {
        }
        /**
         * Prints the scheduled date in a human friendly format.
         *
         * @param array $row The array representation of the current row of the table
         *
         * @return string
         */
        public function column_schedule($row)
        {
        }
        /**
         * Get the scheduled date in a human friendly format.
         *
         * @param ActionScheduler_Schedule $schedule
         * @return string
         */
        protected function get_schedule_display_string(\ActionScheduler_Schedule $schedule)
        {
        }
        /**
         * Bulk delete
         *
         * Deletes actions based on their ID. This is the handler for the bulk delete. It assumes the data
         * properly validated by the callee and it will delete the actions without any extra validation.
         *
         * @param array  $ids
         * @param string $ids_sql Inherited and unused
         */
        protected function bulk_delete(array $ids, $ids_sql)
        {
        }
        /**
         * Implements the logic behind running an action. ActionScheduler_Abstract_ListTable validates the request and their
         * parameters are valid.
         *
         * @param int $action_id
         */
        protected function row_action_cancel($action_id)
        {
        }
        /**
         * Implements the logic behind running an action. ActionScheduler_Abstract_ListTable validates the request and their
         * parameters are valid.
         *
         * @param int $action_id
         */
        protected function row_action_run($action_id)
        {
        }
        /**
         * Force the data store schema updates.
         */
        protected function recreate_tables()
        {
        }
        /**
         * Implements the logic behind processing an action once an action link is clicked on the list table.
         *
         * @param int    $action_id
         * @param string $row_action_type The type of action to perform on the action.
         */
        protected function process_row_action($action_id, $row_action_type)
        {
        }
        /**
         * {@inheritDoc}
         */
        public function prepare_items()
        {
        }
        /**
         * Prints the available statuses so the user can click to filter.
         */
        protected function display_filter_by_status()
        {
        }
        /**
         * Get the text to display in the search box on the list table.
         */
        protected function get_search_box_button_text()
        {
        }
        /**
         * {@inheritDoc}
         */
        protected function get_per_page_option_name()
        {
        }
    }
    /**
     * ActionScheduler DateTime class.
     *
     * This is a custom extension to DateTime that
     */
    class ActionScheduler_DateTime extends \DateTime
    {
        /**
         * UTC offset.
         *
         * Only used when a timezone is not set. When a timezone string is
         * used, this will be set to 0.
         *
         * @var int
         */
        protected $utcOffset = 0;
        /**
         * Get the unix timestamp of the current object.
         *
         * Missing in PHP 5.2 so just here so it can be supported consistently.
         *
         * @return int
         */
        #[\ReturnTypeWillChange]
        public function getTimestamp()
        {
        }
        /**
         * Set the UTC offset.
         *
         * This represents a fixed offset instead of a timezone setting.
         *
         * @param $offset
         */
        public function setUtcOffset($offset)
        {
        }
        /**
         * Returns the timezone offset.
         *
         * @return int
         * @link http://php.net/manual/en/datetime.getoffset.php
         */
        #[\ReturnTypeWillChange]
        public function getOffset()
        {
        }
        /**
         * Set the TimeZone associated with the DateTime
         *
         * @param DateTimeZone $timezone
         *
         * @return static
         * @link http://php.net/manual/en/datetime.settimezone.php
         */
        #[\ReturnTypeWillChange]
        public function setTimezone($timezone)
        {
        }
        /**
         * Get the timestamp with the WordPress timezone offset added or subtracted.
         *
         * @since  3.0.0
         * @return int
         */
        public function getOffsetTimestamp()
        {
        }
    }
    /**
     * Class ActionScheduler_ActionClaim
     */
    class ActionScheduler_ActionClaim
    {
        public function __construct($id, array $action_ids)
        {
        }
        public function get_id()
        {
        }
        public function get_actions()
        {
        }
    }
    /**
     * Class ActionScheduler_QueueCleaner
     */
    class ActionScheduler_QueueCleaner
    {
        /** @var int */
        protected $batch_size;
        /**
         * ActionScheduler_QueueCleaner constructor.
         *
         * @param ActionScheduler_Store $store      The store instance.
         * @param int                   $batch_size The batch size.
         */
        public function __construct(\ActionScheduler_Store $store = \null, $batch_size = 20)
        {
        }
        /**
         * Default queue cleaner process used by queue runner.
         *
         * @return array
         */
        public function delete_old_actions()
        {
        }
        /**
         * Delete selected actions limited by status and date.
         *
         * @param string[] $statuses_to_purge List of action statuses to purge. Defaults to canceled, complete.
         * @param DateTime $cutoff_date Date limit for selecting actions. Defaults to 31 days ago.
         * @param int|null $batch_size Maximum number of actions per status to delete. Defaults to 20.
         * @param string   $context Calling process context. Defaults to `old`.
         * @return array Actions deleted.
         */
        public function clean_actions(array $statuses_to_purge, \DateTime $cutoff_date, $batch_size = \null, $context = 'old')
        {
        }
        /**
         * Unclaim pending actions that have not been run within a given time limit.
         *
         * When called by ActionScheduler_Abstract_QueueRunner::run_cleanup(), the time limit passed
         * as a parameter is 10x the time limit used for queue processing.
         *
         * @param int $time_limit The number of seconds to allow a queue to run before unclaiming its pending actions. Default 300 (5 minutes).
         */
        public function reset_timeouts($time_limit = 300)
        {
        }
        /**
         * Mark actions that have been running for more than a given time limit as failed, based on
         * the assumption some uncatachable and unloggable fatal error occurred during processing.
         *
         * When called by ActionScheduler_Abstract_QueueRunner::run_cleanup(), the time limit passed
         * as a parameter is 10x the time limit used for queue processing.
         *
         * @param int $time_limit The number of seconds to allow an action to run before it is considered to have failed. Default 300 (5 minutes).
         */
        public function mark_failures($time_limit = 300)
        {
        }
        /**
         * Do all of the cleaning actions.
         *
         * @param int $time_limit The number of seconds to use as the timeout and failure period. Default 300 (5 minutes).
         * @author Jeremy Pry
         */
        public function clean($time_limit = 300)
        {
        }
        /**
         * Get the batch size for cleaning the queue.
         *
         * @author Jeremy Pry
         * @return int
         */
        protected function get_batch_size()
        {
        }
    }
    /**
     * CRON expression parser that can determine whether or not a CRON expression is
     * due to run, the next run date and previous run date of a CRON expression.
     * The determinations made by this class are accurate if checked run once per
     * minute (seconds are dropped from date time comparisons).
     *
     * Schedule parts must map to:
     * minute [0-59], hour [0-23], day of month, month [1-12|JAN-DEC], day of week
     * [1-7|MON-SUN], and an optional year.
     *
     * @author Michael Dowling <mtdowling@gmail.com>
     * @link http://en.wikipedia.org/wiki/Cron
     */
    class CronExpression
    {
        const MINUTE = 0;
        const HOUR = 1;
        const DAY = 2;
        const MONTH = 3;
        const WEEKDAY = 4;
        const YEAR = 5;
        /**
        * Factory method to create a new CronExpression.
        *
        * @param string $expression The CRON expression to create.  There are
        *      several special predefined values which can be used to substitute the
        *      CRON expression:
        *
        *      @yearly, @annually) - Run once a year, midnight, Jan. 1 - 0 0 1 1 *
        *      @monthly - Run once a month, midnight, first of month - 0 0 1 * *
        *      @weekly - Run once a week, midnight on Sun - 0 0 * * 0
        *      @daily - Run once a day, midnight - 0 0 * * *
        *      @hourly - Run once an hour, first minute - 0 * * * *
        *
        *@param CronExpression_FieldFactory $fieldFactory (optional) Field factory to use
        *
        * @return CronExpression
        */
        public static function factory($expression, \CronExpression_FieldFactory $fieldFactory = \null)
        {
        }
        /**
         * Parse a CRON expression
         *
         * @param string       $expression   CRON expression (e.g. '8 * * * *')
         * @param CronExpression_FieldFactory $fieldFactory Factory to create cron fields
         */
        public function __construct($expression, \CronExpression_FieldFactory $fieldFactory)
        {
        }
        /**
         * Set or change the CRON expression
         *
         * @param string $value CRON expression (e.g. 8 * * * *)
         *
         * @return CronExpression
         * @throws InvalidArgumentException if not a valid CRON expression
         */
        public function setExpression($value)
        {
        }
        /**
         * Set part of the CRON expression
         *
         * @param int    $position The position of the CRON expression to set
         * @param string $value    The value to set
         *
         * @return CronExpression
         * @throws InvalidArgumentException if the value is not valid for the part
         */
        public function setPart($position, $value)
        {
        }
        /**
         * Get a next run date relative to the current date or a specific date
         *
         * @param string|DateTime $currentTime (optional) Relative calculation date
         * @param int             $nth         (optional) Number of matches to skip before returning a
         *     matching next run date.  0, the default, will return the current
         *     date and time if the next run date falls on the current date and
         *     time.  Setting this value to 1 will skip the first match and go to
         *     the second match.  Setting this value to 2 will skip the first 2
         *     matches and so on.
         * @param bool $allowCurrentDate (optional) Set to TRUE to return the
         *     current date if it matches the cron expression
         *
         * @return DateTime
         * @throws RuntimeException on too many iterations
         */
        public function getNextRunDate($currentTime = 'now', $nth = 0, $allowCurrentDate = \false)
        {
        }
        /**
         * Get a previous run date relative to the current date or a specific date
         *
         * @param string|DateTime $currentTime      (optional) Relative calculation date
         * @param int             $nth              (optional) Number of matches to skip before returning
         * @param bool            $allowCurrentDate (optional) Set to TRUE to return the
         *     current date if it matches the cron expression
         *
         * @return DateTime
         * @throws RuntimeException on too many iterations
         * @see CronExpression::getNextRunDate
         */
        public function getPreviousRunDate($currentTime = 'now', $nth = 0, $allowCurrentDate = \false)
        {
        }
        /**
         * Get multiple run dates starting at the current date or a specific date
         *
         * @param int             $total            Set the total number of dates to calculate
         * @param string|DateTime $currentTime      (optional) Relative calculation date
         * @param bool            $invert           (optional) Set to TRUE to retrieve previous dates
         * @param bool            $allowCurrentDate (optional) Set to TRUE to return the
         *     current date if it matches the cron expression
         *
         * @return array Returns an array of run dates
         */
        public function getMultipleRunDates($total, $currentTime = 'now', $invert = \false, $allowCurrentDate = \false)
        {
        }
        /**
         * Get all or part of the CRON expression
         *
         * @param string $part (optional) Specify the part to retrieve or NULL to
         *      get the full cron schedule string.
         *
         * @return string|null Returns the CRON expression, a part of the
         *      CRON expression, or NULL if the part was specified but not found
         */
        public function getExpression($part = \null)
        {
        }
        /**
         * Helper method to output the full expression.
         *
         * @return string Full CRON expression
         */
        public function __toString()
        {
        }
        /**
         * Determine if the cron is due to run based on the current date or a
         * specific date.  This method assumes that the current number of
         * seconds are irrelevant, and should be called once per minute.
         *
         * @param string|DateTime $currentTime (optional) Relative calculation date
         *
         * @return bool Returns TRUE if the cron is due to run or FALSE if not
         */
        public function isDue($currentTime = 'now')
        {
        }
        /**
         * Get the next or previous run date of the expression relative to a date
         *
         * @param string|DateTime $currentTime      (optional) Relative calculation date
         * @param int             $nth              (optional) Number of matches to skip before returning
         * @param bool            $invert           (optional) Set to TRUE to go backwards in time
         * @param bool            $allowCurrentDate (optional) Set to TRUE to return the
         *     current date if it matches the cron expression
         *
         * @return DateTime
         * @throws RuntimeException on too many iterations
         */
        protected function getRunDate($currentTime = \null, $nth = 0, $invert = \false, $allowCurrentDate = \false)
        {
        }
    }
    /**
     * CRON field interface
     *
     * @author Michael Dowling <mtdowling@gmail.com>
     */
    interface CronExpression_FieldInterface
    {
        /**
         * Check if the respective value of a DateTime field satisfies a CRON exp
         *
         * @param DateTime $date  DateTime object to check
         * @param string   $value CRON expression to test against
         *
         * @return bool Returns TRUE if satisfied, FALSE otherwise
         */
        public function isSatisfiedBy(\DateTime $date, $value);
        /**
         * When a CRON expression is not satisfied, this method is used to increment
         * or decrement a DateTime object by the unit of the cron field
         *
         * @param DateTime $date   DateTime object to change
         * @param bool     $invert (optional) Set to TRUE to decrement
         *
         * @return CronExpression_FieldInterface
         */
        public function increment(\DateTime $date, $invert = \false);
        /**
         * Validates a CRON expression for a given field
         *
         * @param string $value CRON expression value to validate
         *
         * @return bool Returns TRUE if valid, FALSE otherwise
         */
        public function validate($value);
    }
    /**
     * Abstract CRON expression field
     *
     * @author Michael Dowling <mtdowling@gmail.com>
     */
    abstract class CronExpression_AbstractField implements \CronExpression_FieldInterface
    {
        /**
         * Check to see if a field is satisfied by a value
         *
         * @param string $dateValue Date value to check
         * @param string $value     Value to test
         *
         * @return bool
         */
        public function isSatisfied($dateValue, $value)
        {
        }
        /**
         * Check if a value is a range
         *
         * @param string $value Value to test
         *
         * @return bool
         */
        public function isRange($value)
        {
        }
        /**
         * Check if a value is an increments of ranges
         *
         * @param string $value Value to test
         *
         * @return bool
         */
        public function isIncrementsOfRanges($value)
        {
        }
        /**
         * Test if a value is within a range
         *
         * @param string $dateValue Set date value
         * @param string $value     Value to test
         *
         * @return bool
         */
        public function isInRange($dateValue, $value)
        {
        }
        /**
         * Test if a value is within an increments of ranges (offset[-to]/step size)
         *
         * @param string $dateValue Set date value
         * @param string $value     Value to test
         *
         * @return bool
         */
        public function isInIncrementsOfRanges($dateValue, $value)
        {
        }
    }
    /**
     * Minutes field.  Allows: * , / -
     *
     * @author Michael Dowling <mtdowling@gmail.com>
     */
    class CronExpression_MinutesField extends \CronExpression_AbstractField
    {
        /**
         * {@inheritdoc}
         */
        public function isSatisfiedBy(\DateTime $date, $value)
        {
        }
        /**
         * {@inheritdoc}
         */
        public function increment(\DateTime $date, $invert = \false)
        {
        }
        /**
         * {@inheritdoc}
         */
        public function validate($value)
        {
        }
    }
    /**
     * CRON field factory implementing a flyweight factory
     *
     * @author Michael Dowling <mtdowling@gmail.com>
     * @link http://en.wikipedia.org/wiki/Cron
     */
    class CronExpression_FieldFactory
    {
        /**
         * Get an instance of a field object for a cron expression position
         *
         * @param int $position CRON expression position value to retrieve
         *
         * @return CronExpression_FieldInterface
         * @throws InvalidArgumentException if a position is not valid
         */
        public function getField($position)
        {
        }
    }
    /**
     * Hours field.  Allows: * , / -
     *
     * @author Michael Dowling <mtdowling@gmail.com>
     */
    class CronExpression_HoursField extends \CronExpression_AbstractField
    {
        /**
         * {@inheritdoc}
         */
        public function isSatisfiedBy(\DateTime $date, $value)
        {
        }
        /**
         * {@inheritdoc}
         */
        public function increment(\DateTime $date, $invert = \false)
        {
        }
        /**
         * {@inheritdoc}
         */
        public function validate($value)
        {
        }
    }
    /**
     * Day of week field.  Allows: * / , - ? L #
     *
     * Days of the week can be represented as a number 0-7 (0|7 = Sunday)
     * or as a three letter string: SUN, MON, TUE, WED, THU, FRI, SAT.
     *
     * 'L' stands for "last". It allows you to specify constructs such as
     * "the last Friday" of a given month.
     *
     * '#' is allowed for the day-of-week field, and must be followed by a
     * number between one and five. It allows you to specify constructs such as
     * "the second Friday" of a given month.
     *
     * @author Michael Dowling <mtdowling@gmail.com>
     */
    class CronExpression_DayOfWeekField extends \CronExpression_AbstractField
    {
        /**
         * {@inheritdoc}
         */
        public function isSatisfiedBy(\DateTime $date, $value)
        {
        }
        /**
         * {@inheritdoc}
         */
        public function increment(\DateTime $date, $invert = \false)
        {
        }
        /**
         * {@inheritdoc}
         */
        public function validate($value)
        {
        }
    }
    /**
     * Day of month field.  Allows: * , / - ? L W
     *
     * 'L' stands for "last" and specifies the last day of the month.
     *
     * The 'W' character is used to specify the weekday (Monday-Friday) nearest the
     * given day. As an example, if you were to specify "15W" as the value for the
     * day-of-month field, the meaning is: "the nearest weekday to the 15th of the
     * month". So if the 15th is a Saturday, the trigger will fire on Friday the
     * 14th. If the 15th is a Sunday, the trigger will fire on Monday the 16th. If
     * the 15th is a Tuesday, then it will fire on Tuesday the 15th. However if you
     * specify "1W" as the value for day-of-month, and the 1st is a Saturday, the
     * trigger will fire on Monday the 3rd, as it will not 'jump' over the boundary
     * of a month's days. The 'W' character can only be specified when the
     * day-of-month is a single day, not a range or list of days.
     *
     * @author Michael Dowling <mtdowling@gmail.com>
     */
    class CronExpression_DayOfMonthField extends \CronExpression_AbstractField
    {
        /**
         * {@inheritdoc}
         */
        public function isSatisfiedBy(\DateTime $date, $value)
        {
        }
        /**
         * {@inheritdoc}
         */
        public function increment(\DateTime $date, $invert = \false)
        {
        }
        /**
         * {@inheritdoc}
         */
        public function validate($value)
        {
        }
    }
    /**
     * Year field.  Allows: * , / -
     *
     * @author Michael Dowling <mtdowling@gmail.com>
     */
    class CronExpression_YearField extends \CronExpression_AbstractField
    {
        /**
         * {@inheritdoc}
         */
        public function isSatisfiedBy(\DateTime $date, $value)
        {
        }
        /**
         * {@inheritdoc}
         */
        public function increment(\DateTime $date, $invert = \false)
        {
        }
        /**
         * {@inheritdoc}
         */
        public function validate($value)
        {
        }
    }
    /**
     * Month field.  Allows: * , / -
     *
     * @author Michael Dowling <mtdowling@gmail.com>
     */
    class CronExpression_MonthField extends \CronExpression_AbstractField
    {
        /**
         * {@inheritdoc}
         */
        public function isSatisfiedBy(\DateTime $date, $value)
        {
        }
        /**
         * {@inheritdoc}
         */
        public function increment(\DateTime $date, $invert = \false)
        {
        }
        /**
         * {@inheritdoc}
         */
        public function validate($value)
        {
        }
    }
}
namespace SRFM\Inc\Lib\Browser {
    class Browser
    {
        const BROWSER_UNKNOWN = 'unknown';
        const VERSION_UNKNOWN = 'unknown';
        const BROWSER_OPERA = 'Opera';
        // http://www.opera.com/
        const BROWSER_OPERA_MINI = 'Opera Mini';
        // http://www.opera.com/mini/
        const BROWSER_WEBTV = 'WebTV';
        // http://www.webtv.net/pc/
        const BROWSER_EDGE = 'Edge';
        // https://www.microsoft.com/edge
        const BROWSER_IE = 'Internet Explorer';
        // http://www.microsoft.com/ie/
        const BROWSER_POCKET_IE = 'Pocket Internet Explorer';
        // http://en.wikipedia.org/wiki/Internet_Explorer_Mobile
        const BROWSER_KONQUEROR = 'Konqueror';
        // http://www.konqueror.org/
        const BROWSER_ICAB = 'iCab';
        // http://www.icab.de/
        const BROWSER_OMNIWEB = 'OmniWeb';
        // http://www.omnigroup.com/applications/omniweb/
        const BROWSER_FIREBIRD = 'Firebird';
        // http://www.ibphoenix.com/
        const BROWSER_FIREFOX = 'Firefox';
        // https://www.mozilla.org/en-US/firefox/
        const BROWSER_BRAVE = 'Brave';
        // https://brave.com/
        const BROWSER_PALEMOON = 'Palemoon';
        // https://www.palemoon.org/
        const BROWSER_ICEWEASEL = 'Iceweasel';
        // http://www.geticeweasel.org/
        const BROWSER_SHIRETOKO = 'Shiretoko';
        // http://wiki.mozilla.org/Projects/shiretoko
        const BROWSER_MOZILLA = 'Mozilla';
        // http://www.mozilla.com/en-US/
        const BROWSER_AMAYA = 'Amaya';
        // http://www.w3.org/Amaya/
        const BROWSER_LYNX = 'Lynx';
        // http://en.wikipedia.org/wiki/Lynx
        const BROWSER_SAFARI = 'Safari';
        // http://apple.com
        const BROWSER_IPHONE = 'iPhone';
        // http://apple.com
        const BROWSER_IPOD = 'iPod';
        // http://apple.com
        const BROWSER_IPAD = 'iPad';
        // http://apple.com
        const BROWSER_CHROME = 'Chrome';
        // http://www.google.com/chrome
        const BROWSER_ANDROID = 'Android';
        // http://www.android.com/
        const BROWSER_GOOGLEBOT = 'GoogleBot';
        // http://en.wikipedia.org/wiki/Googlebot
        const BROWSER_CURL = 'cURL';
        // https://en.wikipedia.org/wiki/CURL
        const BROWSER_WGET = 'Wget';
        // https://en.wikipedia.org/wiki/Wget
        const BROWSER_UCBROWSER = 'UCBrowser';
        // https://www.ucweb.com/
        const BROWSER_YANDEXBOT = 'YandexBot';
        // http://yandex.com/bots
        const BROWSER_YANDEXIMAGERESIZER_BOT = 'YandexImageResizer';
        // http://yandex.com/bots
        const BROWSER_YANDEXIMAGES_BOT = 'YandexImages';
        // http://yandex.com/bots
        const BROWSER_YANDEXVIDEO_BOT = 'YandexVideo';
        // http://yandex.com/bots
        const BROWSER_YANDEXMEDIA_BOT = 'YandexMedia';
        // http://yandex.com/bots
        const BROWSER_YANDEXBLOGS_BOT = 'YandexBlogs';
        // http://yandex.com/bots
        const BROWSER_YANDEXFAVICONS_BOT = 'YandexFavicons';
        // http://yandex.com/bots
        const BROWSER_YANDEXWEBMASTER_BOT = 'YandexWebmaster';
        // http://yandex.com/bots
        const BROWSER_YANDEXDIRECT_BOT = 'YandexDirect';
        // http://yandex.com/bots
        const BROWSER_YANDEXMETRIKA_BOT = 'YandexMetrika';
        // http://yandex.com/bots
        const BROWSER_YANDEXNEWS_BOT = 'YandexNews';
        // http://yandex.com/bots
        const BROWSER_YANDEXCATALOG_BOT = 'YandexCatalog';
        // http://yandex.com/bots
        const BROWSER_SLURP = 'Yahoo! Slurp';
        // http://en.wikipedia.org/wiki/Yahoo!_Slurp
        const BROWSER_W3CVALIDATOR = 'W3C Validator';
        // http://validator.w3.org/
        const BROWSER_BLACKBERRY = 'BlackBerry';
        // http://www.blackberry.com/
        const BROWSER_ICECAT = 'IceCat';
        // http://en.wikipedia.org/wiki/GNU_IceCat
        const BROWSER_NOKIA_S60 = 'Nokia S60 OSS Browser';
        // http://en.wikipedia.org/wiki/Web_Browser_for_S60
        const BROWSER_NOKIA = 'Nokia Browser';
        // * all other WAP-based browsers on the Nokia Platform
        const BROWSER_MSN = 'MSN Browser';
        // http://explorer.msn.com/
        const BROWSER_MSNBOT = 'MSN Bot';
        // http://search.msn.com/msnbot.htm
        const BROWSER_BINGBOT = 'Bing Bot';
        // http://en.wikipedia.org/wiki/Bingbot
        const BROWSER_VIVALDI = 'Vivaldi';
        // https://vivaldi.com/
        const BROWSER_YANDEX = 'Yandex';
        // https://browser.yandex.ua/
        const BROWSER_NETSCAPE_NAVIGATOR = 'Netscape Navigator';
        // http://browser.netscape.com/ (DEPRECATED)
        const BROWSER_GALEON = 'Galeon';
        // http://galeon.sourceforge.net/ (DEPRECATED)
        const BROWSER_NETPOSITIVE = 'NetPositive';
        // http://en.wikipedia.org/wiki/NetPositive (DEPRECATED)
        const BROWSER_PHOENIX = 'Phoenix';
        // http://en.wikipedia.org/wiki/History_of_Mozilla_Firefox (DEPRECATED)
        const BROWSER_PLAYSTATION = 'PlayStation';
        const BROWSER_SAMSUNG = 'SamsungBrowser';
        const BROWSER_SILK = 'Silk';
        const BROWSER_I_FRAME = 'Iframely';
        const BROWSER_COCOA = 'CocoaRestClient';
        const PLATFORM_UNKNOWN = 'unknown';
        const PLATFORM_WINDOWS = 'Windows';
        const PLATFORM_WINDOWS_CE = 'Windows CE';
        const PLATFORM_APPLE = 'Apple';
        const PLATFORM_LINUX = 'Linux';
        const PLATFORM_OS2 = 'OS/2';
        const PLATFORM_BEOS = 'BeOS';
        const PLATFORM_IPHONE = 'iPhone';
        const PLATFORM_IPOD = 'iPod';
        const PLATFORM_IPAD = 'iPad';
        const PLATFORM_BLACKBERRY = 'BlackBerry';
        const PLATFORM_NOKIA = 'Nokia';
        const PLATFORM_FREEBSD = 'FreeBSD';
        const PLATFORM_OPENBSD = 'OpenBSD';
        const PLATFORM_NETBSD = 'NetBSD';
        const PLATFORM_SUNOS = 'SunOS';
        const PLATFORM_OPENSOLARIS = 'OpenSolaris';
        const PLATFORM_ANDROID = 'Android';
        const PLATFORM_PLAYSTATION = 'Sony PlayStation';
        const PLATFORM_ROKU = 'Roku';
        const PLATFORM_APPLE_TV = 'Apple TV';
        const PLATFORM_TERMINAL = 'Terminal';
        const PLATFORM_FIRE_OS = 'Fire OS';
        const PLATFORM_SMART_TV = 'SMART-TV';
        const PLATFORM_CHROME_OS = 'Chrome OS';
        const PLATFORM_JAVA_ANDROID = 'Java/Android';
        const PLATFORM_POSTMAN = 'Postman';
        const PLATFORM_I_FRAME = 'Iframely';
        const OPERATING_SYSTEM_UNKNOWN = 'unknown';
        /**
         * Class constructor
         *
         * @param string $userAgent
         */
        public function __construct($userAgent = '')
        {
        }
        /**
         * Reset all properties
         */
        public function reset()
        {
        }
        /**
         * Check to see if the specific browser is valid
         *
         * @param string $browserName
         * @return bool True if the browser is the specified browser
         */
        function isBrowser($browserName)
        {
        }
        /**
         * The name of the browser.  All return types are from the class contants
         *
         * @return string Name of the browser
         */
        public function getBrowser()
        {
        }
        /**
         * Set the name of the browser
         *
         * @param $browser string The name of the Browser
         */
        public function setBrowser($browser)
        {
        }
        /**
         * The name of the platform.  All return types are from the class contants
         *
         * @return string Name of the browser
         */
        public function getPlatform()
        {
        }
        /**
         * Set the name of the platform
         *
         * @param string $platform The name of the Platform
         */
        public function setPlatform($platform)
        {
        }
        /**
         * The version of the browser.
         *
         * @return string Version of the browser (will only contain alpha-numeric characters and a period)
         */
        public function getVersion()
        {
        }
        /**
         * Set the version of the browser
         *
         * @param string $version The version of the Browser
         */
        public function setVersion($version)
        {
        }
        /**
         * The version of AOL.
         *
         * @return string Version of AOL (will only contain alpha-numeric characters and a period)
         */
        public function getAolVersion()
        {
        }
        /**
         * Set the version of AOL
         *
         * @param string $version The version of AOL
         */
        public function setAolVersion($version)
        {
        }
        /**
         * Is the browser from AOL?
         *
         * @return boolean True if the browser is from AOL otherwise false
         */
        public function isAol()
        {
        }
        /**
         * Is the browser from a mobile device?
         *
         * @return boolean True if the browser is from a mobile device otherwise false
         */
        public function isMobile()
        {
        }
        /**
         * Is the browser from a tablet device?
         *
         * @return boolean True if the browser is from a tablet device otherwise false
         */
        public function isTablet()
        {
        }
        /**
         * Is the browser from a robot (ex Slurp,GoogleBot)?
         *
         * @return boolean True if the browser is from a robot otherwise false
         */
        public function isRobot()
        {
        }
        /**
         * Is the browser from facebook?
         *
         * @return boolean True if the browser is from facebook otherwise false
         */
        public function isFacebook()
        {
        }
        /**
         * Set the browser to be from AOL
         *
         * @param $isAol
         */
        public function setAol($isAol)
        {
        }
        /**
         * Set the Browser to be mobile
         *
         * @param boolean $value is the browser a mobile browser or not
         */
        protected function setMobile($value = true)
        {
        }
        /**
         * Set the Browser to be tablet
         *
         * @param boolean $value is the browser a tablet browser or not
         */
        protected function setTablet($value = true)
        {
        }
        /**
         * Set the Browser to be a robot
         *
         * @param boolean $value is the browser a robot or not
         */
        protected function setRobot($value = true)
        {
        }
        /**
         * Set the Browser to be a Facebook request
         *
         * @param boolean $value is the browser a robot or not
         */
        protected function setFacebook($value = true)
        {
        }
        /**
         * Get the user agent value in use to determine the browser
         *
         * @return string The user agent from the HTTP header
         */
        public function getUserAgent()
        {
        }
        /**
         * Set the user agent value (the construction will use the HTTP header value - this will overwrite it)
         *
         * @param string $agent_string The value for the User Agent
         */
        public function setUserAgent($agent_string)
        {
        }
        /**
         * Used to determine if the browser is actually "chromeframe"
         *
         * @since 1.7
         * @return boolean True if the browser is using chromeframe
         */
        public function isChromeFrame()
        {
        }
        /**
         * Returns a formatted string with a summary of the details of the browser.
         *
         * @return string formatted string with a summary of the browser
         */
        public function __toString()
        {
        }
        /**
         * Protected routine to calculate and determine what the browser is in use (including platform)
         */
        protected function determine()
        {
        }
        /**
         * Protected routine to determine the browser type
         *
         * @return boolean True if the browser was detected otherwise false
         */
        protected function checkBrowsers()
        {
        }
        /**
         * Determine if the user is using a BlackBerry (last updated 1.7)
         *
         * @return boolean True if the browser is the BlackBerry browser otherwise false
         */
        protected function checkBrowserBlackBerry()
        {
        }
        /**
         * Determine if the user is using an AOL User Agent (last updated 1.7)
         *
         * @return boolean True if the browser is from AOL otherwise false
         */
        protected function checkForAol()
        {
        }
        /**
         * Determine if the browser is the GoogleBot or not (last updated 1.7)
         *
         * @return boolean True if the browser is the GoogletBot otherwise false
         */
        protected function checkBrowserGoogleBot()
        {
        }
        /**
         * Determine if the browser is the YandexBot or not
         *
         * @return boolean True if the browser is the YandexBot otherwise false
         */
        protected function checkBrowserYandexBot()
        {
        }
        /**
         * Determine if the browser is the YandexImageResizer or not
         *
         * @return boolean True if the browser is the YandexImageResizer otherwise false
         */
        protected function checkBrowserYandexImageResizerBot()
        {
        }
        /**
         * Determine if the browser is the YandexCatalog or not
         *
         * @return boolean True if the browser is the YandexCatalog otherwise false
         */
        protected function checkBrowserYandexCatalogBot()
        {
        }
        /**
         * Determine if the browser is the YandexNews or not
         *
         * @return boolean True if the browser is the YandexNews otherwise false
         */
        protected function checkBrowserYandexNewsBot()
        {
        }
        /**
         * Determine if the browser is the YandexMetrika or not
         *
         * @return boolean True if the browser is the YandexMetrika otherwise false
         */
        protected function checkBrowserYandexMetrikaBot()
        {
        }
        /**
         * Determine if the browser is the YandexDirect or not
         *
         * @return boolean True if the browser is the YandexDirect otherwise false
         */
        protected function checkBrowserYandexDirectBot()
        {
        }
        /**
         * Determine if the browser is the YandexWebmaster or not
         *
         * @return boolean True if the browser is the YandexWebmaster otherwise false
         */
        protected function checkBrowserYandexWebmasterBot()
        {
        }
        /**
         * Determine if the browser is the YandexFavicons or not
         *
         * @return boolean True if the browser is the YandexFavicons otherwise false
         */
        protected function checkBrowserYandexFaviconsBot()
        {
        }
        /**
         * Determine if the browser is the YandexBlogs or not
         *
         * @return boolean True if the browser is the YandexBlogs otherwise false
         */
        protected function checkBrowserYandexBlogsBot()
        {
        }
        /**
         * Determine if the browser is the YandexMedia or not
         *
         * @return boolean True if the browser is the YandexMedia otherwise false
         */
        protected function checkBrowserYandexMediaBot()
        {
        }
        /**
         * Determine if the browser is the YandexVideo or not
         *
         * @return boolean True if the browser is the YandexVideo otherwise false
         */
        protected function checkBrowserYandexVideoBot()
        {
        }
        /**
         * Determine if the browser is the YandexImages or not
         *
         * @return boolean True if the browser is the YandexImages otherwise false
         */
        protected function checkBrowserYandexImagesBot()
        {
        }
        /**
         * Determine if the browser is the MSNBot or not (last updated 1.9)
         *
         * @return boolean True if the browser is the MSNBot otherwise false
         */
        protected function checkBrowserMSNBot()
        {
        }
        /**
         * Determine if the browser is the BingBot or not (last updated 1.9)
         *
         * @return boolean True if the browser is the BingBot otherwise false
         */
        protected function checkBrowserBingBot()
        {
        }
        /**
         * Determine if the browser is the W3C Validator or not (last updated 1.7)
         *
         * @return boolean True if the browser is the W3C Validator otherwise false
         */
        protected function checkBrowserW3CValidator()
        {
        }
        /**
         * Determine if the browser is the Yahoo! Slurp Robot or not (last updated 1.7)
         *
         * @return boolean True if the browser is the Yahoo! Slurp Robot otherwise false
         */
        protected function checkBrowserSlurp()
        {
        }
        /**
         * Determine if the browser is Brave or not
         *
         * @return boolean True if the browser is Brave otherwise false
         */
        protected function checkBrowserBrave()
        {
        }
        /**
         * Determine if the browser is Edge or not
         *
         * @return boolean True if the browser is Edge otherwise false
         */
        protected function checkBrowserEdge()
        {
        }
        /**
         * Determine if the browser is Internet Explorer or not (last updated 1.7)
         *
         * @return boolean True if the browser is Internet Explorer otherwise false
         */
        protected function checkBrowserInternetExplorer()
        {
        }
        /**
         * Determine if the browser is Opera or not (last updated 1.7)
         *
         * @return boolean True if the browser is Opera otherwise false
         */
        protected function checkBrowserOpera()
        {
        }
        /**
         * Determine if the browser is Chrome or not (last updated 1.7)
         *
         * @return boolean True if the browser is Chrome otherwise false
         */
        protected function checkBrowserChrome()
        {
        }
        /**
         * Determine if the browser is WebTv or not (last updated 1.7)
         *
         * @return boolean True if the browser is WebTv otherwise false
         */
        protected function checkBrowserWebTv()
        {
        }
        /**
         * Determine if the browser is NetPositive or not (last updated 1.7)
         *
         * @return boolean True if the browser is NetPositive otherwise false
         */
        protected function checkBrowserNetPositive()
        {
        }
        /**
         * Determine if the browser is Galeon or not (last updated 1.7)
         *
         * @return boolean True if the browser is Galeon otherwise false
         */
        protected function checkBrowserGaleon()
        {
        }
        /**
         * Determine if the browser is Konqueror or not (last updated 1.7)
         *
         * @return boolean True if the browser is Konqueror otherwise false
         */
        protected function checkBrowserKonqueror()
        {
        }
        /**
         * Determine if the browser is iCab or not (last updated 1.7)
         *
         * @return boolean True if the browser is iCab otherwise false
         */
        protected function checkBrowserIcab()
        {
        }
        /**
         * Determine if the browser is OmniWeb or not (last updated 1.7)
         *
         * @return boolean True if the browser is OmniWeb otherwise false
         */
        protected function checkBrowserOmniWeb()
        {
        }
        /**
         * Determine if the browser is Phoenix or not (last updated 1.7)
         *
         * @return boolean True if the browser is Phoenix otherwise false
         */
        protected function checkBrowserPhoenix()
        {
        }
        /**
         * Determine if the browser is Firebird or not (last updated 1.7)
         *
         * @return boolean True if the browser is Firebird otherwise false
         */
        protected function checkBrowserFirebird()
        {
        }
        /**
         * Determine if the browser is Netscape Navigator 9+ or not (last updated 1.7)
         * NOTE: (http://browser.netscape.com/ - Official support ended on March 1st, 2008)
         *
         * @return boolean True if the browser is Netscape Navigator 9+ otherwise false
         */
        protected function checkBrowserNetscapeNavigator9Plus()
        {
        }
        /**
         * Determine if the browser is Shiretoko or not (https://wiki.mozilla.org/Projects/shiretoko) (last updated 1.7)
         *
         * @return boolean True if the browser is Shiretoko otherwise false
         */
        protected function checkBrowserShiretoko()
        {
        }
        /**
         * Determine if the browser is Ice Cat or not (http://en.wikipedia.org/wiki/GNU_IceCat) (last updated 1.7)
         *
         * @return boolean True if the browser is Ice Cat otherwise false
         */
        protected function checkBrowserIceCat()
        {
        }
        /**
         * Determine if the browser is Nokia or not (last updated 1.7)
         *
         * @return boolean True if the browser is Nokia otherwise false
         */
        protected function checkBrowserNokia()
        {
        }
        /**
         * Determine if the browser is Palemoon or not
         *
         * @return boolean True if the browser is Palemoon otherwise false
         */
        protected function checkBrowserPalemoon()
        {
        }
        /**
         * Determine if the browser is UCBrowser or not
         *
         * @return boolean True if the browser is UCBrowser otherwise false
         */
        protected function checkBrowserUCBrowser()
        {
        }
        /**
         * Determine if the browser is Firefox or not
         *
         * @return boolean True if the browser is Firefox otherwise false
         */
        protected function checkBrowserFirefox()
        {
        }
        /**
         * Determine if the browser is Firefox or not (last updated 1.7)
         *
         * @return boolean True if the browser is Firefox otherwise false
         */
        protected function checkBrowserIceweasel()
        {
        }
        /**
         * Determine if the browser is Mozilla or not (last updated 1.7)
         *
         * @return boolean True if the browser is Mozilla otherwise false
         */
        protected function checkBrowserMozilla()
        {
        }
        /**
         * Determine if the browser is Lynx or not (last updated 1.7)
         *
         * @return boolean True if the browser is Lynx otherwise false
         */
        protected function checkBrowserLynx()
        {
        }
        /**
         * Determine if the browser is Amaya or not (last updated 1.7)
         *
         * @return boolean True if the browser is Amaya otherwise false
         */
        protected function checkBrowserAmaya()
        {
        }
        /**
         * Determine if the browser is Safari or not (last updated 1.7)
         *
         * @return boolean True if the browser is Safari otherwise false
         */
        protected function checkBrowserSafari()
        {
        }
        protected function checkBrowserSamsung()
        {
        }
        protected function checkBrowserSilk()
        {
        }
        protected function checkBrowserIframely()
        {
        }
        protected function checkBrowserCocoa()
        {
        }
        /**
         * Detect if URL is loaded from FacebookExternalHit
         *
         * @return boolean True if it detects FacebookExternalHit otherwise false
         */
        protected function checkFacebookExternalHit()
        {
        }
        /**
         * Detect if URL is being loaded from internal Facebook browser
         *
         * @return boolean True if it detects internal Facebook browser otherwise false
         */
        protected function checkForFacebookIos()
        {
        }
        /**
         * Detect Version for the Safari browser on iOS devices
         *
         * @return boolean True if it detects the version correctly otherwise false
         */
        protected function getSafariVersionOnIos()
        {
        }
        /**
         * Detect Version for the Chrome browser on iOS devices
         *
         * @return boolean True if it detects the version correctly otherwise false
         */
        protected function getChromeVersionOnIos()
        {
        }
        /**
         * Determine if the browser is iPhone or not (last updated 1.7)
         *
         * @return boolean True if the browser is iPhone otherwise false
         */
        protected function checkBrowseriPhone()
        {
        }
        /**
         * Determine if the browser is iPad or not (last updated 1.7)
         *
         * @return boolean True if the browser is iPad otherwise false
         */
        protected function checkBrowseriPad()
        {
        }
        /**
         * Determine if the browser is iPod or not (last updated 1.7)
         *
         * @return boolean True if the browser is iPod otherwise false
         */
        protected function checkBrowseriPod()
        {
        }
        /**
         * Determine if the browser is Android or not (last updated 1.7)
         *
         * @return boolean True if the browser is Android otherwise false
         */
        protected function checkBrowserAndroid()
        {
        }
        /**
         * Determine if the browser is Vivaldi
         *
         * @return boolean True if the browser is Vivaldi otherwise false
         */
        protected function checkBrowserVivaldi()
        {
        }
        /**
         * Determine if the browser is Yandex
         *
         * @return boolean True if the browser is Yandex otherwise false
         */
        protected function checkBrowserYandex()
        {
        }
        /**
         * Determine if the browser is a PlayStation
         *
         * @return boolean True if the browser is PlayStation otherwise false
         */
        protected function checkBrowserPlayStation()
        {
        }
        /**
         * Determine if the browser is Wget or not (last updated 1.7)
         *
         * @return boolean True if the browser is Wget otherwise false
         */
        protected function checkBrowserWget()
        {
        }
        /**
         * Determine if the browser is cURL or not (last updated 1.7)
         *
         * @return boolean True if the browser is cURL otherwise false
         */
        protected function checkBrowserCurl()
        {
        }
        /**
         * Determine the user's platform (last updated 2.0)
         */
        protected function checkPlatform()
        {
        }
    }
}

namespace SRFM\Inc\Lib {
    class Connect_Zip_AI{
        use \SRFM\Inc\Traits\Get_Instance;
    }
}

namespace SRFM\Inc {
    /**
     * Create New Form.
     *
     * @since 0.0.1
     */
    class Create_New_Form
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Constructor
         *
         * @since  0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Add custom API Route create-new-form.
         *
         * @return void
         * @since 0.0.1
         */
        public function register_custom_endpoint()
        {
        }
        /**
         * Checks whether a given request has permission to create new forms.
         *
         * @param WP_REST_Request $request Full details about the request.
         * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
         * @since 0.0.1
         */
        public function get_items_permissions_check($request)
        {
        }
        /**
         * Get default post metas for form when creating using template.
         *
         * @return array<string, array<int, int|string>> Default meta keys.
         * @since 0.0.3
         */
        public static function get_default_meta_keys()
        {
        }
        /**
         * Create new form post from selected template.
         *
         * @param \WP_REST_Request $data Form Markup Data.
         *
         * @return WP_Error|WP_REST_Response
         * @since 0.0.1
         */
        public static function create_form($data)
        {
        }
    }
    /**
     * Load Defaults Class.
     *
     * @since 0.0.1
     */
    class Generate_Form_Markup
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Constructor
         *
         * @since  0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Add custom API Route to generate form markup.
         *
         * @return void
         * @since 0.0.1
         */
        public function register_custom_endpoint()
        {
        }
        /**
         * Handle Form status
         *
         * @param int|string $id Contains form ID.
         * @param boolean    $show_title_current_page Boolean to show/hide form title.
         * @param string     $sf_classname additional class_name.
         * @param string     $post_type Contains post type.
         *
         * @return string|false
         * @since 0.0.1
         */
        public static function get_form_markup($id, $show_title_current_page = true, $sf_classname = '', $post_type = 'post')
        {
        }
    }
    /**
     * Activation Class.
     *
     * @since 0.0.1
     */
    class Activator
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Activation handler function.
         *
         * @since 0.0.1
         * @return void
         */
        public static function activate()
        {
        }
    }
    /**
     * Load Defaults Class.
     *
     * @since 0.0.1
     */
    class Forms_Data
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Constructor
         *
         * @since 0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Add custom API Route load-form-defaults
         *
         * @return void
         * @since 0.0.1
         */
        public function register_custom_endpoint()
        {
        }
        /**
         * Checks whether a given request has permission to read the form.
         *
         * @param \WP_REST_Request $request Full details about the request.
         * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
         * @since 0.0.1
         */
        public function get_form_permissions_check($request)
        {
        }
        /**
         * Handle Form status
         *
         * @param \WP_REST_Request $request Full details about the request.
         *
         * @return WP_REST_Response
         * @since 0.0.1
         */
        public function load_forms($request)
        {
        }
    }
    /**
     * Sureforms Background Process Class.
     *
     * @since 0.0.3
     */
    class Background_Process
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Namespace.
         *
         * @var string
         */
        protected $namespace = 'sureforms/v1';
        /**
         * Submission Id.
         *
         * @var int
         */
        protected $submission_id = 0;
        /**
         * Form Id.
         *
         * @var int
         */
        protected $form_id = 0;
        /**
         * Submission Data.
         *
         * @var array<mixed>
         */
        protected $submission_data = [];
        /**
         * Constructor
         *
         * @since  0.0.3
         */
        public function __construct()
        {
        }
        /**
         * Add custom API Route for 'After Submission' background process.
         *
         * @since 0.0.3
         * @return void
         */
        public function register_custom_endpoint()
        {
        }
        /**
         * Handle 'After Submission' background process
         *
         * @param \WP_REST_Request $request Request object or array containing form data.
         * @since 0.0.3
         * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
         */
        public function handle_after_submission($request)
        {
        }
        /**
         * Handle 'After Submission' background process
         *
         * @since 0.0.3
         * @return bool
         */
        public function trigger_after_submission_process()
        {
        }
    }
}
namespace SRFM\Inc\Blocks {
    /**
     * Block base class.
     */
    abstract class Base
    {
        /**
         * Optional directory to .json block data files.
         *
         * @var string
         * @since 0.0.1
         */
        protected $directory = '';
        /**
         * Holds the block.
         *
         * @var object
         * @since 0.0.1
         */
        protected $block;
        /**
         * Register the block for dynamic output
         *
         * @return void
         * @since 0.0.1
         */
        public function register()
        {
        }
        /**
         * Get the called class directory path
         *
         * @return string
         * @since 0.0.1
         */
        public function get_dir()
        {
        }
        /**
         * Optionally run a function to modify attributes before rendering.
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content   Post content.
         * @param array<mixed> $block Block attributes.
         *
         * @return boolean|string
         * @since 0.0.1
         */
        public function pre_render($attributes, $content, $block)
        {
        }
        /**
         * Render the block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string
         * @since 0.0.1
         */
        public function render($attributes, $content)
        {
        }
    }
}
namespace SRFM\Inc\Blocks\Input {
    /**
     * Address Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render the block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|boolean
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc\Blocks\Phone {
    /**
     * Phone Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render the block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|boolean
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc\Blocks\Address_Compact {
    /**
     * Address Compact Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render the block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|boolean
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc\Blocks\Email {
    /**
     * Address Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render the block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|boolean
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc\Blocks\GDPR {
    /**
     * GDPR Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render form GDPR block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|boolean
         * @since 0.0.3
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc\Blocks\Number {
    /**
     * Address Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render the block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|boolean
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc\Blocks\Checkbox {
    /**
     * Address Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render form checkbox block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|boolean
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc\Blocks\Multichoice {
    /**
     * Multichoice Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render the block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|boolean
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc\Blocks\Textarea {
    /**
     * Address Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render the block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|boolean
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc\Blocks\Url {
    /**
     * URL Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render the block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|boolean
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc\Blocks\Inlinebutton {
    /**
     * Inline Button Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render the block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|boolean
         * @since 0.0.3
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc\Blocks {
    /**
     * Manage Blocks registrations.
     *
     * @since 0.0.1
     */
    class Register
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Constructor
         *
         * @since  0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Register Blocks
         *
         * @param array<int, string>|false $blocks_dir Block directory.
         * @param string                   $namespace Namespace.
         * @param string                   $base Base.
         * @return void
         * @since 0.0.1
         */
        public static function register_block($blocks_dir, $namespace, $base)
        {
        }
    }
}
namespace SRFM\Inc\Blocks\Sform {
    /**
     * Sureforms_Form Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render the block.
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|false
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc\Blocks\Address {
    /**
     * Address Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render the block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|boolean
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc\Blocks\Dropdown {
    /**
     * Dropdown Block.
     */
    class Block extends \SRFM\Inc\Blocks\Base
    {
        /**
         * Render the block
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content Post content.
         *
         * @return string|boolean
         */
        public function render($attributes, $content = '')
        {
        }
    }
}
namespace SRFM\Inc {
    /**
     * Sureforms Helper Class.
     *
     * @since 0.0.1
     */
    class Helper
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Get common error message.
         *
         * @since 0.0.2
         * @return array<string>
         */
        public static function get_common_err_msg()
        {
        }
        /**
         * Convert a file URL to a file path.
         *
         * @param string $file_url The URL of the file.
         *
         * @since 1.3.0
         * @return string The file path.
         */
        public static function convert_fileurl_to_filepath($file_url)
        {
        }
        /**
         * Checks if current value is string or else returns default value
         *
         * @param mixed $data data which need to be checked if is string.
         *
         * @since 0.0.1
         * @return string
         */
        public static function get_string_value($data)
        {
        }
        /**
         * Checks if current value is number or else returns default value
         *
         * @param mixed $value data which need to be checked if is string.
         * @param int   $base value can be set is $data is not a string, defaults to empty string.
         *
         * @since 0.0.1
         * @return int
         */
        public static function get_integer_value($value, $base = 10)
        {
        }
        /**
         * Checks if current value is an array or else returns default value
         *
         * @param mixed $data Data which needs to be checked if it is an array.
         *
         * @since 0.0.3
         * @return array<mixed>
         */
        public static function get_array_value($data)
        {
        }
        /**
         * Extracts the field type from the dynamic field key ( or field slug ).
         *
         * @param string $field_key Dynamic field key.
         * @since 0.0.6
         * @return string Extracted field type.
         */
        public static function get_field_type_from_key($field_key)
        {
        }
        /**
         * Extracts the field label from the dynamic field key ( or field slug ).
         *
         * @param string $field_key Dynamic field key.
         * @since 1.1.1
         * @return string Extracted field label.
         */
        public static function get_field_label_from_key($field_key)
        {
        }
        /**
         * Returns the proper sanitize callback functions according to the field type.
         *
         * @param string $field_type HTML field type.
         * @since 0.0.6
         * @return callable Returns sanitize callbacks according to the provided field type.
         */
        public static function get_field_type_sanitize_function($field_type)
        {
        }
        /**
         * Sanitizes a numeric value.
         *
         * This function checks if the input value is numeric. If it is numeric, it sanitizes
         * the value to ensure it's a float or integer, allowing for fractions and thousand separators.
         * If the value is not numeric, it sanitizes it as a text field.
         *
         * @param mixed $value The value to be sanitized.
         * @since 0.0.6
         * @return int|float|string The sanitized value.
         */
        public static function sanitize_number($value)
        {
        }
        /**
         * This function sanitizes the submitted form data according to the field type.
         *
         * @param array<mixed> $form_data $form_data User submitted form data.
         * @since 0.0.6
         * @return array<mixed> $result Sanitized form data.
         */
        public static function sanitize_by_field_type($form_data)
        {
        }
        /**
         * This function performs array_map for multi dimensional array
         *
         * @param string       $function function name to be applied on each element on array.
         * @param array<mixed> $data_array array on which function needs to be performed.
         * @return array<mixed>
         * @since 0.0.1
         */
        public static function sanitize_recursively($function, $data_array)
        {
        }
        /**
         * Generates common markup liked label, etc
         *
         * @param int|string $form_id form id.
         * @param string     $type Type of form markup.
         * @param string     $label Label for the form markup.
         * @param string     $slug Slug for the form markup.
         * @param string     $block_id Block id for the form markup.
         * @param bool       $required If field is required or not.
         * @param string     $help Help for the form markup.
         * @param string     $error_msg Error message for the form markup.
         * @param bool       $is_unique Check if the field is unique.
         * @param string     $duplicate_msg Duplicate message for field.
         * @param bool       $override Override for error markup.
         * @return string
         * @since 0.0.1
         */
        public static function generate_common_form_markup($form_id, $type, $label = '', $slug = '', $block_id = '', $required = false, $help = '', $error_msg = '', $is_unique = false, $duplicate_msg = '', $override = false)
        {
        }
        /**
         * Get an SVG Icon
         *
         * @since 0.0.1
         * @param string $icon the icon name.
         * @param string $class if the baseline class should be added.
         * @param string $html Custom attributes inside svg wrapper.
         * @return string
         */
        public static function fetch_svg($icon = '', $class = '', $html = '')
        {
        }
        /**
         * Encrypt data using base64.
         *
         * @param string $input The input string which needs to be encrypted.
         * @since 0.0.1
         * @return string The encrypted string.
         */
        public static function encrypt($input)
        {
        }
        /**
         * Decrypt data using base64.
         *
         * @param string $input The input string which needs to be decrypted.
         * @since 0.0.1
         * @return string The decrypted string.
         */
        public static function decrypt($input)
        {
        }
        /**
         * Update an option from the database.
         *
         * @param string $key              The option key.
         * @param mixed  $value            The value to update.
         * @param bool   $network_override Whether to allow the network_override admin setting to be overridden on subsites.
         * @since 0.0.1
         * @return bool True if the option was updated, false otherwise.
         */
        public static function update_admin_settings_option($key, $value, $network_override = false)
        {
        }
        /**
         * Update an option from the database.
         *
         * @param int|string $post_id post id / form id.
         * @param string     $key meta key name.
         * @param bool       $single single or multiple.
         * @param mixed      $default default value.
         *
         * @since 0.0.1
         * @return string Meta value.
         */
        public static function get_meta_value($post_id, $key, $single = true, $default = '')
        {
        }
        /**
         * Wrapper for the WordPress's get_post_meta function with the support for default values.
         *
         * @param int|string $post_id Post ID.
         * @param string     $key The meta key to retrieve.
         * @param mixed      $default Default value.
         * @param bool       $single Optional. Whether to return a single value.
         * @since 0.0.8
         * @return mixed Meta value.
         */
        public static function get_post_meta($post_id, $key, $default = null, $single = true)
        {
        }
        /**
         * Returns query params data for instant form live preview.
         *
         * @since 0.0.8
         * @return array<mixed> Live preview data.
         */
        public static function get_instant_form_live_data()
        {
        }
        /**
         * Default dynamic block value.
         *
         * @since 0.0.1
         * @return array<string> Meta value.
         */
        public static function default_dynamic_block_option()
        {
        }
        /**
         * Get default dynamic block value.
         *
         * @param string $key meta key name.
         * @since 0.0.1
         * @return string Meta value.
         */
        public static function get_default_dynamic_block_option($key)
        {
        }
        /**
         * Checks whether a given request has appropriate permissions.
         *
         * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
         * @since 0.0.1
         */
        public static function get_items_permissions_check()
        {
        }
        /**
         * Check if the current user has a given capability.
         *
         * @param string $capability The capability to check.
         * @since 0.0.3
         * @return bool Whether the current user has the given capability or role.
         */
        public static function current_user_can($capability = '')
        {
        }
        /**
         * Get all the entries for the given form ids. The entries are older than the given days_old.
         *
         * @param int        $days_old The number of days old the entries should be.
         * @param array<int> $sf_form_ids The form ids for which the entries need to be fetched.
         * @since 0.0.2
         * @return array<mixed> the entries matching the criteria.
         */
        public static function get_entries_from_form_ids($days_old = 0, $sf_form_ids = [])
        {
        }
        /**
         * Decode block attributes.
         * The function reverses the effect of serialize_block_attributes()
         *
         * @link https://developer.wordpress.org/reference/functions/serialize_block_attributes/
         * @param string $encoded_data the encoded block attribute.
         * @since 0.0.2
         * @return string decoded block attribute
         */
        public static function decode_block_attribute($encoded_data = '')
        {
        }
        /**
         * Map slugs to submission data.
         *
         * @param array<mixed> $submission_data submission_data.
         * @since 0.0.3
         * @return array<mixed>
         */
        public static function map_slug_to_submission_data($submission_data = [])
        {
        }
        /**
         * Get forms options. Shows all the available forms in the dropdown.
         *
         * @since 0.0.5
         * @param string $key Determines the type of data to return.
         * @return array<mixed>
         */
        public static function get_sureforms($key = '')
        {
        }
        /**
         * Get all the forms.
         *
         * @since 0.0.5
         * @return array<mixed>
         */
        public static function get_sureforms_title_with_ids()
        {
        }
        /**
         * Get the CSS variables based on different field spacing sizes.
         *
         * @param string|null $field_spacing The field spacing size or boolean false to return complete sizes array.
         *
         * @since 0.0.7
         * @return array<string|mixed>
         */
        public static function get_css_vars($field_spacing = null)
        {
        }
        /**
         * Array of SureForms blocks which get have user input.
         *
         * @since 0.0.10
         * @return array<string>
         */
        public static function get_sureforms_blocks()
        {
        }
        /**
         * Process blocks and inner blocks.
         *
         * @param array<array<array<mixed>>> $blocks The block data.
         * @param array<string>              $slugs The array of existing slugs.
         * @param bool                       $updated The array of existing slugs.
         * @param string                     $prefix The array of existing slugs.
         * @param bool                       $skip_checking_existing_slug Skips the checking of existing slug if passed true. More information documented inside this function.
         * @since 0.0.10
         * @return array{array<array<array<mixed>>>,array<string>,bool}
         */
        public static function process_blocks($blocks, &$slugs, &$updated, $prefix = '', $skip_checking_existing_slug = false)
        {
        }
        /**
         * Generates slug based on the provided block and existing slugs.
         *
         * @param array<mixed>  $block The block data.
         * @param array<string> $slugs The array of existing slugs.
         * @param string        $prefix The array of existing slugs.
         * @since 0.0.10
         * @return string The generated unique block slug.
         */
        public static function generate_unique_block_slug($block, $slugs, $prefix)
        {
        }
        /**
         * This function ensures that the slug is unique.
         * If the slug is already taken, it appends a number to the slug to make it unique.
         *
         * @param string        $slug test to be converted to slug.
         * @param array<string> $slugs An array of existing slugs.
         * @since 0.0.10
         * @return string The unique slug.
         */
        public static function generate_slug($slug, $slugs)
        {
        }
        /**
         * Encode data to JSON. This function will encode the data with JSON_UNESCAPED_SLASHES and JSON_UNESCAPED_UNICODE.
         *
         * @since 0.0.11
         * @param array<mixed> $data The data to encode.
         * @return string|false The JSON representation of the value on success or false on failure.
         */
        public static function encode_json($data)
        {
        }
        /**
         * Returns true if SureTriggers plugin is ready for the custom app.
         *
         * @since 1.0.3
         * @return bool Returns true if SureTriggers plugin is ready for the custom app.
         */
        public static function is_suretriggers_ready()
        {
        }
        /**
         * Registers script translations for a specific handle.
         *
         * This function sets the script translations for a given script handle, allowing
         * localization of JavaScript strings using the specified text domain and path.
         *
         * @param string $handle The script handle to apply translations to.
         * @param string $domain Optional. The text domain for translations. Default is 'sureforms'.
         * @param string $path   Optional. The path to the translation files. Default is the 'languages' folder in the SureForms directory.
         *
         * @since 1.0.5
         * @return void
         */
        public static function register_script_translations($handle, $domain = 'sureforms', $path = SRFM_DIR . 'languages')
        {
        }
        /**
         * Validates whether the specified conditions or a single key-value pair exist in the request context.
         *
         * - If `$conditions` is provided as an array, it will validate all key-value pairs in `$conditions`
         *   against the `$_REQUEST` superglobal.
         * - If `$conditions` is empty, it validates a single key-value pair from `$key` and `$value`.
         *
         * @param string                $value      The expected value to match in the request if `$conditions` is not used.
         * @param string                $key        The key to check for in the request if `$conditions` is not used.
         * @param array<string, string> $conditions An optional associative array of key-value pairs to validate.
         * @since 1.1.1
         * @return bool Returns true if all conditions are met or the single key-value pair is valid, otherwise false.
         */
        public static function validate_request_context($value, $key = 'post_type', array $conditions = [])
        {
        }
        /**
         * Retrieve the list of excluded fields for form data processing.
         *
         * This method returns an array of field keys that should be excluded when
         * processing form data.
         *
         * @since 1.1.1
         * @return array<string> Returns the string array of excluded fields.
         */
        public static function get_excluded_fields()
        {
        }

        /**
	     * Filters and concatenates valid class names from an array.
	     *
	     * @param array<string> $class_names The array containing potential class names.
	     * @since x.x.x
	     * @return string The concatenated string of valid class names separated by spaces.
	     */
	    public static function join_strings( $class_names )
        {
        }
}
namespace SRFM\Inc\Global_Settings {
    /**
     * Email Summary Class.
     *
     * @since 0.0.2
     */
    class Email_Summary
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Constructor
         *
         * @since  0.0.1
         */
        public function __construct()
        {
        }
        /**
         * API endpoint to send test email.
         *
         * @return void
         * @since 0.0.2
         */
        public function register_custom_endpoint()
        {
        }
        /**
         * Send test email.
         *
         * @param WP_REST_Request $request Request object.
         * @return WP_REST_Response
         * @since 0.0.2
         */
        public function send_test_email($request)
        {
        }
        /**
         * Function to get the total number of entries for the last week.
         *
         * @since 0.0.2
         * @return string HTML table with entries count.
         */
        public static function get_total_entries_for_week()
        {
        }
        /**
         * Function to send the entries to admin mail.
         *
         * @param array<mixed>|bool $email_summary_options Email Summary Options.
         * @since 0.0.2
         * @return void
         */
        public static function send_entries_to_admin($email_summary_options)
        {
        }
        /**
         * Schedule the event action to run weekly.
         *
         * @return void
         * @since 0.0.2
         */
        public static function schedule_weekly_entries_email()
        {
        }
    }
    /**
     * Sureforms Global Settings.
     *
     * @since 0.0.1
     */
    class Global_Settings
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Namespace.
         *
         * @var string
         */
        protected $namespace = 'sureforms/v1';
        /**
         * Constructor
         *
         * @since  0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Add custom API Route submit-form
         *
         * @return void
         * @since 0.0.1
         */
        public function register_custom_endpoint()
        {
        }
        /**
         * Save global settings options.
         *
         * @param WP_REST_Request $request Request object.
         * @return WP_REST_Response|WP_Error
         *
         * @since 0.0.1
         */
        public static function srfm_save_global_settings($request)
        {
        }
        /**
         * Save General Settings
         *
         * @param array<mixed> $setting_options Setting options.
         * @return bool
         * @since 0.0.1
         */
        public static function srfm_save_general_settings($setting_options)
        {
        }
        /**
         * Save General Settings Dynamic Options
         *
         * @param array<mixed> $setting_options Setting options.
         * @return bool
         * @since 0.0.1
         */
        public static function srfm_save_general_settings_dynamic_opt($setting_options)
        {
        }
        /**
         * Save Email Summary Settings
         *
         * @param array<mixed> $setting_options Setting options.
         * @return bool
         * @since 0.0.1
         */
        public static function srfm_save_email_summary_settings($setting_options)
        {
        }
        /**
         * Save Security Settings
         *
         * @param array<mixed> $setting_options Setting options.
         * @return bool
         * @since 0.0.1
         */
        public static function srfm_save_security_settings($setting_options)
        {
        }
        /**
         * Get Settings Form Data
         *
         * @param \WP_REST_Request $request Request object or array containing form data.
         * @return void
         * @since 0.0.1
         */
        public static function srfm_get_general_settings($request)
        {
        }
    }
}
namespace SRFM\Admin\Views {
    /**
     * Single entry page.
     *
     * @since 0.0.13
     */
    class Single_Entry
    {
        /**
         * Initialize the properties.
         *
         * @since 0.0.13
         */
        public function __construct()
        {
        }
        /**
         * Render the single entry page if an entry is found.
         *
         * @since 0.0.13
         * @return void
         */
        public function render()
        {
        }
		/**
         * Helper method to paginate the provided array data.
         *
         * @param array<mixed> $array Array item to paginate.
         * @param int          $current_page Current page number.
         * @param int          $items_per_page Total items to return per pagination.
         * @since 1.3.0
         * @return array<mixed>
         */
        public static function paginate_array($array, $current_page, $items_per_page = 3)
        {
        }
        /**
         * Prints entry note item markup.
         *
         * @param array $note Single note array.
         * @since 1.3.0
         * @return void
         */
        public static function entry_note_item_markup($note)
        {
        }
        /**
         * Provides table markup for the entry logs.
         *
         * @param array<mixed> $entry_logs Entry logs stored in the database.
         * @since 1.3.0
         * @return void
         */
        public static function entry_logs_table_markup($entry_logs)
        {
        }
    }
    /**
     * Create the entries table using WP_List_Table.
     */
    class Entries_List_Table extends \WP_List_Table
    {
        /**
         * Stores the entries data fetched from database.
         *
         * @var array<mixed>
         * @since 0.0.13
         */
        protected $data = [];
        /**
         * Stores the count for the entries data fetched from the database according to the status.
         * It will be used for pagination.
         *
         * @var int
         * @since 0.0.13
         */
        public $entries_count;
        /**
         * Stores the count for all entries regardles of status.
         * It will be used for managing the no entries found page.
         *
         * @var int
         * @since 0.0.13
         */
        public $all_entries_count;
        /**
         * Stores the count for the trashed entries.
         * Used for displaying the no entries found page.
         *
         * @var int
         * @since 0.0.13
         */
        public $trash_entries_count;
        /**
         * Constructor.
         *
         * @since 0.0.13
         * @return void
         */
        public function __construct()
        {
        }
        /**
         * Override the parent columns method. Defines the columns to use in your listing table.
         *
         * @since 0.0.13
         * @return array
         */
        public function get_columns()
        {
        }
        /**
         * Define the sortable columns.
         *
         * @since 0.0.13
         * @return array
         */
        public function get_sortable_columns()
        {
        }
        /**
         * Bulk action items.
         *
         * @since 0.0.13
         * @return array $actions Bulk actions.
         */
        public function get_bulk_actions()
        {
        }
        /**
         * Message to be displayed when there are no entries.
         *
         * @since 0.0.13
         * @return void
         */
        public function no_items()
        {
        }
        /**
         * Prepare the items for the table to process.
         *
         * @since 0.0.13
         * @return void
         */
        public function prepare_items()
        {
        }
        /**
         * Define what data to show on each column of the table.
         *
         * @param array  $item Column data.
         * @param string $column_name Current column name.
         *
         * @since 0.0.13
         * @return mixed
         */
        public function column_default($item, $column_name)
        {
        }
        /**
         * Callback function for checkbox field.
         *
         * @param array $item Columns items.
         * @return string
         * @since 0.0.13
         */
        public function column_cb($item)
        {
        }
        /**
         * Define the data for the "id" column and return the markup.
         *
         * @param array $item Column data.
         *
         * @since 0.0.13
         * @return string
         */
        protected function column_id($item)
        {
        }
        /**
         * Define the data for the "form name" column and return the markup.
         *
         * @param array $item Column data.
         *
         * @since 0.0.13
         * @return string
         */
        protected function column_form_name($item)
        {
        }
        /**
         * Define the data for the "status" column and return the markup.
         *
         * @param array $item Column data.
         *
         * @since 0.0.13
         * @return string
         */
        protected function column_status($item)
        {
        }
        /**
         * Define the data for the "first field" column and return the markup.
         *
         * @param array $item Column data.
         *
         * @since 0.0.13
         * @return string
         */
        protected function column_first_field($item)
        {
        }
        /**
         * Define the data for the "submitted on" column and return the markup.
         *
         * @param array $item Column data.
         *
         * @since 0.0.13
         * @return string
         */
        protected function column_created_at($item)
        {
        }
        /**
         * Returns array of row actions for packages.
         *
         * @param array $item Column data.
         *
         * @since 0.0.13
         * @return array
         */
        protected function package_row_actions($item)
        {
        }
        /**
         * Extra controls to be displayed between bulk actions and pagination.
         *
         * @param string $which Which table navigation is it... Is it top or bottom.
         *
         * @since 0.0.13
         * @return void
         */
        protected function extra_tablenav($which)
        {
        }
        /**
         * Generates the table navigation above or below the table.
         *
         * @param string $which is it the top or bottom of the table.
         *
         * @since 0.0.13
         * @return void
         */
        protected function display_tablenav($which)
        {
        }
        /**
         * Display the available form name to filter entries.
         *
         * @since 0.0.13
         * @return void
         */
        protected function display_form_filter()
        {
        }
        /**
         * Display the month and year from which the entries are present to filter entries according to time.
         *
         * @since 0.0.13
         * @return void
         */
        protected function display_month_filter()
        {
        }
        /**
         * Entries table form search input markup.
         * Currently search is based on entry ID only and not text.
         *
         * @param string $text The 'submit' button label.
         * @param int    $input_id ID attribute value for the search input field.
         *
         * @since 0.0.13
         * @return void
         */
        public function search_box_markup($text, $input_id)
        {
        }
        /**
         * Displays the table.
         *
         * @since 0.0.13
         */
        public function display()
        {
        }
        /**
         * List of CSS classes for the "WP_List_Table" table element.
         *
         * @since 0.0.13
         * @return array<string>
         */
        protected function get_table_classes()
        {
        }
        /**
         * Get the views for the entries table.
         *
         * @since 0.0.13
         * @return array<string,string>
         */
        protected function get_views()
        {
        }
        /**
         * Process bulk actions.
         *
         * @since 0.0.13
         * @return void
         */
        public static function process_bulk_actions()
        {
        }
        /**
         * Check if the current page is a trash list.
         *
         * @since 0.0.13
         * @return bool
         */
        public static function is_trash_view()
        {
        }
        /**
         * Common function to update the status of an entry.
         *
         * @param int    $entry_id The ID of the entry to update.
         * @param string $action The action to perform.
         * @param string $view The view to handle redirection.
         *
         * @since 0.0.13
         * @return void
         */
        public static function handle_entry_status($entry_id, $action, $view = '')
        {
        }
        /**
         * Display admin notice for bulk actions.
         *
         * @since 0.0.13
         * @return void
         */
        public static function display_bulk_action_notice()
        {
        }
    }
}
namespace SRFM\Inc {
    /**
     * Sureforms Submit Class.
     *
     * @since 0.0.1
     */
    class Form_Submit
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Namespace.
         *
         * @var string
         */
        protected $namespace = 'sureforms/v1';
        /**
         * Constructor
         *
         * @since  0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Add custom API Route submit-form
         *
         * @return void
         * @since 0.0.1
         */
        public function register_custom_endpoint()
        {
        }
        /**
         * Check whether a given request has permission access route.
         *
         * @since 0.0.1
         * @param  WP_REST_Request $request Full details about the request.
         * @return WP_Error|boolean
         */
        public function permissions_check($request)
        {
        }
        /**
         * Handle Form Submission
         *
         * @param \WP_REST_Request $request Request object or array containing form data.
         * @since 0.0.1
         * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
         */
        public function handle_form_submission($request)
        {
        }
        /**
         * Change the upload directory
         *
         * @param array<mixed> $dirs upload directory.
         * @return array<mixed>
         * @since 0.0.1
         */
        public function change_upload_dir($dirs)
        {
        }
        /**
         * Send Email and Create Entry.
         *
         * @param array<string> $form_data Request object or array containing form data.
         * @since 0.0.1
         * @return array<mixed> Array containing the response data.
         */
        public function handle_form_entry($form_data)
        {
        }
        /**
         * Send Email.
         *
         * @param string                $id       Form ID.
         * @param array<string, string> $meta_data Meta data.
         * @since 0.0.1
         * @return array<mixed> Array containing the response data.
         */
        public static function send_email($id, $meta_data)
        {
        }
        /**
         * Retrieve all entries data for a specific form ID to check for unique values.
         *
         * @since 0.0.1
         * @return void
         */
        public function field_unique_validation()
        {
        }
        /**
         * Function to save allowed block data.
         *
         * @since 0.0.1
         * @return void
         */
        public function srfm_global_update_allowed_block()
        {
        }
        /**
         * Function to save enable/disable data.
         *
         * @since 0.0.1
         * @return void
         */
        public function srfm_global_sidebar_enabled()
        {
        }
    }
    /**
     * Post Types Main Class.
     *
     * @since 0.0.1
     */
    class Post_Types
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Constructor
         *
         * @since  0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Add SureForms menu.
         *
         * @param string $title Parent slug.
         * @param string $subtitle Parent slug.
         * @param string $image Parent slug.
         * @param string $button_text Parent slug.
         * @param string $button_url Parent slug.
         * @return void
         * @since 0.0.1
         */
        public function get_blank_page_markup($title, $subtitle, $image, $button_text = '', $button_url = '')
        {
        }
        /**
         * Render blank state for add new form screen.
         *
         * @param string $post_type Post type.
         * @return void
         * @since  0.0.1
         */
        public function sureforms_render_blank_state($post_type)
        {
        }
        /**
         * Registers the forms and submissions post types.
         *
         * @return void
         * @since 0.0.1
         */
        public function register_post_types()
        {
        }
        /**
         * Customize the URL of new form in the admin bar.
         *
         * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance.
         *
         * @return void
         * @since 0.0.1
         */
        public function custom_admin_bar_menu_url($wp_admin_bar)
        {
        }
        /**
         * Modify post update message for Entry post type.
         *
         * @param string $messages Post type.
         * @return string
         * @since  0.0.1
         */
        public function entries_updated_message($messages)
        {
        }
        /**
         * Remove publishing actions from single entries page.
         *
         * @return void
         * @since  0.0.1
         */
        public function remove_entries_publishing_actions()
        {
        }
        /**
         * Modify list row actions.
         *
         * @param array<mixed> $actions An array of row action links.
         * @param \WP_Post     $post  The current WP_Post object.
         *
         * @return array<mixed> $actions Modified row action links.
         * @since  0.0.1
         */
        public function modify_entries_list_row_actions($actions, $post)
        {
        }
        /**
         * Modify list bulk actions.
         *
         * @param array<mixed> $bulk_actions An array of bulk action links.
         * @since 0.0.1
         * @return array<mixed> $bulk_actions Modified action links.
         */
        public function register_modify_bulk_actions($bulk_actions)
        {
        }
        /**
         * Show blank slate styles.
         *
         * @return void
         * @since  0.0.1
         */
        public function get_blank_state_styles()
        {
        }
        /**
         * Show blank slate.
         *
         * @param string $which String which tablenav is being shown.
         * @return void
         * @since  0.0.1
         */
        public function maybe_render_blank_form_state($which)
        {
        }
        /**
         * Set up a div for the header to render into it.
         *
         * @return void
         * @since  0.0.1
         */
        public static function embed_page_header()
        {
        }
        /**
         * Registers the sureforms metas.
         *
         * @return void
         * @since 0.0.1
         */
        public function register_post_metas()
        {
        }
        /**
         * Custom Shortcode.
         *
         * @param array<mixed> $atts Attributes.
         * @return string|false. $content Post Content.
         * @since 0.0.1
         */
        public function forms_shortcode(array $atts)
        {
        }
        /**
         * Add custom column header.
         *
         * @param array<mixed> $columns Attributes.
         * @return array<mixed> $columns Post Content.
         * @since 0.0.1
         */
        public function custom_form_columns($columns)
        {
        }
        /**
         * Populate custom column with data.
         *
         * @param string  $column Attributes.
         * @param integer $post_id Attributes.
         * @return void
         * @since 0.0.1
         */
        public function custom_form_column_data($column, $post_id)
        {
        }
        /**
         * Show the import form popup
         *
         * @since 0.0.1
         * @return void
         */
        public function import_form_popup()
        {
        }
        /**
         * Redirect to home page if instant form is not enabled.
         *
         * @since 0.0.1
         * @return void
         */
        public function srfm_instant_form_redirect()
        {
        }
    }
    /**
     * SureForms events scheduler class.
     *
     * @since 0.0.3
     */
    class Events_Scheduler
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Constructor
         *
         * @since 0.0.3
         */
        public function __construct()
        {
        }
        /**
         * Schedules a action that runs every 24 hours for SureForms.
         *
         * @hooked - init
         * @uses as_has_scheduled_action() To check if the action is already scheduled.
         * @uses as_schedule_recurring_action() To schedule a recurring action.
         * @link https://actionscheduler.org/api/
         * @since 0.0.3
         * @return void
         */
        public function srfm_schedule_daily_action()
        {
        }
        /**
         * Unschedule any action.
         *
         * @param string $hook Event hook name.
         * @since 0.0.3
         * @return void
         */
        public static function unschedule_events($hook)
        {
        }
    }
    /**
     * Gutenberg hooks handler class.
     *
     * @since 0.0.1
     */
    class Gutenberg_Hooks
    {
        /**
         * Block patterns to register.
         *
         * @var array<mixed>
         */
        protected $patterns = [];

        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Class constructor.
         *
         * @return void
         * @since 0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Disable Sureforms_Form Block and allowed only sureforms block inside Sureform CPT editor.
         *
         * @param bool|string[]            $allowed_block_types Array of block types.
         * @param \WP_Block_Editor_Context $editor_context The current block editor context.
         * @return array<mixed>|void
         * @since 0.0.1
         */
        public function disable_forms_wrapper_block($allowed_block_types, $editor_context)
        {
        }
        /**
         * Register our custom block category.
         *
         * @param array<mixed> $categories Array of categories.
         * @return array<mixed>
         * @since 0.0.1
         */
        public function register_block_categories($categories)
        {
        }
        /**
         * Register our block patterns.
         *
         * @return void
         * @since 0.0.1
         */
        public function register_block_patterns()
        {
        }
        /**
         * Add Form Editor Scripts.
         *
         * @return void
         * @since 0.0.1
         */
        public function form_editor_screen_assets()
        {
        }
        /**
         * Register all editor scripts.
         *
         * @return void
         * @since 0.0.1
         */
        public function block_editor_assets()
        {
        }
        /**
         * This function generates slug for sureforms blocks.
         * Generates slug only if slug attribute of block is empty.
         * Ensures that all sureforms blocks have unique slugs.
         *
         * @param int      $post_id current sureforms form post id.
         * @param \WP_Post $post SureForms post object.
         * @since 0.0.3
         * @return void
         */
        public function update_field_slug($post_id, $post)
        {
        }
        /**
         * Generates slug based on the provided block and existing slugs.
         *
         * @param array<string,string|array<string,mixed>> $block The block data.
         * @param array<string>                            $slugs The array of existing slugs.
         * @since 0.0.3
         * @return string The generated unique block slug.
         */
        public function generate_unique_block_slug($block, $slugs)
        {
        }
        /**
         * This function ensures that the slug is unique.
         * If the slug is already taken, it appends a number to the slug to make it unique.
         *
         * @param string        $slug test to be converted to slug.
         * @param array<string> $slugs An array of existing slugs.
         * @since 0.0.3
         * @return string The unique slug.
         */
        public function generate_slug($slug, $slugs)
        {
        }
    }
}
namespace SRFM {
    /**
     * Plugin_Loader
     *
     * @since 0.0.1
     */
    class Plugin_Loader
    {
        /**
         * Initiator
         *
         * @since 0.0.1
         * @return object initialized object of class.
         */
        public static function get_instance()
        {
        }
        /**
         * Autoload classes.
         *
         * @param string $class class name.
         * @return void
         */
        public function autoload($class)
        {
        }
        /**
         * Constructor
         *
         * @since 0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Activation Reset
         *
         * @return void
         * @since 0.0.1
         */
        public function activation_redirect()
        {
        }
        /**
         * Load Classes.
         *
         * @return void
         * @since 0.0.1
         */
        public function load_classes()
        {
        }
        /**
         * Load Plugin Text Domain.
         * This will load the translation textdomain depending on the file priorities.
         *      1. Global Languages /wp-content/languages/sureforms/ folder
         *      2. Local directory /wp-content/plugins/sureforms/languages/ folder
         *
         * @since 0.0.1
         * @return void
         */
        public function load_textdomain()
        {
        }
        /**
         * Loads plugin files.
         *
         * @since 0.0.1
         *
         * @return void
         */
        public function load_plugin()
        {
        }
        /**
         * Load Core Files.
         *
         * @since 0.0.1
         *
         * @return void
         */
        public function load_core_files()
        {
        }
    }
}
namespace SRFM\API {
    /**
     * Core class used to access block patterns via the REST API.
     *
     * @see WP_REST_Controller
     * @since 0.0.1
     */
    class Block_Patterns extends \WP_REST_Controller
    {
        use \SRFM\Inc\Traits\Get_Instance;
        /**
         * Class Constructor
         *
         * @return void
         */
        public function __construct()
        {
        }
        /**
         * Registers the routes for the objects of the controller.
         *
         * @since 0.0.1
         * @return void
         */
        public function register_routes()
        {
        }
        /**
         * Checks whether a given request has permission to read block patterns.
         *
         * @param WP_REST_Request $request Full details about the request.
         * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
         * @since 0.0.1
         */
        public function get_items_permissions_check($request)
        {
        }
        /**
         * Retrieves all block patterns.
         *
         * @param WP_REST_Request $request Full details about the request.
         * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
         * @since 0.0.1
         */
        public function get_items($request)
        {
        }
        /**
         * Prepare a raw block pattern before it gets output in a REST API response.
         *
         * @param array<mixed>    $item    Raw pattern as registered, before any changes.
         * @param WP_REST_Request $request Request object.
         * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
         * @since 0.0.1
         */
        public function prepare_item_for_response($item, $request)
        {
        }
        /**
         * Retrieves the block pattern schema, conforming to JSON Schema.
         *
         * @return array<mixed>  Item schema data.
         * @since 0.0.1
         */
        public function get_item_schema()
        {
        }
    }
}
namespace {
    /**
     * Class Spec_Block_Loader.
     */
    final class Spec_Block_Loader
    {
        /**
         *  Initiator
         */
        public static function get_instance()
        {
        }
        /**
         * Constructor
         */
        public function __construct()
        {
        }
        /**
         * Loads plugin files.
         *
         * @since 0.0.1
         *
         * @return void
         */
        public function load_plugin()
        {
        }
    }
    /**
     * Spec_Init_Blocks.
     *
     * @package Sureforms
     */
    class Spec_Init_Blocks
    {
        /**
         * Store Json variable
         *
         * @since 0.0.1
         * @var instance
         */
        public static $icon_json;
        /**
         * As our svg icon is too long array so we will divide that into number of icon chunks.
         *
         * @var int
         * @since 0.0.1
         */
        public static $number_of_icon_chunks = 4;
        /**
         *  Initiator
         */
        public static function get_instance()
        {
        }
        /**
         * Constructor
         */
        public function __construct()
        {
        }
        /**
         * Enqueue Gutenberg block assets for both frontend + backend.
         *
         * @since 0.0.1
         */
        public function block_assets()
        {
        }
        /**
         * Enqueue assets for both backend.
         *
         * @since 0.0.1
         */
        public function editor_assets()
        {
        }
    }
    /**
     * Class for Spectra compatibility
     */
    class Spec_Spectra_Compatibility
    {
        /**
         * Initiator
         *
         * @since 0.0.1
         */
        public static function get_instance()
        {
        }
        /**
         * Constructor
         *
         * @since 0.0.1
         */
        public function __construct()
        {
        }
        /**
         * Clear theme cached CSS if required.
         */
        public function spectra_editor_assets()
        {
        }
        /**
         * Localize SVG icon scripts in chunks.
         * Ex - if 1800 icons available so we will localize 4 variables for it.
         *
         * @since 0.0.1
         * @return void
         */
        public function add_svg_icon_assets()
        {
        }
    }
    /**
     * Class Spec_Filesystem.
     */
    class Spec_Filesystem
    {
        /**
         *  Initiator
         */
        public static function get_instance()
        {
        }
        /**
         * Get an instance of WP_Filesystem.
         *
         * @since 0.0.1
         */
        public function get_filesystem()
        {
        }
        /**
         * Method to direct.
         *
         * @since 0.0.1
         */
        public function filesystem_method()
        {
        }
        /**
         * Sets credentials to true.
         *
         * @since 0.0.1
         */
        public function request_filesystem_credentials()
        {
        }
    }
    /**
     * Class Sureforms_Spec_Block_JS.
     */
    class Spec_Block_JS
    {
        /**
         * Adds Google fonts for Next Step Button.
         *
         * @since 0.0.1
         * @param array $attr the blocks attr.
         */
        public static function blocks_separator_gfont($attr)
        {
        }
        /**
         * Adds Google fonts for Advanced Heading block.
         *
         * @since 0.0.1
         * @param array $attr the blocks attr.
         */
        public static function blocks_advanced_heading_gfont($attr)
        {
        }
        /**
         * Adds Google fonts for Advanced Image block.
         *
         * @since 0.0.1
         * @param array $attr the blocks attr.
         */
        public static function blocks_image_gfont($attr)
        {
        }
    }
    /**
     * Class Spec_Gb_Helper.
     */
    final class Spec_Gb_Helper
    {
        /**
         * Member Variable
         *
         * @since 0.0.1
         * @var instance
         */
        public static $block_list;
        /**
         * Current Block List
         *
         * @since 0.0.1
         * @var current_block_list
         */
        public static $current_block_list = [];
        /**
         * Page Blocks Variable
         *
         * @since 0.0.1
         * @var instance
         */
        public static $page_blocks;
        /**
         * Stylesheet
         *
         * @since 0.0.1
         * @var stylesheet
         */
        public static $stylesheet;
        /**
         * Script
         *
         * @since 0.0.1
         * @var script
         */
        public static $script;
        /**
         * Sureforms Block Flag
         *
         * @since 0.0.1
         * @var srfm_flag
         */
        public static $srfm_flag = \false;
        /**
         * Static CSS Added Array
         *
         * @since 0.0.1
         * @var array
         */
        public $static_css_blocks = [];
        /**
         * Google fonts to enqueue
         *
         * @since 0.0.1
         * @var array
         */
        public static $gfonts = [];
        /**
         * As our svg icon is too long array so we will divide that into number of icon chunks.
         *
         * @var int
         * @since 0.0.1
         */
        public static $number_of_icon_chunks = 4;
        /**
         * Store Json variable
         *
         * @since 0.0.1
         * @var instance
         */
        public static $icon_json;
        /**
         * We have icon list in chunks in this variable we will merge all insides array into one single array.
         *
         * @var array
         * @since 0.0.1
         */
        public static $icon_array_merged = [];
        /**
         *  Initiator
         *
         * @since 0.0.1
         */
        public static function get_instance()
        {
        }
        /**
         * Constructor
         */
        public function __construct()
        {
        }
        /**
         * Get form id content.
         *
         * @param string $id form id.
         * @since 0.0.1
         * @return void
         */
        public function form_content_by_id($id)
        {
        }
        /**
         * Render function.
         *
         * @param string $block_content Entire Block Content.
         * @param array  $block Block Properties As An Array.
         * @return string
         */
        public function generate_render_styles($block_content, $block)
        {
        }
        /**
         * WP Actions.
         */
        public function wp_actions()
        {
        }
        /**
         * Load the front end Google Fonts.
         */
        public function frontend_gfonts()
        {
        }
        /**
         * Set alignment css function.
         *
         * @param string $align passed.
         * @since 0.0.1
         * @return array
         */
        public static function alignment_css($align)
        {
        }
        /**
         * Print the Script in footer.
         */
        public function print_script()
        {
        }
        /**
         * Print the Stylesheet in header.
         */
        public function print_stylesheet()
        {
        }
        /**
         * Generates stylesheet and appends in head tag.
         *
         * @since 0.0.1
         */
        public function generate_assets()
        {
        }
        /**
         * Generates stylesheet in loop.
         *
         * @param object $this_post Current Post Object.
         * @since 0.0.1
         */
        public function get_generated_stylesheet($this_post)
        {
        }
        /**
         * Enqueue Gutenberg block assets for both frontend + backend.
         *
         * @since 0.0.1
         */
        public function block_assets()
        {
        }
        /**
         * Parse Guten Block.
         *
         * @param string $content the content string.
         * @since 0.0.1
         */
        public function parse($content)
        {
        }
        /**
         * Generates stylesheet for reusable blocks.
         *
         * @param array $blocks Blocks array.
         * @since 0.0.1
         */
        public function get_assets($blocks)
        {
        }
        /**
         * Get Typography Dynamic CSS.
         *
         * @param  array  $attr The Attribute array.
         * @param  string $slug The field slug.
         * @param  string $selector The selector array.
         * @param  array  $combined_selectors The combined selector array.
         * @since  0.0.1
         * @return bool|string
         */
        public static function get_typography_css($attr, $slug, $selector, $combined_selectors)
        {
        }
        /**
         * Get CSS value
         *
         * Syntax:
         *
         *  get_css_value( VALUE, UNIT );
         *
         * E.g.
         *
         *  get_css_value( VALUE, 'em' );
         *
         * @param string $value  CSS value.
         * @param string $unit  CSS unit.
         * @since 0.0.1
         */
        public static function get_css_value($value = '', $unit = '')
        {
        }
        /**
         * Parse CSS into correct CSS syntax.
         *
         * @param array  $combined_selectors The combined selector array.
         * @param string $id The selector ID.
         * @since 0.0.1
         */
        public static function generate_all_css($combined_selectors, $id)
        {
        }
        /**
         * Generate the Box Shadow or Text Shadow CSS.
         *
         * For Text Shadow CSS:
         * ( 'spread', 'position' ) should not be sent as params during the function call.
         * ( 'spread_unit' ) will have no effect.
         *
         * For Box/Text Shadow Hover CSS:
         * ( 'alt_color' ) should be set as the attribute used for ( 'color' ) in Box/Text Shadow Normal CSS.
         *
         * @param array $shadow_properties  Array containing the necessary shadow properties.
         * @return string                   The generated border CSS or an empty string on early return.
         *
         * @since 0.0.1
         */
        public static function generate_shadow_css($shadow_properties)
        {
        }
        /**
         * Parse CSS into correct CSS syntax.
         *
         * @param array  $selectors The block selectors.
         * @param string $id The selector ID.
         * @since 0.0.1
         */
        public static function generate_css($selectors, $id)
        {
        }
        /**
         * Adds Google fonts all blocks.
         *
         * @param array  $load_google_font the blocks attr.
         * @param string $font_family the blocks attr.
         * @param array  $font_weight the blocks attr.
         */
        public static function blocks_google_font($load_google_font, $font_family, $font_weight)
        {
        }
        /**
         * Generates CSS recurrsively.
         *
         * @param object $block The block object.
         * @since 0.0.1
         */
        public function get_block_css_and_js($block)
        {
        }
        /**
         * Get block dynamic CSS selector with filters applied for extending it.
         *
         * @param string $block_name Block name to filter.
         * @param array  $selectors Array of selectors to filter.
         * @param array  $attr Attributes.
         * @return array Combined selectors array.
         * @since 0.0.1
         */
        public static function get_combined_selectors($block_name, $selectors, $attr)
        {
        }
        /**
         * Get Static CSS of Block.
         *
         * @param string $block_name Block Name.
         *
         * @return string Static CSS.
         * @since 0.0.1
         */
        public function get_block_static_css($block_name)
        {
        }
        /**
         * Border attribute generation Function.
         *
         * @since 0.0.1
         * @param  array $prefix   Attribute Prefix.
         * @param array $default_args  default attributes args.
         * @return array
         */
        public function generate_php_border_attribute($prefix, $default_args = [])
        {
        }
        /**
         * Get Json Data.
         *
         * @since 0.0.1
         * @return array
         */
        public static function backend_load_font_awesome_icons()
        {
        }
        /**
         * Generate SVG.
         *
         * @since 0.0.1
         * @param  array $icon Decoded fontawesome json file data.
         * @param  boolean $return Return or echo the svg markup.
         */
        public static function render_svg_html($icon, $return = false)
        {
        }
    }
    /**
     * Class Spec_Block_Helper.
     */
    class Spec_Block_Helper
    {
        /**
         * Get Icon Block CSS
         *
         * @since 0.0.1
         * @param array  $attr The block attributes.
         * @param string $id The selector ID.
         * @return array The Widget List.
         */
        public static function get_icon_css($attr, $id)
        {
        }
        /**
         * Get Image Block CSS
         *
         * @since 0.0.1
         * @param array  $attr The block attributes.
         * @param string $id The selector ID.
         * @return array The Widget List.
         */
        public static function get_image_css($attr, $id)
        {
        }
        /**
         * Get Heading Block CSS
         *
         * @since 0.0.1
         * @param array  $attr The block attributes.
         * @param string $id The selector ID.
         * @return array The Widget List.
         */
        public static function get_advanced_heading_css($attr, $id)
        {
        }
        /**
         * Get Separator Block CSS
         *
         * @since 0.0.1
         * @param array  $attr The block attributes.
         * @param string $id The selector ID.
         * @return array The Widget List.
         */
        public static function get_separator_css($attr, $id)
        {
        }
        /**
         * Border CSS generation Function.
         *
         * @since 0.0.1
         * @param  array  $attr   Attribute List.
         * @param  string $prefix Attribuate prefix .
         * @param  string $device Responsive.
         * @return array         border css array.
         */
        public static function generate_border_css($attr, $prefix, $device = 'desktop')
        {
        }
        /**
         * Get CSS value
         *
         * Syntax:
         *
         *  get_css_value( VALUE, UNIT );
         *
         * E.g.
         *
         *  get_css_value( VALUE, 'em' );
         *
         * @param string $value  CSS value.
         * @param string $unit  CSS unit.
         * @since 0.0.1
         */
        public static function get_css_value($value = '', $unit = '')
        {
        }
        /**
         * Deprecated Border CSS generation Function.
         *
         * @since 0.0.1
         * @param  array  $current_css   Current style list.
         * @param  string $border_width   Border Width.
         * @param  string $border_radius Border Radius.
         * @param  string $border_color Border Color.
         * @param string $border_style Border Style.
         */
        public static function generate_deprecated_border_css($current_css, $border_width, $border_radius, $border_color = '', $border_style = '')
        {
        }
    }
    /**
     * Class Spec_Block_Config.
     */
    class Spec_Block_Config
    {
        /**
         * Block Attributes
         *
         * @var block_attributes
         */
        public static $block_attributes = \null;
        /**
         * Block Assets
         *
         * @since 0.0.1
         * @var block_attributes
         */
        public static $block_assets_css = \null;
        /**
         * Block Assets
         *
         * @var block_attributes
         */
        public static $block_assets = \null;
        /**
         * Get Widget List.
         *
         * @since 0.0.1
         *
         * @return array The Widget List.
         */
        public static function get_block_attributes()
        {
        }
        /**
         * Get Block Assets.
         *
         * @since 0.0.1
         *
         * @return array The Asset List.
         */
        public static function get_block_assets()
        {
        }
        /**
         * Get Block Assets.
         *
         * @since 0.0.1
         *
         * @return array The Asset List.
         */
        public static function get_block_assets_css()
        {
        }
        /**
         * Border attribute generation Function.
         *
         * @since 0.0.1
         * @param  array $prefix   Attribute Prefix.
         * @return array
         */
        public static function generate_border_attribute($prefix)
        {
        }
    }
    /**
     * Class Advanced_Image.
     */
    class Advanced_Image
    {
        /**
         *  Initiator
         *
         * @return Advanced_Image The instance of the Advanced_Image class.
         */
        public static function get_instance()
        {
        }
        /**
         * Constructor
         */
        public function __construct()
        {
        }
        /**
         * Registers the `sureforms/image` block on server.
         *
         * @since 0.0.1
         *
         * @return void
         */
        public function register_blocks()
        {
        }
        /**
         * Render CF HTML.
         *
         * @param array<mixed> $attributes Array of block attributes.
         *
         * @since 0.0.1
         *
         * @return string|false
         */
        public function render_html($attributes)
        {
        }
        /**
         * Utility function to get link relation.
         *
         * @param string|false $rel stored in block attributes.
         *
         * @since 0.0.1
         *
         * @return string
         */
        public function get_rel($rel)
        {
        }
    }
    /**
     * Class Spec_Separator.
     */
    class Spec_Separator
    {
        /**
         *  Initiator
         */
        public static function get_instance()
        {
        }
        /**
         * Constructor
         */
        public function __construct()
        {
        }
        /**
         * Registers the `core/latest-posts` block on server.
         *
         * @since 0.0.1
         */
        public function register_blocks()
        {
        }
        /**
         * Render CF HTML.
         *
         * @param array $attributes Array of block attributes.
         *
         * @since 0.0.1
         */
        public function render_html($attributes)
        {
        }
    }
    /**
     * Class Advanced_Heading.
     */
    class Advanced_Heading
    {
        /**
         *  Initiator
         *
         * @return Advanced_Heading The instance of the Advanced_Heading class.
         */
        public static function get_instance()
        {
        }
        /**
         * Constructor
         */
        public function __construct()
        {
        }
        /**
         * Registers the `sureforms/advanced-heading` block on server.
         *
         * @since 0.0.1
         *
         * @return void
         */
        public function register_blocks()
        {
        }
        /**
         * Render CF HTML.
         *
         * @param array<mixed> $attributes Array of block attributes.
         *
         * @since 0.0.1
         *
         * @return string|false
         */
        public function render_html($attributes)
        {
        }
    }
    /**
     * Class Spec_Icon.
     */
    class Spec_Icon
    {
        /**
         *  Initiator
         */
        public static function get_instance()
        {
        }
        /**
         * Constructor
         */
        public function __construct()
        {
        }
        /**
         * Registers the `core/latest-posts` block on server.
         *
         * @since 0.0.1
         */
        public function register_blocks()
        {
        }
        /**
         * Render CF HTML.
         *
         * @param array $attributes Array of block attributes.
         *
         * @since 0.0.1
         */
        public function render_html($attributes)
        {
        }
    }
}
namespace {
    // WRCS: DEFINED_VERSION.
    // phpcs:disable Generic.Functions.OpeningFunctionBraceKernighanRitchie.ContentAfterBrace
    /**
     * Registers this version of Action Scheduler.
     */
    function action_scheduler_register_3_dot_7_dot_1()
    {
    }
    // phpcs:disable Generic.Functions.OpeningFunctionBraceKernighanRitchie.ContentAfterBrace
    /**
     * Initializes this version of Action Scheduler.
     */
    function action_scheduler_initialize_3_dot_7_dot_1()
    {
    }
    /**
     * Deprecated API functions for scheduling actions
     *
     * Functions with the wc prefix were deprecated to avoid confusion with
     * Action Scheduler being included in WooCommerce core, and it providing
     * a different set of APIs for working with the action queue.
     */
    /**
     * Schedule an action to run one time
     *
     * @param int    $timestamp When the job will run
     * @param string $hook The hook to trigger
     * @param array  $args Arguments to pass when the hook triggers
     * @param string $group The group to assign this job to
     *
     * @return string The job ID
     */
    function wc_schedule_single_action($timestamp, $hook, $args = array(), $group = '')
    {
    }
    /**
     * Schedule a recurring action
     *
     * @param int    $timestamp When the first instance of the job will run
     * @param int    $interval_in_seconds How long to wait between runs
     * @param string $hook The hook to trigger
     * @param array  $args Arguments to pass when the hook triggers
     * @param string $group The group to assign this job to
     *
     * @deprecated 2.1.0
     *
     * @return string The job ID
     */
    function wc_schedule_recurring_action($timestamp, $interval_in_seconds, $hook, $args = array(), $group = '')
    {
    }
    /**
     * Schedule an action that recurs on a cron-like schedule.
     *
     * @param int    $timestamp The schedule will start on or after this time
     * @param string $schedule A cron-link schedule string
     * @see http://en.wikipedia.org/wiki/Cron
     *   *    *    *    *    *    *
     *   ┬    ┬    ┬    ┬    ┬    ┬
     *   |    |    |    |    |    |
     *   |    |    |    |    |    + year [optional]
     *   |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
     *   |    |    |    +---------- month (1 - 12)
     *   |    |    +--------------- day of month (1 - 31)
     *   |    +-------------------- hour (0 - 23)
     *   +------------------------- min (0 - 59)
     * @param string $hook The hook to trigger
     * @param array  $args Arguments to pass when the hook triggers
     * @param string $group The group to assign this job to
     *
     * @deprecated 2.1.0
     *
     * @return string The job ID
     */
    function wc_schedule_cron_action($timestamp, $schedule, $hook, $args = array(), $group = '')
    {
    }
    /**
     * Cancel the next occurrence of a job.
     *
     * @param string $hook The hook that the job will trigger
     * @param array  $args Args that would have been passed to the job
     * @param string $group
     *
     * @deprecated 2.1.0
     */
    function wc_unschedule_action($hook, $args = array(), $group = '')
    {
    }
    /**
     * @param string $hook
     * @param array  $args
     * @param string $group
     *
     * @deprecated 2.1.0
     *
     * @return int|bool The timestamp for the next occurrence, or false if nothing was found
     */
    function wc_next_scheduled_action($hook, $args = \null, $group = '')
    {
    }
    /**
     * Find scheduled actions
     *
     * @param array  $args Possible arguments, with their default values:
     *         'hook' => '' - the name of the action that will be triggered
     *         'args' => NULL - the args array that will be passed with the action
     *         'date' => NULL - the scheduled date of the action. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
     *         'date_compare' => '<=' - operator for testing "date". accepted values are '!=', '>', '>=', '<', '<=', '='
     *         'modified' => NULL - the date the action was last updated. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
     *         'modified_compare' => '<=' - operator for testing "modified". accepted values are '!=', '>', '>=', '<', '<=', '='
     *         'group' => '' - the group the action belongs to
     *         'status' => '' - ActionScheduler_Store::STATUS_COMPLETE or ActionScheduler_Store::STATUS_PENDING
     *         'claimed' => NULL - TRUE to find claimed actions, FALSE to find unclaimed actions, a string to find a specific claim ID
     *         'per_page' => 5 - Number of results to return
     *         'offset' => 0
     *         'orderby' => 'date' - accepted values are 'hook', 'group', 'modified', or 'date'
     *         'order' => 'ASC'
     * @param string $return_format OBJECT, ARRAY_A, or ids
     *
     * @deprecated 2.1.0
     *
     * @return array
     */
    function wc_get_scheduled_actions($args = array(), $return_format = \OBJECT)
    {
    }
    /**
     * General API functions for scheduling actions
     *
     * @package ActionScheduler.
     */
    /**
     * Enqueue an action to run one time, as soon as possible
     *
     * @param string $hook The hook to trigger.
     * @param array  $args Arguments to pass when the hook triggers.
     * @param string $group The group to assign this job to.
     * @param bool   $unique Whether the action should be unique.
     * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
     *
     * @return int The action ID. Zero if there was an error scheduling the action.
     */
    function as_enqueue_async_action($hook, $args = array(), $group = '', $unique = \false, $priority = 10)
    {
    }
    /**
     * Schedule an action to run one time
     *
     * @param int    $timestamp When the job will run.
     * @param string $hook The hook to trigger.
     * @param array  $args Arguments to pass when the hook triggers.
     * @param string $group The group to assign this job to.
     * @param bool   $unique Whether the action should be unique.
     * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
     *
     * @return int The action ID. Zero if there was an error scheduling the action.
     */
    function as_schedule_single_action($timestamp, $hook, $args = array(), $group = '', $unique = \false, $priority = 10)
    {
    }
    /**
     * Schedule a recurring action
     *
     * @param int    $timestamp When the first instance of the job will run.
     * @param int    $interval_in_seconds How long to wait between runs.
     * @param string $hook The hook to trigger.
     * @param array  $args Arguments to pass when the hook triggers.
     * @param string $group The group to assign this job to.
     * @param bool   $unique Whether the action should be unique.
     * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
     *
     * @return int The action ID. Zero if there was an error scheduling the action.
     */
    function as_schedule_recurring_action($timestamp, $interval_in_seconds, $hook, $args = array(), $group = '', $unique = \false, $priority = 10)
    {
    }
    /**
     * Schedule an action that recurs on a cron-like schedule.
     *
     * @param int    $timestamp The first instance of the action will be scheduled
     *           to run at a time calculated after this timestamp matching the cron
     *           expression. This can be used to delay the first instance of the action.
     * @param string $schedule A cron-link schedule string.
     * @see http://en.wikipedia.org/wiki/Cron
     *   *    *    *    *    *    *
     *   ┬    ┬    ┬    ┬    ┬    ┬
     *   |    |    |    |    |    |
     *   |    |    |    |    |    + year [optional]
     *   |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
     *   |    |    |    +---------- month (1 - 12)
     *   |    |    +--------------- day of month (1 - 31)
     *   |    +-------------------- hour (0 - 23)
     *   +------------------------- min (0 - 59)
     * @param string $hook The hook to trigger.
     * @param array  $args Arguments to pass when the hook triggers.
     * @param string $group The group to assign this job to.
     * @param bool   $unique Whether the action should be unique.
     * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
     *
     * @return int The action ID. Zero if there was an error scheduling the action.
     */
    function as_schedule_cron_action($timestamp, $schedule, $hook, $args = array(), $group = '', $unique = \false, $priority = 10)
    {
    }
    /**
     * Cancel the next occurrence of a scheduled action.
     *
     * While only the next instance of a recurring or cron action is unscheduled by this method, that will also prevent
     * all future instances of that recurring or cron action from being run. Recurring and cron actions are scheduled in
     * a sequence instead of all being scheduled at once. Each successive occurrence of a recurring action is scheduled
     * only after the former action is run. If the next instance is never run, because it's unscheduled by this function,
     * then the following instance will never be scheduled (or exist), which is effectively the same as being unscheduled
     * by this method also.
     *
     * @param string $hook The hook that the job will trigger.
     * @param array  $args Args that would have been passed to the job.
     * @param string $group The group the job is assigned to.
     *
     * @return int|null The scheduled action ID if a scheduled action was found, or null if no matching action found.
     */
    function as_unschedule_action($hook, $args = array(), $group = '')
    {
    }
    /**
     * Cancel all occurrences of a scheduled action.
     *
     * @param string $hook The hook that the job will trigger.
     * @param array  $args Args that would have been passed to the job.
     * @param string $group The group the job is assigned to.
     */
    function as_unschedule_all_actions($hook, $args = array(), $group = '')
    {
    }
    /**
     * Check if there is an existing action in the queue with a given hook, args and group combination.
     *
     * An action in the queue could be pending, in-progress or async. If the is pending for a time in
     * future, its scheduled date will be returned as a timestamp. If it is currently being run, or an
     * async action sitting in the queue waiting to be processed, in which case boolean true will be
     * returned. Or there may be no async, in-progress or pending action for this hook, in which case,
     * boolean false will be the return value.
     *
     * @param string $hook Name of the hook to search for.
     * @param array  $args Arguments of the action to be searched.
     * @param string $group Group of the action to be searched.
     *
     * @return int|bool The timestamp for the next occurrence of a pending scheduled action, true for an async or in-progress action or false if there is no matching action.
     */
    function as_next_scheduled_action($hook, $args = \null, $group = '')
    {
    }
    /**
     * Check if there is a scheduled action in the queue but more efficiently than as_next_scheduled_action().
     *
     * It's recommended to use this function when you need to know whether a specific action is currently scheduled
     * (pending or in-progress).
     *
     * @since 3.3.0
     *
     * @param string $hook  The hook of the action.
     * @param array  $args  Args that have been passed to the action. Null will matches any args.
     * @param string $group The group the job is assigned to.
     *
     * @return bool True if a matching action is pending or in-progress, false otherwise.
     */
    function as_has_scheduled_action($hook, $args = \null, $group = '')
    {
    }
    /**
     * Find scheduled actions
     *
     * @param array  $args Possible arguments, with their default values.
     *         'hook' => '' - the name of the action that will be triggered.
     *         'args' => NULL - the args array that will be passed with the action.
     *         'date' => NULL - the scheduled date of the action. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
     *         'date_compare' => '<=' - operator for testing "date". accepted values are '!=', '>', '>=', '<', '<=', '='.
     *         'modified' => NULL - the date the action was last updated. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
     *         'modified_compare' => '<=' - operator for testing "modified". accepted values are '!=', '>', '>=', '<', '<=', '='.
     *         'group' => '' - the group the action belongs to.
     *         'status' => '' - ActionScheduler_Store::STATUS_COMPLETE or ActionScheduler_Store::STATUS_PENDING.
     *         'claimed' => NULL - TRUE to find claimed actions, FALSE to find unclaimed actions, a string to find a specific claim ID.
     *         'per_page' => 5 - Number of results to return.
     *         'offset' => 0.
     *         'orderby' => 'date' - accepted values are 'hook', 'group', 'modified', 'date' or 'none'.
     *         'order' => 'ASC'.
     *
     * @param string $return_format OBJECT, ARRAY_A, or ids.
     *
     * @return array
     */
    function as_get_scheduled_actions($args = array(), $return_format = \OBJECT)
    {
    }
    /**
     * Helper function to create an instance of DateTime based on a given
     * string and timezone. By default, will return the current date/time
     * in the UTC timezone.
     *
     * Needed because new DateTime() called without an explicit timezone
     * will create a date/time in PHP's timezone, but we need to have
     * assurance that a date/time uses the right timezone (which we almost
     * always want to be UTC), which means we need to always include the
     * timezone when instantiating datetimes rather than leaving it up to
     * the PHP default.
     *
     * @param mixed  $date_string A date/time string. Valid formats are explained in http://php.net/manual/en/datetime.formats.php.
     * @param string $timezone A timezone identifier, like UTC or Europe/Lisbon. The list of valid identifiers is available http://php.net/manual/en/timezones.php.
     *
     * @return ActionScheduler_DateTime
     */
    function as_get_datetime_object($date_string = \null, $timezone = 'UTC')
    {
    }
    /**
     * Set constants
     */
    \define('SRFM_FILE', __FILE__);
    \define('SRFM_BASENAME', \plugin_basename(\SRFM_FILE));
    \define('SRFM_DIR', \plugin_dir_path(\SRFM_FILE));
    \define('SRFM_URL', \plugins_url('/', \SRFM_FILE));
    \define('SRFM_VER', '');
    \define('SRFM_SLUG', 'srfm');
    // ------ ADDITIONAL CONSTANTS ------- //
    \define('SRFM_FORMS_POST_TYPE', 'sureforms_form');
    \define('SRFM_ENTRIES', 'sureforms_entries');
    \define('SRFM_PRO_RECOMMENDED_VER', '');
    /**
     * Filesystem class
     *
     * @since 0.0.1
     */
    function spec_filesystem()
    {
    }
}
