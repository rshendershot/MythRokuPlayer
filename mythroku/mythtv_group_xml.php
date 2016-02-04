<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

if(isset($_GET['Group'])) {
	$select = str_replace(' ', '%', rawurldecode($_GET['Group']));
	$SQL = <<<EOF
select 
  (case v.category when 0 then 'Default' else c.category end) as category
, (case releasedate when (releasedate is null) then insertdate else releasedate end) as starttime
,v.*
from videometadata v left join videocategory c on c.intid = v.category
where v.filename like '%.m%4%' 
and v.host > ''
having category like '$select';
EOF;

	//build feed for this specific group	
	error_log("selecting Group: $select", 0);

	$conditions = array('conditions' => array('basename like ? AND playgroup like ?', '%.mp4', $select));
	$rquery = array(
			'select'=>'recorded.*
			, ifnull(
				nullif(recorded.originalairdate,0)
				, makedate( (select airdate from recordedprogram where recordedprogram.chanid=recorded.chanid and recordedprogram.starttime=recorded.starttime),1 )
	    	) as airdate'
	);
	
	$record = Recorded::all( array_merge($rquery, $conditions) );	
	error_log("COUNT of RECORDED: ".count($record), 0);
	
	$video = VideoMetadata::find_by_sql( $SQL );
	error_log("COUNT of VIDEOMETADATA: ".count($video), 0);
	
	$items = array();
	$shows = array_values(array_merge($record, $video));
	foreach($shows as $item => $show ){
		$items[] = new item($show);
	}
	//usort($items, 'items_title_date_compare');
	usort($items, 'items_title_episode_compare');
	
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
	//build category from available groups	

	$group = new category(
		array(XmlEmitter::ATR.'title'=>'Group'
			, XmlEmitter::ATR.'description'=>'Select a Group'
			, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/tab-detach.png"
			, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/tab-detach.png"
			, 'categoryLeaf'=>array()
		)
	);

	$menu = array();
	$results = array();
	
	$rec_cat = Recorded::find_by_sql( "select distinct playgroup from recorded where basename like '%.mp4'" );
	foreach ( $rec_cat as $value ) {
       $results[] = ucwords(str_replace('-', ' ', $value->playgroup));
	}	
	$vid_genre = VideoCategory::find_by_sql( 'select distinct vc.category from videocategory vc join videometadata v on v.category = vc.intid' );
	foreach ( $vid_genre as $value ) {
    	$results[] = ucwords(str_replace('-', ' ', $value->category));   
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
