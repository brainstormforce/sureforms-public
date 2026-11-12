<?php
/**
 * Field Rules Class
 *
 * Applies a form's own field rules to a submission on the server.
 *
 * @package SureForms
 * @since 2.13.1
 */

namespace SRFM\Inc;

use SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Server-side counterpart of the field checks in assets/js/unminified/validation.js.
 *
 * INVARIANT — a value the browser's checks accept is accepted here. Each rule
 * below mirrors the browser's own condition (the line it mirrors is named next to
 * it), so these rules only turn away a request the rendered form could not have
 * sent. They exist because the browser checks run in the visitor's browser and a
 * request can be sent without them.
 *
 * The rules are read from the saved form: its blocks, with the block.json defaults
 * the renderer applies, and its conditional logic meta. Nothing about the rules is
 * taken from the request.
 *
 * @since 2.13.1
 *
 * @phpstan-type RuleField array{type: string, block_id: string, slug: string, attrs: array<string, mixed>, in_address: bool}
 * @phpstan-type RuleContext array{hidden_ids: array<string, true>, multilingual: bool, in_address?: bool}
 */
class Field_Rules {
	/**
	 * Field blocks these rules cover, mapped to their rule set.
	 *
	 * @since 2.13.1
	 */
	private const FIELD_BLOCKS = [
		'srfm/input'        => 'input',
		'srfm/email'        => 'email',
		'srfm/textarea'     => 'textarea',
		'srfm/number'       => 'number',
		'srfm/dropdown'     => 'dropdown',
		'srfm/multi-choice' => 'multi-choice',
		'srfm/checkbox'     => 'checkbox',
		'srfm/gdpr'         => 'gdpr',
		'srfm/phone'        => 'phone',
		'srfm/url'          => 'url',
	];

	/**
	 * The browser's email pattern (validation.js, `emailRegex`), with `u` so a
	 * non-ASCII local part is read by code point as the browser reads it.
	 *
	 * @since 2.13.1
	 */
	private const EMAIL_PATTERN = '/^(([^<>()[\]\\\\.,;:\s@"]+(\.[^<>()[\]\\\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/u';

	/**
	 * Separator the browser joins multi-select values with (frontend.js `prepareValue`).
	 *
	 * @since 2.13.1
	 */
	private const VALUE_SEPARATOR = ' | ';

	/**
	 * Check a submission against the form's field rules.
	 *
	 * @param array<mixed> $form_data Submitted fields after sanitizing and the unknown-key strip.
	 * @param array<mixed> $raw_data  The same request before sanitizing, read for the
	 *                                values a sanitizer would change (an invalid email
	 *                                sanitizes to '').
	 * @param int          $form_id   Form being submitted.
	 * @since 2.13.1
	 * @return array<string,string> Error message by field key; an unsent checkbox is keyed `srfm-<type>-<block_id>`.
	 */
	public static function validate( $form_data, $raw_data, $form_id ) {
		$form_id = absint( $form_id );

		/**
		 * Filters whether the server applies a form's field rules to its submissions.
		 *
		 * @since 2.13.1
		 * @param bool $enforce Whether to apply them. Default true.
		 * @param int  $form_id Form being submitted.
		 */
		if ( $form_id <= 0 || ! apply_filters( 'srfm_enforce_field_rules', true, $form_id ) ) {
			return [];
		}

		$post = get_post( $form_id );
		if ( ! $post instanceof \WP_Post || '' === $post->post_content || ! function_exists( 'parse_blocks' ) ) {
			return [];
		}

		$context = [
			'hidden_ids'   => self::get_conditional_block_ids( $form_id ),
			'multilingual' => Multilingual_Manager::get_instance()->provider()->is_active(),
		];
		$fields  = [];
		$visited = [];
		self::collect_fields( parse_blocks( $post->post_content ), $context, $fields, $visited );

		$keys   = self::index_submitted_keys( is_array( $form_data ) ? $form_data : [] );
		$errors = [];

		foreach ( $fields as $field ) {
			$key = $keys['ids'][ $field['block_id'] ] ?? ( '' !== $field['slug'] ? ( $keys['slugs'][ $field['slug'] ] ?? '' ) : '' );

			// The browser posts every rendered field except an unticked checkbox, which
			// therefore reads as empty. Any other field missing from the request is
			// treated as not rendered (hidden by block visibility or another plugin's
			// render filter, or added after a cached copy of the page was made) and is
			// left unchecked, rather than failing a page the visitor cannot fix.
			if ( '' === $key && ! in_array( $field['type'], [ 'checkbox', 'gdpr' ], true ) ) {
				continue;
			}

			// Read the request value, falling back to the sanitized one.
			$value = '';
			if ( '' !== $key ) {
				$value = is_array( $raw_data ) && array_key_exists( $key, $raw_data ) ? $raw_data[ $key ] : $form_data[ $key ];
			}

			$error = self::check_field( $field, $value, $context );
			if ( '' !== $error ) {
				$errors[ '' !== $key ? $key : 'srfm-' . $field['type'] . '-' . $field['block_id'] ] = $error;
			}
		}

		return $errors;
	}

	/**
	 * Collect the fields to check, with the attributes the renderer gives them.
	 *
	 * Mirrors Field_Validation::collect_field_block_ids(): recurses into inner
	 * blocks and expands reusable blocks, with a cycle guard. Repeater rows hold
	 * nested values SureForms Pro validates, so a repeater's children are left out.
	 * A field the form's conditional logic can hide, or one inside a container it
	 * can hide, is left out too: the browser submits it empty or stale and skips
	 * its checks (conditional-logic.js, skipHidden*Validation).
	 *
	 * @param array<mixed>                   $blocks  Parsed blocks.
	 * @param array<string,mixed>            $context Conditional block ids, multilingual state, ancestry.
	 * @phpstan-param RuleContext $context
	 * @param array<int,array<string,mixed>> $fields  Collected fields (by reference).
	 * @phpstan-param array<int, RuleField> &$fields
	 * @param array<int,true>                $visited Expanded reusable block ids (by reference).
	 * @param int                            $depth   Recursion depth.
	 * @since 2.13.1
	 * @return void
	 */
	private static function collect_fields( $blocks, $context, &$fields, &$visited, $depth = 0 ) {
		if ( ! is_array( $blocks ) || $depth > 50 ) {
			return;
		}

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$name     = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';
			$attrs    = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];
			$block_id = isset( $attrs['block_id'] ) && is_string( $attrs['block_id'] ) ? $attrs['block_id'] : '';

			// Block visibility (WordPress 6.9+) can keep a block, and everything in
			// it, off the page.
			$visibility = $attrs['metadata']['blockVisibility'] ?? true;

			if ( 'srfm/repeater' === $name || true !== $visibility || ( '' !== $block_id && isset( $context['hidden_ids'][ $block_id ] ) ) ) {
				continue;
			}

			if ( isset( self::FIELD_BLOCKS[ $name ] ) && '' !== $block_id ) {
				$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( $name );
				$fields[]   = [
					'type'       => self::FIELD_BLOCKS[ $name ],
					'block_id'   => $block_id,
					'slug'       => isset( $attrs['slug'] ) && is_string( $attrs['slug'] ) ? $attrs['slug'] : '',
					// The attributes the block renders with: block.json defaults applied the same way.
					'attrs'      => $block_type ? $block_type->prepare_attributes_for_render( $attrs ) : $attrs,
					'in_address' => ! empty( $context['in_address'] ),
				];
			}

			if ( 'core/block' === $name && ! empty( $attrs['ref'] ) && is_scalar( $attrs['ref'] ) ) {
				$ref = absint( $attrs['ref'] );
				if ( $ref > 0 && ! isset( $visited[ $ref ] ) ) {
					$visited[ $ref ] = true;
					$ref_post        = get_post( $ref );
					if ( $ref_post instanceof \WP_Post && 'wp_block' === $ref_post->post_type && '' !== $ref_post->post_content ) {
						self::collect_fields( parse_blocks( $ref_post->post_content ), $context, $fields, $visited, $depth + 1 );
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$inner_context = $context;
				if ( 'srfm/address' === $name ) {
					$inner_context['in_address'] = true;
				}
				self::collect_fields( $block['innerBlocks'], $inner_context, $fields, $visited, $depth + 1 );
			}
		}
	}

	/**
	 * The error for one field's value, or '' when it passes.
	 *
	 * @param array<string,mixed> $field   Field from collect_fields().
	 * @phpstan-param RuleField $field
	 * @param mixed               $value   Submitted value, '' when the field was not sent.
	 * @param array<string,mixed> $context Multilingual state.
	 * @phpstan-param RuleContext $context
	 * @since 2.13.1
	 * @return string
	 */
	private static function check_field( $field, $value, $context ) {
		// Every field covered here submits a single string. Anything else was not
		// sent by the rendered form.
		if ( ! is_string( $value ) ) {
			return __( 'Field is not valid.', 'sureforms' );
		}

		$attrs = $field['attrs'];

		// validation.js: `isRequired === 'true' && ! inputValue`, and for checkbox,
		// GDPR and multi-choice "nothing checked". A GDPR field is always required
		// (GDPR_Markup). An unticked checkbox posts nothing, so it arrives as ''.
		$required = 'gdpr' === $field['type'] || ! empty( $attrs['required'] );
		if ( '' === $value ) {
			// A choice field with no options is not rendered (SureForms Pro drops an
			// empty dynamic one), so the visitor had nothing to pick. A multi-choice
			// whose titles cannot be told apart submits '' even with a box ticked
			// (multi-choice.js), so its required check stays with the browser.
			if ( $required && in_array( $field['type'], [ 'dropdown', 'multi-choice' ], true ) && ( empty( self::rendered_options( $field ) ) || ( 'multi-choice' === $field['type'] && null === self::option_labels( $field ) ) ) ) {
				return '';
			}

			return $required ? self::required_message( $attrs ) : '';
		}

		switch ( $field['type'] ) {
			case 'input':
				// A mask or an address autocomplete sets the value by script, which
				// `maxlength` does not limit.
				$scripted = $field['in_address'] || ! in_array( $attrs['inputMask'] ?? 'none', [ '', 'none' ], true );
				return $scripted ? '' : self::check_length( $value, $attrs['textLength'] ?? 0, $attrs, $context );
			case 'textarea':
				// Rich text submits markup, whose length the browser does not limit.
				return empty( $attrs['isRichText'] ) ? self::check_length( $value, $attrs['maxLength'] ?? 0, $attrs, $context ) : '';
			case 'email':
				// validation.js checks the format on the input event, after trimming,
				// so a value the visitor never typed (the field's default) is not checked.
				return 1 === preg_match( self::EMAIL_PATTERN, trim( $value ) ) || self::is_rendered_default( $value, $attrs, $context ) ? '' : self::message( 'srfm_valid_email' );
			case 'number':
				return self::check_number( $value, $attrs );
			case 'dropdown':
				return self::check_choices( $value, $field, $context );
			case 'multi-choice':
				return self::check_choices( $value, $field, $context );
		}

		return '';
	}

	/**
	 * Check a value against the field's `maxlength`.
	 *
	 * The browser counts UTF-16 code units and a line break as one character,
	 * while the submitted value carries CRLF. Counting code points after folding
	 * CRLF never gives a larger count than the browser's, so a value the browser
	 * allowed is never too long here. `maxlength` only limits typing, so the
	 * field's rendered default is accepted whatever its length.
	 *
	 * A limit that is not a positive number is no limit, as the browser treats an
	 * invalid `maxlength`.
	 *
	 * @param string              $value   Submitted value.
	 * @param mixed               $max     textLength or maxLength attribute.
	 * @param array<string,mixed> $attrs   Field attributes.
	 * @param array<string,mixed> $context Multilingual state.
	 * @phpstan-param RuleContext $context
	 * @since 2.13.1
	 * @return string
	 */
	private static function check_length( $value, $max, $attrs, $context ) {
		$max = is_numeric( $max ) ? (int) $max : 0;
		if ( $max <= 0 ) {
			return '';
		}

		$text = str_replace( [ "\r\n", "\r" ], "\n", $value );
		if ( mb_strlen( $text ) <= $max || self::is_rendered_default( $text, $attrs, $context ) ) {
			return '';
		}

		/* translators: %s: maximum number of characters. */
		return sprintf( __( 'Please enter no more than %s characters.', 'sureforms' ), $max );
	}

	/**
	 * Whether a value can be the one the field rendered as its default, which the
	 * browser submits without checking it.
	 *
	 * A default holding a smart tag (`{get_input:…}`, `{user_email}`) is resolved
	 * when the form renders, from data this request cannot recreate, and a
	 * multilingual provider renders a translated default, so any value is accepted
	 * for such a field. A literal default is accepted when the value equals it, or
	 * equals it after the texturize pass the page's content filter runs over it.
	 *
	 * @param string              $value   Submitted value, line breaks folded to LF.
	 * @param array<string,mixed> $attrs   Field attributes.
	 * @param array<string,mixed> $context Multilingual state.
	 * @phpstan-param RuleContext $context
	 * @since 2.13.1
	 * @return bool
	 */
	private static function is_rendered_default( $value, $attrs, $context ) {
		$default = isset( $attrs['defaultValue'] ) && is_scalar( $attrs['defaultValue'] ) ? str_replace( [ "\r\n", "\r" ], "\n", (string) $attrs['defaultValue'] ) : '';
		if ( '' === $default ) {
			return false;
		}

		if ( false !== strpos( $default, '{' ) || ! empty( $context['multilingual'] ) ) {
			return true;
		}

		// The browser submits the texturized text decoded from its entities.
		$texturized = html_entity_decode( wptexturize( $default ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return in_array( trim( $value ), [ trim( $default ), trim( $texturized ) ], true );
	}

	/**
	 * Check a number field value.
	 *
	 * The number.js script removes every character but digits, `,`, `.` and `-` as the
	 * visitor types, and formats an overlong number as `∞`, so anything else was not
	 * typed into the field. The range check
	 * mirrors validation.js: the value is normalised for its format, read the way
	 * parseFloat() reads it (its leading number, so "-" alone is skipped as the
	 * browser skips it), and compared to the min/max Number_Markup renders, which
	 * are only rendered when not empty.
	 *
	 * @param string              $value Submitted value.
	 * @param array<string,mixed> $attrs Field attributes.
	 * @since 2.13.1
	 * @return string
	 */
	private static function check_number( $value, $attrs ) {
		$value = trim( $value );

		// A read-only or calculated value is set by the form, not typed.
		if ( empty( $attrs['readOnly'] ) && empty( $attrs['enableCalculation'] ) && 1 !== preg_match( '/^[0-9,.\-∞]+$/u', $value ) ) {
			return __( 'Enter a valid number.', 'sureforms' );
		}

		$normalized = 'eu-style' === ( $attrs['formatType'] ?? 'us-style' )
			? (string) preg_replace( '/,/', '.', str_replace( '.', '', $value ), 1 )
			: str_replace( ',', '', $value );

		if ( 1 !== preg_match( '/^[+-]?(\d+\.?\d*|\.\d+)([eE][+-]?\d+)?/', $normalized, $match ) ) {
			return '';
		}

		$number = (float) $match[0];
		$min    = $attrs['minValue'] ?? '';
		$max    = $attrs['maxValue'] ?? '';

		if ( ! empty( $min ) && is_numeric( $min ) && $number < (float) $min ) {
			return sprintf( self::message( 'srfm_input_min_value' ), $min );
		}
		if ( ! empty( $max ) && is_numeric( $max ) && $number > (float) $max ) {
			return sprintf( self::message( 'srfm_input_max_value' ), $max );
		}

		return '';
	}

	/**
	 * Check a dropdown or multi-choice value: its selection count, then that every
	 * selected value is one of the field's rendered options.
	 *
	 * Selection limits mirror validation.js. A dropdown applies them only when it
	 * is multi-select; a multi-choice only when it is not single-selection, and a
	 * required one only enforces a minimum above 1.
	 *
	 * Options are the field's own, passed through the same srfm_field_options
	 * filter the field renders with (Fields\Base), so options another plugin
	 * supplies at render time are what a value is checked against. A dropdown
	 * submits its rendered label, which a multilingual provider translates, so
	 * dropdown options are not checked while one is active. A multi-choice submits
	 * its untranslated title. Options are not checked inside an address, whose
	 * fields an autocomplete can fill, nor when option_labels() cannot map a value
	 * back to the options.
	 *
	 * @param string              $value   Submitted value.
	 * @param array<string,mixed> $field   Field from collect_fields().
	 * @phpstan-param RuleField $field
	 * @param array<string,mixed> $context Multilingual state.
	 * @phpstan-param RuleContext $context
	 * @since 2.13.1
	 * @return string
	 */
	private static function check_choices( $value, $field, $context ) {
		$attrs       = $field['attrs'];
		$is_dropdown = 'dropdown' === $field['type'];
		$is_multiple = $is_dropdown ? ! empty( $attrs['multiSelect'] ) : empty( $attrs['singleSelection'] );
		$selected    = $is_multiple ? explode( self::VALUE_SEPARATOR, $value ) : [ $value ];
		$count       = count( $selected );
		$min         = is_numeric( $attrs['minValue'] ?? '' ) ? absint( $attrs['minValue'] ) : 0;
		$max         = is_numeric( $attrs['maxValue'] ?? '' ) ? absint( $attrs['maxValue'] ) : 0;
		$prefix      = $is_dropdown ? 'srfm_dropdown' : 'srfm_multi_choice';

		// A multi-choice value is built from its titles; when they cannot be told
		// apart it does not say how many boxes are ticked.
		if ( ! $is_dropdown && null === self::option_labels( $field ) ) {
			return '';
		}

		if ( $is_multiple ) {
			$min_applies = $is_dropdown || empty( $attrs['required'] ) || $min > 1;
			if ( $min > 0 && $min_applies && $count < $min ) {
				return sprintf( self::message( $prefix . '_min_selections' ), $min );
			}
			if ( $max > 0 && $count > $max ) {
				return sprintf( self::message( $prefix . '_max_selections' ), $max );
			}
		}

		if ( $field['in_address'] || ( $is_dropdown && ! empty( $context['multilingual'] ) ) ) {
			return '';
		}

		$labels = self::option_labels( $field );
		if ( empty( $labels ) ) {
			return '';
		}

		foreach ( $selected as $choice ) {
			if ( ! isset( $labels[ self::comparable( $choice ) ] ) ) {
				return __( 'Select a valid option.', 'sureforms' );
			}
		}

		return '';
	}

	/**
	 * The comparable labels a choice field's value is built from, or null when a
	 * value cannot be mapped back to them.
	 *
	 * Null when a label is empty or repeated (multi-choice.js then cannot record
	 * which box is ticked), holds the value separator, or holds a smart tag, which
	 * the page's render resolves into text this request does not see.
	 *
	 * @param array<string,mixed> $field Field from collect_fields().
	 * @phpstan-param RuleField $field
	 * @since 2.13.1
	 * @return array<string,true>|null
	 */
	private static function option_labels( $field ) {
		$labels = [];
		foreach ( self::rendered_options( $field ) as $option ) {
			$label = is_array( $option ) ? ( $option[ 'dropdown' === $field['type'] ? 'label' : 'optionTitle' ] ?? '' ) : '';
			$label = is_scalar( $label ) ? self::comparable( (string) $label ) : '';
			if ( '' === $label || isset( $labels[ $label ] ) || false !== strpos( $label, trim( self::VALUE_SEPARATOR ) ) || false !== strpos( $label, '{' ) ) {
				return null;
			}
			$labels[ $label ] = true;
		}

		return $labels;
	}

	/**
	 * A choice field's options as it renders them: through the srfm_field_options
	 * filter with the same arguments Fields\Base passes, so options another plugin
	 * supplies at render time (SureForms Pro's dynamic options) are included.
	 *
	 * @param array<string,mixed> $field Field from collect_fields().
	 * @phpstan-param RuleField $field
	 * @since 2.13.1
	 * @return array<int|string,mixed>
	 */
	private static function rendered_options( $field ) {
		$attrs   = $field['attrs'];
		$options = apply_filters( 'srfm_field_options', $attrs['options'] ?? '', array_merge( $attrs, [ 'block_args' => [ 'slug' => 'dropdown' === $field['type'] ? 'dropdown' : 'multi-choice' ] ] ) );

		return is_array( $options ) ? $options : [];
	}

	/**
	 * An option label or submitted choice in the form both are compared in.
	 *
	 * A label is stored with or without entities depending on who saved the form
	 * (kses encodes `&` for a user without unfiltered_html), and the browser
	 * submits it exactly as stored. Decoding both sides, then sanitizing them the
	 * same way, matches either shape.
	 *
	 * @param string $text Label or choice.
	 * @since 2.13.1
	 * @return string
	 */
	private static function comparable( $text ) {
		return trim( sanitize_text_field( html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	}

	/**
	 * Block ids the form's conditional logic shows or hides.
	 *
	 * Read the way SureForms Pro reads the meta when it marks a block with the
	 * `conditional-logic` class (Conditional_Logic::add_conditional_classes()):
	 * every entry that sets an action.
	 *
	 * @param int $form_id Form id.
	 * @since 2.13.1
	 * @return array<string,true>
	 */
	private static function get_conditional_block_ids( $form_id ) {
		$ids = [];
		foreach ( (array) get_post_meta( $form_id, '_srfm_conditional_logic' ) as $meta ) {
			foreach ( is_array( $meta ) ? $meta : [] as $item ) {
				foreach ( is_array( $item ) ? $item : [] as $block_id => $rule ) {
					if ( is_array( $rule ) && isset( $rule['action'] ) ) {
						$ids[ (string) $block_id ] = true;
					}
				}
			}
		}

		return $ids;
	}

	/**
	 * Map the submitted field keys by block id and by slug.
	 *
	 * The slug finds a field whose block id drifted under a full-page cache, as
	 * Field_Validation::strip_unknown_field_keys() does.
	 *
	 * @param array<mixed> $form_data Submitted fields.
	 * @since 2.13.1
	 * @return array{ids:array<string,string>,slugs:array<string,string>}
	 */
	private static function index_submitted_keys( $form_data ) {
		$index = [
			'ids'   => [],
			'slugs' => [],
		];
		foreach ( array_keys( $form_data ) as $key ) {
			if ( ! is_string( $key ) || false === strpos( $key, '-lbl-' ) ) {
				continue;
			}
			$index['ids'][ Helper::get_block_id_from_key( $key ) ]         = $key;
			$index['slugs'][ Field_Validation::get_slug_from_key( $key ) ] = $key;
		}

		return $index;
	}

	/**
	 * The field's required message: its own, else the site-wide one.
	 *
	 * @param array<string,mixed> $attrs Field attributes.
	 * @since 2.13.1
	 * @return string
	 */
	private static function required_message( $attrs ) {
		$own = isset( $attrs['errorMsg'] ) && is_string( $attrs['errorMsg'] ) ? trim( $attrs['errorMsg'] ) : '';
		if ( '' !== $own ) {
			return $own;
		}

		$common = Helper::get_common_err_msg()['required'] ?? '';

		return is_string( $common ) && '' !== trim( $common ) ? $common : __( 'This field is required.', 'sureforms' );
	}

	/**
	 * A validation message the browser also shows.
	 *
	 * Never empty: validate() reads an empty message as "no error", so a message
	 * removed by a filter or left blank in the settings falls back to the built-in
	 * text.
	 *
	 * @param string $key Translatable::dynamic_messages() key.
	 * @since 2.13.1
	 * @return string
	 */
	private static function message( $key ) {
		foreach ( [ Translatable::dynamic_validation_messages(), Translatable::dynamic_messages() ] as $messages ) {
			if ( isset( $messages[ $key ] ) && is_string( $messages[ $key ] ) && '' !== trim( $messages[ $key ] ) ) {
				return $messages[ $key ];
			}
		}

		return __( 'Field is not valid.', 'sureforms' );
	}
}
