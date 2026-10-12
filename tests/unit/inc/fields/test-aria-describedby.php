<?php
/**
 * Tests that server-rendered aria-describedby references always resolve.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Fields\Checkbox_Markup;
use SRFM\Inc\Fields\Dropdown_Markup;
use SRFM\Inc\Fields\Email_Markup;
use SRFM\Inc\Fields\GDPR_Markup;
use SRFM\Inc\Fields\Input_Markup;
use SRFM\Inc\Fields\Multichoice_Markup;
use SRFM\Inc\Fields\Number_Markup;
use SRFM\Inc\Fields\Phone_Markup;
use SRFM\Inc\Fields\Textarea_Markup;
use SRFM\Inc\Fields\Url_Markup;

class Test_Aria_Describedby extends TestCase {

	/**
	 * Field classes with the extra attributes each one needs to render.
	 *
	 * @return array<string, array{0: class-string, 1: array<string, mixed>}>
	 */
	public function field_provider() {
		return [
			'input'       => [ Input_Markup::class, [ 'textLength' => '', 'inputMask' => '' ] ],
			'email'       => [ Email_Markup::class, [ 'isConfirmEmail' => true, 'confirmLabel' => 'Confirm' ] ],
			'textarea'    => [ Textarea_Markup::class, [ 'maxLength' => '', 'rows' => '4', 'isRichText' => false ] ],
			'number'      => [ Number_Markup::class, [ 'minValue' => '', 'maxValue' => '', 'formatType' => '', 'prefix' => '', 'suffix' => '' ] ],
			'url'         => [ Url_Markup::class, [] ],
			'phone'       => [ Phone_Markup::class, [ 'autoCountry' => false, 'defaultCountry' => '' ] ],
			'checkbox'    => [ Checkbox_Markup::class, [] ],
			'gdpr'        => [ GDPR_Markup::class, [] ],
			'dropdown'    => [
				Dropdown_Markup::class,
				[
					'options'            => [ [ 'label' => 'A', 'icon' => '' ], [ 'label' => 'B', 'icon' => '' ] ],
					'multiSelect'        => false,
					'searchable'         => false,
					'preselectedOptions' => [],
				],
			],
			'multichoice' => [
				Multichoice_Markup::class,
				[
					'options'            => [ [ 'optionTitle' => 'A', 'icon' => '', 'image' => '' ], [ 'optionTitle' => 'B', 'icon' => '', 'image' => '' ] ],
					'singleSelection'    => true,
					'choiceWidth'        => '',
					'verticalLayout'     => false,
					'optionType'         => 'icon',
					'preselectedOptions' => [],
				],
			],
		];
	}

	/**
	 * Renders a field with the shared attributes plus the per-class extras.
	 *
	 * @param string               $class    Field class.
	 * @param array<string, mixed> $extra    Per-class attributes.
	 * @param bool                 $required Whether the field is required.
	 * @param string               $help     Help text.
	 * @return string
	 */
	private function render( $class, $extra, $required, $help ) {
		$attributes = array_merge(
			[
				'required'     => $required,
				'fieldWidth'   => '',
				'label'        => 'Field',
				'help'         => $help,
				'block_id'     => 'aria001',
				'formId'       => '1',
				'slug'         => 'field',
				'placeholder'  => '',
				'defaultValue' => '',
				'checked'      => false,
				'isUnique'     => false,
				'errorMsg'     => 'This field is required.',
				'duplicateMsg' => '',
				'readOnly'     => false,
			],
			$extra
		);

		return ( new $class( $attributes ) )->markup();
	}

	/**
	 * Asserts every aria-describedby token names an id present in the markup.
	 *
	 * @param string $markup Rendered field markup.
	 */
	private function assert_references_resolve( $markup ) {
		preg_match_all( '/\sid=["\']([^"\']+)["\']/', $markup, $ids );
		preg_match_all( '/aria-describedby=["\']([^"\']*)["\']/', $markup, $refs );

		$this->assertDoesNotMatchRegularExpression( '/aria-describedby=["\'][^"\']*srfm-error-/', $markup, 'The error id is linked by validation JS, not in server markup.' );

		foreach ( $refs[1] as $ref ) {
			foreach ( preg_split( '/\s+/', trim( $ref ), -1, PREG_SPLIT_NO_EMPTY ) as $token ) {
				$this->assertContains( $token, $ids[1], "aria-describedby references missing id \"{$token}\"." );
			}
		}
	}

	/**
	 * @dataProvider field_provider
	 */
	public function test_required_field_references_resolve( $class, $extra ) {
		$markup = $this->render( $class, $extra, true, '' );

		$this->assert_references_resolve( $markup );
		$this->assertStringContainsString( 'data-srfm-id="srfm-error-aria001"', $markup );
		$this->assertStringContainsString( 'data-srfm-error-target', $markup, 'Field control must be marked so validation JS can link the error message.' );
	}

	/**
	 * @dataProvider field_provider
	 */
	public function test_optional_field_references_resolve( $class, $extra ) {
		$this->assert_references_resolve( $this->render( $class, $extra, false, '' ) );
	}

	/**
	 * @dataProvider field_provider
	 */
	public function test_help_text_stays_linked( $class, $extra ) {
		$markup = $this->render( $class, $extra, true, 'Some help' );

		$this->assert_references_resolve( $markup );
		$this->assertMatchesRegularExpression( '/aria-describedby=["\'][^"\']*srfm-description-aria001/', $markup );
	}
}
