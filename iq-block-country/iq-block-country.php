<?php
/*
 Plugin Name: iQ Block Country Lite
 Plugin URI: https://www.webence.nl/plugins/iq-block-country-the-wordpress-plugin-that-blocks-countries-for-you/
 Version: 1.2.6.1
 Author: Pascal, nrekow
 Author URI: https://www.webence.nl/
 Description: Block visitors from visiting your website and backend website based on which country their IP address is from. The Maxmind GeoIP lite database is used for looking up from which country an ip address is from.
 License: GPL2
 Text Domain: iq-block-country
 Domain Path: /lang
 */

/* This script uses GeoLite Country from MaxMind (http://www.maxmind.com) which is available under terms of GPL/LGPL */

/*  Copyright 2010-2019  Pascal  (email: pascal@webence.nl)
    Copyright 2019       nrekow

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


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
	$dbversion = get_option( 'blockcountry_version' );
	update_option('blockcountry_version',VERSION);
	
	if ($dbversion != "" && version_compare($dbversion, "1.2.5", '<') ) {
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
		update_option('blockcountry_backendbanlist',$frontendbanlist);
	}
	
	iqblockcountry_update_db_check();
}


/*
 * Main plugin.
 */
define('CHOSENJS', plugins_url('/js/chosen.jquery.js', __FILE__));
define('CHOSENCSS', plugins_url('/css/chosen.css', __FILE__));
define('CHOSENCUSTOM', plugins_url('/js/chosen.custom.js', __FILE__));
define('PLUGINPATH', plugin_dir_path( __FILE__ ));
define('GEOIP2DB', 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz'); // Used to display download location.
define('GEOIP2DBFILE', PLUGINPATH . '/db/GeoLite2-Country.mmdb');
define('VERSION', '1.2.5');
define('DBVERSION', '122');


/*
 * Whoisip extension
 * @author nrekow
 */
define('JQUERYUICSS', get_home_url() . '/' . WPINC . '/css/jquery-ui-dialog.min.css');
define('WHOISCSS', plugins_url('/css/whoisip.css', __FILE__));
define('WHOISIPJS', plugins_url('/js/whoisip.js', __FILE__));


/*
 * Include libraries
 */
require_once 'libs/blockcountry-geoip.php';
require_once 'libs/blockcountry-checks.php';
require_once 'libs/blockcountry-settings.php';
require_once 'libs/blockcountry-validation.php';
require_once 'libs/blockcountry-logging.php';
require_once 'libs/blockcountry-search-engines.php';
require_once 'vendor/autoload.php';

global $apiblacklist;
$apiblacklist = false;
$backendblacklistcheck = false;

$blockcountry_is_login_page = iqblockcountry_is_login_page();
$blockcountry_is_xmlrpc = iqblockcountry_is_xmlrpc();

register_activation_hook(__file__, 'iqblockcountry_this_plugin_first');
register_activation_hook(__file__, 'iqblockcountry_set_defaults');
register_uninstall_hook(__file__, 'iqblockcountry_uninstall');


if (!function_exists('is_user_logged_in')) {
	include ABSPATH . WPINC . '/pluggable.php';
}


if (is_user_logged_in() && is_admin()) {
	// Check if upgrade is necessary
	iqblockcountry_upgrade();
}

/* Check if the Geo Database does not exist or is too old, and download the latest version automatically. */
iqblockcountry_update_GeoIP2DB();


/* Clean logging database */
iqblockcountry_clean_db();
iqblockcountry_get_blackwhitelist();


if (isset($_POST['action'])) {
	$iqaction = filter_var($_POST['action'],FILTER_SANITIZE_STRING);
	
	if ($iqaction == 'csvoutput') {
		if (is_user_logged_in() && is_admin() && check_admin_referer( 'iqblockcountrycsv' )) {
			global $wpdb;
			
			$output = '';
			$table_name = $wpdb->prefix . 'iqblock_logging';
			$format = get_option('date_format') . ' ' . get_option('time_format');
			
			foreach ($wpdb->get_results( "SELECT * FROM $table_name ORDER BY datetime ASC" ) as $row) {
				$datetime = strtotime($row->datetime);
				$mysqldate = date($format, $datetime);
				$output .= '"' . $mysqldate . '"' . ';"' . $row->ipaddress . '";"' . $row->url . '"'. "\n";
			}
			
			$iqtempvalue = preg_replace('/[^A-Za-z0-9]/', '', get_bloginfo());
			$filename = $iqtempvalue . '-iqblockcountry-logging-export.csv';
			
			header('Content-type: text/csv');
			header('Content-Disposition: attachment; filename=' . $filename);
			header('Pragma: no-cache');
			header('Expires: 0');
			
			echo $output;
			exit();
		}
	}
}


/*
 * Add WordPress actions and filters
 */
$ip_address = iqblockcountry_get_ipaddress();
$country = iqblockcountry_check_ipaddress($ip_address);
iqblockcountry_debug_logging($ip_address, $country, '');


function iq_add_my_scripts() {
	$iqscreen = get_current_screen();
	if ( $iqscreen->id == 'settings_page_iq-block-country/libs/blockcountry-settings' ) {
		// Scripts
		wp_enqueue_script( 'chosen', CHOSENJS, array( 'jquery' ), false, true );
		wp_enqueue_script( 'custom', CHOSENCUSTOM, array( 'jquery', 'chosen' ), false, true );
		wp_enqueue_script( 'whoisip', WHOISIPJS, array( 'jquery', 'jquery-ui-core', 'jquery-ui-button', 'jquery-ui-dialog' ), false, true );
	}
}
add_action( 'admin_enqueue_scripts', 'iq_add_my_scripts' );


/*
 * Check first if users want to block the backend.
 */
if (($blockcountry_is_login_page || is_admin() || $blockcountry_is_xmlrpc) && get_option('blockcountry_blockbackend') == 'on') {
	add_action ( 'init', 'iqblockcountry_checkCountryBackEnd', 1 );
} elseif ((!$blockcountry_is_login_page && !is_admin() && !$blockcountry_is_xmlrpc) && get_option('blockcountry_blockfrontend') == 'on') {
	add_action ( 'wp', 'iqblockcountry_checkCountryFrontEnd', 1 );
} else {
	$ip_address = iqblockcountry_get_ipaddress();
	$country = iqblockcountry_check_ipaddress($ip_address);
	iqblockcountry_debug_logging($ip_address, $country, 'NH');
}

add_action ( 'admin_init', 'iqblockcountry_localization');
add_action ( 'admin_menu', 'iqblockcountry_create_menu' );
add_filter ( 'update_option_blockcountry_debuglogging', 'iqblockcountry_blockcountry_debuglogging', 10, 2);
add_filter ( 'add_option_blockcountry_debuglogging', 'iqblockcountry_blockcountry_debuglogging', 10, 2);
if (get_option('blockcountry_buffer') == 'on') {
	add_action ( 'init', 'iqblockcountry_buffer',1);
	add_action ( 'shutdown', 'iqblockcountry_buffer_flush');
}