<?php
/**
 * SRFM NPS Notice.
 *
 * @since x.x.x
 *
 * @package sureforms
 */

namespace SRFM\Inc;

use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Traits\Get_Instance;

use Nps_Survey;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SRFM_NPS_Notice' ) ) {

	/**
	 * Class SRFM_NPS_Notice
	 */
	class SRFM_NPS_Notice {
		use Get_Instance;

		/**
		 * Constructor.
		 *
		 * @since x.x.x
		 */
		private function __construct() {
			add_action( 'admin_footer', [ $this, 'show_nps_notice' ], 999 );

			// Display the NPS survey only on SureForms pages.
			add_filter(
				'nps_survey_allowed_screens',
				static function ( $screens ) {
					return [
						'toplevel_page_sureforms_menu',
						'sureforms_page_sureforms_form_settings',
						'sureforms_page_sureforms_entries',
						'sureforms_page_add-new-form',
					];
				}
			);
		}

		/**
		 * Count the number of published forms and number form submissions.
		 * Return whether the NPS survey should be shown or not.
		 *
		 * @since x.x.x
		 * @return bool
		 */
		public function maybe_display_nps_survey() {
			$form_count    = wp_count_posts( SRFM_FORMS_POST_TYPE )->publish; // Get the number of published forms.
			$entries_count = Entries::get_total_entries_by_status( '' ); // Get the number of form submissions.

			// Show the NPS survey if there are at least 3 published forms or 3 form submissions.
			if ( $form_count >= 3 || $entries_count >= 3 ) {
				return true;
			}
			return false;
		}

		/**
		 * Render NPS Survey
		 *
		 * @since x.x.x
		 * @return void
		 */
		public function show_nps_notice() {
			// Ensure the Nps_Survey class exists before proceeding.
			if ( ! class_exists( 'Nps_Survey' ) ) {
				return;
			}

			/**
			 * Check if the constant WEEK_IN_SECONDS is already defined.
			 * This ensures that the constant is not redefined if it's already set by WordPress or other parts of the code.
			 */
			if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
				// Define the WEEK_IN_SECONDS constant with the value of 604800 seconds (equivalent to 7 days).
				define( 'WEEK_IN_SECONDS', 604800 );
			}

			// Display the NPS survey.
			Nps_Survey::show_nps_notice(
				'nps-survey-sureforms',
				[
					'show_if'          => $this->maybe_display_nps_survey(),
					'dismiss_timespan' => WEEK_IN_SECONDS,
					'display_after'    => 0,
					'plugin_slug'      => 'srfm',
					'message'          => [
						'logo'                  => esc_url( plugin_dir_url( __DIR__ ) . 'admin/assets/sureforms-logo.png' ),
						'plugin_name'           => __( 'SureForms', 'sureforms' ),
						'nps_rating_message'    => __( 'How likely are you to recommend SureForms to your friends or colleagues?', 'sureforms' ),
						'feedback_title'        => __( 'Thanks a lot for your feedback! 😍', 'sureforms' ),
						'feedback_content'      => __( 'Could you please do us a favor and give us a 5-star rating on WordPress? It would help others choose SureForms with confidence. Thank you!', 'sureforms' ),
						'plugin_rating_link'    => esc_url( 'https://wordpress.org/support/plugin/sureforms/reviews/#new-post' ),
						'plugin_rating_title'   => __( 'Thank you for your feedback', 'sureforms' ),
						'plugin_rating_content' => __( 'We value your input. How can we improve your experience?', 'sureforms' ),

					],

				]
			);
		}
	}
}
