<?php if ($property_types_selected[0] != ''): ?>
  
  <div class='flexmls_connect__search_field flexmls_connect__search_new_property_type 
    flexmls_connect__search_new_field_group'>


    <?php 
      if ($property_type_enabled == "on" and count($good_prop_types) > 0):
    ?>
      <label class='flexmls_connect__search_new_label'>Property Type</label>
    <?php
      foreach ($good_prop_types as $type):
        if(is_array($user_selected_property_types) && in_array($type, $user_selected_property_types))
          $checked = 'checked="checked"';
        else
          $checked = null;
    ?>
        <input type='checkbox' name='PropertyType[]' value='<?php echo $type; ?>' 
          class='flexmls_connect__search_new_checkboxes' <?php echo $checked; ?> >
        <?php echo flexmlsConnect::nice_property_type_label($type); ?>
        <br>
    <?php
      endforeach;
    else:
    ?>
      <input type='hidden' name='PropertyType' value='<?php echo implode(",", $good_prop_types); ?>' />
    <?php endif; ?>
 

    <?php //  property sub type ?>

    <?php foreach ($property_sub_types as $property_code => $sub_types): ?>
      <div id="flexmls_connect__search_new_subtypes_for_<?php echo $property_code; ?>" 
        class="flexmls_connect__search_new_subtypes">
        <label class='flexmls_connect__search_new_label'>Property Sub Types</label>
        <?php foreach ($sub_types as $sub_type): ?>
	<?php $checked = (in_array($sub_type["Value"], $user_selected_property_sub_types)) ? 'checked="checked"' : ''; ?>
          <input type='checkbox' name='PropertySubType[]' value='<?php echo $sub_type["Value"]; ?>' class='flexmls_connect__search_new_checkboxes' <?php echo $checked; ?>>
          <?php echo $sub_type["Name"]; ?><br>
        <?php endforeach;  ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
