<?php

class flexmlsConnectPageListingDetails extends flexmlsConnectPageCore {

	private $listing_data;
	protected $search_criteria;
	protected $api;
	protected $type;

	function __construct( $tag = '' ){

		if ($tag == 'fmc_vow_tag'){
			global $fmc_api_portal;
			$this->api = $fmc_api_portal;
			$this->type = $tag;
		}
		else {
			global $fmc_api;
			$this->api = $fmc_api;
			$this->type = 'fmc_tag';
		}
	}

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
			$filterstr .= " and MlsId Eq '".flexmlsConnect::wp_input_get('m')."'";
		}

		$params = array(
				'_filter' => $filterstr,
				'_limit' => 1,
				'_expand' => 'Photos,Videos,OpenHouses,VirtualTours,Documents,Rooms,CustomFields'
		);
		$result = $this->api->GetListings($params);
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
			$page = flexmlsConnect::get_no_listings_page_number();
			$page_data = get_page($page);
			$fmc_special_page_caught['page-title'] = "Listing Not Available";
			$fmc_special_page_caught['post-title'] = $page_data->post_title;
		}



	}


	function generate_page($from_shortcode = false) {
		global $fmc_api;
		global $fmc_special_page_caught;
		global $fmc_plugin_url;
		global $fmc_api_portal;
		//david debug
		//var_dump($fmc_api->last_error_code);
		//var_dump($fmc_api->last_error_mess);


		if ($this->type == 'fmc_vow_tag' && !$fmc_api_portal->is_logged_in()){
			return "Sorry, but you must <a href={$fmc_api_portal->get_portal_page()}>log in</a> to see this page.<br />";
		}

		if ($this->listing_data == null) {
			if (flexmlsConnect::get_no_listings_pref() == 'page')
			{
				if (!(isset($_SESSION['prevent_recursion'])))
				{
					$_SESSION['prevent_recursion'] = true;
					$page = flexmlsConnect::get_no_listings_page_number();
					$page_data = get_page($page);
					remove_filter('the_content', array('flexmlsConnectPage', 'custom_post_content'));
					echo apply_filters('the_content', $page_data->post_content);
				}
			}
			else
			{
				echo "This listing is no longer available.";
			}
			if (isset($_SESSION['prevent_recursion']))
				unset($_SESSION['prevent_recursion']);
			return;
		}

		$standard_fields_plus = $this->api->GetStandardFields();
		$standard_fields_plus = $standard_fields_plus[0];
		// $custom_fields = $fmc_api->GetCustomFields();

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
			'StreetDirSuffix',
			'StreetSuffix',
			'StreetAdditionalInfo',
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



		ob_start();
		flexmlsPortalPopup::popup_portal('detail_page');
		// disable display of the H1 entry title on this page only
		echo "<style type='text/css'>\n  .entry-title { display:none; }\n</style>\n\n\n";
		//This was added to give grid lines on details page

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

		// RVA HAS SILLY BUSINESS RULES
		//if RVA then add MLSStatus to list of fields to suppress
		if ($sf['MlsId'] === "20051230194116769413000000") {
			array_push($mls_fields_to_suppress, "MlsStatus");
		}


		// begin
		echo "<div class='flexmls_connect__sr_detail' title='{$one_line_address} - MLS# {$sf['ListingId']}'>\n";
		echo "  <img src='{$sf['Photos'][0]['UriLarge']}' class='flexmls_connect__resize_image' />\n";
		echo "  <hr class='flexmls_connect__sr_divider'>\n";

		echo "  <div class='flexmls_connect__sr_address'>\n";

			// show price
		echo "<div class='flexmls_connect__sr_price'>$". flexmlsConnect::gentle_price_rounding($sf['ListPrice']);
			fmcAccount::write_carts($record);
		echo "</div>";
		// show top address details
		echo "{$first_line_address}<br>\n";

		echo "{$second_line_address}<br>\n";

		echo "MLS# {$sf['ListingId']}<br>\n";


		if ($sf['MlsStatus'] == 'Closed')
			$color='Blue';
		else
		    $color='Orange';

		if (($sf['MlsStatus'] != 'Active') and !in_array( "MlsStatus", $mls_fields_to_suppress))
			echo "Status: <span style='color: $color; font-weight:bold;'>{$sf['MlsStatus']}</span><br />";


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
				echo "		  <button class='video_click' rel='v-{$sf['ListingKey']}'>Videos ({$count_videos})</button>\n";
			if ($count_tours > 0) {
				echo " &nbsp;|&nbsp; ";
			}
			}
			if ($count_tours > 0) {
				echo "		  <button class='tour_click' rel='t-{$sf['ListingKey']}'>Virtual Tours ({$count_tours})</button>\n";
			}
		echo "    </div>\n";

		// Share and Print buttons
			echo "		  <button class='print_click' onclick='flexmls_connect.print(this);'><img src='{$fmc_plugin_url}/images/print.png'align='absmiddle' /> Print</button>\n";

			$api_my_account = $this->api->GetMyAccount();
			?>
			<?php if (isset($api_my_account['Name']) && isset($api_my_account['Emails'][0]['Address'])) : ?>
					<button onclick="flexmls_connect.scheduleShowing('<?php addslashes($sf['ListingKey']) ?>',
						'<?php echo addslashes($one_line_address) ?> - MLS# <?php echo addslashes($sf['ListingId']) ?>',
						'<?php echo addslashes($api_my_account['Name'])?>',
						'<?php echo addslashes($api_my_account['Emails'][0]['Address']) ?>');">
							<img src='<?php echo $fmc_plugin_url ?>/images/showing.png' align='absmiddle' /> Schedule a Showing
					</button>
			<?php endif ?>
			<button onclick="flexmls_connect.contactForm('Ask a Question',
				'<?php echo addslashes($one_line_address); ?> - MLS# <?php echo addslashes($sf['ListingId'])?> ',
				'<?php echo addslashes($sf['ListAgentEmail']);?>',
				'<?php echo addslashes($sf['ListOfficeEmail']); ?>',
				'<?php echo addslashes($sf['ListingId']); ?>');"> <img src='<?php echo $fmc_plugin_url ?>/images/admin_16.png'align='absmiddle' /> Ask a Question
			</button>
			<?php
           

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
					echo "<a href='{$p['UriLarge']}' data-connect-ajax='true' rel='p-{$sf['ListingKey']}' title='".htmlspecialchars($p['Caption'], ENT_QUOTES)."'></a>\n";
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
			if ($sf['DocumentsCount'])
				echo "<div class='flexmls_connect__tab' group='flexmls_connect__document_group'>Documents</div>";
		echo "</div>";


		// build the Details portion of the page
		echo "<div class='flexmls_connect__tab_group' id='flexmls_connect__detail_group'>";


		//Organize Custom Fields ["Main"] and ["Details"] if they exist
		$custom_fields = array();
		if (is_array($record["CustomFields"][0]["Main"])){
			foreach ($record["CustomFields"][0]["Main"] as $data){
				foreach ($data as $group_name => $fields)
					foreach ($fields as $field)
							foreach ($field as $field_name => $val)
								$custom_fields["Main"][$group_name][$field_name]= $val;
			}
		}

		if (isset($record["CustomFields"][0]["Details"]) and is_array($record["CustomFields"][0]["Details"])) {
			foreach ($record["CustomFields"][0]["Details"] as $data){
				foreach ($data as $group_name => $fields)
					foreach ($fields as $field)
							foreach ($field as $field_name => $val)
								$custom_fields["Details"][$group_name][$field_name]= $val;
			}
		}


		$MlsFieldOrder = $this->api->GetFieldOrder($sf["PropertyType"]);
		$property_features_values = array();
		foreach ($MlsFieldOrder as $field){
			foreach ($field as $name => $key){
				foreach ($key as $property){
					if (in_array($property["Label"],$mls_fields_to_suppress)){
						continue;
					}

					$is_standard_Field = false;
					if (isset($property["Domain"]) and (isset($sf[$property["Field"]]))){
						if ($property["Domain"] == "StandardFields" and flexmlsConnect::is_not_blank_or_restricted($sf[$property["Field"]])){
							$is_standard_Field = true;
						}
					}


					$detail_custom_bool = false;
					$custom_custom_bool = false;
					// If a field has a boolean for a value, mark it in the features section
					if (isset($custom_fields["Details"][$name][$property["Label"]])) {
						$detail_custom_bool = $custom_fields["Details"][$name][$property["Label"]] === true;
					}
					if (isset($custom_fields["Main"][$name][$property["Label"]])) {
						$custom_custom_bool = $custom_fields["Main"][$name][$property["Label"]] === true;
					}

					// Check if for Custom field Details
					$custom_details = false;
					if (isset($property["Detail"]) and isset($custom_fields["Details"][$name][$property["Label"]])){
						$custom_details = $property["Detail"] and flexmlsConnect::is_not_blank_or_restricted($custom_fields["Details"][$name][$property["Label"]]);
					}

					$custom_main = false;
					if (isset($custom_fields["Main"][$name][$property["Label"]]))
						$custom_main = flexmlsConnect::is_not_blank_or_restricted($custom_fields["Main"][$name][$property["Label"]]);

					//Standard Fields
					if ($is_standard_Field){
						$property_detail_values[$name][] = "<b>".$property["Label"].":</b> ".$sf[$property["Field"]];
					}

					//Custom Fields with value of true are placed in property feature section
					else if ($detail_custom_bool or $custom_custom_bool){
						$property_features_values[$name][]= $property["Label"];
					}
					//Custom Fields - DETAIL
 					else if ($custom_details){
 						$property_detail_values[$name][] = "<b>".$property["Label"].":</b> ".$custom_fields["Details"][$name][$property["Label"]];
 					}

 					//Custom Fields - MAIN
 					else if ($custom_main){
						$property_detail_values[$name][] = "<b>".$property["Label"].":</b> ".$custom_fields["Main"][$name][$property["Label"]];
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

		echo "<div class='flexmls_connect__detail_header'>Property Features</div>\n";
		echo "<table width='100%'>\n";
		$index = 0;
		foreach ($property_features_values as $k => $v) {
			$value = "<b>".$k.": </b>";
			foreach($v as $x){
				$value .= $x."; ";
			}
			$value = trim($value,"; ");

			echo "	<tr " . ($index % 2 == 1 ? "class='flexmls_connect__sr_zebra_on'" : "") . "><td>{$value}</td></tr>\n";
			$index++;
		}
		echo "</table>\n";
		echo "<br><br>\n\n";






		// build the Room Information portion of the page
		$room_fields = $this->api->GetRoomFields($sf['MlsId']);
		$room_names = array();
		$room_values = array();
		foreach ($room_fields as $mls_named_room){
			array_push($room_names,$mls_named_room["Label"]);
			array_push($room_values,array());
		}
		$room_information_values = array();
		if ( count($sf['Rooms'] > 0) ) {

			foreach ($sf['Rooms'] as $r) {

				foreach ($r['Fields'] as $rf) {
					foreach ($rf as $rfk => $rfv) {

						$label = null;
						if (is_array($room_fields) && array_key_exists($rfk, $room_fields)) {
							// since the given name is a key found in the metadata, use the metadata label for it
							$label = $room_fields[$rfk]['Label'];
						}	else {
							$label = $rfk;
						}

						for ($i = 0; $i < count($room_names); $i++){
							if ($label == $room_names[$i]){
								array_push($room_values[$i],$rfv);
							}
						}
						/*if     ($label == "Room") {
							$this_name = $rfv;
						}*/
					}
				}
			}

			//if all values in a field are zero append them to an array
			$toUnset = array();
			for ($i=0;$i<count($room_values);$i++){
				if (!array_filter($room_values[$i])) {
					array_push($toUnset,$i);
				}
			}
			//unset causes issues if attempt to do this in above for loop
			foreach ($toUnset as $index){
				unset($room_values[$index]);
				unset($room_names[$index]);
			}
			//reset the indexes to have order 0,1,2,...
			$room_values=array_values($room_values);
			$room_names= array_values($room_names);

			$room_count = isset($room_values[0]) ? count($room_values[0]) : false;
			if ($room_count) {
				echo "<div class='flexmls_connect__detail_header'>Room Information</div>\n";
				echo "<table width='100%'>\n";
				echo "	<tr>\n";
				foreach ($room_names as $room){
					echo "    <td><b>{$room}</b></td>\n";
				}
				echo "  </tr>\n";

				for ($x = 0; $x < $room_count; $x++)
				{
					echo "  <tr " . ($x % 2 == 0 ? "class='flexmls_connect__sr_zebra_on'" : "") . ">\n";
					for ($i = 0; $i < count($room_values); $i++){
						echo "<td>{$room_values[$i][$x]}</td>";
					}
					echo "</tr>\n";
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


			//Documents tab
			if ($sf['DocumentsCount'])
			{

				echo "<div class='flexmls_connect__tab_group' id='flexmls_connect__document_group' style='display:none'>";
				echo "<div class='flexmls_connect__detail_header'>Listing Documents</div>\n";
				echo "<table>";

				//Image extensions to show colorbox for
				$fmc_colorbox_extensions = array('gif', 'png');

				foreach ($sf['Documents'] as $fmc_document){
					if ($fmc_document['Privacy']=='Public'){
						echo "<tr class=flexmls_connect__zebra><td>";
						$fmc_extension = explode('.',$fmc_document['Uri']);
						$fmc_extension = ($fmc_extension[count($fmc_extension)-1]);
						if ($fmc_extension == 'pdf'){
							$fmc_file_image = $fmc_plugin_url . '/images/pdf-tiny.gif';
							$fmc_docs_class = "class='fmc_document_pdf'";
						}
						elseif (in_array($fmc_extension, $fmc_colorbox_extensions)){
							$fmc_file_image = $fmc_plugin_url . '/images/image_16.gif';
							$fmc_docs_class = "class='fmc_document_colorbox'";
						}
						else{
							$fmc_file_image = $fmc_plugin_url . '/images/docs_16.gif';
						}
						echo "<a $fmc_docs_class value={$fmc_document['Uri']}><img src='{$fmc_file_image}' align='absmiddle' /> {$fmc_document['Name']} </a>";

						echo "</td></tr>";
					}

				}
				echo "</table>";
				echo "</div>";
			}


			echo "  <hr class='flexmls_connect__sr_divider'>\n";
		// disclaimer
			echo "	<div class='flexmls_connect__idx_disclosure_text'>";

					$compList = flexmlsConnect::mls_required_fields_and_values("Detail",$record);

					foreach ($compList as $reqs){
							if (flexmlsConnect::is_not_blank_or_restricted($reqs[1])){
								if ($reqs[0] == 'LOGO'){
									echo "<img style='padding-bottom: 5px' src=$reqs[1]></img>";
									continue;
								}
								echo "<p>{$reqs[0]}: {$reqs[1]}</p>";
							}
					}

			echo "<p>";
			echo flexmlsConnect::get_big_idx_disclosure_text();
			echo "</p>\n";

	echo "<p>".date('l jS \of F Y  h:i A')."</p>\n";

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
		return flexmlsConnect::make_nice_tag_url('next-listing', $link_criteria, $this->type);
	}

	function browse_previous_url() {
		$link_criteria = $this->search_criteria;
		$link_criteria['id'] = $this->listing_data['StandardFields']['ListingId'];
		return flexmlsConnect::make_nice_tag_url('prev-listing', $link_criteria,$this->type);
	}


}
