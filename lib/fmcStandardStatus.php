<?php 

class fmcStandardStatus {

  private $data;

  function __construct($data) {
    $this->data = $data;
  }

  function standard_statuses() {

    $statuses = $this->data;
    
    $return = array();

    if (is_array($statuses)) {
      if(is_array($statuses[0]) && array_key_exists("StandardStatus", $statuses[0])) {
        if(is_array($statuses[0]["StandardStatus"]) && array_key_exists("FieldList", $statuses[0]["StandardStatus"])) {
          if(is_array($statuses[0]["StandardStatus"]["FieldList"])) {
            $return = $statuses[0]["StandardStatus"]["FieldList"];
          }
        }
      }
    }

    return $return;
  }


  function allow_sold_searching() {
    $statuses = $this->standard_statuses();

    foreach ($statuses as $status) {
      if ($status["Value"] == "Closed"){
        return true;
      }
    }
    return false;
  }


}
