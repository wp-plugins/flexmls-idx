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

	<?php	if ($destination == "remote") { ?>
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

		<?php	// Location Search ?>

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

					// TODO: Dry this out

					foreach ($std_fields_selected as $fi) {

						if ( $fi == "list_price" ) {
							echo  "<div class='flexmls_connect__search_field' data-connect-type='number' data-connect-field='Price'>";
							echo    "<label class='flexmls_connect__search_new_label' for='MinPrice'>Price Range</label>";
							echo    "<input type='text' class='text' value='".($_GET["MinPrice"]?$_GET["MinPrice"]:"")."' name='MinPrice' id='<?php echo $rand}-MinPrice' data-connect-default='Min' onChange=\"this.value =  this.value.replace(/,/g,'').replace(/\\\$/g,'')\" />";
							echo    "<span class='flexmls_connect__search_new_to'>to</span>";
							echo    "<input type='text' class='text' value='".($_GET["MaxPrice"]?$_GET["MaxPrice"]:"")."' name='MaxPrice' id='<?php echo $rand}-MaxPrice' data-connect-default='Max' onChange=\"this.value =  this.value.replace(/,/g,'').replace(/\\\$/g,'')\" />";
							echo  "</div>";
							$search_fields[] = "ListPrice";
						}

						if ( $fi == "beds" ) {
							echo  "<div class='flexmls_connect__search_field' data-connect-type='number' data-connect-field='Beds'>";
							echo    "<label class='flexmls_connect__search_new_label' for='MinBeds'>Bedrooms</label>";
							echo    "<input type='text' class='text' value='".($_GET["MinBeds"]?$_GET["MinBeds"]:"")."' name='MinBeds' id='<?php echo $rand}-MinBeds' data-connect-default='Min' />";
							echo    "<span class='flexmls_connect__search_new_to'>to</span>";
							echo    "<input type='text' class='text' value='".($_GET["MaxBeds"]?$_GET["MaxBeds"]:"")."' name='MaxBeds' id='<?php echo $rand}-MaxBeds' data-connect-default='Max' />";
							echo  "</div>";
							$search_fields[] = "BedsTotal";
						}

						if ( $fi == "baths" ) {
							echo  "<div class='flexmls_connect__search_field' data-connect-type='number' data-connect-field='Baths'>";
							echo    "<label class='flexmls_connect__search_new_label' for='MinBaths'>Bathroom</label>";
							echo    "<input type='text' class='text' value='".($_GET["MinBaths"]?$_GET["MinBaths"]:"")."' name='MinBaths' id='<?php echo $rand}-MinBaths' data-connect-default='Min' />";
							echo    "<span class='flexmls_connect__search_new_to'>to</span>";
							echo    "<input type='text' class='text' value='".($_GET["MaxBaths"]?$_GET["MaxBaths"]:"")."' name='MaxBaths' id='<?php echo $rand}-MaxBaths' data-connect-default='Max' />";
							echo  "</div>";
							$search_fields[] = "BathsTotal";
						}

						if ( $fi == "square_footage" ) {
							echo  "<div class='flexmls_connect__search_field' data-connect-type='number' data-connect-field='SqFt'>";
							echo    "<label class='flexmls_connect__search_new_label' for='MinSqFt'>Square Feet</label>";
							echo    "<input type='text' class='text' value='".($_GET["MinSqFt"]?$_GET["MinSqFt"]:"")."' name='MinSqFt' id='<?php echo $rand}-MinSqFt' data-connect-default='Min' />";
							echo    "<span class='flexmls_connect__search_new_to'>to</span>";
							echo    "<input type='text' class='text' value='".($_GET["MaxSqFt"]?$_GET["MaxSqFt"]:"")."' name='MaxSqFt' id='<?php echo $rand}-MaxSqFt' data-connect-default='Max' />";
							echo   "</div>";
							$search_fields[] = "BuildingAreaTotal";
						}

						if ( $fi == "age" ) {
							echo  "<div class='flexmls_connect__search_field' data-connect-type='number' data-connect-field='Year'>";
							echo    "<label class='flexmls_connect__search_new_label' for='MinYear'>Year Built</label>";
							echo    "<input type='text' class='text' value='".($_GET["MinYear"]?$_GET["MinYear"]:"")."' name='MinYear' id='<?php echo $rand}-MinYear' data-connect-default='Min' />";
							echo    "<span class='flexmls_connect__search_new_to'>to</span>";
							echo    "<input type='text' class='text' value='".($_GET["MaxYear"]?$_GET["MaxYear"]:"")."' name='MaxYear' id='<?php echo $rand}-MaxYear' data-connect-default='Max' />";
							echo  "</div>";
							$search_fields[] = "YearBuilt";
						}
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

		<?php echo $submit_return; ?>

	</form>
</div>

<?php echo $after_widget; ?>



