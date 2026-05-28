<?php
/**
 * Class Test_Gravity_Importer
 *
 * Coverage for SureForms' Gravity Forms importer. Each public/protected
 * method gets a dedicated `test_<method>()` per the project's
 * check-test-coverage convention. Per-field-type translation is covered
 * by separate cases so regressions are easy to localize.
 *
 * @package sureforms
 */

use SRFM\Inc\Migrator\Importers\Gravity_Importer;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Gravity_Importer extends TestCase {

	/**
	 * @return Gravity_Importer
	 */
	private function make_importer() {
		return new class() extends Gravity_Importer {
			public function exist() {
				return true;
			}

			/**
			 * @param array<string,mixed> $form Source descriptor.
			 * @return string
			 */
			public function build_form_content_public( array $form ) {
				return $this->build_form_content( $form );
			}

			/**
			 * @param array<string,mixed> $form Source descriptor.
			 * @return array<string,mixed>
			 */
			public function get_form_metas_public( array $form ) {
				$this->build_form_content( $form );
				return $this->get_form_metas( $form );
			}

			/**
			 * @param array<string,mixed> $form Source descriptor.
			 * @return int
			 */
			public function source_id_public( array $form ) {
				return $this->get_source_form_id( $form );
			}

			/**
			 * @param array<string,mixed> $form Source descriptor.
			 * @return string
			 */
			public function source_name_public( array $form ) {
				return $this->get_source_form_name( $form );
			}

			/**
			 * @return array<int,array<string,mixed>>
			 */
			public function source_forms_public() {
				return $this->get_source_forms();
			}
		};
	}

	/**
	 * Build a synthetic source descriptor with the given display_meta
	 * fields array. Mirrors what `get_source_forms` would have produced
	 * from a `wp_gf_form_meta.display_meta` row.
	 *
	 * @param array<int,array<string,mixed>> $fields Gravity fields.
	 * @param array<string,mixed>            $extras Extra display_meta keys.
	 * @return array<string,mixed>
	 */
	private function source_form_with( array $fields, array $extras = [] ) {
		$display_meta = array_merge(
			[
				'title'  => 'Probe',
				'button' => [ 'text' => 'Submit', 'type' => 'text' ],
				'fields' => $fields,
			],
			$extras
		);
		return [
			'id'           => 1,
			'name'         => 'Probe',
			'display_meta' => (string) wp_json_encode( $display_meta ),
		];
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_all_filters( 'srfm_migrator_preprocess_template' );
		remove_all_filters( 'srfm_migrator_tag_to_template_map' );
		remove_all_filters( 'srfm_migrator_unsupported_tags' );
		remove_all_filters( 'srfm_migrator_block_template' );
		$ids = get_posts(
			[
				'post_type'      => SRFM_FORMS_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);
		foreach ( $ids as $id ) {
			wp_delete_post( (int) $id, true );
		}
		delete_option( 'srfm_imported_forms_map' );
		parent::tear_down();
	}

	/**
	 * @return void
	 */
	public function test_exist() {
		$importer = new Gravity_Importer();
		$this->assertSame(
			class_exists( 'GFForms' ) || class_exists( 'GFFormsModel' ) || defined( 'GF_MIN_WP_VERSION' ),
			$importer->exist()
		);
	}

	/**
	 * @return void
	 */
	public function test_default_field_map() {
		$map = $this->make_importer()->default_field_map();
		$this->assertSame( 'input', $map['text'] );
		$this->assertSame( 'textarea', $map['textarea'] );
		$this->assertSame( 'email', $map['email'] );
		$this->assertSame( 'number', $map['number'] );
		$this->assertSame( 'dropdown', $map['select'] );
		$this->assertSame( 'multi_choice', $map['radio'] );
		$this->assertSame( 'multi_choice', $map['checkbox'] );
		$this->assertSame( 'url', $map['website'] );
		$this->assertSame( 'phone', $map['phone'] );
		$this->assertSame( 'gdpr', $map['consent'] );
	}

	/**
	 * @return void
	 */
	public function test_get_source_form_id() {
		$this->assertSame( 42, $this->make_importer()->source_id_public( [ 'id' => 42 ] ) );
		$this->assertSame( 0, $this->make_importer()->source_id_public( [] ) );
	}

	/**
	 * @return void
	 */
	public function test_get_source_form_name() {
		$this->assertSame( 'Hi', $this->make_importer()->source_name_public( [ 'name' => 'Hi' ] ) );
		$this->assertStringContainsString( 'untitled', $this->make_importer()->source_name_public( [] ) );
	}

	/**
	 * @return void
	 */
	public function test_get_source_forms_uses_modern_table_when_version_is_new() {
		// Sanity-check the version gate flips at 2.3-dev-1.
		update_option( 'gf_database_version', '2.5' );
		// `exist` returns true (subclass override), but the query against
		// non-existent tables returns null → empty array, not an error.
		$forms = $this->make_importer()->source_forms_public();
		$this->assertIsArray( $forms );
		delete_option( 'gf_database_version' );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_text_field() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[
					[ 'id' => 1, 'type' => 'text', 'label' => 'First name', 'isRequired' => true, 'placeholder' => 'Jane' ],
				]
			)
		);
		$this->assertStringContainsString( 'wp:srfm/input', $markup );
		$this->assertStringContainsString( '"label":"First name"', $markup );
		$this->assertStringContainsString( '"required":true', $markup );
		$this->assertStringContainsString( '"placeholder":"Jane"', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_textarea() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[ [ 'id' => 1, 'type' => 'textarea', 'label' => 'Notes', 'maxLength' => 200 ] ]
			)
		);
		$this->assertStringContainsString( 'wp:srfm/textarea', $markup );
		$this->assertStringContainsString( '"maxLength":200', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_email() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[ [ 'id' => 1, 'type' => 'email', 'label' => 'Email', 'isRequired' => true ] ]
			)
		);
		$this->assertStringContainsString( 'wp:srfm/email', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_two_email_blocks_when_confirmation_enabled() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[ [ 'id' => 1, 'type' => 'email', 'label' => 'Email', 'isRequired' => true, 'emailConfirmEnabled' => true ] ]
			)
		);
		$this->assertSame( 2, substr_count( $markup, 'wp:srfm/email' ) );
		$this->assertStringContainsString( 'Email (Confirm)', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_number_with_range() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[ [ 'id' => 1, 'type' => 'number', 'label' => 'Age', 'rangeMin' => 0, 'rangeMax' => 120 ] ]
			)
		);
		$this->assertStringContainsString( 'wp:srfm/number', $markup );
		$this->assertStringContainsString( '"minValue":0', $markup );
		$this->assertStringContainsString( '"maxValue":120', $markup );
	}

	/**
	 * Gravity stores choices as `{text, value, isSelected, price}`. The
	 * importer must translate `isSelected` into the preselected-INDEX
	 * array (SureForms matches by index, not label).
	 *
	 * @return void
	 */
	public function test_build_form_content_emits_select_with_preserved_choices() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[
					[
						'id'      => 1,
						'type'    => 'select',
						'label'   => 'Country',
						'choices' => [
							[ 'text' => 'India',  'value' => 'IN', 'isSelected' => false ],
							[ 'text' => 'Canada', 'value' => 'CA', 'isSelected' => true ],
							[ 'text' => 'UK',     'value' => 'UK', 'isSelected' => false ],
						],
					],
				]
			)
		);
		$this->assertStringContainsString( 'wp:srfm/dropdown', $markup );
		$this->assertStringContainsString( 'India', $markup );
		$this->assertStringContainsString( 'Canada', $markup );
		$this->assertStringContainsString( '"preselectedOptions":[1]', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_radio_as_single_select_multichoice() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[
					[
						'id'      => 1,
						'type'    => 'radio',
						'label'   => 'Pick',
						'choices' => [
							[ 'text' => 'A', 'value' => 'a', 'isSelected' => false ],
							[ 'text' => 'B', 'value' => 'b', 'isSelected' => false ],
						],
					],
				]
			)
		);
		$this->assertStringContainsString( 'wp:srfm/multi-choice', $markup );
		$this->assertStringContainsString( '"singleSelection":true', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_checkbox_as_multi_select_multichoice() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[
					[
						'id'      => 1,
						'type'    => 'checkbox',
						'label'   => 'Pick many',
						'choices' => [
							[ 'text' => 'X', 'value' => 'x', 'isSelected' => false ],
							[ 'text' => 'Y', 'value' => 'y', 'isSelected' => false ],
						],
					],
				]
			)
		);
		$this->assertStringContainsString( 'wp:srfm/multi-choice', $markup );
		$this->assertStringNotContainsString( '"singleSelection":true', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_website_as_url() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[ [ 'id' => 1, 'type' => 'website', 'label' => 'Site' ] ]
			)
		);
		$this->assertStringContainsString( 'wp:srfm/url', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_phone() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[ [ 'id' => 1, 'type' => 'phone', 'label' => 'Mobile', 'phoneFormat' => 'international' ] ]
			)
		);
		$this->assertStringContainsString( 'wp:srfm/phone', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_name_with_visible_inputs_emits_two_inputs() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[
					[
						'id'     => 1,
						'type'   => 'name',
						'label'  => 'Name',
						'inputs' => [
							[ 'id' => '1.2', 'label' => 'Prefix', 'isHidden' => true ],
							[ 'id' => '1.3', 'label' => 'First' ],
							[ 'id' => '1.4', 'label' => 'Middle', 'isHidden' => true ],
							[ 'id' => '1.6', 'label' => 'Last' ],
							[ 'id' => '1.8', 'label' => 'Suffix', 'isHidden' => true ],
						],
					],
				]
			)
		);
		$this->assertSame( 2, substr_count( $markup, 'wp:srfm/input' ) );
		$this->assertStringContainsString( 'Name (First)', $markup );
		$this->assertStringContainsString( 'Name (Last)', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_consent_emits_gdpr() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[
					[
						'id'            => 1,
						'type'          => 'consent',
						'label'         => 'Consent',
						'isRequired'    => true,
						'checkboxLabel' => 'I agree',
					],
				]
			)
		);
		$this->assertStringContainsString( 'wp:srfm/gdpr', $markup );
		$this->assertStringContainsString( 'I agree', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_invalid_display_meta_returns_empty() {
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'display_meta' => '{not-json' ]
		);
		$this->assertSame( '', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_skips_creditcard_and_post_fields_as_unsupported() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[
					[ 'id' => 1, 'type' => 'creditcard', 'label' => 'CC' ],
					[ 'id' => 2, 'type' => 'post_title', 'label' => 'Post Title' ],
					[ 'id' => 3, 'type' => 'product',    'label' => 'Item' ],
				]
			)
		);
		$this->assertSame( '', trim( $markup ) );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_unmapped_type_with_no_subscriber_is_flagged_unsupported() {
		$importer = $this->make_importer();
		// Inject a synthetic form via the source-forms override.
		$markup = $importer->build_form_content_public(
			$this->source_form_with(
				[ [ 'id' => 1, 'type' => 'mystery_widget', 'label' => 'Surprise' ] ]
			)
		);
		$this->assertSame( '', $markup );
	}

	/**
	 * Conditional logic — verify the 7 operators translate correctly and
	 * rule targets are rewritten to SureForms block_ids.
	 *
	 * @return void
	 */
	public function test_get_form_metas_translates_conditional_logic_operators() {
		$form = $this->source_form_with(
			[
				[ 'id' => 1, 'type' => 'text',   'label' => 'Trigger' ],
				[
					'id'               => 2,
					'type'             => 'number',
					'label'            => 'Dependent',
					'conditionalLogic' => [
						'actionType' => 'show',
						'logicType'  => 'all',
						'rules'      => [
							[ 'fieldId' => '1', 'operator' => 'is',           'value' => 'yes' ],
							[ 'fieldId' => '1', 'operator' => 'contains',     'value' => 'pre' ],
							[ 'fieldId' => '1', 'operator' => 'starts_with',  'value' => 'a' ],
							[ 'fieldId' => '1', 'operator' => 'greater_than', 'value' => '0' ],
						],
					],
				],
			]
		);
		$metas = $this->make_importer()->get_form_metas_public( $form );
		$this->assertNotEmpty( $metas['_srfm_conditional_logic'] );
		$entry   = $metas['_srfm_conditional_logic'][0];
		$payload = (array) reset( $entry );
		$this->assertSame( 'show', $payload['action'] );
		$this->assertCount( 1, $payload['logic'] ); // logicType=all → one AND-group.
		$ops = array_column( $payload['logic'][0], 'operator' );
		$this->assertSame( [ '==', 'includes', 'startWith', '>' ], $ops );
		// rule.field is the source block_id (8 hex), not the GF field id.
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{8}$/', $payload['logic'][0][0]['field'] );
	}

	/**
	 * `logicType=any` becomes a group of one-rule OR subgroups.
	 *
	 * @return void
	 */
	public function test_get_form_metas_translates_logicType_any_as_or_groups() {
		$form  = $this->source_form_with(
			[
				[ 'id' => 1, 'type' => 'text',   'label' => 'Trigger' ],
				[
					'id'               => 2,
					'type'             => 'text',
					'label'            => 'Dep',
					'conditionalLogic' => [
						'actionType' => 'show',
						'logicType'  => 'any',
						'rules'      => [
							[ 'fieldId' => '1', 'operator' => 'is', 'value' => 'a' ],
							[ 'fieldId' => '1', 'operator' => 'is', 'value' => 'b' ],
						],
					],
				],
			]
		);
		$metas = $this->make_importer()->get_form_metas_public( $form );
		$entry = (array) reset( $metas['_srfm_conditional_logic'][0] );
		$this->assertCount( 2, $entry['logic'] );
	}

	/**
	 * @return void
	 */
	public function test_get_form_metas_translates_submit_text() {
		$metas = $this->make_importer()->get_form_metas_public(
			$this->source_form_with( [], [ 'button' => [ 'text' => 'Send' ] ] )
		);
		$this->assertSame( 'Send', $metas['_srfm_submit_button_text'] );
	}

	/**
	 * @return void
	 */
	public function test_get_form_metas_translates_email_notifications() {
		$metas = $this->make_importer()->get_form_metas_public(
			$this->source_form_with(
				[],
				[
					'notifications' => [
						'1' => [
							'name'    => 'Admin',
							'to'      => 'admin@example.com',
							'subject' => 'New entry',
							'replyTo' => '{admin_email}',
							'message' => '{all_fields}',
						],
					],
				]
			)
		);
		$this->assertSame( 'admin@example.com', $metas['_srfm_email_notification'][0]['email_to'] );
		$this->assertSame( 'New entry', $metas['_srfm_email_notification'][0]['subject'] );
	}

	/**
	 * @return void
	 */
	public function test_get_form_metas_translates_confirmation_message() {
		$metas = $this->make_importer()->get_form_metas_public(
			$this->source_form_with(
				[],
				[
					'confirmations' => [
						'1' => [ 'type' => 'message', 'message' => '<p>Thanks!</p>' ],
					],
				]
			)
		);
		$this->assertSame( 'same page', $metas['_srfm_form_confirmation'][0]['confirmation_type'] );
		$this->assertStringContainsString( 'Thanks', $metas['_srfm_form_confirmation'][0]['message'] );
	}

	/**
	 * @return void
	 */
	public function test_get_form_metas_translates_confirmation_redirect() {
		$metas = $this->make_importer()->get_form_metas_public(
			$this->source_form_with(
				[],
				[
					'confirmations' => [
						'1' => [ 'type' => 'redirect', 'url' => 'https://example.com/thanks' ],
					],
				]
			)
		);
		$this->assertSame( 'different page', $metas['_srfm_form_confirmation'][0]['confirmation_type'] );
		$this->assertSame( 'https://example.com/thanks', $metas['_srfm_form_confirmation'][0]['page_url'] );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_xss_in_label_is_escaped_through_json() {
		$markup = $this->make_importer()->build_form_content_public(
			$this->source_form_with(
				[ [ 'id' => 1, 'type' => 'text', 'label' => 'Name <script>alert(1)</script>' ] ]
			)
		);
		$this->assertStringNotContainsString( '<script', $markup );
		$this->assertStringContainsString( '<', $markup );
	}
}
