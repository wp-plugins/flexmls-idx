<?php



class fmcSearch extends fmcWidget {

	function fmcSearch() {
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

		if ($type == "widget" && empty($settings['title']) && flexmlsConnect::use_default_titles()) {
			$settings['title'] = "IDX Search";
		}
		
		$return = '';

		$rand = mt_rand();

		$title = trim($settings['title']);
		$width = trim($settings['width']);
		$buttontext = htmlspecialchars(trim($settings['buttontext']), ENT_QUOTES);
		$location_search = trim($settings['location_search']);
		$property_type = trim($settings['property_type']);
		$my_link = trim($settings['link']);
		$std_fields = trim($settings['std_fields']);

		$std_fields_selected = explode(",", $std_fields);
		$property_types_selected = explode(",", $property_type);

		$api_prop_types = $fmc_api->PropertyTypes();
		$api_system_info = $fmc_api->SystemInfo();
		$api_location_search_api = $fmc_api->GetLocationSearchApiUrl();
		$api_links = $fmc_api->GetIDXLinks();

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

		$return .= $before_widget;

		if ( !empty($title) ) {
			$return .= $before_title;
			$return .= $title;
			$return .= $after_title;
			$return .= "\n";
		}

		if (empty($buttontext)) {
			$buttontext = "Search";
		}

		if (empty($width)) {
			$width = 400;
		}

		$search_fields = array();

		$this_target = "";
		if (flexmlsConnect::get_destination_window_pref() == "new") {
			$this_target = " target='_blank'";
		}

		$return .= "<div class='flexmls_connect__search' style='width:{$width}px;'>\n";
		$return .= "<form action='{$_SERVER['REQUEST_URI']}' method='post'{$this_target}>\n";
		$return .= "<table cellpadding='0' cellspacing='0' border='0'>\n";

		if ($location_search == "on") {
			$return .= "\t<tr><td colspan='4'>\n";
			$return .= "\t\t<input type='text' name='location' data-connect-url='{$api_location_search_api}' class='flexmls_connect__location_search' autocomplete='off' value='City, Zip, Address or Other Location' />\n";
			$return .= "\t</td></tr>\n";
			$return .= "\t<tr class='shade'><td colspan='4' class='flexmls_connect__location_list'></td></tr>\n";
			$search_fields[] = "Location";
		}
		
		$good_prop_types = array();

		foreach ($api_prop_types as $k => $v) {
			if (in_array($k, $property_types_selected)) {
				$good_prop_types[] = $k;
			}
		}

		$return .= "\n\n";

		if (count($good_prop_types) > 1) {
			$return .= "\t<tr class='shade' data-connect-type='property-type' data-connect-field='property-type'><td colspan='4'>\n";
			foreach ($good_prop_types as $type) {
				$return .= "\t\t<span><input type='checkbox' class='property-type-checkbox' checked='checked' name='property-type-{$type}' id='{$rand}-property-type-{$type}' value='{$type}'> <label for='{$rand}-property-type-{$type}'>{$api_prop_types[$type]}</label></span>\n";
			}
			$return .= "\t</td></tr>\n";
		}
		else {
			$return .= "\t<input type='hidden' name='property-type' value='".implode("", $good_prop_types)."' />\n";
		}
		$search_fields[] = "PropertyType";

		$return .= "\n\n";

		foreach ($std_fields_selected as $fi) {
			if ( $fi == "list_price" ) {
				$return .= "<tr class='shade' data-connect-type='number' data-connect-field='list_price'>\n";
				$return .= "<td class='first'><label for='{$rand}-list_price_from'>Price: </label></td>\n";
				$return .= "<td><input type='text' class='text' name='list_price_from' id='{$rand}-list_price_from' data-connect-default='Min' /></td>\n";
				$return .= "<td><label for='{$rand}-list_price_to'>to</label></td>\n";
				$return .= "<td><input type='text' class='text' name='list_price_to' id='{$rand}-list_price_to' data-connect-default='Max' /></td>\n";
				$return .= "</tr>\n";
				$search_fields[] = "ListPrice";
			}

			if ( $fi == "beds" ) {
				$return .= "<tr class='shade' data-connect-type='number' data-connect-field='beds'>\n";
				$return .= "<td class='first'><label for='{$rand}-beds_from'>Bedrooms: </label></td>\n";
				$return .= "<td><input type='text' class='text' name='beds_from' id='{$rand}-beds_from' data-connect-default='Min' /></td>\n";
				$return .= "<td><label for='{$rand}-beds_to'>to</label></td>\n";
				$return .= "<td><input type='text' class='text' name='beds_to' id='{$rand}-beds_to' data-connect-default='Max' /></td>\n";
				$return .= "</tr>\n";
				$search_fields[] = "BedsTotal";
			}

			if ( $fi == "baths" ) {
				$return .= "<tr class='shade' data-connect-type='number' data-connect-field='baths'>\n";
				$return .= "<td class='first'><label for='{$rand}-baths_from'>Bathrooms: </label></td>\n";
				$return .= "<td><input type='text' class='text' name='baths_from' id='{$rand}-baths_from' data-connect-default='Min' /></td>\n";
				$return .= "<td><label for='{$rand}-baths_to'>to</label></td>\n";
				$return .= "<td><input type='text' class='text' name='baths_to' id='{$rand}-baths_to' data-connect-default='Max' /></td>\n";
				$return .= "</tr>\n";
				$search_fields[] = "BathsTotal";
			}

			if ( $fi == "square_footage" ) {
				$return .= "<tr class='shade' data-connect-type='number' data-connect-field='square_footage'>\n";
				$return .= "<td class='first'><label for='{$rand}-square_footage_from'>Square Footage: </label></td>\n";
				$return .= "<td><input type='text' class='text' name='square_footage_from' id='{$rand}-square_footage_from' data-connect-default='Min' /></td>\n";
				$return .= "<td><label for='{$rand}-square_footage_to'>to</label></td>\n";
				$return .= "<td><input type='text' class='text' name='square_footage_to' id='{$rand}-square_footage_to' data-connect-default='Max' /></td>\n";
				$return .= "</tr>\n";
				$search_fields[] = "BuildingAreaTotal";
			}

			if ( $fi == "age" ) {
				$return .= "<tr class='shade' data-connect-type='number' data-connect-field='age'>\n";
				$return .= "<td class='first'><label for='{$rand}-age_from'>Age: </label></td>\n";
				$return .= "<td><input type='text' class='text' name='age_from' id='{$rand}-age_from' data-connect-default='Min' /></td>\n";
				$return .= "<td><label for='{$rand}-age_to'>to</label></td>\n";
				$return .= "<td><input type='text' class='text' name='age_to' id='{$rand}-age_to' data-connect-default='Max' /></td>\n";
				$return .= "</tr>\n";
				$search_fields[] = "YearBuilt";
			}

		}
		
		$return .= "<tr><td colspan='4'>\n";
		$return .= "<input type='hidden' name='fmc_do' value='fmc_search' />\n";
		$return .= "<input type='hidden' name='link' class='flexmls_connect__link' value='{$my_link}' />\n";
		$return .= "<input type='hidden' name='query' value='' />\n";
		$return .= "<input type='hidden' name='tech_id' class='flexmls_connect__tech_id' value=\"x'{$api_system_info['Id']}'\" />\n";
		$return .= "<input type='hidden' name='ma_tech_id' class='flexmls_connect__ma_tech_id' value=\"x'{$api_system_info['MlsId']}'\" />\n";
		$return .= "<input type='hidden' name='destlink' value='".flexmlsConnect::get_destination_link()."' />\n";
		$return .= "<input type='submit' value='{$buttontext}' />\n";
		$return .= "</td></tr>\n";

		$return .= "</table>\n";
		$return .= "</form>\n";
		$return .= "</div>\n";

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

		$title = esc_attr($instance['title']);
		$width = esc_attr($instance['width']);
		$property_type = esc_attr($instance['property_type']);
		$location_search = esc_attr($instance['location_search']);
		$std_fields = esc_attr($instance['std_fields']);
		$standard_fields = explode(",", $std_fields);
		$link = esc_attr($instance['link']);
		$buttontext = esc_attr($instance['buttontext']);

		$property_types_selected = explode(",", $property_type);

		$possible_standard_fields = array(
				'age' => "Age",
				'baths' => "Bathrooms",
				'beds' => "Bedrooms",
				'square_footage' => "Square Footage",
				'list_price' => "Price"
		);
		
		$location_search_options = array(
				'on' => 'On',
				'off' => 'Off'
				);


		$fmc_api = new FlexmlsApiWp;
		$api_property_type_options = $fmc_api->PropertyTypes();
		$api_links = $fmc_api->GetIDXLinks();

		if ($api_property_type_options === false || $api_links === false) {
			return flexmlsConnect::widget_not_available($fmc_api, true);
		}

		$return = "";

		$selected_code = " selected='selected'";

		$return .= "

			<p>
				<label for='".$this->get_field_id('title')."'>" . __('Title:') . "</label>
				<input fmc-field='title' fmc-type='text' type='text' class='widefat' id='".$this->get_field_id('title')."' name='".$this->get_field_name('title')."' value='{$title}'>
			</p>

			<p>
				<label for='".$this->get_field_id('width')."'>" . __('Width:') . "</label>
				<input fmc-field='width' fmc-type='text' type='text' id='".$this->get_field_id('width')."' name='".$this->get_field_name('width')."' value='{$width}'>
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
					</select><br /><span class='description'>Link used when search is executed</span>
			</p>

			<p><b>Filters:</b></p>
			<div style='padding: 2px 5px 10px 10px; border: 1px dashed #C0C0C0;'>

			<p>
				<label for='".$this->get_field_id('property_type')."'>" . __('Property Type:') . "</label><br />
				<select fmc-field='property_type' fmc-type='select' name='".$this->get_field_name('property_type')."[]' style='height: 75px;' multiple='multiple'>
			";

		foreach ($api_property_type_options as $k => $v) {
			$is_selected = (in_array($k, $property_types_selected)) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}>{$v}</option>\n";
		}

		$return .= "
				</select><br />
			</p>

			<p>
				<label for='".$this->get_field_id('location_search')."'>" . __('Location Search:') . "</label>
				<select fmc-field='location_search' fmc-type='select' name='".$this->get_field_name('location_search')."' id='".$this->get_field_id('location_search')."'>
				";
		
		foreach ($location_search_options as $k => $v) {
			$is_selected = ($k == $location_search) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}>{$v}</option>\n";
		}
		
		$return .= "
				</select><br />
				<span class='description'>Show the Location Search</span>
			</p>

			<p>
				<label for='".$this->get_field_id('std_fields')."'>" . __('Fields:') . "</label>
				<input fmc-field='std_fields' fmc-type='text' type='hidden' name='".$this->get_field_name('std_fields')."' class='flexmls_connect__std_fields' value='{$std_fields}' />
			    <ul class='flexmls_connect__sortable'>
					";

		foreach ($standard_fields as $fi) {
			$fi = trim($fi);
			if (empty($fi)) {
				continue;
			}
			$return .= "
							<li data-connect-name='{$fi}'>
								<span class='remove' title='Remove this field'>&times;</span>
								<span class='ui-icon ui-icon-arrowthick-2-n-s'></span>
								{$possible_standard_fields[$fi]}
							</li>
							";
		}

		$return .= "
					</ul>

					<hr />
					<select name='".$this->get_field_name('available_fields')."' class='flexmls_connect__available_fields'>
					";

		foreach ($possible_standard_fields as $k => $v) {
			$return .= "<option value='{$k}'>{$v}</option>\n";
		}

		$return .= "
					</select>
					<a href='javascript:void(0);' title='Add this field to the search' class='flexmls_connect__add_std_field'>Add Field</a>

			</p>

				</div>
						
			<p><br />
				<label for='".$this->get_field_id('buttontext')."'>" . __('Button Text:') . "</label>
				<input fmc-field='buttontext' fmc-type='text' type='text' class='widefat' id='".$this->get_field_id('buttontext')."' name='".$this->get_field_name('buttontext')."' value='{$buttontext}'>
				<span class='description'>Customize the name of the submit button</span>
			</p>


			<img src='x' class='flexmls_connect__bootloader' onerror='flexmls_connect.sortable_setup(this);' />


			";


		$return .= "<input type='hidden' name='shortcode_fields_to_catch' value='title,width,link,property_type,location_search,buttontext,std_fields' />\n";
		$return .= "<input type='hidden' name='widget' value='". get_class($this) ."' />\n";

		return $return;

	}



	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['width'] = strip_tags($new_instance['width']);
		$instance['location_search'] = strip_tags($new_instance['location_search']);
		$instance['property_type'] = implode(",", array_map('strip_tags', $new_instance['property_type']));
		$instance['buttontext'] = strip_tags($new_instance['buttontext']);
		$instance['link'] = strip_tags($new_instance['link']);
		$instance['std_fields'] = strip_tags($new_instance['std_fields']);

		return $instance;
	}


	function submit_search() {
		global $fmc_api;

		// translate from form field names to standard names
		$fields_to_catch = array(
				'PropertyType' => 'PropertyType',
				'MapOverlay' => 'MapOverlay',
				'list_price' => 'ListPrice',
				'beds' => 'BedsTotal',
				'baths' => 'BathsTotal',
				'age' => 'YearBuilt',
				'square_footage' => 'BuildingAreaTotal'
		);

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

			// special handling for the 'age' field to convert it into years
			if ($key == "age") {
				if ( strpos($value, ",") !== false) {
					// range search.  convert both to years
					list($from, $to) = explode(",", $value);
					$from = date("Y") - $from;
					$to = date("Y") - $to;
					// swap values since it makes sense for year searches.
					// 5 to 25 years becomes 2005 to 1985 otherwise
					$value = "{$to},{$from}";
				}
				else {
					$value = date("Y") - $value;
				}
			}
			else {
				$vals = explode(",", $value);
				$vals = array_map( array('flexmlsConnect', 'strip_quotes') , $vals);
				$vals = array_map( array('flexmlsConnect', 'remove_starting_equals') , $vals);
				$value = implode(",", $vals);
			}

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
			$std_query_conditions[$std_cond_field] = array('v' => $qc['v'], 'o' => $qc['o']);
			$link_transform_params[$std_cond_field] = "*{$std_cond_field}*";
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
			header("Location: {$outbound_link}");
			exit;
		}
		
	}


}
