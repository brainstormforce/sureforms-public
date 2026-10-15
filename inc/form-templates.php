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
					'query' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					// `request` when asked for, `search` for a search with no results.
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
		 * `email_intro`, `autoresponder` ([ 'subject', 'lines', 'greeting', 'summary' ]),
		 * `steps` (step titles for multi-step forms) and `is_pro`.
		 *
		 * A field may carry `pro` (schema keys that replace its own when SureForms Pro
		 * is active), `pro_only` (only added with Pro) and `blockAttributes` (extra
		 * block attributes, such as `minValue` and `maxValue` on a number field).
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
		$list    = [];
		$has_pro = Helper::has_pro();
		foreach ( self::get_templates() as $template ) {
			$resolved = self::resolve_fields( $template, $has_pro );
			$fields   = array_values(
				array_filter(
					array_map(
						static fn( $field ) => ! in_array( $field['fieldType'] ?? '', [ 'gdpr', 'page-break' ], true ) ? Helper::get_string_value( $field['label'] ?? '' ) : '',
						$resolved
					)
				)
			);
			$features = self::get_features( $template, $resolved );

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
				// What the template gains with SureForms Pro, for sites without it.
				'pro_features' => $has_pro ? [] : array_values( array_diff( self::get_features( $template, self::resolve_fields( $template, true ) ), $features ) ),
				'submit_text'  => Helper::get_string_value( $template['submit_text'] ?? '' ),
				'messages'     => self::get_messages( $template, $resolved ),
				'answers'      => self::get_answers( $resolved ),
				'is_pro'       => ! empty( $template['is_pro'] ),
				'is_locked'    => ! empty( $template['is_pro'] ) && ! Helper::has_pro(),
			];
		}
		return $list;
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
				'summary'  => ! isset( $autoresponder['summary'] ) || false !== $autoresponder['summary'],
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
		$theme  = isset( $themes[ $preset ] ) ? $themes[ $preset ] : self::get_template_theme( $template );

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
	 * A template's fields as they will be built: `pro` variants applied and
	 * `pro_only` fields kept only when SureForms Pro is active.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param bool                 $with_pro Whether SureForms Pro is active.
	 * @since x.x.x
	 * @return array<int, array<string, mixed>>
	 */
	public static function resolve_fields( $template, $with_pro ) {
		$fields = [];
		foreach ( Helper::get_array_value( $template['fields'] ?? [] ) as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			if ( ! empty( $field['pro_only'] ) && ! $with_pro ) {
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
			unset( $field['pro'], $field['pro_only'] );
			$fields[] = $field;
		}
		return $fields;
	}

	/**
	 * What a template sets up, for the "Included" chips in the library.
	 *
	 * @param array<string, mixed>             $template Template.
	 * @param array<int, array<string, mixed>> $fields   Fields from resolve_fields().
	 * @since x.x.x
	 * @return array<int, string>
	 */
	public static function get_features( $template, $fields ) {
		$by_type = [
			'page-break'  => 'multi_step',
			'upload'      => 'file_upload',
			'date-picker' => 'date_picker',
			'time-picker' => 'time_picker',
			'rating'      => 'rating',
			'nps'         => 'nps',
			'gdpr'        => 'consent',
		];

		$features = [ 'notification' ];
		if ( ! empty( $template['autoresponder'] ) && self::has_required_email( $fields ) ) {
			$features[] = 'autoresponder';
		}
		foreach ( $fields as $field ) {
			$type = Helper::get_string_value( $field['fieldType'] ?? '' );
			if ( isset( $by_type[ $type ] ) && ! in_array( $by_type[ $type ], $features, true ) ) {
				$features[] = $by_type[ $type ];
			}
		}
		return $features;
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
		$request = new WP_REST_Request( 'POST' );
		$request->set_param(
			'form_data',
			[
				'form' => [
					'formTitle'  => $template['title'] ?? '',
					'formFields' => self::resolve_fields( $template, Helper::has_pro() ),
				],
			]
		);

		add_filter( 'srfm_ai_form_builder_modify_merged_attributes', [ self::class, 'apply_block_attributes' ], 20, 2 );
		$content = Field_Mapping::generate_gutenberg_fields_from_questions( $request );
		remove_filter( 'srfm_ai_form_builder_modify_merged_attributes', [ self::class, 'apply_block_attributes' ], 20 );

		if ( is_wp_error( $content ) ) {
			return $content;
		}

		return Helper::get_string_value( $content );
	}

	/**
	 * Post metas for a form created from a template.
	 *
	 * Starts from the registered defaults, like a hand-created form, then applies
	 * the template's thank-you message and notification subject.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param array<string, mixed> $style    Optional style from sanitize_style().
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	public static function build_metas( $template, $style = [] ) {
		$metas      = Create_New_Form::get_default_meta_keys();
		$registered = get_registered_meta_keys( 'post', SRFM_FORMS_POST_TYPE );

		$confirmation = $registered['_srfm_form_confirmation']['default'] ?? [];
		if ( ! empty( $template['confirmation'] ) && is_array( $template['confirmation'] ) && isset( $confirmation[0] ) && is_array( $confirmation[0] ) ) {
			$confirmation[0]['message'] = self::confirmation_message(
				Helper::get_string_value( $template['confirmation']['heading'] ?? '' ),
				Helper::get_string_value( $template['confirmation']['text'] ?? '' )
			);
		}

		$metas['_srfm_form_confirmation'] = $confirmation;
		$metas                            = array_merge( $metas, self::build_settings_metas( $template ), self::build_style_metas( $template, $style ) );

		$metas['_srfm_compliance']       = $registered['_srfm_compliance']['default'] ?? [];
		$metas[ self::CREATED_VIA_META ] = 'template';
		$metas[ self::TEMPLATE_ID_META ] = $template['id'];

		return $metas;
	}

	/**
	 * The behaviour metas a template sets: button text, emails and steps.
	 * Used when creating a form and when a template is applied in the editor.
	 *
	 * @param array<string, mixed> $template Template.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	public static function build_settings_metas( $template ) {
		$registered = get_registered_meta_keys( 'post', SRFM_FORMS_POST_TYPE );
		$fields     = self::resolve_fields( $template, Helper::has_pro() );
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

			if ( $has_email && ! empty( $template['autoresponder'] ) && is_array( $template['autoresponder'] ) ) {
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
	 * Body of the confirmation email sent to the person who filled in the form.
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
		if ( ! isset( $autoresponder['summary'] ) || false !== $autoresponder['summary'] ) {
			$body .= '<p>' . esc_html__( 'Here is a copy of what you sent us:', 'sureforms' ) . '</p>{all_data}';
		}
		return $body . '<p>' . esc_html__( 'Thanks,', 'sureforms' ) . '<br>{site_title}</p>';
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

		if ( ! empty( $template['is_pro'] ) && ! Helper::has_pro() ) {
			return new WP_Error( 'srfm_template_locked', __( 'This template needs SureForms Pro.', 'sureforms' ), [ 'status' => 403 ] );
		}

		$template = self::apply_messages( $template, self::sanitize_messages( $messages ) );
		$style    = self::sanitize_style( $style );
		$content  = self::build_content( $template );
		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$post_id = wp_insert_post(
			[
				'post_title'   => Helper::get_string_value( $template['title'] ?? '' ),
				'post_content' => $content,
				'post_status'  => 'draft',
				'post_type'    => SRFM_FORMS_POST_TYPE,
				'meta_input'   => self::build_metas( $template, $style ),
			],
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
		$post_id = self::create_form_from_template(
			Helper::get_string_value( $request->get_param( 'template_id' ) ),
			self::sanitize_style( $request->get_param( 'style' ) ),
			Helper::get_array_value( $request->get_param( 'messages' ) )
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( rest_sanitize_boolean( $request->get_param( 'enable_spam_protection' ) ) && ! self::is_spam_protection_on() ) {
			self::enable_spam_protection();
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

		if ( ! empty( $template['is_pro'] ) && ! Helper::has_pro() ) {
			return new WP_Error( 'srfm_template_locked', __( 'This template needs SureForms Pro.', 'sureforms' ), [ 'status' => 403 ] );
		}

		$style   = self::sanitize_style( $request->get_param( 'style' ) );
		$content = self::build_content( $template );
		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$style_metas    = self::build_style_metas( $template, $style );
		$settings_metas = self::build_settings_metas( $template );
		$confirmation   = self::build_metas( $template, $style )['_srfm_form_confirmation'];
		$form_id        = Helper::get_integer_value( $request->get_param( 'form_id' ) );
		$has_entries    = $form_id > 0 && Entries::get_total_entries_by_status( 'all', $form_id ) > 0;

		return new WP_REST_Response(
			[
				'id'           => $template['id'],
				'title'        => Helper::get_string_value( $template['title'] ?? '' ),
				'content'      => $content,
				'has_entries'  => $has_entries,
				// Applied over the open form's own settings in the editor.
				'styling'      => self::changed_keys( $style_metas['_srfm_forms_styling'], Helper::get_array_value( get_registered_meta_keys( 'post', SRFM_FORMS_POST_TYPE )['_srfm_forms_styling']['default'] ?? [] ) ),
				'instant_form' => self::instant_form_style( self::resolve_theme( $template, $style ) ),
				// Button text, emails, thank-you message and steps the template sets.
				// The editor only applies the get_message_meta_keys() ones when the
				// user chooses to replace the form's messages too.
				'settings'     => array_merge( $settings_metas, [ '_srfm_form_confirmation' => $confirmation ] ),
			]
		);
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
	 * POST /form-templates/request
	 *
	 * Records a template someone asked for, or searched for without a match.
	 * Nothing is recorded unless the site shares usage data with SureForms.
	 *
	 * @param WP_REST_Request $request Request.
	 * @since x.x.x
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_record_demand( $request ) {
		$query = self::clean_demand_query( $request->get_param( 'query' ) );
		if ( '' === $query ) {
			return new WP_Error( 'srfm_template_request_invalid', __( 'Please describe the template in a few words.', 'sureforms' ), [ 'status' => 400 ] );
		}

		if ( ! self::is_usage_sharing_on() ) {
			return new WP_REST_Response( [ 'recorded' => false ] );
		}

		Analytics::track_form_template_demand( $query, Helper::get_string_value( $request->get_param( 'type' ) ) );

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
		$style   = self::sanitize_style( isset( $_GET['srfm_style'] ) ? sanitize_text_field( wp_unslash( $_GET['srfm_style'] ) ) : '' );
		$content = self::build_content( $template );
		if ( is_wp_error( $content ) ) {
			wp_die( esc_html( $content->get_error_message() ), '', [ 'response' => 500 ] );
		}

		self::seed_preview_form( $template, $content, $style );

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
	 * Put an in-memory form in the object cache, so the renderer can read it
	 * like a saved form for the rest of this request. It is removed again at
	 * shutdown, so nothing is kept in a persistent object cache.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param string               $content  Block markup.
	 * @param array<string, mixed> $style    Style from sanitize_style().
	 * @since x.x.x
	 * @return void
	 */
	private static function seed_preview_form( $template, $content, $style = [] ) {
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
		foreach ( self::build_metas( $template, $style ) as $key => $value ) {
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
	 * Field helper: one field in the AI builder schema.
	 *
	 * @param string               $type     Field type (block name without `srfm/`).
	 * @param string               $slug     Field slug.
	 * @param string               $label    Label.
	 * @param bool                 $required Required.
	 * @param array<string, mixed> $extra    Extra schema keys (placeholder, helpText, fieldOptions, ...).
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	private static function field( $type, $slug, $label, $required = false, $extra = [] ) {
		return array_merge(
			[
				'fieldType' => $type,
				'slug'      => $slug,
				'label'     => $label,
				'required'  => $required,
			],
			$extra
		);
	}

	/**
	 * Options for a dropdown field.
	 *
	 * @param array<int, string> $labels Option labels.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	private static function dropdown_options( $labels ) {
		return [ 'fieldOptions' => array_map( static fn( $label ) => [ 'label' => $label ], $labels ) ];
	}

	/**
	 * Options for a multiple-choice field.
	 *
	 * @param array<int, string> $labels Option labels.
	 * @param bool               $single Single selection.
	 * @param array<int, string> $icons  Optional icon per option (Font Awesome names). With
	 *                                   an icon on every option the choices show as cards.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	private static function choice_options( $labels, $single = true, $icons = [] ) {
		$options = [];
		foreach ( $labels as $index => $label ) {
			$options[] = [
				'optionTitle' => $label,
				'icon'        => $icons[ $index ] ?? '',
			];
		}
		return [
			'fieldOptions'    => $options,
			'singleSelection' => $single,
		];
	}

	/**
	 * Sets how much of the row a field takes.
	 *
	 * @param array<string, mixed> $field Field schema.
	 * @param float|int            $width 100, 75, 66.66, 50, 33.33 or 25, as in the editor.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	private static function width( $field, $width ) {
		return self::attrs( $field, [ 'fieldWidth' => $width ] );
	}

	/**
	 * Adds block attributes to a field, keeping the ones it already has.
	 *
	 * @param array<string, mixed> $field      Field schema.
	 * @param array<string, mixed> $attributes Block attributes.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	private static function attrs( $field, $attributes ) {
		$field['blockAttributes'] = array_merge( Helper::get_array_value( $field['blockAttributes'] ?? [] ), $attributes );
		return $field;
	}

	/**
	 * Name, email and consent fields most templates share.
	 *
	 * @since x.x.x
	 * @return array<string, array<string, mixed>>
	 */
	private static function common_fields() {
		return [
			'name'       => self::field( 'input', 'full-name', __( 'Full Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ),
			'first_name' => self::width( self::field( 'input', 'first-name', __( 'First Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane', 'sureforms' ) ] ), 50 ),
			'last_name'  => self::width( self::field( 'input', 'last-name', __( 'Last Name', 'sureforms' ), true, [ 'placeholder' => __( 'Smith', 'sureforms' ) ] ), 50 ),
			'email'      => self::field( 'email', 'email', __( 'Email', 'sureforms' ), true, [ 'placeholder' => __( 'jane@example.com', 'sureforms' ) ] ),
			'phone'      => self::field( 'phone', 'phone', __( 'Phone', 'sureforms' ), false, [ 'blockAttributes' => [ 'defaultCountry' => self::site_country() ] ] ),
			'consent'    => self::field( 'gdpr', 'consent', __( 'I agree to have this website store my submitted information so it can respond to my request.', 'sureforms' ), true ),
		];
	}

	/**
	 * The site's country from its language (en_GB gives gb), used when the
	 * phone field cannot detect the visitor's country.
	 *
	 * @since x.x.x
	 * @return string Two-letter country code, or empty when the language has none.
	 */
	private static function site_country() {
		$parts = explode( '_', get_locale() );
		return isset( $parts[1] ) && 2 === strlen( $parts[1] ) ? strtolower( $parts[1] ) : '';
	}

	/**
	 * A date question: a date picker with SureForms Pro, a text field without it.
	 *
	 * @param string $slug     Field slug.
	 * @param string $label    Label.
	 * @param bool   $required Required.
	 * @param bool   $future   Only dates from today on (bookings), not past ones.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	private static function date_field( $slug, $label, $required = false, $future = false ) {
		return self::field(
			'input',
			$slug,
			$label,
			$required,
			[
				'placeholder' => __( 'e.g. 14 October', 'sureforms' ),
				'pro'         => [
					'fieldType'       => 'date-picker',
					'placeholder'     => '',
					'dateFormat'      => 'mm/dd/yyyy',
					'blockAttributes' => [ 'disablePastDates' => $future ],
				],
			]
		);
	}

	/**
	 * A step divider for multi-step forms. Only added with SureForms Pro, which
	 * renders the form as steps; free forms stay on one page.
	 *
	 * @param string $slug  Field slug.
	 * @param string $label Title of the step that starts here.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	private static function step( $slug, $label ) {
		return self::field( 'page-break', $slug, $label, false, [ 'pro_only' => true ] );
	}

	/**
	 * A number field limited to a range.
	 *
	 * @param string               $slug     Field slug.
	 * @param string               $label    Label.
	 * @param bool                 $required Required.
	 * @param int                  $min      Lowest value.
	 * @param int                  $max      Highest value.
	 * @param array<string, mixed> $extra    Extra schema keys.
	 * @since x.x.x
	 * @return array<string, mixed>
	 */
	private static function number_field( $slug, $label, $required, $min, $max, $extra = [] ) {
		return self::attrs(
			self::field( 'number', $slug, $label, $required, $extra ),
			[
				'minValue' => $min,
				'maxValue' => $max,
			]
		);
	}

	/**
	 * The templates that ship with the plugin.
	 *
	 * Every template works with the free plugin. Fields marked `pro` switch to a
	 * richer SureForms Pro field (date picker, file upload, rating, NPS) when Pro
	 * is active, and `pro_only` fields (step dividers, ratings) are only added then.
	 *
	 * Each template also sets the button text, the email to the site owner, an
	 * optional confirmation email to the person who filled it in, and the
	 * thank-you message, so a form is ready to publish as soon as it is created.
	 *
	 * @since x.x.x
	 * @return array<int, array<string, mixed>>
	 */
	private static function default_templates() {
		$c = self::common_fields();

		$satisfaction = [ __( 'Very satisfied', 'sureforms' ), __( 'Satisfied', 'sureforms' ), __( 'Neutral', 'sureforms' ), __( 'Dissatisfied', 'sureforms' ), __( 'Very dissatisfied', 'sureforms' ) ];
		$nps_scale    = array_map( 'strval', range( 0, 10 ) );
		$services     = [ __( 'Consultation', 'sureforms' ), __( 'Installation', 'sureforms' ), __( 'Maintenance', 'sureforms' ), __( 'Repair', 'sureforms' ), __( 'Something else', 'sureforms' ) ];
		$no_search    = [ 'searchable' => false ];
		$screenshots  = [
			'pro' => [
				'fieldType'        => 'upload',
				'label'            => __( 'Screenshots (optional)', 'sureforms' ),
				'placeholder'      => '',
				'helpText'         => __( 'Add up to 3 images or PDFs.', 'sureforms' ),
				'allowedTypes'     => 'jpg,jpeg,png,gif,webp,pdf',
				'multiUpload'      => true,
				'multiFilesNumber' => 3,
			],
		];

		return [
			// Contact & leads.
			[
				'id'            => 'contact-us',
				'category'      => 'contact',
				'theme'         => 'ocean',
				'icon'          => 'mail',
				'title'         => __( 'Contact Us', 'sureforms' ),
				'description'   => __( 'A simple form for visitors to send you a message.', 'sureforms' ),
				'submit_text'   => __( 'Send message', 'sureforms' ),
				'fields'        => [
					$c['first_name'],
					$c['last_name'],
					self::width( $c['email'], 50 ),
					self::width( $c['phone'], 50 ),
					self::field( 'input', 'subject', __( 'Subject', 'sureforms' ), false, [ 'placeholder' => __( 'What is this about?', 'sureforms' ) ] ),
					self::attrs( self::field( 'textarea', 'message', __( 'Message', 'sureforms' ), true, [ 'placeholder' => __( 'How can we help?', 'sureforms' ) ] ), [ 'rows' => 5 ] ),
					$c['consent'],
				],
				'confirmation'  => [
					'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
					'text'    => __( 'Your message is with us. We will reply to {form:email} as soon as we can.', 'sureforms' ),
				],
				'email_subject' => __( 'New message from {form:first-name} {form:last-name}', 'sureforms' ),
				'email_intro'   => __( '{form:first-name} {form:last-name} sent a message through your contact form. Reply to this email to answer them directly.', 'sureforms' ),
				'autoresponder' => [
					'subject'  => __( 'Thanks for contacting {site_title}', 'sureforms' ),
					'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
					'lines'    => [ __( 'Thanks for reaching out. Your message has reached us and we will get back to you as soon as we can.', 'sureforms' ) ],
				],
			],
			[
				'id'            => 'quote-request',
				'category'      => 'contact',
				'theme'         => 'midnight',
				'icon'          => 'receipt',
				'title'         => __( 'Quote Request', 'sureforms' ),
				'description'   => __( 'Collect project details so you can send an estimate.', 'sureforms' ),
				'submit_text'   => __( 'Request my quote', 'sureforms' ),
				'steps'         => [ __( 'Your details', 'sureforms' ), __( 'Your project', 'sureforms' ) ],
				'fields'        => [
					$c['first_name'],
					$c['last_name'],
					self::width( $c['email'], 50 ),
					self::width( $c['phone'], 50 ),
					self::field( 'input', 'company', __( 'Company', 'sureforms' ), false, [ 'placeholder' => __( 'If you are asking for a business', 'sureforms' ) ] ),
					self::step( 'project-step', __( 'Your project', 'sureforms' ) ),
					self::attrs( self::field( 'dropdown', 'service', __( 'Service Needed', 'sureforms' ), true, array_merge( self::dropdown_options( $services ), [ 'placeholder' => __( 'Select a service', 'sureforms' ) ] ) ), $no_search ),
					self::width( self::attrs( self::field( 'dropdown', 'budget', __( 'Estimated Budget', 'sureforms' ), false, array_merge( self::dropdown_options( [ __( 'Under 1,000', 'sureforms' ), __( '1,000 – 5,000', 'sureforms' ), __( '5,000 – 20,000', 'sureforms' ), __( 'Over 20,000', 'sureforms' ), __( 'Not sure yet', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select a range', 'sureforms' ) ] ) ), $no_search ), 50 ),
					self::width( self::attrs( self::field( 'dropdown', 'timeline', __( 'Timeline', 'sureforms' ), false, array_merge( self::dropdown_options( [ __( 'As soon as possible', 'sureforms' ), __( 'Within a month', 'sureforms' ), __( '1–3 months', 'sureforms' ), __( 'More than 3 months', 'sureforms' ), __( 'Flexible', 'sureforms' ) ] ), [ 'placeholder' => __( 'When do you need it?', 'sureforms' ) ] ) ), $no_search ), 50 ),
					self::attrs(
						self::field(
							'textarea',
							'project-details',
							__( 'Project Details', 'sureforms' ),
							true,
							[
								'placeholder' => __( 'What do you need, how big is the job, and is there anything we should know?', 'sureforms' ),
								'helpText'    => __( 'The more detail you share, the more accurate your quote will be.', 'sureforms' ),
							]
						),
						[ 'rows' => 5 ]
					),
					self::field(
						'upload',
						'attachments',
						__( 'Attachments', 'sureforms' ),
						false,
						[
							'pro_only'         => true,
							'helpText'         => __( 'Plans, photos or specs help us quote accurately. Up to 5 files.', 'sureforms' ),
							'allowedTypes'     => 'jpg,jpeg,png,pdf,doc,docx',
							'uploadSize'       => 10,
							'multiUpload'      => true,
							'multiFilesNumber' => 5,
						]
					),
					$c['consent'],
				],
				'confirmation'  => [
					'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
					'text'    => __( 'Your quote request is in. We will review your project details and send your estimate to {form:email}.', 'sureforms' ),
				],
				'email_subject' => __( 'Quote request: {form:service} from {form:first-name} {form:last-name}', 'sureforms' ),
				'email_intro'   => __( '{form:first-name} {form:last-name} asked for a quote for {form:service}. Reply to this email to send your estimate.', 'sureforms' ),
				'autoresponder' => [
					'subject'  => __( 'We received your quote request', 'sureforms' ),
					'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
					'lines'    => [ __( 'Thanks for telling us about your project. We will review the details and get back to you with an estimate.', 'sureforms' ) ],
				],
			],
			[
				'id'            => 'callback-request',
				'category'      => 'contact',
				'theme'         => 'mint',
				'icon'          => 'phone-call',
				'title'         => __( 'Callback Request', 'sureforms' ),
				'description'   => __( 'Let visitors ask for a phone call at a time that suits them.', 'sureforms' ),
				'submit_text'   => __( 'Request a call', 'sureforms' ),
				'fields'        => [
					self::width( $c['name'], 50 ),
					self::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
					self::attrs(
						self::field(
							'multi-choice',
							'best-time',
							__( 'Best time to call', 'sureforms' ),
							true,
							array_merge(
								self::choice_options( [ __( 'Morning', 'sureforms' ), __( 'Afternoon', 'sureforms' ), __( 'Evening', 'sureforms' ), __( 'Any time', 'sureforms' ) ], true, [ 'sun', 'cloud-sun', 'moon', 'clock' ] ),
								[ 'helpText' => __( 'In your local time.', 'sureforms' ) ]
							)
						),
						[ 'choiceWidth' => 25 ]
					),
					self::attrs( self::field( 'textarea', 'topic', __( 'What would you like to talk about?', 'sureforms' ), false, [ 'placeholder' => __( 'A quick note helps us prepare for the call.', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
					$c['consent'],
				],
				'confirmation'  => [
					'heading' => __( 'Thanks, we will call you back', 'sureforms' ),
					'text'    => __( 'We will call you on {form:phone}. Best time noted: {form:best-time}.', 'sureforms' ),
				],
				'email_subject' => __( 'Call {form:full-name} back ({form:best-time})', 'sureforms' ),
				'email_intro'   => __( '{form:full-name} would like a call on {form:phone}. Best time: {form:best-time}.', 'sureforms' ),
			],
			[
				'id'            => 'consultation-request',
				'category'      => 'contact',
				'theme'         => 'lavender',
				'icon'          => 'messages-square',
				'title'         => __( 'Consultation Request', 'sureforms' ),
				'description'   => __( 'Qualify leads who want a free consultation.', 'sureforms' ),
				'submit_text'   => __( 'Request a consultation', 'sureforms' ),
				'fields'        => [
					$c['first_name'],
					$c['last_name'],
					self::width( $c['email'], 50 ),
					self::width( $c['phone'], 50 ),
					self::width( self::field( 'input', 'company', __( 'Company', 'sureforms' ) ), 50 ),
					self::width( self::field( 'url', 'website', __( 'Website', 'sureforms' ), false, [ 'placeholder' => 'https://' ] ), 50 ),
					self::attrs(
						self::field(
							'textarea',
							'goals',
							__( 'What are your goals?', 'sureforms' ),
							true,
							[
								'placeholder' => __( 'What would you like to achieve, and what is getting in the way?', 'sureforms' ),
								'helpText'    => __( 'A few sentences is plenty.', 'sureforms' ),
							]
						),
						[ 'rows' => 4 ]
					),
					self::field( 'multi-choice', 'meeting-type', __( 'How would you like to meet?', 'sureforms' ), false, self::choice_options( [ __( 'Video call', 'sureforms' ), __( 'Phone call', 'sureforms' ), __( 'In person', 'sureforms' ) ], true, [ 'video', 'phone', 'handshake' ] ) ),
					self::date_field( 'preferred-date', __( 'Preferred Date', 'sureforms' ), false, true ) + [ 'pro_only' => true ],
					self::attrs( self::field( 'dropdown', 'how-heard', __( 'How did you hear about us?', 'sureforms' ), false, array_merge( self::dropdown_options( [ __( 'Search engine', 'sureforms' ), __( 'Social media', 'sureforms' ), __( 'Referral', 'sureforms' ), __( 'Advertisement', 'sureforms' ), __( 'Other', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select an option', 'sureforms' ) ] ) ), $no_search ),
					$c['consent'],
				],
				'confirmation'  => [
					'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
					'text'    => __( 'We have your request and will email {form:email} to arrange a time for your consultation.', 'sureforms' ),
				],
				'email_subject' => __( 'Consultation request from {form:first-name} {form:last-name}', 'sureforms' ),
				'email_intro'   => __( '{form:first-name} {form:last-name} would like a consultation. Reply to this email to arrange a time.', 'sureforms' ),
				'autoresponder' => [
					'subject'  => __( 'Your consultation request with {site_title}', 'sureforms' ),
					'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
					'lines'    => [ __( 'Thanks for your interest. We will review your goals and get in touch to find a time that works for you.', 'sureforms' ) ],
				],
			],
			[
				'id'            => 'product-inquiry',
				'category'      => 'contact',
				'theme'         => 'sand',
				'icon'          => 'package',
				'title'         => __( 'Product Inquiry', 'sureforms' ),
				'description'   => __( 'Answer questions about a product before someone buys.', 'sureforms' ),
				'submit_text'   => __( 'Send my question', 'sureforms' ),
				'fields'        => [
					self::width( $c['name'], 50 ),
					self::width( $c['email'], 50 ),
					self::width( self::field( 'input', 'product', __( 'Product', 'sureforms' ), true, [ 'placeholder' => __( 'Product name or code', 'sureforms' ) ] ), 66.66 ),
					self::width( self::number_field( 'quantity', __( 'Quantity', 'sureforms' ), false, 1, 10000, [ 'placeholder' => '1' ] ), 33.33 ),
					self::attrs( self::field( 'textarea', 'question', __( 'Your Question', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Is it in stock? Does it come in other sizes? Do you offer bulk pricing?', 'sureforms' ) ] ), [ 'rows' => 4 ] ),
					$c['consent'],
				],
				'confirmation'  => [
					'heading' => __( 'Thanks for your question', 'sureforms' ),
					'text'    => __( 'We will reply to {form:email} with an answer about {form:product} as soon as we can.', 'sureforms' ),
				],
				'email_subject' => __( 'Product question: {form:product} from {form:full-name}', 'sureforms' ),
				'email_intro'   => __( '{form:full-name} has a question about {form:product}. Reply to this email to answer them directly.', 'sureforms' ),
				'autoresponder' => [
					'subject' => __( 'We got your question about {form:product}', 'sureforms' ),
					'lines'   => [ __( 'Thanks for your interest in {form:product}. We have received your question and will get back to you with an answer soon.', 'sureforms' ) ],
				],
			],
			[
				'id'            => 'partnership-inquiry',
				'category'      => 'contact',
				'theme'         => 'midnight',
				'icon'          => 'handshake',
				'title'         => __( 'Partnership Inquiry', 'sureforms' ),
				'description'   => __( 'Hear from businesses that want to work with you.', 'sureforms' ),
				'submit_text'   => __( 'Send proposal', 'sureforms' ),
				'fields'        => [
					self::width( $c['name'], 50 ),
					self::width(
						array_merge(
							$c['email'],
							[
								'label'       => __( 'Work Email', 'sureforms' ),
								'placeholder' => __( 'jane@company.com', 'sureforms' ),
							]
						),
						50
					),
					self::width( self::field( 'input', 'company', __( 'Company', 'sureforms' ), true ), 50 ),
					self::width( self::field( 'input', 'job-title', __( 'Your Role', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Head of Partnerships', 'sureforms' ) ] ), 50 ),
					self::width( self::field( 'url', 'website', __( 'Company Website', 'sureforms' ), false, [ 'placeholder' => 'https://' ] ), 50 ),
					self::width( self::attrs( self::field( 'dropdown', 'partnership-type', __( 'Type of Partnership', 'sureforms' ), true, array_merge( self::dropdown_options( [ __( 'Reseller or distributor', 'sureforms' ), __( 'Affiliate or referral', 'sureforms' ), __( 'Technology integration', 'sureforms' ), __( 'Co-marketing', 'sureforms' ), __( 'Sponsorship', 'sureforms' ), __( 'Something else', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select a type', 'sureforms' ) ] ) ), $no_search ), 50 ),
					self::field(
						'textarea',
						'proposal',
						__( 'Tell us about your proposal', 'sureforms' ),
						true,
						[
							'placeholder' => __( 'What do you have in mind, and how would it benefit both sides?', 'sureforms' ),
							'helpText'    => __( 'Include your audience or customer base if it is relevant.', 'sureforms' ),
						]
					),
					self::field(
						'upload',
						'media-kit',
						__( 'Pitch Deck or Media Kit', 'sureforms' ),
						false,
						[
							'pro_only'     => true,
							'allowedTypes' => 'pdf,ppt,pptx,key',
							'uploadSize'   => 10,
						]
					),
					$c['consent'],
				],
				'confirmation'  => [
					'heading' => __( 'Thanks for your interest', 'sureforms' ),
					'text'    => __( 'We have received the proposal from {form:company}. We review every one and will be in touch if it is a good fit.', 'sureforms' ),
				],
				'email_subject' => __( 'Partnership inquiry: {form:partnership-type} from {form:company}', 'sureforms' ),
				'email_intro'   => __( '{form:full-name} from {form:company} sent a partnership proposal ({form:partnership-type}). Reply to this email to continue the conversation.', 'sureforms' ),
				'autoresponder' => [
					'subject' => __( 'Thanks for your partnership proposal', 'sureforms' ),
					'lines'   => [ __( 'Thanks for thinking of us. We have received the proposal from {form:company} and will get back to you if it is a good fit.', 'sureforms' ) ],
				],
			],

			// Bookings & events.
			[
				'id'            => 'appointment-request',
				'category'      => 'booking',
				'theme'         => 'lavender',
				'icon'          => 'calendar-clock',
				'title'         => __( 'Appointment Request', 'sureforms' ),
				'description'   => __( 'Let clients request an appointment and preferred time.', 'sureforms' ),
				'submit_text'   => __( 'Request appointment', 'sureforms' ),
				'fields'        => [
					self::width( $c['name'], 50 ),
					self::width( $c['email'], 50 ),
					self::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
					self::width( self::attrs( self::field( 'dropdown', 'service', __( 'Service', 'sureforms' ), true, array_merge( self::dropdown_options( [ __( 'First visit', 'sureforms' ), __( 'Follow-up visit', 'sureforms' ), __( 'Consultation', 'sureforms' ), __( 'Something else', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select a service', 'sureforms' ) ] ) ), $no_search ), 50 ),
					self::date_field( 'preferred-date', __( 'Preferred Date', 'sureforms' ), true, true ),
					self::attrs( self::field( 'multi-choice', 'preferred-time', __( 'Preferred Time', 'sureforms' ), true, self::choice_options( [ __( 'Morning', 'sureforms' ), __( 'Afternoon', 'sureforms' ), __( 'Evening', 'sureforms' ) ], true, [ 'sun', 'cloud-sun', 'moon' ] ) ), [ 'choiceWidth' => 33.33 ] ),
					self::attrs( self::field( 'textarea', 'notes', __( 'Anything we should know?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. the reason for your visit, access needs, or other times that work', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
					$c['consent'],
				],
				'confirmation'  => [
					'heading' => __( 'Appointment requested', 'sureforms' ),
					'text'    => __( 'We have noted {form:preferred-date} ({form:preferred-time}). This is not confirmed yet: we will check availability and confirm by email or phone.', 'sureforms' ),
				],
				'email_subject' => __( 'Appointment request from {form:full-name}: {form:preferred-date}, {form:preferred-time}', 'sureforms' ),
				'email_intro'   => __( '{form:full-name} would like an appointment ({form:service}) on {form:preferred-date}, {form:preferred-time}. Reply to this email to confirm, or call them on {form:phone}.', 'sureforms' ),
				'autoresponder' => [
					'subject' => __( 'We received your appointment request', 'sureforms' ),
					'lines'   => [
						__( 'Thanks for your request. We have noted {form:service} on {form:preferred-date} ({form:preferred-time}).', 'sureforms' ),
						__( 'This is not a confirmation yet: we will check availability and get back to you to confirm.', 'sureforms' ),
					],
				],
			],
			[
				'id'            => 'event-registration',
				'category'      => 'booking',
				'theme'         => 'sunset',
				'icon'          => 'ticket',
				'title'         => __( 'Event Registration', 'sureforms' ),
				'description'   => __( 'Register attendees for a workshop, webinar or meetup.', 'sureforms' ),
				'submit_text'   => __( 'Register now', 'sureforms' ),
				'fields'        => [
					self::width( $c['name'], 50 ),
					self::width( $c['email'], 50 ),
					self::width( $c['phone'], 33.33 ),
					self::width( self::field( 'input', 'organization', __( 'Organization', 'sureforms' ) ), 33.33 ),
					self::width(
						self::number_field(
							'attendees',
							__( 'Attendees', 'sureforms' ),
							true,
							1,
							20,
							[
								'placeholder' => __( 'Including yourself', 'sureforms' ),
							]
						),
						33.33
					),
					self::field( 'multi-choice', 'dietary', __( 'Dietary Requirements', 'sureforms' ), false, array_merge( self::choice_options( [ __( 'Vegetarian', 'sureforms' ), __( 'Vegan', 'sureforms' ), __( 'Gluten-free', 'sureforms' ), __( 'Dairy-free', 'sureforms' ), __( 'Halal', 'sureforms' ), __( 'Other', 'sureforms' ) ], false ), [ 'helpText' => __( 'For in-person events. Leave blank if none.', 'sureforms' ) ] ) ),
					self::attrs( self::field( 'textarea', 'questions', __( 'Anything else we should know?', 'sureforms' ), false, [ 'placeholder' => __( 'Accessibility needs, dietary details or questions for the organizers', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
					self::field( 'checkbox', 'event-updates', __( 'Email me about future events', 'sureforms' ) ),
					$c['consent'],
				],
				'confirmation'  => [
					'heading' => __( 'You are registered', 'sureforms' ),
					'text'    => __( 'Thanks for registering. A confirmation is on its way to {form:email}.', 'sureforms' ),
				],
				'email_subject' => __( 'New registration: {form:full-name} ({form:attendees} attending)', 'sureforms' ),
				'email_intro'   => __( '{form:full-name} has registered for your event. Places requested: {form:attendees}. Reply to this email to reach them directly.', 'sureforms' ),
				'autoresponder' => [
					'subject' => __( 'You are registered', 'sureforms' ),
					'lines'   => [
						__( 'Thanks for registering. Places requested: {form:attendees}.', 'sureforms' ),
						__( 'We will send you the event details before the day. If your plans change, just reply to this email.', 'sureforms' ),
					],
				],
			],
			[
				'id'            => 'rsvp',
				'category'      => 'booking',
				'theme'         => 'sunset',
				'icon'          => 'party-popper',
				'title'         => __( 'RSVP', 'sureforms' ),
				'description'   => __( 'Find out who is coming to your party, wedding or gathering.', 'sureforms' ),
				'submit_text'   => __( 'Send my RSVP', 'sureforms' ),
				'fields'        => [
					$c['first_name'],
					$c['last_name'],
					$c['email'],
					self::attrs( self::field( 'multi-choice', 'attending', __( 'Will you attend?', 'sureforms' ), true, self::choice_options( [ __( 'Yes, I will be there', 'sureforms' ), __( 'Sorry, I can\'t make it', 'sureforms' ) ], true, [ 'circle-check', 'circle-xmark' ] ) ), [ 'choiceWidth' => 50 ] ),
					self::width(
						self::number_field(
							'guests',
							__( 'Number of Guests', 'sureforms' ),
							false,
							1,
							10,
							[
								'placeholder' => __( 'Including you', 'sureforms' ),
							]
						),
						50
					),
					self::width( self::field( 'input', 'dietary', __( 'Dietary Requirements', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. vegetarian, nut allergy', 'sureforms' ) ] ), 50 ),
					self::attrs( self::field( 'textarea', 'message', __( 'Message for the Host', 'sureforms' ), false, [ 'placeholder' => __( 'Anything you would like to share?', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
					$c['consent'],
				],
				'confirmation'  => [
					'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
					'text'    => __( 'Your RSVP is saved. We have sent a copy to {form:email}.', 'sureforms' ),
				],
				'email_subject' => __( 'RSVP: {form:first-name} {form:last-name} – {form:attending}', 'sureforms' ),
				'email_intro'   => __( '{form:first-name} {form:last-name} replied to your invitation: {form:attending}. Their full RSVP is below. Reply to this email to get in touch with them.', 'sureforms' ),
				'autoresponder' => [
					'subject'  => __( 'We got your RSVP', 'sureforms' ),
					'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
					'lines'    => [
						__( 'Thanks for letting us know. Your reply: {form:attending}.', 'sureforms' ),
						__( 'If your plans change, just reply to this email.', 'sureforms' ),
					],
				],
			],
			[
				'id'            => 'class-enrollment',
				'category'      => 'booking',
				'theme'         => 'ocean',
				'icon'          => 'graduation-cap',
				'title'         => __( 'Class Enrollment', 'sureforms' ),
				'description'   => __( 'Enroll students in a class or course.', 'sureforms' ),
				'submit_text'   => __( 'Enroll now', 'sureforms' ),
				'fields'        => [
					$c['first_name'],
					$c['last_name'],
					self::width( $c['email'], 50 ),
					self::width( $c['phone'], 50 ),
					self::width( self::attrs( self::field( 'dropdown', 'course', __( 'Course', 'sureforms' ), true, array_merge( self::dropdown_options( [ __( 'Beginner workshop', 'sureforms' ), __( 'Weekly class', 'sureforms' ), __( 'Weekend intensive', 'sureforms' ), __( 'Private lesson', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select a course', 'sureforms' ) ] ) ), $no_search ), 50 ),
					self::width( self::date_field( 'start-date', __( 'Preferred Start Date', 'sureforms' ), false, true ), 50 ),
					self::field( 'multi-choice', 'experience', __( 'Experience Level', 'sureforms' ), true, self::choice_options( [ __( 'New to this', 'sureforms' ), __( 'Some experience', 'sureforms' ), __( 'Experienced', 'sureforms' ) ], true, [ 'seedling', 'arrow-trend-up', 'award' ] ) ),
					self::field( 'multi-choice', 'schedule', __( 'Times That Suit You', 'sureforms' ), false, array_merge( self::choice_options( [ __( 'Weekday mornings', 'sureforms' ), __( 'Weekday evenings', 'sureforms' ), __( 'Weekends', 'sureforms' ) ], false, [ 'sun', 'moon', 'calendar-week' ] ), [ 'helpText' => __( 'Select all that apply.', 'sureforms' ) ] ) ),
					self::attrs( self::field( 'textarea', 'goals', __( 'Goals or Anything We Should Know', 'sureforms' ), false, [ 'placeholder' => __( 'What you hope to learn, access needs, questions…', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
					$c['consent'],
				],
				'confirmation'  => [
					'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
					'text'    => __( 'We have received your enrollment for {form:course} and sent a copy to {form:email}. We will be in touch with your place and the next steps.', 'sureforms' ),
				],
				'email_subject' => __( 'New enrollment: {form:first-name} {form:last-name} – {form:course}', 'sureforms' ),
				'email_intro'   => __( '{form:first-name} {form:last-name} wants to enroll in {form:course} (level: {form:experience}). Reply to this email to confirm their place.', 'sureforms' ),
				'autoresponder' => [
					'subject'  => __( 'Your enrollment for {form:course}', 'sureforms' ),
					'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
					'lines'    => [
						__( 'Thanks for enrolling in {form:course}. We have your details and will be in touch shortly to confirm your place and share the schedule.', 'sureforms' ),
						__( 'Questions in the meantime? Just reply to this email.', 'sureforms' ),
					],
				],
			],

			// Feedback & surveys.
			[
				'id'            => 'customer-feedback',
				'category'      => 'feedback',
				'theme'         => 'mint',
				'icon'          => 'message-circle-heart',
				'title'         => __( 'Customer Feedback', 'sureforms' ),
				'description'   => __( 'Ask customers what they liked and what to improve.', 'sureforms' ),
				'submit_text'   => __( 'Send feedback', 'sureforms' ),
				'fields'        => [
					self::field(
						'multi-choice',
						'satisfaction',
						__( 'How satisfied are you overall?', 'sureforms' ),
						true,
						array_merge(
							self::choice_options( $satisfaction, true, [ 'face-grin-stars', 'face-smile', 'face-meh', 'face-frown', 'face-angry' ] ),
							[
								// One option per row, so the five faces read as a scale.
								'blockAttributes' => [
									'verticalLayout' => false,
									'choiceWidth'    => 100,
								],
								'pro'             => [
									'fieldType'     => 'rating',
									'label'         => __( 'How would you rate your overall experience?', 'sureforms' ),
									'showTooltip'   => true,
									'tooltipValues' => [ [ __( 'Very poor', 'sureforms' ), __( 'Poor', 'sureforms' ), __( 'Okay', 'sureforms' ), __( 'Good', 'sureforms' ), __( 'Excellent', 'sureforms' ) ] ],
								],
							]
						)
					),
					self::attrs( self::field( 'textarea', 'liked', __( 'What did you like most?', 'sureforms' ) ), [ 'rows' => 3 ] ),
					self::attrs( self::field( 'textarea', 'improve', __( 'What could we do better?', 'sureforms' ) ), [ 'rows' => 3 ] ),
					self::width( self::field( 'input', 'full-name', __( 'Name (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ), 50 ),
					self::width(
						self::field(
							'email',
							'email',
							__( 'Email (if you would like a reply)', 'sureforms' ),
							false,
							[
								'placeholder' => __( 'jane@example.com', 'sureforms' ),
							]
						),
						50
					),
				],
				'confirmation'  => [
					'heading' => __( 'Thanks for your feedback', 'sureforms' ),
					'text'    => __( 'Your answers help us do better.', 'sureforms' ),
				],
				'email_subject' => __( 'New customer feedback – overall: {form:satisfaction}', 'sureforms' ),
				'email_intro'   => __( 'A customer rated their overall experience: {form:satisfaction}. Their answers are below. If they left an email address, you can follow up there.', 'sureforms' ),
			],
			[
				'id'            => 'satisfaction-survey',
				'category'      => 'feedback',
				'theme'         => 'lavender',
				'icon'          => 'gauge',
				'title'         => __( 'Satisfaction Survey', 'sureforms' ),
				'description'   => __( 'A short survey with a 0–10 recommend score.', 'sureforms' ),
				'submit_text'   => __( 'Submit survey', 'sureforms' ),
				'fields'        => [
					self::field(
						'dropdown',
						'recommend-score',
						__( 'How likely are you to recommend us to a friend or colleague?', 'sureforms' ),
						true,
						array_merge(
							self::dropdown_options( $nps_scale ),
							[
								'placeholder'     => __( 'Select a score', 'sureforms' ),
								'helpText'        => __( '0 = not at all likely, 10 = extremely likely', 'sureforms' ),
								'blockAttributes' => $no_search,
								'pro'             => [
									'fieldType'   => 'nps',
									'helpText'    => '',
									'placeholder' => '',
								],
							]
						)
					),
					self::attrs( self::field( 'textarea', 'reason', __( 'What is the main reason for your score?', 'sureforms' ) ), [ 'rows' => 3 ] ),
					self::attrs( self::field( 'multi-choice', 'frequency', __( 'How often do you use our product or service?', 'sureforms' ), false, self::choice_options( [ __( 'Daily', 'sureforms' ), __( 'Weekly', 'sureforms' ), __( 'Monthly', 'sureforms' ), __( 'Rarely', 'sureforms' ) ], true, [ 'calendar-day', 'calendar-week', 'calendar', 'hourglass' ] ) ), [ 'choiceWidth' => 25 ] ),
					self::field(
						'email',
						'email',
						__( 'Email (optional)', 'sureforms' ),
						false,
						[
							'placeholder' => __( 'jane@example.com', 'sureforms' ),
							'helpText'    => __(
								'Only if you would like us to follow up.',
								'sureforms'
							),
						]
					),
				],
				'confirmation'  => [
					'heading' => __( 'Thank you', 'sureforms' ),
					'text'    => __( 'We appreciate you taking the time to answer.', 'sureforms' ),
				],
				'email_subject' => __( 'New survey response: {form:recommend-score}/10', 'sureforms' ),
				'email_intro'   => __( 'New recommend score: {form:recommend-score}/10. As a guide, 9–10 are promoters, 7–8 passives and 0–6 detractors. The full response is below.', 'sureforms' ),
			],
			[
				'id'            => 'website-feedback',
				'category'      => 'feedback',
				'theme'         => 'ocean',
				'icon'          => 'monitor',
				'title'         => __( 'Website Feedback', 'sureforms' ),
				'description'   => __( 'Learn whether visitors found what they came for.', 'sureforms' ),
				'submit_text'   => __( 'Send feedback', 'sureforms' ),
				'fields'        => [
					self::field( 'multi-choice', 'found-it', __( 'Did you find what you were looking for?', 'sureforms' ), true, self::choice_options( [ __( 'Yes', 'sureforms' ), __( 'Partly', 'sureforms' ), __( 'No', 'sureforms' ) ], true, [ 'circle-check', 'circle-half-stroke', 'circle-xmark' ] ) ),
					self::attrs( self::field( 'multi-choice', 'ease', __( 'How easy was the site to use?', 'sureforms' ), true, self::choice_options( [ __( 'Very easy', 'sureforms' ), __( 'Easy', 'sureforms' ), __( 'Difficult', 'sureforms' ), __( 'Very difficult', 'sureforms' ) ], true, [ 'face-grin-beam', 'face-smile', 'face-frown', 'face-angry' ] ) ), [ 'choiceWidth' => 25 ] ),
					self::field( 'textarea', 'improve', __( 'What could we do better?', 'sureforms' ), false, [ 'placeholder' => __( 'Tell us what was missing, confusing or not working.', 'sureforms' ) ] ),
					self::field(
						'upload',
						'screenshot',
						__( 'Screenshot (optional)', 'sureforms' ),
						false,
						[
							'pro_only'     => true,
							'helpText'     => __( 'Helpful if something looked broken.', 'sureforms' ),
							'allowedTypes' => 'jpg,jpeg,png,gif,webp',
							'uploadSize'   => 5,
						]
					),
					self::width( self::field( 'input', 'page', __( 'Which page is this about?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Pricing, or paste a link', 'sureforms' ) ] ), 50 ),
					self::width(
						self::field(
							'email',
							'email',
							__( 'Email (if you would like a reply)', 'sureforms' ),
							false,
							[
								'placeholder' => __( 'jane@example.com', 'sureforms' ),
							]
						),
						50
					),
				],
				'confirmation'  => [
					'heading' => __( 'Thanks for helping us improve', 'sureforms' ),
					'text'    => __( 'We read every response and use it to make the site better.', 'sureforms' ),
				],
				'email_subject' => __( 'Website feedback: found what they needed? {form:found-it}', 'sureforms' ),
				'email_intro'   => __( 'A visitor left feedback from {current_page_url}. If they left an email address, you will find it below.', 'sureforms' ),
			],
			[
				'id'            => 'testimonial',
				'category'      => 'feedback',
				'theme'         => 'sand',
				'icon'          => 'quote',
				'title'         => __( 'Testimonial Submission', 'sureforms' ),
				'description'   => __( 'Collect reviews you can feature on your site.', 'sureforms' ),
				'submit_text'   => __( 'Share my testimonial', 'sureforms' ),
				'fields'        => [
					self::width( $c['name'], 50 ),
					self::width( array_merge( $c['email'], [ 'label' => __( 'Email (never published)', 'sureforms' ) ] ), 50 ),
					self::width( self::field( 'input', 'role', __( 'Job Title', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Owner', 'sureforms' ) ] ), 50 ),
					self::width( self::field( 'input', 'company', __( 'Company', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Bright Bakery', 'sureforms' ) ] ), 50 ),
					self::field(
						'rating',
						'rating',
						__( 'How would you rate us?', 'sureforms' ),
						false,
						[
							'pro_only'  => true,
							'iconShape' => 'star',
						]
					),
					self::attrs(
						self::field(
							'textarea',
							'testimonial',
							__( 'Your Testimonial', 'sureforms' ),
							true,
							[
								'placeholder' => __( 'What did we help you with, and what difference did it make?', 'sureforms' ),
								'helpText'    => __( 'A few honest sentences are perfect.', 'sureforms' ),
							]
						),
						[ 'rows' => 5 ]
					),
					self::field(
						'upload',
						'photo',
						__( 'Your Photo (optional)', 'sureforms' ),
						false,
						[
							'pro_only'     => true,
							'helpText'     => __( 'A clear headshot shown next to your testimonial.', 'sureforms' ),
							'allowedTypes' => 'jpg,jpeg,png',
							'uploadSize'   => 5,
						]
					),
					self::field( 'gdpr', 'publish-permission', __( 'You may publish my testimonial with my name and job title on this website. It may be lightly edited for length.', 'sureforms' ), true ),
				],
				'confirmation'  => [
					'heading' => __( 'Thank you, {form:full-name}', 'sureforms' ),
					'text'    => __( 'We really appreciate you sharing your experience. We will review it before it appears on our site.', 'sureforms' ),
				],
				'email_subject' => __( 'New testimonial from {form:full-name}', 'sureforms' ),
				'email_intro'   => __( '{form:full-name} shared a testimonial and gave permission to publish it. Reply to this email to thank them or ask a question.', 'sureforms' ),
				'autoresponder' => [
					'subject' => __( 'Thank you for your testimonial', 'sureforms' ),
					'lines'   => [
						__( 'Thank you for taking the time to share your experience. It means a lot to us.', 'sureforms' ),
						__( 'We review every testimonial before publishing it. If you would like to change anything, just reply to this email.', 'sureforms' ),
					],
				],
			],
			[
				'id'            => 'complaint',
				'category'      => 'feedback',
				'theme'         => 'mint',
				'icon'          => 'circle-alert',
				'title'         => __( 'Complaint Form', 'sureforms' ),
				'description'   => __( 'Give customers a clear way to raise a problem.', 'sureforms' ),
				'submit_text'   => __( 'Submit complaint', 'sureforms' ),
				'steps'         => [ __( 'Your details', 'sureforms' ), __( 'Your complaint', 'sureforms' ) ],
				'fields'        => [
					self::width( $c['name'], 50 ),
					self::width( $c['email'], 50 ),
					self::width( $c['phone'], 50 ),
					self::width( self::field( 'input', 'reference', __( 'Order or Reference Number', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. #10234', 'sureforms' ) ] ), 50 ),
					self::step( 'complaint-step', __( 'Your complaint', 'sureforms' ) ),
					self::width( self::attrs( self::field( 'dropdown', 'complaint-type', __( 'What is your complaint about?', 'sureforms' ), true, array_merge( self::dropdown_options( [ __( 'Product quality', 'sureforms' ), __( 'Service or staff', 'sureforms' ), __( 'Billing or payment', 'sureforms' ), __( 'Delivery or shipping', 'sureforms' ), __( 'Website or account', 'sureforms' ), __( 'Something else', 'sureforms' ) ] ), [ 'placeholder' => __( 'Select a topic', 'sureforms' ) ] ) ), $no_search ), 50 ),
					self::width( self::date_field( 'incident-date', __( 'When did it happen?', 'sureforms' ) ), 50 ),
					self::attrs( self::field( 'textarea', 'details', __( 'What happened?', 'sureforms' ), true, [ 'helpText' => __( 'Please include names, dates and anything you have already tried.', 'sureforms' ) ] ), [ 'rows' => 6 ] ),
					self::field(
						'url',
						'evidence',
						__( 'Link to a photo or document (optional)', 'sureforms' ),
						false,
						[
							'placeholder' => 'https://',
							'helpText'    => __( 'For example a shared Google Drive or Dropbox link.', 'sureforms' ),
							'pro'         => [
								'fieldType'        => 'upload',
								'label'            => __( 'Photos or documents (optional)', 'sureforms' ),
								'placeholder'      => '',
								'helpText'         => __( 'Up to 3 files: JPG, PNG or PDF.', 'sureforms' ),
								'allowedTypes'     => 'jpg,jpeg,png,pdf',
								'uploadSize'       => 10,
								'multiUpload'      => true,
								'multiFilesNumber' => 3,
							],
						]
					),
					self::attrs( self::field( 'textarea', 'resolution', __( 'How would you like us to put it right?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. a refund, a replacement or a call back', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
					$c['consent'],
				],
				'confirmation'  => [
					'heading' => __( 'We have received your complaint', 'sureforms' ),
					'text'    => __( 'We are sorry for the trouble, {form:full-name}. A copy has been sent to {form:email}, and we will contact you once we have looked into it.', 'sureforms' ),
				],
				'email_subject' => __( 'Complaint from {form:full-name}: {form:complaint-type}', 'sureforms' ),
				'email_intro'   => __( '{form:full-name} raised a complaint about: {form:complaint-type}. Reply to this email to respond to them directly.', 'sureforms' ),
				'autoresponder' => [
					'subject' => __( 'We have received your complaint', 'sureforms' ),
					'lines'   => [
						__( 'We are sorry for the trouble, and thank you for letting us know.', 'sureforms' ),
						__( 'Your complaint has been logged and someone from our team will look into it and contact you. If you have anything to add, just reply to this email.', 'sureforms' ),
					],
				],
			],

			// Sign-ups & applications.
			[
				'id'            => 'newsletter-signup',
				'category'      => 'signup',
				'theme'         => 'sunset',
				'icon'          => 'newspaper',
				'title'         => __( 'Newsletter Signup', 'sureforms' ),
				'description'   => __( 'Grow your mailing list with a one-line signup form.', 'sureforms' ),
				'submit_text'   => __( 'Subscribe', 'sureforms' ),
				'fields'        => [
					self::width( array_merge( $c['email'], [ 'label' => __( 'Email Address', 'sureforms' ) ] ), 75 ),
					self::attrs(
						self::field( 'inline-button', 'subscribe', __( 'Subscribe', 'sureforms' ) ),
						[
							'fieldWidth' => 25,
							'buttonText' => __( 'Subscribe', 'sureforms' ),
						]
					),
					self::field( 'gdpr', 'subscribe-consent', __( 'Yes, send me news and updates by email. I can unsubscribe at any time.', 'sureforms' ), true ),
				],
				'confirmation'  => [
					'heading' => __( 'You are on the list', 'sureforms' ),
					'text'    => __( 'Thanks for subscribing. We have sent a welcome email to {form:email}.', 'sureforms' ),
				],
				'email_subject' => __( 'New subscriber: {form:email}', 'sureforms' ),
				'email_intro'   => __( '{form:email} signed up for your newsletter. Add them to your email list, or connect an email marketing integration to do this automatically.', 'sureforms' ),
				'autoresponder' => [
					'greeting' => __( 'Hi there,', 'sureforms' ),
					'subject'  => __( 'Welcome to {site_title}', 'sureforms' ),
					'lines'    => [
						__( 'Thanks for subscribing. We will send our latest news and updates to this address.', 'sureforms' ),
						__( 'If you did not sign up, you can ignore this email or reply to let us know.', 'sureforms' ),
					],
					'summary'  => false,
				],
			],

			// Support.
			[
				'id'            => 'support-request',
				'category'      => 'support',
				'theme'         => 'ocean',
				'icon'          => 'life-buoy',
				'title'         => __( 'Support Request', 'sureforms' ),
				'description'   => __( 'Let customers report an issue and how urgent it is.', 'sureforms' ),
				'submit_text'   => __( 'Send request', 'sureforms' ),
				'fields'        => [
					self::width( $c['name'], 50 ),
					self::width( $c['email'], 50 ),
					self::width( self::attrs( self::field( 'dropdown', 'topic', __( 'Topic', 'sureforms' ), false, array_merge( self::dropdown_options( [ __( 'Technical issue', 'sureforms' ), __( 'Billing or payment', 'sureforms' ), __( 'Account or login', 'sureforms' ), __( 'Orders and delivery', 'sureforms' ), __( 'Something else', 'sureforms' ) ] ), [ 'placeholder' => __( 'Choose a topic', 'sureforms' ) ] ) ), $no_search ), 50 ),
					self::width( self::field( 'input', 'order-number', __( 'Order or Account Number', 'sureforms' ), false, [ 'placeholder' => __( 'If you have one', 'sureforms' ) ] ), 50 ),
					self::field( 'input', 'subject', __( 'Subject', 'sureforms' ), true, [ 'placeholder' => __( 'A short summary of the issue', 'sureforms' ) ] ),
					self::attrs(
						self::field(
							'multi-choice',
							'priority',
							__( 'How urgent is this?', 'sureforms' ),
							true,
							array_merge(
								self::choice_options( [ __( 'Low', 'sureforms' ), __( 'Normal', 'sureforms' ), __( 'High', 'sureforms' ), __( 'Urgent', 'sureforms' ) ], true, [ 'arrow-down', 'equals', 'arrow-up', 'fire' ] ),
								[ 'helpText' => __( 'Choose Urgent only if you cannot use the product or service at all.', 'sureforms' ) ]
							)
						),
						[ 'choiceWidth' => 25 ]
					),
					self::attrs( self::field( 'textarea', 'description', __( 'Describe the issue', 'sureforms' ), true, [ 'helpText' => __( 'Include what you were doing, what happened and any error messages you saw.', 'sureforms' ) ] ), [ 'rows' => 6 ] ),
					self::field( 'url', 'link', __( 'Link to a screenshot or page (optional)', 'sureforms' ), false, array_merge( [ 'placeholder' => 'https://' ], $screenshots ) ),
					$c['consent'],
				],
				'confirmation'  => [
					'heading' => __( 'Request received', 'sureforms' ),
					'text'    => __( 'Thanks, {form:full-name}. Your reference number is #{entry_id}. We will reply to {form:email} as soon as we can.', 'sureforms' ),
				],
				'email_subject' => __( 'Support: {form:subject} ({form:priority}) from {form:full-name}', 'sureforms' ),
				'email_intro'   => __( '{form:full-name} needs help ({form:priority} priority, request #{entry_id}). Reply to this email to answer them directly.', 'sureforms' ),
				'autoresponder' => [
					'subject' => __( 'We received your request: {form:subject}', 'sureforms' ),
					'lines'   => [
						__( 'Thanks for getting in touch. We have logged your request about "{form:subject}" with reference number #{entry_id}.', 'sureforms' ),
						__( 'Our team will reply as soon as we can. If you have anything to add, just reply to this email.', 'sureforms' ),
					],
				],
			],
			[
				'id'            => 'bug-report',
				'category'      => 'support',
				'theme'         => 'midnight',
				'icon'          => 'bug',
				'title'         => __( 'Bug Report', 'sureforms' ),
				'description'   => __( 'Collect clear, reproducible bug reports.', 'sureforms' ),
				'submit_text'   => __( 'Report bug', 'sureforms' ),
				'fields'        => [
					self::width( $c['name'], 50 ),
					self::width( $c['email'], 50 ),
					self::field( 'input', 'summary', __( 'Bug Summary', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. The checkout button does nothing on mobile', 'sureforms' ) ] ),
					self::field(
						'multi-choice',
						'severity',
						__( 'Severity', 'sureforms' ),
						true,
						array_merge(
							self::choice_options( [ __( 'Minor', 'sureforms' ), __( 'Major', 'sureforms' ), __( 'Blocking', 'sureforms' ) ], true, [ 'circle-info', 'triangle-exclamation', 'ban' ] ),
							[ 'helpText' => __( 'Minor: looks wrong or is annoying. Major: something does not work. Blocking: you cannot continue.', 'sureforms' ) ]
						)
					),
					self::attrs( self::field( 'textarea', 'what-happened', __( 'What went wrong?', 'sureforms' ), true, [ 'helpText' => __( 'Tell us what you saw, including any error message, and what you expected to happen instead.', 'sureforms' ) ] ), [ 'rows' => 5 ] ),
					self::field(
						'textarea',
						'steps',
						__( 'Steps to Reproduce', 'sureforms' ),
						false,
						[
							'placeholder' => __( '1. Go to… 2. Click… 3. See the error', 'sureforms' ),
							'helpText'    => __( 'Optional, but it helps us fix the problem much faster.', 'sureforms' ),
						]
					),
					self::field( 'url', 'page-url', __( 'Page Where It Happened', 'sureforms' ), false, [ 'placeholder' => 'https://' ] ),
					self::width( self::attrs( self::field( 'dropdown', 'browser', __( 'Browser', 'sureforms' ), false, array_merge( self::dropdown_options( [ 'Chrome', 'Safari', 'Firefox', 'Edge', __( 'Other', 'sureforms' ) ] ), [ 'placeholder' => __( 'Choose a browser', 'sureforms' ) ] ) ), $no_search ), 50 ),
					self::width( self::attrs( self::field( 'dropdown', 'device', __( 'Device', 'sureforms' ), false, array_merge( self::dropdown_options( [ __( 'Desktop or laptop', 'sureforms' ), __( 'Phone', 'sureforms' ), __( 'Tablet', 'sureforms' ) ] ), [ 'placeholder' => __( 'Choose a device', 'sureforms' ) ] ) ), $no_search ), 50 ),
					self::field( 'url', 'screenshot', __( 'Link to a screenshot (optional)', 'sureforms' ), false, array_merge( [ 'placeholder' => 'https://' ], $screenshots ) ),
					$c['consent'],
				],
				'confirmation'  => [
					'heading' => __( 'Thanks for the report', 'sureforms' ),
					'text'    => __( 'We have logged "{form:summary}" as report #{entry_id}. We will contact you at {form:email} if we need more details.', 'sureforms' ),
				],
				'email_subject' => __( 'Bug: {form:summary} ({form:severity})', 'sureforms' ),
				'email_intro'   => __( '{form:full-name} reported a {form:severity} bug (report #{entry_id}). Reply to this email to ask them for more details.', 'sureforms' ),
				'autoresponder' => [
					'subject' => __( 'We received your bug report: {form:summary}', 'sureforms' ),
					'lines'   => [
						__( 'Thanks for helping us improve. We have logged your report "{form:summary}" with reference number #{entry_id}.', 'sureforms' ),
						__( 'We will look into it and get back to you if we need more details.', 'sureforms' ),
					],
				],
			],

			// Sign-ups & applications.
			[
				'id'            => 'volunteer-signup',
				'category'      => 'signup',
				'theme'         => 'mint',
				'icon'          => 'hand-heart',
				'title'         => __( 'Volunteer Signup', 'sureforms' ),
				'description'   => __( 'Recruit volunteers and see when they can help.', 'sureforms' ),
				'submit_text'   => __( 'Sign me up', 'sureforms' ),
				'fields'        => [
					$c['name'],
					self::width( $c['email'], 50 ),
					self::width( $c['phone'], 50 ),
					self::attrs(
						self::field(
							'multi-choice',
							'availability',
							__( 'When are you available?', 'sureforms' ),
							true,
							array_merge(
								self::choice_options( [ __( 'Weekday daytime', 'sureforms' ), __( 'Weekday evenings', 'sureforms' ), __( 'Weekends', 'sureforms' ), __( 'Flexible', 'sureforms' ) ], false, [ 'sun', 'moon', 'calendar-week', 'clock' ] ),
								[ 'helpText' => __( 'Select all that apply.', 'sureforms' ) ]
							)
						),
						[ 'choiceWidth' => 25 ]
					),
					self::field(
						'multi-choice',
						'interests',
						__( 'How would you like to help?', 'sureforms' ),
						false,
						array_merge(
							self::choice_options( [ __( 'Events', 'sureforms' ), __( 'Fundraising', 'sureforms' ), __( 'Outreach', 'sureforms' ), __( 'Admin and office', 'sureforms' ), __( 'Social media', 'sureforms' ), __( 'Wherever needed', 'sureforms' ) ], false, [ 'calendar-days', 'hand-holding-dollar', 'bullhorn', 'file-lines', 'hashtag', 'heart' ] ),
							[ 'helpText' => __( 'Select all that apply.', 'sureforms' ) ]
						)
					),
					self::field( 'textarea', 'skills', __( 'Relevant Skills or Experience', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. first aid, driving licence, languages, event planning', 'sureforms' ) ] ),
					array_merge( $c['consent'], [ 'label' => __( 'I agree to have this website store my details so it can contact me about volunteering opportunities.', 'sureforms' ) ] ),
				],
				'confirmation'  => [
					'heading' => __( 'Thanks for volunteering, {form:full-name}!', 'sureforms' ),
					'text'    => __( 'We will be in touch at {form:email} about opportunities that suit your availability.', 'sureforms' ),
				],
				'email_subject' => __( 'New volunteer: {form:full-name}', 'sureforms' ),
				'email_intro'   => __( '{form:full-name} signed up to volunteer. Available: {form:availability}.', 'sureforms' ),
				'autoresponder' => [
					'subject' => __( 'Thanks for volunteering with {site_title}', 'sureforms' ),
					'lines'   => [
						__( 'Thank you for offering your time. We have noted when you are available and what you would like to help with.', 'sureforms' ),
						__( 'We will contact you about upcoming opportunities that match.', 'sureforms' ),
					],
				],
			],
			[
				'id'            => 'job-application',
				'category'      => 'signup',
				'theme'         => 'lavender',
				'icon'          => 'briefcase',
				'title'         => __( 'Job Application', 'sureforms' ),
				'description'   => __( 'Accept applications with the applicant\'s CV.', 'sureforms' ),
				'submit_text'   => __( 'Submit application', 'sureforms' ),
				'steps'         => [ __( 'About you', 'sureforms' ), __( 'Your experience', 'sureforms' ) ],
				'fields'        => [
					self::width( $c['name'], 50 ),
					self::width( $c['email'], 50 ),
					self::width( array_merge( $c['phone'], [ 'required' => true ] ), 50 ),
					self::width( self::field( 'input', 'position', __( 'Position Applied For', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. Marketing Manager', 'sureforms' ) ] ), 50 ),
					self::step( 'experience-step', __( 'Your experience', 'sureforms' ) ),
					self::field(
						'url',
						'cv',
						__( 'Link to your CV', 'sureforms' ),
						true,
						[
							'placeholder' => 'https://',
							'helpText'    => __( 'A Google Drive, Dropbox or OneDrive link that anyone with the link can view.', 'sureforms' ),
							'pro'         => [
								'fieldType'    => 'upload',
								'label'        => __( 'Your CV', 'sureforms' ),
								'placeholder'  => '',
								'helpText'     => __( 'PDF or Word document, up to 5 MB.', 'sureforms' ),
								'allowedTypes' => 'pdf,doc,docx',
								'uploadSize'   => 5,
							],
						]
					),
					self::width( self::field( 'url', 'linkedin', __( 'LinkedIn Profile', 'sureforms' ), false, [ 'placeholder' => 'https://linkedin.com/in/' ] ), 50 ),
					self::width( self::field( 'url', 'portfolio', __( 'Portfolio or Website', 'sureforms' ), false, [ 'placeholder' => 'https://' ] ), 50 ),
					self::width( self::attrs( self::number_field( 'years-experience', __( 'Years of Relevant Experience', 'sureforms' ), false, 0, 50, [ 'placeholder' => __( 'e.g. 3', 'sureforms' ) ] ), [ 'suffix' => __( 'years', 'sureforms' ) ] ), 50 ),
					self::width( self::attrs( self::field( 'dropdown', 'start-date', __( 'When Could You Start?', 'sureforms' ), false, array_merge( self::dropdown_options( [ __( 'Immediately', 'sureforms' ), __( 'Within a month', 'sureforms' ), __( 'In 1–3 months', 'sureforms' ), __( 'Later than that', 'sureforms' ) ] ), [ 'placeholder' => __( 'Choose an option', 'sureforms' ) ] ) ), $no_search ), 50 ),
					self::attrs( self::field( 'textarea', 'cover-letter', __( 'Why are you a good fit for this role?', 'sureforms' ), true, [ 'helpText' => __( 'A few sentences about your relevant experience is plenty.', 'sureforms' ) ] ), [ 'rows' => 6 ] ),
					array_merge( $c['consent'], [ 'label' => __( 'I agree to have this website store my application details so they can be used to assess my application.', 'sureforms' ) ] ),
				],
				'confirmation'  => [
					'heading' => __( 'Your application for {form:position} is in', 'sureforms' ),
					'text'    => __( 'Thanks, {form:full-name}. We will review it and contact you at {form:email} if your experience is a good match.', 'sureforms' ),
				],
				'email_subject' => __( 'Application for {form:position} from {form:full-name}', 'sureforms' ),
				'email_intro'   => __( '{form:full-name} applied for {form:position}. Their details and answers are below.', 'sureforms' ),
				'autoresponder' => [
					'subject' => __( 'We received your application for {form:position}', 'sureforms' ),
					'lines'   => [
						__( 'Thank you for applying for the {form:position} role at {site_title}.', 'sureforms' ),
						__( 'Our team reviews every application. If your experience is a good match, we will contact you about next steps.', 'sureforms' ),
					],
				],
			],
		];
	}
}
