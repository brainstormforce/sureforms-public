<?php
/**
 * Field Validation Class
 *
 * Handles all field validation for SureForms
 *
 * @package SureForms
 * @since 1.12.2
 */

namespace SRFM\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Field Validation Class
 */
class Field_Validation {
	/**
	 * Field blocks whose markup is a single checkbox input, mapped to the field type
	 * slug and the label their markup class falls back to.
	 *
	 * A browser submits nothing for an unticked checkbox, so these are the only fields
	 * that can be absent from a submission while every other field posts an empty value.
	 * The label fallbacks mirror the set_input_label() calls in Checkbox_Markup and
	 * GDPR_Markup; they are what the rendered input name encodes when a form leaves the
	 * label empty, so the two have to agree.
	 *
	 * @since 2.12.9
	 */
	public const UNSUBMITTABLE_BLOCKS = [
		'srfm/checkbox' => [
			'type'  => 'checkbox',
			'label' => 'Checkbox',
		],
		'srfm/gdpr'     => [
			'type'  => 'gdpr',
			'label' => 'I consent to have this website store my information so they can respond to my inquiry.',
		],
	];

	/**
	 * Add block configuration for form fields.
	 *
	 * This function processes blocks in a form and stores their configuration as post meta.
	 * It applies filters to allow extensions to modify block configs and stores processed
	 * values for blocks that need special handling (like upload fields).
	 *
	 * @param array<mixed> $blocks  Array of blocks to process.
	 * @param int          $form_id Form post ID.
	 * @return void
	 * @since 1.12.2
	 */
	public static function add_block_config( $blocks, $form_id ) {
		// Initialize array to store processed block configurations.
		$block_config = [];

		// Loop through each block.
		foreach ( $blocks as $block ) {
			// Ensure $block is an array and has the required structure.
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( ! isset( $block['blockName'] ) || ! isset( $block['attrs'] ) || ! is_array( $block['attrs'] ) ) {
				continue;
			}
			// Validate block id.
			if ( ! array_key_exists( 'block_id', $block['attrs'] ) || empty( $block['attrs']['block_id'] ) || ! is_string( $block['attrs']['block_id'] ) ) {
				continue;
			}

			$block_id   = sanitize_text_field( $block['attrs']['block_id'] );
			$block_name = $block['blockName'];

			// Process specific block types.
			$processed_config = null;

			switch ( $block_name ) {
				case 'srfm/payment':
					$processed_config = self::process_payment_block( $block['attrs'], $blocks );
					break;
				case 'srfm/dropdown':
					$processed_config = self::process_dropdown_block( $block['attrs'] );
					break;
				case 'srfm/multi-choice':
					$processed_config = self::process_multichoice_block( $block['attrs'] );
					break;
				case 'srfm/number':
					$processed_config = self::process_number_block( $block['attrs'] );
					break;
				case 'srfm/textarea':
					$processed_config = self::process_textarea_block( $block['attrs'] );
					break;
			}

			// If block was processed, store its configuration.
			if ( null !== $processed_config && ! empty( $processed_config ) ) {
				$processed_config['block_name'] = $block_name;
				// Add the slug to the configuration.
				if ( isset( $block['attrs']['slug'] ) && ! empty( $block['attrs']['slug'] ) ) {
					$processed_config['slug'] = sanitize_text_field( $block['attrs']['slug'] );
				}

				$block_config[ $block_id ] = $processed_config;
				continue;
			}

			// Allow extensions to process and modify block config.
			$config = apply_filters( 'srfm_block_config', [ 'block' => $block ] );

			// If block was processed by a filter, add its processed value.
			if ( isset( $config['processed_value'] ) && ! empty( $config['processed_value'] ) ) {
				$block_config[ $block_id ] = $config['processed_value'];
				continue;
			}
		}

		// Sync the meta on every save. When $block_config is empty (e.g. a textarea
		// whose minLength was cleared, with no other blocks needing per-block
		// validation), we must clear the stored meta — otherwise the previously
		// saved values keep being used by the validator.
		if ( ! empty( $block_config ) ) {
			update_post_meta( $form_id, '_srfm_block_config', $block_config );
		} else {
			delete_post_meta( $form_id, '_srfm_block_config' );
		}
	}

	/**
	 * Retrieve or migrate the block configuration for legacy forms.
	 *
	 * This function checks if the _srfm_block_config post meta exists for the given form ID.
	 * Example: get_post_meta( 123, '_srfm_block_config', true ) might return an array of block configs.
	 * If not found, it attempts to parse the form's post content and generate the block config.
	 * Example: If a legacy form with ID 123 has no _srfm_block_config, but its post_content contains blocks,
	 *          the function will parse those blocks and call add_block_config() to generate and store the config.
	 *
	 * @param int $form_id The ID of the form post.
	 * @since 1.12.2
	 * @return array|null The block configuration array, or null if not found or invalid.
	 */
	public static function get_or_migrate_block_config_for_legacy_form( $form_id ) {
		// Validate that $form_id is a positive integer.
		// Example: $form_id = 123 is valid; $form_id = -1 or 'abc' is not.
		if ( ! is_int( $form_id ) || $form_id <= 0 ) {
			return null;
		}

		// Retrieve the block config from post meta.
		// Example: $block_config = [ 'block-1' => [ ... ], 'block-2' => [ ... ] ].
		$block_config = get_post_meta( $form_id, '_srfm_block_config', true );
		if ( ! empty( $block_config ) && is_array( $block_config ) ) {
			// If it exists and is an array, return it directly (no migration needed).
			// Example: Returning the existing $block_config array.
			return $block_config;
		}

		// Get the post by ID and validate.
		// Example: $post = get_post( 123 ); $post->post_content should contain block markup.
		$post = get_post( $form_id );
		if ( ! ( $post instanceof \WP_Post ) || empty( $post->post_content ) ) {
			return null;
		}

		// Parse the blocks from the post content and attempt migration.
		// Example: $blocks = parse_blocks( $post->post_content ); $blocks is an array of block arrays.
		if ( function_exists( 'parse_blocks' ) ) {
			$blocks = parse_blocks( $post->post_content );
			if ( is_array( $blocks ) && ! empty( $blocks ) ) {
				self::add_block_config( $blocks, $form_id );
			}
		}

		// Retrieve the block config again after migration attempt.
		// Example: After migration, $block_config should now be an array if successful.
		$block_config = get_post_meta( $form_id, '_srfm_block_config', true );

		return ! empty( $block_config ) && is_array( $block_config ) ? $block_config : null;
	}

	/**
	 * Prepare validation data for a given form.
	 *
	 * Retrieves the form block configuration from post meta and adds a 'name_with_id'
	 * key to each block, which is a unique identifier for the field (used for validation).
	 *
	 * @param int $current_form_id The ID of the form post.
	 * @since 1.12.2
	 * @return array|null The processed form configuration array, or null if not found.
	 */
	public static function prepared_validation_data( $current_form_id ) {
		// Retrieve the form block configuration from post meta.
		$get_form_config = self::get_or_migrate_block_config_for_legacy_form( $current_form_id );

		// If the configuration is an array, add a 'name_with_id' key to each block.
		if ( is_array( $get_form_config ) ) {
			foreach ( $get_form_config as $index => $block ) {
				// Ensure both 'blockName' and 'block_id' exist before creating the identifier.
				if ( isset( $block['blockName'] ) ) {
					// 'name_with_id' is used as a unique field identifier for validation.
					// Example: 'sureforms-input-abc123' for blockName 'sureforms/input' and block_id 'abc123'
					$name_with_id = str_replace( '/', '-', $block['blockName'] ) . '-' . $index;

					// Allow custom filter based on block type.
					$name_with_id = apply_filters(
						'srfm_block_config_name_with_id',
						$name_with_id,
						$block
					);

					$get_form_config[ $index ]['name_with_id'] = $name_with_id;
				}
			}
		}

		// Return the processed configuration array, or an empty array if not found.
		return is_array( $get_form_config ) ? $get_form_config : [];
	}

	/**
	 * Restore the fields a browser never submits when the visitor leaves them unset.
	 *
	 * An unticked checkbox is not posted at all, so its key never reaches the server:
	 * the field is missing from the entry and from notification emails, while every
	 * other field left empty arrives as '' and is shown as '-'. This rebuilds those
	 * keys from the form's own blocks so an untouched checkbox reads like every other
	 * untouched field.
	 *
	 * Submitted values are never replaced -- a key already present is left exactly as
	 * it arrived, so a ticked box keeps the value the browser sent.
	 *
	 * The key is assembled the same way the markup does it
	 * ({@see \SRFM\Inc\Fields\Base::set_input_label()} and the `name` attribute in
	 * checkbox-markup.php), so the two have to stay in step; test-field-validation.php
	 * asserts they do by rendering the field and comparing.
	 *
	 * @param array<mixed> $form_data Submitted form data.
	 * @param int|mixed    $form_id   The form post id.
	 * @since 2.12.9
	 * @return array<mixed> Form data with the missing keys added.
	 */
	public static function backfill_unsubmitted_fields( $form_data, $form_id ) {
		if ( ! is_array( $form_data ) ) {
			return $form_data;
		}

		$form_id = Helper::get_integer_value( $form_id );
		if ( $form_id <= 0 ) {
			return $form_data;
		}

		$walked = self::get_field_identifiers( $form_id );
		if ( empty( $walked['unsubmittable'] ) || ! is_array( $walked['unsubmittable'] ) ) {
			return $form_data;
		}

		foreach ( $walked['unsubmittable'] as $field ) {
			$key = 'srfm-' . $field['type'] . '-' . $field['block_id'] . '-lbl-' . Helper::encode( $field['label'] ) . '-' . $field['slug'];

			// array_key_exists(), not isset(): a submitted null must still count as
			// submitted, so this never overwrites what the visitor sent.
			if ( ! array_key_exists( $key, $form_data ) ) {
				$form_data[ $key ] = '';
			}
		}

		return $form_data;
	}

	/**
	 * Build the set of block ids that legitimately belong to a form.
	 *
	 * Walks the form's block markup and collects the `block_id` of every SureForms
	 * (`srfm/*`) block, recursing into `innerBlocks` (so repeater/container children are
	 * included) and expanding `core/block` reusable/synced pattern references into their
	 * `wp_block` post (so pattern-embedded fields are included too). A cycle guard on the
	 * reference ids prevents infinite recursion.
	 *
	 * Used by {@see self::strip_unknown_field_keys()}, which DROPS submitted keys whose
	 * block id is not part of the form rather than rejecting the submission — see the
	 * note there for why rejecting made cached forms unsubmittable.
	 *
	 * Note: this is deliberately NOT `prepared_validation_data()`
	 * — that map only holds blocks with extra validation config (dropdowns, payments,
	 * textarea min-length) and omits plain inputs, so it is not a field allowlist.
	 *
	 * @param int|mixed $form_id The form post id.
	 * @since 2.12.3
	 * @return array<string,true> Map of known block id => true. Empty when the form's
	 *                            blocks could not be derived (callers should fail open).
	 */
	public static function get_known_field_block_ids( $form_id ) {
		$form_id = Helper::get_integer_value( $form_id );
		if ( $form_id <= 0 ) {
			return [];
		}

		$walked = self::get_field_identifiers( $form_id );

		/**
		 * Filter the set of block ids considered valid for a form during submission.
		 *
		 * Extensions that inject legitimate fields not present in the form's own block
		 * markup (for example dynamically generated keys) can add their block ids here so
		 * those submissions are not dropped as unknown.
		 *
		 * Expects a map of `block_id => true`. A plain list of ids is accepted and
		 * converted; any other return value is ignored in favour of the walked set.
		 *
		 * @since 2.12.3
		 * @param array<string,true> $ids     Map of known block id => true.
		 * @param int                $form_id The form post id.
		 */
		$ids = apply_filters( 'srfm_known_field_block_ids', $walked['ids'], $form_id );

		// Coerce defensively. A callback returning a list (`[ 'aaa', 'bbb' ]`) rather
		// than a map would otherwise make every real field look unknown, and a non-array
		// return would drop the whole allowlist — so fall back to the walked set instead
		// of silently turning the check off.
		if ( ! is_array( $ids ) ) {
			return $walked['ids'];
		}

		return wp_is_numeric_array( $ids ) ? array_fill_keys( array_map( 'strval', $ids ), true ) : $ids;
	}

	/**
	 * The slugs of every SureForms field the form defines, block_id => true style.
	 *
	 * A slug is stable across renders where a block_id is not, so it is the identifier
	 * used to keep a submitted field whose block_id drifted (full-page cache, an editor
	 * rebuild). Mirrors get_known_field_block_ids(): one memoised walk, an unmemoised
	 * filter pass.
	 *
	 * @param int|mixed $form_id The form post id.
	 * @since 2.12.7
	 * @return array<string,true> Map of known slug => true.
	 */
	public static function get_known_field_slugs( $form_id ) {
		$form_id = Helper::get_integer_value( $form_id );
		if ( $form_id <= 0 ) {
			return [];
		}

		$walked = self::get_field_identifiers( $form_id );

		/**
		 * Filter the set of field slugs considered valid for a form during submission.
		 *
		 * @since 2.12.7
		 * @param array<string,true> $slugs   Map of known slug => true.
		 * @param int                $form_id The form post id.
		 */
		$slugs = apply_filters( 'srfm_known_field_slugs', $walked['slugs'], $form_id );

		if ( ! is_array( $slugs ) ) {
			return $walked['slugs'];
		}

		return wp_is_numeric_array( $slugs ) ? array_fill_keys( array_map( 'strval', $slugs ), true ) : $slugs;
	}

	/**
	 * Remove submitted field keys the form does not define.
	 *
	 * SECURITY INVARIANT — every submitted key must be checked against the form's own
	 * definition. The `-lbl-` substring proves only that a key LOOKS like a SureForms
	 * field, not that this form actually defines it, so shape alone is never sufficient:
	 * only keys the form declares may reach storage, email or export.
	 *
	 * Unknown keys are dropped rather than rejected. Rejecting looked safer but behaved
	 * badly: the allowlist is derived from `post_content` at submit time while the
	 * visitor's HTML was rendered earlier, so full-page caching or an editor-side
	 * `block_id` reassignment would make an otherwise valid form unsubmittable behind an
	 * error the visitor cannot act on. Dropping meets the same security goal — the
	 * invented key never reaches storage, email or export — without that failure mode.
	 *
	 * Repeater rows arrive as `repeaterKey[index][childKey]`, which PHP collapses into a
	 * single top-level key holding nested arrays. Those child keys are copied verbatim by
	 * Pro's `process_repeater_field()` and label-decoded downstream, so they are walked
	 * here too; the allowlist already contains repeater children because the collector
	 * recurses into `innerBlocks`.
	 *
	 * @param array<mixed> $form_data The submitted form data (sanitized).
	 * @param int|mixed    $form_id   The ID of the form being submitted.
	 * @since 2.12.3
	 * @return array<mixed> The form data with unknown field keys removed.
	 */
	public static function strip_unknown_field_keys( $form_data, $form_id ) {
		if ( ! is_array( $form_data ) ) {
			return [];
		}

		$form_id_int     = Helper::get_integer_value( $form_id );
		$known_block_ids = self::get_known_field_block_ids( $form_id_int );
		$known_slugs     = self::get_known_field_slugs( $form_id_int );

		// Fail open. The sets are empty only when the form's blocks could not be derived
		// (no/empty post_content, a parse failure, or a structure this walk does not
		// recognise). Enforcing on an empty set would strip every field.
		if ( empty( $known_block_ids ) && empty( $known_slugs ) ) {
			return $form_data;
		}

		foreach ( $form_data as $key => $value ) {
			if ( ! is_string( $key ) || false === strpos( $key, '-lbl-' ) ) {
				continue;
			}

			// Keep the field when EITHER its block_id OR its slug matches the form. The
			// block_id can drift between the (possibly full-page-cached) HTML the visitor
			// submitted and the current post_content; the slug does not, so requiring only
			// a block_id match silently deleted real submissions (#1517643). A key with
			// neither a known block_id nor a known slug is genuinely foreign and dropped.
			if ( ! self::field_key_belongs_to_form( $key, $known_block_ids, $known_slugs ) ) {
				unset( $form_data[ $key ] );
				continue;
			}

			// A known key whose value is an array is a repeater: walk its rows.
			if ( is_array( $value ) ) {
				$form_data[ $key ] = self::strip_unknown_repeater_keys( $value, $known_block_ids, $known_slugs );
			}
		}

		return $form_data;
	}

	/**
	 * Validate form data for a given form.
	 *
	 * This function checks each field in the submitted form data (including uploaded files)
	 * and applies the 'srfm_validate_form_data' filter to validate each field according to
	 * its configuration. Only fields with keys containing '-lbl-' (SureForms fields) are processed.
	 * If a field fails validation, its error message is added to the $not_valid_fields array.
	 *
	 * @param array<mixed> $form_data        The submitted form data (sanitized).
	 * @param int|mixed    $current_form_id  The ID of the form being validated.
	 * @since 1.12.2
	 * @return array An array of invalid fields and their error messages. Empty if all fields are valid.
	 */
	public static function validate_form_data( $form_data, $current_form_id ) {
		if ( ! is_array( $form_data ) || ! is_numeric( $current_form_id ) ) {
			return [];
		}

		// Holds fields that are not valid. Example: [ 'srfm-email-c867d9d9-lbl-email' => 'This field is required.' ].
		$not_valid_fields = [];

		// Retrieve the processed form configuration for validation.
		$get_form_config = self::prepared_validation_data( Helper::get_integer_value( $current_form_id ) );

		$form_data = apply_filters( 'srfm_field_validation_data', $form_data );

		// Iterate over each field in the form data.
		foreach ( $form_data as $key => $value ) {
			/**
			 * Only process SureForms fields.
			 * The '-lbl-' substring is mandatory in SureForms field keys.
			 * Example: $key = 'srfm-email-c867d9d9-lbl-email'
			 */
			if ( false === strpos( $key, '-lbl-' ) ) {
				continue;
			}

			$get_name_with_id = explode( '-lbl-', $key );
			// Extract the block id, i.e. the segment right before the first '-lbl-'.
			// Example: $get_name_with_id[0] = "srfm-email-c867d9d9" => "c867d9d9".
			//
			// Uses the shared Helper so submission agrees with every downstream consumer
			// of a field key (entries export, uniqueness check, smart tags). A local
			// regex here would define a second, subtly different notion of "the block
			// id" and the two could disagree on a legacy id.
			$extracted_id = is_string( $key ) ? Helper::get_block_id_from_key( $key ) : '';

			// $get_slug will be the slug after the first hyphen in the second part.
			// Example: $get_name_with_id[1] = "email" or "field-email", $get_slug = "email".
			$get_slug = isset( $get_name_with_id[1] ) ? preg_replace( '/^[^-]+-/', '', $get_name_with_id[1] ) : '';

			// $get_field_name is the field name without the block id.
			// Example: "srfm-email-c867d9d9" => "srfm-email".
			$get_field_name = str_replace( '-' . $extracted_id, '', $get_name_with_id[0] );

			// Apply the validation filter for the current field.
			// Example: Passes all relevant field data to the filter for validation.
			$field_validated = apply_filters(
				'srfm_validate_form_data',
				[
					'field_key'    => $key,
					'field_value'  => $value,
					'form_id'      => $current_form_id,
					'form_config'  => $get_form_config,
					'block_id'     => $extracted_id,
					'block_slug'   => $get_slug,
					'name_with_id' => $get_name_with_id[0],
					'field_name'   => $get_field_name,
				]
			);

			// Check the result of the validation.
			// Example: $field_validated = [ 'validated' => false, 'error' => 'This field is required.' ].
			if ( isset( $field_validated['validated'] ) ) {
				// If the field is valid, skip to the next field.
				if ( true === $field_validated['validated'] ) {
					continue;
				}

				// If the field is not valid, add the error message to the result array.
				// Example: $not_valid_fields[ 'srfm-email-c867d9d9-lbl-email' ] = 'This field is required.'.
				if ( false === $field_validated['validated'] ) {
					$not_valid_fields[ $key ] = $field_validated['error'] ?? __( 'Field is not valid.', 'sureforms' );
				}
			}

			// Textarea minimum character server-side validation.
			if ( 'srfm-textarea' === $get_field_name && is_string( $value ) && '' !== $value ) {
				$block_config = isset( $get_form_config[ $extracted_id ] ) && is_array( $get_form_config[ $extracted_id ] ) ? $get_form_config[ $extracted_id ] : [];
				$min_length   = isset( $block_config['min_length'] ) ? absint( $block_config['min_length'] ) : 0;
				if ( $min_length > 0 && mb_strlen( $value ) < $min_length ) {
					$dynamic_messages  = Translatable::dynamic_validation_messages();
					$min_chars_message = isset( $dynamic_messages['srfm_textarea_min_chars'] ) && is_string( $dynamic_messages['srfm_textarea_min_chars'] ) && '' !== $dynamic_messages['srfm_textarea_min_chars']
						? $dynamic_messages['srfm_textarea_min_chars']
						/* translators: %s represents the minimum number of characters required */
						: __( 'Please enter at least %s characters.', 'sureforms' );
					$not_valid_fields[ $key ] = sprintf( $min_chars_message, $min_length );
				}
			}

			// Email field RFC 5321 length limits (local part / domain), overridable via filter.
			// Only the main email value is in form data (the confirm input has no `name`),
			// so the server validates that value; the client mirrors this for both inputs.
			// Split on the LAST @ per RFC 5321 so the local part may contain a quoted @.
			$at_pos = is_string( $value ) && '' !== $value ? strrpos( $value, '@' ) : false;
			if ( 'srfm-email' === $get_field_name && is_string( $value ) && false !== $at_pos ) {
				$email_limits = self::get_email_char_limits();
				$local_max    = $email_limits['local'];
				$domain_max   = $email_limits['domain'];
				$local_len    = mb_strlen( substr( $value, 0, $at_pos ) );
				$domain_len   = mb_strlen( substr( $value, $at_pos + 1 ) );

				$dynamic_messages = Translatable::dynamic_validation_messages();
				if ( $local_max > 0 && $local_len > $local_max ) {
					$local_message = isset( $dynamic_messages['srfm_email_local_max_length'] ) && is_string( $dynamic_messages['srfm_email_local_max_length'] ) && '' !== $dynamic_messages['srfm_email_local_max_length']
						? $dynamic_messages['srfm_email_local_max_length']
						/* translators: %s: maximum characters allowed before the @ symbol. */
						: __( 'The part before @ may not exceed %s characters.', 'sureforms' );
					$not_valid_fields[ $key ] = sprintf( $local_message, $local_max );
				} elseif ( $domain_max > 0 && $domain_len > $domain_max ) {
					$domain_message = isset( $dynamic_messages['srfm_email_domain_max_length'] ) && is_string( $dynamic_messages['srfm_email_domain_max_length'] ) && '' !== $dynamic_messages['srfm_email_domain_max_length']
						? $dynamic_messages['srfm_email_domain_max_length']
						/* translators: %s: maximum characters allowed after the @ symbol. */
						: __( 'The part after @ may not exceed %s characters.', 'sureforms' );
					$not_valid_fields[ $key ] = sprintf( $domain_message, $domain_max );
				}
			}
		}

		// Return the array of invalid fields and their error messages.
		// Example: [ 'srfm-email-c867d9d9-lbl-email' => 'This field is required.' ].
		return $not_valid_fields;
	}

	/**
	 * Resolve the Email field character limits (RFC 5321), split on the last @.
	 *
	 * Single source of truth shared by the server validation and the limits localized to the
	 * frontend script, so a filter override applies consistently to both.
	 *
	 * @return array{local:int,domain:int} Resolved limits. A value of 0 disables that check.
	 * @since 2.12.1
	 */
	public static function get_email_char_limits() {
		/**
		 * Filters the Email field character limits (RFC 5321).
		 *
		 * @param array $limits {
		 *     Character limits for the email value, split on the last @.
		 *
		 *     @type int $local  Max characters before the @. 0 disables the check. Default 64.
		 *     @type int $domain Max characters after the @.  0 disables the check. Default 255.
		 * }
		 * @since 2.12.1
		 */
		$email_limits = apply_filters(
			'srfm_email_field_char_limits',
			[
				'local'  => 64,
				'domain' => 255,
			]
		);

		return [
			'local'  => isset( $email_limits['local'] ) ? absint( $email_limits['local'] ) : 64,
			'domain' => isset( $email_limits['domain'] ) ? absint( $email_limits['domain'] ) : 255,
		];
	}

	/**
	 * The walked allowlists for one form, memoised for the request.
	 *
	 * One cache, not one per getter. Both public getters need the same walk, and each
	 * holding its own `static $cache` meant parse_blocks() plus the recursive collect
	 * ran twice for every submission -- once for the ids and again for the slugs --
	 * on a form whose markup can be large.
	 *
	 * Only the walk is memoised. The filtered results deliberately are not: a third
	 * party returning a malformed value would otherwise poison the set for the rest of
	 * the request, and because the lookup short-circuits on isset() the walk would
	 * never be retried.
	 *
	 * @param int $form_id The form post id, already normalised by the callers.
	 * @since 2.12.8
	 * @return array{ids:array<string,true>,slugs:array<string,true>,unsubmittable:array<int,array<string,string>>}
	 */
	private static function get_field_identifiers( $form_id ) {
		static $cache = [];

		if ( ! isset( $cache[ $form_id ] ) ) {
			$cache[ $form_id ] = self::walk_field_identifiers( $form_id );
		}

		return $cache[ $form_id ];
	}

	/**
	 * Walk a form's blocks once, returning both the block-id and slug allowlists.
	 *
	 * @param int $form_id The form post id.
	 * @since 2.12.7
	 * @return array{ids:array<string,true>,slugs:array<string,true>,unsubmittable:array<int,array<string,string>>}
	 */
	private static function walk_field_identifiers( $form_id ) {
		$ids           = [];
		$slugs         = [];
		$unsubmittable = [];
		$post          = get_post( $form_id );

		if ( $post instanceof \WP_Post && ! empty( $post->post_content ) && function_exists( 'parse_blocks' ) ) {
			$visited = [];
			self::collect_field_block_ids( parse_blocks( $post->post_content ), $ids, $slugs, $visited, 0, $unsubmittable );
		}

		return [
			'ids'           => $ids,
			'slugs'         => $slugs,
			'unsubmittable' => $unsubmittable,
		];
	}

	/**
	 * Whether a submitted field key belongs to the form, by block_id or by slug.
	 *
	 * @param string             $key             Submitted field key.
	 * @param array<string,true> $known_block_ids Allowlisted block ids.
	 * @param array<string,true> $known_slugs     Allowlisted field slugs.
	 * @since 2.12.7
	 * @return bool
	 */
	private static function field_key_belongs_to_form( $key, $known_block_ids, $known_slugs ) {
		if ( isset( $known_block_ids[ Helper::get_block_id_from_key( $key ) ] ) ) {
			return true;
		}

		$slug = self::get_slug_from_key( $key );

		return '' !== $slug && isset( $known_slugs[ $slug ] );
	}

	/**
	 * Extract a field's slug from its submitted key.
	 *
	 * A key is `srfm-<type>-<block_id>-lbl-<base64 label>-<slug>`. Helper::encode() is
	 * padding-stripped standard base64 and never contains a hyphen, so the slug is
	 * everything after the first hyphen that follows `-lbl-` (the slug itself may
	 * contain hyphens, which is why only the first is used as the boundary).
	 *
	 * @param string $key Submitted field key.
	 * @since 2.12.7
	 * @return string The slug, or '' when it cannot be derived.
	 */
	private static function get_slug_from_key( $key ) {
		if ( ! is_string( $key ) || false === strpos( $key, '-lbl-' ) ) {
			return '';
		}

		$parts = explode( '-lbl-', $key );
		$after = $parts[1] ?? '';
		$pos   = strpos( $after, '-' );

		return false === $pos ? '' : substr( $after, $pos + 1 );
	}

	/**
	 * Remove unknown child field keys from repeater rows.
	 *
	 * @param array<mixed>       $rows            The repeater's submitted rows.
	 * @param array<string,true> $known_block_ids Map of block ids belonging to the form.
	 * @param array<string,true> $known_slugs     Map of field slugs belonging to the form.
	 * @since 2.12.3
	 * @return array<mixed> The rows with unknown child keys removed.
	 */
	private static function strip_unknown_repeater_keys( $rows, $known_block_ids, $known_slugs = [] ) {
		foreach ( $rows as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			foreach ( array_keys( $row ) as $child_key ) {
				if ( ! is_string( $child_key ) || false === strpos( $child_key, '-lbl-' ) ) {
					continue;
				}

				if ( ! self::field_key_belongs_to_form( $child_key, $known_block_ids, $known_slugs ) ) {
					unset( $row[ $child_key ] );
				}
			}

			$rows[ $index ] = $row;
		}

		return $rows;
	}

	/**
	 * Recursively collect SureForms block ids from a parsed block tree.
	 *
	 * @param array<mixed>                    $blocks  Parsed blocks from parse_blocks().
	 * @param array<string,true>              $ids     Accumulator of block id => true (by reference).
	 * @param array<string,true>              $slugs   Accumulator of field slug => true (by reference).
	 * @param array<int,true>                 $visited Expanded reusable-block post ids, guards cycles.
	 * @param int                             $depth   Current recursion depth, guards pathological trees.
	 * @param array<int,array<string,string>> $unsubmittable Accumulator of the checkbox/GDPR
	 *                                                       fields a browser cannot submit
	 *                                                       when unticked (by reference).
	 * @since 2.12.3
	 * @return void
	 */
	private static function collect_field_block_ids( $blocks, &$ids, &$slugs, &$visited, $depth = 0, &$unsubmittable = [] ) {
		if ( ! is_array( $blocks ) || $depth > 50 ) {
			return;
		}

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$attrs      = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];
			$block_name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';

			// Stored raw, deliberately. The lookup side derives the id from the submitted
			// key via Helper::get_block_id_from_key(), which does not sanitise — putting
			// sanitize_text_field() only on this side would file any id the sanitiser
			// alters under a different string than the one looked up, making a legitimate
			// field permanently unsubmittable. These are map keys used for comparison
			// only; nothing is echoed from here.
			if ( 0 === strpos( $block_name, 'srfm/' ) ) {
				if ( ! empty( $attrs['block_id'] ) && is_string( $attrs['block_id'] ) ) {
					$ids[ $attrs['block_id'] ] = true;
				}
				// Also index by slug. The block_id is rebuilt when the editor recreates a
				// field and can differ between the cached HTML a visitor submitted and the
				// current post_content, but the slug is the stable field identifier — so a
				// slug match keeps a legitimately-submitted field whose block_id drifted.
				if ( ! empty( $attrs['slug'] ) && is_string( $attrs['slug'] ) ) {
					$slugs[ $attrs['slug'] ] = true;
				}

				// A browser posts nothing for an unticked checkbox, so these fields need
				// rebuilding from the form itself -- see backfill_unsubmitted_fields().
				if ( isset( self::UNSUBMITTABLE_BLOCKS[ $block_name ] ) && ! empty( $attrs['block_id'] ) && is_string( $attrs['block_id'] ) ) {
					$unsubmittable[] = [
						'type'     => self::UNSUBMITTABLE_BLOCKS[ $block_name ]['type'],
						'block_id' => $attrs['block_id'],
						'slug'     => isset( $attrs['slug'] ) && is_string( $attrs['slug'] ) ? $attrs['slug'] : '',
						'label'    => ! empty( $attrs['label'] ) && is_string( $attrs['label'] )
							? $attrs['label']
							: self::UNSUBMITTABLE_BLOCKS[ $block_name ]['label'],
					];
				}
			}

			// Expand reusable/synced patterns so fields living inside a pattern count as
			// part of the form.
			if ( 'core/block' === $block_name && ! empty( $attrs['ref'] ) && is_scalar( $attrs['ref'] ) ) {
				$ref = absint( $attrs['ref'] );
				if ( $ref > 0 && ! isset( $visited[ $ref ] ) ) {
					$visited[ $ref ] = true;
					$ref_post        = get_post( $ref );
					if ( $ref_post instanceof \WP_Post && 'wp_block' === $ref_post->post_type && '' !== $ref_post->post_content ) {
						self::collect_field_block_ids( parse_blocks( $ref_post->post_content ), $ids, $slugs, $visited, $depth + 1, $unsubmittable );
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				self::collect_field_block_ids( $block['innerBlocks'], $ids, $slugs, $visited, $depth + 1, $unsubmittable );
			}
		}
	}

	/**
	 * Process payment block configuration.
	 *
	 * @param array<mixed> $attrs Block attributes.
	 * @param array<mixed> $blocks All blocks.
	 * @return array Processed payment configuration.
	 * @since 2.3.0
	 */
	private static function process_payment_block( $attrs, $blocks ) {
		$payment_config = [];

		// Extract payment type (single or subscription).
		$payment_config['payment_type'] = isset( $attrs['paymentType'] ) && is_string( $attrs['paymentType'] ) ? sanitize_text_field( $attrs['paymentType'] ) : 'one-time';

		// Persist subscription plan (interval + billing cycles) for any form that
		// has a subscription path. The admin picks a single value for each in the
		// editor; the server uses these stored values as the source of truth on
		// submit so a tampered interval/cycles in form data cannot redirect Stripe
		// to a different billing cadence.
		if ( in_array( $payment_config['payment_type'], [ 'subscription', 'both' ], true ) && isset( $attrs['subscriptionPlan'] ) && is_array( $attrs['subscriptionPlan'] ) ) {
			if ( isset( $attrs['subscriptionPlan']['interval'] ) && is_string( $attrs['subscriptionPlan']['interval'] ) ) {
				$payment_config['subscription_interval'] = sanitize_text_field( $attrs['subscriptionPlan']['interval'] );
			}
			if ( isset( $attrs['subscriptionPlan']['billingCycles'] ) ) {
				// billingCycles is either an integer count or the string 'ongoing'.
				$cycles_raw                                    = $attrs['subscriptionPlan']['billingCycles'];
				$payment_config['subscription_billing_cycles'] = is_numeric( $cycles_raw ) ? intval( $cycles_raw ) : sanitize_text_field( (string) $cycles_raw );
			}
		}

		// Extract amount type (fixed or minimum).
		$payment_config['amount_type'] = isset( $attrs['amountType'] ) && is_string( $attrs['amountType'] ) ? sanitize_text_field( $attrs['amountType'] ) : 'fixed';

		$payment_config['fixed_amount'] = isset( $attrs['fixedAmount'] ) ? floatval( $attrs['fixedAmount'] ) : 10;

		$payment_config['minimum_amount'] = isset( $attrs['minimumAmount'] ) ? floatval( $attrs['minimumAmount'] ) : 0;

		// Extract variable amount field reference.
		if ( isset( $attrs['variableAmountField'] ) ) {
			$variable_amount_slug                    = sanitize_text_field( $attrs['variableAmountField'] );
			$payment_config['variable_amount_field'] = $variable_amount_slug;

			// Find and add the block name from which the variable amount field comes from.
			if ( ! empty( $variable_amount_slug ) && is_array( $blocks ) ) {
				foreach ( $blocks as $block ) {
					if ( isset( $block['attrs']['slug'] ) && $block['attrs']['slug'] === $variable_amount_slug ) {
						$payment_config['variable_amount_field_block_name'] = $block['blockName'];
						break;
					}
				}
			}
		}

		// BOTH MODE: store per-type amount configs so server-side validation can
		// use the correct config based on which flow the user actually chose.
		if ( 'both' === $payment_config['payment_type'] ) {
			$payment_config['one_time_amount_type']    = isset( $attrs['oneTimeAmountType'] ) && is_string( $attrs['oneTimeAmountType'] ) ? sanitize_text_field( $attrs['oneTimeAmountType'] ) : 'fixed';
			$payment_config['one_time_fixed_amount']   = isset( $attrs['oneTimeFixedAmount'] ) ? floatval( $attrs['oneTimeFixedAmount'] ) : 10;
			$payment_config['one_time_minimum_amount'] = isset( $attrs['oneTimeMinimumAmount'] ) ? floatval( $attrs['oneTimeMinimumAmount'] ) : 0;

			if ( isset( $attrs['oneTimeVariableAmountField'] ) ) {
				$ot_slug = sanitize_text_field( $attrs['oneTimeVariableAmountField'] );
				$payment_config['one_time_variable_amount_field'] = $ot_slug;
				if ( ! empty( $ot_slug ) && is_array( $blocks ) ) {
					foreach ( $blocks as $block ) {
						if ( isset( $block['attrs']['slug'] ) && $block['attrs']['slug'] === $ot_slug ) {
							$payment_config['one_time_variable_amount_field_block_name'] = $block['blockName'];
							break;
						}
					}
				}
			}

			$payment_config['subscription_amount_type']    = isset( $attrs['subscriptionAmountType'] ) && is_string( $attrs['subscriptionAmountType'] ) ? sanitize_text_field( $attrs['subscriptionAmountType'] ) : 'fixed';
			$payment_config['subscription_fixed_amount']   = isset( $attrs['subscriptionFixedAmount'] ) ? floatval( $attrs['subscriptionFixedAmount'] ) : 10;
			$payment_config['subscription_minimum_amount'] = isset( $attrs['subscriptionMinimumAmount'] ) ? floatval( $attrs['subscriptionMinimumAmount'] ) : 0;

			if ( isset( $attrs['subscriptionVariableAmountField'] ) ) {
				$sub_slug = sanitize_text_field( $attrs['subscriptionVariableAmountField'] );
				$payment_config['subscription_variable_amount_field'] = $sub_slug;
				if ( ! empty( $sub_slug ) && is_array( $blocks ) ) {
					foreach ( $blocks as $block ) {
						if ( isset( $block['attrs']['slug'] ) && $block['attrs']['slug'] === $sub_slug ) {
							$payment_config['subscription_variable_amount_field_block_name'] = $block['blockName'];
							break;
						}
					}
				}
			}
		}

		return $payment_config;
	}

	/**
	 * Process dropdown block configuration.
	 *
	 * @param array<mixed> $attrs Block attributes.
	 * @return array Processed dropdown configuration.
	 * @since 2.3.0
	 */
	private static function process_dropdown_block( $attrs ) {
		$dropdown_config = [];

		// Extract required field.
		$dropdown_config['required'] = isset( $attrs['required'] ) && ! empty( $attrs['required'] ) ? true : false;

		// Extract options with their full structure (label, icon, value).
		if ( isset( $attrs['options'] ) && is_array( $attrs['options'] ) ) {
			$sanitized_options = [];
			foreach ( $attrs['options'] as $option ) {
				if ( is_array( $option ) ) {
					$sanitized_options[] = [
						'label' => isset( $option['label'] ) ? sanitize_text_field( $option['label'] ) : '',
						'icon'  => isset( $option['icon'] ) ? sanitize_text_field( $option['icon'] ) : '',
						'value' => isset( $option['value'] ) ? sanitize_text_field( $option['value'] ) : '',
					];
				}
			}
			$dropdown_config['options'] = $sanitized_options;
		}

		// Extract showValues flag.
		$dropdown_config['show_values'] = isset( $attrs['showValues'] ) ? rest_sanitize_boolean( $attrs['showValues'] ) : false;

		// Extract multiSelect flag.
		if ( isset( $attrs['multiSelect'] ) ) {
			$dropdown_config['multi_select'] = rest_sanitize_boolean( $attrs['multiSelect'] );
		}

		// Extract minValue for multi-select validation.
		if ( isset( $attrs['minValue'] ) ) {
			$dropdown_config['min_value'] = absint( $attrs['minValue'] );
		}

		// Extract maxValue for multi-select validation.
		if ( isset( $attrs['maxValue'] ) ) {
			$dropdown_config['max_value'] = absint( $attrs['maxValue'] );
		}

		return $dropdown_config;
	}

	/**
	 * Process multi-choice block configuration.
	 *
	 * @param array<mixed> $attrs Block attributes.
	 * @return array Processed multi-choice configuration.
	 * @since 2.3.0
	 */
	private static function process_multichoice_block( $attrs ) {
		$multichoice_config = [];

		// Extract required field.
		$multichoice_config['required'] = isset( $attrs['required'] ) && ! empty( $attrs['required'] ) ? true : false;

		// Extract singleSelection flag.
		if ( isset( $attrs['singleSelection'] ) ) {
			$multichoice_config['single_selection'] = rest_sanitize_boolean( $attrs['singleSelection'] );
		}

		// Extract minValue for validation.
		if ( isset( $attrs['minValue'] ) ) {
			$multichoice_config['min_value'] = absint( $attrs['minValue'] );
		}

		// Extract maxValue for validation.
		if ( isset( $attrs['maxValue'] ) ) {
			$multichoice_config['max_value'] = absint( $attrs['maxValue'] );
		}

		// Extract options with their full structure (label, icon, value).
		if ( isset( $attrs['options'] ) && is_array( $attrs['options'] ) ) {
			$sanitized_options = [];
			foreach ( $attrs['options'] as $option ) {
				if ( is_array( $option ) ) {
					$sanitized_options[] = [
						'label' => isset( $option['optionTitle'] ) ? trim( sanitize_text_field( $option['optionTitle'] ) ) : '',
						'icon'  => isset( $option['icon'] ) ? sanitize_text_field( $option['icon'] ) : '',
						'value' => isset( $option['value'] ) ? sanitize_text_field( $option['value'] ) : '',
					];
				}
			}
			$multichoice_config['options'] = $sanitized_options;
		}

		// Extract showValues flag.
		if ( isset( $attrs['showValues'] ) ) {
			$multichoice_config['show_values'] = rest_sanitize_boolean( $attrs['showValues'] );
		}

		return $multichoice_config;
	}

	/**
	 * Process textarea block configuration.
	 *
	 * @param array<mixed> $attrs Block attributes.
	 * @return array Processed textarea configuration.
	 * @since 2.8.2
	 */
	private static function process_textarea_block( $attrs ) {
		// Always emit a min_length key so a cleared/invalid value overwrites any
		// previously stored config on save instead of falling back to stale data.
		// Rich-text editors submit HTML markup which would skew mb_strlen counts,
		// so they're treated as "no min-length validation".
		if ( ! empty( $attrs['isRichText'] ) ) {
			return [ 'min_length' => 0 ];
		}

		$min_length = isset( $attrs['minLength'] ) && is_numeric( $attrs['minLength'] ) ? absint( $attrs['minLength'] ) : 0;
		$max_length = isset( $attrs['maxLength'] ) && is_numeric( $attrs['maxLength'] ) ? absint( $attrs['maxLength'] ) : 0;

		// Misconfiguration guard — drop min when it exceeds max so the form stays submittable.
		if ( $max_length > 0 && $min_length > $max_length ) {
			$min_length = 0;
		}

		return [ 'min_length' => $min_length ];
	}

	/**
	 * Process number block configuration.
	 *
	 * @param array<mixed> $attrs Block attributes.
	 * @return array Processed number configuration.
	 * @since 2.4.0
	 */
	private static function process_number_block( $attrs ) {
		$number_config = [];

		// Extract required field.
		if ( isset( $attrs['required'] ) ) {
			$number_config['required'] = ! empty( $attrs['required'] ) ? true : false;
		}

		// Extract format type (us-style or eu-style).
		$number_config['format_type'] = isset( $attrs['formatType'] ) && is_string( $attrs['formatType'] ) ? sanitize_text_field( $attrs['formatType'] ) : 'us-style';

		// Extract min value.
		if ( isset( $attrs['min'] ) ) {
			$number_config['min'] = floatval( $attrs['min'] );
		}

		// Extract max value.
		if ( isset( $attrs['max'] ) ) {
			$number_config['max'] = floatval( $attrs['max'] );
		}

		// Capture calculation config (Pro feature) so a calculation-driven number field used as a
		// payment amount source can be re-derived server-side instead of trusting the submitted
		// value. Harmless when the calculation feature is not in use.
		if ( ! empty( $attrs['enableCalculation'] ) ) {
			$number_config['enableCalculation']  = true;
			$number_config['calculationFormula'] = isset( $attrs['calculationFormula'] ) && is_string( $attrs['calculationFormula'] ) ? $attrs['calculationFormula'] : '';
			// Stored as null when unset so the validator rounds only when a precision is configured.
			$number_config['calculationRound'] = isset( $attrs['calculationRound'] ) && is_numeric( $attrs['calculationRound'] ) ? absint( $attrs['calculationRound'] ) : null;
		}

		return $number_config;
	}
}
