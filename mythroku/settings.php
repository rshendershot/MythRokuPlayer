<?php
require_once 'php-activerecord/ActiveRecord.php';

$WebServer = "http://192.168.1.200/mythweb";   // include path to mythweb eg, http://yourip/mythweb
$MythRokuDir = "mythroku";				       // name of your mythroku directory in the mythweb folder
$RokuDisplayType = "SD";				       // set to the same as your Roku player under display type, HD or SD  
$BitRate = "1500";					           // bit rate of endcoded streams
$MysqlServer = "192.168.1.200";                // mysql server ip/name
$MythTVdb = "mythconverg";                     // mythtv database name
$MythTVdbuser = "mythtv";                      // mythtv database user
$MythTVdbpass = "mythtv";                      // mythtv database password
$MythTVvideos = "/data/Videos/";			   // mythweb/data symbolic link to the storage group for mythvideos 

/* can use TimeOffset in mythconver.settings */
//date_default_timezone_set ( 'GMT' );
//date_default_timezone_set ( 'America/Chicago' );

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

//function to convert mysql timestamp to unix time
function convert_datetime($str) 
{
	return strtotime( $str. ' UTC' );
}

?>
