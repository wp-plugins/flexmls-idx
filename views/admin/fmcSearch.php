<?php 
/***************************************************************** 

  Page template for IDX Search Widget shortcode generator form. 

******************************************************************/ 
?>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('title', 'Title:') ?>
  <?php $this->text_field_tag('title') ?>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('link', 'IDX Link:') ?>  
  <?php $this->select_tag( array(
    'fmc_field' => 'link', 
    'collection' => $idx_links,
    'option_value_attr' => 'LinkId', 
    'option_display_attr' => 'Name',
    'class' => 'widefat',
    'default' => $idx_links_default
  )) ?>  
  <span class="description">Link used when search is executed</span>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('buttontext', "Submit Button Text:") ?>
  <?php $this->text_field_tag('buttontext') ?>
  <span class="description">(ex. "Search for Homes")</span>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('detailed_search', "Detailed Search:") ?>
  <?php $this->select_tag( array(
    'fmc_field' => 'detailed_search',
    'collection' => $on_off_options,
    'option_value_attr' => 'value',
    'option_display_attr' => 'display_text',
    'class' => 'flexmls_connect__setting_enabler_detailed_search'
  ) ); ?>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('destination', "Send users to:") ?>
  <?php $this->select_tag( array(
    'fmc_field' => 'destination',
    'collection' => $destination_options,
    'option_value_attr' => 'value',
    'option_display_attr' => 'display_text',
  ) ); ?>
</div>

<div class="flexmls-shortcode-section-title">Sorting</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('user_sorting', "User Sorting:") ?>
  <?php $this->select_tag( array(
    'fmc_field' => 'user_sorting',
    'collection' => $on_off_options,
    'option_value_attr' => 'value',
    'option_display_attr' => 'display_text',
    'class' => "flexmls_connect__setting_enabler_user_sorting"
  ) ); ?>
</div>

<div class="flexmls-shortcode-section-title">Filters</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('location_search', "Location Search:") ?>
  <?php $this->select_tag( array(
    'fmc_field' => 'location_search',
    'collection' => $on_off_options,
    'option_value_attr' => 'value',
    'option_display_attr' => 'display_text',
    'class' => "flexmls_connect__setting_enabler_location_search"
  ) ); ?>
</div>

<?php if ($mls_allows_sold_searching): ?>
  <div class="flexmls-admin-field-row">
    <?php $this->label_tag('allow_sold_searching', "Allow Sold Searching:") ?>
    <?php $this->select_tag( array(
      'fmc_field' => 'allow_sold_searching',
      'collection' => $on_off_options,
      'option_value_attr' => 'value',
      'option_display_attr' => 'display_text',
      'default' => $allow_sold_searching_default
    ) ); ?>
  </div>
<?php endif; ?>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('property_type_enabled', "Property Type:") ?>
  <?php $this->select_tag( array(
    'fmc_field' => 'property_type_enabled',
    'collection' => $on_off_options,
    'option_value_attr' => 'value',
    'option_display_attr' => 'display_text',
    'class' => "flexmls_connect__setting_enabler_property_type_enabled"
  ) ); ?>
</div>

<div class="flexmls_connect__disable_group_property_type_enabled flexmls-admin-field-row">
  <?php $this->label_tag('property_type', "Property Types:") ?>

  <div>
    <input fmc-field="property_type" fmc-type="text" type="hidden" 
      name='<?php echo $this->get_field_name("property_type"); ?>'
      class="flexmls_connect__list_values" value="<?php echo $this->get_field_value("property_type"); ?>">
    
    <?php $this->sortable_list($selected_property_types); ?>
    
    <select name="available_types" class="flexmls_connect__available">
      <?php foreach ($property_types as $id => $name): ?>
        <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
      <?php endforeach; ?>
    </select>
    
    <button title="Add this to the search" class="flexmls_connect__add_property_type">Add Type</button>
    <img src="x" class="flexmls_connect__bootloader" onerror="flexmls_connect.sortable_setup(this);">
  </div>

</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('std_fields', "Fields:") ?>

  <div>
    <input fmc-field="std_fields" fmc-type="text" type="hidden" 
      name="<?php echo $this->get_field_name('std_fields'); ?>" class="flexmls_connect__list_values" 
      value="<?php echo $this->get_field_value("std_fields"); ?>">

    <?php $this->sortable_list($selected_std_fields); ?>

    <select name="available_fields" class="flexmls_connect__available">
      <?php foreach ($available_fields as $field): ?>
        <option value="<?php echo $field['value']; ?>"><?php echo $field['display_text']; ?></option>
      <?php endforeach; ?>
    </select>

    <button title="Add this to the search" class="flexmls_connect__add_std_field">Add Field</button>
    <img src="x" class="flexmls_connect__bootloader" onerror="flexmls_connect.sortable_setup(this);">
  </div>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('theme', "Select a Theme:") ?>
   <?php $this->select_tag( array(
    'fmc_field' => 'theme',
    'collection' => $theme_options,
    'option_value_attr' => 'value',
    'option_display_attr' => 'display_text',
    'class' => "flexmls_connect__theme_selector widefat"
  ) ); ?>
  <span class="description">
    Selecting a theme will override your current layout, style and color settings. The default width of a 
    vertical theme is 300px and 730px for horizontal.
  </span>
</div>

<div class="flexmls-shortcode-section-title">Layout</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('orientation', "Orientation:") ?>
  <?php $this->select_tag( array(
    'fmc_field' => 'orientation',
    'collection' => $orientation_options,
    'option_value_attr' => 'value',
    'option_display_attr' => 'display_text'
  ) ); ?>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('width', "Widget Width:") ?>
  <?php $this->text_field_tag('width', array('class' => '', 'size' => '5')) ?>
  px
</div>

<div class="flexmls-shortcode-section-title">Style</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('title_font', "Title Font:") ?>
  <?php $this->select_tag( array(
    'fmc_field' => 'title_font',
    'collection' => $fonts
  ) ); ?>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('field_font', "Field Font:") ?>
  <?php $this->select_tag( array(
    'fmc_field' => 'field_font',
    'collection' => $fonts
  ) ); ?>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('border_style', "Border Style:") ?>
  <?php $this->select_tag( array(
    'fmc_field' => 'border_style',
    'collection' => $border_style_options,
    'option_value_attr' => 'value',
    'option_display_attr' => 'display_text'
  ) ); ?>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('widget_drop_shadow', "Widget Drop Shadow:") ?>
  <?php $this->select_tag( array(
    'fmc_field' => 'widget_drop_shadow',
    'collection' => $on_off_options,
    'option_value_attr' => 'value',
    'option_display_attr' => 'display_text',
    'class' => "flexmls_connect__setting_enabler_widget_drop_shadow"
  ) ); ?>
</div>


<div class="flexmls-shortcode-section-title">Color</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('background_color', "Background:") ?>
  <?php $this->color_field_tag('background_color', "#FFFFFF" ) ?>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('title_text_color', "Title Text:") ?>
  <?php $this->color_field_tag('title_text_color', "#000000" ) ?>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('field_text_color', "Field Text:") ?>
  <?php $this->color_field_tag('field_text_color', "#000000" ) ?>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('detailed_search_text_color', "Detailed Search:") ?>
  <?php $this->color_field_tag('detailed_search_text_color', "#000000" ) ?>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('submit_button_shine', "Submit Button:") ?>

  <?php $this->select_tag( array(
    'fmc_field' => 'submit_button_shine',
    'collection' => $submit_button_options,
    'option_value_attr' => 'value',
    'option_display_attr' => 'display_text'
  ) ); ?>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('submit_button_background', "Submit Button Background:") ?>
  <?php $this->color_field_tag('submit_button_background', "#000000" ) ?>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('submit_button_text_color', "Submit Button Text:") ?>
  <?php $this->color_field_tag('submit_button_text_color', "#FFFFFF" ) ?>
</div>

<input type='hidden' name='shortcode_fields_to_catch' value='title, link, buttontext, detailed_search, 
  detailed_search_text, destination, user_sorting, location_search, property_type_enabled, property_type, 
  std_fields, theme, orientation, width, title_font, field_font, border_style, widget_drop_shadow, 
  background_color, title_text_color, field_text_color, detailed_search_text_color, submit_button_shine, 
  submit_button_background, submit_button_text_color, allow_sold_searching' />

<input type='hidden' name='widget' value='fmcSearch' />

<script type='text/javascript'>
  // set up the color picker for the search widget
  jQuery('.wp-color-picker').wpColorPicker(); 
</script>
