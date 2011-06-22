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

			if (flexmlsConnect::is_ie() && flexmlsConnect::ie_version() < 9) {
				wp_register_script('fmc_excanvas', $fmc_plugin_url .'/includes/excanvas.min.js');
				wp_enqueue_script('fmc_excanvas');
			}
			
		}
		else {

			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-sortable');

			wp_register_script('fmc_connect', $fmc_plugin_url .'/includes/connect_admin.min.js', array('jquery','jquery-ui-core'));
			wp_enqueue_script('fmc_connect');

			wp_register_style('fmc_connect', $fmc_plugin_url .'/includes/connect_admin.min.css');
			wp_enqueue_style('fmc_connect');

			wp_register_style('fmc_jquery_ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/themes/ui-lightness/jquery-ui.css');
			wp_enqueue_style('fmc_jquery_ui');
			
		}

		wp_localize_script('fmc_connect', 'fmcAjax', array( 'ajaxurl' => admin_url('admin-ajax.php'), 'pluginurl' => $fmc_plugin_url ) );

		add_shortcode("idx_frame", array('flexmlsConnect', 'shortcode'));
		
	}


	function widget_init() {
		// Load all of the widgets we need for the plugin.

		global $fmc_plugin_dir;
		global $fmc_widgets;

		foreach ($fmc_widgets as $class => $wdg) {
			if ( file_exists($fmc_plugin_dir . "/components/{$wdg['component']}") ) {
				require_once($fmc_plugin_dir . "/components/{$wdg['component']}");

				$meets_key_reqs = false;
				if ($wdg['requires_key'] == false || ($wdg['requires_key'] == true && flexmlsConnect::has_api_saved())) {
					$meets_key_reqs = true;
				}

				if ( class_exists($class, false) && $meets_key_reqs && $wdg['widget'] == true) {
					register_widget($class);
				}
				if ($wdg['widget'] == false) {
					new $class();
				}
			}
		}
		
		// register where the AJAX calls should be routed when they come in
		add_action('wp_ajax_fmcShortcodeContainer', array('flexmlsConnect', 'shortcode_container') );

	}
	

	function plugin_deactivate() {
		
		flexmlsConnect::clear_temp_cache();
		delete_transient('fmc_api');
		
	}


	function plugin_activate() {

		flexmlsConnect::clear_temp_cache();
		$options = get_option('fmc_settings');

		if ($options === false) {
			// plugin install is brand new

			$new_page = array(
					'post_title' => "Search",
					'post_content' => "[idx_frame width='100%' height='600']",
					'post_type' => 'page',
					'post_status' => 'publish'
			);

			$new_page_id = wp_insert_post($new_page);

			$options = array(
					'default_titles' => true,
					'destpref' => 'page',
					'destlink' => $new_page_id,
					'autocreatedpage' => $new_page_id,
					'contact_notifications' => true
					);

			add_option('fmc_settings', $options);

		}
		else {
			// plugin is only be re-activated with previous settings.
		}

	}


	function admin_menus_init() {

		// check if the user is able to manage options.  if not, boot them out
		if ( !function_exists('current_user_can') || !current_user_can('manage_options') ) {
			return;
		}

		if ( function_exists('add_options_page') ) {
			add_options_page('flexmls&reg; IDX Settings', 'flexmls&reg; IDX', 'manage_options', 'flexmls_connect', array('flexmlsConnect', 'settings_page') );
		}

		add_submenu_page('edit.php?post_type=page','Add New Neighborhood Page', 'Add Neighborhood', 'manage_options', 'flexmls_connect', array('flexmlsConnect', 'neighborhood_page') );

	}

	function neighborhood_page() {
		echo "<div class='wrap'>\n";
		screen_icon('page');
		echo "<h2>flexmls&reg; IDX: Add New Neighborhood Page</h2>\n";

		if ($_REQUEST['action'] == "save") {

			if (empty($_REQUEST['template'])) {
				$_REQUEST['template'] = "default";
			}

			$loc = flexmlsConnect::parse_location_search_string( flexmlsConnect::unescape_request_var($_REQUEST['location']) );
			$loc_title = $loc[0]['l'];
			$loc_raw = $loc[0]['r'];

			$shortcode = "[neighborhood_page title=\"{$loc_title}\" location=\"{$loc_raw}\" template=\"{$_REQUEST['template']}\"]";

			$new_page = array(
				'post_title' => "{$loc_title}",
				'post_content' => $shortcode,
				'post_type' => 'page',
				'post_status' => 'publish',
				'post_parent' => $_REQUEST['parent']
			);

			$new_page_id = wp_insert_post($new_page);

			$template_page_template = get_post_meta( flexmlsConnect::get_neighborhood_template_id($_REQUEST['template']), '_wp_page_template', true );
			update_post_meta( $new_page_id, '_wp_page_template', $template_page_template);

			echo "<p><b>Congratulations!</b>  You just created a new neighborhood page.  Now what?</p>";

			echo "<p>";
			echo "<a href='edit.php?post_type=page&page=flexmls_connect'>Create Another</a> &nbsp; or &nbsp; ";
			echo "<a href='post.php?post={$new_page_id}&action=edit'>Edit Your New Page</a>";
			echo "</p>";

		}
		else {

			echo "<p>To create a new neighborhood page automatically, select your location and template below.</p>";
			echo "<p>&nbsp;</p>";
			echo "<h3>Neighborhood Page Details</h3>";

			echo "<form action='edit.php?post_type=page&page=flexmls_connect' method='post'>\n";
			echo "<input type='hidden' name='action' value='save' />";

			$fmc_api = new flexmlsApiWP();
			$api_system_info = $fmc_api->SystemInfo();
			$api_location_search_api = $fmc_api->GetLocationSearchApiUrl();

			echo "

				<p>
					<label for='parent'>Parent Page:</label>
				";

				$args = array(
					'name' => 'parent',
					'post_status' => 'publish',
					'echo' => true,
					'show_option_none' => __('(no parent)')
				);

				wp_dropdown_pages($args);


			echo "

				</p>

				<p>
					<label for='template'>Neighborhood Template:</label>
					";


				$args = array(
					'name' => 'template',
					'post_status' => 'draft',
					'echo' => false,
					'show_option_none' => '(Use Saved Default)'
				);

				$page_selection = wp_dropdown_pages($args);
				if (!empty($page_selection)) {
					echo $page_selection;
				}
				else {
					echo "Please create a page as a draft to select it here.";
				}

			echo "
				</p>

				<div class='flexmls_connect__location'>
					<p>
  					<label for='location'>Location:</label>
  					<input type='text' name='location_input' data-connect-url='{$api_location_search_api}' class='flexmls_connect__location_search' autocomplete='off' value='City, Postal Code, etc.' />
  					<a href='javascript:void(0);' title='Click here to browse through available locations' class='flexmls_connect__location_browse'>Browse &raquo;</a>
  					<div class='flexmls_connect__location_list' data-connect-multiple='false'>
  						<p>All Locations Included</p>
  					</div>
  					<input type='hidden' name='tech_id' class='flexmls_connect__tech_id' value=\"x'{$api_system_info['Id']}'\" />
  					<input type='hidden' name='ma_tech_id' class='flexmls_connect__ma_tech_id' value=\"x'{$api_system_info['MlsId']}'\" />
  					<input fmc-field='location' fmc-type='text' type='hidden' name='location' class='flexmls_connect__location_fields' value=\"\" />
  					<select style='display:none;' fmc-field='property_type' class='flexmls_connect__property_type' fmc-type='select' id='property_type' name='property_type'>
    			    <option value='A' selected='selected'></option>
    			  </select>
					</p>
				</div>

				<img src='x' class='flexmls_connect__bootloader' onerror='flexmls_connect.location_setup(this);' />
			";


			echo "<br/><input type='submit' value='Create Page' />\n";

			echo "</form>\n";

		}

		echo "</div>\n";
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
		add_settings_field('fmc_clear_cache', 'Clear Cache?', array('flexmlsConnect', 'settings_field_clear_cache') , 'flexmls_connect', 'fmc_settings_api');

		add_settings_section('fmc_settings_plugin', '<br/>Plugin Behavior', array('flexmlsConnect', 'settings_overview_plugin') , 'flexmls_connect');
		add_settings_field('fmc_default_titles', 'Use Default Widget Titles', array('flexmlsConnect', 'settings_field_default_titles') , 'flexmls_connect', 'fmc_settings_plugin');
		add_settings_field('fmc_destlink', 'Open IDX Links', array('flexmlsConnect', 'settings_field_destlink') , 'flexmls_connect', 'fmc_settings_plugin');
		add_settings_field('fmc_default_link', 'Default IDX Link', array('flexmlsConnect', 'settings_field_default_link') , 'flexmls_connect', 'fmc_settings_plugin');
		add_settings_field('fmc_neigh_template', 'Neighborhood Page Template', array('flexmlsConnect', 'settings_field_neigh_template') , 'flexmls_connect', 'fmc_settings_plugin');
		add_settings_field('fmc_contact_notifications', 'When a new lead is created', array('flexmlsConnect', 'settings_field_contact_notifications') , 'flexmls_connect', 'fmc_settings_plugin');

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

		if ($input['clear_cache'] == "y") {
			// since clear_cache is checked, wipe out the contents of the fmc_cache_* transient items
			// but don't do anything else since we aren't saving the state of this particular checkbox

			flexmlsConnect::clear_temp_cache();
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
		$options['neigh_template'] = $input['neigh_template'];
		
		if ($input['contact_notifications'] == "y") {
			$options['contact_notifications'] = true;
		}
		else {
			$options['contact_notifications'] = false;
		}

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
		echo "<p><b>Note:</b> When you activated this plugin, a page with this shortcode in the body <a href='".get_permalink($options['autocreatedpage'])."'>was created automatically</a>.</p>";
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


	function settings_field_neigh_template() {
		$options = get_option('fmc_settings');

		$selected_neigh_template = $options['neigh_template'];

		$args = array(
				'name' => 'fmc_settings[neigh_template]',
				'selected' => $selected_neigh_template,
				'post_status' => 'draft',
				'echo' => false
		);

		$page_selection = wp_dropdown_pages($args);
		if (!empty($page_selection)) {
			echo $page_selection;
		}
		else {
			echo "Please create a page as a draft to select it here.";
		}
		
		echo "<br/><span class='description'>Select the page to use as your default neighborhood page template.</span>";

	}
	
	
	function settings_field_contact_notifications() {
		$options = get_option('fmc_settings');

		$checked_code = " checked='checked'";

		if (!array_key_exists('contact_notifications', $options)) {
			$checked_yes = $checked_code;
		}
		elseif ($options['contact_notifications'] === true) {
			$checked_yes = $checked_code;
		}
		else {
			$checked_no = $checked_code;
		}

		
		echo "<label><input type='radio' name='fmc_settings[contact_notifications]' value='y'{$checked_yes} /> Notify me within flexmls&reg;</label> &nbsp; ";
		echo "<label><input type='radio' name='fmc_settings[contact_notifications]' value='n'{$checked_no} /> Don't send any notification</label><br />\n";
		
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
		$value = stripslashes($value);

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
			return "<span style='color:red;'>flexmls&reg; IDX: {$reqs_missing} are required settings for the {$widget} widget.</span>";
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

		$show_link = flexmlsConnect::get_default_idx_link_url();

		$query_url = flexmlsConnect::wp_input_get('url');

		if (!empty($query_url)) {
			$show_link = $query_url;
		}
		else {
			$default_link = $attr['default'];
			if (!empty($default_link)) {
				$show_link = $default_link;
			}
		}

		if (empty($show_link)) {
			return;
		}
		
		if (!empty($query_url)) {
			$show_link = $query_url;
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

	function get_idx_link_details($my_link) {
		$fmc_api = new flexmlsApiWP;
		$api_links = $fmc_api->GetIDXLinks();

		if (is_array($api_links)) {
			foreach ($api_links as $link) {
				if ($link['LinkId'] == $my_link) {
					return $link;
				}
			}
		}

		return false;

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

		$this_ua = getenv('HTTP_USER_AGENT');

		if ($this_ua && (strpos($this_ua, 'MSIE') !== false) && (strpos($this_ua, 'Opera') === false) ) {
			return true;
		}
		else {
			return false;
		}

	}
	
	function ie_version() {
		ereg('MSIE ([0-9]\.[0-9])',$_SERVER['HTTP_USER_AGENT'],$reg);
		if(!isset($reg[1])) {
			return -1;
		} else {
			return floatval($reg[1]);
		}
	}
	
	
	function clear_temp_cache() {
		// get list of transient items to clear
		$cache_tracker = get_transient('fmc_cache_tracker');
		if (is_array($cache_tracker)) {
			foreach ($cache_tracker as $key => $value) {
				delete_transient('fmc_cache_'. $key);
			}
		}
		delete_transient('fmc_cache_tracker');
	}

	function greatest_fitting_number($num, $slide, $max) {
		if (($num * ($slide + 1)) <= $max) {
			return flexmlsConnect::greatest_fitting_number($num, $slide + 1, $max);
		}
		else {
			return $num * $slide;
		}
	}

	/**
	 *
	 * Used to calculate the API limit needed to fill as many
	 * slides as possible without having any partially filled.
	 */
	function generate_api_limit_value($hor, $ver) {

		// _limit number to shoot for if we can
		$kind_limit = 10;

		// maximum _limit is allowed to be
		$max_limit = 25;

		if (empty($hor)) {
			$hor = 1;
		}
		if (empty($ver)) {
			$ver = 1;
		}

		$total = $hor * $ver;

		if ($total < $kind_limit) {
			return flexmlsConnect::greatest_fitting_number($total, 1, $kind_limit);
		}
		elseif ($total >= $kind_limit && $total <= $max_limit) {
			return $total;
		}
		else {
			return $max_limit;
		}

	}


	function generate_appropriate_dimensions($hor, $ver) {
		$new_horizontal = $hor;

		if ($new_horizontal > 25) {
			$new_horizontal = 25;
		}

		$initial_total = ($hor * $ver);

		if ($initial_total > 25) {
			$room_to_grow = true;
			$new_vertical = 1;
			while ($room_to_grow) {
				if (($new_horizontal * ($new_vertical + 1)) >= 25) {
					$room_to_grow = false;
				}
				else {
					$new_vertical++;
				}
			}
		}
		else {
			// the grid is fine as-is
			$new_vertical = $ver;
		}

		return array( $new_horizontal, $new_vertical );
	}


    function parse_location_search_string($location) {
        $locations = array();
        if (!empty($location)) {
            if (preg_match('/\|/', $location)) {
                $locations = explode("|", $location);
            }
            else {
                $locations[] = $location;
            }
        }

        $return = array();

        foreach ($locations as $loc) {
            list($loc_name, $loc_value) = explode("=", $loc, 2);
            list($loc_value, $loc_display) = explode("&", $loc_value);
            $loc_value_nice = preg_replace('/^\'(.*)\'$/', "$1", $loc_value);
            // if there weren't any single quotes, just use the original value
			if (empty($loc_value_nice)) {
				$loc_value_nice = $loc_value;
			}
            $loc_value_nice = flexmlsConnect::remove_starting_equals($loc_value_nice);
            $return[] = array(
				'r' => $loc,
                'f' => $loc_name,
                'v' => $loc_value_nice,
                'l' => $loc_display
            );
        }

        return $return;
    }

    function cache_turned_on() {
        return true;
    }


	function get_neighborhood_template_content($page_id = false) {

		$neigh_template_page_id = flexmlsConnect::get_neighborhood_template_id($page_id);

		if (!$neigh_template_page_id) {
			return false;
		}
		
		$content = get_page($neigh_template_page_id);
		return $content->post_content;

	}


	function get_neighborhood_template_id($page_id = false) {

		if (!$page_id || $page_id == "default") {
			$options = get_option('fmc_settings');
			$neigh_template_page_id = $options['neigh_template'];
		}
		else {
			$neigh_template_page_id = $page_id;
		}

		if (empty($neigh_template_page_id)) {
			return false;
		}

		return $neigh_template_page_id;

	}


	function unescape_request_var($string) {	
		return stripslashes($string);
	}


	function special_location_tag_text() {
		return "<br /><span class='description'>You can use <code>{Location}</code> on neighborhood templates to customize.</span>";
	}


	function wp_input_get($key) {
		
		if (isset($_GET) && is_array($_GET) && array_key_exists($key, $_GET)) {
			return self::wp_input_clean($_GET[$key]);
		}
		else {
			// parse the query string manually.  some kind of internal redirect
			// or protection is keeping PHP from knowing what $_GET is
			
			$full_requested_url = (preg_match('/^HTTP\//', $_SERVER['SERVER_PROTOCOL'])) ? "http" : "https";
			$full_requested_url .= "://". $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			
			$query_string = parse_url($full_requested_url, PHP_URL_QUERY);
			$query_parts = explode("&", $query_string);
			$manual = array();
			foreach ($query_parts as $p) {
				list($k, $v) = @explode("=", $p, 2);
				if (array_key_exists($k, $manual)) {
					$manual[$k] .= ",".urldecode($v);
				}
				else {
					$manual[$k] = urldecode($v);
				}
			}
			return $manual[$key];
		}
	}
	
	
	function wp_input_post($key) {
		if (isset($_POST) && is_array($_POST) && array_key_exists($key, $_POST)) {
			return self::wp_input_clean($_POST[$key]);
		}
		else {
			return null;
		}
	}
	
	
	function wp_input_get_post($key) {
		$via_post = self::wp_input_post($key);
		if ($via_post !== null) {
			return $via_post;
		}
		
		$via_get = self::wp_input_get($key);
		if ($via_get !== null) {
			return $via_get;
		}
		
		return null;
	}
	
	
	function wp_input_clean($string) {
		
		$string = stripslashes($string);
		return $string;
		
	}
	
	
	function send_notification() {
	
		$options = get_option('fmc_settings');

		if (!array_key_exists('contact_notifications', $options)) {
			return true;
		}
		elseif ($options['contact_notifications'] === true) {
			return true;
		}
		else {
			return false;
		}
		
	}

}
