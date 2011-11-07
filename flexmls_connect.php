<?php
/*
Plugin Name: flexmls&reg; IDX
Plugin URI: http://www.flexmls.com/wpdemo/
Description: Provides flexmls&reg; Customers with flexmls&reg; IDX features on their WordPress blog. <strong>Tips:</strong> <a href="options-general.php?page=flexmls_connect">Activate your flexmls IDX plugin</a> on the settings page; <a href="widgets.php">add widgets to your sidebar</a> using the Widgets Admin under Appearance; and include widgets on your posts or pages using the flexmls IDX Widget Short-Code Generator on the Visual page editor.
Author: FBS
Version: 2.4.2
Author URI: http://www.flexmls.com/
*/

$fmc_version = '2.4.2';
$fmc_plugin_dir = dirname(realpath(__FILE__));
$fmc_plugin_url = get_option('siteurl') .'/wp-content/plugins/flexmls-idx';

/*
 * Define widget information
 */

$fmc_widgets = array(
		'fmcMarketStats' => array(
				'component' => 'market-statistics.php',
				'title' => "flexmls&reg;: Market Statistics",
				'description' => "Show market statistics on your blog",
				'requires_key' => true,
				'shortcode' => 'market_stats',
				'max_cache_time' => 0,
				'widget' => true
				),
		'fmcPhotos' => array(
				'component' => 'photos.php',
				'title' => "flexmls&reg;: IDX Slideshow",
				'description' => "Show photos of selected listings",
				'requires_key' => true,
				'shortcode' => 'idx_slideshow',
				'max_cache_time' => 600,
				'widget' => true
				),
		'fmcSearch' => array(
				'component' => 'search.php',
				'title' => "flexmls&reg;: IDX Search",
				'description' => "Allow users to search for listings",
				'requires_key' => true,
				'shortcode' => 'idx_search',
				'max_cache_time' => 0,
				'widget' => true
				),
		'fmcLocationLinks' => array(
				'component' => 'location-links.php',
				'title' => "flexmls&reg;: 1-Click Location Searches",
				'description' => "Allow users to view listings from a custom search narrowed to a specific area",
				'requires_key' => true,
				'shortcode' => 'idx_location_links',
				'max_cache_time' => 0,
				'widget' => true
				),
		'fmcIDXLinks' => array(
				'component' => 'idx-links.php',
				'title' => "flexmls&reg;: 1-Click Custom Searches",
				'description' => "Share popular searches with your users",
				'requires_key' => true,
				'shortcode' => 'idx_custom_links',
				'max_cache_time' => 0,
				'widget' => true
				),
		'fmcLeadGen' => array(
				'component' => 'lead-generation.php',
				'title' => "flexmls&reg;: Contact Me Form",
				'description' => "Allow users to share information with you",
				'requires_key' => true,
				'shortcode' => 'lead_generation',
				'max_cache_time' => 0,
				'widget' => true
				),
		'fmcNeighborhoods' => array(
				'component' => 'neighborhoods.php',
				'title' => "flexmls&reg;: Neighborhood Page",
				'description' => "Create a neighborhood page from a template",
				'requires_key' => true,
				'shortcode' => 'neighborhood_page',
				'max_cache_time' => 0,
				'widget' => false
				)
		);




/*
 * Load in the basics
 */

require_once('lib/base.php');
require_once('lib/flexmls-json.php');
require_once('lib/flexmls-api-wp.php');
require_once('components/widget.php');


$fmc_api = new flexmlsApiWP;
$fmc_api->last_token = get_transient('fmc_last_authtoken');

$fmc_instance_cache = array();

// handle form submission actions from the plugin
if ( array_key_exists('fmc_do', $_POST) ) {
	switch($_POST['fmc_do']) {
		case "fmc_search":
			require_once("components/search.php");
			$handle = new fmcSearch();
			$handle->submit_search();
			break;
	}
}


/*
 * register the init functions with the appropriate WP hooks
 */
add_action('widgets_init', array('flexmlsConnect', 'widget_init') );
add_action('admin_init', array('flexmlsConnect', 'settings_init') );
add_action('admin_menu', array('flexmlsConnect', 'admin_menus_init') );
add_action('init', array('flexmlsConnect', 'initial_init') );
register_deactivation_hook( __FILE__, array('flexmlsConnect', 'plugin_deactivate') );
register_activation_hook( __FILE__, array('flexmlsConnect', 'plugin_activate') );
