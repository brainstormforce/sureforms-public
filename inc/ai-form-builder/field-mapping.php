<?php
/**
 * SureForms - AI Form Builder.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace SRFM\Inc\AI_Form_Builder;

use SRFM\Inc\Traits\Get_Instance;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SureForms AI Form Builder Class.
 */
class Field_Mapping {
	use Get_Instance;

	/**
	 * Generate Gutenberg Fields from AI data.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return string
	 */
	public static function generate_gutenberg_fields_from_questions( $request ) {

		// Get params from request.
		$params = $request->get_params();

		// check parama is empty or not and is an array and consist form_data key.
		if ( empty( $params ) || ! is_array( $params ) || ! array_key_exists( 'form_data', $params ) || 0 === count( $params['form_data'] ) ) {
			return '';
		}

		// Get questions from form data.
		$form_data = $params['form_data'];
		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			return '';
		}

		$form = $form_data['form'];
		if ( empty( $form ) || ! is_array( $form ) ) {
			return '';
		}

		$form_fields = $form['formFields'];
		// if questions is empty then return empty string.
		if ( empty( $form_fields ) || ! is_array( $form ) ) {
			return '';
		}

		// Initialize post content string.
		$post_content = '';

		// Loop through questions.
		foreach ( $form_fields as $question ) {

			// Check if question is empty then continue to next question.
			if ( empty( $question ) || ! is_array( $question ) ) {
				return '';
			}

			// Initialize common attributes.
			$common_attributes = [
				'block_id' => bin2hex( random_bytes( 4 ) ), // Generate random block_id.
				'formId'   => 0, // Set your formId here.
			];

			// Merge common attributes with question attributes.
			$merged_attributes = array_merge(
				$common_attributes,
				[
					'label'    => sanitize_text_field( $question['label'] ),
					'required' => filter_var( $question['required'], FILTER_VALIDATE_BOOLEAN ),
					'help'     => sanitize_text_field( $question['helpText'] ),
				]
			);

			// Determine field type based on fieldType.
			switch ( $question['fieldType'] ) {
				case 'input':
				case 'email':
				case 'number':
				case 'textarea':
				case 'dropdown':
				case 'checkbox':
				case 'address':
				case 'inline-button':
				case 'gdpr':
				case 'multi-choice':
				case 'url':
				case 'phone':
					// Handle specific attributes for certain fields.
					if ( 'textarea' === $question['fieldType'] && ! empty( $question['helpText'] ) ) {
						$merged_attributes['textAreaHelpText'] = sanitize_text_field( $question['helpText'] );
					}
					if ( 'dropdown' === $question['fieldType'] && ! empty( $question['fieldOptions'] ) && is_array( $question['fieldOptions'] ) &&
					! empty( $question['fieldOptions'][0]['label'] )
					) {
						$merged_attributes['options'] = $question['fieldOptions'];
					}
					if ( 'multi-choice' === $question['fieldType'] ) {
						if ( ! empty( $question['fieldOptions'] ) && is_array( $question['fieldOptions'] )
						&& ! empty( $question['fieldOptions'][0]['optionTitle'] )
						) {
							$merged_attributes['options'] = $question['fieldOptions'];
						}
						if ( ! empty( $question['singleSelection'] ) ) {
							$merged_attributes['singleSelection'] = filter_var( $question['singleSelection'], FILTER_VALIDATE_BOOLEAN );
						}
					}
					// if checkbox then map help to checkboxHelpText.
					if ( 'checkbox' === $question['fieldType'] && ! empty( $question['helpText'] ) ) {
						$merged_attributes['checkboxHelpText'] = sanitize_text_field( $question['helpText'] );
					}
					// if gdpr then map help to gdprHelpText.
					if ( 'gdpr' === $question['fieldType'] && ! empty( $question['helpText'] ) ) {
						$merged_attributes['gdprHelpText'] = sanitize_text_field( $question['helpText'] );
					}

					$post_content .= '<!-- wp:srfm/' . $question['fieldType'] . ' ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'number-slider':
				case 'page-break':
				case 'date-time-picker':
				case 'upload':
				case 'hidden':
				case 'rating':
					// If pro version is not active then do not add pro fields.
					if ( ! defined( 'SRFM_PRO_VER' ) ) {
						break;
					}
					// Handle specific attributes for certain pro fields.
					if ( 'upload' === $question['fieldType'] ) {
						if ( ! empty( $question['allowedFormats'] ) ) {
							$merged_attributes['allowedFormats'] = $question['allowedFormats'];
						}
						if ( ! empty( $question['fileSizeLimit'] ) ) {
							$merged_attributes['fileSizeLimit'] = filter_var( $question['fileSizeLimit'], FILTER_VALIDATE_INT );
						}
					}
					if ( 'rating' === $question['fieldType'] && ! empty( $question['helpText'] ) ) {
						$merged_attributes['ratingBoxHelpText'] = sanitize_text_field( $question['helpText'] );
					}
					if ( 'phone' === $question['fieldType'] ) {
						$merged_attributes['autoCountry'] = true;
					}

					$post_content .= '<!-- wp:srfm/' . $question['fieldType'] . ' ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				default:
					// Unsupported field type - fallback to input.
					$post_content .= '<!-- wp:srfm/input ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
			}
		}

		return $post_content;
	}

}
