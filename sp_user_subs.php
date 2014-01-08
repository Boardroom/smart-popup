<?php

include($incPath.'test_sp_export_clickstream_api.php');

// Params
$host = "xxx.xxxxxxxx.com";
$username = "email@domain.com";
$password = "password";
$databaseId = "xxxxxxxx"; 

$nls = array("BLS","DHN","HMDS","BLHW","HEFL","BLM","DBE"); // From E_MASTER.
// $nls = array("NL","BLS","DHN","HMD","BHW","HEFL","DOLAN","DBOE"); // From SP. Note: NL value is for development testing only.
//$nls = array("NL","BLS","DHN","HMD","BHW","HEFL","DOLAN","DBOE"); // From SP. Note: NL value is for development testing only.
$subs = array();

// Get email from request.
$uid = $_REQUEST['uid'];
if ($uid) {
	// Get newsletter from request.
	$nl = $_REQUEST['nl'];
	// Check for valid newsletter.
	if (in_array(strtoupper($nl), $nls)) {
		// Login to Silverpop Engage
		$response = login($username, $password, $host);
		$xml = getXMLObject($response);
		$sessionId = null;
		if ($xml) {
			$sessionId = getSessionId($xml);
			if ($sessionId) {
				$response = selectRecipientData($databaseId, null, $host, $sessionId, $uid);
				$xml = getXMLObject($response);
				if ($xml) {
					$subs[$nl] = getValue($xml->Body->RESULT->COLUMNS->COLUMN, $nl."_Flag");
				}
			}
		}
		// Logout of Silverpop Engage
		$response = logout($host, $sessionId);
	}
}

// Send response in form of JSON.
if (count($subs) == 0) {
	$json_subs = "null";
} else {
	$json_subs = json_encode($subs);
}
echo $json_subs;

?>