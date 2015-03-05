<?php

class flexmlsConnectPagePrevListing extends flexmlsConnectPageCore {

  protected $browse_list;
  protected $no_more = false;
  protected $type;
  function __construct( $tag ){

    if ($tag == 'fmc_vow_tag'){
      global $fmc_api_portal;
      $this->api = $fmc_api_portal;
      $this->type = $tag;
    }
    else {
      global $fmc_api;
      $this->api = $fmc_api;
      $this->type = 'fmc_tag';
    }
  }

  function pre_tasks() {

    list($previous_listing_url, $next_listing_url) = $this->get_browse_redirects();
    wp_redirect($previous_listing_url, 301);

    exit;

  }

}
