<?php 
define ( 'CFP_REQUIRED_PHP_VERSION', '5' ) ; 
define ( 'CFP_REQUIRED_WP_VERSION', '4.6' ) ;   
define ( 'CFP_REQUIRED_PL_VERSION', '5.1.7' ); 


/**
 * Checks if the system requirements are met
 *
 * @return bool True if system requirements are met, false if not
 */
function custom_zapier_requirements_met () {
    global $wp_version ;
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' ) ;  // to get is_plugin_active() early

    if ( version_compare ( PHP_VERSION, CFP_REQUIRED_PHP_VERSION, '<' ) ) {
        return false ;
    }

    if ( version_compare ( $wp_version, CFP_REQUIRED_WP_VERSION, '<' ) ) {
        return false ;
    }

    return true ;
} 
function custom_zapier_requirements_error () { ?>

   <div class="notice notice-error">
        <p> Please Install & Activate the latest version of wordpress, before using custom-wp-zapeir Plugin.</p>
    </div>
    <?php
}