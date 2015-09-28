<?php 
/***************************************************************** 

  Page template for fmcSearchResults shortcode generator form. 

******************************************************************/ 
?>

<p>
  <label class="flexmls-admin-field-label" for="fmc_shortcode_field_title"><?php _e('Title:'); ?> </label>
    
  <input fmc-field="title" fmc-type="text" type="text" class="widefat" id="fmc_shortcode_field_title" name="title">
  <?php echo $special_neighborhood_title_ability; ?>
</p>

<?php 
  // IDX link
  $api_links = flexmlsConnect::get_all_idx_links(true);
?>

<p>
  <label class="flexmls-admin-field-label" for="fmc_shortcode_field_link"><?php _e('Saved Search:'); ?></label>
  <select fmc-field="link" fmc-type='select' id="fmc_shortcode_field_link" name="link">

    <option value="">(None)</option>
    <option value='default'>(Use Saved Default)</option>
    <?php foreach ($api_links as $my_l): ?>
      <option value="<?php echo $my_l['LinkId']; ?>">
        <?php echo $my_l['Name']; ?>
      </option>
    <?php endforeach; ?>

  </select>
  <br />
  <span class='description'>flexmls Saved Search to apply</span>
</p>

<!-- filter by -->
<p>
  <label class="flexmls-admin-field-label" for="fmc_shortcode_field_source"><?php _e('Filter by:'); ?></label>
  
  <select fmc-field="source" fmc-type='select' id="fmc_shortcode_field_source" name="source" 
    class="flexmls_connect__listing_source">

    <?php foreach ($source_options as $k => $v): ?>
    <?php $is_selected = ($k == 'location') ? 'selected' : ""; ?>
        <option value="<?php echo $k; ?>" <?php echo $is_selected; ?>>
          <?php echo $v; ?>
        </option>
    <?php 
      endforeach;
    ?>

  </select>
  <br />
  <span class='description'>Which listings to display</span>
</p>

<?php // roster ?>

<?php if (isset($office_roster)): ?>
  <div class='flexmls_connect__roster'>
    <p>
      <label class="flexmls-admin-field-label" for="fmc_shortcode_field_agent"><?php _e('Agent:'); ?></label>
      <select fmc-field="agent" fmc-type='select' id="fmc_shortcode_field_agent" name="agent">
      
        <option value=''>  - Select One -  </option>

        <?php foreach ($office_roster as $agent): ?>
          <option value='<?php echo $agent['Id']; ?>'>
            <?php echo htmlspecialchars($agent['Name']); ?>
          </option>
        <?php endforeach; ?>

      </select>
    </p>
  </div>
<?php endif; ?>

<!-- property type -->
<p class='flexmls_connect__location_property_type_p'>

  <label class="flexmls-admin-field-label" for="fmc_shortcode_field_property_type">
    <?php _e('Property Type:'); ?>
  </label>

  <select fmc-field="property_type" class='flexmls_connect__property_type' fmc-type='select' 
    id="fmc_shortcode_field_property_type" name="property_type">

    <option value=''>All</option>
    <?php foreach ($api_property_type_options as $k => $v): ?>
      <option value="<?php echo $k; ?>">
        <?php echo $v; ?>
      </option>
    <?php endforeach; ?>

  </select>
</p>

<?php //  property sub type ?>
<p>
  <label class="flexmls-admin-field-label" for='fmc_shortcode_field_property_sub_type'>
    <?php _e('Property Sub Type:'); ?>
  </label>
  <select fmc-field="property_sub_type" class='flexmls_connect__property_sub_type' fmc-type='select'
  id='fmc_shortcode_field_property_sub_type' name="property_sub_type">

    <?php foreach ($api_property_type_options as $property_code => $v) { ?>
      <optgroup label="<?php echo $property_code; ?>">
          <option value="" selected="selected">All Sub Types</option>
        <?php foreach ($api_property_sub_type_options as $sub_type) { 
          if(in_array($property_code, $sub_type['AppliesTo']) and $sub_type['Name'] != "Select One" ){
          ?>
            <option value="<?php echo $sub_type["Value"]; ?>"><?php echo $sub_type["Name"]; ?></option>
          <?php
          }
        } // end inner foreach
      ?>
      </optgroup>
    <?php } // end outer foreach?>
  </select>
</p>

<?php // property status ?>

<?php if($standard_status->allow_sold_searching()): ?>
  <p>
    <label class="flexmls-admin-field-label" for="fmc_shortcode_field_status"><?php _e('Status:'); ?></label>
    
    <select fmc-field="status" fmc-type='select' id="fmc_shortcode_field_status" name="status" 
      class="flexmls_connect__listing_status">

      <?php foreach ($standard_status->standard_statuses() as $status_option): ?>
        <option value="<?php echo $status_option["Value"]; ?>">
          <?php echo $status_option["Name"]; ?>
        </option>
      <?php endforeach; ?>

    </select>
  </p>
<?php endif; ?>


<?php // location ?>
<p class='flexmls_connect__location'>
  <label class="flexmls-admin-field-label" for='horizontal'><?php _e('Location:'); ?></label>
  <input type='text' name='location_input' data-connect-url='<?php echo $api_location_search_api; ?>' 
    class='flexmls_connect__location_search' autocomplete='off' value='City, Postal Code, etc.' />
  <a href='javascript:void(0);' title='Click here to browse through available locations' 
    class='flexmls_connect__location_browse'>Browse &raquo;</a>
  <div class='flexmls_connect__location_list' data-connect-multiple='true'>
    <p>All Locations Included</p>
  </div>
  <?php // may need to investigate the value of the input below ?>
  <input type='hidden' name='tech_id' class='flexmls_connect__tech_id' 
    value="x'<?php echo $api_system_info['Id']; ?>'" />
  <input type='hidden' name='ma_tech_id' class='flexmls_connect__ma_tech_id' 
    value="x'<?php echo flexmlsConnect::fetch_ma_tech_id(); ?>'" />
  <input fmc-field="location" fmc-type='text' type='hidden' name="location" 
    class='flexmls_connect__location_fields' />
</p>


<?php // display ?>
<p>
  <label class="flexmls-admin-field-label" for="fmc_shortcode_field_display"><?php _e('Display:'); ?></label>
  <select class='photos_display' fmc-field="display" fmc-type='select' id="fmc_shortcode_field_display" 
    name="display">

    <?php foreach ($display_options as $k => $v): ?>
        <option value='<?php echo $k; ?>'>
          <?php echo $v; ?>
        </option>
    <?php endforeach; ?>
  </select>
</p>

<p class='photos_days' style='display:none'>
  <label class="flexmls-admin-field-label" for="fmc_shortcode_field_day">
    <?php _e('Number of Days:'); ?>
  </label>
  
  <select fmc-field="days" fmc-type='select' id="fmc_shortcode_field_days" name="days">

    <?php foreach ($display_day_options as $k => $v): ?>                         
        <option value='<?php echo $k; ?>'>
          <?php echo $v; ?>
        </option>
    <?php endforeach; ?>

  </select>
</p>

<?php // sort ?>
<p>
<label class="flexmls-admin-field-label" for="fmc_shortcode_field_sort"><?php _e('Sort by:'); ?></label>
  <select fmc-field="sort" fmc-type='select' id="fmc_shortcode_field_sort" name="sort">

    <?php foreach ($sort_options as $k => $v): ?>
      <option value='<?php echo $k; ?>'>
        <?php echo $v; ?>
      </option>
    <?php endforeach; ?>

  </select>
</p>

<img src='x' class='flexmls_connect__bootloader' onerror='flexmls_connect.location_setup(this);' />

<input type='hidden' name='shortcode_fields_to_catch' 
  value='title,link,source,property_type,property_sub_type,location,display,sort,agent,days,status' />

<input type='hidden' name='widget' value='<?php echo get_class($this); ?>' />
