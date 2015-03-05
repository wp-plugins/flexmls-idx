<?php

  class Photo_Settings {

    private $settings;

    function __construct($settings) {
      $this->settings = $settings;
    }

    function __call($name, $arguments) {
      return array_key_exists($name, $this->settings) ? esc_attr(trim($this->settings[$name])) : "";
    }

    function location() {
      return html_entity_decode(
        flexmlsConnect::clean_comma_list(
          //Fixing locations to work with API requests(need backslahes before single quotes). WP-149
          addslashes(
            array_key_exists('location', $this->settings) ? $this->settings['location'] : ""
          )
        )
      );
    }

  }

?>
