<?php
/**
 * Sureforms export.
 *
 * @package sureforms.
 * @since X.X.X
 */

namespace SureForms\Inc;
use SureForms\Inc\Traits\Get_Instance;
/**
 * Load Defaults Class.
 *
 * @since X.X.X
 */
class SRFM_Export {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * @since  X.X.X
	 */
	public function __construct() {
		add_action('wp_ajax_export_form', 'handle_export_form');
        add_action('wp_ajax_nopriv_export_form', 'handle_export_form');
	}

    /**
     * Undocumented function
     *
     * @since x.x.x
     * @return void
     */
	public function handle_export_form() {
        $postId = $_POST['post_id'];
      
        // Fetch the form data
        $post = get_post($postId);
      
        $jsonData = json_encode($post);
      
        // Return the JSON data
        echo $jsonData;
        die();
      }
}
