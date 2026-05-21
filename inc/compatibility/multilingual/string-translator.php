<?php
/**
 * Multilingual String Translator.
 *
 * Provides convenient, name-scheme-aware helpers for translating SureForms'
 * form-level metadata strings through the active multilingual provider.
 *
 * @package sureforms.
 * @since x.x.x
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
 * Naming scheme (domain `sureforms`):
 *  - Submit button text:           `form_{form_id}_submit_button`
 *  - Confirmation message:         `form_{form_id}_confirmation_{N}_message`
 *  - Notification subject:         `form_{form_id}_notification_{N}_subject`
 *  - Notification body:            `form_{form_id}_notification_{N}_body`
 *  - Notification from_name:       `form_{form_id}_notification_{N}_from_name`
 *  - Notification reply_to:        `form_{form_id}_notification_{N}_reply_to`
 *  - Compliance message:           `form_{form_id}_compliance_{N}_message`
 *  - Form restriction message:     `form_{form_id}_restriction_message`
 *  - Block string attribute:       `form_{form_id}_block_{block_id}_{attribute}`
 *  - Block option label:           `form_{form_id}_block_{block_id}_option_{N}_label`
 *
 * @since x.x.x
 */
class String_Translator {
	use Get_Instance;

	/**
	 * Translation domain used for every SureForms string.
	 *
	 * @since x.x.x
	 */
	public const DOMAIN = 'sureforms';

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
	 * @since x.x.x
	 * @return array<string, array<int, string>>
	 */
	public static function translatable_block_attributes(): array {
		return [
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
	}

	/**
	 * Block names whose `options` array contains translatable `label` entries.
	 *
	 * @since x.x.x
	 * @return array<int, string>
	 */
	public static function translatable_option_blocks(): array {
		return [ 'srfm/dropdown', 'srfm/multi-choice' ];
	}

	/**
	 * Translate a form's submit button text.
	 *
	 * @param int    $form_id Form post ID.
	 * @param string $value   Original value (used as fallback when no translation exists).
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_submit_button( int $form_id, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_submit_button';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate a per-confirmation message.
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the confirmation within the form's confirmation set.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_confirmation_message( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_confirmation_' . $index . '_message';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate an email notification subject.
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the notification within the form's notification set.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_notification_subject( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_notification_' . $index . '_subject';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate an email notification body.
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the notification within the form's notification set.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_notification_body( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_notification_' . $index . '_body';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate an email notification "from name".
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the notification within the form's notification set.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_notification_from_name( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_notification_' . $index . '_from_name';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate an email notification reply-to address.
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the notification within the form's notification set.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_notification_reply_to( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_notification_' . $index . '_reply_to';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate a compliance/GDPR message.
	 *
	 * @param int    $form_id Form post ID.
	 * @param int    $index   Zero-based index of the compliance entry within the form's compliance set.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_compliance_message( int $form_id, int $index, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_compliance_' . $index . '_message';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate the form restriction message.
	 *
	 * @param int    $form_id Form post ID.
	 * @param string $value   Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_restriction_message( int $form_id, string $value ): string {
		if ( '' === $value ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_restriction_message';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate a scalar block attribute (label, placeholder, help, errorMsg, etc).
	 *
	 * @param int    $form_id   Form post ID.
	 * @param string $block_id  Stable block identifier from the block's `block_id` attribute.
	 * @param string $attribute Attribute key (e.g., `label`, `placeholder`).
	 * @param string $value     Original value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_block_attribute( int $form_id, string $block_id, string $attribute, string $value ): string {
		if ( '' === $value || '' === $block_id || '' === $attribute ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_block_' . $block_id . '_' . $attribute;
		return $this->dispatch( $value, $name );
	}

	/**
	 * Translate a single option label inside a dropdown / multi-choice block.
	 *
	 * @param int    $form_id      Form post ID.
	 * @param string $block_id     Stable block identifier from the block's `block_id` attribute.
	 * @param int    $option_index Zero-based index of the option within the block's options array.
	 * @param string $value        Original label value.
	 * @since x.x.x
	 * @return string Translated value, or the original when no provider/translation is available.
	 */
	public function translate_block_option_label( int $form_id, string $block_id, int $option_index, string $value ): string {
		if ( '' === $value || '' === $block_id ) {
			return $value;
		}

		$name = 'form_' . $form_id . '_block_' . $block_id . '_option_' . $option_index . '_label';
		return $this->dispatch( $value, $name );
	}

	/**
	 * Pre-translate a form's raw block markup before it reaches `do_blocks()`.
	 *
	 * Parses the post content, walks every translatable SureForms block attribute
	 * (per {@see translatable_block_attributes()} and {@see translatable_option_blocks()}),
	 * substitutes the translated value where the active multilingual provider has one,
	 * and re-serialises the markup back into a string. Inner blocks are walked
	 * recursively so nested fields are covered.
	 *
	 * Acts as a no-op when no multilingual provider is active or when the content
	 * contains no SureForms blocks the translator knows about.
	 *
	 * @param int    $form_id      Form post ID.
	 * @param string $post_content Raw block markup (typically `$post->post_content`).
	 * @since x.x.x
	 * @return string The (possibly translated) block markup.
	 */
	public function translate_form_content( int $form_id, string $post_content ): string {
		$provider = Multilingual_Manager::get_instance()->provider();

		if ( ! $provider->is_active() || '' === trim( $post_content ) ) {
			return $post_content;
		}

		$blocks = parse_blocks( $post_content );

		if ( empty( $blocks ) ) {
			return $post_content;
		}

		$blocks = $this->translate_blocks_recursive( $form_id, $blocks );

		// $blocks is the round-tripped output of parse_blocks() with only its scalar
		// attribute values swapped, so serialize_blocks() is safe here. PHPStan's
		// stricter shape declaration for serialize_blocks() can't see through the
		// generic array<string, mixed> we use internally to keep the recursion
		// type-stable across innerBlocks. The runtime shape is identical.
		// @phpstan-ignore-next-line argument.type
		return serialize_blocks( $blocks );
	}

	/**
	 * Walk a list of parsed blocks (and their innerBlocks) translating attributes
	 * in-place. Block markers without recognised SureForms attributes pass through
	 * unchanged.
	 *
	 * @param int                                     $form_id Form post ID.
	 * @param array<int|string, array<string, mixed>> $blocks  Parsed-blocks structure.
	 * @since x.x.x
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
	 * @since x.x.x
	 * @return string Translated value, or the original value when the provider has no translation.
	 */
	private function dispatch( string $value, string $name ): string {
		$provider = Multilingual_Manager::get_instance()->provider();
		return $provider->translate( $value, $name, self::DOMAIN );
	}
}
