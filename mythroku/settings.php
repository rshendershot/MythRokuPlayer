<?php
require_once 'php-activerecord/ActiveRecord.php';
const DB_UTC_VER = '1307';  // http://www.mythtv.org/wiki?title=Category:DB_Table&oldid=56896

//EDIT-HERE: set to the number of upcoming to show in the Top Upcoming and Weather Forecaset display
$UpcomingListLimit = 5;     

//EDIT-HERE: Weather Forecaset location - weather information provided by http://openweathermap.org
$City = 'District of Columbia';
$State = '';
$Country = 'USA';

//EDIT-HERE: Addresses of systems needed to use MythRokuPlayer
$WebHostIP = "192.168.1.130";  // web server IP address
$MysqlServer  = $WebHostIP;     // mysql server ip/name
$MythTVdb     = "mythconverg";  // mythtv database name
$MythTVdbuser = "mythtv";       // mythtv database user
$MythTVdbpass = "mythtv";       // mythtv database password
$MythBackendIP = $WebHostIP;   // Myth Backend server IP
$MythBackendPort = "6544";     // Myth Backend services port   

// Edits are not normally needed below.  Note:  SD works for heterogenous households (for both HD and SD televisions)
$WebServer = "http://" . $WebHostIP . "/mythweb";
$MythRokuDir = "mythroku";

$MythContentSvc = "http://" . $MythBackendIP . ":" . $MythBackendPort . "/Content/";
$MythDvrSvc = "http://" . $MythBackendIP . ":" . $MythBackendPort . "/Dvr/";
$localSvcDir = '';
$localSvc = "$WebServer/$MythRokuDir/$localSvcDir/";

$RokuDisplayType = "SD";	// set to the same as your Roku player under display type, HD or SD  
$BitRate = "1500";			// bit rate of endcoded streams

$db_connections = array(
   'MYSQL' => "mysql://$MythTVdbuser:$MythTVdbpass@$MysqlServer/$MythTVdb"
);

//--- XML Proxy classes ---//
abstract class XmlInjector implements Countable {
	public function __construct(SimpleXMLElement $xml) {
		if(defined('_DEBUG')) print_r($xml);
 
        if(!empty($xml)) {
        	$properties = $xml->children();
            foreach($properties as $key => $property) {
                $this->{$key} = (string)$property;   
            }
        }   
        if(defined('_DEBUG')) print_r($this);                                  			
	}
	public function count() {
		return count(get_object_vars($this));
	}	
}
abstract class XmlEmitter implements Countable {
	const ATR = 'attribute.'; //since PHP does not yet support annotations
	private $attributes = array();
	private $content;

	protected function addToAttributes($atr, $property){
		if(is_scalar($property) || empty($property)){
			if(!property_exists($this, $atr))
				error_log("XmlEmitter::addToAttributes  overriding containment, setting $atr, $property", 0);
			$this->attributes[$atr] = $property;
			return true;
		}
		error_log("XmlEmitter::setContent  non-scalar value: ".print_r($property, true), 0);
		return false;
	}
	protected function getAttribute($attribute){
		error_log("returning attribute for $attribute:  $this->attributes[$attribute]", 0);
		return $this->attributes[$attribute];
	}
	protected function setContent($value){
		if(is_scalar($value) || empty($value)){
			$this->content = $value;
			return true;
		}
		error_log("XmlEmitter::setContent  non-scalar value: ".print_r($value, true), 0);
		return false;
	}
	public function __construct()
    {
        $arguments = func_get_args();
        if(defined('_DEBUG')) print_r($arguments);

        if(!empty($arguments)) {
            foreach($arguments[0] as $key => $property) {
                if(property_exists($this, $key)) {
                    $this->{$key} = $property;    
                }else {
            		$this->addToAttributes(str_replace(XmlEmitter::ATR,'',$key), $property);
                }
            }
        }   
        if(defined('_DEBUG')) print_r($this);                                    	
    }	
	public function count() {
		return count(get_object_vars($this));
	}
	public function Value() { 
		foreach($this as $key => $value) {	
   			if($key == 'content') {
   				$this->setContent($value);
   			}
		}		
		return $this->content; 
	}
	public function __toString() {	
		$stringBuffer = "<" . get_class($this);
		$end =	"</" . get_class($this) . ">";
		
		$associations = array();
		$objects = array();
		foreach($this as $key => $value) {			
   			if($key == 'content') {
   				$this->setContent($value);
   			} elseif(is_array($value)) {
    			$associations[$key] = $value;
    		} elseif(is_object($value)) {
    			$objects[$key] = $value;
    		}     		    				
		}
		
		foreach($this->attributes as $key => $value) {
			$stringBuffer .= " $key=\"$value\"";
		}
		if(count($associations) || count($objects) || isset($this->content)) {
			$stringBuffer .= ">";	
			if(!isset($this->content)){
				$stringBuffer .= " ";	
			}else{
				$stringBuffer .= $this->content;	
			}				
			foreach($objects as $key => $value) {
				$stringBuffer .= $value;
			}		
			foreach($associations as $key => $value) {
    			foreach($value as $child => $childValue){
    				if(!array_key_exists($child, $this->attributes)){
    					$stringBuffer .= $childValue;	
    				}    				
    			} 				
			}		
		} else {
			$end = " />";			
		}
		$stringBuffer .= $end;			
		
		return $stringBuffer;		
	}
}


//--- Utility functions ---//

function convert_date( $date )
{
    list($year, $month, $day) = explode('-', $date);

    if ( 0 === $year  ) { $year  = 1900; }
    if ( 0 === $month ) { $month = 1;    }
    if ( 0 === $day   ) { $day   = 1;    }

    $timestamp = mktime(0, 0, 0, (int)$month, (int)$day, (int)$year);

    return $timestamp;
}

function useUTC(){
	if(!defined('_UTC')){
		$conditions = array('conditions'=>array('value = ?', 'DBSchemaVer'));
		$settings = MythSettings::first($conditions);
		$value = $settings->data;		
		error_log(">>> DB SCHEMA: $value", 0);
		
		define('_UTC', (int)$value >= (int)DB_UTC_VER ? true:false);		
	}
	return _UTC == true; 
}

function convert_datetime($str) {
	//convert date formatted string to unix timestamp
	if(useUTC())
		return convert_datetime_utc($str);
	else
		return convert_datetime_pre($str);
		
}

function convert_datetime_utc($str) 
{
	if(defined('_DEBUG')) error_log(">>>convert_datetime_utc  $str", 0);
	return strtotime( $str. ' UTC' );
}

function convert_datetime_pre( $str ) //mythtv  0.25
{
	if(defined('_DEBUG')) error_log(">>>convert_datetime_pre  $str", 0);
    list($date, $time)            = explode(' ', $str);
    list($year, $month,  $day)    = explode('-', $date);
    list($hour, $minute, $second) = explode(':', $time);

    if ( 0 === $year  ) { $year  = 1900; }
    if ( 0 === $month ) { $month = 1;    }
    if ( 0 === $day   ) { $day   = 1;    }

    return mktime((int)$hour, (int)$minute, (int)$second, (int)$month, (int)$day, (int)$year);
}

function normalizeHtml($string){
	if(is_string($string))
		return htmlspecialchars(preg_replace('/[^(\x20-\x7F)]*/','', $string));
	else
		return ''; 
}

function items_title_compare($a, $b){
	if(  is_a($a,'item') && is_a($b,'item')  ){
		$aTitle = ltrim(preg_replace('/^[Tt]he /', '', $a->title->Value()));
		$bTitle = ltrim(preg_replace('/^[Tt]he /', '', $b->title->Value()));
		
		if($aTitle === $bTitle){  //int compared to $ is true always so use compare identical strict operator, even tho type is already checked. 
			return items_date_compare($a, $b);
		}
				
		return $aTitle < $bTitle ? -1 : 1;
	}else{
		return 0;
	}
}

function items_date_compare($a, $b){
	if(  is_a($a,'item') && is_a($b,'item')  ){
		$aTime = strtotime($a->date->Value());
		$bTime = strtotime($b->date->Value());		

		return $aTime < $bTime ? -1 : 1;
	}else{
		return 0;
	}	
}

//--- Active Record ORM http://www.phpactiverecord.org, requires version 20110425 or later due to DB DateTime default format ---//

ActiveRecord\DateTime::$DEFAULT_FORMAT = 'db';

ActiveRecord\Config::initialize(function($cfg) use ($db_connections)
{    
    $cfg->set_model_directory('.');
    $cfg->set_connections($db_connections);
    
    $cfg->set_default_connection('MYSQL');
});

class Recorded extends ActiveRecord\Model 
{ 
    static $table_name = 'recorded';
    
    static $has_one = array( array('storagegroups'
    	, 'class_name' => 'StorageGroup'    
	    , 'primary_key'=>'storagegroup'
	    , 'foreign_key'=>'groupname')
    );

    function get_starttime() {
        return $this->read_attribute('starttime')->format('db');
    }
           
//    function get_title() {
//    	return preg_replace('/^The /', '', $this->read_attribute('title'));
//    }  
//    
//    function get_fulltitle() {
//    	return $this->read_attribute('title');
//    } 
}

class StorageGroup extends ActiveRecord\Model
{
    static $table_name = 'storagegroup';
    
    static $read_only = 'true';
    
    static $has_many = array( array('recordings'
    	, 'class_name' => 'Recorded'
    	, 'primary_key'=>'groupname'
    	, 'foreign_key'=>'storagegroup') 
    );
    
    function get_dirname(){
    	return $this->read_attribute('dirname') ."/";
    }
}    

class VideoMetadata extends ActiveRecord\Model
{
    static $table_name = 'videometadata';
}    

class VideoCategory extends ActiveRecord\Model
{
    static $table_name = 'videocategory';
}   

class JobQueue extends ActiveRecord\Model
{
	static $table_name = 'jobqueue';
}
 
class MythSettings extends ActiveRecord\Model
{
	static $table_name = 'settings';
}

class Guide extends ActiveRecord\Model
{
	static $table_name = 'program';
}
 
?>
