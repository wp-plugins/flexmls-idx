<?php

class flexmlsConnectPageCore {
	
	public $input_data = array();
	public $input_source = 'page';
	
	
	protected function parse_search_parameters_into_api_request() {
		global $fmc_api;
	
		// pull StandardFields from the API to verify searchability prior to searching
		$result = $fmc_api->GetStandardFields();
		$this->standard_fields = $result[0];

		$searchable_fields = array();
		foreach ($this->standard_fields as $k => $v) {
			if ($v['Searchable']) {
				$searchable_fields[] = $k;
			}
		}

		// add in special fields
		$searchable_fields[] = 'SavedSearch';


		// start catching and building API search criteria
		$search_criteria = array();

		$catch_fields = array(
				array(
						'input' => 'SavedSearch',
						'operator' => 'Eq',
						'field' => 'SavedSearch'
				),
				array(
						'input' => 'ListingId',
						'operator' => 'Eq',
						'field' => 'ListingId',
						'allow_or' => true
				),
				array(
						'input' => 'PropertyType',
						'operator' => 'Eq',
						'field' => 'PropertyType',
						'allow_or' => true
				),
				array(
						'input' => 'MapOverlay',
						'operator' => 'Eq',
						'field' => 'MapOverlay'
				),
				array(
						'input' => 'City',
						'operator' => 'Eq',
						'field' => 'City',
						'allow_or' => true
				),
				array(
						'input' => 'StateOrProvince',
						'operator' => 'Eq',
						'field' => 'StateOrProvince',
						'allow_or' => true
				),
				array(
						'input' => 'CountyOrParish',
						'operator' => 'Eq',
						'field' => 'CountyOrParish',
						'allow_or' => true
				),
				array(
						'input' => 'PostalCode',
						'operator' => 'Eq',
						'field' => 'PostalCode',
						'allow_or' => true
				),
				array(
						'input' => 'SubdivisionName',
						'operator' => 'Eq',
						'field' => 'SubdivisionName',
						'allow_or' => true
				),
				array(
						'input' => 'MinBeds',
						'operator' => 'Ge',
						'field' => 'BedsTotal'
				),
				array(
						'input' => 'MaxBeds',
						'operator' => 'Le',
						'field' => 'BedsTotal'
				),
				array(
						'input' => 'MinBaths',
						'operator' => 'Ge',
						'field' => 'BathsTotal'
				),
				array(
						'input' => 'MaxBaths',
						'operator' => 'Le',
						'field' => 'BathsTotal'
				),
				array(
						'input' => 'MinPrice',
						'operator' => 'Ge',
						'field' => 'ListPrice'
				),
				array(
						'input' => 'MaxPrice',
						'operator' => 'Le',
						'field' => 'ListPrice'
				),
				array(
						'input' => 'MinSqFt',
						'operator' => 'Ge',
						'field' => 'BuildingAreaTotal'
				),
				array(
						'input' => 'MaxSqFt',
						'operator' => 'Le',
						'field' => 'BuildingAreaTotal'
				),
				array(
						'input' => 'MinYear',
						'operator' => 'Ge',
						'field' => 'YearBuilt'
				),
				array(
						'input' => 'MaxYear',
						'operator' => 'Le',
						'field' => 'YearBuilt'
				),
				array(
						'input' => 'MLSAreaMinor',
						'operator' => 'Eq',
						'field' => 'MLSAreaMinor'
				),
		);
		
		$possible_api_parameters = array('HotSheet','OpenHouses');


		$cleaned_raw_criteria = array();

		// used to track how many field values are provided for each field
		$field_value_count = array();
		
		// pluck out values from GET or POST
		foreach ($catch_fields as $f) {
			$value = $this->fetch_input_data($f['input']);
			if ($value === null or $value == '') {
				// not provided
				continue;
			}

			if ( !in_array($f['field'], $searchable_fields) ) {
				// field would usually be OK but it's not searchable for this user
				continue;
			}

			$field_value_count[ $f['field'] ] = 0;

			$cleaned_raw_criteria[ $f['input'] ] = $value;

			if ( array_key_exists($f['field'], $this->standard_fields) ) {
				$type = $this->standard_fields[ $f['field'] ][ 'Type' ];
			}
			else {
				$type = 'Character';
			}

			if ( array_key_exists('allow_or', $f) and $f['allow_or']) {
				$this_field = array();

				$condition = '(';
				$f_values = explode(',', $value);
				foreach ($f_values as $fv) {
					$field_value_count[ $f['field'] ]++;

					$formatted_value = flexmlsConnect::make_api_formatted_value($fv, $type);
					if ($formatted_value === null) {
						continue;
					}
					$this_field[] = $f['field'] .' '. $f['operator'] .' '. $formatted_value;
				}
				$condition .= implode(" Or ", $this_field);
				$condition .= ')';
			}
			else {
				$field_value_count[ $f['field'] ]++;

				$formatted_value = flexmlsConnect::make_api_formatted_value($value, $type);
				if ($formatted_value === null) {
					continue;
				}
				$condition = $f['field'] .' '. $f['operator'] .' '. $formatted_value;
			}

			$search_criteria[] = $condition;
		}
		
		// check for ListAgentId
		$list_agent_id = $this->fetch_input_data('ListAgentId');
		if ($list_agent_id != null) {
			$cleaned_raw_criteria['ListAgentId'] = $list_agent_id;
			$search_criteria[] = "(ListAgentId Eq '{$list_agent_id}' Or CoListAgentId Eq '{$list_agent_id}')";
		}

		$this->field_value_count = $field_value_count;

		// pull this directly off of the page input rather than $this->fetch_input_data
		$pg = (flexmlsConnect::wp_input_get_post('pg')) ? flexmlsConnect::wp_input_get_post('pg') : 1;
		$cleaned_raw_criteria['pg'] = $pg;

		$context = $this->fetch_input_data('My');
		if (!empty($context)) {
			$cleaned_raw_criteria['My'] = $context;
		}
		
		$desired_orderby = $this->fetch_input_data('OrderBy');
		$orderby = ( !empty($desired_orderby) ) ? $desired_orderby : "-ListPrice";
		
		$desired_limit = $this->fetch_input_data('Limit');
		$limit = ($desired_limit) ? $desired_limit : 10;
		if ($limit != 10) {
			$cleaned_raw_criteria['Limit'] = $limit;
		}
		
		$params = array(
				'_filter' => implode(" And ", $search_criteria),
				'_pagination' => 1,
				'_limit' => $limit,
				'_page' => $pg,
				'_expand' => 'Photos,Videos,VirtualTours,OpenHouses'
		);
		
		if ($orderby !== null and $orderby != 'natural') {
			$params['_orderby'] = $orderby;
		}
		$cleaned_raw_criteria['OrderBy'] = $orderby;
		
		foreach ($possible_api_parameters as $p) {
			$v = $this->fetch_input_data($p);
			if ($v != null) {
				$params[$p] = $v;
				$cleaned_raw_criteria[$p] = $v;
			}
		}
		
		return array($params, $cleaned_raw_criteria, $context);
		
	}
	
	
	
	function fetch_input_data($key) {
	
		if ($this->input_source == 'shortcode') {
			// pull values from $this->input_data rather than $_REQUEST
			return ( array_key_exists($key, $this->input_data) ) ? $this->input_data[$key] : null;
		}
		else {
			return flexmlsConnect::wp_input_get_post($key);
		}
		
	}
	
	
	
	function get_browse_redirects() {
		global $fmc_api;
		
		$last_page = flexmlsConnect::wp_input_get('pg');
		
		if ($last_page > 1) {
			$this->build_browse_list( $last_page - 1 );
		}
		
		$this->build_browse_list( flexmlsConnect::wp_input_get('pg') );
		
		if ($no_more == false) {
			$this->build_browse_list( flexmlsConnect::wp_input_get('pg') + 1 );
		}
		
		
		$last_listing = flexmlsConnect::wp_input_get('id');
		$this_listings_index = null;
		
		foreach ($this->browse_list as $bl) {
			if ($bl['ListingId'] == $last_listing) {
				$this_listings_index = $bl['Index'];
			}
		}
		
		if ( array_key_exists((string) $this_listings_index-1, $this->browse_list) ) {
			$previous_listing_url = $this->browse_list[(string) $this_listings_index-1]['Uri'];
		}
		if ( array_key_exists((string) $this_listings_index+1, $this->browse_list) ) {
			$next_listing_url = $this->browse_list[(string) $this_listings_index+1]['Uri'];
		}
		
		return array($previous_listing_url, $next_listing_url);
		
	}
	
	
	function build_browse_list($pg) {
		global $fmc_api;
		
		// parse passed parameters for browsing capability
		list($params, $cleaned_raw_criteria, $context) = $this->parse_search_parameters_into_api_request();
		
		// cut out pieces we don't want
		$modified_params = $params;
		$modified_raw_criteria = $cleaned_raw_criteria;
		
		unset($modified_params['_expand']);
		$modified_params['_page'] = $pg;
		$modified_raw_criteria['pg'] = $pg;
				
		if ($context == "listings") {
			$results = $fmc_api->GetMyListings($modified_params);
		}
		elseif ($context == "office") {
			$results = $fmc_api->GetOfficeListings($modified_params);
		}
		elseif ($context == "company") {
			$results = $fmc_api->GetCompanyListings($modified_params);
		}
		else {
			$results = $fmc_api->GetListings($modified_params);
		}
		
		$result_count = 0;
		foreach ($results as $record) {
			$result_count++;
			
			$this_result_overall_index = ($fmc_api->page_size * ($fmc_api->current_page - 1)) + $result_count;
			
			$link_to_details_criteria = $modified_raw_criteria;
			// figure out if there's a previous listing
			$link_to_details_criteria['p'] = ($this_result_overall_index != 1) ? 'y' : 'n';
			
			// figure out if there's a next listing possible
			$link_to_details_criteria['n'] = ( $this_result_overall_index < $fmc_api->last_count ) ? 'y' : 'n';
			
			if ($link_to_details_criteria['n'] == 'n') {
				$this->no_more = true;
			}
				
			$this->browse_list[(string) $this_result_overall_index] = array(
			    'Index' => $this_result_overall_index,
			    'Id' => $record['Id'],
			    'ListingId' => $record['StandardFields']['ListingId'],
			    'Uri' => flexmlsConnect::make_nice_address_url($record, $link_to_details_criteria)
			);
			
		}
		
	}
		
	
}
