<?php

/*
 * Check of an IP address is a valid IPv4 address
 */
function iqblockcountry_is_valid_ipv4($ipv4) {
	if (filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
		return false;
	}

	return true;
}

/*
 * Check of an IP address is a valid IPv6 address
 */
function iqblockcountry_is_valid_ipv6($ipv6) {
	if (filter_var($ipv6, FILTER_VALIDATE_IP,FILTER_FLAG_IPV6) === false) {
		return false;
	}

	return true;
}

function iqblockcountry_is_valid_ipv4_cidr($ip) {
	if (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/([0-9]|[1-2][0-9]|3[0-2]))$/',$ip)) {
		return true;
	} else {
		return false;
	}
}

function iqblockcountry_is_valid_ipv6_cidr($ip) {
	if (preg_match('/^s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|:)))(%.+)?s*(\/([0-9]|[1-9][0-9]|1[0-1][0-9]|12[0-8]))?$/',$ip)) {
		return true;
	} else {
		return false;
	}
}


/*
 * Check of given url is a valid url
 */
function iqblockcountry_is_valid_url($input) {
	if (filter_var($input,FILTER_VALIDATE_URL) === false) {
		return '';
	} else {
		return $input;
	}
}


 /*
  * Sanitize callback. Check if supplied IP address list is valid IPv4 or IPv6
  */
function iqblockcountry_validate_ip($input) {
	$validips = "";
	if (preg_match('/;/',$input)) {
		$arr = explode(";", $input);
		foreach ($arr as $value) {
			if (iqblockcountry_is_valid_ipv4($value) || iqblockcountry_is_valid_ipv6($value)  || iqblockcountry_is_valid_ipv4_cidr($value) || iqblockcountry_is_valid_ipv6_cidr($value)) {
				$validips .= $value . ";";
			}
		}
	} else {
		if (iqblockcountry_is_valid_ipv4($input) || iqblockcountry_is_valid_ipv6($input) || iqblockcountry_is_valid_ipv4_cidr($input) || iqblockcountry_is_valid_ipv6_cidr($input)) {
			$validips = $input . ";";
		}
	}
	return $validips;
}


/**
 * Check if a given ip is in a network
 * @param  string $ip	IP to check in IPV4 format eg. 127.0.0.1
 * @param  string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
 * @return boolean true if the ip is in this range / false if not.
 */
function iqblockcountry_ip_in_ipv4_range( $ip, $range ) {
	if ( strpos( $range, '/' ) == false ) {
		$range .= '/32';
	}
	
	// $range is in IP/CIDR format eg 127.0.0.1/24
	list( $range, $netmask ) = explode( '/', $range, 2 );
	
	$range_decimal = ip2long( $range );
	$ip_decimal = ip2long( $ip );
	$wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
	$netmask_decimal = ~ $wildcard_decimal;
	
	return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}


function iqblockcountry_ip2long6($ip) {
	if (substr_count($ip, '::')) { 
		$ip = str_replace('::', str_repeat(':0000', 8 - substr_count($ip, ':')) . ':', $ip); 
	} 
		
	$ip = explode(':', $ip);
	$r_ip = ''; 
	
	foreach ($ip as $v) {
		$r_ip .= str_pad(base_convert($v, 16, 2), 16, 0, STR_PAD_LEFT); 
	} 
		
	return base_convert($r_ip, 2, 10); 
}


// Get the ipv6 full format and return it as a decimal value.
function iqblockcountry_get_ipv6_full($ip) {
	$pieces = explode ("/", $ip, 2);
	$left_piece = $pieces[0];
	$right_piece = $pieces[1];

	// Extract out the main IP pieces
	$ip_pieces = explode("::", $left_piece, 2);
	$main_ip_piece = $ip_pieces[0];
	$last_ip_piece = $ip_pieces[1];
	
	// Pad out the shorthand entries.
	$main_ip_pieces = explode(":", $main_ip_piece);
	
	foreach($main_ip_pieces as $key => $val) {
		$main_ip_pieces[$key] = str_pad($main_ip_pieces[$key], 4, "0", STR_PAD_LEFT);
	}
	// Check to see if the last IP block (part after ::) is set
	$last_piece = "";
	$size = count($main_ip_pieces);
	
	if (trim($last_ip_piece) != "") {
		$last_piece = str_pad($last_ip_piece, 4, "0", STR_PAD_LEFT);
	
		// Build the full form of the IPV6 address considering the last IP block set
		for ($i = $size; $i < 7; $i++) {
			$main_ip_pieces[$i] = "0000";
		}
		
		$main_ip_pieces[7] = $last_piece;
	} else {
		// Build the full form of the IPV6 address
		for ($i = $size; $i < 8; $i++) {
			$main_ip_pieces[$i] = "0000";
		}		
	}
	
	// Rebuild the final long form IPV6 address
	$final_ip = implode(":", $main_ip_pieces);
	
	return iqblockcountry_ip2long6($final_ip);
}


// Determine whether the IPV6 address is within range.
// $ip is the IPV6 address in decimal format to check if its within the IP range created by the cloudflare IPV6 address, $range_ip. 
// $ip and $range_ip are converted to full IPV6 format.
// Returns true if the IPV6 address, $ip,  is within the range from $range_ip.  False otherwise.
function iqblockcountry_ipv6_in_range($ip, $range_ip) {
	$pieces = explode ("/", $range_ip, 2);
	$left_piece = $pieces[0];
	$right_piece = $pieces[1];
	
	// Extract out the main IP pieces
	$ip_pieces = explode("::", $left_piece, 2);
	$main_ip_piece = $ip_pieces[0];
	$last_ip_piece = $ip_pieces[1];
	
	// Pad out the shorthand entries.
	$main_ip_pieces = explode(":", $main_ip_piece);
	
	foreach($main_ip_pieces as $key => $val) {
		$main_ip_pieces[$key] = str_pad($main_ip_pieces[$key], 4, "0", STR_PAD_LEFT);
	}
	
	// Create the first and last pieces that will denote the IPV6 range.
	$first = $main_ip_pieces;
	$last = $main_ip_pieces;
	
	// Check to see if the last IP block (part after ::) is set
	$last_piece = "";
	$size = count($main_ip_pieces);
	
	if (trim($last_ip_piece) != "") {
		$last_piece = str_pad($last_ip_piece, 4, "0", STR_PAD_LEFT);
	
		// Build the full form of the IPV6 address considering the last IP block set
		for ($i = $size; $i < 7; $i++) {
			$first[$i] = "0000";
			$last[$i] = "ffff";
		}
		
		$main_ip_pieces[7] = $last_piece;
	} else {
		// Build the full form of the IPV6 address
		for ($i = $size; $i < 8; $i++) {
			$first[$i] = "0000";
			$last[$i] = "ffff";
		}		
	}
	
	// Rebuild the final long form IPV6 address
	$first = iqblockcountry_ip2long6(implode(":", $first));
	$last = iqblockcountry_ip2long6(implode(":", $last));
	$in_range = ($ip >= $first && $ip <= $last);
	
	return $in_range;
}


function iqblockcountry_validate_ip_in_list($ipaddress,$ipv4list,$ipv6list,$iplist) {
	$match = false;
	
	if (iqblockcountry_is_valid_ipv4($ipaddress) && is_array($ipv4list)) {
		foreach ($ipv4list as $iprange) {
			if (iqblockcountry_ip_in_ipv4_range($ipaddress,$iprange)) {
				$match = true;
			}
		}
	} elseif (iqblockcountry_is_valid_ipv6($ipaddress) && is_array($ipv6list)) {
		foreach ($ipv6list as $iprange) {
			$ipaddress6 = iqblockcountry_get_ipv6_full($ipaddress);
			if (iqblockcountry_ipv6_in_range($ipaddress6,$iprange)) {
				$match = true;
			}
		}
	 
	}
	
	if ((iqblockcountry_is_valid_ipv4($ipaddress) || iqblockcountry_is_valid_ipv6($ipaddress)) && is_array($iplist)) {
		if (in_array($ipaddress,$iplist)) {
			$match = true;
		}
	}
	
	return $match;
}
