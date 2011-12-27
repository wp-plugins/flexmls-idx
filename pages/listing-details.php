<?php

class flexmlsConnectPageListingDetails extends flexmlsConnectPageCore {

	private $listing_data;
	protected $search_criteria;
	

	function pre_tasks($tag) {
		global $fmc_special_page_caught;
		global $fmc_api;
		
		// parse passed parameters for browsing capability
		list($params, $cleaned_raw_criteria, $context) = $this->parse_search_parameters_into_api_request();
		$this->search_criteria = $cleaned_raw_criteria;

		preg_match('/\-mls\_(.*?)$/', $tag, $matches);

		$id_found = $matches[1];

		$params = array(
				'_filter' => "ListingId Eq '{$id_found}'",
				'_limit' => 1,
				'_expand' => 'Photos,Videos,OpenHouses,VirtualTours,Documents,Rooms,CustomFields'
		);
		$result = $fmc_api->GetListings($params);
		$listing = $result[0];

		$fmc_special_page_caught['type'] = "listing-details";
		$this->listing_data = $listing;
		if ($listing != null) {
			$fmc_special_page_caught['page-title'] = flexmlsConnect::make_nice_address_title($listing);
			$fmc_special_page_caught['post-title'] = flexmlsConnect::make_nice_address_title($listing);
			$fmc_special_page_caught['page-url'] = flexmlsConnect::make_nice_address_url($listing);
		}
		else {
			$fmc_special_page_caught['page-title'] = "Listing Not Available";
			$fmc_special_page_caught['post-title'] = "Listing Not Available";
//			$fmc_special_page_caught['page-url'] = flexmlsConnect::make_nice_address_url($listing);
		}
	}


	function generate_page($from_shortcode = false) {
		global $fmc_api;
		global $fmc_special_page_caught;
		global $fmc_plugin_url;
		
		if ($this->listing_data == null) {
			return "<p>The listing you requested is no longer available.</p>";
		}
		
		$mls_property_detail_fields = array(
		    'MLS#' => 'ListingId',
		    'Status' => 'MlsStatus',
		    'Sold Date' => 'CloseDate',
		    'Property Type' => 'PropertyType',
		    'Property Sub-Type' => 'PropertySubType',
		    'County' => 'CountyOrParish',
		    '# of Bedrooms' => 'BedsTotal',
		    '# of Bathrooms' => 'BathsTotal',
		    '# of Full Baths' => 'BathsFull',
		    '# of Half Baths' => 'BathsHalf',
		    '# of 3/4 Baths' => 'BathsThreeQuarter',
		    'Year Built' => 'YearBuilt',
		    'Total SQFT' => 'BuildingAreaTotal',
		    'Latitude' => 'Latitude',
		    'Longitude' => 'Longitude',
		    'Subdivision' => 'SubdivisionName',
		    'Area' => 'MLSAreaMinor',
		);
		
		
		ob_start();
		
		// disable display of the H1 entry title on this page only
		echo "<style type='text/css'>\n  .entry-title { display:none; }\n</style>\n\n\n";
		
		echo "<div class='flexmls_connect__prev_next'>";
		  if ( $this->has_previous_listing() )
			  echo "<button class='flexmls_connect__button left' href='". $this->browse_previous_url() ."'><img src='{$fmc_plugin_url}/images/left.png' align='absmiddle' /> Prev</button>\n";
		  if ( $this->has_next_listing() )
			  echo "<button class='flexmls_connect__button right' href='". $this->browse_next_url() ."'>Next <img src='{$fmc_plugin_url}/images/right.png' align='absmiddle' /></button>\n";
		echo "</div>";
		
		// set some variables
		$record =& $this->listing_data;
		$sf =& $record['StandardFields'];
		$listing_address = flexmlsConnect::format_listing_street_address($record);
		$first_line_address = htmlspecialchars($listing_address[0]);
		$second_line_address = htmlspecialchars($listing_address[1]);
		$one_line_address = htmlspecialchars($listing_address[2]);

		// begin
		echo "<div class='flexmls_connect__sr_detail' title='{$one_line_address} - MLS# {$sf['ListingId']}'>\n";
		echo "  <img src='{$sf['Photos'][0]['UriLarge']}' class='flexmls_connect__resize_image' />\n";
		echo "  <hr class='flexmls_connect__sr_divider'>\n";
		
		echo "  <div class='flexmls_connect__sr_address'>\n";
			
			// show price
  		echo "<div class='flexmls_connect__sr_price'>$". number_format($sf['ListPrice'], 0, '.', ',') . "</div>\n";		  

  		// show top address details
  		echo "{$first_line_address}<br>\n";
  		echo "{$second_line_address}<br>\n";
		
  		// show under address details (beds, baths, etc.)
  		$under_address_details = array();
		
  		if ( flexmlsConnect::is_not_blank_or_restricted($sf['BedsTotal']) ) {
  			$under_address_details[] = $sf['BedsTotal'] .' beds';
  		}
		
  		if ( flexmlsConnect::is_not_blank_or_restricted($sf['BathsTotal']) ) {
  			$under_address_details[] = $sf['BathsTotal'] .' baths';
  		}
		
  		if ( flexmlsConnect::is_not_blank_or_restricted($sf['BuildingAreaTotal']) ) {
  			$under_address_details[] = $sf['BuildingAreaTotal'] .' sqft';
  		}
		
  		echo implode(" &nbsp;|&nbsp; ", $under_address_details) . "<br>\n";
						
  				
		echo "  </div>\n";
		
		echo "  <hr class='flexmls_connect__sr_divider'>\n";
		
		
		// find the count for media stuff
		$count_photos = count($sf['Photos']);
		$count_videos = count($sf['Videos']);
		$count_tours = count($sf['VirtualTours']);
		$count_openhouses = count($sf['OpenHouses']);
		
		// display buttons
		echo "  <div class='flexmls_connect__sr_details'>\n";
		  
		  // first, media buttons are on the right
  		echo "    <div class='flexmls_connect__right'>\n";
        if ($count_videos > 0) {
    			echo "		  <button class='video_click' rel='v{$rand}-{$sf['ListingKey']}'>Videos ({$count_videos})</button>\n";
    		  if ($count_tours > 0) {
    		    echo " &nbsp;|&nbsp; ";
    	    }
    		}
    		if ($count_tours > 0) {
    			echo "		  <button class='tour_click' rel='t{$rand}-{$sf['ListingKey']}'>Virtual Tours ({$count_tours})</button>\n";
    		}
		  echo "    </div>\n";
		  
		  // Share and Print buttons
			echo "		  <button class='print_click' onclick='flexmls_connect.print(this);'><img src='{$fmc_plugin_url}/images/print.png'align='absmiddle' /> Print</button>\n";
			
		echo "  </div>\n";
		
		echo "  <hr class='flexmls_connect__sr_divider'>\n";
		
		// hidden divs for tours and videos (colorboxes)
		echo "  <div class='flexmls_connect__hidden2'></div>";
		echo "  <div class='flexmls_connect__hidden3'></div>";
		
		// Photos
		if (count($sf['Photos']) >= 1) {
		  $main_photo_url = $sf['Photos'][0]['UriLarge'];
		  
		  echo "  <div class='flexmls_connect__photos'>\n";
		  echo "    <div class='flexmls_connect__photo_container'>\n";
      echo "      <img src='{$main_photo_url}' class='flexmls_connect__main_image' onload='flexmls_connect.resizeMainPhoto(this)' title='{$one_line_address} - MLS# {$sf['ListingId']}' />\n";
		  echo "    </div>\n";

    	// photo pager
		  echo "    <div class='flexmls_connect__photo_pager'>\n";
		  echo "      <div class='flexmls_connect__photo_switcher'>\n";
    	echo "        <button><img src='{$fmc_plugin_url}/images/left.png' /></button>\n";
    	echo "        &nbsp; <span>1</span> / {$count_photos} &nbsp;\n";
    	echo "        <button><img src='{$fmc_plugin_url}/images/right.png' /></button>\n";
      echo "      </div>\n";
    	
    	
    	// colobox photo popup
    	echo "      <button class='photo_click'>View Larger Photos ({$count_photos})</button>\n";
      echo "    </div>";
      
      // filmstrip
    	echo "    <div class='flexmls_connect__filmstrip'>\n";
    		if ($count_photos > 0) {
    		  $ind = 0;
    			foreach ($sf['Photos'] as $p) {
    				echo "<img src='{$p['UriThumb']}' ind='{$ind}' fullsrc='{$p['UriLarge']}' alt='".htmlspecialchars($p['Caption'], ENT_QUOTES)."' title='".htmlspecialchars($p['Caption'], ENT_QUOTES)."' width='65' /> \n";
    			  $ind++;
    			}
    		}
  		echo "    </div>";
		  echo "  </div>";
		  
		  // hidden div for colorbox
		  echo "    <div class='flexmls_connect__hidden'>\n";
			  if ($count_photos > 0) {
    			foreach ($sf['Photos'] as $p) {
    				echo "<a href='{$p['UriLarge']}' data-connect-ajax='true' rel='p{$rand}-{$sf['ListingKey']}' title='".htmlspecialchars($p['Caption'], ENT_QUOTES)."'></a>\n";
    			}
    		}
			echo "    </div>\n";
		}
		
		
		// Open Houses
		if ($count_openhouses > 0) {
			$this_o = $sf['OpenHouses'][0];
			echo "<div class='flexmls_connect__sr_openhouse'><em>Open House</em> (". $this_o['Date'] ." - ". $this_o['StartTime'] ." - ". $this_o['EndTime'] .")</div>\n\n";
		}

		
		// Property Dscription
		if ( flexmlsConnect::is_not_blank_or_restricted($sf['PublicRemarks']) ) {
			echo "<br><b>Property Description</b><br>\n";
			echo $sf['PublicRemarks'];
			echo "<br><br>\n\n";
		}
		
		
		// Tabs
		echo "<div class='flexmls_connect__tab_div'>";
		  echo "<div class='flexmls_connect__tab active' group='flexmls_connect__detail_group'>Details</div>";
			if ($sf['Latitude'] && $sf['Longitude'] && $sf['Latitude'] != "********" && $sf['Longitude'] != "********")
		    echo "<div class='flexmls_connect__tab' group='flexmls_connect__map_group'>Maps</div>";		  
		echo "</div>";
		
		
		// build the Details portion of the page
		echo "<div class='flexmls_connect__tab_group' id='flexmls_connect__detail_group'>";
		$property_detail_values = array("Summary" => array());
		
		foreach ($mls_property_detail_fields as $k => $v) {
			if ( array_key_exists($v, $sf) and flexmlsConnect::is_not_blank_or_restricted($sf[$v]) ) {
				$this_val = $sf[$v];
				
				if ($v == "PropertyType") {
					$this_val = flexmlsConnect::nice_property_type_label($this_val);
				}
				
				$property_detail_values["Summary"][] = "<b>{$k}:</b>&nbsp; {$this_val}";
			}
		}
		
		foreach ($record['CustomFields'][0]['Main'] as $one) {
			foreach ($one as $property_headline_description => $two) {
				foreach ($two as $three) {
					foreach ($three as $k => $v) {
						if ( flexmlsConnect::is_not_blank_or_restricted($v) ) {
							$this_val = $v;

							$property_detail_values[$property_headline_description][] = "<b>{$k}:</b> {$this_val}";
						}
					}
				}
			}
		}
		
		// render the results now
		foreach ($property_detail_values as $k => $v) {
			echo "<div class='flexmls_connect__detail_header'>{$k}</div>\n";
			echo "<table width='100%'>\n";

			$details_per_column = ceil( count($v) / 2);
			$details_count = 0;
			$row_count = 0;
			
			foreach ($v as $value) {
				$details_count++;
				
				if ($details_count === 1) {
					$row_count++;
					echo "	<tr " . ($row_count % 2 == 1 ? "" : "class='flexmls_connect__sr_zebra_on'") . ">\n";
				}

				$left_val = $v[$left_det];
				$right_val = (array_key_exists((int) $right_det, $v)) ? $v[$right_det] : "";

				echo "		<td width='50%' valign='top'>{$value}</td>\n";
				
				if ($details_count === 2) {
					echo "	</tr>\n";
					$details_count = 0;
				}
			}
			
			if ($details_count === 1) {
				// details ended earlier without last cell
				echo "		<td>&nbsp;</td>\n";
				echo "	</tr>\n";
			}

			echo "</table>\n";
			echo "<br><br>\n\n";
		}
		
		// build the Property Featured portion of the page
		$property_features_values = array();
		
		foreach ($record['CustomFields'][0]['Details'] as $one) {
			foreach ($one as $k => $two) {
				$this_feature = array();
				foreach ($two as $three) {
					foreach ($three as $name => $value) {
						if ($value === true) {
							$this_feature[] = $name;
						}
					}
				}
				if ( count($this_feature) > 0) {
					$property_features_values[] = "<b>{$k}:</b> ". implode("; ", $this_feature);
				}
			}
		}
		
		echo "<div class='flexmls_connect__detail_header'>Property Features</div>\n";
		echo "<table width='100%'>\n";
		$index = 0;
		foreach ($property_features_values as $v) {
			echo "	<tr " . ($index % 2 == 1 ? "class='flexmls_connect__sr_zebra_on'" : "") . "><td>{$v}</td></tr>\n";
			$index++;
		}
		echo "</table>\n";
		echo "<br><br>\n\n";
		
		
		// build the Room Information portion of the page
		$room_information_values = array();
		if ( count($sf['Rooms'] > 0) ) {
			$verified_room_count = 0;
			
			foreach ($sf['Rooms'] as $r) {
				$this_name = null;
				$this_level = null;
				
				foreach ($r['Fields'] as $rf) {
					foreach ($rf as $rfk => $rfv) {
						if ($rfk == "Room Name") {
							$this_name = $rfv;
						}
						if ($rfk == "Room Level") {
							$this_level = $rfv;
						}
					}
				}
				
				if ($this_name != null and $this_level != null) {
					$room_information_values[] = array(
					    'name' => $this_name, 
					    'level' => $this_level
					);
					$verified_room_count++;
				}
			}
			
			$rooms_per_column = ceil($verified_room_count / 2);
			
			if ($verified_room_count > 0) {
				echo "<div class='flexmls_connect__detail_header'>Room Information</div>\n";
				echo "<table width='100%'>\n";
				echo "	<tr><td width='25%'><b>Room Name</b></td><td width='25%'><b>Room Level</b></td><td width='25%'><b>Room Name</b></td><td width='25%'><b>Room Level</b></td></tr>\n";
			
				for ($i = 0; $i < $rooms_per_column; $i++) {
					$left_room = $i;
					$right_room = $i + $rooms_per_column;
					
					$left_name = $room_information_values[$left_room]['name'];
					$left_level = $room_information_values[$left_room]['level'];
					
					$right_name = (array_key_exists((int) $right_room, $room_information_values)) ? $room_information_values[$right_room]['name'] : "";
					$right_level = (array_key_exists((int) $right_room, $room_information_values)) ? $room_information_values[$right_room]['level'] : "";

					echo "	<tr " . ($i % 2 == 0 ? "class='flexmls_connect__sr_zebra_on'" : "") . ">\n";
					echo "		<td>{$left_name}</td><td>{$left_level}</td>\n";
					echo "		<td>{$right_name}</td><td>{$right_level}</td>\n";
					echo "	</tr>\n";
				}

				echo "</table>\n";
				
			}
			
			echo "</div>\n";
			
			
			// map details, if present
			if ($sf['Latitude'] && $sf['Longitude'] && $sf['Latitude'] != "********" && $sf['Longitude'] != "********") {
			  echo "<div class='flexmls_connect__tab_group' id='flexmls_connect__map_group'>
                <div id='flexmls_connect__map_canvas' latitude='{$sf['Latitude']}' longitude='{$sf['Longitude']}'></div>
			          <script type='text/javascript' src='http://maps.googleapis.com/maps/api/js?sensor=false'></script>\n
    		      </div>";
			}
			
			
			echo "  <hr class='flexmls_connect__sr_divider'>\n";
  		
  		// disclaimer
			echo "	<div class='flexmls_connect__idx_disclosure_text'>";
			if (flexmlsConnect::is_not_blank_or_restricted($sf['ListOfficeName'])) {
				echo "<p>Listing Office: {$sf['ListOfficeName']}</p>\n";
			}
			echo flexmlsConnect::get_big_idx_disclosure_text();
			echo "</div>\n\n";
		}
		
    // end
    echo "</div>\n";
    
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
	
	function has_previous_listing() {
		return ( flexmlsConnect::wp_input_get('p') == 'y' ) ? true : false;
	}
	
	function has_next_listing() {
		return ( flexmlsConnect::wp_input_get('n') == 'y' ) ? true : false;
	}
	
	function browse_next_url() {
		$link_criteria = $this->search_criteria;
		$link_criteria['id'] = $this->listing_data['StandardFields']['ListingId'];
		return flexmlsConnect::make_nice_tag_url('next-listing', $link_criteria);
	}
	
	function browse_previous_url() {
		$link_criteria = $this->search_criteria;
		$link_criteria['id'] = $this->listing_data['StandardFields']['ListingId'];
		return flexmlsConnect::make_nice_tag_url('prev-listing', $link_criteria);
	}


}
