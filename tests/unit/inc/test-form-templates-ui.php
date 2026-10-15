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
		$this->assertSame( '$49.00', $samples['{form-payment:pay:amount}'] );
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

		// (1200 + 3) * 2, with the field's prefix.
		$this->assertSame( '1,200', $answers['price'] );
		$this->assertSame( '$2,406', $answers['total'] );

		$samples = Form_Templates::get_tag_samples( '{calculation-result}', [], $fields );
		$this->assertSame( '$2,406', $samples['{calculation-result}'] );
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
}
