<?php

class flexmlsApiWP {

	private $is_ready = false;
	private $api_base = "api.flexmls.com"; // no trailing slash
	private $location_search_url = "http://www.flexmls.com"; // http://www.flexmls.com
	public $last_error_code = null;
	public $last_error_mess = null;
	public $last_count = 0;
	public $last_count_pages = 0;
	public $last_current_page = 0;
	public $api_roles = null;
	public $last_token = null;
	public $last_token_expire = null;
	private $last_response_from = null;
	private $send_debug = false;


	function __construct() {
		global $fmc_plugin_dir;

		$api_ini_file = $fmc_plugin_dir . '/lib/api.ini';

		if (file_exists($api_ini_file)) {
			$local_settings = parse_ini_file($api_ini_file);
			if (array_key_exists('api_base', $local_settings)) {
				$this->api_base = trim($local_settings['api_base']);
			}
			if (array_key_exists('location_search_url', $local_settings)) {
				$this->location_search_url = trim($local_settings['location_search_url']);
			}
		}

		$options = get_option('fmc_settings');

		if ( !empty($options['api_key']) && !empty($options['api_secret']) ) {
			$this->is_ready = true;
		}


	}


	function __destruct() { }


	function HasBasicRole() {
		$api_settings = get_transient('fmc_api');

		if ( is_array($api_settings['Roles']) ) {
			if ( in_array('basic', $api_settings['Roles']) ) {
				return true;
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}

	}

	function GetTransformedIDXLink($link, $args = array()) {

		$result = $this->MakeAPIRequest("GET", "/v1/redirect/idxlink/{$link}", $args, $data = array(), $auth = false, $cache_time = 2880);

		if ($result === false) {
			return false;
		}

		return $result[0]['Uri'];

	}


	function StandardFields() {
		$result = $this->MakeAPIRequest("GET", "/v1/standardfields", array(), array(), $auth = false, $cache_time = 2880);
		return $result;
	}

	function GetLocationSearchApiUrl() {
		return $this->location_search_url;
	}


	function MarketStats($type, $options = "", $property_type = "", $location_name = "", $location_value = "") {

		$args = array();

		if (!empty($options)) {
			$args['Options'] = $options;
		}

		if (!empty($property_type)) {
			$args['PropertyTypeCode'] = $property_type;
		}

		if (!empty($location_name)) {
			$args['LocationField'] = $location_name;
			$args['LocationValue'] = $location_value;
		}

		$result = $this->MakeAPIRequest("GET", "/v1/marketstatistics/{$type}", $args, $data = array(), $auth = false, $cache_time = 2880);

		if ($result === false) {
			return false;
		}

		return $result[0];
		
	}


	function Authenticate($force = false) {
		global $fmc_token;

		$result = $this->MakeAPIRequest("POST", "/v1/session", $args, $data = array(), $auth = true, $cache_time = 0, $force);

		if ($result === false) {
			return false;
		}

		$this->last_token = $result[0]['AuthToken'];
		$this->last_token_expire = $result[0]['Expires'];
		set_transient('fmc_api', $result[0], 60*60*24*7);
		set_transient('fmc_last_authtoken', $this->last_token, 60*60*24);

	}


	function SendContact($contact_data) {
		$args = array();

		$data = array(
		    'Contacts' => array($contact_data),
		    'Notify' => flexmlsConnect::send_notification()
		);
		
		$result = $this->MakeAPIRequest("POST", "/v1/contacts", $args, $data, $auth = false);

		if ($result === false) {
			return false;
		}

		return $result;
	}


	function ConnectPrefs() {

		$args = array();

		$result = $this->MakeAPIRequest("GET", "/v1/connect/prefs", $args, $data = array(), $auth = false, $cache_time = 300);

		if ($result === false) {
			return false;
		}

		$records = array();
		foreach ($result as $pref) {
			$records[$pref['Name']] = $pref['Value'];
		}
		return $records;
		
	}


	function PropertyTypes() {

		$args = array();

		$result = $this->MakeAPIRequest("GET", "/v1/propertytypes", $args, $data = array(), $auth = false, $cache_time = 900);

		if ($result === false) {
			return false;
		}

		$records = array();
		foreach ($result as $res) {
			$records[$res['MlsCode']] = $res['MlsName'];
		}

		return $records;

	}


	function Listings($args = array()) {

		$result = $this->MakeAPIRequest("GET", "/v1/listings", $args, $data = array(), $auth = false, $cache_time = 10);

		if ($result === false) {
			return false;
		}

		return $result;

	}


	function ListingPhotos($id) {

		$args = array();

		$result = $this->MakeAPIRequest("GET", "/v1/listings/".$id."/photos", $args, $data = array(), $auth = false, $cache_time = 10);

		if ($result === false) {
			return false;
		}

		return $result;

	}


	function MyListings($args = array()) {

		$result = $this->MakeAPIRequest("GET", "/v1/my/listings", $args, $data = array(), $auth = false, $cache_time = 10);

		if ($result === false) {
			return false;
		}

		return $result;

	}

	function OfficeListings($args = array()) {

		$result = $this->MakeAPIRequest("GET", "/v1/office/listings", $args, $data = array(), $auth = false, $cache_time = 10);

		if ($result === false) {
			return false;
		}

		return $result;

	}
	
	function CompanyListings($args = array()) {

		$result = $this->MakeAPIRequest("GET", "/v1/company/listings", $args, $data = array(), $auth = false, $cache_time = 10);

		if ($result === false) {
			return false;
		}

		return $result;

	}


	function GetIDXLinks($tags = "", $args = array()) {

		$tags = trim($tags);
		if ( !empty($tags) ) {
			$args['tags'] = $this->clean_comma_list($tags);
		}

		$result = $this->MakeAPIRequest("GET", "/v1/idxlinks", $args, $data = array(), $auth = false, $cache_time = 1200);

		if ($result === false) {
			return false;
		}

		return $result;

	}


	function SystemInfo() {

		$args = array();

		$result = $this->MakeAPIRequest("GET", "/v1/system", $args, $data = array(), $auth = false, $cache_time = 600);

		if ($result === false) {
			return false;
		}

		if (!array_key_exists('MlsId', $result[0])) {
			$result[0]['Mls'] = $result[0]['Name'];
			$result[0]['MlsId'] = $result[0]['Id'];
		}
		
		return $result[0];

	}


	/*
	 * Makes the API call to the flexmls API.
	 * 
	 * @param string $method HTTP method to use when making the call.  GET, POST, etc.
	 * @param string $uri HTTP request URI to hit with the request
	 * @param array $args array of key/value pairs of parameters.  added to request depending on HTTP method
	 * @param array $caching array of caching settings. 'enabled' is true/false. 'minutes' defines how long if enabled
	 * @return mixed Returns array of parsed JSON results if successful.  Returns false if API call fails
	 */
	function MakeAPIRequest($method, $uri, $args = array(), $data = array(), $is_auth_request = false, $cache_time = 0, $a_retry = false) {
		global $fmc_token;
		global $fmc_instance_cache;
		global $fmc_version;

		if (!is_array($args)) {
			$args = array();
		}

		$http_parameters = $args;

		if ($this->is_ready == false) {
			return;
		}
		
		// used to track where we received the data from
		$data_source = null;

		// retrieve all saved items
		$options = get_option('fmc_settings');

		// start with the basic part of the security string and add to it as we go
		$sec_string  = "{$options['api_secret']}ApiKey{$options['api_key']}";

		$post_data = "";

		if ($method == "POST" && count($data) > 0) {
			// the request is to post some JSON data back to the API (like adding a contact)
			$post_body = flexmlsJSON::json_encode( array('D' => $data ) );
		}
		
		if ($is_auth_request) {
			$http_parameters['ApiKey'] = $options['api_key'];
		}
		else {
			$http_parameters['AuthToken'] = $this->last_token;

			// since this isn't an authentication request, add the ServicePath to the security string
			$sec_string .= "ServicePath{$uri}";

			ksort($http_parameters);

			// add each of the HTTP query string parameters to the security string
			foreach ($http_parameters as $k => $v) {
				$sec_string .= $k . $v;
			}
		}

		// add the JSON data to the end of the security string if it exists
		$sec_string .= $post_body;

		// calculate the security string as ApiSig
		$api_sig = md5($sec_string);

		$http_parameters['ApiSig'] = $api_sig;

		// build the HTTP request essentials the way WordPress wants them
		$http_args = array();
		$http_args['method'] = strtoupper($method);
		$http_args['timeout'] = 20;
		$http_args['sslverify'] = false;
		$http_args['headers']['User-Agent'] = "flexmls API WP PHP Client/1.0";
		$http_args['headers']['X-flexmlsApi-User-Agent'] = "flexmls WordPress Plugin/{$fmc_version}";

		if ($is_auth_request == true) {
			$http_proto = "https://";
		}
		else {
			$http_proto = "http://";
		}

		// start putting the URL parts together
		$full_url = $http_proto . $this->api_base . $uri;
		$cache_url = $http_proto . $this->api_base . $uri;

		// take the parameter key/values and put them into a URL-like structure.  key=value&key2=value2& etc.
		$query_string = http_build_query($http_parameters);
		// build a query string that we can cache against, since $http_parameters includes the ApiSig and AuthToken that we don't want
		$cache_string = http_build_query($args);

		if (!empty($query_string)) {
			$full_url .= '?' . $query_string;
			$cache_url .= '?' . $cache_string;
		}

		if ($method == "POST") {
			// put the built parameter key/values as the body of the POST request
			$http_args['body'] = $post_body;
			$http_args['headers']['Content-Type'] = "application/json";
		}

		$cache_item_name = md5($cache_url);

		// innocent until proven guilty
		$served_from_cache = null;

		// check if we should retrieve from cache
		if ($method == "GET" && $cache_time > 0 && $a_retry == false) {
			// retrieve the cache data for this particular request
			$cache = get_transient('fmc_cache_'. $cache_item_name);

			// check if the item's expire time has passed already
			if ($cache !== false) {
				// item exists and it hasn't expired yet, so we'll serve the request from cache
				$served_from_cache = $cache;
			}

		}
		elseif ($method == "GET" && $a_retry == false) {
			if (!empty($fmc_instance_cache[ $cache_item_name ])) {
				$served_from_cache = $fmc_instance_cache[ $cache_item_name ]['data'];
			}
		}

		// since we didn't get any unexpired data from the cache, make the call
		if ($served_from_cache == null) {
			$return = wp_remote_request($full_url, $http_args);
			$data_source = "live";
			$this->last_response_from = "live";
		}
		else {
			// act like the API returned data when it was really the cache
			$return = $served_from_cache;
			$data_source = "cache";
			$this->last_response_from = "cache";
		}

		if ( is_wp_error($return) ) {
			$this->send_debug = true;
			$this->last_error_code = "WP1";
			$this->last_error_mess = "WordPress error";
			return false;
		}

		// start handling the response

		$json = flexmlsJSON::json_decode($return['body']);

		$this->last_error_code = $json['D']['Code'];
		$this->last_error_mess = $json['D']['Message'];

		if ( array_key_exists('Pagination', $json['D']) ) {
			$this->last_count = $json['D']['Pagination']['TotalRows'];
			$this->last_count_pages = $json['D']['Pagination']['TotalPages'];
			$this->last_current_page = $json['D']['Pagination']['CurrentPage'];
		}
		else {
			$this->last_count = 0;
			$this->last_count_pages = 0;
			$this->last_current_page = 0;
		}

		if ( $json['D']['Success'] == true) {

			// check a couple of conditions to see if we should update the cache
			if ($cache_time > 0 && $data_source == "live" && $method == "GET" && !empty($return)) {

				// update transient item which tracks cache items
				$cache_tracker = get_transient('fmc_cache_tracker');
				$cache_tracker[ $cache_item_name ] = true;
				set_transient('fmc_cache_tracker', $cache_tracker, 60*60*24*7);

				$cache_expire_length = $cache_time*60;
				$return = $this->utf8_encode_mix($return);
				$cache_set_result = set_transient('fmc_cache_'. $cache_item_name, $return, $cache_expire_length);
			}
			elseif ($data_source == "live" && $method == "GET" && !empty($return)) {
				$fmc_instance_cache[ $cache_item_name ]['data'] = $return;
			}

			return $json['D']['Results'];

		}
		elseif ($a_retry == false && $is_auth_request == false && ($this->last_error_code == 1020 || $this->last_error_code == 1000) ) {
			$this->Authenticate(true);
			$return = $this->MakeAPIRequest($method, $uri, $args, $data, $is_auth_request, $cache_time, $a_retry = true);
			return $return;
		}
		else {

			if ($this->last_error_code == "") {
				$this->last_error_code = "API Down";
				$this->last_error_mess = "The flexmls IDX API didn't respond as expected.";
			}

			return false;
		}

	}


	/*
	 * Take a value and clean it so it can be used as a parameter value in what's sent to the API.
	 *
	 * @param string $var Regular string of text to be cleaned
	 * @return string Cleaned string
	 */
	function clean_comma_list($var) {

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

	// source: http://www.php.net/manual/en/function.utf8-encode.php#83777
	function utf8_encode_mix($input, $encode_keys = false) {

		if(is_array($input)) {
			$result = array();
			foreach($input as $k => $v) {
				$key = ($encode_keys)? utf8_encode($k) : $k;
				$result[$key] = $this->utf8_encode_mix( $v, $encode_keys);
			}
		}
		elseif (is_object($input)) {
			return $input;
		}
		else {
			$result = utf8_encode($input);
		}

		return $result;

	}

}
