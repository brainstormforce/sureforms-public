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
 * @since 2.13.0
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Feature registry.
 *
 * @since 2.13.0
 */
class Features {
	/**
	 * Option holding the site's switches: a map of feature key → bool.
	 * A key that is absent falls back to the feature's default, so a site
	 * that never saved the tab behaves exactly as before this option existed.
	 */
	public const OPTION = 'srfm_features_options';

	/**
	 * Option holding the blocks a site owner switched off: a list of block names.
	 * An empty or missing option means every block is on.
	 */
	public const BLOCKS_OPTION = 'srfm_disabled_blocks';

	/**
	 * Per-request cache of the filtered block rows.
	 *
	 * @var array<string,array{label:string,group:string,feature:string}>|null
	 */
	private static $blocks = null;

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
	 * Row keys:
	 * - label, description (string), group (string).
	 * - default (bool): state when the site never saved the switch.
	 * - depends_on (string[]): the feature is off whenever any of these is off.
	 * - routes (string[]): keys this feature adds to `srfm_rest_api_endpoints`.
	 *   They are dropped while it is off, for features whose classes are booted
	 *   by other code and so cannot be gated at their own boot line.
	 * - cron_hooks (string[]): scheduled hooks to clear when it is turned off.
	 * - usage_blocks (string[]): block names whose published forms are listed
	 *   as "in use" before the feature is turned off.
	 * - usage_meta (string[]): form meta keys checked the same way; a form
	 *   counts when the meta (array or JSON) has its on/off flag set.
	 *
	 * @since 2.13.0
	 * @return array<string,array<string,mixed>>
	 */
	public static function definitions() {
		if ( null !== self::$definitions ) {
			return self::$definitions;
		}

		$rows = [
			'entries'    => [
				'label'       => __( 'Entries', 'sureforms' ),
				'description' => __( 'Store form submissions and view them under SureForms → Entries. When off, notifications, confirmations, webhooks and integrations still run, but no submission is saved.', 'sureforms' ),
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
				],
				'cron_hooks'  => [ 'srfm_weekly_scheduled_events' ],
			],
			'ai_builder' => [
				'label'       => __( 'AI form builder', 'sureforms' ),
				'description' => __( 'Create forms from a prompt, in the New Form screen and the WordPress dashboard widget.', 'sureforms' ),
				'group'       => 'core',
				'routes'      => [ 'generate-form', 'map-fields', 'initiate-auth', 'handle-access-key' ],
			],
			'ottokit'    => [
				'label'       => __( 'OttoKit automations', 'sureforms' ),
				'description' => __( 'The Automations tab in form settings and the OttoKit settings page.', 'sureforms' ),
				'group'       => 'core',
			],
			'payments'   => [
				'label'        => __( 'Payments', 'sureforms' ),
				'description'  => __( 'Collect payments with Stripe: the Payment and Payment History blocks, the Payments page and the payment settings.', 'sureforms' ),
				'group'        => 'core',
				'usage_blocks' => [ 'srfm/payment' ],
			],
		];

		/**
		 * Filters the features a site owner can switch off.
		 *
		 * @since 2.13.0
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
					'label'        => $key,
					'description'  => '',
					'group'        => 'core',
					'default'      => true,
					'depends_on'   => [],
					'routes'       => [],
					'cron_hooks'   => [],
					'usage_blocks' => [],
					'usage_meta'   => [],
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
	 * @since 2.13.0
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
		 * @since 2.13.0
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
	 * @since 2.13.0
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
	 * @since 2.13.0
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
		 * @since 2.13.0
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
	 * the save would turn off a feature that published forms use and `confirm`
	 * is not set, nothing is saved and those forms are returned instead, so
	 * the owner can confirm first. Permission and nonce are checked by the
	 * `srfm-global-settings` route before this runs.
	 *
	 * @param array<mixed> $params Request params without `srfm_tab`.
	 * @since 2.13.0
	 * @return \WP_REST_Response
	 */
	public static function rest_save( $params ) {
		$values  = Helper::get_array_value( $params['features'] ?? [] );
		$blocks  = Helper::get_array_value( $params['blocks'] ?? [] );
		$confirm = filter_var( $params['confirm'] ?? false, FILTER_VALIDATE_BOOLEAN );

		if ( ! $confirm ) {
			$in_use = self::in_use_if_saved( $values );
			// Blocks this save switches off that published forms still contain.
			$newly_off = array_diff( self::disabled_after( $blocks ), self::get_disabled_blocks() );
			foreach ( $newly_off as $name ) {
				$forms = self::forms_matching( [ $name ], [] );
				if ( ! empty( $forms ) ) {
					$in_use[ $name ] = $forms;
				}
			}
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
		self::save_blocks( $blocks );

		return new \WP_REST_Response(
			[
				'data'     => __( 'Settings Saved Successfully.', 'sureforms' ),
				'features' => self::for_js(),
				'blocks'   => self::blocks_for_js(),
			]
		);
	}

	/**
	 * Resolved state of every registered feature.
	 *
	 * @since 2.13.0
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
	 * @since 2.13.0
	 * @return array<string,array<string,mixed>>
	 */
	public static function for_js() {
		$definitions = self::definitions();
		$saved       = self::get_saved();
		$out         = [];

		foreach ( $definitions as $key => $row ) {
			$blocked_by = [];
			foreach ( Helper::get_array_value( $row['depends_on'] ) as $parent ) {
				if ( is_string( $parent ) && ! self::is_enabled( $parent ) ) {
					$blocked_by[] = $parent;
				}
			}
			$out[ $key ] = [
				'label'       => $row['label'],
				'description' => $row['description'],
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
	 * @since 2.13.0
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
	 * Published forms that use a feature, for the warning shown before it is
	 * turned off.
	 *
	 * @param string $key Feature key.
	 * @since 2.13.0
	 * @return array<int,array{id:int,title:string}>
	 */
	public static function forms_using( $key ) {
		$definitions = self::definitions();
		if ( ! isset( $definitions[ $key ] ) ) {
			return [];
		}
		return self::forms_matching(
			Helper::get_array_value( $definitions[ $key ]['usage_blocks'] ),
			Helper::get_array_value( $definitions[ $key ]['usage_meta'] )
		);
	}

	/**
	 * Published forms that contain any of the blocks or have any of the metas switched on.
	 *
	 * @param array<mixed> $blocks Block names.
	 * @param array<mixed> $metas  Form meta keys.
	 * @since 2.13.0
	 * @return array<int,array{id:int,title:string}>
	 */
	public static function forms_matching( $blocks, $metas ) {
		if ( empty( $blocks ) && empty( $metas ) ) {
			return [];
		}

		$form_ids = get_posts(
			[
				'post_type'      => SRFM_FORMS_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);

		$using = [];
		foreach ( $form_ids as $form_id ) {
			$form_id = Helper::get_integer_value( $form_id );
			if ( self::form_uses( $form_id, $blocks, $metas ) ) {
				$using[] = [
					'id'    => $form_id,
					'title' => get_the_title( $form_id ),
				];
			}
		}
		return $using;
	}

	/**
	 * Whether a form uses any of the given blocks or meta flags.
	 *
	 * @param int          $form_id Form ID.
	 * @param array<mixed> $blocks  Block names.
	 * @param array<mixed> $metas   Meta keys.
	 * @since 2.13.0
	 * @return bool
	 */
	public static function form_uses( $form_id, $blocks, $metas ) {
		if ( ! empty( $blocks ) ) {
			$content = Helper::get_string_value( get_post_field( 'post_content', $form_id ) );
			foreach ( $blocks as $block ) {
				if ( is_string( $block ) && has_block( $block, $content ) ) {
					return true;
				}
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
	 * Forget cached definitions and states.
	 *
	 * @since 2.13.0
	 * @return void
	 */
	public static function reset() {
		self::$resolved    = [];
		self::$definitions = null;
		self::$blocks      = null;
	}

	/**
	 * Blocks a site owner can switch off, keyed by block name.
	 *
	 * Each row has a label, a group (free, pro or spectra) and an optional
	 * owning feature. A block with a feature has no switch of its own: it is on
	 * exactly while that feature is on. The Form block is not listed, because
	 * it is how forms are embedded on pages.
	 *
	 * @since 2.13.0
	 * @return array<string,array{label:string,group:string,feature:string}>
	 */
	public static function block_definitions() {
		if ( null !== self::$blocks ) {
			return self::$blocks;
		}

		$rows = [
			'srfm/input'            => [ 'label' => __( 'Text', 'sureforms' ) ],
			'srfm/email'            => [ 'label' => __( 'Email', 'sureforms' ) ],
			'srfm/textarea'         => [ 'label' => __( 'Textarea', 'sureforms' ) ],
			'srfm/number'           => [ 'label' => __( 'Number', 'sureforms' ) ],
			'srfm/phone'            => [ 'label' => __( 'Phone Number', 'sureforms' ) ],
			'srfm/url'              => [ 'label' => __( 'URL', 'sureforms' ) ],
			'srfm/address'          => [ 'label' => __( 'Address', 'sureforms' ) ],
			'srfm/dropdown'         => [ 'label' => __( 'Dropdown', 'sureforms' ) ],
			'srfm/multi-choice'     => [ 'label' => __( 'Multiple Choice', 'sureforms' ) ],
			'srfm/checkbox'         => [ 'label' => __( 'Checkbox', 'sureforms' ) ],
			'srfm/gdpr'             => [ 'label' => __( 'GDPR Agreement', 'sureforms' ) ],
			'srfm/inline-button'    => [ 'label' => __( 'Custom Button', 'sureforms' ) ],
			'srfm/payment'          => [
				'label'   => __( 'Payment', 'sureforms' ),
				'feature' => 'payments',
			],
			'srfm/payment-history'  => [
				'label'   => __( 'Payment History', 'sureforms' ),
				'feature' => 'payments',
			],
			'srfm/advanced-heading' => [
				'label' => __( 'Advanced Heading', 'sureforms' ),
				'group' => 'spectra',
			],
			'srfm/image'            => [
				'label' => __( 'Image', 'sureforms' ),
				'group' => 'spectra',
			],
			'srfm/icon'             => [
				'label' => __( 'Icon', 'sureforms' ),
				'group' => 'spectra',
			],
			'srfm/separator'        => [
				'label' => __( 'Separator', 'sureforms' ),
				'group' => 'spectra',
			],
		];

		/**
		 * Filters the blocks a site owner can switch off.
		 *
		 * @since 2.13.0
		 * @param array<string,array<string,string>> $rows Block rows keyed by block name.
		 */
		$rows = apply_filters( 'srfm_feature_blocks', $rows );

		self::$blocks = [];
		foreach ( Helper::get_array_value( $rows ) as $name => $row ) {
			if ( ! is_string( $name ) || 'srfm/form' === $name || ! is_array( $row ) ) {
				continue;
			}
			self::$blocks[ $name ] = [
				'label'   => Helper::get_string_value( $row['label'] ?? $name ),
				'group'   => Helper::get_string_value( $row['group'] ?? 'free' ),
				'feature' => Helper::get_string_value( $row['feature'] ?? '' ),
			];
		}

		return self::$blocks;
	}

	/**
	 * Block names switched off on the Blocks tab.
	 *
	 * @since 2.13.0
	 * @return array<int,string>
	 */
	public static function get_disabled_blocks() {
		return array_values( array_filter( Helper::get_array_value( get_option( self::BLOCKS_OPTION, [] ) ), 'is_string' ) );
	}

	/**
	 * Whether a block is on. A block nobody listed is always on.
	 *
	 * @param string $name Block name.
	 * @since 2.13.0
	 * @return bool
	 */
	public static function is_block_enabled( $name ) {
		$rows = self::block_definitions();
		if ( ! isset( $rows[ $name ] ) ) {
			return true;
		}
		if ( '' !== $rows[ $name ]['feature'] ) {
			return self::is_enabled( $rows[ $name ]['feature'] );
		}
		return ! in_array( $name, self::get_disabled_blocks(), true );
	}

	/**
	 * Unregister every switched-off block, so it renders nothing and loads no assets.
	 *
	 * Hooked on `init` after every block is registered. The editor drops the same
	 * blocks in `src/blocks/blocks.js`.
	 *
	 * @since 2.13.0
	 * @return void
	 */
	public static function unregister_disabled_blocks() {
		$registry = \WP_Block_Type_Registry::get_instance();
		foreach ( array_keys( self::block_definitions() ) as $name ) {
			if ( ! self::is_block_enabled( $name ) && $registry->is_registered( $name ) ) {
				unregister_block_type( $name );
			}
		}
	}

	/**
	 * Block rows for the Blocks tab and the editor.
	 *
	 * @since 2.13.0
	 * @return array<string,array{label:string,group:string,feature:string,switch:bool,enabled:bool}>
	 */
	public static function blocks_for_js() {
		$disabled = self::get_disabled_blocks();
		$out      = [];
		foreach ( self::block_definitions() as $name => $row ) {
			$enabled      = self::is_block_enabled( $name );
			$out[ $name ] = $row + [
				'switch'  => '' === $row['feature'] ? ! in_array( $name, $disabled, true ) : $enabled,
				'enabled' => $enabled,
			];
		}
		return $out;
	}

	/**
	 * Save block switches. Keeps listed blocks without an owning feature and
	 * boolean values; drops everything else.
	 *
	 * @param array<mixed> $values Map of block name → bool.
	 * @since 2.13.0
	 * @return void
	 */
	public static function save_blocks( $values ) {
		update_option( self::BLOCKS_OPTION, self::disabled_after( $values ), false );
	}

	/**
	 * The disabled-block list that saving these values would store.
	 *
	 * @param array<mixed> $values Map of block name → bool.
	 * @return array<int,string>
	 */
	private static function disabled_after( $values ) {
		$rows     = self::block_definitions();
		$disabled = self::get_disabled_blocks();
		foreach ( $values as $name => $on ) {
			if ( ! is_string( $name ) || ! isset( $rows[ $name ] ) || '' !== $rows[ $name ]['feature'] || ! is_bool( $on ) ) {
				continue;
			}
			$disabled = $on ? array_diff( $disabled, [ $name ] ) : array_merge( $disabled, [ $name ] );
		}
		return array_values( array_unique( $disabled ) );
	}

	/**
	 * Features this save would turn off, directly or through a dependency,
	 * with the published forms that use them.
	 *
	 * @param array<mixed> $values Submitted map of feature key → bool.
	 * @since 2.13.0
	 * @return array<string,array<int,array{id:int,title:string}>>
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
	 * @since 2.13.0
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
	 * @since 2.13.0
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
	 * @since 2.13.0
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
	 * @since 2.13.0
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
