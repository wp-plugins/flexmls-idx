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

		preg_match('/mls\_(.*?)$/', $tag, $matches);

		$id_found = $matches[1];

    $filterstr = "ListingId Eq '{$id_found}'";
    
    if ( flexmlsConnect::wp_input_get('m') ) {
      $filterstr .= " and MlsId Eq'".flexmlsConnect::wp_input_get('m')."'";
    }

		$params = array(
				'_filter' => $filterstr,
				'_limit' => 1,
				'_expand' => 'Photos,Videos,OpenHouses,VirtualTours,Documents,Rooms,CustomFields'
		);
		$result = $fmc_api->GetListings($params);
		$listing = $result[0];

		//david debug
		//print_r($params);
		//print_r($listing);
	

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
		
		echo "<style>.no_meta_data {display:none; width:1px; height:1px;}</style>";
		
		//david debug
		//var_dump($fmc_api->last_error_code);
		//var_dump($fmc_api->last_error_mess);			
		
		if ($this->listing_data == null) {
			return "<p>The listing you requested is no longer available.</p>";
		}
		
		$standard_fields_plus = $fmc_api->GetStandardFieldsPlusHasList();
		$custom_fields = $fmc_api->GetCustomFields();
		
		$mls_fields_to_suppress = array(
		  'ListingKey',
		  'ListingId',
		  'ListingPrefix',
		  'ListingNumber',
		  
		  'Latitude',
		  'Longitude',
		  
		  'MlsId',
		  'StandardStatus',
		  'PermitInternetYN',
		  'UnparsedAddress',
		  
		  'ListAgentId',
		  'ListAgentUserType',
		  'ListOfficeUserType',
		  'ListAgentFirstName',
		  'ListAgentMiddleName',
		  'ListAgentLastName',
		  'ListAgentEmail',
		  'ListAgentStateLicense',
		  'ListAgentPreferredPhone',
		  'ListAgentPreferredPhoneExt',
		  'ListAgentOfficePhone',
		  'ListAgentOfficePhoneExt',
		  'ListAgentDesignation',
		  'ListAgentTollFreePhone',
		  'ListAgentCellPhone',
		  'ListAgentDirectPhone',
		  'ListAgentPager',
		  'ListAgentVoiceMail',
		  'ListAgentVoiceMailExt',
		  'ListAgentFax',
		  'ListAgentURL',

      'ListOfficeId',		  
      'ListCompanyId',		  
		  'ListOfficeName',
		  'ListCompanyName',
		  'ListOfficeFax',
		  'ListOfficeEmail',
		  'ListOfficeURL',
		  'ListOfficePhone',
		  'ListOfficePhoneExt',
		  
		  'CoListAgentId',
		  'CoListAgentUserType',
		  'CoListOfficeUserType',
		  'CoListAgentFirstName',
		  'CoListAgentMiddleName',
		  'CoListAgentLastName',
		  'CoListAgentEmail',
		  'CoListAgentStateLicense',
		  'CoListAgentPreferredPhone',
		  'CoListAgentPreferredPhoneExt',
		  'CoListAgentOfficePhone',
		  'CoListAgentOfficePhoneExt',
		  'CoListAgentDesignation',
		  'CoListAgentTollFreePhone',
		  'CoListAgentCellPhone',
		  'CoListAgentDirectPhone',
		  'CoListAgentPager',
		  'CoListAgentVoiceMail',
		  'CoListAgentVoiceMailExt',
		  'CoListAgentFax',
		  'CoListAgentURL',		  

      'CoListOfficeId',		  
      'CoListCompanyId',		  
		  'CoListOfficeName',
		  'CoListCompanyName',
		  'CoListOfficeFax',
		  'CoListOfficeEmail',
		  'CoListOfficeURL',
		  'CoListOfficePhone',
		  'CoListOfficePhoneExt',
		  		  
		  'BuyerAgentId',		  
		  'CoBuyerAgentId',
		  'BuyerOfficeId',
		  'CoBuyerOfficeId',
		  		  
		  'StreetNumber',
		  'StreetName',
		  'StreetDirPrefix',
		  'PropertyClass',
		  'StateOrProvince',
		  'PostalCode',
		  'City',
		  
		  'ApprovalStatus',
		  'PublicRemarks',
		  
      'VOWAddressDisplayYN',
      'VOWConsumerCommentYN',
      'VOWAutomatedValuationDisplayYN',
      'VOWEntireListingDisplayYN',
		  
		  'PriceChangeTimestamp',
		  'MajorChangeTimestamp',
		  'MajorChangeType',
		  'ModificationTimestamp',
		  'StatusChangeTimestamp'
		);
		
		//old hardcode list that is not spun anymore
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
		    
  			'Sold Price' => 'ClosePrice',
  			'Listing Service' => 'ListingService',
  			'Listing Agreement' => 'ListingAgreement',
  			'Lease Considered' => 'LeaseConsideredYN',
  			'Home Warranty' => 'HomeWarrantyYN',
  			'Cancel Date' => 'CancelDate',
  			'Pending Date' => 'PendingDate',
  			'Withdrawn Date' => 'WithdrawDate',
  			'Status Change Date' => 'StatusChangeDate',
  			'Listing Contract Date' => 'ListingContractDate',
  			'Expiration Date' => 'ExpirationDate',
  			'Off Market Date' => 'OffMarketDate',
  			'Rent/Lease Date' => 'RentOrLeaseDate',
  			'Days On Market' => 'DaysOnMarket',
  			'Cumulative Days On Market' => 'CumulativeDaysOnMarket',
  			'Carrier Route' => 'CarrierRoute',
  			'Township' => 'Township',
  
  			'Directions' => 'Directions',
  			'Map Coordinate' => 'MapCoordinate',
  			'Map Coordinate Source' => 'MapCoordinateSource',
  			'Map URL' => 'MapURL',
  			'Cross Street' => 'CrossStreet',
  			'Elementary School' => 'ElementarySchool',
  			'Middle/Junior High School' => 'MiddleOrJuniorSchool',
  			'High School' => 'HighSchool',
  			'Elementary School District' => 'DistrictElementarySchool',
  			'Middle/Junior High School District' => 'DistrictMiddleOrJuniorSchool',
  			'High School District' => 'DistrictHighSchool',
  			'School District' => 'SchoolDistrict',
  			'Status Contingency' => 'MlsStatusInformation',
  
  			'Association Fee' => 'AssociationFee',
  			'Association Fee Frequency' => 'AssociationFeeFrequency',
  			'Association Fee 2' => 'AssociationFee2',
  			'Association Fee 2 Frequency' => 'AssociationFee2Frequency',
  			'Association Fee Includes' => 'AssociationFeeIncludes',
  			'Association Amenities' => 'AssociationAmenities',
  			'Association Phone' => 'AssociationPhone',
  			'Association' => 'AssociationYN',
  			'Lot Size Area' => 'LotSizeArea',
  			'Lot Size Source' => 'LotSizeSource',
  			'Lot Size Units' => 'LotSizeUnits',
  			'Lot Size Dimensions' => 'LotSizeDimensions',
  			'Lot Dimensions Source' => 'LotDimensionsSource',
  			'Occupant Name' => 'OccupantName',
  			'Original Listing Price' => 'OriginalListPrice',
  			'Listing Price Low' => 'ListPriceLow',
  			'Previous Listing Price' => 'PreviousListPrice',
  			'Reserve Listing Price' => 'ReserveListPrice',
  			'Listing Price Currency' => 'ListPriceCurrency',
  			'Rent/Lease Price' => 'RentOrLeasePrice',
  			'Rent/Lease Price Frequency' => 'RentOrLeasePriceFrequency',
  			'Previous Rent/Lease Price' => 'PreviousRentOrLeasePrice',
  			'Rent/Lease Price Currency' => 'RentOrLeasePriceCurrency',
  			'Buyer Agency Compensation' => 'BuyerAgencyCompensation',
  			'Buyer Agency Compensation Type' => 'BuyerAgencyCompensationType',
  			'Sub Agency Compensation' => 'SubAgencyCompensation',
  			'Sub Agency Compensation Type' => 'SubAgencyCompensationType',
  			'Transaction Broker Compensation' => 'TransactionBrokerCompensation',
  			'Transaction Broker Compensation Type' => 'TransactionBrokerCompensationType',
  			'Dual Variable Compensation' => 'DualVariableCompensationYN',
  			'Waterfront' => 'WaterFrontYN',
  			'Frontage Type' => 'FrontageType',
  			'Frontage Length' => 'FrontageLength',
  			'Frontage Length Units' => 'FrontageLengthUnits',
  			'Water Body Name' => 'WaterBodyName',
  			'Showing Instructions' => 'ShowingInstructions',
  			'Showing Phone #' => 'ShowingPhoneNumber',
  			'Lock Box #' => 'LockBoxNumber',
  			'Lock Box Location' => 'LockBoxLocation',
  			'Lock Box Type' => 'LockBoxType',
  			'Exclusions' => 'Exclusions',
  			'Inclusions' => 'Inclusions',
  			'Disclosures' => 'Disclosures',
  			'Special Listing Conditions' => 'SpecialListingConditions',
  			'Listing Financing' => 'ListingFinancing',
  			'Buyer Financing' => 'BuyerFinancing',
  			'Possession' => 'Possession',
  			'MLS Area Major' => 'MLSAreaMajor',
  
  			'Above Grade Finished Area' => 'AboveGradeFinishedArea',
  			'Accessibility Features' => 'AccessibilityFeatures',
  			'Additional Parcels Description' => 'AdditionalParcelsDescription',
  			'Additional Parcels' => 'AdditionalParcelsYN',
  			'Architectural Style' => 'ArchitecturalStyle',
  			'Provider Name' => 'ProviderName',
  			'Property Class' => 'PropertyClass',
  
  			'Major Change Type' => 'MajorChangeType',
  			'Major Change Timestamp' => 'MajorChangeTimestamp',
  			'Status Change Timestamp' => 'StatusChangeTimestamp',
  			'Contingency' => 'Contingency',
  
  			'Country' => 'Country',
  			'Occupant Phone' => 'OccupantPhone',
  			'Occupant Type' => 'OccupantType',
  			'Owner Name' => 'OwnerName',
  			'Owner Phone' => 'OwnerPhone',
  			'Land Lease' => 'LandLeaseYN',
  			'Land Lease Fee' => 'LandLeaseFee',
  			'Land Lease Fee Frequency' => 'LandLeaseFeeFrequency',
  			'Land Lease Expiration Date' => 'LandLeaseExpirationDate',
  			'View' => 'View',
  			'Lot Features' => 'LotFeatures',
  			'Community Features' => 'CommunityFeatures',
  			'Pool Features' => 'PoolFeatures',
  			'Spa Features' => 'SpaFeatures',
  			'Gross Scheduled Income' => 'GrossScheduledIncome',
  			'Operating Expense' => 'OperatingExpense',
  			'Net Operating Income' => 'NetOperatingIncome',
  			'CAP Rate' => 'CAPRate',
  			'Monthly Gross Scheduled Income' => 'MonthlyGrossScheduledIncome',
  			'Total Annual Operating Expenses' => 'TotalAnnualOperatingExpenses',
  			'Number Of Units Leased' => 'NumberOfUnitsLeased',
  			'Total Actual Rent' => 'TotalActualRent',
  			'Rent Control' => 'RentControlYN',
  			'Number Of Units Total' => 'NumberOfUnitsTotal',
  			'Number Of Units Buildings' => 'NumberOfUnitsBuildings',
  			'Tenant Pays' => 'TenantPays',
  			'Vacancy Allowance' => 'VacancyAllowance',
  			'Cable TV Expense' => 'CableTvExpense',
  			'Electric Expense' => 'ElectricExpense',
  			'Gardner Expense' => 'GardnerExpense',
  			'Furniture Replacement Expense' => 'FurnitureReplacementExpense',
  			'Gas Expense' => 'GasExpense',
  			'Insurance Expense' => 'InsuranceExpense',
  			'Other Expense' => 'OtherExpense',
  			'Licenses Expense' => 'LicensesExpense',
  			'Maintenance Expense' => 'MaintenanceExpense',
  			'New Taxes Expense' => 'NewTaxesExpense',
  			'Pest Control Expense' => 'PestControlExpense',
  			'Pool Expense' => 'PoolExpense',
  			'Supplies Expense' => 'SuppliesExpense',
  			'Trash Expense' => 'TrashExpense',
  			'Water Sewer Expense' => 'WaterSewerExpense',
  			'Workmans Compensation Expense' => 'WorkmansCompensationExpense',
  			'Professional Management Expense' => 'ProfessionalManagementExpense',
  			'Manager Expense' => 'ManagerExpense',
  			'Rent Includes' => 'RentIncludes',
  			'Irrigation Water Rights' => 'IrrigationWaterRightsYN',
  			'Irrigation Water Rights Acres' => 'IrrigationWaterRightsAcres',
  			'Irrigation Source' => 'IrrigationSource',
  			'Distance From Shopping' => 'DistanceFromShopping',
  			'Electric On Property' => 'ElectricOnPropertyYN',
  			'Distance To Electric' => 'DistanceToElectric',
  			'Crops Included' => 'CropsIncludedYN',
  			'Grazing Permits BLM' => 'GrazingPermitsBLMYN',
  			'Grazing Permits Forest Service' => 'GrazingPermitsForestServiceYN',
  			'Grazing Permits Private' => 'GrazingPermitsPrivateYN',
  			'Cultivated Area' => 'CultivatedArea',
  			'Pasture Area' => 'PastureArea',
  			'Range Area' => 'RangeArea',
  			'Wooded Area' => 'WoodedArea',
  			'Fencing' => 'Fencing',
  			'Distance From School Bus' => 'DistanceFromSchoolBus',
  			'Farm Credit Service Included' => 'FarmCreditServiceInclYN',
  			'Farm Land Area Units' => 'FarmLandAreaUnits',
  			'Farm Land Area Source' => 'FarmLandAreaSource',
  			'Yard And Grounds Features' => 'YardAndGroundsFeatures',
  			'Utilities' => 'Utilities',
  			'Baths One Quarter' => 'BathsOneQuarter',
  			'Living Area' => 'LivingArea',
  			'Below Grade Finished Area' => 'BelowGradeFinishedArea',
  			'Building Area Source' => 'BuildingAreaSource',
  			'Detached' => 'DetachedYN',
  			'Foundation Area' => 'FoundationArea',
  			'Garage' => 'GarageYN',
  			'Garage Spaces' => 'GarageSpaces',
  			'Attached Garage' => 'AttachedGarageYN',
  			'Carport Spaces' => 'CarportSpaces',
  			'Carport' => 'CarportYN',
  			'Open Parking YN' => 'OpenParkingYN',
  			'Open Parking Spaces' => 'OpenParkingSpaces',
  			'Covered Spaces' => 'CoveredSpaces',
  			'Parking Features' => 'ParkingFeatures',
  			'Other Parking' => 'OtherParking',
  			'Parking Total' => 'ParkingTotal',
  			'RV Parking Dimensions' => 'RVParkingDimensions',
  			'Stories Total' => 'StoriesTotal',
  			'Stories' => 'Stories',
  			'Levels' => 'Levels',
  			'Year Built Effective' => 'YearBuiltEffective',
  			'Green Building Certification' => 'GreenBuildingCertification',
  			'Green Certifying Body' => 'GreenCertifyingBody',
  			'Green Year Certified' => 'GreenYearCertified',
  			'Green Certification Rating' => 'GreenCertificationRating',
  			'Builder Name' => 'BuilderName',
  			'Builder Model' => 'BuilderModel',
  			'Heating' => 'Heating',
  			'Heating Fuel' => 'HeatingFuel',
  			'Cooling' => 'Cooling',
  			'Interior Features' => 'InteriorFeatures',
  			'Exterior Features' => 'ExteriorFeatures',
  			'Rooms Total' => 'RoomsTotal',
  			'Fireplace Features' => 'FireplaceFeatures',
  			'Fireplace Fuel' => 'FireplaceFuel',
  			'Fireplace Locations' => 'FireplaceLocations',
  			'Fireplaces Total' => 'FireplacesTotal',
  			'Roof' => 'Roof',
  			'Construction Materials' => 'ConstructionMaterials',
  			'Foundation Details' => 'FoundationDetails',
  			'Flooring' => 'Flooring',
  			'Water Source' => 'WaterSource',
  			'Water Heater Fuel' => 'WaterHeaterFuel',
  			'Sewer' => 'Sewer',
  			'Direction Faces' => 'DirectionFaces',
  			'Other Structures' => 'OtherStructures',
  			'Other Equipment' => 'OtherEquipment',
  			'Kitchen Appliances' => 'KitchenAppliances',
  			'Security Features' => 'SecurityFeatures',
  			'Rooms Description' => 'RoomsDescription',
  			'Door Features' => 'DoorFeatures',
  			'Window Features' => 'WindowFeatures',
  			'Patio And Porch Features' => 'PatioAndPorchFeatures',
  			'Year Built Details' => 'YearBuiltDetails',
  			'Cooling Fuel' => 'CoolingFuel',
  			'Number Of Separate Electric Meters' => 'NumberOfSeparateElectricMeters',
  			'Number Of Separate Gas Meters' => 'NumberOfSeparateGasMeters',
  			'Number Of Separate Water Meters' => 'NumberOfSeparateWaterMeters',
  			'Green Energy Efficient' => 'GreenEnergyEfficient',
  			'Green Energy Generation' => 'GreenEnergyGeneration',
  			'Green Sustainability' => 'GreenSustainability',
  			'Green Water Conservation' => 'GreenWaterConservation',
  			'Green Indoor Air Quality' => 'GreenIndoorAirQuality',
  			'Green Location' => 'GreenLocation',
  			'Habitable Residence' => 'HabitableResidenceYN',
  			'Disability Features' => 'DisabilityFeatures',
  			'Electric' => 'Electric',
  			'Gas' => 'Gas',
  			'Telephone' => 'Telephone',
  			'Mobile Length' => 'MobileLength',
  			'Mobile Width' => 'MobileWidth',
  			'Body Type' => 'BodyType',
  			'Skirt' => 'Skirt',
  			'Mobile Dim Units' => 'MobileDimUnits',
  			'Park Name' => 'ParkName',
  			'Park Manager Name' => 'ParkManagerName',
  			'Park Manager Phone' => 'ParkManagerPhone',
  			'Mobile Home Remains' => 'MobileHomeRemainsYN',
  			'Make' => 'Make',
  			'Model' => 'Model',
  			'Number Of Pads' => 'NumberOfPads',
  			'SerialU' => 'SerialU',
  			'DOH1' => 'DOH1',
  			'License 1' => 'License1',
  			'SerialX' => 'SerialX',
  			'DOH2' => 'DOH2',
  			'License 2' => 'License2',
  			'SerialXX' => 'SerialXX',
  			'DOH3' => 'DOH3',
  			'License 3' => 'License3',
  			'Structural Area Units' => 'StructuralAreaUnits',
  			'Rooms List' => 'RoomsList',
  			'Energy Saving Features' => 'EnergySavingFeatures',
  			'Zoning' => 'Zoning',
  			'Parcel Number' => 'ParcelNumber',
  			'Tax Lot' => 'TaxLot',
  			'Tax Block' => 'TaxBlock',
  			'Tax Tract/Section' => 'TaxTractOrSection',
  			'Tax Legal Description' => 'TaxLegalDescription',
  			'Tax Amount' => 'TaxAmount',
  			'Tax Amount Frequency' => 'TaxAmountFrequency',
  			'Tax Year' => 'TaxYear',
  			'Tax Assessed Value' => 'TaxAssessedValue',
  			'Tax Exemptions' => 'TaxExemptions',
  			'Tax Other Assessment Amount' => 'TaxOtherAssessmentAmount',
  			'Tax Other Assessment Amount Frequency' => 'TaxOtherAssessmentAmountFrequency',
  			'Tax Book Number' => 'TaxBookNumber',
  			'Tax Map Number' => 'TaxMapNumber',
  			'Tax Parcel Letter' => 'TaxParcelLetter',		    
		    
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
  		echo "<div class='flexmls_connect__sr_price'>$". flexmlsConnect::gentle_price_rounding($sf['ListPrice']) . "</div>\n";

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
			
			$api_my_account = $fmc_api->GetMyAccount();
			
			if ($api_my_account['Name'] && $api_my_account['Emails'][0]['Address']) {
  			echo "		  <button class='flexmls_connect__schedule_showing_click' onclick=\"flexmls_connect.scheduleShowing('{$sf['ListingKey']}','{$one_line_address} - MLS# {$sf['ListingId']}','".htmlspecialchars ($api_my_account['Name'])."','{$api_my_account['Emails'][0]['Address']}');\"><img src='{$fmc_plugin_url}/images/showing.png'align='absmiddle' /> Schedule a Showing</button>\n";
			}
			
			echo "<div style='display:none;color:green;font-weight:bold;text-align:center;padding:10px' id='flexmls_connect__success_message'></div>";
			
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
		
		foreach ($standard_fields_plus as $k => $v) {
			if ( array_key_exists($k, $sf) && !in_array($k, $mls_fields_to_suppress)) {
				$this_val = $sf[$k];
				
				if ($this_val === true) { $this_val = "Yes"; }

				if ($k == "PropertyType") {
					$this_val = flexmlsConnect::nice_property_type_label($this_val);
				}

				if ($v['HasList']==1 && is_array($this_val)){
				  $this_val_new = array();
			    foreach ($this_val as $ki => $vii) {
			      $foundone = false; //(11/19/2012 2:06:07 PM) Brandon M: It's official, if you don't have meta data, don't display the field at all.
			      foreach ($v['HasListValues'] as $vi) {
  			      if ($vi["Value"]==$ki) {
  			        array_push($this_val_new, $vi["Name"]);
  			        $foundone = true;
  			      }
			      } 
			      if (!$foundone) {
              $logurl = "http://gather.flexmls.com/?u={$sf['MlsId']}&d=MetaDataFail&e=standard field&t={$v['Label']}&l={$ki}";
  			      echo "<img src=\"{$logurl}\" class='no_meta_data'>";
  			    }  
			    }
				  $this_val = implode("; ", $this_val_new); 
				} else if (is_array($this_val)) {
          $logurl = "http://gather.flexmls.com/?u={$sf['MlsId']}&d=MetaDataFail&e=standard field value is array but has_list is not set&t={$v['Label']}&l={$this_val}";
		      echo "<img src=\"{$logurl}\" class='no_meta_data'>";
				  //have to blank it out cause value has an array but does not has_list
				  $this_val = "";
				}

				if (flexmlsConnect::is_not_blank_or_restricted($this_val)) {
				  $property_detail_values["Summary"][] = "<b>{$v['Label']}:</b>&nbsp; {$this_val}"; //({$k})
			  }
			}
		}
		
		$detailsarepresent = false;
		
		if (isset($record['CustomFields'][0]['Details']))
		  $detailsarepresent = true;
		
		//old way details are present
		if ($detailsarepresent === true) {
		
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
  		
		//new way no details they are in main list WP-121
	  } else {		

  		foreach ($record['CustomFields'][0]['Main'] as $one) {
  			foreach ($one as $property_headline_description => $two) {
  				foreach ($two as $three) {
  					foreach ($three as $k => $v) {
  						if ( flexmlsConnect::is_not_blank_or_restricted($v) && is_bool($v) === false ) {
  						  $this_val = "";
  						  if ($custom_fields[0][$property_headline_description]["Fields"][$k]["HasList"]==1) {
  						    $custom_field = $fmc_api->GetCustomField($k);
    						  foreach ($custom_field[0][$k]["FieldList"] as $cfv) {
    						    if ($cfv["Value"]==$v)
    						      $this_val = $cfv["Name"];
    						  }		
    						  if ($this_val=="") {
    						    $logurl = "http://gather.flexmls.com/?u={$sf['MlsId']}&d=MetaDataFail&e=custom main non boolean field&t={$k}&l={$v}";
  			            echo "<img src=\"{$logurl}\" class='no_meta_data'>";
    						  }  
  						  } else {
    							$this_val = $v;
    						}
                if ($this_val!="")
  							  $property_detail_values[$property_headline_description][] = "<b>{$k}:</b> {$this_val}";
  						}
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

		//old way details are present
		if ($detailsarepresent === true) {
  		
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

		//new way no details they are in main list WP-121
	  } else {		

  		foreach ($record['CustomFields'][0]['Main'] as $one) {
  			foreach ($one as $k => $two) {
  			  $this_feature = array();
  				foreach ($two as $three) {
  					foreach ($three as $name => $v) {
  						if ( flexmlsConnect::is_not_blank_or_restricted($v) && is_bool($v) === true ) {
  							if ($v === true) {
  							  if ($custom_fields[0][$k]["Fields"][$name]["Label"]) {
          			    $this_feature[] = $custom_fields[0][$k]["Fields"][$name]["Label"];
          			  } else {
          			    $logurl = "http://gather.flexmls.com/?u={$sf['MlsId']}&d=MetaDataFail&e=custom main boolean field&t={$k}&l={$v}";
  			            echo "<img src=\"{$logurl}\" class='no_meta_data'>";
          			  }  
  						  }
  						}
  					}
  				}
  				if ( count($this_feature) > 0) {
  					$property_features_values[] = "<b>{$k}:</b> ". implode("; ", $this_feature);
  				}				
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

		$room_fields = $fmc_api->GetRoomFields($sf['MlsId']);

		// build the Room Information portion of the page
		$room_information_values = array();
		if ( count($sf['Rooms'] > 0) ) {
			$verified_room_count = 0;
			
			foreach ($sf['Rooms'] as $r) {
				$this_name = null;
				$this_level = null;
				
				foreach ($r['Fields'] as $rf) {
					foreach ($rf as $rfk => $rfv) {

						$label = null;
						if (is_array($room_fields) && array_key_exists($rfk, $room_fields)) {
							// since the given name is a key found in the metadata, use the metadata label for it
							$label = $room_fields[$rfk]['Label'];
						}	else {
							$label = $rfk;
						}

						if ($label == "Room Name") {
							$this_name = $rfv;
						}
						if ($label == "Room Level") {
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
			if (flexmlsConnect::mls_requires_agent_name_in_search_results() && flexmlsConnect::is_not_blank_or_restricted($sf['ListAgentFirstName']) && flexmlsConnect::is_not_blank_or_restricted($sf['ListAgentLastName'])) {
				echo "<p>Listing Agent: {$sf['ListAgentFirstName']} {$sf['ListAgentLastName']}</p>\n";
			}
			echo "<p>";
			echo flexmlsConnect::get_big_idx_disclosure_text();
			echo "</p>\n";
			echo "<p>Prepared on ".date('l jS \of F Y \a\t h:i A')."</p>\n";
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
		$link_criteria['m'] = $this->listing_data['StandardFields']['MlsId'];
		return flexmlsConnect::make_nice_tag_url('next-listing', $link_criteria);
	}
	
	function browse_previous_url() {
		$link_criteria = $this->search_criteria;
		$link_criteria['id'] = $this->listing_data['StandardFields']['ListingId'];
		$link_criteria['m'] = $this->listing_data['StandardFields']['MlsId'];
		return flexmlsConnect::make_nice_tag_url('prev-listing', $link_criteria);
	}


}
