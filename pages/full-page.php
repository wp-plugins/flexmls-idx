<?php

class flexmlsConnectPage {

	function __construct() {

	}

	//Hook is in flexmls_connect.php
	function query_vars_init($qvars) {
		$qvars[] = 'fmc_tag';
		$qvars[] = 'oauth_tag';
		$qvars[] = 'fmc_vow_tag';
		return $qvars;
	}

	//Hook is in flexmls_connect.php
	function do_rewrite() {
		$options = get_option('fmc_settings');
		add_rewrite_rule( $options['permabase'] .'/([^/]+)?' , 'index.php?plugin=flexmls-idx&fmc_tag=$matches[1]&page_id='. $options['destlink'] , 'top' );
		add_rewrite_rule( 'portal'.'/([^/]+)?' , 'index.php?plugin=flexmls-idx&fmc_vow_tag=$matches[1]&page_id='. $options['destlink'] , 'top' );
		add_rewrite_rule('oauth/callback', 'index.php?plugin=flexmls-idx&oauth_tag=oauth-login', 'top');
	}

	function catch_special_request() {
		global $fmc_special_page_caught;
		global $wp_query;
		global $fmc_api_portal;

		$tag = get_query_var('fmc_tag');
		$oauth_tag = get_query_var('oauth_tag');
		$vow_tag = get_query_var('fmc_vow_tag');
		if (!($tag) and !($oauth_tag) and !($vow_tag))
			return;

		if ($vow_tag) {
			$tag=$vow_tag;
			$type='fmc_vow_tag';
		}
		else {
			//default
			$type = null;
		}

		if ($tag) {
			// this is the first indication that the page requested is one of our full pages
			// These full pages can be accessed with get_site_url()/[permalink_slug]/$tag
			switch($tag) {
				case "search":
					$custom_page = new flexmlsConnectPageSearchResults($type);
					break;
				case "next-listing":
					$custom_page = new flexmlsConnectPageNextListing($type);
					break;
				case "prev-listing":
					$custom_page = new flexmlsConnectPagePrevListing($type);
					break;

				default:
					// request for listing details assumed
					$custom_page = new flexmlsConnectPageListingDetails($type);
					break;
			}
			$custom_page->pre_tasks($tag);
			$fmc_special_page_caught['fmc-page'] = $custom_page;

			add_filter('body_class', array('flexmlsConnectPage', 'custom_body_class') );
			add_filter('wp_title', array('flexmlsConnectPage', 'custom_page_title') );
			add_filter('the_post', array('flexmlsConnectPage', 'custom_post_title') );
			add_filter('the_content', array('flexmlsConnectPage', 'custom_post_content') );


			if ( !empty($fmc_special_page_caught['page-url']) ) {
				remove_action('wp_head', 'rel_canonical');
				add_action('wp_head', array('flexmlsConnectPage', 'my_rel_canonical') );
			}
		}
		//Making OAuth seperate because don't want the permalink to be allowed to change for it.
		// full page can be accessed with get_site_url()/oauth/callback
		elseif ($oauth_tag){
			$custom_page = new flexmlsConnectPageOAuthLogin;
			$custom_page->pre_tasks($tag);
			$fmc_special_page_caught['fmc-page'] = $custom_page;


			add_filter('wp_title', array('flexmlsConnectPage', 'custom_page_title') );
			add_filter('the_post', array('flexmlsConnectPage', 'custom_post_title') );
			add_filter('the_content', array('flexmlsConnectPage', 'custom_post_content') );


			if ( !empty($fmc_special_page_caught['page-url']) ) {
				remove_action('wp_head', 'rel_canonical');
				add_action('wp_head', array('flexmlsConnectPage', 'my_rel_canonical') );
			}
		}
	}

	function custom_body_class($classes) {
		// add a class to the body tag based on what type of page it is.
		global $fmc_special_page_caught;
		$classes[] = 'flexmls_connect__' . str_replace('-', '_', $fmc_special_page_caught['type']) . '_page';
		return $classes;
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
		// TODO: replace this style line with normal css, now that each page has a special body class.
		// disable the "Comments are disabled" text on the page
		$return = "<style type='text/css'> .nocomments { display:none; }</style>";
		$return .= $fmc_special_page_caught['fmc-page']->generate_page();
		return $return;
	}

	/**
	 * Custom canonical links
	 */
	function my_rel_canonical() {
		global $fmc_special_page_caught;
		echo "<link rel='canonical' href='" . $fmc_special_page_caught['page-url'] . "' />\n";
	}

}
