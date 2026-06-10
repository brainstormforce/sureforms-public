<?php
/**
 * Block Templates — emits Gutenberg block markup for each SureForms field block.
 *
 * Acts as the canonical destination dictionary for the form migrator. Each
 * `template_*()` method returns a serialized Gutenberg block string that can
 * be concatenated into a SureForms form's `post_content`.
 *
 * Attribute schemas mirror `src/blocks/<block>/block.json` — only attributes
 * different from the block's defaults are emitted, to keep imported forms
 * indistinguishable from hand-built ones in the editor.
 *
 * @package sureforms
 * @since   2.11.0
 */

namespace SRFM\Inc\Migrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block_Templates
 *
 * @since 2.11.0
 */
class Block_Templates {
	/**
	 * Build a srfm/input block (text / email-as-text / password / date / hidden).
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args Field args (label, placeholder, required, fieldType, etc.).
	 * @return string Serialized Gutenberg block markup.
	 */
	public static function input( array $args ) {
		$attrs = self::strip_empty(
			[
				'label'        => self::str( $args, 'label', 'Text Field' ),
				'placeholder'  => self::str( $args, 'placeholder' ),
				'defaultValue' => self::str( $args, 'default_value' ),
				'required'     => self::bool( $args, 'required' ),
				'help'         => self::str( $args, 'help' ),
				'errorMsg'     => self::str( $args, 'error_message' ),
				'textLength'   => self::int_or_null( $args, 'max_length' ),
				'inputMask'    => self::str( $args, 'input_mask', 'none' ),
				'slug'         => self::slug_from_args( $args, 'text-field' ),
				'block_id'     => self::block_id(),
			]
		);
		return self::serialize_block( 'srfm/input', $attrs );
	}

	/**
	 * Build a srfm/email block. When `confirm_email` is set, the block's
	 * native confirm-email mode (`isConfirmEmail`) is enabled — a second
	 * "Confirm Email" input on the same field — instead of the caller having
	 * to emit a separate email block.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args Field args (incl. `confirm_email`,
	 *                                   `confirm_label`).
	 * @return string
	 */
	public static function email( array $args ) {
		$attrs = self::strip_empty(
			[
				'label'        => self::str( $args, 'label', 'Email' ),
				'placeholder'  => self::str( $args, 'placeholder' ),
				'defaultValue' => self::str( $args, 'default_value' ),
				'required'     => self::bool( $args, 'required' ),
				'help'         => self::str( $args, 'help' ),
				'errorMsg'     => self::str( $args, 'error_message' ),
				'slug'         => self::slug_from_args( $args, 'email' ),
				'block_id'     => self::block_id(),
			]
		);
		if ( self::bool( $args, 'confirm_email' ) ) {
			$attrs['isConfirmEmail'] = true;
			$confirm_label           = self::str( $args, 'confirm_label' );
			if ( '' !== $confirm_label ) {
				$attrs['confirmLabel'] = $confirm_label;
			}
		}
		return self::serialize_block( 'srfm/email', $attrs );
	}

	/**
	 * Build a srfm/url block.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args Field args.
	 * @return string
	 */
	public static function url( array $args ) {
		$attrs = self::strip_empty(
			[
				'label'        => self::str( $args, 'label', 'URL' ),
				'placeholder'  => self::str( $args, 'placeholder' ),
				'defaultValue' => self::str( $args, 'default_value' ),
				'required'     => self::bool( $args, 'required' ),
				'help'         => self::str( $args, 'help' ),
				'errorMsg'     => self::str( $args, 'error_message' ),
				'slug'         => self::slug_from_args( $args, 'url' ),
				'block_id'     => self::block_id(),
			]
		);
		return self::serialize_block( 'srfm/url', $attrs );
	}

	/**
	 * Build a srfm/phone block.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args Field args.
	 * @return string
	 */
	public static function phone( array $args ) {
		$attrs = self::strip_empty(
			[
				'label'       => self::str( $args, 'label', 'Phone Number' ),
				'placeholder' => self::str( $args, 'placeholder' ),
				'required'    => self::bool( $args, 'required' ),
				'help'        => self::str( $args, 'help' ),
				'errorMsg'    => self::str( $args, 'error_message' ),
				'slug'        => self::slug_from_args( $args, 'phone' ),
				'block_id'    => self::block_id(),
			]
		);
		return self::serialize_block( 'srfm/phone', $attrs );
	}

	/**
	 * Build a srfm/number block. Handles range/slider via min/max args.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args Field args.
	 * @return string
	 */
	public static function number( array $args ) {
		$attrs = self::strip_empty(
			[
				'label'        => self::str( $args, 'label', 'Number' ),
				'placeholder'  => self::str( $args, 'placeholder' ),
				'defaultValue' => self::str( $args, 'default_value' ),
				'required'     => self::bool( $args, 'required' ),
				'help'         => self::str( $args, 'help' ),
				'errorMsg'     => self::str( $args, 'error_message' ),
				'minValue'     => self::int_or_null( $args, 'min' ),
				'maxValue'     => self::int_or_null( $args, 'max' ),
				'prefix'       => self::str( $args, 'prefix' ),
				'suffix'       => self::str( $args, 'suffix' ),
				'slug'         => self::slug_from_args( $args, 'number' ),
				'block_id'     => self::block_id(),
			]
		);
		return self::serialize_block( 'srfm/number', $attrs );
	}

	/**
	 * Build a srfm/textarea block.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args Field args.
	 * @return string
	 */
	public static function textarea( array $args ) {
		$attrs = self::strip_empty(
			[
				'label'        => self::str( $args, 'label', 'Textarea' ),
				'placeholder'  => self::str( $args, 'placeholder' ),
				'defaultValue' => self::str( $args, 'default_value' ),
				'required'     => self::bool( $args, 'required' ),
				'help'         => self::str( $args, 'help' ),
				'errorMsg'     => self::str( $args, 'error_message' ),
				'minLength'    => self::int_or_null( $args, 'min_length' ),
				'maxLength'    => self::int_or_null( $args, 'max_length' ),
				'rows'         => self::int_or_null( $args, 'rows', 4 ),
				'slug'         => self::slug_from_args( $args, 'textarea' ),
				'block_id'     => self::block_id(),
			]
		);
		return self::serialize_block( 'srfm/textarea', $attrs );
	}

	/**
	 * Build a srfm/dropdown (single-select or multi-select) block.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args Field args. Expects `options` => list of strings.
	 * @return string
	 */
	public static function dropdown( array $args ) {
		$options = self::format_options( $args, 'label' );
		$attrs   = self::strip_empty(
			[
				'label'              => self::str( $args, 'label', 'Dropdown' ),
				'placeholder'        => self::str( $args, 'placeholder' ),
				'required'           => self::bool( $args, 'required' ),
				'multiSelect'        => self::bool( $args, 'multiple' ),
				'help'               => self::str( $args, 'help' ),
				'errorMsg'           => self::str( $args, 'error_message' ),
				'options'            => $options,
				'preselectedOptions' => self::array_or_null( $args, 'preselected' ),
				'slug'               => self::slug_from_args( $args, 'dropdown' ),
				'block_id'           => self::block_id(),
			]
		);
		return self::serialize_block( 'srfm/dropdown', $attrs );
	}

	/**
	 * Build a srfm/multi-choice (radio / multiple-choice) block.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args Field args. Expects `options` => list of strings.
	 * @return string
	 */
	public static function multi_choice( array $args ) {
		$options = self::format_options( $args, 'optionTitle' );
		$attrs   = self::strip_empty(
			[
				'label'              => self::str( $args, 'label', 'Multi Choice' ),
				'required'           => self::bool( $args, 'required' ),
				'singleSelection'    => ! self::bool( $args, 'multiple' ),
				'help'               => self::str( $args, 'help' ),
				'errorMsg'           => self::str( $args, 'error_message' ),
				'options'            => $options,
				'preselectedOptions' => self::array_or_null( $args, 'preselected' ),
				'slug'               => self::slug_from_args( $args, 'multi-choice' ),
				'block_id'           => self::block_id(),
			]
		);
		return self::serialize_block( 'srfm/multi-choice', $attrs );
	}

	/**
	 * Build a srfm/checkbox block.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args Field args.
	 * @return string
	 */
	public static function checkbox( array $args ) {
		$attrs = self::strip_empty(
			[
				'label'    => self::str( $args, 'label', 'Checkbox' ),
				'required' => self::bool( $args, 'required' ),
				'checked'  => self::bool( $args, 'checked' ),
				'help'     => self::str( $args, 'help' ),
				'errorMsg' => self::str( $args, 'error_message' ),
				'slug'     => self::slug_from_args( $args, 'checkbox' ),
				'block_id' => self::block_id(),
			]
		);
		return self::serialize_block( 'srfm/checkbox', $attrs );
	}

	/**
	 * Build a srfm/gdpr block.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args Field args. Expects `label` (consent text).
	 * @return string
	 */
	public static function gdpr( array $args ) {
		$attrs = self::strip_empty(
			[
				'label'    => self::str( $args, 'label', 'I consent to have this website store my submitted information so they can respond to my inquiry.' ),
				'checked'  => self::bool( $args, 'checked' ),
				'help'     => self::str( $args, 'help' ),
				'errorMsg' => self::str( $args, 'error_message' ),
				'slug'     => self::slug_from_args( $args, 'consent' ),
				'block_id' => self::block_id(),
			]
		);
		return self::serialize_block( 'srfm/gdpr', $attrs );
	}

	/**
	 * Generate a deterministic Gutenberg-style block id.
	 *
	 * @since 2.11.0
	 *
	 * @return string An 8-character lowercase hex slug.
	 */
	public static function block_id() {
		return substr( md5( wp_generate_uuid4() ), 0, 8 );
	}

	/**
	 * Serialize a self-closing Gutenberg block with the given name and attributes.
	 *
	 * @since 2.11.0
	 *
	 * @param string              $name  Block name (e.g. 'srfm/input').
	 * @param array<string,mixed> $attrs Block attributes.
	 * @return string Block markup.
	 */
	private static function serialize_block( $name, array $attrs ) {
		$json = self::encode_attrs( $attrs );
		return "<!-- wp:{$name} {$json} /-->\n";
	}

	/**
	 * JSON-encode block attributes for Gutenberg comment delimiter.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $attrs Attributes to encode.
	 * @return string JSON string, or '{}' if empty.
	 */
	private static function encode_attrs( array $attrs ) {
		if ( empty( $attrs ) ) {
			return '{}';
		}
		// JSON_HEX_TAG/AMP/APOS/QUOT escape <, >, &, ', " as < etc. so attacker-controlled
		// strings (e.g. a CF7 label containing `-->`) cannot terminate the surrounding HTML block
		// comment delimiter and inject markup. Mirrors what core's serialize_block() does.
		$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
		$json  = wp_json_encode( $attrs, $flags );
		return is_string( $json ) ? $json : '{}';
	}

	/**
	 * Drop keys whose value is null, empty string, or empty array.
	 *
	 * Keeps zero-valued numbers and `false` booleans, which are meaningful.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $arr Attribute array.
	 * @return array<string,mixed>
	 */
	private static function strip_empty( array $arr ) {
		return array_filter(
			$arr,
			static function ( $v ) {
				if ( null === $v ) {
					return false;
				}
				if ( '' === $v ) {
					return false;
				}
				if ( is_array( $v ) && empty( $v ) ) {
					return false;
				}
				return true;
			}
		);
	}

	/**
	 * Fetch a string from args with a default.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args    Args.
	 * @param string              $key     Key to fetch.
	 * @param string              $default Default if missing/empty.
	 * @return string
	 */
	private static function str( array $args, $key, $default = '' ) {
		if ( ! isset( $args[ $key ] ) ) {
			return $default;
		}
		$v = $args[ $key ];
		if ( ! is_string( $v ) && ! is_numeric( $v ) ) {
			return $default;
		}
		return (string) $v;
	}

	/**
	 * Fetch a boolean from args.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args Args.
	 * @param string              $key  Key.
	 * @return bool
	 */
	private static function bool( array $args, $key ) {
		return ! empty( $args[ $key ] );
	}

	/**
	 * Fetch an integer or return null if missing.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args    Args.
	 * @param string              $key     Key.
	 * @param int|null            $default Default if missing.
	 * @return int|null
	 */
	private static function int_or_null( array $args, $key, $default = null ) {
		if ( ! isset( $args[ $key ] ) || '' === $args[ $key ] ) {
			return $default;
		}
		if ( ! is_numeric( $args[ $key ] ) ) {
			return $default;
		}
		return (int) $args[ $key ];
	}

	/**
	 * Fetch an array or return null if missing.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args Args.
	 * @param string              $key  Key.
	 * @return array<int,mixed>|null
	 */
	private static function array_or_null( array $args, $key ) {
		if ( ! isset( $args[ $key ] ) || ! is_array( $args[ $key ] ) || empty( $args[ $key ] ) ) {
			return null;
		}
		return array_values( $args[ $key ] );
	}

	/**
	 * Convert a label string to a URL-safe slug, lowercased.
	 *
	 * @since 2.11.0
	 *
	 * @param string $label Source label.
	 * @return string Slug — falls back to 'field' if empty.
	 */
	private static function slugify( $label ) {
		$slug = sanitize_title( $label );
		return $slug ? $slug : 'field';
	}

	/**
	 * Resolve the block slug from `$args`, honoring an explicit `slug` override
	 * (used by importers that need to dedupe collisions across the form).
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args     Block args.
	 * @param string              $fallback Slug fallback when label is empty.
	 * @return string
	 */
	private static function slug_from_args( array $args, $fallback ) {
		$override = self::str( $args, 'slug' );
		if ( '' !== $override ) {
			return $override;
		}
		return self::slugify( self::str( $args, 'label', $fallback ) );
	}

	/**
	 * Format a list of source-plugin choices into SureForms dropdown/multi-choice
	 * option shape.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,mixed> $args     Field args containing 'options'.
	 * @param string              $label_key Either 'label' (dropdown) or 'optionTitle' (multi-choice).
	 * @return array<int,array<string,string>>
	 */
	private static function format_options( array $args, $label_key ) {
		if ( empty( $args['options'] ) || ! is_array( $args['options'] ) ) {
			return [
				[ $label_key => 'Option 1' ],
			];
		}
		$out = [];
		foreach ( $args['options'] as $opt ) {
			if ( is_string( $opt ) ) {
				$out[] = [ $label_key => $opt ];
				continue;
			}
			if ( is_array( $opt ) && isset( $opt['label'] ) ) {
				$out[] = [ $label_key => (string) $opt['label'] ];
			}
		}
		return empty( $out ) ? [ [ $label_key => 'Option 1' ] ] : $out;
	}
}
