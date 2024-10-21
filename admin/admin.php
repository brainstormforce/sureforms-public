<?php
/**
 * Admin Class.
 *
 * @package sureforms.
 */

namespace SRFM\Admin;

use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\AI_Form_Builder\AI_Helper;
use SRFM\Inc\Helper;
use SRFM\Admin\Views\Single_Entry;
use SRFM\Admin\Views\Entries_List_Table;
use SRFM\Inc\Post_Types;
use SRFM\Inc\Database\Tables\Entries;

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

		add_action( 'current_screen', [ $this, 'enable_gutenberg_for_sureforms' ], 100 );

		// Handle entry actions.
		add_action( 'admin_init', [ $this, 'handle_entry_actions' ] );
		add_action( 'admin_notices', [ $this, 'entries_migration_notice' ] );
		add_action( 'admin_notices', [ Entries_List_Table::class, 'display_bulk_action_notice' ] );
	}

	/**
	 * Print notice to inform users about entries database migration.
	 *
	 * @since 0.0.13
	 * @return void
	 */
	public function entries_migration_notice() {
		$dismiss = get_option( 'srfm_dismiss_entries_migration_notice' );
		if ( 'hide' === $dismiss || ! $dismiss ) {
			// If we are here then it means user has either dismissed the notice 'hide' or user has a fresh setup.
			return;
		}

		// Show notice for users coming from a version lower than 0.0.13 and have legacy entries.

		$ajaxurl = add_query_arg(
			[
				'action'   => 'sureforms_dismiss_plugin_notice',
				'security' => wp_create_nonce( 'srfm_notice_dismiss_nonce' ),
			],
			admin_url( 'admin-ajax.php' )
		);
		?>
		<!-- Adding this internal style to maintain notice styling without worrying about conditional loading of the css files. -->
		<style>
		.srfm-plugin-notice-container {
			display: flex;
			align-items: center;
			gap: 20px;
			padding: 10px 0;
		}
		.srfm-plugin-notice--logo svg {
			width: 60px;
			height: 60px;
		}
		.srfm-plugin-notice--content .srfm-plugin-notice--title {
			margin: 0;
			font-size: 20px;
			line-height: 1.5;
		}
		.srfm-plugin-notice--content .srfm-plugin-notice--message {
			margin-top: 4px;
			padding: 0;
		}
		</style>
		<div class="notice notice-warning is-dismissible srfm-plugin-notice">
			<div class="srfm-plugin-notice-container">
				<div class="srfm-plugin-notice--logo">
					<svg width="500" height="500" viewBox="0 0 500 500" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M250 500C388.072 500 500 388.071 500 250C500 111.929 388.072 0 250 0C111.929 0 0 111.929 0 250C0 388.071 111.929 500 250 500ZM251.076 125C231.002 125 203.224 136.48 189.028 150.641L150.477 189.103H342.635L406.887 125H251.076ZM310.648 349.359C296.454 363.52 268.673 375 248.599 375H92.7892L157.043 310.897H349.199L310.648 349.359ZM373.1 221.154H118.42L106.39 233.173C77.9043 258.814 86.3526 278.846 126.246 278.846H381.615L393.649 266.827C421.859 241.336 412.993 221.154 373.1 221.154Z" fill="#D54407"/>
					</svg>
				</div>
				<div class="srfm-plugin-notice--content">
					<h3 class="srfm-plugin-notice--title"><?php esc_html_e( 'SureForms - Important Update Notice', 'sureforms' ); ?></h3>
					<p class="srfm-plugin-notice--message"><strong><?php esc_html_e( "From version 0.0.13 we're migrating to a custom database to enhance SureForms' performance and features.", 'sureforms' ); ?></strong></p>
					<p class="srfm-plugin-notice--message"><?php esc_html_e( 'This step is necessary and irreversible and your current existing entries will be lost. Thank you for your understanding!', 'sureforms' ); ?></p>
				</div>
			</div>
		</div>
		<script>
			(function() {
				window.addEventListener('load', function() {
					const dismissNotice = document.querySelector('.is-dismissible.srfm-plugin-notice .notice-dismiss');

					dismissNotice.addEventListener('click', function() {
						fetch('<?php echo esc_url_raw( $ajaxurl ); ?>');
					});
				});
			}());
		</script>
		<?php
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
		add_submenu_page(
			'sureforms_menu',
			__( 'Entries', 'sureforms' ),
			__( 'Entries', 'sureforms' ),
			'edit_others_posts',
			'sureforms_entries',
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
		echo '<div class="wrap"><h1 class="wp-heading-inline">Entries</h1>';
		if ( 0 >= $entries_table->all_entries_count && 0 >= $entries_table->trash_count ) {
			$instance = Post_Types::get_instance();
			$instance->sureforms_render_blank_state( SRFM_ENTRIES_POST_TYPE );
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

		$localization_data = [
			'site_url'                => get_site_url(),
			'breadcrumbs'             => $this->get_breadcrumbs_for_current_page(),
			'sureforms_dashboard_url' => admin_url( '/admin.php?page=sureforms_menu' ),
			'plugin_version'          => SRFM_VER,
			'global_settings_nonce'   => current_user_can( 'manage_options' ) ? wp_create_nonce( 'wp_rest' ) : '',
			'is_pro_active'           => defined( 'SRFM_PRO_VER' ),
			'pro_plugin_version'      => defined( 'SRFM_PRO_VER' ) ? SRFM_PRO_VER : '',
			'sureforms_pricing_page'  => $this->get_sureforms_website_url( 'pricing' ),
			'field_spacing_vars'      => Helper::get_css_vars(),
		];

		if ( class_exists( 'SRFM_PRO\Admin\Licensing' ) ) {
			$license_active                         = \SRFM_PRO\Admin\Licensing::is_license_active();
			$localization_data['is_license_active'] = $license_active;
		}

		if ( SRFM_FORMS_POST_TYPE === $current_screen->post_type || 'toplevel_page_sureforms_menu' === $current_screen->base || SRFM_ENTRIES_POST_TYPE === $current_screen->post_type
		|| 'sureforms_page_sureforms_form_settings' === $current_screen->id || 'sureforms_page_sureforms_entries' === $current_screen->id
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

			$localization_data['security_settings_url'] = admin_url( '/admin.php?page=sureforms_form_settings&tab=security-settings' );
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

		if ( 'sureforms_page_sureforms_form_settings' === $current_screen->id ) {
			wp_enqueue_style( SRFM_SLUG . '-settings', $css_uri . 'backend/settings' . $file_prefix . '.css', [], SRFM_VER );
		}

		// Enqueue styles for the entries page.
		if ( 'sureforms_page_sureforms_entries' === $current_screen->id ) {
			$asset_handle = '-entries';
			wp_enqueue_style( SRFM_SLUG . $asset_handle, $css_uri . 'backend/entries' . $file_prefix . '.css', [], SRFM_VER );
			wp_enqueue_script( SRFM_SLUG . $asset_handle, SRFM_URL . 'assets/build/entries.js', $script_info['dependencies'], SRFM_VER, true );
		}

		// Admin Submenu Styles.
		wp_enqueue_style( SRFM_SLUG . '-admin', $css_uri . 'backend/admin' . $file_prefix . '.css', [], SRFM_VER );

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
				$localization_data
			);
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
					'srfm_ai_usage_details'        => AI_Helper::get_current_usage_details(),
					'is_pro_license_active'        => AI_Helper::is_pro_license_active(),
					'srfm_ai_auth_user_email'      => get_option( 'srfm_ai_auth_user_email' ),
					'pricing_page_url'             => $this->get_sureforms_website_url( 'pricing' ),
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

		/**
		 * Enqueuing SureTriggers Integration script.
		 * This script loads suretriggers iframe in Intergations tab.
		 */
		if ( SRFM_FORMS_POST_TYPE === $current_screen->post_type ) {
			wp_enqueue_script( SRFM_SLUG . '-suretriggers-integration', SRFM_SURETRIGGERS_INTERGATION_BASE_URL . 'js/v2/embed.js', [], SRFM_VER, true );
		}
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
		if ( isset( $_GET['entry'] ) && isset( $_GET['action'] ) ) {
			Entries_List_Table::process_bulk_actions();
			return;
		}
		if ( ! isset( $_GET['page'] ) || 'sureforms_entries' !== $_GET['page'] ) {
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

}
