<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

$select = array('select' => '*, case title regexp \'^The \' when 1 then SUBSTRING(title,5) else title end as titleSortkey');
$order = array('order' => 'titleSortkey ASC');

$conditions = array('conditions' => array('basename like ? ', '%.mp4'));
$record = Recorded::all( array_merge($select, $conditions, $order) );

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
?>
