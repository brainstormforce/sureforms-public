<?php
/**
 * Class Test_Form_Templates
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Templates;
use SRFM\Inc\Form_Templates\Catalog;

class Test_Form_Templates extends TestCase {

	/**
	 * Blocks the free plugin renders. A free template using anything else would
	 * be silently dropped (or render nothing) on a site without Pro.
	 *
	 * @var array<int, string>
	 */
	private $free_blocks = [ 'input', 'email', 'number', 'textarea', 'dropdown', 'checkbox', 'address', 'gdpr', 'multi-choice', 'url', 'phone', 'inline-button', 'payment' ];

	public function tear_down() {
		$this->restore_preview_globals();
		foreach ( [ 'srfm_form_templates', 'srfm_pro_tier', 'srfm_form_template_is_available', 'srfm_form_template_content', 'srfm_form_template_metas', 'srfm_form_template_features', 'srfm_form_template_message_meta_keys' ] as $hook ) {
			remove_all_filters( $hook );
		}
		$this->reset_template_cache();
		delete_option( 'srfm_security_settings_options' );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Script and style queues and front-end hooks saved by
	 * isolate_preview_globals(), restored in tear_down(); null when untouched.
	 *
	 * @var array{assets: array{0: mixed, 1: mixed}, hooks: array<string, mixed>}|null
	 */
	private $preview_globals = null;

	/**
	 * Start the test with empty script and style queues, and put them and the
	 * front-end hooks back afterwards, for tests that filter what a template
	 * preview loads.
	 */
	private function isolate_preview_globals() {
		global $wp_scripts, $wp_styles, $wp_filter;
		$hooks = [];
		foreach ( [ 'wp_enqueue_scripts', 'wp_head', 'wp_print_styles', 'wp_print_scripts', 'wp_body_open', 'wp_footer', 'wp_print_footer_scripts' ] as $hook ) {
			$hooks[ $hook ] = isset( $wp_filter[ $hook ] ) ? clone $wp_filter[ $hook ] : null;
		}
		$this->preview_globals = [
			'assets' => [ $wp_scripts, $wp_styles ],
			'hooks'  => $hooks,
		];

		$wp_scripts = null;
		$wp_styles  = null;
	}

	/**
	 * Put back what isolate_preview_globals() saved.
	 */
	private function restore_preview_globals() {
		if ( null === $this->preview_globals ) {
			return;
		}
		global $wp_scripts, $wp_styles, $wp_filter;
		list( $wp_scripts, $wp_styles ) = $this->preview_globals['assets'];
		foreach ( $this->preview_globals['hooks'] as $hook => $saved ) {
			if ( null === $saved ) {
				unset( $wp_filter[ $hook ] );
			} else {
				$wp_filter[ $hook ] = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the hooks the test changed.
			}
		}
		$this->preview_globals = null;
	}

	private function reset_template_cache() {
		$property = new ReflectionProperty( Form_Templates::class, 'templates' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	/**
	 * The visitor confirmation email is only set up while the honeypot is on.
	 */
	private function turn_on_spam_protection() {
		update_option( 'srfm_security_settings_options', [ 'srfm_honeypot' => true ] );
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

	public function test_get_templates_ships_twenty_with_unique_ids() {
		$templates = Form_Templates::get_templates();

		// The 20 free templates, in display order; the Premium categories follow.
		$this->assertSame( \SRFM\Inc\Form_Templates\Catalog::ORDER, array_slice( array_keys( $templates ), 0, 20 ) );
		// get_templates() is keyed by id, so a repeated id would silently replace
		// the first template: check the catalog files themselves.
		$ids = array_column( \SRFM\Inc\Form_Templates\Catalog::templates(), 'id' );
		$this->assertSame( array_unique( $ids ), $ids, 'Two catalog templates share an id.' );
		$this->assertCount( count( $ids ), $templates );
	}

	public function test_get_categories_hides_categories_without_templates() {
		$used = array_unique( array_column( Form_Templates::get_templates(), 'category' ) );
		$ids  = wp_list_pluck( Form_Templates::get_categories(), 'id' );

		sort( $used );
		sort( $ids );
		$this->assertSame( $used, $ids );
	}

	public function test_get_categories_cover_every_template() {
		$categories = wp_list_pluck( Form_Templates::get_categories(), 'id' );

		foreach ( Form_Templates::get_templates() as $id => $template ) {
			$this->assertContains( $template['category'], $categories, "Template {$id} has an unknown category." );
			$this->assertNotEmpty( $template['title'], "Template {$id} has no title." );
			$this->assertNotEmpty( $template['description'], "Template {$id} has no description." );
		}
	}

	public function test_build_content_uses_only_free_blocks() {
		foreach ( Form_Templates::get_templates() as $id => $template ) {
			// Paid templates are only built on a plan that has their blocks.
			if ( 'free' !== Form_Templates::get_required_tier( $template ) ) {
				continue;
			}
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

	public function test_get_templates_for_js_omits_the_field_schema_and_consent_label() {
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

	public function test_get_preview_url_links_each_card_to_a_front_end_preview() {
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
		$this->turn_on_spam_protection();

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
		// The visitor's copy never repeats their answers: see test_autoresponder_body_never_echoes_answers().
		$this->assertStringNotContainsString( '{all_data}', $reply['email_body'] );
	}

	public function test_every_row_is_full_and_email_tags_use_required_fields() {
		// Every template on every plan: a paid template still previews with the free fields.
		foreach ( Form_Templates::TIERS as $tier ) {
			foreach ( Form_Templates::get_templates() as $id => $template ) {
				$fields = Form_Templates::resolve_fields( $template, $tier );
				$id    .= " ({$tier})";

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
				// Conditional thank-you messages are built from Starter on.
				if ( 'free' !== $tier ) {
					$texts = array_merge( $texts, array_column( $template['confirmations'] ?? [], 'message' ) );
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
		$this->turn_on_spam_protection();
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
			// Calculator and payment prices use the currency in the payment settings.
			if ( ! in_array( $template['category'], [ 'calculators', 'payments' ], true ) ) {
				$this->assertStringNotContainsString( '$', $json, "Template {$id} assumes a currency." );
			}
		}
	}

	public function test_resolve_fields_replaces_free_ones_only_with_pro() {
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

	public function test_apply_block_attributes_carries_number_limits() {
		$content = Form_Templates::build_content( Form_Templates::get_template( 'event-registration' ) );

		$this->assertMatchesRegularExpression( '/wp:srfm\/number \{[^}]*"minValue":1,"maxValue":20/', $content );
		// The limits only apply while a template is built.
		$this->assertFalse( has_filter( 'srfm_ai_form_builder_modify_merged_attributes', [ Form_Templates::class, 'apply_block_attributes' ] ) );
	}

	public function test_enable_spam_protection_from_create_keeps_other_security_settings() {
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
		$this->turn_on_spam_protection();

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

	public function test_get_themes_cover_every_template() {
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

	public function test_build_style_metas_reach_the_created_form() {
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

	public function test_get_template_ignores_a_template_without_fields() {
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

	public function test_rest_get_template_content_reports_no_entries_for_a_new_form() {
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
	public function test_rest_get_template_content_returns_the_chosen_style_for_the_editor() {
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

	public function test_rest_record_action_tracks_steps_and_counts_locked_previews() {
		$this->login_admin();
		add_filter( 'sureforms_tracking_enabled', '__return_true' );
		$free = static fn() => 'free';
		add_filter( 'srfm_pro_tier', $free );
		\SRFM\Inc\Helper::update_srfm_option( 'form_template_usage', [] );

		$track = static function ( $action, $template_id = '' ) {
			$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/track' );
			$request->set_param( 'action', $action );
			$request->set_param( 'template_id', $template_id );
			return rest_do_request( $request );
		};

		$this->assertTrue( $track( 'library_open' )->get_data()['recorded'] );
		$track( 'preview', 'contact-us' );
		$track( 'preview', 'loan-emi-calculator' );
		$track( 'upgrade_click', 'loan-emi-calculator' );
		$this->assertSame( 404, $track( 'preview', 'no-such-template' )->get_status() );
		$this->assertSame( 400, $track( 'locked_preview', 'contact-us' )->get_status(), 'Locked previews are decided on the server.' );

		remove_filter( 'srfm_pro_tier', $free );
		remove_filter( 'sureforms_tracking_enabled', '__return_true' );

		$usage = \SRFM\Inc\Helper::get_srfm_option( 'form_template_usage', [] );
		$this->assertSame( 1, $usage['library_opens'] );
		$this->assertSame( 2, $usage['previews'] );
		$this->assertSame( 1, $usage['locked_previews'], 'Only the Business template counts as locked on a free site.' );
		$this->assertSame( [ 'loan-emi-calculator' => 1 ], $usage['locked_preview_counts'] );
		$this->assertSame( [ 'loan-emi-calculator' => 1 ], $usage['upgrade_click_counts'] );
	}

	public function test_rest_record_action_records_nothing_without_usage_sharing() {
		$this->login_admin();
		add_filter( 'sureforms_tracking_enabled', '__return_false' );
		\SRFM\Inc\Helper::update_srfm_option( 'form_template_usage', [] );

		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/track' );
		$request->set_param( 'action', 'library_open' );
		$response = rest_do_request( $request );

		remove_filter( 'sureforms_tracking_enabled', '__return_false' );

		$this->assertFalse( $response->get_data()['recorded'] );
		$this->assertEmpty( \SRFM\Inc\Helper::get_srfm_option( 'form_template_usage', [] ) );
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
		// A search with no results is only counted, never its text.
		$this->assertSame( 1, $demand['searches'] );
		$this->assertStringNotContainsString( 'pet', wp_json_encode( $demand ) );
		\SRFM\Inc\Helper::update_srfm_option( 'form_template_demand', [] );
	}

	public function test_rest_record_demand_counts_an_empty_search_without_text() {
		$this->login_admin();
		add_filter( 'sureforms_tracking_enabled', '__return_true' );
		\SRFM\Inc\Helper::update_srfm_option( 'form_template_demand', [] );

		// What the library sends: no query at all.
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/request' );
		$request->set_param( 'type', 'search' );
		$response = rest_do_request( $request );

		remove_filter( 'sureforms_tracking_enabled', '__return_true' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['recorded'] );
		$this->assertSame( [ 'searches' => 1 ], \SRFM\Inc\Helper::get_srfm_option( 'form_template_demand', [] ) );
		\SRFM\Inc\Helper::update_srfm_option( 'form_template_demand', [] );

		// A request still needs its text.
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/request' );
		$this->assertSame( 400, rest_do_request( $request )->get_status() );
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

	public function test_get_themes_for_js_lists_every_preset() {
		$ids = wp_list_pluck( Form_Templates::get_themes_for_js(), 'id' );
		$this->assertSame( array_keys( Form_Templates::get_themes() ), $ids );
	}

	public function test_get_themes_for_js_carry_the_colors_the_server_resolves() {
		$template = Form_Templates::get_template( 'contact-us' );
		foreach ( Form_Templates::get_themes_for_js() as $preset ) {
			$theme = Form_Templates::resolve_theme( $template, [ 'preset' => $preset['id'] ] );
			$this->assertSame( $theme['background'], $preset['background'], $preset['id'] );
			$this->assertSame( $theme['primary'], $preset['accent'], $preset['id'] );
			$this->assertSame( $theme['text'], $preset['text'], $preset['id'] );
			$this->assertSame( $theme['on_primary'], $preset['onPrimary'], $preset['id'] );
			// The preview restyles itself by swapping these values in the
			// form's CSS, so no two of them may be the same color.
			$colors = [ $theme['primary'], $theme['text'], $theme['on_primary'] ];
			$this->assertSame( $colors, array_unique( $colors ), $preset['id'] );
		}
	}

	public function test_get_messages_and_answers_reach_the_library() {
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

	public function test_apply_messages_from_the_preview_reach_the_created_form() {
		$this->login_admin();
		$this->turn_on_spam_protection();

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
		// Kses may return the quotes as &quot; or as plain ", depending on the WordPress version. Decode so the test checks they survived, not how they are encoded.
		$this->assertStringContainsString( 'We said "hi" to {form:company}.', html_entity_decode( $message, ENT_QUOTES ), 'Message text keeps its quotes, not cut at the quote.' );
	}

	public function test_saved_thank_you_message_keeps_its_icon() {
		$post_id = Form_Templates::create_form_from_template( 'contact-us' );
		$message = get_post_meta( $post_id, '_srfm_form_confirmation', true )[0]['message'];

		// The saved message is filtered by wp_kses_post(), so the icon must survive it.
		$this->assertStringContainsString( 'src="' . SRFM_URL . 'images/check-icon.svg"', $message );
	}

	public function test_premium_templates_the_free_preview_cannot_show_have_a_small_picture() {
		add_filter( 'srfm_pro_tier', static fn() => 'free' );
		$items = array_column( Form_Templates::get_templates_for_js(), null, 'id' );
		remove_all_filters( 'srfm_pro_tier' );

		foreach ( Form_Templates::get_templates() as $id => $template ) {
			$image = $template['preview_image'] ?? '';
			if ( '' !== $image ) {
				$path = SRFM_DIR . 'images/templates/' . $image;
				$this->assertFileExists( $path, "Template {$id}: preview image missing." );
				$this->assertLessThan( 60 * 1024, filesize( $path ), "Template {$id}: preview image is 60 KB or more." );
			}

			if ( 'free' === Form_Templates::get_required_tier( $template ) ) {
				continue;
			}
			// Fields the free preview drops, or a calculation it can't run.
			$calculated = in_array( 'calculation', $template['features'] ?? [], true );
			if ( $items[ $id ]['preview_missing'] > 0 || $calculated ) {
				$this->assertNotSame( '', $image, "Template {$id}: the free preview can't show it, so it needs a preview image." );
				$this->assertSame( SRFM_URL . 'images/templates/' . $image, $items[ $id ]['preview_image'] );
			}
		}
	}

	public function test_visitor_email_can_be_turned_off_in_the_preview() {
		$this->turn_on_spam_protection();
		$post_id = Form_Templates::create_form_from_template( 'contact-us', [], [ 'autoresponder' => [ 'enabled' => false ] ] );

		$this->assertCount( 1, get_post_meta( $post_id, '_srfm_email_notification', true ) );
	}

	/**
	 * Five scored questions, one right answer each.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function quiz_fields() {
		$fields = [];
		for ( $i = 1; $i <= 5; $i++ ) {
			$fields[] = [
				'fieldType'    => 'multi-choice',
				'slug'         => 'q' . $i,
				'label'        => 'Question ' . $i,
				'fieldOptions' => [
					[
						'optionTitle' => 'Right',
						'isCorrect'   => true,
					],
					[
						'optionTitle' => 'Wrong',
						'isCorrect'   => false,
					],
				],
			];
		}
		return $fields;
	}

	/**
	 * A quantity, a price and a calculated total of the two.
	 *
	 * @param array<string, mixed> $total Extra keys for the total field.
	 * @return array<int, array<string, mixed>>
	 */
	private function calculator_fields( $total = [] ) {
		return [
			[
				'fieldType'   => 'number',
				'slug'        => 'qty',
				'label'       => 'Quantity',
				'placeholder' => 'e.g. 3',
			],
			[
				'fieldType'   => 'number',
				'slug'        => 'price',
				'label'       => 'Price',
				'placeholder' => 'e.g. 1,200',
			],
			array_merge(
				[
					'fieldType'          => 'number',
					'slug'               => 'total',
					'label'              => 'Total',
					'calculationFormula' => '{form:qty} * {form:price}',
				],
				$total
			),
		];
	}

	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();

		$expected = [
			'/sureforms/v1/form-templates'                              => 'GET',
			'/sureforms/v1/form-templates/create'                       => 'POST',
			'/sureforms/v1/form-templates/request'                      => 'POST',
			'/sureforms/v1/form-templates/track'                        => 'POST',
			'/sureforms/v1/form-templates/swap'                         => 'POST',
			'/sureforms/v1/form-templates/(?P<template_id>[a-z0-9-]+)' => 'GET',
		];
		foreach ( $expected as $route => $method ) {
			$this->assertArrayHasKey( $route, $routes, "Route {$route} is not registered." );
			$this->assertArrayHasKey( $method, $routes[ $route ][0]['methods'], "Route {$route} does not accept {$method}." );
			$this->assertSame( [ \SRFM\Inc\Helper::class, 'get_items_permissions_check' ], $routes[ $route ][0]['permission_callback'] );
		}

		// Locked previews are decided on the server, so the client can't send one.
		$this->assertSame( [ 'library_open', 'preview', 'upgrade_click' ], $routes['/sureforms/v1/form-templates/track'][0]['args']['action']['enum'] );
	}

	public function test_rest_get_template_content_is_a_404_for_an_unknown_template() {
		$request = new WP_REST_Request( 'GET', '/sureforms/v1/form-templates/no-such-template' );
		$request->set_param( 'template_id', 'no-such-template' );

		$result = Form_Templates::get_instance()->rest_get_template_content( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_template_not_found', $result->get_error_code() );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	public function test_rest_record_action_is_a_404_for_an_unknown_template() {
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/track' );
		$request->set_param( 'action', 'preview' );
		$request->set_param( 'template_id', 'no-such-template' );

		$result = Form_Templates::get_instance()->rest_record_action( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	public function test_get_templates_for_js_lists_every_template_with_its_library_keys() {
		$list = Form_Templates::get_templates_for_js();

		$this->assertSame( array_keys( Form_Templates::get_templates() ), array_column( $list, 'id' ) );
		foreach ( $list as $item ) {
			foreach ( [ 'title', 'category', 'theme', 'fields', 'features', 'messages', 'answers', 'requires', 'is_locked', 'lock_reason' ] as $key ) {
				$this->assertArrayHasKey( $key, $item, "{$item['id']} has no {$key}." );
			}
			$this->assertArrayHasKey( $item['theme'], Form_Templates::get_themes() );
			$this->assertSame( 'free' !== $item['requires'], $item['is_pro'] );
		}
	}

	public function test_get_messages() {
		$template = [
			'confirmation'  => [
				'heading' => 'You scored {quiz_score}',
				'text'    => 'Thanks for taking part.',
			],
			'email_subject' => 'New quiz entry',
			'email_intro'   => 'Someone took the quiz.',
			'autoresponder' => [
				'subject' => 'Your result',
				'lines'   => [ 'Result: {quiz_pass_fail}' ],
			],
			'quiz'          => [ 'passPercent' => 60 ],
		];
		$fields   = array_merge(
			[
				[
					'fieldType' => 'email',
					'slug'      => 'email',
					'label'     => 'Email',
					'required'  => true,
				],
			],
			$this->quiz_fields()
		);

		$messages = Form_Templates::get_messages( $template, $fields );

		$this->assertSame( 'You scored {quiz_score}', $messages['confirmation']['heading'] );
		$this->assertSame( 'New quiz entry', $messages['email']['subject'] );
		$this->assertSame( 'Someone took the quiz.', $messages['email']['intro'] );
		$this->assertSame( 'Your result', $messages['autoresponder']['subject'] );
		$this->assertSame( 'Hi {form:full-name},', $messages['autoresponder']['greeting'], 'A missing greeting falls back to the default.' );
		$this->assertSame( [ 'Result: {quiz_pass_fail}' ], $messages['autoresponder']['lines'] );
		$this->assertArrayNotHasKey( 'summary', $messages['autoresponder'], 'The visitor email carries no answer summary.' );
		$this->assertContains( '{quiz_score}', array_column( $messages['result_tags'], 'tag' ) );
		$this->assertSame( '4 / 5', $messages['tag_samples']['{quiz_score}'] );
		$this->assertSame( 'Pass', $messages['tag_samples']['{quiz_pass_fail}'] );

		// Without a required email field there is no one to send the visitor email to.
		$this->assertNull( Form_Templates::get_messages( $template, $this->quiz_fields() )['autoresponder'] );
	}

	public function test_get_answers() {
		$fields  = array_merge(
			[
				[
					'fieldType' => 'input',
					'slug'      => 'full-name',
					'label'     => 'Full Name',
					'required'  => true,
				],
				[
					'fieldType' => 'gdpr',
					'slug'      => 'consent',
					'label'     => 'I agree',
					'required'  => true,
				],
				[
					'fieldType' => 'payment',
					'slug'      => 'payment',
					'label'     => 'Payment',
				],
				[
					'fieldType' => 'hidden',
					'slug'      => 'source',
					'label'     => 'Source',
				],
				[
					'fieldType'    => 'dropdown',
					'slug'         => 'service',
					'label'        => 'Service',
					'fieldOptions' => [ [ 'label' => 'Haircut' ], [ 'label' => 'Shave' ] ],
				],
			],
			$this->calculator_fields()
		);

		$answers = array_column( Form_Templates::get_answers( $fields ), null, 'slug' );

		$this->assertSame( [ 'full-name', 'source', 'service', 'qty', 'price', 'total' ], array_keys( $answers ), 'Consent and payment fields have no answer; hidden fields do.' );
		$this->assertSame(
			[
				'slug'     => 'full-name',
				'label'    => 'Full Name',
				'required' => true,
				'sample'   => 'Jane Smith',
			],
			$answers['full-name']
		);
		$this->assertFalse( $answers['service']['required'] );
		$this->assertSame( 'Haircut', $answers['service']['sample'] );
		$this->assertSame( '3', $answers['qty']['sample'], 'An example placeholder is used without its "e.g." prefix.' );
		$this->assertSame( '3,600', $answers['total']['sample'], 'A calculated field shows its worked-out result.' );
	}

	public function test_get_result_tags() {
		$fields   = array_merge(
			$this->calculator_fields(),
			[
				[
					'fieldType' => 'payment',
					'slug'      => 'pay',
					'label'     => 'Payment',
				],
			]
		);
		$template = [
			'quiz'     => [ 'passPercent' => 60 ],
			'features' => [ 'survey' ],
		];

		$tags = array_column( Form_Templates::get_result_tags( $template, $fields ), 'label', 'tag' );

		$this->assertSame(
			[
				'{quiz_score}',
				'{quiz_percentage}',
				'{quiz_pass_fail}',
				'{quiz_letter_grade}',
				'{quiz_result}',
				'{form:total}',
				'{form-payment:pay:amount}',
				'{form-payment:pay:order-id}',
				'{form-payment:pay:status}',
				'{poll_results}',
			],
			array_keys( $tags )
		);
		$this->assertSame( 'Total', $tags['{form:total}'], 'A calculated field is offered under its own label.' );

		// A plain contact form has no results to offer.
		$this->assertSame( [], Form_Templates::get_result_tags( [], [ [ 'fieldType' => 'input', 'slug' => 'name' ] ] ) );
	}

	public function test_get_tag_samples() {
		$fields   = array_merge(
			$this->quiz_fields(),
			[
				[
					'fieldType'   => 'payment',
					'slug'        => 'pay',
					'amountType'  => 'fixed',
					'fixedAmount' => 25,
				],
			]
		);
		$template = [
			'quiz' => [
				'passPercent'  => 90,
				'letterGrades' => [
					[
						'grade'      => 'A',
						'percentage' => 85,
					],
					[
						'grade'      => 'C',
						'percentage' => 50,
					],
				],
			],
		];
		$currency = strtoupper( \SRFM\Inc\Payments\Payment_Helper::get_currency() );
		$symbol   = \SRFM\Inc\Payments\Payment_Helper::get_currency_symbol( $currency );
		$symbol   = '' === $symbol ? $currency . ' ' : $symbol;

		$samples = Form_Templates::get_tag_samples(
			'Score {quiz_score} ({quiz_percentage}), {quiz_pass_fail}, grade {quiz_letter_grade}. Paid {form-payment:pay:amount}, order {form-payment:pay:order-id}. Hi {form:full-name}',
			[ [ 'tag' => '{poll_results}', 'label' => 'Poll results' ] ],
			$fields,
			$template
		);

		$this->assertSame( '4 / 5', $samples['{quiz_score}'] );
		$this->assertSame( '80%', $samples['{quiz_percentage}'] );
		$this->assertSame( 'Fail', $samples['{quiz_pass_fail}'], '80% is below the 90% pass mark.' );
		$this->assertSame( 'C', $samples['{quiz_letter_grade}'], 'The first grade reached applies.' );
		$this->assertSame( $symbol . '25.00', $samples['{form-payment:pay:amount}'] );
		$this->assertSame( 'SF-#1042', $samples['{form-payment:pay:order-id}'] );
		$this->assertSame( '(live poll results)', $samples['{poll_results}'] );
		$this->assertArrayNotHasKey( '{form:full-name}', $samples, 'Answers come from get_answers().' );
	}

	public function test_calculated_sample() {
		$fields = $this->calculator_fields();

		$this->assertNull( Form_Templates::calculated_sample( $fields[0], $fields ), 'A plain number field is not calculated.' );
		$this->assertSame( '3,600', Form_Templates::calculated_sample( $fields[2], $fields ) );

		// The rounding set on the field applies.
		$rounded = $this->calculator_fields( [ 'blockAttributes' => [ 'calculationRound' => 2 ] ] );
		$this->assertSame( '3,600.00', Form_Templates::calculated_sample( $rounded[2], $rounded ) );

		// The editor saves the tags escaped.
		$escaped = $this->calculator_fields( [ 'calculationFormula' => '%{%form:qty%}% + 1' ] );
		$this->assertSame( '4', Form_Templates::calculated_sample( $escaped[2], $escaped ) );

		// A formula that can't be worked out shows the fallback.
		$unknown = $this->calculator_fields( [ 'calculationFormula' => '{form:missing} * 2' ] );
		$this->assertSame( Form_Templates::CALCULATION_FALLBACK, Form_Templates::calculated_sample( $unknown[2], $unknown ) );

		// SureForms Pro can supply its own result.
		$pro = static fn() => 7.5;
		add_filter( 'srfm_form_template_calculation_sample', $pro );
		$this->assertSame( '7.50', Form_Templates::calculated_sample( $unknown[2], $unknown ) );
		remove_filter( 'srfm_form_template_calculation_sample', $pro );
	}

	public function test_preview_payment_methods() {
		$this->assertSame( 'not-an-array', Form_Templates::preview_payment_methods( 'not-an-array' ) );

		$connected = [ 'stripe' => [ 'enabled' => true ] ];
		$this->assertSame( $connected, Form_Templates::preview_payment_methods( $connected ) );

		$other = [ 'paypal' => [ 'enabled' => false ] ];
		$this->assertSame( $other, Form_Templates::preview_payment_methods( $other ), 'Only Stripe is shown before it is connected.' );

		$methods = Form_Templates::preview_payment_methods(
			[
				'stripe' => [ 'enabled' => false ],
				'paypal' => [ 'enabled' => false ],
			]
		);
		$this->assertTrue( $methods['stripe']['enabled'] );
		$this->assertStringContainsString( 'srfm-template-preview__payment-note', $methods['stripe']['place_holder_content'] );
		$this->assertFalse( $methods['paypal']['enabled'] );
	}

	public function test_confirmation_message() {
		$message = Form_Templates::confirmation_message( 'Thanks <b>Jane</b>', 'We\'ll reply <script>soon</script>' );

		$this->assertStringContainsString( '<img src="' . esc_url( SRFM_URL . 'images/check-icon.svg' ) . '"', $message );
		$this->assertStringContainsString( '<h2 style="text-align: center;">Thanks &lt;b&gt;Jane&lt;/b&gt;</h2>', $message );
		$this->assertStringNotContainsString( '<script>', $message );
		$this->assertStringContainsString( 'src="' . SRFM_URL . 'images/check-icon.svg"', wp_kses_post( $message ), 'The icon survives the kses filter the message is saved through.' );
	}

	public function test_maybe_render_preview_does_nothing_on_other_pages() {
		unset( $_GET[ Form_Templates::PREVIEW_QUERY_VAR ] );

		$this->assertNull( Form_Templates::get_instance()->maybe_render_preview() );
	}

	/**
	 * Run maybe_render_preview() with wp_die() throwing, as the page would end there.
	 *
	 * @param string $template_id Requested template id.
	 * @return string The wp_die() message, '' when it did not die.
	 */
	private function render_preview_until_it_dies( $template_id ) {
		$throw = static fn() => static function ( $message = '' ) {
			throw new WPDieException( is_string( $message ) ? $message : '' );
		};
		add_filter( 'wp_die_handler', $throw, 999 );
		$_GET[ Form_Templates::PREVIEW_QUERY_VAR ] = $template_id;

		try {
			Form_Templates::get_instance()->maybe_render_preview();
			return '';
		} catch ( WPDieException $e ) {
			return $e->getMessage();
		} finally {
			unset( $_GET[ Form_Templates::PREVIEW_QUERY_VAR ] );
			remove_filter( 'wp_die_handler', $throw, 999 );
		}
	}

	public function test_maybe_render_preview_refuses_users_who_cannot_create_forms() {
		$user_id = wp_insert_user(
			[
				'user_login' => 'srfm_tpl_sub_' . uniqid(),
				'user_pass'  => 'password',
				'user_email' => 'srfm_tpl_sub_' . uniqid() . '@example.com',
				'role'       => 'subscriber',
			]
		);
		wp_set_current_user( $user_id );
		$this->assertFalse( current_user_can( 'manage_options' ) );

		$this->assertStringContainsString( 'not allowed', $this->render_preview_until_it_dies( 'contact-us' ) );
	}

	public function test_maybe_render_preview_is_a_404_for_an_unknown_template() {
		$this->login_admin();

		$this->assertStringContainsString( 'could not be found', $this->render_preview_until_it_dies( 'no-such-template' ) );
	}

	public function test_get_preview_image_url_from_a_catalog_file_or_a_full_url() {
		$this->assertSame( SRFM_URL . 'images/templates/quiz.webp', Form_Templates::get_preview_image_url( [ 'preview_image' => 'quiz.webp' ] ) );
		$this->assertSame( SRFM_URL . 'images/templates/quizzes/score.png', Form_Templates::get_preview_image_url( [ 'preview_image' => 'quizzes/score.png' ] ) );
		$this->assertSame( 'https://cdn.example.com/q.webp', Form_Templates::get_preview_image_url( [ 'preview_image' => ' https://cdn.example.com/q.webp ' ] ) );

		// Nothing outside images/templates/, and only image files.
		$this->assertSame( '', Form_Templates::get_preview_image_url( [] ) );
		$this->assertSame( '', Form_Templates::get_preview_image_url( [ 'preview_image' => '../../wp-config.png' ] ) );
		$this->assertSame( '', Form_Templates::get_preview_image_url( [ 'preview_image' => '/etc/quiz.webp' ] ) );
		$this->assertSame( '', Form_Templates::get_preview_image_url( [ 'preview_image' => 'quiz.php' ] ) );
		$this->assertSame( '', Form_Templates::get_preview_image_url( [ 'preview_image' => 'javascript:alert(1).png' ] ) );
	}

	public function test_count_missing_preview_fields_counts_higher_plan_and_unknown_blocks() {
		$template = [
			'fields' => [
				[
					'fieldType' => 'input',
					'slug'      => 'name',
				],
				[
					'fieldType' => 'signature',
					'slug'      => 'sign',
					'pro_tier'  => 'business',
				],
				[
					'fieldType' => 'page-break',
					'slug'      => 'step',
					'pro_only'  => true,
				],
				[
					'fieldType' => 'not-a-block',
					'slug'      => 'ghost',
				],
			],
		];
		$resolved = Form_Templates::resolve_fields( $template, 'free' );

		// `sign` needs Business and `ghost` has no block; the step divider isn't a field.
		$this->assertSame( 2, Form_Templates::count_missing_preview_fields( $template, $resolved ) );
	}

	public function test_get_setup_lists_each_known_step_once() {
		$setup = Form_Templates::get_setup( [ 'setup' => [ 'stripe', 'fax_machine', 'stripe', 'google_maps' ] ] );

		$this->assertSame( [ 'stripe', 'google_maps' ], array_column( $setup, 'id' ) );
		foreach ( $setup as $item ) {
			$this->assertSame( [ 'id', 'label', 'done', 'url' ], array_keys( $item ) );
			$this->assertFalse( $item['done'], $item['id'] );
			$this->assertNotSame( '', $item['label'] );
		}
		$this->assertStringContainsString( 'tab=google-maps-settings', $setup[1]['url'] );
		$this->assertSame( [], Form_Templates::get_setup( [] ) );

		// A step SureForms Pro provides is left out without it.
		if ( ! class_exists( '\SRFM_Pro\Inc\Business\Payments\PayPal\Helper' ) ) {
			$this->assertSame( [], Form_Templates::get_setup( [ 'setup' => [ 'paypal' ] ] ) );
		}

		update_option( 'srfm_google_maps_settings', [ 'srfm_google_maps_api_key' => 'AIza-test' ] );
		$this->assertTrue( Form_Templates::get_setup( [ 'setup' => [ 'google_maps' ] ] )[0]['done'] );
		delete_option( 'srfm_google_maps_settings' );
	}

	public function test_get_result_tags_from_quiz_calculations_payments_and_polls() {
		$fields = [
			[
				'fieldType' => 'number',
				'slug'      => 'price',
				'label'     => 'Price',
			],
			[
				'fieldType'       => 'number',
				'slug'            => 'total',
				'label'           => 'Total',
				'blockAttributes' => [ 'calculationFormula' => '{form:price} * 2' ],
			],
			[
				'fieldType' => 'payment',
				'slug'      => 'pay',
				'label'     => 'Payment',
			],
			[
				'fieldType'          => 'number',
				'label'              => 'No slug',
				'calculationFormula' => '1 + 1',
			],
		];

		$tags = Form_Templates::get_result_tags(
			[
				'features' => [ 'quiz' ],
				'survey'   => true,
			],
			$fields
		);

		$this->assertSame(
			[
				'{quiz_score}',
				'{quiz_percentage}',
				'{quiz_pass_fail}',
				'{quiz_letter_grade}',
				'{quiz_result}',
				'{form:total}',
				'{form-payment:pay:amount}',
				'{form-payment:pay:order-id}',
				'{form-payment:pay:status}',
				'{poll_results}',
			],
			array_column( $tags, 'tag' )
		);
		$this->assertSame( 'Total', array_column( $tags, 'label', 'tag' )['{form:total}'], 'A calculated field is offered by its label.' );

		$this->assertSame( [], Form_Templates::get_result_tags( [], [ $fields[0] ] ), 'Plain fields give no result tags.' );
	}

	public function test_get_tag_samples_for_tags_in_the_text_and_result_tags() {
		$fields = [
			[
				'fieldType'   => 'number',
				'slug'        => 'deposit',
				'placeholder' => 'e.g. 1,200',
			],
			[
				'fieldType'   => 'payment',
				'slug'        => 'pay',
				'amountType'  => 'fixed',
				'fixedAmount' => '10',
			],
			[
				'fieldType'           => 'payment',
				'slug'                => 'balance',
				'amountType'          => 'variable',
				'variableAmountField' => 'deposit',
			],
		];
		$text   = 'Paid {form-payment:pay:amount} and {form-payment:balance:amount} as {form-payment:pay:name}. {form-payment:pay:unknown} {form:deposit} {calculation-result}';

		$samples = Form_Templates::get_tag_samples( $text, [ [ 'tag' => '{quiz_score}', 'label' => 'Quiz score' ] ], $fields );

		$this->assertSame(
			[
				'{form-payment:pay:amount}'     => '$10.00',
				'{form-payment:balance:amount}' => '$1,200.00',
				'{form-payment:pay:name}'       => 'Jane Smith',
				// No calculated field, so the fixed example.
				'{calculation-result}'          => Form_Templates::CALCULATION_FALLBACK,
				'{quiz_score}'                  => '8 / 10',
			],
			$samples,
			'Answers and unknown payment properties have no sample.'
		);
	}

	public function test_calculated_sample_rounds_without_prefix_or_suffix() {
		$fields = [
			[
				'fieldType'   => 'number',
				'slug'        => 'hours',
				'placeholder' => 'e.g. 10',
			],
			[
				'fieldType'          => 'number',
				'slug'               => 'per-day',
				'calculationFormula' => '{form:hours} / 3',
				'prefix'             => '~',
				'suffix'             => 'h',
			],
		];

		// Entries save the raw value, so the prefix and suffix are left out.
		$this->assertSame( '3.33', Form_Templates::calculated_sample( $fields[1], $fields ), 'Fractions show two decimals.' );

		$fields[1]['blockAttributes'] = [ 'calculationRound' => 0 ];
		$this->assertSame( '3', Form_Templates::calculated_sample( $fields[1], $fields ), 'The field rounding applies.' );

		$this->assertNull( Form_Templates::calculated_sample( $fields[0], $fields ), 'A field without a formula is not calculated.' );
	}

	public function test_evaluate_formula_reads_escaped_tags_choices_hidden_and_calculated_fields() {
		$fields = [
			[
				'fieldType'    => 'dropdown',
				'slug'         => 'plan',
				'fieldOptions' => [
					[
						'label' => 'Basic',
						'value' => '3',
					],
				],
			],
			[
				'fieldType'    => 'hidden',
				'slug'         => 'discount',
				'defaultValue' => '-4',
			],
			[
				'fieldType'          => 'number',
				'slug'               => 'double',
				'calculationFormula' => '{form:plan} * 2',
			],
			[
				'fieldType'          => 'number',
				'slug'               => 'loop',
				'calculationFormula' => '{form:loop} + 1',
			],
		];

		$this->assertSame( 3.0, Form_Templates::evaluate_formula( '%{%form:plan%}%', $fields ), 'The editor escapes tags as %{%...%}%.' );
		$this->assertSame( 7.0, Form_Templates::evaluate_formula( '{form:double} + 1', $fields ) );
		$this->assertSame( 14.0, Form_Templates::evaluate_formula( '10 - {form:discount}', $fields ), 'A negative answer stays one value.' );

		$this->assertNull( Form_Templates::evaluate_formula( '{form:missing} + 1', $fields ) );
		$this->assertNull( Form_Templates::evaluate_formula( '{other:plan} + 1', $fields ) );
		$this->assertNull( Form_Templates::evaluate_formula( '{form:loop}', $fields ), 'A formula that uses itself gives up.' );
	}

	public function test_get_templates_for_js_shows_a_locked_templates_premium_version() {
		add_filter( 'srfm_pro_tier', static fn() => 'free' );
		add_filter(
			'srfm_form_templates',
			static function ( $templates ) {
				$templates[] = [
					'id'            => 'premium-ui',
					'title'         => 'Premium',
					'category'      => 'contact',
					'requires'      => 'business',
					'preview_image' => 'premium.webp',
					'setup'         => [ 'stripe' ],
					'fields'        => [
						[
							'fieldType' => 'input',
							'slug'      => 'name',
							'label'     => 'Name',
							'required'  => false,
						],
						[
							// A signature has no answer to include, so a rating.
							'fieldType' => 'rating',
							'slug'      => 'stars',
							'label'     => 'Stars',
							'required'  => false,
							'pro_tier'  => 'business',
						],
					],
				];
				return $templates;
			}
		);
		$this->reset_template_cache();

		$found = wp_list_filter( Form_Templates::get_templates_for_js(), [ 'id' => 'premium-ui' ] );
		$item  = reset( $found );
		remove_all_filters( 'srfm_pro_tier' );

		$this->assertTrue( $item['is_locked'] );
		$this->assertSame( SRFM_URL . 'images/templates/premium.webp', $item['preview_image'] );
		$this->assertSame( 1, $item['preview_missing'] );
		$this->assertSame( [ 'stripe' ], array_column( $item['setup'], 'id' ) );
		// The messages describe the Premium version, so its answers include the Business field.
		$this->assertContains( 'stars', array_column( $item['answers'], 'slug' ) );
		$this->assertNotContains( 'Stars', $item['fields'], 'The field list is what this site builds.' );
	}

	public function test_get_unlock_url_matches_the_lock_reason() {
		$this->assertSame( '', Form_Templates::get_unlock_url( 'quiz', '' ) );
		$this->assertSame( admin_url( 'plugins.php?plugin_status=upgrade' ), Form_Templates::get_unlock_url( 'quiz', 'update' ) );
		$this->assertStringContainsString( 'utm_content=template-quiz', Form_Templates::get_unlock_url( 'quiz', 'tier' ) );
	}

	public function test_get_messages_carries_result_tags_and_their_samples() {
		$messages = Form_Templates::get_messages(
			[
				'quiz'          => [ 'pass' => 70 ],
				'confirmation'  => [
					'heading' => 'You scored {quiz_score}',
					'text'    => 'Thanks',
				],
				'email_subject' => 'Paid {form-payment:pay:amount}',
			],
			[]
		);

		$this->assertContains( '{quiz_percentage}', array_column( $messages['result_tags'], 'tag' ) );
		$this->assertSame( '8 / 10', $messages['tag_samples']['{quiz_score}'] );
		$this->assertSame( '80%', $messages['tag_samples']['{quiz_percentage}'], 'Result tags get a sample even when no message uses them yet.' );
		$this->assertSame( '$49.00', $messages['tag_samples']['{form-payment:pay:amount}'], 'Without the payment field, the example amount.' );
		$this->assertSame( 'You scored {quiz_score}', $messages['confirmation']['heading'] );
	}

	public function test_get_answers_samples_number_placeholders_and_calculations() {
		$fields  = [
			[
				'fieldType'   => 'number',
				'slug'        => 'price',
				'label'       => 'Price',
				'placeholder' => 'e.g. 1,200',
			],
			[
				'fieldType'          => 'number',
				'slug'               => 'total',
				'label'              => 'Total',
				'calculationFormula' => '{form:price} * 2',
				'prefix'             => '$',
			],
		];
		$answers = array_column( Form_Templates::get_answers( $fields ), 'sample', 'slug' );

		$this->assertSame( '1,200', $answers['price'], "A number field's example placeholder is its sample." );
		$this->assertSame( '2,400', $answers['total'], 'Calculated answers leave out the prefix, as entries save the raw value.' );
	}

	/**
	 * Add one-field test templates through `srfm_form_templates`.
	 *
	 * @param array<int, array<string, mixed>> $templates Keys over a one-field template; `id` required.
	 */
	private function add_tier_templates( $templates ) {
		add_filter(
			'srfm_form_templates',
			static function ( $list ) use ( $templates ) {
				foreach ( $templates as $template ) {
					$list[] = array_merge(
						[
							'title'    => 'Tier test',
							'category' => 'contact',
							'fields'   => [
								[
									'fieldType' => 'input',
									'slug'      => 'name',
									'label'     => 'Name',
									'required'  => false,
								],
							],
						],
						$template
					);
				}
				return $list;
			}
		);
		$this->reset_template_cache();
	}

	private function use_tier( $tier ) {
		remove_all_filters( 'srfm_pro_tier' );
		add_filter( 'srfm_pro_tier', static fn() => $tier );
	}

	public function test_get_templates_keeps_plan_keys_and_cleans_the_id() {
		$this->add_tier_templates(
			[
				[
					'id'         => 'Tier Test!',
					'requires'   => 'business',
					'conditions' => [ 'name' => [ 'show', [] ] ],
				],
			]
		);

		$templates = Form_Templates::get_templates();

		$this->assertArrayHasKey( 'tiertest', $templates );
		$this->assertSame( 'tiertest', $templates['tiertest']['id'] );
		$this->assertSame( 'business', $templates['tiertest']['requires'] );
		$this->assertArrayHasKey( 'conditions', $templates['tiertest'] );
	}

	public function test_get_templates_for_js_reports_the_lock_for_the_site_plan() {
		$this->add_tier_templates(
			[
				[
					'id'       => 'business-only',
					'requires' => 'business',
				],
			]
		);
		$item = static function ( $id ) {
			$found = wp_list_filter( Form_Templates::get_templates_for_js(), [ 'id' => $id ] );
			return reset( $found );
		};

		$this->use_tier( 'pro' );
		$locked = $item( 'business-only' );
		$this->assertSame( 'business', $locked['requires'] );
		$this->assertTrue( $locked['is_pro'] );
		$this->assertTrue( $locked['is_locked'] );
		$this->assertSame( 'tier', $locked['lock_reason'] );
		$this->assertStringContainsString( 'utm_content=template-business-only', $locked['unlock_url'] );

		$this->use_tier( 'business' );
		$open = $item( 'business-only' );
		$this->assertFalse( $open['is_locked'] );
		$this->assertSame( '', $open['lock_reason'] );
		$this->assertSame( '', $open['unlock_url'] );
		$this->assertSame( [], $open['pro_features'], 'Business has nothing left to unlock.' );

		$free = $item( 'contact-us' );
		$this->assertSame( 'free', $free['requires'] );
		$this->assertFalse( $free['is_pro'] );
	}

	public function test_get_required_tier_reads_requires_then_is_pro() {
		$this->assertSame( 'free', Form_Templates::get_required_tier( [] ) );
		$this->assertSame( 'pro', Form_Templates::get_required_tier( [ 'requires' => 'pro' ] ) );
		$this->assertSame( 'starter', Form_Templates::get_required_tier( [ 'is_pro' => true ] ), 'The older is_pro flag means Starter.' );
		$this->assertSame( 'business', Form_Templates::get_required_tier( [ 'requires' => 'business', 'is_pro' => true ] ) );
		$this->assertSame( 'free', Form_Templates::get_required_tier( [ 'requires' => 'enterprise' ] ), 'An unknown plan is ignored.' );
	}

	public function test_lock_reason_for_plan_builder_version_and_filter() {
		if ( defined( 'SRFM_PRO_TEMPLATE_API' ) ) {
			$this->markTestSkipped( 'SureForms Pro is loaded in this run.' );
		}

		$this->use_tier( 'starter' );
		$this->assertSame( 'tier', Form_Templates::lock_reason( [ 'requires' => 'pro' ] ) );
		$this->assertSame( '', Form_Templates::lock_reason( [ 'requires' => 'starter' ] ) );

		$this->use_tier( 'business' );
		// The Pro build has no template builder here, so its keys can't be built.
		$this->assertSame(
			'update',
			Form_Templates::lock_reason(
				[
					'requires' => 'business',
					'quiz'     => [],
				]
			)
		);
		// A free template's Pro keys only add to it, so they never lock it.
		$this->assertSame( '', Form_Templates::lock_reason( [ 'quiz' => [] ] ) );

		add_filter( 'srfm_form_template_is_available', '__return_false' );
		$this->assertSame( 'tier', Form_Templates::lock_reason( [] ), 'A filter can lock any template.' );
	}

	public function test_is_template_available_follows_the_lock_reason() {
		$this->use_tier( 'free' );

		$this->assertTrue( Form_Templates::is_template_available( [] ) );
		$this->assertFalse( Form_Templates::is_template_available( [ 'requires' => 'starter' ] ) );

		add_filter( 'srfm_form_template_is_available', '__return_true' );
		$this->assertTrue( Form_Templates::is_template_available( [ 'requires' => 'starter' ] ), 'A filter can unlock a template.' );
	}

	public function test_get_pro_template_api_reads_the_pro_constant() {
		$expected = defined( 'SRFM_PRO_TEMPLATE_API' ) ? (int) constant( 'SRFM_PRO_TEMPLATE_API' ) : 0;

		$this->assertSame( $expected, Form_Templates::get_pro_template_api() );
	}

	public function test_get_required_pro_template_api_per_feature_key() {
		$this->assertSame( 0, Form_Templates::get_required_pro_template_api( [ 'requires' => 'business' ] ), 'No Pro keys, no builder needed.' );
		$this->assertSame(
			1,
			Form_Templates::get_required_pro_template_api(
				[
					'conditions'  => [],
					'save_resume' => true,
				]
			)
		);
		$this->assertSame(
			2,
			Form_Templates::get_required_pro_template_api(
				[
					'conditions' => [],
					'pdf'        => [],
				]
			),
			'The highest version a key needs wins.'
		);

		// Every Pro key has a version: the Business keys arrive with version 2, the rest with 1.
		$this->assertSame( array_keys( Form_Templates::PRO_FEATURE_KEYS ), array_keys( Form_Templates::PRO_FEATURE_API ) );
		foreach ( Form_Templates::PRO_FEATURE_KEYS as $key => $tier ) {
			$this->assertSame( 'business' === $tier ? 2 : 1, Form_Templates::get_required_pro_template_api( [ $key => [] ] ), $key );
		}
	}

	public function test_get_unlock_url_by_lock_reason() {
		$this->assertSame( '', Form_Templates::get_unlock_url( 'rsvp', '' ) );
		$this->assertStringContainsString( 'plugins.php?plugin_status=upgrade', Form_Templates::get_unlock_url( 'rsvp', 'update' ) );

		$pricing = Form_Templates::get_unlock_url( 'rsvp', 'tier' );
		$this->assertStringContainsString( 'utm_medium=form-templates', $pricing );
		$this->assertStringContainsString( 'utm_content=template-rsvp', $pricing );
		$this->assertStringNotContainsString( '&#038;', $pricing, 'The URL is opened from JS, so it is not HTML-escaped.' );
	}

	public function test_resolve_fields_keeps_fields_from_their_pro_tier() {
		$template = [
			'fields' => [
				[
					'fieldType' => 'input',
					'slug'      => 'name',
				],
				[
					'fieldType' => 'signature',
					'slug'      => 'signature',
					'pro_tier'  => 'pro',
				],
				[
					'fieldType' => 'input',
					'slug'      => 'when',
					'pro_tier'  => 'business',
					'pro'       => [ 'fieldType' => 'date-picker' ],
				],
			],
		];
		$types = static fn( $tier ) => wp_list_pluck( Form_Templates::resolve_fields( $template, $tier ), 'fieldType', 'slug' );

		$this->assertSame(
			[
				'name' => 'input',
				'when' => 'input',
			],
			$types( 'starter' )
		);
		$this->assertSame(
			[
				'name'      => 'input',
				'signature' => 'signature',
				'when'      => 'input',
			],
			$types( 'pro' )
		);
		$this->assertSame( 'date-picker', $types( 'business' )['when'] );
		$this->assertSame( $types( 'business' ), $types( true ), '`true` is read as Business.' );
		$this->assertSame( $types( 'free' ), $types( false ), '`false` is read as free.' );
	}

	public function test_get_features_counts_pro_keys_from_their_plan() {
		$template = [
			'conditions' => [ 'name' => [ 'show', [] ] ],
			'quiz'       => [ 'questions' => [] ],
			'features'   => [ 'calculation', 'calculation' ],
		];
		$fields   = [ [ 'fieldType' => 'signature' ] ];

		$starter = Form_Templates::get_features( $template, $fields, 'starter' );
		$this->assertContains( 'conditional_logic', $starter );
		$this->assertContains( 'calculation', $starter );
		$this->assertContains( 'signature', $starter );
		$this->assertNotContains( 'quiz', $starter, 'Quiz is built from Business.' );
		$this->assertSame( $starter, array_values( array_unique( $starter ) ) );

		$this->assertContains( 'quiz', Form_Templates::get_features( $template, $fields, 'business' ) );
		$this->assertNotContains( 'conditional_logic', Form_Templates::get_features( $template, $fields, 'free' ) );

		$this->use_tier( 'business' );
		$this->assertContains( 'quiz', Form_Templates::get_features( $template, $fields ), "Defaults to the site's plan." );
	}

	public function test_build_content_passes_the_markup_through_the_content_filter() {
		$seen = null;
		add_filter(
			'srfm_form_template_content',
			static function ( $content, $template, $fields ) use ( &$seen ) {
				$seen = [ $template['id'], wp_list_pluck( $fields, 'slug' ) ];
				return $content . '<!-- srfm-content-filter -->';
			},
			10,
			3
		);

		$content = Form_Templates::build_content( Form_Templates::get_template( 'contact-us' ) );

		$this->assertStringEndsWith( '<!-- srfm-content-filter -->', $content );
		$this->assertSame( 'contact-us', $seen[0] );
		$this->assertContains( 'email', $seen[1] );
	}

	public function test_build_metas_gives_the_metas_filter_the_content_and_context() {
		$seen = null;
		add_filter(
			'srfm_form_template_metas',
			static function ( $metas, $template, $content, $fields, $context ) use ( &$seen ) {
				$seen                    = [ $content, $context ];
				$metas['_srfm_test_key'] = 'added';
				return $metas;
			},
			10,
			5
		);

		$metas = Form_Templates::build_metas( Form_Templates::get_template( 'rsvp' ), [], '<!-- given -->', 'preview' );

		$this->assertSame( [ '<!-- given -->', 'preview' ], $seen );
		$this->assertSame( 'added', $metas['_srfm_test_key'] );
		$this->assertArrayHasKey( '_srfm_form_confirmation', $metas );
		$this->assertArrayHasKey( '_srfm_forms_styling', $metas );
	}

	public function test_build_settings_metas_defaults_to_the_swap_context() {
		$template = Form_Templates::get_template( 'rsvp' );
		$plain    = Form_Templates::build_settings_metas( $template );
		$this->assertArrayNotHasKey( '_srfm_form_confirmation', $plain, 'Settings metas leave the thank-you message out.' );

		$context = null;
		add_filter(
			'srfm_form_template_metas',
			static function ( $metas, $template, $content, $fields, $ctx ) use ( &$context ) {
				$context = [ $ctx, $content ];
				return [ '_srfm_only' => 1 ];
			},
			10,
			5
		);

		$this->assertSame( [ '_srfm_only' => 1 ], Form_Templates::build_settings_metas( $template ) );
		$this->assertSame( 'swap', $context[0] );
		$this->assertStringContainsString( '<!-- wp:srfm/', $context[1], 'The content is built when none is given.' );
	}

	public function test_get_categories_match_the_catalog_files() {
		$ids = wp_list_pluck( Form_Templates::get_categories(), 'id' );

		$this->assertSame( [], array_values( array_diff( $ids, Catalog::FILES ) ), 'Each category has a catalog file.' );
		foreach ( Catalog::templates() as $template ) {
			$this->assertContains( $template['category'], $ids, $template['id'] );
		}
	}

	public function test_rest_list_templates_reports_the_pro_tier() {
		$this->use_tier( 'pro' );

		$data = ( new Form_Templates() )->rest_list_templates()->get_data();

		$this->assertSame( 'pro', $data['pro_tier'] );
		$this->assertArrayHasKey( 'lock_reason', $data['templates'][0] );
	}

	public function test_rest_get_template_content_refuses_a_locked_template_with_its_reason() {
		$this->login_admin();
		$this->add_tier_templates(
			[
				[
					'id'       => 'pro-locked',
					'requires' => 'pro',
				],
			]
		);
		$this->use_tier( 'starter' );

		$request = new WP_REST_Request( 'GET', '/sureforms/v1/form-templates/pro-locked' );
		$request->set_param( 'template_id', 'pro-locked' );
		$result = ( new Form_Templates() )->rest_get_template_content( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_template_locked', $result->get_error_code() );
		$this->assertSame( 403, $result->get_error_data()['status'] );
		$this->assertSame( 'tier', $result->get_error_data()['reason'] );
		$this->assertSame( 'pro', $result->get_error_data()['requires'] );
	}

	public function test_maybe_render_preview_builds_a_locked_template_in_the_preview_context() {
		$this->login_admin();
		$this->add_tier_templates(
			[
				[
					'id'       => 'preview-locked',
					'requires' => 'business',
				],
			]
		);
		$this->use_tier( 'free' );

		// Stop at the metas filter, before the page is printed and the request ends.
		$context = null;
		add_filter(
			'srfm_form_template_metas',
			static function ( $metas, $template, $content, $fields, $ctx ) use ( &$context ) {
				$context = [ $template['id'], $ctx ];
				throw new RuntimeException( 'srfm-preview-built' );
			},
			10,
			5
		);

		$_GET[ Form_Templates::PREVIEW_QUERY_VAR ] = 'preview-locked';
		try {
			( new Form_Templates() )->maybe_render_preview();
			$this->fail( 'The preview should have been built.' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'srfm-preview-built', $e->getMessage() );
		} finally {
			unset( $_GET[ Form_Templates::PREVIEW_QUERY_VAR ] );
		}

		$this->assertSame( [ 'preview-locked', 'preview' ], $context, 'A locked template still previews.' );
	}

	public function test_get_message_meta_keys_lists_the_form_messages() {
		$this->assertSame(
			[ '_srfm_submit_button_text', '_srfm_email_notification', '_srfm_form_confirmation', '_srfm_page_break_settings' ],
			Form_Templates::get_message_meta_keys()
		);

		// The emails, thank-you message and button text a swap sends are all message keys,
		// so leaving the message keys out keeps the form's own.
		$request = new WP_REST_Request( 'GET', '/sureforms/v1/form-templates/rsvp' );
		$request->set_param( 'template_id', 'rsvp' );
		$this->login_admin();
		$settings = ( new Form_Templates() )->rest_get_template_content( $request )->get_data()['settings'];
		foreach ( [ '_srfm_submit_button_text', '_srfm_email_notification', '_srfm_form_confirmation' ] as $key ) {
			$this->assertArrayHasKey( $key, $settings );
			$this->assertContains( $key, Form_Templates::get_message_meta_keys() );
		}
		// Fields and design are never message keys: the swap always applies them.
		$this->assertNotContains( '_srfm_forms_styling', Form_Templates::get_message_meta_keys() );
		$this->assertNotContains( '_srfm_is_inline_button', Form_Templates::get_message_meta_keys() );
	}

	public function test_get_message_meta_keys_can_be_extended_by_pro() {
		add_filter(
			'srfm_form_template_message_meta_keys',
			static function ( $keys ) {
				$keys[] = '_srfm_conditional_confirmations';
				$keys[] = '_srfm_email_notification';
				$keys[] = [ 'not-a-key' ];
				return $keys;
			}
		);

		$keys = Form_Templates::get_message_meta_keys();
		$this->assertContains( '_srfm_conditional_confirmations', $keys );
		$this->assertSame( $keys, array_values( array_unique( $keys ) ), 'Keys are listed once.' );
		$this->assertContainsOnly( 'string', $keys );

		add_filter( 'srfm_form_template_message_meta_keys', '__return_null', 20 );
		$this->assertSame(
			[ '_srfm_submit_button_text', '_srfm_email_notification', '_srfm_form_confirmation', '_srfm_page_break_settings' ],
			Form_Templates::get_message_meta_keys(),
			'A broken filter falls back to the default keys, so the form keeps its messages.'
		);
	}

	public function test_rest_list_templates_sends_the_message_meta_keys() {
		$this->login_admin();

		$data = ( new Form_Templates() )->rest_list_templates()->get_data();

		$this->assertSame( Form_Templates::get_message_meta_keys(), $data['message_meta_keys'] );
		$this->assertNotEmpty( $data['templates'] );
		$this->assertArrayHasKey( 'spam_protection', $data );
	}

	public function test_autoresponder_body_never_echoes_answers() {
		$this->turn_on_spam_protection();

		foreach ( Form_Templates::get_templates() as $id => $template ) {
			$emails = Form_Templates::build_settings_metas( $template )['_srfm_email_notification'];
			foreach ( $emails as $email ) {
				if ( '{form:email}' !== $email['email_to'] ) {
					continue;
				}
				// The address is typed into the form, so free text echoed back
				// would let bots send their own text from this site to anyone.
				$this->assertStringNotContainsString( '{all_data}', $email['email_body'], "Template {$id} echoes the answers to the visitor." );
				$this->assertStringNotContainsString( '{form:message}', $email['email_body'], "Template {$id} echoes the message to the visitor." );
			}
			// The site owner's notification keeps every answer.
			$this->assertStringEndsWith( '{all_data}', $emails[0]['email_body'] );
		}

		// A filtered template asking for a summary still gets none.
		$template                             = Form_Templates::get_template( 'contact-us' );
		$template['autoresponder']['summary'] = true;
		$emails                               = Form_Templates::build_settings_metas( $template )['_srfm_email_notification'];
		$this->assertStringNotContainsString( '{all_data}', $emails[1]['email_body'] );
	}

	public function test_build_settings_metas_holds_the_visitor_email_without_spam_protection() {
		delete_option( 'srfm_security_settings_options' );

		$emails = Form_Templates::build_settings_metas( Form_Templates::get_template( 'contact-us' ) )['_srfm_email_notification'];
		$this->assertCount( 1, $emails, 'Only the site owner is notified.' );
		$this->assertSame( '{admin_email}', $emails[0]['email_to'] );
		$this->assertTrue( $emails[0]['status'] );

		$this->turn_on_spam_protection();
		$this->assertCount( 2, Form_Templates::build_settings_metas( Form_Templates::get_template( 'contact-us' ) )['_srfm_email_notification'] );
	}

	public function test_rest_create_form_without_spam_protection_has_no_visitor_email() {
		$this->login_admin();
		delete_option( 'srfm_security_settings_options' );

		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/create' );
		$request->set_param( 'template_id', 'contact-us' );
		$post_id = ( new Form_Templates() )->rest_create_form( $request )->get_data()['id'];

		$emails = get_post_meta( $post_id, '_srfm_email_notification', true );
		$this->assertCount( 1, $emails );
		$this->assertNotSame( '{form:email}', $emails[0]['email_to'] );
		$this->assertFalse( Form_Templates::is_spam_protection_on() );
	}

	public function test_rest_create_form_turning_on_spam_protection_keeps_the_visitor_email() {
		$this->login_admin();
		delete_option( 'srfm_security_settings_options' );

		// What "Protect this form from spam" (ticked by default) sends.
		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/create' );
		$request->set_param( 'template_id', 'contact-us' );
		$request->set_param( 'enable_spam_protection', true );
		$post_id = ( new Form_Templates() )->rest_create_form( $request )->get_data()['id'];

		$emails = get_post_meta( $post_id, '_srfm_email_notification', true );
		$this->assertCount( 2, $emails );
		$this->assertSame( '{form:email}', $emails[1]['email_to'] );
		$this->assertTrue( Form_Templates::is_spam_protection_on() );
	}

	public function test_register_routes_adds_every_template_route() {
		do_action( 'rest_api_init' );
		$routes = rest_get_server()->get_routes();

		foreach ( [ '/sureforms/v1/form-templates', '/sureforms/v1/form-templates/create', '/sureforms/v1/form-templates/request', '/sureforms/v1/form-templates/swap', '/sureforms/v1/form-templates/(?P<template_id>[a-z0-9-]+)' ] as $route ) {
			$this->assertArrayHasKey( $route, $routes, "{$route} is not registered." );
		}

		// Every route needs a user who can manage forms.
		wp_set_current_user( 0 );
		$this->assertSame( 401, rest_do_request( new WP_REST_Request( 'GET', '/sureforms/v1/form-templates' ) )->get_status() );
		$this->login_admin();
		$this->assertSame( 200, rest_do_request( new WP_REST_Request( 'GET', '/sureforms/v1/form-templates' ) )->get_status() );
	}

	public function test_get_answers_skips_fields_nobody_answers_in_an_email() {
		$fields = [
			[ 'fieldType' => 'input', 'slug' => 'full-name', 'label' => 'Name', 'required' => true ],
			[ 'fieldType' => 'gdpr', 'slug' => 'consent', 'label' => 'I agree' ],
			[ 'fieldType' => 'page-break', 'slug' => 'step-2', 'label' => 'Step 2' ],
			[ 'fieldType' => 'upload', 'slug' => 'files', 'label' => 'Files' ],
			[ 'fieldType' => 'email', 'slug' => 'email', 'label' => 'Email' ],
			[ 'fieldType' => 'dropdown', 'slug' => 'topic', 'label' => 'Topic', 'fieldOptions' => [ [ 'label' => 'Sales' ] ] ],
		];

		$answers = Form_Templates::get_answers( $fields );

		$this->assertSame( [ 'full-name', 'email', 'topic' ], wp_list_pluck( $answers, 'slug' ) );
		$this->assertSame( 'Jane Smith', $answers[0]['sample'] );
		$this->assertTrue( $answers[0]['required'] );
		$this->assertSame( 'jane@example.com', $answers[1]['sample'] );
		$this->assertFalse( $answers[1]['required'] );
		$this->assertSame( 'Sales', $answers[2]['sample'], 'Choices use their first option.' );
	}

	public function test_apply_messages_merges_edits_and_can_drop_the_visitor_email() {
		$template = Form_Templates::get_template( 'contact-us' );

		$edited = Form_Templates::apply_messages(
			$template,
			[
				'confirmation'  => [ 'heading' => 'Got it' ],
				'email_subject' => '',
				'autoresponder' => [ 'subject' => 'Hello', 'lines' => [] ],
			]
		);
		$this->assertSame( 'Got it', $edited['confirmation']['heading'] );
		$this->assertSame( $template['confirmation']['text'], $edited['confirmation']['text'] );
		$this->assertSame( $template['email_subject'], $edited['email_subject'], 'An emptied subject keeps the template text.' );
		$this->assertSame( 'Hello', $edited['autoresponder']['subject'] );
		$this->assertSame( $template['autoresponder']['lines'], $edited['autoresponder']['lines'], 'Empty values are ignored.' );

		$off = Form_Templates::apply_messages( $template, [ 'autoresponder' => [ 'enabled' => false ] ] );
		$this->assertArrayNotHasKey( 'autoresponder', $off );
	}

	public function test_get_features_lists_what_a_template_sets_up() {
		$this->assertSame(
			[ 'notification', 'autoresponder', 'consent' ],
			Form_Templates::get_features( Form_Templates::get_template( 'contact-us' ), Form_Templates::resolve_fields( Form_Templates::get_template( 'contact-us' ), false ) )
		);

		// No required email, so no confirmation to the visitor.
		$callback = Form_Templates::get_template( 'callback-request' );
		$this->assertNotContains( 'autoresponder', Form_Templates::get_features( $callback, Form_Templates::resolve_fields( $callback, false ) ) );

		$job = Form_Templates::get_template( 'job-application' );
		$this->assertContains( 'multi_step', Form_Templates::get_features( $job, Form_Templates::resolve_fields( $job, true ) ) );
		$this->assertContains( 'file_upload', Form_Templates::get_features( $job, Form_Templates::resolve_fields( $job, true ) ) );
	}

	public function test_build_metas_stamps_the_form_and_sets_its_messages() {
		$this->turn_on_spam_protection();
		$template = Form_Templates::get_template( 'contact-us' );

		$metas = Form_Templates::build_metas( $template );

		$this->assertSame( 'template', $metas[ Form_Templates::CREATED_VIA_META ] );
		$this->assertSame( 'contact-us', $metas[ Form_Templates::TEMPLATE_ID_META ] );
		$this->assertStringContainsString( 'Thanks, {form:first-name}!', $metas['_srfm_form_confirmation'][0]['message'] );
		$this->assertSame( 'Send message', $metas['_srfm_submit_button_text'] );
		$this->assertCount( 2, $metas['_srfm_email_notification'] );
		$this->assertSame( 1, $metas['_srfm_single_page_form_title'] );
		// Starts from the defaults a hand-made form gets.
		$this->assertArrayHasKey( '_srfm_form_container_width', $metas );
	}

	public function test_build_style_metas_follow_the_chosen_style() {
		$template = Form_Templates::get_template( 'contact-us' );
		$style    = [ 'preset' => 'midnight' ];
		$theme    = Form_Templates::resolve_theme( $template, $style );

		$metas = Form_Templates::build_style_metas( $template, $style );

		$this->assertSame( [ '_srfm_forms_styling', '_srfm_instant_form_settings', '_srfm_single_page_form_title' ], array_keys( $metas ) );
		$this->assertSame( $theme['primary'], $metas['_srfm_forms_styling']['primary_color'] );
		$this->assertSame( $theme['background'], $metas['_srfm_forms_styling']['bg_gradient'] );
		$this->assertSame( $theme['radius'], $metas['_srfm_forms_styling']['instant_form_border_radius_right'] );
		$this->assertSame( $theme['banner'], $metas['_srfm_instant_form_settings']['cover_color'] );
	}

	public function test_is_spam_protection_on_reads_the_honeypot_setting() {
		delete_option( 'srfm_security_settings_options' );
		$this->assertFalse( Form_Templates::is_spam_protection_on() );

		update_option( 'srfm_security_settings_options', 'garbage' );
		$this->assertFalse( Form_Templates::is_spam_protection_on() );

		update_option( 'srfm_security_settings_options', [ 'srfm_honeypot' => false ] );
		$this->assertFalse( Form_Templates::is_spam_protection_on() );

		$this->turn_on_spam_protection();
		$this->assertTrue( Form_Templates::is_spam_protection_on() );
	}

	public function test_enable_spam_protection_turns_on_the_honeypot_only() {
		update_option( 'srfm_security_settings_options', [ 'srfm_cf_appearance_mode' => 'dark' ] );

		Form_Templates::enable_spam_protection();

		$this->assertSame(
			[
				'srfm_cf_appearance_mode' => 'dark',
				'srfm_honeypot'           => true,
			],
			get_option( 'srfm_security_settings_options' )
		);
	}

	public function test_is_usage_sharing_on_follows_the_opt_in() {
		$previous = get_site_option( 'sureforms_usage_optin', false );

		update_site_option( 'sureforms_usage_optin', 'no' );
		$this->assertFalse( Form_Templates::is_usage_sharing_on() );

		update_site_option( 'sureforms_usage_optin', 'yes' );
		$this->assertTrue( Form_Templates::is_usage_sharing_on() );

		// The global BSF switch wins over the opt-in.
		add_filter( 'bsf_usage_tracking_enabled', '__return_false' );
		$this->assertFalse( Form_Templates::is_usage_sharing_on() );
		remove_filter( 'bsf_usage_tracking_enabled', '__return_false' );

		false === $previous ? delete_site_option( 'sureforms_usage_optin' ) : update_site_option( 'sureforms_usage_optin', $previous );
	}

	public function test_get_request_settings_for_js_follow_usage_sharing() {
		add_filter( 'sureforms_tracking_enabled', '__return_false' );
		$settings = Form_Templates::get_request_settings_for_js();
		remove_filter( 'sureforms_tracking_enabled', '__return_false' );

		$this->assertFalse( $settings['enabled'] );
		$this->assertStringContainsString( 'page=sureforms_form_settings', $settings['settings_url'] );

		add_filter( 'sureforms_tracking_enabled', '__return_true' );
		$this->assertTrue( Form_Templates::get_request_settings_for_js()['enabled'] );
		remove_filter( 'sureforms_tracking_enabled', '__return_true' );
	}

	public function test_rest_record_swap_stamps_the_form() {
		$this->login_admin();
		$post_id = Form_Templates::create_form_from_template( 'contact-us' );

		$request = new WP_REST_Request( 'POST', '/sureforms/v1/form-templates/swap' );
		$request->set_param( 'form_id', $post_id );
		$request->set_param( 'template_id', 'rsvp' );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
		$this->assertSame( 'rsvp', get_post_meta( $post_id, Form_Templates::TEMPLATE_ID_META, true ) );
	}

	public function test_forget_preview_form_clears_the_preview_cache() {
		$property = new ReflectionProperty( Form_Templates::class, 'preview_post_id' );
		$property->setAccessible( true );

		// Nothing previewed: nothing to clear, and nothing breaks.
		$property->setValue( null, null );
		Form_Templates::forget_preview_form();

		$property->setValue( null, 2147000001 );
		wp_cache_set( 2147000001, (object) [ 'ID' => 2147000001 ], 'posts' );
		wp_cache_set( 2147000001, [ '_srfm_template_id' => [ 'contact-us' ] ], 'post_meta' );

		Form_Templates::forget_preview_form();

		$this->assertFalse( wp_cache_get( 2147000001, 'posts' ) );
		$this->assertFalse( wp_cache_get( 2147000001, 'post_meta' ) );
		$property->setValue( null, null );
	}

	public function test_maybe_render_preview_needs_the_query_var_and_capability() {
		$die = static function () {
			return static function ( $message, $title, $args ) {
				throw new RuntimeException( (string) ( $args['response'] ?? 0 ) );
			};
		};
		add_filter( 'wp_die_handler', $die );

		// Not a preview request: the page renders as usual.
		unset( $_GET[ Form_Templates::PREVIEW_QUERY_VAR ] );
		$this->assertNull( ( new Form_Templates() )->maybe_render_preview() );

		$_GET[ Form_Templates::PREVIEW_QUERY_VAR ] = 'contact-us';
		$status                                    = 0;
		try {
			( new Form_Templates() )->maybe_render_preview();
		} catch ( RuntimeException $e ) {
			$status = (int) $e->getMessage();
		}
		$this->assertSame( 403, $status, 'Visitors cannot preview templates.' );

		$this->login_admin();
		$_GET[ Form_Templates::PREVIEW_QUERY_VAR ] = 'does-not-exist';
		$status                                    = 0;
		try {
			( new Form_Templates() )->maybe_render_preview();
		} catch ( RuntimeException $e ) {
			$status = (int) $e->getMessage();
		}
		$this->assertSame( 404, $status );

		unset( $_GET[ Form_Templates::PREVIEW_QUERY_VAR ] );
		remove_filter( 'wp_die_handler', $die );
	}

	// ─── what a template preview loads ───────────────────────────

	public function test_is_preview_asset_keeps_only_sureforms_core_and_theme_assets() {
		$this->assertTrue( Form_Templates::is_preview_asset( 'srfm-form-submit', SRFM_URL . 'assets/js/x.js', 'script' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( 'sureforms-pro-page-break-deps', 'https://example.com/pro.js', 'script' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( 'srfm-pro-signature-pad-lib', 'https://example.com/lib.js', 'script' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( 'any-handle', SRFM_URL . 'assets/js/x.js', 'script' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( 'hcaptcha', 'https://js.hcaptcha.com/1/api.js', 'script' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( 'global-styles', '', 'style' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( get_stylesheet() . '-style', '', 'style' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( 'theme-css', trailingslashit( get_stylesheet_directory_uri() ) . 'style.css', 'style' ) );

		$this->assertFalse( Form_Templates::is_preview_asset( 'third-party-test', 'https://example.com/pixel.js', 'script' ) );
		$this->assertFalse( Form_Templates::is_preview_asset( 'chat-widget', 'https://example.com/chat.css', 'style' ) );
		$this->assertFalse( Form_Templates::is_preview_asset( 'admin-bar', '/wp-includes/css/admin-bar.min.css', 'style' ) );
		// Core scripts stay: SureForms Pro's field scripts call wp.a11y.speak().
		$this->assertTrue( Form_Templates::is_preview_asset( 'wp-a11y', '/wp-includes/js/dist/a11y.min.js', 'script' ) );
		$this->assertTrue( Form_Templates::is_preview_asset( 'wp-a11y', includes_url( 'js/dist/a11y.min.js' ), 'script' ) );
		// The theme's scripts don't run either.
		$this->assertFalse( Form_Templates::is_preview_asset( get_stylesheet() . '-navigation', trailingslashit( get_stylesheet_directory_uri() ) . 'nav.js', 'script' ) );
		$this->assertFalse( Form_Templates::is_preview_asset( 'global-styles', '', 'script' ) );
	}

	public function test_filter_preview_assets_drops_other_plugins_and_keeps_dependencies() {
		$this->isolate_preview_globals();
		wp_enqueue_script( 'srfm-test-form', SRFM_URL . 'assets/js/test.js', [ 'wp-i18n', 'wp-hooks' ], '1', true );
		wp_enqueue_script( 'third-party-test', 'https://example.com/third-party-test.js', [], '1', true );
		wp_enqueue_style( 'srfm-test-form', SRFM_URL . 'assets/css/test.css', [], '1' );
		wp_enqueue_style( 'third-party-test', 'https://example.com/third-party-test.css', [], '1' );
		wp_register_style( 'global-styles', false, [], '1' );
		wp_enqueue_style( 'global-styles' );

		Form_Templates::filter_preview_assets();

		$this->assertSame( [ 'srfm-test-form' ], wp_scripts()->queue );
		$this->assertSame( [ 'srfm-test-form', 'global-styles' ], wp_styles()->queue );

		// The kept scripts' dependencies still print.
		wp_scripts()->all_deps( wp_scripts()->queue );
		$this->assertContains( 'wp-i18n', wp_scripts()->to_do );
		$this->assertContains( 'wp-hooks', wp_scripts()->to_do );
		$this->assertNotContains( 'third-party-test', wp_scripts()->to_do );
	}

	/**
	 * SureForms Pro enqueues wp-a11y on its own (not as a dependency) and its
	 * rating, upload, NPS, page-break and date-picker scripts call
	 * wp.a11y.speak(), so the preview must keep it.
	 */
	public function test_filter_preview_assets_keeps_wp_a11y() {
		$this->isolate_preview_globals();
		wp_enqueue_script( 'srfm-test-form', SRFM_URL . 'assets/js/test.js', [], '1', true );
		wp_enqueue_script( 'wp-a11y' );
		wp_enqueue_script( 'third-party-test', 'https://example.com/third-party-test.js', [], '1', true );

		Form_Templates::filter_preview_assets();

		$this->assertContains( 'wp-a11y', wp_scripts()->queue );
		$this->assertNotContains( 'third-party-test', wp_scripts()->queue );
	}

	public function test_remove_third_party_hooks_removes_third_party_head_and_footer_callbacks() {
		$this->isolate_preview_globals();
		// Defined outside WordPress, SureForms and the theme, like another
		// plugin's tracking snippet. (This test file is inside SureForms.)
		$file = trailingslashit( get_temp_dir() ) . 'srfm-third-party-pixel.php';
		file_put_contents( $file, '<?php return static function () { echo "<script>window.thirdPartyPixel = 1;</script>"; };' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture.
		$pixel = require $file;
		unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test fixture.
		add_action( 'wp_head', $pixel );
		add_action( 'wp_footer', $pixel, 30 );
		add_action( 'wp_enqueue_scripts', $pixel );
		add_action( 'wp_head', [ Form_Templates::class, 'filter_preview_assets' ], 5 );

		Form_Templates::remove_third_party_hooks();

		$this->assertFalse( has_action( 'wp_head', $pixel ) );
		$this->assertFalse( has_action( 'wp_footer', $pixel ) );
		$this->assertFalse( has_action( 'wp_enqueue_scripts', $pixel ) );
		// WordPress core and SureForms keep theirs.
		$this->assertSame( 8, has_action( 'wp_head', 'wp_print_styles' ) );
		$this->assertSame( 20, has_action( 'wp_footer', 'wp_print_footer_scripts' ) );
		$this->assertSame( 5, has_action( 'wp_head', [ Form_Templates::class, 'filter_preview_assets' ] ) );
	}
	public function test_set_form_id_points_unset_blocks_at_the_form() {
		$content = '<!-- wp:srfm/input {"block_id":"a","formId":0,"slug":"name"} /--><!-- wp:srfm/email {"formId":0} /--><!-- wp:srfm/url {"formId":12} /-->';

		$this->assertSame(
			'<!-- wp:srfm/input {"block_id":"a","formId":7,"slug":"name"} /--><!-- wp:srfm/email {"formId":7} /--><!-- wp:srfm/url {"formId":12} /-->',
			Form_Templates::set_form_id( $content, 7 )
		);
		$this->assertSame( $content, Form_Templates::set_form_id( $content, 0 ) );
	}

	public function test_created_form_blocks_carry_its_id() {
		$post_id = Form_Templates::create_form_from_template( 'job-application' );
		$content = get_post( $post_id )->post_content;

		$this->assertStringNotContainsString( '"formId":0', $content );
		$this->assertStringContainsString( '"formId":' . $post_id, $content );
		foreach ( parse_blocks( $content ) as $block ) {
			if ( ! empty( $block['blockName'] ) && array_key_exists( 'formId', $block['attrs'] ) ) {
				$this->assertSame( $post_id, $block['attrs']['formId'], $block['blockName'] );
			}
		}
	}

	public function test_swapped_blocks_carry_the_open_forms_id() {
		$this->login_admin();

		$request = new WP_REST_Request( 'GET', '/sureforms/v1/form-templates/rsvp' );
		$request->set_param( 'template_id', 'rsvp' );
		$request->set_param( 'form_id', 42 );
		$data = ( new Form_Templates() )->rest_get_template_content( $request )->get_data();

		$this->assertStringNotContainsString( '"formId":0', $data['content'] );
		$this->assertStringContainsString( '"formId":42', $data['content'] );
	}

	public function test_get_card_fields() {
		$card = Form_Templates::get_card_fields(
			[
				[
					'fieldType'       => 'input',
					'slug'            => 'first',
					'label'           => 'First <b>Name</b>',
					'placeholder'     => 'Jane',
					'blockAttributes' => [ 'fieldWidth' => 50 ],
				],
				[
					'fieldType' => 'hidden',
					'slug'      => 'source',
					'label'     => 'Source',
				],
				[
					'fieldType' => 'page-break',
					'slug'      => 'step',
					'label'     => 'Step two',
				],
				[
					'fieldType'    => 'dropdown',
					'slug'         => 'plan',
					'label'        => 'Plan',
					'fieldOptions' => [ [ 'label' => 'Basic' ], [ 'label' => 'Plus' ], [ 'label' => 'Max' ], [ 'label' => 'Team' ] ],
				],
				[
					'fieldType'    => 'multi-choice',
					'slug'         => 'size',
					'label'        => 'Size',
					'fieldOptions' => [ [ 'optionTitle' => 'Small' ], [ 'optionTitle' => 'Large' ] ],
				],
				[
					'fieldType'  => 'date-picker',
					'slug'       => 'day',
					'label'      => 'Day',
					'dateFormat' => 'mm/dd/yyyy',
				],
			]
		);

		$this->assertSame( [ 'input', 'dropdown', 'multi-choice', 'date-picker' ], array_column( $card, 't' ) );
		$this->assertSame(
			[
				't' => 'input',
				'l' => 'First Name',
				'p' => 'Jane',
				'o' => [],
				'w' => 'half',
			],
			$card[0]
		);
		// A dropdown without a placeholder shows its first option, and at most three.
		$this->assertSame( 'Basic', $card[1]['p'] );
		$this->assertSame( [ 'Basic', 'Plus', 'Max' ], $card[1]['o'] );
		$this->assertSame( 'full', $card[1]['w'] );
		$this->assertSame( [ 'Small', 'Large' ], $card[2]['o'] );
		// A date without a placeholder shows its format.
		$this->assertSame( 'mm/dd/yyyy', $card[3]['p'] );

		$many = array_fill(
			0,
			12,
			[
				'fieldType' => 'input',
				'label'     => 'X',
			]
		);
		$this->assertCount( 8, Form_Templates::get_card_fields( $many ) );
	}

	public function test_get_form_type() {
		$input = [
			'fieldType' => 'input',
			'label'     => 'Name',
		];
		$step  = [
			'fieldType' => 'page-break',
			'label'     => 'Step two',
		];

		$this->assertSame( 'single', Form_Templates::get_form_type( [], [] ) );
		$this->assertSame( 'single', Form_Templates::get_form_type( [], [ $input ] ) );
		$this->assertSame( 'multistep', Form_Templates::get_form_type( [], [ $input, $step, $input ] ) );
		// Conversational wins over steps; an empty conversational key doesn't count.
		$this->assertSame( 'conversational', Form_Templates::get_form_type( [ 'conversational' => [ 'start_button' => 'Go' ] ], [ $input, $step ] ) );
		$this->assertSame( 'multistep', Form_Templates::get_form_type( [ 'conversational' => [] ], [ $step ] ) );
	}
}
