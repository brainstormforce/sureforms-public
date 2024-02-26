<?php

namespace SRFM\Inc\Blocks {
    /**
     * Block base class.
     */
    abstract class SRFM_Base
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
         * Run any block middleware before rendering.
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $content   Post content.
         * @return boolean|\WP_Error;
         * @since 0.0.1
         */
        protected function middleware($attributes, $content)
        {
        }
        /**
         * Allows filtering of attributes before rendering.
         *
         * @param array<mixed> $attributes Block attributes.
         * @return array<mixed> $attributes
         * @since 0.0.1
         */
        public function get_attributes($attributes)
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
namespace SRFM\Inc\Blocks\Dropdown {
    /**
     * Dropdown Block.
     */
    class SRFM_Block extends \SRFM\Inc\Blocks\SRFM_Base
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
namespace SRFM\Inc\Blocks\Input {
    /**
     * Address Block.
     */
    class SRFM_Block extends \SRFM\Inc\Blocks\SRFM_Base
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
    class SRFM_Block extends \SRFM\Inc\Blocks\SRFM_Base
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
    class SRFM_Block extends \SRFM\Inc\Blocks\SRFM_Base
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
    class SRFM_Block extends \SRFM\Inc\Blocks\SRFM_Base
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
namespace SRFM\Inc\Blocks\Number {
    /**
     * Address Block.
     */
    class SRFM_Block extends \SRFM\Inc\Blocks\SRFM_Base
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
    class SRFM_Block extends \SRFM\Inc\Blocks\SRFM_Base
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
namespace SRFM\Inc\Traits {
    /**
     * Trait Get_Instance.
     */
    trait SRFM_Get_Instance
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
namespace SRFM\Inc\Blocks {
    /**
     * Manage Blocks registrations.
     *
     * @since 0.0.1
     */
    class SRFM_Register
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
    class SRFM_Block extends \SRFM\Inc\Blocks\SRFM_Base
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
    class SRFM_Block extends \SRFM\Inc\Blocks\SRFM_Base
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
    class SRFM_Block extends \SRFM\Inc\Blocks\SRFM_Base
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
    class SRFM_Block extends \SRFM\Inc\Blocks\SRFM_Base
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
     * Sureforms Submit Class.
     *
     * @since 0.0.1
     */
    class SRFM_Submit
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
         * Handle Settings Form Submission
         *
         * @param \WP_REST_Request $request Request object or array containing form data.
         * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
         * @since 0.0.1
         */
        public function handle_settings_form_submission($request)
        {
        }
        /**
         * Get Settings Form Data
         *
         * @param \WP_REST_Request $request Request object or array containing form data.
         *
         * @return void
         * @since 0.0.1
         */
        public function get_settings_form_data($request)
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
    class SRFM_Post_Types
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
         * @param array<Mixed> $actions An array of row action links.
         * @param \WP_Post     $post  The current WP_Post object.
         *
         * @return array<Mixed> $actions Modified row action links.
         * @since  0.0.1
         */
        public function modify_entries_list_row_actions($actions, $post)
        {
        }
        /**
         * Modify list bulk actions.
         *
         * @param array<Mixed> $bulk_actions An array of bulk action links.
         * @since 0.0.1
         * @return array<Mixed> $bulk_actions Modified action links.
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
         * Sureforms entries meta box callback.
         *
         * @param \WP_Post $post Template.
         * @return void
         * @since 0.0.1
         */
        public function sureforms_meta_box_callback(\WP_Post $post)
        {
        }
        /**
         * Add Sureforms entries meta box.
         *
         * @return void
         * @since 0.0.1
         */
        public function entries_meta_box()
        {
        }
        /**
         * Sureforms box Form Name meta box callback.
         *
         * @param \WP_Post $post Template.
         * @return void
         * @since 0.0.1
         */
        public function sureforms_form_name_meta_callback(\WP_Post $post)
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
         * Add custom column header.
         *
         * @param array<mixed> $columns Attributes.
         * @return array<mixed> $columns Post Content.
         * @since 0.0.1
         */
        public function custom_entry_columns($columns)
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
        public function custom_entry_column_data($column, $post_id)
        {
        }
        /**
         * Add SureForms taxonomy filter.
         *
         * @return void
         * @since 0.0.1
         */
        public function add_tax_filter()
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
    }
    /**
     * Public Class
     *
     * @since 0.0.1
     */
    class SRFM_Admin_Ajax
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
         * @since 1.0.0
         */
        public function required_plugin_activate()
        {
        }
        /**
         * Get ajax error message.
         *
         * @param string $type Message type.
         * @return string
         * @since 1.0.0
         */
        public function get_error_msg($type)
        {
        }
        /**
         * Localize the variables required for integration plugins.
         *
         * @return void
         * @since 1.0.0
         */
        public function localize_script_integration()
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
         * @since 1.0.0
         *
         * @param  string $plugin_init_file Plguin init file.
         * @return string
         */
        public static function get_plugin_status($plugin_init_file)
        {
        }
    }
    /**
     * Sureforms Helper Class.
     *
     * @since 0.0.1
     */
    class SRFM_Helper
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
         * This function performs array_map for multi dimensional array
         *
         * @param string       $function function name to be applied on each element on array.
         * @param array<mixed> $data_array array on which function needs to be performed.
         * @return array<mixed>
         * @since 1.0.0
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
         * @since 1.0.0
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
         * @since 1.0.0
         * @return string Meta value.
         */
        public static function get_meta_value($post_id, $key, $single = true, $default = '')
        {
        }
    }
    /**
     * Gutenberg hooks handler class.
     *
     * @since 0.0.1
     */
    class SRFM_Gutenberg_Hooks
    {
        /**
         * Block patterns to register.
         *
         * @var array<mixed>
         */
        protected $patterns = [];
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
    }
    /**
     * Sureforms Smart Tags Class.
     *
     * @since 0.0.1
     */
    class SRFM_Smart_Tags
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
}
namespace SRFM\Inc\Fields {
    /**
     * Field Base Class
     *
     * Defines the base class for form fields.
     *
     * @since 0.0.1
     */
    class SRFM_Base
    {
        /**
         * Render the sureforms default
         *
         * @param array<mixed> $attributes Block attributes.
         * @param string       $form_id form id.
         *
         * @return string|boolean
         */
        public function markup($attributes, $form_id)
        {
        }
    }
    /**
     * SureForms Multichoice Markup Class.
     *
     * @since 0.0.1
     */
    class SRFM_Multichoice_Markup extends \SRFM\Inc\Fields\SRFM_Base
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
        /**
         * Render the sureforms Multichoice classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         * @param int|string   $form_id form id.
         *
         * @return string|boolean
         */
        public function markup($attributes, $form_id)
        {
        }
    }
    /**
     * Sureforms Url Field Markup Class.
     *
     * @since 0.0.1
     */
    class SRFM_Url_Markup extends \SRFM\Inc\Fields\SRFM_Base
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
        /**
         * Render the sureforms url classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         * @param int|string   $form_id form id.
         *
         * @return string|boolean
         */
        public function markup($attributes, $form_id)
        {
        }
    }
    /**
     * Sureforms Address Markup Class.
     *
     * @since 0.0.1
     */
    class SRFM_Address_Markup extends \SRFM\Inc\Fields\SRFM_Base
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
        /**
         * Return Phone codes
         *
         * @return mixed|array<mixed|string> $data with phone codes
         */
        public function get_countries()
        {
        }
        /**
         * Render the sureforms address classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         * @param int|string   $form_id form id.
         *
         * @return string|boolean
         */
        public function markup($attributes, $form_id)
        {
        }
    }
    /**
     * Sureforms_Phone_Markup Class.
     *
     * @since 0.0.1
     */
    class SRFM_Phone_Markup extends \SRFM\Inc\Fields\SRFM_Base
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
        /**
         * Render the sureforms phone classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         * @param int|string   $form_id form id.
         *
         * @return string|boolean
         */
        public function markup($attributes, $form_id)
        {
        }
    }
    /**
     * Sureforms Dropdown Markup Class.
     *
     * @since 0.0.1
     */
    class SRFM_Dropdown_Markup extends \SRFM\Inc\Fields\SRFM_Base
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
        /**
         * Render the sureforms dropdown classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         * @param int|string   $form_id form id.
         *
         * @return string|boolean
         */
        public function markup($attributes, $form_id)
        {
        }
    }
    /**
     * SureForms Email Markup Class.
     *
     * @since 0.0.1
     */
    class SRFM_Email_Markup extends \SRFM\Inc\Fields\SRFM_Base
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
        /**
         * Render the sureforms email classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         * @param int|string   $form_id form id.
         * @return string|boolean
         */
        public function markup($attributes, $form_id)
        {
        }
    }
    /**
     * Sureforms Checkbox Markup Class.
     *
     * @since 0.0.1
     */
    class SRFM_Checkbox_Markup extends \SRFM\Inc\Fields\SRFM_Base
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
        /**
         * Render the sureforms checkbox classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         * @param int|string   $form_id form id.
         *
         * @return string|boolean
         */
        public function markup($attributes, $form_id)
        {
        }
    }
    /**
     * Sureforms Number Field Markup Class.
     *
     * @since 0.0.1
     */
    class SRFM_Number_Markup extends \SRFM\Inc\Fields\SRFM_Base
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
        /**
         * Render the sureforms number classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         * @param int|string   $form_id form id.
         *
         * @return string|boolean
         */
        public function markup($attributes, $form_id)
        {
        }
    }
    /**
     * Sureforms Textarea Markup Class.
     *
     * @since 0.0.1
     */
    class SRFM_Textarea_Markup extends \SRFM\Inc\Fields\SRFM_Base
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
        /**
         * Render the sureforms textarea classic styling
         *
         * @param array<mixed> $attributes Block attributes.
         * @param int|string   $form_id form id.
         *
         * @return string|boolean
         */
        public function markup($attributes, $form_id)
        {
        }
    }
    /**
     * Sureforms Input Markup Class.
     *
     * @since 0.0.1
     */
    class SRFM_Input_Markup extends \SRFM\Inc\Fields\SRFM_Base
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
        /**
         * Render input markup
         *
         * @param array<mixed> $attributes Block attributes.
         * @param int|string   $form_id form id.
         *
         * @return string|boolean
         */
        public function markup($attributes, $form_id)
        {
        }
    }
}
namespace SRFM\Inc {
    /**
     * Activation Class.
     *
     * @since 0.0.1
     */
    class SRFM_Activator
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
    class SRFM_Forms_Data
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
     * Load Defaults Class.
     *
     * @since 0.0.1
     */
    class SRFM_Export
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
    /**
     * Create New Form.
     *
     * @since 0.0.1
     */
    class SRFM_Create_New_Form
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
         * Create new form from selected templates
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
    class SRFM_Generate_Form_Markup
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
         * @param boolean    $hide_title_current_page Boolean to show/hide form title.
         * @param string     $sf_classname additional class_name.
         * @param string     $post_type Contains post type.
         *
         * @return string|false
         * @since 0.0.1
         */
        public static function get_form_markup($id, $hide_title_current_page = false, $sf_classname = '', $post_type = 'post')
        {
        }
    }
    /**
     * Public Class
     *
     * @since 0.0.1
     */
    class SRFM_Public
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
}
namespace SRFM\Inc\Email {
    /**
     * Email Class
     *
     * @since 0.0.1
     */
    class SRFM_Email_Template
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
         * @return string
         */
        public function get_header()
        {
        }
        /**
         * Get email footer.
         *
         * @since 0.0.1
         * @return string footer tags.
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
namespace SRFM\Inc\Services\Browser {
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
namespace SRFM {
    /**
     * Plugin_Loader
     *
     * @since 0.0.1
     */
    class SRFM_Plugin_Loader
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
         * @since 1.0.0
         *
         * @return void
         */
        public function load_plugin()
        {
        }
        /**
         * Load Core Files.
         *
         * @since 1.0.0
         *
         * @return void
         */
        public function load_core_files()
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
    class SRFM_Admin
    {
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
        use \SRFM\Inc\Traits\SRFM_Get_Instance;
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
     * Class SRFM_Advanced_Heading.
     */
    class SRFM_Advanced_Heading
    {
        /**
         *  Initiator
         *
         * @return SRFM_Advanced_Heading The instance of the SRFM_Advanced_Heading class.
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
     * Class SRFM_Spec_Separator.
     */
    class SRFM_Spec_Separator
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
     * Class SRFM_Advanced_Image.
     */
    class SRFM_Advanced_Image
    {
        /**
         *  Initiator
         *
         * @return SRFM_Advanced_Image The instance of the SRFM_Advanced_Image class.
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
     * Class SRFM_Spec_Icon.
     */
    class SRFM_Spec_Icon
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
     * Class SRFM_Spec_Block_Config.
     */
    class SRFM_Spec_Block_Config
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
     * Class SRFM_Spec_Gb_Helper.
     */
    final class SRFM_Spec_Gb_Helper
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
         */
        public static function render_svg_html($icon)
        {
        }
    }
    /**
     * Class SRFM_Spec_Block_Loader.
     */
    final class SRFM_Spec_Block_Loader
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
     * Class Sureforms_Spec_Block_JS.
     */
    class SRFM_Spec_Block_JS
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
     * SRFM_Spec_Init_Blocks.
     *
     * @package Sureforms
     */
    class SRFM_Spec_Init_Blocks
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
     * Class SRFM_Spec_Filesystem.
     */
    class SRFM_Spec_Filesystem
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
     * Class for Spectra compatibility
     */
    class SRFM_Spec_Spectra_Compatibility
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
     * Class SRFM_Spec_Block_Helper.
     */
    class SRFM_Spec_Block_Helper
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
}
namespace {
    /**
     * Set constants
     */
    \define('SRFM_FILE', __FILE__);
    \define('SRFM_BASENAME', \plugin_basename(\SRFM_FILE));
    \define('SRFM_DIR', \plugin_dir_path(\SRFM_FILE));
    \define('SRFM_URL', \plugins_url('/', \SRFM_FILE));
    \define('SRFM_VER', '0.0.1');
    \define('SRFM_SLUG', 'srfm');
    \define('SRFM_LOC', 'SureForms');
    // ------ ADDITIONAL CONSTANTS ------- //
    \define('SRFM_FORMS_POST_TYPE', 'sureforms_form');
    \define('SRFM_ENTRIES_POST_TYPE', 'sureforms_entry');
    /**
     * Filesystem class
     *
     * @since 0.0.1
     */
    function srfm_filesystem()
    {
    }
}