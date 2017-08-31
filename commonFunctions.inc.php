<?php

//Send in new playlist to play based on votes
//Change the configured/running schedule file with the new playlist
//need a DEFAULT playlist in the event that there is no votes
//that will run in REPEAT mode
//then gracefully shutdown the playlist
//reload the FPPD to read the current schedule
//restart fppd.. or reload. whichever.
function loadNewPlaylist($playlist) {
	
	global $DEBUG,  $settings;
	
	$LOAD_RESULT = false;
	
	logEntry("Loading a new Playlist: ".$playlist);
	
	logEntry("FPP Bin dir: ".$settings['fppDir']);
	
	//FPP FPPD location need to fix based on the platform!
	$FPPD_LOCATION = $settings['fppDir']."/fppd";
	
	
	if(file_exists($FPPD_LOCATION)) {
		
	}else {
	
		//it is in the SRC location
		$FPPD_LOCATION = $settings['fppDir']."/src/fppd";
	}
	
	
	return $LOAD_RESULT;
	
}

function playNewSequence($sequence) {
	
	global $DEBUG,  $settings;
	
	$PLAY_RESULT = false;
	if($DEBUG) {
		logEntry("Loading a new Sequence: ".$sequence);
		
		logEntry("FPP Bin dir: ".$settings['fppDir']);
	}
	//FPP FPPD location need to fix based on the platform!
	$FPP_LOCATION = $settings['fppDir']."/fppd";
	
	
	if(file_exists($FPP_LOCATION)) {
		
	}else {
		
		//it is in the SRC location
		$FPP_LOCATION = $settings['fppDir']."/src/fpp";
	}
	
	//set the sequence to play!
	$PLAY_CMD = $FPP_LOCATION ." -P ".$sequence;
	shell_exec($PLAY_CMD);
	
	//return $PLAY_RESULT;
	return;
}


//update the plugin from gitHub! 
//TODO: should update to just trigger the script!
function updatePluginFromGitHub($gitURL, $branch="master", $pluginName) {
	
	
	global $settings;
	logEntry ("updating plugin: ".$pluginName);
	
	logEntry("settings: ".$settings['pluginDirectory']);
	
	//create update script
	//$gitUpdateCMD = "sudo cd ".$settings['pluginDirectory']."/".$pluginName."/; sudo /usr/bin/git git pull ".$gitURL." ".$branch;

	$pluginUpdateCMD = "/opt/fpp/scripts/update_plugin ".$pluginName;

	logEntry("update command: ".$pluginUpdateCMD);


	exec($pluginUpdateCMD, $updateResult);

	//logEntry("update result: ".print_r($updateResult));

	//loop through result	
	return;// ($updateResult);
	
	
	
}
//create script to randmomize
function createScriptFile($scriptFilename,$scriptCMD) {


	global $settings,$pluginName;

	$scriptFilename = $settings['scriptDirectory']."/".$scriptFilename;

	logEntry("Creating  script: ".$scriptFilename);
	
	$ext = pathinfo($scriptFilename, PATHINFO_EXTENSION);

	
	$data = "";

	$data .="#!/bin/sh\n";

	
	$data .= "\n";
	$data .= "#Script to run $scriptCMD\n";
	$data .= "#Created by ".$pluginName."\n";
	$data .= "#\n";
	$data .= "/usr/bin/php ".$scriptCMD."\n";
	
	//logEntry($data);


	$fs = fopen($scriptFilename,"w");
	fputs($fs, $data);
	fclose($fs);

}
//return the next event file available for use

//get the next available event filename
function getNextEventFilename() {

	global $settings;
	$MAX_MAJOR_DIGITS=2;
	$MAX_MINOR_DIGITS=2;
	

	//echo "Event Directory: ".$eventDirectory."<br/> \n";

	$MAJOR=array();
	$MINOR=array();

	$MAJOR_INDEX=0;
	$MINOR_INDEX=0;

	$EVENT_FILES = directoryToArray($settings['eventDirectory'], false);
	//print_r($EVENT_FILES);

	foreach ($EVENT_FILES as $eventFile) {

		$eventFileParts = explode("_",$eventFile);

		$MAJOR[] = (int)basename($eventFileParts[0]);
		//$MAJOR = $eventFileParts[0];

		$minorTmp = explode(".fevt",$eventFileParts[1]);

		$MINOR[] = (int)$minorTmp[0];

		//echo "MAJOR: ".$MAJOR." MINOR: ".$MINOR."\n";
		//print_r($MAJOR);
		//print_r($MINOR);

	}

	$MAJOR_INDEX = max(array_values($MAJOR));
	$MINOR_INDEX = max(array_values($MINOR));

	//echo "Major max: ".$MAJOR_INDEX." MINOR MAX: ".$MINOR_INDEX."\n";



	if($MAJOR_INDEX <= 0) {
		$MAJOR_INDEX=1;
	}
	if($MINOR_INDEX <= 0) {
		$MINOR_INDEX=1;

	} else {

		$MINOR_INDEX++;
	}

	$MAJOR_INDEX = str_pad($MAJOR_INDEX, $MAX_MAJOR_DIGITS, '0', STR_PAD_LEFT);
	$MINOR_INDEX = str_pad($MINOR_INDEX, $MAX_MINOR_DIGITS, '0', STR_PAD_LEFT);
	//for now just return the next MINOR index up and keep the same Major
	$newIndex=$MAJOR_INDEX."_".$MINOR_INDEX.".fevt";
	//echo "new index: ".$newIndex."\n";
	return $newIndex;
}


function directoryToArray($directory, $recursive) {
	$array_items = array();
	if ($handle = opendir($directory)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if (is_dir($directory. "/" . $file)) {
					if($recursive) {
						$array_items = array_merge($array_items, directoryToArray($directory. "/" . $file, $recursive));
					}
					$file = $directory . "/" . $file;
					$array_items[] = preg_replace("/\/\//si", "/", $file);
				} else {
					$file = $directory . "/" . $file;
					$array_items[] = preg_replace("/\/\//si", "/", $file);
				}
			}
		}
		closedir($handle);
	}
	return $array_items;
}


//check all the event files for a string matching this and return true/false if exist
function checkEventFilesForKey($keyCheckString) {
	global $settings;
	
	logEntry("Checking event files for the key: ".$keyCheckString);
	$eventDirectory = $settings['mediaDirectory']."/events";
	$keyExist = false;
	$eventFiles = array();

	$eventFiles = directoryToArray($eventDirectory, false);
	foreach ($eventFiles as $eventFile) {

		if( strpos(file_get_contents($eventFile),$keyCheckString) !== false) {
			// do stuff
			$keyExist= true;
			break;
			// return $keyExist;
		}
	}

	return $keyExist;

}

function createViewerVotingEventFiles() {
	
	global $DEBUG, $settings, $pluginName; 
	if($DEBUG)
		logEntry("Inside: ".__FUNCTION__,1,__FILE__,__LINE__);
		
	foreach ($settings as $key => $value) {
		logEntry("Settings: ".$key. " has value: ".$value);
	}
	
	//echo "next event file name available: ".$nextEventFilename."\n";
	
	$EVENT_FILE=false;
	
	//there is not event directory in the settings!!!
	$eventDirectory = $settings['mediaDirectory']."/events";
					
					//check to see that the file doesnt already exist - do a grep and return contents
					$EVENT_CHECK = checkEventFilesForKey("CHECK-VOTES");
					if(!$EVENT_CHECK)
					{
						logEntry("There is not an event file for this . creating one");
						
						$nextEventFilename = getNextEventFilename();
						$MAJOR=substr($nextEventFilename,0,2);
						$MINOR=substr($nextEventFilename,3,2);
						$eventData  ="";
						$eventData  = "majorID=".(int)$MAJOR."\n";
						$eventData .= "minorID=".(int)$MINOR."\n";
						$eventData .= "name='CHECK-VOTES'\n";
						$eventData .= "effect=''\n";
						$eventData .= "startChannel=\n";
						$eventData .= "script='checkVotes.sh'\n";
						
						//	echo "eventData: ".$eventData."<br/>\n";
						file_put_contents($eventDirectory."/".$nextEventFilename, $eventData);
						
						$scriptCMD = $settings['pluginDirectory']."/".$pluginName."/"."checkVOTES.php";
						createScriptFile("checkVotes.sh",$scriptCMD);
					} else {
						logEntry("Event file exists for this exiiting");
					}
				
				
				//echo "$key => $val\n";
			
		
	
	
	
	
}
?>
