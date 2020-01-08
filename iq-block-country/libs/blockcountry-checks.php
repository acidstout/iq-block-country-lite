<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

function iqblockcountry_check_ipaddress($ip_address) {
	if (!class_exists('GeoIP')) {
		include_once 'geoip.php';
	}
	
	$country = 'Unknown';
	
	if ((is_file ( GEOIP2DBFILE))) {
		if (iqblockcountry_is_valid_ipv4($ip_address) || iqblockcountry_is_valid_ipv6($ip_address)) {
			if (class_exists('GeoIp2\\Database\\Reader')) {
				try {
					$blockreader = new GeoIp2\Database\Reader( GEOIP2DBFILE );
					$blockrecord = $blockreader->country($ip_address);
					$country = $blockrecord->country->isoCode;
				} catch(Exception $e) {
					$country = 'Unknown';
				}
			}
		}
	}
	
	if (empty($country) || is_null($country)) {
		$country = 'Unknown';
	}
	
	//error_log('Detected country: ' . print_r($country, true));
	
	return $country;
}


/*
 *  Check country against bad countries, whitelist and blacklist
 */
function iqblockcountry_check($country, $badcountries, $ip_address) {
	/* Set default blocked status and get all options */
	$blocked = false; 
	$blockedpage = get_option('blockcountry_blockpages');
	$pagesbanlist = get_option( 'blockcountry_pages' );
	
	if (!is_array($pagesbanlist)) {
		$pagesbanlist = array();
	}
		
	if (get_option( 'blockcountry_blockpages_inverse' ) == 'on') {
		$pages = get_pages();
		$all_pages = array();
		
		foreach ( $pages as $page ) {
			$all_pages[$page->ID] = $page->ID; 
		}
		
		$blockedpages = array_diff($all_pages, $pagesbanlist);
	} else {
		$blockedpages = $pagesbanlist;
	}

	$blockedcategory = get_option('blockcountry_blockcategories');
	$blocktags = get_option('blockcountry_blocktags');
	$blockedposttypes = get_option('blockcountry_blockposttypes');
	$blockedtag = get_option('blockcountry_blocktag');
	$blockedfeed = get_option('blockcountry_blockfeed');
	$postid = get_the_ID();

	global $feblacklistip,
		$feblacklistiprange4,
		$feblacklistiprange6,
		$fewhitelistip,
		$fewhitelistiprange4,
		$fewhitelistiprange6;
	
	global $beblacklistip,
		$beblacklistiprange4,
		$beblacklistiprange6,
		$bewhitelistip,
		$bewhitelistiprange4,
		$bewhitelistiprange6;
	
	$backendbanlistip = unserialize(get_option('blockcountry_backendbanlistip'));
	$blockredirect = get_option ( 'blockcountry_redirect');
	
	/* Block if user is in a bad country from frontend or backend. Unblock may happen later */
	if (is_array ( $badcountries ) && in_array ( $country, $badcountries )) {
		$blocked = true;
		global $backendblacklistcheck;
		$backendblacklistcheck = true;
	}

	global $blockcountry_is_login_page,
		$blockcountry_is_xmlrpc;
	

	/* Check if requested url is not login page. Else check against frontend whitelist/blacklist. */
	if (!($blockcountry_is_login_page) && !(is_admin()) && !($blockcountry_is_xmlrpc)) {
		if (iqblockcountry_validate_ip_in_list($ip_address, $feblacklistiprange4, $feblacklistiprange6, $feblacklistip)) {
			 $blocked = true;
		}
		
		if (iqblockcountry_validate_ip_in_list($ip_address, $fewhitelistiprange4, $fewhitelistiprange6, $fewhitelistip)) {
			$blocked = FALSE;
		}
	}
	
	
	if ($blockcountry_is_login_page || is_admin() || $blockcountry_is_xmlrpc) {
		if (is_array($backendbanlistip) &&  in_array($ip_address, $backendbanlistip)) {
			$blocked = true;
			global $apiblacklist;
			$apiblacklist = true;
		}
		
		if (iqblockcountry_validate_ip_in_list($ip_address, $beblacklistiprange4, $beblacklistiprange6, $beblacklistip)) {
			 $blocked = true;
		}
		
		if (iqblockcountry_validate_ip_in_list($ip_address, $bewhitelistiprange4, $bewhitelistiprange6, $bewhitelistip)) {
			$blocked = false;
		}
		
		if (iqblockcountry_is_adminajax() && get_option('blockcountry_adminajax')) {
			$blocked = false;
		}
	}

	if ($blockedposttypes == 'on') {
		$blockedposttypes = get_option('blockcountry_posttypes');
		
		if (is_array($blockedposttypes) && in_array(get_post_type( $postid ), $blockedposttypes) && ((is_array ( $badcountries ) && in_array ( $country, $badcountries ) || (iqblockcountry_validate_ip_in_list($ip_address, $feblacklistiprange4, $feblacklistiprange6, $feblacklistip))))) {
			$blocked = TRUE;
			if (iqblockcountry_validate_ip_in_list($ip_address, $fewhitelistiprange4, $fewhitelistiprange6, $fewhitelistip)) {
				$blocked = FALSE;
			}
		} else {
			$blocked = FALSE;
		}
	}
	
	if (is_page() && $blockedpage == 'on') {
		$post = get_post();
		
		if (is_page($blockedpages) && !empty($blockedpages) && ((is_array ( $badcountries ) && in_array ( $country, $badcountries ) || (iqblockcountry_validate_ip_in_list($ip_address, $feblacklistiprange4, $feblacklistiprange6, $feblacklistip))))) {
			$blocked = TRUE;
			if (iqblockcountry_validate_ip_in_list($ip_address, $fewhitelistiprange4, $fewhitelistiprange6, $fewhitelistip)) {
				$blocked = FALSE;
			}
		} else {
			$blocked = FALSE;
		}
	}
	
	if (is_single() && $blockedcategory == 'on') {
		$blockedcategories = get_option('blockcountry_categories');
		
		if (!is_array($blockedcategories)) {
			$blockedcategories = array();
		}
		
		$post_categories = wp_get_post_categories( $postid );
		$flagged = FALSE;
		
		foreach ($post_categories as $key => $value) {
			if (in_array($value,$blockedcategories)) {
				if (is_single() && ((is_array ( $badcountries ) && in_array ( $country, $badcountries ) || (iqblockcountry_validate_ip_in_list($ip_address, $feblacklistiprange4, $feblacklistiprange6, $feblacklistip))))) {
					$flagged = TRUE;
					if (iqblockcountry_validate_ip_in_list($ip_address,$fewhitelistiprange4,$fewhitelistiprange6,$fewhitelistip)) {
						$flagged = FALSE;
					}
				}
			}			
		}
		
		if ($flagged) {
			$blocked = true;
		} else {
			$blocked = false;
		}
	}

	if (is_single() && $blocktags == 'on') {
		$previousblock = $blocked;
		$blockedtags = get_option('blockcountry_tags');
		
		if (!is_array($blockedtags)) {
			$blockedtags = array();
		}
		
		$post_tags = get_the_tags($postid);
		if (empty($post_tags)) {
			$post_tags = array();
		}
		
		$flagged = false;
		
		foreach ($post_tags as $tag) {
			if (in_array($tag->term_id, $blockedtags)) {
				if (is_single() && ((is_array ( $badcountries ) && in_array ( $country, $badcountries ) || (iqblockcountry_validate_ip_in_list($ip_address, $feblacklistiprange4, $feblacklistiprange6, $feblacklistip))))) {
					$flagged = true;
					if (iqblockcountry_validate_ip_in_list($ip_address, $fewhitelistiprange4, $fewhitelistiprange6, $fewhitelistip)) {
						$flagged = false;
					}
				}
			}			
		}
		
		if ($flagged || $previousblock == true) {
			$blocked = true;
		} else {
			$blocked = false;
		}
	}

	
	if (is_category() && $blockedcategory == 'on') {
		$flagged = false;
		$blockedcategories = get_option('blockcountry_categories');
		
		if (is_category($blockedcategories) && ((is_array ( $badcountries ) && in_array ( $country, $badcountries ) || (iqblockcountry_validate_ip_in_list($ip_address, $feblacklistiprange4, $feblacklistiprange6, $feblacklistip))))) {
			$flagged = true;
		}
		
		if (iqblockcountry_validate_ip_in_list($ip_address, $fewhitelistiprange4, $fewhitelistiprange6, $fewhitelistip)) {
			$flagged = false;
		}
		
		if ($flagged) {
			$blocked = true;
		} else {
			$blocked = false;
		}
	}

	
	if (is_tag() && $blockedtag == 'on') {
		$flagged = false;
		
		if ((is_array ( $badcountries ) && in_array ( $country, $badcountries ) || (iqblockcountry_validate_ip_in_list($ip_address, $feblacklistiprange4, $feblacklistiprange6, $feblacklistip)))) {
			$flagged = true;
		}
		
		if (iqblockcountry_validate_ip_in_list($ip_address, $fewhitelistiprange4, $fewhitelistiprange6, $fewhitelistip)) {
			$flagged = false;
		}
		
		if ($flagged) {
			$blocked = true;
		} else {
			$blocked = false;
		}
	} elseif (is_tag() && $blockedtag == false) {
		$blocked = false;
	}

	if (is_feed()) {
		if ($blockedfeed == false) {
			$blocked = false;
		} else {
			$flagged = false;
			if ((is_array ( $badcountries ) && in_array ( $country, $badcountries ) || (iqblockcountry_validate_ip_in_list($ip_address, $feblacklistiprange4, $feblacklistiprange6, $feblacklistip)))) {
				$flagged = true;
			}
			
			if (iqblockcountry_validate_ip_in_list($ip_address, $fewhitelistiprange4, $fewhitelistiprange6, $fewhitelistip)) {
				$flagged = false;
			}
			
			if ($flagged) {
				$blocked = true;
			} else {
				$blocked = false;
			}
		}
	}
	
	if (is_home() && (get_option('blockcountry_blockhome')) == FALSE && $blockedcategory == 'on') {
		$blocked = false;
	}
	
	if (is_page($blockredirect) && ($blockredirect != 0) && !(empty($blockredirect))) {
		$blocked = false;
	}
	
	$allowse = get_option('blockcountry_allowse');
	if (!$blockcountry_is_login_page && isset ($_SERVER['HTTP_USER_AGENT']) && iqblockcountry_check_searchengine($_SERVER['HTTP_USER_AGENT'], $allowse)) {
		$blocked = false;
	}
	
	if (is_search() && (get_option('blockcountry_blocksearch')) == false) {
		$blocked = false;
	}

	return $blocked;
}

/*
 * 
 * Does the real check of visitor IP against MaxMind database or the GeoAPI
 * 
 */
function iqblockcountry_CheckCountryBackEnd() {
	$ip_address = iqblockcountry_get_ipaddress();
	$country = iqblockcountry_check_ipaddress($ip_address);
	global $blockcountry_is_login_page,$blockcountry_is_xmlrpc;
	
	if (($blockcountry_is_login_page || is_admin() || $blockcountry_is_xmlrpc) && get_option('blockcountry_blockbackend') == 'on') { 
		$banlist = get_option( 'blockcountry_backendbanlist' );
		if (!is_array($banlist)) {
			$banlist = array();
		}
		
		if (get_option( 'blockcountry_backendbanlist_inverse' ) == 'on') {
			$all_countries = array_keys(iqblockcountry_get_isocountries());
			$badcountries = array_diff($all_countries, $banlist);
		} else {
			$badcountries = $banlist;
		}
	}

	$blocklogin = get_option ( 'blockcountry_blocklogin' );
	if ( ((is_user_logged_in()) && ($blocklogin != 'on')) || (!(is_user_logged_in())) )  {			

		/* Check ip address against banlist, whitelist and blacklist */
		if (iqblockcountry_check($country, $badcountries, $ip_address)) {
			if (($blockcountry_is_login_page || is_admin() || $blockcountry_is_xmlrpc) && get_option('blockcountry_blockbackend') == 'on') {
				$blocked = get_option('blockcountry_backendnrblocks');
				
				if (empty($blocked)) {
					$blocked = 0;
				}
				
				$blocked++;
				
				update_option('blockcountry_backendnrblocks', $blocked);
				
				global $apiblacklist, $backendblacklistcheck, $debughandled;
				
				if (!get_option('blockcountry_logging')) {
					if (!$apiblacklist) {
						iqblockcountry_logging($ip_address, $country, 'B');
						iqblockcountry_debug_logging($ip_address, $country, 'BB');
					} elseif ($backendblacklistcheck && $apiblacklist) {
						iqblockcountry_logging($ip_address, $country, 'T');
						iqblockcountry_debug_logging($ip_address, $country, 'TB');
					} else {
						iqblockcountry_logging($ip_address, $country, 'A');
						iqblockcountry_debug_logging($ip_address, $country, 'AB');
					}
				}
			} else {
				$blocked = get_option('blockcountry_frontendnrblocks');
				
				if (empty($blocked)) {
					$blocked = 0;
				}
				
				$blocked++;
				
				update_option('blockcountry_frontendnrblocks', $blocked);
				
				if (!get_option('blockcountry_logging')) {
					iqblockcountry_logging($ip_address, $country, 'F');
					iqblockcountry_debug_logging($ip_address, $country, 'FB');
				}
			}
			
			$blockmessage = get_option ( 'blockcountry_blockmessage' );
			$blockredirect = get_option ( 'blockcountry_redirect');
			$blockredirect_url = get_option ( 'blockcountry_redirect_url');
			$header = get_option('blockcountry_header');
			
			if (!empty($header) && ($header)) {
				// Prevent as much as possible that this error message is cached:
				header('Cache-Control: no-store, no-cache, must-revalidate');
				header('Cache-Control: post-check=0, pre-check=0', false);
				header('Pragma: no-cache');
				header('Expires: Sat, 26 Jul 2012 05:00:00 GMT'); 
				header('HTTP/1.1 403 Forbidden');
			}
			
			if (!empty($blockredirect_url)) {
				header('Location: ' . $blockredirect_url);
			} elseif (!empty($blockredirect) && $blockredirect != 0) {
				$redirecturl = get_permalink($blockredirect);
				header('Location: '. $redirecturl);
			}
			
			// Display block message
			echo $blockmessage;
			exit();
		} else {
			iqblockcountry_debug_logging($ip_address, $country,'NB');
		}
	} else {
		iqblockcountry_debug_logging($ip_address, $country, 'NB');
	}
}


/*
 * 
 * Does the real check of visitor IP against MaxMind database or the GeoAPI FrontEnd
 * 
 */
function iqblockcountry_CheckCountryFrontEnd() {
	$ip_address = iqblockcountry_get_ipaddress();
	$country = iqblockcountry_check_ipaddress($ip_address);
	$banlist = get_option( 'blockcountry_banlist' );
	
	if (!is_array($banlist)) {
		$banlist = array();
	}
	
	if (get_option( 'blockcountry_banlist_inverse' ) == 'on') {
		$all_countries = array_keys(iqblockcountry_get_isocountries());
		$badcountries = array_diff($all_countries, $banlist);
	} else {
		$badcountries = $banlist;
	}

	$blocklogin = get_option ( 'blockcountry_blocklogin' );
	if ( ((is_user_logged_in()) && ($blocklogin != 'on')) || (!(is_user_logged_in())) )  {			

		/* Check ip address against banlist, whitelist and blacklist */
		if (iqblockcountry_check($country, $badcountries, $ip_address)) {
			$blocked = get_option('blockcountry_frontendnrblocks');
			
			if (empty($blocked)) {
				$blocked = 0;
			}
			
			$blocked++;
			
			update_option('blockcountry_frontendnrblocks', $blocked);
			
			if (!get_option('blockcountry_logging')) {
				iqblockcountry_logging($ip_address, $country, 'F');
				iqblockcountry_debug_logging($ip_address, $country, 'FB');
			}
				
			$blockmessage = get_option ( 'blockcountry_blockmessage' );
			$blockredirect = get_option ( 'blockcountry_redirect');
			$blockredirect_url = get_option ( 'blockcountry_redirect_url');
			$header = get_option('blockcountry_header');
			
			if (!empty($header) && ($header)) {
				// Prevent as much as possible that this error message is cached:
				header('Cache-Control: no-store, no-cache, must-revalidate');
				header('Cache-Control: post-check=0, pre-check=0', false);
				header('Pragma: no-cache');
				header('Expires: Sat, 26 Jul 2012 05:00:00 GMT'); 
				header('HTTP/1.1 403 Forbidden');
			}
			
			if (!empty($blockredirect_url)) {
				header('Location: ' . $blockredirect_url);
			} elseif (!empty($blockredirect) && $blockredirect != 0) {
				$redirecturl = get_permalink($blockredirect);
				header('Location: ' . $redirecturl);
			}
			
			// Display block message
			echo $blockmessage;
			exit ();
		} else {
			iqblockcountry_debug_logging($ip_address, $country, 'NB');
		}
	} else {
		iqblockcountry_debug_logging($ip_address, $country, 'NB');
	}
}


/**
 * Check if xmlrpc.php is hit.
 * @return bool
 */
function iqblockcountry_is_xmlrpc() {
	return defined('XMLRPC_REQUEST') && XMLRPC_REQUEST;
}


/*
 * Check for active caching plugins
 */
function iqblockcountry_is_caching_active() {
	$found = false;

	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	
	if ( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) ) {
		$found = true;
	}
	
	if ( is_plugin_active( 'hyper-cache/plugin.php' ) ) {
		$found = true;
	} 

	if (get_option('blockcountry_blockfrontend') == false) {
		$found = false;
	}
	
	return $found;
}


/*
 * Check if page is the login page
 */
function iqblockcountry_is_login_page() {
	$found = false;
	$pos2 = false;
	
	include_once ABSPATH . 'wp-admin/includes/plugin.php'; 
	if ( is_plugin_active( 'all-in-one-wp-security-and-firewall/wp-security.php' ) ) {
		$aio = get_option('aio_wp_security_configs');
		if (!empty($aio) && !(empty($aio['aiowps_login_page_slug']))) {
			$pos2 = strpos( $_SERVER['REQUEST_URI'], $aio['aiowps_login_page_slug'] );
		}
	}
	
	if ( is_plugin_active( 'lockdown-wp-admin/lockdown-wp-admin.php' ) ) {
		$ld = get_option('ld_login_base');
		if (!empty($ld)) {
			$pos2 = strpos( $_SERVER['REQUEST_URI'], $ld);
		}
	} 
	
	if ( is_plugin_active( 'wp-simple-firewall/icwp-wpsf.php' ) ) {
		$wpsf = get_option('icwp_wpsf_loginprotect_options');
		if (!empty($wpsf['rename_wplogin_path'])) {
			$pos2 = strpos( $_SERVER['REQUEST_URI'], $wpsf['rename_wplogin_path'] );
		}
	} 

	if ( is_plugin_active( 'rename-wp-login/rename-wp-login.php' ) ) {
		$rwpl = get_option('rwl_page');
		if (!empty($rwpl)) {
			$pos2 = strpos( $_SERVER['REQUEST_URI'], $rwpl );
		}
	} 
	
	if ( is_plugin_active( 'wps-hide-login/wps-hide-login.php' ) ) {
		$whlpage = get_option('whl_page');
		if (!empty($whlpage)) {
			$pos2 = strpos( $_SERVER['REQUEST_URI'], $whlpage );
		}
	} 
	
	$pos = strpos( $_SERVER['REQUEST_URI'], 'wp-login' );
	
	if ($pos !== false) {
		$found = true;
	} elseif ($pos2 !== false) {
		$found = true;
	}
	
	return $found;
}


/*
 * Check if page is within wp-admin page
 */
function iqblockcountry_is_admin() {
	if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/') !== false) {
		return true;
	}
	
	return false;
}


/*
 * Check if page is within admin-ajax url.
 */
function iqblockcountry_is_adminajax() {
	if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/admin-ajax.php') !== false) {
		return true;
	}
	
	return false;
}
