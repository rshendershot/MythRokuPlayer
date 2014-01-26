<?php
require_once 'settings.php';

// Weather API classes
class Weather extends XmlInjector {}

// MythTV Services API classes
class Program extends XmlInjector {
	public $isScheduled = false;
	public $isRecording = false;
	public $hasJob = false;
	public $isConflict = false;
}
class ProgramTpl extends Program {
	const rsNONE = '<Program><Title>Nothing Found.</Title><Description>No results from your selection.  This is probably not a problem.</Description></Program>';
	const rsEMPTY = '<Program><Title>Service returned nothing.</Title><Description>Data from the service was empty.  Please try again later.</Description></Program>';
	const rsERROR = '<Program><Title/><Description/></Program>';  //it is expected that Title and Description will be filled from the error information before passing this to the Item constructor  -RSH
	
	public function __construct($xml){
		if(useUTC())
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
		if(1==0){ //dummy to make editing easier - RSH
		}elseif(is_a($show,'Weather')){
			//handles Weather current conditions, forecast items, and alerts schemae
			
			$ShowLength = 90;
			$title = "$show->Location";
			$title .= empty($show->Temperature) ? "" : ", $show->Temperature";
			$title .= empty($show->Description) ? "" : ", $show->Description";
			$subtitle = empty($show->Conditions) ? $show->Message : $show->Conditions;
			$subtitle .= empty($show->WindDirection) ? "" : ", $show->WindDirection";
			$subtitle .= empty($show->WindSpeed) ? "" : "@$show->WindSpeed";
			$subtitle .= empty($show->WindGust) ? "" : ", G$show->WindGust";
			$subtitle .= empty($show->Humidity) ? "" : ", hum $show->Humidity";
			$subtitle .= empty($show->Clouds) ? "" : ", vis $show->Clouds mi.";
			$genre = empty($show->Until) ? $show->Temperature : "until $show->Until";
			$synopsis = "$subtitle $show->Source";

			$this->title = new title(array('content'=>$title)); 
			$this->contentQuality = new contentQuality(array('content'=>$RokuDisplayType));
			$this->subtitle = new subtitle(array('content'=>$subtitle));
			$this->addToAttributes('sdImg', "$show->Icon");
			$this->addToAttributes('hdImg', "$show->Icon");
			$this->contentId = new contentId(array('content'=>$show->Location));
			//$this->contentType = new contentType(array('content'=>'TV'));
			//$this->media->streamUrl->setContent("$streamUrl "); //yes the space is required
			
			$this->synopsis = new synopsis(array('content'=>$synopsis));
			$this->genres = new genres(array('content'=>$genre));
			$this->runtime = new runtime(array('content'=>$ShowLength));
						
			$this->date = new date(array('content'=>$show->AsOf));
			$this->tvormov = new tvormov(array('content'=>'weather'));
		}elseif(is_a($show,'Program')){
			/// MythTV Program schema
			
			$ShowLength = convert_datetime($show->EndTime) - convert_datetime($show->StartTime);
			if($show->isScheduled){
				$imgUrl = "$WebServer/$MythRokuDir/images/oval_green.png";
			} elseif($show->isRecording){
				$imgUrl = "$WebServer/$MythRokuDir/images/oval_blue.png";
				$show->Category = 'NOW RECORDING!';
			} elseif($show->hasJob) {
				$imgUrl = "$WebServer/$MythRokuDir/images/oval_orange.png";
				$show->Category = 'NOW PROCESSING A JOB!';
			} elseif($show->isConflict) {
				$imgUrl = "$WebServer/$MythRokuDir/images/oval_red.png";
				$show->Category = 'SCHEDULE CONFLICT!';
			} else {
				$imgUrl = "$WebServer/$MythRokuDir/images/oval_grey.png";
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
		}elseif(is_a($show,'Guide')){
			//handles Pilots/Premieres schema
			
			$ShowLength = convert_datetime($show->endtime) - convert_datetime($show->starttime);
			if($show->recstatus == -1){
				$imgUrl = "$WebServer/$MythRokuDir/images/oval_blue.png";
				$show->category .= ' (WILL RECORD)';
			} elseif($show->recstatus == 10 || $show->recstatus == 7){ //inactive or conflict
				$imgUrl = "$WebServer/$MythRokuDir/images/oval_purple.png";
				$show->category .= ' (' . $show->getStatusName( $show->recstatus ) . ')';
			} elseif($show->last && $show->first){
				$imgUrl = "$WebServer/$MythRokuDir/images/oval_red.png";
				if(!empty($show->recstatus) && $show->recstatus != 10 && $show->recstatus != 7) { 
					$imgUrl = "$WebServer/$MythRokuDir/images/oval_grey.png";
					$show->category .= ' (' . $show->getStatusName( $show->recstatus ) . ')';
				} else {
					$show->category .= ' (ONLY CHANCE)';
				}
			} elseif($show->last) {
				$imgUrl = "$WebServer/$MythRokuDir/images/oval_orange.png";
				if(!empty($show->recstatus) && $show->recstatus != 10 && $show->recstatus != 7) { 
					$imgUrl = "$WebServer/$MythRokuDir/images/oval_grey.png";
					$show->category .= ' (' . $show->getStatusName( $show->recstatus ) . ')';
				} else {
					$show->category .= ' (LAST CHANCE)';
				}				
			} else {
				if(!empty($show->recstatus)) {
					$imgUrl = "$WebServer/$MythRokuDir/images/oval_grey.png";
					$show->category .= ' (' . $show->getStatusName( $show->recstatus ) . ')';
				} else {
					$imgUrl = "$WebServer/$MythRokuDir/images/oval_green.png";
				}
			}
			
			$this->title = new title(array('content'=>normalizeHtml($show->station.' '.$show->title)));
			$this->contentQuality = new contentQuality(array('content'=>$RokuDisplayType));
			$this->subtitle = new subtitle(array('content'=>normalizeHtml($show->subtitle)));
			$this->addToAttributes('sdImg', $imgUrl);
			$this->addToAttributes('hdImg', $imgUrl);
			$this->contentId = new contentId(array('content'=>$show->programid));
			//$this->contentType = new contentType(array('content'=>'TV'));
			//$this->media->streamUrl->setContent("$streamUrl "); //yes the space is required
			$this->synopsis = new synopsis(array('content'=>normalizeHtml($show->description)));
			$this->genres = new genres(array('content'=>normalizeHtml($show->category)));
			$this->runtime = new runtime(array('content'=>$ShowLength));
			$this->date = new date(array('content'=>date("F j, Y, g:i a", convert_datetime($show->starttime))));
			$this->tvormov = new tvormov(array('content'=>'new'));			
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
			
			$videos = StorageGroup::first( array('conditions' => array('groupname = ?', 'Videos')) );	    	
	    	$streamfile = $videos->dirname . $show->filename;
	    	$streamUrl = "$WebServer/$MythRokuDir/image.php?image=" . rawurlencode($streamfile);	    	   	

			// http://www.mythtv.org/wiki/Video_Library#Metadata_Grabber_Troubleshooting
			// http://www.mythtv.org/wiki/MythVideo_File_Parsing#Filenames
//			if(!empty($show->screenshot)){
				$screenart = StorageGroup::first( array('conditions' => array('groupname = ?', 'Screenshots')) );
				$imgfile = $screenart->dirname . $show->screenshot;
//			}elseif(!empty($show->fanart)){
//				$fanart = StorageGroup::first( array('conditions' => array('groupname = ?', 'Fanart')) );
//				$imgfile = $fanart->dirname . $show->fanart;
//			}else{
//				$coverart = StorageGroup::first( array('conditions' => array('groupname = ?', 'Coverart')) );
//				$imgfile = $coverart->dirname . $show->coverfile;
//			}
			//TODO coverart and fanart are 5-10X sizeof screenshots.  videometadata doesn't contain screenshots for movies.  create screenshots and update db
	    	$imgUrl = "$WebServer/$MythRokuDir/image.php?image=" . rawurlencode($imgfile);
	    	
	    	//TODO lookup genres for item::genres.  can be an array?	 			
	    	$category = VideoCategory::first( array('conditions' => array('intid = ?', $show->category)) );    	

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
			$this->runtime = new runtime(array('content'=>$show->length * 60));
			$this->date = new date(array('content'=>date("Y-m-d", convert_datetime($show->starttime))));
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
class contentId extends XmlEmitter {
	public function __construct(){
		$arguments = func_get_args();		
		$arguments[0]['content'] = crc32($arguments[0]['content']);
		
		parent::__construct($arguments[0]);
	}
}
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
