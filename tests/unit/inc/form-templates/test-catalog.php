<?php
/**
 * Class Test_Catalog
 *
 * The prebuilt template catalog loader, its field helpers and the catalog
 * entries they shape.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Templates;
use SRFM\Inc\Form_Templates\Catalog;

class Test_Catalog extends TestCase {

	/**
	 * A catalog template by id, straight from the catalog files.
	 *
	 * @param string $id Template id.
	 * @return array<string, mixed>
	 */
	private function catalog_template( $id ) {
		$templates = array_column( Catalog::templates(), null, 'id' );
		$this->assertArrayHasKey( $id, $templates, "Template {$id} is missing." );
		return $templates[ $id ];
	}

	/**
	 * A template's field by slug.
	 *
	 * @param array<string, mixed> $template Template.
	 * @param string               $slug     Field slug.
	 * @return array<string, mixed>
	 */
	private function field( $template, $slug ) {
		$fields = array_column( $template['fields'], null, 'slug' );
		$this->assertArrayHasKey( $slug, $fields, "Field {$slug} is missing from {$template['id']}." );
		return $fields[ $slug ];
	}

	/**
	 * Runs a callback with the payment settings set to a currency, then restores it.
	 *
	 * @param string   $currency Currency code.
	 * @param callable $callback Callback.
	 */
	private function with_currency( $currency, $callback ) {
		$saved = \SRFM\Inc\Payments\Payment_Helper::get_currency();
		\SRFM\Inc\Payments\Payment_Helper::update_global_setting( 'currency', $currency );
		try {
			$callback();
		} finally {
			\SRFM\Inc\Payments\Payment_Helper::update_global_setting( 'currency', $saved );
		}
	}

	public function test_currency_symbol() {
		$this->with_currency( 'USD', fn() => $this->assertSame( '$', Catalog::currency_symbol() ) );
		$this->with_currency( 'EUR', fn() => $this->assertSame( '€', Catalog::currency_symbol() ) );
		// A code without a known symbol is shown as the code and a space.
		$this->with_currency( 'XYZ', fn() => $this->assertSame( 'XYZ ', Catalog::currency_symbol() ) );
	}

	public function test_money() {
		$this->with_currency( 'USD', fn() => $this->assertSame( '$1,500', Catalog::money( 1500 ) ) );
		$this->with_currency( 'EUR', fn() => $this->assertSame( '€25', Catalog::money( 25 ) ) );
	}

	public function test_thank_you() {
		$this->assertSame(
			Form_Templates::confirmation_message( 'Thanks!', 'We will be in touch.' ),
			Catalog::thank_you( 'Thanks!', 'We will be in touch.' )
		);

		$message = Catalog::thank_you( 'Thanks <em>Jane</em>', 'Soon & sure' );
		$this->assertStringContainsString( 'check-icon.svg', $message );
		$this->assertStringContainsString( 'Thanks &lt;em&gt;Jane&lt;/em&gt;', $message );
		$this->assertStringContainsString( 'Soon &amp; sure', $message );
	}

	public function test_attrs() {
		$field = Catalog::field( 'input', 'name', 'Name', true, [ 'blockAttributes' => [ 'minLength' => 2 ] ] );

		$result = Catalog::attrs( $field, [ 'maxLength' => 40 ] );

		$this->assertSame(
			[
				'minLength' => 2,
				'maxLength' => 40,
			],
			$result['blockAttributes'],
			'Existing attributes are kept.'
		);
		$this->assertSame( 'name', $result['slug'] );

		// A later value replaces an earlier one; a field without attributes gets them.
		$this->assertSame( [ 'minLength' => 5 ], Catalog::attrs( $field, [ 'minLength' => 5 ] )['blockAttributes'] );
		$this->assertSame( [ 'fieldWidth' => 50 ], Catalog::attrs( Catalog::field( 'email', 'email', 'Email' ), [ 'fieldWidth' => 50 ] )['blockAttributes'] );
	}

	public function test_attrs_set_the_width_of_the_pro_appointment_date() {
		$date = $this->field( $this->catalog_template( 'appointment-request' ), 'preferred-date' );

		// Free: a text field beside the service. Pro: a future-only date picker sharing the row with a time.
		$this->assertSame( 'input', $date['fieldType'] );
		$this->assertSame( [ 'fieldWidth' => 50 ], $date['blockAttributes'] );
		$this->assertSame( 'date-picker', $date['pro']['fieldType'] );
		$this->assertSame(
			[
				'disablePastDates' => true,
				'fieldWidth'       => 50,
			],
			$date['pro']['blockAttributes']
		);
	}

	public function test_quiz_questions_mark_exactly_one_answer_correct() {
		foreach ( Catalog::templates() as $template ) {
			if ( empty( $template['quiz'] ) ) {
				continue;
			}
			$advanced = 'advanced' === ( $template['quiz']['scoreType'] ?? '' );
			foreach ( $template['fields'] as $field ) {
				if ( 'multi-choice' !== $field['fieldType'] || ! isset( $field['fieldOptions'][0]['isCorrect'] ) ) {
					continue;
				}
				$options = $field['fieldOptions'];
				$this->assertSame( 1, count( array_filter( array_column( $options, 'isCorrect' ) ) ), "{$template['id']}: {$field['slug']} needs one right answer." );
				foreach ( $options as $index => $option ) {
					$this->assertNotEmpty( $option['optionTitle'], "{$template['id']}: {$field['slug']} lost its option titles." );
					if ( $advanced && isset( $option['individualScore'] ) ) {
						$this->assertSame( $index, $option['individualScore'], "{$template['id']}: {$field['slug']} scores answers 0, 1, 2, …" );
						$this->assertSame( count( $options ) - 1 === $index, $option['isCorrect'], "{$template['id']}: the best answer is the correct one." );
					}
				}
			}
		}
	}

	public function test_templates_load_every_file_in_display_order() {
		$ids = wp_list_pluck( Catalog::templates(), 'id' );

		$this->assertSame( Catalog::ORDER, array_slice( $ids, 0, count( Catalog::ORDER ) ), 'Listed templates come first, in ORDER.' );
		$this->assertSame( $ids, array_unique( $ids ) );

		// Each catalog file holds the templates of one category (a file may still be empty).
		$categories = array_unique( wp_list_pluck( Catalog::templates(), 'category' ) );
		$this->assertSame( [], array_values( array_diff( $categories, Catalog::FILES ) ) );
	}

	public function test_field_fills_the_schema_keys_and_keeps_extras() {
		$this->assertSame(
			[
				'fieldType'   => 'email',
				'slug'        => 'work-email',
				'label'       => 'Work Email',
				'required'    => true,
				'placeholder' => 'you@example.com',
			],
			Catalog::field( 'email', 'work-email', 'Work Email', true, [ 'placeholder' => 'you@example.com' ] )
		);
		$this->assertFalse( Catalog::field( 'input', 'name', 'Name' )['required'], 'Fields are optional by default.' );
	}

	public function test_dropdown_options_wraps_each_label() {
		$this->assertSame(
			[
				'fieldOptions' => [
					[ 'label' => 'Yes' ],
					[ 'label' => 'No' ],
				],
			],
			Catalog::dropdown_options( [ 'Yes', 'No' ] )
		);
	}

	public function test_choice_options_pairs_labels_with_icons() {
		$options = Catalog::choice_options( [ 'Email', 'Phone' ], false, [ 'envelope' ] );

		$this->assertFalse( $options['singleSelection'] );
		$this->assertSame(
			[
				[
					'optionTitle' => 'Email',
					'icon'        => 'envelope',
				],
				[
					'optionTitle' => 'Phone',
					'icon'        => '',
				],
			],
			$options['fieldOptions']
		);
		$this->assertTrue( Catalog::choice_options( [ 'A' ] )['singleSelection'], 'Single selection by default.' );
	}

	public function test_width_sets_the_field_width_attribute() {
		$field = Catalog::width( Catalog::field( 'input', 'city', 'City' ), 50 );

		$this->assertSame( [ 'fieldWidth' => 50 ], $field['blockAttributes'] );
	}

	public function test_attrs_merges_with_the_attributes_a_field_has() {
		$field = Catalog::attrs( Catalog::width( Catalog::field( 'number', 'age', 'Age' ), 50 ), [ 'minValue' => 18 ] );

		$this->assertSame(
			[
				'fieldWidth' => 50,
				'minValue'   => 18,
			],
			$field['blockAttributes']
		);
		$this->assertSame( 25, Catalog::attrs( $field, [ 'fieldWidth' => 25 ] )['blockAttributes']['fieldWidth'], 'A new value replaces the old one.' );
	}

	public function test_common_fields_share_slugs_and_the_site_country() {
		$fields = Catalog::common_fields();

		$this->assertSame( [ 'name', 'first_name', 'last_name', 'email', 'phone', 'consent' ], array_keys( $fields ) );
		$this->assertSame( 'email', $fields['email']['fieldType'] );
		$this->assertSame( 'gdpr', $fields['consent']['fieldType'] );
		$this->assertTrue( $fields['consent']['required'] );
		$this->assertSame( 50, $fields['first_name']['blockAttributes']['fieldWidth'] );
		// en_US gives `us`; a language without a region gives no default.
		$this->assertSame( 'us', $fields['phone']['blockAttributes']['defaultCountry'] );

		$locale = static fn() => 'fr';
		add_filter( 'locale', $locale );
		$this->assertSame( '', Catalog::common_fields()['phone']['blockAttributes']['defaultCountry'] );
		remove_filter( 'locale', $locale );
	}

	public function test_date_field_is_text_without_pro_and_a_date_picker_with_it() {
		$field = Catalog::date_field( 'arrival', 'Arrival', true, true );

		$this->assertSame( 'input', $field['fieldType'] );
		$this->assertTrue( $field['required'] );
		$this->assertSame( 'date-picker', $field['pro']['fieldType'] );
		$this->assertTrue( $field['pro']['blockAttributes']['disablePastDates'], 'Bookings only allow dates from today on.' );
		$this->assertFalse( Catalog::date_field( 'born', 'Born' )['pro']['blockAttributes']['disablePastDates'] );
	}

	public function test_step_is_a_pro_only_page_break() {
		$step = Catalog::step( 'details', 'Your details' );

		$this->assertSame( 'page-break', $step['fieldType'] );
		$this->assertSame( 'Your details', $step['label'] );
		$this->assertTrue( $step['pro_only'] );
	}

	public function test_number_field_carries_its_range() {
		$field = Catalog::number_field( 'guests', 'Guests', true, 1, 20, [ 'placeholder' => '2' ] );

		$this->assertSame( 'number', $field['fieldType'] );
		$this->assertSame( '2', $field['placeholder'] );
		$this->assertSame(
			[
				'minValue' => 1,
				'maxValue' => 20,
			],
			$field['blockAttributes']
		);
	}
}
