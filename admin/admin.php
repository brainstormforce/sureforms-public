<?php
/**
 * Admin Class.
 *
 * @package sureforms.
 */

namespace SureForms\Admin;

use SureForms\Inc\Traits\Get_Instance;
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
		add_action( 'admin_menu', [ $this, 'sureforms_settings_page' ] );
		add_action( 'admin_menu', [ $this, 'sureforms_add_new_form' ] );
		add_filter( 'plugin_action_links', [ $this, 'add_settings_link' ], 10, 2 );
		add_action( 'enqueue_block_assets', [ $this, 'sureforms_enqueue_styles' ] );
		add_action( 'admin_head', [ $this, 'sureforms_enqueue_header_styles' ] );
		add_action( 'admin_body_class', array( $this, 'admin_template_picker_body_class' ) );
	}

	/**
	 * Sureforms editor header styles.
	 *
	 * @since 0.0.1
	 */
	public function sureforms_enqueue_header_styles() {
		$current_screen = get_current_screen();
		$file_prefix    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name       = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';

		$css_uri = SUREFORMS_URL . 'assets/css/' . $dir_name . '/';

		/* RTL */
		if ( is_rtl() ) {
			$file_prefix .= '-rtl';
		}

		if ( 'sureforms_form' === $current_screen->id ) {
			wp_enqueue_style( SUREFORMS_SLUG . '-editor-header-styles', $css_uri . 'header-styles' . $file_prefix . '.css', array(), SUREFORMS_VER );
		}
	}

	/**
	 * Add menu page.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function add_menu_page() {
		$capability = 'manage_options';
		$menu_slug  = 'sureforms_menu';

		$logo = file_get_contents( plugin_dir_path( SUREFORMS_FILE ) . 'images/icon.svg' );
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
			array( $this, 'render_dashboard' )
		);
	}

	/**
	 * Add Settings page.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function sureforms_settings_page() {
		add_submenu_page(
			'sureforms_menu',
			__( 'Settings', 'sureforms' ),
			__( 'Settings', 'sureforms' ),
			'edit_others_posts',
			'sureforms_form_settings',
			[ $this, 'sureforms_settings_page_callback' ],
		);

		// Get the current submenu page.
		$submenu_page = isset( $_GET['page'] ) ? $_GET['page'] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! isset( $_GET['tab'] ) && 'sureforms_form_settings' === $submenu_page ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
	public function sureforms_settings_page_callback() {
		echo '<div id="srfm-settings-container"></div>';
	}

	/**
	 * Add new form menu item.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function sureforms_add_new_form() {
		add_submenu_page(
			'sureforms_menu',
			__( 'New Form', 'sureforms' ),
			__( 'New Form', 'sureforms' ),
			'edit_others_posts',
			'add-new-form',
			[ $this, 'sureforms_add_new_form_callback' ],
			2
		);
	}

	/**
	 * Add new form mentu item callback.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function sureforms_add_new_form_callback() {
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
	public function sureforms_enqueue_styles() {
		$current_screen = get_current_screen();

		$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
		$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';

		$css_uri = SUREFORMS_URL . 'assets/css/' . $dir_name . '/';

		/* RTL */
		if ( is_rtl() ) {
			$file_prefix .= '-rtl';
		}

		// Enqueue editor styles for post and page.
		if ( SUREFORMS_FORMS_POST_TYPE === $current_screen->post_type ) {
			wp_enqueue_style( SUREFORMS_SLUG . '-block-styles', $css_uri . 'block-styles' . $file_prefix . '.css', array(), SUREFORMS_VER );
		}
		wp_enqueue_style( SUREFORMS_SLUG . '-form-selector', $css_uri . 'srfm-form-selector' . $file_prefix . '.css', array(), SUREFORMS_VER );
		wp_enqueue_style( 'srfm-editor-styles', SUREFORMS_URL . 'assets/src/blocks/editor-styles.css', [], SUREFORMS_VER, 'all' );
		wp_enqueue_style( 'srfm-common-editor', SUREFORMS_URL . 'assets/build/common-editor.css', [], SUREFORMS_VER, 'all' );
		wp_enqueue_style( 'srfm-frontend-styles', SUREFORMS_URL . 'assets/build/sureforms_backend_styles.css', [], SUREFORMS_VER, 'all' );
		wp_enqueue_style( 'flatpickr', SUREFORMS_URL . 'assets/build/flatpickr_css.css', [], SUREFORMS_VER, 'all' );
		wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], SUREFORMS_VER );
		wp_enqueue_style( 'intlTelInput', SUREFORMS_URL . 'assets/src/public/styles/dependencies/intlTelInput.min.css', [], SUREFORMS_VER );
	}

	/**
	 * Get Breadcrumbs for current page.
	 *
	 * @since 0.0.1
	 * @return array Breadcrumbs Array.
	 */
	public function get_breadcrumbs_for_current_page() {
		global $post, $pagenow;
		$breadcrumbs = array();

		if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page_title    = get_admin_page_title();
			$breadcrumbs[] = array(
				'title' => $page_title,
				'link'  => '',
			);
		} elseif ( $post && in_array( $pagenow, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
			$post_type_obj = get_post_type_object( get_post_type() );
			if ( $post_type_obj ) {
				$post_type_plural = $post_type_obj->labels->name;
				$breadcrumbs[]    = array(
					'title' => $post_type_plural,
					'link'  => admin_url( 'edit.php?post_type=' . $post_type_obj->name ),
				);

				if ( 'edit.php' === $pagenow && ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$breadcrumbs[ count( $breadcrumbs ) - 1 ]['link'] = '';
				} else {
					$breadcrumbs[] = array(
						/* Translators: Post Title. */
						'title' => sprintf( __( 'Edit %1$s', 'sureforms' ), get_the_title() ),
						'link'  => get_edit_post_link( $post->ID ),
					);
				}
			}
		} else {
			$current_screen = get_current_screen();
			if ( $current_screen && 'sureforms_form' === $current_screen->post_type ) {
				$breadcrumbs[] = array(
					'title' => 'Forms',
					'link'  => '',
				);
			} elseif ( $current_screen && 'sureforms_entry' === $current_screen->post_type ) {
				$breadcrumbs[] = array(
					'title' => 'Entries',
					'link'  => '',
				);
			} else {
				$breadcrumbs[] = array(
					'title' => '',
					'link'  => '',
				);
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

			$css_uri = SUREFORMS_URL . 'assets/css/' . $dir_name . '/';

			/* RTL */
		if ( is_rtl() ) {
			$file_prefix .= '-rtl';
		}

		if ( SUREFORMS_FORMS_POST_TYPE === $current_screen->post_type || 'toplevel_page_sureforms_menu' === $current_screen->base || SUREFORMS_ENTRIES_POST_TYPE === $current_screen->post_type ) {
			$asset_handle = 'dashboard';

			wp_enqueue_style( $asset_handle . '-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap', array(), SUREFORMS_VER );

			$script_asset_path = SUREFORMS_DIR . 'assets/build/' . $asset_handle . '.asset.php';
			$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: [
				'dependencies' => [],
				'version'      => SUREFORMS_VER,
			];
			wp_enqueue_script( 'sureforms-' . $asset_handle, SUREFORMS_URL . 'assets/build/' . $asset_handle . '.js', $script_info['dependencies'], SUREFORMS_VER, true );

			wp_localize_script( 'sureforms-' . $asset_handle, 'scIcons', [ 'path' => SUREFORMS_URL . 'assets/build/icon-assets' ] );
			wp_localize_script(
				'sureforms-' . $asset_handle,
				'sureforms_admin',
				[
					'site_url'                => get_site_url(),
					'breadcrumbs'             => $this->get_breadcrumbs_for_current_page(),
					'sureforms_dashboard_url' => admin_url( '/admin.php?page=sureforms_menu' ),
					'plugin_version'          => SUREFORMS_VER,
				]
			);
			wp_enqueue_style( 'srfm-dashboard', SUREFORMS_URL . 'assets/build/dashboard.css', [], SUREFORMS_VER, 'all' );

			// Flatpickr JS.
			wp_enqueue_script( 'flatpickr', SUREFORMS_URL . 'assets/build/flatpickr_js.js', [], SUREFORMS_VER, true );

		}

		// Admin Submenu Styles.
		wp_enqueue_style( 'srfm-submenu', SUREFORMS_URL . 'assets/src/admin/sureforms-submenu.css', array(), SUREFORMS_VER );
		wp_enqueue_style( SUREFORMS_SLUG . '-single-form-modal', $css_uri . 'single-form-setting' . $file_prefix . '.css', array(), SUREFORMS_VER );

		if ( 'edit-' . SUREFORMS_FORMS_POST_TYPE === $current_screen->id || 'edit-' . SUREFORMS_ENTRIES_POST_TYPE === $current_screen->id ) {
			$asset_handle = 'page_header';

			$script_asset_path = SUREFORMS_DIR . 'assets/build/' . $asset_handle . '.asset.php';
			$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: [
				'dependencies' => [],
				'version'      => SUREFORMS_VER,
			];
			wp_enqueue_script( 'srfm-form-page-header', SUREFORMS_URL . 'assets/build/' . $asset_handle . '.js', $script_info['dependencies'], SUREFORMS_VER, true );
			wp_enqueue_style( SUREFORMS_SLUG . '-form-archive-styles', $css_uri . 'form-archive-styles' . $file_prefix . '.css', array(), SUREFORMS_VER );
		}
		if ( 'sureforms_page_' . SUREFORMS_FORMS_POST_TYPE . '_settings' === $current_screen->base ) {
			$asset_handle = 'settings';

			$script_asset_path = SUREFORMS_DIR . 'assets/build/' . $asset_handle . '.asset.php';
			$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: [
				'dependencies' => [],
				'version'      => SUREFORMS_VER,
			];
			wp_enqueue_script( 'settings', SUREFORMS_URL . 'assets/build/' . $asset_handle . '.js', $script_info['dependencies'], SUREFORMS_VER, true );
			wp_enqueue_style( 'srfm-setting-styles', SUREFORMS_URL . 'assets/build/' . $asset_handle . '.css', [ 'wp-components' ], SUREFORMS_VER, 'all' );
			wp_localize_script(
				'settings',
				'sureforms_admin',
				[
					'site_url' => get_site_url(),
				]
			);
		}
		if ( 'edit-' . SUREFORMS_FORMS_POST_TYPE === $current_screen->id ) {
			wp_enqueue_script( 'form-archive-script', SUREFORMS_URL . 'assets/src/admin/scripts/form-archive-script.js', [], SUREFORMS_VER, true );
		}
		wp_enqueue_script( 'srfm-export', SUREFORMS_URL . 'assets/src/public/scripts/export.js', [], SUREFORMS_VER, true );
		wp_localize_script(
			'srfm-export',
			'sureforms_export',
			array(
				'ajaxurl'              => admin_url( 'admin-ajax.php' ),
				'srfm_export_nonce'    => wp_create_nonce( 'export_form_nonce' ),
				'site_url'             => get_site_url(),
				'srfm_import_endpoint' => '/wp-json/sureforms/v1/sureforms_import',
			)
		);
		// Int-tel-input JS.
		wp_enqueue_script( 'intlTelInput', SUREFORMS_URL . 'assets/src/public/scripts/dependencies/intTellnput.min.js', [], SUREFORMS_VER, true );

		if ( 'sureforms_page_add-new-form' === $current_screen->id ) {

			$file_prefix = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? '' : '.min';
			$dir_name    = defined( 'SRFM_DEBUG' ) && SRFM_DEBUG ? 'unminified' : 'minified';

			$css_uri = SUREFORMS_URL . 'assets/css/' . $dir_name . '/';

			/* RTL */
			if ( is_rtl() ) {
				$file_prefix .= '-rtl';
			}

			wp_enqueue_style( SUREFORMS_SLUG . '-template-picker', $css_uri . 'template-picker' . $file_prefix . '.css', array(), SUREFORMS_VER );

			$sureforms_admin = 'templatePicker';

			$script_asset_path = SUREFORMS_DIR . 'assets/build/' . $sureforms_admin . '.asset.php';
			$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: [
				'dependencies' => [],
				'version'      => SUREFORMS_VER,
			];
			wp_enqueue_script( 'srfm-template-picker', SUREFORMS_URL . 'assets/build/' . $sureforms_admin . '.js', $script_info['dependencies'], SUREFORMS_VER, true );

			wp_localize_script(
				'srfm-template-picker',
				'sureforms_admin',
				[
					'site_url'                     => get_site_url(),
					'plugin_url'                   => SUREFORMS_URL,
					'preview_images_url'           => SUREFORMS_URL . 'images/template-previews/',
					'admin_url'                    => admin_url( '/edit.php?post_type=sureforms_form' ),
					'new_template_picker_base_url' => admin_url( 'post-new.php?post_type=sureforms_form' ),
					'capability'                   => current_user_can( 'edit_posts' ),
				]
			);
		}
	}

	/**
	 * Form Template Picker Admin Body Classes
	 *
	 * @since x.x.x
	 * @param string $classes Space separated class string.
	 */
	public function admin_template_picker_body_class( $classes = '' ) {
		$theme_builder_class = isset( $_GET['page'] ) && 'add-new-form' === $_GET['page'] ? 'srfm-template-picker' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Fetching a $_GET value, no nonce available to validate.
		$classes            .= ' ' . $theme_builder_class . ' ';

		return $classes;

	}
}
