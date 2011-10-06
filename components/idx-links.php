<?php



class fmcIDXLinks extends fmcWidget {

	function fmcIDXLinks() {
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

		$options = get_option('fmc_settings');

		extract($args);

		if ($type == "widget" && empty($settings['title']) && flexmlsConnect::use_default_titles()) {
			$settings['title'] = "IDX Links";
		}
		
		$return = '';

		$title = trim($settings['title']);
		$my_links = $settings['links'];

		$links_selected = explode(",", $my_links);

		$links_to_show = "";

		$api_links = $fmc_api->GetIDXLinks();

		if ($api_links === false) {
			return flexmlsConnect::widget_not_available($fmc_api, false, $args, $settings);
		}

		// some small shuffling to maintain the given order of the links
		$valid_links = array();
		foreach ($api_links as $link) {
			$valid_links[$link['LinkId']] = array('Uri' => $link['Uri'], 'Name' => $link['Name']);
		}

		foreach ($links_selected as $link) {
			if ( array_key_exists($link, $valid_links) || empty($my_links)) {
				$this_link = $valid_links[$link];

				$destination_link = flexmlsConnect::make_destination_link($this_link['Uri']);

				$this_target = "";
				if (flexmlsConnect::get_destination_window_pref() == "new") {
					$this_target = " target='_blank'";
				}

				$links_to_show .= "<li><a href='{$destination_link}' title='{$this_link['Name']}'{$this_target}>{$this_link['Name']}</a></li>\n";
			}
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
		$links = esc_attr($instance['links']);

		$links_selected = explode(",", $links);

		$selected_code = " checked='checked'";

		$api_links = $fmc_api->GetIDXLinks();

		if ($api_links === false) {
			return flexmlsConnect::widget_not_available($fmc_api, true);
		}

		$return = "";

		$return .= "

			<p>
				<label for='".$this->get_field_id('title')."'>" . __('Title:') . "
					<input fmc-field='title' fmc-type='text' type='text' class='widefat' id='".$this->get_field_id('title')."' name='".$this->get_field_name('title')."' value='{$title}'>
				</label>
			</p>

			<p>
				<label for='".$this->get_field_id('links')."'>" . __('Links to Display:') . "</label>

				";

		foreach ($api_links as $link) {
			$return .= "<div>";
			$this_selected = (in_array($link['LinkId'], $links_selected)) ? $selected_code : "";
			$return .= "<input fmc-field='links' fmc-type='checkbox' type='checkbox' name='".$this->get_field_name('links')."[{$link['LinkId']}]' value='{$link['LinkId']}' id='".$this->get_field_id('links')."-".$link['LinkId']."'{$this_selected} /> ";
			$return .= "<label for='".$this->get_field_id('links')."-".$link['LinkId']."'>{$link['Name']}</label>";
			$return .= "</div>\n";
		}

		$return .= "<span class='description'>Links can be managed inside the flexmls&reg; Web IDX Manager</span>";

		$return .= "
			</p>
			";

		$return .= "<input type='hidden' name='shortcode_fields_to_catch' value='title,links' />\n";
		$return .= "<input type='hidden' name='widget' value='". get_class($this) ."' />\n";

		return $return;
	}


	function update($new_instance, $old_instance) {

		$links_selected = "";
		if (is_array($new_instance['links'])) {
			foreach ($new_instance['links'] as $link) {
				if (!empty($links_selected)) {
					$links_selected .= ",";
				}
				$links_selected .= strip_tags($link);
			}
		}

		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['links'] = $links_selected;
		return $instance;
	}


}
