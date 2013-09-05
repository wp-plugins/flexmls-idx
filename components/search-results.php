<?php



class fmcSearchResults extends fmcWidget {

	function fmcSearchResults() {
		global $fmc_widgets;

		$widget_info = $fmc_widgets[ get_class($this) ];

		$widget_ops = array( 'description' => $widget_info['description'] );
//		$this->WP_Widget( get_class($this) , $widget_info['title'], $widget_ops);

		// have WP replace instances of [first_argument] with the return from the second_argument function
		add_shortcode($widget_info['shortcode'], array(&$this, 'shortcode'));

		// register where the AJAX calls should be routed when they come in
		add_action('wp_ajax_'.get_class($this).'_shortcode', array(&$this, 'shortcode_form') );
		add_action('wp_ajax_'.get_class($this).'_shortcode_gen', array(&$this, 'shortcode_generate') );

	}


	function jelly($args, $settings, $type) {
		global $fmc_api;
		global $fmc_plugin_url;

		extract($args);

		if ($type == "widget" && empty($settings['title']) && flexmlsConnect::use_default_titles()) {
			$settings['title'] = "Listings";
		}

		$encoded_settings = urlencode( serialize($settings) );

		$return = '';


		$title = isset($settings['title']) ? ($settings['title']) : '';
		$source = isset($settings['source']) ? trim($settings['source']) : '';
		$display = isset($settings['display']) ? trim($settings['display']) : '';
		$days = isset($settings['days']) ? trim($settings['days']) :  '';
		$property_type = isset($settings['property_type']) ? trim($settings['property_type']): '';
		$link = isset($settings['link']) ? trim($settings['link']) : '';
		$location = isset($settings['location']) ? html_entity_decode(flexmlsConnect::clean_comma_list($settings['location'])):'';
		$sort = isset($settings['sort']) ? trim($settings['sort']) : '';
		$agent = isset($settings['agent']) ? trim($settings['agent']) : '';

		if ($link == "default") {
			$link = flexmlsConnect::get_default_idx_link();
		}

		$source = (empty($source)) ? "my" : $source;

		$filter_conditions = array();
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
		$flexmls_hours = $days*24;

		if ($display == "all") {
			// nothing to do
		}
		elseif ($display == "new") {
            $params['_pagination'] = 1;
            $filter_conditions[] = "OnMarketDate Ge {$specific_time}";
            $outbound_criteria .= "&listingevent=new&listingeventhours={$flexmls_hours}";
            $pure_conditions["OnMarketDate"] = $specific_time;
		}
		elseif ($display == "open_houses") {
			$params['OpenHouses'] = $days;
			$pure_conditions['OpenHouses'] = $days;
			$params['_expand'] .= ',OpenHouses';
			$outbound_criteria .= "&openhouse={$days}";
		}
		elseif ($display == "price_changes") {
            $params['_pagination'] = 1;
            $filter_conditions[] = "PriceChangeTimestamp Gt {$specific_time}";
            $outbound_criteria .= "&listingevent=price&listingeventhours={$flexmls_hours}";
            $pure_conditions["PriceChangeTimestamp"] = $specific_time;
		}
		elseif ($display == "recent_sales") {
            $params['_pagination'] = 1;
            $filter_conditions[] = "StatusChangeTimestamp Gt {$specific_time}";
			$outbound_criteria .= "&status=C&listingevent=status&listingeventhours={$flexmls_hours}";
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


		$apply_property_type = ($source == 'location') ? true : false;

		if ($source == 'agent') {
			$pure_conditions['ListAgentId'] = $agent;
		}

		// parse location search settings
		$locations = flexmlsConnect::parse_location_search_string($location);

		$location_conditions = array();
		$location_field_names = array();

		foreach ($locations as $loc) {
			$location_conditions[] = "{$loc['f']} Eq '{$loc['v']}'";
			$pure_conditions[$loc['f']] = $loc['v'];
			$location_field_names[] = $loc['f'];
		}

		$uniq_location_field_names = array_unique($location_field_names);

		if (count($location_conditions) > 1) {
			return "<span style='color:red;'>flexmls&reg; IDX: This widget is configured with too many location search criteria options.  Please reduce to 1.</span>";
		}

		if ($apply_property_type and !empty($property_type)) {
			$pure_conditions['PropertyType'] = $property_type;
		}

		if ($link) {
			$link_details = flexmlsConnect::get_idx_link_details($link);
            if ($link_details['LinkType'] == "SavedSearch") {

				$pure_conditions['SavedSearch'] = $link_details['SearchId'];
			}
		}

		if ($source == "my") {
			$outbound_criteria .= "&my_listings=true";
			$pure_conditions['My'] = 'listings';
			// make a simple request to /my/listings with no _filter's
		}
		elseif ($source == "office") {
			$outbound_criteria .= "&office=". flexmlsConnect::get_office_id();
			$pure_conditions['My'] = 'office';
		}
		elseif ($source == "company") {
			$outbound_criteria .= "&office=". flexmlsConnect::get_company_id();
			$pure_conditions['My'] = 'company';
		}
		elseif ($source == 'agent') {
			$outbound_criteria .= "&agent={$agent}";
		}
		else { }





		$custom_page = new flexmlsConnectPageSearchResults;
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


	function settings_form($instance) {
		global $fmc_api;

		$title = esc_attr($instance['title']);
		$source = esc_attr($instance['source']);
		$display = esc_attr($instance['display']);
		$days = esc_attr($instance['days']);
		$property_type = esc_attr($instance['property_type']);
		$link = esc_attr($instance['link']);
		$location = $instance['location'];
		$sort = esc_attr($instance['sort']);
		$agent = esc_attr($instance['agent']);

		$selected_code = " selected='selected'";
		$checked_code = " checked='checked'";

		$source_options = array();
		$roster_feature = false;

		$my_company_id = flexmlsConnect::get_company_id();

		if ( flexmlsConnect::is_agent() ) {
			$source_options['my'] = "My Listings";
			$source_options['office'] = "My Office's Listings";
			if ( !empty($my_company_id) ) {
				$source_options['company'] = "My Company's Listings";
			}
		}

		if ( flexmlsConnect::is_office() ) {
			$source_options['office'] = "My Office's Listings";
			if ( !empty($my_company_id) ) {
				$source_options['company'] = "My Company's Listings";
			}
			$source_options['agent'] = "Specific agent";
			$roster_feature = true;
		}

		if ( flexmlsConnect::is_company() ) {
			$source_options['company'] = "My Company's Listings";
		}

		$display_options = array(
				"all" => "All Listings",
				"new" => "New Listings",
				"open_houses" => "Open Houses",
				"price_changes" => "Recent Price Changes",
				"recent_sales" => "Recent Sales"
		);

		$display_day_options = array(
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

		$sort_options = array(
				"recently_changed" => "Recently changed first",
				"price_low_high" => "Price, low to high",
				"price_high_low" => "Price, high to low"
		);

		$possible_destinations = flexmlsConnect::possible_destinations();

		if (empty($destination)) {
			$destination = 'remote';
		}

		$api_property_type_options = $fmc_api->GetPropertyTypes();
		$api_system_info = $fmc_api->GetSystemInfo();
		$api_location_search_api = flexmlsConnect::get_locationsearch_url();
		$api_my_account = $fmc_api->GetMyAccount();

		if ($api_property_type_options === false || $api_system_info === false || $api_location_search_api === false || $api_my_account === false) {
			return flexmlsConnect::widget_not_available($fmc_api, true);
		}

		if (!$fmc_api->HasBasicRole()) {
			$source_options['location'] = "Location";
		}

		$office_roster = ($roster_feature) ? $fmc_api->GetAccountsByOffice( $api_my_account['Id'] ) : array();

		$source = (empty($source)) ? "location" : $source;

		$special_neighborhood_title_ability = null;
		if (array_key_exists('_instance_type', $instance) && $instance['_instance_type'] == "shortcode") {
			$special_neighborhood_title_ability = flexmlsConnect::special_location_tag_text();
		}


		$return = "";


		// widget title
		$return .= "<p>\n";
		$return .= "<label for='".$this->get_field_id('title')."'>" . __('Title:') . "</label>\n";
		$return .= "<input fmc-field='title' fmc-type='text' type='text' class='widefat' id='".$this->get_field_id('title')."' name='".$this->get_field_name('title')."' value='{$title}'>\n";
		$return .= $special_neighborhood_title_ability;
		$return .= "</p>\n";


		// IDX link
		$api_links = flexmlsConnect::get_all_idx_links(true);

		$return .= "<p>\n";
		$return .= "<label for='".$this->get_field_id('link')."'>" . __('Saved Search:') . "</label>\n";
		$return .= "<select fmc-field='link' fmc-type='select' id='".$this->get_field_id('link')."' name='".$this->get_field_name('link')."'>\n";

		$is_selected = ($link == "") ? $selected_code : "";
		$return .= "<option value=''{$is_selected}>(None)</option>\n";

		$is_selected = ($link == "default") ? $selected_code : "";
		$return .= "<option value='default'{$is_selected}>(Use Saved Default)</option>\n";

		foreach ($api_links as $my_l) {
			$is_selected = ($my_l['LinkId'] == $link) ? $selected_code : "";
			$return .= "<option value='{$my_l['LinkId']}'{$is_selected}{$is_disabled}>{$my_l['Name']}</option>\n";
		}

		$return .= "</select><br /><span class='description'>flexmls Saved Search to apply</span>\n";
		$return .= "</p>\n";


		// filter by
		$return .= "<p>\n";
		$return .= "<label for='".$this->get_field_id('source')."'>" . __('Filter by:') . "</label>\n";
		$return .= "<select fmc-field='source' fmc-type='select' id='".$this->get_field_id('source')."' name='".$this->get_field_name('source')."' class='flexmls_connect__listing_source'>\n";

		foreach ($source_options as $k => $v) {
			$is_selected = ($k == $source) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}{$is_disabled}>{$v}</option>\n";
		}

		$hidden_location = ($source != "location") ? " style='display:none;'" : "";
		$hidden_roster = ($source != "agent") ? " style='display:none;'" : "";

		$return .= "</select><br /><span class='description'>Which listings to display</span>\n";
		$return .= "</p>\n";


		// property type
		$return .= "<p class='flexmls_connect__location_property_type_p' {$hidden_location}>\n";
		$return .= "<label for='".$this->get_field_id('property_type')."'>" . __('Property Type:') . "</label>\n";
		$return .= "<select fmc-field='property_type' class='flexmls_connect__property_type' fmc-type='select' id='".$this->get_field_id('property_type')."' name='".$this->get_field_name('property_type')."'>\n";

		foreach ($api_property_type_options as $k => $v) {
			$is_selected = ($k == $property_type) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}{$is_disabled}>{$v}</option>\n";
		}

		$return .= "</select>\n";
		$return .= "</p>\n";


		// location
		$return .= "<div class='flexmls_connect__location'{$hidden_location}>\n";
		$return .= "<p>\n";
		$return .= "<label for='horizontal'>Location:</label>\n";
		$return .= "<input type='text' name='location_input' data-connect-url='{$api_location_search_api}' class='flexmls_connect__location_search' autocomplete='off' value='City, Postal Code, etc.' />\n";
		$return .= "<a href='javascript:void(0);' title='Click here to browse through available locations' class='flexmls_connect__location_browse'>Browse &raquo;</a>\n";
		$return .= "<div class='flexmls_connect__location_list' data-connect-multiple='false'>\n";
		$return .= "	<p>All Locations Included</p>\n";
		$return .= "</div>\n";
		$return .= "<input type='hidden' name='tech_id' class='flexmls_connect__tech_id' value=\"x'{$api_system_info['Id']}'\" />\n";
		$return .= "<input type='hidden' name='ma_tech_id' class='flexmls_connect__ma_tech_id' value=\"x'". flexmlsConnect::fetch_ma_tech_id() ."'\" />\n";
		$return .= "<input fmc-field='location' fmc-type='text' type='hidden' name='".$this->get_field_name('location')."' class='flexmls_connect__location_fields' value=\"{$location}\" />\n";
		$return .= "</p>\n";
		$return .= "</div>\n";


		// roster
		$return .= "<div class='flexmls_connect__roster'{$hidden_roster}>\n";
		$return .= "<p>\n";
		$return .= "<label for='".$this->get_field_id('agent')."'>" . __('Agent:') . "\n";
		$return .= "<select fmc-field='agent' fmc-type='select' id='".$this->get_field_id('agent')."' name='".$this->get_field_name('agent')."'>\n";
		$return .= "<option value=''>  - Select One -  </option>\n";

		foreach ($office_roster as $a) {
			$is_selected = ($a['Id'] == $agent) ? $selected_code : "";
			$return .= "<option value='{$a['Id']}'{$is_selected}>". htmlspecialchars($a['Name']) ."</option>";
		}

		$return .= "</select>\n";
		$return .= "</label>\n";
		$return .= "</p>\n";
		$return .= "</div>\n";


		// display
		$return .= "<p>\n";
		$return .= "<label for='".$this->get_field_id('display')."'>" . __('Display:') . "\n";
		$return .= "<select class='photos_display' fmc-field='display' fmc-type='select' id='".$this->get_field_id('display')."' name='".$this->get_field_name('display')."'>\n";

		foreach ($display_options as $k => $v) {
			$is_selected = ($k == $display) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}{$is_disabled}>{$v}</option>\n";
		}

		$return .= "</select>\n";
		$return .= "</label>\n";
		$return .= "</p>\n";

		 $return .= "<p>
                 <label class='photos_days' style='display:none' for='".$this->get_field_id('day')."'>" . __('Number of Days:') . "
                     <select fmc-field='day' fmc-type='select' id='".$this->get_field_id('days')."' name='".$this->get_field_name('days')."'>
                         ";
		foreach ($display_day_options as $k => $v) {
            $is_selected = ($k == $days) ? $selected_code : "";
            $return .= "<option value='{$k}'{$is_selected}{$is_disabled}>{$v}</option>\n";
        }

		$return .= "</select> </label> </p>";

		// sort
		$return .= "<p>\n";
		$return .= "<label for='".$this->get_field_id('sort')."'>" . __('Sort by:') . "\n";
		$return .= "<select fmc-field='sort' fmc-type='select' id='".$this->get_field_id('sort')."' name='".$this->get_field_name('sort')."'>\n";

		foreach ($sort_options as $k => $v) {
			$is_selected = ($k == $sort) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}{$is_disabled}>{$v}</option>\n";
		}

		$return .= "</select>\n";
		$return .= "</label>\n";
		$return .= "</p>\n";



		$return .= "<img src='x' class='flexmls_connect__bootloader' onerror='flexmls_connect.location_setup(this);' />\n";


		$return .= "<input type='hidden' name='shortcode_fields_to_catch' value='title,link,source,property_type,location,display,sort,agent,days' />\n";
		$return .= "<input type='hidden' name='widget' value='". get_class($this) ."' />\n";

		return $return;

	}


	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['source'] = strip_tags($new_instance['source']);
		$instance['display'] = strip_tags($new_instance['display']);
		$instance['days'] = strip_tags($new_instance['days']);
		$instance['property_type'] = strip_tags($new_instance['property_type']);
		$instance['link'] = strip_tags($new_instance['link']);
		$instance['location'] = strip_tags($new_instance['location']);
		$instance['sort'] = strip_tags($new_instance['sort']);
		$instance['agent'] = strip_tags($new_instance['agent']);

		return $instance;
	}

}
