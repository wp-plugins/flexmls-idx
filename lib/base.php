<?php


class flexmlsConnect {


	function __construct() {

	}


	function initial_init() {
		global $fmc_plugin_url;
		global $fmc_api;

		// turn on PHP sessions handling if they aren't on already
		if (!session_id()) {
			session_start();
		}


		if (!is_admin()) {

			// check if the user appears to be logged in.  if so, switch to OAuth mode
			if ( array_key_exists('fmc_oauth_logged_in', $_SESSION) and $_SESSION['fmc_oauth_logged_in'] === true ) {
				$temp_api = flexmlsConnect::new_oauth_client();
				$temp_api->SetAccessToken($_SESSION['fmc_oauth_access_token']);
				$temp_api->SetRefreshToken($_SESSION['fmc_oauth_refresh_token']);

				$ping = $temp_api->Ping();
				if ($ping) {
					// ping to the live API succeded so change API mode to OAuth for the rest of this process
					$fmc_api = $temp_api;
				}
				else {
					// ping failed so leave API in APIAuth mode and set the session variable to save a ping later
					$_SESSION['fmc_oauth_logged_in'] = false;
				}
			}

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

		$options = get_option('fmc_settings');
		add_rewrite_rule( $options['permabase'] .'/([^/]+)?' , 'index.php?plugin=flexmls-idx&fmc_tag=$matches[1]&page_id='. $options['destlink'] , 'top' );

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

	function wp_init() {
		// handle form submission actions from the plugin
		if ( array_key_exists('fmc_do', $_POST) ) {
			switch($_POST['fmc_do']) {
				case "fmc_search":
					$handle = new fmcSearch();
					$handle->submit_search();
					break;
			}
		}
	}


	function query_vars_init($qvars) {
		$qvars[] = 'fmc_tag';
		return $qvars;
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
                    'listpref' => 'page',
					'destlink' => $new_page_id,
					'autocreatedpage' => $new_page_id,
					'contact_notifications' => true,
					'permabase' => 'idx'
					);

			add_option('fmc_settings', $options);

			add_option('fmc_cache_version', 1);

		}
		else {
			// plugin is only be re-activated with previous settings.

			if ( !array_key_exists('permabase', $options) ) {
				$options['permabase'] = 'idx';
				update_option('fmc_settings', $options);
			}

		}

	}


	function admin_menus_init() {

		// check if the user is able to manage options.  if not, boot them out
		if ( !function_exists('current_user_can') || !current_user_can('manage_options') ) {
			return;
		}

		if ( function_exists('add_options_page') ) {
			add_options_page('flexmls&reg; IDX Settings', 'flexmls&reg; IDX', 'manage_options', 'flexmls_connect', array('flexmlsConnectSettings', 'settings_page') );
		}

		add_submenu_page('edit.php?post_type=page','Add New Neighborhood Page', 'Add Neighborhood', 'manage_options', 'flexmls_connect', array('flexmlsConnect', 'neighborhood_page') );

	}

	function neighborhood_page() {
		global $fmc_api;

		echo "<div class='wrap'>\n";
		screen_icon('page');
		echo "<h2>flexmls&reg; IDX: Add New Neighborhood Page</h2>\n";

		if (array_key_exists('action', $_REQUEST) and $_REQUEST['action'] == "save") {

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

			$api_system_info = $fmc_api->GetSystemInfo();
			$api_location_search_api = flexmlsConnect::get_locationsearch_url();

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
		echo flexmlsJSON::json_encode($response);
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


	function make_destination_link($link, $as = 'url', $params = array()) {

		$extra_query_string = null;
		if ( count($params) > 0 ) {
			$extra_query_string = http_build_query($params);
		}

		$options = get_option('fmc_settings');

		if (flexmlsConnect::get_destination_pref() == "own") {
			if (empty($extra_query_string)) {
				return $link;
			}
			else {
				return $link . '?' . $extra_query_string;
			}
		}

		if (!empty($options['destlink'])) {

			$permalink = get_permalink($options['destlink']);

			if (empty($permalink)) {
				return $link;
			}

			$return = "";

			$link = urlencode($link);

			if (strpos($permalink, '?') !== false) {
				$return = $permalink . '&' . $as . '=' . $link;
			}
			else {
				$return = $permalink . '?' . $as . '=' . $link;
			}

			if (empty($extra_query_string)) {
				return $return;
			}
			else {
				return $return . '&' . $extra_query_string;
			}

		}
		else {
			if (empty($extra_query_string)) {
				return $link;
			}
			else {
				return $link . '?' . $extra_query_string;
			}
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

    function get_no_listings_page_number(){
        $options = get_option('fmc_settings');
        return ($options['listlink']);
    }

    function get_no_listings_pref(){
        $options = get_option('fmc_settings');
        return $options['listpref'];
    }


	function get_default_idx_link() {
		global $fmc_api;

		$options = get_option('fmc_settings');

		if (array_key_exists('default_link', $options) && !empty($options['default_link'])) {
			return $options['default_link'];
		}
		else {
			$api_links = flexmlsConnect::get_all_idx_links();
			return $api_links[0]['LinkId'];
		}

	}

	function get_idx_link_details($my_link) {
		global $fmc_api;

		$api_links = flexmlsConnect::get_all_idx_links();

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
		global $fmc_api;

		$default_link = flexmlsConnect::get_default_idx_link();
		$api_links = flexmlsConnect::get_all_idx_links();

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
		preg_match('/MSIE ([0-9]\.[0-9])/', $_SERVER['HTTP_USER_AGENT'], $reg);
		if(!isset($reg[1])) {
			return -1;
		} else {
			return floatval($reg[1]);
		}
	}


	function clear_temp_cache() {
		$count = get_option('fmc_cache_version');
		if (empty($count)) {
			$count = 0;
		}
		$count++;
		update_option('fmc_cache_version', $count);
		flexmlsConnect::garbage_collect_bad_caches();
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


	static function wp_input_get($key) {

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
			if ( array_key_exists($key, $manual) ) {
				return $manual[$key];
			}
			else {
				return null;
			}
		}
	}


	static function wp_input_post($key) {
		if (isset($_POST) && is_array($_POST) && array_key_exists($key, $_POST)) {
			return self::wp_input_clean($_POST[$key]);
		}
		else {
			return null;
		}
	}


	static function wp_input_get_post($key) {
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


	static function wp_input_clean($string) {

		$string = stripslashes($string);
		return $string;

	}


	static function send_notification() {

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



	static function format_listing_street_address($data) {

		$listing = $data['StandardFields'];

		$one_line_address = "{$listing['StreetNumber']} {$listing['StreetDirPrefix']} {$listing['StreetName']} ";
		$one_line_address .= "{$listing['StreetSuffix']} {$listing['StreetDirSuffix']} {$listing['StreetAdditionalInfo']}";
		$one_line_address = str_replace("********", "", $one_line_address);
		$one_line_address = flexmlsConnect::clean_spaces_and_trim($one_line_address);

		$first_line_address = $one_line_address;

		$second_line_address = "";

		if ( flexmlsConnect::is_not_blank_or_restricted($listing['City']) ) {
			$second_line_address .= "{$listing['City']}, ";
		}

		if ( flexmlsConnect::is_not_blank_or_restricted($listing['StateOrProvince']) ) {
			$second_line_address .= "{$listing['StateOrProvince']} ";
		}

		if ( flexmlsConnect::is_not_blank_or_restricted($listing['StateOrProvince']) ) {
			$second_line_address .= "{$listing['PostalCode']}";
		}

		$second_line_address = str_replace("********", "", $second_line_address);
		$second_line_address = flexmlsConnect::clean_spaces_and_trim($second_line_address);

		$one_line_address .= ", {$second_line_address}";
		$one_line_address = flexmlsConnect::clean_spaces_and_trim($one_line_address);

		return array($first_line_address, $second_line_address, $one_line_address);

	}

	static function is_not_blank_or_restricted($val) {
		$val = trim($val);
		return ( empty($val) or $val == "********") ? false : true;
	}

	static function generate_nice_urls() {
		global $wp_rewrite;
		return $wp_rewrite->using_mod_rewrite_permalinks();
	}

	static function make_nice_tag_url($tag, $params = array()) {

		$query_string = null;
		if ( count($params) > 0 ) {
			$query_string .= '?'. http_build_query($params);
		}

		if (flexmlsConnect::generate_nice_urls()) {
			$options = get_option('fmc_settings');
			return get_home_url() . '/' . $options['permabase'] . '/' . $tag . $query_string;
		}
		else {
			return flexmlsConnect::make_destination_link($tag, 'fmc_tag', $params);
		}
	}

	static function make_nice_address_url($data, $params = array()) {
		$address = flexmlsConnect::format_listing_street_address($data);

		$return = $address[0] .'-'. $address[1] .'-mls_'. $data['StandardFields']['ListingId'];
		$return = preg_replace('/[^\w]/', '-', $return);

		while (preg_match('/\-\-/', $return)) {
			$return = preg_replace('/\-\-/', '-', $return);
		}

		$return = preg_replace('/^\-/', '', $return);
		$return = preg_replace('/\-$/', '', $return);

		if (flexmlsConnect::generate_nice_urls()) {
			$options = get_option('fmc_settings');

			if (count($params) > 0) {
				$return .= '?'. http_build_query($params);
			}

			return get_home_url() . '/' . $options['permabase'] . '/' . $return;
		}
		else {
			return flexmlsConnect::make_destination_link($return, 'fmc_tag', $params);
		}
	}

	static function make_nice_address_title($data) {
		$address = flexmlsConnect::format_listing_street_address($data);

		$return = $address[0] .', '. $address[1] .' (MLS# '. $data['StandardFields']['ListingId'] .')';
		$return = flexmlsConnect::clean_spaces_and_trim($return);

		return $return;
	}

	static function format_date ($format,$date){
		//Format Last Modified Date
		//search for "php date" for format specs
                $LastModifiedDate= "";
                if (flexmlsConnect::is_not_blank_or_restricted($date)){
                        $Seconds = strtotime($date);
                        $LastModifiedDate=date($format,$Seconds);
                }
		return $LastModifiedDate;
	}
	
	static function generate_api_query($conditions) {

	}

	static function make_api_formatted_value($value, $type) {

		$formatted_value = null;

		if ($type == 'Character') {
			$formatted_value = (string) "'". addslashes( trim( trim($value) ,"'") ) ."'";
		}
		elseif ($type == 'Integer') {
			$formatted_value = (int) $value;
		}
		elseif ($type == 'Decimal') {
			$formatted_value = number_format($value, 2, '.', '');
		}
		elseif ($type == 'Date') {
			$formatted_value = trim($value); // no single quotes
		}
		else { }

		return $formatted_value;

	}

	static function make_api_displayable_value($value, $type) {

		$formatted_value = null;

		if ($type == 'Character') {
			$formatted_value = (string) $value;
		}
		elseif ($type == 'Integer') {
			$formatted_value = (int) number_format($value, 0, '', ',');
		}
		elseif ($type == 'Decimal') {
			$formatted_value = number_format($value, 2, '.', ',');
		}
		elseif ($type == 'Date') {
			$date_parts = explode("-", $value);
			$formatted_value = $date_parts[1].'/'.$date_parts[2].'/'.$date_parts[0];
		}
		else { }

		return $formatted_value;
	}

	static function get_big_idx_disclosure_text() {
		global $fmc_api;

		$api_system_info = $fmc_api->GetSystemInfo();
		return trim( $api_system_info['Configuration'][0]['IdxDisclaimer'] );
	}

	static function mls_custom_idx_logo() {
		global $fmc_api;

		$api_system_info = $fmc_api->GetSystemInfo();

		if (array_key_exists('IdxLogoSmall', $api_system_info['Configuration'][0]) && !empty($api_system_info['Configuration'][0]['IdxLogoSmall'])) {
			return $api_system_info['Configuration'][0]['IdxLogoSmall'];
		}
		else {
			return false;
		}
	}

	static function add_contact($content){
        global $fmc_api;
		 return ($fmc_api->AddContact($content, flexmlsConnect::send_notification()));
	}

	static function message_me($subject, $body, $from_email){
		global $fmc_api;
		$my_account = $fmc_api->GetMyAccount();
		$sender = $fmc_api->GetContacts(null, array("_select" => "Id", "_filter" => "PrimaryEmail Eq '{$from_email}'"));
		return $fmc_api->AddMessage(array(
		'Type'       => 'General',
		'Subject'    => $subject,
		'Body'       => $body,
		'Recipients' => array($my_account['Id']),
		'SenderId'   => $sender[0]['Id']
		));
	}

	static function mls_requires_office_name_in_search_results() {
	

		global $fmc_api;
                $api_system_info = $fmc_api->GetSystemInfo();
                $mlsId = $api_system_info["MlsId"];
                $compList = ($api_system_info["DisplayCompliance"][$mlsId]["View"]["Summary"]["DisplayCompliance"]);

		return (in_array("ListOfficeName",$compList));
	}

	static function mls_requires_agent_name_in_search_results() {

                global $fmc_api;
                $api_system_info = $fmc_api->GetSystemInfo();
                $mlsId = $api_system_info["MlsId"];
                $compList = ($api_system_info["DisplayCompliance"][$mlsId]["View"]["Summary"]["DisplayCompliance"]);

                return (in_array("ListAgentName",$compList));

	}
	
	static function mls_required_fields_and_values($type, &$record){
		//$type		String 	 "Summary" | "Detail"
		//$record	GetListings(params)[0]

		global $fmc_api;
        	$api_system_info = $fmc_api->GetSystemInfo();
        	$mlsId = $api_system_info["MlsId"];
		$compList = ($api_system_info["DisplayCompliance"][$mlsId]["View"][$type]['DisplayCompliance']);
		$sf = $record["StandardFields"];


		//Get Adresses
        	//Since these fields take a considerable amount of time to get, check if they are required from the compliance list beforehand.
        	if (in_array('ListOfficeAddress',$compList)){
            		$OfficeInfo = $fmc_api->GetAccountsByOffice($sf["ListOfficeId"]);
            		$OfficeAddress = ($OfficeInfo[0]["Addresses"][0]["Address"]);
       		}

        	if (in_array('ListMemberAddress',$compList)){
		    $AgentInfo  = $fmc_api->GetAccount($sf["ListAgentId"]);
		    $AgentAddress = ($AgentInfo[0]["Addresses"][0]["Address"]);
        	}

        	if (in_array('CoListAgentAddress',$compList)){
		    	$CoAgentInfo	= $fmc_api->GetAccount($sf["CoListAgentId"]);
            		$CoAgentAddress	= ($CoAgentInfo[0]["Addresses"][0]["Address"]);
        	}

		//Names
		$AgentName = "";
		$CoAgentName = "";
                if ((flexmlsConnect::is_not_blank_or_restricted($sf["ListAgentFirstName"])) && (flexmlsConnect::is_not_blank_or_restricted($sf["ListAgentLastName"])))
                        $AgentName = "{$sf["ListAgentFirstName"]} {$sf["ListAgentLastName"]}";

		if ((flexmlsConnect::is_not_blank_or_restricted($sf["CoListAgentFirstName"])) && (flexmlsConnect::is_not_blank_or_restricted($sf["CoListAgentLastName"])))
                        $CoAgentName = "{$sf["CoListAgentFirstName"]} {$sf["CoListAgentLastName"]}";


		//Primary Phone Numbers and Extensions
		$ListOfficePhone = "";
		$ListAgentPhone = "";
		$CoListAgentPhone = "";
		if (flexmlsConnect::is_not_blank_or_restricted($sf["ListOfficePhone"]))
			$ListOfficePhone = $sf["ListOfficePhone"];
			if (flexmlsConnect::is_not_blank_or_restricted($sf["ListOfficePhoneExt"]))
                        	$ListOfficePhone .= " ext. " . $sf["ListOfficePhoneExt"];

		if (flexmlsConnect::is_not_blank_or_restricted($sf["ListAgentPreferredPhone"]))
                        $ListAgentPhone = $sf["ListAgentPreferredPhone"];
                        if (flexmlsConnect::is_not_blank_or_restricted($sf["ListAgentPreferredPhone"]))
                                $ListAgentPhone .= " ext. " . $sf["ListAgentPreferredPhone"];

                if (flexmlsConnect::is_not_blank_or_restricted($sf["CoListAgentPreferredPhone"]))
                        $CoListAgentPhone = $sf["CoListAgentPreferredPhone"];
                        if (flexmlsConnect::is_not_blank_or_restricted($sf["CoListAgentPreferredPhone"]))
                                $CoListAgentPhone .= " ext. " . $sf["CoListAgentPreferredPhone"];


		//format last modified date
		$LastModifiedDate = flexmlsConnect::format_date("F - d - Y", $sf["ModificationTimestamp"]);

		//These will be printed in this order.
		$possibleRequired = array(
			"ListOfficeName" 	=> array("Listing Office",$sf["ListOfficeName"]),
			"ListOfficePhone" 	=> array("Office Phone",$ListOfficePhone),
			"ListOfficeEmail" 	=> array("Office Email",$sf["ListOfficeEmail"]),
			"ListOfficeURL" 	=> array("Office Website",$sf["ListOfficeURL"]),
			"ListOfficeAddress" 	=> array("Office Address",$OfficeAddress),
			"ListAgentName" 	=> array("Listing Agent",$AgentName),//Agent name is done below to make sure first and last name are present
			"ListMemberPhone" 	=> array("Agent Phone",$sf["ListAgentPreferredPhone"] ),
			"ListMemberEmail" 	=> array("Agent Email",$sf["ListAgentEmail"]),
			"ListMemberURL" 	=> array("Agent Website",$sf["ListAgentURL"]),
			"ListMemberAddress" 	=> array("Agent Address",$AgentAddress),
			"CoListOfficeName" 	=> array("Co Office Name",$sf["CoListOfficeName"]),
			"CoListOfficePhone"	=> array("Co Office Phone",$sf["CoListOfficePhone"]),
			"CoListOfficeEmail"	=> array("Co Office Email",$sf["CoListOfficeEmail"]),
			"CoListOfficeURL"	=> array("Co Office Website",$sf["CoListOfficeURL"]),
			"CoListOfficeAddress"	=> array("Co Office Address","$CoAgentAddress"),
			"CoListAgentName"	=> array("Co Listing Agent",$CoAgentName),
			"CoListAgentPhone"	=> array("Co Agent Phone",$CoListAgentPhone),
			"CoListAgentEmail"	=> array("Co Agent Email",$sf["CoListAgentEmail"]),
			"CoListAgentURL"	=> array("Co Agent Webpage",$sf["CoListAgentURL"]),
			"CoListAgentAddress"	=> array("Co Agent Address",$CoAgentAddress),
			"ListingUpdateTimestamp"=> array("Listing Was Last Updated",$LastModifiedDate),
			"IDXLogo"               => array("LOGO",""),//Todo -- Print Logo?
		);

        	$values= array();

        	/*foreach ($compList as $test){
            	array_push($values,array($possibleRequired[$test][0],$possibleRequired[$test][1]));
       	 	} */
        
        	foreach ($possibleRequired as $key => $value){
			if (in_array($key, $compList))
				array_push($values,array($value[0],$value[1]));
        	}
		return $values;
	}

	static function fetch_ma_tech_id() {
		global $fmc_api;

		$api_system_info = $fmc_api->GetSystemInfo();

		if ( array_key_exists('MlsId', $api_system_info) ) {
			return $api_system_info['MlsId'];
		}
		else {
			// MLS level key with no MlsId defined.  return Id
			return $api_system_info['Id'];
		}

	}

	static function is_odd($val) {
		return ($val % 2) ? true : false;
	}

	static function get_locationsearch_url() {
		global $fmc_location_search_url;
		return $fmc_location_search_url;
	}


	/*
	 * Take a value and clean it so it can be used as a parameter value in what's sent to the API.
	 *
	 * @param string $var Regular string of text to be cleaned
	 * @return string Cleaned string
	 */
	static function clean_comma_list($var) {

		$return = "";

		if ( strpos($var, ',') !== false ) {
			// $var contains a comma so break it apart into a list...
			$list = explode(",", $var);
			// trim the extra spaces and weird characters from the beginning and end of each item in the list...
			$list = array_map('trim', $list);
			// and put it back together as a comma-separated string to be returned
			$return = implode(",", $list);
		}
		else {
			// trim the extra spaces and weird characters from the beginning and end of the string to be returned
			$return = trim($var);
		}

		return $return;

	}

	static function page_slug_tag() {
		global $wp_query;
		return $wp_query->get('fmc_tag');
	}

	static function oauth_login_link() {
		global $fmc_api;
		$api_system_info = $fmc_api->GetSystemInfo();
		$base_url = $api_system_info['Configuration'][0]['OAuth2ServiceEndpointPortal'];

		$options = get_option('fmc_settings');

		$options['oauth_client_id'] = '33v3wnk3mi8sw2k8g8h9petwo';
		$options['oauth_client_secret'] = 'jyxib0a75tbikly4vbtk1uxs';

		$params = array(
		    'client_id' => $options['oauth_client_id'],
		    'redirect_uri' => flexmlsConnect::oauth_login_landing_page(),
		    'response_type' => 'code'
		);

		return $base_url .'?'. http_build_query($params);
	}

	static function oauth_login_landing_page() {
		return flexmlsConnect::make_nice_tag_url('oauth-login');
	}

	static function new_apiauth_client() {
		global $fmc_version;

		$options = get_option('fmc_settings');

		$fmc_api = new flexmlsAPI_APIAuth($options['api_key'], $options['api_secret']);
		// enable API caching via WordPress transient cache system
		$fmc_api->SetCache( new flexmlsAPI_WordPressCache );
		// set application name
		$fmc_api->SetApplicationName("flexmls WordPress Plugin/{$fmc_version}");
		$fmc_api->SetNewAccessCallback( array('flexmlsConnect', 'new_access_keys') );

		$fmc_api->SetCachePrefix('fmc_'. get_option('fmc_cache_version') .'_');

		return $fmc_api;
	}

	static function new_oauth_client() {
		global $fmc_version;

		$options = get_option('fmc_settings');

		$options['oauth_client_id'] = '33v3wnk3mi8sw2k8g8h9petwo';
		$options['oauth_client_secret'] = 'jyxib0a75tbikly4vbtk1uxs';

		$fmc_api = new flexmlsAPI_OAuth($options['oauth_key'], $options['oauth_secret'], flexmlsConnect::oauth_login_landing_page());
		// enable API caching via WordPress transient cache system
		$fmc_api->SetCache( new flexmlsAPI_WordPressCache );
		// set application name
		$fmc_api->SetApplicationName("flexmls WordPress Plugin/{$fmc_version}");
		$fmc_api->SetNewAccessCallback( array('flexmlsConnect', 'new_access_keys') );

		$fmc_api->SetCachePrefix("1");

		return $fmc_api;
	}

	static function new_access_keys($type, $values) {
		if ($type == "oauth") {
			// save to session since OAuth tokens are user-specific
			$_SESSION['fmc_oauth_access_token'] = $values['access_token'];
			$_SESSION['fmc_oauth_refresh_token'] = $values['refresh_token'];
		}
		elseif ($type == "api") {
			// managed via cache automatically since API auth is only site-specific
		}
	}

	static function is_logged_in() {
		return (array_key_exists('fmc_oauth_logged_in', $_SESSION) and $_SESSION['fmc_oauth_logged_in']) ? true : false;
	}

	static function is_oauth() {
		global $fmc_api;
		return ($fmc_api->auth_mode == "oauth") ? true : false;
	}

	static function expand_link_id($string) {

		$maximum = flexmlsConnect::bc_base_convert($string, 36, 10);

		$prefix = '20';
		if (substr($maximum, 0, 1) == '9' and strlen($maximum) == 18) {
			$prefix = '19';
		}

		while ( strlen($prefix . $maximum) < 20 ) {
			$prefix .= '0';
		}

		$short = $prefix . $maximum . '000000';

		return (string) $short;

	}

	static function get_all_idx_links($only_saved_search = false) {
		global $fmc_api;

		$return = array();

		$current_page = 0;
		$total_pages = 1;

		while ($current_page < $total_pages) {

			$current_page++;

			$params = array(
			    '_pagination' => 1,
			    '_page' => $current_page
			);

			$result = $fmc_api->GetIDXLinks($params);

			if ( is_array($result) ) {
				foreach ($result as $r) {
					if ($only_saved_search and !array_key_exists('SearchId', $r) ) {
						// we're only wanting saved search links and this isn't one
						continue;
					}
					$return[] = $r;
				}
			}

			if ( $fmc_api->total_pages == null ) {
				break;
			}
			else {
				$current_page = $fmc_api->current_page;
				$total_pages = $fmc_api->total_pages;
			}
		}

		return $return;
	}

	static function possible_destinations() {
		return array('local' => 'my search results', 'remote' => 'a flexmls IDX frame');
	}

	static function is_agent() {
		$type = get_option('fmc_my_type');
		return ($type == 'Member') ? true : false;
	}

	static function is_office() {
		$type = get_option('fmc_my_type');
		return ($type == 'Office') ? true : false;
	}

	static function is_company() {
		$type = get_option('fmc_my_type');
		return ($type == 'Company') ? true : false;
	}

	static function get_office_id() {
		return get_option('fmc_my_office');
	}

	static function get_company_id() {
		return get_option('fmc_my_company');
	}

	static function possible_fonts() {
		return array(
		    'Arial' => 'Arial',
		    'Lucida Sans Unicode' => 'Lucida Sans Unicode',
		    'Tahoma' => 'Tahoma',
		    'Verdana' => 'Verdana'
		    );
	}

	static function hexLighter($hex, $factor = 20) {
    $new_hex = '';
    $base['R'] = hexdec($hex{0}.$hex{1});
    $base['G'] = hexdec($hex{2}.$hex{3});
    $base['B'] = hexdec($hex{4}.$hex{5});

    foreach ($base as $k => $v) {
      $amount = 255 - $v;
      $amount = $amount / 100;
      $amount = round($amount * $factor);
      $new_decimal = $v + $amount;

      $new_hex_component = dechex($new_decimal);
      if (strlen($new_hex_component) < 2) {
        $new_hex_component = "0".$new_hex_component;
      }
      $new_hex .= $new_hex_component;
    }

    return $new_hex;
	}

	function hexDarker($color, $dif=20){
    $color = str_replace('#', '', $color);
    if (strlen($color) != 6){ return '000000'; }
    $rgb = '';

    for ($x=0;$x<3;$x++){
        $c = hexdec(substr($color,(2*$x),2)) - $dif;
        $c = ($c < 0) ? 0 : dechex($c);
        $rgb .= (strlen($c) < 2) ? '0'.$c : $c;
    }

    return $rgb;
  }

    public static function allowMultipleLists() {
        $options = get_option('fmc_settings');

        if (array_key_exists('multiple_summaries', $options)) {
            if ($options['multiple_summaries']) {
                return true;
            }
        }
        return false;
    }


	static function nice_property_type_label($abbrev) {
		global $fmc_api;
		$options = get_option('fmc_settings');

		if ( array_key_exists("property_type_label_{$abbrev}", $options) and !empty($options["property_type_label_{$abbrev}"]) ) {
			return $options["property_type_label_{$abbrev}"];
		}
		else {
			$api_property_types = $fmc_api->GetPropertyTypes();
			return $api_property_types[$abbrev];
		}
	}

	static public function gentle_price_rounding($val) {
		// check if the value has decimal places and if those aren't just zeros

    if ( !flexmlsConnect::is_not_blank_or_restricted($val) )
      return "";

		if ( strpos($val, '.') !== false ) {
			// has a decimal
			$places = explode(".", $val);
			if ($places[1] != "00") {
				return number_format($val, 2);
			}
		}

		return number_format($val, 0);
	}

	/*
	 * idea credit to: https://github.com/Seebz/Snippets/blob/master/Wordpress/plugins/purge-transients/purge-transients.php
	 */
	static public function garbage_collect_bad_caches() {
		global $wpdb;

		$transients = $wpdb->get_col(
			$wpdb->prepare("
			SELECT REPLACE(option_name, '_transient_timeout_', '') AS transient_name FROM {$wpdb->options} WHERE
			option_name LIKE '\_transient\_timeout\_%%' AND option_value < %s
			", time())
		);

		foreach ($transients as $transient) {
			get_transient($transient);
		}

		return true;
	}



	// source: http://www.technischedaten.de/pmwiki2/pmwiki.php?n=Php.BaseConvert
	function bc_base_convert($value, $quellformat, $zielformat) {
		$vorrat = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		if (max($quellformat, $zielformat) > strlen($vorrat))
			trigger_error('Bad Format max: ' . strlen($vorrat), E_USER_ERROR);
		if (min($quellformat, $zielformat) < 2)
			trigger_error('Bad Format min: 2', E_USER_ERROR);
		$dezi = '0';
		$level = 0;
		$result = '';
		$value = trim((string) $value, "\r\n\t +");
		$vorzeichen = '-' === $value{0} ? '-' : '';
		$value = ltrim($value, "-0");
		$len = strlen($value);
		for ($i = 0; $i < $len; $i++) {
			$wert = strpos($vorrat, $value{$len - 1 - $i});
			if (FALSE === $wert)
				trigger_error('Bad Char in input 1', E_USER_ERROR);
			if ($wert >= $quellformat)
				trigger_error('Bad Char in input 2', E_USER_ERROR);
			$dezi = bcadd($dezi, bcmul(bcpow($quellformat, $i), $wert));
		}
		if (10 == $zielformat)
			return $vorzeichen . $dezi; // abk�rzung
		while (1 !== bccomp(bcpow($zielformat, $level++), $dezi));
		for ($i = $level - 2; $i >= 0; $i--) {
			$factor = bcpow($zielformat, $i);
			$zahl = bcdiv($dezi, $factor, 0);
			$dezi = bcmod($dezi, $factor);
			$result .= $vorrat{$zahl};
		}
		$result = empty($result) ? '0' : $result;
		return $vorzeichen . $result;
	}


}

