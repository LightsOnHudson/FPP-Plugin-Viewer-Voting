#!/usr/bin/php
<?php
error_reporting(0);
//added Dec 3 2015
ob_implicit_flush();


//include_once '/opt/fpp/www/config.php';
include_once '/opt/fpp/www/common.php';

$pluginName = "ViewerVoting";

$pluginUpdateFile = $settings['pluginDirectory']."/".$pluginName."/"."pluginUpdate.inc";

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

?>