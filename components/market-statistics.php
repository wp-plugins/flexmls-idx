<?php

class fmcMarketStats extends fmcWidget {

  private $stat_types = array(
      "absorption" => array(),
      "inventory" => array(
          array("label" => "Number of Active Listings", "value" => "ActiveListings", "selected" => true),
          array("label" => "Number of New Listings", "value" => "NewListings", "selected" => true),
          array("label" => "Number of Pended Listings", "value" => "PendedListings"),
          array("label" => "Number of Sold Listings", "value" => "SoldListings")
        ),
      "price" => array(
          array("label" => "Active Avg List Price (in Dollars)", "value" => "ActiveAverageListPrice", "selected" => true),
          array("label" => "New Avg List Price (in Dollars)", "value" => "NewAverageListPrice", "selected" => true),
          array("label" => "Pended Avg List Price (in Dollars)", "value" => "PendedAverageListPrice"),
          array("label" => "Sold Avg List Price (in Dollars)", "value" => "SoldAverageListPrice"),
          array("label" => "Sold Avg Sale Price (in Dollars)", "value" => "SoldAverageSoldPrice"),
          array("label" => "Active Median List Price (in Dollars)", "value" => "ActiveMedianListPrice", "selected" => true),
          array("label" => "New Median List Price (in Dollars)", "value" => "NewMedianListPrice", "selected" => true),
          array("label" => "Pended Median List Price (in Dollars)", "value" => "PendedMedianListPrice"),
          array("label" => "Sold Median List Price (in Dollars)", "value" => "SoldMedianListPrice"),
          array("label" => "Sold Median Sale Price (in Dollars)", "value" => "SoldMedianSoldPrice")
        ),
      "ratio" => array(
          array("label" => "Sale to Original List Price (Percentage)", "value" => "SaleToOriginalListPriceRatio", "selected" => true),
          array("label" => "Sale to List Price (Percentage)", "value" => "SaleToListPriceRatio")
        ),
      "dom" => array(
          array("label" => "Average CDOM (in Days)", "value" => "AverageCdom", "selected" => true),
          array("label" => "Average ADOM (in Days)", "value" => "AverageDom")
        ),
      "volume" => array(
          array("label" => "Active List Volume (in Dollars)", "value" => "ActiveListVolume", "selected" => true),
          array("label" => "New List Volume (in Dollars)", "value" => "NewListVolume", "selected" => true),
          array("label" => "Pended List Volume (in Dollars)", "value" => "PendedListVolume"),
          array("label" => "Sold List Volume (in Dollars)", "value" => "SoldListVolume"),
          array("label" => "Sold Sale Volume (in Dollars)", "value" => "SoldSaleVolume")
        )
      );
  

  function __construct() {
    global $fmc_widgets;

    $widget_info = $fmc_widgets[ get_class($this) ];

    $widget_ops = array( 'description' => $widget_info['description'] );
    WP_Widget::__construct( get_class($this) , $widget_info['title'], $widget_ops);

    // have WP replace instances of [first_argument] with the return from the second_argument function
    add_shortcode($widget_info['shortcode'], array(&$this, 'shortcode'));

    // register where the AJAX calls should be routed when they come in
    add_action('wp_ajax_'.get_class($this).'_shortcode', array(&$this, 'shortcode_form') );
    add_action('wp_ajax_'.get_class($this).'_shortcode_gen', array(&$this, 'shortcode_generate') );

  }


  function jelly($args, $settings, $type) {
    global $fmc_api;

    extract($args);

    $all_stat_types = $this->stat_types;
    // add to the list only for display purposes
    $all_stat_types['absorption'][] = array('label' => 'Absorption Rate (in Months)', 'value' => 'AbsorptionRate');

    if ($type == "widget" && empty($settings['title']) && flexmlsConnect::use_default_titles()) {
      $settings['title'] = "Market Statistics";
    }

    $title = trim($settings['title']);
    $width = trim($settings['width']);
    $height = trim($settings['height']);
    $stat_type = trim($settings['type']);
    $property_type = trim($settings['property_type']);
    $display = trim($settings['display']);
    $location = html_entity_decode(trim($settings['location']));

    $displays_selected = explode(",", $display);

    if ( empty($stat_type) || ( $stat_type != "absorption" && empty($display)) ) {
      return flexmlsConnect::widget_missing_requirements("Market Statistics", "Type and Display");
    }

    $locations = flexmlsConnect::parse_location_search_string($location);

    $loc_name = "";
    $loc_value_nice = "";

    foreach ($locations as $loc) {
        $loc_name = $loc['f'];
        $loc_value_nice = $loc['v'];
    }

    $return = '';

    $api_market_stats = $fmc_api->GetMarketStats($stat_type, $display, $property_type, $loc_name, $loc_value_nice);
    
    if ($api_market_stats === false) {
      return flexmlsConnect::widget_not_available($fmc_api, false, $args, $settings);
    }
    

    if (isset($auto_rotate) && $auto_rotate != 0 && $auto_rotate < 1000) {
      $auto_rotate = $auto_rotate * 1000;
    }

    if ($type == "widget") {
      $default_width = 200;
      $default_height = 200;
    }
    else {
      $default_width = 480;
      $default_height = 200;
    }

    if (empty($width)) {
      $width = $default_width;
    }
    if (empty($height)) {
      $height = $default_height;
    }

    $return .= $before_widget;

    if ( !empty($title) ) {
      $return .= $before_title;
      $return .= $title;
      $return .= $after_title;
    }

    $return .= '
  <div class="flexmls_connect__market_stats">

    <div class="flexmls_connect__market_stats_graph" style="max-width: '.$width.'px; height:'.$height.'px;"></div>
    <div class="flexmls_connect__market_stats_legend" style="max-width: '.$width.'px;"></div>
    <ul>';

    foreach ($all_stat_types[$stat_type] as $opt) {
      $label = $opt['label'];
      $code = $opt['value'];

      if ( is_array($api_market_stats[$code]) ) {
        $stats = $api_market_stats[$code];

        krsort($stats);
        $stat_val = array();
        foreach ($stats as $st) {
          if (empty($st)) {
            $st = 0;
          }
          $stat_val[] = $st;
        }
        $return .= "<li data-connect-label='{$label}'>".implode(",", $stat_val)."</li>";
      }
    }

    $return .= '
    </ul>
    <p class="flexmls_connect__disclaimer">Information is deemed to be reliable, but is not guaranteed. &copy; '.date("Y").'</p>
  </div>
          ';

    $return .= $after_widget;

    return $return;

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


  function settings_form($instance) {
    global $fmc_api;

    $title =          array_key_exists('title', $instance) ? esc_attr($instance['title']) : "";
    $width =          array_key_exists('width', $instance) ? esc_attr($instance['width']) : "";
    $height =         array_key_exists('height', $instance) ? esc_attr($instance['height']) : "";
    $type =           array_key_exists('type', $instance) ? esc_attr($instance['type']) : "";
    $property_type =  array_key_exists('property_type', $instance) ? esc_attr($instance['property_type']) : "";
    $display =        array_key_exists('display', $instance) ? esc_attr($instance['display']) : "";
    $location =       array_key_exists('location', $instance) ? $instance['location'] : "";


    $display_selected = explode(",", $display);

    $selected_code = " selected='selected'";

    $type_options = array(
        "absorption" => "Absorption Rate",
        "inventory" => "Inventory",
        "price" => "Prices",
        "ratio" => "Sale to Original List Price Ratio",
        "dom" => "Sold DOM",
        "volume" => "Volume"
    );

    $api_property_type_options = $fmc_api->GetPropertyTypes();
    $api_system_info = $fmc_api->GetSystemInfo();
    $api_location_search_api = flexmlsConnect::get_locationsearch_url();

    if ($api_property_type_options === false || $api_system_info === false || $api_location_search_api === false) {
      return flexmlsConnect::widget_not_available($fmc_api, true);
    }

    $display_options = array();

    if (is_array($this->stat_types) && array_key_exists($type, $this->stat_types)) {
      $these_display_options = $this->stat_types[$type];
    }
    else {
      $these_display_options = array();
    }
    foreach ($these_display_options as $opt) {
      $display_options[$opt['value']] = $opt['label'];
    }
    
    $special_neighborhood_title_ability = null;
    if (array_key_exists('_instance_type', $instance) && $instance['_instance_type'] == "shortcode") {
      $special_neighborhood_title_ability = flexmlsConnect::special_location_tag_text();
    }

    $return = "";

    $return .= "

      <p>
        <label for='".$this->get_field_id('title')."'>" . __('Title:') . "</label>
        <input fmc-field='title' fmc-type='text' type='text' class='widefat' id='".$this->get_field_id('title')."' name='".$this->get_field_name('title')."' value='{$title}' />
        $special_neighborhood_title_ability
      </p>

      <p>
        <label for='".$this->get_field_id('width')."'>" . __('Width:') . "</label>
        <input fmc-field='width' fmc-type='text' type='text' id='".$this->get_field_id('width')."' name='".$this->get_field_name('width')."' value='{$width}' />
      </p>

      <p>
        <label for='".$this->get_field_id('height')."'>" . __('Height:') . "</label>
        <input fmc-field='height' fmc-type='text' type='text' id='".$this->get_field_id('height')."' name='".$this->get_field_name('height')."' value='{$height}' />
      </p>

      <p>
        <label for='".$this->get_field_id('type')."'>" . __('Type:') . "</label>
          <select fmc-field='type' fmc-type='select' id='".$this->get_field_id('type')."' name='".$this->get_field_name('type')."' class='flexmls_connect__stat_type'>
            ";

    foreach ($type_options as $k => $v) {
      $is_selected = ($k == $type) ? $selected_code : "";
      $return .= "<option value='{$k}'{$is_selected}>{$v}</option>";
    }

    $return .= "
          </select><br /><span class='description'>Which type of chart to display</span>
      </p>

      <p>
        <label for='".$this->get_field_id('display')."'>" . __('Display:') . "</label>
          <select fmc-field='display' fmc-type='select' id='".$this->get_field_id('display')."' name='".$this->get_field_name('display')."[]' class='flexmls_connect__stat_display' style='height: 110px;' size='5' multiple='multiple'>
            ";

    foreach ($display_options as $k => $v) {
      $is_selected = (in_array($k, $display_selected)) ? $selected_code : "";
      $return .= "<option value='{$k}'{$is_selected}>{$v}</option>";
    }

    $return .= "
          </select><br /><span class='description'>What statistics to display</span>
      </p>

      <p>
        <label for='".$this->get_field_id('property_type')."'>" . __('Property Type:') . "</label>
        <select fmc-field='property_type' fmc-type='select' id='".$this->get_field_id('property_type')."' name='".$this->get_field_name('property_type')."' class='flexmls_connect__property_type'>
            ";

    $return .= "<option value=''>All</option>";
    foreach ($api_property_type_options as $k => $v) {
      $is_selected = ($k == $property_type) ? $selected_code : "";
      $return .= "<option value='{$k}'{$is_selected}>{$v}</option>";
    }

    $return .= "
        </select>
      </p>

      <p class='flexmls_connect__location'>
        <label for='".$this->get_field_id('location')."'>" . __('Location:') . "</label> 
          <input type='text' data-connect-url='{$api_location_search_api}' class='flexmls_connect__location_search'
              autocomplete='off' id='".$this->get_field_id('location')."' name='".$this->get_field_name('location_input')."'
              value='City, Postal Code, etc.'>
          <a href='javascript:void(0);' title='Click here to browse through available locations' class='flexmls_connect__location_browse'>Browse &raquo;</a>
        <br />
        <div class='flexmls_connect__location_list' data-connect-multiple='false'>
          <p>All Locations Included</p>
        </div>
        <input type='hidden' name='tech_id' class='flexmls_connect__tech_id' value=\"x'{$api_system_info['Id']}'\" />
        <input type='hidden' name='ma_tech_id' class='flexmls_connect__ma_tech_id' value=\"x'". flexmlsConnect::fetch_ma_tech_id() ."'\" />
        <input fmc-field='location' fmc-type='text' type='hidden' name='".$this->get_field_name('location')."' class='flexmls_connect__location_fields' value=\"{$location}\" />
      </p>
      
      <img src='x' class='flexmls_connect__bootloader' onerror='flexmls_connect.location_setup(this);' />
          ";

    $return .= "<input type='hidden' name='shortcode_fields_to_catch' value='title,width,height,type,display,property_type,location' />";
    $return .= "<input type='hidden' name='widget' value='". get_class($this) ."' />";

    return $return;
  }



  function update($new_instance, $old_instance) {
    $instance = $old_instance;

    $instance['title'] = strip_tags($new_instance['title']);
    $instance['width'] = strip_tags($new_instance['width']);
    $instance['height'] = strip_tags($new_instance['height']);
    $instance['type'] = strip_tags($new_instance['type']);
    $instance['property_type'] = strip_tags($new_instance['property_type']);
    $instance['display'] = implode(",", array_map('strip_tags', $new_instance['display']));
    $instance['location'] = strip_tags($new_instance['location']);

    return $instance;
  }


}
