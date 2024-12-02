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
     * Test validate_request_context with a single key-value pair that is valid.
     *
     * This test sets $_REQUEST to contain a single key-value pair and checks if
     * validate_request_context returns true when the key-value pair matches.
     */
    public function test_validate_request_context_single_key_value_pair_valid() {
        $_REQUEST = [
            'post_type' => 'post'
        ];

        $result = Helper::validate_request_context('post', 'post_type');
        $this->assertTrue($result);
    }

    /**
     * Test validate_request_context with a single key-value pair that is invalid.
     *
     * This test sets $_REQUEST to contain a single key-value pair and checks if
     * validate_request_context returns false when the key-value pair does not match.
     */
    public function test_validate_request_context_single_key_value_pair_invalid() {
        $_REQUEST = [
            'post_type' => 'page'
        ];

        $result = Helper::validate_request_context('post', 'post_type');
        $this->assertFalse($result);
    }

    /**
     * Test validate_request_context with multiple conditions that are valid.
     *
     * This test sets $_REQUEST to contain multiple key-value pairs and checks if
     * validate_request_context returns true when all conditions match.
     */
    public function test_validate_request_context_multiple_conditions_valid() {
        $_REQUEST = [
            'post_type' => 'post',
            'status' => 'publish'
        ];

        $conditions = [
            'post_type' => 'post',
            'status' => 'publish'
        ];

        $result = Helper::validate_request_context('', '', $conditions);
        $this->assertTrue($result);
    }

    /**
     * Test validate_request_context with multiple conditions that are invalid.
     *
     * This test sets $_REQUEST to contain multiple key-value pairs and checks if
     * validate_request_context returns false when not all conditions match.
     */
    public function test_validate_request_context_multiple_conditions_invalid() {
        $_REQUEST = [
            'post_type' => 'post',
            'status' => 'draft'
        ];

        $conditions = [
            'post_type' => 'post',
            'status' => 'publish'
        ];

        $result = Helper::validate_request_context('', '', $conditions);
        $this->assertFalse($result);
    }

    /**
     * Test validate_request_context with empty conditions.
     *
     * This test sets $_REQUEST to be empty and checks if validate_request_context
     * returns false when there are no conditions to match.
     */
    public function test_validate_request_context_empty_conditions() {
        $_REQUEST = [];

        $result = Helper::validate_request_context('post', 'post_type');
        $this->assertFalse($result);
    }
}
