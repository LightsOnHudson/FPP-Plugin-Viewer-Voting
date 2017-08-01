#!/usr/bin/php
<?php
error_reporting(0);
//added Dec 3 2015
ob_implicit_flush();


//include_once '/opt/fpp/www/config.php';
include_once '/opt/fpp/www/common.php';

$pluginName = "ViewerVoting";

$pluginUpdateFile = $settings['pluginDirectory']."/".$pluginName."/"."pluginUpdate.inc";

$pluginConfigFile = $settings['configDirectory'] . "/plugin." .$pluginName;

$dontateFile = $settings['pluginDirectory']."/".$pluginName."/"."donate.inc.php";

include_once 'functions.inc.php';
include_once 'commonFunctions.inc.php';

include_once 'version.inc';

$myPid = getmypid();



$gitURL = "https://github.com/LightsOnHudson/FPP-Plugin-Viewer-Voting.git";

//arg0 is  the program
//arg1 is the first argument in the registration this will be --list
//$DEBUG=true;
$logFile = $settings['logDirectory']."/".$pluginName.".log";

$DEBUG = ReadSettingFromFile("DEBUG",$pluginName);
logEntry("Reading setting from file debug: ".$DEBUG);


logEntry("PluginConfig File: ".$pluginConfigFile);

if (file_exists($pluginConfigFile))
	$pluginSettings = parse_ini_file($pluginConfigFile);
	
	$API_TOKEN= $pluginSettings['API_TOKEN'];
	$SERVER_IP= $pluginSettings['SERVER_IP'];
	
	if($DEBUG) {
		logEntry("API token: ".$API_TOKEN);
		logEntry("SERVER IP :".$SERVER_IP);
	}

// = "357FED1F-60C6-C53A-38A4-B5EED9A08B33";
checkForVotes($SERVER_IP, $API_TOKEN);


$LOAD_RESULT = loadNewPlaylist($playlist);

logEntry("Load playlist result: ".$LOAD_RESULT);

?>