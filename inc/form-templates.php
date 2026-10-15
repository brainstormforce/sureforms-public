<?php
/**
 * Prebuilt form templates.
 *
 * A small, curated set of everyday forms that ship inside the plugin, so a site
 * owner can start from a working form without spending an AI generation. Each
 * template is described in the same field schema the AI builder returns and is
 * turned into blocks by Field_Mapping, so a template form is built exactly like
 * an AI form and only uses blocks the free plugin can render.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc;

use SRFM\Admin\Analytics;
use SRFM\Inc\AI_Form_Builder\Field_Mapping;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Form_Templates\Catalog;
use SRFM\Inc\Traits\Get_Instance;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Form templates registry and REST routes.
 *
 * @since x.x.x
 */
class Form_Templates {
	use Get_Instance;

	/**
	 * Meta key recording how a form was created (`template`, `ai`).
	 *
	 * @since x.x.x
	 */
	public const CREATED_VIA_META = '_srfm_created_via';

	/**
	 * Meta key recording which template a form was created from.
	 *
	 * @since x.x.x
	 */
	public const TEMPLATE_ID_META = '_srfm_template_id';

	/**
	 * Query var that renders a template preview page.
	 *
	 * @since x.x.x
	 */
	public const PREVIEW_QUERY_VAR = 'srfm_template_preview';

	/**
	 * Post ID of the in-memory form a preview renders. Never written to the database.
	 *
	 * @since x.x.x
	 */
	private const PREVIEW_POST_ID = 2147483600;

	/**
	 * Plan tiers a template can require, lowest first.
	 *
	 * @since x.x.x
	 */
	public const TIERS = [ 'free', 'starter', 'pro', 'business' ];

	/**
	 * Template keys that SureForms Pro builds into the form (through the
	 * `srfm_form_template_content` and `srfm_form_template_metas` filters),
	 * with the tier whose Pro build handles each.
	 *
	 * @since x.x.x
	 */
	public const PRO_FEATURE_KEYS = [
		'conditions'       => 'starter',
		'confirmations'    => 'starter',
		'email_conditions' => 'starter',
		'attach_uploads'   => 'starter',
		'restrictions'     => 'starter',
		'save_resume'      => 'pro',
		'survey'           => 'pro',
		'conversational'   => 'pro',
		'quiz'             => 'business',
		'partial_entries'  => 'business',
		'cpt'              => 'business',
		'registration'     => 'business',
		'entry_limit'      => 'business',
		'pdf'              => 'business',
	];

	/**
	 * Lowest `SRFM_PRO_TEMPLATE_API` that builds each of the PRO_FEATURE_KEYS.
	 * Version 1 builds the Starter and Pro keys, version 2 adds the Business
	 * ones. A paid template declaring a key is locked (reason `update`) while
	 * the active SureForms Pro reports a lower version.
	 *
	 * @since x.x.x
	 */
	public const PRO_FEATURE_API = [
		'conditions'       => 1,
		'confirmations'    => 1,
		'email_conditions' => 1,
		'attach_uploads'   => 1,
		'restrictions'     => 1,
		'save_resume'      => 1,
		'survey'           => 1,
		'conversational'   => 1,
		'quiz'             => 2,
		'partial_entries'  => 2,
		'cpt'              => 2,
		'registration'     => 2,
		'entry_limit'      => 2,
		'pdf'              => 2,
	];

	/**
	 * The "Included" feature each Pro template key adds.
	 *
	 * @since x.x.x
	 */
	private const FEATURE_BY_KEY = [
		'conditions'      => 'conditional_logic',
		'save_resume'     => 'save_resume',
		'survey'          => 'survey',
		'conversational'  => 'conversational',
		'quiz'            => 'quiz',
		'partial_entries' => 'partial_entries',
		'cpt'             => 'post_submission',
		'registration'    => 'user_registration',
		'pdf'             => 'pdf',
	];

	/**
	 * Memoised template list, keyed by id.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private static $templates = null;

	/**
	 * ID of the in-memory form rendered by this preview request.
	 *
	 * @var int|null
	 */
	private static $preview_post_id = null;

	/**
	 * Constructor
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'template_redirect', [ $this, 'maybe_render_preview' ], 1 );
	}

	/**
	 * Register the template REST routes.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'sureforms/v1',
			'/form-templates',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_list_templates' ],
				'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
			]
		);

		register_rest_route(
			'sureforms/v1',
			'/form-templates/create',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_create_form' ],
				'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				'args'                => [
					'template_id'            => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					],
					// Style chosen in the preview; cleaned by sanitize_style().
					'style'                  => [
						'type'     => 'object',
						'required' => false,
					],
					// Thank-you message and email text edited in the preview; cleaned by sanitize_messages().
					'messages'               => [
						'type'     => 'object',
						'required' => false,
					],
					// Turns on the site-wide honeypot while creating the form.
					'enable_spam_protection' => [
						'type'    => 'boolean',
						'default' => false,
					],
				],
			]
		);

		register_rest_route(
			'sureforms/v1',
			'/form-templates/request',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_record_demand' ],
				'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				'args'                => [
					// What was asked for. Only sent with a `request`.
					'query' => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					// `request` when asked for, `search` for a search with no results
					// (counted only, without its text).
					'type'  => [
						'type'    => 'string',
						'enum'    => [ 'request', 'search' ],
						'default' => 'request',
					],
				],
			]
		);

		register_rest_route(
			'sureforms/v1',
			'/form-templates/swap',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_record_swap' ],
				'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				'args'                => [
					'form_id'     => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
					'template_id' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);

		register_rest_route(
			'sureforms/v1',
			'/form-templates/(?P<template_id>[a-z0-9-]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_get_template_content' ],
				'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				'args'                => [
					'form_id' => [
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					],
					// Style chosen in the preview, as JSON; cleaned by sanitize_style().
					'style'   => [
						'type'     => 'string',
						'required' => false,
					],
				],
			]
		);
	}

	/**
	 * All templates, keyed by id.
	 *
	 * @since x.x.x
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_templates() {
		if ( null !== self::$templates ) {
			return self::$templates;
		}

		/**
		 * Filters the prebuilt form templates.
		 *
		 * Each template is an array with `id`, `title`, `description`, `category`,
		 * `icon`, `fields` (the AI builder field schema), and optional
		 * `confirmation` ([ 'heading', 'text' ]), `submit_text`, `email_subject`,
		 * `email_intro`, `autoresponder` ([ 'subject', 'lines', 'greeting' ]),
		 * `steps` (step titles for multi-step forms), `features` (extra "Included"
		 * feature ids, such as `calculation`) and the Pro keys in PRO_FEATURE_KEYS.
		 *
		 * `requires` is the plan a template needs: `free` (default), `starter`,
		 * `pro` or `business`; `is_pro => true` is read as `starter`. A paid
		 * template can also set `min_pro_version`, the lowest SureForms Pro version
		 * it works with.
		 *
		 * A field may carry `pro` (schema keys that replace its own with SureForms
		 * Pro), `pro_only` (only added with Pro), `pro_tier` (the plan those need,
		 * `starter` by default; a field with `pro_tier` and no `pro` is only added
		 * from that plan) and `blockAttributes` (extra block attributes, such as
		 * `minValue` and `maxValue` on a number field).
		 *
		 * @since x.x.x
		 * @param array<int, array<string, mixed>> $templates Templates.
		 */
		$templates = apply_filters( 'srfm_form_templates', self::default_templates() );

		self::$templates = [];
		foreach ( (array) $templates as $template ) {
			if ( ! is_array( $template ) || empty( $template['id'] ) || empty( $template['fields'] ) || ! is_array( $template['fields'] ) ) {
				continue;
			}
			$id                     = sanitize_key( Helper::get_string_value( $template['id'] ) );
			$template['id']         = $id;
			self::$templates[ $id ] = $template;
		}

		return self::$templates;
	}

	/**
	 * One template by id.
	 *
	 * @param string $template_id Template id.
	 * @since x.x.x
	 * @return array<string, mixed>|null
	 */
	public static function get_template( $template_id ) {
		$templates = self::get_templates();
		return $templates[ sanitize_key( $template_id ) ] ?? null;
	}

	/**
	 * Template summaries for the picker: everything except the field schema.
	 *
	 * @since x.x.x
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_templates_for_js() {
		$list = [];
		$tier = Helper::get_pro_tier();
		foreach ( self::get_templates() as $template ) {
			$resolved    = self::resolve_fields( $template, $tier );
			$fields      = array_values(
				array_filter(
					array_map(
						static fn( $field ) => ! in_array( $field['fieldType'] ?? '', [ 'gdpr', 'page-break' ], true ) ? Helper::get_string_value( $field['label'] ?? '' ) : '',
						$resolved
					)
				)
			);
			$features    = self::get_features( $template, $resolved, $tier );
			$requires    = self::get_required_tier( $template );
			$lock_reason = self::lock_reason( $template );

			$list[] = [
				'id'           => $template['id'],
				'preview_url'  => self::get_preview_url( Helper::get_string_value( $template['id'] ) ),
				'title'        => Helper::get_string_value( $template['title'] ?? '' ),
				'description'  => Helper::get_string_value( $template['description'] ?? '' ),
				'category'     => Helper::get_string_value( $template['category'] ?? 'other' ),
				'icon'         => Helper::get_string_value( $template['icon'] ?? '' ),
				'theme'        => isset( self::get_themes()[ $template['theme'] ?? '' ] ) ? Helper::get_string_value( $template['theme'] ) : 'ocean',
				'fields'       => $fields,
				'features'     => $features,
				// What the template gains with a higher SureForms Pro plan.
				'pro_features' => 'business' === $tier ? [] : array_values( array_diff( self::get_features( $template, self::resolve_fields( $template, 'business' ), 'business' ), $features ) ),
				'submit_text'  => Helper::get_string_value( $template['submit_text'] ?? '' ),
				'messages'     => self::get_messages( $template, $resolved ),
				'answers'      => self::get_answers( $resolved ),
				'requires'     => $requires,
				'is_pro'       => 'free' !== $requires,
				'is_locked'    => '' !== $lock_reason,
				// `tier` (needs a higher plan) or `update` (needs a newer SureForms Pro).
				'lock_reason'  => $lock_reason,
				'unlock_url'   => self::get_unlock_url( Helper::get_string_value( $template['id'] ), $lock_reason ),
			];
		}
		return $list;
	}

	/**
	 * The plan a template needs: `requires`, or `starter` for the older
	 * `is_pro` flag, or `free`.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since x.x.x
	 * @return string 'free', 'starter', 'pro' or 'business'.
	 */
	public static function get_required_tier( $template ) {
		$requires = $template['requires'] ?? '';
		if ( is_string( $requires ) && in_array( $requires, self::TIERS, true ) ) {
			return $requires;
		}
		return ! empty( $template['is_pro'] ) ? 'starter' : 'free';
	}

	/**
	 * Why a template can't be used on this site.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since x.x.x
	 * @return string '' when it can be used, `tier` when it needs a higher plan,
	 *                `update` when it needs a newer SureForms Pro.
	 */
	public static function lock_reason( $template ) {
		$requires = self::get_required_tier( $template );
		$reason   = '';

		if ( ! Helper::has_tier( $requires ) ) {
			$reason = 'tier';
		} elseif ( 'free' !== $requires && self::needs_pro_update( $template ) ) {
			$reason = 'update';
		}

		/**
		 * Filters whether a template can be used on this site. Returning false
		 * locks it, for example to hide a broken template in a hotfix.
		 *
		 * @since x.x.x
		 * @param bool                 $available Whether the template can be used.
		 * @param array<string, mixed> $template  Template.
		 * @param string               $reason    Lock reason: '', `tier` or `update`.
		 */
		$available = (bool) apply_filters( 'srfm_form_template_is_available', '' === $reason, $template, $reason );

		if ( $available ) {
			return '';
		}
		return '' === $reason ? 'tier' : $reason;
	}

	/**
	 * Whether a template can be used on this site.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since x.x.x
	 * @return bool
	 */
	public static function is_template_available( $template ) {
		return '' === self::lock_reason( $template );
	}

	/**
	 * Whether the active SureForms Pro is too old for a template: older than its
	 * `min_pro_version`, or without the template builder its Pro keys need.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since x.x.x
	 * @return bool
	 */
	private static function needs_pro_update( $template ) {
		$min_version = Helper::get_string_value( $template['min_pro_version'] ?? '' );
		$pro_version = defined( 'SRFM_PRO_VER' ) ? Helper::get_string_value( constant( 'SRFM_PRO_VER' ) ) : '';
		if ( '' !== $min_version && version_compare( $pro_version, $min_version, '<' ) ) {
			return true;
		}

		return self::get_pro_template_api() < self::get_required_pro_template_api( $template );
	}

	/**
	 * Lowest `SRFM_PRO_TEMPLATE_API` that builds every Pro key a template declares.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since x.x.x
	 * @return int 0 when the template declares no Pro keys.
	 */
	public static function get_required_pro_template_api( $template ) {
		$required = 0;
		foreach ( array_keys( array_intersect_key( $template, self::PRO_FEATURE_API ) ) as $key ) {
			$required = max( $required, self::PRO_FEATURE_API[ $key ] );
		}
		return $required;
	}

	/**
	 * Version of the template builder in the active SureForms Pro, 0 without one.
	 *
	 * @since x.x.x
	 * @return int
	 */
	public static function get_pro_template_api() {
		return defined( 'SRFM_PRO_TEMPLATE_API' ) ? Helper::get_integer_value( constant( 'SRFM_PRO_TEMPLATE_API' ) ) : 0;
	}

	/**
	 * Where a locked template's button leads: the pricing page, or the plugin
	 * updates when SureForms Pro is too old.
	 *
	 * @param string $template_id Template id.
	 * @param string $lock_reason From lock_reason().
	 * @since x.x.x
	 * @return string Unescaped URL, or '' when the template isn't locked.
	 */
	public static function get_unlock_url( $template_id, $lock_reason ) {
		if ( '' === $lock_reason ) {
			return '';
		}
		if ( 'update' === $lock_reason ) {
			return admin_url( 'plugins.php?plugin_status=upgrade' );
		}
		$url = Helper::get_sureforms_website_url(
			'pricing',
			[
				'utm_medium'  => 'form-templates',
				'utm_content' => 'template-' . $template_id,
			]
		);
		// The helper returns a URL escaped for HTML; this one is opened from JS.
		return esc_url_raw( wp_specialchars_decode( $url ) );
	}

	/**
	 * The error for a template that can't be used on this site.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since x.x.x
	 * @return WP_Error|null Null when the template can be used.
	 */
	private static function locked_error( $template ) {
		$reason = self::lock_reason( $template );
		if ( '' === $reason ) {
			return null;
		}
		$message = 'update' === $reason
			? __( 'Update SureForms Pro to use this template.', 'sureforms' )
			: __( 'This template needs a SureForms Premium plan.', 'sureforms' );

		return new WP_Error(
			'srfm_template_locked',
			$message,
			[
				'status'   => 403,
				'requires' => self::get_required_tier( $template ),
				'reason'   => $reason,
			]
		);
	}

	/**
	 * The thank-you message and emails a template sets, for the preview to
	 * show and edit before the form is created.
	 *
	 * @param array<string, mixed>             $template Template.
	 * @param array<int, array<string, mixed>> $fields   Fields from resolve_fields().
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	public static function get_messages( $template, $fields ) {
		$confirmation  = Helper::get_array_value( $template['confirmation'] ?? [] );
		$autoresponder = ! empty( $template['autoresponder'] ) && self::has_required_email( $fields ) ? Helper::get_array_value( $template['autoresponder'] ) : null;

		return [
			'confirmation'  => [
				'heading' => Helper::get_string_value( $confirmation['heading'] ?? '' ),
				'text'    => Helper::get_string_value( $confirmation['text'] ?? '' ),
			],
			'email'         => [
				'subject' => Helper::get_string_value( $template['email_subject'] ?? '' ),
				'intro'   => Helper::get_string_value( $template['email_intro'] ?? '' ),
			],
			'autoresponder' => null === $autoresponder ? null : [
				'subject'  => Helper::get_string_value( $autoresponder['subject'] ?? '' ),
				'greeting' => Helper::get_string_value( $autoresponder['greeting'] ?? __( 'Hi {form:full-name},', 'sureforms' ) ),
				'lines'    => array_map( [ Helper::class, 'get_string_value' ], Helper::get_array_value( $autoresponder['lines'] ?? [] ) ),
			],
		];
	}

	/**
	 * The answers a message can include as `{form:slug}`, each with an example
	 * value for the preview.
	 *
	 * @param array<int, array<string, mixed>> $fields Fields from resolve_fields().
	 * @since x.x.x
	 * @return array<int, array{slug: string, label: string, required: bool, sample: string}>
	 */
	public static function get_answers( $fields ) {
		$answers = [];
		foreach ( $fields as $field ) {
			$type = Helper::get_string_value( $field['fieldType'] ?? '' );
			if ( in_array( $type, [ 'gdpr', 'page-break', 'inline-button', 'upload' ], true ) ) {
				continue;
			}
			$answers[] = [
				'slug'     => Helper::get_string_value( $field['slug'] ?? '' ),
				'label'    => Helper::get_string_value( $field['label'] ?? '' ),
				'required' => ! empty( $field['required'] ),
				'sample'   => self::sample_answer( $field ),
			];
		}
		return $answers;
	}

	/**
	 * Clean up the thank-you message and email text edited in the preview.
	 *
	 * @param mixed $raw `messages` from the create request.
	 * @since x.x.x
	 * @return array<string, mixed> Only the keys that were sent, as plain text.
	 */
	public static function sanitize_messages( $raw ) {
		if ( ! is_array( $raw ) ) {
			return [];
		}
		$line  = static fn( $value ) => mb_substr( sanitize_text_field( Helper::get_string_value( $value ) ), 0, 300 );
		$text  = static fn( $value ) => mb_substr( sanitize_textarea_field( Helper::get_string_value( $value ) ), 0, 2000 );
		$clean = [];

		if ( isset( $raw['confirmation'] ) && is_array( $raw['confirmation'] ) ) {
			foreach ( [
				'heading' => $line,
				'text'    => $text,
			] as $key => $sanitize ) {
				if ( isset( $raw['confirmation'][ $key ] ) && is_string( $raw['confirmation'][ $key ] ) ) {
					$clean['confirmation'][ $key ] = $sanitize( $raw['confirmation'][ $key ] );
				}
			}
		}
		if ( isset( $raw['email_subject'] ) && is_string( $raw['email_subject'] ) ) {
			$clean['email_subject'] = $line( $raw['email_subject'] );
		}
		if ( isset( $raw['email_intro'] ) && is_string( $raw['email_intro'] ) ) {
			$clean['email_intro'] = $text( $raw['email_intro'] );
		}
		if ( isset( $raw['autoresponder'] ) && is_array( $raw['autoresponder'] ) ) {
			$auto = $raw['autoresponder'];
			if ( isset( $auto['enabled'] ) ) {
				$clean['autoresponder']['enabled'] = rest_sanitize_boolean( $auto['enabled'] );
			}
			foreach ( [ 'subject', 'greeting' ] as $key ) {
				if ( isset( $auto[ $key ] ) && is_string( $auto[ $key ] ) ) {
					$clean['autoresponder'][ $key ] = $line( $auto[ $key ] );
				}
			}
			if ( isset( $auto['lines'] ) && is_array( $auto['lines'] ) ) {
				$clean['autoresponder']['lines'] = array_values( array_filter( array_map( $text, array_slice( $auto['lines'], 0, 10 ) ) ) );
			}
		}
		return $clean;
	}

	/**
	 * A template with the thank-you message and email text edited in the preview.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param array<string, mixed> $messages From sanitize_messages().
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	public static function apply_messages( $template, $messages ) {
		if ( ! empty( $messages['confirmation'] ) ) {
			$template['confirmation'] = array_merge( Helper::get_array_value( $template['confirmation'] ?? [] ), Helper::get_array_value( $messages['confirmation'] ) );
		}
		foreach ( [ 'email_subject', 'email_intro' ] as $key ) {
			if ( isset( $messages[ $key ] ) && '' !== $messages[ $key ] ) {
				$template[ $key ] = $messages[ $key ];
			}
		}
		if ( ! empty( $messages['autoresponder'] ) && ! empty( $template['autoresponder'] ) ) {
			$auto = Helper::get_array_value( $messages['autoresponder'] );
			if ( isset( $auto['enabled'] ) && ! $auto['enabled'] ) {
				unset( $template['autoresponder'] );
			} else {
				unset( $auto['enabled'] );
				$template['autoresponder'] = array_merge( Helper::get_array_value( $template['autoresponder'] ), array_filter( $auto ) );
			}
		}
		return $template;
	}

	/**
	 * Visual themes a template can use: the form's background and colors.
	 * Everything maps to the free form styling options, so a
	 * form created from a template keeps its look and stays editable in the
	 * Style tab.
	 *
	 * @since x.x.x
	 * @return array<string, array<string, string>>
	 */
	public static function get_themes() {
		return [
			'ocean'    => [
				'background' => 'linear-gradient(135deg, #EAF2FF 0%, #F7FAFF 100%)',
				'primary'    => '#2563EB',
				'text'       => '#1E293B',
				'on_primary' => '#FFFFFF',
				'banner'     => '#1D4ED8',
			],
			'midnight' => [
				'background' => 'linear-gradient(160deg, #0F172A 0%, #1E293B 100%)',
				'primary'    => '#38BDF8',
				'text'       => '#E2E8F0',
				'on_primary' => '#0F172A',
				'banner'     => '#020617',
			],
			'mint'     => [
				'background' => 'linear-gradient(135deg, #E6FAF1 0%, #F5FFFB 100%)',
				'primary'    => '#059669',
				'text'       => '#1F2F2A',
				'on_primary' => '#FFFFFF',
				'banner'     => '#047857',
			],
			'lavender' => [
				'background' => 'linear-gradient(135deg, #F0EBFF 0%, #FBF8FF 100%)',
				'primary'    => '#7C3AED',
				'text'       => '#27203A',
				'on_primary' => '#FFFFFF',
				'banner'     => '#6D28D9',
			],
			'sunset'   => [
				'background' => 'linear-gradient(135deg, #FFEADF 0%, #FFF5F7 100%)',
				'primary'    => '#E4572E',
				'text'       => '#33201A',
				'on_primary' => '#FFFFFF',
				'banner'     => '#C2410C',
			],
			'sand'     => [
				'background' => 'linear-gradient(135deg, #FFF4D9 0%, #FFFBF1 100%)',
				'primary'    => '#B45309',
				'text'       => '#2D2416',
				'on_primary' => '#FFFFFF',
				'banner'     => '#92400E',
			],
		];
	}

	/**
	 * Presets for the preview's style panel: name, background and accent.
	 *
	 * @since x.x.x
	 * @return array<int, array{id:string,background:string,accent:string}>
	 */
	public static function get_themes_for_js() {
		$presets = [];
		foreach ( self::get_themes() as $id => $theme ) {
			$presets[] = [
				'id'         => $id,
				'background' => $theme['background'],
				'accent'     => $theme['primary'],
			];
		}
		return $presets;
	}

	/**
	 * Clean up a style chosen in the template preview.
	 *
	 * Only known keys survive: a preset from get_themes() and hex colors for
	 * the background and accent.
	 *
	 * @param mixed $raw Style from the request or the preview URL.
	 * @since x.x.x
	 * @return array{preset?:string,background?:string,accent?:string}
	 */
	public static function sanitize_style( $raw ) {
		if ( is_string( $raw ) && '' !== $raw ) {
			$raw = json_decode( $raw, true );
		}
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$style  = [];
		$preset = isset( $raw['preset'] ) && is_string( $raw['preset'] ) ? sanitize_key( $raw['preset'] ) : '';
		if ( isset( self::get_themes()[ $preset ] ) ) {
			$style['preset'] = $preset;
		}
		foreach ( [ 'background', 'accent' ] as $key ) {
			$color = isset( $raw[ $key ] ) && is_string( $raw[ $key ] ) ? sanitize_hex_color( $raw[ $key ] ) : '';
			if ( ! empty( $color ) ) {
				$style[ $key ] = strtoupper( $color );
			}
		}

		return $style;
	}

	/**
	 * The look a form gets: the template's theme (or the chosen preset), with
	 * any background and accent chosen in the preview on top.
	 *
	 * Text and button-text colors follow the chosen colors, so the
	 * result stays readable whatever is picked.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param array<string, mixed> $style    Style from sanitize_style().
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	public static function resolve_theme( $template, $style = [] ) {
		$themes = self::get_themes();
		$preset = Helper::get_string_value( $style['preset'] ?? '' );
		$theme  = $themes[ $preset ] ?? self::get_template_theme( $template );

		if ( ! empty( $style['background'] ) ) {
			$background          = Helper::get_string_value( $style['background'] );
			$theme['background'] = 'linear-gradient(135deg, ' . $background . ' 0%, ' . self::mix_with( $background, '#FFFFFF', 0.35 ) . ' 100%)';
			$theme['text']       = self::is_dark( $background ) ? '#E2E8F0' : '#1E293B';
		}

		if ( ! empty( $style['accent'] ) ) {
			$accent              = Helper::get_string_value( $style['accent'] );
			$theme['primary']    = $accent;
			$theme['on_primary'] = self::is_dark( $accent ) ? '#FFFFFF' : '#0F172A';
			$theme['banner']     = self::mix_with( $accent, '#000000', 0.15 );
		}

		$theme['radius'] = 16;

		return $theme;
	}

	/**
	 * Template categories, in display order.
	 *
	 * @since x.x.x
	 * @return array<int, array<string, string>>
	 */
	public static function get_categories() {
		return [
			[
				'id'    => 'contact',
				'label' => __( 'Contact & Leads', 'sureforms' ),
			],
			[
				'id'    => 'booking',
				'label' => __( 'Bookings & Events', 'sureforms' ),
			],
			[
				'id'    => 'feedback',
				'label' => __( 'Feedback', 'sureforms' ),
			],
			[
				'id'    => 'signup',
				'label' => __( 'Sign-ups & Applications', 'sureforms' ),
			],
			[
				'id'    => 'support',
				'label' => __( 'Support', 'sureforms' ),
			],
		];
	}

	/**
	 * A template's fields as they will be built on a plan: `pro` variants
	 * applied and `pro_only` fields kept only from their `pro_tier` (Starter
	 * by default).
	 *
	 * @param array<string, mixed> $template Template.
	 * @param string|bool          $tier     Plan tier from Helper::get_pro_tier(). `true` is
	 *                                       read as `business` and `false` as `free`.
	 * @since x.x.x
	 * @return array<int, array<string, mixed>>
	 */
	public static function resolve_fields( $template, $tier ) {
		if ( is_bool( $tier ) ) {
			$tier = $tier ? 'business' : 'free';
		}
		$rank   = Helper::tier_rank( $tier );
		$fields = [];
		foreach ( Helper::get_array_value( $template['fields'] ?? [] ) as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$with_pro = $rank >= max( 1, Helper::tier_rank( Helper::get_string_value( $field['pro_tier'] ?? 'starter' ) ) );
			$pro_only = ! empty( $field['pro_only'] ) || ( isset( $field['pro_tier'] ) && empty( $field['pro'] ) );
			if ( $pro_only && ! $with_pro ) {
				continue;
			}
			if ( $with_pro && ! empty( $field['pro'] ) && is_array( $field['pro'] ) ) {
				// The Pro field keeps the free field's width and other block attributes.
				$pro = $field['pro'];
				if ( isset( $pro['blockAttributes'] ) ) {
					$pro['blockAttributes'] = array_merge( Helper::get_array_value( $field['blockAttributes'] ?? [] ), Helper::get_array_value( $pro['blockAttributes'] ) );
				}
				$field = array_merge( $field, $pro );
			}
			unset( $field['pro'], $field['pro_only'], $field['pro_tier'] );
			$fields[] = $field;
		}
		return $fields;
	}

	/**
	 * What a template sets up, for the "Included" chips in the library.
	 *
	 * Besides the features its fields bring, a template's Pro keys (such as
	 * `conditions`) count from the plan that builds them, and ids listed in its
	 * `features` key (such as `calculation`) always count.
	 *
	 * @param array<string, mixed>             $template Template.
	 * @param array<int, array<string, mixed>> $fields   Fields from resolve_fields().
	 * @param string                           $tier     Plan the fields were resolved for. Defaults to this site's.
	 * @since x.x.x
	 * @return array<int, string>
	 */
	public static function get_features( $template, $fields, $tier = '' ) {
		$by_type = [
			'page-break'  => 'multi_step',
			'upload'      => 'file_upload',
			'date-picker' => 'date_picker',
			'time-picker' => 'time_picker',
			'rating'      => 'rating',
			'nps'         => 'nps',
			'gdpr'        => 'consent',
			'signature'   => 'signature',
			'slider'      => 'slider',
			'repeater'    => 'repeater',
			'payment'     => 'payment',
		];
		$tier    = '' === $tier ? Helper::get_pro_tier() : $tier;

		$features = [ 'notification' ];
		if ( ! empty( $template['autoresponder'] ) && self::has_required_email( $fields ) ) {
			$features[] = 'autoresponder';
		}
		foreach ( $fields as $field ) {
			$type = Helper::get_string_value( $field['fieldType'] ?? '' );
			if ( isset( $by_type[ $type ] ) ) {
				$features[] = $by_type[ $type ];
			}
		}
		foreach ( self::FEATURE_BY_KEY as $key => $feature ) {
			if ( ! empty( $template[ $key ] ) && Helper::tier_rank( $tier ) >= Helper::tier_rank( self::PRO_FEATURE_KEYS[ $key ] ) ) {
				$features[] = $feature;
			}
		}
		foreach ( Helper::get_array_value( $template['features'] ?? [] ) as $feature ) {
			$features[] = sanitize_key( Helper::get_string_value( $feature ) );
		}

		/**
		 * Filters what a template sets up, for the "Included" chips in the library.
		 *
		 * @since x.x.x
		 * @param array<int, string>               $features Feature ids, such as `multi_step` or `quiz`.
		 * @param array<string, mixed>             $template Template.
		 * @param array<int, array<string, mixed>> $fields   Fields from resolve_fields().
		 */
		$features = apply_filters( 'srfm_form_template_features', $features, $template, $fields );

		return array_values( array_unique( array_filter( array_map( [ Helper::class, 'get_string_value' ], Helper::get_array_value( $features ) ) ) ) );
	}

	/**
	 * Passes a template field's `blockAttributes` through to its block.
	 *
	 * Hooked only while a template is being built.
	 *
	 * @param array<string, mixed> $attributes Block attributes from the mapper.
	 * @param array<string, mixed> $question   Field schema.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	public static function apply_block_attributes( $attributes, $question ) {
		if ( ! empty( $question['blockAttributes'] ) && is_array( $question['blockAttributes'] ) ) {
			$attributes = array_merge( $attributes, $question['blockAttributes'] );
		}
		return $attributes;
	}

	/**
	 * Block markup for a template.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since x.x.x
	 * @return string|WP_Error
	 */
	public static function build_content( $template ) {
		$fields  = self::resolve_fields( $template, Helper::get_pro_tier() );
		$request = new WP_REST_Request( 'POST' );
		$request->set_param(
			'form_data',
			[
				'form' => [
					'formTitle'  => $template['title'] ?? '',
					'formFields' => $fields,
				],
			]
		);

		add_filter( 'srfm_ai_form_builder_modify_merged_attributes', [ self::class, 'apply_block_attributes' ], 20, 2 );
		$content = Field_Mapping::generate_gutenberg_fields_from_questions( $request );
		remove_filter( 'srfm_ai_form_builder_modify_merged_attributes', [ self::class, 'apply_block_attributes' ], 20 );

		if ( is_wp_error( $content ) ) {
			return $content;
		}

		/**
		 * Filters the block markup built from a template, before it is saved,
		 * previewed or swapped into a form. SureForms Pro uses it to add the block
		 * attributes its template features need.
		 *
		 * @since x.x.x
		 * @param string                           $content  Block markup.
		 * @param array<string, mixed>             $template Template.
		 * @param array<int, array<string, mixed>> $fields   Fields from resolve_fields(), as built.
		 */
		return Helper::get_string_value( apply_filters( 'srfm_form_template_content', Helper::get_string_value( $content ), $template, $fields ) );
	}

	/**
	 * Build a template's block markup and metas together.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param array<string, mixed> $style    Style from sanitize_style().
	 * @param string               $context  `create` or `preview` for all the form's metas,
	 *                                       `swap` for the settings metas and the thank-you message.
	 * @since x.x.x
	 * @return array{content: string, metas: array<string, mixed>}|WP_Error
	 */
	private static function build( $template, $style, $context ) {
		$content = self::build_content( $template );
		if ( is_wp_error( $content ) ) {
			return $content;
		}

		if ( 'swap' === $context ) {
			$metas = array_merge(
				self::build_settings_metas( $template, $content, 'swap' ),
				[ '_srfm_form_confirmation' => self::confirmation_meta( $template ) ]
			);
		} else {
			$metas = self::build_metas( $template, $style, $content, $context );
		}

		return [
			'content' => $content,
			'metas'   => $metas,
		];
	}

	/**
	 * Post metas for a form created from a template.
	 *
	 * Starts from the registered defaults, like a hand-created form, then applies
	 * the template's thank-you message and notification subject.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param array<string, mixed> $style    Optional style from sanitize_style().
	 * @param string               $content  The form's block markup, from build_content(). Built when
	 *                                       empty and something filters the metas.
	 * @param string               $context  `create` or `preview`.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	public static function build_metas( $template, $style = [], $content = '', $context = 'create' ) {
		$metas      = Create_New_Form::get_default_meta_keys();
		$registered = get_registered_meta_keys( 'post', SRFM_FORMS_POST_TYPE );

		$metas['_srfm_form_confirmation'] = self::confirmation_meta( $template );
		$metas                            = array_merge( $metas, self::settings_metas( $template ), self::build_style_metas( $template, $style ) );

		$metas['_srfm_compliance']       = $registered['_srfm_compliance']['default'] ?? [];
		$metas[ self::CREATED_VIA_META ] = 'template';
		$metas[ self::TEMPLATE_ID_META ] = $template['id'];

		return self::filter_metas( $metas, $template, $content, $context );
	}

	/**
	 * The behaviour metas a template sets: button text, emails and steps.
	 * Used when creating a form and when a template is applied in the editor.
	 *
	 * The confirmation email to the visitor goes to whatever address is typed
	 * into the form, so it is only set up while the site's spam protection is
	 * on. Otherwise bots could use the form to send email to anyone.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param string               $content  The form's block markup, from build_content(). Built when
	 *                                       empty and something filters the metas.
	 * @param string               $context  `swap`, `create` or `preview`.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	public static function build_settings_metas( $template, $content = '', $context = 'swap' ) {
		return self::filter_metas( self::settings_metas( $template ), $template, $content, $context );
	}

	/**
	 * Run a template's metas through `srfm_form_template_metas`.
	 *
	 * @param array<string, mixed> $metas    Metas.
	 * @param array<string, mixed> $template Template.
	 * @param string               $content  The form's block markup, or '' to build it when needed.
	 * @param string               $context  `create`, `swap` or `preview`.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	private static function filter_metas( $metas, $template, $content, $context ) {
		if ( ! has_filter( 'srfm_form_template_metas' ) ) {
			return $metas;
		}
		if ( '' === $content ) {
			$built   = self::build_content( $template );
			$content = is_string( $built ) ? $built : '';
		}

		/**
		 * Filters the metas a template sets on a form. SureForms Pro uses it to
		 * write the settings of the template's Pro features, such as conditional
		 * logic, matching fields by slug in `$content`.
		 *
		 * In the `swap` context the metas go back to the editor as they are, and
		 * only the template's settings metas (plus `_srfm_form_confirmation`) are
		 * passed; `create` metas are saved through wp_insert_post() and are
		 * unslashed on the way; `preview` metas render an in-memory form.
		 *
		 * @since x.x.x
		 * @param array<string, mixed>             $metas    Metas, keyed by meta key.
		 * @param array<string, mixed>             $template Template.
		 * @param string                           $content  The form's block markup.
		 * @param array<int, array<string, mixed>> $fields   Fields from resolve_fields(), as built.
		 * @param string                           $context  `create`, `swap` or `preview`.
		 */
		$filtered = apply_filters( 'srfm_form_template_metas', $metas, $template, $content, self::resolve_fields( $template, Helper::get_pro_tier() ), $context );

		return is_array( $filtered ) ? $filtered : $metas;
	}

	/**
	 * The thank-you message meta a template sets.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since x.x.x
	 * @return mixed `_srfm_form_confirmation` value.
	 */
	private static function confirmation_meta( $template ) {
		$registered   = get_registered_meta_keys( 'post', SRFM_FORMS_POST_TYPE );
		$confirmation = $registered['_srfm_form_confirmation']['default'] ?? [];
		if ( ! empty( $template['confirmation'] ) && is_array( $template['confirmation'] ) && is_array( $confirmation ) && isset( $confirmation[0] ) && is_array( $confirmation[0] ) ) {
			$confirmation[0]['message'] = self::confirmation_message(
				Helper::get_string_value( $template['confirmation']['heading'] ?? '' ),
				Helper::get_string_value( $template['confirmation']['text'] ?? '' )
			);
		}
		return $confirmation;
	}

	/**
	 * The behaviour metas a template sets, before `srfm_form_template_metas`.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	private static function settings_metas( $template ) {
		$registered = get_registered_meta_keys( 'post', SRFM_FORMS_POST_TYPE );
		$fields     = self::resolve_fields( $template, Helper::get_pro_tier() );
		$has_email  = self::has_required_email( $fields );
		$metas      = [];

		if ( ! empty( $template['submit_text'] ) ) {
			$metas['_srfm_submit_button_text'] = Helper::get_string_value( $template['submit_text'] );
		}

		// A custom button among the fields replaces the form's own submit button.
		// Always set, so applying a template in the editor clears an old value.
		$metas['_srfm_is_inline_button'] = ! empty( array_filter( $fields, static fn( $field ) => 'inline-button' === ( $field['fieldType'] ?? '' ) ) );

		$notifications = Helper::get_array_value( $registered['_srfm_email_notification']['default'] ?? [] );
		if ( isset( $notifications[0] ) && is_array( $notifications[0] ) ) {
			$admin = $notifications[0];
			if ( ! empty( $template['email_subject'] ) ) {
				$admin['subject'] = Helper::get_string_value( $template['email_subject'] );
			}
			if ( ! empty( $template['email_intro'] ) ) {
				$admin['email_body'] = '<p>' . esc_html( Helper::get_string_value( $template['email_intro'] ) ) . '</p>{all_data}';
			}
			// Replying should reach the person who filled in the form, and the
			// owner does not need copies of their own notification.
			$admin['email_reply_to'] = $has_email ? '{form:email}' : $admin['email_reply_to'];
			$admin['email_cc']       = '';
			$admin['email_bcc']      = '';
			$notifications           = [ $admin ];

			if ( $has_email && ! empty( $template['autoresponder'] ) && is_array( $template['autoresponder'] ) && self::is_spam_protection_on() ) {
				$notifications[] = array_merge(
					$admin,
					[
						'id'             => 2,
						'name'           => __( 'Confirmation to the submitter', 'sureforms' ),
						'email_to'       => '{form:email}',
						'email_reply_to' => '{admin_email}',
						'subject'        => Helper::get_string_value( $template['autoresponder']['subject'] ?? '' ),
						'email_body'     => self::autoresponder_body( $template['autoresponder'] ),
					]
				);
			}
		}
		$metas['_srfm_email_notification'] = $notifications;

		$has_steps = ! empty( array_filter( $fields, static fn( $field ) => 'page-break' === ( $field['fieldType'] ?? '' ) ) );
		if ( $has_steps ) {
			$steps                              = Helper::get_array_value( $template['steps'] ?? [] );
			$metas['_srfm_page_break_settings'] = array_merge(
				Helper::get_array_value( $registered['_srfm_page_break_settings']['default'] ?? [] ),
				[
					'is_page_break'           => true,
					'first_page_label'        => Helper::get_string_value( $steps[0] ?? __( 'Step 1', 'sureforms' ) ),
					'progress_indicator_type' => 'connector',
					'toggle_label'            => true,
				]
			);
		}

		return $metas;
	}

	/**
	 * The metas that carry a template's look: form styling, and the banner on
	 * the form's own page. Used when creating a form and when a template is
	 * applied to an open form in the editor.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param array<string, mixed> $style    Optional style from sanitize_style().
	 * @since x.x.x
	 * @return array{_srfm_forms_styling: array<string, mixed>, _srfm_instant_form_settings: array<string, mixed>, _srfm_single_page_form_title: int}
	 */
	public static function build_style_metas( $template, $style = [] ) {
		$registered = get_registered_meta_keys( 'post', SRFM_FORMS_POST_TYPE );
		$theme      = self::resolve_theme( $template, $style );
		$styling    = Helper::get_array_value( $registered['_srfm_forms_styling']['default'] ?? [] );
		$styling    = array_merge(
			$styling,
			[
				'primary_color'           => $theme['primary'],
				'text_color'              => $theme['text'],
				'text_color_on_primary'   => $theme['on_primary'],
				'field_spacing'           => 'medium',
				'submit_button_alignment' => 'justify',
				'bg_type'                 => 'gradient',
				'gradient_type'           => 'basic',
				'bg_gradient'             => $theme['background'],
			]
		);
		// The form's own page (Instant Form) reads its own padding and corner
		// keys, so both get the same values and the form looks alike everywhere.
		foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
			$styling[ 'form_padding_' . $side ]               = 40;
			$styling[ 'form_border_radius_' . $side ]         = $theme['radius'];
			$styling[ 'instant_form_padding_' . $side ]       = 40;
			$styling[ 'instant_form_border_radius_' . $side ] = $theme['radius'];
		}

		// The form's own page (Instant Form) shows the form title above the
		// form, and its banner follows the template's theme.
		$instant_form = array_merge(
			Helper::get_array_value( $registered['_srfm_instant_form_settings']['default'] ?? [] ),
			self::instant_form_style( $theme )
		);

		return [
			'_srfm_forms_styling'          => $styling,
			'_srfm_instant_form_settings'  => $instant_form,
			'_srfm_single_page_form_title' => 1,
		];
	}

	/**
	 * Create a draft form from a template.
	 *
	 * @param string               $template_id Template id.
	 * @param array<string, mixed> $style       Optional style chosen in the preview (sanitize_style()).
	 * @param array<string, mixed> $messages    Optional thank-you and email text edited in the preview (sanitize_messages()).
	 * @since x.x.x
	 * @return int|WP_Error New form ID.
	 */
	public static function create_form_from_template( $template_id, $style = [], $messages = [] ) {
		$template = self::get_template( $template_id );

		if ( null === $template ) {
			return new WP_Error( 'srfm_template_not_found', __( 'That template could not be found.', 'sureforms' ), [ 'status' => 404 ] );
		}

		$locked = self::locked_error( $template );
		if ( null !== $locked ) {
			return $locked;
		}

		$template = self::apply_messages( $template, self::sanitize_messages( $messages ) );
		$built    = self::build( $template, self::sanitize_style( $style ), 'create' );
		if ( is_wp_error( $built ) ) {
			return $built;
		}

		// wp_insert_post() unslashes its input, which would strip the backslashes
		// that escape quotes inside the block JSON (a label like `Say "hi"`) and
		// in edited message text, so slash everything first.
		$post_id = wp_insert_post(
			wp_slash(
				[
					'post_title'   => Helper::get_string_value( $template['title'] ?? '' ),
					'post_content' => $built['content'],
					'post_status'  => 'draft',
					'post_type'    => SRFM_FORMS_POST_TYPE,
					'meta_input'   => $built['metas'],
				]
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( empty( $post_id ) ) {
			return new WP_Error( 'srfm_template_create_failed', __( 'The form could not be created. Please try again.', 'sureforms' ), [ 'status' => 500 ] );
		}

		Analytics::track_form_template_usage( $template['id'], 'create' );

		return $post_id;
	}

	/**
	 * GET /form-templates
	 *
	 * @since x.x.x
	 * @return WP_REST_Response
	 */
	public function rest_list_templates() {
		return new WP_REST_Response(
			[
				'templates'         => self::get_templates_for_js(),
				'pro_tier'          => Helper::get_pro_tier(),
				'categories'        => self::get_categories(),
				'themes'            => self::get_themes_for_js(),
				'requests'          => self::get_request_settings_for_js(),
				'spam_protection'   => self::is_spam_protection_on(),
				// Settings the editor only replaces when the user chooses to.
				'message_meta_keys' => self::get_message_meta_keys(),
			]
		);
	}

	/**
	 * The form settings that hold a form's messages: emails, the thank-you
	 * message, the button text and the step labels.
	 *
	 * Applying a template to an open form always replaces its fields and
	 * design. These keys are only replaced when the user chooses to, so a
	 * form can take a new look and keep its own emails and messages.
	 *
	 * @since x.x.x
	 * @return array<int, string> Meta keys.
	 */
	public static function get_message_meta_keys() {
		$keys = [
			'_srfm_submit_button_text',
			'_srfm_email_notification',
			'_srfm_form_confirmation',
			'_srfm_page_break_settings',
		];

		/**
		 * Filters the form settings a template swap only replaces when the user
		 * chooses to also replace the form's emails and messages.
		 *
		 * @since x.x.x
		 * @param array<int, string> $keys Meta keys.
		 */
		$keys = apply_filters( 'srfm_form_template_message_meta_keys', $keys );

		return array_values( array_unique( array_filter( array_map( [ Helper::class, 'get_string_value' ], is_array( $keys ) ? $keys : [] ) ) ) );
	}

	/**
	 * Whether the site-wide honeypot is on.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public static function is_spam_protection_on() {
		$options = get_option( 'srfm_security_settings_options' );
		return is_array( $options ) && ! empty( $options['srfm_honeypot'] );
	}

	/**
	 * Turn on the site-wide honeypot, keeping the other security settings.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public static function enable_spam_protection() {
		$options                  = get_option( 'srfm_security_settings_options' );
		$options                  = is_array( $options ) ? $options : [];
		$options['srfm_honeypot'] = true;
		update_option( 'srfm_security_settings_options', $options );
	}

	/**
	 * POST /form-templates/create
	 *
	 * @param WP_REST_Request $request Request.
	 * @since x.x.x
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_create_form( $request ) {
		// Before creating, so the form gets its confirmation email to the
		// visitor, which is only set up while spam protection is on.
		if ( rest_sanitize_boolean( $request->get_param( 'enable_spam_protection' ) ) && ! self::is_spam_protection_on() ) {
			self::enable_spam_protection();
		}

		$post_id = self::create_form_from_template(
			Helper::get_string_value( $request->get_param( 'template_id' ) ),
			self::sanitize_style( $request->get_param( 'style' ) ),
			Helper::get_array_value( $request->get_param( 'messages' ) )
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		return new WP_REST_Response(
			[
				'id'       => $post_id,
				'edit_url' => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
			]
		);
	}

	/**
	 * GET /form-templates/<id>
	 *
	 * Block markup for swapping a template into an open form. Also says whether
	 * that form already has entries, because swapping changes the fields that
	 * existing entries and integrations were recorded against.
	 *
	 * @param WP_REST_Request $request Request.
	 * @since x.x.x
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_get_template_content( $request ) {
		$template = self::get_template( Helper::get_string_value( $request->get_param( 'template_id' ) ) );

		if ( null === $template ) {
			return new WP_Error( 'srfm_template_not_found', __( 'That template could not be found.', 'sureforms' ), [ 'status' => 404 ] );
		}

		$locked = self::locked_error( $template );
		if ( null !== $locked ) {
			return $locked;
		}

		$style = self::sanitize_style( $request->get_param( 'style' ) );
		$built = self::build( $template, $style, 'swap' );
		if ( is_wp_error( $built ) ) {
			return $built;
		}

		$style_metas = self::build_style_metas( $template, $style );
		$form_id     = Helper::get_integer_value( $request->get_param( 'form_id' ) );
		$has_entries = $form_id > 0 && Entries::get_total_entries_by_status( 'all', $form_id ) > 0;

		return new WP_REST_Response(
			[
				'id'           => $template['id'],
				'title'        => Helper::get_string_value( $template['title'] ?? '' ),
				'content'      => $built['content'],
				'has_entries'  => $has_entries,
				// Applied over the open form's own settings in the editor.
				'styling'      => self::changed_keys( $style_metas['_srfm_forms_styling'], Helper::get_array_value( get_registered_meta_keys( 'post', SRFM_FORMS_POST_TYPE )['_srfm_forms_styling']['default'] ?? [] ) ),
				'instant_form' => self::instant_form_style( self::resolve_theme( $template, $style ) ),
				// Button text, emails, thank-you message and steps the template sets.
				// The editor only applies the get_message_meta_keys() ones when the
				// user chooses to replace the form's messages too.
				'settings'     => $built['metas'],
			]
		);
	}

	/**
	 * POST /form-templates/request
	 *
	 * Records a template someone asked for, or counts a search without a
	 * match (not its text). Nothing is recorded unless the site shares usage
	 * data with SureForms.
	 *
	 * @param WP_REST_Request $request Request.
	 * @since x.x.x
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_record_demand( $request ) {
		$type  = 'search' === $request->get_param( 'type' ) ? 'search' : 'request';
		$query = 'search' === $type ? '' : self::clean_demand_query( $request->get_param( 'query' ) );
		if ( 'request' === $type && '' === $query ) {
			return new WP_Error( 'srfm_template_request_invalid', __( 'Please describe the template in a few words.', 'sureforms' ), [ 'status' => 400 ] );
		}

		if ( ! self::is_usage_sharing_on() ) {
			return new WP_REST_Response( [ 'recorded' => false ] );
		}

		Analytics::track_form_template_demand( $query, $type );

		return new WP_REST_Response( [ 'recorded' => true ] );
	}

	/**
	 * Clean search text before it is shared as template demand.
	 *
	 * Keeps short, plain descriptions only. Text that looks like contact
	 * details or a link is dropped, as people sometimes paste those into
	 * search boxes.
	 *
	 * @param mixed $query Search text.
	 * @since x.x.x
	 * @return string Cleaned text, or '' when it should not be shared.
	 */
	public static function clean_demand_query( $query ) {
		$query = strtolower( trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( Helper::get_string_value( $query ) ) ) ) );

		if ( '' === $query || strlen( $query ) < 2 || strlen( $query ) > 60 ) {
			return '';
		}

		// Emails, links and long numbers (phone numbers, IDs).
		if ( false !== strpos( $query, '@' ) || preg_match( '#https?:|www\.|\.com\b|\d{5,}#', $query ) ) {
			return '';
		}

		if ( count( explode( ' ', $query ) ) > 6 ) {
			return '';
		}

		return $query;
	}

	/**
	 * Whether this site shares usage data with SureForms, which is how
	 * template requests reach the team.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public static function is_usage_sharing_on() {
		if ( ! apply_filters( 'bsf_usage_tracking_enabled', true ) ) {
			return false;
		}
		return (bool) apply_filters( 'sureforms_tracking_enabled', 'yes' === get_site_option( 'sureforms_usage_optin', false ) );
	}

	/**
	 * What the template library needs to offer "Request this template".
	 *
	 * @since x.x.x
	 * @return array{enabled: bool, settings_url: string}
	 */
	public static function get_request_settings_for_js() {
		return [
			'enabled'      => self::is_usage_sharing_on(),
			'settings_url' => admin_url( 'admin.php?page=sureforms_form_settings&tab=general-settings' ),
		];
	}

	/**
	 * POST /form-templates/swap
	 *
	 * @param WP_REST_Request $request Request.
	 * @since x.x.x
	 * @return WP_REST_Response
	 */
	public function rest_record_swap( $request ) {
		self::record_swap(
			Helper::get_integer_value( $request->get_param( 'form_id' ) ),
			Helper::get_string_value( $request->get_param( 'template_id' ) )
		);

		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Record a template swapped into an open form.
	 *
	 * The swap itself happens in the editor; this only stamps the form and counts it.
	 *
	 * @param int    $form_id     Form ID.
	 * @param string $template_id Template id.
	 * @since x.x.x
	 * @return void
	 */
	public static function record_swap( $form_id, $template_id ) {
		$template = self::get_template( $template_id );
		if ( null === $template || SRFM_FORMS_POST_TYPE !== get_post_type( $form_id ) ) {
			return;
		}
		update_post_meta( $form_id, self::TEMPLATE_ID_META, $template['id'] );
		Analytics::track_form_template_usage( $template['id'], 'swap' );
	}

	/**
	 * URL of a template's front-end preview.
	 *
	 * @param string $template_id Template id.
	 * @since x.x.x
	 * @return string
	 */
	public static function get_preview_url( $template_id ) {
		return add_query_arg(
			[
				self::PREVIEW_QUERY_VAR => rawurlencode( $template_id ),
				// The preview is a front-end page, which would use the site language;
				// show it in the admin's language, like the form they will create.
				'srfm_locale'           => rawurlencode( get_user_locale() ),
			],
			home_url( '/' )
		);
	}

	/**
	 * Remove the in-memory preview form from the object cache at the end of the
	 * request, so a persistent cache (Redis, Memcached) never keeps it.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public static function forget_preview_form() {
		if ( null === self::$preview_post_id ) {
			return;
		}
		wp_cache_delete( self::$preview_post_id, 'posts' );
		wp_cache_delete( self::$preview_post_id, 'post_meta' );
	}

	/**
	 * Render a template preview page instead of the requested front-end page.
	 *
	 * The page is the site's own front end: the active theme's styles plus the
	 * real SureForms renderer, so the preview looks exactly like the form will
	 * once it is embedded. Only users who can create forms see it.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function maybe_render_preview() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preview gated by a capability check; it changes nothing.
		if ( ! isset( $_GET[ self::PREVIEW_QUERY_VAR ] ) ) {
			return;
		}

		if ( ! Helper::current_user_can() ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to preview form templates.', 'sureforms' ), '', [ 'response' => 403 ] );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display language only; checked against installed languages.
		$locale = isset( $_GET['srfm_locale'] ) ? sanitize_text_field( wp_unslash( $_GET['srfm_locale'] ) ) : '';
		if ( '' !== $locale && determine_locale() !== $locale && ( 'en_US' === $locale || in_array( $locale, get_available_languages(), true ) ) ) {
			switch_to_locale( $locale );
			self::$templates = null; // Rebuild the template strings in that language.
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- See above.
		$template = self::get_template( sanitize_key( wp_unslash( $_GET[ self::PREVIEW_QUERY_VAR ] ) ) );
		if ( null === $template ) {
			wp_die( esc_html__( 'That template could not be found.', 'sureforms' ), '', [ 'response' => 404 ] );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preview; the style is sanitized to known keys and hex colors.
		$style = self::sanitize_style( isset( $_GET['srfm_style'] ) ? sanitize_text_field( wp_unslash( $_GET['srfm_style'] ) ) : '' );
		// A locked template still previews, built with this site's fields, so
		// the library can show what it offers.
		$built = self::build( $template, $style, 'preview' );
		if ( is_wp_error( $built ) ) {
			wp_die( esc_html( $built->get_error_message() ), '', [ 'response' => 500 ] );
		}

		self::seed_preview_form( $template, $built['content'], $built['metas'] );

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Standard page-cache opt-out constant.
		}
		nocache_headers();
		show_admin_bar( false );
		add_action( 'wp_enqueue_scripts', [ Frontend_Assets::class, 'enqueue_scripts_and_styles' ], 20 );

		// Block styles are normally built from the page's main post, which this
		// preview does not have, so build them from the in-memory form instead.
		if ( class_exists( '\Spec_Gb_Helper' ) ) {
			\Spec_Gb_Helper::get_instance()->get_generated_stylesheet( get_post( self::get_preview_post_id() ) );
		}

		// Render first, so the blocks enqueue the scripts and styles they need
		// before the head and footer print them.
		$markup = Helper::get_string_value( Generate_Form_Markup::get_form_markup( self::get_preview_post_id(), false ) );
		$width  = Helper::get_integer_value( Create_New_Form::get_default_meta_keys()['_srfm_form_container_width'] ?? 650 );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display flag only.
		$is_thumbnail = ! empty( $_GET['srfm_thumb'] );

		self::render_preview_document( $markup, $width, $is_thumbnail );
		exit;
	}

	/**
	 * An example answer for a field, taken from its placeholder or first option.
	 *
	 * @param array<string, mixed> $field Field schema.
	 * @since x.x.x
	 * @return string
	 */
	private static function sample_answer( $field ) {
		// Fields whose placeholder or first option would make a poor example.
		$by_slug = [
			'full-name'       => 'Jane Smith',
			'first-name'      => 'Jane',
			'last-name'       => 'Smith',
			'company'         => __( 'Bright Bakery', 'sureforms' ),
			'organization'    => __( 'Bright Bakery', 'sureforms' ),
			'subject'         => __( 'Question about my order', 'sureforms' ),
			'order-number'    => '#10234',
			'reference'       => '#10234',
			'product'         => __( 'Oak dining table', 'sureforms' ),
			'position'        => __( 'Marketing Manager', 'sureforms' ),
			'summary'         => __( 'The checkout button does nothing', 'sureforms' ),
			'page'            => __( 'Pricing', 'sureforms' ),
			'recommend-score' => '9',
		];
		$slug    = Helper::get_string_value( $field['slug'] ?? '' );
		if ( isset( $by_slug[ $slug ] ) ) {
			return $by_slug[ $slug ];
		}
		$type    = Helper::get_string_value( $field['fieldType'] ?? '' );
		$options = Helper::get_array_value( $field['fieldOptions'] ?? [] );
		if ( ! empty( $options[0] ) && is_array( $options[0] ) ) {
			return Helper::get_string_value( $options[0]['optionTitle'] ?? $options[0]['label'] ?? '' );
		}

		$samples     = [
			'email'       => 'jane@example.com',
			'phone'       => '+1 555 010 2030',
			'url'         => 'https://example.com',
			'number'      => '2',
			'date-picker' => '10/14/2026',
			'rating'      => '5',
			'nps'         => '9',
			'textarea'    => __( 'A short message from the visitor.', 'sureforms' ),
			'checkbox'    => __( 'Yes', 'sureforms' ),
		];
		$placeholder = Helper::get_string_value( $field['placeholder'] ?? '' );
		// Example placeholders ("e.g. 14 October") read as real answers without the prefix.
		$example = preg_replace( '/^' . preg_quote( __( 'e.g.', 'sureforms' ), '/' ) . '\s*/u', '', $placeholder );

		if ( isset( $samples[ $type ] ) && 'textarea' !== $type ) {
			return $samples[ $type ];
		}
		if ( 'input' === $type && '' !== $placeholder && $example !== $placeholder ) {
			return Helper::get_string_value( $example );
		}

		return $samples[ $type ] ?? ( '' !== $placeholder ? $placeholder : __( 'Their answer', 'sureforms' ) );
	}

	/**
	 * A template's theme, falling back to the first one.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since x.x.x
	 * @return array<string, string>
	 */
	private static function get_template_theme( $template ) {
		$themes = self::get_themes();
		$key    = Helper::get_string_value( $template['theme'] ?? '' );
		return $themes[ $key ] ?? $themes['ocean'];
	}

	/**
	 * Whether a hex color is dark enough to need light text on it.
	 *
	 * @param string $hex Hex color.
	 * @since x.x.x
	 * @return bool
	 */
	private static function is_dark( $hex ) {
		[ $r, $g, $b ] = self::hex_to_rgb( $hex );
		$channel       = static function ( $value ) {
			$value /= 255;
			return $value <= 0.03928 ? $value / 12.92 : ( ( $value + 0.055 ) / 1.055 ) ** 2.4;
		};
		$luminance     = 0.2126 * $channel( $r ) + 0.7152 * $channel( $g ) + 0.0722 * $channel( $b );

		// Below this, white text has the better contrast.
		return $luminance < 0.179;
	}

	/**
	 * Mix two hex colors.
	 *
	 * @param string $hex    Base color.
	 * @param string $with   Color mixed in.
	 * @param float  $amount Share of $with, 0 to 1.
	 * @since x.x.x
	 * @return string
	 */
	private static function mix_with( $hex, $with, $amount ) {
		$base = self::hex_to_rgb( $hex );
		$mix  = self::hex_to_rgb( $with );
		$out  = '#';
		foreach ( [ 0, 1, 2 ] as $i ) {
			$out .= str_pad( dechex( (int) round( $base[ $i ] + ( $mix[ $i ] - $base[ $i ] ) * $amount ) ), 2, '0', STR_PAD_LEFT );
		}
		return strtoupper( $out );
	}

	/**
	 * Hex color to RGB.
	 *
	 * @param string $hex #RGB or #RRGGBB.
	 * @since x.x.x
	 * @return array{0:int,1:int,2:int}
	 */
	private static function hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$hex = str_pad( substr( $hex, 0, 6 ), 6, '0' );
		return [ (int) hexdec( substr( $hex, 0, 2 ) ), (int) hexdec( substr( $hex, 2, 2 ) ), (int) hexdec( substr( $hex, 4, 2 ) ) ];
	}

	/**
	 * Whether the form always collects the visitor's email in the `email` field,
	 * so replies and confirmation emails have somewhere to go.
	 *
	 * @param array<int, array<string, mixed>> $fields Fields from resolve_fields().
	 * @since x.x.x
	 * @return bool
	 */
	private static function has_required_email( $fields ) {
		foreach ( $fields as $field ) {
			if ( 'email' === ( $field['fieldType'] ?? '' ) && 'email' === ( $field['slug'] ?? '' ) && ! empty( $field['required'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Body of the confirmation email sent to the person who filled in the form.
	 *
	 * It never repeats the answers ({all_data}): the address it goes to is
	 * typed into the form, so echoing free text would let anyone send their
	 * own text from this site to any address.
	 *
	 * @param array<string, mixed> $autoresponder Template `autoresponder`.
	 * @since x.x.x
	 * @return string
	 */
	private static function autoresponder_body( $autoresponder ) {
		$greeting = Helper::get_string_value( $autoresponder['greeting'] ?? __( 'Hi {form:full-name},', 'sureforms' ) );
		$body     = '<p>' . esc_html( $greeting ) . '</p>';
		foreach ( Helper::get_array_value( $autoresponder['lines'] ?? [] ) as $line ) {
			$body .= '<p>' . esc_html( Helper::get_string_value( $line ) ) . '</p>';
		}
		return $body . '<p>' . esc_html__( 'Thanks,', 'sureforms' ) . '<br>{site_title}</p>';
	}

	/**
	 * The Instant Form settings a template sets. Only these keys change when
	 * a template is applied to a form, so its other page settings stay.
	 *
	 * @param array<string, mixed> $theme Theme from resolve_theme().
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	private static function instant_form_style( $theme ) {
		return [
			'single_page_form_title' => true,
			'cover_type'             => 'color',
			'cover_color'            => $theme['banner'],
		];
	}

	/**
	 * The entries of `$values` that differ from `$defaults`, so applying a
	 * template in the editor keeps the form's other style choices.
	 *
	 * @param array<string, mixed> $values   Values.
	 * @param array<string, mixed> $defaults Defaults.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	private static function changed_keys( $values, $defaults ) {
		return array_filter(
			$values,
			static fn( $value, $key ) => ! array_key_exists( $key, $defaults ) || $defaults[ $key ] !== $value,
			ARRAY_FILTER_USE_BOTH
		);
	}

	/**
	 * ID of the in-memory preview form. Each request uses its own ID in a range
	 * real posts don't reach, so previews rendering at the same time never share
	 * cache entries.
	 *
	 * @since x.x.x
	 * @return int
	 */
	private static function get_preview_post_id() {
		if ( null === self::$preview_post_id ) {
			self::$preview_post_id = self::PREVIEW_POST_ID - wp_rand( 0, 999999 );
		}
		return self::$preview_post_id;
	}

	/**
	 * Put an in-memory form in the object cache, so the renderer can read it
	 * like a saved form for the rest of this request. It is removed again at
	 * shutdown, so nothing is kept in a persistent object cache.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param string               $content  Block markup.
	 * @param array<string, mixed> $metas    Metas from build_metas(), including any a filter added.
	 * @since x.x.x
	 * @return void
	 */
	private static function seed_preview_form( $template, $content, $metas ) {
		$post = (object) [
			'ID'                => self::get_preview_post_id(),
			'post_author'       => get_current_user_id(),
			'post_date'         => current_time( 'mysql' ),
			'post_date_gmt'     => current_time( 'mysql', true ),
			'post_content'      => $content,
			'post_title'        => Helper::get_string_value( $template['title'] ?? '' ),
			'post_excerpt'      => '',
			'post_status'       => 'publish',
			'comment_status'    => 'closed',
			'ping_status'       => 'closed',
			'post_password'     => '',
			'post_name'         => 'srfm-template-preview',
			'to_ping'           => '',
			'pinged'            => '',
			'post_modified'     => current_time( 'mysql' ),
			'post_modified_gmt' => current_time( 'mysql', true ),
			'post_parent'       => 0,
			'guid'              => '',
			'menu_order'        => 0,
			'post_type'         => SRFM_FORMS_POST_TYPE,
			'post_mime_type'    => '',
			'comment_count'     => 0,
			'filter'            => 'raw',
		];
		wp_cache_set( self::get_preview_post_id(), $post, 'posts' );
		add_action( 'shutdown', [ self::class, 'forget_preview_form' ] );

		$meta = [];
		foreach ( $metas as $key => $value ) {
			$meta[ $key ] = [ is_scalar( $value ) ? (string) $value : maybe_serialize( Helper::get_array_value( $value ) ) ];
		}
		wp_cache_set( self::get_preview_post_id(), $meta, 'post_meta' );
	}

	/**
	 * Print the preview page around the rendered form.
	 *
	 * Submitting is switched off: this is a picture of the form, and the
	 * in-memory form it renders cannot take entries.
	 *
	 * @param string $markup       Rendered form markup.
	 * @param int    $width        Form container width in px.
	 * @param bool   $is_thumbnail Whether the page is shown as a scaled-down card thumbnail.
	 * @since x.x.x
	 * @return void
	 */
	private static function render_preview_document( $markup, $width, $is_thumbnail = false ) {
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
		<?php wp_head(); ?>
	<style>
		html { margin-top: 0 !important; }
		body.srfm-template-preview { margin: 0; }
		.srfm-template-preview .srfm-edit-form-btn-wrap { display: none !important; }
		.srfm-template-preview__wrap { box-sizing: border-box; max-width: <?php echo esc_attr( (string) $width ); ?>px; margin: 0 auto; padding: 40px 24px; }
		<?php if ( $is_thumbnail ) { ?>
		html, body.srfm-template-preview { overflow: hidden !important; }
		.srfm-template-preview__wrap { padding: 20px 16px; }
		<?php } ?>
	</style>
</head>
<body <?php body_class( 'srfm-template-preview' ); ?>>
	<div class="srfm-template-preview__wrap">
		<?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output of the SureForms form renderer, escaped by each block. ?>
	</div>
	<script>
		( function () {
			var stop = function ( event ) {
				var target = event.target;
				var isSubmit = 'submit' === event.type || ( target && target.closest && target.closest( 'form button[type="submit"], form .srfm-submit-button, form input[type="submit"]' ) );
				if ( isSubmit ) {
					event.preventDefault();
					event.stopImmediatePropagation();
				}
			};
			document.addEventListener( 'submit', stop, true );
			document.addEventListener( 'click', stop, true );
		}() );
	</script>
		<?php wp_footer(); ?>
</body>
</html>
		<?php
	}

	/**
	 * Thank-you message in the same layout as the default confirmation.
	 *
	 * @param string $heading Heading.
	 * @param string $text    Body text.
	 * @since x.x.x
	 * @return string
	 */
	private static function confirmation_message( $heading, $text ) {
		// A file URL, not a data: URI: the saved message goes through wp_kses_post(),
		// which strips the data: scheme and would leave a broken image.
		$check_icon = SRFM_URL . 'images/check-icon.svg';

		return '<p style="text-align: center;"><img src="' . esc_url( $check_icon ) . '" alt="" aria-hidden="true" /></p><h2 style="text-align: center;">' . esc_html( $heading ) . '</h2><p style="text-align: center;">' . esc_html( $text ) . '</p>';
	}

	/**
	 * The templates that ship with the plugin, from the catalog files.
	 *
	 * @since x.x.x
	 * @return array<int, array<string, mixed>>
	 */
	private static function default_templates() {
		return Catalog::templates();
	}
}
