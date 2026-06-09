<?php
/**
 * Multilingual String Translator.
 *
 * Provides convenient, name-scheme-aware helpers for translating SureForms'
 * form-level metadata strings through the active multilingual provider.
 *
 * @package sureforms.
 * @since 2.11.0
 */

namespace SRFM\Inc\Compatibility\Multilingual;

use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * String_Translator.
 *
 * Bridges SureForms render paths to the multilingual provider using a stable naming
 * scheme that matches the strings registered on save by the string-collector.
 *
 * Per-form strings are translated through a WPML **String Package** (one package
 * per form, see {@see form_package()}), so they appear grouped under the form in the
 * Translation Editor instead of as flat global strings. Package-scoped string names
 * (no `form_{id}_` prefix — the package is the form):
 *  - Submit button text:       `submit_button`
 *  - Confirmation message:     `confirmation_{N}_message`
 *  - Notification field:       `notification_{N}_{subject|body|from_name|reply_to}`
 *  - Form restriction message: `restriction_message`
 *  - Block string attribute:   `block_{block_id}_{attribute}`
 *  - Block option label:       `block_{block_id}_option_{N}_label`
 *
 * Built-in validation messages stay flat global strings (domain `sureforms`,
 * `validation_{key}`) since they are not per-form. When the provider can't do
 * packages, per-form strings fall back to the legacy flat name `form_{id}_{name}`.
 *
 * @since 2.11.0
 */
class String_Translator {
	use Get_Instance;

	/**
	 * Translation domain used for every SureForms string.
	 *
	 * @since 2.11.0
	 */
	public const DOMAIN = 'sureforms';

	/**
	 * WPML "kind" label for SureForms form string packages.
	 *
	 * @since 2.11.0
	 */
	public const PACKAGE_KIND = 'SureForms Form';

	/**
	 * Map of SureForms block name → translatable scalar attribute keys.
	 *
	 * Keys correspond to the `name` field in each block's `block.json`. Values
	 * are the attribute keys whose `string` value is shown to the visitor and
	 * therefore needs to be registered with WPML and translated at render time.
	 *
	 * Blocks that store option lists (dropdown, multi-choice) declare ONLY their
	 * scalar attributes here; their `options[*].label` strings are walked
	 * separately by {@see translatable_option_blocks()}.
	 *
	 * @since 2.11.0
	 * @return array<string, array<int, string>>
	 */
	public static function translatable_block_attributes(): array {
		$map = [
			'srfm/input'         => [ 'label', 'placeholder', 'help', 'errorMsg', 'defaultValue', 'duplicateMsg' ],
			'srfm/email'         => [ 'label', 'placeholder', 'help', 'errorMsg', 'defaultValue', 'duplicateMsg', 'confirmLabel' ],
			'srfm/phone'         => [ 'label', 'placeholder', 'help', 'errorMsg', 'duplicateMsg' ],
			'srfm/number'        => [ 'label', 'placeholder', 'help', 'errorMsg', 'defaultValue', 'prefix', 'suffix' ],
			'srfm/url'           => [ 'label', 'placeholder', 'help', 'errorMsg', 'defaultValue' ],
			'srfm/textarea'      => [ 'label', 'placeholder', 'help', 'errorMsg', 'defaultValue' ],
			'srfm/address'       => [ 'label', 'help' ],
			'srfm/dropdown'      => [ 'label', 'placeholder', 'help', 'errorMsg' ],
			'srfm/multi-choice'  => [ 'label', 'help', 'errorMsg' ],
			'srfm/checkbox'      => [ 'label', 'help', 'errorMsg' ],
			'srfm/gdpr'          => [ 'label', 'help', 'errorMsg' ],
			'srfm/inline-button' => [ 'buttonText' ],
			'srfm/payment'       => [ 'label', 'help', 'errorMsg', 'amountLabel', 'paymentDescription', 'oneTimeLabel', 'subscriptionLabel' ],
		];

		/**
		 * Filters the map of block name → translatable scalar attribute keys.
		 *
		 * Both the string-collector (registration on save) and the string-translator
		 * (translation at render) read this method, so a single filter keeps the two
		 * sides in lockstep. Pro field blocks register their translatable attributes here.
		 *
		 * @since 2.11.0
		 * @param array<string, array<int, string>> $map Block name → attribute keys.
		 */
		$filtered = apply_filters( 'srfm_translatable_block_attributes', $map );

		return is_array( $filtered ) ? $filtered : $map;
	}

	/**
	 * Block names whose `options` array contains translatable `label` entries.
	 *
	 * @since 2.11.0
	 * @return array<int, string>
	 */
	public static function translatable_option_blocks(): array {
		$blocks = [ 'srfm/dropdown', 'srfm/multi-choice' ];

		/**
		 * Filters the list of block names whose `options[*].label` entries are translatable.
		 *
		 * Read by both the string-collector (registration) and the string-translator
		 * (translation), so a single filter keeps the two sides in lockstep. Pro
		 * option-bearing blocks opt into option-label translation here.
		 *
		 * @since 2.11.0
		 * @param array<int, string> $blocks Block names with translatable option labels.
		 */
		$filtered = apply_filters( 'srfm_translatable_option_blocks', $blocks );

		return is_array( $filtered ) ? $filtered : $blocks;
	}

	/**
	 * Human-readable field-type label for a SureForms block, used to group a
	 * field's strings in the Translation Editor (e.g. the "Email #2" heading).
	 *
	 * Deliberately uses the block TYPE rather than the user-entered field label:
	 * WPML builds the Translation-Editor tree by splitting the string title on
	 * `/` (nesting) and `: ` (path vs. leaf), so a user who types either sequence
	 * into a field label would otherwise corrupt the grouping. Block types are
	 * plugin-controlled and safe.
	 *
	 * @since 2.11.0
	 * @param string $block_name Full block name, e.g. `srfm/email`.
	 * @return string Friendly type label, e.g. `Email`.
	 */
	public static function block_type_label( string $block_name ): string {
		$labels = [
			'srfm/input'         => __( 'Text', 'sureforms' ),
			'srfm/email'         => __( 'Email', 'sureforms' ),
			'srfm/phone'         => __( 'Phone', 'sureforms' ),
			'srfm/number'        => __( 'Number', 'sureforms' ),
			'srfm/url'           => __( 'URL', 'sureforms' ),
			'srfm/textarea'      => __( 'Textarea', 'sureforms' ),
			'srfm/address'       => __( 'Address', 'sureforms' ),
			'srfm/dropdown'      => __( 'Dropdown', 'sureforms' ),
			'srfm/multi-choice'  => __( 'Multiple Choice', 'sureforms' ),
			'srfm/checkbox'      => __( 'Checkbox', 'sureforms' ),
			'srfm/gdpr'          => __( 'GDPR', 'sureforms' ),
			'srfm/inline-button' => __( 'Button', 'sureforms' ),
			'srfm/payment'       => __( 'Payment', 'sureforms' ),
		];

		/**
		 * Filters the block-name → field-type label map used to group a field's
		 * strings in the Translation Editor. Pro field blocks add their labels here.
		 *
		 * @since 2.11.0
		 * @param array<string, string> $labels Block name → friendly type label.
		 */
		$labels = apply_filters( 'srfm_block_type_labels', $labels );

		if ( isset( $labels[ $block_name ] ) && is_string( $labels[ $block_name ] ) ) {
			return $labels[ $block_name ];
		}

		// Derive from the block name: strip the namespace, turn dashes into spaces
		// and title-case (e.g. `srfm/date-time-picker` → `Date Time Picker`).
		$slug = false !== strpos( $block_name, '/' ) ? substr( $block_name, strpos( $block_name, '/' ) + 1 ) : $block_name;
		$slug = str_replace( '-', ' ', $slug );
		return '' !== $slug ? ucwords( $slug ) : __( 'Field', 'sureforms' );
	}

	/**
	 * Build the string-package descriptor for a form.
	 *
	 * One package per form (named by post ID) so every form string surfaces
	 * together under the form in the multilingual plugin's Translation Editor,
	 * instead of as flat, global String-Translation entries. The collector
	 * (registration) and the translator (render) both call this, keeping the two
	 * sides in lockstep.
	 *
	 * @param int $form_id Form post ID.
	 * @since 2.11.0
	 * @return array<string,string> { kind, name, title, edit_link }.
	 */
	public static function form_package( int $form_id ): array {
		$title = get_the_title( $form_id );
		if ( '' === $title ) {
			/* translators: %d is the form ID. */
			$title = sprintf( __( 'SureForms Form #%d', 'sureforms' ), $form_id );
		}

		$edit_link = get_edit_post_link( $form_id, 'raw' );

		return [
			'kind'      => self::PACKAGE_KIND,
			'name'      => (string) $form_id,
			'title'     => $title,
			'edit_link' => is_string( $edit_link ) ? $edit_link : '',
		];
	}

	/**
	 * Package-scoped submit-button string name. Names are unique within a form's
	 * package (which is itself scoped to the form), so they carry no `form_{id}_`
	 * prefix; the legacy flat-string fallback re-adds it in {@see dispatch_package()}.
	 *
	 * @since 2.11.0
	 * @return string
	 */
	public static function submit_button_name(): string {
		return 'submit_button';
	}

	/**
	 * Package-scoped confirmation-message string name.
	 *
	 * @param int $index Confirmation index.
	 * @since 2.11.0
	 * @return string
	 */
	public static function confirmation_name( int $index ): string {
		return 'confirmation_' . $index . '_message';
	}

	/**
	 * Package-scoped notification-field string name.
	 *
	 * @param int    $index Notification index.
	 * @param string $field Notification field (subject|body|from_name|reply_to).
	 * @since 2.11.0
	 * @return string
	 */
	public static function notification_name( int $index, string $field ): string {
		return 'notification_' . $index . '_' . $field;
	}

	/**
	 * Package-scoped restriction-message string name.
	 *
	 * @since 2.11.0
	 * @return string
	 */
	public static function restriction_name(): string {
		return 'restriction_message';
	}

	/**
	 * Package-scoped block-attribute string name.
	 *
	 * @param string $block_id  Stable block identifier.
	 * @param string $attribute Attribute key.
	 * @since 2.11.0
	 * @return string
	 */
	public static function block_attribute_name( string $block_id, string $attribute ): string {
		return 'block_' . $block_id . '_' . $attribute;
	}

	/**
	 * Package-scoped block-option-label string name.
	 *
	 * @param string $block_id     Stable block identifier.
	 * @param int    $option_index Option index.
	 * @since 2.11.0
	 * @return string
	 */
	public static function block_option_name( string $block_id, int $option_index ): string {
		return 'block_' . $block_id . '_option_' . $option_index . '_label';
	}

	/**
	 * Translate a form's submit button text.
	 *
	 * @param int    $form_id Form post ID.
	 * @param string $value   Original value (used as fallback when no translation exists).
	 * @since 2.11.0
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_submit_button( int $form_id, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		return $this->dispatch_package( $form_id, self::submit_button_name(), $value );
	}

	/**
	 * Translate a per-confirmation message.
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the confirmation within the form's confirmation set.
	 * @param string $value   Original value.
	 * @since 2.11.0
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_confirmation_message( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		return $this->dispatch_package( $form_id, self::confirmation_name( $index ), $value );
	}

	/**
	 * Translate an email notification subject.
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the notification within the form's notification set.
	 * @param string $value   Original value.
	 * @since 2.11.0
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_notification_subject( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		return $this->dispatch_package( $form_id, self::notification_name( $index, 'subject' ), $value );
	}

	/**
	 * Translate an email notification body.
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the notification within the form's notification set.
	 * @param string $value   Original value.
	 * @since 2.11.0
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_notification_body( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		return $this->dispatch_package( $form_id, self::notification_name( $index, 'body' ), $value );
	}

	/**
	 * Translate an email notification "from name".
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the notification within the form's notification set.
	 * @param string $value   Original value.
	 * @since 2.11.0
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_notification_from_name( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		return $this->dispatch_package( $form_id, self::notification_name( $index, 'from_name' ), $value );
	}

	/**
	 * Translate a single built-in validation message keyed by its srfm_* identifier.
	 *
	 * Falls back to the supplied `$value` (which is typically already the
	 * WP-locale-translated output of {@see Translatable::dynamic_messages()}),
	 * so existing `.mo`-based translations keep working when the active
	 * multilingual provider has no translation for the string.
	 *
	 * @param string $key   Stable identifier (e.g., `srfm_valid_email`).
	 * @param string $value Already-i18n'd value to use as fallback.
	 * @since 2.11.0
	 * @return string Translated value, or `$value` when the provider has no
	 *                better translation.
	 */
	public function translate_validation_message( string $key, string $value ): string {
		if ( '' === $key || '' === $value ) {
			return $value;
		}

		$name = 'validation_' . $key;
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate an associative array of validation messages in one shot.
	 *
	 * Convenience wrapper around {@see translate_validation_message()} for the
	 * frontend-localize call site. Preserves the original array's keys and
	 * leaves the structure intact when the provider is inactive.
	 *
	 * @param array<string, string> $messages Map of `srfm_*` keys to message values.
	 * @since 2.11.0
	 * @return array<string, string> Map with translated values.
	 */
	public function translate_validation_messages( array $messages ): array {
		$out = [];
		foreach ( $messages as $key => $value ) {
			if ( ! is_string( $key ) || ! is_string( $value ) ) {
				$out[ $key ] = $value;
				continue;
			}
			$out[ $key ] = $this->translate_validation_message( $key, $value );
		}
		return $out;
	}

	/**
	 * Translate the form restriction message.
	 *
	 * @param int    $form_id Form post ID.
	 * @param string $value   Original value.
	 * @since 2.11.0
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_restriction_message( int $form_id, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		return $this->dispatch_package( $form_id, self::restriction_name(), $value );
	}

	/**
	 * Translate a scalar block attribute (label, placeholder, help, errorMsg, etc).
	 *
	 * @param int    $form_id   Form post ID.
	 * @param string $block_id  Stable block identifier from the block's `block_id` attribute.
	 * @param string $attribute Attribute key (e.g., `label`, `placeholder`).
	 * @param string $value     Original value.
	 * @since 2.11.0
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_block_attribute( int $form_id, string $block_id, string $attribute, string $value ): string {
		if ( '' === $value || '' === $block_id || '' === $attribute ) {
			return $value;
		}

		return $this->dispatch_package( $form_id, self::block_attribute_name( $block_id, $attribute ), $value );
	}

	/**
	 * Translate a single option label inside a dropdown / multi-choice block.
	 *
	 * @param int    $form_id      Form post ID.
	 * @param string $block_id     Stable block identifier from the block's `block_id` attribute.
	 * @param int    $option_index Zero-based index of the option within the block's options array.
	 * @param string $value        Original label value.
	 * @since 2.11.0
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_block_option_label( int $form_id, string $block_id, int $option_index, string $value ): string {
		if ( '' === $value || '' === $block_id ) {
			return $value;
		}

		return $this->dispatch_package( $form_id, self::block_option_name( $block_id, $option_index ), $value );
	}

	/**
	 * Pre-translate a form's raw block markup and also return the parsed top-level
	 * blocks so the render path can derive its block count without re-parsing the
	 * rendered HTML.
	 *
	 * Parses the post content, walks every translatable SureForms block attribute
	 * (per {@see translatable_block_attributes()} and {@see translatable_option_blocks()}),
	 * substitutes the translated value where the active multilingual provider has one,
	 * and re-serialises the markup. Inner blocks are walked recursively.
	 *
	 * This is the canonical, idempotent entry point that every render path (the
	 * standard form markup, and Pro multi-step / conversational renderers) should
	 * call AFTER applying the `srfm_get_form_post_content` filter and AFTER any
	 * path-specific marker injection — never before.
	 *
	 * The translated markup is cached per (form, language, post-modified, content)
	 * in the object cache: `post_modified_gmt` auto-invalidates on every form edit,
	 * and the content hash guards against content-mutating filters (e.g. randomised
	 * question order). Acts as a pure pass-through — no cache access, no parse — when
	 * no provider is active or the content is empty.
	 *
	 * @param int           $form_id      Form post ID.
	 * @param string        $post_content Raw block markup (typically `$post->post_content`).
	 * @param \WP_Post|null $post        Form post, used only for cache invalidation via `post_modified_gmt`.
	 * @since 2.11.0
	 * @return array{0: string, 1: array<int|string, array<string, mixed>>} [ markup, parsed top-level blocks ].
	 */
	public function translate_form_content_with_blocks( int $form_id, string $post_content, ?\WP_Post $post = null ): array {
		$provider = Multilingual_Manager::get_instance()->provider();

		// Single-language / no-provider path: pure pass-through, zero overhead.
		if ( ! $provider->is_active() || '' === trim( $post_content ) ) {
			return [ $post_content, [] ];
		}

		$language  = $provider->current_language();
		$modified  = $post instanceof \WP_Post ? (string) $post->post_modified_gmt : '';
		$cache_key = 'srfm_xl_form_' . $form_id . '_' . md5( $language . '|' . $modified . '|' . md5( $post_content ) );

		$cached = wp_cache_get( $cache_key, 'srfm_multilingual' );
		if ( is_string( $cached ) ) {
			return [ $cached, parse_blocks( $cached ) ];
		}

		$blocks = parse_blocks( $post_content );

		if ( empty( $blocks ) ) {
			return [ $post_content, [] ];
		}

		$translated_blocks = $this->translate_blocks_recursive( $form_id, $blocks );

		// $translated_blocks is the round-tripped output of parse_blocks() with only
		// its scalar attribute values swapped, so serialize_blocks() is safe here.
		// PHPStan's stricter shape declaration for serialize_blocks() can't see through
		// the generic array<string, mixed> we use internally to keep the recursion
		// type-stable across innerBlocks. The runtime shape is identical.
		// @phpstan-ignore-next-line argument.type
		$markup = serialize_blocks( $translated_blocks );

		wp_cache_set( $cache_key, $markup, 'srfm_multilingual', HOUR_IN_SECONDS );

		return [ $markup, $translated_blocks ];
	}

	/**
	 * Back-compatible single-string entry point. Pre-translate a form's raw block
	 * markup before it reaches `do_blocks()`.
	 *
	 * Thin wrapper over {@see translate_form_content_with_blocks()} preserved for
	 * existing callers (including Pro) that only need the markup string. Idempotent:
	 * re-feeding already-translated markup is safe.
	 *
	 * @param int    $form_id      Form post ID.
	 * @param string $post_content Raw block markup (typically `$post->post_content`).
	 * @since 2.11.0
	 * @return string The (possibly translated) block markup.
	 */
	public function translate_form_content( int $form_id, string $post_content ): string {
		$post       = get_post( $form_id );
		[ $markup ] = $this->translate_form_content_with_blocks( $form_id, $post_content, $post instanceof \WP_Post ? $post : null );

		return $markup;
	}

	/**
	 * Walk a list of parsed blocks (and their innerBlocks) translating attributes
	 * in-place. Block markers without recognised SureForms attributes pass through
	 * unchanged.
	 *
	 * @param int                                     $form_id Form post ID.
	 * @param array<int|string, array<string, mixed>> $blocks  Parsed-blocks structure.
	 * @since 2.11.0
	 * @return array<int|string, array<string, mixed>> The same structure with translatable attribute values swapped.
	 */
	protected function translate_blocks_recursive( int $form_id, array $blocks ): array {
		$attribute_map = self::translatable_block_attributes();
		$option_blocks = self::translatable_option_blocks();

		foreach ( $blocks as $index => $block ) {
			$block_name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';
			$attrs      = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];
			$block_id   = isset( $attrs['block_id'] ) && is_string( $attrs['block_id'] ) ? $attrs['block_id'] : '';

			// Translate scalar attributes for known SureForms blocks.
			if ( '' !== $block_id && isset( $attribute_map[ $block_name ] ) ) {
				foreach ( $attribute_map[ $block_name ] as $attribute_key ) {
					if ( ! isset( $attrs[ $attribute_key ] ) || ! is_string( $attrs[ $attribute_key ] ) ) {
						continue;
					}
					$attrs[ $attribute_key ] = $this->translate_block_attribute(
						$form_id,
						$block_id,
						$attribute_key,
						$attrs[ $attribute_key ]
					);
				}
			}

			// Translate option labels for dropdown / multi-choice.
			if ( '' !== $block_id && in_array( $block_name, $option_blocks, true ) && isset( $attrs['options'] ) && is_array( $attrs['options'] ) ) {
				foreach ( $attrs['options'] as $option_index => $option ) {
					if ( ! is_array( $option ) || ! isset( $option['label'] ) || ! is_string( $option['label'] ) ) {
						continue;
					}
					$attrs['options'][ $option_index ]['label'] = $this->translate_block_option_label(
						$form_id,
						$block_id,
						(int) $option_index,
						$option['label']
					);
				}
			}

			$blocks[ $index ]['attrs'] = $attrs;

			// Recurse into innerBlocks (e.g., page-break wrappers).
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$blocks[ $index ]['innerBlocks'] = $this->translate_blocks_recursive( $form_id, $block['innerBlocks'] );
			}
		}

		return $blocks;
	}

	/**
	 * Dispatch the translate call to the active multilingual provider.
	 *
	 * @param string $value Original value (used as fallback when no translation exists).
	 * @param string $name  Fully built string name per the documented naming scheme.
	 * @since 2.11.0
	 * @return string Translated value, or the original value when the provider has no translation.
	 */
	private function dispatch( string $value, string $name ): string {
		$provider = Multilingual_Manager::get_instance()->provider();
		return $provider->translate( $value, $name, self::DOMAIN );
	}

	/**
	 * Translate a per-form string via the provider's String Package, so it shows
	 * grouped under the form in the Translation Editor.
	 *
	 * Falls back to a flat String-Translation string (legacy `form_{id}_{name}`
	 * naming) when the provider can't do packages — keeping older WPML setups and
	 * non-package providers working.
	 *
	 * @param int    $form_id Form post ID.
	 * @param string $name    Package-scoped string name (see the *_name() builders).
	 * @param string $value   Original value (fallback when untranslated).
	 * @since 2.11.0
	 * @return string Translated value, or the original when no translation exists.
	 */
	private function dispatch_package( int $form_id, string $name, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$provider = Multilingual_Manager::get_instance()->provider();

		if ( $provider->supports_packages() ) {
			return $provider->translate_package_string( self::form_package( $form_id ), $name, $value );
		}

		// Legacy / non-package fallback: flat string with the form-scoped name.
		return $provider->translate( $value, 'form_' . $form_id . '_' . $name, self::DOMAIN );
	}
}
