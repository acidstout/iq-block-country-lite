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
		$html = str_replace("\t", '', $html);
		$html = str_replace("\n", '', $html);
		$html = str_replace("\r", '', $html);
		$html = str_replace('  ', '', $html);
		$html = str_replace(' >', '>', $html);
		$html = str_replace('> <', '><', $html);
		$html = substr($html, strpos($html, '<div class="tool-results">'));
		$html = substr($html, 0, strpos($html, '</div></div><iframe'));
		
		// Show result.
		echo '<!doctype html><html><head><meta charset="utf-8"/></head><body>' . $html . '</body></html>';
	}
}

die();