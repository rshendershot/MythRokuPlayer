<?php

//get the local info from the settings file
require_once './settings.php';

$conditions = array('conditions' => array('filename like ? AND host > ?', '%.m%4%', '')); //using combination of Storage Group and locally hosted video the host value in videometadata is currently only set for the backend machine.  TODO: check for actual host name
$order = array('order' => 'insertdate ASC');

if (isset($_GET['sort'])) //there is not GET in the session when running php from CLI
{
    switch($_GET['sort'])
    {
        case "date":
            $order = array('order' => 'insertdate DESC');
            break;
        case "title":
            $order = array('order' => 'title ASC');
            break;
        case "genre":
            $order = array('order' => 'category ASC');
            break;
        case "year":
            $order = array('order' => 'year DESC');
            break;
        default:
            break;
    }	
}




$item = VideoMetadata::all( array_merge($conditions, $order) );
	
//print the xml header
print "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?> 
	<feed>
	<!-- resultLength indicates the total number of results for this feed -->
	<resultLength>" . count($item) . "</resultLength>
	<!-- endIndix  indicates the number of results for this *paged* section of the feed -->
	<endIndex>" . count($item)  . "</endIndex>";

	$coverart = StorageGroup::first( array('conditions' => array('groupname = ?', 'Coverart')) );
	$screenart = StorageGroup::first( array('conditions' => array('groupname = ?', 'Screenshots')) );
	$videos = StorageGroup::first( array('conditions' => array('groupname = ?', 'Videos')) );
	
    foreach ($item as $key => $value)
    {   
    	$category = VideoCategory::first( array('conditions' => array('intid = ?', $value->category)) );    	
    	$streamfile = $videos->dirname . $value->filename;
    	$coverfile = $coverart->dirname . $value->coverfile; 
    	$screenfile = $screenart->dirname . $value->screenshot;
    	// TODO define sdposterurl and hdposterurl as coverfile.  needs channel deployment.  see categoryFeed.brs

	    //print out the record in xml format for roku to read
		print 	
		"<item sdImg=\"" . $WebServer . "/" . $MythRokuDir . "/image.php?image=" . rawurlencode($screenfile) . "\"" .
				" hdImg=\"" . $WebServer . "/" . $MythRokuDir . "/image.php?image=" . rawurlencode($screenfile) . "\" >
			<title>" . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $value->title )) . "</title>
			<contentId>" . $value->filename . "</contentId>
			<contentType>Movies</contentType>
			<contentQuality>". $RokuDisplayType . "</contentQuality>
			<media>
				<streamFormat>mp4</streamFormat>
				<streamQuality>". $RokuDisplayType . "</streamQuality>
				<streamBitrate>". $BitRate . "</streamBitrate>
				<streamUrl>" . $WebServer . "/" . $MythRokuDir . "/image.php?image=" . rawurlencode($streamfile) . " </streamUrl>
			</media>
			<synopsis>" . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $value->plot )) . "</synopsis>
			<genres>" . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $category->category )) . "</genres>
			<runtime>" .$value->length . "</runtime>
			<date>Year: " . $value->year . "</date>
			<tvormov>movie</tvormov>
			<starrating>" . $value->userrating * 10 ."</starrating>
		</item>";	
    }

print "</feed>";

?>

