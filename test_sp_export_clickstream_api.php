<?php

	include($incPath.'test_sp_export_api.php');

	function unzipFile($file,$fileOut,$path){
		echo "\n";
		system("unzip '".$file."'");
		system("mv '".$fileOut."' '".$path.$fileOut."'");
		unlink($file);
		return true;
	} 

	function outputMailingFile($xml,$filePrefix = null) {
		if (!$filePrefix) {
			$filePrefix = date("Y-m-d_H-i-s");
		}
		$filePath = "download/clickstream/zeta_format/";
		$file = $filePath.$filePrefix."CMP_Exportresponses".".csv";
		$handle = @fopen($file, "w");
		$fieldNames = "Blastid,TimeSent,MailingName,Subject";
		$ok = writeFieldNames($handle, $fieldNames);
		if (!$ok) {
			echo "\nError:  Unable to write field names (mailing file).";
			exit(1);
		}
		$mailings = $xml->Body->RESULT->Mailing;
		foreach ($mailings AS $mailing) {
			$fields = array();
			$fields[] = $mailing->MailingId;
//			$fields[] = date_format(date_create($mailing->SentTS), 'Y-m-d H:i:s');
			$fields[] = date('Y-m-d H:i:s', strtotime($mailing->SentTS));
			$fields[] = $mailing->MailingName;
			$fields[] = $mailing->Subject;
			$ok = writeFields($handle, $fields);
			if (!$ok) {
				echo "\nError:  Unable to write a record (mailing file).";
				exit(1);
			}
		}
		fclose($handle);
		return true;
	}
	
	function cmp($a, $b)
	{
		if ($a[3] == $b[3]) {
			if ($a[0] == $b[0]) {
				return 0;
			} 
			return ($a[0] < $b[0]) ? -1 : 1;
		}
		return ($a[3] < $b[3]) ? -1 : 1;
	}

	function cmp_alt($a, $b)
	{
		if ($a[0] == $b[3]) {
			if ($a[3] == $b[0]) {
				return 0;
			} 
			return ($a[3] < $b[0]) ? -1 : 1;
		}
		return ($a[0] < $b[3]) ? -1 : 1;
	}

    function binary_search(array $a, $first, $last, $key, $compare) {
		$lo = $first;
		$hi = $last - 1;
 
		while ($lo <= $hi) {
			$mid = (int)(($hi - $lo) / 2) + $lo;
			$cmp = call_user_func($compare, $a[$mid], $key);
 
			if ($cmp < 0) {
				$lo = $mid + 1;
			} elseif ($cmp > 0) {
				$hi = $mid - 1;
			} else {
				return $mid;
			}
		}
		return -($lo + 1);
	}	
	
	function isBounce($cols, &$bounces, &$idxBounce, $cntBounces) {
		$ret = false;
		$bounce = $bounces[$idxBounce];
		$cmp = cmp_alt($cols, $bounce);
		$x = 0;
		while ($cmp == 1 && ($idxBounce < ($cntBounces - 1))) {
			$x++;
			if ($x > 10000) {
				exit;
			}
			$idxBounce++;
			$bounce = $bounces[$idxBounce];
			$cmp = cmp_alt($cols, $bounce);
		}
		if ($cmp == 0) {
			$ret = true;
		}
		return $ret;
	}
	
	function processClickstreamDataBounces($filename, $bounces) {
		$ret = false;
		$idxBounce = 0;
		usort($bounces, "cmp");
		echo "\nnum bounce = ".count($bounces)."\n";
		// Read the sent clickstream file, line by line.
		$handle = @fopen($filename, "r");
		$filenameOut = $filename."_temp";
		$outFile = fopen($filenameOut, "w");
		$num = 0;
		$cols = array();
		if ($handle) {
			$cntBounces = count($bounces);
			while (($buffer = fgets($handle)) !== false) {
//				if ($num % 10000 === 0) {
//					echo "\n$num processed...".date("Y-m-d H-i-s");
//				}
				if ($num == 0) {
//ttt					fwrite($outFile, $buffer);
$xyz = 1;//ttt
				} else {
					$cols = explode(',',$buffer);
					if ($idxBounce < $cntBounces) {
						$isBounce = isBounce($cols, $bounces, $idxBounce, $cntBounces);
						if ($isBounce) {
							$cols[7] = "F";
							$idxBounce++;
						}
					}
					$temp = $cols[0];
					$cols[0] = $cols[3];
					$cols[3] = $temp;
					$buffer = implode(',',$cols);
					fwrite($outFile, $buffer);
				}
				$num++;
			}
			if (!feof($handle)) {
				echo "\nError: unexpected fgets() fail\n";
			} else {
				$ret = true;
			}
			fclose($handle);
			fclose($outFile);
			unlink($filename);
			rename($filenameOut, $filename); 
		}				
		return $ret;
	}
	
	function processClickstreamData($filename, &$bounces, &$sentFilename, $filePrefix = null) {
		$ret = false;
		if (!$filePrefix) {
			$filePrefix = date("Y-m-d_H-i-s");
		}
		// Read the clickstream file, line by line.
		$handle = @fopen($filename, "r");
		$num = 0;
		$numCols = 0;
		$colNames = array();
		$cols = array();
		$files = array();
		if ($handle) {
			while (($buffer = fgets($handle)) !== false) {
				if ($num == 0) {
					// Grab column names.
					$colNames = explode('|',$buffer);
					$evalCols = array(
								"Mailing Id" => null,
								"Recipient Id" => null,
								"Event Timestamp" => null,
								"Email" => null,
								"Event Type" => null,
								"URL" => null
								);
					foreach ($evalCols AS $key => $value) {
						$pos = array_search($key, $colNames);
						if ($pos !== false) {
							$evalCols[$key] = $pos;
						}
					}
					// Open all of the possible files.
					$eventTypes = array(					
						"Reply Mail Block",
						"Open",
						"Reply Other",
						"Reply Change Address",
						"Soft Bounce",
						"Hard Bounce",
						"Forward",
						"Click Through",
						"Opt Out",
						"Reply Abuse",
						"Sent",
						"Suppressed"
						);
					$files = openFiles($eventTypes, $sentFilename, $filePrefix);
				} else {
					$cols = explode('|',$buffer);
					if (count($cols) == count($colNames)) {
						$ok = processEvent($cols, $evalCols, $files, $bounces);
						if (!$ok) {
							echo "\nError:  Failed to process clickstream event.";
							exit(1);
						}
					}
				}
				$num++;
			}
			if (!feof($handle)) {
				echo "\nError: unexpected fgets() fail\n";
			} else {
				$ret = true;
			}
			fclose($handle);
		}				
		return $ret;
	}

	function openFiles($eventTypes, &$sentFilename, $filePrefix = null) {
		if (!$filePrefix) {
			$filePrefix = date("Y-m-d_H-i-s");
		}
		$files = array();
		$file = null;
		$filePath = "download/clickstream/zeta_format/";
		foreach ($eventTypes AS $eventType) {
			switch ($eventType) {
				case "Clickstream":
				case "Conversion":
				case "Attachment":
				case "Media":
				case "SMS Error":
				case "SMS Reject":
				case "SMS Opt Out":			
				case "Reply Mail Block":
				case "Reply Change Address":
				case "Reply Other":
				case "Reply Change Address":
				case "Reply Abuse":
				case "Forward":
				case "Opt In":
				case "Opt Out":
				case "Suppressed":
					$files[$eventType] = null;
					break;
				case "Soft Bounce":
				case "Hard Bounce":
					//$file = $filePath.$filePrefix."BOUNCE_Exportresponses";
					//$fieldNames = "BlastId,CustomerId,DeliveryDate,Email,ErrorCode,ErrorDescription,ProfileID,Status,BlastInstanceId,CampaignName,ContentsetName,DepartmentName,Domain,RetryCount";
					$files[$eventType] = null;
					break;
				case "Click Through":
					$file = $filePath.$filePrefix."CLK_Exportresponses";
					$fieldNames = "BlastId,ClickDate,CustomerId,Email,Id,BlastInstanceId,BrowserAgent,ContentsetName,CampaignName,DepartmentName,Domain,EmailIp,Url,UrlName,ProfileID";
					break;
				case "Open":
					$file = $filePath.$filePrefix."OPN_Exportresponses";
					$fieldNames = "BlastId,CustomerId,Email,Id,OpenDate,ProfileID,BlastInstanceId,BrowserAgent,CampaignName,ContentsetName,DepartmentName,Domain,EmailIp";
					break;
				case "Sent": // Delivered
					$file = $filePath.$filePrefix."DEL_Exportresponses";
					$fieldNames = "BlastId,CustomerId,DeliveryDate,Email,ErrorCode,ErrorDescription,ProfileID,Status,BlastInstanceId,CampaignName,ContentsetName,DepartmentName,Domain,RetryCount";
					$sentFilename = $file.".csv";
					break;
				default:
					$files[$eventType] = null;
					break;
			}
			if ($file) {
				$files[$eventType] = openFile($file);
if ($eventType == "Sent") {//ttt	
				$ok = writeFieldNames($files[$eventType], $fieldNames);
				if (!$ok) {
					echo "\nError:  Unable to write field names ($eventType).";
					exit(1);
				}
}//ttt
				$file = null;
			}			
		}
		return $files;
	}

	function closeFiles($files) {
		foreach ($files AS $file) {
			fclose($file);
		}
	}
	
	function openFile($filename, $extension = ".csv") {
		$handle = @fopen($filename.$extension, "w");
		return $handle;
	}
	
	function closeFile($handle) {
		return fclose($handle);
	}
	
	function writeFieldNames($file, $fieldNames) {
		return fwrite($file, $fieldNames."\n");
	}

	function writeFields($file, $fields) {
		$str = "";
		foreach ($fields AS $field) {
			$str .= $field.",";
		}
		$str[strlen($str) - 1] = "\n";
		return fwrite($file, $str);
	}

	function processEvent($cols, $evalCols, $files, &$bounces) {
		$ret = true;
		// Get event fields.
		// Get event fields.
		$mailingId = $cols[$evalCols["Mailing Id"]];
		$recipientId = $cols[$evalCols["Recipient Id"]];
//		$timestamp = date_format(date_create($cols[$evalCols["Event Timestamp"]]), 'Y-m-d H:i:s');
		$timestamp = date('Y-m-d H:i:s', strtotime($cols[$evalCols["Event Timestamp"]]));
		$email = $cols[$evalCols["Email"]];
		$eventType = $cols[$evalCols["Event Type"]];
		$url = str_replace('"','',$cols[$evalCols["URL"]]);
		$fields = array();
		switch ($eventType) {
			case "Clickstream":
			case "Conversion":
			case "Attachment":
			case "Media":
			case "SMS Error":
			case "SMS Reject":
			case "SMS Opt Out":			
			case "Reply Mail Block":
			case "Reply Change Address":
			case "Reply Other":
			case "Reply Change Address":
			case "Reply Abuse":
			case "Forward":
			case "Opt In":
			case "Opt Out":
			case "Suppressed":
				break;
			case "Soft Bounce":
			case "Hard Bounce":
				$fields[] = $mailingId;
				$fields[] = $recipientId;
				$fields[] = $timestamp;
				$fields[] = $email;
				$numFields = 14;
				$bounces[] = $fields;
				$fields = null;
				break;
			case "Click Through":
				$fields[] = $mailingId;
				$fields[] = $timestamp;
				$fields[] = $recipientId;
				$fields[] = $email;
				$fields[] = "";
				$fields[] = "";
				$fields[] = "";
				$fields[] = "";
				$fields[] = "";
				$fields[] = "";
				$fields[] = "";
				$fields[] = "";
				$fields[] = $url;
				$fields[] = "";
				$fields[] = "";
				$numFields = 15;
				break;
			case "Open":
				$fields[] = $mailingId;
				$fields[] = $recipientId;
				$fields[] = $email;
				$fields[] = "";
				$fields[] = $timestamp;
				$numFields = 13;
				break;
			case "Sent": // Delivered
				$fields[] = $email;
				$fields[] = $recipientId;
				$fields[] = $timestamp;
				$fields[] = $mailingId;
				$fields[] = "";
				$fields[] = "";
				$fields[] = "";
				$fields[] = "J";
				$numFields = 14;
				break;
			default:
				//echo "\nAlert:  Unknown event type ($eventType).";
				break;
		}		
		$numCurFields = count($fields);
		if ($numCurFields) {
			for ($x = 0; $x < $numFields - $numCurFields; $x++) {
				$fields[] = "";
			}
			$ok = writeFields($files[$eventType], $fields);
			if (!$ok) {
				echo "\nError:  Unable to write a record ($eventType).";
				exit(1);
			}
		}
		return $ret;
	}
	
	function getMailingIds($xml) {
		$mailingIds = array();
		$mailings = $xml->Body->RESULT->Mailing;
		foreach ($mailings AS $mailing) {
			$mailingIds[] = $mailing->MailingId;
		}
		return $mailingIds;
	}

?>