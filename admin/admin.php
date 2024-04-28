<?php
/**
 * Admin Class.
 *
 * @package sureforms.
 */

namespace SRFM\Admin;

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
		add_action( 'admin_body_class', [ $this, 'admin_template_picker_body_class' ] );

		// this action is used to restrict Spectra's quick action bar on SureForms CPTS.
		add_action( 'uag_enable_quick_action_sidebar', [ $this, 'restrict_spectra_quick_action_bar' ] );
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
			function () {},
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
	 * Adds a settings link to the plugin action links on the plugins page.
	 *
	 * @param array  $links An array of plugin action links.
	 * @param string $file The plugin file path.
	 * @return array The updated array of plugin action links.
	 * @since 0.0.1
	 */
	public function add_settings_link( $links, $file ) {
		if ( 'sureforms/sureforms.php' === $file ) {
			$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=sureforms_form_settings&tab=general-settings' ) ) . '">' . esc_html__( 'Settings', 'sureforms' ) . '</a>';
			array_push( $links, $settings_link );
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
			} elseif ( $current_screen && 'sureforms_entry' === $current_screen->post_type ) {
				$breadcrumbs[] = [
					'title' => 'Entries',
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

		$file_prefix  = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
			$dir_name = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';
			$js_uri   = SRFM_URL . 'assets/js/' . $dir_name . '/';
			$css_uri  = SRFM_URL . 'assets/css/' . $dir_name . '/';

			/* RTL */
		if ( is_rtl() ) {
			$file_prefix .= '-rtl';
		}

		if ( SRFM_FORMS_POST_TYPE === $current_screen->post_type || 'toplevel_page_sureforms_menu' === $current_screen->base || SRFM_ENTRIES_POST_TYPE === $current_screen->post_type
		|| 'sureforms_page_sureforms_form_settings' === $current_screen->id
		) {
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
			wp_localize_script(
				SRFM_SLUG . $asset_handle,
				SRFM_SLUG . '_admin',
				apply_filters(
					SRFM_SLUG . '_admin_filter',
					[
						'site_url'                => get_site_url(),
						'breadcrumbs'             => $this->get_breadcrumbs_for_current_page(),
						'sureforms_dashboard_url' => admin_url( '/admin.php?page=sureforms_menu' ),
						'plugin_version'          => SRFM_VER,
						'global_settings_nonce'   => ( current_user_can( 'manage_options' ) ) ? wp_create_nonce( 'wp_rest' ) : '',
					]
				)
			);
			wp_enqueue_style( SRFM_SLUG . '-dashboard', SRFM_URL . 'assets/build/dashboard.css', [], SRFM_VER, 'all' );

		}

		if ( 'sureforms_page_sureforms_form_settings' === $current_screen->id ) {
			wp_enqueue_style( SRFM_SLUG . '-settings', $css_uri . 'backend/settings' . $file_prefix . '.css', [], SRFM_VER );
		}

		// Admin Submenu Styles.
		wp_enqueue_style( SRFM_SLUG . '-admin', $css_uri . 'backend/admin' . $file_prefix . '.css', [], SRFM_VER );
		wp_enqueue_style( SRFM_SLUG . '-single-form-modal', $css_uri . 'single-form-setting' . $file_prefix . '.css', [], SRFM_VER );

		if ( 'edit-' . SRFM_FORMS_POST_TYPE === $current_screen->id || 'edit-' . SRFM_ENTRIES_POST_TYPE === $current_screen->id ) {
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
		}
		if ( 'sureforms_page_' . SRFM_FORMS_POST_TYPE . '_settings' === $current_screen->base ) {
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
				[
					'site_url'                => get_site_url(),
					'breadcrumbs'             => $this->get_breadcrumbs_for_current_page(),
					'sureforms_dashboard_url' => admin_url( '/admin.php?page=sureforms_menu' ),
					'plugin_version'          => SRFM_VER,
					'global_settings_nonce'   => current_user_can( 'manage_options' ) ? wp_create_nonce( 'wp_rest' ) : '',
					'is_pro_active'           => defined( 'SRFM_PRO_VER' ),

				]
			);
		}
		if ( 'edit-' . SRFM_FORMS_POST_TYPE === $current_screen->id ) {
			wp_enqueue_script( SRFM_SLUG . '-form-archive', $js_uri . 'form-archive' . $file_prefix . '.js', [], SRFM_VER, true );
			wp_enqueue_script( SRFM_SLUG . '-export', $js_uri . 'export' . $file_prefix . '.js', [], SRFM_VER, true );
			wp_localize_script(
				SRFM_SLUG . '-export',
				SRFM_SLUG . '_export',
				[
					'ajaxurl'              => admin_url( 'admin-ajax.php' ),
					'srfm_export_nonce'    => wp_create_nonce( 'export_form_nonce' ),
					'site_url'             => get_site_url(),
					'srfm_import_endpoint' => '/wp-json/sureforms/v1/sureforms_import',
					'import_form_nonce'    => ( current_user_can( 'edit_posts' ) ) ? wp_create_nonce( 'wp_rest' ) : '',
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

		}

		if ( 'sureforms_page_add-new-form' === $current_screen->id ) {

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
				]
			);
		}
		// Quick action sidebar.
		$default_allowed_quick_sidebar_blocks = apply_filters(
			'srfm_quick_sidebar_allowed_blocks',
			[
				'srfm/input',
				'srfm/email',
				'srfm/textarea',
				'srfm/number',
				'srfm/address',
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
	}

	/**
	 * Form Template Picker Admin Body Classes
	 *
	 * @since 0.0.1
	 * @param string $classes Space separated class string.
	 */
	public function admin_template_picker_body_class( $classes = '' ) {
		$theme_builder_class = isset( $_GET['page'] ) && 'add-new-form' === $_GET['page'] ? 'srfm-template-picker' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Fetching a $_GET value, no nonce available to validate.
		$classes            .= ' ' . $theme_builder_class . ' ';

		return $classes;

	}

	/**
	 * Disable spectra's quick action bar in sureforms CPT.
	 *
	 * @param string $status current status of the quick action bar.
	 * @since x.x.x
	 * @return string
	 */
	public function restrict_spectra_quick_action_bar( $status ) {
		$screen = get_current_screen();
		if ( 'disabled' !== $status && isset( $screen->id ) && 'sureforms_form' === $screen->id ) {
			$status = 'disabled';
		}

		return $status;
	}
}
