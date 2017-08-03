<?php


$skipJSsettings = 1;
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

//$DEBUG = false;

$sequenceExtension = ".fseq";

if($DEBUG) {
	logEntry("plugin update file: ".$pluginUpdateFile);
	logEntry("donate file: ".$dontateFile);
	//logEntry("open log file: ".$logFile);
}





if(isset($_POST['updatePlugin']))
{
	$updateResult = updatePluginFromGitHub($gitURL, $branch="master", $pluginName);
	
	echo $updateResult."<br/> \n";
}

if(isset($_POST['sync_sequnces'])) {
	
	//the sync sequences was pressed
	logEntry("Syncing sequences");
	
	$SEQUENCE_DIR = $settings['sequenceDirectory'];
	logEntry("Sequence directory: ".$SEQUENCE_DIR);
	$SEQUENCE_ARRAY = directoryToArray($SEQUENCE_DIR, $recursive);
	
	
	print_r($SEQUENCE_ARRAY);
	
	continue;
	
	
} elseif(isset($_POST['sync_playlists'])) {
	
	//the sync sequences was pressed
	logEntry("Syncing playlists");
	
	$PLAYLIST_DIR = $settings['mediaDirectory']."/playlists/";
	logEntry("playlist directory: ".$PLAYLIST_DIR);
	$PLAYLIST_ARRAY = directoryToArray($PLAYLIST_DIR, $recursive);
	
	print_r($PLAYLIST_ARRAY);
	
	continue;
	
} elseif(isset($_POST['save_config'])) {

	

	
	
	//$ENABLED=$_POST["ENABLED"];

	//	echo "Writring config fie <br/> \n";


	WriteSettingToFile("API_TOKEN",urlencode($_POST["API_TOKEN"]),$pluginName);

	sleep(1);

} 


	
	
	//$ENABLED = ReadSettingFromFile("ENABLED",$pluginName);
	//$ENABLED = ReadSettingFromFile("ENABLED",$pluginName);
	
	$API_TOKEN =  ReadSettingFromFile("API_TOKEN",$pluginName);
	
	
	
	//test variables

?>

<html>
<head>
</head>

<div id="plugin" class="settings">
<fieldset>
<legend>Viewer Voting Support/Install Instructions</legend>

<p>Known Issues:
<ul>
<li>NONE</li>
</ul>


<p>Configuration:
<ul>
<li></li>
</ul>

<form method="post" action="http://<? echo $_SERVER['SERVER_ADDR'].":".$_SERVER['SERVER_PORT']?>/plugin.php?plugin=<?php echo $pluginName;?>&page=plugin_setup.php">
<br>
<p/>

<?

echo "VER: ".$VERSION;
echo "<br/> \n";

echo "ENABLE PLUGIN: ";

//if($ENABLED == "on" || $ENABLED == 1) {
//	echo "<input type=\"checkbox\" checked name=\"ENABLED\"> \n";
	PrintSettingCheckbox($pluginName, "ENABLED", $restart = 0, $reboot = 0, "ON", "OFF", $pluginName = $pluginName, $callbackName = "");
//} else {
//	echo "<input type=\"checkbox\"  name=\"ENABLED\"> \n";
//}
echo "<p/>\n";

echo "Client Token: \n";
echo "<input size=\"64\" type=\"text\" name=\"API_TOKEN\" value=\"".$API_TOKEN."\"> \n";



?>
<p/>
<input id="submit_button" name="save_config" type="submit" class="buttons" value="Save Config">

<input id="sync_sequences" name="sync_sequnces" type="submit" class="buttons" value="Sync Sequnences">

<input id="sync_playlists" name="sync_playlists" type="submit" class="buttons" value="Sync Playlists">

</form>


<p>To report a bug, please file it against the  plug-in project on Git: <? echo $gitURL;?>
<form method="post" action="http://<? echo $_SERVER['SERVER_ADDR'].":".$_SERVER['SERVER_PORT']?>/plugin.php?plugin=<?echo $pluginName;?>&page=plugin_setup.php">
<?
 if(file_exists($pluginUpdateFile))
 {
 	//echo "updating plugin included";
	include $pluginUpdateFile;
}
?>
</form>

<?
 if(file_exists($dontateFile))
 {
 	
	include $dontateFile;
} else {
	logEntry("No donate file exists");
}
?>
<p/>
</fieldset>
</div>
<br />
</html>
