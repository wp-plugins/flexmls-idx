<?php

class flexmlsConnectPage {

	function __construct() {

	}

	function catch_special_request() {
		global $fmc_special_page_caught;
		global $wp_query;

		$tag = flexmlsConnect::page_slug_tag('fmc_tag');

		if ($tag) {

			// this is the first indication that the page requested is one of our full pages

			switch($tag) {

				case "search":
					$custom_page = new flexmlsConnectPageSearchResults;
					break;
				
				case "next-listing":
					$custom_page = new flexmlsConnectPageNextListing;
					break;
				
				case "prev-listing":
					$custom_page = new flexmlsConnectPagePrevListing;
					break;
// i4 TODO
//				case "my":
//					$custom_page = new flexmlsConnectPageMyAccount;
//					break;
//				
//				case "oauth-login":
//					$custom_page = new flexmlsConnectPageOAuthLogin;
//					break;
//				
//				case "logout":
//					$custom_page = new flexmlsConnectPageLogout;
//					break;

				default:
					// request for listing details assumed
					$custom_page = new flexmlsConnectPageListingDetails;
					break;

			}

			$custom_page->pre_tasks($tag);
			$fmc_special_page_caught['fmc-page'] = $custom_page;


			add_filter('wp_title', array('flexmlsConnectPage', 'custom_page_title') );
			add_filter('the_post', array('flexmlsConnectPage', 'custom_post_title') );
			add_filter('the_content', array('flexmlsConnectPage', 'custom_post_content') );


			if ( !empty($fmc_special_page_caught['page-url']) ) {
				remove_action('wp_head', 'rel_canonical');
				add_action('wp_head', array('flexmlsConnectPage', 'my_rel_canonical') );
			}
//
//			$cookie_data = array('PropertyType' => 'A', 'MinBeds' => 2);
//			setcookie('fmc_last_search_tracker', json_encode($cookie_data) );

		}
	}

	function custom_page_title() {
		global $fmc_special_page_caught;
		return $fmc_special_page_caught['page-title'] . " | ";
	}


	function custom_post_title($page) {
		global $fmc_special_page_caught;
		global $wp_query;

		if ($wp_query->post->ID == $page->ID) {
			$page->post_title = $fmc_special_page_caught['post-title'];
		}

		return $page;
	}
	

	function custom_post_content($page) {
		global $fmc_special_page_caught;

		$return  = "\n";
		// disable the "Comments are disabled" text on the page
		$return .= "<style type='text/css'>\n  .nocomments { display:none; }\n</style>\n\n\n";
		$return .= $fmc_special_page_caught['fmc-page']->generate_page();

		return $return;

	}


	/**
	 * Custom canonical links
	 */
	function my_rel_canonical() {
		global $fmc_special_page_caught;

		$options = get_option('fmc_settings');
		echo "<link rel='canonical' href='" . $fmc_special_page_caught['page-url'] . "' />\n";
	}




}
