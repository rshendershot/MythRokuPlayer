<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

/*
 * usage:   php mythtv_all_xml.php [url-to-decode]
 * 
 * This file provides troubleshooting support by creating a Feed list of everything that
 * whould be shown by the Roku.  It can be called with the optional URL to make them 
 * more readable.  Look in the output for sdImg or streamUrl - this will decode either.
 */

if(!empty($argv[1])){
	//var_dump( $argv);
	$d = $argv[1];
	$d1 = rawurldecode($d);
	$d2 = strpos($d1, '%') ? rawurldecode($d1) : $d1;
	print "---\n $d";
	print "\n  is...\n";
	print " $d2 \n---\n";
	
}else{
	$select = array('select' => '*, case title regexp \'^The \' when 1 then SUBSTRING(title,5) else title end as titleSortkey');
	$order = array('order' => 'titleSortkey ASC');
	
	$conditions = array('conditions' => array('basename like ? ', '%.mp4'));
	$record = Recorded::all( array_merge($select, $conditions, $order) );
	
	$select['select'] .= ', case releasedate when (releasedate is null) then insertdate else releasedate end as starttime';
	$conditions = array('conditions' => array('filename like ? AND host > ?', '%.m%4%', ''));
	$video = VideoMetadata::all( array_merge($select, $conditions, $order) );
	
	$items = array();
	$shows = array_values(array_merge($record, $video));
	foreach($shows as $item => $show ){
		$items[] = new item($show);
	}
	usort($items, 'items_title_compare');
	
	$feed = new feed(
		array(
			'resultLength'=>new resultLength(array('content'=>count($items)))
			, 'endIndex'=>new endIndex(array('content'=>count($items)))
			, 'item'=>$items
		)
	);
	
	print $feed;
}
?>
