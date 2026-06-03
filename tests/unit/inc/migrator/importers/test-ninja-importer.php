<?php
/**
 * Class Test_Ninja_Importer
 *
 * Coverage for SureForms' Ninja Forms importer. Each public/protected
 * method gets a dedicated `test_<method>()`. Ninja's schema is multi-
 * table (nf3_forms + nf3_fields + nf3_field_meta + nf3_form_meta +
 * nf3_objects + nf3_object_meta) — the tests use an anonymous subclass
 * that stubs the DB-reader methods (`fetch_fields`, `fetch_form_meta`,
 * `fetch_actions`) with canned arrays so unit tests don't need the
 * full table set installed.
 *
 * @package sureforms
 */

use SRFM\Inc\Migrator\Importers\Ninja_Importer;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Ninja_Importer extends TestCase {

	/**
	 * Build a test importer with stubbed DB readers. `$fields` is the
	 * canned response from `fetch_fields()`; `$form_meta` from
	 * `fetch_form_meta()`; `$actions` from `fetch_actions()`.
	 *
	 * @param array<int,array<string,mixed>> $fields    Fields to return.
	 * @param array<string,mixed>            $form_meta Form-meta to return.
	 * @param array<int,array<string,mixed>> $actions   Actions to return.
	 * @return Ninja_Importer
	 */
	private function make_importer( array $fields = [], array $form_meta = [], array $actions = [] ) {
		return new class( $fields, $form_meta, $actions ) extends Ninja_Importer {
			/** @var array<int,array<string,mixed>> */
			private $stub_fields;
			/** @var array<string,mixed> */
			private $stub_form_meta;
			/** @var array<int,array<string,mixed>> */
			private $stub_actions;

			/**
			 * @param array<int,array<string,mixed>> $fields    Canned fields.
			 * @param array<string,mixed>            $form_meta Canned form meta.
			 * @param array<int,array<string,mixed>> $actions   Canned actions.
			 */
			public function __construct( array $fields, array $form_meta, array $actions ) {
				parent::__construct();
				$this->stub_fields    = $fields;
				$this->stub_form_meta = $form_meta;
				$this->stub_actions   = $actions;
			}

			public function exist() {
				return true;
			}

			/**
			 * @param int $form_id Ignored.
			 * @return array<int,array<string,mixed>>
			 */
			protected function fetch_fields( $form_id ) {
				unset( $form_id );
				return $this->stub_fields;
			}

			/**
			 * @param int $form_id Ignored.
			 * @return array<string,mixed>
			 */
			protected function fetch_form_meta( $form_id ) {
				unset( $form_id );
				return $this->stub_form_meta;
			}

			/**
			 * @param int $form_id Ignored.
			 * @return array<int,array<string,mixed>>
			 */
			protected function fetch_actions( $form_id ) {
				unset( $form_id );
				return $this->stub_actions;
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

			// Wrappers calling the REAL (parent) DB readers — bypass the stub
			// overrides above so the actual implementations can be exercised.

			/**
			 * @param int $form_id Form id.
			 * @return array<int,array<string,mixed>>
			 */
			public function fetch_fields_real( $form_id ) {
				return parent::fetch_fields( $form_id );
			}

			/**
			 * @param int $form_id Form id.
			 * @return array<string,mixed>
			 */
			public function fetch_form_meta_real( $form_id ) {
				return parent::fetch_form_meta( $form_id );
			}

			/**
			 * @param int $form_id Form id.
			 * @return array<int,array<string,mixed>>
			 */
			public function fetch_actions_real( $form_id ) {
				return parent::fetch_actions( $form_id );
			}
		};
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
		$importer = new Ninja_Importer();
		$this->assertSame(
			class_exists( 'Ninja_Forms' ) || defined( 'NF_PLUGIN_VERSION' ),
			$importer->exist()
		);
	}

	/**
	 * @return void
	 */
	public function test_default_field_map() {
		$map = $this->make_importer()->default_field_map();
		$this->assertSame( 'input', $map['textbox'] );
		$this->assertSame( 'input', $map['firstname'] );
		$this->assertSame( 'input', $map['lastname'] );
		$this->assertSame( 'email', $map['email'] );
		$this->assertSame( 'textarea', $map['textarea'] );
		$this->assertSame( 'number', $map['number'] );
		$this->assertSame( 'phone', $map['phone'] );
		$this->assertSame( 'dropdown', $map['listselect'] );
		$this->assertSame( 'dropdown', $map['listmultiselect'] );
		$this->assertSame( 'multi_choice', $map['listradio'] );
		$this->assertSame( 'multi_choice', $map['listcheckbox'] );
		$this->assertSame( 'checkbox', $map['checkbox'] );
		$this->assertSame( 'gdpr', $map['terms'] );
	}

	/**
	 * @return void
	 */
	public function test_get_source_form_id() {
		$this->assertSame( 12, $this->make_importer()->source_id_public( [ 'id' => 12 ] ) );
		$this->assertSame( 0, $this->make_importer()->source_id_public( [] ) );
	}

	/**
	 * @return void
	 */
	public function test_get_source_form_name() {
		$this->assertSame( 'X', $this->make_importer()->source_name_public( [ 'name' => 'X' ] ) );
		$this->assertStringContainsString( 'untitled', $this->make_importer()->source_name_public( [] ) );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_textbox() {
		$markup = $this->make_importer(
			[
				[ 'id' => 1, 'type' => 'textbox', 'label' => 'Name', 'key' => 'name', 'required' => true, 'placeholder' => 'Jane' ],
			]
		)->build_form_content_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertStringContainsString( 'wp:srfm/input', $markup );
		$this->assertStringContainsString( '"label":"Name"', $markup );
		$this->assertStringContainsString( '"required":true', $markup );
		$this->assertStringContainsString( '"placeholder":"Jane"', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_textarea() {
		$markup = $this->make_importer(
			[
				[ 'id' => 1, 'type' => 'textarea', 'label' => 'Message', 'key' => 'message', 'input_limit' => 250 ],
			]
		)->build_form_content_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertStringContainsString( 'wp:srfm/textarea', $markup );
		$this->assertStringContainsString( '"maxLength":250', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_email() {
		$markup = $this->make_importer(
			[ [ 'id' => 1, 'type' => 'email', 'label' => 'Email', 'key' => 'email', 'required' => true ] ]
		)->build_form_content_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertStringContainsString( 'wp:srfm/email', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_number_with_range() {
		$markup = $this->make_importer(
			[
				[
					'id'     => 1,
					'type'   => 'number',
					'label'  => 'Age',
					'key'    => 'age',
					'number' => [ 'num_min' => 0, 'num_max' => 120, 'num_step' => 1 ],
				],
			]
		)->build_form_content_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertStringContainsString( 'wp:srfm/number', $markup );
		$this->assertStringContainsString( '"minValue":0', $markup );
		$this->assertStringContainsString( '"maxValue":120', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_listselect_with_preselected_index() {
		$markup = $this->make_importer(
			[
				[
					'id'      => 1,
					'type'    => 'listselect',
					'label'   => 'Country',
					'key'     => 'country',
					'options' => [
						[ 'label' => 'India',  'value' => 'IN', 'selected' => 0 ],
						[ 'label' => 'Canada', 'value' => 'CA', 'selected' => 1 ],
						[ 'label' => 'UK',     'value' => 'UK', 'selected' => 0 ],
					],
				],
			]
		)->build_form_content_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertStringContainsString( 'wp:srfm/dropdown', $markup );
		$this->assertStringContainsString( 'India', $markup );
		$this->assertStringContainsString( '"preselectedOptions":[1]', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_listradio_as_single_select_multichoice() {
		$markup = $this->make_importer(
			[
				[
					'id'      => 1,
					'type'    => 'listradio',
					'label'   => 'Pick',
					'key'     => 'pick',
					'options' => [
						[ 'label' => 'A', 'value' => 'a', 'selected' => 0 ],
						[ 'label' => 'B', 'value' => 'b', 'selected' => 0 ],
					],
				],
			]
		)->build_form_content_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertStringContainsString( 'wp:srfm/multi-choice', $markup );
		$this->assertStringContainsString( '"singleSelection":true', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_listcheckbox_as_multi_select_multichoice() {
		$markup = $this->make_importer(
			[
				[
					'id'      => 1,
					'type'    => 'listcheckbox',
					'label'   => 'Pick many',
					'key'     => 'pick_many',
					'options' => [
						[ 'label' => 'X', 'value' => 'x', 'selected' => 0 ],
						[ 'label' => 'Y', 'value' => 'y', 'selected' => 0 ],
					],
				],
			]
		)->build_form_content_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertStringContainsString( 'wp:srfm/multi-choice', $markup );
		$this->assertStringNotContainsString( '"singleSelection":true', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_terms_emits_gdpr() {
		$markup = $this->make_importer(
			[ [ 'id' => 1, 'type' => 'terms', 'label' => 'I agree', 'key' => 'terms', 'required' => true ] ]
		)->build_form_content_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertStringContainsString( 'wp:srfm/gdpr', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_submit_button_captures_label_only() {
		$markup = $this->make_importer(
			[
				[ 'id' => 1, 'type' => 'textbox', 'label' => 'Name', 'key' => 'name' ],
				[ 'id' => 2, 'type' => 'submit', 'label' => 'Send it!', 'key' => 'submit' ],
			]
		)->build_form_content_public( [ 'id' => 1, 'name' => 'F' ] );
		// Submit is not a block.
		$this->assertSame( 1, substr_count( $markup, 'wp:srfm/' ) );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_creditcard_is_unsupported() {
		$markup = $this->make_importer(
			[ [ 'id' => 1, 'type' => 'creditcard', 'label' => 'CC', 'key' => 'cc' ] ]
		)->build_form_content_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertSame( '', trim( $markup ) );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_xss_in_label_is_escaped() {
		$markup = $this->make_importer(
			[ [ 'id' => 1, 'type' => 'textbox', 'label' => 'Name <script>alert(1)</script>', 'key' => 'n' ] ]
		)->build_form_content_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertStringNotContainsString( '<script', $markup );
		$this->assertStringContainsString( '<', $markup );
	}

	/**
	 * @return void
	 */
	public function test_get_form_metas_translates_submit_text_from_field() {
		$metas = $this->make_importer(
			[ [ 'id' => 1, 'type' => 'submit', 'label' => 'Send', 'key' => 'submit' ] ]
		)->get_form_metas_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertSame( 'Send', $metas['_srfm_submit_button_text'] );
	}

	/**
	 * Email notification (Ninja `email` action).
	 *
	 * @return void
	 */
	public function test_get_form_metas_translates_email_notifications_from_actions() {
		$metas = $this->make_importer(
			[],
			[],
			[
				[
					'id'       => 99,
					'type'     => 'email',
					'label'    => 'Admin',
					'settings' => [
						'to'            => 'admin@example.com',
						'email_subject' => 'New entry',
						'email_message' => '{all_fields}',
					],
				],
			]
		)->get_form_metas_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertSame( 'admin@example.com', $metas['_srfm_email_notification'][0]['email_to'] );
		$this->assertSame( 'New entry', $metas['_srfm_email_notification'][0]['subject'] );
	}

	/**
	 * @return void
	 */
	public function test_get_form_metas_translates_confirmation_from_successmessage_action() {
		$metas = $this->make_importer(
			[],
			[],
			[
				[
					'id'       => 99,
					'type'     => 'successmessage',
					'label'    => 'Success',
					'settings' => [ 'success_msg' => '<p>Thanks!</p>' ],
				],
			]
		)->get_form_metas_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertSame( 'same page', $metas['_srfm_form_confirmation'][0]['confirmation_type'] );
		$this->assertStringContainsString( 'Thanks', $metas['_srfm_form_confirmation'][0]['message'] );
	}

	/**
	 * @return void
	 */
	public function test_get_form_metas_translates_redirect_action() {
		$metas = $this->make_importer(
			[],
			[],
			[
				[
					'id'       => 99,
					'type'     => 'redirect',
					'label'    => 'Redirect',
					'settings' => [ 'redirect_url' => 'https://example.com/done' ],
				],
			]
		)->get_form_metas_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertSame( 'different page', $metas['_srfm_form_confirmation'][0]['confirmation_type'] );
		$this->assertSame( 'https://example.com/done', $metas['_srfm_form_confirmation'][0]['page_url'] );
	}

	/**
	 * Conditional logic — verify operators valid for the source bucket
	 * translate and rule targets are rewritten to SureForms block_ids, and an
	 * operator invalid for the bucket (`greater_than` on a textbox/`default`
	 * source) is dropped rather than emitted as a dead rule.
	 *
	 * @return void
	 */
	public function test_get_form_metas_translates_conditional_logic_operators() {
		$metas = $this->make_importer(
			[
				[ 'id' => 1, 'type' => 'textbox', 'label' => 'Trigger', 'key' => 'trigger_field' ],
				[ 'id' => 2, 'type' => 'textbox', 'label' => 'Dep',     'key' => 'dep_field' ],
			],
			[
				'conditions' => [
					[
						'when'      => [
							[ 'key' => 'trigger_field', 'comparator' => 'equal',        'value' => 'yes' ],
							[ 'key' => 'trigger_field', 'comparator' => 'contains',     'value' => 'pre' ],
							[ 'key' => 'trigger_field', 'comparator' => 'starts_with',  'value' => 'a' ],
							[ 'key' => 'trigger_field', 'comparator' => 'ends_with',    'value' => 'z' ],
							// `>` is not valid for the `default` (text) bucket — dropped.
							[ 'key' => 'trigger_field', 'comparator' => 'greater_than', 'value' => '0' ],
						],
						'connector' => 'and',
						'then'      => [
							[ 'key' => 'dep_field', 'trigger' => 'show', 'value' => '' ],
						],
					],
				],
			]
		)->get_form_metas_public( [ 'id' => 1, 'name' => 'F' ] );
		$this->assertNotEmpty( $metas['_srfm_conditional_logic'] );
		$entry   = $metas['_srfm_conditional_logic'][0];
		$payload = (array) reset( $entry );
		$this->assertSame( 'show', $payload['action'] );
		$this->assertCount( 1, $payload['logic'] );
		$ops = array_column( $payload['logic'][0], 'operator' );
		$this->assertSame( [ '==', 'includes', 'startWith', 'endWith' ], $ops );
		$this->assertNotContains( '>', $ops, 'greater_than is invalid for a text source and must be dropped' );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{8}$/', $payload['logic'][0][0]['field'] );
	}

	/**
	 * A CL rule whose source is a list field using a text/numeric comparator
	 * (e.g. `contains`) is dropped — `list` supports only ==/!=/in/etc., so an
	 * `includes` operator on a list source would import as a dead rule.
	 *
	 * @return void
	 */
	public function test_get_form_metas_drops_invalid_operator_for_list_source() {
		$metas = $this->make_importer(
			[
				[ 'id' => 1, 'type' => 'listselect', 'label' => 'Choice', 'key' => 'choice_field', 'options' => [ [ 'label' => 'A', 'value' => 'a' ] ] ],
				[ 'id' => 2, 'type' => 'textbox',    'label' => 'Dep',    'key' => 'dep_field' ],
			],
			[
				'conditions' => [
					[
						'when'      => [
							// Valid for list.
							[ 'key' => 'choice_field', 'comparator' => 'equal',    'value' => 'a' ],
							// Invalid for list → dropped.
							[ 'key' => 'choice_field', 'comparator' => 'contains', 'value' => 'a' ],
						],
						'connector' => 'and',
						'then'      => [ [ 'key' => 'dep_field', 'trigger' => 'show', 'value' => '' ] ],
					],
				],
			]
		)->get_form_metas_public( [ 'id' => 1, 'name' => 'F' ] );
		$payload = (array) reset( $metas['_srfm_conditional_logic'][0] );
		$ops     = array_column( $payload['logic'][0], 'operator' );
		$this->assertSame( [ '==' ], $ops );
		$this->assertNotContains( 'includes', $ops );
	}

	/**
	 * @return void
	 */
	public function test_get_form_metas_translates_or_connector_as_or_groups() {
		$metas = $this->make_importer(
			[
				[ 'id' => 1, 'type' => 'textbox', 'label' => 'T', 'key' => 'trigger_field' ],
				[ 'id' => 2, 'type' => 'textbox', 'label' => 'D', 'key' => 'dep_field' ],
			],
			[
				'conditions' => [
					[
						'when'      => [
							[ 'key' => 'trigger_field', 'comparator' => 'equal', 'value' => 'a' ],
							[ 'key' => 'trigger_field', 'comparator' => 'equal', 'value' => 'b' ],
						],
						'connector' => 'or',
						'then'      => [ [ 'key' => 'dep_field', 'trigger' => 'show', 'value' => '' ] ],
					],
				],
			]
		)->get_form_metas_public( [ 'id' => 1, 'name' => 'F' ] );
		$entry = (array) reset( $metas['_srfm_conditional_logic'][0] );
		$this->assertCount( 2, $entry['logic'] );
	}

	/**
	 * @return void
	 */
	public function test_get_source_forms_returns_array_when_table_missing() {
		// `exist()` overridden true; the actual DB has no nf3_forms table
		// in the test env, so wpdb->get_results returns null → [].
		$this->assertIsArray( $this->make_importer()->source_forms_public() );
	}

	/**
	 * The real fetch_fields() degrades to [] when nf3 tables are absent
	 * (wpdb->get_results returns null on a missing table).
	 *
	 * @return void
	 */
	public function test_fetch_fields() {
		$this->assertSame( [], $this->make_importer()->fetch_fields_real( 1 ) );
	}

	/**
	 * The real fetch_form_meta() degrades to [] when nf3 tables are absent.
	 *
	 * @return void
	 */
	public function test_fetch_form_meta() {
		$this->assertSame( [], $this->make_importer()->fetch_form_meta_real( 1 ) );
	}

	/**
	 * The real fetch_actions() degrades to [] when nf3 tables are absent, and
	 * is memoized — a second call returns the same cached result rather than
	 * re-querying (notifications + confirmation both read it per form).
	 *
	 * @return void
	 */
	public function test_fetch_actions() {
		$importer = $this->make_importer();
		$first    = $importer->fetch_actions_real( 1 );
		$second   = $importer->fetch_actions_real( 1 );
		$this->assertSame( [], $first );
		$this->assertSame( $first, $second );
	}
}
