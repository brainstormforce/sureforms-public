<?php

namespace Bricks {

    // Exit if accessed directly
    abstract class Element
    {
        /**
         * Gutenberg block name: 'core/heading', etc.
         *
         * Mapping of Gutenberg block to Bricks element to load block post_content in Bricks and save Bricks data as WordPress post_content.
         */
        public $block = null;
        // Builder
        public $element;
        public $category;
        public $name;
        public $label;
        public $keywords;
        public $icon;
        public $controls;
        public $control_groups;
        public $control_options;
        public $css_selector;
        public $scripts = [];
        public $post_id = 0;
        public $draggable = true;
        // false to prevent dragging over entire element in builder
        public $deprecated = false;
        // true to hide element in panel (editing of existing deprecated element still works)
        public $panel_condition = [];
        // array conditions to show the element in the panel
        // Frontend
        public $id;
        public $tag = 'div';
        public $attributes = [];
        public $settings;
        public $theme_styles = [];
        public $is_frontend = false;
        /**
         * Custom attributes
         *
         * true: renders custom attributes on element '_root' (= default)
         * false: handle custom attributes in element render_attributes( 'xxx', true ) function (e.g. Nav Menu)
         *
         * @since 1.3
         */
        public $custom_attributes = true;
        /**
         * Nestable elements
         *
         * @since 1.5
         */
        public $nestable = false;
        // true to allow to insert child elements (e.g. Container, Div)
        public $nestable_item;
        // First child of nestable element (Use as blueprint for nestable children & when adding repeater item)
        public $nestable_children;
        // Array of children elements that are added inside nestable element when it's added to the canvas.
        public $nestable_hide = false;
        // Boolean to hide nestable in Structure & prevent dragging (true if no full access)
        public $nestable_html = '';
        // Nestable HTML with placeholder for element 'children'
        public $vue_component;
        // Set specific Vue component to render element in builder (e.g. 'bricks-nestable' for Section, Container, Div)
        public $original_query = '';
        public function __construct($element = null)
        {
        }
        /**
         * Populate element data (when element is requested)
         *
         * Builder: Load all elements
         * Frontend: Load only requested elements
         *
         * @since 1.0
         */
        public function load()
        {
        }
        /**
         * Add element-specific WordPress actions to run in constructor
         *
         * @since 1.0
         */
        public function add_actions()
        {
        }
        /**
         * Add element-specific WordPress filters to run in constructor
         *
         * E.g. 'nav_menu_item_title' filter in Element_Nav_Menu
         *
         * @since 1.0
         */
        public function add_filters()
        {
        }
        /**
         * Set default CSS selector of each control with 'css' property
         *
         * To target specific element child tag (such as 'a' in 'button' etc.)
         * Avoids having to set CSS selector manually for each element control.
         *
         * @since 1.0
         */
        public function set_css_selector($custom_css_selector)
        {
        }
        public function get_label()
        {
        }
        public function get_keywords()
        {
        }
        /**
         * Return element tag
         *
         * Default: 'div'
         * Next:    $tag set in theme styles
         * Last:    $tag set in element settings
         *
         * Custom tag: Check element 'tag' and 'customTag' settings.
         *
         * @since 1.4
         */
        public function get_tag()
        {
        }
        /**
         * Element-specific control groups
         *
         * @since 1.0
         */
        public function set_control_groups()
        {
        }
        /**
         * Element-specific controls
         *
         * @since 1.0
         */
        public function set_controls()
        {
        }
        /**
         * Control groups used by all elements under 'style' tab
         *
         * @since 1.0
         */
        public function set_common_control_groups()
        {
        }
        /**
         * Controls used by all elements under 'style' tab
         *
         * @since 1.0
         */
        public function set_controls_before()
        {
        }
        /**
         * Controls used by all elements under 'style' tab
         *
         * @since 1.0
         */
        public function set_controls_after()
        {
        }
        /**
         * Get default data
         *
         * @since 1.0
         */
        public function get_default_data()
        {
        }
        /**
         * Builder: Element placeholder HTML
         *
         * @since 1.0
         */
        public final function render_element_placeholder($data = [], $type = 'info')
        {
        }
        /**
         * Return element attribute: id
         *
         * @since 1.5
         *
         * @since 1.7.1: Parse dynamic data for _cssId (same for _cssClasses)
         */
        public function get_element_attribute_id()
        {
        }
        /**
         * Set element root attributes (element ID, classes, etc.)
         *
         * @since 1.4
         */
        public function set_root_attributes()
        {
        }
        /**
         * Return true if element has 'css' settings
         *
         * @return boolean
         *
         * @since 1.5
         */
        public function has_css_settings($settings)
        {
        }
        /**
         * Convert the global classes ids into the classes names
         *
         * @param array $class_ids The global classes ids.
         *
         * @return array
         */
        public static function get_element_global_classes($class_ids)
        {
        }
        /**
         * Set HTML element attribute + value(s)
         *
         * @param string       $key         Element identifier.
         * @param string       $attribute   Attribute to set value(s) for.
         * @param string|array $value       Set single value (string) or values (array).
         *
         * @since 1.0
         */
        public function set_attribute($key, $attribute, $value = null)
        {
        }
        /**
         * Set link attributes
         *
         * Helper to set attributes for control type 'link'
         *
         * @since 1.0
         *
         * @param string $attribute_key Desired key for set_attribute.
         * @param string $link_settings Element control type 'link' settings.
         */
        public function set_link_attributes($attribute_key, $link_settings)
        {
        }
        /**
         * Maybe set aria-current="page" attribute to the link if it points to the current page.
         *
         * Example: nav-nested active nav item background color.
         *
         * NOTE: url_to_postid() returns 0 if URL contains the port like https://bricks.local:49581/blog/
         *
         * @since 1.8
         */
        public function maybe_set_aria_current($link_settings, $attribute_key)
        {
        }
        /**
         * Remove attribute
         *
         * @param string      $key        Element identifier.
         * @param string      $attribute  Attribute to remove.
         * @param string|null $value Set to remove single value instead of entire attribute.
         *
         * @since 1.0
         */
        public function remove_attribute($key, $attribute, $value = null)
        {
        }
        /**
         * Render HTML attributes for specific element
         *
         * @param string  $key                   Attribute identifier.
         * @param boolean $add_custom_attributes true to get custom atts for elements where we don't add them to the wrapper (Nav Menu).
         *
         * @since 1.0
         */
        public function render_attributes($key, $add_custom_attributes = false)
        {
        }
        /**
         * Calculate element custom attributes based on settings (dynamic data too)
         *
         * @since 1.3
         */
        public function get_custom_attributes($settings = [])
        {
        }
        public static function stringify_attributes($attributes = [])
        {
        }
        /**
         * Enqueue element-specific styles and scripts
         *
         * @since 1.0
         */
        public function enqueue_scripts()
        {
        }
        /**
         * Element HTML render
         *
         * @since 1.0
         */
        public function render()
        {
        }
        /**
         * Element HTML render in builder via x-template
         *
         * @since 1.0
         */
        public static function render_builder()
        {
        }
        /**
         * Builder: Get nestable item
         *
         * Use as blueprint for nestable children & when adding repeater item.
         *
         * @since 1.5
         */
        public function get_nestable_item()
        {
        }
        /**
         * Builder: Array of child elements added when inserting new nestable element
         *
         * @since 1.5
         */
        public function get_nestable_children()
        {
        }
        /**
         * Frontend: Lazy load (images, videos)
         *
         * Global settings 'disableLazyLoad': Disable lazy load altogether
         * Page settings 'disableLazyLoad': Disable lazy load on this page (@since 1.8.6)
         * Element settings 'disableLazyLoad': Carousel, slider, testimonials (= bricksSwiper) (@since 1.4)
         *
         * @since 1.0
         */
        public function lazy_load()
        {
        }
        /**
         * Enqueue element scripts & styles, set attributes, render
         *
         * @since 1.0
         */
        public function init()
        {
        }
        /**
         * Calculate column width
         */
        public function calc_column_width($columns_count = 1, $max = false)
        {
        }
        /**
         * Column width calculator
         *
         * @param int $columns Number of columns.
         * @param int $count   Total amount of items.
         */
        public function column_width($columns, $count)
        {
        }
        /**
         * Post fields
         *
         * Shared between elements: Carousel, Posts, Products, etc.
         *
         * @since 1.0
         */
        public function get_post_fields()
        {
        }
        /**
         * Post content
         *
         * Shared between elements: Carousel, Posts
         *
         * @since 1.0
         */
        public function get_post_content()
        {
        }
        /**
         * Post overlay
         *
         * Shared between elements: Carousel, Posts
         *
         * @since 1.0
         */
        public function get_post_overlay()
        {
        }
        /**
         * Get swiper controls
         *
         * Elements: Carousel, Slider, Team Members.
         *
         * @since 1.0
         */
        public static function get_swiper_controls()
        {
        }
        /**
         * Render swiper nav: Navigation (arrows) & pagination (dots)
         *
         * Elements: Carousel, Slider, Team Members.
         *
         * @param array $options SwiperJS options.
         *
         * @since 1.4
         */
        public function render_swiper_nav($options = false)
        {
        }
        /**
         * Custom loop builder controls
         *
         * Shared between Container, Template, ...
         *
         * @since 1.3.7
         */
        public function get_loop_builder_controls($group = '')
        {
        }
        /**
         * Render the query loop trail
         *
         * Trail enables infinite scroll
         *
         * @since 1.5
         *
         * @param Query  $query    The query object.
         * @param string $node_key The element key to add the query data attributes (used in the posts element).
         *
         * @return string
         */
        public function render_query_loop_trail($query, $node_key = '')
        {
        }
        /**
         * Get the dynamic data for a specific tag
         *
         * @param string $tag Dynamic data tag.
         * @param string $context text, image, media, link.
         * @param array  $args Needed to set size for avatar image.
         * @param string $post_id Post ID.
         *
         * @return mixed
         */
        public function render_dynamic_data_tag($tag = '', $context = 'text', $args = [], $post_id = 0)
        {
        }
        /**
         * Render dynamic data tags on a string
         *
         * @param string $content
         *
         * @return mixed
         */
        public function render_dynamic_data($content = '')
        {
        }
        /**
         * Set Post ID
         *
         * @param int $post_id
         *
         * @return void
         */
        public function set_post_id($post_id = 0)
        {
        }
        /**
         * Setup query for templates according to 'templatePreviewType'
         *
         * To alter builder template and template preview query. NOT the frontend!
         *
         * 1. Set element $post_id
         * 2. Populate query_args from"Populate content" settings and set it to global $wp_query
         *
         * @param integer $post_id
         *
         * @since 1.0
         */
        public function setup_query($post_id = 0)
        {
        }
        /**
         * Restore custom query after element render()
         *
         * @since 1.0
         */
        public function restore_query()
        {
        }
        /**
         * Render control 'icon' HTML (either font icon 'i' or 'svg' HTML)
         *
         * @param array $icon Contains either 'icon' CSS class or 'svg' URL data.
         * @param array $attributes Additional icon HTML attributes.
         *
         * @see ControlIcon.vue
         * @return string SVG HMTL string
         *
         * @since 1.2.1
         */
        public static function render_icon($icon, $attributes = [])
        {
        }
        /**
         * Add attributes to SVG HTML string
         *
         * @since 1.4
         */
        public static function render_svg($svg = '', $attributes = [])
        {
        }
        /**
         * Change query if we are previewing a CPT archive template (set in-builder via "Populated Content")
         *
         * @since 1.4
         */
        public function maybe_set_preview_query($query_vars, $settings, $element_id)
        {
        }
        /**
         * Is layout element: Section, Container, Block, Div
         *
         * For element control visibility in builder (flex controls, shape divider, etc.)
         *
         * @return boolean
         *
         * @since 1.5
         */
        public function is_layout_element()
        {
        }
        /**
         * Generate breakpoint-specific @media rules for nav menu & mobile menu toggle
         *
         * If not set to 'always' or 'never'
         *
         * @since 1.5.1
         */
        public function generate_mobile_menu_inline_css($settings = [], $breakpoint = '')
        {
        }
        /**
         * Return true if any of the element classes contains a match
         *
         * @param array $values_to_check Array of values to check the global class settings for.
         *
         * @see image.php 'popupOverlay', video.php 'overlay', etc.
         *
         * @since 1.7.1
         */
        public function element_classes_have($values_to_check = [])
        {
        }
    }
}

namespace {
    /**
     * Builder check
     *
     * @since 1.0
     */
    function bricks_is_builder() {
    }

}
