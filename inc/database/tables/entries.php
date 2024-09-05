<?php
/**
 * SureForms Database Base Class.
 *
 * @link       https://sureforms.com
 * @since      x.x.x
 * @package    SureForms
 * @author     SureForms <https://sureforms.com/>
 */

namespace SRFM\Inc\Database\Tables;

use SRFM\Inc\Database\Base;
use SRFM\Inc\Traits\Get_Instance;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class Entries extends Base {
	use Get_Instance;

	/**
	 * @inheritDoc
	 */
	protected $table_suffix = 'entries';

	public function add( $data ) {
		if ( empty( $data['form_id'] ) ) {
			return false;
		}

		$_data = array(
			'form_id'   => $data['form_id'],
			'user_data' => $data['user_data'],
		);
	}
}
