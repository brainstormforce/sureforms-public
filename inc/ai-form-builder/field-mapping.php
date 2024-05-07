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
     * @since 1.0.0
     * @var string
     */
    private $namespace = 'sureforms/v1';

    public function __construct() {
		// Setup the Sidebar Rest Routes.
		add_action( 'rest_api_init', array( $this, 'register_route' ) );

		// Setup the Sidebar Auth Ajax.
		// add_action( 'wp_ajax_verify_zip_ai_authenticity', array( $this, 'verify_authenticity' ) );

		// add_action( 'admin_bar_menu', array( $this, 'add_admin_trigger' ), 999 );

		// Render the Sidebar React App in the Footer in the Gutenberg Editor, Admin, and the Front-end.
		// add_action( 'admin_footer', array( $this, 'render_sidebar_markup' ) );
		// add_action( 'wp_footer', array( $this, 'render_sidebar_markup' ) );

		// Add the Sidebar to the Gutenberg Editor, Admin, and the Front-end.
		// add_action( 'admin_enqueue_scripts', array( $this, 'load_sidebar_assets' ) );
		// add_action( 'wp_enqueue_scripts', array( $this, 'load_sidebar_assets' ) );
	}

	/**
	 * Register All Routes.
	 *
	 * @hooked - rest_api_init
	 * @since 1.0.0
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			$this->namespace,
			'/map-fields',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'generate_gutenberg_fields_from_questions' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					}
				),
			)
		);
	}
    public static function generate_gutenberg_fields_from_questions($request) {

        // Get questions from request
        $params = $request->get_params();

        // get form_data from request

        $questions = $params['form_data']['questions'];

        if ( $questions === null ){
            $questions = $params['form_data'];
        }

        

        // Initialize post content string
        $post_content = '';
    
        // Loop through questions
        foreach ($questions as $index => $question) {
            // Initialize common attributes
            $common_attributes = [
                'block_id' => bin2hex(random_bytes(4)), // Generate random block_id
                // 'required' => true, // Default required attribute
                'formId' => 0 // Set your formId here
            ];
    
            // Merge common attributes with question attributes
            $merged_attributes = array_merge($common_attributes, [
                'label' => $question['label'],
                'required' => $question['required'],
                'placeholder' => $question['placeholder'],
                'help' => $question['helpText'],
                'fieldWidth' => $question['fieldWidth']
            ]);

            // exclude label in page-break

            // if ($question['fieldType'] === 'page-break') {
            //     unset($merged_attributes['label']);
            // }
    
            // Determine field type based on fieldType
            switch ($question['fieldType']) {
                case 'input':
                    $post_content .= '<!-- wp:srfm/input ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                case 'email':
                    $post_content .= '<!-- wp:srfm/email ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                case 'number':
                    $post_content .= '<!-- wp:srfm/number ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                case 'textarea':
                    $post_content .= '<!-- wp:srfm/textarea ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                case 'dropdown':
                    // Check if fieldOptions are provided
                    if (isset($question['fieldOptions']) && is_array($question['fieldOptions'])) {
                        $merged_attributes['options'] = $question['fieldOptions'];
                    } else {
                        // Default options
                        $merged_attributes['options'] = ['Option 1', 'Option 2', 'Option 3'];
                    }
                    $post_content .= '<!-- wp:srfm/dropdown ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                case 'checkbox':
                    $post_content .= '<!-- wp:srfm/checkbox ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                case 'date-time':
                    $post_content .= '<!-- wp:srfm/date-time-picker ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                case 'upload':
                    $post_content .= '<!-- wp:srfm/upload ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                    // case for address
                case 'address':
                    $post_content .= '<!-- wp:srfm/address ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                    // case for address-compact
                case 'address-compact':
                    $post_content .= '<!-- wp:srfm/address-compact ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                    // case for inline-button
                case 'inline-button':
                    $post_content .= '<!-- wp:srfm/inline-button ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                    // case for hidden
                case 'hidden':
                    $post_content .= '<!-- wp:srfm/hidden ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                    // case for rating
                case 'rating':
                    if (isset($question['fieldOptions']) && is_array($question['fieldOptions'])) {
                        $merged_attributes['ratingBoxHelpText'] = $question['helpText'];
                    }
                    $post_content .= '<!-- wp:srfm/rating ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                    // case for phone
                case 'phone':
                    $post_content .= '<!-- wp:srfm/phone ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                    // case for gdpr
                case 'gdpr':
                    $post_content .= '<!-- wp:srfm/gdpr ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                    // case for number-slider
                case 'number-slider':
                    $post_content .= '<!-- wp:srfm/number-slider ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                    // case for page-break
                case 'page-break':
                    $post_content .= '<!-- wp:srfm/page-break ' . json_encode($common_attributes) . ' /-->' . PHP_EOL;
                    break;
                    // multi choice
                case 'multi-choice':
                    // Check if fieldOptions are provided
                    if (isset($question['fieldOptions']) && is_array($question['fieldOptions'])) {
                        $merged_attributes['options'] = $question['fieldOptions'];
                        $merged_attributes['singleSelection'] = $question['singleSelection'];
                    } else {
                        // Default options
                        $merged_attributes['options'] = array(
                            array("optionTitle" => "Option 1"),
                            array("optionTitle" => "Option 2"),
                            array("optionTitle" => "Option 3"),
                            array("optionTitle" => "Option 4")
                        );
                    }
                    $post_content .= '<!-- wp:srfm/multi-choice ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
                    break;
                    // case for multi-choice
                default:
                    // Unsupported field type
                    $post_content .= '<!-- wp:srfm/' . $question['fieldType'] . ' ' . json_encode($merged_attributes) . ' /-->' . PHP_EOL;
            }
        }
    
        return $post_content;
    }
    
    
        
}
