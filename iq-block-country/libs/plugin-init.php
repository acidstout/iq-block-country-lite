<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}


/*
 * Try to make this plugin the first plugin that is loaded.
 * Because we output header info we don't want other plugins to send output first.
 */
function iqblockcountry_this_plugin_first() {
	$wp_path_to_this_file = preg_replace('/(.*)plugins\/(.*)$/', WP_PLUGIN_DIR . "/$2", __FILE__);
	$this_plugin = plugin_basename(trim($wp_path_to_this_file));
	$active_plugins = get_option('active_plugins');
	$this_plugin_key = array_search($this_plugin, $active_plugins);
	
	// if it's 0, it's the first plugin already, no need to continue
	if ($this_plugin_key) {
		array_splice($active_plugins, $this_plugin_key, 1);
		array_unshift($active_plugins, $this_plugin);
		update_option('active_plugins', $active_plugins);
	}
}


/*
 * Attempt on output buffering to protect against headers already send mistakes
 */
function iqblockcountry_buffer() {
	ob_start();
}


/*
 * Attempt on output buffering to protect against headers already send mistakes
 */
function iqblockcountry_buffer_flush() {
	if (ob_get_contents()) ob_end_flush();
}


/*
 * Localization
 */
function iqblockcountry_localization() {
	load_plugin_textdomain( 'iq-block-country', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
}


/*
 * Retrieves the IP address from the HTTP Headers
 */
function iqblockcountry_get_ipaddress() {
	global $ip_address;

	// Get IP address of server.
	$server_address = '';
	if (isset($_SERVER['SERVER_ADDR']) && !empty($_SERVER['SERVER_ADDR'])) {
		// Apache, nginx, ...
		$server_address = $_SERVER['SERVER_ADDR'];
	} else if (isset($_SERVER['LOCAL_ADDR']) && !empty($_SERVER['LOCAL_ADDR'])) {
		// On Window 7 IIS one must use $_SERVER['LOCAL_ADDR'] instead to get the server's IP address.
		$server_address = $_SERVER['LOCAL_ADDR'];
	} else if (isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME']) && stristr(PHP_OS, 'WIN')) {
		// Windows IIS v6 does not include $_SERVER['SERVER_ADDR']. This relies on a DNS lookup and may result in significant performance issues.
		$server_address = gethostbyname($_SERVER['SERVER_NAME']);
	}
	if ( isset($_SERVER['HTTP_CF_CONNECTING_IP']) && !empty($_SERVER['HTTP_CF_CONNECTING_IP']) ) {
		$ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'];
	} elseif ( isset($_SERVER['HTTP_X_REAL_IP']) && !empty($_SERVER['HTTP_X_REAL_IP']) ) {
		$ip_address = $_SERVER['HTTP_X_REAL_IP'];
	} elseif ( isset($_SERVER['HTTP_X_SUCURI_CLIENTIP']) && !empty($_SERVER['HTTP_X_SUCURI_CLIENTIP']) ) {
		$ip_address = $_SERVER['HTTP_X_SUCURI_CLIENTIP'];
	} elseif ( isset($_SERVER['HTTP_INCAP_CLIENT_IP']) && !empty($_SERVER['HTTP_INCAP_CLIENT_IP']) ) {
		$ip_address = $_SERVER['HTTP_INCAP_CLIENT_IP'];
	} elseif ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
		$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} elseif ( isset($_SERVER['HTTP_X_FORWARDED']) && !empty($_SERVER['HTTP_X_FORWARDED']) ) {
		$ip_address = $_SERVER['HTTP_X_FORWARDED'];
	} elseif ( isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']) ) {
		$ip_address = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( isset($_SERVER['HTTP_FORWARDED']) && !empty($_SERVER['HTTP_FORWARDED']) ) {
		$ip_address = $_SERVER['HTTP_FORWARDED'];
	} elseif ( isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) ) {
		$ip_address = $_SERVER['REMOTE_ADDR'];
	}

	$ipoverride = get_option('blockcountry_ipoverride');
	if (isset($ipoverride) && (!empty($ipoverride) && ($ipoverride != "NONE") )) {
		if ( isset($_SERVER[$ipoverride]) && !empty($_SERVER[$ipoverride])) {
			$ip_address = $_SERVER[$ipoverride];			
		}
	}
	
	// Get first ip if ip_address contains multiple addresses
	$ips = explode(',', $ip_address);
	$ip_address = trim($ips[0]);

	if ($ip_address == $server_address) {
		if ( isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) ) {
			$ip_address = $_SERVER['REMOTE_ADDR'];
		} else {
			$ip_address = "0.0.0.0";
		}
	}
	
	return $ip_address;
}


/*
 * Check if update is necessary
 */
function iqblockcountry_upgrade() {
	$dbversion = get_option('blockcountry_version');
	update_option('blockcountry_version', VERSION);

	if ($dbversion != "" && version_compare($dbversion, "1.2.10", '<') ) {
		//iqblockcountry_find_geoip_location();
		$server_addr = array_key_exists( 'SERVER_ADDR', $_SERVER ) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
		if (get_option('blockcountry_backendwhitelist') === FALSE || (get_option('blockcountry_backendwhitelist') == "")) {
			update_option('blockcountry_backendwhitelist',$server_addr . ";");
		} else {
			$tmpbackendallowlist = get_option('blockcountry_backendwhitelist');
			$ippos = strpos($tmpbackendallowlist,$server_addr);
			if ($ippos === false)
			{
				$tmpbackendallowlist .= $server_addr . ";";
				update_option('blockcountry_backendwhitelist',$tmpbackendallowlist);
			}
		}
	} elseif ($dbversion != "" && version_compare($dbversion, "1.2.5", '<') ) {
		update_option('blockcountry_blockfeed' , 'on');
	} elseif ($dbversion != "" && version_compare($dbversion, "1.2.3", '<') ) {
		update_option('blockcountry_ipoverride' , 'NONE');
	} elseif ($dbversion != "" && version_compare($dbversion, "1.2.2", '<') ) {
		//iqblockcountry_find_geoip_location();
	} elseif ($dbversion != "" && version_compare($dbversion, "1.1.44", '<') ) {
		$server_addr = array_key_exists( 'SERVER_ADDR', $_SERVER ) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
		if (get_option('blockcountry_frontendwhitelist') === false || (get_option('blockcountry_frontendwhitelist') == "")) {
			update_option('blockcountry_frontendwhitelist',$server_addr);
		}
		iqblockcountry_install_db();
	} elseif ($dbversion != "" && version_compare($dbversion, "1.1.41", '<') ) {
		//iqblockcountry_find_geoip_location();
		update_option('blockcountry_daysstatistics',30);
	} elseif ($dbversion != "" && version_compare($dbversion, "1.1.31", '<') ) {
		if (!get_option('blockcountry_blocktag')) {
			update_option('blockcountry_blocktag','on');
		}
	} elseif ($dbversion != "" && version_compare($dbversion, "1.1.19", '<') ) {
		update_option('blockcountry_blocksearch','on');
	}
	
	if ($dbversion != "" && version_compare($dbversion, "1.1.17", '<') ) {
		delete_option('blockcountry_automaticupdate');
		delete_option('blockcountry_lastupdate');
	} elseif ($dbversion != "" && version_compare($dbversion, "1.1.11", '<') ) {
		update_option('blockcountry_nrstatistics', 15);
	} elseif ($dbversion != "" && version_compare($dbversion, "1.0.10", '<') ) {
		$frontendbanlist = get_option('blockcountry_banlist');
		update_option('blockcountry_backendbanlist',$frontendbanlist);
		update_option('blockcountry_backendnrblocks', 0);
		update_option('blockcountry_frontendnrblocks', 0);
		update_option('blockcountry_header', 'on');
	} elseif ($dbversion != "" && version_compare($dbversion, "1.0.10", '=') ) {
		iqblockcountry_install_db();
		update_option('blockcountry_backendnrblocks', 0);
		update_option('blockcountry_frontendnrblocks', 0);
		update_option('blockcountry_header', 'on');
	} elseif ($dbversion == "") {
		iqblockcountry_install_db();
		add_option( "blockcountry_dbversion", DBVERSION );
		update_option('blockcountry_blockfrontend' , 'on');
		update_option('blockcountry_version',VERSION);
		update_option('blockcountry_backendnrblocks', 0);
		update_option('blockcountry_frontendnrblocks', 0);
		update_option('blockcountry_header', 'on');
		$frontendbanlist = get_option('blockcountry_banlist');
		update_option('blockcountry_backendbanlist', $frontendbanlist);
	}
	
	iqblockcountry_update_db_check();
}
