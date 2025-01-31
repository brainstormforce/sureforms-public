<?php
/**
 * SRFM NPS Notice.
 *
 * @since 1.2.2
 *
 * @package sureforms
 */

namespace SRFM\Inc;

use Nps_Survey;
use SRFM\Inc\Database\Tables\Entries;
use SRFM\Inc\Traits\Get_Instance;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Nps_Notice' ) ) {

	/**
	 * Class Nps_Notice
	 */
	class Nps_Notice {
		use Get_Instance;

		/**
		 * Array of allowed screens where the NPS survey should be displayed.
		 * This ensures that the NPS survey is only displayed on SureForms pages.
		 *
		 * @var array<string>
		 * @since 1.2.2
		 */
		private static $allowed_screens = [
			'toplevel_page_sureforms_menu',
			'sureforms_page_sureforms_form_settings',
			'sureforms_page_sureforms_entries',
			'edit-sureforms_form',
		];

		/**
		 * Constructor.
		 *
		 * @since 1.2.2
		 */
		private function __construct() {
			add_action( 'admin_footer', [ $this, 'show_nps_notice' ], 999 );
			add_filter( 'nps_survey_post_data', [ $this, 'update_nps_survey_post_data' ] );
		}

		/**
		 * Count the number of published forms and number form submissions.
		 * Return whether the NPS survey should be shown or not.
		 *
		 * @since 1.2.2
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
		 * @since 1.2.2
		 * @return void
		 */
		public function show_nps_notice() {
			// Ensure the Nps_Survey class exists before proceeding.
			if ( ! class_exists( 'Nps_Survey' ) ) {
				return;
			}

			// Display the NPS Survey only on SureForms pages and avoid conflicts with other plugins.
			if ( ! Helper::is_sureforms_admin_page() ) {
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
					'dismiss_timespan' => 2 * WEEK_IN_SECONDS,
					'display_after'    => 0,
					'plugin_slug'      => 'sureforms',
					'show_on_screens'  => self::$allowed_screens,
					'message'          => [
						'logo'                        => esc_url( plugin_dir_url( __DIR__ ) . 'admin/assets/sureforms-logo.png' ),
						'plugin_name'                 => __( 'SureForms', 'sureforms' ),
						'nps_rating_message'          => __( 'How likely are you to recommend SureForms to your friends or colleagues?', 'sureforms' ),
						'feedback_title'              => __( 'Thanks a lot for your feedback! 😍', 'sureforms' ),
						'feedback_content'            => __( 'Could you please do us a favor and give us a 5-star rating on WordPress? It would help others choose SureForms with confidence. Thank you!', 'sureforms' ),
						'plugin_rating_link'          => esc_url( 'https://wordpress.org/support/plugin/sureforms/reviews/#new-post' ),
						'plugin_rating_title'         => __( 'Thank you for your feedback', 'sureforms' ),
						'plugin_rating_content'       => __( 'We value your input. How can we improve your experience?', 'sureforms' ),
						'plugin_rating_button_string' => __( 'Rate SureForms', 'sureforms' ),

					],

				]
			);
		}

		/**
		 * Update the NPS survey post data.
		 * Add SureForms plugin version to the NPS survey post data.
		 *
		 * @param array<mixed> $post_data NPS survey post data.
		 * @since 1.4.0
		 * @return array<mixed>
		 */
		public function update_nps_survey_post_data( $post_data ) {
			if ( isset( $post_data['plugin_slug'] ) && 'sureforms' === $post_data['plugin_slug'] ) {
				$post_data['plugin_version'] = SRFM_VER;
			}

			return $post_data;
		}
	}
}
