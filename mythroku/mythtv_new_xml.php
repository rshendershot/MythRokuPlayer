<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

if(isset($_GET['New'])) {
	$select = rawurldecode($_GET['New']);

	//build feed for this specific genre	
	error_log("selecting New: $select", 0);
	
	$interval = '1 week';
	if(useUTC())
		$intervalQry = "program.starttime BETWEEN utc_timestamp() AND adddate(utc_timestamp(), interval $interval) ";
	else
		$intervalQry = "program.starttime BETWEEN now() AND adddate(now(), interval $interval) ";
	
	$conditions = array('conditions'=>"program.manualid=0 AND $intervalQry");
	switch ( $select ) {
		case 'Series': $conditions['conditions'].= " AND program.category_type='series' AND (program.programid like '%001' AND program.previouslyshown=FALSE AND program.first=TRUE) OR (program.subtitle = 'Pilot' and program.first=TRUE) "; break;
		case 'Specials': $conditions['conditions'].= " AND program.category='Special'  AND program.originalairdate> adddate(now(), interval -1 month) AND( program.first=true OR program.last=true )"; break;
		case 'Movies': $conditions['conditions'].= " AND program.category_type='movie' AND program.airdate>=year(now())-2 AND( program.first=true OR program.last=true )"; break;
		case 'Sports': $conditions['conditions'].= " AND program.category LIKE '$NewSportsQueryType' AND program.PreviouslyShown=false AND( program.first=true OR program.last=true ) AND( program.originalairdate is null OR program.originalairdate> adddate(now(), interval -1 day) )"; break;
		default:
			break;
	}	
	$conditions['conditions'].= " AND program.chanid in (select chanid from channel where visible=TRUE)";
	$conditions['conditions'].= " ORDER BY program.starttime";
	$conditions['conditions'].= " LIMIT $NewShowsQueryLimit";
	
	$joins = array('joins'=>'LEFT JOIN oldrecorded o on(program.programid=o.programid AND program.starttime=o.starttime)');
	$query = array('select'=>'program.*, o.recstatus, o.station ');
	$guide = Guide::all( array_merge($query, $joins, $conditions) );
	error_log("COUNT of GUIDE: ".count($guide), 0);
	
	$items = array();
	$shows = array_values(array_merge($guide));
	foreach($shows as $item => $show ){
		if($show->recstatus != 8 && $show->recstatus != 4 ) { //later or earlier showings removed from list
			//error_log(">>> chanid: $show->chanid  time: $show->starttime  recstatus: $show->recstatus", 0);
			$items[] = new item($show);
		}
	}
	$items = array_unique($items);
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
	// build category static groupings: Series, Specials, Movies

	$new = new category(
		array(XmlEmitter::ATR.'title'=>'Guide'
			, XmlEmitter::ATR.'description'=>'New Pilots, Premieres and recent Movies'
			, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/view-right-new-4.png"
			, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/view-right-new-4.png"
			, 'categoryLeaf'=>array()
		)
	);

	$menu = array();
	$results = array('Series','Specials','Movies','Sports');	
	
	foreach ( $results as $value ) {
		$parms = array('New'=>rawurlencode($value));
    	$menu[] = new categoryLeaf( 
    		array(XmlEmitter::ATR.'title'=>$value
    		, XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_new_xml.php?".http_build_query($parms))  
    	);   
	}

	$new->categoryLeaf = $menu;

	return $new;
}

?>
