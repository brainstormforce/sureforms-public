<?php
/**
 * Class Test_Form_Templates
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Templates;

class Test_Form_Templates extends TestCase {

	/**
	 * Blocks the free plugin renders. A free template using anything else would
	 * be silently dropped (or render nothing) on a site without Pro.
	 *
	 * @var array<int, string>
	 */
	private $free_blocks = [ 'input', 'email', 'number', 'textarea', 'dropdown', 'checkbox', 'address', 'gdpr', 'multi-choice', 'url', 'phone', 'inline-button' ];

	public function tear_down() {
		remove_all_filters( 'srfm_form_templates' );
		$this->reset_template_cache();
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	private function reset_template_cache() {
		$property = new ReflectionProperty( Form_Templates::class, 'templates' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	private function login_admin() {
		$user_id = wp_insert_user(
			[
				'user_login' => 'srfm_tpl_admin_' . uniqid(),
				'user_pass'  => 'password',
				'user_email' => 'srfm_tpl_' . uniqid() . '@example.com',
				'role'       => 'administrator',
			]
		);
		wp_set_current_user( $user_id );
	}

	public function test_ships_twenty_templates_with_unique_ids() {
		$templates = Form_Templates::get_templates();

		$this->assertCount( 20, $templates );
		$this->assertSame( array_keys( $templates ), array_unique( array_keys( $templates ) ) );
	}

	public function test_every_template_has_a_known_category() {
		$categories = wp_list_pluck( Form_Templates::get_categories(), 'id' );

		foreach ( Form_Templates::get_templates() as $id => $template ) {
			$this->assertContains( $template['category'], $categories, "Template {$id} has an unknown category." );
			$this->assertNotEmpty( $template['title'], "Template {$id} has no title." );
			$this->assertNotEmpty( $template['description'], "Template {$id} has no description." );
		}
	}

	public function test_every_template_builds_only_free_blocks() {
		foreach ( Form_Templates::get_templates() as $id => $template ) {
			$content = Form_Templates::build_content( $template );

			$this->assertIsString( $content, "Template {$id} failed to build." );

			preg_match_all( '/<!-- wp:srfm\/([a-z-]+) /', $content, $matches );
			$this->assertNotContains( 'advanced-heading', $matches[1], "Template {$id} should not add a heading block." );

			$fields = $matches[1];
			$this->assertCount( count( Form_Templates::resolve_fields( $template, false ) ), $fields, "Template {$id} lost a field while building." );

			foreach ( $fields as $block ) {
				$this->assertContains( $block, $this->free_blocks, "Template {$id} uses the non-free block {$block}." );
			}
		}
	}

	public function test_templates_for_js_omit_the_field_schema_and_consent_label() {
		$list = Form_Templates::get_templates_for_js();
		$item = $list[0];

		$this->assertSame( 'contact-us', $item['id'] );
		$this->assertArrayNotHasKey( 'confirmation', $item );
		$this->assertContains( 'Message', $item['fields'] );
		foreach ( $item['fields'] as $label ) {
			$this->assertStringNotContainsString( 'I agree', $label );
		}
		$this->assertFalse( $item['is_locked'] );
	}

	public function test_templates_for_js_link_to_a_front_end_preview() {
		$item = Form_Templates::get_templates_for_js()[0];

		$this->assertSame(
			add_query_arg(
				[
					'srfm_template_preview' => 'contact-us',
					'srfm_locale'           => get_user_locale(),
				],
				home_url( '/' )
			),
			$item['preview_url']
		);
	}

	public function test_create_form_from_template_creates_a_stamped_draft() {
		$this->login_admin();

		$post_id = Form_Templates::create_form_from_template( 'quote-request' );

		$this->assertIsInt( $post_id );
		$post = get_post( $post_id );
		$this->assertSame( 'draft', $post->post_status );
		$this->assertSame( SRFM_FORMS_POST_TYPE, $post->post_type );
		$this->assertSame( 'Quote Request', $post->post_title );
		$this->assertStringContainsString( '<!-- wp:srfm/dropdown ', $post->post_content );
		$this->assertSame( 'template', get_post_meta( $post_id, '_srfm_created_via', true ) );
		$this->assertSame( 'quote-request', get_post_meta( $post_id, '_srfm_template_id', true ) );

		$confirmation = get_post_meta( $post_id, '_srfm_form_confirmation', true );
		$this->assertStringContainsString( 'Your quote request is in', $confirmation[0]['message'] );

		$this->assertSame( 'Request my quote', get_post_meta( $post_id, '_srfm_submit_button_text', true ) );

		$notifications = get_post_meta( $post_id, '_srfm_email_notification', true );
		$this->assertCount( 2, $notifications );

		$admin = $notifications[0];
		$this->assertSame( 'Quote request: {form:service} from {form:first-name} {form:last-name}', $admin['subject'] );
		$this->assertStringContainsString( '{form:service}', $admin['email_body'] );
		$this->assertStringEndsWith( '{all_data}', $admin['email_body'] );
		$this->assertSame( '{form:email}', $admin['email_reply_to'] );
		$this->assertSame( '', $admin['email_cc'] );
		$this->assertSame( '', $admin['email_bcc'] );

		$reply = $notifications[1];
		$this->assertSame( '{form:email}', $reply['email_to'] );
		$this->assertTrue( $reply['status'] );
		$this->assertSame( 'We received your quote request', $reply['subject'] );
		$this->assertStringContainsString( 'Hi {form:first-name},', $reply['email_body'] );
		$this->assertStringContainsString( '{site_title}', $reply['email_body'] );
	}

	public function test_every_row_is_full_and_email_tags_use_required_fields() {
		foreach ( [ false, true ] as $with_pro ) {
			foreach ( Form_Templates::get_templates() as $id => $template ) {
				$fields = Form_Templates::resolve_fields( $template, $with_pro );

				// Fields sit side by side until their widths reach 100; a half-empty row looks unfinished.
				$row = 0;
				foreach ( $fields as $field ) {
					if ( 'page-break' === $field['fieldType'] ) {
						$this->assertSame( 0, $row, "Template {$id} has a row cut by a step." );
						continue;
					}
					$width = $field['blockAttributes']['fieldWidth'] ?? 100;
					// Help text sits above the input, so a narrow field with help text is taller than its neighbour.
					if ( $width < 100 ) {
						$this->assertEmpty( $field['helpText'] ?? '', "Template {$id}: {$field['slug']} shares a row, so it cannot have help text." );
					}
					$row += $width;
					$this->assertLessThanOrEqual( 100.01, $row, "Template {$id} has a row wider than the form." );
					if ( $row >= 99.98 ) {
						$row = 0;
					}
				}
				$this->assertSame( 0, $row, "Template {$id} ends with a half-empty row." );

				// An optional answer can be blank, which would leave a gap in the email or thank-you text.
				$required = wp_list_pluck( array_filter( $fields, static fn( $field ) => ! empty( $field['required'] ) ), 'slug' );
				$texts    = [ $template['email_subject'], $template['email_intro'], $template['confirmation']['heading'], $template['confirmation']['text'] ];
				if ( ! empty( $template['autoresponder'] ) ) {
					$texts   = array_merge( $texts, $template['autoresponder']['lines'] );
					$texts[] = $template['autoresponder']['subject'];
					$texts[] = $template['autoresponder']['greeting'] ?? 'Hi {form:full-name},';
				}
				preg_match_all( '/\{form:([a-z0-9-]+)\}/', implode( ' ', $texts ), $tags );
				foreach ( $tags[1] as $slug ) {
					$this->assertContains( $slug, $required, "Template {$id} uses {form:{$slug}}, which is not a required field." );
				}
			}
		}
	}

	public function test_pro_fields_keep_their_width() {
		$fields = Form_Templates::resolve_fields( Form_Templates::get_template( 'class-enrollment' ), true );
		$date   = wp_list_filter( $fields, [ 'slug' => 'start-date' ] );
		$date   = reset( $date );

		$this->assertSame( 'date-picker', $date['fieldType'] );
		$this->assertEquals( 50, $date['blockAttributes']['fieldWidth'] );
		$this->assertTrue( $date['blockAttributes']['disablePastDates'] );
		$this->assertSame( 'mm/dd/yyyy', $date['dateFormat'] );
	}

	public function test_multi_choice_options_can_have_icons() {
		$content = Form_Templates::build_content( Form_Templates::get_template( 'callback-request' ) );

		$this->assertStringContainsString( '"icon":"moon"', $content );
		$this->assertStringContainsString( '"choiceWidth":25', $content );
	}

	public function test_newsletter_is_a_one_line_form() {
		$template = Form_Templates::get_template( 'newsletter-signup' );
		$content  = Form_Templates::build_content( $template );

		$this->assertStringContainsString( 'wp:srfm/inline-button', $content );
		$this->assertStringContainsString( '"buttonText":"Subscribe"', $content );
		// The custom button replaces the form's own one, so the form is marked for it.
		$this->assertTrue( Form_Templates::build_settings_metas( $template )['_srfm_is_inline_button'] );
		$this->assertFalse( Form_Templates::build_settings_metas( Form_Templates::get_template( 'contact-us' ) )['_srfm_is_inline_button'] );
	}

	public function test_a_score_scale_starting_at_zero_keeps_its_options() {
		$content = Form_Templates::build_content( Form_Templates::get_template( 'satisfaction-survey' ) );

		$this->assertStringContainsString( '"label":"0"', $content );
		$this->assertStringContainsString( '"label":"10"', $content );
	}

	public function test_every_template_is_ready_to_publish() {
		foreach ( Form_Templates::get_templates() as $id => $template ) {
			$this->assertNotEmpty( $template['submit_text'] ?? '', "Template {$id} has no button text." );
			$this->assertNotEmpty( $template['email_subject'] ?? '', "Template {$id} has no email subject." );
			$this->assertNotEmpty( $template['email_intro'] ?? '', "Template {$id} has no email intro." );

			$settings = Form_Templates::build_settings_metas( $template );
			$this->assertSame( $template['submit_text'], $settings['_srfm_submit_button_text'] );

			$fields = Form_Templates::resolve_fields( $template, false );
			$types  = wp_list_pluck( $fields, 'fieldType' );
			$email  = array_values( array_filter( $fields, static fn( $f ) => 'email' === $f['slug'] ) );

			// A form that requires the visitor's details asks for consent.
			if ( ! empty( $email ) && ! empty( $email[0]['required'] ) ) {
				$this->assertContains( 'gdpr', $types, "Template {$id} collects an email without consent." );
			}

			// Only forms that always collect an email send the visitor a confirmation.
			$expected = ! empty( $template['autoresponder'] ) && ! empty( $email ) && ! empty( $email[0]['required'] ) ? 2 : 1;
			$this->assertCount( $expected, $settings['_srfm_email_notification'], "Template {$id} has the wrong number of emails." );
		}
	}

	public function test_no_template_ships_placeholder_options() {
		foreach ( Form_Templates::get_templates() as $id => $template ) {
			$json = wp_json_encode( $template['fields'] );
			$this->assertDoesNotMatchRegularExpression( '/(Service|Course) [ABC]\b/', $json, "Template {$id} still has placeholder options." );
			$this->assertStringNotContainsString( '$', $json, "Template {$id} assumes a currency." );
		}
	}

	public function test_pro_fields_replace_free_ones_only_with_pro() {
		$template = Form_Templates::get_template( 'job-application' );

		$free = wp_list_pluck( Form_Templates::resolve_fields( $template, false ), 'fieldType' );
		$pro  = wp_list_pluck( Form_Templates::resolve_fields( $template, true ), 'fieldType' );

		$this->assertNotContains( 'page-break', $free );
		$this->assertNotContains( 'upload', $free );
		$this->assertContains( 'page-break', $pro );
		$this->assertContains( 'upload', $pro );

		$item = wp_list_filter( Form_Templates::get_templates_for_js(), [ 'id' => 'job-application' ] );
		$item = reset( $item );
		$this->assertContains( 'autoresponder', $item['features'] );
		$this->assertContains( 'consent', $item['features'] );
		$this->assertContains( 'multi_step', $item['pro_features'] );
		$this->assertContains( 'file_upload', $item['pro_features'] );
		$this->assertSame( 'Submit application', $item['submit_text'] );
	}

	public function test_number_fields_carry_their_limits() {
		$content = Form_Templates::build_content( Form_Templates::get_template( 'event-registration' ) );

		$this->assertMatchesRegularExpression( '/wp:srfm\/number \{[^}]*"minValue":1,"maxValue":20/', $content );
		// The limits only apply while a template is built.
		$this->assertFalse( has_filter( 'srfm_ai_form_builder_modify_merged_attributes', [ Form_Templates::class, 'apply_block_attributes' ] ) );
	}

	public function test_creating_can_turn_on_spam_protection() {
		$this->login_admin();
		delete_option( 'srfm_security_settings_options' );
		update_option( 'srfm_security_settings_options', [ 'srfm_v3_site_key' => 'keep-me' ] );
		$this->assertFalse( Form_Templates::is_spam_protection_on() );

		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/create' );
		$request->set_param( 'template_id', 'contact-us' );
		$request->set_param( 'enable_spam_protection', true );
		( new Form_Templates() )->rest_create_form( $request );

		$this->assertTrue( Form_Templates::is_spam_protection_on() );
		$this->assertSame( 'keep-me', get_option( 'srfm_security_settings_options' )['srfm_v3_site_key'] );
	}

	public function test_swap_content_carries_the_template_settings() {
		$this->login_admin();

		$request = new WP_REST_Request( 'GET', '/sureforms/v1/form-templates/rsvp' );
		$request->set_param( 'template_id', 'rsvp' );
		$data = ( new Form_Templates() )->rest_get_template_content( $request )->get_data();

		$this->assertSame( 'Send my RSVP', $data['settings']['_srfm_submit_button_text'] );
		$this->assertCount( 2, $data['settings']['_srfm_email_notification'] );
		$this->assertStringContainsString( 'Thanks, {form:first-name}!', $data['settings']['_srfm_form_confirmation'][0]['message'] );
		// Only the styling the template changes, so the form keeps its other choices.
		$this->assertArrayHasKey( 'primary_color', $data['styling'] );
		$this->assertLessThan( count( Form_Templates::build_style_metas( Form_Templates::get_template( 'rsvp' ) )['_srfm_forms_styling'] ), count( $data['styling'] ) );
	}

	public function test_every_template_uses_a_known_theme() {
		$themes = Form_Templates::get_themes();

		foreach ( Form_Templates::get_templates() as $id => $template ) {
			$this->assertArrayHasKey( $template['theme'], $themes, "Template {$id} has an unknown theme." );
		}
	}

	public function test_every_theme_has_a_banner_color() {
		foreach ( Form_Templates::get_themes() as $key => $theme ) {
			$this->assertMatchesRegularExpression( '/^#[0-9A-F]{6}$/', $theme['banner'], "Theme {$key} has no banner color." );
		}
	}

	public function test_created_form_carries_the_theme_styling() {
		$this->login_admin();

		$post_id = Form_Templates::create_form_from_template( 'quote-request' );
		$styling = get_post_meta( $post_id, '_srfm_forms_styling', true );
		$theme   = Form_Templates::get_themes()['midnight'];

		$this->assertSame( 'gradient', $styling['bg_type'] );
		$this->assertSame( $theme['background'], $styling['bg_gradient'] );
		$this->assertSame( $theme['primary'], $styling['primary_color'] );
		$this->assertSame( $theme['text'], $styling['text_color'] );
		$this->assertEquals( 40, $styling['form_padding_top'] );
		$this->assertEquals( 16, $styling['form_border_radius_left'] );
		$this->assertStringNotContainsString( 'wp:srfm/advanced-heading', get_post( $post_id )->post_content );

		// No heading block, so the form page shows the title itself.
		$instant_form = get_post_meta( $post_id, '_srfm_instant_form_settings', true );
		$this->assertTrue( $instant_form['single_page_form_title'] );
		$this->assertSame( $theme['banner'], $instant_form['cover_color'] );
	}

	public function test_unknown_template_is_a_404() {
		$result = Form_Templates::create_form_from_template( 'does-not-exist' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	public function test_pro_template_is_refused_without_pro() {
		if ( defined( 'SRFM_PRO_VER' ) ) {
			$this->markTestSkipped( 'Pro is loaded in this run.' );
		}

		add_filter(
			'srfm_form_templates',
			static function ( $templates ) {
				$templates[] = [
					'id'       => 'pro-only',
					'title'    => 'Pro only',
					'category' => 'contact',
					'is_pro'   => true,
					'fields'   => [
						[
							'fieldType' => 'input',
							'label'     => 'Name',
							'required'  => false,
						],
					],
				];
				return $templates;
			}
		);
		$this->reset_template_cache();

		$locked = wp_list_filter( Form_Templates::get_templates_for_js(), [ 'id' => 'pro-only' ] );
		$this->assertTrue( reset( $locked )['is_locked'] );

		$result = Form_Templates::create_form_from_template( 'pro-only' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_template_locked', $result->get_error_code() );
	}

	public function test_template_without_fields_is_ignored() {
		add_filter(
			'srfm_form_templates',
			static function ( $templates ) {
				$templates[] = [
					'id'     => 'empty',
					'title'  => 'Empty',
					'fields' => [],
				];
				return $templates;
			}
		);
		$this->reset_template_cache();

		$this->assertNull( Form_Templates::get_template( 'empty' ) );
	}

	public function test_rest_content_reports_no_entries_for_a_new_form() {
		$this->login_admin();
		$post_id = Form_Templates::create_form_from_template( 'contact-us' );

		$request = new WP_REST_Request( 'GET', '/sureforms/v1/form-templates/rsvp' );
		$request->set_param( 'template_id', 'rsvp' );
		$request->set_param( 'form_id', $post_id );

		$response = Form_Templates::get_instance()->rest_get_template_content( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertSame( 'rsvp', $data['id'] );
		$this->assertFalse( $data['has_entries'] );
		$this->assertStringContainsString( '<!-- wp:srfm/multi-choice ', $data['content'] );
	}

	/**
	 * The editor's "Change template" gets the chosen style back, to apply to
	 * the open form exactly as previewed.
	 */
	public function test_rest_content_returns_the_chosen_style_for_the_editor() {
		$this->login_admin();
		$style = [
			'preset' => 'midnight',
			'accent' => '#DB2777',
		];

		$request = new WP_REST_Request( 'GET', '/sureforms/v1/form-templates/event-registration' );
		$request->set_param( 'style', wp_json_encode( $style ) );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );

		$data  = $response->get_data();
		$theme = Form_Templates::resolve_theme( Form_Templates::get_template( 'event-registration' ), $style );

		$this->assertSame( '#DB2777', $data['styling']['primary_color'] );
		$this->assertSame( $theme['background'], $data['styling']['bg_gradient'] );
		$this->assertStringNotContainsString( 'wp:srfm/advanced-heading', $data['content'] );

		// Only the keys a template owns, so the form's other page settings stay.
		$this->assertSame(
			[
				'single_page_form_title' => true,
				'cover_type'             => 'color',
				'cover_color'            => $theme['banner'],
			],
			$data['instant_form']
		);
	}

	public function test_clean_demand_query_keeps_short_descriptions_only() {
		$this->assertSame( 'gym membership', Form_Templates::clean_demand_query( '  Gym   <b>Membership</b> ' ) );
		$this->assertSame( '', Form_Templates::clean_demand_query( 'a' ) );
		$this->assertSame( '', Form_Templates::clean_demand_query( 'jane@example.com' ) );
		$this->assertSame( '', Form_Templates::clean_demand_query( 'call 9876543210' ) );
		$this->assertSame( '', Form_Templates::clean_demand_query( 'https://example.org/form' ) );
		$this->assertSame( '', Form_Templates::clean_demand_query( 'one two three four five six seven' ) );
	}

	public function test_template_request_is_not_recorded_without_usage_sharing() {
		$this->login_admin();
		add_filter( 'sureforms_tracking_enabled', '__return_false' );

		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/request' );
		$request->set_param( 'query', 'gym membership' );
		$response = rest_do_request( $request );

		remove_filter( 'sureforms_tracking_enabled', '__return_false' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['recorded'] );
		$this->assertEmpty( \SRFM\Inc\Helper::get_srfm_option( 'form_template_demand', [] ) );
	}

	public function test_template_requests_and_empty_searches_are_counted() {
		$this->login_admin();
		add_filter( 'sureforms_tracking_enabled', '__return_true' );

		foreach ( [ [ 'Gym membership', 'request' ], [ 'gym membership', 'request' ], [ 'pet adoption', 'search' ] ] as $item ) {
			$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/request' );
			$request->set_param( 'query', $item[0] );
			$request->set_param( 'type', $item[1] );
			$this->assertTrue( rest_do_request( $request )->get_data()['recorded'] );
		}

		remove_filter( 'sureforms_tracking_enabled', '__return_true' );

		$demand = \SRFM\Inc\Helper::get_srfm_option( 'form_template_demand', [] );
		$this->assertSame( 2, $demand['request']['gym_membership'] );
		$this->assertSame( 1, $demand['search']['pet_adoption'] );
	}

	public function test_template_request_rejects_contact_details() {
		$this->login_admin();

		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/request' );
		$request->set_param( 'query', 'jane@example.com' );

		$this->assertSame( 400, rest_do_request( $request )->get_status() );
	}

	public function test_record_swap_stamps_the_form() {
		$this->login_admin();
		$post_id = Form_Templates::create_form_from_template( 'contact-us' );

		Form_Templates::record_swap( $post_id, 'bug-report' );

		$this->assertSame( 'bug-report', get_post_meta( $post_id, '_srfm_template_id', true ) );
	}

	public function test_record_swap_ignores_other_post_types() {
		$post_id = wp_insert_post(
			[
				'post_title'  => 'Plain post',
				'post_status' => 'draft',
			]
		);

		Form_Templates::record_swap( $post_id, 'bug-report' );

		$this->assertSame( '', get_post_meta( $post_id, '_srfm_template_id', true ) );
	}

	public function test_sanitize_style_keeps_only_known_keys_and_valid_values() {
		$this->assertSame(
			[
				'preset'     => 'mint',
				'background' => '#ABCDEF',
				'accent'     => '#123456',
			],
			Form_Templates::sanitize_style(
				wp_json_encode(
					[
						'preset'     => 'mint',
						'background' => '#abcdef',
						'accent'     => '#123456',
						'corners'    => 'soft',
						'evil'       => '<script>',
					]
				)
			)
		);

		$this->assertSame( [], Form_Templates::sanitize_style( 'not json' ) );
		$this->assertSame( [], Form_Templates::sanitize_style( [ 'preset' => 'nope', 'background' => 'red' ] ) );
		$this->assertSame( [], Form_Templates::sanitize_style( null ) );
	}

	public function test_resolve_theme_keeps_text_readable_on_any_chosen_color() {
		$template = Form_Templates::get_template( 'contact-us' );

		$dark = Form_Templates::resolve_theme( $template, [ 'background' => '#0F172A', 'accent' => '#FACC15' ] );
		$this->assertSame( '#E2E8F0', $dark['text'], 'Light text on a dark background.' );
		$this->assertSame( '#0F172A', $dark['on_primary'], 'Dark button text on a yellow accent.' );
		$this->assertStringStartsWith( 'linear-gradient(135deg, #0F172A 0%', $dark['background'] );

		$light = Form_Templates::resolve_theme( $template, [ 'background' => '#FFFFFF', 'accent' => '#1D4ED8' ] );
		$this->assertSame( '#1E293B', $light['text'] );
		$this->assertSame( '#FFFFFF', $light['on_primary'] );
	}

	public function test_resolve_theme_uses_the_chosen_preset() {
		$template = Form_Templates::get_template( 'contact-us' );
		$midnight = Form_Templates::get_themes()['midnight'];

		$theme = Form_Templates::resolve_theme( $template, [ 'preset' => 'midnight' ] );

		$this->assertSame( $midnight['background'], $theme['background'] );
		$this->assertSame( $midnight['primary'], $theme['primary'] );
		$this->assertSame( 16, $theme['radius'] );
	}

	/**
	 * What is picked in the preview is exactly what the new form gets.
	 */
	public function test_created_form_matches_the_style_chosen_in_the_preview() {
		$this->login_admin();

		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/create' );
		$request->set_param( 'template_id', 'contact-us' );
		$request->set_param(
			'style',
			[
				'background' => '#FFF7ED',
				'accent'     => '#DB2777',
			]
		);
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );

		$post_id = $response->get_data()['id'];
		$styling = get_post_meta( $post_id, '_srfm_forms_styling', true );
		$theme   = Form_Templates::resolve_theme(
			Form_Templates::get_template( 'contact-us' ),
			[
				'background' => '#FFF7ED',
				'accent'     => '#DB2777',
			]
		);

		$this->assertSame( '#DB2777', $styling['primary_color'] );
		$this->assertSame( $theme['on_primary'], $styling['text_color_on_primary'] );
		$this->assertSame( $theme['background'], $styling['bg_gradient'] );
		$this->assertEquals( 16, $styling['form_border_radius_top'] );
		// The form's own page uses its own corner keys; they must match.
		$this->assertEquals( 16, $styling['instant_form_border_radius_top'] );
		$this->assertEquals( 40, $styling['instant_form_padding_left'] );
		$this->assertStringNotContainsString( 'wp:srfm/advanced-heading', get_post( $post_id )->post_content );
		$this->assertSame( $theme['banner'], get_post_meta( $post_id, '_srfm_instant_form_settings', true )['cover_color'] );
	}

	public function test_themes_for_js_list_every_preset() {
		$ids = wp_list_pluck( Form_Templates::get_themes_for_js(), 'id' );
		$this->assertSame( array_keys( Form_Templates::get_themes() ), $ids );
	}

	public function test_library_gets_each_templates_messages_and_answers() {
		$templates = array_column( Form_Templates::get_templates_for_js(), null, 'id' );

		$contact = $templates['contact-us'];
		$this->assertSame( 'Thanks, {form:first-name}!', $contact['messages']['confirmation']['heading'] );
		$this->assertSame( 'Hi {form:first-name},', $contact['messages']['autoresponder']['greeting'] );
		$this->assertSame( [ 'slug' => 'first-name', 'label' => 'First Name', 'required' => true, 'sample' => 'Jane' ], $contact['answers'][0] );

		// No required email, so nothing is sent to the visitor.
		$this->assertNull( $templates['callback-request']['messages']['autoresponder'] );
		// Consent and the inline button are not answers anyone puts in an email.
		$this->assertNotContains( 'consent', wp_list_pluck( $contact['answers'], 'slug' ) );
		$this->assertNotContains( 'subscribe', wp_list_pluck( $templates['newsletter-signup']['answers'], 'slug' ) );
	}

	public function test_sanitize_messages_keeps_plain_text_only() {
		$clean = Form_Templates::sanitize_messages(
			[
				'confirmation'  => [
					'heading' => '<b>Thanks</b>, {form:first-name}!',
					'evil'    => 'x',
				],
				'email_subject' => "Hello\nBcc: x@example.com",
				'autoresponder' => [
					'enabled' => 'false',
					'lines'   => [ 'One', '', '<script>alert(1)</script>Two' ],
				],
				'unknown'       => 'x',
			]
		);

		$this->assertSame( [ 'heading' => 'Thanks, {form:first-name}!' ], $clean['confirmation'] );
		$this->assertStringNotContainsString( "\n", $clean['email_subject'], 'A subject cannot add email headers.' );
		$this->assertFalse( $clean['autoresponder']['enabled'] );
		$this->assertSame( [ 'One', 'Two' ], $clean['autoresponder']['lines'] );
		$this->assertArrayNotHasKey( 'unknown', $clean );
		$this->assertSame( [], Form_Templates::sanitize_messages( 'nope' ) );
	}

	public function test_created_form_uses_the_messages_edited_in_the_preview() {
		$this->login_admin();

		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/create' );
		$request->set_param( 'template_id', 'contact-us' );
		$request->set_param(
			'messages',
			[
				'confirmation'  => [ 'heading' => 'Got it, {form:first-name}' ],
				'email_subject' => 'Website message from {form:first-name}',
				'autoresponder' => [ 'subject' => 'We have your message' ],
			]
		);
		$post_id = rest_do_request( $request )->get_data()['id'];

		$confirmation = get_post_meta( $post_id, '_srfm_form_confirmation', true );
		$this->assertStringContainsString( 'Got it, {form:first-name}', $confirmation[0]['message'] );
		$this->assertStringContainsString( 'We will reply to {form:email}', $confirmation[0]['message'], 'Text not edited stays as the template had it.' );

		$emails = get_post_meta( $post_id, '_srfm_email_notification', true );
		$this->assertSame( 'Website message from {form:first-name}', $emails[0]['subject'] );
		$this->assertSame( 'We have your message', $emails[1]['subject'] );
		$this->assertStringContainsString( 'Thanks for reaching out', $emails[1]['email_body'] );
	}

	public function test_quotes_and_backslashes_survive_creating_the_form() {
		$add_template = static function ( $templates ) {
			$templates[] = [
				'id'           => 'quotes',
				'title'        => 'The "Best" Form',
				'category'     => 'contact',
				'fields'       => [
					[
						'fieldType'   => 'input',
						'label'       => 'Company "Inc"',
						'slug'        => 'company',
						'required'    => true,
						'placeholder' => 'C:\\path',
					],
				],
				'confirmation' => [
					'heading' => 'Thanks',
					'text'    => 'We said "hi" to {form:company}.',
				],
			];
			return $templates;
		};
		add_filter( 'srfm_form_templates', $add_template );
		$this->reset_template_cache();

		$post_id = Form_Templates::create_form_from_template( 'quotes' );

		$this->assertIsInt( $post_id );
		$this->assertSame( 'The "Best" Form', get_post_field( 'post_title', $post_id ) );

		$blocks = parse_blocks( get_post_field( 'post_content', $post_id ) );
		$this->assertSame( 'Company "Inc"', $blocks[0]['attrs']['label'] ?? null, 'Block JSON stays valid when a label has quotes.' );
		$this->assertSame( 'C:\\path', $blocks[0]['attrs']['placeholder'] ?? null );

		$message = get_post_meta( $post_id, '_srfm_form_confirmation', true )[0]['message'];
		$this->assertStringContainsString( 'We said &quot;hi&quot; to {form:company}.', $message, 'Message text is escaped, not cut at the quote.' );
	}

	public function test_saved_thank_you_message_keeps_its_icon() {
		$post_id = Form_Templates::create_form_from_template( 'contact-us' );
		$message = get_post_meta( $post_id, '_srfm_form_confirmation', true )[0]['message'];

		// The saved message is filtered by wp_kses_post(), so the icon must survive it.
		$this->assertStringContainsString( 'src="' . SRFM_URL . 'images/check-icon.svg"', $message );
	}

	public function test_visitor_email_can_be_turned_off_in_the_preview() {
		$post_id = Form_Templates::create_form_from_template( 'contact-us', [], [ 'autoresponder' => [ 'enabled' => false ] ] );

		$this->assertCount( 1, get_post_meta( $post_id, '_srfm_email_notification', true ) );
	}
}
