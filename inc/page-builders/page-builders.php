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

/**
 * Class to add SureForms widget in other page builders.
 */
class Page_Builders {


	/**
	 * Constructor
	 * 
	 * Load all Page Builders form widgets.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function __construct() {
        new Elementor_Service_Provider();
        new Bricks_Service_Provider();
	}

}
