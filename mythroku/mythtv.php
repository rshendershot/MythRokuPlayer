<?php
require_once 'settings.php';


class categories extends XmlIterator {
	public $banner_ad; //attribute
	public $category = array();	
}
class banner_ad extends XmlIterator {
	public $sd_img; //attribute
	public $hd_img; //attribute
}
class category extends XmlIterator {
	public $title; //attribute
	public $description; //attribute
	public $sd_img; //attribute
	public $hd_img; //attribute
	public $categoryLeaf = array();
}
class categoryLeaf extends XmlIterator {
	public $title; //attribute
	public $description; //attribute
	public $feed; //attribute
}

//TV
$tv_date = new categoryLeaf(
	array(XmlIterator::ATR.'description'=>'', XmlIterator::ATR.'title'=>'Date', XmlIterator::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=date")
);
$tv_genre = new categoryLeaf(
	array(XmlIterator::ATR.'description'=>'', XmlIterator::ATR.'title'=>'Genre', XmlIterator::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=genre")
);
$tv_title = new categoryLeaf(
	array(XmlIterator::ATR.'description'=>'', XmlIterator::ATR.'title'=>'Title', XmlIterator::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=title")
);
$tv_channel = new categoryLeaf(
	array(XmlIterator::ATR.'description'=>'', XmlIterator::ATR.'title'=>'Channel', XmlIterator::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=channel")
);
$tv_group = new categoryLeaf(
	array(XmlIterator::ATR.'description'=>'', XmlIterator::ATR.'title'=>'Group', XmlIterator::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv_xml.php?sort=group")
);
$tv = new category(
	array(XmlIterator::ATR.'title'=>'TV'
		, XmlIterator::ATR.'description'=>'Television'
		, XmlIterator::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_tv.png"
		, XmlIterator::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_tv.png"
		, 'categoryLeaf'=>array($tv_date, $tv_genre, $tv_title, $tv_channel, $tv_group)
	)
);

//VID
$vid_date = new categoryLeaf(
	array(XmlIterator::ATR.'description'=>'', XmlIterator::ATR.'title'=>'Date', XmlIterator::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_movies_xml.php?sort=date")
);
$vid_title = new categoryLeaf(
	array(XmlIterator::ATR.'description'=>'', XmlIterator::ATR.'title'=>'Title', XmlIterator::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_movies_xml.php?sort=title")
);
$vid = new category(
	array(XmlIterator::ATR.'title'=>'VID'
		, XmlIterator::ATR.'description'=>'Videos'
		, XmlIterator::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_movie.png"
		, XmlIterator::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_movie.png"
		, 'categoryLeaf'=>array($vid_date, $vid_title)
	)
);

//TEST
$test_all = new categoryLeaf(
	array(XmlIterator::ATR.'description'=>'', XmlIterator::ATR.'title'=>'All', XmlIterator::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_all_xml.php")
);
$test = new category(
	array(XmlIterator::ATR.'title'=>'All'
		, XmlIterator::ATR.'description'=>'All Movie and TV'
		, XmlIterator::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/mythtv_all.png"
		, XmlIterator::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/mythtv_all.png"
		, 'categoryLeaf'=>array($test_all)
	)
);
//CONFIG
$config = new categoryLeaf(
	array(XmlIterator::ATR.'description'=>'', XmlIterator::ATR.'title'=>'Settings', XmlIterator::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_tv.xml")
);
$conf = new category(
	array(XmlIterator::ATR.'title'=>'Settings'
		, XmlIterator::ATR.'description'=>'Configuration'
		, XmlIterator::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_settings.png"
		, XmlIterator::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/Mythtv_settings.png"
		, 'categoryLeaf'=>array($config)
	)
);

//TOP
$top = new categories(
	array('banner_ad'=>new banner_ad(
		array(XmlIterator::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/mythtv_logo_SD.png"
			,XmlIterator::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/mythtv_logo_SD.png")
		)
		, 'category'=>array($tv, $vid, $conf, $test)
	)
);

print "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>" . $top ."\n";
?>
