<?php
/**
 * Plugin Name: SureForms Custom Functions Docker
 * Plugin URI: https://sureforms.com
 * Description: Custom Function for docker testing
 * Author: SureForms
 * Author URI: https://sureforms.com/
 * Version: 1.0.0
 * License: GPLv2 or later
 *
 */

 add_action('admin_head', 'srfm_hide_popovers');
 
	function srfm_hide_popovers() {
	        ?>
	        <style>
	                .wp-admin .components-popover.nux-dot-tip {
 	                        display: none !important;
 	                }
 	        </style>
 	
	      <script>
 	              jQuery(window).on('load', function(){
 	                      wp.data && wp.data.select( 'core/edit-post' ).isFeatureActive( 'welcomeGuide' ) && wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'welcomeGuide' );
						wp.data && wp.data.select( 'core/edit-post' ).isFeatureActive( 'fullscreenMode' ) && wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'fullscreenMode' );
            });
        </script>
         <?php
 	}