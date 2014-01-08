<?php

	function sockIt($data, $host, $servlet="XMLAPI", $port=80, $time_out=20) {
		$sock = fsockopen ($host, $port, $errno, $errstr, $time_out); // open socket on port 80 w/ timeout of 20
		if (!$sock) {
			print("Could not connect to host:". $errno . $errstr);
			return (false);
		}
		$size = strlen ($data);
		fputs ($sock, "POST /servlet/" . $servlet . " HTTP/1.0\n");
		fputs ($sock, "Host: " . $host . "\n");
		fputs ($sock, "Content-type: application/x-www-form-urlencoded\n");
		fputs ($sock, "Content-length: " . $size . "\n");
		fputs ($sock, "Connection: close\n\n");
		fputs ($sock, $data);
		$buffer = "";
		while (!feof ($sock)) {
			$buffer .= fgets ($sock, 4096);
		}
		fclose ($sock);
		return ($buffer);
	}

	function login ($username, $password, $host, $servlet="XMLAPI", $port=80, $time_out=20) {
		$data = "xml=<?xml version=\"1.0\"?><Envelope><Body>";
		$data .= "<Login>";
		$data .= "<USERNAME>" . $username . "</USERNAME>";
		$data .= "<PASSWORD>" . $password . "</PASSWORD>";
		$data .= "</Login></Body></Envelope>";
		return sockIt($data, $host, $servlet, $port, $time_out);
	}

	function logout($host, $jsessionid, $servlet="XMLAPI", $port=80, $time_out=20) {
		$servlet = $servlet . ";jsessionid=" . $jsessionid;
		$data = <<<EOD
xml=<?xml version="1.0"?>		
<Envelope>
	<Body>
		<Logout/>
	</Body>
</Envelope>	
EOD;
		return sockIt($data, $host, $servlet, $port, $time_out);
	}

	function executeQuery($queryId, $email, $host, $jsessionid, $servlet="XMLAPI", $port=80, $time_out=20) {
		$servlet = $servlet . ";jsessionid=" . $jsessionid;
		$data = <<<EOD
xml=<?xml version="1.0"?>		
<Envelope>
	<Body>
		<CalculateQuery>
			<QUERY_ID>$queryId</QUERY_ID>
			<EMAIL>$email</EMAIL>
		</CalculateQuery>
	</Body>
</Envelope>	
EOD;
		return sockIt($data, $host, $servlet, $port, $time_out);
	}
	
	function getJobStatus($jobId, $host, $jsessionid, $servlet="XMLAPI", $port=80, $time_out=20) {
		$servlet = $servlet . ";jsessionid=" . $jsessionid;
		$data = <<<EOD
xml=<?xml version="1.0"?>		
<Envelope>
	<Body>
		<GetJobStatus>
			<JOB_ID>$jobId</JOB_ID>
		</GetJobStatus>
	</Body>
</Envelope>	
EOD;
		return sockIt($data, $host, $servlet, $port, $time_out);
	}

	function exportFileToFTP($listId, $host, $jsessionid, $servlet="XMLAPI", $port=80, $time_out=20) {
		$servlet = $servlet . ";jsessionid=" . $jsessionid;
		$data = <<<EOD
xml=<?xml version="1.0"?>		
<Envelope>
	<Body>
	<ExportList>
		<LIST_ID>$listId</LIST_ID>
		<EXPORT_TYPE>ALL</EXPORT_TYPE>
		<EXPORT_FORMAT>PIPE</EXPORT_FORMAT>
		<ADD_TO_STORED_FILES/>
	</ExportList>
	</Body>
</Envelope>	
EOD;
		return sockIt($data, $host, $servlet, $port, $time_out);
	}

	function getFile($server, $user_name, $user_pass, $source_file, $destination_file, $ftp_mode = FTP_ASCII) {
		$ret = false;
		
		// set up basic connection
		$conn_id = ftp_connect($server); 

		// login with username and password
		$login_result = ftp_login($conn_id, $user_name, $user_pass); 

		// check connection
		if ((!$conn_id) || (!$login_result)) { 
			echo "\nFTP connection has failed!";
			echo "\nAttempted to connect to $server for user $user_name"; 
			exit; 
		} else {
			echo "\nConnected to $server, for user $user_name";
		}

		// download the file
		$download = ftp_get($conn_id, $destination_file, $source_file, $ftp_mode); 
		if (!$download) { 
		    echo "\nFTP download has failed! $destination_file | $source_file | $ftp_mode";
		} else {
		    echo "\nDownloaded $source_file from $server as $destination_file";
			$ret = true;
		}

		// close the FTP stream 
		ftp_close($conn_id);
		return $ret;
	}
	
	function isSuccessfulRequest($response, $successStr = "HTTP/1.1 200 OK") {
		return stristr($response,$successStr);
	}
	
	function isSuccessfulResponse($xml) {
		return $xml->Body->RESULT->SUCCESS;
	}
	
	function getXMLObject($response, $envelopeStrStart = "<Envelope>", $envelopeStrEnd = "</Envelope>") {
		$xml = false;
		if (isSuccessfulRequest($response)) {
			$posStart = stripos($response,$envelopeStrStart);
			$posEnd = stripos($response,$envelopeStrEnd);
			$posLen = ($posEnd + strlen($envelopeStrEnd)) - $posStart + 1;
			if ($posStart === false) {
				$xml = false;
			} else {
				$env = substr($response,$posStart, $posLen);
				$env = str_ireplace(" & ", " &amp; ", $env);
				$xml = new SimpleXMLElement($env);
				if (strtolower(isSuccessfulResponse($xml)) != "true") {
					$xml = false;
				}
			}
		}
		return $xml;
	}
	
	function getSessionId($xml) {
		return $xml->Body->RESULT->SESSIONID;
	}
	
	function getJobStatusValue($xml) {
		return strtoupper($xml->Body->RESULT->JOB_STATUS);
	}
	
	function getJobId($xml) {
		$jobId = $xml->Body->RESULT->JOB_ID;
		if (!$jobId) {
			$jobId = $xml->Body->RESULT->MAILING->JOB_ID;
		}
		return $jobId;
	}
	
	function getListId($xml) {
		$listId = null;
		$parameters = $xml->Body->RESULT->PARAMETERS->PARAMETER;
		if ($parameters) {
			foreach ($parameters AS $parameter) {
				if ($parameter->NAME == "LIST_ID") {
					$listId = $parameter->VALUE;
					break;
				}
			}
		}
		return $listId;
	}

	function getFilename($xml) {
		$filename = $xml->Body->RESULT->FILE_PATH;
		if (!$filename) {
			$filename = $xml->Body->RESULT->MAILING->FILE_PATH;		
		}
		return $filename;
	}
	
	function getJobStatusLoop($jobId, $host, $sessionId, &$xml, $numAttempts = 600) {
		$isCompleted = false;
		$attempts = 0;
		$response = null;
		//sleep(300); // Sleep for the first five minutes no matter what.
		while (!$isCompleted && $attempts < $numAttempts) {
			$response = getJobStatus($jobId, $host, $sessionId);
			$xml = getXMLObject($response);
			//print_r($xml); exit();
			if (!$xml) {
				echo "\nError: Silverpop get job status failed. ($response)";
				exit(-1);
			}
			$jobStatus = getJobStatusValue($xml);
			switch ($jobStatus) {
				case "COMPLETE":
					$isCompleted = true;
					break;
				case "RUNNING":
				case "WAITING":
					// Give the job time to complete.
					sleep(60);
					break;
				case "CANCELED":
				case "ERROR":
				default:
					echo "\nError: Silverpop get job status execution failed. ($response)";
					exit(-1);
					break;
			}
			// Increment the attempts; limit attempts.
			$attempts++;
		}
		if ($isCompleted === false) {
			$isCompleted = $response;
		}
		return $isCompleted;
	}

	function exportRawRecipientData($mailingId, $startDate, $endDate, $email, $host, $jsessionid, $servlet="XMLAPI", $port=80, $time_out=20) {
		$servlet = $servlet . ";jsessionid=" . $jsessionid;
		$databaseIdXML = "";
		if ($databaseId !== null) {
			$databaseIdXML = "<LIST_ID>$databaseId</LIST_ID>";
		}
		$mailingIdXML = "";
		if ($mailingId !== null) {
			if (is_array($mailingId)) {
				$str = "";
				foreach ($mailingId AS $mailing) {
					$str .= "<MAILING>$mailing</MAILING>\n";
				}
				if (strlen($str)) {
					$mailingId = "\n".$str;
				}
			}
			$mailingIdXML = "<MAILING_ID>$mailingId</MAILING_ID>";
		}
		$startDateXML = "<EVENT_DATE_START>$startDate</EVENT_DATE_START>";
		if ($startDate === null) {
			$startDateXML = "";
		}
		$endDateXML = "<EVENT_DATE_END>$endDate</EVENT_DATE_END>";
		if ($endDate === null) {
			$endDateXML = "";
		}
		$startSendDateXML = "<SEND_DATE_START>$startDate</SEND_DATE_START>";
		if ($startDate === null) {
			$startSendDateXML = "";
		}
		$endSendDateXML = "<SEND_DATE_END>$endDate</SEND_DATE_END>";
		if ($endDate === null) {
			$endSendDateXML = "";
		}
//			<INCLUDE_INBOX_MONITORING/>
//			<RETURN_MAILING_NAME>true</RETURN_MAILING_NAME>
//			<RETURN_SUBJECT>true</RETURN_SUBJECT>
//			<RETURN_CRM_CAMPAIGN_ID>true</RETURN_CRM_CAMPAIGN_ID>
		$data = <<<EOD
xml=<?xml version="1.0"?>		
<Envelope>
	<Body>
		<RawRecipientDataExport>
			$databaseIdXML
			$mailingIdXML
			$startDateXML
			$endDateXML
			<MOVE_TO_FTP/>
			<EXPORT_FORMAT>1</EXPORT_FORMAT>
			<EMAIL>$email</EMAIL>
			<ALL_EVENT_TYPES/>
			<SENT_MAILINGS/>
			<CAMPAIGN_ACTIVE/>
		</RawRecipientDataExport>
	</Body>
</Envelope>	
EOD;
		//echo $data;
		return sockIt($data, $host, $servlet, $port, $time_out);
	}
	
	function exportTrackingMetric($databaseId, $mailingId, $startDate, $endDate, $email, $host, $jsessionid, $servlet="XMLAPI", $port=80, $time_out=20) {
		$servlet = $servlet . ";jsessionid=" . $jsessionid;
		$databaseIdXML = "";
		if ($databaseId !== null) {
			$databaseIdXML = "<LIST_ID>$databaseId</LIST_ID>";
		}
		$mailingIdXML = "";
		if ($mailingId !== null) {
			$mailingIdXML = "<MAILING_ID>$mailingId</MAILING_ID>";
		}
		$startDateXML = "<EVENT_DATE_START>$startDate</EVENT_DATE_START>";
		if ($startDate === null) {
			$startDateXML = "";
		}
		$endDateXML = "<EVENT_DATE_END>$endDate</EVENT_DATE_END>";
		if ($endDate === null) {
			$endDateXML = "";
		}
		$data = <<<EOD
xml=<?xml version="1.0"?>		
<Envelope>
	<Body>
		<TrackingMetricExport>
			$databaseIdXML
			$mailingIdXML
			$startDateXML
			$endDateXML
			<MOVE_TO_FTP/>
			<EXPORT_FORMAT>PIPE</EXPORT_FORMAT>
			<EMAIL>$email</EMAIL>
		</TrackingMetricExport>
	</Body>
</Envelope>	
EOD;
		return sockIt($data, $host, $servlet, $port, $time_out);
	}

	function getSentMailingsForOrg($startDate, $endDate, $host, $jsessionid, $servlet="XMLAPI", $port=80, $time_out=20) {
		$servlet = $servlet . ";jsessionid=" . $jsessionid;
		$mailingIdXML = "";
		if ($mailingId !== null) {
			$mailingIdXML = "<MAILING_ID>$mailingId</MAILING_ID>";
		}
		$startDateXML = "<DATE_START>$startDate</DATE_START>";
		if ($startDate === null) {
			$startDateXML = "";
		}
		$endDateXML = "<DATE_END>$endDate</DATE_END>";
		if ($endDate === null) {
			$endDateXML = "";
		}
//			<SHARED/>

		$data = <<<EOD
xml=<?xml version="1.0"?>		
<Envelope>
	<Body>
		<GetSentMailingsForOrg>
			$startDateXML
			$endDateXML
			<SENT/>
			<EXCLUDE_TEST_MAILINGS/>
		</GetSentMailingsForOrg>
	</Body>
</Envelope>	
EOD;
		return sockIt($data, $host, $servlet, $port, $time_out);
	}
	
	function addDCRuleset($databaseId, $mailingId, $subject, $host, $jsessionid, $rulesetName = null, $servlet="XMLAPI", $port=80, $time_out=20) {
		$servlet = $servlet . ";jsessionid=" . $jsessionid;
		$databaseIdXML = "";
		if ($databaseId !== null) {
			$databaseIdXML = "<LIST_ID>$databaseId</LIST_ID>";
		}
		$mailingIdXML = "";
		if ($mailingId !== null) {
			$mailingIdXML = "<MAILING_ID>$mailingId</MAILING_ID>";
		}
		if ($rulesetName === null) {
			$rulesetName = "Ruleset for Mailing #$rulesetName";
		}
		$rulesetNameXML = "<RULESET_NAME>$rulesetName</RULESET_NAME>";
		$data = <<<EOD
xml=<?xml version="1.0"?>		
<Envelope>
	<Body>
		<AddDCRuleset>
			$rulesetNameXML
			$databaseIdXML
			$mailingIdXML
			<CONTENT_AREAS>
				<CONTENT_AREA name="dc_mailing_subject" type="Subject">	
					<DEFAULT_CONTENT name="Default.dc_mailing_subject"><![CDATA[$subject]]></DEFAULT_CONTENT>
				</CONTENT_AREA>	
			</CONTENT_AREAS>				
			<RULES>
				<RULE>
					<RULE_NAME>Always A Match</RULE_NAME>
					<PRIORITY>1</PRIORITY>
					<CRITERIA>
						<EXPRESSION>
							<AND_OR/>
							<LEFT_PARENS>(</LEFT_PARENS>
								<TYPE>TE</TYPE>
								<COLUMN_NAME>Email</COLUMN_NAME>
								<OPERATORS><![CDATA[!=]]></OPERATORS>
								<VALUES><![CDATA[EMAIL]]></VALUES>
							<RIGHT_PARENS>)</RIGHT_PARENS>
						</EXPRESSION>
					</CRITERIA>
					<CONTENTS>
						<CONTENT name="always_subject" content_area="dc_mailing_subject"><![CDATA[$subject]]></CONTENT>
					</CONTENTS>
				</RULE>
			</RULES>
		</AddDCRuleset>
	</Body>
</Envelope>	
EOD;
		//echo $data;
		return sockIt($data, $host, $servlet, $port, $time_out);
	}
	
	function replaceDCRuleset($rulesetId, $subject, $host, $jsessionid, $servlet="XMLAPI", $port=80, $time_out=20) {
		$servlet = $servlet . ";jsessionid=" . $jsessionid;
		$rulesetIdXML = "";
		if ($rulesetId !== null) {
			$rulesetIdXML = "<RULESET_ID>$rulesetId</RULESET_ID>";
		}
		$data = <<<EOD
xml=<?xml version="1.0"?>		
<Envelope>
	<Body>
		<ReplaceDCRuleset>
			$rulesetIdXML
			<CONTENT_AREAS>
				<CONTENT_AREA name="dc_mailing_subject" type="Subject">	
					<DEFAULT_CONTENT name="Default.dc_mailing_subject"><![CDATA[$subject]]></DEFAULT_CONTENT>
				</CONTENT_AREA>	
			</CONTENT_AREAS>				
			<RULES>
				<RULE>
					<RULE_NAME>Always A Match</RULE_NAME>
					<PRIORITY>1</PRIORITY>
					<CRITERIA>
						<EXPRESSION>
							<AND_OR/>
							<LEFT_PARENS>(</LEFT_PARENS>
								<TYPE>TE</TYPE>
								<COLUMN_NAME>Email</COLUMN_NAME>
								<OPERATORS><![CDATA[!=]]></OPERATORS>
								<VALUES><![CDATA[EMAIL]]></VALUES>
							<RIGHT_PARENS>)</RIGHT_PARENS>
						</EXPRESSION>
					</CRITERIA>
					<CONTENTS>
						<CONTENT name="always_subject" content_area="dc_mailing_subject"><![CDATA[$subject]]></CONTENT>
					</CONTENTS>
				</RULE>
			</RULES>
		</ReplaceDCRuleset>
	</Body>
</Envelope>	
EOD;
		//echo $data;
		return sockIt($data, $host, $servlet, $port, $time_out);
	}

	function selectRecipientData($databaseId, $email, $host, $jsessionid, $userId=null, $servlet="XMLAPI", $port=80, $time_out=20) {
		$servlet = $servlet . ";jsessionid=" . $jsessionid;
		$emailXML = "";
		if ($email !== null) {
			$emailXML = "<EMAIL>$email</EMAIL>";
		}
		$userIdXML = "";
		if ($userId !== null) {
			$userIdXML = "<ENCODED_RECIPIENT_ID>$userId</ENCODED_RECIPIENT_ID>";
		}

		$data = <<<EOD
xml=<?xml version="1.0"?>		
<Envelope>
	<Body>
		<SelectRecipientData>
			<LIST_ID>$databaseId</LIST_ID>
			$emailXML
			$userIdXML
		</SelectRecipientData>
	</Body>
</Envelope>	
EOD;
		return sockIt($data, $host, $servlet, $port, $time_out);
	}
	
	function addRecipient($databaseId, $email, $host, $jsessionid, $createdFrom = 2, $servlet="XMLAPI", $port=80, $time_out=20) {
		$servlet = $servlet . ";jsessionid=" . $jsessionid;

		$data = <<<EOD
xml=<?xml version="1.0"?>		
<Envelope>
	<Body>
		<AddRecipient>
			<LIST_ID>$databaseId</LIST_ID>
			<CREATED_FROM>$createdFrom</CREATED_FROM>
			<COLUMN>
				<NAME>EMAIL</NAME>
				<VALUE>$email</VALUE>
			</COLUMN>
		</AddRecipient>
	</Body>
</Envelope>	
EOD;
		return sockIt($data, $host, $servlet, $port, $time_out);
	}
	
	function updateRecipient($databaseId, $email, $nl, $host, $jsessionid, $source = "Modal Pop-up", $servlet="XMLAPI", $port=80, $time_out=20) {
		$servlet = $servlet . ";jsessionid=" . $jsessionid;

		$nlFlag = "";
		$nlSubDate = "";
		if ($nl !== null) {
			$nlFlag = $nl."_Flag";
			$nlSubDate = $nl."_Sub_Date";
		}
		$today = date("m/d/Y", strtotime('today'));	
		
		$data = <<<EOD
xml=<?xml version="1.0"?>		
<Envelope>
	<Body>
		<UpdateRecipient>
			<LIST_ID>$databaseId</LIST_ID>
			<OLD_EMAIL>$email</OLD_EMAIL>
			<COLUMN>
				<NAME>$nlFlag</NAME>
				<VALUE>Yes</VALUE>
			</COLUMN>
			<COLUMN>
				<NAME>$nlSubDate</NAME>
				<VALUE>$today</VALUE>
			</COLUMN>
			<COLUMN>
				<NAME>SOURCE</NAME>
				<VALUE>$source</VALUE>
			</COLUMN>
		</UpdateRecipient>
	</Body>
</Envelope>	
EOD;
		return sockIt($data, $host, $servlet, $port, $time_out);
	}

	function sendMailing($databaseId, $mailingId, $email, $host, $jsessionid, $servlet="XMLAPI", $port=80, $time_out=20) {
		$servlet = $servlet . ";jsessionid=" . $jsessionid;

		$data = <<<EOD
xml=<?xml version="1.0"?>		
<Envelope>
	<Body>
		<SendMailing>
			<MailingId>$mailingId</MailingId>
			<RecipientEmail>$email</RecipientEmail>
		</SendMailing>
	</Body>
</Envelope>	
EOD;
		return sockIt($data, $host, $servlet, $port, $time_out);
	}
	
	function getValue($arr, $name) {
		$ret = null;
		foreach ($arr AS $nvPair) {
			if ($name == $nvPair->NAME) {
				$ret = "".$nvPair->VALUE;
				break;
			}
		}
		return $ret;
	}
	
?>










