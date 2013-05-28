<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

if(isset($_GET['Genre'])) {
	$select = rawurldecode($_GET['Genre']);
	$SQL = <<<EOF
select g.genre, v.* from videometadatagenre a 
join videometadata v on v.intid = a.idvideo
join videogenre g on g.intid = a.idgenre
where v.filename like '%.m%4%' 
and v.host > ''
and g.genre = '$select'
EOF;
}

if(isset($select)){
//build feed for this specific genre	
	error_log("selecting Genre: $select", 0);

	$conditions = array('conditions' => array('basename like ? AND category=?', '%.mp4', $select));
	$record = Recorded::all( $conditions );
	error_log("COUNT of RECORDED: ".count($record));
	
	$video = VideoMetadata::find_by_sql( $SQL );
	error_log("COUNT of VIDEOMETADATA: ".count($video));
	
	$items = array();
	$shows = array_values(array_merge($record, $video));
	usort($shows, 'shows_title_compare');
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
				, 'item'=>array(new item(new Program(new SimpleXMLElement(Program::NONE))))
			)		
		);				
	}
	
	print $feed;
	
}else{
//build category from available genres	

	$genre = new category(
		array(XmlEmitter::ATR.'title'=>'Genre'
			, XmlEmitter::ATR.'description'=>'Select a Genre'
			, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/bookmark.png"
			, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/bookmark.png"
			, 'categoryLeaf'=>array()
		)
	);

	$menu = array();
	$results = array();
	
	$rec_cat = Recorded::find_by_sql( 'select distinct category from recorded' );
	foreach ( $rec_cat as $value ) {
       $results[] = $value->category;
	}	
	$vid_genre = VideoMetadata::find_by_sql( 'select genre from videogenre' );
	foreach ( $vid_genre as $value ) {
    	$results[] = $value->genre;   
	}	
	asort($results);
	$results = array_unique($results);

	foreach ( $results as $value ) {
		$parms = array('Genre'=>rawurlencode($value)); 
    	$menu[] = new categoryLeaf( 
    		array(XmlEmitter::ATR.'title'=>$value
    		, XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_genre_xml.php?".http_build_query($parms)) 
    	);   
	}

	$genre->categoryLeaf = $menu;

	return $genre;
}

?>