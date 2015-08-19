<?php

class fmcLeadGen extends fmcWidget {

  function fmcLeadGen() {

    parent::__construct();

    global $fmc_widgets;

    $class = get_class($this);

    $widget_info = $fmc_widgets[ $class ];

    $widget_ops = array( 'description' => $widget_info['description'] );
    $this->WP_Widget( $class , $widget_info['title'], $widget_ops);

    // have WP replace instances of [first_argument] with the return from the second_argument function
    add_shortcode($widget_info['shortcode'], array(&$this, 'shortcode'));

    // register where the AJAX calls should be routed when they come in
    add_action('wp_ajax_fmcLeadGen_submit', array(&$this, 'submit_lead') );
    add_action('wp_ajax_nopriv_fmcLeadGen_submit', array(&$this, 'submit_lead') );

    add_action("wp_ajax_{$class}_shortcode", array(&$this, 'shortcode_form') );
    add_action("wp_ajax_{$class}_shortcode_gen", array(&$this, 'shortcode_generate') );
    
  }


  function jelly($args, $settings, $type) {
    global $fmc_api;
    extract($args);

    if (!is_array($settings)) {
      $settings = array();
    }

    if ($type == "widget" && empty($settings['title']) && flexmlsConnect::use_default_titles()) {
      $settings['title'] = "Lead Generation";
    }

    $api_prefs = $fmc_api->GetPreferences();
    
    if ($api_prefs === false) {
      return flexmlsConnect::widget_not_available($fmc_api, false, $args, $settings);
    }

    if (!array_key_exists('RequiredFields', $api_prefs)) {
      $api_prefs['RequiredFields'] = array();
    }

    $title = array_key_exists('title', $settings) ? trim($settings['title']) : null;
    $blurb = array_key_exists('blurb', $settings) ? trim($settings['blurb']) : null;

    if (array_key_exists('buttontext', $settings) && $settings['buttontext']) {
      $buttontext = trim($settings['buttontext']);
    } else {
      $buttontext = "Submit";
    }

    if (array_key_exists('use_captcha', $settings) && $settings['use_captcha'] !== null) {
      $use_captcha = $settings['use_captcha'];
    } else {
      $use_captcha = null;
    }

    if (array_key_exists('success', $settings) && $settings['success']) {
      $success = trim($settings['success']);
    } else {
      $success = "Thank you for your request.";
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

  function admin_view_vars() {
    $view_vars = array();

    $view_vars["special_neighborhood_title_ability"] = null;
    if (array_key_exists('_instance_type', $this->instance) && $this->instance['_instance_type'] == "shortcode") {
      $view_vars["special_neighborhood_title_ability"] = flexmlsConnect::special_location_tag_text();
    }

    $view_vars["captcha_default"] = $this->options->use_captcha();

    return $view_vars;
  }


  function update($new_instance, $old_instance) {
    $instance = $old_instance;

    $instance['title'] = strip_tags($new_instance['title']);
    $instance['blurb'] = strip_tags($new_instance['blurb']);
    $instance['success'] = strip_tags($new_instance['success']);
    $instance['buttontext'] = strip_tags($new_instance['buttontext']);
    $instance['use_captcha'] = array_key_exists('use_captcha', $new_instance ) ? $new_instance['use_captcha'] : false;
    
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
    
    if (is_array($api_prefs) && !is_array($api_prefs['RequiredFields'])) {
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

    // check for spam submission
    if ($this->spam_submission()){
      $return = array('success' => true, 'nonce' => wp_create_nonce('fmcLeadGen'));
    }
    elseif ($success == true) {
      // create a contact in flexmls
      $contact = $fmc_api->AddContact($data, flexmlsConnect::send_notification());

      // add a message in flexmls
      $subject = $data['DisplayName']." would like you to contact them.";
      $body = "Message: " . flexmlsConnect::wp_input_get_post('message_body') . "\n";
      if ($data['PrimaryEmail'])
        $body .= "\nEmail: ".$data['PrimaryEmail'];
      if ($data['PrimaryPhoneNumber'])
        $body .= "\nPhone: ".$data['PrimaryPhoneNumber'];
      $body .= "\n\n(This message was generated by your WordPress flexmls&copy; Contact Me Form)\n";
      flexmlsConnect::message_me($subject, $body, $data['PrimaryEmail']);
      
      $return = array('success' => true, 'nonce' => wp_create_nonce('fmcLeadGen'));
    }
    else {
      $return = array('success' => false, 'nonce' => wp_create_nonce('fmcLeadGen'), 'message' => $message);
    }


    echo flexmlsJSON::json_encode($return);

    wp_die();

  }

  private function spam_submission() {
    return $this->honey_pot_has_value() || $this->captcha_fail();
  }

  private function honey_pot_has_value() {
    $fmc_verify = flexmlsConnect::wp_input_get_post('flexmls_connect__important');
    return !empty($fmc_verify);
  }

  private function captcha_fail() {
    $captcha_entry = flexmlsConnect::wp_input_get_post('captcha');
    $captcha_answer = flexmlsConnect::wp_input_get_post('captcha-answer');
    return $captcha_entry != $captcha_answer;
  }

}
