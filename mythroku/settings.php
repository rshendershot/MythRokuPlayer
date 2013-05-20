<?php
require_once 'php-activerecord/ActiveRecord.php';

$WebHostIP = "192.168.1.130";                           // web server IP address
$WebServer = "http://" . $WebHostIP . "/mythweb";       // include path to mythweb eg, http://yourip/mythweb
$MythRokuDir = "mythroku";                              // name of your mythroku directory in the mythweb folder

$MythBackendIP = $WebHostIP;                              // Myth Backend server IP
$MythBackendPort = "6544";                                // Myth Backend services port   

$MythContentSvc = "http://" . $MythBackendIP . ":" . $MythBackendPort . "/Content/";

$MysqlServer  = $WebHostIP;     // mysql server ip/name
$MythTVdb     = "mythconverg";  // mythtv database name
$MythTVdbuser = "mythtv";       // mythtv database user
$MythTVdbpass = "mythtv";       // mythtv database password

$RokuDisplayType = "SD";	// set to the same as your Roku player under display type, HD or SD  
$BitRate = "1500";			// bit rate of endcoded streams

//--- XML Proxy classes ---//
abstract class XmlIterator implements Countable {
	const ATR = 'attribute.'; //since PHP does not yet support annotations
	private $attributes = array();
	private $content;

	public function __construct()
    {
        $arguments = func_get_args();

        if(!empty($arguments)) {
            foreach($arguments[0] as $key => $property) {
                if(property_exists($this, $key)) {
                    $this->{$key} = $property;    
                }else {
                	$atr = str_replace(XmlIterator::ATR,'',$key);
                	if(property_exists($this, $atr)){
                		$this->attributes[$atr] = $property;
                	}
                }
            }
        }                                       	
    }	
	public function count() {
		return count(get_object_vars($this));
	}
	public function __toString() {	
		$stringBuffer = "<" . get_class($this);
		$end =	"</" . get_class($this) . ">";
		
		$associations = array();
		$objects = array();
		foreach($this as $key => $value) {			
   			if($key == 'content') {
   				$this->content = $value;
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



//--- Active Record ORM http://www.phpactiverecord.org, requires version 20110425 or later due to DB DateTime default format ---//

ActiveRecord\DateTime::$DEFAULT_FORMAT = 'db';

$URL = "mysql://$MythTVdbuser:$MythTVdbpass@$MysqlServer/$MythTVdb";

ActiveRecord\Config::initialize(function($cfg)
{
    global $URL;
    
    $cfg->set_model_directory('.');
    $cfg->set_connections(array('PVR1' => $URL));
    
    $cfg->set_default_connection('PVR1');
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

//--- Utility functions ---//

function convert_datetime($str) 
{
	//function to convert mysql timestamp to unix time
	return strtotime( $str. ' UTC' );
}

?>
