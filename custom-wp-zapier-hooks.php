<?php
/**
 * Hooks Class for main entry point
 */
namespace CustomWpZapier\Hooks;
class Hooks
{	
	function __construct($plugin)
	{		 
		add_filter("plugin_action_links_$plugin", array($this, 'custom_wp_zappier_settings_link'), 10, 2 );
	}
	/*link in plugins table*/
	public function custom_wp_zappier_settings_link($links) { 
		$main = CUSTOM_WP_ZAPIER_SETTINGS_MAIN_PAGE;
	  	$settings_link = '<a href="options-general.php?page='.$main.'">Settings</a>'; 
	  	array_unshift($links, $settings_link); 
	  	return $links; 
	}
}
new Hooks(CUSTOM_WP_ZAPIER_PLUGIN_NAME);
