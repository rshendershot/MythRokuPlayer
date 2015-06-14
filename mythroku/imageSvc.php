<?php
// adapted from http://forums.phpfreaks.com/topic/106711-php-code-which-supports-byte-range-downloads-for-iphone/#entry547301
// thank you to phpfreak jonsjava

require_once './settings.php'; 

if (isset ($_GET['image'])) { //send a file spec
	$file = rawurldecode($_GET['image']);
	if (file_exists($file)) {					
        if (isset($_SERVER['HTTP_RANGE'])) {
            rangeDownload($file);
        } else {
        	output($file);
        }												
	} else {
		throw new Exception("unknown file: $file");
	}
} elseif (isset($_GET['preview'])) { //send a key of chanid and starttime. 
	$preview = rawurldecode($_GET['preview']);
	$chanid = ltrim(substr($preview,0,6),'_');
	$starttime = substr($preview,6);
	
	$rawstarttime = str_replace(' ', 'T', $starttime);
	
	if(defined('_DEBUG')) error_log(">>>PREVIEW: chanid $chanid : startime $rawstarttime", 0);
	
	if($chanid && $starttime) {		
		$conditions = array('conditions' => array('chanid=? and starttime=? ', $chanid, $rawstarttime)); 
		$record = Recorded::first( $conditions );		
		$file = $record->storagegroups->dirname . $record->basename . '.png'; 
		$t = "0";
		$tfile = $record->storagegroups->dirname . $record->basename . ".$t.png"; 
    	if(!file_exists($file)) { //generate preview images since the user may not be invoking this from myth frontend
    		//file_get_contents($MythContentSvc . 'GetPreviewImage' . "?ChanId=$chanid&StartTime=$rawstarttime");
    		file_get_contents($MythContentSvc . 'GetPreviewImage' . "?ChanId=$chanid&StartTime=$rawstarttime&SecsIn=$t");
    		rename( $tfile, $file );
//     		error_log(
//     			"*** " . implode( '|',
// 			    	get_headers(
// 						$MythContentSvc . 'GetPreviewImage' . rawurlencode("?ChanId=$chanid&StartTime=$rawstarttime")
// 					)	
// 				)			
// 			,0);		
    	}    	
    	
    	try {
			if(filesize($file)){    		
				header('Cache-Control: no-cache');
				output($file);
			} else {
				throw new Exception("unable to get file size of: $file");
			}
    	} catch(Exception $e) {
    		//http_response_code(304);  //only for PHP 5.4 and later
    		header(':', true, '404'); 
    	}
	}	
}

function output($file)
{
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	if (!$finfo) 
		throw new Exception('cannot get file_info.');	
	header('Content-Length: ' . filesize($file));
	header('Content-Type: ' . finfo_file($finfo, $file), true);
	
	$fp = fopen($file, "r");
	if ($fp) {
		while (!feof($fp)) {			
			echo fread($fp, 1024);  //changing the buffer size here seems to make little difference as the chunk is followed by a request for a range -RSH
			flush();
		}
		fclose($fp);
	}else{
		throw new Exception("Unable to open a file handle to $file");
	}
}

function rangeDownload($file)
{
    $fp = @fopen($file, 'rb');

    $size   = filesize($file); // File size
    $length = $size;           // Content length
    $start  = 0;               // Start byte
    $end    = $size - 1;       // End byte
    // Now that we've gotten so far without errors we send the accept range header
    /* At the moment we only support single ranges.
     * Multiple ranges requires some more work to ensure it works correctly
     * and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
     *
     * Multirange support annouces itself with:
     * header('Accept-Ranges: bytes');
     *
     * Multirange content must be sent with multipart/byteranges mediatype,
     * (mediatype = mimetype)
     * as well as a boundry header to indicate the various chunks of data.
     */
    header("Accept-Ranges: 0-$length");
    // header('Accept-Ranges: bytes');
    // multipart/byteranges
    // http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
    if (isset($_SERVER['HTTP_RANGE']))
    {
            $c_start = $start;
            $c_end   = $end;
            // Extract the range string
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            // Make sure the client hasn't sent us a multibyte range
            if (strpos($range, ',') !== false)
            {
                    // (?) Shoud this be issued here, or should the first
                    // range be used? Or should the header be ignored and
                    // we output the whole content?
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    // (?) Echo some info to the client?
                    exit;
            }
            // If the range starts with an '-' we start from the beginning
            // If not, we forward the file pointer
            // And make sure to get the end byte if spesified
            if ($range{0} == '-')
            {
                    // The n-number of the last bytes is requested
                    $c_start = $size - substr($range, 1);
            }
            else
            {
                    $range  = explode('-', $range);
                    $c_start = $range[0];
                    $c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
            }
            /* Check the range and make sure it's treated according to the specs.
             * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
             */
            // End bytes can not be larger than $end.
            $c_end = ($c_end > $end) ? $end : $c_end;
            // Validate the requested range and return an error if it's not correct.
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size)
            {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    // (?) Echo some info to the client?
                    exit;
            }
            $start  = $c_start;
            $end    = $c_end;
            $length = $end - $start + 1; // Calculate new content length
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
    }
    // Notify the client the byte range we'll be outputting
    header("Content-Range: bytes $start-$end/$size");
    header("Content-Length: $length");
    
    if(defined('_DEBUG')) error_log( "*** ".implode("|", headers_list()), 0 );

    // Start buffered download
    $buffer = 1024 * 8;
    while(!feof($fp) && ($p = ftell($fp)) <= $end)
    {
            if ($p + $buffer > $end)
            {
                    // In case we're only outputtin a chunk, make sure we don't
                    // read past the length
                    $buffer = $end - $p + 1;
            }
            set_time_limit(0); // Reset time limit for big files
            echo fread($fp, $buffer);
            flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
    }

    fclose($fp);
}                  

?> 
