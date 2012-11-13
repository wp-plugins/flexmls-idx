<?php

class flexmlsConnectSettings {
	
	
	function settings_init() {
		global $wp_rewrite;

		if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
			add_filter( 'mce_buttons', array('flexmlsConnect', 'filter_mce_button' ) );
			add_filter( 'mce_external_plugins', array('flexmlsConnect', 'filter_mce_plugin' ) );
		}

		// register our settings with WordPress so it can automatically handle saving them
		register_setting('fmc_settings_group', 'fmc_settings', array('flexmlsConnectSettings', 'settings_validate') );

		$current_tab = flexmlsConnect::wp_input_get('tab');
		if ( empty($current_tab) ) {
			$current_tab = 'settings';
		}
		
		if ($current_tab == 'settings') {
		
			// add a section called fmc_settings_api to the settings page
			add_settings_section('fmc_settings_api', '<br/>API Credentials', array('flexmlsConnectSettings', 'settings_overview_api') , 'flexmls_connect');

			// add some setting fields to the fmc_settings_api section of the settings page
			add_settings_field('fmc_api_key', 'API Key', array('flexmlsConnectSettings', 'settings_field_api_key') , 'flexmls_connect', 'fmc_settings_api');
			add_settings_field('fmc_api_secret', 'API Secret', array('flexmlsConnectSettings', 'settings_field_api_secret') , 'flexmls_connect', 'fmc_settings_api');
			add_settings_field('fmc_clear_cache', 'Clear Cache?', array('flexmlsConnectSettings', 'settings_field_clear_cache') , 'flexmls_connect', 'fmc_settings_api');

// i4 TODO
//			// oauth settings
//			add_settings_section('fmc_settings_oauth', '<br/>OAuth Credentials', array('flexmlsConnectSettings', 'settings_overview_oauth') , 'flexmls_connect');
//			add_settings_field('fmc_oauth_key', 'OAuth Client ID/Key', array('flexmlsConnectSettings', 'settings_field_oauth_key') , 'flexmls_connect', 'fmc_settings_oauth');
//			add_settings_field('fmc_oauth_secret', 'OAuth Client Secret', array('flexmlsConnectSettings', 'settings_field_oauth_secret') , 'flexmls_connect', 'fmc_settings_oauth');
//			add_settings_field('fmc_oauth_redirect', 'OAuth Redirect URI', array('flexmlsConnectSettings', 'settings_field_oauth_redirect') , 'flexmls_connect', 'fmc_settings_oauth');
		
		}
		elseif ($current_tab == 'behavior') {
			
			add_settings_section('fmc_settings_plugin', '<br/>General', array('flexmlsConnectSettings', 'settings_overview_plugin') , 'flexmls_connect');
			add_settings_field('fmc_default_titles', 'Use Default Widget Titles', array('flexmlsConnectSettings', 'settings_field_default_titles') , 'flexmls_connect', 'fmc_settings_plugin');
			add_settings_field('fmc_neigh_template', 'Neighborhood Page Template', array('flexmlsConnectSettings', 'settings_field_neigh_template') , 'flexmls_connect', 'fmc_settings_plugin');
			add_settings_field('fmc_contact_notifications', 'When a new lead is created', array('flexmlsConnectSettings', 'settings_field_contact_notifications') , 'flexmls_connect', 'fmc_settings_plugin');
			add_settings_field('fmc_multiple_summaries', 'Multiple summary lists', array('flexmlsConnectSettings', 'settings_field_multiple_summaries') , 'flexmls_connect', 'fmc_settings_plugin');

			add_settings_section('fmc_settings_linking', '<br/>Linking', array('flexmlsConnectSettings', 'settings_overview_linking') , 'flexmls_connect');
			add_settings_field('fmc_default_link', 'Default IDX Link', array('flexmlsConnectSettings', 'settings_field_default_link') , 'flexmls_connect', 'fmc_settings_linking');
			add_settings_field('fmc_destlink', 'Open IDX Links', array('flexmlsConnectSettings', 'settings_field_destlink') , 'flexmls_connect', 'fmc_settings_linking');
			add_settings_field('fmc_permabase', 'Permalink Slug', array('flexmlsConnectSettings', 'settings_field_permabase') , 'flexmls_connect', 'fmc_settings_linking');
			
			add_settings_section('fmc_settings_labels', '<br/>Labels', array('flexmlsConnectSettings', 'settings_overview_labels') , 'flexmls_connect');
			add_settings_field('fmc_property_type_labels', 'Property Types', array('flexmlsConnectSettings', 'settings_field_property_type_labels') , 'flexmls_connect', 'fmc_settings_labels');
			
			add_settings_section('fmc_settings_compliance', '<br/>IDX Compliance', array('flexmlsConnectSettings', 'settings_overview_compliance') , 'flexmls_connect');
			add_settings_field('fmc_disclosure', 'Disclosure', array('flexmlsConnectSettings', 'settings_field_disclosure') , 'flexmls_connect', 'fmc_settings_compliance');
			
			// force refresh of WordPress mod_rewrite rules in case the page was just saved with a new permabase
			$wp_rewrite->flush_rules(true);
			
		}
		elseif ($current_tab == 'about') {
			
			$do_show = flexmlsConnect::wp_input_get('show');
			if ($do_show != "yes") {
				// make the extra hop so we can see if WP's transient cache is working
				set_transient('fmc_quick_cache_test', 'works', 60*60*24);
				wp_redirect( $_SERVER['REQUEST_URI']. '&show=yes' );
				exit;
			}
			
			add_settings_section('fmc_settings_about_general', '', array('flexmlsConnectSettings', 'settings_overview_about') , 'flexmls_connect');

		}
			
	}
	
	function settings_page() {

		// put the settings page together

		$options = get_option('fmc_settings');

		echo "<div class='wrap'>\n";
		screen_icon('options-general');
		echo "<h2>flexmls&reg; IDX Settings</h2>\n";
		
		if ( flexmlsConnect::wp_input_get('settings-updated') == "true" ) {
			echo '<div id="message" class="updated"><p>Settings updated.</p></div>';
		}
		
		$current_tab = flexmlsConnect::wp_input_get('tab');
		if ( empty($current_tab) ) {
			$current_tab = 'settings';
		}
		flexmlsConnectSettings::settings_page_tabs($current_tab);

		echo "<form action='options.php' method='post'>\n";

//		echo "<p>&nbsp;</p><table class='widefat'>\n";
//		echo "<thead><tr><th>API Credentials</th></tr></thead>\n";
//		echo "<tbody><tr><td>";
		settings_fields('fmc_settings_group');
		do_settings_sections('flexmls_connect');
		
		if ($current_tab != "about") {
			echo "<p class='submit'><input type='submit' name='Submit' type='submit' class='button-primary' value='" .__('Save Settings'). "' />\n";
		}
		
//		echo "</td></tr></table>\n";
		echo "</form>\n";
		echo "</div>\n";

	}
	
	function settings_page_tabs($current) {
	
		$tabs = array(
		    'settings' => 'API Settings',
		    'behavior' => 'Behavior',
		    'about' => 'About'
		);
		
		echo "<h2 class='nav-tab-wrapper'>\n";
		foreach ($tabs as $t => $v) {
			$class = ($t == $current) ? ' nav-tab-active' : '';
			echo "	<a href='?page=flexmls_connect&amp;tab={$t}' class='nav-tab{$class}'>{$v}</a>\n";
		}	
		echo "</h2>\n";
		
	}
	
	function settings_validate($input) {
		global $wp_rewrite;

		$options = get_option('fmc_settings');

		foreach ($input as $key => $value) {
			$input[$key] = trim($value);
		}

		
		if ($input['tab'] == "settings") {
		
			if ($options['api_key'] != $input['api_key'] || $options['api_secret'] != $input['api_secret']) {
				$input['clear_cache'] = "y";
			}
		
			$options['api_key'] = trim($input['api_key']);
			$options['api_secret'] = trim($input['api_secret']);
			
			$options['oauth_key'] = trim($input['oauth_key']);
			$options['oauth_secret'] = trim($input['oauth_secret']);
		
			if ( array_key_exists('clear_cache', $input) and $input['clear_cache'] == "y") {
				// since clear_cache is checked, wipe out the contents of the fmc_cache_* transient items
				// but don't do anything else since we aren't saving the state of this particular checkbox

				flexmlsConnect::clear_temp_cache();
			}
			
		}
		elseif ($input['tab'] == "behavior") {
			
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
			$options['permabase'] = (!empty($input['permabase'])) ? $input['permabase'] : 'idx';

			if ($input['contact_notifications'] == "y") {
				$options['contact_notifications'] = true;
			}
			else {
				$options['contact_notifications'] = false;
			}

            if ($input['multiple_summaries'] == "y") {
                $options['multiple_summaries'] = true;
            }
            else {
                $options['multiple_summaries'] = false;
            }

			$property_types = explode(",", $input['property_types']);
			foreach ($property_types as $pt) {
				$options['property_type_label_'.$pt] = $input['property_type_label_'.$pt];
			}

			$options['listing_office_disclosure'] = $input['listing_office_disclosure'];
			$options['listing_agent_disclosure'] = $input['listing_agent_disclosure'];

		}

		return $options;

	}

	function settings_overview_api() {
		if (flexmlsConnect::has_api_saved() == false) {
			echo "<p>Please call FBS Broker Agent Services at 800-437-4232, ext. 108, or email <a href='mailto:idx@flexmls.com'>idx@flexmls.com</a> to purchase a key to activate your plugin.</p>";
		}
		echo "<input type='hidden' name='fmc_settings[tab]' value='settings' />\n";
	}

	function settings_overview_linking() {
		echo "";
	}

	function settings_overview_labels() {
		echo "";
	}

	function settings_overview_compliance() {
		echo "";
	}
	
	function settings_overview_oauth() {
		echo "<p>In order for your clients to log into your site using their flexmls Portal account, the below details must be filled in.</p>";
	}

	function settings_overview_plugin() {
		//echo "<p>Tweak how the flexmls&reg; Connect plugin behaves.</p>";
		echo "<input type='hidden' name='fmc_settings[tab]' value='behavior' />\n";
	}

	function settings_overview_helpful() {
		echo "<p>Here is some information you may find helpful.</p>";
	}
	
	function settings_field_api_key() {
		global $fmc_api;
		global $fmc_plugin_url;

		$options = get_option('fmc_settings');

		$api_status_info = "";

		if (flexmlsConnect::has_api_saved()) {
			$api_auth = $fmc_api->Authenticate(true);

			if ($api_auth === false) {
				$api_status_info = " <img src='{$fmc_plugin_url}/images/error.png'> Error with entered info";
			}
			else {
				$api_status_info = " <img src='{$fmc_plugin_url}/images/accept.png'> It works!";
				
				$api_my_account = $fmc_api->GetMyAccount();
				
				update_option('fmc_my_type', $api_my_account['UserType']);
				
				update_option('fmc_my_id', $api_my_account['Id']);
				
				$my_office_id = "";
				if ( array_key_exists('OfficeId', $api_my_account) and !empty($api_my_account['OfficeId']) ) {
					$my_office_id = $api_my_account['OfficeId'];
				}
				update_option('fmc_my_office', $my_office_id);
				
				$my_company_id = "";
				if ( array_key_exists('CompanyId', $api_my_account) and !empty($api_my_account['CompanyId']) ) {
					$my_company_id = $api_my_account['CompanyId'];
				}
				update_option('fmc_my_company', $my_company_id);
								
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
		global $fmc_api;
		$options = get_option('fmc_settings');

		$selected_default_link = $options['default_link'];

		if (flexmlsConnect::has_api_saved()) {

			$api_links = flexmlsConnect::get_all_idx_links();

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

    function settings_field_multiple_summaries() {
   		$options = get_option('fmc_settings');

   		$checked_code = " checked='checked'";

   		if (array_key_exists('multiple_summaries', $options) and $options['multiple_summaries'] === true) {
   			$checked_yes = $checked_code;
   		}
   		else {
   			$checked_no = $checked_code;
   		}


   		echo "<label><input type='checkbox' name='fmc_settings[multiple_summaries]' value='y'{$checked_yes} /> Allow multiple lists per page</label> &nbsp; ";

   	}


	function settings_helpful_proptypes() {
		global $fmc_api;
		
		$api_prop_types = $fmc_api->GetPropertyTypes();
		$api_system_info = $fmc_api->GetSystemInfo();
		
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
		global $fmc_api;

		$api_links = flexmlsConnect::get_all_idx_links();

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
	
	function settings_field_oauth_key() {
		global $fmc_api;
		global $fmc_plugin_url;

		$options = get_option('fmc_settings');
		
		echo "<input type='text' id='fmc_api_key' name='fmc_settings[oauth_key]' value='{$options['oauth_key']}' size='30' maxlength='40' />\n";
	}

	function settings_field_oauth_secret() {
		$options = get_option('fmc_settings');
		echo "<input type='password' id='fmc_api_secret' name='fmc_settings[oauth_secret]' value='{$options['oauth_secret']}' size='24' maxlength='40' />\n";
	}
	
	function settings_field_oauth_redirect() {
		echo "<input type='text' value='". flexmlsConnect::oauth_login_landing_page() ."' size='75' readonly='readonly' name='redirect_uri' onClick=\"javascript:this.form.redirect_uri.focus();this.form.redirect_uri.select();\">\n";
	}
	
	function settings_field_permabase() {
		$options = get_option('fmc_settings');
		echo "<input type='text' name='fmc_settings[permabase]' value='{$options['permabase']}' size='15' maxlength='50' />\n";
		echo "<br/><span class='description'>Changes the URL for special plugin pages.  ";
		echo "i.e. ". get_home_url() . '/<b><u>' . $options['permabase'] . '</u></b>/' . "search </span>";
	}
	
	function settings_field_property_type_labels() {
		global $fmc_api;
		
		$options = get_option('fmc_settings');
		
		$api_property_types = $fmc_api->GetPropertyTypes();
		
		echo "<span class='description'>Customize how property types names are displayed</span><br/>\n";
		
		echo "<table cellpadding='2' cellspacing='1'>\n";
		echo "<tr><td><b>MLS</b></td><td><b>Your Site</b></td></tr>\n";
		foreach ($api_property_types as $pk => $pv) {
			$show_value = $pv;
			
			if ( array_key_exists("property_type_label_{$pk}", $options) and !empty($options["property_type_label_{$pk}"]) ) {
				$show_value = $options["property_type_label_{$pk}"];
			}
			
			echo "<tr><td>{$pv}</td><td><input type='text' name='fmc_settings[property_type_label_{$pk}]' value=\"".htmlspecialchars($show_value)."\" /></td></tr>\n";
		}
		echo "</table>\n";
		echo "<input type='hidden' name='fmc_settings[property_types]' value='". implode(",", array_keys($api_property_types) ) ."' />\n";
		
		
	}
	
	function settings_field_disclosure() {
		$options = get_option('fmc_settings');
		
		$checked = ($options['listing_office_disclosure'] == 'y') ? " checked='checked'" : null;
		echo "<label><input type='checkbox' name='fmc_settings[listing_office_disclosure]'{$checked} value='y' /> Force Listing Office to display</label>\n";
		
		$checked = ($options['listing_agent_disclosure'] == 'y') ? " checked='checked'" : null;
		echo "<br><label><input type='checkbox' name='fmc_settings[listing_agent_disclosure]'{$checked} value='y' /> Force Listing Agent to display</label>\n";
	}
	
	
	function settings_overview_about() {
		global $fmc_api;
		global $wp_version;
		global $fmc_version;
		
		$known_plugin_conflicts = array(
		    'screencastcom-video-embedder/screencast.php', // Screencast Video Embedder, JS syntax errors in 0.4.4 breaks all pages
		);
		
		/*
		 * Support Information
		 */
		
		echo "<br/>\n";
		echo "<h3>Support Information</h3>";
	
		echo "<blockquote>";
		
		echo "<b>flexmls Broker/Agent Services</b><br/>\n";
		echo "<b>Phone:</b> 800-437-4232, ext. 108<br/>\n";
		echo "<b>Email:</b> <a href='mailto:idx@flexmls.com'>idx@flexmls.com</a><br/>\n";
		echo "<b>Website:</b> <a href='http://www.flexmls.com/wpdemo/' target='_blank'>http://www.flexmls.com/wpdemo/</a><br/>\n";
				
		echo "</blockquote>";
		
		
		/*
		 * Plugin Information
		 */
		
		echo "<br/>\n";
		echo "<h3>Plugin Information</h3>";
		$options = get_option('fmc_settings');
		
		$api_system_info = $fmc_api->GetSystemInfo();
		
		$licensed_to = "Unlicensed";
		$member_of = "";
		$agent_of = "";
		
		if ($api_system_info) {
			$licensed_to = $api_system_info['Name'];
			$member_of = $api_system_info['Mls'];
			if ( flexmlsConnect::is_not_blank_or_restricted($api_system_info['Office']) ) {
				$agent_of = " &nbsp;of&nbsp; {$api_system_info['Office']}";
			}
		}
		
		echo "<blockquote>";
		
		echo "<b>Licensed to:</b> {$licensed_to}{$agent_of}<br/>\n";
		if (!empty($member_of)) {
			echo "<b>Member of:</b> {$member_of}<br/>\n";
		}
		echo "<b>API Key:</b> {$options['api_key']}<br/>\n";
		if (!empty($options['oauth_key'])) {
			echo "<b>OAuth Client ID:</b> {$options['oauth_key']}<br/>\n";
		}
// i4 TODO
//		echo "<b>OAuth Landing Page:</b> ". flexmlsConnect::oauth_login_landing_page() ."<br/>\n";
		
		
		echo "</blockquote>";
		
		
		
		/*
		 * Installation Details
		 */
		
		echo "<br/>\n";
		echo "<h3>Installation Details</h3>\n";

		
		$child_theme_data = get_theme_data(get_stylesheet_directory_uri() .'/style.css');
		$parent_theme_data = get_theme_data(get_template_directory_uri() .'/style.css');
		
		$cache_test = get_transient('fmc_quick_cache_test');
		
		$options = get_option('fmc_settings');
		
		$curl_details = curl_version();
		
		$plugins = get_plugins();
		
		echo "<blockquote>";
		
		echo "<b>Site URL:</b> ". get_home_url() ."<br/>\n";
		echo "<b>Web Server:</b> {$_SERVER['SERVER_SOFTWARE']}<br/>\n";
		echo "<b>PHP Version:</b> ". phpversion() ."<br/>\n";
		echo "<b>WP Version:</b> {$wp_version}<br/>\n";
		echo "<b>WP Theme:</b> <a href='{$child_theme_data['URI']}' target='_blank'>{$child_theme_data['Name']}</a> (version {$child_theme_data['Version']}) by {$child_theme_data['Author']}<br/>\n";
		if ($child_theme_data['Name'] != $parent_theme_data['Name']) {
			echo "<b>WP Parent Theme:</b> <a href='{$parent_theme_data['URI']}' target='_blank'>{$parent_theme_data['Name']}</a> (version {$parent_theme_data['Version']}) by {$parent_theme_data['Author']}<br/>\n";
		}
		
		$plugin_list = array();
		
		foreach ($plugins as $plugin_file => $plugin_data) {
			$which_plugin_list = ( is_plugin_active($plugin_file) ) ? "active" : "inactive";
				
			$conflict_tag = "";
			if ( in_array($plugin_file, $known_plugin_conflicts) ) {
				$conflict_tag = " &nbsp; <span style='color:red'>Known issues</span>";
			}

			$plugin_list[$which_plugin_list] .= " &nbsp; &nbsp; &middot; <a href='{$plugin_data['PluginURI']}' target='_blank'>{$plugin_data['Name']}</a> (version {$plugin_data['Version']}) by <a href='{$plugin_data['AuthorURI']}' target='_blank'>{$plugin_data['AuthorName']}</a>{$conflict_tag}<br/>\n";

		}

		if ( array_key_exists('active', $plugin_list) and !empty($plugin_list['active']) ) {
			echo "<b>WP Active Plugins:</b><br/>\n";
			echo $plugin_list['active'];
		}
		
		if ( array_key_exists('inactive', $plugin_list) and !empty($plugin_list['inactive']) ) {
			echo "<b>WP Inactive Plugins:</b><br/>\n";
			echo $plugin_list['inactive'];
		}
		
		
		echo "<b>flexmls WP Plugin Version:</b> {$fmc_version}<br/>\n";
		echo "<b>flexmls API Client Version:</b> {$fmc_api->api_client_version}<br/>\n";
		echo "<b>cURL Version:</b> {$curl_details['version']}<br/>\n";
		echo "<b>API Base:</b> {$fmc_api->api_base}<br>\n";
		echo "<b>Location Search Base:</b> ". flexmlsConnect::get_locationsearch_url() ."<br/>\n";
		echo "<b>Cache Version:</b> ". get_option('fmc_cache_version') ."<br/>\n";
		echo "<b>Caching Test:</b> ";
		echo ($cache_test == "works") ? "<span style='color:green'>working</span>" : "<span style='color:red'>not detected</span>";
		echo "<br/>\n";
		
		echo "<b>Plugin Permalink Slug:</b> {$options['permabase']}<br/>\n";
		echo "<b>Permalinks:</b> ";
		echo (flexmlsConnect::generate_nice_urls()) ? "<span style='color:green'>on</span>" : "<span style='color:red'>off</span>";
		echo "<br/>\n";
		
		echo "<b>PHP Magic Quotes:</b> ";
		echo (get_magic_quotes_gpc() == 1) ? "on" : "off";
		echo "<br/>\n";
		
		echo "<b>PHP Register Globals:</b> ";
		echo (ini_get('register_globals') == 1) ? "<span style='color:red'>on</span>" : "<span style='color:green'>off</span>";
		echo "<br/>\n";
		
		
			
		echo "</blockquote>";
		
	}
	
	
	
}
