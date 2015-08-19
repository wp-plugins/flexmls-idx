<?php 

class FMC_Account {

  function __construct($data) {
    foreach ($data as $property => $value) {
      $this->$property = $value;
    }

  }

  function primary_email() {
    foreach ($this->Emails as $email) {
      if(array_key_exists("Primary", $email)) {
        return $email["Address"];
      }
    }
    if(sizeof($this->Emails) > 0) {
      return $this->Emails[0]["Address"];
    }
  }


}
