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


//--- Active Record ORM , requires version 20110425 or later due to DB DateTime default format ---//

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
