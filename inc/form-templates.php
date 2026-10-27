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
 * @since 2.13.0
 */

namespace SRFM\Inc;

use SRFM\Admin\Analytics;
use SRFM\Inc\AI_Form_Builder\Field_Mapping;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Form_Templates\Catalog;
use SRFM\Inc\Form_Templates\Formula;
use SRFM\Inc\Payments\Payment_Helper;
use SRFM\Inc\Payments\Stripe\Stripe_Helper;
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
 * @since 2.13.0
 */
class Form_Templates {
	use Get_Instance;

	/**
	 * Meta key recording how a form was created (`template`, `ai`).
	 *
	 * @since 2.13.0
	 */
	public const CREATED_VIA_META = '_srfm_created_via';

	/**
	 * Meta key recording which template a form was created from.
	 *
	 * @since 2.13.0
	 */
	public const TEMPLATE_ID_META = '_srfm_template_id';

	/**
	 * Query var that renders a template preview page.
	 *
	 * @since 2.13.0
	 */
	public const PREVIEW_QUERY_VAR = 'srfm_template_preview';

	/**
	 * Plan tiers a template can require, lowest first.
	 *
	 * @since 2.13.0
	 */
	public const TIERS = [ 'free', 'starter', 'pro', 'business' ];

	/**
	 * Template keys that SureForms Pro builds into the form (through the
	 * `srfm_form_template_content` and `srfm_form_template_metas` filters),
	 * with the tier whose Pro build handles each.
	 *
	 * @since 2.13.0
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
	];

	/**
	 * Lowest `SRFM_PRO_TEMPLATE_API` that builds each of the PRO_FEATURE_KEYS.
	 * Version 1 builds the Starter and Pro keys, version 2 adds the Business
	 * ones. A paid template declaring a key is locked (reason `update`) while
	 * the active SureForms Pro reports a lower version.
	 *
	 * @since 2.13.0
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
	];

	/**
	 * Example result of a calculated field whose formula can't be worked out.
	 *
	 * @since 2.13.0
	 */
	public const CALCULATION_FALLBACK = '123.45';

	/**
	 * Post meta keys whose value maps a form's fields by slug.
	 *
	 * @since 2.13.0
	 */
	public const FIELD_MAPPING_META_KEYS = [ '_srfm_native_integrations', '_srfm_integrations_webhooks', '_srfm_user_registration_settings', '_srfm_raw_cpt_meta' ];

	/**
	 * Post ID of the in-memory form a preview renders. Never written to the database.
	 *
	 * @since 2.13.0
	 */
	private const PREVIEW_POST_ID = 2147483600;

	/**
	 * The "Included" feature each Pro template key adds.
	 *
	 * @since 2.13.0
	 */
	private const FEATURE_BY_KEY = [
		'conditions'       => 'conditional_logic',
		'confirmations'    => 'conditional_confirmations',
		'email_conditions' => 'conditional_emails',
		'attach_uploads'   => 'email_attachments',
		'save_resume'      => 'save_resume',
		'survey'           => 'survey',
		'conversational'   => 'conversational',
		'quiz'             => 'quiz',
		'partial_entries'  => 'partial_entries',
		'cpt'              => 'post_submission',
		'registration'     => 'user_registration',
	];

	/**
	 * Field types whose answer can't be put in a message as `{form:slug}`: no
	 * value (consent, steps, buttons, HTML), a file or image (uploads,
	 * signatures), a secret (passwords), rows of values (repeaters), or a
	 * payment, whose details come with the `{form-payment:…}` result tags.
	 *
	 * @since 2.13.0
	 */
	private const NO_ANSWER_TYPES = [ 'gdpr', 'page-break', 'inline-button', 'upload', 'html', 'signature', 'payment', 'password', 'repeater' ];

	/**
	 * Hooks whose callbacks print or enqueue assets on the preview page.
	 *
	 * @since 2.13.0
	 */
	private const PREVIEW_ASSET_HOOKS = [ 'wp_enqueue_scripts', 'wp_head', 'wp_print_styles', 'wp_print_scripts', 'wp_body_open', 'wp_footer', 'wp_print_footer_scripts' ];

	/**
	 * Core styles the preview keeps besides the form's own: block and global
	 * styles, which give the form the theme's fonts and colors.
	 *
	 * @since 2.13.0
	 */
	private const PREVIEW_CORE_STYLES = [ 'wp-block-library', 'global-styles', 'classic-theme-styles', 'core-block-supports', 'core-block-supports-duotone', 'wp-img-auto-sizes-contain' ];

	/**
	 * Spam-protection scripts SureForms loads under their providers' handles.
	 *
	 * @since 2.13.0
	 */
	private const PREVIEW_CAPTCHA_SCRIPTS = [ 'google-recaptcha', 'google-recaptcha-invisible', 'hcaptcha' ];

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
	 * Metas of the in-memory preview form, in the post meta cache's shape.
	 *
	 * @var array<string, array<int, mixed>>
	 */
	private static $preview_meta = [];

	/**
	 * Constructor
	 *
	 * @since 2.13.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'template_redirect', [ $this, 'maybe_render_preview' ], 1 );
	}

	/**
	 * Register the template REST routes.
	 *
	 * @since 2.13.0
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
					// Add New Form start screen choice in play, '' when none.
					'start_choice'           => [
						'type'    => 'string',
						'enum'    => array_merge( [ '' ], Form_Start::CHOICES ),
						'default' => '',
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
			'/form-templates/track',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'rest_record_action' ],
				'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
				'args'                => [
					'action'      => [
						'type'     => 'string',
						'required' => true,
						'enum'     => [ 'library_open', 'preview', 'upgrade_click' ],
					],
					'template_id' => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
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
	 * @since 2.13.0
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
		 * `email_intro`, `autoresponder` ([ 'subject', 'lines', 'greeting', 'signoff' ]),
		 * `form_title` (the created form's name, when it differs from the library
		 * card's `title`, e.g. a generic card for an example topic), `steps`
		 * (step titles for multi-step forms), `features` (extra "Included"
		 * feature ids, such as `calculation`) and the Pro keys in PRO_FEATURE_KEYS.
		 *
		 * `email_all_data => false` leaves the answers out of the site owner's
		 * email, so it only sends `email_intro` (a template without an intro
		 * keeps them).
		 *
		 * `requires` is the plan a template needs: `free` (default), `starter`,
		 * `pro` or `business`; `is_pro => true` is read as `starter`. A paid
		 * template can also set `min_pro_version`, the lowest SureForms Pro version
		 * it works with.
		 *
		 * A field may carry `pro` (schema keys that replace its own with SureForms
		 * Pro), `pro_only` (only added with Pro), `pro_tier` (the plan those need,
		 * `starter` by default; a field with `pro_tier` and no `pro` is only added
		 * from that plan), `blockAttributes` (extra block attributes, such as
		 * `minValue` and `maxValue` on a number field) and `no_sample` (the
		 * library's example answers leave it empty, e.g. a field a condition
		 * hides for the example's first option).
		 *
		 * @since 2.13.0
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
	 * @since 2.13.0
	 * @return array<string, mixed>|null
	 */
	public static function get_template( $template_id ) {
		$templates = self::get_templates();
		return $templates[ sanitize_key( $template_id ) ] ?? null;
	}

	/**
	 * Template summaries for the picker: everything except the field schema.
	 *
	 * @since 2.13.0
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_templates_for_js() {
		$list = [];
		$tier = Helper::get_pro_tier();
		foreach ( self::get_templates() as $template ) {
			$resolved = self::resolve_fields( $template, $tier );
			$fields   = array_values(
				array_filter(
					array_map(
						// Hidden fields are never seen by the visitor, so the card doesn't list them.
						static fn( $field ) => ! in_array( $field['fieldType'] ?? '', [ 'gdpr', 'page-break', 'hidden' ], true ) ? Helper::get_string_value( $field['label'] ?? '' ) : '',
						$resolved
					)
				)
			);
			$features    = self::get_features( $template, $resolved, $tier );
			$requires    = self::get_required_tier( $template );
			$lock_reason = self::lock_reason( $template );
			// A locked template's messages and card describe its Premium version,
			// whose answers (a calculated total, say) the messages use.
			$message_fields = '' === $lock_reason ? $resolved : self::resolve_fields( $template, 'business' );
			$thumbnail      = self::get_thumbnail_url( $template, $tier );

			$list[] = [
				'id'              => $template['id'],
				'preview_url'     => self::get_preview_url( Helper::get_string_value( $template['id'] ) ),
				'title'           => Helper::get_string_value( $template['title'] ?? '' ),
				'form_title'      => self::get_form_title( $template ),
				'description'     => Helper::get_string_value( $template['description'] ?? '' ),
				'category'        => Helper::get_string_value( $template['category'] ?? 'other' ),
				'icon'            => Helper::get_string_value( $template['icon'] ?? '' ),
				'theme'           => isset( self::get_themes()[ $template['theme'] ?? '' ] ) ? Helper::get_string_value( $template['theme'] ) : 'ocean',
				// Built from the same version as the messages: what this site gets, or the Premium version while locked.
				'form_type'       => self::get_form_type( $template, $message_fields ),
				'fields'          => $fields,
				// The first fields, drawn as a small form on a card that has no picture.
				// Drawn when there's no picture, or the picture doesn't load.
				'card_fields'     => self::get_card_fields( $message_fields ),
				'features'        => $features,
				// What the template gains with a higher SureForms Pro plan.
				'pro_features'    => 'business' === $tier ? [] : array_values( array_diff( self::get_features( $template, self::resolve_fields( $template, 'business' ), 'business' ), $features ) ),
				'submit_text'     => Helper::get_string_value( $template['submit_text'] ?? '' ),
				'messages'        => self::get_messages( $template, $message_fields ),
				'answers'         => self::get_answers( $message_fields ),
				'requires'        => $requires,
				'is_pro'          => 'free' !== $requires,
				'is_locked'       => '' !== $lock_reason,
				// `tier` (needs a higher plan) or `update` (needs a newer SureForms Pro).
				'lock_reason'     => $lock_reason,
				'unlock_url'      => self::get_unlock_url( Helper::get_string_value( $template['id'] ), $lock_reason ),
				// Picture of the Premium version, shown instead of the live preview while locked.
				'preview_image'   => self::get_preview_image_url( $template ),
				// Picture of the form on the library card, so the grid never loads live previews.
				'thumbnail'       => $thumbnail,
				// How many of the Premium version's fields the live preview can't show here.
				'preview_missing' => '' === $lock_reason ? 0 : self::count_missing_preview_fields( $template, $resolved ),
				'setup'           => self::get_setup( $template ),
			];
		}
		return $list;
	}

	/**
	 * How a template is laid out, for the library's "Form type" filter.
	 *
	 * @param array<string, mixed>             $template Template.
	 * @param array<int, array<string, mixed>> $fields   Fields from resolve_fields().
	 * @since 2.13.0
	 * @return string `conversational`, `multistep` or `single`.
	 */
	public static function get_form_type( $template, $fields ) {
		if ( ! empty( $template['conversational'] ) ) {
			return 'conversational';
		}
		foreach ( $fields as $field ) {
			if ( 'page-break' === ( $field['fieldType'] ?? '' ) ) {
				return 'multistep';
			}
		}
		return 'single';
	}

	/**
	 * The first fields a visitor sees, in the compact shape the library card
	 * draws as a small form: `t` field type, `l` label, `p` placeholder (a
	 * dropdown's first option, a date's format), `o` up to three option labels
	 * and `w` `half` when the field shares its row. Hidden fields, steps and
	 * HTML blocks are left out; works for any template, added ones too.
	 *
	 * @param array<int, array<string, mixed>> $fields Fields from resolve_fields().
	 * @since 2.13.0
	 * @return array<int, array{t: string, l: string, p: string, o: array<int, string>, w: string}>
	 */
	public static function get_card_fields( $fields ) {
		$card = [];
		foreach ( $fields as $field ) {
			$type = Helper::get_string_value( $field['fieldType'] ?? '' );
			if ( in_array( $type, [ 'hidden', 'page-break', 'html' ], true ) ) {
				continue;
			}
			$options = [];
			foreach ( Helper::get_array_value( $field['fieldOptions'] ?? [] ) as $option ) {
				$option = is_array( $option ) ? ( $option['optionTitle'] ?? $option['label'] ?? '' ) : $option;
				$option = trim( wp_strip_all_tags( Helper::get_string_value( $option ) ) );
				if ( '' !== $option ) {
					$options[] = $option;
				}
			}
			$placeholder = Helper::get_string_value( $field['placeholder'] ?? '' );
			if ( '' === $placeholder && 'dropdown' === $type ) {
				$placeholder = $options[0] ?? '';
			} elseif ( '' === $placeholder && 'date-picker' === $type ) {
				$placeholder = Helper::get_string_value( $field['dateFormat'] ?? '' );
			}
			$attributes = Helper::get_array_value( $field['blockAttributes'] ?? [] );
			$width      = (float) ( $attributes['fieldWidth'] ?? 100 );

			$card[] = [
				't' => $type,
				// Consent labels run long; the card shows a line or two.
				'l' => mb_substr( trim( wp_strip_all_tags( Helper::get_string_value( $field['label'] ?? '' ) ) ), 0, 120 ),
				'p' => mb_substr( trim( wp_strip_all_tags( $placeholder ) ), 0, 60 ),
				'o' => array_slice( $options, 0, 3 ),
				'w' => $width > 0 && $width <= 50 ? 'half' : 'full',
			];
			// More than the card has room for.
			if ( count( $card ) >= 8 ) {
				break;
			}
		}
		return $card;
	}

	/**
	 * The plan a template needs: `requires`, or `starter` for the older
	 * `is_pro` flag, or `free`.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since 2.13.0
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
	 * @since 2.13.0
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
		 * @since 2.13.0
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
	 * @since 2.13.0
	 * @return bool
	 */
	public static function is_template_available( $template ) {
		return '' === self::lock_reason( $template );
	}

	/**
	 * Lowest `SRFM_PRO_TEMPLATE_API` that builds every Pro key a template declares.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since 2.13.0
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
	 * @since 2.13.0
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
	 * @since 2.13.0
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
	 * URL of a template's `preview_image`: a picture of its Premium version,
	 * for sites where the live preview can't render the blocks it is built on.
	 *
	 * The catalog gives a file name relative to the plugin's `images/templates/`
	 * folder (e.g. `quiz.webp`); a template added through `srfm_form_templates`
	 * may give a full http(s) URL instead.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since 2.13.0
	 * @return string Unescaped URL, '' when the template has none.
	 */
	public static function get_preview_image_url( $template ) {
		$image = trim( Helper::get_string_value( $template['preview_image'] ?? '' ) );
		if ( '' === $image ) {
			return '';
		}
		if ( preg_match( '#^https?://#i', $image ) ) {
			return esc_url_raw( $image );
		}
		// A plain file path inside images/templates/, nothing that leaves it.
		if ( false !== strpos( $image, '..' ) || ! preg_match( '#^[a-z0-9][a-z0-9/_.-]*\.(webp|png|jpe?g|avif)$#i', $image ) ) {
			return '';
		}
		// Versioned, so a plugin update replaces cached pictures.
		return add_query_arg( 'ver', SRFM_VER, SRFM_URL . 'images/templates/' . ltrim( $image, '/' ) );
	}

	/**
	 * URL of the picture on a template's library card: its `preview_image`, or
	 * the plugin's `images/templates/<id>.webp`. Both are screenshots of the
	 * template's preview, taken with bin/capture-template-images.js.
	 *
	 * A free template that SureForms Pro builds differently (step dividers,
	 * Pro fields) also has `<id>-pro.webp`, its Business build, which sites
	 * on a paid plan see instead.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param string               $tier     Plan tier, defaults to this site's (Helper::get_pro_tier()).
	 * @since 2.13.0
	 * @return string Unescaped URL, '' when the template has no picture.
	 */
	public static function get_thumbnail_url( $template, $tier = '' ) {
		$image = self::get_preview_image_url( $template );
		if ( '' !== $image ) {
			return $image;
		}
		$id = sanitize_key( Helper::get_string_value( $template['id'] ?? '' ) );
		if ( '' === $id || ! file_exists( SRFM_DIR . 'images/templates/' . $id . '.webp' ) ) {
			return '';
		}
		$tier = '' === $tier ? Helper::get_pro_tier() : $tier;
		$file = 'free' !== $tier && file_exists( SRFM_DIR . 'images/templates/' . $id . '-pro.webp' ) ? $id . '-pro.webp' : $id . '.webp';
		return add_query_arg( 'ver', SRFM_VER, SRFM_URL . 'images/templates/' . $file );
	}

	/**
	 * How many fields of a template's Premium version the live preview on this
	 * site leaves out: fields only added on a higher plan, and fields whose
	 * block this site doesn't have.
	 *
	 * @param array<string, mixed>             $template Template.
	 * @param array<int, array<string, mixed>> $resolved Fields from resolve_fields() for this site.
	 * @since 2.13.0
	 * @return int
	 */
	public static function count_missing_preview_fields( $template, $resolved ) {
		$here     = array_map( static fn( $field ) => Helper::get_string_value( $field['slug'] ?? '' ), $resolved );
		$registry = \WP_Block_Type_Registry::get_instance();
		$missing  = 0;
		foreach ( self::resolve_fields( $template, 'business' ) as $field ) {
			$type = Helper::get_string_value( $field['fieldType'] ?? '' );
			if ( in_array( $type, [ 'page-break', 'hidden' ], true ) ) {
				continue; // Not shown as a field anyway.
			}
			if ( ! in_array( Helper::get_string_value( $field['slug'] ?? '' ), $here, true ) || ( '' !== $type && ! $registry->is_registered( 'srfm/' . $type ) ) ) {
				++$missing;
			}
		}
		return $missing;
	}

	/**
	 * What a template needs set up on the site to work fully, from its `setup`
	 * key: a list of `stripe`, `paypal` and `google_maps`.
	 * Unknown ids, and PayPal without the SureForms Pro that provides it, are
	 * left out. Setup never stops a form being created.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since 2.13.0
	 * @return array<int, array{id: string, label: string, done: bool, url: string}>
	 */
	public static function get_setup( $template ) {
		$setup = [];
		foreach ( array_unique( array_map( [ Helper::class, 'get_string_value' ], Helper::get_array_value( $template['setup'] ?? [] ) ) ) as $id ) {
			$item = self::resolve_setup_item( $id );
			if ( null !== $item ) {
				$setup[] = $item;
			}
		}
		return $setup;
	}

	/**
	 * The thank-you message and emails a template sets, for the preview to
	 * show and edit before the form is created.
	 *
	 * @param array<string, mixed>             $template Template.
	 * @param array<int, array<string, mixed>> $fields   Fields from resolve_fields().
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	public static function get_messages( $template, $fields ) {
		$confirmation  = Helper::get_array_value( $template['confirmation'] ?? [] );
		$autoresponder = ! empty( $template['autoresponder'] ) && self::has_required_email( $fields ) ? Helper::get_array_value( $template['autoresponder'] ) : null;
		$result_tags   = self::get_result_tags( $template, $fields );

		// Every text the messages hold, to find the result tags they use.
		$texts = array_merge(
			[ $confirmation['heading'] ?? '', $confirmation['text'] ?? '', $template['email_subject'] ?? '', $template['email_intro'] ?? '' ],
			null === $autoresponder ? [] : array_merge( [ $autoresponder['subject'] ?? '', $autoresponder['greeting'] ?? '', $autoresponder['signoff'] ?? '' ], Helper::get_array_value( $autoresponder['lines'] ?? [] ) )
		);

		return [
			// Example values for the SureForms Pro tags (quiz score, payment amount, …)
			// the messages use or the "Results" chips insert.
			'tag_samples'   => self::get_tag_samples( implode( "\n", array_map( [ Helper::class, 'get_string_value' ], $texts ) ), $result_tags, $fields, $template ),
			// Results the messages can include: { tag, label }.
			'result_tags'   => $result_tags,
			'confirmation'  => [
				'heading' => Helper::get_string_value( $confirmation['heading'] ?? '' ),
				'text'    => Helper::get_string_value( $confirmation['text'] ?? '' ),
			],
			'email'         => [
				'subject'  => Helper::get_string_value( $template['email_subject'] ?? '' ),
				'intro'    => Helper::get_string_value( $template['email_intro'] ?? '' ),
				// Whether every answer is listed below the intro.
				'all_data' => self::includes_all_data( $template ),
			],
			'autoresponder' => null === $autoresponder ? null : [
				'subject'  => Helper::get_string_value( $autoresponder['subject'] ?? '' ),
				'greeting' => Helper::get_string_value( $autoresponder['greeting'] ?? __( 'Hi {form:full-name},', 'sureforms' ) ),
				'lines'    => array_map( [ Helper::class, 'get_string_value' ], Helper::get_array_value( $autoresponder['lines'] ?? [] ) ),
				'signoff'  => self::autoresponder_signoff( $autoresponder ),
			],
		];
	}

	/**
	 * The answers a message can include as `{form:slug}`, each with an example
	 * value for the preview. Fields without a readable answer (consent, steps,
	 * buttons, uploads, HTML, signatures, payments, passwords and repeaters) are
	 * left out; hidden fields stay, as their values (a source page, UTM tags)
	 * are useful in the site owner's email.
	 *
	 * @param array<int, array<string, mixed>> $fields Fields from resolve_fields().
	 * @since 2.13.0
	 * @return array<int, array{slug: string, label: string, required: bool, sample: string}>
	 */
	public static function get_answers( $fields ) {
		$answers = [];
		foreach ( $fields as $field ) {
			$type = Helper::get_string_value( $field['fieldType'] ?? '' );
			if ( in_array( $type, self::NO_ANSWER_TYPES, true ) ) {
				continue;
			}
			$answers[] = [
				'slug'     => Helper::get_string_value( $field['slug'] ?? '' ),
				'label'    => Helper::get_string_value( $field['label'] ?? '' ),
				'required' => ! empty( $field['required'] ),
				'sample'   => self::calculated_sample( $field, $fields ) ?? self::sample_answer( $field ),
			];
		}
		return $answers;
	}

	/**
	 * Results a template's messages can include, from the SureForms Pro
	 * features it uses: quiz scores, calculated totals, payment details and
	 * poll results. The library offers them as "Results" chips.
	 *
	 * @param array<string, mixed>             $template Template.
	 * @param array<int, array<string, mixed>> $fields   Fields from resolve_fields().
	 * @since 2.13.0
	 * @return array<int, array{tag: string, label: string}>
	 */
	public static function get_result_tags( $template, $fields ) {
		$features = array_map( [ Helper::class, 'get_string_value' ], Helper::get_array_value( $template['features'] ?? [] ) );
		$tags     = [];

		if ( ! empty( $template['quiz'] ) || in_array( 'quiz', $features, true ) ) {
			$tags['{quiz_score}']        = __( 'Quiz score', 'sureforms' );
			$tags['{quiz_percentage}']   = __( 'Quiz percentage', 'sureforms' );
			$tags['{quiz_pass_fail}']    = __( 'Pass or fail', 'sureforms' );
			$tags['{quiz_letter_grade}'] = __( 'Letter grade', 'sureforms' );
			$tags['{quiz_result}']       = __( 'All quiz results', 'sureforms' );
		}
		foreach ( $fields as $field ) {
			$slug = Helper::get_string_value( $field['slug'] ?? '' );
			if ( '' === $slug ) {
				continue;
			}
			if ( '' !== self::get_formula( $field ) ) {
				$tags[ '{form:' . $slug . '}' ] = Helper::get_string_value( $field['label'] ?? $slug );
			}
			if ( 'payment' === ( $field['fieldType'] ?? '' ) ) {
				$tags[ '{form-payment:' . $slug . ':amount}' ]   = __( 'Amount paid', 'sureforms' );
				$tags[ '{form-payment:' . $slug . ':order-id}' ] = __( 'Order ID', 'sureforms' );
				$tags[ '{form-payment:' . $slug . ':status}' ]   = __( 'Payment status', 'sureforms' );
			}
		}
		if ( ! empty( $template['survey'] ) || in_array( 'survey', $features, true ) ) {
			$tags['{poll_results}'] = __( 'Poll results', 'sureforms' );
		}

		$list = [];
		foreach ( $tags as $tag => $label ) {
			$list[] = [
				'tag'   => $tag,
				'label' => $label,
			];
		}
		return $list;
	}

	/**
	 * Example values for the SureForms Pro tags in a text and for the result
	 * tags, for the message previews. Answers (`{form:slug}`) aren't included:
	 * they come with get_answers().
	 *
	 * @param string                                        $text        Message text.
	 * @param array<int, array{tag: string, label: string}> $result_tags From get_result_tags().
	 * @param array<int, array<string, mixed>>              $fields      Fields from resolve_fields().
	 * @param array<string, mixed>                          $template    Template, for its quiz settings.
	 * @since 2.13.0
	 * @return array<string, string> Sample by tag, e.g. `{quiz_score}` => '4 / 5'.
	 */
	public static function get_tag_samples( $text, $result_tags, $fields, $template = [] ) {
		preg_match_all( '/\{(?:quiz_[a-z_]+|poll_results|calculation-result|form-payment:[a-z0-9_-]+:[a-z-]+)\}/i', $text, $matches );
		$tags    = array_merge( $matches[0], array_column( $result_tags, 'tag' ) );
		$samples = [];
		foreach ( array_unique( $tags ) as $tag ) {
			$sample = self::tag_sample( strtolower( $tag ), $fields, $template );
			if ( null !== $sample ) {
				$samples[ $tag ] = $sample;
			}
		}
		return $samples;
	}

	/**
	 * Example result of a calculated field: its formula worked out from the
	 * example answers and rounded, as the entry saves it. The field's prefix
	 * and suffix (a currency symbol, a unit) are only shown on the form, not
	 * saved, so they are left out. 123.45 when the formula can't be worked out.
	 *
	 * @param array<string, mixed>             $field  Field schema.
	 * @param array<int, array<string, mixed>> $fields All fields, for the answers the formula uses.
	 * @since 2.13.0
	 * @return string|null Null for a field that isn't calculated.
	 */
	public static function calculated_sample( $field, $fields ) {
		$formula = self::get_formula( $field );
		if ( '' === $formula ) {
			return null;
		}

		/**
		 * Filters the example result of a calculated field in the template
		 * library, e.g. so SureForms Pro can work it out with its own calculator.
		 *
		 * @since 2.13.0
		 * @param float|null                       $value   Result from the free evaluator, null when it couldn't work it out.
		 * @param string                           $formula Formula, with `{form:slug}` tags.
		 * @param array<string, mixed>             $field   Calculated field.
		 * @param array<int, array<string, mixed>> $fields  All fields.
		 */
		$value = apply_filters( 'srfm_form_template_calculation_sample', self::evaluate_formula( $formula, $fields ), $formula, $field, $fields );

		$attributes = Helper::get_array_value( $field['blockAttributes'] ?? [] );
		if ( ! is_numeric( $value ) ) {
			return self::CALCULATION_FALLBACK;
		}
		$value    = (float) $value;
		$round    = $field['calculationRound'] ?? $attributes['calculationRound'] ?? null;
		$decimals = is_numeric( $round ) ? max( 0, min( 6, (int) $round ) ) : ( floor( $value ) === $value ? 0 : 2 );

		return number_format( $value, $decimals );
	}

	/**
	 * Work out a formula from the example answers.
	 *
	 * @param string                           $formula Formula with `{form:slug}` tags.
	 * @param array<int, array<string, mixed>> $fields  Fields.
	 * @param int                              $depth   Calculated fields already being worked out, against loops.
	 * @since 2.13.0
	 * @return float|null Null when a tag has no number or the arithmetic is invalid.
	 */
	public static function evaluate_formula( $formula, $fields, $depth = 0 ) {
		// The editor saves the tags escaped as %{%form:slug%}%.
		$formula = str_replace( [ '%{%', '%}%' ], [ '{', '}' ], $formula );
		$invalid = false;

		$expression = preg_replace_callback(
			'/\{([^:{}]+):([^{}]+)\}/',
			static function ( $match ) use ( $fields, $depth, &$invalid ) {
				$value = 'form' === trim( $match[1] ) ? self::numeric_sample( trim( $match[2] ), $fields, $depth ) : null;
				if ( null === $value ) {
					$invalid = true;
					return '0';
				}
				$number = rtrim( rtrim( sprintf( '%.10F', abs( $value ) ), '0' ), '.' );
				$number = '' === $number ? '0' : $number;
				// Parenthesized, so a negative answer stays one value next to any operator.
				return $value < 0 ? '(-' . $number . ')' : $number;
			},
			$formula
		);

		if ( $invalid || ! is_string( $expression ) ) {
			return null;
		}
		return Formula::evaluate( $expression );
	}

	/**
	 * Clean up the thank-you message and email text edited in the preview.
	 *
	 * @param mixed $raw `messages` from the create request.
	 * @since 2.13.0
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
		if ( isset( $raw['email_all_data'] ) ) {
			$clean['email_all_data'] = rest_sanitize_boolean( $raw['email_all_data'] );
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
			if ( isset( $auto['signoff'] ) && is_string( $auto['signoff'] ) ) {
				$clean['autoresponder']['signoff'] = mb_substr( sanitize_textarea_field( $auto['signoff'] ), 0, 300 );
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
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	public static function apply_messages( $template, $messages ) {
		if ( ! empty( $messages['confirmation'] ) ) {
			$template['confirmation'] = array_merge( Helper::get_array_value( $template['confirmation'] ?? [] ), Helper::get_array_value( $messages['confirmation'] ) );
		}
		if ( isset( $messages['email_all_data'] ) ) {
			$template['email_all_data'] = (bool) $messages['email_all_data'];
		}
		// An emptied subject keeps the template's, since an email needs one. An
		// emptied intro stays empty, as the preview showed it.
		if ( isset( $messages['email_subject'] ) && '' !== $messages['email_subject'] ) {
			$template['email_subject'] = $messages['email_subject'];
		}
		if ( isset( $messages['email_intro'] ) ) {
			$template['email_intro'] = $messages['email_intro'];
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
	 * Visual themes a template can use: the form's colors and the page
	 * behind it. Everything maps to the free form styling options, so a
	 * form created from a template keeps its look and stays editable in the
	 * Style tab.
	 *
	 * Each theme is a white form on a light page of its own color, with an
	 * accent that keeps white button text readable (WCAG AA). A dark theme
	 * also carries a light `default`: a template on it starts light, and the
	 * dark look is there to pick in the preview.
	 *
	 * @since 2.13.0
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_themes() {
		return [
			'ocean'    => [
				'page'       => '#E3EBFB',
				'background' => '#FFFFFF',
				'primary'    => '#2563EB',
				'text'       => '#1E293B',
				'on_primary' => '#FFFFFF',
			],
			'midnight' => [
				'page'       => '#E4E8EF',
				'background' => '#111827',
				'primary'    => '#38BDF8',
				'text'       => '#E2E8F0',
				'on_primary' => '#0F172A',
				'default'    => [
					'background' => '#FFFFFF',
					'primary'    => '#0369A1',
					'text'       => '#1E293B',
					'on_primary' => '#FFFFFF',
				],
			],
			'mint'     => [
				'page'       => '#DEF2E8',
				'background' => '#FFFFFF',
				'primary'    => '#047857',
				'text'       => '#1F2F2A',
				'on_primary' => '#FFFFFF',
			],
			'lavender' => [
				'page'       => '#EAE5FA',
				'background' => '#FFFFFF',
				'primary'    => '#7C3AED',
				'text'       => '#27203A',
				'on_primary' => '#FFFFFF',
			],
			'sunset'   => [
				'page'       => '#FAE6DD',
				'background' => '#FFFFFF',
				'primary'    => '#C2410C',
				'text'       => '#33201A',
				'on_primary' => '#FFFFFF',
			],
			'sand'     => [
				'page'       => '#F8EED6',
				'background' => '#FFFFFF',
				'primary'    => '#B45309',
				'text'       => '#2D2416',
				'on_primary' => '#FFFFFF',
			],
		];
	}

	/**
	 * Presets for the preview's style panel: name, background, accent and
	 * the text colors that go with them, so the preview can restyle itself
	 * exactly like resolve_theme() does.
	 *
	 * A dark preset also carries the light look a template on it starts
	 * with (`default`), so the card and the preview start out the same.
	 *
	 * @since 2.13.0
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_themes_for_js() {
		$to_js   = static fn( $colors ) => [
			'background' => $colors['background'],
			'accent'     => $colors['primary'],
			'text'       => $colors['text'],
			'onPrimary'  => $colors['on_primary'],
		];
		$presets = [];
		foreach ( self::get_themes() as $id => $theme ) {
			$preset = array_merge( [ 'id' => $id ], $to_js( $theme ), [ 'page' => $theme['page'] ] );
			if ( ! empty( $theme['default'] ) ) {
				$preset['default'] = $to_js( $theme['default'] );
			}
			$presets[] = $preset;
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
	 * @since 2.13.0
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
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	public static function resolve_theme( $template, $style = [] ) {
		$themes = self::get_themes();
		$preset = Helper::get_string_value( $style['preset'] ?? '' );
		if ( isset( $themes[ $preset ] ) ) {
			$theme = $themes[ $preset ];
		} else {
			// The template's own look: a dark theme starts light.
			$theme = self::get_template_theme( $template );
			$theme = array_merge( $theme, Helper::get_array_value( $theme['default'] ?? [] ) );
		}
		unset( $theme['default'] );

		if ( ! empty( $style['background'] ) ) {
			$background          = Helper::get_string_value( $style['background'] );
			$theme['background'] = $background;
			$theme['text']       = self::is_dark( $background ) ? '#E2E8F0' : '#1E293B';
		}

		if ( ! empty( $style['accent'] ) ) {
			$accent              = Helper::get_string_value( $style['accent'] );
			$theme['primary']    = $accent;
			$theme['on_primary'] = self::is_dark( $accent ) ? '#FFFFFF' : '#0F172A';
		}

		// The form as a card on its page, like the library's preview.
		$theme['radius']  = 10;
		$theme['padding'] = 28;

		return $theme;
	}

	/**
	 * Template categories that have at least one template, in display order.
	 *
	 * @since 2.13.0
	 * @return array<int, array<string, string>>
	 */
	public static function get_categories() {
		$used = array_flip( array_map( [ Helper::class, 'get_string_value' ], array_column( self::get_templates(), 'category' ) ) );

		return array_values(
			array_filter(
				self::all_categories(),
				static fn( $category ) => isset( $used[ $category['id'] ] )
			)
		);
	}

	/**
	 * A template's fields as they will be built on a plan: `pro` variants
	 * applied and `pro_only` fields kept only from their `pro_tier` (Starter
	 * by default).
	 *
	 * @param array<string, mixed> $template Template.
	 * @param string|bool          $tier     Plan tier from Helper::get_pro_tier(). `true` is
	 *                                       read as `business` and `false` as `free`.
	 * @since 2.13.0
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
	 * @since 2.13.0
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
		 * @since 2.13.0
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
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	public static function apply_block_attributes( $attributes, $question ) {
		if ( ! empty( $question['blockAttributes'] ) && is_array( $question['blockAttributes'] ) ) {
			$attributes = array_merge( $attributes, $question['blockAttributes'] );
		}
		return $attributes;
	}

	/**
	 * The name of a form created from a template: its `form_title`, or the
	 * library card's `title` when it has none.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since 2.13.0
	 * @return string
	 */
	public static function get_form_title( $template ) {
		$title = Helper::get_string_value( $template['form_title'] ?? '' );
		return '' !== $title ? $title : Helper::get_string_value( $template['title'] ?? '' );
	}

	/**
	 * Block markup for a template.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since 2.13.0
	 * @return string|WP_Error
	 */
	public static function build_content( $template ) {
		$fields  = self::resolve_fields( $template, Helper::get_pro_tier() );
		$request = new WP_REST_Request( 'POST' );
		$request->set_param(
			'form_data',
			[
				'form' => [
					'formTitle'  => self::get_form_title( $template ),
					'formFields' => self::group_register_fields( $template, $fields ),
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
		 * @since 2.13.0
		 * @param string                           $content  Block markup.
		 * @param array<string, mixed>             $template Template.
		 * @param array<int, array<string, mixed>> $fields   Fields from resolve_fields(), as built.
		 */
		return Helper::get_string_value( apply_filters( 'srfm_form_template_content', Helper::get_string_value( $content ), $template, $fields ) );
	}

	/**
	 * The fields to build, with the ones a `registration` template signs people
	 * up with (email, username, password, names, website) inside one register
	 * block. SureForms Business's User Registration settings only work with that
	 * block in the form: without it, opening them turns the registration off.
	 * The template's own field list stays flat, so messages, answers and checks
	 * see every field.
	 *
	 * The fields are the ones SureForms Pro signs people up with: the declared
	 * slugs, else the first email and password fields (`'registration' => true`
	 * declares none). Only a run of them next to each other is wrapped, from the
	 * first one, so no field moves past another or across a step; a field
	 * further on stays where it is and still works.
	 *
	 * @param array<string, mixed>             $template Template.
	 * @param array<int, array<string, mixed>> $fields   Fields from resolve_fields().
	 * @since 2.13.0
	 * @return array<int, array<string, mixed>>
	 */
	public static function group_register_fields( $template, $fields ) {
		if ( empty( $template['registration'] ) ) {
			return $fields;
		}
		$options = is_array( $template['registration'] ) ? $template['registration'] : [];
		$first   = static function ( $type ) use ( $fields ) {
			foreach ( $fields as $field ) {
				if ( ( $field['fieldType'] ?? '' ) === $type ) {
					return Helper::get_string_value( $field['slug'] ?? '' );
				}
			}
			return '';
		};

		// Like SureForms Pro's registration builder: the first email and password fields when not declared.
		$slugs = [
			isset( $options['email'] ) ? Helper::get_string_value( $options['email'] ) : $first( 'email' ),
			isset( $options['password'] ) ? Helper::get_string_value( $options['password'] ) : $first( 'password' ),
		];
		foreach ( [ 'username', 'first_name', 'last_name', 'website' ] as $key ) {
			if ( isset( $options[ $key ] ) && is_string( $options[ $key ] ) ) {
				$slugs[] = $options[ $key ];
			}
		}
		$slugs = array_filter( $slugs );

		$register = static fn( $inner ) => [
			'fieldType'   => 'register',
			'slug'        => 'register',
			'innerFields' => $inner,
		];

		$grouped = [];
		$inner   = [];
		$state   = 'before'; // Then 'in' the run, then 'after' it.
		foreach ( $fields as $field ) {
			$type     = Helper::get_string_value( $field['fieldType'] ?? '' );
			$is_inner = in_array( Helper::get_string_value( $field['slug'] ?? '' ), $slugs, true ) && in_array( $type, Field_Mapping::REGISTER_INNER_TYPES, true );
			if ( $is_inner && 'after' !== $state ) {
				$state   = 'in';
				$inner[] = $field;
				continue;
			}
			if ( 'in' === $state ) {
				$grouped[] = $register( $inner );
				$state     = 'after';
			}
			$grouped[] = $field;
		}
		if ( 'before' === $state ) {
			return $fields;
		}
		if ( 'in' === $state ) {
			$grouped[] = $register( $inner );
		}
		return $grouped;
	}

	/**
	 * Point a template's blocks at the form they belong to.
	 *
	 * Blocks are built with `formId: 0`; the block editor fills it in when the
	 * form is opened there. Until then features that look a form up from its
	 * blocks, like loading SureForms Pro's Save & Resume styles, can't find it,
	 * so the template sets it right away.
	 *
	 * @param string $content Block markup.
	 * @param int    $form_id Form ID; 0 leaves the markup as it is.
	 * @since 2.13.0
	 * @return string
	 */
	public static function set_form_id( $content, $form_id ) {
		$form_id = absint( $form_id );
		if ( 0 === $form_id ) {
			return $content;
		}
		return Helper::get_string_value( preg_replace( '/"formId":0(?=[,}\s])/', '"formId":' . $form_id, $content ) );
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
	 * @since 2.13.0
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
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	public static function build_settings_metas( $template, $content = '', $context = 'swap' ) {
		return self::filter_metas( self::settings_metas( $template ), $template, $content, $context );
	}

	/**
	 * The metas that carry a template's look: form styling, and the banner on
	 * the form's own page. Used when creating a form and when a template is
	 * applied to an open form in the editor.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param array<string, mixed> $style    Optional style from sanitize_style().
	 * @since 2.13.0
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
				'bg_type'                 => 'color',
				'bg_color'                => $theme['background'],
			]
		);
		// The form's own page (Instant Form) reads its own padding and corner
		// keys, so both get the same values and the form looks alike everywhere.
		foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
			$styling[ 'form_padding_' . $side ]               = $theme['padding'];
			$styling[ 'form_border_radius_' . $side ]         = $theme['radius'];
			$styling[ 'instant_form_padding_' . $side ]       = $theme['padding'];
			$styling[ 'instant_form_border_radius_' . $side ] = $theme['radius'];
		}

		// The form's own page (Instant Form) shows the form title above the
		// form, on the template's page color.
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
	 * @since 2.13.0
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
					'post_title'   => self::get_form_title( $template ),
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

		// The new form's ID is only known now, so its blocks get it in a second write.
		$content = self::set_form_id( $built['content'], $post_id );
		if ( $content !== $built['content'] ) {
			wp_update_post(
				wp_slash(
					[
						'ID'           => $post_id,
						'post_content' => $content,
					]
				)
			);
		}

		Analytics::track_form_template_usage( $template['id'], 'create' );

		return $post_id;
	}

	/**
	 * GET /form-templates
	 *
	 * @since 2.13.0
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
	 * @since 2.13.0
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
		 * @since 2.13.0
		 * @param array<int, string> $keys Meta keys.
		 */
		$filtered = apply_filters( 'srfm_form_template_message_meta_keys', $keys );

		// A broken filter falls back to the defaults: with no keys, a swap the user
		// asked to keep the form's messages would replace them anyway.
		return array_values( array_unique( array_filter( array_map( [ Helper::class, 'get_string_value' ], is_array( $filtered ) ? $filtered : $keys ) ) ) );
	}

	/**
	 * Post meta keys whose value maps a form's fields by slug: the built-in ones
	 * plus any a filter adds. A filter can add keys, never remove one.
	 *
	 * @since 2.13.0
	 * @return array<int, string>
	 */
	public static function get_field_mapping_meta_keys() {
		/**
		 * Filters the post meta keys whose value maps a form's fields by slug. A
		 * form with any of them set gets a new form from a template instead of
		 * having its fields replaced.
		 *
		 * @param array<int, string> $meta_keys Meta keys.
		 * @since 2.13.0
		 */
		$filtered = apply_filters( 'srfm_field_mapping_meta_keys', self::FIELD_MAPPING_META_KEYS );
		$keys     = array_merge( self::FIELD_MAPPING_META_KEYS, is_array( $filtered ) ? array_filter( $filtered, 'is_string' ) : [] );
		return array_values( array_unique( $keys ) );
	}

	/**
	 * Whether a form has something set up that maps its fields by slug: a
	 * native integration, a webhook, a user registration or a post creation
	 * feed (the last two from SureForms Pro). A value that cannot be read counts
	 * as set up. A disabled one counts too, as it can be switched back on.
	 *
	 * @param int $form_id Form ID.
	 * @since 2.13.0
	 * @return bool
	 */
	public static function has_field_mappings( $form_id ) {
		foreach ( self::get_field_mapping_meta_keys() as $meta_key ) {
			$value = get_post_meta( $form_id, $meta_key, true );
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				$value = json_decode( $value, true );
				if ( ! is_array( $value ) ) {
					return true;
				}
			}
			if ( ! empty( $value ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether the site-wide honeypot is on.
	 *
	 * @since 2.13.0
	 * @return bool
	 */
	public static function is_spam_protection_on() {
		$options = get_option( 'srfm_security_settings_options' );
		return is_array( $options ) && ! empty( $options['srfm_honeypot'] );
	}

	/**
	 * Turn on the site-wide honeypot, keeping the other security settings.
	 *
	 * @since 2.13.0
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
	 * @since 2.13.0
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

		Form_Start::record_created( $post_id, $request->get_param( 'start_choice' ), 'template' );

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
	 * @since 2.13.0
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

		$style   = self::sanitize_style( $request->get_param( 'style' ) );
		$form_id = Helper::get_integer_value( $request->get_param( 'form_id' ) );
		$built   = self::build( $template, $style, 'swap', $form_id );
		if ( is_wp_error( $built ) ) {
			return $built;
		}

		$style_metas = self::build_style_metas( $template, $style );
		$has_entries = $form_id > 0 && Entries::get_total_entries_by_status( 'all', $form_id ) > 0;
		$has_mapping = $form_id > 0 && self::has_field_mappings( $form_id );

		return new WP_REST_Response(
			[
				'id'                 => $template['id'],
				'title'              => self::get_form_title( $template ),
				'content'            => $built['content'],
				'has_entries'        => $has_entries,
				// Integrations and webhooks send fields by their slugs, which a
				// swap replaces, so such a form is copied to a new one instead.
				'has_field_mappings' => $has_mapping,
				// The same keys, for the editor to check its unsaved values.
				'mapping_meta_keys'  => self::get_field_mapping_meta_keys(),
				// Applied over the open form's own settings in the editor: every
				// value the template owns, also when it equals the default, so
				// none of the form's old values survive the swap.
				'styling'            => self::template_owned_styling( $style_metas['_srfm_forms_styling'] ),
				'instant_form'       => self::instant_form_style( self::resolve_theme( $template, $style ) ),
				// Button text, emails, thank-you message and steps the template sets.
				// The editor only applies the get_message_meta_keys() ones when the
				// user chooses to replace the form's messages too.
				'settings'           => $built['metas'],
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
	 * @since 2.13.0
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
	 * @since 2.13.0
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
	 * @since 2.13.0
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
	 * @since 2.13.0
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
	 * @since 2.13.0
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
	 * @since 2.13.0
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
	 * @since 2.13.0
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
	 * @since 2.13.0
	 * @return void
	 */
	public static function forget_preview_form() {
		if ( null === self::$preview_post_id ) {
			return;
		}
		wp_cache_delete( self::$preview_post_id, 'posts' );
		wp_cache_delete( self::$preview_post_id, 'post_meta' );
		remove_filter( 'get_post_metadata', [ self::class, 'get_preview_meta' ], 10 );
		foreach ( [ 'add_post_metadata', 'update_post_metadata', 'delete_post_metadata' ] as $hook ) {
			remove_filter( $hook, [ self::class, 'drop_preview_meta_write' ], 10 );
		}
		self::$preview_meta = [];
	}

	/**
	 * Serve the in-memory preview form's metas, so they survive its meta
	 * cache being cleared. Other posts, and keys the form has no value for,
	 * read as usual.
	 *
	 * @param mixed  $value     Short-circuit value; null to read as usual.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key; empty for all metas.
	 * @since 2.13.0
	 * @return mixed
	 */
	public static function get_preview_meta( $value, $object_id, $meta_key ) {
		if ( null !== $value || self::$preview_post_id !== (int) $object_id ) {
			return $value;
		}
		if ( '' === $meta_key ) {
			return self::$preview_meta;
		}
		if ( ! isset( self::$preview_meta[ $meta_key ] ) ) {
			return null;
		}
		// The preview form stores one value per key, so single or not, it is a one-item list.
		return [ maybe_unserialize( Helper::get_string_value( self::$preview_meta[ $meta_key ][0] ?? '' ) ) ];
	}

	/**
	 * Drop a meta write to the in-memory preview form; it is never saved.
	 *
	 * @param mixed $check     Short-circuit value; null to write as usual.
	 * @param int   $object_id Post ID.
	 * @since 2.13.0
	 * @return mixed False for the preview form, otherwise $check.
	 */
	public static function drop_preview_meta_write( $check, $object_id ) {
		return self::$preview_post_id !== (int) $object_id ? $check : false;
	}

	/**
	 * Render a template preview page instead of the requested front-end page.
	 *
	 * The page is the site's own front end: the active theme's styles plus the
	 * real SureForms renderer, so the preview looks exactly like the form will
	 * once it is embedded. Only users who can create forms see it.
	 *
	 * @since 2.13.0
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
		$built = self::build( $template, $style, 'preview', self::get_preview_post_id() );
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
		add_filter( 'srfm_payment_methods_registry', [ self::class, 'preview_payment_methods' ], 99 );
		// Print only the form's own scripts and styles, so other plugins'
		// analytics, pixels, chat widgets and cookie banners don't run here.
		add_action( 'wp_print_styles', [ self::class, 'filter_preview_assets' ], PHP_INT_MAX );
		add_action( 'wp_print_scripts', [ self::class, 'filter_preview_assets' ], PHP_INT_MAX );
		add_action( 'wp_print_footer_scripts', [ self::class, 'filter_preview_assets' ], 9 );

		// Block styles are normally built from the page's main post, which this
		// preview does not have, so build them from the in-memory form instead.
		if ( class_exists( '\Spec_Gb_Helper' ) ) {
			\Spec_Gb_Helper::get_instance()->get_generated_stylesheet( get_post( self::get_preview_post_id() ) );
		}

		// The preview is the in-memory form's page, not whatever page the request
		// URL resolved to. Rendering runs `the_content`, and page builders read the
		// current post there: Elementor replaces the content with the current
		// page's own layout when that page was built with it.
		$preview_post = get_post( self::get_preview_post_id() );
		if ( $preview_post instanceof \WP_Post ) {
			$GLOBALS['post'] = $preview_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The preview is this form's page.
			setup_postdata( $preview_post );
		}

		// SureForms Pro draws the conversational layout on the form's own page
		// only: it switches it on at template_redirect when the page's post is
		// a conversational form. So such a template previews as that page:
		// Pro's hook sees the in-memory form set above, and the form's page
		// template renders it once every hook has run.
		if ( self::is_conversational( $built['metas'] ) ) {
			// Only Pro's hook needs to see the form before it renders. Other
			// plugins' redirects (maintenance mode, language, membership) would
			// send the preview frame elsewhere, as the other previews exit
			// before them.
			self::remove_third_party_hooks( [ 'template_redirect' ] );
			add_action(
				'template_redirect',
				static function () {
					self::render_conversational_preview();
				},
				PHP_INT_MAX
			);
			return;
		}

		// Render first, so the blocks enqueue the scripts and styles they need
		// before the head and footer print them.
		$markup = Helper::get_string_value( Generate_Form_Markup::get_form_markup( self::get_preview_post_id(), false ) );
		$width  = Helper::get_integer_value( Create_New_Form::get_default_meta_keys()['_srfm_form_container_width'] ?? 650 );

		self::render_preview_document( $markup, $width );
		exit;
	}

	/**
	 * Leave only the preview's own scripts and styles in the queues: SureForms
	 * and SureForms Pro handles, its spam-protection scripts, the core styles in
	 * PREVIEW_CORE_STYLES and the active theme's styles. Everything else (another plugin's analytics, pixel,
	 * chat widget or cookie banner, the theme's scripts) is dropped. The kept
	 * handles' dependencies (wp-i18n, wp-hooks, ...) still print, since
	 * WordPress resolves them when it prints the queue.
	 *
	 * @since 2.13.0
	 * @return void
	 */
	public static function filter_preview_assets() {
		$queues = [
			'script' => wp_scripts(),
			'style'  => wp_styles(),
		];
		foreach ( $queues as $type => $assets ) {
			$assets->queue = array_values(
				array_filter(
					$assets->queue,
					static function ( $handle ) use ( $assets, $type ) {
						$src = isset( $assets->registered[ $handle ] ) ? $assets->registered[ $handle ]->src : '';
						return self::is_preview_asset( Helper::get_string_value( $handle ), is_string( $src ) ? $src : '', $type );
					}
				)
			);
		}
	}

	/**
	 * Whether the preview page prints a queued script or style.
	 *
	 * @param string $handle Handle.
	 * @param string $src    Source URL, '' for an inline-only handle.
	 * @param string $type   'script' or 'style'.
	 * @since 2.13.0
	 * @return bool
	 */
	public static function is_preview_asset( $handle, $src, $type ) {
		// SureForms and SureForms Pro: srfm-*, sureforms-*, sureforms-pro-*.
		if ( 1 === preg_match( '/^(srfm|sureforms)[-_]/', $handle ) ) {
			return true;
		}
		$own = [ Helper::get_string_value( SRFM_URL ) ];
		if ( defined( 'SRFM_PRO_URL' ) ) {
			$own[] = Helper::get_string_value( constant( 'SRFM_PRO_URL' ) );
		}
		foreach ( $own as $url ) {
			if ( '' !== $src && 0 === strpos( $src, $url ) ) {
				return true;
			}
		}
		if ( 'style' !== $type ) {
			// WordPress core scripts (wp-a11y, wp-i18n, ...): SureForms Pro enqueues
			// some on their own rather than as dependencies, and its field scripts
			// call them (wp.a11y.speak()).
			return self::is_core_asset( $src ) || in_array( $handle, self::PREVIEW_CAPTCHA_SCRIPTS, true );
		}
		if ( in_array( $handle, self::PREVIEW_CORE_STYLES, true ) ) {
			return true;
		}
		// The theme's styles: the form takes its fonts from the theme.
		foreach ( array_unique( [ get_stylesheet(), get_template() ] ) as $theme ) {
			if ( '' !== $theme && 0 === strpos( $handle, $theme . '-' ) ) {
				return true;
			}
		}
		foreach ( array_unique( [ get_stylesheet_directory_uri(), get_template_directory_uri() ] ) as $url ) {
			if ( '' !== $src && 0 === strpos( $src, trailingslashit( $url ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Remove the callbacks other plugins add to the hooks that build the page's
	 * head and footer, so code they print directly (a tracking snippet, a
	 * chat widget) is left out like their queued assets. WordPress core,
	 * SureForms, SureForms Pro and the active theme keep theirs.
	 *
	 * @param array<int, string> $hooks Hooks to clear; the head and footer ones by default.
	 * @since 2.13.0
	 * @return void
	 */
	public static function remove_third_party_hooks( $hooks = self::PREVIEW_ASSET_HOOKS ) {
		global $wp_filter;

		$kept = [ ABSPATH . 'wp-includes', Helper::get_string_value( SRFM_DIR ), get_stylesheet_directory(), get_template_directory() ];
		if ( defined( 'SRFM_PRO_DIR' ) ) {
			$kept[] = Helper::get_string_value( constant( 'SRFM_PRO_DIR' ) );
		}
		$kept = array_map(
			static function ( $dir ) {
				$real = realpath( $dir );
				return trailingslashit( wp_normalize_path( false === $real ? $dir : $real ) );
			},
			$kept
		);

		foreach ( $hooks as $hook ) {
			if ( ! isset( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof \WP_Hook ) {
				continue;
			}
			foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$file = self::callback_file( $callback['function'] );
					if ( '' === $file ) {
						continue; // Unknown origin: keep it.
					}
					$is_kept = false;
					foreach ( $kept as $dir ) {
						if ( 0 === strpos( $file, $dir ) ) {
							$is_kept = true;
							break;
						}
					}
					if ( ! $is_kept ) {
						remove_action( $hook, $callback['function'], $priority );
					}
				}
			}
		}
	}

	/**
	 * Show the Stripe payment field in a template preview even before Stripe is
	 * connected.
	 *
	 * A form leaves out a payment field whose payment method isn't connected, so
	 * a payment template would preview with no payment step at all. The preview
	 * is a picture of the form, so it shows the field with a note where the card
	 * fields will appear; the field takes no payment here (it has no Stripe key,
	 * and submitting is switched off).
	 *
	 * @param mixed $methods Payment methods from `srfm_payment_methods_registry`.
	 * @since 2.13.0
	 * @return mixed
	 */
	public static function preview_payment_methods( $methods ) {
		if ( ! is_array( $methods ) || ! isset( $methods['stripe'] ) || ! is_array( $methods['stripe'] ) || ! empty( $methods['stripe']['enabled'] ) ) {
			return $methods;
		}

		$methods['stripe']['enabled']              = true;
		$methods['stripe']['place_holder_content'] = '<p class="srfm-template-preview__payment-note">' . esc_html__( 'Connect Stripe to take payments. The card fields appear here once it is connected.', 'sureforms' ) . '</p>';
		return $methods;
	}

	/**
	 * POST /form-templates/track
	 *
	 * Counts a step of the template journey in the library: opening it,
	 * previewing a template, or clicking "Upgrade to unlock". A preview of a
	 * template this site can't use is also counted as a locked preview.
	 * Nothing is recorded unless the site shares usage data with SureForms.
	 *
	 * @param WP_REST_Request $request Request.
	 * @since 2.13.0
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_record_action( $request ) {
		$action      = Helper::get_string_value( $request->get_param( 'action' ) );
		$template_id = Helper::get_string_value( $request->get_param( 'template_id' ) );
		$template    = '' !== $template_id ? self::get_template( $template_id ) : null;

		if ( 'library_open' !== $action && ! $template ) {
			return new WP_Error( 'srfm_template_not_found', __( 'Template not found.', 'sureforms' ), [ 'status' => 404 ] );
		}

		if ( ! self::is_usage_sharing_on() ) {
			return new WP_REST_Response( [ 'recorded' => false ] );
		}

		Analytics::track_form_template_action( $action, 'library_open' === $action ? '' : $template_id );
		if ( 'preview' === $action && $template && ! self::is_template_available( $template ) ) {
			Analytics::track_form_template_action( 'locked_preview', $template_id );
		}

		return new WP_REST_Response( [ 'recorded' => true ] );
	}

	/**
	 * Thank-you message in the same layout as the default confirmation. Catalog
	 * files use it through Catalog::thank_you() for conditional messages.
	 *
	 * A blank line in the text starts a new paragraph.
	 *
	 * @param string $heading Heading.
	 * @param string $text    Body text.
	 * @since 2.13.0
	 * @return string
	 */
	public static function confirmation_message( $heading, $text ) {
		// A file URL, not a data: URI: the saved message goes through wp_kses_post(),
		// which strips the data: scheme and would leave a broken image.
		$check_icon = SRFM_URL . 'images/check-icon.svg';

		$message    = '<p style="text-align: center;"><img src="' . esc_url( $check_icon ) . '" alt="" aria-hidden="true" /></p><h2 style="text-align: center;">' . esc_html( $heading ) . '</h2>';
		$paragraphs = preg_split( '/\n\s*\n/', $text );
		foreach ( false === $paragraphs ? [ $text ] : $paragraphs as $paragraph ) {
			$message .= '<p style="text-align: center;">' . esc_html( trim( $paragraph ) ) . '</p>';
		}
		return $message;
	}

	/**
	 * Text color for the Instant Form banner title, which is white. A form made
	 * from a template has a light page color behind the title, so it gets dark
	 * text; other forms keep theirs.
	 *
	 * @param int   $form_id     Form ID.
	 * @param mixed $cover_type  Banner type, `color` or `image`.
	 * @param mixed $cover_color Banner color.
	 * @since 2.13.0
	 * @return string Hex color, or '' to keep the white title.
	 */
	public static function get_banner_title_color( $form_id, $cover_type, $cover_color ) {
		if ( 'image' === $cover_type || ! is_string( $cover_color ) || ! preg_match( '/^#[0-9a-f]{6}$/i', $cover_color ) || self::is_dark( $cover_color ) ) {
			return '';
		}
		return '' !== get_post_meta( $form_id, self::TEMPLATE_ID_META, true ) ? '#1E293B' : '';
	}

	/**
	 * Whether a hex color is dark enough to need light text on it.
	 *
	 * @param string $hex Hex color.
	 * @since 2.13.0
	 * @return bool
	 */
	public static function is_dark( $hex ) {
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
	 * Whether built metas switch the conversational layout on (set by SureForms
	 * Pro's template builder when the template declares `conversational`).
	 *
	 * @param array<string, mixed> $metas Built metas.
	 * @since 2.13.0
	 * @return bool
	 */
	public static function is_conversational( $metas ) {
		$form = Helper::get_array_value( $metas['_srfm_conversational_form'] ?? [] );
		return ! empty( $form['is_cf_enabled'] ) && filter_var( $form['is_cf_enabled'], FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Whether the active SureForms Pro is too old for a template: older than its
	 * `min_pro_version`, or without the template builder its Pro keys need.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since 2.13.0
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
	 * One setup step and whether it is done.
	 *
	 * @param string $id `stripe`, `paypal` or `google_maps`.
	 * @since 2.13.0
	 * @return array{id: string, label: string, done: bool, url: string}|null Null for an unknown id or one this site can't do.
	 */
	private static function resolve_setup_item( $id ) {
		$settings_url = static fn( $tab ) => admin_url( 'admin.php?page=sureforms_form_settings&tab=' . $tab );

		switch ( $id ) {
			case 'stripe':
				return [
					'id'    => $id,
					'label' => __( 'Connect Stripe', 'sureforms' ),
					'done'  => Stripe_Helper::is_stripe_connected(),
					'url'   => Stripe_Helper::get_stripe_settings_url(),
				];

			case 'paypal':
				$connected = [ '\SRFM_Pro\Inc\Business\Payments\PayPal\Helper', 'is_paypal_connected' ];
				if ( ! class_exists( $connected[0] ) || ! is_callable( $connected ) ) {
					return null;
				}
				return [
					'id'    => $id,
					'label' => __( 'Connect PayPal', 'sureforms' ),
					'done'  => (bool) call_user_func( $connected ),
					'url'   => $settings_url( 'payments-settings&subpage=payment-methods&gateway=paypal' ),
				];

			case 'google_maps':
				$maps = Helper::get_array_value( get_option( 'srfm_google_maps_settings', [] ) );
				return [
					'id'    => $id,
					'label' => __( 'Add a Google Maps API key', 'sureforms' ),
					'done'  => '' !== trim( Helper::get_string_value( $maps['srfm_google_maps_api_key'] ?? '' ) ),
					'url'   => $settings_url( 'google-maps-settings' ),
				];
		}
		return null;
	}

	/**
	 * The error for a template that can't be used on this site.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since 2.13.0
	 * @return WP_Error|null Null when the template can be used.
	 */
	private static function locked_error( $template ) {
		$reason = self::lock_reason( $template );
		if ( '' === $reason ) {
			return null;
		}
		$message = 'update' === $reason
			? __( 'Update SureForms Premium to use this template.', 'sureforms' )
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
	 * Example value of one SureForms Pro tag.
	 *
	 * @param string                           $tag      Tag, lowercase, with braces.
	 * @param array<int, array<string, mixed>> $fields   Fields from resolve_fields().
	 * @param array<string, mixed>             $template Template, for its quiz settings.
	 * @since 2.13.0
	 * @return string|null Null for tags without an example (answers, unknown tags).
	 */
	private static function tag_sample( $tag, $fields, $template = [] ) {
		$fixed = array_merge(
			self::quiz_samples( Helper::get_array_value( $template['quiz'] ?? [] ), $fields ),
			[ '{poll_results}' => __( '(live poll results)', 'sureforms' ) ]
		);
		if ( isset( $fixed[ $tag ] ) ) {
			return $fixed[ $tag ];
		}

		if ( '{calculation-result}' === $tag ) {
			foreach ( $fields as $field ) {
				$sample = self::calculated_sample( $field, $fields );
				if ( null !== $sample ) {
					return $sample;
				}
			}
			return self::CALCULATION_FALLBACK;
		}

		if ( ! preg_match( '/^\{form-payment:([a-z0-9_-]+):([a-z-]+)\}$/', $tag, $match ) ) {
			return null;
		}
		$by_property = [
			'order-id'    => 'SF-#1042',
			'status'      => 'succeeded',
			'name'        => 'Jane Smith',
			'email'       => 'jane@example.com',
			'description' => __( 'Order payment', 'sureforms' ),
		];
		if ( 'amount' === $match[2] ) {
			return self::payment_amount_sample( $match[1], $fields );
		}
		return $by_property[ $match[2] ] ?? null;
	}

	/**
	 * Example values of the quiz tags, as SureForms Pro writes them, for a
	 * person who scored 80% of the quiz's most points: `{quiz_score}` is the
	 * score out of the most points, which come from the quiz's questions
	 * (one `correctAnswerScore` per question, or each question's best
	 * `individualScore` in advanced scoring). 8 / 10 when the fields have no
	 * scored question.
	 *
	 * @param array<string, mixed>             $quiz   Template `quiz` settings.
	 * @param array<int, array<string, mixed>> $fields Fields from resolve_fields().
	 * @since 2.13.0
	 * @return array<string, string> Sample by tag.
	 */
	private static function quiz_samples( $quiz, $fields ) {
		$advanced  = 'advanced' === ( $quiz['scoreType'] ?? '' );
		$per_right = is_numeric( $quiz['correctAnswerScore'] ?? null ) ? (float) $quiz['correctAnswerScore'] : 1.0;
		$questions = 0;
		$most      = 0.0;
		foreach ( $fields as $field ) {
			$options = array_filter( Helper::get_array_value( $field['fieldOptions'] ?? [] ), 'is_array' );
			$scores  = array_filter( array_column( $options, 'individualScore' ), 'is_numeric' );
			if ( $advanced && ! empty( $scores ) ) {
				++$questions;
				$most += (float) max( $scores );
			} elseif ( ! $advanced && in_array( true, array_column( $options, 'isCorrect' ), true ) ) {
				++$questions;
				$most += $per_right;
			}
		}
		if ( 0 === $questions || $most <= 0 ) {
			$questions = 10;
			$most      = 10.0 * $per_right;
		}
		$score      = round( $most * 0.8 );
		$percentage = (int) round( $score / $most * 100 );
		$number     = static fn( $value ) => floor( $value ) === $value ? number_format( $value ) : number_format( $value, 2 );

		$pass  = ! is_numeric( $quiz['passPercent'] ?? null ) || $percentage >= (float) $quiz['passPercent'];
		$grade = 'B';
		foreach ( Helper::get_array_value( $quiz['letterGrades'] ?? [] ) as $letter ) {
			// Grades are listed from the highest; the first one reached applies.
			if ( is_array( $letter ) && is_numeric( $letter['percentage'] ?? null ) && $percentage >= (float) $letter['percentage'] ) {
				$grade = Helper::get_string_value( $letter['grade'] ?? $grade );
				break;
			}
		}

		return [
			'{quiz_score}'        => $number( $score ) . ' / ' . $number( $most ),
			'{quiz_percentage}'   => $percentage . '%',
			'{quiz_pass_fail}'    => $pass ? __( 'Pass', 'sureforms' ) : __( 'Fail', 'sureforms' ),
			'{quiz_letter_grade}' => $grade,
			'{quiz_result}'       => $advanced
				/* translators: 1: points scored, 2: most points. */
				? sprintf( __( '%1$s of %2$s points', 'sureforms' ), $number( $score ), $number( $most ) )
				/* translators: 1: right answers, 2: questions. */
				: sprintf( __( '%1$d of %2$d answers correct', 'sureforms' ), (int) round( $questions * 0.8 ), $questions ),
		];
	}

	/**
	 * Example amount a payment field takes, in the currency set in the payment
	 * settings: its fixed amount, or the example value of the field the amount
	 * comes from, else 49.00.
	 *
	 * @param string                           $slug   Payment field slug.
	 * @param array<int, array<string, mixed>> $fields Fields from resolve_fields().
	 * @since 2.13.0
	 * @return string
	 */
	private static function payment_amount_sample( $slug, $fields ) {
		$payment  = self::find_field( $slug, $fields );
		$amount   = 49.0;
		$currency = strtoupper( Payment_Helper::get_currency() );
		$symbol   = Helper::get_string_value( Payment_Helper::get_currency_symbol( $currency ) );
		$symbol   = '' === $symbol ? $currency . ' ' : $symbol;
		if ( null !== $payment ) {
			$type = Helper::get_string_value( $payment['amountType'] ?? 'fixed' );
			if ( 'fixed' === $type && isset( $payment['fixedAmount'] ) && is_numeric( $payment['fixedAmount'] ) ) {
				$amount = (float) $payment['fixedAmount'];
			} elseif ( 'variable' === $type && ! empty( $payment['variableAmountField'] ) ) {
				$amount = self::numeric_sample( Helper::get_string_value( $payment['variableAmountField'] ), $fields ) ?? $amount;
			}
		}
		return $symbol . number_format( $amount, 2 );
	}

	/**
	 * A field by slug.
	 *
	 * @param string                           $slug   Slug.
	 * @param array<int, array<string, mixed>> $fields Fields.
	 * @since 2.13.0
	 * @return array<string, mixed>|null
	 */
	private static function find_field( $slug, $fields ) {
		foreach ( $fields as $field ) {
			if ( ( $field['slug'] ?? '' ) === $slug ) {
				return $field;
			}
		}
		return null;
	}

	/**
	 * A field's calculation formula, '' for a field that isn't calculated.
	 *
	 * @param array<string, mixed> $field Field schema.
	 * @since 2.13.0
	 * @return string
	 */
	private static function get_formula( $field ) {
		$attributes = Helper::get_array_value( $field['blockAttributes'] ?? [] );
		return trim( Helper::get_string_value( $field['calculationFormula'] ?? $attributes['calculationFormula'] ?? '' ) );
	}

	/**
	 * A field's example answer as a number, as the calculator reads it: a
	 * choice's option value, a calculated field's result, or the answer itself.
	 *
	 * @param string                           $slug   Field slug.
	 * @param array<int, array<string, mixed>> $fields Fields.
	 * @param int                              $depth  Calculated fields already being worked out.
	 * @since 2.13.0
	 * @return float|null Null for an unknown field or a non-numeric answer.
	 */
	private static function numeric_sample( $slug, $fields, $depth = 0 ) {
		$field = self::find_field( $slug, $fields );
		if ( null === $field ) {
			return null;
		}
		if ( ! empty( $field['no_sample'] ) ) {
			// Hidden in the example, so the form's calculator counts it as empty.
			return 0.0;
		}
		if ( '' !== self::get_formula( $field ) ) {
			return $depth < 5 ? self::evaluate_formula( self::get_formula( $field ), $fields, $depth + 1 ) : null;
		}

		$type    = Helper::get_string_value( $field['fieldType'] ?? '' );
		$options = Helper::get_array_value( $field['fieldOptions'] ?? [] );
		if ( in_array( $type, [ 'dropdown', 'multi-choice' ], true ) && ! empty( $options[0] ) && is_array( $options[0] ) ) {
			// The first option is the example answer; the calculator uses its value.
			$value = $options[0]['value'] ?? 0;
			return is_numeric( $value ) ? (float) $value : 0.0;
		}
		if ( 'hidden' === $type ) {
			$value = $field['defaultValue'] ?? Helper::get_array_value( $field['blockAttributes'] ?? [] )['defaultValue'] ?? '';
			return is_numeric( $value ) ? (float) $value : 0.0;
		}

		$answer = str_replace( ',', '', self::sample_answer( $field ) );
		return is_numeric( $answer ) ? (float) $answer : null;
	}

	/**
	 * Every template category, in display order.
	 *
	 * @since 2.13.0
	 * @return array<int, array<string, string>>
	 */
	private static function all_categories() {
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
			[
				'id'    => 'calculators',
				'label' => __( 'Calculators', 'sureforms' ),
			],
			[
				'id'    => 'quizzes',
				'label' => __( 'Quizzes & Polls', 'sureforms' ),
			],
			[
				'id'    => 'payments',
				'label' => __( 'Payments', 'sureforms' ),
			],
			[
				'id'    => 'agreements',
				'label' => __( 'Agreements', 'sureforms' ),
			],
			[
				'id'    => 'accounts',
				'label' => __( 'Accounts & Submissions', 'sureforms' ),
			],
		];
	}

	/**
	 * Build a template's block markup and metas together.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param array<string, mixed> $style    Style from sanitize_style().
	 * @param string               $context  `create` or `preview` for all the form's metas,
	 *                                       `swap` for the settings metas and the thank-you message.
	 * @param int                  $form_id  The form the blocks belong to, when known.
	 * @since 2.13.0
	 * @return array{content: string, metas: array<string, mixed>}|WP_Error
	 */
	private static function build( $template, $style, $context, $form_id = 0 ) {
		$content = self::build_content( $template );
		if ( is_wp_error( $content ) ) {
			return $content;
		}
		$content = self::set_form_id( $content, $form_id );

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
	 * Run a template's metas through `srfm_form_template_metas`.
	 *
	 * @param array<string, mixed> $metas    Metas.
	 * @param array<string, mixed> $template Template.
	 * @param string               $content  The form's block markup, or '' to build it when needed.
	 * @param string               $context  `create`, `swap` or `preview`.
	 * @since 2.13.0
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
		 * On `create` the form doesn't exist yet, so `$content`'s blocks still
		 * have `formId: 0`; they get the new form's ID once it is saved.
		 *
		 * @since 2.13.0
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
	 * @since 2.13.0
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
	 * @since 2.13.0
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
			// Without an intro the answers stay, so the owner never gets an empty email.
			if ( ! empty( $template['email_intro'] ) ) {
				$admin['email_body'] = '<p>' . esc_html( Helper::get_string_value( $template['email_intro'] ) ) . '</p>' . ( self::includes_all_data( $template ) ? '{all_data}' : '' );
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
	 * File a hook callback is defined in.
	 *
	 * @param mixed $callback Callback.
	 * @since 2.13.0
	 * @return string Normalized real path, '' when it can't be told.
	 */
	private static function callback_file( $callback ) {
		try {
			if ( is_string( $callback ) && false !== strpos( $callback, '::' ) ) {
				$callback = explode( '::', $callback, 2 );
			}
			if ( is_array( $callback ) && 2 === count( $callback ) && is_string( $callback[1] ) && ( is_object( $callback[0] ) || is_string( $callback[0] ) ) ) {
				$reflection = new \ReflectionMethod( $callback[0], $callback[1] );
			} elseif ( $callback instanceof \Closure || is_string( $callback ) ) {
				$reflection = new \ReflectionFunction( $callback );
			} elseif ( is_object( $callback ) && method_exists( $callback, '__invoke' ) ) {
				$reflection = new \ReflectionMethod( $callback, '__invoke' );
			} else {
				return '';
			}
		} catch ( \ReflectionException $e ) {
			return '';
		}
		$file = $reflection->getFileName();
		if ( ! is_string( $file ) ) {
			return ''; // Internal PHP function.
		}
		$real = realpath( $file );
		return wp_normalize_path( false === $real ? $file : $real );
	}

	/**
	 * An example answer for a field, taken from its placeholder or first option.
	 *
	 * @param array<string, mixed> $field Field schema.
	 * @since 2.13.0
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
		if ( ! empty( $field['no_sample'] ) ) {
			return '';
		}
		$slug = Helper::get_string_value( $field['slug'] ?? '' );
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

		// A number field's example placeholder ("e.g. 1,200") is a better example than 2.
		if ( 'number' === $type && is_numeric( str_replace( ',', '', Helper::get_string_value( $example ) ) ) ) {
			return Helper::get_string_value( $example );
		}
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
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	private static function get_template_theme( $template ) {
		$themes = self::get_themes();
		$key    = Helper::get_string_value( $template['theme'] ?? '' );
		return $themes[ $key ] ?? $themes['ocean'];
	}

	/**
	 * Hex color to RGB.
	 *
	 * @param string $hex #RGB or #RRGGBB.
	 * @since 2.13.0
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
	 * @since 2.13.0
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
	 * Whether the site owner's email lists every answer ({all_data}) below its
	 * intro. On unless the template, or the preview, turns it off; read like
	 * the preview's value, so `0`, `'0'` and `'false'` are off too.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since 2.13.0
	 * @return bool
	 */
	private static function includes_all_data( $template ) {
		$value = $template['email_all_data'] ?? true;
		return is_bool( $value ) || is_int( $value ) || is_string( $value ) ? rest_sanitize_boolean( $value ) : ! empty( $value );
	}

	/**
	 * Body of the confirmation email sent to the person who filled in the form.
	 *
	 * It never repeats the answers ({all_data}): the address it goes to is
	 * typed into the form, so echoing free text would let anyone send their
	 * own text from this site to any address.
	 *
	 * @param array<string, mixed> $autoresponder Template `autoresponder`.
	 * @since 2.13.0
	 * @return string
	 */
	private static function autoresponder_body( $autoresponder ) {
		$greeting = Helper::get_string_value( $autoresponder['greeting'] ?? __( 'Hi {form:full-name},', 'sureforms' ) );
		$body     = '<p>' . esc_html( $greeting ) . '</p>';
		foreach ( Helper::get_array_value( $autoresponder['lines'] ?? [] ) as $line ) {
			$body .= '<p>' . esc_html( Helper::get_string_value( $line ) ) . '</p>';
		}
		$signoff = array_map( 'esc_html', explode( "\n", self::autoresponder_signoff( $autoresponder ) ) );
		return $body . '<p>' . implode( '<br>', $signoff ) . '</p>';
	}

	/**
	 * The sign-off that closes the confirmation email: the template's or the
	 * one edited in the preview, else "Thanks," over the site title.
	 *
	 * @param array<string, mixed> $autoresponder Template `autoresponder`.
	 * @since 2.13.0
	 * @return string One line per line of the sign-off.
	 */
	private static function autoresponder_signoff( $autoresponder ) {
		$signoff = trim( Helper::get_string_value( $autoresponder['signoff'] ?? '' ) );
		return '' === $signoff ? __( 'Thanks,', 'sureforms' ) . "\n{site_title}" : $signoff;
	}

	/**
	 * The Instant Form settings a template sets. Only these keys change when
	 * a template is applied to a form, so its other page settings stay.
	 *
	 * @param array<string, mixed> $theme Theme from resolve_theme().
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	private static function instant_form_style( $theme ) {
		return [
			'single_page_form_title'        => true,
			'cover_type'                    => 'color',
			// The banner color fills the whole page, so the form sits on its
			// page color as a card, like in the library.
			'cover_color'                   => $theme['page'],
			'use_banner_as_page_background' => true,
		];
	}

	/**
	 * The styling values a template sets (see build_style_metas()), so applying
	 * a template in the editor keeps the form's other style choices.
	 *
	 * Padding and corners go with their unit and link keys: the numbers mean
	 * nothing without them. The background type and color go too, so a form
	 * that had a gradient or image doesn't keep it.
	 *
	 * @param array<string, mixed> $styling From build_style_metas().
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	private static function template_owned_styling( $styling ) {
		$owned = [ 'primary_color', 'text_color', 'text_color_on_primary', 'field_spacing', 'submit_button_alignment', 'bg_type', 'bg_color' ];

		return array_filter(
			$styling,
			static fn( $key ) => in_array( $key, $owned, true ) || 1 === preg_match( '/^(instant_)?form_(padding|border_radius)_/', $key ),
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * ID of the in-memory preview form. Each request uses its own ID in a range
	 * real posts don't reach, so previews rendering at the same time never share
	 * cache entries.
	 *
	 * @since 2.13.0
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
	 * @since 2.13.0
	 * @return void
	 */
	private static function seed_preview_form( $template, $content, $metas ) {
		$post = (object) [
			'ID'                => self::get_preview_post_id(),
			'post_author'       => get_current_user_id(),
			'post_date'         => current_time( 'mysql' ),
			'post_date_gmt'     => current_time( 'mysql', true ),
			'post_content'      => $content,
			'post_title'        => self::get_form_title( $template ),
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

		// Any meta write to a post clears its meta cache, which would leave the
		// renderer reading this form's defaults. The preview renders inside
		// `the_content` as the current post, where themes write their own meta
		// for it (Divi 5 caches its canvases), so its metas are served from
		// memory and writes to it are dropped: the form is never saved.
		self::$preview_meta = $meta;
		add_filter( 'get_post_metadata', [ self::class, 'get_preview_meta' ], 10, 3 );
		foreach ( [ 'add_post_metadata', 'update_post_metadata', 'delete_post_metadata' ] as $hook ) {
			add_filter( $hook, [ self::class, 'drop_preview_meta_write' ], 10, 2 );
		}
	}

	/**
	 * Render a conversational template's preview as the form's own page
	 * (templates/single-form.php), whose body SureForms Pro replaces with the
	 * conversational layout. Runs last on template_redirect, after Pro has
	 * seen the in-memory form. Keeps the preview's own body class, styles and
	 * submit blocker, which the library relies on.
	 *
	 * @since 2.13.0
	 * @return void
	 */
	private static function render_conversational_preview() {
		add_filter(
			'body_class',
			static function ( $classes ) {
				$classes[] = 'srfm-template-preview';
				return $classes;
			}
		);
		// A closure: the hook passes its own (empty) argument, which would replace the hook list.
		add_action( 'wp_head', static fn() => self::remove_third_party_hooks(), 0 );
		add_action(
			'wp_head',
			static function () {
				?>
	<meta name="robots" content="noindex, nofollow">
	<style>
		html { margin-top: 0 !important; }
		.srfm-template-preview .srfm-edit-form-btn-wrap { display: none !important; }
		.srfm-template-preview__payment-note { margin: 0; font-size: 14px; line-height: 20px; color: var(--srfm-color-input-description, #6b7280); }
	</style>
				<?php
			},
			99
		);
		add_action( 'wp_footer', static fn() => self::remove_third_party_hooks(), 0 );
		add_action(
			'wp_footer',
			static function () {
				self::print_submit_blocker();
			},
			99
		);

		include SRFM_DIR . 'templates/single-form.php';
		exit;
	}

	/**
	 * Script that stops the preview's form from being submitted: it is a
	 * picture of the form, and the in-memory form cannot take entries.
	 *
	 * @since 2.13.0
	 * @return void
	 */
	private static function print_submit_blocker() {
		?>
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
		<?php
	}

	/**
	 * Print the preview page around the rendered form.
	 *
	 * Submitting is switched off: this is a picture of the form, and the
	 * in-memory form it renders cannot take entries.
	 *
	 * @param string $markup Rendered form markup.
	 * @param int    $width  Form container width in px.
	 * @since 2.13.0
	 * @return void
	 */
	private static function render_preview_document( $markup, $width ) {
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
		<?php
		self::remove_third_party_hooks();
		wp_head();
		?>
	<style>
		html { margin-top: 0 !important; }
		/* See-through, so the form card sits on the page color the library paints behind the frame. */
		html, body.srfm-template-preview { background: transparent !important; }
		body.srfm-template-preview { margin: 0; }
		.srfm-template-preview .srfm-edit-form-btn-wrap { display: none !important; }
		.srfm-template-preview__payment-note { margin: 0; font-size: 14px; line-height: 20px; color: var(--srfm-color-input-description, #6b7280); }
		.srfm-template-preview__wrap { box-sizing: border-box; max-width: <?php echo esc_attr( (string) $width ); ?>px; margin: 0 auto; padding: 40px 24px; }
	</style>
</head>
<body <?php body_class( 'srfm-template-preview' ); ?>>
	<div class="srfm-template-preview__wrap">
		<?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output of the SureForms form renderer, escaped by each block. ?>
	</div>
		<?php
		self::print_submit_blocker();
		self::remove_third_party_hooks();
		wp_footer();
		?>
</body>
</html>
		<?php
	}

	/**
	 * The templates that ship with the plugin, from the catalog files.
	 *
	 * @since 2.13.0
	 * @return array<int, array<string, mixed>>
	 */
	private static function default_templates() {
		return Catalog::templates();
	}

	/**
	 * Whether an asset is served from WordPress core (wp-includes). Core
	 * registers its scripts with site-relative paths, so both forms are checked.
	 *
	 * @param string $src Source URL.
	 * @since 2.13.0
	 * @return bool
	 */
	private static function is_core_asset( $src ) {
		if ( '' === $src ) {
			return false;
		}
		$path = Helper::get_string_value( wp_parse_url( includes_url(), PHP_URL_PATH ) );
		return 0 === strpos( $src, includes_url() ) || ( '' !== $path && 0 === strpos( $src, $path ) );
	}
}
