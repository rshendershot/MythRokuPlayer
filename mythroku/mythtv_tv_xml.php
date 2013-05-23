<?php

//get the local info from the settings file
require_once 'settings.php';

$conditions = array('conditions' => array('basename like ? ', '%.mp4'));
$order = array('order' => 'starttime ASC');
$select = array('select' => '*, case title regexp \'^The \' when 1 then SUBSTRING(title,5) else title end as titleSortkey');
if (isset($_GET['sort'])) //there is not GET in the session when running php from CLI
{
    switch($_GET['sort'])
    {
        case "date":
            $order = array('order' => 'starttime DESC');
            break;
        case "title":
            $order = array('order' => 'titleSortkey ASC');
            break;
        case "playgroup":
            $order = array('order' => 'playgroup ASC');
            break;
        case "genre":
            $order = array('order' => 'category ASC');
            break;
        case "channel":
            $order = array('order' => 'chanid ASC');
            break;
        default:
            break;
    }
}

$item = Recorded::all( array_merge($select, $conditions, $order) );

//print out the record in xml format for roku to read
print "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?> 
	<feed>
	<!-- resultLength indicates the total number of results for this feed -->
	<resultLength>" . count($item) . "</resultLength>
	<!-- endIndix  indicates the number of results for this *paged* section of the feed -->
	<endIndex>" . count($item)  . "</endIndex>";

    foreach ($item as $key => $value)
    {		
		$ShowLength = convert_datetime($value->endtime) - convert_datetime($value->starttime);
    	$streamfile  = $value->storagegroups->dirname . $value->basename;

    	$parms = array('image'=>$streamfile);
    	$streamUrl = $WebServer . "/" . $MythRokuDir . "/image.php?" 
    		.http_build_query($parms);

    	$parms = array('preview'=>str_pad($value->chanid, 6, "_", STR_PAD_LEFT).rawurlencode($value->starttime));
    	$imgUrl = $WebServer . "/" . $MythRokuDir . "/image.php?" 
    		.http_build_query($parms);

	    //print out the record in xml format for roku to read 
	    print "	
	    <item sdImg=\"" . $imgUrl . "\" hdImg=\"" . $imgUrl . "\" >
		    <title>" . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $value->title )) . "</title>
		    <contentId>" . $value->basename . "</contentId>
		    <contentType>TV</contentType>
		    <contentQuality>". $RokuDisplayType . "</contentQuality>
		    <media>
			    <streamFormat>mp4</streamFormat>
			    <streamQuality>". $RokuDisplayType . "</streamQuality>
			    <streamBitrate>" . $BitRate . "</streamBitrate>
			    <streamUrl>" . $streamUrl . " </streamUrl>
		    </media>
		    <synopsis>" . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $value->description )) . "</synopsis>
	        <genres>" . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $value->category )) . "</genres>
		    <subtitle>" . htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $value->subtitle )) . "</subtitle>
            <runtime>" . $ShowLength . "</runtime>
  			<date>" . date("F j, Y, g:i a", convert_datetime($value->starttime)) . "</date>
		    <tvormov>tv</tvormov>
		    <delcommand>" . $WebServer . "/mythroku/mythtv_tv_del.php?basename=" . $value->basename . "</delcommand>
	    </item>";	
    }

print "</feed>";

?>
