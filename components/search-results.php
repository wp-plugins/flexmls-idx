<?php



class fmcSearchResults extends fmcWidget {

  function fmcSearchResults() {

    parent::__construct();

    global $fmc_widgets;

    $widget_info = $fmc_widgets[ get_class($this) ];

    $widget_ops = array( 'description' => $widget_info['description'] );

    // have WP replace instances of [first_argument] with the return from the second_argument function
    add_shortcode($widget_info['shortcode'], array(&$this, 'shortcode'));

    // register where the AJAX calls should be routed when they come in
    add_action('wp_ajax_'.get_class($this).'_shortcode', array(&$this, 'shortcode_form') );
    add_action('wp_ajax_'.get_class($this).'_shortcode_gen', array(&$this, 'shortcode_generate') );

  }

  // Called by shortcode() to create the content
  function jelly($args, $settings, $type) {
    global $fmc_api;
    global $fmc_plugin_url;

    // sets up $before_title, $after_title, $before_widget, $after_widget
    extract($args);

    if ($type == "widget" && empty($settings['title']) && flexmlsConnect::use_default_titles()) {
      $settings['title'] = "Listings";
    }

    $title              = isset($settings['title']) ? ($settings['title']) : '';
    $source             = isset($settings['source']) ? trim($settings['source']) : '';
    $display            = isset($settings['display']) ? trim($settings['display']) : '';
    $days               = isset($settings['days']) ? trim($settings['days']) :  '';
    $property_type      = isset($settings['property_type']) ? trim($settings['property_type']): '';
    $property_sub_type  = isset($settings['property_sub_type']) ? trim($settings['property_sub_type']): '';
    $link               = isset($settings['link']) ? trim($settings['link']) : '';
    $sort               = isset($settings['sort']) ? trim($settings['sort']) : '';
    $agent              = isset($settings['agent']) ? trim($settings['agent']) : '';
    $status             = isset($settings['status']) ? trim($settings['status']) : '';

    $locations = ''; 

    if ( isset($settings['location']) ) { 
      $locations = html_entity_decode( flexmlsConnect::clean_comma_list( stripslashes( $settings['location'] )  ) );
    }

    if ($link == "default") {
      $link = flexmlsConnect::get_default_idx_link();
    }

    $source = (empty($source)) ? "my" : $source;

    $pure_conditions = array();

    if (isset($settings['days'])){
      $days = $settings['days'];
    }
    elseif ($display == "open_houses"){
      //For backward compatibility. Set # of days for open house default to 10
      $days = 10;
    }
    else{
      $days = 1;
      if (date("l") == "Monday")
        $days = 3;
    }


    $flexmls_temp_date = date_default_timezone_get();
    date_default_timezone_set('America/Chicago');
    $specific_time = date("Y-m-d\TH:i:s.u",strtotime("-".$days." days"));
    date_default_timezone_set($flexmls_temp_date);

    if ($display == "new") {
      $pure_conditions["OnMarketDate"] = $specific_time;
    }
    elseif ($display == "open_houses") {
      $pure_conditions['OpenHouses'] = $days;
    }
    elseif ($display == "price_changes") {
      $pure_conditions["PriceChangeTimestamp"] = $specific_time;
    }
    elseif ($display == "recent_sales") {
      $pure_conditions["StatusChangeTimestamp"] = $specific_time;
    }

    if ($sort == "recently_changed") {
      $pure_conditions['OrderBy'] = "-ModificationTimestamp"; // special tag caught later
    }
    elseif ($sort == "price_low_high") {
      $pure_conditions['OrderBy'] = "+ListPrice";
    }
    elseif ($sort == "price_high_low") {
      $pure_conditions['OrderBy'] = "-ListPrice";
    }
   elseif ($sort == "open_house"){
      $pure_conditions['OrderBy'] = "+OpenHouses";
    }
    elseif ($sort == "sqft_low_high") {
      $pure_conditions['OrderBy'] = "+BuildingAreaTotal";
    }
    elseif ($sort == "sqft_high_low") {
      $pure_conditions['OrderBy'] = "-BuildingAreaTotal";
    }
    elseif ($sort == "year_built_high_low") {
      $pure_conditions['OrderBy'] = "-YearBuilt";
    }
    elseif ($sort == "year_built_low_high") {
      $pure_conditions['OrderBy'] = "+YearBuilt";
    }

    $apply_property_type = ($source == 'location') ? true : false;

    if ($source == 'agent') {
      $pure_conditions['ListAgentId'] = $agent;
    }

    // parse location search settings
    $locations = flexmlsConnect::parse_location_search_string($locations);

    foreach ($locations as $loc) {
      if(array_key_exists($loc['f'], $pure_conditions)) {
        $pure_conditions[$loc['f']] .=  ',' . $loc['v'];
      } else {
        $pure_conditions[$loc['f']] = $loc['v'];
      }
    }

    if ($apply_property_type and !empty($property_type)) {
      $pure_conditions['PropertyType'] = $property_type;
      $pure_conditions['PropertySubType'] = $property_sub_type;
    }

    if ($link) {
      $link_details = $fmc_api->GetIDXLinkFromTinyId($link);
      if ($link_details['LinkType'] == "SavedSearch") {
          $pure_conditions['SavedSearch'] = $link_details['SearchId'];
        }
    }

    if ($source == "my") {
      // make a simple request to /my/listings with no _filter's
      $pure_conditions['My'] = 'listings';
    }
    elseif ($source == "office") {
      $pure_conditions['My'] = 'office';
    }
    elseif ($source == "company") {
      $pure_conditions['My'] = 'company';
    }

    if ($status) {
      $pure_conditions["StandardStatus"] = $status;
    }

    $custom_page = new flexmlsConnectPageSearchResults($fmc_api);
    $custom_page->title = $title;
    $custom_page->input_source = 'shortcode';
    $custom_page->input_data = $pure_conditions;
    $custom_page->pre_tasks(null);
    return $custom_page->generate_page(true);

  }

  function widget($args, $instance) {
    echo $this->jelly($args, $instance, "widget");
  }


  function shortcode($attr = array()) {

    $args = array(
        'before_title' => '<h3>',
        'after_title' => '</h3>',
        'before_widget' => '',
        'after_widget' => ''
        );

    return $this->jelly($args, $attr, "shortcode");

  }


  function admin_view_vars() {
    global $fmc_api;
    global $fmc_plugin_dir;

    $api_my_account = $fmc_api->GetMyAccount();

    $vars = array();

    $vars["source_options"] = array();

    $my_company_id = flexmlsConnect::get_company_id();


    if ( flexmlsConnect::is_agent() ) {
      $vars["source_options"]['my'] = "My Listings";
      $vars["source_options"]['office'] = "My Office's Listings";
      if ( !empty($my_company_id) ) {
        $vars["source_options"]['company'] = "My Company's Listings";
      }
    }

    if ( flexmlsConnect::is_office() ) {
      $vars["source_options"]['office'] = "My Office's Listings";
      if ( !empty($my_company_id) ) {
        $vars["source_options"]['company'] = "My Company's Listings";
      }
      $vars["source_options"]['agent'] = "Specific agent";
      $vars["office_roster"] = $fmc_api->GetAccountsByOffice( $api_my_account['Id'] );
    }

    if ( flexmlsConnect::is_company() ) {
      $vars["source_options"]['company'] = "My Company's Listings";
    }

    $vars["display_options"] = array(
        "all" => "All Listings",
        "new" => "New Listings",
        "open_houses" => "Open Houses",
        "price_changes" => "Recent Price Changes",
        "recent_sales" => "Recent Sales"
    );

    $vars["display_day_options"] = array(
            null => "1 (3 on Monday)",
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
            7 => 7,
            8 => 8,
            9 => 9,
            10 => 10,
            11 => 11,
            12 => 12,
            13 => 13,
            14 => 14,
            15 => 15,
        );

    $vars["sort_options"] = array(
        "recently_changed" => "Recently changed first",
        "price_low_high" => "Price, low to high",
        "price_high_low" => "Price, high to low",
        "year_built_low_high" => "Year Built, low to high",
        "year_built_high_low" => "Year Built, high to low",
        "sqft_low_high" => "SqFt, low to high",
        "sqft_high_low" => "SqFt, high to low",
	"open_house" => "Open House"
    );

    $possible_destinations = flexmlsConnect::possible_destinations();

    if (empty($destination)) {
      $destination = 'remote';
    }

    $vars["api_property_type_options"] = $fmc_api->GetPropertyTypes();
    $vars["api_property_sub_type_options"] = $fmc_api->GetPropertySubTypes();
    
    $vars["api_system_info"] = $fmc_api->GetSystemInfo();
    $vars["api_location_search_api"] = flexmlsConnect::get_locationsearch_url();

    if ($vars["api_property_type_options"] === false || $vars["api_system_info"] === false || $vars["api_location_search_api"] === false || $api_my_account === false) {
      return flexmlsConnect::widget_not_available($fmc_api, true);
    }

    if (!$fmc_api->HasBasicRole()) {
      $vars["source_options"]['location'] = "Location";
    }

    $vars["special_neighborhood_title_ability"] = null;
    if (array_key_exists('_instance_type', $this->instance) && $this->instance['_instance_type'] == "shortcode") {
      $vars["special_neighborhood_title_ability"] = flexmlsConnect::special_location_tag_text();
    }

    $vars["standard_status"] = new fmcStandardStatus($fmc_api->GetStandardField("StandardStatus"));

    return $vars;

  }

}
