<?php
/**
 * Class Test_Catalog
 *
 * The prebuilt template catalog loader and its field helpers.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Templates\Catalog;

class Test_Catalog extends TestCase {

	public function test_templates_load_every_file_in_display_order() {
		$ids = wp_list_pluck( Catalog::templates(), 'id' );

		$this->assertSame( Catalog::ORDER, array_slice( $ids, 0, count( Catalog::ORDER ) ), 'Listed templates come first, in ORDER.' );
		$this->assertSame( $ids, array_unique( $ids ) );

		$categories = array_unique( wp_list_pluck( Catalog::templates(), 'category' ) );
		sort( $categories );
		$files = Catalog::FILES;
		sort( $files );
		$this->assertSame( $files, $categories, 'Each catalog file holds the templates of one category.' );
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
