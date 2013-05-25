<?php
require_once 'settings.php';
include 'player_feed.php';

//const _DEBUG = 'true';

$select = array('select' => '*, case title regexp \'^The \' when 1 then SUBSTRING(title,5) else title end as titleSortkey');
$conditions = array('conditions' => array('basename like ? ', '%.mp4'));
$order = array('order' => 'titleSortkey ASC');
$record = Recorded::all( array_merge($select, $conditions, $order) );


$conditions = array('conditions' => array('filename like ? AND host > ?', '%.m%4%', ''));
$order = array('order' => 'title ASC');
$video = VideoMetadata::all( array_merge($conditions, $order) );

$items = array();
$shows = array_values(array_merge($record, $video));
usort($shows, 'shows_title_compare');
foreach($shows as $item => $show ){
	$items[] = new item($show);
}

$feed = new feed(
	array(
		'resultLength'=>new resultLength(array('content'=>count($items)))
		, 'endIndex'=>new endIndex(array('content'=>count($items)))
		, 'item'=>$items
	)
);

print "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>" . $feed ."\n";
?>
