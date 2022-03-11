<?php
/**
 * WhoisIp extension
 * 
 * GET and POST requests work.
 * So, we can do a simple https://ipwhois.app/json/54.39.77.167 and evaluate the JSON which we get as response. :)
 * 
 * Keep in mind that ipwhois.app has limited free requests to a maximum of 10,000 per month. You can see the request count in their API response.
 *
 * @author nrekow
 * 
 */

/**
 * Replace all new-line characters with UNIX-style new-line characters. 
 * 
 * @param string $str
 * @param string $nl
 * @return mixed
 */
function nl2nl($str, $nl = "\n") {
	$str = str_replace(array("\r\n", "\r", "\n"), $nl, $str);
	return $str;
}


if (!isset($_REQUEST['ajax']) || empty($_REQUEST['ajax'])) {
	echo 'This module cannot be accessed directly.';
	die();
}


// Check if a valid ip has been specified.
if (isset($_REQUEST['ip']) && !empty($_REQUEST['ip'])) {
	if (preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/', trim($_REQUEST['ip'])) !== false) {
		$ipAddress = $_REQUEST['ip'];

		if (isset($_REQUEST['gethostname']) && !empty($_REQUEST['gethostname'])) {
			// For now this is never used anywhere.
			$html = gethostbyaddr($ipAddress);
		} else {
			// Do a cURL request against the API.
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://ipwhois.app/json/' . $ipAddress);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Receive server response
			$response = curl_exec($ch);
			curl_close($ch);
			
			// Decode JSON
			$json = json_decode($response, true, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
			
			// Prepare outpot (e.g. make it look nice)
			$html = 'Failed to decode server response.';
			if ($json !== null && $json !== false) {
				$html = '';
				foreach ($json as $key => $value) {
					$html .= '<strong>' . ucfirst(str_replace('_', ' ', $key)) . ':</strong> ' . $value . '<br/>';
				}
			}
			
		}
		
		// Show result.
		echo $html;
	}
}

die();