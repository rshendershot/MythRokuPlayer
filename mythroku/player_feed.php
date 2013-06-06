<?php
require_once 'settings.php';

// Weather API classes
class Weather extends XmlInjector {}

// MythTV Services API classes
class Program extends XmlInjector {
	public $isRecording = false;
	public $hasJob = false;
}
class ProgramTpl extends Program {
	const rsNONE = '<Program><Title>Nothing Found.</Title><Description>No results from your selection.  This is probably not a problem.</Description></Program>';
	const rsEMPTY = '<Program><Title>Service returned nothing.</Title><Description>Data from the service was empty.  Please try again later.</Description></Program>';

	public function __construct($xml){
		if(UseUTC)
			$this->StartTime=gmdate('Y-m-d H:i:s');
		else
			$this->StartTime=date('Y-m-d H:i:s');
		$this->EndTime=$this->StartTime;
		$this->SubTitle='Information';
		$this->ProgramId=$this->SubTitle;
		$this->Category=$this->SubTitle;
		
		parent::__construct($xml);
	}
}

// MythRokuPlayer menu classes
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
	
	public function __construct(){
		$arguments = func_get_args();
		
		// the player UI dies if category leaf has null value for description attribute, so fix it -RSH
		if (!array_key_exists(XmlEmitter::ATR.'description', $arguments[0])) {
			$arguments[0][XmlEmitter::ATR.'description'] = '';			
		}
		
		parent::__construct($arguments[0]);
	}
}

// MythRokuPlayer menu selection (feed) classes
class feed extends XmlEmitter {
	public $resultLength;  //value
	public $endIndex;  //value
	public $item = array();
}
class item extends XmlEmitter {	
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
						
		$this->media = new media(
			array(
				'streamFormat'=>new streamFormat(array('content'=>'mp4'))
				, 'streamQuality'=>new streamQuality(array('content'=>$RokuDisplayType))
				, 'streamBitrate'=>new streamBitrate(array('content'=>$BitRate))
				, 'streamUrl'=>new streamUrl()
			)
		);
		if(is_a($show,'Weather')){
			$ShowLength = 0;
			$title = "$show->Location,  $show->Temperature";
			$subtitle = "$show->Conditions, $show->WindSpeed, $show->WindDirection, $show->Clouds";
			$synopsis = "$subtitle $show->Source";

			$this->title = new title(array('content'=>$title)); 
			$this->contentQuality = new contentQuality(array('content'=>$RokuDisplayType));
			$this->subtitle = new subtitle(array('content'=>$subtitle));
			$this->addToAttributes('sdImg', "http://openweathermap.org/img/w/$show->Icon");
			$this->addToAttributes('hdImg', "http://openweathermap.org/img/w/$show->Icon");
			$this->contentId = new contentId(array('content'=>$show->Location));
			//$this->contentType = new contentType(array('content'=>'TV'));
			//$this->media->streamUrl->setContent("$streamUrl "); //yes the space is required
			
			$this->synopsis = new synopsis(array('content'=>$synopsis));
			$this->genres = new genres(array('content'=>$show->Temperature));
			$this->runtime = new runtime(array('content'=>0));
						
			$this->date = new date(array('content'=>date('D dMo', convert_date($show->AsOf))));
			$this->tvormov = new tvormov(array('content'=>'weather'));
		}elseif(is_a($show,'Program')){
			/// MythTV Program schema
			
			$ShowLength = convert_datetime($show->EndTime) - convert_datetime($show->StartTime);
			if($show->isRecording){
				$imgUrl = "$WebServer/$MythRokuDir/images/mythtv_other.png";
				$show->Category = 'NOW RECORDING!';
			} elseif($show->hasJob) {
				$imgUrl = "$WebServer/$MythRokuDir/images/mythtv_conflict.png";
				$show->Category = 'NOW PROCESSING A JOB!';
			} else {
				$imgUrl = "$WebServer/$MythRokuDir/images/mythtv_scheduled.png";
			}
			
			$this->title = new title(array('content'=>normalizeHtml($show->Title))); 
			$this->contentQuality = new contentQuality(array('content'=>$RokuDisplayType));
			$this->subtitle = new subtitle(array('content'=>normalizeHtml($show->SubTitle)));
			$this->addToAttributes('sdImg', $imgUrl);
			$this->addToAttributes('hdImg', $imgUrl);
			$this->contentId = new contentId(array('content'=>$show->ProgramId));
			//$this->contentType = new contentType(array('content'=>'TV'));
			//$this->media->streamUrl->setContent("$streamUrl "); //yes the space is required
			$this->synopsis = new synopsis(array('content'=>normalizeHtml($show->Description)));
			$this->genres = new genres(array('content'=>normalizeHtml($show->Category)));
			$this->runtime = new runtime(array('content'=>$ShowLength));
			if(!is_a($show, 'ProgramTpl)'))
				$this->date = new date(array('content'=>date("F j, Y, g:i a", convert_datetime($show->StartTime))));
			else
				$this->date = new date(array('content'=>$show->StartTime));
			$this->tvormov = new tvormov(array('content'=>'upcoming'));
			//error_log(">>>>DATE: $show->StartTime  ==>  $this->date", 0);
		}elseif(is_a($show,'Recorded')){
			/// TV from Recorded table
			
			$ShowLength = convert_datetime($show->endtime) - convert_datetime($show->starttime);
	    	$streamfile  = $show->storagegroups->dirname . $show->basename;
	
	    	$parms = array('image'=>$streamfile);
	    	$streamUrl = "$WebServer/$MythRokuDir/image.php?"
	    		.http_build_query($parms);
	
	    	$parms = array('preview'=>str_pad($show->chanid, 6, "_", STR_PAD_LEFT).rawurlencode($show->starttime));
	    	$imgUrl = "$WebServer/$MythRokuDir/image.php?" 
	    		.http_build_query($parms);			
						
			$this->title = new title(array('content'=>normalizeHtml($show->title))); 
			$this->contentQuality = new contentQuality(array('content'=>$RokuDisplayType));
			$this->subtitle = new subtitle(array('content'=>normalizeHtml($show->subtitle)));
			$this->addToAttributes('sdImg', $imgUrl);
			$this->addToAttributes('hdImg', $imgUrl);
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
			/// Video from VideoMetadata table
			
			$coverart = StorageGroup::first( array('conditions' => array('groupname = ?', 'Coverart')) );
			$screenart = StorageGroup::first( array('conditions' => array('groupname = ?', 'Screenshots')) );
			$videos = StorageGroup::first( array('conditions' => array('groupname = ?', 'Videos')) );
	    	$category = VideoCategory::first( array('conditions' => array('intid = ?', $show->category)) );    	
	    	
	    	$streamfile = $videos->dirname . $show->filename;
	    	$coverfile = $coverart->dirname . $show->coverfile; 
	    	$screenfile = $screenart->dirname . $show->screenshot;
	    	
	    	$streamUrl = "$WebServer/$MythRokuDir/image.php?image=" . rawurlencode($streamfile);	    	   	
	    	
	    	$imgUrl = "$WebServer/$MythRokuDir/image.php?image=" . rawurlencode($screenfile);
	    		 			
			$this->title = new title(array('content'=>normalizeHtml($show->title))); 
			$this->contentQuality = new contentQuality(array('content'=>$RokuDisplayType));
			$this->subtitle = new subtitle(array('content'=>normalizeHtml($show->subtitle)));
			$this->addToAttributes('sdImg', $imgUrl);
			$this->addToAttributes('hdImg', $imgUrl);
			$this->contentId = new contentId(array('content'=>$show->filename));
			$this->contentType = new contentType(array('content'=>'Movie'));
			$this->media->streamUrl->setContent("$streamUrl "); //yes the space is required
			$this->synopsis = new synopsis(array('content'=>normalizeHtml($show->plot)));
			$this->genres = new genres(array('content'=>normalizeHtml(empty($category->category) ? '':$category->category)));
			$this->runtime = new runtime(array('content'=>$show->length));
			$this->date = new date(array('content'=>"Year: $show->year"));
			$this->tvormov = new tvormov(array('content'=>'movie'));
			$this->starrating = new starrating(array('content'=>$show->userrating * 10));
		}else{
			parent::__construct($show);
		}				
	}	
}

// Value objects
class resultLength extends XmlEmitter {}
class endIndex extends XmlEmitter {}

class title extends XmlEmitter {}
class contentId extends XmlEmitter {}
class contentType extends XmlEmitter {} //TV,Movie
class contentQuality extends XmlEmitter {} //SD,HD
class synopsis extends XmlEmitter {}
class genres extends XmlEmitter {}
class subtitle extends XmlEmitter {}
class runtime extends XmlEmitter {}
class date extends XmlEmitter {}
class tvormov extends XmlEmitter {}
class delcommand extends XmlEmitter {}
class starrating extends XmlEmitter {}

class streamFormat extends XmlEmitter {}
class streamQuality extends XmlEmitter {}
class streamBitrate extends XmlEmitter {}
class streamUrl extends XmlEmitter {}

// Child elements
class media extends XmlEmitter {
	public $streamFormat;
	public $streamQuality;
	public $streamBitrate;
	public $streamUrl;	
}

?>
