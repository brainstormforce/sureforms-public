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
			// Extract the part after the last '-' in the key, if it matches the pattern.
			// Example: $get_name_with_id[0] = "srfm-email-c867d9d9".
			// $extracted_id = "c867d9d9".
			$extracted_id = '';
			if ( is_string( $key ) && preg_match( '/-([a-zA-Z0-9]+)$/', $get_name_with_id[0], $matches ) ) {
				$extracted_id = $matches[1];
				// Now $extracted_id contains "c867d9d9" for "srfm-email-c867d9d9".
			}

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
		}

		// Return the array of invalid fields and their error messages.
		// Example: [ 'srfm-email-c867d9d9-lbl-email' => 'This field is required.' ].
		return $not_valid_fields;
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

		return $number_config;
	}
}
