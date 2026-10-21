<?php
/**
 * Class Test_Form_Templates_Enhancements
 *
 * The Pro additions to the free templates: the free form stays as it was,
 * and every Pro declaration points at a field the paid build has.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Templates;
use SRFM\Inc\Helper;

class Test_Form_Templates_Enhancements extends TestCase {

	/**
	 * Fields of the enhanced templates without Pro, as `slug:type`, in order.
	 * These are the forms as they were before the Pro additions.
	 *
	 * @var array<string, string>
	 */
	private const FREE_FIELDS = [
		'contact-us'           => 'name:input email:email phone:phone subject:input message:textarea consent:gdpr',
		'quote-request'        => 'name:input email:email phone:phone company:input service:dropdown budget:dropdown timeline:dropdown project-details:textarea consent:gdpr',
		'consultation-request' => 'first-name:input last-name:input email:email phone:phone company:input website:url goals:textarea meeting-type:multi-choice how-heard:dropdown consent:gdpr',
		'product-inquiry'      => 'name:input email:email phone:phone topic:multi-choice product:input quantity:number question:textarea consent:gdpr',
		'partnership-inquiry'  => 'full-name:input email:email company:input job-title:input website:url partnership-type:dropdown proposal:textarea consent:gdpr',
		'appointment-request'  => 'name:input email:email phone:phone service:dropdown preferred-date:input preferred-time:multi-choice meeting-type:multi-choice notes:textarea consent:gdpr',
		'event-registration'   => 'name:input email:email phone:phone attendees:number organization:input questions:textarea event-updates:checkbox consent:gdpr',
		'rsvp'                 => 'first-name:input last-name:input email:email attending:multi-choice guests:number dietary:input message:textarea consent:gdpr',
		'customer-feedback'    => 'satisfaction:multi-choice liked:textarea improve:textarea name:input email:email consent:gdpr',
		'satisfaction-survey'  => 'recommend-score:dropdown reason:textarea frequency:multi-choice email:email',
		'website-feedback'     => 'found-it:multi-choice ease:multi-choice improve:textarea page:input email:email',
		'complaint'            => 'full-name:input email:email phone:phone reference:input complaint-type:dropdown incident-date:input details:textarea evidence:url resolution:textarea consent:gdpr',
		'support-request'      => 'full-name:input email:email topic:dropdown order-number:input subject:input priority:multi-choice description:textarea link:url consent:gdpr',
		'bug-report'           => 'full-name:input email:email summary:input severity:multi-choice what-happened:textarea steps:textarea page-url:url browser:dropdown device:dropdown screenshot:url consent:gdpr',
		'volunteer-signup'     => 'full-name:input email:email phone:phone availability:multi-choice interests:multi-choice skills:textarea consent:gdpr',
		'job-application'      => 'full-name:input email:email phone:phone position:input cv:url linkedin:url portfolio:url years-experience:number start-date:dropdown cover-letter:textarea consent:gdpr',
	];

	public function tear_down() {
		remove_all_filters( 'srfm_pro_tier' );
		parent::tear_down();
	}

	/**
	 * A template's fields on a plan, keyed by slug.
	 *
	 * @param string $id   Template id.
	 * @param string $tier Plan.
	 * @return array<string, array<string, mixed>>
	 */
	private function fields( $id, $tier ) {
		$fields = Form_Templates::resolve_fields( Form_Templates::get_template( $id ), $tier );
		return array_combine( wp_list_pluck( $fields, 'slug' ), $fields );
	}

	/**
	 * `slug:type` list of a template on a plan.
	 *
	 * @param string $id   Template id.
	 * @param string $tier Plan.
	 * @return string
	 */
	private function signature( $id, $tier ) {
		return implode( ' ', array_map( static fn( $field ) => $field['slug'] . ':' . $field['fieldType'], Form_Templates::resolve_fields( Form_Templates::get_template( $id ), $tier ) ) );
	}

	public function test_free_forms_are_unchanged_and_stay_free() {
		foreach ( self::FREE_FIELDS as $id => $expected ) {
			$template = Form_Templates::get_template( $id );
			$this->assertSame( $expected, $this->signature( $id, 'free' ), "Template {$id} changed without Pro." );
			$this->assertSame( 'free', Form_Templates::get_required_tier( $template ), "Template {$id} must stay free." );
			$this->assertSame( '', Form_Templates::lock_reason( $template ), "Template {$id} must never be locked." );
		}
	}

	public function test_lead_forms_record_the_page_with_starter() {
		foreach ( [ 'contact-us', 'quote-request', 'consultation-request', 'product-inquiry' ] as $id ) {
			$this->assertArrayNotHasKey( 'source-page', $this->fields( $id, 'free' ) );
			$field = $this->fields( $id, 'starter' )['source-page'] ?? [];
			$this->assertSame( 'hidden', $field['fieldType'] ?? '', "Template {$id} has no source page with Starter." );
			$this->assertSame( '{current_page_url}', $field['defaultValue'] );
		}

		$event = $this->fields( 'event-registration', 'starter' );
		foreach ( [ 'source', 'medium', 'campaign' ] as $param ) {
			$this->assertSame( 'hidden', $event[ 'utm-' . $param ]['fieldType'] );
			$this->assertSame( '{get_input:utm_' . $param . '}', $event[ 'utm-' . $param ]['defaultValue'] );
		}
	}

	public function test_time_pickers_with_starter() {
		$appointment = $this->fields( 'appointment-request', 'starter' );
		$this->assertSame( 'time-picker', $appointment['preferred-time']['fieldType'] );
		$this->assertTrue( $appointment['preferred-time']['required'] );
		$this->assertSame( '09:00', $appointment['preferred-time']['minTime'] );
		$this->assertSame( '18:00', $appointment['preferred-time']['maxTime'] );
		// Date and time share a row with Pro, under a full-width service. Without
		// it the date sits beside the service.
		$this->assertEquals( 100, $appointment['service']['blockAttributes']['fieldWidth'] );
		$this->assertEquals( 50, $appointment['preferred-date']['blockAttributes']['fieldWidth'] );
		$this->assertEquals( 50, $appointment['preferred-time']['blockAttributes']['fieldWidth'] );
		$this->assertTrue( $appointment['preferred-date']['blockAttributes']['disablePastDates'] );
		$free = $this->fields( 'appointment-request', 'free' );
		$this->assertEquals( 50, $free['service']['blockAttributes']['fieldWidth'] );
		$this->assertEquals( 50, $free['preferred-date']['blockAttributes']['fieldWidth'] );

		$consultation = $this->fields( 'consultation-request', 'starter' );
		$this->assertSame( 'time-picker', $consultation['preferred-time']['fieldType'] );
		$this->assertSame( 'date-picker', $consultation['preferred-date']['fieldType'] );
		$this->assertSame( [ 'preferred-date', 'preferred-time' ], array_slice( array_keys( $consultation ), array_search( 'preferred-date', array_keys( $consultation ), true ), 2 ) );
	}

	public function test_signatures_need_the_pro_plan() {
		$cases = [
			'complaint'        => [ 'declaration-signature' ],
			'volunteer-signup' => [ 'waiver-agreement', 'waiver-signature' ],
		];
		foreach ( $cases as $id => $slugs ) {
			foreach ( $slugs as $slug ) {
				$this->assertArrayNotHasKey( $slug, $this->fields( $id, 'starter' ), "{$id}: {$slug} needs the Pro plan." );
				$this->assertTrue( $this->fields( $id, 'pro' )[ $slug ]['required'], "{$id}: {$slug} should be required on Pro." );
			}
		}
		$this->assertSame( 'signature', $this->fields( 'volunteer-signup', 'pro' )['waiver-signature']['fieldType'] );
		$this->assertSame( 'checkbox', $this->fields( 'volunteer-signup', 'pro' )['waiver-agreement']['fieldType'] );

		// The declaration sits on the complaint step, right before consent.
		$complaint = array_keys( $this->fields( 'complaint', 'pro' ) );
		$this->assertSame( 'consent', $complaint[ array_search( 'declaration-signature', $complaint, true ) + 1 ] );
	}

	public function test_declarations_point_at_fields_of_the_plan_that_builds_them() {
		$expected = [
			'quote-request'       => [ 'attach_uploads' ],
			'product-inquiry'     => [ 'confirmations' ],
			'partnership-inquiry' => [ 'attach_uploads' ],
			'rsvp'                => [ 'conditions' ],
			'customer-feedback'   => [ 'confirmations' ],
			'satisfaction-survey' => [ 'confirmations', 'survey' ],
			'website-feedback'    => [ 'conditions' ],
			'complaint'           => [ 'attach_uploads' ],
			'support-request'     => [ 'attach_uploads' ],
			'bug-report'          => [ 'attach_uploads' ],
			'job-application'     => [ 'attach_uploads', 'save_resume' ],
		];

		// The 20 original templates; the Premium batches test their own declarations.
		foreach ( \SRFM\Inc\Form_Templates\Catalog::ORDER as $id ) {
			$template = Form_Templates::get_template( $id );
			$declared = array_keys( array_intersect_key( $template, Form_Templates::PRO_FEATURE_KEYS ) );
			sort( $declared );
			$this->assertSame( $expected[ $id ] ?? [], $declared, "Template {$id} declares other Pro features." );

			foreach ( $declared as $key ) {
				$this->assertNotSame( 'business', Form_Templates::PRO_FEATURE_KEYS[ $key ], "Template {$id} uses the Business key {$key}." );
				$this->assertNotContains( $key, [ 'conversational', 'entry_limit', 'restrictions' ], "Template {$id} must not use {$key}." );
			}

			$fields = $this->fields( $id, 'starter' );
			foreach ( Helper::get_array_value( $template['attach_uploads'] ?? [] ) as $slug ) {
				$this->assertSame( 'upload', $fields[ $slug ]['fieldType'] ?? '', "{$id}: attach_uploads names {$slug}, which is not an upload with Starter." );
			}

			foreach ( Helper::get_array_value( $template['conditions'] ?? [] ) as $target => $rule ) {
				$this->assertArrayHasKey( $target, $fields, "{$id}: condition target {$target} is missing with Starter." );
				// The form reads as a whole without Pro, so a hidden field never blocks submitting.
				$this->assertEmpty( $fields[ $target ]['required'], "{$id}: conditional field {$target} must be optional." );
				foreach ( $rule['logic'] as $group ) {
					foreach ( $group as $condition ) {
						$source = $fields[ $condition[0] ] ?? [];
						$this->assertNotEmpty( $source, "{$id}: condition source {$condition[0]} is missing." );
						if ( in_array( $condition[1], [ '==', '!=', 'in', '!in' ], true ) && 'multi-choice' === $source['fieldType'] ) {
							$options = wp_list_pluck( $source['fieldOptions'], 'optionTitle' );
							foreach ( (array) $condition[2] as $value ) {
								$this->assertContains( $value, $options, "{$id}: condition on {$condition[0]} uses an unknown option." );
							}
						}
					}
				}
			}

			foreach ( Helper::get_array_value( $template['confirmations'] ?? [] ) as $confirmation ) {
				foreach ( $confirmation['rules'] as $rule ) {
					$this->assertArrayHasKey( $rule[0], $fields, "{$id}: confirmation rule names a missing field." );
				}
				$required = array_keys( array_filter( $fields, static fn( $field ) => ! empty( $field['required'] ) ) );
				preg_match_all( '/\{form:([a-z0-9-]+)\}/', $confirmation['message'], $tags );
				foreach ( $tags[1] as $slug ) {
					$this->assertContains( $slug, $required, "{$id}: confirmation uses {form:{$slug}}, which is not a required field." );
				}
			}
		}
	}

	public function test_score_based_confirmations_read_the_pro_score_fields() {
		$feedback = Form_Templates::get_template( 'customer-feedback' );
		$this->assertSame( 'rating', $this->fields( 'customer-feedback', 'starter' )['satisfaction']['fieldType'] );
		$this->assertSame( [ 'satisfaction', '_GREATER_OR_EQUAL_', '4' ], $feedback['confirmations'][0]['rules'][0] );
		$this->assertSame( [ 'satisfaction', '_LESSER_OR_EQUAL_', '2' ], $feedback['confirmations'][1]['rules'][0] );

		$survey = Form_Templates::get_template( 'satisfaction-survey' );
		$this->assertSame( 'nps', $this->fields( 'satisfaction-survey', 'starter' )['recommend-score']['fieldType'] );
		$this->assertSame( [ 'recommend-score', '_GREATER_OR_EQUAL_', '9' ], $survey['confirmations'][0]['rules'][0] );
		$this->assertSame( [ 'recommend-score', '_LESSER_OR_EQUAL_', '6' ], $survey['confirmations'][1]['rules'][0] );
		// Live results would replace the thank-you message.
		$this->assertSame( [ 'show_live_results' => false ], $survey['survey'] );
	}

	public function test_bulk_order_rule_matches_formatted_quantities_of_fifty_and_more() {
		$rule    = Form_Templates::get_template( 'product-inquiry' )['confirmations'][0]['rules'][0];
		$pattern = '/' . $rule[2] . '/';

		$this->assertSame( [ 'quantity', '_REGEX_' ], array_slice( $rule, 0, 2 ) );
		foreach ( [ '50', '99', '120', '999', '1,000', '1.000', '10,000', '2500' ] as $quantity ) {
			$this->assertSame( 1, preg_match( $pattern, $quantity ), "{$quantity} is a bulk order." );
		}
		foreach ( [ '', '1', '9', '49', '0', '5' ] as $quantity ) {
			$this->assertSame( 0, preg_match( $pattern, $quantity ), "{$quantity} is not a bulk order." );
		}
	}

	public function test_free_sites_are_told_what_pro_adds() {
		add_filter( 'srfm_pro_tier', static fn() => 'free' );
		$items = [];
		foreach ( Form_Templates::get_templates_for_js() as $item ) {
			$items[ $item['id'] ] = $item;
		}

		$this->assertContains( 'time_picker', $items['appointment-request']['pro_features'] );
		$this->assertContains( 'time_picker', $items['consultation-request']['pro_features'] );
		$this->assertContains( 'conditional_logic', $items['rsvp']['pro_features'] );
		$this->assertContains( 'conditional_logic', $items['website-feedback']['pro_features'] );
		$this->assertContains( 'survey', $items['satisfaction-survey']['pro_features'] );
		$this->assertContains( 'save_resume', $items['job-application']['pro_features'] );
		$this->assertContains( 'signature', $items['complaint']['pro_features'] );
		$this->assertContains( 'signature', $items['volunteer-signup']['pro_features'] );

		foreach ( [ 'rsvp', 'satisfaction-survey', 'job-application', 'complaint' ] as $id ) {
			$this->assertFalse( $items[ $id ]['is_locked'] );
			$this->assertNotContains( 'conditional_logic', $items[ $id ]['features'] );
			$this->assertNotContains( 'survey', $items[ $id ]['features'] );
			$this->assertNotContains( 'save_resume', $items[ $id ]['features'] );
		}
	}

	public function test_signature_is_not_offered_to_starter() {
		add_filter( 'srfm_pro_tier', static fn() => 'starter' );
		$item = wp_list_filter( Form_Templates::get_templates_for_js(), [ 'id' => 'volunteer-signup' ] );
		$item = reset( $item );

		$this->assertNotContains( 'signature', $item['features'] );
		$this->assertContains( 'signature', $item['pro_features'] );
	}
}
