<?php
require_once 'settings.php';
include 'player_feed.php';

//const _DEBUG = 'true';

$recordingSvc = "$MythDvrSvc/GetRecordedList?Count=1&Descending=true";
if (isset($_GET['Count']))
	$upcomingSvc = "$MythDvrSvc/GetUpcomingList?Count=$UpcomingListLimit";
else
	$upcomingSvc = "$MythDvrSvc/GetUpcomingList";

$upcoming = new SimpleXMLElement($upcomingSvc, NULL, TRUE);
$recording = new SimpleXMLElement($recordingSvc, NULL, TRUE);

$items = array();
foreach($recording->xpath('//Program') as $value) {
	$statusEl = $value->xpath('//Status');	
	$status = (string)$statusEl[0];
	if($status == -2){
		$program = new Program($value);
		$program->isRecording = true;
		$items[] = new item($program);		
	}  
}
foreach($upcoming->xpath('//Program') as $value) {
	//var_dump($value);
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
