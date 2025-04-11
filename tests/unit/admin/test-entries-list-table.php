<?php
/**
 * Class Test_Entries_List_Table
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

use SRFM\Admin\Views\Entries_List_Table;

/**
 * Tests Plugin Initialization.
 *
 */
class Test_Entries_List_Table extends TestCase {
    /**
     * The results array simulating the data returned from the database.
     * 
     * @var array
     */
    private array $results;

    /**
     * The block key map simulating the mapping of block keys to form data.
     * 
     * @var array
     */
    private array $block_key_map;

    /**
     * The labels array simulating the labels for the form fields.
     * 
     * @var array
     */
    private array $labels;

    /**
     * Template method to set up the test environment.
     */
    protected function setUp(): void {
        parent::setUp();

        $this->results = [
            [
                'ID' => 1,
                'form_data' => [
                    'srfm-input-de99dfb8-lbl-TmFtZQ-name'   => 'John',
                    'srfm-email-9546c43e-lbl-RW1haWw-srfm-email' => 'john@example.com',
                ],
            ],
        ];
        $this->block_key_map = [
            'de99dfb8' => 'srfm-input-de99dfb8-lbl-TmFtZQ-name',
            '9546c43e' => 'srfm-email-9546c43e-lbl-RW1haWw-srfm-email',
        ];
        $this->labels = [
            'de99dfb8' => 'Name',
            '9546c43e' => 'Email',
        ];
    }

    /**
     * Get an instance of the Entries_List_Table class.
     */
    private function get_entries_list_table_instance() {
        $reflection = new \ReflectionClass(Entries_List_Table::class);
        return $reflection->newInstanceWithoutConstructor();
    }

    /**
     * Get a private method from the Entries_List_Table class.
     */
    private function get_private_method( $instant, $method_name ) {
        $reflection = new \ReflectionClass( $instant );
        $method     = $reflection->getMethod( $method_name );
        $method->setAccessible( true );
        return $method;
    }

    /**
     * Test the build_block_key_map_and_labels method.
     */
    public function test_build_block_key_map_and_labels() {
        $instance   = $this->get_entries_list_table_instance();
        $method = $this->get_private_method( $instance, 'build_block_key_map_and_labels' );

        $output = $method->invoke($instance, $this->results);

        $this->assertEquals([
            'map' => $this->block_key_map,
            'labels' => $this->labels,
        ], $output);
        $this->assertIsArray($output);
    }

    /**
     * Test the write_csv_rows method.
     */
    public function test_write_csv_rows_writes_expected_values() {
        $instance = $this->get_entries_list_table_instance();
        $method   = $this->get_private_method($instance, 'write_csv_rows');

        $stream = fopen('php://memory', 'r+');
        $method->invoke($instance, $stream, $this->results, $this->block_key_map);

        rewind($stream);
        $row = fgetcsv($stream);

        $this->assertEquals(['#1', 'John', 'john@example.com'], $row);
        fclose($stream);
    }

    /**
     * Test the write_csv_header method.
     */
    public function test_write_csv_header_writes_expected_labels() {
        $instance = $this->get_entries_list_table_instance();
        $method   = $this->get_private_method($instance, 'write_csv_header');

        $stream = fopen('php://memory', 'r+');
        $method->invoke($instance, $stream, $this->labels);

        rewind($stream);
        $csv = fgetcsv($stream);

        $this->assertEquals(['ID', 'Name', 'Email'], $csv);
        fclose($stream);
    }
    
}
