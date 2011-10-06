<?php


class fmcWidget extends WP_Widget {

	function shortcode_form() {
		global $fmc_widgets;

		$widget_info = $fmc_widgets[ get_class($this) ];

		$settings_content = $this->settings_form( array('_instance_type' => 'shortcode') );
		$settings_content = $settings_content;

		$response = array(
				'title' => $widget_info['title'] .' widget',
				'body' => flexmlsConnect::shortcode_header() . $settings_content . flexmlsConnect::shortcode_footer()
		);

		echo flexmlsJSON::json_encode($response);

		exit;

	}
	

	function cache_jelly($args, $instance, $type) {
		global $fmc_widgets;

		$widget_info = $fmc_widgets[ get_class($this) ];

		$cache_item_name = md5(get_class($this) .'_'. serialize($instance) . $type);
		$cache = get_transient('fmc_cache_'. $cache_item_name);

		if (!empty($cache) && flexmlsConnect::cache_turned_on() == true) {
			$return = $cache;
		}
		else {
			$return = $this->jelly($args, $instance, $type);
			$cache_set_result = set_transient('fmc_cache_'. $cache_item_name, $return, $widget_info['max_cache_time']);

			// update transient item which tracks cache items
			$cache_tracker = get_transient('fmc_cache_tracker');
			$cache_tracker[ $cache_item_name ] = true;
			set_transient('fmc_cache_tracker', $cache_tracker, 60*60*24*7);
		}

		return $return;

	}


	function form($instance) {
		echo $this->settings_form($instance);
	}


	function shortcode_generate() {
		global $fmc_widgets;

		$widget_info = $fmc_widgets[ get_class($this) ];

		$shortcode = "[{$widget_info['shortcode']}";
		
		$is_service_lacking_filter_support = false;
		$shortcode_source = flexmlsConnect::wp_input_get_post('source');
		if ($shortcode_source != "location") {
			$is_service_lacking_filter_support = true;
		}
		$is_slideshow_widget = ($widget_info['shortcode'] == "idx_slideshow") ? true : false;

		foreach ($_REQUEST as $k => $v) {
			if ($k == "action") {
				continue;
			}
			
			if ($is_slideshow_widget && $is_service_lacking_filter_support && ($k == "property_type" || $k == "location")) {
				continue;
			}
			
			if (!empty($v)) {
				$v = htmlentities(stripslashes($v), ENT_QUOTES);
				$shortcode .= " {$k}=\"{$v}\"";
			}
		}

		$shortcode .= "]";

		$response = array(
				'body' => $shortcode
		);

		echo flexmlsJSON::json_encode($response);
		
		exit;

	}


	function get_field_id($val) {
		$widget = $this->is_called_for_widget();
		if ($widget) {
			return parent::get_field_id($val);
		}
		else {
			return "fmc_shortcode_field_{$val}";
		}
	}


	function get_field_name($val) {
		$widget = $this->is_called_for_widget();
		if ($widget) {
			return parent::get_field_name($val);
		}
		else {
			return $val;
		}
	}


	function is_called_for_widget() {
		// find out what context this was called from
		$backtrace = debug_backtrace();
		if ($backtrace[3]['function'] == "shortcode_form") {
			return false;
		}
		else {
			return true;
		}
	}

}
