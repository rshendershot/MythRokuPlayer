<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

$recordingSvc = "$MythDvrSvc/GetRecordedList?Descending=true";
$upcomingSvc = "$MythDvrSvc/GetUpcomingList";
if (isset($_GET['Count']))
	$upcomingSvc .= "?Count=$UpcomingListLimit";	
$jobqueueSvc = "$localSvc/jobqueue_service.php";

$upcomingList = new SimpleXMLElement($upcomingSvc, NULL, TRUE);
$recordingList = new SimpleXMLElement($recordingSvc, NULL, TRUE);

$items = array();
foreach($recordingList->xpath('//Program') as $value) {  
	$statusEl = $value->xpath('.//Status');  
	$programFlagsEl = $value->xpath('.//ProgramFlags');
	$chanidEl = $value->xpath('.//ChanId');
	$startTimeEl = $value->xpath('.//StartTime');
	
	$flags = (int)$programFlagsEl[0];
	$status = (int)$statusEl[0];  
	$chanid = (string)$chanidEl[0];
	$starttime = (string)$startTimeEl[0];
	
	$parms = array('chanid'=>$chanid, 'starttime'=>$starttime);
	$jobs = new SimpleXMLElement("$jobqueueSvc?".http_build_query($parms), NULL, TRUE);
	$hasJob = (bool)$jobs[0];  

	$program = new Program($value);
	$program->hasJob = $hasJob;
	$program->isRecording = ($status == -2);			
	
	if($program->hasJob || $program->isRecording) { 
		$items[] = new item($program);
	}
}
foreach($upcomingList->xpath('//Program') as $value) {
	$program = new Program($value);
	$items[] = new item($program);
}
usort($items, 'items_date_compare');

$feed = new feed(
	array(
		'resultLength'=>new resultLength(array('content'=>count($items)))
		, 'endIndex'=>new endIndex(array('content'=>count($items)))
		, 'item'=>$items
	)
);

print "\n<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>" . $feed ."\n";
?>
