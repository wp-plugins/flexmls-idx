<?php

  class Fmc_Settings {

    private $options;

    function __construct() {
      $options = get_option('fmc_settings');
      $this->options = $options ? $options : array();
    }

    function __call($name, $arguments) {
      return array_key_exists($name, $this->options) ? $this->options[$name] : null;
    }

  }

?>
