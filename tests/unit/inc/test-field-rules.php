<?php
/**
 * Tests for the server-side field rules applied to a submission.
 *
 * @package sureforms
 */

use SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager;
use SRFM\Inc\Compatibility\Multilingual\Providers\Provider;
use SRFM\Inc\Field_Rules;
use SRFM\Inc\Field_Validation;
use SRFM\Inc\Helper;

/**
 * Class Test_Field_Rules
 */
class Test_Field_Rules extends SRFM_Unit_Test_Case {

	/**
	 * The form under test.
	 *
	 * @var int
	 */
	private $form_id;

	/**
	 * Field keys by slug, built the way the field markup builds them.
	 *
	 * @var array<string,string>
	 */
	private $keys = [];

	/**
	 * Forms created by a test, deleted in tear_down().
	 *
	 * @var int[]
	 */
	private $forms = [];

	/**
	 * A contact form with one field of every type the rules cover.
	 */
	public function set_up() {
		parent::set_up();
		$this->forms = [];

		$blocks = [
			'<!-- wp:srfm/input {"block_id":"in000001","slug":"name","label":"Name","required":true,"textLength":30} /-->',
			'<!-- wp:srfm/email {"block_id":"em000001","slug":"email","label":"Email","required":true} /-->',
			'<!-- wp:srfm/number {"block_id":"nu000001","slug":"quantity","label":"Quantity","minValue":1,"maxValue":100} /-->',
			'<!-- wp:srfm/dropdown {"block_id":"dd000001","slug":"plan","label":"Plan","options":[{"label":"Basic","icon":""},{"label":"Pro & Team","icon":""}]} /-->',
			'<!-- wp:srfm/multi-choice {"block_id":"mc000001","slug":"topics","label":"Topics","options":[{"optionTitle":"Sales","icon":""},{"optionTitle":"Support","icon":""}],"minValue":1,"maxValue":2} /-->',
			'<!-- wp:srfm/checkbox {"block_id":"cb000001","slug":"terms","label":"Terms","required":true} /-->',
			'<!-- wp:srfm/gdpr {"block_id":"gd000001","slug":"consent","label":"Consent"} /-->',
			'<!-- wp:srfm/input {"block_id":"in000002","slug":"subject","label":"Subject","textLength":30} /-->',
			'<!-- wp:srfm/textarea {"block_id":"ta000001","slug":"message","label":"Message","maxLength":200} /-->',
		];

		$this->form_id = $this->form( implode( "\n", $blocks ) );

		$this->keys = [
			'name'     => $this->key( 'input', 'in000001', 'Name', 'name' ),
			'email'    => $this->key( 'email', 'em000001', 'Email', 'email' ),
			'quantity' => $this->key( 'number', 'nu000001', 'Quantity', 'quantity' ),
			'plan'     => $this->key( 'dropdown', 'dd000001', 'Plan', 'plan' ),
			'topics'   => $this->key( 'input-multi-choice', 'mc000001', 'Topics', 'topics' ),
			'terms'    => $this->key( 'checkbox', 'cb000001', 'Terms', 'terms' ),
			'consent'  => $this->key( 'gdpr', 'gd000001', 'Consent', 'consent' ),
			'subject'  => $this->key( 'input', 'in000002', 'Subject', 'subject' ),
			'message'  => $this->key( 'textarea', 'ta000001', 'Message', 'message' ),
		];
	}

	/**
	 * Deletes the forms the test created, with their meta.
	 */
	public function tear_down() {
		foreach ( $this->forms as $form_id ) {
			wp_delete_post( $form_id, true );
		}
		parent::tear_down();
	}

	/**
	 * Creates a published form. The content is slashed as the editor's save is,
	 * since wp_insert_post() unslashes it.
	 *
	 * @param string $content Block markup.
	 * @return int
	 */
	private function form( $content ) {
		$form_id       = self::factory()->post->create(
			[
				'post_type'    => 'sureforms_form',
				'post_status'  => 'publish',
				'post_content' => wp_slash( $content ),
			]
		);
		$this->forms[] = $form_id;

		return $form_id;
	}

	/**
	 * A field key as its markup names it.
	 *
	 * @param string $type     Key type segment.
	 * @param string $block_id Block id.
	 * @param string $label    Field label.
	 * @param string $slug     Field slug.
	 * @return string
	 */
	private function key( $type, $block_id, $label, $slug ) {
		return 'srfm-' . $type . '-' . $block_id . '-lbl-' . Helper::encode( $label ) . '-' . $slug;
	}

	/**
	 * A submission the browser would send for valid answers.
	 *
	 * @param array<string,mixed> $overrides Values by slug; null removes the key.
	 * @return array<string,mixed>
	 */
	private function submission( $overrides = [] ) {
		$values = array_merge(
			[
				'name'     => 'Jane Doe',
				'email'    => 'jane@example.com',
				'quantity' => '42',
				'plan'     => 'Pro & Team',
				'topics'   => 'Sales | Support',
				'terms'    => 'on',
				'consent'  => 'on',
				'subject'  => 'Hello',
				'message'  => 'A short message.',
			],
			$overrides
		);

		$data = [ 'form-id' => (string) $this->form_id ];
		foreach ( $values as $slug => $value ) {
			if ( null !== $value ) {
				$data[ $this->keys[ $slug ] ] = $value;
			}
		}

		return $data;
	}

	/**
	 * Errors the submit path reports for a raw submission.
	 *
	 * @param array<string,mixed> $raw Raw request params.
	 * @return array<string,string>
	 */
	private function errors( $raw ) {
		return Field_Validation::validate_form_data( Helper::sanitize_by_field_type( $raw ), $this->form_id, $raw );
	}

	/**
	 * A submission the browser accepts passes.
	 */
	public function test_valid_submission_passes() {
		$this->assertSame( [], $this->errors( $this->submission() ) );
	}

	/**
	 * Every tampered value from the QA report is rejected on the field it targets.
	 *
	 * @dataProvider tampered_provider
	 *
	 * @param string $slug  Field slug.
	 * @param mixed  $value Tampered value; null drops the key.
	 */
	public function test_tampered_value_is_rejected( $slug, $value ) {
		$errors = $this->errors( $this->submission( [ $slug => $value ] ) );

		$this->assertNotEmpty( $errors, "Tampered $slug must be rejected." );

		// A checkbox that was never sent is reported by its block id, as srfm-<type>-<block_id>.
		$block_id = Helper::get_block_id_from_key( $this->keys[ $slug ] );
		$this->assertNotEmpty(
			array_filter( array_keys( $errors ), static fn( $key ) => $block_id === Helper::get_block_id_from_key( $key ) || str_ends_with( $key, '-' . $block_id ) ),
			"The error names the $slug field."
		);
		$this->assertCount( 1, $errors, 'Only the tampered field is reported.' );
	}

	/**
	 * Tampered values, one field at a time.
	 *
	 * @return array<string,array<int,mixed>>
	 */
	public function tampered_provider() {
		return [
			'required name emptied'     => [ 'name', '' ],
			'email not an email'        => [ 'email', 'not-an-email' ],
			'number over max'           => [ 'quantity', '999' ],
			'number under min'          => [ 'quantity', '0.5' ],
			'number not numeric'        => [ 'quantity', 'abc' ],
			'dropdown injected option'  => [ 'plan', 'Injected Option' ],
			'multi choice injected'     => [ 'topics', 'Hacker' ],
			'multi choice one injected' => [ 'topics', 'Sales | Hacker' ],
			'required checkbox unset'   => [ 'terms', null ],
			'gdpr consent unset'        => [ 'consent', null ],
			'gdpr consent emptied'      => [ 'consent', '' ],
			'subject over max length'   => [ 'subject', str_repeat( 'x', 45 ) ],
			'message over max length'   => [ 'message', str_repeat( 'y', 260 ) ],
			'name sent as an array'     => [ 'name', [ 'Jane' ] ],
		];
	}

	/**
	 * Values the browser lets through are accepted, so no visitor who passed the
	 * browser checks is turned away.
	 *
	 * @dataProvider accepted_provider
	 *
	 * @param string $slug  Field slug.
	 * @param mixed  $value Value the browser can submit.
	 */
	public function test_browser_accepted_value_passes( $slug, $value ) {
		$this->assertSame( [], $this->errors( $this->submission( [ $slug => $value ] ) ) );
	}

	/**
	 * Values the browser accepts, including edge cases.
	 *
	 * @return array<string,array<int,mixed>>
	 */
	public function accepted_provider() {
		return [
			'optional number left empty'      => [ 'quantity', '' ],
			'number with thousands separator' => [ 'quantity', '1,0' ],
			'number at max'                   => [ 'quantity', '100' ],
			'number mid-typing minus'         => [ 'quantity', '-' ],
			'unicode local part'              => [ 'email', 'jöhn@example.com' ],
			'email with surrounding spaces'   => [ 'email', ' jane@example.com ' ],
			'dropdown entity label'           => [ 'plan', 'Pro & Team' ],
			'multi choice single pick'        => [ 'topics', 'Support' ],
			'optional dropdown empty'         => [ 'plan', '' ],
			'optional multi choice empty'     => [ 'topics', '' ],
			'subject exactly at max'          => [ 'subject', str_repeat( 'x', 30 ) ],
			'subject of emoji at max'         => [ 'subject', str_repeat( "\u{1F600}", 15 ) ],
			// A browser counts each line break once; the submitted value carries CRLF.
			'message with crlf at max'        => [ 'message', implode( "\r\n", array_fill( 0, 20, str_repeat( 'z', 9 ) ) ) ],
			'number too long to show'         => [ 'quantity', '∞' ],
		];
	}

	/**
	 * A field the form's conditional logic can hide is submitted empty when hidden,
	 * and the browser skips it, so the server does not require it either.
	 */
	public function test_conditionally_hidden_field_is_not_required() {
		update_post_meta(
			$this->form_id,
			'_srfm_conditional_logic',
			[
				[
					'in000001' => [
						'action' => 'show',
						'logic'  => [ [ [ 'field' => 'em000001' ] ] ],
					],
				],
			]
		);

		$this->assertSame( [], $this->errors( $this->submission( [ 'name' => '' ] ) ) );
		$this->assertArrayHasKey( $this->keys['subject'], $this->errors( $this->submission( [ 'subject' => str_repeat( 'x', 45 ) ] ) ), 'Fields without a rule are still checked.' );
	}

	/**
	 * Children of a container hidden by conditional logic keep stale values and are
	 * skipped by the browser, so they are skipped here too.
	 */
	public function test_children_of_conditional_address_are_skipped() {
		$form_id = $this->form( '<!-- wp:srfm/address {"block_id":"ad000001","slug":"address"} --><!-- wp:srfm/input {"block_id":"ad000002","slug":"street","label":"Street","required":true} /--><!-- /wp:srfm/address -->' );
		update_post_meta( $form_id, '_srfm_conditional_logic', [ [ 'ad000001' => [ 'action' => 'show' ] ] ] );
		$raw = [ $this->key( 'input', 'ad000002', 'Street', 'street' ) => '' ];

		$this->assertSame( [], Field_Validation::validate_form_data( $raw, $form_id, $raw ) );

		delete_post_meta( $form_id, '_srfm_conditional_logic' );
		$this->assertNotEmpty( Field_Validation::validate_form_data( $raw, $form_id, $raw ), 'Without the rule the child is required.' );
	}

	/**
	 * An address country can be filled by autocomplete with a name that is not in
	 * the stored list, so options are not checked inside an address.
	 */
	public function test_address_dropdown_options_are_not_checked() {
		$form_id = $this->form( '<!-- wp:srfm/address {"block_id":"ad000003","slug":"address"} --><!-- wp:srfm/dropdown {"block_id":"ad000004","slug":"country","label":"Country","options":[{"label":"India","icon":""}]} /--><!-- /wp:srfm/address -->' );
		$raw = [ $this->key( 'dropdown', 'ad000004', 'Country', 'country' ) => 'United States of America' ];

		$this->assertSame( [], Field_Validation::validate_form_data( $raw, $form_id, $raw ) );
	}

	/**
	 * Repeater rows carry nested values validated by SureForms Pro, so their
	 * children are left alone.
	 */
	public function test_repeater_children_are_skipped() {
		$form_id = $this->form( '<!-- wp:srfm/repeater {"block_id":"rp000001","slug":"people"} --><!-- wp:srfm/input {"block_id":"rp000002","slug":"person","label":"Person","required":true} /--><!-- /wp:srfm/repeater -->' );

		$raw = [ $this->key( 'input', 'rp000002', 'Person', 'person' ) => '' ];

		$this->assertSame( [], Field_Validation::validate_form_data( $raw, $form_id, $raw ) );
	}

	/**
	 * A default the form itself renders is accepted even when it is longer than
	 * the field's limit, because the browser submits it as is.
	 */
	public function test_default_longer_than_limit_is_accepted() {
		$default = str_repeat( 'd', 40 );
		$form_id = $this->form( '<!-- wp:srfm/input {"block_id":"df000001","slug":"code","label":"Code","textLength":30,"defaultValue":"' . $default . '"} /-->' );
		$key = $this->key( 'input', 'df000001', 'Code', 'code' );

		$this->assertSame( [], Field_Validation::validate_form_data( [ $key => $default ], $form_id, [ $key => $default ] ) );
		$this->assertNotEmpty( Field_Validation::validate_form_data( [ $key => $default . 'x' ], $form_id, [ $key => $default . 'x' ] ) );
	}

	/**
	 * A default holding a smart tag is filled in when the form renders, and the
	 * browser does not check a value it never saw typed, so neither length nor
	 * email format is enforced for such a field.
	 */
	public function test_smart_tag_default_is_not_length_or_format_checked() {
		$form_id = $this->form( '<!-- wp:srfm/input {"block_id":"st000001","slug":"ref","label":"Ref","textLength":10,"defaultValue":"{get_input:ref}"} /-->'
					. '<!-- wp:srfm/email {"block_id":"st000002","slug":"mail","label":"Mail","defaultValue":"{get_input:mail}"} /-->'
					. '<!-- wp:srfm/email {"block_id":"st000003","slug":"plain","label":"Plain","defaultValue":"Not an email"} /-->' );
		$values = [
			$this->key( 'input', 'st000001', 'Ref', 'ref' )       => str_repeat( 'r', 40 ),
			$this->key( 'email', 'st000002', 'Mail', 'mail' )     => 'not-an-email',
			$this->key( 'email', 'st000003', 'Plain', 'plain' )   => 'Not an email',
		];

		$this->assertSame( [], Field_Validation::validate_form_data( $values, $form_id, $values ) );

		$values[ $this->key( 'email', 'st000003', 'Plain', 'plain' ) ] = 'typed-not-an-email';
		$this->assertCount( 1, Field_Validation::validate_form_data( $values, $form_id, $values ), 'A literal default only covers its own value.' );
	}

	/**
	 * Options come through the same srfm_field_options filter the field renders
	 * with, so dynamic options are what a submission is checked against.
	 */
	public function test_options_follow_the_render_filter() {
		$dynamic = static function ( $options, $attributes ) {
			return 'dropdown' === ( $attributes['block_args']['slug'] ?? '' ) ? [ [ 'label' => 'From a post' ] ] : $options;
		};
		add_filter( 'srfm_field_options', $dynamic, 10, 2 );

		try {
			$this->assertSame( [], $this->errors( $this->submission( [ 'plan' => 'From a post' ] ) ) );
			$this->assertArrayHasKey( $this->keys['plan'], $this->errors( $this->submission( [ 'plan' => 'Basic' ] ) ) );
		} finally {
			remove_filter( 'srfm_field_options', $dynamic, 10 );
		}
	}

	/**
	 * A required choice field whose options come out empty is not rendered, so
	 * it is not required.
	 */
	public function test_required_choice_without_options_is_not_required() {
		$form_id = $this->form( '<!-- wp:srfm/dropdown {"block_id":"eo000001","slug":"source","label":"Source","required":true,"options":[{"label":"A"}]} /-->' );
		$empty = static fn() => [];
		$raw   = [ $this->key( 'dropdown', 'eo000001', 'Source', 'source' ) => '' ];

		$this->assertNotEmpty( Field_Validation::validate_form_data( $raw, $form_id, $raw ), 'With options it is required.' );

		add_filter( 'srfm_field_options', $empty );
		try {
			$this->assertSame( [], Field_Validation::validate_form_data( $raw, $form_id, $raw ) );
		} finally {
			remove_filter( 'srfm_field_options', $empty );
		}
	}

	/**
	 * Multi-choice and dropdown selection limits match the browser's rules.
	 */
	public function test_selection_limits() {
		$form_id = $this->form( '<!-- wp:srfm/multi-choice {"block_id":"ml000001","slug":"pick","label":"Pick","minValue":2,"maxValue":2,"options":[{"optionTitle":"A"},{"optionTitle":"B"},{"optionTitle":"C"}]} /-->'
					. '<!-- wp:srfm/dropdown {"block_id":"ml000002","slug":"many","label":"Many","multiSelect":true,"maxValue":1,"options":[{"label":"A"},{"label":"B"}]} /-->' );
		$pick = $this->key( 'input-multi-choice', 'ml000001', 'Pick', 'pick' );
		$many = $this->key( 'dropdown', 'ml000002', 'Many', 'many' );

		$check = static function ( $values ) use ( $form_id ) {
			return Field_Validation::validate_form_data( $values, $form_id, $values );
		};

		$this->assertSame( [], $check( [ $pick => 'A | B', $many => 'A' ] ) );
		$this->assertArrayHasKey( $pick, $check( [ $pick => 'A', $many => '' ] ), 'Below the minimum.' );
		$this->assertArrayHasKey( $pick, $check( [ $pick => 'A | B | C', $many => '' ] ), 'Above the maximum.' );
		$this->assertArrayHasKey( $many, $check( [ $pick => '', $many => 'A | B' ] ), 'Dropdown above its maximum.' );
		$this->assertSame( [], $check( [ $pick => '', $many => '' ] ), 'Optional fields left empty skip the limits, as in the browser.' );
	}

	/**
	 * The rules can be switched off per form.
	 */
	public function test_rules_can_be_disabled_per_form() {
		$off = static function ( $enabled, $form_id ) {
			return false;
		};
		add_filter( 'srfm_enforce_field_rules', $off, 10, 2 );

		try {
			$this->assertSame( [], $this->errors( $this->submission( [ 'name' => '' ] ) ) );
		} finally {
			remove_filter( 'srfm_enforce_field_rules', $off, 10 );
		}
	}

	/**
	 * Field_Rules::validate() reports an unsent required checkbox by its block
	 * id, and leaves other unsent fields alone: the browser posts every field it
	 * renders, so a missing one was not on the page.
	 */
	public function test_validate() {
		$errors = Field_Rules::validate( [], [], $this->form_id );

		$this->assertSame( [ 'srfm-checkbox-cb000001', 'srfm-gdpr-gd000001' ], array_keys( $errors ) );
		$this->assertSame( [], Field_Rules::validate( [], [], 0 ) );
	}

	/**
	 * A required field missing from the request was not rendered, so it is not
	 * required; the same field sent empty is.
	 */
	public function test_field_missing_from_request_is_not_checked() {
		$this->assertSame( [], $this->errors( $this->submission( [ 'name' => null ] ) ) );
		$this->assertArrayHasKey( $this->keys['name'], $this->errors( $this->submission( [ 'name' => '' ] ) ) );
	}

	/**
	 * A block hidden with block visibility is not on the page, so even a required
	 * checkbox in it is not required.
	 */
	public function test_block_hidden_by_visibility_is_skipped() {
		$form_id = $this->form( '<!-- wp:srfm/checkbox {"block_id":"bv000001","slug":"agree","label":"Agree","required":true,"metadata":{"blockVisibility":false}} /-->' );
		$shown   = $this->form( '<!-- wp:srfm/checkbox {"block_id":"bv000002","slug":"agree","label":"Agree","required":true} /-->' );

		$this->assertSame( [], Field_Validation::validate_form_data( [], $form_id, [] ) );
		$this->assertNotEmpty( Field_Validation::validate_form_data( [], $shown, [] ), 'A visible required checkbox is still required.' );
	}

	/**
	 * A multi-choice value is built from its option titles. When titles are empty
	 * or repeated the value cannot say which boxes are ticked, so required,
	 * limits and membership are left to the browser.
	 *
	 * @dataProvider unmappable_titles_provider
	 *
	 * @param string $options Options JSON.
	 */
	public function test_multi_choice_with_unmappable_titles_is_not_checked( $options ) {
		$form_id = $this->form( '<!-- wp:srfm/multi-choice {"block_id":"um000001","slug":"pick","label":"Pick","required":true,"minValue":2,"options":' . $options . '} /-->' );
		$key     = $this->key( 'input-multi-choice', 'um000001', 'Pick', 'pick' );

		foreach ( [ '', 'A', 'Other' ] as $value ) {
			$this->assertSame( [], Field_Validation::validate_form_data( [ $key => $value ], $form_id, [ $key => $value ] ), "Value '$value' is accepted." );
		}
	}

	/**
	 * Option lists whose titles cannot be mapped back from a value.
	 *
	 * @return array<string,array<int,string>>
	 */
	public function unmappable_titles_provider() {
		return [
			'empty title'    => [ '[{"optionTitle":"A"},{"optionTitle":""}]' ],
			'repeated title' => [ '[{"optionTitle":"A"},{"optionTitle":"A"}]' ],
			'smart tag'      => [ '[{"optionTitle":"A"},{"optionTitle":"{site_title}"}]' ],
		];
	}

	/**
	 * A dropdown label holding a smart tag renders as other text, so its options
	 * are not checked.
	 */
	public function test_dropdown_with_smart_tag_label_is_not_option_checked() {
		$form_id = $this->form( '<!-- wp:srfm/dropdown {"block_id":"sl000001","slug":"site","label":"Site","options":[{"label":"{site_title}"}]} /-->' );
		$key     = $this->key( 'dropdown', 'sl000001', 'Site', 'site' );

		$this->assertSame( [], Field_Validation::validate_form_data( [ $key => 'My Site' ], $form_id, [ $key => 'My Site' ] ) );
	}

	/**
	 * A limit that is not a positive number is no limit.
	 */
	public function test_non_positive_length_is_no_limit() {
		$form_id = $this->form( '<!-- wp:srfm/input {"block_id":"nl000001","slug":"a","label":"A","textLength":-5} /--><!-- wp:srfm/textarea {"block_id":"nl000002","slug":"b","label":"B","maxLength":0} /-->' );
		$raw     = [
			$this->key( 'input', 'nl000001', 'A', 'a' )    => str_repeat( 'a', 500 ),
			$this->key( 'textarea', 'nl000002', 'B', 'b' ) => str_repeat( 'b', 500 ),
		];

		$this->assertSame( [], Field_Validation::validate_form_data( $raw, $form_id, $raw ) );
	}

	/**
	 * A masked input or an address line is filled by script, which the browser's
	 * length limit does not stop, so its length is not checked.
	 */
	public function test_script_filled_input_is_not_length_checked() {
		$form_id = $this->form(
			'<!-- wp:srfm/input {"block_id":"sf000001","slug":"phone","label":"Phone","textLength":5,"inputMask":"(999) 999-9999"} /-->'
			. '<!-- wp:srfm/address {"block_id":"sf000002","slug":"address"} --><!-- wp:srfm/input {"block_id":"sf000003","slug":"street","label":"Street","textLength":5} /--><!-- /wp:srfm/address -->'
			. '<!-- wp:srfm/input {"block_id":"sf000004","slug":"plain","label":"Plain","textLength":5} /-->'
		);
		$raw     = [
			$this->key( 'input', 'sf000001', 'Phone', 'phone' )   => '(555) 123-4567',
			$this->key( 'input', 'sf000003', 'Street', 'street' ) => '1600 Amphitheatre Parkway',
		];

		$this->assertSame( [], Field_Validation::validate_form_data( $raw, $form_id, $raw ) );

		$raw[ $this->key( 'input', 'sf000004', 'Plain', 'plain' ) ] = '123456';
		$this->assertCount( 1, Field_Validation::validate_form_data( $raw, $form_id, $raw ), 'A plain input keeps its limit.' );
	}

	/**
	 * The page's content filter texturizes a default, so the curly-quoted value
	 * the browser submits is the rendered default.
	 */
	public function test_texturized_default_is_accepted() {
		$form_id = $this->form( '<!-- wp:srfm/textarea {"block_id":"tx000001","slug":"q","label":"Q","maxLength":5,"defaultValue":"\\u0022It\\u0027s ok\\u0022"} /-->' );
		$key     = $this->key( 'textarea', 'tx000001', 'Q', 'q' );
		$value   = "\u{201C}It\u{2019}s ok\u{201D}";

		$this->assertSame( [], Field_Validation::validate_form_data( [ $key => $value ], $form_id, [ $key => $value ] ) );
		$this->assertNotEmpty( Field_Validation::validate_form_data( [ $key => $value . '!' ], $form_id, [ $key => $value . '!' ] ), 'Only the rendered default itself.' );
	}

	/**
	 * A multilingual provider renders a translated default, which can be longer
	 * than the stored one, so any default-holding field passes its length check.
	 */
	public function test_translated_default_is_accepted_when_multilingual() {
		$form_id = $this->form( '<!-- wp:srfm/input {"block_id":"ml000009","slug":"g","label":"G","textLength":5,"defaultValue":"Hi"} /-->' );
		$key     = $this->key( 'input', 'ml000009', 'G', 'g' );
		$raw     = [ $key => 'Bonjour à tous' ];

		$this->assertNotEmpty( Field_Validation::validate_form_data( $raw, $form_id, $raw ), 'Without a provider the limit holds.' );

		$provider = $this->createMock( Provider::class );
		$provider->method( 'is_active' )->willReturn( true );
		$filter = static fn() => $provider;
		add_filter( 'srfm_multilingual_provider', $filter );
		$this->reset_multilingual_manager();

		try {
			$this->assertSame( [], Field_Validation::validate_form_data( $raw, $form_id, $raw ) );
		} finally {
			remove_filter( 'srfm_multilingual_provider', $filter );
			$this->reset_multilingual_manager();
		}
	}

	/**
	 * Clears the multilingual manager singleton so the next call resolves the
	 * provider afresh.
	 */
	private function reset_multilingual_manager() {
		$prop = new ReflectionProperty( Multilingual_Manager::class, 'instance' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );
	}
}
