<?php


class flexmlsConnect {


	function __construct() {
		
	}


	function initial_init() {
		global $fmc_plugin_url;
	
		if (!is_admin()) {
			
			wp_enqueue_script('jquery');

			wp_register_script('fmc_connect', $fmc_plugin_url .'/includes/connect.min.js', array('jquery'));
			wp_enqueue_script('fmc_connect');

			wp_register_style('fmc_connect', $fmc_plugin_url .'/includes/connect.min.css');
			wp_enqueue_style('fmc_connect');

			if (flexmlsConnect::is_ie()) {
				wp_register_script('fmc_excanvas', $fmc_plugin_url .'/includes/excanvas.min.js');
				wp_enqueue_script('fmc_excanvas');
			}
			
		}
		else {

			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-sortable');

			wp_register_script('fmc_connect', $fmc_plugin_url .'/includes/connect_admin.min.js', array('jquery'));
			wp_enqueue_script('fmc_connect');

			wp_register_style('fmc_connect', $fmc_plugin_url .'/includes/connect_admin.min.css');
			wp_enqueue_style('fmc_connect');

			wp_register_style('fmc_jquery_ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/themes/ui-lightness/jquery-ui.css');
			wp_enqueue_style('fmc_jquery_ui');

			wp_localize_script('fmc_connect', 'fmcAjax', array( 'ajaxurl' => admin_url('admin-ajax.php'), 'pluginurl' => $fmc_plugin_url ) );
			
		}

		add_shortcode("idx_frame", array('flexmlsConnect', 'shortcode'));
		
	}


	function widget_init() {
		// Load all of the widgets we need for the plugin.

		global $fmc_plugin_dir;
		global $fmc_widgets;

		foreach ($fmc_widgets as $class => $wdg) {
			if ( file_exists($fmc_plugin_dir . "/components/{$wdg['component']}") ) {
				require_once($fmc_plugin_dir . "/components/{$wdg['component']}");
				if ( class_exists($class, false) && ($wdg['requires_key'] == false || ($wdg['requires_key'] == true && flexmlsConnect::has_api_saved())) ) {
					register_widget($class);
				}
			}
		}
		
		// register where the AJAX calls should be routed when they come in
		add_action('wp_ajax_fmcShortcodeContainer', array('flexmlsConnect', 'shortcode_container') );

	}
	

	function plugin_deactivate() {
		
		// delete the page this plugin automatically created initially
		$options = get_option('fmc_settings');
		wp_delete_post($options['autocreatedpage'], false);

		// delete all of the saved options and cache information stored since the site owner has deactivated us
		delete_option('fmc_settings');

		// get list of transient items to clear
		$cache_tracker = get_transient('fmc_cache_tracker');
		if (is_array($cache_tracker)) {
			foreach ($cache_tracker as $key => $value) {
				delete_transient('fmc_cache_'. $key);
			}
		}
		delete_transient('fmc_cache_tracker');
		delete_transient('fmc_api');
	}


	function plugin_activate() {

		$new_page = array(
				'post_title' => "Search",
				'post_content' => "[idx_frame width='100%' height='600']",
				'post_type' => 'page',
				'post_status' => 'publish'
		);

		$new_page_id = wp_insert_post($new_page);

		$options = array(
				'default_titles' => true,
				'enable_cache' => true,
				'destpref' => 'page',
				'destlink' => $new_page_id,
				'autocreatedpage' => $new_page_id
				);

		add_option('fmc_settings', $options);
	}


	function admin_menus_init() {

		// check if the user is able to manage options.  if not, boot them out
		if ( !function_exists('current_user_can') || !current_user_can('manage_options') ) {
			return;
		}

		if ( function_exists('add_options_page') ) {
			add_options_page('flexmls&reg; IDX Settings', 'flexmls&reg; IDX', 'manage_options', 'flexmls_connect', array('flexmlsConnect', 'settings_page') );
		}

	}

	

	function filter_mce_button($buttons) {
		array_push( $buttons, '|', 'fmc_button' );
		return $buttons;
	}

	function filter_mce_plugin($plugins) {
		global $fmc_plugin_url;
		$plugins['fmc'] = $fmc_plugin_url . '/includes/tinymce_plugin.min.js';
		return $plugins;
	}


	function shortcode_container() {
		global $fmc_widgets;

		$return = '';

		$return .= "<div id='fmc_box_body'>";
		$return .= "<ul class='flexmls_connect__widget_menu'>\n";

		foreach ($fmc_widgets as $class => $widg) {
			$short_title = str_replace("flexmls&reg;: ", "", $widg['title']);
			$return .= "<li class='flexmls_connect__widget_menu_item'><a class='fmc_which_shortcode' data-connect-shortcode='{$class}' style='cursor:pointer;'>{$short_title}</a></li>\n";
		}
		$return .= "</ul>\n";

		$return .= "<div id='fmc_shortcode_window_content'><p class='first'>please select a widget to the left</p></div>";

		$return .= "</div>";

		$response['title'] = "";
		$response['body'] = $return;
		$js = new Moxiecode_JSON();
		echo str_replace("\'", "'", $js->encode($response));
		exit;
	}

	
	// called to put the start of the form on the shortcode generator page
	function shortcode_header() {
		$return = "\n\n";
		$return .= "<form fmc-shortcode-form='true'>\n";

		return $return;
	}

	// called to put the end of the form and submit button on the shortcode generator page
	function shortcode_footer() {
		$return = "";
		$return .= "<p><input type='button' class='fmc_shortcode_submit button-primary' value='Insert Widget' /></p>\n";
		$return .= "</form>\n";

		$return .= "\n\n";

		return $return;
	}


	function settings_init() {

		if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
			add_filter( 'mce_buttons', array('flexmlsConnect', 'filter_mce_button' ) );
			add_filter( 'mce_external_plugins', array('flexmlsConnect', 'filter_mce_plugin' ) );
		}

		// register our settings with WordPress so it can automatically handle saving them
		register_setting('fmc_settings_group', 'fmc_settings', array('flexmlsConnect', 'settings_validate') );

		// add a section called fmc_settings_api to the settings page
		add_settings_section('fmc_settings_api', '<br/>API Settings', array('flexmlsConnect', 'settings_overview_api') , 'flexmls_connect');

		// add some setting fields to the fmc_settings_api section of the settings page
		add_settings_field('fmc_api_key', 'API Key', array('flexmlsConnect', 'settings_field_api_key') , 'flexmls_connect', 'fmc_settings_api');
		add_settings_field('fmc_api_secret', 'API Secret', array('flexmlsConnect', 'settings_field_api_secret') , 'flexmls_connect', 'fmc_settings_api');
		add_settings_field('fmc_plugin_cache', 'Enable API Cache', array('flexmlsConnect', 'settings_field_plugin_cache') , 'flexmls_connect', 'fmc_settings_api');
		add_settings_field('fmc_clear_cache', 'Clear Cache?', array('flexmlsConnect', 'settings_field_clear_cache') , 'flexmls_connect', 'fmc_settings_api');

		add_settings_section('fmc_settings_plugin', '<br/>Plugin Behavior', array('flexmlsConnect', 'settings_overview_plugin') , 'flexmls_connect');
		add_settings_field('fmc_default_titles', 'Use Default Widget Titles', array('flexmlsConnect', 'settings_field_default_titles') , 'flexmls_connect', 'fmc_settings_plugin');
		add_settings_field('fmc_destlink', 'Open IDX Links', array('flexmlsConnect', 'settings_field_destlink') , 'flexmls_connect', 'fmc_settings_plugin');
		add_settings_field('fmc_default_link', 'Default IDX Link', array('flexmlsConnect', 'settings_field_default_link') , 'flexmls_connect', 'fmc_settings_plugin');

	}


	function settings_page() {

		// put the settings page together

		$options = get_option('fmc_settings');

		echo "<div class='wrap'>\n";
		screen_icon('options-general');
		echo "<h2>flexmls&reg; IDX Settings</h2>\n";

		echo "<form action='options.php' method='post'>\n";

		settings_fields('fmc_settings_group');
		do_settings_sections('flexmls_connect');

		echo "<p class='submit'><input type='submit' name='Submit' type='submit' class='button-primary' value='" .__('Save Settings'). "' />\n";
		
		echo "</form>\n";
		echo "</div>\n";

	}


	function settings_validate($input) {
		$options = get_option('fmc_settings');

		foreach ($input as $key => $value) {
			$input[$key] = trim($value);
		}

		if ($options['api_key'] != $input['api_key'] || $options['api_secret'] != $input['api_secret']) {
			$input['clear_cache'] = "y";
		}

		$options['api_key'] = trim($input['api_key']);
		$options['api_secret'] = trim($input['api_secret']);

		// save the state of the Enable API Cache selection
		if ($input['enable_cache'] == "y") {
			$options['enable_cache'] = true;
		}
		else {
			$options['enable_cache'] = false;
		}

		if ($input['clear_cache'] == "y") {
			// since clear_cache is checked, wipe out the contents of the fmc_cache_* transient items
			// but don't do anything else since we aren't saving the state of this particular checkbox

			// get list of transient items to clear
			$cache_tracker = get_transient('fmc_cache_tracker');
			if (is_array($cache_tracker)) {
				foreach ($cache_tracker as $key => $value) {
					delete_transient('fmc_cache_'. $key);
				}
			}
			delete_transient('fmc_cache_tracker');
		}

		if ($input['default_titles'] == "y") {
			$options['default_titles'] = true;
		}
		else {
			$options['default_titles'] = false;
		}

		$options['destpref'] = $input['destpref'];
		$options['destlink'] = $input['destlink'];
		$options['destwindow'] = $input['destwindow'];
		$options['default_link'] = $input['default_link'];

		return $options;

	}


	function settings_overview_api() {
		if (flexmlsConnect::has_api_saved() == false) {
			echo "<p>Please call FBS Broker Agent Services at 800-437-4232, ext. 108, or email <a href='mailto:idx@flexmls.com'>idx@flexmls.com</a> to purchase a key to activate your plugin.</p>";
		}
	}

	function settings_overview_plugin() {
		//echo "<p>Tweak how the flexmls&reg; Connect plugin behaves.</p>";
	}

	function settings_overview_helpful() {
		echo "<p>Here is some information you may find helpful.</p>";
	}

	function settings_field_api_key() {
		global $fmc_plugin_url;
		$options = get_option('fmc_settings');

		$api_status_info = "";

		if (flexmlsConnect::has_api_saved()) {
			$fmc_api = new flexmlsApiWP;
			$api_auth = $fmc_api->Authenticate(true);

			if ($api_auth === false) {
				$api_status_info = " <img src='{$fmc_plugin_url}/images/error.png'> Error with entered info";
			}
			else {
				$api_status_info = " <img src='{$fmc_plugin_url}/images/accept.png'> It works!";
			}
		}
		
		echo "<input type='text' id='fmc_api_key' name='fmc_settings[api_key]' value='{$options['api_key']}' size='16' maxlength='32' />{$api_status_info}\n";
	}

	function settings_field_api_secret() {
		$options = get_option('fmc_settings');
		echo "<input type='password' id='fmc_api_secret' name='fmc_settings[api_secret]' value='{$options['api_secret']}' size='14' maxlength='25' />\n";
	}

	function settings_field_plugin_cache() {
		$options = get_option('fmc_settings');

		$checked = "";

		if ($options['enable_cache'] == true) {
			$checked_yes = " checked='checked'";
		}
		else {
			$checked_no = " checked='checked'";
		}
		
		echo "<label><input type='radio' name='fmc_settings[enable_cache]' value='y'{$checked_yes} /> Yes</label> &nbsp; ";
		echo "<label><input type='radio' name='fmc_settings[enable_cache]' value='n'{$checked_no} /> No</label><br />\n";
		echo "<span class='description'>Speed up your site by caching certain flexmls&reg; API calls every few minutes.</span>\n";
	}

	function settings_field_default_titles() {
		$options = get_option('fmc_settings');

		$checked = "";

		if ($options['default_titles'] == true) {
			$checked_yes = " checked='checked'";
		}
		else {
			$checked_no = " checked='checked'";
		}

		echo "<label><input type='radio' name='fmc_settings[default_titles]' value='y'{$checked_yes} /> Yes</label> &nbsp; ";
		echo "<label><input type='radio' name='fmc_settings[default_titles]' value='n'{$checked_no} /> No</label><br />\n";
		echo "<span class='description'>Use the default widget titles when no title is entered.</span>\n";
	}

	function settings_field_clear_cache() {
		// stale option that doesn't pay attention to any saved option.  this simply triggers the cache clearing
		echo "<label><input type='checkbox' name='fmc_settings[clear_cache]' value='y' /> Clear the cached flexmls&reg; API responses</label>\n";
	}


	function settings_field_destlink() {
		$options = get_option('fmc_settings');

		$args = array(
				'name' => 'fmc_settings[destlink]',
				'selected' => $options['destlink']
		);
		
		$checked_code = " checked='checked'";

		if ($options['destpref'] == "own") {
			$checked_own = $checked_code;
		}
		elseif ($options['destpref'] == "page") {
			$checked_page = $checked_code;
		}
		else {
			$checked_own = $checked_code;
		}

		if ($options['destwindow'] == "new") {
			$checked_new = $checked_code;
		}

		echo "<label><input type='checkbox' name='fmc_settings[destwindow]' value='new'{$checked_new} /> in a new window</label>";
		echo "<br />\n";
		echo "<br />\n";
		echo "<label><input type='radio' name='fmc_settings[destpref]' value='own'{$checked_own} /> separate from WordPress</label><br />\n";
		echo "<label><input type='radio' name='fmc_settings[destpref]' value='page'{$checked_page} /> framed within a WordPress page (select below)</label><br />\n";
		echo "&nbsp; &nbsp; &nbsp; Page: ";
		wp_dropdown_pages($args);
		echo "<br/>";
		echo "&nbsp; &nbsp; &nbsp; <span class='description'><a href='#' id='idx_frame_shortcode_docs_link'>View the documentation</a> for more details on how this works.</span> ";

		echo "<div id='idx_frame_shortcode_docs' style='display: none; margin-left: 23px; width: 700px;'>";
		echo "<p>In order for this feature to work, the page you point your links to must have the following shortcode in the body of the page:</p>";
		echo "<blockquote><pre>[idx_frame width='100%' height='600']</pre></blockquote>";
		echo "<p>By using this shortcode, it allows the flexmls&reg; IDX plugin to catch links and show the appropriate pages to your users.  If the page with this shortcode is viewed and no link is provided, the 'Default IDX Link' (below) will be displayed.</p>";
		echo "<p><b>Note:</b> When you activated this plugin, a page with this shortcode in the body <a href='".get_permalink($options['autocreatedpage'])."'>was created automatically</a>.  If you choose to deactivate this plugin, this page will automatically be deleted.</p>";
		echo "<p><b>Another Note:</b> If you're using a SEO plugin, you may need to disable Permalink Cleaning for this feature to work.</p>";
		echo "</div>";

	}


	function settings_field_default_link() {
		$options = get_option('fmc_settings');

		$selected_default_link = $options['default_link'];

		if (flexmlsConnect::has_api_saved()) {

			$fmc_api = new flexmlsApiWP;
			$api_links = $fmc_api->GetIDXLinks();

			if ($api_links === false) {
				if ($fmc_api->last_error_code == 1500) {
					echo "This functionality requires a subscription to flexmls&reg; IDX in order to work.  <a href=''>Buy Now</a>.<input type='hidden' name='fmc_settings[default_link]' value='{$selected_default_link}' />";
				}
				else {
					echo "Information not currently available due to API issue.<input type='hidden' name='fmc_settings[default_link]' value='{$selected_default_link}' />";
				}
				return;
			}

			echo "<select name='fmc_settings[default_link]'>\n";
			foreach ($api_links as $link) {
				$selected = ($link['LinkId'] == $selected_default_link) ? " selected='selected'" : "";
				echo "<option value='{$link['LinkId']}'{$selected}>{$link['Name']}</option>\n";
			}
			echo "</select>\n";
			echo "<br/>\n";
			echo "<span class='description'>Select the default flexmls&reg; IDX link your widgets should use</span>";

		}
		else {
			echo "<span class='description'>You must enter API key information to select this option.</span><input type='hidden' name='fmc_settings[default_link]' value='{$selected_default_link}' />";
		}

	}


	function settings_helpful_proptypes() {
		
		$fmc_api = new flexmlsApiWP;
		$api_prop_types = $fmc_api->PropertyTypes();
		$api_system_info = $fmc_api->SystemInfo();
		
		if ($api_prop_types === false || $api_system_info === false) {
			echo "Information not currently available due to API issue.";
			return;
		}

		echo "<span class='description'>Below are the names and codes for each property type {$api_system_info['Mls']} supports:</span><br />\n";
		echo "<table border='0' width='400'>\n";
		echo "	<tr><td><b>Code</b></td><td><b>Property Type</b></td></tr>\n";
		foreach ($api_prop_types as $k => $v) {
			echo "	<tr><td>{$k}</td><td>{$v}</td></tr>\n";
		}
		echo "</table>\n";
	}

	function settings_helpful_idxlinks() {

		$fmc_api = new flexmlsApiWP;
		$api_links = $fmc_api->GetIDXLinks();

		if ($api_links === false) {
			if ($fmc_api->last_error_code == 1500) {
				echo "This functionality requires a subscription to flexmls&reg; IDX in order to work.  <a href=''>Buy Now</a>.";
			}
			else {
				echo "Information not currently available due to API issue.";
			}
			return;
		}

		echo "<span class='description'>Below are the names and codes for saved IDX link you have:</span><br />\n";
		echo "<table border='0' width='400'>\n";
		echo "	<tr><td><b>Code</b></td><td><b>Name</b></td></tr>\n";
		foreach ($api_links as $link) {
			echo "	<tr><td>{$link['LinkId']}</td><td>{$link['Name']}</td></tr>\n";
		}
		echo "</table>\n";

	}



	function clean_spaces_and_trim($value) {
		$value = trim($value);
		// keep looking for sequences of multiple spaces until they no longer exist
		while (preg_match('/\s\s+/', "{$value}")) {
			$value = preg_replace('/\s\s+/', ' ', "{$value}");
		}
		return trim($value);
	}


	function strip_quotes($value) {
		if (get_magic_quotes_gpc()) {
			$value = stripslashes($value);
		}

		if (preg_match('/^\'(.*?)\'$/', $value)) {
			return substr($value, 1, -1);
		}
		else {
			return $value;
		}
	}


	function widget_not_available(&$api, $detailed = false, $args = false, $settings = false) {
		$return = "";

		if (is_array($args)) {
			$return .= $args['before_widget'];
			$return .= $args['before_title'];
			$return .= $settings['title'];
			$return .= $args['after_title'];
		}

		if ($api->last_error_code == 1500) {
			$message = "This widget requires a subscription to flexmls&reg; IDX in order to work.  <a href=''>Buy Now</a>.";
		}
		elseif ($detailed == true) {
			$message = "There was an issue communicating with the flexmls&reg; IDX API services required to generate this widget.  Please refresh the page or try again later.  Error code: ".$api->last_error_code;
		}
		else {
			$message = "This widget is temporarily unavailable.  Please refresh the page or try again later.  Error code: ".$api->last_error_code;
		}

		$return .= $message;

		if (is_array($args)) {
			$return .= $args['after_widget'];
		}
		
		return $return;
	}
	

	function widget_missing_requirements($widget, $reqs_missing) {

		if (is_user_logged_in()) {
			return "<span style='color:red;'>flexmls&reg; Connect: {$reqs_missing} are required settings for the {$widget} widget.</span>";
		}
		else {
			return false;
		}

	}


	function shortcode($attr = array()) {

		if (!is_array($attr)) {
			$attr = array();
		}

		if (!array_key_exists('width', $attr)) {
			$attr['width'] = 600;
		}
		if (!array_key_exists('height', $attr)) {
			$attr['height'] = 500;
		}

		$show_link = $attr['default'];

		if (array_key_exists('url', $_GET) && !empty($_GET['url'])) {
			$show_link = $_GET['url'];
		}
		else {
			$default_link = flexmlsConnect::get_default_idx_link_url();
			if (!empty($default_link)) {
				$show_link = $default_link;
			}
		}

		if (empty($show_link)) {
			return;
		}
		
		if (!empty($_GET['url'])) {
			$show_link = $_GET['url'];
		}

		return "<iframe src='{$show_link}' width='{$attr['width']}' height='{$attr['height']}' frameborder='0'></iframe>";
	}


	function is_mobile() {

		$mobile_enabled = false;



		// WPTouch: http://wordpress.org/extend/plugins/wptouch/
		global $wptouch_plugin;

		if (is_object($wptouch_plugin)) {
			if ($wptouch_plugin->applemobile == true) {
				$mobile_enabled = true;
			}
		}

		// @todo add more later as deemed necessary

		return $mobile_enabled;

	}


	function has_api_saved() {
		$options = get_option('fmc_settings');

		if ( empty($options['api_key']) || empty($options['api_secret']) ) {
			return false;
		}
		else {
			return true;
		}
		
	}

	function use_default_titles() {
		$options = get_option('fmc_settings');

		if ($options['default_titles'] == true) {
			return true;
		}
		else {
			return false;
		}
	}


	function get_destination_link() {
		$options = get_option('fmc_settings');
		$permalink = get_permalink($options['destlink']);
		return $permalink;
	}


	function make_destination_link($link) {

		$options = get_option('fmc_settings');

		if (flexmlsConnect::get_destination_pref() == "own") {
			return $link;
		}

		if (!empty($options['destlink'])) {

			$permalink = get_permalink($options['destlink']);

			if (empty($permalink)) {
				return $link;
			}

			$return = "";

			$link = urlencode($link);

			if (strpos($permalink, '?') !== false) {
				$return = $permalink . '&url=' . $link;
			}
			else {
				$return = $permalink . '?url=' . $link;
			}

			return $return;

		}
		else {
			return $link;
		}

	}


	function get_destination_window_pref() {
		$options = get_option('fmc_settings');
		return $options['destwindow'];
	}

	function get_destination_pref() {
		$options = get_option('fmc_settings');
		return $options['destpref'];
	}

	function get_default_idx_link() {
		$options = get_option('fmc_settings');
		
		if (array_key_exists('default_link', $options) && !empty($options['default_link'])) {
			return $options['default_link'];
		}
		else {
			$fmc_api = new flexmlsApiWP;
			$api_links = $fmc_api->GetIDXLinks();
			return $api_links[0]['LinkId'];
		}
		
	}

	function get_default_idx_link_url() {
		$default_link = flexmlsConnect::get_default_idx_link();
		$fmc_api = new flexmlsApiWP;
		$api_links = $fmc_api->GetIDXLinks();

		$valid_links = array();
		foreach ($api_links as $link) {
			$valid_links[$link['LinkId']] = array('Uri' => $link['Uri'], 'Name' => $link['Name']);
		}

		if (array_key_exists($default_link, $valid_links) && array_key_exists('Uri', $valid_links[$default_link])) {
			return $valid_links[$default_link]['Uri'];
		}
		else {
			return "";
		}

	}

	function remove_starting_equals($val) {
		if (preg_match('/^\=/', $val)) {
			$val = substr($val, 1);
		}
		return $val;
	}

	function is_ie() {
    if (
				isset($_SERVER['HTTP_USER_AGENT']) &&
				(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) &&
				(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') === false)
				) {
			return true;
		}
    else {
			return false;
		}

	}

}
