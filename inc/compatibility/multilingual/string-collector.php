<?php
/**
 * Multilingual String Collector.
 *
 * Walks a SureForms form post and meta on save, registering every user-facing
 * translatable string with the active multilingual provider. Guarantees strings
 * appear in WPML's String Translation registry even when WPML's declarative
 * <custom-fields-texts> handling is inconsistent across versions.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc\Compatibility\Multilingual;

use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * String_Collector.
 *
 * Walks form metadata at save time and registers every translatable string with
 * the active multilingual provider. Acts as a belt-and-suspenders safeguard
 * against inconsistencies in WPML's declarative <custom-fields-texts> handling.
 *
 * @since x.x.x
 */
class String_Collector {
	use Get_Instance;

	/**
	 * Constructor. Hooks into save_post for the SureForms form post type.
	 *
	 * Uses priority 20 so this runs after WordPress's own save processing and
	 * any default meta updates from the editor request.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		add_action( 'save_post_' . SRFM_FORMS_POST_TYPE, [ $this, 'on_form_save' ], 20, 1 );
	}

	/**
	 * Entry point hooked to save_post_{post_type}.
	 *
	 * Skips autosaves and revisions, and bails when no multilingual provider is
	 * active. Otherwise delegates to {@see collect()} to register all strings.
	 *
	 * @param int $form_id The form post ID being saved.
	 * @since x.x.x
	 * @return void
	 */
	public function on_form_save( int $form_id ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $form_id ) ) {
			return;
		}

		$provider = Multilingual_Manager::get_instance()->provider();

		if ( ! $provider->is_active() ) {
			return;
		}

		$this->collect( $form_id );
	}

	/**
	 * Walk the form and register every translatable string with the provider.
	 *
	 * Public so unit tests can exercise the collection logic directly without
	 * needing to fire the save_post action, and so migration code paths can
	 * back-fill strings for existing forms.
	 *
	 * @param int $form_id The form post ID.
	 * @since x.x.x
	 * @return void
	 */
	public function collect( int $form_id ): void {
		$provider = Multilingual_Manager::get_instance()->provider();

		if ( ! $provider->is_active() ) {
			return;
		}

		// Submit button text.
		$submit_button_text = $this->get_meta_string( $form_id, '_srfm_submit_button_text' );
		$this->register_if_non_empty( 'form_' . $form_id . '_submit_button', $submit_button_text );

		// Form confirmations — stored as a nested array (get_post_meta without $single = true).
		$confirmations_raw = get_post_meta( $form_id, '_srfm_form_confirmation' );
		$confirmations     = is_array( $confirmations_raw ) && isset( $confirmations_raw[0] ) && is_array( $confirmations_raw[0] )
			? $confirmations_raw[0]
			: [];

		foreach ( $confirmations as $index => $confirmation ) {
			if ( ! is_array( $confirmation ) ) {
				continue;
			}

			$message = isset( $confirmation['message'] ) ? Helper::get_string_value( $confirmation['message'] ) : '';
			$this->register_if_non_empty(
				'form_' . $form_id . '_confirmation_' . (int) $index . '_message',
				$message
			);
		}

		// Email notifications — same nested-array shape as confirmations.
		$notifications_raw = get_post_meta( $form_id, '_srfm_email_notification' );
		$notifications     = is_array( $notifications_raw ) && isset( $notifications_raw[0] ) && is_array( $notifications_raw[0] )
			? $notifications_raw[0]
			: [];

		foreach ( $notifications as $index => $notification ) {
			if ( ! is_array( $notification ) ) {
				continue;
			}

			$fields = [ 'subject', 'body', 'from_name', 'reply_to' ];
			foreach ( $fields as $field ) {
				$value = isset( $notification[ $field ] ) ? Helper::get_string_value( $notification[ $field ] ) : '';
				$this->register_if_non_empty(
					'form_' . $form_id . '_notification_' . (int) $index . '_' . $field,
					$value
				);
			}
		}

		// Compliance items — same nested-array shape.
		$compliance_raw = get_post_meta( $form_id, '_srfm_compliance' );
		$compliance     = is_array( $compliance_raw ) && isset( $compliance_raw[0] ) && is_array( $compliance_raw[0] )
			? $compliance_raw[0]
			: [];

		foreach ( $compliance as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$message = isset( $item['message'] ) ? Helper::get_string_value( $item['message'] ) : '';
			$this->register_if_non_empty(
				'form_' . $form_id . '_compliance_' . (int) $index . '_message',
				$message
			);
		}

		// Form restriction — JSON-encoded string in single meta.
		$restriction_raw = $this->get_meta_string( $form_id, '_srfm_form_restriction' );
		if ( '' !== $restriction_raw ) {
			$restriction = json_decode( $restriction_raw, true );
			if ( is_array( $restriction ) && isset( $restriction['message'] ) ) {
				$message = Helper::get_string_value( $restriction['message'] );
				$this->register_if_non_empty( 'form_' . $form_id . '_restriction_message', $message );
			}
		}

		// Block-attribute strings (field labels, placeholders, option labels, etc.).
		$this->collect_block_strings( $form_id );
	}

	/**
	 * Walk the form's parsed blocks and register every translatable block attribute
	 * with the active multilingual provider.
	 *
	 * Names use the same scheme that {@see String_Translator::translate_block_attribute()}
	 * and {@see String_Translator::translate_block_option_label()} consume at render time,
	 * so the collector and the translator stay in lockstep:
	 *
	 *  - `form_{form_id}_block_{block_id}_{attribute}`
	 *  - `form_{form_id}_block_{block_id}_option_{N}_label`
	 *
	 * Bails early when the form has no block content.
	 *
	 * @param int $form_id The form post ID.
	 * @since x.x.x
	 * @return void
	 */
	public function collect_block_strings( int $form_id ): void {
		$post = get_post( $form_id );
		if ( ! $post instanceof \WP_Post || '' === trim( $post->post_content ) ) {
			return;
		}

		$blocks = parse_blocks( $post->post_content );
		if ( empty( $blocks ) ) {
			return;
		}

		$this->walk_blocks_for_collection( $form_id, $blocks );
	}

	/**
	 * Recursively iterate parsed blocks and register translatable strings.
	 *
	 * @param int                                     $form_id Form post ID.
	 * @param array<int|string, array<string, mixed>> $blocks  Parsed-blocks structure.
	 * @since x.x.x
	 * @return void
	 */
	protected function walk_blocks_for_collection( int $form_id, array $blocks ): void {
		$attribute_map = String_Translator::translatable_block_attributes();
		$option_blocks = String_Translator::translatable_option_blocks();

		foreach ( $blocks as $block ) {
			$block_name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';
			$attrs      = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];
			$block_id   = isset( $attrs['block_id'] ) && is_string( $attrs['block_id'] ) ? $attrs['block_id'] : '';

			if ( '' !== $block_id && isset( $attribute_map[ $block_name ] ) ) {
				foreach ( $attribute_map[ $block_name ] as $attribute_key ) {
					if ( ! isset( $attrs[ $attribute_key ] ) || ! is_string( $attrs[ $attribute_key ] ) ) {
						continue;
					}
					$this->register_if_non_empty(
						'form_' . $form_id . '_block_' . $block_id . '_' . $attribute_key,
						$attrs[ $attribute_key ]
					);
				}
			}

			if ( '' !== $block_id && in_array( $block_name, $option_blocks, true ) && isset( $attrs['options'] ) && is_array( $attrs['options'] ) ) {
				foreach ( $attrs['options'] as $option_index => $option ) {
					if ( ! is_array( $option ) || ! isset( $option['label'] ) || ! is_string( $option['label'] ) ) {
						continue;
					}
					$this->register_if_non_empty(
						'form_' . $form_id . '_block_' . $block_id . '_option_' . (int) $option_index . '_label',
						$option['label']
					);
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$this->walk_blocks_for_collection( $form_id, $block['innerBlocks'] );
			}
		}
	}

	/**
	 * Read a single post meta value defensively as a string.
	 *
	 * @param int    $form_id The form post ID.
	 * @param string $key     Meta key to read.
	 * @since x.x.x
	 * @return string Meta value coerced to string, or empty string when missing.
	 */
	private function get_meta_string( int $form_id, string $key ): string {
		return Helper::get_string_value( get_post_meta( $form_id, $key, true ) );
	}

	/**
	 * Register a string with the active provider, skipping empty values.
	 *
	 * @param string $name  Unique string identifier.
	 * @param string $value Original string value.
	 * @since x.x.x
	 * @return void
	 */
	private function register_if_non_empty( string $name, string $value ): void {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return;
		}

		Multilingual_Manager::get_instance()->provider()->register_string( $name, $value );
	}
}
