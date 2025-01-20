<?php
/**
 * Admin Class.
 *
 * @package sureforms.
 */

namespace SRFM\Admin;

use SRFM\Admin\Views\Entries_List_Table;
use SRFM\Admin\Views\Single_Entry;
use SRFM\Inc\AI_Form_Builder\AI_Helper;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Helper;
use SRFM\Inc\Post_Types;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Admin handler class.
 *
 * @since 0.0.1
 */
class Admin {
	use Get_Instance;

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
		add_action( 'admin_menu', [ $this, 'add_new_form' ] );
		add_filter( 'plugin_action_links', [ $this, 'add_settings_link' ], 10, 2 );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_styles' ] );
		add_action( 'admin_head', [ $this, 'enqueue_header_styles' ] );
		add_filter( 'admin_body_class', [ $this, 'admin_template_picker_body_class' ] );

		// this action is used to restrict Spectra's quick action bar on SureForms CPTS.
		add_action( 'uag_enable_quick_action_sidebar', [ $this, 'restrict_spectra_quick_action_bar' ] );

		add_action( 'current_screen', [ $this, 'enable_gutenberg_for_sureforms' ], 100 );
		add_action( 'admin_notices', [ $this, 'srfm_pro_version_compatibility' ] );

		// Handle entry actions.
		add_action( 'admin_init', [ $this, 'handle_entry_actions' ] );
		add_action( 'admin_notices', [ Entries_List_Table::class, 'display_bulk_action_notice' ] );

		// This action enqueues translations for NPS Survey library.
		// A better solution will be required from library to resolve plugin conflict.
		add_action(
			'admin_footer',
			static function() {
				Helper::register_script_translations( 'nps-survey-script' );
			},
			1000
		);
	}

	/**
	 * Enable Gutenberg for SureForms associated post types.
	 *
	 * @since 0.0.10
	 */
	public function enable_gutenberg_for_sureforms() {

		// Check if the Classic Editor plugin is active and if it is set to replace the block editor.
		if ( ! class_exists( 'Classic_Editor' ) || 'block' === get_option( 'classic-editor-replace' ) ) {
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
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$capability = 'manage_options';
		$menu_slug  = 'sureforms_menu';

		$logo = file_get_contents( plugin_dir_path( SRFM_FILE ) . 'images/icon.svg' );
		add_menu_page(
			__( 'SureForms', 'sureforms' ),
			__( 'SureForms', 'sureforms' ),
			'edit_others_posts',
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
			$capability,
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
			'edit_others_posts',
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
	 * Render Admin Dashboard.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function render_dashboard() {
		echo '<div id="srfm-dashboard-container"></div>';
	}

	/**
	 * Settings page callback.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function settings_page_callback() {
		echo '<div id="srfm-settings-container"></div>';
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
			__( 'New Form', 'sureforms' ),
			__( 'New Form', 'sureforms' ),
			'edit_others_posts',
			'add-new-form',
			[ $this, 'add_new_form_callback' ],
			2
		);
		add_submenu_page(
			'sureforms_menu',
			__( 'Entries', 'sureforms' ),
			__( 'Entries', 'sureforms' ),
			'edit_others_posts',
			SRFM_ENTRIES,
			[ $this, 'render_entries' ],
			3
		);
	}

	/**
	 * Add new form mentu item callback.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function add_new_form_callback() {
		echo '<div id="srfm-add-new-form-container"></div>';
	}

	/**
	 * Entries page callback.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public function render_entries() {
		// Render single entry view.
		// Adding the phpcs ignore nonce verification as no database operations are performed in this function, it is used to display the single entry view.
		if ( isset( $_GET['entry_id'] ) && is_numeric( $_GET['entry_id'] ) && isset( $_GET['view'] ) && 'details' === $_GET['view'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$single_entry_view = new Single_Entry();
			$single_entry_view->render();
			return;
		}

		// Render all entries view.
		$entries_table = new Entries_List_Table();
		$entries_table->prepare_items();
		echo '<div class="wrap"><h1 class="wp-heading-inline">' . esc_html__( 'Entries', 'sureforms' ) . '</h1>';
		if ( empty( $entries_table->all_entries_count ) && empty( $entries_table->trash_entries_count ) ) {
			$instance = Post_Types::get_instance();
			$instance->sureforms_render_blank_state( SRFM_ENTRIES );
			$instance->get_blank_state_styles();
			return;
		}
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="sureforms_entries">';
		$entries_table->display();
		echo '</form>';
		echo '</div>';
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
			$plugin_links = apply_filters(
				'sureforms_plugin_action_links',
				[
					'sureforms_settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=sureforms_form_settings&tab=general-settings' ) ) . '">' . esc_html__( 'Settings', 'sureforms' ) . '</a>',
				]
			);
			$links        = array_merge( $plugin_links, $links );
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

		if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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

				if ( 'edit.php' === $pagenow && ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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

		$file_prefix  = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
			$dir_name = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';
			$js_uri   = SRFM_URL . 'assets/js/' . $dir_name . '/';
			$css_uri  = SRFM_URL . 'assets/css/' . $dir_name . '/';

		/**
		 * List of the handles in which we need to add translation compatibility.
		 */
		$script_translations_handlers = [];

			/* RTL */
		if ( is_rtl() ) {
			$file_prefix .= '-rtl';
		}

		$localization_data = [
			'site_url'                => get_site_url(),
			'breadcrumbs'             => $this->get_breadcrumbs_for_current_page(),
			'sureforms_dashboard_url' => admin_url( '/admin.php?page=sureforms_menu' ),
			'plugin_version'          => SRFM_VER,
			'global_settings_nonce'   => current_user_can( 'manage_options' ) ? wp_create_nonce( 'wp_rest' ) : '',
			'is_pro_active'           => defined( 'SRFM_PRO_VER' ),
			'pro_plugin_version'      => defined( 'SRFM_PRO_VER' ) ? SRFM_PRO_VER : '',
			'pro_plugin_name'         => defined( 'SRFM_PRO_VER' ) && defined( 'SRFM_PRO_PRODUCT' ) ? SRFM_PRO_PRODUCT : 'SureForms Pro',
			'sureforms_pricing_page'  => $this->get_sureforms_website_url( 'pricing' ),
			'field_spacing_vars'      => Helper::get_css_vars(),
			'is_ver_lower_than_6_7'   => version_compare( $wp_version, '6.6.2', '<=' ),
		];

		$is_screen_sureforms_menu          = Helper::validate_request_context( 'sureforms_menu', 'page' );
		$is_screen_add_new_form            = Helper::validate_request_context( 'add-new-form', 'page' );
		$is_screen_sureforms_form_settings = Helper::validate_request_context( 'sureforms_form_settings', 'page' );
		$is_screen_sureforms_entries       = Helper::validate_request_context( SRFM_ENTRIES, 'page' );
		$is_post_type_sureforms_form       = SRFM_FORMS_POST_TYPE === $current_screen->post_type;

		if ( $is_screen_sureforms_menu || $is_post_type_sureforms_form || $is_screen_add_new_form || $is_screen_sureforms_form_settings || $is_screen_sureforms_entries ) {
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

			$localization_data['security_settings_url']    = admin_url( '/admin.php?page=sureforms_form_settings&tab=security-settings' );
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

		if ( $is_screen_sureforms_form_settings ) {
			wp_enqueue_style( SRFM_SLUG . '-settings', $css_uri . 'backend/settings' . $file_prefix . '.css', [], SRFM_VER );

			// if version is equal to or lower than 6.6.2 then add compatibility css.
			if ( version_compare( $wp_version, '6.6.2', '<=' ) ) {
				$srfm_inline_css = '
				.srfm-settings-page-container
					.components-toggle-control {
						.components-base-control__help{
							margin-left: 4em;
						}
					}
				}
				';
				wp_add_inline_style( SRFM_SLUG . '-settings', $srfm_inline_css );
			}
		}

		// Enqueue styles for the entries page.
		if ( $is_screen_sureforms_entries ) {
			$asset_handle = '-entries';
			wp_enqueue_script( SRFM_SLUG . $asset_handle, SRFM_URL . 'assets/build/entries.js', $script_info['dependencies'], SRFM_VER, true );

			$script_translations_handlers[] = SRFM_SLUG . $asset_handle;
		}

		// Admin Submenu Styles.
		wp_enqueue_style( SRFM_SLUG . '-admin', $css_uri . 'backend/admin' . $file_prefix . '.css', [], SRFM_VER );

		if ( 'edit-' . SRFM_FORMS_POST_TYPE === $current_screen->id ) {
			$asset_handle = 'page_header';

			$script_asset_path = SRFM_DIR . 'assets/build/' . $asset_handle . '.asset.php';
			$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: [
				'dependencies' => [],
				'version'      => SRFM_VER,
			];
			wp_enqueue_script( SRFM_SLUG . '-form-page-header', SRFM_URL . 'assets/build/' . $asset_handle . '.js', $script_info['dependencies'], SRFM_VER, true );
			wp_enqueue_style( SRFM_SLUG . '-form-archive-styles', $css_uri . 'form-archive-styles' . $file_prefix . '.css', [], SRFM_VER );

			$script_translations_handlers[] = SRFM_SLUG . '-form-page-header';
		}

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
			wp_enqueue_style( SRFM_SLUG . '-setting-styles', SRFM_URL . 'assets/build/' . $asset_handle . '.css', [ 'wp-components' ], SRFM_VER, 'all' );
			wp_localize_script(
				SRFM_SLUG . '-settings',
				SRFM_SLUG . '_admin',
				$localization_data
			);

			$script_translations_handlers[] = SRFM_SLUG . '-settings';
		}
		if ( 'edit-' . SRFM_FORMS_POST_TYPE === $current_screen->id ) {
			wp_enqueue_script( SRFM_SLUG . '-form-archive', $js_uri . 'form-archive' . $file_prefix . '.js', [], SRFM_VER, true );
			wp_enqueue_script( SRFM_SLUG . '-export', $js_uri . 'export' . $file_prefix . '.js', [ 'wp-i18n' ], SRFM_VER, true );
			wp_localize_script(
				SRFM_SLUG . '-export',
				SRFM_SLUG . '_export',
				[
					'ajaxurl'              => admin_url( 'admin-ajax.php' ),
					'srfm_export_nonce'    => wp_create_nonce( 'export_form_nonce' ),
					'site_url'             => get_site_url(),
					'srfm_import_endpoint' => '/wp-json/sureforms/v1/sureforms_import',
					'import_form_nonce'    => current_user_can( 'edit_posts' ) ? wp_create_nonce( 'wp_rest' ) : '',
				]
			);

			wp_enqueue_script( SRFM_SLUG . '-backend', $js_uri . 'backend' . $file_prefix . '.js', [], SRFM_VER, true );
			wp_localize_script(
				SRFM_SLUG . '-backend',
				SRFM_SLUG . '_backend',
				[
					'site_url' => get_site_url(),
				]
			);

			$script_translations_handlers[] = SRFM_SLUG . '-form-archive';
			$script_translations_handlers[] = SRFM_SLUG . '-export';
			$script_translations_handlers[] = SRFM_SLUG . '-backend';
		}

		if ( $is_screen_add_new_form ) {
			$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
			$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';

			$css_uri = SRFM_URL . 'assets/css/' . $dir_name . '/';

			/* RTL */
			if ( is_rtl() ) {
				$file_prefix .= '-rtl';
			}

			wp_enqueue_style( SRFM_SLUG . '-template-picker', $css_uri . 'template-picker' . $file_prefix . '.css', [], SRFM_VER );

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
					'preview_images_url'           => SRFM_URL . 'images/template-previews/',
					'admin_url'                    => admin_url( 'admin.php' ),
					'new_template_picker_base_url' => admin_url( 'post-new.php?post_type=sureforms_form' ),
					'capability'                   => current_user_can( 'edit_posts' ),
					'template_picker_nonce'        => current_user_can( 'edit_posts' ) ? wp_create_nonce( 'wp_rest' ) : '',
					'is_pro_active'                => defined( 'SRFM_PRO_VER' ),
					'srfm_ai_usage_details'        => AI_Helper::get_current_usage_details(),
					'is_pro_license_active'        => AI_Helper::is_pro_license_active(),
					'srfm_ai_auth_user_email'      => get_option( 'srfm_ai_auth_user_email' ),
					'pricing_page_url'             => $this->get_sureforms_website_url( 'pricing' ),
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
	 * Get SureForms Website URL.
	 *
	 * @param string $trail The URL trail to append to SureForms website URL. The parameter should not include a leading slash as the base URL already ends with a trailing slash.
	 * @since 0.0.7
	 * @return string
	 */
	public static function get_sureforms_website_url( $trail ) {
		$url = SRFM_WEBSITE;
		if ( ! empty( $trail ) && is_string( $trail ) ) {
			$url = SRFM_WEBSITE . $trail;
		}

		$url = \BSF_UTM_Analytics\Inc\Utils::get_utm_ready_link( $url, 'sureforms' );

		return esc_url( $url );
	}

	// Entries methods.

	/**
	 * Handle entry actions.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public function handle_entry_actions() {
		Entries_List_Table::process_bulk_actions();

		if ( ! isset( $_GET['page'] ) || SRFM_ENTRIES !== $_GET['page'] ) {
			return;
		}
		if ( ! isset( $_GET['entry_id'] ) || ! isset( $_GET['action'] ) ) {
			return;
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'srfm_entries_action' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'sureforms' ) );
		}
		$action   = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		$entry_id = Helper::get_integer_value( sanitize_text_field( wp_unslash( $_GET['entry_id'] ) ) );
		$view     = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
		if ( $entry_id > 0 ) {
			if ( 'read' === $action && 'details' === $view ) {
				$entry_status = Entries::get( $entry_id )['status'];
				if ( 'trash' === $entry_status ) {
					wp_die( esc_html__( 'You cannot view this entry because it is in trash.', 'sureforms' ) );
				}
			}
			Entries_List_Table::handle_entry_status( $entry_id, $action, $view );
		}
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
		$plugin_file = 'sureforms-pro/sureforms-pro.php';
		if ( ! is_plugin_active( $plugin_file ) || ! defined( 'SRFM_PRO_VER' ) ) {
			return;
		}

		if ( empty( get_current_screen() ) ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
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
			$message = '<p>' . sprintf(
				// translators: %1$s: Opening anchor tag with URL, %2$s: Closing anchor tag, %3$s: SureForms Pro Plugin Name.
				esc_html__( 'Please %1$sactivate%2$s your copy of %3$s to get new features, access support, receive update notifications, and more.', 'sureforms' ),
				'<a href="' . esc_url( $url ) . '">',
				'</a>',
				'<i>' . esc_html( $pro_plugin_name ) . '</i>'
			) . '</p>';
		}

		if ( ! version_compare( SRFM_PRO_VER, SRFM_PRO_RECOMMENDED_VER, '>=' ) ) {
			$message .= '<p>' . sprintf(
				// translators: %1$s: SureForms version, %2$s: SureForms Pro Plugin Name, %3$s: SureForms Pro Version, %4$s: Anchor tag open, %5$s: Closing anchor tag.
				esc_html__( 'SureForms %1$s requires minimum %2$s %3$s to work properly. Please update to the latest version from %4$shere%5$s.', 'sureforms' ),
				esc_html( SRFM_VER ),
				esc_html( $pro_plugin_name ),
				esc_html( SRFM_PRO_RECOMMENDED_VER ),
				'<a href=' . esc_url( admin_url( 'update-core.php' ) ) . '>',
				'</a>'
			) . '</p>';
		}

		if ( ! empty( $message ) ) {
			// Phpcs ignore comment is required as $message variable is already escaped.
			echo '<div class="notice notice-warning">' . $message . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

}
