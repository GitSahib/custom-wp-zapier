<?php
/**
 * @package CustomWpZapier
 * @version 1.3.2
 */
/*
Plugin Name: Custom WP Zapier
Plugin URI:  https://paktweet.com/plugins/latest/wp-zapier
Description: Listens to your zapier/api webhooks on [your-wordpress-site]/wp-json/custom-wp-zapier/v1/sf-post-hook endpoint.
Version: 1.3.2
Author URI: https://www.linkedin.com/in/sahib-khan/
*/

include_once("custom-wp-zapier-dependency-check.php");
if ( custom_zapier_requirements_met() ) 
{
    DEFINE('CUSTOM_WP_ZAPIER_PLUGIN_VERSION', "1.3.2");
    DEFINE('CUSTOM_WP_ZAPIER_PLUGIN_NAME', plugin_basename(__FILE__));
    DEFINE('CUSTOM_WP_ZAPIER_PLUGIN_DIR', plugin_dir_url(__FILE__));
    DEFINE('CUSTOM_WP_ZAPIER_SETTINGS_GROUP', 'CUSTOM_WP_ZAPIER_SETTINGS');
    DEFINE('CUSTOM_WP_ZAPIER_SETTINGS_MAIN_PAGE', 'custom_wp_zapier_settings_main_admin'); 
    include_once("custom-wp-zapier-mappings.php"); 
    include_once("custom-wp-zapier-utils.php"); 
    include_once("custom-wp-zapier-hooks.php");
    include_once("custom-wp-zapier-settings.php"); 
    include_once("custom-wp-zapier-rest-settings.php"); 
    register_activation_hook( __FILE__, 'custom_wp_zapier_admin_notice_activation_hook' );
    register_deactivation_hook( __FILE__, 'custom_wp_zapier_admin_notice_deactivation_hook' ); 
    register_uninstall_hook( __FILE__, 'custom_wp_zapier_admin_notice_uninstall_hook'); 
    /* Add admin notice */
    add_action( 'admin_notices', 'custom_wp_zapier_admin_notice_check' );

} 
else 
{
    add_action( 'admin_notices', 'custom_zapier_requirements_error' );
}

/**
 * Runs only when the plugin is activated.
 * @since 0.1.0
 */
function custom_wp_zapier_admin_notice_activation_hook() {
    /* Create transient data */
    set_transient( 'custom_wp_zapier_activation_transient', true, 5 );
    set_transient( 'custom_wp_zapier_activation_loging_transient', true, 5 );
}
/**
 * Runs only when the plugin is deactivated.
 * @since 0.1.0
 */
function custom_wp_zapier_admin_notice_deactivation_hook(){
    
}
/**
 * Runs only when the plugin is uninstalled.
 * @since 0.1.0
 */
function custom_wp_zapier_admin_notice_uninstall_hook(){
    delete_option(CUSTOM_WP_ZAPIER_SETTINGS_GROUP);
}

/**
 * Admin Notice on Activation.
 * @since 0.1.0
 */
function custom_wp_zapier_admin_notice_check()
{

    /* Check transient, if available display notice */
    if( get_transient( 'custom_wp_zapier_activation_transient' ) )
    { ?>
        <div class="updated notice is-dismissible">
            <p>Thank you for using Custom WP Zapier plugin! Please goto plugin 
            <a href="options-general.php?page=<?php echo CUSTOM_WP_ZAPIER_SETTINGS_MAIN_PAGE?>">Settings</a> page, and map your fields to start listening to your api webhooks.</p>
        </div>
        <?php
        /* Delete transient, only display this notice once. */
        delete_transient( 'custom_wp_zapier_activation_transient' );
    }
}

