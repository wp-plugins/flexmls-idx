<?php

class flexmlsConnectPageOAuthLogin {

  private $temp_api;

  function pre_tasks($tag) {
    global $fmc_special_page_caught;
    global $fmc_api;
    global $fmc_api_portal;

    $code = flexmlsConnect::wp_input_get('code');
    $state = flexmlsConnect::wp_input_get('state');

    $URI = parse_url($state);
    $query_params = array();
    parse_str($URI['query'], $query_params);

    if (!empty($code)) {
      $grant = $fmc_api_portal->Grant($code);
      if ($grant) {
        if (array_key_exists('remove_cart', $query_params)){
          $carts = $fmc_api_portal->GetListingCartsWithListing($query_params['listing_id']);
          foreach($carts as $the_cart) {
            if ($the_cart['PortalCartType'] == $query_params['remove_cart']){
              $fmc_api_portal->DeleteListingsFromCart($the_cart['Id'], $query_params['listing_id']);
            }
          }
        }
        elseif (array_key_exists('add_cart', $query_params)){
          $all_carts = $fmc_api_portal->GetListingCarts();
          $already_added = false;
          //remove from other carts, and prevent another api request if not in other carts
          $carts = $fmc_api_portal->GetListingCartsWithListing($query_params['listing_id']);
          foreach($carts as $the_cart) {
            if ($the_cart['PortalCartType'] != $query_params['add_cart']){
              $fmc_api_portal->DeleteListingsFromCart($the_cart['Id'], $query_params['listing_id']);
            }
            else {
              $already_added=true;
            }
          }

          if (!$already_added){
            foreach ($all_carts as $single_cart){
              if ($single_cart['PortalCartType']==$query_params['add_cart']){
                $fmc_api_portal->AddListingsToCart($single_cart['Id'], array($query_params['listing_id']));
                break;
              }
            }
          }

          unset($query_params['add_cart']);
          unset($query_params['listing_id']);

          $page =  explode('?',$state);
          if (count($query_params)>0)
            $state = $page[0].'?'.http_build_query($query_params);
          else {
              $state = $page[0];
          }

        }
        //redirect to last page user was on
        wp_redirect($state);
        exit;
      }
    }

    $fmc_special_page_caught['type'] = "oauth-login";
    $fmc_special_page_caught['page-title'] = flexmlsConnect::make_nice_address_title($listing);
    $fmc_special_page_caught['post-title'] = flexmlsConnect::make_nice_address_title($listing);
    $fmc_special_page_caught['page-url'] = flexmlsConnect::make_nice_address_url($listing);
  }


  function generate_page() {
    global $fmc_api;
    global $fmc_special_page_caught;
    global $fmc_api_portal;

    // if we got this far, it's because of an error with the OAuth grant

    ob_start();

    // disable display of the H1 entry title on this page only
    echo "<style type='text/css'>\n  .entry-title { display:none; }\n</style>\n\n\n";

    echo "Error with OAuth access grant:<br><br>\n\n";

    echo "Code: ". $fmc_api_portal->last_error_code ."<br>\n";
    echo "Message: ". $fmc_api_portal->last_error_mess ."<br>\n";


    $content = ob_get_contents();
    ob_end_clean();

    return $content;
  }


}
