<?php

class flexmlsJSON {

  static function json_encode($str) {
    if (flexmlsJSON::php_json_available()) {
      return json_encode($str);
    }
    else {
      require_once(ABSPATH . "/wp-includes/js/tinymce/plugins/spellchecker/classes/utils/JSON.php");
      $js = new Moxiecode_JSON();
      // Moxiecode's JSON encoder escapes single quotes when it shouldn't, so unescape those
      return str_replace("\'", "'", $js->encode($str));
    }
  }

  static function json_decode($json) {
    if (flexmlsJSON::php_json_available()) {
      return json_decode($json, true);
    }
    else {
      require_once(ABSPATH . "/wp-includes/js/tinymce/plugins/spellchecker/classes/utils/JSON.php");
      $js = new Moxiecode_JSON();
      return $js->decode($json);
    }
  }

  static function php_json_available() {
    return function_exists('json_decode');
  }

}
