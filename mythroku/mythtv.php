<?php
require_once 'settings.php';
include_once 'player_feed.php';

//TV
$tv_date = new categoryLeaf(
	array(XmlEmitter::ATR.'description'=>'some description', XmlEmitter::ATR.'title'=>'Date', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=date")
);
$tv_title = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'Title', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=title")
);
$tv_special = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'Special', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=special")
);
$tv_political = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'Political', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=political")
);
$tv_ed = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'Educational', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=education")
);
$tv_movies = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'Movies', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=movies")
);
$tv_channel = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'Channel', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=channel")
);
$tv_group = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'Group', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=playgroup")
);
$tv_todo = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'ToDo', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=todo")
);
$tv = new category(
	array(XmlEmitter::ATR.'title'=>'TV'
		, XmlEmitter::ATR.'description'=>'Television'
		, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_tv.png"
		, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_tv.png"
		, 'categoryLeaf'=>array($tv_date, $tv_title, $tv_special, $tv_political, $tv_ed, $tv_movies, $tv_channel, $tv_group, $tv_todo)
	)
);

//VID
$vid_date = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'Date', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_movies_xml.php?sort=date")
);
$vid_title = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'Title', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_movies_xml.php?sort=title")
);
$vid = new category(
	array(XmlEmitter::ATR.'title'=>'VID'
		, XmlEmitter::ATR.'description'=>'Videos'
		, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_movie.png"
		, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_movie.png"
		, 'categoryLeaf'=>array($vid_date, $vid_title)
	)
);

//GROUP  (recorded.playgroup and videometadata.category which are both user defined)

//GENRE  (recorded.category and videometadatagenre[videometadata 1-* videogenre] which are both supplied by feeds)
include 'mythtv_genre_xml.php';

//ALL
$mythtv_all = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'All', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_all_xml.php")
);
$all = new category(
	array(XmlEmitter::ATR.'title'=>'All'
		, XmlEmitter::ATR.'description'=>'All Movie and TV'
		, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/mythtv_all.png"
		, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/mythtv_all.png"
		, 'categoryLeaf'=>array($mythtv_all)
	)
);

//UPCOMING
$mythtv_upcoming_all = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'All', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_upcoming_xml.php")
);
$mythtv_upcoming_top = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>"Top $UpcomingListLimit", XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_upcoming_xml.php?Count=$UpcomingListLimit")
);
$upcoming = new category(
	array(XmlEmitter::ATR.'title'=>'Upcoming'
		, XmlEmitter::ATR.'description'=>'Scheduled Recordings'
		, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/mythtv_scheduled.png"
		, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/mythtv_scheduled.png"
		, 'categoryLeaf'=>array($mythtv_upcoming_top, $mythtv_upcoming_all)
	)
);

//CONFIG
$config = new categoryLeaf(
	array(XmlEmitter::ATR.'title'=>'Settings', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv.xml")
);
$conf = new category(
	array(XmlEmitter::ATR.'title'=>'Settings'
		, XmlEmitter::ATR.'description'=>'Configuration'
		, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_settings.png"
		, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_settings.png"
		, 'categoryLeaf'=>array($config)
	)
);

//TOP
$top = new categories(
	array('banner_ad'=>new banner_ad(
		array(XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/mythtv_logo_SD.png"
			,XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/mythtv_logo_SD.png")
		)
		, 'category'=>array($tv, $vid, $conf, $genre, $all, $upcoming)
	)
);

print $top;
?>
