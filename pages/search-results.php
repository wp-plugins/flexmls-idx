<?php

class flexmlsConnectPageSearchResults extends flexmlsConnectPageCore {

	protected $search_criteria;
	protected $field_value_count;
	protected $search_data;
	protected $standard_fields;
	protected $total_pages;
	protected $current_page;
	protected $total_rows;
	
	

	function pre_tasks($tag) {
		global $fmc_special_page_caught;
		global $fmc_api;
		global $fmc_plugin_url;
		
		list($params, $cleaned_raw_criteria, $context) = $this->parse_search_parameters_into_api_request();
		
		$this->search_criteria = $cleaned_raw_criteria;
		
		
		if ($context == "listings") {
			$results = $fmc_api->GetMyListings($params);
		}
		elseif ($context == "office") {
			$results = $fmc_api->GetOfficeListings($params);
		}
		elseif ($context == "company") {
			$results = $fmc_api->GetCompanyListings($params);
		}
		else {
			$results = $fmc_api->GetListings($params);
		}
		

		$this->search_data = $results;
		$this->total_pages = $fmc_api->total_pages;
		$this->current_page = $fmc_api->current_page;
		$this->total_rows = $fmc_api->last_count;
		$this->page_size = $fmc_api->page_size;


		$fmc_special_page_caught['type'] = "search-results";
		$fmc_special_page_caught['page-title'] = "Property Search";
		$fmc_special_page_caught['post-title'] = "Property Search";
		$fmc_special_page_caught['page-url'] = flexmlsConnect::make_nice_tag_url('search') .'?'. $_SERVER['QUERY_STRING'];
		
	}


	function generate_page($from_shortcode = false) {
		global $fmc_api;
		global $fmc_special_page_caught;
		global $fmc_plugin_url;
		global $fmc_search_results_loaded;
		
		if ($fmc_search_results_loaded) {
			return false;
		}
		$fmc_search_results_loaded = true;
    
		ob_start();
		
		
		$primary_details = array(
				'Property Type' => 'PropertyType',
				'# of Bedrooms' => 'BedsTotal',
				'# of Bathrooms' => 'BathsTotal',
				'Square Footage' => 'BuildingAreaTotal',
				'Year Built' => 'YearBuilt',
				'Area' => 'MLSAreaMinor',
				'Subdivision' => 'SubdivisionName',
				'Description' => 'PublicRemarks'
		);

		$exclude_property_type = false;
		$exclude_county = false;
		$exclude_area = false;

		if ( array_key_exists('PropertyType', $this->field_value_count) && $this->field_value_count['PropertyType'] == 1) {
			$exclude_property_type = true;
		}

		if ( array_key_exists('MLSAreaMinor', $this->field_value_count) && $this->field_value_count['MLSAreaMinor'] == 1) {
			$exclude_area = true;
		}

		if ( array_key_exists('CountyOrParish', $this->field_value_count) && $this->field_value_count['CountyOrParish'] == 1) {
			$exclude_county = true;
		}

		echo "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";
		
		echo "<div class='flexmls_connect__page_content'>\n";
    
  	echo "	<div class='flexmls_connect__sr_matches'>\n";
		echo "		<div class='flexmls_connect__sr_matches_count'>" . number_format($this->total_rows, 0, '.', ',') ."</div>\n";
		echo "		matches found\n";
		echo "	</div><!-- matches -->\n\n\n";

		// echo "	<div class='flexmls_connect__sr_email_updates'>\n";
		// echo "		<a href='#'>Get Email Updates for New Listings</a>\n";
		// echo "	</div><!-- email_updates -->\n\n";
		
		echo "<hr class='flexmls_connect__sr_divider'>\n\n";
		
		$mls_custom_idx_badge = flexmlsConnect::mls_custom_idx_logo();
		$office_name_required_in_results = flexmlsConnect::mls_requires_office_name_in_search_results();
		
		$result_count = 0;
		
		foreach ($this->search_data as $record) {
			$result_count++;
			
			// Establish some variables
			$listing_address = flexmlsConnect::format_listing_street_address($record);
			$first_line_address = htmlspecialchars($listing_address[0]);
			$second_line_address = htmlspecialchars($listing_address[1]);
			$one_line_address = htmlspecialchars($listing_address[2]);
			$link_to_details_criteria = $this->search_criteria;
			
			$this_result_overall_index = ($this->page_size * ($this->current_page - 1)) + $result_count;

			// figure out if there's a previous listing
			$link_to_details_criteria['p'] = ($this_result_overall_index != 1) ? 'y' : 'n';
			
			// figure out if there's a next listing possible
			$link_to_details_criteria['n'] = ( $this_result_overall_index < $this->total_rows ) ? 'y' : 'n';
				
			$link_to_details = flexmlsConnect::make_nice_address_url($record, $link_to_details_criteria);
			$sf =& $record['StandardFields'];
			$rand = mt_rand();
			
			
			// Container
			echo "	<div class='flexmls_connect__sr_result' title='{$one_line_address} - MLS# {$sf['ListingId']}' link='{$link_to_details}'>\n";
			
      
      // Price
      echo "    <div class='flexmls_connect__sr_price'>\n";
			echo '		  $'. flexmlsConnect::gentle_price_rounding($sf['ListPrice']) . "\n";
      echo "    </div>\n";
      
      
      // Address
            
      echo "    <div class='flexmls_connect__sr_address'>\n";
			echo "		  <a href='{$link_to_details}' title='Click for more details'>". $first_line_address ."<br />". $second_line_address ."</a>\n";
      echo "    </div>\n";
      
      
      // Image
      if ( count($sf['Photos']) >= 1 ) {
				$main_photo_url = $sf['Photos'][0]['Uri300'];
				$main_photo_urilarge = $sf['Photos'][0]['UriLarge'];
				$caption = htmlspecialchars($sf['Photos'][0]['Caption']);
			}
			else {
				$main_photo_url = "{$fmc_plugin_url}/images/nophoto.gif";
				$main_photo_urilarge = "{$fmc_plugin_url}/images/nophoto.gif";
				$caption = "";
			}
			echo "		<a class='photo' href='{$main_photo_urilarge}' rel='{$rand}-{$sf['ListingKey']}' title='{$caption}'>\n";
			echo "      <img src='{$main_photo_url}' width='300' onerror='this.src=\"{$fmc_plugin_url}/images/nophoto.gif\"' />\n";
			echo "    </a>\n";
			echo "    <div class='flexmls_connect__hidden'></div>";
			echo "    <div class='flexmls_connect__hidden2'></div>";
			echo "    <div class='flexmls_connect__hidden3'></div>";
      
      
      // Actions
      // echo "    <div class='flexmls_connect__sr_actions'>\n";
  		// echo "      <button class='colored'>Mark as Favorite</button>\n";
  		// echo "      <button>Questions?</button>\n";
  		// echo "    </div>\n";
      
      
      // Open House
      if ( count($sf['OpenHouses']) >= 1) {
        echo "    <div class='flexmls_connect__sr_openhouse'>\n";
  			echo "		  <em>Open House</em> ({$sf['OpenHouses'][0]['Date']} - {$sf['OpenHouses'][0]['StartTime']})\n";
        echo "    </div>\n";
      }
      
      
      // Details table
      echo "		<div class='flexmls_connect__sr_listing_facts_container'>\n";
      echo "    <table class='flexmls_connect__sr_listing_facts' cellspacing='0'>\n";
			$detail_count = 0;
			foreach ($primary_details as $k => $v) {
				if ($v == 'PropertyType' and $exclude_property_type) {
					continue;
				}
				if ($v == 'MLSAreaMinor' and $exclude_area) {
					continue;
				}
				if ($v == 'CountyOrParish' and $exclude_county) {
					continue;
				}
				
				$zebra = (flexmlsConnect::is_odd($detail_count)) ? 'on' : 'off';

				if ( flexmlsConnect::is_not_blank_or_restricted( $sf[$v] ) ) {
					$this_val = $sf[$v];

					if ($v == "PropertyType") {
						$this_val = flexmlsConnect::nice_property_type_label($this_val);
					}
					
					if ($v == "PublicRemarks") {
						$this_val = substr($this_val, 0, 75) . "...";
					}

					$detail_count++;
					echo "			<tr class='flexmls_connect__sr_zebra_{$zebra}'><td><b>{$k}</b>:</td><td>{$this_val}</td></tr>\n";
				}

				if ($detail_count == 5) {
					break;
				}
			}
			
			
			// place IDX disclosure in the table to prevent wrapping when small images are included
			echo "			<tr><td colspan='2' class='flexmls_connect__sr_idx'>";
			if ( flexmlsConnect::get_office_id() != $sf['ListOfficeId'] ) {
				if ($mls_custom_idx_badge) {
					echo "			<img src='{$mls_custom_idx_badge}' class='flexmls_connect__badge_image' title='{$sf['ListOfficeName']}' />\n";
				}
				else {
					echo "			<span class='flexmls_connect__badge' title='{$sf['ListOfficeName']}'>IDX</span>\n";
				}
			}
			if ( flexmlsConnect::mls_requires_office_name_in_search_results() or flexmlsConnect::get_office_id() == $sf['ListOfficeId']) {
				echo "			<span class='flexmls_connect__sr_idx_badge_office'>Listing Office: {$sf['ListOfficeName']}</span>\n";
			}
  	  echo "			</td></tr>\n";
		
			
			// end table
			echo "		</table></div>\n\n";
      
      
      // Detail Links
      $count_photos = count($sf['Photos']);
			$count_videos = count($sf['Videos']);
			$count_tours = count($sf['VirtualTours']);      
      echo "    <div class='flexmls_connect__sr_details'>\n";
			echo "		  <button href='{$link_to_details}'>View Details</button>\n";
			if ($count_photos > 0) {
			  echo "		  <a class='photo_click'>View Photos ({$count_photos})</a>\n";
			  if ($count_videos > 0 || $count_tours > 0) {
			    echo " &nbsp;|&nbsp; ";
		    }
			}
			if ($count_videos > 0) {
  			echo "		  <a class='video_click' rel='v{$rand}-{$sf['ListingKey']}'>Videos ({$count_videos})</a>\n";
			  if ($count_tours > 0) {
			    echo " &nbsp;|&nbsp; ";
		    }
			}
			if ($count_tours > 0) {
  			echo "		  <a class='tour_click' rel='t{$rand}-{$sf['ListingKey']}'>Virtual Tours ({$count_tours})</a>\n";
			}
      echo "    </div>\n";
			echo "	</div><!-- result -->\n\n";
		}

		echo "<hr class='flexmls_connect__sr_divider'>\n\n";

		if ($this->total_pages != 1) {
			echo $this->pagination($this->current_page, $this->total_pages) . "\n";
		}
		
		echo "	<div class='flexmls_connect__idx_disclosure_text flexmls_connect__disclaimer_text'>";
		echo flexmlsConnect::get_big_idx_disclosure_text();
		echo "</div>\n\n";
		
		echo "</div><!-- page_content -->\n";
		
		echo "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";
		
//		echo "<br><br><br><br>\n\n";

		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}


	function pagination($current_page, $total_pages) {

		$jump_after_first = false;
		$jump_before_last = false;

		$tolerance = 5;

		$return = "	<div class='flexmls_connect__sr_pagination'>\n";

		if ($current_page != 1) {
			$return .= "		<button href='". $this->make_pagination_link($current_page - 1) ."'>Previous</button>\n";
		}

		if ( ($current_page - $tolerance - 1) > 1 ) {
			$jump_after_first = true;
		}

		if ( $total_pages > ($current_page + $tolerance + 1) ) {
			$jump_before_last = true;
		}


		for ($i = 1; $i <= $total_pages; $i++) {

			if ($i == $total_pages and $jump_before_last) {
				$return .= "		 ... \n";
			}

			$is_current = ($i == $current_page) ? true : false;
			if ($i != 1 and $i != $total_pages) {
				if ( $i < ($current_page - $tolerance) or $i > ($current_page + $tolerance) ) {
					continue;
				}
			}

			if ($is_current) {
				$return .= "		<span>{$i}</span> \n";
			}
			else {
				$return .= "		<a href='". $this->make_pagination_link($i) ."'>{$i}</a> \n";
			}

			if ($i == 1 and $jump_after_first) {
				$return .= "		 ... \n";
			}

		}
		

		if ($current_page != $total_pages) {
			$return .= "		 <button href='". $this->make_pagination_link($current_page + 1) ."'>Next</button>\n";
		}
		
		$return .= "	</div><!-- pagination -->\n";

		return $return;

	}


	function make_pagination_link($page) {
		
		if ($this->input_source == 'shortcode') {
			// when the page is generated via shortcode, forget everything else.
			// the link will only append a "pg=X" value back to the original URL so visitors stay 
			// in the same place but are looking at the next page of listings
			if ( flexmlsConnect::generate_nice_urls() ) {
				return get_permalink() .'?pg='. $page;
			}
			else {
				// permalinks is turned off so append 'pg' with the rest of WP's query string
				return get_permalink() .'&pg='. $page;
			}
		}
		else {
			$page_conditions = $this->search_criteria;
			$page_conditions['pg'] = $page;
			return flexmlsConnect::make_nice_tag_url('search', $page_conditions);
		}
		
	}
	


}
