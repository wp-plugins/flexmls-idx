<?php



class fmcNeighborhoods extends fmcWidget {

	function fmcNeighborhoods() {
		global $fmc_widgets;

		$widget_info = $fmc_widgets[ get_class($this) ];

		$widget_ops = array( 'description' => $widget_info['description'] );
//		$this->WP_Widget( get_class($this) , $widget_info['title'], $widget_ops);

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
		global $fmc_widgets;

		$return = '';

		$title = trim($settings['title']);
		$location = html_entity_decode(flexmlsConnect::clean_comma_list($settings['location']));
		$template = trim($settings['template']);

		$page_content = flexmlsConnect::get_neighborhood_template_content($template);

		if ($page_content === false) {
			// no appropriate template page is selected.
			return "<span style='color:red;'>flexmls&reg; IDX: This neighborhood feature requires a template to be selected from the Settings > flexmls IDX dashboard within WordPress.</span>";
		}
		
		$page_content = str_replace("{Location}", $title, $page_content);

		// parse the location search setting for this page
		$locations = flexmlsConnect::parse_location_search_string($location);

		// make a quick list of all of the widgets supported by our plugin
		$all_widget_shortcodes = array();
		foreach ($fmc_widgets as $class => $wdg) {
			$all_widget_shortcodes[] = $wdg['shortcode'];
		}

		// make a pipe delimited list of the shortcodes ready for the regular expression
		$tagregexp = implode('|', array_map('preg_quote', $all_widget_shortcodes));

		// find all matching shortcodes
		preg_match_all('/(.?)\[('.$tagregexp.')\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?(.?)/s', $page_content, $matches);

		// go through all of our shortcodes found on the template page and start adding/replacing location values
		foreach ($matches[0] as $found) {
			$full_tag = trim($found);
			if ( preg_match('/ (location|locations)=/', $full_tag) ) {
				// the 'location' or 'locations' attribute was found in this particular shortcode so replace it's value
				$new_tag = preg_replace('/ (location|locations)="(.*?)"/', ' location="'.$locations[0]['r'].'"', $full_tag);
			}
			else {
				// no 'location' or 'locations' attribute was found so add it to the end of the attributes
				$attr_name = "location";
				if ( preg_match('/^\[idx_location_links/', $full_tag) ) {
					$attr_name = "locations";
				}

				// anchor to the beginning of the shortcode.
				// an escaped shortcode (double close square brackets) is messed up if anchored to the end
				$new_tag = preg_replace('/^(.*?)\]/', '$1 '.$attr_name.'="'.$locations[0]['r'].'"]', $full_tag);
			}

			// replace the old shortcode on the template page with the one specific to this page
			$page_content = str_replace($full_tag, $new_tag, $page_content);
		}

		// run our new content back through WordPress for formatting and shortcode parsing
		$return .= apply_filters( 'the_content', $page_content );

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
		$location = $instance['location'];
		$template = $instance['template'];

		$api_system_info = $fmc_api->GetSystemInfo();
		$api_location_search_api = flexmlsConnect::get_locationsearch_url();

		$return = "

			<p>
				<label for='".$this->get_field_id('title')."'>" . __('Title:') . "</label>
				<input fmc-field='title' fmc-type='text' type='text' class='widefat' id='".$this->get_field_id('title')."' name='".$this->get_field_name('title')."' value='{$title}'>
			</p>

			<p>
				<label for='template'>Neighborhood Template:</label>
				";


			$args = array(
				'name' => 'template',
				'post_status' => 'draft',
				'echo' => false,
				'show_option_none' => '(Use Saved Default)',
				'selected' => $template
			);

			$page_selection = wp_dropdown_pages($args);
			if (!empty($page_selection)) {
				$return .= $page_selection;
			}
			else {
				$return .= "Please create a page as a draft to select it here.";
			}

		$return .= "
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
				<select style='display:none;' fmc-field='property_type' class='flexmls_connect__property_type' fmc-type='select' id='".$this->get_field_id('property_type')."' name='".$this->get_field_name('property_type')."'>
			    <option value='A' selected='selected'></option>
			  </select>
			</p>

			<img src='x' class='flexmls_connect__bootloader' onerror='flexmls_connect.location_setup(this);' />

					";

		$return .= "<input type='hidden' name='shortcode_fields_to_catch' value='title,location,template' />\n";
		$return .= "<input type='hidden' name='widget' value='". get_class($this) ."' />\n";

		return $return;

	}


	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['template'] = strip_tags($new_instance['template']);
		$instance['location'] = strip_tags($new_instance['location']);

		return $instance;
	}

}
