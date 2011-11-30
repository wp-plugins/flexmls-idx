<?php

class flexmlsConnectPageOAuthLogin {

	private $temp_api;

	function pre_tasks($tag) {
		global $fmc_special_page_caught;
		global $fmc_api;
		
		$code = flexmlsConnect::wp_input_get('code');
		
		if (!empty($code)) {
			$this->temp_api = flexmlsConnect::new_oauth_client();
			$grant = $this->temp_api->Grant($code);
			if ($grant) {
				$_SESSION['fmc_oauth_logged_in'] = true;
				wp_redirect( flexmlsConnect::make_nice_tag_url('my') );
				exit;
			}
		}
		
		
		$fmc_special_page_caught['type'] = "oauth-login";
		$fmc_special_page_caught['page-title'] = flexmlsConnect::make_nice_address_title($listing);
		$fmc_special_page_caught['post-title'] = flexmlsConnect::make_nice_address_title($listing);
		$fmc_special_page_caught['page-url'] = flexmlsConnect::make_nice_address_url($listing);
	}


	function generate_page() {
		global $fmc_api;
		global $fmc_special_page_caught;
		
	
		// if we got this far, it's because of an error with the OAuth grant
		
		ob_start();
		
		// disable display of the H1 entry title on this page only
		echo "<style type='text/css'>\n  .entry-title { display:none; }\n</style>\n\n\n";
		
		echo "Error with OAuth access grant:<br><br>\n\n";
		
		echo "Code: ". $this->temp_api->last_error_code ."<br>\n";
		echo "Message: ". $this->temp_api->last_error_mess ."<br>\n";
		

		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}


}
