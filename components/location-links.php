<?php



class fmcLocationLinks extends fmcWidget {

	function fmcLocationLinks() {
		global $fmc_widgets;

		$widget_info = $fmc_widgets[ get_class($this) ];

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

		// set default title if a widget, none given, and the default_titles setting is turned on
		if ($type == "widget" && empty($settings['title']) && flexmlsConnect::use_default_titles()) {
			$settings['title'] = "Location Links";
		}

		$return = '';

		$title = trim($settings['title']);
		$my_link = trim($settings['link']);
		$property_type = trim($settings['property_type']);
		$my_locations = html_entity_decode(flexmlsConnect::clean_comma_list($settings['locations']));

		// check if required parameters were given
		if (empty($my_link) || empty($my_locations) || empty($property_type)) {
			return flexmlsConnect::widget_missing_requirements("Location Links", "Link, Locations and Property Type");
		}
		
		if ($my_link == "default") {
			$my_link = flexmlsConnect::get_default_idx_link();
		}
		
		// make API call
		$api_links = flexmlsConnect::get_all_idx_links();

		if ($api_links === false) {
			return flexmlsConnect::widget_not_available($fmc_api, false, $args, $settings);
		}

		// break the Location Search string into separate pieces
		$locations = flexmlsConnect::parse_location_search_string($my_locations);

		// re-arrange the structure a bit for easier testing
		$valid_links = array();
		foreach ($api_links as $link) {
			$valid_links[$link['LinkId']] = array('Uri' => $link['Uri'], 'Name' => $link['Name'], 'SearchId' => $link['SearchId']);
		}

		// test if the selected link is valid.  bail out if not
		if ( !array_key_exists($my_link, $valid_links) ) {
			return;
		}

		// make a list of all of the standard field names used in the Location Search value
		$location_field_names = array();
		foreach ($locations as $loc) {
			$location_field_names[] = $loc['f'];
		}


		// make that list unique
		$uniq_location_field_names = array_unique($location_field_names);

		// prepare some values for the transformation API call.
		// this allows us to get the transformed link for all of the fields at once rather than requiring
		// a separate API call for each unique field we're generating links for
		$link_transform_params = array();
		foreach ($uniq_location_field_names as $loc_name) {
			$link_transform_params["{$loc_name}"] = "*{$loc_name}*";
		}
		
		$link_transform_params["PropertyType"] = "*PropertyType*";
		
		if ($settings['destination'] != 'local') {
			// make the API call to translate standard field names
			$outbound_link = $fmc_api->GetTransformedIDXLink($my_link, $link_transform_params);
			
		}

		foreach ($locations as $loc) {
			
			$final_destination = null;
			$final_target = null;
			
			if ($settings['destination'] == 'local') {
				$final_destination = flexmlsConnect::make_nice_tag_url('search', array('SavedSearch' => $valid_links[$my_link]['SearchId'], $loc['f'] => $loc['v']) );
			}
			else {
				// start replacing the placeholders in the link with the real values for this link
				$this_link = $outbound_link;
				$this_link = preg_replace('/\*'.preg_quote($loc['f']).'\*/', $loc['v'], $this_link);
				$this_link = preg_replace('/\*PropertyType\*/', $property_type, $this_link);
				// replace all remaining placeholders with a blank value since it doesn't apply to this link
				$this_link = preg_replace('/\*(.*?)\*/', "", $this_link);
				$this_target = "";
				if (flexmlsConnect::get_destination_window_pref() == "new") {
					$this_target = " target='_blank'";
				}
				$final_destination = flexmlsConnect::make_destination_link($this_link);
				$final_target = $this_target;
			}

			
			$links_to_show .= "<li><a href=\"{$final_destination}\" title=\"{$valid_links[$my_link]['Name']} - {$loc['l']}\"{$final_target}}>{$loc['l']}</a></li>\n";
		}

		if (empty($links_to_show)) {
			return;
		}

		$return .= $before_widget;

		if ( !empty($title) ) {
			$return .= $before_title;
			$return .= $title;
			$return .= $after_title;
			$return .= "\n";
		}

		$return .= "<ul>\n";
		$return .= $links_to_show;
		$return .= "</ul>\n";

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

		$title = esc_attr($instance['title']);
		$link = esc_attr($instance['link']);
		$property_type = esc_attr($instance['property_type']);
		$locations = $instance['locations'];
		$destination = esc_attr($instance['destination']);
		
		$possible_destinations = flexmlsConnect::possible_destinations();

		$selected_code = " selected='selected'";

		$api_links = flexmlsConnect::get_all_idx_links(true);
		$api_property_type_options = $fmc_api->GetPropertyTypes();
		$api_system_info = $fmc_api->GetSystemInfo();
		$api_location_search_api = flexmlsConnect::get_locationsearch_url();

		if ($api_links === false || $api_property_type_options === false || $api_system_info === false || $api_location_search_api === false) {
			return flexmlsConnect::widget_not_available($fmc_api, true);
		}

		$return = "";

		$return .= "

			<p>
				<label for='".$this->get_field_id('title')."'>" . __('Title:') . "</label>
				<input fmc-field='title' fmc-type='text' type='text' class='widefat' id='".$this->get_field_id('title')."' name='".$this->get_field_name('title')."' value='{$title}'>
			</p>

			<p>
				<label for='".$this->get_field_id('link')."'>" . __('IDX Link:') . "</label>
				<select fmc-field='link' fmc-type='select' id='".$this->get_field_id('link')."' name='".$this->get_field_name('link')."'>
						";

		$is_selected = ($link == "default") ? $selected_code : "";
		$return .= "<option value='default'{$is_selected}>(Use Saved Default)</option>\n";

		foreach ($api_links as $my_l) {
			$is_selected = ($my_l['LinkId'] == $link) ? $selected_code : "";
			$return .= "<option value='{$my_l['LinkId']}'{$is_selected}{$is_disabled}>{$my_l['Name']}</option>\n";
		}

		$return .= "
					</select><br /><span class='description'>Saved Search IDX link these locations are built upon</span>
			</p>

			<p>
				<label for='".$this->get_field_id('property_type')."'>" . __('Property Type:') . "</label>
				<select fmc-field='property_type' fmc-type='select' id='".$this->get_field_id('property_type')."' name='".$this->get_field_name('property_type')."' class='flexmls_connect__property_type'>
						";

		foreach ($api_property_type_options as $k => $v) {
			$is_selected = ($k == $property_type) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}>{$v}</option>\n";
		}

		$return .= "
				</select>
			</p>

    <div class='flexmls_connect__location'>
			<label for='horizontal'>Location:</label>
      <input type='text' name='location_input' data-connect-url='{$api_location_search_api}' class='flexmls_connect__location_search' autocomplete='off' value='City, Postal Code, etc.' />

      <a href='javascript:void(0);' title='Click here to browse through available locations' class='flexmls_connect__location_browse'>Browse &raquo;</a>
      <div class='flexmls_connect__location_list' data-connect-multiple='true'>
        <p>All Locations Included</p>
      </div>
      <input type='hidden' name='{$this->get_field_name('tech_id')}' class='flexmls_connect__tech_id' value=\"x'{$api_system_info['Id']}'\" />
      <input type='hidden' name='{$this->get_field_name('ma_tech_id')}' class='flexmls_connect__ma_tech_id' value=\"x'". flexmlsConnect::fetch_ma_tech_id() ."'\" />
      <input fmc-field='locations' fmc-type='text' type='hidden' name='{$this->get_field_name('locations')}' class='flexmls_connect__location_fields' value=\"{$locations}\" />
    </div>
		
		<img src='x' class='flexmls_connect__bootloader' onerror='flexmls_connect.location_setup(this);' />
	 
			 <p><br/>
				<label for='".$this->get_field_id('destination')."'>" . __('Send users to:') . "</label>
				<select fmc-field='destination' fmc-type='select' id='".$this->get_field_id('destination')."' name='".$this->get_field_name('destination')."'>
						";

		foreach ($possible_destinations as $dk => $dv) {
			$is_selected = ($dk == $destination) ? " selected='selected'" : "";
			$return .= "<option value='{$dk}'{$is_selected}{$is_disabled}>{$dv}</option>\n";
		}

		$return .= "
					</select>
			</p>

			";

		$return .= "<input type='hidden' name='shortcode_fields_to_catch' value='title,link,property_type,locations,destination' />\n";
		$return .= "<input type='hidden' name='widget' value='". get_class($this) ."' />\n";

		return $return;
	}



	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['link'] = strip_tags($new_instance['link']);
		$instance['property_type'] = strip_tags($new_instance['property_type']);
		$instance['locations'] = strip_tags($new_instance['locations']);
		$instance['destination'] = strip_tags($new_instance['destination']);

		return $instance;
	}


}
