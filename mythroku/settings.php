<?php

set_error_handler( "fatal_handler" );

require_once 'php-activerecord/ActiveRecord.php'; // http://www.phpactiverecord.org
const DB_UTC_VER = '1307';  // http://www.mythtv.org/wiki?title=Category:DB_Table&oldid=56896

//EDIT-HERE: Weather Forecaset location
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

//EDIT-HERE: set to the number of upcoming to show in the Top Upcoming and Weather Forecaset display
$UpcomingListLimit = 5;     

//EDIT-HERE: set to the number of New Shows query rows to return
$NewShowsQueryLimit = 500;     

//EDIT-HERE: set to the type of Sports New-Shows to include.  Uncomment only the type you desire
//$NewSportsQueryType = 'Sports%';  //Note: this selection return everything! It is limited though by $NewShowsQueryLimit setting     
//$NewSportsQueryType = 'Sports talk';
//$NewSportsQueryType = 'Sports non-event';
$NewSportsQueryType = 'Sports event';

// EDITs are not normally needed below.  Note:  SD works for heterogenous households (for both HD and SD televisions)
$WebServer = "http://" . $WebHostIP . "/mythweb";
$MythRokuDir = "mythroku";
$localSvcDir = '';

$MythContentSvc = "http://" . $MythBackendIP . ":" . $MythBackendPort . "/Content/";
$MythDvrSvc = "http://" . $MythBackendIP . ":" . $MythBackendPort . "/Dvr/";
$localSvc = "$WebServer/$MythRokuDir/$localSvcDir/";

$RokuDisplayType = "SD";	// set to the same as your Roku player under display type, HD or SD  
$BitRate = "1500";			// bit rate of endcoded streams

$PWS=false;  //use local user stations for reporting  see http://www.wunderground.com/weather/api
$NWS=false;  //use wunderground N.W.S. data instead of its Best Forecast option
$RADIUS='50';  //default for radar map is 100. adjust this to zoom in or out.
$API_KEY='8d114d04cff24445';  //NOTE: wunderground limits use velocity so this may become obsolete without warning.

$db_connections = array(
   'MYSQL' => "mysql://$MythTVdbuser:$MythTVdbpass@$MysqlServer/$MythTVdb"
);

//--- Fatal Error Handler ---//
function fatal_handler($errno, $errstr, $errfile, $errline){
	error_log(">>> fatal_handler: $errstr;  In $errfile;  At line $errline", 0);
	
	print <<<EOF
<categories>
	<banner_ad/>
	<category title="Error" description="Is phpActiveRecord installed?"
		sd_img=""
		hd_img="">
        <categoryLeaf title="see README Testing section."
            feed=""
            description="">
        </categoryLeaf>
	</category>
	<category title="Message" description="$errstr"
		sd_img=""
		hd_img="">
		<categoryLeaf title="see webserver log"
			feed=""
			description="">
		</categoryLeaf>
	</category>
	<category title="Settings" description="Configuration"
		sd_img=""
		hd_img="">
		<categoryLeaf title="Settings"
			feed=""
			description="">
		</categoryLeaf>
	</category>			
</categories>
EOF;
}

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
	return strtotime( useUTC() ? "$str UTC" : $str);
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
    
    function get_airdate(){
    	return $this->read_attribute('airdate');
    }    
           
    function set_airdate($newdate){
    	$this->airdate = $newdate;
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
	
	static $primary_key = 'value';
}

class Guide extends ActiveRecord\Model
{
	static $table_name = 'program';
	
	static function getStatusName($status){
		switch ($status) {
			case -13: return "other recording";
			case -12: return "other tuning";
			case -11: return "missed future";
			case -10: return "tuning";
			case -9: return "failed";
			case -8: return "tuner busy";
			case -7: return "low disk space";
			case -6: return "cancelled";
			case -5: return "missed";
			case -4: return "aborted";
			case -3: return "recorded";
			case -2: return "recording";
			case -1: return "will record";
			case 0: return "unknown";
			case 1: return "dont record";
			case 2: return "previous recording";
			case 3: return "recorded";
			case 4: return "earlier showing";
			case 5: return "too many recordings";
			case 6: return "not listed";
			case 7: return "conflict";
			case 8: return "later showing";
			case 9: return "repeat";
			case 10: return "inactive";
			case 11: return "never record";
			case 12: return "off line";
			case 13: return "other showing";
			default: return "undefined";
		}
	}
}

?>
