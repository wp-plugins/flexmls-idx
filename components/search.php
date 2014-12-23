<?php



class fmcSearch extends fmcWidget {

  protected $widget_settings;

  function fmcSearch() {
    global $fmc_widgets;

    $widget_info = $fmc_widgets[ get_class($this) ];

    // set the view template for the widget
    $this->page_view = $widget_info['page_view'];

    $widget_ops = array( 'description' => $widget_info['description'] );
    $this->WP_Widget( get_class($this) , $widget_info['title'], $widget_ops);

    // have WP replace instances of [first_argument] with the return from the second_argument function
    add_shortcode($widget_info['shortcode'], array(&$this, 'shortcode'));

    // register where the AJAX calls should be routed when they come in
    add_action('wp_ajax_'.get_class($this).'_shortcode', array(&$this, 'shortcode_form') );
    add_action('wp_ajax_'.get_class($this).'_shortcode_gen', array(&$this, 'shortcode_generate') );

  }


  function jelly($args, $settings, $type) {
    global $fmc_api;

    extract($args);

    $this->widget_settings = $settings;

    if ($type == "widget" && empty($settings['title']) && flexmlsConnect::use_default_titles()) {
      $settings['title'] = "IDX Search";
    }

    $rand = mt_rand();

    // presentation variables from settings
    $title = isset($settings['title']) ? trim($settings['title']) : null;
    $my_link = isset($settings['link']) ? ($settings['link']): null;
    $buttontext = (array_key_exists('buttontext', $settings) and !empty($settings['buttontext'])) ? htmlspecialchars(trim($settings['buttontext']), ENT_QUOTES) : "Search";
    $detailed_search = trim($settings['detailed_search']);
    $detailed_search_text = (array_key_exists('detailed_search_text', $settings) and !empty($settings['detailed_search_text'])) ? trim($settings['detailed_search_text']) : "More Search Options" ;
    // destination="local"
    $location_search = trim($settings['location_search']);
    $user_sorting = trim($settings['user_sorting']);
    $property_type_enabled = (array_key_exists('property_type_enabled', $settings)) ? trim($settings['property_type_enabled']) : "on" ;
    $property_type = isset($settings['property_type'])? trim($settings['property_type']) : null;
    $property_types_selected = explode(",", $property_type);
    $std_fields = isset($settings['std_fields'])? trim($settings['std_fields']) : null;
    $std_fields_selected = explode(",", $std_fields);
    // theme="vert_round_dark"
    $orientation = (array_key_exists('orientation', $settings)) ? trim($settings['orientation']) : "horizontal" ;
    
    $width = ($orientation == "horizontal") ? 760 : 360;
    if (array_key_exists('width', $settings)) {
      $width = trim($settings['width']) - 40;
    } 
    
    $border_style = (array_key_exists('border_style', $settings)) ? trim($settings['border_style']) : "squared" ;
    $widget_drop_shadow = (array_key_exists('widget_drop_shadow', $settings)) ? trim($settings['widget_drop_shadow']) : "on" ;
    
    $background_color = fmcSearch::get_setting_color('background_color');
    $title_text_color = fmcSearch::get_setting_color('title_text_color');
    $field_text_color = fmcSearch::get_setting_color('field_text_color');
    $detailed_search_text_color = fmcSearch::get_setting_color('detailed_search_text_color');

    $submit_button_shine = (array_key_exists('submit_button_shine', $settings)) ? trim($settings['submit_button_shine']) : "shine" ;
    
    $submit_button_background = fmcSearch::get_setting_color('submit_button_background');
    $submit_button_text_color = fmcSearch::get_setting_color('submit_button_text_color');

    $title_font = (array_key_exists('title_font', $settings)) ? trim($settings['title_font']) : "Arial" ;
    $field_font = (array_key_exists('field_font', $settings)) ? trim($settings['field_font']) : "Arial" ;
    $destination = (array_key_exists('destination', $settings)) ? trim($settings['destination']) : "local" ;

    // API variables
    $api_prop_types = $fmc_api->GetPropertyTypes();
    $api_property_sub_types = $fmc_api->GetPropertySubTypes();
    $api_system_info = $fmc_api->GetSystemInfo();
    $api_location_search_api = flexmlsConnect::get_locationsearch_url();
    $api_links = flexmlsConnect::get_all_idx_links();

    if ($api_prop_types === false || $api_system_info === false || $api_location_search_api === false || $api_links === false) {
      return flexmlsConnect::widget_not_available($fmc_api, false, $args, $settings);
    }

    if ($my_link == "default") {
      $my_link = flexmlsConnect::get_default_idx_link();
    }

    $good_link = false;
    foreach ($api_links as $link) {
      if ($link['LinkId'] == $my_link) {
        $good_link = true;
      }
    }

    if (!$good_link) {
      return flexmlsConnect::widget_not_available($fmc_api, false, $args, $settings);
    }

    $search_fields = array();

    $this_target = "";
    if (flexmlsConnect::get_destination_window_pref() == "new") {
      $this_target = " target='_blank'";
    }

    $idx_link_details = flexmlsConnect::get_idx_link_details($my_link);
    $detailed_search_url = flexmlsConnect::make_destination_link($idx_link_details['Uri']);


    // set border radius code
    $border_radius = "";
    if ($border_style == "rounded")
      $border_radius = "border-radius:8px;-moz-border-radius:8px;-webkit-border-radius:8px;";

    // set shadow
    $box_shadow_class = "";
    if ($widget_drop_shadow == "on") {
      $box_shadow_class = 'flexmls_connect__search_new_shadow';
    }

    // submit button CSS
    $text_shadow = ($submit_button_text_color == "#FFFFFF") ? "#111" : "#eee" ;
    $submit_button_css = "background:{$submit_button_background} !important; color: {$submit_button_text_color} !important;";
    if ($submit_button_shine == 'gradient') {
      $lighter = flexmlsConnect::hexLighter($submit_button_background, 40);
      $darker = flexmlsConnect::hexDarker($submit_button_background, 40);
      $dark_border_color = flexmlsConnect::hexDarker($submit_button_background, 60);
      $submit_button_css .= "border: 1px solid {$dark_border_color} !important;";
      $submit_button_css .= "text-shadow: 0 1px 1px {$text_shadow} !important;";
      $submit_button_css .= "background: -moz-linear-gradient(top, {$lighter} 0%, {$submit_button_background} 44%, {$darker} 100%) !important;";
      $submit_button_css .= "background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,{$lighter}), color-stop(44%,{$submit_button_background}), color-stop(100%,{$darker})) !important;";
      $submit_button_css .= "background: -webkit-linear-gradient(top, {$lighter} 0%,{$submit_button_background} 44%,{$darker} 100%) !important;";
      $submit_button_css .= "background: -o-linear-gradient(top, {$lighter} 0%,{$submit_button_background} 44%,{$darker} 100%) !important;";
      $submit_button_css .= "background: -ms-linear-gradient(top, {$lighter} 0%,{$submit_button_background} 44%,{$darker} 100%) !important;";
      $submit_button_css .= "background: linear-gradient(top, {$lighter} 0%,{$submit_button_background} 44%,{$darker} 100%) !important;";

    } else if ($submit_button_shine == 'shine') {
      $light = flexmlsConnect::hexLighter($submit_button_background, 20);
      $lighter = flexmlsConnect::hexLighter($submit_button_background, 30);
      $dark = flexmlsConnect::hexDarker($submit_button_background, 10);
      $darker = flexmlsConnect::hexDarker($submit_button_background, 30);
      $submit_button_css .= "text-shadow: 0 1px 1px {$text_shadow} !important;";
      $submit_button_css .= "box-shadow: 0 1px 1px #111 !important; -webkit-box-shadow: 0 1px 1px #111 !important; -moz-box-shadow: 0 1px 1px #111 !important;";
      $submit_button_css .= "background: -moz-linear-gradient(top, {$light} 0%, {$lighter} 50%, {$dark} 51%, {$darker} 100%) !important;";
      $submit_button_css .= "background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,{$light}), color-stop(50%,{$lighter}), color-stop(51%,{$dark}), color-stop(100%,{$darker})) !important;";
      $submit_button_css .= "background: -webkit-linear-gradient(top, {$light} 0%,{$lighter} 50%,{$dark} 51%,{$darker} 100%) !important;";
      $submit_button_css .= "background: -o-linear-gradient(top, {$light} 0%,{$lighter} 50%,{$dark} 51%,{$darker} 100%) !important;";
      $submit_button_css .= "background: -ms-linear-gradient(top, {$light} 0%,{$lighter} 50%,{$dark} 51%,{$darker} 100%) !important;";
      $submit_button_css .= "background: linear-gradient(top, {$light} 0%,{$lighter} 50%,{$dark} 51%,{$darker} 100%) !important;";
    }

    // Submit Return
    $submit_return  = "";

    // only include remote link information if necessary
    if ($destination == "remote") {
      $submit_return .= "<input type='hidden' name='fmc_do' value='fmc_search' />";
      $submit_return .= "<input type='hidden' name='link' class='flexmls_connect__link' value='{$my_link}' />";
      $submit_return .= "<input type='hidden' name='destlink' value='".flexmlsConnect::get_destination_link()."' />";
      $submit_return .= "<input type='hidden' name='destination' value='{$destination}' />";
      $submit_return .= "<input type='hidden' name='query' value='' />";
    } else {
      // include the link if it's a Saved Search - added 1-29-2013 by Brandon Medenwald (WP-137)
      if ($idx_link_details['LinkType'] == "SavedSearch") {
        $submit_return .= "<input type='hidden' name='SavedSearch' class='flexmls_connect__link 
          flexmls_connect__search_new_submit' value='{$idx_link_details['SearchId']}' />";
      }
    }

    $submit_return .= "<div style='visibility:hidden;' class='query' ></div>";
    $submit_return .= "<input type='hidden' class='flexmls_connect__tech_id' value=\"x'{$api_system_info['Id']}'\" />";
    $submit_return .= "<input type='hidden' class='flexmls_connect__ma_tech_id' value=\"x'". flexmlsConnect::fetch_ma_tech_id() ."'\" />";

    $submit_return .= "<div class='flexmls_connect__search_new_links'>";
    $submit_return .= "<input class='flexmls_connect__search_new_submit' type='submit' value='{$buttontext}' style='{$submit_button_css}' />";
    if ($detailed_search == "on") {
      $submit_return .= "<a href='{$detailed_search_url}' style='color:{$detailed_search_text_color};'{$this_target}>{$detailed_search_text}</a>";
    }
    $submit_return .= "</div>";
    


    // Property Types
    $good_prop_types = array();
    foreach ($api_prop_types as $k => $v) {
      if (in_array($k, $property_types_selected)) {
        $good_prop_types[] = $k;
      }
    }

    $user_selected_property_types = ( isset($_GET['PropertyType']) ) ? $_GET['PropertyType'] : array(); 

    $search_fields[] = "PropertyType";

    // set up prop sub types in a way that will be easy to output in the view
    $property_sub_types = array();
    foreach ($api_property_sub_types as $sub_type) {
      if ($sub_type['Name'] != "Select One") {
        foreach($sub_type['AppliesTo'] as $property_code) {
          if (array_key_exists($property_code, $property_sub_types)) {
            $property_sub_types[$property_code][] = $sub_type;
          } else  {
            $property_sub_types[$property_code] = array($sub_type);
          }
        }
      }
    }

    // output html from the template
    ob_start();
      require($this->page_view);
      $return = ob_get_contents();
    ob_end_clean();

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

    $selected_code = " selected='selected'";
    $hidden_code = " style='display:none;'";

    $hide_section = array();

    $setting_fields = fmcSearch::settings_fields();
    $settings = array();
    // pull, clean and compile all relevant settings for this form
    foreach ($setting_fields as $name => $details) {
      $value = null;

      if ( array_key_exists('default', $details) ) {
        $settings[$name] = $details['default'];
      }

      switch($details['type']) {

        case "color":
        case "select":
        case "text":
          $value = esc_attr($instance[$name]);
          break;

        case "list":
          $this_val = esc_attr( trim($instance[$name]) );

          if ( strlen($this_val) === 0 ) {
            $value = array();
          }
          else {
            $value = explode(",", $this_val);
          }
          break;

        case "enabler":
          $value = ($instance[$name] == "off") ? "off" : "on";
          $hide_section[$name] = ($value == "off") ? $hidden_code : null;
          break;
      }

      if ($value != null) {
        $settings[$name] = $value;
      }

    }


    $current_standard_fields = $settings['std_fields'];
    $possible_standard_fields = array(
        'age' => "Year Built",
        'baths' => "Bathrooms",
        'beds' => "Bedrooms",
        'square_footage' => "Square Footage",
        'list_price' => "Price"
    );


    $api_property_type_options = $fmc_api->GetPropertyTypes();

    $current_property_types = $settings['property_type'];
    $possible_property_types = $api_property_type_options;

    if ($api_property_type_options === false || $api_links === false) {
      return flexmlsConnect::widget_not_available($fmc_api, true);
    }


    $new_return = "";

    $on_section = null;

    foreach ($setting_fields as $name => $details) {

      $this_section = ( array_key_exists('section', $details) ) ? $details['section'] : null;

      // show section headings if we've switched to a new section
      if ($on_section != $this_section) {
        $new_return .= "<br/><div style='width:95%; background-color:white; margin:0px 0px 5px 0px; padding:5px 0px 5px 5px; border: 1px solid grey;'><b>{$this_section}</b></div>";
      }

      $group_show = null;
      $group_class = null;
      if ( !empty($details['field_grouping']) ) {
        $group_class = " class='flexmls_connect__disable_group_{$details['field_grouping']}'";
        $group_show = $hide_section[$details['field_grouping']];
      }

      $new_return .= "<div{$group_class}{$group_show}>";
      $new_return .= "<p>";

      $input_class = null;
      if ($details['input_width'] == 'full') {
        $details['input_width'] = null;
        $details['class'] .= " widefat";
      }

      // shortcut overrides
      // change enabler into a standard select box with preset options
      if ($details['type'] == "enabler") {
        $details['type'] = "select";
        $details['options'] = array(
            'on' => 'Enabled',
            'off' => 'Disabled'
        );
        $details['class'] = "flexmls_connect__setting_enabler_{$name}";
      }

      $input_value = htmlspecialchars($settings[$name], ENT_QUOTES);

      // change a color box into a standard text field with a few added options
      if ($details['type'] == "color") {
        $details['type'] = "text";
        $details['input_width'] = 6;
        $details['class'] = "wp-color-picker";

        if ($instance['_instance_type'] == 'shortcode')
          $label_class = "fmc_shortcode_field_label";
        else
          $label_class = "fmc_widget_field_label";

        if($input_value and strpos($input_value, '#') === false) {
          $input_value = '#' . $input_value;
        }
      }

      $input_class = null;
      if ( !empty($details['class']) ) {
        $input_class = " class='{$details['class']}'";
      }

      $new_return .= "<label for='".$this->get_field_id($name)."' class='{$label_class}'>" . 
        __($details['label']. ':') . "</label>";

      // text box
      if ($details['type'] == "text") {
        $new_return .= "<input fmc-field='{$name}' fmc-type='text' size='{$details['input_width']}' 
        type='text'{$input_class} id='".$this->get_field_id($name)."' name='".$this->get_field_name($name)."' 
        value='". $input_value . "'>";
      }
      // select box
      elseif ($details['type'] == "select") {
        $new_return .= "<select fmc-field='{$name}' fmc-type='select'{$input_class} id='".$this->get_field_id($name)."' name='".$this->get_field_name($name)."'>";
        foreach ($details['options'] as $k => $v) {
          $is_selected = ( $settings[$name] == $k ) ? $selected_code : null;
          $new_return .= "<option value='{$k}'{$is_selected}>{$v}</option>";
        }
        $new_return .= "</select>";
      }
      // sortable list selection
      elseif ($details['type'] == "list") {
        if ($name == "property_type") {
          $possible_list = $possible_property_types;
          $current_list = $current_property_types;
          $available_class = "available_types";
          $add_button_text = "Add Type";
          $add_button_class = "add_property_type";
        }
        elseif ($name == "std_fields") {
          $possible_list = $possible_standard_fields;
          $current_list = $current_standard_fields;
          $available_class = "available_fields";
          $add_button_text = "Add Field";
          $add_button_class = "add_std_field";
        }

        if ( !is_array($current_list) ) {
          $current_list = array();
        }

        $new_return .= "<div>";

        $new_return .= "<input fmc-field='{$name}' fmc-type='text' type='hidden' name='".$this->get_field_name($name)."' class='flexmls_connect__list_values' value='". implode(",", $current_list). "' />";
        $new_return .= "<ul class='flexmls_connect__sortable'>";

        foreach ($current_list as $k) {
          $show_label = ($name == "property_type") ? flexmlsConnect::nice_property_type_label($k) : $possible_list[$k];

          $new_return .= "<li data-connect-name='{$k}'>";
          $new_return .= "  <span class='remove' title='Remove this from the search'>&times;</span>";
          $new_return .= "  <span class='ui-icon ui-icon-arrowthick-2-n-s'></span>";
          $new_return .= "  {$show_label}";
          $new_return .= "</li>";
        }

        $new_return .= "</ul>";
        $new_return .= "<select name='".$this->get_field_name($available_class)."' class='flexmls_connect__available'>";

        foreach ($possible_list as $fk => $fv) {
          $show_label = ($name == "property_type") ? flexmlsConnect::nice_property_type_label($fk) : $fv;
          $new_return .= "<option value='{$fk}'>{$show_label}</option>";
        }

        $new_return .= "</select>";
        $new_return .= "<button title='Add this to the search' class='flexmls_connect__{$add_button_class}'>{$add_button_text}</button>";
        $new_return .= "<img src='x' class='flexmls_connect__bootloader' onerror='flexmls_connect.sortable_setup(this);' />";
        $new_return .= "</div>";

      }
      // other
      else {
        $new_return .= "{$details['type']}";
      }

      $new_return .= $details['after_input'];

      if ( !empty($details['description']) ) {
        $new_return .= "<br/><span class='description'>{$details['description']}</span>";
      }

      $new_return .= "</p>";
      $new_return .= "</div>";

    }

    $new_return .= "<script type='text/javascript'>
        // set up the color picker for the search widget
        jQuery('.wp-color-picker').wpColorPicker(); 
    </script>";

    $new_return .= "<input type='hidden' name='shortcode_fields_to_catch' value='". implode(",", array_keys($setting_fields) ) ."' />";
    $new_return .= "<input type='hidden' name='widget' value='". get_class($this) ."' />";

    return $new_return;

  }



  function update($new_instance, $old_instance) {
    $instance = $old_instance;

    $setting_fields = fmcSearch::settings_fields();

    foreach ($setting_fields as $name => $details) {

      if ($details['output'] == "text") {
        $instance[$name] = strip_tags($new_instance[$name]);
      }
      elseif ($details['output'] == "list") {
        $instance[$name] = implode(",", array_map('strip_tags', $new_instance[$name]) );
      }
      elseif ($details['output'] == "enabler") {
        $instance[$name] = ( $new_instance[$name] == "off" ) ? "off" : "on";
      }

    }

    return $instance;
  }


  function submit_search() {
    global $fmc_api;

    $destination_type = flexmlsConnect::wp_input_get_post('destination');
    if ( empty($destination_type) ) {
      $destination_type = 'remote';
    }

    if ($destination_type == 'local') {
      fmcSearch::handle_local_search();
    }
    else {
      fmcSearch::handle_remote_search();
    }

  }

  function handle_local_search() {
    global $fmc_api;

    $api_standard_fields = $fmc_api->GetStandardFields();
    $api_standard_fields = $api_standard_fields[0];

    $search_conditions = array();

    $translate_fields = array(
      'beds_from' => 'MinBeds',
      'beds_to' => 'MaxBeds',
      'baths_from' => 'MinBaths',
      'baths_to' => 'MaxBaths',
      'age_from' => 'MinYear',
      'age_to' => 'MaxYear',
      'square_footage_from' => 'MinSqFt',
      'square_footage_to' => 'MaxSqFt',
      'list_price_from' => 'MinPrice',
      'list_price_to' => 'MaxPrice'
    );

    foreach ($translate_fields as $local_field => $search_field) {
      $this_value = flexmlsConnect::wp_input_get_post($local_field);
      if ( !empty($this_value) and $this_value != 'Min' and $this_value != 'Max' ) {
        $search_conditions[$search_field] = $this_value;
      }
    }

    parse_str( flexmlsConnect::wp_input_get_post('query') , $query_parts);

    // fetch other interesting values from the provided submission.
    // mainly to catch location search values and the selected property types
    foreach ($query_parts as $part_key => $part_value) {
      if ( array_key_exists($part_key, $api_standard_fields) and !empty($part_value) ) {
        $search_conditions[$part_key] = stripslashes($part_value);
      }
    }

    // turn IDX link into SavedSearch condition if needed
    $selected_idx_link = flexmlsConnect::wp_input_get_post('link');
    $link_details = flexmlsConnect::get_idx_link_details($selected_idx_link);

    if ( array_key_exists('SearchId', $link_details) and !empty($link_details['SearchId']) ) {
      $search_conditions['SavedSearch'] = $link_details['SearchId'];
    }

    wp_redirect( flexmlsConnect::make_nice_tag_url('search', $search_conditions) );
    exit;

  }

  function handle_remote_search() {
    global $fmc_api;
    /* @var $fmc_api flexmlsAPI_Core */

    // translate from form field names to standard names
    $fields_to_catch = array(
        'PropertyType' => 'PropertyType',
        'MapOverlay' => 'MapOverlay',
        'list_price' => 'ListPrice',
        'Beds' => 'BedsTotal',
        'Baths' => 'BathsTotal',
        'Year' => 'YearBuilt',
        'SqFt' => 'BuildingAreaTotal',
        'Price' => 'ListPrice'
    );

    $standard_fields = $fmc_api->GetStandardFields();
    $standard_fields = $standard_fields[0];

    if (array_key_exists('BathsTotal', $standard_fields)) {
      if (array_key_exists('MlsVisible', $standard_fields['BathsTotal']) and empty($standard_fields['BathsTotal']['MlsVisible'])) {
        $fields_to_catch['Baths'] = "BathsFull";
      }
    }

    $query = stripslashes($_POST['query']);
    $my_link = stripslashes($_POST['link']);

    $query_conditions = array();
    $std_query_conditions = array();

    // break the 'query' value apart and start saving the operators and values separately
    $conds = explode("&", $query);
    foreach ($conds as $c) {
      $key = "";
      $value = "";
      $operator = "";

      // check for the special operators
      if ( strpos($c, ">=") !== false ) {
        $operator = ">=";
      }
      elseif ( strpos($c, "<=") !== false ) {
        $operator = "<=";
      }
      else {
        $operator = "=";
      }

      // break the key/value apart based on the operator found
      list($key, $value) = explode($operator, $c, 2);

      $vals = explode(",", $value);
      $vals = array_map( array('flexmlsConnect', 'strip_quotes') , $vals);
      $vals = array_map( array('flexmlsConnect', 'remove_starting_equals') , $vals);
      $value = implode(",", $vals);

      $query_conditions[$key] = array('v' => $value, 'o' => $operator);

    }

    // build the transform link and map fields to their standard names
    $link_transform_params = array();
    foreach ($query_conditions as $k => $qc) {
      if (array_key_exists($k, $fields_to_catch)) {
        $std_cond_field = $fields_to_catch[$k];
      }
      else {
        $std_cond_field = $k;
      }
      if ($std_cond_field) {
        $std_query_conditions[$std_cond_field] = array('v' => $qc['v'], 'o' => $qc['o']);
        $link_transform_params[$std_cond_field] = "*{$std_cond_field}*";
      }
    }

    // get transformed link
    $outbound_link = $fmc_api->GetTransformedIDXLink($my_link, $link_transform_params);

    // change the placeholders back to actual values with the given operator
    foreach ($std_query_conditions as $k => $sqc) {
      $outbound_link = str_replace("=*{$k}*", $sqc['o'] . $sqc['v'], $outbound_link);
    }

    // take out all remaining placeholders
    $outbound_link = preg_replace('/\*(.*?)\*/', "", $outbound_link);

    $outbound_link = urlencode($outbound_link);

    $permalink = stripslashes($_POST['destlink']);

    if (strpos($permalink, '?') !== false) {
      $outbound_link = $permalink . '&url=' . $outbound_link;
    }
    else {
      $outbound_link = $permalink . '?url=' . $outbound_link;
    }

    // forward the user on if we have someplace for them to go
    if (!empty($outbound_link)) {

      //change StreetAddress parameter to streetname for Smart Frame URL
      if(strpos($outbound_link, 'StreetAddress')){
        $outbound_link = str_replace('StreetAddress', 'streetaddress', $outbound_link);
      }
      header("Location: {$outbound_link}");
      exit;
    }

  }

  function settings_fields() {
    global $fmc_api;


    $api_links = flexmlsConnect::get_all_idx_links();
    $idx_links = array();

    foreach ($api_links as $l_d) {
      $idx_links[$l_d['LinkId']] = $l_d['Name'];
    }


    $settings_fields = array(
        // main
        'title' => array(
         'label' => 'Title',
         'type' => 'text',
         'output' => 'text', // legacy
         'input_width' => 'full',
         ),
        'link' => array(
         'label' => 'IDX Link',
         'type' => 'select',
         'options' => $idx_links,
         'output' => 'text', // legacy
         'description' => 'Link used when search is executed',
         'input_width' => 'full',
         ),
        'buttontext' => array(
         'label' => 'Submit Button Text',
         'type' => 'text',
         'output' => 'text', // legacy
         'input_width' => 'full',
         'description' => '(ex. "Search for Homes")'
         ),
        'detailed_search' => array(
         'label' => 'Detailed Search',
         'type' => 'enabler',
         'output' => 'enabler',
         ),
        'detailed_search_text' => array(
         'label' => 'Detailed Search Title',
         'type' => 'text',
         'output' => 'text',
         'input_width' => 'full',
         'description' => '(ex. "More Search Options")',
         'field_grouping' => 'detailed_search'
         ),
        'destination' => array(
         'label' => 'Send users to',
         'type' => 'select',
         'options' => flexmlsConnect::possible_destinations(),
         'output' => 'text',
         ),

            'user_sorting' => array(
                'label' => 'User Sorting',
                'type' => 'enabler',
                'output' => 'enabler',
                'section' => 'Sorting',
            ),

        // filters
        'location_search' => array(
         'label' => 'Location Search',
         'type' => 'enabler',
         'output' => 'enabler', // legacy
         'section' => 'Filters',
         ),
        'property_type_enabled' => array(
         'label' => 'Property Type',
         'type' => 'enabler',
         'output' => 'enabler',
         ),
        'property_type' => array(
         'label' => 'Property Types',
         'type' => 'list',
         'output' => 'text', // legacy
         'field_grouping' => 'property_type_enabled'
         ),
        'std_fields' => array(
         'label' => 'Fields',
         'type' => 'list',
         'output' => 'text', // legacy
         ),

        // theme
        'theme' => array(
         'label' => 'Select a Theme',
         'type' => 'select',
         'options' => array(
          '' => '(Select One)',
          'vert_round_light' => 'Vertical Rounded Light',
          'vert_round_dark' => 'Vertical Rounded Dark',
          'vert_square_light' => 'Vertical Square Light',
          'vert_square_dark' => 'Vertical Square Dark',
          'hori_round_light' => 'Horizontal Rounded Light',
          'hori_round_dark' => 'Horizontal Rounded Dark',
          'hori_square_light' => 'Horizontal Square Light',
          'hori_square_dark' => 'Horizontal Square Dark',
         ),
         'output' => 'text',
         'description' => 'Selecting a theme will override your current layout, style and color settings.
           The default width of a vertical theme is 300px and 730px for horizontal.',
         'input_width' => 'full',
         'class' => 'flexmls_connect__theme_selector'
         ),

        // layout
        'orientation' => array(
         'label' => 'Orientation',
         'type' => 'select',
         'options' => array(
          'horizontal' => 'Horizontal',
          'vertical' => 'Vertical',
          ),
         'output' => 'text',
         'section' => 'Layout',
         ),
        'width' => array(
         'label' => 'Widget Width',
         'type' => 'text',
         'output' => 'text', // legacy
         'input_width' => 5,
         'after_input' => ' px'
         ),

        // style
        'title_font' => array(
         'label' => 'Title Font',
         'type' => 'select',
         'options' => flexmlsConnect::possible_fonts(),
         'output' => 'text',
         'section' => 'Style',
         ),
        'field_font' => array(
         'label' => 'Field Font',
         'type' => 'select',
         'options' => flexmlsConnect::possible_fonts(),
         'output' => 'text',
         ),
        'border_style' => array(
         'label' => 'Border Style',
         'type' => 'select',
         'options' => array(
          'squared' => 'Squared',
          'rounded' => 'Rounded'
          ),
         'output' => 'text',
         ),
        'widget_drop_shadow' => array(
         'label' => 'Widget Drop Shadow',
         'type' => 'enabler',
         'output' => 'enabler',
         ),

        // color
        'background_color' => array(
         'label' => 'Background',
         'type' => 'color',
         'output' => 'text',
         'section' => 'Color',
         ),
        'title_text_color' => array(
         'label' => 'Title Text',
         'type' => 'color',
         'output' => 'text',
         'default' => '000000'
         ),
        'field_text_color' => array(
         'label' => 'Field Text',
         'type' => 'color',
         'output' => 'text',
         'default' => '000000'
         ),
        'detailed_search_text_color' => array(
         'label' => 'Detailed Search',
         'type' => 'color',
         'output' => 'text',
         'default' => '000000'
         ),
        'submit_button_shine' => array(
         'label' => 'Submit Button',
         'type' => 'select',
         'options' => array(
          'shine' => 'Shine',
          'gradient' => 'Gradient',
          'none' => 'None'
          ),
         'output' => 'text',
         ),
        'submit_button_background' => array(
         'label' => 'Submit Button Background',
         'type' => 'color',
         'output' => 'text',
         'default' => '000000'
         ),
        'submit_button_text_color' => array(
         'label' => 'Submit Button Text',
         'type' => 'color',
         'output' => 'text',
         'description' => 'Select a color shine that compliments your website or select a custom color.'
         ),
    );

    return $settings_fields;

  }

  private function get_setting_color($property) {

    $defaults = array(
      'background_color' => "#FFFFFF",
      'submit_button_background' => "#000000",
      'title_text_color' => "000000",
      'field_text_color' => "000000",
      'detailed_search_text_color' => "FFFFFF",
      'submit_button_text_color' => "FFFFFF"
      );

    $color = $defaults[$property];
    if (array_key_exists($property, $this->widget_settings)) {
      $color = '#' . str_replace('#', '', trim($this->widget_settings[$property]));
    }
    return $color;
  }

}
