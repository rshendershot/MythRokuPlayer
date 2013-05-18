<?php
require_once 'settings.php';

class categories extends XmlIterator {
	public $banner_ad;
	public $category = array();	
}
class banner_ad extends XmlIterator {
	public $sd_img;
	public $hd_img;
}
class category extends XmlIterator {
	public $title;
	public $description;
	public $sd_img;
	public $hd_img;
	public $categoryLeaf = array();
}
class categoryLeaf extends XmlIterator {
	public $title;
	public $description='';
	public $feed;
}

//TV
$tv_date = new categoryLeaf(
	array('title'=>'Date', 'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=date")
);
$tv_genre = new categoryLeaf(
	array('title'=>'Genre', 'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=genre")
);
$tv_title = new categoryLeaf(
	array('title'=>'Title', 'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=title")
);
$tv_channel = new categoryLeaf(
	array('title'=>'Channel', 'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=channel")
);
$tv_group = new categoryLeaf(
	array('title'=>'Group', 'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=group")
);
$tv = new category(
	array('title'=>'TV'
		, 'description'=>'Television'
		, 'sd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_tv.png"
		, 'hd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_tv.png"
		, 'categoryLeaf'=>array($tv_date, $tv_genre, $tv_title, $tv_channel, $tv_group)
	)
);

//VID
$vid_date = new categoryLeaf(
	array('title'=>'Date', 'feed'=>"$WebServer/$MythRokuDir/mythtv_movies_xml.php?sort=date")
);
$vid_title = new categoryLeaf(
	array('title'=>'Title', 'feed'=>"$WebServer/$MythRokuDir/mythtv_movies_xml.php?sort=title")
);
$vid = new category(
	array('title'=>'VID'
		, 'description'=>'Videos'
		, 'sd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_movie.png"
		, 'hd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_movie.png"
		, 'categoryLeaf'=>array($vid_date, $vid_title)
	)
);

//CONFIG
$config = new categoryLeaf(
	array('title'=>'Settings', 'feed'=>"$WebServer/$MythRokuDir/mythtv_tv.xml")
);
$conf = new category(
	array('title'=>'Settings'
		, 'description'=>'Configuration'
		, 'sd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_settings.png"
		, 'hd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_settings.png"
		, 'categoryLeaf'=>array($config)
	)
);

//TOP
$top = new categories(
	array('banner_ad'=>new banner_ad(
		array('sd_img'=>"$WebServer/$MythRokuDir/images/mythtv_logo_SD.png"
			,'hd_img'=>"$WebServer/$MythRokuDir/images/mythtv_logo_SD.png")
		)
		, 'category'=>array($tv, $vid, $conf)
	)
);

print "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>" . $top->__toString() ."\n";
?>
