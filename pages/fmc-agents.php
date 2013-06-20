<?php

class flexmlsConnectAgentSearchResults extends flexmlsConnectPageCore {

	private $api_my_account;
	protected $search_criteria;
	protected $total_pages;
	protected $current_page;
	protected $total_rows;
	protected $page_size;
	protected $fmc_accounts;
	public $settings;
	
	function pre_tasks($tag) {
		//flexmlsConnect::show_warnings(true);
		global $fmc_special_page_caught;
		global $fmc_api;
		global $fmc_plugin_url;
		//Need to get all search criteria here.
		$this->api_my_account = $fmc_api->GetMyAccount();
		
		$params = $this->get_search_data();
		$this->fmc_accounts = $fmc_api->GetAccounts($params);
		$this->total_pages = $fmc_api->total_pages;
		$this->current_page = $fmc_api->current_page;
		$this->total_rows = $fmc_api->last_count;
		$this->page_size = $fmc_api->page_size;
	}


	function generate_page($from_shortcode = false) {
		global $fmc_api;
		global $fmc_special_page_caught;
		global $fmc_plugin_url;
		global $fmc_search_results_loaded;

		ob_start();
		
		if (isset($this->settings['title']))
			echo "<h2>".$this->settings['title']."</h2>";
		
		$this->print_search_box();
		
		/* Print number of results with the type of result */
		?>
		<span class='flexmls_connect__sr_matches'>
		<div class='flexmls_connect__sr_matches_count'> 
			<?php echo number_format($this->total_rows, 0, '.', ','); ?> 
		</div>
		
		<?php 
		$plural = ($this->total_rows==1 ? "":"s");
		if (isset($this->search_criteria['office_search'] )){
			echo "Agent".$plural." in <b>".$this->fmc_accounts[0]['Office']."</b>";
		}
		elseif (($this->search_criteria['search_type']=='offices')){
			echo "Office".$plural." Found";
		}
		else {
		    echo "Agent".$plural." Found";
		}
		echo "<hr class='flexmls_connect__sr_divider'>";
		/* End of Results*/
		
		foreach($this->fmc_accounts as $fmc_account){
			$this->print_agent($fmc_account);
		}
		
		echo '<hr class="flexmls_connect__sr_divider">';
		
		echo $this->pagination($this->current_page,$this->total_pages);

		
		$content = ob_get_contents();
		ob_end_clean();
	
		return $content;
	}
	
	/** 
	*	Outputs html to screen to draw search area, will not output if shortcode setting (search) is false.
	*	@return void
	*/
	function print_search_box(){
		
		if ($this->settings['search']!='true') 
			return;

		?>
		<form method="get" action="#">
			<div style='width:50%'>
			<input type="textbox" style="width:100%" placeholder="Name, Office, Email, Zip Code" name="contact_search" />
				<br />
				<span style='padding:8px'>
					<input name=search_type 
					<?php if ($this->search_criteria['search_type']=='offices') print " checked=checked "; ?> 
						value=offices type="radio" /> Offices 
				</span>
				<span style='padding:8px'>
					<input name=search_type 
					<?php if ($this->search_criteria['search_type']=='agents') print " checked=checked "; ?> 
						value=agents type="radio" /> Agents 
				</span>
				<br />
			<input type="submit" class=flexmls_connect__button value="Search"  />
			</div>
		</form>
		

		<?php
		return;
	}

	/**
	* Outputs html to the screen for a single agent.
	* @param $agent - A single agent or office as returned by the API
	* @return Void
	*/
	function print_agent($agent){
		global $fmc_plugin_url;
		
		
		$fmc_agent_image = isset($agent['Images'][0]['Uri']) ? $agent['Images'][0]['Uri']:false;
		if (!$fmc_agent_image)
			$fmc_agent_image = $fmc_plugin_url."/images/placeholder.png";

		$fmc_agent_name = $agent['Name'];
		
		
		if ($agent['Name'] != $agent['Office'])
			$fmc_agent_office = utf8_decode($agent['Office']);
		else 
			$fmc_agent_office = utf8_decode($agent['Mls']);
			
		//Phone
		$fmc_agent_phone=false;
		if (is_array($agent['Phones'])){
			foreach ($agent['Phones'] as $fmc_agent_phone2){
				if ($fmc_agent_phone2['Primary']){
					$fmc_agent_phone = $fmc_agent_phone2['Number'];
					break;
				}
			}
		}
		if ($fmc_agent_phone)
			$fmc_agent_phone= "ph: ".$fmc_agent_phone;

		
		//Primary Email
		$fmc_agent_email = false;
		if (is_array($agent['Emails'])){
			foreach ($agent['Emails'] as $fmc_agent_email2){
				if ($fmc_agent_email2['Primary']){
					$fmc_agent_email = $fmc_agent_email2['Address'];
					break;
				}
			}
		}

		//Get primary Website href data - $fmc_agent_website_link
		$fmc_agent_website = false;
		if (is_array($agent['Websites'])){
			foreach ($agent['Websites'] as $fmc_agent_website_check){
				if ($fmc_agent_website_check['Primary']){
					$fmc_agent_website = $fmc_agent_website_check['Uri'];
					if ($fmc_agent_website){
						$fmc_agent_website = str_replace("http://","",$fmc_agent_website);
						$fmc_agent_website = "http://".$fmc_agent_website;
					}
					break;
				}
			}
		}
		?>
			<div class=hover_container>
			<div class='hover_border' style="clear:both;">
				<img style="width:100px; margin: 3px; float: left; border: 7px double lightgray;border-radius: 5px 5px 5px 5px;" src="<?php echo $fmc_agent_image; ?>"></img>
				<div style="float:left;margin-left:10px;">
					<div style="font-weight:bold; margin-bottom:10px;">
						<span style="font-size:large;font-weight:bold;">
						<?php 
							if ($agent["UserType"]=='Office'){
								echo "<a href=".get_permalink()."?office_search={$agent['OfficeId']}>$fmc_agent_name</a>";
								}
							else {
							    echo $fmc_agent_name;
							}
						?> 
						
						</span><br />
						<span style="font-weight:bold;"><?php echo $fmc_agent_office; ?> </span><br />
					</div>
					<?php
					if ($fmc_agent_phone)
						echo "<span> $fmc_agent_phone <br /></span>";
					if ($fmc_agent_email)
						echo "<span> <a href='mailto:".$fmc_agent_email."'>".$fmc_agent_email."</a> </span><br />";
					if ($fmc_agent_website)
						echo "<span> <a href='".$fmc_agent_website."'>".$fmc_agent_website."</a></span><br />";
					?>
					</div>
			</div>
			</div>
		<?php
		
		return;
	}//End of fmc_print_agent()

	
	function pagination($current_page, $total_pages) {

		$jump_after_first = false;
		$jump_before_last = false;

		$tolerance = 3;

		$return = "	<div class='flexmls_connect__sr_pagination'>\n";

		if ($current_page != 1) {
			$return .= "		<a class=flexmls_connect__button href='". $this->make_pagination_link($current_page - 1) ."'>Previous</a>\n";
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
			$return .= "		 <a class=flexmls_connect__button href='". $this->make_pagination_link($current_page + 1) ."'>Next</a>\n";
		}
		$return .= "	</div><!-- pagination -->\n";

		return $return;

	}


	function make_pagination_link($page) {
		
			$page_conditions = $this->search_criteria;
			$page_conditions['pg'] = $page;
			return get_permalink() . '?' . http_build_query($page_conditions);
	}

	/**
	*	Sets api parameters for GetAccounts for the agent/office search widget
	*/
	function get_search_data(){
		
		$custom_search = array();		//The parameters which will get returned
		$filter_conditions = array();		//The filter conditions for the search (Will get seperated by And)
		
		//If current user has chosen a search type, use that
		$fmc_office_id = flexmlsConnect::wp_input_get_post('office_search');
		
		/* Find User Type  */
		//If an Office link was clicked on, show members in that office
		if (isset($fmc_office_id)){
			$this->search_criteria['office_search']=$fmc_office_id;
			$filter_conditions[]="UserType Eq 'Member'";
			$filter_conditions[]="OfficeId Eq '$fmc_office_id'";
		}
		elseif ($this->api_my_account['UserType'] != 'Office') {
			$this->search_criteria['search_type'] = flexmlsConnect::wp_input_get_post('search_type');
			if (!isset($this->search_criteria['search_type'])){
				$this->search_criteria['search_type']=$this->settings['search_type'];
			}
			switch($this->search_criteria['search_type']){
				case "agents":
					$filter_conditions[]="UserType Eq 'Member'";
					break;
				case "offices":
					$filter_conditions[]="UserType Eq 'Office'";
					break;
				//if search type never gets set(which should not happen), use Members
				default:
					$filter_conditions[]="UserType Eq 'Member'";
					break;
			}
		}
		else {
			$filter_conditions[]="UserType Eq 'Member'";
			$filter_conditions[]="OfficeId Eq '{$this->api_my_account['OfficeId']}'";
		}
		/* End Find User Type  */
		
		/* Get Search Filter */
		$fmc_custom_search = flexmlsConnect::wp_input_get_post('contact_search');
		
		if ($fmc_custom_search){
			//clean up the search in case a copy->paste is done.
			$fmc_custom_search = trim($fmc_custom_search," \t\n\r\0\x0B" );
			addslashes($fmc_custom_search);
			$this->search_criteria['contact_search']=$fmc_custom_search;
		}

		if (!$fmc_custom_search){
			//There is no search criteria
		}
		//Postal Code / Zip
		elseif (is_numeric($fmc_custom_search)){
			$filter_conditions[] = "PostalCode Eq '$fmc_custom_search'";
		}
		//Email
		elseif (strpos($fmc_custom_search,"@")){
			$filter_conditions[] = "Email Eq '$fmc_custom_search*'";
		}
		//Name and Office Name
		else {
			$filter_conditions[] = " (Name Eq '$fmc_custom_search*' Or Office Eq '$fmc_custom_search*')";
		}
		/* End Get Search Filter */
		
		//put all results into api parameters, and return it
		$custom_search['_filter'] = implode(" And ", $filter_conditions);
		$custom_search['_pagination'] = 1;
		$custom_search['_page'] = (flexmlsConnect::wp_input_get_post('pg')) ? flexmlsConnect::wp_input_get_post('pg') : 1;
		$custom_search['_limit'] = 5;
		
		return $custom_search;
	}
}
