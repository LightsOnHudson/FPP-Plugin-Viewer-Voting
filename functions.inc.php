<?php

//send sequences to server with API token
function sendSequencesToServer($API_TOKEN, $SEQUENCES) {
	
	$url = "your url";
	$content = json_encode("your data to be sent");
	
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER,
			array("Content-type: application/json"));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
	
	$json_response = curl_exec($curl);
	
	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	
	if ( $status != 201 ) {
		die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
	}
	
	
	curl_close($curl);
	
	$response = json_decode($json_response, true);
}

//update the playlist in the schedule with the passed filename!
function updatePlaylistInSchedule($SEQUENCE) {
	
	global $DEBUG, $settings;
	
	//the schedule file is in the mediaDir
	
	$scheduleFile = $settings['mediaDirectory']."/schedule";
	
	if($DEBUG) {
		logEntry("Schedule file: ".$scheduleFile);
	}
	
	//a sample schedule file looks like
	//fpp@FPPVM1:~/media$ more schedule
	//1,PlaylistTWO,7,00,30,00,23,30,00,1,2017-07-31,2099-12-31,
	//there are 12 values, the 2nd value is the one we want to replace!
	
	$scheduleData = file_get_contents($scheduleFile);
	if($DEBUG)
//			print_r($scheduleData);
	
	$scheduleDataArray = explode(",", $scheduleData);
	
	//remove the file
	unlink($scheduleFile);
	
	sleep(1);
	$scheduleDataArray[1] = $SEQUENCE;
	if($DEBUG) {
		logEntry("Writing new playlist: ".$SEQUENCE." into schedule array");
//		print_r($scheduleDataArray);
	}
	$NEWScheduleData = implode(",",$scheduleDataArray);
	
	file_put_contents($scheduleFile,$NEWScheduleData);
	
	
}

function curl_to_host($method, $url, $headers, $data, &$resp_headers)
	{
	$ch=curl_init($url);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $GLOBALS['POST_TO_HOST.LINE_TIMEOUT']?$GLOBALS['POST_TO_HOST.LINE_TIMEOUT']:5);
	curl_setopt($ch, CURLOPT_TIMEOUT, $GLOBALS['POST_TO_HOST.TOTAL_TIMEOUT']?$GLOBALS['POST_TO_HOST.TOTAL_TIMEOUT']:20);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	
	if ($method=='POST')
	{curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	}
	foreach ($headers as $k=>$v)
	{$headers[$k]=str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $k)))).': '.$v;
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$rtn=curl_exec($ch);
	curl_close($ch);
	
	$rtn=explode("\r\n\r\nHTTP/", $rtn, 2);    //to deal with "HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 200 OK...\r\n\r\n..." header
	$rtn=(count($rtn)>1 ? 'HTTP/' : '').array_pop($rtn);
	list($str_resp_headers, $rtn)=explode("\r\n\r\n", $rtn, 2);
	
	$str_resp_headers=explode("\r\n", $str_resp_headers);
	array_shift($str_resp_headers);    //get rid of "HTTP/1.1 200 OK"
	$resp_headers=array();
	foreach ($str_resp_headers as $k=>$v)
	{$v=explode(': ', $v, 2);
	$resp_headers[$v[0]]=$v[1];
	}
	
	return $rtn;
}

function checkForVotes($SERVER_IP, $API_TOKEN) {
	
	
	global $DEBUG;
	
	$CHECK_VOTES_CMD = "http://". $SERVER_IP . "/FPPViewerVotingServer/server.php?API_TOKEN=".$API_TOKEN;
	
	$json = file_get_contents($CHECK_VOTES_CMD);
	
	$data = json_decode($json, TRUE);
	
	//print_r($data);
	if($DEBUG) {
		logEntry("JSON data back from server: ".$SERVER_IP);
		
		foreach($data[0] as $key => $value) {
			logEntry("KEY: ".$key. " = ".$value);
		}
		
	}
	
	
	//there is  asingle array!
	//check against the server
	
	$CLIENT_TOKEN = $data[0]['CLIENT_TOKEN'];
	$SITE_ENABLED = $data[0]['SITE_ENABLED'];
	
	if($CLIENT_TOKEN == $API_TOKEN) {
		logEntry("WE HAVE A MATCHING TOKEN YAY");
	}
	logEntry("Client Token: ".$CLIENT_TOKEN);
	logEntry("SITE_ENABLED: ".$SITE_ENABLED);
	
	if(!$SITE_ENABLED) {
		if($DEBUG) {
			logEntry("SITE IS NOT ENABLED ON THE SERVER");
			exit(0);
		}
	} elseif($SITE_ENABLED) {
		if($DEBUG) {
			logEntry("SITE IS ENABLED");
			
		}
		
	}
	
	return $data;
	
}

//create unique GUID:
function getGUID(){
	if (function_exists('com_create_guid')){
		return com_create_guid();
	}else{
		mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45);// "-"
		$uuid = chr(123)// "{"
		.substr($charid, 0, 8).$hyphen
		.substr($charid, 8, 4).$hyphen
		.substr($charid,12, 4).$hyphen
		.substr($charid,16, 4).$hyphen
		.substr($charid,20,12)
		.chr(125);// "}"
		return $uuid;
	}
}

function tryGetHost($ip)
{
	$string = '';
	exec("dig +short -x $ip 2>&1", $output, $retval);
	if ($retval != 0)
	{
		// there was an error performing the command
	}
	else
	{
		$x=0;
		while ($x < (sizeof($output)))
		{
			$string.= $output[$x];
			$x++;
		}
	}
	
	if (empty($string))
		$string = $ip;
		else //remove the trailing dot
			$string = substr($string, 0, -1);
			
			return $string;
}




function sendTCP($IP, $PORT, $cmd) {
	
	
/* Create a TCP/IP socket. */
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    logEntry("socket_create() failed: reason: " . socket_strerror(socket_last_error()));
} else {
   logEntry("TCPIP Socket Created");
}


$result = socket_connect($socket, $IP, $PORT);
if ($result === false) {
    logEntry("socket_connect() failed. Reason: ($result) " . socket_strerror(socket_last_error($socket)));
} else {
    logEntry("TCPIP CONNECTED");
}


socket_write($socket, $cmd, strlen($cmd));


logEntry("Reading response");
while ($out = socket_read($socket, 2048)) {
    logEntry($out);
}

logEntry("Closing socket...");
socket_close($socket);
logEntry("OK");

}
function hex_dump($data, $newline="\n")
{
  static $from = '';
  static $to = '';

  static $width = 16; # number of bytes per line

  static $pad = '.'; # padding for non-visible characters

  if ($from==='')
  {
    for ($i=0; $i<=0xFF; $i++)
    {
      $from .= chr($i);
      $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
    }
  }

  $hex = str_split(bin2hex($data), $width*2);
  $chars = str_split(strtr($data, $from, $to), $width);

$HEX_OUT ="";
  $offset = 0;
  foreach ($hex as $i => $line)
  {
    $HEX_OUT.= sprintf('%6X',$offset).' : '.implode(' ', str_split($line,2)) . ' [' . $chars[$i] . ']';
    $offset += $width;
  }
return $HEX_OUT;
}

function decode_code($code)
{
    return preg_replace_callback('@\\\(x)?([0-9a-f]{2,3})@',
        function ($m) {
            if ($m[1]) {
                $hex = substr($m[2], 0, 2);
                $unhex = chr(hexdec($hex));
		echo "UNHEX: ".$unhex;
                if (strlen($m[2]) > 2) {
                    $unhex .= substr($m[2], 2);
                }
                return $unhex;
            } else {
                return chr(octdec($m[2]));
            }
        }, $code);
}


function logEntry($data) {

	global $logFile,$myPid,$callBackPid;
	
	if($callBackPid != "") {
		$data = $_SERVER['PHP_SELF']." : [".$callBackPid.":".$myPid."] ".$data;
	} else { 
	
		$data = $_SERVER['PHP_SELF']." : [".$myPid."] ".$data;
	}
	$logWrite= fopen($logFile, "a") or die("Unable to open file!");
	fwrite($logWrite, date('Y-m-d h:i:s A',time()).": ".$data."\n");
	fclose($logWrite);
}


function escapeshellarg_special($file) {
	return "'" . str_replace("'", "'\"'\"'", $file) . "'";
}


function processCallback($argv) {

	global $DEBUG,$pluginName;
	
	
	if($DEBUG)
		print_r($argv);
	//argv0 = program
	
	//argv2 should equal our registration // need to process all the rgistrations we may have, array??
	//argv3 should be --data
	//argv4 should be json data
	
	$registrationType = $argv[2];
	$data =  $argv[4];
	
	logEntry("PROCESSING CALLBACK");
	$clearMessage=FALSE;
	
	switch ($registrationType)
	{
		case "media":
			if($argv[3] == "--data")
			{
				$data=trim($data);
				logEntry("DATA: ".$data);
				$obj = json_decode($data);
	
				$type = $obj->{'type'};
	
				switch ($type) {
						
					case "sequence":
	
						//$sequenceName = ;
						processSequenceName($obj->{'Sequence'});
							
						break;
					case "media":
							
						logEntry("We do not understand type media at this time");
							
						exit(0);
	
						break;
	
					default:
						logEntry("We do not understand: type: ".$obj->{'type'}. " at this time");
						exit(0);
						break;
	
				}
	
	
			}
	
			break;
			exit(0);
				
		default:
			exit(0);
	
	}
	


}
?>
