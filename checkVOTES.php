#!/usr/bin/php
<?php
error_reporting(0);
ob_flush();flush();

$DEBUG = false;
$skipJSsettings = 1;
//FPP BIN DIR
require_once('/opt/fpp/www/common.php');
$skipJSsettings = 1;
//TODO: need to get this from the fpp settings!
$FPP_BIN = $settings['fppBinDir']."/fpp";

$pluginName = "ViewerVoting";

$pluginConfigFile = $settings['configDirectory'] . "/plugin." .$pluginName;

include_once 'functions.inc.php';
include_once 'commonFunctions.inc.php';

$myPid = getmypid();
$logFile = $settings['logDirectory']."/".$pluginName.".log";

logEntry("PluginConfig File: ".$pluginConfigFile);

if (file_exists($pluginConfigFile))
	$pluginSettings = parse_ini_file($pluginConfigFile);
	
	$API_TOKEN= $pluginSettings['API_TOKEN'];
	$SERVER_IP= $pluginSettings['SERVER_IP'];
	$DEBUG = $pluginSettings['DEBUG'];
	$VOTE_COUNT = $pluginSettings['VOTE_COUNT'];
	$PLAYLIST_COUNT = $pluginSettings['PLAYLIST_COUNT'];
	
	if((int)$PLAYLIST_COUNT <= 0) {
		$PLAYLIST_COUNT = 0;
	}
	//the last playlist that was voted on =
	$PLAYLIST_NAME = urldecode($pluginSettings['PLAYLIST_NAME']);
	$LAST_VOTED_PLAYLIST = urldecode($pluginSettings['LAST_VOTED_PLAYLIST']);
	
	if($DEBUG) {
		logEntry("API token: ".$API_TOKEN);
		logEntry("SERVER IP :".$SERVER_IP);
		logEntry("PLAYLIST IF THRESHOLD HIT: ".$PLAYLIST_NAME);
		logEntry("LAST VOTED PLAYLIST: ".$LAST_VOTED_PLAYLIST);
		logEntry("LAST VOTED VOTE COUNT: ".$VOTE_COUNT);
	}

// = "357FED1F-60C6-C53A-38A4-B5EED9A08B33";
$VOTE_DATA = checkForVotes($SERVER_IP, $API_TOKEN);


logEntry("Back from getting server vote data");

foreach($VOTE_DATA[0] as $key => $value) {
        logEntry("KEY: ".$key. " = ".$value);
        }
//site is enabled - continue checking to get vote data (if it exists)
$SEQUENCE = $VOTE_DATA[0]['FSEQ'];
$FSEQ = $VOTE_DATA[0]['FSEQ'];
$VOTES = $VOTE_DATA[0]['VOTES'];
$LAST_READ = $VOTE_DATA[0]['LAST_VOTE_TIMESTAMP'];
$SITE_ENABLED = $VOTE_DATA[0]['SITE_ENABLED'];

//if the sequence is blank(fseq) thent he server may not have any votes for that.
//replace with the Playlist
if($VOTES == 0 || $SEQUENCE == "") {
	$SEQUENCE = $PLAYLIST_NAME;
	
	logEntry("Site did not have any votes or the FSEQ was blank: replacing with the playlist name: ".$PLAYLIST_NAME);
	$PLAY_RESULT = playNewSequence($SEQUENCE);
	//exiting here
	exit(0);
}

if($DEBUG) {
	logEntry("Sequence/Playlist: ".$SEQUENCE);
	
}

//replace the playlist in the schedule

//$UPDATE_PLAYLIST_IN_SCHEDULE = updatePlaylistInSchedule($SEQUENCE);


//the new playlist has been loaded into the schedule!!!
//now tell the FPP binary to reload the schedule
$CMD_RELOAD_SCHEDULE = $FPP_BIN." -R";
if($DEBUG) {
	logEntry("Reloading schedule cmd: ".$CMD_RELOAD_SCHEDULE);
	
}
//shell_exec($CMD_RELOAD_SCHEDULE);

//STOP the fppd daemon
//then restart it
$CMD_FPPD_STOP = "/usr/bin/sudo /opt/fpp/scripts/fppd_stop";
if($DEBUG) {
	logEntry("Stopping fppd to have the schedule take effect!!!");
}
//shell_exec($CMD_FPPD_STOP);

//start the ffpd
$CMD_FPPD_START = "/usr/bin/sudo /opt/fpp/scripts/fppd_start";
if($DEBUG) {
	logEntry("Starting the fppd to have the schedule take effect!!!");
	
}
//shell_exec($CMD_FPPD_START);

//now that the fppd daemon is restart, start the new playlist! running. maybe we do not have to kill the daemon??..


//$playlist= "VOTE_TEST";

//check to see if the last playlist name is the current one 
//if so, then use the PLAYLIST 

logEntry("VOTE COUNT: ".$VOTE_COUNT);
logEntry("Last playlist count: ".$PLAYLIST_COUNT);

if($LAST_VOTED_PLAYLIST == $SEQUENCE &&  $PLAYLIST_COUNT < $VOTE_COUNT) {
	
	$PLAYLIST_COUNT++;
	WriteSettingToFile("PLAYLIST_COUNT",$PLAYLIST_COUNT,$pluginName);
	
} elseif($LAST_VOTED_PLAYLIST == $SEQUENCE &&  $PLAYLIST_COUNT >=  $VOTE_COUNT) {
	logEntry("Sequence: ".$SEQUENCE . " has reached vote count: ".$VOTE_COUNT.", replacing with operator playlist: ".$PLAYLIST_NAME);
	
	//go ahead and allow this and write it as the last voted
	//but DO NOT write the playlist they want to use here, because otherwise it would not get there on the next catch
	//write the last one to the config file
	WriteSettingToFile("LAST_VOTED_PLAYLIST",urlencode($SEQUENCE),$pluginName);
	//reset the count to 1
	WriteSettingToFile("PLAYLIST_COUNT",0,$pluginName);
	$SEQUENCE = $PLAYLIST_NAME;
} else {
	//reset the playlist count because we got a NEW vote
	WriteSettingToFile("LAST_VOTED_PLAYLIST",urlencode($SEQUENCE),$pluginName);
	//reset the count to 1
	WriteSettingToFile("PLAYLIST_COUNT",0,$pluginName);
}

logEntry("Loading playlist/sequence: ".$SEQUENCE);

$PLAY_RESULT = playNewSequence($SEQUENCE);





?>
