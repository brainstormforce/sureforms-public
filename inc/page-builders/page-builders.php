<?php
/**
 * Page Builder.
 *
 * @package sureforms.
 * @since x.x.x
 */

namespace SRFM\Inc\Page_Builders;

use SRFM\Inc\Page_Builders\Elementor\Service_Provider as Elementor_Service_Provider;
use SRFM\Inc\Page_Builders\Bricks\Service_Provider as Bricks_Service_Provider;
use SRFM\Inc\Traits\Get_Instance;

/**
 * Class to add SureForms widget in other page builders.
 */
class Page_Builders {
	use Get_Instance;

	/**
	 * Constructor
	 *
	 * Load all Page Builders form widgets.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function __construct() {
		Elementor_Service_Provider::get_instance();
		Bricks_Service_Provider::get_instance();
	}

}
