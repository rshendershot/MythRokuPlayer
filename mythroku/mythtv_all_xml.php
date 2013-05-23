<?php
require_once 'settings.php';
//const _DEBUG = 'true';
class feed extends XmlIterator {
	public $resultLength;  //value
	public $endIndex;  //value
	public $item = array();
}
class item extends XmlIterator {	
	public $sdImg; //attribute
	public $hdImg; //attribute
	public $title;
	public $contentId;
	public $contentType;
	public $contentQuality;
	public $media;  //child element
	public $synopsis;
	public $genres;
	public $subtitle;
	public $runtime;
	public $date;
	public $tvormov;
	public $delcommand;
	public $starrating;
	
	public function __construct($show){
		$WebServer = $GLOBALS['WebServer'];
		$MythRokuDir = $GLOBALS['MythRokuDir'];
		$RokuDisplayType = $GLOBALS['RokuDisplayType'];
		$BitRate = $GLOBALS['BitRate'];

		$coverart = StorageGroup::first( array('conditions' => array('groupname = ?', 'Coverart')) );
		$screenart = StorageGroup::first( array('conditions' => array('groupname = ?', 'Screenshots')) );
		$videos = StorageGroup::first( array('conditions' => array('groupname = ?', 'Videos')) );
						
		$this->media = new media(
			array(
				'streamFormat'=>new streamFormat(array('content'=>'mp4'))
				, 'streamQuality'=>new streamQuality(array('content'=>$RokuDisplayType))
				, 'streamBitrate'=>new streamBitrate(array('content'=>$BitRate))
				, 'streamUrl'=>new streamUrl()
			)
		);
		$this->title = new title(array('content'=>normalizeHtml($show->title))); 
		$this->contentQuality = new contentQuality(array('content'=>$RokuDisplayType));
		$this->subtitle = new subtitle(array('content'=>normalizeHtml($show->subtitle)));
		if(is_a($show,'Recorded')){
			//print("it's a TV show'");
			
			$ShowLength = convert_datetime($show->endtime) - convert_datetime($show->starttime);
	    	$streamfile  = $show->storagegroups->dirname . $show->basename;
	
	    	$parms = array('image'=>$streamfile);
	    	$streamUrl = "$WebServer/$MythRokuDir/image.php?"
	    		.http_build_query($parms);
	
	    	$parms = array('preview'=>str_pad($show->chanid, 6, "_", STR_PAD_LEFT).rawurlencode($show->starttime));
	    	$imgUrl = "$WebServer/$MythRokuDir/image.php?" 
	    		.http_build_query($parms);			
						
			parent::addToAttributes('sdImg', $imgUrl);
			parent::addToAttributes('hdImg', $imgUrl);
			$this->contentId = new contentId(array('content'=>$show->basename));
			$this->contentType = new contentType(array('content'=>'TV'));
			$this->media->streamUrl->setContent("$streamUrl "); //yes the space is required
			$this->synopsis = new synopsis(array('content'=>normalizeHtml($show->description)));
			$this->genres = new genres(array('content'=>normalizeHtml($show->category)));
			$this->runtime = new runtime(array('content'=>$ShowLength));
			$this->date = new date(array('content'=>date("F j, Y, g:i a", convert_datetime($show->starttime))));
			$this->tvormov = new tvormov(array('content'=>'tv'));
			$this->delcommand = new delcommand(array('content'=>"$WebServer/mythroku/mythtv_tv_del.php?basename=$show->basename"));
		}elseif(is_a($show,'VideoMetadata')){
			//print("it's a Video'");
			
	    	$category = VideoCategory::first( array('conditions' => array('intid = ?', $show->category)) );    	
	    	$streamfile = $videos->dirname . $show->filename;
	    	$coverfile = $coverart->dirname . $show->coverfile; 
	    	$screenfile = $screenart->dirname . $show->screenshot;
	    	
	    	$streamUrl = "$WebServer/$MythRokuDir/image.php?image=" . rawurlencode($streamfile);	    	   	
	    	
	    	$imgUrl = "$WebServer/$MythRokuDir/image.php?image=" . rawurlencode($screenfile);
	    		 			
			parent::addToAttributes('sdImg', $imgUrl);
			parent::addToAttributes('hdImg', $imgUrl);
			$this->contentId = new contentId(array('content'=>$show->filename));
			$this->contentType = new contentType(array('content'=>'Movie'));
			$this->media->streamUrl->setContent("$streamUrl "); //yes the space is required
			$this->synopsis = new synopsis(array('content'=>normalizeHtml($show->plot)));
			$this->genres = new genres(array('content'=>normalizeHtml($category->category)));
			$this->runtime = new runtime(array('content'=>$show->length));
			$this->date = new date(array('content'=>"Year: $show->year"));
			$this->tvormov = new tvormov(array('content'=>'movie'));
			$this->starrating = new starrating(array('content'=>$show->userrating * 10));
		}else{
			parent::__construct();
		}
				
	}
	
}
// Value objects
class resultLength extends XmlIterator {}
class endIndex extends XmlIterator {}

class title extends XmlIterator {}
class contentId extends XmlIterator {}
class contentType extends XmlIterator {} //TV,Movie
class contentQuality extends XmlIterator {} //SD,HD
class synopsis extends XmlIterator {}
class genres extends XmlIterator {}
class subtitle extends XmlIterator {}
class runtime extends XmlIterator {}
class date extends XmlIterator {}
class tvormov extends XmlIterator {}
class delcommand extends XmlIterator {}
class starrating extends XmlIterator {}

class streamFormat extends XmlIterator {}
class streamQuality extends XmlIterator {}
class streamBitrate extends XmlIterator {}
class streamUrl extends XmlIterator {}

// Child elements
class media extends XmlIterator {
	public $streamFormat;
	public $streamQuality;
	public $streamBitrate;
	public $streamUrl;	
}

$select = array('select' => '*, case title regexp \'^The \' when 1 then SUBSTRING(title,5) else title end as titleSortkey');
$conditions = array('conditions' => array('basename like ? ', '%.mp4'));
$order = array('order' => 'titleSortkey ASC');
$record = Recorded::all( array_merge($select, $conditions, $order) );


$conditions = array('conditions' => array('filename like ? AND host > ?', '%.m%4%', ''));
$order = array('order' => 'title ASC');
$video = VideoMetadata::all( array_merge($conditions, $order) );

$items = array();
$shows = array_values(array_merge($record, $video));
usort($shows, 'shows_compare');
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
