<?php
/**
 * Class Test_Form_Templates_Polls_Agreements
 *
 * The Premium poll, survey, lead and agreement templates: their plans, the Pro
 * features they declare and the fields those declarations name. The rental and
 * scholarship applications are covered by Test_Form_Templates_Accounts.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Templates;

class Test_Form_Templates_Polls_Agreements extends TestCase {

	/**
	 * Template id => [ category, plan ].
	 */
	private const TEMPLATES = [
		'quick-poll'                    => [ 'quizzes', 'pro' ],
		'employee-pulse-survey'         => [ 'quizzes', 'pro' ],
		'product-recommender'           => [ 'quizzes', 'starter' ],
		'conversational-lead-qualifier' => [ 'contact', 'pro' ],
		'ad-landing-lead-form'          => [ 'contact', 'starter' ],
		'service-agreement'             => [ 'agreements', 'business' ],
		'photo-release-consent'         => [ 'agreements', 'business' ],
	];

	public function tear_down() {
		remove_all_filters( 'srfm_pro_tier' );
		$property = new ReflectionProperty( Form_Templates::class, 'templates' );
		$property->setAccessible( true );
		$property->setValue( null, null );
		parent::tear_down();
	}

	private function template( $id ) {
		$template = Form_Templates::get_template( $id );
		$this->assertIsArray( $template, "Template {$id} is missing." );
		return $template;
	}

	private function slugs( $template, $tier = 'business' ) {
		return wp_list_pluck( Form_Templates::resolve_fields( $template, $tier ), 'slug' );
	}

	public function test_templates_have_their_category_and_plan() {
		foreach ( self::TEMPLATES as $id => [ $category, $tier ] ) {
			$template = $this->template( $id );
			$this->assertSame( $category, $template['category'], "Template {$id} has the wrong category." );
			$this->assertSame( $tier, Form_Templates::get_required_tier( $template ), "Template {$id} needs the wrong plan." );
		}
	}

	public function test_declared_feature_keys_need_the_plan_that_builds_them() {
		foreach ( array_keys( self::TEMPLATES ) as $id ) {
			$template = $this->template( $id );
			$keys     = array_intersect_key( $template, Form_Templates::PRO_FEATURE_KEYS );
			$required = Form_Templates::get_required_tier( $template );
			foreach ( array_keys( $keys ) as $key ) {
				// The plan a template asks for builds every feature it declares.
				$this->assertGreaterThanOrEqual(
					array_search( Form_Templates::PRO_FEATURE_KEYS[ $key ], Form_Templates::TIERS, true ),
					array_search( $required, Form_Templates::TIERS, true ),
					"Template {$id} declares {$key} but only needs {$required}."
				);
			}
		}
	}

	public function test_declarations_name_fields_the_template_has() {
		foreach ( array_keys( self::TEMPLATES ) as $id ) {
			$template = $this->template( $id );
			$slugs    = $this->slugs( $template );
			$required = wp_list_pluck( array_filter( Form_Templates::resolve_fields( $template, 'business' ), static fn( $f ) => ! empty( $f['required'] ) ), 'slug' );

			foreach ( $template['conditions'] ?? [] as $target => $condition ) {
				$this->assertContains( $target, $slugs, "Template {$id}: condition target {$target} is not a field." );
				foreach ( $condition['logic'] as $group ) {
					foreach ( $group as $rule ) {
						$this->assertContains( $rule[0], $slugs, "Template {$id}: condition source {$rule[0]} is not a field." );
					}
				}
			}

			foreach ( $template['confirmations'] ?? [] as $confirmation ) {
				foreach ( $confirmation['rules'] as $rule ) {
					$this->assertContains( $rule[0], $slugs, "Template {$id}: confirmation rule names {$rule[0]}." );
					$field = wp_list_filter( Form_Templates::resolve_fields( $template, 'business' ), [ 'slug' => $rule[0] ] );
					$field = reset( $field );
					// A rule on a choice must match one of its options exactly.
					if ( ! empty( $field['fieldOptions'] ) ) {
						$options = array_map( static fn( $o ) => $o['optionTitle'] ?? $o['label'], $field['fieldOptions'] );
						$this->assertContains( $rule[2], $options, "Template {$id}: confirmation value {$rule[2]} is not an option." );
					}
				}
				preg_match_all( '/\{form:([a-z0-9-]+)\}/', $confirmation['message'], $tags );
				foreach ( $tags[1] as $slug ) {
					$this->assertContains( $slug, $required, "Template {$id}: confirmation uses {form:{$slug}}, which is not required." );
				}
			}
		}
	}

	public function test_conversational_template_has_no_page_break_and_a_welcome_screen() {
		foreach ( array_keys( self::TEMPLATES ) as $id ) {
			$template = $this->template( $id );
			if ( empty( $template['conversational'] ) ) {
				continue;
			}
			$types = wp_list_pluck( Form_Templates::resolve_fields( $template, 'business' ), 'fieldType' );
			$this->assertNotContains( 'page-break', $types, "Conversational template {$id} has a page break." );
			$this->assertArrayNotHasKey( 'steps', $template );
			$this->assertNotEmpty( $template['conversational']['welcome_heading'] );
		}
		$features = Form_Templates::get_features( $this->template( 'conversational-lead-qualifier' ), [], 'pro' );
		$this->assertContains( 'conversational', $features );
	}

	public function test_signatures_need_the_pro_plan() {
		$with_signature = [ 'service-agreement', 'photo-release-consent' ];
		foreach ( $with_signature as $id ) {
			$template   = $this->template( $id );
			$signatures = array_filter( $template['fields'], static fn( $f ) => 'signature' === $f['fieldType'] );
			$this->assertNotEmpty( $signatures, "Template {$id} has no signature." );
			foreach ( $signatures as $field ) {
				$this->assertSame( 'pro', $field['pro_tier'] ?? '', "Template {$id}: signature {$field['slug']} must need the Pro plan." );
			}

			$starter = wp_list_pluck( Form_Templates::resolve_fields( $template, 'starter' ), 'fieldType' );
			$pro     = wp_list_pluck( Form_Templates::resolve_fields( $template, 'pro' ), 'fieldType' );
			$this->assertNotContains( 'signature', $starter );
			$this->assertContains( 'signature', $pro );
			$this->assertContains( 'signature', Form_Templates::get_features( $template, Form_Templates::resolve_fields( $template, 'pro' ), 'pro' ) );

			// Signing always comes with a required acknowledgement.
			$gdpr = array_filter( $template['fields'], static fn( $f ) => 'gdpr' === $f['fieldType'] && ! empty( $f['required'] ) );
			$this->assertNotEmpty( $gdpr, "Template {$id} has no required acknowledgement." );
		}
	}

	public function test_agreement_terms_are_marked_as_starter_wording() {
		foreach ( [ 'service-agreement', 'photo-release-consent' ] as $id ) {
			$html = array_values( array_filter( $this->template( $id )['fields'], static fn( $f ) => 'html' === $f['fieldType'] ) );
			$this->assertCount( 1, $html, "Template {$id} has no terms block." );
			$this->assertTrue( $html[0]['pro_only'] );
			$this->assertStringContainsString( 'not legal advice', $html[0]['htmlContent'] );
			// What the mapper keeps is what was written.
			$this->assertSame( wp_kses_post( $html[0]['htmlContent'] ), $html[0]['htmlContent'] );
		}
	}

	public function test_ad_lead_form_records_utm_tags_in_hidden_fields() {
		$template = $this->template( 'ad-landing-lead-form' );
		$hidden   = array_column( array_filter( $template['fields'], static fn( $f ) => 'hidden' === $f['fieldType'] ), 'defaultValue', 'slug' );

		$this->assertSame(
			[
				'utm-source'   => '{get_input:utm_source}',
				'utm-medium'   => '{get_input:utm_medium}',
				'utm-campaign' => '{get_input:utm_campaign}',
				'utm-content'  => '{get_input:utm_content}',
				'utm-term'     => '{get_input:utm_term}',
				'gclid'        => '{get_input:gclid}',
				'fbclid'       => '{get_input:fbclid}',
				'landing-page' => '{current_page_url}',
				'referrer'     => '{http_referer}',
			],
			$hidden
		);
		// Hidden fields are SureForms Pro blocks: only added with a plan.
		$this->assertEmpty( array_intersect( array_keys( $hidden ), $this->slugs( $template, 'free' ) ) );

		add_filter( 'srfm_pro_tier', static fn() => 'starter' );
		$content = Form_Templates::build_content( $template );
		if ( false !== strpos( $content, 'wp:srfm/hidden' ) ) {
			$this->assertStringContainsString( '"defaultValue":"{get_input:utm_source}"', $content );
		}
	}

	public function test_live_results_only_on_the_public_poll() {
		$poll = $this->template( 'quick-poll' );
		$this->assertTrue( $poll['survey']['show_live_results'] );
		// Live results show the latest text answers to every visitor, so the poll asks none.
		$types = wp_list_pluck( Form_Templates::resolve_fields( $poll, 'business' ), 'fieldType' );
		$this->assertEmpty( array_intersect( [ 'textarea', 'input', 'email', 'phone' ], $types ) );
		$this->assertContains( '{poll_results}', array_column( Form_Templates::get_result_tags( $poll, [] ), 'tag' ) );

		$pulse = $this->template( 'employee-pulse-survey' );
		$this->assertTrue( $pulse['survey'] );
		$types = wp_list_pluck( Form_Templates::resolve_fields( $pulse, 'pro' ), 'fieldType' );
		$this->assertContains( 'nps', $types );
		$this->assertContains( 'rating', $types );
		// Anonymous: no name or email asked.
		$this->assertEmpty( array_intersect( [ 'email', 'phone' ], $types ) );
		$this->assertNotContains( 'full-name', $this->slugs( $pulse ) );
	}

	public function test_recommender_falls_back_to_the_default_message() {
		$template = $this->template( 'product-recommender' );
		$this->assertCount( 3, $template['confirmations'] );
		$this->assertNotEmpty( $template['confirmation']['text'] );
		$this->assertContains( 'conditional_logic', Form_Templates::get_features( $template, Form_Templates::resolve_fields( $template, 'starter' ), 'starter' ) );
	}

	public function test_locked_on_free_with_a_premium_preview() {
		add_filter( 'srfm_pro_tier', static fn() => 'free' );
		$list = array_column( Form_Templates::get_templates_for_js(), null, 'id' );
		foreach ( array_keys( self::TEMPLATES ) as $id ) {
			$this->assertTrue( $list[ $id ]['is_locked'], "Template {$id} should be locked on free." );
			$this->assertSame( 'tier', $list[ $id ]['lock_reason'] );
		}
	}
}
