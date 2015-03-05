<div class="flexmls-admin-field-row">
  <?php $this->label_tag('title', 'Title:') ?>
  <?php $this->text_field_tag('title') ?>
  <?php echo $special_neighborhood_title_ability; ?>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('blurb', 'Description:') ?>
  <?php $this->textarea_tag('blurb') ?>
  <span class='description'>This text appears below the title</span>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('success', 'Success Message:') ?>
  <?php $this->textarea_tag('success') ?>
  <span class='description'>This text appears after the user sends the information</span>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->label_tag('buttontext', 'Button Text:') ?>
  <?php $this->text_field_tag('buttontext') ?>
  <span class='description'>Customize the text of the submit button</span>
</div>

<div class="flexmls-admin-field-row">
  <?php $this->checkbox_tag('use_captcha', array("default" => $captcha_default)); ?>
  <?php $this->label_tag('use_captcha', "Use Captcha?") ?>
</div>

<input type='hidden' name='shortcode_fields_to_catch' value='title,blurb,success,buttontext,use_captcha' />
<input type='hidden' name='widget' value="<?php echo get_class($this); ?>" />
