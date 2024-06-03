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
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_route' ] );
	}

	/**
	 * Register All Routes.
	 *
	 * @hooked - rest_api_init
	 * @since x.x.x
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			$this->namespace,
			'/map-fields',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'generate_gutenberg_fields_from_questions' ],
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				],
			]
		);
	}

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
		if ( empty( $params ) || ! is_array( $params ) || ! array_key_exists( 'form_data', $params ) ) {
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
		foreach ( $questions as $index => $question ) {

			// Check if question is empty then continue to next question.
			if ( empty( $question ) ) {
				continue;
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
					'label'       => $question['label'],
					'required'    => $question['required'],
					'placeholder' => $question['placeholder'],
					'help'        => $question['helpText'],
				]
			);

			// Determine field type based on fieldType.
			switch ( $question['fieldType'] ) {
				case 'input':
					$post_content .= '<!-- wp:srfm/input ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'email':
					$post_content .= '<!-- wp:srfm/email ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'number':
					$post_content .= '<!-- wp:srfm/number ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'textarea':
					// Check if helpText is provided.
					if ( ! empty( $question['helpText'] ) ) {
						$merged_attributes['textAreaHelpText'] = $question['helpText'];
					}
					$post_content .= '<!-- wp:srfm/textarea ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'dropdown':
					// Check if fieldOptions are provided.
					if ( ! empty( $question['fieldOptions'] ) ) {
						$merged_attributes['options'] = $question['fieldOptions'];
					}
					$post_content .= '<!-- wp:srfm/dropdown ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'checkbox':
					$post_content .= '<!-- wp:srfm/checkbox ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'date-time':
					$post_content .= '<!-- wp:srfm/date-time-picker ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'upload':
					// Check if allowedFormats and fileSizeLimit are provided.
					if ( ! empty( $question['allowedFormats'] ) ) {
						$merged_attributes['allowedFormats'] = $question['allowedFormats'];
					}
					if ( ! empty( $question['fileSizeLimit'] ) ) {
						$merged_attributes['fileSizeLimit'] = $question['fileSizeLimit'];
					}
					$post_content .= '<!-- wp:srfm/upload ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'address':
					$post_content .= '<!-- wp:srfm/address ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'address-compact':
					$post_content .= '<!-- wp:srfm/address-compact ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'inline-button':
					$post_content .= '<!-- wp:srfm/inline-button ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'hidden':
					$post_content .= '<!-- wp:srfm/hidden ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'rating':
					if ( ! empty( $question['helpText'] ) ) {
						$merged_attributes['ratingBoxHelpText'] = $question['helpText'];
					}
					$post_content .= '<!-- wp:srfm/rating ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'phone':
					$merged_attributes['autoCountry'] = true;
					$post_content                    .= '<!-- wp:srfm/phone ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'gdpr':
					$post_content .= '<!-- wp:srfm/gdpr ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'number-slider':
					$post_content .= '<!-- wp:srfm/number-slider ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'page-break':
					$post_content .= '<!-- wp:srfm/page-break ' . wp_json_encode( $common_attributes ) . ' /-->' . PHP_EOL;
					break;
				case 'multi-choice':
					// check if fieldOptions or singleSelection is provided.
					if ( ! empty( $question['fieldOptions'] ) ) {
						$merged_attributes['options'] = $question['fieldOptions'];
					}
					if ( ! empty( $question['singleSelection'] ) ) {
						$merged_attributes['singleSelection'] = $question['singleSelection'];
					}
					$post_content .= '<!-- wp:srfm/multi-choice ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
					break;
				default:
					// Unsupported field type - fallback to input.
					$post_content .= '<!-- wp:srfm/' . $question['fieldType'] . ' ' . wp_json_encode( $merged_attributes ) . ' /-->' . PHP_EOL;
			}
		}

		return $post_content;
	}

}
