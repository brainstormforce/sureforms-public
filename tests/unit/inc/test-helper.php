<?php
/**
 * Class Test_Helper
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Inc\Helper;

/**
 * Tests Plugin Initialization.
 *
 */
class Test_Helper extends TestCase {
    /**
     * Test if get_field_label_from_key is converting field key to label properly.
     */
    public function test_get_field_label_from_key() {
        $field_key = 'srfm-input-fe439fd2-lbl-RnVsbCBOYW1l-full-name';

        $result = Helper::get_field_label_from_key( $field_key );

        $this->assertEquals( 'Full Name', $result );
    }

	/**
	 * Test get_common_err_msg returns expected array structure
	 */
	public function test_get_common_err_msg_returns_expected_structure() {
		$result = Helper::get_common_err_msg();

		// Test that result is an array
		$this->assertIsArray($result);

		// Test that array has exactly the expected keys
		$this->assertSame(
			['required', 'unique'],
			array_keys($result)
		);

		// Test that values are strings
		$this->assertIsString($result['required']);
		$this->assertIsString($result['unique']);
	}

	/**
	 * Test get_common_err_msg returns translated strings
	 */
	public function test_get_common_err_msg_returns_translated_strings() {
		// Store current locale
		$current_locale = get_locale();

		// Set locale to English to test base strings
		switch_to_locale('en_US');

		$result = Helper::get_common_err_msg();

		// Test English strings
		$this->assertSame('This field is required.', $result['required']);
		$this->assertSame('Value needs to be unique.', $result['unique']);

		// Restore original locale
		switch_to_locale($current_locale);
	}

	/**
	 * Test get_common_err_msg translations work
	 */
	public function test_get_common_err_msg_translations() {
		// Store current locale
		$current_locale = get_locale();

		// Test Spanish translation if available
		switch_to_locale('es_ES');

		// Register translations
		add_filter('gettext', function($translation, $text, $domain) {
			if ($domain !== 'sureforms') {
				return $translation;
			}

			$translations = [
				'This field is required.' => 'Este campo es obligatorio.',
				'Value needs to be unique.' => 'El valor debe ser único.',
			];

			return isset($translations[$text]) ? $translations[$text] : $translation;
		}, 10, 3);

		$result = Helper::get_common_err_msg();

		// Test Spanish strings (if translations are loaded)
		$this->assertNotSame('This field is required.', $result['required']);
		$this->assertNotSame('Value needs to be unique.', $result['unique']);

		// Restore original locale
		switch_to_locale($current_locale);
	}

	/**
     * Test scalar values are converted to strings
     *
     * @dataProvider provideScalarValues
     */
    public function test_get_string_value_scalar($input, $expected) {
        $result = Helper::get_string_value($input);
        $this->assertSame($expected, $result);
        $this->assertIsString($result);
    }

    /**
     * Data provider for scalar values
     */
    public function provideScalarValues() {
        return [
            'integer' => [42, '42'],
            'float' => [3.14, '3.14'],
            'string' => ['hello', 'hello'],
            'boolean true' => [true, '1'],
            'boolean false' => [false, ''],
        ];
    }

    /**
     * Test object with __toString method
     */
    public function test_get_string_value_object_with_to_string() {
        $obj = new class() {
            public function __toString() {
                return 'converted string';
            }
        };

        $result = Helper::get_string_value($obj);
        $this->assertSame('converted string', $result);
    }

    /**
     * Test object without __toString method
     */
    public function test_get_string_value_object_without_to_string() {
        $obj = new class() {};

        $result = Helper::get_string_value($obj);
        $this->assertSame('', $result);
    }

    /**
     * Test null value
     */
    public function test_get_string_value_null() {
        $result = Helper::get_string_value(null);
        $this->assertSame('', $result);
    }

    /**
     * Test array value
     */
    public function test_get_string_value_array() {
        $result = Helper::get_string_value(['test']);
        $this->assertSame('', $result);
    }

    /**
     * Test resource value
     */
    public function test_get_string_value_resource() {
        $handle = fopen('php://memory', 'r');
        $result = Helper::get_string_value($handle);
        $this->assertSame('', $result);
        fclose($handle);
    }

    /**
     * Test empty values
     *
     * @dataProvider provideEmptyValues
     */
    public function test_get_string_value_empty_values($input) {
        $result = Helper::get_string_value($input);
        $this->assertSame('', $result);
    }

    /**
     * Data provider for empty values
     */
    public function provideEmptyValues() {
        return [
            'empty array' => [[]],
            'null' => [null],
            'empty string' => [''],
            'false' => [false],
        ];
    }

	/**
     * Test sanitize_recursively with various input scenarios
     *
     * @dataProvider provideSanitizeTestCases
     */
    public function test_sanitize_recursively($function, $input, $expected, $message = '') {
        $result = Helper::sanitize_recursively($function, $input);
        $this->assertSame($expected, $result, $message);
    }

    /**
     * Data provider for sanitize_recursively tests
     */
    public function provideSanitizeTestCases() {
        return [
            'simple array' => [
                'trim',
                ['  test  ', ' hello '],
                ['test', 'hello'],
                'Should trim all strings in array'
            ],

            'nested array' => [
                'trim',
                ['level1' => ['  nested  ', '  value  ']],
                ['level1' => ['nested', 'value']],
                'Should handle nested arrays'
            ],

            'deeply nested array' => [
                'trim',
                ['l1' => ['l2' => ['l3' => '  deep  ']]],
                ['l1' => ['l2' => ['l3' => 'deep']]],
                'Should handle deeply nested arrays'
            ],

            'mixed content array' => [
                'absint',
                ['1', '2.5', '-3', '0'],
                [1, 2, 3, 0],
                'Should convert strings to integers'
            ],

            'empty array' => [
                'trim',
                [],
                [],
                'Should handle empty arrays'
            ],

            'non-callable function' => [
                'non_existent_function',
                ['test'],
                ['test'],
                'Should return original array when function is not callable'
            ],

            'custom sanitization' => [
                function($value) { return strtoupper($value); },
                ['hello', 'world'],
                ['HELLO', 'WORLD'],
                'Should work with custom callbacks'
            ]
        ];
    }

    /**
     * Test with non-array input
     */
    public function test_sanitize_recursively_non_array() {
        $result = Helper::sanitize_recursively('trim', 'not an array');
        $this->assertSame([], $result, 'Should return empty array for non-array input');
    }

    /**
     * Test with null input
     */
    public function test_sanitize_recursively_null() {
        $result = Helper::sanitize_recursively('trim', null);
        $this->assertSame([], $result, 'Should return empty array for null input');
    }

	 /**
     * Test encryption with various inputs
     *
     * @dataProvider provideEncryptTestCases
     */
    public function test_encrypt($input, $expected, $message = '') {
        $result = Helper::encrypt($input);
        $this->assertSame($expected, $result, $message);
    }

    /**
     * Data provider for encryption tests
     */
    public function provideEncryptTestCases() {
        return [
            'simple string' => [
                'hello',
                'aGVsbG8',
                'Should correctly encode simple string'
            ],

            'string with special chars' => [
                'hello@world!123',
                'aGVsbG9Ad29ybGQhMTIz',
                'Should handle special characters'
            ],

            'empty string' => [
                '',
                '',
                'Should return empty string for empty input'
            ],

            'string with padding chars' => [
                'test==',
                'dGVzdD09',
                'Should handle strings containing pad characters'
            ],

            'unicode string' => [
                'héllo wörld',
                'aMOpbGxvIHfDtnJsZA',
                'Should handle unicode characters'
            ],
        ];
    }

    /**
     * Test invalid input types
     *
     * @dataProvider provideInvalidInputs
     */
    public function test_encrypt_invalid_inputs($input) {
        $result = Helper::encrypt($input);
        $this->assertSame('', $result, 'Should return empty string for invalid input');
    }

    /**
     * Data provider for invalid inputs
     */
    public function provideInvalidInputs() {
        return [
            'null' => [null],
            'integer' => [42],
            'float' => [3.14],
            'boolean' => [true],
            'array' => [['test']],
            'object' => [new stdClass()],
        ];
    }

    /**
    * Test validate_request_context method.
    *
    * This method tests various scenarios for the validate_request_context function, 
    * including single key-value pair validations and multiple condition validations. 
    * It covers the following cases:
    * 
    * - A single key-value pair that matches (valid).
    * - A single key-value pair that does not match (invalid).
    * - Multiple conditions where all conditions match (valid).
    * - Multiple conditions where at least one condition does not match (invalid).
    * - Empty conditions with no matching request values.
    * 
    * Each case ensures that the function behaves as expected in different scenarios
    * by asserting the returned boolean value.
    *
    * @return void
    */
    public function test_validate_request_context() {
        // Case 1: Single key-value pair that is valid.
        $_REQUEST = [
            'post_type' => 'post'
        ];
        $result = Helper::validate_request_context('post', 'post_type');
        $this->assertTrue($result, 'Failed: Single key-value pair valid case');

        // Case 2: Single key-value pair that is invalid.
        $_REQUEST = [
            'post_type' => 'page'
        ];
        $result = Helper::validate_request_context('post', 'post_type');
        $this->assertFalse($result, 'Failed: Single key-value pair invalid case');

        // Case 3: Multiple conditions that are valid.
        $_REQUEST = [
            'post_type' => 'post',
            'status' => 'publish'
        ];
        $conditions = [
            'post_type' => 'post',
            'status' => 'publish'
        ];
        $result = Helper::validate_request_context('', '', $conditions);
        $this->assertTrue($result, 'Failed: Multiple conditions valid case');

        // Case 4: Multiple conditions that are invalid.
        $_REQUEST = [
            'post_type' => 'post',
            'status' => 'draft'
        ];
        $conditions = [
            'post_type' => 'post',
            'status' => 'publish'
        ];
        $result = Helper::validate_request_context('', '', $conditions);
        $this->assertFalse($result, 'Failed: Multiple conditions invalid case');

        // Case 5: Empty conditions with no matching request values.
        $_REQUEST = [];
        $result = Helper::validate_request_context('post', 'post_type');
        $this->assertFalse($result, 'Failed: Empty conditions case');
    }

    /**
     * Test that the function returns the default excluded fields.
     */
    public function test_get_excluded_fields_default() {
        $expected = [ 'srfm-honeypot-field', 'g-recaptcha-response', 'srfm-sender-email-field', 'form-id' ];
        $result = Helper::get_excluded_fields();

        // Test that the result is an array.
        $this->assertIsArray( $result );

        // Test that the result is not empty.
        $this->assertNotEmpty( $result );

        // Test that the result is the expected value.
        $this->assertSame( $expected, $result );
    }

    /**
     * Test that the function returns the default excluded fields with additional fields.
     */
    public function test_is_valid_css_class_name()
    {
        // Valid class names
        $valid_class_names = [
            'my-class',
            'my_class',
            'class123',
            '名字123',        // Unicode characters
            'valid-name',
            'a',              // Single valid character
        ];

        foreach ($valid_class_names as $class_name) {
            $this->assertTrue(
                Helper::is_valid_css_class_name($class_name),
                "Expected '$class_name' to be a valid CSS class name."
            );
        }

        // Invalid class names
        $invalid_class_names = [
            '123class',       // Starts with a digit
            '-invalid-class', // Starts with a hyphen
            '_invalid',       // Starts with an underscore
            '',               // Empty string
        ];

        foreach ($invalid_class_names as $class_name) {
            $this->assertFalse(
                Helper::is_valid_css_class_name($class_name),
                "Expected '$class_name' to be an invalid CSS class name."
            );
        }
    }

    /**
     * Test the join_strings method with various inputs.
     */
    public function testJoinStrings() {
        $testCases = [
            'normal strings' => [
                'input' => ['class1', 'class2', 'class3'],
                'expected' => 'class1 class2 class3'
            ],
            'empty strings' => [
                'input' => ['class1', '', 'class2'],
                'expected' => 'class1 class2'
            ],
            'false values' => [
                'input' => ['class1', false, 'class2'],
                'expected' => 'class1 class2'
            ],
            'null values' => [
                'input' => ['class1', null, 'class2'],
                'expected' => 'class1 class2'
            ],
            'numeric values' => [
                'input' => ['class1', 123, 'class2'],
                'expected' => 'class1 class2'
            ],
            'mixed valid and invalid' => [
                'input' => ['class1', '', false, null, 'class2', 0, 'class3'],
                'expected' => 'class1 class2 class3'
            ]
        ];

        // Iterate through test cases and assert results
        foreach ($testCases as $description => $testCase) {
            $this->assertEquals(
                $testCase['expected'],
                Helper::join_strings($testCase['input']),
                "Failed asserting for case: {$description}"
            );
        }
    }

    /**
     * Test the get_gradient_css function with various inputs.
     */
    public function testGetGradientCss() {
        $testCases = [
            'default values' => [
                'input' => [],
                'expected' => 'linear-gradient(90deg, #FFC9B2 0%, #C7CBFF 100%)'
            ],
            'custom linear gradient' => [
                'input' => ['linear', '#FF0000', '#00FF00', 10, 90, 45],
                'expected' => 'linear-gradient(45deg, #FF0000 10%, #00FF00 90%)'
            ],
            'custom radial gradient' => [
                'input' => ['radial', '#000000', '#FFFFFF', 20, 80],
                'expected' => 'radial-gradient(#000000 20%, #FFFFFF 80%)'
            ],
            'linear with zero values' => [
                'input' => ['linear', '#123456', '#654321', 0, 0, 0],
                'expected' => 'linear-gradient(0deg, #123456 0%, #654321 0%)'
            ],
            'radial with zero values' => [
                'input' => ['radial', '#ABCDEF', '#FEDCBA', 0, 0],
                'expected' => 'radial-gradient(#ABCDEF 0%, #FEDCBA 0%)'
            ],
        ];

        // Iterate through test cases and assert results.
        foreach ($testCases as $description => $testCase) {
            $this->assertEquals(
                $testCase['expected'],
                Helper::get_gradient_css(...$testCase['input']),
                "Failed asserting for case: {$description}"
            );
        }
    }

    /**
     * Test the get_background_classes method with various inputs.
     */
    public function testGetBackgroundClasses() {
        $testCases = [
            'default background type' => [
                'input' => ['', '', ''],
                'expected' => 'srfm-bg-color'
            ],
            'background type: color, overlay: image (should ignore overlay)' => [
                'input' => ['color', 'image', ''],
                'expected' => 'srfm-bg-color'
            ],
            'background type: image, overlay: color (no bg image)' => [
                'input' => ['image', 'color', ''],
                'expected' => 'srfm-bg-image'
            ],
            'background type: image, overlay: color (with bg image)' => [
                'input' => ['image', 'color', 'example.jpg'],
                'expected' => 'srfm-bg-image srfm-overlay-color'
            ],
            'background type: image, overlay: gradient (no bg image)' => [
                'input' => ['image', 'gradient', ''],
                'expected' => 'srfm-bg-image'
            ],
            'background type: image, overlay: gradient (with bg image)' => [
                'input' => ['image', 'gradient', 'example.jpg'],
                'expected' => 'srfm-bg-image srfm-overlay-gradient'
            ],
            'background type: gradient, overlay: image (should ignore overlay)' => [
                'input' => ['gradient', 'image', ''],
                'expected' => 'srfm-bg-gradient'
            ],
            'background type: empty, overlay: image (should ignore overlay)' => [
                'input' => ['', 'image', ''],
                'expected' => 'srfm-bg-color'
            ],
            'background type: image, overlay: none (with bg image)' => [
                'input' => ['image', '', 'example.jpg'],
                'expected' => 'srfm-bg-image'
            ],
            'background type: image, overlay: none (no bg image)' => [
                'input' => ['image', '', ''],
                'expected' => 'srfm-bg-image'
            ],
        ];

        // Iterate through test cases and assert results.
        foreach ($testCases as $description => $testCase) {
            $this->assertEquals(
                $testCase['expected'],
                Helper::get_background_classes(...$testCase['input']),
                "Failed asserting for case: {$description}"
            );
        }
    }
}
