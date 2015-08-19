<?php 

class FMC_IDX_Links {

  public $links;

  function __construct($data) {
    $this->links = array();
    
    foreach ($data as $link) {
      if($link['LinkType'] == 'SavedSearch') {
        $this->links[$link['LinkId']] = array(
          'Id' => $link['LinkId'],
          'Uri' => $link['Uri'], 
          'Name' => $link['Name'], 
          'SearchId' => $link['SearchId']
        );
      }
    }

  }

  public function validate_link($link) {
    $id = is_array($link) ? $link["Id"] : $link;
    return array_key_exists($id, $this->links);
  }

  public function default_link() {
    $link_from_options = $this->get_default_link_from_options();
    return ($link_from_options) ? $link_from_options : reset($this->links);
  }

  private function get_default_link_from_options() {
    $options = get_option('fmc_settings');
    if ( is_array($options) && array_key_exists('default_link', $options) ) {
      if ( $this->validate_link($options['default_link']) ) {
        return $this->links[ $options['default_link'] ];
      }
    }
    return false;
  }

}
