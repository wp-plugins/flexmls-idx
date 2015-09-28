<?php


class fmcWidget extends WP_Widget {

  // holds the path for the view templates
  public $page_view;
  public $admin_page_view;
  public $admin_view_vars;

  public function __construct() {
    global $fmc_plugin_dir;

    // set the view template for the widget
    $class_name = get_class($this);
    $this->page_view = $fmc_plugin_dir . "/views/{$class_name}.php";
    $this->admin_page_view = $fmc_plugin_dir . "/views/admin/{$class_name}.php";

    $this->options = new Fmc_Settings;
  }


  function shortcode_form() {
    global $fmc_widgets;

    $widget_info = $fmc_widgets[ get_class($this) ];

    $settings_content = $this->settings_form( array('_instance_type' => 'shortcode') );

    $response = array(
        'title' => $widget_info['title'] .' widget',
        'body' => flexmlsConnect::shortcode_header() . $settings_content . flexmlsConnect::shortcode_footer()
    );

    echo flexmlsJSON::json_encode($response);

    exit;

  }
  

  function cache_jelly($args, $instance, $type) {
    global $fmc_widgets;

    $widget_info = $fmc_widgets[ get_class($this) ];

    $cache_item_name = md5(get_class($this) .'_'. serialize($instance) . $type);
    $cache = get_transient('fmc_cache_'. $cache_item_name);

    if (!empty($cache) && flexmlsConnect::cache_turned_on() == true) {
      $return = $cache;
    }
    else {
      $return = $this->jelly($args, $instance, $type);
      $cache_set_result = set_transient('fmc_cache_'. $cache_item_name, $return, $widget_info['max_cache_time']);

      // update transient item which tracks cache items
      $cache_tracker = get_transient('fmc_cache_tracker');
      $cache_tracker[ $cache_item_name ] = true;
      set_transient('fmc_cache_tracker', $cache_tracker, 60*60*24*7);
    }

    return $return;

  }

  // form for generating a widget. used in appearence > widgets. not used for shortcode forms
  function form($instance) {
    echo "<div class='flexmls-widget-settings'>";
    echo $this->settings_form($instance);
    echo "</div>";
  }

  function settings_form($instance) {
    $this->instance = $instance;
    $this->admin_view_vars = $this->admin_view_vars();
    return $this->render_admin_view();
  }

  function shortcode_generate() {
    global $fmc_widgets;

    $widget_info = $fmc_widgets[ get_class($this) ];

    $shortcode = "[{$widget_info['shortcode']}";
    
    $is_service_lacking_filter_support = false;
    $shortcode_source = flexmlsConnect::wp_input_get_post('source');
    if ($shortcode_source != "location") {
      $is_service_lacking_filter_support = true;
    }
    $is_slideshow_widget = ($widget_info['shortcode'] == "idx_slideshow") ? true : false;

    foreach ($_REQUEST as $k => $v) {
      if ($k == "action") {
        continue;
      }
      
      if ($is_slideshow_widget && $is_service_lacking_filter_support && ($k == "property_type" || $k == "location")) {
        continue;
      }
      
      if (!empty($v)) {
        $v = htmlentities(stripslashes($v), ENT_QUOTES);
        $shortcode .= " {$k}=\"{$v}\"";
      }
    }

    $shortcode .= "]";

    $response = array(
        'body' => $shortcode
    );

    echo flexmlsJSON::json_encode($response);

    wp_die();
  }

  function render_view($view_vars = null) {
    return $this->render($this->page_view, $view_vars);
  }

  function render_admin_view() {
    return $this->render($this->admin_page_view, $this->admin_view_vars);
  }

  private function render($path_to_view, $view_vars) {
    if (file_exists($path_to_view)) {
      if(is_array($view_vars)){
        extract($view_vars);
      }
      ob_start();
        require($path_to_view);
        $view = ob_get_contents();
      ob_end_clean();
      return $view;
    } else {
      return false;
    }
  }


  function get_field_id($val) {
    $widget = $this->is_called_for_widget();
    if ($widget) {
      return parent::get_field_id($val);
    }
    else {
      return "fmc_shortcode_field_{$val}";
    }
  }


  function get_field_name($val) {
    $widget = $this->is_called_for_widget();
    if ($widget) {
      return parent::get_field_name($val);
    }
    else {
      return $val;
    }
  }


  function is_called_for_widget() {
    // find out what context this was called from
    $backtrace = debug_backtrace();
    if ($backtrace[3]['function'] == "shortcode_form") {
      return false;
    }
    else {
      return true;
    }
  }


  function requestVariableArray($key) {
    if ( isset($_GET[$key]) ) {
      if(is_array($_GET[$key])) {
        return $_GET[$key];
      } elseif (is_string($_GET[$key])) {
        return explode(',', $_GET[$key]);
      }
    } else {
      return array();
    }
  }
  

  protected function label_tag($for, $display_text) {
    echo '<label for="' . $this->get_field_id($for) . '" class="flexmls-admin-field-label">';
      _e($display_text);
    echo '</label>';
  }

  protected function text_field_tag($for, $args = array()) {
    $size = array_key_exists('size', $args) ? $args['size'] : null;
    $class = array_key_exists('class', $args) ? $args['class'] : 'widefat';

    $default = array_key_exists('default', $args) ? $args['default'] : null;
    $value = $this->get_field_value($for) != false ? $this->get_field_value($for) : $default;

    echo "<input fmc-field=\"$for\" fmc-type='text' size='$size' type='text' class='$class' 
      id='{$this->get_field_id($for)}' name='{$this->get_field_name($for)}' 
      value='{$value}'>";
  }

  protected function textarea_tag($for, $args = array()) {
    echo "<textarea fmc-field=\"$for\" fmc-type='text' id='{$this->get_field_id($for)}' 
      class='flexmls-admin-textarea' name='{$this->get_field_name($for)}'>";
    echo $this->get_field_value($for);
    echo "</textarea>";
  }

  protected function checkbox_tag($for, $args = array()) {
    $default = array_key_exists('default', $args) ? $args["default"] : null;
    $previous_value = $this->get_field_value($for);
    $checked = $default === true ? "checked" : null;

    if ($previous_value === true) {
      $checked = "checked";
    } elseif ($previous_value === false) {
      $checked = null;
    }
    echo "<input fmc-field=\"$for\" type='checkbox' fmc-type='checkbox' name='{$this->get_field_name($for)}' 
      value='true' $checked >";
  }

  protected function get_field_value($field) {
    if (is_array($this->instance) && array_key_exists($field, $this->instance)) {
      $value = $this->instance[$field];
      return ($value === true || $value === false) ? $value : esc_attr($value);
    } else {
      return ($this->is_bool_field($field)) ? "off" : null;
    }
  }

  private function is_bool_field($field) {
    $bool_fields = array("allow_sold_searching");
    return in_array($field, $bool_fields);
  }

}
