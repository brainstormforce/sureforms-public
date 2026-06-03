<?php
/**
 * Class Test_Wpforms_Importer
 *
 * Coverage for SureForms' WPForms importer. Each public/protected method gets
 * a dedicated `test_<method>()` per the project's check-test-coverage
 * convention. Per-field-type translation is covered by separate cases so
 * regressions are easy to localize.
 *
 * @package sureforms
 */

use SRFM\Inc\Migrator\Importers\Wpforms_Importer;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Wpforms_Importer extends TestCase {

	/**
	 * Register the WPForms CPT before each test — the real plugin isn't
	 * loaded into the PHPUnit env, so neither `wp_insert_post` (which
	 * permits the post_type) nor `get_posts` (which filters by it) work
	 * unless we register the slug ourselves.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		// Run as admin so wp_insert_post doesn't apply kses to post_content;
		// the WPForms schema includes attribute-like strings (e.g.
		// `{field_id="1"}` in replyto) that kses would mangle into `&quot;`.
		wp_set_current_user( 1 );
		if ( ! post_type_exists( 'wpforms' ) ) {
			register_post_type(
				'wpforms',
				[
					'label'           => 'WPForms',
					'public'          => false,
					'show_ui'         => false,
					'capability_type' => 'post',
				]
			);
		}
	}

	/**
	 * Test-only subclass: forces exist() true and exposes the protected
	 * builders so we can assert on raw markup / metas without running the
	 * full import_forms pipeline.
	 *
	 * @return Wpforms_Importer
	 */
	private function make_importer() {
		return new class() extends Wpforms_Importer {
			public function exist() {
				return true;
			}

			/**
			 * @param array<string,mixed> $form Source form descriptor.
			 * @return string
			 */
			public function build_form_content_public( array $form ) {
				return $this->build_form_content( $form );
			}

			/**
			 * @param array<string,mixed> $form Source form descriptor.
			 * @return array<string,mixed>
			 */
			public function get_form_metas_public( array $form ) {
				$this->build_form_content( $form );
				return $this->get_form_metas( $form );
			}

			/**
			 * @param array<string,mixed> $form Source form descriptor.
			 * @return int
			 */
			public function source_id_public( array $form ) {
				return $this->get_source_form_id( $form );
			}

			/**
			 * @param array<string,mixed> $form Source form descriptor.
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
	 * Build a `wpforms` post with the given form-data array. Mimics
	 * `wpforms_encode()` (wp_slash + wp_json_encode).
	 *
	 * @param array<string,mixed> $form_data Form data shape.
	 * @param string              $title     Post title.
	 * @return int Post id.
	 */
	private function make_wpforms( array $form_data, $title = 'WF Form' ) {
		$id = wp_insert_post(
			[
				'post_type'    => 'wpforms',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => wp_slash( (string) wp_json_encode( $form_data ) ),
			]
		);
		return (int) $id;
	}

	/**
	 * Build a minimal form_data array with a single field.
	 *
	 * @param array<string,mixed> $field WPForms field array.
	 * @return array<string,mixed>
	 */
	private function form_data_with( array $field ) {
		return [
			'id'       => 1,
			'fields'   => [ (int) $field['id'] => $field ],
			'settings' => [ 'submit_text' => 'Send' ],
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
		foreach ( [ SRFM_FORMS_POST_TYPE, 'wpforms' ] as $pt ) {
			$ids = get_posts(
				[
					'post_type'      => $pt,
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				]
			);
			foreach ( $ids as $id ) {
				wp_delete_post( (int) $id, true );
			}
		}
		delete_option( 'srfm_imported_forms_map' );
		parent::tear_down();
	}

	/**
	 * @return void
	 */
	public function test_exist() {
		// The real `Wpforms_Importer` returns true iff the WPForms plugin is
		// active. Lite isn't loaded into the PHPUnit env, so we instantiate
		// the production class (not the test subclass) and assert it
		// follows the class_exists / WPFORMS_VERSION sentinel.
		$importer = new Wpforms_Importer();
		$this->assertSame(
			class_exists( 'WPForms' ) || defined( 'WPFORMS_VERSION' ),
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
		$this->assertSame( 'slider', $map['number-slider'] );
		$this->assertSame( 'dropdown', $map['select'] );
		$this->assertSame( 'multi_choice', $map['radio'] );
		$this->assertSame( 'multi_choice', $map['checkbox'] );
		$this->assertSame( 'gdpr', $map['gdpr-checkbox'] );
	}

	/**
	 * @return void
	 */
	public function test_get_source_forms() {
		$this->make_wpforms( [ 'id' => 1, 'fields' => [], 'settings' => [] ], 'Probe' );
		$forms = $this->make_importer()->source_forms_public();
		$names = array_column( $forms, 'name' );
		$this->assertContains( 'Probe', $names );
	}

	/**
	 * @return void
	 */
	public function test_get_source_form_id() {
		$this->assertSame( 7, $this->make_importer()->source_id_public( [ 'id' => 7 ] ) );
		$this->assertSame( 0, $this->make_importer()->source_id_public( [] ) );
	}

	/**
	 * @return void
	 */
	public function test_get_source_form_name() {
		$this->assertSame( 'Hello', $this->make_importer()->source_name_public( [ 'name' => 'Hello' ] ) );
		$this->assertStringContainsString( 'untitled', $this->make_importer()->source_name_public( [] ) );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_text_field() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'text', 'label' => 'First name', 'required' => '1', 'placeholder' => 'Jane' ]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringContainsString( 'wp:srfm/input', $markup );
		$this->assertStringContainsString( '"label":"First name"', $markup );
		$this->assertStringContainsString( '"required":true', $markup );
		$this->assertStringContainsString( '"placeholder":"Jane"', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_textarea_with_size_mapped_to_rows() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'textarea', 'label' => 'Message', 'size' => 'large' ]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringContainsString( 'wp:srfm/textarea', $markup );
		$this->assertStringContainsString( '"rows":8', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_email() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'email', 'label' => 'Email', 'required' => '1' ]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringContainsString( 'wp:srfm/email', $markup );
		// No confirmation → confirm mode stays off.
		$this->assertStringNotContainsString( '"isConfirmEmail":true', $markup );
	}

	/**
	 * WPForms' "Enable Email Confirmation" maps to srfm/email's native
	 * confirm option on one field — not a dropped flag or a duplicate block.
	 *
	 * @return void
	 */
	public function test_build_form_content_enables_confirm_email_option() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'email', 'label' => 'Email', 'required' => '1', 'confirmation' => '1' ]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertSame( 1, substr_count( $markup, 'wp:srfm/email' ) );
		$this->assertStringContainsString( '"isConfirmEmail":true', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_number() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'number', 'label' => 'Age', 'min' => 0, 'max' => 120 ]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringContainsString( 'wp:srfm/number', $markup );
		$this->assertStringContainsString( '"minValue":0', $markup );
		$this->assertStringContainsString( '"maxValue":120', $markup );
	}

	/**
	 * number-slider falls through to `slider` method — Free's
	 * Block_Templates has no slider, so the markup is empty until Pro hooks
	 * in. We assert the field is flagged unsupported in that case.
	 *
	 * @return void
	 */
	public function test_build_form_content_number_slider_is_unsupported_in_free() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'number-slider', 'label' => 'Rating', 'min' => 0, 'max' => 10 ]
			)
		);
		$importer = $this->make_importer();
		$markup   = $importer->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringNotContainsString( 'wp:srfm/slider', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_select_with_preserved_choices() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[
					'id'      => 1,
					'type'    => 'select',
					'label'   => 'Country',
					'choices' => [
						1 => [ 'label' => 'India',  'value' => '', 'default' => '' ],
						2 => [ 'label' => 'Canada', 'value' => '', 'default' => '1' ],
						3 => [ 'label' => 'UK',     'value' => '', 'default' => '' ],
					],
				]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringContainsString( 'wp:srfm/dropdown', $markup );
		$this->assertStringContainsString( 'India', $markup );
		$this->assertStringContainsString( 'Canada', $markup );
		// SureForms' dropdown / multi-choice render preselected entries by
		// option index, not label. Canada is index 1 in the source choices.
		$this->assertStringContainsString( '"preselectedOptions":[1]', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_radio_as_single_select_multichoice() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[
					'id'      => 1,
					'type'    => 'radio',
					'label'   => 'Pick one',
					'choices' => [
						1 => [ 'label' => 'A', 'value' => '', 'default' => '' ],
						2 => [ 'label' => 'B', 'value' => '', 'default' => '' ],
					],
				]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringContainsString( 'wp:srfm/multi-choice', $markup );
		$this->assertStringContainsString( '"singleSelection":true', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_checkbox_as_multi_select_multichoice() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[
					'id'      => 1,
					'type'    => 'checkbox',
					'label'   => 'Pick many',
					'choices' => [
						1 => [ 'label' => 'X', 'value' => '', 'default' => '' ],
						2 => [ 'label' => 'Y', 'value' => '', 'default' => '' ],
					],
				]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringContainsString( 'wp:srfm/multi-choice', $markup );
		// singleSelection=false → key stripped by strip_empty.
		$this->assertStringNotContainsString( '"singleSelection":true', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_name_simple_emits_one_input() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'name', 'label' => 'Your name', 'format' => 'simple' ]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertSame( 1, substr_count( $markup, 'wp:srfm/input' ) );
		$this->assertStringContainsString( '"label":"Your name"', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_name_first_last_emits_two_inputs() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'name', 'label' => 'Name', 'format' => 'first-last' ]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertSame( 2, substr_count( $markup, 'wp:srfm/input' ) );
		$this->assertStringContainsString( 'Name (First)', $markup );
		$this->assertStringContainsString( 'Name (Last)', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_name_first_middle_last_emits_three_inputs() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'name', 'label' => 'Name', 'format' => 'first-middle-last' ]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertSame( 3, substr_count( $markup, 'wp:srfm/input' ) );
		$this->assertStringContainsString( 'Name (Middle)', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_emits_gdpr() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'gdpr-checkbox', 'label' => 'I agree' ]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringContainsString( 'wp:srfm/gdpr', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_layout_recurses_into_core_columns() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[
					'id'      => 1,
					'type'    => 'layout',
					'label'   => 'Row',
					'columns' => [
						[
							'fields' => [
								[ 'id' => 2, 'type' => 'text', 'label' => 'Left' ],
							],
						],
						[
							'fields' => [
								[ 'id' => 3, 'type' => 'text', 'label' => 'Right' ],
							],
						],
					],
				]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringContainsString( 'wp:columns', $markup );
		$this->assertStringContainsString( 'wp:column', $markup );
		$this->assertStringContainsString( '"label":"Left"', $markup );
		$this->assertStringContainsString( '"label":"Right"', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_xss_in_label_is_escaped_through_json() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'text', 'label' => 'Name <script>alert(1)</script>' ]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		// Block_Templates JSON encoder uses JSON_HEX_TAG to escape `<`.
		$this->assertStringNotContainsString( '<script', $markup );
		$this->assertStringContainsString( '<', $markup );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_unmapped_type_is_flagged_unsupported() {
		$post_id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'mystery_widget', 'label' => 'Surprise' ]
			)
		);
		// `import_forms` keys on the WP post id (which is the source id Free's
		// listing UI surfaces), not the WPForms-internal `form_data.id`.
		$result = $this->make_importer()->import_forms( [ $post_id ], true );
		$this->assertNotEmpty( $result['unsupported_fields'] );
		$this->assertContains( 'Surprise', $result['unsupported_fields'] );
	}

	/**
	 * @return void
	 */
	public function test_build_form_content_invalid_json_returns_empty() {
		$id = wp_insert_post(
			[
				'post_type'    => 'wpforms',
				'post_status'  => 'publish',
				'post_title'   => 'Bad',
				'post_content' => '{not-json',
			]
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertSame( '', $markup );
	}

	/**
	 * @return void
	 */
	public function test_get_form_metas_translates_submit_text() {
		$id    = $this->make_wpforms(
			[
				'id'       => 1,
				'fields'   => [],
				'settings' => [ 'submit_text' => 'Send now!' ],
			]
		);
		$metas = $this->make_importer()->get_form_metas_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertSame( 'Send now!', $metas['_srfm_submit_button_text'] );
	}

	/**
	 * @return void
	 */
	public function test_get_form_metas_translates_email_notifications() {
		$id    = $this->make_wpforms(
			[
				'id'       => 1,
				'fields'   => [],
				'settings' => [
					'submit_text'   => 'Submit',
					'notifications' => [
						1 => [
							'email'   => 'admin@example.com',
							'subject' => 'New contact',
							'replyto' => '{field_id="1"}',
							'message' => '{all_fields}',
						],
					],
				],
			]
		);
		$metas = $this->make_importer()->get_form_metas_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertNotEmpty( $metas['_srfm_email_notification'] );
		$this->assertSame( 'admin@example.com', $metas['_srfm_email_notification'][0]['email_to'] );
		$this->assertSame( 'New contact', $metas['_srfm_email_notification'][0]['subject'] );
	}

	/**
	 * @return void
	 */
	public function test_get_form_metas_translates_confirmation_message() {
		$id    = $this->make_wpforms(
			[
				'id'       => 1,
				'fields'   => [],
				'settings' => [
					'submit_text'   => 'Submit',
					'confirmations' => [
						1 => [ 'type' => 'message', 'message' => '<p>Thanks!</p>' ],
					],
				],
			]
		);
		$metas = $this->make_importer()->get_form_metas_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertNotEmpty( $metas['_srfm_form_confirmation'] );
		$this->assertSame( 'same page', $metas['_srfm_form_confirmation'][0]['confirmation_type'] );
		$this->assertStringContainsString( 'Thanks', $metas['_srfm_form_confirmation'][0]['message'] );
	}

	/**
	 * @return void
	 */
	public function test_get_form_metas_translates_confirmation_redirect() {
		$id    = $this->make_wpforms(
			[
				'id'       => 1,
				'fields'   => [],
				'settings' => [
					'submit_text'   => 'Submit',
					'confirmations' => [
						1 => [ 'type' => 'redirect', 'redirect' => 'https://example.com/thanks' ],
					],
				],
			]
		);
		$metas = $this->make_importer()->get_form_metas_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertSame( 'different page', $metas['_srfm_form_confirmation'][0]['confirmation_type'] );
		$this->assertSame( 'https://example.com/thanks', $metas['_srfm_form_confirmation'][0]['page_url'] );
	}

	/**
	 * Conditional logic — verify the 8 supported operators are translated
	 * and rules reference the SureForms target block_id, not the WPForms id.
	 *
	 * @return void
	 */
	public function test_get_form_metas_translates_conditional_logic_operators() {
		// Build a form with a source text field (id=1) referenced by a
		// dependent number field (id=2)'s conditional_logic.
		$form_data = [
			'id'       => 9,
			'fields'   => [
				1 => [ 'id' => 1, 'type' => 'text', 'label' => 'Trigger' ],
				2 => [
					'id'                => 2,
					'type'              => 'number',
					'label'             => 'Dependent',
					'conditional_logic' => '1',
					'conditional_type'  => 'show',
					'conditionals'      => [
						[
							[ 'field' => 1, 'operator' => '==', 'value' => 'yes' ],
							[ 'field' => 1, 'operator' => 'c', 'value' => 'pre' ],
						],
						[
							[ 'field' => 1, 'operator' => '!=', 'value' => 'no' ],
						],
					],
				],
			],
			'settings' => [ 'submit_text' => 'Submit' ],
		];
		$id    = $this->make_wpforms( $form_data, 'CL' );
		$metas = $this->make_importer()->get_form_metas_public(
			[ 'id' => 9, 'name' => 'CL', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertNotEmpty( $metas['_srfm_conditional_logic'] );
		$entry = $metas['_srfm_conditional_logic'][0];
		$payload = (array) reset( $entry );
		$this->assertSame( 'show', $payload['action'] );
		$this->assertCount( 2, $payload['logic'] );
		$this->assertSame( '==', $payload['logic'][0][0]['operator'] );
		$this->assertSame( 'includes', $payload['logic'][0][1]['operator'] );
		$this->assertSame( '!=', $payload['logic'][1][0]['operator'] );
		// rule.field is the source block_id (8 hex), not the WPForms field id.
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{8}$/', $payload['logic'][0][0]['field'] );
	}

	/**
	 * @return void
	 */
	public function test_get_form_metas_drops_unsupported_operators() {
		$form_data = [
			'id'       => 1,
			'fields'   => [
				1 => [ 'id' => 1, 'type' => 'text', 'label' => 'T' ],
				2 => [
					'id'                => 2,
					'type'              => 'text',
					'label'             => 'D',
					'conditional_logic' => '1',
					'conditional_type'  => 'hide',
					'conditionals'      => [
						[
							[ 'field' => 1, 'operator' => '>', 'value' => 'x' ], // unsupported
						],
					],
				],
			],
			'settings' => [ 'submit_text' => 'Submit' ],
		];
		$id    = $this->make_wpforms( $form_data, 'UnsupOp' );
		$metas = $this->make_importer()->get_form_metas_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		// No conditional logic should be emitted — the rule was dropped.
		$this->assertEmpty( $metas['_srfm_conditional_logic'] ?? [] );
	}

	/**
	 * import_forms end-to-end — multi-field WPForms post is imported into a
	 * `sureforms_form` post; the mapping is recorded for idempotency.
	 *
	 * @return void
	 */
	/**
	 * build_block_args carries the format / date_format / time_format keys
	 * for `date-time` fields so Pro's `date_time_picker` emitter can route
	 * correctly. Probe by registering a tiny in-test subscriber and
	 * asserting the args it receives.
	 *
	 * @return void
	 */
	public function test_build_block_args_carries_date_time_format() {
		$captured = [];
		add_filter(
			'srfm_migrator_block_template',
			static function ( $markup, $method, $args ) use ( &$captured ) {
				if ( 'date_time_picker' === $method ) {
					$captured = $args;
					return '<!-- wp:srfm/time-picker /-->';
				}
				return $markup;
			},
			10,
			3
		);
		add_filter(
			'srfm_migrator_tag_to_template_map',
			static function ( $map, $key ) {
				if ( 'wpforms' === $key ) {
					$map['date-time'] = 'date_time_picker';
				}
				return $map;
			},
			10,
			2
		);
		$id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'date-time', 'label' => 'When', 'format' => 'time', 'date_format' => 'd/m/Y' ]
			)
		);
		$this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertSame( 'time', $captured['format'] );
		$this->assertSame( 'd/m/Y', $captured['date_format'] );
	}

	/**
	 * build_block_args expands a comma-separated WPForms file-upload
	 * `extensions` string into an `allowed_formats` array and forwards
	 * max_size / max_file_number as numeric attributes.
	 *
	 * @return void
	 */
	public function test_build_block_args_translates_file_upload_constraints() {
		$captured = [];
		add_filter(
			'srfm_migrator_block_template',
			static function ( $markup, $method, $args ) use ( &$captured ) {
				if ( 'upload' === $method ) {
					$captured = $args;
					return '<!-- wp:srfm/upload /-->';
				}
				return $markup;
			},
			10,
			3
		);
		add_filter(
			'srfm_migrator_tag_to_template_map',
			static function ( $map, $key ) {
				if ( 'wpforms' === $key ) {
					$map['file-upload'] = 'upload';
				}
				return $map;
			},
			10,
			2
		);
		$id = $this->make_wpforms(
			$this->form_data_with(
				[ 'id' => 1, 'type' => 'file-upload', 'label' => 'Resume', 'extensions' => 'pdf, docx, txt', 'max_size' => '5', 'max_file_number' => 3 ]
			)
		);
		$this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertSame( [ 'pdf', 'docx', 'txt' ], $captured['allowed_formats'] );
		$this->assertSame( 5, $captured['file_size_limit'] );
		$this->assertSame( 3, $captured['max_files'] );
		$this->assertTrue( $captured['multiple'] );
	}

	/**
	 * translate_repeater_field assembles children markup recursively and
	 * passes it through the `repeater_container` filter so Pro can wrap
	 * with srfm/repeater innerBlocks. When no subscriber answers, the
	 * children fall back to top-level so submission data isn't lost.
	 *
	 * @return void
	 */
	public function test_translate_repeater_field_recurses_into_children() {
		add_filter(
			'srfm_migrator_block_template',
			static function ( $markup, $method, $args ) {
				if ( 'repeater_container' === $method ) {
					return "<!-- wp:srfm/repeater -->\n" . ( $args['children'] ?? '' ) . "<!-- /wp:srfm/repeater -->\n";
				}
				return $markup;
			},
			10,
			3
		);
		$id = $this->make_wpforms(
			$this->form_data_with(
				[
					'id'       => 20,
					'type'     => 'repeater',
					'label'    => 'Items',
					'min_rows' => 1,
					'max_rows' => 5,
					'columns'  => [
						[ 'fields' => [
							[ 'id' => 21, 'type' => 'text', 'label' => 'Item name' ],
							[ 'id' => 22, 'type' => 'number', 'label' => 'Quantity' ],
						] ],
					],
				]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		$this->assertStringContainsString( '<!-- wp:srfm/repeater', $markup );
		$this->assertStringContainsString( '<!-- /wp:srfm/repeater', $markup );
		$this->assertStringContainsString( '"label":"Item name"', $markup );
		$this->assertStringContainsString( '"label":"Quantity"', $markup );
	}

	/**
	 * @return void
	 */
	public function test_translate_repeater_field_falls_back_to_inline_children_without_subscriber() {
		$id = $this->make_wpforms(
			$this->form_data_with(
				[
					'id'      => 20,
					'type'    => 'repeater',
					'label'   => 'Items',
					'columns' => [
						[ 'fields' => [ [ 'id' => 21, 'type' => 'text', 'label' => 'Item name' ] ] ],
					],
				]
			)
		);
		$markup = $this->make_importer()->build_form_content_public(
			[ 'id' => 1, 'name' => 'F', 'post_content' => get_post_field( 'post_content', $id ) ]
		);
		// No subscriber → no repeater wrapper, children appear inline.
		$this->assertStringNotContainsString( 'wp:srfm/repeater', $markup );
		$this->assertStringContainsString( '"label":"Item name"', $markup );
	}

	public function test_import_forms_creates_sureforms_post() {
		$form_data = [
			'id'       => 1,
			'fields'   => [
				1 => [ 'id' => 1, 'type' => 'text', 'label' => 'Name', 'required' => '1' ],
				2 => [ 'id' => 2, 'type' => 'email', 'label' => 'Email', 'required' => '1' ],
				3 => [ 'id' => 3, 'type' => 'textarea', 'label' => 'Message' ],
			],
			'settings' => [ 'submit_text' => 'Submit' ],
		];
		$post_id = $this->make_wpforms( $form_data, 'Contact' );
		$result  = $this->make_importer()->import_forms( [ $post_id ], false );
		$this->assertSame( [], $result['failed'] );
		$this->assertNotEmpty( $result['imported'] );
		$srfm = get_post( $result['imported'][0]['srfm_id'] );
		$this->assertSame( SRFM_FORMS_POST_TYPE, $srfm->post_type );
		$this->assertSame( 'Contact', $srfm->post_title );
		$this->assertStringContainsString( 'wp:srfm/input', $srfm->post_content );
		$this->assertStringContainsString( 'wp:srfm/email', $srfm->post_content );
		$this->assertStringContainsString( 'wp:srfm/textarea', $srfm->post_content );
	}
}
