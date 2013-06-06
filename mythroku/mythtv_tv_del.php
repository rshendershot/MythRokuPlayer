<?php

//get the local info from the settings file
require_once 'settings.php';

if (isset($_GET['basename']))
{
    $basefname = $_GET['basename'];
    $conditions = array('conditions' => array('basename = ? ', $basefname));
    $recordings = Recorded::all($conditions);
    if(count($recordings) != 1) {
        error_log( "There are " . count($recordings) . " items with the basename: $basefname", 0 );
    }else{
        $recording = $recordings[0];
        
        error_log( "here we delete $recording->basename from the database.", 0 ); 
        $recording->delete();
        
        $fname = $recording->storagegroups->dirname . strtok($recording->basename, "."); 
        foreach(glob($fname . "*") as $file){
            error_log( "here we delete $file from the filesystem.", 0 ); 
            unlink($file);
        }
    }
}else{
    error_log("the 'basename' was not passed to this routine!", 0);
}

?>

