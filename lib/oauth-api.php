<?php
/**
* Contains class for the OAuth2 user authentication.
* @package API
* @subpackage OAuth2_API
*/

/**
* OAuth2 authentication user class.
* @package API
*/
class flexmlsConnectPortalUser extends flexmlsAPI_OAuth {

	function __construct($oauth_key, $oauth_secret) {
		global $fmc_version;

		$options = get_option('fmc_settings');
		$this->SetCache( new flexmlsAPI_WordPressCache );
		$this->SetApplicationName("flexmls WordPress Plugin/{$fmc_version}/VOW");
		$this->SetCachePrefix("fmc_".get_option('fmc_cache_version')."_VOW");
		$this->user_start_time();
		parent::__construct($oauth_key, $oauth_secret, $this->redirect_uri(), null);
		
	}

	/**
	*	This function tracks how long the visitor has been on the site while not logged in
	*	@return time When the user visited the website, NULL if the user is logged in
	*/
	function user_start_time(){
		if ($this->is_logged_in() and isset($_COOKIE['user_start_time'])){
				//mark cookie for deletion
				setcookie ("user_start_time", "", time() - 3600);
				return NULL;
		}
		else if (!isset($_COOKIE['user_start_time']) and (!headers_sent())){
			setcookie('user_start_time', time() ,time()+60*60*24*30);
		}
		return isset($_COOKIE['user_start_time']) ? $_COOKIE['user_start_time'] : time();
	}

	/**
	* Returns the URI which OAuth redirects to.
	* @static
	* @return string The URI.
	*/
	static function redirect_uri(){
		return get_site_url().'/index.php/oauth/callback';
	}

	/**
	* Returns if the user is logged in or not.
	* @return bool If user is logged in
	*/
	public function is_logged_in(){
		if (!isset($_SESSION['oauth_access_token']) and !isset($_SESSION['oauth_refresh_token']))
			return false;
		return ($this->get_info() ? true: false);
	}

	/**
	* Logs user out. Does NOT end the user's SESSION.
	* Does NOT delete any cookies which may exist.
	*/
	public function log_out(){
		unset($_SESSION['oauth_access_token']);
		unset($_SESSION['oauth_refresh_token']);
		unset($_SESSION['last_token']);
		return;
	}

	/**
	* Gets the URI of the Site Owner's Portal Page
	* @param bool $signup If true, returns the portal signup page instead of login page.
	* @param string $page_override URI of current page/state (For Ajax Use)
	* @return string URI of the portal page
	*/
	public function get_portal_page($signup=false, $additional_state_params=array(), $page_override=null){
		global $fmc_api;
		$options = get_option('fmc_settings');
		$Name = $fmc_api->GetPortal();
		//$raw_state = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$raw_state = parse_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
		if ($page_override != null and count($additional_state_params)>0){
			$raw_state = parse_url($page_override);
		}
		
		if (isset($raw_state['query'])){
			parse_str($raw_state['query'], $query_params);
		} else {
			$query_params=array();
		}
		$raw_state['query'] = http_build_query(array_merge($query_params, $additional_state_params));

		if ($page_override != null and count($additional_state_params)>0){
			$page = explode('?',$page_override);
			$state = $page[0].'?'.$raw_state['query'];
		}
		else
			$state = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		$page_conditions = array(
			'response_type' =>'code',
			'client_id' =>	$options['oauth_key'],
			'redirect_uri' => $this->redirect_uri(),
			'state' => $state,
		);
		$main_link = "https://portal.flexmls.com/r/login/".$Name[0]['Name'];
		if ($signup)
			$main_link.='/signup/';
		return $main_link . '?' . http_build_query($page_conditions);
	}

	/**
	*	Wrapper for an API request to get User's information needed.
	* 	@link is_logged_in
	*	@return Array
	*/
	public function get_info(){
		if (!isset($this->user_info))
			$this->user_info = $this->MyContact(array('_select' => 'DisplayName'));
		return $this->user_info;
	}

	/**
	*	Wrapper for an API request to get User's Listing Carts.
	*	@return Array
	*/
	public function GetListingCarts(){
		if (!isset($this->carts))
			$this->carts = parent::GetListingCarts();
		return $this->carts;
	}

	/*
	All of the Following functions were overloaded 
	from the parent function to use SESSION vars for access tokens
	*/
	function Grant($code, $type = 'authorization_code') {
		$body = array(
			'client_id' => $this->api_client_id,
			'client_secret' => $this->api_client_secret,
		 	'grant_type' => $type,
			'redirect_uri' => $this->oauth_redirect_uri
		);

		if ($type == 'authorization_code') {
			$body['code'] = $code;
		}
		if ($type == 'refresh_token') {
			$body['refresh_token'] = $code;
		}


		$response = $this->MakeAPICall("POST", "oauth2/grant", '0s', array(), json_encode($body) );

		if ($response['success'] == true) {
			$this->SetAccessToken( $response['results']['access_token'] );
			$this->SetRefreshToken( $response['results']['refresh_token'] );

			if ( is_callable($this->access_change_callback) ) {
				call_user_func($this->access_change_callback, 'oauth', array('access_token' => unserialize( $_SESSION['oauth_access_token'] ), 'refresh_token' => unserialize( $_SESSION['oauth_refresh_token']) ));
			}

			return true;
		}
		else {
			return false;
		}
	}

	function SetAccessToken($token) {
		$token = serialize($token);
		$_SESSION['oauth_access_token'] = $token;
		$_SESSION['last_token'] = $token;
	}

	function SetRefreshToken($token) {
		$token = serialize($token);
		$_SESSION['oauth_refresh_token'] = $token;
	}

	function ReAuthenticate() {
		if ( !empty( $_SESSION['oauth_refresh_token'] ) ) {
			return $this->Grant(unserialize( $_SESSION['oauth_refresh_token'] ), 'refresh_token');
		}
		return false;
	}

	function sign_request($request) {
		$last_token = isset($_SESSION['last_token']) ? unserialize($_SESSION['last_token']) : '';
		$this->SetHeader('Authorization', 'OAuth '. $last_token);
		// reload headers into request
		$request['headers'] = $this->headers;
		$request['query_string'] = http_build_query($request['params']);
		$request['cacheable_query_string'] = $request['query_string'];
		return $request;
	}

}


?>
