<?php 
/***************************************************************** 

  Page template for IDX Search Widget

******************************************************************/ 
?>

<?php echo $before_widget; ?>

<div class="flexmls_connect__search flexmls_connect__search_new 
  flexmls_connect__search_new_<?php echo $orientation; ?> <?php echo $box_shadow_class; ?>" 
  style="
    color: <?php echo $field_text_color; ?>; 
    max-width: <?php echo $width; ?>px;
    font-family: <?php echo $field_font; ?>, sans-serif; 
    <?php echo $border_radius; ?> 
    background-color: <?php echo $background_color ?>;
  ">

  <?php if ($destination == "remote") { ?>
    <form action='<?php echo $_SERVER['REQUEST_URI'] ?>' method='post' <?php echo $this_target ?> >
  <?php } else { ?>
    <form action="<?php echo flexmlsConnect::make_nice_tag_url('search'); ?>/" method='get'
      <?php echo $this_target; ?> >
  <?php } ?>

    <?php // title ?>
    <div class='flexmls_connect__search_new_title' style="color: <?php echo $title_text_color; ?>; 
      font-family: <?php echo $title_font; ?>, sans-serif;">
      <?php echo $title; ?>
    </div>

    <?php 
      // property types for vertical layout
      if($orientation == 'vertical') { require('_property_types.php'); }
    ?>

    <?php // Location Search ?>

    <?php if ($location_search == "on") { ?>
      <div class='flexmls_connect__search_field'>
        <label>Location</label>
        <input type='text' data-connect-url='<?php echo $api_location_search_api; ?>' 
          class='flexmls_connect__location_search' autocomplete='off' value='City, Zip, Address or Other Location' />
      </div>
    <?php
        $search_fields[] = "Location";
      }
    ?>

    <?php if ($std_fields_selected[0] != '') { ?>
      
      <div class='flexmls_connect__search_new_min_max flexmls_connect__search_new_field_group'>

        <?php

          foreach ($std_fields_selected as $fi) {

            fmcSearch::create_min_max_row($fi);
            
          }
        ?>
      </div>
    <?php } ?>

    
    <?php 
      // property types for horizontal layout
      if($orientation == 'horizontal') { require('_property_types.php'); }
    ?>

    <?php if ($destination == "local" and $user_sorting == "on") { ?>

      <div class='flexmls_connect__search_field flexmls_connect__search_new_sort_by 
        flexmls_connect__search_new_field_group'>
        <label>Sort By</label>
        <select name='OrderBy' size='1'>
          <option value='-ListPrice'>List price (High to Low)</option>
          <option value='ListPrice'>List price (Low to High)</option>
          <option value='-BedsTotal'># Bedrooms</option>
          <option value='-BathsTotal'># Bathrooms</option>
          <option value='-YearBuilt'>Year Built</option>
          <option value='-BuildingAreaTotal'>Square Footage</option>
          <option value='-ModificationTimestamp'>Recently Updated</option>
        </select>
      </div>
    <?php } ?>

    <?php if($allow_sold_searching == "on") : ?>
      <div class='flexmls_connect__search_field flexmls_connect__search_new_field_group'>
        <label class='flexmls_connect__search_new_label'>Listing Status</label>
        <input type='checkbox' name='StandardStatus[]' value='Active'
          class='flexmls_connect__search_new_checkboxes' checked="checked" > Active
        <br>
        <input type='checkbox' name='StandardStatus[]' value='Closed'
          class='flexmls_connect__search_new_checkboxes'> Sold
      </div>
    <?php endif; ?>

    <?php echo $submit_return; ?>

  </form>
</div>

<?php echo $after_widget; ?>


