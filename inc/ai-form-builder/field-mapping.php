<?php
/**
 * SureForms - AI Form Builder.
 *
 * @package sureforms
 * @since 0.0.8
 */

namespace SRFM\Inc\AI_Form_Builder;

use SRFM\Inc\Helper;
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
		if ( empty( $params ) || ! is_array( $params ) || ! isset( $params['form_data'] ) || 0 === count( $params['form_data'] ) ) {
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
					if ( 'dropdown' === $question['fieldType'] && ! empty( $question['fieldOptions'] ) && is_array( $question['fieldOptions'] ) &&
					! empty( $question['fieldOptions'][0]['label'] )
					) {
						$merged_attributes['options'] = $question['fieldOptions'];

						// remove icon from options for the dropdown field.
						foreach ( $merged_attributes['options'] as $key => $option ) {
							if ( ! empty( $merged_attributes['options'][ $key ]['icon'] ) ) {
								$merged_attributes['options'][ $key ]['icon'] = '';
							}
						}
					}
					if ( 'multi-choice' === $question['fieldType'] ) {

						// Remove duplicate icons and clear icons if all are the same.
						$icons        = array_column( $question['fieldOptions'], 'icon' );
						$options      = array_column( $question['fieldOptions'], 'optionTitle' );
						$unique_icons = array_unique( $icons );
						if ( count( $unique_icons ) === 1 || count( $options ) !== count( $icons ) ) {
							foreach ( $question['fieldOptions'] as &$option ) {
								$option['icon'] = '';
							}
						}

						// Set options if they are valid.
						if ( ! empty( $question['fieldOptions'][0]['optionTitle'] ) ) {
							$merged_attributes['options'] = $question['fieldOptions'];
						}

						// Determine vertical layout based on icons.
						if ( ! empty( $merged_attributes['options'] ) ) {
							$merged_attributes['verticalLayout'] = array_reduce(
								$merged_attributes['options'],
								static fn( $carry, $option ) => $carry && ! empty( $option['icon'] ),
								true
							);
						}

						// Set single selection if provided.
						if ( isset( $question['singleSelection'] ) ) {
							$merged_attributes['singleSelection'] = filter_var( $question['singleSelection'], FILTER_VALIDATE_BOOLEAN );
						}

						// Set choiceWidth for options divisible by 3.
						if ( ! empty( $merged_attributes['options'] ) && count( $merged_attributes['options'] ) % 3 === 0 ) {
							$merged_attributes['choiceWidth'] = 33.33;
						}
					}
					if ( 'phone' === $question['fieldType'] ) {
						$merged_attributes['autoCountry'] = true;
					}

					$post_content .= '<!-- wp:srfm/' . $question['fieldType'] . ' ' . Helper::encode_json( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'slider':
				case 'page-break':
				case 'date-picker':
				case 'time-picker':
				case 'upload':
				case 'hidden':
				case 'rating':
					// If pro version is not active then do not add pro fields.
					if ( ! defined( 'SRFM_PRO_VER' ) ) {
						break;
					}

					// Handle specific attributes for certain pro fields.
					if ( 'date-picker' === $question['fieldType'] ) {
						$merged_attributes['dateFormat'] = ! empty( $question['dateFormat'] ) ? sanitize_text_field( $question['dateFormat'] ) : 'mm/dd/yy';
						$merged_attributes['min']        = ! empty( $question['minDate'] ) ? sanitize_text_field( $question['minDate'] ) : '';
						$merged_attributes['max']        = ! empty( $question['maxDate'] ) ? sanitize_text_field( $question['maxDate'] ) : '';
					}
					if ( 'time-picker' === $question['fieldType'] ) {
						$merged_attributes['increment']            = ! empty( $question['increment'] ) ? filter_var( $question['increment'], FILTER_VALIDATE_INT ) : 30;
						$merged_attributes['showTwelveHourFormat'] = ! empty( $question['showTwelveHourFormat'] ) ? filter_var( $question['useTwelveHourFormat'], FILTER_VALIDATE_BOOLEAN ) : false;
						$merged_attributes['min']                  = ! empty( $question['minTime'] ) ? sanitize_text_field( $question['minTime'] ) : '';
						$merged_attributes['max']                  = ! empty( $question['maxTime'] ) ? sanitize_text_field( $question['maxTime'] ) : '';
					}
					if ( 'rating' === $question['fieldType'] ) {
						$merged_attributes['iconShape']     = ! empty( $question['iconShape'] ) ? sanitize_text_field( $question['iconShape'] ) : 'star';
						$merged_attributes['showText']      = ! empty( $question['showTooltip'] ) ? filter_var( $question['showTooltip'], FILTER_VALIDATE_BOOLEAN ) : false;
						$merged_attributes['defaultRating'] = ! empty( $question['defaultRating'] ) ? filter_var( $question['defaultRating'], FILTER_VALIDATE_INT ) : 0;

						if ( ! empty( $merged_attributes['showText'] ) ) {
							foreach ( $question['tooltipValues'] as $tooltips ) {
								$i = 0;
								foreach ( $tooltips as $value ) {
									$merged_attributes['ratingText'][ $i ] = ! empty( $value ) ? sanitize_text_field( $value ) : '';
									$i++;
								}
							}
						}
					}
					if ( 'upload' === $question['fieldType'] ) {
						if ( ! empty( $question['allowedTypes'] ) ) {
							$allowed_types = str_replace( '.', '', $question['allowedTypes'] );

							$allowed_types = explode( ',', $allowed_types );

							$types_array = array_map(
								static function( $type ) {
									return [
										'value' => trim( $type ),
										'label' => trim( $type ),
									];
								},
								$allowed_types
							);

							$merged_attributes['allowedFormats'] = $types_array;
						} else {
							$merged_attributes['allowedFormats'] = [
								[
									'value' => 'jpg',
									'label' => 'jpg',
								],
								[
									'value' => 'jpeg',
									'label' => 'jpeg',
								],
								[
									'value' => 'gif',
									'label' => 'gif',
								],
								[
									'value' => 'png',
									'label' => 'png',
								],
								[
									'value' => 'pdf',
									'label' => 'pdf',
								],
							];
						}

						$merged_attributes['fileSizeLimit'] = ! empty( $question['uploadSize'] ) ? filter_var( $question['uploadSize'], FILTER_VALIDATE_INT ) : 10;

						$merged_attributes['multiple'] = ! empty( $question['multiUpload'] ) ? filter_var( $question['multiUpload'], FILTER_VALIDATE_BOOLEAN ) : false;

						$merged_attributes['maxFiles'] = ! empty( $question['multiFilesNumber'] ) ? filter_var( $question['multiFilesNumber'], FILTER_VALIDATE_INT ) : 2;

					}

					$post_content .= '<!-- wp:srfm/' . $question['fieldType'] . ' ' . Helper::encode_json( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				default:
					// Unsupported field type - fallback to input.
					$post_content .= '<!-- wp:srfm/input ' . Helper::encode_json( $merged_attributes ) . ' /-->' . PHP_EOL;
			}
		}

		return $post_content;
	}

}
