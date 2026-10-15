<?php
/**
 * Class Test_Catalog
 *
 * The field helpers the catalog files build templates with, and the catalog
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

		// Free: a text field on its own row. Pro: a future-only date picker sharing the row with a time.
		$this->assertSame( 'input', $date['fieldType'] );
		$this->assertArrayNotHasKey( 'blockAttributes', $date );
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
}
