<?php
/**
 * Class Test_Post_Types
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Post_Types;

class Test_Post_Types extends TestCase {

	public function test_register_post_types() {
		if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
			$this->markTestSkipped( 'SRFM_FORMS_POST_TYPE not defined' );
		}

		$post_types = new Post_Types();
		$post_types->register_post_types();

		$this->assertTrue( post_type_exists( SRFM_FORMS_POST_TYPE ) );

		$post_type_obj = get_post_type_object( SRFM_FORMS_POST_TYPE );
		$this->assertTrue( $post_type_obj->public );
		$this->assertTrue( $post_type_obj->show_in_rest );
		$this->assertTrue( post_type_supports( SRFM_FORMS_POST_TYPE, 'title' ) );
		$this->assertTrue( post_type_supports( SRFM_FORMS_POST_TYPE, 'editor' ) );
		$this->assertTrue( post_type_supports( SRFM_FORMS_POST_TYPE, 'custom-fields' ) );
	}
}
