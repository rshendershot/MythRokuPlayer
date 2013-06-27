<?php
require_once 'settings.php';
include_once 'player_feed.php';


//GROUP  (recorded.playgroup and videometadata.category which are both user defined)
include 'mythtv_group_xml.php';

//GENRE  (recorded.category and videometadatagenre[videometadata 1-* videogenre] which are both supplied by feeds)
include 'mythtv_genre_xml.php';

//DATE  (videometadata insertdate or releasedate as starttime, recorded starttime  DESC)
include 'mythtv_date_xml.php'; 

//UPCOMING
include 'mythtv_upcoming_xml.php';

//NEW
include 'mythtv_new_xml.php';

//WEATHER
include 'mythtv_weather_xml.php';

//CONFIG
$config = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'Settings', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv.xml")
);
$conf = new category(
	array(XmlEmitter::ATR.'title'=>'Settings'
		, XmlEmitter::ATR.'description'=>'Configuration'
		, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/preferences-system-2.png"
		, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/preferences-system-2.png"
		, 'categoryLeaf'=>array($config)
	)
);

////ALL
//$mythtv_all = new categoryLeaf(
//	array(XmlEmitter::ATR.'title'=>'All', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_all_xml.php")
//);
//$all = new category(
//	array(XmlEmitter::ATR.'title'=>'All'
//		, XmlEmitter::ATR.'description'=>'All Movie and TV'
//		, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/zoom-out.png"
//		, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/zoom-out.png"
//		, 'categoryLeaf'=>array($mythtv_all)
//	)
//);

//TOP
$top = new categories(
	array('banner_ad'=>new banner_ad(
		array(XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/mythtv_logo_SD.png"
			,XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/mythtv_logo_SD.png")
		)
		, 'category'=>array(
			$date
			, $upcoming
			, $conf  //-- This MUST be the third item.  The UI references it (2) by zero-based index. --//
			//, $all
			, $weather
			, $new
			, $group
			, $genre
		)
	)
);

print $top;
?>
