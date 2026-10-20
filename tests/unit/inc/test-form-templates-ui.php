<?php
/**
 * Class Test_Form_Templates_UI
 *
 * What the template library shows for paid templates: preview pictures,
 * setup steps and result tag samples. The formula evaluator is tested in
 * form-templates/test-formula.php.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Templates;

class Test_Form_Templates_UI extends TestCase {

	public function tear_down() {
		remove_all_filters( 'srfm_form_templates' );
		remove_all_filters( 'srfm_pro_tier' );
		remove_all_filters( 'srfm_form_template_calculation_sample' );
		remove_filter( 'srfm_payment_methods_registry', [ Form_Templates::class, 'preview_payment_methods' ], 99 );
		delete_option( 'srfm_google_maps_settings' );
		$property = new ReflectionProperty( Form_Templates::class, 'templates' );
		$property->setAccessible( true );
		$property->setValue( null, null );
		parent::tear_down();
	}

	/**
	 * Add one test template and return its JS summary.
	 *
	 * @param array<string, mixed> $keys Template keys.
	 * @return array<string, mixed>
	 */
	private function template_for_js( $keys ) {
		$template = array_merge(
			[
				'id'       => 'ui-test',
				'title'    => 'UI test',
				'category' => 'contact',
				'fields'   => [
					[
						'fieldType' => 'input',
						'slug'      => 'name',
						'label'     => 'Name',
					],
				],
			],
			$keys
		);
		add_filter(
			'srfm_form_templates',
			static function ( $list ) use ( $template ) {
				$list[] = $template;
				return $list;
			}
		);
		foreach ( Form_Templates::get_templates_for_js() as $item ) {
			if ( 'ui-test' === $item['id'] ) {
				return $item;
			}
		}
		$this->fail( 'Test template missing.' );
	}

	private function calc_fields() {
		return [
			[
				'fieldType'   => 'number',
				'slug'        => 'price',
				'label'       => 'Price',
				'placeholder' => 'e.g. 1,200',
			],
			[
				'fieldType'    => 'dropdown',
				'slug'         => 'plan',
				'label'        => 'Plan',
				'fieldOptions' => [
					[
						'label' => 'Basic',
						'value' => '3',
					],
					[
						'label' => 'Plus',
						'value' => '5',
					],
				],
			],
			[
				'fieldType'          => 'number',
				'slug'               => 'total',
				'label'              => 'Total',
				'calculationFormula' => '({form:price} + {form:plan}) * 2',
				'prefix'             => '$',
			],
		];
	}

	public function test_preview_image_is_exposed_as_plugin_url() {
		$item = $this->template_for_js( [ 'preview_image' => 'quiz.webp' ] );
		$this->assertSame( SRFM_URL . 'images/templates/quiz.webp', $item['preview_image'] );
	}

	public function test_preview_image_rejects_paths_leaving_the_folder() {
		$this->assertSame( '', Form_Templates::get_preview_image_url( [ 'preview_image' => '../../secret.png' ] ) );
		$this->assertSame( '', Form_Templates::get_preview_image_url( [ 'preview_image' => 'quiz.php' ] ) );
		$this->assertSame( '', Form_Templates::get_preview_image_url( [] ) );
		$this->assertSame( 'https://cdn.example.com/q.webp', Form_Templates::get_preview_image_url( [ 'preview_image' => 'https://cdn.example.com/q.webp' ] ) );
	}

	public function test_free_template_has_no_preview_image_or_setup() {
		$item = $this->template_for_js( [] );
		$this->assertSame( '', $item['preview_image'] );
		$this->assertSame( [], $item['setup'] );
		$this->assertSame( 0, $item['preview_missing'] );
	}

	public function test_locked_preview_counts_fields_this_site_leaves_out() {
		add_filter( 'srfm_pro_tier', static fn() => 'free' );
		$item = $this->template_for_js(
			[
				'requires' => 'business',
				'fields'   => [
					[
						'fieldType' => 'input',
						'slug'      => 'name',
						'label'     => 'Name',
					],
					[
						'fieldType' => 'signature',
						'slug'      => 'sign',
						'label'     => 'Sign',
						'pro_tier'  => 'business',
					],
				],
			]
		);
		$this->assertTrue( $item['is_locked'] );
		$this->assertSame( 1, $item['preview_missing'] );
	}

	/**
	 * Block markup of a template with a payment field, as the preview builds it.
	 *
	 * @return string
	 */
	private function payment_template_content() {
		return Form_Templates::build_content(
			[
				'id'     => 'ui-pay',
				'title'  => 'Pay',
				'fields' => [
					[
						'fieldType' => 'input',
						'slug'      => 'full-name',
						'label'     => 'Name',
					],
					[
						'fieldType' => 'email',
						'slug'      => 'email',
						'label'     => 'Email',
					],
					[
						'fieldType'          => 'payment',
						'slug'               => 'pay',
						'label'              => 'Payment',
						'paymentType'        => 'one-time',
						'amountType'         => 'fixed',
						'fixedAmount'        => 25,
						'customerNameField'  => 'full-name',
						'customerEmailField' => 'email',
					],
				],
			]
		);
	}

	public function test_preview_payment_methods_show_stripe_before_it_is_connected() {
		$methods = Form_Templates::preview_payment_methods(
			[
				'stripe' => [
					'id'              => 'stripe',
					'enabled'         => false,
					'container_class' => 'srfm-stripe-payment-element',
				],
				'paypal' => [
					'id'      => 'paypal',
					'enabled' => false,
				],
			]
		);
		$this->assertTrue( $methods['stripe']['enabled'] );
		$this->assertStringContainsString( 'srfm-template-preview__payment-note', $methods['stripe']['place_holder_content'] );
		// Only Stripe, the free gateway, is shown; other methods are left as they are.
		$this->assertFalse( $methods['paypal']['enabled'] );
	}

	public function test_preview_payment_methods_leave_a_connected_stripe_alone() {
		$connected = [
			'stripe' => [
				'id'      => 'stripe',
				'enabled' => true,
			],
		];
		$this->assertSame( $connected, Form_Templates::preview_payment_methods( $connected ) );
		$this->assertSame( 'not-an-array', Form_Templates::preview_payment_methods( 'not-an-array' ) );
		$this->assertSame( [], Form_Templates::preview_payment_methods( [] ) );
	}

	public function test_preview_renders_the_payment_field_of_a_template() {
		$content = $this->payment_template_content();
		$this->assertStringContainsString( '<!-- wp:srfm/payment ', $content );

		// A form leaves the field out while Stripe is not connected...
		$this->assertStringNotContainsString( 'srfm-payment-block', do_blocks( $content ) );

		// ...the preview shows it, with the note where the card fields go.
		add_filter( 'srfm_payment_methods_registry', [ Form_Templates::class, 'preview_payment_methods' ], 99 );
		$markup = do_blocks( $content );
		$this->assertStringContainsString( 'srfm-payment-block', $markup );
		$this->assertStringContainsString( 'srfm-template-preview__payment-note', $markup );
		$this->assertStringContainsString( 'data-customer-email-field="email"', $markup );
		$this->assertStringContainsString( 'data-stripe-key=""', $markup );
	}

	public function test_preview_form_renders_each_block_through_render_block() {
		// SureForms Pro enqueues each Pro block's scripts and styles from the
		// `render_block` filter, so the preview must render the in-memory form
		// through the block pipeline like a saved form.
		$content = $this->payment_template_content();
		$seed    = new ReflectionMethod( Form_Templates::class, 'seed_preview_form' );
		$seed->setAccessible( true );
		$seed->invoke( null, [ 'title' => 'Pay' ], $content, [] );
		$id = new ReflectionMethod( Form_Templates::class, 'get_preview_post_id' );
		$id->setAccessible( true );
		$form_id = $id->invoke( null );

		$rendered = [];
		$spy      = static function ( $block_content, $block ) use ( &$rendered ) {
			$rendered[] = $block['blockName'] ?? '';
			return $block_content;
		};
		add_filter( 'render_block', $spy, 10, 2 );
		$markup = \SRFM\Inc\Generate_Form_Markup::get_form_markup( $form_id, false );
		remove_filter( 'render_block', $spy, 10 );
		Form_Templates::forget_preview_form();

		$this->assertStringContainsString( 'srfm-input-block', (string) $markup );
		$this->assertContains( 'srfm/input', $rendered );
		$this->assertContains( 'srfm/email', $rendered );
		$this->assertContains( 'srfm/payment', $rendered );
	}

	public function test_setup_stripe_not_connected_and_unknown_ids_ignored() {
		$setup = Form_Templates::get_setup( [ 'setup' => [ 'stripe', 'fax_machine', 'stripe' ] ] );
		$this->assertCount( 1, $setup );
		$this->assertSame( 'stripe', $setup[0]['id'] );
		$this->assertFalse( $setup[0]['done'] );
		$this->assertStringContainsString( 'gateway=stripe', $setup[0]['url'] );
		$this->assertNotSame( '', $setup[0]['label'] );
	}

	public function test_setup_skips_pro_steps_without_pro_and_reads_maps_key() {
		$ids = array_column( Form_Templates::get_setup( [ 'setup' => [ 'paypal', 'pdf_library', 'google_maps' ] ] ), 'done', 'id' );
		if ( ! class_exists( '\SRFM_Pro\Inc\Business\Payments\PayPal\Helper' ) ) {
			$this->assertArrayNotHasKey( 'paypal', $ids );
		}
		if ( ! class_exists( '\SRFM_Pro\Inc\Business\Pdf\Utils' ) ) {
			$this->assertArrayNotHasKey( 'pdf_library', $ids );
		}
		$this->assertFalse( $ids['google_maps'] );

		update_option( 'srfm_google_maps_settings', [ 'srfm_google_maps_api_key' => 'AIza-test' ] );
		$maps = Form_Templates::get_setup( [ 'setup' => [ 'google_maps' ] ] );
		$this->assertTrue( $maps[0]['done'] );
		$this->assertStringContainsString( 'tab=google-maps-settings', $maps[0]['url'] );
	}

	public function test_setup_is_in_js_summary() {
		$item = $this->template_for_js( [ 'setup' => [ 'stripe' ] ] );
		$this->assertSame( [ 'stripe' ], array_column( $item['setup'], 'id' ) );
	}

	public function test_tag_samples_for_pro_tags_in_messages() {
		$template = [
			'confirmation'  => [
				'heading' => 'You scored {quiz_score}',
				'text'    => '{quiz_percentage} – {quiz_pass_fail} ({quiz_letter_grade}). {poll_results} Paid {form-payment:pay:amount}, order {form-payment:pay:order-id}, {form-payment:pay:status}.',
			],
			'email_subject' => 'Result {quiz_result}',
			'email_intro'   => 'Hi {form:name}',
		];
		$fields   = [
			[
				'fieldType'   => 'payment',
				'slug'        => 'pay',
				'label'       => 'Payment',
				'amountType'  => 'fixed',
				'fixedAmount' => 49,
			],
		];
		$samples  = Form_Templates::get_messages( $template, $fields )['tag_samples'];

		$this->assertSame( '8 / 10', $samples['{quiz_score}'] );
		$this->assertSame( '80%', $samples['{quiz_percentage}'] );
		$this->assertSame( 'Pass', $samples['{quiz_pass_fail}'] );
		$this->assertSame( 'B', $samples['{quiz_letter_grade}'] );
		$this->assertNotEmpty( $samples['{quiz_result}'] );
		$this->assertSame( '(live poll results)', $samples['{poll_results}'] );
		// In the store currency, which other tests may change.
		$symbol = \SRFM\Inc\Payments\Payment_Helper::get_currency_symbol( \SRFM\Inc\Payments\Payment_Helper::get_currency() );
		$this->assertSame( $symbol . '49.00', $samples['{form-payment:pay:amount}'] );
		$this->assertSame( 'SF-#1042', $samples['{form-payment:pay:order-id}'] );
		$this->assertSame( 'succeeded', $samples['{form-payment:pay:status}'] );
		$this->assertArrayNotHasKey( '{form:name}', $samples );
	}

	public function test_result_tags_and_samples_follow_template_features() {
		$template = [
			'quiz'   => [ 'pass' => 70 ],
			'survey' => true,
		];
		$messages = Form_Templates::get_messages( $template, $this->calc_fields() );
		$tags     = array_column( $messages['result_tags'], 'tag' );

		$this->assertContains( '{quiz_score}', $tags );
		$this->assertContains( '{poll_results}', $tags );
		$this->assertContains( '{form:total}', $tags );
		$this->assertSame( '8 / 10', $messages['tag_samples']['{quiz_score}'] );

		$plain = Form_Templates::get_messages( [], [] );
		$this->assertSame( [], $plain['result_tags'] );
		$this->assertSame( [], $plain['tag_samples'] );
	}

	public function test_calculated_field_sample_uses_sample_answers() {
		$fields  = $this->calc_fields();
		$answers = array_column( Form_Templates::get_answers( $fields ), 'sample', 'slug' );

		// (1200 + 3) * 2, without the field's prefix: the entry saves the number only.
		$this->assertSame( '1,200', $answers['price'] );
		$this->assertSame( '2,406', $answers['total'] );

		$samples = Form_Templates::get_tag_samples( '{calculation-result}', [], $fields );
		$this->assertSame( '2,406', $samples['{calculation-result}'] );
	}

	public function test_calculated_sample_falls_back_and_is_filterable() {
		$fields = [
			[
				'fieldType'          => 'number',
				'slug'               => 'ratio',
				'calculationFormula' => '{form:missing} / 0',
			],
		];
		$this->assertSame( Form_Templates::CALCULATION_FALLBACK, Form_Templates::calculated_sample( $fields[0], $fields ) );

		$fields[0]['calculationFormula'] = '10 / 0';
		$this->assertSame( '123.45', Form_Templates::calculated_sample( $fields[0], $fields ) );

		add_filter( 'srfm_form_template_calculation_sample', static fn() => 7.5 );
		$this->assertSame( '7.50', Form_Templates::calculated_sample( $fields[0], $fields ) );

		$this->assertNull( Form_Templates::calculated_sample( [ 'slug' => 'plain' ], $fields ) );
	}

	public function test_answers_leave_out_fields_without_a_readable_value() {
		$types  = [ 'input', 'hidden', 'gdpr', 'page-break', 'inline-button', 'upload', 'html', 'signature', 'payment', 'password', 'repeater' ];
		$fields = array_map(
			static fn( $type ) => [
				'fieldType' => $type,
				'slug'      => 'f-' . $type,
				'label'     => ucfirst( $type ),
			],
			$types
		);

		// Hidden values (source page, UTM tags) are useful in the owner's email.
		$this->assertSame( [ 'f-input', 'f-hidden' ], array_column( Form_Templates::get_answers( $fields ), 'slug' ) );
	}

	public function test_library_cards_do_not_list_hidden_fields() {
		add_filter( 'srfm_pro_tier', static fn() => 'business' );
		$item = wp_list_filter( Form_Templates::get_templates_for_js(), [ 'id' => 'contact-us' ] );
		$item = reset( $item );
		remove_all_filters( 'srfm_pro_tier' );

		$hidden = wp_list_filter( Form_Templates::resolve_fields( Form_Templates::get_template( 'contact-us' ), 'business' ), [ 'fieldType' => 'hidden' ] );
		$this->assertNotEmpty( $hidden, 'contact-us records the source page with a plan.' );
		foreach ( $hidden as $field ) {
			$this->assertNotContains( $field['label'], $item['fields'] );
		}
		$this->assertContains( 'source-page', array_column( $item['answers'], 'slug' ) );
	}

	/**
	 * Form type of catalog templates on each plan.
	 *
	 * @return array<string, array{0: string, 1: array<string, string>}>
	 */
	public function form_type_by_plan() {
		return [
			// Step dividers need a plan, so free sites build these on one page.
			// Locked templates show their Premium version.
			'free'     => [
				'free',
				[
					'contact-us'                    => 'single',
					'quote-request'                 => 'single',
					'rental-application'            => 'multistep',
					'conversational-lead-qualifier' => 'conversational',
				],
			],
			'starter'  => [
				'starter',
				[
					'contact-us'                    => 'single',
					'quote-request'                 => 'multistep',
					'rental-application'            => 'multistep',
					'conversational-lead-qualifier' => 'conversational',
				],
			],
			'pro'      => [
				'pro',
				[
					'contact-us'                    => 'single',
					'quote-request'                 => 'multistep',
					'rental-application'            => 'multistep',
					'conversational-lead-qualifier' => 'conversational',
				],
			],
			'business' => [
				'business',
				[
					'contact-us'                    => 'single',
					'quote-request'                 => 'multistep',
					'rental-application'            => 'multistep',
					'conversational-lead-qualifier' => 'conversational',
				],
			],
		];
	}

	/**
	 * @dataProvider form_type_by_plan
	 *
	 * @param string                $tier     Plan.
	 * @param array<string, string> $expected Form type by template id.
	 */
	public function test_library_form_type_follows_the_plan( $tier, $expected ) {
		add_filter( 'srfm_pro_tier', static fn() => $tier );
		$types = array_column( Form_Templates::get_templates_for_js(), 'form_type', 'id' );

		foreach ( $expected as $id => $type ) {
			$this->assertSame( $type, $types[ $id ] ?? '', $id . ' on ' . $tier );
		}
		foreach ( $types as $id => $type ) {
			$this->assertContains( $type, [ 'single', 'multistep', 'conversational' ], $id );
		}
	}

	public function test_form_type_of_a_third_party_template() {
		$this->assertSame( 'single', $this->template_for_js( [] )['form_type'] );
		remove_all_filters( 'srfm_form_templates' );
		$this->reset_templates();

		$steps = $this->template_for_js(
			[
				'fields' => [
					[
						'fieldType' => 'input',
						'slug'      => 'name',
						'label'     => 'Name',
					],
					[
						'fieldType' => 'page-break',
						'slug'      => 'step-2',
						'label'     => 'Step 2',
					],
				],
			]
		);
		$this->assertSame( 'multistep', $steps['form_type'] );
		remove_all_filters( 'srfm_form_templates' );
		$this->reset_templates();

		$this->assertSame( 'conversational', $this->template_for_js( [ 'conversational' => [ 'start_button' => 'Go' ] ] )['form_type'] );
	}

	private function reset_templates() {
		$property = new ReflectionProperty( Form_Templates::class, 'templates' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	public function test_pro_keys_show_as_features_from_their_plan() {
		$template = [
			'confirmations'    => [ [ 'message' => 'x', 'rules' => [] ] ],
			'email_conditions' => [ [ 'rules' => [] ] ],
			'attach_uploads'   => [ 'cv' ],
		];
		$expected = [ 'conditional_confirmations', 'conditional_emails', 'email_attachments' ];

		$this->assertEmpty( array_intersect( $expected, Form_Templates::get_features( $template, [], 'free' ) ) );
		foreach ( $expected as $feature ) {
			$this->assertContains( $feature, Form_Templates::get_features( $template, [], 'starter' ) );
		}

		// Free sites see them as what Premium adds.
		add_filter( 'srfm_pro_tier', static fn() => 'free' );
		$item = wp_list_filter( Form_Templates::get_templates_for_js(), [ 'id' => 'customer-feedback' ] );
		$this->assertContains( 'conditional_confirmations', reset( $item )['pro_features'] );
		remove_all_filters( 'srfm_pro_tier' );
	}

	public function test_payment_amount_sample_uses_the_store_currency() {
		$fields = [
			[
				'fieldType'   => 'payment',
				'slug'        => 'pay',
				'amountType'  => 'fixed',
				'fixedAmount' => 25,
				// Ignored: the block charges in the payment settings currency.
				'currency'    => 'USD',
			],
		];
		$before = \SRFM\Inc\Payments\Payment_Helper::get_global_setting( 'currency', 'USD' );
		\SRFM\Inc\Payments\Payment_Helper::update_global_setting( 'currency', 'EUR' );
		$samples = Form_Templates::get_tag_samples( '{form-payment:pay:amount}', [], $fields );
		\SRFM\Inc\Payments\Payment_Helper::update_global_setting( 'currency', $before );

		$this->assertSame( '€25.00', $samples['{form-payment:pay:amount}'] );
	}

	public function test_catalog_thank_you_matches_the_template_message() {
		$message = \SRFM\Inc\Form_Templates\Catalog::thank_you( 'Thanks, "Jane"', 'We will reply <soon>.' );

		$this->assertSame( Form_Templates::confirmation_message( 'Thanks, "Jane"', 'We will reply <soon>.' ), $message );
		$this->assertStringContainsString( 'images/check-icon.svg', $message );
		$this->assertStringContainsString( '&lt;soon&gt;', $message );
		// Saved through wp_kses_post(), which must keep the icon.
		$this->assertStringContainsString( 'images/check-icon.svg', wp_kses_post( $message ) );
	}
}
