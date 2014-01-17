<?php

include($incPath.'test_sp_export_api.php');

// Params
$host = "api4.silverpop.com";
$username = "matthew.clemmer@jsnconsulting.com";
$password = "vAWnLR9V#";
$databaseId = "10170434"; //"11408595"; // EMAIL_PROFILE_MASTER
$mailingId = "44556845";
//$nls = array("NL","BLS","DHN","HMD","BHW","HEFL","DOLAN","DBOE"); // From SP. Note: NL value is for development testing only.
$nls = array("BLS","DHN","HMD","BHW","HEFL","DOLAN","DBOE"); // From SP.
$reqResponse = "false";

// Get email from request.
$email = $_REQUEST['email'];
// Get source from request.
$source = $_REQUEST['source'] ? $_REQUEST['source'] : "Modal Pop-up";
// Get mailing id from request.
$mailingId = $_REQUEST['mid'] ? $_REQUEST['mid'] : $mailingId;
if ($email) {
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
				$response = addRecipient($databaseId, $email, $host, $sessionId);
				$xml = getXMLObject($response);
				if ($xml) {
					$recipientId = $xml->Body->RESULT->RecipientId;
				}
				$response = updateRecipient($databaseId, $email, $nl, $host, $sessionId, $source);
				$xml = getXMLObject($response);
				if ($xml) {
					$reqResponse = "true";
				}
				$response = sendMailing($databaseId, $mailingId, $email, $host, $sessionId);
				$xml = getXMLObject($response);
				if ($xml) {
					$reqResponse = "true";
				}
			}
		}
		// Logout of Silverpop Engage
		$response = logout($host, $sessionId);
	}
}

// Send response in form of JSON.
$json = json_encode($reqResponse);
echo $json;

?>