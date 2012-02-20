<?php



class fmcLeadGen extends fmcWidget {

	function fmcLeadGen() {
		global $fmc_widgets;

		$widget_info = $fmc_widgets[ get_class($this) ];

		$widget_ops = array( 'description' => $widget_info['description'] );
		$this->WP_Widget( get_class($this) , $widget_info['title'], $widget_ops);

		// have WP replace instances of [first_argument] with the return from the second_argument function
		add_shortcode($widget_info['shortcode'], array(&$this, 'shortcode'));

		// register where the AJAX calls should be routed when they come in
		add_action('wp_ajax_fmcLeadGen_submit', array(&$this, 'submit_lead') );
		add_action('wp_ajax_nopriv_fmcLeadGen_submit', array(&$this, 'submit_lead') );

		add_action('wp_ajax_'.get_class($this).'_shortcode', array(&$this, 'shortcode_form') );
		add_action('wp_ajax_'.get_class($this).'_shortcode_gen', array(&$this, 'shortcode_generate') );
		
	}


	function jelly($args, $settings, $type) {
		global $fmc_api;
		extract($args);

		if ($type == "widget" && empty($settings['title']) && flexmlsConnect::use_default_titles()) {
			$settings['title'] = "Lead Generation";
		}

		$return = '';

		$api_prefs = $fmc_api->GetPreferences();
		
		if ($api_prefs === false) {
			return flexmlsConnect::widget_not_available($fmc_api, false, $args, $settings);
		}

		if (!array_key_exists('RequiredFields', $api_prefs)) {
			$api_prefs['RequiredFields'] = array();
		}

		$title = trim($settings['title']);
		$blurb = trim($settings['blurb']);
		$buttontext = trim($settings['buttontext']);
		if (empty($buttontext)) {
			$buttontext = "Submit";
		}
		$success = trim($settings['success']);

		if (empty($success)) {
			$success = "Thank you for your request";
		}

		$return .= $before_widget;

		if ( !empty($title) ) {
			$return .= $before_title;
			$return .= $title;
			$return .= $after_title;
			$return .= "\n";
		}

		if ( !empty($blurb) ) {
			$return .= "<p>{$blurb}</p>\n";
		}

		$return .= "<form data-connect-validate='true' data-connect-ajax='true' action='".admin_url('admin-ajax.php')."'>\n";

		$return .= "<input type='hidden' name='action' value='fmcLeadGen_submit' />\n";
		$return .= "<input type='hidden' name='nonce' value='".wp_create_nonce('fmcLeadGen')."' />\n";
		$return .= "<input type='hidden' name='callback' value='?' />\n";
		$return .= "<input type='hidden' name='success-message' value='".htmlspecialchars($success, ENT_QUOTES)."' />\n";

		$return .= "<input type='text' name='name' data-connect-default='First and Last Name' data-connect-validate='text' /><br />\n";
		$return .= "<input type='text' name='email' data-connect-default='Email Address' data-connect-validate='email' /><br />\n";

		if ( in_array('address', $api_prefs['RequiredFields']) ) {
			$return .= "<input type='text' name='address' data-connect-default='Home Address' data-connect-validate='text' /><br />\n";
		}

		if ( in_array('address', $api_prefs['RequiredFields']) ) {
			$return .= "<input type='text' name='city' data-connect-default='City' data-connect-validate='text' /><br />\n";
		}

		if ( in_array('address', $api_prefs['RequiredFields']) ) {
			$return .= "<input type='text' name='state' data-connect-default='State' data-connect-validate='text' /><br />\n";
		}

		if ( in_array('address', $api_prefs['RequiredFields']) ) {
			$return .= "<input type='text' name='zip' data-connect-default='Zip' data-connect-validate='text' /><br />\n";
		}

		if ( in_array('phone', $api_prefs['RequiredFields']) ) {
			$return .= "<input type='text' name='phone' data-connect-default='Phone Number' data-connect-validate='phone' /><br />\n";
		}
		$return .= "<br />\n";

		$return .= "<input type='submit' value='{$buttontext}' />\n";

		$return .= "</form>";

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
		$blurb = esc_attr($instance['blurb']);
		$success = esc_attr($instance['success']);
		$buttontext = esc_attr($instance['buttontext']);

		if (array_key_exists('_instance_type', $instance) && $instance['_instance_type'] == "shortcode") {
			$special_neighborhood_title_ability = flexmlsConnect::special_location_tag_text();
		}

		$return = "";

		$return .= "

			<p>
				<label for='".$this->get_field_id('title')."'>" . __('Title:') . "
					<input fmc-field='title' fmc-type='text' type='text' class='widefat' id='".$this->get_field_id('title')."' name='".$this->get_field_name('title')."' value='{$title}'>
					$special_neighborhood_title_ability
				</label>
			</p>
			<p>
				<label for='".$this->get_field_id('blurb')."'>" . __('Description:') . "
					<textarea fmc-field='blurb' fmc-type='text' id='".$this->get_field_id('blurb')."' name='".$this->get_field_name('blurb')."'>{$blurb}</textarea>
				</label><br /><span class='description'>This text appears below the title</span>
			</p>
			<p>
				<label for='".$this->get_field_id('success')."'>" . __('Success Message:') . "
					<textarea fmc-field='success' fmc-type='text' id='".$this->get_field_id('success')."' name='".$this->get_field_name('success')."'>{$success}</textarea>
				</label><br /><span class='description'>This text appears after the user sends the information</span>
			</p>
			<p>
				<label for='".$this->get_field_id('buttontext')."'>" . __('Button Text:') . "
					<input fmc-field='buttontext' fmc-type='text' type='text' class='widefat' id='".$this->get_field_id('buttontext')."' name='".$this->get_field_name('buttontext')."' value='{$buttontext}'>
				</label><br /><span class='description'>Customize the text of the submit button</span>
			</p>

					";

		$return .= "<input type='hidden' name='shortcode_fields_to_catch' value='title,blurb,success,buttontext' />\n";
		$return .= "<input type='hidden' name='widget' value='". get_class($this) ."' />\n";

		return $return;
	}



	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['blurb'] = strip_tags($new_instance['blurb']);
		$instance['success'] = strip_tags($new_instance['success']);
		$instance['buttontext'] = strip_tags($new_instance['buttontext']);
		
		return $instance;
	}



	function submit_lead() {
		global $fmc_api;

		// verify that the AJAX hit is legit.  returns -1 and stops if not
		check_ajax_referer('fmcLeadGen', 'nonce');
		
		$api_prefs = $fmc_api->GetPreferences();

		$data = array();
		
		$success = true;
		$message = "";
		

		if (is_array($api_prefs) && !array_key_exists('RequiredFields', $api_prefs)) {
			$api_prefs['RequiredFields'] = array();
		}
		
		if (!is_array($api_prefs['RequiredFields'])) {
			$api_prefs['RequiredFields'] = array();
		}

		// check to see if all of the required fields were provided to us filled out
		$data['DisplayName'] = flexmlsConnect::wp_input_get_post('name');
		if ( in_array('name', $api_prefs['RequiredFields']) && empty($data['DisplayName'] ) ) {
			$success = false;
			$message = "Name is a required field.";
		}
		
		$data['PrimaryEmail'] = flexmlsConnect::wp_input_get_post('email');
		if ( in_array('email', $api_prefs['RequiredFields']) && empty($data['PrimaryEmail']) ) {
			$success = false;
			$message = "Email Address is a required field.";
		}
		
		$data['HomeStreetAddress'] = flexmlsConnect::wp_input_get_post('address');
		if ( in_array('address', $api_prefs['RequiredFields']) && empty($data['HomeStreetAddress']) ) {
			$success = false;
			$message = "Home Address is a required field.";
		}

		$data['HomeLocality'] = flexmlsConnect::wp_input_get_post('city');
		if ( in_array('address', $api_prefs['RequiredFields']) && empty($data['HomeLocality']) ) {
			$success = false;
			$message = "City is a required field.";
		}

		$data['HomeRegion'] = flexmlsConnect::wp_input_get_post('state');
		if ( in_array('address', $api_prefs['RequiredFields']) && empty($data['HomeRegion']) ) {
			$success = false;
			$message = "State is a required field.";
		}

		$data['HomePostalCode'] = flexmlsConnect::wp_input_get_post('zip');
		if ( in_array('address', $api_prefs['RequiredFields']) && empty($data['HomePostalCode']) ) {
			$success = false;
			$message = "Zip is a required field.";
		}
		
		$data['PrimaryPhoneNumber'] = flexmlsConnect::wp_input_get_post('phone');
		if ( in_array('phone', $api_prefs['RequiredFields']) && empty($data['PrimaryPhoneNumber']) ) {
			$success = false;
			$message = "Phone Number is a required field.";
		}

		if ($success == true) {
			$contact = $fmc_api->AddContact($data, flexmlsConnect::send_notification());
			$return = array('success' => true, 'nonce' => wp_create_nonce('fmcLeadGen'));
		}
		else {
			$return = array('success' => false, 'nonce' => wp_create_nonce('fmcLeadGen'), 'message' => $message);
		}


		echo flexmlsJSON::json_encode($return);

		die();

	}



}
