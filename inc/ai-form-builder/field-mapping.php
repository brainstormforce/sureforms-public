<?php
/**
 * SureForms - AI Form Builder.
 *
 * @package sureforms
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
	 * The namespace for the Rest Routes.
	 *
	 * @since x.x.x
	 * @var string
	 */
	private $namespace = 'sureforms/v1';

	/**
	 * Constructor of this class.
	 *
	 * @since x.x.x
	 * @return void
	 */
	// public function __construct() {
	// add_action( 'rest_api_init', [ $this, 'register_route' ] );
	// }

	/**
	 * Register All Routes.
	 *
	 * @hooked - rest_api_init
	 * @since x.x.x
	 * @return void
	 */
	// public function register_route() {
	// register_rest_route(
	// $this->namespace,
	// '/map-fields',
	// [
	// [
	// 'methods'             => \WP_REST_Server::CREATABLE,
	// 'callback'            => [ $this, 'generate_gutenberg_fields_from_questions' ],
	// 'permission_callback' => function () {
	// return current_user_can( 'edit_posts' );
	// },
	// ],
	// ]
	// );
	// }

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
		$questions = $params['form_data']['questions'];

		// Check if questions are null then set it to form_data.
		if ( empty( $questions ) ) {
			$questions = $params['form_data'];
		}

		// if questions is empty then return empty string.
		if ( empty( $questions ) ) {
			return '';
		}

		// Initialize post content string.
		$post_content = '';

		// Loop through questions.
		foreach ( $questions as $question ) {

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
					'label'       => sanitize_text_field( $question['label'] ),
					'required'    => filter_var( $question['required'], FILTER_VALIDATE_BOOLEAN ),
					'placeholder' => sanitize_text_field( $question['placeholder'] ),
					'help'        => sanitize_text_field( $question['helpText'] ),
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
					// Handle specific attributes for certain fields.
					if ( $question['fieldType'] === 'textarea' && ! empty( $question['helpText'] ) ) {
						$merged_attributes['textAreaHelpText'] = sanitize_text_field( $question['helpText'] );
					}
					if ( $question['fieldType'] === 'dropdown' && ! empty( $question['fieldOptions'] ) ) {
						$merged_attributes['options'] = $question['fieldOptions'];
					}
					if ( $question['fieldType'] === 'multi-choice' ) {
						if ( ! empty( $question['fieldOptions'] ) ) {
							$merged_attributes['options'] = $question['fieldOptions'];
						}
						if ( ! empty( $question['singleSelection'] ) ) {
							$merged_attributes['singleSelection'] = filter_var( $question['singleSelection'], FILTER_VALIDATE_BOOLEAN );
						}
					}

					$post_content .= '<!-- wp:srfm/' . $question['fieldType'] . ' ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'number-slider':
				case 'page-break':
				case 'date-time-picker':
				case 'upload':
				case 'hidden':
				case 'rating':
				case 'phone':
					// If pro version is not active then do not add pro fields.
					if ( ! defined( 'SRFM_PRO_VER' ) ) {
						break;
					}
					// Handle specific attributes for certain pro fields.
					if ( $question['fieldType'] === 'upload' ) {
						if ( ! empty( $question['allowedFormats'] ) ) {
							$merged_attributes['allowedFormats'] = $question['allowedFormats'];
						}
						if ( ! empty( $question['fileSizeLimit'] ) ) {
							$merged_attributes['fileSizeLimit'] = filter_var( $question['fileSizeLimit'], FILTER_VALIDATE_INT );
						}
					}
					if ( $question['fieldType'] === 'rating' && ! empty( $question['helpText'] ) ) {
						$merged_attributes['ratingBoxHelpText'] = sanitize_text_field( $question['helpText'] );
					}
					if ( $question['fieldType'] === 'phone' ) {
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
