<?php
/**
 * Prebuilt form template catalog.
 *
 * The templates live in one file per library category under `catalog/`, each
 * returning its templates as an array. This class loads them in display order
 * and holds the helpers the catalog files build their fields with.
 *
 * @package sureforms
 * @since 2.13.0
 */

namespace SRFM\Inc\Form_Templates;

use SRFM\Inc\Form_Templates;
use SRFM\Inc\Helper;
use SRFM\Inc\Payments\Payment_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Template catalog loader and field helpers.
 *
 * @since 2.13.0
 */
class Catalog {
	/**
	 * Catalog files, one per category, in the order they are loaded.
	 *
	 * @since 2.13.0
	 */
	public const FILES = [ 'contact', 'booking', 'feedback', 'signup', 'support', 'calculators', 'quizzes', 'payments', 'agreements', 'accounts' ];

	/**
	 * Display order of the templates in the library. Templates not listed here
	 * follow, in the order of FILES and then of each file.
	 *
	 * @since 2.13.0
	 */
	public const ORDER = [
		'contact-us',
		'quote-request',
		'callback-request',
		'consultation-request',
		'product-inquiry',
		'partnership-inquiry',
		'appointment-request',
		'event-registration',
		'rsvp',
		'class-enrollment',
		'customer-feedback',
		'satisfaction-survey',
		'website-feedback',
		'testimonial',
		'complaint',
		'newsletter-signup',
		'support-request',
		'bug-report',
		'volunteer-signup',
		'job-application',
	];

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
	 * The files are required on every call, not once, so the strings are
	 * translated in the language active at the time.
	 *
	 * @since 2.13.0
	 * @return array<int, array<string, mixed>>
	 */
	public static function templates() {
		$templates = [];
		foreach ( self::FILES as $file ) {
			foreach ( Helper::get_array_value( require __DIR__ . '/catalog/' . $file . '.php' ) as $template ) {
				if ( is_array( $template ) ) {
					$templates[] = $template;
				}
			}
		}

		$position = array_flip( self::ORDER );
		$count    = count( $position );
		$keys     = [];
		foreach ( $templates as $index => $template ) {
			$id             = Helper::get_string_value( $template['id'] ?? '' );
			$keys[ $index ] = $position[ $id ] ?? $count + $index;
		}
		array_multisort( $keys, SORT_NUMERIC, $templates );

		return $templates;
	}

	/**
	 * Field helper: one field in the AI builder schema.
	 *
	 * @param string               $type     Field type (block name without `srfm/`).
	 * @param string               $slug     Field slug.
	 * @param string               $label    Label.
	 * @param bool                 $required Required.
	 * @param array<string, mixed> $extra    Extra schema keys (placeholder, helpText, fieldOptions, ...).
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	public static function field( $type, $slug, $label, $required = false, $extra = [] ) {
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
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	public static function dropdown_options( $labels ) {
		return [ 'fieldOptions' => array_map( static fn( $label ) => [ 'label' => $label ], $labels ) ];
	}

	/**
	 * Options for a multiple-choice field.
	 *
	 * @param array<int, string> $labels Option labels.
	 * @param bool               $single Single selection.
	 * @param array<int, string> $icons  Optional icon per option (Font Awesome names). With
	 *                                   an icon on every option the choices show as cards.
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	public static function choice_options( $labels, $single = true, $icons = [] ) {
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
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	public static function width( $field, $width ) {
		return self::attrs( $field, [ 'fieldWidth' => $width ] );
	}

	/**
	 * Adds block attributes to a field, keeping the ones it already has.
	 *
	 * @param array<string, mixed> $field      Field schema.
	 * @param array<string, mixed> $attributes Block attributes.
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	public static function attrs( $field, $attributes ) {
		$field['blockAttributes'] = array_merge( Helper::get_array_value( $field['blockAttributes'] ?? [] ), $attributes );
		return $field;
	}

	/**
	 * A thank-you message in the layout of a template's own one (check icon,
	 * heading, text), for the messages in a template's `confirmations`.
	 *
	 * @param string $heading Heading.
	 * @param string $text    Text.
	 * @since 2.13.0
	 * @return string HTML.
	 */
	public static function thank_you( $heading, $text ) {
		return Form_Templates::confirmation_message( $heading, $text );
	}

	/**
	 * The site's currency symbol, or its code and a space when it has no symbol.
	 *
	 * @since 2.13.0
	 * @return string
	 */
	public static function currency_symbol() {
		$currency = Payment_Helper::get_currency();
		$symbol   = Payment_Helper::get_currency_symbol( $currency );
		return '' === $symbol ? $currency . ' ' : $symbol;
	}

	/**
	 * An amount in the site's currency, e.g. "$1,500".
	 *
	 * @param float|int $amount Amount.
	 * @since 2.13.0
	 * @return string
	 */
	public static function money( $amount ) {
		return self::currency_symbol() . number_format_i18n( $amount );
	}

	/**
	 * Name, email and consent fields most templates share.
	 *
	 * @since 2.13.0
	 * @return array<string, array<string, mixed>>
	 */
	public static function common_fields() {
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
	 * A date question: a date picker with SureForms Pro, a text field without it.
	 *
	 * @param string $slug     Field slug.
	 * @param string $label    Label.
	 * @param bool   $required Required.
	 * @param bool   $future   Only dates from today on (bookings), not past ones.
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	public static function date_field( $slug, $label, $required = false, $future = false ) {
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
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	public static function step( $slug, $label ) {
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
	 * @since 2.13.0
	 * @return array<string, mixed>
	 */
	public static function number_field( $slug, $label, $required, $min, $max, $extra = [] ) {
		return self::attrs(
			self::field( 'number', $slug, $label, $required, $extra ),
			[
				'minValue' => $min,
				'maxValue' => $max,
			]
		);
	}

	/**
	 * The site's country from its language (en_GB gives gb), used when the
	 * phone field cannot detect the visitor's country.
	 *
	 * @since 2.13.0
	 * @return string Two-letter country code, or empty when the language has none.
	 */
	private static function site_country() {
		$parts = explode( '_', get_locale() );
		return isset( $parts[1] ) && 2 === strlen( $parts[1] ) ? strtolower( $parts[1] ) : '';
	}
}
