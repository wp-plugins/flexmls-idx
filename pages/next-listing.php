<?php

class flexmlsConnectPageNextListing extends flexmlsConnectPageCore {
	
	protected $browse_list;
	protected $no_more = false;
	
	function pre_tasks() {
		
		list($previous_listing_url, $next_listing_url) = $this->get_browse_redirects();		
		wp_redirect($next_listing_url, 301);
		
		exit;
		
	}
	
}
