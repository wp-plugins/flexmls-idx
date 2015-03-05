<?php

//Register Ajax Responses for this class
add_action('wp_ajax_fmcPortal_No_Thanks', array('flexmlsPortalPopup', 'no_thanks') );
add_action('wp_ajax_nopriv_fmcPortal_No_Thanks', array('flexmlsPortalPopup', 'no_thanks') );

class flexmlsPortalPopup{


  function __construct(){

  }

  /**
  * @param $page Page which the function runs on. (either "search_page" or "detail_page")
  */
  static function popup_portal($page){
    global $fmc_api_portal;
    $options = new Fmc_Settings;
    $show = false;
    $seconds = '-1';

    //Never show if user is logged in
    if ($fmc_api_portal && $fmc_api_portal->is_logged_in()){
      return;
    }
    //Make sure setting is on for appropriate page
    $page_mapping = array(
      'search_page' => $options->portal_search(),
      'detail_page' => $options->portal_listing()
    );
    if (!$page_mapping[$page]){
      return;
    }

  //    Since headers have already been sent, need to update cookie values in javascript
    echo "<input class='flexmlsConnect_cookie' value=$page type='hidden'  />";

    $page_view_number = (isset($_COOKIE[$page])) ? intval($_COOKIE[$page])  : 1 ;
    $needed_page_views = intval($options->$page());
    $show_for_time = ($options->portal_mins() !== null ? (flexmlsPortalPopup::timeout_time_left() <= 0) : false);
    $show_for_page = ($options->$page() !== null ? ($needed_page_views <= $page_view_number) : false);
    if ($show_for_time or $show_for_page){
      $show = true;
    }
    $time_left = false;
    if ($show == false and $options->portal_mins() !== null){
      $time_left = flexmlsPortalPopup::timeout_time_left();
    }

    flexmlsPortalPopup::draw_portal_popup($show, $time_left);
    return;

  }

  function timeout_time_left(){
    global $fmc_api_portal;
    $options = get_option('fmc_settings');
    $start_time = $fmc_api_portal->user_start_time();
    $time_left = ($start_time + (60 * $options['portal_mins']) - time());
    return $time_left;
  }

  function draw_portal_popup($show_now, $seconds){
    //This shows a jquery dialog of the portal on the page.
    global $fmc_api_portal;
    $options = get_option('fmc_settings');
    $Link = $fmc_api_portal->get_portal_page();
    ?>
      <input id="portal_seconds" type=hidden value ="<?php echo $seconds; ?>" />
      <input id="portal_show" type=hidden value ="<?php echo $show_now; ?>" />
      <input id="portal_required" type=hidden value="<?php echo $options['portal_force']; ?>" />
      <input id="portal_position_x" type=hidden value="<?php echo $options['portal_position_x'];?>" />
      <input id="portal_position_y" type=hidden value="<?php echo $options['portal_position_y'];?>" />
      <input id="portal_link" type=hidden value="<?php echo $Link;?>" />
      <div id="fmc_dialog" style="display:none; padding:13px; line-height: 150%" title="Create A Portal">

      <?php echo $options["portal_text"]; ?>
      </div>
    <?php

  }

  function no_thanks(){
    //Cookie values must be deleted in javascript
    ob_clean();
    exit('SUCCESS');
  }

}

?>
