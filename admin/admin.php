<?php
/**
 * Admin Class.
 *
 * @package sureforms.
 */

namespace SRFM\Admin;

use Astra_Notices;
use SRFM\Inc\AI_Form_Builder\AI_Helper;
use SRFM\Inc\Client_Logger;
use SRFM\Inc\Database\Register;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Generate_Form_Markup;
use SRFM\Inc\Global_Settings\Global_Settings;
use SRFM\Inc\Helper;
use SRFM\Inc\Onboarding;
use SRFM\Inc\Payments\Payment_Helper;
use SRFM\Inc\Payments\Stripe\Stripe_Helper;
use SRFM\Inc\Smart_Tags;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'BSF_Admin_Notices' ) ) {
	require_once SRFM_DIR . 'inc/lib/astra-notices/class-bsf-admin-notices.php';
}
/**
 * Admin handler class.
 *
 * @since 0.0.1
 */
class Admin {
	use Get_Instance;

	/**
	 * Minimum number of forms or entries required to show the rating notice.
	 *
	 * @since 2.5.2
	 */
	public const RATING_NOTICE_THRESHOLD = 3;

	/**
	 * Post meta the Starter Templates (Astra Sites) plugin stamps on every post it
	 * imports. The "Finish setting up" Thank You prompt (#3030) scopes to these
	 * forms only. Owned by a plugin that is NOT a SureForms dependency: on installs
	 * without Starter Templates nothing carries this meta and the prompt never shows.
	 *
	 * @since 2.12.4
	 */
	public const ASTRA_SITES_IMPORT_META = '_astra_sites_imported_post';

	/**
	 * Negative-cache transient: no form on this site carries the import marker.
	 *
	 * Set only when the marker query itself returns zero posts, which is a
	 * site-wide fact rather than a per-user one, and cleared as soon as any post is
	 * stamped with the marker (see invalidate_starter_template_cache()). This keeps
	 * the query off the majority of installs without tying the features to whether
	 * Starter Templates happens to still be active — the marker outlives it.
	 *
	 * @since 2.12.4
	 */
	public const NO_IMPORTED_FORMS_TRANSIENT = 'srfm_no_starter_template_forms';

	/**
	 * Inline CSS for Quill 1.x (react-quill) list markers.
	 *
	 * Quill 1.x renders bullet/numbered list markers via CSS ::before pseudo-elements,
	 * whereas the vendor quill.snow.css targets .ql-ui child elements (Quill 2.x approach).
	 * This constant is shared by enqueue_styles() and enqueue_scripts() to prevent drift.
	 *
	 * @since 2.5.2
	 */
	public const QUILL_1X_INLINE_CSS = '.ql-editor ul,.ql-editor ol{padding-left:1.5em}.ql-editor ul>li,.ql-editor ol>li{list-style-type:none}.ql-editor ol li:not(.ql-direction-rtl),.ql-editor ul li:not(.ql-direction-rtl){padding-left:1.5em}.ql-editor ol li.ql-direction-rtl,.ql-editor ul li.ql-direction-rtl{padding-right:1.5em}.ql-editor ul>li::before{content:"\2022"}.ql-editor li::before{display:inline-block;white-space:nowrap;width:1.2em}.ql-editor li:not(.ql-direction-rtl)::before{margin-left:-1.5em;margin-right:.3em;text-align:right}.ql-editor li.ql-direction-rtl::before{margin-left:.3em;margin-right:-1.5em}.ql-editor ol li{counter-reset:list-1 list-2 list-3 list-4 list-5 list-6 list-7 list-8 list-9;counter-increment:list-0}.ql-editor ol li::before{content:counter(list-0,decimal) ". "}.ql-editor ol li.ql-indent-1{counter-increment:list-1;counter-reset:list-2 list-3 list-4 list-5 list-6 list-7 list-8 list-9}.ql-editor ol li.ql-indent-1::before{content:counter(list-1,lower-alpha) ". "}.ql-editor ol li.ql-indent-2{counter-increment:list-2;counter-reset:list-3 list-4 list-5 list-6 list-7 list-8 list-9}.ql-editor ol li.ql-indent-2::before{content:counter(list-2,lower-roman) ". "}.ql-editor ol li.ql-indent-3{counter-increment:list-3;counter-reset:list-4 list-5 list-6 list-7 list-8 list-9}.ql-editor ol li.ql-indent-3::before{content:counter(list-3,decimal) ". "}.ql-editor ol li.ql-indent-4{counter-increment:list-4;counter-reset:list-5 list-6 list-7 list-8 list-9}.ql-editor ol li.ql-indent-4::before{content:counter(list-4,lower-alpha) ". "}.ql-editor ol li.ql-indent-5{counter-increment:list-5;counter-reset:list-6 list-7 list-8 list-9}.ql-editor ol li.ql-indent-5::before{content:counter(list-5,lower-roman) ". "}.ql-editor ol li.ql-indent-6{counter-increment:list-6;counter-reset:list-7 list-8 list-9}.ql-editor ol li.ql-indent-6::before{content:counter(list-6,decimal) ". "}.ql-editor ol li.ql-indent-7{counter-increment:list-7;counter-reset:list-8 list-9}.ql-editor ol li.ql-indent-7::before{content:counter(list-7,lower-alpha) ". "}.ql-editor ol li.ql-indent-8{counter-increment:list-8;counter-reset:list-9}.ql-editor ol li.ql-indent-8::before{content:counter(list-8,lower-roman) ". "}.ql-editor ol li.ql-indent-9{counter-increment:list-9}.ql-editor ol li.ql-indent-9::before{content:counter(list-9,decimal) ". "}';

	/**
	 * Notice id for the "Finish setting up" Thank You prompt (#3030).
	 *
	 * A single stable id (not per-form): keeps both the autoloaded
	 * `allowed_astra_notices` option and the per-user dismissal meta bounded to one
	 * row, and lets a dismissed user short-circuit before the query runs.
	 *
	 * @since 2.12.6
	 */
	public const THANKYOU_PROMPT_NOTICE_ID = 'srfm-thankyou-prompt';

	/**
	 * Where the Contact Support button writes to.
	 *
	 * An inbox rather than a form, restoring the 2.12.6 behaviour. A mailto: opens
	 * the composer the person already has open with the subject and the whole
	 * report in the body, so reporting a fault is one click and a send. The
	 * troubleshooting form could carry neither, which is why 2.12.7 had to gate the
	 * button behind copying the diagnostics by hand first.
	 *
	 * @since 2.12.8
	 */
	private const SUPPORT_EMAIL = 'support@sureforms.com';

	/**
	 * Longest Contact Support mailto: URL we hand to a mail client.
	 *
	 * Below the roughly 2000-character limit the strictest common clients and
	 * browsers apply to a link, with room to spare.
	 *
	 * @since 2.12.8
	 */
	private const SUPPORT_MAILTO_MAX_LENGTH = 1800;

	/**
	 * Dashboard widget entries data.
	 *
	 * @var array
	 * @since 1.9.1
	 */
	private $dashboard_widget_data = [];

	/**
	 * Cached result for whether the rating notice should display.
	 *
	 * @var bool|null
	 * @since 2.5.2
	 */
	private $should_show_rating = null;

	/**
	 * SureForms Page Default permission.
	 *
	 * @var string
	 * @since 1.12.2
	 */
	private static $sureforms_page_default_capability = 'manage_options';

	/**
	 * Request memo for the "Finish setting up" Thank You prompt (#3030).
	 *
	 * A static property (not a function-local static) so tests can reset it via
	 * reflection / reset_thankyou_prompt_cache() — otherwise the first call pins
	 * the value for the whole process and the feature is untestable.
	 *
	 * @var array<int,array<string,mixed>>|null
	 * @since 2.12.4
	 */
	private static $thankyou_prompt_cache = null;

	/**
	 * Request memo for the dashboard setup-checklist card (#3031).
	 *
	 * A static property (not a function-local static) so tests can reset it via
	 * reset_form_setup_card_cache() and exercise the populated path — a
	 * function-local static pins the first result for the whole process. Keyed by
	 * user id since the payload derives from that user's capabilities.
	 * `false` means "not computed yet"; `null`/array is a computed result.
	 *
	 * @var array<int,array<string,mixed>|null>
	 * @since 2.12.4
	 */
	private static $setup_card_cache = [];

	/**
	 * Action items for this request, or null before the first build.
	 *
	 * Built twice on every admin page without this -- once for the localisation
	 * payload, once in the classic renderer -- and each open failure category reads
	 * a log excerpt. get_action_items() also records an impression, which running
	 * twice counted twice.
	 *
	 * Reset with reset_action_items_cache(). Admin is a singleton, so without that
	 * the first build pins the answer for the whole process and any test that
	 * records a failure and then asks again is testing the memo.
	 *
	 * @var array<int,array<string,mixed>>|null
	 * @since 2.12.7
	 */
	private static $action_items_cache = null;

	/**
	 * Class constructor.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ], 9 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_menu', [ $this, 'settings_page' ] );
		add_action( 'admin_menu', [ $this, 'add_learn_page' ] );
		add_action( 'admin_menu', [ $this, 'add_new_form' ] );
		if ( ! Helper::hide_promotions() ) {
			add_action( 'admin_menu', [ $this, 'add_suremail_page' ] );
		}
		if ( ! Helper::has_pro() ) {
			add_action( 'admin_menu', [ $this, 'add_quiz_page' ] );
			add_action( 'admin_menu', [ $this, 'add_survey_reports_page' ] );
			add_action( 'admin_menu', [ $this, 'add_partial_entries_page' ] );
			add_action( 'admin_menu', [ $this, 'add_upgrade_to_pro' ] );
			add_action( 'admin_footer', [ $this, 'add_upgrade_to_pro_target_attr' ] );
		}

		add_filter( 'plugin_action_links', [ $this, 'add_settings_link' ], 10, 2 );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_styles' ] );
		add_action( 'admin_head', [ $this, 'enqueue_header_styles' ] );
		add_filter( 'admin_body_class', [ $this, 'admin_template_picker_body_class' ] );

		// this action is used to restrict Spectra's quick action bar on SureForms CPTS.
		add_action( 'uag_enable_quick_action_sidebar', [ $this, 'restrict_spectra_quick_action_bar' ] );

		add_action( 'current_screen', [ $this, 'enable_gutenberg_for_sureforms' ], 100 );
		// Register notices early for React pages (before admin_enqueue_scripts).
		add_action( 'admin_init', [ $this, 'register_pro_compatibility_notices' ], 5 );

		// Database maintenance notice: the entries table is missing, so submissions
		// cannot be saved. Registered at admin_init priority 5 so the React notice is
		// in place before admin_enqueue_scripts localizes it.
		add_action( 'admin_init', [ $this, 'register_database_repair_notice' ], 5 );
		add_action( 'admin_notices', [ $this, 'render_action_item_notices' ] );
		// Late priority so the items are built after anything hooking
		// srfm_action_items has had a chance to register.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_action_item_styles' ], 20 );
		add_action( 'admin_notices', [ $this, 'render_database_repair_notice' ] );
		add_action( 'admin_post_srfm_repair_entries_table', [ $this, 'handle_database_repair' ] );
		// Display notices on traditional WordPress admin pages.
		add_action( 'admin_notices', [ $this, 'srfm_pro_version_compatibility' ] );

		// Enfold theme compatibility to enable block editor for SureForms post type.
		add_filter( 'avf_use_block_editor_for_post', [ $this, 'enable_block_editor_in_enfold_theme' ] );

		// Add action links to the plugin page.
		add_filter( 'plugin_action_links_' . SRFM_BASENAME, [ $this, 'add_action_links' ] );
		// Check if admin notification is enabled and add entries badge.
		$general_options       = get_option( 'srfm_general_settings_options', [] );
		$admin_notification_on = isset( $general_options['srfm_admin_notification'] ) ? (bool) $general_options['srfm_admin_notification'] : true;

		if ( $admin_notification_on ) {
			add_action( 'admin_menu', [ $this, 'maybe_add_entries_badge' ], 99 );
		}

		add_filter( 'wpforms_current_user_can', [ $this, 'disable_wpforms_capabilities' ], 10, 3 );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_pointer' ] );
		// Ajax callbacks for wp-pointer functionality.
		add_action( 'wp_ajax_should_show_pointer', [ $this, 'pointer_should_show' ] );
		add_action( 'wp_ajax_sureforms_dismiss_pointer', [ $this, 'pointer_dismissed' ] );
		add_action( 'wp_ajax_sureforms_accept_cta', [ $this, 'pointer_accepted_cta' ] );
		add_action( 'wp_ajax_srfm_notice_response', [ $this, 'handle_notice_response' ] );
		add_action( 'wp_ajax_srfm_dismiss_action_item', [ $this, 'handle_dismiss_action_item' ] );
		add_action( 'admin_post_srfm_dismiss_action_item_link', [ $this, 'handle_dismiss_action_item_link' ] );
		add_action( 'wp_ajax_srfm_ai_widget_usage', [ $this, 'track_ai_widget_usage' ] );
		add_action( 'load-post.php', [ $this, 'maybe_track_edit_form_button_click' ] );
		add_filter( 'removable_query_args', [ $this, 'add_removable_query_args' ] );

		// Register dashboard widget only if there are recent entries.
		add_action( 'admin_init', [ $this, 'maybe_register_dashboard_widget' ] );

		// Enqueue the AI quick draft widget script on the dashboard screen.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_ai_dashboard_widget_assets' ] );

		// "Finish setting up" checklist widget on the main WP dashboard (#3031).
		add_action( 'wp_dashboard_setup', [ $this, 'register_form_setup_widget' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_form_setup_widget_assets' ] );

		// Drop the "no imported forms" negative cache as soon as a post is stamped
		// with the import marker, so a template imported after the cache was written
		// surfaces immediately instead of waiting for the transient to expire.
		add_action( 'added_post_meta', [ $this, 'invalidate_starter_template_cache' ], 10, 3 );
		add_action( 'updated_post_meta', [ $this, 'invalidate_starter_template_cache' ], 10, 3 );

		// Save first form creation time stamp.
		add_action( 'admin_init', [ $this, 'save_first_form_creation_time_stamp' ] );
		add_action( 'admin_notices', [ $this, 'display_srfm_rating_notice' ] );
		add_action( 'admin_notices', [ $this, 'display_srfm_getting_started_notice' ] );

		// "Finish setting up" prompt, shown as an Astra Notices admin notice on
		// every admin screen except the dashboard (#3030).
		add_action( 'admin_notices', [ $this, 'render_thankyou_prompt_notice' ] );

		/**
		 * Suppress foreign (third-party) admin notices on SureForms admin screens.
		 *
		 * Some plugins (e.g. Ninja Forms) print large promotional banners on every
		 * admin page via the admin_notices / all_admin_notices / network_admin_notices
		 * hooks. These bleed onto SureForms' own React admin screens and break the UI.
		 * We run at the EARLIEST priority on each notice hook (all third-party
		 * callbacks are registered before these hooks fire, during admin_init /
		 * plugin load) and strip the foreign ones before they are echoed, while
		 * preserving SureForms' own notices. Scoped strictly to SureForms screens.
		 */
		add_action( 'admin_notices', [ $this, 'suppress_foreign_admin_notices' ], PHP_INT_MIN );
		add_action( 'all_admin_notices', [ $this, 'suppress_foreign_admin_notices' ], PHP_INT_MIN );
		add_action( 'network_admin_notices', [ $this, 'suppress_foreign_admin_notices' ], PHP_INT_MIN );
	}

	/**
	 * Remove third-party admin notices on SureForms admin screens.
	 *
	 * Iterates over the callbacks registered on the admin notice hooks and
	 * removes any that are not owned by SureForms. A callback is considered
	 * owned by SureForms when it belongs to a class in the `SRFM` / `SRFM_PRO`
	 * namespaces or to the bundled `BSF_Admin_Notices` / `Astra_Notices`
	 * notices library. SureForms' own notices are therefore preserved while
	 * foreign promotional banners are suppressed.
	 *
	 * This callback is hooked at `PHP_INT_MIN` so that it runs first on each
	 * notice hook and removes the foreign callbacks before WordPress echoes
	 * them (WP_Hook honours removals made during iteration). It is strictly
	 * scoped to SureForms admin screens via {@see Helper::is_sureforms_admin_page()}
	 * so no other admin page is affected.
	 *
	 * @since 2.10.0
	 * @return void
	 */
	public function suppress_foreign_admin_notices() {
		// Bail early if we are not on a SureForms admin screen. This keeps the
		// suppression strictly scoped and avoids touching any other admin page.
		// is_sureforms_admin_page() covers the core screens (dashboard, add-new,
		// settings, entries, the form CPT); we additionally match any admin page
		// whose `page` slug is SureForms-owned (sureforms_* / srfm_*) so the
		// suppression also applies to the payments/quiz/survey/learn/SMTP screens.
		if ( ! Helper::is_sureforms_admin_page() && ! $this->is_sureforms_owned_admin_page() ) {
			return;
		}

		global $wp_filter;

		// The hook currently being fired (admin_notices, all_admin_notices or network_admin_notices).
		$current_hook = current_action();

		if ( empty( $current_hook ) || empty( $wp_filter[ $current_hook ] ) || ! ( $wp_filter[ $current_hook ] instanceof \WP_Hook ) ) {
			return;
		}

		foreach ( $wp_filter[ $current_hook ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'] ?? null;

				// Never remove our own suppression callback.
				if ( is_array( $function ) && isset( $function[0] ) && $function[0] === $this && 'suppress_foreign_admin_notices' === $function[1] ) {
					continue;
				}

				// Preserve SureForms-owned notices, remove everything else.
				if ( $this->is_sureforms_owned_notice_callback( $function ) ) {
					continue;
				}

				remove_action( $current_hook, $function, $priority );
			}
		}
	}

	/**
	 * Get the first form creation time stamp.
	 *
	 * @since 1.10.1
	 * @return int|false
	 */
	public static function get_first_form_creation_time_stamp() {
		return Helper::get_srfm_option( 'first_form_created_at', false );
	}

	/**
	 * Check if the first form has been created.
	 *
	 * @since 1.10.1
	 * @return bool
	 */
	public static function is_first_form_created() {
		// Convert the first form creation time stamp to a boolean. If it exists, it will return true, otherwise false.
		$first_form_creation_time_stamp = self::get_first_form_creation_time_stamp();

		// If the first form creation time stamp is not set, return false.
		if ( ! $first_form_creation_time_stamp ) {
			return false; // No forms created yet.
		}

		// Check if the first form creation time stamp is a valid integer and greater than zero.
		return is_int( $first_form_creation_time_stamp ) && $first_form_creation_time_stamp > 0;
	}

	/**
	 * Whether a form's confirmation message is still the shipped default.
	 *
	 * Compared on tag-stripped, entity-decoded, whitespace-collapsed text rather
	 * than raw HTML: the default is stored with a base64 icon on creation but
	 * regenerated with a URL icon, so the markup differs while the wording does
	 * not, and a starter-template import can store a literal apostrophe where the
	 * generated default carries the encoded `&#039;` — decoding entities makes both
	 * compare equal. Any real edit to the heading or body text changes the text and
	 * flips this to false, which is exactly when the prompt should stop showing.
	 *
	 * Locale caveat: the comparison target is translated at call time, so a form
	 * whose default was stored under a different active locale won't match. That
	 * fails safe — the prompt simply doesn't show — never a false nag.
	 *
	 * @param int $form_id Form post ID.
	 *
	 * @since 2.12.4
	 * @return bool
	 */
	public static function is_default_confirmation_message( $form_id ) {
		$confirmation = get_post_meta( (int) $form_id, '_srfm_form_confirmation', true );

		if ( ! is_array( $confirmation ) || ! isset( $confirmation[0]['message'] ) || ! is_string( $confirmation[0]['message'] ) ) {
			return false;
		}

		// The default message is only ever shown for a "same page" confirmation.
		// A redirect ("different page" / "custom url") never renders it, yet the
		// stored settings still seed the default message string — so without this
		// guard a redirect form would be nagged forever about a message no visitor
		// sees, with no way to clear the prompt by doing what it asks.
		if ( ! isset( $confirmation[0]['confirmation_type'] ) || 'same page' !== $confirmation[0]['confirmation_type'] ) {
			return false;
		}

		$message = $confirmation[0]['message'];

		if ( '' === trim( $message ) ) {
			return false;
		}

		$normalize = static function ( $html ) {
			// Decode entities too, so an encoded apostrophe (&#039;) in the generated
			// default matches a literal one stored by a template import.
			$text = html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES, 'UTF-8' );
			return trim( (string) preg_replace( '/\s+/', ' ', $text ) );
		};

		return $normalize( $message ) === $normalize( Global_Settings::get_default_confirmation_message() );
	}

	/**
	 * Whether a form has somewhere to send replies (an enabled email notification
	 * with a non-empty recipient).
	 *
	 * @param int $form_id Form post ID.
	 *
	 * @since 2.12.4
	 * @return bool
	 */
	public static function form_has_reply_destination( $form_id ) {
		$notifications = get_post_meta( (int) $form_id, '_srfm_email_notification', true );

		if ( ! is_array( $notifications ) ) {
			return false;
		}

		foreach ( $notifications as $notification ) {
			if ( is_array( $notification ) && ! empty( $notification['status'] ) && ! empty( $notification['email_to'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The most recently created form still needing setup (default Thank You
	 * message, or no reply destination).
	 *
	 * Powers the "Finish setting up" prompt (#3030). Limited to the single latest
	 * such form to avoid clutter, and to forms the current user may actually edit.
	 * A form is a candidate when it still has an unfinished step (default Thank You
	 * message, or no reply destination). Dismissal is enforced by the caller,
	 * before this query runs.
	 *
	 * @since 2.12.4
	 * @return array<int,array<string,mixed>> One entry, or none.
	 */
	public static function get_thankyou_prompt_forms() {
		// Memoized for the request so repeated reads (e.g. the notice render plus
		// any add-on consumer) share a single query. Sentinel is null, not false,
		// so a filter returning false (__return_false to disable) still memoizes.
		if ( null !== self::$thankyou_prompt_cache ) {
			return self::$thankyou_prompt_cache;
		}

		/**
		 * Filter the forms the "Finish setting up" Thank You notice may surface.
		 *
		 * @param array<int,array<string,mixed>> $prompts Candidate prompt payloads.
		 *
		 * @since 2.12.4
		 */
		$filtered                    = apply_filters( 'srfm_thankyou_prompt_forms', self::compute_thankyou_prompt_forms() );
		self::$thankyou_prompt_cache = is_array( $filtered ) ? $filtered : [];

		return self::$thankyou_prompt_cache;
	}

	/**
	 * Clear the request memo for the action items.
	 *
	 * Admin is a singleton, so the memo outlives a request in a test process.
	 * Anything that records or clears a failure inside one process has to call
	 * this, or it reads the answer from before the change.
	 *
	 * @since 2.12.7
	 * @return void
	 */
	public static function reset_action_items_cache() {
		self::$action_items_cache = null;
	}

	/**
	 * Clear the request memo for the Thank You prompt (#3030).
	 *
	 * Lets tests exercise the memoized public path, and is a safe hook for anything
	 * that changes which form qualifies (e.g. a form save).
	 *
	 * @since 2.12.4
	 * @return void
	 */
	public static function reset_thankyou_prompt_cache() {
		self::$thankyou_prompt_cache = null;
	}

	/**
	 * Setup-checklist data for the newest starter-template form (#3031).
	 *
	 * Picks the most recent form the current user can edit that was created from an
	 * Astra Sites starter template. The widget
	 * lists a fixed set of optional next-steps for it — their completion is not
	 * computed — so the payload carries only the form and the CTA targets. Memoized
	 * for the request so the widget register/enqueue/render passes share one query.
	 *
	 * @since 2.12.4
	 * @return array<string,mixed>|null Card payload, or null when there is no candidate form.
	 */
	public static function get_form_setup_card() {
		$user_id = get_current_user_id();

		// Request memo, keyed per user — the payload derives from that user's
		// capabilities. Reset via reset_form_setup_card_cache().
		if ( array_key_exists( $user_id, self::$setup_card_cache ) ) {
			return self::$setup_card_cache[ $user_id ];
		}

		self::$setup_card_cache[ $user_id ] = self::compute_form_setup_card();

		return self::$setup_card_cache[ $user_id ];
	}

	/**
	 * Drop the "no imported forms" negative cache when the marker is written.
	 *
	 * Hooked to added_post_meta/updated_post_meta. Without this, a starter template
	 * imported after the negative cache was written would show neither the Thank You
	 * prompt nor the setup widget until the transient expired.
	 *
	 * Arguments are read from func_get_args() rather than declared: the hook passes
	 * ( $meta_id, $post_id, $meta_key ) and the meta id is never needed, so declaring
	 * it would leave an unused parameter that the coding-standards gate rejects.
	 *
	 * @since 2.12.4
	 * @return void
	 */
	public function invalidate_starter_template_cache() {
		$args     = func_get_args();
		$post_id  = isset( $args[1] ) ? (int) $args[1] : 0;
		$meta_key = isset( $args[2] ) ? (string) $args[2] : '';

		if ( self::ASTRA_SITES_IMPORT_META !== $meta_key ) {
			return;
		}

		// A full-site import stamps this marker on every post it creates, so narrow to
		// our own post type: both features only ever query sureforms_form, and this
		// avoids clearing the cache repeatedly for pages and products during an import.
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) || SRFM_FORMS_POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		delete_transient( self::NO_IMPORTED_FORMS_TRANSIENT );
	}

	/**
	 * Clear the setup-card request memo (#3031).
	 *
	 * Lets tests exercise the populated path, and is a safe hook for anything that
	 * changes which form qualifies (e.g. a form save).
	 *
	 * @since 2.12.4
	 * @return void
	 */
	public static function reset_form_setup_card_cache() {
		self::$setup_card_cache = [];
	}

	/**
	 * REST handler: record a "Finish setting up" widget interaction (#3031).
	 *
	 * Records the clicked CTA/view action as an analytics event. The request
	 * carries the displayed form id: capability is re-checked against it here —
	 * beyond the route's generic permission callback — so only a genuine editor of
	 * that form can act.
	 *
	 * @param \WP_REST_Request<array<string,mixed>> $request Request.
	 *
	 * @since 2.12.4
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function dismiss_form_setup_card( $request ) {
		$form_id = absint( $request->get_param( 'form_id' ) );

		if ( $form_id <= 0 || ! defined( 'SRFM_FORMS_POST_TYPE' ) || SRFM_FORMS_POST_TYPE !== get_post_type( $form_id ) || ! current_user_can( 'edit_post', $form_id ) ) {
			return new \WP_Error( 'srfm_setup_card_forbidden', __( 'You are not allowed to update this prompt.', 'sureforms' ), [ 'status' => 403 ] );
		}

		$action = sanitize_key( (string) $request->get_param( 'action' ) );

		// Interaction analytics for the setup widget (#3031). Each event carries a
		// date automatically (see BSF_Analytics_Events::track()) and dedupes per
		// event name, matching the sibling notice's telemetry.
		$events = [
			'edit_form'     => 'form_setup_widget_edit_form',
			'edit_thankyou' => 'form_setup_widget_edit_thankyou',
			'set_up_email'  => 'form_setup_widget_set_up_email',
			'view_form'     => 'form_setup_widget_view_form',
		];

		if ( isset( $events[ $action ] ) ) {
			Analytics::events()->track( $events[ $action ], (string) $form_id );
		}

		return new \WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Register the "Finish setting up" checklist widget on the main WP dashboard (#3031).
	 *
	 * Only for capable users, and only when there is a form still needing setup —
	 * so the widget never appears empty. The data is memoized in get_form_setup_card()
	 * and reused by the enqueue and render passes.
	 *
	 * @since 2.12.4
	 * @return void
	 */
	public function register_form_setup_widget() {
		if ( ! Helper::current_user_can() || Helper::hide_promotions() ) {
			return;
		}

		if ( null === self::get_form_setup_card() ) {
			return;
		}

		wp_add_dashboard_widget(
			'srfm_form_setup_checklist',
			__( 'Finish setting up your form', 'sureforms' ),
			[ $this, 'render_form_setup_widget' ],
			null,
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Render the setup-checklist widget content (#3031).
	 *
	 * A heading (with a link to view the form), a subtitle, and a fixed list of
	 * optional next-steps — each always shown with its CTA; completion is not
	 * computed. Each CTA deep-links into the editor (edit form / Thank You message /
	 * email notification) and records an analytics event via the REST endpoint
	 * wired in the enqueued inline script.
	 *
	 * @since 2.12.4
	 * @return void
	 */
	public function render_form_setup_widget() {
		$card = self::get_form_setup_card();

		if ( null === $card ) {
			return;
		}

		// Optional next-steps — always offered, their completion is not computed.
		// 'event' is the analytics action key beaconed on click (see the widget JS).
		$rows = [
			[
				'label' => __( 'Review or edit your form', 'sureforms' ),
				'cta'   => __( 'Edit form', 'sureforms' ),
				'url'   => $card['edit_url'],
				'event' => 'edit_form',
			],
			[
				'label' => __( 'Personalize the Thank You message', 'sureforms' ),
				'cta'   => __( 'Edit message', 'sureforms' ),
				'url'   => $card['thankyou_url'],
				'event' => 'edit_thankyou',
			],
			[
				'label' => __( 'Choose who gets notified of new replies', 'sureforms' ),
				'cta'   => __( 'Set up email', 'sureforms' ),
				'url'   => $card['email_url'],
				'event' => 'set_up_email',
			],
		];

		// Fall back to a generic label for an untitled form so the heading never
		// renders "Finish setting up " with a dangling space.
		$card_title = '' !== trim( (string) $card['title'] ) ? $card['title'] : __( 'your form', 'sureforms' );
		$heading    = sprintf(
			/* translators: %s: form title. */
			__( 'Finish setting up %s', 'sureforms' ),
			$card_title
		);
		?>
		<div class="srfm-setup-checklist" id="srfm-setup-checklist">
			<p class="srfm-setup-checklist__title">
				<?php echo esc_html( $heading ); ?>
				<?php if ( ! empty( $card['view_url'] ) ) { ?>
					<a class="srfm-setup-checklist__view" data-srfm-event="view_form" href="<?php echo esc_url( $card['view_url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: form title. */ __( 'View %s (opens in a new tab)', 'sureforms' ), $card_title ) ); ?>">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
					</a>
				<?php } ?>
			</p>
			<p class="srfm-setup-checklist__subtitle"><?php esc_html_e( 'Customize your form to get it ready for real submissions:', 'sureforms' ); ?></p>

			<ul class="srfm-setup-checklist__steps">
				<?php foreach ( $rows as $row ) { ?>
					<li class="srfm-setup-checklist__step">
						<span class="srfm-setup-checklist__label"><?php echo esc_html( $row['label'] ); ?></span>
						<a class="srfm-setup-checklist__cta" data-srfm-event="<?php echo esc_attr( $row['event'] ); ?>" href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $row['cta'] ); ?></a>
					</li>
				<?php } ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Enqueue the setup-checklist widget's styles and behavior on the dashboard (#3031).
	 *
	 * Mirrors the AI widget convention: an inline-only handle carries the CSS and the
	 * behavior (CTA click analytics), with server values —
	 * the REST URL, nonce and form id — passed through wp_localize_script rather than
	 * printed into the markup, so it stays Plugin-Check clean.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 *
	 * @since 2.12.4
	 * @return void
	 */
	public function enqueue_form_setup_widget_assets( $hook_suffix ) {
		if ( 'index.php' !== $hook_suffix || ! Helper::current_user_can() || Helper::hide_promotions() ) {
			return;
		}

		$card = self::get_form_setup_card();

		if ( null === $card ) {
			return;
		}

		$css = <<<'CSS'
#srfm_form_setup_checklist .inside { margin: 0; padding: 0; }
.srfm-setup-checklist { padding: 12px 16px 16px; }
.srfm-setup-checklist__title { margin: 0 0 4px; font-size: 15px; font-weight: 600; color: #1e1e1e; }
.srfm-setup-checklist__view { display: inline-flex; align-items: center; margin-left: 6px; color: #d54e21; vertical-align: middle; }
.srfm-setup-checklist__view:hover, .srfm-setup-checklist__view:focus { color: #b83c14; }
.srfm-setup-checklist__subtitle { margin: 0 0 12px; color: #646970; font-size: 13px; }
.srfm-setup-checklist__steps { margin: 0; padding: 0; list-style: none; }
.srfm-setup-checklist__step { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; }
.srfm-setup-checklist__step + .srfm-setup-checklist__step { margin-top: 6px; }
.srfm-setup-checklist__step { background: #f6f7f7; }
.srfm-setup-checklist__label { flex: 1 1 auto; font-size: 14px; color: #1e1e1e; }
.srfm-setup-checklist__cta { margin-left: auto; border: 0; background: transparent; padding: 0; font-size: 14px; font-weight: 600; color: #d54e21; text-decoration: underline; cursor: pointer; }
.srfm-setup-checklist__cta:hover { color: #b83c14; }
/* Keep visited links on-brand — WP admin's a:visited would otherwise turn them blue. */
.srfm-setup-checklist a:visited { color: #d54e21; }
.srfm-setup-checklist a:visited:hover, .srfm-setup-checklist a:visited:focus { color: #b83c14; }
/* Drop WP's blue focus ring on the widget's links; keep an accessible, on-brand keyboard outline. */
.srfm-setup-checklist a:focus { outline: none; box-shadow: none; }
.srfm-setup-checklist a:focus-visible { outline: 2px solid #d54e21; outline-offset: 2px; box-shadow: none; }
CSS;

		wp_register_style( 'srfm-setup-checklist-widget', false, [], SRFM_VER );
		wp_enqueue_style( 'srfm-setup-checklist-widget' );
		wp_add_inline_style( 'srfm-setup-checklist-widget', $css );

		wp_register_script( 'srfm-setup-checklist-widget', '', [], SRFM_VER, true );
		wp_enqueue_script( 'srfm-setup-checklist-widget' );

		wp_localize_script(
			'srfm-setup-checklist-widget',
			'srfmSetupChecklist',
			[
				'restUrl' => esc_url_raw( rest_url( 'sureforms/v1/dismiss-form-setup-card' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'formId'  => $card['id'],
			]
		);

		$inline_script = <<<'JS'
( function () {
	const cfg = window.srfmSetupChecklist || {};
	const widget = document.getElementById( 'srfm-setup-checklist' );
	if ( ! widget ) {
		return;
	}

	const persist = function ( action ) {
		return fetch( cfg.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			keepalive: true,
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
			body: JSON.stringify( { form_id: cfg.formId, action: action } ),
		} ).catch( function () {} );
	};

	// Beacon the CTA / view-form clicks for analytics. keepalive on the fetch lets
	// the request finish even though the CTA immediately navigates away.
	widget.addEventListener( 'click', function ( e ) {
		const target = e.target?.closest?.( '[data-srfm-event]' );
		if ( target ) {
			persist( target.getAttribute( 'data-srfm-event' ) );
		}
	} );
}() );
JS;

		wp_add_inline_script( 'srfm-setup-checklist-widget', $inline_script );
	}

	/**
	 * Register the "Finish setting up" prompt as an Astra Notices admin notice (#3030).
	 *
	 * Hooked to admin_notices so it registers before the Astra Notices library
	 * renders (priority 30). Shown on every admin screen EXCEPT the main dashboard,
	 * for the newest form the current user can edit that still has an unfinished
	 * step (default Thank You message, or no reply destination). Uses a single
	 * stable notice id so the library's built-in ✕ dismissal is one persistent
	 * choice ("stop nudging me"), not a per-form row.
	 *
	 * Split out from the renderer so the decision has exactly one home. The Getting
	 * Started notice suppresses itself when this returns a form, and duplicating the
	 * conditions there would have meant two copies drifting apart. Reading it costs
	 * nothing extra — get_thankyou_prompt_forms() memoizes its query per request.
	 *
	 * @since 2.12.6
	 * @return array<string,mixed>|null The form to prompt for, or null when no prompt should render.
	 */
	public function get_displayable_thankyou_prompt() {
		if ( ! Helper::current_user_can() || ! class_exists( 'Astra_Notices' ) ) {
			return null;
		}

		/**
		 * Short-circuit the "Finish setting up" Thank You notice.
		 *
		 * @param bool $show Whether to show the notice. Default true.
		 *
		 * @since 2.12.4
		 */
		if ( ! apply_filters( 'srfm_show_thankyou_prompt', true ) ) {
			return null;
		}

		// Everywhere in wp-admin except the main dashboard. A null screen fails
		// closed (return) rather than registering the notice on an unknown screen.
		$screen = get_current_screen();

		if ( ! $screen || 'dashboard' === $screen->id ) {
			return null;
		}

		// The library only checks dismissal at render (priority 30, after this
		// query would already have run). Check it up front so a user who dismissed
		// the prompt never pays for the WP_Query on subsequent admin page views.
		if ( 'notice-dismissed' === get_user_meta( get_current_user_id(), self::THANKYOU_PROMPT_NOTICE_ID, true ) ) {
			return null;
		}

		// array_values so a filter returning a key-preserving array (e.g. the
		// result of array_filter()) still exposes the newest prompt at index 0.
		$prompts = array_values( (array) self::get_thankyou_prompt_forms() );

		// Validate every key build_thankyou_notice_markup() reads, not just id/edit_url
		// — a filter returning a partial payload would otherwise trip "Undefined array
		// key" warnings and esc_url( null ) deprecations on every admin page.
		if (
			empty( $prompts[0] ) || ! is_array( $prompts[0] )
			|| empty( $prompts[0]['id'] ) || empty( $prompts[0]['edit_url'] )
			|| empty( $prompts[0]['thankyou_url'] ) || empty( $prompts[0]['replies_url'] )
			|| ! isset( $prompts[0]['title'] )
		) {
			return null;
		}

		return $prompts[0];
	}

	/**
	 * Render the "Finish setting up" Thank You notice (#3030).
	 *
	 * @since 2.12.4
	 * @return void
	 */
	public function render_thankyou_prompt_notice() {
		$notice_id = self::THANKYOU_PROMPT_NOTICE_ID;
		$form      = $this->get_displayable_thankyou_prompt();

		if ( null === $form ) {
			return;
		}

		// A broken form outranks a setup prompt. This is the top of the existing
		// precedence chain, so the action-item check goes here rather than the
		// action items standing down for an engagement notice.
		if ( $this->has_action_item_warnings() ) {
			return;
		}

		\Astra_Notices::add_notice(
			[
				'id'                         => $notice_id,
				'type'                       => 'info',
				'message'                    => self::build_thankyou_notice_markup( $form ),
				'class'                      => 'srfm-notice srfm-thankyou-notice',
				'is_dismissible'             => true,
				'display-with-other-notices' => true,
				// Render late so this nudge never pre-empts higher-priority notices
				// (e.g. Astra's minimum-version warnings, which are display-with-
				// other-notices => false and would be skipped once ours renders).
				'priority'                   => 100,
			]
		);

		// The message is wp_kses_post'd by the library, so the brand-orange styling
		// is printed through the notice's pre-markup hook instead of inline.
		add_action( 'astra_notice_before_markup_' . $notice_id, [ $this, 'print_srfm_notice_styles' ] );

		// Track clicks on the CTAs and the dismiss ✕ via the shared notice-response
		// endpoint, enqueued only when the notice actually renders.
		add_action( 'astra_notice_after_markup_' . $notice_id, [ $this, 'enqueue_thankyou_notice_tracking' ] );
	}

	/**
	 * Enqueue the click-tracking for the Thank You notice (#3030).
	 *
	 * Sends an analytics beacon to the shared `srfm_notice_response` AJAX handler
	 * when a CTA or the dismiss ✕ is clicked. Uses `keepalive` so the beacon
	 * survives the navigation the CTA links trigger.
	 *
	 * @since 2.12.4
	 * @return void
	 */
	public function enqueue_thankyou_notice_tracking() {
		if ( wp_script_is( 'srfm-thankyou-notice-track', 'enqueued' ) ) {
			return;
		}

		wp_register_script( 'srfm-thankyou-notice-track', '', [], SRFM_VER, true );
		wp_enqueue_script( 'srfm-thankyou-notice-track' );

		$config = wp_json_encode(
			[
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'srfm_notice_response' ),
			]
		);

		wp_add_inline_script( 'srfm-thankyou-notice-track', 'window.srfmThankYouNoticeTrack = ' . $config . ';', 'before' );

		$inline_script = <<<'JS'
( function () {
	const cfg = window.srfmThankYouNoticeTrack || {};
	const wrap = document.querySelector( '.srfm-thankyou-notice' );
	if ( ! wrap ) {
		return;
	}
	const noticeId = wrap.id || '';
	const send = function ( button ) {
		const body = new URLSearchParams();
		body.append( 'action', 'srfm_notice_response' );
		body.append( 'nonce', cfg.nonce );
		body.append( 'notice_id', noticeId );
		body.append( 'button', button );
		fetch( cfg.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			keepalive: true,
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		} ).catch( function () {} );
	};
	// Delegate from the wrapper: this inline script runs at parse time, before
	// core's common.js injects the .notice-dismiss ✕ (on DOMContentLoaded), so a
	// direct querySelector for it would find nothing and the dismiss beacon would
	// never fire. Delegation catches the ✕ and the CTAs whenever they exist.
	const ctas = [
		[ '.srfm-ty-edit-form', 'edit_form' ],
		[ '.srfm-ty-set-replies', 'set_replies' ],
		[ '.srfm-ty-edit-thankyou', 'edit_thankyou' ],
	];
	wrap.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '.notice-dismiss' ) ) {
			send( 'dismissed' );
			return;
		}
		for ( let i = 0; i < ctas.length; i++ ) {
			if ( e.target.closest( ctas[ i ][ 0 ] ) ) {
				send( ctas[ i ][ 1 ] );
				return;
			}
		}
	} );
}() );
JS;

		wp_add_inline_script( 'srfm-thankyou-notice-track', $inline_script );
	}

	/**
	 * Print the Thank You notice's brand-orange styling (#3030).
	 *
	 * Fired via astra_notice_before_markup_{id} so it lands right before the notice
	 * and only when the notice actually renders.
	 *
	 * @since 2.12.4
	 * @return void
	 */
	public function print_srfm_notice_styles() {
		// The library wp_kses_post()'s the message, which strips <svg> and data:
		// image srcs, so the SureForms mark is painted as a CSS background here
		// (this hook fires outside that kses call). URL-encoded, not base64, so the
		// value is fully percent-encoded and safe to pass through esc_url.
		$icon = 'data:image/svg+xml,' . rawurlencode(
			'<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 32 32"><path fill="#D54407" fill-rule="evenodd" clip-rule="evenodd" d="M32 0H0V32H32V0ZM22.8573 6.85728H9.14304V11.4287V13.7144L11.4288 11.4287H22.8573V6.85728ZM20.5717 13.7146H9.14314V18.286V20.5714V20.5718V25.1428H16.0003V20.5714H9.14351L11.4289 18.286H20.5717V13.7146Z"/></svg>'
		);
		?>
		<style id="srfm-notice-styles">
			.srfm-notice.notice { border-left-color: #D54407; }
			/* Stack our blocks (the library lays the container out as a flex row) and reserve room on the left for the SureForms mark. */
			.srfm-notice .astra-notice-container { display: block; padding: 4px 0 4px 52px; background: url('<?php echo esc_url( $icon, [ 'data' ] ); ?>') no-repeat 4px 6px; background-size: 32px 32px; }
			.srfm-notice .srfm-notice__title { margin: 0 0 4px; font-size: 14px; font-weight: 600; color: #1d2327; }
			.srfm-notice .srfm-notice__text { margin: 0 0 10px; color: #50575e; }
			.srfm-notice .srfm-notice__actions { margin: 12px 0 2px; display: flex; flex-wrap: wrap; gap: 10px 20px; align-items: center; }
			.srfm-notice .button-primary { background: #D54407; border-color: #D54407; color: #fff; box-shadow: none; text-shadow: none; }
			.srfm-notice .button-primary:hover, .srfm-notice .button-primary:focus { background: #C83B00; border-color: #C83B00; color: #fff; box-shadow: none; }
			.srfm-notice .button:not(.button-primary) { background: transparent; border-color: transparent; color: #D54407; box-shadow: none; padding: 0; }
			.srfm-notice .button:not(.button-primary):hover, .srfm-notice .button:not(.button-primary):focus { background: transparent; border-color: transparent; color: #C83B00; box-shadow: none; }
			.srfm-notice .button-primary:focus { outline: 2px solid #D54407; outline-offset: 1px; }
		</style>
		<?php
	}

	/**
	 * Check and save the first form creation time stamp.
	 * If not already saved.
	 *
	 * @since 1.10.1
	 * @return void
	 */
	public static function save_first_form_creation_time_stamp() {
		if ( ! Helper::current_user_can() || self::is_first_form_created() || ! defined( 'SRFM_FORMS_POST_TYPE' ) || ! post_type_exists( SRFM_FORMS_POST_TYPE ) ) {
			return;
		}

		// Get the first form creation time from the database that is published.
		$query = new \WP_Query(
			[
				'post_type'      => SRFM_FORMS_POST_TYPE,
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'post_status'    => 'publish',
			]
		);

		if ( ! empty( $query->posts ) && isset( $query->posts[0] ) ) {
			// Get the first post from the query result.
			$post_id = $query->posts[0];
			// Get the post creation time in GMT.
			$creation_time = get_post_field( 'post_date_gmt', $post_id );
			// Convert the creation time to a timestamp.
			$timestamp = strtotime( $creation_time );

			if ( ! $timestamp ) {
				return;
			}

			Helper::update_srfm_option( 'first_form_created_at', $timestamp );
		}
	}

	/**
	 * Check if n days have passed since the first form creation.
	 * This is used to determine if the dynamic nudges should be shown.
	 *
	 * @param int $days Number of days to check.
	 * @since 1.10.1
	 * @return bool
	 */
	public static function check_first_form_creation_threshold( $days = 3 ) {
		$first_form_creation_time_stamp = self::get_first_form_creation_time_stamp();

		if ( ! $first_form_creation_time_stamp ) {
			return false; // No forms created yet.
		}

		/**
		 * Calculate the number of days since the first form was created.
		 */
		$days_from_creation = ( strtotime( current_time( 'mysql' ) ) - $first_form_creation_time_stamp ) / DAY_IN_SECONDS;

		// Return a boolean indicating if the number of days since creation is greater than the specified days.
		return $days_from_creation > $days;
	}

	/**
	 * Show action on plugin page.
	 *
	 * @param  array $links links.
	 * @return array
	 * @since 1.4.2
	 */
	public function add_action_links( $links ) {
		if ( ! Helper::has_pro() ) {
			// Display upsell link if SureForms Pro is not installed.
			$upsell_link = Helper::get_sureforms_website_url( 'pricing', [ 'utm_medium' => 'plugin-list' ] );

			ob_start();
			?>
			<a href="<?php echo esc_url( $upsell_link ); ?>" target="_blank" rel="noreferrer" class="sureforms-plugins-go-pro">
				<?php echo esc_html__( 'Get SureForms Pro', 'sureforms' ); ?>
			</a>
			<?php
			$links[] = trim( ob_get_clean() );
		}

		return $links;
	}

	/**
	 * Enable block editor in Enfold theme for SureForms post type.
	 *
	 * @param bool $use_block_editor Whether to use block editor.
	 * @since 1.3.1
	 */
	public function enable_block_editor_in_enfold_theme( $use_block_editor ) {
		// if SureForms form post type then return true.
		if ( SRFM_FORMS_POST_TYPE === get_current_screen()->post_type ) {
			return true;
		}
		return $use_block_editor;
	}

	/**
	 * Enable Gutenberg for SureForms associated post types.
	 *
	 * @since 0.0.10
	 */
	public function enable_gutenberg_for_sureforms() {
		/**
		 * Check if the classic editor is enabled from Classic Editor plugin settings or Divi settings.
		 */
		if ( 'block' === get_option( 'classic-editor-replace' ) || 'on' === get_option( 'et_enable_classic_editor' ) ) {
			return;
		}

		$srfm_post_types = apply_filters( 'srfm_enable_gutenberg_post_types', [ SRFM_FORMS_POST_TYPE ] );

		if ( in_array( get_current_screen()->post_type, $srfm_post_types, true ) ) {
			add_filter( 'use_block_editor_for_post_type', '__return_true', 110 );
			add_filter( 'gutenberg_can_edit_post_type', '__return_true', 110 );
		}
	}

	/**
	 * Sureforms editor header styles.
	 *
	 * @since 0.0.1
	 */
	public function enqueue_header_styles() {
		$current_screen = get_current_screen();
		$file_prefix    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name       = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';

		$css_uri = SRFM_URL . 'assets/css/' . $dir_name . '/';

		/* RTL */
		if ( is_rtl() ) {
			$file_prefix .= '-rtl';
		}

		if ( 'sureforms_form' === $current_screen->id ) {
			wp_enqueue_style( SRFM_SLUG . '-editor-header-styles', $css_uri . 'header-styles' . $file_prefix . '.css', [], SRFM_VER );
		}
	}

	/**
	 * Add menu page.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function add_menu_page() {
		$menu_slug = 'sureforms_menu';

		$logo = file_get_contents( plugin_dir_path( SRFM_FILE ) . 'images/icon.svg' );
		add_menu_page(
			__( 'SureForms', 'sureforms' ),
			__( 'SureForms', 'sureforms' ),
			self::$sureforms_page_default_capability,
			$menu_slug,
			static function () {
			},
			'data:image/svg+xml;base64,' . base64_encode( $logo ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			30
		);

		// Add the Dashboard Submenu.
		add_submenu_page(
			$menu_slug,
			__( 'Dashboard', 'sureforms' ),
			__( 'Dashboard', 'sureforms' ),
			self::$sureforms_page_default_capability,
			$menu_slug,
			[ $this, 'render_dashboard' ]
		);
	}

	/**
	 * Add Settings page.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function settings_page() {
		$callback = [ $this, 'settings_page_callback' ];
		add_submenu_page(
			'sureforms_menu',
			__( 'Settings', 'sureforms' ),
			__( 'Settings', 'sureforms' ),
			self::$sureforms_page_default_capability,
			'sureforms_form_settings',
			$callback
		);

		// Get the current submenu page.
		$submenu_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET['page'] does not provide nonce.

		if ( ! isset( $_GET['tab'] ) && 'sureforms_form_settings' === $submenu_page ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET['page'] does not provide nonce.
			wp_safe_redirect( admin_url( 'admin.php?page=sureforms_form_settings&tab=general-settings' ) );
			exit;
		}
	}

	/**
	 * Open to Upgrade to Pro submenu link in new tab.
	 *
	 * @return void
	 * @since 1.6.1
	 */
	public function add_upgrade_to_pro_target_attr() {
		?>
		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function () {
				// Upgrade link handler.
				// IMPORTANT: If this URL changes, also update it in the `add_upgrade_to_pro` function.
				const upgradeLink = document.querySelector('a[href*="https://sureforms.com/upgrade"]');
				if (upgradeLink) {
					upgradeLink.addEventListener('click', e => {
						e.preventDefault();
						window.open(upgradeLink.href, '_blank');
					});
				}
			});
		</script>
		<?php
	}

	/**
	 * Add Upgrade to pro menu item.
	 *
	 * @return void
	 * @since 1.6.1
	 */
	public function add_upgrade_to_pro() {
		// The url used here is used as a selector for css to style the upgrade to pro submenu.
		// If you are changing this url, please make sure to update the css as well.
		$upgrade_url = Helper::get_sureforms_website_url( 'upgrade', [ 'utm_medium' => 'submenu_link_upgrade' ] );

		add_submenu_page(
			'sureforms_menu',
			__( 'Upgrade', 'sureforms' ),
			__( 'Upgrade', 'sureforms' ),
			self::$sureforms_page_default_capability,
			$upgrade_url
		);
	}

	/**
	 * Add Quiz empty state submenu page for free users.
	 *
	 * @return void
	 * @since 2.7.0
	 */
	public function add_quiz_page() {
		add_submenu_page(
			'sureforms_menu',
			__( 'Quiz Entries', 'sureforms' ),
			__( 'Quizzes', 'sureforms' ),
			self::$sureforms_page_default_capability,
			'sureforms_quiz_entries',
			[ $this, 'render_quiz_empty_state' ],
			5
		);
	}

	/**
	 * Quiz empty state page callback.
	 *
	 * @return void
	 * @since 2.7.0
	 */
	public function render_quiz_empty_state() {
		?>
		<div id="srfm-quiz-entries-root" class="srfm-admin-wrapper"></div>
		<?php
	}

	/**
	 * Add Survey Reports promotional submenu page for free users.
	 *
	 * @return void
	 * @since 2.8.0
	 */
	public function add_survey_reports_page() {
		add_submenu_page(
			'sureforms_menu',
			__( 'Survey Reports', 'sureforms' ),
			__( 'Survey Reports', 'sureforms' ),
			self::$sureforms_page_default_capability,
			'sureforms_survey_reports',
			[ $this, 'render_survey_empty_state' ],
			6
		);
	}

	/**
	 * Survey empty state page callback.
	 *
	 * @return void
	 * @since 2.8.0
	 */
	public function render_survey_empty_state() {
		?>
		<div id="srfm-survey-empty-state-root" class="srfm-admin-wrapper"></div>
		<?php
	}

	/**
	 * Add Partial Entries promotional submenu page for free users.
	 *
	 * @return void
	 * @since 2.9.0
	 */
	public function add_partial_entries_page() {
		add_submenu_page(
			'sureforms_menu',
			__( 'Partial Entries', 'sureforms' ),
			__( 'Partial Entries', 'sureforms' ),
			self::$sureforms_page_default_capability,
			'sureforms_partial_entries',
			[ $this, 'render_partial_entries_empty_state' ],
			7
		);
	}

	/**
	 * Partial Entries empty state page callback.
	 *
	 * @return void
	 * @since 2.9.0
	 */
	public function render_partial_entries_empty_state() {
		?>
		<div id="srfm-partial-entries-empty-state-root" class="srfm-admin-wrapper"></div>
		<?php
	}

	/**
	 * Add SMTP promotional submenu page.
	 *
	 * @return void
	 * @since 1.7.1
	 */
	public function add_suremail_page() {
		add_submenu_page(
			'sureforms_menu',
			__( 'SMTP', 'sureforms' ),
			__( 'SMTP', 'sureforms' ),
			self::$sureforms_page_default_capability,
			'sureforms_smtp',
			[ $this, 'suremail_page_callback' ]
		);

		// Get the current submenu page.
		$submenu_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET['page'] does not provide nonce.

		// Check if SureMail is installed and active.
		if ( 'sureforms_smtp' === $submenu_page && file_exists( WP_PLUGIN_DIR . '/suremails/suremails.php' ) && is_plugin_active( 'suremails/suremails.php' ) ) {
			// Plugin is installed and active - redirect to SureMail dashboard.
			wp_safe_redirect( admin_url( 'options-general.php?page=suremail#/dashboard' ) );
			exit;
		}
	}

	/**
	 * SMTP promotional page callback.
	 *
	 * @return void
	 * @since 1.7.1
	 */
	public function suremail_page_callback() {
		?>
		<div id="srfm-suremail-container" class="srfm-admin-wrapper"></div>
		<?php
	}

	/**
	 * Render Admin Dashboard.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function render_dashboard() {
		?>
		<div id="srfm-dashboard-container" class="srfm-admin-wrapper"></div>
		<?php
	}

	/**
	 * Settings page callback.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function settings_page_callback() {
		?>
		<div id="srfm-settings-container" class="srfm-admin-wrapper"></div>
		<?php
	}

	/**
	 * Add Learn submenu page.
	 *
	 * @return void
	 * @since 2.5.2
	 */
	public function add_learn_page() {
		add_submenu_page(
			'sureforms_menu',
			__( 'Learn', 'sureforms' ),
			__( 'Learn', 'sureforms' ),
			self::$sureforms_page_default_capability,
			'sureforms_learn',
			[ $this, 'render_learn' ]
		);
	}

	/**
	 * Learn page callback.
	 *
	 * @return void
	 * @since 2.5.2
	 */
	public function render_learn() {
		?>
		<div id="srfm-learn-root" class="srfm-admin-wrapper"></div>
		<?php
	}

	/**
	 * Add new form menu item.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function add_new_form() {
		add_submenu_page(
			'sureforms_menu',
			__( 'Forms', 'sureforms' ),
			__( 'Forms', 'sureforms' ),
			self::$sureforms_page_default_capability,
			'sureforms_forms',
			[ $this, 'render_forms' ],
			1
		);
		add_submenu_page(
			'sureforms_menu',
			__( 'New Form', 'sureforms' ),
			__( 'New Form', 'sureforms' ),
			self::$sureforms_page_default_capability,
			'add-new-form',
			[ $this, 'add_new_form_callback' ],
			2
		);
		$entries_hook = add_submenu_page(
			'sureforms_menu',
			__( 'Entries', 'sureforms' ),
			__( 'Entries', 'sureforms' ),
			self::$sureforms_page_default_capability,
			SRFM_ENTRIES,
			[ $this, 'render_entries' ],
			3
		);

		add_submenu_page(
			'sureforms_menu',
			__( 'Payments', 'sureforms' ),
			__( 'Payments', 'sureforms' ),
			self::$sureforms_page_default_capability,
			SRFM_PAYMENTS,
			[ $this, 'render_payments' ],
			4
		);

		if ( $entries_hook ) {
			add_action( 'load-' . $entries_hook, [ $this, 'mark_entries_page_visit' ] );
		}
	}

	/**
	 * Payments page callback.
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function render_payments() {
		?>
		<div id="srfm-payments-react-container" class="srfm-admin-wrapper"></div>
		<?php
	}

	/**
	 * Add new form mentu item callback.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function add_new_form_callback() {
		?>
		<div id="srfm-add-new-form-container" class="srfm-admin-wrapper"></div>
		<?php
	}

	/**
	 * Forms page callback.
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function render_forms() {
		?>
		<div id="srfm-forms-root" class="srfm-admin-wrapper"></div>
		<?php
	}

	/**
	 * Entries page callback.
	 *
	 * @since 0.0.13
	 * @since 2.0.0 - Updated the entries UI and the function definition.
	 * @return void
	 */
	public function render_entries() {
		echo '<div id="srfm-entries-root"></div>';
	}

	/**
	 * Add notification badge to SureForms menu when there are new entries.
	 *
	 * @since 1.7.3
	 * @return void
	 */
	public function maybe_add_entries_badge() {
		if ( ! Helper::current_user_can() ) {
			return;
		}

		// If currently viewing the entries listing page, mark it as visited and skip the badge.
		if ( isset( $_GET['page'] ) && SRFM_ENTRIES === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only checking the page slug.
			$this->mark_entries_page_visit();
			return;
		}

		$srfm_options = get_option( 'srfm_options', [] );
		$last_visit   = isset( $srfm_options['entries_last_visited'] ) ? absint( $srfm_options['entries_last_visited'] ) : 0;
		$new_entries  = Entries::get_entries_count_after( $last_visit );

		if ( $new_entries <= 0 ) {
			return;
		}

		global $menu;
		foreach ( $menu as $index => $item ) {
			if ( isset( $item[2] ) && 'sureforms_menu' === $item[2] ) {
				ob_start();
				?>
				<span class="srfm-update-dot"></span>
				<?php
				$dot_html = ob_get_clean();
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Adding notifications for menu item.
				$menu[ $index ][0] .= $dot_html;
				break;
			}
		}

		global $submenu;
		if ( isset( $submenu['sureforms_menu'] ) ) {
			foreach ( $submenu['sureforms_menu'] as $index => $sub_item ) {
				if ( isset( $sub_item[2] ) && SRFM_ENTRIES === $sub_item[2] ) {
					ob_start();
					?>
					<span class="update-plugins count-<?php echo absint( $new_entries ); ?>">
						<span class="plugin-count"><?php echo absint( $new_entries ); ?></span>
					</span>
					<?php
					$badge_html = ob_get_clean();
					// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Adding notifications for submenu item.
					$submenu['sureforms_menu'][ $index ][0] .= $badge_html;
					break;
				}
			}
		}
	}

	/**
	 * Mark the user's visit to the entries page.
	 *
	 * @since 1.7.3
	 * @return void
	 */
	public function mark_entries_page_visit() {
		if ( Helper::current_user_can() ) {
			$srfm_options                         = get_option( 'srfm_options', [] );
			$srfm_options['entries_last_visited'] = time();
			\SRFM\Inc\Helper::update_admin_settings_option( 'srfm_options', $srfm_options );
		}
	}

	/**
	 * Adds a settings link to the plugin action links on the plugins page.
	 *
	 * @param array  $links An array of plugin action links.
	 * @param string $file The plugin file path.
	 * @return array The updated array of plugin action links.
	 * @since 0.0.1
	 */
	public function add_settings_link( $links, $file ) {
		if ( 'sureforms/sureforms.php' === $file ) {
			ob_start();
			?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=sureforms_form_settings&tab=general-settings' ) ); ?>">
				<?php echo esc_html__( 'Settings', 'sureforms' ); ?>
			</a>
			<?php
			$settings_link_html = ob_get_clean();
			$plugin_links       = apply_filters(
				'sureforms_plugin_action_links',
				[
					'sureforms_settings' => $settings_link_html,
				]
			);
			$links              = array_merge( $plugin_links, $links );
		}
		return $links;
	}

	/**
	 * Sureforms block editor styles.
	 *
	 * @since 0.0.1
	 */
	public function enqueue_styles() {
		$current_screen = get_current_screen();
		global $wp_version;

		$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';

		$css_uri        = SRFM_URL . 'assets/css/' . $dir_name . '/';
		$vendor_css_uri = SRFM_URL . 'assets/css/minified/deps/';

		/* RTL */
		if ( is_rtl() ) {
			$file_prefix .= '-rtl';
		}

		// Enqueue editor styles for post and page.
		if ( SRFM_FORMS_POST_TYPE === $current_screen->post_type ) {
			wp_enqueue_style( SRFM_SLUG . '-editor', $css_uri . 'backend/editor' . $file_prefix . '.css', [], SRFM_VER );
			wp_enqueue_style( SRFM_SLUG . '-backend-blocks', $css_uri . 'blocks/default/backend' . $file_prefix . '.css', [], SRFM_VER );
			wp_enqueue_style( SRFM_SLUG . '-intl', $vendor_css_uri . 'intl/intlTelInput-backend.min.css', [], SRFM_VER );
			wp_enqueue_style( SRFM_SLUG . '-common', $css_uri . 'common' . $file_prefix . '.css', [], SRFM_VER );
			wp_enqueue_style( SRFM_SLUG . '-reactQuill', $vendor_css_uri . 'quill/quill.snow.css', [], SRFM_VER );
			wp_add_inline_style( SRFM_SLUG . '-reactQuill', self::QUILL_1X_INLINE_CSS );
			wp_enqueue_style( SRFM_SLUG . '-single-form-modal', $css_uri . 'single-form-setting' . $file_prefix . '.css', [], SRFM_VER );

			// if version is equal to or lower than 6.6.2 then add compatibility css.
			if ( version_compare( $wp_version, '6.6.2', '<=' ) ) {
				$srfm_inline_css = '.srfm-settings-modal .srfm-setting-modal-container .components-toggle-control .components-base-control__help{
					margin-left: 4em;
				}';
				wp_add_inline_style( SRFM_SLUG . '-single-form-modal', $srfm_inline_css );
			}
		}

		wp_enqueue_style( SRFM_SLUG . '-form-selector', $css_uri . 'srfm-form-selector' . $file_prefix . '.css', [], SRFM_VER );
		wp_enqueue_style( SRFM_SLUG . '-common-editor', SRFM_URL . 'assets/build/common-editor.css', [], SRFM_VER, 'all' );
	}

	/**
	 * Get Breadcrumbs for current page.
	 *
	 * @since 0.0.1
	 * @return array Breadcrumbs Array.
	 */
	public function get_breadcrumbs_for_current_page() {
		global $post, $pagenow;
		$breadcrumbs = [];

		if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We don't need nonce verification here.
			$page_title    = get_admin_page_title();
			$breadcrumbs[] = [
				'title' => $page_title,
				'link'  => '',
			];
		} elseif ( $post && in_array( $pagenow, [ 'post.php', 'post-new.php', 'edit.php' ], true ) ) {
			$post_type_obj = get_post_type_object( get_post_type() );
			if ( $post_type_obj ) {
				$post_type_plural = $post_type_obj->labels->name;
				$breadcrumbs[]    = [
					'title' => $post_type_plural,
					'link'  => admin_url( 'edit.php?post_type=' . $post_type_obj->name ),
				];

				if ( 'edit.php' === $pagenow && ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We don't need nonce verification here.
					$breadcrumbs[ count( $breadcrumbs ) - 1 ]['link'] = '';
				} else {
					$breadcrumbs[] = [
						/* Translators: Post Title. */
						'title' => sprintf( __( 'Edit %1$s', 'sureforms' ), get_the_title() ),
						'link'  => get_edit_post_link( $post->ID ),
					];
				}
			}
		} else {
			$current_screen = get_current_screen();
			if ( $current_screen && 'sureforms_form' === $current_screen->post_type ) {
				$breadcrumbs[] = [
					'title' => 'Forms',
					'link'  => '',
				];
			} else {
				$breadcrumbs[] = [
					'title' => '',
					'link'  => '',
				];
			}
		}

		return $breadcrumbs;
	}

	/**
	 * Enqueue Admin Scripts.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function enqueue_scripts() {
		$current_screen = get_current_screen();
		global $wp_version;

		$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';
		$css_uri     = SRFM_URL . 'assets/css/' . $dir_name . '/';
		$is_rtl      = is_rtl();
		$rtl         = $is_rtl ? '-rtl' : '';

		/**
		 * List of the handles in which we need to add translation compatibility.
		 */
		$script_translations_handlers = [];
		$onboarding_instance          = Onboarding::get_instance();
		$current_user                 = wp_get_current_user();

		$localization_data = [
			'site_url'                     => get_site_url(),
			'current_user_login'           => $current_user->user_login ?? '',
			'website_lead_details'         => [
				'first_name' => $current_user->first_name ?? '',
				'last_name'  => $current_user->last_name ?? '',
				'email'      => $current_user->user_email ?? '',
			],
			'breadcrumbs'                  => $this->get_breadcrumbs_for_current_page(),
			'sureforms_dashboard_url'      => admin_url( '/admin.php?page=sureforms_menu' ),
			'plugin_version'               => SRFM_VER,
			'global_settings_nonce'        => Helper::current_user_can() ? wp_create_nonce( 'wp_rest' ) : '',
			'is_pro_active'                => Helper::has_pro(),
			'is_first_form_created'        => self::is_first_form_created(),
			'check_three_days_threshold'   => self::check_first_form_creation_threshold(),
			'check_eight_days_threshold'   => self::check_first_form_creation_threshold( 8 ),
			'pro_plugin_version'           => Helper::has_pro() ? SRFM_PRO_VER : '',
			'pro_plugin_name'              => Helper::has_pro() && defined( 'SRFM_PRO_PRODUCT' ) ? SRFM_PRO_PRODUCT : 'SureForms Pro',
			'sureforms_pricing_page'       => Helper::get_sureforms_website_url( 'pricing' ),
			'field_spacing_vars'           => Helper::get_css_vars(),
			'is_ver_lower_than_6_7'        => version_compare( $wp_version, '6.6.2', '<=' ),
			'integrations'                 => Helper::sureforms_get_integration(),
			'hide_promotions'              => Helper::hide_promotions(),
			// Null makes the dashboard's ExtendTab render nothing.
			'rotating_plugin_banner'       => Helper::hide_promotions() ? null : Helper::get_rotating_plugin_banner(),
			'ajax_url'                     => admin_url( 'admin-ajax.php' ),
			'client_logs_nonce'            => Helper::current_user_can() ? wp_create_nonce( 'srfm_client_logs' ) : '',
			'action_items'                 => $this->get_action_items(),
			'notice_response_nonce'        => Helper::current_user_can() ? wp_create_nonce( 'srfm_notice_response' ) : '',
			'dismiss_action_item_nonce'    => Helper::current_user_can() ? wp_create_nonce( 'srfm_dismiss_action_item' ) : '',
			'sf_plugin_manager_nonce'      => wp_create_nonce( 'sf_plugin_manager_nonce' ),
			'plugin_installer_nonce'       => wp_create_nonce( 'updates' ),
			'plugin_activating_text'       => __( 'Activating...', 'sureforms' ),
			'plugin_activated_text'        => __( 'Activated', 'sureforms' ),
			'plugin_activate_text'         => __( 'Activate', 'sureforms' ),
			'plugin_installing_text'       => __( 'Installing...', 'sureforms' ),
			'plugin_installed_text'        => __( 'Installed', 'sureforms' ),
			'privacy_policy_url'           => Helper::get_sureforms_website_url( 'privacy-policy/' ),
			'is_rtl'                       => $is_rtl,
			'onboarding_completed'         => method_exists( $onboarding_instance, 'get_onboarding_status' ) ? $onboarding_instance->get_onboarding_status() : false,
			// Read by the onboarding cache-conflict step: the name decides whether the
			// step renders, the URL is where "View full guide" points.
			'caching_plugin'               => Helper::get_active_caching_plugin(),
			'caching_plugin_doc_url'       => Helper::get_caching_plugin_doc_url( 'onboarding' ),
			'migration_banner_dismissed'   => method_exists( $onboarding_instance, 'is_migration_banner_dismissed' ) ? $onboarding_instance->is_migration_banner_dismissed() : false,
			'migration_settings_url'       => admin_url( 'admin.php?page=sureforms_form_settings&tab=migration-settings' ),
			'onboarding_redirect'          => isset( $_GET['srfm-activation-redirect'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is not required for the activation redirection.
			'pointer_nonce'                => wp_create_nonce( 'sureforms_pointer_action' ),
			'general_settings_url'         => admin_url( '/options-general.php' ),
			'additional_header_nav_items'  => [],
			// Smart tags for the Global Defaults email notification fields.
			// srfm_block_data is only available in the block editor; these keys
			// make the same data accessible on the settings page via srfm_admin.
			'smart_tags_array'             => Smart_Tags::smart_tag_list(),
			'smart_tags_array_email'       => Smart_Tags::email_smart_tag_list(),
			// Default confirmation message HTML (icon + heading + text) used as
			// the initial React state before the settings API response arrives.
			'default_confirmation_message' => Global_Settings::get_default_confirmation_message(),
			'payments'                     => apply_filters(
				'srfm_admin_localize_payments_data',
				[
					'stripe_connected'        => Stripe_Helper::is_stripe_connected(),
					'stripe_mode'             => Stripe_Helper::get_stripe_mode(),
					'stripe_connect_url'      => Stripe_Helper::get_stripe_settings_url(),
					'currencies_data'         => Payment_Helper::get_all_currencies_data(),
					'zero_decimal_currencies' => Payment_Helper::get_zero_decimal_currencies(),
					'webhook_url'             => Stripe_Helper::get_webhook_url(),
					'webhook_test_connected'  => Stripe_Helper::is_webhook_configured( 'test', true ),
					'webhook_live_connected'  => Stripe_Helper::is_webhook_configured( 'live', true ),
					'is_transaction_present'  => Stripe_Helper::is_transaction_present(),
					'payment_currency'        => Payment_Helper::get_currency(),
					'currency_sign_position'  => Payment_Helper::get_currency_sign_position(),
				]
			),
			'mcp_adapter_status'           => file_exists( WP_PLUGIN_DIR . '/mcp-adapter/mcp-adapter.php' )
				? ( is_plugin_active( 'mcp-adapter/mcp-adapter.php' ) ? 'active' : 'installed' )
				: 'not_installed',
			'mcp_endpoint_url'             => esc_url_raw( rest_url( 'sureforms/v1/mcp' ) ),
		];

		$is_screen_sureforms_menu              = Helper::validate_request_context( 'sureforms_menu', 'page' );
		$is_screen_add_new_form                = Helper::validate_request_context( 'add-new-form', 'page' );
		$is_screen_sureforms_forms             = Helper::validate_request_context( 'sureforms_forms', 'page' );
		$is_screen_sureforms_form_settings     = Helper::validate_request_context( 'sureforms_form_settings', 'page' );
		$is_screen_sureforms_payments          = Helper::validate_request_context( 'sureforms_payments', 'page' );
		$is_screen_sureforms_entries           = Helper::validate_request_context( SRFM_ENTRIES, 'page' );
		$is_screen_sureforms_learn             = Helper::validate_request_context( 'sureforms_learn', 'page' );
		$is_screen_quiz_empty_state            = Helper::validate_request_context( 'sureforms_quiz_entries', 'page' );
		$is_screen_survey_empty_state          = Helper::validate_request_context( 'sureforms_survey_reports', 'page' );
		$is_screen_partial_entries_empty_state = Helper::validate_request_context( 'sureforms_partial_entries', 'page' );
		$is_post_type_sureforms_form           = SRFM_FORMS_POST_TYPE === $current_screen->post_type;

		/**
		 * Check if the current screen is the SureForms Menu and AI Auth Email is present then we will add user type as registered.
		 * Compatibility with existing UI code that checks for this condition.
		 */
		if ( $is_screen_sureforms_menu ) {
			// If email is stored send the user type as registered else non-registered.
			$localization_data['srfm_ai_details'] = [
				'type' => ! empty( get_option( 'srfm_ai_auth_user_email' ) ) ? 'registered' : 'non-registered',
			];
		}

		// Add the Quizzes and Survey Reports nav items when pro is not active.
		if ( ! Helper::has_pro() ) {
			$localization_data['additional_header_nav_items'][] = [
				'slug' => 'sureforms_quiz_entries',
				'text' => __( 'Quizzes', 'sureforms' ),
				'link' => admin_url( 'admin.php?page=sureforms_quiz_entries' ),
			];
			$localization_data['additional_header_nav_items'][] = [
				'slug' => 'sureforms_survey_reports',
				'text' => __( 'Survey Reports', 'sureforms' ),
				'link' => admin_url( 'admin.php?page=sureforms_survey_reports' ),
			];
			$localization_data['additional_header_nav_items'][] = [
				'slug' => 'sureforms_partial_entries',
				'text' => __( 'Partial Entries', 'sureforms' ),
				'link' => admin_url( 'admin.php?page=sureforms_partial_entries' ),
			];
		}

		$is_sureforms_screen = $is_screen_sureforms_menu || $is_post_type_sureforms_form || $is_screen_add_new_form || $is_screen_sureforms_forms || $is_screen_sureforms_form_settings || $is_screen_sureforms_entries || $is_screen_sureforms_payments || $is_screen_sureforms_learn || $is_screen_quiz_empty_state || $is_screen_survey_empty_state || $is_screen_partial_entries_empty_state;

		/**
		 * Filter to allow extending the SureForms dashboard screen check.
		 *
		 * @since 2.6.0
		 *
		 * @param bool $is_sureforms_screen Whether the current screen is a SureForms dashboard screen.
		 */
		$is_sureforms_screen = apply_filters( 'srfm_is_dashboard_screen', $is_sureforms_screen );

		if ( $is_sureforms_screen ) {
			$asset_handle = '-dashboard';

			wp_enqueue_style( SRFM_SLUG . $asset_handle . '-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap', [], SRFM_VER );

			$script_asset_path = SRFM_DIR . 'assets/build/dashboard.asset.php';
			$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: [
				'dependencies' => [],
				'version'      => SRFM_VER,
			];
			wp_enqueue_script( SRFM_SLUG . $asset_handle, SRFM_URL . 'assets/build/dashboard.js', $script_info['dependencies'], SRFM_VER, true );

			wp_localize_script( SRFM_SLUG . $asset_handle, 'scIcons', [ 'path' => SRFM_URL . 'assets/build/icon-assets' ] );

			$script_translations_handlers[] = SRFM_SLUG . $asset_handle;

			if ( class_exists( 'SRFM_PRO\Admin\Licensing' ) ) {
				$license_active                         = \SRFM_PRO\Admin\Licensing::is_license_active();
				$localization_data['is_license_active'] = $license_active;

				// Updating current licensing status.
				$srfm_pro_license_status = get_option( 'srfm_pro_license_status', '' );
				$current_license_status  = $license_active ? 'licensed' : 'unlicensed';
				if ( $current_license_status !== $srfm_pro_license_status ) {
					update_option( 'srfm_pro_license_status', $current_license_status );
				}
			}

			$localization_data['security_settings_url']    = admin_url( '/admin.php?page=sureforms_form_settings&tab=security-settings&subpage=recaptcha' );
			$localization_data['integration_settings_url'] = admin_url( '/admin.php?page=sureforms_form_settings&tab=integration-settings' );
			wp_localize_script(
				SRFM_SLUG . $asset_handle,
				SRFM_SLUG . '_admin',
				apply_filters(
					SRFM_SLUG . '_admin_filter',
					$localization_data
				)
			);
			wp_enqueue_style( SRFM_SLUG . '-dashboard', SRFM_URL . 'assets/build/dashboard.css', [], SRFM_VER, 'all' );
		}

		if ( $is_screen_sureforms_form_settings || $is_screen_sureforms_forms ) {
			wp_enqueue_style( SRFM_SLUG . '-settings', $css_uri . 'backend/settings' . $file_prefix . $rtl . '.css', [], SRFM_VER );
		}

		// Enqueue styles for the entries page.
		if ( $is_screen_sureforms_entries ) {
			$asset_handle = '-entries';
			wp_enqueue_script( SRFM_SLUG . $asset_handle, SRFM_URL . 'assets/build/entries.js', $script_info['dependencies'], SRFM_VER, true );

			wp_localize_script(
				SRFM_SLUG . $asset_handle,
				SRFM_SLUG . '_admin',
				apply_filters(
					SRFM_SLUG . '_admin_filter',
					$localization_data
				)
			);
			$script_translations_handlers[] = SRFM_SLUG . $asset_handle;
		}

		// Enqueue scripts for the learn page.
		if ( $is_screen_sureforms_learn ) {
			$asset_handle = '-learn';
			wp_enqueue_script( SRFM_SLUG . $asset_handle, SRFM_URL . 'assets/build/learn.js', $script_info['dependencies'], SRFM_VER, true );

			wp_localize_script(
				SRFM_SLUG . $asset_handle,
				SRFM_SLUG . '_admin',
				apply_filters(
					SRFM_SLUG . '_admin_filter',
					$localization_data
				)
			);
			$script_translations_handlers[] = SRFM_SLUG . $asset_handle;
		}

		// Enqueue scripts for the forms page.
		if ( $is_screen_sureforms_forms ) {
			$asset_handle = '-forms';

			$script_asset_path = SRFM_DIR . 'assets/build/forms.asset.php';
			$script_info       = file_exists( $script_asset_path )
				? include $script_asset_path
				: [
					'dependencies' => [],
					'version'      => SRFM_VER,
				];

			wp_enqueue_script( SRFM_SLUG . $asset_handle, SRFM_URL . 'assets/build/forms.js', $script_info['dependencies'], SRFM_VER, true );
			wp_localize_script(
				SRFM_SLUG . $asset_handle,
				SRFM_SLUG . '_admin',
				apply_filters(
					SRFM_SLUG . '_admin_filter',
					$localization_data
				)
			);
			wp_enqueue_style( SRFM_SLUG . $asset_handle, SRFM_URL . 'assets/build/forms.css', [], SRFM_VER, 'all' );

			$script_translations_handlers[] = SRFM_SLUG . $asset_handle;
		}

		// Enqueue scripts for the SureMail promotional page.
		$is_screen_sureforms_smtp = Helper::validate_request_context( 'sureforms_smtp', 'page' );
		if ( $is_screen_sureforms_smtp ) {
			$asset_handle = 'suremail';

			$script_asset_path = SRFM_DIR . 'assets/build/' . $asset_handle . '.asset.php';
			$script_info       = file_exists( $script_asset_path )
				? include $script_asset_path
				: [
					'dependencies' => [],
					'version'      => SRFM_VER,
				];

			wp_enqueue_script( SRFM_SLUG . '-suremail', SRFM_URL . 'assets/build/' . $asset_handle . '.js', $script_info['dependencies'], SRFM_VER, true );
			wp_enqueue_style( SRFM_SLUG . '-suremail', SRFM_URL . 'assets/build/suremail.css', [], SRFM_VER, 'all' );

			// Localize script for SureMail page.
			$suremail_localization_data = [
				'ajax_url'               => admin_url( 'admin-ajax.php' ),
				'admin_url'              => admin_url(),
				'suremail_url'           => 'https://sureforms.com/suremail/',
				'plugin_installer_nonce' => wp_create_nonce( 'updates' ),
				'sfPluginManagerNonce'   => wp_create_nonce( 'sf_plugin_manager_nonce' ),
				'suremail_status'        => file_exists( WP_PLUGIN_DIR . '/suremails/suremails.php' )
					? ( is_plugin_active( 'suremails/suremails.php' ) ? 'active' : 'installed' )
					: 'not_installed',
			];

			wp_localize_script(
				SRFM_SLUG . '-suremail',
				SRFM_SLUG . '_admin',
				apply_filters(
					SRFM_SLUG . '_suremail_admin_filter',
					$suremail_localization_data
				)
			);

			$script_translations_handlers[] = SRFM_SLUG . '-suremail';
		}

		// Enqueue scripts for the Quiz empty state page (free users only).
		if ( $is_screen_quiz_empty_state && ! Helper::has_pro() ) {
			$asset_handle = 'quizEmptyState';

			$script_asset_path = SRFM_DIR . 'assets/build/' . $asset_handle . '.asset.php';
			$script_info       = file_exists( $script_asset_path )
				? include $script_asset_path
				: [
					'dependencies' => [],
					'version'      => SRFM_VER,
				];

			wp_enqueue_script( SRFM_SLUG . '-quiz-empty-state', SRFM_URL . 'assets/build/' . $asset_handle . '.js', $script_info['dependencies'], SRFM_VER, true );
			wp_enqueue_style( SRFM_SLUG . '-quiz-empty-state', SRFM_URL . 'assets/build/' . $asset_handle . '.css', [], SRFM_VER, 'all' );

			$script_translations_handlers[] = SRFM_SLUG . '-quiz-empty-state';
		}

		// Enqueue scripts for the Survey Reports empty state page (free users only).
		if ( $is_screen_survey_empty_state && ! Helper::has_pro() ) {
			$asset_handle = 'surveyEmptyState';

			$script_asset_path = SRFM_DIR . 'assets/build/' . $asset_handle . '.asset.php';
			$script_info       = file_exists( $script_asset_path )
				? include $script_asset_path
				: [
					'dependencies' => [],
					'version'      => SRFM_VER,
				];

			wp_enqueue_script( SRFM_SLUG . '-survey-empty-state', SRFM_URL . 'assets/build/' . $asset_handle . '.js', $script_info['dependencies'], SRFM_VER, true );
			wp_enqueue_style( SRFM_SLUG . '-survey-empty-state', SRFM_URL . 'assets/build/' . $asset_handle . '.css', [], SRFM_VER, 'all' );

			$script_translations_handlers[] = SRFM_SLUG . '-survey-empty-state';
		}

		// Enqueue scripts for the Partial Entries empty state page (free users only).
		if ( $is_screen_partial_entries_empty_state && ! Helper::has_pro() ) {
			$asset_handle = 'partialEntriesEmptyState';

			$script_asset_path = SRFM_DIR . 'assets/build/' . $asset_handle . '.asset.php';
			$script_info       = file_exists( $script_asset_path )
				? include $script_asset_path
				: [
					'dependencies' => [],
					'version'      => SRFM_VER,
				];

			wp_enqueue_script( SRFM_SLUG . '-partial-entries-empty-state', SRFM_URL . 'assets/build/' . $asset_handle . '.js', $script_info['dependencies'], SRFM_VER, true );
			wp_enqueue_style( SRFM_SLUG . '-partial-entries-empty-state', SRFM_URL . 'assets/build/' . $asset_handle . '.css', [], SRFM_VER, 'all' );

			$script_translations_handlers[] = SRFM_SLUG . '-partial-entries-empty-state';
		}

		// Admin Submenu Styles.
		wp_enqueue_style( SRFM_SLUG . '-admin', $css_uri . 'backend/admin' . $file_prefix . $rtl . '.css', [], SRFM_VER );

		if ( $is_screen_sureforms_form_settings ) {
			$asset_handle = 'settings';

			$script_asset_path = SRFM_DIR . 'assets/build/' . $asset_handle . '.asset.php';
			$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: [
				'dependencies' => [],
				'version'      => SRFM_VER,
			];

			wp_enqueue_script( SRFM_SLUG . '-settings', SRFM_URL . 'assets/build/' . $asset_handle . '.js', $script_info['dependencies'], SRFM_VER, true );
			wp_localize_script(
				SRFM_SLUG . '-settings',
				SRFM_SLUG . '_admin',
				apply_filters(
					SRFM_SLUG . '_admin_filter',
					$localization_data
				)
			);

			// Enqueue Tailwind and Quill editor styles for the settings page.
			wp_enqueue_style( SRFM_SLUG . '-settings-build', SRFM_URL . 'assets/build/settings.css', [], SRFM_VER, 'all' );
			wp_enqueue_style( SRFM_SLUG . '-reactQuill', SRFM_URL . 'assets/css/minified/deps/quill/quill.snow.css', [], SRFM_VER );
			wp_add_inline_style( SRFM_SLUG . '-reactQuill', self::QUILL_1X_INLINE_CSS );

			$script_translations_handlers[] = SRFM_SLUG . '-settings';
		}

		if ( $is_screen_add_new_form ) {
			wp_enqueue_style( SRFM_SLUG . '-template-picker', $css_uri . 'template-picker' . $file_prefix . $rtl . '.css', [], SRFM_VER );

			$sureforms_admin = 'templatePicker';

			$script_asset_path = SRFM_DIR . 'assets/build/' . $sureforms_admin . '.asset.php';
			$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: [
				'dependencies' => [],
				'version'      => SRFM_VER,
			];
			wp_enqueue_script( SRFM_SLUG . '-template-picker', SRFM_URL . 'assets/build/' . $sureforms_admin . '.js', $script_info['dependencies'], SRFM_VER, true );

			wp_localize_script(
				SRFM_SLUG . '-template-picker',
				SRFM_SLUG . '_admin',
				[
					'site_url'                     => get_site_url(),
					'plugin_url'                   => SRFM_URL,
					'admin_url'                    => admin_url( 'admin.php' ),
					'new_template_picker_base_url' => admin_url( 'post-new.php?post_type=sureforms_form' ),
					'capability'                   => Helper::current_user_can(),
					'template_picker_nonce'        => Helper::current_user_can() ? wp_create_nonce( 'wp_rest' ) : '',
					'is_pro_active'                => Helper::has_pro(),
					'srfm_ai_usage_details'        => AI_Helper::get_current_usage_details(),
					'is_pro_license_active'        => AI_Helper::is_pro_license_active(),
					'srfm_ai_auth_user_email'      => get_option( 'srfm_ai_auth_user_email' ),
					'pricing_page_url'             => Helper::get_sureforms_website_url( 'pricing' ),
					'licensing_nonce'              => wp_create_nonce( 'srfm_pro_licensing_nonce' ),
				]
			);

			$script_translations_handlers[] = SRFM_SLUG . '-template-picker';
		}
		// Quick action sidebar.
		$default_allowed_quick_sidebar_blocks = apply_filters(
			'srfm_quick_sidebar_allowed_blocks',
			[
				'srfm/input',
				'srfm/email',
				'srfm/textarea',
				'srfm/checkbox',
				'srfm/number',
				'srfm/inline-button',
				'srfm/advanced-heading',
				'srfm/payment',
			]
		);
		if ( ! is_array( $default_allowed_quick_sidebar_blocks ) ) {
			$default_allowed_quick_sidebar_blocks = [];
		}

		$srfm_enable_quick_action_sidebar = get_option( 'srfm_enable_quick_action_sidebar' );
		if ( ! $srfm_enable_quick_action_sidebar ) {
			$srfm_enable_quick_action_sidebar = 'disabled';
		}
		$quick_sidebar_allowed_blocks = get_option( 'srfm_quick_sidebar_allowed_blocks' );
		$quick_sidebar_allowed_blocks = ! empty( $quick_sidebar_allowed_blocks ) && is_array( $quick_sidebar_allowed_blocks ) ? $quick_sidebar_allowed_blocks : $default_allowed_quick_sidebar_blocks;
		$srfm_ajax_nonce              = wp_create_nonce( 'srfm_ajax_nonce' );

		if ( Helper::is_sureforms_admin_page() ) {
			wp_enqueue_script( SRFM_SLUG . '-quick-action-siderbar', SRFM_URL . 'assets/build/quickActionSidebar.js', [], SRFM_VER, true );
			wp_localize_script(
				SRFM_SLUG . '-quick-action-siderbar',
				SRFM_SLUG . '_quick_sidebar_blocks',
				[
					'allowed_blocks'                   => $quick_sidebar_allowed_blocks,
					'srfm_enable_quick_action_sidebar' => $srfm_enable_quick_action_sidebar,
					'srfm_ajax_nonce'                  => $srfm_ajax_nonce,
					'srfm_ajax_url'                    => admin_url( 'admin-ajax.php' ),
				]
			);

			$script_translations_handlers[] = SRFM_SLUG . '-quick-action-siderbar';
		}

		/**
		 * Enqueuing SureTriggers Integration script.
		 * This script loads suretriggers iframe in Intergations tab.
		 */
		if ( $is_post_type_sureforms_form ) {
			wp_enqueue_script( SRFM_SLUG . '-suretriggers-integration', SRFM_SURETRIGGERS_INTEGRATION_BASE_URL . 'js/v2/embed.js', [], SRFM_VER, true );
		}

		// Check $script_translations_handlers is not empty before calling the function.
		if ( ! empty( $script_translations_handlers ) ) {
			// Remove duplicates values from the array.
			$script_translations_handlers = array_unique( $script_translations_handlers );

			foreach ( $script_translations_handlers as $script_handle ) {
				Helper::register_script_translations( $script_handle );
			}
		}
	}

	/**
	 * Form Template Picker Admin Body Classes
	 * WordPress sometimes translates class names in the admin body tag, which can result in
	 * incorrect or missing class names when rendering the admin pages. This function ensures
	 * that essential class names are manually added to the body tag to maintain proper functionality.
	 *
	 * @since 0.0.1
	 * @param string $classes Space separated class string.
	 */
	public function admin_template_picker_body_class( $classes = '' ) {
		// Define an associative array of class names and their corresponding conditions.
		// Each condition checks whether a specific request context matches.
		$srfm_classes = [
			'sureforms_page_sureforms_entries'       => Helper::validate_request_context( SRFM_ENTRIES, 'page' ),
			'sureforms_page_sureforms_form_settings' => Helper::validate_request_context( 'sureforms_form_settings', 'page' ),
			'srfm-template-picker'                   => Helper::validate_request_context( 'add-new-form', 'page' ),
		];

		$add_srfm_classes = '';

		// Loop through the defined classes and conditions.
		foreach ( $srfm_classes as $class => $condition ) {
			// Check if the condition evaluates to true.
			if ( $condition ) {
				// Append the class to the existing classes string, followed by a space.
				$add_srfm_classes .= empty( $add_srfm_classes ) ? $class : ' ' . $class;
			}
		}

		// Append the new classes to the existing classes string.
		if ( ! empty( $add_srfm_classes ) ) {
			$classes .= ' ' . $add_srfm_classes;
		}

		// Return the updated list of classes.
		return $classes;
	}

	/**
	 * Disable spectra's quick action bar in sureforms CPT.
	 *
	 * @param string $status current status of the quick action bar.
	 * @since 0.0.2
	 * @return string
	 */
	public function restrict_spectra_quick_action_bar( $status ) {
		$screen = get_current_screen();
		if ( 'disabled' !== $status && isset( $screen->id ) && 'sureforms_form' === $screen->id ) {
			$status = 'disabled';
		}

		return $status;
	}

	/**
	 * Register Pro compatibility notices early for React pages.
	 *
	 * This method runs on admin_init (priority 5) to ensure notices are
	 * registered BEFORE admin_enqueue_scripts, so they're available when
	 * wp_localize_script runs.
	 *
	 * Hooked - admin_init (priority 5)
	 *
	 * @return void
	 * @since 2.5.0
	 */
	public function register_pro_compatibility_notices() {
		// Early exit if Pro is not active, user lacks permissions, or Notice_Manager is unavailable.
		if ( ! Helper::has_pro() || ! Helper::current_user_can() || ! class_exists( 'SRFM\Admin\Notice_Manager' ) ) {
			return;
		}

		// Register version outdated notice for React pages.
		if ( ! version_compare( SRFM_PRO_VER, SRFM_PRO_RECOMMENDED_VER, '>=' ) ) {
			$pro_plugin_name        = defined( 'SRFM_PRO_PRODUCT' ) ? SRFM_PRO_PRODUCT : 'SureForms Pro';
			$react_outdated_message = sprintf(
				// translators: %1$s: SureForms version, %2$s: SureForms Pro Plugin Name, %3$s: SureForms Pro Version.
				esc_html__( 'SureForms %1$s requires minimum %2$s %3$s to work properly. Please update to the latest version.', 'sureforms' ),
				esc_html( SRFM_VER ),
				esc_html( $pro_plugin_name ),
				esc_html( SRFM_PRO_RECOMMENDED_VER )
			);

			\SRFM\Admin\Notice_Manager::register_notice(
				[
					'id'      => 'sureforms-pro-version-outdated',
					'variant' => 'warning',
					'message' => $react_outdated_message,
					'actions' => [
						[
							'label'   => esc_html__( 'Update Now', 'sureforms' ),
							'url'     => admin_url( 'update-core.php' ),
							'variant' => 'primary',
						],
					],
					'pages'   => [ 'all' ],
				]
			);
		}
	}

	/**
	 * Register the React notice when the entries table is missing.
	 *
	 * Hooked - admin_init, priority 5.
	 *
	 * Priority 5 is load-bearing: Notice_Manager hands notices to the front end
	 * through the `srfm_admin_filter` applied during admin_enqueue_scripts, so
	 * anything registering later never reaches the page.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public function register_database_repair_notice() {
		// admin_init also fires on admin-ajax.php. Nothing there renders a notice, so
		// skip the work rather than reading a transient on every AJAX request.
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! Helper::current_user_can() ) {
			return;
		}

		if ( ! class_exists( 'SRFM\Admin\Notice_Manager' ) ) {
			return;
		}

		// A just-completed repair reports its outcome instead of the warning.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag; the repair itself is nonce-checked in handle_database_repair().
		$result = isset( $_GET['srfm_db_repair'] ) ? sanitize_key( wp_unslash( $_GET['srfm_db_repair'] ) ) : '';

		if ( 'done' === $result ) {
			Notice_Manager::register_notice(
				[
					'id'      => 'srfm-database-repaired',
					'variant' => 'success',
					'message' => __( 'Your SureForms database is up to date. New form entries will be saved as usual.', 'sureforms' ),
					'pages'   => [ 'all' ],
				]
			);
			return;
		}

		if ( 'failed' === $result ) {
			Notice_Manager::register_notice(
				[
					'id'      => 'srfm-database-repair-failed',
					// Still a warning, not an error: a host that does not allow
					// SureForms to create tables is not the user's mistake.
					'variant' => 'warning',
					'message' => __( 'SureForms could not finish updating the database. Your hosting may not allow SureForms to create database tables — please contact your hosting provider or SureForms support.', 'sureforms' ),
					'actions' => [
						[
							'label' => __( 'Contact support', 'sureforms' ),
							'url'   => 'https://sureforms.com/contact/',
						],
					],
					'pages'   => [ 'all' ],
				]
			);
			return;
		}

		if ( ! Register::is_entries_table_missing() ) {
			return;
		}

		$this->track_database_notice_impression();

		Notice_Manager::register_notice(
			[
				'id'      => 'srfm-database-maintenance',
				'variant' => 'warning',
				'title'   => __( 'Database update needed', 'sureforms' ),
				// Plain text only. AdminNotice.js renders this as a React child, so
				// any markup here would show up as literal characters.
				'message' => $this->get_database_notice_message(),
				'actions' => [
					[
						'label'  => __( 'Fix now', 'sureforms' ),
						// Opaque identifier, resolved to a handler in AdminNotice.js.
						// Deliberately not a URL or endpoint: the server never tells
						// the browser which address to call.
						'action' => 'repair-entries-table',
						'url'    => $this->get_database_repair_url(),
					],
				],
				'pages'   => [ 'all' ],
			]
		);
	}

	/**
	 * Render the classic warning on the WordPress dashboard.
	 *
	 * Hooked - admin_notices.
	 *
	 * Scoped to index.php on purpose. The React notice already covers the SureForms
	 * screens, so leaving this one admin-wide would stack two warnings on the same
	 * page and nag on every screen in wp-admin.
	 *
	 * Registered as [ $this, 'method' ] rather than a closure because
	 * suppress_foreign_admin_notices() strips any callback it cannot attribute to a
	 * SureForms class — a closure here would be silently removed.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public function render_database_repair_notice() {
		if ( ! Helper::current_user_can() ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || 'dashboard' !== $screen->base ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag; the repair itself is nonce-checked in handle_database_repair().
		$result = isset( $_GET['srfm_db_repair'] ) ? sanitize_key( wp_unslash( $_GET['srfm_db_repair'] ) ) : '';

		if ( 'done' === $result ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Your SureForms database is up to date. New form entries will be saved as usual.', 'sureforms' ); ?></p>
			</div>
			<?php
			return;
		}

		if ( 'failed' === $result ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php esc_html_e( 'SureForms could not finish updating the database. Your hosting may not allow SureForms to create database tables — please contact your hosting provider or SureForms support.', 'sureforms' ); ?></p>
			</div>
			<?php
			return;
		}

		if ( ! Register::is_entries_table_missing() ) {
			return;
		}

		$this->track_database_notice_impression();
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'SureForms — database update needed', 'sureforms' ); ?></strong>
			</p>
			<p><?php echo esc_html( $this->get_database_notice_message() ); ?></p>
			<p>
				<a href="<?php echo esc_url( $this->get_database_repair_url() ); ?>" class="button button-primary">
					<?php esc_html_e( 'Update database', 'sureforms' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Repair the entries table, then redirect back with the outcome.
	 *
	 * Hooked - admin_post_srfm_repair_entries_table.
	 *
	 * A nonce-protected GET that changes state matches how core's own plugin
	 * activate / deactivate / delete links work.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public function handle_database_repair() {
		if ( ! Helper::current_user_can() ) {
			wp_die( esc_html__( 'You do not have permission to update the database.', 'sureforms' ), 403 );
		}

		check_admin_referer( 'srfm_repair_entries_table' );

		$repaired = $this->do_database_repair();
		$referer  = wp_get_referer();

		wp_safe_redirect(
			add_query_arg(
				'srfm_db_repair',
				$repaired ? 'done' : 'failed',
				$referer ? $referer : admin_url()
			)
		);
		exit;
	}

	/**
	 * Repair the entries table and record what happened.
	 *
	 * The single place the repair is performed and counted, shared by the
	 * admin-post handler and the REST endpoint. One user action reaches exactly one
	 * of those, so the click counter cannot double-count across the two surfaces.
	 *
	 * @since 2.12.6
	 * @return bool True when the table exists afterwards.
	 */
	public function do_database_repair() {
		// Cumulative counter, so $force = true: each new count is a new value and is
		// re-sent, while an identical repeat short-circuits inside track().
		$attempts = Helper::get_integer_value( Helper::get_srfm_option( 'db_repair_attempts', 0 ) ) + 1;
		Helper::update_srfm_option( 'db_repair_attempts', $attempts );

		// Event name is the `database_error` => `fix_now` entry in the $valid
		// allowlist in handle_notice_response(). Kept in sync by hand; that array is
		// where the team looks notice event names up.
		Analytics::events()->track( 'database_error_notice_cta', (string) $attempts, [], true );

		$repaired = Register::repair_entries_table();

		// The failure case is the more valuable signal: it means the host refuses to
		// let SureForms create tables, which no amount of retrying will fix.
		Analytics::events()->track(
			'database_repair_result',
			$repaired ? 'success' : 'failed',
			[],
			true
		);

		return $repaired;
	}

	/**
	 * Admin Notice Callback if sureforms pro is out of date.
	 *
	 * Hooked - admin_notices
	 *
	 * @return void
	 * @since 1.0.4
	 */
	public function srfm_pro_version_compatibility() {
		if ( ! Helper::has_pro() ) {
			return;
		}

		if ( empty( get_current_screen() ) ) {
			return;
		}

		if ( ! Helper::current_user_can() ) {
			return;
		}

		$srfm_pro_license_status = get_option( 'srfm_pro_license_status', '' );
		/**
		 * If the license status is not set then get the license status and update the option accordingly.
		 * This will be executed only once. Subsequently, the option status is updated by the licensing class on license activation or deactivation.
		 */
		if ( empty( $srfm_pro_license_status ) && class_exists( 'SRFM_PRO\Admin\Licensing' ) ) {
			$srfm_pro_license_status = \SRFM_PRO\Admin\Licensing::is_license_active() ? 'licensed' : 'unlicensed';
			update_option( 'srfm_pro_license_status', $srfm_pro_license_status );
		}

		$pro_plugin_name = defined( 'SRFM_PRO_PRODUCT' ) ? SRFM_PRO_PRODUCT : 'SureForms Pro';
		$message         = '';
		$url             = admin_url( 'admin.php?page=sureforms_form_settings&tab=account-settings' );
		if ( 'unlicensed' === $srfm_pro_license_status ) {
			ob_start();
			?>
			<p>
				<?php
				printf(
					// translators: %1$s: Opening anchor tag with URL, %2$s: Closing anchor tag, %3$s: SureForms Pro Plugin Name.
					esc_html__( 'Please %1$sactivate%2$s your copy of %3$s to get new features, access support, receive update notifications, and more.', 'sureforms' ),
					'<a href="' . esc_url( $url ) . '">',
					'</a>',
					'<i>' . esc_html( $pro_plugin_name ) . '</i>'
				);
				?>
			</p>
			<?php
			$message = ob_get_clean();
		}

		if ( ! version_compare( SRFM_PRO_VER, SRFM_PRO_RECOMMENDED_VER, '>=' ) ) {
			ob_start();
			?>
			<p>
				<?php
				printf(
					// translators: %1$s: SureForms version, %2$s: SureForms Pro Plugin Name, %3$s: SureForms Pro Version, %4$s: Anchor tag open, %5$s: Closing anchor tag.
					esc_html__( 'SureForms %1$s requires minimum %2$s %3$s to work properly. Please update to the latest version from %4$shere%5$s.', 'sureforms' ),
					esc_html( SRFM_VER ),
					esc_html( $pro_plugin_name ),
					esc_html( SRFM_PRO_RECOMMENDED_VER ),
					'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">',
					'</a>'
				);
				?>
			</p>
			<?php
			$message .= ob_get_clean();
		}

		if ( ! empty( $message ) ) {
			// Phpcs ignore comment is required as $message variable is already escaped.
			?>
			<div class="notice notice-warning"><?php echo wp_kses_post( $message ); ?></div>
			<?php
		}
	}

	/**
	 * Display a notice to the user about providing a review.
	 *
	 * @since 2.5.2
	 * @return void
	 */
	public function display_srfm_rating_notice() {
		// Only show to admins.
		if ( ! Helper::current_user_can() ) {
			return;
		}

		// Allow the notice to be disabled; never shown while promotions are hidden.
		if ( Helper::hide_promotions() || ! apply_filters( 'srfm_show_rating_notice', true ) ) {
			return;
		}

		$notice_id = 'srfm-plugin-review-notice';

		Astra_Notices::add_notice(
			[
				'id'                         => $notice_id,
				'type'                       => '',
				'message'                    => self::build_srfm_notice_markup(
					__( 'Amazing! SureForms is powering your forms and submissions - let\'s keep growing together!', 'sureforms' ),
					__( 'If SureForms has been helpful, would you mind taking a moment to leave a 5-star review on WordPress.org?', 'sureforms' ),
					[
						[
							'text'     => __( 'Rate SureForms', 'sureforms' ),
							'url'      => esc_url( 'https://wordpress.org/support/plugin/sureforms/reviews/' ),
							'primary'  => true,
							// Leaves wp-admin, so it also dismisses on the way out.
							'dismiss'  => true,
							'external' => true,
						],
						[
							'text'    => __( 'Maybe later', 'sureforms' ),
							'url'     => '#',
							'dismiss' => true,
							'snooze'  => WEEK_IN_SECONDS,
						],
						[
							'text'    => __( 'I already did', 'sureforms' ),
							'url'     => '#',
							'dismiss' => true,
						],
					]
				),
				'class'                      => 'srfm-notice srfm-rating-notice',
				'repeat-notice-after'        => WEEK_IN_SECONDS,
				// Yields to the Thank You prompt for the same reason the Getting Started
				// notice does: a specific form to finish beats a recurring review ask,
				// and a user with three forms who then imports a template would
				// otherwise see both at once.
				'show_if'                    => $this->maybe_display_rating_notice() && null === $this->get_displayable_thankyou_prompt() && ! $this->has_action_item_warnings(),
				'display-with-other-notices' => true,
			]
		);

		add_action( 'astra_notice_before_markup_' . $notice_id, [ $this, 'print_srfm_notice_styles' ] );
		add_action( 'astra_notice_after_markup_' . $notice_id, [ $this, 'enqueue_notice_response_script' ] );
	}

	/**
	 * Display a "Getting Started" admin notice for new users who haven't yet
	 * reached the rating-notice milestone (3+ forms or 3+ entries).
	 *
	 * The Astra Notices library handles the 7-day delay via the
	 * `display-notice-after` parameter.
	 *
	 * @since 2.5.2
	 * @return void
	 */
	public function display_srfm_getting_started_notice() {
		// Only show to admins.
		if ( ! Helper::current_user_can() ) {
			return;
		}

		// Allow the notice to be disabled programmatically.
		if ( ! apply_filters( 'srfm_show_getting_started_notice', true ) ) {
			return;
		}

		$notice_id = 'srfm-getting-started-notice';

		Astra_Notices::add_notice(
			[
				'id'                         => $notice_id,
				'type'                       => '',
				'message'                    => self::build_srfm_notice_markup(
					__( 'SureForms is ready to power your forms — explore what\'s possible!', 'sureforms' ),
					__( 'Manage your forms, track submissions, and discover features like AI Form Builder, payment integrations, and more from the SureForms dashboard.', 'sureforms' ),
					[
						[
							'text'    => __( 'Go to Dashboard', 'sureforms' ),
							'url'     => esc_url( admin_url( 'admin.php?page=sureforms_menu' ) ),
							'primary' => true,
						],
						[
							'text'    => __( 'Maybe later', 'sureforms' ),
							'url'     => '#',
							'dismiss' => true,
							'snooze'  => WEEK_IN_SECONDS,
						],
						[
							'text'    => __( 'I already know', 'sureforms' ),
							'url'     => '#',
							'dismiss' => true,
						],
					]
				),
				'class'                      => 'srfm-notice srfm-getting-started-notice',
				'repeat-notice-after'        => WEEK_IN_SECONDS,
				// Yields to both of the other SureForms notices, so only one of ours is
				// ever on screen. The rating notice supersedes it once the user has real
				// usage; the Thank You prompt supersedes it because "finish this specific
				// form" is a concrete next step and this is a generic tour invitation.
				'show_if'                    => ! $this->maybe_display_rating_notice() && null === $this->get_displayable_thankyou_prompt() && ! $this->has_action_item_warnings(),
				'display-notice-after'       => WEEK_IN_SECONDS,
				'display-with-other-notices' => true,
			]
		);

		// Same pre-markup hook the Thank You prompt uses, so both notices are painted
		// by one stylesheet instead of two that drift apart.
		add_action( 'astra_notice_before_markup_' . $notice_id, [ $this, 'print_srfm_notice_styles' ] );
		add_action( 'astra_notice_after_markup_' . $notice_id, [ $this, 'enqueue_notice_response_script' ] );
	}

	/**
	 * Enqueue the notice response analytics script.
	 *
	 * Called via the astra_notice_after_markup_{id} hook so the script
	 * only loads when a SureForms notice is actually rendered.
	 *
	 * @since 2.5.2
	 * @return void
	 */
	public function enqueue_notice_response_script() {
		if ( wp_script_is( 'srfm-notice-response', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_script(
			'srfm-notice-response',
			SRFM_URL . 'admin/assets/js/notice-response.js',
			[],
			SRFM_VER,
			true
		);

		wp_localize_script(
			'srfm-notice-response',
			'srfmNoticeResponse',
			[
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'srfm_notice_response' ),
				// Carousel chrome. Built in the browser rather than printed here so
				// that with JavaScript off every notice simply stays visible, which
				// is the behaviour this replaced -- controls that cannot work must
				// not be what hides a warning.
				'carousel' => [
					'previous' => __( 'Previous notice', 'sureforms' ),
					'next'     => __( 'Next notice', 'sureforms' ),
					/* translators: 1: current position, 2: total notices. */
					'counter'  => __( '%1$d of %2$d', 'sureforms' ),
				],
			]
		);
	}

	/**
	 * Handle the notice response AJAX request.
	 *
	 * Validates the request and records the analytics event
	 * for the notice button that was clicked.
	 *
	 * @since 2.5.2
	 * @return void
	 */
	public function handle_notice_response() {
		if ( ! check_ajax_referer( 'srfm_notice_response', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'sureforms' ) ], 403 );
			return;
		}

		if ( ! Helper::current_user_can() ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized user.', 'sureforms' ) ], 403 );
			return;
		}

		$notice_id = isset( $_POST['notice_id'] ) ? sanitize_text_field( wp_unslash( $_POST['notice_id'] ) ) : '';
		$button    = isset( $_POST['button'] ) ? sanitize_text_field( wp_unslash( $_POST['button'] ) ) : '';

		$valid = [
			'srfm-getting-started-notice' => [
				'go_to_dashboard' => 'getting_started_notice_cta',
				'maybe_later'     => 'getting_started_notice_snooze',
				'dismissed'       => 'getting_started_notice_dismiss',
			],
			'srfm-plugin-review-notice'   => [
				'rate_sureforms' => 'rating_notice_cta',
				'maybe_later'    => 'rating_notice_snooze',
				'dismissed'      => 'rating_notice_dismiss',
			],
			// Database maintenance notice. Keyed `database_error` for the warehouse;
			// the user-facing copy deliberately reads as a routine update, not an
			// error. `dismissed` is registered but unreachable today — a missing
			// entries table is not something we let people dismiss.
			'database_error'              => [
				'fix_now'   => 'database_error_notice_cta',
				'dismissed' => 'database_error_notice_dismiss',
			],
			// The "Finish setting up" prompt (#3030): three CTAs, plus the ✕.
			'form_submission_error'       => [
				'contact_support' => 'submission_failure_notice_cta',
				'dismissed'       => 'submission_failure_notice_dismiss',
			],
			'notification_error'          => [
				'contact_support' => 'notification_failure_notice_cta',
				'help_me_fix'     => 'notification_failure_notice_guide',
				'dismissed'       => 'notification_failure_notice_dismiss',
			],
			'integration_error'           => [
				'contact_support' => 'integration_failure_notice_cta',
				'dismissed'       => 'integration_failure_notice_dismiss',
			],
			'caching_plugin'              => [
				'help_me_fix' => 'caching_plugin_notice_cta',
				'dismissed'   => 'caching_plugin_notice_dismiss',
			],
			'srfm-thankyou-prompt'        => [
				'edit_form'     => 'thankyou_notice_edit_form',
				'set_replies'   => 'thankyou_notice_set_replies',
				'edit_thankyou' => 'thankyou_notice_edit_thankyou',
				'dismissed'     => 'thankyou_notice_dismiss',
			],
		];

		if ( ! isset( $valid[ $notice_id ][ $button ] ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid parameters.', 'sureforms' ) ], 400 );
			// wp_send_json_error() ends the request in production. The explicit return
			// keeps the guard a guard rather than something that only works because of
			// a side effect in a function elsewhere.
			return;
		}

		$this->track_notice_event( $valid[ $notice_id ][ $button ] );

		// Reporting the failures retires the notice until something new fails.
		// Handled here rather than in the browser so both surfaces share it. The
		// click still reaches here only through JavaScript -- notice-response.js
		// on the classic notice, ActionItems.js on the dashboard -- so with
		// JavaScript off the link opens the email but the notice stays.
		$categories = [
			'form_submission_error' => 'submission',
			'notification_error'    => 'notification',
			'integration_error'     => 'integration',
		];

		if ( 'contact_support' === $button && isset( $categories[ $notice_id ] ) ) {
			Client_Logger::acknowledge_category( $categories[ $notice_id ] );

			if ( 'form_submission_error' === $notice_id ) {
				Client_Logger::acknowledge_failures();
			}
		}

		wp_send_json_success();
	}

	/**
	 * Disables the capabilities for WPForms to avoid conflicts when enqueueing
	 * scripts and styles for WPForms.
	 *
	 * This function is intended to prevent any potential conflicts that may arise
	 * when WPForms scripts and styles are enqueued. By disabling certain capabilities,
	 * it ensures that WPForms does not interfere with other functionalities.
	 *
	 * @param bool $user_can A boolean indicating whether the user has the capability.
	 * @return bool Returns true if the capabilities are successfully disabled, false otherwise.
	 * @since 1.4.2
	 */
	public function disable_wpforms_capabilities( $user_can ) {
		// Note: Nonce verification is intentionally omitted here as no database operations are performed.
		// The values of the $_REQUEST variables are strictly validated, ensuring security without the need for nonce verification.

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = ! empty( $_REQUEST['post'] ) && ! empty( $_REQUEST['action'] ) ? absint( $_REQUEST['post'] ) : 0;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_type = $post_id ? get_post_type( $post_id ) : sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ?? '' ) );
		return SRFM_FORMS_POST_TYPE === $post_type ? false : $user_can;
	}

	/**
	 * Enqueueus the admin pointer script and styles.
	 *
	 * @return void
	 * @since 1.8.0
	 */
	public function enqueue_admin_pointer() {
		if ( ! $this->is_admin_pointer_visible() ) {
			return;
		}
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );
		wp_enqueue_script(
			'sureforms-admin-pointer',
			plugins_url( 'admin/assets/js/sureforms-pointer.js', SRFM_FILE ),
			[ 'wp-pointer', 'jquery' ],
			SRFM_VER,
			true
		);
		wp_localize_script(
			'sureforms-admin-pointer',
			'sureformsPointerData',
			[
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'pointer_nonce' => wp_create_nonce( 'sureforms_pointer_action' ),
			]
		);
	}

	/**
	 * Ajax handler for pointer popup visibility.
	 *
	 * @return void
	 * @since 1.8.0
	 */
	public function pointer_should_show() {
		// Security: Check user capability.
		if ( ! Helper::current_user_can() ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized user.', 'sureforms' ) ], 403 );
		}
		// Security: Nonce check.
		if ( empty( $_POST['pointer_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pointer_nonce'] ) ), 'sureforms_pointer_action' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'sureforms' ) ], 403 );
		}

		$content_markup = sprintf(
			/* translators: 1: opening span, 2: opening strong (inline), 3: closing strong, 4: closing span, 5: opening strong (block), 6: closing strong */
			__( '%1$sGet started by %2$sbuilding your first form%3$s.%4$s%5$sExperience the power of our intuitive AI Form Builder%6$s', 'sureforms' ),
			'<span>',
			'<strong>',
			'</strong>',
			'</span><br/>',
			'<strong style="font-size:1.1em;">',
			'</strong>'
		);
		wp_send_json(
			[
				'show'        => true,
				'title'       => esc_html( __( 'SureForms is waiting for you!', 'sureforms' ) ),
				'content'     => wp_kses_post( $content_markup ),
				'button_text' => esc_html( __( 'Build My First Form', 'sureforms' ) ),
				'dismiss'     => esc_html( __( 'Dismiss', 'sureforms' ) ),
				'button_url'  => admin_url( 'admin.php?page=add-new-form' ),
			]
		);
	}

	/**
	 * Ajax callback for pointer popup dismissed action.
	 *
	 * @return void
	 * @since 1.8.0
	 */
	public function pointer_dismissed() {
		// Security: Check user capability.
		if ( ! Helper::current_user_can() ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized user.', 'sureforms' ) ], 403 );
		}
		// Security: Nonce check.
		if ( empty( $_POST['pointer_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pointer_nonce'] ) ), 'sureforms_pointer_action' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'sureforms' ) ], 403 );
		}
		// Use Helper to update srfm_options key.
		Helper::update_srfm_option( 'pointer_popup_dismissed', time() );

		wp_send_json_success();
	}

	/**
	 * Ajax pointer accepted CTA callback.
	 *
	 * @return void
	 * @since 1.8.0
	 */
	public function pointer_accepted_cta() {
		// Security: Check user capability.
		if ( ! Helper::current_user_can() ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized user.', 'sureforms' ) ], 403 );
		}
		// Security: Nonce check.
		if ( empty( $_POST['pointer_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pointer_nonce'] ) ), 'sureforms_pointer_action' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'sureforms' ) ], 403 );
		}
		// Use Helper to update srfm_options key.
		Helper::update_srfm_option( 'pointer_popup_accepted', time() );

		wp_send_json_success();
	}

	/**
	 * Maybe register the dashboard widget based on entries.
	 *
	 * @return void
	 * @since 1.9.1
	 */
	public function maybe_register_dashboard_widget() {

		// Only for users with manage_options capability, and never while
		// promotions are hidden: no SureForms widget on the WordPress dashboard.
		if ( ! Helper::current_user_can() || Helper::hide_promotions() ) {
			return;
		}

		// Register the AI quick draft widget for capable users (the capability gate above applies); unlike the recent-entries widget below, it is not conditional on having entries.
		add_action( 'wp_dashboard_setup', [ $this, 'register_ai_dashboard_widget' ] );

		// Quick check if there are any entries in the last 7 days.
		$seven_days_ago = strtotime( '-7 days' );
		$total_entries  = Entries::get_entries_count_after( $seven_days_ago );

		// Only add the dashboard setup hook if there are entries.
		if ( $total_entries > 0 ) {
			// Get forms with entries (limit 4 for dashboard widget).
			$this->dashboard_widget_data = Helper::get_forms_with_entry_counts( $seven_days_ago, 4 );

			// Only show dashboard widget if there are forms with entries.
			if ( ! empty( $this->dashboard_widget_data ) ) {
				add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
			}
		}
	}

	/**
	 * Register the dashboard widget.
	 *
	 * @return void
	 * @since 1.9.1
	 */
	public function register_dashboard_widget() {
		// Add the widget with high priority to position it at the top.
		wp_add_dashboard_widget(
			'sureforms_recent_entries',
			__( 'SureForms', 'sureforms' ),
			[ $this, 'render_dashboard_widget' ],
			null,
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Register the AI quick draft dashboard widget.
	 *
	 * @return void
	 * @since 2.12.1
	 */
	public function register_ai_dashboard_widget() {
		wp_add_dashboard_widget(
			'sureforms_ai_quick_draft',
			__( 'SureForms AI Quick Draft', 'sureforms' ),
			[ $this, 'render_ai_dashboard_widget' ],
			null,
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Render AI quick draft dashboard widget content.
	 *
	 * @return void
	 * @since 2.12.1
	 */
	public function render_ai_dashboard_widget() {
		?>
		<div class="srfm-ai-dashboard-widget">
			<p>
				<?php esc_html_e( 'Describe the form and let SureForms AI generate it for you.', 'sureforms' ); ?>
			</p>
			<label for="srfm-ai-dashboard-prompt" class="screen-reader-text">
				<?php esc_html_e( 'Describe your form', 'sureforms' ); ?>
			</label>
			<textarea
				id="srfm-ai-dashboard-prompt"
				class="widefat"
				rows="5"
				maxlength="2000"
				placeholder="<?php esc_attr_e( 'Example: Create a contact form with name, email, phone, and message fields.', 'sureforms' ); ?>"
			></textarea>
			<p style="margin-top:10px;margin-bottom:0;display:flex;align-items:center;gap:10px;">
				<button type="button" class="button button-primary" id="srfm-ai-dashboard-generate" disabled>
					<?php esc_html_e( 'Create New Form', 'sureforms' ); ?>
				</button>
				<span id="srfm-ai-dashboard-char-count" style="color:#646970;">0/2000</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Enqueue the AI quick draft dashboard widget script on the dashboard screen.
	 *
	 * The widget's behavior lives here (attached via wp_add_inline_script) rather than as an
	 * inline <script> in the render callback, so it passes Plugin Check and keeps server values
	 * out of the markup. Server values are passed through wp_localize_script.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 * @since 2.12.1
	 */
	public function enqueue_ai_dashboard_widget_assets( $hook_suffix ) {
		// Only on the main dashboard, and only for capable users (matches the widget gate).
		if ( 'index.php' !== $hook_suffix || ! Helper::current_user_can() || Helper::hide_promotions() ) {
			return;
		}

		// Register an inline-only handle (empty src) — the WordPress-core pattern for attaching
		// localized data plus an inline script without shipping a separate asset file.
		wp_register_script( 'srfm-ai-dashboard-widget', '', [], SRFM_VER, true );
		wp_enqueue_script( 'srfm-ai-dashboard-widget' );

		wp_localize_script(
			'srfm-ai-dashboard-widget',
			'srfmAiDashboardWidget',
			[
				'redirectUrl'    => admin_url( 'admin.php?page=add-new-form' ),
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'srfm_ai_widget_usage' ),
				'redirectingTxt' => __( 'Redirecting...', 'sureforms' ),
			]
		);

		$inline_script = <<<'JS'
( function () {
	const config = window.srfmAiDashboardWidget || {};
	const generateButton = document.getElementById( 'srfm-ai-dashboard-generate' );
	const promptField = document.getElementById( 'srfm-ai-dashboard-prompt' );
	const charCount = document.getElementById( 'srfm-ai-dashboard-char-count' );
	if ( ! generateButton || ! promptField ) {
		return;
	}

	const updateWidgetState = function () {
		const promptValue = promptField.value.trim();
		generateButton.disabled = ! promptValue;
		if ( charCount ) {
			charCount.textContent = `${ promptField.value.length }/2000`;
		}
	};

	const triggerGeneration = function () {
		const prompt = promptField.value.trim();
		if ( ! prompt ) {
			promptField.focus();
			return;
		}

		generateButton.disabled = true;
		generateButton.textContent = config.redirectingTxt;

		const redirectUrl = new URL( config.redirectUrl, window.location.origin );
		redirectUrl.searchParams.set( 'srfm_ai_dashboard_prompt', prompt );

		const requestBody = new URLSearchParams();
		requestBody.append( 'action', 'srfm_ai_widget_usage' );
		requestBody.append( 'nonce', config.nonce );

		fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: requestBody.toString(),
		} ).finally( function () {
			window.location.href = redirectUrl.toString();
		} );
	};

	promptField.addEventListener( 'input', updateWidgetState );
	generateButton.addEventListener( 'click', triggerGeneration );
	promptField.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Enter' && ( event.metaKey || event.ctrlKey ) ) {
			event.preventDefault();
			triggerGeneration();
		}
	} );

	updateWidgetState();
}() );
JS;

		wp_add_inline_script( 'srfm-ai-dashboard-widget', $inline_script );
	}

	/**
	 * Count an editor visit that came from the front-end "Edit Form" pill.
	 *
	 * The pill is a plain link, so the click is attributed by the marker query arg
	 * it carries rather than by a front-end click handler. That keeps the front end
	 * script-free and adds no AJAX endpoint: the only thing on the page is still an
	 * anchor. It also measures the outcome that matters — the editor actually
	 * opening — instead of a click that may never land.
	 *
	 * Every decision here comes from server state. The query arg selects the code
	 * path; what gets counted is derived from the resolved post and the current
	 * user's capability on it. An absent, empty, misspelled or reused arg, a post
	 * that is not a SureForms form, and a user without `edit_post` on that form all
	 * fall through to no-op without an explicit branch.
	 *
	 * No nonce, deliberately: the pill is rendered into front-end HTML that may be
	 * page-cached, so a nonce would either be baked into the cache or be stale on
	 * arrival. The effect is a private usage counter for a user who can already edit
	 * the form, and nothing attacker-controlled reaches the analytics payload — the
	 * value sent is an integer read back from stored state.
	 *
	 * Because the marker is just a query arg, the invariant that bounds this is the
	 * dedup transient below, not the arg: a given editor moves the counter at most
	 * once per form per hour, no matter how many times the URL is requested. That is
	 * also what keeps the metric honest — without it a refresh or a back-navigation
	 * would count again, and each count is a read-modify-write of the whole
	 * `srfm_options` row, which holds unrelated settings.
	 *
	 * @return void
	 * @since 2.12.6
	 */
	public function maybe_track_edit_form_button_click() {
		// is_string() before sanitize_key(): `?srfm_edit_src[]=x` satisfies isset(),
		// and wp_unslash() hands the array straight through. sanitize_key() only grew
		// its is_scalar() guard after this plugin's minimum WordPress, so on the older
		// supported versions that reaches strtolower( array ) — a TypeError on PHP 8,
		// i.e. the one input shape that ended in a fatal rather than in the no-op the
		// rest of this method guarantees.
		$arg = Generate_Form_Markup::EDIT_FORM_BUTTON_SOURCE_ARG;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only attribution marker; see docblock for why a nonce is neither possible nor needed.
		$source = isset( $_GET[ $arg ] ) && is_string( $_GET[ $arg ] ) ? sanitize_key( wp_unslash( $_GET[ $arg ] ) ) : '';

		if ( 'embed' !== $source ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Same read-only path as above.
		$post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;

		// Resolve the post type from the stored post, never from the request.
		//
		// The capability below reads as per-post but is not: sureforms_form is
		// registered with an explicit capabilities map and no `map_meta_cap`
		// (inc/post-types.php), so core short-circuits `edit_post` to the post type's
		// `edit_post` capability — `manage_options` — without ever consulting $post_id.
		// The real gate is therefore "site administrator", which is stricter than a
		// per-form check, not weaker. Written down because a later `map_meta_cap` on
		// the CPT would silently change what this line means with no diff here.
		if ( 0 === $post_id || SRFM_FORMS_POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// One count per editor per form per hour. Without this the metric measures
		// "editor loads carrying the marker" rather than pill clicks — a refresh or a
		// back-navigation re-counts — and a forged page could drive the counter, and
		// the writes behind it, without bound.
		$dedup_key = 'srfm_pill_click_' . get_current_user_id() . '_' . $post_id;

		if ( false !== get_transient( $dedup_key ) ) {
			return;
		}

		set_transient( $dedup_key, 1, HOUR_IN_SECONDS );

		$count = Helper::get_integer_value( Helper::get_srfm_option( 'edit_form_button_clicks', 0 ) ) + 1;
		Helper::update_srfm_option( 'edit_form_button_clicks', $count );

		// $force = true because this is a cumulative counter, not a one-time event —
		// it must re-send the latest count each cycle (bypasses one-time dedup).
		Analytics::events()->track( 'edit_form_button_clicked', (string) $count, [], true );
	}

	/**
	 * Let core strip the edit-attribution marker from the admin URL.
	 *
	 * Core's wp_admin_canonical_url() rewrites the address bar via replaceState() on
	 * admin_head, which runs after load-post.php — so the marker has already been
	 * counted by the time it is removed and no attribution is lost. Without this it
	 * lingers in the address bar, in bookmarks, and in the Referer header sent to
	 * every subresource the editor loads.
	 *
	 * @param array<string> $args Query args core already removes.
	 * @since 2.12.6
	 * @return array<string> Args with the marker appended.
	 */
	public function add_removable_query_args( $args ) {
		if ( ! is_array( $args ) ) {
			return [ Generate_Form_Markup::EDIT_FORM_BUTTON_SOURCE_ARG ];
		}

		$args[] = Generate_Form_Markup::EDIT_FORM_BUTTON_SOURCE_ARG;

		return $args;
	}

	/**
	 * Track AI dashboard widget usage.
	 *
	 * @return void
	 * @since 2.12.1
	 */
	public function track_ai_widget_usage() {
		if ( ! check_ajax_referer( 'srfm_ai_widget_usage', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'sureforms' ) ], 403 );
		}

		if ( ! Helper::current_user_can() ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized user.', 'sureforms' ) ], 403 );
		}

		$current_count = (int) Helper::get_srfm_option( 'ai_dashboard_widget_uses', 0 ) + 1;
		Helper::update_srfm_option( 'ai_dashboard_widget_uses', $current_count );

		// Emit an analytics event so usage lands in the warehouse via events_record.
		// $force = true because this is a cumulative counter, not a one-time event —
		// it must re-send the latest count each cycle (bypasses one-time dedup).
		Analytics::events()->track( 'ai_dashboard_widget_used', (string) $current_count, [], true );

		wp_send_json_success();
	}

	/**
	 * Render the dashboard widget content.
	 *
	 * @return void
	 * @since 1.9.1
	 */
	public function render_dashboard_widget() {
		// Use the pre-fetched data to avoid duplicate queries.
		$entries_data = $this->dashboard_widget_data;

		// Display the widget content.
		?>
		<div class="srfm-dashboard-widget">
			<div class="srfm-widget-header">
				<h3 class="srfm-widget-title">
					<?php esc_html_e( 'Recent Entries', 'sureforms' ); ?>
					<span class="srfm-widget-subtitle"><?php esc_html_e( '( Last 7 days )', 'sureforms' ); ?></span>
				</h3>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sureforms_entries' ) ); ?>" class="srfm-widget-view-link">
					<?php esc_html_e( 'View', 'sureforms' ); ?>
				</a>
			</div>

			<div class="srfm-table-wrapper">
				<table class="srfm-entries-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Form Name', 'sureforms' ); ?></th>
							<th><?php esc_html_e( 'Entries', 'sureforms' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $entries_data as $form_data ) { ?>
							<tr>
								<td class="form-name"><?php echo esc_html( $form_data['title'] ); ?></td>
								<td class="entry-count"><?php echo esc_html( $form_data['count'] ); ?></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>

			<?php
			// Render footer if applicable.
			$this->render_dashboard_widget_footer( $entries_data );
			?>
		</div>
		<?php
	}

	/**
	 * Classic dashboard notice when submissions keep failing.
	 *
	 * Hooked - admin_notices.
	 *
	 * Gated to the WP dashboard. The React notice already covers SureForms' own
	 * screens, so leaving this admin-wide would stack two warnings on one page.
	 *
	 * Registered as [ $this, 'method' ] rather than a closure because
	 * suppress_foreign_admin_notices() strips any callback it cannot attribute to
	 * a SureForms class -- a closure here would be silently removed.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public function render_action_item_notices() {
		// Shown across wp-admin, because someone whose forms are silently failing
		// may not open the WP dashboard or SureForms for days.
		//
		// The one exclusion is SureForms' own dashboard: the Form Checks panel in
		// its sidebar already lists these, and a banner above it would say the same
		// thing twice on one screen.
		if ( Helper::validate_request_context( 'sureforms_menu', 'page' ) ) {
			return;
		}

		$items = $this->get_action_items();

		// Only the faults reach this surface, so count those before deciding
		// whether the carousel stylesheet is worth printing.
		$rendered = 0;

		foreach ( $items as $item ) {
			$status = Helper::get_string_value( $item['status'] ?? '' );

			if ( 'success' !== $status && '' !== $status ) {
				$rendered++;
			}
		}

		if ( 0 === $rendered ) {
			return;
		}

		$this->enqueue_notice_response_script();

		foreach ( $items as $item ) {
			$status = Helper::get_string_value( $item['status'] ?? '' );

			// Passing checks belong in the SureForms panel, not in wp-admin. A
			// notice that says nothing is wrong is noise on every page load.
			if ( 'success' === $status || '' === $status ) {
				continue;
			}

			// A fault reads as an error; advice reads as a warning. Both are shown,
			// but they are not the same kind of message and should not look alike.
			$class = 'error' === $status ? 'notice-error' : 'notice-warning';
			?>
			<div class="notice srfm-action-item-notice <?php echo esc_attr( $class ); ?>">
				<?php
				/*
				 * Guarded like every sibling key. A filter item carrying only
				 * id/status/cta_* is a shape this surface designs for, and reading
				 * these unguarded is two PHP 8 undefined-key warnings plus an
				 * esc_html( null ) deprecation on 8.1+. React tolerates the absence,
				 * so leaving it would keep the two renderers disagreeing.
				 */
				?>
				<p><strong><?php echo esc_html( Helper::get_string_value( $item['title'] ?? '' ) ); ?></strong></p>
				<p><?php echo esc_html( Helper::get_string_value( $item['message'] ?? '' ) ); ?></p>
				<?php
				// Self-serve first, so the emphasis follows the order rather than the
				// identity: whichever action leads is the primary button, and an item
				// with no guide still leads with Contact Support.
				$has_guide = ! empty( $item['guide_label'] ) && ! empty( $item['guide_url'] );

				// Both keys, not either. An item contributed through
				// srfm_action_items may carry only guide_* keys -- reading cta_url
				// unguarded emits two PHP 8 undefined-key warnings and renders
				// href="" -- and a label without a URL renders an anchor that is not
				// keyboard focusable. React gates on the same pair.
				$has_cta = ! empty( $item['cta_label'] ) && ! empty( $item['cta_url'] );
				?>
				<p>
					<?php if ( $has_guide ) { ?>
						<a
							href="<?php echo esc_url( Helper::get_string_value( $item['guide_url'] ) ); ?>"
							class="button button-primary"
							data-srfm-notice-id="<?php echo esc_attr( Helper::get_string_value( $item['id'] ) ); ?>"
							data-srfm-button="<?php echo esc_attr( Helper::get_string_value( $item['guide_action'] ?? '' ) ); ?>"
							target="_blank"
							rel="noopener noreferrer"
						>
							<?php echo esc_html( $item['guide_label'] ); ?>
						</a>
					<?php } ?>
					<?php if ( $has_cta ) { ?>
						<a
							href="<?php echo esc_url( Helper::get_string_value( $item['cta_url'] ) ); ?>"
							class="<?php echo $has_guide ? 'button' : 'button button-primary'; ?>"
							data-srfm-notice-id="<?php echo esc_attr( Helper::get_string_value( $item['id'] ) ); ?>"
							data-srfm-button="<?php echo esc_attr( Helper::get_string_value( $item['cta_action'] ?? '' ) ); ?>"
							<?php
							// A mailto: must reach the mail client, not a new tab --
							// there is no document to open, so _blank leaves a blank
							// one behind.
							if ( 0 !== strpos( Helper::get_string_value( $item['cta_url'] ), 'mailto:' ) ) {
								echo 'target="_blank" rel="noopener noreferrer"';
							}
							?>
						>
							<?php echo esc_html( $item['cta_label'] ); ?>
						</a>
					<?php } ?>
					<?php if ( ! empty( $item['dismissible'] ) ) { ?>
						<a href="<?php echo esc_url( $this->get_dismiss_action_item_url( Helper::get_string_value( $item['id'] ) ) ); ?>" class="button">
							<?php esc_html_e( 'Dismiss', 'sureforms' ); ?>
						</a>
					<?php } ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Dismiss an action item from the classic notice's link.
	 *
	 * Hooked - admin_post_srfm_dismiss_action_item_link.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public function handle_dismiss_action_item_link() {
		if ( ! Helper::current_user_can() ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'sureforms' ), 403 );
		}

		check_admin_referer( 'srfm_dismiss_action_item' );

		$item_id = isset( $_GET['item'] ) ? sanitize_key( wp_unslash( $_GET['item'] ) ) : '';

		$this->dismiss_action_item( $item_id );

		$referer = wp_get_referer();

		wp_safe_redirect( $referer ? $referer : admin_url() );
		exit;
	}

	/**
	 * Whether a first-party warning is currently on screen.
	 *
	 * Asked from the show_if of the rating, Getting Started and Thank You notices,
	 * all of which are gated on nothing being wrong. "Wrong" has to mean the same
	 * thing here as it does to the person looking at the screen.
	 *
	 * It used to re-state the conditions instead of reading them, and the
	 * restatement was narrower than the display: has_persistent_failures() reads
	 * the `submission` counter alone, while the notices and the Form Checks panel
	 * warn on any open failure in any of the three categories. So an open
	 * notification or integration failure left this false, and the review ask
	 * appeared directly beneath "We noticed a notification failure on Contact
	 * Form". Submission was covered only incidentally, by FAULT_THRESHOLD being 1 --
	 * raise that and it would have joined them.
	 *
	 * Derived from get_first_party_action_items() now, which is the thing that
	 * builds those warnings, so the gate cannot drift from the display again.
	 *
	 * Two constraints kept from the previous version. It must not call
	 * get_action_items(): that records an impression as a side effect and must
	 * never run from a show_if. And it reads the first-party set specifically, so
	 * an item contributed through `srfm_action_items` cannot suppress notices that
	 * have nothing to do with it.
	 *
	 * Returns false with logging disabled, which is what makes those notices
	 * eligible again on a site that has turned this surface off. Intended: with the
	 * surface off there is nothing being reported.
	 *
	 * @since 2.12.6
	 * @return bool
	 */
	public function has_action_item_warnings() {
		if ( ! Client_Logger::is_enabled() ) {
			return false;
		}

		foreach ( $this->get_first_party_action_items() as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$status = Helper::get_string_value( $item['status'] ?? '' );

			// Matches the renderers: 'success' is a passing check and an empty
			// status is not a warning either, so neither suppresses anything.
			if ( 'success' !== $status && '' !== $status ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Things on this site that need the owner's attention, newest concern first.
	 *
	 * Fed to the dashboard sidebar carousel. Each entry is self-describing so the
	 * front end has no rules of its own to keep in sync -- adding a new item here
	 * makes it appear with no JavaScript change.
	 *
	 * `dismissible` separates a fault from advice. A run of failed submissions is
	 * not something to wave away, and clears itself when a submission succeeds. A
	 * caching plugin being present is information, so it can be dismissed.
	 *
	 * @since 2.12.6
	 * @return array<int,array<string,mixed>>
	 */
	public function get_action_items() {
		if ( ! Helper::current_user_can() ) {
			return [];
		}

		// Memoised for the request. This runs twice on every admin page -- once
		// building the localisation payload and once in the classic renderer -- and
		// each open category reads a log excerpt. It also records an impression, so
		// running twice counted twice. Matches the $thankyou_prompt_cache and
		// $setup_card_cache pattern already in this class.
		if ( null !== self::$action_items_cache ) {
			return self::$action_items_cache;
		}

		// Logging off is the opt-out for this surface. Not because the counters go
		// stale -- Client_Logger::record_failure() has no enabled check, and the
		// notification and integration categories are written by direct calls in
		// inc/form-submit.php that keep counting accurately with logging off. It is
		// simply the switch a site owner has to turn these notices off, and it
		// covers our own items only: the filter below still runs, because a third
		// party's advisory has nothing to do with SureForms' logging toggle.
		$warnings = [];

		if ( Client_Logger::is_enabled() ) {
			$warnings = $this->get_first_party_action_items();
		}

		$this->track_action_item_impressions( $warnings );

		/**
		 * Filter the dashboard action items.
		 *
		 * Each entry needs id, status ('warning' or 'success'), title, message,
		 * cta_label, cta_url and dismissible. Only ids in
		 * handle_dismiss_action_item()'s allowlist can actually be dismissed, so
		 * adding a dismissible item here also needs a line there.
		 *
		 * A third-party item's cta_url is followed as a plain link. The prefilled
		 * support email is built only for SureForms' own failure items, from its own
		 * client error log.
		 *
		 * @since 2.12.6
		 *
		 * @param array<int,array<string,mixed>> $items Action items.
		 */
		$items = Helper::apply_filters_as_array( 'srfm_action_items', $warnings );

		// Both URLs normalised once, here, rather than trusting each renderer to do
		// it. Two things are being fixed at once.
		//
		// The scheme: the classic notice runs esc_url() and drops anything outside
		// the allowlist, while React assigns href directly and react-dom 18 leaves
		// a javascript: URL intact -- its sanitizeURL() only warns, and the warning
		// is compiled out of the production build. esc_url_raw() with the same
		// allowlist closes both.
		//
		// The ampersands: Helper::get_sureforms_website_url() returns an esc_url()'d
		// string, so a URL with UTM parameters arrives with &#038; in it. In an HTML
		// href the browser decodes that; React sets the property directly, so the
		// entity would be sent to the server verbatim. Decoded to one raw form here,
		// and each renderer escapes it for its own context.
		foreach ( $items as $index => $item ) {
			// A filter may hand back an object. isset() on it returns false, which
			// would slip the item past both the URL normalisation and the
			// sanitize_key() below without any sign that it had.
			if ( ! is_array( $item ) ) {
				continue;
			}

			foreach ( [ 'cta_url', 'guide_url' ] as $key ) {
				if ( ! isset( $item[ $key ] ) ) {
					continue;
				}

				$items[ $index ][ $key ] = esc_url_raw(
					wp_specialchars_decode( Helper::get_string_value( $item[ $key ] ), ENT_QUOTES ),
					[ 'http', 'https', 'mailto' ]
				);
			}

			// The id ends up in the notice's data-srfm-notice-id attribute, which
			// notice-response.js matches on, and in the dismiss allowlist.
			// sanitize_key() is what both dismiss paths already apply, so applying
			// it once here means the value that renders is the value they compare
			// against -- and a filter-contributed id carrying a quote cannot break
			// the selector.
			if ( isset( $item['id'] ) ) {
				$items[ $index ]['id'] = sanitize_key( Helper::get_string_value( $item['id'] ) );
			}
		}

		self::$action_items_cache = $items;

		return $items;
	}

	/**
	 * Dismiss one action item.
	 *
	 * Hooked - wp_ajax_srfm_dismiss_action_item.
	 *
	 * Only items get_action_items() marks dismissible can be dismissed, so a
	 * crafted request cannot silence a genuine fault.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	public function handle_dismiss_action_item() {
		if ( ! Helper::current_user_can() ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized user.', 'sureforms' ) ], 403 );
			return;
		}

		if ( ! check_ajax_referer( 'srfm_dismiss_action_item', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'sureforms' ) ], 403 );
			return;
		}

		$item_id = isset( $_POST['item_id'] ) ? sanitize_key( wp_unslash( $_POST['item_id'] ) ) : '';

		if ( ! $this->dismiss_action_item( $item_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid parameters.', 'sureforms' ) ], 400 );
			return;
		}

		wp_send_json_success();
	}

	/**
	 * The stylesheet for the notice carousel.
	 *
	 * In a stylesheet rather than inline style assignments in
	 * notice-response.js, so the rules use logical properties and an RTL sheet can
	 * override them.
	 *
	 * Only the classic wp-admin surface needs these. The SureForms dashboard is
	 * styled by the Tailwind build, so nothing here reaches it.
	 *
	 * Attached to a registered handle with no file of its own, which is the WP way
	 * to ship CSS tied to one script.
	 *
	 * Hooked to admin_enqueue_scripts rather than called from the renderer.
	 * admin_notices fires from admin-header.php after admin_print_styles has
	 * flushed the head, so enqueuing there reached the page only through core's
	 * late-styles pass in the footer -- and until that parsed, every stacked notice
	 * rendered expanded before collapsing to one, the carousel controls overlapped
	 * the notice text, and the defensive `display: none` on the hidden payload was
	 * inert, which is the exact window that rule exists for.
	 *
	 * @since 2.12.7
	 * @return void
	 */
	public function enqueue_action_item_styles() {
		if ( wp_style_is( 'srfm-action-items', 'enqueued' ) ) {
			return;
		}

		if ( ! Helper::current_user_can() ) {
			return;
		}

		// Nothing to style unless the carousel is actually going to build. Cheap to
		// ask: get_action_items() is memoised for the request.
		//
		// Two, not one: notice-response.js bails below two cards, so these rules
		// have no consumer on a site with a single open fault.
		$notices = 0;

		foreach ( $this->get_action_items() as $item ) {
			$status = Helper::get_string_value( is_array( $item ) ? $item['status'] ?? '' : '' );

			if ( 'success' !== $status && '' !== $status ) {
				$notices++;
			}
		}

		if ( $notices < 2 ) {
			return;
		}

		wp_register_style( 'srfm-action-items', false, [], SRFM_VER );
		wp_enqueue_style( 'srfm-action-items' );

		$css = <<<'CSS'
.srfm-action-item-carousel { position: relative; }
.srfm-action-item-carousel .srfm-action-item-notice { padding-inline-end: var(--srfm-carousel-reserve, 130px); }
/* [hidden] is only a UA rule, and WordPress sets display on .notice, so a
   third-party admin sheet can otherwise put a notice the carousel has hidden back
   on screen. */
.srfm-action-item-carousel .srfm-action-item-notice[hidden] { display: none; }
.srfm-action-item-carousel-nav {
	position: absolute;
	top: 8px;
	inset-inline-end: 12px;
	margin: 0;
	display: flex;
	align-items: center;
	gap: 8px;
}
CSS;

		wp_add_inline_style( 'srfm-action-items', $css );
	}

	/**
	 * SureForms' own action items, before the filter.
	 *
	 * Split out so the Enable Logs gate in get_action_items() can sit above this
	 * rather than above `srfm_action_items`. An item contributed through that
	 * filter has nothing to do with SureForms' logging toggle, and was being
	 * silenced by it.
	 *
	 * @since 2.12.7
	 * @return array<int,array<string,mixed>>
	 */
	private function get_first_party_action_items() {
		$warnings = [];
		$open     = Client_Logger::get_open_failures();

		// One item per category. They read differently to a site owner and must not
		// be collapsed: submissions failing means visitors cannot reach you, a
		// notification failing means you are not hearing about entries that did
		// save, an integration failing means a third party is not receiving them.
		$categories = [
			'submission'   => [
				'id'      => 'form_submission_error',
				/* translators: %s: form title. */
				'title'   => __( 'We noticed a form submission failure on %s.', 'sureforms' ),
				'generic' => __( 'We noticed a form submission failure.', 'sureforms' ),
				'message' => __( 'Visitors may not be able to reach you, and their entries were not saved.', 'sureforms' ),
			],
			'notification' => [
				'id'      => 'notification_error',
				/* translators: %s: form title. */
				'title'   => __( 'We noticed a notification failure on %s.', 'sureforms' ),
				'generic' => __( 'We noticed a notification failure.', 'sureforms' ),
				'message' => __( 'The entry was saved, but we could not send the email about it. New entries may be coming in without you knowing.', 'sureforms' ),
				// Email is the one failure here a site owner can usually fix without
				// us: it is almost always SMTP not being configured. Offer the guide
				// alongside support rather than making them wait for a reply.
				'guide'   => Helper::get_sureforms_website_url(
					'docs/troubleshooting-email-sending-in-sureforms/',
					[
						'utm_medium'  => 'form_checks_notice',
						'utm_content' => 'notification_error',
					]
				),
			],
			'integration'  => [
				'id'      => 'integration_error',
				/* translators: %s: form title. */
				'title'   => __( 'We noticed an integration failure on %s.', 'sureforms' ),
				'generic' => __( 'We noticed an integration failure.', 'sureforms' ),
				'message' => __( 'The entry was saved, but we could not send it to a connected service.', 'sureforms' ),
			],
		];

		foreach ( $categories as $category => $copy ) {
			if ( ! isset( $open[ $category ] ) ) {
				continue;
			}

			// Name the form. "A form is failing" is not actionable on a site with
			// twenty of them, and the title is the first thing anyone asks for.
			$form_title = Helper::get_string_value( $open[ $category ]['form_title'] ?? '' );

			$warning = [
				'id'          => $copy['id'],
				'status'      => 'error',
				'title'       => '' !== $form_title
					? sprintf( $copy['title'], $form_title )
					: $copy['generic'],
				'message'     => $copy['message'],
				// Straight to a composed email, as 2.12.6 did. The subject, the
				// diagnostics and the log tail are already in it, so reporting a
				// fault is one click and a send.
				//
				// Built when the page renders, so the report ships in the href of the
				// classic notice on every admin screen and in srfm_admin.action_items
				// on the dashboard, both for capable users only. Its log comes from
				// the client error log, which any visitor with a form's submit token
				// can write to, so treat it as untrusted text. It is inert here:
				// http_build_query() percent-encodes all of it, so it cannot break
				// out of the attribute or add &cc= / &bcc= to the mailto:, and the
				// URL is length-capped. Building it on click instead would bring back
				// an AJAX round trip and a nonce to open an email -- the 2.12.7
				// dialog's machinery -- for text the person reads in the composer
				// before anything is sent.
				'cta_label'   => __( 'Contact Support', 'sureforms' ),
				'cta_url'     => $this->get_support_contact_url( $category, $form_title ),
				'cta_action'  => 'contact_support',
				'dismissible' => false,
			];

			// A second, optional action. Absent keys render nothing, so a category
			// without a guide needs no branch in either renderer, and neither does
			// an item contributed through srfm_action_items.
			if ( ! empty( $copy['guide'] ) ) {
				$warning['guide_label']  = __( 'Help Me Fix', 'sureforms' );
				$warning['guide_url']    = $copy['guide'];
				$warning['guide_action'] = 'help_me_fix';
			}

			$warnings[] = $warning;
		}

		$caching_plugin = Helper::get_active_caching_plugin();

		if ( '' === $caching_plugin ) {
			return $warnings;
		}

		// Read here rather than at the top: with no caching plugin active nothing
		// consults it, and this is the only dismissible item.
		$dismissed = Helper::get_array_value( Helper::get_srfm_option( 'dismissed_action_items', [] ) );

		if ( ! in_array( 'caching_plugin', $dismissed, true ) ) {
			$warnings[] = [
				'id'          => 'caching_plugin',
				'status'      => 'warning',
				'title'       => sprintf(
					/* translators: %s: caching plugin name. */
					__( '%s may interfere with your forms.', 'sureforms' ),
					$caching_plugin
				),
				'message'     => __( 'Caching can show visitors an old copy of your form, or load its scripts in the wrong order.', 'sureforms' ),
				'cta_label'   => __( 'Help Me Fix', 'sureforms' ),
				'cta_url'     => Helper::get_caching_plugin_doc_url(),
				'cta_action'  => 'help_me_fix',
				'dismissible' => true,
			];
		}

		return $warnings;
	}

	/**
	 * Nonce-protected URL that repairs the entries table.
	 *
	 * Shared by both notice surfaces so there is one repair route, one nonce and one
	 * place that counts the click. Private, so it stays off the public API and out of
	 * the test-coverage gate.
	 *
	 * @since 2.12.6
	 * @return string
	 */
	private function get_database_repair_url() {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=srfm_repair_entries_table' ),
			'srfm_repair_entries_table'
		);
	}

	/**
	 * The database notice body, which differs by what the repair will actually do.
	 *
	 * Two outcomes are possible and they are not equivalent to the person clicking:
	 * when the entries table exists under a different prefix — a changed
	 * `$table_prefix`, a restored dump, a security plugin that renamed tables and
	 * skipped ours — the repair renames it back and every stored entry comes with
	 * it. When there is nothing to adopt, the repair creates an empty table and the
	 * old submissions are not recoverable from here.
	 *
	 * Promising the wrong one is how a maintenance prompt turns into a complaint, so
	 * the copy states which is about to happen.
	 *
	 * @since 2.12.6
	 * @return string
	 */
	private function get_database_notice_message() {
		if ( '' !== Register::get_adoptable_entries_table() ) {
			return __( 'SureForms found your form entries stored under a different database table prefix. Reconnecting them takes a moment, and your existing entries will be kept.', 'sureforms' );
		}

		return __( 'SureForms needs to update your database before it can save new form entries. This only takes a moment and will not change your forms or existing content. Entries submitted before now cannot be recovered from here.', 'sureforms' );
	}

	/**
	 * Count one sighting of the database notice, at most once per user per day.
	 *
	 * While the table is missing the notice renders on every admin page load, on two
	 * surfaces. Counting each render would rewrite the autoloaded `srfm_options` blob
	 * on every pageview of a site that is already broken, and one site left unfixed
	 * would dominate the aggregate. Throttling to a day per user answers the question
	 * that matters — how many people are seeing this — for one write.
	 *
	 * @since 2.12.6
	 * @return void
	 */
	private function track_database_notice_impression() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$key = 'srfm_db_notice_seen_' . $user_id;

		if ( get_transient( $key ) ) {
			return;
		}

		set_transient( $key, 1, DAY_IN_SECONDS );

		Analytics::events()->track( 'database_error_notice_shown', 'entries' );
	}

	/**
	 * Build the setup-card payload (uncached). See get_form_setup_card().
	 *
	 * @since 2.12.4
	 * @return array<string,mixed>|null Card payload, or null when there is no candidate.
	 */
	private static function compute_form_setup_card() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) || ! post_type_exists( SRFM_FORMS_POST_TYPE ) ) {
			return null;
		}

		// Negative cache. Deliberately not a `defined( 'ASTRA_SITES_VER' )` check:
		// Starter Templates defines that constant in its main plugin file, so it only
		// exists while the plugin is active, yet neither its uninstall.php nor its
		// deactivation hook removes the import marker. Gating on the constant would
		// silently switch this feature off for the very people it targets — anyone who
		// imported a starter template and then removed the one-shot import plugin.
		if ( 'no' === get_transient( self::NO_IMPORTED_FORMS_TRANSIENT ) ) {
			return null;
		}

		// Only forms created from an Astra Sites starter template — those carry the
		// marker Starter Templates stamps on imported posts (self::ASTRA_SITES_IMPORT_META).
		// Prime post + meta caches (the loop reads title, permalink and edit link
		// per candidate) so this is a single query, not a follow-up per form.
		$query = new \WP_Query(
			[
				'post_type'              => SRFM_FORMS_POST_TYPE,
				'post_status'            => [ 'publish', 'draft', 'pending' ],
				'posts_per_page'         => 10,
				// ID breaks the tie: a starter-template import creates several forms
				// within the same second, so post_date alone leaves "the newest form"
				// up to MySQL and it can differ between page loads.
				'orderby'                => [
					'date' => 'DESC',
					'ID'   => 'DESC',
				],
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'meta_query'             => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded to 10 recent forms; dashboard-only.
					[
						'key'     => self::ASTRA_SITES_IMPORT_META,
						'compare' => 'EXISTS',
					],
				],
			]
		);

		// Nothing on this site carries the marker — remember that, so the query does
		// not repeat on every load. Keyed on the query result rather than on anything
		// user-specific, so it is safe to share, and invalidated the moment a post is
		// stamped (see invalidate_starter_template_cache()).
		if ( empty( $query->posts ) ) {
			set_transient( self::NO_IMPORTED_FORMS_TRANSIENT, 'no', WEEK_IN_SECONDS );
		}

		foreach ( $query->posts as $post ) {
			$form_id = (int) $post->ID;

			if ( ! current_user_can( 'edit_post', $form_id ) ) {
				continue;
			}

			$edit_link = get_edit_post_link( $form_id, 'raw' );

			if ( empty( $edit_link ) ) {
				continue;
			}

			// The steps are shown as optional next-steps — their completion is not
			// computed, so the widget simply lists the actions the owner can take.
			return [
				'id'           => $form_id,
				'title'        => get_the_title( $form_id ),
				'edit_url'     => $edit_link,
				// Deep-links to the email-notification panel where supported; falls
				// back to opening the editor when the focus handler isn't present.
				'email_url'    => add_query_arg( 'srfm_focus', 'notifications', $edit_link ),
				// Deep-links to the Form Confirmation panel (the Thank You message).
				'thankyou_url' => add_query_arg( 'srfm_focus', 'thankyou', $edit_link ),
				// Front-end instant-form page. get_permalink() only yields a working
				// URL for published forms; a draft/pending form has no public URL, so
				// omit the view link there (the empty() guard hides the icon).
				'view_url'     => 'publish' === $post->post_status ? (string) get_permalink( $form_id ) : '',
			];
		}

		return null;
	}

	/**
	 * Build the Thank You prompt payload (uncached). See get_thankyou_prompt_forms().
	 *
	 * @since 2.12.4
	 * @return array<int,array<string,mixed>> One entry, or none.
	 */
	private static function compute_thankyou_prompt_forms() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) || ! post_type_exists( SRFM_FORMS_POST_TYPE ) ) {
			return [];
		}

		// Negative cache — this notice renders on every admin screen, so keeping the
		// query off installs that can never match is what matters here. See
		// self::NO_IMPORTED_FORMS_TRANSIENT for why this is not gated on whether
		// Starter Templates is still active: the marker outlives the plugin.
		if ( 'no' === get_transient( self::NO_IMPORTED_FORMS_TRANSIENT ) ) {
			return [];
		}

		// Only forms imported from a Starter Templates (Astra Sites) starter
		// template — see self::ASTRA_SITES_IMPORT_META. Prime post + meta caches
		// (the loop reads meta, title and creation time per candidate) so this is a
		// single query rather than the main query plus a follow-up per form.
		$query = new \WP_Query(
			[
				'post_type'              => SRFM_FORMS_POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 10,
				// ID breaks the tie — an import creates several forms in the same
				// second, so post_date alone makes "newest" MySQL-dependent.
				'orderby'                => [
					'date' => 'DESC',
					'ID'   => 'DESC',
				],
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'meta_query'             => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded to 10 recent forms; admin-notice only.
					[
						'key'     => self::ASTRA_SITES_IMPORT_META,
						'compare' => 'EXISTS',
					],
				],
			]
		);

		// Nothing on this site carries the marker — remember that, so the query does
		// not repeat on every load. Keyed on the query result rather than on anything
		// user-specific, so it is safe to share, and invalidated the moment a post is
		// stamped (see invalidate_starter_template_cache()).
		if ( empty( $query->posts ) ) {
			set_transient( self::NO_IMPORTED_FORMS_TRANSIENT, 'no', WEEK_IN_SECONDS );
		}

		$prompts = [];
		$now     = time();

		foreach ( $query->posts as $post ) {
			$form_id = (int) $post->ID;

			if ( ! current_user_can( 'edit_post', $form_id ) ) {
				continue;
			}

			$steps = [
				// A destination for replies: an enabled notification with a recipient.
				'replies'  => ! self::form_has_reply_destination( $form_id ),
				// The thank-you message is still the shipped default.
				'thankyou' => self::is_default_confirmation_message( $form_id ),
			];

			// Nothing left to finish — no card for this form.
			if ( ! $steps['replies'] && ! $steps['thankyou'] ) {
				continue;
			}

			$edit_link = get_edit_post_link( $form_id, 'raw' );

			if ( empty( $edit_link ) ) {
				continue;
			}

			$created  = get_post_time( 'U', true, $form_id );
			$days_ago = is_int( $created ) ? (int) floor( ( $now - $created ) / DAY_IN_SECONDS ) : 0;

			$prompts[] = [
				'id'           => $form_id,
				'title'        => get_the_title( $form_id ),
				'days_ago'     => max( 0, $days_ago ),
				'steps'        => $steps,
				'edit_url'     => $edit_link,
				// The editor reads srfm_focus to open the matching settings tab:
				// "notifications" lands on Email Notification (where the reply
				// destination is set, so the CTA can actually clear that step) and
				// "thankyou" on Form Confirmation.
				'replies_url'  => add_query_arg( 'srfm_focus', 'notifications', $edit_link ),
				'thankyou_url' => add_query_arg( 'srfm_focus', 'thankyou', $edit_link ),
			];

			// One card is enough — surface only the latest form needing setup.
			break;
		}

		return $prompts;
	}

	/**
	 * Build the Thank You notice's inner markup (title, sentence, action buttons).
	 *
	 * @param array<string,mixed> $form Prompt payload from get_thankyou_prompt_forms().
	 *
	 * @since 2.12.4
	 * @return string
	 */
	private static function build_thankyou_notice_markup( $form ) {
		// The prompt only surfaces starter-template imports (see the meta gate), so
		// the form was created for the user rather than by them. Kept generic — no
		// per-step claim — so it is always accurate whatever the user has since
		// changed, while the action buttons point to the specific things to finish.
		$sentence = __( 'We’ve already created this form for you. Finish customising it so it’s ready to collect real submissions.', 'sureforms' );

		return self::build_srfm_notice_markup(
			sprintf(
				/* translators: %s: form name. */
				__( 'Finish setting up “%s”', 'sureforms' ),
				$form['title']
			),
			$sentence,
			[
				[
					'text'     => __( 'Edit form', 'sureforms' ),
					'url'      => $form['edit_url'],
					'primary'  => true,
					'class'    => 'srfm-ty-edit-form',
					'external' => true,
				],
				[
					'text'     => __( 'Edit the Thank You message', 'sureforms' ),
					'url'      => $form['thankyou_url'],
					'class'    => 'srfm-ty-edit-thankyou',
					'external' => true,
				],
				[
					'text'     => __( 'Set where replies go', 'sureforms' ),
					'url'      => $form['replies_url'],
					'class'    => 'srfm-ty-set-replies',
					'external' => true,
				],
			]
		);
	}

	/**
	 * Build the shared SureForms admin-notice body: title, sentence, action row.
	 *
	 * One builder for every SureForms notice so they cannot drift into looking like
	 * two different plugins. Everything is escaped here rather than by the caller —
	 * the notices library runs the result through wp_kses_post(), which would strip
	 * anything richer anyway.
	 *
	 * @param string                         $title   Notice heading.
	 * @param string                         $text    Supporting sentence.
	 * @param array<int,array<string,mixed>> $actions Action links. Each accepts
	 *                                                text, url, and optionally
	 *                                                primary, class, external,
	 *                                                dismiss and snooze (seconds).
	 * @since 2.12.6
	 * @return string
	 */
	private static function build_srfm_notice_markup( $title, $text, $actions ) {
		ob_start();
		?>
		<p class="srfm-notice__title"><?php echo esc_html( $title ); ?></p>
		<p class="srfm-notice__text"><?php echo esc_html( $text ); ?></p>
		<p class="srfm-notice__actions">
			<?php
			foreach ( $actions as $action ) {
				if ( empty( $action['text'] ) || ! isset( $action['url'] ) ) {
					continue;
				}

				$classes = [ 'button' ];

				if ( ! empty( $action['primary'] ) ) {
					$classes[] = 'button-primary';
				}

				// astra-notice-close is what the library binds its dismiss handler to.
				if ( ! empty( $action['dismiss'] ) ) {
					$classes[] = 'astra-notice-close';
				}

				if ( ! empty( $action['class'] ) ) {
					$classes[] = $action['class'];
				}
				?>
				<a
					class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
					href="<?php echo esc_url( $action['url'] ); ?>"
					<?php echo empty( $action['snooze'] ) ? '' : ' data-repeat-notice-after="' . esc_attr( (string) $action['snooze'] ) . '"'; ?>
					<?php echo empty( $action['external'] ) ? '' : ' target="_blank" rel="noopener noreferrer"'; ?>
				><?php echo esc_html( $action['text'] ); ?></a>
				<?php
			}
			?>
		</p>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Determine whether a notice callback is owned by SureForms.
	 *
	 * Recognises object methods on classes in the `SRFM` / `SRFM_PRO` namespaces
	 * as well as the bundled notices libraries (`BSF_Admin_Notices` and the
	 * legacy `Astra_Notices` alias). Everything else is treated as foreign.
	 *
	 * @param callable|array|string|null $function The registered callback function.
	 * @since 2.10.0
	 * @return bool True when the callback belongs to SureForms, false otherwise.
	 */
	private function is_sureforms_owned_notice_callback( $function ) {
		$class_name = '';

		if ( is_array( $function ) && isset( $function[0] ) ) {
			// Object or static method callback represented as an array. The first
			// element is either the object instance or the fully qualified class name.
			$class_name = is_object( $function[0] ) ? get_class( $function[0] ) : (string) $function[0];
		} elseif ( is_string( $function ) && false !== strpos( $function, '::' ) ) {
			// Static method passed as "Class::method".
			$class_name = strstr( $function, '::', true );
		}

		if ( '' === $class_name ) {
			// Plain function callbacks are never owned by SureForms.
			return false;
		}

		// SureForms (free and pro) namespaced classes. Pro's real namespace is
		// `SRFM_Pro\` (case-sensitive) — not the all-caps `SRFM_PRO_` constant
		// prefix — so match it case-insensitively to be safe.
		if ( 0 === strpos( $class_name, 'SRFM\\' ) || 0 === stripos( $class_name, 'SRFM_Pro\\' ) ) {
			return true;
		}

		// Bundled notices library shipped with SureForms.
		return in_array( $class_name, [ 'BSF_Admin_Notices', 'Astra_Notices' ], true );
	}

	/**
	 * Whether the current admin page is a SureForms-owned screen identified by a
	 * `sureforms_*` / `srfm_*` `page` query slug. Complements
	 * {@see Helper::is_sureforms_admin_page()} so foreign-notice suppression also
	 * covers the payments / quiz / survey / learn / SMTP / partial-entries screens
	 * that the core helper does not enumerate. Read-only screen check.
	 *
	 * @since 2.10.0
	 * @return bool
	 */
	private function is_sureforms_owned_admin_page() {
		if ( ! is_admin() || empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection, no state change.
			return false;
		}
		$page = sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection, no state change.
		return 0 === strpos( $page, 'sureforms' ) || 0 === strpos( $page, 'srfm' );
	}

	/**
	 * Callback for displaying the rating notice conditionally.
	 *
	 * Returns true if the user has 3 or more published forms or 3 or more form entries.
	 *
	 * @since 2.5.2
	 * @return bool
	 */
	private function maybe_display_rating_notice() {
		if ( null === $this->should_show_rating ) {
			$entries_count            = Entries::get_total_entries_by_status( 'all' );
			$form_count               = wp_count_posts( SRFM_FORMS_POST_TYPE );
			$this->should_show_rating = $entries_count >= self::RATING_NOTICE_THRESHOLD || Helper::get_integer_value( $form_count->publish ?? 0 ) >= self::RATING_NOTICE_THRESHOLD;
		}

		return $this->should_show_rating;
	}

	/**
	 * Get random premium feature text.
	 *
	 * @return string Random feature text.
	 * @since 1.9.1
	 */
	private function get_random_premium_feature_text() {
		$features = [
			__( 'Use Conditional Logic to show only what matters', 'sureforms' ),
			__( 'Split your form into steps to keep it easy', 'sureforms' ),
			__( 'Let people upload files directly to your form', 'sureforms' ),
			__( 'Turn responses into downloadable PDFs automatically', 'sureforms' ),
			__( 'Let users sign with a simple signature field', 'sureforms' ),
			__( 'Connect your form to other tools using webhooks', 'sureforms' ),
			__( 'Use Conversational Forms for a chat-like experience', 'sureforms' ),
			__( 'Let users register or log in through your form', 'sureforms' ),
			__( 'Build forms that create WordPress user accounts', 'sureforms' ),
			__( 'Add calculations to auto-total scores or prices', 'sureforms' ),
		];

		// Get a random feature.
		$random_key = array_rand( $features );
		return $features[ $random_key ];
	}

	/**
	 * Render the dashboard widget footer for upsell.
	 *
	 * @param array $entries_data The entries data array.
	 * @return void
	 * @since 1.9.1
	 */
	private function render_dashboard_widget_footer( $entries_data ) {
		// Only show footer if Pro is not active.
		if ( Helper::has_pro() ) {
			return;
		}

		// Count total entries in last 7 days.
		$total_entries = 0;
		foreach ( $entries_data as $form_data ) {
			$total_entries += $form_data['count'];
		}

		// Count total published forms.
		$published_forms_count = wp_count_posts( SRFM_FORMS_POST_TYPE )->publish;

		// Show footer only if 3+ entries received OR 3+ forms published.
		if ( $total_entries >= 3 || $published_forms_count >= 3 ) {
			?>
			<div class="srfm-widget-footer">
				<div class="srfm-upgrade-content">
					<svg class="srfm-logo-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<rect width="20" height="20" fill="#D54407"/>
						<path d="M5.7139 4.2854H14.2853V7.1425H7.1424L5.7139 8.5711V7.1425V4.2854Z" fill="white"/>
						<path d="M5.7139 4.2854H14.2853V7.1425H7.1424L5.7139 8.5711V7.1425V4.2854Z" fill="white"/>
						<path d="M5.7148 8.5713H12.8577V11.4284H7.1434L5.7148 12.857V11.4284V8.5713Z" fill="white"/>
						<path d="M5.7148 8.5713H12.8577V11.4284H7.1434L5.7148 12.857V11.4284V8.5713Z" fill="white"/>
						<path d="M5.7148 12.8569H10.0006V15.7141H5.7148V12.8569Z" fill="white"/>
						<path d="M5.7148 12.8569H10.0006V15.7141H5.7148V12.8569Z" fill="white"/>
					</svg>
					<span><?php echo esc_html( $this->get_random_premium_feature_text() ); ?></span>
				</div>
				<?php
				$upgrade_url = Helper::get_sureforms_website_url( 'pricing', [ 'utm_medium' => 'dashboard-widget' ] );
				?>
				<a href="<?php echo esc_url( $upgrade_url ); ?>" class="srfm-upgrade-link" target="_blank">
					<?php esc_html_e( 'Upgrade', 'sureforms' ); ?>
				</a>
			</div>
			<?php
		}
	}

	/**
	 * Determine if the admin pointer should be visible on this page.
	 *
	 * @since 1.8.0
	 * @return bool
	 */
	private function is_admin_pointer_visible() {
		global $pagenow;
		$allowed_pages = [ 'index.php', 'options-general.php' ];

		// Do not show if promotions are hidden, the pointer was dismissed or
		// accepted, or more than 1 form exists.
		if (
			Helper::hide_promotions()
			|| ! empty( Helper::get_srfm_option( 'pointer_popup_dismissed' ) )
			|| ! empty( Helper::get_srfm_option( 'pointer_popup_accepted' ) )
			|| (int) ( wp_count_posts( SRFM_FORMS_POST_TYPE )->publish ?? 0 ) > 1
		) {
			return false;
		}

		if ( in_array( $pagenow, $allowed_pages, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Nonced URL that dismisses one action item without JavaScript.
	 *
	 * The classic notice cannot use the AJAX dismissal the carousel uses, and
	 * WordPress's own `is-dismissible` only hides the notice for that pageview.
	 *
	 * @param string $item_id Item to dismiss.
	 * @since 2.12.6
	 * @return string
	 */
	private function get_dismiss_action_item_url( $item_id ) {
		return wp_nonce_url(
			add_query_arg(
				[
					'action' => 'srfm_dismiss_action_item_link',
					'item'   => $item_id,
				],
				admin_url( 'admin-post.php' )
			),
			'srfm_dismiss_action_item'
		);
	}

	/**
	 * Count one sighting of each warning, at most once per user per day.
	 *
	 * Throttled because the classic notice renders on every admin page: counting
	 * each render would measure how much wp-admin someone browses, not how many
	 * sites are affected. A day per user answers the question that matters -- how
	 * many people are seeing this -- for one option write.
	 *
	 * Counts SureForms' own items only. It runs before `srfm_action_items`, so a
	 * third party's contribution is not counted here -- SureForms has no name for
	 * it and no analytics key that would mean anything.
	 *
	 * @param array<int,array<string,mixed>> $warnings SureForms' own items.
	 * @since 2.12.6
	 * @return void
	 */
	private function track_action_item_impressions( $warnings ) {
		if ( empty( $warnings ) || wp_doing_ajax() ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$counts  = Helper::get_array_value( Helper::get_srfm_option( 'action_item_impressions', [] ) );
		$changed = false;

		foreach ( $warnings as $warning ) {
			$item_id = Helper::get_string_value( $warning['id'] ?? '' );

			if ( '' === $item_id ) {
				continue;
			}

			$seen_key = 'srfm_action_item_seen_' . $item_id . '_' . $user_id;

			if ( get_transient( $seen_key ) ) {
				continue;
			}

			set_transient( $seen_key, 1, DAY_IN_SECONDS );

			$counts[ $item_id ] = Helper::get_integer_value( $counts[ $item_id ] ?? 0 ) + 1;
			$changed            = true;

			// Cumulative, so $force = true: each new count is a new value and is
			// re-sent, while an identical repeat short-circuits inside track().
			Analytics::events()->track(
				$item_id . '_notice_shown',
				(string) $counts[ $item_id ],
				[],
				true
			);
		}

		if ( $changed ) {
			Helper::update_srfm_option( 'action_item_impressions', $counts );
		}
	}

	/**
	 * Record one interaction with a Form Checks notice, cumulatively.
	 *
	 * Both the value and `$force` matter. Analytics_Events::track() returns early
	 * when the event name is already in `usage_events_pushed`, so a call with
	 * `$force` omitted records each name at most once per site, ever -- the report
	 * could then say whether a button had ever been clicked but not how often, and
	 * these events exist to answer the second question. Sending a running total
	 * with `$force = true` re-sends each new value while an identical repeat still
	 * short-circuits inside track(). Same reasoning as
	 * track_action_item_impressions().
	 *
	 * @param string $event_name Analytics key from the allowlist.
	 * @since 2.12.7
	 * @return void
	 */
	private function track_notice_event( $event_name ) {
		$counts = Helper::get_array_value( Helper::get_srfm_option( 'action_item_events', [] ) );

		$counts[ $event_name ] = Helper::get_integer_value( $counts[ $event_name ] ?? 0 ) + 1;

		Helper::update_srfm_option( 'action_item_events', $counts );

		Analytics::events()->track( $event_name, (string) $counts[ $event_name ], [], true );
	}

	/**
	 * A pre-addressed support email for the failure being reported.
	 *
	 * Restores the 2.12.6 behaviour: the button opens the composer the person
	 * already uses, with the subject and the whole report written for them. What
	 * 2.12.7 replaced it with -- a web form -- could carry neither the diagnostics
	 * nor the log, so the button had to be gated behind copying them by hand and
	 * pasting them into a field on the far side. That is three deliberate steps to
	 * report a fault the plugin had already written up.
	 *
	 * The log is pasted into the body rather than attached because mailto has no
	 * attachment parameter -- browsers drop any attempt to add one -- and it is a
	 * tail rather than the whole file because a megabyte of JSON would exceed the
	 * URL length every mail client enforces. The finished URL is capped at
	 * SUPPORT_MAILTO_MAX_LENGTH, and the log is what gives way to meet it.
	 *
	 * The subject and body are English on every site, deliberately untranslated:
	 * they are written for SureForms support, and plain literals cannot be
	 * rewritten by a locale, a translation plugin or a gettext filter.
	 *
	 * @param string $category   One of Client_Logger::CATEGORIES, naming the failure
	 *                           being reported. An unknown or absent one gets
	 *                           deliberately neutral wording via get_support_copy().
	 * @param string $form_title Form the failure was recorded against, when known.
	 * @since 2.12.8
	 * @return string
	 */
	private function get_support_contact_url( $category, $form_title = '' ) {
		$copy = $this->get_support_copy( $category );
		$host = Helper::get_string_value( wp_parse_url( home_url(), PHP_URL_HOST ) );

		$subject = sprintf( $copy['subject'], $host );

		$url = $this->build_support_mailto_within_limit( $category, $form_title, $subject );

		// The last resort: the subject alone still names the problem and the site.
		if ( '' === $url ) {
			$url = $this->build_support_mailto( $subject );
		}

		/**
		 * Filter where the Contact Support action sends people.
		 *
		 * A white-label install wants its own inbox or its own support page, so both
		 * are accepted. Returning an http(s) URL is supported but drops the body --
		 * a web form cannot carry it -- so the person arrives without the site
		 * details or the debug log. They can still download the log from SureForms →
		 * Settings → General, but nothing prompts them to, so a filter returning a
		 * page should ask for it there.
		 *
		 * @since 2.12.7
		 *
		 * @param string $url        The pre-addressed mailto: URL.
		 * @param string $category   The failure being reported.
		 * @param string $form_title Form the failure was recorded against, or ''.
		 */
		$filtered = Helper::get_string_value( apply_filters( 'srfm_support_contact_url', $url, $category, $form_title ) );

		// Escaped after the filter, not before: the point of escaping here is that
		// neither renderer has to trust what comes back.
		$safe = esc_url_raw( $filtered, [ 'http', 'https', 'mailto' ] );

		// Never empty. Contact Support is the only action that retires these
		// notices and they are dismissible => false, so returning '' for a filter
		// value that cannot survive escaping leaves an undismissable notice with
		// nothing on it that works. The unfiltered URL is built here rather than
		// supplied, so it always escapes.
		return '' !== $safe ? $safe : esc_url_raw( $url, [ 'mailto' ] );
	}

	/**
	 * The fullest support mailto: that fits SUPPORT_MAILTO_MAX_LENGTH.
	 *
	 * A mailto: is a URL and every client enforces a length limit on it.
	 * Overrunning it does not truncate politely -- it drops the body, or the
	 * whole link -- while the click still retires the notice. So the cap is on
	 * the encoded URL, not the raw log: JSON-escaped non-ASCII text grows about
	 * eight times once percent-encoded. The log gives way first, because the
	 * site details are the part support cannot do without.
	 *
	 * @param string $category   The failure being reported.
	 * @param string $form_title Form the failure was recorded against, or ''.
	 * @param string $subject    Subject line.
	 * @since 2.12.8
	 * @return string The URL, or '' when even the body without a log is too long.
	 */
	private function build_support_mailto_within_limit( $category, $form_title, $subject ) {
		// CRLF, not "\n". RFC 6068 leaves the line ending to the client and the
		// major composers normalise either, but Outlook renders a bare LF body as a
		// single run-on line -- which is exactly the report a support agent has to
		// read.
		$message = str_replace( "\n", "\r\n", $this->get_support_message( $category, $form_title ) );

		foreach ( [ 1200, 800, 400, 0 ] as $budget ) {
			$log = 0 < $budget
				? $this->get_support_log_block( $budget )
				: '---' . "\n" . 'Debug log left out to keep this email short enough to send. The full log can be downloaded from SureForms → Settings → General.';

			$url = $this->build_support_mailto( $subject, $message . "\r\n\r\n" . str_replace( "\n", "\r\n", $log ) );

			if ( strlen( $url ) <= self::SUPPORT_MAILTO_MAX_LENGTH ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * A mailto: to the support inbox.
	 *
	 * @param string $subject Subject line.
	 * @param string $body    Body, with CRLF line endings. Omitted when empty.
	 * @since 2.12.8
	 * @return string
	 */
	private function build_support_mailto( $subject, $body = '' ) {
		$query = [ 'subject' => $subject ];

		if ( '' !== $body ) {
			$query['body'] = $body;
		}

		return 'mailto:' . self::SUPPORT_EMAIL . '?' . http_build_query(
			$query,
			'',
			'&',
			// RFC 3986, so a space is %20 rather than +. A mail client reading a
			// mailto: body decodes it as a URI, not as form data, so + arrives as a
			// literal plus in every word gap.
			PHP_QUERY_RFC3986
		);
	}

	/**
	 * The log tail, formatted for pasting.
	 *
	 * The budget is a parameter because it goes into a mailto: URL, and
	 * get_support_contact_url() lowers it until the encoded URL fits.
	 *
	 * @param int $max_chars Characters of log to include.
	 * @since 2.12.7
	 * @return string
	 */
	private function get_support_log_block( $max_chars = 1200 ) {
		$log   = Client_Logger::get_tail( $max_chars );
		$block = '---' . "\n";

		if ( '' === $log['text'] ) {
			return $block . 'Debug log: no entries recorded.';
		}

		$block .= sprintf(
			'Debug log (most recent %1$d of %2$d entries)',
			$log['shown'],
			$log['total']
		) . "\n";

		// Fenced so it survives a reply and reads as data rather than prose wherever
		// Markdown is rendered.
		$block .= '```' . "\n" . $log['text'] . "\n" . '```';

		if ( $log['shown'] < $log['total'] ) {
			$block .= "\n\n" . 'Older entries were left out to keep this excerpt readable. The full log can be downloaded from SureForms → Settings → General.';
		}

		return $block;
	}

	/**
	 * Subject and countless opening line for one kind of failure.
	 *
	 * Both come from here so they cannot drift apart: a subject naming one problem
	 * over a body describing another is worse than either alone. The counted form
	 * of the opening line lives in get_support_count_sentence().
	 *
	 * An unknown or absent category gets deliberately neutral wording. The
	 * alternative -- defaulting to the submission copy -- states something specific
	 * that may not be true, and an item contributed through srfm_action_items has no
	 * category at all.
	 *
	 * @param string $category One of Client_Logger::CATEGORIES.
	 * @since 2.12.7
	 * @return array{subject:string,anon:string}
	 */
	private function get_support_copy( $category ) {
		$copy = [
			'submission'   => [
				'subject' => 'SureForms: form submissions are failing on %s',
				'anon'    => 'SureForms has recorded form submissions on %s that could not be completed.',
			],
			'notification' => [
				'subject' => 'SureForms: notification emails are not being sent on %s',
				'anon'    => 'SureForms saved entries on %s but could not send the notification emails for them.',
			],
			'integration'  => [
				'subject' => 'SureForms: an integration is not receiving entries on %s',
				'anon'    => 'SureForms saved entries on %s but could not pass them to a connected service.',
			],
		];

		if ( isset( $copy[ $category ] ) ) {
			return $copy[ $category ];
		}

		return [
			'subject' => 'SureForms: a problem with the forms on %s',
			'anon'    => 'SureForms has recorded a problem with the forms on %s.',
		];
	}

	/**
	 * The sentence that opens the support email, with the failure count in it.
	 *
	 * English only, like the rest of the support email, so `1 === $count` is the
	 * whole plural rule.
	 *
	 * @param string $category One of Client_Logger::CATEGORIES. Unknown or absent
	 *                         gets neutral wording rather than a specific claim.
	 * @param int    $count    Failures recorded for that category.
	 * @since 2.12.7
	 * @return string
	 */
	private function get_support_count_sentence( $category, $count ) {
		switch ( $category ) {
			case 'submission':
				return sprintf(
					( 1 === $count
						? 'SureForms has recorded %d form submission that could not be completed.'
						: 'SureForms has recorded %d form submissions that could not be completed.' ),
					$count
				);

			case 'notification':
				return sprintf(
					( 1 === $count
						? 'SureForms saved %d entry but could not send the notification email for it.'
						: 'SureForms saved %d entries but could not send the notification emails for them.' ),
					$count
				);

			case 'integration':
				return sprintf(
					( 1 === $count
						? 'SureForms saved %d entry but could not pass it to a connected service.'
						: 'SureForms saved %d entries but could not pass them to a connected service.' ),
					$count
				);

			default:
				return sprintf(
					( 1 === $count
						? 'SureForms has recorded %d problem with the forms on this site.'
						: 'SureForms has recorded %d problems with the forms on this site.' ),
					$count
				);
		}
	}

	/**
	 * Diagnostics block for the support report.
	 *
	 * Carries what support would otherwise have to ask for, so the first reply can
	 * be an answer rather than a questionnaire.
	 *
	 * The count is the one for this category, not get_fault_streak(), which reports
	 * submissions only -- so a notification failure used to quote a number from an
	 * unrelated counter, often zero.
	 *
	 * @param string $category   One of Client_Logger::CATEGORIES.
	 * @param string $form_title Form the failure was recorded against, when known.
	 * @since 2.12.6
	 * @return string
	 */
	private function get_support_message( $category = '', $form_title = '' ) {
		global $wp_version;

		$failures = Client_Logger::get_failures();
		$count    = Helper::get_integer_value( $failures[ $category ]['count'] ?? 0 );
		$copy     = $this->get_support_copy( $category );

		// With nothing recorded, describe the failure without a number. The old
		// max( 1, $count ) reported "recorded 1 problem" and "Recorded failures: 1"
		// for a count nobody recorded -- a number support would then chase.
		$host = Helper::get_string_value( wp_parse_url( home_url(), PHP_URL_HOST ) );

		$lines = [
			'Hello SureForms support,',
			'',
			$count > 0
				? $this->get_support_count_sentence( $category, $count )
				: sprintf( $copy['anon'], $host ),
		];

		if ( '' !== $form_title ) {
			$lines[] = '';
			$lines[] = sprintf(
				'Form: %s',
				$form_title
			);
		}

		// Once: each call reads an option and a site option.
		$caching = Helper::get_active_caching_plugin();

		$lines = array_merge(
			$lines,
			[
				'',
				'---',
				'Site details',
				sprintf( 'Site: %s', home_url() ),
				sprintf( 'SureForms: %s', SRFM_VER ),
				sprintf(
					'SureForms Pro: %s',
					Helper::has_pro() && defined( 'SRFM_PRO_VER' ) ? SRFM_PRO_VER : 'not active'
				),
				sprintf( 'WordPress: %s', Helper::get_string_value( $wp_version ) ),
				sprintf( 'PHP: %s', PHP_VERSION ),
				sprintf(
					'Caching: %s',
					'' !== $caching ? $caching : 'none detected'
				),
				sprintf(
					'Recorded failures: %s',
					$count > 0 ? Helper::get_string_value( $count ) : 'none recorded'
				),
			]
		);

		// Only when there is one. A repeat report is worth knowing about: the same
		// category having been reported before means the last answer did not hold,
		// which is a different conversation from a first report. Appended with the
		// rest of the site details rather than raised to the top, because it is
		// context for them rather than a headline.
		//
		// Survives only until the next success in that category, because
		// clear_category() unsets the whole record -- so in practice it is
		// reachable for 'integration', which has no success signal, and transient
		// for the other two.
		//
		// Stored as time(), a UTC epoch comparable with the sibling 'at', and
		// formatted here with wp_date() so it reads in the site's timezone rather
		// than the server's.
		$acked_at = Helper::get_integer_value( $failures[ $category ]['acked_at'] ?? 0 );

		if ( $acked_at > 0 ) {
			$lines[] = sprintf(
				'Previously reported: %s',
				Helper::get_string_value( wp_date( 'Y-m-d H:i T', $acked_at ) )
			);
		}

		return implode( "\n", $lines );
	}

	/**
	 * Record one dismissal, shared by the AJAX and no-JS entry points.
	 *
	 * Allowlisted, so only advisory items can be dismissed. A run of failed
	 * submissions is a fault and must stay put until it actually resolves --
	 * otherwise a crafted request could silence the one message that matters.
	 *
	 * @param string $item_id Item to dismiss.
	 * @since 2.12.6
	 * @return bool False when the id is not dismissible.
	 */
	private function dismiss_action_item( $item_id ) {
		if ( ! in_array( $item_id, [ 'caching_plugin' ], true ) ) {
			return false;
		}

		$dismissed = Helper::get_array_value( Helper::get_srfm_option( 'dismissed_action_items', [] ) );

		if ( ! in_array( $item_id, $dismissed, true ) ) {
			$dismissed[] = $item_id;
			Helper::update_srfm_option( 'dismissed_action_items', $dismissed );

			// Recorded here rather than at each caller: both the cross in the
			// dashboard panel and the no-JS link in the classic notice land here.
			$this->track_notice_event( $item_id . '_notice_dismiss' );
		}

		return true;
	}
}
