<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

// Check if MaxMind license key has been set, if not so display notification.
global $maxmind_license_key;
if (empty($maxmind_license_key) || $maxmind_license_key === false) {
	add_action( 'admin_notices', 'iq_missing_maxmind_license_key_notice' );
}


// Check if the Geo Database exists, if not so display notification.
if (!is_file(GEOIP2DBFILE)) {
	if (!iqblockcountry_update_GeoIP2DB()) {
		add_action( 'admin_notices', 'iq_missing_db_notice' );
	}
}

// Check if caching plugins are active, if so display notice.
if (iqblockcountry_is_caching_active()) {
	add_action( 'admin_notices', 'iq_cachingisactive_notice' );
}


/**
 * Check if an update of the GeoLite IP database is necessarry.
 * 
 * @return boolean
 */
function iqblockcountry_update_GeoIP2DB($updateRequired = false) {
	global $maxmind_license_key;
	
	// Exit if there's no license key.
	if (empty($maxmind_license_key)) {
		return false;
	}
	
	if (is_file(GEOIP2DBFILE)) {
		$iqfiledate = filemtime(GEOIP2DBFILE);
		$iq3months = time() - 3 * 31 * 86400;
		
		if ($iqfiledate < $iq3months || $updateRequired !== false) {
			// GeoLite IP database is too old.
			$updateRequired = true;
			if (is_file(GEOIP2DBFILE_GZIPPED)) {
				unlink(GEOIP2DBFILE_GZIPPED);
			}
		}
	} else {
		// GeoLite IP database does not exist.
		$updateRequired = true;
	}
	
	// Update of GeoLite IP database is required.
	if ($updateRequired) {
		// If gz-archive exists, delete it to download the latest version.
		if (is_file(GEOIP2DBFILE_GZIPPED)) {
			if (!unlink(GEOIP2DBFILE_GZIPPED)) {
				error_log('Could not delete gzipped GeoLite IP database. Maybe permission error?');
				return false;
			}
		}
		
		// If download failed, show error.
		error_log('Downloading ' . GEOIP2DB);
		$resp = DownloadGeoIP2DBfile();
		if ($resp != 200) {
			error_log('Download of gzipped GeoLite IP database failed with error-code ' . $resp . '. Please try to manually update the database.');
			return $resp;
		}
		
		error_log('Found gzipped GeoLite IP database.');
		// If gz-file exists, try to unpack it.
		if (UnpackGeoIP2DBfile() !== false) {
			return true;
		}
	}
	
	return false;
}


/**
 *	Download GeoLite2 database. 
 *
 *  @author nrekow
 */
function DownloadGeoIP2DBfile() {
	$ch = curl_init(GEOIP2DB);
	$curl_response_code = -1;
	
	// Clear PHP's folder/file status cache.
	clearstatcache();
	
	// Check if parent folder is writable.
	if (is_writable(PLUGINPATH)) {
		// Check if destination folder doesn't exist, and create it.
		if (!file_exists(PLUGINPATH . DIRECTORY_SEPARATOR . 'db') && !is_dir(PLUGINPATH . DIRECTORY_SEPARATOR . 'db')) {
			mkdir(PLUGINPATH . DIRECTORY_SEPARATOR. 'db');
		}

		// Open destination file for writing binary.
		try {
			$fp = fopen(GEOIP2DBFILE_GZIPPED, 'wb');
			
			// Download file and write it to destination file.
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			if (curl_exec($ch) !== false) {
				$curl_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				error_log('HTTP response code: ' . $curl_response_code);
			} else {
				// Custom code to define server is unavailable.
				$curl_response_code = -1;
			}
			curl_close($ch);
			
			// Close destination file.
			fclose($fp);
		} catch (Exception $e) {
			error_log($e->getMessage());
		}
		
		return $curl_response_code;
	}
	
	return false;
}


/**
 * Unpack gzipped GeoLite2 database.
 * 
 * @author nrekow
 * @return boolean
 */
function UnpackGeoIP2DBfile() {
	// Unzip downloaded file.
	if (is_file(GEOIP2DBFILE_GZIPPED)) {
		$archive = new PharData(GEOIP2DBFILE_GZIPPED);
		foreach($archive as $file) {
			$list = scandir($file);
			foreach ($list as $entry) {
				if ($entry == 'GeoLite2-Country.mmdb') {
					$filename = $file . DIRECTORY_SEPARATOR . $entry;
					
					// Open source file for reading binary.
					$fr = fopen($filename, 'rb');
					
					// Open destination file for writing binary.
					$fw = fopen(GEOIP2DBFILE, 'wb');
					
					// Read 32k blocks from source file and write them into destination file. 
					while (!feof($fr)) {
						$buffer = fread($fr, 32768);
						fwrite($fw, $buffer);
					}
					
					// Close file handles.
					fclose($fw);
					fclose($fr);
					
					// Delete gz-file.
					unlink(GEOIP2DBFILE_GZIPPED);
					
					return true;
				}
			}
		}
		
		// Another way unpacking a gzip-archive.		
		/*
		$in_file = gzopen(GEOIP2DBFILE_GZIPPED, 'rb');
		$out_file = fopen(GEOIP2DBFILE, 'wb');
		 
		// Keep repeating until the end of the input file
		while (!gzeof($in_file)) {
			// Read buffer-size bytes
			// Both fwrite and gzread and binary-safe
			fwrite($out_file, gzread($in_file, 32768));
		}
		 
		// Files are done, close files
		fclose($out_file);
		gzclose($in_file);
		*/
	}
	
	return false;
}


/*
 * Display missing MaxMind license key notification.
 */
function iq_missing_maxmind_license_key_notice() {
	?><div class="notice notice-error">
		<h3><?php echo PLUGINNAME;?></h3>
		<p><?php _e('Missing MaxMind GeoIP2 license key. Please set a valid license key in the <a href="' . site_url() . '/wp-admin/options-general.php?page=iq-block-country/libs/blockcountry-settings.php&tab=tools">plugin settings</a> in order to download the latest GeoIP2 database.', 'iq-block-country');?></p>
		<p><?php _e('For more detailed instructions take a look at the documentation.', 'iq-block-country');?></p>
	</div><?php
}

/*
 * Display missing database notification.
 */
function iq_missing_db_notice() {
	if (!is_file(GEOIP2DBFILE)) {
		?><div class="notice notice-error">
			<h3><?php echo PLUGINNAME;?></h3>
			<p><?php _e('The MaxMind GeoIP2 database does not exist.', 'iq-block-country');?></p>
			<p><?php
				_e('We try to download it automatically and reload this page afterwards to have changes in effect, but in case it fails, please download the database from: ' , 'iq-block-country');
				echo '<a href="' . GEOIP2DB . '" target="_blank">' . GEOIP2DB . '</a> ';
				_e('unzip the file and afterwards upload the GeoLite2-Country.mmdb file to the following location: ' , 'iq-block-country');
				?><b><?php echo GEOIP2DBFILE;?></b>
			</p>
			<p><?php _e('For more detailed instructions take a look at the documentation.', 'iq-block-country');?></p>
		</div><?php
	}
}


function iq_failed_db_download() {
	?><div class="notice notice-error">
		<h3><?php echo PLUGINNAME;?></h3>
		<p><?php _e('The MaxMind GeoIP2 database does not exist.', 'iq-block-country');?></p>
		<p><?php
			_e('We tried to download it automatically, but it failed. Please download the database from: ' , 'iq-block-country');
			echo '<a href="' . GEOIP2DB . '" target="_blank">' . GEOIP2DB . '</a> ';
			_e('unzip the file and afterwards upload the GeoLite2-Country.mmdb file to the following location: ' , 'iq-block-country');
			?><b><?php echo GEOIP2DBFILE;?></b>
		</p>
		<p><?php _e('For more detailed instructions take a look at the documentation.', 'iq-block-country');?></p>
	</div><?php
}

/*
 * Display missing database notification.
 */
function iq_cachingisactive_notice() {
	?><div class="notice notice-warning is-dismissible">
		<h3><?php echo PLUGINNAME;?></h3>
		<p><?php _e('A caching plugin appears to be active on your WordPress installation.', 'iq-block-country'); ?></p>
		<p><?php _e('Caching plugins do not always cooperate nicely together with the <?php echo PLUGINNAME;?> plugin which may lead to non blocked visitors getting a cached banned message or page.', 'iq-block-country'); ?></p>
		<p><?php _e('For more information visit the following page:','iq-block-country'); ?> <a target="_blank"href="https://www.webence.nl/questions/iq-block-country-and-caching-plugins/">https://www.webence.nl/questions/iq-block-country-and-caching-plugins/</a></p>
	</div><?php
}


/*
 * Display missing database notification.
 */
function iq_old_db_notice() {
	?><div class="notice notice-warning">
		<h3><?php echo PLUGINNAME;?></h3>
		<p><?php _e('The MaxMind GeoIP database was older than 3 months and has been updated.', 'iq-block-country');?></p>
		<p><?php
			_e("Please check if everything works fine. In case of error, try to download the database from: " , 'iq-block-country');
			echo "<a href=\"" . GEOIP2DB . "\" target=\"_blank\">" . GEOIP2DB . "</a> ";
			_e("unzip the file and afterwards upload it to the following location: " , 'iq-block-country');
			?><b><?php echo GEOIP2DBFILE; ?></b>
		</p>
		<p><?php _e('For more detailed instructions take a look at the documentation.', 'iq-block-country'); ?></p>
	</div><?php
}


/*
 * Create the wp-admin menu
 */
function iqblockcountry_create_menu() {
	//create new menu option in the settings department
	add_submenu_page ( 'options-general.php', PLUGINNAME, PLUGINNAME, 'administrator', __FILE__, 'iqblockcountry_settings_page' );
	//call register settings function
	add_action ( 'admin_init', 'iqblockcountry_register_mysettings' );
}


/*
 * Register all settings.
 */
function iqblockcountry_register_mysettings() {
	register_setting ( 'iqblockcountry-settings-group', 'blockcountry_blockmessage' );
	register_setting ( 'iqblockcountry-settings-group', 'blockcountry_redirect');
	register_setting ( 'iqblockcountry-settings-group', 'blockcountry_redirect_url','iqblockcountry_is_valid_url');
	register_setting ( 'iqblockcountry-settings-group', 'blockcountry_header');
	register_setting ( 'iqblockcountry-settings-group', 'blockcountry_buffer');
	register_setting ( 'iqblockcountry-settings-group', 'blockcountry_nrstatistics');
	register_setting ( 'iqblockcountry-settings-group', 'blockcountry_daysstatistics');
	register_setting ( 'iqblockcountry-settings-group', 'blockcountry_lookupstatistics');
	register_setting ( 'iqblockcountry-settings-group', 'blockcountry_debuglogging');
	register_setting ( 'iqblockcountry-settings-group', 'blockcountry_accessibility');
	register_setting ( 'iqblockcountry-settings-group', 'blockcountry_logging');
	register_setting ( 'iqblockcountry-settings-group', 'blockcountry_adminajax');
	register_setting ( 'iqblockcountry-settings-group-backend', 'blockcountry_blockbackend' );
	register_setting ( 'iqblockcountry-settings-group-backend', 'blockcountry_backendbanlist' );
	register_setting ( 'iqblockcountry-settings-group-backend', 'blockcountry_backendbanlist_inverse' );
	register_setting ( 'iqblockcountry-settings-group-backend', 'blockcountry_backendblacklist','iqblockcountry_validate_ip');
	register_setting ( 'iqblockcountry-settings-group-backend', 'blockcountry_backendwhitelist','iqblockcountry_validate_ip');
	register_setting ( 'iqblockcountry-settings-group-frontend', 'blockcountry_banlist' );
	register_setting ( 'iqblockcountry-settings-group-frontend', 'blockcountry_banlist_inverse' );
	register_setting ( 'iqblockcountry-settings-group-frontend', 'blockcountry_frontendblacklist','iqblockcountry_validate_ip');
	register_setting ( 'iqblockcountry-settings-group-frontend', 'blockcountry_frontendwhitelist','iqblockcountry_validate_ip');
	register_setting ( 'iqblockcountry-settings-group-frontend', 'blockcountry_blocklogin' );
	register_setting ( 'iqblockcountry-settings-group-frontend', 'blockcountry_blocksearch' );
	register_setting ( 'iqblockcountry-settings-group-frontend', 'blockcountry_blockfrontend' );
	register_setting ( 'iqblockcountry-settings-group-frontend', 'blockcountry_blocktag' );
	register_setting ( 'iqblockcountry-settings-group-frontend', 'blockcountry_blockfeed' );
	register_setting ( 'iqblockcountry-settings-group-pages', 'blockcountry_blockpages');
	register_setting ( 'iqblockcountry-settings-group-pages', 'blockcountry_blockpages_inverse');
	register_setting ( 'iqblockcountry-settings-group-pages', 'blockcountry_pages');
	register_setting ( 'iqblockcountry-settings-group-posttypes', 'blockcountry_blockposttypes');
	register_setting ( 'iqblockcountry-settings-group-posttypes', 'blockcountry_posttypes');
	register_setting ( 'iqblockcountry-settings-group-cat', 'blockcountry_blockcategories');
	register_setting ( 'iqblockcountry-settings-group-cat', 'blockcountry_categories');
	register_setting ( 'iqblockcountry-settings-group-cat', 'blockcountry_blockhome');
	register_setting ( 'iqblockcountry-settings-group-tags', 'blockcountry_blocktags');
	register_setting ( 'iqblockcountry-settings-group-tags', 'blockcountry_tags');
	register_setting ( 'iqblockcountry-settings-group-tools', 'blockcountry_maxmind_license_key' );
	register_setting ( 'iqblockcountry-settings-group-se', 'blockcountry_allowse');
}

/**
 * Retrieve an array of all the options the plugin uses. It can't use only one due to limitations of the options API.
 *
 * @return array of options.
 */
function iqblockcountry_get_options_arr() {
	$optarr = array(
		'blockcountry_banlist','blockcountry_banlist_inverse', 'blockcountry_backendbanlist','blockcountry_backendbanlist_inverse',
		'blockcountry_backendblacklist','blockcountry_backendwhitelist','blockcountry_frontendblacklist','blockcountry_frontendwhitelist',
		'blockcountry_blockmessage','blockcountry_blocklogin','blockcountry_blockfrontend','blockcountry_blockbackend','blockcountry_header',
		'blockcountry_blockpages','blockcountry_pages','blockcountry_blockcategories','blockcountry_categories',
		'blockcountry_blockhome','blockcountry_nrstatistics','blockcountry_daysstatistics','blockcountry_lookupstatistics',
		'blockcountry_redirect','blockcountry_redirect_url','blockcountry_allowse', 'blockcountry_maxmind_license_key',
		'blockcountry_debuglogging','blockcountry_buffer','blockcountry_accessibility','blockcountry_ipoverride','blockcountry_logging','blockcountry_blockposttypes',
		'blockcountry_posttypes','blockcountry_blocksearch','blockcountry_adminajax','blockcountry_blocktag','blockcountry_blockfeed','blockcountry_blocktags','blockcountry_tags'
	);
	return apply_filters( 'iqblockcountry_options', $optarr );
}


/*
 * Set default values when activating this plugin.
 */
function iqblockcountry_set_defaults() {
	update_option('blockcountry_version', VERSION);
	$ip_address = iqblockcountry_get_ipaddress();
	$server_addr = array_key_exists( 'SERVER_ADDR', $_SERVER ) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];

	if (get_option('blockcountry_blockfrontend')			=== false) { update_option('blockcountry_blockfrontend' , 'on'); }
	if (get_option('blockcountry_blockfeed')                === false) { update_option('blockcountry_blockfeed' , 'on'); }
	if (get_option('blockcountry_backendnrblocks')			=== false) { update_option('blockcountry_backendnrblocks', 0); }
	if (get_option('blockcountry_frontendnrblocks')			=== false) { update_option('blockcountry_frontendnrblocks', 0); }
	if (get_option('blockcountry_header')					=== false) { update_option('blockcountry_header', 'on'); }
	if (get_option('blockcountry_nrstatistics')				=== false) { update_option('blockcountry_nrstatistics', 15); }
	if (get_option('blockcountry_daysstatistics', null)		=== null ) { update_option('blockcountry_daysstatistics', 30); }
	if (get_option('blockcountry_backendwhitelist')			=== false || (get_option('blockcountry_backendwhitelist') == "")) { update_option('blockcountry_backendwhitelist', $ip_address); }
	if (get_option('blockcountry_frontendwhitelist')		=== false || (get_option('blockcountry_frontendwhitelist') == "")) { update_option('blockcountry_frontendwhitelist', $server_addr); }
	if (get_option('blockcountry_banlist_inverse')			=== false) { update_option('blockcountry_banlist_inverse' , 'off'); }
	if (get_option('blockcountry_backendbanlist_inverse')	=== false) { update_option('blockcountry_backendbanlist_inverse' , 'off'); }
	if (get_option('blockcountry_ipoverride')				=== false) { update_option('blockcountry_ipoverride' , 'NONE'); }
	
	iqblockcountry_install_db();
	//iqblockcountry_find_geoip_location();
}

/* 
 * Deletes all the database entries that the plugin has created
 */
function iqblockcountry_uninstall() {
	iqblockcountry_uninstall_db();
	iqblockcountry_uninstall_loggingdb();
	
	delete_option('blockcountry_banlist' );
	delete_option('blockcountry_banlist_inverse' );
	delete_option('blockcountry_backendbanlist' );
	delete_option('blockcountry_backendbanlist_inverse');
	delete_option('blockcountry_backendblacklist' );
	delete_option('blockcountry_backendwhitelist' );
	delete_option('blockcountry_frontendblacklist' );
	delete_option('blockcountry_frontendwhitelist' );
	delete_option('blockcountry_blockmessage' );
	delete_option('blockcountry_backendnrblocks' );
	delete_option('blockcountry_frontendnrblocks' );
	delete_option('blockcountry_blocklogin' );
	delete_option('blockcountry_blockfrontend' );
	delete_option('blockcountry_blockbackend' );
	delete_option('blockcountry_version');
	delete_option('blockcountry_header');
	delete_option('blockcountry_blockpages');
	delete_option('blockcountry_blockpages_inverse');
	delete_option('blockcountry_pages');
	delete_option('blockcountry_blockcategories');
	delete_option('blockcountry_categories');
	delete_option('blockcountry_lasttrack');
	delete_option('blockcountry_blockhome');
	delete_option('blockcountry_backendbanlistip');
	delete_option('blockcountry_nrstastistics');
	delete_option('blockcountry_daysstatistics');
	delete_option('blockcountry_lookupstatistics');
	delete_option('blockcountry_redirect');
	delete_option('blockcountry_redirect_url');
	delete_option('blockcountry_allowse');
	delete_option('blockcountry_debuglogging');
	delete_option('blockcountry_buffer');
	delete_option('blockcountry_accessibility');
	delete_option('blockcountry_ipoverride');
	delete_option('blockcountry_logging');
	delete_option('blockcountry_blockposttypes');
	delete_option('blockcountry_posttypes');
	delete_option('blockcountry_blocksearch');
	delete_option('blockcountry_adminajax');
	delete_option('blockcountry_blocktag');
	delete_option('blockcountry_blocktags');
	delete_option('blockcountry_blockfeed');
	delete_option('blockcountry_tags');
	delete_option('blockcountry_maxmind_license_key');
}


function iqblockcountry_settings_tools() {
	global $feblacklistip,
		$feblacklistiprange4,
		$feblacklistiprange6,
		$fewhitelistip,
		$fewhitelistiprange4,
		$fewhitelistiprange6,
		$beblacklistip,
		$beblacklistiprange4,
		$beblacklistiprange6,
		$bewhitelistip,
		$bewhitelistiprange4,
		$bewhitelistiprange6;
	
	global $maxmind_license_key;
	
	$ipcheck_result = '';
		
	if (isset($_POST['action'])) {
		$iqaction = sanitize_text_field($_POST['action']);
		if (!isset($_POST[$iqaction . '_nonce'])) {
			die('Failed security check.');
		}
		
		if (!wp_verify_nonce($_POST[$iqaction . '_nonce'], $iqaction . '_nonce')) {
			die('Is this a CSRF attempts?');
		}
		
		switch ($iqaction) {
			// IP check
			case 'ipcheck':
				if (isset($_POST['ipaddress']) && !empty($_POST['ipaddress'])) {
					$ip_address = sanitize_text_field($_POST['ipaddress']);
					
					if (iqblockcountry_is_valid_ipv4($ip_address) || iqblockcountry_is_valid_ipv6($ip_address)) {
						$country = iqblockcountry_check_ipaddress($ip_address);
						$countrylist = iqblockcountry_get_isocountries();
						
						if ($country == "Unknown" || $country == "ipv6" || $country == "" || $country == "FALSE") {
							$ipcheck_result = '<p>' . __('No country for', 'iq-block-country') . ' ' . $ip_address . ' ' . __('could be found. Or', 'iq-block-country') . ' ' . $ip_address . ' ' . __('is not a valid IPv4 or IPv6 IP address', 'iq-block-country') . '</p>';
						} else {
							$displaycountry = $countrylist[$country];
							$ipcheck_result = '<p>' . __('IP Adress', 'iq-block-country') . ' ' . $ip_address . ' ' . __('belongs to', 'iq-block-country') . ' ' . $displaycountry . '.</p>';
							$haystack = get_option('blockcountry_banlist');
							
							if (!is_array($haystack)) {
								$haystack = array();
							}
							
							$inverse = get_option( 'blockcountry_banlist_inverse');
							
							if ($inverse) {
								if (is_array($haystack) && !in_array ($country, $haystack )) {
									$ipcheck_result .= __('This country is not permitted to visit the frontend of this website.', 'iq-block-country');
									$ipcheck_result .= '<br/>';
								}
							} else {
								if (is_array($haystack) && in_array ( $country, $haystack )) {
									$ipcheck_result .= __('This country is not permitted to visit the frontend of this website.', 'iq-block-country');
									$ipcheck_result .= '<br/>';
								}
							}
							
							$inverse = get_option( 'blockcountry_backendbanlist_inverse');
							$haystack = get_option('blockcountry_backendbanlist');
							
							if (!is_array($haystack)) {
								$haystack = array();
							}
							
							if ($inverse) {
								if (is_array($haystack) && !in_array ( $country, $haystack )) {
									$ipcheck_result .= __('This country is not permitted to visit the backend of this website.', 'iq-block-country');
									$ipcheck_result .= '<br/>';
								}
							} else {
								if (is_array($haystack) && in_array ( $country, $haystack )) {
									$ipcheck_result .= __('This country is not permitted to visit the backend of this website.', 'iq-block-country');
									$ipcheck_result .= '<br/>';
								}
							}
							
							$backendbanlistip = unserialize(get_option('blockcountry_backendbanlistip'));
							
							if (is_array($backendbanlistip) &&  in_array($ip_address,$backendbanlistip)) {
								$ipcheck_result .= __('This IP address is present in the blacklist.', 'iq-block-country');
							}
						}
						
						if (iqblockcountry_validate_ip_in_list($ip_address,$feblacklistiprange4,$feblacklistiprange6,$feblacklistip)) {
							$ipcheck_result .= __('This IP address is present in the frontend blacklist.', 'iq-block-country');
							$ipcheck_result .= '<br/>';
						}
						
						if (iqblockcountry_validate_ip_in_list($ip_address,$fewhitelistiprange4,$fewhitelistiprange6,$fewhitelistip)) {
							$ipcheck_result .= __('This IP address is present in the frontend whitelist.', 'iq-block-country');
							$ipcheck_result .= '<br/>';
						}
						
						if (iqblockcountry_validate_ip_in_list($ip_address,$beblacklistiprange4,$beblacklistiprange6,$beblacklistip)) {
							$ipcheck_result .= __('This IP address is present in the backend blacklist.', 'iq-block-country');
							$ipcheck_result .= '<br/>';
						}
						
						if (iqblockcountry_validate_ip_in_list($ip_address,$bewhitelistiprange4,$bewhitelistiprange6,$beblacklistip)) {
							$ipcheck_result .= __('This IP address is present in the backend whitelist.', 'iq-block-country');
							$ipcheck_result .= '<br/>';
						}
					}
				}
				break;
				
			// Update GeoIP2 database
			case 'updategeoip2db':
				$update_response = iqblockcountry_update_GeoIP2DB(true);
				break;
				
			// Set GeoIP2 license key
			case 'setgeoip2dblicense':
				if (isset($_POST['setgeoip2dblicense_key'])) {
					$setgeoip2dblicense_key = sanitize_text_field($_POST['setgeoip2dblicense_key']);
					if ( update_option('blockcountry_maxmind_license_key', $setgeoip2dblicense_key, 'yes') ) {
						// Poor man's choice to remove an admin notice which is already visible.
						header('Location: ' . site_url() . '/wp-admin/options-general.php?page=iq-block-country/libs/blockcountry-settings.php&tab=tools');
						die();
					}
				}
				break;
		}
	}
	
	?><h3><?php _e('Check which country belongs to an IP Address according to the current database.', 'iq-block-country'); ?></h3>
	<form name="ipcheck" action="#ipcheck" method="post">
		<input type="hidden" name="action" value="ipcheck" />
		<input name="ipcheck_nonce" type="hidden" value="<?php echo wp_create_nonce('ipcheck_nonce'); ?>" />
		<?php _e('IP Address to check:', 'iq-block-country'); ?> <input type="text" name="ipaddress" lenth="50" /><?php 

		echo $ipcheck_result;

		echo '<div class="submit"><input type="submit" class="button" name="test" value="' . __( 'Check IP address', 'iq-block-country' ) . '" /></div>';
		wp_nonce_field('iqblockcountry');
	?></form>
	<hr />
	<h3><?php _e('Database information', 'iq-block-country'); ?></h3><?php
		
	$format = get_option('date_format') . ' ' . get_option('time_format');
	/* Check if the Geo Database exists */
	if (is_file(GEOIP2DBFILE)) {
		_e("GeoIP2 database exists. File date: ", 'iq-block-country');
		$iqfiledate = filemtime(GEOIP2DBFILE);
		echo date($format, $iqfiledate) . " ";
		$iq3months = time() - 3 * 31 * 86400;
		
		if ($iqfiledate < $iq3months) { 
			_e("Database is older than 3 months... Please update...", 'iq-block-country');
		}
	} else {
		_e("GeoIP2 database does not exist.", 'iq-block-country');
	}
	?><br/>
	<br/><?php 
	if (isset($update_response) && $update_response != 200) {
		if ($update_response <= 0) {
			echo __('Could not connect to server: ' . GEOIP2DB, 'iq-block-country');
		} else {
			echo __('Download failed with error-code ' . $update_response, 'iq-block-country');
		}
		?><br/>
		<br/><?php 
	}

	$update_button_disabled = '';
	if (empty($maxmind_license_key) || $maxmind_license_key === false) {
		$update_button_disabled = 'disabled';
	}
	
	?><form name="updategeoip2db" action="#updategeoip2db" method="post">
		<input type="hidden" name="action" value="updategeoip2db" />
		<input name="updategeoip2db_nonce" type="hidden" value="<?php echo wp_create_nonce('updategeoip2db_nonce'); ?>" />
		<input type="submit" class="button" name="update" value="<?php echo __('Update', 'iq-block-country')?>" <?php echo $update_button_disabled;?>/>
		<?php wp_nonce_field('iqblockcountry');?>
	</form>
	<br/>
	<hr/>
	<h3><?php _e('MaxMind GeoIP2 database license', 'iq-block-country'); ?></h3>
	<form name="setgeoip2dblicense" action="#setgeoip2dblicense" method="post">
		<input type="hidden" name="action" value="setgeoip2dblicense" />
		<input name="setgeoip2dblicense_nonce" type="hidden" value="<?php echo wp_create_nonce('setgeoip2dblicense_nonce');?>" />
		<input name="setgeoip2dblicense_key" type="text" placeholder="License key" value="<?php echo $maxmind_license_key;?>" />
		<input type="submit" class="button" name="save" value="<?php echo __('Save', 'iq-block-country');?>"/>
		<?php wp_nonce_field('iqblockcountry');?>
	</form><?php
}


/*
 * Function: Import/Export settings
 */
function iqblockcountry_settings_importexport() {
	$dir = wp_upload_dir();
	if (!isset($_POST['export']) && !isset($_POST['import'])) {  
		?><div class="wrap">  
			<div id="icon-tools" class="icon32"><br /></div>  
			<h2><?php _e('Export', 'iq-block-country'); ?></h2>  
			<p><?php _e('When you click on <tt>Backup all settings</tt> button a backup of the configuration will be created.', 'iq-block-country'); ?></p>  
			<p><?php _e('After exporting, you can either use the backup file to restore your settings on this site again or copy the settings to another WordPress site.', 'iq-block-country'); ?></p>  
			<form method="post">
				<p class="submit">  
					<?php wp_nonce_field('iqblockexport'); ?>  
					<input type="submit" class="button" name="export" value="<?php _e('Backup all settings', 'iq-block-country');?>"/>  
				</p>  
			</form>  
		</div>  

		<div class="wrap">  
			<div id="icon-tools" class="icon32"><br /></div>
			<h2><?php _e('Import', 'iq-block-country'); ?></h2>
			<p><?php _e('Click the browse button and choose a zip file that you exported before.', 'iq-block-country'); ?></p>
			<p><?php _e('Press Restore settings button, and let WordPress do the magic for you.', 'iq-block-country'); ?></p>
			<form method="post" enctype="multipart/form-data">
				<p class="submit">
					<?php wp_nonce_field('iqblockimport');?>
					<input type="file" name="import"/>
					<input type="submit" class="button" name="import" value="<?php _e('Restore settings', 'iq-block-country'); ?>"/>
				</p>  
			</form>  
		</div><?php  
	} elseif (isset($_POST['export'])) {  
  		$blogname = str_replace(" ", "", get_option('blogname'));  
		$date = date("d-m-Y");  
		$json_name = $blogname."-".$date; // Namming the filename will be generated.  
  
		$optarr = iqblockcountry_get_options_arr();
		$need_options = array();
		foreach ( $optarr as $options ) {
			$value = get_option($options);  
			$need_options[$options] = $value;  
		}  
	   
		$json_file = json_encode($need_options); // Encode data into json data  
  
		if ( !$handle = fopen( $dir['path'] . '/' . 'iqblockcountry.ini', 'w' ) ) {
			wp_die(__("Something went wrong exporting this file", 'iq-block-country'));
		}

		if ( !fwrite( $handle, $json_file ) ) {
			wp_die(__("Something went wrong exporting this file", 'iq-block-country'));
		}

		fclose( $handle );

		require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

		chdir( $dir['path'] );
		$zip = new PclZip( './' . $json_name . '-iqblockcountry.zip' );
		
		if ( $zip->create( './' . 'iqblockcountry.ini' ) == 0 ) {
			wp_die(__("Something went wrong exporting this file", 'iq-block-country'));
		}

		$url = $dir['url'] . '/' . $json_name . '-iqblockcountry.zip';
		$content = "<div class='notice notice-success'><p>" . __("Exporting settings...", 'iq-block-country') . "</p></div>";

		if ( $url ) {
			$content .= '<script type="text/javascript">document.location = \'' . $url . '\';</script>';
		} else {
			$content .= 'Error: ' . $url;
		}
		
		echo $content;
	} elseif (isset($_POST['import'])) { 
		$optarr = iqblockcountry_get_options_arr();
		
		if (isset($_FILES['import']) && check_admin_referer('iqblockimport')) {  
			if ($_FILES['import']['error'] > 0) {  
				wp_die(__("Something went wrong importing this file", 'iq-block-country'));  
			} else {
				require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
				$zip = new PclZip( $_FILES['import']['tmp_name'] );
				$unzipped = $zip->extract( $p_path = $dir['path'] );
				
				if ( $unzipped[0]['stored_filename'] == 'iqblockcountry.ini' ) {
					$encode_options = file_get_contents($dir['path'] . '/iqblockcountry.ini');  
					$options = json_decode($encode_options, true);  
					
					foreach ($options as $key => $value) {  
						if (in_array($key,$optarr)) { 
							update_option($key, $value);  
						}
					}
					
					if (file_exists($dir['path'] . '/iqblockcountry.ini')) {
						unlink($dir['path'] . '/iqblockcountry.ini');
					}
					
					echo "<div class='notice notice-success'><p>" . __("All options are restored successfully.", 'iq-block-country') . "</p></div>";  
				} else {  
					echo "<div class='notice notice-error'><p>" . __("Invalid file.", 'iq-block-country') ."</p></div>";  
				}  
			}  
		}
	} else {
		wp_die(__("No correct import or export option given.", 'iq-block-country'));
	}
}


/*
 * Function: Page settings
 */
function iqblockcountry_settings_pages() {
	?><h3><?php _e('Select which pages are blocked.', 'iq-block-country'); ?></h3>
	<form method="post" action="options.php">
		<?php settings_fields('iqblockcountry-settings-group-pages');?>
		<table class="form-table" cellspacing="2" cellpadding="5" width="100%">			
			<tr valign="top">
				<th width="30%">
					<?php _e('Do you want to block individual pages:', 'iq-block-country'); ?><br />
					<?php _e('If you do not select this option all pages will be blocked.', 'iq-block-country'); ?>
				</th>
				<td width="70%">
					<input type="checkbox" name="blockcountry_blockpages" value="on" <?php checked('on', get_option('blockcountry_blockpages'), true); ?> /> 	
				</td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Block pages selected below:', 'iq-block-country'); ?><br />
					<?php _e('Block all pages except those selected below', 'iq-block-country'); ?>
				</th>
				<td width="70%">
					<input type="radio" name="blockcountry_blockpages_inverse" value="off" <?php checked('off', get_option('blockcountry_blockpages_inverse'), true); ?> <?php checked(FALSE, get_option('blockcountry_blockpages_inverse'), true); ?>  /><br />
					<input type="radio" name="blockcountry_blockpages_inverse" value="on" <?php checked('on', get_option('blockcountry_blockpages_inverse'), true); ?> />
				</td>
			</tr>
			<tr valign="top">
				<th width="30%"><?php _e('Select pages you want to block:', 'iq-block-country'); ?></th>
				<td width="70%">
					<ul><?php
						$selectedpages = get_option('blockcountry_pages'); 
						$pages = get_pages(); 
						$selected = "";
						
						foreach ( $pages as $page ) {
							if (is_array($selectedpages)) {
								if ( in_array( $page->ID,$selectedpages) ) {
									$selected = " checked=\"checked\"";
								} else {
									$selected = "";
								}
							}
							echo "<li><input type=\"checkbox\" " . $selected . " name=\"blockcountry_pages[]\" value=\"" . $page->ID . "\" id=\"" . $page->post_title . "\" /> <label for=\"" . $page->post_title . "\">" . $page->post_title . "</label></li>"; 	
						}?>
					</ul>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><p class="submit"><input type="submit" class="button button-primary" value="<?php _e ( 'Save Changes', 'iq-block-country' )?>" /></p></td>
			</tr>
		</table>	
	</form><?php
}

/*
 * Function: Categories settings
 */
function iqblockcountry_settings_categories() {
	?><h3><?php _e('Select which categories are blocked.', 'iq-block-country'); ?></h3>
	<form method="post" action="options.php">
		<?php settings_fields('iqblockcountry-settings-group-cat');?>
		<table class="form-table" cellspacing="2" cellpadding="5" width="100%">			
			<tr valign="top">
				<th width="30%">
					<?php _e('Do you want to block individual categories:', 'iq-block-country'); ?><br />
					<?php _e('If you do not select this option all blog articles will be blocked.', 'iq-block-country'); ?>
				</th>
				<td width="70%">
					<input type="checkbox" name="blockcountry_blockcategories" value="on" <?php checked('on', get_option('blockcountry_blockcategories'), true);?>/> 	
				</td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Do you want to block the homepage:', 'iq-block-country'); ?><br />
					<?php _e('If you do not select this option visitors will not be blocked from your homepage regardless of the categories you select.', 'iq-block-country'); ?>
				</th>
				<td width="70%">
					<input type="checkbox" name="blockcountry_blockhome" value="on" <?php checked('on', get_option('blockcountry_blockhome'), true);?>/> 	
				</td>
			</tr>
			<tr valign="top">
				<th width="30%"><?php _e('Select categories you want to block:', 'iq-block-country'); ?></th>
				<td width="70%">
					<ul><?php
						$selectedcategories = get_option('blockcountry_categories'); 
						$categories = get_categories(array("hide_empty"=>0));
						$selected = "";
						
						foreach ( $categories as $category ) {
							if (is_array($selectedcategories)) {
								if ( in_array( $category->term_id,$selectedcategories) ) {
									$selected = " checked=\"checked\"";
								} else {
									$selected = "";
								}
							}
							echo "<li><input type=\"checkbox\" " . $selected . " name=\"blockcountry_categories[]\" value=\"" . $category->term_id . "\" id=\"" . $category->name . "\" /> <label for=\"" . $category->name . "\">" . $category->name . "</label></li>"; 	
						}?>
					</ul>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><p class="submit"><input type="submit" class="button button-primary" value="<?php _e ( 'Save Changes', 'iq-block-country' )?>"/></p></td>
			</tr>
		</table>
	</form><?php
}


/*
 * Function: Categories settings
 */
function iqblockcountry_settings_tags() {
	?><h3><?php _e('Select which tags are blocked.', 'iq-block-country'); ?></h3>
	<form method="post" action="options.php">
		<?php settings_fields('iqblockcountry-settings-group-tags');?>
		<table class="form-table" cellspacing="2" cellpadding="5" width="100%">			
			<tr valign="top">
				<th width="30%">
					<?php _e('Do you want to block individual tags:', 'iq-block-country'); ?><br />
					<?php _e('If you do not select this option all blog articles will be blocked.', 'iq-block-country'); ?>
				</th>
				<td width="70%">
					<input type="checkbox" name="blockcountry_blocktags" value="on" <?php checked('on', get_option('blockcountry_blocktags'), true);?>/> 	
				</td>
			</tr>
			<tr valign="top">
				<th width="30%"><?php _e('Select tags you want to block:', 'iq-block-country'); ?></th>
				<td width="70%">
					<ul><?php
						$selectedtags = get_option('blockcountry_tags');
						$tags = get_tags(array('hide_empty' => 0));
						$selected = '';
						
						foreach ( $tags as $tag ) {
							if (is_array($selectedtags)) {
								if ( in_array( $tag->term_id,$selectedtags) ) {
									$selected = ' checked="checked"';
								} else {
									$selected = '';
								}
							}
							echo '<li><input type="checkbox" ' . $selected . ' name="blockcountry_tags[]" value="' . $tag->term_id . '" id="' . $tag->name . '"/> <label for="' . $tag->name . '">' . $tag->name . '</label></li>'; 	
						}?>
					</ul>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><p class="submit"><input type="submit" class="button button-primary" value="<?php _e( 'Save Changes', 'iq-block-country' )?>"/></p></td>
			</tr>
		</table>	
	</form><?php
}


/*
 * Function: Custom post type settings
 */
function iqblockcountry_settings_posttypes() {
	?><h3><?php _e('Select which post types are blocked.', 'iq-block-country'); ?></h3>
	<form method="post" action="options.php">
		<?php settings_fields('iqblockcountry-settings-group-posttypes');?>
		<table class="form-table" cellspacing="2" cellpadding="5" width="100%">
			<tr valign="top">
				<th width="30%">
					<?php _e('Do you want to block individual post types:', 'iq-block-country'); ?><br />
				</th>
				<td width="70%">
					<input type="checkbox" name="blockcountry_blockposttypes" value="on" <?php checked('on', get_option('blockcountry_blockposttypes'), true);?>/>
				</td>
			</tr>
			<tr valign="top">
				<th width="30%"><?php _e('Select post types you want to block:', 'iq-block-country'); ?></th>
				<td width="70%">
					<ul><?php
						$post_types = get_post_types( '', 'names' ); 
						$selectedposttypes = get_option('blockcountry_posttypes');
						$selected = '';
						
						foreach ( $post_types as $post_type ) {
							if (is_array($selectedposttypes)) {
								if ( in_array( $post_type,$selectedposttypes) ) {
									$selected = ' checked="checked"';
								} else {
									$selected = '';
								}
							}
							echo '<li><input type="checkbox" ' . $selected . ' name="blockcountry_posttypes[]" value="' . $post_type . '" id="' . $post_type . '" /> <label for="' . $post_type . '">' . $post_type . '</label></li>'; 	
						}?>
					</ul>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><p class="submit"><input type="submit" class="button button-primary" value="<?php _e( 'Save Changes', 'iq-block-country' );?>"/></p></td>
			</tr>
		</table>
	</form><?php
}


/*
 * Function: Services settings
 */
function iqblockcountry_settings_services() {
	?><h3><?php _e('Select which services are allowed.', 'iq-block-country');?></h3>
	<form method="post" action="options.php">
		<?php settings_fields('iqblockcountry-settings-group-se');?>
		<table class="form-table" cellspacing="2" cellpadding="5" width="100%">
			<tr valign="top">
				<th width="30%">
					<?php _e('Select which services you want to allow:', 'iq-block-country');?><br/>
					<?php _e('This will allow a service like for instance a search engine to your site despite if you blocked the country.', 'iq-block-country');?><br />
					<?php _e('Please note the "Search Engine Visibility" should not be selected in ', 'iq-block-country');?><a href="/wp-admin/options-reading.php"><?php _e('reading settings.', 'iq-block-country');?></a>
				</th>
				<td width="70%">
					<ul><?php
						global $searchengines;
						$selectedse = get_option('blockcountry_allowse'); 
						$selected = '';
						
						foreach ( $searchengines AS $se => $seua ) {
							if (is_array($selectedse)) {
								if ( in_array( $se,$selectedse) ) {
									$selected = ' checked="checked"';
								} else {
									$selected = '';
								}
							} 
							echo '<li><input type="checkbox" ' . $selected . ' name="blockcountry_allowse[]" value="' . $se . '" id="' . $se . '"/> <label for="' . $se . '">' . $se . '</label></li>'; 	
						}?>
					</ul>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Save Changes', 'iq-block-country');?>" /></p></td>
			</tr>	
		</table>	
	</form><?php
}


/*
 * Settings frontend
 */
function iqblockcountry_settings_frontend() {
	if (!class_exists('GeoIP')) {
		include_once('geoip.php');
	}
	
	?><h3><?php _e('Frontend options', 'iq-block-country'); ?></h3><?php
	$countrylist = iqblockcountry_get_isocountries();
	$frontendwhitelist = get_option('blockcountry_frontendwhitelist');
	$frontendblacklist = get_option('blockcountry_frontendblacklist');
		
	?><link rel="stylesheet" href="<?php echo CHOSENCSS;?>" type="text/css" />
	<form method="post" action="options.php">
		<?php settings_fields('iqblockcountry-settings-group-frontend');?>
		<table class="form-table" cellspacing="2" cellpadding="5" width="100%">			
			<tr valign="top">
				<th width="30%"><?php _e('Block visitors from visiting the frontend of your website:', 'iq-block-country');?></th>
				<td width="70%"><input type="checkbox" name="blockcountry_blockfrontend" <?php checked('on', get_option('blockcountry_blockfrontend'), true);?>/></td>
			</tr>
			<tr valign="top">
				<th width="30%"><?php _e('Do not block visitors that are logged in from visiting frontend website:', 'iq-block-country');?></th>
				<td width="70%"><input type="checkbox" name="blockcountry_blocklogin" <?php checked('on', get_option('blockcountry_blocklogin'), true);?>/></td>
			</tr>
			<tr valign="top">
				<th width="30%"><?php _e('Block visitors from using the search function of your website:', 'iq-block-country');?></th>
				<td width="70%"><input type="checkbox" name="blockcountry_blocksearch" <?php checked('on', get_option('blockcountry_blocksearch'), true);?>/></td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Block countries selected below:', 'iq-block-country');?><br/>
					<?php _e('Block all countries except those selected below', 'iq-block-country');?>
				</th>
				<td width="70%">
					<input type="radio" name="blockcountry_banlist_inverse" value="off" <?php checked('off', get_option('blockcountry_banlist_inverse'), true);?> <?php checked(FALSE, get_option('blockcountry_banlist_inverse'), true);?>/><br />
					<input type="radio" name="blockcountry_banlist_inverse" value="on" <?php checked('on', get_option('blockcountry_banlist_inverse'), true);?>/>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" width="30%">
					<?php _e('Select the countries:', 'iq-block-country');?><br/>
					<?php _e('Use the CTRL key to select multiple countries', 'iq-block-country'); ?>
				</th>
				<td width="70%"><?php
					$selected = '';
					$haystack = get_option('blockcountry_banlist');

					if (get_option('blockcountry_accessibility')) {
						echo '<ul>';
						
						foreach ( $countrylist as $key => $value ) {
							if (is_array($haystack) && in_array ( $key, $haystack )) {
								$selected = ' checked="checked"';
							} else {
								$selected = '';
							}
							echo '<li><input type="checkbox" ' . $selected . ' name="blockcountry_banlist[]" value="' . $key . '"/> <label for="' . $value . '">' . $value . '</label></li>';
						}
						echo '</ul>';
					} else {
						?><select data-placeholder="Choose a country..." class="chosen" name="blockcountry_banlist[]" multiple="true" style="width:600px;">
							<optgroup label="(de)select all countries"><?php
								foreach ( $countrylist as $key => $value ) {
									echo '<option value="' . $key . '"';
									
									if (is_array($haystack) && in_array ( $key, $haystack )) {
										echo ' selected="selected" ';
									}
									echo '>' . $value. '</option>';
								}?>
							</optgroup>
						</select><?php
					}?>
				</td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Block tag pages:', 'iq-block-country');?><br/>
					<?php _e('If you select this option tag pages will be blocked.', 'iq-block-country');?>
				</th>
				<td width="70%"><input type="checkbox" name="blockcountry_blocktag" <?php checked('on', get_option('blockcountry_blocktag'), true);?>/></td>
			</tr>
			<tr valign="top">
				<th width="30%"><?php _e('Block feed:', 'iq-block-country');?><br/><?php _e('If you select this option feed pages will be blocked.', 'iq-block-country');?></th>
				<td width="70%"><input type="checkbox" name="blockcountry_blockfeed" <?php checked('on', get_option('blockcountry_blockfeed'), true);?>/></td>
			</tr>
			<tr valign="top">
				<th width="30%"><?php _e('Frontend whitelist IPv4 and/or IPv6 addresses:', 'iq-block-country');?><br/><?php _e('Use a semicolon (;) to separate IP addresses', 'iq-block-country'); ?><br /><?php _e('This field accepts single IP addresses as well as ranges in CIDR format.', 'iq-block-country');?></th>
				<td width="70%"><textarea cols="70" rows="5" name="blockcountry_frontendwhitelist"><?php echo $frontendwhitelist;?></textarea></td>
			</tr>
			<tr valign="top">
				<th width="30%"><?php _e('Frontend blacklist IPv4 and/or IPv6 addresses:', 'iq-block-country'); ?><br /><?php _e('Use a semicolon (;) to separate IP addresses', 'iq-block-country'); ?><br /><?php _e('This field accepts single IP addresses as well as ranges in CIDR format.', 'iq-block-country');?></th>
				<td width="70%"><textarea cols="70" rows="5" name="blockcountry_frontendblacklist"><?php echo $frontendblacklist;?></textarea></td>
			</tr>
			<tr>
				<td></td>
				<td><p class="submit"><input type="submit" class="button button-primary" value="<?php _e( 'Save Changes', 'iq-block-country' );?>"/></p></td>
			</tr>
		</table>
	</form><?php
}


/*
 * Settings backend.
 */
function iqblockcountry_settings_backend() {
	?><h3><?php _e('Backend Options', 'iq-block-country'); ?></h3><?php
	if (!class_exists('GeoIP')) {
		include_once('geoip.php');
	}
	
	$countrylist = iqblockcountry_get_isocountries();
	$ip_address = iqblockcountry_get_ipaddress();
	$country = iqblockcountry_check_ipaddress($ip_address);
	
	if ($country == 'Unknown' || $country == 'ipv6' || $country == '' || $country == 'FALSE') {
		$displaycountry = 'Unknown';
	} else {
		$displaycountry = $countrylist[$country];
	}
	
	$backendwhitelist = get_option('blockcountry_backendwhitelist');
	$backendblacklist = get_option('blockcountry_backendblacklist');
	
	?><link rel="stylesheet" href=<?php echo "\"" . CHOSENCSS . "\""?> type="text/css" />
	<form method="post" action="options.php">
		<?php settings_fields('iqblockcountry-settings-group-backend');?>
		<table class="form-table" cellspacing="2" cellpadding="5" width="100%">			
			<tr valign="top">
				<th width="30%"><?php _e('Block visitors from visiting the backend (administrator) of your website:', 'iq-block-country');?></th>
				<td width="70%"><input type="checkbox" name="blockcountry_blockbackend" <?php checked('on', get_option('blockcountry_blockbackend'), true);?>/></td>
			</tr>
			<tr>
				<th width="30%"></th>
				<th width="70%">
					<?php _e('Your IP address is', 'iq-block-country'); ?> <i><?php echo $ip_address ?></i>. <?php _e('The country that is listed for this IP address is', 'iq-block-country');?> <em><?php echo $displaycountry;?></em>.<br/>
					<?php _e('Do <strong>NOT</strong> set the \'Block visitors from visiting the backend (administrator) of your website\' and also select', 'iq-block-country');?> <?php echo $displaycountry;?> <?php _e('below.', 'iq-block-country');?><br/>
					<?php echo '<strong>' . __('You will NOT be able to login the next time if you DO block your own country from visiting the backend.', 'iq-block-country') . '</strong>';?>
				</th>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Block countries selected below:', 'iq-block-country');?><br/>
					<?php _e('Block all countries except those selected below', 'iq-block-country');?>
				</th>
				<td width="70%">
					<input type="radio" name="blockcountry_backendbanlist_inverse" value="off" <?php checked('off', get_option('blockcountry_backendbanlist_inverse'), true);?> <?php checked(FALSE, get_option('blockcountry_backendbanlist_inverse'), true);?>/><br/>
					<input type="radio" name="blockcountry_backendbanlist_inverse" value="on" <?php checked('on', get_option('blockcountry_backendbanlist_inverse'), true);?>/>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" width="30%">
					<?php _e('Select the countries:', 'iq-block-country');?><br />
					<?php _e('Use the CTRL key to select multiple countries', 'iq-block-country');?>
				</th>
				<td width="70%"><?php
					$selected = "";
					$haystack = get_option ( 'blockcountry_backendbanlist' );	   
					
					if (get_option('blockcountry_accessibility')) {
						echo '<ul>';
						foreach ( $countrylist as $key => $value ) {
							if (is_array($haystack) && in_array ( $key, $haystack )) {
								$selected = ' checked="checked"';
							} else {
								$selected = '';
							}
							echo '<li><input type="checkbox" ' . $selected . ' name="blockcountry_backendbanlist[]" value="' . $key . '"/> <label for="' . $value . '">' . $value . '</label></li>'; 	
						}
						echo '</ul>';
					} else {
						?><select class="chosen" data-placeholder="Choose a country..." name="blockcountry_backendbanlist[]" multiple="true" style="width:600px;">
							<optgroup label="(de)select all countries"><?php
								foreach ( $countrylist as $key => $value ) {
									echo '<option value="' . $key . '"';
									if (is_array($haystack) && in_array ( $key, $haystack )) {
										echo ' selected="selected" ';
									}
									echo '>' . $value . '</option>';
								}?>
							</optgroup>
						</select><?php
					}?>
				</td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Backend whitelist IPv4 and/or IPv6 addresses:', 'iq-block-country');?><br/>
					<?php _e('Use a semicolon (;) to separate IP addresses', 'iq-block-country');?><br/>
					<?php _e('This field accepts single IP addresses as well as ranges in CIDR format.', 'iq-block-country');?>
				</th>
				<td width="70%"><textarea cols="70" rows="5" name="blockcountry_backendwhitelist"><?php echo $backendwhitelist;?></textarea></td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Backend blacklist IPv4 and/or IPv6 addresses:', 'iq-block-country');?><br/>
					<?php _e('Use a semicolon (;) to separate IP addresses', 'iq-block-country'); ?><br/>
					<?php _e('This field accepts single IP addresses as well as ranges in CIDR format.', 'iq-block-country');?>
				</th>
				<td width="70%"><textarea cols="70" rows="5" name="blockcountry_backendblacklist"><?php echo $backendblacklist;?></textarea></td>
			</tr>
			<tr>
				<td></td>
				<td><p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Save Changes', 'iq-block-country');?>"/></p></td>
			</tr>
		</table>
	</form><?php
}

				
/*
 * Settings home
 */
function iqblockcountry_settings_home() {
	/* Check if the Geo Database exists or if GeoIP API key is entered otherwise display notification */
	if (is_file ( GEOIP2DBFILE )) {
		$iqfiledate = filemtime(GEOIP2DBFILE);
		$iq3months = time() - 3 * 31 * 86400;
		
		if ($iqfiledate < $iq3months) {
			iqblockcountry_update_GeoIP2DB();
			iq_old_db_notice();
		}
	}
	
	$blockedbackendnr = get_option('blockcountry_backendnrblocks');
	$blockedfrontendnr = get_option('blockcountry_frontendnrblocks');
	
	?><h3><?php _e('Overall statistics since start', 'iq-block-country');?></h3>
	<p><?php echo number_format($blockedbackendnr);?> <?php _e('visitors blocked from the backend.', 'iq-block-country');?></p>
	<p><?php echo number_format($blockedfrontendnr);?> <?php _e('visitors blocked from the frontend.', 'iq-block-country');?></p><?php

	if (!class_exists('GeoIP')) {
		include_once('geoip.php');
	}
	
	$blockmessage = get_option('blockcountry_blockmessage');
	if (empty($blockmessage)) {
		$blockmessage = "Forbidden - Visitors from your country are not permitted to browse this site.";
	}
	
	?><link rel="stylesheet" href=<?php echo "\"" . CHOSENCSS . "\""?> type="text/css" />
	<form method="post" action="options.php">
		<?php settings_fields('iqblockcountry-settings-group');?>
		<hr>
		<h3><?php _e('Block type', 'iq-block-country'); ?></h3>
		<em><?php _e('You should choose one of the 3 block options below. This wil either show a block message, redirect to an internal page or redirect to an external page.', 'iq-block-country'); ?></em>
		<table class="form-table" cellspacing="2" cellpadding="5" width="100%">			
			<tr valign="top">
				<th width="30%"><?php _e('Message to display when people are blocked:', 'iq-block-country');?></th>
				<td width="70%"><textarea cols="100" rows="3" name="blockcountry_blockmessage"><?php echo $blockmessage;?></textarea></td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Page to redirect to:', 'iq-block-country'); ?><br />
					<em><?php _e('If you select a page here blocked visitors will be redirected to this page instead of displaying above block message.', 'iq-block-country'); ?></em>
				</th>
				<td width="70%">
					<select class="chosen" name="blockcountry_redirect" style="width:400px;"><?php
						$haystack = get_option ( 'blockcountry_redirect' );
						$pages = get_pages();
						
						echo '<option value="0">'. __('Choose a page...', 'iq-block-country') . '</option>';
						
						foreach ( $pages as $page ) {
							echo '<option value="'. $page->ID . '"';
							if ($page->ID == $haystack) { 
								echo ' selected="selected" ';
							}
							echo '>' . $page->post_title .'</option>';
						}?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('URL to redirect to:', 'iq-block-country');?><br />
					<em><?php _e('If you enter a URL here blocked visitors will be redirected to this URL instead of displaying above block message or redirected to a local page.', 'iq-block-country');?></em>
				</th>
				<td width="70%"><input type="text" style="width:100%" name="blockcountry_redirect_url" value="<?php echo get_option ( 'blockcountry_redirect_url' );?>"></td>
			</tr>
		</table>
		<hr>
		<h3><?php _e('General settings', 'iq-block-country'); ?></h3>
		<table class="form-table" cellspacing="2" cellpadding="5" width="100%">			
			<tr valign="top">
				<th width="30%">
					<?php _e('Send headers when user is blocked:', 'iq-block-country');?><br />
					<em><?php _e('Under normal circumstances you should keep this selected! Only if you have "Cannot modify header information - headers already sent" errors or if you know what you are doing uncheck this.', 'iq-block-country');?></em>
				</th>
				<td width="70%"><input type="checkbox" name="blockcountry_header" <?php checked('on', get_option('blockcountry_header'), true);?>/></td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Buffer output?:', 'iq-block-country');?><br />
					<em><?php _e('You can use this option to buffer all output. This can be helpful in case you have "headers already sent" issues.', 'iq-block-country');?></em>
				</th>
				<td width="70%"><input type="checkbox" name="blockcountry_buffer" <?php checked('on', get_option('blockcountry_buffer'), true);?>/></td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Do not log IP addresses:', 'iq-block-country');?><br />
					<em><?php _e('Check this box if the laws in your country do not permit you to log IP addresses or if you do not want to log the ip addresses.', 'iq-block-country'); ?></em>
				</th>
				<td width="70%"><input type="checkbox" name="blockcountry_logging" <?php checked('on', get_option('blockcountry_logging'), true);?>/></td>
			</tr>
	   		<tr valign="top">
				<th width="30%">
					<?php _e('Do not block admin-ajax.php:', 'iq-block-country');?><br />
					<em><?php _e('Check this box if you use a plugin that uses admin-ajax.php.', 'iq-block-country');?></em>
				</th>
				<td width="70%"><input type="checkbox" name="blockcountry_adminajax" <?php checked('on', get_option('blockcountry_adminajax'), true);?>/></td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Number of rows on logging tab:', 'iq-block-country');?><br/>
					<em><?php _e('How many rows do you want to display on each column on the logging tab.', 'iq-block-country');?></em>
				</th>
				<td width="70%"><?php
					$nrrows = get_option('blockcountry_nrstatistics');
					?><select name="blockcountry_nrstatistics">
						<option <?php selected( $nrrows, 10 );?> value="10">10</option>
						<option <?php selected( $nrrows, 15 );?> value="15">15</option>
						<option <?php selected( $nrrows, 20 );?> value="20">20</option>
						<option <?php selected( $nrrows, 25 );?> value="25">25</option>
						<option <?php selected( $nrrows, 30 );?> value="30">30</option>
						<option <?php selected( $nrrows, 45 );?> value="45">45</option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Number of days to keep logging:', 'iq-block-country');?><br/>
					<em><?php _e('How many days do you want to keep the logging used for the logging tab.', 'iq-block-country');?></em>
				</th>
				<td width="70%"><?php
					$nrdays = get_option('blockcountry_daysstatistics');
					?><select name="blockcountry_daysstatistics">
						<option <?php selected( $nrdays, 7 );?> value="7">7</option>
						<option <?php selected( $nrdays, 14 );?> value="14">14</option>
						<option <?php selected( $nrdays, 21 );?> value="21">21</option>
						<option <?php selected( $nrdays, 30 );?> value="30">30</option>
						<option <?php selected( $nrdays, 60 );?> value="60">60</option>
						<option <?php selected( $nrdays, 90 );?> value="90">90</option>
					</select>
				</td>
			</tr>
	   		<tr valign="top">
				<th width="30%">
					<?php _e('Do not lookup hosts on the logging tab:', 'iq-block-country');?><br/>
					<em><?php _e('On some hosting environments looking up hosts may slow down the logging tab.', 'iq-block-country');?></em>
				</th>
				<td width="70%"><input type="checkbox" name="blockcountry_lookupstatistics" <?php checked('on', get_option('blockcountry_lookupstatistics'), true);?>/></td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Accessibility options:', 'iq-block-country');?><br/>
					<em><?php _e('Set this option if you cannot use the default country selection box.', 'iq-block-country');?></em>
				</th>
				<td width="70%"><input type="checkbox" name="blockcountry_accessibility" <?php checked('on', get_option('blockcountry_accessibility'), true);?>/></td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Override IP information:', 'iq-block-country');?><br/>
					<em><?php _e('This option allows you to override how to get the real IP of your visitors.', 'iq-block-country');?></em>
				</th>
				<td width="70%"><?php
					$ipoverride = get_option('blockcountry_ipoverride');
					?><select name="blockcountry_ipoverride">
						<option <?php selected( $ipoverride, "NONE" ); ?> value="NONE">No override</option>
						<option <?php selected( $ipoverride, "REMOTE_ADDR" ); ?> value="REMOTE_ADDR">REMOTE_ADDR</option>
						<option <?php selected( $ipoverride, "HTTP_FORWARDED" ); ?> value="HTTP_FORWARDED">HTTP_FORWARDED</option>
						<option <?php selected( $ipoverride, "HTTP_X_REAL_IP" ); ?> value="HTTP_X_REAL_IP">HTTP_X_REAL_IP</option>
						<option <?php selected( $ipoverride, "HTTP_CLIENT_IP" ); ?> value="HTTP_CLIENT_IP">HTTP_CLIENT_IP</option>
						<option <?php selected( $ipoverride, "HTTP_X_FORWARDED" ); ?> value="HTTP_X_FORWARDED">HTTP_X_FORWARDED</option>
						<option <?php selected( $ipoverride, "HTTP_X_FORWARDED_FOR" ); ?> value="HTTP_X_FORWARDED_FOR">HTTP_X_FORWARDED_FOR</option>
						<option <?php selected( $ipoverride, "HTTP_INCAP_CLIENT_IP" ); ?> value="HTTP_INCAP_CLIENT_IP">HTTP_X_FORWARDED</option>
						<option <?php selected( $ipoverride, "HTTP_X_SUCURI_CLIENTIP" ); ?> value="HTTP_X_SUCURI_CLIENTIP">HTTP_X_SUCURI_CLIENTIP</option>
						<option <?php selected( $ipoverride, "HTTP_CF_CONNECTING_IP" ); ?> value="HTTP_CF_CONNECTING_IP">HTTP_CF_CONNECTING_IP</option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th width="30%">
					<?php _e('Log all visits:', 'iq-block-country');?><br/>
					<em><?php _e('This logs all visits despite if they are blocked or not. This is only for debugging purposes.', 'iq-block-country');?></em>
				</th>
				<td width="70%"><input type="checkbox" name="blockcountry_debuglogging" <?php checked('on', get_option('blockcountry_debuglogging'), true);?>/></td>
			</tr>
			<tr>
				<td></td>
				<td><p class="submit"><input type="submit" class="button button-primary" value="<?php _e ( 'Save Changes', 'iq-block-country' );?>"/></p></td>
			</tr>
		</table>
	</form><?php
}


/*
 * Function: Display logging
 */
function iqblockcountry_settings_logging() {
	/**
	 * Whoisip extension
	 * @author nrekow
	 */
	?><link rel="stylesheet" href="<?php echo JQUERYUICSS;?>" type="text/css"/>
	<link rel="stylesheet" href="<?php echo WHOISCSS;?>" type="text/css"/>
	<div id="whoisip-dialog" class="hidden"><div id="whoisip-result"></div></div>
	<h3><?php _e('Last blocked visits', 'iq-block-country');?></h3><?php
	if (!get_option('blockcountry_logging')) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'iqblock_logging';
		$format = get_option('date_format') . ' ' . get_option('time_format');
		$nrrows = get_option('blockcountry_nrstatistics');
		$lookupstats = get_option('blockcountry_lookupstatistics');
		
		if ($nrrows == '') {
			$nrrows = 15;
		}
		
		$countrylist = iqblockcountry_get_isocountries();
		echo '<table class="widefat">';
		echo '<thead><tr><th>' . __('Date / Time', 'iq-block-country') . '</th><th>' . __('IP Address', 'iq-block-country') . '</th><th>' . __('Hostname', 'iq-block-country') . '</th><th>' . __('URL', 'iq-block-country') . '</th><th>' . __('Country', 'iq-block-country') . '</th><th>' . __('Frontend/Backend', 'iq-block-country') . '</th></tr></thead>';
		
		foreach ($wpdb->get_results( "SELECT * FROM $table_name ORDER BY datetime DESC LIMIT $nrrows" ) as $row) {
			$countryimage = 'icons/' . strtolower($row->country) . '.png';
			$countryurl = '<img src="' . plugins_url( $countryimage , dirname(__FILE__) ) . '" > ';
			echo '<tbody><tr><td>';
			$datetime = strtotime($row->datetime);
			$mysqldate = date($format, $datetime);
			
			/*
			if ($lookupstats) {
				echo $mysqldate . '</td><td class="whoisip" data-ip="' . $row->ipaddress . '">' . $row->ipaddress . '</td><td>' . $row->ipaddress . 'S</td><td><a href="http' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $row->url . '" target="_blank">' . $row->url . '</a></td><td>' . $countryurl . $countrylist[$row->country] . '<td>';
			} else {
				echo $mysqldate . '</td><td class="whoisip" data-ip="' . $row->ipaddress . '">' . $row->ipaddress . '</td><td>' . gethostbyaddr( $row->ipaddress ) . '</td><td><a href="http' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $row->url . '" target="_blank">' . $row->url . '</a></td><td>' . $countryurl . $countrylist[$row->country] . '<td>';
			}
			*/
			if ($lookupstats) {
				if (extension_loaded('mbstring')) {
					echo $mysqldate . '</td><td class="whoisip" data-ip="' . $row->ipaddress . '" title="Click to lookup this IP">' . $row->ipaddress . '</td><td>' . $row->ipaddress . 'S</td><td><a href="http' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $row->url . '" target="_blank">' . mb_strimwidth($row->url, 0, 75, '...') . '</a></td><td>' . $countryurl . $countrylist[$row->country] . '<td>';
				} else {
					echo $mysqldate . '</td><td class="whoisip" data-ip="' . $row->ipaddress . '" title="Click to lookup this IP">' . $row->ipaddress . '</td><td>' . $row->ipaddress . 'S</td><td><a href="http' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $row->url . '" target="_blank">' . $row->url . '</a></td><td>' . $countryurl . $countrylist[$row->country] . '<td>';
				}
			} else {
				if (extension_loaded('mbstring')) {
					echo $mysqldate . '</td><td class="whoisip" data-ip="' . $row->ipaddress . '" title="Click to lookup this IP">' . $row->ipaddress . '</td><td>' . gethostbyaddr( $row->ipaddress ) . '</td><td><a href="http' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $row->url . '" target="_blank">' .  mb_strimwidth($row->url, 0, 75, '...') . '</a></td><td>' . $countryurl . $countrylist[$row->country] . '<td>';
				} else {
					echo $mysqldate . '</td><td class="whoisip" data-ip="' . $row->ipaddress . '" title="Click to lookup this IP">' . $row->ipaddress . '</td><td>' . gethostbyaddr( $row->ipaddress ) . '</td><td><a href="http' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $row->url . '" target="_blank">' . $row->url . '</a></td><td>' . $countryurl . $countrylist[$row->country] . '<td>';
				}
			}
			
			
			if ($row->banned == 'F') {
				_e('Frontend', 'iq-block-country');
			} elseif ($row->banned == 'A') {
				_e('Backend banlist', 'iq-block-country');
			} elseif ($row->banned == 'T') {
				_e('Backend & Backend banlist', 'iq-block-country');
			} else {
				_e('Backend', 'iq-block-country');
			}
			
			echo "</td></tr></tbody>";
		}
		
		echo '</table>';
		echo '<hr>';
		echo '<h3>' . __('Top countries that are blocked', 'iq-block-country') . '</h3>';
		echo '<table class="widefat">';
		echo '<thead><tr><th>' . __('Country', 'iq-block-country') . '</th><th>' . __('# of blocked attempts', 'iq-block-country') . '</th></tr></thead>';

		foreach ($wpdb->get_results( "SELECT count(country) AS count,country FROM $table_name GROUP BY country ORDER BY count(country) DESC LIMIT $nrrows" ) as $row) {
			$countryimage = "icons/" . strtolower($row->country) . ".png";
			$countryurl = '<img src="' . plugins_url( $countryimage , dirname(__FILE__) ) . '" > ';
			echo "<tbody><tr><td>" . $countryurl . $countrylist[$row->country] . "</td><td>" . $row->count . "</td></tr></tbody>";
		}
		echo '</table>';
		
		echo '<hr>';
		echo '<h3>' . __('Top hosts that are blocked', 'iq-block-country') . '</h3>';
		echo '<table class="widefat">';
		echo '<thead><tr><th>' . __('IP Address', 'iq-block-country') . '</th><th>' . __('Hostname', 'iq-block-country') . '</th><th>' . __('# of blocked attempts', 'iq-block-country') . '</th></tr></thead>';

		foreach ($wpdb->get_results( "SELECT count(ipaddress) AS count,ipaddress FROM $table_name GROUP BY ipaddress ORDER BY count(ipaddress) DESC LIMIT $nrrows" ) as $row) {
			if ($lookupstats) {
				echo '<tbody><tr><td class="whoisip" data-ip="' . $row->ipaddress . '">' . $row->ipaddress . '</td><td>' . $row->ipaddress . '</td><td>' . $row->count . '</td></tr></tbody>';
			} else {
				echo '<tbody><tr><td class="whoisip" data-ip="' . $row->ipaddress . '">' . $row->ipaddress . '</td><td>' . gethostbyaddr($row->ipaddress) . '</td><td>' . $row->count . '</td></tr></tbody>';
			}
		}
		echo '</table>';

		echo '<hr>';
		echo '<h3>' . __('Top URLs that are blocked', 'iq-block-country') . '</h3>';
		echo '<table class="widefat">';
		echo '<thead><tr><th>' . __('URL', 'iq-block-country') . '</th><th>' .  __('# of blocked attempts', 'iq-block-country') .  '</th></tr></thead>';

		foreach ($wpdb->get_results( "SELECT count(url) AS count,url FROM $table_name GROUP BY url ORDER BY count(url) DESC LIMIT $nrrows" ) as $row) {
			echo '<tbody><tr><td><a href="http' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $row->url . '" target="_blank">' . $row->url . '</a></td><td>' . $row->count . '</td></tr></tbody>';
		}
		echo '</table>';
		
		?>
		<script>
		var whoisip_php = '<?php echo plugin_dir_url(__FILE__) . 'whoisip.php';?>';
		</script>
		<script src="<?php echo WHOISIPJS;?>"></script>
		
		<form name="cleardatabase" action="#" method="post">
			<input type="hidden" name="action" value="cleardatabase" />
			<input name="cleardatabase_nonce" type="hidden" value="<?php echo wp_create_nonce('cleardatabase_nonce');?>"/>
			<div class="submit"><input type="submit" class="button" name="test" value="<?php echo __( 'Clear database', 'iq-block-country' );?>" /></div>
			<?php wp_nonce_field('iqblockcountry');

			if ( isset($_POST['action']) && $_POST[ 'action' ] == 'cleardatabase') {
				if (!isset($_POST['cleardatabase_nonce'])) {
					die("Failed security check.");
				}
				
				if (!wp_verify_nonce($_POST['cleardatabase_nonce'],'cleardatabase_nonce')) {
					die("Is this a CSRF attempt?");
				}
				
				global $wpdb;
				$table_name = $wpdb->prefix . "iqblock_logging";
				
				$sql = "TRUNCATE " . $table_name . ";";
				$wpdb->query($sql);
				
				$sql = "ALTER TABLE ". $table_name . " AUTO_INCREMENT = 1;";
				$wpdb->query($sql);
				echo "Cleared database";
			}
		?></form>
		
		<form name="csvoutput" action="#" method="post">
			<input type="hidden" name="action" value="csvoutput" />
			<input name="csv_nonce" type="hidden" value="<?php echo wp_create_nonce('csv_nonce'); ?>"/>
			<div class="submit"><input type="submit" class="button" name="submit" value="<?php echo __( 'Download as CSV file', 'iq-block-country' );?>"/></div>
			<?php wp_nonce_field('iqblockcountrycsv');?>
		</form><?php
	} else {
		echo "<hr><h3>";
		_e('You are not logging any information. Please uncheck the option \'Do not log IP addresses\' if this is not what you want.', 'iq-block-country');
		echo "<hr></h3>";
	}
}


/*
 * Create the settings page.
 */
function iqblockcountry_settings_page() {
	if( isset( $_GET[ 'tab' ] ) ) {  
		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'home';			  
	} else {
		$active_tab = 'home';
	}
	
	?><h2 class="nav-tab-wrapper">
		<a href="?page=iq-block-country/libs/blockcountry-settings.php&tab=home" class="nav-tab <?php echo $active_tab == 'home' ? 'nav-tab-active' : ''; ?>"><?php _e('Home', 'iq-block-country'); ?></a>  
		<a href="?page=iq-block-country/libs/blockcountry-settings.php&tab=frontend" class="nav-tab <?php echo $active_tab == 'frontend' ? 'nav-tab-active' : ''; ?>"><?php _e('Frontend', 'iq-block-country'); ?></a>  
		<a href="?page=iq-block-country/libs/blockcountry-settings.php&tab=backend" class="nav-tab <?php echo $active_tab == 'backend' ? 'nav-tab-active' : ''; ?>"><?php _e('Backend', 'iq-block-country'); ?></a>  
		<a href="?page=iq-block-country/libs/blockcountry-settings.php&tab=pages" class="nav-tab <?php echo $active_tab == 'pages' ? 'nav-tab-active' : ''; ?>"><?php _e('Pages', 'iq-block-country'); ?></a>  
		<a href="?page=iq-block-country/libs/blockcountry-settings.php&tab=categories" class="nav-tab <?php echo $active_tab == 'categories' ? 'nav-tab-active' : ''; ?>"><?php _e('Categories', 'iq-block-country'); ?></a>  
		<a href="?page=iq-block-country/libs/blockcountry-settings.php&tab=tags" class="nav-tab <?php echo $active_tab == 'tags' ? 'nav-tab-active' : ''; ?>"><?php _e('Tags', 'iq-block-country'); ?></a>
		<a href="?page=iq-block-country/libs/blockcountry-settings.php&tab=posttypes" class="nav-tab <?php echo $active_tab == 'posttypes' ? 'nav-tab-active' : ''; ?>"><?php _e('Post types', 'iq-block-country'); ?></a>
		<a href="?page=iq-block-country/libs/blockcountry-settings.php&tab=services" class="nav-tab <?php echo $active_tab == 'services' ? 'nav-tab-active' : ''; ?>"><?php _e('Services', 'iq-block-country'); ?></a>  
		<a href="?page=iq-block-country/libs/blockcountry-settings.php&tab=tools" class="nav-tab <?php echo $active_tab == 'tools' ? 'nav-tab-active' : ''; ?>"><?php _e('Tools', 'iq-block-country'); ?></a>  
		<a href="?page=iq-block-country/libs/blockcountry-settings.php&tab=logging" class="nav-tab <?php echo $active_tab == 'logging' ? 'nav-tab-active' : ''; ?>"><?php _e('Logging', 'iq-block-country'); ?></a>  
		<a href="?page=iq-block-country/libs/blockcountry-settings.php&tab=export" class="nav-tab <?php echo $active_tab == 'export' ? 'nav-tab-active' : ''; ?>"><?php _e('Import/Export', 'iq-block-country'); ?></a>  
	</h2>  
	
	<div class="wrap">
		<h2><?php echo PLUGINNAME;?></h2>
		<hr /><?php
		
		switch ($active_tab) {
			case "frontend": 
				iqblockcountry_settings_frontend();
				break;
			case "backend":
				iqblockcountry_settings_backend();
				break;
			case "tools":
				iqblockcountry_settings_tools();
				break;
			case "logging":
				iqblockcountry_settings_logging();
				break;
			case "pages":
				iqblockcountry_settings_pages();
				break;
			case "categories":
				iqblockcountry_settings_categories();
				break;
			case "tags":
				iqblockcountry_settings_tags();
				break;
			case "posttypes":
				iqblockcountry_settings_posttypes();
				break;
			case "services":
				iqblockcountry_settings_services();
				break;
			case "export":
				iqblockcountry_settings_importexport();
				break;
			default:
				 iqblockcountry_settings_home();
				 break;
		}
	?></div><?php
}


/*
 * Get different lists of black and whitelist
 */
function iqblockcountry_get_blackwhitelist() {
	$frontendblacklist = get_option ( 'blockcountry_frontendblacklist' );
	$frontendwhitelist = get_option ( 'blockcountry_frontendwhitelist' );
	$backendblacklist = get_option ( 'blockcountry_backendblacklist' );
	$backendwhitelist = get_option ( 'blockcountry_backendwhitelist' );
	
	$frontendblacklistip = array();
	$frontendwhitelistip = array();
	$backendblacklistip = array();
	$backendwhitelistip = array();
	
	$feblacklistip = array();
	$feblacklistiprange4 = array();
	$feblacklistiprange6 = array();
	$fewhitelistip = array();
	$fewhitelistiprange4 = array();
	$fewhitelistiprange6 = array();
	
	$beblacklistip = array();
	$beblacklistiprange4 = array();
	$beblacklistiprange6 = array();
	$bewhitelistip = array();
	$bewhitelistiprange4 = array();
	$bewhitelistiprange6 = array();
	
	global $feblacklistip,
		$feblacklistiprange4,
		$feblacklistiprange6,
		$fewhitelistip,
		$fewhitelistiprange4,
		$fewhitelistiprange6,
		$beblacklistip,
		$beblacklistiprange4,
		$beblacklistiprange6,
		$bewhitelistip,
		$bewhitelistiprange4,
		$bewhitelistiprange6;
	
	
	if (preg_match('/;/',$frontendblacklist)) {
		$frontendblacklistip = explode(";", $frontendblacklist);
		
		foreach ($frontendblacklistip AS $ip) {
			if (iqblockcountry_is_valid_ipv4($ip) || iqblockcountry_is_valid_ipv6($ip)) {
				$feblacklistip[] = $ip;
			} elseif (iqblockcountry_is_valid_ipv4_cidr($ip)) {
				$feblacklistiprange4[] = $ip;
			} elseif (iqblockcountry_is_valid_ipv6_cidr($ip)) {
				$feblacklistiprange6[] = $ip;
			}
		}
	}
	
	if (preg_match('/;/',$frontendwhitelist)) {
		$frontendwhitelistip = explode(";", $frontendwhitelist);
		
		foreach ($frontendwhitelistip AS $ip) {
			if (iqblockcountry_is_valid_ipv4($ip) || iqblockcountry_is_valid_ipv6($ip)) {
				$fewhitelistip[] = $ip;
			} elseif (iqblockcountry_is_valid_ipv4_cidr($ip)) {
				$fewhitelistiprange4[] = $ip;
			} elseif (iqblockcountry_is_valid_ipv6_cidr($ip)) {
				$fewhitelistiprange6[] = $ip;
			}
		}
	}
	
	if (preg_match('/;/',$backendblacklist)) {
		$backendblacklistip = explode(";", $backendblacklist);
		
		foreach ($backendblacklistip AS $ip) {
			if (iqblockcountry_is_valid_ipv4($ip) || iqblockcountry_is_valid_ipv6($ip)) {
				$beblacklistip[] = $ip;
			} elseif (iqblockcountry_is_valid_ipv4_cidr($ip)) {
				$beblacklistiprange4[] = $ip;
			} elseif (iqblockcountry_is_valid_ipv6_cidr($ip)) {
				$beblacklistiprange6[] = $ip;
			}
		}
	}
	
	if (preg_match('/;/',$backendwhitelist)) {
		$backendwhitelistip = explode(";", $backendwhitelist);
		
		foreach ($backendwhitelistip AS $ip) {
			if (iqblockcountry_is_valid_ipv4($ip) || iqblockcountry_is_valid_ipv6($ip)) {
				$bewhitelistip[] = $ip;
			} elseif (iqblockcountry_is_valid_ipv4_cidr($ip)) {
				$bewhitelistiprange4[] = $ip;
			} elseif (iqblockcountry_is_valid_ipv6_cidr($ip)) {
				$bewhitelistiprange6[] = $ip;
			}
		}
	}
}