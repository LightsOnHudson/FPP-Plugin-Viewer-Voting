#!/usr/bin/php
<?php
error_reporting(0);
//added Dec 3 2015
ob_implicit_flush();


//include_once '/opt/fpp/www/config.php';
include_once '/opt/fpp/www/common.php';

//FPP BIN DIR
//TODO: need to get this from the fpp settings!
$FPP_BIN = $settings['fppBinDir']."/fpp";

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
$VOTE_DATA = checkForVotes($SERVER_IP, $API_TOKEN);

if($DEBUG) {
	print_r($VOTE_DATA);
}
//site is enabled - continue checking to get vote data (if it exists)
$SEQUENCE = $VOTE_DATA[0]['FSEQ'];
$FSEQ = $VOTE_DATA[0]['FSEQ'];
$VOTES = $VOTE_DATA[0]['VOTES'];
$LAST_READ = $VOTE_DATA[0]['LAST_VOTE_TIMESTAMP'];
$SITE_ENABLED = $VOTE_DATA[0]['SITE_ENABLED'];

if($DEBUG) {
	logEntry("Sequence/Playlist: ".$SEQUENCE);
	
}

//replace the playlist in the schedule

$UPDATE_PLAYLIST_IN_SCHEDULE = updatePlaylistInSchedule($SEQUENCE);


//the new playlist has been loaded into the schedule!!!
//now tell the FPP binary to reload the schedule
$CMD_RELOAD_SCHEDULE = $FPP_BIN." -R";
if($DEBUG) {
	logEntry("Reloading schedule cmd: ".$CMD_RELOAD_SCHEDULE);
	
}
shell_exec($CMD_RELOAD_SCHEDULE);

//STOP the fppd daemon
//then restart it
$CMD_FPPD_STOP = "/usr/bin/sudo /opt/fpp/scripts/fppd_stop";
if($DEBUG) {
	logEntry("Stopping fppd to have the schedule take effect!!!");
}
shell_exec($CMD_FPPD_STOP);

//start the ffpd
$CMD_FPPD_START = "/usr/bin/sudo /opt/fpp/scripts/fppd_start";
if($DEBUG) {
	logEntry("Starting the fppd to have the schedule take effect!!!");
	
}
shell_exec($CMD_FPPD_START);

$playlist= "VOTE_TEST";

$LOAD_RESULT = loadNewPlaylist($playlist);

logEntry("Load playlist result: ".$LOAD_RESULT);

?>