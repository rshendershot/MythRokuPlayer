<?php
require_once 'settings.php';
include 'player_feed.php';

class categories extends XmlEmitter {
	public $banner_ad; //attribute
	public $category = array();	
}
class banner_ad extends XmlEmitter {
	public $sd_img; //attribute
	public $hd_img; //attribute
}
class category extends XmlEmitter {
	public $title; //attribute
	public $description; //attribute
	public $sd_img; //attribute
	public $hd_img; //attribute
	public $categoryLeaf = array();
}
class categoryLeaf extends XmlEmitter {
	public $title; //attribute
	public $description; //attribute
	public $feed; //attribute
}

//TV
$tv_date = new categoryLeaf(
	array(XmlEmitter::ATR.'description'=>'', XmlEmitter::ATR.'title'=>'Date', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=date")
);
$tv_genre = new categoryLeaf(
	array(XmlEmitter::ATR.'description'=>'', XmlEmitter::ATR.'title'=>'Genre', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=genre")
);
$tv_title = new categoryLeaf(
	array(XmlEmitter::ATR.'description'=>'', XmlEmitter::ATR.'title'=>'Title', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=title")
);
$tv_channel = new categoryLeaf(
	array(XmlEmitter::ATR.'description'=>'', XmlEmitter::ATR.'title'=>'Channel', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=channel")
);
$tv_group = new categoryLeaf(
	array(XmlEmitter::ATR.'description'=>'', XmlEmitter::ATR.'title'=>'Group', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=group")
);
$tv = new category(
	array(XmlEmitter::ATR.'title'=>'TV'
		, XmlEmitter::ATR.'description'=>'Television'
		, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_tv.png"
		, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_tv.png"
		, 'categoryLeaf'=>array($tv_date, $tv_genre, $tv_title, $tv_channel, $tv_group)
	)
);

//VID
$vid_date = new categoryLeaf(
	array(XmlEmitter::ATR.'description'=>'', XmlEmitter::ATR.'title'=>'Date', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_movies_xml.php?sort=date")
);
$vid_title = new categoryLeaf(
	array(XmlEmitter::ATR.'description'=>'', XmlEmitter::ATR.'title'=>'Title', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_movies_xml.php?sort=title")
);
$vid = new category(
	array(XmlEmitter::ATR.'title'=>'VID'
		, XmlEmitter::ATR.'description'=>'Videos'
		, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_movie.png"
		, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_movie.png"
		, 'categoryLeaf'=>array($vid_date, $vid_title)
	)
);

//ALL
$mythtv_all = new categoryLeaf(
	array(XmlEmitter::ATR.'description'=>'', XmlEmitter::ATR.'title'=>'All', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_all_xml.php")
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
	array(XmlEmitter::ATR.'description'=>'', XmlEmitter::ATR.'title'=>'All', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_upcoming_xml.php")
);
$mythtv_upcoming_top = new categoryLeaf(
	array(XmlEmitter::ATR.'description'=>'', XmlEmitter::ATR.'title'=>"Top $UpcomingListLimit", XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_upcoming_xml.php?Count=$UpcomingListLimit")
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
	array(XmlEmitter::ATR.'description'=>'', XmlEmitter::ATR.'title'=>'Settings', XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv.xml")
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
		, 'category'=>array($tv, $vid, $conf, $all, $upcoming)
	)
);

print "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>" . $top ."\n";
?>
