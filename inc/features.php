<?php
/**
 * Site-wide feature switches.
 *
 * One registry for every feature a site owner can turn off from
 * SureForms → Settings → Features. A feature that is off is not booted at
 * all: its classes are never instantiated, so it registers no menu, REST
 * route, cron job, block or asset. Turning a feature off never deletes its
 * data; turning it back on restores it as it was.
 *
 * Pro adds its own rows through the `srfm_features` filter and gates its own
 * boot lines with `Features::is_enabled()`.
 *
 * @package sureforms
 * @since 2.14.0
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Feature registry.
 *
 * @since 2.14.0
 */
class Features {
	/**
	 * Option holding the site's switches: a map of feature key → bool.
	 * A key that is absent falls back to the feature's default, so a site
	 * that never saved the tab behaves exactly as before this option existed.
	 */
	public const OPTION = 'srfm_features_options';

	/**
	 * Per-request cache of resolved states, reset when the option is saved.
	 *
	 * @var array<string,bool>
	 */
	private static $resolved = [];

	/**
	 * Per-request cache of the filtered definitions.
	 *
	 * @var array<string,array<string,mixed>>|null
	 */
	private static $definitions = null;

	/**
	 * Every feature that can be switched off.
	 *
	 * Rows hold no translated text, because modules are gated on
	 * `plugins_loaded`, before translations may load; labels() supplies it.
	 *
	 * Row keys:
	 * - group (string).
	 * - default (bool): state when the site never saved the switch.
	 * - depends_on (string[]): the feature is off whenever any of these is off.
	 * - routes (string[]): keys this feature adds to `srfm_rest_api_endpoints`.
	 *   They are dropped while it is off, for features whose classes are booted
	 *   by other code and so cannot be gated at their own boot line.
	 * - cron_hooks (string[]): scheduled hooks to clear when it is turned off.
	 * - usage_blocks (string[]): block names whose forms are listed
	 *   as "in use" before the feature is turned off.
	 * - usage_meta (string[]): form meta keys checked the same way; a form
	 *   counts when the meta (array or JSON) has its on/off flag set.
	 * - usage_attrs (string[]): block attribute names checked the same way; a
	 *   form counts when any of its blocks has one of them set to a truthy value.
	 * - admin_pages (string[]): `page` slugs of the admin screens this feature
	 *   adds. While it is off, opening one goes to Settings → Features.
	 *
	 * @since 2.14.0
	 * @return array<string,array<string,mixed>>
	 */
	public static function definitions() {
		if ( null !== self::$definitions ) {
			return self::$definitions;
		}

		$rows = [
			'entries'    => [
				'group'       => 'core',
				'routes'      => [
					'entries/list',
					'entries/read-status',
					'entries/trash',
					'entries/delete',
					'entries/export',
					'entry/(?P<id>\d+)/details',
					'entry/(?P<id>\d+)/logs',
					'database/repair-entries-table',
					'entries-chart-data',
				],
				'cron_hooks'  => [ 'srfm_weekly_scheduled_events' ],
				// Entry limits count stored entries, so they stop applying while this is off.
				'usage_meta'  => [ '_srfm_form_restriction' ],
				'admin_pages' => [ SRFM_ENTRIES ],
			],
			'ai_builder' => [
				'group'  => 'core',
				'routes' => [ 'generate-form', 'map-fields', 'initiate-auth', 'handle-access-key' ],
			],
			'ottokit'    => [
				'group' => 'core',
			],
			'payments'   => [
				'group'        => 'core',
				'usage_blocks' => [ 'srfm/payment' ],
				'admin_pages'  => [ SRFM_PAYMENTS ],
			],
		];

		/**
		 * Filters the features a site owner can switch off.
		 *
		 * @since 2.14.0
		 * @param array<string,array<string,mixed>> $rows Feature rows keyed by feature key.
		 */
		$rows = apply_filters( 'srfm_features', $rows );

		$normalised = [];
		foreach ( Helper::get_array_value( $rows ) as $key => $row ) {
			if ( ! is_string( $key ) || '' === $key || ! is_array( $row ) ) {
				continue;
			}
			$normalised[ $key ] = wp_parse_args(
				$row,
				[
					'group'        => 'core',
					'default'      => true,
					'depends_on'   => [],
					'routes'       => [],
					'cron_hooks'   => [],
					'usage_blocks' => [],
					'usage_meta'   => [],
					'usage_attrs'  => [],
					'admin_pages'  => [],
				]
			);
		}

		// Only cache once the filter can have run in full; Pro adds its rows on plugins_loaded.
		if ( did_action( 'plugins_loaded' ) ) {
			self::$definitions = $normalised;
		}

		return $normalised;
	}

	/**
	 * Whether a feature is on.
	 *
	 * A key nobody registered reads as on, so a caller can never switch a
	 * feature off by asking about it before its row exists. A feature is off
	 * when its own switch is off or when anything it depends on is off.
	 *
	 * @param string $key Feature key.
	 * @since 2.14.0
	 * @return bool
	 */
	public static function is_enabled( $key ) {
		if ( isset( self::$resolved[ $key ] ) ) {
			return self::$resolved[ $key ];
		}

		$enabled = self::resolve( $key, self::get_saved(), [] );

		/**
		 * Filters whether a feature is on.
		 *
		 * @since 2.14.0
		 * @param bool   $enabled Whether the feature is on.
		 * @param string $key     Feature key.
		 */
		$enabled = (bool) apply_filters( 'srfm_feature_enabled', $enabled, $key );

		if ( did_action( 'plugins_loaded' ) ) {
			self::$resolved[ $key ] = $enabled;
		}

		return $enabled;
	}

	/**
	 * The stored switches, limited to booleans.
	 *
	 * @since 2.14.0
	 * @return array<string,bool>
	 */
	public static function get_saved() {
		$saved = get_option( self::OPTION, [] );
		$out   = [];
		foreach ( Helper::get_array_value( $saved ) as $key => $value ) {
			if ( is_string( $key ) && is_bool( $value ) ) {
				$out[ $key ] = $value;
			}
		}
		return $out;
	}

	/**
	 * Save the switches.
	 *
	 * Only registered keys with a boolean value are kept; everything else is
	 * dropped. Turning a feature off clears its scheduled hooks; turning
	 * Entries back on reschedules the email summary when that is switched on.
	 *
	 * @param array<mixed> $values Submitted map of feature key → bool.
	 * @since 2.14.0
	 * @return bool
	 */
	public static function save( $values ) {
		$definitions = self::definitions();
		$before      = self::states();

		update_option( self::OPTION, self::merge( self::get_saved(), $values ) );
		self::reset();

		$after = self::states();
		foreach ( $definitions as $key => $row ) {
			if ( $before[ $key ] && ! $after[ $key ] ) {
				foreach ( Helper::get_array_value( $row['cron_hooks'] ) as $hook ) {
					self::unschedule( Helper::get_string_value( $hook ) );
				}
			}
		}

		if ( ! $before['entries'] && $after['entries'] ) {
			$summary = get_option( 'srfm_email_summary_settings_options', [] );
			if ( is_array( $summary ) && ! empty( $summary['srfm_email_summary'] ) ) {
				Global_Settings\Email_Summary::schedule_weekly_entries_email();
			}
		}

		/**
		 * Fires after the feature switches are saved.
		 *
		 * @since 2.14.0
		 * @param array<string,bool> $before States before saving.
		 * @param array<string,bool> $after  States after saving.
		 */
		do_action( 'srfm_features_saved', $before, $after );

		return true;
	}

	/**
	 * Save the switches from the Features settings tab.
	 *
	 * Expects `features` (map of key → bool) and an optional `confirm`. When
	 * the save would turn off a feature that forms use and `confirm`
	 * is not set, nothing is saved and those forms are returned instead, so
	 * the owner can confirm first. Permission and nonce are checked by the
	 * `srfm-global-settings` route before this runs.
	 *
	 * @param array<mixed> $params Request params without `srfm_tab`.
	 * @since 2.14.0
	 * @return \WP_REST_Response|\WP_Error WP_Error (400) when `features` is not a map.
	 */
	public static function rest_save( $params ) {
		if ( ! isset( $params['features'] ) || ! is_array( $params['features'] ) ) {
			return new \WP_Error( 'srfm_invalid_features', __( 'Features must be a map of feature key to on or off.', 'sureforms' ), [ 'status' => 400 ] );
		}
		$values  = $params['features'];
		$confirm = filter_var( $params['confirm'] ?? false, FILTER_VALIDATE_BOOLEAN );

		if ( ! $confirm ) {
			$in_use = self::in_use_if_saved( $values );
			if ( ! empty( $in_use ) ) {
				return new \WP_REST_Response(
					[
						'needs_confirmation' => true,
						'in_use'             => $in_use,
					]
				);
			}
		}

		self::save( $values );

		return new \WP_REST_Response(
			[
				'data'     => __( 'Settings Saved Successfully.', 'sureforms' ),
				'features' => self::for_js(),
			]
		);
	}

	/**
	 * Resolved state of every registered feature.
	 *
	 * @since 2.14.0
	 * @return array<string,bool>
	 */
	public static function states() {
		$states = [];
		foreach ( array_keys( self::definitions() ) as $key ) {
			$states[ $key ] = self::is_enabled( $key );
		}
		return $states;
	}

	/**
	 * Data for the admin and editor scripts: every row with its saved switch,
	 * its resolved state, and the features that keep it off.
	 *
	 * @since 2.14.0
	 * @return array<string,array<string,mixed>>
	 */
	public static function for_js() {
		$definitions = self::definitions();
		$saved       = self::get_saved();
		$labels      = self::labels();
		$out         = [];

		foreach ( $definitions as $key => $row ) {
			$blocked_by = [];
			foreach ( Helper::get_array_value( $row['depends_on'] ) as $parent ) {
				if ( is_string( $parent ) && ! self::is_enabled( $parent ) ) {
					$blocked_by[] = $parent;
				}
			}
			$out[ $key ] = [
				'label'       => Helper::get_string_value( $labels[ $key ]['label'] ?? $key ),
				'description' => Helper::get_string_value( $labels[ $key ]['description'] ?? '' ),
				'group'       => $row['group'],
				'depends_on'  => array_values( Helper::get_array_value( $row['depends_on'] ) ),
				'switch'      => $saved[ $key ] ?? (bool) $row['default'],
				'enabled'     => self::is_enabled( $key ),
				'blocked_by'  => $blocked_by,
			];
		}

		return $out;
	}

	/**
	 * Drop the REST endpoints of features that are off.
	 *
	 * Hooked late on `srfm_rest_api_endpoints` so it also covers endpoints Pro
	 * adds through the same filter.
	 *
	 * @param array<mixed> $endpoints Endpoints keyed by route.
	 * @since 2.14.0
	 * @return array<mixed>
	 */
	public static function filter_endpoints( $endpoints ) {
		foreach ( self::definitions() as $key => $row ) {
			if ( self::is_enabled( $key ) ) {
				continue;
			}
			foreach ( Helper::get_array_value( $row['routes'] ) as $route ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}

	/**
	 * Forms that use a feature, for the warning shown before it is turned off.
	 *
	 * Each form also says what it uses (`reason`), from the feature's `usage` label.
	 *
	 * @param string $key Feature key.
	 * @since 2.14.0
	 * @return array<int,array{id:int,title:string,edit_url:string,status:string,reason:string}>
	 */
	public static function forms_using( $key ) {
		$definitions = self::definitions();
		if ( ! isset( $definitions[ $key ] ) ) {
			return [];
		}
		$labels = self::labels();
		$reason = Helper::get_string_value( $labels[ $key ]['usage'] ?? '' );
		$forms  = self::forms_matching(
			Helper::get_array_value( $definitions[ $key ]['usage_blocks'] ),
			Helper::get_array_value( $definitions[ $key ]['usage_meta'] ),
			Helper::get_array_value( $definitions[ $key ]['usage_attrs'] )
		);
		foreach ( $forms as $i => $form ) {
			$forms[ $i ]['reason'] = $reason;
		}
		return $forms;
	}

	/**
	 * Forms (any status but trash and auto-draft) that contain any of the blocks,
	 * have any of the metas switched on, or have a block with any of the attributes set.
	 *
	 * @param array<mixed> $blocks Block names.
	 * @param array<mixed> $metas  Form meta keys.
	 * @param array<mixed> $attrs  Block attribute names.
	 * @since 2.14.0
	 * @return array<int,array{id:int,title:string,edit_url:string,status:string}>
	 */
	public static function forms_matching( $blocks, $metas, $attrs = [] ) {
		if ( empty( $blocks ) && empty( $metas ) && empty( $attrs ) ) {
			return [];
		}

		$form_ids = get_posts(
			[
				'post_type'      => SRFM_FORMS_POST_TYPE,
				// Unpublished forms are listed too: they would break once published.
				'post_status'    => [ 'publish', 'future', 'draft', 'pending', 'private' ],
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);

		$using = [];
		foreach ( $form_ids as $form_id ) {
			$form_id = Helper::get_integer_value( $form_id );
			if ( self::form_uses( $form_id, $blocks, $metas, $attrs ) ) {
				$using[] = [
					'id'       => $form_id,
					'title'    => get_the_title( $form_id ),
					// Empty when the current user cannot edit the form.
					'edit_url' => (string) get_edit_post_link( $form_id, 'raw' ),
					'status'   => (string) get_post_status( $form_id ),
				];
			}
		}
		return $using;
	}

	/**
	 * Whether a form uses any of the given blocks, meta flags or block attributes.
	 *
	 * @param int          $form_id Form ID.
	 * @param array<mixed> $blocks  Block names.
	 * @param array<mixed> $metas   Meta keys.
	 * @param array<mixed> $attrs   Block attribute names.
	 * @since 2.14.0
	 * @return bool
	 */
	public static function form_uses( $form_id, $blocks, $metas, $attrs = [] ) {
		if ( ! empty( $blocks ) || ! empty( $attrs ) ) {
			$content = Helper::get_string_value( get_post_field( 'post_content', $form_id ) );
			// A block that renders from a synced pattern is part of the form.
			$visited = [];
			$content = self::with_pattern_content( $content, $visited );
			foreach ( $blocks as $block ) {
				if ( is_string( $block ) && has_block( $block, $content ) ) {
					return true;
				}
			}
			if ( ! empty( $attrs ) && self::has_block_attr( parse_blocks( $content ), $attrs ) ) {
				return true;
			}
		}
		foreach ( $metas as $meta_key ) {
			$meta = get_post_meta( $form_id, Helper::get_string_value( $meta_key ), true );
			if ( is_string( $meta ) && '' !== $meta && in_array( $meta[0], [ '{', '[' ], true ) ) {
				$meta = json_decode( $meta, true );
			}
			if ( self::meta_is_on( $meta ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Send a request for the admin screen of a feature that is off to
	 * Settings → Features, instead of WordPress's generic permission error.
	 *
	 * Hooked on `admin_page_access_denied`, which WordPress fires only for a
	 * `page` that is not registered for the current user, so it never takes over
	 * a screen that exists. Settings checks its own capability.
	 *
	 * @since 2.14.0
	 * @return void
	 */
	public static function redirect_disabled_page() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only picks where to redirect; nothing is changed.
		if ( '' === $page ) {
			return;
		}
		foreach ( self::definitions() as $key => $row ) {
			if ( in_array( $page, Helper::get_array_value( $row['admin_pages'] ), true ) && ! self::is_enabled( $key ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=sureforms_form_settings&tab=features-settings' ) );
				exit;
			}
		}
	}

	/**
	 * Forget cached definitions and states.
	 *
	 * @since 2.14.0
	 * @return void
	 */
	public static function reset() {
		self::$resolved    = [];
		self::$definitions = null;
	}

	/**
	 * Label and description of each feature, for the Features tab.
	 *
	 * Kept apart from definitions() so that gating modules on `plugins_loaded`
	 * never loads translations early. Only read on admin screens, after `init`.
	 *
	 * `usage` (optional) says what a form uses, for the warning shown before the
	 * feature is turned off.
	 *
	 * @since 2.14.0
	 * @return array<string,array{label:string,description:string,usage?:string}>
	 */
	public static function labels() {
		$labels = [
			'entries'    => [
				'label'       => __( 'Entries', 'sureforms' ),
				'description' => __( 'Turning this off stops saving new entries, so entry limits stop applying. Old ones are kept.', 'sureforms' ),
				'usage'       => __( 'has an entry limit', 'sureforms' ),
			],
			'ai_builder' => [
				'label'       => __( 'AI form builder', 'sureforms' ),
				'description' => __( 'Turning this off hides Create with AI on the New Form screen and the AI dashboard widget.', 'sureforms' ),
			],
			'ottokit'    => [
				'label'       => __( 'OttoKit automations', 'sureforms' ),
				'description' => __( 'Turning this off hides the Automations tab and OttoKit page.', 'sureforms' ),
			],
			'payments'   => [
				'label'       => __( 'Payments', 'sureforms' ),
				'description' => __( 'Turning this off removes payment fields and pauses paid forms.', 'sureforms' ),
				'usage'       => __( 'has a payment field', 'sureforms' ),
			],
		];

		/**
		 * Filters the label and description shown for each feature.
		 *
		 * @since 2.14.0
		 * @param array<string,array<string,string>> $labels Keyed by feature key.
		 */
		return Helper::get_array_value( apply_filters( 'srfm_feature_labels', $labels ) );
	}

	/**
	 * The content followed by the content of every synced pattern it references,
	 * at any depth, each pattern once. Like render_block_core_block(), a pattern
	 * that is not published or has a password adds nothing.
	 *
	 * @param string           $content Block content.
	 * @param array<int, true> $visited Pattern IDs already expanded, keyed by ID.
	 * @since 2.14.0
	 * @return string
	 */
	private static function with_pattern_content( $content, &$visited ) {
		if ( ! has_block( 'core/block', $content ) ) {
			return $content;
		}
		$stack = parse_blocks( $content );
		while ( ! empty( $stack ) ) {
			$block = array_pop( $stack );
			if ( ! is_array( $block ) ) {
				continue;
			}
			$ref = absint( $block['attrs']['ref'] ?? 0 );
			if ( 'core/block' === ( $block['blockName'] ?? '' ) && $ref && ! isset( $visited[ $ref ] ) ) {
				$visited[ $ref ] = true;
				$pattern         = get_post( $ref );
				if (
					$pattern instanceof \WP_Post
					&& 'wp_block' === $pattern->post_type
					&& 'publish' === $pattern->post_status
					&& empty( $pattern->post_password )
				) {
					$content .= self::with_pattern_content( $pattern->post_content, $visited );
				}
			}
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				array_push( $stack, ...$block['innerBlocks'] );
			}
		}
		return $content;
	}

	/**
	 * Whether any block, at any depth, has one of the attributes set to a truthy value.
	 *
	 * @param array<mixed> $blocks Parsed blocks.
	 * @param array<mixed> $attrs  Block attribute names.
	 * @since 2.14.0
	 * @return bool
	 */
	private static function has_block_attr( $blocks, $attrs ) {
		$stack = $blocks;
		while ( ! empty( $stack ) ) {
			$block = array_pop( $stack );
			if ( ! is_array( $block ) ) {
				continue;
			}
			foreach ( $attrs as $attr ) {
				if ( is_string( $attr ) && ! empty( $block['attrs'][ $attr ] ) ) {
					return true;
				}
			}
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				array_push( $stack, ...$block['innerBlocks'] );
			}
		}
		return false;
	}

	/**
	 * Features this save would turn off, directly or through a dependency,
	 * with the forms that use them.
	 *
	 * @param array<mixed> $values Submitted map of feature key → bool.
	 * @since 2.14.0
	 * @return array<string,array<int,array{id:int,title:string,edit_url:string,status:string,reason:string}>>
	 */
	private static function in_use_if_saved( $values ) {
		$saved  = self::get_saved();
		$next   = self::merge( $saved, $values );
		$in_use = [];

		// Includes features turned off only because something they depend on is.
		foreach ( array_keys( self::definitions() ) as $key ) {
			if ( ! self::resolve( $key, $saved, [] ) || self::resolve( $key, $next, [] ) ) {
				continue;
			}
			$forms = self::forms_using( $key );
			if ( ! empty( $forms ) ) {
				$in_use[ $key ] = $forms;
			}
		}
		return $in_use;
	}

	/**
	 * Whether a feature's form meta says it is switched on for that form.
	 *
	 * Reads the on/off flag the settings blobs use (`status`, `enabled`,
	 * `enable`, `is_cf_enabled`), and for a list (e.g. several PDFs) whether
	 * any item is on. A scalar counts when it is truthy and not "false".
	 *
	 * @param mixed $meta Meta value, JSON already decoded.
	 * @since 2.14.0
	 * @return bool
	 */
	private static function meta_is_on( $meta ) {
		if ( ! is_array( $meta ) ) {
			return ! empty( $meta ) && 'false' !== $meta;
		}
		foreach ( [ 'status', 'enabled', 'enable', 'is_cf_enabled' ] as $flag ) {
			if ( array_key_exists( $flag, $meta ) ) {
				return filter_var( $meta[ $flag ], FILTER_VALIDATE_BOOLEAN );
			}
		}
		if ( isset( $meta[0] ) ) {
			foreach ( $meta as $item ) {
				if ( is_array( $item ) && self::meta_is_on( $item ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Apply submitted switches over the saved ones, keeping only registered
	 * keys with a boolean-like value.
	 *
	 * @param array<string,bool> $saved  Saved switches.
	 * @param array<mixed>       $values Submitted map of feature key → bool.
	 * @since 2.14.0
	 * @return array<string,bool>
	 */
	private static function merge( $saved, $values ) {
		$definitions = self::definitions();
		foreach ( $values as $key => $value ) {
			if ( ! is_string( $key ) || ! isset( $definitions[ $key ] ) ) {
				continue;
			}
			$bool = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
			if ( null !== $bool ) {
				$saved[ $key ] = $bool;
			}
		}
		return $saved;
	}

	/**
	 * Resolve a feature against its own switch and its dependencies.
	 *
	 * @param string             $key   Feature key.
	 * @param array<string,bool> $saved Switches to resolve against.
	 * @param array<string>      $seen  Keys already on the path, to stop a dependency loop.
	 * @since 2.14.0
	 * @return bool
	 */
	private static function resolve( $key, $saved, $seen ) {
		$definitions = self::definitions();
		if ( ! isset( $definitions[ $key ] ) ) {
			return true;
		}
		if ( in_array( $key, $seen, true ) ) {
			return true;
		}
		$seen[] = $key;

		if ( ! ( $saved[ $key ] ?? (bool) $definitions[ $key ]['default'] ) ) {
			return false;
		}

		foreach ( Helper::get_array_value( $definitions[ $key ]['depends_on'] ) as $parent ) {
			if ( is_string( $parent ) && ! self::resolve( $parent, $saved, $seen ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Clear a scheduled hook from both Action Scheduler and WP-Cron.
	 *
	 * @param string $hook Hook name.
	 * @since 2.14.0
	 * @return void
	 */
	private static function unschedule( $hook ) {
		if ( '' === $hook ) {
			return;
		}
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( $hook );
		}
		wp_clear_scheduled_hook( $hook );
	}
}
