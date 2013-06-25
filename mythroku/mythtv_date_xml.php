<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

if(isset($_GET['Date'])) {
	$select = rawurldecode($_GET['Date']);

	//build feed for this specific date set
	error_log("selecting Date: $select", 0);
	
	//build date range
	$interval = '-1 HOUR';
	switch ( $select ) {
		case 'Today': $interval = '-1 DAY';break;
		case 'Week': $interval = '-7 DAY';break;
		case 'Month': $interval = '-1 MONTH';break;
		case 'Year': $interval = '-1 YEAR';break;
		case 'All': $interval = '-100 YEAR';break;
		default:
			break;
	}

	if(useUTC())
		$intervalQry = "starttime between adddate(utc_timestamp(), interval $interval) and utc_timestamp() ";
	else
		$intervalQry = "starttime between adddate(now(), interval $interval) and now() ";
	
	$conditions = array('conditions' => array("basename like ? AND $intervalQry", '%.mp4'));
	$record = Recorded::all( $conditions );
	error_log("COUNT of RECORDED: ".count($record));
	
	$vselect = array('select' => '*, case releasedate when (releasedate is null) then insertdate else releasedate end as starttime');
	$conditions = array('conditions' => array("filename like ? AND host > ? HAVING $intervalQry", '%.m%4%', ''));
	$video = VideoMetadata::all( array_merge($vselect, $conditions) );
	error_log("COUNT of VIDEOMETADATA: ".count($video));
	
	$items = array();
	$shows = array_values(array_merge($record, $video));
	usort($shows, 'shows_date_compare');
	foreach($shows as $item => $show ){
		$items[] = new item($show);
	}	
	
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
	//	build category static groupings: Week, Month, Year, All
		
	$date = new category(
		array(XmlEmitter::ATR.'title'=>'Date'
			, XmlEmitter::ATR.'description'=>'Select a Date'
			, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/Date-Time.png"
			, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/Date-Time.png"
			, 'categoryLeaf'=>array()
		)
	);

	$menu = array();
	$results = array('Today','Week','Month','Year','All');	

	foreach ( $results as $value ) {
		$parms = array('Date'=>rawurlencode($value));
    	$menu[] = new categoryLeaf( 
    		array(XmlEmitter::ATR.'title'=>$value
    		, XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_date_xml.php?".http_build_query($parms))  
    	);   
	}

	$date->categoryLeaf = $menu;

	return $date;
	
}

function shows_date_compare($a, $b){
	if(  (is_a($a,'Recorded') || is_a($a,'VideoMetadata'))  &&  (is_a($b,'Recorded') || is_a($b,'VideoMetadata'))  ){
		$aTime = $a->starttime;
		$bTime = $b->starttime;
		
		if($aTime === $bTime){
			$aTitle = ltrim(preg_replace('/^[Tt]he /', '', $a->title));
			$bTitle = ltrim(preg_replace('/^[Tt]he /', '', $b->title));
			
			return $aTitle > $bTitle ? -1 : 1;			
		}
		
		return $aTime > $bTime ? -1 : 1;		
	}else{
		return 0;
	}	
}
?>
