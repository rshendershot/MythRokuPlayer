<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

if(isset($_GET['Upcoming'])) {
	$select = rawurldecode($_GET['Upcoming']);	

	//build feed for this specific group	
	error_log("selecting Upcoming: $select", 0);

	$jobqueueSvc = "$localSvc/jobqueue_service.php";	
	$recordingSvc = "$MythDvrSvc/GetRecordedList?Descending=true";
	$upcomingSvc = "$MythDvrSvc/GetUpcomingList";
	if ($select == 'Top')
		$upcomingSvc .= "?Count=$UpcomingListLimit";	
	
	$upcomingList = new SimpleXMLElement($upcomingSvc, NULL, TRUE);
	$recordingList = new SimpleXMLElement($recordingSvc, NULL, TRUE);
	
	$items = array();
	foreach($recordingList->xpath('//Program') as $value) {
		$statusEl = $value->xpath('.//Status');  
		$programFlagsEl = $value->xpath('.//ProgramFlags');
		$chanidEl = $value->xpath('.//ChanId');
		$startTimeEl = $value->xpath('.//StartTs');
		
		$flags = (int)$programFlagsEl[0];
		$status = (int)$statusEl[0];  
		$chanid = (string)$chanidEl[0];
		$startTS = (string)$startTimeEl[0];
		
		$timestamp = convert_datetime($startTS);
		if(useUTC())
			$starttime = gmdate('Y-m-d H:i:s', $timestamp );
		else
			$starttime = date('Y-m-d H:i:s', $timestamp );	
		
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
	
	if(count($items)){
		$feed = new feed(
			array(
				'resultLength'=>new resultLength(array('content'=>count($items)))
				, 'endIndex'=>new endIndex(array('content'=>count($items)))
				, 'item'=>$items
			)
		);
	}else{
		$feed = new feed(
			array(
				'resultLength'=>new resultLength(array('content'=>count($items)))
				, 'endIndex'=>new endIndex(array('content'=>count($items)))
				, 'item'=>array(new item(new ProgramTpl(new SimpleXMLElement(ProgramTpl::rsNONE))))
			)		
		);		
	}	
	
	print $feed;
	
}else{
	// build category static groupings: All $limit, All	

	$upcoming = new category(
		array(XmlEmitter::ATR.'title'=>'Upcoming'
			, XmlEmitter::ATR.'description'=>'Scheduled Recordings'
			, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/event_viewer.png"
			, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/event_viewer.png"
			, 'categoryLeaf'=>array()
		)
	);
	
	$menu = array();
	$results = array('Top','All');	
	
	foreach ( $results as $value ) {
		$parms = array('Upcoming'=>rawurlencode($value));
    	$menu[] = new categoryLeaf( 
    		array(XmlEmitter::ATR.'title'=>($value == 'Top' ? $value." $UpcomingListLimit" : $value)
    		, XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_upcoming_xml.php?".http_build_query($parms))  
    	);   
	}
	
	$upcoming->categoryLeaf = $menu;

	return $upcoming;	
	
}	

?>
