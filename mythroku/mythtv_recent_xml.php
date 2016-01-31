<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

if (!function_exists('shows_date_compare')) {
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
}

if(isset($_GET['Recent'])) {
	$select = rawurldecode($_GET['Recent']);

	//build feed for this specific date set
	error_log("selecting Recent: $select", 0);
	
	//New, Recent, Older, All
	
	//build date range
	$interval = '-1 HOUR';
	switch ( $select ) {  //TODO use top in the select query
		case 'New': $interval = '-7 DAY';break;
		case 'Recent': $interval = '-15 DAY';break;
		//case 'Month': $interval = '-1 MONTH';break;
		case 'Older': $interval = '-1 YEAR';break;
		case 'All': $interval = '-100 YEAR';break;
		default:
			break;
	}

	if(useUTC())
		$intervalQry = "starttime between adddate(utc_timestamp(), interval $interval) and utc_timestamp() ";
	else
		$intervalQry = "starttime between adddate(now(), interval $interval) and now() ";
		
	$conditions = array('conditions' => array("basename like ? AND $intervalQry", '%.mp4') );
	if($select == 'New'){
		$conditions['conditions'][0].= " HAVING datediff(recorded.starttime, airdate) < 7";
	}
	$rquery = array(
		'select'=>'recorded.*
			, ifnull( 
				nullif(recorded.originalairdate,0)
				, makedate( (select airdate from recordedprogram where recordedprogram.chanid=recorded.chanid and recordedprogram.starttime=recorded.starttime),1 )
	    	) as airdate'			
	);

	$record = Recorded::all( array_merge($rquery, $conditions) );
	error_log("COUNT of RECORDED: ".count($record), 0);
	
	$vselect = array('select' => '*, case releasedate when (releasedate is null) then insertdate else releasedate end as starttime');
	$conditions = array('conditions' => array("filename like ? AND host > ? HAVING $intervalQry", '%.m%4%', ''));
	$video = VideoMetadata::all( array_merge($vselect, $conditions) );
	error_log("COUNT of VIDEOMETADATA: ".count($video), 0);
	
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
	//	build category dynamic groupings: Recent, Older, All
		
	$recent = new category(
		array(XmlEmitter::ATR.'title'=>'Date'
			, XmlEmitter::ATR.'description'=>'See most recent'
			, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/view-calendar-upcoming-days.png"
			, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/view-calendar-upcoming-days.png"
			, 'categoryLeaf'=>array()
		)
	);

	$menu = array();
	$results = array('New', 'Recent','Older','All');	

	foreach ( $results as $value ) {
		$parms = array('Recent'=>rawurlencode($value));
    	$menu[] = new categoryLeaf( 
    		array(XmlEmitter::ATR.'title'=>$value
    		, XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_recent_xml.php?".http_build_query($parms))  
    	);   
	}

	$recent->categoryLeaf = $menu;

	return $recent;	
}

?>
