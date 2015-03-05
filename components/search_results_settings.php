<?php

  class Search_Results_Settings {

    public $title;
    public $source;
    public $display;
    public $days;
    public $property_type;
    public $link;
    public $location;
    public $sort;
    public $agent;

    function __construct($arguments) {

      foreach ($arguments as $key => $value) {
        $this->$key = esc_attr($value);
      }

    }

  }

?>
