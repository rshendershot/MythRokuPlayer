<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

if(isset($_GET['Group'])) {
	$select = rawurldecode($_GET['Group']);
	$SQL = <<<EOF
select v.*,case v.category when 0 then 'Default' else c.category end as categoryKey 
from videometadata v left join videocategory c on c.intid = v.category
where v.filename like '%.m%4%' 
and v.host > ''
having categoryKey = '$select';
EOF;
}


if(isset($select)){
//build feed for this specific group	
	error_log("selecting Group: $select", 0);

	$conditions = array('conditions' => array('basename like ? AND playgroup=?', '%.mp4', $select));
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
//build category from available groups	

	$group = new category(
		array(XmlEmitter::ATR.'title'=>'Group'
			, XmlEmitter::ATR.'description'=>'Select a Group'
			, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/galleryfolder.png"
			, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/galleryfolder.png"
			, 'categoryLeaf'=>array()
		)
	);

	$menu = array();
	$results = array();
	
	$rec_cat = Recorded::find_by_sql( 'select distinct playgroup from recorded' );
	foreach ( $rec_cat as $value ) {
       $results[] = $value->playgroup;
	}	
	$vid_genre = VideoCategory::find_by_sql( 'select category from videocategory' );
	foreach ( $vid_genre as $value ) {
    	$results[] = $value->category;   
	}	
	asort($results);
	$results = array_unique($results);

	foreach ( $results as $value ) {
		$parms = array('Group'=>rawurlencode($value));
    	$menu[] = new categoryLeaf( 
    		array(XmlEmitter::ATR.'title'=>$value
    		, XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_group_xml.php?".http_build_query($parms))  
    	);   
	}

	$group->categoryLeaf = $menu;

	return $group;
}

?>
