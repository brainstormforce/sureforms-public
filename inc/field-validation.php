<?php
/**
 * Field Validation Class
 *
 * Handles all field validation for SureForms
 *
 * @package SureForms
 * @since x.x.x
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
	 * Constructor
	 */
	public function __construct() {
		// $this->init_hooks();
	}

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
	 * @since x.x.x
	 */
	public static function add_block_config( $blocks, $form_id ) {
		// Initialize array to store processed block configurations.
		$block_config = [];

		// Loop through each block.
		foreach ( $blocks as $block ) {
			// Skip if block name is not set.
			if ( is_array( $block ) && ! isset( $block['blockName'] ) ) {
				continue;
			}

			// Allow extensions to process and modify block config.
			$config = apply_filters( 'srfm_block_config', [ 'block' => $block ] );

			// If block was processed by a filter, add its processed value.
			if ( isset( $config['is_processed'] ) && true === $config['is_processed'] ) {
				$block_config[] = $config['process_value'];
				continue;
			}
		}

		// Only update meta if we have processed configurations.
		if ( ! empty( $block_config ) ) {
			update_post_meta( $form_id, '_srfm_block_config', $block_config );
		}
	}

	/**
	 * Retrieve or migrate the block configuration for legacy forms.
	 *
	 * This function checks if the _srfm_block_config post meta exists for the given form ID.
	 * If not, it attempts to parse the form's post content and generate the block config.
	 *
	 * @param int $form_id The ID of the form post.
	 * @since x.x.x
	 * @return array|null The block configuration array, or null if not found or invalid.
	 */
	public static function get_or_migrate_block_config_for_legacy_form( $form_id ) {
		// Validate that $form_id is a positive integer.
		if ( ! is_int( $form_id ) || $form_id <= 0 ) {
			return null;
		}

		// Retrieve the block config from post meta.
		$block_config = get_post_meta( $form_id, '_srfm_block_config', true );
		if ( ! empty( $block_config ) && is_array( $block_config ) ) {
			// If it exists and is an array, return it directly (no migration needed).
			return $block_config;
		}

		// Get the post by ID and validate.
		$post = get_post( $form_id );
		if ( ! ( $post instanceof \WP_Post ) || empty( $post->post_content ) ) {
			return null;
		}

		// Parse the blocks from the post content and attempt migration.
		if ( function_exists( 'parse_blocks' ) ) {
			$blocks = parse_blocks( $post->post_content );
			if ( is_array( $blocks ) && ! empty( $blocks ) ) {
				// Helper::add_block_config( $blocks, $form_id );
				Field_Validation::add_block_config( $blocks, $form_id );
			}
		}

		// Retrieve the block config again after migration attempt.
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
	 * @since x.x.x
	 * @return array|null The processed form configuration array, or null if not found.
	 */
	public static function prepared_validation_data( $current_form_id ) {
		// Retrieve the form block configuration from post meta.
		$get_form_config = self::get_or_migrate_block_config_for_legacy_form( $current_form_id );

		// If the configuration is an array, add a 'name_with_id' key to each block.
		if ( is_array( $get_form_config ) ) {
			foreach ( $get_form_config as $index => $block ) {
				// Ensure both 'blockName' and 'block_id' exist before creating the identifier.
				if ( isset( $block['blockName'] ) && isset( $block['block_id'] ) ) {
					// 'name_with_id' is used as a unique field identifier for validation.
					$get_form_config[ $index ]['name_with_id'] = str_replace( '/', '-', $block['blockName'] ) . '-' . $block['block_id'];
				}
			}
		}

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
	 * @since x.x.x
	 * @return array An array of invalid fields and their error messages. Empty if all fields are valid.
	 */
	public static function validate_form_data( $form_data, $current_form_id ) {
		if ( ! is_array( $form_data ) || ! is_numeric( $current_form_id ) ) {
			return [];
		}

		// Initialize an array to hold fields that are not valid.
		$not_valid_fields = [];

		// Retrieve the processed form configuration for validation.
		$get_form_config = self::prepared_validation_data( Helper::get_integer_value( $current_form_id ) );

		// Merge uploaded files into the form data for validation, if any files were uploaded.
		if ( ! empty( $_FILES ) ) {
			$form_data = array_merge( $form_data, $_FILES );
		}

		// Iterate over each field in the form data.
		foreach ( $form_data as $key => $value ) {
			/**
			 * Only process SureForms fields.
			 * The '-lbl-' substring is mandatory in SureForms field keys.
			 */
			if ( false === str_contains( $key, '-lbl-' ) ) {
				continue;
			}

			// Apply the validation filter for the current field.
			$field_validated = apply_filters(
				'srfm_validate_form_data',
				[
					'field_key'   => $key,
					'field_value' => $value,
					'form_id'     => $current_form_id,
					'form_config' => $get_form_config,
				]
			);

			// Check the result of the validation.
			if ( isset( $field_validated['validated'] ) ) {
				// If the field is valid, skip to the next field.
				if ( true === $field_validated['validated'] ) {
					continue;
				}

				// If the field is not valid, add the error message to the result array.
				if ( false === $field_validated['validated'] ) {
					$not_valid_fields[ $key ] = $field_validated['error'] ?? __( 'Field is not valid.', 'sureforms' );
				}
			}
		}

		// Return the array of invalid fields and their error messages.
		return $not_valid_fields;
	}
}
