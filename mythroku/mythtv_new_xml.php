<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

if(isset($_GET['New'])) {
	$select = rawurldecode($_GET['New']);

	//build feed for this specific genre	
	error_log("selecting New: $select", 0);
	
	$conditions = array('conditions'=>'manualid=0 AND starttime>now() AND starttime< adddate(now(), interval 1 day)');
	switch ( $select ) {
		case 'Series': $conditions['conditions'].= " AND category_type='series' AND subtitle='Pilot'"; break;
		case 'Specials': $conditions['conditions'].= " AND category='special'"; break;
		case 'Movies': $conditions['conditions'].= " AND category='movie' AND airdate>=year(now())-2"; break;
		default:
			break;
	}	
	
	$guide = Guide::all( $conditions );
	error_log("COUNT of PROGRAM: ".count($guide));
	
	$items = array();
	$shows = array_values(array_merge($guide));
	foreach($shows as $item => $show ){
		$items[] = new item($show);
	}
	$items = array_unique($items);
	usort($items, 'items_title_compare');
	
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
	// build category static groupings: TV, Movies, All

	$new = new category(
		array(XmlEmitter::ATR.'title'=>'New'
			, XmlEmitter::ATR.'description'=>'Pilots, Premieres and recent Movies'
			, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/bookmark.png"
			, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/bookmark.png"
			, 'categoryLeaf'=>array()
		)
	);

	$menu = array();
	$results = array('Series','Specials','Movies');	
	
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
