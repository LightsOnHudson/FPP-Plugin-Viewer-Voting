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
	$PLAY_IN_LAST_COUNT= $pluginSettings['PLAY_IN_LAST_COUNT'];
	
	$PLAYED_SEQUENCE_ARRAY = array();
	
	if((int)$PLAYLIST_COUNT <= 0) {
		$PLAYLIST_COUNT = 0;
	}
	//initize to 3
	if((int)$PLAY_IN_LAST_COUNT <= 0) {
		//disabled!!!
		$PLAY_IN_LAST_COUNT = 0;
	}
	//the last playlist that was voted on =
	$PLAYLIST_NAME = urldecode($pluginSettings['PLAYLIST_NAME']);
	$LAST_VOTED_PLAYLISTS = urldecode($pluginSettings['LAST_VOTED_PLAYLISTS']);
	
	//load it into the array!
	//if there is no comma just push the value into the array!
	$pos = strpos($LAST_VOTED_PLAYLISTS, ",");
	if ($pos === false) {
		array_push($PLAYED_SEQUENCE_ARRAY, $LAST_VOTED_PLAYLISTS);
	} else {
		$PLAYED_SEQUENCE_ARRAY = explode(",", $LAST_VOTED_PLAYLISTS);
	}
	
	if($DEBUG) {
		logEntry("API token: ".$API_TOKEN);
		logEntry("SERVER IP :".$SERVER_IP);
		logEntry("PLAYLIST IF THRESHOLD HIT: ".$PLAYLIST_NAME);
		logEntry("LAST VOTED PLAYLIST: ".$LAST_VOTED_PLAYLISTS);
		logEntry("LAST VOTED VOTE COUNT: ".$VOTE_COUNT);
	}
	
	if($DEBUG) {
		//show the array in logentry
		foreach ($PLAYED_SEQUENCE_ARRAY as $pl) {
			logEntry("Playlist in last voted playlists: ".$pl);
		}
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
//	logEntry("Stopping fppd to have the schedule take effect!!!");
}
//shell_exec($CMD_FPPD_STOP);

//start the ffpd
$CMD_FPPD_START = "/usr/bin/sudo /opt/fpp/scripts/fppd_start";
if($DEBUG) {
//	logEntry("Starting the fppd to have the schedule take effect!!!");
	
}
//shell_exec($CMD_FPPD_START);

//now that the fppd daemon is restart, start the new playlist! running. maybe we do not have to kill the daemon??..


//$playlist= "VOTE_TEST";

//check to see if the last playlist name is the current one 
//if so, then use the PLAYLIST 

logEntry("VOTE COUNT: ".$VOTE_COUNT);
logEntry("Last playlist count: ".$PLAYLIST_COUNT);

//if($DEBUG) {
//	logEntry("Pushing: ".$SEQUENCE." to end of array and then writing it out");
//}

///array_push($PLAYED_SEQUENCE_ARRAY, $SEQUENCE);

if($PLAY_IN_LAST_COUNT == 0) {
	if($DEBUG) {
		logEntry("Play last in count is Zero");
	}
	//it is disabled. just allow this to run!
	array_push($PLAYED_SEQUENCE_ARRAY, $SEQUENCE);
	//reset the playlist count because we got a NEW vote
	$LAST_VOTED_PLAYLISTS = implode(",", $PLAYED_SEQUENCE_ARRAY);
	WriteSettingToFile("LAST_VOTED_PLAYLISTS",urlencode($LAST_VOTED_PLAYLISTS),$pluginName);
	//reset the count to 1
	//$PLAYLIST_COUNT++;
	WriteSettingToFile("PLAYLIST_COUNT",0,$pluginName);
	
} else {

	if($DEBUG) {
		logentry("Played sequence array count: ".count($PLAYED_SEQUENCE_ARRAY));
	}
	//need to reset the array! while the count is >
	$PLAYED_SEQUENCE_ARRAY_COUNT = count($PLAYED_SEQUENCE_ARRAY);
	
	while($PLAYED_SEQUENCE_ARRAY_COUNT > $PLAY_IN_LAST_COUNT) {
			if($DEBUG) {
				logEntry("Current cound of play sequence array: ".count($PLAYED_SEQUENCE_ARRAY));
				logEntry("Removing an entry off the sequence played sequence array to get it below the coung: ".$PLAY_IN_LAST_COUNT);
			}
			array_shift($PLAYED_SEQUENCE_ARRAY);
			$PLAYED_SEQUENCE_ARRAY = array_values($PLAYED_SEQUENCE_ARRAY);
			$PLAYED_SEQUENCE_ARRAY_COUNT = count($PLAYED_SEQUENCE_ARRAY);
		
		} 
	
		if($DEBUG){
			logEntry("Array reset to : ".$PLAY_IN_LAST_COUNT);
			logEntry("Count of array is ".count($PLAYED_SEQUENCE_ARRAY));
		}
			
		//$LAST_VOTED_PLAYLISTS = implode(",", $PLAYED_SEQUENCE_ARRAY);
		//WriteSettingToFile("LAST_VOTED_PLAYLISTS",urlencode($LAST_VOTED_PLAYLISTS),$pluginName);
		if($DEBUG)
			logEntry("Shwoing new array after trimming");
			
		foreach ($PLAYED_SEQUENCE_ARRAY as $pl) {
			if($DEBUG) {
				logEntry("Playlist: ".$pl);
			}
		}
		//$LAST_VOTED_PLAYLISTS = implode(",", $PLAYED_SEQUENCE_ARRAY);
		//WriteSettingToFile("LAST_VOTED_PLAYLISTS",urlencode($LAST_VOTED_PLAYLISTS),$pluginName);
	//reset the array to the highest $PLAY_IN_LAST_COUNT
	
		//check to see if the sequence has played in the play lin last count
		//since we reset the array, we should be able to tell if it has played just by doing if in array
		if(in_array($SEQUENCE, $PLAYED_SEQUENCE_ARRAY) &&  $PLAYLIST_COUNT < $VOTE_COUNT) {
			
			//it is in the array however it has not allowed to play the number of times.
			if($DEBUG) {
				logEntry("Sequence: ".$SEQUENCE." is in the last played, however it has not played enough allowed voted count: ".$VOTE_COUNT." repeat times");
			}
			$PLAYLIST_COUNT++;
			//WriteSettingToFile("PLAYLIST_COUNT",$PLAYLIST_COUNT,$pluginName);
			
			
				
			
		} elseif(in_array($SEQUENCE, $PLAYED_SEQUENCE_ARRAY) &&  $PLAYLIST_COUNT >=  $VOTE_COUNT) {
			if($DEBUG)
				logEntry("Sequence: ".$SEQUENCE . " has reached vote count: ".$VOTE_COUNT.", replacing with operator playlist: ".$PLAYLIST_NAME);
			
		
			$SEQUENCE = $PLAYLIST_NAME;
			
			$PLAYLIST_COUNT = 0;
				
		} elseif(!in_array($SEQUENCE, $PLAYED_SEQUENCE_ARRAY) &&  $PLAYLIST_COUNT < $VOTE_COUNT) {
			
			//it is NOT the array however it has not allowed to play the number of times.
			if($DEBUG) {
				logEntry("Sequence: ".$SEQUENCE." is NOT the last played");
			}
			$PLAYLIST_COUNT++;
			//WriteSettingToFile("PLAYLIST_COUNT",$PLAYLIST_COUNT,$pluginName);
			
				
		}
}
logEntry("Loading playlist/sequence: ".$SEQUENCE);

$PLAY_RESULT = playNewSequence($SEQUENCE);
//push what we played to the end!!!
array_push($PLAYED_SEQUENCE_ARRAY, $SEQUENCE);
$LAST_VOTED_PLAYLISTS = implode(",", $PLAYED_SEQUENCE_ARRAY);
WriteSettingToFile("LAST_VOTED_PLAYLISTS",urlencode($LAST_VOTED_PLAYLISTS),$pluginName);
WriteSettingToFile("PLAYLIST_COUNT",$PLAYLIST_COUNT,$pluginName);



?>
