<?php
/**
* @package Widget
* @subpackage my-account
*/

/**
* @package Widget
*/
class fmcAccount extends fmcWidget {

  function fmcAccount() {
    
    parent::__construct();

    global $fmc_widgets;

    $widget_info = $fmc_widgets[ get_class($this) ];

    $widget_ops = array( 'description' => $widget_info['description'] );
    $this->WP_Widget( get_class($this) , $widget_info['title'], $widget_ops);
    add_shortcode($widget_info['shortcode'], array(&$this, 'shortcode'));
    // have WP replace instances of [first_argument] with the return from the second_argument function
    add_action('wp_ajax_'.get_class($this).'_shortcode', array(&$this, 'shortcode_form') );
    add_action('wp_ajax_'.get_class($this).'_shortcode_gen', array(&$this, 'shortcode_generate') );

    add_action('wp_ajax_'.get_class($this).'_remove_cart', array(&$this, 'ajax_remove_listing_from_cart') );
    add_action('wp_ajax_nopriv_'.get_class($this).'_remove_cart', array(&$this, 'ajax_remove_listing_from_cart') );

    add_action('wp_ajax_'.get_class($this).'_add_cart', array(&$this, 'ajax_add_listing_to_cart') );
    add_action('wp_ajax_nopriv_'.get_class($this).'_add_cart', array(&$this, 'ajax_add_listing_to_cart') );

    add_action('wp_ajax_'.get_class($this).'_portal', array(&$this, 'portal_clear_session_vars') );
    add_action('wp_ajax_nopriv_'.get_class($this).'_portal', array(&$this, 'portal_clear_session_vars') );

    return;
  }

  /**
  * This function is designed to work only with Ajax Requests.
  * Exits with string SUCCESS or redirect_uri.
  * Requires The Following Post Variables:
  * - flexmls_cart_id (Id of the listing cart to remove from)
  * - flexmls_listing_id (Id of the listing to remove)
  * - flexmls_cart_type (Type of cart to be added if not logged in)
  */
  function ajax_remove_listing_from_cart(){
    ob_clean();
    global $fmc_api_portal;

    $bool = $fmc_api_portal->DeleteListingsFromCart(
      flexmlsConnect::wp_input_get_post('flexmls_cart_id'),
      flexmlsConnect::wp_input_get_post('flexmls_listing_id') );
    $add_cart_params = array(
      'remove_cart'=>flexmlsConnect::wp_input_get_post('flexmls_cart_type'),
      'listing_id' => flexmlsConnect::wp_input_get_post('flexmls_listing_id')
      );
    $current_page = flexmlsConnect::wp_input_get_post('flexmls_page_override');
    $bool ? exit("SUCCESS") : exit($fmc_api_portal->get_portal_page(false, $add_cart_params, $current_page));
  }

  /**
  * This function is designed to work only with Ajax Requests.
  * Exits with string SUCCESS or redirect_uri.
  * Requires The Following Post Variables:
  * - flexmls_cart_id (Id of the listing cart to add to)
  * - flexmls_listing_id (Id of the listing to add)
  * - flexmls_cart_type (Type of cart to be added if not logged in)
  */
  function ajax_add_listing_to_cart(){
    ob_clean();
    global $fmc_api_portal;

    $bool = $fmc_api_portal->AddListingsToCart(
      flexmlsConnect::wp_input_get_post('flexmls_cart_id'),
      array(flexmlsConnect::wp_input_get_post('flexmls_listing_id')) );
    $add_cart_params = array(
      'add_cart'=>flexmlsConnect::wp_input_get_post('flexmls_cart_type'),
      'listing_id' => flexmlsConnect::wp_input_get_post('flexmls_listing_id')
      );
    $current_page = flexmlsConnect::wp_input_get_post('flexmls_page_override');
    $bool ? exit("SUCCESS") : exit($fmc_api_portal->get_portal_page(false, $add_cart_params, $current_page));
  }

  function portal_clear_session_vars(){
    ob_clean();
    global $fmc_api_portal;
    $fmc_api_portal->log_out();
    exit(true);
  }

  function jelly($args, $settings, $type) {
    global $fmc_api;
    global $fmc_plugin_url;
    global $fmc_api_portal;
    extract($args);
    ob_start();

    if (!$fmc_api_portal->is_logged_in()){
      $this->sign_up($settings);
    }
    else {
      $this->logged_in($settings);
    }
    $content = ob_get_contents();
    ob_end_clean();
    return $content;

  }

  function widget($args, $instance) {
    echo $this->jelly($args, $instance, "widget");
  }


  function shortcode($attr = array()) {

    $args = array(
        'before_title' => '<h3>',
        'after_title' => '</h3>',
        'before_widget' => '',
        'after_widget' => ''
        );

    return $this->jelly($args, $attr, "shortcode");

  }

  function admin_view_vars() {
    $vars = array();

    $vars["values_to_catch"] = implode(",", array_keys($this->additional_field_options()));

    if (array_key_exists('shown_fields', $this->instance)){
      $vars["selected_fields"] = explode(",", esc_attr($this->instance['shown_fields']));
    } else {
      $vars["selected_fields"] = array();
    }

    return $vars;
  }

  function additional_field_options() {
    return array(
      'log_in'    => 'Log in prompt when not signed in',
      'name'      => "Visitor Name and Sign-out Button",
      'searches'  => "Saved Searches",
      'carts'     => "Listing Carts"
    );
  }

  function update($new_instance, $old_instance) {
    $instance = $old_instance;

    $shown_fields_selected = "";
    if (is_array($new_instance['shown_fields'])) {
      foreach ($new_instance['shown_fields'] as $v) {
        if (!empty($shown_fields_selected)) {
          $shown_fields_selected .= ",";
        }
        $shown_fields_selected .= strip_tags(trim($v));
      }
    }

    $instance['shown_fields'] = $shown_fields_selected;

    return $instance;
  }

  private function sign_up($settings){
    global $fmc_api_portal;
    $shown_fields = trim($settings['shown_fields']);
    $show_shown_fields = explode(",", $shown_fields);
    $options = get_option('fmc_settings');
    if (!in_array('log_in',$show_shown_fields)){
      return;
    }
    ?>
      <div class="dialog" style="background:lightgrey;padding:15px;border-width:1px;border-style:solid;border-bottom:none;">
      <b>Create A Real Estate Portal</b>
      </div>
      <div style="margin-bottom:10px;border-width:1px;border-style:solid;border-top:none;padding:15px;">
        <?php echo $options["portal_text"]; ?>
        <div>
          <div>
            <button class='flexmls_connect__page_content portal-button-primary flexmls_connect__button' href='<?php echo $fmc_api_portal->get_portal_page(); ?>'> Log In </button>
            <button class='flexmls_connect__page_content portal-button-primary flexmls_connect__button' href='<?php echo $fmc_api_portal->get_portal_page(true); ?>'> Sign Up </button>
          </div>
        </div>
      </div>
    <?php
  }




  private function logged_in($settings){
    global $fmc_api_portal;
    $shown_fields = trim($settings['shown_fields']);
    $show_shown_fields = explode(",", $shown_fields);
    ?><div class="my_account_outer"><?php
    if (in_array('name',$show_shown_fields)) {
      $this->display_name();
    }?>
    <div>
    <?php

    if (in_array('carts',$show_shown_fields)){
      $this->display_carts();
    }

    if (in_array('searches',$show_shown_fields)){
      $this->display_searches();
    }?>
    </div>
    </div>
    <?php
  }

  private function display_searches(){
    global $fmc_api_portal;
    ?>
      <div class="my_account_inner">
      <span class='flexmls_connect__heading'>My Searches</span><br />
        <?php $searches = $fmc_api_portal->GetSavedSearches(); ?>

        <?php foreach($searches as $search): ?>
          <a title="<? echo $search['Description'] ?>" href='<?php echo flexmlsConnect::make_nice_tag_url('search',array('SavedSearch'=>$search['Id'])); ?>'>
            <?php echo $search['Name'] ?>
          </a>
          <br/>
        <?php endforeach ?>
      </div>
    <?php
    return;
  }

  private function display_name(){
    global $fmc_api_portal;
    $info = $fmc_api_portal->get_info();
    ?>
      <a href='#' style='float: right;' class=flexmls_connect_log_out>Sign Out</a>
      <span style='font-weight:bold; font-size: 1.2em;'>
        Hello,
        <span style='display: inline-block; margin-right: 10px;'>
          <?php echo $info['DisplayName']; ?>!
        </span>
      </span>
    <?php
    return;
  }

  private function display_carts(){
    global $fmc_api_portal;
    $carts = $fmc_api_portal->GetListingCarts();
    $ignore_type = array('Recommended','Removed');
    ?>
    <div class='my_account_inner'>
    <span class='flexmls_connect__heading'>My Listing Carts</span><br />

    <?php foreach ($carts as $cart):
      if (in_array($cart['PortalCartType'],$ignore_type)){
        continue;
      }
      if ($cart['ListingCount']>=0) : ?>
        <a href='<?php echo flexmlsConnect::make_nice_tag_url('search',array('ListingCart'=>$cart['Id']),'fmc_vow_tag');?>' >
          <?php echo $cart['Name'].' ('.$cart['ListingCount'].')' ?>
        </a>
        <br/>
      <?php endif;?>
    <?php endforeach;?>
    </div>
    <?php
  }

  static function write_carts(&$record){
    global $fmc_api_portal;
    $options = new Fmc_Settings;

    if (!$options->portal_carts())
      return;

    $is_favorite = null;
    $is_possibility = null;
    $is_reject = null;
    $is_selected = ' selected ';

    $favorite_id = null;
    $possibility_id = null;
    $reject_id = null;

    if ($fmc_api_portal->is_logged_in()){

      $this_cart = ($fmc_api_portal->GetListingCartsWithListing($record['Id']));
      $all_carts = $fmc_api_portal->GetListingCarts();

      if (is_array($all_carts)){
        foreach ($all_carts as $single_cart){
          if ($single_cart['PortalCartType']=='Favorites'){
            $favorite_id = $single_cart['Id'];
          }
          elseif ($single_cart['PortalCartType']=='Possibilities') {
            $possibility_id = $single_cart['Id'];
          }
          elseif ($single_cart['PortalCartType']=='Rejects') {
            $reject_id = $single_cart['Id'];
          }
        }
      }

      if (is_array($this_cart))
      foreach($this_cart as $the_cart) {

        if ($the_cart['PortalCartType'] == 'Favorites'){
          $is_favorite = $is_selected;
        }
        if ($the_cart['PortalCartType'] == 'Possibilities'){
          $is_possibility = $is_selected;
        }
        if ($the_cart['PortalCartType'] == 'Rejects'){
          $is_reject = $is_selected;
        }
      }
    }

    ?>
    <div class='listing_cart' value=<?php echo $record['Id']?>>
      <span class="Favorites flexmls_portal_cart_handle <?php echo $is_favorite ?>" 
        title="Mark this listing as a favorite (Login required)" value='<?php echo $favorite_id ?>' >
        <i class="flexmls-icon-heart"></i>
      </span>

      <span class="Possibilities flexmls_portal_cart_handle <?php echo $is_possibility ?>" 
        title="Like (Login required)" value='<?php echo $possibility_id ?>' >
        <i class="flexmls-icon-thumb_up"></i>
      </span>

      <span class="Rejects flexmls_portal_cart_handle <?php echo $is_reject ?>" 
        title="No Interest (Login required)" value='<?php echo $reject_id ?>' >
        <i class="flexmls-icon-thumb_down"></i>
      </span>
    </div>
  <?php
  }

}
