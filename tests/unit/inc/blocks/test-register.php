<?php
/**
 * Class Test_Blocks_Register
 *
 * @package sureforms
 */

use SRFM\Inc\Blocks\Register;
use SRFM\Inc\Features;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Blocks_Register extends TestCase {

	protected function tearDown(): void {
		delete_option( Features::OPTION );
		Features::reset();
		if ( ! WP_Block_Type_Registry::get_instance()->is_registered( 'srfm/payment' ) ) {
			Register::register_block( [ SRFM_DIR . 'inc/blocks/payment/block.php' ], 'SRFM\\Inc\\Blocks', 'Block' );
		}
		parent::tearDown();
	}

	/**
	 * The payment block is registered only while Payments is on.
	 */
	public function test_register_block_skips_payment_blocks_while_payments_is_off() {
		$registry = WP_Block_Type_Registry::get_instance();
		$file     = SRFM_DIR . 'inc/blocks/payment/block.php';
		if ( $registry->is_registered( 'srfm/payment' ) ) {
			unregister_block_type( 'srfm/payment' );
		}

		Features::save( [ 'payments' => false ] );
		Register::register_block( [ $file ], 'SRFM\\Inc\\Blocks', 'Block' );
		$this->assertFalse( $registry->is_registered( 'srfm/payment' ) );

		Features::save( [ 'payments' => true ] );
		Register::register_block( [ $file ], 'SRFM\\Inc\\Blocks', 'Block' );
		$this->assertTrue( $registry->is_registered( 'srfm/payment' ) );
	}
}
