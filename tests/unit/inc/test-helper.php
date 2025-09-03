<?php
/**
 * Class Test_Helper
 *
 * @package sureforms
 */

namespace SRFM\Inc;

use stdClass; // Fixes "Class not found" error

// Override get_plugins in the same namespace.
function get_plugins() {
    return \SRFM\Inc\Test_Helper::$mock_plugins;
}

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Inc\Helper;

/**
 * Tests Plugin Initialization.
 *
 */
class Test_Helper extends TestCase {

    public static $mock_plugins = [];

    /**
     * Test if get_field_label_from_key is converting field key to label properly.
     */
    public function test_get_field_label_from_key() {
        $field_key = 'srfm-input-fe439fd2-lbl-RnVsbCBOYW1l-full-name';

        $result = Helper::get_field_label_from_key( $field_key );

        $this->assertEquals( 'Full Name', $result );
    }

    /**
     * Test if get_block_id_from_key is converting field key to block id properly.
     */
    public function test_get_block_id_from_key() {
        $testCases = [
            'simple input key' => [
                'input' => 'srfm-input-fe439fd2-lbl-RnVsbCBOYW1l-full-name',
                'expected' => 'fe439fd2'
            ],
            'input key with multi word slug' => [
                'input' => 'srfm-input-multi-choice-3ccec323-lbl-TXVsdGkgQ2hvaWNl-srfm-multi-choice',
                'expected' => '3ccec323'
            ],
            'invalid input key' => [
                'input' => 'srfm-input-invalid-key',
                'expected' => ''
            ],
            'input with empty key' => [
                'input' => '',
                'expected' => ''
            ],
        ];

        // Iterate through test cases and assert results.
        foreach ($testCases as $description => $testCase) {
            $this->assertEquals(
                $testCase['expected'],
                Helper::get_block_id_from_key($testCase['input']),
                "Failed asserting for case: {$description}"
            );
        }
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
     * Test the check_starter_template_plugin method with mock plugin data.
     */
    public function test_check_starter_template_plugin() {
        // Case 1: Only premium plugin available
        self::$mock_plugins = [
            'astra-pro-sites/astra-pro-sites.php' => [ 'Name' => 'Starter Templates Pro' ],
        ];
        $this->assertEquals(
            'astra-pro-sites/astra-pro-sites.php',
            Helper::check_starter_template_plugin(),
            'Failed when premium plugin is available'
        );

        // Case 2: Only free plugin available
        self::$mock_plugins = [
            'astra-sites/astra-sites.php' => [ 'Name' => 'Starter Templates' ],
        ];
        $this->assertEquals(
            'astra-sites/astra-sites.php',
            Helper::check_starter_template_plugin(),
            'Failed when only free plugin is available'
        );

        // Case 3: Both plugins available (prefer premium)
        self::$mock_plugins = [
            'astra-pro-sites/astra-pro-sites.php' => [ 'Name' => 'Starter Templates Pro' ],
            'astra-sites/astra-sites.php' => [ 'Name' => 'Starter Templates' ],
        ];
        $this->assertEquals(
            'astra-pro-sites/astra-pro-sites.php',
            Helper::check_starter_template_plugin(),
            'Failed when both plugins are available (should prefer premium)'
        );

        // Case 4: Neither plugin available
        self::$mock_plugins = [
            'hello-dolly/hello.php' => [],
        ];
        $this->assertEquals(
            'astra-sites/astra-sites.php',
            Helper::check_starter_template_plugin(),
            'Failed when no starter template plugin is found'
        );
    }

    /**
     * Test the join_strings method with various inputs.
     */
    public function test_join_strings() {
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
    public function test_get_gradient_css() {
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
    public function test_get_background_classes() {
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

    /**
     * Test the sanitize_textarea method with various inputs.
     */
    public function test_sanitize_textarea() {
        $testCases = [
            'plain text' => [
                'input'    => 'Hello, World!',
                'expected' => 'Hello, World!',
            ],
            'simple HTML tags' => [
                'input'    => '<p>Hello, <strong>World!</strong></p>',
                'expected' => '<p>Hello, <strong>World!</strong></p>',
            ],
            'special characters with newlines' => [
                'input'    => "Hello, World!\nThis is a test.",
                'expected' => "Hello, World!\nThis is a test.",
            ],
            'disallowed tags (script)' => [
                'input'    => 'Hello<script>alert("Hack");</script>World!',
                'expected' => 'HelloWorld!',
            ],
            'rich text with style' => [
                'input'    => '<h1><span style="color: rgb(230, 0, 0);">Rich text content</span></h1>',
                'expected' => '<h1><span style="color: rgb(230, 0, 0);">Rich text content</span></h1>',
            ],
            'empty input' => [
                'input'    => '',
                'expected' => '',
            ],
        ];


        // Iterate through test cases and assert results.
        foreach ($testCases as $description => $testCase) {
            $this->assertEquals(
                $testCase['expected'],
                Helper::sanitize_textarea($testCase['input']),
                "Failed asserting for case: {$description}"
            );
        }
    }

    /**
     * Test the esc_textarea method with various inputs.
     */
    public function test_esc_textarea() {
        $testCases = [
            'plain text' => [
                'input'    => 'Hello, World!',
                'expected' => '<p>Hello, World!</p>',
            ],
            'special characters with newlines' => [
                'input'    => "Hello, World!\nThis is a test.",
                'expected' => "<p>Hello, World!<br />This is a test.</p>",
            ],
            'empty input' => [
                'input'    => '',
                'expected' => '',
            ],
        ];


        // Iterate through test cases and assert results.
        foreach ($testCases as $description => $testCase) {
            $this->assertEquals(
                $testCase['expected'],
                Helper::esc_textarea($testCase['input']),
                "Failed asserting for case: {$description}"
            );
        }
    }
    /**
     * Test the strip_js_attributes method with various inputs.
     */
    public function test_strip_js_attributes() {
        $testCases = [
            'removes script tag' => [
            'input' => '<div>Hello<script>alert("x")</script>World</div>',
            'expected' => '<body><div>HelloWorld</div></body>',
            ],
            'removes onclick attribute' => [
                'input' => '<button onclick="doSomething()">Click</button>',
                'expected' => '<body><button>Click</button></body>',
            ],
            'multiple on* attributes removed' => [
                'input' => '<div onmouseover="x" onload="y">Text</div>',
                'expected' => '<body><div>Text</div></body>',
            ],
            'preserves safe attributes' => [
                'input' => '<img src="test.jpg" alt="image">',
                'expected' => '<body><img src="test.jpg" alt="image"></body>',
            ],
            'mixed safe and unsafe attributes' => [
                'input' => '<a href="#" onclick="bad()">Link</a>',
                'expected' => '<body><a href="#">Link</a></body>',
            ],
            'nested script and event attributes' => [
                'input' => '<div><script>alert(1)</script><span onclick="x()">Test</span></div>',
                'expected' => '<body><div><span>Test</span></div></body>',
            ],
            'invalid html gracefully handled' => [
                'input' => '<div><button onclick="bad()">Click',
                'expected' => '<body><div><button>Click</button></div></body>',
            ],
            'image tag with onerror attribute' => [
                'input' => '<img src=x onerror=alert(1)>',
                'expected' => '<body><img src="x"></body>',
            ],
        ];
        // Iterate through test cases and assert results.
        foreach ($testCases as $description => $testCase) {
            $this->assertSame(
                $testCase['expected'],
                Helper::strip_js_attributes($testCase['input']),
                "Failed asserting for case: {$description}"
            );
        }
    }

    /**
     * Test the has_pro method to check if SureForms Pro plugin is installed.
     */
    public function test_has_pro() {
        // Case 1: When SRFM_PRO_VER is not defined (should return false)
        if (defined('SRFM_PRO_VER')) {
            // If constant is already defined, we need to test differently
            $this->assertTrue(
                Helper::has_pro(),
                'Failed: has_pro should return true when SRFM_PRO_VER is defined'
            );
        } else {
            $this->assertFalse(
                Helper::has_pro(),
                'Failed: has_pro should return false when SRFM_PRO_VER is not defined'
            );

            // Case 2: Define the constant and test again
            define('SRFM_PRO_VER', '1.0.0');
            $this->assertTrue(
                Helper::has_pro(),
                'Failed: has_pro should return true when SRFM_PRO_VER is defined'
            );
        }
    }

    /**
     * Mock data for SMTP detection tests
     */
    public static $mock_active_plugins = [];
    public static $mock_network_plugins = [];
    public static $mock_is_multisite = false;

    /**
     * Reset SMTP test data
     */
    public function reset_smtp_test_data() {
        self::$mock_active_plugins = [];
        self::$mock_network_plugins = [];
        self::$mock_is_multisite = false;
    }

    /**
     * Helper function to set up SMTP plugin mocks
     *
     * @param array $active_plugins Array of active plugins to mock
     * @param array $network_plugins Array of network plugins to mock (for multisite)
     * @param bool  $is_multisite Whether to simulate multisite environment
     */
    public function setup_smtp_plugin_mocks( $active_plugins = [], $network_plugins = [], $is_multisite = false ) {
        // Clear any existing filters
        remove_all_filters('pre_option_active_plugins');
        remove_all_filters('pre_site_option_active_sitewide_plugins');
        remove_all_filters('pre_option_is_multisite');

        // Set up new filters
        add_filter('pre_option_active_plugins', function() use ($active_plugins) {
            return $active_plugins;
        });

        add_filter('pre_site_option_active_sitewide_plugins', function() use ($network_plugins) {
            return $network_plugins;
        });

        if ($is_multisite) {
            add_filter('pre_option_is_multisite', '__return_true');
        } else {
            add_filter('pre_option_is_multisite', '__return_false');
        }
    }

    /**
     * Helper function to set up multisite SMTP plugin mocks with time-based values
     *
     * @param array $active_plugins Array of site-level active plugins
     * @param array $network_plugins Array of network-activated plugins (without time values)
     */
    public function setup_multisite_smtp_mocks( $active_plugins = [], $network_plugins = [] ) {
        // Convert network plugins array to time-based format
        $network_with_time = [];
        foreach ($network_plugins as $plugin) {
            $network_with_time[$plugin] = time();
        }

        $this->setup_smtp_plugin_mocks($active_plugins, $network_with_time, true);
    }

    /**
     * Test is_any_smtp_plugin_active - should return false when no plugins are active
     */
    public function test_is_any_smtp_plugin_active_no_plugins() {
        $this->reset_smtp_test_data();
        $this->setup_smtp_plugin_mocks();

        $result = Helper::is_any_smtp_plugin_active();

        $this->assertFalse($result);
    }

    /**
     * Test is_any_smtp_plugin_active - should return true when WP Mail SMTP is active
     */
    public function test_is_any_smtp_plugin_active_wp_mail_smtp() {
        $this->reset_smtp_test_data();
        $this->setup_smtp_plugin_mocks([
            'wp-mail-smtp/wp_mail_smtp.php',
            'akismet/akismet.php'
        ]);

        $result = Helper::is_any_smtp_plugin_active();

        $this->assertTrue($result);
    }

    /**
     * Test is_any_smtp_plugin_active - should return false with old incorrect WP Mail SMTP path
     */
    public function test_is_any_smtp_plugin_active_old_wp_mail_smtp_path() {
        $this->reset_smtp_test_data();
        $this->setup_smtp_plugin_mocks([
            'wp-mail-smtp/wp-mail-smtp.php', // Old incorrect path with hyphens
            'akismet/akismet.php'
        ]);

        $result = Helper::is_any_smtp_plugin_active();

        $this->assertFalse($result);
    }

    /**
     * Test is_any_smtp_plugin_active - should return true when Newsletter plugin is active
     */
    public function test_is_any_smtp_plugin_active_newsletter() {
        $this->reset_smtp_test_data();
        $this->setup_smtp_plugin_mocks([
            'newsletter/plugin.php', // Correct file path is plugin.php not newsletter.php
            'akismet/akismet.php'
        ]);

        $result = Helper::is_any_smtp_plugin_active();

        $this->assertTrue($result);
    }

    /**
     * Test is_any_smtp_plugin_active - should return false with old incorrect Newsletter path
     */
    public function test_is_any_smtp_plugin_active_old_newsletter_path() {
        $this->reset_smtp_test_data();
        $this->setup_smtp_plugin_mocks([
            'newsletter/newsletter.php', // Old incorrect path
            'akismet/akismet.php'
        ]);

        $result = Helper::is_any_smtp_plugin_active();

        $this->assertFalse($result);
    }

    /**
     * Test is_any_smtp_plugin_active - should return true when SureMails is active
     */
    public function test_is_any_smtp_plugin_active_suremails() {
        $this->reset_smtp_test_data();
        $this->setup_smtp_plugin_mocks([
            'suremails/suremails.php',
            'akismet/akismet.php'
        ]);

        $result = Helper::is_any_smtp_plugin_active();

        $this->assertTrue($result);
    }

    /**
     * Test is_any_smtp_plugin_active - should return true when Site Mailer is active
     */
    public function test_is_any_smtp_plugin_active_site_mailer() {
        $this->reset_smtp_test_data();
        $this->setup_smtp_plugin_mocks([
            'site-mailer/site-mailer.php',
            'akismet/akismet.php'
        ]);

        $result = Helper::is_any_smtp_plugin_active();

        $this->assertTrue($result);
    }

    /**
     * Test is_any_smtp_plugin_active - should return true when multiple SMTP plugins are active
     */
    public function test_is_any_smtp_plugin_active_multiple() {
        $this->reset_smtp_test_data();
        $this->setup_smtp_plugin_mocks([
            'wp-mail-smtp/wp_mail_smtp.php',
            'newsletter/plugin.php',
            'fluent-smtp/fluent-smtp.php',
            'akismet/akismet.php'
        ]);

        $result = Helper::is_any_smtp_plugin_active();

        $this->assertTrue($result);
    }

    /**
     * Test is_any_smtp_plugin_active - should return false when only non-SMTP plugins are active
     */
    public function test_is_any_smtp_plugin_active_non_smtp_plugins() {
        $this->reset_smtp_test_data();
        $this->setup_smtp_plugin_mocks([
            'akismet/akismet.php',
            'jetpack/jetpack.php',
            'contact-form-7/wp-contact-form-7.php'
        ]);

        $result = Helper::is_any_smtp_plugin_active();

        $this->assertFalse($result);
    }

    /**
     * Test is_any_smtp_plugin_active - multisite with network active SMTP plugins
     */
    public function test_is_any_smtp_plugin_active_multisite_network() {
        $this->reset_smtp_test_data();

        // Mock is_multisite() to return true
        add_filter('ms_is_switched', '__return_false');

        add_filter('pre_option_active_plugins', function() {
            return [
                'akismet/akismet.php'
            ];
        });

        add_filter('pre_site_option_active_sitewide_plugins', function() {
            return [
                'wp-mail-smtp/wp_mail_smtp.php' => time(),
                'some-network-plugin/plugin.php' => time()
            ];
        });

        // Skip test if not in multisite environment
        if ( ! is_multisite() ) {
            $this->markTestSkipped( 'This test requires a multisite environment.' );
            return;
        }

        $result = Helper::is_any_smtp_plugin_active();

        $this->assertTrue($result);
    }

    /**
     * Test is_any_smtp_plugin_active - multisite with both site and network plugins
     */
    public function test_is_any_smtp_plugin_active_multisite_both() {
        $this->reset_smtp_test_data();

        // Skip test if not in multisite environment
        if ( ! is_multisite() ) {
            $this->markTestSkipped( 'This test requires a multisite environment.' );
            return;
        }

        $this->setup_multisite_smtp_mocks(
            ['newsletter/plugin.php', 'akismet/akismet.php'], // site plugins
            ['fluent-smtp/fluent-smtp.php', 'some-network-plugin/plugin.php'] // network plugins
        );

        $result = Helper::is_any_smtp_plugin_active();

        $this->assertTrue($result);
    }

    /**
     * Test is_any_smtp_plugin_active - multisite with no SMTP plugins
     */
    public function test_is_any_smtp_plugin_active_multisite_no_smtp() {
        $this->reset_smtp_test_data();

        add_filter('pre_option_active_plugins', function() {
            return [
                'akismet/akismet.php'
            ];
        });

        add_filter('pre_site_option_active_sitewide_plugins', function() {
            return [
                'some-network-plugin/plugin.php' => time()
            ];
        });

        // Skip test if not in multisite environment
        if ( ! is_multisite() ) {
            $this->markTestSkipped( 'This test requires a multisite environment.' );
            return;
        }

        $result = Helper::is_any_smtp_plugin_active();

        $this->assertFalse($result);
    }

    /**
     * Test is_any_smtp_plugin_active - should test all SMTP plugins in the detection array
     */
    public function test_is_any_smtp_plugin_active_all_smtp_plugins() {
        $smtp_plugins = [
            'wp-mail-smtp/wp_mail_smtp.php',
            'post-smtp/postman-smtp.php',
            'easy-wp-smtp/easy-wp-smtp.php',
            'wp-smtp/wp-smtp.php',
            'newsletter/plugin.php',
            'fluent-smtp/fluent-smtp.php',
            'pepipost-smtp/pepipost-smtp.php',
            'mail-bank/wp-mail-bank.php',
            'smtp-mailer/smtp-mailer.php',
            'suremails/suremails.php',
            'site-mailer/site-mailer.php',
        ];

        foreach ($smtp_plugins as $plugin) {
            $this->reset_smtp_test_data();

            add_filter('pre_option_active_plugins', function() use ($plugin) {
                return [$plugin, 'akismet/akismet.php'];
            });

            add_filter('pre_site_option_active_sitewide_plugins', function() {
                return [];
            });

            add_filter('pre_option_is_multisite', '__return_false');

            $result = Helper::is_any_smtp_plugin_active();

            $this->assertTrue($result, "Plugin $plugin should be detected as SMTP plugin");

            // Clean up filters for next iteration
            remove_all_filters('pre_option_active_plugins');
            remove_all_filters('pre_site_option_active_sitewide_plugins');
            remove_all_filters('pre_option_is_multisite');
        }
    }

    /**
     * Test is_any_smtp_plugin_active - should return correct type (boolean)
     */
    public function test_is_any_smtp_plugin_active_return_type() {
        $this->reset_smtp_test_data();

        // Test with no plugins
        add_filter('pre_option_active_plugins', function() {
            return [];
        });

        add_filter('pre_site_option_active_sitewide_plugins', function() {
            return [];
        });

        add_filter('pre_option_is_multisite', '__return_false');

        $result_false = Helper::is_any_smtp_plugin_active();
        $this->assertIsBool($result_false);
        $this->assertFalse($result_false);

        // Clean up filters
        remove_all_filters('pre_option_active_plugins');
        remove_all_filters('pre_site_option_active_sitewide_plugins');
        remove_all_filters('pre_option_is_multisite');

        // Test with SMTP plugin
        add_filter('pre_option_active_plugins', function() {
            return ['wp-mail-smtp/wp_mail_smtp.php'];
        });

        add_filter('pre_site_option_active_sitewide_plugins', function() {
            return [];
        });

        add_filter('pre_option_is_multisite', '__return_false');

        $result_true = Helper::is_any_smtp_plugin_active();
        $this->assertIsBool($result_true);
        $this->assertTrue($result_true);
    }

    /**
     * Test is_any_smtp_plugin_active - should handle empty/false plugin options
     */
    public function test_is_any_smtp_plugin_active_empty_options() {
        $this->reset_smtp_test_data();

        // Mock get_option to return false (option doesn't exist)
        add_filter('pre_option_active_plugins', function() {
            return false;
        });

        add_filter('pre_site_option_active_sitewide_plugins', function() {
            return false;
        });

        add_filter('pre_option_is_multisite', '__return_false');

        $result = Helper::is_any_smtp_plugin_active();

        $this->assertFalse($result);
    }

    /**
     * Test is_any_smtp_plugin_active - performance test with large plugin list
     */
    public function test_is_any_smtp_plugin_active_performance() {
        $this->reset_smtp_test_data();

        // Create a large list of non-SMTP plugins
        $large_plugin_list = [];
        for ($i = 0; $i < 100; $i++) {
            $large_plugin_list[] = "plugin-$i/plugin-$i.php";
        }
        // Add one SMTP plugin at the end
        $large_plugin_list[] = 'wp-mail-smtp/wp_mail_smtp.php';

        add_filter('pre_option_active_plugins', function() use ($large_plugin_list) {
            return $large_plugin_list;
        });

        add_filter('pre_site_option_active_sitewide_plugins', function() {
            return [];
        });

        add_filter('pre_option_is_multisite', '__return_false');

        $start_time = microtime(true);
        $result = Helper::is_any_smtp_plugin_active();
        $end_time = microtime(true);

        $execution_time = $end_time - $start_time;

        $this->assertTrue($result);
        $this->assertLessThan(0.1, $execution_time, 'Function should execute quickly even with many plugins');
	}

	/**
     * Test apply_filters_as_array method.
     */
    public function test_apply_filters_as_array() {
        // Test case 1: Empty filter name should return default array
        $default = ['test'];
        $result = Helper::apply_filters_as_array('', $default);
        $this->assertTrue(
            is_array($result),
            'Empty filter name should return array'
        );
        $this->assertSame(
            $default,
            $result,
            'Empty filter name should return default array unchanged'
        );

        // Test case 2: Non-array default should be converted to empty array
        $result = Helper::apply_filters_as_array('test_filter', 'string');
        $this->assertTrue(
            is_array($result),
            'Non-array default should be converted to array'
        );
        $this->assertEmpty(
            $result,
            'Non-array default should be converted to empty array'
        );

        // Test case 3: Valid filter returning non-empty array should return that array
        $expected = ['filtered'];
        add_filter('test_filter_valid', function($value) use ($expected) {
            return $expected;
        });

        $result = Helper::apply_filters_as_array('test_filter_valid', ['default']);
        $this->assertTrue(
            is_array($result),
            'Filter result should be array'
        );
        $this->assertSame(
            $expected,
            $result,
            'Should return array from filter'
        );

        // Test case 4: Filter returning non-array should return default
        add_filter('test_filter_invalid', function($value) {
            return 'not an array';
        });

        $default = ['default'];
        $result = Helper::apply_filters_as_array('test_filter_invalid', $default);
        $this->assertSame(
            $default,
            $result,
            'Should return default when filter returns non-array'
        );
    }

    /**
     * Test get_forms_with_entry_counts method.
     *
     * @since 1.9.1
     */
    public function test_get_forms_with_entry_counts() {
        // Skip test if SRFM_FORMS_POST_TYPE is not defined.
        if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
            $this->markTestSkipped( 'SRFM_FORMS_POST_TYPE constant is not defined' );
        }
        
        // Since we cannot easily mock the static Entries::get_entries_count_after method,
        // we'll test the method's behavior with forms only (without entry counts).
        // This still tests the core functionality: filtering published forms, handling blank titles,
        // sorting, and limiting results.
        
        // Create mock forms.
        $form1_id = wp_insert_post([
            'post_type' => SRFM_FORMS_POST_TYPE,
            'post_status' => 'publish',
            'post_title' => 'Contact Form'
        ]);
        
        $form2_id = wp_insert_post([
            'post_type' => SRFM_FORMS_POST_TYPE,
            'post_status' => 'publish',
            'post_title' => 'Newsletter Signup'
        ]);
        
        $form3_id = wp_insert_post([
            'post_type' => SRFM_FORMS_POST_TYPE,
            'post_status' => 'publish',
            'post_title' => ''  // Test blank title
        ]);
        
        // Create a draft form (should not be included).
        $draft_form_id = wp_insert_post([
            'post_type' => SRFM_FORMS_POST_TYPE,
            'post_status' => 'draft',
            'post_title' => 'Draft Form'
        ]);
        
        // Test basic functionality.
        $result = Helper::get_forms_with_entry_counts(strtotime('-7 days'));
        
        // Should return only published forms.
        $this->assertCount(3, $result, 'Should return only published forms');
        
        // Check that all results have the expected structure.
        foreach ($result as $form) {
            $this->assertArrayHasKey('form_id', $form, 'Each result should have form_id');
            $this->assertArrayHasKey('title', $form, 'Each result should have title');
            $this->assertArrayHasKey('count', $form, 'Each result should have count');
        }
        
        // Find the form with blank title to verify it was replaced.
        $blank_title_found = false;
        foreach ($result as $form) {
            if ($form['form_id'] === $form3_id) {
                $this->assertEquals('Blank Form', $form['title'], 'Empty title should be replaced with "Blank Form"');
                $blank_title_found = true;
                break;
            }
        }
        $this->assertTrue($blank_title_found, 'Form with blank title should be in results');
        
        // Test with limit.
        $result_limited = Helper::get_forms_with_entry_counts(strtotime('-7 days'), 2);
        $this->assertCount(2, $result_limited, 'Should respect the limit parameter');
        
        // Test without sorting.
        $result_unsorted = Helper::get_forms_with_entry_counts(strtotime('-7 days'), 0, false);
        $this->assertCount(3, $result_unsorted, 'Should return all forms when sort is false');
        
        // Clean up.
        wp_delete_post($form1_id, true);
        wp_delete_post($form2_id, true);
        wp_delete_post($form3_id, true);
        wp_delete_post($draft_form_id, true);
        
        // Test when no forms exist.
        $result_empty = Helper::get_forms_with_entry_counts(strtotime('-7 days'));
        $this->assertEmpty($result_empty, 'Should return empty array when no forms exist');
    }

    /**
     * Test get_forms_with_entry_counts sorting behavior.
     *
     * @since 1.9.1
     */
    public function test_get_forms_with_entry_counts_sorting() {
        // Skip test if SRFM_FORMS_POST_TYPE is not defined.
        if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
            $this->markTestSkipped( 'SRFM_FORMS_POST_TYPE constant is not defined' );
        }
        
        // Create multiple forms to test sorting.
        $form_ids = [];
        for ($i = 1; $i <= 3; $i++) {
            $form_ids[] = wp_insert_post([
                'post_type' => SRFM_FORMS_POST_TYPE,
                'post_status' => 'publish',
                'post_title' => 'Form ' . $i
            ]);
        }
        
        // Test that results are returned (even if all counts are 0).
        $result = Helper::get_forms_with_entry_counts(strtotime('-7 days'));
        
        $this->assertCount(3, $result, 'Should return all published forms');
        
        // When all entry counts are the same (likely 0 in test environment),
        // forms should be sorted by form_id descending.
        if ($result[0]['count'] === $result[1]['count'] && $result[1]['count'] === $result[2]['count']) {
            // Check form_id descending order when counts are equal.
            $this->assertGreaterThan(
                $result[1]['form_id'], 
                $result[0]['form_id'], 
                'When counts are equal, should sort by form_id descending'
            );
            $this->assertGreaterThan(
                $result[2]['form_id'], 
                $result[1]['form_id'], 
                'When counts are equal, should sort by form_id descending'
            );
        }
        
        // Clean up.
        foreach ($form_ids as $form_id) {
            wp_delete_post($form_id, true);
        }
    }

    /**
     * Test the is_valid_form method to validate form IDs.
     */
    public function test_is_valid_form() {
        // Case 1: Empty form ID
        $this->assertFalse(Helper::is_valid_form(''), 'Empty form ID should be invalid');

        // Case 2: Non-numeric form ID
        $this->assertFalse(Helper::is_valid_form('abc'), 'Non-numeric form ID should be invalid');

        // Case 3: Non-existent post ID
        $this->assertFalse(Helper::is_valid_form(999999), 'Non-existent form ID should be invalid');

        // Define post type constant if not already defined
        if (!defined('SRFM_FORMS_POST_TYPE')) {
            define('SRFM_FORMS_POST_TYPE', 'sureform');
        }

        // Case 4: Create a post with wrong post type
        $invalid_post_id = wp_insert_post([
            'post_title'  => 'Invalid Type',
            'post_type'   => 'post',
            'post_status' => 'publish',
        ]);
        $this->assertFalse(Helper::is_valid_form($invalid_post_id), 'Wrong post type should be invalid');

        // Case 5: Create a valid SureForms form post
        $valid_form_id = wp_insert_post([
            'post_title'  => 'Valid SureForm',
            'post_type'   => SRFM_FORMS_POST_TYPE,
            'post_status' => 'publish',
        ]);
        $this->assertTrue(Helper::is_valid_form($valid_form_id), 'Valid SureForms form ID should return true');

        // Case 6: Numeric string form ID
        $this->assertTrue(Helper::is_valid_form((string) $valid_form_id), 'String numeric form ID should return true');
    }

    public function test_get_timestamp_from_string_returns_valid_timestamp() {
        $result = Helper::get_timestamp_from_string('2025-12-31', '10', '30', 'AM');
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * Test get_block_name_from_field method.
     */
    public function test_get_block_name_from_field() {
        // Test case 1: Standard field name with -lbl-
        $field_name = 'srfm-input-fe439fd2-lbl-RnVsbCBOYW1l-full-name';
        $expected = 'srfm-input';
        $result = Helper::get_block_name_from_field($field_name);
        $this->assertEquals($expected, $result);

        // Test case 2: Email field
        $field_name = 'srfm-email-abc123-lbl-RW1haWw-email';
        $expected = 'srfm-email';
        $result = Helper::get_block_name_from_field($field_name);
        $this->assertEquals($expected, $result);

        // Test case 3: Field name without -lbl- (edge case)
        $field_name = 'srfm-textarea-12345-field-name';
        $expected = 'srfm-textarea';
        $result = Helper::get_block_name_from_field($field_name);
        $this->assertEquals($expected, $result);
    }

}
