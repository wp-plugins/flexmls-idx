<?php
/**
* Contains class for the API authentication.
* @package API
* @subpackage API_Auth
*/

/**
* API authentication class.
* @package API
*/
class flexmlsConnectUser extends flexmlsAPI_APIAuth {

  function __construct($api_key, $api_secret) {
    global $fmc_version;
    
    parent::__construct($api_key, $api_secret);
    $this->SetCache( new flexmlsAPI_WordPressCache );
    $this->SetApplicationName("flexmls-WordPress-Plugin/{$fmc_version}");
    $this->SetNewAccessCallback( array('flexmlsConnect', 'new_access_keys') );

    $this->SetCachePrefix('fmc_'. get_option('fmc_cache_version') .'_');
  }
  
  /**
  * @todo Create function is_valid() which checks if this is a valid api_key
  */

}

?>
