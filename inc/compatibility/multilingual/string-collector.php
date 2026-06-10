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
 * @since 2.11.0
 */

namespace SRFM\Inc\Compatibility\Multilingual;

use SRFM\Inc\Helper;
use SRFM\Inc\Traits\Get_Instance;
use SRFM\Inc\Translatable;

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
 * @since 2.11.0
 */
class String_Collector {
	use Get_Instance;

	/**
	 * The form's String Package descriptor for the current collect() run, or null
	 * when no package run is active.
	 *
	 * @since 2.11.0
	 * @var array<string,string>|null
	 */
	private $active_package = null;

	/**
	 * Whether the active provider supports String Packages (decided once per run).
	 *
	 * @since 2.11.0
	 * @var bool
	 */
	private $packages_supported = false;

	/**
	 * Running 1-based index of the current field within a form, used to build the
	 * "Fields/<Type> #<n>" group path shown in the Translation Editor. Reset at the
	 * start of each form's block walk.
	 *
	 * @since 2.11.0
	 * @var int
	 */
	private $field_index = 0;

	/**
	 * Constructor. Hooks into save_post for the SureForms form post type.
	 *
	 * Uses priority 20 so this runs after WordPress's own save processing and
	 * any default meta updates from the editor request.
	 *
	 * @since 2.11.0
	 */
	public function __construct() {
		add_action( 'save_post_' . SRFM_FORMS_POST_TYPE, [ $this, 'on_form_save' ], 20, 1 );

		// Register the GLOBAL built-in validation strings once per admin request
		// (no-op when no multilingual provider is active). These strings are not
		// per-form, so they belong on an admin/authoring hook rather than on every
		// frontend request. WPML dedupes by (domain, name, value), so re-asserting
		// on each admin load is cheap and idempotent, and covers fresh installs and
		// the WPML-activated-after-SureForms case.
		if ( is_admin() ) {
			add_action( 'admin_init', [ $this, 'collect_validation_messages' ] );
		}
	}

	/**
	 * Entry point hooked to save_post_{post_type}.
	 *
	 * Skips autosaves and revisions, and bails when no multilingual provider is
	 * active. Otherwise delegates to {@see collect()} to register all strings.
	 *
	 * @param int $form_id The form post ID being saved.
	 * @since 2.11.0
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
	 * @since 2.11.0
	 * @return void
	 */
	public function collect( int $form_id ): void {
		$provider = Multilingual_Manager::get_instance()->provider();

		if ( ! $provider->is_active() ) {
			return;
		}

		// Group every per-form string into a single WPML String Package so they
		// surface together under the form in the Translation Editor (instead of as
		// flat, global String-Translation entries). start/finish bracket the
		// registrations so strings for deleted fields are pruned automatically.
		$package                  = String_Translator::form_package( $form_id );
		$this->packages_supported = $provider->supports_packages();
		$this->active_package     = $package;
		if ( $this->packages_supported ) {
			$provider->start_package( $package );
		}

		// Submit button text.
		$this->register_form_string(
			$form_id,
			String_Translator::submit_button_name(),
			$this->get_meta_string( $form_id, '_srfm_submit_button_text' ),
			// "Group: Leaf" — WPML splits on ': ' to nest this under a Settings group.
			__( 'Settings', 'sureforms' ) . ': ' . __( 'Submit button text', 'sureforms' )
		);

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
			$this->register_form_string(
				$form_id,
				String_Translator::confirmation_name( (int) $index ),
				$message,
				/* translators: %d is the confirmation number. */
				__( 'Confirmations', 'sureforms' ) . '/' . sprintf( __( 'Confirmation #%d', 'sureforms' ), (int) $index + 1 ) . ': ' . __( 'Message', 'sureforms' ),
				'AREA'
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

			// reply_to is an email address (or a smart tag resolving to one), not
			// human-readable copy, so it is intentionally excluded from the
			// translatable set. from_name can legitimately be a localized display name.
			$fields = [
				'subject'   => [ __( 'Subject', 'sureforms' ), 'LINE' ],
				'body'      => [ __( 'Message body', 'sureforms' ), 'AREA' ],
				'from_name' => [ __( '"From" name', 'sureforms' ), 'LINE' ],
			];
			/* translators: %d is the notification number. */
			$notification_group = __( 'Notifications', 'sureforms' ) . '/' . sprintf( __( 'Notification #%d', 'sureforms' ), (int) $index + 1 );
			foreach ( $fields as $field => $meta ) {
				$value = isset( $notification[ $field ] ) ? Helper::get_string_value( $notification[ $field ] ) : '';
				$this->register_form_string(
					$form_id,
					String_Translator::notification_name( (int) $index, $field ),
					$value,
					$notification_group . ': ' . $meta[0],
					$meta[1]
				);
			}
		}

		// Form restriction — JSON-encoded string in single meta.
		$restriction_raw = $this->get_meta_string( $form_id, '_srfm_form_restriction' );
		if ( '' !== $restriction_raw ) {
			$restriction = json_decode( $restriction_raw, true );
			if ( is_array( $restriction ) && isset( $restriction['message'] ) ) {
				$message = Helper::get_string_value( $restriction['message'] );
				$this->register_form_string(
					$form_id,
					String_Translator::restriction_name(),
					$message,
					__( 'Settings', 'sureforms' ) . ': ' . __( 'Form restriction message', 'sureforms' ),
					'AREA'
				);
			}
		}

		// Block-attribute strings (field labels, placeholders, option labels, etc.).
		$this->collect_block_strings( $form_id );

		if ( $this->packages_supported ) {
			$provider->finish_package( $package );
		}
		$this->active_package = null;
	}

	/**
	 * Register every built-in dynamic validation message with the multilingual
	 * provider, using the raw English source as the value. Names follow
	 * {@see String_Translator::translate_validation_message()}: `validation_{key}`.
	 *
	 * These strings are global (not per-form), so registration runs on `admin_init`
	 * (see the constructor) rather than on the front end. Idempotent — WPML's
	 * `wpml_register_single_string` action deduplicates by (domain, name, value),
	 * so re-asserting on each admin load is safe. Bails when no provider is active.
	 *
	 * @since 2.11.0
	 * @return void
	 */
	public function collect_validation_messages(): void {
		$provider = Multilingual_Manager::get_instance()->provider();
		if ( ! $provider->is_active() ) {
			return;
		}

		foreach ( Translatable::dynamic_messages_source() as $key => $value ) {
			if ( ! is_string( $key ) || ! is_string( $value ) ) {
				continue;
			}
			$this->register_if_non_empty( 'validation_' . $key, $value );
		}

		// Common strings rendered server-side in field markup that are NOT stored
		// per-form (so they never reach a form package): JS-validation fallbacks
		// and shared field defaults such as the dropdown's empty-state placeholder,
		// which is only serialized into block markup when a site owner overrides it.
		$common = [
			'srfm_required_field'       => 'This field is required.',
			'srfm_unique_field'         => 'Value needs to be unique.',
			'srfm_submit_error'         => 'There was an error trying to submit your form. Please try again.',
			'srfm_dropdown_placeholder' => 'Select an option',
		];
		foreach ( $common as $key => $value ) {
			$this->register_if_non_empty( 'validation_' . $key, $value );
		}
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
	 * @since 2.11.0
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

		// Self-frame the package when called directly (not from collect()), so the
		// block strings are still grouped + pruned. collect() leaves $active_package
		// set, in which case we reuse its framing.
		$standalone = null === $this->active_package;
		if ( $standalone ) {
			$provider                 = Multilingual_Manager::get_instance()->provider();
			$this->packages_supported = $provider->supports_packages();
			$this->active_package     = String_Translator::form_package( $form_id );
			if ( $this->packages_supported ) {
				$provider->start_package( $this->active_package );
			}
		}

		$this->field_index = 0;
		$this->walk_blocks_for_collection( $form_id, $blocks );

		if ( $standalone ) {
			if ( $this->packages_supported && is_array( $this->active_package ) ) {
				Multilingual_Manager::get_instance()->provider()->finish_package( $this->active_package );
			}
			$this->active_package = null;
		}
	}

	/**
	 * Recursively iterate parsed blocks and register translatable strings.
	 *
	 * @param int                                     $form_id Form post ID.
	 * @param array<int|string, array<string, mixed>> $blocks  Parsed-blocks structure.
	 * @since 2.11.0
	 * @return void
	 */
	protected function walk_blocks_for_collection( int $form_id, array $blocks ): void {
		$attribute_map = String_Translator::translatable_block_attributes();
		$option_blocks = String_Translator::translatable_option_blocks();

		foreach ( $blocks as $block ) {
			$block_name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';
			$attrs      = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];
			$block_id   = isset( $attrs['block_id'] ) && is_string( $attrs['block_id'] ) ? $attrs['block_id'] : '';

			$has_attributes = '' !== $block_id && isset( $attribute_map[ $block_name ] );
			$has_options    = '' !== $block_id && in_array( $block_name, $option_blocks, true ) && isset( $attrs['options'] ) && is_array( $attrs['options'] );

			// Build the "Fields/<Type> #<n>" group path once per translatable field,
			// so a field's label/placeholder/help/options nest together under one
			// node in the Translation Editor. The block TYPE (not the user label) is
			// used so the path can't be corrupted by '/' or ': ' in user content.
			$field_group = '';
			if ( $has_attributes || $has_options ) {
				$this->field_index++;
				$field_group = sprintf(
					'%s/%s #%d',
					__( 'Fields', 'sureforms' ),
					String_Translator::block_type_label( $block_name ),
					$this->field_index
				);
			}

			if ( $has_attributes ) {
				foreach ( $attribute_map[ $block_name ] as $attribute_key ) {
					if ( ! isset( $attrs[ $attribute_key ] ) || ! is_string( $attrs[ $attribute_key ] ) ) {
						continue;
					}
					$this->register_form_string(
						$form_id,
						String_Translator::block_attribute_name( $block_id, $attribute_key ),
						$attrs[ $attribute_key ],
						$field_group . ': ' . $this->attribute_label( $attribute_key )
					);
				}
			}

			if ( $has_options ) {
				foreach ( $attrs['options'] as $option_index => $option ) {
					if ( ! is_array( $option ) || ! isset( $option['label'] ) || ! is_string( $option['label'] ) ) {
						continue;
					}
					$this->register_form_string(
						$form_id,
						String_Translator::block_option_name( $block_id, (int) $option_index ),
						$option['label'],
						/* translators: %d is the option number. */
						$field_group . ': ' . sprintf( __( 'Option %d', 'sureforms' ), (int) $option_index + 1 )
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
	 * @since 2.11.0
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
	 * @since 2.11.0
	 * @return void
	 */
	private function register_if_non_empty( string $name, string $value ): void {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return;
		}

		Multilingual_Manager::get_instance()->provider()->register_string( $name, $value );
	}

	/**
	 * Register a per-form string into the active String Package, skipping empties.
	 *
	 * Falls back to a flat String-Translation string (legacy `form_{id}_{name}`
	 * name) when the provider can't do packages, matching
	 * {@see String_Translator::dispatch_package()} so registration and render stay
	 * in lockstep on either path.
	 *
	 * @param int    $form_id Form post ID.
	 * @param string $name    Package-scoped string name (see String_Translator *_name() builders).
	 * @param string $value   Original string value.
	 * @param string $title   Human-readable label shown in the Translation Editor.
	 * @param string $type    Editor field type: LINE, AREA or VISUAL.
	 * @since 2.11.0
	 * @return void
	 */
	private function register_form_string( int $form_id, string $name, string $value, string $title = '', string $type = 'LINE' ): void {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return;
		}

		$provider = Multilingual_Manager::get_instance()->provider();

		if ( $this->packages_supported && is_array( $this->active_package ) ) {
			$provider->register_package_string( $this->active_package, $name, $value, $title, $type );
			return;
		}

		// Legacy / non-package fallback: flat string with the form-scoped name.
		$provider->register_string( 'form_' . $form_id . '_' . $name, $value );
	}

	/**
	 * Human-readable label for a block attribute, shown in the Translation Editor.
	 *
	 * @param string $attribute Block attribute key.
	 * @since 2.11.0
	 * @return string
	 */
	private function attribute_label( string $attribute ): string {
		$labels = [
			'label'              => __( 'Label', 'sureforms' ),
			'placeholder'        => __( 'Placeholder', 'sureforms' ),
			'help'               => __( 'Help text', 'sureforms' ),
			'errorMsg'           => __( 'Error message', 'sureforms' ),
			'defaultValue'       => __( 'Default value', 'sureforms' ),
			'duplicateMsg'       => __( 'Duplicate message', 'sureforms' ),
			'confirmLabel'       => __( 'Confirm label', 'sureforms' ),
			'prefix'             => __( 'Prefix', 'sureforms' ),
			'suffix'             => __( 'Suffix', 'sureforms' ),
			'buttonText'         => __( 'Button text', 'sureforms' ),
			'amountLabel'        => __( 'Amount label', 'sureforms' ),
			'paymentDescription' => __( 'Payment description', 'sureforms' ),
			'oneTimeLabel'       => __( 'One-time label', 'sureforms' ),
			'subscriptionLabel'  => __( 'Subscription label', 'sureforms' ),
		];

		return $labels[ $attribute ] ?? $attribute;
	}
}
