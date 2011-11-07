<?php



class fmcPhotos extends fmcWidget {

	function fmcPhotos() {
		global $fmc_widgets;

		$widget_info = $fmc_widgets[ get_class($this) ];

		$widget_ops = array( 'description' => $widget_info['description'] );
		$this->WP_Widget( get_class($this) , $widget_info['title'], $widget_ops);

		// have WP replace instances of [first_argument] with the return from the second_argument function
		add_shortcode($widget_info['shortcode'], array(&$this, 'shortcode'));

		// register where the AJAX calls should be routed when they come in
		add_action('wp_ajax_'.get_class($this).'_shortcode', array(&$this, 'shortcode_form') );
		add_action('wp_ajax_'.get_class($this).'_shortcode_gen', array(&$this, 'shortcode_generate') );

		add_action('wp_ajax_'.get_class($this).'_additional_photos', array(&$this, 'additional_photos') );
		add_action('wp_ajax_nopriv_'.get_class($this).'_additional_photos', array(&$this, 'additional_photos') );

		add_action('wp_ajax_'.get_class($this).'_additional_slides', array(&$this, 'additional_slides') );
		add_action('wp_ajax_nopriv_'.get_class($this).'_additional_slides', array(&$this, 'additional_slides') );

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

		$title = trim($settings['title']);
		$horizontal = trim($settings['horizontal']);
		$vertical = trim($settings['vertical']);
		$auto_rotate = trim($settings['auto_rotate']);
		$source = trim($settings['source']);
		$display = trim($settings['display']);
		$property_type = trim($settings['property_type']);
		$link = trim($settings['link']);
		$location = html_entity_decode(flexmlsApiWP::clean_comma_list($settings['location']));
		$sort = trim($settings['sort']);
		$page = trim($settings['page']);
		$additional_fields = trim($settings['additional_fields']);

		$show_additional_fields = array();
		if (!empty($additional_fields)) {
			$show_additional_fields = explode(",", $additional_fields);
		}
		if (count($show_additional_fields) > 0) {
			$tall_carousel = true;
		}
		else {
			$tall_carousel = false;
		}

		if ($link == "default") {
			$link = flexmlsConnect::get_default_idx_link();
		}

		if ($auto_rotate != 0 && $auto_rotate < 1000) {
			$auto_rotate = $auto_rotate * 1000;
		}

		if (empty($horizontal)) {
			$horizontal = 1;
		}
		if (flexmlsConnect::is_mobile() && $horizontal > 2) {
			$horizontal = 2;
		}
		if (empty($vertical)) {
			$vertical = 1;
		}
		if (empty($auto_rotate)) {
			$auto_rotate = 0;
		}
		if (empty($source)) {
			$source = "my";
		}

		$total_listings_to_show = ($horizontal * $vertical);
		if ($total_listings_to_show > 25) {
			list($horizontal, $vertical) = flexmlsConnect::generate_appropriate_dimensions($horizontal, $vertical);
		}

		$api_limit = flexmlsConnect::generate_api_limit_value($horizontal, $vertical);

		$filter_conditions = array();
		$outbound_criteria = "";

		$params = array();
		$params['_expand'] = 'PrimaryPhoto';
		$params['_pagination'] = 1;
		if (!empty($page) && $page > 0) {
			$params['_page'] = $page;
		}
		$params['_limit'] = $api_limit;

		$hours = 24;
		if (date("l") == "Monday") {
			$hours = 72;
		}

		if ($display == "all") {
			// nothing to do
		}
		elseif ($display == "new") {
			$params['HotSheet'] = "new";
			$outbound_criteria .= "&listingevent=new&listingeventhours={$hours}";
		}
		elseif ($display == "open_houses") {
			$params['OpenHouses'] = 10;
			$params['_expand'] .= ',OpenHouses';
			$outbound_criteria .= "&openhouse=10";
		}
		elseif ($display == "price_changes") {
			$params['HotSheet'] = "price";
			$outbound_criteria .= "&listingevent=price&listingeventhours={$hours}";
		}
		elseif ($display == "recent_sales") {
			$params['HotSheet'] = "sold";
			$outbound_criteria .= "&status=C&listingevent=status&listingeventhours={$hours}";
		}

		if ($sort == "recently_changed") {
			// nothing to do.  this is the default
		}
		elseif ($sort == "price_low_high") {
			$params['_orderby'] = "+ListPrice";
		}
		elseif ($sort == "price_high_low") {
			$params['_orderby'] = "-ListPrice";
		}



		$api_system_info = $fmc_api->SystemInfo();
		$apply_property_type = false;

		if ($source == "my") {
			$outbound_criteria .= "&my_listings=true";
			// make a simple request to /my/listings with no _filter's
			$api_listings = $fmc_api->MyListings( $params );
		}
		elseif ($source == "office") {
			$api_listings = $fmc_api->OfficeListings( $params );
		}
		elseif ($source == "company") {
			$api_listings = $fmc_api->CompanyListings( $params );
		}
		else {
			
			// set this to true since only GetListings() calls can have _filter's applied
			$apply_property_type = true;
			
			// parse the given Locations for the _filter

			$locations = flexmlsConnect::parse_location_search_string($location);

			$location_conditions = array();
			$location_field_names = array();

			foreach ($locations as $loc) {
				$location_conditions[] = "{$loc['f']} Eq '{$loc['v']}'";
				$location_field_names[] = $loc['f'];
			}

			$uniq_location_field_names = array_unique($location_field_names);

			if (count($location_conditions) > 1) {
				return "<span style='color:red;'>flexmls&reg; IDX: This IDX slideshow widget is configured with too many location search criteria options.  Please reduce to 1.</span>";
			}

			if (count($location_conditions) > 0) {
				$filter_conditions[] = implode(" Or ", $location_conditions);
			}
			if (!empty($property_type)) {
				$filter_conditions[] = "PropertyType Eq '{$property_type}'";
			}

			$link_details = flexmlsConnect::get_idx_link_details($link);
			if ($link_details['LinkType'] == "SavedSearch") {
				$filter_conditions[] = "SavedSearch Eq '{$link_details['SearchId']}'";
			}

			$params['_filter'] = implode(" And ", $filter_conditions);

			$api_listings = $fmc_api->Listings( $params );
		}
		
		$total_js_pages = ceil($fmc_api->last_count / ($horizontal * $vertical));
		
		if ($fmc_api->last_count == 1) {
			$show_count = "1 Listing";
		}
		else {
			$show_count = number_format($fmc_api->last_count) . " Listings";
		}

		if ($api_listings === false || $api_system_info === false) {
			return flexmlsConnect::widget_not_available($fmc_api, false, $args, $settings);
		}

		if (!is_array($uniq_location_field_names)) {
			$uniq_location_field_names = array();
		}

		if (!is_array($locations)) {
			$locations = array();
		}

		$link_transform_params = array();
		foreach ($uniq_location_field_names as $loc_name) {
			$link_transform_params["{$loc_name}"] = "*{$loc_name}*";
		}

		if ($apply_property_type) {
			$link_transform_params["PropertyType"] = "*PropertyType*";
		}

		// make the API call to translate standard field names
		$outbound_link = $fmc_api->GetTransformedIDXLink($link, $link_transform_params);
		$this_link = $outbound_link;

		foreach ($locations as $loc) {
			// start replacing the placeholders in the link with the real values for this link
			$this_link = preg_replace('/\*'.preg_quote($loc['f']).'\*/', $loc['v'], $this_link);
		}

		if ($apply_property_type) {
			$this_link = preg_replace('/\*PropertyType\*/', $property_type, $this_link);
		}

		// replace all remaining placeholders with a blank value since it doesn't apply to this link
		$this_link = preg_replace('/\*(.*?)\*/', "", $this_link);
		$this_link .= $outbound_criteria;


		$destination_link = "";

		if (!empty($this_link) && !$fmc_api->HasBasicRole()) {
			$destination_link = $this_link;
		}

		$return .= $before_widget;

		$carousel_class = "flexmls_connect__carousel";
		if ($tall_carousel) {
			$carousel_class .= " tall";
		}


		if ($type != "ajax") {
			$div_box = "<div class='{$carousel_class}' data-connect-vertical='{$vertical}' data-connect-horizontal='{$horizontal}' data-connect-autostart='{$auto_rotate}' data-connect-settings=\"{$encoded_settings}\" data-connect-total-pages='{$total_js_pages}'>\n";

			if ( !empty($title) ) {
				$title_line = "\t" . $before_title . $title . $after_title . "\n";
			}

			if ($type == "widget") {
				$return .= $title_line . $div_box;
			}
			else {
				$return .= $div_box . $title_line;
			}

			$this_target = "";
			if (flexmlsConnect::get_destination_window_pref() == "new") {
				$this_target = " target='_blank'";
			}


			if (!empty($destination_link)) {
				$return .= "\t<div class='flexmls_connect__count'><a href='".flexmlsConnect::make_destination_link($destination_link)."'{$this_target}>{$show_count}</a></div>\n";
			}
			else {
				$return .= "\t<div class='flexmls_connect__count'>{$show_count}</div>\n";
			}

			$return .= "\t<div class='flexmls_connect__container'>\n";
			$return .= "\t\t<div class='flexmls_connect__slides'>\n";

		}

		$rand = mt_rand();

		$total_listings = 0;
		if (is_array($api_listings)) {
			foreach ($api_listings as $li) {
				$total_listings++;
				$show_idx_badge = "";

				$listing = $li['StandardFields'];
				$one_line_address = "{$listing['StreetNumber']} {$listing['StreetDirPrefix']} {$listing['StreetName']} ";
				$one_line_address .= "{$listing['StreetSuffix']} {$listing['StreetDirSuffix']}";
				$one_line_address = str_replace("********", "", $one_line_address);
				$one_line_address = flexmlsConnect::clean_spaces_and_trim($one_line_address);

				$first_line_address = $one_line_address;

				$second_line_address = "{$listing['City']}, {$listing['StateOrProvince']} {$listing['PostalCode']}";
				$second_line_address = str_replace("********", "", $second_line_address);

				$one_line_address .= ", {$second_line_address}";
				$one_line_address = flexmlsConnect::clean_spaces_and_trim($one_line_address);



				$price = '$'. number_format($listing['ListPrice'], 0);

				if ($source != "my" && $source != "my_office") {
					if (array_key_exists('IdxLogoSmall', $api_system_info['Configuration'][0]) && !empty($api_system_info['Configuration'][0]['IdxLogoSmall'])) {
						$show_idx_badge = "<img src='{$api_system_info['Configuration'][0]['IdxLogoSmall']}' class='flexmls_connect__badge_image' title='{$listing['ListOfficeName']}' />\n";
					}
					else {
						$show_idx_badge = "<span class='flexmls_connect__badge' title='{$listing['ListOfficeName']}'>IDX</span>\n";
					}
				}

				$relevant_info_line = "";

				if ($display == "open_houses") {
					$relevant_info_line = $listing['OpenHouses'][0]['Date'] . " " . $listing['OpenHouses'][0]['StartTime'];
				}
				else {
					$relevant_info_line = $price;
				}

				$tall_line = "";
				$extra_title_line = "";
				
				if ($tall_carousel) {
					$show_additional_field_line = array();
					foreach ($show_additional_fields as $fi) {
						if ($fi == "beds") {
							$show_additional_field_line[] = "{$listing['BedsTotal']} beds";
						}
						elseif ($fi == "baths") {
							$show_additional_field_line[] = "{$listing['BathsTotal']} baths";
						}
						elseif ($fi == "sqft") {
							$show_additional_field_line[] = number_format($listing['BuildingAreaTotal'])." sqft";
						}
					}

					$extra_title_line = ' | '. implode(" | ", $show_additional_field_line);
					$tall_line = "<small class='dark'>". implode(" &nbsp; ", $show_additional_field_line) ."</small>";
				}



				$link_to_start = "<a>";
				$link_to_end = "</a>";
				$this_link = "";
				$this_target = "";

				if (!empty($destination_link)) {
					$this_link = flexmlsConnect::make_destination_link("{$destination_link}&start=details&start_id={$listing['ListingKey']}");
					if (flexmlsConnect::get_destination_window_pref() == "new") {
						$this_target = " target='_blank'";
					}
					$link_to_start = "<a href='{$this_link}'{$this_target}>";
					$link_to_end = "</a>";
				}

				$main_photo_uri300 = "";
				$main_photo_caption = "";
				$main_photo_urilarge = "";

				$photo_return = '';

				$photo_count = 0;
				foreach ($listing['Photos'] as $photo) {
					$photo_count++;
					if ($photo_count == 1) {
						continue;
					}
					$caption = htmlspecialchars($photo['Caption'], ENT_QUOTES);
					$photo_return .= "<a href='{$photo['UriLarge']}' class='popup' rel='{$rand}-{$listing['ListingKey']}' title='{$caption}'></a>\n";

					if ($photo['Primary'] == true) {
						$main_photo_caption = $caption;
						$main_photo_uri300 = $photo['Uri300'];
						$main_photo_urilarge = $photo['UriLarge'];
					}

				}

				// default to the first photo given if the primary isn't set
				if (empty($main_photo_urilarge)) {
					$main_photo_caption = htmlspecialchars($listing['Photos'][0]['Caption'], ENT_QUOTES);
					$main_photo_uri300 = $listing['Photos'][0]['Uri300'];
					$main_photo_urilarge = $listing['Photos'][0]['UriLarge'];
				}

				if (empty($main_photo_uri300)) {
					$main_photo_uri300 = "{$fmc_plugin_url}/images/nophoto.gif";
					$listing['Photos'][0]['UriLarge'] = $main_photo_uri300;
					$main_photo_caption = "No Photo Available";
				}

				$return .= "<!-- Listing -->
						<div title='{$first_line_address}, {$listing['City']}, {$listing['StateOrProvince']} {$listing['PostalCode']} | MLS #: {$listing['ListingId']} | {$price}{$extra_title_line}' link='{$this_link}' target=\"{$this_target}\">
							<a href='{$listing['Photos'][0]['UriLarge']}' class='popup' rel='{$rand}-{$listing['ListingKey']}' title='{$main_photo_caption}'>
								<img src='{$main_photo_uri300}' style='width:134px;height:100px' alt='' />
							</a>
							<p class='caption'>
								{$link_to_start}
								{$relevant_info_line}
								{$tall_line}
								<small>{$first_line_address}<br />{$second_line_address}</small>
								{$link_to_end}
							</p>
							{$show_idx_badge}
							<div class='flexmls_connect__hidden'>
								";

							$return .= $photo_return;

				$return .= "			</div>
						</div>\n\n";
			}
		}

		if ($type != "ajax") {
// now that we use AJAX to load more, no need for the View All Listings slide
// removed as a part of WP-7 by Brandon Medenwald on 3/8/2011
//			if ($fmc_api->last_count > count($api_listings) && !empty($destination_link)) {
//				$return .= "\t<div title='Click to View All Listings'>\n";
//				$this_target = "";
//				if (flexmlsConnect::get_destination_window_pref() == "new") {
//					$this_target = " target='_blank'";
//				}
//				$return .= "\t\t<p class='caption'><a href='".flexmlsConnect::make_destination_link($destination_link)."' class='flexmls_connect__more_anchor'{$this_target}>View All Listings<small>All ".number_format($fmc_api->last_count)." Listings</small></a>\n";
//				$return .= "\t</div>\n";
//			}

			$return .= "</div>\n";
			$return .= "</div>\n";

			if ($total_listings > 0) {
				$return .= "<a href='#' class='previous'>previous</a><a href='#' class='next'>next</a>";

				if ($source != "my" && $source != "my_office") {
					$return .= "<p class='flexmls_connect__disclaimer'>\n";

					if (array_key_exists('IdxLogoSmall', $api_system_info['Configuration'][0]) && !empty($api_system_info['Configuration'][0]['IdxLogoSmall'])) {
						$return .= "<img src='{$api_system_info['Configuration'][0]['IdxLogoSmall']}' class='flexmls_connect__badge_image' title='Read the full IDX Listings Disclosure' />\n";
					}
					else {
						$return .= "  <span class='flexmls_connect__badge' title='Read the full IDX Listings Disclosure'>IDX</span>\n";
					}

					$return .= "  <a title='Read the full IDX Listings Disclosure'>MLS IDX Listing Disclosure &copy; ".date("Y")."</a>\n";
					$return .= "</p>\n";
					$return .= "<p class='flexmls_connect__hidden flexmls_connect__disclaimer_text'>\n";
					$return .= $api_system_info['Configuration'][0]['IdxDisclaimer'];
					$return .= "\n";
					$return .= "</p>\n";
				}

			}

			$return .= "</div>\n";

			$return .= $after_widget;
		}

		return $return;

	}


	function widget($args, $instance) {
		echo $this->cache_jelly($args, $instance, "widget");
	}


	function shortcode($attr = array()) {

		$args = array(
				'before_title' => '<h3>',
				'after_title' => '</h3>',
				'before_widget' => '',
				'after_widget' => ''
				);

		return $this->cache_jelly($args, $attr, "shortcode");

	}


	function settings_form($instance) {
		global $fmc_api;

		$title = esc_attr($instance['title']);
		$horizontal = esc_attr($instance['horizontal']);
		$vertical = esc_attr($instance['vertical']);
		$auto_rotate = esc_attr($instance['auto_rotate']);
		$source = esc_attr($instance['source']);
		$display = esc_attr($instance['display']);
		$property_type = esc_attr($instance['property_type']);
		$link = esc_attr($instance['link']);
		$location = $instance['location'];
		$sort = esc_attr($instance['sort']);
		$additional_fields = esc_attr($instance['additional_fields']);

		$selected_code = " selected='selected'";
		$checked_code = " checked='checked'";

		$horizontal_options = array(1, 2, 3, 4, 5, 6, 7, 8);

		$vertical_options = array(1, 2, 3, 4, 5, 6, 7, 8);

		$auto_rotate_options = array(
				0 => "Off",
				1000 => "1 second",
				2000 => "2 seconds",
				3000 => "3 seconds",
				4000 => "4 seconds",
				5000 => "5 seconds",
				6000 => "6 seconds",
				7000 => "7 seconds",
				8000 => "8 seconds",
				9000 => "9 seconds",
				10000 => "10 seconds",
				15000 => "15 seconds",
				20000 => "20 seconds",
				25000 => "25 seconds",
				30000 => "30 seconds",
				60000 => "1 minute"
				);

		$source_options = array(
				"my" => "My Listings",
				"office" => "My Office's Listings",
				"company" => "My Company's Listings"
		);

		$display_options = array(
				"all" => "All Listings",
				"new" => "New Listings",
				"open_houses" => "Open Houses",
				"price_changes" => "Recent Price Changes",
				"recent_sales" => "Recent Sales"
		);

		$sort_options = array(
				"recently_changed" => "Recently changed first",
				"price_low_high" => "Price, low to high",
				"price_high_low" => "Price, high to low"
		);

		$additional_field_options = array(
			'beds' => "Bedrooms",
			'baths' => "Bathrooms",
			'sqft' => "Square Footage"
		);

		$additional_fields_selected = array();
		if (!empty($additional_fields)) {
			$additional_fields_selected = explode(",", $additional_fields);
		}

		$api_property_type_options = $fmc_api->PropertyTypes();
		$api_system_info = $fmc_api->SystemInfo();
		$api_location_search_api = $fmc_api->GetLocationSearchApiUrl();

		if ($api_property_type_options === false || $api_system_info === false || $api_location_search_api === false) {
			return flexmlsConnect::widget_not_available($fmc_api, true);
		}

		// only show the Location option if this user is allowed to show those types of listings
		if (!$fmc_api->HasBasicRole()) {
			$source_options['location'] = "Location";
		}

		if (empty($source)) {
			$source = "location";
		}

		if (array_key_exists('_instance_type', $instance) && $instance['_instance_type'] == "shortcode") {
			$special_neighborhood_title_ability = flexmlsConnect::special_location_tag_text();
		}

		$return = "";

		$return .= "

			<p>
				<label for='".$this->get_field_id('title')."'>" . __('Title:') . "</label>
				<input fmc-field='title' fmc-type='text' type='text' class='widefat' id='".$this->get_field_id('title')."' name='".$this->get_field_name('title')."' value='{$title}'>
				$special_neighborhood_title_ability
			</p>

				";

		if (!$fmc_api->HasBasicRole()) {
			$api_links = flexmlsConnect::get_all_idx_links();

			$return .= "
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
						</select><br /><span class='description'>Link used when a listing is viewed</span>
				</p>
				";
		}


		$return .= "
			<p>
				<label for='".$this->get_field_id('horizontal')."'>" . __('Slideshow Dimensions:') . "</label>
					<select fmc-field='horizontal' fmc-type='select' id='".$this->get_field_id('horizontal')."' name='".$this->get_field_name('horizontal')."'>
						";

		foreach ($horizontal_options as $k) {
			$is_selected = ($k == $horizontal) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}>{$k}</option>\n";
		}

		$return .= "
					</select> &times; <select fmc-field='vertical' fmc-type='select' id='".$this->get_field_id('vertical')."' name='".$this->get_field_name('vertical')."'>
						";

		foreach ($vertical_options as $k) {
			$is_selected = ($k == $vertical) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}>{$k}</option>\n";
		}

		$return .= "
					</select>
				<br /><span class='description'>Horizontal &times; Vertical</span>
			</p>

			<p>
				<label for='".$this->get_field_id('auto_rotate')."'>" . __('Slideshow:') . "
					<select fmc-field='auto_rotate' fmc-type='select' id='".$this->get_field_id('auto_rotate')."' name='".$this->get_field_name('auto_rotate')."'>
						";

		foreach ($auto_rotate_options as $k => $v) {
			$is_selected = ($k == $auto_rotate) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}>{$v}</option>\n";
		}

		$return .= "
					</select>
				</label>
			</p>

			<p>
				<label for='".$this->get_field_id('source')."'>" . __('Filter by:') . "</label>
					<select fmc-field='source' fmc-type='select' id='".$this->get_field_id('source')."' name='".$this->get_field_name('source')."' class='flexmls_connect__listing_source'>
						";

		foreach ($source_options as $k => $v) {
			$is_selected = ($k == $source) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}{$is_disabled}>{$v}</option>\n";
		}

		$hidden_location = ($source != "location") ? " style='display:none;'" : "";
		$no_filter_warning = ($source == "location") ? "display:none;" : "";

		$return .= "
					</select><br /><span class='description'>Which listings to display</span>
			</p>

			<p style='{$no_filter_warning}color:red;' class='flexmls_connect__no_filters_applied'>Note: Criteria applied to the selected saved search link above will not affect listings displayed.</p>

			<p class='flexmls_connect__location_property_type_p' {$hidden_location}>
				<label for='".$this->get_field_id('property_type')."'>" . __('Property Type:') . "</label>
				<select fmc-field='property_type' class='flexmls_connect__property_type' fmc-type='select' id='".$this->get_field_id('property_type')."' name='".$this->get_field_name('property_type')."'>
						";

		foreach ($api_property_type_options as $k => $v) {
			$is_selected = ($k == $property_type) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}{$is_disabled}>{$v}</option>\n";
		}

		$return .= "
				</select>
			</p>

			<div class='flexmls_connect__location'{$hidden_location}>
				<p>
				<label for='horizontal'>Location:</label>
				<input type='text' name='location_input' data-connect-url='{$api_location_search_api}' class='flexmls_connect__location_search' autocomplete='off' value='City, Postal Code, etc.' />
				<a href='javascript:void(0);' title='Click here to browse through available locations' class='flexmls_connect__location_browse'>Browse &raquo;</a>
				<div class='flexmls_connect__location_list' data-connect-multiple='false'>
					<p>All Locations Included</p>
				</div>
				<input type='hidden' name='tech_id' class='flexmls_connect__tech_id' value=\"x'{$api_system_info['Id']}'\" />
				<input type='hidden' name='ma_tech_id' class='flexmls_connect__ma_tech_id' value=\"x'{$api_system_info['MlsId']}'\" />
				<input fmc-field='location' fmc-type='text' type='hidden' name='".$this->get_field_name('location')."' class='flexmls_connect__location_fields' value=\"{$location}\" />
				</p>
			</div>

			<p>
				<label for='".$this->get_field_id('display')."'>" . __('Display:') . "
					<select fmc-field='display' fmc-type='select' id='".$this->get_field_id('display')."' name='".$this->get_field_name('display')."'>
						";

		foreach ($display_options as $k => $v) {
			$is_selected = ($k == $display) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}{$is_disabled}>{$v}</option>\n";
		}

		$return .= "
					</select>
				</label>
			</p>

			<p>
				<label for='".$this->get_field_id('sort')."'>" . __('Sort by:') . "
					<select fmc-field='sort' fmc-type='select' id='".$this->get_field_id('sort')."' name='".$this->get_field_name('sort')."'>
						";

		foreach ($sort_options as $k => $v) {
			$is_selected = ($k == $sort) ? $selected_code : "";
			$return .= "<option value='{$k}'{$is_selected}{$is_disabled}>{$v}</option>\n";
		}

		$return .= "
					</select>
				</label>
			</p>

			<p>
				<label for='".$this->get_field_id('additional_fields')."'>" . __('Additional Fields to Show:') . "</label>

				";

		foreach ($additional_field_options as $k => $v) {
			$return .= "<div>";
			$this_checked = (in_array($k, $additional_fields_selected)) ? $checked_code : "";
			$return .= " &nbsp; &nbsp; &nbsp; <input fmc-field='additional_fields' fmc-type='checkbox' type='checkbox' name='".$this->get_field_name('additional_fields')."[{$k}]' value='{$k}' id='".$this->get_field_id('additional_fields')."-".$k."'{$this_checked} /> ";
			$return .= "<label for='".$this->get_field_id('additional_fields')."-".$k."'>{$v}</label>";
			$return .= "</div>\n";
		}

		$return .= "
			</p>

			<img src='x' class='flexmls_connect__bootloader' onerror='flexmls_connect.location_setup(this);' />

					";

		if ($fmc_api->HasBasicRole()) {
			$return .= "<p><span style='color:red;'>Note:</span> <a href='http://flexmls.com/' target='_blank'>flexmls&reg; IDX subscription</a> is required in order to show IDX listings and to link listings to full detail pages.</p>";
		}


		$return .= "<input type='hidden' name='shortcode_fields_to_catch' value='title,link,horizontal,vertical,auto_rotate,source,property_type,location,display,sort,additional_fields' />\n";
		$return .= "<input type='hidden' name='widget' value='". get_class($this) ."' />\n";

		return $return;

	}




	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['horizontal'] = strip_tags($new_instance['horizontal']);
		$instance['vertical'] = strip_tags($new_instance['vertical']);
		$instance['auto_rotate'] = strip_tags($new_instance['auto_rotate']);
		$instance['source'] = strip_tags($new_instance['source']);
		$instance['display'] = strip_tags($new_instance['display']);
		$instance['property_type'] = strip_tags($new_instance['property_type']);
		$instance['link'] = strip_tags($new_instance['link']);
		$instance['location'] = strip_tags($new_instance['location']);
		$instance['sort'] = strip_tags($new_instance['sort']);

		$additional_fields_selected = "";
		if (is_array($new_instance['additional_fields'])) {
			foreach ($new_instance['additional_fields'] as $v) {
				if (!empty($additional_fields_selected)) {
					$additional_fields_selected .= ",";
				}
				$additional_fields_selected .= strip_tags(trim($v));
			}
		}

		$instance['additional_fields'] = $additional_fields_selected;

		return $instance;
	}


	function additional_photos() {
		global $fmc_api;

		$full_id = flexmlsConnect::wp_input_get_post('id');
		$id = $full_id;
		$id = substr($id, -26, 26);

		$photos = $fmc_api->ListingPhotos($id);

		$return = array();

		if (is_array($photos)) {
			foreach ($photos as $photo) {
				$return[] = array('photo' => $photo['UriLarge'], 'caption' => htmlspecialchars($photo['Caption'], ENT_QUOTES) );
			}
			echo flexmlsJSON::json_encode($return);
		}
		else {
			echo flexmlsJSON::json_encode( false );
		}

		die();

	}


	function additional_slides() {

		// no arguments need to be passed for prepping the AJAX response
		$args = array();

		$settings_string = flexmlsConnect::wp_input_get_post('settings');

		// these get parsed from the sent AJAX response
		$settings = unserialize($settings_string);
		$listings_from = flexmlsConnect::wp_input_get_post('page');

		$horizontal = $settings['horizontal'];
		$vertical = $settings['vertical'];

		$total_listings_to_show = ($horizontal * $vertical);
		if ($total_listings_to_show > 25) {
			list($horizontal, $vertical) = flexmlsConnect::generate_appropriate_dimensions($horizontal, $vertical);
		}
		$total_listings_to_show = ($horizontal * $vertical);

		$api_limit = flexmlsConnect::generate_api_limit_value($horizontal, $vertical);

		$settings['page'] = ceil( $listings_from / ($api_limit / $total_listings_to_show) );

		$type = "ajax";

		echo $this->cache_jelly($args, $settings, $type);

		die();

	}


}
