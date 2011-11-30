<?php



class fmcListingDetails extends fmcWidget {

	function fmcListingDetails() {
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
		
		$custom_page = new flexmlsConnectPageListingDetails;
		$custom_page->pre_tasks('-mls_'. trim($settings['listing']) );
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
		$listing = esc_attr($instance['listing']);

		$return  = "<p>\n";
		$return .= "<label for='".$this->get_field_id('title')."'>" . __('MLS#:') . "</label>\n";
		$return .= "<input fmc-field='listing' fmc-type='text' type='text' class='widefat' id='".$this->get_field_id('listing')."' name='".$this->get_field_name('listing')."' value='{$listing}'>\n";
		$return .= "</p>\n";

		$return .= "<input type='hidden' name='shortcode_fields_to_catch' value='listing' />\n";
		$return .= "<input type='hidden' name='widget' value='". get_class($this) ."' />\n";

		return $return;

	}


	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		
		$instance['listing'] = strip_tags($new_instance['listing']);

		return $instance;
	}

}
