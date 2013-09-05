<?php

class flexmlsConnectPageCore {

	public $input_data = array();
	public $input_source = 'page';
	protected $api;

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
		$searchable_fields[] = 'StreetAddress';
		$searchable_fields[] = 'MapOverlay';
		$searchable_fields[] = 'ListingCart';

		// start catching and building API search criteria
		$search_criteria = array();

		$catch_fields = array(
				array(
				    'input' => 'ListingCart',
				    'operator' => 'Eq',
				    'field' => 'ListingCart'
				),
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
						'input' => 'StreetAddress',
						'operator' => 'Eq',
						'field' => 'StreetAddress',
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
						'field' => 'MLSAreaMinor',
						'allow_or' => true
				),
		);

		$possible_api_parameters = array('HotSheet','OpenHouses');

		$cleaned_raw_criteria = array();

		// used to track how many field values are provided for each field
		$field_value_count = array();

		// pluck out values from GET or POST
		foreach ($catch_fields as $f) {

			if ($f['field'] == "BathsTotal") {
				if (array_key_exists('BathsTotal', $this->standard_fields)) {
					if (array_key_exists('MlsVisible', $this->standard_fields['BathsTotal']) and empty($this->standard_fields['BathsTotal']['MlsVisible'])) {
						$f['field'] = "BathsFull";
					}
				}
			}

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

    /*
    //attempt at cross mls for WP-139, removing for now
    $db2only = false;
    $subdivused = false;
    $postalused = false;
    $stateused = false;
		foreach ($search_criteria as $sc) {
		  if (strrpos($sc, "MapOverlay")!==false ||
		      strrpos($sc, "ListingCart")!==false ||
		      strrpos($sc, "SavedSearch")!==false ||
		      strrpos($sc, "IdxParam")!==false ||
		      strrpos($sc, "EmailLink")!==false) {
		     $db2only = true;
		  }
		  if (strrpos($sc, "SubdivisionName")!==false) {
		    $subdivused = true;
		  }
		  if (strrpos($sc, "StateOrProvince")!==false) {
		    $stateused = true;
		  }
		  if (strrpos($sc, "PostalCode")!==false) {
		    $postalused = true;
		  }
    }
		//print_r($search_criteria);
		//echo "<BR>";
		//echo "<BR>";

    $mlss = $fmc_api->GetStandardField('MlsId');
    $mlss = $mlss[0]['MlsId']['FieldList'];

    //print_r($mlss);
    //echo "<BR>";
    //echo "<BR>";
    $mlscond = "(MlsId Eq ";
    $dum = 0;
		foreach ($mlss as $m) {
			if ($m["Value"] != null) {
        $mlssd = $fmc_api->GetStandardFieldByMls('SubdivisionName',$m["Value"]);
        $mlssz = $fmc_api->GetStandardFieldByMls('PostalCode',$m["Value"]);
        $mlssp = $fmc_api->GetStandardFieldByMls('StateOrProvince',$m["Value"]);
			  if (($subdivused && $mlssd[0]['SubdivisionName']['Searchable']==1 || !$subdivused) &&
			      ($postalused && $mlssz[0]['PostalCode']['Searchable']==1 || !$postalused) &&
			      ($stateused && $mlssp[0]['StateOrProvince']['Searchable']==1 || !$stateused)
			     ) {
            if ($dum!=0)
    			    $mlscond .= ",";
    			  $mlscond .= "'".$m["Value"]."'";
    			  $dum++;
  			}
			}
		}
		$mlscond .= ")";

		//print_r($mlscond);
		//echo "<BR>";
		//echo "<BR>";

		if ($mlscond!="(MlsId Eq )" && !$db2only) {
		  $search_criteria[] = $mlscond;
		}

		//print_r($search_criteria);
		//echo "<BR>";
		//echo "<BR>";
		*/


		if ($this->fetch_input_data('OnMarketDate') != null)
			$search_criteria[]= "OnMarketDate Gt " .$this->fetch_input_data('OnMarketDate');

		if ($this->fetch_input_data('PriceChangeTimestamp') != null)
			$search_criteria[]= "PriceChangeTimestamp Gt " .$this->fetch_input_data('PriceChangeTimestamp');

		if ($this->fetch_input_data('StatusChangeTimestamp') != null)
			$search_criteria[]= "StatusChangeTimestamp Gt " .$this->fetch_input_data('StatusChangeTimestamp');

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

		$desired_orderby = flexmlsConnect::wp_input_get_post('OrderBy') ? flexmlsConnect::wp_input_get_post('OrderBy') : $this->fetch_input_data('OrderBy');

		$orderby = ( !empty($desired_orderby) ) ? $desired_orderby : "-ListPrice";

		$desired_limit = $this->fetch_input_data('Limit');
		$limit = ($desired_limit) ? $desired_limit : 10;
		if ($limit != 10) {
			$cleaned_raw_criteria['Limit'] = $limit;
		}

		$params = array(
				'_filter' => implode(" And ", $search_criteria),
				'_select' => 'MlsId,ListingId,ListPrice,Photos,ListingKey,OpenHouses,ListOfficeId,ListOfficeName,ListAgentFirstName,ListAgentLastName,Videos,VirtualTours,PropertyType,BedsTotal,BathsTotal,BuildingAreaTotal,YearBuilt,MLSAreaMinor,SubdivisionName,PublicRemarks,StreetNumber,StreetDirPrefix,StreetName,StreetSuffix,StreetDirSuffix,StreetAdditionalInfo,City,StateOrProvince,PostalCode,MapOverlay,SavedSearch,CountyOrParish,StreetAddress',
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

		if (flexmlsConnect::wp_input_get('m')) {
			$previous_listing_url .= "&m=".flexmlsConnect::wp_input_get('m');
			$next_listing_url .= "&m=".flexmlsConnect::wp_input_get('m');
		}

		return array($previous_listing_url, $next_listing_url);

	}


	function build_browse_list($pg) {
		global $fmc_api;

		if (!$this->api){
			$this->api = $fmc_api;
		}


		// parse passed parameters for browsing capability
		list($params, $cleaned_raw_criteria, $context) = $this->parse_search_parameters_into_api_request();

		// cut out pieces we don't want
		$modified_params = $params;
		$modified_raw_criteria = $cleaned_raw_criteria;

		unset($modified_params['_expand']);
		$modified_params['_page'] = $pg;
		$modified_raw_criteria['pg'] = $pg;
		$modified_params['_limit'] = empty($_COOKIE['flexmlswordpressplugin']) ? 10 : intval($_COOKIE['flexmlswordpressplugin']) ;
		if ($context == "listings") {
			$results = $this->api->GetMyListings($modified_params);
		}
		elseif ($context == "office") {
			$results = $this->api->GetOfficeListings($modified_params);
		}
		elseif ($context == "company") {
			$results = $this->api->GetCompanyListings($modified_params);
		}
		else {
			$results = $this->api->GetListings($modified_params);
		}

		$result_count = 0;
		foreach ($results as $record) {
			$result_count++;

			$this_result_overall_index = ($this->api->page_size * ($this->api->current_page - 1)) + $result_count;

			$link_to_details_criteria = $modified_raw_criteria;
			// figure out if there's a previous listing
			$link_to_details_criteria['p'] = ($this_result_overall_index != 1) ? 'y' : 'n';

			// figure out if there's a next listing possible
			$link_to_details_criteria['n'] = ( $this_result_overall_index < $this->api->last_count ) ? 'y' : 'n';

			if ($link_to_details_criteria['n'] == 'n') {
				$this->no_more = true;
			}

			$this->browse_list[(string) $this_result_overall_index] = array(
			    'Index' => $this_result_overall_index,
			    'Id' => $record['Id'],
			    'ListingId' => $record['StandardFields']['ListingId'],
			    'Uri' => flexmlsConnect::make_nice_address_url($record, $link_to_details_criteria, $this->type)
			);

		}

	}


}
