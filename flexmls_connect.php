<?php
/*
Plugin Name: flexmls&reg; IDX
Plugin URI: http://www.flexmls.com/wpdemo/
Description: Provides flexmls&reg; Customers with flexmls&reg; IDX features on their WordPress blog. <strong>Tips:</strong> <a href="options-general.php?page=flexmls_connect">Activate your flexmls IDX plugin</a> on the settings page; <a href="widgets.php">add widgets to your sidebar</a> using the Widgets Admin under Appearance; and include widgets on your posts or pages using the flexmls IDX Widget Short-Code Generator on the Visual page editor.
Author: FBS
Version: 3.0.7
Author URI: http://www.flexmls.com/
*/

$fmc_version = '3.0.7';
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
				'max_cache_time' => 0,
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
				),
		'fmcListingDetails' => array(
				'component' => 'listing-details.php',
				'title' => "flexmls&reg;: IDX Listing Details",
				'description' => "Insert listing details into a page or post",
		    		'requires_key' => true,
		    		'shortcode' => 'idx_listing_details',
		    		'max_cache_time' => 0,
		    		'widget' => false
				),
		'fmcSearchResults' => array(
				'component' => 'search-results.php',
				'title' => "flexmls&reg;: IDX Listing Summary",
				'description' => "Insert a summary of listings into a page or post",
		    		'requires_key' => true,
		    		'shortcode' => 'idx_listing_summary',
		    		'max_cache_time' => 0,
		    		'widget' => false
				),
		);




/*
 * Load in the basics
 */

require_once('lib/base.php');
require_once('lib/flexmls-json.php');
require_once('lib/settings-page.php');
require_once('lib/flexmlsAPI/Core.php');
require_once("lib/flexmlsAPI/WordPressCache.php");
require_once('components/widget.php');

$fmc_special_page_caught = array(
		'type' => null
);

require_once('pages/core.php');
require_once('pages/full-page.php');
require_once('pages/listing-details.php');
require_once('pages/search-results.php');
require_once('pages/next-listing.php');
require_once('pages/prev-listing.php');
// i4 TODO
//require_once('pages/oauth-login.php');
//require_once('pages/logout.php');
//require_once('pages/my-account.php');


$fmc_api = flexmlsConnect::new_apiauth_client();

$api_ini_file = $fmc_plugin_dir . '/lib/api.ini';

$fmc_location_search_url = "http://www.flexmls.com";

if (file_exists($api_ini_file)) {
	$local_settings = parse_ini_file($api_ini_file);
	if (array_key_exists('api_base', $local_settings)) {
		$fmc_api->api_base = trim($local_settings['api_base']);
	}
	if (array_key_exists('location_search_url', $local_settings)) {
		$fmc_location_search_url = trim($local_settings['location_search_url']);
	}
}


$fmc_instance_cache = array();


/*
 * register the init functions with the appropriate WP hooks
 */
add_action('widgets_init', array('flexmlsConnect', 'widget_init') );
add_action('admin_init', array('flexmlsConnectSettings', 'settings_init') );
add_action('admin_menu', array('flexmlsConnect', 'admin_menus_init') );
add_action('init', array('flexmlsConnect', 'initial_init') );
register_deactivation_hook( __FILE__, array('flexmlsConnect', 'plugin_deactivate') );
register_activation_hook( __FILE__, array('flexmlsConnect', 'plugin_activate') );
add_filter('query_vars', array('flexmlsConnect', 'query_vars_init') );
add_action('wp', array('flexmlsConnectPage', 'catch_special_request') );
add_action('wp', array('flexmlsConnect', 'wp_init') );

$fmc_search_results_loaded = false;

/**
 * since WordPress doesn't garbage collect expired cache items, we'll force it to every ~1,000 requests
 */
if (rand(1, 1000) === 1) {
	flexmlsConnect::garbage_collect_bad_caches();
}
