<?php

class flexmlsConnectPageLogout {

	function pre_tasks($tag) {

		$_SESSION['fmc_oauth_logged_in'] = false;
		wp_redirect( get_home_url() );
		exit;
		
	}


	function generate_page() {
		return null;
	}


}
