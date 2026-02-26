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
			'sureforms_page_sureforms_forms', // @since 2.0.0 - Added for forms list page and removed native forms page.
			'sureforms_page_sureforms_payments', // @since 2.0.0 - Added for payments page.
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

			$is_onboarding_completed = Helper::get_srfm_option( 'onboarding_completed', true );

			// When user manually opens onboarding from dashboard, do not show NPS survey.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_REQUEST['srfm-activation-redirect'] ) && '1' === sanitize_text_field( wp_unslash( $_REQUEST['srfm-activation-redirect'] ) ) ) {
				return false;
			}

			// Show the NPS survey if there are at least 3 published forms or 3 form submissions.
			if ( 'no' !== $is_onboarding_completed && ( $form_count >= 3 || $entries_count >= 3 ) ) {
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
						'plugin_name'                 => __( 'Quick Question!', 'sureforms' ),
						'nps_rating_message'          => __( 'How would you rate SureForms? Love it, hate it, or somewhere in between? Your honest answer helps us understand how we\'re doing.', 'sureforms' ),
						'feedback_title'              => __( 'Thanks a lot for your feedback! 😍', 'sureforms' ),
						'feedback_content'            => __( 'Thanks for being part of the SureForms community! Got feedback or suggestions? We\'d love to hear it.', 'sureforms' ),
						'plugin_rating_link'          => esc_url( 'https://wordpress.org/support/plugin/sureforms/reviews/#new-post' ),
						'plugin_rating_title'         => __( 'Thank you for your feedback', 'sureforms' ),
						'plugin_rating_content'       => __( 'We value your input. How can we improve your experience?', 'sureforms' ),
						'plugin_rating_button_string' => __( 'Rate SureForms', 'sureforms' ),
						'rating_min_label'            => __( 'Hate it!', 'sureforms' ),
						'rating_max_label'            => __( 'Love it!', 'sureforms' ),
					],
					'privacy_policy'   => [
						'url' => 'https://sureforms.com/privacy-policy',
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
