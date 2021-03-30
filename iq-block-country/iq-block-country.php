<?php
/**
 * Plugin Name: iQ Block Country Lite
 * Plugin URI: https://rekow.ch
 * Version: 1.2.11.1
 * Author: Nils Rekow
 * Author URI: https://rekow.ch
 * Description: Block visitors from visiting your website and backend website based on which country their IP address is from. The Maxmind GeoIP lite database is used for looking up from which country an ip address is from.
 * License: GPL2
 * Text Domain: iq-block-country
 * Domain Path: /lang
 */

/*
 * This script uses GeoLite Country from MaxMind (http://www.maxmind.com) which is available under terms of GPL/LGPL
 */

/**
 * Copyright 2010-2020  Pascal  (email: pascal@webence.nl)
 * Copyright 2019-2021  Nils Rekow
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}


/*
 * Define constants.
 */
define('CHOSENJS', plugins_url('/js/chosen.jquery.js', __FILE__));
define('CHOSENCSS', plugins_url('/css/chosen.css', __FILE__));
define('CHOSENCUSTOM', plugins_url('/js/chosen.custom.js', __FILE__));
define('PLUGINNAME', 'iQ Block Country Lite');
define('PLUGINPATH', plugin_dir_path( __FILE__ ));

// define('GEOIP2DB_LICENSE_KEY', '');

$maxmind_license_key = get_option('blockcountry_maxmind_license_key');
if ($maxmind_license_key === false && defined('GEOIP2DB_LICENSE_KEY') && !empty(GEOIP2DB_LICENSE_KEY)) {
	$maxmind_license_key = GEOIP2DB_LICENSE_KEY;
}

define('GEOIP2DB', 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=' . $maxmind_license_key . '&suffix=tar.gz'); // Used to download GeoIP database.
define('GEOIP2DBFILE_GZIPPED', PLUGINPATH . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'GeoLite2-Country.tar.gz');
define('GEOIP2DBFILE', PLUGINPATH . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR. 'GeoLite2-Country.mmdb');

define('DBVERSION', '122');
define('VERSION', '1.2.11.1');


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
require_once 'libs/plugin-init.php';
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


/*
 * Register hooks.
 */
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

/*
 * Check if the Geo Database does not exist or is too old, and download the latest version automatically.
 */
iqblockcountry_update_GeoIP2DB();


/*
 * Clean logging database
 */
iqblockcountry_clean_db();
iqblockcountry_get_blockallowlist();


/*
 * Check if CSV export is requested
 */
if (isset($_POST['action'])) {
	$iqaction = filter_var($_POST['action'], FILTER_SANITIZE_STRING);
	
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
