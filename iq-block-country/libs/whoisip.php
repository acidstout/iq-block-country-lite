<?php
/**
 * Whoisip extension
 * 
 * GET and POST requests work.
 * So, we can do a simple https://www.ultratools.com/tools/ipWhoisLookupResult?ipAddress=54.39.77.167 in an iframe. :)
 *
 * @author nrekow
 * 
 */

function nl2nl($string, $nl = "\n") {
	$string = str_replace(array("\r\n", "\r", "\n"), $nl, $string);
	return $string;
}


if (!isset($_REQUEST['ajax']) || empty($_REQUEST['ajax'])) {
	echo 'This module cannot be accessed directly.';
	die();
}

// Fallback ip-address.
//$ipAddress = '54.39.77.167';

// Check if a valid ip has been specified.
if (isset($_REQUEST['ip']) && !empty($_REQUEST['ip'])) {
	if (preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/', trim($_REQUEST['ip'])) !== false) {
		$ipAddress = $_REQUEST['ip'];

		// Do cURL request.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://www.ultratools.com/tools/ipWhoisLookupResult');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('ipAddress' => $ipAddress)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Receive server response
		$html = curl_exec($ch);
		curl_close($ch);
		
		// Clean output.
		$html = nl2nl($html);
		$html = str_replace("\t", '', $html);
		$html = str_replace(' >', '>', $html);
		$html = str_replace('> <', '><', $html);
		$html = substr($html, strpos($html, '<div class="tool-results">'));
		$html = substr($html, 0, strpos($html, '<iframe'));
		
		// Show result.
		echo $html;
	}
}

die();