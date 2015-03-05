<?php

class flexmlsAPI_WordPressCache implements flexmlsAPI_CacheInterface {


  function get($key) {
    $value = get_transient($key);
    if ($value !== false) {
      return $value;
    }
    return null;
  }

  function set($key, $value, $expire) {
    return set_transient($key, $value, $expire);
  }



  /**
    Database Cache should only be used if you need the value to be stored
    inside the database (the value cannot expire at any time)
   */
  private function get_db_key(){
    return 'fmc_db_cache_key';
  }

  /**
    Return key/value from database
    null if does not exist or is expired
   */
  function getDB($key){

    $default = array(
      $key => array( 'expire' => 0 )
    );
    $db_values = get_option($this->get_db_key(), $default);
    if ( $db_values[$key]['expire'] < time() ){
      return null;
    }

    return $db_values[$key]['value'];
  }

  /**
    Sets the value in the Database and deletes expired items
    Max size for all key/values combined is 2^32
   */
  function setDB($key, $value, $expire){

    $db_options = get_option($this->get_db_key(), array());
    delete_option($this->get_db_key());

    // Remove Expired Items
    foreach ( $db_options as $db_key => $db_value ){
      if ( $db_value['expire'] < time() ){
        unset( $db_options[$db_key] );
      }
    }
    // add/change the current key
    $db_options[$key] = array('value' => $value, 'expire' => time() + $expire);
    return add_option( $this->get_db_key(), $db_options, '', 'yes' );
  }

  static function clearDB(){
    $temp = new flexmlsAPI_WordPressCache();
    return delete_option($temp->get_db_key() );
  }

}
