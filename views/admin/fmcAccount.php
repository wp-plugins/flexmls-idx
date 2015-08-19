<p>
  <label for="<?php $this->get_field_id('shown_fields') ?>">
   Sections to Show:
  </label>
</p>

<ul>
  <?php foreach ($this->additional_field_options() as $field_id => $display_text) : ?>

    <li>

      <?php $checked = (in_array($field_id, $selected_fields)) ? "checked='checked'" : null; ?>
    
      <input fmc-field='shown_fields' <?php echo $checked; ?> fmc-type='checkbox' type='checkbox' 
        name="<?php echo $this->get_field_name('shown_fields'); ?>[<?php echo $field_id; ?>]" 
        value="<?php echo $field_id; ?>" id="<?php $this->get_field_id('shown_fields'); ?>-<?php echo $field_id; ?>" />

      <label for="<?php $this->get_field_id('shown_fields'); ?>-<?php echo $field_id; ?>">
        <?php echo $display_text; ?>
      </label>

    </li>

  <?php endforeach; ?>
</ul>
  
<input type='hidden' name='shortcode_fields_to_catch' value='$values_to_catch' />
<input type='hidden' name='widget' value="<?php echo get_class($this); ?>" />
