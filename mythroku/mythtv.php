<?php

require_once './settings.php';

print "
<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>
<categories>

	  <!-- banner_ad: optional element which displays an add at the top level category screen -->
	  <banner_ad sd_img=\"" . $WebServer . "/" . $MythRokuDir . "/images/mythtv_logo_SD.png\" hd_img=\"" . $WebServer . "/" . $MythRokuDir . "/images/mythtv_logo_SD.png\"/>

	<category title=\"MythTV TV\"       description=\"Television Recordings.\"            sd_img=\"" . $WebServer . "/" . $MythRokuDir . "/images/Mythtv_tv.png\"       hd_img=\"" . $WebServer . "/" . $MythRokuDir . "/images/Mythtv_tv.png\">
		<categoryLeaf title=\"Date\"     description=\"\" feed=\"" . $WebServer . "/" . $MythRokuDir . "/mythtv_tv_xml.php?sort=date\"/> 
		<categoryLeaf title=\"Genre\"    description=\"\" feed=\"" . $WebServer . "/" . $MythRokuDir . "/mythtv_tv_xml.php?sort=genre\"/> 
		<categoryLeaf title=\"Title\"    description=\"\" feed=\"" . $WebServer . "/" . $MythRokuDir . "/mythtv_tv_xml.php?sort=title\"/> 
		<categoryLeaf title=\"Channel\"  description=\"\" feed=\"" . $WebServer . "/" . $MythRokuDir . "/mythtv_tv_xml.php?sort=channel\"/> 
		<categoryLeaf title=\"Group\"    description=\"\" feed=\"" . $WebServer . "/" . $MythRokuDir . "/mythtv_tv_xml.php?sort=playgroup\"/> 		
	</category>

	<category title=\"MythTV Videos\"   description=\"Saved Videos.\"        sd_img=\"" . $WebServer . "/" . $MythRokuDir . "/images/Mythtv_movie.png\"    hd_img=\"" . $WebServer . "/" . $MythRokuDir . "/images/Mythtv_movie.png\">
		<categoryLeaf title=\"Date\"     description=\"\" feed=\"" . $WebServer . "/" . $MythRokuDir . "/mythtv_movies_xml.php?sort=date\"/> 
		<categoryLeaf title=\"Genre\"    description=\"\" feed=\"" . $WebServer . "/" . $MythRokuDir . "/mythtv_movies_xml.php?sort=genre\"/> 
		<categoryLeaf title=\"Title\"    description=\"\" feed=\"" . $WebServer . "/" . $MythRokuDir . "/mythtv_movies_xml.php?sort=title\"/> 
		<categoryLeaf title=\"Year\"     description=\"\" feed=\"" . $WebServer . "/" . $MythRokuDir . "/mythtv_movies_xml.php?sort=year\"/> 
	</category>

	<category title=\"Settings\" description=\"Roku MythTV Settings\" sd_img=\"" . $WebServer . "/" . $MythRokuDir . "/images/Mythtv_settings.png\" hd_img=\"" . $WebServer . "/" . $MythRokuDir . "/images/Mythtv_settings.png\">
		<categoryLeaf title=\"Settings\" description=\"\" feed=\"" . $WebServer . "/" . $MythRokuDir . "/mythtv_tv.xml\"/> 
	</category>

</categories>";

?>
