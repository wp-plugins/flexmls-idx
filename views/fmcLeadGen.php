<?php echo $before_widget; ?>

<?php 
  if ( !empty($title) ) {
    echo $before_title;
    echo $title;
    echo $after_title;
  }

  if ( !empty($blurb) ) {
    echo "<p>" . $blurb . "</p>";
  }

?>

<form data-connect-validate='true' data-connect-ajax='true' action='<?php echo admin_url('admin-ajax.php'); ?>'>
  
  <?php // This is the spam test field. It's hidden with css. ?>
  <input type="text" name="flexmls_connect__important" id="flexmls_connect__important" tabindex="1000"/>

  <input type='hidden' name='action' value='fmcLeadGen_submit' />
  <input type='hidden' name='nonce' value='<?php echo wp_create_nonce('fmcLeadGen'); ?>' />
  <input type='hidden' name='callback' value='?' />
  <input type='hidden' name='success-message' value='<?php echo htmlspecialchars($success, ENT_QUOTES); ?>' />

  <div class="flexmls_connect__form_row">
    <label for="name" class="flexmls_connect__form_label">Your Name</label>
    <input class="flexmls_connect__form_input" type='text' name='name' id="name" 
      data-connect-default='Your Name' data-connect-validate='text' />
  </div>
  
  <div class="flexmls_connect__form_row">
    <label for="email" class="flexmls_connect__form_label">Email Address</label>
    <input class="flexmls_connect__form_input" type='text' name='email' id="email" 
     data-connect-default='Email Address' data-connect-validate='email' />
  </div>

  <?php if ( in_array('address', $api_prefs['RequiredFields']) ) { ?>
    <div class="flexmls_connect__form_row">
      <label for="address" class="flexmls_connect__form_label">Home Address</label>
      <input class="flexmls_connect__form_input" type='text' name='address' id="address" 
        data-connect-default='Home Address' data-connect-validate='text' />
    </div>
  <?php } ?>

  <?php if ( in_array('address', $api_prefs['RequiredFields']) ) { ?>
    <div class="flexmls_connect__form_row">
      <label for="city" class="flexmls_connect__form_label">City</label>
      <input class="flexmls_connect__form_input" type='text' name='city' id="city" data-connect-default='City' 
        data-connect-validate='text' />
    </div>
  <?php } ?>

  <?php if ( in_array('address', $api_prefs['RequiredFields']) ) { ?>
    <div class="flexmls_connect__form_row">
      <label for="state" class="flexmls_connect__form_label">State</label>
      <input class="flexmls_connect__form_input" type='text' name='state' id="state" 
        data-connect-default='State' data-connect-validate='text' />
    </div>
  <?php } ?>

  <?php if ( in_array('address', $api_prefs['RequiredFields']) ) { ?>
    <div class="flexmls_connect__form_row">
      <label for="zip" class="flexmls_connect__form_label">Zip Code</label>
      <input class="flexmls_connect__form_input" type='text' name='zip' id="zip" data-connect-default='Zip Code' 
        data-connect-validate='text' />
    </div>
  <?php } ?>

  <?php if ( in_array('phone', $api_prefs['RequiredFields']) ) { ?>
    <div class="flexmls_connect__form_row">
      <label for="phone" class="flexmls_connect__form_label">Phone Number</label>
      <input class="flexmls_connect__form_input" type='text' name='phone' id="phone" 
        data-connect-default='Phone Number' data-connect-validate='phone' />
    </div>
  <?php } ?>
    
  <div class="flexmls_connect__form_row">
    <label for="message" class="flexmls_connect__form_label">Your Message</label>
    <textarea class="flexmls_connect__form_textarea" name='message_body' id="message" 
      data-connect-default='Your Message' data-connect-validate='text' rows='5'></textarea>
  </div>

  <?php 
    if ( $use_captcha ) { 
      $a = rand(1, 10); $b = rand(1, 10); $sum = $a + $b;
    ?>
    <div class="flexmls_connect__form_row">
      <label for="captcha" class="flexmls_connect__form_label">
        What is <?php echo $a; ?> + <?php echo $b; ?>?</label>
      <input type="hidden" name="captcha-answer" value="<?php echo $sum; ?>" />
      <input class="flexmls_connect__form_input flexmls-captcha-input" type='text' name='captcha' id="captcha" 
        data-connect-validate='captcha' />
      <span class="flexmls-captcha-hint">Hint: It's <?php echo $sum; ?></span>
    </div>
  <?php } ?>

  <input class="flexmls_connect__form_submit" type='submit' value='<?php echo $buttontext; ?>' />

</form>

<?php echo $after_widget; ?>
