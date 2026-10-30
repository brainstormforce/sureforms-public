<?php
/**
 * Class Test_Form_Templates_Tiers
 *
 * Plan tiers, lock reasons and the hooks SureForms Pro builds template
 * features through.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Templates;
use SRFM\Inc\Helper;

class Test_Form_Templates_Tiers extends TestCase {

	private const HOOKS = [
		'srfm_form_templates',
		'srfm_pro_tier',
		'srfm_form_template_is_available',
		'srfm_form_template_content',
		'srfm_form_template_metas',
		'srfm_form_template_features',
	];

	public function tear_down() {
		foreach ( self::HOOKS as $hook ) {
			remove_all_filters( $hook );
		}
		$this->reset_template_cache();
		Form_Templates::forget_preview_form();
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
				'user_login' => 'srfm_tier_admin_' . uniqid(),
				'user_pass'  => 'password',
				'user_email' => 'srfm_tier_' . uniqid() . '@example.com',
				'role'       => 'administrator',
			]
		);
		wp_set_current_user( $user_id );
	}

	private function set_tier( $tier ) {
		remove_all_filters( 'srfm_pro_tier' );
		add_filter( 'srfm_pro_tier', static fn() => $tier );
	}

	/**
	 * Add test templates through `srfm_form_templates`.
	 *
	 * @param array<int, array<string, mixed>> $templates Keys over a one-field template; `id` required.
	 */
	private function add_templates( $templates ) {
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

	private function js_item( $id ) {
		$item = wp_list_filter( Form_Templates::get_templates_for_js(), [ 'id' => $id ] );
		return reset( $item );
	}

	private function swap_request( $id ) {
		$request = new WP_REST_Request( 'GET', '/sureforms/v1/form-templates/' . $id );
		$request->set_param( 'template_id', $id );
		return ( new Form_Templates() )->rest_get_template_content( $request );
	}

	private function skip_with_pro() {
		if ( defined( 'SRFM_PRO_VER' ) ) {
			$this->markTestSkipped( 'Pro is loaded in this run.' );
		}
	}

	public function test_tier_defaults_to_free_without_pro_and_follows_the_filter() {
		$this->skip_with_pro();
		$this->assertSame( 'free', Helper::get_pro_tier() );

		// The detected tier is cached, but the filter still applies on every call.
		$this->set_tier( 'pro' );
		$this->assertSame( 'pro', Helper::get_pro_tier() );
		$this->assertTrue( Helper::has_tier( 'starter' ) );
		$this->assertTrue( Helper::has_tier( 'pro' ) );
		$this->assertFalse( Helper::has_tier( 'business' ) );

		$this->set_tier( 'gold' );
		$this->assertSame( 'free', Helper::get_pro_tier() );
		$this->assertSame( 0, Helper::tier_rank( 'gold' ) );
		$this->assertSame( 3, Helper::tier_rank( 'business' ) );
	}

	public function test_lock_matrix_for_every_tier_and_requirement() {
		$templates = [];
		foreach ( Form_Templates::TIERS as $requires ) {
			$templates[] = [
				'id'       => 'needs-' . $requires,
				'requires' => $requires,
			];
		}
		$this->add_templates( $templates );

		foreach ( Form_Templates::TIERS as $tier ) {
			$this->set_tier( $tier );
			foreach ( Form_Templates::TIERS as $requires ) {
				$locked   = Helper::tier_rank( $tier ) < Helper::tier_rank( $requires );
				$template = Form_Templates::get_template( 'needs-' . $requires );
				$item     = $this->js_item( 'needs-' . $requires );

				$this->assertSame( ! $locked, Form_Templates::is_template_available( $template ), "{$tier} site, {$requires} template." );
				$this->assertSame( $locked, $item['is_locked'], "{$tier} site, {$requires} template." );
				$this->assertSame( $locked ? 'tier' : '', $item['lock_reason'] );
				$this->assertSame( $requires, $item['requires'] );
				$this->assertSame( 'free' !== $requires, $item['is_pro'] );
			}
		}
	}

	public function test_is_pro_is_read_as_starter() {
		$this->add_templates(
			[
				[
					'id'     => 'legacy-pro',
					'is_pro' => true,
				],
			]
		);
		$template = Form_Templates::get_template( 'legacy-pro' );

		$this->assertSame( 'starter', Form_Templates::get_required_tier( $template ) );
		$this->set_tier( 'free' );
		$this->assertSame( 'tier', Form_Templates::lock_reason( $template ) );
		$this->set_tier( 'starter' );
		$this->assertSame( '', Form_Templates::lock_reason( $template ) );
	}

	public function test_locked_template_is_refused_on_create_and_swap() {
		$this->login_admin();
		$this->add_templates(
			[
				[
					'id'       => 'business-only',
					'requires' => 'business',
				],
			]
		);
		$this->set_tier( 'pro' );

		$result = Form_Templates::create_form_from_template( 'business-only' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'srfm_template_locked', $result->get_error_code() );
		$this->assertSame(
			[
				'status'   => 403,
				'requires' => 'business',
				'reason'   => 'tier',
			],
			$result->get_error_data()
		);

		$swap = $this->swap_request( 'business-only' );
		$this->assertInstanceOf( WP_Error::class, $swap );
		$this->assertSame( 403, $swap->get_error_data()['status'] );

		// On the plan it needs, it builds.
		$this->set_tier( 'business' );
		$this->assertIsInt( Form_Templates::create_form_from_template( 'business-only' ) );
		$this->assertInstanceOf( WP_REST_Response::class, $this->swap_request( 'business-only' ) );
	}

	public function test_locked_template_links_to_pricing_for_that_template() {
		$this->add_templates(
			[
				[
					'id'       => 'pro-only',
					'requires' => 'pro',
				],
			]
		);
		$this->set_tier( 'starter' );

		$item = $this->js_item( 'pro-only' );
		$this->assertStringContainsString( 'utm_content=template-pro-only', $item['unlock_url'] );
		$this->assertStringNotContainsString( '&#038;', $item['unlock_url'] );
		$this->assertSame( '', $this->js_item( 'contact-us' )['unlock_url'] );
	}

	public function test_too_old_pro_locks_with_reason_update() {
		$this->skip_with_pro();
		$this->login_admin();
		$this->add_templates(
			[
				[
					'id'              => 'new-pro',
					'requires'        => 'starter',
					'min_pro_version' => '2.13.0',
				],
				[
					'id'         => 'needs-builder',
					'requires'   => 'starter',
					'conditions' => [ 'name' => [ 'show', [] ] ],
				],
				// A free template's Pro keys only add to it, so they never lock it.
				[
					'id'         => 'free-enhanced',
					'conditions' => [ 'name' => [ 'show', [] ] ],
				],
			]
		);
		$this->set_tier( 'business' );

		foreach ( [ 'new-pro', 'needs-builder' ] as $id ) {
			$item = $this->js_item( $id );
			$this->assertTrue( $item['is_locked'], $id );
			$this->assertSame( 'update', $item['lock_reason'], $id );
			$this->assertStringContainsString( 'plugins.php', $item['unlock_url'] );
		}
		$this->assertSame( '', Form_Templates::lock_reason( Form_Templates::get_template( 'free-enhanced' ) ) );

		$result = Form_Templates::create_form_from_template( 'needs-builder' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'update', $result->get_error_data()['reason'] );
		$this->assertSame( 'starter', $result->get_error_data()['requires'] );

		// A plan that is too low is reported before a version that is too old.
		$this->set_tier( 'free' );
		$this->assertSame( 'tier', $this->js_item( 'new-pro' )['lock_reason'] );
	}

	public function test_availability_filter_can_lock_or_unlock() {
		$this->add_templates(
			[
				[
					'id'       => 'business-only',
					'requires' => 'business',
				],
			]
		);
		$this->set_tier( 'free' );

		add_filter( 'srfm_form_template_is_available', static fn( $available, $template ) => 'contact-us' === $template['id'] ? false : $available, 10, 2 );
		$this->assertSame( 'tier', Form_Templates::lock_reason( Form_Templates::get_template( 'contact-us' ) ) );
		$this->assertInstanceOf( WP_Error::class, Form_Templates::create_form_from_template( 'contact-us' ) );

		remove_all_filters( 'srfm_form_template_is_available' );
		add_filter( 'srfm_form_template_is_available', '__return_true' );
		$this->assertTrue( Form_Templates::is_template_available( Form_Templates::get_template( 'business-only' ) ) );
	}

	public function test_resolve_fields_takes_a_tier() {
		$template = [
			'fields' => [
				[
					'fieldType' => 'input',
					'slug'      => 'name',
					'label'     => 'Name',
				],
				[
					'fieldType' => 'page-break',
					'slug'      => 'step',
					'label'     => 'Step',
					'pro_only'  => true,
				],
				[
					'fieldType' => 'signature',
					'slug'      => 'signature',
					'label'     => 'Signature',
					'pro_tier'  => 'pro',
				],
				[
					'fieldType' => 'input',
					'slug'      => 'when',
					'label'     => 'When',
					'pro_tier'  => 'business',
					'pro'       => [ 'fieldType' => 'date-picker' ],
				],
			],
		];
		$slugs    = static fn( $tier ) => wp_list_pluck( Form_Templates::resolve_fields( $template, $tier ), 'slug' );
		$types    = static fn( $tier ) => wp_list_pluck( Form_Templates::resolve_fields( $template, $tier ), 'fieldType', 'slug' );

		$this->assertSame( [ 'name', 'when' ], $slugs( 'free' ) );
		$this->assertSame( [ 'name', 'step', 'when' ], $slugs( 'starter' ) );
		$this->assertSame( [ 'name', 'step', 'signature', 'when' ], $slugs( 'pro' ) );
		$this->assertSame( 'input', $types( 'pro' )['when'] );
		$this->assertSame( 'date-picker', $types( 'business' )['when'] );

		// Booleans still work: true is every feature, false is the free plugin.
		$this->assertSame( $slugs( 'business' ), $slugs( true ) );
		$this->assertSame( $slugs( 'free' ), $slugs( false ) );
		foreach ( Form_Templates::resolve_fields( $template, true ) as $field ) {
			$this->assertArrayNotHasKey( 'pro_tier', $field );
		}
	}

	public function test_content_filter_gets_the_built_fields() {
		$seen = null;
		add_filter(
			'srfm_form_template_content',
			static function ( $content, $template, $fields ) use ( &$seen ) {
				$seen = [ $template['id'], wp_list_pluck( $fields, 'slug' ) ];
				return $content . '<!-- srfm-test -->';
			},
			10,
			3
		);

		$content = Form_Templates::build_content( Form_Templates::get_template( 'contact-us' ) );

		$this->assertStringEndsWith( '<!-- srfm-test -->', $content );
		$this->assertSame( 'contact-us', $seen[0] );
		$this->assertContains( 'message', $seen[1] );
	}

	/**
	 * Hook srfm_form_template_metas to add a meta and record each call.
	 *
	 * @return array<int, array<string, mixed>> Calls, by reference.
	 */
	private function &record_metas_filter() {
		$calls = [];
		add_filter(
			'srfm_form_template_metas',
			static function ( $metas, $template, $content, $fields, $context ) use ( &$calls ) {
				$calls[]                     = [
					'context' => $context,
					'content' => $content,
					'slugs'   => wp_list_pluck( $fields, 'slug' ),
					'keys'    => array_keys( $metas ),
				];
				$metas['_srfm_test_meta'] = 'from-' . $context;
				return $metas;
			},
			10,
			5
		);
		return $calls;
	}

	public function test_metas_filter_runs_once_on_create_with_the_content() {
		$this->login_admin();
		$calls = &$this->record_metas_filter();

		$post_id = Form_Templates::create_form_from_template( 'contact-us' );

		$this->assertCount( 1, $calls );
		$this->assertSame( 'create', $calls[0]['context'] );
		// The filter runs before the form exists; the saved blocks then get its ID.
		$this->assertStringContainsString( '"formId":0', $calls[0]['content'] );
		$this->assertSame( get_post( $post_id )->post_content, Form_Templates::set_form_id( $calls[0]['content'], $post_id ) );
		$this->assertContains( 'email', $calls[0]['slugs'] );
		$this->assertContains( '_srfm_forms_styling', $calls[0]['keys'] );
		$this->assertSame( 'from-create', get_post_meta( $post_id, '_srfm_test_meta', true ) );
	}

	public function test_metas_filter_runs_on_swap_with_the_content() {
		$this->login_admin();
		$calls = &$this->record_metas_filter();

		$data = $this->swap_request( 'rsvp' )->get_data();

		$this->assertCount( 1, $calls );
		$this->assertSame( 'swap', $calls[0]['context'] );
		$this->assertSame( $data['content'], $calls[0]['content'] );
		$this->assertSame( 'from-swap', $data['settings']['_srfm_test_meta'] );
		// The thank-you message is still part of the swapped settings.
		$this->assertArrayHasKey( '_srfm_form_confirmation', $data['settings'] );
	}

	public function test_metas_filter_builds_the_content_when_none_is_given() {
		$calls = &$this->record_metas_filter();

		Form_Templates::build_settings_metas( Form_Templates::get_template( 'rsvp' ) );

		$this->assertStringContainsString( '<!-- wp:srfm/', $calls[0]['content'] );
		$this->assertSame( 'swap', $calls[0]['context'] );
	}

	public function test_metas_filter_runs_on_preview_and_its_metas_are_seeded() {
		$calls    = &$this->record_metas_filter();
		$template = Form_Templates::get_template( 'contact-us' );

		$build = new ReflectionMethod( Form_Templates::class, 'build' );
		$build->setAccessible( true );
		$built = $build->invoke( null, $template, [], 'preview' );

		$this->assertCount( 1, $calls );
		$this->assertSame( 'preview', $calls[0]['context'] );
		$this->assertSame( $built['content'], $calls[0]['content'] );

		$seed = new ReflectionMethod( Form_Templates::class, 'seed_preview_form' );
		$seed->setAccessible( true );
		$seed->invoke( null, $template, $built['content'], $built['metas'] );

		$id = new ReflectionMethod( Form_Templates::class, 'get_preview_post_id' );
		$id->setAccessible( true );
		$this->assertSame( 'from-preview', get_post_meta( $id->invoke( null ), '_srfm_test_meta', true ) );
	}

	public function test_features_come_from_pro_keys_declared_ids_and_the_filter() {
		$this->add_templates(
			[
				[
					'id'         => 'loan-calculator',
					'conditions' => [ 'name' => [ 'show', [] ] ],
					'features'   => [ 'calculation' ],
				],
			]
		);

		$this->set_tier( 'free' );
		$item = $this->js_item( 'loan-calculator' );
		$this->assertContains( 'calculation', $item['features'] );
		$this->assertNotContains( 'conditional_logic', $item['features'] );
		$this->assertContains( 'conditional_logic', $item['pro_features'] );

		$this->set_tier( 'starter' );
		$this->assertContains( 'conditional_logic', $this->js_item( 'loan-calculator' )['features'] );

		add_filter(
			'srfm_form_template_features',
			static function ( $features, $template, $fields ) {
				if ( 'loan-calculator' === $template['id'] && ! empty( $fields ) ) {
					$features[] = 'quiz';
				}
				return $features;
			},
			10,
			3
		);
		$this->assertContains( 'quiz', $this->js_item( 'loan-calculator' )['features'] );
	}

	public function test_template_list_reports_the_tier() {
		$this->login_admin();
		$this->set_tier( 'starter' );

		$data = ( new Form_Templates() )->rest_list_templates()->get_data();

		$this->assertSame( 'starter', $data['pro_tier'] );
	}
}
